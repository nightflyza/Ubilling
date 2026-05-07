<?php

/**
 * Builds and users map service based on MapCore API.
 */
class BuildsMap {
    /**
     * Ubilling configuration provider.
     *
     * @var object
     */
    protected $ubillingConfig = null;

    /**
     * Builds database abstraction layer.
     *
     * @var object
     */
    protected $buildDb = null;

    /**
     * MapCore instance.
     *
     * @var object
     */
    protected $mapCore = null;

    /**
     * Cache instance.
     *
     * @var object
     */
    protected $cache = null;

    const TABLE_BUILDS = 'build';
    const BUILD_USERS_CACHE = 'INBUILDUSERS';
    const BUILD_USERS_CACHE_TTL = 3600;

    /**
     * Creates BuildsMap service instance.
     *
     * @global object $ubillingConfig
     */
    public function __construct() {
        global $ubillingConfig;
        $this->ubillingConfig = $ubillingConfig;
        $this->initDb();
        $this->initMapCore();
        $this->initCache();
    }

    /**
     * Inits builds database abstraction layer.
     *
     * @return void
     */
    protected function initDb() {
        $this->buildDb = new NyanORM(self::TABLE_BUILDS);
    }

    /**
     * Inits MapCore instance.
     *
     * @return void
     */
    protected function initMapCore() {
        $this->mapCore = new MapCore('buildsmap');
    }

    /**
     * Inits cache instance.
     *
     * @return void
     */
    protected function initCache() {
        $this->cache = new UbillingCache();
    }

    /**
     * Persists posted building coordinates from editor form.
     *
     * @return void
     */
    public function saveBuildPlacement() {
        if (ubRouting::checkPost(array('buildplacing', 'placecoords'))) {
            if (cfr('BUILDS')) {
                zb_AddressChangeBuildGeo(ubRouting::post('buildplacing'), ubRouting::post('placecoords'));
                ubRouting::nav('?module=usersmap&locfinder=true');
            } else {
                show_window(__('Error'), __('Access denied'));
            }
        }
    }

    /**
     * Returns build icon key by users count.
     *
     * @param int $usersCount
     *
     * @return string
     */
    public function getBuildIcon($usersCount) {
        $result = 'marker.building';
        if ($usersCount < 3) {
            $result = 'marker.house';
        }
        if ($usersCount == 0) {
            $result = 'marker.camping';
        }
        return ($result);
    }

    /**
     * Returns full map marks for builds with filled GEO field.
     *
     * @param int $buildIdFilter
     *
     * @return string
     */
    public function drawBuilds($buildIdFilter = '') {
        $buildIdFilter = ubRouting::filters($buildIdFilter, 'int');
        $result = '';
        $allUserData = zb_UserGetAllDataCache();
        $allbuilds = $this->getBuildsWithGeo($buildIdFilter);
        $allstreets = zb_AddressGetStreetAllData();
        $alluserips = zb_UserGetAllIPs();
        $streetData = array();
        $mapCore = $this->mapCore;
        $deferredLoading = ($this->ubillingConfig->getAlterParam('BUILDMAP_DEFERRED')) ? true : false;
        $cacheData = $this->getBuildUsersCacheData();
        $cachedData = $cacheData;
        $cacheChanged = false;
        $aptData = $this->getAptUsersData();
        $dnUsers = $this->getOnlineUsersMap();

        if (!empty($allstreets)) {
            foreach ($allstreets as $ia => $eachstreet) {
                $streetData[$eachstreet['id']] = $eachstreet['streetname'];
            }
        }

        if (!empty($allbuilds)) {
            foreach ($allbuilds as $io => $each) {
                $geo = ubRouting::filters($each['geo'], 'mres');
                $streetname = isset($streetData[$each['streetid']]) ? $streetData[$each['streetid']] : '';
                $title = wf_Link("?module=builds&action=editbuild&frommaps=true&streetid=" . $each['streetid'] . "&buildid=" . $each['id'], $streetname . ' ' . $each['buildnum'], false);
                $buildData = $this->getBuildUsersDataFromCache($each['id'], $aptData, $alluserips, $allUserData, $dnUsers, $cachedData);
                $cachedData = $buildData['cachedData'];
                if ($buildData['cacheUpdated']) {
                    $cacheChanged = true;
                }

                $usersCount = $buildData['userscount'];
                $onlineUsers = $buildData['onlineusers'];
                $activeUsers = $buildData['activeusers'];
                $rows = $buildData['rows'];
                $footer = __('Active') . '/' . __('Online') . '/' . __('Total') . ': ' . $activeUsers . '/' . $onlineUsers . '/' . $usersCount;
                $icon = $this->getBuildIcon($usersCount);
                $content = wf_TableBody($rows, '', 0);
                if ($deferredLoading) {
                    $mapCore->addDynamicMarker($geo, $title, '?module=usersmap&getbuildusers=' . $each['id'], array('icon' => $icon, 'tooltip' => $title));
                } else {
                    $markerOptions = array(
                        'icon' => $icon,
                        'tooltip' => $title,
                        'popupTitle' => $title,
                        'popupFooter' => $footer
                    );
                    $mapCore->addMarker($geo, $content, $markerOptions);
                }
            }
        }

        if ($cacheChanged) {
            $this->cache->set(self::BUILD_USERS_CACHE, $cachedData, self::BUILD_USERS_CACHE_TTL);
        }

        return ($result);
    }

    /**
     * Returns build popup html by building id from cache.
     *
     * @param int $buildId
     *
     * @return string
     */
    public function getBuildData($buildId) {
        $result = 'Oo';
        $buildId = ubRouting::filters($buildId, 'int');
        $cachedData = $this->getBuildUsersCacheData();
        if (is_array($cachedData) and !empty($cachedData)) {
            if (isset($cachedData[$buildId])) {
                $allBuildsAddress = zb_AddressGetBuildAllAddress(false);
                $buildData = $cachedData[$buildId];
                $result = '';
                if (isset($allBuildsAddress[$buildId])) {
                    $result .= wf_tag('b') . $allBuildsAddress[$buildId] . wf_tag('b', true);
                }
                $result .= wf_TableBody($buildData['rows'], '', 0);
                $result .= wf_delimiter(0);
                $result .= __('Active') . '/' . __('Online') . '/' . __('Total') . ': ' . @$buildData['activeusers'] . '/' . @$buildData['onlineusers'] . '/' . @$buildData['userscount'];
            }
        }
        return ($result);
    }

    /**
     * Returns form for placing build to selected coordinates.
     *
     * @return string
     */
    public function getLocationBuildForm() {
        $result = '';
        $selectedBuild = (ubRouting::checkGet('placebld')) ? ubRouting::get('placebld', 'int') : '';
        $buildData = array();
        $streetData = array();
        $cityData = array();
        $allNoGeoBuilds = $this->getBuildsWithoutGeo();

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
                $streetname = isset($streetData[$each['streetid']]) ? $streetData[$each['streetid']] : '';
                $streetcity = (isset($cityData[$each['streetid']])) ? $cityData[$each['streetid']] . ' ' : '';
                $buildData[$each['id']] = $streetcity . $streetname . ' - ' . $each['buildnum'];
            }
            if (cfr('BUILDS')) {
                $inputs = wf_Selector('buildplacing', $buildData, '', $selectedBuild, true);
                $inputs .= wf_Submit('Save');
                $result .= $inputs;
            }
        }

        return ($result);
    }

    /**
     * Returns geo coordinates locator for builds.
     *
     * @return string
     */
    public function getLocationFinder() {
        $result = '';
        $title = wf_tag('b') . __('Place coordinates') . wf_tag('b', true);
        $data = $this->getLocationBuildForm();
        $this->mapCore->addLocationEditor('placecoords', $title, $data);
        return ($result);
    }

    /**
     * Renders map controls and map container block.
     *
     * @return void
     */
    public function renderMapContainer() {
        $container = wf_tag('div', false, '', 'id="buildsmap" style="width: 1000; height:800px;"');
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

    /**
     * Creates configured MapCore instance with all overlays.
     *
     * @return object
     */
    public function getMapCore() {
        $ymconf = $this->ubillingConfig->getYmaps();
        $ymCenter = $ymconf['CENTER'];
        $ymZoom = $ymconf['ZOOM'];
        $ymType = $ymconf['TYPE'];
        

        if (ubRouting::checkGet('findbuild')) {
            $ymZoom = $ymconf['FINDING_ZOOM'];
            $ymCenter = ubRouting::get('findbuild', 'vf');
            if ($ymconf['FINDING_CIRCLE']) {
                $radius = 30;
                $this->mapCore->addCircle($ymCenter, $radius, __('Search area radius') . ' ' . $radius . ' ' . __('meters'), array('hint' => __('Search area')));
            }
        }

        $this->mapCore->setZoom($ymZoom);
        $this->mapCore->setType($ymType);
        if (!empty($ymCenter)) {
            $this->mapCore->setCenter($ymCenter);
        }

        $this->drawBuilds('');
        if (ubRouting::checkGet('locfinder')) {
            $this->getLocationFinder();
        }
        if (ubRouting::checkGet('placebld')) {
            $allBuildsAddr = zb_AddressGetBuildAllAddress();
            $buildLookupId = ubRouting::get('placebld', 'int');
            $searchPrefill = isset($allBuildsAddr[$buildLookupId]) ? $allBuildsAddr[$buildLookupId] : '';
            $this->mapCore->setSearchPrefill($searchPrefill);
        }

        return ($this->mapCore);
    }

    /**
     * Renders full builds map page output.
     *
     * @return void
     */
    public function render() {
        $this->renderMapContainer();
        $mapCore = $this->getMapCore();
        show_window('', $mapCore->render());
    }

    /**
     * Returns builds layer as MapCore-compatible map objects payload.
     *
     * @param int $buildIdFilter
     *
     * @return array
     */
    public function getBuildsMapObjects($buildIdFilter = '') {
        $this->drawBuilds($buildIdFilter);
        $result = $this->mapCore->getMapObjects();
        return ($result);
    }

    /**
     * Returns builds layer placemarks JS buffer only.
     *
     * @param int $buildIdFilter
     *
     * @return string
     */
    public function getBuildsPlacemarks($buildIdFilter = '') {
        $this->drawBuilds($buildIdFilter);
        $result = $this->mapCore->getPlacemarks();
        return ($result);
    }

    /**
     * Returns builds with non-empty GEO field.
     *
     * @param int $buildIdFilter
     *
     * @return array
     */
    protected function getBuildsWithGeo($buildIdFilter = '') {
        $buildIdFilter = ubRouting::filters($buildIdFilter, 'int');
        $this->buildDb->where('geo', '!=', '');
        if (!empty($buildIdFilter)) {
            $this->buildDb->where('id', '=', $buildIdFilter);
        }
        $result = $this->buildDb->getAll();
        return ($result);
    }

    /**
     * Returns builds without GEO.
     *
     * @return array
     */
    protected function getBuildsWithoutGeo() {
        $this->buildDb->whereRaw("`geo` IS NULL OR `geo`=''");
        $this->buildDb->orderBy('streetid');
        $result = $this->buildDb->getAll();
        return ($result);
    }

    /**
     * Returns apt->build user mapping rows.
     *
     * @return array
     */
    protected function getAptUsersData() {
        $result = array();
        $allapts_q = "SELECT `buildid`,`apt`,`login` from `apt` JOIN `address` ON `apt`.`id`=`address`.`aptid`";
        $allapts = simple_queryall($allapts_q);
        if (!empty($allapts)) {
            $result = $allapts;
        }
        return ($result);
    }

    /**
     * Returns map with currently online users.
     *
     * @return array
     */
    protected function getOnlineUsersMap() {
        $result = array();
        $dnUsers = rcms_scandir('content/dn');
        if (!empty($dnUsers)) {
            $result = array_flip($dnUsers);
        }
        return ($result);
    }

    /**
     * Returns build users cache envelope.
     *
     * @return array
     */
    protected function getBuildUsersCacheData() {
        $result = array();
        $cachedData = $this->cache->get(self::BUILD_USERS_CACHE, self::BUILD_USERS_CACHE_TTL);
        if (is_array($cachedData) and !empty($cachedData)) {
            $result = $cachedData;
        }
        return ($result);
    }

    /**
     * Gets users table/statistics for single build from cache or computes it.
     *
     * @param int $buildId
     * @param array $aptData
     * @param array $allUserIps
     * @param array $allUserData
     * @param array $dnUsers
     * @param array $cachedData
     *
     * @return array
     */
    protected function getBuildUsersDataFromCache($buildId, $aptData, $allUserIps, $allUserData, $dnUsers, $cachedData) {
        $result = array(
            'rows' => '',
            'userscount' => 0,
            'onlineusers' => 0,
            'activeusers' => 0,
            'cacheUpdated' => false,
            'cachedData' => $cachedData
        );
        $cells = wf_TableCell(__('apt.'));
        $cells .= wf_TableCell(__('User'));
        $cells .= wf_TableCell(__('Active'));
        $cells .= wf_TableCell(__('Online'));
        $headerRows = wf_tag('tr', false, 'row1', 'bgcolor=#DCDCDC') . $cells . wf_tag('tr', true);

        if (isset($cachedData[$buildId])) {
            $cachePrev = $cachedData[$buildId];
            $result['rows'] = isset($cachePrev['rows']) ? $cachePrev['rows'] : $headerRows;
            $result['userscount'] = isset($cachePrev['userscount']) ? $cachePrev['userscount'] : 0;
            $result['onlineusers'] = isset($cachePrev['onlineusers']) ? $cachePrev['onlineusers'] : 0;
            $result['activeusers'] = isset($cachePrev['activeusers']) ? $cachePrev['activeusers'] : 0;
        } else {
            $rows = $headerRows;
            if (!empty($aptData)) {
                foreach ($aptData as $ib => $eachapt) {
                    if ($eachapt['buildid'] == $buildId) {
                        if (isset($allUserIps[$eachapt['login']])) {
                            $result['userscount']++;
                            $userLogin = $eachapt['login'];
                            $userIp = $allUserIps[$eachapt['login']];
                            $onlineFlag = web_bool_star(false);
                            $onlineKey = 'd';
                            if (isset($dnUsers[$userLogin])) {
                                $onlineFlag = web_bool_star(true);
                                $onlineKey = 'l';
                                $result['onlineusers']++;
                            }

                            $activeFlag = web_bool_led(false);
                            $activeKey = '0';
                            if (isset($allUserData[$userLogin])) {
                                $activityState = zb_UserIsAlive($allUserData[$userLogin]);
                                switch ($activityState) {
                                    case -1:
                                        $activeFlag = web_yellow_led();
                                        $activeKey = '-1';
                                        break;
                                    case 1:
                                        $activeFlag = web_bool_led(true);
                                        $activeKey = '1';
                                        $result['activeusers']++;
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
            }
            $result['rows'] = $rows;
            $result['cachedData'][$buildId] = array(
                'rows' => $rows,
                'userscount' => $result['userscount'],
                'onlineusers' => $result['onlineusers'],
                'activeusers' => $result['activeusers']
            );
            $result['cacheUpdated'] = true;
        }

        return ($result);
    }
}