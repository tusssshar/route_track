<?php
include_once(__DIR__ . '/request_filter.php');

$app_key = $_REQUEST['app_key'];
include_once("../bootstrap.php");
include_once('message.php');

// The trip the driver should work on now (running first, else the next planned/loaded one) with vehicle, load and expenses
$driver_id = isset($_REQUEST['driver_id']) ? (int) $_REQUEST['driver_id'] : 0;
if ($driver_id < 1) {
    echo json_encode(array($RESPONSE_KEY => $RESPONSE_NULL_PARAMETER, 'msg' => 'Please pass all parameter', 'last_id' => '0'));
    exit;
}
$current = fleet::current_trip_for_driver($driver_id);
if (!$current) {
    echo json_encode(array($RESPONSE_KEY => $RESPONSE_NOT_EXIST, 'msg' => 'No trip assigned', 'last_id' => '0'));
    exit;
}
$trip = fleet::trip($current['trip_id']);
$vehicle = fleet::vehicle($trip['vehicle_id']);
$expenses = fleet::expense_summary($trip['trip_id']);

$info = array(
    'trip_id' => $trip['trip_id'],
    'trip_number' => $trip['trip_number'],
    'trip_date' => $trip['trip_date'],
    'status' => $trip['status'],
    'route_id' => $trip['route_id'],
    'route_name' => $trip['route_name'],
    'helper_name' => $trip['helper_name'],
    'start_odometer' => $trip['start_odometer'],
    'start_time' => $trip['start_time'],
    'vehicle' => array(
        'vehicle_id' => $vehicle['vehicle_id'],
        'registration_no' => $vehicle['registration_no'],
        'vehicle_type' => $vehicle['vehicle_type'],
        'fastag_id' => $vehicle['fastag_id'],
        'odometer_km' => $vehicle['odometer_km'],
        'capacity_units' => $vehicle['capacity_units'],
        'document_warnings' => fleet::document_warnings($vehicle),
    ),
    'progress' => fleet::progress($trip['trip_id']),
    'load_totals' => fleet::load_totals($trip['trip_id']),
    'load' => fleet::load_summary($trip['trip_id']),
    'packages' => fleet::package_summary($trip['trip_id']),
    'expenses' => array(
        'toll' => round($expenses['toll'], 2),
        'toll_count' => $expenses['toll_count'],
        'tax' => round($expenses['tax'], 2),
        'fuel' => round($expenses['fuel'], 2),
        'fuel_litres' => round($expenses['fuel_litres'], 2),
        'total' => round($expenses['total'], 2),
    ),
    'expense_types' => fleet::$EXPENSE_TYPES,
    'payment_modes' => fleet::$PAYMENT_MODES,
    'can_start' => in_array($trip['status'], array('Planned', 'Loaded')) && $trip['trip_date'] <= date('Y-m-d') ? 1 : 0,
    'can_complete' => $trip['status'] == 'In Transit' ? 1 : 0,
);
echo json_encode(array($RESPONSE_KEY => $RESPONSE_SUCCESS, 'msg' => 'Success', 'info' => $info));
