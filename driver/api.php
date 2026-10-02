<?php
/*
 * JSON backend of the driver phone page (driver/index.php). The driver comes from the session, never from the request.
 */
include_once(__DIR__ . "/../bootstrap.php");
header("Content-Type: application/json");

function driver_reply($success, $msg, $data = array()) {
	echo json_encode(array("success" => $success ? 1 : 0, "msg" => $msg, "data" => $data));
	exit;
}

$action = isset($_POST['action']) ? (string) $_POST['action'] : "";

if ($action == "login") {
	$username = trim(isset($_POST['username']) ? (string) $_POST['username'] : "");
	$password = isset($_POST['password']) ? (string) $_POST['password'] : "";
	$user = fleet::row("SELECT user_id, first_name, last_name, status, is_driver FROM users
		WHERE (acct_username='" . sanitize($username) . "' OR email_addr='" . sanitize($username) . "') AND acct_password='" . md5($password) . "' LIMIT 1");
	if (!$user || $username == "") {
		driver_reply(false, "Wrong username or password");
	}
	if ($user['is_driver'] != 1 || $user['status'] != 1) {
		driver_reply(false, "This account is not an active driver");
	}
	session_regenerate_id(true);
	$_SESSION["routetrac-driver"] = array("driver_id" => (int) $user['user_id'], "name" => fleet::driver_name($user));
	driver_reply(true, "Welcome " . fleet::driver_name($user));
}

if (!isset($_SESSION["routetrac-driver"]["driver_id"])) {
	header('HTTP/1.1 401 Unauthorized');
	driver_reply(false, "Please log in");
}
$driver_id = (int) $_SESSION["routetrac-driver"]["driver_id"];

// Every trip action works on the driver's own current trip only
$current = fleet::current_trip_for_driver($driver_id);
$trip_id = $current ? (int) $current['trip_id'] : 0;
$post = function ($key) {
	return isset($_POST[$key]) ? (string) $_POST[$key] : "";
};

switch ($action) {
	case "logout":
		unset($_SESSION["routetrac-driver"]);
		driver_reply(true, "Logged out");

	case "trip":
		if (!$current) {
			driver_reply(true, "No trip assigned", array("driver" => $_SESSION["routetrac-driver"]["name"], "trip" => null));
		}
		$trip = fleet::trip($trip_id);
		$expenses = fleet::expense_summary($trip_id);
		$on_board = array();
		foreach (fleet::load_summary($trip_id) as $s) {
			$s['on_board'] = $s['loaded'] - $s['delivered'] + $s['picked_up'];
			$on_board[] = $s;
		}
		$vehicle = fleet::vehicle($trip['vehicle_id']);
		driver_reply(true, "", array(
			"driver" => $_SESSION["routetrac-driver"]["name"],
			"trip" => array(
				"trip_number" => $trip['trip_number'], "trip_date" => $trip['trip_date'], "status" => $trip['status'],
				"route_name" => $trip['route_name'], "registration_no" => $trip['registration_no'], "vehicle_type" => $trip['vehicle_type'],
				"vehicle_odometer" => (int) $vehicle['odometer_km'], "start_odometer" => $trip['start_odometer'], "start_time" => $trip['start_time'],
				"can_start" => in_array($trip['status'], array("Planned", "Loaded")) && $trip['trip_date'] <= date("Y-m-d"),
				"can_complete" => $trip['status'] == "In Transit",
			),
			"progress" => fleet::progress($trip_id),
			"load_totals" => fleet::load_totals($trip_id),
			"load" => $on_board,
			"expenses" => array("toll" => fleet::money($expenses['toll']), "toll_count" => (int) $expenses['toll_count'],
				"tax" => fleet::money($expenses['tax']), "fuel" => fleet::money($expenses['fuel']), "total" => fleet::money($expenses['total'])),
		));

	case "location":
		// The phone sends its whole offline queue: [{"lat","lng","t" (ms, when recorded),"acc" (m)}, ...]
		list($accepted, $rejected, $tagged) = fleet::record_locations($driver_id, $post('points'));
		driver_reply($accepted > 0 || $rejected > 0, $accepted > 0 ? "" : "No valid points",
			array("accepted" => $accepted, "rejected" => $rejected, "trip_id" => $tagged, "time" => date("H:i") . " " . company_settings::tz_abbr()));

	case "start":
		$errors = $trip_id ? fleet::start_trip($trip_id, $post('odometer'), $driver_id, "Driver App", $post('latitude'), $post('longitude')) : array("No trip assigned");
		driver_reply(count($errors) == 0, count($errors) == 0 ? "Trip started. Drive safe!" : implode("\n", $errors));

	case "complete":
		$errors = $trip_id ? fleet::complete_trip($trip_id, $post('odometer'), $driver_id, "Driver App", "", $post('latitude'), $post('longitude')) : array("No trip assigned");
		driver_reply(count($errors) == 0, count($errors) == 0 ? "Trip completed" : implode("\n", $errors));

	case "expense":
		if (!$trip_id) {
			driver_reply(false, "No trip assigned");
		}
		list($expense_id, $errors) = fleet::add_expense($trip_id, $_POST, $driver_id, "Driver App");
		driver_reply(count($errors) == 0, count($errors) == 0 ? "Saved" : implode("\n", $errors));

	case "history":
		// The driver's own finished trips, 10 per page, plus month / all-time totals and open settlements
		$page = max(1, (int) $post('page'));
		$trips = fleet::driver_trips($driver_id, 11, ($page - 1) * 10);
		$more = count($trips) > 10;
		$out = array();
		foreach (array_slice($trips, 0, 10) as $t) {
			$out[] = array("trip_id" => (int) $t['trip_id'], "trip_number" => $t['trip_number'], "date" => $t['trip_date'], "status" => $t['status'],
				"route" => $t['route_name'], "vehicle" => $t['registration_no'], "km" => $t['km'], "stops" => (int) $t['stops'], "delivered" => (int) $t['delivered'],
				"toll" => fleet::money($t['toll']), "tax" => fleet::money($t['tax']), "fuel" => fleet::money($t['fuel']), "expenses" => fleet::money($t['expenses']),
				"start" => $t['start_time'], "end" => $t['end_time'], "settlement" => $t['settlement_text'], "settled" => $t['settlement_status'] == "Settled",
				"note" => $t['close_note']);
		}
		$totals = fleet::driver_totals($driver_id);
		driver_reply(true, "", array("trips" => $out, "more" => $more, "page" => $page, "unit" => fleet::du(),
			"month" => $totals['month'], "all" => $totals['all'], "pending_trips" => $totals['pending_trips'],
			"pending_text" => $totals['pending_balance'] > 0 ? "You hold " . fleet::money($totals['pending_balance']) . " of trip advance to return"
				: ($totals['pending_balance'] < 0 ? "Company owes you " . fleet::money(-$totals['pending_balance']) : "")));

	case "history_trip":
		// One past trip: stops and expenses (read only, own trips only)
		$t = fleet::row("SELECT trip_id FROM trips WHERE trip_id=" . (int) $post('trip_id') . " AND driver_id=" . $driver_id . " AND status IN ('Completed','Cancelled')");
		if (!$t) {
			driver_reply(false, "Trip not found");
		}
		$stops = fleet::rows("SELECT D.stop_number, D.order_number, D.status, D.delivery_time, D.signature_text, D.exception_reason, M.manifest_type, C.customer_name
			FROM deliveries D LEFT JOIN customers C ON C.customer_id=D.customer_id LEFT JOIN manifests M ON M.manifest_id=D.manifest_id
			WHERE D.trip_id=" . (int) $t['trip_id'] . " ORDER BY D.stop_number");
		$expenses = fleet::rows("SELECT expense_type, amount, payment_mode, location, expense_time, receipt_file<>'' AS has_receipt
			FROM trip_expenses WHERE trip_id=" . (int) $t['trip_id'] . " ORDER BY expense_time");
		foreach ($expenses as $i => $e) {
			$expenses[$i]['amount'] = fleet::money($e['amount']);
		}
		$s = fleet::settlement($t['trip_id']);
		driver_reply(true, "", array("stops" => $stops, "expenses" => $expenses,
			"advance" => fleet::money($s['advance']), "driver_paid" => fleet::money($s['driver_paid'])));

	case "profile":
		$d = fleet::driver($driver_id);
		$days = $d['license_expiry'] ? (int) floor((strtotime($d['license_expiry']) - strtotime(date("Y-m-d"))) / 86400) : null;
		driver_reply(true, "", array("name" => fleet::driver_name($d), "phone" => $d['phone'], "license_no" => $d['license_no'],
			"license_classes" => fleet::license_class_list($d['license_classes']), "license_expiry" => $d['license_expiry'],
			"license_warning" => $days === null ? "" : ($days < 0 ? "Your licence expired on " . $d['license_expiry'] . " - you cannot be put on trips"
				: ($days <= 30 ? "Your licence expires in " . $days . " day(s) - renew it soon" : "")),
			"experience_years" => $d['experience_years'], "aadhaar" => fleet::mask_aadhaar($d['aadhaar_no']),
			"documents" => array("license" => $d['license_doc'] != "", "aadhaar" => $d['aadhaar_doc'] != "")));

	default:
		driver_reply(false, "Unknown action");
}
