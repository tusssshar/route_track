<?php
include_once(__DIR__ . '/request_filter.php');

$app_key = $_REQUEST['app_key'];
include_once("../bootstrap.php");
include_once('message.php');
include_once('trip_common.php');

/*
 * Toll / tax / fuel ... paid on the road.
 * params: driver_id, trip_id, expense_type, amount [, payment_mode, location, reference_no, fuel_litres, odometer_km,
 *         latitude, longitude, expense_time] + optional multipart file "receipt" (jpg, jpeg, png, pdf)
 */
list($expense_id, $errors) = fleet::add_expense($trip_id, $_REQUEST, $driver_id, 'Driver App');
if (count($errors) > 0) {
    trip_reply($RESPONSE_ERROR, implode("\n", $errors));
}
$summary = fleet::expense_summary($trip_id);
trip_reply($RESPONSE_SUCCESS, 'Expense saved', array('expense_id' => $expense_id,
    'toll' => round($summary['toll'], 2), 'tax' => round($summary['tax'], 2), 'total' => round($summary['total'], 2)));
