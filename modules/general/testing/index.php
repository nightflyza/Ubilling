<?php

//just dummy module for testing purposes
error_reporting(E_ALL);

//$placemarks = gm_MapAddMark('48.5277, 25.0366', 'test placemark title', 'testing some content', 'info window footer');
//$placemarks.= gm_MapAddMark('48.5277, 25.0367', 'test placemark title2', 'testing some content2', 'info window footer2');
//
//$placemarks.= gm_MapAddLine('48.5277, 25.0267', '48.5277, 25.0378', '#FF0000', 'hint here', 5);
//$placemarks.= gm_MapAddCircle('48.5277, 25.0366', 50, 'something here', 'test hint there');
//
//deb(gm_MapContainer() . gm_MapInit('48.5277, 25.0366', '17', 'map', $placemarks));

//memcached performance
$cache = new UbillingCache(); // Создаем объект
$cacheTime=600; // в секундах
for ($i=0;$i<100;$i++) {
$someData = $cache->getCallback('JUNGEN', function () {
    return ('SOME_TEXT_ATTRIBUTE');
}, $cacheTime);
}

?>