<?php
include("../../bootstrap.php");

// every value below is concatenated into SQL: escape once here (names such as "Joe's" otherwise break the query)
foreach ($_REQUEST as $req_key => $req_value) {
    if (is_string($req_value)) {
        $_REQUEST[$req_key] = sanitize($req_value);
    }
}

$request=$_REQUEST["action"];
/////////swich case to which action performated
switch ($request)
{ 
   case "check_route":
        $route_name=$_REQUEST["route_name"];
       
        $db_check_route=new database();
        $query="SELECT name FROM routes WHERE name='$route_name'";
        $db_check_route->myCustomQuery($query);
        if($db_check_route->row_count>0)
        {
            echo "false";
        }
        else
        {
            echo "true";
        }
        break;
        
   case "check_email":
       $user_id=$_REQUEST['hidden_user_id'];
       $email=$_REQUEST['email_addr'];
       $db_check_email=new database();
        $query="SELECT email_addr FROM users WHERE email_addr='$email' AND user_id !='$user_id'";
        $db_check_email->myCustomQuery($query);
        if($db_check_email->row_count>0)
        {
            echo "false";
        }
        else
        {
            echo "true";
        }
       
       break;
       
       case "check_user_name":
      
       $acct_username=$_REQUEST['acct_username'];
       $user_id=$_REQUEST['hidden_user_id'];
       $db_check_username=new database();
        $query="SELECT acct_username FROM users WHERE acct_username='$acct_username' AND user_id !='$user_id'";
        $db_check_username->myCustomQuery($query);
        if($db_check_username->row_count>0)
        {
            echo "false";
        }
        else
        {
            echo "true";
        }
       
       break;
       
        case "check_area":
        $area_name=$_REQUEST['area_name'];
        $area_id=$_REQUEST['hidden_area_id'];
        $db_check_area=new database();
        $query="SELECT name FROM areas WHERE name='$area_name' AND area_id !='$area_id'";
        $db_check_area->myCustomQuery($query);
        if($db_check_area->row_count>0)
        {
            echo "false";
        }
        else
        {
            echo "true";
        }
       
       break;
       
       case "check_package":
        $pack_id=$_REQUEST['hidden_package_id'];
        $pack_name=$_REQUEST['name'];
        $db_check_package=new database();
        $query="SELECT name FROM package_types WHERE name='$pack_name' and package_type_id !='$pack_id'";
        $db_check_package->myCustomQuery($query);
        if($db_check_package->row_count>0)
        {
            echo "false";
        }
        else
        {
            echo "true";
        }
       
       break;
       
       case "check_acct_no":
        $cust_id=$_REQUEST['hidden_cust_id'];
        $cust_act_no=$_REQUEST['cust_act_no'];
        $db_check_act_no=new database();
        $query="SELECT account_number FROM customers WHERE account_number='$cust_act_no' and customer_id !='$cust_id'";
        $db_check_act_no->myCustomQuery($query);
        if($db_check_act_no->row_count>0)
        {
            echo "false";
        }
        else
        {
            echo "true";
        }
       
       break;
       
       case "check_route_area":
       $route_id=$_REQUEST['hidden_route_id'];
       $name=$_REQUEST['route_name'];
       if($_REQUEST['area_id']>0){
           $area_name=$_REQUEST['area_id'];
       }else{
           $area_name=$_REQUEST['area_name'];
       }
       
       $db_check_route_area=new database();
       $query="SELECT name FROM  routes WHERE name='$name' and area_id='$area_name'and route_id !='$route_id'";
        $db_check_route_area->myCustomQuery($query);
        if($db_check_route_area->row_count>0)
        {
            echo "false";
        }
        else
        {
            echo "true";
        }
       
       break;
       
       case "get_address":
        $cust_id=$_REQUEST['cust_id'];
        $addr="address_line_1,address_line_2,address_line_3";
        
        ?>
            <?=$functions->createCombo("customer_addresses","address_id",$addr,$db->f_address_id," order by address_line_1 asc","customer_id=".$cust_id); ?>     

       
        <?php
       break;
   
    case "delete_item":
        $item_id=$_REQUEST['item_id'];
        $db = new database();
        $db->where("item_id=".$item_id);
        $db->delete("delivery_items");
       break;
   
   case "unset_error_msg":
        $type=$_REQUEST["type"];
        unset($_SESSION[$type]["error_success_msg"]);
        unset($_SESSION[$type]["error_success_msg"]);
       break;
   case "check_default_area":
       $db_check_area=new database();
       $area_id=$_REQUEST['hidden_area_id'];
       $is_default=$_REQUEST['is_default'];
       $query="SELECT area_id FROM  areas WHERE area_id ='$area_id' AND is_default=1";
        $db_check_area->myCustomQuery($query);
        if($db_check_area->row_count>0 && $is_default==0)
        {
            echo "false";
        }
        else
        {
            echo "true";
        } 
       break;
       
   case "get_packages_list":
      ?>
       <select name="package_id[]" id="package_id" class="form-control">
              <option value="">Select one</option>
          
                <?=$functions->createCombo("package_types","package_type_id","name",""," order by name asc"); ?>
       </select>
          <?php
       break;
   
   case "get_delivery_table":
       
      $delete_id=$_REQUEST['delete_id'];
      
        if($delete_id > 0){
                $db1 = new database();
		$db1->where("delivery_id=".$delete_id);
		$db1->delete("deliveries");  
	}
      $manifest_id=$_REQUEST['manifest_id'];
        $db_deliveries = new database();
	$query = "SELECT SQL_CALC_FOUND_ROWS
                M.manifest_type,
                M.manifest_id,
                SB.first_name as mfirst_name,
                SB.last_name as mlast_name,
                D.cust_po_num,
                D.delivery_notes,
                D.driver_notes,
                D.scheduled_delivery,
                D.signature_text,
                D.delivery_id,
                D.status,
                D.stop_number,
                D.order_number,
                C.customer_name,
                C.account_number,
                CA.address_line_1,
                CA.address_line_2,
                CA.address_line_3,
                CA.city,
                CA.state,
                CA.zip,
                CA.phone,
                U.first_name,
                U.last_name,
                R.name
                    FROM
                    manifests M LEFT JOIN deliveries D ON M.manifest_id=D.manifest_id
                    LEFT JOIN customers C ON D.customer_id=C.customer_id
                    LEFT JOIN routes R ON D.route_id=R.route_id
                    LEFT JOIN customer_addresses CA ON D.address_id=CA.address_id
                    LEFT JOIN users U ON D.driver_id=U.user_id
                    LEFT JOIN users SB ON M.submitted_by_user_id=SB.user_id WHERE D.manifest_id='".$manifest_id."'";
        	$db_deliveries->myCustomQuery($query);
                if($db_deliveries->row_count > 0){
				for($i=0;$i<$db_deliveries->row_count;$i++){
					$db_deliveries->moveto($i);
                                          $show_type=$db_deliveries->f_manifest_type;
                                        ?>
          
          
          <tr>
              <td></td>
              <td><?=$db_deliveries->f_manifest_type?></td>
              <td><?=$db_deliveries->f_manifest_id?></td>
              <td><?=$db_deliveries->f_name?></td>
              <td><?=$db_deliveries->f_first_name." ".$db_deliveries->f_last_name?></td>
              <td><?=$db_deliveries->f_stop_number?></td>
              <td><?=$db_deliveries->f_customer_name?></td>
              <td><?=$db_deliveries->f_address_line_1." ".$db_deliveries->f_address_line_2." ".$db_deliveries->f_address_line_3." ".$db_deliveries->f_city." ".$db_deliveries->f_state." ".$db_deliveries->f_zip?></td>
              <td><?=$db_deliveries->f_status?></td>
              <td><?=$db_deliveries->f_order_number?></td>
              <td><?=$db_deliveries->f_cust_po_num?></td>
              <td class="text-center" onclick='event.stopPropagation();return false;'>
             <a href="javascript:void(0);" onclick="javascript:ajax_edit_deliveries(<?=$db_deliveries->f_delivery_id;?>,<?=$db_deliveries->f_manifest_id?>);" title="Edit"><i class="fa fa-pencil-square-o fa-size"></i></a> &nbsp;&nbsp;
            <a href="javascript:void(0);" onclick="javascript:deleteGridRecord_delivery('<?=SITEROOT?>/admin/ajax/admin_ajax_action.php?action=get_delivery_table&delete_id=<?=$db_deliveries->f_delivery_id?>&manifest_id=<?=$db_deliveries->f_manifest_id?>','<?=$db_list->f_delivery_id?>');" title="Delete"><i class="fa fa-trash-o fa-size"></i></a>
          </td>
          </tr>
          <?php
          
                                }
                }else{
			?>
        <tr>
          <td align='center' colspan="100%" style="height:40px;">No Record found</td>
        </tr>
        <?php
			}
			
    
       
       break;
   case "get_item_details":
     $delivery_id=$_REQUEST['delivery_id'];
       ?>
        
        
       <table width="100%">
                            <tr>
                            <th>SKU</th>
                            <th>Qty</th>
                            <th>Name</th>
                            <th>Desc</th>
                            </tr>
                         <?php
          $db_item=new database();        
	$query1 = "SELECT SQL_CALC_FOUND_ROWS DI.sku,DI.qty,DI.product_name,DI.description FROM deliveries D LEFT JOIN  delivery_items DI ON D.delivery_id=DI.delivery_id WHERE DI.delivery_id='".$delivery_id."'";
                  $db_item->myCustomQuery($query1);
                   
                   if($db_item->row_count > 0){
				
				for($i=0;$i<$db_item->row_count;$i++){
					$db_item->moveto($i);
                                        ?>
                                <tr>
                                    <td><?=$db_item->f_sku;?></td>
                                    <td><?=$db_item->f_qty;?></td>
                                    <td><?=$db_item->f_product_name;?></td>
                                    <td><?=$db_item->f_description;?></td>
                                </tr>
                           
                            <?php
                                        
                                }
                   }
        
                    ?>
                         </table>
        <?php
       
       
       break;
   
}

function is_sha1($str) {
    return (bool) preg_match('/^[0-9a-f]{40}$/i', $str);
}

function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

?>
