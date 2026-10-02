<?php
	// CSV export runs before any HTML is sent
	$export = isset($_GET['export']);
	if ($export) {
		include_once(__DIR__ . "/../../../bootstrap.php");
		include(ROOTPATH . "/admin/authorized.php");
		if (!fleet::has_access()) {
			exit("No access");
		}
	} else {
		include_once(__DIR__ . "/includes/page_start.php");
	}

	$date_from = isset($_GET['date_from']) && fleet::valid_date($_GET['date_from']) ? $_GET['date_from'] : date("Y-m-01");
	$date_to = isset($_GET['date_to']) && fleet::valid_date($_GET['date_to']) ? $_GET['date_to'] : date("Y-m-d");
	$group_by = isset($_GET['group_by']) && in_array($_GET['group_by'], array("vehicle", "driver", "trip")) ? $_GET['group_by'] : "vehicle";
	$vehicle_id = (int) (isset($_GET['vehicle_id']) ? $_GET['vehicle_id'] : 0);
	$driver_id = (int) (isset($_GET['driver_id']) ? $_GET['driver_id'] : 0);

	$group_columns = array(
		"vehicle" => array("T.vehicle_id", "V.registration_no", "Vehicle"),
		"driver" => array("T.driver_id", "CONCAT(U.first_name,' ',U.last_name)", "Driver"),
		"trip" => array("T.trip_id", "CONCAT(T.trip_number,' | ',V.registration_no,' | ',U.first_name,' ',U.last_name,' | ',R.name)", "Trip | Vehicle | Driver | Route"),
	);
	list($group_key, $group_label, $group_title) = $group_columns[$group_by];

	$where = "T.status<>'Cancelled' AND T.trip_date BETWEEN '" . $date_from . "' AND '" . $date_to . "'";
	if ($vehicle_id > 0) {
		$where .= " AND T.vehicle_id=" . $vehicle_id;
	}
	if ($driver_id > 0) {
		$where .= " AND T.driver_id=" . $driver_id;
	}
	$tax_list = fleet::in_list(fleet::$TAX_EXPENSE_TYPES);

	$rows = fleet::rows("SELECT " . $group_label . " AS label,
			COUNT(*) AS trips,
			SUM(T.status='Completed') AS completed,
			SUM(IF(T.status='Completed', T.end_odometer-T.start_odometer, 0)) AS km,
			SUM(COALESCE(S.stops,0)) AS stops, SUM(COALESCE(S.delivered,0)) AS delivered_stops,
			SUM(COALESCE(L.skus,0)) AS sku_lines, SUM(COALESCE(L.loaded,0)) AS units_loaded, SUM(COALESCE(L.delivered,0)) AS units_delivered,
			SUM(COALESCE(E.toll,0)) AS toll, SUM(COALESCE(E.toll_count,0)) AS toll_count,
			SUM(COALESCE(E.tax,0)) AS tax, SUM(COALESCE(E.fuel,0)) AS fuel, SUM(COALESCE(E.litres,0)) AS litres,
			SUM(COALESCE(E.total,0)) AS total
		FROM trips T
		LEFT JOIN vehicles V ON V.vehicle_id=T.vehicle_id
		LEFT JOIN users U ON U.user_id=T.driver_id
		LEFT JOIN routes R ON R.route_id=T.route_id
		LEFT JOIN (SELECT trip_id, COUNT(*) AS stops, SUM(status='Delivered') AS delivered FROM deliveries WHERE trip_id>0 GROUP BY trip_id) S ON S.trip_id=T.trip_id
		LEFT JOIN (SELECT D.trip_id, COUNT(DISTINCT DI.sku) AS skus,
				SUM(IF(M.manifest_type='Pickup',0,DI.qty)) AS loaded, SUM(IF(M.manifest_type='Pickup',0,DI.qty_delivered)) AS delivered
			FROM delivery_items DI JOIN deliveries D ON D.delivery_id=DI.delivery_id LEFT JOIN manifests M ON M.manifest_id=D.manifest_id
			WHERE D.trip_id>0 GROUP BY D.trip_id) L ON L.trip_id=T.trip_id
		LEFT JOIN (SELECT trip_id,
				SUM(IF(expense_type='Toll',amount,0)) AS toll, SUM(expense_type='Toll') AS toll_count,
				SUM(IF(expense_type IN (" . $tax_list . "),amount,0)) AS tax,
				SUM(IF(expense_type='Fuel',amount,0)) AS fuel, SUM(fuel_litres) AS litres, SUM(amount) AS total
			FROM trip_expenses GROUP BY trip_id) E ON E.trip_id=T.trip_id
		WHERE " . $where . "
		GROUP BY " . $group_key . "
		ORDER BY label");

	$columns = array("trips" => "Trips", "completed" => "Completed", "km" => ucfirst(fleet::du()), "stops" => "Stops", "delivered_stops" => "Delivered Stops",
		"sku_lines" => "SKUs", "units_loaded" => "Units Loaded", "units_delivered" => "Units Delivered",
		"toll_count" => "Toll Plazas", "toll" => "Toll", "tax" => "Tax", "fuel" => "Fuel", "litres" => "Fuel (" . fleet::fu() . ")", "other" => "Other Expenses",
		"total" => "Total Cost", "cost_per_km" => "Cost / " . fleet::du(), "km_per_litre" => fleet::du() . " / " . fleet::fu());
	$money = array("toll", "tax", "fuel", "other", "total", "cost_per_km");
	$totals = array_fill_keys(array_keys($columns), 0);
	foreach ($rows as $i => $r) {
		$r['other'] = $r['total'] - $r['toll'] - $r['tax'] - $r['fuel'];
		$r['cost_per_km'] = $r['km'] > 0 ? $r['total'] / $r['km'] : 0;
		$r['km_per_litre'] = $r['litres'] > 0 ? round($r['km'] / $r['litres'], 2) : 0;
		foreach ($columns as $key => $label) {
			if (!in_array($key, array("cost_per_km", "km_per_litre"))) {
				$totals[$key] += $r[$key];
			}
		}
		$rows[$i] = $r;
	}
	$totals['cost_per_km'] = $totals['km'] > 0 ? $totals['total'] / $totals['km'] : 0;
	$totals['km_per_litre'] = $totals['litres'] > 0 ? round($totals['km'] / $totals['litres'], 2) : 0;

	if ($export) {
		header("Content-Type: text/csv");
		header("Content-Disposition: attachment; filename=fleet_report_" . $group_by . "_" . $date_from . "_" . $date_to . ".csv");
		$out = fopen("php://output", "w");
		$header = array($group_title);
		foreach ($columns as $key => $label) {
			$header[] = in_array($key, $money) ? $label . " (" . company_settings::get("currency") . ")" : $label;
		}
		fputcsv($out, $header);
		foreach ($rows as $r) {
			$line = array($r['label']);
			foreach ($columns as $key => $label) {
				$line[] = in_array($key, $money) ? number_format($r[$key], 2, ".", "") : $r[$key] + 0;
			}
			fputcsv($out, $line);
		}
		fclose($out);
		exit;
	}

	$vehicles = fleet::rows("SELECT vehicle_id, registration_no FROM vehicles ORDER BY registration_no");
	$drivers = fleet::rows("SELECT user_id, first_name, last_name FROM users WHERE is_driver=1 ORDER BY first_name, last_name");
	$query_string = http_build_query(array("date_from" => $date_from, "date_to" => $date_to, "group_by" => $group_by, "vehicle_id" => $vehicle_id, "driver_id" => $driver_id));
?>
<div class="container-fluid detail-page">
  <div class="page-header">
    <h4>
      <div class="clearfix">
        <div class="pull-left"><span class="section-heading">Vehicle Cost &amp; Load Report</span></div>
        <div class="pull-right"><small><i class="fa fa-download"></i>&nbsp;<a href="fleet_report.php?export=1&amp;<?= fleet::h($query_string) ?>">Export CSV</a>
          &nbsp;&nbsp;<i class="fa fa-road"></i>&nbsp;<a href="trips.php">Trips</a></small></div>
      </div>
    </h4>
  </div>
  <form class="form-inline" method="get" style="margin-bottom:10px">
    <div class="form-group"><label>From</label> <input type="date" name="date_from" class="form-control" value="<?= fleet::h($date_from) ?>" /></div>
    <div class="form-group"><label>To</label> <input type="date" name="date_to" class="form-control" value="<?= fleet::h($date_to) ?>" /></div>
    <div class="form-group"><label>Group by</label>
      <select name="group_by" class="form-control">
        <option value="vehicle" <?= $group_by == "vehicle" ? "selected" : "" ?>>Vehicle</option>
        <option value="driver" <?= $group_by == "driver" ? "selected" : "" ?>>Driver</option>
        <option value="trip" <?= $group_by == "trip" ? "selected" : "" ?>>Trip</option>
      </select></div>
    <div class="form-group"><select name="vehicle_id" class="form-control"><option value="">All vehicles</option>
      <?php foreach ($vehicles as $v) { ?><option value="<?= (int) $v['vehicle_id'] ?>" <?= $vehicle_id == $v['vehicle_id'] ? "selected" : "" ?>><?= fleet::h($v['registration_no']) ?></option><?php } ?></select></div>
    <div class="form-group"><select name="driver_id" class="form-control"><option value="">All drivers</option>
      <?php foreach ($drivers as $d) { ?><option value="<?= (int) $d['user_id'] ?>" <?= $driver_id == $d['user_id'] ? "selected" : "" ?>><?= fleet::h(fleet::driver_name($d)) ?></option><?php } ?></select></div>
    <button type="submit" class="btn btn-default">Show</button>
  </form>
  <p class="text-muted">Trips dated <?= fleet::h($date_from) ?> to <?= fleet::h($date_to) ?>, cancelled trips excluded. Distance counts completed trips only. Amounts in <?= fleet::h(company_settings::get("currency")) ?>. Tax = <?= fleet::h(implode(", ", fleet::$TAX_EXPENSE_TYPES)) ?>.</p>
  <div style="overflow-x:auto">
  <table class="table table-hover table-condensed">
    <thead><tr><th><?= fleet::h($group_title) ?></th><?php foreach ($columns as $label) { ?><th class="num"><?= fleet::h($label) ?></th><?php } ?></tr></thead>
    <tbody>
    <?php if (count($rows) == 0) { ?><tr><td colspan="100%" align="center" style="height:40px">No trips in this period</td></tr><?php } ?>
    <?php foreach ($rows as $r) { ?>
      <tr><td><?= fleet::h($r['label']) ?></td>
      <?php foreach ($columns as $key => $label) { ?><td class="num"><?= in_array($key, $money) ? fleet::money($r[$key]) : fleet::h($r[$key] + 0) ?></td><?php } ?></tr>
    <?php } ?>
    </tbody>
    <?php if (count($rows) > 1) { ?>
    <tfoot><tr><th>Total</th><?php foreach ($columns as $key => $label) { ?><th class="num"><?= in_array($key, $money) ? fleet::money($totals[$key]) : fleet::h($totals[$key] + 0) ?></th><?php } ?></tr></tfoot>
    <?php } ?>
  </table>
  </div>
</div>
<?php
	include ROOTPATH . '/admin/templates/admin_footer_start.php';
	include ROOTPATH . '/admin/templates/admin_footer_end.php';
?>
