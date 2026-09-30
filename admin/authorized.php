<?php
/////check session stored / user login or not
if(!isset($_SESSION["routetrac-admin"]["al_id"])){
	?>
    <script>
        location.href='<?php echo SITEROOT;?>/index.php';
    </script>
    <?php
	exit;
}
?>
