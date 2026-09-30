<?php
include_once(__DIR__ . '/request_filter.php');


$app_key = $_REQUEST['app_key'];

include_once("../bootstrap.php");
include_once('message.php');

$driver_id = $_REQUEST['driver_id'];
$latitude = $_REQUEST['latitude'];
$longitude = $_REQUEST['longitude'];



$result = array();
$data = array();
if ($driver_id != "" && $latitude != "" && $longitude != "") {

    //ADD CURRENT LOCATION
    $db = new database();
    $db->data("driver_id ", $driver_id);
    $db->data("latitude", $latitude);
    $db->data("longitude", $longitude);
    $db->insert("driver_location");

    //UPDATE CURRENT LOCATION
    $db_update_current_location = new database();
    $db_update_current_location->data("latitude", $latitude);
    $db_update_current_location->data("longitude", $longitude);
    $db_update_current_location->where("user_id=" . $driver_id);
    $db_update_current_location->update("users");



    $json = array($RESPONSE_KEY => $RESPONSE_SUCCESS, 'msg' => 'Success', 'info' => $result);
    echo json_encode($json);
} else {
    $json = array($RESPONSE_KEY => $RESPONSE_ERROR, 'msg' => 'Please pass all parameter', 'last_id' => '0');
    echo json_encode($json);
}
?>


