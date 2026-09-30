<?php
	include_once("../../../../bootstrap.php");

	$s_id = ($_REQUEST['s_id']?$_REQUEST['s_id']:0);
	
	if(strlen(trim($_POST['submit'])) > 0){
		$error = "";
		$success = 0;
		if(strlen(trim($_POST['s_name'])) < 1){
			$error = "Please provide name";
		}
		if(strlen($error) < 1){
			$db = new database();
			$db->data("s_name",trim($_POST['s_name']));
			
			if($s_id < 1){
				$db->insert("vjgm_skills");
				$msg = "New skill successfully added";
				$success = 1;
			}else{
				$db->where("s_id=".$s_id);
				$db->update("vjgm_skills");
				$msg = "Skill successfully updated";
				$success = 1;
			}
		}
		
		$array = array("success" => $success, "error" => $error, "msg" => $msg);
		echo json_encode($array);
		exit;
		
	}

	if($s_id > 0){
		$db = new database();
		$db->where("s_id=".$s_id."");
		$db->selectstmt("vjgm_skills");
	}
?>
<script>
jQuery(document).ready(function(){
	$("#s_name").focus();
	// validate signup form on keyup and submit
	jQuery("#meAdminForm").validate({
		errorElement:'div',
		rules: {
		 s_name:{
			required: true,
			minlength: 1,
			maxlength: 50
		 }
		 
		},
		messages:{
		 s_name:{
			required: "Enter name",
			minlength: jQuery.validator.format("Enter at least {0} characters"),
			maxlength: jQuery.validator.format("Enter at most {0} characters")
		 }
		},
		// set this class to error-labels to indicate valid fields
		success: function(label){
			// set &nbsp; as text for IE
			label.hide();
		}, 
		submitHandler: function(form) {
			var k = $("#meAdminForm").serializeArray();
			<? if($s_id > 0){?>
				k.push({
					name: "s_id",
					value: <?=$s_id?>
				});
			<? } ?>
			k = jQuery.param(k);
			
			jQuery("#divAlertSuccess, #divAlertError").hide();
			loadingIndicator('start');
			$( "#submit" ).prop( "disabled", true );
			$.post("ajax/skills-new.php",k,function(data){
				loadingIndicator('end');
				if(data.success == 1){
					$("#divAlertSuccess").html(data.msg).show();
					loadDefaultList();
					$("#s_name").focus();
					
					$("#s_name").val("");
					
					$( "#submit" ).prop( "disabled", false );
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
    <?=($s_id > 0?"Update":"Add New")?>
    Skill </div>
  <div class="panel-body">
    <form class="form-horizontal" role="form" name="meAdminForm" id="meAdminForm" action="" method="post">

      <div class="form-group">
        <label for="s_name" class="col-sm-2 control-label"><span class="error">*</span>Name</label>
        <div class="col-sm-10">
          <input type="text" class="form-control" value="<?=$db->f_s_name?>" id="s_name" name="s_name" maxlength="20" />
        </div>
      </div>	  
      
      <div class="form-group">
        <div class="col-sm-offset-2 col-sm-10">
          <button type="submit" name="submit" id="submit" value=" Save " class="btn btn-success"><i class="fa fa-floppy-o"></i> Save </button>
            <span style="color:#999999">or</span>&nbsp; <span class="cancellink"><a href="javascript:void(0);" onClick="javascript:ajaxNewModifyFormCancel();">Cancel</a></span> </div>
      </div>
    </form>
  </div>
</div>
