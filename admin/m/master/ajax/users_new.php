<?php
	include_once("../../../../bootstrap.php");
        
	 $user_id = ($_REQUEST['user_id']?$_REQUEST['user_id']:0);

	if(strlen(trim($_POST['submit'])) > 0){
		$error = "";
		$success = 0;
		if(strlen(trim($_POST['first_name'])) < 1){
			$error = "Please provide name";
		}
		//	Driver KYC: licence classes, experience, Aadhaar (checksum + unique)
		$license_classes = array_values(array_intersect(isset($_POST['license_classes']) && is_array($_POST['license_classes']) ? $_POST['license_classes'] : array(), array_keys(fleet::$LICENSE_CLASSES)));
		$aadhaar = preg_replace('/\s+/', '', (string) $_POST['aadhaar_no']);
		$experience = trim((string) $_POST['experience_years']);
		if(strlen($error) < 1 && $aadhaar != "" && !fleet::valid_aadhaar($aadhaar)){
			$error = "Aadhaar number is not valid (12 digits, checksum failed)";
		}
		if(strlen($error) < 1 && $aadhaar != "" && fleet::row("SELECT user_id FROM users WHERE aadhaar_no='".sanitize($aadhaar)."' AND user_id<>".(int)$user_id)){
			$error = "This Aadhaar number is already registered for another user";
		}
		if(strlen($error) < 1 && $experience != "" && (!ctype_digit($experience) || (int) $experience > 60)){
			$error = "Driving experience must be a number of years (0-60)";
		}
		if(strlen($error) < 1 && trim($_POST['license_expiry']) != "" && !fleet::valid_date(trim($_POST['license_expiry']))){
			$error = "Licence expiry is not a valid date";
		}
		if(strlen($error) < 1){
			$db = new database();
			$db->data("first_name",trim($_POST['first_name']));
			$db->data("last_name",trim($_POST['last_name']));
			$db->data("email_addr",trim($_POST['email_addr']));
			$db->data("status",trim($_POST['status']));
			$db->data("access_level",trim($_POST['access_level']));
			$db->data("is_driver",trim($_POST['is_driver']));
			$db->data("default_route_id",trim($_POST['default_route_id']));
			$db->data("license_no",strtoupper(trim($_POST['license_no'])));
			$db->data("phone",trim($_POST['phone']));
			$db->data("license_classes",implode(",", $license_classes));
			if($aadhaar != ""){	//	blank = keep the number already on file (the form only shows it masked)
				$db->data("aadhaar_no",$aadhaar);
			}
			//	licence expiry is checked when the driver is put on a trip; blank = NULL
			$license_expiry_sql = "license_expiry=".fleet::date_sql($_POST['license_expiry']).", experience_years=".($experience == "" ? "NULL" : (int) $experience);
			
			//	If the user DOES NOT check the checkbox (or the checkbox is not visible), please insert '' (empty string) into `users`.`api_key`.
			//	If the user DOES check the checkbox, please call static function RouteTracAPI::makeAPIKey() which will return a string.  Use that string for the value of `users`.`api_key`.
			
			//	"API Only" users (access level 5) always need a key; the checkbox is disabled for them so the browser does not post it
			if(($_POST['is_api_access'] == '' || $_POST['is_api_access'] == 0) && $_POST['access_level'] != 5){
				$db->data("api_key",'');
			}else{
				//	keep an existing key so editing a user does not break their integrations
				$existing_api_key = '';
				if($user_id > 0){
					$db_key = new database();
					$db_key->myCustomQuery("SELECT api_key FROM users WHERE user_id=".(int)$user_id);
					$existing_api_key = $db_key->f_api_key;
				}
				$api_key = ($existing_api_key != '') ? $existing_api_key : bin2hex(random_bytes(16));
				$db->data("api_key",$api_key);
			}
					   
			if($user_id < 1){
				$db->data("acct_username",trim($_POST['acct_username']));
				$db->data("acct_password",trim(md5($_POST['acct_password'])));
				$db->insert("users");
				$db_licence = new database();
				$db_licence->myCustomQuery("UPDATE users SET ".$license_expiry_sql." WHERE user_id=".(int)$db->id, 1);
				$msg = "New user successfully added";
				$success = 1;
                                
			}else{
				$db->where("user_id=".$user_id);
				$db->update("users", $license_expiry_sql);
				$msg = "User successfully updated";
				$success = 1;
			}
		}
		
		//	Scanned licence / Aadhaar copies (kept outside public access, see driver_doc.php)
		if($success == 1){
			$saved_id = $user_id > 0 ? (int) $user_id : (int) $db->id;
			foreach(array("license_doc" => "license", "aadhaar_doc" => "aadhaar") as $column => $type){
				list($file, $doc_error) = fleet::save_driver_doc($column, $saved_id, $type);
				if($doc_error != ""){
					$error = $doc_error;
					$success = 0;
					$msg = "";
				}elseif($file != ""){
					$old = fleet::row("SELECT ".$column." AS f FROM users WHERE user_id=".$saved_id);
					fleet::exec("UPDATE users SET ".$column."='".sanitize($file)."' WHERE user_id=".$saved_id);
					if($old && $old['f'] != "" && is_file(ROOTPATH."/uploads/driver_docs/".basename($old['f']))){
						@unlink(ROOTPATH."/uploads/driver_docs/".basename($old['f']));
					}
				}
			}
			if($success == 0){
				$error = "User saved, but: ".$error;
			}
		}

		$array = array("success" => $success, "error" => $error, "msg" => $msg);
		echo json_encode($array);
		exit;
		
	}

	if($user_id > 0){
		$db = new database();
		$db->where("user_id=".$user_id."");
		$db->selectstmt("users");
	}
?>
<script>
jQuery(document).ready(function(){
	$("#first_name").focus();
        var hidden_user_id=$("#hidden_user_id").val().trim();
	// validate signup form on keyup and submit
        
	jQuery("#meAdminForm").validate({
		errorElement:'div',
		rules: {
		 first_name:{
                        required: true,
                        number : false
			
		 },
                 last_name :{
                     required: true,
                     number : false
                    
                 },
                 email_addr : {
                     required: true,
                     email : true,
                     remote: {url:SITEROOT + "/admin/ajax/admin_ajax_action.php?action=check_email&hidden_user_id="+hidden_user_id, type:"post",async: true}

                 },
                 acct_username : {
                    required: true,
                    remote: {url:SITEROOT + "/admin/ajax/admin_ajax_action.php?action=check_user_name&hidden_user_id="+hidden_user_id, type:"post",async: true}

                 },
                 acct_password : {
                    required: true

		},
                access_level :{
                     required: true
                },
                status :{
                    required: true
                },
                default_route_id :{
                    required:true
                }
            },  
		messages:{
		 first_name:{
			required: "Please enter first name"
			
		 },
                 last_name :{
                     required: "Please enter last name"
                 },
                 email_addr :{
                     required: "Please enter email-id",
                     remote : "This email is allready exists"
                 },
                 acct_username :{
                     required: "Please enter user name",
                     remote: "This user name is allready exists"
                 },
                 acct_password :{
                     required: "Please enter password"
                 },
                 access_level :{
                     required : "Please select access level"
                 },
                 status:{
                     required : "Please select status"
                 },
                 default_route_id :{
                     required :"Please select default route"
                 }
		},
		// set this class to error-labels to indicate valid fields
		success: function(label){
			// set &nbsp; as text for IE
			label.hide();
		}, 
		submitHandler: function(form) {
			var k = $("#meAdminForm").serializeArray();
			<? if($user_id > 0){?>
				k.push({
					name: "user_id",
					value: <?=$user_id?>
                                        
				});
                               
			<? } ?>
			// multipart, so the licence / Aadhaar copies are sent too
			var fd = new FormData();
			$.each(k, function(i, f){ fd.append(f.name, f.value); });
			$("#meAdminForm input[type=file]").each(function(){ if(this.files.length){ fd.append(this.name, this.files[0]); } });
			k = fd;
			
			jQuery("#divAlertSuccess, #divAlertError").hide();
			loadingIndicator('start');
			$( "#submit" ).prop( "disabled", true );
			$.ajax({ url: "ajax/users_new.php", type: "POST", data: k, processData: false, contentType: false, dataType: "json" }).done(function(data){
				loadingIndicator('end');
				if(data.success == 1){
					var hidden_user_id = $("#hidden_user_id").val();

					loadDefaultList();

					if(hidden_user_id < 1){
						$("#divAlertSuccess").html(data.msg).show();
						$("#first_name").focus();
						$("#first_name").val("");
						$("#last_name").val("");
						$("#email_addr").val("");
						$("#acct_username").val("");
						$("#acct_password").val("");
						$("#access_level").val("");
						$("#status").val("");
						$("#default_route_id").val("");
						$('#is_driver').attr('checked', false);
						$("#meAdminForm input[type=file], #aadhaar_no, #experience_years, #license_no, #license_expiry, #phone").val("");
						$("input[name='license_classes[]']").prop("checked", false);
						$( "#submit" ).prop( "disabled", false );
					}else{
						$("#successMsg").html(data.msg).show();
						ajaxNewModifyFormCancel();
					}
				}else{
					$("#divAlertError").html(data.error).show();
					$( "#submit" ).prop( "disabled", false );	// let the user correct the form and save again
				}
				var k = setTimeout(function(){
					jQuery("#divAlertSuccess, #divAlertError").hide();
				},10000);
				
			});
		}
	});	
	
});
</script>

<div class="alert alert-success" id="divAlertSuccess" style="display:none">Success! Well done its submitted.</div>
<div class="alert alert-danger"  id="divAlertError" style="display:none">Error ! Change few things.</div>
<div class="panel panel-info">
  <div class="panel-heading">
    <?=($user_id > 0?"Update":"Add New")?>
    User </div>
  <div class="panel-body">
    <form class="form-horizontal" role="form" name="meAdminForm" id="meAdminForm" action="" method="post">
      <input type="hidden" value="<?php echo $user_id?>" name="hidden_user_id" id="hidden_user_id">
      <div class="form-group">
        <label for="first_name" class="col-sm-2 control-label"><span class="error">*</span>First Name</label>
        <div class="col-sm-10">
          <input type="text" class="form-control" value="<?=$db->f_first_name?>" id="first_name" name="first_name"/>
        </div>
      </div>
      <div class="form-group">
        <label for="last_name" class="col-sm-2 control-label"><span class="error">*</span>Last Name</label>
        <div class="col-sm-10">
          <input type="text" class="form-control" value="<?=$db->f_last_name?>" id="last_name" name="last_name"/>
        </div>
      </div>
      <div class="form-group">
        <label for="email_addr" class="col-sm-2 control-label"><span class="error">*</span>Email-ID</label>
        <div class="col-sm-10">
          <input type="text" class="form-control" value="<?=$db->f_email_addr?>" id="email_addr" name="email_addr"/>
        </div>
      </div>
      <?php 
        if($user_id<=0){?>
      <div class="form-group">
        <label for="acct_username" class="col-sm-2 control-label"><span class="error">*</span>User Name</label>
        <div class="col-sm-10">
          <input type="text" class="form-control" value="<?=$db->f_acct_username?>" id="acct_username" name="acct_username" />
        </div>
      </div>
      <div class="form-group">
        <label for="acct_password" class="col-sm-2 control-label"><span class="error">*</span>Password</label>
        <div class="col-sm-10">
          <input type="password" class="form-control" value="<?=$db->f_acct_password?>" id="acct_password" name="acct_password"/>
        </div>
      </div>
      <?php }?>
      <div class="form-group">
        <label for="is_driver" class="col-sm-2 control-label">Is Driver</label>
        <div class="col-sm-2">
          <input type="checkbox" class="form-control" value="1" id="is_driver" name="is_driver" maxlength="50" <?php if($db->f_is_driver==1){ echo "checked";}?> onclick="show_routes()"/>
        </div>
      </div>
      <div class="form-group" <?php if($db->f_is_driver==0) {?>style="display: none" <?php } ?>id="route_div">
        <label for="default_route_id" class="col-sm-2 control-label"><span class="error">*</span>Default Route</label>
        <div class="col-sm-10">
          <select name="default_route_id" id="default_route_id" class="form-control">
            <option value="">Select one</option>
            <?=$functions->createCombo("routes","route_id","name",$db->f_default_route_id," order by name asc"); ?>
          </select>
        </div>
      </div>
      <div class="form-group driver-field" <?php if($db->f_is_driver==0) {?>style="display: none" <?php } ?>>
        <label for="license_no" class="col-sm-2 control-label">Licence No</label>
        <div class="col-sm-4">
          <input type="text" class="form-control" value="<?=htmlspecialchars((string)$db->f_license_no)?>" id="license_no" name="license_no" maxlength="50"/>
        </div>
        <label for="license_expiry" class="col-sm-2 control-label">Valid Till</label>
        <div class="col-sm-4">
          <input type="date" class="form-control" value="<?=htmlspecialchars((string)$db->f_license_expiry)?>" id="license_expiry" name="license_expiry"/>
        </div>
      </div>
      <div class="form-group driver-field" <?php if($db->f_is_driver==0) {?>style="display: none" <?php } ?>>
        <label for="phone" class="col-sm-2 control-label">Mobile</label>
        <div class="col-sm-4">
          <input type="text" class="form-control" value="<?=htmlspecialchars((string)$db->f_phone)?>" id="phone" name="phone" maxlength="20"/>
        </div>
        <label for="experience_years" class="col-sm-2 control-label">Experience</label>
        <div class="col-sm-4">
          <div class="input-group"><input type="number" min="0" max="60" class="form-control" value="<?=htmlspecialchars((string)$db->f_experience_years)?>" id="experience_years" name="experience_years"/><span class="input-group-addon">years</span></div>
        </div>
      </div>
      <div class="form-group driver-field" <?php if($db->f_is_driver==0) {?>style="display: none" <?php } ?>>
        <label class="col-sm-2 control-label">Licence classes</label>
        <div class="col-sm-10">
          <?php $have_classes = fleet::license_class_list($db->f_license_classes);
          foreach(fleet::$LICENSE_CLASSES as $code => $label){ ?>
            <label class="checkbox-inline" title="<?=htmlspecialchars($label)?>"><input type="checkbox" name="license_classes[]" value="<?=htmlspecialchars($code)?>" <?=in_array($code, $have_classes) ? "checked" : ""?>> <?=htmlspecialchars($code)?></label>
          <?php } ?>
          <p class="help-block" style="margin-bottom:0">Trucks need HMV; vans, pickups and mini trucks need LMV-TR or HMV. Checked when the driver is put on a trip.</p>
        </div>
      </div>
      <div class="form-group driver-field" <?php if($db->f_is_driver==0) {?>style="display: none" <?php } ?>>
        <label for="aadhaar_no" class="col-sm-2 control-label">Aadhaar No</label>
        <div class="col-sm-4">
          <input type="text" class="form-control" value="" id="aadhaar_no" name="aadhaar_no" maxlength="14" inputmode="numeric"
            placeholder="<?=$db->f_aadhaar_no != "" ? htmlspecialchars(fleet::mask_aadhaar($db->f_aadhaar_no))." (on file)" : "12-digit number"?>"/>
          <?php if($db->f_aadhaar_no != ""){ ?><p class="help-block" style="margin-bottom:0">Leave empty to keep the number on file.</p><?php } ?>
        </div>
      </div>
      <div class="form-group driver-field" <?php if($db->f_is_driver==0) {?>style="display: none" <?php } ?>>
        <label for="license_doc" class="col-sm-2 control-label">Licence copy</label>
        <div class="col-sm-4">
          <input type="file" class="form-control" id="license_doc" name="license_doc" accept="image/*,.pdf" style="padding:4px;height:auto"/>
          <?php if($db->f_license_doc != ""){ ?><a href="<?=SITEROOT?>/admin/m/master/driver_doc.php?user_id=<?=(int)$user_id?>&amp;type=license" target="_blank"><i class="fa fa-file-image-o"></i> View licence copy on file</a><?php } ?>
        </div>
        <label for="aadhaar_doc" class="col-sm-2 control-label">Aadhaar copy</label>
        <div class="col-sm-4">
          <input type="file" class="form-control" id="aadhaar_doc" name="aadhaar_doc" accept="image/*,.pdf" style="padding:4px;height:auto"/>
          <?php if($db->f_aadhaar_doc != ""){ ?><a href="<?=SITEROOT?>/admin/m/master/driver_doc.php?user_id=<?=(int)$user_id?>&amp;type=aadhaar" target="_blank"><i class="fa fa-file-image-o"></i> View Aadhaar copy on file</a><?php } ?>
        </div>
      </div>
      <div class="form-group">
        <label for="access_level" class="col-sm-2 control-label"><span class="error">*</span>Access Level</label>
        <div class="col-sm-10">
          <?php 
		  
		  //	In the "Update User" and "Add New User" popup panels, please make sure that the currently-logged-in user CANNOT select "API Only" for their own Access Level
		  
			$objGlobalConnection = new dbSqlConnection();
			$oGlobalConnection = $objGlobalConnection->Connect(HST_1,USR_1,PWD_1,DBN_1);

			$apiOnlyWhere = "+";
			if($_SESSION["routetrac-admin"]["al_id"] == $user_id){
				$apiOnlyWhere = " level_id != 5 ";
			}
			
             ?>
            <select name="access_level" id="access_level" class="form-control" onclick="var  access_level_id= $('#access_level').val();if(access_level_id==5){$('#is_api_access').prop('checked', true);$('#is_api_access').prop('disabled', true); }else{ $('#is_api_access').prop('disabled', false);}">
            <option value="">Select one</option>
            <?=$functions->createCombo("access_levels","level_id","name",$db->f_access_level," order by name asc",$apiOnlyWhere); ?>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label for="status" class="col-sm-2 control-label"><span class="error">*</span>Status</label>
        <div class="col-sm-10">
          <select name="status" id="status" class="form-control">
            <option value="1" <?=($db->f_status == 1?"selected='selected'":"")?> >Active</option>
            <option value="0" <?=($db->f_status == 0?"selected='selected'":"")?>>Inactive</option>
          </select>
        </div>
      </div>
	  
	  <?php
	  	//	This checkbox should ONLY be visible if the logged-in user has Access Level of "Administrator" or "User Manager".
	  	if($_SESSION["routetrac-admin"]["access_level"] == 1 || $_SESSION["routetrac-admin"]["access_level"] == 2){
	  ?>
	  <div class="form-group">
        <label for="is_driver" class="col-sm-2 control-label">API Access</label>
        <div class="col-sm-2">
          <input type="checkbox" class="form-control" value="1" id="is_api_access" name="is_api_access" <?php if($db->f_api_key != '' || $db->f_access_level==5){ echo "checked ";}
                 
                 if($db->f_access_level==5){
                     echo " disabled";
                 }?> 
                 />
        </div>
      </div>
	  <?php
	  	}
	  ?>
	  
      <?php if($user_id>0){?>
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
