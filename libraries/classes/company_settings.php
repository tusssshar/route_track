<?php
/*
 * Per-client settings stored in the client's repository DB (table company_settings):
 * timezone, currency, distance unit and fuel unit. Defaults are India.
 *
 * The timezone decides the business day ("today" for trips, orders, reports) and every time shown or stored.
 * bootstrap.php applies it to PHP and to the MySQL session, so date() / NOW() / CURDATE() always agree.
 * DATETIME columns hold wall-clock time of the company timezone; changing the timezone converts them.
 */
class company_settings {

	public static $DEFAULTS = array("timezone" => "Asia/Kolkata", "currency" => "INR", "distance_unit" => "km", "fuel_unit" => "L",
		"company_name" => "RouteTrac Logistics", "company_address" => "", "company_tax_id" => "",
		// branding / look (Staff -> Company Settings -> Branding & Theme)
		"system_name" => "RouteTrac", "system_title" => "Fleet & Delivery Management", "theme" => "highway",
		"primary_color" => "", "accent_color" => "", "font" => "Poppins", "logo_file" => "",
		"support_phone" => "", "support_email" => "", "footer_text" => "");
	// Free text printed on trip sheets and delivery receipts
	public static $TEXT_SETTINGS = array("company_name" => "Company name", "company_address" => "Address", "company_tax_id" => "Tax ID (GSTIN / VAT / EIN)");

	// code => [symbol, name]
	public static $CURRENCIES = array(
		"INR" => array("₹", "Indian Rupee"), "USD" => array("$", "US Dollar"), "EUR" => array("€", "Euro"), "GBP" => array("£", "Pound Sterling"),
		"AED" => array("AED ", "UAE Dirham"), "SAR" => array("SAR ", "Saudi Riyal"), "QAR" => array("QAR ", "Qatari Riyal"), "OMR" => array("OMR ", "Omani Rial"),
		"KWD" => array("KWD ", "Kuwaiti Dinar"), "SGD" => array("S$", "Singapore Dollar"), "MYR" => array("RM ", "Malaysian Ringgit"),
		"AUD" => array("A$", "Australian Dollar"), "NZD" => array("NZ$", "New Zealand Dollar"), "CAD" => array("C$", "Canadian Dollar"),
		"ZAR" => array("R ", "South African Rand"), "KES" => array("KSh ", "Kenyan Shilling"), "NGN" => array("₦", "Nigerian Naira"),
		"NPR" => array("Rs ", "Nepalese Rupee"), "LKR" => array("Rs ", "Sri Lankan Rupee"), "BDT" => array("৳", "Bangladeshi Taka"),
	);
	public static $DISTANCE_UNITS = array("km" => "Kilometres (km)", "mi" => "Miles (mi)");

	/*
	 * Theme presets. nav = top bar, primary = buttons / links / highlights, accent = second highlight,
	 * stripe = the dashed "road marking" under the top bar, bg / surface / text / muted / border = page colours.
	 */
	public static $THEMES = array(
		"highway" => array("name" => "Highway", "nav" => "#1e293b", "nav_text" => "#e2e8f0", "primary" => "#f59e0b", "accent" => "#2563eb", "stripe" => "#fbbf24",
			"bg" => "#f1f5f9", "surface" => "#ffffff", "text" => "#1e293b", "muted" => "#64748b", "border" => "#e2e8f0"),
		"night_drive" => array("name" => "Night Drive", "nav" => "#020617", "nav_text" => "#cbd5e1", "primary" => "#38bdf8", "accent" => "#a78bfa", "stripe" => "#facc15",
			"bg" => "#0f172a", "surface" => "#111c33", "text" => "#e2e8f0", "muted" => "#94a3b8", "border" => "#1e2b47", "dark" => true),
		"green_route" => array("name" => "Green Route", "nav" => "#064e3b", "nav_text" => "#d1fae5", "primary" => "#10b981", "accent" => "#f59e0b", "stripe" => "#fde047",
			"bg" => "#f0fdf4", "surface" => "#ffffff", "text" => "#14301f", "muted" => "#5f7d6c", "border" => "#d6eadc"),
		"express_red" => array("name" => "Express Red", "nav" => "#3b0a10", "nav_text" => "#fde2e4", "primary" => "#e11d48", "accent" => "#f97316", "stripe" => "#fbbf24",
			"bg" => "#fff7f7", "surface" => "#ffffff", "text" => "#2a1215", "muted" => "#7c5a5e", "border" => "#f3dadd"),
		"ocean_freight" => array("name" => "Ocean Freight", "nav" => "#0c4a6e", "nav_text" => "#e0f2fe", "primary" => "#0284c7", "accent" => "#14b8a6", "stripe" => "#7dd3fc",
			"bg" => "#f0f9ff", "surface" => "#ffffff", "text" => "#0c2a3e", "muted" => "#557189", "border" => "#d3e8f5"),
		"desert_road" => array("name" => "Desert Road", "nav" => "#43260d", "nav_text" => "#fdecc8", "primary" => "#d97706", "accent" => "#0f766e", "stripe" => "#fcd34d",
			"bg" => "#fffbeb", "surface" => "#ffffff", "text" => "#3b2610", "muted" => "#8a6d4a", "border" => "#f1e3c2"),
		"purple_fleet" => array("name" => "Purple Fleet", "nav" => "#2e1065", "nav_text" => "#ede9fe", "primary" => "#7c3aed", "accent" => "#ec4899", "stripe" => "#f0abfc",
			"bg" => "#faf5ff", "surface" => "#ffffff", "text" => "#24123f", "muted" => "#6f5d8a", "border" => "#e9ddfa"),
		"clean_white" => array("name" => "Clean White", "nav" => "#ffffff", "nav_text" => "#1e293b", "primary" => "#2563eb", "accent" => "#f59e0b", "stripe" => "#f59e0b",
			"bg" => "#f8fafc", "surface" => "#ffffff", "text" => "#0f172a", "muted" => "#64748b", "border" => "#e5e7eb"),
		"classic" => array("name" => "Classic", "nav" => "#f8f8f8", "nav_text" => "#333333", "primary" => "#337ab7", "accent" => "#faa81a", "stripe" => "#faa81a",
			"bg" => "#ffffff", "surface" => "#ffffff", "text" => "#333333", "muted" => "#777777", "border" => "#dddddd"),
	);
	public static $FONTS = array("Poppins", "Inter", "Roboto", "Montserrat", "Nunito", "Open Sans", "Lato", "Rubik", "Mukta");
	const LOGO_MAX_BYTES = 1048576;
	public static $FUEL_UNITS = array("L" => "Litres (L)", "gal" => "US Gallons (gal)");

	private static $values = null;

	//------------------------------------------------------------------------------------------------------------------
	// loading / applying

	static function load($force = false) {
		global $oGlobalConnection;
		if (self::$values !== null && !$force) {
			return self::$values;
		}
		self::$values = self::$DEFAULTS;
		// A client DB that has not run the migration yet simply keeps the defaults
		$res = ($oGlobalConnection instanceof mysqli) ? @$oGlobalConnection->query("SELECT setting_key, setting_value FROM company_settings") : false;
		if ($res) {
			while ($row = $res->fetch_assoc()) {
				self::$values[$row['setting_key']] = $row['setting_value'];
			}
		}
		if (!in_array(self::$values['timezone'], DateTimeZone::listIdentifiers())) {
			self::$values['timezone'] = self::$DEFAULTS['timezone'];
		}
		return self::$values;
	}

	// Called by bootstrap.php: company timezone for PHP and the MySQL session
	static function apply() {
		global $oGlobalConnection;
		self::load(true);
		date_default_timezone_set(self::$values['timezone']);
		self::sync_connection($oGlobalConnection);
	}

	// MySQL session follows the PHP timezone (an offset, so the MySQL tz tables are not needed)
	static function sync_connection($connection) {
		if ($connection instanceof mysqli) {
			@$connection->query("SET time_zone='" . date('P') . "'");
		}
	}

	static function get($key) {
		$values = self::load();
		return isset($values[$key]) ? $values[$key] : (isset(self::$DEFAULTS[$key]) ? self::$DEFAULTS[$key] : "");
	}

	//------------------------------------------------------------------------------------------------------------------
	// display helpers

	static function currency_symbol() {
		$code = self::get("currency");
		return isset(self::$CURRENCIES[$code]) ? self::$CURRENCIES[$code][0] : $code . " ";
	}

	static function money($amount) {
		return self::currency_symbol() . number_format((float) $amount, 2);
	}

	static function distance_unit() {
		return self::get("distance_unit");
	}

	static function fuel_unit() {
		return self::get("fuel_unit");
	}

	// "IST" / "EDT" ... (PHP gives "+04" style names for zones without an abbreviation)
	static function tz_abbr() {
		return date('T');
	}

	static function tz_label() {
		return self::get("timezone") . " (UTC" . date('P') . ")";
	}

	//------------------------------------------------------------------------------------------------------------------
	// branding / theme

	// The theme's colours with the custom primary / accent colour applied
	static function theme() {
		$key = self::get("theme");
		$t = isset(self::$THEMES[$key]) ? self::$THEMES[$key] : self::$THEMES["highway"];
		foreach (array("primary_color" => "primary", "accent_color" => "accent") as $setting => $slot) {
			if (self::valid_color(self::get($setting))) {
				$t[$slot] = self::get($setting);
			}
		}
		$t["dark"] = !empty($t["dark"]);
		$t["font"] = in_array(self::get("font"), self::$FONTS) ? self::get("font") : "Poppins";
		return $t;
	}

	static function valid_color($value) {
		return (bool) preg_match('/^#[0-9a-fA-F]{6}$/', (string) $value);
	}

	// Readable text colour (white or near-black) on a background colour
	static function on_color($hex) {
		$hex = ltrim($hex, "#");
		$c = array_map(function ($i) use ($hex) {
			$v = hexdec(substr($hex, $i, 2)) / 255;
			return $v <= 0.03928 ? $v / 12.92 : pow(($v + 0.055) / 1.055, 2.4);
		}, array(0, 2, 4));
		$luminance = 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
		return $luminance > 0.4 ? "#111827" : "#ffffff";
	}

	// CSS custom properties for a theme (also used by the live preview on the settings page)
	static function css_vars($t) {
		return "--rt-on-primary:" . self::on_color($t['primary']) . ";--rt-on-accent:" . self::on_color($t['accent']) . ";"
			. "--rt-nav:" . $t['nav'] . ";--rt-nav-text:" . $t['nav_text'] . ";--rt-primary:" . $t['primary'] . ";--rt-accent:" . $t['accent']
			. ";--rt-stripe:" . $t['stripe'] . ";--rt-bg:" . $t['bg'] . ";--rt-surface:" . $t['surface'] . ";--rt-text:" . $t['text']
			. ";--rt-muted:" . $t['muted'] . ";--rt-border:" . $t['border'] . ";--rt-font:'" . $t['font'] . "',system-ui,-apple-system,'Segoe UI',sans-serif;";
	}

	static function logo_url() {
		$file = basename(self::get("logo_file"));
		return ($file != "" && is_file(ROOTPATH . "/uploads/branding/" . $file)) ? SITEROOT . "/uploads/branding/" . rawurlencode($file) : "";
	}

	static function footer_text() {
		$text = self::get("footer_text");
		return $text != "" ? str_replace("{year}", date("Y"), $text) : "© " . date("Y") . " " . self::get("system_name") . ". All rights reserved.";
	}

	// Font, theme stylesheet, colours and favicon for <head>
	static function head_tags() {
		$t = self::theme();
		$css = "/admin/templates/css/routetrac-theme.css";
		$logo = self::logo_url();
		return '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
			. '<link href="https://fonts.googleapis.com/css2?family=' . str_replace(" ", "+", $t['font']) . ':wght@400;500;600;700&display=swap" rel="stylesheet">'
			. '<link rel="stylesheet" href="' . SITEROOT . $css . '?v=' . @filemtime(ROOTPATH . $css) . '">'
			. '<style>:root{' . self::css_vars($t) . '}</style>'
			. ($logo != "" ? '<link rel="icon" href="' . htmlspecialchars($logo) . '">' : '');
	}

	// Brand block (logo + name) for the top bar and the login page
	static function brand_html($with_title = false) {
		$logo = self::logo_url();
		$name = htmlspecialchars(self::get("system_name"));
		$mark = $logo != "" ? '<img src="' . htmlspecialchars($logo) . '" alt="" class="rt-logo">'
			: '<span class="rt-logo rt-logo-mark"><i class="fa fa-truck"></i></span>';
		return $mark . '<span class="rt-brand-text"><span class="rt-brand-name">' . $name . '</span>'
			. ($with_title ? '<span class="rt-brand-title">' . htmlspecialchars(self::get("system_title")) . '</span>' : '') . '</span>';
	}

	// Store an uploaded logo (png / jpg / webp, max 1 MB) under uploads/branding. Returns array(file, error).
	static function save_logo($field) {
		if (empty($_FILES[$field]) || $_FILES[$field]['error'] == UPLOAD_ERR_NO_FILE) {
			return array("", "");
		}
		$f = $_FILES[$field];
		if ($f['error'] != UPLOAD_ERR_OK) {
			return array("", "Logo upload failed (code " . $f['error'] . ")");
		}
		if ($f['size'] > self::LOGO_MAX_BYTES) {
			return array("", "Logo must be smaller than 1 MB");
		}
		// check the real image type, not the file name (SVG is refused: it can carry scripts)
		$info = @getimagesize($f['tmp_name']);
		$types = array(IMAGETYPE_PNG => "png", IMAGETYPE_JPEG => "jpg", IMAGETYPE_WEBP => "webp");
		if (!$info || !isset($types[$info[2]])) {
			return array("", "Logo must be a PNG, JPG or WEBP image");
		}
		$dir = ROOTPATH . "/uploads/branding";
		if (!is_dir($dir)) {
			mkdir($dir, 0755, true);
		}
		$file = "logo_" . date("YmdHis") . "." . $types[$info[2]];
		if (!move_uploaded_file($f['tmp_name'], $dir . "/" . $file)) {
			return array("", "Could not store the logo");
		}
		return array($file, "");
	}

	//------------------------------------------------------------------------------------------------------------------
	// changing settings

	/*
	 * Why a setting cannot be changed any more ('' = it can). Amounts and distances already recorded are not converted,
	 * so currency / units are fixed once data exists in them. The timezone can always change (times are converted).
	 */
	static function change_blocker($key) {
		switch ($key) {
			case "currency":
				$n = self::count("SELECT COUNT(*) FROM trip_expenses");
				return $n > 0 ? $n . " expenses are already recorded in " . self::get("currency") . " (amounts are not converted)" : "";
			case "distance_unit":
				$n = self::count("SELECT COUNT(*) FROM trips WHERE start_odometer IS NOT NULL") + self::count("SELECT COUNT(*) FROM vehicles WHERE odometer_km>0");
				return $n > 0 ? "odometer readings are already recorded in " . self::get("distance_unit") . " (readings are not converted)" : "";
			case "fuel_unit":
				$n = self::count("SELECT COUNT(*) FROM trip_expenses WHERE fuel_litres>0");
				return $n > 0 ? $n . " fuel fills are already recorded in " . self::get("fuel_unit") . " (volumes are not converted)" : "";
		}
		return "";
	}

	private static function count($query) {
		$row = fleet::row(str_replace("SELECT COUNT(*)", "SELECT COUNT(*) AS c", $query));
		return (int) $row['c'];
	}

	/*
	 * Save the settings form. Returns array(errors, messages).
	 * $convert_times: on a timezone change, shift every stored DATETIME so history keeps meaning the same moment.
	 */
	static function save($input, $user_id, $convert_times = true) {
		$current = self::load(true);
		$errors = array();
		$messages = array();
		$new = array();
		foreach (self::$DEFAULTS as $key => $default) {
			$new[$key] = isset($input[$key]) && !is_array($input[$key]) ? trim((string) $input[$key]) : $current[$key];
		}
		// The logo only changes through an upload (save_logo) or "remove logo", never from a form value
		$new['logo_file'] = $current['logo_file'];
		if (!empty($input['__logo_file'])) {
			$new['logo_file'] = basename($input['__logo_file']);
		} elseif (!empty($input['remove_logo'])) {
			$new['logo_file'] = "";
		}
		if (!in_array($new['timezone'], DateTimeZone::listIdentifiers())) {
			$errors[] = "Please select a valid timezone";
		}
		if (!isset(self::$CURRENCIES[$new['currency']])) {
			$errors[] = "Please select a currency";
		}
		if (!isset(self::$DISTANCE_UNITS[$new['distance_unit']])) {
			$errors[] = "Please select a distance unit";
		}
		if (!isset(self::$FUEL_UNITS[$new['fuel_unit']])) {
			$errors[] = "Please select a fuel unit";
		}
		if ($new['company_name'] == "") {
			$errors[] = "Please enter the company name";
		}
		if ($new['system_name'] == "") {
			$errors[] = "Please enter the system name";
		}
		if (!isset(self::$THEMES[$new['theme']])) {
			$errors[] = "Please select a theme";
		}
		foreach (array("primary_color" => "Primary colour", "accent_color" => "Accent colour") as $key => $label) {
			if ($new[$key] != "" && !self::valid_color($new[$key])) {
				$errors[] = $label . " must look like #1e293b";
			}
		}
		if (!in_array($new['font'], self::$FONTS)) {
			$errors[] = "Please select a font";
		}
		if ($new['support_email'] != "" && !filter_var($new['support_email'], FILTER_VALIDATE_EMAIL)) {
			$errors[] = "Support email is not valid";
		}
		foreach (array("system_name" => "System name", "system_title" => "System title", "support_phone" => "Support phone", "footer_text" => "Footer text") as $key => $label) {
			if (strlen($new[$key]) > 255) {
				$errors[] = $label . " is too long (max 255 characters)";
			}
		}
		foreach (self::$TEXT_SETTINGS as $key => $label) {
			if (strlen($new[$key]) > 255) {
				$errors[] = $label . " is too long (max 255 characters)";
			}
		}
		foreach (array("currency" => "Currency", "distance_unit" => "Distance unit", "fuel_unit" => "Fuel unit") as $key => $label) {
			if ($new[$key] != $current[$key] && ($why = self::change_blocker($key)) != "") {
				$errors[] = $label . " cannot be changed: " . $why;
			}
		}
		if (count($errors) > 0) {
			return array($errors, $messages);
		}

		if ($new['timezone'] != $current['timezone']) {
			if ($convert_times) {
				$rows = self::convert_times($current['timezone'], $new['timezone']);
				$messages[] = "Timezone changed to " . $new['timezone'] . "; " . $rows . " stored times converted";
			} else {
				$messages[] = "Timezone changed to " . $new['timezone'] . "; stored times were kept as they are";
			}
		}
		foreach ($new as $key => $value) {
			if ($value != $current[$key]) {
				fleet::exec("INSERT INTO company_settings (setting_key, setting_value, updated_by, updated_at) VALUES ('" . sanitize($key) . "', '" . sanitize($value) . "', "
					. (int) $user_id . ", '" . date("Y-m-d H:i:s") . "') ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), updated_by=VALUES(updated_by), updated_at=VALUES(updated_at)");
				if ($key != "timezone") {
					$messages[] = (isset(self::$TEXT_SETTINGS[$key]) ? self::$TEXT_SETTINGS[$key] : $key) . " updated";
				}
			}
		}
		// the replaced / removed logo file is no longer needed
		if ($new['logo_file'] != $current['logo_file'] && $current['logo_file'] != "" && is_file(ROOTPATH . "/uploads/branding/" . basename($current['logo_file']))) {
			@unlink(ROOTPATH . "/uploads/branding/" . basename($current['logo_file']));
		}
		self::apply();
		return array(array(), $messages);
	}

	/*
	 * Re-express every DATETIME value of this DB from wall-clock time in $from to wall-clock time in $to.
	 * Handles daylight saving: the shift is worked out per period in which both zones keep the same UTC offset.
	 * TIMESTAMP columns are stored in UTC by MySQL and follow the session timezone by themselves; DATE columns are
	 * business dates (trip date, scheduled delivery, document expiry) and stay as they are.
	 */
	static function convert_times($from, $to) {
		$from_tz = new DateTimeZone($from);
		$to_tz = new DateTimeZone($to);
		$columns = fleet::rows("SELECT table_name AS t, column_name AS c FROM information_schema.columns
			WHERE table_schema=DATABASE() AND data_type='datetime' ORDER BY table_name, column_name");
		$affected = 0;
		foreach ($columns as $col) {
			$t = "`" . str_replace("`", "", $col['t']) . "`";
			$c = "`" . str_replace("`", "", $col['c']) . "`";
			$span = fleet::row("SELECT MIN($c) AS lo, MAX($c) AS hi FROM $t WHERE $c > '1000-01-01'");
			if (!$span || $span['lo'] === null) {
				continue;
			}
			// UTC instants covering the data (+-2 days of slack for the offsets)
			$lo = strtotime($span['lo'] . " UTC") - 2 * 86400;
			$hi = strtotime($span['hi'] . " UTC") + 2 * 86400;
			$instants = array($lo, $hi);
			foreach (array($from_tz, $to_tz) as $tz) {
				foreach ($tz->getTransitions($lo, $hi) as $transition) {
					$instants[] = (int) $transition['ts'];
				}
			}
			$instants = array_values(array_unique(array_filter($instants, function ($x) use ($lo, $hi) { return $x >= $lo && $x <= $hi; })));
			sort($instants);

			$cases = array();
			for ($i = 0; $i < count($instants) - 1; $i++) {
				$mid = new DateTime("@" . (int) (($instants[$i] + $instants[$i + 1]) / 2));
				$from_offset = $from_tz->getOffset($mid);
				$shift = $to_tz->getOffset($mid) - $from_offset;
				// period boundaries as wall-clock time of the source zone; first/last period open-ended
				$wall_lo = $i == 0 ? "1000-01-01 00:00:00" : gmdate("Y-m-d H:i:s", $instants[$i] + $from_offset);
				$wall_hi = $i == count($instants) - 2 ? "9999-12-31 23:59:59" : gmdate("Y-m-d H:i:s", $instants[$i + 1] + $from_offset);
				$cases[] = "WHEN $c >= '$wall_lo' AND $c < '$wall_hi' THEN $c + INTERVAL $shift SECOND";
			}
			// CASE reads the original value of each row, so a shifted value is never shifted twice
			$db = fleet::exec("UPDATE $t SET $c = CASE " . implode(" ", $cases) . " ELSE $c END WHERE $c > '1000-01-01'");
			$affected += $db->conn->affected_rows;
		}
		return $affected;
	}
}
?>
