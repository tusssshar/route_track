<?php
include_once(__DIR__ . '/request_filter.php');


$app_key = $_REQUEST['app_key'];
include_once("../bootstrap.php");
include_once('message.php');

$item_id = $_REQUEST['item_id'];
$qty = $_REQUEST['qty'];
$delivery_id = $_REQUEST['delivery_id'];
$driver_notes = $_REQUEST['driver_notes'];
$scan_img_flag="Yes";

$result = array();
$data = array();



if (isset($item_id) && isset($qty) && $qty > 0) {


    $img_name == "";
    $img1 = "image1";
    $img2 = "image1";
    $img3 = "image1";
    $file_date = date('m_d_Y_H_i_s');
    //count(array_filter($_FILES['image']['name']))

    if ($_REQUEST["image1"] != "") {

        $server_filename = $_REQUEST["image1"];

        $server_filename = base64_decode($server_filename);
        $img_name = imagecreatefromstring($server_filename);

        //header('Content-Type: image/png');
        ob_start();
        if ($img_name) imagejpeg($img_name);
        $imagevariable = ob_get_contents();
        ob_end_clean();

        $filename = "image1_" . date('Y-m-d H_i_s') . "_" . $delivery_id . ".jpeg";
        /* Rename the image by id */
        $path = "../uploads/product_pics/$filename";
        file_put_contents($path, $server_filename);

        $db = new database();
        $db->data("product_file_path", $filename);
        $db->data("delivery_id", $delivery_id);

        $db->insert("product_pics");
    }

    if ($_REQUEST["image2"] != "") {

        $server_filename = $_REQUEST["image2"];

        $server_filename = base64_decode($server_filename);
        $img_name = imagecreatefromstring($server_filename);
        //header('Content-Type: image/jpeg', true);
        ob_start();
        if ($img_name) imagejpeg($img_name);
        $imagevariable = ob_get_contents();
        ob_end_clean();

        $filename = "image2_" . date('Y-m-d H_i_s') . "_" . $delivery_id . ".jpeg";
        /* Rename the image by id */
        $path = "../uploads/product_pics/$filename";
        file_put_contents($path, base64_decode($server_filename));

        $db = new database();
        $db->data("product_file_path", $filename);
        $db->data("delivery_id", $delivery_id);

        $db->insert("product_pics");
    }

    if ($_REQUEST["image3"] != "") {

        $server_filename = $_REQUEST["image3"];

        $server_filename = base64_decode($server_filename);
        $img_name = imagecreatefromstring($server_filename);
       // header('Content-Type: image/jpeg', true);
        ob_start();
        if ($img_name) imagejpeg($img_name);
        $imagevariable = ob_get_contents();
        ob_end_clean();

        $filename = "image3_" . date('Y-m-d H_i_s') . "_" . $delivery_id . ".jpeg";
        /* Rename the image by id */
         $path = "../uploads/product_pics/$filename";
        file_put_contents($path, base64_decode($server_filename));

        $db = new database();
        $db->data("product_file_path", $filename);
        $db->data("delivery_id", $delivery_id);

        $db->insert("product_pics");
    }
    
    
    if ($_REQUEST["is_scan_image"] != "") {

        $server_filename = $_REQUEST["is_scan_image"];

        $server_filename = base64_decode($server_filename);
        $img_name = imagecreatefromstring($server_filename);
       // header('Content-Type: image/jpeg', true);
        ob_start();
        if ($img_name) imagejpeg($img_name);
        $imagevariable = ob_get_contents();
        ob_end_clean();

        $filename = "scan_image_" . date('Y-m-d H_i_s') . "_" . $delivery_id . ".jpeg";
        /* Rename the image by id */
         $path = "../uploads/product_pics/$filename";
        file_put_contents($path, base64_decode($server_filename));

        $db = new database();
        $db->data("product_file_path", $filename);
        $db->data("is_scan_image", 'YES');
        $db->data("delivery_id", $delivery_id);

        $db->insert("product_pics");
    }


    if (1) {
        $db = new database();
        $db->data("qty_delivered", $qty);
        $db->where("item_id=" . $item_id);
        $db->update("delivery_items");


        $db1 = new database();
        $db1->data("driver_notes", $driver_notes);
        $db1->where("delivery_id=" . $delivery_id);
        $db1->update("deliveries");

        $json = array($RESPONSE_KEY => $RESPONSE_SUCCESS, 'msg' => 'Order Details updated successfully', 'info' => $result);
        echo json_encode($json);
    } else {
        $json = array($RESPONSE_KEY => $RESPONSE_ERROR, 'msg' => 'Failed', 'last_id' => '0');
        echo json_encode($json);
    }
} else {
    $json = array($RESPONSE_KEY => $RESPONSE_ERROR, 'msg' => 'Please pass all parameter', 'last_id' => '0');
    echo json_encode($json);
}










/* if ($_FILES[$img1]["name"] != "") {

  $allowedExts = array("jpg", "jpeg", "png");
  $temp_extension = explode(".", $_FILES[$img1]["name"]);
  $extension = end($temp_extension);
  //$extension="jpg";
  $theme_image = substr($_FILES[$img1]["name"], 0, strpos($_FILES[$img1]["name"], '.'));


  if (in_array($extension, $allowedExts)) {

  if ($_FILES[$img]["error"] > 0) {

  echo "Return Code: " . $_FILES[$img]["error"][$i] . "<br />";
  } else {
  $theme_image = ((strlen($theme_image) < 10) ? $theme_image : substr($theme_image, 0, 10));
  $theme_image = str_replace(" ", "_", $theme_image);
  $img_name = $theme_image . "_" . $file_date . "." . ($extension);


  move_uploaded_file($_FILES[$img1]["tmp_name"], $target_dir = ROOTPATH . "/uploads/photo/" . $img_name);


  $db = new database();
  $db->data("product_file_path", $img_name);
  $db->data("delivery_id", $delivery_id);

  $db->insert("product_pics");
  }
  } else {
  $json = array($RESPONSE_KEY => $RESPONSE_SUCCESS, 'msg' => 'Please select file with valid extension (e.g.jpg, jpeg, png,pdf)', 'last_id' => '');
  echo json_encode($json);
  exit();
  }
  }

  if ($_FILES[$img2]["name"] != "") {

  $allowedExts = array("jpg", "jpeg", "png");
  $temp_extension = explode(".", $_FILES[$img2]["name"]);
  $extension = end($temp_extension);
  //$extension="jpg";
  $theme_image = substr($_FILES[$img2]["name"], 0, strpos($_FILES[$img2]["name"], '.'));


  if (in_array($extension, $allowedExts)) {

  if ($_FILES[$img]["error"] > 0) {

  echo "Return Code: " . $_FILES[$img2]["error"][$i] . "<br />";
  } else {
  $theme_image = ((strlen($theme_image) < 10) ? $theme_image : substr($theme_image, 0, 10));
  $theme_image = str_replace(" ", "_", $theme_image);
  $img_name = $theme_image . "_" . $file_date . "." . ($extension);


  move_uploaded_file($_FILES[$img2]["tmp_name"], $target_dir = ROOTPATH . "/uploads/product_pics/" . $img_name);


  $db = new database();
  $db->data("product_file_path", $img_name);
  $db->data("delivery_id", $delivery_id);

  $db->insert("product_pics");
  }
  } else {
  $json = array($RESPONSE_KEY => $RESPONSE_SUCCESS, 'msg' => 'Please select file with valid extension (e.g.jpg, jpeg, png,pdf)', 'last_id' => '');
  echo json_encode($json);
  exit();
  }
  }

  if ($_FILES[$img3]["name"] != "") {

  $allowedExts = array("jpg", "jpeg", "png");
  $temp_extension = explode(".", $_FILES[$img3]["name"]);
  $extension = end($temp_extension);
  //$extension="jpg";
  $theme_image = substr($_FILES[$img3]["name"], 0, strpos($_FILES[$img3]["name"], '.'));


  if (in_array($extension, $allowedExts)) {

  if ($_FILES[$img]["error"] > 0) {

  echo "Return Code: " . $_FILES[$img3]["error"][$i] . "<br />";
  } else {
  $theme_image = ((strlen($theme_image) < 10) ? $theme_image : substr($theme_image, 0, 10));
  $theme_image = str_replace(" ", "_", $theme_image);
  $img_name = $theme_image . "_" . $file_date . "." . ($extension);


  move_uploaded_file($_FILES[$img3]["tmp_name"], $target_dir = ROOTPATH . "/uploads/photo/" . $img_name);


  $db = new database();
  $db->data("product_file_path", $img_name);
  $db->data("delivery_id", $delivery_id);

  $db->insert("product_pics");
  }
  } else {
  $json = array($RESPONSE_KEY => $RESPONSE_SUCCESS, 'msg' => 'Please select file with valid extension (e.g.jpg, jpeg, png,pdf)', 'last_id' => '');
  echo json_encode($json);
  exit();
  }
  }
 */








/* if (isset($item_id) && isset($qty) && $qty > 0) {


  $img_name == "";
  //if (!empty($_FILES) && $delivery_id > 0) {
  $img = "image";
  $file_date = date('m_d_Y_H_i_s');
  //count(array_filter($_FILES['image']['name']))

  for ($i = 0; $i < 3; $i++) {

  $allowedExts = array("jpg", "jpeg", "png");
  $temp_extension = explode(".", $_FILES[$img]["name"][$i]);
  $extension = end($temp_extension);
  //$extension="jpg";
  $theme_image = substr($_FILES[$img]["name"][$i], 0, strpos($_FILES[$img]["name"][$i], '.'));


  if (in_array($extension, $allowedExts)) {

  if ($_FILES[$img]["error"] > 0) {

  echo "Return Code: " . $_FILES[$img]["error"][$i] . "<br />";
  } else {
  $theme_image = ((strlen($theme_image) < 10) ? $theme_image : substr($theme_image, 0, 10));
  $theme_image = str_replace(" ", "_", $theme_image);
  $img_name = $theme_image . "_" . $file_date . "." . ($extension);


  move_uploaded_file($_FILES[$img]["tmp_name"][$i], $target_dir = ROOTPATH . "/uploads/photo/" . $img_name);


  $db = new database();
  $db->data("product_file_path", $img_name);
  $db->data("delivery_id", $delivery_id);

  $db->insert("product_pics");
  }
  } else {
  $json = array($RESPONSE_KEY => $RESPONSE_SUCCESS, 'msg' => 'Please select file with valid extension (e.g.jpg, jpeg, png,pdf)', 'last_id' => '');
  echo json_encode($json);
  exit();
  }
  }

  if (1) {
  $db = new database();
  $db->data("qty_delivered", $qty);
  $db->where("item_id=" . $item_id);
  $db->update("delivery_items");


  $db1 = new database();
  $db1->data("driver_notes", $driver_notes);
  $db1->where("delivery_id=" . $delivery_id);
  $db1->update("deliveries");

  $json = array($RESPONSE_KEY => $RESPONSE_SUCCESS, 'msg' => 'Item quantity updated successfully', 'info' => $result);
  echo json_encode($json);
  } else {
  $json = array($RESPONSE_KEY => $RESPONSE_ERROR, 'msg' => 'Failed', 'last_id' => '0');
  echo json_encode($json);
  }
  } else {
  $json = array($RESPONSE_KEY => $RESPONSE_ERROR, 'msg' => 'Please pass all parameter', 'last_id' => '0');
  echo json_encode($json);
  } */
?>


