<?php
include_once(__DIR__ . '/request_filter.php');


$app_key = $_REQUEST['app_key'];
$delivery_id = $_REQUEST['delivery_id'];
$signature_text = $_REQUEST['signature_text'];
$server_filename = $_REQUEST['server_filename'];


$package_ids = $_REQUEST['package_id'];
$package_qty = $_REQUEST['package_qty'];
$item_qty = $_REQUEST['item_qty'];
$item_id = $_REQUEST['item_id'];
$driver_notes = $_REQUEST['driver_notes'];


include_once("../bootstrap.php");
include_once('message.php');

$delivery_id_array = array();
$package_id_array = array();
$package_qty_array = array();
$item_qty_array = array();
$item_id_array = array();
$result = array();
$img_name == "";
if ($server_filename != "") {
    $delivery_id_array = explode(",", $delivery_id);
    $package_id_array = explode(",", $package_ids);
    $package_qty_array = explode(",", $package_qty);
    $item_qty_array = explode(",", $item_qty);
    $item_id_array = explode(",", $item_id);

    // echo "<pre>";
    //  print_r($package_id_array);
    //   echo "<br>";
    //     echo "<pre>";
    //   print_r($item_id_array);


    $img_name == "";
    $server_filename = base64_decode($server_filename);
    $img_name = imagecreatefromstring($server_filename);
    header('Content-Type: image/jpeg', true);
    ob_start();
    if ($img_name) imagejpeg($img_name);
    $imagevariable = ob_get_contents();
    ob_end_clean();
    // echo "<pre>";
    //print_r($delivery_id_array);

    foreach ($delivery_id_array as $delivery_id) {
        $filename = "signature_" . date('Y-m-d H_i_s') . "_" . $delivery_id . ".jpeg";
        /* Rename the image by id */
        $path = "../uploads/signature/$filename";
        file_put_contents($path, $server_filename);

        //Insert signature
        $db = new database();
        $db->data("server_filename", $filename);
        $db->insert("signature_pics");

        $last_id = $db->id;
        //Update signature in deliveries
        $curdate_time = date("Y-m-d H:i:s");
        $db_update_sign = new database();
        $db_update_sign->data("signature_pic_id", $last_id);
        $db_update_sign->data("status", 'Delivered');
        $db_update_sign->data("signature_text", $signature_text);
        $db_update_sign->data("delivery_time", $curdate_time);
        $db_update_sign->data("driver_notes", $driver_notes);
        $db_update_sign->where("delivery_id=" . $delivery_id);
        $db_update_sign->update("deliveries");

        //UPDATE PACKAGE QUANTITY
        if (strlen($package_ids) > 0) {
            $i = 0;
            foreach ($package_id_array as $package_id_data) {
                $db_package_qty_update = new database();
                // echo "package=".$package_qty_array[$i];
                // echo "ID=".$package_id_data;
                if ($package_qty_array[$i] != "") {
                    $db_package_qty_update->data("qty_delivered", $package_qty_array[$i]);
                    $db_package_qty_update->where("packages_id=" . $package_id_data);
                    $db_package_qty_update->update("delivery_packages");
                }

                $i++;
            }
        } else {

            $db_package = new database();
            $query = "SELECT qty,packages_id FROM delivery_packages WHERE delivery_id = '" . $delivery_id . "'";
            $db_package->myCustomQuery($query);
            if ($db_package->row_count > 0) {

                for ($i = 0; $i < $db_package->row_count; $i++) {
                    $db_package->moveto($i);
                    $qty = $db_package->f_qty;
                    $package_id = $db_package->f_packages_id;

                    $db_package_update = new database();
                    $db_package_update->data("qty_delivered", $qty);
                    $db_package_update->where("packages_id=" . $package_id);
                    $db_package_update->update("delivery_packages");
                }
            }
        }



        //         
        //UPDATE ITEM QUANTITY
        if (strlen($item_id) > 0) {
            $i = 0;
            foreach ($item_id_array as $item_id_data) {
                $db_item_qty_update = new database();

                // echo "item=".$item_qty_array[$i];
                // echo "ID=".$item_id_data;
                if ($item_qty_array[$i] != "") {
                    $db_item_qty_update->data("qty_delivered", $item_qty_array[$i]);
                    $db_item_qty_update->where("item_id=" . $item_id_data);
                    $db_item_qty_update->update("delivery_items");
                }
                $i++;
            }
        } else {

            $db_item = new database();
            $query = "SELECT qty,item_id FROM delivery_items WHERE delivery_id = '" . $delivery_id . "'";
            $db_item->myCustomQuery($query);
            if ($db_item->row_count > 0) {

                for ($i = 0; $i < $db_item->row_count; $i++) {
                    $db_item->moveto($i);
                    $qty = $db_item->f_qty;
                    $item_id1 = $db_item->f_item_id;

                    $db_item_update = new database();
                    $db_item_update->data("qty_delivered", $qty);
                    $db_item_update->where("item_id=" . $item_id1);
                    $db_item_update->update("delivery_items");
                }
            }
        }



        //UPLOAD IMAGES
        $img_name = "";
        $img1 = "image1";
        $img2 = "image1";
        $img3 = "image1";
        $file_date = date('m_d_Y_H_i_s');
        //count(array_filter($_FILES['image']['name']))

        if ($_REQUEST["image1"] != "") {
            $img_name = "";
            $server_filename1 = $_REQUEST["image1"];

            $server_filename1 = base64_decode($server_filename1);
            $img_name1 = imagecreatefromstring($server_filename1);

            header('Content-Type: image/jpeg', true);
            ob_start();
            if ($img_name1) imagejpeg($img_name1);
            $imagevariable = ob_get_contents();
            ob_end_clean();

            $filename1 = "image1_" . date('Y-m-d H_i_s') . "_" . $delivery_id . ".jpeg";
            /* Rename the image by id */
            $path = "../uploads/product_pics/$filename1";
            file_put_contents($path, $server_filename1);

            //ADD FIRST IMAGE
            $db_image_1 = new database();
            $db_image_1->data("product_file_path", $filename1);
            $db_image_1->data("delivery_id", $delivery_id);
            $db_image_1->insert("product_pics");
        }

        if ($_REQUEST["image2"] != "") {
            $img_name = "";
            $server_filename2 = $_REQUEST["image2"];

            $server_filename2 = base64_decode($server_filename2);
            $img_name2 = imagecreatefromstring($server_filename2);

            header('Content-Type: image/jpeg', true);
            ob_start();
            if ($img_name2) imagejpeg($img_name2);
            $imagevariable = ob_get_contents();
            ob_end_clean();

            $filename2 = "image2_" . date('Y-m-d H_i_s') . "_" . $delivery_id . ".jpeg";
            /* Rename the image by id */
            $path = "../uploads/product_pics/$filename2";
            file_put_contents($path, $server_filename2);

            //ADD SECOND IMAGE
            $db_image_2 = new database();
            $db_image_2->data("product_file_path", $filename2);
            $db_image_2->data("delivery_id", $delivery_id);
            $db_image_2->insert("product_pics");
        }

        if ($_REQUEST["image3"] != "") {
            $img_name = "";
            $server_filename3 = $_REQUEST["image3"];

            $server_filename3 = base64_decode($server_filename3);
            $img_name3 = imagecreatefromstring($server_filename3);

            header('Content-Type: image/jpeg', true);
            ob_start();
            if ($img_name3) imagejpeg($img_name3);
            $imagevariable = ob_get_contents();
            ob_end_clean();

            $filename3 = "image3_" . date('Y-m-d H_i_s') . "_" . $delivery_id . ".jpeg";
            /* Rename the image by id */
            $path = "../uploads/product_pics/$filename3";
            file_put_contents($path, $server_filename3);

            //ADD THIRD IMAGE
            $db_image_3 = new database();
            $db_image_3->data("product_file_path", $filename3);
            $db_image_3->data("delivery_id", $delivery_id);
            $db_image_3->insert("product_pics");
        }


        if ($_REQUEST["is_scan_image"] != "") {
            $img_name = "";
            $server_filename4 = $_REQUEST["is_scan_image"];

            $server_filename4 = base64_decode($server_filename4);
            $img_name = imagecreatefromstring($server_filename4);

            header('Content-Type: image/jpeg', true);
            ob_start();
            if ($img_name) imagejpeg($img_name);
            $imagevariable = ob_get_contents();
            ob_end_clean();

            $filename_scan = "scan_image_" . date('Y-m-d H_i_s') . "_" . $delivery_id . ".jpeg";
            /* Rename the image by id */
            $path = "../uploads/product_pics/$filename_scan";
            file_put_contents($path, $server_filename4);

            //ADD SCAN IMAGE
            $db_scan_image = new database();
            $db_scan_image->data("product_file_path", $filename_scan);
            $db_scan_image->data("is_scan_image", 'YES');
            $db_scan_image->data("delivery_id", $delivery_id);
            $db_scan_image->insert("product_pics");
        }
    }
    $json = array($RESPONSE_KEY => $RESPONSE_SUCCESS, 'msg' => 'Signature uploaded successfully', 'info' => $result);
    echo json_encode($json);
} else {
    $json = array($RESPONSE_KEY => $RESPONSE_ERROR, 'msg' => 'Please select file with valid extension (e.g.jpg, jpeg, png,pdf)', 'last_id' => '');
    echo json_encode($json);
    exit();
}
?>

