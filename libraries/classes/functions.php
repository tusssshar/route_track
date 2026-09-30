<?php
	function quotes_to_entities($str){
		return str_replace(array("\'","\"","'",'"','<', '>'), array("&#39;","&quot;","&#39;","&quot;","&lt;","&gt;"), $str);
	}	
	
	function masterDBInfo(){
		$host = HST;
		$rootPass = PWD_1;
		$masterDB = DBN_1;
		return array('65.60.3.216','?b6ArR-tUsu)',$masterDB);
	}
	class functions extends clsPagination{
	
		function __construct(){
			$this->functions();
		}

		function functions(){

		}
		//------------------------------------------------------------------------------------------------------------------
		
		function getTypeName($tablename,$id,$name,$value){
			$_name = "";
			
			$db = new sDatabase();
			$db->where($id."='".$value."'");
			$db->selectstmt($tablename,$name);
			$db->stmt;
			$del = "";
			if($db->row_count > 0){
				$aNames = explode(",",$name);
				foreach($aNames as $key=>$value){
					$_name.= $del.$db->{"f_".$value};
					$del = " ";
				}
			}
			$db->free_result($db->result);
			return $_name;
		}
		//------------------------------------------------------------------------------------------------------------------
		
		function createCombo($tablename,$id,$name,$selected_id=0,$order_by="*",$where="+",$selectFields="*"){
                        
			$dis = explode(",",$name);
			$db = new database();
			if($order_by == "*" and $where == "+")	{		$wh = "1=1 ";		}
			if($order_by != "*" and $where == "+")	{		$wh = "1=1 ".$order_by;		}
			if($order_by == "*" and $where != "+")	{		$wh = $where."";		}
			if($order_by != "*" and $where != "+")	{		$wh = $where." ".$order_by;		}
			$db->where($wh);
			$db->selectstmt($tablename,$selectFields);
                        //echo $db->stmt;
			
			//echo $db->stmt;
			
			if($db->row_count > 0)
			{
				for($m=0;$m<$db->row_count;$m++)
				{
					$db->moveto($m);
					//echo $db->{"f_".$id}."-".$selected_id."<br>";
					if($db->{"f_".$id} == $selected_id)
					{
						$mm = "selected='selected'";
					}
					else	
					{	
						$mm = " ";		
					}
					//$mr.="<option value='".$db->{"f_".$id}."'".$mm.">".$db->{"f_".$name}."</option>"; 
					$mr.= "<option value='".$db->{"f_".$id}."' ".$mm.">";
					if(sizeof($dis) == 1)
					{
						$mr.=$db->{"f_".$dis[0]};
					}
					else
					{
						for($i=0;$i<sizeof($dis);$i++)
						{
							$mr.=$db->{"f_".$dis[$i]}." ";
						}
					}
					$mr.="</option>";
					
				}
			}
			else
			{
				return "Table is empty";
			}
			return $mr;
		}
		//------------------------------------------------------------------------------------------------------------------
		
		function send_mail($email,$subject,$message)
		{
			//$from_mail = '';
			//$headers = "MIME-Version: 1.0" . "\r\n";
			//$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
			//$headers .= "From:$from_mail" . "\r\n";
			//mail($email,$subject,$message,$headers);
			$email_message = file_get_contents(SITEROOT."/m/ajax/email_template.php");
			$message = str_replace("[Main Content]",$message,$email_message);
			$message = chunk_split(base64_encode($message));
			$headers = 'From: nitinr@aosware.com' . "\r\n" ;
			$headers .='Reply-To: '. $email . "\r\n" ;
			$headers .='X-Mailer: PHP/' . phpversion();
			$headers .= "MIME-Version: 1.0\r\n";
			$headers .= "Content-type: text/html; charset=iso-8859-1\r\n";
			$headers .= "Content-Transfer-Encoding: base64\r\n\r\n";
			mail($email, $subject, $message,$headers);
		}
		//------------------------------------------------------------------------------------------------------------------
		
		function generateRandomString($length = 10) {
			$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			$charactersLength = strlen($characters);
			$randomString = '';
			for ($i = 0; $i < $length; $i++) {
				$randomString .= $characters[rand(0, $charactersLength - 1)];
			}
			return $randomString;
		}
		//------------------------------------------------------------------------------------------------------------------
               
                ////////date format change to display
		function date_format_change($date_val){
			$date=date_create($date_val);
			$date_val=date_format($date,"d/m/Y");
			return $date_val;
		}
		//------------------------------------------------------------------------------------------------------------------
				
		function dateTimeDiff($date1, $date2, $format = false) 
		{
			$diff = date_diff( date_create($date1), date_create($date2) );
			if ($format)
				return $diff->format($format);
			
			return array('y' => $diff->y,
					'm' => $diff->m,
					'd' => $diff->d,
					'h' => $diff->h,
					'i' => $diff->i,
					's' => $diff->s
				);
		}
		//------------------------------------------------------------------------------------------------------------------
                
                ////////date format change to display
		function mysql_date_format_change($date_val)
		{
			$date_part=explode("/",$date_val);
			$d=$date_part[0];
			$m=$date_part[1];
			$y=$date_part[2];
			return $created_date=$y."-".$m."-".$d;
			//$date=date_create($created_date);
			//$date_val=date_format($date,"Y-m-d");
			//return $date_val;
		}
		//------------------------------------------------------------------------------------------------------------------
              
		function change_url_to_library_path($path)
		{
			$path=str_replace(SITEROOT,ROOTPATH,$path);
			$path=str_replace('http://65.60.3.201/~vaahjob','/home/vaahjob/public_html/',$path);
			return $path;
		}
		//------------------------------------------------------------------------------------------------------------------
				              
		function multi_attach_mail($to, $subject, $message, $senderMail, $senderName, $files,$path){
			$from = $senderName." <".$senderMail.">"; 
			$headers = "From: $from";
			// boundary 
			$semi_rand = md5(time()); 
			$mime_boundary = "==Multipart_Boundary_x{$semi_rand}x"; 
			// headers for attachment 
			$headers .= "\nMIME-Version: 1.0\n" . "Content-Type: multipart/mixed;\n" . " boundary=\"{$mime_boundary}\""; 
			
			$email_message = file_get_contents(SITEROOT."/m/ajax/email_template.php");
			$message = str_replace("[Main Content]",$message,$email_message);
			// multipart boundary 
			$message = "--{$mime_boundary}\n" . "Content-Type: text/html; charset=\"UTF-8\"\n" .
			"Content-Transfer-Encoding: 7bit\n\n" . $message . "\n\n"; 
			// preparing attachments
			if(count($files) > 0){
				for($i=0;$i<count($files);$i++){
					$new_file=$path."/".$files[$i];
					if(is_file($new_file)){
						$message .= "--{$mime_boundary}\n";
						$fp =    fopen($new_file,"rb");
						$data =  fread($fp,filesize($new_file));
						@fclose($fp);
						$data = chunk_split(base64_encode($data));
						$message .= "Content-Type: application/octet-stream; name=\"".basename($files[$i])."\"\n" . 
						"Content-Description: ".basename($files[$i])."\n" .
						"Content-Disposition: attachment;\n" . " filename=\"".basename($files[$i])."\"; size=".filesize($new_file).";\n" . 
						"Content-Transfer-Encoding: base64\n\n" . $data . "\n\n";
					}
				}
			}
			$message .= "--{$mime_boundary}--";
			$returnpath = "-f" . $senderMail;
			//send email
			$mail = mail($to, $subject, $message, $headers, $returnpath); 
			//function return true, if email sent, otherwise return fasle
			if($mail){return TRUE; } else {return FALSE; }
		}
		//------------------------------------------------------------------------------------------------------------------
                
                ////////////check file columns
		function check_csv_columns($standard_coumns,$file_columns)
		{
			$is_true="true";
			$std_no_of_columns=count($standard_coumns);
			$file_no_of_columns=count($file_columns);
			//print_r($standard_coumns);
			//print_r($file_columns);
		//        echo $std_no_of_columns."##".$file_no_of_columns;
		//        echo "<br/>";
			if($std_no_of_columns == $file_no_of_columns)
			{
				for($i=0;$i<$std_no_of_columns;$i++)
				{
					$standard_coumns_name=preg_replace('/\s+/', '', $standard_coumns[$i]);
					$file_columns_name=preg_replace('/\s+/', '', $file_columns[$i]);
					//echo $standard_coumns_name."##".$file_columns_name;
					//echo "<br/>";
					if($standard_coumns_name == $file_columns_name)
					{}
					else
					{
						$_SESSION["employer"]["error_success_msg"]="<span style='color:red'>Spelling mistake</span>";
						$_SESSION["employee"]["error_success_msg"]="<span style='color:red'>Spelling mistake</span>";
						//echo "Spelling mistake";
						$is_true="false";
						break;
					}
				}
			}
			else
			{
				$is_true="false";
			}
			return $is_true;
		}
		//------------------------------------------------------------------------------------------------------------------
                         
                /////////resume downloaded for this job
		function resume_downloaded($cp_id)
		{
			$db_resume_downloaded=new database();
			$job_profiles_str=$this->get_job_profiles($cp_id);
			$already_resume_downloaded_query="select t2.js_id from vjct_company_points t1 inner join vjjm_job_seeker_profiles t2 on t1.cpt_reff_jsp_id=t2.jsp_id where t1.c_id='".$_SESSION["employer"]["c_id"]."' and t1.pc_id='3' and (t1.cpt_reff_post_id='$cp_id' or t1.cpt_reff_post_id IN($job_profiles_str))";
			$db_resume_downloaded->myCustomQuery($already_resume_downloaded_query);
			$resume_down_array=array();
			for($i=0;$i<$db_resume_downloaded->row_count;$i++)
			{
				$db_resume_downloaded->moveto($i);
				$js_id=$db_resume_downloaded->f_js_id;
				array_push($resume_down_array, $js_id);
			}
			return $resume_down_array;
		}
		//------------------------------------------------------------------------------------------------------------------
              
                ////////get last id
		function get_last_id($primary_id,$table_name)
		{
			$db=new database();
			$query="select $primary_id as uni_id from $table_name order by $primary_id desc LIMIT 1";
			$db->myCustomQuery($query);
			$primary_id=$db->f_uni_id;
			return $primary_id+1;
		}
		//------------------------------------------------------------------------------------------------------------------
             
                /////////get access level
		function user_access_level($menu_id,$level_id)
		{
			$db_get_access_level=new database();
			$query="SELECT access_menu_id FROM access_levels WHERE level_id='".$level_id."'";
			$db_get_access_level->myCustomQuery($query);
			$cu_access_level=$db_get_access_level->f_access_menu_id;
			$cu_access_level_arr=  explode(",", $cu_access_level);
			
//                    if($cu_access_level=="ALL_ACCESS")
//                    {
//                        return 1;
//                    }
			if(in_array($menu_id, $cu_access_level_arr))
			{
				return 1;
			}
			else
			{
				return 0;
			}
		}
		//------------------------------------------------------------------------------------------------------------------
                
		
	}
        
       
       
        
      
       
?>