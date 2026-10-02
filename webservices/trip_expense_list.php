<?php
include_once(__DIR__ . '/request_filter.php');

$app_key = $_REQUEST['app_key'];
include_once("../bootstrap.php");
include_once('message.php');
include_once('trip_common.php');

// Expenses of the trip plus the allowed expense types / payment modes for the app's dropdowns
$expenses = fleet::rows("SELECT expense_id, expense_type, amount, payment_mode, location, reference_no, fuel_litres, odometer_km,
        expense_time, entered_by, receipt_file
    FROM trip_expenses WHERE trip_id=" . (int) $trip_id . " ORDER BY expense_time, expense_id");
foreach ($expenses as $i => $expense) {
    $expenses[$i]['receipt_url'] = $expense['receipt_file'] != '' ? SITEROOT . '/uploads/expense/' . rawurlencode($expense['receipt_file']) : '';
    unset($expenses[$i]['receipt_file']);
}
$summary = fleet::expense_summary($trip_id);
trip_reply($RESPONSE_SUCCESS, 'Success', array(
    'expenses' => $expenses,
    'toll' => round($summary['toll'], 2),
    'tax' => round($summary['tax'], 2),
    'total' => round($summary['total'], 2),
    'expense_types' => fleet::$EXPENSE_TYPES,
    'payment_modes' => fleet::$PAYMENT_MODES,
));
