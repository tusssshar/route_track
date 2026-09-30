<?php
include_once(__DIR__ . '/request_filter.php');


include_once("../bootstrap.php");
include_once('message.php');
if (isset($_REQUEST['app_key']) && $_REQUEST['app_key'] != "") {
    $app_key = $_REQUEST['app_key'];

    $result = array();
    $objGlobalConnection = new dbSqlConnection();
    $oGlobalConnection = $objGlobalConnection->Connect(HST_1, USR_1, PWD_1, DBN_1);
    $db = new database();
    $query = "SELECT client_id,url_domain FROM app_keys WHERE app_key ='" . $app_key . "'";

    $db->myCustomQuery($query);
    $data = array();

    if ($db->row_count > 0) {

        $client_id = $db->f_client_id;
        $url_domain = $db->f_url_domain;

        // $objGlobalConnection = new dbSqlConnection();
        // $oGlobalConnection = $objGlobalConnection->Connect(HST, USR, PWD, DBN);

        $db1 = new database();
        $query1 = "SELECT  client_name,repository_db  FROM clients WHERE client_id='" . $client_id . "'";
        $db1->myCustomQuery($query1);


        $data['customer_name'] = $db1->f_client_name;
        $data['repository_db'] = $db1->f_repository_db;
        $data['client_id'] = $client_id;
        $data['url_domain'] = $url_domain;


        array_push($result, $data);


        $json = array($RESPONSE_KEY => $RESPONSE_SUCCESS, 'msg' => 'Success', 'info' => $result);
        echo json_encode($json);
    } else {
        $json = array($RESPONSE_KEY => $RESPONSE_ERROR, 'msg' => 'Failed', 'last_id' => '0');
        echo json_encode($json);
    }
} else {
    $json = array($RESPONSE_KEY => $RESPONSE_ERROR, 'msg' => 'Please pass the licence key', 'last_id' => '0');
    echo json_encode($json);
}
?>


