<?php

//just dummy module for testing purposes
error_reporting(E_ALL);
if (cfr('ROOT')) {
    //Mapcore testing playground
    $ymconf = $ubillingConfig->getYmaps();
    $mapCore = new MapCore('ubmap');
    $mapCore->setZoom($ymconf['ZOOM']);
    $mapCore->setType($ymconf['TYPE']);
    if (!empty($ymconf['CENTER'])) {
        $mapCore->setCenter($ymconf['CENTER']);
    }

    $markerCanonicalIcons=array(
    'marker.blue', 
    'marker.red',
    'marker.yellow',
     'marker.green',
      'marker.pink',
       'marker.brown',
        'marker.darkblue',
         'marker.orange',
          'marker.grey',
           'marker.black',
            'marker.building',
             'marker.house', 
             'marker.camping', 
             'vehicle.red', 
             'vehicle.green', 
             'vehicle.yellow', 
             'marker.wifi', 
             'marker.camera',
            );
             
    $markerCount = 100;
    $markerInitialLat = 48.52584946874239;
    $markerInitialLon = 25.03798691368117;
    $maxDistanceMeters = 5000; // maximum marker offset from center, in meters
    

    // generating markers with random coordinates around initial point
    for ($i = 0; $i < $markerCount; $i++) {
        $randomAngle = rand(0, 360) * pi() / 180;
        $distanceRatio = rand(0, 1000000) / 1000000;
        $randomDistance = sqrt($distanceRatio) * $maxDistanceMeters;

        $latOffset = $randomDistance / 111320;
        $lonOffset = $randomDistance / (111320 * cos($markerInitialLat * pi() / 180));

        $markerLat = $markerInitialLat + ($latOffset * cos($randomAngle));
        $markerLon = $markerInitialLon + ($lonOffset * sin($randomAngle));
        $randomIcon = $markerCanonicalIcons[rand(0, count($markerCanonicalIcons) - 1)];
        $options=array(
            'icon' => $randomIcon,
            'popupTitle' => 'Marker Title'.$i,
            'popupFooter' => 'Marker '.$i.' is at '.$markerLat.','.$markerLon,
            'tooltip' => 'Marker '.$i
        );

        $mapCore->addMarker($markerLat.','.$markerLon, 'Marker '.$i, $options);
    }

    $clusteringOptions=array(
        'maxClusterRadius' => 50,
        'iconCreateFunction' => null,
        'chunkedLoading' => true,
        'chunkInterval' => 200,
        'chunkDelay' => 50,
        'chunkProgress' => null
    );
    $mapCore->enableClustering(false, $clusteringOptions);
    
    show_window('Типу мапа', $mapCore->renderContainer('100%', '650px') . $mapCore->render());


}
