<?php

include_once '../libraries/api_functions.php';
/* START OF CREATE NEW CUSTOMER ADDRESS */
//$data1 = array(
//    "api_key" => "58a6d8b187f6f",
//    "customer_id" => 10, //required
//    "ship_to" => "test",
//    "address_1" => "address_1",
//    "address_2" => "address_2",
//    "address_3" => "address_3",
//    "city" => "city",
//    "state" => "state",
//    "zip" => 12345,
//    "phone" => 123456
//);
//
//$data = http_build_query($data1);
////pass api key to apiFunction
//$objfun = new apiFunctions("58a6d8b187f6f");
//$result = $objfun->create_customer_address($data);
//echo $result . "<br/>";
/* END OF CREATE NEW CUSTOMER ADDRESS */


/* START OF UPDATE CUSTOMER ADDRESS */
$data1 = array(
    "api_key" => "58a6d8b187f6f",
    "address_id" => 10, //required
    "ship_to" => "test",
    "address_1" => "address_1",
    "address_2" => "address_2",
    "address_3" => "address_3",
    "city" => "city",
    "state" => "state",
    "zip" => 12345,
    "phone" => 123456
);

$data = http_build_query($data1);
//pass api key to apiFunction
$objfun = new apiFunctions("58a6d8b187f6f");
$result = $objfun->create_customer_address($data);
echo $result . "<br/>";

/* START OF UPDATE CUSTOMER ADDRESS */
?>