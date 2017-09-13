<?php

class WifiCPE {

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains all available APs as id=>APdata
     *
     * @var array
     */
    protected $allAP = array();

    /**
     * Contains all available devices models as modelid=>name
     *
     * @var array
     */
    protected $deviceModels = array();

    /**
     * Contains all available CPEs as id=>CPEdata
     *
     * @var array
     */
    protected $allCPE = array();

    /**
     * Messages helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    public function __construct() {
        $this->loadConfigs();
        $this->initMessages();
        $this->loadDeviceModels();
        $this->loadAps();
        $this->loadCPEs();
    }

    /**
     * Loads system alter config to protected property for further usage
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfigs() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Initalizes system message helper instance
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Loads all available AP devices from switches directory
     * 
     * @return void
     */
    protected function loadAps() {
        $query = "SELECT * from `switches` WHERE `desc` LIKE '%AP%';";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allAP[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads all available CPE to protected property
     * 
     * @return void
     */
    protected function loadCPEs() {
        $query = "SELECT * from `wcpedevices`;";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allCPE[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads available device models from database
     * 
     * @return void
     */
    protected function loadDeviceModels() {
        $this->deviceModels = zb_SwitchModelsGetAllTag();
    }

    /**
     * Creates new CPE in database
     * 
     * @param int $modelId
     * @param string $ip
     * @param string $mac
     * @param string $location
     * @param bool $bridgeMode
     * @param int $uplinkApId
     * @param string $geo
     * 
     * @return void/string on error
     */
    public function createCPE($modelId, $ip, $mac, $location, $bridgeMode = false, $uplinkApId, $geo) {
        $result = '';
        $modelId = vf($modelId, 3);
        $ipF = mysql_real_escape_string($ip);
        $mac = strtolower_utf8($mac);
        $macF = mysql_real_escape_string($mac);
        $loactionF = mysql_real_escape_string($location);
        $bridgeMode = ($bridgeMode) ? 1 : 0;
        $uplinkApId = vf($uplinkApId, 3);
        $geoF = mysql_real_escape_string($geo);

        if (isset($this->deviceModels[$modelId])) {
            if (check_mac_format($macF)) {
                $query = "INSERT INTO `wcpedevices` (`id`, `modelid`, `ip`, `mac`, `location`, `bridge`, `uplinkapid`, `uplinkcpeid`, `geo`) "
                        . "VALUES (NULL, '" . $modelId . "', '" . $ipF . "', '" . $macF . "', '" . $loactionF . "', '" . $bridgeMode . "', '" . $uplinkApId . "', NULL, '" . $geoF . "');";
                nr_query($query);
                $newId = simple_get_lastid('wcpedevices');
                log_register('WCPE CREATE [' . $newId . ']');
            } else {
                $result.=$this->messages->getStyledMessage(__('This MAC have wrong format'), 'error');
            }
        } else {
            $result.=$this->messages->getStyledMessage(__('Wrong element format') . ': modeleid', 'error');
        }
        return ($result);
    }

    /**
     * Renders CPE creation form
     * 
     * @return string
     */
    public function renderCpeCreateForm() {
        $result = '';
        if (!empty($this->deviceModels)) {
            $inputs = wf_HiddenInput('createnewcpe', 'true');
            $inputs.= wf_Selector('newcpemodelid', $this->deviceModels, __('Model'), '', true);
            $inputs.= wf_TextInput('newcpeip', __('IP'), '', true, 15);
            $inputs.= wf_TextInput('newcpemac', __('MAC'), '', true, 15);
            $inputs.= wf_Selector('newcpebridge', array('0' => __('No'), '1' => __('Yes')), __('Bridge mode'), '0', true);

            $result = wf_Form('', 'POST', $inputs, 'glamour');
        } else {
            $result = $this->messages->getStyledMessage(__('No') . ' ' . __('Equipment models'), 'error');
        }
        return ($result);
    }

}
?>