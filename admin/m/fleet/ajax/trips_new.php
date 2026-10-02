<?php
	include_once("../../../../bootstrap.php");
	fleet::require_access();

	$trip_id = (int) (isset($_REQUEST['trip_id']) ? $_REQUEST['trip_id'] : 0);
	$user_id = (int) $_SESSION["routetrac-admin"]["al_id"];

	if (isset($_POST['submit_trip'])) {
		if ($trip_id < 1) {
			list($trip_id, $errors) = fleet::create_trip($_POST, $user_id);
			$msg = "Trip planned. Orders are now assigned to the driver.";
		} else {
			$errors = fleet::update_trip($trip_id, $_POST, $user_id);
			$msg = "Trip updated";
		}
		echo json_encode(array("success" => count($errors) == 0 ? 1 : 0, "error" => implode("<br>", array_map(array('fleet', 'h'), $errors)), "msg" => $msg, "trip_id" => $trip_id));
		exit;
	}

	$trip = $trip_id > 0 ? fleet::trip($trip_id) : null;
	if (!$trip) {
		$trip_id = 0;
		$trip = array("trip_date" => date("Y-m-d"), "route_id" => 0, "vehicle_id" => 0, "driver_id" => 0, "helper_name" => "", "notes" => "", "status" => "Planned");
	}
	$assignable = in_array($trip['status'], array("Planned", "Loaded"));
	$vehicles = fleet::rows("SELECT vehicle_id, registration_no, vehicle_type, capacity_units, status FROM vehicles
		WHERE status<>'Inactive' OR vehicle_id=" . (int) $trip['vehicle_id'] . " ORDER BY status='Available' DESC, registration_no");
	$drivers = fleet::rows("SELECT user_id, first_name, last_name, license_expiry FROM users
		WHERE (is_driver=1 AND status=1) OR user_id=" . (int) $trip['driver_id'] . " ORDER BY first_name, last_name");
	$routes = fleet::rows("SELECT route_id, name FROM routes ORDER BY name");
?>
<script>
jQuery(document).ready(function(){
	$("#trip_route_id, #trip_date, #trip_vehicle_id, #trip_driver_id").change(previewTrip);
	previewTrip();

	jQuery("#meAdminForm").validate({
		errorElement:'div',
		rules: {
			trip_date: { required: true },
			route_id: { required: true },
			vehicle_id: { required: true },
			driver_id: { required: true }
		},
		messages: {
			route_id: { required: "Please select route" },
			vehicle_id: { required: "Please select vehicle" },
			driver_id: { required: "Please select driver" }
		},
		success: function(label){ label.hide(); },
		submitHandler: function(form) {
			jQuery("#divAlertError").hide();
			loadingIndicator('start');
			$("#submit").prop("disabled", true);
			$.post("ajax/trips_new.php", $("#meAdminForm").serialize() + "&submit_trip=1", function(data){
				loadingIndicator('end');
				$("#submit").prop("disabled", false);
				if(data.success == 1){
					if(typeof loadDefaultList == "function"){ loadDefaultList(); }
					$("#successMsg").html(data.msg + " <a href='trip_detail.php?trip_id=" + data.trip_id + "'>Open trip</a>").show();
					ajaxNewModifyFormCancel();
					if(typeof onTripSaved == "function"){ onTripSaved(); }
				}else{
					$("#divAlertError").html(data.error).show();
				}
			}, "json");
		}
	});
});

// Shows orders / units vs capacity and every rule that would block this assignment
function previewTrip(){
	var k = { action: "preview", trip_id: <?= $trip_id ?>, trip_date: $("#trip_date").val(), route_id: $("#trip_route_id").val(),
		vehicle_id: $("#trip_vehicle_id").val(), driver_id: $("#trip_driver_id").val() };
	if(!k.route_id || !k.trip_date){
		$("#tripPreview").html("<span class='text-muted'>Select date and route to see the load.</span>");
		return;
	}
	$.post("ajax/trip_action.php", k, function(data){
		var html = "<strong>" + data.orders + "</strong> orders &middot; <strong>" + data.units + "</strong> units to load";
		if(data.capacity_units > 0){
			html += " &middot; vehicle capacity <strong>" + data.capacity_units + "</strong> units (" + data.utilisation + "% used)";
		}
		if(data.errors.length > 0){
			html += "<div class='fleet-warn' style='margin-top:6px'><i class='fa fa-exclamation-triangle'></i> " + data.errors.join("<br><i class='fa fa-exclamation-triangle'></i> ") + "</div>";
		}else if(k.vehicle_id && k.driver_id){
			html += "<div style='color:#3c763d;margin-top:6px'><i class='fa fa-check'></i> Vehicle and driver can be assigned</div>";
		}
		if(data.warnings.length > 0){
			html += "<div class='text-warning' style='margin-top:6px'>" + data.warnings.join("<br>") + "</div>";
		}
		$("#tripPreview").html(html);
	}, "json");
}
</script>
<div class="alert alert-danger" id="divAlertError" style="display:none"></div>
<div class="panel panel-info">
  <div class="panel-heading"><?= $trip_id > 0 ? "Update Trip " . fleet::h($trip['trip_number']) : "Plan New Trip" ?></div>
  <div class="panel-body">
    <form class="form-horizontal" role="form" name="meAdminForm" id="meAdminForm" action="" method="post">
      <input type="hidden" name="trip_id" value="<?= $trip_id ?>">
      <div class="form-group">
        <label for="trip_date" class="col-sm-3 control-label"><span class="error">*</span>Date</label>
        <div class="col-sm-9"><input type="date" class="form-control" id="trip_date" name="trip_date" value="<?= fleet::h($trip['trip_date']) ?>" <?= $trip_id > 0 ? "disabled" : "min='" . date("Y-m-d") . "'" ?> /></div>
      </div>
      <div class="form-group">
        <label for="trip_route_id" class="col-sm-3 control-label"><span class="error">*</span>Route</label>
        <div class="col-sm-9"><select class="form-control" id="trip_route_id" name="route_id" <?= $trip_id > 0 ? "disabled" : "" ?>><option value="">Select One</option>
          <?php foreach ($routes as $r) { ?><option value="<?= (int) $r['route_id'] ?>" <?= $trip['route_id'] == $r['route_id'] ? "selected" : "" ?>><?= fleet::h($r['name']) ?></option><?php } ?>
        </select></div>
      </div>
      <div class="form-group">
        <label for="trip_vehicle_id" class="col-sm-3 control-label"><span class="error">*</span>Vehicle</label>
        <div class="col-sm-9"><select class="form-control" id="trip_vehicle_id" name="vehicle_id" <?= $assignable ? "" : "disabled" ?>><option value="">Select One</option>
          <?php foreach ($vehicles as $v) { ?><option value="<?= (int) $v['vehicle_id'] ?>" <?= $trip['vehicle_id'] == $v['vehicle_id'] ? "selected" : "" ?>><?= fleet::h($v['registration_no'] . " - " . $v['vehicle_type'] . ($v['capacity_units'] > 0 ? " (" . $v['capacity_units'] . " units)" : "") . ($v['status'] != "Available" ? " [" . $v['status'] . "]" : "")) ?></option><?php } ?>
        </select></div>
      </div>
      <div class="form-group">
        <label for="trip_driver_id" class="col-sm-3 control-label"><span class="error">*</span>Driver</label>
        <div class="col-sm-9"><select class="form-control" id="trip_driver_id" name="driver_id" <?= $assignable ? "" : "disabled" ?>><option value="">Select One</option>
          <?php foreach ($drivers as $d) { ?><option value="<?= (int) $d['user_id'] ?>" <?= $trip['driver_id'] == $d['user_id'] ? "selected" : "" ?>><?= fleet::h(fleet::driver_name($d) . ($d['license_expiry'] ? " (licence till " . $d['license_expiry'] . ")" : "")) ?></option><?php } ?>
        </select></div>
      </div>
      <div class="form-group">
        <label for="helper_name" class="col-sm-3 control-label">Helper</label>
        <div class="col-sm-9"><input type="text" class="form-control" id="helper_name" name="helper_name" maxlength="100" value="<?= fleet::h($trip['helper_name']) ?>" placeholder="Loader / co-driver (optional)" /></div>
      </div>
      <div class="form-group">
        <label for="notes" class="col-sm-3 control-label">Notes</label>
        <div class="col-sm-9"><textarea class="form-control" id="notes" name="notes" rows="2"><?= fleet::h($trip['notes']) ?></textarea></div>
      </div>
      <div class="form-group">
        <div class="col-sm-offset-3 col-sm-9"><div id="tripPreview" class="well well-sm" style="margin:0"></div></div>
      </div>
      <?php if (!$assignable) { ?><p class="text-muted col-sm-offset-3">Vehicle and driver are locked once the trip is <?= fleet::h($trip['status']) ?>.</p><?php } ?>
      <div class="form-group">
        <div class="col-sm-offset-3 col-sm-9">
          <button type="submit" name="submit" id="submit" class="btn btn-success"><i class="fa fa-floppy-o"></i> <?= $trip_id > 0 ? "Update" : "Plan Trip" ?> </button>
          <span style="color:#999999">or</span>&nbsp; <span class="cancellink"><a href="javascript:void(0);" onClick="javascript:ajaxNewModifyFormCancel();">Cancel</a></span>
        </div>
      </div>
    </form>
  </div>
</div>
