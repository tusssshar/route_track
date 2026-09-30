<?php
	// Real credentials live in config.local.php (not committed) - copy config.example.php to config.local.php and fill it in.
	if (file_exists(__DIR__ . '/config.local.php')) {
		include __DIR__ . '/config.local.php';
	} else {

$server = $_SERVER['SERVER_NAME'];

	if( $_SERVER['SERVER_NAME'] == 'routetrac' || $_SERVER['SERVER_NAME'] == 'localhost'  || $_SERVER['SERVER_NAME'] == '192.168.0.206' ) {
		$envSetting = array(
			'HST'				=>	'localhost',
			'USR'				=>	'root',
			'PWD'				=>	'',
			'DBN'				=>	"RouteTrac_template_repository",
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
	} else {
		$envSetting = array(
			'HST'				=>	'localhost',
			'USR'				=>	'routetra_spark',
			'PWD'				=>	'',
			'DBN'				=>	'routetra_RouteTrac_template_repository',
			'DB_PREFIX'			=>	'',
			'DBTYPE'			=>	'mysql',
			'SITEROOT'			=>	'http://'.$_SERVER['HTTP_HOST']."/routetrac",
			'SITEROOT_HTTPS'	=>	'https://'.$_SERVER['HTTP_HOST']."/routetrac",
			'ROOTPATH'			=>	$_SERVER['DOCUMENT_ROOT']."/routetrac",

			'HST_1'				=>	'localhost',
			'USR_1'				=>	'routetra_spark',
			'PWD_1'				=>	'',
			'DBN_1'				=>	'routetra_RouteTrac_master',

		);		
	}
	
	foreach ($envSetting as $key => $value)
	{
		//$_ENV[$key] = $value;
		//$_SERVER[$key] = $value;
		//putenv("{$key}={$value}");
		define($key, $value);
	}

	}

	// Google Geocoding / Maps JavaScript keys and the FCM server key (define them in config.local.php or the environment)
	if (!defined('GOOGLE_MAPS_API_KEY'))	define('GOOGLE_MAPS_API_KEY', (string) getenv('GOOGLE_MAPS_API_KEY'));
	if (!defined('GOOGLE_MAPS_JS_KEY'))	define('GOOGLE_MAPS_JS_KEY', (string) getenv('GOOGLE_MAPS_JS_KEY'));
	if (!defined('FCM_SERVER_KEY'))		define('FCM_SERVER_KEY', (string) getenv('FCM_SERVER_KEY'));
