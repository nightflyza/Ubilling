<?php

/**
 * Performs rendering of exiting user-assigned PON ONU devices on coverage map
 */
class PONONUMAP {

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
     * Predefined routes, URLs etc.
     */
    const URL_ME = '?module=ponmap';
    const ROUTE_FILTER_OLT = 'oltidfilter';
    const PROUTE_OLTSELECTOR = 'renderoltidonus';

    /**
     * Creates new ONU MAP instance
     * 
     * @return void
     */
    public function __construct($oltId = '') {
        $this->loadConfigs();
        $this->setOltIdFilter($oltId);
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
                            $onuSignal = (isset($allOnuSignals[$eachOnu['login']])) ? $allOnuSignals[$eachOnu['login']] : 'NO';
                            $onuIcon = $this->getIcon($onuSignal);
                            $onuControls = $this->getONUControls($eachOnu['id'], $eachOnu['login'], $userData['geo']);
                            $onuTitle = $userData['fulladress'];
                            $signalLabel = ($onuSignal == 'NO' OR $onuSignal == 'Offline' OR $onuSignal == '-9000') ? __('No signal') : $onuSignal;
                            $placemarks .= generic_mapAddMark($userData['geo'], $onuTitle, $signalLabel, $onuControls, $onuIcon, '', true);
                            $marksRendered++;
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
        return($result);
    }

}
