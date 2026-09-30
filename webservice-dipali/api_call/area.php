<?php
include_once '../libraries/api_functions.php';
/*******************START OF CREATE NEW DELIVERY AREA*******************/
$data1 = array(
    "api_key" => "58a6d8b187f6f",
    "area_id" => 24,//required , for new area area_id should be 0 else for update area_id should be > 0
    "area_name" => "testing area",//required 
    "is_default" => "0", // should be 0 or 1
);

$data = http_build_query($data1);
//pass api key to apiFunction
$objfun = new apiFunctions();
$result = $objfun->create_new_area($data);
echo $result . "<br/>";
/*******************END OF CREATE NEW DELIVERY AREA*******************/

/*******************START OF DELETE DELIVERY AREA*******************/
//$data1 = array(
//    "area_id" => 27,//required ,area_id should be > 0
//);
//$data = http_build_query($data1);
////pass api key to apiFunction
//$objfun = new apiFunctions("58a6d8b187f6f");
//$result = $objfun->delete_area($data);
//echo $result . "<br/>";
/*******************END OF DELETE DELIVERY AREA*******************/

/*******************START OF SEARCH DELIVERY AREA*******************/
//$data1 = array(
//    "area_name" => "area",//required ,area_id should be > 0
//);
//$data = http_build_query($data1);
////pass api key to apiFunction
//$objfun = new apiFunctions("58a6d8b187f6f");
//$result = $objfun->search_area($data);
//echo $result . "<br/>";
/*******************END OF SEARCH DELIVERY AREA*******************/

?>