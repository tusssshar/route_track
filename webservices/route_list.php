<?php
include_once(__DIR__ . '/request_filter.php');


$app_key = $_REQUEST['app_key'];
include_once("../bootstrap.php");
include_once('message.php');

$driver_id = $_REQUEST['driver_id'];
$CURDATE = date("Y-m-d");
$status = 'NO';
$result = array();
$data = array();
if (isset($driver_id)) {

    $db = new database();
    $db_delivery_item = new database();
    $db_pickup_item = new database();
    $db_delivery_package = new database();
    $db_pickup_package = new database();
    $db_total_deliveries_orders = new database();
    $db_total_pickup_orders = new database();
    $db_accept_route = new database();
    $query = "SELECT DISTINCT(D.route_id) AS ROUTE_ID,R.name AS ROUTE_NAME FROM deliveries D LEFT JOIN routes R ON D.route_id=R.route_id WHERE  D.driver_id ='" . $driver_id . "'AND  D.status='In Transit' AND  scheduled_delivery='$CURDATE'";


//echo "<BR><BR>";

    $db->myCustomQuery($query);
    if ($db->row_count > 0) {

        for ($i = 0; $i < $db->row_count; $i++) {
            $db->moveto($i);

            $delivery_id = $db->f_delivery_id;
            $route_id = $db->f_ROUTE_ID;
            //$driver_id = $db->f_driver_id;

            $query_check_accept_status = "SELECT status FROM route_accept WHERE route_id='" . $route_id . "' AND driver_id='" . $driver_id . "' AND  route_accept_date='$CURDATE'";

            $db_accept_route->myCustomQuery($query_check_accept_status);
            if ($db_accept_route->row_count > 0) {
                $status = $db_accept_route->f_status;
            } else {
                $status = 'NO';
            }




            //Total Deliveries Orders
            $query_total_deliveries_orders = "SELECT COUNT(delivery_id)  AS DELIVERY_ORDER_COUNT FROM `deliveries` D LEFT JOIN manifests M ON D.manifest_id=M.manifest_id WHERE `route_id`='" . $route_id . "' and `driver_id`='" . $driver_id . "' AND scheduled_delivery='$CURDATE' AND M.manifest_type='Delivery' AND D.status='In Transit'";
            $db_total_deliveries_orders->myCustomQuery($query_total_deliveries_orders);
            $db_total_deliveries_orders->moveto(0);

//ECHO "<BR>";
            //Total Pickup Orders
            $query_total_pickup_orders = "SELECT COUNT(delivery_id) AS PICKUP_ORDER_COUNT FROM `deliveries` D LEFT JOIN manifests M ON D.manifest_id=M.manifest_id WHERE `route_id`='" . $route_id . "' and `driver_id`='" . $driver_id . "' AND scheduled_delivery='$CURDATE' AND M.manifest_type='Pickup' AND D.status='In Transit'";
            $db_total_pickup_orders->myCustomQuery($query_total_pickup_orders);
            $db_total_pickup_orders->moveto(0);

            /*

              $query_delivery_item = "SELECT SUM(DI.qty) AS TOTAL_DELIVERIES_ITEM FROM deliveries D LEFT JOIN delivery_items DI ON D.delivery_id = DI.delivery_id  LEFT JOIN manifests M ON M.manifest_id=D.manifest_id  WHERE D.driver_id ='" . $driver_id . "' AND D.route_id='" . $route_id . "' AND M.manifest_type='Pickup' AND D.status='In Transit' AND  D.scheduled_delivery=$CURDATE";
              $db_delivery_item->myCustomQuery($query_delivery_item);
              $db_delivery_item->moveto(0);

              // echo "<BR><BR>";
              $query_delivey_package = "SELECT SUM(DP.qty) AS TOTAL_DELIVERIES_PACKAGE FROM deliveries D  LEFT JOIN delivery_packages DP ON D.delivery_id=DP.delivery_id LEFT JOIN manifests M ON M.manifest_id=D.manifest_id  WHERE D.driver_id ='" . $driver_id . "' AND D.route_id='" . $route_id . "' AND M.manifest_type='Delivery' AND D.status='In Transit' AND  D.scheduled_delivery=$CURDATE";


              $db_delivery_package->myCustomQuery($query_delivey_package);
              $db_delivery_package->moveto(0);


              //echo "<BR><BR>";
              $query_pickup_items = "SELECT SUM(DI.qty) AS TOTAL_PICKUP_ITEM FROM deliveries D LEFT JOIN manifests M ON M.manifest_id=D.manifest_id  LEFT JOIN delivery_items DI ON D.delivery_id = DI.delivery_id  WHERE D.driver_id ='" . $driver_id . "' AND D.route_id='" . $route_id . "' AND M.manifest_type='Pickup' AND D.status='In Transit' AND  D.scheduled_delivery=$CURDATE";

              $db_pickup_item->myCustomQuery($query_pickup_items);
              $db_pickup_item->moveto(0);

              //  echo "<BR><BR>";

              $query_pickup_package = "SELECT SUM(DP.qty) AS TOTAL_PICKUP_PACKAGE FROM deliveries D LEFT JOIN manifests M ON M.manifest_id=D.manifest_id LEFT JOIN delivery_packages DP ON D.delivery_id=DP.delivery_id   WHERE D.driver_id ='" . $driver_id . "' AND D.route_id='" . $route_id . "' AND M.manifest_type='Pickup' AND D.status='In Transit' AND  D.scheduled_delivery=$CURDATE";


              $db_pickup_package->myCustomQuery($query_pickup_package);
              $db_pickup_package->moveto(0);

              //$data['customer_name'] = $db->f_customer_name;
              //$data['order_number'] = $db->f_order_number;

              //   $data['total_deliveries_item'] = $db_delivery_item->f_TOTAL_DELIVERIES_ITEM;
              //  $data['total_deliveries_package'] = $db_delivery_package->f_TOTAL_DELIVERIES_PACKAGE;
              // $data['delivery_id'] = $db->f_delivery_id;
              // $data['manifest_type'] = $db->f_manifest_type;
              $data['route_name'] = $db->f_ROUTE_NAME;
              //  $data['total_pickup_item'] = $db_pickup_item->f_TOTAL_PICKUP_ITEM;
              //  $data['total_pickup_package'] = $db_pickup_package->f_TOTAL_PICKUP_PACKAGE;
              //  $data['total_pickup_package'] = $db_pickup_package->f_TOTAL_PICKUP_PACKAGE;

             */
            $data['route_name'] = $db->f_ROUTE_NAME;
            $data['route_id'] = $db->f_ROUTE_ID;
            $data['total_delivery_orders'] = $db_total_deliveries_orders->f_DELIVERY_ORDER_COUNT;
            $data['total_picup_orders'] = $db_total_pickup_orders->f_PICKUP_ORDER_COUNT;
            $data['accept_flag'] = $status;

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