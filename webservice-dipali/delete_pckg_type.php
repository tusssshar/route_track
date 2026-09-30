<?php
include_once("../bootstrap.php");
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $api_key = trim($_POST['api_key']);
    $package_type_id = trim($_POST['package_type_id']);
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
        } else if ($package_type_id == "") {
            //check if package type id is not empty
            $json = array("status" => 0, "msg" => "Error!! please provide package type id.");
            $error = 1;
        } else if (!is_numeric($package_type_id)) {
            //check if package type id value is non numeric
            $json = array("status" => 0, "msg" => "Error!! please provide valid package type id,it should be numeric and greater than equals to 1.");
            $error = 1;
        } else if ($package_type_id <= 0) {
            //check if package type id is non zero or greater than 1
            $json = array("status" => 0, "msg" => "Error!! please provide valid package type id,it should be greater than equals to 1.");
            $error = 1;
        } else if ($error == 0) {
            $package_type_db = new database(); //AREA TABLE
            /* if package type id not exist then show error
              else delete package type id */
            $check_package_type_id = "SELECT name,package_type_id FROM package_types WHERE package_type_id='" . sanitize($package_type_id) . "'";
            $package_type_db->myCustomQuery($check_package_type_id);
            if ($package_type_db->row_count >= 1) {
                $package_type_delete_db = new database();
                $package_type_delete_db->where("package_type_id=" . $package_type_id);
                $package_type_delete = $package_type_delete_db->delete("package_types");
                if ($package_type_delete) {
                    $json = array("status" => 0, "msg" => "Error!! error in deleting package type.");
                    $error = 1;
                } else {
                    $json = array("status" => 1, "msg" => "Success!! package type deleted successfully.");
                    $error = 0;
                }
            } else {
                $json = array("status" => 0, "msg" => "Error!! package type id not exists.");
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