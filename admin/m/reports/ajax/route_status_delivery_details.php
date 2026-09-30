<?php
include_once("../../../../bootstrap.php");
$delivery_id=$_REQUEST["delivery_id"];
?>

<div class="modal-body">   
    <div class="col-xl-12">
        <div class="panel panel-info">
            <div class="panel-heading">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h3 class="panel-title">Delivery Details</h3>
            </div>
            <div class="panel-body">
                <div class="row">
                    <?php
                    $db_delivery = new database();
                    $query = "SELECT SQL_CALC_FOUND_ROWS
                M.manifest_type,
                M.manifest_id,
                SB.first_name as mfirst_name,
                SB.last_name as mlast_name,
                D.cust_po_num,
                D.delivery_notes,
                D.driver_notes,
                D.scheduled_delivery,
                D.signature_text,
                D.delivery_id,
                D.status,
                D.order_number,
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
                D.stop_number,
                R.name
                    FROM
                    manifests M  JOIN deliveries D ON M.manifest_id=D.manifest_id
                    LEFT JOIN customers C ON D.customer_id=C.customer_id
                    LEFT JOIN routes R ON D.route_id=R.route_id
                    LEFT JOIN customer_addresses CA ON D.address_id=CA.address_id
                    LEFT JOIN users U ON D.driver_id=U.user_id
                    LEFT JOIN users SB ON M.submitted_by_user_id=SB.user_id WHERE D.delivery_id='" . $delivery_id . "'";
                    $db_delivery->myCustomQuery($query);

                    if ($db_delivery->row_count > 0) {
                        ?>
                        <div class=" col-md-4 col-lg-4 well"> 
                            <table class="table table-user-information">
                                <tbody>
                                    <tr>
                                        <td>Route:</td>
                                        <td><?= ucfirst($db_delivery->f_name) ?></td>
                                    </tr>
                                    <tr>
                                        <td>Address</td>
                                        <td><?=$db_delivery->f_address_line_1.",".$db_delivery->f_address_line_2.",".$db_delivery->f_city." ".$db_delivery->f_state." ".$db_delivery->f_zip?></td>
                                    </tr>
                                    <tr>
                                        <td>PO No.</td>
                                        <td><?=$db_delivery->f_cust_po_num?></td>
                                    </tr>
                                </tbody>
                            </table>

                        </div>
    <?php
}
?>
                    <div class=" col-md-4 col-lg-4 well"> 
                        <table class="table table-user-information">
                            <tbody>
                                <tr>
                                    <td>Driver:</td>
                                    <td><?=ucfirst($db_delivery->f_first_name." ".$db_delivery->f_last_name)?></td>
                                </tr>
                                <tr>
                                    <td>Stop Number</td>
                                    <td><?=ucfirst($db_delivery->f_first_name." ".$db_delivery->f_stop_number)?></td>
                                </tr>
                                <tr>
                                    <td>Delivery Notes</td>
                                    <td><?=ucfirst($db_delivery->f_delivery_notes)?></td>
                                </tr>
                            </tbody>
                        </table>

                    </div>
                    <div class=" col-md-4 col-lg-4 well"> 
                        <table class="table table-user-information">
                            <tbody>
                                <tr>
                                    <td>Customer:</td>
                                    <td><?=ucfirst($db_delivery->f_customer_name)?></td>
                                </tr>
                                <tr>
                                    <td>Order No</td>
                                    <td><?=$db_delivery->f_order_number?></td>
                                </tr>
                                <tr>
                                    <td>Delivery Date</td>
                                    <td><?=$db_delivery->f_scheduled_delivery?></td>
                                </tr>
                            </tbody>
                        </table>

                    </div>  
                </div>
            </div>
            <div class="panel-heading">
                <h3 class="panel-title">Items Details</h3>
            </div>  
            <div class="panel-body">
                <div class="row">
                    <div class=" col-md-4 col-lg-12"> 
                        <table  class="table table-hover">
                            <tr>
                            <th>SKU</th>
                            <th>Qty</th>
                            <th>Name</th>
                            <th>Desc</th>
                             <th>UOM</th>
                            </tr>
                            <?php
                            $db_item = new database();
                            $query1 = "SELECT SQL_CALC_FOUND_ROWS DI.sku,DI.qty,DI.product_name,DI.description,DI.uom FROM deliveries D LEFT JOIN  delivery_items DI ON D.delivery_id=DI.delivery_id WHERE DI.delivery_id='" . $delivery_id . "'";
                            $db_item->myCustomQuery($query1);

                            if ($db_item->row_count > 0) {

                                for ($i1 = 0; $i1 < $db_item->row_count; $i1++) {
                                    $db_item->moveto($i1);
                                    ?>
                                    <tr>
                                        <td><?= $db_item->f_sku; ?></td>
                                        <td><?= $db_item->f_qty; ?></td>
                                        <td><?= $db_item->f_product_name; ?></td>
                                        <td><?= $db_item->f_description; ?></td>
                                        <td><?= $db_item->f_uom; ?></td>
                                    </tr>

                                    <?php
                                }
                            } else {
                                ?>
                                <tr>
                                    <td align='center' colspan="100%" style="height:40px;">No Record found</td>
                                </tr>
                                <?php
                            }
                            ?>
                        </table>

                    </div>
                </div>
            </div>
            <div class="panel-heading">
                <h3 class="panel-title">Packages Details</h3>
            </div>  
            <div class="panel-body">
                <div class="row">
                    <div class=" col-md-4 col-lg-12"> 
                        <table  class="table table-hover">
                            <tr>
                                <th>Package Type</th>
                                <th>Quantity</th>
                            </tr>
                            <?php
                            $db_package = new database();
                            $query1 = "SELECT SQL_CALC_FOUND_ROWS DP.qty,PT.name FROM deliveries D LEFT JOIN delivery_packages DP ON D.delivery_id=DP.delivery_id LEFT JOIN package_types PT ON DP. 	package_type_id=PT.package_type_id WHERE DP.delivery_id='" . $db_delivery->f_delivery_id . "'";
                            $db_package->myCustomQuery($query1);

                            if ($db_package->row_count > 0) {

                                for ($i1 = 0; $i1 < $db_package->row_count; $i1++) {
                                    $db_package->moveto($i1);
                                    ?>

                                    <tr>
                                        <td>
                                            <?= $db_package->f_name ?>
                                        </td>
                                        <td><?= $db_package->f_qty ?></td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                ?>
                                <tr>
                                    <td align='center' colspan="100%" style="height:40px;">No record found</td>
                                </tr>
                                <?php
                            }
                            ?>
                        </table>

                    </div>
                </div>
            </div>  
        </div>
    </div>

</div>
<div class="modal-footer">
    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
</div>