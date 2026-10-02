
	// Live Fleet Map: OpenStreetMap + Leaflet, data from ajax/live_positions.php
	var REFRESH_MS = 30000;
	var map, layer, refreshTimer = null, firstFit = true, focusTrip = null, markersByKey = {};

	$(document).ready(function(){
		if(typeof L == "undefined"){
			$("#fleetMap").html("<div class='alert alert-danger' style='margin:20px'>The map library could not be loaded (no internet access?).</div>");
			$("#vlist").html("");
			return;
		}
		map = L.map("fleetMap").setView([19.0, 75.0], 6);	// Maharashtra until the first data arrives
		L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", { maxZoom: 19, attribution: "&copy; OpenStreetMap contributors" }).addTo(map);
		layer = L.layerGroup().addTo(map);
		loadLive();
	});

	function esc(s){ return $("<div>").text(s == null ? "" : s).html(); }

	function ago(sig){
		if(sig.state == "none"){ return "no GPS yet"; }
		if(sig.minutes < 1){ return "just now"; }
		if(sig.minutes < 60){ return sig.minutes + " min ago"; }
		var h = Math.floor(sig.minutes / 60);
		return h < 24 ? h + " h " + (sig.minutes % 60) + " min ago" : Math.floor(h / 24) + " day(s) ago";
	}

	function setMode(){
		var history = $("input[name=mode]:checked").val() == "history";
		$("#historyFields").toggle(history);
		if(history){
			clearTimeout(refreshTimer);
			layer.clearLayers();
			$("#vlist").html("<p class='text-muted'>Choose a driver and dates, then Show Track.</p>");
			$("#updated").text("");
		}else{
			firstFit = true;
			loadLive();
		}
	}

	function loadLive(){
		clearTimeout(refreshTimer);
		$.getJSON("ajax/live_positions.php", { mode: "live" }, drawLive)
			.fail(function(){ $("#updated").text(" - update failed, retrying"); })
			.always(function(){
				if($("input[name=mode]:checked").val() == "live"){ refreshTimer = setTimeout(loadLive, REFRESH_MS); }
			});
	}

	function drawLive(data){
		layer.clearLayers();
		markersByKey = {};
		var bounds = [], html = "";
		$("#updated").text(" - updated " + data.time + " - " + data.vehicles.length + " vehicle(s) on the road");

		data.vehicles.forEach(function(v){
			var key = "t" + v.trip_id;
			if(v.track.length > 1){
				L.polyline(v.track, { color: "#337ab7", weight: 4, opacity: 0.7 }).addTo(layer);
			}
			v.stops.forEach(function(s){
				L.circleMarker([s.lat, s.lng], { radius: 6, color: s.status == "Delivered" ? "#5cb85c" : "#f0ad4e", fillOpacity: 0.9 })
					.addTo(layer).bindPopup("Stop " + s.stop + ": " + esc(s.customer) + "<br>" + esc(s.order) + " - " + esc(s.status) + "<br>" + esc(s.time));
			});
			var info = "<strong>" + esc(v.registration_no) + "</strong> (" + esc(v.vehicle_type) + ")<br>" + esc(v.driver) + (v.phone ? " &middot; " + esc(v.phone) : "")
				+ "<br>" + esc(v.trip_number) + " &middot; " + esc(v.route) + "<br>Stops " + v.stops_done + "/" + v.stops_total + " &middot; " + v.on_board + " units on board"
				+ "<br><span class='sig " + v.signal.state + "'>Last GPS " + ago(v.signal) + "</span>"
				+ "<br><a href='" + SITEROOT + "/admin/m/fleet/trip_detail.php?trip_id=" + v.trip_id + "#tabTrack'>Open trip</a>";
			if(v.last){
				var m = L.marker([v.last[0], v.last[1]], { icon: L.divIcon({ className: "", html: "<span class='vtruck " + v.signal.state + "'>&#128666; " + esc(v.registration_no) + "</span>", iconAnchor: [10, 10] }) })
					.addTo(layer).bindPopup(info);
				markersByKey[key] = m;
				bounds.push([v.last[0], v.last[1]]);
				bounds = bounds.concat(v.track);
			}
			html += "<div class='vcard " + v.signal.state + "' onclick=\"focusOn('" + key + "')\">"
				+ "<strong>&#128666; " + esc(v.registration_no) + "</strong> <span class='sig " + v.signal.state + " pull-right'>" + ago(v.signal) + "</span><br>"
				+ esc(v.driver) + "<br><small>" + esc(v.route) + " &middot; stops " + v.stops_done + "/" + v.stops_total + " &middot; " + v.on_board + " units</small></div>";
		});
		if(data.vehicles.length == 0){
			html += "<p class='text-muted'>No vehicle is on the road right now.</p>";
		}

		if(data.drivers.length > 0){
			html += "<h5 style='margin-top:14px'>Other drivers (last 24 h)</h5>";
			data.drivers.forEach(function(d){
				var key = "d" + d.id;
				var m = L.circleMarker([d.last[0], d.last[1]], { radius: 7, color: "#555", fillColor: "#999", fillOpacity: 0.9, weight: 2 })
					.addTo(layer).bindTooltip(esc(d.driver)).bindPopup("<strong>" + esc(d.driver) + "</strong>" + (d.phone ? "<br>" + esc(d.phone) : "") + "<br>Not on a trip<br>Last GPS " + esc(d.last[2]) + " (" + ago(d.signal) + ")");
				markersByKey[key] = m;
				bounds.push([d.last[0], d.last[1]]);
				html += "<div class='vcard " + d.signal.state + "' onclick=\"focusOn('" + key + "')\">&#128100; " + esc(d.driver)
					+ " <span class='sig " + d.signal.state + " pull-right'>" + ago(d.signal) + "</span><br><small>not on a trip</small></div>";
			});
		}
		$("#vlist").html(html);

		if(focusTrip && markersByKey[focusTrip]){
			if(markersByKey[focusTrip].getLatLng){ map.panTo(markersByKey[focusTrip].getLatLng()); }
			markersByKey[focusTrip].openPopup();
		}else if(firstFit && bounds.length > 0){
			map.fitBounds(bounds, { padding: [30, 30], maxZoom: 14 });
			firstFit = false;
		}
	}

	function focusOn(key){
		focusTrip = key;
		var m = markersByKey[key];
		if(m){
			map.setView(m.getLatLng(), Math.max(map.getZoom(), 12));
			m.openPopup();
		}
	}

	function loadHistory(){
		var k = { mode: "history", driver_id: $("#h_driver").val(), from: $("#h_from").val(), to: $("#h_to").val() };
		if(!k.driver_id){ alert("Select a driver"); return; }
		loadingIndicator('start');
		$.getJSON("ajax/live_positions.php", k, function(data){
			loadingIndicator('end');
			layer.clearLayers();
			if(data.error){ $("#vlist").html("<div class='alert alert-danger'>" + esc(data.error) + "</div>"); return; }
			var html = "<h5>" + esc(data.driver) + "</h5><p>" + data.count + " GPS points</p>";
			data.trips.forEach(function(t){
				html += "<div class='vcard'><a href='" + SITEROOT + "/admin/m/fleet/trip_detail.php?trip_id=" + t.trip_id + "'>" + esc(t.trip_number) + "</a> "
					+ esc(t.status) + "<br><small>" + esc(t.start_time || "") + (t.end_time ? " &rarr; " + esc(t.end_time) : "")
					+ (t.end_odometer ? " &middot; " + (t.end_odometer - t.start_odometer) + " km" : "") + "</small></div>";
			});
			if(data.count == 0){
				html += "<p class='text-muted'>No GPS points in this period.</p>";
				$("#vlist").html(html);
				return;
			}
			$("#vlist").html(html);
			var line = data.points.map(function(p){ return [p[0], p[1]]; });
			L.polyline(line, { color: "#c9302c", weight: 4, opacity: 0.8 }).addTo(layer);
			var first = data.points[0], last = data.points[data.points.length - 1];
			L.circleMarker(line[0], { radius: 8, color: "#5cb85c", fillOpacity: 1 }).addTo(layer).bindPopup("Start " + esc(first[2])).openPopup();
			L.circleMarker(line[line.length - 1], { radius: 8, color: "#d9534f", fillOpacity: 1 }).addTo(layer).bindPopup("End " + esc(last[2]));
			map.fitBounds(line, { padding: [30, 30] });
		}).fail(function(){ loadingIndicator('end'); alert("Request failed"); });
	}
