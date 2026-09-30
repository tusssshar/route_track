<?php
include_once '../libraries/api_functions.php';
/*******************START OF CREATE NEW ROUTE*******************/
$data1 = array(
    "api_key" => "58a6d8b187f6f",
    "route_id" => 3,//required , for new route route_id should be 0 else for update route_id should be > 0
    "route_name" => "route updated",//required 
    "area_name" => "test area for route2", // required
);

$data = http_build_query($data1);
//pass api key to apiFunction
$objfun = new apiFunctions("58a6d8b187f6f");
$result = $objfun->create_new_route($data);
echo $result . "<br/>";
/*******************END OF CREATE NEW ROUTE*******************/

/*******************START OF DELETE ROUTE*******************/
//$data1 = array(
//    "route_id" => 2//required ,route_id should be > 0
//);
//$data = http_build_query($data1);
////pass api key to apiFunction
//$objfun = new apiFunctions("58a6d8b187f6f");
//$result = $objfun->delete_route($data);
//echo $result . "<br/>";
/*******************END OF DELETE ROUTE*******************/

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