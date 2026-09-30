<?php
include_once("../bootstrap.php");
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    // Get data
    $api_key = trim($_POST['api_key']);
    $manifest_type = trim($_POST['manifest_type']);
    $route_name = trim($_POST['route_name']);
    $area_name = trim($_POST['area_name']);
    $driver_first_name = trim($_POST['driver_first_name']);
    $driver_last_name = trim($_POST['driver_last_name']);
    $driver_emailid = trim($_POST['driver_emailid']);
    $customer_name = trim($_POST['customer_name']);
    $customer_address = trim($_POST['customer_address']);
    $customer_city = trim($_POST['customer_city']);
    $customer_state = trim($_POST['customer_state']);
    $customer_zip = trim($_POST['customer_zip']);
    $stop_no = trim($_POST['stop_no']);
    $order_no = trim($_POST['order_no']);
    $po_no = trim($_POST['po_no']);
    $deliver_notes = trim($_POST['deliver_notes']);
    $scheduled_delivery = $_POST['scheduled_delivery'];
    $packages = $_POST['packages'];
    $items = $_POST['items'];
    $validemail = preg_match('/^[A-z0-9_\-]+[@][A-z0-9_\-]+([.][A-z0-9_\-]+)+[A-z.]{2,4}$/', $driver_emailid);

    if ($_POST == "") {
        $json = array("status" => 0, "msg" => "Error!! please post data properly.");
    } else {
        /* check whether apikey exists for any user if yes then fetch userid else show error */
        $user_api_db = new database(); //AREA TABLE
        $check_user_api = "SELECT user_id,api_key,status FROM users WHERE api_key='$api_key'";
        $user_api_db->myCustomQuery($check_user_api);
        $status = $user_api_db->f_status;
        $user_id = $user_api_db->f_user_id;

        if (empty($api_key)) {
            $json = array("status" => 0, "msg" => "Error!! please provide API Key.");
        } else if ($user_api_db->row_count < 1) {
            $json = array("status" => 0, "msg" => "Error!! API Key $api_key is invalid.");
        } else if ($user_api_db->row_count >= 1 && $status = 0) {
            $json = array("status" => 0, "msg" => "Error!! user account is not active yet.");
        } else if (empty($manifest_type) || empty($route_name) || empty($area_name) || empty($driver_first_name) || empty($driver_last_name) || empty($driver_emailid) || empty($customer_name) || empty($customer_address) || empty($customer_city) || empty($customer_state) || empty($customer_zip) || empty($scheduled_delivery)) {
            $json = array("status" => 0, "msg" => "Error!! please enter all required fields.");
        } else if ($manifest_type == "Pickup" && count($items) < 1) {
            $json = array("status" => 0, "msg" => "Error!! please enter all sku details.");
        } else if (!filter_var($driver_emailid, FILTER_VALIDATE_EMAIL)) {
            $json = array("status" => 0, "msg" => "Error!! $driver_emailid email id is invalid.");
        }else if (!is_numeric($customer_zip) || strlen($customer_zip) < 5 || strlen($customer_zip) > 5) {
            //check if $customer_id value is non numeric
            $json = array("status" => 0, "msg" => "Error!! please provide valid customer zip,it should be numeric and 5 digits.");
            $error = 1;
        }  else {
            $route_db = new database(); //ROUTE TABLE
            $area_db = new database(); //AREA TABLE
            $driver_db = new database(); //DRIVER TABLE
            $customer_db = new database(); //CUSTOMER TABLE
            $address_db = new database(); //ADDRESS TABLE

            /* check whether route name already exists 
              if yes then insert id or else insert route in route tbl then insert id */
            $check_route = "SELECT name,route_id FROM routes WHERE name='$route_name'";
            $route_db->myCustomQuery($check_route);
            if ($route_db->row_count > 0) {
                $route_id = $route_db->f_route_id;
            } else {
                /* check whether area name already exists 
                  if yes then insert id or else insert area in area tbl then insert id */
                $check_area = "SELECT name,area_id FROM areas WHERE name='$area_name'";
                $area_db->myCustomQuery($check_area);
                if ($area_db->row_count > 0) {
                    $area_id = $area_db->f_area_id;
                } else {
                    $area_insert_db = new database(); //for area insert
                    $area_insert_db->data("name", $area_name);
                    $area_insert_db->data("added_by_api", "1");
                    $area_insert_db->insert("areas");
                    $area_id = $area_insert_db->id;
                }

                /* insert route because route not exists */
                $route_insert_db = new database(); //for route insert
                $route_insert_db->data("area_id", $area_id);
                $route_insert_db->data("name", $route_name);
                $route_insert_db->data("added_by_api", "1");
                $route_insert_db->insert("routes");
                $route_id = $route_insert_db->id;
            }//end else

            /* check whether driver emailid already exists 
              if yes then insert id or else show error driver not exists */
            $check_driver = "SELECT user_id,first_name,last_name,email_addr,is_driver FROM users WHERE "
                    . "email_addr='$driver_emailid' and is_driver='1' and status='1'";
            $driver_db->myCustomQuery($check_driver);
            if ($driver_db->row_count > 0) {
                $driver_id = $driver_db->f_user_id;
            } else {
                $driver_id = 0;
            }


            /* check whether customer name already exists 
              if yes then insert id or else insert customer with address details */
            $check_customer = "SELECT customer_id,customer_name FROM customers WHERE "
                    . "customer_name='$customer_name'";
            $customer_db->myCustomQuery($check_customer);
            if ($customer_db->row_count > 0) {
                $customer_id = $customer_db->f_customer_id;
                /* check if address is exists for particular customer
                  if else insert address id
                  else insert customer address in tbl and then insert address id */
                $check_address = "SELECT customer_id,address_id,address_line_1,city,state,zip FROM customer_addresses WHERE "
                        . "address_line_1='$customer_address' AND "
                        . "city='$customer_city' AND "
                        . "state='$customer_state' AND "
                        . "zip='$customer_zip' AND "
                        . "customer_id='$customer_id'";
                $address_db->myCustomQuery($check_address);
                if ($address_db->row_count > 0) {
                    $address_id = $address_db->f_address_id;
                } else {
                    /* insery customer address because 
                      address for that particular customer not exists */
                    $address_insert_db = new database(); //for address insert
                    $address_insert_db->data("customer_id", $customer_id);
                    $address_insert_db->data("address_line_1", $customer_address);
                    $address_insert_db->data("city", $customer_city);
                    $address_insert_db->data("state", $customer_state);
                    $address_insert_db->data("zip", $customer_zip);
                    $address_insert_db->data("added_by_api", "1");
                    $address_insert_db->insert("customer_addresses");
                    $address_id = $address_insert_db->id;
                }
            } else {
                $customer_insert_db = new database(); //for customer insert
                $customer_insert_db->data("customer_name", $customer_name);
                $customer_insert_db->data("added_by_api", "1");
                $customer_insert_db->insert("customers");
                $customer_id = $customer_insert_db->id;

                $address_insert_db = new database(); //for address insert
                $address_insert_db->data("customer_id", $customer_id);
                $address_insert_db->data("address_line_1", $customer_address);
                $address_insert_db->data("city", $customer_city);
                $address_insert_db->data("state", $customer_state);
                $address_insert_db->data("zip", $customer_zip);
                $address_insert_db->data("added_by_api", "1");
                $address_insert_db->insert("customer_addresses");
                $address_id = $address_insert_db->id;
            }
            /* check whether all required fields i.e route_id,driver_id,customer_id,address_id are greater than 0
              if it is less than 0 then show error
              else insert records in manifest tbl
             */
            if (($driver_id > 0)) {

                $manifest_insert_db = new database(); //for manifest insert
                $manifest_insert_db->data("submitted_by_user_id", $user_id);
                $manifest_insert_db->data("submit_date", date('Y-m-d H:i:s'));
                $manifest_insert_db->data("manifest_type", $manifest_type);
                $manifest_insert_db->data("api_submitted", "1");
                $manifest_insert_db->insert("manifests");
                $manifest_id = $manifest_insert_db->id;

                $delivery_insert_db = new database(); //for manifest insert
                $delivery_insert_db->data("manifest_id", $manifest_id);
                $delivery_insert_db->data("route_id", $route_id);
                $delivery_insert_db->data("driver_id", $driver_id);
                $delivery_insert_db->data("stop_number", $stop_no);
                $delivery_insert_db->data("customer_id", $customer_id);
                $delivery_insert_db->data("address_id", $address_id);
                $delivery_insert_db->data("order_number", $order_no);
                $delivery_insert_db->data("cust_po_num", $po_no);
                $delivery_insert_db->data("delivery_notes", $deliver_notes);
                $delivery_insert_db->data("scheduled_delivery", $scheduled_delivery);
                $qur = $delivery_insert_db->insert("deliveries");
                $delivery_id = $delivery_insert_db->id;
                if ($qur) {
                    $json = array("status" => 0, "msg" => "Error!! adding manifest.");
                } else {
                    /* START OF INSERT DATA INTO DELIVERY ITEMS TBL */
                    foreach ($items as $itemvalue) {
                        $item_insert_db = new database(); //for items insert
                        $item_insert_db->data("delivery_id", $delivery_id);
                        $item_insert_db->data("sku", $itemvalue['sku']);
                        $item_insert_db->data("qty", $itemvalue['sku_quantity']);
                        $item_insert_db->data("product_name", $itemvalue['prod_name']);
                        $item_insert_db->data("description", $itemvalue['desc']);
                        $item_insert_db->data("uom", $itemvalue['uom']);
                        $pckg_ins = $item_insert_db->insert("delivery_items");
                    }
                    /* END OF INSERT DATA INTO DELIVERY ITEMS TBL */

                    /* START OF INSERT DATA INTO DELIVEY PACAKAGE TBL */

                    foreach ($packages as $value) {
                        $pckg_type = $value['pckg_type'];
                        $quantity = $value['quantity'];
                        $package_db = new database();
                        /* check whether package name already exists 
                          if yes then insert id or else insert packages name  in package type tbl then insert id */
                        $check_package = "SELECT package_type_id,name FROM package_types WHERE name='$pckg_type'";
                        $package_db->myCustomQuery($check_package);
                        if ($package_db->row_count > 0) {
                            $package_type_id = $package_db->f_package_type_id;
                        } else {
                            $package_type_insert_db = new database(); //for manifest insert
                            $package_type_insert_db->data("name", $pckg_type);
                            $package_type_insert_db->data("added_by_api", "1");
                            $package_type_insert_db->insert("package_types");
                            $package_type_id = $package_type_insert_db->id;
                        }

                        $pckg_insert_db = new database(); //for packages insert
                        $pckg_insert_db->data("delivery_id", $delivery_id);
                        $pckg_insert_db->data("package_type_id", $package_type_id);
                        $pckg_insert_db->data("qty", $value['quantity']);
                        $pckg_ins = $pckg_insert_db->insert("delivery_packages");
                    }
                    /* START OF INSERT DATA INTO DELIVEY PACAKAGE TBL */
                    $db_update_manifest_data = new database();
                    $update_delivery_routes = "SELECT count(D.delivery_id) AS NO_OF_DELIVERY,count(D.route_id) AS NO_OF_ROUTES FROM manifests M LEFT JOIN deliveries D ON M.manifest_id=D.manifest_id WHERE D.manifest_id='" . $manifest_id . "'";
                    $db_update_manifest_data->myCustomQuery($update_delivery_routes);
                    if ($db_update_manifest_data->row_count > 0) {

                        $db_update_manifest_data->moveto(0);
                        $no_of_deliveries = $db_update_manifest_data->f_NO_OF_DELIVERY;
                        $no_of_routes = $db_update_manifest_data->f_NO_OF_ROUTES;
                    }
                    //Update package count
                    $db_update_packages = new database();
                    $update_packages = "SELECT SUM(DP.qty) AS NO_OF_PACKAGES from manifests M LEFT JOIN deliveries D ON M.manifest_id=D.manifest_id LEFT JOIN delivery_packages DP ON D.delivery_id=DP.delivery_id WHERE D.manifest_id='" . $manifest_id . "'";
                    $db_update_packages->myCustomQuery($update_packages);
                    if ($db_update_packages->row_count > 0) {
                        $db_update_packages->moveto(0);

                        $no_of_packages = $db_update_packages->f_NO_OF_PACKAGES;
                    }
                    //Update item count
                    $db_update_item = new database();
                    $update_item = "SELECT SUM(DI.qty) AS NO_OF_ITEMS FROM manifests M LEFT JOIN deliveries D ON M.manifest_id=D.manifest_id LEFT JOIN delivery_items DI ON D.delivery_id=DI.delivery_id WHERE D.manifest_id='" . $manifest_id . "'";
                    $db_update_item->myCustomQuery($update_item);
                    if ($db_update_item->row_count > 0) {
                        $db_update_item->moveto(0);

                        $no_of_items = $db_update_item->f_NO_OF_ITEMS;
                    }

                    $db_update_data = new database();
                    $db_update_data->data('num_deliveries', $no_of_deliveries);
                    $db_update_data->data('num_routes', $no_of_routes);
                    $db_update_data->data('num_packages', $no_of_packages);
                    $db_update_data->data('num_items', $no_of_items);

                    $db_update_data->where("manifest_id=" . $manifest_id);
                    $db_update_data->update("manifests");
                    $json = array("status" => 1, "msg" => "Success!! new $manifest_type manifest successfully added.");
                }//end of $qry else
            } else {
                $json = array("status" => 0, "msg" => "Error!! please enter valid driver name.");
            }//end of else
        }//end of main else
    }//end of $_POST = ""
} else {
    $json = array("status" => 0, "msg" => "Error!! request method not accepted.");
}//end of else


/* Output header */
header('Content-type: application/json');
echo json_encode($json);
?>