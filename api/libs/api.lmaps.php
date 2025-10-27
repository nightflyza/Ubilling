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
    $height = (!empty($height)) ? $height : '800px';
    $id = (!empty($id)) ? $id : 'ubmap';
    $result = wf_tag('div', false, '', 'id="' . $id . '" style="width:' . $width . '; height:' . $height . ';"');
    $result .= wf_tag('div', true);
    return ($result);
}

/**
 * Translates yandex to google icon code
 * 
 * @param string $icon
 * @return string
 */
function lm_GetIconUrl($icon) {
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
        case 'twirl#blackIcon':
            $result = 'skins/mapmarks/black.png';
            break;


        //unknown icon fallback
        default:
            $result = 'skins/mapmarks/blue.png';
            show_warning('Unknown icon received: ' . $icon);
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
function generic_MapAddMark($coords, $title = '', $content = '', $footer = '', $icon = 'twirl#lightblueIcon', $iconlabel = '', $canvas = false) {
    $result = '';

    $title = str_replace('"', '\"', $title);
    $content = str_replace('"', '\"', $content);
    $footer = str_replace('"', '\"', $footer);
    $iconCode = '';
    $iconDefines = '';

    if (!empty($icon)) {
        $iconFile = lm_GetIconUrl($icon);
        $iconDefines .= "var LeafIcon = L.Icon.extend({
		options: {
			iconSize:     [42, 42],
			iconAnchor:   [22, 41],
			popupAnchor:  [-3, -44]
		}
	});
        

      	var customIcon = new LeafIcon({iconUrl: '" . $iconFile . "'});

        ";
        $iconCode .= ', {icon: customIcon}';
    }

    $result .= $iconDefines;
    $result .= 'var placemark=L.marker([' . $coords . ']' . $iconCode . ').addTo(map)
		.bindPopup("<b>' . $title . '</b><br />' . $content . '<br>' . $footer . '",  {maxWidth: 320, minWidth: 50, maxHeight: 600, closeButton: true, closeOnEscapeKey: true });';

    if (!empty($content)) {
        $result .= 'placemark.bindTooltip("' . $content . '", { sticky: true});';
    }
    return ($result);
}

/**
 * Adds a dynamic map marker with AJAX-loaded popup content
 *
 * @param string $coords Coordinates in "lat,lng" format
 * @param string $title Marker tooltip text
 * @param string $contentUrl URL to load popup content from
 * @param string $icon Icon identifier
 * 
 * @return string
 */
function generic_MapAddMarkDynamic($coords, $title = '', $contentUrl = '', $icon = 'twirl#lightblueIcon') {
    $markerId = wf_InputId();
    $title = str_replace('"', '\"', $title);
    $contentUrl = str_replace('"', '\"', $contentUrl);
    $iconCode = '';
    $iconDefines = '';

    if (!empty($icon)) {
        $iconFile = lm_GetIconUrl($icon);
        $iconDefines .= "var LeafIcon = L.Icon.extend({
            options: {
                iconSize:     [42, 42],
                iconAnchor:   [22, 41],
                popupAnchor:  [-3, -44]
            }
        });

        var customIcon_$markerId = new LeafIcon({iconUrl: '" . $iconFile . "'});\n";
        $iconCode = ", {icon: customIcon_$markerId}";
    }

    $js = "
        $iconDefines
        var marker_$markerId = L.marker([$coords]$iconCode).addTo(map);";

    if (!empty($contentUrl)) {
        $js .= "
        marker_$markerId.bindPopup('" . __('Loading') . "...');
        marker_$markerId._popupHtml = null;

        marker_$markerId.on('click', function (e) {
            var marker = e.target;

            if (marker._popupHtml !== null) {
                marker.setPopupContent(marker._popupHtml);
                marker.openPopup();
                return;
            }

            marker.setPopupContent('" . __('Loading') . "...');
            marker.openPopup();

            fetch('$contentUrl')
                .then(response => response.text())
                .then(html => {
                    marker._popupHtml = html;
                    marker.setPopupContent(html);
                    marker.openPopup();
                })
                .catch(() => {
                    marker.setPopupContent('" . __('Error') . ' ' . __('Loading') . "');
                    marker.openPopup();
                });
        });";
    }

    if (!empty($title)) {
        $js .= "\nmarker_$markerId.bindTooltip(\"$title\", {sticky: true});";
    }

    return $js;
}



/**
 * Returns circle map placemark
 * 
 * @param string $coords - map coordinates
 * @param int $radius - circle radius in meters
 * @param string $content - popup balloon content
 * @param string $hint - on mouseover hint
 * @param string $color - circle border color, default: 009d25
 * @param float  $opacity - border opacity from 0 to 1, default: 0.8
 * @param string $fillColor - fill color of circle, default: 00a20b55
 * @param float $fillOpacity - fill opacity from 0 to 1, default: 0.5
 * 
 * @return string
 */
function generic_MapAddCircle($coords, $radius, $content = '', $hint = '', $color = '009d25', $opacity = 0.8, $fillColor = '00a20b55', $fillOpacity = 0.5) {
    $result = '
           var circle = L.circle([' . $coords . '], {
                    color: \'#' . $color . '\',
                    opacity: ' . $opacity . ',
                    fillColor: \'#' . $fillColor . '\',
                    fillOpacity: ' . $fillOpacity . ',
                    radius: ' . $radius . '
                }).addTo(map);
            ';
    if (!empty($content)) {
        $result .= 'circle.bindPopup("' . $content . '");';
    }

    if (!empty($hint)) {
        $hint = str_replace('"', '\"', $hint);
        $result .= 'circle.bindTooltip("' . $hint . '", { sticky: true});';
    }


    return ($result);
}
/**
 * Initalizes leaflet maps API with some params
 * 
 * @param string $center
 * @param int $zoom
 * @param string $type
 * @param string $placemarks
 * @param string $editor
 * @param string $lang
 * @param string $container
 * @param string $searchPrefill
 * 
 * @return string
 */
function generic_MapInit($center = '', $zoom = 15, $type = 'roadmap', $placemarks = '', $editor = '', $lang = 'uk-UA', $container = 'ubmap', $searchPrefill = '') {
    global $ubillingConfig;
    $mapsCfg = $ubillingConfig->getYmaps();
    $result = '';
    $tileLayerCustoms = '';
    $searchCode = '';
    $type = ($type == 'map') ? 'roadmap' : $type; // legacy config option
    $canvasRender = ($mapsCfg['CANVAS_RENDER']) ? 'true' : 'false'; //string values

    if (empty($center)) {
        //autolocator here
        $mapCenter = 'map.locate({setView: true, maxZoom: ' . $zoom . '});
                      function onLocationError(e) {
                          alert(e.message);
                      }
                      map.on(\'locationerror\', onLocationError);';
    } else {
        //explicit map center
        $mapCenter = 'map.setView([' . $center . '], ' . $zoom . ');';
    }

    //default OSM tile layer
    $tileLayerOSM = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';

    //satellite map layers
    $tileLayerSatellite = 'https://mt1.google.com/vt/lyrs=s&x={x}&y={y}&z={z}';
    $tileLayerHybrid = 'https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}';

    //custom OSM tile layer
    if (isset($mapsCfg['LEAFLET_TILE_LAYER'])) {
        if ($mapsCfg['LEAFLET_TILE_LAYER']) {
            $tileLayerOSM = $mapsCfg['LEAFLET_TILE_LAYER'];

            //Visicom custom options
            if (ispos($tileLayerOSM, 'visicom')) {
                $tileLayerCustoms = "subdomains: '123',
                tms: true";
            }

            //google satellite
            if (ispos($tileLayerOSM, 'google.com')) {
                $tileLayerCustoms = "subdomains:['mt0','mt1','mt2','mt3']";
            }
        }
    }

    if (!empty($searchPrefill)) {
        $searchCode = '
        const searchInput = document.querySelector(\'.leaflet-control-geocoder-form input\');
        if (searchInput) {
            searchInput.value = \'' . $searchPrefill . '\';
        }';
    }

    //Leaflet core libs
    $result .= wf_tag('link', false, '', 'rel="stylesheet" href="modules/jsc/leaflet/leaflet.css"');
    $result .= wf_tag('script', false, '', 'src="modules/jsc/leaflet/leaflet.js"') . wf_tag('script', true);

    //Geocoder libs init
    $result .= wf_tag('link', false, '', 'rel="stylesheet" href="modules/jsc/leaflet-geocoder/Control.Geocoder.css"');
    $result .= wf_tag('script', false, '', 'src="modules/jsc/leaflet-geocoder/Control.Geocoder.min.js"') . wf_tag('script', true);

    //Ruler libs init
    $result .= wf_tag('link', false, '', 'rel="stylesheet" href="modules/jsc/leaflet-ruler/src/leaflet-ruler.css"');
    $result .= wf_tag('script', false, '', 'src="modules/jsc/leaflet-ruler/src/leaflet-ruler.js"') . wf_tag('script', true);

    //Easyprint lib init
    $result .= wf_tag('script', false, '', 'src="modules/jsc/leaflet-easyprint/dist/bundle.js"') . wf_tag('script', true);

    //Fullscreen control
    $result .= wf_tag('link', false, '', 'rel="stylesheet" href="modules/jsc/leaflet-fullscreen/dist/leaflet.fullscreen.css"');
    $result .= wf_tag('script', false, '', 'src="modules/jsc/leaflet-fullscreen/dist/Leaflet.fullscreen.min.js"') . wf_tag('script', true);

    //basic map init
    $result .= wf_tag('script', false, '', 'type = "text/javascript"');
    $result .= '
    var map = L.map(\'' . $container . '\');
    ' . $mapCenter . '

    // Tile layers
    var roadmap = L.tileLayer(\'' . $tileLayerOSM . '\', {
        maxZoom: 18,
        attribution: \'\',
        ' . $tileLayerCustoms . '
    });

    var satellite = L.tileLayer(\'' . $tileLayerSatellite . '\', {
        maxZoom: 18,
        attribution: \'© Google\'
    });

    var hybrid = L.tileLayer(\'' . $tileLayerHybrid . '\', {
        maxZoom: 18,
        attribution: \'© Google\'
    });

    // Default tile layer
    ' . $type . '.addTo(map);

    // Base layers switcher
    var baseMaps = {
        "' . __('Map') . '": roadmap,
        "' . __('Hybrid') . '": hybrid,
        "' . __('Satellite') . '": satellite,
        
    };

    var geoControl = new L.Control.Geocoder({showResultIcons: true, errorMessage: "' . __('Nothing found') . '", placeholder: "' . __('Search') . '"});
    geoControl.addTo(map);

    L.easyPrint({
        title: \'' . __('Export') . '\',
        defaultSizeTitles: {Current: \'' . __('Current') . '\', A4Landscape: \'A4 Landscape\', A4Portrait: \'A4 Portrait\'},
        position: \'topright\',
        filename: \'ubillingmap_' . date("Y-m-d_H:i:s") . '\',
        exportOnly: true,
        hideControlContainer: true,
        sizeModes: [\'Current\', \'A4Landscape\', \'A4Portrait\']
    }).addTo(map);

    var options = {
        position: \'topright\',
        preferCanvas: \'' . $canvasRender . '\',
        lengthUnit: {        
            display: \'' . __('meters') . '\',          
            decimal: 2,               
            factor: 1000,    
            label: \'' . __('Distance') . ':\'           
        },
        angleUnit: {
            display: \'&deg;\',
            decimal: 2,        
            factor: null, 
            label: \'' . __('Bearing') . ':\'
        }
    };
    L.control.ruler(options).addTo(map);

    map.addControl(new L.Control.Fullscreen({
    title: {
        \'false\': \'' . __('Fullscreen') . '\',
        \'true\': \'' . __('Exit fullscreen') . '\'
    }
    }));

    var layerControl = L.control.layers(baseMaps, null, { collapsed: true });
    map.addControl(layerControl);

    ' . $placemarks . '
    ' . $editor . '
    ' . $searchCode . '

    ';
    $result .= wf_tag('script', true);
    return ($result);
}


/**
 * Return generic editor code
 * 
 * @param string $name
 * @param string $title
 * @param string $data
 * @param int    $precision
 * 
 * @return string
 */
function generic_MapEditor($name, $title = '', $data = '', $precision = 8) {

    $data = str_replace("'", '`', $data);
    $data = str_replace("\n", '', $data);
    $data = str_replace('"', '\"', $data);
    $content = '<form action=\"\" method=\"POST\"><input type=\"hidden\" name=' . $name . ' value=\'"+e.latlng.lat.toPrecision(' . $precision . ')+\',\'+e.latlng.lng.toPrecision(' . $precision . ')+"\'>' . $data . '</form>';

    $windowCode = '<b>' . $title . '</b><br>' . $content;
    $result = 'var popup = L.popup();

                function onMapClick(e) {
                        popup
                                .setLatLng(e.latlng)
                                .setContent("' . $windowCode . '<br>" + e.latlng.lat.toPrecision(' . $precision . ') + "," + e.latlng.lng.toPrecision(' . $precision . '))
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
 * @param string $width
 * 
 * @return string
 */
function generic_MapAddLine($coord1, $coord2, $color = '', $hint = '', $width = '') {
    $lineId = wf_InputId();
    $color = (!empty($color)) ? $color : '#000000';
    $width = (!empty($color)) ? $width + 1 : '1';

    $result = '';
    $result .= '
        var pointA = new L.LatLng(' . $coord1 . ');
        var pointB = new L.LatLng(' . $coord2 . ');
        var pointList = [pointA, pointB];

        var polyline_' . $lineId . ' = new L.Polyline(pointList, {
            color: \'' . $color . '\',
            weight: ' . $width . ',
            opacity: 0.8,
            smoothFactor: 1
        });
        polyline_' . $lineId . '.addTo(map);
        ';
    if (!empty($hint)) {
        $hint = str_replace('"', '\"', $hint);
        $result .= 'polyline_' . $lineId . '.bindTooltip("' . $hint . '", { sticky: true});';
    }
    return ($result);
}
