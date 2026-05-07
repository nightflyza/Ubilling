<?php

/**
 * Switches map service based on MapCore API.
 */
class SwitchMap {
    /**
     * Ubilling configuration provider.
     *
     * @var object
     */
    protected $ubillingConfig = null;

    /**
     * Canonical icon key for alive switches.
     *
     * @var string
     */
    protected $iconSwitchAlive = 'marker.blue';

    /**
     * Canonical icon key for dead switches.
     *
     * @var string
     */
    protected $iconSwitchDead = 'marker.red';

    /**
     * Canonical icon key for NP/unknown switches.
     *
     * @var string
     */
    protected $iconSwitchUnknown = 'marker.darkblue';

    /**
     * Switches database abstraction layer.
     *
     * @var object
     */
    protected $switchDb = '';

    /**
     * Active map core instance for current rendering context.
     *
     * @var object
     */
    protected $mapCore = '';

    // some predefined stuff
    const TABLE_SWITCHES = 'switches';

    /**
     * Creates SwitchMap service instance.
     *
     * @global object $ubillingConfig
     */
    public function __construct() {
        global $ubillingConfig;
        $this->ubillingConfig = $ubillingConfig;
        $this->initDb();
        $this->initMapCore();
    }

    /**
     * Inits switches database abstraction layer.
     *
     * @return void
     */
    protected function initDb() {
        $this->switchDb = new NyanORM(self::TABLE_SWITCHES);
    }

    /**
     * Inits single mapcore instance.
     *
     * @return void
     */
    protected function initMapCore() {
        $this->mapCore = new MapCore('switchmap');
    }

    /**
     * Returns all switches from database.
     *
     * @return array
     */
    protected function getAllSwitches() {
        $this->initDb();
        $result = $this->switchDb->getAll();
        return ($result);
    }

    /**
     * Returns switches with non-empty GEO coordinates.
     *
     * @return array
     */
    protected function getSwitchesWithGeo() {
        $this->initDb();
        $this->switchDb->where('geo', '!=', '');
        $result = $this->switchDb->getAll();
        return ($result);
    }

    /**
     * Returns switches without GEO and excluding NP devices.
     *
     * @return array
     */
    protected function getSwitchesWithoutGeo() {
        $this->initDb();
        $this->switchDb->where('geo', '=', '');
        $this->switchDb->whereRaw('`desc` NOT LIKE \'%NP%\'');
        $result = $this->switchDb->getAll();
        return ($result);
    }


    /**
     * Persists posted switch coordinates from editor form if received from editor form
     *
     * @return void
     */
    public function saveSwitchPlacement() {
        if (ubRouting::checkPost(array('switchplacing', 'placecoords'))) {
            if (cfr('SWITCHESEDIT')) {
                $switchid = ubRouting::post('switchplacing', 'int');
                $placegeo = ubRouting::post('placecoords', 'mres');
                simple_update_field('switches', 'geo', $placegeo, "WHERE `id`='" . $switchid . "'");
                log_register('SWITCH CHANGE [' . $switchid . ']' . ' GEO ' . $placegeo);
                ubRouting::nav('?module=switchmap&locfinder=true');
            } else {
                show_error(__('Access denied'));
            }
        }
    }

    /**
     * Detects whether brief minimap mode is enabled.
     *
     * @return bool
     */
    public static function isBriefMinimapEnabled() {
        global $ubillingConfig;
        $result = false;
        if (ubRouting::checkGet('briefminimap')) {
            $result = (ubRouting::get('briefminimap') == 'on') ? true : false;
        } else {
            $result = $ubillingConfig->getAlterParam('BRIEF_MINIMAP');
        }
        return ($result);
    }

    /**
     * Checks whether switch id belongs to traced uplink chain.
     *
     * @param array $alllinks
     * @param int $traceid
     * @param int $checkid
     *
     * @return bool
     */
    public static function isLinkedSwitch($alllinks, $traceid, $checkid) {
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
     * Returns chain of parent switches including current.
     *
     * @param array $alllinks
     * @param int $traceid
     *
     * @return array
     */
    public static function getSwitchParents($alllinks, $traceid) {
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
     * Validates that new parent does not create topology loop.
     *
     * @param array $alllinks
     * @param int $switchId
     * @param int $setParent
     *
     * @return bool
     */
    public static function isLoopAllowed($alllinks, $switchId, $setParent) {
        $result = false;
        if (!empty($switchId)) {
            if (self::isLinkedSwitch($alllinks, $setParent, $switchId)) {
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
     * Collects all related switch ids for brief minimap mode.
     *
     * @param int $switchId
     *
     * @return array
     */
    public function getLinkedSwitchIds($switchId) {
        $switchId = ubRouting::filters($switchId, 'int');
        $tmpSwitches = $this->getAllSwitches();
        $parentMap = array();
        $childrenMap = array();
        $result = array();
        $queue = array();

        if (!empty($switchId)) {
            if (!empty($tmpSwitches)) {
                foreach ($tmpSwitches as $io => $each) {
                    $currentId = (int) $each['id'];
                    $parentId = (int) $each['parentid'];
                    $parentMap[$currentId] = $parentId;
                    if (!isset($childrenMap[$currentId])) {
                        $childrenMap[$currentId] = array();
                    }
                    if (!empty($parentId)) {
                        if (!isset($childrenMap[$parentId])) {
                            $childrenMap[$parentId] = array();
                        }
                        $childrenMap[$parentId][$currentId] = $currentId;
                    }
                }
            }

            $result[$switchId] = $switchId;

            $currentParent = isset($parentMap[$switchId]) ? (int) $parentMap[$switchId] : 0;
            while (!empty($currentParent)) {
                if (isset($result[$currentParent])) {
                    break;
                }
                $result[$currentParent] = $currentParent;
                if (isset($parentMap[$currentParent])) {
                    $currentParent = (int) $parentMap[$currentParent];
                } else {
                    $currentParent = 0;
                }
            }

            $queue[] = $switchId;
            while (!empty($queue)) {
                $current = array_shift($queue);
                if (isset($childrenMap[$current])) {
                    foreach ($childrenMap[$current] as $childId) {
                        if (!isset($result[$childId])) {
                            $result[$childId] = $childId;
                            $queue[] = $childId;
                        }
                    }
                }
            }
        }

        return ($result);
    }

    /**
     * Builds uplink lines set for full map or trace mode.
     *
     * @param int|string $traceid
     *
     * @return void
     */
    public function drawSwitchUplinks($traceid = '') {
        $tmpSwitches = $this->getAllSwitches();
        $allswitches = array();
        $alllinks = array();
        $dead_raw = zb_StorageGet('SWDEAD');
        $deadarr = array();
        if ($dead_raw) {
            $deadarr = unserialize($dead_raw);
        }

        if (!empty($tmpSwitches)) {
            foreach ($tmpSwitches as $io => $each) {
                $allswitches[$each['id']] = $each;
            }
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
                        if (($allswitches[$each['parentid']]['geo'] != '') and ($each['geo'] != '')) {
                            $coord1 = $each['geo'];
                            $coord2 = $allswitches[$each['parentid']]['geo'];
                            $hint = $each['location'] . ' ' . $each['ip'] . ' → ' . $allswitches[$each['parentid']]['location'] . ' ' . $allswitches[$each['parentid']]['ip'];

                            if ((!isset($deadarr[$each['ip']])) and (!isset($deadarr[$allswitches[$each['parentid']]['ip']]))) {
                                $color = '#00FF00';
                            } else {
                                $color = '#FF0000';
                            }

                            if (!empty($traceid)) {
                                if ($each['id'] == $traceid) {
                                    $width = 5;
                                    $this->mapCore->addLine($coord1, $coord2, array('color' => $color, 'hint' => $hint, 'width' => $width));
                                } else {
                                    if (self::isLinkedSwitch($alllinks, $traceid, $each['id'])) {
                                        $width = 3;
                                        $this->mapCore->addLine($coord1, $coord2, array('color' => $color, 'hint' => $hint, 'width' => $width));
                                    }
                                }
                            } else {
                                $width = 1;
                                $this->mapCore->addLine($coord1, $coord2, array('color' => $color, 'hint' => $hint, 'width' => $width));
                            }
                        }
                    }
                }
            }
        }
        return;
    }

    /**
     * Builds markers for linked switches only.
     *
     * @param int $switchId
     *
     * @return void
     */
    public function drawLinkedSwitches($switchId) {
        $switchId = ubRouting::filters($switchId, 'int');
        $linkedSwitches = $this->getLinkedSwitchIds($switchId);
        $allswitches = $this->getSwitchesWithGeo();

        $uplinkTraceIcon = wf_img('skins/ymaps/uplinks.png', __('Show links'));
        $switchEditIcon = wf_img('skins/icon_edit.gif', __('Edit'));
        $switchPollerIcon = wf_img('skins/snmp.png', __('SNMP query'));
        $switchLocatorIcon = wf_img('skins/icon_search_small.gif', __('Zoom in'));

        $footerDelimiter = wf_tag('br');
        $dead_raw = zb_StorageGet('SWDEAD');
        $deadarr = array();
        if ($dead_raw) {
            $deadarr = unserialize($dead_raw);
        }

        if (!empty($allswitches)) {
            foreach ($allswitches as $io => $each) {
                if (isset($linkedSwitches[$each['id']])) {
                    $geo = ubRouting::filters($each['geo'], 'mres');
                    $title = ubRouting::filters($each['ip'], 'mres');
                    $content = ubRouting::filters($each['location'], 'mres');
                    if (empty($content)) {
                        $content = __('No location set');
                    }
                    $iconlabel = '';

                    if (!isset($deadarr[$each['ip']])) {
                        if (ispos($each['desc'], 'NP')) {
                            $footer = __('Switch') . ': ' . __('Status') . ' ' . __('Unknown');
                            $icon = $this->iconSwitchUnknown;
                        } else {
                            $footer = __('Switch alive');
                            $icon = $this->iconSwitchAlive;
                        }
                    } else {
                        $footer = __('Switch dead');
                        $icon = $this->iconSwitchDead;
                    }

                    if (!empty($each['location'])) {
                        $iconlabel = $each['location'];
                    } else {
                        $iconlabel = $each['ip'];
                    }

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

                    $markerOptions = array(
                        'icon' => $icon,
                        'tooltip' => $iconlabel,
                        'popupTitle' => $title,
                        'popupFooter' => $footer
                    );
                    $this->mapCore->addMarker($geo, $content, $markerOptions);
                }
            }
        }
        return;
    }

    /**
     * Builds links for linked switches only.
     *
     * @param int $switchId
     *
     * @return void
     */
    public function drawSwitchAllLinks($switchId) {
        $switchId = ubRouting::filters($switchId, 'int');
        $tmpSwitches = $this->getAllSwitches();
        $allswitches = array();
        $linkedSwitches = $this->getLinkedSwitchIds($switchId);

        $dead_raw = zb_StorageGet('SWDEAD');
        $deadarr = array();
        if ($dead_raw) {
            $deadarr = unserialize($dead_raw);
        }

        if (!empty($tmpSwitches)) {
            foreach ($tmpSwitches as $io => $each) {
                $allswitches[$each['id']] = $each;
            }
        }

        if (!empty($allswitches)) {
            foreach ($allswitches as $io => $each) {
                if (!empty($each['parentid'])) {
                    if (isset($allswitches[$each['parentid']])) {
                        if (($allswitches[$each['parentid']]['geo'] != '') and ($each['geo'] != '')) {
                            if (isset($linkedSwitches[$each['id']]) and isset($linkedSwitches[$each['parentid']])) {
                                $coord1 = $each['geo'];
                                $coord2 = $allswitches[$each['parentid']]['geo'];
                                $hint = $each['location'] . ' ' . $each['ip'] . ' → ' . $allswitches[$each['parentid']]['location'] . ' ' . $allswitches[$each['parentid']]['ip'];

                                if ((!isset($deadarr[$each['ip']])) and (!isset($deadarr[$allswitches[$each['parentid']]['ip']]))) {
                                    $color = '#00FF00';
                                } else {
                                    $color = '#FF0000';
                                }

                                if (($each['id'] == $switchId) or ($each['parentid'] == $switchId)) {
                                    $width = 5;
                                } else {
                                    $width = 3;
                                }
                                $this->mapCore->addLine($coord1, $coord2, array('color' => $color, 'hint' => $hint, 'width' => $width));
                            }
                        }
                    }
                }
            }
        }
        return;
    }

    /**
     * Builds all switch markers for switch map.
     *
     * @return void
     */
    public function drawSwitches() {
        $allswitches = $this->getSwitchesWithGeo();

        $uplinkTraceIcon = wf_img('skins/ymaps/uplinks.png', __('Show links'));
        $switchEditIcon = wf_img('skins/icon_edit.gif', __('Edit'));
        $switchPollerIcon = wf_img('skins/snmp.png', __('SNMP query'));
        $switchLocatorIcon = wf_img('skins/icon_search_small.gif', __('Zoom in'));

        $footerDelimiter = wf_tag('br');
        $dead_raw = zb_StorageGet('SWDEAD');
        $deadarr = array();
        if ($dead_raw) {
            $deadarr = unserialize($dead_raw);
        }

        if (!empty($allswitches)) {
            foreach ($allswitches as $io => $each) {
                $geo = ubRouting::filters($each['geo'], 'mres');
                $title = ubRouting::filters($each['ip'], 'mres');
                $content = ubRouting::filters($each['location'], 'mres');
                if (empty($content)) {
                    $content = __('No location set');
                }
                $iconlabel = '';

                if (!isset($deadarr[$each['ip']])) {
                    if (ispos($each['desc'], 'NP')) {
                        $footer = __('Switch') . ': ' . __('Status') . ' ' . __('Unknown');
                        $icon = $this->iconSwitchUnknown;
                    } else {
                        $footer = __('Switch alive');
                        $icon = $this->iconSwitchAlive;
                    }
                } else {
                    $footer = __('Switch dead');
                    $icon = $this->iconSwitchDead;
                }

                if (!empty($each['location'])) {
                    $iconlabel = $each['location'];
                } else {
                    $iconlabel = $each['ip'];
                }

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

                $markerOptions = array(
                    'icon' => $icon,
                    'tooltip' => $iconlabel,
                    'popupTitle' => $title,
                    'popupFooter' => $footer
                );
                $this->mapCore->addMarker($geo, $content, $markerOptions);
            }
        }
        return;
    }

    /**
     * Returns switch layer as MapCore-compatible map objects payload.
     *
     * @return array
     */
    public function getSwitchesMapObjects() {
        $this->drawSwitches();
        $result = $this->mapCore->getMapObjects();
        return ($result);
    }

    /**
     * Returns switch layer placemarks JS buffer only.
     *
     * @return string
     */
    public function getSwitchesPlacemarks() {
        $this->drawSwitches();
        $result = $this->mapCore->getPlacemarks();
        return ($result);
    }

    /**
     * Renders selector form for placing switches.
     *
     * @return string
     */
    public function getLocationSwitchForm() {
        $allNoGeoSwitches = $this->getSwitchesWithoutGeo();
        $switchData = array();
        $result = '';

        if (!empty($allNoGeoSwitches)) {
            foreach ($allNoGeoSwitches as $io => $each) {
                $cleanLocation = str_replace("'", '`', $each['location']);
                $switchData[$each['id']] = $each['ip'] . ' - ' . $cleanLocation;
            }
            if (cfr('SWITCHESEDIT')) {
                if (ubRouting::checkGet('placesw')) {
                    $selected = ubRouting::get('placesw');
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
     * Builds click-to-place editor code for map.
     *
     * @return string
     */
    public function getLocationFinder() {
        $title = wf_tag('b') . __('Place coordinates') . wf_tag('b', true);
        $data = $this->getLocationSwitchForm();
        $this->mapCore->addLocationEditor('placecoords', $title, $data);
        $result = '';
        return ($result);
    }

    /**
     * Renders map controls and map container block.
     *
     * @return void
     */
    public function renderMapContainer() {
        $container = wf_tag('div', false, '', 'id="switchmap" style="width: 1000; height:800px;"');
        $container .= wf_tag('div', true);
        $controls = '';
        if (cfr('SWITCHMAP')) {
            $controls .= wf_Link("?module=switchmap", wf_img('skins/ymaps/network.png') . ' ' . __('Switches map'), false, 'ubButton');
        }
        if (cfr('USERSMAP')) {
            $controls .= wf_Link("?module=usersmap", wf_img('skins/ymaps/build.png') . ' ' . __('Builds map'), false, 'ubButton');
        }
        if (cfr('SWITCHESEDIT')) {
            $controls .= wf_Link("?module=switchmap&locfinder=true", wf_img('skins/ymaps/edit.png') . ' ' . __('Edit map'), false, 'ubButton');
        }
        $controls .= wf_Link("?module=switchmap&showuplinks=true", wf_img('skins/ymaps/uplinks.png') . ' ' . __('Show links'), false, 'ubButton');
        if (cfr('SWITCHES')) {
            $controls .= wf_Link("?module=switches", wf_img('skins/ymaps/switchdir.png') . ' ' . __('Available switches'), false, 'ubButton');
        }
        $controls .= wf_delimiter(1);
        show_window(__('Active equipment map'), $controls . $container);
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
        if (ubRouting::checkGet('finddevice')) {
            $ymZoom = $ymconf['FINDING_ZOOM'];
            $ymCenter = ubRouting::get('finddevice', 'vf');
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

        $this->drawSwitches();

        if (ubRouting::checkGet('showuplinks')) {
            $traceLinks = '';
            if (ubRouting::checkGet('traceid')) {
                $traceLinks = ubRouting::get('traceid', 'int');
            }
            $this->drawSwitchUplinks($traceLinks);
        }

        if (ubRouting::checkGet('locfinder')) {
            $this->getLocationFinder();
        }

        return ($this->mapCore);
    }

    /**
     * Renders full switch map page output.
     *
     * @return void
     */
    public function render() {
        $this->renderMapContainer();
        $mapCore = $this->getMapCore();
        show_window('', $mapCore->render());
    }

    /**
     * Renders compact switch mini-map for switch profile page.
     *
     * @param array $switchdata
     *
     * @return string
     */
    public function renderMiniMap($switchdata) {
        $ymconf = $this->ubillingConfig->getYmaps();
        $briefMinimap = self::isBriefMinimapEnabled();
        $result = '';
        $radius = 30;
        $this->mapCore->setCenter($switchdata['geo']);
        $this->mapCore->setZoom($ymconf['FINDING_ZOOM']);
        $this->mapCore->setType($ymconf['TYPE']);
        $this->mapCore->addCircle($switchdata['geo'], $radius, __('Search area radius') . ' ' . $radius . ' ' . __('meters'), array('hint' => __('Search area')));
        $result .= wf_tag('div', false, '', 'id="switchmap" class="glamour" style="width: 97%; height:300px;"') . wf_tag('div', true);
        $result .= wf_delimiter();
        if ($briefMinimap) {
            $this->drawLinkedSwitches($switchdata['id']);
            $this->drawSwitchAllLinks($switchdata['id']);
        } else {
            $this->drawSwitches();
            $this->drawSwitchUplinks($switchdata['id']);
        }
        $result .= $this->mapCore->render();
        $result .= wf_tag('div', false, '', 'style="clear:both;"') . wf_tag('div', true);
        return ($result);
    }

}