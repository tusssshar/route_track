<?php
	include_once("../../../../bootstrap.php");
	
	$delete_id = ($_REQUEST['delete_id']?$_REQUEST['delete_id']:0);
	
	$txtSearch = ($_REQUEST['txtSearch']?$_REQUEST['txtSearch']:'');
	
	if($delete_id > 0){
		$db = new database();
		$db->where("route_id=".$delete_id);
		$db->delete("routes");
	}

	//pageing coading
	$PAGE = ((empty($_GET['page']))?1:$_GET['page']);
	$PAGE_LIMIT = 25;
	$PAGE_LIMIT_VALUE = ($PAGE - 1) * $PAGE_LIMIT;
	
	if(strlen(trim($txtSearch)) > 0){
		$search_query = " and (R.name like '%".$txtSearch."%')";
	}
	
	$db_list = new database();
	$query = "select SQL_CALC_FOUND_ROWS A.name AS area_name,R.*
					from 
						routes R LEFT JOIN areas A ON R.area_id=A.area_id 
			 where 1=1  ".$search_query." 
			 order by R.route_id desc
			 LIMIT $PAGE_LIMIT_VALUE, $PAGE_LIMIT";
			  
			 
			 //vjgm_packages.pack_is_deleted=0
	
	$db_list->myCustomQuery($query);

	$PAGE_TOTAL_ROWS = $db_list->FOUND_ROWS();
	
	$PAGE_URL = basename($_SERVER['PHP_SELF'])."-txtSearch=".$txtSearch;
	
	$PAGING = $functions->showAjaxPaging("MyAjaxInterView");	
?>
<script>
//	search code
	var ajaxFilePath = "ajax/routes.php";
	$(document).ready(function(){
		if(document.getElementById("txtSearch")){
			$("#txtSearch").focus();	
		}
		$("#form").submit(function(){
			loadingIndicator('start');
			$.post(ajaxFilePath,$(this).serializeArray(),function(data){
				$("#MyAjaxInterView").html(data);
				loadingIndicator('end');
			}, "html");
			return false;
		});//search submit
	});//document ready

	//	clear all search values
	function clearSearchValues(){
		$("#txtSearch").val("");
		loadDefaultList();
	}
</script>
<div class="alert alert-success" id="successMsg" style="margin:10px;display:none">Success! Well done its submitted.</div>
<div class="row">
  <div class="col-md-12" style="padding:0 20px">
  	<div class="pull-left">
		<form class="form-inline" role="form" autocomplete="off" enctype="multipart/form-data" method="post"  name="form" id="form">
              <div class="form-group">
                <label class="sr-only" for="name">Name</label>
                <input type="text" name="txtSearch" id="txtSearch" value="<?=$txtSearch;?>" class="form-control" />
              </div>
              <input name="search" type="submit" id="search" value="  Search " class="btn btn-default" />
              <div class="form-group"> <a href="javascript:void(0);" onclick="javascript:clearSearchValues();">Clear Search</a> </div>
            </form>
	</div>
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
          <th>Area</th>
          <th>Route</th>
          <th class="text-center">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php
                    $db_list1 = new database();
			$counter = 1;
			if($db_list->row_count > 0){
				$counter = ($PAGE_LIMIT_VALUE+1);
				for($i=0;$i<$db_list->row_count;$i++){
					$db_list->moveto($i);
                                        $c_id=$db_list->f_c_id;
                      
			?>
        <tr id="tr_<?=$db_list->f_route_id?>">
          <td class="text-center"><?=$counter?></td>
          <td><?=ucfirst($db_list->f_area_name)?></td>
          <td><?=ucfirst($db_list->f_name)?></td>
           
          <td class="text-center"><a href="javascript:void(0);" onclick="javascript:ajaxNewModify(<?=$db_list->f_route_id;?>);" title="Edit Area"><i class="fa fa-pencil-square-o fa-size"></i></a> &nbsp;&nbsp;
            <a href="javascript:void(0);" onclick="javascript:deleteGridRecord_route('<?=SITEROOT?>/admin/m/master/ajax/routes.php?delete_id=<?=$db_list->
            f_route_id?>',<?=$db_list->f_route_id?>);" title="Delete Area"><i class="fa fa-trash-o fa-size"></i></a>
          </td>
        </tr>
        <?php
					$counter++;
            	}
			}else{
			?>
        <tr>
          <td align='center' colspan="100%" style="height:40px;">No Route found</td>
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
      <?=$PAGING?>
    </div>
  </div>
</div>
