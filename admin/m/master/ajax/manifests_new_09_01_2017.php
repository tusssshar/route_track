<?php
	include_once("../../../../bootstrap.php");
           
	  	$manifest_id = ($_REQUEST['manifest_id']?$_REQUEST['manifest_id']:0);
	 
	if(strlen(trim($_POST['submit'])) > 0){
    
		$error = "";
		$success = 0;
		if(strlen(trim($_POST['num_areas'])) < 1){
			$error = "Please provide name";
		}
                
		if(strlen($error) < 1){
                    
                    if($manifest_id <=0){
                        $obj=new functions();
                        $manifest_last_id=$obj->get_last_id("manifest_id","manifests");
                        $delivery_last_id=$obj->get_last_id("delivery_id","deliveries");
                    }
			$db = new database();
                        $db1=new database();
                        $db2=new database();
                        
                        //Manifest table
			$db->data("submitted_by_user_id",$_SESSION["routetrac-admin"]["al_id"]);
                       
                        $db->data("num_areas",trim($_POST['num_areas']));
                        $db->data("num_routes",trim($_POST['num_routes']));
                        $db->data("num_deliveries",trim($_POST['num_deliveries']));
                        $db->data("num_packages",trim($_POST['num_packages']));
                        $db->data("num_items",trim($_POST['num_items']));
                        
                        //Deliveries table
                        
                        $db1->data("route_id",trim($_POST['route_id']));
                        $db1->data("driver_id",trim($_POST['driver_id']));
                        $db1->data("stop_number",trim($_POST['stop_number']));
                        $db1->data("customer_id",trim($_POST['customer_id']));
                        $db1->data("address_id",trim($_POST['address_id'])); 
                        $db1->data("status",trim($_POST['status']));                        
                        $db1->data("order_number",trim($_POST['order_number']));
                        $db1->data("cust_po_num",trim($_POST['cust_po_num']));
                        $db1->data("delivery_notes",trim($_POST['delivery_notes']));
                        $db1->data("driver_notes",trim($_POST['driver_notes']));
                        $db1->data("scheduled_delivery",trim($_POST['scheduled_delivery']));
                        $db1->data("delivery_time",trim($_POST['delivery_time']));
                        $db1->data("stop_number",trim($_POST['stop_number']));
                        
                        //Delivery Items
                        
			if($manifest_id < 1){
                             $db->data("manifest_type",trim($_POST['manifest_type']));
                             $db1->data("manifest_id",trim($manifest_last_id));
				$db->insert("manifests");
                                $db1->insert("deliveries");
                                $i=0;
                                //print_r($_REQUEST['qty']);
                               
                         foreach($_REQUEST['qty'] as $skulist){
                             
                             
                                $db2->data("delivery_id",trim($delivery_last_id));
                                $db2->data("sku",trim($skulist));
                                $db2->data("qty",trim($_REQUEST['qty'][$i]));
                                $db2->data("product_name",trim($_REQUEST['product_name'][$i]));
                                $db2->data("description",trim($_REQUEST['description'][$i]));
                                
                                $i++;
                                $db2->insert("delivery_items");
                             }
				$msg = "New manifest successfully added";
                               
				$success = 1;
                                
			}else{
                                //Update manifest table
				$db->where("manifest_id=".$manifest_id);
				$db->update("manifests");
				
                                //Update delivery table
                                $delivery_id=$_POST['temp_delivery_id'];
                                $db1->where("delivery_id=".$delivery_id);
				$db1->update("deliveries");
                                
                                //Update Delivery Item table
                                 $i=0;
                                //print_r($_REQUEST['item_id']);
                               foreach($_REQUEST['item_id'] as $skulist){
                             
                             
                                $db2->data("delivery_id",trim($delivery_id));
                                $db2->data("sku",trim($_REQUEST['sku'][$i]));
                                $db2->data("qty",trim($_REQUEST['qty'][$i]));
                                $db2->data("product_name",trim($_REQUEST['product_name'][$i]));
                                $db2->data("description",trim($_REQUEST['description'][$i]));
                                 if($_REQUEST['item_id'][$i]!=0){
                                     $db2->where("item_id=".$_REQUEST['item_id'][$i]);
				$db2->update("delivery_items");
                                 }else{
                                     
                                   
                                     $db2->insert("delivery_items");
                                     
                                 }
                                
                                $i++;
                             }
                                
				$msg = "User manifest updated";
				$success = 1;
			}
		}
		
		$array = array("success" => $success, "error" => $error, "msg" => $msg);
		echo json_encode($array);
		exit;
		
	}
        $show_type="Create Delivery";
	if($manifest_id > 0){
            $db = new database();
            $sql="SELECT * FROM manifests M LEFT JOIN deliveries D ON M.manifest_id=D.manifest_id WHERE M.manifest_id='".$manifest_id."'";
            $db->myCustomQuery($sql);
		
            $item_delivery_id=$db->f_delivery_id;
            $show_manifest_type=$db->f_manifest_type;
            if($show_manifest_type=="Delivery"){
                $show_type="Update Delivery";
            }else{
                $show_type="Update Pick Up";
            }
           $db1 = new database();
           $sql1="SELECT * FROM delivery_items  WHERE delivery_id='".$item_delivery_id."'";
            $res = $db1->myCustomQuery($sql1);
           
        }
         
?>
<script>
jQuery(document).ready(function(){
	$("#num_areas").focus();
     
	// validate signup form on keyup and submit
	jQuery("#meAdminForm").validate({
            
		errorElement:'div',
		rules: {
		 num_areas:{
                        required: true,
                        number : true
			
		 },
                 num_routes :{
                     required: true,
                     number : true
                    
                 },
                 num_deliveries :{
                     required: true,
                     number : true
                 },
                 num_packages : {
                     required: true,
                     number : true
                 },
                 num_items : {
                    required: true,
                    number : true
                 },
                status :{
                    required: true
                },
                route_id :{
                    required : true
                },
                driver_id :{
                    required : true
                },
                customer_id :{
                    required : true
                },
                address_id :{
                    required :true
                },
                'sku[]':{
                    required :true
                },
                'qty[]':{
                    required :true,
                    number: true
                },
                'product_name[]':{
                    required : true
                },
                'description[]':{
                    required :true
                },
                scheduled_delivery:{
                    required : true
                }
                
            },  
		messages:{
		 num_areas:{
			required: "Please enter no of  area"
			
		 },
                 num_routes :{
                     required: "Please enter no of route"
                 },
                 num_deliveries :{
                     required: "Please enter no of deliveries"
                 },
                 num_packages :{
                     required: "Please enter no of packages"
                 },
                 num_items :{
                     required:"Please enter no of items"
                 },
                 status:{
                     required :"Please select status"
                 },
                 route_id :{
                     required :"Please select route"
                 },
                 driver_id :{
                     required :"Please select driver"
                 },
                 customer_id :{
                     required :"Please select customer"
                 },
                 address_id :{
                     required :"Please select address"
                 },
                 'sku[]':{
                    required :"Please enter sku"
                },
                'qty[]':{
                    required :"Please enter quantity"
                },
                'product_name[]':{
                    required : "Please enter product name"
                },
                'description[]':{
                    required :"Please enter description"
                },
                scheduled_delivery :{
                    required :"Please select schedule date"
                }
                
		},
		// set this class to error-labels to indicate valid fields
		success: function(label){
			// set &nbsp; as text for IE
			label.hide();
		}, 
		submitHandler: function(form) {
			var k = $("#meAdminForm").serializeArray();
			<? if($manifest_id > 0){?>
				k.push({
					name: "manifest_id",
					value: <?=$manifest_id?>
                                        
				});
                               
			<? } ?>
			k = jQuery.param(k);
			
			jQuery("#divAlertSuccess, #divAlertError").hide();
			loadingIndicator('start');
			$( "#submit" ).prop( "disabled", true );
			$.post("ajax/manifests_new.php",k,function(data){
				loadingIndicator('end');
				if(data.success == 1){
					$("#divAlertSuccess").html(data.msg).show();
					loadDefaultList();
					$("#num_areas").focus();
					$("#num_areas").val("");
					$("#num_routes").val("");
                                        $("#num_deliveries").val("");
                                        $("#num_packages").val("");
                                        $("#num_items").val("");
                                      
                                        
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
<style>
    .panel-heading{
        
        height: 50px
    }
</style>
        
<div class="alert alert-success" id="divAlertSuccess" style="display:none">Success! Well done its submitted.</div>
<div class="alert alert-danger"  id="divAlertError" style="display:none">Error ! Change few things.</div>
<div class="panel panel-info">
    <div class="panel-heading">
        <label style="padding-top: 8px"><?=($manifest_id > 0?"Update":"Create")?>
            Manifest </label>
        
        <div class="btn-group" style="float: right">
            <button type="button" class="btn btn-primary" onclick="show_delivery()" value="Delivery" name="delivety" id="btn_delivery" style="background-color:#286090">Delivery</button>
  <button type="button" class="btn btn-primary" onclick="show_pickup()" id="btn_pickup" value="Pickup">Pick Up</button>
</div>
  </div>
   
    <br>
    <div class="panel-body"  id="delivery">
        <form class="form-horizontal" role="form" name="meAdminForm" id="meAdminForm" action="" method="post">
            <fieldset>
                <legend  id="lbl_id"><?php echo $show_type?></legend>
        <input type="hidden" value="<?php echo $item_delivery_id?>" name="temp_delivery_id">
          <input type="hidden" name="manifest_type" value="Delivery" id="manifest_type"> 
            
    <div class="container">
  <div class="row">
    <div class="col-sm-3">
        <label for="area" class="col-sm-0 control-label"><span class="error">*</span>No.Of Area</label>
     <input type="text"   value="<?=$db->f_num_areas?>" id="num_areas" name="num_areas"  class="form-control">
    </div>
    <div class="col-sm-3">
        <label for="route" class="col-sm-0 control-label"><span class="error">*</span>No.Of Route</label>
          <input type="text"  value="<?=$db->f_num_routes?>" id="num_routes" name="num_routes" class="form-control"/>
    </div>
    <div class="col-sm-3">
        <label for="deliveried" class="col-sm-0 control-label"><span class="error">*</span>No.Of Deliveries</label>
      <input type="text"  value="<?=$db->f_num_deliveries?>" id="num_deliveries" name="num_deliveries" class="form-control"/>
    </div>
       <div class="col-sm-3">
           <label for="package" class="col-sm-0 control-label"><span class="error">*</span>No.Of Packages</label>
      <input type="text"   value="<?=$db->f_num_packages?>" id="num_packages" name="num_packages" class="form-control"/>
    </div>
  </div>
    <br>
    <div class="row">
    <div class="col-sm-3">
        <label for="item" class="col-sm-0 control-label"><span class="error">*</span>No.Of Item</label>
    <input type="text"   value="<?=$db->f_num_items?>" id="num_items" name="num_items" class="form-control"/>
    </div>
    <div class="col-sm-3">
    
        <label for="route_id" class="col-sm-0 control-label"><span class="error">*</span>Select Route</label>
          <select name="route_id" id="route_id" class="form-control">
                          <option value="">Select one</option>
              
                      <?=$functions->createCombo("routes","route_id","name",$db->f_route_id," order by name asc"); ?>
                       </select>
    </div>
    <div class="col-sm-3">
        <label for="route_id" class="col-sm-0 control-label"><span class="error">*</span>Select Driver</label>
       <select name="driver_id" id="driver_id" class="form-control">
              <option value="">Select one</option>
              
                <?=$functions->createCombo("users","user_id","first_name",$db->f_driver_id," order by first_name asc","is_driver=1"); ?>
          </select>
    </div>
       <div class="col-sm-3">
           <label for="route_id" class="col-sm-0 control-label"><span class="error">*</span>Select Customer</label>
      <select name="customer_id" id="customer_id"  onchange="get_address()" class="form-control">
              <option value="">Select one</option>
              
                <?=$functions->createCombo("customers","customer_id","customer_name",$db->f_customer_id," order by customer_name asc"); ?>
          </select>
    </div>
  </div>
    <br>
    <div class="row">
    <div class="col-sm-3">
        <label for="route_id" class="col-sm-0 control-label"><span class="error">*</span>Select Address</label>
    <?php
                $addr="address_line_1,address_line_2,address_line_3";
            ?>
          <select name="address_id" id="address_id" class="form-control">
              <option value="">Select one</option>
              <?php if($manifest_id>0){?>
                <?=$functions->createCombo("customer_addresses","address_id",$addr,$db->f_address_id," order by address_line_1 asc","customer_id=".$db->f_customer_id); ?>
              <?php }?>
          </select>
    </div>
    <div class="col-sm-3">
    
        <label for="stop_nmuber" class="col-sm-0 control-label">Stop Number</label>
          <input type="text" value="<?=$db->f_stop_number?>" id="stop_number" name="stop_number" class="form-control"/>
    </div>
    <div class="col-sm-3">
        <label for="order_no" class="col-sm-0 control-label">Order No</label>
       <input type="text"  value="<?=$db->f_order_number?>" id="order_number" name="order_number" class="form-control"/>
    </div>
       <div class="col-sm-3">
           <label for="po_no" class="col-sm-0 control-label">PO No.</label>
        <input type="text"  value="<?=$db->f_cust_po_num?>" id="cust_po_num" name="cust_po_num" class="form-control"/>
    </div>
  </div>
    <br>
    <div class="row">
    <div class="col-sm-3">
        <label for="delivery_note" class="col-sm-0 control-label">Delivery Notes</label>
      <input  value="<?=$db->f_delivery_notes?>" id="delivery_notes" name="delivery_notes" class="form-control"/>
    </div>
    <div class="col-sm-3">
    
        <label for="driver_note" class="col-sm-0 control-label">Driver Notes</label>
          <input value="<?=$db->f_driver_notes?>" id="driver_notes" name="driver_notes" class="form-control" />
    </div>
    <div class="col-sm-3">
        <label for="schedule_date" class="col-sm-0 control-label"><span class="error">*</span>Schedule Date</label>
        <input type="text" value="<?=$db->f_scheduled_delivery?>" id="scheduled_delivery" name="scheduled_delivery" class="form-control"/>
    </div>
       <div class="col-sm-3">
           <label for="status" class="col-sm-0 control-label"><span class="error">*</span>Status</label>
       <select name="status" id="status" class="form-control">
              
            <option value="In Transit" <?=($db->f_status == 'In Transit'?"selected='selected'":"")?> >In Transit</option>
            <option value="Completed" <?=($db->f_status == 'Completed'?"selected='selected'":"")?>>Completed</option>
             <option value="Refused" <?=($db->f_status == 'Refused'?"selected='selected'":"")?>>Refused</option>
            
            
          </select>
    </div>
  </div>
    <br>
    <!--Delivery Info-->
    <fieldset>
        <legend>Delivery Info</legend>   
<?php
     
                   
                    if($manifest_id<=0){
               
?>
<div class="input_fields_wrap">
    <div class="row">
    <div class="col-sm-2">
<label for="sku" class="col-sm-0 control-label"><span class="error">*</span>SKU</label>      
<input type="text" value="" id="sku" name="sku[]" class="form-control">
    </div>
    <div class="col-sm-2">
        <label for="quantity" class="col-sm-0 control-label"><span class="error">*</span>Quantity</label>
      <input type="text" value="" id="qty" name="qty[]" class="form-control"/>
    </div>
    <div class="col-sm-2">
        <label for="product_name" class="col-sm-0 control-label"><span class="error">*</span>Product Name</label>
        <input type="text" value="" id="product_name" name="product_name[]" class="form-control"/>
    </div>
        
       <div class="col-sm-2">
           <label for="description" class="col-sm-0 control-label"><span class="error">*</span>Description</label>
         <input type="text" value="" id="description" name="description[]" class="form-control"/>
    </div>
         <div class="col-sm-2">
		 <label class="col-sm-0 control-label">&nbsp;</label>
          <button class="add_field_button form-control btn-info">Add More Fields</button>
    </div>
  </div>  
     </div>
<?php }else{
                        ?>
               
            <div class="row">
    <div class="col-sm-2">
<label for="sku" class="col-sm-0 control-label"><span class="error">*</span>SKU</label>      
    </div>
    <div class="col-sm-2">
        <label for="quantity" class="col-sm-0 control-label"><span class="error">*</span>Quantity</label>
    </div>
    <div class="col-sm-2">
        <label for="product_name" class="col-sm-0 control-label"><span class="error">*</span>Product Name</label>
        
    </div>
        
       <div class="col-sm-2">
           <label for="description" class="col-sm-0 control-label"><span class="error">*</span>Description</label>
         
    </div>
         <div class="col-sm-2">
          <button class="add_field_button form-control btn-info">Add More Fields</button>
    </div>
  </div> 
                  
                <div class="input_fields_wrap">
                    
                <?php
                         if($db1->row_count > 0){
                             $j=$db1->row_count-1;
		for($i=0;$i<$db1->row_count;$i++){
                   $db1->moveto($i);
                    ?>
                
                    <div>
                
      <div class="row">
    <div class="col-sm-2">
    
<input type="text"  id="sku" name="sku[]" class="form-control" value="<?=$db1->f_sku?>">
    </div>
    <div class="col-sm-2">
        
      <input type="text"  id="qty" name="qty[]" class="form-control" value="<?=$db1->f_qty?>"/>
    </div>
    <div class="col-sm-2">
        
        <input type="text"  id="product_name" name="product_name[]" class="form-control" value="<?=$db1->f_product_name?>"/>
    </div>
        
       <div class="col-sm-2">
           
         <input type="text"  id="description" name="description[]" class="form-control" value="<?=$db1->f_description?>"/>
         <input type="hidden" value="<?=$db1->f_item_id?>" id="item_id" name="item_id[]"/>
    </div>
         <div class="col-sm-2">
          
             <a href='javascript:void(0)' class='remove_field' id="<?php echo $db1->f_item_id?>">Remove</a>
             
    </div>
  </div>                <?php
                        if($i<$j){
                            ?>
                        <br>
                        <?php }?>
               
                </div>
                <?php
                }
                ?>
                </div>
                <?php
	      }
              
                }?>
    
    </fieldset>
    
    
    <br>
    <div class="row">
    <div class="col-sm-3">
        
      
      <?php if($manifest_id>0){?>

      <div class="form-group">
        <div class="col-sm-offset-2 col-sm-10">
          <button type="submit" name="submit" id="submit" value=" Save " class="btn btn-success"></i> Update Delivery</button>
            <span style="color:#999999">or</span>&nbsp; <span class="cancellink"><a href="javascript:void(0);" onClick="javascript:ajaxNewModifyFormCancel();">Cancel</a></span> </div>
      </div>
<?php }else{?>
            <div class="form-group">
        <div class="col-sm-10">
          <button type="submit" name="submit" id="submit" value=" Save " class="btn btn-success"></i> Create Delivery </button>
            <span style="color:#999999">or</span>&nbsp; <span class="cancellink"><a href="javascript:void(0);" onClick="javascript:ajaxNewModifyFormCancel();">Cancel</a></span> </div>
      </div>
<?php }?>
       
  </div>
</div>
       
    </div>
            </fieldset>
     </form>
</div>



<script>
     function get_address(){
            var cust_id=$("#customer_id").val();
            $.ajax({
            type: "POST",
            url: SITEROOT +"/admin/ajax/admin_ajax_action.php?action=get_address&cust-id="+cust_id,
            data:{cust_id:cust_id},
            success: function(data){
                    $("#address_id").html(data);
            }
            });
        }
    
         function show_delivery(){
             $("#lbl_id").text("Create Delivery");
             $("#manifest_type").val("Delivery");
             //$("#btn_delivery").css('background-color','#FFFFFF');
            // $("#btn_pickup").css('background-color','');
                      
        }
        function show_pickup(){ 
             $("#lbl_id").text("Enter Pick Up");
             $("#manifest_type").val("Pickup");
             //$("#btn_pickup").css('background-color','#FFFFFF');
             $("#btn_delivery").css('background-color','');
        }
        
        $(document).ready(function() {
   // var max_fields      = 10; //maximum input boxes allowed
    var wrapper         = $(".input_fields_wrap"); //Fields wrapper
    var add_button      = $(".add_field_button"); //Add button ID
   
    var x = 1; //initlal text box count
    $(add_button).click(function(e){ //on add input button click
        e.preventDefault();
        //if(x < max_fields){ //max input box allowed
           // x++; //text box increment
            $(wrapper).append("<div><div class='row'><br/><div class='col-sm-2'><input type='text' id='sku' name='sku[]' class='form-control'/></div><div class='col-sm-2'><input type='text' id='qty' name='qty[]'class='form-control'/></div><div class='col-sm-2'><input type='text'  id='product_name' name='product_name[]' class='form-control'/></div><div class='col-sm-2'><input type='text' id='description' name='description[]' class='form-control'/></div><div class='col-sm-2'><a href='javascript:void(0)' class='remove_field'>Remove</a><input type='hidden' value='0' id='item_id' name='item_id[]'/></div></div></div>"); //add input box
       // }
    });
   
    
    $(wrapper).on("click",".remove_field", function(e){ //user click on remove text
        if(confirm('Are you sure to delete')){
      
         var item_id = $(this).attr('id');
          $.ajax({
            type: "POST",
            url: SITEROOT +"/admin/ajax/admin_ajax_action.php?action=delete_item",
            data:{item_id:item_id},
            success: function(data){
                    //$("#address_id").html(data);
            }
            });
        
        e.preventDefault(); $(this).parent('div').parent('div').remove(); 
//x--;
   }
    })
        
});

$(function() {
	
        $('#scheduled_delivery').datetimepicker();
	
});


</script>

  
    
  
  
 
    