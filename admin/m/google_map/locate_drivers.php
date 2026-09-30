<?php
include_once("../../../bootstrap.php");
include(ROOTPATH . "/admin/authorized.php");
//include_once(ROOTPATH . "/admin/templates/admin_header_start.php");
include_once(ROOTPATH . "/admin/templates/admin_header_end.php");
$GOOGLE_DeveloperKey = GOOGLE_MAPS_JS_KEY;
$CURDATE = date("Y-m-d");
//Check Access Level
$res = $functions->user_access_level(1, $_SESSION["routetrac-admin"]["access_level"]);

if ($res == 0) {

    echo "<script>window.location.assign('../../../index.php')</script>";
}

$objGlobalConnection1 = new dbSqlConnection();
$oGlobalConnection = $objGlobalConnection1->Connect(HST, USR, PWD, DBN);
if (isset($_REQUEST["submit1"])) {

    //echo "<script>location.href='area.php';</script>";
}
?>
<link rel="shortcut icon" type="image/x-icon" href="<?php echo SITEROOT; ?>/admin/templates/images/favicon.ico" />
<link rel="stylesheet" href="<?php echo SITEROOT; ?>/admin/templates/css/bootstrap.min.css">
<link href="<?= SITEROOT ?>/admin/templates/js/font-awesome-4.6.3/css/font-awesome.min.css" rel="stylesheet">
<link href="<?php echo SITEROOT; ?>/admin/templates/css/main.css" rel="stylesheet">
<link href="<?php echo SITEROOT; ?>/admin/templates/css/datetimepicker.css" rel="stylesheet">
<script>var SITEROOT = '<?php echo SITEROOT; ?>';</script>
<span class="error-msg" style="display:block;"><?php echo $_SESSION["routetrac-admin"]["error_success_msg"]; ?></span>
<script>var SITEROOT = '<?php echo SITEROOT; ?>';</script>
<?php unset($_SESSION["routetrac-admin"]["error_success_msg"]); ?>
<?php
include ROOTPATH . '/admin/templates/admin_footer_start.php';
?>
<!--  <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo $GOOGLE_DeveloperKey; ?>&sensor=false"></script>-->
<script src="https://maps.googleapis.com/maps/api/js?key=<?php echo $GOOGLE_DeveloperKey; ?>&sensor=false"></script>


<div class="container-fluid detail-page">
    <div class="page-header">
        <h4>
            <div class="clearfix">
                <div class="pull-left"> <a href="area.php"><span class="section-heading">Google Map</span></a> </div>

            </div>
        </h4>
    </div>
    <div class="row">
        <div class="col-md-12" id="divList">
            <div class="clearfix">
                <form name="form" id="form">
                    <table width="100%" border="0">
                        <tr style="height: 55px">
                            <td>
                                <select id="driver_id" name="driver_id"  style="width: 70%" class="form-control" onchange="show_data(this.value)">
                                    <optgroup label="All">
                                        <option  value="All_Drivers" <?php
if ($driver_id == "All_Drivers") {
    echo "selected";
}
?>>All Drivers</option>
                                        <option  value="All_Customer" <?php
                                                 if ($driver_id == "All_Customer") {
                                                     echo "selected";
                                                 }
                                                 ?>>All Customer</option>
                                        <option  value="All_Drivers_And_Customer" <?php
                                                 if ($driver_id == "All_Drivers_And_Customer") {
                                                     echo "selected";
                                                 }
                                                 ?>>All Drivers And Customer</option>
                                    </optgroup>
                                    <optgroup label="Particular Driver">
<?php
$db_get_driver = new database();
$query_get_driver = "SELECT D.delivery_id,D.order_number,U.first_name,U.last_name,U.user_id,U.latitude,U.longitude FROM deliveries D  LEFT JOIN users U ON  D.driver_id = U.user_id WHERE D.status='In Transit' AND  scheduled_delivery='".$CURDATE."' GROUP BY U.user_id";
$db_get_driver->myCustomQuery($query_get_driver);
if ($db_get_driver->row_count > 0) {
    for ($i = 0; $i < $db_get_driver->row_count; $i++) {
        $db_get_driver->moveto($i);
        $latitude = $db_get_driver->f_latitude;
        $longitude = $db_get_driver->f_longitude;
        $name = ucfirst($db_get_driver->f_first_name) . " " . ucfirst($db_get_driver->f_last_name);
        $id = $db_get_driver->f_user_id;
        ?>

                                                <option value="<?= $id ?>" <?php
                                        if ($driver_id == $id) {
                                            echo "selected";
                                        }
        ?>><?= $name ?></option>
                                                <?php
                                                    }
                                                }
                                                ?>
                                    </optgroup>

                                </select>
                            </td>
                            <td>
                               
                                Start Date : <input class="" type="text" name="date_range_from" id="date_range_from" placeholder="Start Date" style="width: 20%" disabled="">    End Date :  <input class="" type="text" id="date_range_to" name="date_range_to" placeholder="End Date" style="width: 20%" disabled="">
                             
                            </td>
                           
                        </tr>
                      
                        <tr style="height: 55px">
                            <td coslpan="100%">
                           
                       
                            <button type="submit" class="btn btn-info">Generate</button>
                      
                            </td>
                        </tr>
                    </table>
                </form>

                <div class="row" id="row_list">
                    <div class="col-md-12">
                        <div id="MyAjaxInterView">


                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

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
</script>
<script type="text/javascript" src="js/locate_drivers.js"></script>
<?php
include ROOTPATH . '/admin/templates/admin_footer_end.php';
?>
