<?php
	include_once("../../../../bootstrap.php");

	$cust_id = ($_REQUEST['cust_id']?$_REQUEST['cust_id']:0);
	
	if(strlen(trim($_POST['submit'])) > 0){
		$error = "";
		$success = 0;
		if(strlen(trim($_POST['cust_name'])) < 1){
			$error = "Please provide name";
		}
		if(strlen($error) < 1){
			$db = new database();
			$db->data("customer_name",trim($_POST['cust_name']));
                        $db->data("account_number",trim($_POST['cust_act_no']));
			
			if($cust_id < 1){
                           
				$db->insert("customers");
				$msg = "New customer successfully added";
				$success = 1;
			}else{
				$db->where("customer_id=".$cust_id);
				$db->update("customers");
				$msg = "Customer successfully updated";
				$success = 1;
			}
		}
		
		$array = array("success" => $success, "error" => $error, "msg" => $msg);
		echo json_encode($array);
		exit;
		
	}

	if($cust_id > 0){
		$db = new database();
		$db->where("customer_id=".$cust_id."");
		$db->selectstmt("customers");
	}
?>
<script>
jQuery(document).ready(function(){
	$("#cust_name").focus();
        var hidden_cust_id=$("#hidden_cust_id").val().trim();
	// validate signup form on keyup and submit
	jQuery("#meAdminForm").validate({
		errorElement:'div',
		rules: {
		 cust_name:{
                        required: true,
			minlength: 2,
			maxlength: 100
		 },
                 cust_act_no :{
                     number : true,
                     remote: {url:SITEROOT + "/admin/ajax/admin_ajax_action.php?action=check_acct_no&hidden_cust_id="+hidden_cust_id, type:"post",async: true}
                 }
		},
		messages:{
		 cust_name:{
			required: "Please enter customer name",
			minlength: jQuery.validator.format("Enter at least {0} characters"),
			maxlength: jQuery.validator.format("Enter at most {0} characters")
		 },
                 cust_act_no :{
                     remote :"This account no. is already exists"
                 }
                 
		},
		// set this class to error-labels to indicate valid fields
		success: function(label){
			// set &nbsp; as text for IE
			label.hide();
		}, 
		submitHandler: function(form) {
			var k = $("#meAdminForm").serializeArray();
			<? if($cust_id > 0){?>
				k.push({
					name: "cust_id",
					value: <?=$cust_id?>
                                        
				});
                               
			<? } ?>
			k = jQuery.param(k);
			
			jQuery("#divAlertSuccess, #divAlertError").hide();
			loadingIndicator('start');
			$( "#submit" ).prop( "disabled", true );
			$.post("ajax/customer_new.php",k,function(data){
				loadingIndicator('end');
				if(data.success == 1){
                                    loadDefaultList();
                                     var hidden_cust_id=$("#hidden_cust_id").val().trim();
                                     if(hidden_cust_id < 1){
					$("#divAlertSuccess").html(data.msg).show();
					$("#cust_name").focus();
					$("#cust_name").val("");
                                        $("#cust_act_no").val("");
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
    <?=($cust_id > 0?"Update":"Add New")?>
    Customer </div>
  <div class="panel-body">
    <form class="form-horizontal" role="form" name="meAdminForm" id="meAdminForm" action="" method="post">
        <input type="hidden" value="<?php echo $cust_id?>" name="hidden_cust_id" id="hidden_cust_id">
      <div class="form-group">
        <label for="cust_name" class="col-sm-2 control-label"><span class="error">*</span>Name</label>
        <div class="col-sm-10">
          <input type="text" class="form-control" value="<?=$db->f_customer_name?>" id="cust_name" name="cust_name" maxlength="100" />
        </div>
      </div>
         <div class="form-group">
        <label for="cust_act_no" class="col-sm-2 control-label">Account Number</label>
        <div class="col-sm-10">
          <input type="text" class="form-control" value="<?=$db->f_account_number?>" id="cust_act_no" name="cust_act_no" maxlength="50" />
        </div>
      </div>

      
<?php if($cust_id>0){?>

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
<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

