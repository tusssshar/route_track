<?php
	include_once(__DIR__ . "/includes/page_start.php");

	$trip_id = (int) (isset($_GET['trip_id']) ? $_GET['trip_id'] : 0);
	$trip = fleet::trip($trip_id);
	if (!$trip) {
		echo "<div class='container-fluid detail-page'><div class='alert alert-danger'>Trip not found. <a href='trips.php'>Back to trips</a></div></div>";
		include ROOTPATH . '/admin/templates/admin_footer_start.php';
		include ROOTPATH . '/admin/templates/admin_footer_end.php';
		exit;
	}
	$vehicle = fleet::vehicle($trip['vehicle_id']);
	$progress = fleet::progress($trip_id);
	$load = fleet::load_totals($trip_id);
	$skus = fleet::load_summary($trip_id);
	$packages = fleet::package_summary($trip_id);
	$expense_summary = fleet::expense_summary($trip_id);
	$expenses = fleet::rows("SELECT * FROM trip_expenses WHERE trip_id=" . $trip_id . " ORDER BY expense_time, expense_id");
	$events = fleet::rows("SELECT E.*, U.first_name, U.last_name FROM trip_events E LEFT JOIN users U ON U.user_id=E.user_id WHERE E.trip_id=" . $trip_id . " ORDER BY E.event_time, E.event_id");
	$stops = fleet::rows("SELECT D.delivery_id, D.stop_number, D.order_number, D.status, D.delivery_time, D.exception_reason, D.latitude, D.longitude, D.signature_text,
			M.manifest_type, C.customer_name, A.address_line_1, A.city,
			(SELECT COALESCE(SUM(qty),0) FROM delivery_items DI WHERE DI.delivery_id=D.delivery_id) AS units
		FROM deliveries D
		LEFT JOIN manifests M ON M.manifest_id=D.manifest_id
		LEFT JOIN customers C ON C.customer_id=D.customer_id
		LEFT JOIN customer_addresses A ON A.address_id=D.address_id
		WHERE D.trip_id=" . $trip_id . " ORDER BY D.stop_number, D.delivery_id");
	$points = fleet::track_points($trip);
	$last_point = count($points) > 0 ? $points[count($points) - 1] : null;

	$km = $trip['end_odometer'] !== null && $trip['start_odometer'] !== null ? $trip['end_odometer'] - $trip['start_odometer'] : null;
	$duration = "";
	if ($trip['start_time']) {
		$seconds = strtotime($trip['end_time'] ? $trip['end_time'] : date("Y-m-d H:i:s")) - strtotime($trip['start_time']);
		$duration = floor($seconds / 3600) . "h " . floor(($seconds % 3600) / 60) . "m" . ($trip['end_time'] ? "" : " so far");
	}
	$status_class = str_replace(" ", "-", $trip['status']);
	$settle = fleet::settlement($trip_id);
	$settled = $settle['status'] == "Settled";
	$can_expense = $trip['status'] != "Cancelled" && !$settled;	// a settled trip sheet locks the expenses
?>
<div class="container-fluid detail-page">
  <div class="page-header">
    <h4>
      <div class="clearfix">
        <div class="pull-left"><a href="trips.php"><span class="section-heading">Trips</span></a> &raquo; <?= fleet::h($trip['trip_number']) ?>
          <span class="fleet-badge fleet-<?= $status_class ?>"><?= fleet::h($trip['status']) ?></span></div>
        <div class="pull-right"><small>
          <?php if ($trip['status'] == "Planned") { ?>
            <a href="javascript:void(0);" class="btn btn-info btn-sm" onclick="tripAction('loaded')"><i class="fa fa-cubes"></i> Mark Loaded</a>
          <?php } ?>
          <?php if (in_array($trip['status'], array("Planned", "Loaded"))) { ?>
            <a href="javascript:void(0);" class="btn btn-default btn-sm" onclick="tripAction('refresh_orders')" title="Add orders created for this route/date after planning"><i class="fa fa-refresh"></i> Refresh Orders</a>
            <a href="javascript:void(0);" class="btn btn-default btn-sm" onclick="ajaxNewModify(<?= $trip_id ?>)"><i class="fa fa-exchange"></i> Reassign</a>
            <a href="javascript:void(0);" class="btn btn-primary btn-sm" onclick="showPanel('#startPanel')"><i class="fa fa-play"></i> Start Trip</a>
            <a href="javascript:void(0);" class="btn btn-danger btn-sm" onclick="showPanel('#cancelPanel')"><i class="fa fa-ban"></i> Cancel</a>
          <?php } ?>
          <?php if ($trip['status'] == "In Transit") { ?>
            <a href="javascript:void(0);" class="btn btn-success btn-sm" onclick="showPanel('#completePanel')"><i class="fa fa-flag-checkered"></i> Complete Trip</a>
          <?php } ?>
          <?php if ($trip['status'] == "Completed") { ?>
            <a href="trip_sheet.php?trip_id=<?= $trip_id ?>" target="_blank" class="btn btn-primary btn-sm" title="Proof of delivery, goods reconciliation, toll/tax/expenses and driver settlement"><i class="fa fa-file-text-o"></i> Trip Sheet</a>
            <a href="trip_sheet.php?trip_id=<?= $trip_id ?>&amp;export=csv" class="btn btn-default btn-sm"><i class="fa fa-download"></i> CSV</a>
          <?php } ?>
        </small></div>
      </div>
    </h4>
  </div>

  <p class="text-muted small"><i class="fa fa-clock-o"></i> Times in <?= fleet::h(company_settings::tz_label()) ?></p>
  <div class="alert alert-success" id="successMsg" style="display:none"></div>
  <div class="alert alert-danger" id="errorMsg" style="display:none"></div>

  <!-- status change panels -->
  <div class="well well-sm" id="startPanel" style="display:none">
    <form class="form-inline" onsubmit="tripAction('start', $(this)); return false;">
      <strong>Start trip:</strong> &nbsp;
      <div class="form-group"><input type="number" min="<?= (int) $vehicle['odometer_km'] ?>" name="odometer" class="form-control" placeholder="Start odometer (<?= fleet::du() ?>)" value="<?= (int) $vehicle['odometer_km'] ?>" required /></div>
      <span class="text-muted">last recorded <?= number_format($vehicle['odometer_km']) ?> <?= fleet::du() ?></span>
      <button type="submit" class="btn btn-primary btn-sm">Start</button> <a href="javascript:void(0);" onclick="$('#startPanel').hide()">Close</a>
    </form>
  </div>
  <div class="well well-sm" id="completePanel" style="display:none">
    <form class="form-inline" onsubmit="tripAction('complete', $(this)); return false;">
      <strong>Complete trip:</strong> &nbsp;
      <div class="form-group"><input type="number" min="<?= (int) $trip['start_odometer'] ?>" name="odometer" class="form-control" placeholder="End odometer (<?= fleet::du() ?>)" required /></div>
      <span class="text-muted">started at <?= number_format((int) $trip['start_odometer']) ?> <?= fleet::du() ?></span>
      <?php if ($progress['open'] > 0) { ?>
        <div class="form-group"><input type="text" name="force_note" class="form-control" style="width:380px" placeholder="<?= $progress['open'] ?> stops still open - note why goods return to depot" /></div>
      <?php } ?>
      <button type="submit" class="btn btn-success btn-sm">Complete</button> <a href="javascript:void(0);" onclick="$('#completePanel').hide()">Close</a>
    </form>
  </div>
  <div class="well well-sm" id="cancelPanel" style="display:none">
    <form class="form-inline" onsubmit="tripAction('cancel', $(this)); return false;">
      <strong>Cancel trip:</strong> &nbsp;
      <div class="form-group"><input type="text" name="reason" class="form-control" style="width:380px" placeholder="Reason (vehicle breakdown, driver absent ...)" required /></div>
      <button type="submit" class="btn btn-danger btn-sm">Cancel Trip</button> <a href="javascript:void(0);" onclick="$('#cancelPanel').hide()">Close</a>
    </form>
  </div>

  <div class="row" id="divList">
    <!-- assignment -->
    <div class="col-md-4">
      <table class="table table-condensed">
        <tr><th>Date</th><td><?= fleet::h($trip['trip_date']) ?></td></tr>
        <tr><th>Route</th><td><?= fleet::h($trip['route_name']) ?></td></tr>
        <tr><th>Vehicle</th><td><a href="trips.php?vehicle_id=<?= (int) $trip['vehicle_id'] ?>"><?= fleet::h($trip['registration_no']) ?></a> &middot; <?= fleet::h($trip['vehicle_type']) ?>
          <?php $warnings = fleet::document_warnings($vehicle); if (count($warnings) > 0) { ?><br><span class="fleet-warn"><?= implode("<br>", array_map(array('fleet', 'h'), $warnings)) ?></span><?php } ?></td></tr>
        <tr><th>Driver</th><td><?= fleet::h(fleet::driver_name($trip)) ?><?= $trip['driver_phone'] != "" ? " &middot; " . fleet::h($trip['driver_phone']) : "" ?></td></tr>
        <?php if ($trip['helper_name'] != "") { ?><tr><th>Helper</th><td><?= fleet::h($trip['helper_name']) ?></td></tr><?php } ?>
        <tr><th>Odometer</th><td><?= $trip['start_odometer'] !== null ? number_format($trip['start_odometer']) : "-" ?> &rarr; <?= $trip['end_odometer'] !== null ? number_format($trip['end_odometer']) : "-" ?> <?= fleet::du() ?></td></tr>
        <tr><th>Time</th><td><?= fleet::h($trip['start_time'] ? $trip['start_time'] : "not started") ?><?= $trip['end_time'] ? " &rarr; " . fleet::h($trip['end_time']) : "" ?><?= $duration != "" ? "<br><small>" . $duration . "</small>" : "" ?></td></tr>
        <?php if ($trip['close_note'] != "") { ?><tr><th>Close note</th><td class="fleet-warn"><?= fleet::h($trip['close_note']) ?></td></tr><?php } ?>
        <?php if ($trip['notes'] != "") { ?><tr><th>Notes</th><td><?= fleet::h($trip['notes']) ?></td></tr><?php } ?>
      </table>
    </div>
    <!-- KPIs -->
    <div class="col-md-8">
      <div class="row">
        <div class="col-sm-3"><div class="fleet-kpi"><div class="v"><?= $progress['delivered'] ?> / <?= $progress['total'] ?></div><div class="l">Stops delivered<?= $progress['exceptions'] > 0 ? ", " . $progress['exceptions'] . " exception" : "" ?><?= $progress['open'] > 0 ? ", " . $progress['open'] . " open" : "" ?></div></div></div>
        <div class="col-sm-3"><div class="fleet-kpi"><div class="v"><?= $load['on_board_units'] ?></div><div class="l">Units on vehicle now (<?= $load['sku_count'] ?> SKU, <?= $load['packages_on_board'] ?> pkgs)</div></div></div>
        <div class="col-sm-3"><div class="fleet-kpi"><div class="v"><?= $load['loaded_units'] ?> &rarr; <?= $load['delivered_units'] ?></div><div class="l">Units loaded &rarr; delivered<?= $load['picked_up_units'] > 0 ? ", " . $load['picked_up_units'] . " picked up" : "" ?><?= $trip['capacity_units'] > 0 ? " &middot; cap " . (int) $trip['capacity_units'] : "" ?></div></div></div>
        <div class="col-sm-3"><div class="fleet-kpi"><div class="v"><?= $km !== null ? number_format($km) : "-" ?></div><div class="l"><?= ucfirst(fleet::du()) ?> driven<?= $km ? " &middot; " . fleet::money($expense_summary['total'] / $km) . " per " . fleet::du() : "" ?></div></div></div>
      </div>
      <div class="row">
        <div class="col-sm-3"><div class="fleet-kpi"><div class="v"><?= fleet::money($expense_summary['toll']) ?></div><div class="l">Toll paid (<?= (int) $expense_summary['toll_count'] ?> plazas)</div></div></div>
        <div class="col-sm-3"><div class="fleet-kpi"><div class="v"><?= fleet::money($expense_summary['tax']) ?></div><div class="l">Tax paid (entry / permit / green)</div></div></div>
        <div class="col-sm-3"><div class="fleet-kpi"><div class="v"><?= fleet::money($expense_summary['fuel']) ?></div><div class="l">Fuel (<?= $expense_summary['fuel_litres'] + 0 ?> <?= fleet::fu() ?>)</div></div></div>
        <div class="col-sm-3"><div class="fleet-kpi"><div class="v"><?= fleet::money($expense_summary['total']) ?></div><div class="l">Total trip expenses</div></div></div>
      </div>
    </div>
  </div>
  <div class="row"><div class="col-md-12" id="divForm" style="display:none"></div></div>

  <?php if ($trip['status'] != "Cancelled") { ?>
  <!-- driver cash settlement -->
  <div class="panel <?= $settled ? "panel-success" : "panel-default" ?>" style="margin-bottom:10px">
    <div class="panel-heading"><strong>Driver cash settlement</strong> &middot; <?= $settled ? "<i class='fa fa-lock'></i> Settled" : "Pending" ?>
      <span class="text-muted small">&middot; advance <?= fleet::money($settle['advance']) ?> &minus; paid by driver (<?= fleet::h(implode("/", fleet::$DRIVER_PAID_MODES)) ?>) <?= fleet::money($settle['driver_paid']) ?>
        = <strong><?= fleet::h($settle['outcome']) ?></strong> &middot; paid by company <?= fleet::money($settle['company_paid']) ?></span></div>
    <div class="panel-body">
      <?php if ($settled) { ?>
        Trip sheet approved by <strong><?= fleet::h(fleet::driver_name(fleet::row("SELECT first_name, last_name FROM users WHERE user_id=" . (int) $settle['settled_by']) ?: array("first_name" => "-", "last_name" => ""))) ?></strong>
        on <?= fleet::h($settle['settled_at']) ?><?= $settle['note'] != "" ? " &middot; " . fleet::h($settle['note']) : "" ?>. Expenses and advance are locked.
      <?php } else { ?>
        <form class="form-inline" onsubmit="tripAction('set_advance', $(this)); return false;" style="display:inline-block;margin-right:20px">
          <label>Cash advance</label>
          <input type="number" step="0.01" min="0" name="cash_advance" class="form-control input-sm" style="width:120px" value="<?= fleet::h($settle['advance'] + 0) ?>">
          <button type="submit" class="btn btn-default btn-sm">Save</button>
        </form>
        <?php if ($trip['status'] == "Completed") { ?>
        <form class="form-inline" onsubmit="tripAction('settle', $(this)); return false;" style="display:inline-block">
          <input type="text" name="settlement_note" class="form-control input-sm" style="width:320px"
            placeholder="<?= $settle['missing_receipts'] > 0 ? $settle['missing_receipts'] . " cash expense(s) without receipt - note required" : "Note (optional)" ?>">
          <button type="submit" class="btn btn-success btn-sm"><i class="fa fa-check"></i> Approve Trip Sheet &amp; Settle</button>
        </form>
        <?php } else { ?><span class="text-muted small">The trip sheet can be approved after the trip is completed.</span><?php } ?>
      <?php } ?>
    </div>
  </div>
  <?php } ?>

  <ul class="nav nav-tabs" style="margin-top:10px">
    <li class="active"><a data-toggle="tab" href="#tabLoad">Load Sheet (<?= count($skus) ?> SKU)</a></li>
    <li><a data-toggle="tab" href="#tabStops">Stops (<?= $progress['total'] ?>)</a></li>
    <li><a data-toggle="tab" href="#tabExpenses">Toll, Tax &amp; Expenses (<?= count($expenses) ?>)</a></li>
    <li><a data-toggle="tab" href="#tabTrack" id="trackTabLink">Tracking &amp; Timeline</a></li>
  </ul>
  <div class="tab-content" style="padding-top:10px">

    <!-- load -->
    <div id="tabLoad" class="tab-pane fade in active">
      <table class="table table-hover table-condensed">
        <thead><tr><th>SKU</th><th>Product</th><th>UOM</th><th class="num">Orders</th><th class="num">Loaded</th><th class="num">Delivered</th><th class="num">Picked Up</th><th class="num">On Vehicle Now</th></tr></thead>
        <tbody>
        <?php if (count($skus) == 0) { ?><tr><td colspan="100%" align="center">No items on this trip</td></tr><?php } ?>
        <?php foreach ($skus as $s) { $on_board = $s['loaded'] - $s['delivered'] + $s['picked_up']; ?>
          <tr>
            <td><?= fleet::h($s['sku']) ?></td><td><?= fleet::h($s['product_name']) ?></td><td><?= fleet::h($s['uom']) ?></td>
            <td class="num"><?= (int) $s['orders'] ?></td><td class="num"><?= (int) $s['loaded'] ?></td><td class="num"><?= (int) $s['delivered'] ?></td>
            <td class="num"><?= (int) $s['picked_up'] ?><?= $s['pickup_planned'] > 0 ? " / " . (int) $s['pickup_planned'] : "" ?></td>
            <td class="num"><strong><?= $on_board ?></strong></td>
          </tr>
        <?php } ?>
        </tbody>
        <?php if (count($skus) > 0) { ?>
        <tfoot><tr><th colspan="4">Total</th><th class="num"><?= $load['loaded_units'] ?></th><th class="num"><?= $load['delivered_units'] ?></th><th class="num"><?= $load['picked_up_units'] ?></th><th class="num"><?= $load['on_board_units'] ?></th></tr></tfoot>
        <?php } ?>
      </table>
      <?php if (count($packages) > 0) { ?>
      <h5>Packages</h5>
      <table class="table table-condensed" style="max-width:600px">
        <thead><tr><th>Package Type</th><th class="num">Loaded</th><th class="num">Delivered</th><th class="num">Picked Up</th><th class="num">On Vehicle Now</th></tr></thead>
        <tbody>
        <?php foreach ($packages as $p) { ?>
          <tr><td><?= fleet::h($p['package_type']) ?></td><td class="num"><?= (int) $p['loaded'] ?></td><td class="num"><?= (int) $p['delivered'] ?></td><td class="num"><?= (int) $p['picked_up'] ?></td><td class="num"><strong><?= $p['loaded'] - $p['delivered'] + $p['picked_up'] ?></strong></td></tr>
        <?php } ?>
        </tbody>
      </table>
      <?php } ?>
    </div>

    <!-- stops -->
    <div id="tabStops" class="tab-pane fade">
      <table class="table table-hover table-condensed">
        <thead><tr><th>Stop</th><th>Type</th><th>Order No</th><th>Customer</th><th>Address</th><th class="num">Units</th><th>Status</th><th>Received By</th><th>Delivered At</th><th></th></tr></thead>
        <tbody>
        <?php if (count($stops) == 0) { ?><tr><td colspan="100%" align="center">No orders on this trip</td></tr><?php } ?>
        <?php foreach ($stops as $s) { ?>
          <tr>
            <td><?= (int) $s['stop_number'] ?></td><td><?= fleet::h($s['manifest_type']) ?></td><td><?= fleet::h($s['order_number']) ?></td>
            <td><?= fleet::h($s['customer_name']) ?></td><td><?= fleet::h(trim($s['address_line_1'] . ", " . $s['city'], ", ")) ?></td>
            <td class="num"><?= (int) $s['units'] ?></td>
            <td><?= fleet::h($s['status']) ?><?= $s['exception_reason'] != "" ? "<br><small class='fleet-warn'>" . fleet::h($s['exception_reason']) . "</small>" : "" ?></td>
            <td><?= fleet::h($s['signature_text']) ?></td>
            <td><?= in_array($s['status'], fleet::$CLOSED_ORDER_STATUSES) && $s['delivery_time'] > "0000-00-00 00:00:00" ? fleet::h($s['delivery_time']) : "-" ?></td>
            <td><?php if (in_array($s['status'], fleet::$CLOSED_ORDER_STATUSES)) { ?><a href="trip_sheet.php?trip_id=<?= $trip_id ?>&amp;delivery_id=<?= (int) $s['delivery_id'] ?>" target="_blank" title="Delivery receipt for the customer"><i class="fa fa-print"></i> Receipt</a><?php } ?></td>
          </tr>
        <?php } ?>
        </tbody>
      </table>
    </div>

    <!-- expenses -->
    <div id="tabExpenses" class="tab-pane fade">
      <?php if (count($expense_summary['by_type']) > 0) { ?>
      <p>
        <?php foreach ($expense_summary['by_type'] as $type => $row) { ?>
          <span class="label label-default" style="font-size:12px;margin-right:6px"><?= fleet::h($type) ?>: <?= fleet::money($row['amount']) ?> (<?= (int) $row['entries'] ?>)</span>
        <?php } ?>
      </p>
      <?php } ?>
      <table class="table table-hover table-condensed">
        <thead><tr><th>When</th><th>Type</th><th>Location / Plaza</th><th>Paid By</th><th>Reference</th><th class="num">Amount</th><th>Receipt</th><th>Entered</th><th></th></tr></thead>
        <tbody>
        <?php if (count($expenses) == 0) { ?><tr><td colspan="100%" align="center">No toll, tax or other expense recorded</td></tr><?php } ?>
        <?php foreach ($expenses as $e) { ?>
          <tr id="exp_<?= (int) $e['expense_id'] ?>">
            <td><?= fleet::h($e['expense_time']) ?></td>
            <td><?= fleet::h($e['expense_type']) ?><?= $e['fuel_litres'] > 0 ? "<br><small>" . ($e['fuel_litres'] + 0) . " " . fleet::h(fleet::fu()) . "</small>" : "" ?></td>
            <td><?= fleet::h($e['location']) ?></td>
            <td><?= fleet::h($e['payment_mode']) ?></td>
            <td><?= fleet::h($e['reference_no']) ?></td>
            <td class="num"><?= fleet::money($e['amount']) ?></td>
            <td><?= $e['receipt_file'] != "" ? "<a target='_blank' href='" . SITEROOT . "/uploads/expense/" . rawurlencode($e['receipt_file']) . "'><i class='fa fa-paperclip'></i> view</a>" : "<span class='fleet-warn'>none</span>" ?></td>
            <td><small><?= fleet::h($e['entered_by']) ?></small></td>
            <td><?php if (!$settled) { ?><a href="javascript:void(0);" onclick="deleteExpense(<?= (int) $e['expense_id'] ?>)" title="Delete"><i class="fa fa-trash-o"></i></a><?php } ?></td>
          </tr>
        <?php } ?>
        </tbody>
        <?php if (count($expenses) > 0) { ?><tfoot><tr><th colspan="5">Total</th><th class="num"><?= fleet::money($expense_summary['total']) ?></th><th colspan="3"></th></tr></tfoot><?php } ?>
      </table>

      <?php if ($can_expense) { ?>
      <div class="panel panel-info" style="max-width:900px">
        <div class="panel-heading">Record Toll / Tax / Expense</div>
        <div class="panel-body">
          <form id="expenseForm" class="form-horizontal" enctype="multipart/form-data" onsubmit="addExpense(); return false;">
            <input type="hidden" name="action" value="add_expense"><input type="hidden" name="trip_id" value="<?= $trip_id ?>">
            <div class="form-group">
              <label class="col-sm-2 control-label"><span class="error">*</span>Type</label>
              <div class="col-sm-4"><select name="expense_type" id="expense_type" class="form-control" required onchange="$('#fuelRow').toggle(this.value=='Fuel')"><option value="">Select One</option><?= fleet::options(fleet::$EXPENSE_TYPES) ?></select></div>
              <label class="col-sm-2 control-label"><span class="error">*</span>Amount</label>
              <div class="col-sm-4"><input type="number" step="0.01" min="0.01" name="amount" class="form-control" required /></div>
            </div>
            <div class="form-group">
              <label class="col-sm-2 control-label">Paid By</label>
              <div class="col-sm-4"><select name="payment_mode" class="form-control"><?= fleet::options(fleet::$PAYMENT_MODES) ?></select></div>
              <label class="col-sm-2 control-label">When</label>
              <div class="col-sm-4"><input type="datetime-local" name="expense_time" class="form-control" value="<?= date("Y-m-d\TH:i") ?>" /></div>
            </div>
            <div class="form-group">
              <label class="col-sm-2 control-label">Location</label>
              <div class="col-sm-4"><input type="text" name="location" class="form-control" placeholder="Toll plaza / check post / pump" /></div>
              <label class="col-sm-2 control-label">Receipt No</label>
              <div class="col-sm-4"><input type="text" name="reference_no" class="form-control" placeholder="Receipt / FASTag txn id" /></div>
            </div>
            <div class="form-group" id="fuelRow" style="display:none">
              <label class="col-sm-2 control-label"><span class="error">*</span>Fuel (<?= fleet::fu() ?>)</label>
              <div class="col-sm-4"><input type="number" step="0.01" min="0" name="fuel_litres" class="form-control" /></div>
              <label class="col-sm-2 control-label">Odometer</label>
              <div class="col-sm-4"><input type="number" min="0" name="odometer_km" class="form-control" /></div>
            </div>
            <div class="form-group">
              <label class="col-sm-2 control-label">Receipt</label>
              <div class="col-sm-4"><input type="file" name="receipt" accept=".jpg,.jpeg,.png,.pdf" class="form-control" style="padding:0" /></div>
              <div class="col-sm-6"><button type="submit" class="btn btn-success"><i class="fa fa-floppy-o"></i> Save Expense</button></div>
            </div>
          </form>
        </div>
      </div>
      <?php } ?>
    </div>

    <!-- tracking -->
    <div id="tabTrack" class="tab-pane fade">
      <div class="row">
        <div class="col-md-8">
          <?php if (count($points) == 0) { ?>
            <div class="alert alert-info">No GPS points yet. The driver app sends them through <code>webservices/get_driver_current_location.php</code> while the trip is running.</div>
          <?php } else { ?>
            <p><strong><?= count($points) ?></strong> GPS points &middot; last seen <strong><?= fleet::h($last_point['add_date_time']) ?></strong> at <?= fleet::h($last_point['latitude']) ?>, <?= fleet::h($last_point['longitude']) ?></p>
          <?php } ?>
          <div id="tripMap" style="height:420px;border:1px solid #ddd;display:none"></div>
        </div>
        <div class="col-md-4">
          <h5>Timeline</h5>
          <table class="table table-condensed">
          <?php foreach ($events as $ev) { ?>
            <tr><td style="white-space:nowrap"><small><?= fleet::h($ev['event_time']) ?></small></td>
              <td><strong><?= fleet::h($ev['event_type']) ?></strong> <small class="text-muted">(<?= fleet::h($ev['source']) ?><?= $ev['first_name'] != "" ? ", " . fleet::h(fleet::driver_name($ev)) : "" ?>)</small><br><small><?= fleet::h($ev['note']) ?></small></td></tr>
          <?php } ?>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include ROOTPATH . '/admin/templates/admin_footer_start.php'; ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
	var TRIP_ID = <?= $trip_id ?>;
	var TRACK_POINTS = <?= json_encode(array_map(function ($p) { return array((float) $p['latitude'], (float) $p['longitude'], $p['add_date_time']); }, $points)) ?>;
	var STOP_POINTS = <?= json_encode(array_values(array_map(function ($s) { return array((float) $s['latitude'], (float) $s['longitude'], $s['customer_name'] . " - " . $s['status']); },
		array_filter($stops, function ($s) { return is_numeric($s['latitude']) && is_numeric($s['longitude']); })))) ?>;
</script>
<script type="text/javascript" src="js/trip_detail.js?v=<?= filemtime(__DIR__ . "/js/trip_detail.js") ?>"></script>
<?php include ROOTPATH . '/admin/templates/admin_footer_end.php'; ?>
