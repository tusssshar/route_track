<?php include_once(__DIR__ . "/includes/page_start.php"); ?>
<div class="container-fluid detail-page">
  <div class="page-header">
    <h4>
      <div class="clearfix">
        <div class="pull-left"> <a href="vehicles.php"><span class="section-heading">Vehicle(s)</span></a> </div>
        <div class="pull-right"> <small> <a href="javascript:void(0);" onclick="javascript:ajaxNewModify(0);" title="Add New Vehicle"><i class="fa fa-plus"></i>&nbsp;&nbsp;New Vehicle</a>
          &nbsp;&nbsp;<i class="fa fa-road"></i>&nbsp;&nbsp;<a href="trips.php">Trips &amp; Dispatch</a></small> </div>
      </div>
    </h4>
  </div>
  <div class="alert alert-success" id="successMsg" style="margin:10px;display:none"></div>
  <div class="row">
    <div class="col-md-12" id="divList">
      <div class="row">
        <div class="col-md-12" style="padding:0 20px">
          <form class="form-inline" role="form" autocomplete="off" method="post" name="form" id="form">
            <div class="form-group">
              <input type="text" name="txtSearch" id="txtSearch" class="form-control" placeholder="Registration / model / FASTag" />
            </div>
            <div class="form-group">
              <select name="status" id="status" class="form-control">
                <option value="">All statuses</option>
                <?= fleet::options(fleet::$VEHICLE_STATUSES) ?>
              </select>
            </div>
            <input name="search" type="submit" id="search" value="  Search " class="btn btn-default" />
            <div class="form-group"> <a href="javascript:void(0);" onclick="javascript:clearSearchValues();">Clear Search</a> </div>
          </form>
        </div>
      </div>
      <div class="row" id="row_list">
        <div class="col-md-12">
          <div id="MyAjaxInterView">&nbsp;</div>
        </div>
      </div>
    </div>
    <div class="col-md-0" id="divForm" style="display:none"></div>
  </div>
</div>
<?php include ROOTPATH . '/admin/templates/admin_footer_start.php'; ?>
<script type="text/javascript" src="js/vehicles.js?v=<?= filemtime(__DIR__ . "/js/vehicles.js") ?>"></script>
<?php include ROOTPATH . '/admin/templates/admin_footer_end.php'; ?>
