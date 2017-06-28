<?php

//just dummy module for testing purposes
error_reporting(E_ALL);

$placemarks = gm_MapAddMark('48.5277, 25.0366', 'test placemark title', 'testing some content', 'info window footer');
$placemarks.= gm_MapAddMark('48.5277, 25.0367', 'test placemark title2', 'testing some content2', 'info window footer2');
deb(gm_MapContainer() . gm_MapInit('48.5277, 25.0366', '17', 'map', $placemarks));

?>