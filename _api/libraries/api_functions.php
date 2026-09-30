<?php
include_once("../../bootstrap.php");
class apiFunctions {

    public $apikey;

/*********************Parameterized constructor with API Key as parameter******************** */

    function __construct($apikey) {
        $this->apikey = $apikey;
    }

/*********************END OF Parameterized constructor with API Key as parameter******************** */

/*********************************START OF COMMON CURL FUNCTION***********************************/
/*common_curl function contains below mentioned parameters
1.$url-> To which url we need to pass data
2.$data -> post data required to send to url
3.$api_key->api key need for authorization
*/
function common_curl($url,$data,$api_key){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data . "&" . $api_key);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($ch);
        return $res;
}
/*********************************END OF COMMON CURL FUNCTION***********************************/

/*********************************START OF COMMON API_KEY FUNCTION***********************************/
function api_key_auth() {
        $apikey = array("api_key" => $this->apikey);
        $api_key = http_build_query($apikey);
        return $api_key;
}
/*********************************END OF COMMON API_KEY FUNCTION***********************************/

/*********************************START OF CREATE MANIFEST FUNCTION***********************************/

    function create_new_manifest($data) {
        $api_key = $this->api_key_auth();
        $url = SITEROOT."/api/create_delivery_manifest.php";
        $result =  $this->common_curl($url,$data,$api_key);
        return $result;
    }
/*********************************END OF CREATE MANIFEST FUNCTION***********************************/
  
/*********************************START OF CREATE/UPDATE AREA FUNCTION***********************************/

    function create_new_area($data) {
        $api_key = $this->api_key_auth();
        $url = SITEROOT."/api/create_new_area.php";
        $result =  $this->common_curl($url,$data,$api_key);
        return $result;
    }
/*********************************END OF CREATE/UPDATE AREA FUNCTION***********************************/

/*********************************START OF DELETE AREA FUNCTION***********************************/
 function delete_area($data) {
        $api_key = $this->api_key_auth();
        $url = SITEROOT."/api/delete_area.php";
        $result = $this->common_curl($url,$data,$api_key);
        return $result;        
 }
    
/*********************************END OF DELETE AREA FUNCTION***********************************/
 
/*********************************START OF DELETE AREA FUNCTION***********************************/
 function search_area($data) {
        $api_key = $this->api_key_auth();
        $url = SITEROOT."/api/search_area.php";
        $result = $this->common_curl($url,$data,$api_key);
        return $result;
    }
    
/*********************************END OF DELETE AREA FUNCTION***********************************/

/*********************************START OF CREATE/UPDATE PACKAGE TYPE FUNCTION***********************************/

    function create_new_pckg_type($data) {
        $api_key = $this->api_key_auth();
        $url = SITEROOT."/api/create_new_pckg_type.php";
        $result =  $this->common_curl($url,$data,$api_key);
        return $result;
    }
/*********************************END OF CREATE/UPDATE PACKAGE TYPE FUNCTION***********************************/
    
/*********************************START OF DELETE PACKAGE TYPE FUNCTION***********************************/
 function delete_pckg_type($data) {
        $api_key = $this->api_key_auth();
        $url = SITEROOT."/api/delete_pckg_type.php";
        $result = $this->common_curl($url,$data,$api_key);
        return $result;        
 }
    
/*********************************END OF DELETE PACKAGE TYPE FUNCTION***********************************/   
    
/*********************************START OF CREATE/UPDATE ROUTE FUNCTION***********************************/
function create_new_route($data) {
        $api_key = $this->api_key_auth();
        $url = SITEROOT."/api/create_new_route.php";
        $result =  $this->common_curl($url,$data,$api_key);
        return $result;
 }
/*********************************END OF CREATE/UPDATE ROUTE FUNCTION***********************************/
 
/*********************************START OF DELETE ROUTE FUNCTION***********************************/
 function delete_route($data) {
        $api_key = $this->api_key_auth();
        $url = SITEROOT."/api/delete_route.php";
//                $url = SITEROOT."/api/test.php";
        $result = $this->common_curl($url,$data,$api_key);
        return $result;        
 }
    
/*********************************END OF DELETE ROUTE FUNCTION***********************************/
 
/*********************************START OF CREATE/UPDATE CUSTOMER FUNCTION***********************************/
function create_new_customer($data) {
        $api_key = $this->api_key_auth();
        $url = SITEROOT."/api/create_new_customer.php";
        $result =  $this->common_curl($url,$data,$api_key);
        return $result;
 }
/*********************************END OF CREATE/UPDATE ROUTE FUNCTION***********************************/ 

/*********************************START OF DELETE CUSTOMER FUNCTION***********************************/
 function delete_customer($data) {
        $api_key = $this->api_key_auth();
        $url = SITEROOT."/api/delete_customer.php";
        $result = $this->common_curl($url,$data,$api_key);
        return $result;        
 }
/*********************************END OF DELETE CUSTOMER FUNCTION***********************************/
 
/*********************************START OF CREATE/UPDATE CUSTOMER FUNCTION***********************************/
function create_customer_address($data) {
        $api_key = $this->api_key_auth();
        $url = SITEROOT."/api/create_customer_address.php";
        $result =  $this->common_curl($url,$data,$api_key);
        return $result;
 }
/*********************************END OF CREATE/UPDATE ROUTE FUNCTION***********************************/ 
 
}//end of class

?>