<?php
include_once("../../bootstrap.php");
$today = date("Y-m-d");
$route_id = ($_REQUEST['route_id'] ? (int) $_REQUEST['route_id'] : '');
$percent = "0";
$db_list = new database();
$qry = "select count(*) as count from deliveries "
        . "where `scheduled_delivery` = '$today' "
        . "and `status` = 'Delivered' "
        . "and `route_id` = '$route_id'";
$db_list->myCustomQuery($qry);
$done = $db_list->f_count;

$db_list_1 = new database();
$qry_1 = "select count(*) as count_1 from deliveries "
        . "where `scheduled_delivery` = '$today' "
        . "and `route_id` = '$route_id'";
$db_list_1->myCustomQuery($qry_1);
$total = $db_list_1->f_count_1;
$percent_round_off = ($total > 0) ? ($done / $total) * 100 : 0;
$percent = round($percent_round_off);

$db_list_start = new database();
$qry_start = "select `delivery_time` from deliveries "
        . "where `scheduled_delivery` = '$today' "
        . "and `status` = 'Delivered' "
        . "and `route_id` = '$route_id' "
        . "order by `delivery_time` ASC LIMIT 1";
$db_list_start->myCustomQuery($qry_start);
$first = $db_list_start->f_delivery_time;
$last = $first;

$row_count_start = $db_list_start->row_count;
if ($row_count_start > 0) {
    $db_list_last = new database();
    $qry_last = "select `delivery_time` from deliveries "
            . "where `scheduled_delivery` = '$today' "
            . "and `status` = 'Delivered' "
            . "and `route_id` = '$route_id' "
            . "order by `delivery_time` DESC LIMIT 1";
    $db_list_last->myCustomQuery($qry_last);
    $last = $db_list_last->f_delivery_time;
}
$difference = $functions->dateTimeDiff($first, $last);
//$difference = $last;
if($difference['h']==0){
    $difference['h'] = "00";
}else{
    if(strlen($difference['h'])==1){
        
        $difference['h']="0".$difference['h'];
    }
}
if($difference['i']==0){
    $difference['i'] = "00";
}else{
    if(strlen($difference['i'])==1){
        
        $difference['i']="0".$difference['i'];
    }
}

$time_1 = $difference['h'] . ":" . $difference['i'];
if ($time_1 == "0:0") {
    $time = "00:00";
} else {
    $time = $time_1;
}
$db_list_last_deli = new database();
$last_deli_qry = "select d.*, c.customer_name from "
        . "deliveries d inner join customers c on c.customer_id = d.customer_id "
        . "where d.`scheduled_delivery` = '$today' "
        . "and d.`status` = 'Delivered' "
        . "and d.`route_id` = '$route_id' "
        . "order by d.`delivery_time` DESC LIMIT 1";
$db_list_last_deli->myCustomQuery($last_deli_qry);
$delivery_time_db = $db_list_last_deli->f_delivery_time;
$delivery_time = "N/A";
if($delivery_time_db !=""){
    $delivery_time = $delivery_time_db;
}
  

$db_list_last_deli1 = new database();
$last_deli_qry1 = "select d.*, c.customer_name from "
        . "deliveries d inner join customers c on c.customer_id = d.customer_id "
        . "where d.`status` = 'Delivered' "
        . "and d.`route_id` = '$route_id' "
        . "order by d.`delivery_time` DESC LIMIT 1";
$db_list_last_deli1->myCustomQuery($last_deli_qry1);
$last_delivery_db = $db_list_last_deli1->f_delivery_time;
$last_delivery = "N/A";
if($last_delivery_db !=""){
    $last_delivery = $last_delivery_db;
}
?>
<script>
//	search code
    var ajaxFilePath = "ajax/home_route_status.php";
    $(document).ready(function () {

        $("#form").submit(function () {
            loadingIndicator('start');
            $.post(ajaxFilePath, $(this).serializeArray(), function (data) {
                $("#MyAjaxInterView").html(data);
                loadingIndicator('end');
            }, "html");
            return false;
        });//search submit
    });//document ready

</script>
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
        <div class="col-lg-8 col-md-8 col-sm-8 col-xs-12">
            <label class="">Completion Status</label>
            <div class="progress">
                <div class="progress-bar" role="progressbar" aria-valuenow="70" aria-valuemin="0" aria-valuemax="100" style="width:<?php echo $percent . "%"; ?>">
                    <span class="sr-only">70% Complete</span>
                </div> 
            </div>
        </div>
        <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
            <p style="margin-top:10px;font-size:24px;margin-bottom:0px;font-weight:bold;color:#3c763d;"><?php echo $percent . "%"; ?></p> <p class="text-success margin-b-0">Complete</p>
        </div>
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
            <p class="completion-time"><?= $done ?> of <?= $total ?> stops  <b><?= $time ?>  </b>Total Time</p>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
            <label>Last Delivery/Pickup</label>
            <p><?= $last_delivery;?></p>
        </div>
        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12 text-right">
            <label>Time Delivered/Pickup</label>
            <p><?= $delivery_time ?></p>
            <!--<a href="#">more »</a>-->
        </div>
    </div>
</div>
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
            <label>Remaining Deliveries/Pickup</label>
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th class="text-center">Customer</th>
                        <th>Driver</th>
                        <th>Stop #</th>
                        <th>Order #</th> 
                        <th>PO #</th> 
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $db_rem_deli = new database();
                    $query = "select d.*, c.customer_name,CONCAT_WS(' ', u.first_name, u.last_name) as driver_name from "
                            . "deliveries d inner join customers c on c.customer_id = d.customer_id "
                            . "inner join users u on u.user_id = d.driver_id "
                            . "where d.`scheduled_delivery` = '$today' "
                            . "and d.`status` = 'In Transit' "
                            . "and d.`route_id` = '$route_id' "
                            . "order by c.customer_name, d.stop_number";

                    $db_rem_deli->myCustomQuery($query);
                    if ($db_rem_deli->row_count > 0) {
                        for ($i = 0; $i < $db_rem_deli->row_count; $i++) {
                            $db_rem_deli->moveto($i);
                            ?>
                            <tr>
                                <td class="text-center"><?= ucfirst($db_rem_deli->f_customer_name) ?></td>
                                <td><?= ucfirst($db_rem_deli->f_driver_name) ?></td>
                                <td><?= ucfirst($db_rem_deli->f_stop_number) ?></td>
                                <td><?= ucfirst($db_rem_deli->f_order_number) ?></td>
                                <td><?= ucfirst($db_rem_deli->f_cust_po_num) ?></td>

                            </tr>
                            <?php
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

</div>
