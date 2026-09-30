<?php
	include_once("../../../../bootstrap.php");


        $objGlobalConnection = new dbSqlConnection();
	$oGlobalConnection = $objGlobalConnection->Connect(HST_1,USR_1,PWD_1,DBN_1);
        
	$level_id = ($_REQUEST['level_id']?$_REQUEST['level_id']:0);
	
	if(strlen(trim($_POST['submit'])) > 0){
            
           
           
		$error = "";
		$success = 0;
//		if(strlen(trim($_POST['menu_name'])) < 1){
//			$error = "Please provide name";
//		}
		//if(strlen($error) < 1){
                        $i=0;
                        
                        $menu_id=implode(",",$_REQUEST['menu_name']);
			$db = new database();
			$db->data("access_menu_id",$menu_id); 
                        $db->where("level_id=".$level_id);
                        $db->update("access_levels");
                        $msg = "Access level successfully updated";
                        $success = 1;
			
		$array = array("success" => $success, "error" => $error, "msg" => $msg);
		echo json_encode($array);
		exit;
		
	}

	if($level_id > 0){
		$db = new database();
		$db->where("level_id=".$level_id."");
		$db->selectstmt("access_levels");
	}
?>
<script>
jQuery(document).ready(function(){
	
         var hidden_area_id=$("#hidden_level_id").val().trim();
	// validate signup form on keyup and submit
	jQuery("#meAdminForm").validate({
		errorElement:'div',
		rules: {
		 
		 
		},
		messages:{
		
		},
		// set this class to error-labels to indicate valid fields
		success: function(label){
			// set &nbsp; as text for IE
			label.hide();
		}, 
		submitHandler: function(form) {
			var k = $("#meAdminForm").serializeArray();
			<? if($level_id > 0){?>
				k.push({
					name: "level_id",
					value: <?=$level_id?>
                                        
				});
                               
			<? } ?>
			k = jQuery.param(k);
			
			jQuery("#divAlertSuccess, #divAlertError").hide();
			loadingIndicator('start');
			$( "#submit" ).prop( "disabled", true );
			$.post("ajax/access_level_new.php",k,function(data){
				loadingIndicator('end');
				if(data.success == 1){
                                    loadDefaultList();
                                     var hidden_area_id=$("#hidden_level_id").val().trim();
                                     if(hidden_area_id < 1){
					$("#divAlertSuccess").html(data.msg).show();
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
 
<style>
    .form-horizontal .control-label {
    margin-bottom: 0;
    padding-top: 13px;
    text-align: right;
}
    
</style>
<div class="alert alert-success" id="divAlertSuccess" style="display:none">Success! Well done its submitted.</div>
<div class="alert alert-danger"  id="divAlertError" style="display:none">Error ! Change few things.</div>
<div class="panel panel-info">
  <div class="panel-heading">
    <?=($level_id > 0?"Update":"Add New")?>
    Access Level </div>
  <div class="panel-body">
    <form class="form-horizontal" role="form" name="meAdminForm" id="meAdminForm" action="" method="post">
        <input type="hidden" value="<?php echo $level_id?>" name="hidden_level_id" id="hidden_level_id">
      <div class="form-group">
        <div class="col-sm-10" style="padding-top:0px ">
           
            <b>Access Level : </b> <?=$db->f_name?>
        </div>
      </div>
        <div class="row">
        <?php
        
            $menu_arr=  explode(",",$db->f_access_menu_id );
            $db_list = new database();
	$query = "SELECT access_menu_id,menu_name FROM  access_menu";		 
	$db_list->myCustomQuery($query);
            if($db_list->row_count > 0){
				
				for($i=0;$i<$db_list->row_count;$i++){
					$db_list->moveto($i);
                                        
                                        ?>
         
    <div class="col-sm-6">
        <div class="form-group">
        
        <div class="col-sm-2">
            <input type="checkbox" class="form-control" value="<?php echo $db_list->f_access_menu_id;?>" id="menu_name_<?php echo $db_list->f_access_menu_id?>" name="menu_name[]"  <?php if(in_array($db_list->f_access_menu_id, $menu_arr)){ echo "checked";}?>/>
        </div>
            <label for="menu_name_<?php echo $db_list->f_access_menu_id?>" class="col-sm-0 control-label"><?php echo $db_list->f_menu_name;?></label>
      </div>
    </div>  
  
        
        <?php
                                }
        }
        ?>
        
       </div>

      
<?php if($level_id>0){?>

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
