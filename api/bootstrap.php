<?php
	@session_start();
//	header ("Expires: ".gmdate("D, d M Y H:i:s", time())." GMT");  
//	header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  
//	header ("Cache-Control: no-cache, must-revalidate");  
//	header ("Pragma: no-cache");
	
	error_reporting(0);

	date_default_timezone_set('Asia/Kolkata');

	include_once("libraries/config.php");
	include_once("libraries/dbSqliConnection.php");
	include_once("libraries/define.php");
	include_once("libraries/classes/pagination.php");
	include_once("libraries/classes/functions.php");
	$functions = new functions();
	
	include_once("libraries/classes/class.phpmailer.php");
	//include_once("libraries/classes/mail.php");
	
?>