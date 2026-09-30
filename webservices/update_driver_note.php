<?php
include_once(__DIR__ . '/request_filter.php');


$app_key = $_REQUEST['app_key'];
$delivery_id = $_REQUEST['delivery_id'];
$driver_notes = $_REQUEST['driver_notes'];
include_once("../bootstrap.php");
include_once('message.php');

    if (isset($_REQUEST["driver_notes"])) {
        
        //Update driver notes.
        $db_update_sign = new database();
        $db_update_sign->data("driver_notes", $driver_notes);
        $db_update_sign->where("delivery_id=" . $delivery_id);
        $db_update_sign->update("deliveries");


        $json = array($RESPONSE_KEY => $RESPONSE_SUCCESS, 'msg' => 'Note updated successfully', 'info' => $result);
        echo json_encode($json);
    } else {
        $json = array($RESPONSE_KEY => $RESPONSE_ERROR, 'msg' => 'Please select file with valid extension (e.g.jpg, jpeg, png,pdf)', 'last_id' => '');
        echo json_encode($json);
        exit();
    }

?>

