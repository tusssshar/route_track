<?php

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
            $json = array("status" => 0, "msg" => "Error!! please provice valid API Key, $api_key is invalid.");
            $error = 1;
        } else if ($user_api_db->row_count >= 1 && $status == 0) {
            //check if API Key found but user is not active i.e status=1
            $json = array("status" => 0, "msg" => "Error!! user account is not active yet.");
            $error = 1;
        } else if (empty($route_name) || empty($area_name) || $route_id == "") {
            //check if $route_name,$area_name,$route_id is not empty
            $json = array("status" => 0, "msg" => "Error!! please provide route id or route name or area name.");
            $error = 1;
        } else if (is_numeric($area_name)) {
            //check if $area_name value is not numeric
            $json = array("status" => 0, "msg" => "Error!! please provide valid area name,it can not be numeric only.");
            $error = 1;
        } else if (!is_numeric($route_id)) {
            //check if $route_id value is non numeric
            $json = array("status" => 0, "msg" => "Error!! please provide valid route id,it should be numeric 0 or greater than equals to 1.");
            $error = 1;
        } else if ($route_id < 0) {
            //check if route id is non zero or greater than 1
            $json = array("status" => 0, "msg" => "Error!! please provide valid route id,it should be 0 or greater than equals to 1.");
            $error = 1;
        } else if ($error == 0) {
            /* check whether area name already exists 
              if yes then area insert id
              else first insert area in area tbl then insert id area id in route tbl */
            $area_insert_db = new database();
            $check_area = "SELECT name,area_id FROM areas WHERE name='" . sanitize($area_name) . "'";
            $area_insert_db->myCustomQuery($check_area);
            if ($area_insert_db->row_count > 0) {
                $area_id = $area_insert_db->f_area_id;
            } else {
                $area_insert_db = new database();
                $area_insert_db->data("name", $area_name);
                $area_insert_db->data("is_default", "0");
                $area_insert_db->data("added_by_api", "1");
                $check_area_insert = $area_insert_db->insert("areas");
                if ($check_area_insert) {
                    $json = array("status" => 0, "msg" => "Error!! error in adding area.");
                    $error = 1;
                } else {
                    $area_id = $area_insert_db->id;
                }
            }

            $route_insert_db = new database();
            $route_insert_db->data("area_id", $area_id);
            $route_insert_db->data("name", $route_name);
            $route_insert_db->data("added_by_api", "1");

            if ($route_id == 0) {
                if (empty($area_id) || $area_id == 0) {
                    $json = array("status" => 0, "msg" => "Error!! error in adding route.");
                    $error = 1;
                } else {
                    /* check if route name for area name already exist then show error
                      else insert route name in route tbl */
                    $route_db = new database();
                    $check_route = "SELECT area_id,name FROM routes WHERE name='" . sanitize($route_name) . "' AND "
                            . "area_id='" . sanitize($area_id) . "'";
                    $route_db->myCustomQuery($check_route);
                    if ($route_db->row_count >= 1) {
                        $json = array("status" => 0, "msg" => "Error!! route name for area already exists.");
                        $error = 1;
                    } else {
                        $check_route_insert = $route_insert_db->insert("routes");
                        if ($check_route_insert) {
                            $json = array("status" => 0, "msg" => "Error!! error in adding route.");
                        } else {
                            $json = array("status" => 1, "msg" => "Success!! new route successfully added.");
                        }
                    }
                }
            } else if ($route_id >= 1) {
                if (empty($area_id) || $area_id == 0) {
                    $json = array("status" => 0, "msg" => "Error!! error in updating route.");
                    $error = 1;
                } else {
                    $route_db = new database(); //AREA TABLE
                    /* if area already exist then show error
                      else insert new delivery area */
                    $check_route = "SELECT name,route_id FROM routes WHERE route_id='" . sanitize($route_id) . "'";
                    $route_db->myCustomQuery($check_route);
                    if ($route_db->row_count >= 1) {
                        $route_insert_db->where("route_id=" . $route_id);
                        $check_route_insert = $route_insert_db->update("routes");
                        if ($check_route_insert) {
                            $json = array("status" => 0, "msg" => "Error!! error in updating new route.");
                            $error = 1;
                        } else {
                            $json = array("status" => 1, "msg" => "Success!! route successfully updated.");
                            $error = 0;
                        }
                    } else {
                        $json = array("status" => 0, "msg" => "Error!! route id not exists.");
                        $error = 1;
                    }
                }
            }
        }
    }
} else {
    $json = array("status" => 0, "msg" => "Request method not accepted");
}

/* Output header */
header('Content-type: application/json');
echo json_encode($json);
?>