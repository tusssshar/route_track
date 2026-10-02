<?php
/*
 * JSON for the Live Fleet Map (locate_drivers.php).
 *   mode=live                                  running trips: vehicle, driver, last position, track, closed stops;
 *                                              plus drivers not on a trip who reported in the last 24 h
 *   mode=history&driver_id=N&from=Y-m-d&to=Y-m-d   one driver's GPS track for a date range
 * Times are company time (company_settings).
 */
include_once("../../../../bootstrap.php");
header("Content-Type: application/json");
// Same users as before (Areas menu) plus everyone with Fleet access
if (!fleet::has_access(1) && !fleet::has_access(fleet::FLEET_MENU_ID)) {
	header('HTTP/1.1 403 Forbidden');
	echo json_encode(array("error" => "No access"));
	exit;
}

const MAX_TRACK_POINTS = 400;		// tracks are thinned to keep the page light
const SIGNAL_OK_MINUTES = 5;		// last reading newer than this = live
const SIGNAL_STALE_MINUTES = 30;	// older than this = signal lost

// Every n-th point (always keeping the last) so a long day stays drawable
function thin($points, $max = MAX_TRACK_POINTS) {
	$n = count($points);
	if ($n <= $max) {
		return $points;
	}
	$step = $n / $max;
	$out = array();
	for ($i = 0; $i < $max - 1; $i++) {
		$out[] = $points[(int) floor($i * $step)];
	}
	$out[] = $points[$n - 1];
	return $out;
}

function signal($last_time) {
	if (!$last_time) {
		return array("state" => "none", "minutes" => null);
	}
	$minutes = (int) floor((time() - strtotime($last_time)) / 60);
	return array("state" => $minutes <= SIGNAL_OK_MINUTES ? "live" : ($minutes <= SIGNAL_STALE_MINUTES ? "stale" : "lost"), "minutes" => max(0, $minutes));
}

$mode = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : "live";

if ($mode == "history") {
	$driver_id = (int) (isset($_REQUEST['driver_id']) ? $_REQUEST['driver_id'] : 0);
	$from = isset($_REQUEST['from']) ? (string) $_REQUEST['from'] : "";
	$to = isset($_REQUEST['to']) ? (string) $_REQUEST['to'] : "";
	if ($driver_id < 1 || !fleet::valid_date($from) || !fleet::valid_date($to) || $from > $to) {
		echo json_encode(array("error" => "Select a driver and a valid date range"));
		exit;
	}
	$points = fleet::rows("SELECT L.latitude, L.longitude, L.add_date_time, L.trip_id, T.trip_number FROM driver_location L LEFT JOIN trips T ON T.trip_id=L.trip_id
		WHERE L.driver_id=" . $driver_id . " AND L.add_date_time BETWEEN '" . $from . " 00:00:00' AND '" . $to . " 23:59:59'
		AND (L.accuracy_m IS NULL OR L.accuracy_m<=" . fleet::GPS_GOOD_ACCURACY_M . ") ORDER BY L.add_date_time, L.driver_location_id");
	$trips = fleet::rows("SELECT trip_id, trip_number, trip_date, status, start_time, end_time, start_odometer, end_odometer FROM trips
		WHERE driver_id=" . $driver_id . " AND status<>'Cancelled' AND trip_date BETWEEN '" . $from . "' AND '" . $to . "' ORDER BY trip_date, trip_id");
	$driver = fleet::driver($driver_id);
	echo json_encode(array(
		"driver" => $driver ? fleet::driver_name($driver) : "",
		"count" => count($points),
		"points" => array_map(function ($p) { return array((float) $p['latitude'], (float) $p['longitude'], $p['add_date_time'], $p['trip_number']); }, thin($points, 2000)),
		"trips" => $trips,
	));
	exit;
}

// ---- live
$running = fleet::rows("SELECT T.trip_id, T.trip_number, T.trip_date, T.start_time, T.driver_id, T.vehicle_id, R.name AS route_name,
		V.registration_no, V.vehicle_type, U.first_name, U.last_name, U.phone
	FROM trips T
	LEFT JOIN routes R ON R.route_id=T.route_id
	LEFT JOIN vehicles V ON V.vehicle_id=T.vehicle_id
	LEFT JOIN users U ON U.user_id=T.driver_id
	WHERE T.status='In Transit' ORDER BY V.registration_no");
$vehicles = array();
$on_trip = array();
foreach ($running as $t) {
	$on_trip[] = (int) $t['driver_id'];
	$track = fleet::track_points(array("trip_id" => $t['trip_id'], "driver_id" => $t['driver_id'], "start_time" => $t['start_time'], "end_time" => null));
	$last = count($track) > 0 ? $track[count($track) - 1] : null;
	$stops = fleet::rows("SELECT D.stop_number, D.status, D.order_number, D.delivery_time, D.latitude, D.longitude, C.customer_name
		FROM deliveries D LEFT JOIN customers C ON C.customer_id=D.customer_id WHERE D.trip_id=" . (int) $t['trip_id'] . " ORDER BY D.stop_number");
	$progress = fleet::progress($t['trip_id']);
	$load = fleet::load_totals($t['trip_id']);
	$vehicles[] = array(
		"trip_id" => (int) $t['trip_id'], "trip_number" => $t['trip_number'], "route" => $t['route_name'],
		"registration_no" => $t['registration_no'], "vehicle_type" => $t['vehicle_type'],
		"driver" => fleet::driver_name($t), "phone" => $t['phone'], "started" => $t['start_time'],
		"stops_done" => $progress['delivered'] + $progress['exceptions'], "stops_total" => $progress['total'],
		"on_board" => $load['on_board_units'],
		"last" => $last ? array((float) $last['latitude'], (float) $last['longitude'], $last['add_date_time']) : null,
		"signal" => signal($last ? $last['add_date_time'] : null),
		"track" => array_map(function ($p) { return array((float) $p['latitude'], (float) $p['longitude']); }, thin($track)),
		"stops" => array_values(array_map(function ($s) {
			return array("lat" => (float) $s['latitude'], "lng" => (float) $s['longitude'], "status" => $s['status'], "stop" => (int) $s['stop_number'],
				"customer" => $s['customer_name'], "order" => $s['order_number'], "time" => $s['delivery_time']);
		}, array_filter($stops, function ($s) { return is_numeric($s['latitude']) && is_numeric($s['longitude']); }))),
	);
}

// Drivers not on a running trip who sent a position in the last 24 hours
$others = fleet::rows("SELECT U.user_id, U.first_name, U.last_name, U.phone, L.latitude, L.longitude, L.add_date_time
	FROM users U
	JOIN driver_location L ON L.driver_location_id=(SELECT L2.driver_location_id FROM driver_location L2 WHERE L2.driver_id=U.user_id
		AND (L2.accuracy_m IS NULL OR L2.accuracy_m<=" . fleet::GPS_GOOD_ACCURACY_M . ") ORDER BY L2.add_date_time DESC, L2.driver_location_id DESC LIMIT 1)
	WHERE U.is_driver=1 AND U.status='1'" . (count($on_trip) > 0 ? " AND U.user_id NOT IN (" . implode(",", $on_trip) . ")" : "") . "
	AND L.add_date_time >= '" . date("Y-m-d H:i:s", strtotime("-24 hours")) . "' ORDER BY L.add_date_time DESC");

echo json_encode(array(
	"time" => date("H:i:s") . " " . company_settings::tz_abbr(),
	"vehicles" => $vehicles,
	"drivers" => array_map(function ($d) {
		return array("id" => (int) $d['user_id'], "driver" => fleet::driver_name($d), "phone" => $d['phone'],
			"last" => array((float) $d['latitude'], (float) $d['longitude'], $d['add_date_time']), "signal" => signal($d['add_date_time']));
	}, $others),
));
