<?php
include_once("../../../../bootstrap.php");

$delete_id = ($_REQUEST['delete_id'] ? $_REQUEST['delete_id'] : 0);

$txtSearch = ($_REQUEST['txtSearch'] ? $_REQUEST['txtSearch'] : '');

if ($delete_id > 0) {
    $db = new database();
    $db->where("s_id=" . $delete_id);
    $db->delete("vjgm_skills");
}

//pageing coading
$PAGE = ((empty($_GET['page'])) ? 1 : $_GET['page']);
$PAGE_LIMIT = 25;
$PAGE_LIMIT_VALUE = ($PAGE - 1) * $PAGE_LIMIT;

if (strlen(trim($txtSearch)) > 0) {
    $search_query = " and (vjgm_point_consumption.pc_activity like '%" . $txtSearch . "%') or (vjgm_point_consumption_company.pcc_points like '%" . $txtSearch . "%')";
}

$db_list = new database();
$query = "select SQL_CALC_FOUND_ROWS vjgm_point_consumption.*,vjgm_point_consumption_company.*
					from 
						vjgm_point_consumption inner join vjgm_point_consumption_company on vjgm_point_consumption.pc_id=vjgm_point_consumption_company.pc_id
			 where 1=1 and vjgm_point_consumption_company.c_id=0 " . $search_query . " 
			 order by vjgm_point_consumption_company.pcc_id desc
			 LIMIT $PAGE_LIMIT_VALUE, $PAGE_LIMIT";

//vjgm_skills.pack_is_deleted=0

$db_list->myCustomQuery($query);

$PAGE_TOTAL_ROWS = $db_list->FOUND_ROWS();

$PAGE_URL = basename($_SERVER['PHP_SELF']) . "-txtSearch=" . $txtSearch;

$PAGING = $functions->showAjaxPaging("MyAjaxInterView");
?>

<div class="alert alert-success" id="successMsg" style="margin:10px;display:none">Success! Well done its submitted.</div>
<div class="row">
    <div class="col-md-12" style="padding:0 20px">
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
                    <th class="text-center">#</th>
                    <th>Type</th>          
                    <th>Points</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $counter = 1;
                if ($db_list->row_count > 0) {
                    $counter = ($PAGE_LIMIT_VALUE + 1);
                    for ($i = 0; $i < $db_list->row_count; $i++) {
                        $db_list->moveto($i);
                        $pc_id=$db_list->f_pc_id;
                        $activity_for="";
                        switch($pc_id)
                        {
                            case "1":
                                $activity_for="Employer";
                                break;
                            case "2":
                                $activity_for="Employer";
                                break;
                            case "3":
                                $activity_for="Employer";
                                break;
                            case "4":
                                $activity_for="Employee";
                                break;
                            case "5":
                                $activity_for="Employer";
                                break;
                            case "6":
                                $activity_for="Employee";
                                break;
                            case "7":
                                $activity_for="Employer";
                                break;
                        }
                        ?>
                        <tr id="tr_<?= $db_list->f_pcc_id ?>">
                            <td class="text-center"><?= $counter ?></td>
                            <td><?= htmlentities($db_list->f_pc_activity)." (".$activity_for.")" ?></td>
                            <td><?= htmlentities($db_list->f_pcc_points) ?></td>
                            <td class="text-center"><a href="javascript:void(0);" onclick="javascript:ajaxNewModify(<?= $db_list->f_pcc_id; ?>);"><i class="fa fa-pencil-square-o fa-size"></i></a> &nbsp;&nbsp;
                              <!--<a href="javascript:void(0);" onclick="javascript:deleteGridRecord('<?= SITEROOT ?>/admin/m/master/ajax/points_consumption.php?delete_id=<?= $db_list->
                f_pcc_id
                        ?>',<?= $db_list->f_pcc_id ?>);"><i class="fa fa-trash-o fa-size"></i></a>-->
                            </td>
                        </tr>
                        <?php
                        $counter++;
                    }
                } else {
                    ?>
                    <tr>
                        <td align='center' colspan="100%" style="height:40px;">No Points consumption found</td>
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
