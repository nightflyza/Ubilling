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

        //unknown icon fallback
        default :
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

    //$result.='markerscluster.addLayer(placemark);';

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

    if (!empty($hint)) {
        $hint = str_replace('"', '\"', $hint);
        $result .= 'circle.bindTooltip("' . $hint . '", { sticky: true});';
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
    $result = '';
    $tileLayerCustoms = '';
    $canvasRender = ($mapsCfg['CANVAS_RENDER']) ? 'true' : 'false'; //string values
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

    //default OSM tile layer
    $tileLayer = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';

    //custom tile layer
    if (isset($mapsCfg['LEAFLET_TILE_LAYER'])) {
        if ($mapsCfg['LEAFLET_TILE_LAYER']) {
            $tileLayer = $mapsCfg['LEAFLET_TILE_LAYER'];
            //Visicom custom options
            if (ispos($tileLayer, 'visicom')) {
                $tileLayerCustoms = "subdomains: '123',
                tms: true";
            }

            //google satellite
            if (ispos($tileLayer, 'google.com')) {
                $tileLayerCustoms = "subdomains:['mt0','mt1','mt2','mt3']";
            }
        }
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

    //Easyprint libs init
    $result .= wf_tag('script', false, '', 'src="modules/jsc/leaflet-easyprint/dist/bundle.js"') . wf_tag('script', true);

    //Marker cluster libs init
    /**
      $result .= wf_tag('link', false, '', 'rel="stylesheet" href="modules/jsc/leaflet-markercluster/dist/MarkerCluster.css"');
      $result .= wf_tag('link', false, '', 'rel="stylesheet" href="modules/jsc/leaflet-markercluster/dist/MarkerCluster.Default.css"');
      $result .= wf_tag('script', false, '', 'src="modules/jsc/leaflet-markercluster/dist/leaflet.markercluster-src.js"') . wf_tag('script', true);
     */
    //basic map init
    $result .= wf_tag('script', false, '', 'type = "text/javascript"');
    $result .= '
	var map = L.map(\'' . $container . '\');
        ' . $mapCenter . '
	L.tileLayer(\'' . $tileLayer . '\', {
		maxZoom: 18,
		attribution: \'\',
		id: \'mapbox.streets\',
                ' . $tileLayerCustoms . '
	}).addTo(map);
        
        var geoControl = new L.Control.Geocoder({showResultIcons: true, errorMessage: "' . __('Nothing found') . '", placeholder: "' . __('Search') . '"});
        geoControl.addTo(map);
        
        L.easyPrint({
	title: \'' . __('Export') . '\',
        defaultSizeTitles: {Current: \'' . __('Current') . '\', A4Landscape: \'A4 Landscape\', A4Portrait: \'A4 Portrait\'},
	position: \'topright\',
        filename: \'ubillingmap_' . date("Y-m-d_H:i:s") . '\',
        exportOnly: true,
        hideControlContainer: true,
	sizeModes: [\'Current\', \'A4Landscape\', \'A4Portrait\'],
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
        /**
	var markerscluster = L.markerClusterGroup({
			maxClusterRadius: 20
                        },
        );
        **/
        
	' . $placemarks . '
        ' . $editor . '
            
        
	/** map.addLayer(markerscluster); **/
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
    $content = '<form action=\"\" method=\"POST\"><input type=\"hidden\" name=' . $name . ' value=\'"+e.latlng.lat.toPrecision(7)+\',\'+e.latlng.lng.toPrecision(7)+"\'>' . $data . '</form>';


    //$content = str_replace('"', '\"', $content);
    $windowCode = '<b>' . $title . '</b><br>' . $content;
    $result = 'var popup = L.popup();

                function onMapClick(e) {
                        popup
                                .setLatLng(e.latlng)
                                .setContent("' . $windowCode . '<br>" + e.latlng.lat.toPrecision(7) + "," + e.latlng.lng.toPrecision(7))
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

?>