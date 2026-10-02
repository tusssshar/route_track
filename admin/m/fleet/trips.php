<?php
	include_once(__DIR__ . "/includes/page_start.php");

	$filter_vehicle_id = (int) (isset($_GET['vehicle_id']) ? $_GET['vehicle_id'] : 0);
	$filter_driver_id = (int) (isset($_GET['driver_id']) ? $_GET['driver_id'] : 0);
	$vehicles = fleet::rows("SELECT vehicle_id, registration_no FROM vehicles ORDER BY registration_no");
	$drivers = fleet::rows("SELECT user_id, first_name, last_name FROM users WHERE is_driver=1 ORDER BY first_name, last_name");
	$routes = fleet::rows("SELECT route_id, name FROM routes ORDER BY name");

	// KPI strip for today
	$today = date("Y-m-d");
	// a trip that left yesterday evening and is still on the road counts as running today
	$kpi = fleet::row("SELECT COUNT(*) AS trips, SUM(status='In Transit') AS running, SUM(status IN ('Planned','Loaded')) AS waiting, SUM(status='Completed') AS done
		FROM trips WHERE (trip_date='" . $today . "' AND status<>'Cancelled') OR status='In Transit'");
	$kpi_vehicles = fleet::row("SELECT SUM(status='Available') AS available, SUM(status='On Trip') AS on_trip, SUM(status='Maintenance') AS maintenance FROM vehicles");
	$kpi_money = fleet::row("SELECT COALESCE(SUM(IF(E.expense_type='Toll',E.amount,0)),0) AS toll,
			COALESCE(SUM(IF(E.expense_type IN (" . fleet::in_list(fleet::$TAX_EXPENSE_TYPES) . "),E.amount,0)),0) AS tax,
			COALESCE(SUM(E.amount),0) AS total
		FROM trip_expenses E WHERE DATE(E.expense_time)='" . $today . "'");
	$unplanned = fleet::row("SELECT COUNT(DISTINCT D.route_id) AS routes, COUNT(*) AS orders FROM deliveries D LEFT JOIN trips T ON T.trip_id=D.trip_id
		WHERE D.scheduled_delivery='" . $today . "' AND (D.trip_id=0 OR T.trip_id IS NULL OR T.status='Cancelled')
		AND D.status NOT IN (" . fleet::in_list(fleet::$CLOSED_ORDER_STATUSES) . ")");
?>
<div class="container-fluid detail-page">
  <div class="page-header">
    <h4>
      <div class="clearfix">
        <div class="pull-left"> <a href="trips.php"><span class="section-heading">Trips &amp; Dispatch</span></a> </div>
        <div class="pull-right"> <small> <a href="javascript:void(0);" onclick="javascript:ajaxNewModify(0);" title="Plan a new trip"><i class="fa fa-plus"></i>&nbsp;&nbsp;New Trip</a>
          &nbsp;&nbsp;<i class="fa fa-truck"></i>&nbsp;&nbsp;<a href="vehicles.php">Vehicles</a>
          &nbsp;&nbsp;<i class="fa fa-bar-chart"></i>&nbsp;&nbsp;<a href="fleet_report.php">Report</a></small> </div>
      </div>
    </h4>
  </div>

  <div class="row">
    <div class="col-md-2 col-sm-4"><div class="fleet-kpi"><div class="v"><?= (int) $kpi['trips'] ?></div><div class="l">Trips today (<?= (int) $kpi['running'] ?> running, <?= (int) $kpi['waiting'] ?> waiting, <?= (int) $kpi['done'] ?> done)</div></div></div>
    <div class="col-md-2 col-sm-4"><div class="fleet-kpi"><div class="v"><?= (int) $kpi_vehicles['available'] ?> / <?= (int) $kpi_vehicles['on_trip'] ?></div><div class="l">Vehicles available / on trip (<?= (int) $kpi_vehicles['maintenance'] ?> in maintenance)</div></div></div>
    <div class="col-md-2 col-sm-4"><div class="fleet-kpi"><div class="v" <?= $unplanned['orders'] > 0 ? "style='color:#c9302c'" : "" ?>><?= (int) $unplanned['orders'] ?></div><div class="l">Open orders today without a trip (<?= (int) $unplanned['routes'] ?> routes)</div></div></div>
    <div class="col-md-2 col-sm-4"><div class="fleet-kpi"><div class="v"><?= fleet::money($kpi_money['toll']) ?></div><div class="l">Toll paid today</div></div></div>
    <div class="col-md-2 col-sm-4"><div class="fleet-kpi"><div class="v"><?= fleet::money($kpi_money['tax']) ?></div><div class="l">Taxes paid today</div></div></div>
    <div class="col-md-2 col-sm-4"><div class="fleet-kpi"><div class="v"><?= fleet::money($kpi_money['total']) ?></div><div class="l">All trip expenses today</div></div></div>
  </div>

  <p class="text-muted small"><i class="fa fa-clock-o"></i> Times in <?= fleet::h(company_settings::tz_label()) ?> &middot; amounts in <?= fleet::h(company_settings::get("currency")) ?> &middot; distance in <?= fleet::h(fleet::du()) ?></p>
  <div class="alert alert-success" id="successMsg" style="margin:10px 0;display:none"></div>
  <div class="row">
    <div class="col-md-12" id="divList">
      <form class="form-inline" role="form" autocomplete="off" method="post" name="form" id="form" style="margin-bottom:10px">
        <div class="form-group"><input type="text" name="txtSearch" id="txtSearch" class="form-control" placeholder="Trip no." style="width:130px" /></div>
        <div class="form-group"><input type="date" name="date_from" id="date_from" class="form-control" title="From date" /></div>
        <div class="form-group"><input type="date" name="date_to" id="date_to" class="form-control" title="To date" /></div>
        <div class="form-group"><select name="status" id="status" class="form-control"><option value="">Active trips</option><option value="all">All statuses</option><?= fleet::options(fleet::$TRIP_STATUSES) ?></select></div>
        <div class="form-group"><select name="vehicle_id" id="vehicle_id" class="form-control"><option value="">All vehicles</option>
          <?php foreach ($vehicles as $v) { ?><option value="<?= (int) $v['vehicle_id'] ?>" <?= $filter_vehicle_id == $v['vehicle_id'] ? "selected" : "" ?>><?= fleet::h($v['registration_no']) ?></option><?php } ?></select></div>
        <div class="form-group"><select name="driver_id" id="driver_id" class="form-control"><option value="">All drivers</option>
          <?php foreach ($drivers as $d) { ?><option value="<?= (int) $d['user_id'] ?>" <?= $filter_driver_id == $d['user_id'] ? "selected" : "" ?>><?= fleet::h(fleet::driver_name($d)) ?></option><?php } ?></select></div>
        <div class="form-group"><select name="route_id" id="route_id" class="form-control"><option value="">All routes</option>
          <?php foreach ($routes as $r) { ?><option value="<?= (int) $r['route_id'] ?>"><?= fleet::h($r['name']) ?></option><?php } ?></select></div>
        <input name="search" type="submit" id="search" value="  Search " class="btn btn-default" />
        <div class="form-group"> <a href="javascript:void(0);" onclick="javascript:clearSearchValues();">Clear Search</a> </div>
      </form>
      <div id="MyAjaxInterView">&nbsp;</div>
    </div>
    <div class="col-md-0" id="divForm" style="display:none"></div>
  </div>
</div>
<?php include ROOTPATH . '/admin/templates/admin_footer_start.php'; ?>
<script type="text/javascript" src="js/trips.js?v=<?= filemtime(__DIR__ . "/js/trips.js") ?>"></script>
<?php include ROOTPATH . '/admin/templates/admin_footer_end.php'; ?>
