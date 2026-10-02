<?php
/*
 * Shared by the trip_* endpoints: loads the trip named by trip_id and makes sure it belongs to driver_id.
 * Sets $trip, or answers with an error and stops.
 */
function trip_reply($key, $msg, $info = array()) {
    global $RESPONSE_KEY;
    echo json_encode(array($RESPONSE_KEY => $key, 'msg' => $msg, 'info' => $info));
    exit;
}

$driver_id = isset($_REQUEST['driver_id']) ? (int) $_REQUEST['driver_id'] : 0;
$trip_id = isset($_REQUEST['trip_id']) ? (int) $_REQUEST['trip_id'] : 0;

if ($driver_id < 1 || $trip_id < 1) {
    trip_reply($RESPONSE_NULL_PARAMETER, 'Please pass all parameter');
}
$trip = fleet::trip($trip_id);
if (!$trip || (int) $trip['driver_id'] != $driver_id) {
    trip_reply($RESPONSE_NOT_EXIST, 'Trip not found for this driver');
}
