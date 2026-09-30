<?php
	include_once("../../../bootstrap.php");
	include(ROOTPATH."/admin/authorized.php");
	include_once(ROOTPATH."/admin/templates/admin_header_start.php");
	include_once(ROOTPATH."/admin/templates/admin_header_end.php");
        
        //Check Access Level
      $res=$functions->user_access_level(6,$_SESSION["routetrac-admin"]["access_level"]);
      if($res==0){
          echo "<script>window.location.assign('../../../index.php')</script>";
      }
      $objGlobalConnection1 = new dbSqlConnection();
        $oGlobalConnection = $objGlobalConnection1->Connect(HST,USR,PWD,DBN);
        
        if(isset($_REQUEST["submit1"]))
        {
            $_FILES['import_file_name']['name'];
            $sourcePath = $_FILES['import_file_name']['tmp_name'];       
            $targetPath = ROOTPATH."/admin/uploads/import_".date('YmdHis')."_".mt_rand(1000,9999).".csv";
            move_uploaded_file($sourcePath,$targetPath);
    
            $file = fopen($targetPath,"r");
    
            $import_file_columns=array("Status","First_Name","Last_Name","Email_ID","User_Name","Password","Access_Level","Is_Driver","Default_Root");
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
           $status=trim((string) $data_array[0]);
           $first_name=trim((string) $data_array[1]);
           $last_name=trim((string) $data_array[2]);
           $email_addr=trim((string) $data_array[3]);
           $acct_username=trim((string) $data_array[4]);
           $acct_password=trim((string) $data_array[5]);
           $access_level=trim((string) $data_array[6]);
           $is_driver=trim((string) $data_array[7]);
           $default_route_id=trim((string) $data_array[8]);
           $status=(in_array(strtolower($status), array('1','yes','y','active','true')) ? 1 : 0);
           $is_driver=(in_array(strtolower($is_driver), array('1','yes','y','true')) ? 1 : 0);


                    //Check duplicate email            
                    $db1 = new database();
                    
                   $sql="SELECT first_name FROM users WHERE LOWER(email_addr)='".sanitize(strtolower($email_addr))."' OR LOWER(acct_username)='".sanitize(strtolower($acct_username))."'";
                    $db1->myCustomQuery($sql);
                       
                        if($db1->row_count<=0)
                        {

                             $db_import_file->data("status",trim($status));
                             
                             $db_import_file->data("first_name",trim($first_name));
                             $db_import_file->data("last_name",trim($last_name));
                             $db_import_file->data("email_addr",trim($email_addr));
                             $db_import_file->data("acct_username",trim($acct_username));
                             $db_import_file->data("acct_password",trim(md5($acct_password)));
                             
        //Check access level                     
        $objGlobalConnection = new dbSqlConnection();
	$oGlobalConnection = $objGlobalConnection->Connect(HST_1,USR_1,PWD_1,DBN_1);
     
         $db2 = new database();
        
            $sql="SELECT name,level_id FROM access_levels WHERE LOWER(name)='".sanitize(strtolower(trim($access_level)))."'";
            
            $db2->myCustomQuery($sql);
         
		if($db2->row_count>0)
                {
                     $level_name=$db2->f_level_id;
                }else{
                    
                    $db3 = new database();
		    $db3->data("name",trim($access_level));
		    $db3->insert("access_levels");
                    $obj=new functions();
                    $level_name=$db3->id;
                      
                }
          
                             $db_import_file->data("access_level",trim($level_name));
                             $db_import_file->data("is_driver",trim($is_driver));
                            
                //Check route             
                             
        $objGlobalConnection = new dbSqlConnection();
	$oGlobalConnection = $objGlobalConnection->Connect(HST,USR,PWD,DBN);
                              $db4 = new database();
                              
                if($is_driver==1){
          $sql="SELECT name,route_id FROM routes WHERE LOWER(name)='".sanitize(strtolower(trim($default_route_id)))."'";
          
            $db4->myCustomQuery($sql);
              
		if($db4->row_count>0)
                {
                     $route_name=$db4->f_route_id;
                }else{
                    
                    $db5 = new database();
		    $db5->data("name",trim($default_route_id));
		    $db5->insert("routes");
                    $obj=new functions();
                    $route_name=$db5->id;
                }
               }else{
                   $route_name=0;
               }            
                    $db_import_file->data("default_route_id",trim($route_name));
                    //	only "API Only" users (level 5) get a key on import; a time() value was guessable and shared
                    $db_import_file->data("api_key",($level_name==5 ? bin2hex(random_bytes(16)) : ''));
                      
                    $db_import_file->insert("users");
                     
                    $_SESSION["routetrac-admin"]["error_success_msg"] = "File imported successfully";
                        }

                               
                            }
                        }
                        $incrmt_count++;
        }
    }
    
    unlink($targetPath);
    echo "<script>location.href='users.php';</script>";
}
	
?>
<div class="container-fluid detail-page">
  <div class="page-header">
    <h4>
      <div class="clearfix">
        <div class="pull-left"> <a href="users.php"><span class="section-heading">User(s)</span></a> </div>
        <div class="pull-right"> <small> <a href="javascript:void(0);" onclick="javascript:ajaxNewModify(0);" class="" title="Add New User"><i class="fa fa-plus"></i>&nbsp;&nbsp;New User</a>&nbsp;&nbsp;<i class="fa fa-file"></i>&nbsp;&nbsp;<a href="javascript:void(0);" onclick="import_file()">Import</a> &nbsp;&nbsp;<i class="fa fa-download">&nbsp;&nbsp;</i><a href="<?php echo SITEROOT.'/admin/m/master/export.php?value=export_users';?>" class="">Export</a>
                
                </small> </div>
      </div>
    </h4>
  </div>
  <div class="row">
    <div class="col-md-12" id="divList">
      <div class="clearfix">
	  	<!-- 
        <div class="row" id="row_serach">
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
          <br>
        -->
         <div class="row" id="import_id" style="display: none">
          <div class="col-md-3"></div>
          <div class="col-md-6">
            <form action="" id="import_form"  class="form-inline" role="form" enctype="multipart/form-data" method="post"  name="import_form">
              <input type="file" class="form-control" name="import_file_name" id="import_file_name" style="padding: 0;width: 35%"/>
              <button type="submit" name="submit1" id="submit1" value=" Save" class="btn btn-success"><i class="fa fa-floppy-o"></i> Import </button>
                <span style="color:#999999">or</span>&nbsp; <span class="cancellink"><a href="javascript:void(0);" onClick="javascript:import_cancel();">Cancel</a></span> <br>
              <br>
              <i class="fa fa-download"></i>&nbsp;&nbsp;<a href="<?php echo SITEROOT.'/admin/m/master/templates/USERS.csv';?>" class="" download>Download Template</a>
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
<script type="text/javascript" src="js/users.js"></script>
<?php
include ROOTPATH.'/admin/templates/admin_footer_end.php'; 
?>
