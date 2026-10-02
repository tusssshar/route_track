<?php
	include_once("../../../../bootstrap.php");
	fleet::require_access();

	$action = isset($_POST['action']) ? (string) $_POST['action'] : "";
	$trip_id = (int) (isset($_POST['trip_id']) ? $_POST['trip_id'] : 0);
	$user_id = (int) $_SESSION["routetrac-admin"]["al_id"];
	$post = function ($key) {
		return isset($_POST[$key]) ? (string) $_POST[$key] : "";
	};

	switch ($action) {
		case "preview":
			// Load + rule check for the plan/assign form
			$route_id = (int) $post('route_id');
			$trip_date = $post('trip_date');
			if ($trip_id > 0 && ($trip = fleet::trip($trip_id))) {
				$route_id = $trip['route_id'];
				$trip_date = $trip['trip_date'];
			}
			$orders = fleet::valid_date($trip_date) ? count(fleet::rows(fleet::attachable_orders_sql($route_id, $trip_date, $trip_id))) : 0;
			$units = fleet::valid_date($trip_date) ? fleet::outbound_units($route_id, $trip_date, $trip_id) : 0;
			$vehicle = fleet::vehicle($post('vehicle_id'));
			$errors = array();
			if ($post('vehicle_id') != "" && $post('driver_id') != "") {
				$errors = fleet::validate_assignment($trip_date, $route_id, $post('vehicle_id'), $post('driver_id'), $trip_id);
			} elseif ($orders == 0) {
				$errors[] = "No orders are scheduled on this route for " . $trip_date;
			}
			$warnings = $vehicle ? fleet::document_warnings($vehicle) : array();
			$capacity = $vehicle ? (int) $vehicle['capacity_units'] : 0;
			echo json_encode(array("orders" => $orders, "units" => $units, "capacity_units" => $capacity,
				"utilisation" => $capacity > 0 ? round($units * 100 / $capacity) : 0,
				"errors" => array_map(array('fleet', 'h'), $errors), "warnings" => array_map(array('fleet', 'h'), $warnings)));
			exit;

		case "loaded":
			$errors = fleet::mark_loaded($trip_id, $user_id);
			$msg = "Trip marked as loaded";
			break;

		case "refresh_orders":
			$trip = fleet::trip($trip_id);
			if ($trip && in_array($trip['status'], array("Planned", "Loaded"))) {
				$count = fleet::attach_orders($trip_id);
				$errors = array();
				$msg = $count . " orders on this trip";
			} else {
				$errors = array("Orders can be refreshed only before the trip starts");
			}
			break;

		case "start":
			$errors = fleet::start_trip($trip_id, $post('odometer'), $user_id, "Admin");
			$msg = "Trip started - vehicle is now On Trip";
			break;

		case "complete":
			$errors = fleet::complete_trip($trip_id, $post('odometer'), $user_id, "Admin", $post('force_note'));
			$msg = "Trip completed - vehicle is available again";
			break;

		case "cancel":
			$errors = fleet::cancel_trip($trip_id, $post('reason'), $user_id);
			$msg = "Trip cancelled - its orders are free to be planned again";
			break;

		case "add_expense":
			list($expense_id, $errors) = fleet::add_expense($trip_id, $_POST, $user_id, "Admin");
			$msg = "Expense recorded";
			break;

		case "delete_expense":
			$expense_id = (int) $post('expense_id');
			$owned = fleet::row("SELECT expense_id FROM trip_expenses WHERE expense_id=" . $expense_id . " AND trip_id=" . $trip_id);
			$errors = ($owned && fleet::delete_expense($expense_id)) ? array() : array("Expense not found, or the trip is settled");
			if (count($errors) == 0) {
				fleet::log_event($trip_id, "Expense Deleted", "Expense #" . $expense_id, $user_id);
			}
			$msg = "Expense deleted";
			break;

		case "set_advance":
			$errors = fleet::set_advance($trip_id, $post('cash_advance'), $user_id);
			$msg = "Cash advance saved";
			break;

		case "settle":
			$errors = fleet::settle_trip($trip_id, $user_id, $post('settlement_note'));
			$msg = "Trip sheet approved - trip settled";
			break;

		default:
			$errors = array("Unknown action");
	}

	echo json_encode(array("success" => count($errors) == 0 ? 1 : 0, "error" => implode("<br>", array_map(array('fleet', 'h'), $errors)), "msg" => isset($msg) ? $msg : ""));
?>
