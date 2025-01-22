<?php

/**
 * Just runs some PON OLT custom scripts
 */
class PONScripts {

    /**
     * Contains available scripts as scriptId=>[path,vendor,type,class,icon,name]
     *
     * @var array
     */
    protected $allScripts = array();
    /**
     * Contains available script types as substring=>typeName
     *
     * @var array
     */
    protected $typesAvail = array();

    /**
     * Contains available script/interface classes as substring=>className
     *
     * @var array
     */
    protected $classesAvail = array();

    /**
     * Contains available script applicable vendors as substring=>vendorName
     *
     * @var array
     */
    protected $vendorsAvail = array();

    /**
     * Contains available scripts icons as substring=>iconName
     *
     * @var array
     */
    protected $scriptIcons = array();

    /**
     * Contains available scripts names as substring=>scriptName
     *
     * @var array
     */
    protected $scriptNames = array();

    /**
     * Contains all OLT devices as id=>IP
     *
     * @var array
     */
    protected $allOltIps = array();

    /**
     * Contains all OLT dev modelIds as oltId=>modelId
     *
     * @var array
     */
    protected $allOltModelIds = array();

    /**
     * Contains all devive models data as modelId=>modelData
     *
     * @var array
     */
    protected $allOltModelsData = array();

    /**
     * Contains all switchAuth devices auth data
     *
     * @var array
     */
    protected $allAuthData = array();

    /**
     * Devices auth data object instance
     *
     * @var object
     */
    protected $swAuth = '';

    /**
     * Contains full expect path
     *
     * @var string
     */
    protected $expectPath = '';

    /**
     * System message helper placeholder
     *
     * @var object
     */
    protected $messages = '';

    //some predefined stuff
    const PATH_SCRIPTS = 'config/scripts/ponscripts/';
    const PATH_CUSTOM = 'content/documents/myscripts/';
    const ICONS_PATH = 'skins/';
    const URL_ME = '?module=ponscripts';
    const ROUTE_RUN_IFSCRIPT = 'runifscript';
    const ROUTE_RUN_OLTID = 'oltid';
    const ROUTE_RUN_IFNAME = 'iface';
    const RUN_PID = 'PONSCRIPT_';

    /**
     * Creates new PON scripts instance
     *
     * @param array $allOltIps
     * @param array $allOltModelIds
     * @param array $allOltModelsData
     */
    public function __construct($allOltIps, $allOltModelIds, $allOltModelsData) {
        $this->initMessages();
        $this->setOptions();
        $this->loadScripts();
        $this->initSwitchAuth();
        $this->loadSwitchAuthData();
        $this->setOltIps($allOltIps);
        $this->setOltModelIds($allOltModelIds);
        $this->setOltModelsData($allOltModelsData);
    }


    /**
     * Sets some object defaults
     *
     * @return void
     */
    protected function setOptions() {
        global $ubillingConfig;
        $billCfg = $ubillingConfig->getBilling();
        $this->expectPath = $billCfg['EXPECT_PATH'];

        $this->typesAvail = array(
            '_if_' => 'iface',
            '_onu_' => 'onu',
            '_olt_' => 'olt',
            '_mac_' => 'mac',
        );

        $this->classesAvail = array(
            '_epon_' => 'epon',
            '_gpon_' => 'gpon',
        );

        $this->vendorsAvail = array(
            'bdcom_' => 'BDCOM',
            'zte_' => 'ZTE',
            'stels_' => 'STELS',
            'gcom_' => 'GCOM',
            'huawei_' => 'Huawei',
            'vsol_' => 'V_SOL',
        );

        $this->scriptIcons = array(
            '_activeonu' => 'icon_active.gif',
            '_inactiveonu' => 'icon_inactive.gif',
            '_tflush' => 'icon_cleanup.png',
            '_optdiag' => 'pon_icon.gif',
        );

        $this->scriptNames = array(
            '_activeonu' => __('Active') . ' ' . __('ONU'),
            '_inactiveonu' => __('Inactive') . ' ' . __('ONU'),
            '_tflush' => __('Clear') . ' ' . __('Interface'),
            '_optdiag' => __('Optical diagnostics'),
        );
    }


    /**
     * Initializes the messages property with an instance of UbillingMessageHelper.
     *
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Inits switchesAuth instance into protected prop
     *
     * @return void
     */
    protected function initSwitchAuth() {
        $this->swAuth = new SwitchAuth();
    }

    /**
     * Loads devices auth data for further usage
     *
     * @return void
     */
    protected function loadSwitchAuthData() {
        $this->allAuthData = $this->swAuth->getAllAuthData();
    }

    /**
     * Sets OLTs IPs property
     *
     * @param array $allOltIps
     * 
     * @return void
     */
    protected function setOltIps($allOltIps) {
        $this->allOltIps = $allOltIps;
    }

    /**
     * Sets OLTs modelIds property
     *
     * @param array $allOltModelIds
     * 
     * @return void
     */
    protected function setOltModelIds($allOltModelIds) {
        $this->allOltModelIds = $allOltModelIds;
    }

    /**
     * Sets all dev models data property
     *
     * @param array $allOltModelsData
     * 
     * @return void
     */
    protected function setOltModelsData($allOltModelsData) {
        $this->allOltModelsData = $allOltModelsData;
    }

    protected function getScriptParam($paramArr, $scriptName, $default) {
        $result = $default;
        if (!empty($paramArr)) {
            foreach ($paramArr as $paramKey => $paramVal) {
                if (ispos($scriptName, $paramKey)) {
                    $result = $paramVal;
                }
            }
        }
        return ($result);
    }

    /**
     * Loads and preprocess available PON-scripts from fs
     *
     * @return void
     */
    protected function loadScripts() {
        $allScripts = rcms_scandir(self::PATH_SCRIPTS);
        if (!empty($allScripts)) {
            foreach ($allScripts as $io => $each) {
                $scriptType = $this->getScriptParam($this->typesAvail, $each, 'unknown');
                $scriptClass = $this->getScriptParam($this->classesAvail, $each, 'unknown');
                $scriptVendor = $this->getScriptParam($this->vendorsAvail, $each, 'unknown');
                $scriptIcon = $this->getScriptParam($this->scriptIcons, $each, 'script16.png');
                $scriptLabel = $this->getScriptParam($this->scriptNames, $each, $each);

                $eachScriptPath = self::PATH_SCRIPTS . $each;
                if (file_exists(self::PATH_CUSTOM . $each)) {
                    $eachScriptPath = self::PATH_CUSTOM . $each;
                }

                $this->allScripts[$each]['path'] = $eachScriptPath;
                $this->allScripts[$each]['vendor'] = $scriptVendor;
                $this->allScripts[$each]['type'] = $scriptType;
                $this->allScripts[$each]['class'] = $scriptClass;
                $this->allScripts[$each]['icon'] = $scriptIcon;
                $this->allScripts[$each]['name'] = $scriptLabel;
            }
        }
    }

    /**
     * Returns vendor string of some OLT
     *
     * @param int $oltId
     * 
     * @return string|void
     */
    protected function getOltVendor($oltId) {
        $result = '';
        if (isset($this->allOltModelIds[$oltId])) {
            $oltModelId = $this->allOltModelIds[$oltId];
            if (isset($this->allOltModelsData[$oltModelId])) {
                $oltModelData = $this->allOltModelsData[$oltModelId];
                if (!empty($oltModelData['snmptemplate'])) {
                    $oltSnmpTemplateName = $oltModelData['snmptemplate'];
                    foreach ($this->vendorsAvail as $vendorId => $vendorName) {
                        if (ispos($oltSnmpTemplateName, $vendorName)) {
                            $result = $vendorName;
                        }
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Returns interface class by interface name
     *
     * @param string $ifaceName
     * 
     * @return string|void
     */
    protected function getIfaceClass($ifaceName) {
        $result = '';
        $lowerIf = strtolower($ifaceName);
        foreach ($this->classesAvail as $classId => $className) {
            if (ispos($lowerIf, $className)) {
                $result = $className;
            }
        }
        return ($result);
    }

    /**
     * Checks is OLT authorization data avail or not?
     *
     * @param int $oltId
     * 
     * @return bool
     */
    protected function isAuthDataOk($oltId) {
        $result = false;
        if (isset($this->allAuthData[$oltId])) {
            if ($this->allAuthData[$oltId]['login'] and $this->allAuthData[$oltId]['password']) {
                $result = true;
            }
        }
        return ($result);
    }

    /**
     * Renders some interface script controls for specified OLT 
     *
     * @param int $oltId
     * @param string $interface
     * 
     * @return string
     */
    public function renderIfaceControls($oltId, $interface) {
        $result = '';
        if (!empty($this->allScripts)) {
            $oltVendor = $this->getOltVendor($oltId);
            $ifClass = $this->getIfaceClass($interface);
            $authDataOk = $this->isAuthDataOk($oltId);
            foreach ($this->allScripts as $scriptId => $scriptData) {
                if ($scriptData['type'] == 'iface') {
                    //is this PON OLT?
                    if (isset($this->allOltIps[$oltId])) {
                        //any auth data available?
                        if ($authDataOk) {
                            if ($scriptData['vendor'] == $oltVendor and $scriptData['class'] == $ifClass) {
                                $actionUrl = self::URL_ME . '&' . self::ROUTE_RUN_IFSCRIPT . '=' . $scriptId . '&' . self::ROUTE_RUN_OLTID . '=' . $oltId . '&' . self::ROUTE_RUN_IFNAME . '=' . $interface;
                                $cancelUrl = PONizer::URL_ME . '&oltstats=true';
                                $scriptLabel = wf_img_sized(self::ICONS_PATH . $scriptData['icon'], $scriptData['name'], '12', '12');
                                $scriptText = __('Run script') . ' «' . $scriptData['name'] . '» ' . __('for') . ' ' . $oltVendor . ' ' . $this->allOltIps[$oltId] . ' ' . $interface . '?';
                                $result .= wf_ConfirmDialog($actionUrl, $scriptLabel, $scriptText, '', $cancelUrl, $this->messages->getEditAlert());
                            }
                        }
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Executes some script using expect
     *
     * @param string $scriptPath
     * @param string $parameters
     * 
     * @return string
     */
    protected function executeScript($scriptPath, $parameters) {
        $result = '';
        if (!empty($this->expectPath)) {
            if (file_exists($scriptPath)) {
                $command = $this->expectPath . ' ' . $scriptPath . ' ' . $parameters;
                $result .= shell_exec($command);
            } else {
                log_register('PONSCRIPT FAIL `' . $scriptPath . '` NOT EXISTS');
            }
        }
        return ($result);
    }

    /**
     * Runs a specified OLT interface script.
     *
     * @param int $scriptId The ID of the script to be executed.
     * @param int $oltId The ID of the OLT
     * @param string $ifaceName The name of the interface.
     * 
     * @return string 
     */

    public function runIfaceScript($scriptId, $oltId, $ifaceName) {
        $result = '';
        if (isset($this->allScripts[$scriptId])) {
            if (isset($this->allOltIps[$oltId])) {
                $oltIp = $this->allOltIps[$oltId];
                $scriptData = $this->allScripts[$scriptId];
                $oltVendor = $this->getOltVendor($oltId);
                $ifClass = $this->getIfaceClass($ifaceName);
                $authDataOk = $this->isAuthDataOk($oltId);
                if ($authDataOk) {
                    if ($scriptData['class'] == $ifClass) {
                        if ($scriptData['vendor'] == $oltVendor) {
                            $process = new StarDust(self::RUN_PID . $oltId);
                            if ($process->notRunning()) {
                                $process->start();
                                $oltLogin = $this->allAuthData[$oltId]['login'];
                                $oltPassword = $this->allAuthData[$oltId]['password'];
                                $oltEnable = $this->allAuthData[$oltId]['enable'];
                                $scriptParams = $oltIp . ' ' . $oltLogin . ' ' . $oltPassword . ' ' . $oltEnable . ' ' . $ifaceName;
                                $executionResultRaw = $this->executeScript($scriptData['path'], $scriptParams);
                                if (!empty($executionResultRaw)) {
                                    $inputs = wf_tag('textarea', false, 'fileeditorarea', 'name="ponscriptsresult" cols="145" rows="30" spellcheck="false"');
                                    $inputs .= print_r($executionResultRaw, true);
                                    $inputs .= wf_tag('textarea', true);
                                    $result .= $inputs;
                                } else {
                                    $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('Result') . ' ' . __('is empty'), 'error');
                                }
                                log_register('PONSCRIPT RUN `' . $scriptId . '` OLT [' . $oltId . '] IFACE `' . $ifaceName . '`');
                                $process->stop();
                            } else {
                                $result .= $this->messages->getStyledMessage(__('Script') . ' ' . __('for') . ' ' . __('OLT') . ' [' . $oltId . '] ' . __('Already running'), 'error');
                                log_register('PONSCRIPT FAIL `' . $scriptId . '` OLT [' . $oltId . '] IFACE `' . $ifaceName . '` ALREADY RUNNING');
                            }
                        } else {
                            $result .= $this->messages->getStyledMessage(__('Wrong OLT vendor') . ': ' . $oltVendor . ', ' . $scriptData['vendor'] . ' ' . __('expected'), 'error');
                        }
                    } else {
                        $result .= $this->messages->getStyledMessage(__('Wrong interface class') . ': ' . $ifaceName, 'error');
                    }
                } else {
                    $result .= $this->messages->getStyledMessage(__('Device authorization data') . ' ' . __('is empty'), 'error');
                }
            } else {
                $result .= $this->messages->getStyledMessage(__('OLT') . ' ' . $oltId . ' ' . __('not exists'), 'error');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Script') . ' ' . $scriptId . ' ' . __('not exists'), 'error');
        }
        $result .= wf_delimiter();
        $result .= wf_BackLink(PONizer::URL_ME . '&oltstats=true');
        return ($result);
    }
}
