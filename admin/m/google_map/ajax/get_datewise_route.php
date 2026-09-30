<?php
include_once("../../../../bootstrap.php");
$GOOGLE_DeveloperKey = GOOGLE_MAPS_JS_KEY;
$driver_id = $_REQUEST['driver_id'];
$date_range_from = $_REQUEST['date_range_from'];
$date_range_to = $_REQUEST['date_range_to'];
$db = new database();
$query = " SELECT * FROM driver_location WHERE driver_id='" . $driver_id . "' AND DATE_FORMAT(add_date_time,'%Y-%m-%d')  BETWEEN '" . $date_range_from . "' AND '" . $date_range_to . "'";
$db->myCustomQuery($query);
$locations = array();
if ($db->row_count > 0) {
    for ($i = 0; $i < $db->row_count; $i++) {
        $db->moveto($i);
        $locations[] = array(
            'lat' => (float) $db->f_latitude,
            'lng' => (float) $db->f_longitude
        );
    }
}

//print_r($locations);
$start_lat_point = (float) $locations[0]['lat'];
$start_lng_point = (float) $locations[0]['lng'];

$array_size = sizeof($locations) - 1;

$end_lat_point = (float) $locations[$array_size]['lat'];
$end_lng_point = (float) $locations[$array_size]['lng'];
?>


<style>
    /* Always set the map height explicitly to define the size of the div
     * element that contains the map. */
    #map {
        height: 100%;
    }
    /* Optional: Makes the sample page fill the window. */
    html, body {
        height: 100%;
        margin: 0;
        padding: 0;
    }
</style>

<?php
    if($db->row_count>0){
?>
<div id="map" style="border-style: solid;"></div>
    <?php }else{
        echo "Record not found";
    }?>



<script>



    function initMap() {
        var map = new google.maps.Map(document.getElementById('map'), {
            zoom: 5,
            center: {lat: 20.593684, lng: 78.962880},
            mapTypeId: 'terrain'
        });

        var flightPlanCoordinates = <?php echo json_encode($locations); ?>
        /*   [
         {lat: 18.520430, lng: 73.856744},
         {lat: 19.997453, lng: 73.789802}
         
         ];*/


        var flightPath = new google.maps.Polyline({
            path: flightPlanCoordinates,
            geodesic: true,
            strokeColor: '#FF0000',
            strokeOpacity: 1.0,
            strokeWeight: 2
        });

        var myLatLng_start = {lat: <?php echo $start_lat_point ?>, lng: <?php echo $start_lng_point ?>};
        var myLatLng_end = {lat: <?php echo $end_lat_point ?>, lng: <?php echo $end_lng_point ?>};





        var startMarker = new google.maps.Marker({
            position: myLatLng_start,
            map: map,
            title: "<b>Hello World!</b>"

        });


        var infowindow_start = new google.maps.InfoWindow({
            content: "<b>Start Point<br><?php echo date('m/d/y',  strtotime($date_range_from))?>  To <b><?php echo date('m/d/y',  strtotime($date_range_to))?>"
        });


        infowindow_start.open(map, startMarker);


         startMarker.addListener('click', function () {
         infowindow_start.open(map, startMarker);
         });

        var endMarker = new google.maps.Marker({
            position: myLatLng_end,
            map: map
        });

        var infowindow_end = new google.maps.InfoWindow({
            content: "<b>End Point</b>"
        });
        infowindow_end.open(map, endMarker);

                endMarker.addListener('click', function () {
         infowindow_end.open(map, endMarker);
         });

        flightPath.setMap(map);

    }


</script>
<script  src="https://maps.googleapis.com/maps/api/js?key=<?php echo $GOOGLE_DeveloperKey ?>&callback=initMap"></script>

</body>
</html>