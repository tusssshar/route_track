<?php
include_once("../../../../bootstrap.php");

//Get client id for options check/uncheck
$db_get_client_id = new database();
$db_get_client_id->selectstmt("client_id");
$client_id = $db_get_client_id->f_client_id;



$objGlobalConnection = new dbSqlConnection();
$oGlobalConnection = $objGlobalConnection->Connect(HST_1, USR_1, PWD_1, DBN_1);

if (strlen(trim($_POST['submit'])) > 0) {
    $error = "";
    $success = 0;
    /* if(strlen(trim($_POST['area_name'])) < 1){
      $error = "Please provide area name";
      } */

    if (strlen($error) < 1) {
        $db = new database();

        $admin_require_packages = $_POST['admin_require_packages'];
        $admin_require_items = $_POST['admin_require_items'];
        $db->data("admin_require_packages", $admin_require_packages);
        $db->data("admin_require_items", $admin_require_items);
        $db->where("client_id=" . $client_id);
        $db->update("clients");

        $msg = "Record successfully updated";
        $success = 1;
    }

    $array = array("success" => $success, "error" => $error, "msg" => $msg);
    echo json_encode($array);
    exit;
}


$db1 = new database();
$db1->selectstmt("clients");
?>
<script>
    jQuery(document).ready(function () {
        // validate signup form on keyup and submit
        jQuery("#meAdminForm").validate({
            errorElement: 'div',
            rules: {
            },
            messages: {
            },
            // set this class to error-labels to indicate valid fields
            success: function (label) {
                // set &nbsp; as text for IE
                label.hide();
            },
            submitHandler: function (form) {
                var k = $("#meAdminForm").serializeArray();

                k = jQuery.param(k);

                jQuery("#divAlertSuccess, #divAlertError").hide();
                loadingIndicator('start');
                //$("#submit").prop("disabled", true);
                $.post("ajax/options.php", k, function (data) {
                    loadingIndicator('end');
                    if (data.success == 1) {
                        // var hidden_area_id = $("#hidden_area_id").val().trim();
                        $("#divAlertSuccess").html(data.msg).show();
                        loadDefaultList();
                        
                            $("#successMsg").html(data.msg).show();
                            ajaxNewModifyFormCancel();
                        
                    } else {
                        $("#divAlertError").html(data.error).show();
                    }
                    var k = setTimeout(function () {
                        jQuery("#divAlertSuccess, #divAlertError").hide();
                    }, 10000);

                }, "json");
            }
        });

    });
</script>


<?php
$api_version = $db1->f_api_version;
$disabled = "";
if ($api_version < 2) {
    $disabled = " disabled ";
}
?>
<div class="alert alert-success" id="divAlertSuccess" style="display:none">Success! Well done its submitted.</div>
<div class="alert alert-danger"  id="divAlertError" style="display:none">Error ! Change few things.</div>
<div class="panel panel-info">
    <div class="panel-heading">
        Options </div>
    <div class="panel-body">
        <form class="form-horizontal" role="form" name="meAdminForm" id="meAdminForm" action="" method="post">

            <div class="form-group">
                <div class="col-sm-2">
                    <input type="checkbox" class="form-control" value="1" id="admin_require_packages" name="admin_require_packages" maxlength="50" <?php
                    if ($db1->f_admin_require_packages == 1) {
                        echo " checked ";
                    }
                    if ($api_version < 2) {
                        echo $disabled;
                    }
                    ?>/>
                </div>
                <label for="admin_require_packages" class="control-label" style="padding-top: 13px">Require Package details on all deliveries and pickups.</label>

            </div>
            <div class="form-group">
                <div class="col-sm-2">
                    <input type="checkbox" class="form-control" value="1" id="admin_require_items" name="admin_require_items" maxlength="50" <?php
                           if ($db1->f_admin_require_items == 1) {
                               echo "checked";
                           }
                           if ($api_version < 2) {
                               echo $disabled;
                           }
                    ?>/>
                </div>
                <label for="admin_require_items" class="control-label" style="padding-top: 13px">Require Item details on all deliveries, as well as pickups.</label>

            </div>

            <div class="form-group">
                <div class="col-sm-offset-2 col-sm-10">
                    <button type="submit" name="submit" id="submit" value=" Save " <?php echo $disabled ?> class="btn btn-success"><i class="fa fa-floppy-o"></i> Save </button>
                                 </div>
            </div>

        </form>
<?php
if ($api_version < 2) {
    ?>
            <div class="alert alert-danger" style="width:35%">
                <strong>You must upgrade to the Advanced API to use these options.</strong>
            </div>
    <?php
}
?>

    </div>
</div>
