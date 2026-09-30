<?php

class custom_validations {

    function validate_api($api_key) {
        /* check whether apikey exists for any user if yes then fetch userid else show error */
        $user_api_db = new database(); //For API
        $check_user_api = "SELECT user_id,api_key,status FROM users WHERE api_key='$api_key'";
        $user_api_db->myCustomQuery($check_user_api);
        $status = $user_api_db->f_status;
        $user_id = $user_api_db->f_user_id;
        if (empty($api_key)) {
            //check if api key empty or not
            $json = array("status" => 0, "msg" => "Error!! please provide API Key.");
            $error = 1;
            return $json;
        } else if ($user_api_db->row_count < 1) {
            //check if api key is valid or not
            $json = array("status" => 0, "msg" => "Error!! please provice valid API Key, $api_key is invalid.");
            $error = 1;
            return $json;
        } else if ($user_api_db->row_count >= 1 && $status = 0) {
            //check if API Key found but user is not active i.e status=1
            $json = array("status" => 0, "msg" => "Error!! user account is not active yet.");
            $error = 1;
            return $json;
        }
    }

//end of validate_api

    function is_non_numeric($id) {
        if ($id < 0) {
            //check if route id is non zero or greater than 1
            $json = array("status" => 0, "msg" => "Error!! please provide valid route id,it should be 0 or greater than equals to 1.");
            $error = 1;
            return $json;
        }
    }

}

//end of class
?>