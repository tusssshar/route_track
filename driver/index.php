<?php
	include_once(__DIR__ . "/../bootstrap.php");
	$logged_in = isset($_SESSION["routetrac-driver"]["driver_id"]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<meta name="theme-color" content="<?= fleet::h(company_settings::theme()["nav"]) ?>">
<title><?= fleet::h(company_settings::get("system_name")) ?> Driver</title>
<link rel="stylesheet" href="<?= SITEROOT ?>/admin/templates/css/bootstrap.min.css">
<link rel="stylesheet" href="<?= SITEROOT ?>/admin/templates/js/font-awesome-4.6.3/css/font-awesome.min.css">
<?= company_settings::head_tags() ?>
<style>
	body { background:var(--rt-bg); padding:0 0 40px !important; }
	.topbar { background:var(--rt-nav); color:var(--rt-nav-text); padding:10px 16px; font-size:17px; display:flex; align-items:center; gap:10px; position:relative; }
	.topbar::after { content:""; position:absolute; left:0; right:0; bottom:-5px; height:5px; background:repeating-linear-gradient(90deg,var(--rt-stripe) 0 22px,transparent 22px 38px) center/38px 2px repeat-x, color-mix(in srgb,var(--rt-nav) 85%,#000); }
	.topbar .rt-logo { height:32px; } .topbar .rt-logo-mark { width:32px; font-size:15px; } .topbar .rt-brand-name { font-size:16px !important; }
	.topbar a { margin-left:auto; color:var(--rt-nav-text); }
	.card { background:var(--rt-surface) !important; border:1px solid var(--rt-border); color:var(--rt-text); }
	.kpis div { background:var(--rt-head) !important; } .kpis span { color:var(--rt-muted) !important; }
	.topbar a { color:#fff; float:right; font-size:14px; }
	.wrap { padding:12px; max-width:600px; margin:0 auto; }
	.card { background:#fff; border-radius:8px; padding:14px; margin-bottom:12px; box-shadow:0 1px 3px rgba(0,0,0,.08); }
	.card h4 { margin-top:0; }
	.kpis { display:flex; flex-wrap:wrap; margin:0 -4px; }
	.kpis div { flex:1 1 45%; margin:4px; background:#f7f9fb; border-radius:6px; padding:8px; }
	.kpis b { display:block; font-size:20px; }
	.kpis span { font-size:12px; color:#777; }
	.btn-lg { width:100%; margin-top:6px; }
	.gps { padding:10px 14px; border-radius:8px; margin-bottom:12px; color:#fff; }
	.gps-on { background:#5cb85c; } .gps-off { background:#999; } .gps-err { background:#d9534f; }
	.badge-status { background:#337ab7; }
	#msg { position:fixed; left:12px; right:12px; bottom:12px; z-index:10; display:none; white-space:pre-line; }
	table { font-size:13px; }
	.dtabs { display:flex; gap:6px; margin-bottom:12px; }
	.dtabs a { flex:1; text-align:center; padding:10px 4px; border-radius:10px; background:var(--rt-surface); border:1px solid var(--rt-border); color:var(--rt-text); font-weight:600; font-size:13px; text-decoration:none; }
	.dtabs a.on { background:var(--rt-primary); border-color:var(--rt-primary); color:var(--rt-on-primary); }
	.htrip { cursor:pointer; }
	.htrip .top { display:flex; justify-content:space-between; gap:8px; }
	.htrip .meta { color:var(--rt-muted); font-size:12.5px; margin-top:2px; }
	.htrip .nums { display:flex; flex-wrap:wrap; gap:6px 14px; margin-top:8px; font-size:12.5px; }
	.htrip .settle { margin-top:8px; font-weight:600; font-size:13px; }
	.htrip .settle.ok { color:#15803d; } .htrip .settle.pending { color:#b45309; }
	.htrip .detail { display:none; margin-top:10px; border-top:1px dashed var(--rt-border); padding-top:8px; }
	.pill { display:inline-block; padding:2px 8px; border-radius:999px; font-size:11px; font-weight:700; background:var(--rt-head); color:var(--rt-muted); }
	.pill.ok { background:#dcfce7; color:#166534; } .pill.bad { background:#fee2e2; color:#991b1b; }
	.prow { display:flex; justify-content:space-between; padding:7px 0; border-bottom:1px solid var(--rt-border); } .prow span { color:var(--rt-muted); }
</style>
</head>
<body class="<?= company_settings::theme()["dark"] ? "rt-dark" : "" ?>">
<div class="topbar"><?= company_settings::brand_html() ?> <small style="opacity:.75">Driver</small> <a href="javascript:void(0)" id="logoutLink" style="display:none" onclick="logout()">Log out</a></div>
<div class="wrap">

  <!-- login -->
  <div class="card" id="loginCard" style="display:none">
    <h4>Driver Login</h4>
    <form onsubmit="login(); return false;">
      <div class="form-group"><input type="text" id="username" class="form-control input-lg" placeholder="Username or email" autocomplete="username" required></div>
      <div class="form-group"><input type="password" id="password" class="form-control input-lg" placeholder="Password" autocomplete="current-password" required></div>
      <button type="submit" class="btn btn-primary btn-lg">Log in</button>
    </form>
  </div>

  <div id="appArea" style="display:none">
    <div class="gps gps-off" id="gpsBox"><i class="fa fa-map-marker"></i> <span id="gpsText">Location sharing is off</span></div>

    <div class="dtabs">
      <a href="javascript:void(0)" class="on" data-pane="paneTrip" onclick="showPane(this)"><i class="fa fa-truck"></i> Current Trip</a>
      <a href="javascript:void(0)" data-pane="paneHistory" onclick="showPane(this)"><i class="fa fa-history"></i> My Trips</a>
      <a href="javascript:void(0)" data-pane="paneProfile" onclick="showPane(this)"><i class="fa fa-user"></i> Profile</a>
    </div>

    <div id="paneTrip">

    <div class="card" id="noTrip" style="display:none"><h4 id="driverName"></h4><p>No trip is assigned to you right now. Pull down to refresh later.</p>
      <button class="btn btn-default btn-lg" onclick="loadTrip()"><i class="fa fa-refresh"></i> Refresh</button></div>

    <div id="tripArea" style="display:none">
      <div class="card">
        <h4><span id="tripNumber"></span> <span class="badge badge-status" id="tripStatus"></span></h4>
        <div><i class="fa fa-truck"></i> <b id="vehicle"></b> &middot; <i class="fa fa-road"></i> <span id="route"></span> &middot; <span id="tripDate"></span></div>
        <div class="kpis" style="margin-top:8px">
          <div><b id="kStops"></b><span>stops delivered</span></div>
          <div><b id="kOnBoard"></b><span>units on vehicle</span></div>
          <div><b id="kToll"></b><span>toll paid</span></div>
          <div><b id="kTax"></b><span>tax paid</span></div>
        </div>
        <div id="startBox" style="display:none;margin-top:10px">
          <input type="number" id="startOdo" class="form-control input-lg" placeholder="Odometer reading (<?= fleet::du() ?>)">
          <button class="btn btn-primary btn-lg" onclick="tripStatus('start')"><i class="fa fa-play"></i> Start Trip</button>
        </div>
        <div id="completeBox" style="display:none;margin-top:10px">
          <input type="number" id="endOdo" class="form-control input-lg" placeholder="Odometer reading at return (<?= fleet::du() ?>)">
          <button class="btn btn-success btn-lg" onclick="tripStatus('complete')"><i class="fa fa-flag-checkered"></i> Complete Trip</button>
        </div>
      </div>

      <div class="card" id="expenseCard" style="display:none">
        <h4><i class="fa fa-money"></i> Toll / Tax / Expense</h4>
        <form id="expenseForm" onsubmit="saveExpense(); return false;">
          <input type="hidden" name="action" value="expense">
          <input type="hidden" name="latitude" id="expLat"><input type="hidden" name="longitude" id="expLng">
          <div class="form-group"><select name="expense_type" id="expenseType" class="form-control input-lg" required onchange="$('#fuelBox').toggle(this.value=='Fuel')">
            <option value="">Type</option><?= fleet::options(fleet::$EXPENSE_TYPES) ?></select></div>
          <div class="form-group"><input type="number" step="0.01" min="0.01" name="amount" class="form-control input-lg" placeholder="Amount" required></div>
          <div class="form-group"><select name="payment_mode" class="form-control input-lg"><?= fleet::options(fleet::$PAYMENT_MODES) ?></select></div>
          <div class="form-group"><input type="text" name="location" class="form-control" placeholder="Toll plaza / check post / pump name"></div>
          <div class="form-group"><input type="text" name="reference_no" class="form-control" placeholder="Receipt no. (optional)"></div>
          <div id="fuelBox" style="display:none">
            <div class="form-group"><input type="number" step="0.01" min="0" name="fuel_litres" class="form-control" placeholder="Fuel quantity (<?= fleet::fu() ?>)"></div>
            <div class="form-group"><input type="number" min="0" name="odometer_km" class="form-control" placeholder="Odometer (<?= fleet::du() ?>)"></div>
          </div>
          <div class="form-group"><label><i class="fa fa-camera"></i> Receipt photo</label><input type="file" name="receipt" accept="image/*,.pdf" capture="environment" class="form-control" style="padding:4px"></div>
          <button type="submit" class="btn btn-warning btn-lg">Save Expense</button>
        </form>
      </div>

      <div class="card">
        <h4><i class="fa fa-cubes"></i> Load on Vehicle</h4>
        <table class="table table-condensed"><thead><tr><th>SKU</th><th>Product</th><th class="text-right">Loaded</th><th class="text-right">On board</th></tr></thead><tbody id="loadRows"></tbody></table>
      </div>
    </div>
    </div>

    <!-- my trips: the driver's own finished trips -->
    <div id="paneHistory" style="display:none">
      <div class="card">
        <h4><i class="fa fa-bar-chart"></i> My Driving</h4>
        <div class="kpis">
          <div><b id="hMonthTrips">-</b><span>trips this month</span></div>
          <div><b id="hMonthKm">-</b><span id="hMonthKmLabel">km this month</span></div>
          <div><b id="hAllTrips">-</b><span>trips in total</span></div>
          <div><b id="hAllDelivered">-</b><span>deliveries in total</span></div>
        </div>
        <div id="hPending" class="alert alert-warning" style="display:none;margin:10px 0 0"></div>
      </div>
      <div id="historyList"></div>
      <button class="btn btn-default btn-lg" id="historyMore" style="display:none" onclick="loadHistory(historyPage + 1)">Show older trips</button>
    </div>

    <!-- profile -->
    <div id="paneProfile" style="display:none">
      <div class="card" id="profileCard"><p class="text-muted">Loading...</p></div>
    </div>
  </div>
</div>
<div class="alert" id="msg"></div>

<script src="<?= SITEROOT ?>/admin/templates/js/jquery/jquery.min.js"></script>
<script>
var API = "api.php";
var LOGGED_IN = <?= $logged_in ? "true" : "false" ?>;
var SEND_EVERY_MS = 30000;      // at most one point every 30 seconds
var lastSent = 0, lastPos = null, watchId = null, wakeLock = null, tripStatusNow = "";

function toast(text, ok){
	$("#msg").removeClass("alert-success alert-danger").addClass(ok ? "alert-success" : "alert-danger").text(text).fadeIn();
	setTimeout(function(){ $("#msg").fadeOut(); }, 4000);
}

function api(data, done){
	$.post(API, data, function(r){ done(r); }, "json").fail(function(x){
		if(x.status == 401){ showLogin(); } else { toast("No network - try again", false); }
	});
}

function showLogin(){
	$("#loginCard").show(); $("#appArea, #logoutLink").hide();
}

function login(){
	api({ action: "login", username: $("#username").val(), password: $("#password").val() }, function(r){
		if(r.success){ start(); } else { toast(r.msg, false); }
	});
}

function logout(){
	stopTracking();
	historyPage = 0; $("#historyList").html("");
	api({ action: "logout" }, function(){ showLogin(); });
}

function start(){
	$("#loginCard").hide(); $("#appArea, #logoutLink").show();
	loadTrip();
	startTracking();
}

function loadTrip(){
	api({ action: "trip" }, function(r){
		if(!r.success){ toast(r.msg, false); return; }
		var d = r.data;
		if(!d.trip){
			$("#tripArea").hide(); $("#noTrip").show(); $("#driverName").text("Hello " + d.driver);
			tripStatusNow = "";
			return;
		}
		var t = d.trip;
		tripStatusNow = t.status;
		$("#noTrip").hide(); $("#tripArea").show();
		$("#tripNumber").text(t.trip_number); $("#tripStatus").text(t.status);
		$("#vehicle").text(t.registration_no); $("#route").text(t.route_name); $("#tripDate").text(t.trip_date);
		$("#kStops").text(d.progress.delivered + " / " + d.progress.total);
		$("#kOnBoard").text(d.load_totals.on_board_units);
		$("#kToll").text(d.expenses.toll + " (" + d.expenses.toll_count + ")");
		$("#kTax").text(d.expenses.tax);
		$("#startBox").toggle(t.can_start); $("#startOdo").attr("placeholder", "Odometer reading (last " + t.vehicle_odometer + " <?= fleet::du() ?>)");
		$("#completeBox").toggle(t.can_complete); $("#expenseCard").toggle(t.can_complete);
		var rows = "";
		d.load.forEach(function(s){
			rows += "<tr><td>" + esc(s.sku) + "</td><td>" + esc(s.product_name) + "</td><td class='text-right'>" + s.loaded + "</td><td class='text-right'><b>" + s.on_board + "</b></td></tr>";
		});
		$("#loadRows").html(rows || "<tr><td colspan='4'>No items</td></tr>");
	});
}

function esc(s){ return $("<div>").text(s == null ? "" : s).html(); }

// ---- tabs
function showPane(link){
	$(".dtabs a").removeClass("on"); $(link).addClass("on");
	$("#paneTrip, #paneHistory, #paneProfile").hide();
	var pane = $(link).data("pane");
	$("#" + pane).show();
	if(pane == "paneHistory" && historyPage == 0){ loadHistory(1); }
	if(pane == "paneProfile"){ loadProfile(); }
}

// ---- my trips: the driver's own finished trips, newest first
var historyPage = 0;
function loadHistory(page){
	api({ action: "history", page: page }, function(r){
		if(!r.success){ toast(r.msg, false); return; }
		var d = r.data;
		historyPage = d.page;
		$("#hMonthTrips").text(d.month.trips); $("#hMonthKm").text(d.month.km); $("#hMonthKmLabel").text(d.unit + " this month");
		$("#hAllTrips").text(d.all.trips); $("#hAllDelivered").text(d.all.delivered);
		$("#hPending").toggle(!!d.pending_text).text(d.pending_text ? d.pending_text + " (" + d.pending_trips + " trip(s) waiting for the office to settle)" : "");
		var html = "";
		d.trips.forEach(function(t){
			var cancelled = t.status == "Cancelled";
			html += "<div class='card htrip' onclick='toggleTrip(this, " + t.trip_id + ")'>"
				+ "<div class='top'><b>" + esc(t.route) + "</b><span class='pill " + (cancelled ? "bad" : "ok") + "'>" + esc(t.status) + "</span></div>"
				+ "<div class='meta'>" + esc(t.date) + " &middot; " + esc(t.trip_number) + " &middot; <i class='fa fa-truck'></i> " + esc(t.vehicle) + "</div>"
				+ (cancelled ? "<div class='meta'>" + esc(t.note) + "</div>" :
					"<div class='nums'><span><i class='fa fa-road'></i> " + t.km + " " + esc(d.unit) + "</span><span><i class='fa fa-check'></i> " + t.delivered + "/" + t.stops + " delivered</span>"
					+ "<span>Toll " + esc(t.toll) + "</span><span>Tax " + esc(t.tax) + "</span><span>Fuel " + esc(t.fuel) + "</span></div>"
					+ "<div class='settle " + (t.settled ? "ok" : "pending") + "'>" + esc(t.settlement) + " &middot; " + (t.settled ? "settled" : "waiting for settlement") + "</div>")
				+ "<div class='detail'></div></div>";
		});
		if(page == 1 && d.trips.length == 0){ html = "<div class='card'><p class='text-muted'>No completed trips yet.</p></div>"; }
		if(page == 1){ $("#historyList").html(html); } else { $("#historyList").append(html); }
		$("#historyMore").toggle(d.more);
	});
}

function toggleTrip(card, tripId){
	var box = $(card).find(".detail");
	if(box.is(":visible")){ box.slideUp(150); return; }
	if(box.data("loaded")){ box.slideDown(150); return; }
	api({ action: "history_trip", trip_id: tripId }, function(r){
		if(!r.success){ toast(r.msg, false); return; }
		var html = "<b>Stops</b><table class='table table-condensed'>";
		r.data.stops.forEach(function(s){
			html += "<tr><td>" + s.stop_number + "</td><td>" + esc(s.customer_name) + (s.manifest_type == "Pickup" ? " <small>(pickup)</small>" : "")
				+ "<br><small class='text-muted'>" + esc(s.order_number) + (s.signature_text ? " &middot; received by " + esc(s.signature_text) : "") + (s.exception_reason ? " &middot; " + esc(s.exception_reason) : "") + "</small></td>"
				+ "<td class='text-right'>" + esc(s.status) + "</td></tr>";
		});
		html += "</table><b>Expenses</b><table class='table table-condensed'>";
		r.data.expenses.forEach(function(e){
			html += "<tr><td>" + esc(e.expense_type) + "<br><small class='text-muted'>" + esc(e.location) + " &middot; " + esc(e.payment_mode) + (e.has_receipt == 1 ? " &middot; receipt" : "") + "</small></td><td class='text-right'>" + esc(e.amount) + "</td></tr>";
		});
		if(r.data.expenses.length == 0){ html += "<tr><td class='text-muted'>No expenses</td></tr>"; }
		html += "</table><small class='text-muted'>Advance given " + esc(r.data.advance) + " &middot; paid by you (cash/UPI) " + esc(r.data.driver_paid) + "</small>";
		box.html(html).data("loaded", true).slideDown(150);
	});
}

// ---- profile
function loadProfile(){
	api({ action: "profile" }, function(r){
		if(!r.success){ toast(r.msg, false); return; }
		var p = r.data;
		var row = function(label, value){ return "<div class='prow'><span>" + label + "</span><b>" + (value ? esc(value) : "-") + "</b></div>"; };
		$("#profileCard").html("<h4><i class='fa fa-user'></i> " + esc(p.name) + "</h4>"
			+ (p.license_warning ? "<div class='alert alert-danger'>" + esc(p.license_warning) + "</div>" : "")
			+ row("Mobile", p.phone) + row("Driving licence", p.license_no) + row("Licence classes", p.license_classes.join(", "))
			+ row("Licence valid till", p.license_expiry) + row("Experience", p.experience_years !== null && p.experience_years !== "" ? p.experience_years + " years" : "")
			+ row("Aadhaar", p.aadhaar) + row("Documents on file", (p.documents.license ? "Licence " : "") + (p.documents.aadhaar ? "Aadhaar" : "") || "none")
			+ "<p class='text-muted small' style='margin-top:10px'>To change these details, contact the office.</p>");
	});
}

function posData(){
	return lastPos ? { latitude: lastPos.coords.latitude, longitude: lastPos.coords.longitude } : {};
}

function tripStatus(action){
	var odo = action == "start" ? $("#startOdo").val() : $("#endOdo").val();
	if(!odo){ toast("Enter the odometer reading", false); return; }
	api($.extend({ action: action, odometer: odo }, posData()), function(r){
		toast(r.msg, r.success);
		if(r.success){ lastSent = 0; loadTrip(); }
	});
}

function saveExpense(){
	if(lastPos){ $("#expLat").val(lastPos.coords.latitude); $("#expLng").val(lastPos.coords.longitude); }
	$.ajax({ url: API, type: "POST", data: new FormData(document.getElementById("expenseForm")), processData: false, contentType: false, dataType: "json" })
		.done(function(r){
			toast(r.msg, r.success);
			if(r.success){ document.getElementById("expenseForm").reset(); $("#fuelBox").hide(); loadTrip(); }
		}).fail(function(){ toast("No network - try again", false); });
}

// ---- GPS: the phone reports its position while this page is open
function gps(cls, text){ $("#gpsBox").attr("class", "gps " + cls); $("#gpsText").text(text); }

function startTracking(){
	if(!("geolocation" in navigator)){ gps("gps-err", "This phone/browser cannot share location"); return; }
	if(!window.isSecureContext){ gps("gps-err", "Location needs a secure (https) link - ask the office"); return; }
	watchId = navigator.geolocation.watchPosition(onPosition, function(err){
		gps("gps-err", err.code == 1 ? "Location permission denied - allow it in browser settings" : "Waiting for GPS signal...");
	}, { enableHighAccuracy: true, maximumAge: 10000, timeout: 30000 });
	keepAwake();
}

function stopTracking(){
	if(watchId !== null){ navigator.geolocation.clearWatch(watchId); watchId = null; }
	gps("gps-off", "Location sharing is off");
}

// Readings are queued on the phone with the time they were taken, and sent in batches. Without network the queue
// is kept (also across a page reload) and sent when the signal returns, so the route has no gaps.
var QUEUE_KEY = "routetrac_gps_queue", QUEUE_MAX = 2000, sending = false;

function loadQueue(){
	try { return JSON.parse(localStorage.getItem(QUEUE_KEY) || "[]"); } catch(e) { return []; }
}
function saveQueue(q){
	try { localStorage.setItem(QUEUE_KEY, JSON.stringify(q.slice(-QUEUE_MAX))); } catch(e) {}
}

function onPosition(pos){
	lastPos = pos;
	var now = Date.now();
	if(now - lastSent < SEND_EVERY_MS){ return; }
	lastSent = now;
	var q = loadQueue();
	q.push({ lat: +pos.coords.latitude.toFixed(7), lng: +pos.coords.longitude.toFixed(7), t: pos.timestamp || now, acc: Math.round(pos.coords.accuracy) });
	saveQueue(q);
	flushQueue();
}

function flushQueue(){
	var q = loadQueue();
	if(sending || q.length == 0){ return; }
	sending = true;
	var batch = q.slice(0, 500);
	$.post(API, { action: "location", points: JSON.stringify(batch) }, function(r){
		sending = false;
		if(r.success){
			saveQueue(loadQueue().slice(batch.length));	// accepted or permanently refused: drop them
			var left = loadQueue().length;
			var acc = lastPos ? " (±" + Math.round(lastPos.coords.accuracy) + " m)" : "";
			gps("gps-on", "Sharing location" + (r.data.trip_id > 0 ? " for this trip" : "") + " - last sent " + r.data.time + acc + (left ? " - " + left + " waiting" : ""));
			if(left){ flushQueue(); }
		}
	}, "json").fail(function(x){
		sending = false;
		if(x.status == 401){ showLogin(); return; }
		gps("gps-err", "No network - " + loadQueue().length + " locations saved on the phone, will send when online");
	});
}
window.addEventListener("online", flushQueue);

// Keep the screen on so the browser keeps reporting (where the phone supports it)
function keepAwake(){
	if("wakeLock" in navigator){
		navigator.wakeLock.request("screen").then(function(l){ wakeLock = l; }).catch(function(){});
	}
}
document.addEventListener("visibilitychange", function(){
	if(document.visibilityState == "visible" && watchId !== null){ keepAwake(); loadTrip(); }
});
setInterval(function(){ if(watchId !== null && document.visibilityState == "visible"){ loadTrip(); } }, 120000);

if(LOGGED_IN){ start(); flushQueue(); } else { showLogin(); }
</script>
</body>
</html>
