
	//	search code
	var ajaxFilePath = "ajax/delivery_status.php";
	$(document).ready(function(){
		if(document.getElementById("txtSearch")){
			$("#txtSearch").focus();	
		}
		loadDefaultList(cust_name,delivery);
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

	function loadDefaultList(cust_name,delivery){
		loadingIndicator('start');
                
		$.get("ajax/delivery_status.php",{"cust_name" : cust_name,"delivery" : delivery},function(data){
			$("#MyAjaxInterView").html(data);
			loadingIndicator('end');
		},"html");	
	}

