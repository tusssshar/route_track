<?php
include_once("../../../bootstrap.php");
include(ROOTPATH . "/admin/authorized.php");
include_once(ROOTPATH . "/admin/templates/admin_header_start.php");
include_once(ROOTPATH . "/admin/templates/admin_header_end.php");

$cust_name = ($_REQUEST['cust_name']?$_REQUEST['cust_name']:'');
$delivery = ($_REQUEST['delivery']?$_REQUEST['delivery']:'');


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
                <div class="pull-left"> <a href="delivery_status.php"><span class="section-heading">Order Status</span></a> </div>

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
                            <input class="form-control" type="text" id="customer_name" name="customer_name" value="<?php if(isset($_GET['cust_name'])){    echo htmlspecialchars($_GET['cust_name']);} ?>"> 
                        </div>

                        <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 margin-b-10">
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
                    </div>
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 padding-0">
                        <div class="col-lg-3 col-md-4 col-sm-4 col-xs-12 margin-b-10">
                            <label>Delivery Status</label>
                            <select class="form-control" id="delivery_status" name="delivery_status">
                                <option value="">All</option>                
                        <?php 
                        $db_delivery=new database();
                         $query = "SELECT DISTINCT status FROM deliveries where status!=''";
                         $db_delivery->myCustomQuery($query);
                         for($i=0;$i<$db_delivery->row_count;$i++){
			 $db_delivery->moveto($i);
                         ?>
                        <option value="<?=$db_delivery->f_status?>"><?=$db_delivery->f_status?></option>
                        <?php  
                         }
                        ?>
                            </select>
                        </div>
                        <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 col-lg-offset-1 col-md-offset-1 col-sm-offset-1 margin-b-10">
                            <label>Delivery #</label>
                            <input class="form-control" type="text" name="delivery" id="delivery" value="<?php if(isset($_GET['delivery'])){    echo htmlspecialchars($_GET['delivery']);} ?>">
                        </div>
                    </div>
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 padding-0">
                        <div class="col-lg-3 col-md-4 col-sm-4 col-xs-12 margin-b-10">
                            <label>Delivery Type</label>
                            <select class="form-control" id="delivery_type" name="delivery_type">
                                <option value="">All</option>
                                <option value="Delivery">Delivery</option>
                                 <option value="Pickup">Pickup</option>
                            </select>
                        </div>

                        <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 col-lg-offset-1 col-md-offset-1 col-sm-offset-1 margin-b-10">
                            <label>Purchase Order #</label>
                            <input class="form-control" type="text" id="purchase_order" name="purchase_order">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 text-center margin-b-10">
                            <button type="submit" class="btn btn-info">Check Status</button>
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
    
    var cust_name = "<?php echo $cust_name;?>";
    var delivery = "<?php echo $delivery;?>";    
    
</script>
<script type="text/javascript" src="js/delivery_status.js"></script>
<?php
include ROOTPATH . '/admin/templates/admin_footer_end.php';
?>
