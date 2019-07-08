<?php

/**
 * Maps services compatibility dispatcher
 */
$mapsCfg = rcms_parse_ini_file(CONFIG_PATH . 'ymaps.ini');
$mapsService = isset($mapsCfg['MAPS_SERVICE']) ? $mapsCfg['MAPS_SERVICE'] : 'yandex';

switch ($mapsService) {
    case 'yandex':
        include('api/libs/api.ymaps.php');
        break;
    case 'google':
        include('api/libs/api.gmaps.php');
        break;
    case 'leaflet':
        include('api/libs/api.lmaps.php');
        break;
}
?>