<?php

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
     * Creates new ONU MAP instance
     * 
     * @return void
     */
    public function __construct() {
        $this->loadConfigs();
        $this->initMessages();
        $this->initPonizer();
        $this->loadUsers();
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
        $this->ponizer = new PONizer();
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
        if ($onuSignal == 'NO') {
            $result = 'twirl#greyIcon';
        }
        return($result);
    }

    /**
     * Renders ONU signals Map 
     * 
     * @return string
     */
    public function renderOnu() {
        $result = '';
        $allOnu = $this->ponizer->getAllOnu();
        $allOnuSignals = $this->ponizer->getAllONUSignals();
        $placemarks = '';
        $marksRendered = 0;
        $marksNoGeo = 0;
        $marksNoUser = 0;
        $result .= wf_BackLink('?module=ponizer') . wf_delimiter();

        $result .= generic_MapContainer('', '', 'ponmap');
        if (!empty($allOnu)) {
            foreach ($allOnu as $io => $eachOnu) {
                if (!empty($eachOnu['login'])) {
                    if (isset($this->allUserData[$eachOnu['login']])) {
                        $userData = $this->allUserData[$eachOnu['login']];
                        if (!empty($userData['geo'])) {
                            $onuSignal = (isset($allOnuSignals[$eachOnu['login']])) ? $allOnuSignals[$eachOnu['login']] : 'NO';
                            $onuIcon = $this->getIcon($onuSignal);
                            $signalLabel = ($onuSignal != 'NO') ? $onuSignal : __('No signal');
                            $placemarks .= generic_mapAddMark($userData['geo'], 'title', $signalLabel, 'footer', $onuIcon, '', true);
                            $marksRendered++;
                        } else {
                            $marksNoGeo++;
                        }
                    } else {
                        $marksNoUser++;
                    }
                } else {
                    $marksNoUser++;
                }
            }
        }

        $result .= generic_MapInit($this->mapsCfg['CENTER'], $this->mapsCfg['ZOOM'], $this->mapsCfg['TYPE'], $placemarks, '', $this->mapsCfg['LANG'], 'ponmap');
        $result .= $this->messages->getStyledMessage(__('Total') . ' ' . __('ONU') . ': ' . sizeof($allOnu), 'info');
        $result .= $this->messages->getStyledMessage(__('ONU rendered on map') . ': ' . $marksRendered, 'success');
        $result .= $this->messages->getStyledMessage(__('User builds not placed on map') . ': ' . $marksNoGeo, 'warning');
        $result .= $this->messages->getStyledMessage(__('ONU without assigned user') . ': ' . $marksNoUser, 'warning');
        

        return($result);
    }

}
