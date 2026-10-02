<?php
include_once(__DIR__ . '/request_filter.php');


$app_key = $_REQUEST['app_key'];

include_once("../bootstrap.php");
include_once('message.php');

/*
 * params: driver_id + either
 *   latitude, longitude [, recorded_at (unix s or ms, when the phone took the reading), accuracy (m)]
 *   or points = JSON [{"lat":..,"lng":..,"t":ms,"acc":m}, ...]   (readings queued while offline)
 * Points are tagged with the trip the driver was running at that moment and stored in company time.
 */
$driver_id = isset($_REQUEST['driver_id']) ? $_REQUEST['driver_id'] : '';
if ($driver_id == '' || (!isset($_REQUEST['points']) && (!isset($_REQUEST['latitude']) || $_REQUEST['latitude'] === '' || !isset($_REQUEST['longitude']) || $_REQUEST['longitude'] === ''))) {
    echo json_encode(array($RESPONSE_KEY => $RESPONSE_ERROR, 'msg' => 'Please pass all parameter', 'last_id' => '0'));
    exit;
}

if (isset($_REQUEST['points'])) {
    list($accepted, $rejected, $trip_id) = fleet::record_locations($driver_id, $_REQUEST['points']);
    $ok = $accepted > 0;
    $info = array('trip_id' => (string) $trip_id, 'accepted' => $accepted, 'rejected' => $rejected);
    $msg = $ok ? 'Success' : 'No valid points';
} else {
    $result = fleet::record_location($driver_id, $_REQUEST['latitude'], $_REQUEST['longitude'],
        isset($_REQUEST['recorded_at']) ? $_REQUEST['recorded_at'] : null, isset($_REQUEST['accuracy']) ? $_REQUEST['accuracy'] : null);
    $ok = is_int($result);
    $info = array('trip_id' => $ok ? (string) $result : '0');
    $msg = $ok ? 'Success' : 'Location refused: ' . $result;
}
echo json_encode(array($RESPONSE_KEY => $ok ? $RESPONSE_SUCCESS : $RESPONSE_ERROR, 'msg' => $msg, 'info' => $info,
    'server_time' => date('Y-m-d H:i:s'), 'timezone' => company_settings::get('timezone')));
?>
