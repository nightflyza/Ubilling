<?php

/*
 * Leaflet maps API implementation
 */

/**
 * Returns leaflet maps empty container
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
    $result = '';

    $title = str_replace('"', '\"', $title);
    $content = str_replace('"', '\"', $content);
    $footer = str_replace('"', '\"', $footer);

    $result .= 'L.marker([' . $coords . ']).addTo(map)
		.bindPopup("<b>' . $title . '</b><br />' . $content . '<br>' . $footer . '");';
    return($result);
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

    $result = '
           var circle = L.circle([' . $coords . '], {
                    color: \'#009d25\',
                    fillColor: \'#00a20b55\',
                    fillOpacity: 0.5,
                    radius: ' . $radius . '
                }).addTo(map);
            ';
    if (!empty($content)) {
        $result .= 'circle.bindPopup("' . $content . '");';
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

    if (empty($center)) {
        //autolocator here
        $mapCenter = 'map.locate({setView: true, maxZoom: ' . $zoom . '});';
        //error notice if autolocation failed
        $mapCenter .= 'function onLocationError(e) {
                        alert(e.message);
                       }
                       map.on(\'locationerror\', onLocationError)';
    } else {
        //explicit map center
        $mapCenter = 'map.setView([' . $center . '], ' . $zoom . ');';
    }

    //default tile layer
    $tileLayer = 'https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token=pk.eyJ1IjoibWFwYm94IiwiYSI6ImNpejY4NXVycTA2emYycXBndHRqcmZ3N3gifQ.rJcFIG214AriISLbB6B5aw';

    //custom tile layer
    if (isset($mapsCfg['LEAFLET_TILE_LAYER'])) {
        if ($mapsCfg['LEAFLET_TILE_LAYER']) {
            $tileLayer = $mapsCfg['LEAFLET_TILE_LAYER'];
        }
    }

    $result = '';
    $result .= '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.5.1/dist/leaflet.css"/>';
    $result .= wf_tag('script', false, '', 'src="https://unpkg.com/leaflet@1.5.1/dist/leaflet.js"');
    $result .= wf_tag('script', true);
    $result .= wf_tag('script', false, '', 'type = "text/javascript"');

    $result .= '
	var map = L.map(\'' . $container . '\');
        ' . $mapCenter . '
	L.tileLayer(\'' . $tileLayer . '\', {
		maxZoom: 18,
		attribution: \'\',
		id: \'mapbox.streets\'
	}).addTo(map);

	' . $placemarks . '
        ' . $editor . '
';
    $result .= wf_tag('script', true);
    return($result);
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

    $data = str_replace("'", '`', $data);
    $data = str_replace("\n", '', $data);
    $data = str_replace('"', '\"', $data);
    $content = '<form action=\"\" method=\"POST\"><input type=\"hidden\" name=' . $name . ' value=\'"+e.latlng.lat+\', \'+e.latlng.lng+"\'>' . $data . '</form>';


    //$content = str_replace('"', '\"', $content);
    $windowCode = '<b>' . $title . '</b><br>' . $content;
    $result = 'var popup = L.popup();

	function onMapClick(e) {
		popup
			.setLatLng(e.latlng)
                        .setContent("' . $windowCode . '<br>" + e.latlng.lat + ", " + e.latlng.lng)
			.openOn(map);
	}

	map.on(\'click\', onMapClick);';

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
    $color = (!empty($color)) ? $color : '#000000';
    $width = (!empty($color)) ? $width : '1';
    
    $result = '';
    $result .= '
        var pointA = new L.LatLng(' . $coord1 . ');
        var pointB = new L.LatLng(' . $coord2 . ');
        var pointList = [pointA, pointB];

        var firstpolyline = new L.Polyline(pointList, {
            color: \'' . $color . '\',
            weight: ' . $width . ',
            opacity: 0.8,
            smoothFactor: 1
        });
        firstpolyline.addTo(map);';
    return ($result);
}

?>