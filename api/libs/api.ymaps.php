<?php

/*
 * Yandex maps API implementation
 */

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
    $hint = (!empty($hint)) ? 'hintContent: "' . $hint . '"' : '';
    $color = (!empty($color)) ? $color : '#000000';
    $width = (!empty($color)) ? $width : '1';

    $result = '
         var myPolyline = new ymaps.Polyline([[' . $coord1 . '],[' . $coord2 . ']], 
             {' . $hint . '}, 
             {
              draggable: false,
              strokeColor: \'' . $color . '\',
              strokeWidth: \'' . $width . '\'
             }
             
             );
             myMap.geoObjects.add(myPolyline);
            ';
    return ($result);
}

/**
 * Initalizes maps API with some params
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
    if (empty($center)) {
        $center = 'ymaps.geolocation.latitude, ymaps.geolocation.longitude';
    } else {
        $center = $center;
    }


    $mapsCfg = $ubillingConfig->getYmaps();
    $yandexApiKey = @$mapsCfg['YMAPS_APIKEY'];
    if ($yandexApiKey) {
        $yandexApiKey = '&apikey=' . $yandexApiKey;
    } else {
        $yandexApiKey = '';
    }
    $apiUrl = 'https://api-maps.yandex.ru/2.0/';
    $result = wf_tag('script', false, '', 'src="' . $apiUrl . '?load=package.full&lang=' . $lang . $yandexApiKey . '"  type="text/javascript"');
    $result .= wf_tag('script', true);
    $result .= wf_tag('script', false, '', 'type="text/javascript"');
    $result .= '
        ymaps.ready(init);
        function init () {
            var myMap = new ymaps.Map(\'' . $container . '\', {
                    center: [' . $center . '], 
                    zoom: ' . $zoom . ',
                    type: \'yandex#' . $type . '\',
                    behaviors: [\'default\',\'scrollZoom\']
                })
               
                 myMap.controls
                .add(\'zoomControl\')
                .add(\'typeSelector\')
                .add(\'mapTools\')
                .add(\'searchControl\');
               
         ' . $placemarks . '    
         ' . $editor . '
    }';
    $result .= wf_tag('script', true);

    return ($result);
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
             myCircle = new ymaps.Circle([
                    [' . $coords . '],
                    ' . $radius . '
                ], {
                    balloonContent: "' . $content . '",
                    hintContent: "' . $hint . '"
                }, {
                    draggable: true,
             
                    fillColor: "#' . $fillColor . '",
                    strokeColor: "#' . $color . '",
                    strokeOpacity: ' . $opacity . ',
                    fillOpacity: ' . $fillOpacity . ',
                    strokeWidth: 1
                });
    
            myMap.geoObjects.add(myCircle);
            ';

    return ($result);
}

/**
 * Returns map mark
 * 
 * @param string $coords - map coordinates
 * @param string $title - ballon title
 * @param string $content - ballon content
 * @param string $footer - ballon footer content
 * @param string $icon - YM icon class
 * @param string $iconlabel - icon label string
 * @param string $canvas
 * 
 * @return string
 */
function generic_mapAddMark($coords, $title = '', $content = '', $footer = '', $icon = 'twirl#lightblueIcon', $iconlabel = '', $canvas = '') {
    if ($canvas) {
        if ($iconlabel == '') {
            $overlay = 'overlayFactory: "default#interactiveGraphics"';
        } else {
            $overlay = '';
        }
    } else {
        $overlay = '';
    }

    if (!wf_CheckGet(array('clusterer'))) {
        $markType = 'myMap.geoObjects';
    } else {
        $markType = 'clusterer';
    }

    $result = '
            myPlacemark = new ymaps.Placemark([' . $coords . '], {
                 iconContent: \'' . $iconlabel . '\',
                 balloonContentHeader: \'' . $title . '\',
                 balloonContentBody: \'' . $content . '\',
                 balloonContentFooter: \'' . $footer . '\',
                 hintContent: "' . $content . '",
                } , {
                    draggable: false,
                    preset: \'' . $icon . '\',
                    ' . $overlay . '
                        
                }),
 
            
           ' . $markType . '.add(myPlacemark);
        
            
            ';
    return ($result);
}

/**
 * Returns maps empty container
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
 * Return generic editor code
 * 
 * @param string $name
 * @param string $title
 * @param string $data
 * @param int $precision
 * 
 * @return string
 */
function generic_MapEditor($name, $title = '', $data = '', $precision = 8) {
    $data = str_replace("'", '`', $data);
    $data = str_replace("\n", '', $data);

    $result = '
            myMap.events.add(\'click\', function (e) {
                if (!myMap.balloon.isOpen()) {
                    var coords = e.get(\'coordPosition\');
                    myMap.balloon.open(coords, {
                        contentHeader: \'' . $title . '\',
                        contentBody: \' \' +
                            \'<p>\' + [
                            coords[0].toPrecision(' . $precision . '),
                            coords[1].toPrecision(' . $precision . ')
                            ].join(\', \') + \'</p> <form action="" method="POST"><input type="hidden" name="' . $name . '" value="\'+coords[0].toPrecision(' . $precision . ')+\', \'+coords[1].toPrecision(' . $precision . ')+\'">' . $data . '</form> \'
                 
                    });
                } else {
                    myMap.balloon.close();
                }
            });
            ';

    return ($result);
}
