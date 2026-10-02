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

<div class="rt-login">
    <div class="rt-login-hero">
        <div class="rt-login-brand"><?= company_settings::brand_html(true) ?></div>
        <div>
            <h1>Every route, every vehicle, <span>live.</span></h1>
            <p class="lead">Plan trips, assign drivers and vehicles, track deliveries on the map and close every trip with a complete trip sheet.</p>
            <ul class="rt-login-points">
                <li><i class="fa fa-map-marker"></i> Live GPS tracking</li>
                <li><i class="fa fa-truck"></i> Driver &amp; vehicle dispatch</li>
                <li><i class="fa fa-cubes"></i> SKU load on every vehicle</li>
                <li><i class="fa fa-road"></i> Toll, tax &amp; fuel per trip</li>
                <li><i class="fa fa-check-square-o"></i> Proof of delivery</li>
                <li><i class="fa fa-file-text-o"></i> Trip sheet &amp; settlement</li>
            </ul>
        </div>
        <div class="rt-road-scene" aria-hidden="true">
            <svg viewBox="0 0 1400 150" preserveAspectRatio="xMinYMax slice">
                <path d="M0 70 C 250 30, 450 110, 700 70 S 1150 30, 1400 70 L1400 150 L0 150 Z" fill="rgba(255,255,255,.05)"/>
                <rect x="0" y="92" width="1400" height="58" fill="rgba(0,0,0,.35)"/>
                <line class="rt-dash" x1="0" y1="121" x2="1400" y2="121" stroke="var(--rt-stripe)" stroke-width="4"/>
                <g transform="translate(330,64)"><circle class="rt-pin" r="9" fill="var(--rt-primary)"/><circle r="5" fill="var(--rt-primary)"/></g>
                <g transform="translate(640,58)"><circle class="rt-pin" r="9" fill="var(--rt-accent)"/><circle r="5" fill="var(--rt-accent)"/></g>
                <g class="rt-truck">
                    <g transform="translate(0,72)">
                        <rect x="0" y="8" width="70" height="34" rx="4" fill="var(--rt-primary)"/>
                        <path d="M70 18 h20 l14 14 v10 h-34 z" fill="#e5e7eb"/>
                        <rect x="76" y="21" width="12" height="9" rx="1" fill="#93c5fd"/>
                        <circle cx="18" cy="44" r="7" fill="#111827"/><circle cx="18" cy="44" r="3" fill="#9ca3af"/>
                        <circle cx="88" cy="44" r="7" fill="#111827"/><circle cx="88" cy="44" r="3" fill="#9ca3af"/>
                    </g>
                </g>
            </svg>
        </div>
    </div>
    <div class="rt-login-side">
        <div class="rt-login-card">
            <h2>Welcome back</h2>
            <div class="sub">Sign in to <?= htmlspecialchars(company_settings::get("system_name")) ?></div>
            <? if($error){?>
            <div class="alert alert-danger"><?= $error ?></div>
            <? }?>
            <? if($success){?>
            <div class="alert alert-success"><?= $success ?></div>
            <? }?>
            <form method="post" id="frmLoginForm" name="frmLoginForm">
                <div id="common_error_div" class="validation_div"></div>
                <div class="form-group">
                    <label for="al_username">Username</label>
                    <div class="input-group"><span class="input-group-addon"><i class="fa fa-user"></i></span>
                        <input type="text" placeholder="Your username" name="al_username" id="al_username" class="form-control" autocomplete="username" /></div>
                </div>
                <div class="form-group">
                    <label for="al_password">Password</label>
                    <div class="input-group"><span class="input-group-addon"><i class="fa fa-lock"></i></span>
                        <input type="password" placeholder="Your password" name="al_password" id="al_password" class="form-control" autocomplete="current-password" /></div>
                </div>
                <div class="form-group" style="margin-top:22px">
                    <button type="submit" name="submit" id="submit" value="SIGN IN" class="btn btn-primary"><i class="fa fa-sign-in"></i>&nbsp; Sign in</button>
                </div>
            </form>
            <div class="rt-help">Drivers: use the driver app or <a href="<?= SITEROOT ?>/driver/">driver page</a><?php
                $phone = company_settings::get("support_phone"); echo $phone != "" ? "<br>Support " . htmlspecialchars($phone) : ""; ?></div>
        </div>
    </div>
</div>
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
            },
            errorPlacement: function (error, element) {
                error.insertAfter(element.closest(".input-group"));
            }
        });

    });
</script>
<?
include ROOTPATH.'/admin/templates/admin_footer_end.php'; 
?>
