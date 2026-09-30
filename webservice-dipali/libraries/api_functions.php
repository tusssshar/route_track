<?php
include_once("../../bootstrap.php");
class apiFunctions {

/*********************************START OF COMMON CURL FUNCTION***********************************/
/*common_curl function contains below mentioned parameters
1.$url-> To which url we need to pass data
2.$data -> post data required to send to url
3.$api_key->webservice key need for authorization
*/
function common_curl($url,$data){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($ch);
        return $res;
}
/*********************************END OF COMMON CURL FUNCTION***********************************/

/*********************************START OF CREATE/UPDATE AREA FUNCTION***********************************/

    function create_new_area($data) {
        $url = SITEROOT."/webservice/create_new_area.php";
        $result =  $this->common_curl($url,$data);
        return $result;
    }
/*********************************END OF CREATE/UPDATE AREA FUNCTION***********************************/

/*********************************START OF DELETE AREA FUNCTION***********************************/
 function delete_area($data) {
        $url = SITEROOT."/webservice/delete_area.php";
        $result = $this->common_curl($url,$data);
        return $result;        
 }
    
/*********************************END OF DELETE AREA FUNCTION***********************************/
 
/*********************************START OF DELETE AREA FUNCTION***********************************/
 function search_area($data) {
        $url = SITEROOT."/webservice/search_area.php";
        $result = $this->common_curl($url,$data);
        return $result;
    }
    
/*********************************END OF DELETE AREA FUNCTION***********************************/

/*********************************START OF CREATE/UPDATE PACKAGE TYPE FUNCTION***********************************/

    function create_new_pckg_type($data) {
        $url = SITEROOT."/webservice/create_new_pckg_type.php";
        $result =  $this->common_curl($url,$data);
        return $result;
    }
/*********************************END OF CREATE/UPDATE PACKAGE TYPE FUNCTION***********************************/
    
/*********************************START OF DELETE PACKAGE TYPE FUNCTION***********************************/
 function delete_pckg_type($data) {
        $url = SITEROOT."/webservice/delete_pckg_type.php";
        $result = $this->common_curl($url,$data);
        return $result;        
 }
    
/*********************************END OF DELETE PACKAGE TYPE FUNCTION***********************************/   
    
/*********************************START OF CREATE/UPDATE ROUTE FUNCTION***********************************/
function create_new_route($data) {
        $url = SITEROOT."/webservice/create_new_route.php";
        $result =  $this->common_curl($url,$data);
        return $result;
 }
/*********************************END OF CREATE/UPDATE ROUTE FUNCTION***********************************/
 
/*********************************START OF DELETE ROUTE FUNCTION***********************************/
 function delete_route($data) {
        $url = SITEROOT."/webservice/delete_route.php";
//                $url = SITEROOT."/webservice/test.php";
        $result = $this->common_curl($url,$data);
        return $result;        
 }
    
/*********************************END OF DELETE ROUTE FUNCTION***********************************/
 
/*********************************START OF CREATE/UPDATE CUSTOMER FUNCTION***********************************/
function create_new_customer($data) {
        $url = SITEROOT."/webservice/create_new_customer.php";
        $result =  $this->common_curl($url,$data);
        return $result;
 }
/*********************************END OF CREATE/UPDATE ROUTE FUNCTION***********************************/ 

/*********************************START OF DELETE CUSTOMER FUNCTION***********************************/
 function delete_customer($data) {
        $url = SITEROOT."/webservice/delete_customer.php";
        $result = $this->common_curl($url,$data);
        return $result;        
 }
/*********************************END OF DELETE CUSTOMER FUNCTION***********************************/
 
/*********************************START OF CREATE/UPDATE CUSTOMER FUNCTION***********************************/
function create_customer_address($data) {
        $url = SITEROOT."/webservice/create_customer_address.php";
        $result =  $this->common_curl($url,$data);
        return $result;
 }
/*********************************END OF CREATE/UPDATE ROUTE FUNCTION***********************************/ 
 
}//end of class

?>