<?php
	include_once("../../../bootstrap.php");
	include(ROOTPATH."/admin/authorized.php");
	include_once(ROOTPATH."/admin/templates/admin_header_start.php");
	include_once(ROOTPATH."/admin/templates/admin_header_end.php");
        
        //Check Access Level
      $res=$functions->user_access_level(8,$_SESSION["routetrac-admin"]["access_level"]);
      if($res==0){
          echo "<script>window.location.assign('../../../index.php')</script>";
      }
        
        $objGlobalConnection = new dbSqlConnection();
	$oGlobalConnection = $objGlobalConnection->Connect(HST_1,USR_1,PWD_1,DBN_1);
        
   ?>
<div class="container-fluid detail-page">
  <div class="page-header">
    <h4>
      <div class="clearfix">
        <div class="pull-left"> <a href="area.php"><span class="section-heading">Access Level(s)</span></a> </div>
       
      </div>
    </h4>
  </div>
  <div class="row">
    <div class="col-md-12" id="divList">
      <div class="clearfix">
<!--        <div class="row">
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
        </div>-->
         
        <div class="row">
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
<script type="text/javascript" src="js/access_level.js"></script>
    <?php
include ROOTPATH.'/admin/templates/admin_footer_end.php'; 
?>
