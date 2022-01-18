<?php

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

/**
 * Returns array of switch parent switches with himself
 * 
 * @param array $alllinks all switches links
 * @param int $traceid switch name to trace
 * 
 * @return array
 */
function zb_SwitchGetParents($alllinks, $traceid) {
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

    $result = $road;
    return ($result);
}

/**
 * Checks is loop possible after setting for switchId something as parent device
 * 
 * @param array $alllinks
 * @param int $switchId
 * @param int $setParent
 * 
 * @return bool
 */
function sm_CheckLoop($alllinks, $switchId, $setParent) {
    $result = false;
    $tmpArr = array();
    if (!empty($switchId)) {
        if (sm_MapIsLinked($alllinks, $setParent, $switchId)) {
            $result = false;
        } else {
            $result = true;
        }
    } else {
        $result = true;
    }
    return ($result);
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
    $query = "SELECT * from `switches`";
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
                    if (($allswitches[$each['parentid']]['geo'] != '') AND ( $each['geo'] != '')) {
                        $coord1 = $each['geo'];
                        $coord2 = $allswitches[$each['parentid']]['geo'];
                        $hint = $each['location'] . ' ' . $each['ip'] . ' → ' . $allswitches[$each['parentid']]['location'] . ' ' . $allswitches[$each['parentid']]['ip'];

                        if ((!isset($deadarr[$each['ip']])) AND ( !isset($deadarr[$allswitches[$each['parentid']]['ip']]))) {
                            $color = '#00FF00';
                        } else {
                            $color = '#FF0000';
                        }

                        /**
                         * Круглый год без забот жить бы в норке как енот,
                         * Вырыть ход в огород, воровать, что в нём растёт,
                         * Но боюсь, снег пойдёт - все тропинки заметёт.
                         * Кто-нибудь не разберёт и с ружьём за мной придёт.
                         * 
                         * 
                         * Жрать не буду целый день, и сдохну всем на зло!
                         * Пусть охотники идут - им не повезло!
                         */
                        //trace mode
                        if (!empty($traceid)) {
                            //switch is traced device
                            if ($each['id'] == $traceid) {
                                $width = 5;
                                $result .= sm_MapAddLine($coord1, $coord2, $color, $hint, $width);
                            } else {
                                //detecting uplinks
                                if (sm_MapIsLinked($alllinks, $traceid, $each['id'])) {
                                    $width = 3;
                                    $result .= sm_MapAddLine($coord1, $coord2, $color, $hint, $width);
                                }
                            }
                        } else {
                            $width = 1;
                            $result .= sm_MapAddLine($coord1, $coord2, $color, $hint, $width);
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
            $result .= sm_MapAddCircle($geo, '100');
        }
    }
    return ($result);
}

/**
 * Returns full map marks for switches with filled GEO field
 * 
 * @return string
 *  
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


            $iconlabel = '';

            if (!isset($deadarr[$each['ip']])) {
                $footer = __('Switch alive');

                if ($ym_conf['CANVAS_RENDER']) {
                    if ($ym_conf['CANVAS_RENDER_IGNORE_LABELED']) {
                        if ($ym_conf['ALIVE_LABEL']) {
                            $icon = sm_MapGoodIcon();
                        } else {
                            $icon = sm_MapGoodIcon(false);
                        }
                    } else {
                        $icon = sm_MapGoodIcon(false);
                    }
                } else {
                    $icon = sm_MapGoodIcon();
                }
                //alive mark labels
                if ($ym_conf['ALIVE_LABEL']) {
                    $iconlabel = $each['location'];
                } else {
                    $iconlabel = '';
                }
            } else {
                $footer = __('Switch dead');

                if ($ym_conf['CANVAS_RENDER']) {
                    if ($ym_conf['CANVAS_RENDER_IGNORE_LABELED']) {
                        if ($ym_conf['DEAD_LABEL']) {
                            $icon = sm_MapBadIcon();
                        } else {
                            $icon = sm_MapBadIcon(false);
                        }
                    } else {
                        $icon = sm_MapBadIcon(false);
                    }
                } else {
                    $icon = sm_MapBadIcon();
                }
                //dead mark labels
                if ($ym_conf['DEAD_LABEL']) {
                    if (!empty($each['location'])) {
                        $iconlabel = $each['location'];
                    } else {
                        $iconlabel = __('No location set');
                    }
                } else {
                    $iconlabel = '';
                }
            }


            //switch footer controls
            $footer .= $footerDelimiter;
            $footer .= wf_tag('a', false, '', 'href="?module=switches&edit=' . $each['id'] . '"') . $switchEditIcon . wf_tag('a', true) . ' ';


            if (!empty($each['snmp'])) {
                $footer .= wf_tag('a', false, '', 'href="?module=switchpoller&switchid=' . $each['id'] . '"') . $switchPollerIcon . wf_tag('a', true) . ' ';
            }

            $footer .= wf_tag('a', false, '', 'href="?module=switchmap&finddevice=' . $each['geo'] . '"') . $switchLocatorIcon . wf_tag('a', true) . ' ';


            if (!empty($each['parentid'])) {
                $uplinkTraceUrl = '?module=switchmap&finddevice=' . $each['geo'] . '&showuplinks=true&traceid=' . $each['id'];
                $uplinkTraceLink = wf_tag('a', false, '', 'href="' . $uplinkTraceUrl . '"') . $uplinkTraceIcon . wf_tag('a', true) . ' ';
                $footer .= $uplinkTraceLink;
            }

            if ($ym_conf['CANVAS_RENDER']) {
                $result .= sm_MapAddMark($geo, $title, $content, $footer, $icon, $iconlabel, true);
            } else {
                $result .= sm_MapAddMark($geo, $title, $content, $footer, $icon, $iconlabel, false);
            }
        }
    }
    return ($result);
}

/**
 * Returns full map marks for builds with filled GEO field
 * 
 * @param int $buildIdFilter return only one build placemark
 * 
 * @return string
 */
function um_MapDrawBuilds($buildIdFilter = '') {
    $buildIdFilter = ubRouting::filters($buildIdFilter, 'int');
    $ym_conf = rcms_parse_ini_file(CONFIG_PATH . "ymaps.ini");
    $query = "SELECT * from `build` WHERE `geo` != '' ";
    //optional filter here
    if ($buildIdFilter) {
        $query .= " AND `id`='" . $buildIdFilter . "'";
    }
    $allbuilds = simple_queryall($query);
    $allstreets = zb_AddressGetStreetAllData();
    $streetData = array();

    $cache = new UbillingCache();
    $cacheTime = 3600;
    //reading cached data
    $cachedData = $cache->get('INBUILDUSERS', $cacheTime);
    if (empty($cachedData)) {
        $cachedData = array();
        $updateCache = true;
    } else {
        $updateCache = false;
    }

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
            $title = wf_Link("?module=builds&action=editbuild&frommaps=true&streetid=" . $each['streetid'] . "&buildid=" . $each['id'], $streetname . ' ' . $each['buildnum'], false);

            $content = '';
            $cells = wf_TableCell(__('apt.'));
            $cells .= wf_TableCell(__('User'));
            $cells .= wf_TableCell(__('Status'));
            $rows = wf_tag('tr', false, 'row1', 'bgcolor=#DCDCDC') . $cells . wf_tag('tr', true);
            $iconlabel = '';
            $footer = '';

            $aliveUsers = 0;
            $usersCount = 0;
            if (!empty($aptData)) {
                //is current build in cache
                if (isset($cachedData[$each['id']])) {
                    $updateCache = false;
                } else {
                    $updateCache = true;
                }
                //cache in actual state
                if (!$updateCache) {
                    //build extracted from cache
                    $cachePrev = $cachedData[$each['id']];

                    $rows = $cachePrev['rows'];
                    $usersCount = $cachePrev['userscount'];
                    $aliveUsers = $cachePrev['aliveusers'];
                } else {
                    //all cache need to be updated
                    foreach ($aptData as $ib => $eachapt) {
                        if ($eachapt['buildid'] == $each['id']) {
                            if (isset($alluserips[$eachapt['login']])) {
                                $userIp = $alluserips[$eachapt['login']];
                                $usersCount++;
                                if (isset($aliveIps[$userIp])) {
                                    $aliveFlag = web_bool_led(true);
                                    $aliveUsers++;
                                    $aliveKey = 'live';
                                } else {
                                    $aliveFlag = web_bool_led(false);
                                    $aliveKey = 'dead';
                                }


                                $cells = wf_TableCell($eachapt['apt']);
                                $cells .= wf_TableCell(wf_Link('?module=userprofile&username=' . $eachapt['login'], $userIp, false));
                                $cells .= wf_TableCell($aliveFlag, '', '', 'sorttable_customkey="' . $aliveKey . '"');
                                $rows .= wf_TableRow($cells, 'row5');
                            }
                        }
                    }

                    $cachedData[$each['id']]['rows'] = $rows;
                    $cachedData[$each['id']]['userscount'] = $usersCount;
                    $cachedData[$each['id']]['aliveusers'] = $aliveUsers;
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

            $result .= sm_MapAddMark($geo, $title, $content, $footer, $icon, $iconlabel, true);
        }

        //update cache data if required
        if ($updateCache) {
            $cache->set('INBUILDUSERS', $cachedData, $cacheTime);
        }
    }
    return ($result);
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
 * Returns form for placing switch to selected coordinates
 * 
 * @return string
 */
function um_MapLocationBuildForm() {
    $query = "SELECT * from `build` WHERE `geo` IS NULL OR `geo`='' ORDER by `streetid`";
    $allNoGeoBuilds = simple_queryall($query);
    $buildData = array();
    $streetData = array();
    $cityData = array();
    $result = '';

    $selectedBuild = (wf_CheckGet(array('placebld'))) ? vf($_GET['placebld'], 3) : '';

    if (!empty($allNoGeoBuilds)) {
        $allCities = zb_AddressGetFullCityNames();
        $allStreets = zb_AddressGetStreetAllData();
        if (!empty($allStreets)) {
            foreach ($allStreets as $ia => $eachstreet) {
                $streetData[$eachstreet['id']] = $eachstreet['streetname'];
                if (isset($allCities[$eachstreet['cityid']])) {
                    $cityData[$eachstreet['id']] = $allCities[$eachstreet['cityid']];
                }
            }
        }

        foreach ($allNoGeoBuilds as $io => $each) {
            @$streetname = $streetData[$each['streetid']];
            $streetcity = (isset($cityData[$each['streetid']])) ? $cityData[$each['streetid']] . ' ' : '';
            $buildData[$each['id']] = $streetcity . $streetname . ' - ' . $each['buildnum'];
        }
        //form construct
        if (cfr('BUILDS')) {
            $inputs = wf_Selector('buildplacing', $buildData, '', $selectedBuild, true);
            $inputs .= wf_Submit('Save');
            $result .= $inputs;
        }
    }
    return ($result);
}

/**
 * Returns form for placing switch to selected coordinates
 * 
 * @return string
 */
function sm_MapLocationSwitchForm() {
    $query = "SELECT * from `switches` WHERE `geo`='' AND `desc` NOT LIKE '%NP%'";
    $allNoGeoSwitches = simple_queryall($query);
    $switchData = array();
    $result = '';

    if (!empty($allNoGeoSwitches)) {
        foreach ($allNoGeoSwitches as $io => $each) {
            $cleanLocation = str_replace("'", '`', $each['location']);
            $switchData[$each['id']] = $each['ip'] . ' - ' . $cleanLocation;
        }
        //form construct
        if (cfr('SWITCHESEDIT')) {
            //preselect some switch if required
            if (wf_CheckGet(array('placesw'))) {
                $selected = $_GET['placesw'];
            } else {
                $selected = '';
            }
            $inputs = wf_Selector('switchplacing', $switchData, '', $selected, true);
            $inputs .= wf_Submit('Save');
            $result .= $inputs;
        }
    }
    return ($result);
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
    return (generic_MapAddMark($coords, $title, $content, $footer, $icon, $iconlabel, $canvas));
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
    return (generic_MapAddCircle($coords, $radius, $content, $hint));
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
    return (generic_MapAddLine($coord1, $coord2, $color, $hint, $width));
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
    show_window('', generic_MapInit($center, $zoom, $type, $placemarks, $editor, $lang));
}

/**
 * Return geo coordinates locator for builds
 * 
 * @return string
 */
function um_MapLocationFinder() {
    $title = wf_tag('b') . __('Place coordinates') . wf_tag('b', true);
    $data = um_MapLocationBuildForm();
    $result = generic_MapEditor('placecoords', $title, $data);
    return ($result);
}

/**
 * Returns geo coordinates locator
 * 
 * @return string
 */
function sm_MapLocationFinder() {
    $title = wf_tag('b') . __('Place coordinates') . wf_tag('b', true);
    $data = sm_MapLocationSwitchForm();
    $result = generic_MapEditor('placecoords', $title, $data);
    return ($result);
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
    return (generic_MapInit($center, $zoom, $type, $placemarks, $editor, $lang));
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
    return (generic_MapInit($center, $zoom, $type, $placemarks, $editor, $lang));
}

/**
 * Shows map container
 *
 * @return void
 *  
 */
function sm_ShowMapContainer() {
    $container = wf_tag('div', false, '', 'id="ubmap" style="width: 1000; height:800px;"');
    $container .= wf_tag('div', true);
    $controls = '';
    if (cfr('USERSMAP')) {
        $controls .= wf_Link("?module=usersmap", wf_img('skins/ymaps/build.png') . ' ' . __('Builds map'), false, 'ubButton');
    }
    if (cfr('SWITCHMAP')) {
        $controls .= wf_Link("?module=switchmap", wf_img('skins/ymaps/network.png') . ' ' . __('Switches map'), false, 'ubButton');
    }
    if (cfr('SWITCHESEDIT')) {
        $controls .= wf_Link("?module=switchmap&locfinder=true", wf_img('skins/ymaps/edit.png') . ' ' . __('Edit map'), false, 'ubButton');
    }
    $controls .= wf_Link("?module=switchmap&showuplinks=true", wf_img('skins/ymaps/uplinks.png') . ' ' . __('Show links'), false, 'ubButton');
    $controls .= wf_Link("?module=switchmap&coverage=true", wf_img('skins/ymaps/coverage.png') . ' ' . __('Coverage area'), false, 'ubButton');
    if (cfr('SWITCHES')) {
        $controls .= wf_Link("?module=switches", wf_img('skins/ymaps/switchdir.png') . ' ' . __('Available switches'), true, 'ubButton');
    }
    $controls .= wf_delimiter(1);

    show_window(__('Active equipment map'), $controls . $container);
}

/**
 * Shows map container for builds
 *
 * @return void
 */
function um_ShowMapContainer() {
    $container = wf_tag('div', false, '', 'id="ubmap" style="width: 1000; height:800px;"');
    $container .= wf_tag('div', true);
    $controls = '';
    if (cfr('SWITCHMAP')) {
        $controls .= wf_Link("?module=switchmap", wf_img('skins/ymaps/network.png') . ' ' . __('Switches map'), false, 'ubButton');
    }
    if (cfr('USERSMAP')) {
        $controls .= wf_Link("?module=usersmap", wf_img('skins/ymaps/build.png') . ' ' . __('Builds map'), false, 'ubButton');
    }
    if (cfr('BUILDS')) {
        $controls .= wf_Link("?module=usersmap&locfinder=true", wf_img('skins/ymaps/edit.png') . ' ' . __('Edit map'), false, 'ubButton');
    }
    $controls .= wf_delimiter(1);

    show_window(__('Builds and users map'), $controls . $container);
}
