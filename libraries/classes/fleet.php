<?php
/*
 * Fleet business rules shared by the admin panel (admin/m/fleet) and the driver app (webservices/trip_*).
 *
 * Trip = one vehicle + one driver running one route on one date.
 *   Planned -> Loaded -> In Transit -> Completed
 *   Planned / Loaded -> Cancelled
 * The route's orders (deliveries) for that date are attached to the trip (deliveries.trip_id) and handed to the
 * trip's driver (deliveries.driver_id), which is what the driver app lists.
 */
class fleet {

	public static $VEHICLE_TYPES = array("Two Wheeler", "Three Wheeler", "Van", "Pickup", "Mini Truck", "Truck", "Container", "Trailer");
	public static $FUEL_TYPES = array("Diesel", "Petrol", "CNG", "LPG", "Electric", "Hybrid");
	public static $VEHICLE_STATUSES = array("Available", "On Trip", "Maintenance", "Inactive");

	// Compliance documents a vehicle must hold to be dispatched (column => label)
	public static $VEHICLE_DOCUMENTS = array(
		"insurance_expiry" => "Insurance",
		"fitness_expiry" => "Fitness Certificate",
		"permit_expiry" => "Permit",
		"pollution_expiry" => "Pollution (PUC)",
	);

	// Driving licence classes (Indian Motor Vehicles Act categories)
	public static $LICENSE_CLASSES = array(
		"MCWG" => "MCWG - Motorcycle with gear",
		"LMV" => "LMV - Light motor vehicle (private)",
		"LMV-TR" => "LMV-TR - Light goods / transport vehicle",
		"HMV" => "HMV / HGMV - Heavy goods & transport vehicle",
	);
	// Licence class(es) that allow driving each vehicle type (any one is enough)
	public static $VEHICLE_LICENSE = array(
		"Two Wheeler" => array("MCWG"), "Three Wheeler" => array("LMV-TR", "HMV"), "Van" => array("LMV-TR", "HMV"),
		"Pickup" => array("LMV-TR", "HMV"), "Mini Truck" => array("LMV-TR", "HMV"),
		"Truck" => array("HMV"), "Container" => array("HMV"), "Trailer" => array("HMV"),
	);
	public static $DRIVER_DOC_EXTENSIONS = array("jpg", "jpeg", "png", "webp", "pdf");
	const DRIVER_DOC_MAX_BYTES = 3145728;

	public static $TRIP_STATUSES = array("Planned", "Loaded", "In Transit", "Completed", "Cancelled");
	public static $ACTIVE_TRIP_STATUSES = array("Planned", "Loaded", "In Transit");

	// Order statuses that mean the stop is finished (set by upload_signature / get_order_exception_reason_and_note)
	public static $CLOSED_ORDER_STATUSES = array("Delivered", "Refused", "Exception");

	public static $EXPENSE_TYPES = array("Toll", "State Entry / Border Tax", "Permit Tax", "Green / Municipal Tax", "Fuel",
		"Parking", "Loading / Unloading", "Challan / Fine", "Repair", "Driver Allowance", "Other");
	// Expense types reported as "Tax" (Toll is reported on its own)
	public static $TAX_EXPENSE_TYPES = array("State Entry / Border Tax", "Permit Tax", "Green / Municipal Tax");
	public static $PAYMENT_MODES = array("Cash", "FASTag", "UPI", "Card", "Company Account");
	// Paid by the driver out of the trip advance / own pocket (the rest is paid by the company directly)
	public static $DRIVER_PAID_MODES = array("Cash", "UPI");

	public static $RECEIPT_EXTENSIONS = array("jpg", "jpeg", "png", "pdf");
	const RECEIPT_MAX_BYTES = 5242880;
	const DOCUMENT_WARNING_DAYS = 30;
	const FLEET_MENU_ID = 10;

	//------------------------------------------------------------------------------------------------------------------
	// small data helpers

	static function rows($query) {
		$db = new database();
		$db->myCustomQuery($query);
		$rows = array();
		if ($db->row_count > 0) {
			$db->result->data_seek(0);
			while ($row = $db->result->fetch_assoc()) {
				$rows[] = $row;
			}
		}
		return $rows;
	}

	static function row($query) {
		$rows = self::rows($query);
		return count($rows) > 0 ? $rows[0] : null;
	}

	static function exec($query) {
		$db = new database();
		$db->myCustomQuery($query, 1);
		return $db;
	}

	// $input[$key] as a trimmed string ('' when missing)
	static function input($input, $key) {
		return isset($input[$key]) && !is_array($input[$key]) ? trim((string) $input[$key]) : "";
	}

	static function in_list($values) {
		$quoted = array();
		foreach ($values as $value) {
			$quoted[] = "'" . sanitize($value) . "'";
		}
		return implode(",", $quoted);
	}

	// 'Y-m-d' (or empty) -> SQL literal
	static function date_sql($value) {
		$value = trim((string) $value);
		return self::valid_date($value) ? "'" . $value . "'" : "NULL";
	}

	static function valid_date($value) {
		$d = DateTime::createFromFormat('Y-m-d', (string) $value);
		return $d && $d->format('Y-m-d') === $value;
	}

	// Amount with the company currency symbol (company_settings)
	static function money($amount) {
		return company_settings::money($amount);
	}

	// Company distance unit (km / mi) and fuel unit (L / gal) labels
	static function du() {
		return company_settings::distance_unit();
	}

	static function fu() {
		return company_settings::fuel_unit();
	}

	static function h($value) {
		return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
	}

	static function options($values, $selected = "") {
		$html = "";
		foreach ($values as $value) {
			$html .= "<option value='" . self::h($value) . "'" . ((string) $value === (string) $selected ? " selected='selected'" : "") . ">" . self::h($value) . "</option>";
		}
		return $html;
	}

	// Prev / next pager for AJAX lists; $js_function(page) reloads the list
	static function pager($page, $total_rows, $page_limit, $js_function = "loadList") {
		$pages = max(1, (int) ceil($total_rows / $page_limit));
		$html = "<span style='line-height:34px'>" . (int) $total_rows . " record(s) &nbsp; Page " . (int) $page . " of " . $pages . "</span> ";
		if ($page > 1) {
			$html .= "<a class='btn btn-default btn-sm' href='javascript:void(0);' onclick='" . $js_function . "(" . ($page - 1) . ")'>&laquo; Prev</a> ";
		}
		if ($page < $pages) {
			$html .= "<a class='btn btn-default btn-sm' href='javascript:void(0);' onclick='" . $js_function . "(" . ($page + 1) . ")'>Next &raquo;</a>";
		}
		return $html;
	}

	// Menu permission check for AJAX handlers (pages use $functions->user_access_level after the header switched DBs)
	static function has_access($menu_id = self::FLEET_MENU_ID) {
		if (!isset($_SESSION["routetrac-admin"]["access_level"])) {
			return false;
		}
		$master = @new mysqli(HST_1, USR_1, PWD_1, DBN_1);
		if ($master->connect_errno) {
			return false;
		}
		$res = $master->query("SELECT access_menu_id FROM access_levels WHERE level_id=" . (int) $_SESSION["routetrac-admin"]["access_level"]);
		$row = $res ? $res->fetch_assoc() : null;
		$master->close();
		return $row && in_array((string) $menu_id, explode(",", $row['access_menu_id']), true);
	}

	static function require_access($menu_id = self::FLEET_MENU_ID) {
		if (!self::has_access($menu_id)) {
			header('HTTP/1.1 403 Forbidden');
			echo json_encode(array("success" => 0, "error" => "You do not have access to Fleet"));
			exit;
		}
	}

	//------------------------------------------------------------------------------------------------------------------
	// vehicles / drivers

	static function vehicle($vehicle_id) {
		return self::row("SELECT * FROM vehicles WHERE vehicle_id=" . (int) $vehicle_id);
	}

	static function driver($driver_id) {
		return self::row("SELECT user_id, first_name, last_name, acct_username, status, is_driver, license_no, license_expiry, phone,
				license_classes, experience_years, aadhaar_no, license_doc, aadhaar_doc
			FROM users WHERE user_id=" . (int) $driver_id);
	}

	static function license_class_list($value) {
		return array_values(array_filter(array_map("trim", explode(",", (string) $value))));
	}

	// '' = the driver's licence allows this vehicle type (or no class is recorded yet), otherwise the reason
	static function license_problem($driver, $vehicle_type) {
		$have = self::license_class_list($driver['license_classes']);
		if (count($have) == 0 || !isset(self::$VEHICLE_LICENSE[$vehicle_type])) {
			return "";
		}
		$need = self::$VEHICLE_LICENSE[$vehicle_type];
		return count(array_intersect($have, $need)) > 0 ? ""
			: "Driver " . self::driver_name($driver) . " holds " . implode(", ", $have) . " but a " . $vehicle_type . " needs " . implode(" or ", $need);
	}

	// Aadhaar: 12 digits, does not start with 0 or 1, last digit = Verhoeff checksum
	static function valid_aadhaar($number) {
		if (!preg_match('/^[2-9][0-9]{11}$/', (string) $number)) {
			return false;
		}
		$d = array(array(0,1,2,3,4,5,6,7,8,9), array(1,2,3,4,0,6,7,8,9,5), array(2,3,4,0,1,7,8,9,5,6), array(3,4,0,1,2,8,9,5,6,7),
			array(4,0,1,2,3,9,5,6,7,8), array(5,9,8,7,6,0,4,3,2,1), array(6,5,9,8,7,1,0,4,3,2), array(7,6,5,9,8,2,1,0,4,3),
			array(8,7,6,5,9,3,2,1,0,4), array(9,8,7,6,5,4,3,2,1,0));
		$p = array(array(0,1,2,3,4,5,6,7,8,9), array(1,5,7,6,2,8,3,0,9,4), array(5,8,0,3,7,9,6,1,4,2), array(8,9,1,6,0,4,3,5,2,7),
			array(9,4,5,3,1,2,6,8,7,0), array(4,2,8,6,5,7,3,9,0,1), array(2,7,9,3,8,0,6,4,1,5), array(7,0,4,6,9,1,3,2,5,8));
		$c = 0;
		foreach (array_reverse(str_split((string) $number)) as $i => $digit) {
			$c = $d[$c][$p[$i % 8][(int) $digit]];
		}
		return $c === 0;
	}

	// Shown everywhere instead of the full number (UIDAI masked format)
	static function mask_aadhaar($number) {
		return strlen((string) $number) == 12 ? "XXXX XXXX " . substr($number, 8) : "";
	}

	/*
	 * Store a scanned licence / Aadhaar copy (jpg, png, webp or pdf up to 3 MB, checked by content) under uploads/driver_docs,
	 * which the web server never serves directly. Returns array(file, error).
	 */
	static function save_driver_doc($field, $user_id, $type) {
		if (empty($_FILES[$field]) || $_FILES[$field]['error'] == UPLOAD_ERR_NO_FILE) {
			return array("", "");
		}
		$f = $_FILES[$field];
		$label = $type == "aadhaar" ? "Aadhaar copy" : "Licence copy";
		if ($f['error'] != UPLOAD_ERR_OK) {
			return array("", $label . " upload failed (code " . $f['error'] . ")");
		}
		if ($f['size'] > self::DRIVER_DOC_MAX_BYTES) {
			return array("", $label . " must be smaller than 3 MB");
		}
		$head = (string) file_get_contents($f['tmp_name'], false, null, 0, 5);
		$info = @getimagesize($f['tmp_name']);
		$types = array(IMAGETYPE_JPEG => "jpg", IMAGETYPE_PNG => "png", IMAGETYPE_WEBP => "webp");
		if ($head === "%PDF-") {
			$ext = "pdf";
		} elseif ($info && isset($types[$info[2]])) {
			$ext = $types[$info[2]];
		} else {
			return array("", $label . " must be a JPG, PNG, WEBP or PDF file");
		}
		$dir = ROOTPATH . "/uploads/driver_docs";
		if (!is_dir($dir)) {
			mkdir($dir, 0755, true);
		}
		if (!is_file($dir . "/.htaccess")) {	// KYC copies must never be served directly
			file_put_contents($dir . "/.htaccess", "Require all denied
");
		}
		$file = $type . "_" . (int) $user_id . "_" . date("YmdHis") . "_" . mt_rand(1000, 9999) . "." . $ext;
		if (!move_uploaded_file($f['tmp_name'], $dir . "/" . $file)) {
			return array("", "Could not store the " . strtolower($label));
		}
		return array($file, "");
	}

	static function driver_name($row) {
		return trim($row['first_name'] . " " . $row['last_name']);
	}

	// Documents already expired on $on_date
	static function expired_documents($vehicle, $on_date) {
		$expired = array();
		foreach (self::$VEHICLE_DOCUMENTS as $column => $label) {
			if (!empty($vehicle[$column]) && $vehicle[$column] < $on_date) {
				$expired[] = $label . " (expired " . $vehicle[$column] . ")";
			}
		}
		return $expired;
	}

	// Documents missing or expiring within $days of today
	static function document_warnings($vehicle, $days = self::DOCUMENT_WARNING_DAYS) {
		$warnings = array();
		$today = date("Y-m-d");
		$limit = date("Y-m-d", strtotime("+" . (int) $days . " days"));
		foreach (self::$VEHICLE_DOCUMENTS as $column => $label) {
			if (empty($vehicle[$column])) {
				$warnings[] = $label . " date not recorded";
			} elseif ($vehicle[$column] < $today) {
				$warnings[] = $label . " expired " . $vehicle[$column];
			} elseif ($vehicle[$column] <= $limit) {
				$warnings[] = $label . " expires " . $vehicle[$column];
			}
		}
		return $warnings;
	}

	// The one trip that currently "owns" the vehicle (In Transit first, then the earliest Planned/Loaded)
	static function current_trip_for_vehicle($vehicle_id) {
		return self::row("SELECT * FROM trips WHERE vehicle_id=" . (int) $vehicle_id . " AND status IN (" . self::in_list(self::$ACTIVE_TRIP_STATUSES) . ")
			ORDER BY (status='In Transit') DESC, trip_date ASC, trip_id ASC LIMIT 1");
	}

	// Driver app: the trip the driver should be working on now
	static function current_trip_for_driver($driver_id) {
		return self::row("SELECT * FROM trips WHERE driver_id=" . (int) $driver_id . " AND status IN (" . self::in_list(self::$ACTIVE_TRIP_STATUSES) . ")
			ORDER BY (status='In Transit') DESC, trip_date ASC, trip_id ASC LIMIT 1");
	}

	//------------------------------------------------------------------------------------------------------------------
	// trips

	static function trip($trip_id) {
		return self::row("SELECT T.*, R.name AS route_name, V.registration_no, V.vehicle_type, V.capacity_units, V.capacity_kg,
				U.first_name, U.last_name, U.phone AS driver_phone
			FROM trips T
			LEFT JOIN routes R ON R.route_id=T.route_id
			LEFT JOIN vehicles V ON V.vehicle_id=T.vehicle_id
			LEFT JOIN users U ON U.user_id=T.driver_id
			WHERE T.trip_id=" . (int) $trip_id);
	}

	static function next_trip_number($trip_date) {
		$prefix = "TRP-" . str_replace("-", "", $trip_date) . "-";
		$last = self::row("SELECT trip_number FROM trips WHERE trip_number LIKE '" . sanitize($prefix) . "%' ORDER BY trip_number DESC LIMIT 1");
		$next = $last ? ((int) substr($last['trip_number'], strlen($prefix)) + 1) : 1;
		return $prefix . str_pad($next, 4, "0", STR_PAD_LEFT);
	}

	static function log_event($trip_id, $event_type, $note = "", $user_id = 0, $source = "Admin", $latitude = "", $longitude = "") {
		$db = new database();
		$db->data("trip_id", (int) $trip_id);
		$db->data("event_type", $event_type);
		$db->data("note", $note);
		$db->data("user_id", (int) $user_id);
		$db->data("source", $source);
		$db->data("latitude", $latitude);
		$db->data("longitude", $longitude);
		$db->data("event_time", date("Y-m-d H:i:s"));
		$db->insert("trip_events");
	}

	// Orders of the route/date that this trip can carry: unassigned, already on this trip, or left over from a cancelled trip
	static function attachable_orders_sql($route_id, $trip_date, $trip_id = 0) {
		return "SELECT D.delivery_id FROM deliveries D LEFT JOIN trips T ON T.trip_id=D.trip_id
			WHERE D.route_id=" . (int) $route_id . " AND D.scheduled_delivery='" . sanitize($trip_date) . "'
			AND (D.trip_id=0 OR D.trip_id=" . (int) $trip_id . " OR T.trip_id IS NULL OR T.status='Cancelled')";
	}

	// Item units the vehicle leaves the depot with (delivery orders; pickups are collected on the way)
	static function outbound_units($route_id, $trip_date, $trip_id = 0) {
		$row = self::row("SELECT COALESCE(SUM(DI.qty),0) AS units FROM delivery_items DI
			JOIN deliveries D ON D.delivery_id=DI.delivery_id
			LEFT JOIN manifests M ON M.manifest_id=D.manifest_id
			WHERE D.delivery_id IN (" . self::attachable_orders_sql($route_id, $trip_date, $trip_id) . ")
			AND (M.manifest_type='Delivery' OR M.manifest_type IS NULL)");
		return (int) $row['units'];
	}

	/*
	 * Rules for putting a vehicle + driver on a route for a date. Returns a list of error messages (empty = OK).
	 */
	static function validate_assignment($trip_date, $route_id, $vehicle_id, $driver_id, $trip_id = 0) {
		$errors = array();
		$trip_id = (int) $trip_id;
		$active = self::in_list(self::$ACTIVE_TRIP_STATUSES);

		if (!self::valid_date($trip_date)) {
			$errors[] = "Please select a valid trip date";
		} elseif ($trip_id < 1 && $trip_date < date("Y-m-d")) {
			$errors[] = "Trip date cannot be in the past";
		}
		if (!self::row("SELECT route_id FROM routes WHERE route_id=" . (int) $route_id)) {
			$errors[] = "Please select a route";
		}

		$vehicle = self::vehicle($vehicle_id);
		if (!$vehicle) {
			$errors[] = "Please select a vehicle";
		} else {
			if ($vehicle['status'] == "Maintenance" || $vehicle['status'] == "Inactive") {
				$errors[] = "Vehicle " . $vehicle['registration_no'] . " is " . $vehicle['status'] . " and cannot be dispatched";
			}
			if (self::valid_date($trip_date)) {
				$expired = self::expired_documents($vehicle, $trip_date);
				if (count($expired) > 0) {
					$errors[] = "Vehicle " . $vehicle['registration_no'] . " documents not valid on " . $trip_date . ": " . implode(", ", $expired);
				}
			}
			$clash = self::row("SELECT trip_number, status, trip_date FROM trips WHERE vehicle_id=" . (int) $vehicle_id . " AND trip_id<>" . $trip_id . "
				AND status IN (" . $active . ") AND (trip_date='" . sanitize($trip_date) . "' OR status='In Transit') LIMIT 1");
			if ($clash) {
				$errors[] = "Vehicle " . $vehicle['registration_no'] . " is already on trip " . $clash['trip_number'] . " (" . $clash['status'] . ", " . $clash['trip_date'] . ")";
			}
		}

		$driver = self::driver($driver_id);
		if (!$driver || $driver['is_driver'] != 1) {
			$errors[] = "Please select a driver";
		} else {
			if ($driver['status'] != 1) {
				$errors[] = "Driver " . self::driver_name($driver) . " is inactive";
			}
			if (!empty($driver['license_expiry']) && self::valid_date($trip_date) && $driver['license_expiry'] < $trip_date) {
				$errors[] = "Driving licence of " . self::driver_name($driver) . " expired on " . $driver['license_expiry'];
			}
			if ($vehicle && ($why = self::license_problem($driver, $vehicle['vehicle_type'])) != "") {
				$errors[] = $why;
			}
			$clash = self::row("SELECT trip_number, status, trip_date FROM trips WHERE driver_id=" . (int) $driver_id . " AND trip_id<>" . $trip_id . "
				AND status IN (" . $active . ") AND (trip_date='" . sanitize($trip_date) . "' OR status='In Transit') LIMIT 1");
			if ($clash) {
				$errors[] = "Driver " . self::driver_name($driver) . " is already on trip " . $clash['trip_number'] . " (" . $clash['status'] . ", " . $clash['trip_date'] . ")";
			}
		}

		if (count($errors) == 0) {
			$clash = self::row("SELECT trip_number FROM trips WHERE route_id=" . (int) $route_id . " AND trip_date='" . sanitize($trip_date) . "'
				AND trip_id<>" . $trip_id . " AND status IN (" . $active . ") LIMIT 1");
			if ($clash) {
				$errors[] = "This route already has trip " . $clash['trip_number'] . " on " . $trip_date . " - reassign that trip instead";
			}

			$orders = self::rows(self::attachable_orders_sql($route_id, $trip_date, $trip_id));
			if (count($orders) == 0) {
				$errors[] = "No orders are scheduled on this route for " . $trip_date . " - create the manifest first";
			}

			$units = self::outbound_units($route_id, $trip_date, $trip_id);
			if ($vehicle['capacity_units'] > 0 && $units > $vehicle['capacity_units']) {
				$errors[] = "Overloaded: " . $units . " units to load but " . $vehicle['registration_no'] . " carries max " . $vehicle['capacity_units'] . " units";
			}
		}
		return $errors;
	}

	// Load the route's orders on the trip and hand them to the trip's driver. Returns number of orders on the trip.
	static function attach_orders($trip_id) {
		$trip = self::trip($trip_id);
		if (!$trip) {
			return 0;
		}
		$ids = array();
		foreach (self::rows(self::attachable_orders_sql($trip['route_id'], $trip['trip_date'], $trip_id)) as $order) {
			$ids[] = (int) $order['delivery_id'];
		}
		if (count($ids) > 0) {
			self::exec("UPDATE deliveries SET trip_id=" . (int) $trip_id . ", driver_id=" . (int) $trip['driver_id'] . " WHERE delivery_id IN (" . implode(",", $ids) . ")");
		}
		return count($ids);
	}

	static function create_trip($input, $user_id) {
		$trip_date = self::input($input, 'trip_date');
		$errors = self::validate_assignment($trip_date, self::input($input, 'route_id'), self::input($input, 'vehicle_id'), self::input($input, 'driver_id'));
		if (count($errors) > 0) {
			return array(0, $errors);
		}
		$db = new database();
		$db->data("trip_number", self::next_trip_number($trip_date));
		$db->data("trip_date", $trip_date);
		$db->data("route_id", (int) self::input($input, 'route_id'));
		$db->data("vehicle_id", (int) self::input($input, 'vehicle_id'));
		$db->data("driver_id", (int) self::input($input, 'driver_id'));
		$db->data("helper_name", self::input($input, 'helper_name'));
		$db->data("notes", self::input($input, 'notes'));
		$db->data("status", "Planned");
		$db->data("created_by", (int) $user_id);
		$db->data("created_date", date("Y-m-d H:i:s"));
		$db->insert("trips");
		$trip_id = $db->id;

		$orders = self::attach_orders($trip_id);
		$trip = self::trip($trip_id);
		self::log_event($trip_id, "Created", "Vehicle " . $trip['registration_no'] . ", driver " . self::driver_name($trip) . ", " . $orders . " orders", $user_id);
		return array($trip_id, array());
	}

	// Change vehicle / driver / helper before the vehicle leaves the depot
	static function update_trip($trip_id, $input, $user_id) {
		$trip = self::trip($trip_id);
		if (!$trip) {
			return array("Trip not found");
		}
		$editable = in_array($trip['status'], array("Planned", "Loaded"));
		$vehicle_id = $editable ? (int) self::input($input, 'vehicle_id') : (int) $trip['vehicle_id'];
		$driver_id = $editable ? (int) self::input($input, 'driver_id') : (int) $trip['driver_id'];

		if ($editable) {
			$errors = self::validate_assignment($trip['trip_date'], $trip['route_id'], $vehicle_id, $driver_id, $trip_id);
			if (count($errors) > 0) {
				return $errors;
			}
		}
		$db = new database();
		$db->data("vehicle_id", $vehicle_id);
		$db->data("driver_id", $driver_id);
		$db->data("helper_name", self::input($input, 'helper_name'));
		$db->data("notes", self::input($input, 'notes'));
		$db->where("trip_id=" . (int) $trip_id);
		$db->update("trips");

		$changes = array();
		if ($vehicle_id != $trip['vehicle_id']) {
			$vehicle = self::vehicle($vehicle_id);
			$changes[] = "vehicle " . $trip['registration_no'] . " -> " . $vehicle['registration_no'];
		}
		if ($driver_id != $trip['driver_id']) {
			$driver = self::driver($driver_id);
			$changes[] = "driver " . self::driver_name($trip) . " -> " . self::driver_name($driver);
		}
		if ($editable) {
			self::attach_orders($trip_id);
		}
		if (count($changes) > 0) {
			self::log_event($trip_id, "Reassigned", implode("; ", $changes), $user_id);
		}
		return array();
	}

	// Depot confirms the vehicle is loaded; picks up orders added to the route since planning
	static function mark_loaded($trip_id, $user_id) {
		$trip = self::trip($trip_id);
		if (!$trip || $trip['status'] != "Planned") {
			return array("Only a Planned trip can be marked as loaded");
		}
		$errors = self::validate_assignment($trip['trip_date'], $trip['route_id'], $trip['vehicle_id'], $trip['driver_id'], $trip_id);
		if (count($errors) > 0) {
			return $errors;
		}
		$orders = self::attach_orders($trip_id);
		self::exec("UPDATE trips SET status='Loaded' WHERE trip_id=" . (int) $trip_id);
		$load = self::load_totals($trip_id);
		self::log_event($trip_id, "Loaded", $orders . " orders, " . $load['sku_count'] . " SKUs, " . $load['loaded_units'] . " units, " . $load['packages'] . " packages", $user_id);
		return array();
	}

	static function start_trip($trip_id, $odometer, $user_id, $source = "Admin", $latitude = "", $longitude = "") {
		$trip = self::trip($trip_id);
		if (!$trip || !in_array($trip['status'], array("Planned", "Loaded"))) {
			return array("Only a Planned or Loaded trip can be started");
		}
		if ($trip['trip_date'] > date("Y-m-d")) {
			return array("This trip is scheduled for " . $trip['trip_date'] . " and cannot start earlier");
		}
		$errors = self::validate_assignment($trip['trip_date'], $trip['route_id'], $trip['vehicle_id'], $trip['driver_id'], $trip_id);
		$vehicle = self::vehicle($trip['vehicle_id']);
		if (!is_numeric($odometer) || (int) $odometer < 0) {
			$errors[] = "Please enter the start odometer reading";
		} elseif ((int) $odometer < (int) $vehicle['odometer_km']) {
			$errors[] = "Start odometer " . (int) $odometer . " " . self::du() . " is below the vehicle's last reading of " . $vehicle['odometer_km'] . " " . self::du();
		}
		if (count($errors) > 0) {
			return $errors;
		}
		$orders = self::attach_orders($trip_id);
		self::exec("UPDATE trips SET status='In Transit', start_odometer=" . (int) $odometer . ", start_time='" . date("Y-m-d H:i:s") . "' WHERE trip_id=" . (int) $trip_id);
		self::exec("UPDATE vehicles SET status='On Trip' WHERE vehicle_id=" . (int) $trip['vehicle_id']);
		self::log_event($trip_id, "Started", "Odometer " . (int) $odometer . " " . self::du() . ", " . $orders . " orders", $user_id, $source, $latitude, $longitude);
		return array();
	}

	/*
	 * Close the trip. Every stop must be finished (delivered / refused / exception) unless an admin force-closes with a
	 * note - undelivered goods are then returned to the depot and the orders stay open for re-dispatch.
	 */
	static function complete_trip($trip_id, $odometer, $user_id, $source = "Admin", $force_note = "", $latitude = "", $longitude = "") {
		$trip = self::trip($trip_id);
		if (!$trip || $trip['status'] != "In Transit") {
			return array("Only an In Transit trip can be completed");
		}
		$errors = array();
		if (!is_numeric($odometer) || (int) $odometer < (int) $trip['start_odometer']) {
			$errors[] = "End odometer must be at least the start reading of " . $trip['start_odometer'] . " " . self::du();
		}
		$progress = self::progress($trip_id);
		$force_note = trim((string) $force_note);
		if ($progress['open'] > 0 && ($source != "Admin" || $force_note == "")) {
			$errors[] = $progress['open'] . " of " . $progress['total'] . " stops are still open - finish them"
				. ($source == "Admin" ? " or close the trip with a return note" : " or ask the office to close the trip");
		}
		if (count($errors) > 0) {
			return $errors;
		}
		$note = $progress['open'] > 0 ? $progress['open'] . " undelivered orders returned to depot: " . $force_note : "";
		$distance = (int) $odometer - (int) $trip['start_odometer'];

		$db = new database();
		$db->data("close_note", $note);
		$db->where("trip_id=" . (int) $trip_id);
		$db->update("trips", "status='Completed', end_odometer=" . (int) $odometer . ", end_time='" . date("Y-m-d H:i:s") . "'");
		// Free the vehicle unless it is already booked on another running trip
		self::exec("UPDATE vehicles SET odometer_km=GREATEST(odometer_km," . (int) $odometer . "),
			status=IF(status='On Trip','Available',status) WHERE vehicle_id=" . (int) $trip['vehicle_id']);
		if ($progress['open'] > 0) {
			// Orders not delivered leave the trip so they can be planned on a new one
			self::exec("UPDATE deliveries SET trip_id=0 WHERE trip_id=" . (int) $trip_id . " AND status NOT IN (" . self::in_list(self::$CLOSED_ORDER_STATUSES) . ")");
		}
		self::log_event($trip_id, "Completed", "Odometer " . (int) $odometer . " " . self::du() . ", distance " . $distance . " " . self::du() . ($note != "" ? "; " . $note : ""), $user_id, $source, $latitude, $longitude);
		return array();
	}

	static function cancel_trip($trip_id, $reason, $user_id) {
		$trip = self::trip($trip_id);
		if (!$trip || !in_array($trip['status'], array("Planned", "Loaded"))) {
			return array("Only a Planned or Loaded trip can be cancelled");
		}
		if (trim((string) $reason) == "") {
			return array("Please give a reason for cancelling");
		}
		$db = new database();
		$db->data("close_note", $reason);
		$db->where("trip_id=" . (int) $trip_id);
		$db->update("trips", "status='Cancelled'");
		self::exec("UPDATE deliveries SET trip_id=0 WHERE trip_id=" . (int) $trip_id);
		self::log_event($trip_id, "Cancelled", $reason, $user_id);
		return array();
	}

	//------------------------------------------------------------------------------------------------------------------
	// load, progress, expenses

	/*
	 * SKU-wise load of the trip.
	 *   loaded    = delivery-order qty put on the vehicle at the depot
	 *   delivered = qty handed over to customers
	 *   picked_up = qty collected on pickup orders
	 *   on_board  = loaded - delivered + picked_up (what is physically on the vehicle now)
	 */
	static function load_summary($trip_id) {
		return self::rows("SELECT DI.sku, MAX(DI.product_name) AS product_name, MAX(DI.uom) AS uom,
				SUM(IF(M.manifest_type='Pickup', 0, DI.qty)) AS loaded,
				SUM(IF(M.manifest_type='Pickup', 0, DI.qty_delivered)) AS delivered,
				SUM(IF(M.manifest_type='Pickup', DI.qty, 0)) AS pickup_planned,
				SUM(IF(M.manifest_type='Pickup', DI.qty_delivered, 0)) AS picked_up,
				COUNT(DISTINCT D.delivery_id) AS orders
			FROM delivery_items DI
			JOIN deliveries D ON D.delivery_id=DI.delivery_id
			LEFT JOIN manifests M ON M.manifest_id=D.manifest_id
			WHERE D.trip_id=" . (int) $trip_id . "
			GROUP BY DI.sku ORDER BY DI.sku");
	}

	static function package_summary($trip_id) {
		return self::rows("SELECT PT.name AS package_type,
				SUM(IF(M.manifest_type='Pickup', 0, DP.qty)) AS loaded,
				SUM(IF(M.manifest_type='Pickup', 0, DP.qty_delivered)) AS delivered,
				SUM(IF(M.manifest_type='Pickup', DP.qty_delivered, 0)) AS picked_up
			FROM delivery_packages DP
			JOIN deliveries D ON D.delivery_id=DP.delivery_id
			LEFT JOIN manifests M ON M.manifest_id=D.manifest_id
			LEFT JOIN package_types PT ON PT.package_type_id=DP.package_type_id
			WHERE D.trip_id=" . (int) $trip_id . "
			GROUP BY DP.package_type_id ORDER BY PT.name");
	}

	static function load_totals($trip_id) {
		$totals = array("sku_count" => 0, "loaded_units" => 0, "delivered_units" => 0, "picked_up_units" => 0, "on_board_units" => 0, "packages" => 0, "packages_on_board" => 0);
		foreach (self::load_summary($trip_id) as $row) {
			$totals['sku_count']++;
			$totals['loaded_units'] += $row['loaded'];
			$totals['delivered_units'] += $row['delivered'];
			$totals['picked_up_units'] += $row['picked_up'];
		}
		$totals['on_board_units'] = $totals['loaded_units'] - $totals['delivered_units'] + $totals['picked_up_units'];
		foreach (self::package_summary($trip_id) as $row) {
			$totals['packages'] += $row['loaded'];
			$totals['packages_on_board'] += $row['loaded'] - $row['delivered'] + $row['picked_up'];
		}
		return $totals;
	}

	static function progress($trip_id) {
		$row = self::row("SELECT COUNT(*) AS total,
				SUM(status='Delivered') AS delivered,
				SUM(status IN ('Refused','Exception')) AS exceptions,
				SUM(status NOT IN (" . self::in_list(self::$CLOSED_ORDER_STATUSES) . ")) AS open
			FROM deliveries WHERE trip_id=" . (int) $trip_id);
		return array("total" => (int) $row['total'], "delivered" => (int) $row['delivered'], "exceptions" => (int) $row['exceptions'], "open" => (int) $row['open']);
	}

	static function expense_summary($trip_id) {
		$summary = array("by_type" => array(), "toll" => 0, "tax" => 0, "fuel" => 0, "other" => 0, "total" => 0, "toll_count" => 0, "fuel_litres" => 0);
		$rows = self::rows("SELECT expense_type, COUNT(*) AS entries, SUM(amount) AS amount, SUM(fuel_litres) AS litres
			FROM trip_expenses WHERE trip_id=" . (int) $trip_id . " GROUP BY expense_type ORDER BY expense_type");
		foreach ($rows as $row) {
			$summary['by_type'][$row['expense_type']] = $row;
			$summary['total'] += $row['amount'];
			if ($row['expense_type'] == "Toll") {
				$summary['toll'] += $row['amount'];
				$summary['toll_count'] += $row['entries'];
			} elseif (in_array($row['expense_type'], self::$TAX_EXPENSE_TYPES)) {
				$summary['tax'] += $row['amount'];
			} elseif ($row['expense_type'] == "Fuel") {
				$summary['fuel'] += $row['amount'];
				$summary['fuel_litres'] += $row['litres'];
			} else {
				$summary['other'] += $row['amount'];
			}
		}
		return $summary;
	}

	// Store an uploaded receipt (field $field of $_FILES). Returns array(filename, error).
	static function save_receipt($field) {
		if (empty($_FILES[$field]) || $_FILES[$field]['error'] == UPLOAD_ERR_NO_FILE) {
			return array("", "");
		}
		$file = $_FILES[$field];
		if ($file['error'] != UPLOAD_ERR_OK) {
			return array("", "Receipt upload failed (code " . $file['error'] . ")");
		}
		$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
		if (!in_array($extension, self::$RECEIPT_EXTENSIONS)) {
			return array("", "Receipt must be a " . implode(", ", self::$RECEIPT_EXTENSIONS) . " file");
		}
		if ($file['size'] > self::RECEIPT_MAX_BYTES) {
			return array("", "Receipt must be smaller than 5 MB");
		}
		$dir = ROOTPATH . "/uploads/expense";
		if (!is_dir($dir)) {
			mkdir($dir, 0755, true);
		}
		$filename = "receipt_" . date("YmdHis") . "_" . mt_rand(1000, 9999) . "." . $extension;
		if (!move_uploaded_file($file['tmp_name'], $dir . "/" . $filename)) {
			return array("", "Could not store the receipt");
		}
		return array($filename, "");
	}

	/*
	 * Record a toll / tax / fuel / ... payment against a trip. The vehicle and driver are taken from the trip.
	 */
	static function add_expense($trip_id, $input, $user_id, $source = "Admin") {
		$trip = self::trip($trip_id);
		$errors = array();
		if (!$trip) {
			return array(0, array("Trip not found"));
		}
		if ($trip['status'] == "Cancelled") {
			$errors[] = "Cannot add expenses to a cancelled trip";
		}
		if ($trip['settlement_status'] == "Settled") {
			$errors[] = "Trip " . $trip['trip_number'] . " is settled - expenses are locked";
		}
		if ($source == "Driver App" && $trip['status'] != "In Transit") {
			$errors[] = "Expenses can be added from the app only while the trip is In Transit";
		}
		$type = self::input($input, 'expense_type');
		if (!in_array($type, self::$EXPENSE_TYPES)) {
			$errors[] = "Please select an expense type (" . implode(", ", self::$EXPENSE_TYPES) . ")";
		}
		$amount = (float) self::input($input, 'amount');
		if (!is_numeric(self::input($input, 'amount')) || $amount <= 0) {
			$errors[] = "Please enter an amount greater than zero";
		}
		$mode = self::input($input, 'payment_mode');
		if ($mode == "") {
			$mode = "Cash";
		}
		if (!in_array($mode, self::$PAYMENT_MODES)) {
			$errors[] = "Payment mode must be one of " . implode(", ", self::$PAYMENT_MODES);
		}
		$litres = (float) self::input($input, 'fuel_litres');
		if ($type == "Fuel" && $litres <= 0) {
			$errors[] = "Please enter the fuel quantity (" . self::fu() . ")";
		}
		$expense_time = str_replace("T", " ", self::input($input, 'expense_time'));
		if ($expense_time == "") {
			$expense_time = date("Y-m-d H:i:s");
		} elseif (strtotime($expense_time) === false) {
			$errors[] = "Please enter a valid expense date/time";
		} else {
			$expense_time = date("Y-m-d H:i:s", strtotime($expense_time));
			if ($expense_time > date("Y-m-d H:i:s", strtotime("+10 minutes"))) {
				$errors[] = "Expense time cannot be in the future";
			}
		}
		if (count($errors) > 0) {
			return array(0, $errors);
		}
		list($receipt, $upload_error) = self::save_receipt("receipt");
		if ($upload_error != "") {
			return array(0, array($upload_error));
		}

		$db = new database();
		$db->data("trip_id", (int) $trip_id);
		$db->data("vehicle_id", (int) $trip['vehicle_id']);
		$db->data("driver_id", (int) $trip['driver_id']);
		$db->data("expense_type", $type);
		$db->data("amount", round($amount, 2));
		$db->data("payment_mode", $mode);
		$db->data("location", self::input($input, 'location'));
		$db->data("reference_no", self::input($input, 'reference_no'));
		$db->data("receipt_file", $receipt);
		$db->data("fuel_litres", $type == "Fuel" ? round($litres, 2) : 0);
		$db->data("odometer_km", (int) self::input($input, 'odometer_km'));
		$db->data("latitude", self::input($input, 'latitude'));
		$db->data("longitude", self::input($input, 'longitude'));
		$db->data("expense_time", $expense_time);
		$db->data("entered_by", $source);
		$db->data("entered_by_user_id", (int) $user_id);
		$db->data("added_date", date("Y-m-d H:i:s"));
		$db->insert("trip_expenses");
		return array($db->id, array());
	}

	static function delete_expense($expense_id) {
		$expense = self::row("SELECT E.receipt_file, T.settlement_status FROM trip_expenses E JOIN trips T ON T.trip_id=E.trip_id WHERE E.expense_id=" . (int) $expense_id);
		if (!$expense || $expense['settlement_status'] == "Settled") {
			return false;
		}
		if ($expense['receipt_file'] != "" && is_file(ROOTPATH . "/uploads/expense/" . basename($expense['receipt_file']))) {
			unlink(ROOTPATH . "/uploads/expense/" . basename($expense['receipt_file']));
		}
		self::exec("DELETE FROM trip_expenses WHERE expense_id=" . (int) $expense_id);
		return true;
	}

	//------------------------------------------------------------------------------------------------------------------
	// trip sheet / driver settlement

	/*
	 * Driver cash settlement of a trip.
	 *   driver_paid  = expenses paid in Cash / UPI (out of the advance)
	 *   company_paid = FASTag / Card / Company Account
	 *   balance      = advance - driver_paid   (> 0 driver returns it, < 0 company reimburses the driver)
	 */
	static function settlement($trip_id) {
		$trip = self::row("SELECT cash_advance, settlement_status, settled_by, settled_at, settlement_note FROM trips WHERE trip_id=" . (int) $trip_id);
		$row = self::row("SELECT COALESCE(SUM(IF(payment_mode IN (" . self::in_list(self::$DRIVER_PAID_MODES) . "), amount, 0)),0) AS driver_paid,
				COALESCE(SUM(IF(payment_mode IN (" . self::in_list(self::$DRIVER_PAID_MODES) . "), 0, amount)),0) AS company_paid,
				SUM(payment_mode IN (" . self::in_list(self::$DRIVER_PAID_MODES) . ") AND receipt_file='') AS missing_receipts
			FROM trip_expenses WHERE trip_id=" . (int) $trip_id);
		$advance = (float) $trip['cash_advance'];
		$balance = round($advance - (float) $row['driver_paid'], 2);
		return array(
			"advance" => $advance, "driver_paid" => (float) $row['driver_paid'], "company_paid" => (float) $row['company_paid'],
			"balance" => $balance, "missing_receipts" => (int) $row['missing_receipts'],
			"outcome" => $balance > 0 ? "Driver returns " . self::money($balance) : ($balance < 0 ? "Company reimburses driver " . self::money(-$balance) : "Nothing to settle"),
			"status" => $trip['settlement_status'], "settled_by" => (int) $trip['settled_by'], "settled_at" => $trip['settled_at'], "note" => $trip['settlement_note'],
		);
	}

	static function set_advance($trip_id, $amount, $user_id) {
		$trip = self::trip($trip_id);
		if (!$trip || $trip['status'] == "Cancelled") {
			return array("Trip not found or cancelled");
		}
		if ($trip['settlement_status'] == "Settled") {
			return array("Trip is settled - the advance is locked");
		}
		if (!is_numeric($amount) || $amount < 0) {
			return array("Advance must be zero or more");
		}
		self::exec("UPDATE trips SET cash_advance=" . round((float) $amount, 2) . " WHERE trip_id=" . (int) $trip_id);
		self::log_event($trip_id, "Advance", "Cash advance " . self::money($amount) . " (was " . self::money($trip['cash_advance']) . ")", $user_id);
		return array();
	}

	/*
	 * Fleet manager approves the trip sheet. Only a Completed trip; afterwards expenses and advance are locked.
	 * Driver-paid expenses without a receipt photo need a note explaining why they are accepted.
	 */
	static function settle_trip($trip_id, $user_id, $note = "") {
		$trip = self::trip($trip_id);
		if (!$trip || $trip['status'] != "Completed") {
			return array("Only a completed trip can be settled");
		}
		if ($trip['settlement_status'] == "Settled") {
			return array("Trip is already settled");
		}
		$s = self::settlement($trip_id);
		$note = trim((string) $note);
		if ($s['missing_receipts'] > 0 && $note == "") {
			return array($s['missing_receipts'] . " cash/UPI expense(s) have no receipt - add a note to accept them without one");
		}
		$db = new database();
		$db->data("settlement_note", $note);
		$db->where("trip_id=" . (int) $trip_id);
		$db->update("trips", "settlement_status='Settled', settled_by=" . (int) $user_id . ", settled_at='" . date("Y-m-d H:i:s") . "'");
		self::log_event($trip_id, "Settled", $s['outcome'] . ($note != "" ? "; " . $note : ""), $user_id);
		return array();
	}

	//------------------------------------------------------------------------------------------------------------------
	// driver's own history (driver page)

	// Finished trips of a driver, newest first, with km, stops, expenses and settlement
	static function driver_trips($driver_id, $limit = 10, $offset = 0) {
		$trips = self::rows("SELECT T.trip_id, T.trip_number, T.trip_date, T.status, T.start_time, T.end_time, T.start_odometer, T.end_odometer,
				T.cash_advance, T.settlement_status, T.close_note, R.name AS route_name, V.registration_no, V.vehicle_type,
				(SELECT COUNT(*) FROM deliveries D WHERE D.trip_id=T.trip_id) AS stops,
				(SELECT COUNT(*) FROM deliveries D WHERE D.trip_id=T.trip_id AND D.status='Delivered') AS delivered
			FROM trips T LEFT JOIN routes R ON R.route_id=T.route_id LEFT JOIN vehicles V ON V.vehicle_id=T.vehicle_id
			WHERE T.driver_id=" . (int) $driver_id . " AND T.status IN ('Completed','Cancelled')
			ORDER BY T.trip_date DESC, T.trip_id DESC LIMIT " . (int) $offset . ", " . (int) $limit);
		foreach ($trips as $i => $t) {
			$e = self::expense_summary($t['trip_id']);
			$s = self::settlement($t['trip_id']);
			$trips[$i]['km'] = $t['end_odometer'] !== null && $t['start_odometer'] !== null ? (int) $t['end_odometer'] - (int) $t['start_odometer'] : 0;
			$trips[$i]['toll'] = $e['toll'];
			$trips[$i]['tax'] = $e['tax'];
			$trips[$i]['fuel'] = $e['fuel'];
			$trips[$i]['expenses'] = $e['total'];
			$trips[$i]['balance'] = $s['balance'];
			// the settlement from the driver's side
			$trips[$i]['settlement_text'] = $t['status'] != "Completed" ? "" : ($s['balance'] > 0 ? "You return " . self::money($s['balance'])
				: ($s['balance'] < 0 ? "Company pays you " . self::money(-$s['balance']) : "Nothing to settle"));
		}
		return $trips;
	}

	static function driver_totals($driver_id) {
		$q = function ($where) use ($driver_id) {
			return self::row("SELECT COUNT(*) AS trips, COALESCE(SUM(T.end_odometer-T.start_odometer),0) AS km,
					COALESCE(SUM((SELECT COUNT(*) FROM deliveries D WHERE D.trip_id=T.trip_id AND D.status='Delivered')),0) AS delivered
				FROM trips T WHERE T.driver_id=" . (int) $driver_id . " AND T.status='Completed'" . $where);
		};
		$open = self::rows("SELECT trip_id FROM trips WHERE driver_id=" . (int) $driver_id . " AND status='Completed' AND settlement_status='Pending'");
		$balance = 0;
		foreach ($open as $t) {
			$s = self::settlement($t['trip_id']);
			$balance += $s['balance'];
		}
		return array("month" => $q(" AND T.trip_date>='" . date("Y-m-01") . "'"), "all" => $q(""),
			"pending_trips" => count($open), "pending_balance" => round($balance, 2));
	}

	const GPS_MAX_AGE_SECONDS = 86400;	// points older than this (offline queue) are refused
	const GPS_MAX_AHEAD_SECONDS = 300;	// phone clock may run a little fast
	const GPS_GOOD_ACCURACY_M = 100;	// worse points are stored but left out of the drawn route

	/*
	 * Store a GPS reading from the driver's phone.
	 *   $recorded_at: when the phone took the reading (unix seconds or milliseconds); empty = now. Readings queued
	 *                 while the phone was offline arrive late but keep their real time.
	 *   $accuracy:    metres reported by the phone (optional).
	 * The point is tagged with the trip the driver was running at that moment. Times are stored in company time.
	 * Returns the trip_id the point was tagged with (0 = none), or a string with the reason it was refused.
	 */
	static function record_location($driver_id, $latitude, $longitude, $recorded_at = null, $accuracy = null) {
		if ((int) $driver_id < 1 || !is_numeric($latitude) || !is_numeric($longitude) || abs($latitude) > 90 || abs($longitude) > 180
				|| ((float) $latitude == 0 && (float) $longitude == 0)) {
			return "invalid coordinates";
		}
		$now = time();
		$t = $now;
		if ($recorded_at !== null && $recorded_at !== "") {
			if (!is_numeric($recorded_at)) {
				return "invalid time";
			}
			$t = (float) $recorded_at > 100000000000 ? (int) floor($recorded_at / 1000) : (int) $recorded_at;
		}
		if ($t > $now + self::GPS_MAX_AHEAD_SECONDS) {
			return "time is in the future";
		}
		if ($t < $now - self::GPS_MAX_AGE_SECONDS) {
			return "older than 24 hours";
		}
		$when = date("Y-m-d H:i:s", $t);
		$trip = self::row("SELECT trip_id, vehicle_id FROM trips WHERE driver_id=" . (int) $driver_id . "
			AND status IN ('In Transit','Completed') AND start_time<='" . $when . "' AND (end_time IS NULL OR end_time>='" . $when . "')
			ORDER BY start_time DESC LIMIT 1");
		$newest = self::row("SELECT MAX(add_date_time) AS t FROM driver_location WHERE driver_id=" . (int) $driver_id);

		$db = new database();
		$db->data("driver_id", (int) $driver_id);
		$db->data("latitude", (string) $latitude);
		$db->data("longitude", (string) $longitude);
		$db->data("trip_id", $trip ? (int) $trip['trip_id'] : 0);
		$db->data("vehicle_id", $trip ? (int) $trip['vehicle_id'] : 0);
		$db->data("add_date_time", $when);
		$db->data("received_at", date("Y-m-d H:i:s", $now));
		$db->insert("driver_location");
		if (is_numeric($accuracy)) {
			self::exec("UPDATE driver_location SET accuracy_m=" . min(65535, max(0, (int) round($accuracy))) . " WHERE driver_location_id=" . (int) $db->id);
		}

		// The driver's "current position" only moves forward in time (late offline points do not overwrite it)
		if (!$newest || $newest['t'] === null || $when >= $newest['t']) {
			$db_user = new database();
			$db_user->data("latitude", (string) $latitude);
			$db_user->data("longitude", (string) $longitude);
			$db_user->where("user_id=" . (int) $driver_id);
			$db_user->update("users");
		}
		return $trip ? (int) $trip['trip_id'] : 0;
	}

	/*
	 * A batch of readings (the phone's offline queue): JSON [{"lat":..,"lng":..,"t":ms,"acc":m}, ...], max 500.
	 * Returns array(accepted, rejected, trip_id of the newest accepted point).
	 */
	static function record_locations($driver_id, $json) {
		$points = json_decode((string) $json, true);
		if (!is_array($points)) {
			return array(0, 0, 0);
		}
		usort($points, function ($a, $b) { return (isset($a['t']) ? $a['t'] : 0) <=> (isset($b['t']) ? $b['t'] : 0); });
		$accepted = 0;
		$rejected = 0;
		$trip_id = 0;
		foreach (array_slice($points, 0, 500) as $p) {
			if (!is_array($p)) {
				$rejected++;
				continue;
			}
			$result = self::record_location($driver_id, isset($p['lat']) ? $p['lat'] : "", isset($p['lng']) ? $p['lng'] : "",
				isset($p['t']) ? $p['t'] : null, isset($p['acc']) ? $p['acc'] : null);
			if (is_int($result)) {
				$accepted++;
				$trip_id = $result;
			} else {
				$rejected++;
			}
		}
		return array($accepted, $rejected + max(0, count($points) - 500), $trip_id);
	}

	// GPS breadcrumbs: pings tagged with the trip, plus untagged pings of the driver during the trip window
	static function track_points($trip) {
		$window = "";
		if (!empty($trip['start_time'])) {
			$end = !empty($trip['end_time']) ? "'" . $trip['end_time'] . "'" : "NOW()";
			$window = " OR (driver_id=" . (int) $trip['driver_id'] . " AND trip_id=0 AND add_date_time BETWEEN '" . $trip['start_time'] . "' AND " . $end . ")";
		}
		return self::rows("SELECT latitude, longitude, add_date_time FROM driver_location
			WHERE (trip_id=" . (int) $trip['trip_id'] . $window . ") AND (accuracy_m IS NULL OR accuracy_m<=" . self::GPS_GOOD_ACCURACY_M . ")
			ORDER BY add_date_time, driver_location_id");
	}
}
?>
