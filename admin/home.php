<?php
include("../bootstrap.php");
include(ROOTPATH . "/admin/authorized.php");

include(ROOTPATH . '/admin/templates/admin_header_start.php');
include(ROOTPATH . '/admin/templates/admin_header_end.php');
$objGlobalConnection1 = new dbSqlConnection();
$oGlobalConnection = $objGlobalConnection1->Connect(HST, USR, PWD, DBN);
?>

<div class="container-fluid">
    <!-- Main component for a primary marketing message or call to action -->
    <!--<div class="jumbotron">
      <h1>Hello,</h1>
      <p>Welcome to Routetrac admin panel.</p>
      <br />
    </div>-->
    <div class="row">
        <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <div class="panel panel-info">
                    <div class="panel-heading margin-b-10"><label style="padding-top:8px">Quick Delivery Lookup</label></div>
                    <form name="form" id="quickydelilookup" id="quickydelilookup" method="GET" action="<?php echo SITEROOT . '/admin/m/reports/delivery_status.php' ?>">
                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 padding-0">
                            <div class="col-lg-5 col-md-4 col-sm-4 col-xs-12 margin-b-10">
                                <label>Customer Name</label>
                                <input class="form-control" name="cust_name" id="cust_name" type="text" onkeyup="disable_delivery();">                               
                            </div>
                            <div class="col-lg-2 col-md-4 col-sm-4 col-xs-12 margin-b-10 text-center">
                                <br>
                                <label class="text-danger lead">OR</label> 
                            </div>
                            <div class="col-lg-5 col-md-4 col-sm-4 col-xs-12 margin-b-10">
                                <label>Delivery # </label>
                                <input class="form-control" name="delivery" id="delivery" type="text" onkeyup="disable_customer_name()">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 text-center margin-b-10">
                                <button type="submit" name="submit_search" id="submit_search" class="btn btn-info">Search</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <div class="panel panel-info">
                    <div class="panel-heading margin-b-10">
                        <form name="form" id="form">
                            <div class="row"> 
                                <label class="col-lg-6 col-md-5 col-sm-5 col-xs-12" style="padding-top:8px">Recent Deliveries / Pickups - All Routes</label>							
                                <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12 text-right pull-right"  style="padding-top:8px">
                                    <label>Show Last :</label>
                                    <select id="limit" name="limit" onchange="show_route()">
                                        <option value="10">10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                    </select>                                
                                </div>
                                <div class="col-lg-3 col-md-4 col-sm-4 col-xs-12 pull-right"  style="padding-top:8px">
                                    <select id="deliver_type" name="deliver_type"  onchange="show_route()">
                                        <option value="">Select Delivery Type</option>
                                        <option value="Delivery">Deliveries</option>
                                        <option value="Pickup">Pickups</option>
                                        <option value="Both">Both</option>
                                    </select>
                                </div>
                            </div>
                        </form>
                    </div>                    
                    <div class="clearfix">
                        <div class="col-md-12" id="divList">
                            <div class="clearfix">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div id="MyAjaxInterView">&nbsp;</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-0" id="divForm" style="display:none"></div>
            </div>
        </div>
        <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <div class="panel panel-info">
                    <div class="panel-heading margin-b-10"><label style="padding-top:8px">Route Status</label>
                    </div>
                    
                    <form name="form" id="" class="route-status-form">
                        <div class="row">
                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 margin-b-10">
                                    <label>Route</label>
                                    <select class="form-control" name="route_id" id="route_id" onchange="show_route_status()">
                                        <option value="">Select one route</option>
                                        <?php
                                        $db_route = new database();
                                        $query_route = "SELECT DISTINCT name,route_id FROM routes";
                                        $db_route->myCustomQuery($query_route);
                                        for ($i = 0; $i < $db_route->row_count; $i++) {
                                            $db_route->moveto($i);
                                            ?>
                                            <option value="<?= $db_route->f_route_id ?>"><?= $db_route->f_name ?></option>
                                            <?php
                                        }
                                        ?>                          
                                    </select>
                                </div>
                            </div>
                        </div>
                         <div class="clearfix">
                        <div class="col-md-12" id="divList">
                            <div class="clearfix">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div id="MyAjaxInterView_route_status">&nbsp;</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    </form>

                </div>
            </div>
        </div></div>
</div>
<?php
include (ROOTPATH . '/admin/templates/admin_footer_start.php');
?>
<script type="text/javascript" src="templates/js/home.js"></script>
<?php
include (ROOTPATH . '/admin/templates/admin_footer_end.php');
?>
