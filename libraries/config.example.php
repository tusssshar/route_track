<?php
	// Copy this file to libraries/config.local.php (never committed) and fill in the real values.
	// When config.local.php exists, libraries/config.php loads it instead of its built-in defaults.

	$envSetting = array(
		'HST'				=>	'localhost',
		'USR'				=>	'root',
		'PWD'				=>	'',
		'DBN'				=>	'RouteTrac_template_repository',
		'DB_PREFIX'			=>	'',
		'DBTYPE'			=>	'mysql',
		'SITEROOT'			=>	'http://'.$_SERVER['HTTP_HOST']."/projects/routetrac",
		'SITEROOT_HTTPS'	=>	'https://'.$_SERVER['HTTP_HOST']."/projects/routetrac",
		'ROOTPATH'			=>	$_SERVER['DOCUMENT_ROOT']."/projects/routetrac",

		'HST_1'				=>	'localhost',
		'USR_1'				=>	'root',
		'PWD_1'				=>	'',
		'DBN_1'				=>	'routetra_RouteTrac_master',
	);

	foreach ($envSetting as $key => $value)
	{
		define($key, $value);
	}

	// Google Maps Platform (project needs billing enabled) and Firebase Cloud Messaging
	define('GOOGLE_MAPS_API_KEY', '');	// Geocoding API (server side, manifest addresses)
	define('GOOGLE_MAPS_JS_KEY', '');	// Maps JavaScript API (live driver map)
	define('FCM_SERVER_KEY', '');		// push notifications to the driver app
