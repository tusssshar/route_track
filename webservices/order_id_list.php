<?php
include_once(__DIR__ . '/request_filter.php');

$app_key = $_REQUEST['app_key'];
include_once("../bootstrap.php");
include_once('message.php');

$customer_id = $_REQUEST['customer_id'];

$result = array();
$data = array();
if (isset($customer_id)) {



    $db = new database();
    $query = "SELECT D.delivery_id,D.order_number,D.delivery_id,COUNT(DI.delivery_id) AS ITEM_COUNT FROM deliveries D LEFT JOIN delivery_items DI ON D.delivery_id = DI.delivery_id  WHERE D.customer_id= '" . $customer_id . "'AND D.status='In Transit' GROUP BY DI.delivery_id";

    //         
    $db->myCustomQuery($query);
    if ($db->row_count > 0) {

        for ($i = 0; $i < $db->row_count; $i++) {
            $db->moveto($i);
            $data['order_number'] = $db->f_order_number;
            $data['item_count '] = $db->f_ITEM_COUNT;
            $data['delivery_id'] = $db->f_delivery_id;


            array_push($result, $data);
        }

        $json = array($RESPONSE_KEY => $RESPONSE_SUCCESS, 'msg' => 'Success', 'info' => $result);
        echo json_encode($json);
    } else {
        $json = array($RESPONSE_KEY => $RESPONSE_ERROR, 'msg' => 'Failed', 'last_id' => '0');
        echo json_encode($json);
    }
} else {
    $json = array($RESPONSE_KEY => $RESPONSE_ERROR, 'msg' => 'Please pass all parameter', 'last_id' => '0');
    echo json_encode($json);
}
?>


