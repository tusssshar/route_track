<?php
include_once("../../../../bootstrap.php");
$manifest_id = $_REQUEST['manifest_id'];

$db_list2 = new database();
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
                    manifests M LEFT JOIN users U ON M.submitted_by_user_id=U.user_id WHERE M.manifest_id='" . $manifest_id . "'";

$db_list2->myCustomQuery($query);
?>

<table  class="table table-hover">

    <tbody>
        <?php
        $db_list2->moveto(0);
        ?>
        <tr>
            <td><b>Type</b><br><?= ucfirst($db_list2->f_manifest_type) ?></td>
            <td><b>Manifest</b><br><?= ucfirst($db_list2->f_manifest_id) ?></td>
            <td><b>Submitted By</b><br><?= $db_list2->f_first_name . " " . $db_list2->f_last_name ?></td>
            <td><b>Submit Date</b><br><?= ucfirst($db_list2->f_submit_date) ?></td>
            <td><b>No. of Areas</b><br><?= ucfirst($db_list2->f_num_areas) ?></td>
            <td><b>No. Of Routes</b><br><?= ucfirst($db_list2->f_num_routes) ?></td>
            <td><b>No. Of Deliveries</b><br><?= ucfirst($db_list2->f_num_deliveries) ?></td>
            <td><b>No. of Item</b><br><?= ucfirst($db_list2->f_num_items) ?></td>
            <td><b>No. of Packages</b><br><?= ucfirst($db_list2->f_num_packages) ?></td>
        </tr>
    </tbody>
</table>


<table  class="table table-hover">
    <tbody>

        <tr>
            <th>Delivery</th>
            <th>Customer Name:</th>
            <th>Account No</th>
            <th>Route</th>
            <th>Delivery Date</th>
            <th>Delivery Status</th>
            <th>Address</th>
            <th>P0 No.</th>
            <th>Order No.</th>
            <th>Delivered/Pickup</th>
            <th>Sign By:</th>
            <th>Signature</th>
            <th>Driver</th>
            <th>Geocode</th>
        </tr>
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
                D.latitude,
                D.longitude,
                D.exception_note,
                D.exception_reason,
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
                R.name,
                SP.server_filename
                    FROM
                    manifests M  JOIN deliveries D ON M.manifest_id=D.manifest_id
                    LEFT JOIN customers C ON D.customer_id=C.customer_id
                    LEFT JOIN routes R ON D.route_id=R.route_id
                    LEFT JOIN customer_addresses CA ON D.address_id=CA.address_id
                    LEFT JOIN users U ON D.driver_id=U.user_id
                    LEFT JOIN signature_pics SP ON D.signature_pic_id=SP.signature_pic_id
                    LEFT JOIN users SB ON M.submitted_by_user_id=SB.user_id WHERE D.manifest_id='" . $manifest_id . "'";
        $db_delivery->myCustomQuery($query);

        if ($db_delivery->row_count > 0) {

            for ($i = 0; $i < $db_delivery->row_count; $i++) {
                $db_delivery->moveto($i);
                ?>


                <tr id="tr_<?= $db_list->f_manifest_id ?>"  valign="top">



                    <td><?= $db_delivery->f_delivery_id ?></td>
                    <td><?= ucfirst($db_delivery->f_customer_name) ?></td>
                    <td><?= $db_delivery->f_account_number ?></td>
                    <td><?= ucfirst($db_delivery->f_name) ?></td>
                    <td><?= $db_delivery->f_scheduled_delivery ?></td>
                    <td><?= $db_delivery->f_status ?>
                        <?php
                            if($db_delivery->f_status=="Exception" ){
                                
                                 if($db_delivery->f_exception_reason!='')
                                    echo "<br><b>Reason :</b>".$db_delivery->f_exception_reason;
                                
                                if($db_delivery->f_exception_note!='')
                                    echo "<br><b>Note :</b>".$db_delivery->f_exception_note;
                            }
                        
                        ?>
                        
                    </td>



                    <td>
                        <?php
                        $full_address = "";
                        if ($db_delivery->f_address_line_1 != "") {
                            $full_address.= $db_delivery->f_address_line_1 . ",<br>";
                        }
                        if ($db_delivery->f_address_line_2 != "") {
                            $full_address.= $db_delivery->f_address_line_2 . ",<br>";
                        }
                        if ($db_delivery->f_address_line_3 != "") {
                            $full_address.= $db_delivery->f_address_line_3 . ",<br>";
                        }

                        if ($db_delivery->f_city != "") {
                            $full_address.= $db_delivery->f_city . ",";
                        }

                        if ($db_delivery->f_state != "") {
                            $full_address.= $db_delivery->f_state . ",";
                        }

                        if ($db_delivery->f_zip != "") {
                            $full_address.= $db_delivery->f_zip . "";
                        }
                        ?> 

                        <?= $full_address ?>
                    </td>
                    <td><?= $db_delivery->f_cust_po_num ?></td>
                    <td><?= $db_delivery->f_order_number ?></td>
                    <td><a href="javascript:void(0)"    data-toggle="modal" data-target="#myModal<?php echo $db_delivery->f_delivery_id ?>">Details</a><br>
        <?php
        $db_product_images = new database();
        $query_product_images = "SELECT product_pic_id,product_file_path FROM product_pics WHERE delivery_id='" . $db_delivery->f_delivery_id . "'";
        $db_product_images->myCustomQuery($query_product_images);
        if ($db_product_images->row_count > 0) {
            ?>
                            <a href="javascript:void(0)"    data-toggle="modal" data-target="#productModel<?php echo $db_delivery->f_delivery_id ?>">Images</a>

                        <?php } ?>
                        <!-- Order Details Modal -->
                        <div>
                            <div class="modal fade" id="myModal<?php echo $db_delivery->f_delivery_id ?>" role="dialog">
                                <div class="modal-dialog">

                                    <!-- Modal content-->
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                            <h4 class="modal-title"><?= $db_delivery->f_manifest_type ?>&nbsp;Details</h4>
                                        </div>
                                        <div class="modal-body" id="set_items">
                                            <p>
                                            <table  class="table table-hover">
                                                <tr>
                                                    <th>SKU</th>
                                                    <th>Qty</th>
                                                    <th>Name</th>
                                                    <th>Desc</th>
                                                    <th>UOM</th>
                                                    <th>Qty Delivered</th>
                                                </tr>
        <?php
        $db_item = new database();
        $query1 = "SELECT SQL_CALC_FOUND_ROWS DI.sku,DI.qty,DI.product_name,DI.description,DI.uom,DI.qty_delivered FROM deliveries D LEFT JOIN  delivery_items DI ON D.delivery_id=DI.delivery_id WHERE DI.delivery_id='" . $db_delivery->f_delivery_id . "'";
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
                                                            <td><?= $db_item->f_qty_delivered; ?></td>
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


                                            <table class="table table-hover">
                                                <tr>
                                                    <th>Package Type</th>
                                                    <th>Quantity</th>
                                                    <th>Qty Delivered</th>
                                                </tr>
        <?php
        $db_package = new database();
        $query1 = "SELECT SQL_CALC_FOUND_ROWS DP.qty,DP.qty_delivered,PT.name FROM deliveries D LEFT JOIN delivery_packages DP ON D.delivery_id=DP.delivery_id LEFT JOIN package_types PT ON DP.package_type_id=PT.package_type_id WHERE DP.delivery_id='" . $db_delivery->f_delivery_id . "'";
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
                                                            <td><?= $db_package->f_qty_delivered ?></td>
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


                                            </p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                        </div>
                                    </div>

                                </div>
                            </div>

                        </div>

                        <!-- Product Images Model-->

                        <div>
                            <div class="modal fade" id="productModel<?php echo $db_delivery->f_delivery_id ?>" role="dialog">
                                <div class="modal-dialog">

                                    <!-- Modal content-->
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                            <h4 class="modal-title">Product Images</h4>
                                        </div>
                                        <div class="modal-body" id="set_items">
                                            <p>





                                            <div class="container">

                                                <div class="row text-center text-lg-left">
        <?php
        $db_product_images = new database();
        $query_product_images = "SELECT product_pic_id,product_file_path FROM product_pics WHERE delivery_id='" . $db_delivery->f_delivery_id . "'";
        $db_product_images->myCustomQuery($query_product_images);
        if ($db_product_images->row_count > 0) {
            $t = 1;
            for ($j1 = 0; $j1 < $db_product_images->row_count; $j1++) {
                $db_product_images->moveto($j1);
                $file_path = SITEROOT . "/uploads/product_pics/" . $db_product_images->f_product_file_path;
                if ($db_product_images->f_product_file_path != "" || file_exists($file_path) == TRUE) {
                    if ($t % 3 == 0) {
                        ?>
                                                                    <div class="row text-center text-lg-left">
                                                                    <?php
                                                                }
                                                                ?>

                                                                    <div class="col-lg-2 col-md-4 col-xs-6">
                                                                        <a href="#" class="d-block mb-4 h-100">
                                                                            <img id ="myImg" class="img-fluid img-thumbnail" src="<?= $file_path ?>" alt="">
                                                                        </a>
                                                                    </div> 
                    <?php
                    if ($t % 3 == 0) {
                        ?>
                                                                    </div>
                                                                        <?php
                                                                    }
                                                                    ?>

                                                                <?php
                                                            }
                                                            $t++;
                                                        }
                                                        ?>

                                                        <?php
                                                    }
                                                    ?>



                                                </div>      
                                                </p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                            </div>
                        </div>



                    </td>
                    <td><?= $db_delivery->f_signature_text ?></td>
                    <td><?php
                                                    $file_path = SITEROOT . "/uploads/signature/" . $db_delivery->f_server_filename;
                                                    if ($db_delivery->f_server_filename != "" || file_exists($file_path) == TRUE) {
                                                        ?>
                            <img src="<?= $file_path ?>" style="width: 50px">
                        <?php } ?>
                    </td>
                    <td><?= ucfirst($db_delivery->f_first_name . " " . $db_delivery->f_last_name) ?></td>
                    <td><?= $db_delivery->f_latitude ?><br><?= $db_delivery->f_longitude ?></td>
                </tr>
        <?php
        if ($db_delivery->f_driver_notes != "" || $db_delivery->f_delivery_notes != "") {
            ?>
                    <tr>
                        <td colspan="100%" style="text-align: justify">

            <?php
            if ($db_delivery->f_delivery_notes != "") {
                echo "$add_br<b>Delivery Note:</b>" . ucfirst($db_delivery->f_delivery_notes);
            }
            if ($db_delivery->f_exception_note != "") {
                echo "<br>";
                if ($db_delivery->f_status == "Refused") {
                    echo "$add_br<b>Rejection Note:</b>" . ucfirst($db_delivery->f_exception_note);
                } else {
                    echo "$add_br<b>Exception Note:</b>" . ucfirst($db_delivery->f_exception_note);
                }
            }


            if ($db_delivery->f_driver_notes != "") {
                echo "<br>";
                echo"<b>Driver Note:</b>" . ucfirst($db_delivery->f_driver_notes);

                if ($db_delivery->f_delivery_notes != "") {
                    echo "<br>";
                }
            }
            ?>
                        </td>
                    </tr>

            <?php
        }
    }
} else {
    ?>
            <tr>
                <td align='center' colspan="100%" style="height:40px;">No record found</td>
            </tr>
    <?php
}
?>

        <tr><td colspan="100%" align="center"><a href="<?php echo SITEROOT . '/admin/m/master/manifests.php' ?>"><button type="button" class="btn btn-primary">Back</button></a>&nbsp;<a href="javascript:void(0);" onclick="javascript:ajaxNewModify(<?= $manifest_id; ?>);" title="Add Delivery"><button type="button" class="btn btn-primary">Edit</button></a></td></tr>

    </tbody>
</table>


