<?php

/**
 * Performs rendering of exiting user-assigned PON ONU devices on coverage map
 */
class PONONUMap {

    /**
     * Contains ymaps config as key=>value
     *
     * @var array
     */
    protected $mapsCfg = array();

    /**
     * Contains all available users data as login=>userdata
     *
     * @var array
     */
    protected $allUserData = array();

    /**
     * PONizer object placeholder
     *
     * @var object
     */
    protected $ponizer = '';

    /**
     * System message helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Contains optional OLT filter
     *
     * @var int
     */
    protected $filterOltId = '';

    /**
     * Contains optional offline onu dereg reason filter substring.
     * Only offline ONUs will be rendered if set.
     *
     * @var string
     */
    protected $onuDeregFilter = '';

    /**
     * Predefined routes, URLs etc.
     */
    const URL_ME = '?module=ponmap';
    const ROUTE_FILTER_OLT = 'oltidfilter';
    const ROUTE_FILTER_DEREG = 'deregfilter';
    const PROUTE_OLTSELECTOR = 'renderoltidonus';

    /**
     * Creates new ONU MAP instance
     * 
     * @return void
     */
    public function __construct($oltId = '') {
        $this->loadConfigs();
        $this->setOltIdFilter($oltId);
        $this->setOnuDeregFilter();
        $this->initMessages();
        $this->initPonizer();
        $this->loadUsers();
    }

    /**
     * Sets current instance OLT filter
     * 
     * @param int $oltId
     * 
     * @return void
     */
    protected function setOltIdFilter($oltId = '') {
        if (!empty($oltId)) {
            $this->filterOltId = $oltId;
        }
    }

    /**
     * Sets optional ONU dereg reason filter.
     * 
     * @return void
     */
    protected function setOnuDeregFilter() {
        if (ubRouting::checkGet(self::ROUTE_FILTER_DEREG)) {
            $this->onuDeregFilter = ubRouting::get(self::ROUTE_FILTER_DEREG);
        }
    }

    /**
     * Loads required config files into protected properties
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfigs() {
        global $ubillingConfig;
        $this->mapsCfg = $ubillingConfig->getYmaps();
    }

    /**
     * Inits PONizer object instance
     * 
     * @return void
     */
    protected function initPonizer() {
        $this->ponizer = new PONizer($this->filterOltId);
    }

    /**
     * Inits message helper object instance
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Preloads available users data for further usage
     * 
     * @return void
     */
    protected function loadUsers() {
        $this->allUserData = zb_UserGetAllDataCache();
    }

    /**
     * Returns MAP icon type due signal level
     * 
     * @param string $onuSignal
     * 
     * @return string
     */
    protected function getIcon($onuSignal) {
        $result = 'twirl#greenIcon';
        if ((($onuSignal > -27) AND ( $onuSignal < -25))) {
            $result = 'twirl#orangeIcon';
        }
        if ((($onuSignal > 0) OR ( $onuSignal < -27))) {
            $result = 'twirl#redIcon';
        }
        if ($onuSignal == 'NO' OR $onuSignal == 'Offline' OR $onuSignal == '-9000') {
            $result = 'twirl#greyIcon';
        }
        return($result);
    }

    /**
     * Returns ONU controls
     * 
     * @param int $onuId
     * @param string $login
     * @param string $buildGeo
     * 
     * @return string
     */
    protected function getONUControls($onuId, $login, $buildGeo) {
        $result = '';
        if (!empty($onuId)) {
            $result .= wf_Link(PONizer::URL_ME . '&editonu=' . $onuId, wf_img('skins/switch_models.png', __('Edit') . ' ' . __('ONU')));
            $result = trim($result) . wf_nbsp();
            $result .= wf_Link('?module=userprofile&username=' . $login, wf_img('skins/icons/userprofile.png', __('User profile')));
            $result = trim($result) . wf_nbsp();
            $result .= wf_Link('?module=usersmap&findbuild=' . $buildGeo, wf_img('skins/icon_build.gif', __('Build')));
            $result = trim($result) . wf_nbsp();
        }
        return($result);
    }

    /**
     * Renders module controls
     * 
     * @return string
     */
    protected function renderControls() {
        $result = '';
        $result .= wf_BackLink(PONizer::URL_ONULIST) . ' ';
        if ($this->filterOltId) {
            $result .= wf_Link(self::URL_ME, wf_img('skins/ponmap_icon.png') . ' ' . __('All') . ' ' . __('OLT'), false, 'ubButton');
        } else {
            $result .= wf_Link(self::URL_ME, wf_img('skins/ponmap_icon.png') . ' ' . __('All') . ' ' . __('ONU'), false, 'ubButton');
            $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_FILTER_DEREG . '=Power', wf_img('skins/icon_poweroutage.png') . ' ' . __('Power outages') . '?', false, 'ubButton');
            $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_FILTER_DEREG . '=Wire', wf_img('skins/icon_cable.png') . ' ' . __('Wire issues') . '?', false, 'ubButton');
        }

        $allOlts = array('' => __('All') . ' ' . __('OLT'));
        $allOlts += $this->ponizer->getAllOltDevices();
        $inputs = wf_SelectorAC(self::PROUTE_OLTSELECTOR, $allOlts, __('OLT'), $this->filterOltId, false);
        $opts = 'style="float:right;"';
        $result .= wf_Form('', 'POST', $inputs, 'glamour', '', '', '', $opts);

        $result .= wf_delimiter(0);
        return($result);
    }

    /**
     * Renders ONU signals Map 
     * 
     * @return string
     */
    public function renderOnuMap() {
        $result = '';
        $allOnu = $this->ponizer->getAllOnu();
        $allOnuSignals = $this->ponizer->getAllONUSignals();
        $allDeregReasons = $this->ponizer->getAllONUDeregReasons();
        $placemarks = '';
        $marksRendered = 0;
        $marksNoGeo = 0;
        $marksNoUser = 0;
        $marksDeadUser = 0;
        $totalOnuCount = 0;
        $result .= $this->renderControls();

        $result .= generic_MapContainer('', '', 'ponmap');
        if (!empty($allOnu)) {
            foreach ($allOnu as $io => $eachOnu) {
                if (!empty($eachOnu['login'])) {
                    if (isset($this->allUserData[$eachOnu['login']])) {
                        $userData = $this->allUserData[$eachOnu['login']];
                        if (!empty($userData['geo'])) {
                            if ($this->onuDeregFilter) {
                                $renderAllowedFlag = false;
                            } else {
                                $renderAllowedFlag = true;
                            }

                            $onuSignal = (isset($allOnuSignals[$eachOnu['login']])) ? $allOnuSignals[$eachOnu['login']] : 'NO';
                            $onuIcon = $this->getIcon($onuSignal);
                            $onuControls = $this->getONUControls($eachOnu['id'], $eachOnu['login'], $userData['geo']);
                            $onuTitle = $userData['fulladress'];
                            $deregState = '';
                            if ($onuSignal == 'NO' OR $onuSignal == 'Offline' OR $onuSignal == '-9000') {
                                $signalLabel = __('No signal');
                                if (isset($allDeregReasons[$eachOnu['login']])) {
                                    $deregLabel = $allDeregReasons[$eachOnu['login']]['styled'];
                                    $deregState = $allDeregReasons[$eachOnu['login']]['raw'];
                                    $signalLabel .= ' - ' . $deregLabel;
                                    if ($this->onuDeregFilter) {
                                        if (ispos($deregState, $this->onuDeregFilter)) {
                                            $renderAllowedFlag = true;
                                        }
                                    }
                                }
                            } else {
                                $signalLabel = $onuSignal;
                            }

                            if ($renderAllowedFlag) {
                                $placemarks .= generic_mapAddMark($userData['geo'], $onuTitle, $signalLabel, $onuControls, $onuIcon, '', true);
                                $marksRendered++;
                            }
                        } else {
                            $marksNoGeo++;
                        }
                    } else {
                        if ($eachOnu['login'] != 'dead') {
                            $marksNoUser++;
                        } else {
                            $marksDeadUser++; //TODO: may be output that somewhere in future.
                        }
                    }
                } else {
                    $marksNoUser++;
                }
                $totalOnuCount++;
            }
        }

        $result .= generic_MapInit($this->mapsCfg['CENTER'], $this->mapsCfg['ZOOM'], $this->mapsCfg['TYPE'], $placemarks, '', $this->mapsCfg['LANG'], 'ponmap');
        $result .= $this->messages->getStyledMessage(__('Total') . ' ' . __('ONU') . ': ' . $totalOnuCount, 'info');
        $result .= $this->messages->getStyledMessage(__('ONU rendered on map') . ': ' . $marksRendered, 'success');
        if ($marksNoGeo > 0) {
            $result .= $this->messages->getStyledMessage(__('User builds not placed on map') . ': ' . $marksNoGeo, 'warning');
        }

        if ($marksNoUser > 0) {
            $result .= $this->messages->getStyledMessage(__('ONU without assigned user') . ': ' . $marksNoUser, 'warning');
        }

        return($result);
    }

    /**
     * Returns label if rendering ONUs for only some specified OLT
     * 
     * @return string
     */
    public function getFilteredOLTLabel() {
        $result = '';
        if ($this->filterOltId) {
            $allOltDevices = $this->ponizer->getAllOltDevices();
            if (isset($allOltDevices[$this->filterOltId])) {
                $result .= ': ' . $allOltDevices[$this->filterOltId];
            }
        }

        if ($this->onuDeregFilter) {
            $onuFilterLabel = '';
            switch ($this->onuDeregFilter) {
                case 'Power':
                    $onuFilterLabel .= __('Power outages') . '?';
                    break;
                case 'Wire':
                    $onuFilterLabel .= __('Wire issues') . '?';
                    break;
            }
            $result .= ' : ' . $onuFilterLabel;
        }
        return($result);
    }

}
