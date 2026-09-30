<?php
include_once(__DIR__ . '/request_filter.php');


$app_key = $_REQUEST['app_key'];
include_once("../bootstrap.php");
include_once('message.php');

$reason = $_REQUEST['reason'];
$note = $_REQUEST['note'];
$delivery_id_array = explode(",", $_REQUEST['delivery_ids']);
$data = array();

if (isset($reason)) {
    foreach ($delivery_id_array as $delivery_id) {
        $db_update_order_status = new database();
        $db_update_order_status->data("exception_reason", $reason);
        $db_update_order_status->data("exception_note", $note);

        if ($reason == "Refused Delivery") {
            $db_update_order_status->data("status", 'Refused');
        } else {
            $db_update_order_status->data("status", 'Exception');
        }


        $db_update_order_status->where("delivery_id=" . $delivery_id);
        $db_update_order_status->update("deliveries");
    }
    $json = array($RESPONSE_KEY => $RESPONSE_SUCCESS, 'msg' => 'Order details updated successfully', 'info' => '');
    echo json_encode($json);
} else {
    $json = array($RESPONSE_KEY => $RESPONSE_ERROR, 'msg' => 'Failed', 'last_id' => '0');
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


  move_uploaded_file($_FILES[$img1]["tmp_name"], $target_dir = $_SERVER['DOCUMENT_ROOT'] . "/uploads/photo/" . $img_name);


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


  move_uploaded_file($_FILES[$img2]["tmp_name"], $target_dir = $_SERVER['DOCUMENT_ROOT'] . "/uploads/product_pics/" . $img_name);


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


  move_uploaded_file($_FILES[$img3]["tmp_name"], $target_dir = $_SERVER['DOCUMENT_ROOT'] . "/uploads/photo/" . $img_name);


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


  move_uploaded_file($_FILES[$img]["tmp_name"][$i], $target_dir = $_SERVER['DOCUMENT_ROOT'] . "/uploads/photo/" . $img_name);


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


