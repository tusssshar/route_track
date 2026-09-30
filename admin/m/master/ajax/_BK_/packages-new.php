<?php
	include_once("../../../../bootstrap.php");

	$package_type_id = ($_REQUEST['package_type_id']?$_REQUEST['package_type_id']:0);
	
	if(strlen(trim($_POST['submit'])) > 0){
		$error = "";
		$success = 0;
		if(strlen(trim($_POST['name'])) < 1){
			$error = "Please provide name";
		}
		if(strlen($error) < 1){
			$db = new database();
			$db->data("name",trim($_POST['name']));
			
			
			if($package_type_id < 1){
				$db->insert("package_types");
				$msg = "New package type successfully added";
				$success = 1;
			}else{
				$db->where("package_type_id=".$package_type_id);
				$db->update("package_types");
				$msg = "Package type successfully updated";
				$success = 1;
			}
		}
		
		$array = array("success" => $success, "error" => $error, "msg" => $msg);
		echo json_encode($array);
		exit;
		
	}

	if($package_type_id > 0){
		$db = new database();
		$db->where("package_type_id=".$package_type_id."");
		$db->selectstmt("package_types");
	}
?>
<script>
jQuery(document).ready(function(){
	$("#name").focus();
         var hidden_package_id=$("#hidden_package_id").val().trim();
	// validate signup form on keyup and submit
	jQuery("#meAdminForm").validate({
		errorElement:'div',
		rules: {
		 name:{
			required: true,
		        remote: {url:SITEROOT + "/admin/ajax/admin_ajax_action.php?action=check_package&hidden_package_id="+hidden_package_id, type:"post",async: true}

		 }
		 
		},
		messages:{
		 name:{
			required: "Enter package name",
                        remote: "This package is allready exists"
			
		 }
		},
		// set this class to error-labels to indicate valid fields
		success: function(label){
			// set &nbsp; as text for IE
			label.hide();
		}, 
		submitHandler: function(form) {
			var k = $("#meAdminForm").serializeArray();
			<? if($package_type_id > 0){?>
				k.push({
					name: "package_type_id",
					value: <?=$package_type_id?>
				});
			<? } ?>
			k = jQuery.param(k);
			
			jQuery("#divAlertSuccess, #divAlertError").hide();
			loadingIndicator('start');
			$( "#submit" ).prop( "disabled", true );
			$.post("ajax/packages-new.php",k,function(data){
				loadingIndicator('end');
				if(data.success == 1){
                                    	loadDefaultList();
                                var hidden_package_id=$("#hidden_package_id").val().trim();
                                    if(hidden_package_id < 1){
                                    $("#divAlertSuccess").html(data.msg).show();
					$("#name").focus();
					$("#name").val("");
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
    <?=($package_type_id > 0?"Update":"Add New")?>
    Package Type</div>
  <div class="panel-body">
    <form class="form-horizontal" role="form" name="meAdminForm" id="meAdminForm" action="" method="post">
        <input type="hidden" value="<?php echo $package_type_id?>" name="hidden_package_id" id="hidden_package_id">
      <div class="form-group">
        <label for="name" class="col-sm-2 control-label"><span class="error">*</span>Name</label>
        <div class="col-sm-10">
          <input type="text" class="form-control" value="<?=$db->f_name?>" id="name" name="name"/>
        </div>
      </div>
<?php if($package_type_id>0){?>
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
