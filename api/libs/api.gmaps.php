<?php

/*
 * Google maps API implementation
 */

/**
 * Returns google maps empty container
 * 
 * @param string $width
 * @param string $height
 * @param string $id
 * 
 * @return string
 */
function generic_MapContainer($width = '', $height = '', $id = '') {
    $width = (!empty($width)) ? $width : '100%';
    $height = (!empty($height)) ? $height : '800px;';
    $id = (!empty($id)) ? $id : 'ubmap';
    $result = wf_tag('div', false, '', 'id="' . $id . '" style="width: 100%; height:800px;"');
    $result .= wf_tag('div', true);
    return ($result);
}

/**
 * Translates yandex to google icon code
 * 
 * @param string $icon
 * @return string
 */
function gm_GetIconUrl($icon) {
    $result = '';
    switch ($icon) {
        case 'twirl#lightblueIcon':
            $result = 'skins/mapmarks/blue.png';
            break;
        case 'twirl#lightblueStretchyIcon':
            $result = 'skins/mapmarks/blue.png';
            break;
        case 'twirl#redStretchyIcon':
            $result = 'skins/mapmarks/red.png';
            break;
        case 'twirl#yellowIcon':
            $result = 'skins/mapmarks/yellow.png';
            break;
        case 'twirl#greenIcon':
            $result = 'skins/mapmarks/green.png';
            break;
        case 'twirl#pinkDotIcon':
            $result = 'skins/mapmarks/pink.png';
            break;
        case 'twirl#brownIcon':
            $result = 'skins/mapmarks/brown.png';
            break;
        case 'twirl#nightDotIcon':
            $result = 'skins/mapmarks/darkblue.png';
            break;
        case 'twirl#redIcon':
            $result = 'skins/mapmarks/red.png';
            break;
        case 'twirl#orangeIcon':
            $result = 'skins/mapmarks/orange.png';
            break;
        case 'twirl#greyIcon':
            $result = 'skins/mapmarks/grey.png';
            break;
        case 'twirl#buildingsIcon':
            $result = 'skins/mapmarks/build.png';
            break;
        case 'twirl#houseIcon':
            $result = 'skins/mapmarks/house.png';
            break;
        case 'twirl#campingIcon':
            $result = 'skins/mapmarks/camping.png';
            break;
        //extended icon pack
        case 'redCar':
            $result = 'skins/mapmarks/redcar.png';
            break;
        case 'greenCar':
            $result = 'skins/mapmarks/greencar.png';
            break;
        case 'yellowCar':
            $result = 'skins/mapmarks/yellowcar.png';
            break;

        //unknown icon fallback
        default :
            $result = 'skins/mapmarks/blue.png';
            show_warning('Unknown icon received: ' . $icon);
            break;
    }
    return ($result);
}

/**
 * Initalizes google maps API with some params
 * 
 * @param string $center
 * @param int $zoom
 * @param string $type
 * @param string $placemarks
 * @param bool $editor
 * @param string $lang
 * @param string $container
 * 
 * @return string
 */
function generic_MapInit($center, $zoom, $type, $placemarks = '', $editor = '', $lang = 'ru-RU', $container = 'ubmap') {
    global $ubillingConfig;
    $mapsCfg = $ubillingConfig->getYmaps();
    @$apikey = $mapsCfg['GMAPS_APIKEY'];
    $mapType = $mapsCfg['TYPE'];
    if ($mapType == 'map') {
        $mapType = 'roadmap';
    }
    $result = '';
    if ((!empty($apikey)) AND ( $apikey != 'YOUR_API_KEY_HERE')) {
        if (!empty($center)) {
            $center = explode(',', $center);
            $centerLat = trim($center[0]);
            $centerLng = trim($center[1]);
            $centerCode = 'center: uluru';
            $autoLocator = '';
        } else {

            $autoLocator = '
       if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(success, error);
        } else {
            alert(\'geolocation not supported\');
        }

        function success(position) {
            map.setCenter(new google.maps.LatLng(position.coords.latitude, position.coords.longitude),' . $zoom . ');
        }

        function error(msg) {
            alert(\'error: \' + msg);
        }
        ';
            $centerLat = '48.5319';
            $centerLng = '30.0350';
            $centerCode = 'center: uluru';
        }
        $result .= wf_tag('script', false, '', 'type = "text/javascript"');
        $result .= ' function initMap() {
var uluru = {lat: ' . $centerLat . ', lng: ' . $centerLng . '};
var map = new google.maps.Map(document.getElementById(\'' . $container . '\'), {
          zoom: ' . $zoom . ',
         mapTypeId: \'' . $mapType . '\',

          ' . $centerCode . '
        });
        ' . $placemarks . '
        ' . $editor . '
        ' . $autoLocator . '          
      }
 
';
        $result .= wf_tag('script', true);
        $result .= wf_tag('script', false, '', 'async defer type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=' . $apikey . '&language=' . $lang . '&callback=initMap"');
        $result .= wf_tag('script', true);
    } else {
        $messages = new UbillingMessageHelper();
        $result = $messages->getStyledMessage(__('No valid GMAPS_APIKEY set in ymaps.ini'), 'error');
    }
    return ($result);
}

/**
 * Returns placemark code
 * 
 * @param string $coords
 * @param string $title
 * @param string $content
 * @param string $footer
 * @param string $icon
 * @param string $iconlabel
 * @param bool $canvas
 * 
 * @return string
 */
function generic_MapAddMark($coords, $title = '', $content = '', $footer = '', $icon = 'twirl#lightblueIcon', $iconlabel = '', $canvas = false) {
    $markerId = wf_InputId();
    if (!empty($coords)) {
        $coords = explode(',', $coords);
        $coordLat = trim($coords[0]);
        $coordLng = trim($coords[1]);
    }

    $iconUrl = gm_GetIconUrl($icon);
    if (!empty($iconUrl)) {
        $iconCode = "var image_" . $markerId . " = '" . $iconUrl . "';";
    }

    if (!empty($title)) {
        $titleCode = '<strong>' . $title . '</strong><br>';
    } else {
        $titleCode = '';
    }
    if (!empty($title)) {
        $labelCode = "title: '" . $iconlabel . "',";
    } else {
        $labelCode = '';
    }
    if ((!empty($content)) OR ( !empty($footer))) {
        if (!empty($footer)) {
            $footerCode = '<div id="footer_' . $markerId . '" class="row3">' . $footer . '</div>';
        } else {
            $footerCode = '';
        }
        $contentWindow = 'var contentString_' . $markerId . ' = \'<div id = "content_' . $markerId . '">' . $titleCode . $content . $footerCode . '</div>\';
            var infowindow_' . $markerId . ' = new google.maps.InfoWindow({
            content: contentString_' . $markerId . '
            });
            google.maps.event.addListener(marker_' . $markerId . ', \'click\', function() {
                infowindow_' . $markerId . '.open(map,marker_' . $markerId . ');
            });
            ';
    } else {
        $contentWindow = '';
    }

    $result = '
          var position_' . $markerId . ' = {lat: ' . $coordLat . ', lng: ' . $coordLng . '};
          ' . $iconCode . '
          var marker_' . $markerId . ' = new google.maps.Marker({
          ' . $labelCode . '
          position: position_' . $markerId . ',
          map: map,
          icon: image_' . $markerId . '
        });
         ' . $contentWindow . '
            ';
    return ($result);
}

/**
 * Returns JS code to draw line within two points
 * 
 * @param string $coord1
 * @param string $coord2
 * @param string $color
 * @param string $hint
 * 
 * @return string
 */
function generic_MapAddLine($coord1, $coord2, $color = '', $hint = '', $width = '') {
    $lineId = wf_InputId();
    $color = (!empty($color)) ? $color : '#000000';
    $width = (!empty($color)) ? $width : '1';
    $coord1 = explode(',', $coord1);
    $coord2 = explode(',', $coord2);
    $lat1 = $coord1[0];
    $lng1 = $coord1[1];
    $lat2 = $coord2[0];
    $lng2 = $coord2[1];

    if (!empty($hint)) {
        $tooltipCode = '
            var infoWindow_' . $lineId . ' = new google.maps.InfoWindow({
             content: \'' . $hint . '\'
        });

            google.maps.event.addListener(line_' . $lineId . ', \'mouseover\', function(e) {
            infoWindow_' . $lineId . '.setPosition(e.latLng);
            infoWindow_' . $lineId . '.open(map);
            });

        
           google.maps.event.addListener(line_' . $lineId . ', \'mouseout\', function() {
           infoWindow_' . $lineId . '.close();
           });';
    } else {
        $tooltipCode = '';
    }

    $result = '
         var linecoords_' . $lineId . ' = [
          {lat: ' . $lat1 . ', lng: ' . $lng1 . '},
          {lat: ' . $lat2 . ', lng: ' . $lng2 . '}
        ];
        
        var line_' . $lineId . ' = new google.maps.Polyline({
          path: linecoords_' . $lineId . ',
          geodesic: true,
          strokeColor: \'' . $color . '\',
          strokeOpacity: 1.0,
          strokeWeight: ' . $width . '
        });

        line_' . $lineId . '.setMap(map);
        ' . $tooltipCode . '

            ';
    return ($result);
}

/**
 * Returns map circle
 * 
 * @param string $coords - map coordinates
 * @param int $radius - circle radius in meters
 * @param string $content 
 * 
 * @return string
 *  
 */
function generic_MapAddCircle($coords, $radius, $content = '', $hint = '') {
    $circelId = wf_InputId();
    $coords = explode(',', $coords);
    $lat = $coords[0];
    $lng = $coords[1];

    $result = '
            var circlecoords_' . $circelId . ' =  {lat: ' . $lat . ', lng: ' . $lng . '} ;
               
            var cicrcle_' . $circelId . ' = new google.maps.Circle({
            strokeColor: \'#006107\',
            strokeOpacity: 0.8,
            strokeWeight: 1,
            fillColor: \'#00a20b55\',
            fillOpacity: 0.35,
            map: map,
            center: circlecoords_' . $circelId . ',
            radius: ' . $radius . '
            });
            ';

    return ($result);
}

/**
 * Return generic editor code
 * 
 * @param string $name
 * @param string $title
 * @param string $data
 * 
 * @return string
 */
function generic_MapEditor($name, $title = '', $data = '') {
    $windowId = wf_InputId();
    $data = str_replace("'", '`', $data);
    $data = str_replace("\n", '', $data);

    $content = '<form action="" method="POST"><input type="hidden" name="' . $name . '" value="\'+lat+\', \'+lng+\'">' . $data . '</form>';

    $windowCode = 'var contentString_' . $windowId . ' = \'<div id = "content_' . $windowId . '">' . $title . '<br> \'+lat+\', \'+lng+\' <br> ' . $content . '</div>\';
            var infowindow_' . $windowId . ' = new google.maps.InfoWindow({
            content: contentString_' . $windowId . '
            });';
    $result = '
            google.maps.event.addListener(map, \'click\', function(event) {
            var myLatLng = event.latLng;
            var lat = myLatLng.lat().toPrecision(7);
            var lng = myLatLng.lng().toPrecision(7);
            ' . $windowCode . '
               infowindow_' . $windowId . '.setPosition(event.latLng);
               infowindow_' . $windowId . '.open(map);
            });
            ';
    return ($result);
}

?>