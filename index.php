<?php
include("bootstrap.php");
if (isset($_SESSION["routetrac-admin"]["al_id"])) {
    header('Location: ' . SITEROOT . '/admin/home.php');
    exit;
}

if (strlen(trim($_POST['submit'])) > 0) {
    $error = "";
    $success = 0;

    $al_username = trim($_POST['al_username']);
    $al_password = trim(md5($_POST['al_password']));

    if (strlen(trim($al_username)) < 1 || strlen(trim($al_password)) < 1) {
        $error = "Please provide username / password";
    }

    //	Any user whose Access Level is "API Only" should not be able to log into 	access_level != 5

    if (strlen($error) < 1) {
        $db = new database();
        $query = "SELECT user_id,acct_username,acct_password,access_level,api_key FROM users WHERE acct_username='" . sanitize($al_username) . "' AND acct_password='" . sanitize($al_password) . "' AND status='1'";
        $db->myCustomQuery($query);
        if ($db->row_count > 0) {
            if ($db->f_access_level != 5) {
                $_SESSION["routetrac-admin"]["al_id"] = $db->f_user_id;
                $_SESSION["routetrac-admin"]["al_username"] = $db->f_acct_username;
                $_SESSION["routetrac-admin"]["al_password"] = $al_password;
                $_SESSION["routetrac-admin"]["access_level"] = $db->f_access_level;
                $_SESSION["routetrac-admin"]["api_key"] = $db->f_api_key;

                header('Location: ' . SITEROOT . '/admin/home.php');
                exit;
            } else {
                $error = "Error: Unable to login. Please contact your Administrator";
            }
        } else {
            $error = "Incorrect username / password";
        }
    }
}

include(ROOTPATH . '/admin/templates/admin_header_start.php');
include(ROOTPATH . '/admin/templates/admin_header_end.php');
?>

<div class="container-fluid login-page">
    <div class="col-lg-4 col-md-4 col-sm-5 col-xs-11 col-lg-offset-4 col-md-offset-7 col-sm-offset-6">
        <div class="login-form">
            <? if($error){?>
            <div class="alert alert-danger">
<?= $error ?>
            </div>
            <? }?>
            <? if($success){?>
            <div class="alert alert-success">
<?= $success ?>
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
include ROOTPATH . '/admin/templates/admin_footer_start.php';
?>
<script>
    jQuery(document).ready(function () {
        $("#al_username").focus();
        // validate signup form on keyup and submit
        jQuery("#frmLoginForm").validate({
            errorElement: 'div',
            rules: {
                al_username: {
                    required: true
                },
                al_password: {
                    required: true
                }

            },
            messages: {
                al_username: {
                    required: "Enter username"
                },
                al_password: {
                    required: "Enter password"
                }
            },
            // set this class to error-labels to indicate valid fields
            success: function (label) {
                // set &nbsp; as text for IE
                label.hide();
            }
        });

    });
</script>
<?
include ROOTPATH.'/admin/templates/admin_footer_end.php'; 
?>
