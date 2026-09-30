<?php
	include("../bootstrap.php");
	
	if(isset($_SESSION["vaahjobs-admin"]["al_id"])){
		header('Location: '.SITEROOT.'/admin/home.php'); 
		exit;
	}
	
	if(strlen(trim($_POST['submit'])) > 0){
		$error = "";
		$success = 0;
		
		$al_username = trim($_POST['al_username']);
		$al_password = trim($_POST['al_password']);
		
		if(strlen(trim($al_username)) < 1 || strlen(trim($al_password)) < 1){
			$error = "Please provide username / password";
		}
		
		if(strlen($error) < 1){
			$db=new database();
			$query="select al_id,al_username from vjgm_admin_login where al_username='".$al_username."' and al_password='".sha1($al_password)."'";
			$db->myCustomQuery($query);
			if($db->row_count > 0)
			{
				$_SESSION["vaahjobs-admin"]["al_id"]=$db->f_al_id;
				$_SESSION["vaahjobs-admin"]["al_username"]=$db->f_al_username;
				header("Location:home.php");
				exit;
			}
			else
			{
				$error = "Incorrect username / password";
			}
		}
	}

include(ROOTPATH.'/admin/templates/admin_header_start.php');
include(ROOTPATH.'/admin/templates/admin_header_end.php');

?>

<div class="container-fluid login-page">
  <div class="col-lg-4 col-md-4 col-sm-5 col-xs-11 col-lg-offset-4 col-md-offset-7 col-sm-offset-6">
    <div class="login-form">
    <? if($error){?>
    <div class="alert alert-danger">
      <?=$error ?>
    </div>
    <? }?>
    <? if($success){?>
    <div class="alert alert-success">
      <?=$success ?>
    </div>
    <? }?>
    <form rolo="form" method="post" class="" id="frmLoginForm" name="frmLoginForm">
      <div class="form-group">
        <div id="common_error_div" class="validation_div"></div>
        <input type="text" placeholder="USERNAME" name="al_username" id="al_username" class="form-control" />
      </div>
      <div class="form-group">
        <input type="password" placeholder="PASSWORD" name="al_password" id="al_password" class="form-control" />
      </div>
      <div class="form-group">
        <input type="submit" name="submit" id="submit" class="btn btn-default" value="SIGN IN" />
      </div>
      </div>
    </form>
  </div>
</div>
</div>
<br />
<br />
<br />
<br />
<br />
<?php 
include ROOTPATH.'/admin/templates/admin_footer_start.php';
?>
<script>
jQuery(document).ready(function(){
	$("#al_username").focus();
	// validate signup form on keyup and submit
	jQuery("#frmLoginForm").validate({
		errorElement:'div',
		rules: {
		 al_username:{
			required: true
		 },
		 al_password:{
			required: true
		 }
		 
		},
		messages:{
		 al_username:{
			required: "Enter username"
		 },
		 al_password:{
			required: "Enter password"
		 }
		},
		// set this class to error-labels to indicate valid fields
		success: function(label){
			// set &nbsp; as text for IE
			label.hide();
		}
	});	
	
});
</script>
<?
include ROOTPATH.'/admin/templates/admin_footer_end.php'; 
?>
