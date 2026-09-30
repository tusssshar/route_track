<?php
include_once '../libraries/api_functions.php';
/*******************START OF CREATE NEW PACKAGE TYPE*******************/
$data1 = array(
    "api_key" => "58a6d8b187f6f",
    "package_type_id" => 5,//required , for new package_type package_type_id should be 0 else for update package_type_id should be > 0
    "package_name" => "package_type - feb -17",//required 
);
$data = http_build_query($data1);
//pass api key to apiFunction
$objfun = new apiFunctions("58a6d8b187f6f");
$result = $objfun->create_new_pckg_type($data);
echo $result . "<br/>";
/*******************END OF CREATE NEW PACKAGE TYPE*******************/

/*******************START OF DELETE PACKAGE TYPE*******************/
//$data1 = array(
//    "package_type_id" => 4,//required ,package_type_id should be > 0
//);
//$data = http_build_query($data1);
////pass api key to apiFunction
//$objfun = new apiFunctions("58a6d8b187f6f");
//$result = $objfun->delete_pckg_type($data);
//echo $result . "<br/>";
/*******************END OF DELETE PACKAGE TYPE*******************/

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