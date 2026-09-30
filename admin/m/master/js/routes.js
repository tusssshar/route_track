
	//	search code
	var ajaxFilePath = "ajax/routes.php";
	$(document).ready(function(){
		if(document.getElementById("txtSearch")){
			$("#txtSearch").focus();	
		}
		loadDefaultList();
		$("#form").submit(function(){
			loadingIndicator('start');
			$.post(ajaxFilePath,$(this).serializeArray(),function(data){
				$("#MyAjaxInterView").html(data);
				loadingIndicator('end');
			}, "html");
			return false;
		});//search submit
	});//document ready


	//	clear all search values
	function clearSearchValues(){
		$("#txtSearch").val("");
		loadDefaultList();
	}


	function loadDefaultList(){
		loadingIndicator('start');
		$.get("ajax/routes.php","",function(data){
			$("#MyAjaxInterView").html(data);
			loadingIndicator('end');
		},"html");	
	}

	function ajaxNewModify(route_id){
		$("#divList").removeClass("col-md-12").addClass("col-md-8");
		$("#divForm").removeClass("col-md-0").addClass("col-md-4").show();
		
		loadingIndicator('start');
		
		$.ajax({
			url: "ajax/routes_new.php",
			method: "POST",
			data: { route_id : route_id },
			dataType: "html"
		}).done(function(data) {
			$("#divForm").html(data);
			loadingIndicator('end');
		}).fail(function( jqXHR, textStatus ) {
			alert( "Request failed: " + textStatus );
		});
	}
	
	function ajaxNewModifyFormCancel(){
		$("#divList").removeClass("col-md-8").addClass("col-md-12");
		$("#divForm").removeClass("col-md-4").addClass("col-md-0").hide();
		$("#divForm").html("");
	}

	