<?php
	include_once("../../../../bootstrap.php");
	fleet::require_access();

	$delete_id = (int) (isset($_REQUEST['delete_id']) ? $_REQUEST['delete_id'] : 0);
	if ($delete_id > 0) {
		// A vehicle with trip history is kept for the records - mark it Inactive instead
		$used = fleet::row("SELECT COUNT(*) AS trips FROM trips WHERE vehicle_id=" . $delete_id);
		if ($used['trips'] > 0) {
			echo json_encode(array("success" => 0, "error" => "This vehicle has " . $used['trips'] . " trip(s) on record. Set its status to Inactive instead of deleting it."));
		} else {
			fleet::exec("DELETE FROM vehicles WHERE vehicle_id=" . $delete_id);
			echo json_encode(array("success" => 1, "msg" => "Vehicle deleted"));
		}
		exit;
	}

	$txtSearch = trim(isset($_REQUEST['txtSearch']) ? (string) $_REQUEST['txtSearch'] : '');
	$status = trim(isset($_REQUEST['status']) ? (string) $_REQUEST['status'] : '');

	$PAGE = max(1, (int) (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1));
	$PAGE_LIMIT = 25;
	$PAGE_LIMIT_VALUE = ($PAGE - 1) * $PAGE_LIMIT;

	$where = "1=1";
	if ($txtSearch != "") {
		$s = sanitize($txtSearch);
		$where .= " AND (V.registration_no LIKE '%" . $s . "%' OR V.make_model LIKE '%" . $s . "%' OR V.fastag_id LIKE '%" . $s . "%' OR V.vehicle_type LIKE '%" . $s . "%')";
	}
	if (in_array($status, fleet::$VEHICLE_STATUSES)) {
		$where .= " AND V.status='" . sanitize($status) . "'";
	}

	$vehicles = fleet::rows("SELECT SQL_CALC_FOUND_ROWS V.* FROM vehicles V WHERE " . $where . "
		ORDER BY V.registration_no LIMIT " . $PAGE_LIMIT_VALUE . ", " . $PAGE_LIMIT);
	$total = fleet::row("SELECT FOUND_ROWS() AS c");
	$PAGING = fleet::pager($PAGE, $total['c'], $PAGE_LIMIT);
?>
<div class="row">
  <div class="col-md-12" style="padding:0 20px">
    <div class="pull-right"><?= $PAGING ?></div>
  </div>
</div>
<div class="row">
  <div class="col-md-12">
    <table class="table table-hover">
      <thead>
        <tr>
          <th class="text-center">#</th>
          <th>Registration</th>
          <th>Type / Model</th>
          <th class="num">Capacity</th>
          <th class="num">Odometer</th>
          <th>Status</th>
          <th>Current Trip</th>
          <th class="num">Units On Board</th>
          <th>Documents</th>
          <th class="text-center">Action</th>
        </tr>
      </thead>
      <tbody>
<?php
	if (count($vehicles) == 0) {
		echo "<tr><td align='center' colspan='100%' style='height:40px;'>No vehicle found</td></tr>";
	}
	$counter = $PAGE_LIMIT_VALUE + 1;
	foreach ($vehicles as $v) {
		$trip = fleet::current_trip_for_vehicle($v['vehicle_id']);
		$trip_info = "-";
		$on_board = "-";
		if ($trip) {
			$trip = fleet::trip($trip['trip_id']);
			$trip_info = "<a href='trip_detail.php?trip_id=" . (int) $trip['trip_id'] . "'>" . fleet::h($trip['trip_number']) . "</a>"
				. " <span class='fleet-badge fleet-" . str_replace(" ", "-", $trip['status']) . "'>" . fleet::h($trip['status']) . "</span><br>"
				. "<small>" . fleet::h(fleet::driver_name($trip)) . " &middot; " . fleet::h($trip['route_name']) . " &middot; " . fleet::h($trip['trip_date']) . "</small>";
			$totals = fleet::load_totals($trip['trip_id']);
			$on_board = $totals['on_board_units'] . " <small>(" . $totals['sku_count'] . " SKU)</small>";
		}
		$warnings = fleet::document_warnings($v);
?>
        <tr>
          <td class="text-center"><?= $counter++ ?></td>
          <td><strong><?= fleet::h($v['registration_no']) ?></strong><?= $v['fastag_id'] != "" ? "<br><small>FASTag " . fleet::h($v['fastag_id']) . "</small>" : "" ?></td>
          <td><?= fleet::h($v['vehicle_type']) ?><br><small><?= fleet::h($v['make_model']) ?> &middot; <?= fleet::h($v['fuel_type']) ?></small></td>
          <td class="num"><?= $v['capacity_units'] > 0 ? (int) $v['capacity_units'] . " units" : "-" ?><?= $v['capacity_kg'] > 0 ? "<br><small>" . fleet::h($v['capacity_kg'] + 0) . " kg</small>" : "" ?></td>
          <td class="num"><?= number_format($v['odometer_km']) ?> <?= fleet::du() ?></td>
          <td><span class="fleet-badge fleet-<?= str_replace(" ", "-", $v['status']) ?>"><?= fleet::h($v['status']) ?></span></td>
          <td><?= $trip_info ?></td>
          <td class="num"><?= $on_board ?></td>
          <td><?= count($warnings) == 0 ? "<span style='color:#3c763d'><i class='fa fa-check'></i> Valid</span>" : "<span class='fleet-warn'>" . implode("<br>", array_map(array('fleet', 'h'), $warnings)) . "</span>" ?></td>
          <td class="text-center" style="white-space:nowrap">
            <a href="javascript:void(0);" onclick="javascript:ajaxNewModify(<?= (int) $v['vehicle_id'] ?>);" title="Edit Vehicle"><i class="fa fa-pencil-square-o fa-size"></i></a> &nbsp;
            <a href="trips.php?vehicle_id=<?= (int) $v['vehicle_id'] ?>" title="Trips of this vehicle"><i class="fa fa-road fa-size"></i></a> &nbsp;
            <a href="javascript:void(0);" onclick="javascript:deleteVehicle(<?= (int) $v['vehicle_id'] ?>);" title="Delete Vehicle"><i class="fa fa-trash-o fa-size"></i></a>
          </td>
        </tr>
<?php } ?>
      </tbody>
    </table>
  </div>
</div>
