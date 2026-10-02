
	var ajaxFilePath = "ajax/trips.php";
	$(document).ready(function(){
		loadDefaultList();
		$("#form").submit(function(){
			loadList(1);
			return false;
		});
	});

	function loadList(page){
		loadingIndicator('start');
		var k = $("#form").serializeArray();
		k.push({ name: "page", value: page });
		$.post(ajaxFilePath, $.param(k), function(data){
			$("#MyAjaxInterView").html(data);
			loadingIndicator('end');
		}, "html");
	}

	function loadDefaultList(){
		loadList(1);
	}

	function clearSearchValues(){
		$("#form").find("input[type=text], input[type=date], select").val("");
		loadDefaultList();
	}

	function ajaxNewModify(trip_id){
		$("#divList").removeClass("col-md-12").addClass("col-md-7");
		$("#divForm").removeClass("col-md-0").addClass("col-md-5").show();
		loadingIndicator('start');
		$.ajax({
			url: "ajax/trips_new.php",
			method: "POST",
			data: { trip_id : trip_id },
			dataType: "html"
		}).done(function(data) {
			$("#divForm").html(data);
			loadingIndicator('end');
		}).fail(function( jqXHR, textStatus ) {
			loadingIndicator('end');
			alert( "Request failed: " + textStatus );
		});
	}

	function ajaxNewModifyFormCancel(){
		$("#divList").removeClass("col-md-7").addClass("col-md-12");
		$("#divForm").removeClass("col-md-5").addClass("col-md-0").hide();
		$("#divForm").html("");
	}
