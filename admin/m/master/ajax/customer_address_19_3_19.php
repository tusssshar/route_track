<?php
	include_once("../../../../bootstrap.php");
	
	$delete_id = ($_REQUEST['delete_id']?$_REQUEST['delete_id']:0);
	$txtSearch = ($_REQUEST['txtSearch']?$_REQUEST['txtSearch']:'');
	$customer_id = ($_REQUEST['customer_id']?$_REQUEST['customer_id']:0);
       
	if($delete_id > 0){
		$db = new database();
		$db->where("address_id=".$delete_id);
		$db->delete("customer_addresses");
	}

	//pageing coading
	$PAGE = ((empty($_GET['page']))?1:$_GET['page']);
	$PAGE_LIMIT = 25;
	$PAGE_LIMIT_VALUE = ($PAGE - 1) * $PAGE_LIMIT;
	
	if(strlen(trim($txtSearch)) > 0){
		$search_query = " AND (CD.ship_to_name like '%".$txtSearch."%' OR CD.address_line_1 like '%".$txtSearch."%' OR CD.address_line_2 like '%".$txtSearch."%' OR CD.address_line_3 like '%".$txtSearch."%' OR CD.city like '%".$txtSearch."%' OR CD. state like '%".$txtSearch."%' OR CD. zip like '%".$txtSearch."%' OR CD. phone like '%".$txtSearch."%')";
	}
	if(trim($customer_id) > 0){
		$search_query.= " AND (CD.customer_id = '".$customer_id."')";
	}
	
	$db_list = new database();
        $db_list1 = new database();
       
	$query = "SELECT SQL_CALC_FOUND_ROWS C.customer_name,C.account_number,CD.*
					FROM 
						customer_addresses CD LEFT JOIN customers C ON CD.customer_id=C.customer_id  
			 WHERE 1=1  ".$search_query." 
			 ORDER BY CD.address_id desc
			 LIMIT $PAGE_LIMIT_VALUE, $PAGE_LIMIT";
			
	$db_list->myCustomQuery($query);
        $cnt=$db_list->row_count;
	$PAGE_TOTAL_ROWS = $db_list->FOUND_ROWS();
	
	$PAGE_URL = basename($_SERVER['PHP_SELF'])."-txtSearch=".$txtSearch;
	
	$PAGING = $functions->showAjaxPaging("MyAjaxInterView");	
        
        $query1="SELECT customer_name,account_number FROM customers WHERE customer_id='".$customer_id."'";
        $db_list1->myCustomQuery($query1);
        $db_list1->moveto(0);
       $customer_name = $db_list1->f_customer_name;
       $customer_account_no=$db_list1->f_account_number;
        
?>
<script>
//	search code
	var ajaxFilePath = "ajax/customer_address.php";
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
<div class="row">
  <div class="col-md-6" style="padding:0 20px">
    <div class="pull-left">
        <label> Customer Name : </label><?=  ucfirst($customer_name)?>&nbsp;&nbsp;<label>
            Account No.: </label><?=$customer_account_no?>
       
    </div>
  </div>
    <div class="col-md-6" style="padding:0 20px">
    <div class="pull-right">
    <a href="<?php echo SITEROOT.'/admin/m/master/customer.php'?>"><button type="button" class="btn btn-primary">Back</button></a>
    </div>
        </div>
    
</div>
<?php
    if($cnt<=0){
        echo "<br>";
    }
?>
<br>
<div class="alert alert-success" id="successMsg" style="margin:10px;display:none">Success! Well done its submitted.</div>
<div class="row">
  <div class="col-md-12" style="padding:0 20px">
  	<div class="pull-left">
		<form class="form-inline" role="form" autocomplete="off" enctype="multipart/form-data" method="post"  name="form" id="form">
              <div class="form-group">
                <label class="sr-only" for="name">Name</label>
                <input type="text" name="txtSearch" id="txtSearch" value="<?=$txtSearch;?>" class="form-control" />
                <input type="hidden" value="<?=$customer_id?>" id="customer_id" name="customer_id" />
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
          <th>Ship To Name</th>
          <th>Address</th>
          <th>City</th>
          <th>State</th>
          <th>Zip</th>
          <th>Phone</th>
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
                                       
                      
			?>
        <tr id="tr_<?=$db_list->f_address_id?>">
          <td class="text-center"><?=$counter?></td>
          <td><?=$db_list->f_ship_to_name?></td>
          <td><?=$db_list->f_address_line_1." ".$db_list->f_address_line_2." ".$db_list->f_address_line_3?></td>
          <td><?=$db_list->f_city?></td>
          <td><?=$db_list->f_state?></td>
          <td><?=$db_list->f_zip?></td>
          <td><?=$db_list->f_phone?></td>
          <td class="text-center"><a href="javascript:void(0);" onclick="javascript:ajaxNewModify(<?=$db_list->f_address_id;?>,0);" title="Edit Address"><i class="fa fa-pencil-square-o fa-size"></i></a> &nbsp;&nbsp;
            <a href="javascript:void(0);" onclick="javascript:deleteGridRecord_customer_address('<?=SITEROOT?>/admin/m/master/ajax/customer_address.php?delete_id=<?=$db_list->
            f_address_id?>',<?=$db_list->f_address_id?>);" title="Delete Address"><i class="fa fa-trash-o fa-size"></i></a>
          </td>
        </tr>
        <?php
					$counter++;
            	}
			}else{
			?>
        <tr>
          <td align='center' colspan="100%" style="height:40px;">No Addresses found</td>
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
