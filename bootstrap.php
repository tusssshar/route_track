<?php
	@session_start();
//	header ("Expires: ".gmdate("D, d M Y H:i:s", time())." GMT");  
//	header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  
//	header ("Cache-Control: no-cache, must-revalidate");  
//	header ("Pragma: no-cache");
	
	error_reporting(0);
	// Opt-in debug logging: create an empty file named ".debug" next to this file
	if (file_exists(__DIR__ . '/.debug')) {
		error_reporting(E_ALL & ~E_DEPRECATED);
		ini_set('display_errors', '0');
		ini_set('log_errors', '1');
		ini_set('error_log', __DIR__ . '/logs/php_errors.log');
	}

	//date_default_timezone_set('Asia/Kolkata');
	date_default_timezone_set('America/Chicago');

	include_once("libraries/config.php");
	include_once("libraries/dbSqliConnection.php");
	include_once("libraries/define.php");
	include_once("libraries/classes/pagination.php");
	include_once("libraries/classes/functions.php");
	$functions = new functions();
	
	// Every script under /admin/ requires a logged-in admin session
	$scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME']);
	if (strpos($scriptPath, '/admin/') !== false && basename($scriptPath) != 'logout.php'
			&& !isset($_SESSION["routetrac-admin"]["al_id"])) {
		if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
			header('HTTP/1.1 401 Unauthorized');
			echo "<script>location.href='" . SITEROOT . "/index.php';</script>";
		} else {
			header('Location: ' . SITEROOT . '/index.php');
		}
		exit;
	}

	// Admin ids are concatenated into SQL (often unquoted): keep only digits / comma lists
	if (strpos($scriptPath, '/admin/') !== false) {
		$admin_id_params = array('area_id', 'package_type_id', 'cust_id', 'customer_id', 'address_id', 'user_id', 'level_id',
			'manifest_id', 'delivery_id', 'delete_id', 'temp_delivery_id', 'hidden_delivery_id', 'hidden_manifest_id',
			'driver_id', 'item_id', 'packages_id', 'package_id', 'default_route_id', 'access_level');
		if (strpos($scriptPath, '/m/reports/') === false) {
			$admin_id_params[] = 'route_id';	// the reports post the route *name* in route_id
		}
		foreach ($admin_id_params as $id_param) {
			foreach (array('_REQUEST', '_POST', '_GET') as $req_var) {
				if (!isset($GLOBALS[$req_var][$id_param])) {
					continue;
				}
				if (is_array($GLOBALS[$req_var][$id_param])) {
					foreach ($GLOBALS[$req_var][$id_param] as $k => $v) {
						$GLOBALS[$req_var][$id_param][$k] = preg_replace('/[^0-9]/', '', (string) $v);
					}
				} else {
					$GLOBALS[$req_var][$id_param] = preg_replace('/[^0-9,]/', '', (string) $GLOBALS[$req_var][$id_param]);
				}
			}
		}
	}

	include_once("libraries/classes/class.phpmailer.php");
	//include_once("libraries/classes/mail.php");
	
?>