<?php
include_once(__DIR__ . '/request_filter.php');

$app_key = $_REQUEST['app_key'];

include_once("../bootstrap.php");
include_once('message.php');

// Scanned barcode: matches an item's upc_code or SKU (optionally within one delivery)
$upc_code = trim((string) $_REQUEST['upc_code']);
$delivery_id = (int) $_REQUEST['delivery_id'];
$result = array();
$data = array();
if ($upc_code != "") {

    $db = new database();
    $query = "SELECT item_id,delivery_id,sku,qty,qty_delivered FROM delivery_items WHERE (upc_code='" . sanitize($upc_code) . "' OR sku='" . sanitize($upc_code) . "')";
    if ($delivery_id > 0) {
        $query .= " AND delivery_id=" . $delivery_id;
    }
    $query .= " ORDER BY item_id LIMIT 1";
    $db->myCustomQuery($query);
    if ($db->row_count > 0) {

        $data['upc_status'] = "YES";
        $data['item_id'] = $db->f_item_id;
        $data['delivery_id'] = $db->f_delivery_id;
        $data['sku'] = $db->f_sku;
        $data['qty'] = $db->f_qty;
        $data['qty_delivered'] = $db->f_qty_delivered;
        array_push($result, $data);
        $json = array($RESPONSE_KEY => $RESPONSE_SUCCESS, 'msg' => 'Success', 'info' => $result);
        echo json_encode($json);
    } else {

        $data['upc_status'] = "NO";
        array_push($result, $data);
        $json = array($RESPONSE_KEY => $RESPONSE_SUCCESS, 'msg' => 'Success', 'info' => $result);
        echo json_encode($json);
    }
} else {
    $json = array($RESPONSE_KEY => $RESPONSE_ERROR, 'msg' => 'Please pass all parameter', 'last_id' => '0');
    echo json_encode($json);
}
?>
