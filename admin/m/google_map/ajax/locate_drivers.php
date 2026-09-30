<?php
include_once("../../../../bootstrap.php");

$current_date = date("Y-m-d");
$db = new database();
$drive_condition = "";
$driver_maker_name = "Driver";
$driver_icon_url = "icon/vehicle.png";
$customer_maker_name = "Customer";
$customer_icon_url = "icon/customer.png";
$show_driver_legend = 0;
$show_customer_legend = 0;
$CURDATE = date("Y-m-d");
$driver_id = $_REQUEST['driver_id'];
$date_range_from = $_REQUEST['date_range_from'];
$date_range_to = $_REQUEST['date_range_to'];
?>

<?php
if ($driver_id == "All_Drivers") {

    $show_driver_legend = 1;
    $query = "SELECT D.delivery_id,D.order_number,U.first_name,U.last_name,U.user_id,U.latitude,U.longitude FROM deliveries D  LEFT JOIN users U ON  D.driver_id = U.user_id WHERE D.status='In Transit' AND  scheduled_delivery='" . $CURDATE . "' GROUP BY D.delivery_id";
    $db->myCustomQuery($query);
    $data = array();
    if ($db->row_count > 0) {
        for ($i = 0; $i < $db->row_count; $i++) {
            $db->moveto($i);

            $dataArray[$i]['latitude'] = $db->f_latitude;
            $dataArray[$i]['longitude'] = $db->f_longitude;
            $dataArray[$i]['name'] = $db->f_first_name . " " . $db->f_last_name;
            $dataArray[$i]['id'] = $db->f_user_id;
            $dataArray[$i]['maker_name'] = $driver_maker_name;
            $dataArray[$i]['icon_url'] = $driver_icon_url;
        }
    }
} else if ($driver_id == "All_Customer") {
    $show_customer_legend = 1;
    $query = "SELECT D.delivery_id,D.order_number,D.delivery_id,C.customer_name,C.customer_id,D.latitude,D.longitude FROM deliveries D   LEFT JOIN customers C ON D.customer_id = C.customer_id  WHERE D.status='In Transit' AND  scheduled_delivery='" . $CURDATE . "' AND D.latitude!='' AND longitude!=''";
    $db->myCustomQuery($query);
    $data = array();
    if ($db->row_count > 0) {
        for ($i = 0; $i < $db->row_count; $i++) {
            $db->moveto($i);

            $order_number = $db->f_order_number;

            $dataArray[$i]['latitude'] = $db->f_latitude;
            $dataArray[$i]['longitude'] = $db->f_longitude;
            $dataArray[$i]['name'] = $db->f_customer_name;
            $dataArray[$i]['id'] = $db->f_customer_id;
            $dataArray[$i]['maker_name'] = $customer_maker_name;
            $dataArray[$i]['icon_url'] = $customer_icon_url;
            $dataArray[$i]['info'] = $order_number;
        }
    }
    $url = "icon/customer.png";
    $maker_name = "Customer";
    //echo "<pre>";
    // print_r($dataArray);
} else if ($driver_id == "All_Drivers_And_Customer") {

    $show_driver_legend = 1;
    $show_customer_legend = 1;
    $db1 = new database();
    $query1 = "SELECT D.delivery_id,D.order_number,D.delivery_id,C.customer_name,C.customer_id,D.latitude,D.longitude FROM deliveries D   LEFT JOIN customers C ON D.customer_id = C.customer_id  WHERE D.status='In Transit' AND  scheduled_delivery='" . $CURDATE . "' AND D.latitude!='' AND longitude!=''";
    $db1->myCustomQuery($query1);
    $data = array();
    if ($db1->row_count > 0) {
        for ($i = 0; $i < $db1->row_count; $i++) {
            $db1->moveto($i);
            $dataArray[$i]['latitude'] = $db1->f_latitude;
            $dataArray[$i]['longitude'] = $db1->f_longitude;
            $dataArray[$i]['name'] = $db1->f_customer_name;
            $dataArray[$i]['id'] = $db1->f_customer_id;
            $dataArray[$i]['maker_name'] = $customer_maker_name;
            $dataArray[$i]['icon_url'] = $customer_icon_url;
            $dataArray[$i]['info'] = "";
        }
    }
    $j = $i;

    $db2 = new database();
    $query2 = "SELECT D.delivery_id,D.order_number,U.first_name,U.last_name,U.user_id,U.latitude,U.longitude FROM deliveries D  LEFT JOIN users U ON  D.driver_id = U.user_id WHERE D.status='In Transit' AND  scheduled_delivery='" . $CURDATE . "' GROUP BY D.delivery_id";
    $db2->myCustomQuery($query2);
    if ($db2->row_count > 0) {


        for ($i = 0; $i < $db2->row_count; $i++) {
            $db2->moveto($s);
            $dataArray[$j]['latitude'] = $db2->f_latitude;
            $dataArray[$j]['longitude'] = $db2->f_longitude;
            $dataArray[$j]['name'] = $db2->f_first_name . " " . $db2->f_last_name;
            $dataArray[$j]['id'] = $db2->f_user_id;
            $dataArray[$j]['maker_name'] = $driver_maker_name;
            $dataArray[$j]['icon_url'] = $driver_icon_url;
            $dataArray[$i]['info'] = "";
            $j++;
        }
    }

    $drive_condition = "";
} else {

    if ($driver_id != "" && $driver_id > 0) {

        $driver_condition = "AND user_id=" . $driver_id;
    }

    $show_driver_legend = 1;
    $query = " SELECT D.delivery_id,D.order_number,U.first_name,U.last_name,U.user_id,U.latitude,U.longitude FROM deliveries D  LEFT JOIN users U ON  D.driver_id = U.user_id WHERE D.status='In Transit' AND  scheduled_delivery='" . $CURDATE . "' $driver_condition GROUP BY U.user_id ";
    $db->myCustomQuery($query);
    $data = array();
    if ($db->row_count > 0) {
        for ($i = 0; $i < $db->row_count; $i++) {
            $db->moveto($i);

            $dataArray[$i]['latitude'] = $db->f_latitude;
            $dataArray[$i]['longitude'] = $db->f_longitude;
            $dataArray[$i]['name'] = $db->f_first_name . " " . $db->f_last_name;
            $dataArray[$i]['id'] = $db->f_user_id;
            $dataArray[$i]['maker_name'] = $driver_maker_name;
            $dataArray[$i]['icon_url'] = $driver_icon_url;
            $dataArray[$i]['info'] = "";
        }
    }
}
//echo json_encode($dataArray);
//echo "<pre>";
//print_r($dataArray);
?>



<?php
if (!empty($dataArray)) {
    $count = count($dataArray);
    //echo "<pre>";print_r($dataArray);exit;
    ?>


    <meta http-equiv="Content-type" content="text/html;charset=UTF-8">
    <style>
        #legend {
            padding: 10px;
            margin:5px;
            border:1px #000000 dashed;
            background-color:rgba(255,255,255,0.5);
            font-family:'Roboto',Arial,sans-serif;
        }
        .labels {
            font-family: "Lucida Grande", "Arial", sans-serif;
            font-size: 13px;
            text-align: center;
            white-space: nowrap;
        }
    </style>

    <script>
        //var latitude="<?php echo $dataArray[0]['lattitude'] ?>";		
        //var longitude="<?php echo $dataArray[0]['longitude'] ?>";
        //var latitude="40.7506428";		
        //var longitude="-74.05426469999998";
        var latitude = "21.385889";
        var longitude = "74.251296";
        var map;
        var markers = [];
        var arr1 = new Array;
        var chkstatus = new Array;
        var waypts = new Array();
        var directionsService = new google.maps.DirectionsService();
        var bounds = new google.maps.LatLngBounds();
        var dataStr = '';
        var mystring;
        var chance = 0;
        $(document).ready(function () {
            initialize();
            placeMarkers();
            //e.preventDefault();
        });

        function initialize()
        {
            var latLong = new google.maps.LatLng(latitude, longitude);
            var mapOptions = {
                zoom: 5,
                center: latLong,
                streetViewControl: true,
                mapTypeId: google.maps.MapTypeId.ROADMAP,
                panControl: true,
                zoomControl: true,
                mapTypeControl: true,
                scaleControl: true,
                overviewMapControl: true,
                rotateControl: true
            };
            map = new google.maps.Map(document.getElementById('map-canvas'), mapOptions);
            map.controls[google.maps.ControlPosition.RIGHT_TOP].push(document.getElementById('legend'));

        }

        function placeMarkers(e)
        {
            var infowindow = new google.maps.InfoWindow();
    <?php
    $i = 0;
    $j = 0;

    $count = count($dataArray);
    for ($i = 0; $i < $count; $i++) {
        $maker_name = $dataArray[$i]['maker_name'];
        $url = $dataArray[$i]['icon_url'];

        $str = '<div style="width:300px;height:80px;overflow-y: hidden;">';
        $str.="<table>";
        $str.="<tr><td><b>$maker_name Name :</b></td><td>" . ucfirst($dataArray[$i]['name']) . "</td></tr>";
        $str.="<tr><td><b>$maker_name ID :</b></td><td>" . $dataArray[$i]['id'] . "</td></tr>";
        $str.="<tr><td><b>Order Details :</b></td><td>" . $dataArray[$i]['info'] . "</td></tr>";
        $str.='</table>';
        $str.='</div>';
        ?>
                // alert('lat='+<?php echo $dataArray[$i]['latitude'] ?>);
                // alert('long='+<?php echo $dataArray[$i]['longitude'] ?>);
                var i =<?php echo $i; ?>;

                var icon = {
                    url: '<?php echo $url ?>', // url
                    scaledSize: new google.maps.Size(30, 30), // scaled size
                    origin: new google.maps.Point(0, 0), // origin
                    anchor: new google.maps.Point(0, 0) // anchor
                };

                // alert('<?php echo $dataArray[$i]['latitude'] ?>');
                var marker = new google.maps.Marker({
                    map: map,
                    position: new google.maps.LatLng(<?php echo $dataArray[$i]['latitude'] ?>,<?php echo $dataArray[$i]['longitude'] ?>),
                    icon: icon
                });

                markers.push(marker);
                arr1.push("'" + marker.getPosition() + "'");
                bounds.extend(marker.getPosition());

                google.maps.event.addListener(marker, 'click', (function (marker, i) {
                    return function () {
                        infowindow.setContent('<?php echo $str; ?>');
                        infowindow.open(map, marker);
                    }
                })(marker, i));
    <?php }
    ?>
            map.fitBounds(bounds);
        }



    </script>



    <div align="right">
    </div>
    <div id="map-canvas" style="height:90%; width:100%;float:left;margin-bottom:10px;border-style: solid;"></div>
    <div id="legend">
        <span style="font-size:16px">Legends</span>
        <p>______________</p>
        <?php
        if ($show_driver_legend == 1) {
            ?>
            <img src="icon/vehicle.png" width="16px"><span>&nbsp;&nbsp;&nbsp;Driver(s)</span><br />
        <?php } ?>
        <?php
        if ($show_customer_legend == 1) {
            ?>
            <img src="icon/customer.png" width="16px"><span>&nbsp;Customer(s)</span><br />
        <?php } ?>
    </div>

    <div id="loader" align="center" style="position: absolute; height: 80%; width: 80%; top: 0px; left: 0px; background: none repeat scroll 0px 0px rgb(0, 0, 0); opacity: 0.6;display : none" ><img alt="" src="icon/spinner.GIF" style="padding-right: 20%; padding-top: 15%;">
    </div>
    <?php
    exit();
    ?>
    <?php
} else {
    ?>
    <div id="detail_div" style="display:block" name="detail_div">No Records Found</div>
    <?php
}
?>

