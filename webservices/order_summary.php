<?php
include_once(__DIR__ . '/request_filter.php');

$app_key = $_REQUEST['app_key'];
include_once("../bootstrap.php");
include_once('message.php');

$customer_id = $_REQUEST['customer_id'];
$driver_id = $_REQUEST['driver_id'];
$CURDATE = date("Y-m-d");
$result = array();
$data = array();
if (isset($driver_id)) {

    $db = new database();
   
 $query = "SELECT D.order_number,D.delivery_time,D.delivery_id,D.order_number,D.delivery_id,C.customer_name,M.manifest_type,CA.ship_to_name,CA.address_line_1,CA.address_line_2,CA.address_line_3,CA.city,CA.state,CA.zip,CA.phone FROM deliveries D LEFT JOIN delivery_items DI ON D.delivery_id = DI.delivery_id  LEFT JOIN customers C ON D.customer_id = C.customer_id LEFT JOIN  manifests M ON M.manifest_id=D.manifest_id  LEFT JOIN customer_addresses CA ON C.customer_id=CA.customer_id WHERE  D.driver_id ='" . $driver_id . "'AND  D.status='Delivered'AND DATE_FORMAT(delivery_time,'%Y-%m-%d')='".$CURDATE."' GROUP BY D.delivery_id ORDER BY delivery_time DESC";
            
    $db->myCustomQuery($query);
    if ($db->row_count > 0) {

        for ($i = 0; $i < $db->row_count; $i++) {
            $db->moveto($i);
            $data['customer_name'] = $db->f_customer_name;
            $data['delivery_id'] = $db->f_delivery_id;
            $data['order_number'] = $db->f_order_number;
            $data['manifest_type'] = $db->f_manifest_type;
            $data['delivery_time'] = $db->f_delivery_time;
            $data['ship_to_name'] = $db->f_ship_to_name;
            $data['address_line_1'] = $db->f_address_line_1;
            $data['address_line_2'] = $db->f_address_line_2;
            $data['address_line_3'] = $db->f_address_line_3;
            $data['city'] = $db->f_city;
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
?>


