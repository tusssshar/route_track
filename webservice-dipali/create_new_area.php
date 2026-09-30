<?php

include_once("../bootstrap.php");
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $api_key = trim($_POST['api_key']);
    $area_id = trim($_POST['area_id']);
    $delivery_area_name = trim($_POST['area_name']);
    $is_default = trim($_POST['is_default']);
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
        } else if (empty($delivery_area_name) || $area_id == "") {
            //check if area name is not empty
            $json = array("status" => 0, "msg" => "Error!! please provide area name or area id.");
            $error = 1;
        } else if (!is_numeric($area_id)) {
            //check if area id value is non numeric
            $json = array("status" => 0, "msg" => "Error!! please provide valid area id,it should be numeric 0 or greater than equals to 1.");
            $error = 1;
        } else if ($area_id < 0) {
            //check if is_default is non zero or greater than 1
            $json = array("status" => 0, "msg" => "Error!! please provide valid area id,it should be 0 or greater than equals to 1.");
            $error = 1;
        } else if (!is_numeric($is_default)) {
            //check if default value is non numeric
            $json = array("status" => 0, "msg" => "Error!! please provide valid is_default value,it should be numeric, 0 or 1.");
            $error = 1;
        } else if ($is_default < 0 || $is_default > 1) {
            //check if is_default is non zero or greater than 1
            $json = array("status" => 0, "msg" => "Error!! please provide valid is_default value,it should be 0 or 1.");
            $error = 1;
        } else if ($error == 0) {
            /* if area_id <= 0 then create new delivery area 
              else if area_id >=1 then update record for area_id */

            $area_insert_db = new database(); //INSERT AREA TABLE
            $area_insert_db->data("name", $delivery_area_name);
            $area_insert_db->data("is_default", $is_default);
            $area_insert_db->data("added_by_api", "1");

            if ($area_id == 0) {
                $area_db = new database(); //AREA TABLE
                /* if area already exist then show error
                  else insert new delivery area */
                $check_area = "SELECT name,area_id FROM areas WHERE name='" . sanitize($delivery_area_name) . "'";
                $area_db->myCustomQuery($check_area);
                if ($area_db->row_count > 0) {
                    $json = array("status" => 0, "msg" => "Error!! area name already exists.");
                    $error = 1;
                } else {
                    $check_area_insert = $area_insert_db->insert("areas");
                    if ($check_area_insert) {
                        $json = array("status" => 0, "msg" => "Error!! error in adding new delivery area.");
                        $error = 1;
                    } else {
                        $json = array("status" => 1, "msg" => "Success!! new delivery area successfully added.");
                        $error = 0;
                    }
                }
            } else if ($area_id >= 1) {
                $area_db = new database(); //AREA TABLE
                /* if area already exist then show error
                  else insert new delivery area */
                $check_area_id = "SELECT name,area_id FROM areas WHERE area_id='" . sanitize($area_id) . "'";
                $area_db->myCustomQuery($check_area_id);
                if ($area_db->row_count >= 1) {
                    $area_insert_db->where("area_id=" . $area_id);
                    $check_area_insert = $area_insert_db->update("areas");
                    if ($check_area_insert) {
                        $json = array("status" => 0, "msg" => "Error!! error in updating delivery area.");
                        $error = 1;
                    } else {
                        $json = array("status" => 1, "msg" => "Success!! delivery area successfully updated.");
                        $error = 0;
                    }
                } else {
                    $json = array("status" => 0, "msg" => "Error!! area id not exists.");
                    $error = 1;
                }
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