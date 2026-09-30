<?php
include_once("../../../../bootstrap.php");

$objGlobalConnection = new dbSqlConnection();
$oGlobalConnection = $objGlobalConnection->Connect(HST, USR, PWD, DBN);

//pageing coading
$PAGE = ((empty($_GET['page'])) ? 1 : max(1, (int) $_GET['page']));
$PAGE_LIMIT = 25;
$PAGE_LIMIT_VALUE = ($PAGE - 1) * $PAGE_LIMIT;

$db_list = new database();


$query = "select d.delivery_id,d.route_id, r.`name` as route_name,
 
     (select `delivery_time` from deliveries x
         where x.route_id = d.route_id and `status` = 'Delivered'
         order by `delivery_time` ASC LIMIT 1) as start_time,
 
     (select `delivery_time` from deliveries x
         where x.route_id = d.route_id and `status` = 'Delivered'
         order by `delivery_time` DESC LIMIT 1) as last_time,
 
     (select `delivery_id` from deliveries x
         where x.route_id = d.route_id and `status` = 'Delivered'
         order by `delivery_time` DESC LIMIT 1) as last_deliv,
 
     (select count(*) from deliveries x
         where x.route_id = d.route_id) as total_route,
 
     (select count(*) from deliveries x
         where x.route_id = d.route_id
         and `status` = 'Delivered') as done_route
 
from deliveries d
    inner join routes r on r.route_id = d.route_id
group by d.route_id

			 LIMIT $PAGE_LIMIT_VALUE, $PAGE_LIMIT";

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
                    <th class="text-center">Route</th>
                    <th class="text-center">Total Orders</th>
                    <th>Percent</th>
                    <th>Last Delivery</th>
                    <th>Delivery Time</th>
                    <th>Route Time</th>
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

                        $datetime1 = new DateTime($db_list->f_start_time);
                        $datetime2 = new DateTime($db_list->f_last_time);
                        $interval = $datetime1->diff($datetime2);

                        $hour = $interval->format('%h');
                        $minut = $interval->format('%i');

                        if ($hour == 0) {
                            $hour = "00";
                        } else {
                            if (strlen($hour) == 1) {
                                $hour = "0" . $hour;
                            }
                        }
                        if ($minut == 0) {
                            $minut = "00";
                        } else {
                            if (strlen($minut) == 1) {
                                $minut = "0" . $minut;
                            }
                        }

                        $time = $hour . ":" . $minut;
                        ?>
                        <tr id="tr_<?= $db_list->f_level_id ?>">
                            <td class="text-center"><?= ucfirst($db_list->f_route_name) ?> </td>
                            <td class="text-center"><?= $db_list->f_total_route ?></td>

                            <td><?= number_format((($db_list->f_done_route / $db_list->f_total_route) * 100), 2, '.', '') ?></td>
                            <td><!--<a href="ajax/route_status_delivery_details.php?delivery_id=<?= $db_list->f_last_deliv ?>" data-toggle="modal" data-target="#myModal"><?= $db_list->f_last_deliv ?></a>-->
                                <a href="javascript:void(0)" type="button" class="openBtn" onclick="getModel(<?=$db_list->f_last_deliv?>)"><?= $db_list->f_last_deliv ?></a>
                            </td>
                            <td><?= date("m/d/y g:i A", strtotime($db_list->f_last_time));?> </td>
                            <td><?= $time ?></td>
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


  <a href="javascript:void(0)" type="button" class="openBtn" onclick="getModel(<?=$db_list->f_last_deliv?>)"><?= $db_list->f_last_deliv ?></a>
<!-- Modal -->
<div class="modal fade" id="myModal" role="dialog">
    <div class="modal-dialog" style="width: 1200px;">

        <!-- Modal content-->
        <div class="modal-content">

        </div>

    </div>
</div>

<script>
function getModel(delivery_id){
    $('.modal-content').load('ajax/route_status_delivery_details.php?delivery_id='+delivery_id,function(){
    $('#myModal').modal({show:true});
    });
}
</script>