<?php
/*
 * Shared manifest helpers
 *  - update_manifest_counts(): used by the manifest editor (ajax/manifests_new.php) and the CSV import
 *  - import_manifest_csv():    CSV import of deliveries (templates/MANIFEST.csv) used by manifests.php and create_manifest.php
 */

//Recalculate the delivery / route / package / item / area totals stored on a manifest
function update_manifest_counts($manifest_id) {
    $manifest_id = (int) $manifest_id;
    if ($manifest_id < 1) {
        return;
    }
    $db_counts = new database();
    $db_counts->myCustomQuery("SELECT count(D.delivery_id) AS NO_OF_DELIVERY, count(DISTINCT D.route_id) AS NO_OF_ROUTES, count(DISTINCT R.area_id) AS NO_OF_AREA,
            (SELECT COALESCE(SUM(DP.qty),0) FROM deliveries D2 INNER JOIN delivery_packages DP ON D2.delivery_id=DP.delivery_id WHERE D2.manifest_id=" . $manifest_id . ") AS NO_OF_PACKAGES,
            (SELECT COALESCE(SUM(DI.qty),0) FROM deliveries D3 INNER JOIN delivery_items DI ON D3.delivery_id=DI.delivery_id WHERE D3.manifest_id=" . $manifest_id . ") AS NO_OF_ITEMS
            FROM deliveries D LEFT JOIN routes R ON R.route_id = D.route_id WHERE D.manifest_id=" . $manifest_id);

    $db_update_data = new database();
    $db_update_data->data('num_deliveries', $db_counts->f_NO_OF_DELIVERY);
    $db_update_data->data('num_routes', $db_counts->f_NO_OF_ROUTES);
    $db_update_data->data('num_packages', $db_counts->f_NO_OF_PACKAGES);
    $db_update_data->data('num_items', $db_counts->f_NO_OF_ITEMS);
    $db_update_data->data('num_areas', $db_counts->f_NO_OF_AREA);
    $db_update_data->where("manifest_id=" . $manifest_id);
    $db_update_data->update("manifests");
}

//Move an uploaded CSV into admin/uploads under a safe generated name; returns the path or false
function save_uploaded_csv($field = 'import_file_name') {
    if (empty($_FILES[$field]['tmp_name']) || !is_uploaded_file($_FILES[$field]['tmp_name'])) {
        return false;
    }
    if (strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION)) != 'csv') {
        return false;
    }
    $targetPath = ROOTPATH . "/admin/uploads/import_" . date('Ymd_His') . "_" . mt_rand(1000, 9999) . ".csv";
    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $targetPath)) {
        return false;
    }
    return $targetPath;
}

/*
 * Import deliveries from a MANIFEST.csv file into a manifest.
 * Columns: Order_No, Account_No, Customer, Address_Line_1, Address_Line_2, Address_Line_3, City, State, ZIP, Phone,
 *          Package_Type, Package_Qty, SKU, Qty, Item_Name, Desc, UOM
 * Rows sharing an Order_No become one delivery with several items / packages.
 * A new manifest is created when $manifest_id < 1. Returns array(error message or "", manifest_id, deliveries imported).
 */
function import_manifest_csv($path, $manifest_id, $route_id, $driver_id, $scheduled_delivery, $manifest_type = 'Delivery') {
    global $functions;

    $import_file_columns = array("Order_No", "Account_No", "Customer", "Address_Line_1", "Address_Line_2", "Address_Line_3", "City", "State", "ZIP", "Phone", "Package_Type", "Package_Qty", "SKU", "Qty", "Item_Name", "Desc", "UOM");

    $file = fopen($path, "r");
    $file_columns = fgetcsv($file);
    if ($functions->check_csv_columns($import_file_columns, (array) $file_columns) == "false") {
        fclose($file);
        return array("Columns not matched. Please use the manifest template.", $manifest_id, 0);
    }

    $manifest_id = (int) $manifest_id;
    $route_id = (int) $route_id;
    $driver_id = (int) $driver_id;
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $scheduled_delivery)) {
        $scheduled_delivery = date('Y-m-d');
    }
    if ($manifest_type != 'Pickup') {
        $manifest_type = 'Delivery';
    }

    $deliveries = array();
    while (($row = fgetcsv($file)) !== false) {
        $row = array_pad(array_map('trim', $row), 17, '');
        list($order_no, $account_number, $customer_name, $address_line_1, $address_line_2, $address_line_3, $city, $state, $zip, $phone,
                $package_type, $package_qty, $sku, $item_qty, $item_name, $item_desc, $item_uom) = $row;
        if ($order_no == "" && $customer_name == "" && $sku == "") {
            continue; // blank line
        }

        if ($manifest_id < 1) {
            $db_manifest = new database();
            $db_manifest->data("submitted_by_user_id", $_SESSION["routetrac-admin"]["al_id"]);
            $db_manifest->data("manifest_type", $manifest_type);
            $db_manifest->insert("manifests");
            $manifest_id = $db_manifest->id;
        }

        //Customer: match by account number, then by name; create it when missing
        $customer_id = 0;
        $address_id = 0;
        if ($customer_name != "" || $account_number != "") {
            $db_customer = new database();
            $where = ($account_number != "") ? "account_number='" . sanitize($account_number) . "'" : "customer_name='" . sanitize($customer_name) . "'";
            $db_customer->myCustomQuery("SELECT customer_id FROM customers WHERE " . $where . " LIMIT 1");
            if ($db_customer->row_count > 0) {
                $customer_id = $db_customer->f_customer_id;
            } else {
                $db_insert_customer = new database();
                $db_insert_customer->data('customer_name', $customer_name);
                $db_insert_customer->data('account_number', $account_number);
                $db_insert_customer->insert("customers");
                $customer_id = $db_insert_customer->id;
            }

            //Address of that customer: reuse a matching one, otherwise add it
            if ($address_line_1 != "") {
                $db_address = new database();
                $db_address->myCustomQuery("SELECT address_id FROM customer_addresses WHERE customer_id='" . (int) $customer_id . "'
                        AND address_line_1='" . sanitize($address_line_1) . "' AND city='" . sanitize($city) . "' AND zip='" . sanitize($zip) . "' LIMIT 1");
                if ($db_address->row_count > 0) {
                    $address_id = $db_address->f_address_id;
                } else {
                    $db_insert_address = new database();
                    $db_insert_address->data('customer_id', $customer_id);
                    $db_insert_address->data('ship_to_name', $customer_name);
                    $db_insert_address->data('address_line_1', $address_line_1);
                    $db_insert_address->data('address_line_2', $address_line_2);
                    $db_insert_address->data('address_line_3', $address_line_3);
                    $db_insert_address->data('city', $city);
                    $db_insert_address->data('state', $state);
                    $db_insert_address->data('zip', $zip);
                    $db_insert_address->data('phone', $phone);
                    $db_insert_address->insert("customer_addresses");
                    $address_id = $db_insert_address->id;
                }
            }
        }

        //Delivery: one per order number within this manifest
        $db_delivery = new database();
        $db_delivery->myCustomQuery("SELECT delivery_id FROM deliveries WHERE manifest_id=" . $manifest_id . " AND order_number='" . sanitize($order_no) . "' LIMIT 1");
        if ($db_delivery->row_count > 0) {
            $delivery_id = $db_delivery->f_delivery_id;
        } else {
            $db_insert_delivery = new database();
            $db_insert_delivery->data("manifest_id", $manifest_id);
            $db_insert_delivery->data("route_id", $route_id);
            $db_insert_delivery->data("driver_id", $driver_id);
            $db_insert_delivery->data("stop_number", count($deliveries) + 1);
            $db_insert_delivery->data("customer_id", $customer_id);
            $db_insert_delivery->data("address_id", $address_id);
            $db_insert_delivery->data("order_number", $order_no);
            $db_insert_delivery->data("scheduled_delivery", $scheduled_delivery);
            $db_insert_delivery->data("status", 'In Transit');
            $db_insert_delivery->insert("deliveries");
            $delivery_id = $db_insert_delivery->id;
        }
        $deliveries[$delivery_id] = 1;

        //Item
        if ($sku != "") {
            $db_insert_item = new database();
            $db_insert_item->data("delivery_id", $delivery_id);
            $db_insert_item->data("sku", $sku);
            $db_insert_item->data("qty", (int) $item_qty);
            $db_insert_item->data("product_name", $item_name);
            $db_insert_item->data("description", $item_desc);
            $db_insert_item->data("uom", substr($item_uom, 0, 2));
            $db_insert_item->insert("delivery_items");
        }

        //Package: package type is created when it does not exist yet; one row per type per delivery
        if ($package_type != "" && (int) $package_qty > 0) {
            $db_package_type = new database();
            $db_package_type->myCustomQuery("SELECT package_type_id FROM package_types WHERE name='" . sanitize($package_type) . "' LIMIT 1");
            if ($db_package_type->row_count > 0) {
                $package_type_id = $db_package_type->f_package_type_id;
            } else {
                $db_insert_type = new database();
                $db_insert_type->data("name", $package_type);
                $db_insert_type->insert("package_types");
                $package_type_id = $db_insert_type->id;
            }

            $db_check_package = new database();
            $db_check_package->myCustomQuery("SELECT packages_id FROM delivery_packages WHERE delivery_id=" . (int) $delivery_id . " AND package_type_id=" . (int) $package_type_id);
            if ($db_check_package->row_count == 0) {
                $db_insert_package = new database();
                $db_insert_package->data("delivery_id", $delivery_id);
                $db_insert_package->data("package_type_id", $package_type_id);
                $db_insert_package->data("qty", (int) $package_qty);
                $db_insert_package->insert("delivery_packages");
            }
        }
    }
    fclose($file);

    if (count($deliveries) == 0) {
        return array("The file has no rows to import.", $manifest_id, 0);
    }
    update_manifest_counts($manifest_id);
    return array("", $manifest_id, count($deliveries));
}

//Handle the import form post (shared by manifests.php and create_manifest.php); sets the flash message
function handle_manifest_import_post() {
    $path = save_uploaded_csv();
    if ($path === false) {
        $_SESSION["routetrac-admin"]["error_success_msg"] = "<span style='color:red'>Please select a .csv file</span>";
        return 0;
    }
    list($error, $manifest_id, $count) = import_manifest_csv($path, $_SESSION['manifest_id'], $_POST['import_route_id'],
            $_POST['import_driver_id'], $_POST['import_scheduled_delivery'], $_POST['import_manifest_type']);
    unlink($path);
    if ($error != "") {
        $_SESSION["routetrac-admin"]["error_success_msg"] = "<span style='color:red'>" . htmlspecialchars($error) . "</span>";
    } else {
        $_SESSION['manifest_id'] = $manifest_id;
        $_SESSION["routetrac-admin"]["error_success_msg"] = "File imported successfully: " . $count . " deliveries added to manifest #" . $manifest_id;
    }
    return $manifest_id;
}

//Import form fields: route / driver / date / type applied to every imported delivery
function manifest_import_form_fields() {
    global $functions;
    ?>
    <div class="form-group" style="margin-right:10px">
        <select name="import_manifest_type" class="form-control">
            <option value="Delivery">Delivery</option>
            <option value="Pickup">Pickup</option>
        </select>
    </div>
    <div class="form-group" style="margin-right:10px">
        <select name="import_route_id" class="form-control" required>
            <option value="">Select Route</option>
            <?= $functions->createCombo("routes", "route_id", "name", "", " order by name asc"); ?>
        </select>
    </div>
    <div class="form-group" style="margin-right:10px">
        <select name="import_driver_id" class="form-control">
            <option value="">Select Driver</option>
            <?= $functions->createCombo("users", "user_id", "first_name", "", "order by first_name asc", "is_driver=1"); ?>
        </select>
    </div>
    <div class="form-group" style="margin-right:10px">
        <input type="text" name="import_scheduled_delivery" class="form-control" value="<?= date('Y-m-d') ?>" placeholder="YYYY-MM-DD" style="width:120px"/>
    </div>
    <br><br>
    <?php
}
