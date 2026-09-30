<?php
include_once("../../../../bootstrap.php");

$delete_id = ($_REQUEST['delete_id'] ? $_REQUEST['delete_id'] : 0);

$txtSearch = ($_REQUEST['txtSearch'] ? $_REQUEST['txtSearch'] : '');

if ($delete_id > 0) {
    $db = new database();
    $db->where("manifest_id=" . $delete_id);
    $db->delete("manifests");

    $db1 = new database();
    $db1->where("manifest_id=" . $delete_id);
    $db1->delete("deliveries");
}

//pageing coading
$PAGE = ((empty($_GET['page'])) ? 1 : $_GET['page']);
$PAGE_LIMIT = 25;
$PAGE_LIMIT_VALUE = ($PAGE - 1) * $PAGE_LIMIT;

if (strlen(trim($txtSearch)) > 0) {
    $search_query = " AND (M.manifest_type LIKE '%" . $txtSearch . "%' OR D.manifest_id LIKE '%" . $txtSearch . "%'  OR D.order_number LIKE '%" . $txtSearch . "%' OR U.first_name  LIKE '%" . $txtSearch . "%' OR U.last_name  LIKE '%" . $txtSearch . "%')";
}

$db_list = new database();
$db_item = new database();
$query = "SELECT SQL_CALC_FOUND_ROWS
                M.manifest_type,
                M.manifest_id,
                M.submit_date,
                M.num_areas,
                M.num_routes,
                M.num_deliveries,
                M.num_packages,
                M.num_items,
                U.first_name,
                U.last_name
                
                    FROM
                    manifests M LEFT JOIN users U ON M.submitted_by_user_id=U.user_id
                    LEFT JOIN deliveries D ON M.manifest_id=D.manifest_id
WHERE 1=1  " . $search_query . " GROUP BY manifest_id
			 ORDER BY M.manifest_id DESC
			 LIMIT $PAGE_LIMIT_VALUE, $PAGE_LIMIT";

$db_list->myCustomQuery($query);

$PAGE_TOTAL_ROWS = $db_list->FOUND_ROWS();

$PAGE_URL = basename($_SERVER['PHP_SELF']) . "-txtSearch=" . $txtSearch;

$PAGING = $functions->showAjaxPaging("MyAjaxInterView");
?>
<script>
    var ajaxFilePath = "ajax/manifests.php";
    $(document).ready(function () {

        if (document.getElementById("txtSearch")) {
            $("#txtSearch").focus();
        }


        $("#form").submit(function () {

            loadingIndicator('start');
            $.post(ajaxFilePath, $(this).serializeArray(), function (data) {

                $("#MyAjaxInterView").html(data);

                loadingIndicator('end');
            }, "html");
            return false;
        });//search submit
    });//document ready


    //	clear all search values
    function clearSearchValues() {
        $("#txtSearch").val("");
        loadDefaultList();
    }
</script>

<div class="alert alert-success" id="successMsg" style="margin:10px;display:none">Success! Well done its submitted.</div>
Search by Manifest No, Submitted By, Order No.
<div class="row">
        
    <div class="col-md-12" style="padding:0 20px">
        <div class="pull-left">
            <form class="form-inline" role="form" autocomplete="off" enctype="multipart/form-data" method="post"  name="form" id="form">
                <div class="form-group">
                    <label class="sr-only" for="name">Name</label>
                    <input type="text" name="txtSearch" id="txtSearch" value="<?= $txtSearch; ?>" class="form-control"  placeholder=""/>
                   
                </div>
                <input name="search" type="submit" id="search" value="Search" class="btn btn-default" />
                
                <div class="form-group"> <a href="javascript:void(0);" onclick="javascript:clearSearchValues();">Clear Search</a> </div><br>
<!--                 Search by Manifest No, Submitted By, Order No.-->
            </form>
        </div>
        <div class="pull-right">
<?= $PAGING ?>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <table class="table table-hover table-custom-manifests">
            <thead>
                <tr>
                    <th class="text-center">#</th>
                    <th>Type</th>
                    <th>Manifest</th>
                    <th>Submitted By</th>
                    <th>Submit Date</th>
                    <th>No. of Areas</th>
                    <th>No. Of Routes</th>
                    <th>No. Of Deliveries</th>
                    <th>No. of Item</th>
                    <th>No. of Packages</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
<?php
$counter = 1;
if ($db_list->row_count > 0) {
    $counter = ($PAGE_LIMIT_VALUE + 1);
    for ($i = 0; $i < $db_list->row_count; $i++) {
        $db_list->moveto($i);
        ?>
                        <tr id="tr_<?= $db_list->f_manifest_id ?>" onclick="show_row(<?= $db_list->f_manifest_id ?>)" style="cursor: pointer">
                            <td class="text-center"><?= $counter ?></td>
                            <td><?php if ($db_list->f_manifest_type == "Delivery") { ?>
                                    <img src="<?php echo SITEROOT . '/admin/templates/images/icons/delivery.png' ?>">
            <?php
        } else {
            ?>
                                    <img src="<?php echo SITEROOT . '/admin/templates/images/icons/pickup.png' ?>">
                                    <?php
                                }
                                ?></td>
                            <td><?= ucfirst($db_list->f_manifest_id) ?></td>
                            <td><?= $db_list->f_first_name . "" . $db_list->f_last_name ?></td>
                            <td><?= $db_list->f_submit_date ?></td>
                            <td><?= $db_list->f_num_areas ?></td>
                            <td><?= $db_list->f_num_routes ?></td>
                            <td><?= $db_list->f_num_deliveries ?></td>
                            <td><?= $db_list->f_num_items ?></td>
                            <td><?= $db_list->f_num_packages ?></td>
                            <td class="text-center" onclick='event.stopPropagation();
                                    return false;'><a href="javascript:void(0);" onclick="javascript:ajaxNewModify(<?= $db_list->f_manifest_id; ?>);" title="Add Delivery"><i class="fa fa-plus"></i></a> &nbsp;&nbsp; <a href="javascript:void(0);" onclick="javascript:deleteGridRecord_manifest('<?= SITEROOT ?>/admin/m/master/ajax/manifests.php?delete_id=<?= $db_list->
                        f_manifest_id
                        ?>',<?= $db_list->f_manifest_id ?>);" title="Delete Manifest"><i class="fa fa-trash-o fa-size"></i></a> </td>
                        </tr>
                        <tr id="child_tr_<?= $db_list->f_manifest_id ?>" style="display: none;">
                            <td colspan="100%"><table width="100%">
                                                                                                                                                                                                                                                                             <?php ?>
                                    <tr style="line-height: 30px" valign="top">
                                        <td width="15%"> PO No. # :
                                    <?= $db_list->f_cust_po_num ?>
                                            <br>
                                            Delivery Note :
                                    <?= ucfirst($db_list->f_delivery_notes) ?>
                                            <br>
                                            Driver :
                                            <?= ucfirst($db_list->f_first_name . " " . $db_list->f_last_name) ?>
                                            <br>
                                            Driver Note :
                                            <?= ucfirst($db_list->f_driver_notes) ?>
                                            <br>
                                            Geocode : </td>
                                        <td width="15%">Address : <br>
        <?= $db_list->f_address_line_1 . "<br>" . $db_list->f_address_line_2 . "<br>" . $db_list->f_city . " " . $db_list->f_state . " " . $db_list->f_zip ?></td>
                                        <td width="25%">Products:<br>
                                        </td>
                                        <td width="15%">Delivered:
        <?= $db_list->f_address ?>
                                            <br>
                                            <a href="javascript:void(0)" onclick="get_manifest_details(<?= $db_list->f_manifest_id ?>)">Details</a> </td>
                                        <td width="15%">Signature:
        <?= $db_list->f_signature_text ?></td>
                                        <td width="15%">Photo:
                                            <?= $db_list->f_photo ?></td>
                                    </tr>
                                    <tr style="line-height: 30px" align="right">
                                        <td colspan="100%"><button type=button" class="btn btn-primary" onclick="manifest_email()">Email</button>
                                            <button class="btn btn-primary" onclick="print_manifest(<?= $db_list->f_manifest_id ?>)">Print</button>
                                            <div id="print_manifest_<?= $db_list->f_manifest_id ?>" style="display: none;font-size: 10%">
                                            <?php
                                            $db_list2 = new database();
                                            $query = "SELECT SQL_CALC_FOUND_ROWS
                M.manifest_type,
                M.manifest_id,
                D.cust_po_num,
                D.delivery_notes,
                D.driver_notes,
                D.scheduled_delivery,
                D.signature_text,
                D.delivery_id,
                D.status,
                C.customer_name,
                C.account_number,
                CA.address_line_1,
                CA.address_line_2,
                CA.address_line_3,
                CA.city,
                CA.state,
                CA.zip,
                CA.phone,
                U.first_name,
                U.last_name,
                R.name
                    FROM
                    manifests M LEFT JOIN deliveries D ON M.manifest_id=D.manifest_id
                    LEFT JOIN customers C ON D.customer_id=C.customer_id
                    LEFT JOIN routes R ON D.route_id=R.route_id
                    LEFT JOIN customer_addresses CA ON D.address_id=CA.address_id
                    LEFT JOIN users U ON D.driver_id=U.user_id
                    WHERE M.manifest_id='" . $db_list->f_manifest_id . "'";

                                            $db_list2->myCustomQuery($query);
                                            ?>
                                                <table  style="font-size: 50%">
                                                    <tbody>
                                                <?php
                                                $db_list2->moveto(0);
                                                ?>
                                                        <tr id="tr_<?= $db_list->f_manifest_id ?>" style="line-height: 40px;" valign="top">
                                                            <td><b>Type:</b><br>
        <?= ucfirst($db_list2->f_manifest_type) ?></td>
                                                            <td><b>Delivery:</b><br>
                                                        <?= $db_list2->f_delivery_id ?></td>
                                                            <td><b>Customer Name:</b><br>
                                                        <?= ucfirst($db_list2->f_customer_name) ?></td>
                                                            <td><b>Account:</b><br>
                                                        <?= $db_list2->f_account_number ?></td>
                                                            <td><b>Route:</b><br>
        <?= ucfirst($db_list2->f_name) ?></td>
                                                            <td><b>Delivery Date:</b><br>
                                                                <?= $db_list2->f_scheduled_delivery ?></td>
                                                            <td><b>Delivery Status:</b><br>
                                                                <?= $db_list2->f_status ?></td>
                                                            <td><b>Sign By:</b><br>
                                                                <?= $db_list2->f_signature_text ?></td>
                                                        </tr>
                                                        <tr style="line-height: 30px" valign="top">
                                                            <td><b>PO No. #</b><br>
                                                                <?= $db_list->f_cust_po_num ?>
                                                            </td>
                                                            <td><b>Address:</b><br>
                                                                <?= $db_list2->f_address_line_1 . "<br>" . $db_list2->f_address_line_2 . "<br>" . $db_list2->f_city . " " . $db_list2->f_state . " " . $db_list2->f_zip ?></td>
                                                            <td><b>Products:</b><br>
                                                                <?= $db_list2->f_address ?></td>
                                                            <td><b>Delivered:</b><br<?= $db_list2->f_address ?></td>
                                                            <td><b>Signature:</b><br<?= $db_list2->f_signature_text ?></td>
                                                            <td><b>Photo:</b><br>
                                                                <?= $db_list2->f_photo ?></td>
                                                            <td><b>Driver:</b><br>
        <?= ucfirst($db_list2->f_first_name . " " . $db_list2->f_last_name) ?></td>
                                                            <td><b>Driver Note:</b><br>
                                                                <?= ucfirst($db_list2->f_driver_notes) ?></td>
                                                        </tr>
                                                        <tr>
                                                            <td><b>Delivery Note:</b><br>
        <?= ucfirst($db_list2->f_delivery_notes) ?></td>
                                                            <td><b>Geocode:</b><br>
                                                                &nbsp;</td>
                                                            <td></td>
                                                            <td>&nbsp;</td>
                                                            <td>&nbsp;</td>
                                                            <td>&nbsp;</td>
                                                            <td>&nbsp;</td>
                                                            <td>&nbsp;</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div></td>
                                    </tr>
                                </table></td>
                        </tr>
        <?php
        $counter++;
    }
} else {
    ?>
                    <tr>
                        <td align='center' colspan="100%" style="height:40px;">No Manifest found</td>
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
                <?= $PAGING ?>
        </div>
    </div>
</div>
<script>
    function show_row(manifest_id) {
        //$('#tr_'+manifest_id).css("background-color","#");
        //$('#child_tr_'+manifest_id).css("background-color","#");
        //$('#child_tr_'+manifest_id).toggle();

        // $("#divForm").removeClass("col-md-0").addClass("col-md-12").show();
        //$("#divList").removeClass("col-md-8").addClass("col-md-12").hide();
        loadingIndicator('start');

        $.ajax({
            url: SITEROOT + "/admin/m/master/ajax/detail_report.php",
            method: "POST",
            data: {manifest_id: manifest_id},
            dataType: "html"
        }).done(function (data) {
            $("#MyAjaxInterView").html(data);
            loadingIndicator('end');
        }).fail(function (jqXHR, textStatus) {
            alert("Request failed: " + textStatus);
        });


    }
    function print_manifest(manifest_id) {
        // var prtContent = $('#print_manifest_'+manifest_id).text();
        var prtContent = document.getElementById("print_manifest_" + manifest_id);
        var WinPrint = window.open('', '_blank', 'left=0,top=0,width=600,height=500,toolbar=0,scrollbars=0,status=0,visible=none');
        WinPrint.document.write(prtContent.innerHTML);
        WinPrint.document.close();
        WinPrint.focus();
        WinPrint.print(false);
        WinPrint.close();
    }

    function get_manifest_details(manifest_id) {

        //$("#divList").removeClass("col-md-12").addClass("col-md-12");
        $("#divForm").removeClass("col-md-0").addClass("col-md-12").show();
        $("#divList").removeClass("col-md-8").addClass("col-md-12").hide();
        loadingIndicator('start');

        $.ajax({
            url: "detail_report.php",
            method: "POST",
            data: {manifest_id: manifest_id},
            dataType: "html"
        }).done(function (data) {
            $("#divForm").html(data);
            loadingIndicator('end');
        }).fail(function (jqXHR, textStatus) {
            alert("Request failed: " + textStatus);
        });
    }
    function manifest_email() {
        alert("Work in progress");
    }

    var ajaxFilePath = "ajax/manifests.php";

    $("#form").submit(function () {

        loadingIndicator('start');
        $.post(ajaxFilePath, $(this).serializeArray(), function (data) {

            $("#MyAjaxInterView").html(data);

            loadingIndicator('end');
        }, "html");
        return false;
    });//search submit



</script>
