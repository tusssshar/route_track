<?php
include_once(__DIR__ . '/request_filter.php');

$app_key = $_REQUEST['app_key'];
include_once("../bootstrap.php");
include_once('message.php');

$driver_id = $_REQUEST['driver_id'];
$route_id = $_REQUEST['route_id'];
$CURDATE = date("Y-m-d");
$result = array();
$data = array();
if (isset($driver_id)) {

    $db = new database();
     $query = "SELECT CA.address_id,D.delivery_id,"
            . "D.order_number,"
            . "C.customer_name,"
            . "D.latitude,"
            . "D.longitude,"
            . "CA.address_line_1,"
            . "CA.address_line_2,"
            . "CA.address_line_3,"
            . "CA.city,"
            . "CA.state,"
            . "CA.zip,"
            . "CA.phone  "
            . " FROM customers C LEFT JOIN deliveries D ON C.customer_id = D.customer_id "
                . "LEFT JOIN customer_addresses CA ON D.address_id =CA.address_id "
                . " WHERE D.driver_id ='" . $driver_id . "' AND D.route_id='".$route_id."' "
                . "AND D.status='In Transit' AND  D.scheduled_delivery='".$CURDATE."' GROUP BY CA.address_id";


    //         
    $db->myCustomQuery($query);
    if ($db->row_count > 0) {

        for ($i = 0; $i < $db->row_count; $i++) {
            $db->moveto($i);
          
            $data['customer_name'] = $db->f_customer_name;
            $data['order_number'] = $db->f_order_number;
            $data['latitude'] = $db->f_latitude;
            $data['longitude'] = $db->f_longitude;
            $data['delivery_id'] = $db->f_delivery_id;
            $data['address_line_1'] = $db->f_address_line_1;
            $data['address_line_2'] = $db->f_address_line_2;
            $data['address_line_3'] = $db->f_address_line_3;
            $data['city'] = $db->f_city;
            $data['state'] = $db->f_state;
            $data['zip'] = $db->f_zip;
            $data['phone'] = $db->f_phone;

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


 //$query1 = "SELECT SUM(DI.qty) AS ITEM_COUNT FROM deliveries D LEFT JOIN delivery_items DI ON D.delivery_id = DI.delivery_id WHERE D.driver_id ='" . $driver_id . "' AND D.status='In Transit' AND D.customer_id='".$db->f_customer_id."'";
            
            //$db1->myCustomQuery($query1);
?>


