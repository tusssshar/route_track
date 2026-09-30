<?php
include_once(__DIR__ . '/request_filter.php');

$app_key = $_REQUEST['app_key'];
$delivery_id = $_REQUEST['delivery_id'];
include_once("../bootstrap.php");
include_once('message.php');

$img_name == "";
//if (!empty($_FILES) && $delivery_id > 0) {
    $img = "image";
    $file_date = date('m_d_Y_H_i_s');

    $allowedExts = array("jpg", "jpeg", "png");
    $temp_extension = explode(".", $_FILES[$img]["name"]);
    $extension = strtolower(end($temp_extension));
    //$extension="jpg";
    $theme_image = substr($_FILES[$img]["name"], 0, strpos($_FILES[$img]["name"], '.'));


    if (in_array($extension, $allowedExts)) {

        if ($_FILES[$img]["error"] > 0) {

            echo "Return Code: " . $_FILES[$img]["error"] . "<br />";
        } else {
            $theme_image = ((strlen($theme_image) < 10) ? $theme_image : substr($theme_image, 0, 10));
            $theme_image = str_replace(" ", "_", $theme_image);
            $img_name = $theme_image . "_" . $file_date . "." . ($extension);


            move_uploaded_file($_FILES[$img]["tmp_name"], $target_dir = ROOTPATH . "/uploads/photo/" . $img_name);


            $db = new database();
            $db->data("server_filename", $img_name);
            $db->data("delivery_id", $delivery_id);

            $db->insert("driver_photos");
            
             $json = array($RESPONSE_KEY => $RESPONSE_SUCCESS, 'msg' => 'Photo successfully uploaded', 'info' => $result);
        echo json_encode($json);
            
        }
    } else {
        $json = array($RESPONSE_KEY => $RESPONSE_SUCCESS, 'msg' => 'Please select file with valid extension (e.g.jpg, jpeg, png,pdf)', 'last_id' => '');
        echo json_encode($json);
        exit();
    }
/*} else {
    $json = array($RESPONSE_KEY => $RESPONSE_ERROR, 'msg' => 'Please pass all parameter', 'last_id' => '0');
    echo json_encode($json);
}*/
?>

