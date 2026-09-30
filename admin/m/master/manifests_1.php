<?php
session_start();
include_once("../../../bootstrap.php");
include(ROOTPATH . "/admin/authorized.php");
include_once(ROOTPATH . "/admin/templates/admin_header_start.php");
include_once(ROOTPATH . "/admin/templates/admin_header_end.php");

//Check Access Level
$res = $functions->user_access_level(7, $_SESSION["routetrac-admin"]["access_level"]);
if ($res == 0) {
    echo "<script>window.location.assign('../../../index.php')</script>";
}
$objGlobalConnection1 = new dbSqlConnection();
$oGlobalConnection = $objGlobalConnection1->Connect(HST, USR, PWD, DBN);
if (isset($_REQUEST["submit1"])) {


    // $_FILES['import_file_name']['name'];
    $sourcePath = $_FILES['import_file_name']['tmp_name'];
    $targetPath = ROOTPATH . "/admin/uploads/" . $_FILES['import_file_name']['name'];
    move_uploaded_file($sourcePath, $targetPath);

    $file = fopen($targetPath, "r");
    $manifest_id = $_SESSION['manifest_id'];
    $import_file_columns = array("Type", "Route", "Driver", "Stop_No", "Customer", "ship_to_name", "Address_Line_1", "Address_Line_2", "Address_Line_3", "City", "State", "ZIP", "Phone", "Status", "Order_No", "Po_No", "SKU", "Qty", "Name", "Desc", "UOM", "Package_Type", "Package_Qty");
    $file_columns = fgetcsv($file);


    //$result = array_diff($import_file_columns, $file_columns);
    //print_r($result);
    //echo "Array Size=".  sizeof($import_file_columns);
    //echo "File Column=".$file_columns;
    // exit();
    //print_r($file_columns);exit;
    $import_data_flag = "true";
    $boolean_var = $functions->check_csv_columns($import_file_columns, $file_columns);

    if ($boolean_var == "false") {
        $import_data_flag = "false";
        $_SESSION["routetrac-admin"]["error_success_msg"] = "<span style='color:red'>Columns not matched</span>";
    }
    $db_check_order_id = new database();
    fclose($file);
    $file = fopen($targetPath, "r");
    $incrmt_count = 0;
    $temp = 0;
    $type_delivery = 0;
    $type_pickup = 0;
    $type_array = array();
    if ($boolean_var == "true") {
        
        $type_check = fopen($targetPath, "r");
        
        while (!feof($type_check)) {
            $data_array = fgetcsv($type_check);
            $type_array[] = $data_array[0];
        }
        fclose($type_check);
        if (in_array('Delivery', $type_array) == TRUE && in_array('Pickup', $type_array) == TRUE) {
            $_SESSION["routetrac-admin"]["error_success_msg"] = "<span style='color:red'>You can not upload Delivery and Pickup in single file, please make it separate</span>";
            echo "<script>location.href='manifests.php';</script>";
            exit();
        }

        while (!feof($file)) {
            $data_array = fgetcsv($file);

            //print_r($data_array);
            if ($incrmt_count == 0) {
                
            } else {
                if (is_array($data_array)) {

                    $db_import_file = new database();
                    //Manifest table records
                    $type = mysqli_real_escape_string($db_import_file->conn, $data_array[0]);
                    $route = mysqli_real_escape_string($db_import_file->conn, $data_array[1]);
                    $driver = mysqli_real_escape_string($db_import_file->conn, $data_array[2]);
                    $stop_no = mysqli_real_escape_string($db_import_file->conn, $data_array[3]);
                    $customer = mysqli_real_escape_string($db_import_file->conn, $data_array[4]);
                    $ship_to_name = mysqli_real_escape_string($db_import_file->conn, $data_array[5]);
                    $address_Line_1 = mysqli_real_escape_string($db_import_file->conn, $data_array[6]);
                    $address_Line_2 = mysqli_real_escape_string($db_import_file->conn, $data_array[7]);
                    $address_Line_3 = mysqli_real_escape_string($db_import_file->conn, $data_array[8]);
                    $city = mysqli_real_escape_string($db_import_file->conn, $data_array[9]);
                    $state = mysqli_real_escape_string($db_import_file->conn, $data_array[10]);
                    $zip = mysqli_real_escape_string($db_import_file->conn, $data_array[11]);
                    $phone = mysqli_real_escape_string($db_import_file->conn, $data_array[12]);
                    $status = mysqli_real_escape_string($db_import_file->conn, $data_array[13]);
                    $order_no = mysqli_real_escape_string($db_import_file->conn, $data_array[14]);
                    $po_num = mysqli_real_escape_string($db_import_file->conn, $data_array[15]);

                    $sku = mysqli_real_escape_string($db_import_file->conn, $data_array[16]);
                    $item_qty = mysqli_real_escape_string($db_import_file->conn, $data_array[17]);
                    $item_name = mysqli_real_escape_string($db_import_file->conn, $data_array[18]);
                    $item_desc = mysqli_real_escape_string($db_import_file->conn, $data_array[19]);
                    $item_uom = mysqli_real_escape_string($db_import_file->conn, $data_array[20]);
                    $package_type = mysqli_real_escape_string($db_import_file->conn, $data_array[21]);
                    $package_qty = mysqli_real_escape_string($db_import_file->conn, $data_array[22]);


                    if ($route != "") {
                        $db_get_route_id = new database();
                        $db_get_route_id->selectstmt("routes");
                        $db_get_route_id->where("name=" . $route);
                        if ($db_get_route_id->row_count > 0) {
                            $route = $db_get_route_id->f_route_id;
                        } else {
                            $db_insert_route = new database();
                            $db_insert_route->data('name', $route);
                            $db_insert_route->insert("routes");
                            $route = $db_insert_route->id;
                        }
                    }

                    if ($driver != "") {

                        $driver = explode(" ", $driver);
                        $db_get_driver_id = new database();
                        $query_check_driver = "SELECT user_id FROM users WHERE first_name='" . $driver[0] . "' AND last_name='" . $driver[1] . "'";
                        $db_get_driver_id->myCustomQuery($query_check_driver);
                        if ($db_get_driver_id->row_count > 0) {
                            $db_get_driver_id->moveto(0);
                            $driver = $db_get_driver_id->f_user_id;
                        }
                    }

                    if ($customer != "") {

                        $db_get_customer_id = new database();
                        $query_check_customer = "SELECT customer_id FROM customers WHERE customer_name='" . $customer . "'";
                        $db_get_customer_id->myCustomQuery($query_check_customer);
                        if ($db_get_customer_id->row_count > 0) {
                            $customer = $db_get_customer_id->f_customer_id;


                            $db_get_addr_id = new database();
                            $query_get_addr_id = "SELECT address_id FROM customer_addresses WHERE address_line_1='" . $address_Line_1 . "' AND address_line_2='" . $address_Line_2 . "' AND address_line_3='" . $address_Line_3 . "' AND city='" . $city . "' AND state='" . $state . "' AND zip='" . $zip . "' AND customer_id='" . $customer . "'";

                            $db_get_addr_id->myCustomQuery($query_get_addr_id);
                            if ($db_get_addr_id->row_count > 0) {
                                $address_id = $db_get_addr_id->f_address_id;
                            }

                            /* $db_insert_customer = new database();
                              $db_insert_customer->data('customer_name', $customer);
                              $db_insert_customer->insert("routes");
                              $customer = $db_insert_customer->id;

                              $db_insert_cust_address = new database();
                              $db_insert_cust_address->data('manifest_id', $manifest_id);
                              $db_insert_cust_address->data('customer_id', $customer);
                              $db_insert_cust_address->data('ship_to_name', $customer);
                              $db_insert_cust_address->data('address_line_1', $address_line_1);
                              $db_insert_cust_address->data('address_line_2', $address_line_2);
                              $db_insert_cust_address->data('address_line_3', $address_line_3);
                              $db_insert_cust_address->data('city', $city);
                              $db_insert_cust_address->data('state', $state);
                              $db_insert_cust_address->data('zip', $zip);
                              $db_insert_cust_address->data('phone', $phone);
                              $db_insert_cust_address->insert("customer_addresses"); */
                        } else {
                            
                        }
                    }


                    if ($package_type != "") {

                        $db_get_package_type = new database();
                        $query_get_package_type = "SELECT package_type_id FROM package_types WHERE name='" . $package_type . "'";
                        $db_get_package_type->myCustomQuery($query_get_package_type);
                        if ($db_get_package_type->row_count > 0) {
                            $package_type_id = $db_get_package_type->f_package_type_id;
                        } else {
                            $package_type_id = 0;
                        }
                    }



                    $db_insert_item = new database();
                    $db_insert_item->data("sku", $sku);
                    $db_insert_item->data("qty", $item_qty);
                    $db_insert_item->data("product_name", $item_name);
                    $db_insert_item->data("description", $item_desc);
                    $db_insert_item->data("uom", $item_uom);


                    $db_insert_package = new database();
                    $db_insert_package->data("package_type_id", $package_type_id);
                    $db_insert_package->data("qty", $package_qty);



                    $db_insert_delivery = new database();
                    $db_insert_delivery->data("manifest_id", trim($manifest_id));
                    $db_insert_delivery->data("route_id", trim($route));
                    $db_insert_delivery->data("driver_id", trim($driver));
                    $db_insert_delivery->data("stop_number", trim($stop_no));
                    $db_insert_delivery->data("customer_id", trim($customer));
                    $db_insert_delivery->data("order_number", trim($order_no));
                    $db_insert_delivery->data("cust_po_num", trim($po_num));
                    $db_insert_delivery->data("status", 'In Transit');


                    //Check order id
                    $query_check_order_id = "SELECT delivery_id FROM deliveries WHERE order_number='" . $order_no . "'";
                    $db_check_order_id->myCustomQuery($query_check_order_id);
                    if ($db_check_order_id->row_count > 0) {
                        $db_check_order_id->moveto(0);
                        $delivery_id = $db_check_order_id->f_delivery_id;
                    } else {
                        $db_insert_delivery->insert("deliveries");
                        $delivery_id = $db_insert_delivery->id;
                    }

                    if ($delivery_id != "") {

                        if ($sku != "") {
                            $db_insert_item->data("delivery_id", $delivery_id);
                            $db_insert_item->insert("delivery_items");
                        }

                        if ($package_type_id != "") {
                            $db_check_duplication_package = new database();
                            $query_check_duplication_package = "SELECT packages_id FROM delivery_packages WHERE delivery_id='" . $delivery_id . "' AND package_type_id='" . $package_type_id . "'";
                            $db_check_duplication_package->myCustomQuery($query_check_duplication_package);
                            if ($db_check_duplication_package->row_count == 0) {
                                $db_insert_package->data("delivery_id", $delivery_id);
                                $db_insert_package->insert("delivery_packages");
                            }
                        } else {
                            
                        }
                    }


                    $_SESSION["routetrac-admin"]["error_success_msg"] = "File imported successfully";
                }
            }
            $incrmt_count++;
        }
    }
    //unset($_SESSION['manifest_id']);
    unlink($targetPath);
    echo "<script>location.href='manifests.php';</script>";
}
?>
<div class="container-fluid detail-page">
    <div class="page-header">
        <h4>
            <div class="clearfix">
                <div class="pull-left"> <a href="manifests.php"><span class="section-heading">Manifest(s)</span></a> </div>
                <div class="pull-right"> <small> <a href="<?php echo SITEROOT . '/admin/m/master/create_manifest.php' ?>"  class="" title="Add New Menifest"><i class="fa fa-plus"></i>&nbsp;&nbsp;New Manifest</a></small> </div>
                <!--  <div class="pull-right"> <small> <a href="ajax/manifests_new.php" class="" title="Add New Menifest"><i class="fa fa-plus"></i>&nbsp;&nbsp;New Menifest</a> </small> </div>-->
            </div>
        </h4>
    </div>
    <div class="row">
        <div class="col-md-12" id="divList">
            <div class="clearfix">
                <div class="row">
                    <div class="col-md-12">


                        <!-- <form class="form-inline" role="form" autocomplete="off" enctype="multipart/form-data" method="post"  name="form" id="form">
                           <div class="form-group">
                             <label class="sr-only" for="name">Name</label>
                             <input type="text" name="txtSearch" id="txtSearch" class="form-control" />
                           </div>
                           <input name="search" type="submit" id="search" value="  Search " class="btn btn-default" />
                           <div class="form-group"> <a href="javascript:void(0);" onclick="javascript:clearSearchValues();">Clear Search</a> </div>
                         </form>-->


                        <div class="row" id="import_id" style="display: none">
                            <div class="col-md-3"></div>
                            <div class="col-md-6">
                                <form action="" id="import_form"  class="form-inline" role="form" enctype="multipart/form-data" method="post"  name="import_form">
                                    <input type="file" class="form-control" name="import_file_name" id="import_file_name" style="padding: 0;width: 35%"/>
                                    <button type="submit" name="submit1" id="submit1" value=" Save" class="btn btn-success"><i class="fa fa-floppy-o"></i> Import </button>
                                                    <span style="color:#999999">or</span>&nbsp; <span class="cancellink"><a href="javascript:void(0);" onClick="javascript:import_cancel();">Cancel</a></span> <br>
                                    <br>
                                    <i class="fa fa-download"></i>&nbsp;&nbsp;<a href="<?php echo SITEROOT . '/admin/m/master/templates/MANIFEST.csv'; ?>" class="" download>Download Template</a>
                                </form>
                            </div>
                            <div class="col-md-3"></div>
                        </div>

                        <br>
                    </div>
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
include ROOTPATH . '/admin/templates/admin_footer_start.php';
?>
<!--jquery.blockUI-->
<script type="text/javascript" src="<?php echo SITEROOT; ?>/admin/templates/js/jquery/blockUI/jquery.blockUI.js"></script>
<script type="text/javascript" src="js/manifests.js"></script>
<?php
include ROOTPATH . '/admin/templates/admin_footer_end.php';
?>
<script>

                                        function openitem_popup(delivery_id) {

                                            loadingIndicator('start');

                                            $.ajax({
                                                url: SITEROOT + "/admin/ajax/admin_ajax_action.php?action=get_item_details",
                                                method: "POST",
                                                data: {delivery_id: delivery_id},
                                                dataType: "html"
                                            }).done(function (data) {
                                                $("#set_items").html(data);
                                                // $("#set_items").show();
                                                alert(data);
                                                loadingIndicator('end');
                                            }).fail(function (jqXHR, textStatus) {
                                                alert("Request failed: " + textStatus);
                                            });
                                        }
</script>
