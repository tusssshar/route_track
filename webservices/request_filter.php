<?php
/*
 * Included first by every webservice (before any $_REQUEST value is read).
 * These parameters are concatenated into SQL, several of them unquoted (e.g. "packages_id=" . $id),
 * so reduce them to the characters they can legitimately contain.
 */

// ids: a number or a comma separated list of numbers
foreach (array('driver_id', 'route_id', 'customer_id', 'delivery_id', 'delivery_ids', 'item_id', 'package_id', 'packages_id', 'user_id', 'trip_id') as $filter_param) {
    if (isset($_REQUEST[$filter_param]) && is_string($_REQUEST[$filter_param])) {
        $_REQUEST[$filter_param] = preg_replace('/[^0-9,]/', '', $_REQUEST[$filter_param]);
    }
}

// quantities: numbers or comma separated lists of numbers
foreach (array('qty', 'package_qty', 'item_qty', 'amount', 'odometer', 'odometer_km', 'fuel_litres', 'latitude', 'longitude', 'recorded_at', 'accuracy') as $filter_param) {
    if (isset($_REQUEST[$filter_param]) && is_string($_REQUEST[$filter_param])) {
        $_REQUEST[$filter_param] = preg_replace('/[^0-9,.\-]/', '', $_REQUEST[$filter_param]);
    }
}

// keys and scanned codes
foreach (array('app_key', 'upc_code', 'abendon_status') as $filter_param) {
    if (isset($_REQUEST[$filter_param]) && is_string($_REQUEST[$filter_param])) {
        $_REQUEST[$filter_param] = preg_replace('/[^A-Za-z0-9_\-.]/', '', $_REQUEST[$filter_param]);
    }
}
