<?php
/*
 * Live Fleet Map (menu Map -> Track Drivers).
 * Running vehicles at their last GPS position with today's track and closed stops, signal age per vehicle,
 * other drivers' recent positions, auto refresh; History mode draws one driver's track for a date range.
 * Maps: OpenStreetMap via Leaflet - no Google key or billing needed. Data: ajax/live_positions.php
 */
include_once("../../../bootstrap.php");
include(ROOTPATH . "/admin/authorized.php");
include_once(ROOTPATH . "/admin/templates/admin_header_start.php");
include_once(ROOTPATH . "/admin/templates/admin_header_end.php");

// Same users as before (Areas menu) plus everyone with Fleet access
$res = $functions->user_access_level(1, $_SESSION["routetrac-admin"]["access_level"]);
$res_fleet = $functions->user_access_level(fleet::FLEET_MENU_ID, $_SESSION["routetrac-admin"]["access_level"]);
if ($res == 0 && $res_fleet == 0) {
	echo "<script>window.location.assign('" . SITEROOT . "/admin/home.php')</script>";
	exit;
}
$objGlobalConnection1 = new dbSqlConnection();
$oGlobalConnection = $objGlobalConnection1->Connect(HST, USR, PWD, DBN);
$drivers = fleet::rows("SELECT user_id, first_name, last_name FROM users WHERE is_driver=1 ORDER BY first_name, last_name");
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
	#fleetMap { height: calc(100vh - 190px); min-height: 420px; border: 1px solid #ddd; }
	.vlist { height: calc(100vh - 190px); min-height: 420px; overflow-y: auto; }
	.vcard { border: 1px solid #ddd; border-left: 5px solid #999; border-radius: 4px; padding: 7px 9px; margin-bottom: 7px; cursor: pointer; background: #fff; }
	.vcard:hover { background: #f5f8fc; }
	.vcard.live { border-left-color: #5cb85c; } .vcard.stale { border-left-color: #f0ad4e; } .vcard.lost, .vcard.none { border-left-color: #d9534f; }
	.sig { font-size: 11px; font-weight: bold; }
	.sig.live { color: #3c763d; } .sig.stale { color: #c77700; } .sig.lost, .sig.none { color: #c9302c; }
	.vtruck { background: #337ab7; color: #fff; border-radius: 4px; padding: 2px 5px; font-size: 11px; font-weight: bold; white-space: nowrap; box-shadow: 0 1px 3px rgba(0,0,0,.4); }
	.vtruck.stale { background: #f0ad4e; } .vtruck.lost, .vtruck.none { background: #d9534f; }
	.vdriver { background: #777; color: #fff; border-radius: 10px; padding: 2px 6px; font-size: 11px; white-space: nowrap; }
</style>
<div class="container-fluid detail-page">
  <div class="page-header">
    <h4>
      <div class="clearfix">
        <div class="pull-left"><span class="section-heading">Live Fleet Map</span>
          <small id="updated" class="text-muted"></small></div>
        <div class="pull-right">
          <form class="form-inline" id="historyForm" onsubmit="loadHistory(); return false;">
            <label class="radio-inline"><input type="radio" name="mode" value="live" checked onchange="setMode()"> Live</label>
            <label class="radio-inline"><input type="radio" name="mode" value="history" onchange="setMode()"> History</label>
            <span id="historyFields" style="display:none">
              &nbsp;<select id="h_driver" class="form-control input-sm"><option value="">Driver</option>
                <?php foreach ($drivers as $d) { ?><option value="<?= (int) $d['user_id'] ?>"><?= fleet::h(fleet::driver_name($d)) ?></option><?php } ?></select>
              <input type="date" id="h_from" class="form-control input-sm" value="<?= date("Y-m-d") ?>">
              <input type="date" id="h_to" class="form-control input-sm" value="<?= date("Y-m-d") ?>">
              <button type="submit" class="btn btn-info btn-sm">Show Track</button>
            </span>
          </form>
        </div>
      </div>
    </h4>
  </div>
  <div class="row">
    <div class="col-md-3">
      <div class="vlist" id="vlist"><p class="text-muted">Loading...</p></div>
    </div>
    <div class="col-md-9">
      <div id="fleetMap"></div>
      <p class="small text-muted" style="margin-top:4px">
        <span class="sig live">&#9632;</span> reported in the last 5 min &nbsp; <span class="sig stale">&#9632;</span> 5-30 min &nbsp; <span class="sig lost">&#9632;</span> no signal for over 30 min
        &nbsp;&middot;&nbsp; <span style="color:#999">&#9679;</span> driver not on a trip (hover for name) &nbsp; <span style="color:#5cb85c">&#9679;</span> delivered stop <span style="color:#f0ad4e">&#9679;</span> refused / exception
        &nbsp;&middot;&nbsp; Times in <?= fleet::h(company_settings::tz_label()) ?> &middot; refreshes every 30 s
      </p>
    </div>
  </div>
</div>
<?php include ROOTPATH . '/admin/templates/admin_footer_start.php'; ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script type="text/javascript" src="js/locate_drivers.js?v=<?= filemtime(__DIR__ . "/js/locate_drivers.js") ?>"></script>
<?php include ROOTPATH . '/admin/templates/admin_footer_end.php'; ?>
