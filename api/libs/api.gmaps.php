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
    $result = wf_tag('div', false, '', 'id="'.$id.'" style="width: 100%; height:800px;"');
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
    $result = '';
    if ((!empty($apikey)) AND ( $apikey != 'YOUR_API_KEY_HERE')) {
        if (!empty($center)) {
            $center = explode(',', $center);
            $centerLat = trim($center[0]);
            $centerLng = trim($center[1]);
            $centerCode = 'center: uluru';
        } else {
            //not working yet, some R&D reqiured about auto detecting position
            $centerLat = '48.5319';
            $centerLng = '25.0350';
            $centerCode = 'center: uluru';
        }
        $result.= wf_tag('script', false, '', 'type="text/javascript"');
        $result.=' function initMap() {
        var uluru = {lat: ' . $centerLat . ', lng: ' . $centerLng . '};
        var map = new google.maps.Map(document.getElementById(\'' . $container . '\'), {
          zoom: ' . $zoom . ',
          ' . $centerCode . '
        });
        ' . $placemarks . '
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
 *
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
 * Returns good icon class
 * 
 * @param bool $stretchy - icon resizable by content?
 * 
 * @return string
 */
function sm_MapGoodIcon($stretchy = true) {
    if ($stretchy) {
        return ('twirl#lightblueStretchyIcon');
    } else {
        return ('twirl#lightblueIcon');
    }
}

/**
 * Returns bad icon class
 * 
 * @param bool $stretchy - icon resizable by content?
 * 
 * @return string
 */
function sm_MapBadIcon($stretchy = true) {
    if ($stretchy) {
        return ('twirl#redStretchyIcon');
    } else {
        return ('twirl#redIcon');
    }
}

function sm_MapInit($center, $zoom, $type, $placemarks = '', $editor = '', $lang = 'ru-RU') {
    show_window('', gm_MapInit($center, $zoom, $type, $placemarks, $editor, $lang));
}

//need some code here - required for builds placement
function um_MapLocationFinder() {
    return ('');
}

//need some code here - required for swithes placement
function sm_MapLocationFinder() {
    return ('');
}

/**
 * Returns full map marks for switches with filled GEO field
 * 
 * @return string
 */
function sm_MapDrawSwitches() {
    $ym_conf = rcms_parse_ini_file(CONFIG_PATH . "ymaps.ini");
    $query = "SELECT * from `switches` WHERE `geo` != '' ";
    $allswitches = simple_queryall($query);

    $uplinkTraceIcon = wf_img('skins/ymaps/uplinks.png', __('Show links'));
    $switchEditIcon = wf_img('skins/icon_edit.gif', __('Edit'));
    $switchPollerIcon = wf_img('skins/snmp.png', __('SNMP query'));
    $switchLocatorIcon = wf_img('skins/icon_search_small.gif', __('Zoom in'));

    $footerDelimiter = wf_tag('br');
    $result = '';
    //dead switches detection
    $dead_raw = zb_StorageGet('SWDEAD');
    $deadarr = array();
    if ($dead_raw) {
        $deadarr = unserialize($dead_raw);
    }

    if (!empty($allswitches)) {
        foreach ($allswitches as $io => $each) {
            $geo = mysql_real_escape_string($each['geo']);
            $title = mysql_real_escape_string($each['ip']);

            //switch hint content
            $content = mysql_real_escape_string($each['location']);


            $iconlabel = $each['location'];

            if (!isset($deadarr[$each['ip']])) {
                $footer = __('Switch alive');
                $icon = sm_MapGoodIcon();
            } else {
                $footer = __('Switch dead');
                $icon = sm_MapBadIcon();
            }


            //switch footer controls
            $footer.=$footerDelimiter;
            $footer.=wf_tag('a', false, '', 'href="?module=switches&edit=' . $each['id'] . '"') . $switchEditIcon . wf_tag('a', true) . ' ';


            if (!empty($each['snmp'])) {
                $footer.=wf_tag('a', false, '', 'href="?module=switchpoller&switchid=' . $each['id'] . '"') . $switchPollerIcon . wf_tag('a', true) . ' ';
            }

            $footer.=wf_tag('a', false, '', 'href="?module=switchmap&finddevice=' . $each['geo'] . '"') . $switchLocatorIcon . wf_tag('a', true) . ' ';


            if (!empty($each['parentid'])) {
                $uplinkTraceUrl = '?module=switchmap&finddevice=' . $each['geo'] . '&showuplinks=true&traceid=' . $each['id'];
                $uplinkTraceLink = wf_tag('a', false, '', 'href="' . $uplinkTraceUrl . '"') . $uplinkTraceIcon . wf_tag('a', true) . ' ';
                $footer.= $uplinkTraceLink;
            }
            $result.=gm_MapAddMark($geo, $title, $content, $footer, $icon, $iconlabel, true);
        }
    }
    return ($result);
}

function sm_MapAddLine($coord1, $coord2, $color = '', $hint = '', $width = '') {
    return (gm_MapAddLine($coord1, $coord2, $color, $hint, $width));
}

function sm_MapAddCircle($coords, $radius, $content = '', $hint = '') {
    return (gm_MapAddCircle($coords, $radius, $content, $hint));
}

/**
 * Checks is some switch id linked with
 * 
 * @param array $alllinks  Array of id=>parentid
 * @param int  $traceid    Switch ID wich will be traced
 * @param int  $checkid    Switch ID to check
 * 
 * @return bool
 */
function sm_MapIsLinked($alllinks, $traceid, $checkid) {
    $road = array();
    $road[] = $traceid;
    $x = $traceid;


    while (!empty($x)) {
        foreach ($alllinks as $id => $parentid) {
            if ($x == $id) {
                $road[] = $parentid;
                $x = $parentid;
            }
        }
    }

    if (in_array($checkid, $road)) {
        $result = true;
    } else {
        $result = false;
    }
    return ($result);
}

function sm_MapInitQuiet($center, $zoom, $type, $placemarks = '', $editor = '', $lang = 'ru-RU') {
    return (gm_MapInit($center, $zoom, $type, $placemarks, $editor, $lang));
}

function sm_MapInitBasic($center, $zoom, $type, $placemarks = '', $editor = '', $lang = 'ru-RU') {
    return (gm_MapInit($center, $zoom, $type, $placemarks, $editor, $lang));
}

/**
 * Returns full map of switch links
 * 
 * @param int $traceid switch ID to trace uplinks
 * 
 * @return string
 */
function sm_MapDrawSwitchUplinks($traceid = '') {
    global $ubillingConfig;
    $ym_conf = $ubillingConfig->getYmaps();
    $query = "SELECT * from `switches` WHERE `geo` != '' ";
    $tmpSwitches = simple_queryall($query);
    $allswitches = array();
    $alllinks = array();
    $result = '';
    //dead switches detection
    $dead_raw = zb_StorageGet('SWDEAD');
    $deadarr = array();
    if ($dead_raw) {
        $deadarr = unserialize($dead_raw);
    }


    if (!empty($tmpSwitches)) {
        //transform array to id=>switchdata
        foreach ($tmpSwitches as $io => $each) {
            $allswitches[$each['id']] = $each;
        }

        //making id=>parentid array if needed
        if (!empty($traceid)) {
            foreach ($tmpSwitches as $io => $each) {
                $alllinks[$each['id']] = $each['parentid'];
            }
        }
    }

    if (!empty($allswitches)) {
        foreach ($allswitches as $io => $each) {
            if (!empty($each['parentid'])) {
                if (isset($allswitches[$each['parentid']])) {
                    if ($allswitches[$each['parentid']]['geo'] != '') {
                        $coord1 = $each['geo'];
                        $coord2 = $allswitches[$each['parentid']]['geo'];
                        $hint = $each['location'] . ' ' . $each['ip'] . ' → ' . $allswitches[$each['parentid']]['location'] . ' ' . $allswitches[$each['parentid']]['ip'];

                        if ((!isset($deadarr[$each['ip']])) AND ( !isset($deadarr[$allswitches[$each['parentid']]['ip']]))) {
                            $color = '#00FF00';
                        } else {
                            $color = '#FF0000';
                        }

                        //trace mode
                        if (!empty($traceid)) {
                            //switch is traced device
                            if ($each['id'] == $traceid) {
                                $width = 5;
                                $result.=sm_MapAddLine($coord1, $coord2, $color, $hint, $width);
                            } else {
                                //detecting uplinks
                                if (sm_MapIsLinked($alllinks, $traceid, $each['id'])) {
                                    $width = 3;
                                    $result.=sm_MapAddLine($coord1, $coord2, $color, $hint, $width);
                                }
                            }
                        } else {
                            $width = 1;
                            $result.=sm_MapAddLine($coord1, $coord2, $color, $hint, $width);
                        }
                    }
                }
            }
        }
    }


    return ($result);
}

/**
 * Returns indications point to nuclear strikes :)
 * 
 * @return string 
 */
function sm_MapDrawSwitchesCoverage() {
    $ym_conf = rcms_parse_ini_file(CONFIG_PATH . "ymaps.ini");
    $query = "SELECT * from `switches` WHERE `geo` != '' ";
    $allswitches = simple_queryall($query);
    $result = '';
    if (!empty($allswitches)) {
        foreach ($allswitches as $io => $each) {
            $geo = mysql_real_escape_string($each['geo']);
            $result.=sm_MapAddCircle($geo, '100');
        }
    }
    return ($result);
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

function sm_MapAddMark($coords, $title = '', $content = '', $footer = '', $icon = 'twirl#lightblueIcon', $iconlabel = '', $canvas = false) {
    return (gm_MapAddMark($coords, $title, $content, $footer, $icon, $iconlabel, $canvas));
}

/**
 * Returns build icon class 
 * 
 * @param int $usersCount - count of users in building
 * 
 * @return string
 */
function um_MapBuildIcon($usersCount) {
    if ($usersCount < 3) {
        $iconClass = 'twirl#houseIcon';
    } else {
        $iconClass = 'twirl#buildingsIcon';
    }

    if ($usersCount == 0) {
        $iconClass = 'twirl#campingIcon';
    }
    return ($iconClass);
}

/**
 * Returns full map marks for builds with filled GEO field
 * 
 * @return string
 */
function um_MapDrawBuilds() {
    $ym_conf = rcms_parse_ini_file(CONFIG_PATH . "ymaps.ini");
    $query = "SELECT * from `build` WHERE `geo` != '' ";
    $allbuilds = simple_queryall($query);
    $allstreets = zb_AddressGetStreetAllData();
    $streetData = array();
    $cacheDir = 'exports/';
    $cacheTime = 10;
    $cacheTime = time() - ($cacheTime * 60);
    //street id => streetname
    if (!empty($allstreets)) {
        foreach ($allstreets as $ia => $eachstreet) {
            $streetData[$eachstreet['id']] = $eachstreet['streetname'];
        }
    }
    //get apts in all builds aggregated with users logins
    $aptData = array();
    $allapts_q = "SELECT `buildid`,`apt`,`login` from `apt` JOIN `address` ON `apt`.`id`=`address`.`aptid`";
    $allapts = simple_queryall($allapts_q);
    if (!empty($allapts)) {
        $aptData = $allapts;
    }
    //get all user ips
    $alluserips = zb_UserGetAllIPs();
    //form alive ips array 
    $aliveIps = array();
    if (file_exists("exports/nmaphostscan")) {
        $nmapData = file_get_contents("exports/nmaphostscan");
        $nmapData = explodeRows($nmapData);
        if (!empty($nmapData)) {
            foreach ($nmapData as $ic => $eachnmaphost) {
                $zhost = zb_ExtractIpAddress($eachnmaphost);
                if ($zhost) {
                    $aliveIps[$zhost] = $zhost;
                }
            }
        }
    }

    $result = '';


    if (!empty($allbuilds)) {
        foreach ($allbuilds as $io => $each) {
            $geo = mysql_real_escape_string($each['geo']);
            @$streetname = $streetData[$each['streetid']];
            $title = wf_Link("?module=builds&action=editbuild&streetid=" . $each['streetid'] . "&buildid=" . $each['id'], $streetname . ' ' . $each['buildnum'], false);

            $content = '';
            $cells = wf_TableCell(__('apt.'));
            $cells.= wf_TableCell(__('User'));
            $cells.= wf_TableCell(__('Status'));
            $rows = wf_tag('tr', false, '', 'bgcolor=#DCDCDC') . $cells . wf_tag('tr', true);
            $iconlabel = '';
            $footer = '';

            $aliveUsers = 0;
            $usersCount = 0;
            if (!empty($aptData)) {
                //build users data caching
                $cacheName = $cacheDir . $each['id'] . '.inbuildusers';

                if (file_exists($cacheName)) {
                    $updateCache = false;
                    if ((filemtime($cacheName) > $cacheTime)) {
                        $updateCache = false;
                    } else {
                        $updateCache = true;
                    }
                } else {
                    $updateCache = true;
                }
                if (!$updateCache) {
                    $cachePrev = file_get_contents($cacheName);
                    $cachePrev = unserialize($cachePrev);
                    $rows = $cachePrev['rows'];
                    $usersCount = $cachePrev['userscount'];
                    $aliveUsers = $cachePrev['aliveusers'];
                } else {
                    foreach ($aptData as $ib => $eachapt) {
                        if ($eachapt['buildid'] == $each['id']) {
                            if (isset($alluserips[$eachapt['login']])) {
                                $userIp = $alluserips[$eachapt['login']];
                                $usersCount++;
                                if (isset($aliveIps[$userIp])) {
                                    $aliveFlag = web_bool_led(true);
                                    $aliveUsers++;
                                } else {
                                    $aliveFlag = web_bool_led(false);
                                }
                                $cells = wf_TableCell($eachapt['apt']);
                                $cells.= wf_TableCell(wf_Link('?module=userprofile&username=' . $eachapt['login'], $userIp, false));
                                $cells.= wf_TableCell($aliveFlag);
                                $rows.=wf_TableRow($cells);
                            }
                        }
                    }
                    $cacheStore = array();
                    $cacheStore['rows'] = $rows;
                    $cacheStore['userscount'] = $usersCount;
                    $cacheStore['aliveusers'] = $aliveUsers;
                    $cacheStore = serialize($cacheStore);
                    file_put_contents($cacheName, $cacheStore);
                }
            }
            $footer = __('Active') . ' ' . $aliveUsers . '/' . $usersCount;
            $icon = um_MapBuildIcon($usersCount);

            $content = json_encode(wf_TableBody($rows, '', 0));
            $title = json_encode($title);

            $content = str_replace('"', '', $content);
            $content = str_replace("'", '', $content);
            $content = str_replace("\n", '', $content);

            $title = str_replace('"', '', $title);
            $title = str_replace("'", '', $title);
            $title = str_replace("\n", '', $title);

            $result.=sm_MapAddMark($geo, $title, $content, $footer, $icon, $iconlabel, true);
        }
    }
    return ($result);
}

?>