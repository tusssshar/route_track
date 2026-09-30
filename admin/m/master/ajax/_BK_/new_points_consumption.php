<?php
	include_once("../../../../bootstrap.php");

	$pcc_id = ($_REQUEST['pcc_id']?$_REQUEST['pcc_id']:0);
	
	if(strlen(trim($_POST['submit'])) > 0){
		$error = "";
		$success = 0;
		if(strlen(trim($_POST['pc_id'])) < 1){
			$error = "Please select type";
		}
                else if(strlen(trim($_POST['pcc_points'])) < 1){
			$error = "Please provice points";
		}
		if(strlen($error) < 1){
			$db = new database();
			$db->data("pc_id",trim($_POST['pc_id']));
                        $db->data("pcc_points",trim($_POST['pcc_points']));
			
			if($pcc_id < 1){
				$db->insert("vjgm_point_consumption_company");
				$msg = "Points consumption successfully added";
				$success = 1;
			}else{
				$db->where("pcc_id=".$pcc_id);
				$db->update("vjgm_point_consumption_company");
				$msg = "Points consumption successfully updated";
				$success = 1;
			}
		}
		
		$array = array("success" => $success, "error" => $error, "msg" => $msg);
		echo json_encode($array);
		exit;
		
	}

	if($pcc_id > 0){
		$db = new database();
		$db->where("pcc_is_deleted=0 and pcc_id=".$pcc_id."");
		$db->selectstmt("vjgm_point_consumption_company");
	}
?>
<script>
jQuery(document).ready(function(){
	$("#s_name").focus();
	// validate signup form on keyup and submit
	jQuery("#meAdminForm").validate({
		errorElement:'div',
		rules: {
		 pc_id:{
			required: true
		 },
                 pcc_points:{
			required: true
		 }
		 
		},
		messages:{
		 pc_id:{
			required: "Please select type"
		 },
                 pcc_points:{
			required: "Please enter points"
		 }
		},
		// set this class to error-labels to indicate valid fields
		success: function(label){
			// set &nbsp; as text for IE
			label.hide();
		}, 
		submitHandler: function(form) {
			var k = $("#meAdminForm").serializeArray();
			<? if($pcc_id > 0){?>
				k.push({
					name: "pcc_id",
					value: <?=$pcc_id?>
				});
			<? } ?>
			k = jQuery.param(k);
			
			jQuery("#divAlertSuccess, #divAlertError").hide();
			loadingIndicator('start');
			//$( "#submit" ).prop( "disabled", true );
			$.post("ajax/new_points_consumption.php",k,function(data){
				loadingIndicator('end');
				if(data.success == 1){
					$("#divAlertSuccess").html(data.msg).show();
					loadDefaultList();
					//$("#s_name").focus();
					
					//$("#s_name").val("");
					
					//$( "#submit" ).prop( "disabled", false );
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
    <?=($pcc_id > 0?"Update":"Add New")?>
    Points consumption </div>
  <div class="panel-body">
    <form class="form-horizontal" role="form" name="meAdminForm" id="meAdminForm" action="" method="post">
        <div class="form-group">
        <label for="s_name" class="col-sm-2 control-label"><span class="error">*</span>Type</label>
        <div class="col-sm-10">
            <?php
            $db_points_consumption=new database();
            $db_points_consumption->where("pc_is_deleted=0");
            $db_points_consumption->selectstmt("vjgm_point_consumption");
            $selected_pc_id=$db->f_pc_id;
            ?>
            <select class="form-control" id="pc_id" name="pc_id">
                <option value="">Select Type</option>
                <?php
                
                for($i=0;$i<$db_points_consumption->row_count;$i++)
                {
                    $db_points_consumption->moveto($i);
                    $pc_id=$db_points_consumption->f_pc_id;
                    $pc_activity=$db_points_consumption->f_pc_activity;
                    $selected="";
                    if($selected_pc_id==$pc_id)
                    {
                        $selected="selected";
                    }
                    echo "<option value=".$pc_id."  ".$selected.">".$pc_activity."</option>";
                    
                }
                ?>
            </select>
        </div>
      </div>
        <div class="form-group">
          <label for="s_name" class="col-sm-2 control-label"><span class="error">*</span>Points</label>
          <div class="col-sm-10">
            <input type="text" class="form-control" value="<?=$db->f_pcc_points?>" id="pcc_points" name="pcc_points" maxlength="8" />
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
