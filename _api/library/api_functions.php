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
  
}//end of class

?>