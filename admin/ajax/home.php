<?php
include_once("../../bootstrap.php");

$deliver_type = ($_REQUEST['deliver_type'] ? $_REQUEST['deliver_type'] : '');
$limit = ($_REQUEST['limit'] ? (int) $_REQUEST['limit'] : '');
if ($deliver_type == "Delivery")
    $t = "'Delivery'";
elseif ($deliver_type == "Pickup")
    $t = "'Pickup'";
else
    $t = "'Delivery','Pickup'";



//pageing coading
$PAGE = ((empty($_REQUEST['limit'])) ? 1 : (int) $_REQUEST['limit']);
$PAGE_LIMIT = $limit;
$PAGE_LIMIT_VALUE = ($PAGE - 1) * (int) $PAGE_LIMIT;

if (strlen(trim($deliver_type)) > 0) {
    $delivery_type_query = " and (mani.manifest_type  in ($t))";
}
if (strlen(trim($limit)) > 0) {
    $limit_query = " order by d.delivery_time DESC LIMIT $PAGE_LIMIT";
}
$db_list = new database();
$query = "select d.*,mani.manifest_type,cust.customer_name,cust.account_number,rout.name as rname from 
			deliveries d
                        INNER JOIN manifests mani on d.manifest_id=mani.manifest_id
                        INNER JOIN customers cust on d.customer_id=cust.customer_id
                        INNER JOIN routes rout on d.route_id=rout.route_id
			 where 1=1  " . $delivery_type_query . " 
			  " . $limit_query . " ";
//echo $query;

$db_list->myCustomQuery($query);

$PAGE_TOTAL_ROWS = $db_list->FOUND_ROWS();

$PAGE_URL = basename($_SERVER['PHP_SELF']) . "-deliver_type=" . $deliver_type . "-limit=" . $limit;

$PAGING = $functions->showAjaxPaging("MyAjaxInterView");
?>
<script>
//	search code
    var ajaxFilePath = "ajax/home.php";
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
<div class="alert alert-success" id="successMsg" style="margin:10px;display:none">Success! Well done its submitted.</div>

<div class="row">
    <div class="col-md-12">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th class="text-center">Type</th>
                    <th>Customer</th>
                    <th>Route</th>
                    <th>Time Delivered/Pickup</th> 
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
                        <tr>
                            <td class="text-center"><?= ucfirst($db_list->f_manifest_type) ?></td>
                            <td><?= ucfirst($db_list->f_customer_name) ?></td>
                            <td><?= ucfirst($db_list->f_rname) ?></td>
                            <td><?= ucfirst($db_list->f_delivery_time) ?></td>
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
            <?//= $PAGING ?>
        </div>
    </div>
</div>
