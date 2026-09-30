<?php
include_once(__DIR__ . '/request_filter.php');


$app_key = $_REQUEST['app_key'];

include_once("../bootstrap.php");
include_once('message.php');

$user_name = $_REQUEST['acct_username'];
$password = md5($_REQUEST['acct_password']);
$imei_no = $_REQUEST['imei_no'];


$result = array();
$data = array();
if (isset($user_name) && isset($password)) {

    $db = new database();
    $query = "SELECT * FROM users WHERE (acct_username ='" . sanitize($user_name) . "' OR  email_addr='" . sanitize($user_name) . "') AND acct_password='" . sanitize($password) . "'";
    $db->myCustomQuery($query);
    if ($db->row_count > 0) {

        $data['user_id'] = $db->f_user_id;
        $data['status'] = $db->f_status;
        $data['first_name'] = $db->f_first_name;
        $data['last_name'] = $db->f_last_name;
        $data['email_addr'] = $db->f_email_addr;
        $data['acct_username'] = $db->f_acct_username;
        $data['acct_password'] = $db->f_acct_password;
        $data['access_level'] = $db->f_access_level;
        $data['is_driver'] = $db->f_is_driver;
        $data['default_route_id'] = $db->f_default_route_id;
        $data['current_route_id'] = $db->f_current_route_id;

        array_push($result, $data);

        //UPDATE IMEI NUMBER
        $db_update_imei_no = new database();
        $db_update_imei_no->data("imei_no", $imei_no);
        $db_update_imei_no->where("user_id=" . $db->f_user_id);
        $db_update_imei_no->update("users");



        $json = array($RESPONSE_KEY => $RESPONSE_SUCCESS, 'msg' => 'Success', 'info' => $result);
        echo json_encode($json);
    } else {
        $json = array($RESPONSE_KEY => $RESPONSE_ERROR, 'msg' => 'Failed', 'last_id' => '0');
        echo json_encode($json);
    }
} else {
    $json = array($RESPONSE_KEY => $RESPONSE_ERROR, 'msg' => 'Please pass all parameter', 'last_id' => '0');
    echo json_encode($json);
}
?>

