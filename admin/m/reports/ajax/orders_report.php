<?php
include_once("../../../../bootstrap.php");

// report filters are concatenated into SQL: escape them once
foreach ($_REQUEST as $req_key => $req_value) {
    if (is_string($req_value)) {
        $_REQUEST[$req_key] = sanitize($req_value);
    }
}

$objGlobalConnection = new dbSqlConnection();
$oGlobalConnection = $objGlobalConnection->Connect(HST, USR, PWD, DBN);


//$txtSearch = ($_REQUEST['txtSearch'] ? $_REQUEST['txtSearch'] : '');
$date_range_from = ($_REQUEST['date_range_from'] ? $_REQUEST['date_range_from'] : '');
$date_range_to = ($_REQUEST['date_range_to'] ? $_REQUEST['date_range_to'] : '');
$customer_name = ($_REQUEST['customer_name'] ? $_REQUEST['customer_name'] : '');
$route_id = ($_REQUEST['route_id'] ? $_REQUEST['route_id'] : '');
$delivery_status = ($_REQUEST['delivery_status'] ? $_REQUEST['delivery_status'] : '');
$delivery = ($_REQUEST['delivery'] ? $_REQUEST['delivery'] : '');
$delivery_type = ($_REQUEST['delivery_type'] ? $_REQUEST['delivery_type'] : '');
$order_number = ($_REQUEST['order_number'] ? $_REQUEST['order_number'] : '');
$driver_id = ($_REQUEST['driver_id'] ? $_REQUEST['driver_id'] : '');



$cust_name = ($_REQUEST['cust_name'] ? $_REQUEST['cust_name'] : '');
$delivery_quick = ($_REQUEST['delivery'] ? $_REQUEST['delivery'] : '');


//pageing coading
$PAGE = ((empty($_GET['page'])) ? 1 : max(1, (int) $_GET['page']));
$PAGE_LIMIT = 25;
$PAGE_LIMIT_VALUE = ($PAGE - 1) * $PAGE_LIMIT;

//if (strlen(trim($txtSearch)) > 0) {
//    $search_query = " and (access_levels.name like '%" . $txtSearch . "%')";
//}

$date_query = "";
$customers_query = "";
$route_query = "";
$delivery_status_query = "";
$delivery_query = "";
$delivery_type_query = "";
$purchase_order_query = "";
$cust_name_query = "";
$delivery_quick_query = "";
$db_list = new database();
if (strlen(trim($date_range_from)) > 0 && strlen(trim($date_range_to)) > 0) {
    $date_query = " and scheduled_delivery between '" . $date_range_from . "' and '" . $date_range_to . "'";
}

if (strlen(trim($customer_name)) > 0) {
    $customers_query = " and (cust.customer_name like '%" . $customer_name . "%')";
}

if (strlen(trim($route_id)) > 0) {
    $route_query = " and (rout.name like '%" . $route_id . "%')";
}

if (strlen(trim($delivery_status)) > 0) {

    if ($delivery_status == "Unscheduled") {
        $delivery_status_query = " and (d.scheduled_delivery  =	 '0000-00-00')";
    } else {
        $delivery_status_query = " and (d.status like '%" . $delivery_status . "%')";
    }
}
if (strlen(trim($delivery)) > 0) {
    $delivery_query = " and (d.stop_number = '" . $delivery . "')";
}
if (strlen(trim($delivery_type)) > 0) {
    $delivery_type_query = " and (mani.manifest_type like '%" . $delivery_type . "%')";
}
if (strlen(trim($order_number)) > 0) {
    $order_number_query = " and (d.order_number = '" . $order_number . "')";
}

if (strlen(trim($cust_name)) > 0) {
    $cust_name_query = " and (cust.customer_name like '%" . $cust_name . "%')";
}

if (strlen(trim($delivery_quick)) > 0) {
    $delivery_quick_query = " and (d.stop_number = '" . $delivery_quick . "')";
}

if (strlen(trim($driver_id)) > 0) {
    $delivery_driver_query = " and (d.driver_id = '" . $driver_id . "')";
}



$query = "select SQL_CALC_FOUND_ROWS d.*,mani.manifest_type,mani.manifest_id,cust.customer_name,cust.account_number,rout.name as rname,CONCAT(U.first_name,' ',U.last_name) as DRIVER_NAME from 
			deliveries d
                        LEFT JOIN manifests mani on d.manifest_id=mani.manifest_id
                        LEFT JOIN customers cust on d.customer_id=cust.customer_id
                        LEFT JOIN routes rout on d.route_id=rout.route_id
                        LEFT JOIN users U on d.driver_id=U.user_id
			 where 1=1  " . $date_query . " 
                         " . $customers_query . " 
                         " . $route_query . "
                         " . $delivery_status_query . " 
                         " . $delivery_query . "   
                         " . $delivery_type_query . " 
                         " . $order_number_query . "  
                         " . $cust_name_query . "    
                         " . $delivery_quick_query . "    
                         " . $delivery_driver_query . "  
			  ORDER BY d.delivery_id DESC
			 LIMIT $PAGE_LIMIT_VALUE, $PAGE_LIMIT ";

//vjgm_packages.pack_is_deleted=0

$db_list->myCustomQuery($query);

$PAGE_TOTAL_ROWS = $db_list->FOUND_ROWS();

$PAGE_URL = basename($_SERVER['PHP_SELF']) . "-txtSearch=" . $txtSearch;

$PAGING = $functions->showAjaxPaging("MyAjaxInterView");
?>

<div class="alert alert-success" id="successMsg" style="margin:10px;display:none">Success! Well done its submitted.</div>
<div class="row">
    <div class="col-md-12" style="padding:0 20px">
        <div class="pull-left">

        </div>
        <div class="pull-right">
            <?= $PAGING ?>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th class="text-center">Type</th>
                    <th class="text-center">Order ID</th>
                    <th>Customer Name</th>
                    <th>Route</th>
                    <th>Driver</th>
                    <th>Schedule Date</th>
                    <th>Delivered Date & Time</th>
                    <th>Status</th>
                    <th>Added Date & Time</th>
                    <!--<th class="text-center">Action</th>-->
                </tr>
            </thead>
            <tbody>
                <?php
                $db_list1 = new database();
                $counter = 1;
                if ($db_list->row_count > 0) {
                    $counter = ($PAGE_LIMIT_VALUE + 1);
                    for ($i = 0; $i < $db_list->row_count; $i++) {
                        $db_list->moveto($i);
                        ?>
                        <tr id="tr_<?= $db_list->f_level_id ?>" <?php if ($db_list->f_status == "In Transit" ||$db_list->f_status == "Exception" ) { ?>onclick="javascript:ajaxNewModify(<?= ucfirst($db_list->f_manifest_id) ?>,<?= $db_list->f_delivery_id ?>)" style="cursor: pointer" <?php } ?>>
                            <td class="text-center"><?= ucfirst($db_list->f_manifest_type) ?> </td>
                            <td class="text-center"><?= ucfirst($db_list->f_order_number) ?></td>
                            <td><?= ucfirst($db_list->f_customer_name) ?></td>
                            <td><?= ucfirst($db_list->f_rname) ?></td>
                            <td><?= ucfirst($db_list->f_DRIVER_NAME) ?></td>
                            <td><?php
                                if ($db_list->f_scheduled_delivery != "0000-00-00") {
                                    echo date('m/d/y', strtotime($db_list->f_scheduled_delivery));
                                } else {
                                    echo "-";
                                }
                                ?></td>
                            <td><?php if ($db_list->f_delivery_time!= "0000-00-00 00:00:00") {
                                    echo date("m/d/y g:i A", strtotime($db_list->f_delivery_time));
                                } else {
                                    echo "-";
                                }?></td>
                            <td><?= ucfirst($db_list->f_status) ?></td>
                            <td><?php
                                if ($db_list->f_added_date != "0000-00-00 00:00:00") {
                                    echo date("m/d/y g:i A", strtotime($db_list->f_added_date));
                                } else {
                                    echo "-";
                                }
                                ?></td>
        <!--                            <td>
                            <?php
//                                if ($db_list->f_level_id != 5) {
                            ?>
                                    <a href="javascript:void(0);" onclick="javascript:ajaxNewModify(<?//= $db_list->f_level_id; ?>);" title="Edit Access Level"><i class="fa fa-pencil-square-o fa-size"></i></a> &nbsp;&nbsp;
                            <?php
//                                }
                            ?>
                            </td>-->
                        </tr>
                        <?php
                        $counter++;
                    }
                } else {
                    ?>
                    <tr>
                        <td align='center' colspan="100%" style="height:40px;">No record found</td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
    </div>
</div>
<div class="row">
    <div class="col-md-12" style="padding:0 20px">
        <div class="pull-right">
            <?= $PAGING ?>
        </div>
    </div>
</div>
