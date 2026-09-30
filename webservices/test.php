<?php
include_once(__DIR__ . '/request_filter.php');


$app_key = $_REQUEST['app_key'];
include_once("../bootstrap.php");
include_once('message.php');

$driver_id = $_REQUEST['driver_id'];

$result = array();
$data = array();
if (isset($driver_id)) {

    $db = new database();
    $db1 = new database();
    $db2 = new database();
    $query = "SELECT DISTINCT(D.route_id) AS ROUTE_ID FROM deliveries D WHERE  D.driver_id ='" . $driver_id . "'AND  D.status='In Transit'";


    $db->myCustomQuery($query);
    if ($db->row_count > 0) {

        for ($i = 0; $i < $db->row_count; $i++) {
            $db->moveto($i);

            $delivery_id = $db->f_delivery_id;
            $route_id = $db->f_ROUTE_ID;
            //$driver_id = $db->f_driver_id;

            $query1 = "SELECT SUM(DI.qty) AS TOTAL_DELIVERIES_ITEM,DP.qty AS TOTAL_DELIVERIES_PACKAGE,R.name AS ROUTE_NAME FROM deliveries D LEFT JOIN delivery_items DI ON D.delivery_id = DI.delivery_id LEFT JOIN delivery_packages DP ON D.delivery_id=DP.delivery_id LEFT JOIN manifests M ON M.manifest_id=D.manifest_id LEFT JOIN routes R ON D.route_id=R.route_id  WHERE D.driver_id ='" . $driver_id . "' AND D.route_id='" . $route_id . "' AND M.manifest_type='Delivery' AND D.status='In Transit'";

            $query2 = "SELECT SUM(DI.qty) AS TOTAL_PICKUP_ITEM,DP.qty AS TOTAL_PICKUP_PACKAGE,R.name AS ROUTE_NAME FROM deliveries D LEFT JOIN manifests M ON M.manifest_id=D.manifest_id LEFT JOIN delivery_packages DP ON D.delivery_id=DP.delivery_id LEFT JOIN delivery_items DI ON D.delivery_id = DI.delivery_id LEFT JOIN routes R ON D.route_id=R.route_id WHERE D.driver_id ='" . $driver_id . "' AND D.route_id='" . $route_id . "' AND M.manifest_type='Pickup' AND D.status='In Transit'";
            $db1->myCustomQuery($query1);
            $db2->myCustomQuery($query2);

            //$data['customer_name'] = $db->f_customer_name;
            //$data['order_number'] = $db->f_order_number;
            $data['total_deliveries_item'] = $db1->f_TOTAL_DELIVERIES_ITEM;
            $data['total_deliveries_package'] = $db1->f_TOTAL_DELIVERIES_PACKAGE;
           // $data['delivery_id'] = $db->f_delivery_id;
           // $data['manifest_type'] = $db->f_manifest_type;
            $data['route_name'] = $db1->f_ROUTE_NAME;
            $data['total_pickup_item'] = $db2->f_TOTAL_PICKUP_ITEM;
            $data['total_pickup_package'] = $db2->f_TOTAL_PICKUP_PACKAGE;

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


