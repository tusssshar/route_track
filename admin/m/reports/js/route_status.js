
	//	search code
	var ajaxFilePath = "ajax/route_status.php";
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
		$.get("ajax/route_status.php","",function(data){
			$("#MyAjaxInterView").html(data);
			loadingIndicator('end');
		},"html");	
	}