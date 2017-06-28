<?php

/**
 * Maps services compatibility dispatcher
 */
$mapsCfg = rcms_parse_ini_file(CONFIG_PATH . 'ymaps.ini');
$mapsService = $mapsCfg['MAPS_SERVICE'];
switch ($mapsService) {
    case 'yandex':
        include('api/libs/api.ymaps.php');
        break;
    case 'google':
        include('api/libs/api.gmaps.php');
        break;
}
?>