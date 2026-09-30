<?php
include_once("../../../bootstrap.php");
include(ROOTPATH . "/admin/authorized.php");
include_once(ROOTPATH . "/admin/templates/admin_header_start.php");
include_once(ROOTPATH . "/admin/templates/admin_header_end.php");

$cust_name = ($_REQUEST['cust_name'] ? $_REQUEST['cust_name'] : '');
$delivery = ($_REQUEST['delivery'] ? $_REQUEST['delivery'] : '');


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
                <div class="pull-left"> <a href="orders_report.php"><span class="section-heading">Update Orders</span></a> </div>

            </div>
        </h4>
    </div>
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-info">
                <div class="panel-heading margin-b-10"><label style="padding-top:8px">Report Setting</label></div>
                <form name="form" id="form">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 padding-0">
                        <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 margin-b-10">

                            <label>Date Range</label>

                            <div class="row">
                                <div class="col-lg-5">
                                    <input class="form-control" type="text" name="date_range_from" id="date_range_from">
                                </div>
                                <div class="col-lg-2 col-md-1 col-sm-1 col-xs-12 text-center">
                                    <label>To</label>
                                </div>

                                <div class="col-lg-5">
                                    <input class="form-control" type="text" id="date_range_to" name="date_range_to">
                                </div>
                            </div>

                        </div>
                        <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 margin-b-10">
                            <label>Customer Name</label>
                            <input class="form-control" type="text" id="customer_name" name="customer_name" value="<?php
                            if (isset($_GET['cust_name'])) {
                                echo htmlspecialchars($_GET['cust_name']);
                            }
                            ?>"> 
                        </div>

                        <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 margin-b-10">

                            <label>Order No. #</label>
                            <input class="form-control" type="text" name="order_number" id="order_number" value="<?php
                            if (isset($_GET['order_number'])) {
                                echo htmlspecialchars($_GET['order_number']);
                            }
                            ?>">



                        </div>
                    </div>
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 padding-0">
                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                            <label>Delivery Status</label>
                            <select class="form-control" id="delivery_status" name="delivery_status">
                                <option value="">All</option>                

                                <option value="In Transit">In Transit</option>
                                <option value="Delivered">Delivered</option>
                                <option value="Unscheduled">Unscheduled Orders </option>
                                <option value="Exception">Exception </option>
                                <option value="Refused">Refused </option>

                            </select>
                        </div>
                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                            <label>Route</label>

                            <select class="form-control" name="route_id" id="route_id">
                                <option value="">All</option>
                                <?php
                                $db_route = new database();
                                $query_route = "SELECT DISTINCT name FROM routes";
                                $db_route->myCustomQuery($query_route);
                                for ($i = 0; $i < $db_route->row_count; $i++) {
                                    $db_route->moveto($i);
                                    ?>
                                    <option value="<?= $db_route->f_name ?>"><?= $db_route->f_name ?></option>
                                    <?php
                                }
                                ?>                          
                            </select>

                        </div>

                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12 margin-b-10">
                            <label>Delivery Type</label>
                            <select class="form-control" id="delivery_type" name="delivery_type">
                                <option value="">All</option>
                                <option value="Delivery">Delivery</option>
                                <option value="Pickup">Pickup</option>
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                            <label>Driver</label>
                            <select class="form-control" name="driver_id" id="driver_id">
                                <option value="">All</option>
                                <?php
                                $db_driver = new database();
                                $query_driver = "SELECT CONCAT(first_name,' ',last_name) as DRIVER_NAME,user_id  FROM users WHERE is_driver=1";
                                $db_driver->myCustomQuery($query_driver);
                                for ($i = 0; $i < $db_driver->row_count; $i++) {
                                    $db_driver->moveto($i);
                                    ?>
                                    <option value="<?= $db_driver->f_user_id ?>"><?= ucfirst($db_driver->f_DRIVER_NAME) ?></option>
                                    <?php
                                }
                                ?>                          
                            </select>
                        </div>

                    </div>
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 padding-0">



                    </div>
                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 text-center margin-b-10">
                            <button type="submit" class="btn btn-info">Generate</button>
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

    });

    var cust_name = "<?php echo $cust_name; ?>";
    var delivery = "<?php echo $delivery; ?>";

</script>
<script type="text/javascript" src="js/orders_report.js"></script>
<?php
include ROOTPATH . '/admin/templates/admin_footer_end.php';
?>
