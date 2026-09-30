<?php
session_start();
include_once("../../../bootstrap.php");
include(ROOTPATH . "/admin/authorized.php");
include_once(ROOTPATH . "/admin/templates/admin_header_start.php");
include_once(ROOTPATH . "/admin/templates/admin_header_end.php");

//Check Access Level
$res = $functions->user_access_level(7, $_SESSION["routetrac-admin"]["access_level"]);
if ($res == 0) {
    echo "<script>window.location.assign('../../../index.php')</script>";
}
$objGlobalConnection1 = new dbSqlConnection();
$oGlobalConnection = $objGlobalConnection1->Connect(HST, USR, PWD, DBN);
include_once(ROOTPATH . "/admin/m/master/includes/manifest_functions.php");
if (isset($_REQUEST["submit1"])) {
    $imported_manifest_id = (int) handle_manifest_import_post();
    echo "<script>location.href='manifests.php?manifest_id_from_report=" . $imported_manifest_id . "&delivery_id_from_report=0';</script>";
    exit;
}
?>
<div class="container-fluid detail-page">
    <div class="page-header">
        <h4>
            <div class="clearfix">
                <div class="pull-left"> <a href="manifests.php"><span class="section-heading">Manifest(s)</span></a> </div>
                <div class="pull-right"> <small> <a href="<?php echo SITEROOT . '/admin/m/master/create_manifest.php' ?>"  class="" title="Add New Menifest"><i class="fa fa-plus"></i>&nbsp;&nbsp;New Manifest</a></small> </div>
                <!--  <div class="pull-right"> <small> <a href="ajax/manifests_new.php" class="" title="Add New Menifest"><i class="fa fa-plus"></i>&nbsp;&nbsp;New Menifest</a> </small> </div>-->
            </div>
        </h4>
    </div>
    <div class="row">
        <div class="col-md-12" id="divList">
            <div class="clearfix">
                <div class="row">
                    <div class="col-md-12">


                        <!-- <form class="form-inline" role="form" autocomplete="off" enctype="multipart/form-data" method="post"  name="form" id="form">
                           <div class="form-group">
                             <label class="sr-only" for="name">Name</label>
                             <input type="text" name="txtSearch" id="txtSearch" class="form-control" />
                           </div>
                           <input name="search" type="submit" id="search" value="  Search " class="btn btn-default" />
                           <div class="form-group"> <a href="javascript:void(0);" onclick="javascript:clearSearchValues();">Clear Search</a> </div>
                         </form>-->


                        <div class="row" id="import_id" style="display: none">
                            <div class="col-md-3"></div>
                            <div class="col-md-6">
                                <form action="" id="import_form"  class="form-inline" role="form" enctype="multipart/form-data" method="post"  name="import_form">
                                    
<?php manifest_import_form_fields(); ?>
<input type="file" class="form-control" name="import_file_name" id="import_file_name" style="padding: 0;width: 35%"/>
                                    <button type="submit" name="submit1" id="submit1" value=" Save" class="btn btn-success"><i class="fa fa-floppy-o"></i> Import </button>
                                                    <span style="color:#999999">or</span>&nbsp; <span class="cancellink"><a href="javascript:void(0);" onClick="javascript:import_cancel();">Cancel</a></span> <br>
                                    <br>
                                    <i class="fa fa-download"></i>&nbsp;&nbsp;<a href="<?php echo SITEROOT . '/admin/m/master/templates/MANIFEST.csv'; ?>" class="" download>Download Template</a>
                                </form>
                            </div>
                            <div class="col-md-3"></div>
                        </div>

                        <br>
                    </div>
                </div>
                <div class="row" id="row_list">
                    <div class="col-md-12">
                        <div id="MyAjaxInterView">&nbsp;</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-0" id="divForm" style="display:none"></div>
    </div>
</div>


<?php
if ($_REQUEST['manifest_id_from_report'] != "" && isset($_REQUEST['manifest_id_from_report'])) {
    $manifest_id_from_report = (int) $_REQUEST['manifest_id_from_report'];
    $delivery_id_from_report = (int) $_REQUEST['delivery_id_from_report'];
    ?>
    <script>
        var manifest_id_from_report = "<?php echo $manifest_id_from_report ?>";
        var delivery_id_from_report = "<?php echo $delivery_id_from_report ?>";
        
    </script>
    <?php
} else {
    ?>
    <script>
        var manifest_id_from_report = 0;
        var delivery_id_from_report = 0;
    </script>
    <?php
    }
    include ROOTPATH . '/admin/templates/admin_footer_start.php';
    ?>
    <!--jquery.blockUI-->
    <script type="text/javascript" src="<?php echo SITEROOT; ?>/admin/templates/js/jquery/blockUI/jquery.blockUI.js"></script>
    <script type="text/javascript" src="js/manifests.js"></script>
    <?php
    include ROOTPATH . '/admin/templates/admin_footer_end.php';
    ?>
<script>

        function openitem_popup(delivery_id) {

            loadingIndicator('start');

            $.ajax({
                url: SITEROOT + "/admin/ajax/admin_ajax_action.php?action=get_item_details",
                method: "POST",
                data: {delivery_id: delivery_id},
                dataType: "html"
            }).done(function (data) {
                $("#set_items").html(data);
                // $("#set_items").show();
                alert(data);
                loadingIndicator('end');
            }).fail(function (jqXHR, textStatus) {
                alert("Request failed: " + textStatus);
            });
        }
</script>



