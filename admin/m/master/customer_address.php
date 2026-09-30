<?php
	include_once("../../../bootstrap.php");
	include(ROOTPATH."/admin/authorized.php");
	include_once(ROOTPATH."/admin/templates/admin_header_start.php");
	include_once(ROOTPATH."/admin/templates/admin_header_end.php");
        
        //echo $_SESSION["routetrac-admin"]["access_level"];
        //exit();
        //Check Access Level
      $res=$functions->user_access_level(4,$_SESSION["routetrac-admin"]["access_level"]);
      if($res==0){
          echo "<script>window.location.assign('../../../index.php')</script>";
      }
      $objGlobalConnection1 = new dbSqlConnection();
      $oGlobalConnection = $objGlobalConnection1->Connect(HST,USR,PWD,DBN);
      
    $customer_id = ($_REQUEST['customer_id']?$_REQUEST['customer_id']:0);
      
        if(isset($_REQUEST["submit1"]))
{
 
     $_FILES['import_file_name']['name'];
     $sourcePath = $_FILES['import_file_name']['tmp_name'];       
     $targetPath = ROOTPATH."/admin/uploads/import_".date('YmdHis')."_".mt_rand(1000,9999).".csv";
     $res=move_uploaded_file($sourcePath,$targetPath);
     $file = fopen($targetPath,"r");
    
    $import_file_columns=array("Customer_Name","Ship_To_Name","Address_Line_1","Address_Line_2","Addreess_Line_3","City","State","Zip","Phone");
    
    $file_columns=fgetcsv($file);
    //print_r($file_columns);
    $import_data_flag="true";
    $boolean_var=$functions->check_csv_columns($import_file_columns,$file_columns);

    if($boolean_var=="false")
    {
        $import_data_flag="false";
        $_SESSION["routetrac-admin"]["error_success_msg"]="<span style='color:red'>Columns not matched</span>";
        
    }
       
    fclose($file);
    $file = fopen($targetPath,"r");
    // echo "<pre>";
            //print_r($data_array);exit;
    $incrmt_count=0;    
   
    if($boolean_var=="true")
    {
        
        while(! feof($file))
        {
                $data_array=fgetcsv($file);
              // print_r($data_array);exit;
                
                if($incrmt_count==0)
                {
                   
                }else 
                {
                    if(is_array($data_array))
                    {
                         
                        $db_import_file=new database();
                        $customer_name1=trim((string) $data_array[0]);
                        $ship_to_name=trim((string) $data_array[1]);
                        $address_line_1=trim((string) $data_array[2]);
                        $address_line_2=trim((string) $data_array[3]);
                        $address_line_3=trim((string) $data_array[4]);
                        $city=trim((string) $data_array[5]);
                        $state=trim((string) $data_array[6]);
                        $zip=trim((string) $data_array[7]);
                        $phone=trim((string) $data_array[8]);
                        
                        
             $db = new database();
            $sql="SELECT customer_name,customer_id FROM customers WHERE LOWER(customer_name)='".sanitize(strtolower($customer_name1))."'";
            
            $db->myCustomQuery($sql);
        
		if($db->row_count>0)
                {
                     $customer_name=$db->f_customer_id;
                }else{
                    
                    $db1 = new database();
		    $db1->data("customer_name",trim($customer_name1));
		    $db1->insert("customers");
                    $obj=new functions();
                    $customer_name=$db1->id;
                      
                }
           $db_import_file->data("customer_id",trim($customer_name));
           $db_import_file->data("ship_to_name",trim($ship_to_name));
           $db_import_file->data("address_line_1",trim($address_line_1));
           $db_import_file->data("address_line_2",trim($address_line_2));
           $db_import_file->data("address_line_3",trim($address_line_3));
           $db_import_file->data("city",trim($city));
           $db_import_file->data("state",trim($state));
           $db_import_file->data("zip",trim($zip));
           $db_import_file->data("phone",trim($phone));

           $db_import_file->insert("customer_addresses");
                       
                    $_SESSION["routetrac-admin"]["error_success_msg"] = "File imported successfully";
                    }
                }
                $incrmt_count++;
        }
    }
    
    unlink($targetPath);
    echo "<script>location.href='customer_address.php';</script>";
}
	
?>

<div class="container-fluid detail-page">
  <div class="page-header">
    <h4>
      <div class="clearfix">
        <div class="pull-left"> <a href="customer_address.php"><span class="section-heading">Customer(s) Address</span></a> </div>
        <div class="pull-right"> <small> <a href="javascript:void(0);" onclick="javascript:ajaxNewModify(0,<?php echo $customer_id?>);" class="" title="Add New Address"><i class="fa fa-plus"></i>&nbsp;&nbsp;New Address</a>&nbsp;&nbsp;<i class="fa fa-file"></i>&nbsp;&nbsp;<a href="javascript:void(0);" onclick="import_file()">Import</a> &nbsp;&nbsp;
                
                <i class="fa fa-download"></i>&nbsp;&nbsp;<a href="<?php echo SITEROOT.'/admin/m/master/export.php?value=export_customer_address';?>" class="">Export</a>
                
                </small> </div>
      </div>
    </h4>
  </div>
  <div class="row">
    <div class="col-md-12" id="divList">
      <div class="clearfix">
          <input type="hidden" value="<?=$customer_id?>" id="customer_id" name="customer_id" />
<!--        <div class="row" id="row_serach">
          <div class="col-md-12">
            <form class="form-inline" role="form" autocomplete="off" enctype="multipart/form-data" method="post"  name="form" id="form">
              <div class="form-group">
                <label class="sr-only" for="name">Name</label>
                
                <input type="text" name="txtSearch" id="txtSearch" class="form-control" />
              </div>
              <input name="search" type="submit" id="search" value="  Search " class="btn btn-default" />
              <div class="form-group"> <a href="javascript:void(0);" onclick="javascript:clearSearchValues();">Clear Search</a> </div>
            </form>
          </div>
        </div>
          <br>-->
          
          <div class="row" id="import_id" style="display: none">
          <div class="col-md-3"></div>
          <div class="col-md-6">
            <form action="" id="import_form"  class="form-inline" role="form" enctype="multipart/form-data" method="post"  name="import_form">
              <input type="file" class="form-control" name="import_file_name" id="import_file_name" style="padding: 0;width: 35%"/>
              <button type="submit" name="submit1" id="submit1" value=" Save" class="btn btn-success"><i class="fa fa-floppy-o"></i> Import </button>
                <span style="color:#999999">or</span>&nbsp; <span class="cancellink"><a href="javascript:void(0);" onClick="javascript:import_cancel();">Cancel</a></span> <br>
              <br>
              <i class="fa fa-download"></i>&nbsp;&nbsp;<a href="<?php echo SITEROOT.'/admin/m/master/templates/CUSTOMER_ADDRESS.csv';?>" class="" download>Download Template</a>
            </form>
          </div>
          <div class="col-md-3"></div>
        </div>
        <div class="row" id="row_list">
          <div class="col-md-12">
            <div id="MyAjaxInterView">&nbsp;</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-0" id="divForm" style="display:none"></div>
  </div>
</div>
<?php 
include ROOTPATH.'/admin/templates/admin_footer_start.php';
?>
<!--jquery.blockUI-->
<script type="text/javascript" src="<?php echo SITEROOT; ?>/admin/templates/js/jquery/blockUI/jquery.blockUI.js"></script>
<script type="text/javascript" src="js/customer_address.js"></script>
<?php
include ROOTPATH.'/admin/templates/admin_footer_end.php'; 
?>
