<?php
	include_once("../../../../bootstrap.php");

	$area_id = ($_REQUEST['area_id']?$_REQUEST['area_id']:0);
	
	if(strlen(trim($_POST['submit'])) > 0){
		$error = "";
		$success = 0;
		if(strlen(trim($_POST['area_name'])) < 1){
			$error = "Please provide area name";
		}
		if(strlen($error) < 1){
			$db = new database();
                        $db_update=new database();
                        
                        if($_POST['is_default']==1)
                            {
                                  $db_update->data("is_default",0);
                                  $db_update->where("is_default=".$_POST['is_default']);
                                  $db_update->update("areas");
                            }
                         
                        
                        
			$db->data("name",trim($_POST['area_name']));
                        $db->data("is_default",trim($_POST['is_default']));
                        
			
			if($area_id < 1){
                            
				$db->insert("areas");
                                
				$msg = "New delivery area successfully added";
				$success = 1;
			}else{
                            
                            
				$db->where("area_id=".$area_id);
				$db->update("areas");
				$msg = "Delivery area successfully updated";
				$success = 1;
			}
		}
		
		$array = array("success" => $success, "error" => $error, "msg" => $msg);
		echo json_encode($array);
		exit;
		
	}

	if($area_id > 0){
		$db = new database();
		$db->where("area_id=".$area_id."");
		$db->selectstmt("areas");
	}
?>
<script>
jQuery(document).ready(function(){
	$("#area_name").focus();
         var hidden_area_id=$("#hidden_area_id").val().trim();
	// validate signup form on keyup and submit
	jQuery("#meAdminForm").validate({
		errorElement:'div',
		rules: {
		 area_name:{
                        required: true,
			minlength: 2,
			maxlength: 50,
                       remote: {url:SITEROOT + "/admin/ajax/admin_ajax_action.php?action=check_area&hidden_area_id="+hidden_area_id, type:"post",async: true}

		 },
                 is_default :{
                      remote: {url:SITEROOT + "/admin/ajax/admin_ajax_action.php?action=check_default_area&hidden_area_id="+hidden_area_id, type:"post",async: true}
                 }
		 
		},
		messages:{
		 area_name:{
			required: "Please enter area name",
			minlength: jQuery.validator.format("Enter at least {0} characters"),
			maxlength: jQuery.validator.format("Enter at most {0} characters"),
                        remote :"This area is allready exists"
		 },
                 is_default :{
                     remote :"One default area is must"
                 }
		},
		// set this class to error-labels to indicate valid fields
		success: function(label){
			// set &nbsp; as text for IE
			label.hide();
		}, 
		submitHandler: function(form) {
			var k = $("#meAdminForm").serializeArray();
			<? if($area_id > 0){?>
				k.push({
					name: "area_id",
					value: <?=$area_id?>
                                        
				});
                               
			<? } ?>
			k = jQuery.param(k);
			
			jQuery("#divAlertSuccess, #divAlertError").hide();
			loadingIndicator('start');
			$( "#submit" ).prop( "disabled", true );
			$.post("ajax/area_new.php",k,function(data){
				loadingIndicator('end');
				if(data.success == 1){
                                     var hidden_area_id=$("#hidden_area_id").val().trim();
					$("#divAlertSuccess").html(data.msg).show();
					loadDefaultList();
                                        if(hidden_area_id<1){
					$("#area_name").focus();
					$("#area_name").val("");
                                        $('#is_default').attr('checked', false);
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
    <?=($area_id > 0?"Update":"Add New")?>
    Delivery Area </div>
  <div class="panel-body">
    <form class="form-horizontal" role="form" name="meAdminForm" id="meAdminForm" action="" method="post">
        <input type="hidden" value="<?php echo $area_id?>" name="hidden_area_id" id="hidden_area_id">
      <div class="form-group">
        <label for="area_name" class="col-sm-2 control-label"><span class="error">*</span>Name</label>
        <div class="col-sm-10">
          <input type="text" class="form-control" value="<?=$db->f_name?>" id="area_name" name="area_name" maxlength="50" />
        </div>
      </div>
        <div class="form-group">
        <label for="is_default" class="col-sm-2 control-label">Is Default</label>
        <div class="col-sm-2">
          <input type="checkbox" class="form-control" value="1" id="is_default" name="is_default" maxlength="50" <?php if($db->f_is_default==1){ echo "checked";}?>/>
        </div>
      </div>

      
<?php if($area_id>0){?>

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
