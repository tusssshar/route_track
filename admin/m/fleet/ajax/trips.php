<?php
	include_once("../../../../bootstrap.php");
	fleet::require_access();

	$txtSearch = trim(isset($_REQUEST['txtSearch']) ? (string) $_REQUEST['txtSearch'] : '');
	$status = trim(isset($_REQUEST['status']) ? (string) $_REQUEST['status'] : '');
	$date_from = trim(isset($_REQUEST['date_from']) ? (string) $_REQUEST['date_from'] : '');
	$date_to = trim(isset($_REQUEST['date_to']) ? (string) $_REQUEST['date_to'] : '');

	$PAGE = max(1, (int) (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1));
	$PAGE_LIMIT = 25;
	$PAGE_LIMIT_VALUE = ($PAGE - 1) * $PAGE_LIMIT;

	$where = "1=1";
	if ($txtSearch != "") {
		$where .= " AND T.trip_number LIKE '%" . sanitize($txtSearch) . "%'";
	}
	if ($status == "") {
		$where .= " AND T.status IN (" . fleet::in_list(fleet::$ACTIVE_TRIP_STATUSES) . ")";
	} elseif (in_array($status, fleet::$TRIP_STATUSES)) {
		$where .= " AND T.status='" . sanitize($status) . "'";
	}
	if (fleet::valid_date($date_from)) {
		$where .= " AND T.trip_date>='" . $date_from . "'";
	}
	if (fleet::valid_date($date_to)) {
		$where .= " AND T.trip_date<='" . $date_to . "'";
	}
	foreach (array("vehicle_id", "driver_id", "route_id") as $field) {
		if (!empty($_REQUEST[$field])) {
			$where .= " AND T." . $field . "=" . (int) $_REQUEST[$field];
		}
	}

	$trips = fleet::rows("SELECT SQL_CALC_FOUND_ROWS T.*, R.name AS route_name, V.registration_no, U.first_name, U.last_name,
			(SELECT COUNT(*) FROM deliveries D WHERE D.trip_id=T.trip_id) AS stops,
			(SELECT COUNT(*) FROM deliveries D WHERE D.trip_id=T.trip_id AND D.status='Delivered') AS delivered,
			(SELECT COALESCE(SUM(E.amount),0) FROM trip_expenses E WHERE E.trip_id=T.trip_id AND E.expense_type='Toll') AS toll,
			(SELECT COALESCE(SUM(E.amount),0) FROM trip_expenses E WHERE E.trip_id=T.trip_id AND E.expense_type IN (" . fleet::in_list(fleet::$TAX_EXPENSE_TYPES) . ")) AS tax,
			(SELECT COALESCE(SUM(E.amount),0) FROM trip_expenses E WHERE E.trip_id=T.trip_id) AS expenses
		FROM trips T
		LEFT JOIN routes R ON R.route_id=T.route_id
		LEFT JOIN vehicles V ON V.vehicle_id=T.vehicle_id
		LEFT JOIN users U ON U.user_id=T.driver_id
		WHERE " . $where . "
		ORDER BY FIELD(T.status,'In Transit','Loaded','Planned','Completed','Cancelled'), T.trip_date DESC, T.trip_id DESC
		LIMIT " . $PAGE_LIMIT_VALUE . ", " . $PAGE_LIMIT);
	$total = fleet::row("SELECT FOUND_ROWS() AS c");
?>
<div class="clearfix"><div class="pull-right"><?= fleet::pager($PAGE, $total['c'], $PAGE_LIMIT) ?></div></div>
<table class="table table-hover">
  <thead>
    <tr>
      <th>Trip</th>
      <th>Date</th>
      <th>Route</th>
      <th>Vehicle</th>
      <th>Driver</th>
      <th>Status</th>
      <th class="num">Stops</th>
      <th class="num">Units Loaded / On Board</th>
      <th class="num"><?= ucfirst(fleet::du()) ?></th>
      <th class="num">Toll</th>
      <th class="num">Tax</th>
      <th class="num">All Expenses</th>
      <th class="text-center">Action</th>
    </tr>
  </thead>
  <tbody>
<?php
	if (count($trips) == 0) {
		echo "<tr><td align='center' colspan='100%' style='height:40px;'>No trip found</td></tr>";
	}
	foreach ($trips as $t) {
		$load = fleet::load_totals($t['trip_id']);
		$km = ($t['end_odometer'] !== null && $t['start_odometer'] !== null) ? number_format($t['end_odometer'] - $t['start_odometer']) : "-";
?>
    <tr>
      <td><a href="trip_detail.php?trip_id=<?= (int) $t['trip_id'] ?>"><strong><?= fleet::h($t['trip_number']) ?></strong></a></td>
      <td><?= fleet::h($t['trip_date']) ?></td>
      <td><?= fleet::h($t['route_name']) ?></td>
      <td><?= fleet::h($t['registration_no']) ?></td>
      <td><?= fleet::h(fleet::driver_name($t)) ?><?= $t['helper_name'] != "" ? "<br><small>+ " . fleet::h($t['helper_name']) . "</small>" : "" ?></td>
      <td><span class="fleet-badge fleet-<?= str_replace(" ", "-", $t['status']) ?>"><?= fleet::h($t['status']) ?></span></td>
      <td class="num"><?= (int) $t['delivered'] ?> / <?= (int) $t['stops'] ?></td>
      <td class="num"><?= $load['loaded_units'] ?> / <?= $load['on_board_units'] ?> <small>(<?= $load['sku_count'] ?> SKU)</small></td>
      <td class="num"><?= $km ?></td>
      <td class="num"><?= fleet::money($t['toll']) ?></td>
      <td class="num"><?= fleet::money($t['tax']) ?></td>
      <td class="num"><?= fleet::money($t['expenses']) ?></td>
      <td class="text-center" style="white-space:nowrap">
        <a href="trip_detail.php?trip_id=<?= (int) $t['trip_id'] ?>" title="Open trip: load, tracking, expenses"><i class="fa fa-eye fa-size"></i></a>
        <?php if ($t['status'] == "Completed") { ?>&nbsp;
        <a href="trip_sheet.php?trip_id=<?= (int) $t['trip_id'] ?>" target="_blank" title="Trip sheet (<?= $t['settlement_status'] == "Settled" ? "settled" : "pending settlement" ?>)"><i class="fa fa-file-text-o fa-size" style="color:<?= $t['settlement_status'] == "Settled" ? "#5cb85c" : "#f0ad4e" ?>"></i></a>
        <?php } ?>
        <?php if (in_array($t['status'], fleet::$ACTIVE_TRIP_STATUSES)) { ?>&nbsp;
        <a href="javascript:void(0);" onclick="javascript:ajaxNewModify(<?= (int) $t['trip_id'] ?>);" title="Edit / reassign"><i class="fa fa-pencil-square-o fa-size"></i></a>
        <?php } ?>
      </td>
    </tr>
<?php } ?>
  </tbody>
</table>
