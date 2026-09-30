<?php
echo SITEROOT;
exit();
include_once("../bootstrap.php");
include_once("./libraries/custom_validations.php");

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    // Get data
    $api_key = trim($_POST['api_key']);
    $route_id = trim($_POST['route_id']);
    $route_name = trim($_POST['route_name']);
    $area_name = trim($_POST['area_name']);
    $success;
    $error;
    if ($_POST == "") {
        $json = array("status" => 0, "msg" => "Error!! please post data properly.");
    } else {
        $validate_api = new custom_validations();
        $result = $validate_api->validate_api($api_key);
        if ($result) {
            $json=$result;
        } else {
            $json = array("status" => 1, "msg" => "Success");
        }
//        else if(is_non_numeric($route_id)){
//            $json;
//        } else {
//            $json = array("status" => 1, "msg" => "Success");
//        }
    }
} else {
    $json = array("status" => 0, "msg" => "Request method not accepted");
}

/* Output header */
header('Content-type: application/json');
echo json_encode($json);
?>