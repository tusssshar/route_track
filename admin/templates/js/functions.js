function deleteGridRecord(url,id){
	if(confirm("Are you sure want to delete")){
		$("#tr_"+id).hide();
		$("#secondtr_"+id).hide();
		$("#successMsg").html("Package successfully deleted");
		$("#successMsg").show();
		$.get(url,"",function(data){
			
			//$("#" + div).html(data);
		},"html");
		jQuery("#successMsg").fadeOut(5000);
	}
}


function chkAllList($id,$val)
{
	var $table = document.getElementById($id);
	var $cell = $table.getElementsByTagName("input");
	for($i=0;$i < $cell.length;$i++)
	{
		if($val == true){
			$cell[$i].checked=true;
		}else{
			$cell[$i].checked=false;
		}
	}
}

function showAjaxPaging(div,url){
	loadingIndicator('start');
	
	var request = $.ajax({
							url: url,
							type: "GET",
							data: {},
							dataType: "html"
	}).done(function( data ) {
		$(div).html(data);
		loadingIndicator('end');
	}).fail(function( jqXHR, textStatus ) {
		alert( "Request failed: " + textStatus );
	});
}

var popUpWin=0;
function popUpWindow(URLStr, left, top, width, height){
  if(popUpWin){
    if(!popUpWin.closed) popUpWin.close();
  }
  popUpWin = open(URLStr, 'popUpWin', 'toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=no,copyhistory=yes,width='+width+',height='+height+',left='+left+', top='+top+',screenX='+left+',screenY='+top+'');
}

function loadingIndicator(mode){
	if(mode == 'start'){
		$("#topLoadingIndicator").show();
	}
	if(mode == 'end'){
		$("#topLoadingIndicator").hide();
	}
}

function active_deactive_list(url,id,message,alt_msg){
	if(confirm(message)){
//		$("#tr_"+id).hide();
//		$("#secondtr_"+id).hide();
		$("#successMsg").html("Emloyer successfully "+alt_msg);
		$("#successMsg").show();
		$.get(url,"",function(data){
			
			//$("#" + div).html(data);
		},"html");
		jQuery("#successMsg").fadeOut(5000);
                loadDefaultList();
	}
}

//delete area
function deleteGridRecord_area(url,id){
	if(confirm("Are you sure want to delete")){
		$("#tr_"+id).hide();
		$("#secondtr_"+id).hide();
		$("#successMsg").html("Area successfully deleted");
		$("#successMsg").show();
		$.get(url,"",function(data){
			
			//$("#" + div).html(data);
		},"html");
		jQuery("#successMsg").fadeOut(5000);
	}
}

//delete customer
function deleteGridRecord_customer(url,id){
	if(confirm("Are you sure want to delete")){
		$("#tr_"+id).hide();
		$("#secondtr_"+id).hide();
		$("#successMsg").html("Customer successfully deleted");
		$("#successMsg").show();
		$.get(url,"",function(data){
			
			//$("#" + div).html(data);
		},"html");
		jQuery("#successMsg").fadeOut(5000);
	}
}

//delete customer address

function deleteGridRecord_customer_address(url,id){
	if(confirm("Are you sure want to delete")){
		$("#tr_"+id).hide();
		$("#secondtr_"+id).hide();
		$("#successMsg").html("Address successfully deleted");
		$("#successMsg").show();
		$.get(url,"",function(data){
			
			//$("#" + div).html(data);
		},"html");
		jQuery("#successMsg").fadeOut(5000);
	}
}



function deleteGridRecord_user(url,id){
	if(confirm("Are you sure want to delete")){
		$("#tr_"+id).hide();
		$("#secondtr_"+id).hide();
		$("#successMsg").html("User successfully deleted");
		$("#successMsg").show();
		$.get(url,"",function(data){
			
			//$("#" + div).html(data);
		},"html");
		jQuery("#successMsg").fadeOut(5000);
	}
}

function deleteGridRecord_route(url,id){
	if(confirm("Are you sure want to delete")){
		$("#tr_"+id).hide();
		$("#secondtr_"+id).hide();
		$("#successMsg").html("Route successfully deleted");
		$("#successMsg").show();
		$.get(url,"",function(data){
			
			//$("#" + div).html(data);
		},"html");
		jQuery("#successMsg").fadeOut(5000);
	}
}

function deleteGridRecord_package(url,id){
	if(confirm("Are you sure want to delete")){
		$("#tr_"+id).hide();
		$("#secondtr_"+id).hide();
		$("#successMsg").html("Package type successfully deleted");
		$("#successMsg").show();
		$.get(url,"",function(data){
			
			//$("#" + div).html(data);
		},"html");
		jQuery("#successMsg").fadeOut(5000);
	}
}

function deleteGridRecord_manifest(url,id){
	if(confirm("Are you sure want to delete")){
		$("#tr_"+id).hide();
		$("#secondtr_"+id).hide();
		$("#successMsg").html("Manifest successfully deleted");
		$("#successMsg").show();
		$.get(url,"",function(data){
			
			//$("#" + div).html(data);
		},"html");
		jQuery("#successMsg").fadeOut(5000);
	}
}


function deleteGridRecord_delivery(url,id){
    if(confirm("Are you sure want to delete")){
		$("#tr_"+id).hide();
		$("#secondtr_"+id).hide();
		$("#successMsg").html("Delivery successfully deleted");
		$("#successMsg").show();
		$.get(url,"",function(data){
			$("#delivery_table_id").html(data);
			//$("#" + div).html(data);
		},"html");
		jQuery("#successMsg").fadeOut(5000);
	}
    
}

function show_deliveries_table(url){
   	$.get(url,"",function(data){
            $("#delivery_table_id").html(data);
			
		},"html");
		
	}
    
	function import_file(){
		$("#import_id").show(); 
		$("#row_serach,#row_list").hide();
	}
	function import_cancel(){
		$("#import_id").hide();       
		$("#row_serach,#row_list").show();
	}
           
           jQuery(document).ready(function(){
	$("#import_file_name").focus();
	jQuery("#import_form").validate({
		errorElement:'div',
		rules: {
		 import_file_name:{
			required: true,
			accept:"csv"
		 }
		 
		},
		messages:{
		 import_file_name:{
			required: "Please select file",
			accept:"Please select only csv file"
		 }
		},
                errorPlacement: function(error, element) {
                    if($(element).prop("id") === "import_file_name") {
                        $("#import_file_name_error").append(error);
                    }
                    else {
                        error.insertAfter(element); // default error placement.
                    }
                },
		// set this class to error-labels to indicate valid fields
		success: function(label){
			// set &nbsp; as text for IE
			label.hide();
		}
	});	
	
});

function ajax_edit_deliveries(delivery_id,manifest_id){
		//$("#divList").removeClass("col-md-12").addClass("col-md-8");
		//$("#divForm").removeClass("col-md-0").addClass("col-md-4").show();
		loadingIndicator('start');
		
		$.ajax({
			url: "ajax/manifests_new.php",
			method: "POST",
			data: { delivery_id : delivery_id,manifest_id:manifest_id },
			dataType: "html"
		}).done(function(data) {
			$("#MyAjaxInterView").html(data);
			loadingIndicator('end');
		}).fail(function( jqXHR, textStatus ) {
			alert( "Request failed: " + textStatus );
		});
	}







