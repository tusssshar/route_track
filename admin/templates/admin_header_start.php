<?php
include("../bootstrap.php");
?>
<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
<meta charset="UTF-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars(company_settings::get("system_name") . " | " . company_settings::get("system_title")); ?></title>
<meta name="description" content="Routetrac" />
<meta name="keywords" content="Routetrac" />
<link rel="shortcut icon" type="image/x-icon" href="<?php echo SITEROOT;?>/admin/templates/images/favicon.ico" />
<link rel="stylesheet" href="<?php echo SITEROOT;?>/admin/templates/css/bootstrap.min.css">

<link href="<?=SITEROOT?>/admin/templates/js/font-awesome-4.6.3/css/font-awesome.min.css" rel="stylesheet">

<link href="<?php echo SITEROOT;?>/admin/templates/css/main.css" rel="stylesheet">
<link href="<?php echo SITEROOT;?>/admin/templates/css/datetimepicker.css" rel="stylesheet">
<?php echo company_settings::head_tags(); /* font, theme stylesheet, company colours, logo favicon */ ?>


<span class="error-msg" style="display:block;"><?php echo $_SESSION["routetrac-admin"]["error_success_msg"];?></span>
<script>var SITEROOT='<?php echo SITEROOT;?>';</script>
<?php unset($_SESSION["routetrac-admin"]["error_success_msg"]);?>
