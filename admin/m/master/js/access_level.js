
	//	search code
	var ajaxFilePath = "ajax/access_level.php";
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
		$.get("ajax/access_level.php","",function(data){
			$("#MyAjaxInterView").html(data);
			loadingIndicator('end');
		},"html");	
	}

	function ajaxNewModify(level_id){
		$("#divList").removeClass("col-md-12").addClass("col-md-6");
		$("#divForm").removeClass("col-md-0").addClass("col-md-6").show();
		
		loadingIndicator('start');
		
		$.ajax({
			url: "ajax/access_level_new.php",
			method: "POST",
			data: { level_id : level_id },
			dataType: "html"
		}).done(function(data) {
			$("#divForm").html(data);
			loadingIndicator('end');
		}).fail(function( jqXHR, textStatus ) {
			alert( "Request failed: " + textStatus );
		});
	}
	
	function ajaxNewModifyFormCancel(){
		$("#divList").removeClass("col-md-6").addClass("col-md-12");
		$("#divForm").removeClass("col-md-6").addClass("col-md-0").hide();
		$("#divForm").html("");
	}
        
        

	