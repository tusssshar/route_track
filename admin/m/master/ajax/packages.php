<?php
	include_once("../../../../bootstrap.php");
	
	$delete_id = (int) ($_REQUEST['delete_id'] ? $_REQUEST['delete_id'] : 0);
	
	$txtSearch = ($_REQUEST['txtSearch']?$_REQUEST['txtSearch']:'');
	
	if($delete_id > 0){
		$db = new database();
		$db->where("package_type_id=".$delete_id);
		$db->delete("package_types");
	}

	//pageing coading
	$PAGE = ((empty($_GET['page'])) ? 1 : max(1, (int) $_GET['page']));
	$PAGE_LIMIT = 25;
	$PAGE_LIMIT_VALUE = ($PAGE - 1) * $PAGE_LIMIT;
	
	if(strlen(trim($txtSearch)) > 0){
		$search_query = " and (package_types.name like '%".sanitize($txtSearch)."%')";
	}
	
	$db_list = new database();
	$query = "select SQL_CALC_FOUND_ROWS package_types.*
					from 
						package_types   
			 where 1=1  ".$search_query." 
			 order by package_types.package_type_id desc
			 LIMIT $PAGE_LIMIT_VALUE, $PAGE_LIMIT";
			  
			 
			 //vjgm_packages.pack_is_deleted=0
	
	$db_list->myCustomQuery($query);

       
	$PAGE_TOTAL_ROWS = $db_list->FOUND_ROWS();
	
	$PAGE_URL = basename($_SERVER['PHP_SELF'])."-txtSearch=".$txtSearch;
	
	$PAGING = $functions->showAjaxPaging("MyAjaxInterView");	
?>
<script>
//	search code
	var ajaxFilePath = "ajax/packages.php";
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
                                        $c_id=$db_list->f_c_id;
                    
			?>
        <tr id="tr_<?=$db_list->f_package_type_id?>">
          <td class="text-center"><?=$counter?></td>
          <td> <?= ucfirst($db_list->f_name);?></td>
         
          <td class="text-center"><a href="javascript:void(0);" onclick="javascript:ajaxNewModify(<?=$db_list->f_package_type_id;?>);" title="Edit Package"><i class="fa fa-pencil-square-o fa-size"></i></a> &nbsp;&nbsp;
            <a href="javascript:void(0);" onclick="javascript:deleteGridRecord_package('<?=SITEROOT?>/admin/m/master/ajax/packages.php?delete_id=<?=$db_list->
            f_package_type_id?>',<?=$db_list->f_package_type_id?>);" title="Delete Package"><i class="fa fa-trash-o fa-size"></i></a>
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
