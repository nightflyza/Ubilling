<?php

/**
 * Returns full map marks for builds with filled GEO field
 * 
 * @param int $buildIdFilter return only one build placemark
 * 
 * @return string
 */
function um_MapDrawBuilds($buildIdFilter = '') {
    global $ubillingConfig;
    $result = '';
    $allUserData=zb_UserGetAllDataCache();
    $buildIdFilter = ubRouting::filters($buildIdFilter, 'int');
    $defferedLoading = ($ubillingConfig->getAlterParam('BUILDMAP_DEFERRED')) ? true : false;
    if ($defferedLoading) {
        if (!function_exists('generic_MapAddMarkDynamic')) {
            $defferedLoading = false;
        }
    }
    $query = "SELECT * from `build` WHERE `geo` != '' ";
    //optional filter here
    if ($buildIdFilter) {
        $query .= " AND `id`='" . $buildIdFilter . "'";
    }
    $allbuilds = simple_queryall($query);
    $allstreets = zb_AddressGetStreetAllData();
    $alluserips = zb_UserGetAllIPs();
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

    //get all Online users if available
    $dnUsers = rcms_scandir('content/dn');
    if (!empty($dnUsers)) {
        $dnUsers = array_flip($dnUsers);
    }

    if (!empty($allbuilds)) {
        foreach ($allbuilds as $io => $each) {
            $geo = mysql_real_escape_string($each['geo']);
            @$streetname = $streetData[$each['streetid']];
            $title = wf_Link("?module=builds&action=editbuild&frommaps=true&streetid=" . $each['streetid'] . "&buildid=" . $each['id'], $streetname . ' ' . $each['buildnum'], false);

            $content = '';
            $cells = wf_TableCell(__('apt.'));
            $cells .= wf_TableCell(__('User'));
            $cells .= wf_TableCell(__('Active'));
            $cells .= wf_TableCell(__('Online'));
            $rows = wf_tag('tr', false, 'row1', 'bgcolor=#DCDCDC') . $cells . wf_tag('tr', true);
            $iconlabel = '';
            $footer = '';

            $onlineUsers = 0;
            $activeUsers = 0;
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
                    $usersCount = @$cachePrev['userscount'];
                    $onlineUsers = @$cachePrev['onlineusers'];
                    $activeUsers = @$cachePrev['activeusers'];
                } else {
                    //all cache need to be updated
                    foreach ($aptData as $ib => $eachapt) {
                        if ($eachapt['buildid'] == $each['id']) {
                            if (isset($alluserips[$eachapt['login']])) {
                                $usersCount++;
                                $userLogin = $eachapt['login'];
                                $userIp = $alluserips[$eachapt['login']];

                                //online flag
                                if (isset($dnUsers[$userLogin])) {
                                    $onlineFlag = web_bool_star(true);
                                    $onlineUsers++;
                                    $onlineKey = 'l';
                                } else {
                                    $onlineFlag = web_bool_star(false);
                                    $onlineKey = 'd';
                                }

                                //activity flag
                                $activeFlag = web_bool_led(false);
                                $activeKey = '0';

                                if (isset($allUserData[$userLogin])) {
                                   $activityState=zb_UserIsAlive($allUserData[$userLogin]);
                                   switch ($activityState) {
                                    case -1:
                                        $activeFlag = web_yellow_led();
                                        $activeKey = '-1';
                                        break;
                                    case 1:
                                        $activeFlag = web_bool_led(true);
                                        $activeKey = '1';
                                        $activeUsers++;
                                        break;
                                    case 0:
                                        $activeFlag = web_bool_led(false);
                                        $activeKey = '0';
                                        break;
                                }

                                } 


                                $cells = wf_TableCell($eachapt['apt']);
                                $cells .= wf_TableCell(wf_Link('?module=userprofile&username=' . $userLogin, $userIp, false));
                                $cells .= wf_TableCell($activeFlag, '', '', 'sorttable_customkey="' . $activeKey . '"');
                                $cells .= wf_TableCell($onlineFlag, '', '', 'sorttable_customkey="' . $onlineKey . '"');
                                $rows .= wf_TableRow($cells, 'row5');
                            }
                        }
                    }

                    $cachedData[$each['id']]['rows'] = $rows;
                    $cachedData[$each['id']]['userscount'] = $usersCount;
                    $cachedData[$each['id']]['onlineusers'] = $onlineUsers;
                    $cachedData[$each['id']]['activeusers'] = $activeUsers;
                }
            }

            $footer = __('Active').'/'.__('Online').'/'.__('Total').': ' . $activeUsers . '/' . $onlineUsers . '/' . $usersCount;
            
            $icon = um_MapBuildIcon($usersCount);

            $content = json_encode(wf_TableBody($rows, '', 0));
            $title = json_encode($title);

            $content = str_replace('"', '', $content);
            $content = str_replace("'", '', $content);
            $content = str_replace("\n", '', $content);

            $title = str_replace('"', '', $title);
            $title = str_replace("'", '', $title);
            $title = str_replace("\n", '', $title);

            if ($defferedLoading) {
                $result .= generic_MapAddMarkDynamic($geo, $title, '?module=usersmap&getbuildusers=' . $each['id'], $icon);
            } else {
                $result .= generic_MapAddMark($geo, $title, $content, $footer, $icon, $iconlabel, true);
            }
        }

        //update cache data if required
        if ($updateCache) {
            $cache->set('INBUILDUSERS', $cachedData, $cacheTime);
        }
    }
    return ($result);
}

/**
 * Retrieves and formats building data including address and user statistics
 * 
 * This function gets building information from cache and formats it with address,
 * user table data and statistics about active/total users.
 *
 * @param mixed $buildId Building ID to retrieve data for
 * 
 * @return string 
 */
function um_GetBuildData($buildId) {
    $buildId = ubRouting::filters($buildId, 'int');
    $result = 'Oo';
    $cache = new UbillingCache();
    $cacheTime = 3600;
    $cachedData = $cache->get('INBUILDUSERS', $cacheTime);
    if (is_array($cachedData) and !empty($cachedData)) {
        if (isset($cachedData[$buildId])) {
            $result = '';
            $allBuildsAddress = zb_AddressGetBuildAllAddress(false);
            if (isset($allBuildsAddress[$buildId])) {
                $result .= wf_tag('b') . $allBuildsAddress[$buildId] . wf_tag('b', true);
            }
            $buildData = $cachedData[$buildId];
            $result .= wf_TableBody($buildData['rows'], '', 0);
            $result .= wf_delimiter(0);
            $result .= __('Active') . '/' . __('Online') . '/' . __('Total') . ': ' . @$buildData['activeusers'] . '/' . @$buildData['onlineusers'] . '/' . @$buildData['userscount'];
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
