<?php

include_once("../bootstrap.php");
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $api_key = trim($_POST['api_key']);
    $package_type_id = trim($_POST['package_type_id']);
    $package_name = trim($_POST['package_name']);
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
        } else if (empty($package_name) || $package_type_id == "") {
            //check if $package_name,$package_type_id is not empty
            $json = array("status" => 0, "msg" => "Error!! please provide package type or package type id.");
            $error = 1;
        } else if (!is_numeric($package_type_id)) {
            //check if $package_type_id value is non numeric
            $json = array("status" => 0, "msg" => "Error!! please provide valid package type id,it should be numeric 0 or greater than equals to 1.");
            $error = 1;
        } else if ($package_type_id < 0) {
            //check if $package_type_id is non zero or greater than 1
            $json = array("status" => 0, "msg" => "Error!! please provide valid package type id,it should be 0 or greater than equals to 1.");
            $error = 1;
        } else if ($error == 0) {
            /* if $package_type_id = 0 then create new package type 
              else if $package_type_id >=1 then update record for $package_type_id */

            $package_insert_db = new database(); //INSERT AREA TABLE
            $package_insert_db->data("name", $package_name);
            $package_insert_db->data("added_by_api", "1");

            if ($package_type_id == 0) {
                $package_db = new database(); //AREA TABLE
                /* if record already exist then show error
                  else insert new record */
                $check_package = "SELECT name,package_type_id FROM package_types WHERE name='" . sanitize($package_name) . "'";
                $package_db->myCustomQuery($check_package);
                if ($package_db->row_count > 0) {
                    $json = array("status" => 0, "msg" => "Error!! package type already exists.");
                    $error = 1;
                } else {
                    $check_package_insert = $package_insert_db->insert("package_types");
                    if ($check_package_insert) {
                        $json = array("status" => 0, "msg" => "Error!! error in adding new package type.");
                        $error = 1;
                    } else {
                        $json = array("status" => 1, "msg" => "Success!! new package type successfully added.");
                        $error = 0;
                    }
                }
            } else if ($package_type_id >= 1) {
                $package_db = new database(); //AREA TABLE
                /* if record already exist then show error
                  else insert new record */
                $check_package = "SELECT name,package_type_id FROM package_types WHERE package_type_id='" . sanitize($package_type_id) . "'";
                $package_db->myCustomQuery($check_package);
                if ($package_db->row_count >= 1) {
                    $package_insert_db->where("package_type_id=" . $package_type_id);
                    $check_package_insert = $package_insert_db->update("package_types");
                    if ($check_package_insert) {
                        $json = array("status" => 0, "msg" => "Error!! error in updating package type.");
                        $error = 1;
                    } else {
                        $json = array("status" => 1, "msg" => "Success!! package type successfully updated.");
                        $error = 0;
                    }
                } else {
                    $json = array("status" => 0, "msg" => "Error!! package type id not exists.");
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