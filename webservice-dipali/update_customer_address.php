<?php

include_once("../bootstrap.php");

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $api_key = trim($_POST['api_key']);
    $address_id = trim($_POST['address_id']);
    $ship_to = $_POST['ship_to'];
    $address_1 = $_POST['address_1'];
    $address_2 = $_POST['address_2'];
    $address_3 = $_POST['address_3'];
    $city = $_POST['city'];
    $state = $_POST['state'];
    $zip = $_POST['zip'];
    $phone = $_POST['phone'];
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
        } else if (empty($address_1) || empty($city) || empty($state) || empty($zip) || $address_id == "") {
            //check if $customer_name,$address_id is not empty
            $json = array("status" => 0, "msg" => "Error!! please provide customer address details or address id.");
            $error = 1;
        } else if (!is_numeric($address_id)) {
            //check if $address_id value is non numeric
            $json = array("status" => 0, "msg" => "Error!! please provide valid address id,it should be numeric 0 or greater than equals to 1.");
            $error = 1;
        } else if ($address_id < 0) {
            //check if $address_id is non zero or greater than 1
            $json = array("status" => 0, "msg" => "Error!! please provide valid address id,it should be 0 or greater than equals to 1.");
            $error = 1;
        } else if (!is_numeric($zip)) {
            //check if $address_id value is non numeric
            $json = array("status" => 0, "msg" => "Error!! please provide valid zip,it should be numeric.");
            $error = 1;
        } else if (!preg_match('/^[0-9]{5}$/', $zip)) {
            //check if zip value should be 5 digits
            $json = array("status" => 0, "msg" => "Error!! please provide valid zip,it should be numeric 5 digits.");
            $error = 1;
        }
//        else if (!empty($phone)) {
//            if (!is_numeric($phone)) {
//                //check if $address_id value is non numeric
//                $json = array("status" => 0, "msg" => "Error!! please provide valid phone,it should be numeric.");
//                $error = 1;
//            } else if ($phone < 10) {
//                $json = array("status" => 0, "msg" => "Error!! please provide valid phone,it should be min 10 & max 12 digits.");
//                $error = 1;
//            }else{
//                $error = 0;
//            }
//        }
        else if ($error == 0) {
            $cust_db = new database(); //AREA TABLE
            /* if customer exist then insert address
              else show error */
            $check_cust = "SELECT address_id,customer_id FROM customer_addresses WHERE address_id='" . sanitize($address_id) . "'";
            $cust_db->myCustomQuery($check_cust);
            if ($cust_db->row_count > 0) {
                $address_insert_db = new database(); //for items insert
                $address_insert_db->data("ship_to_name", $ship_to);
                $address_insert_db->data("address_line_1", $address_1);
                $address_insert_db->data("address_line_2", $address_2);
                $address_insert_db->data("address_line_3", $address_3);
                $address_insert_db->data("city", $city);
                $address_insert_db->data("state", $state);
                $address_insert_db->data("zip", $zip);
                $address_insert_db->data("phone", $phone);
                $address_insert_db->data("added_by_api", "1");
                $address_insert_db->where("address_id=" . $address_id);
                $pckg_ins = $address_insert_db->update("customer_addresses");

                if ($pckg_ins) {
                    $json = array("status" => 0, "msg" => "Error!! error in updating customer address details.");
                } else {
                    $json = array("status" => 1, "msg" => "Success!! customer address updated successfully.");
                }
            } else {
                $json = array("status" => 0, "msg" => "Error!! address id not exists.");
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