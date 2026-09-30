<?php
	include_once("../../../../bootstrap.php");

	 $route_id = ($_REQUEST['route_id']?$_REQUEST['route_id']:0);
	
	if(strlen(trim($_POST['submit'])) > 0){
		$error = "";
		$success = 0;
		if(strlen(trim($_POST['name'])) < 1){
			$error = "Please provide name";
		}
		if(strlen($error) < 1){
			$db = new database();
			$db->data("name",trim($_POST['name']));
                        $db->data("area_id",trim($_POST['area_id']));
			
                        $db_check = new database();
                        $query = "SELECT route_id FROM  routes WHERE name='".$_POST['name']."' AND area_id='".$_POST['area_id']."' AND route_id!='".$route_id."'";
                        $db_check->myCustomQuery($query);
                        $count=$db_check->row_count;
			if($route_id < 1){
                                if($count <= 0){
                                    $db->insert("routes");
                                    $msg = "New route successfully added";
                                    $success = 1;
                                }else{
                                    $error="This route and area is allready exists";
                                }
				
				
			}else{
                                if($count <= 0){
                                    $db->where("route_id=".$route_id);
                                    $db->update("routes");
                                    $msg = "Route successfully updated";
                                    $success = 1;
                                }else{
                                   $error="This route and area is allready exists";
                                }
				
				
			}
    
		}
		
		$array = array("success" => $success, "error" => $error, "msg" => $msg);
		echo json_encode($array);
		exit;
		
	}

	if($route_id > 0){
		$db = new database();
		$db->where("route_id=".$route_id."");
		$db->selectstmt("routes");
	}
?>
<script>
jQuery(document).ready(function(){
      var hidden_route_id=$("#hidden_route_id").val().trim();
      //var area_name = $("#area_name").val().trim();
      var area_id=$("#area_id").val().trim();
      //var route_name=$('#name').val();
          //alert(hidden_route_id);
	$("#name").focus();
	// validate signup form on keyup and submit
	jQuery("#meAdminForm").validate({
        
		errorElement:'div',
		rules: {
                     
		 name:{
                        required: true,
                        //remote: {url:SITEROOT + "/admin/ajax/admin_ajax_action.php?action=check_route_area&hidden_route_id="+hidden_route_id+"&area_id="+$("#area_id").val(),data: {id:$("#area_id").val()}, type:"post",async: true}
                   	
		 },
                 area_id :{
                     required: true,
                     //remote: {url:SITEROOT + "/admin/ajax/admin_ajax_action.php?action=check_route_area&area_id="+area_id+"&name="+$("#name").val(),type:"post",async: true}
                 }
		 
		},
		messages:{
		 name:{
			required: "Please enter route",
                        //remote : "This route is allready exists"
			
		 },
                 area_id :{
                     required: "Please select area",
                     remote : "This route and area is allready exists"
                 }
		},
		// set this class to error-labels to indicate valid fields
		success: function(label){
			// set &nbsp; as text for IE
			label.hide();
		}, 
		submitHandler: function(form) {
                    
			var k = $("#meAdminForm").serializeArray();
			<? if($route_id > 0){?>
				k.push({
					name: "route_id",
					value: <?=$route_id?>
                                        
				});
                               
			<? } ?>
                               
			k = jQuery.param(k);
			
			jQuery("#divAlertSuccess, #divAlertError").hide();
			loadingIndicator('start');
			$( "#submit" ).prop( "disabled", false );
			$.post("ajax/routes_new.php",k,function(data){
				loadingIndicator('end');
                                loadDefaultList();
				if(data.success == 1){
                                    var hidden_route_id=$("#hidden_route_id").val().trim();                                    if(hidden_route_id < 1){
					$("#divAlertSuccess").html(data.msg).show();
					
					$("#name").focus();
					$("#name").val("");
                                        $("#area_id").val("");
					$( "#submit" ).prop( "disabled", false );
                                    }else{
                                        $("#successMsg").html(data.msg).show();
						ajaxNewModifyFormCancel();
                                    }
				}else{
					$("#divAlertError").html(data.error).show();
				}
				var k = setTimeout(function(){
					jQuery("#divAlertSuccess, #divAlertError").hide();
				},10000);
				
			},"json");
		}
	});
        
       
	
});
</script>

<div class="alert alert-success" id="divAlertSuccess" style="display:none">Success! Well done its submitted.</div>
<div class="alert alert-danger"  id="divAlertError" style="display:none">Error ! Change few things.</div>
<div class="panel panel-info">
  <div class="panel-heading">
    <?=($route_id > 0?"Update":"Add New")?>
    Route </div>
  <div class="panel-body">
    <form class="form-horizontal" role="form" name="meAdminForm" id="meAdminForm" action="" method="post">
        <input type="hidden" name="hidden_route_id" id="hidden_route_id" value="<?php echo $route_id?>">
        <input type="hidden" name="area_name" id="area_name" value="<?php echo $db->f_area_id?>">
      <div class="form-group">
        <label for="name" class="col-sm-2 control-label"><span class="error">*</span>Name</label>
        <div class="col-sm-10">
            <input type="text" class="form-control" value="<?=$db->f_name?>" id="name" name="name" />
        </div>
      </div>
         <div class="form-group">
             <?php
                if($route_id<=0){
                    $db_default_id=new database();
                    $query_default_id="SELECT area_id FROM areas WHERE  is_default=1";
                 
                $db_default_id->myCustomQuery($query_default_id);
                //$db_count_addr->moveto(0);
                 $default_area_id=$db_default_id->f_area_id;
                }else{
                    $default_area_id=$db->f_area_id;
                }
             ?>
        <label for="area_id" class="col-sm-2 control-label"><span class="error">*</span>Area</label>
        <div class="col-sm-10">
            <select name="area_id" id="area_id" class="form-control">
		  <option value="">Select One</option>
                <?=$functions->createCombo("areas","area_id","name",$default_area_id," order by name asc"); ?>
              </select>        </div>
      </div>
     
         
<?php if($route_id>0){?>

      <div class="form-group">
        <div class="col-sm-offset-2 col-sm-10">
          <button type="submit" name="submit" id="submit" value=" Save " class="btn btn-success"><i class="fa fa-floppy-o"></i> Update </button>
            <span style="color:#999999">or</span>&nbsp; <span class="cancellink"><a href="javascript:void(0);" onClick="javascript:ajaxNewModifyFormCancel();">Cancel</a></span> </div>
      </div>
<?php }else{?>
            <div class="form-group">
        <div class="col-sm-offset-2 col-sm-10">
          <button type="submit" name="submit" id="submit" value=" Save " class="btn btn-success"><i class="fa fa-floppy-o"></i> Save </button>
            <span style="color:#999999">or</span>&nbsp; <span class="cancellink"><a href="javascript:void(0);" onClick="javascript:ajaxNewModifyFormCancel();">Cancel</a></span> </div>
      </div>
<?php }?>
    </form>
  </div>
</div>
