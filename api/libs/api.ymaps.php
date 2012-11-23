<?php

/*
 * Yandex maps API implementation
 */

/*
 * 
 * Shows map container
 *
 * @return nothing
 *  
 */
    function sm_ShowMapContainer() {
        $container=  wf_tag('div', false, '','id="swmap" style="width: 1000; height:800px;"');
        $container.=wf_tag('div', true);
        
        $controls=  wf_Link("?module=switchmap", __('Switches map'), false, 'ubButton');
        $controls.=  wf_Link("?module=switchmap&locfinder=true", __('Find location'), false, 'ubButton');
        $controls.=  wf_Link("?module=switches", __('Available switches'), true, 'ubButton');
        $controls.=wf_delimiter(1);
        
        show_window(__('Active equipment map'),$controls.$container);
    }

/*
 * 
 * Return bad icon class
 * 
 * @return string
 *  
 */

     function sm_MapBadIcon() {
        return ('twirl#redStretchyIcon');
    }
 
/*
 * 
 * Return good icon class
 * 
 * @return string
 *  
 */
    
    function sm_MapGoodIcon() {
        return ('twirl#lightblueStretchyIcon');
    }

/*
 * 
 * Return geo coordinates locator
 * 
 * @return string
 *  
 */   
    function sm_MapLocationFinder() {
        
        $result='
            myMap.events.add(\'click\', function (e) {
                if (!myMap.balloon.isOpen()) {
                    var coords = e.get(\'coordPosition\');
                    myMap.balloon.open(coords, {
                        contentHeader: \''.__('Place coordinates').'\',
                        contentBody: \'\' +
                            \'<p>\' + [
                            coords[0].toPrecision(6),
                            coords[1].toPrecision(6)
                            ].join(\', \') + \'</p>\'
                 
                    });
                } else {
                    myMap.balloon.close();
                }
            });
            ';
        return ($result);
    }
    
/*
 * 
 * Initialize map container with some settings
 * 
 * @param $center - map center lat,long
 * @param $zoom - default map zoom
 * @param $type - map type, may be: map, satellite, hybrid
 * @param $placemarks - already filled map placemarks
 * @param $editor - field for visual editor or geolocator
 * @param $lang - map language in format ru-RU
 * @return nothing
 *  
 */
    function sm_MapInit($center,$zoom,$type,$placemarks='',$editor='',$lang='ru-RU') {
         $js='
              <script src="http://api-maps.yandex.ru/2.0/?load=package.full&lang='.$lang.'"  type="text/javascript"></script>

    <script type="text/javascript">
        ymaps.ready(init);
    function init () {
            var myMap = new ymaps.Map(\'swmap\', {
                    center: ['.$center.'], // Москва
                    zoom: '.$zoom.',
                    type: \'yandex#'.$type.'\'
                });
                   myMap.controls
                .add(\'zoomControl\')
                .add(\'typeSelector\')
                .add(\'mapTools\');
                
         '.$placemarks.'    
         '.$editor.'
        }
    </script>
             ';
         
         show_window('', $js);

        }
        
/*
 * 
 * Return map mark
 * 
 * @param $coords - map coordinates
 * @param $title - ballon title
 * @param $content - ballon content
 * @param $footer - ballon footer content
 * @param $icon - YM icon class
 * @param $iconlabel - icon label string
 * @return string
 *  
 */   
    function sm_MapAddMark($coords,$title='',$content='',$footer='',$icon='twirl#lightblueIcon',$iconlabel='') {
        $result='
            myPlacemark = new ymaps.Placemark(['.$coords.'], {
                 iconContent: \''.$iconlabel.'\',
                 balloonContentHeader: \''.$title.'\',
                 balloonContentBody: \''.$content.'\',
                 balloonContentFooter: \''.$footer.'\',
               
                } , {
                    preset: \'twirl#blueStretchyIcon\',
                    draggable: false,
                    preset: \''.$icon.'\'
                        
                }),

            myMap.geoObjects.add(myPlacemark)
            ';
        return ($result);
    }
    
   
 /*
 * 
 * Return full map marks for switches with filled GEO field
 * 
 * @return string
 *  
 */
    function sm_MapDrawSwitches() {
        $ym_conf=  rcms_parse_ini_file(CONFIG_PATH."ymaps.ini");
        $query="SELECT * from `switches` WHERE `geo` != '' ";
        $allswitches=  simple_queryall($query);
        $result='';
        //dead switches detection
        $dead_raw=zb_StorageGet('SWDEAD');
        $deadarr=array();
        if ($dead_raw) {
        $deadarr=unserialize($dead_raw);
        }
        
        if (!empty($allswitches)) {
            foreach ($allswitches as $io=>$each) {
                $geo=  mysql_real_escape_string($each['geo']);
                $title=  mysql_real_escape_string($each['ip']);
                $content=  mysql_real_escape_string($each['location']);
                $iconlabel='';
                 
                if (!isset($deadarr[$each['ip']])) {
                    $footer=__('Switch alive');
                    $icon=  sm_MapGoodIcon();
                    //alive mark labels
                    if ($ym_conf['ALIVE_LABEL']) {
                        $iconlabel=$each['location'];
                        } else {
                        $iconlabel='';
                      }
                } else {
                    $footer=__('Switch dead');
                    $icon=  sm_MapBadIcon();
                    //dead mark labels
                     if ($ym_conf['DEAD_LABEL']) {
                        $iconlabel=$each['location'];
                        } else {
                        $iconlabel='';
                      }
                }
                
               
                
                $result.=sm_MapAddMark($geo, $title, $content, $footer, $icon, $iconlabel);
            }
        }
        return ($result);
    }

?>