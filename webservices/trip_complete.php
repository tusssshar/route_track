<?php
include_once(__DIR__ . '/request_filter.php');

$app_key = $_REQUEST['app_key'];
include_once("../bootstrap.php");
include_once('message.php');
include_once('trip_common.php');

// Driver back at the depot: params driver_id, trip_id, odometer [, latitude, longitude]. All stops must be finished.
$errors = fleet::complete_trip($trip_id, isset($_REQUEST['odometer']) ? $_REQUEST['odometer'] : '', $driver_id, 'Driver App', '',
    isset($_REQUEST['latitude']) ? $_REQUEST['latitude'] : '', isset($_REQUEST['longitude']) ? $_REQUEST['longitude'] : '');
if (count($errors) > 0) {
    trip_reply($RESPONSE_ERROR, implode("\n", $errors));
}
$trip = fleet::trip($trip_id);
trip_reply($RESPONSE_SUCCESS, 'Trip completed', array('trip_id' => $trip_id, 'status' => 'Completed',
    'distance_km' => (int) $trip['end_odometer'] - (int) $trip['start_odometer']));
