<?php
include_once("../../../../bootstrap.php");
$db_delivered_status = new database();
$get_delivered_status="select count(*) as done
from deliveries
where `status` = 'Delivered'
";
$db_delivered_status->myCustomQuery($get_delivered_status);
$done = $db_delivered_status->f_done;

$db_all_delivery = new database();
$get_all_delivery="select count(*) as total
from deliveries

";
$db_all_delivery->myCustomQuery($get_all_delivery);
$total = $db_all_delivery->f_total;

$percent = ($done/$total) * 100;
//echo $done."##".$total."##".$percent;

$db_get_start_time=new database();
$get_start_time_query="select `delivery_time` as start
from deliveries
where `status` = 'Delivered'
order by `delivery_time` ASC
LIMIT 1
";
$db_get_start_time->myCustomQuery($get_start_time_query);
$start = $db_get_start_time->f_start;


$db_get_last_time=new database();
$get_last_time_query="select `delivery_time` as last
from deliveries
where `status` = 'Delivered'
order by `delivery_time` DESC
LIMIT 1
";
$db_get_last_time->myCustomQuery($get_last_time_query);
$last = $db_get_last_time->f_last;


$datetime1 = new DateTime($start);
$datetime2 = new DateTime($last);
$interval = $datetime1->diff($datetime2);
$time=$interval->format('%h').":".$interval->format('%i');
?>
<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
				  <div class="col-lg-8 col-md-8 col-sm-8 col-xs-12">
				  <label class="">Completion Status</label>
				<div class="progress">
					<div class="progress-bar" role="progressbar" aria-valuenow="<?=$percent?>" aria-valuemin="0" aria-valuemax="100" style="width:<?=$percent?>%">
					  <span class="sr-only"><?=$percent?>% Complete</span>

					</div> 
				  </div>
				  </div>
				<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
				  <p style="margin-top:10px;font-size:24px;margin-bottom:0px;font-weight:bold;color:#3c763d;"><?=number_format($percent, 2, '.', '');?>%</p> <p class="text-success margin-b-0">Complete</p>
				</div>
				<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">

				<p class="completion-time"><?=$done?> of <?=$total?> stops  <?=$time?> total time</p>
				</div>
				</div>