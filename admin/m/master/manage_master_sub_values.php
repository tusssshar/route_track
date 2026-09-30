<?php 
include("../../../bootstrap.php");
include(ROOTPATH."/admin/authorized.php");

$code = (base64_decode($_REQUEST['code'])?base64_decode($_REQUEST['code']):'');

include ROOTPATH.'/admin/templates/admin_header_start.php';
include ROOTPATH.'/admin/templates/admin_header_end.php' ;
?>

<div class="container-fluid detail-page">
<div class="page-header">
  <h4>
    <div class="clearfix">
      <div class="pull-left"> <a href="manage_master.php"><span class="section-heading">Manage Master value(s)</span></a> </div>
      <div class="pull-right"> <small> &nbsp; </small> </div>
    </div>
  </h4>
</div>
<div class="row">
<input type='hidden' name="exist_code" id="exist_code" value='<?php echo $code;?>'>
<div class="col-lg-10 col-md-10 col-sm-12 col-xs-12">
<form role="form" class="cominfo-form">
<div class="form-group">
  <div class="col-lg-4 col-md-4 col-sm-6">
    <label for="noc" class="data-content"><font color="red">*</font> Value :</label>
  </div>
  <div class="col-lg-8 col-md-8 col-sm-6">
      <input type="text" class="form-control alphabate" id="new_value" name="new_value" maxlength="30">
    <div id="enew_value" class="validation_div"></div>
  </div>
</div>
<div class="form-group">
  <div class="col-lg-4 col-md-4 col-sm-6"> </div>
  <div class="col-lg-8 col-md-8 col-sm-6">
	<button type="button" name="submit" id="submit" value="SAVE" class="btn btn-success" onclick="save_master_value('<?php echo SITEROOT;?>')"><i class="fa fa-floppy-o"></i> Save </button>
	
    <input type="reset" value="Reset" class="btn btn-default">
  </div>
</div>
</div>
</div>
<div class="row">
  <div class="col-xs-12">
    <input type="hidden" id="hidden_edit_id" name="hidden_edit_id" value="">
    <table class="table table-bordered">
      <thead>
        <tr>
          <th>CODE</th>
          <th>VALUE</th>
          <th colspan="2"><center>
              Action
            </center></th>
        </tr>
      </thead>
      <tbody>
        <?php
                        $db_row = new database();
                        $query="select * from vjgm_dropdown where dd_title='".$code."' and dd_is_deleted=0 order by dd_id desc";
                        //$query = "select * from code_value group by CODE_VALUE_CODE order by CODE_VALUE_ID desc";
                        $db_row->myCustomQuery($query);
                        $row_count=$db_row->row_count;
                        if($row_count > 0){
                            for($i=0;$i<$row_count;$i++)
                            {
                                $db_row->moveto($i);
                                $CODE_VALUE_ID=$db_row->f_dd_id;
                                $CODE_VALUE_CODE=$db_row->f_dd_title;
                                $CODE_VALUE_VALUE=$db_row->f_dd_value;
                                ?>
        <tr>
          <td><input type="hidden" id="table_hidden_val_<?php echo $CODE_VALUE_ID;?>" name="table_hidden_val_<?php echo $code_row["CODE_VALUE_ID"];?>" value="<?php echo $CODE_VALUE_ID."??".$CODE_VALUE_CODE."??".$CODE_VALUE_VALUE;?>">
            <?php echo $CODE_VALUE_CODE;?> </td>
          <td><?php echo $CODE_VALUE_VALUE;?></td>
          <td style="width:50px;text-align: center;"><input type="button" value="Edit" onclick="edit_master_code('<?php echo SITEROOT;?>','<?php echo $CODE_VALUE_ID;?>')"></td>
          <td style="width:50px;text-align: center;"><input type="button" value="Delete" onclick="delete_master_code('<?php echo SITEROOT;?>','<?php echo $CODE_VALUE_ID;?>')"></td>
        </tr>
        <?php
                            }
                        }
                        ?>
      </tbody>
    </table>
  </div>
</div>
</div>
<?php 
include ROOTPATH.'/admin/templates/admin_footer_start.php';
include ROOTPATH.'/admin/templates/admin_footer_end.php'; 
?>
