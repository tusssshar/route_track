<?php

include_once("../bootstrap.php");
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $api_key = trim($_POST['api_key']);
    $route_name = trim($_POST['route_name']);
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
            if (strlen(trim($route_name)) > 0) {
                $search_query = " and (R.name like '%" . sanitize($route_name) . "%')";
            }

            $db_list = new database();
            $query = "select SQL_CALC_FOUND_ROWS A.name AS area_name,R.*
					from 
						routes R LEFT JOIN areas A ON R.area_id=A.area_id 
			 where 1=1  " . $search_query . " 
			 order by R.route_id desc";


            //vjgm_packages.pack_is_deleted=0

            $db_list->myCustomQuery($query);
            if ($db_list->row_count > 0) {
                $jsonData = array();
                for ($i = 0; $i < $db_list->row_count; $i++) {
                    $db_list->moveto($i);
                     $area['area_name'] = $db_list->f_area_name;
                     $route['name'] = $db_list->f_name;
                     $data = array_merge($route, $area);
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