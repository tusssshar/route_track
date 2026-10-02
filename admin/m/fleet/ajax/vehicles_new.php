<?php
	include_once("../../../../bootstrap.php");
	fleet::require_access();

	$vehicle_id = (int) (isset($_REQUEST['vehicle_id']) ? $_REQUEST['vehicle_id'] : 0);

	if (isset($_POST['submit_vehicle'])) {
		$errors = array();
		$registration_no = strtoupper(preg_replace('/\s+/', '', (string) $_POST['registration_no']));
		if ($registration_no == "" || !preg_match('/^[A-Z0-9\-]{4,20}$/', $registration_no)) {
			$errors[] = "Please enter a valid registration number (letters, digits, -)";
		} elseif (fleet::row("SELECT vehicle_id FROM vehicles WHERE registration_no='" . sanitize($registration_no) . "' AND vehicle_id<>" . $vehicle_id)) {
			$errors[] = "Registration number " . $registration_no . " already exists";
		}
		if (!in_array($_POST['vehicle_type'], fleet::$VEHICLE_TYPES)) {
			$errors[] = "Please select vehicle type";
		}
		if (!in_array($_POST['fuel_type'], fleet::$FUEL_TYPES)) {
			$errors[] = "Please select fuel type";
		}
		if (!in_array($_POST['status'], fleet::$VEHICLE_STATUSES)) {
			$errors[] = "Please select status";
		}
		foreach (array("capacity_kg" => "Capacity (kg)", "capacity_units" => "Capacity (units)", "odometer_km" => "Odometer") as $field => $label) {
			if ($_POST[$field] !== "" && (!is_numeric($_POST[$field]) || $_POST[$field] < 0)) {
				$errors[] = $label . " must be a positive number";
			}
		}
		foreach (fleet::$VEHICLE_DOCUMENTS as $field => $label) {
			if (trim($_POST[$field]) != "" && !fleet::valid_date(trim($_POST[$field]))) {
				$errors[] = $label . " expiry is not a valid date";
			}
		}

		$current = $vehicle_id > 0 ? fleet::vehicle($vehicle_id) : null;
		if ($vehicle_id > 0 && !$current) {
			$errors[] = "Vehicle not found";
		}
		$active_trip = $vehicle_id > 0 ? fleet::current_trip_for_vehicle($vehicle_id) : null;
		if ($current) {
			// "On Trip" is driven by trips; it can't be set or cleared by hand while a trip is running
			if ($_POST['status'] == "On Trip" && $current['status'] != "On Trip") {
				$errors[] = "Status On Trip is set automatically when a trip starts";
			}
			if ($current['status'] == "On Trip" && $_POST['status'] != "On Trip" && $active_trip && $active_trip['status'] == "In Transit") {
				$errors[] = "Vehicle is running trip " . $active_trip['trip_number'] . " - complete the trip first";
			}
			if ((int) $_POST['odometer_km'] < (int) $current['odometer_km']) {
				$errors[] = "Odometer cannot go back (last reading " . $current['odometer_km'] . " " . fleet::du() . ")";
			}
		} elseif ($_POST['status'] == "On Trip") {
			$errors[] = "Status On Trip is set automatically when a trip starts";
		}

		if (count($errors) == 0) {
			$db = new database();
			$db->data("registration_no", $registration_no);
			$db->data("vehicle_type", $_POST['vehicle_type']);
			$db->data("make_model", $_POST['make_model']);
			$db->data("fuel_type", $_POST['fuel_type']);
			$db->data("capacity_kg", (float) $_POST['capacity_kg']);
			$db->data("capacity_units", (int) $_POST['capacity_units']);
			$db->data("fastag_id", $_POST['fastag_id']);
			$db->data("odometer_km", (int) $_POST['odometer_km']);
			$db->data("status", $_POST['status']);
			$db->data("notes", $_POST['notes']);
			$dates = array();
			foreach (fleet::$VEHICLE_DOCUMENTS as $field => $label) {
				$dates[] = $field . "=" . fleet::date_sql($_POST[$field]);
			}
			if ($vehicle_id < 1) {
				$db->data("added_date", date("Y-m-d H:i:s"));
				$db->insert("vehicles");
				$vehicle_id = $db->id;
				// expiry dates may be NULL, which data()/insert() cannot write
				fleet::exec("UPDATE vehicles SET " . implode(", ", $dates) . " WHERE vehicle_id=" . (int) $vehicle_id);
				$msg = "New vehicle successfully added";
			} else {
				$db->where("vehicle_id=" . (int) $vehicle_id);
				$db->update("vehicles", implode(", ", $dates));
				$msg = "Vehicle successfully updated";
			}
			echo json_encode(array("success" => 1, "error" => "", "msg" => $msg));
		} else {
			echo json_encode(array("success" => 0, "error" => implode("<br>", array_map(array('fleet', 'h'), $errors)), "msg" => ""));
		}
		exit;
	}

	$v = $vehicle_id > 0 ? fleet::vehicle($vehicle_id) : null;
	if (!$v) {
		$vehicle_id = 0;
		$v = array("registration_no" => "", "vehicle_type" => "", "make_model" => "", "fuel_type" => "Diesel", "capacity_kg" => "", "capacity_units" => "",
			"fastag_id" => "", "odometer_km" => "0", "insurance_expiry" => "", "fitness_expiry" => "", "permit_expiry" => "", "pollution_expiry" => "",
			"status" => "Available", "notes" => "");
	}
?>
<script>
jQuery(document).ready(function(){
	$("#registration_no").focus();
	jQuery("#meAdminForm").validate({
		errorElement:'div',
		rules: {
			registration_no: { required: true },
			vehicle_type: { required: true },
			capacity_units: { digits: true },
			capacity_kg: { number: true },
			odometer_km: { required: true, digits: true }
		},
		messages: {
			registration_no: { required: "Please enter registration number" },
			vehicle_type: { required: "Please select vehicle type" }
		},
		success: function(label){ label.hide(); },
		submitHandler: function(form) {
			jQuery("#divAlertSuccess, #divAlertError").hide();
			loadingIndicator('start');
			$.post("ajax/vehicles_new.php", $("#meAdminForm").serialize() + "&submit_vehicle=1", function(data){
				loadingIndicator('end');
				if(data.success == 1){
					loadDefaultList();
					$("#successMsg").html(data.msg).show().fadeOut(5000);
					ajaxNewModifyFormCancel();
				}else{
					$("#divAlertError").html(data.error).show();
				}
			}, "json");
		}
	});
});
</script>
<div class="alert alert-danger" id="divAlertError" style="display:none"></div>
<div class="panel panel-info">
  <div class="panel-heading"><?= ($vehicle_id > 0 ? "Update" : "Add New") ?> Vehicle</div>
  <div class="panel-body">
    <form class="form-horizontal" role="form" name="meAdminForm" id="meAdminForm" action="" method="post">
      <input type="hidden" name="vehicle_id" value="<?= $vehicle_id ?>">
      <div class="form-group">
        <label for="registration_no" class="col-sm-4 control-label"><span class="error">*</span>Registration No</label>
        <div class="col-sm-8"><input type="text" class="form-control" id="registration_no" name="registration_no" maxlength="20" value="<?= fleet::h($v['registration_no']) ?>" placeholder="MH12AB1234" style="text-transform:uppercase" /></div>
      </div>
      <div class="form-group">
        <label for="vehicle_type" class="col-sm-4 control-label"><span class="error">*</span>Vehicle Type</label>
        <div class="col-sm-8"><select class="form-control" id="vehicle_type" name="vehicle_type"><option value="">Select One</option><?= fleet::options(fleet::$VEHICLE_TYPES, $v['vehicle_type']) ?></select></div>
      </div>
      <div class="form-group">
        <label for="make_model" class="col-sm-4 control-label">Make / Model</label>
        <div class="col-sm-8"><input type="text" class="form-control" id="make_model" name="make_model" maxlength="100" value="<?= fleet::h($v['make_model']) ?>" placeholder="Tata Ace Gold" /></div>
      </div>
      <div class="form-group">
        <label for="fuel_type" class="col-sm-4 control-label"><span class="error">*</span>Fuel</label>
        <div class="col-sm-8"><select class="form-control" id="fuel_type" name="fuel_type"><?= fleet::options(fleet::$FUEL_TYPES, $v['fuel_type']) ?></select></div>
      </div>
      <div class="form-group">
        <label for="capacity_units" class="col-sm-4 control-label">Capacity</label>
        <div class="col-sm-4"><input type="text" class="form-control" id="capacity_units" name="capacity_units" value="<?= fleet::h($v['capacity_units']) ?>" placeholder="max units" title="Max item units per trip. Trips above this are blocked. Blank = not checked." /></div>
        <div class="col-sm-4"><input type="text" class="form-control" id="capacity_kg" name="capacity_kg" value="<?= $v['capacity_kg'] > 0 ? fleet::h($v['capacity_kg'] + 0) : "" ?>" placeholder="kg" /></div>
      </div>
      <div class="form-group">
        <label for="odometer_km" class="col-sm-4 control-label"><span class="error">*</span>Odometer (<?= fleet::du() ?>)</label>
        <div class="col-sm-8"><input type="text" class="form-control" id="odometer_km" name="odometer_km" value="<?= fleet::h($v['odometer_km']) ?>" /></div>
      </div>
      <div class="form-group">
        <label for="fastag_id" class="col-sm-4 control-label">FASTag ID</label>
        <div class="col-sm-8"><input type="text" class="form-control" id="fastag_id" name="fastag_id" maxlength="50" value="<?= fleet::h($v['fastag_id']) ?>" /></div>
      </div>
      <p class="text-muted" style="margin-left:15px">Document expiry dates (a vehicle with an expired document cannot be dispatched)</p>
<?php foreach (fleet::$VEHICLE_DOCUMENTS as $field => $label) { ?>
      <div class="form-group">
        <label for="<?= $field ?>" class="col-sm-4 control-label"><?= fleet::h($label) ?></label>
        <div class="col-sm-8"><input type="date" class="form-control" id="<?= $field ?>" name="<?= $field ?>" value="<?= fleet::h($v[$field]) ?>" /></div>
      </div>
<?php } ?>
      <div class="form-group">
        <label for="status" class="col-sm-4 control-label"><span class="error">*</span>Status</label>
        <div class="col-sm-8"><select class="form-control" id="status" name="status"><?= fleet::options(fleet::$VEHICLE_STATUSES, $v['status']) ?></select></div>
      </div>
      <div class="form-group">
        <label for="notes" class="col-sm-4 control-label">Notes</label>
        <div class="col-sm-8"><textarea class="form-control" id="notes" name="notes" rows="2"><?= fleet::h($v['notes']) ?></textarea></div>
      </div>
      <div class="form-group">
        <div class="col-sm-offset-4 col-sm-8">
          <button type="submit" name="submit" id="submit" class="btn btn-success"><i class="fa fa-floppy-o"></i> <?= ($vehicle_id > 0 ? "Update" : "Save") ?> </button>
          <span style="color:#999999">or</span>&nbsp; <span class="cancellink"><a href="javascript:void(0);" onClick="javascript:ajaxNewModifyFormCancel();">Cancel</a></span>
        </div>
      </div>
    </form>
  </div>
</div>
