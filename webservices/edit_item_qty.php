<?php
include_once(__DIR__ . '/request_filter.php');

$app_key = $_REQUEST['app_key'];
include_once("../bootstrap.php");
include_once('message.php');

$item_id = $_REQUEST['item_id'];

$result = array();
$data = array();
if (isset($item_id)) {

    $db = new database();
    $query = "SELECT item_id,sku,qty,product_name,description,uom FROM delivery_items WHERE item_id='" . $item_id . "'";

    $db->myCustomQuery($query);
    if ($db->row_count > 0) {

        for ($i = 0; $i < $db->row_count; $i++) {
            $db->moveto($i);
            $data['item_id'] = $db->f_item_id;
            $data['sku'] = $db->f_sku;
            $data['qty'] = $db->f_qty;
            $data['product_name'] = $db->f_product_name;
            $data['description'] = $db->f_description;
            $data['uom'] = $db->f_uom;
            
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


