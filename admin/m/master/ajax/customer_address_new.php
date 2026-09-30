<?php
	include_once("../../../../bootstrap.php");

	 $address_id = ($_REQUEST['address_id']?$_REQUEST['address_id']:0);
	 $customer_id = ($_REQUEST['customer_id']?$_REQUEST['customer_id']:0);
	if(strlen(trim($_POST['submit'])) > 0){
		$error = "";
		$success = 0;
		if((int) $_POST['cust_name'] < 1){
			$error = "Please select customer";
		}
		if(strlen($error) < 1){
			$db = new database();
                        $db_list1 =new database();
			
                        if(trim($_POST['ship_to_name'])==""){
                      $query1="SELECT customer_name FROM customers WHERE customer_id='".$_POST['cust_name']."'";
                    $db_list1->myCustomQuery($query1);
                   // $db_list1->moveto(0);
                    $customer_name = $db_list1->f_customer_name;
                            $db->data("ship_to_name",trim($customer_name));
                        }else{
                            $db->data("ship_to_name",trim($_POST['ship_to_name']));
                        }
                        $db->data("customer_id",trim($_POST['cust_name']));
                        $db->data("address_line_1",trim($_POST['address_line_1']));
                        $db->data("address_line_2",trim($_POST['address_line_2']));
                        $db->data("address_line_3",trim($_POST['address_line_3']));
                        $db->data("city",trim($_POST['city']));
                        $db->data("state",trim($_POST['state']));
                        $db->data("zip",trim($_POST['zip']));
                        $db->data("phone",trim($_POST['phone']));
                        
			
			if($address_id < 1){
                           
				$db->insert("customer_addresses");
				$msg = "New address successfully added";
				$success = 1;
			}else{
				$db->where("address_id=".$address_id);
				$db->update("customer_addresses");
				$msg = "Address successfully updated";
				$success = 1;
			}
		}
		
		$array = array("success" => $success, "error" => $error, "msg" => $msg);
		echo json_encode($array);
		exit;
		
	}

	if($address_id > 0){
		$db = new database();
		$db->where("address_id=".$address_id."");
		$db->selectstmt("customer_addresses");
                $customer_id=$db->f_customer_id;
	}
?>
<script>
jQuery(document).ready(function(){
	$("#cust_name").focus();
	// validate signup form on keyup and submit
	jQuery("#meAdminForm").validate({
		errorElement:'div',
		rules: {
		 cust_name:{
                        required: true,
			
		 },
                 address_line_1 : {
                     required: true
                 },
                /* zip : {
                     required:true,
                      number: true,
                      minlength: 5
                 },*/
                 phone : {
                    number: true
		},
                city :{
                    required : true
                },
                state :{
                    required:true
                }
            },  
		messages:{
                    address_line_1:{
                     required:"Please enter address line 1"
                 },
		 cust_name:{
			required: "Please Select customer name"
			
		 },
                 city:{
                     required:"Please enter city name"
                 },
                 state:{
                     required:"Please enter district name"
                 },
                 /*zip:{
                     required:"Please enter zip code"
                 }*/
		},
		// set this class to error-labels to indicate valid fields
		success: function(label){
			// set &nbsp; as text for IE
			label.hide();
		}, 
		submitHandler: function(form) {
			var k = $("#meAdminForm").serializeArray();
			<? if($address_id > 0){?>
				k.push({
					name: "address_id",
					value: <?=$address_id?>
                                        
				});
                               
			<? } ?>
			k = jQuery.param(k);
			
			jQuery("#divAlertSuccess, #divAlertError").hide();
			loadingIndicator('start');
			$( "#submit" ).prop( "disabled", true );
			$.post("ajax/customer_address_new.php",k,function(data){
				loadingIndicator('end');
				if(data.success == 1){
                                    loadDefaultList();
                                var hidden_address_id=$("#hidden_address_id").val().trim();
                                if(hidden_address_id < 1){
					$("#divAlertSuccess").html(data.msg).show();
					$("#cust_name").focus();
					$("#ship_to_name").val("");
                                        $("#address_line_1").val("");
                                        $("#address_line_2").val("");
                                        $("#address_line_3").val("");
                                        $("#city").val("");
                                        $("#state").val("");
					$("#zip").val("");
                                        $("#phone").val("");
					
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
    <?=($address_id > 0?"Update":"Add New")?>
    Address </div>
  <div class="panel-body">
    <form class="form-horizontal" role="form" name="meAdminForm" id="meAdminForm" action="" method="post">

        <?php if($customer_id > 0){ ?>
        <input type="hidden" value="<?php echo $customer_id?>" name="cust_name" id="cust_name"/>
        <?php }else{ ?>
      <div class="form-group">
        <label for="cust_name" class="col-sm-2 control-label"><span class="error">*</span>Customer</label>
        <div class="col-sm-10">
          <select name="cust_name" id="cust_name" class="form-control" required>
            <option value="">Select one</option>
            <?= $functions->createCombo("customers", "customer_id", "customer_name", "", " order by customer_name asc"); ?>
          </select>
        </div>
      </div>
        <?php } ?>
        <input type="hidden" value="<?=$db->f_address_id?>" name="hidden_address_id" id="hidden_address_id">
        
      <div class="form-group">
        <label for="ship_to_name" class="col-sm-2 control-label">Ship To Name</label>
        <div class="col-sm-10">
          <input type="text" class="form-control" value="<?=$db->f_ship_to_name?>" id="ship_to_name" name="ship_to_name"/>(Leave blank if ship name is same as customer name)
        </div>
      </div>
         <div class="form-group">
        <label for="address_line_1" class="col-sm-2 control-label"><span class="error">*</span>Address Line 1</label>
        <div class="col-sm-10">
          <input type="text" class="form-control" value="<?=$db->f_address_line_1?>" id="address_line_1" name="address_line_1"/>
        </div>
      </div>
        <div class="form-group">
        <label for="address_line_2" class="col-sm-2 control-label">Address Line 2</label>
        <div class="col-sm-10">
          <input type="text" class="form-control" value="<?=$db->f_address_line_2?>" id="address_line_2" name="address_line_2"/>
        </div>
      </div>
        <div class="form-group">
        <label for="address_line_3" class="col-sm-2 control-label">Address Line 3</label>
        <div class="col-sm-10">
          <input type="text" class="form-control" value="<?=$db->f_address_line_3?>" id="address_line_3" name="address_line_3" />
        </div>
      </div>
        
        <div class="form-group">
        <label for="state" class="col-sm-2 control-label"><span class="error">*</span>District</label>
        <div class="col-sm-10">
<!--           <select name="state" id="state" class="form-control">
		  <option value="-">Select One</option>
                <?=$functions->createCombo("mast_state","state","state",$db->f_state," order by state asc"); ?>
              </select>     -->
            <input type="text" name="state" id="state" class="form-control" value="<?=$db->f_state?>">
          
        </div>
      </div>
        
         <div class="form-group">
        <label for="city" class="col-sm-2 control-label"><span class="error">*</span>City</label>
        
        <div class="col-sm-10">
<!--          <input type="text" class="form-control" value="<?=$db->f_city?>" id="city" name="city"/>-->
          
          <select name="city" id="city" class="form-control">
		  <option value="">Select One</option>
                 <?=$functions->createCombo("mast_state","state","state",$db->f_city," order by state asc"); ?>
              </select> 
          
        </div>
      </div>
         
        
       
       
        <div class="form-group">
        <label for="zip" class="col-sm-2 control-label">Zip</label>
        <div class="col-sm-10">
          <input type="text" class="form-control" value="<?=$db->f_zip?>" id="zip" name="zip" maxlength="5" />
        </div>
      </div>
        <div class="form-group">
        <label for="phone" class="col-sm-2 control-label">Phone</label>
        <div class="col-sm-10">
            <input type="text" class="form-control" value="<?=$db->f_phone?>" id="phone" name="phone" maxlength="12"/>
        </div>
      </div>
      

      
<?php if($address_id>0){?>

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


