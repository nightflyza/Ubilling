<?php

//just dummy module for testing purposes
error_reporting(E_ALL);


$ymaps = $ubillingConfig->getYmaps();
$placemarks = '';


for ($i = 0; $i < 1000; $i++) {
    $offsetX=rand($i*10000,$i*50000);
    $offsetY=rand($i*10000,$i*50000);
    
    $offsetR=rand($i*10000,$i*50000);
    $offsetN=rand($i*10000,$i*50000);
    $placemarks.= generic_MapAddMark('48.5'.$offsetX.',25.0'.$offsetY, 'title:'.$i, 'content:'.$i, 'footer:'.$i, 'twirl#lightblueIcon', 'label:'.$i, true);
   // $placemarks.= generic_MapAddLine('48.5'.$offsetX.',25.0'.$offsetY, '48.5'.$offsetR.',25.0'.$offsetN, '#ff0000', 'hint:'.$i, 1);
}


$map = generic_MapContainer('100%', '400px', 'ubmap');
$map .= generic_MapInit($ymaps['CENTER'], $ymaps['ZOOM'], $ymaps['TYPE'], $placemarks, '', $ymaps['LANG'], 'ubmap');

deb($map);
