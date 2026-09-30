<?php

	//	set global varibales from site_global_variables
	/*
	$db = new sDatabase();
	$db->where("isDeleted = 0 and orgId = ".$_SESSION['USER_LOGIN']['sh_OrdId']."");
	$db->selectstmt("site_global_variables");
	for($i=0;$i<$db->row_count;$i++){
		$db->moveto($i);
		define($db->f_sgvName,$db->f_sgvValue);
	}
	*/
/*
 $db_marital_status=new database();
$query="select dd_id,dd_value from vjgm_dropdown where dd_title='Marital Status' and dd_is_deleted=0 order by dd_id asc";
$db_marital_status->myCustomQuery($query);
$marital_status_array=array();
for($i=0;$i<$db_marital_status->row_count;$i++)
{
    $db_marital_status->moveto($i);
    $marital_status=$db_marital_status->f_dd_value;
    $marital_status_array[$marital_status]=$marital_status;
}
define('MARITAL_STATUS',serialize($marital_status_array));

define('SELF_RATING', serialize (array(
    '0'=>"Basic",
    '1'=>"Know How",
    '2'=>"Above Average",
    '3'=>"Excellent"
)));
 */
define('SELF_RATING', serialize (array(
    '0'=>"Basic",
    '1'=>"Know How",
    '2'=>"Above Average",
    '3'=>"Excellent"
)));

define('MARITAL_STATUS', serialize (array(
    'Single' => "Single",
    'Married' => "Married",
    'Widowed' => "Widowed",
    'Separated' => "Separated",
	'Divorced' => "Divorced"
)));

define('PASSPORT', serialize (array(
    'Required' => "Required",
    'Not Required' => "Not Required"
)));

define('TRAVELLING', serialize (array(
    'Low'=>"Low",
    'High'=>"High",
    'Medium'=>"Medium"
)));


define("VIEW_COMPANY_DETAILS","5");
define("POST_RESUME","4");

?>