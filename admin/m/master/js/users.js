
	//	search code
	var ajaxFilePath = "ajax/users.php";
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
		$.get("ajax/users.php","",function(data){
			$("#MyAjaxInterView").html(data);
			loadingIndicator('end');
		},"html");	
	}

	function ajaxNewModify(user_id){
            
		$("#divList").removeClass("col-md-12").addClass("col-md-8");
		$("#divForm").removeClass("col-md-0").addClass("col-md-4").show();
		
		loadingIndicator('start');
		
		$.ajax({
			url: "ajax/users_new.php",
			method: "POST",
			data: { user_id : user_id },
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

        function show_routes(){
            if(document.getElementById("is_driver").checked==true){
                   document.getElementById("route_div").style.display = "block";
                   $(".driver-field").show();
            }else{
                 $("#default_route_id").val("");
                document.getElementById("route_div").style.display = "none";
                $(".driver-field").hide();
               
            }
                           
        }
	/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


