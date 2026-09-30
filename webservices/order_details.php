<?php
include_once(__DIR__ . '/request_filter.php');


$app_key = $_REQUEST['app_key'];
include_once("../bootstrap.php");
include_once('message.php');

$delivery_id = $_REQUEST['delivery_id'];

$result = array();
$data = array();


if (isset($delivery_id)) {

    $db = new database();
    $db1 = new database();
    $query = "SELECT D.delivery_id,"
            . "D.order_number,"
            . "D.delivery_id,"
            . "D.stop_number,"
            . "D.order_number,"
            . "D.cust_po_num,"
            . "D.delivery_notes ,"
            . "D.driver_notes,"
            . "D.scheduled_delivery,"
            . "D.delivery_time,"
            . "D.signature_text,"
            . "D.status,"
            . "D.latitude,"
            . "D.longitude,"
            . "C.customer_name,"
            . "C.customer_id,"
            . "DI.item_id ,"
            . "DI.sku,"
            . "DI.qty,"
            . "DI.product_name,"
            . "DI.description ,"
            . "DI.uom,"
            . "DI.qty_delivered,"
            . "CA.ship_to_name,"
            . "CA.address_line_1,"
            . "CA.address_line_2,"
            . "CA.address_line_3,"
            . "CA.city,"
            . "CA.state,"
            . "CA.zip,"
            . "CA.phone "
            . "FROM "
            . "deliveries D LEFT JOIN  customers C ON D.customer_id = C.customer_id "
            . "LEFT JOIN customer_addresses CA ON D.address_id =CA.address_id "
            . "JOIN delivery_items DI ON D.delivery_id= DI.delivery_id "
            . "WHERE D.delivery_id ='" . $delivery_id . "'";

    $query1 = "SELECT D.delivery_id,"
            . "D.order_number,"
            . "D.delivery_id,"
            . "D.stop_number,"
            . "D.order_number,"
            . "D.cust_po_num,"
            . "D.delivery_notes ,"
            . "D.driver_notes,"
            . "D.scheduled_delivery,"
            . "D.delivery_time,"
            . "D.signature_text,"
            . "D.status,"
            . "D.latitude,"
            . "D.longitude,"
            . "C.customer_name,"
            . "C.customer_id,"
            . "CA.ship_to_name,"
            . "CA.address_line_1,"
            . "CA.address_line_2,"
            . "CA.address_line_3,"
            . "CA.city,"
            . "CA.state,"
            . "CA.zip,"
            . "CA.phone,"
            . "PT.name AS package_name,"
            . "DP.qty AS package_qty,"
            . "DP.packages_id AS package_id "
            . "FROM "
            . "deliveries D LEFT JOIN  customers C ON D.customer_id = C.customer_id "
            . "LEFT JOIN customer_addresses CA ON D.address_id =CA.address_id "
            . "JOIN delivery_packages DP ON D.delivery_id= DP.delivery_id "
            . "LEFT JOIN package_types PT ON PT.package_type_id= DP.package_type_id "
            . "WHERE D.delivery_id ='" . $delivery_id . "'";


    //         
    $db->myCustomQuery($query);
    if ($db->row_count > 0) {

        for ($i = 0; $i < $db->row_count; $i++) {
            $db->moveto($i);
            $data['order_number'] = $db->f_order_number;
            $data['stop_number'] = $db->f_stop_number;
            $data['cust_po_num'] = $db->f_cust_po_num;
            $data['delivery_notes'] = $db->f_delivery_notes;
            $data['driver_notes'] = $db->f_driver_notes;
            $data['scheduled_delivery'] = $db->f_scheduled_delivery;
            $data['delivery_time'] = $db->f_delivery_time;
            $data['signature_text'] = $db->f_signature_text;
            $data['customer_id'] = $db->f_customer_id;
            $data['customer_name'] = $db->f_customer_name;
            $data['status'] = $db->f_status;
            $data['delivery_id'] = $db->f_delivery_id;
            $data['ship_to_name'] = $db->f_ship_to_name;
            $data['address_line_1'] = $db->f_address_line_1;
            $data['address_line_2'] = $db->f_address_line_2;
            $data['address_line_3'] = $db->f_address_line_3;
            $data['city'] = $db->f_city;
            $data['state'] = $db->f_state;
            $data['zip'] = $db->f_zip;
            $data['phone'] = format_phone($db->f_phone);
            $data['latitude'] = $db->f_latitude;
            $data['longitude'] = $db->f_longitude;


            $data['item_id'] = $db->f_item_id;
            $data['sku'] = $db->f_sku;
            $data['qty'] = $db->f_qty;
            $data['product_name'] = $db->f_product_name;
            $data['description'] = $db->f_description;
            $data['uom'] = $db->f_uom;
            $data['qty_delivered'] = $db->f_qty_delivered;
            $data['type'] = "item";


            array_push($result, $data);
        }
    }
    $db1->myCustomQuery($query1);
    if ($db1->row_count > 0) {

        for ($i = 0; $i < $db1->row_count; $i++) {
            $db1->moveto($i);
            $data1['order_number'] = $db1->f_order_number;
            $data1['stop_number'] = $db1->f_stop_number;
            $data1['cust_po_num'] = $db1->f_cust_po_num;
            $data1['delivery_notes'] = $db1->f_delivery_notes;
            $data1['driver_notes'] = $db1->f_driver_notes;
            $data1['scheduled_delivery'] = $db1->f_scheduled_delivery;
            $data1['delivery_time'] = $db1->f_delivery_time;
            $data1['signature_text'] = $db1->f_signature_text;
            $data1['customer_id'] = $db1->f_customer_id;
            $data1['customer_name'] = $db1->f_customer_name;
            $data1['status'] = $db1->f_status;
            $data1['delivery_id'] = $db1->f_delivery_id;
            $data1['ship_to_name'] = $db1->f_ship_to_name;
            $data1['address_line_1'] = $db1->f_address_line_1;
            $data1['address_line_2'] = $db1->f_address_line_2;
            $data1['address_line_3'] = $db1->f_address_line_3;
            $data1['city'] = $db1->f_city;
            $data1['state'] = $db1->f_state;
            $data1['zip'] = $db1->f_zip;
            $data1['phone'] = format_phone($db1->f_phone);
            $data1['latitude'] = $db->f_latitude;
            $data1['longitude'] = $db->f_longitude;

            $data1['package_name'] = $db1->f_package_name;
            $data1['package_qty'] = $db1->f_package_qty;
            $data1['package_id'] = $db1->f_package_id;
            $data1['type'] = "package";


            array_push($result, $data1);
        }
    }
    $json = array($RESPONSE_KEY => $RESPONSE_SUCCESS, 'msg' => 'Success', 'info' => $result);
    echo json_encode($json);
} else {

    $json = array($RESPONSE_KEY => $RESPONSE_ERROR, 'msg' => 'Failed', 'last_id' => '0');
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


