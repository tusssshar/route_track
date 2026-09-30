<?php
include_once("../../../../bootstrap.php");
session_start();


/* * ***************Code for Item/Package make optional/required*************************** */
//Get client id for options check/uncheck
$db_get_client_id = new database();
$db_get_client_id->selectstmt("client_id");
$client_id = $db_get_client_id->f_client_id;

$objGlobalConnection = new dbSqlConnection();
$oGlobalConnection = $objGlobalConnection->Connect(HST_1, USR_1, PWD_1, DBN_1);


$db_get_client_id1 = new database();
$db_get_client_id1->selectstmt("clients");
$db_get_client_id1->where("client_id=" . $client_id);
$admin_require_packages = $db_get_client_id1->f_admin_require_packages;
$admin_require_items = $db_get_client_id1->f_admin_require_items;

/* * ***************Code for Item/Package make optional/required*************************** */

$label_package = "Optional";
if ($admin_require_packages == 1) {
    $label_package = "Mandatory";
}
$label_item = "Optional";
if ($admin_require_items == 1) {
    $label_item = "Mandatory";
}



$objGlobalConnection = new dbSqlConnection();
$oGlobalConnection = $objGlobalConnection->Connect(HST, USR, PWD, DBN);

function getLatLong($address) {
    if (!empty($address)) {
//Formatted address
        $formattedAddr = str_replace(' ', '+', $address);
//Send request and receive json data by address
        $geocodeFromAddr = file_get_contents
                ('http://maps.googleapis.com/maps/api/geocode/json?address=' . $formattedAddr . '&sensor=false');
        $output = json_decode($geocodeFromAddr);
//Get latitude and longitute from json data
        $data['latitude'] = $output->results[0]->geometry->location->lat;
        $data['longitude'] = $output->results[0]->geometry->location->lng;
//Return latitude and longitude of the given address
        if (!empty($data)) {
            return $data;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

function send_notification($tokens, $message) {

    $url = 'https://fcm.googleapis.com/fcm/send';
    // $url='https://android.googleapis.com/gcm/send';
    $fields = array(
        'registration_ids' => $tokens,
        'data' => $message
    );
    // echo json_encode($fields); exit();
    $headers = array(
        'Authorization:key = ' . FCM_SERVER_KEY,
        'Content-Type: application/json'
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
    $result = curl_exec($ch);
    if ($result === FALSE) {
        die('Curl failed: ' . curl_error($ch));
    }
    curl_close($ch);
    return $result;
}

$manifest_id = ($_REQUEST['manifest_id'] ? $_REQUEST['manifest_id'] : 0);
$_SESSION['manifest_id'] = $manifest_id;

$delivery_id = ($_REQUEST['delivery_id'] ? $_REQUEST['delivery_id'] : 0);
$delete_id = ($_REQUEST['delete_id'] ? $_REQUEST['delete_id'] : 0);

if ($delete_id > 0) {

    $db1 = new database();
    $db1->where("delivery_id=" . $delete_id);
    $db1->delete("deliveries");

    $db_delete_items = new database();
    $db_delete_items->where("delivery_id=" . $delete_id);
    $db_delete_items->delete("delivery_items");

    $db_delete_package = new database();
    $db_delete_package->where("delivery_id=" . $delete_id);
    $db_delete_package->delete("delivery_packages");
}

if (strlen(trim($_POST['submit'])) > 0) {

    $error = "";
    $success = 0;
    if (strlen(trim($_POST['route_id'])) < 1) {
        $error = "Please provide route name";
    }

    if (strlen($error) < 1) {

        $db = new database(); //for manifest table
        $db1 = new database(); //for delivery table
        $db2 = new database(); //for delivery item table
        $db3 = new database(); //for package delivery table
        $update_manifest_id = 0;

        $delivery_id = $_POST['hidden_delivery_id'];
        $manifest_id = $_POST['hidden_manifest_id'];

        //Manifest table
        $db->data("submitted_by_user_id", $_SESSION["routetrac-admin"]["al_id"]);

        //Deliveries table

        $db_get_address = new database();
        //$db_get_address->selectstmt("customer_addresses");
        // $db_get_address->where("address_id = ".$_POST['address_id']);




        $query_get_address = "SELECT * FROM customer_addresses WHERE address_id='" . $_POST['address_id'] . "'";
        $db_get_address->myCustomQuery($query_get_address);
        if ($db_get_address->row_count > 0) {
            $full_addr_array = array();
            if ($db_get_address->f_address_line_1 != "") {
                array_push($full_addr_array, $db_get_address->f_address_line_1);
            }
            if ($db_get_address->f_address_line_2 != "") {
                array_push($full_addr_array, $db_get_address->f_address_line_2);
            }
            if ($db_get_address->f_address_line_3 != "") {
                array_push($full_addr_array, $db_get_address->f_address_line_3);
            }

            if ($db_get_address->f_city != "") {
                array_push($full_addr_array, $db_get_address->f_city);
            }

            if ($db_get_address->f_state != "") {
                array_push($full_addr_array, $db_get_address->f_state);
            }

            if ($db_get_address->f_zip != "") {
                array_push($full_addr_array, $db_get_address->f_zip);
            }
            $address = "";
            if (!empty($full_addr_array)) {
                $address = implode(",", $full_addr_array);
            }
            // print_r($full_addr_array);
            // $address = $db_get_address->f_address_line_1 . "," . $db_get_address->f_address_line_2 . "," . $db_get_address->f_address_line_3 . "," . $db_get_address->f_city . "," . $db_get_address->f_state . "," . $db_get_address->f_zip; //.",".$db_get_address->f_state.",".$db_get_address->f_zip;
        }

        $latLong = getLatLong($address);
        $latitude = $latLong['latitude'];
        $longitude = $latLong['longitude'];



        $db1->data("route_id", trim($_POST['route_id']));
        $db1->data("driver_id", trim($_POST['driver_id']));
        $db1->data("stop_number", trim($_POST['stop_number']));
        $db1->data("customer_id", trim($_POST['customer_id']));
        $db1->data("address_id", trim($_POST['address_id']));
        $db1->data("status", "In Transit");
        $db1->data("order_number", trim($_POST['order_number']));
        $db1->data("cust_po_num", trim($_POST['cust_po_num']));
        $db1->data("delivery_notes", trim($_POST['delivery_notes']));
        $db1->data("scheduled_delivery", trim($_POST['scheduled_delivery']));
        $db1->data("delivery_time", trim($_POST['delivery_time']));
        $db1->data("stop_number", trim($_POST['stop_number']));
        $db1->data("latitude", $latitude);
        $db1->data("longitude", $longitude);




        //Delivery Items

        $unique_package_array = array_unique($_REQUEST['package_id']);

        if ($manifest_id < 1 && $delivery_id < 1) {

            $db->data("manifest_type", trim($_POST['manifest_type']));
            $db->insert("manifests");
            $obj = new functions();

            $manifest_last_id = $db->id;
            $update_manifest_id = $manifest_last_id;

            $db1->data("manifest_id", trim($manifest_last_id));
            $db1->insert("deliveries");
            $delivery_last_id = $db1->id;
            $i = 0;


            foreach ($unique_package_array as $package_id) {
                $db3->data("delivery_id", trim($delivery_last_id));
                $db3->data("package_type_id", trim($_REQUEST['package_id'][$i]));
                $db3->data("qty", trim($_REQUEST['package_qty'][$i]));
                if ($_REQUEST['package_id'][$i] != "" && $_REQUEST['package_qty'][$i] != "") {

                    $db3->insert("delivery_packages");
                }
                $i++;
            }
            $i = 0;


            foreach ($_REQUEST['sku'] as $skulist) {
                $db2->data("delivery_id", trim($delivery_last_id));
                $db2->data("sku", trim($_REQUEST['sku'][$i]));
                $db2->data("qty", trim($_REQUEST['qty'][$i]));
                $db2->data("product_name", trim($_REQUEST['product_name'][$i]));
                $db2->data("description", trim($_REQUEST['description'][$i]));
                $db2->data("uom", trim($_REQUEST['uom'][$i]));

                if ($_REQUEST['sku'][$i] != "") {
                    $db2->insert("delivery_items");
                }
                $i++;
            }
            $msg = "New " . $_POST['manifest_type'] . " successfully added to manifest";
            $success = 1;
        } else {

            if ($delivery_id > 0) {
                //Update delivery table
                if (strlen($_REQUEST['order_status'] > 0 && $_REQUEST['order_status'] != "")) {
                    $db1->data("status", $_REQUEST['order_status']);
                }
                $delivery_id = $_POST['temp_delivery_id'];
                $db1->where("delivery_id=" . $delivery_id);
                $db1->update("deliveries");

                //Delete packages

                if (sizeof($_REQUEST['packages_id']) > 0) {
                    $str = implode(",", $_REQUEST['packages_id']);
                    $db_delete_package = new database();
                    $db_delete_package->where("delivery_id=" . $delivery_id . " AND packages_id NOT IN($str)");
                    $db_delete_package->delete("delivery_packages");
                }//package delete if end

                $i = 0;
                foreach ($unique_package_array as $package_id) {
                    $db3->data("package_type_id", trim($_REQUEST['package_id'][$i]));
                    $db3->data("qty", trim($_REQUEST['package_qty'][$i]));

                    if ($_REQUEST['packages_id'][$i] != 0) {
                        if ($_REQUEST['package_id'][$i] != "") {
                            $db3->where("packages_id=" . $_REQUEST['packages_id'][$i]);
                            $db3->update("delivery_packages");
                        }
                    } else {

                        if ($_REQUEST['package_id'][$i] != "" && $_REQUEST['package_qty'][$i] != "") {
                            $db3->data("delivery_id", $delivery_id);
                            $db3->data("delivery_id", trim($delivery_id));
                            $db3->insert("delivery_packages");
                        }//package if end
                    }

                    $i++;
                }//package for end


                if (sizeof($_REQUEST['sku']) > 0) {
                    $item_ids = implode(",", $_REQUEST['item_id']);
                    $db_delete_item = new database();
                    $db_delete_item->where("delivery_id=" . $delivery_id . " AND item_id NOT IN($item_ids)");
                    $db_delete_item->delete("delivery_items");
                }//item delete if end

                $i = 0;

                foreach ($_REQUEST['sku'] as $skulist) {

                    $db2->data("sku", trim($_REQUEST['sku'][$i]));
                    $db2->data("qty", trim($_REQUEST['qty'][$i]));
                    $db2->data("product_name", trim($_REQUEST['product_name'][$i]));
                    $db2->data("description", trim($_REQUEST['description'][$i]));
                    $db2->data("uom", trim($_REQUEST['uom'][$i]));
                    if ($_REQUEST['item_id'][$i] != 0) {
                        $db2->where("item_id=" . $_REQUEST['item_id'][$i]);
                        $db2->update("delivery_items");
                    } else {

                        if ($_REQUEST['sku'][$i] != "") {
                            $db2->data("delivery_id", trim($delivery_id));
                            $db2->insert("delivery_items");
                        }
                    }

                    $i++;
                }//items foreach end
                $msg = $_POST['manifest_type'] . " successfully updated";
                $success = 1;

                if ($_POST['driver_id'] != "") {
                    // send push notification
                    $tokens = array();
                    $db_get_user_token = new database();
                    $query_get_user_token = "SELECT imei_no  FROM users WHERE user_id='" . $_POST['driver_id'] . "'";
                    $db_get_user_token->myCustomQuery($query_get_user_token);
                    $user_token = $db_get_user_token->f_imei_no;
                    $message = "Order " . $_POST['order_number'] . " is updated please check";
                    $message = array('message' => $message);
                    array_push($tokens, $user_token);
                    $message_status = send_notification($tokens, $message);
                }
            } else {

                //insert new record in delivery table if manifest exists
                $obj = new functions();
                $db1->data("manifest_id", trim($manifest_id));
                $db1->insert("deliveries");
                $delivery_last_id = $db1->id;
                $i = 0;

                foreach ($unique_package_array as $package_id) {
                    $db3->data("delivery_id", trim($delivery_last_id));
                    $db3->data("package_type_id", trim($_REQUEST['package_id'][$i]));
                    $db3->data("qty", trim($_REQUEST['package_qty'][$i]));
                    if ($_REQUEST['package_id'][$i] != "" && $_REQUEST['package_qty'][$i] != "") {

                        $db3->insert("delivery_packages");
                    }
                    $i++;
                }
                $i = 0;

                foreach ($_REQUEST['sku'] as $skulist) {
                    $db2->data("delivery_id", trim($delivery_last_id));
                    $db2->data("sku", trim($_REQUEST['sku'][$i]));
                    $db2->data("qty", trim($_REQUEST['qty'][$i]));
                    $db2->data("product_name", trim($_REQUEST['product_name'][$i]));
                    $db2->data("description", trim($_REQUEST['description'][$i]));
                    $db2->data("uom", trim($_REQUEST['uom'][$i]));

                    if ($_REQUEST['sku'][$i] != "") {
                        $db2->insert("delivery_items");
                    }
                    $i++;
                }
                $msg = "New " . $_POST['manifest_type'] . " successfully added to manifest";
                $success = 1;
            }
        }

        if ($update_manifest_id != 0) {
            $manifest_id = $update_manifest_id;
        }

        //Calculate delivery and routes
        $db_update_manifest_data = new database();
        $update_delivery_routes = "SELECT count(D.delivery_id) AS NO_OF_DELIVERY,count(DISTINCT D.route_id) AS NO_OF_ROUTES FROM deliveries D WHERE D.manifest_id='" . $manifest_id . "'";
        $db_update_manifest_data->myCustomQuery($update_delivery_routes);
        if ($db_update_manifest_data->row_count > 0) {

            $db_update_manifest_data->moveto(0);
            $no_of_deliveries = $db_update_manifest_data->f_NO_OF_DELIVERY;
            $no_of_routes = $db_update_manifest_data->f_NO_OF_ROUTES;
        }
        //Calcaulate package count
        $db_update_packages = new database();
        $update_packages = "SELECT SUM(DP.qty) AS NO_OF_PACKAGES from manifests M LEFT JOIN deliveries D ON M.manifest_id=D.manifest_id LEFT JOIN delivery_packages DP ON D.delivery_id=DP.delivery_id WHERE D.manifest_id='" . $manifest_id . "'";
        $db_update_packages->myCustomQuery($update_packages);
        if ($db_update_packages->row_count > 0) {
            $db_update_packages->moveto(0);

            $no_of_packages = $db_update_packages->f_NO_OF_PACKAGES;
        }
        //Calculate item count
        $db_update_item = new database();
        $update_item = "SELECT SUM(DI.qty) AS NO_OF_ITEMS FROM manifests M LEFT JOIN deliveries D ON M.manifest_id=D.manifest_id LEFT JOIN delivery_items DI ON D.delivery_id=DI.delivery_id WHERE D.manifest_id='" . $manifest_id . "'";
        $db_update_item->myCustomQuery($update_item);
        if ($db_update_item->row_count > 0) {
            $db_update_item->moveto(0);

            $no_of_items = $db_update_item->f_NO_OF_ITEMS;
        }

        //Calculate area count
        $db_update_area = new database();
        $update_area = "SELECT count(distinct R.area_id) AS NO_OF_AREA 
                         FROM deliveries D INNER JOIN routes R on R.route_id = D.route_id WHERE  D.manifest_id='" . $manifest_id . "'";
        $db_update_area->myCustomQuery($update_area);
        if ($db_update_area->row_count > 0) {
            $db_update_area->moveto(0);
            $no_of_area = $db_update_area->f_NO_OF_AREA;
        }


        //Upadate counts
        $db_update_data = new database();
        $db_update_data->data('num_deliveries', $no_of_deliveries);
        $db_update_data->data('num_routes', $no_of_routes);
        $db_update_data->data('num_packages', $no_of_packages);
        $db_update_data->data('num_items', $no_of_items);
        $db_update_data->data('num_areas', $no_of_area);

        $db_update_data->where("manifest_id=" . $manifest_id);
        $db_update_data->update("manifests");


        $array = array("success" => $success, "error" => $error, "msg" => $msg, "manifest_id" => $update_manifest_id);
        echo json_encode($array);
        exit;
    }
}
$show_type = "Create Delivery";
if ($delivery_id > 0) {
    $db = new database();
    $sql = "SELECT * FROM deliveries  WHERE delivery_id='" . $delivery_id . "'";
    $db->myCustomQuery($sql);

    $item_delivery_id = $db->f_delivery_id;

    /* if($show_manifest_type=="Delivery"){
      $show_type="Update Delivery";
      $btn_name="Update Delivery";
      }else{
      $show_type="Update Pick Up";
      $btn_name="Update Pickup";
      } */
    $db1 = new database();
    $sql1 = "SELECT * FROM delivery_items  WHERE delivery_id='" . $item_delivery_id . "'";
    $res = $db1->myCustomQuery($sql1);

    $db2 = new database();
    $sql2 = "SELECT * FROM delivery_packages  WHERE delivery_id='" . $item_delivery_id . "'";
    $res2 = $db2->myCustomQuery($sql2);

    $flag = 1;
}
$show_type = "Delivery";
//echo $update_manifest_id;
?>
<script>
    jQuery(document).ready(function () {
        $("#route_id").focus();
        // validate signup form on keyup and submit
        jQuery("#meAdminForm").validate({
            errorElement: 'div',
            rules: {
                route_id: {
                    required: true
                }/*,
                 driver_id :{
                 required : true
                 }*/,
                customer_id: {
                    required: true
                },
                order_number: {
                    required: true
                },
                address_id: {
                    required: true
                },
                scheduled_delivery: {
                    //required: false
                },
                order_status: {
                    required: true
                }

            },
            messages: {
                route_id: {
                    required: "Please select route"
                }/*,
                 driver_id :{
                 required :"Please select driver"
                 }*/,
                customer_id: {
                    required: "Please select customer"
                },
                order_number: {
                    required: "Please enter order number"
                },
                address_id: {
                    required: "Please select address"
                },
                scheduled_delivery: {
                    //required: "Please select schedule date"
                },
                order_status: {
                    required: "Please select status"
                }

            },
            // set this class to error-labels to indicate valid fields
            success: function (label) {
                // set &nbsp; as text for IE
                label.hide();
            },
            submitHandler: function (form) {
                var k = $("#meAdminForm").serializeArray();
<?php if ($delivery_id > 0) { ?>
                    k.push({
                        name: "delivery_id",
                        value: <?= $delivery_id ?>

                    });

<?php } ?>
                k = jQuery.param(k);

                jQuery("#divAlertSuccess, #divAlertError").hide();
                loadingIndicator('start');
                $("#submit").prop("disabled", true);
                $.post("ajax/manifests_new.php", k, function (data) {
                    loadingIndicator('end');
                    if (data.success == 1) {
                        //alert("response--".data.manifest_id);  
                        var hidden_delivery_id = $("#hidden_delivery_id").val();

                        var hidden_manifest_id = $("#hidden_manifest_id").val();
                        if (hidden_manifest_id == "" || hidden_manifest_id == "0")
                        {
                            hidden_manifest_id = data.manifest_id;
                        }

                        if (hidden_manifest_id > 0) {

                            ajaxNewModify(hidden_manifest_id);

                            // show_deliveries_table(SITEROOT+'/admin/m/master/ajax/manifests_new.php?manifest_id='+hidden_manifest_id);

                        }

                        if (hidden_delivery_id <= 0) {
                            $("#divAlertSuccess").html(data.msg).show();
                            $("#route_id").focus();
                            $("#route_id").val("");
                            $("#driver_id").val("");
                            $("#customer_id").val("");
                            $("#address_id").val("");
                            $("#sku").val("");
                            $("#qty").val("");
                            $("#package_qty").val("");
                            $("#package_id").val("");
                            $("#stop_number").val("");
                            $("#order_number").val("");
                            $("#cust_po_num").val("");
                            $("#delivery_notes").val("");
                            $("#product_name").val("");
                            $("#description").val("");
                            $("#uom").val("");

                            //$("#scheduled_delivery").val("");

                            // ajaxNewModifyFormCancel();
                            $("#submit").prop("disabled", false);
                        } else {
                            $("#divAlertSuccess").html(data.msg).show();

                            // ajaxNewModifyFormCancel();
                        }
                    } else {
                        $("#divAlertError").html(data.error).show();
                    }
                    var k = setTimeout(function () {
                        jQuery("#divAlertSuccess, #divAlertError").hide();
                    }, 10000);

                }, "json");
            }
        });

    });


</script>
<style>
    .panel-heading{

        height: 50px
    }
</style>

<div class="alert alert-success" id="divAlertSuccess" style="display:none">Success! Well done its submitted.</div>
<div class="alert alert-danger"  id="divAlertError" style="display:none">Error ! Change few things.</div>
<div class="alert alert-success" id="successMsg" style="margin:10px;display:none">Success! Well done its submitted.</div>
<div class="panel panel-info" id="delivery_table_id">

    <div class="panel-heading">

        <label style="padding-top: 8px"><?= ($delivery_id > 0 ? "Update" : "Create") ?>
            Manifest </label>
        <?php if ($manifest_id != 0) { ?>
            <span style="float: right">
                <i class="fa fa-file"></i>&nbsp;&nbsp;<a href="javascript:void(0);" onclick="import_file()">Import</a> &nbsp;&nbsp;<i class="fa fa-download"></i>&nbsp;&nbsp;<a href="<?php echo SITEROOT . '/admin/m/master/export.php?value=export_manifest'; ?>" class="" title="">Export</a> 
            </span>
            <?php
        }
        if ($manifest_id == 0 && $delivery_id == 0) {
            ?>
            <div class="btn-group" style="float: right">
                <button type="button" class="btn btn-primary" onclick="show_delivery()" value="Delivery" name="delivety" id="btn_delivery" style="background-color:#286090">Delivery</button>
                <button type="button" class="btn btn-primary" onclick="show_pickup()" id="btn_pickup" value="Pickup">Pick Up</button>
            </div>
        <?php } ?>
    </div>
    <br>
    <!--Deliveries or pick list table  -->

    <table class="table table-hover" >
        <thead>
            <tr>
                <th class="text-center">#</th>
                <th>Type</th>
                <th>Delivery</th>
                <th>Route</th>
                <th>Driver</th>
                <th>Stop Number</th>
                <th>Customer</th>
                <th>Address</th>
                <th>Status</th>
                <th>Exception Note</th>
                <th>Order No.</th>
                <th>PO No.</th>
                <th class="text-center">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $db_deliveries = new database();
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
                D.stop_number,
                D.order_number,
                D.exception_note,
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
                    LEFT JOIN users SB ON M.submitted_by_user_id=SB.user_id WHERE D.manifest_id='" . $manifest_id . "' AND D.status IN('In Transit','Exception')";
            $db_deliveries->myCustomQuery($query);
            if ($db_deliveries->row_count > 0) {
                for ($i = 0; $i < $db_deliveries->row_count; $i++) {
                    $db_deliveries->moveto($i);
                    $show_type = $db_deliveries->f_manifest_type;
                    $show_open_order = "";
                    if ($delivery_id == $db_deliveries->f_delivery_id) {
                        $show_open_order = "style='background: #999999'";
                    }
                    ?>



                    <tr <?php echo $show_open_order ?>>
                        <td></td>
                        <td><?= $db_deliveries->f_manifest_type ?></td>
                        <td><?= $db_deliveries->f_delivery_id ?></td>
                        <td><?= $db_deliveries->f_name ?></td>
                        <td><?= $db_deliveries->f_first_name . " " . $db_deliveries->f_last_name ?></td>
                        <td><?= $db_deliveries->f_stop_number ?></td>
                        <td><?= $db_deliveries->f_customer_name ?></td>
                        <td><?= $db_deliveries->f_address_line_1 . " " . $db_deliveries->f_address_line_2 . " " . $db_deliveries->f_address_line_3 . " " . $db_deliveries->f_city . " " . $db_deliveries->f_state . " " . $db_deliveries->f_zip ?></td>
                        <td><?= $db_deliveries->f_status ?></td>
                        <td><?= nl2br($db_deliveries->f_exception_note) ?></td>
                        <td><?= $db_deliveries->f_order_number ?></td>
                        <td><?= $db_deliveries->f_cust_po_num ?></td>
                        <td class="text-center" onclick='event.stopPropagation();
                                return false;'>
                                <?php
                                if ($db_deliveries->f_status == "In Transit" || $db_deliveries->f_status == "Exception") {
                                    ?>
                                <a href="javascript:void(0);" onclick="javascript:ajax_edit_deliveries(<?= $db_deliveries->f_delivery_id; ?>,<?= $db_deliveries->f_manifest_id ?>);" title="Edit"><i class="fa fa-pencil-square-o fa-size"></i></a>
                                &nbsp;&nbsp;
                                <a href="javascript:void(0);" onclick="javascript:deleteGridRecord_delivery('<?= SITEROOT ?>/admin/m/master/ajax/manifests_new.php?action=get_delivery_table&delete_id=<?= $db_deliveries->f_delivery_id ?>&manifest_id=<?= $db_deliveries->f_manifest_id ?>&maifest_type=<?= $db_deliveries->f_manifest_type ?>', '<?= $db_list->f_delivery_id ?>');" title="Delete"><i class="fa fa-trash-o fa-size"></i></a>
                            <?php } ?>
                        </td>
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
        </tbody>
    </table>

    <div class="panel-body"  id="delivery">
        <form class="form-horizontal" role="form" name="meAdminForm" id="meAdminForm" action="" method="post">


            <?php
            if ($show_type == "Delivery") {
                $show_type_label = "Create";
                $icon_name = 'delivery.png';
            } else {
                $show_type_label = "Enter a";
                $icon_name = 'pickup.png';
            }
            ?>
            <img id="delivery_pickup_id" src="<?php echo SITEROOT . '/admin/templates/images/icons/' . $icon_name ?>" style="padding-left: 10px">&nbsp;



            <span style="font-size: 20px" id="lbl_id"> <?php echo $show_type_label . "&nbsp" . $show_type ?></span>

<!--                  <span style="float: right">  <a href="javascript:void(0);" onclick="clear_form_field" class="" title="New Delivery"><i class="fa fa-plus"></i>&nbsp;&nbsp;New Delivery</a></span>-->

            <input type="hidden" value="<?php echo $item_delivery_id ?>" name="temp_delivery_id">
            <input type="hidden" name="manifest_type" value="Delivery" id="manifest_type"> 
            <input type="hidden" value="<?php echo $delivery_id ?>" name="hidden_delivery_id" id="hidden_delivery_id">
            <input type="hidden" value="<?php echo $manifest_id ?>" name="hidden_manifest_id" id="hidden_manifest_id">
            <div class="container">
                <div class="row">
                    <div class="col-sm-3">

                        <label for="route_id" class="col-sm-0 control-label"><span class="error">*</span>Select Route</label>
                        <select name="route_id" id="route_id" class="form-control">
                            <option value="">Select one</option>

                            <?= $functions->createCombo("routes", "route_id", "name", $db->f_route_id, " order by name asc"); ?>
                        </select>
                    </div>
                    <div class="col-sm-3">
                        <label for="route_id" class="col-sm-0 control-label">Select Driver</label>
                        <select name="driver_id" id="driver_id" class="form-control">
                            <option value="">Select one</option>

                            <?= $functions->createCombo("users", "user_id", "first_name", $db->f_driver_id, "order by first_name asc", "is_driver=1"); ?>
                        </select>
                    </div>
                    <div class="col-sm-3">
                        <label for="route_id" class="col-sm-0 control-label"><span class="error">*</span>Select Customer</label>
                        <select name="customer_id" id="customer_id"  onchange="get_address()" class="form-control">
                            <option value="">Select one</option>

                            <?= $functions->createCombo("customers", "customer_id", "customer_name", $db->f_customer_id, " order by customer_name asc"); ?>
                        </select>
                    </div>
                    <div class="col-sm-3">
                        <label for="route_id" class="col-sm-0 control-label"><span class="error">*</span>Select Address</label>
                        <?php
                        $addr = "address_line_1,address_line_2,address_line_3";
                        ?>
                        <select name="address_id" id="address_id" class="form-control">
                            <option value="">Select one</option>
                            <?php if ($delivery_id > 0) { ?>
                                <?= $functions->createCombo("customer_addresses", "address_id", $addr, $db->f_address_id, " order by address_line_1 asc", "customer_id=" . $db->f_customer_id);
                                ?>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <br>
                <div class="row">

                    <div class="col-sm-3">

                        <label for="stop_nmuber" class="col-sm-0 control-label">Stop Number</label>
                        <input type="text" value="<?= $db->f_stop_number ?>" id="stop_number" name="stop_number" class="form-control"/>
                    </div>
                    <div class="col-sm-3">
                        <label for="order_no" class="col-sm-0 control-label">Order No</label>
                        <span class="error">*</span><input type="text"  value="<?= $db->f_order_number ?>" id="order_number" name="order_number" class="form-control"/>
                    </div>
                    <div class="col-sm-3">
                        <label for="po_no" class="col-sm-0 control-label">PO No.</label>
                        <input type="text"  value="<?= $db->f_cust_po_num ?>" id="cust_po_num" name="cust_po_num" class="form-control"/>
                    </div>
                    <div class="col-sm-3">
                        <label for="delivery_note" class="col-sm-0 control-label">Delivery Notes</label>
                        <input  value="<?= $db->f_delivery_notes ?>" id="delivery_notes" name="delivery_notes" class="form-control"/>
                    </div>

                </div>
                <br>
                <div class="row">



                    <?php
                    if ($delivery_id < 1) {
                        $delivery_date = date('Y-m-d');
                    } else {
                        $delivery_date = $db->f_scheduled_delivery;
                    }
                    ?>
                    <div class="col-sm-3">
                        <label for="schedule_date" class="col-sm-0 control-label">Delivery Date</label>
                        <input type="text" value="<?= $delivery_date ?>" id="scheduled_delivery" name="scheduled_delivery" class="form-control"/>
                    </div>
                    <?php if ($db->f_status == "Exception") { ?>
                        <div class="col-sm-3">
                            <label for="order_status" class="col-sm-0 control-label">
                                <span class="error">*</span>Change Status</label>
                            <select name="order_status" id="order_status" class="form-control">
                                <option value="">Select Status
                                <option value="In Transit">In Transit</option>
                            </select>
                        </div>
                    <?php } ?>
                </div>


                <br>

                <fieldset>
                    <legend>Enter Package <span id="lable_package_id">(<?=$label_package?>)</span></legend>
                    <?php
                    if ($delivery_id <= 0) {
                        ?>
                        <div class="add_package_class">
                            <div class="row">
                                <div class="col-sm-3">
                                    <label for="package_name" class="col-sm-0 control-label">Package Type</label>
                                    <select name="package_id[]" id="package_id" class="form-control" onchange="check_duplicate_pack_id(this.value)">
                                        <option value="">Select one</option>

                                        <?= $functions->createCombo("package_types", "package_type_id", "name", "", " order by name asc"); ?>
                                    </select>
                                </div>
                                <div class="col-sm-3">
                                    <label for="package_qty" class="col-sm-0 control-label">Quantity</label>
                                    <input  id="package_qty" name="package_qty[]" value=""  class="form-control" maxlength="5"/>
                                    <span class="error_qty_pakage" style="color: #ff0000;"></span>
                                </div>

                                <div class="col-sm-3">
                                    <label class="col-sm-0 control-label">&nbsp;</label>
                                    <button class="add_package_button form-control btn-info">Add More Packages</button>
                                </div>

                            </div>
                        </div>
                        <?php
                    } else {
                        ?>

                        <div class="row">
                            <div class="col-sm-3">
                                <label for="package_name" class="col-sm-0 control-label">Package Type</label>

                            </div>
                            <div class="col-sm-3">
                                <label for="package_qty" class="col-sm-0 control-label">Quantity</label>

                            </div>
                            <div class="col-sm-3">
                                <button class="add_package_button form-control btn-info">Add More Packages</button>    
                            </div>

                        </div>

                        <div class="add_package_class">

                            <?php
                            if ($db2->row_count > 0) {
                                $j = $db2->row_count - 1;
                                for ($i = 0; $i < $db2->row_count; $i++) {
                                    $db2->moveto($i);
                                    ?>


                                    <div class="row">
                                        <div class="col-sm-3">

                                            <select name="package_id[]" id="package_id" class="form-control">
                                                <option value="">Select one</option>
                                                <?= $functions->createCombo("package_types", "package_type_id", "name", $db2->f_package_type_id, " order by name asc"); ?>
                                            </select>
                                        </div>
                                        <div class="col-sm-3">
                                            <input  value="<?= $db2->f_qty ?>" id="package_qty" name="package_qty[]" class="form-control" maxlength="5"/>
                                        </div>
                                        <div class="col-sm-3">

                                            <a href='javascript:void(0)' class='remove_package'>Remove</a>

                                        </div>

                                        <input type="hidden" value="<?= $db2->f_packages_id ?>" id="packages_id" name="packages_id[]"/>
                                    </div>
                                    <?php
                                    if ($i < $j) {
                                        ?>
                                        <br>
                                    <?php } ?>    
                                    <?php
                                }
                            }
                            ?>
                        </div>



                        <?php
                    }
                    ?>
                </fieldset>
                <br>
                <!--Item Details-->
                <fieldset>
                    <legend>Enter Items <span id="lable_item_id">(<?=$label_item?>)</span>
                       
                    </legend>   
                    <?php
                    if ($delivery_id <= 0) {
                        ?>
                        <div class="input_fields_wrap">
                            <div class="row">
                                <div class="col-sm-2">
                                    <label for="sku" class="col-sm-0 control-label">SKU</label>      
                                    <input type="text" value="" id="sku" name="sku[]" class="form-control">
                                </div>
                                <div class="col-sm-2">
                                    <label for="quantity" class="col-sm-0 control-label">Quantity</label>
                                    <input type="text" value="" id="qty" name="qty[]" class="form-control" maxlength="5"/>
                                </div>
                                <div class="col-sm-2">
                                    <label for="product_name" class="col-sm-0 control-label">Product Name</label>
                                    <input type="text" value="" id="product_name" name="product_name[]" class="form-control"/>
                                </div>

                                <div class="col-sm-2">
                                    <label for="description" class="col-sm-0 control-label">Description</label>
                                    <input type="text" value="" id="description" name="description[]" class="form-control"/>
                                </div>
                                <div class="col-sm-2">
                                    <label for="description" class="col-sm-0 control-label">UOM</label>
                                    <input type="text" value="" id="uom" name="uom[]" class="form-control" maxlength="2"/>
                                </div>
                                <div class="col-sm-2">
                                    <label class="col-sm-0 control-label">&nbsp;</label>
                                    <button class="add_field_button form-control btn-info">Add Another Item</button>
                                </div>
                            </div>  
                        </div>
                    <?php } else {
                        ?>

                        <div class="row">
                            <div class="col-sm-2">
                                <label for="sku" class="col-sm-0 control-label">SKU</label>      
                            </div>
                            <div class="col-sm-2">
                                <label for="quantity" class="col-sm-0 control-label">Quantity</label>
                            </div>
                            <div class="col-sm-2">
                                <label for="product_name" class="col-sm-0 control-label">Product Name</label>

                            </div>

                            <div class="col-sm-2">
                                <label for="description" class="col-sm-0 control-label">Description</label>

                            </div>
                            <div class="col-sm-2">
                                <label for="uom" class="col-sm-0 control-label">UOM</label>

                            </div>
                            <div class="col-sm-2">
                                <button class="add_field_button form-control btn-info">Add Another Item</button>
                            </div>
                        </div> 

                        <div class="input_fields_wrap">

                            <?php
                            $db1->row_count;
                            if ($db1->row_count > 0) {
                                $j = $db1->row_count - 1;
                                for ($i = 0; $i < $db1->row_count; $i++) {
                                    $db1->moveto($i);
                                    ?>

                                    <div>

                                        <div class="row">
                                            <div class="col-sm-2">

                                                <input type="text"  id="sku" name="sku[]" class="form-control" value="<?= $db1->f_sku ?>">
                                            </div>
                                            <div class="col-sm-2">

                                                <input type="text"  id="qty" name="qty[]" class="form-control" value="<?= $db1->f_qty ?>" maxlength="5"/>
                                            </div>
                                            <div class="col-sm-2">

                                                <input type="text"  id="product_name" name="product_name[]" class="form-control" value="<?= $db1->f_product_name ?>"/>
                                            </div>

                                            <div class="col-sm-2">

                                                <input type="text"  id="description" name="description[]" class="form-control" value="<?= $db1->f_description ?>"/>
                                            </div>
                                            <div class="col-sm-2">

                                                <input type="text"  id="uom" name="uom[]" class="form-control" value="<?= $db1->f_uom ?>" maxlength="2"/>
                                            </div>
                                            <div class="col-sm-2">
                                                <?php
                                                if ($i != 0 && $show_type == "Pickup") {
                                                    ?>
                                                    <a href='javascript:void(0)' class='remove_field' id="<?php echo $db1->f_item_id ?>">Remove</a>
                                                    <?php
                                                }
                                                if ($show_type == "Delivery") {
                                                    ?>
                                                    <a href='javascript:void(0)' class='remove_field' id="<?php echo $db1->f_item_id ?>">Remove</a>
                                                <?php }
                                                ?>
                                            </div>
                                            <input type="hidden" value="<?= $db1->f_item_id ?>" id="item_id" name="item_id[]"/>
                                        </div>                <?php
                                        if ($i < $j) {
                                            ?>
                                            <br>
                                        <?php } ?>

                                    </div>
                                    <?php
                                }
                                ?>
                            </div>
                            <?php
                        }
                    }
                    ?>

                </fieldset>


                <br>
                <div class="row">
                    <div class="col-sm-3">


                        <?php
                        if ($delivery_id > 0) {
                            if ($show_type == "Delivery") {
                                $btn_name = "Update Delivery";
                            } else {
                                $btn_name = "Update Pickup";
                            }
                            ?>  

                            <div class="form-group">
                                <div class="col-sm-12">
                                    <button type="submit" name="submit" id="submit" value=" Save " class="btn btn-success"><?php echo $btn_name ?></button>
                                                <span style="color:#999999">or</span>&nbsp; <span class="cancellink"><a href="<?php echo SITEROOT . '/admin/m/master/manifests.php' ?>">Cancel</a></span> </div>
                            </div>
                            <?php
                        } else {

                            if ($show_type == "Delivery") {
                                $btn_name = "Add Delivery to Manifest";
                            } else {
                                $btn_name = "Add Pickup to Manifest";
                            }
                            ?>
                            <div class="form-group">
                                <div class="col-sm-12">
                                    <input type="submit" name="submit" id="submit" value="<?php echo $btn_name ?>" class="btn btn-success">
                                    <!--          <button type="submit" name="submit" id="submit" value=" Save " class="btn btn-success" id="btn_add">Add Delivery to Manifest</button>-->
                                                <span style="color:#999999">or</span>&nbsp; <span class="cancellink"><a href="<?php echo SITEROOT . '/admin/m/master/manifests.php' ?>">Cancel</a></span> </div>
                            </div>
                        <?php } ?>

                    </div>
                </div>

            </div>
        </form>
    </div>

</div>




<?php
if ($admin_require_packages == 1) {
    ?>
    <script>
        $(document).ready(function () {

            $("#package_id").rules("add", "required");
            $("#package_qty").rules("add", "required");

        });
    </script>
    <?php
} 

if ($admin_require_items == 1) {
    ?>
    <script>
        $(document).ready(function () {
            $("#sku").rules("add", "required");
            $("#qty").rules("add", "required");

        });
    </script>
    <?php
}
?>

<script>
    function get_address() {
        var cust_id = $("#customer_id").val();
        $.ajax({
            type: "POST",
            url: SITEROOT + "/admin/ajax/admin_ajax_action.php?action=get_address&cust-id=" + cust_id,
            data: {cust_id: cust_id},
            success: function (data) {
                $("#address_id").html(data);
            }
        });
    }

    function get_deliveries_table(manifest_id) {
        var cust_id = $("#customer_id").val();
        $.ajax({
            type: "POST",
            url: SITEROOT + "/admin/ajax/admin_ajax_action.php?action=get_delivery_table&manifest_id=" + manifest_id,
            data: {manifest_id: manifest_id},
            success: function (data) {
                $("#address_id").html(data);
            }
        });
    }

    function show_delivery() {
        $("#lbl_id").text("Create Delivery");
        $("#manifest_type").val("Delivery");
        $("#delivery_pickup_id").attr('src', SITEROOT + '/admin/templates/images/icons/delivery.png');
        //document.getElementById("delivery_pickup_id").src="delivery.png";
      //  $("#mandatory_optional").text("(Optional)");
        $("#btn_pickup").css('background-color', '');
        $("#submit").prop('value', 'Add Delivery to Manifest');


    }
    function show_pickup() {
        $("#lbl_id").text("Enter Pick Up");
        $("#manifest_type").val("Pickup");
        $("#delivery_pickup_id").attr('src', SITEROOT + '/admin/templates/images/icons/pickup.png');
        $("#btn_delivery").css('background-color', '');
        $("#submit").prop('value', 'Add Pickup to Manifest');
       // $("#mandatory_optional").text("(Mandatory)");
    }

    $(document).ready(function () {
        //var max_fields      = 10 //maximum input boxes allowed
        var wrapper = $(".input_fields_wrap"); //Fields wrapper
        var add_button = $(".add_field_button"); //Add button ID

        var x = 1; //initlal text box count
        $(add_button).click(function (e) { //on add input button click
            e.preventDefault();
            //if(x < max_fields){ //max input box allowed
            // x++; //text box increment
            $(wrapper).append("<div><div class='row'><br/><div class='col-sm-2'><input type='text' id='sku' name='sku[]' class='form-control'/></div><div class='col-sm-2'><input type='text' id='qty' name='qty[]'class='form-control' maxlength='5'/></div><div class='col-sm-2'><input type='text'  id='product_name' name='product_name[]' class='form-control'/></div><div class='col-sm-2'><input type='text' id='description' name='description[]' class='form-control'/></div><div class='col-sm-2'><input type='text' id='uom' name='uom[]' class='form-control' maxlength='2'/></div><div class='col-sm-2'><a href='javascript:void(0)' class='remove_field'>Remove</a><input type='hidden' value='0' id='item_id' name='item_id[]'/></div></div></div>"); //add input box
            // }
        });

        var add_package_class = $(".add_package_class"); //Fields wrapper
        var add_package_button = $(".add_package_button"); //Add button ID

        $(add_package_button).click(function (e) { //on add input button click
            e.preventDefault();

            $.ajax({
                type: "POST",
                url: SITEROOT + "/admin/ajax/admin_ajax_action.php?action=get_packages_list",
                data: {},
                success: function (data) {
                    //$("#address_id").html(data);
                    $(add_package_class).append("<div><div class='row'><br/><div class='col-sm-3'>" + data + "</div><div class='col-sm-3'><input  id='package_qty' name='package_qty[]' class='form-control' maxlength='5'/></div><div class='col-sm-3'><a href='javascript:void(0)' class='remove_package'>Remove</a><input type='hidden' value='0' id='packages_id' name='packages_id[]'/></div></div></div>");
                }
            });



            //if(x < max_fields){ //max input box allowed
            // x++; //text box increment
            //add input box
            // }
        });


        //Delete Item
        $(wrapper).on("click", ".remove_field", function (e) { //user click on remove text
            if (confirm('Are you sure to delete')) {

//         var item_id = $(this).attr('id');
//          $.ajax({
//            type: "POST",
//            url: SITEROOT +"/admin/ajax/admin_ajax_action.php?action=delete_item",
//            data:{item_id:item_id},
//            success: function(data){
//                    //$("#address_id").html(data);
//            }
//            });

                e.preventDefault();
                $(this).parent('div').parent('div').remove();
//x--;
            }
        })

        //Delete package
        $(add_package_class).on("click", ".remove_package", function (e) { //user click on remove text
            if (confirm('Are you sure to delete')) {
                e.preventDefault();
                $(this).parent('div').parent('div').remove();
//x--;
            }
        })


    });

    $(function () {

        $('#scheduled_delivery').datepicker({
            format: 'yyyy-mm-dd',
            "setDate": new Date(),
            "autoclose": true
        })


    });

    function check_valid_qty(qty) {
        if (isNaN(qty)) {
            $(".error_qty").text("Please enter a valid number.");
        }
    }
</script>






