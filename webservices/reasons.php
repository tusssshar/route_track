<?php
include_once(__DIR__ . '/request_filter.php');


$app_key = $_REQUEST['app_key'];
include_once("../bootstrap.php");
include_once('message.php');

$result = array();
$data = array();

$index_value = 1;
$order_type_array = array("Building Closed", "No One Available to Sign", "Refused Delivery", "Could not find Address", "Aborted  Road Hazards  Closures", "Other");
//print_r($order_type_array);
foreach ($order_type_array as $type) {

    $data[$index_value] = $type;
    $flag = TRUE;
    $index_value++;
}
array_push($result, $data);


if ($flag == TRUE) {
    $json = array($RESPONSE_KEY => $RESPONSE_SUCCESS, 'msg' => 'Success', 'info' => $result);
    echo json_encode($json);
} else {
    $json = array($RESPONSE_KEY => $RESPONSE_ERROR, 'msg' => 'Failed', 'last_id' => '0');
    echo json_encode($json);
}



//$query1 = "SELECT SUM(DI.qty) AS ITEM_COUNT FROM deliveries D LEFT JOIN delivery_items DI ON D.delivery_id = DI.delivery_id WHERE D.driver_id ='" . $driver_id . "' AND D.status='In Transit' AND D.customer_id='".$db->f_customer_id."'";
//$db1->myCustomQuery($query1);
?>


