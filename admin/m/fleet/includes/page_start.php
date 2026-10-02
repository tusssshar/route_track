<?php
	// Common top of every Fleet page: session, header/menu, Fleet access check, back to the repository DB
	include_once(__DIR__ . "/../../../../bootstrap.php");
	include(ROOTPATH . "/admin/authorized.php");

	include_once(ROOTPATH . "/admin/templates/admin_header_start.php");
	include_once(ROOTPATH . "/admin/templates/admin_header_end.php");

	//Check Access Level (header left the connection on the master DB)
	$res = $functions->user_access_level(fleet::FLEET_MENU_ID, $_SESSION["routetrac-admin"]["access_level"]);
	if ($res == 0) {
		echo "<script>window.location.assign('" . SITEROOT . "/admin/home.php')</script>";
		exit;
	}
	$objGlobalConnection1 = new dbSqlConnection();
	$oGlobalConnection = $objGlobalConnection1->Connect(HST, USR, PWD, DBN);
?>
<style>
	.fleet-badge { display:inline-block; padding:2px 8px; border-radius:10px; font-size:12px; color:#fff; white-space:nowrap; }
	.fleet-Available, .fleet-Completed { background:#5cb85c; }
	.fleet-Planned { background:#777; }
	.fleet-Loaded { background:#5bc0de; }
	.fleet-In-Transit, .fleet-On-Trip { background:#337ab7; }
	.fleet-Maintenance { background:#f0ad4e; }
	.fleet-Inactive, .fleet-Cancelled { background:#d9534f; }
	.fleet-warn { color:#c9302c; font-size:12px; }
	.fleet-kpi { border:1px solid #ddd; border-radius:4px; padding:8px 12px; margin-bottom:10px; background:#fafafa; }
	.fleet-kpi .v { font-size:20px; font-weight:bold; }
	.fleet-kpi .l { font-size:12px; color:#777; }
	.table td.num, .table th.num { text-align:right; }
</style>
