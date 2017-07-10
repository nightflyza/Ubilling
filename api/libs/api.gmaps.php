<?php

/**
 * Returns google maps empty container
 * 
 * @return string
 */
function gm_MapContainer($width = '', $height = '', $id = '') {
    $width = (!empty($width)) ? $width : '100%';
    $height = (!empty($height)) ? $height : '800px;';
    $id = (!empty($id)) ? $id : 'ubmap';
    $result = wf_tag('div', false, '', 'id="' . $id . '" style="width: 100%; height:800px;"');
    $result.=wf_tag('div', true);
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
function gm_MapInit($center, $zoom, $type, $placemarks = '', $editor = '', $lang = 'ru-RU', $container = 'ubmap') {
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
        $result.= wf_tag('script', false, '', 'type = "text/javascript"');
        $result.=' function initMap() {
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
        $result.=wf_tag('script', true);
        $result.=wf_tag('script', false, '', 'async defer type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=' . $apikey . '&language=' . $lang . '&callback=initMap"');
        $result.=wf_tag('script', true);
    } else {
        $messages = new UbillingMessageHelper();
        $result = $messages->getStyledMessage(__('No valid GMAPS_APIKEY set in ymaps.ini'), 'error');
    }
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
        case 'twirl#redIcon':
            $result = 'skins/mapmarks/red.png';
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

        default :
            $result = 'skins/mapmarks/blue.png';
            deb('Unknown icon received: ' . $icon);
            break;
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
function gm_MapAddMark($coords, $title = '', $content = '', $footer = '', $icon = 'twirl#lightblueIcon', $iconlabel = '', $canvas = false) {
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
function gm_MapAddLine($coord1, $coord2, $color = '', $hint = '', $width = '') {
    $lineId = wf_InputId();
    $hint = (!empty($hint)) ? 'hintContent: "' . $hint . '"' : '';
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
function gm_MapAddCircle($coords, $radius, $content = '', $hint = '') {
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

///
/// Emulating existing modules functions below
///

/**
 * Shows map container
 *
 * @return void
 */
function sm_ShowMapContainer() {
    $container = gm_MapContainer('', '', 'ubmap');
    $controls = wf_Link("?module=usersmap", wf_img('skins/ymaps/build.png') . ' ' . __('Builds map'), false, 'ubButton');
    $controls.= wf_Link("?module=switchmap", wf_img('skins/ymaps/network.png') . ' ' . __('Switches map'), false, 'ubButton');
    $controls.= wf_Link("?module=switchmap&locfinder=true", wf_img('skins/ymaps/edit.png') . ' ' . __('Edit map'), false, 'ubButton');
    $controls.= wf_Link("?module=switchmap&showuplinks=true", wf_img('skins/ymaps/uplinks.png') . ' ' . __('Show links'), false, 'ubButton');
    $controls.= wf_Link("?module=switchmap&coverage=true", wf_img('skins/ymaps/coverage.png') . ' ' . __('Coverage area'), false, 'ubButton');
    $controls.= wf_Link("?module=switches", wf_img('skins/ymaps/switchdir.png') . ' ' . __('Available switches'), true, 'ubButton');
    $controls.=wf_delimiter(1);
    show_window(__('Active equipment map'), $controls . $container);
}

/**
 * Initialize map container with some settings
 * 
 * @param $center - map center lat,long
 * @param $zoom - default map zoom
 * @param $type - map type, may be: map, satellite, hybrid
 * @param $placemarks - already filled map placemarks
 * @param $editor - field for visual editor or geolocator
 * @param $lang - map language in format ru-RU
 * 
 * @return void
 */
function sm_MapInit($center, $zoom, $type, $placemarks = '', $editor = '', $lang = 'ru-RU') {
    show_window('', gm_MapInit($center, $zoom, $type, $placemarks, $editor, $lang));
}

/**
 * Return geo coordinates locator for builds
 * 
 * @return string
 */
function um_MapLocationFinder() {
    $windowId = wf_InputId();

    $buildSelector = str_replace("'", '`', um_MapLocationBuildForm());
    $buildSelector = str_replace("\n", '', $buildSelector);

    $title = wf_tag('b') . __('Place coordinates') . wf_tag('b', true);
    $content = '<form action="" method="POST"><input type="hidden" name="placecoords" value="\'+lat+\', \'+lng+\'">' . $buildSelector . '</form>';

    $windowCode = 'var contentString_' . $windowId . ' = \'<div id = "content_' . $windowId . '">' . $title . '<br> \'+lat+\', \'+lng+\' <br> ' . $content . '</div>\';
            var infowindow_' . $windowId . ' = new google.maps.InfoWindow({
            content: contentString_' . $windowId . '
            });';
    $result = '
            google.maps.event.addListener(map, \'click\', function(event) {
            var myLatLng = event.latLng;
            var lat = myLatLng.lat().toPrecision(6);
            var lng = myLatLng.lng().toPrecision(6);
            ' . $windowCode . '
               infowindow_' . $windowId . '.setPosition(event.latLng);
               infowindow_' . $windowId . '.open(map);
                  // alert(event.latLng);  
            });
            ';
    return ($result);
}

/**
 * Returns geo coordinates locator
 * 
 * @return string
 */
function sm_MapLocationFinder() {
    $windowId = wf_InputId();

    $title = wf_tag('b') . __('Place coordinates') . wf_tag('b', true);
    $content = '<form action="" method="POST"><input type="hidden" name="placecoords" value="\'+lat+\', \'+lng+\'">' . str_replace("\n", '', sm_MapLocationSwitchForm()) . '</form>';

    $windowCode = 'var contentString_' . $windowId . ' = \'<div id = "content_' . $windowId . '">' . $title . '<br> \'+lat+\', \'+lng+\' <br> ' . $content . '</div>\';
            var infowindow_' . $windowId . ' = new google.maps.InfoWindow({
            content: contentString_' . $windowId . '
            });';
    $result = '
            google.maps.event.addListener(map, \'click\', function(event) {
            var myLatLng = event.latLng;
            var lat = myLatLng.lat().toPrecision(6);
            var lng = myLatLng.lng().toPrecision(6);
            ' . $windowCode . '
               infowindow_' . $windowId . '.setPosition(event.latLng);
               infowindow_' . $windowId . '.open(map);
                  // alert(event.latLng);  
            });
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
function sm_MapAddLine($coord1, $coord2, $color = '', $hint = '', $width = '') {
    return (gm_MapAddLine($coord1, $coord2, $color, $hint, $width));
}

/**
 * Returns map circle
 * 
 * @param string $coords
 * @param int $radius
 * @param string $content
 * @param string $hint
 * 
 * @return string
 */
function sm_MapAddCircle($coords, $radius, $content = '', $hint = '') {
    return (gm_MapAddCircle($coords, $radius, $content, $hint));
}

/**
 * Initialize map container with some settings
 * 
 * @param $center - map center lat,long
 * @param $zoom - default map zoom
 * @param $type - map type, may be: map, satellite, hybrid
 * @param $placemarks - already filled map placemarks
 * @param $editor - field for visual editor or geolocator
 * @param $lang - map language in format ru-RU
 * 
 * @return void
 */
function sm_MapInitQuiet($center, $zoom, $type, $placemarks = '', $editor = '', $lang = 'ru-RU') {
    return (gm_MapInit($center, $zoom, $type, $placemarks, $editor, $lang));
}

/**
 * Initialize map container with some settings
 * 
 * @param $center - map center lat,long
 * @param $zoom - default map zoom
 * @param $type - map type, may be: map, satellite, hybrid
 * @param $placemarks - already filled map placemarks
 * @param $editor - field for visual editor or geolocator
 * @param $lang - map language in format ru-RU
 * 
 * @return void
 */
function sm_MapInitBasic($center, $zoom, $type, $placemarks = '', $editor = '', $lang = 'ru-RU') {
    return (gm_MapInit($center, $zoom, $type, $placemarks, $editor, $lang));
}

/**
 * Shows map container for builds
 *
 * @return void
 */
function um_ShowMapContainer() {
    $container = gm_MapContainer('100%', '800px;', 'ubmap');
    $controls = wf_Link("?module=switchmap", wf_img('skins/ymaps/network.png') . ' ' . __('Switches map'), false, 'ubButton');
    $controls.= wf_Link("?module=usersmap", wf_img('skins/ymaps/build.png') . ' ' . __('Builds map'), false, 'ubButton');
    $controls.= wf_Link("?module=usersmap&locfinder=true", wf_img('skins/ymaps/edit.png') . ' ' . __('Edit map'), false, 'ubButton');
    $controls.=wf_delimiter(1);

    show_window(__('Builds and users map'), $controls . $container);
}

/**
 * Returns map mark
 * 
 * @param $coords - map coordinates
 * @param $title - ballon title
 * @param $content - ballon content
 * @param $footer - ballon footer content
 * @param $icon - YM icon class
 * @param $iconlabel - icon label string
 * @param $canvas - is canvas rendering enabled?
 * 
 * @return string
 */
function sm_MapAddMark($coords, $title = '', $content = '', $footer = '', $icon = 'twirl#lightblueIcon', $iconlabel = '', $canvas = false) {
    return (gm_MapAddMark($coords, $title, $content, $footer, $icon, $iconlabel, $canvas));
}

?>