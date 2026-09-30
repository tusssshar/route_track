<?php

include_once("../bootstrap.php");
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $api_key = trim($_POST['api_key']);
    $area_id = trim($_POST['area_id']);
    $success;
    $error;
    if ($_POST == "") {
        $json = array("status" => 0, "msg" => "Error!! please post data properly.");
    } else {
        /* check whether apikey exists for any user if yes then fetch userid else show error */
        $user_api_db = new database(); //For API
        $check_user_api = "SELECT user_id,api_key,status FROM users WHERE api_key='" . sanitize($api_key) . "'";
        $user_api_db->myCustomQuery($check_user_api);
        $status = $user_api_db->f_status;
        $user_id = $user_api_db->f_user_id;
        if (empty($api_key)) {
            //check if api key empty or not
            $json = array("status" => 0, "msg" => "Error!! please provide API Key.");
            $error = 1;
        } else if ($user_api_db->row_count < 1) {
            //check if api key is valid or not
            $json = array("status" => 0, "msg" => "Error!! please provide valid API Key, $api_key is invalid.");
            $error = 1;
        } else if ($user_api_db->row_count >= 1 && $status == 0) {
            //check if API Key found but user is not active i.e status=1
            $json = array("status" => 0, "msg" => "Error!! user account is not active yet.");
            $error = 1;
        } else if ($area_id == "") {
            //check if $area_id is not empty
            $json = array("status" => 0, "msg" => "Error!! please provide area id.");
            $error = 1;
        } else if (!is_numeric($area_id)) {
            //check if area id value is non numeric
            $json = array("status" => 0, "msg" => "Error!! please provide valid area id,it should be numeric and greater than equals to 1.");
            $error = 1;
        } else if ($area_id <= 0) {
            //check if area id is non zero or greater than 1
            $json = array("status" => 0, "msg" => "Error!! please provide valid area id,it should be greater than equals to 1.");
            $error = 1;
        } else if ($error == 0) {
            $area_db = new database(); //AREA TABLE
            /* if area id not exist then show error
              else delete delivery area */
            $check_area_id = "SELECT name,area_id FROM areas WHERE area_id='" . sanitize($area_id) . "'";
            $area_db->myCustomQuery($check_area_id);
            if ($area_db->row_count >= 1) {
                $area_delete_db = new database();
                $area_delete_db->where("area_id=" . $area_id);
                $area_delete = $area_delete_db->delete("areas");
                if ($area_delete) {
                    $json = array("status" => 0, "msg" => "Error!! error in deleting area.");
                    $error = 1;
                } else {
                    /*if area deleted then delete routes of that particular area*/
                    $route_delete_db = new database();
                    $route_delete_db->where("area_id=" . $area_id);
                    $route_delete_db->delete("routes");

                    $json = array("status" => 1, "msg" => "Success!! area deleted successfully.");
                    $error = 0;
                }
            } else {
                $json = array("status" => 0, "msg" => "Error!! area id not exists.");
                $error = 1;
            }
        }
    }
} else {
    $json = array("status" => 0, "msg" => "Error!! request method not accepted.");
}//end of if


/* Output header */
header('Content-type: application/json');
echo json_encode($json);
?>