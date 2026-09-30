<?php
include_once("../bootstrap.php");

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    // Get data
    $name = trim($_POST['name']);
    $area_name = trim($_POST['area_name']);

    if (empty($name) || empty($area_name)) {
        $json = array("status" => 0, "msg" => "Error adding route! please enter all required fields");
    } else {
        // Insert data into database
        $db = new database();
        /* check whether area name already exists 
          if yes then insert id or else insert area in area tbl then insert id */
        $check_area = "SELECT name,area_id FROM areas WHERE name='$area_name'";
        $db->myCustomQuery($check_area);
        if ($db->row_count > 0) {
            $area_id = $db->f_area_id;
        } else {
            $db_insert = new database();
            $db_insert->data("name", $area_name);
            $db_insert->insert("areas");
            $area_id = $db_insert->id;
        }
        $db_insert = new database();
        $db_insert->data("name", $name);
        $db_insert->data("area_id", $area_id);
        $db_insert->data("added_by_api", "1");
        $qur = $db_insert->insert("routes");

        if ($qur) {
            $json = array("status" => 0, "msg" => "Error adding route!");
        } else {
            $json = array("status" => 1, "msg" => "New route successfully added");
        }
    }
} else {
    $json = array("status" => 0, "msg" => "Request method not accepted");
}

@mysql_close($conn);

/* Output header */
header('Content-type: application/json');
echo json_encode($json);
?>