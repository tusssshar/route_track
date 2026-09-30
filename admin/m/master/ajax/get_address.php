<?php   $HST = $_SERVER['SERVER_NAME'];;
        $USR = "root";
        $PWD = ""; 
        $DBN = "RouteTrac_template_repository";
        //$objGlobalConnection->disconnect();
        // $objGlobalConnection->disconnect();
        $conn1 = new mysqli($HST, $USR, $PWD, $DBN);
        /* check connection */
        if (mysqli_connect_errno()) {
            printf("Connect failed: %s\n", $this->conn->connect_error);
            exit();
        }
        printf ("System status: %s\n", $conn1->stat());
       
    
        $cust_id=$_POST['cust_id'];
       // printf ("System status: %s\n", $conn_obj->stat());
        $sql = "SELECT address_id,address_line_1,address_line_2,address_line_3 	 FROM customer_addresses WHERE  customer_id='".$cust_id."'";
        $result =$conn1->query($sql);
       
          ?>

     
          <option value="">Select one</option>
                 <?php
                    while($row = $result->fetch_array()) {
             ?>
          <option value="<?php echo $row['address_id']?>" <?php if($row['address_id']==1){ echo "selected";}?>><?php echo $row['address_line_1']."".$row['address_line_2']."".$row['address_line_3'];?></option>  
                
<?php
        
        }
        ?>
                            
 


     

        