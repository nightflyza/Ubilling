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
 * Returns map circle
 * 
 * @param $coords - map coordinates
 * @param $radius - circle radius in meters
 * 
 * @return string
 *  
 */
function generic_MapAddCircle($coords, $radius, $content = '', $hint = '') {
    $result = '
             myCircle = new ymaps.Circle([
                    [' . $coords . '],
                    ' . $radius . '
                ], {
                    balloonContent: "' . $content . '",
                    hintContent: "' . $hint . '"
                }, {
                    draggable: true,
             
                    fillColor: "#00a20b55",
                    strokeColor: "#006107",
                    strokeOpacity: 0.5,
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
    return($result);
}

/**
 * Returns maps empty container
 * 
 * @return string
 */
function generic_MapContainer($width = '', $height = '', $id = '') {
    $width = (!empty($width)) ? $width : '100%';
    $height = (!empty($height)) ? $height : '800px';
    $id = (!empty($id)) ? $id : 'ubmap';
    $result = wf_tag('div', false, '', 'id="' . $id . '" style="width:'.$width.'; height:'.$height.';"');
    $result .= wf_tag('div', true);
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
                            coords[0].toPrecision(7),
                            coords[1].toPrecision(7)
                            ].join(\', \') + \'</p> <form action="" method="POST"><input type="hidden" name="' . $name . '" value="\'+coords[0].toPrecision(7)+\', \'+coords[1].toPrecision(7)+\'">' . $data . '</form> \'
                 
                    });
                } else {
                    myMap.balloon.close();
                }
            });
            ';

    return ($result);
}

?>