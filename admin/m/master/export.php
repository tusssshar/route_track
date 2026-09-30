<?php
session_start();
include_once("../../../bootstrap.php");
$data1=array(); 
     $db_list = new database();
  if ($_REQUEST['value']=="export_area")
{      
     $query = "SELECT name,is_default FROM areas";
	
    $db_list->myCustomQuery($query);
     for($i=0;$i<$db_list->row_count;$i++){
	$db_list->moveto($i);
        $data1[]=array($db_list->f_name,$db_list->f_is_default);
     }
  
header('Content-type: text/csv');
header('Content-Disposition: attachment; filename="AREA.csv"');
 
// create a file pointer connected to the output stream
$file = fopen('php://output', 'w');
 
// send the column headers
fputcsv($file, array('Name','IsDefault'));
 
// output each row of the data
foreach ($data1 as $row)
{
    fputcsv($file, $row);
}   
 exit();
}

if ($_REQUEST['value']=="export_route")
{
   
    $query ="select A.name AS area_name,R.name AS route_name FROM routes R LEFT JOIN areas A ON R.area_id=A.area_id";
   
     $db_list->myCustomQuery($query);
     for($i=0;$i<$db_list->row_count;$i++){
	$db_list->moveto($i);
        $data1[]=array($db_list->f_area_name,$db_list->f_route_name);
     }
    
    //$filename="export".date();
 header('Content-type: text/csv');
 header('Content-Disposition: attachment; filename="ROUTE.csv"');
 
// do not cache the file
header('Pragma: no-cache');
header('Expires: 0');
 
// create a file pointer connected to the output stream
$file = fopen('php://output', 'w');
 
// send the column headers
fputcsv($file, array('Area_Name','Route_Name'));

foreach ($data1 as $row)
{
    fputcsv($file, $row);
}
exit();
}

if ($_REQUEST['value']=="export_package")
{
  
    $query = "SELECT name FROM package_types";
   
    $db_list->myCustomQuery($query);
     for($i=0;$i<$db_list->row_count;$i++){
	$db_list->moveto($i);
        $data1[]=array($db_list->f_name);
     }
   
    //$filename="export".date();
 header('Content-type: text/csv');
 header('Content-Disposition: attachment; filename="PACKAGE_TYPE.csv"');
 
// do not cache the file
header('Pragma: no-cache');
header('Expires: 0');
 
// create a file pointer connected to the output stream
$file = fopen('php://output', 'w');
 
// send the column headers
fputcsv($file, array('Name'));

foreach ($data1 as $row)
{
    fputcsv($file, $row);
}
exit();
}

if ($_REQUEST['value']=="export_customer")
{
    
    $query ="SELECT customer_name,account_number FROM customers";
   
    $db_list->myCustomQuery($query);
     for($i=0;$i<$db_list->row_count;$i++){
	$db_list->moveto($i);
        $data1[]=array($db_list->f_customer_name,$db_list->f_account_number);
     }
   
    //$filename="export".date();
 header('Content-type: text/csv');
 header('Content-Disposition: attachment; filename="CUSTOMERS.csv"');
 
// do not cache the file
header('Pragma: no-cache');
header('Expires: 0');
 
// create a file pointer connected to the output stream
$file = fopen('php://output', 'w');
 
// send the column headers
fputcsv($file, array('Customer_Name',"Account_Number"));

foreach ($data1 as $row)
{
    fputcsv($file, $row);
}
exit();
}

if ($_REQUEST['value']=="export_users")
{
    $query = "SELECT AL.name AS access_level,U.status,U.first_name,U.last_name,U.email_addr,U.acct_username, U.acct_password,U.is_driver,R.name AS route_name FROM users U LEFT JOIN ".DBN_1.".access_levels AL ON U.access_level=AL.level_id LEFT JOIN routes R ON U.default_route_id=R.route_id";
   
    
     $db_list->myCustomQuery($query);
     for($i=0;$i<$db_list->row_count;$i++){
	$db_list->moveto($i);
        $data1[]=array($db_list->f_status,$db_list->f_first_name,$db_list->f_last_name,$db_list->f_email_addr,$db_list->f_acct_username,$db_list->f_acct_password,$db_list->f_access_level,$db_list->f_is_driver,$db_list->f_route_name);
     }
    
    //$filename="export".date();
 header('Content-type: text/csv');
 header('Content-Disposition: attachment; filename="USERS.csv"');
 
// do not cache the file
header('Pragma: no-cache');
header('Expires: 0');
 
// create a file pointer connected to the output stream
$file = fopen('php://output', 'w');
 
// send the column headers
fputcsv($file, array("Status","First_Name","Last_Name","Email_ID","User_Name","Password","Access_Level","Is_Driver","Default_Root"));

foreach ($data1 as $row)
{
    fputcsv($file, $row);
}
exit();
}

if ($_REQUEST['value']=="export_customer_address")
{
    
    $query = "SELECT C.customer_name,CD.ship_to_name,CD.address_line_1,CD.address_line_2,CD.address_line_3,CD.city,CD.state,CD.zip,CD.phone FROM customer_addresses CD LEFT JOIN customers C ON CD.customer_id=C.customer_id";
  
     $db_list->myCustomQuery($query);
     for($i=0;$i<$db_list->row_count;$i++){
	$db_list->moveto($i);
        $data1[]=array($db_list->f_customer_name,$db_list->f_ship_to_name,$db_list->f_address_line_1,$db_list->f_address_line_2,$db_list->f_address_line_3,$db_list->f_city,$db_list->f_state,$db_list->f_zip,$db_list->f_phone);
     }
    
    //$filename="export".date();
 header('Content-type: text/csv');
 header('Content-Disposition: attachment; filename="CUSTOMER_ADDRESSES.csv"');
 
// do not cache the file
header('Pragma: no-cache');
header('Expires: 0');
 
// create a file pointer connected to the output stream
$file = fopen('php://output', 'w');
 
// send the column headers
fputcsv($file, array("Customer_Name","Ship_To_Name","Address_Line_1","Address_Line_2","Addreess_Line_3","City","State","Zip","Phone"));

foreach ($data1 as $row)
{
    fputcsv($file, $row);
}
exit();
}
if ($_REQUEST['value']=="export_manifest")
{
$query = "SELECT 
D.manifest_id,   
D.delivery_id, 
M.manifest_type,
M.num_packages,
M.num_items, 
CONCAT(U.first_name,U.last_name) AS Driver_Name,
D.stop_number,
D.order_number,
D.cust_po_num,
D.status,
C.customer_name,
C.account_number,
CD.ship_to_name,
CD.address_line_1,
CD.address_line_2,
CD.address_line_3,
CD.city,
CD.state,
CD.zip,
CD.phone,
PT.name AS Package_Type,
DI.sku,
DI.qty,
DI.product_name,
DI.description,
DI.uom,
DI.qty_delivered  AS Item_qty_Delivered,
R.name AS Route_Name,
DP.qty AS Package_qty,
DP.qty_delivered  AS Package_qty_Delivered 
FROM manifests M
 JOIN deliveries D ON M.manifest_id=D.manifest_id 
LEFT JOIN customers C ON D.customer_id=C.customer_id 
LEFT JOIN delivery_items DI ON D.delivery_id=DI.delivery_id 
LEFT JOIN routes R ON R.route_id=D.route_id 
LEFT JOIN users U ON D.driver_id=U.user_id 
LEFT JOIN delivery_packages DP ON D.delivery_id=DP.delivery_id 
LEFT JOIN package_types PT ON PT.package_type_id=DP.package_type_id
LEFT JOIN customer_addresses CD ON C.customer_id=CD.customer_id WHERE D.manifest_id='".$_SESSION['manifest_id']."'";//GROUP BY D.delivery_id

     $db_list->myCustomQuery($query);
     for($i=0;$i<$db_list->row_count;$i++){
	$db_list->moveto($i);
        $data1[]=array($db_list->f_manifest_type,$db_list->f_Route_Name,$db_list->f_Driver_Name,$db_list->f_stop_number,$db_list->f_account_number,$db_list->f_customer_name,$db_list->f_address_line_1,$db_list->f_address_line_2,$db_list->f_address_line_3,$db_list->f_city,$db_list->f_state,$db_list->f_zip,$db_list->f_phone,$db_list->f_status,$db_list->f_order_number,$db_list->f_cust_po_num,$db_list->f_Package_qty,$db_list->f_Package_qty_Delivered,$db_list->f_Package_Type,$db_list->f_sku,$db_list->f_qty,$db_list->f_product_name,$db_list->f_description,$db_list->f_uom,$db_list->f_Item_qty_Delivered);
     }
  
    //$filename="export".date();
 header('Content-type: text/csv');
 header('Content-Disposition: attachment; filename="MANIFEST.csv"');
 
// do not cache the file
header('Pragma: no-cache');
header('Expires: 0');
 
// create a file pointer connected to the output stream
$file = fopen('php://output', 'w');
 
// send the column headers
fputcsv($file, array("Type","Route","Driver","Stop_No","Acct_No","Customer_Name","Address_Line_1","Address_Line_2","Address_Line_3","City","State","ZIP","Phone","Status","Order No.","Po No.","Package_Qty","Package Delivered","Package_Type","SKU","Qty","Name","Desc","UOM","Item Delivered"));

foreach ($data1 as $row)
{
    fputcsv($file, $row);
}
exit();
}



?>

