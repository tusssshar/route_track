<?php 
include("../../../bootstrap.php");
include(ROOTPATH."/admin/authorized.php");

include ROOTPATH.'/admin/templates/admin_header_start.php';
include ROOTPATH.'/admin/templates/admin_header_end.php' ;
?>

<div class="container-fluid detail-page">
  <div class="page-header">
    <h4>
      <div class="clearfix">
        <div class="pull-left"> <a href="manage_master.php"><span class="section-heading">Manage Master data</span></a> </div>
        <div class="pull-right"> <small> &nbsp; </small> </div>
      </div>
    </h4>
  </div>
  <div class="row">
    <div class="col-xs-12">
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>CODE</th>
            <th colspan="2"><center>
                Action
              </center></th>
          </tr>
        </thead>
        <tbody>
          <?php
                        $db_row = new database();
                        $query = "select dd_id,dd_title,dd_value from vjgm_dropdown group by dd_title order by dd_title ASC";
                        $db_row->myCustomQuery($query);
                        $row_count=$db_row->row_count;
                        if($row_count > 0){
                            for($i=0;$i<$row_count;$i++)
                            {
                                $db_row->moveto($i);
                                $CODE_VALUE_ID=$db_row->f_dd_id;
                                $CODE_VALUE_CODE=$db_row->f_dd_title;
                                $CODE_VALUE_VALUE=$db_row->f_dd_value;
                                if($CODE_VALUE_CODE!="LANGUAGE" && $CODE_VALUE_CODE!="WORK VISA REQUIRED"){
                                ?>
          <tr>
            <td><input type="hidden" id="table_hidden_val_<?php echo $code_row["CODE_VALUE_ID"];?>" name="table_hidden_val_<?php echo $code_row["CODE_VALUE_ID"];?>" value="<?php echo $CODE_VALUE_ID."??".$CODE_VALUE_CODE."??".$CODE_VALUE_VALUE;?>">
              <?php echo $CODE_VALUE_CODE;?></td>
            <td style="width:50px;text-align: center;"><a href="<?php echo SITEROOT.'/admin/m/master/manage_master_sub_values.php?code='.base64_encode($CODE_VALUE_CODE)?>">Manage</a></td>
          </tr>
          <?php
                            }
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
