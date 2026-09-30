<?php
	include_once("../../../../bootstrap.php");
	
	$delete_id = ($_REQUEST['delete_id']?$_REQUEST['delete_id']:0);
	
	$txtSearch = ($_REQUEST['txtSearch']?$_REQUEST['txtSearch']:'');
	
	if($delete_id > 0){
		$db = new database();
		$db->where("s_id=".$delete_id);
		$db->delete("vjgm_skills");
	}

	//pageing coading
	$PAGE = ((empty($_GET['page']))?1:$_GET['page']);
	$PAGE_LIMIT = 25;
	$PAGE_LIMIT_VALUE = ($PAGE - 1) * $PAGE_LIMIT;
	
	if(strlen(trim($txtSearch)) > 0){
		$search_query = " and (vjgm_skills.s_name like '%".$txtSearch."%')";
	}
	
	$db_list = new database();
	$query = "select SQL_CALC_FOUND_ROWS vjgm_skills.*
					from 
						vjgm_skills
			 where 1=1 ".$search_query." 
			 order by vjgm_skills.s_id desc
			 LIMIT $PAGE_LIMIT_VALUE, $PAGE_LIMIT";
			 
			 //vjgm_skills.pack_is_deleted=0
	
	$db_list->myCustomQuery($query);

	$PAGE_TOTAL_ROWS = $db_list->FOUND_ROWS();
	
	$PAGE_URL = basename($_SERVER['PHP_SELF'])."-txtSearch=".$txtSearch;
	
	$PAGING = $functions->showAjaxPaging("MyAjaxInterView");	
?>

<div class="alert alert-success" id="successMsg" style="margin:10px;display:none">Success! Well done its submitted.</div>
<div class="row">
  <div class="col-md-12" style="padding:0 20px">
    <div class="pull-right">
      <?=$PAGING?>
    </div>
  </div>
</div>
<div class="row">
  <div class="col-md-12">
    <table class="table table-hover">
      <thead>
        <tr>
          <th class="text-center">#</th>
          <th>Name</th>
          <th class="text-center">Action</th>
        </tr>
      </thead>
      <tbody>
        <?
			$counter = 1;
			if($db_list->row_count > 0){
				$counter = ($PAGE_LIMIT_VALUE+1);
				for($i=0;$i<$db_list->row_count;$i++){
					$db_list->moveto($i);
			?>
        <tr id="tr_<?=$db_list->f_s_id?>">
          <td class="text-center"><?=$counter?></td>
          <td><?=htmlentities($db_list->f_s_name)?></td>
          <td class="text-center"><a href="javascript:void(0);" onclick="javascript:ajaxNewModify(<?=$db_list->f_s_id;?>);" title="Edit Skill"><i class="fa fa-pencil-square-o fa-size"></i></a> &nbsp;&nbsp;
            <a href="javascript:void(0);" onclick="javascript:deleteGridRecord1('<?=SITEROOT?>/admin/m/master/ajax/skills.php?delete_id=<?=$db_list->
                  f_s_id?>',<?=$db_list->f_s_id?>);" title="Delete Skill"><i class="fa fa-trash-o fa-size"></i></a>
          </td>
        </tr>
        <?
					$counter++;
            	}
			}else{
			?>
        <tr>
          <td align='center' colspan="100%" style="height:40px;">No packages found</td>
        </tr>
        <?
			}
			?>
      </tbody>
    </table>
  </div>
</div>
<div class="row">
  <div class="col-md-12" style="padding:0 20px">
    <div class="pull-right">
      <?=$PAGING?>
    </div>
  </div>
</div>
