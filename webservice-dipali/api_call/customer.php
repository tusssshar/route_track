<?php
include_once '../libraries/api_functions.php';
/*******************START OF CREATE NEW CUSTOMER*******************/
$data1 = array(
    "api_key" => "58a6d8b187f6f",
    "customer_id" => 10,//required , for new customer customer_id should be 0 else for update customer_id should be > 0
    "customer_name" => "test",//required 
    "account_no" => "11111", // 
);
$data = http_build_query($data1);
//pass api key to apiFunction
$objfun = new apiFunctions("58a6d8b187f6f");
$result = $objfun->create_new_customer($data);
echo $result . "<br/>";
/*******************END OF CREATE NEW CUSTOMER*******************/

/*******************START OF DELETE CUSTOMER*******************/
//$data1 = array(
//    "customer_id" => 8//required ,route_id should be > 0
//);
//$data = http_build_query($data1);
////pass api key to apiFunction
//$objfun = new apiFunctions("58a6d8b187f6f");
//$result = $objfun->delete_customer($data);
//echo $result . "<br/>";
/*******************END OF DELETE CUSTOMER*******************/

/*******************START OF SEARCH CUSTOMER*******************/
//$data1 = array(
//    "area_name" => "area",//required ,area_id should be > 0
//);
//$data = http_build_query($data1);
////pass api key to apiFunction
//$objfun = new apiFunctions("58a6d8b187f6f");
//$result = $objfun->search_area($data);
//echo $result . "<br/>";
/*******************END OF SEARCH CUSTOMER*******************/

?>