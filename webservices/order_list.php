<?php
include_once(__DIR__ . '/request_filter.php');


$app_key = $_REQUEST['app_key'];
include_once("../bootstrap.php");
include_once('message.php');

$customer_id = $_REQUEST['customer_id'];
$driver_id = $_REQUEST['driver_id'];
$route_id = $_REQUEST['route_id'];
$CURDATE = date("Y-m-d");
$result = array();
$data = array();
if (isset($customer_id)) {

    $db = new database();
    //$query = "SELECT D.delivery_id,D.order_number,D.delivery_id,C.customer_name,D.status,COUNT(DI.delivery_id) AS ITEM_COUNT,CA.ship_to_name,CA.address_line_1,CA.address_line_2,CA.address_line_3,CA.city,CA.state,CA.zip,CA.phone  FROM deliveries D LEFT JOIN customers C  ON D.customer_id = C.customer_id LEFT JOIN delivery_items DI ON D.delivery_id = DI.delivery_id  LEFT JOIN customer_addresses CA ON D.address_id =CA.address_id WHERE D.driver_id ='" . $driver_id . "' AND D.status='Pending' GROUP BY D.delivery_id";
    $query = "SELECT D.delivery_id,D.order_number,D.delivery_id,D.delivery_notes,C.customer_name,CA.ship_to_name,CA.address_line_1,CA.address_line_2,CA.address_line_3,CA.city,CA.state,CA.zip,CA.phone,M.manifest_type,D.latitude,D.longitude FROM deliveries D LEFT JOIN customers C ON D.customer_id = C.customer_id LEFT JOIN  manifests M ON M.manifest_id=D.manifest_id  LEFT JOIN customer_addresses CA ON CA.address_id = D.address_id  WHERE D.customer_id= '" . $customer_id . "'AND D.driver_id ='" . $driver_id . "'AND  D.status='In Transit' AND  scheduled_delivery='".$CURDATE."' AND D.route_id ='" . $route_id . "' GROUP BY D.delivery_id";


    //         
    $db->myCustomQuery($query);
    if ($db->row_count > 0) {

        for ($i = 0; $i < $db->row_count; $i++) {
            $db->moveto($i);

            $total_package = 0;
            $db_get_package_count = new database();
            $query_get_package_count = "SELECT SUM(qty) AS TOTAL_PACKAGE FROM  delivery_packages WHERE delivery_id='" . $db->f_delivery_id . "'";
            $db_get_package_count->myCustomQuery($query_get_package_count);
            if ($db_get_package_count->row_count > 0) {
                $db_get_package_count->moveto(0);
                $total_package = $db_get_package_count->f_TOTAL_PACKAGE;
            }

            $data['customer_name'] = $db->f_customer_name;
            $data['order_number'] = $db->f_order_number;
            $data['item_count'] = $total_package;
            $data['delivery_id'] = $db->f_delivery_id;
            $data['manifest_type'] = $db->f_manifest_type;
            $data['latitude'] = $db->f_latitude;
            $data['longitude'] = $db->f_longitude;
            $data['ship_to_name'] = $db->f_ship_to_name;
            $data['delivery_notes'] = $db->f_delivery_notes;
            $data['address_line_1'] = $db->f_address_line_1;
            $data['address_line_2'] = $db->f_address_line_2;
            $data['address_line_3'] = $db->f_address_line_3;
            $data['city'] = $db->f_city;
            $data['state'] = $db->f_state;
            $data['zip'] = $db->f_zip;
            $data['phone'] = format_phone($db->f_phone);


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
function format_phone($var_num) {
    $var_num = trim($var_num);
    $var_num = str_replace("(", "", $var_num);
    $var_num = str_replace(")", "", $var_num);
    $var_num = str_replace("-", "", $var_num);
    $var_num = str_replace(" ", "", $var_num);
    $var_num = str_replace(".", "", $var_num);
    $var_num = substr($var_num, -10);
    $var_area_code = substr($var_num, 0, -7);
    $var_exchange = substr($var_num, 3, -4);
    $var_extention = substr($var_num, -4);
    $var_return = "{$var_area_code}-{$var_exchange}-{$var_extention}";
    return $var_return;
}
?>


