
	var tripMap = null, mapBounds = [];

	$(document).ready(function(){
		// The map is drawn only once its tab is visible: a map created inside a hidden tab measures 0 x 0
		$("a[data-toggle='tab']").on("shown.bs.tab", function(e){
			history.replaceState(null, "", $(e.target).attr("href"));
			if($(e.target).attr("href") == "#tabTrack"){
				drawMap();
			}
		});
		// remember the open tab across the reloads that follow every action
		var tab = window.location.hash;
		if(tab && $("a[href='" + tab + "']").length){
			$("a[href='" + tab + "']").tab("show");
		}
	});

	function showPanel(id){
		$("#startPanel, #completePanel, #cancelPanel").hide();
		$(id).show().find("input:visible:first").focus();
	}

	function showResult(data){
		if(data.success == 1){
			$("#errorMsg").hide();
			$("#successMsg").html(data.msg).show();
			setTimeout(function(){ location.reload(); }, 600);
		}else{
			$("#successMsg").hide();
			$("#errorMsg").html(data.error).show();
			$("html, body").animate({ scrollTop: 0 }, 200);
		}
	}

	function tripAction(action, form){
		var k = form ? form.serializeArray() : [];
		k.push({ name: "action", value: action });
		k.push({ name: "trip_id", value: TRIP_ID });
		loadingIndicator('start');
		$.post("ajax/trip_action.php", $.param(k), function(data){
			loadingIndicator('end');
			showResult(data);
		}, "json").fail(function(){
			loadingIndicator('end');
			alert("Request failed");
		});
	}

	function addExpense(){
		loadingIndicator('start');
		$.ajax({
			url: "ajax/trip_action.php",
			type: "POST",
			data: new FormData(document.getElementById("expenseForm")),
			processData: false,
			contentType: false,
			dataType: "json"
		}).done(function(data){
			loadingIndicator('end');
			showResult(data);
		}).fail(function(){
			loadingIndicator('end');
			alert("Request failed");
		});
	}

	function deleteExpense(expense_id){
		if(!confirm("Delete this expense?")){
			return;
		}
		$.post("ajax/trip_action.php", { action: "delete_expense", trip_id: TRIP_ID, expense_id: expense_id }, showResult, "json");
	}

	// Reassign form (same form as on the trips list)
	function ajaxNewModify(trip_id){
		$("#divForm").show();
		loadingIndicator('start');
		$.post("ajax/trips_new.php", { trip_id: trip_id }, function(data){
			$("#divForm").html("<div class='col-md-6 col-md-offset-3'>" + data + "</div>");
			loadingIndicator('end');
		}, "html");
	}

	function ajaxNewModifyFormCancel(){
		$("#divForm").hide().html("");
	}

	function onTripSaved(){
		setTimeout(function(){ location.reload(); }, 600);
	}

	function drawMap(){
		if(tripMap){
			// tab shown again: re-measure the container and re-fit
			tripMap.invalidateSize();
			tripMap.fitBounds(mapBounds, { padding: [30, 30], maxZoom: 15 });
			return;
		}
		if(typeof L == "undefined" || (TRACK_POINTS.length == 0 && STOP_POINTS.length == 0)){
			return;
		}
		$("#tripMap").show();
		tripMap = L.map("tripMap");
		L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
			maxZoom: 19, attribution: "&copy; OpenStreetMap contributors"
		}).addTo(tripMap);

		var bounds = [];
		if(TRACK_POINTS.length > 0){
			var line = TRACK_POINTS.map(function(p){ return [p[0], p[1]]; });
			L.polyline(line, { color: "#337ab7", weight: 4 }).addTo(tripMap);
			L.circleMarker(line[0], { radius: 7, color: "#5cb85c", fillOpacity: 1 }).addTo(tripMap).bindPopup("Start " + TRACK_POINTS[0][2]);
			var last = TRACK_POINTS[TRACK_POINTS.length - 1];
			L.marker([last[0], last[1]]).addTo(tripMap).bindPopup("Last position " + last[2]).openPopup();
			bounds = bounds.concat(line);
		}
		STOP_POINTS.forEach(function(s){
			L.circleMarker([s[0], s[1]], { radius: 6, color: "#d9534f", fillOpacity: 0.8 }).addTo(tripMap).bindPopup(s[2]);
			bounds.push([s[0], s[1]]);
		});
		mapBounds = bounds;
		tripMap.fitBounds(bounds, { padding: [30, 30], maxZoom: 15 });
		// in case the tab was still fading in when the map was created
		setTimeout(function(){ tripMap.invalidateSize(); tripMap.fitBounds(mapBounds, { padding: [30, 30], maxZoom: 15 }); }, 300);
	}
