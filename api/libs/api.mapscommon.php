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

            if ($ym_conf['CANVAS_RENDER']) {
                $result.=sm_MapAddMark($geo, $title, $content, $footer, $icon, $iconlabel, true);
            } else {
                $result.=sm_MapAddMark($geo, $title, $content, $footer, $icon, $iconlabel, false);
            }
        }
    }
    return ($result);
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
            $inputs.=wf_Submit('Save');
            $result.=$inputs;
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
            $inputs.=wf_Submit('Save');
            $result.=$inputs;
        }
    }
    return ($result);
}
