<?php

include_once("../bootstrap.php");
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $api_key = trim($_POST['api_key']);
    $customer_id = trim($_POST['customer_id']);
    $customer_name = trim($_POST['customer_name']);
    $account_no = trim($_POST['account_no']);
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
        } else if (empty($customer_name) || $customer_id == "") {
            //check if $customer_name,$customer_id is not empty
            $json = array("status" => 0, "msg" => "Error!! please provide customer name or account no or customer id.");
            $error = 1;
        } else if (!is_numeric($customer_id)) {
            //check if $customer_id value is non numeric
            $json = array("status" => 0, "msg" => "Error!! please provide valid customer id,it should be numeric 0 or greater than equals to 1.");
            $error = 1;
        } else if ($customer_id < 0) {
            //check if $customer_id is non zero or greater than 1
            $json = array("status" => 0, "msg" => "Error!! please provide valid customer id,it should be 0 or greater than equals to 1.");
            $error = 1;
        } else if ($error == 0) {
            /* if $customer_id = 0 then create new customer
              else if $customer_id >=1 then update customer record */

            $customer_insert_db = new database(); //INSERT CUSTOMER TABLE
            $customer_insert_db->data("customer_name", $customer_name);
            $customer_insert_db->data("account_number", $account_no);
            $customer_insert_db->data("added_by_api", "1");
            
            $customer_db = new database(); 
            if ($customer_id == 0) {
                /* if customer already exist then show error
                  else insert new customer */
                $check_customer = "SELECT account_number,customer_name,customer_id FROM customers WHERE customer_name='" . sanitize($customer_name) . "'";
                $customer_db->myCustomQuery($check_customer);
                if ($customer_db->row_count > 0) {
                    $json = array("status" => 0, "msg" => "Error!! customer already exists.");
                    $error = 1;
                } else {
                    $check_customer_insert = $customer_insert_db->insert("customers");
                    if ($check_customer_insert) {
                        $json = array("status" => 0, "msg" => "Error!! error in adding customer.");
                        $error = 1;
                    } else {
                        $json = array("status" => 1, "msg" => "Success!! new customer successfully added.");
                        $error = 0;
                    }
                }
            } else if ($customer_id >= 1) {
                /* if customer already exist then show error
                  else insert new customer */
                $check_customer = "SELECT account_number,customer_name,customer_id FROM customers WHERE customer_id='" . sanitize($customer_id) . "'";
                $customer_db->myCustomQuery($check_customer);
                if ($customer_db->row_count >= 1) {
                    $customer_insert_db->where("customer_id=" . $customer_id);
                    $check_customer_insert = $customer_insert_db->update("customers");
                    if ($check_customer_insert) {
                        $json = array("status" => 0, "msg" => "Error!! error in updating customer.");
                        $error = 1;
                    } else {
                        $json = array("status" => 1, "msg" => "Success!! customer successfully updated.");
                        $error = 0;
                    }
                } else {
                    $json = array("status" => 0, "msg" => "Error!! customer id not exists.");
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