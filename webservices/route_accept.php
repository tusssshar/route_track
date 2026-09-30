<?php
include_once(__DIR__ . '/request_filter.php');


$app_key = $_REQUEST['app_key'];
include_once("../bootstrap.php");
include_once('message.php');

$driver_id = $_REQUEST['driver_id'];
$route_id = $_REQUEST['route_id'];
$current_date = date('Y-m-d');
$abendon_status = $_REQUEST['abendon_status'];
$CURDATE = date("Y-m-d");

if (isset($driver_id) && isset($route_id) && $route_id > 0 && $driver_id > 0) {

    if ($abendon_status == "YES" && $abendon_status != "") {
        $db_route_abendon = new database();
        $db_route_abendon->data("status", 'NO');
        $db_route_abendon->where("driver_id = " . $driver_id . " AND route_id = " . $route_id . " AND route_accept_date='".$CURDATE."'");
        $db_route_abendon->update("route_accept");

        $json = array($RESPONSE_KEY => $RESPONSE_SUCCESS, 'msg' => 'Route abandon successfully.', 'last_id' => '0');
        echo json_encode($json);
    } else {


        $db_check = new database();
        $db_check->where("driver_id = " . $driver_id . " AND route_id = " . $route_id . " AND route_accept_date='".$CURDATE."'");
        $db_check->selectstmt("route_accept", "route_accept_id");
        if ($db_check->row_count > 0) {
            $route_accept_id = $db_check->f_route_accept_id;
            $db = new database();
            $db->data("route_accept_date", $current_date);
            $db->data("status", 'YES');
            $db->where("route_accept_id=" . $route_accept_id);
            $db->update("route_accept");
        } else {
            $db = new database();
            $db->data("driver_id", $driver_id);
            $db->data("route_id", $route_id);
            $db->data("route_accept_date", $current_date);
            $db->data("status", 'YES');
            $db->insert("route_accept");
        }
        $json = array($RESPONSE_KEY => $RESPONSE_SUCCESS, 'msg' => 'Route accepted successfully.', 'last_id' => '0');
        echo json_encode($json);
    }
} else {
    $json = array($RESPONSE_KEY => $RESPONSE_ERROR, 'msg' => 'Please pass all parameter', 'last_id' => '0');
    echo json_encode($json);
}










