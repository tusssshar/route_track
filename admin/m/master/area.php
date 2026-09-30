<?php
	include_once("../../../bootstrap.php");
	include(ROOTPATH."/admin/authorized.php");
	include_once(ROOTPATH."/admin/templates/admin_header_start.php");
	include_once(ROOTPATH."/admin/templates/admin_header_end.php");
      
      //Check Access Level
      $res=$functions->user_access_level(1,$_SESSION["routetrac-admin"]["access_level"]);
      if($res==0){
          echo "<script>window.location.assign('../../../index.php')</script>";
      }
      
        $objGlobalConnection1 = new dbSqlConnection();
        $oGlobalConnection = $objGlobalConnection1->Connect(HST,USR,PWD,DBN);
        if(isset($_REQUEST["submit1"]))
{
     
    // $_FILES['import_file_name']['name'];
     $sourcePath = $_FILES['import_file_name']['tmp_name'];       
     $targetPath = ROOTPATH."/admin/uploads/import_".date('YmdHis')."_".mt_rand(1000,9999).".csv";
     move_uploaded_file($sourcePath,$targetPath);
    
    $file = fopen($targetPath,"r");
    
    $import_file_columns=array("Name","IsDefault");
    $file_columns=fgetcsv($file);
    //print_r($file_columns);exit;
    $import_data_flag="true";
    $boolean_var=$functions->check_csv_columns($import_file_columns,$file_columns);
    
    if($boolean_var=="false")
    {
        $import_data_flag="false";
       $_SESSION["routetrac-admin"]["error_success_msg"]="<span style='color:red'>Columns not matched</span>";
    }
    fclose($file);
    $file = fopen($targetPath,"r");
    $incrmt_count=0;    
    if($boolean_var=="true")
    {
        while(! feof($file))
        {
                $data_array=fgetcsv($file);
                
                //print_r($data_array);
                if($incrmt_count==0)
                {
                   
                }else 
                {
                    if(is_array($data_array))
                    {
                        
                        $db_import_file=new database();
                        $area_name=trim((string) $data_array[0]);
                        $is_default=trim((string) $data_array[1]);
                        
            //Check duplicate area            
            $db = new database();
            $sql="SELECT name,area_id FROM areas WHERE LOWER(name)='".sanitize(strtolower($area_name))."'";
            
            $db->myCustomQuery($sql);
        
		if($db->row_count<=0)
                {
                     $db_import_file->data("name",trim($area_name));
		     $db_import_file->data("is_default",trim($is_default));
		     $db_import_file->insert("areas");
                }
                
                      $_SESSION["routetrac-admin"]["error_success_msg"] = "File imported successfully";
                    }
                }
                $incrmt_count++;
        }
    }
    
    unlink($targetPath);
    echo "<script>location.href='area.php';</script>";
}
?>

<div class="container-fluid detail-page">
  <div class="page-header">
    <h4>
      <div class="clearfix">
        <div class="pull-left"> <a href="area.php"><span class="section-heading">Delivery Areas</span></a> </div>
        <div class="pull-right"> <small> <a href="javascript:void(0);" onclick="javascript:ajaxNewModify(0);" class="" title="Add New Delivery Area"><i class="fa fa-plus"></i>&nbsp;&nbsp;New Delivery Area </a>&nbsp;&nbsp;<i class="fa fa-file"></i>&nbsp;&nbsp;<a href="javascript:void(0);" onclick="import_file()" title="">Import</a> &nbsp;&nbsp;<i class="fa fa-download"></i> &nbsp;<a href="<?php echo SITEROOT.'/admin/m/master/export.php?value=export_area';?>" class="" title="">Export</a></small> </div>
      </div>
    </h4>
  </div>
  <div class="row">
    <div class="col-md-12" id="divList">
      <div class="clearfix">
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
              <i class="fa fa-download"></i>&nbsp;&nbsp;<a href="<?php echo SITEROOT.'/admin/m/master/templates/AREA.csv';?>" class="" download>Download Template</a>
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
<script>

</script>
<script type="text/javascript" src="js/area.js"></script>
<?php
include ROOTPATH.'/admin/templates/admin_footer_end.php'; 
?>
