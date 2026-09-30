<?php
include_once("../../../bootstrap.php");
include(ROOTPATH . "/admin/authorized.php");
include_once(ROOTPATH . "/admin/templates/admin_header_start.php");
include_once(ROOTPATH . "/admin/templates/admin_header_end.php");

//Check Access Level
$res = $functions->user_access_level(9, $_SESSION["routetrac-admin"]["access_level"]);
if ($res == 0) {
    echo "<script>window.location.assign('../../../index.php')</script>";
}

$objGlobalConnection1 = new dbSqlConnection();
$oGlobalConnection = $objGlobalConnection1->Connect(HST, USR, PWD, DBN);
?>

<div class="container-fluid detail-page">
    <div class="page-header">
        <h4>
            <div class="clearfix">
                <div class="pull-left"> <a href="route_status.php"><span class="section-heading">Route Status</span></a> </div>

            </div>
        </h4>
    </div>
    <div class="row">
        <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
            <div class="panel panel-info">
                <div class="panel-heading margin-b-10"><label style="padding-top:8px">Report Summary</label></div>
                <form name="form" id="form">
                    <div class="row">
                        <div id="MyAjaxInterView_route_status">&nbsp;</div>
                    </div>
                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 text-center margin-b-10">
                            <button type="button" class="btn btn-info" onclick="show_route_status();">Refresh</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-md-12" id="divList">
            <div class="clearfix">







                <!--        <div class="row">
                          <div class="col-md-12">
                            <form class="form-inline" role="form" autocomplete="off" enctype="multipart/form-data" method="post"  name="form" id="form">
                              <div class="form-group">
                                <label class="sr-only" for="name">Name</label>
                                <input type="text" name="txtSearch" id="txtSearch" class="form-control" />
                              </div>
                              <input name="search" type="submit" id="search" value="  Search " class="btn btn-default" />
                              <div class="form-group"> <a href="javascript:void(0);" onclick="javascript:clearSearchValues();">Clear Search</a> </div>
                            </form>
                          </div>
                        </div>-->



                <div class="row">
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
include ROOTPATH . '/admin/templates/admin_footer_start.php';
?>
<!--jquery.blockUI-->
<script type="text/javascript" src="<?php echo SITEROOT; ?>/admin/templates/js/jquery/blockUI/jquery.blockUI.js"></script>
<script>
    $(function () {

        $('#date_range_from').datepicker({
            format: 'yyyy-mm-dd',
            "setDate": new Date(),
            "autoclose": true
        });
        $('#date_range_to').datepicker({
            format: 'yyyy-mm-dd',
            "setDate": new Date(),
            "autoclose": true
        });
        show_route_status();
    });
    
    function show_route_status() {
    loadingIndicator('start');
    $.get("ajax/reports_route_status.php", function (data) {
        $("#MyAjaxInterView_route_status").html(data);
        loadingIndicator('end');
    }, "html");
}
</script>
<script type="text/javascript" src="js/route_status.js"></script>
<?php
include ROOTPATH . '/admin/templates/admin_footer_end.php';
?>
