<?php

include_once '../library/api_functions.php';
/* pass array of packages as shown in below format
  $packages[] = array("pckg_type" => "Pakcage type value", "quantity" => "Package quantity");
  NOTE THAT : DO NOT CHANGE NAME OF PACKAGE ARRAY I.E $packages[]
 */
for ($i = 1; $i < 3; $i++) {
    $packages[] = array("pckg_type" => $i, "quantity" => $i); //do not change name of package array i.e $package[]
}
/* pass array of itmes as shown in below format
  $items[] = array("sku" => "Sku","sku_quantity" => "SKU quantity", "prod_name" => "Product name", "desc" => "Description", "uom" => "Unit of mesurment");
  NOTE THAT : DO NOT CHANGE NAME OF ITEMS ARRAY I.E $items[]
 */
for ($j = 0; $j < 3; $j++) {
    $items[] = array("sku" => "test123", "sku_quantity" => $j, "prod_name" => "abc", "desc" => "test desc", "uom" => "kg");
}
/* ARRAY OF DATA REQUIRED TO CREAT NEW SKU */
$data1 = array(
    "manifest_type" => "Delivery", // Pickup or Delivery
    "route_name" => "demo",
    "area_name" => "demo",
    "driver_first_name" => "amol",
    "driver_last_name" => "mohite",
    "driver_emailid" => "r.amol@gmail.net",
    "customer_name" => "demo",
    "customer_address" => "demo",
    "customer_city" => "demo",
    "customer_state" => "demo",
    "customer_zip" => "12345",
    "stop_no" => "41",
    "order_no" => "41",
    "po_no" => "41",
    "deliver_notes" => "test 17 feb 17",
    "scheduled_delivery" => "2017-02-17", //YYYY-MM-DD
    "packages" => $packages,
    "items" => $items
);

$data = http_build_query($data1);
//pass api key to apiFunction
$objfun = new apiFunctions("58a6d8b187f6f");
$result = $objfun->create_new_manifest($data);
echo $result . "<br/>";



/* * ************************************************************************** */
//for multiple pckgs
//for ($i = 0; $i < 2; $i++) {
//    $packages[] = array("pckg_type" => $i, "quantity" => $i);
//}
//for ($j = 0; $j < 2; $j++) {
//    $items[] = array("sku" => "test123", "sku_quantity" => $j,"prod_name"=>"abc","desc"=>"test desc","uom"=>"kg");
//}
//
//
//$data1 = array(
//    "manifest_type" => "Delivery",
//    "route_name" => "demo",
//    "area_name" => "demo",
//    "driver_first_name" => "amol",
//    "driver_last_name" => "mohite",
//    "driver_emailid" => "amol@gmail.com",
//    "customer_name" => "demo",
//    "customer_address" => "demo",
//    "customer_city" => "demo",
//    "customer_state" => "demo",
//    "customer_zip" => "411011",
//    "stop_no" => "41",
//    "order_no" => "41",
//    "po_no" => "41",
//    "deliver_notes" => "test 17 feb 17",
//    "packages" => $packages,
//    "items" => $items
//);
//$data = http_build_query($data1);
//
//$url = "http://localhost/projects/routetrac/api/libraries/create_delivery_manifest.php";
//$ch = curl_init($url);
//curl_setopt($ch, CURLOPT_POST, 1);
//curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
//curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
//curl_setopt($ch, CURLOPT_HEADER, 0);
//curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//$res = curl_exec($ch);
//
//$resultdata = json_decode($res);
//// get jet token here
//echo $res;
?>