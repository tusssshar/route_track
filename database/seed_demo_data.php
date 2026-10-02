<?php
/*
 * RouteTrac demo data - a Pune based FMCG distributor (Maharashtra + one Karnataka run).
 *
 *   php database/seed_demo_data.php --yes
 *
 * WIPES every table of the repository DB (orders, manifests, trips, GPS, customers, routes, areas, package types,
 * vehicles, users except user_id 4 "admin") and the files under uploads/, then loads a consistent demo week:
 * completed trips for the last 7 days (one cancelled + re-dispatched), today one trip In Transit, one Loaded,
 * one Planned and a route with orders but no trip yet, tomorrow one Planned trip.
 * All times are relative to "now" in the company timezone (company_settings), so the data is consistent whenever it is re-run.
 * Demo logins: admin / 12345 (unchanged), dispatcher priya / 12345, drivers ramesh, suresh, vijay, anil, sunil, ganesh / 12345.
 */
if (!in_array('--yes', $argv)) {
	fwrite(STDERR, "This deletes all RouteTrac data except the admin login. Run with --yes to continue.\n");
	exit(1);
}
mt_srand(20261002);
include_once(dirname(__DIR__) . "/libraries/classes/fleet.php");	// licence rules + Aadhaar checksum (pure functions)

// A 12-digit number that passes the Aadhaar (Verhoeff) checksum - demo only, not a real person's number
function demo_aadhaar() {
	do {
		$n = mt_rand(2, 9) . str_pad((string) mt_rand(0, 9999999999), 10, "0", STR_PAD_LEFT);
		for ($c = 0; $c <= 9 && !fleet::valid_aadhaar($n . $c); $c++);
	} while ($c > 9);
	return $n . $c;
}

$db = new mysqli('localhost', 'root', '', 'RouteTrac_template_repository');
if ($db->connect_errno) {
	fwrite(STDERR, "DB connect failed: " . $db->connect_error . "\n");
	exit(1);
}
$db->set_charset('utf8');
// Same timezone as the app (company_settings, default India) for PHP and this MySQL session
$tz = 'Asia/Kolkata';
if (($res = @$db->query("SELECT setting_value FROM company_settings WHERE setting_key='timezone'")) && ($row = $res->fetch_row()) && in_array($row[0], DateTimeZone::listIdentifiers())) {
	$tz = $row[0];
}
date_default_timezone_set($tz);
$db->query("SET time_zone='" . date('P') . "'");
$UPLOADS = dirname(__DIR__) . '/uploads';
const ADMIN_ID = 4;
const MD5_12345 = '827ccb0eea8a706c4c34a16891f84e7b';

function q($sql) {
	global $db;
	$res = $db->query($sql);
	if ($res === false) {
		fwrite(STDERR, "SQL error: " . $db->error . "\n" . $sql . "\n");
		exit(1);
	}
	return $res;
}
function ins($table, $row) {
	global $db;
	$values = array();
	foreach ($row as $v) {
		$values[] = $v === null ? "NULL" : "'" . $db->real_escape_string((string) $v) . "'";
	}
	q("INSERT INTO `$table` (`" . implode("`,`", array_keys($row)) . "`) VALUES (" . implode(",", $values) . ")");
	return $db->insert_id;
}
function ts($t) { return date('Y-m-d H:i:s', (int) $t); }

// Customer signature captured on the phone (a scribble + the receiver's name) -> signature_pics row id
function signature_image($name, $t) {
	global $UPLOADS;
	$img = imagecreatetruecolor(300, 90);
	imagefill($img, 0, 0, imagecolorallocate($img, 255, 255, 255));
	$ink = imagecolorallocate($img, 20, 40, 120);
	imagesetthickness($img, 2);
	$x = 15; $y = 55;
	for ($i = 0; $i < 14; $i++) {
		$nx = $x + mt_rand(10, 22); $ny = 30 + mt_rand(0, 40);
		imageline($img, $x, $y, $nx, $ny, $ink);
		$x = $nx; $y = $ny;
	}
	imageline($img, 15, 75, $x, 72, $ink);
	imagestring($img, 2, 200, 74, $name, imagecolorallocate($img, 120, 120, 120));
	$file = "signature_" . date('Y-m-d H_i_s', $t) . "_" . mt_rand(100, 999) . ".jpeg";
	imagejpeg($img, "$UPLOADS/signature/$file", 80);
	imagedestroy($img);
	return ins('signature_pics', array('server_filename' => $file));
}

// Photo of a paper receipt (toll slip / fuel bill / parking ticket) -> file name under uploads/expense
function receipt_image($type, $amount, $location, $ref, $t) {
	global $UPLOADS;
	$img = imagecreatetruecolor(260, 150);
	imagefill($img, 0, 0, imagecolorallocate($img, 250, 248, 240));
	$ink = imagecolorallocate($img, 40, 40, 40);
	$lines = array(strtoupper($type) . " RECEIPT", substr($location, 0, 38), "Ref: " . $ref, date('d-m-Y H:i', $t), "Amount: Rs " . number_format($amount, 2), "Thank you - drive safe");
	foreach ($lines as $i => $line) {
		imagestring($img, $i == 0 ? 4 : 3, 10, 10 + $i * 22, $line, $ink);
	}
	$file = "receipt_" . date('YmdHis', $t) . "_" . mt_rand(1000, 9999) . ".jpg";
	imagejpeg($img, "$UPLOADS/expense/$file", 75);
	imagedestroy($img);
	return $file;
}
function day($t) { return date('Y-m-d', (int) $t); }
function pick($list) { return $list[mt_rand(0, count($list) - 1)]; }
function km($a, $b) {	// straight-line distance
	$r = 6371; $dLat = deg2rad($b[0] - $a[0]); $dLng = deg2rad($b[1] - $a[1]);
	$h = sin($dLat / 2) ** 2 + cos(deg2rad($a[0])) * cos(deg2rad($b[0])) * sin($dLng / 2) ** 2;
	return 2 * $r * asin(sqrt($h));
}

// ------------------------------------------------------------------------------------------------ wipe
echo "Wiping old data...\n";
foreach (array('deliveries', 'delivery_items', 'delivery_packages', 'manifests', 'signature_pics', 'product_pics', 'driver_photos',
	'driver_location', 'route_accept', 'trips', 'trip_events', 'trip_expenses', 'vehicles', 'customers', 'customer_addresses',
	'routes', 'areas', 'package_types', 'city_master', 'mast_state') as $table) {
	q("TRUNCATE TABLE `$table`");
}
q("DELETE FROM users WHERE user_id <> " . ADMIN_ID);
q("ALTER TABLE users AUTO_INCREMENT = " . (ADMIN_ID + 1));
q("UPDATE users SET is_driver=0, default_route_id=0, current_route_id=0, latitude='', longitude='' WHERE user_id=" . ADMIN_ID);
foreach (array('signature', 'product_pics', 'photo', 'expense') as $dir) {
	foreach (glob("$UPLOADS/$dir/*") ?: array() as $file) {
		if (is_file($file)) {
			unlink($file);
		}
	}
}

// ------------------------------------------------------------------------------------------------ masters
echo "Loading masters...\n";
foreach (array(array('Maharashtra', 'Maharashtra'), array('Karnataka', 'Karnataka'), array('Maharashtra', 'Pune'), array('Maharashtra', 'Mumbai'),
	array('Maharashtra', 'Thane'), array('Maharashtra', 'Nashik'), array('Karnataka', 'Belagavi')) as $s) {
	ins('mast_state', array('state_name' => $s[0], 'state' => $s[1], 'country_id' => 1, 'route' => 0, 'ship_via' => 0));
}

$PKG = array();
foreach (array('Carton', 'Crate', 'Bag', 'Jar') as $name) {
	$PKG[$name] = ins('package_types', array('name' => $name));
}

// SKU => [product, uom, package type, units per package]
$SKUS = array(
	'ATA-10K' => array('Wheat Atta 10 kg', 'BG', 'Bag', 1),
	'RIC-25K' => array('Basmati Rice 25 kg', 'BG', 'Bag', 1),
	'SUG-05K' => array('Sugar 5 kg Pack', 'PC', 'Carton', 8),
	'OIL-1LB' => array('Sunflower Oil 1 L x 12 Box', 'BX', 'Carton', 1),
	'TEA-500' => array('Premium Tea 500 g', 'PC', 'Carton', 24),
	'COF-200' => array('Instant Coffee 200 g Jar', 'EA', 'Carton', 24),
	'BIS-C48' => array('Glucose Biscuits Case (48)', 'CS', 'Carton', 1),
	'DET-01K' => array('Detergent Powder 1 kg', 'PC', 'Carton', 20),
	'SOP-4PK' => array('Bath Soap 4-Pack', 'PC', 'Carton', 36),
	'TPS-C24' => array('Toothpaste Case (24)', 'CS', 'Carton', 1),
	'MLK-UHT' => array('UHT Milk 1 L x 12 Box', 'BX', 'Crate', 2),
	'WTR-20L' => array('Packaged Water 20 L Jar', 'EA', 'Jar', 1),
);
// Collected back on pickup orders
$RETURNS = array('CRT-EMP' => array('Empty Plastic Crate (return)', 'EA', 'Crate'), 'JAR-EMP' => array('Empty 20 L Jar (return)', 'EA', 'Jar'));

$DEPOT = array(18.5990, 73.7610, 'Pune DC, Wakad');
$areas = array();
foreach (array('Pune' => 1, 'Mumbai' => 0, 'Nashik' => 0, 'Interstate' => 0) as $name => $default) {
	$areas[$name] = ins('areas', array('name' => $name, 'is_default' => $default));
}

/*
 * Routes: road km (round trip), hours on road, tolls on the way out [name, lat, lng, LCV, truck] (paid again on return),
 * border tax, vehicle preference, shops [name, address, city, zip, lat, lng].
 */
$ROUTES = array(
	'PW' => array('name' => 'Pune West - Hinjewadi / Baner', 'area' => 'Pune', 'km' => 58, 'hours' => 7, 'tolls' => array(), 'class' => 'small',
		'shops' => array(
			array('Shree Ganesh Supermarket', 'Phase 1, Rajiv Gandhi Infotech Park, Hinjewadi', 'Pune', '411057', 18.5913, 73.7389),
			array('Annapurna Provision Stores', 'Baner Road, near Balewadi Phata', 'Pune', '411045', 18.5590, 73.7868),
			array('Sai Krupa Mart', 'ITI Road, Aundh', 'Pune', '411007', 18.5580, 73.8075),
			array('Wakad Fresh Mart', 'Datta Mandir Road, Wakad', 'Pune', '411057', 18.6012, 73.7646),
			array('Balewadi Daily Needs', 'High Street, Balewadi', 'Pune', '411045', 18.5741, 73.7770),
		)),
	'PE' => array('name' => 'Pune East - Hadapsar / Kharadi', 'area' => 'Pune', 'km' => 72, 'hours' => 7, 'tolls' => array(), 'class' => 'small',
		'shops' => array(
			array('Om Sai Super Bazaar', 'Solapur Road, Hadapsar', 'Pune', '411028', 18.5089, 73.9260),
			array('Magarpatta Grocers', 'Magarpatta City, Hadapsar', 'Pune', '411013', 18.5146, 73.9270),
			array('Kharadi Kirana Kendra', 'EON IT Park Road, Kharadi', 'Pune', '411014', 18.5515, 73.9348),
			array('Viman Nagar Value Store', 'Datta Mandir Chowk, Viman Nagar', 'Pune', '411014', 18.5679, 73.9143),
		)),
	'MA' => array('name' => 'Mumbai - Andheri / Malad', 'area' => 'Mumbai', 'km' => 335, 'hours' => 11, 'class' => 'medium',
		'tolls' => array(array('Talegaon Toll Plaza (Expressway)', 18.7197, 73.6745, 0, 0), array('Khalapur Toll Plaza (Mumbai-Pune Expressway)', 18.8357, 73.2862, 495, 685)),
		'shops' => array(
			array('Andheri Wholesale Mart', 'J P Road, Andheri West', 'Mumbai', '400058', 19.1136, 72.8697),
			array('Juhu Gourmet Store', 'Juhu Tara Road, Juhu', 'Mumbai', '400049', 19.1075, 72.8263),
			array('Goregaon Super Saver', 'S V Road, Goregaon West', 'Mumbai', '400062', 19.1663, 72.8526),
			array('Malad Family Bazaar', 'Link Road, Malad West', 'Mumbai', '400064', 19.1874, 72.8484),
		)),
	'TH' => array('name' => 'Thane - Ghodbunder / Kalwa', 'area' => 'Mumbai', 'km' => 352, 'hours' => 12, 'class' => 'medium',
		'tolls' => array(array('Khalapur Toll Plaza (Mumbai-Pune Expressway)', 18.8357, 73.2862, 495, 685), array('Shil Phata Toll Naka', 19.1210, 73.0380, 40, 90)),
		'shops' => array(
			array('Thane Mega Mart', 'Eastern Express Highway, Thane West', 'Thane', '400601', 19.2183, 72.9781),
			array('Ghodbunder Fresh', 'Ghodbunder Road, Kasarvadavali', 'Thane', '400615', 19.2550, 72.9710),
			array('Kalwa Janata Stores', 'Station Road, Kalwa', 'Thane', '400605', 19.2002, 72.9996),
		)),
	'NS' => array('name' => 'Nashik City', 'area' => 'Nashik', 'km' => 418, 'hours' => 12, 'class' => 'large',
		'tolls' => array(array('Chalakwadi Toll Plaza (NH60)', 18.9780, 74.0040, 115, 240), array('Hiwargaon Pawsa Toll Plaza (NH60)', 19.3740, 74.1880, 145, 290)),
		'shops' => array(
			array('Satpur Industrial Canteen Supplies', 'MIDC Satpur', 'Nashik', '422007', 20.0063, 73.7468),
			array('College Road Supermarket', 'College Road', 'Nashik', '422005', 20.0059, 73.7647),
			array('Panchavati Bhandar', 'Panchavati Karanja', 'Nashik', '422003', 20.0110, 73.7900),
			array('Nashik Road Traders', 'Bitco Point, Nashik Road', 'Nashik', '422101', 19.9500, 73.8360),
		)),
	'BG' => array('name' => 'Pune - Belagavi (Karnataka)', 'area' => 'Interstate', 'km' => 640, 'hours' => 15, 'class' => 'large',
		'tolls' => array(array('Khed Shivapur Toll Plaza (NH48)', 18.3195, 73.8550, 165, 340), array('Anewadi Toll Plaza (NH48)', 17.8080, 74.0050, 150, 310),
			array('Tasawade Toll Plaza (NH48)', 17.2990, 74.1490, 140, 290), array('Kognoli Toll Plaza (NH48)', 16.4150, 74.3560, 135, 280)),
		'border_tax' => array('Kognoli RTO Check Post (Karnataka entry)', 16.4100, 74.3600, 1850),
		'shops' => array(
			array('Belagavi Wholesale Depot', 'Khade Bazar', 'Belagavi', '590001', 15.8497, 74.4977),
			array('Tilakwadi Mega Mart', 'Congress Road, Tilakwadi', 'Belagavi', '590006', 15.8350, 74.5060),
			array('Kolhapur Gul Traders', 'Shahupuri', 'Kolhapur', '416001', 16.7050, 74.2433),
		)),
);
$STATE_OF = array('Pune' => 'Maharashtra', 'Mumbai' => 'Maharashtra', 'Thane' => 'Maharashtra', 'Nashik' => 'Maharashtra', 'Kolhapur' => 'Maharashtra', 'Belagavi' => 'Karnataka');

$acct = 1001;
foreach ($ROUTES as $code => $r) {
	$ROUTES[$code]['route_id'] = ins('routes', array('area_id' => $areas[$r['area']], 'name' => $r['name']));
	foreach ($r['shops'] as $i => $s) {
		$customer_id = ins('customers', array('customer_name' => $s[0], 'account_number' => 'CUST-' . $acct++));
		$address_id = ins('customer_addresses', array('customer_id' => $customer_id, 'ship_to_name' => $s[0] . ' - Receiving', 'address_line_1' => $s[1],
			'address_line_2' => '', 'address_line_3' => '', 'city' => $s[2], 'state' => $STATE_OF[$s[2]], 'zip' => $s[3],
			'phone' => '+91 20 ' . mt_rand(2400, 2799) . ' ' . mt_rand(1000, 9999)));
		$ROUTES[$code]['shops'][$i]['customer_id'] = $customer_id;
		$ROUTES[$code]['shops'][$i]['address_id'] = $address_id;
	}
}

$now = time();
$TODAY = day($now);
$people = array(
	'priya' => array('Priya', 'Deshpande', 4, 0, '', null, '+91 98220 41123'),
	'ramesh' => array('Ramesh', 'Patil', 3, 1, 'MH12 20110034567', '+3 years', '+91 98500 11234'),
	'suresh' => array('Suresh', 'Jadhav', 3, 1, 'MH14 20150078123', '+2 years', '+91 97660 22345'),
	'vijay' => array('Vijay', 'Shinde', 3, 1, 'MH12 20090045612', '+4 years', '+91 99230 33456'),
	'anil' => array('Anil', 'Pawar', 3, 1, 'MH15 20120098765', '+1 year', '+91 98810 44567'),
	'sunil' => array('Sunil', 'More', 3, 1, 'MH12 20180012345', '+5 years', '+91 90110 55678'),
	'ganesh' => array('Ganesh', 'Kulkarni', 3, 1, 'MH04 20100067890', '+25 days', '+91 98330 66789'),
);
// licence classes, years of experience
$KYC = array('ramesh' => array('LMV-TR,HMV', 12), 'suresh' => array('LMV,LMV-TR,HMV', 6), 'vijay' => array('LMV-TR,HMV', 15),
	'anil' => array('HMV', 10), 'sunil' => array('LMV,LMV-TR', 4), 'ganesh' => array('MCWG,LMV-TR', 8));
$U = array();
foreach ($people as $username => $p) {
	$U[$username] = ins('users', array('status' => '1', 'first_name' => $p[0], 'last_name' => $p[1], 'email_addr' => $username . '@routetrac-demo.in',
		'acct_username' => $username, 'acct_password' => MD5_12345, 'access_level' => $p[2], 'is_driver' => $p[3], 'default_route_id' => 0,
		'current_route_id' => 0, 'api_key' => '', 'latitude' => '', 'longitude' => '', 'imei_no' => $p[3] ? 'DEMO' . mt_rand(100000, 999999) : '',
		'license_no' => $p[4], 'license_expiry' => $p[5] ? day(strtotime($p[5], $now)) : null, 'phone' => $p[6],
		'license_classes' => isset($KYC[$username]) ? $KYC[$username][0] : '', 'experience_years' => isset($KYC[$username]) ? $KYC[$username][1] : null,
		'aadhaar_no' => isset($KYC[$username]) ? demo_aadhaar() : '', 'license_doc' => '', 'aadhaar_doc' => ''));
}
$DRIVERS = array('ramesh', 'suresh', 'vijay', 'anil', 'sunil', 'ganesh');
$HELPERS = array('Raju', 'Santosh', 'Mahesh', 'Prakash', 'Dinesh', '', '');

// [reg, type, model, fuel, units, kg, km/L, price/L, odometer, class, docs offsets (days from today), status]
$VEHICLES = array(
	array('MH12RT4501', 'Mini Truck', 'Tata Ace Gold', 'Diesel', 300, 750, 15, 92.5, 48200, 'small', array(240, 400, 180, 90), 'Available'),
	array('MH12RT4502', 'Pickup', 'Mahindra Bolero Maxx Pik-Up', 'Diesel', 450, 1300, 13, 92.5, 61500, 'medium', array(12, 300, 220, 120), 'Available'),
	array('MH14RT7703', 'Truck', 'Eicher Pro 2049', 'Diesel', 1200, 4990, 7, 92.5, 102300, 'large', array(150, 260, 310, 75), 'Available'),
	array('MH12RT4504', 'Van', 'Maruti Eeco Cargo CNG', 'CNG', 150, 500, 20, 88.0, 23400, 'small', array(200, 500, 365, 140), 'Available'),
	array('MH15RT9905', 'Truck', 'Tata 407 Gold SFC', 'Diesel', 900, 2500, 9, 92.5, 87600, 'large', array(95, 180, 140, 60), 'Available'),
	array('MH04RT2206', 'Mini Truck', 'Ashok Leyland Dost+', 'Diesel', 350, 1250, 14, 92.5, 54100, 'medium', array(210, 330, 260, 100), 'Maintenance'),
	array('MH12RT4507', 'Van', 'Tata Winger Cargo', 'Diesel', 400, 1400, 12, 92.5, 39800, 'medium', array(170, 280, 200, -5), 'Available'),
);
$V = array();
foreach ($VEHICLES as $i => $v) {
	$doc = array();
	foreach (array('insurance_expiry', 'fitness_expiry', 'permit_expiry', 'pollution_expiry') as $k => $col) {
		$doc[$col] = day(strtotime(($v[10][$k] >= 0 ? '+' : '') . $v[10][$k] . ' days', $now));
	}
	$V[$i] = array('id' => ins('vehicles', array_merge(array('registration_no' => $v[0], 'vehicle_type' => $v[1], 'make_model' => $v[2], 'fuel_type' => $v[3],
		'capacity_kg' => $v[5], 'capacity_units' => $v[4], 'fastag_id' => $v[3] == 'CNG' ? '' : '34161FA820' . mt_rand(10000000, 99999999), 'odometer_km' => $v[8],
		'status' => 'Available', 'notes' => $v[11] == 'Maintenance' ? 'Clutch overhaul at Pimpri workshop' : '', 'added_date' => ts(strtotime('-90 days', $now))), $doc)),
		'reg' => $v[0], 'type' => $v[1], 'units' => $v[4], 'kmpl' => $v[6], 'price' => $v[7], 'odo' => $v[8], 'class' => $v[9],
		'truck' => $v[1] == 'Truck', 'docs' => $doc, 'fuel' => $v[3]);
}
// Vehicle 6 went to the workshop 2 days ago; vehicle 7's PUC expired 5 days ago (dispatch is blocked from then on)
$MAINT_FROM = day(strtotime('-2 days', $now));

// ------------------------------------------------------------------------------------------------ orders + trips
$RECEIVERS = array('S. Kale', 'R. Joshi', 'M. Gaikwad', 'P. Bhosale', 'A. Kadam', 'N. Chavan', 'K. Deshmukh', 'V. Salunkhe', 'D. Mane', 'T. Waghmare');
$DELIVERY_NOTES = array('', '', 'Deliver at back gate', 'Call store manager before arrival', 'Unload before 11 AM - no entry after 12', 'Collect signed GRN copy');
$manifest_of_day = array();
$order_seq = array();
$trip_seq = array();
$events = 0;

function manifest_for($date, $type) {
	global $manifest_of_day, $U;
	if (!isset($manifest_of_day[$date][$type])) {
		$manifest_of_day[$date][$type] = ins('manifests', array('submitted_by_user_id' => $U['priya'], 'submit_date' => ts(strtotime($date . ' 18:30 -1 day')),
			'manifest_type' => $type, 'api_submitted' => 0));
	}
	return $manifest_of_day[$date][$type];
}

// Create the orders of a route for a date. Returns list of order dicts. Capacity: keep under ~80% of $units_cap.
function make_orders($code, $date, $units_cap, $with_pickup = true) {
	global $ROUTES, $SKUS, $RETURNS, $PKG, $order_seq, $DELIVERY_NOTES;
	$r = $ROUTES[$code];
	$shops = $r['shops'];
	shuffle($shops);
	$shops = array_slice($shops, 0, max(2, min(count($shops), mt_rand(3, 4))));
	$budget = (int) ($units_cap * 0.8);
	$per_stop = max(8, (int) ($budget / count($shops)));
	$orders = array();
	foreach ($shops as $stop => $shop) {
		$manifest_id = manifest_for($date, 'Delivery');
		$order_seq[$date] = isset($order_seq[$date]) ? $order_seq[$date] + 1 : 1;
		$order = array('code' => $code, 'shop' => $shop, 'stop' => $stop + 1, 'type' => 'Delivery', 'items' => array());
		$order['delivery_id'] = ins('deliveries', array('manifest_id' => $manifest_id, 'route_id' => $r['route_id'], 'driver_id' => 0, 'stop_number' => $stop + 1,
			'customer_id' => $shop['customer_id'], 'address_id' => $shop['address_id'], 'status' => 'In Transit',
			'order_number' => 'SO-' . str_replace('-', '', substr($date, 2)) . '-' . str_pad($order_seq[$date], 3, '0', STR_PAD_LEFT),
			'cust_po_num' => 'PO-' . mt_rand(40000, 49999), 'delivery_notes' => pick($DELIVERY_NOTES), 'driver_notes' => '',
			'scheduled_delivery' => $date, 'delivery_time' => '0000-00-00 00:00:00', 'signature_text' => '', 'signature_pic_id' => 0,
			'latitude' => '', 'longitude' => '', 'added_date' => ts(strtotime($date . ' 18:30 -1 day')), 'exception_reason' => '', 'exception_note' => '', 'trip_id' => 0));
		$skus = array_rand($SKUS, mt_rand(2, 4));
		$left = $per_stop;
		$packages = array();
		foreach ($skus as $n => $sku) {
			$qty = $n == count($skus) - 1 ? max(2, $left) : mt_rand(2, max(3, (int) ($per_stop / count($skus))));
			$qty = min($qty, max(2, $left));
			$left -= $qty;
			$s = $SKUS[$sku];
			$item_id = ins('delivery_items', array('delivery_id' => $order['delivery_id'], 'sku' => $sku, 'qty' => $qty, 'product_name' => $s[0],
				'description' => $s[0], 'uom' => $s[1], 'qty_delivered' => 0, 'upc_code' => '890' . mt_rand(1000000000, 9999999999)));
			$order['items'][] = array('item_id' => $item_id, 'qty' => $qty);
			$packages[$s[2]] = (isset($packages[$s[2]]) ? $packages[$s[2]] : 0) + (int) ceil($qty / $s[3]);
		}
		foreach ($packages as $type => $count) {
			$order['packages'][] = array('id' => ins('delivery_packages', array('delivery_id' => $order['delivery_id'], 'package_type_id' => $PKG[$type],
				'qty' => $count, 'qty_delivered' => 0)), 'qty' => $count);
		}
		$orders[] = $order;
	}
	// Some routes also collect empty crates / jars from one shop
	if ($with_pickup && mt_rand(1, 100) <= 45) {
		$shop = $shops[mt_rand(0, count($shops) - 1)];
		$sku = array_rand($RETURNS);
		$order_seq[$date]++;
		$order = array('code' => $code, 'shop' => $shop, 'stop' => count($orders) + 1, 'type' => 'Pickup', 'items' => array(), 'packages' => array());
		$order['delivery_id'] = ins('deliveries', array('manifest_id' => manifest_for($date, 'Pickup'), 'route_id' => $r['route_id'], 'driver_id' => 0,
			'stop_number' => count($orders) + 1, 'customer_id' => $shop['customer_id'], 'address_id' => $shop['address_id'], 'status' => 'In Transit',
			'order_number' => 'RT-' . str_replace('-', '', substr($date, 2)) . '-' . str_pad($order_seq[$date], 3, '0', STR_PAD_LEFT), 'cust_po_num' => '',
			'delivery_notes' => 'Collect empties from store room', 'driver_notes' => '', 'scheduled_delivery' => $date, 'delivery_time' => '0000-00-00 00:00:00',
			'signature_text' => '', 'signature_pic_id' => 0, 'latitude' => '', 'longitude' => '', 'added_date' => ts(strtotime($date . ' 18:30 -1 day')),
			'exception_reason' => '', 'exception_note' => '', 'trip_id' => 0));
		$qty = mt_rand(6, 20);
		$order['items'][] = array('item_id' => ins('delivery_items', array('delivery_id' => $order['delivery_id'], 'sku' => $sku, 'qty' => $qty,
			'product_name' => $RETURNS[$sku][0], 'description' => $RETURNS[$sku][0], 'uom' => $RETURNS[$sku][1], 'qty_delivered' => 0, 'upc_code' => '')), 'qty' => $qty);
		$order['packages'][] = array('id' => ins('delivery_packages', array('delivery_id' => $order['delivery_id'], 'package_type_id' => $PKG[$RETURNS[$sku][2]],
			'qty' => $qty, 'qty_delivered' => 0)), 'qty' => $qty);
		$orders[] = $order;
	}
	return $orders;
}

function event($trip_id, $type, $note, $time, $user_id, $source, $lat = '', $lng = '') {
	global $events;
	$events++;
	ins('trip_events', array('trip_id' => $trip_id, 'event_type' => $type, 'note' => $note, 'user_id' => $user_id, 'source' => $source,
		'latitude' => $lat, 'longitude' => $lng, 'event_time' => ts($time)));
}

function new_trip($code, $date, $vi, $driver, $status, $created) {
	global $ROUTES, $V, $U, $trip_seq, $HELPERS;
	$trip_seq[$date] = isset($trip_seq[$date]) ? $trip_seq[$date] + 1 : 1;
	$trip_id = ins('trips', array('trip_number' => 'TRP-' . str_replace('-', '', $date) . '-' . str_pad($trip_seq[$date], 4, '0', STR_PAD_LEFT),
		'trip_date' => $date, 'route_id' => $ROUTES[$code]['route_id'], 'vehicle_id' => $V[$vi]['id'], 'driver_id' => $U[$driver], 'helper_name' => pick($HELPERS),
		'status' => $status, 'close_note' => '', 'notes' => '', 'created_by' => $U['priya'], 'created_date' => ts($created),
		// cash for tolls on cash lanes, parking, hamali, bhatta
		'cash_advance' => array('PW' => 500, 'PE' => 500, 'MA' => 3000, 'TH' => 3000, 'NS' => 3500, 'BG' => 6000)[$code]));
	return $trip_id;
}

function attach($trip_id, $orders, $driver) {
	global $U;
	$ids = array();
	foreach ($orders as $o) {
		$ids[] = $o['delivery_id'];
	}
	q("UPDATE deliveries SET trip_id=$trip_id, driver_id=" . $U[$driver] . " WHERE delivery_id IN (" . implode(',', $ids) . ")");
}

function load_note($orders) {
	$skus = array(); $units = 0; $packages = 0;
	foreach ($orders as $o) {
		foreach ($o['items'] as $i) {
			if ($o['type'] == 'Delivery') { $units += $i['qty']; }
		}
		foreach ($o['packages'] as $p) {
			if ($o['type'] == 'Delivery') { $packages += $p['qty']; }
		}
	}
	$sku_count = (int) q("SELECT COUNT(DISTINCT DI.sku) FROM delivery_items DI WHERE DI.delivery_id IN (" . implode(',', array_column($orders, 'delivery_id')) . ")")->fetch_row()[0];
	return count($orders) . " orders, " . $sku_count . " SKUs, " . $units . " units, " . $packages . " packages";
}

/*
 * Drive a trip: GPS breadcrumbs depot -> tolls -> stops -> return tolls -> depot, deliveries closed when the vehicle
 * reaches them, toll / tax / fuel / parking paid along the way. $until = stop simulating at this time (In Transit trip).
 */
function drive($trip_id, $code, $vi, $driver, $orders, $start, $hours, $until = null, $stops_done = null) {
	global $ROUTES, $V, $U, $DEPOT, $RECEIVERS;
	$r = $ROUTES[$code];
	$out = $r['tolls'];
	$back = array_reverse($r['tolls']);
	$way = array(array($DEPOT[0], $DEPOT[1], 'depot'));
	foreach ($out as $t) { $way[] = array($t[1], $t[2], 'toll', $t); }
	if (isset($r['border_tax'])) { $way[] = array($r['border_tax'][1], $r['border_tax'][2], 'tax', $r['border_tax']); }
	foreach ($orders as $k => $o) { $way[] = array($o['shop'][4], $o['shop'][5], 'stop', $k); }
	foreach ($back as $t) { $way[] = array($t[1], $t[2], 'toll', $t); }
	$way[] = array($DEPOT[0], $DEPOT[1], 'depot');

	$dist = array(0); $total = 0;
	for ($i = 1; $i < count($way); $i++) { $total += km($way[$i - 1], $way[$i]); $dist[$i] = $total; }
	if ($stops_done !== null) {
		// trip length such that $until falls half way between stop $stops_done and the next one
		$fracs = array();
		foreach ($way as $i => $w) { if ($w[2] == 'stop') { $fracs[] = $dist[$i] / $total; } }
		$target = ($fracs[$stops_done - 1] + $fracs[$stops_done]) / 2;
		$hours = ($until - $start) / $target / 3600;
	}
	$end = $start + $hours * 3600;
	$time_at = function ($frac) use ($start, $end) { return (int) ($start + $frac * ($end - $start)); };
	$stop_time = $until === null ? $end : $until;
	$v = $V[$vi];
	$uid = $U[$driver];

	// GPS points every ~ 6 minutes of driving
	$n = max(20, min(150, (int) ($hours * 10)));
	$last = null;
	for ($p = 0; $p <= $n; $p++) {
		$f = $p / $n;
		$t = $time_at($f);
		if ($t > $stop_time) { break; }
		$target = $f * $total;
		for ($i = 1; $i < count($way) && $dist[$i] < $target; $i++);
		$i = min($i, count($way) - 1);
		$seg = max(0.0001, $dist[$i] - $dist[$i - 1]);
		$x = ($target - $dist[$i - 1]) / $seg;
		$lat = $way[$i - 1][0] + ($way[$i][0] - $way[$i - 1][0]) * $x + (mt_rand(-20, 20) / 100000);
		$lng = $way[$i - 1][1] + ($way[$i][1] - $way[$i - 1][1]) * $x + (mt_rand(-20, 20) / 100000);
		ins('driver_location', array('driver_id' => $uid, 'latitude' => round($lat, 7), 'longitude' => round($lng, 7), 'add_date_time' => ts($t),
			'trip_id' => $trip_id, 'vehicle_id' => $v['id']));
		$last = array(round($lat, 7), round($lng, 7));
	}
	if ($last) {
		q("UPDATE users SET latitude='" . $last[0] . "', longitude='" . $last[1] . "' WHERE user_id=" . $uid);
	}

	// Fuel at the depot pump before every trip, for the distance of the trip
	$expense = function ($type, $amount, $mode, $location, $t, $lat, $lng, $extra = array()) use ($trip_id, $v, $uid) {
		$ref = $mode == 'FASTag' ? 'FT' . mt_rand(10000000, 99999999) : 'RCPT-' . mt_rand(1000, 9999);
		// drivers photograph most paper receipts; FASTag tolls are on the bank statement
		$receipt = ($mode != 'FASTag' && mt_rand(1, 100) <= 85) ? receipt_image($type, $amount, $location, $ref, $t) : '';
		ins('trip_expenses', array_merge(array('trip_id' => $trip_id, 'vehicle_id' => $v['id'], 'driver_id' => $uid, 'expense_type' => $type,
			'amount' => $amount, 'payment_mode' => $mode, 'location' => $location, 'reference_no' => $ref,
			'receipt_file' => $receipt, 'fuel_litres' => 0, 'odometer_km' => 0, 'latitude' => $lat, 'longitude' => $lng, 'expense_time' => ts($t),
			'entered_by' => 'Driver App', 'entered_by_user_id' => $uid, 'added_date' => ts($t + 120)), $extra));
	};
	$litres = round($r['km'] / $v['kmpl'] * (mt_rand(95, 115) / 100), 1);
	$expense('Fuel', round($litres * $v['price'], 2), pick(array('Card', 'Company Account', 'UPI')), $v['fuel'] == 'CNG' ? 'MNGL CNG Station, Wakad' : 'HP Petrol Pump, Wakad',
		$start - 900, 18.5981, 73.7655, array('fuel_litres' => $litres));

	$closed = 0;
	foreach ($way as $i => $w) {
		$t = $time_at($dist[$i] / $total);
		if ($t > $stop_time) { break; }
		if ($w[2] == 'toll' && $w[3][3] > 0) {
			$expense('Toll', $v['truck'] ? $w[3][4] : $w[3][3], $v['fuel'] == 'CNG' ? 'Cash' : 'FASTag', $w[3][0], $t, $w[0], $w[1]);
		} elseif ($w[2] == 'tax') {
			$expense('State Entry / Border Tax', $w[3][3], 'UPI', $w[3][0], $t, $w[0], $w[1]);
		} elseif ($w[2] == 'stop') {
			$o = $orders[$w[3]];
			$t += 600;
			$roll = mt_rand(1, 100);
			$delivery = array('lat' => round($w[0] + mt_rand(-30, 30) / 100000, 7), 'lng' => round($w[1] + mt_rand(-30, 30) / 100000, 7));
			if ($o['type'] == 'Delivery' && $roll <= 4) {
				q("UPDATE deliveries SET status='Refused', exception_reason='Refused Delivery', exception_note='Store says order was not placed', delivery_time='" . ts($t) . "',
					latitude='" . $delivery['lat'] . "', longitude='" . $delivery['lng'] . "' WHERE delivery_id=" . $o['delivery_id']);
			} elseif ($o['type'] == 'Delivery' && $roll <= 8) {
				q("UPDATE deliveries SET status='Exception', exception_reason='Building Closed', exception_note='Shutter down, owner not reachable', delivery_time='" . ts($t) . "',
					latitude='" . $delivery['lat'] . "', longitude='" . $delivery['lng'] . "' WHERE delivery_id=" . $o['delivery_id']);
			} else {
				$short = ($o['type'] == 'Delivery' && $roll <= 18) ? 1 : 0;	// one unit damaged in transit
				foreach ($o['items'] as $k => $item) {
					$given = $item['qty'] - ($k == 0 ? $short : 0);
					q("UPDATE delivery_items SET qty_delivered=$given WHERE item_id=" . $item['item_id']);
				}
				foreach ($o['packages'] as $pk) {
					q("UPDATE delivery_packages SET qty_delivered=" . $pk['qty'] . " WHERE packages_id=" . $pk['id']);
				}
				$receiver = pick($RECEIVERS);
				q("UPDATE deliveries SET status='Delivered', signature_text='" . $receiver . "', signature_pic_id=" . signature_image($receiver, $t) . ", delivery_time='" . ts($t) . "',
					driver_notes='" . ($short ? '1 unit damaged in transit, customer accepted the rest' : '') . "',
					latitude='" . $delivery['lat'] . "', longitude='" . $delivery['lng'] . "' WHERE delivery_id=" . $o['delivery_id']);
			}
			$closed++;
			if ($r['area'] == 'Mumbai' && mt_rand(1, 100) <= 50) {
				$expense('Parking', pick(array(50, 80, 100)), 'Cash', 'Pay & park near ' . $o['shop'][0], $t - 300, $w[0], $w[1]);
			}
		}
	}
	if ($r['km'] > 300) {
		$expense('Driver Allowance', $r['km'] > 500 ? 600 : 350, 'Cash', 'Outstation bhatta', $start + 3600 * 3, '', '');
		if (mt_rand(1, 100) <= 60) {
			$expense('Loading / Unloading', pick(array(250, 300, 400)), 'Cash', 'Hamali at ' . $orders[0]['shop'][0], $time_at(0.45), '', '');
		}
	}
	if ($r['area'] == 'Mumbai' && mt_rand(1, 100) <= 15 && $time_at(0.5) <= $stop_time) {
		$expense('Challan / Fine', 500, 'UPI', 'e-Challan: no-entry hours, S V Road', $time_at(0.5), '', '');
	}
	return array('end' => $end, 'closed' => $closed, 'hours' => $hours);
}

function finish($trip_id, $vi, $driver, $start, $end, $code) {
	global $V, $U, $ROUTES, $DEPOT;
	$km = (int) round($ROUTES[$code]['km'] * mt_rand(97, 106) / 100);
	$start_odo = $V[$vi]['odo'];
	$end_odo = $start_odo + $km;
	$V[$vi]['odo'] = $end_odo;
	q("UPDATE trips SET status='Completed', start_odometer=$start_odo, end_odometer=$end_odo, start_time='" . ts($start) . "', end_time='" . ts($end) . "' WHERE trip_id=$trip_id");
	event($trip_id, 'Started', "Odometer $start_odo km", $start, $U[$driver], 'Driver App', $DEPOT[0], $DEPOT[1]);
	event($trip_id, 'Completed', "Odometer $end_odo km, distance $km km", $end, $U[$driver], 'Driver App', $DEPOT[0], $DEPOT[1]);
	settle($trip_id, $end);
}

// The dispatcher approves trip sheets at 11:00 the next morning; trips that ended yesterday or today are still pending
function settle($trip_id, $end) {
	global $U, $now;
	if (day($end) >= day(strtotime('-1 day', $now))) {
		return;
	}
	$at = strtotime(day($end) . ' 11:00 +1 day');
	$s = q("SELECT T.cash_advance, COALESCE(SUM(IF(E.payment_mode IN ('Cash','UPI'),E.amount,0)),0), COALESCE(SUM(E.payment_mode IN ('Cash','UPI') AND E.receipt_file=''),0)
		FROM trips T LEFT JOIN trip_expenses E ON E.trip_id=T.trip_id WHERE T.trip_id=$trip_id GROUP BY T.trip_id")->fetch_row();
	$note = $s[2] > 0 ? "Missing receipt(s) confirmed with driver and accepted" : "";
	$balance = $s[0] - $s[1];
	q("UPDATE trips SET settlement_status='Settled', settled_by=" . $U['priya'] . ", settled_at='" . ts($at) . "', settlement_note='" . $note . "' WHERE trip_id=$trip_id");
	event($trip_id, 'Settled', ($balance >= 0 ? "Driver returns Rs " : "Company reimburses driver Rs ") . number_format(abs($balance), 2) . ($note ? "; $note" : ""), $at, $U['priya'], 'Admin');
}

// Which vehicle can run this route on that date (class preference, not double-booked, documents valid, not in workshop)
function choose_vehicle($code, $date, $busy) {
	global $V, $ROUTES, $MAINT_FROM, $USED;
	$order = array('small' => array('small', 'medium'), 'medium' => array('medium', 'large'), 'large' => array('large'));
	foreach ($order[$ROUTES[$code]['class']] as $class) {
		$candidates = $V;
		uksort($candidates, function ($a, $b) use ($USED) { return ($USED[$a] ?? 0) - ($USED[$b] ?? 0) ?: $a - $b; });
		foreach ($candidates as $i => $v) {
			if ($v['class'] != $class || in_array($i, $busy) || ($i == 5 && $date >= $MAINT_FROM)) {
				continue;
			}
			$valid = true;
			foreach ($v['docs'] as $expiry) {
				if ($expiry < $date) { $valid = false; }
			}
			if ($valid) {
				$USED[$i] = ($USED[$i] ?? 0) + 1;
				return $i;
			}
		}
	}
	return null;
}

echo "Simulating the last 7 days...\n";
$day_routes = array(array('PW', 'PE', 'MA', 'NS'), array('PW', 'PE', 'TH', 'BG'), array('PW', 'PE', 'MA'), array('PW', 'PE', 'TH', 'NS'),
	array('PW', 'PE', 'MA', 'BG'), array('PW', 'PE', 'TH'), array('PW', 'PE', 'MA', 'NS'));
$driver_turn = 0;
$USED = array();
for ($d = 7; $d >= 1; $d--) {
	$date = day(strtotime("-$d days", $now));
	$busy = array();
	$busy_drivers = array();
	foreach ($day_routes[7 - $d] as $code) {
		$vi = choose_vehicle($code, $date, $busy);
		if ($vi === null) { continue; }
		$busy[] = $vi;
		for ($k = 0; $k < count($DRIVERS); $k++) {
			$driver = $DRIVERS[($driver_turn + $k) % count($DRIVERS)];
			$need = fleet::$VEHICLE_LICENSE[$VEHICLES[$vi][1]];
			if (!in_array($driver, $busy_drivers) && count(array_intersect(explode(",", $KYC[$driver][0]), $need)) > 0) {
				break;
			}
		}
		$driver_turn += $k + 1;
		$busy_drivers[] = $driver;
		// the breakdown day's orders must also fit the smaller replacement truck (Tata 407, 900 units)
		$orders = make_orders($code, $date, ($d == 4 && $code == 'NS') ? min($V[$vi]['units'], $V[4]['units']) : $V[$vi]['units']);
		$created = strtotime($date . ' 19:00 -1 day');

		// 4 days ago the Tata 407 broke down before leaving: trip cancelled, orders moved to another truck
		if ($d == 4 && $code == 'NS') {
			$cancelled = new_trip($code, $date, $vi, $driver, 'Cancelled', $created);
			q("UPDATE trips SET close_note='Vehicle breakdown - radiator leak at depot' WHERE trip_id=$cancelled");
			event($cancelled, 'Created', "Vehicle " . $V[$vi]['reg'] . ", " . count($orders) . " orders", $created, $U['priya'], 'Admin');
			event($cancelled, 'Cancelled', 'Vehicle breakdown - radiator leak at depot', strtotime($date . ' 06:40'), $U['priya'], 'Admin');
			$vi = choose_vehicle($code, $date, $busy) ?? $vi;
			$busy[] = $vi;
			$created = strtotime($date . ' 06:50');
		}
		$trip_id = new_trip($code, $date, $vi, $driver, 'Completed', $created);
		attach($trip_id, $orders, $driver);
		q("INSERT INTO route_accept (route_id,driver_id,route_accept_date,status) VALUES (" . $ROUTES[$code]['route_id'] . "," . $U[$driver] . ",'$date','YES')");
		$start = strtotime($date . ' 0' . mt_rand(6, 7) . ':' . str_pad(mt_rand(0, 59), 2, '0', STR_PAD_LEFT));
		event($trip_id, 'Created', "Vehicle " . $V[$vi]['reg'] . ", driver " . $driver . ", " . count($orders) . " orders", $created, $U['priya'], 'Admin');
		event($trip_id, 'Loaded', load_note($orders), $start - 1800, $U['priya'], 'Admin');
		$hours = $ROUTES[$code]['hours'] * mt_rand(90, 115) / 100;
		$res = drive($trip_id, $code, $vi, $driver, $orders, $start, $hours);
		finish($trip_id, $vi, $driver, $start, $res['end'], $code);
	}
}

echo "Setting up today and tomorrow...\n";
$tomorrow = day(strtotime('+1 day', $now));

// 1) Thane trip on the road right now (started 2.5 h ago; 2 stops done, driving to the 3rd)
$start = $now - 9000;
$date = day($start);
$vi = 2; $driver = 'vijay';
$orders = make_orders('TH', $date, $V[$vi]['units'], false);
$trip_id = new_trip('TH', $date, $vi, $driver, 'In Transit', $start - 50000);
attach($trip_id, $orders, $driver);
q("INSERT INTO route_accept (route_id,driver_id,route_accept_date,status) VALUES (" . $ROUTES['TH']['route_id'] . "," . $U[$driver] . ",'$date','YES')");
event($trip_id, 'Created', "Vehicle " . $V[$vi]['reg'] . ", driver vijay, " . count($orders) . " orders", $start - 50000, $U['priya'], 'Admin');
event($trip_id, 'Loaded', load_note($orders), $start - 1800, $U['priya'], 'Admin');
drive($trip_id, 'TH', $vi, $driver, $orders, $start, 0, $now, 2);
$odo = $V[$vi]['odo'];
q("UPDATE trips SET start_odometer=$odo, start_time='" . ts($start) . "' WHERE trip_id=$trip_id");
q("UPDATE vehicles SET status='On Trip' WHERE vehicle_id=" . $V[$vi]['id']);
event($trip_id, 'Started', "Odometer $odo km, " . count($orders) . " orders", $start, $U[$driver], 'Driver App', $DEPOT[0], $DEPOT[1]);

// 2) Pune West loaded and waiting at the dock
$vi = 0; $driver = 'ramesh';
$orders = make_orders('PW', $TODAY, $V[$vi]['units']);
$trip_id = new_trip('PW', $TODAY, $vi, $driver, 'Loaded', $now - 40000);
attach($trip_id, $orders, $driver);
event($trip_id, 'Created', "Vehicle " . $V[$vi]['reg'] . ", driver ramesh, " . count($orders) . " orders", $now - 40000, $U['priya'], 'Admin');
event($trip_id, 'Loaded', load_note($orders), $now - 1200, $U['priya'], 'Admin');

// 3) Nashik planned, not loaded yet
$vi = 4; $driver = 'anil';
$orders = make_orders('NS', $TODAY, $V[$vi]['units']);
$trip_id = new_trip('NS', $TODAY, $vi, $driver, 'Planned', $now - 38000);
attach($trip_id, $orders, $driver);
event($trip_id, 'Created', "Vehicle " . $V[$vi]['reg'] . ", driver anil, " . count($orders) . " orders", $now - 38000, $U['priya'], 'Admin');

// 4) Pune East ran early this morning and is back: completed, trip sheet waiting for approval
$vi = 3; $driver = 'suresh';
$start = $now - 6.5 * 3600;
$date = day($start);
$orders = make_orders('PE', $date, $V[$vi]['units']);
$trip_id = new_trip('PE', $date, $vi, $driver, 'Completed', $start - 40000);
attach($trip_id, $orders, $driver);
q("INSERT INTO route_accept (route_id,driver_id,route_accept_date,status) VALUES (" . $ROUTES['PE']['route_id'] . "," . $U[$driver] . ",'$date','YES')");
event($trip_id, 'Created', "Vehicle " . $V[$vi]['reg'] . ", driver suresh, " . count($orders) . " orders", $start - 40000, $U['priya'], 'Admin');
event($trip_id, 'Loaded', load_note($orders), $start - 1800, $U['priya'], 'Admin');
$res = drive($trip_id, 'PE', $vi, $driver, $orders, $start, 6);
finish($trip_id, $vi, $driver, $start, $res['end'], 'PE');

// 5) Mumbai orders for today with no vehicle yet (shows on the dispatch dashboard)
make_orders('MA', $TODAY, 450, false);

// 6) Tomorrow: Belagavi planned on the Bolero, Thane orders not planned yet
$vi = 1; $driver = 'sunil';
$orders = make_orders('BG', $tomorrow, $V[$vi]['units'], false);
$trip_id = new_trip('BG', $tomorrow, $vi, $driver, 'Planned', $now - 3600);
attach($trip_id, $orders, $driver);
event($trip_id, 'Created', "Vehicle " . $V[$vi]['reg'] . ", driver sunil, " . count($orders) . " orders", $now - 3600, $U['priya'], 'Admin');
make_orders('TH', $tomorrow, 400, false);

// ------------------------------------------------------------------------------------------------ finish vehicles + manifests
foreach ($V as $i => $v) {
	q("UPDATE vehicles SET odometer_km=" . $v['odo'] . " WHERE vehicle_id=" . $v['id']);
}
q("UPDATE vehicles SET status='Maintenance' WHERE registration_no='MH04RT2206'");
q("UPDATE manifests M SET
	num_routes=(SELECT COUNT(DISTINCT route_id) FROM deliveries D WHERE D.manifest_id=M.manifest_id),
	num_areas=(SELECT COUNT(DISTINCT R.area_id) FROM deliveries D JOIN routes R ON R.route_id=D.route_id WHERE D.manifest_id=M.manifest_id),
	num_deliveries=(SELECT COUNT(*) FROM deliveries D WHERE D.manifest_id=M.manifest_id),
	num_packages=(SELECT COALESCE(SUM(P.qty),0) FROM delivery_packages P JOIN deliveries D ON D.delivery_id=P.delivery_id WHERE D.manifest_id=M.manifest_id),
	num_items=(SELECT COALESCE(SUM(I.qty),0) FROM delivery_items I JOIN deliveries D ON D.delivery_id=I.delivery_id WHERE D.manifest_id=M.manifest_id)");

// ------------------------------------------------------------------------------------------------ summary
echo "\nDone.\n";
foreach (array('users', 'vehicles', 'routes', 'customers', 'manifests', 'deliveries', 'delivery_items', 'trips', 'trip_expenses', 'trip_events', 'driver_location') as $t) {
	printf("  %-16s %6d\n", $t, q("SELECT COUNT(*) FROM `$t`")->fetch_row()[0]);
}
foreach (q("SELECT status, COUNT(*) FROM trips GROUP BY status") as $row) {
	printf("  trips %-10s %6d\n", $row['status'], $row['COUNT(*)']);
}
