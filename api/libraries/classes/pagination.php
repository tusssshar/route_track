<?php
	class clsPagination{

		function clsPagination(){
			
		} 
		
		function showAjaxPaging($displayDiv='MyAjax'){
			global $PAGE_TOTAL_ROWS;
			global $PAGE_LIMIT;
			global $PAGE_URL;
			global $DISPLAY_PAGES;
			global $admin;
			global $functions;

			//echo "<pre>";print_r($_GET);
			//$working_data = 0;
			$numofpages = ceil($PAGE_TOTAL_ROWS / $PAGE_LIMIT);
			$pages = ((empty($_GET['pages']))?1:$_GET['pages']);
			$page = ((empty($_GET['page']))?1:$_GET['page']);
			$filename = $PAGE_URL;
			
			$displayPages = (($DISPLAY_PAGES < 1)?15:$DISPLAY_PAGES);

			if(strlen(trim($filename)) > 0)
			{			
				$file = explode(".",$filename);
				$fileName = $file[0];
				$file = explode("-",$file[1]);
				
				if(sizeof($file) == 1){
					$_file = $fileName.".".$file[0]."?";
				}
				else{
					for($m=1;$m<sizeof($file);$m++)	{	$fn.= $file[$m]."&";	}
					$_file = $fileName.".".$file[0]."?".$fn;
				}
			}
			

			if($pages > 1){
				$pageprev = ($pages - $displayPages);
				$pageprev = ($pageprev > 0?$pageprev:1);
				$working_data ="<li><a href=\"javascript:void(0);\" onclick=\"javascript:showAjaxPaging('#".$displayDiv."','ajax/".$_file."pages=".$pageprev."&page=".$pageprev."')\">PREV</a></li>";
			}
			
			
			for($i = $pages; $i < $pages + $displayPages; $i++){
				if($i <= $numofpages) {
					$selectedPage = (($page == $i)?"style='font-weight:bold;background-color:#EBEBEB'":"");
					$coteStart = (($page == $i)?"(":"[");
					$coteEnd = (($page == $i)?")":"]");
					
					$working_data.="<li><a href=\"javascript:void(0);\" $selectedPage onclick=\"javascript:showAjaxPaging('#".$displayDiv."','ajax/".$_file."pages=".$pages."&page=".$i."')\">$coteStart".$i."$coteEnd</a></li>";
					
				}
			}
			
			if($pages + $displayPages <= $numofpages)
			{
				$pagenext = ($pages + $displayPages);
				$working_data.="<li><a href=\"javascript:void(0);\" onclick=\"javascript:showAjaxPaging('#".$displayDiv."','ajax/".$_file."pages=".$pagenext."&page=".$pagenext."')\">NEXT</a></li>";
			}
			
			//$combo = "<select onChange=\"javascript:showAjaxPaging('#".$displayDiv."','ajax/".$_file."pages=".$pages."&page=' + this.value)\" style=\"width:55px\" class=\"form-control\">";
			$combo = "<select onChange=\"javascript:showAjaxPaging('#".$displayDiv."','ajax/".$_file."pages=' + this.value + '&page=' + this.value)\" class=\"form-control\">";
			for($mr=1;$mr<=$numofpages;$mr++){
				$selectedPage = (($page==$mr)?"selected":"");
				$combo.="<option value='".$mr."' $selectedPage>".$mr."</option>";
			}
			//$combo.="</select> Total : (".$PAGE_TOTAL_ROWS.")";
			$combo.="</select>";
			
			//return "Page : ".((empty($working_data))?0:$working_data)." of ".$numofpages.$combo;
			return '<div align="right"><table><tr><td>Page : </td><td>'.((empty($working_data))?0:'<ul class="pagination">'.$working_data.'</ul>').'</td><td>&nbsp; of '.$numofpages.' &nbsp;&nbsp;</td><td>'.$combo.'</td></tr></table></div>';
		}
		
	}
?>