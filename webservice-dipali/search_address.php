<?php

include_once("../bootstrap.php");
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $api_key = trim($_POST['api_key']);
    $ship_to = trim($_POST['ship_to']);
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
            if (strlen(trim($ship_to)) > 0) {
                $search_query = " and (CD.ship_to_name like '%" . sanitize($ship_to) . "%')";
            }

            $db_list = new database();
            $query = "select SQL_CALC_FOUND_ROWS C.customer_name,C.account_number,CD.*
					from 
						customer_addresses CD LEFT JOIN customers C ON CD.customer_id=C.customer_id  
			 where 1=1  " . $search_query . " 
			 order by CD.address_id desc";

            $db_list->myCustomQuery($query);
            if ($db_list->row_count > 0) {
                $jsonData = array();
                for ($i = 0; $i < $db_list->row_count; $i++) {
                    $db_list->moveto($i);
                    $ship_to_name['ship_to_name'] = $db_list->f_ship_to_name;
                    $address['address'] = $db_list->f_address_line_1 . " " . $db_list->f_address_line_2 . " " . $db_list->f_address_line_3;
                    $city['city'] = $db_list->f_city;
                    $state['state'] = $db_list->f_state;
                    $zip['zip'] = $db_list->f_zip;
                    $phone['phone'] = $db_list->f_phone;

                    $data = array_merge($ship_to_name, $address,$city,$state,$zip,$phone);
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