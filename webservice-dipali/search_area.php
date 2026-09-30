<?php

include_once("../bootstrap.php");
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $api_key = trim($_POST['api_key']);
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
        } else if ($error == 0) {
            
            if (strlen(trim($area_name)) > 0) {
                $search_query = " and (areas.name like '%" . sanitize($area_name) . "%')";
            }

            $db_list = new database();
            $query = "select SQL_CALC_FOUND_ROWS areas.*
					from 
						areas   
			 where 1=1  " . $search_query . " 
			 order by areas.area_id desc";


            //vjgm_packages.pack_is_deleted=0

            $db_list->myCustomQuery($query);
            if ($db_list->row_count > 0) {
                $jsonData = array();
                for ($i = 0; $i < $db_list->row_count; $i++) {
                    $db_list->moveto($i);
                    $area['name'] = $db_list->f_name;
                    $is_default['is_default'] = $db_list->f_is_default;
                    $data = array_merge($is_default, $area);
                    $json[] = array("status" => 1, "msg" => $data);
                }
            } else {
                $json = array("status" => 0, "msg" => "No record found.");
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