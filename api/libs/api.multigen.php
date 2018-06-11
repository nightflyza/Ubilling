<?php

class MultiGen {

    /**
     * Contains system alter.ini config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains all stargazer user data
     *
     * @var array
     */
    protected $allUserData = array();

    /**
     * Contains existing users NAS bindings
     *
     * @var array
     */
    protected $userNases = array();

    /**
     * Contains available networks as id=>data
     *
     * @var array
     */
    protected $allNetworks = array();

    /**
     * Contains available NAS servers as id=>data
     *
     * @var array
     */
    protected $allNas = array();

    /**
     * Contains array of NASes served networks as netid=>nasids array
     *
     * @var array
     */
    protected $networkNases = array();

    /**
     * Contains available nethosts as ip=>data
     *
     * @var array
     */
    protected $allNetHosts = array();

    /**
     * Contains array of nethosts to networks bindings like ip=>netid
     *
     * @var array
     */
    protected $nethostsNetworks = array();

    /**
     * Contains list of available NAS attributes presets to generate as id=>data
     *
     * @var array
     */
    protected $nasAttributes = array();

    /**
     * Contains multigen NAS options like usernames types etc as nasid=>options
     *
     * @var array
     */
    protected $nasOptions = array();

    /**
     * Contains previously loaded scenarios attributes as scenario=>username=>attributes
     *
     * @var array
     */
    protected $currentAttributes = array();

    /**
     * System message helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Contains available username types as type=>name
     *
     * @var array
     */
    protected $usernameTypes = array();

    /**
     * Contains available nas service handlers as type=>name
     *
     * @var array
     */
    protected $serviceTypes = array();

    /**
     * Contains available operators as operator=>name
     *
     * @var array
     */
    protected $operators = array();

    /**
     * Contains available reply scenarios as stringid=>name
     *
     * @var array
     */
    protected $scenarios = array();

    /**
     * Contains NAS attributes regeneration stats as nasid=>scenario=>statsdata
     *
     * @var array
     */
    protected $scenarioStats = array();

    /**
     * User activity detection accuracy flag
     *
     * @var bool
     */
    protected $accurateUserActivity = true;

    /**
     * Is logging enabled may be exported from MULTIGEN_LOGGING option
     *
     * @var int
     */
    protected $logging = 0;

    /**
     * Contains basic module path
     */
    const URL_ME = '?module=multigen';

    /**
     * Default NAS options table name
     */
    const NAS_OPTIONS = 'mg_nasoptions';

    /**
     * Default NAS attributes templates table name
     */
    const NAS_ATTRIBUTES = 'mg_nasattributes';

    /**
     * Default scenario tables prefix
     */
    const SCENARIO_PREFIX = 'mg_';

    /**
     * Attributes generation logging option name
     */
    const OPTION_LOGGING = 'MULTIGEN_LOGGING';

    /**
     * log path
     */
    const LOG_PATH = 'exports/multigen.log';

    /**
     * Creates new MultiGen instance
     * 
     * @return void
     */
    public function __construct() {
        $this->loadConfigs();
        $this->setOptions();
        $this->initMessages();
        $this->loadNetworks();
        $this->loadNases();
        $this->loadNasAttributes();
        $this->loadNasOptions();
        $this->loadNethosts();
        $this->loadUserData();
        $this->preprocessUserData();
        $this->loadScenarios();
    }

    /**
     * Loads reqired configss
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
     * Sets some basic options for further usage
     * 
     * @return void
     */
    protected function setOptions() {
        if (isset($this->altCfg[self::OPTION_LOGGING])) {
            if ($this->altCfg[self::OPTION_LOGGING]) {
                $this->logging = $this->altCfg[self::OPTION_LOGGING];
            }
        }

        $this->usernameTypes = array(
            'login' => __('Login'),
            'ip' => __('IP'),
            'mac' => __('MAC')
        );

        $this->serviceTypes = array(
            'none' => __('No'),
            'coa' => __('CoA'),
            'pod' => __('PoD')
        );

        $this->scenarios = array(
            'check' => 'check',
            'reply' => 'reply'
        );

        $this->operators = array(
            '=' => '=',
            ':=' => ':=',
            '==' => '==',
            '+=' => '+=',
            '!=' => '!=',
            '>' => '>',
            '>=' => '>=',
            '<=' => '<=',
            '=~' => '=~',
            '!~' => '!~',
            '=*' => '=*',
            '!*' => '!*',
        );
    }

    /**
     * Inits system message helper for further usage
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Loads all existing users data from database
     * 
     * @return void
     */
    protected function loadUserData() {
        $this->allUserData = zb_UserGetAllData();
    }

    /**
     * Prepares user to NAS bindings array
     * 
     * @return void
     */
    protected function preprocessUserData() {
        if (!empty($this->allUserData)) {
            foreach ($this->allUserData as $io => $each) {
                $userIP = $each['ip'];
                if (isset($this->nethostsNetworks[$userIP])) {
                    if (isset($this->networkNases[$this->nethostsNetworks[$userIP]])) {
                        $userNases = $this->networkNases[$this->nethostsNetworks[$userIP]];
                        $this->userNases[$each['login']] = $userNases;
                    }
                }
            }
        }
    }

    /**
     * Loads existing networks from database
     * 
     * @return void
     */
    protected function loadNetworks() {
        $networksRaw = multinet_get_all_networks();
        if (!empty($networksRaw)) {
            foreach ($networksRaw as $io => $each) {
                $this->allNetworks[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads existing NAS servers from database
     * 
     * @return void
     */
    protected function loadNases() {
        $nasesRaw = zb_NasGetAllData();
        if (!empty($nasesRaw)) {
            foreach ($nasesRaw as $io => $each) {
                $this->allNas[$each['id']] = $each;
                $this->networkNases[$each['netid']][$each['id']] = $each['id'];
            }
        }
    }

    /**
     * Loads existing NAS servers attributes generation optionss
     * 
     * @return void
     */
    protected function loadNasAttributes() {
        $query = "SELECT * from `" . self::NAS_ATTRIBUTES . "`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->nasAttributes[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads multigen NAS options from database
     * 
     * @return void
     */
    protected function loadNasOptions() {
        $query = "SELECT * from `" . self::NAS_OPTIONS . "`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->nasOptions[$each['nasid']] = $each;
            }
        }
    }

    /**
     * Loads existing nethosts from database
     * 
     * @return void
     */
    protected function loadNethosts() {
        $query = "SELECT * from `nethosts`";
        $nethostsRaw = simple_queryall($query);
        if (!empty($nethostsRaw)) {
            foreach ($nethostsRaw as $io => $each) {
                $this->allNetHosts[$each['ip']] = $each;
                $this->nethostsNetworks[$each['ip']] = $each['netid'];
            }
        }
    }

    /**
     * Loads existing scenarios attributes
     * 
     * @return void
     */
    protected function loadScenarios() {
        if (!empty($this->scenarios)) {
            foreach ($this->scenarios as $scenarioId => $scenarioName) {
                $query = "SELECT * from `" . self::SCENARIO_PREFIX . $scenarioId . "`";
                $all = simple_queryall($query);
                if (!empty($all)) {
                    foreach ($all as $io => $each) {
                        $this->currentAttributes[$scenarioId][$each['username']][$each['attribute']]['name'] = $each['attribute'];
                        $this->currentAttributes[$scenarioId][$each['username']][$each['attribute']]['value'] = $each['value'];
                        $this->currentAttributes[$scenarioId][$each['username']][$each['attribute']]['op'] = $each['op'];
                    }
                }
            }
        }
    }

    /**
     * Logs data if logging is enabled
     * 
     * @param string $data
     * @param int $logLevel
     * 
     * @return void
     */
    protected function logEvent($data, $logLevel = 1) {
        if ($this->logging) {
            if ($this->logging >= $logLevel) {
                $curDate = curdatetime();
                $logData = $curDate . ' ' . $data . "\n";
                file_put_contents(self::LOG_PATH, $logData, FILE_APPEND);
            }
        }
    }

    /**
     * Renders NAS options editing form
     * 
     * @param int $nasId
     * 
     * @return string
     */
    public function renderNasOptionsEditForm($nasId) {
        $result = '';
        $nasId = vf($nasId, 3);
        if (isset($this->allNas[$nasId])) {
            $onlyActiveParams = array('1' => __('Yes'), '0' => __('No'));
            $inputs = wf_Selector('editnasusername', $this->usernameTypes, __('Username override'), @$this->nasOptions[$nasId]['usernametype'], false) . ' ';
            $inputs.= wf_Selector('editnasservice', $this->serviceTypes, __('Service'), @$this->nasOptions[$nasId]['service'], false) . ' ';
            $inputs.=wf_Selector('editnasonlyactive', $onlyActiveParams, __('Only active users'), @$this->nasOptions[$nasId]['onlyactive'], false) . ' ';
            $inputs.= wf_HiddenInput('editnasid', $nasId);
            $inputs.=wf_Submit(__('Save'));

            $result.=wf_Form(self::URL_ME . '&editnasoptions=' . $nasId, 'POST', $inputs, 'glamour');
        } else {
            $result.=$this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('NAS not exists'), 'error');
        }
        return ($result);
    }

    /**
     * Checks is NAS basically configured?
     * 
     * @param int $nasId
     * 
     * @return bool
     */
    public function nasHaveOptions($nasId) {
        $result = false;
        $nasId = vf($nasId, 3);
        if (isset($this->allNas[$nasId])) {
            if (isset($this->nasOptions[$nasId])) {
                $result = true;
            }
        }
        return ($result);
    }

    /**
     * Saves NAS basic options
     * 
     * @return void/string on error
     */
    public function saveNasOptions() {
        $result = '';
        if (wf_CheckPost(array('editnasid', 'editnasusername', 'editnasservice'))) {
            $nasId = vf($_POST['editnasid'], 3);
            if (isset($this->allNas[$nasId])) {
                $newUserName = $_POST['editnasusername'];
                $newService = $_POST['editnasservice'];
                $newOnlyActive = $_POST['editnasonlyactive'];
                //some NAS options already exists
                if (isset($this->nasOptions[$nasId])) {
                    $currentNasOptions = $this->nasOptions[$nasId];
                    $currentRecordId = $currentNasOptions['id'];
                    $where = "WHERE `id`='" . $currentRecordId . "'";
                    if ($currentNasOptions['usernametype'] != $newUserName) {
                        simple_update_field(self::NAS_OPTIONS, 'usernametype', $newUserName, $where);
                        log_register('MULTIGEN NAS [' . $nasId . '] CHANGE USERNAME `' . $newUserName . '`');
                    }

                    if ($currentNasOptions['service'] != $newService) {
                        simple_update_field(self::NAS_OPTIONS, 'service', $newService, $where);
                        log_register('MULTIGEN NAS [' . $nasId . '] CHANGE SERVICE `' . $newService . '`');
                    }

                    if ($currentNasOptions['onlyactive'] != $newOnlyActive) {
                        simple_update_field(self::NAS_OPTIONS, 'onlyactive', $newOnlyActive, $where);
                        log_register('MULTIGEN NAS [' . $nasId . '] CHANGE ONLYACTIVE `' . $newOnlyActive . '`');
                    }
                } else {
                    //new NAS options creation
                    $newUserName_f = mysql_real_escape_string($newUserName);
                    $newService_f = mysql_real_escape_string($newService);
                    $newOnlyActive_f = mysql_real_escape_string($newOnlyActive);
                    $quyery = "INSERT INTO `" . self::NAS_OPTIONS . "` (`id`,`nasid`,`usernametype`,`service`,`onlyactive`) VALUES "
                            . "(NULL,'" . $nasId . "','" . $newUserName_f . "','" . $newService_f . "','" . $newOnlyActive_f . "');";
                    nr_query($quyery);
                    log_register('MULTIGEN NAS [' . $nasId . '] CREATE USERNAME `' . $newUserName . '` SERVICE `' . $newService . '` ONLYAACTIVE `' . $newOnlyActive . '`');
                }
            } else {
                $result.=__('Something went wrong') . ': ' . __('NAS not exists');
            }
        }
        return ($result);
    }

    /**
     * Returns list of attribute presets for some NAS
     * 
     * @param int $nasId
     * 
     * @return array
     */
    protected function getNasAttributes($nasId) {
        $result = array();
        if (!empty($this->nasAttributes)) {
            foreach ($this->nasAttributes as $io => $each) {
                if ($each['nasid'] == $nasId) {
                    $result[$io] = $each;
                }
            }
        }
        return ($result);
    }

    /**
     * Deletes some attribute preset
     * 
     * @param int $attributeId
     * 
     * @return void/string on error
     */
    public function deleteNasAttribute($attributeId) {
        $result = '';
        $attributeId = vf($attributeId, 3);
        if (isset($this->nasAttributes[$attributeId])) {
            $attributeData = $this->nasAttributes[$attributeId];
            $query = "DELETE from `" . self::NAS_ATTRIBUTES . "` WHERE `id`='" . $attributeId . "';";
            nr_query($query);
            log_register('MULTIGEN ATTRIBUTE `' . $attributeData['attribute'] . '` DELETE [' . $attributeId . ']');
        } else {
            $result.=__('Something went wrong') . ': ' . __('not existing attribute');
        }
        return ($result);
    }

    /**
     * Renders list of available attributes for some NAS
     * 
     * @param int $nasId
     * 
     * @return string
     */
    public function renderNasAttributesList($nasId) {
        $result = '';
        $nasId = vf($nasId, 3);
        if (isset($this->allNas[$nasId])) {
            $currentAtrributes = $this->getNasAttributes($nasId);
            if (!empty($currentAtrributes)) {
                $cells = wf_TableCell(__('Scenario'));
                $cells.= wf_TableCell(__('Attribute'));
                $cells.=wf_TableCell(__('Operator'));
                $cells.= wf_TableCell(__('Value'));
                $cells.= wf_TableCell(__('Actions'));
                $rows = wf_TableRow($cells, 'row1');

                foreach ($currentAtrributes as $io => $each) {
                    $cells = wf_TableCell($each['scenario']);
                    $cells.= wf_TableCell($each['attribute']);
                    $cells.=wf_TableCell($each['operator']);
                    $cells.= wf_TableCell($each['content']);
                    $attributeControls = wf_JSAlert(self::URL_ME . '&editnasoptions=' . $nasId . '&deleteattributeid=' . $each['id'], web_delete_icon(), $this->messages->getDeleteAlert());
                    $cells.= wf_TableCell($attributeControls);
                    $rows.= wf_TableRow($cells, 'row5');
                }

                $result.=wf_TableBody($rows, '100%', 0, 'sortable');
            } else {
                $result.=$this->messages->getStyledMessage(__('Nothing to show'), 'warning');
            }
        } else {
            $result.=$this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('NAS not exists'), 'error');
        }
        return ($result);
    }

    /**
     * Renders NAS attributes editing form
     * 
     * @param int $nasId
     * 
     * @return string
     */
    public function renderNasAttributesEditForm($nasId) {
        $result = '';
        $nasId = vf($nasId, 3);
        if (isset($this->allNas[$nasId])) {
            $inputs = wf_Selector('newscenario', $this->scenarios, __('Scenario'), '', false) . ' ';
            $inputs.= wf_TextInput('newattribute', __('Attribute'), '', false, 20) . ' ';
            $inputs.= wf_Selector('newoperator', $this->operators, __('Operator'), '', false) . ' ';
            $inputs.= wf_TextInput('newcontent', __('Value'), '', false, 20) . ' ';
            $inputs.= wf_HiddenInput('newattributenasid', $nasId);
            $inputs.= wf_Submit(__('Create'));
            //form assembly
            $result.=wf_Form(self::URL_ME . '&editnasoptions=' . $nasId, 'POST', $inputs, 'glamour');
        } else {
            $result.=$this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('NAS not exists'), 'error');
        }
        return ($result);
    }

    /**
     * Creates new NAS attribute preset
     * 
     * 
     * @return void/string on error
     */
    public function createNasAttribute() {
        $result = '';
        if (wf_CheckPost(array('newattributenasid'))) {
            $nasId = vf($_POST['newattributenasid'], 3);
            if (isset($this->allNas[$nasId])) {
                if (wf_CheckPost(array('newscenario', 'newattribute', 'newoperator', 'newcontent'))) {
                    $newScenario = $_POST['newscenario'];
                    $newScenario_f = mysql_real_escape_string($newScenario);
                    $newAttribute = $_POST['newattribute'];
                    $newAttribute_f = mysql_real_escape_string($newAttribute);
                    $newOperator = $_POST['newoperator'];
                    $newOperator_f = mysql_real_escape_string($newOperator);
                    $newContent = $_POST['newcontent'];
                    $newContent_f = mysql_real_escape_string($newContent);

                    $query = "INSERT INTO `" . self::NAS_ATTRIBUTES . "` (`id`,`nasid`,`scenario`,`attribute`,`operator`,`content`) VALUES "
                            . "(NULL,'" . $nasId . "','" . $newScenario_f . "','" . $newAttribute_f . "','" . $newOperator_f . "','" . $newContent_f . "');";
                    nr_query($query);
                    log_register('MULTIGEN NAS [' . $nasId . '] CREATE ATTRIBUTE `' . $newAttribute . '`');
                }
            } else {
                $result.=__('Something went wrong') . ': ' . __('NAS not exists');
            }
        }
        return ($result);
    }

    /**
     * Checks is user active or not
     * 
     * @param string $userLogin
     * @param bool $onlyActive
     * 
     * @return bool
     */
    protected function isUserActive($userLogin, $onlyActive) {
        if ($onlyActive) {
            //really check user activity
            $result = false;
            if (isset($this->allUserData[$userLogin])) {
                if ($this->accurateUserActivity) {
                    if ($this->allUserData[$userLogin]['Cash'] >= '-' . abs($this->allUserData[$userLogin]['Credit'])) {
                        if ($this->allUserData[$userLogin]['Passive'] == 0) {
                            if ($this->allUserData[$userLogin]['AlwaysOnline'] == 1) {
                                if ($this->allUserData[$userLogin]['Down'] == 0) {
                                    $result = true;
                                }
                            }
                        }
                    }
                } else {
                    $result = ($this->allUserData[$userLogin]['Cash'] >= '-' . $this->allUserData[$userLogin]['Credit']) ? true : false;
                }
            }
        } else {
            //for this NAS users always will be active
            $result = true;
        }
        return ($result);
    }

    /**
     * Checks is some user attribute available in scenario and is not changed
     * 
     * @param string $scenario
     * @param string $userLogin
     * @param string $userName
     * @param string $attribute
     * @param string $operator
     * @param string $value
     * 
     * @return int 0 - not exist / 1 - exists and not changed / -2 - changed
     */
    protected function checkScenarioAttribute($scenario, $userLogin, $userName, $attribute, $operator, $value) {
        $result = 0;
        if (isset($this->currentAttributes[$scenario])) {
            if (isset($this->currentAttributes[$scenario][$userName])) {
                if (isset($this->currentAttributes[$scenario][$userName][$attribute])) {
                    if ($this->currentAttributes[$scenario][$userName][$attribute]['value'] == $value) {
                        if ($this->currentAttributes[$scenario][$userName][$attribute]['op'] == $operator) {
                            $result = 1;
                        } else {
                            $result = -2;
                        }
                    } else {
                        $result = -2;
                    }
                } else {
                    $result = 0;
                }
            } else {
                $result = 0;
            }
        } else {
            $result = 0;
        }
        $this->logEvent($userLogin . ' AS ' . $userName . ' SCENARIO ' . $scenario . ' TEST ' . $attribute . ' ' . $operator . ' ' . $value . ' RESULT ' . $result, 2);
        return ($result);
    }

    /**
     * Pushes some scenario attribute to database
     * 
     * @param string $scenario
     * @param string $userLogin
     * @param string $userName
     * @param string $attribute
     * @param string $op
     * @param string $value
     * 
     * @return void
     */
    protected function createScenarioAttribute($scenario, $userLogin, $userName, $attribute, $op, $value) {
        $query = "INSERT INTO `" . self::SCENARIO_PREFIX . $scenario . "` (`id`,`username`,`attribute`,`op`,`value`) VALUES " .
                "(NULL,'" . $userName . "','" . $attribute . "','" . $op . "','" . $value . "');";
        nr_query($query);
        $this->logEvent($userLogin . ' AS ' . $userName . ' SCENARIO ' . $scenario . ' CREATE ' . $attribute . ' ' . $op . ' ' . $value, 1);
    }

    /**
     * Drops some reply attribute from database (required on value changes)
     * 
     * @param string $scenario
     * @param string $userLogin
     * @param string $userName
     * @param string $attribute
     * 
     * @return void
     */
    protected function deleteScenarioAttribute($scenario, $userLogin, $userName, $attribute) {
        $query = "DELETE FROM `" . self::SCENARIO_PREFIX . $scenario . "` WHERE `username`='" . $userName . "' AND `attribute`='" . $attribute . "';";
        nr_query($query);
        $this->logEvent($userLogin . ' AS ' . $userName . ' SCENARIO ' . $scenario . ' DELETE ' . $attribute, 1);
    }

    /**
     * Writes attributes regeneration stats
     * 
     * @param int $nasId
     * @param string $scenario
     * @param string $attributeState
     * 
     * @return void
     */
    protected function writeScenarioStats($nasId, $scenario, $attributeState) {
        if ((!isset($this->scenarioStats[$nasId])) OR ( !isset($this->scenarioStats[$nasId][$scenario])) OR ( !isset($this->scenarioStats[$nasId][$scenario][$attributeState]))) {
            $this->scenarioStats[$nasId][$scenario][$attributeState] = 1;
        } else {
            $this->scenarioStats[$nasId][$scenario][$attributeState] ++;
        }
    }

    /**
     * Flushes buried user attributes if they are exists in database
     * 
     * @param int    $nasId
     * @param string $scenario
     * @param string $userLogin
     * @param string $userName
     * @param string $attribute
     * 
     * @return void
     */
    protected function flushBuriedUser($nasId, $scenario, $userLogin, $userName, $attribute) {
        if (isset($this->currentAttributes[$scenario])) {
            if (isset($this->currentAttributes[$scenario][$userName])) {
                if (isset($this->currentAttributes[$scenario][$userName][$attribute])) {
                    $this->deleteScenarioAttribute($scenario, $userLogin, $userName, $attribute);
                    $this->writeScenarioStats($nasId, $scenario, 'buried');
                }
            }
        }
    }

    /**
     * Returns user state as string
     * 
     * @param string $userLogin
     * 
     * @return string
     */
    protected function getUserStateString($userLogin) {
        $result = '';
        if (isset($this->allUserData[$userLogin])) {
            if (($this->allUserData[$userLogin]['Cash'] >= '-' . $this->allUserData[$userLogin]['Credit']) AND ( $this->allUserData[$userLogin]['AlwaysOnline'] == 1)) {
                $result = 'ON-LINE';
            } else {
                $result = 'OFF-LINE';
            }
            if ($this->allUserData[$userLogin]['Down']) {
                $result = 'DOWN';
            }
            if ($this->allUserData[$userLogin]['Passive']) {
                $result = 'PASSIVE';
            }
        } else {
            $result = 'NOT-EXIST';
        }
        return ($result);
    }

    /**
     * Parses network data to network address and network CIDR
     * 
     * @param string $netDesc
     * 
     * @return array
     */
    protected function parseNetworkDesc($netDesc) {
        $result = array();
        $netDesc = explode('/', $netDesc);
        $result = array('addr' => $netDesc[0], 'cidr' => $netDesc[1]);
        return ($result);
    }

    /**
     * Transforms mac from xx:xx:xx:xx:xx:xx format to xxxx.xxxx.xxxx
     * 
     * @param string $mac
     * 
     * @return string
     */
    protected function transformMacDotted($mac) {
        $result = implode(".", str_split(str_replace(":", "", $mac), 4));
        return ($result);
    }

    /**
     * Transforms mac from xx:xx:xx:xx:xx:xx format to XX-XX-XX-XX-XX-XX
     * 
     * @param string $mac
     * 
     * @return string
     */
    protected function transformMacMinusedCaps($mac) {
        $result = str_replace(':', '-', $mac);
        $result = strtoupper($result);
        return ($result);
    }

    /**
     * Transforms CIDR notation to xxx.xxx.xxx.xxx netmask
     * 
     * @param string $cidr - CIDR
     * 
     * @return string
     */
    protected function transformCidrtoMask($cidr) {
        $result = long2ip(-1 << (32 - (int) $cidr));
        return ($result);
    }

    /**
     * Returns attribute templates value with replaced macro
     * 
     * @param string $userLogin
     * @param string $userName
     * @param string $template
     * 
     * @return string
     */
    public function getAttributeValue($userLogin, $userName, $template) {
        if (isset($this->allUserData[$userLogin])) {
            if (ispos($template, '{IP}')) {
                $template = str_replace('{IP}', $this->allUserData[$userLogin]['ip'], $template);
            }
            if (ispos($template, '{MAC}')) {
                $template = str_replace('{MAC}', $this->allUserData[$userLogin]['mac'], $template);
            }
            if (ispos($template, '{MACDOT}')) {
                $template = str_replace('{MACDOT}', $this->transformMacDotted($this->allUserData[$userLogin]['mac']), $template);
            }
            if (ispos($template, '{LOGIN}')) {
                $template = str_replace('{LOGIN}', $userLogin, $template);
            }
            if (ispos($template, '{USERNAME}')) {
                $template = str_replace('{USERNAME}', $userName, $template);
            }
            if (ispos($template, '{PASSWORD}')) {
                $template = str_replace('{PASSWORD}', $this->allUserData[$userLogin]['Password'], $template);
            }
            if (ispos($template, '{TARIFF}')) {
                $template = str_replace('{TARIFF}', $this->allUserData[$userLogin]['Tariff'], $template);
            }
            if (ispos($template, '{NETID}')) {
                $template = str_replace('{NETID}', $this->nethostsNetworks[$this->allUserData[$userLogin]['ip']], $template);
            }
            if (ispos($template, '{NETADDR}')) {
                $netDesc = $this->allNetworks[$this->nethostsNetworks[$this->allUserData[$userLogin]['ip']]]['desc'];
                $netDesc = $this->parseNetworkDesc($netDesc);
                $netAddr = $netDesc['addr'];
                $template = str_replace('{NETADDR}', $netAddr, $template);
            }
            if (ispos($template, '{NETCIDR}')) {
                $netDesc = $this->allNetworks[$this->nethostsNetworks[$this->allUserData[$userLogin]['ip']]]['desc'];
                $netDesc = $this->parseNetworkDesc($netDesc);
                $netCidr = $netDesc['cidr'];
                $template = str_replace('{NETCIDR}', $netCidr, $template);
            }
            if (ispos($template, '{NETSTART}')) {
                $template = str_replace('{NETSTART}', $this->allNetworks[$this->nethostsNetworks[$this->allUserData[$userLogin]['ip']]]['startip'], $template);
            }
            if (ispos($template, '{NETEND}')) {
                $template = str_replace('{NETEND}', $this->allNetworks[$this->nethostsNetworks[$this->allUserData[$userLogin]['ip']]]['endip'], $template);
            }
            if (ispos($template, '{NETDESC}')) {
                $template = str_replace('{NETDESC}', $this->allNetworks[$this->nethostsNetworks[$this->allUserData[$userLogin]['ip']]]['desc'], $template);
            }
            if (ispos($template, '{NETMASK}')) {
                $netDesc = $this->allNetworks[$this->nethostsNetworks[$this->allUserData[$userLogin]['ip']]]['desc'];
                $netDesc = $this->parseNetworkDesc($netDesc);
                $netCidr = $netDesc['cidr'];
                $netMask = $this->transformCidrtoMask($netCidr);
                $template = str_replace('{NETMASK}', $netMask, $template);
            }
        }

        if (ispos($template, '{STATE}')) {
            $template = str_replace('{STATE}', $this->getUserStateString($userLogin), $template);
        }

        $result = $template;
        return ($result);
    }

    /**
     * Performs generation of user attributes if their NAS requires it.
     * 
     * @return void
     */
    public function generateNasAttributes() {
        if (!empty($this->allUserData)) {
            foreach ($this->allUserData as $io => $eachUser) {
                $userLogin = $eachUser['login'];
                if (isset($this->userNases[$userLogin])) {
                    $userNases = $this->userNases[$userLogin];
                    if (!empty($userNases)) {
                        foreach ($userNases as $eachNasId) {
                            @$nasOptions = $this->nasOptions[$eachNasId];
                            $userNameType = $nasOptions['usernametype'];
                            //overriding username type if required
                            switch ($userNameType) {
                                case 'login':
                                    $userName = $userLogin;
                                    break;
                                case 'ip':
                                    $userName = $eachUser['ip'];
                                    break;
                                case 'mac':
                                    $userName = $eachUser['mac'];
                                    break;
                            }

                            if (!empty($nasOptions)) {
                                $nasAttributes = $this->getNasAttributes($eachNasId);
                                if (!empty($nasAttributes)) {
                                    foreach ($nasAttributes as $eachAttributeId => $eachAttributeData) {
                                        $scenario = $eachAttributeData['scenario'];
                                        $onlyActive = $nasOptions['onlyactive'];
                                        $attribute = $eachAttributeData['attribute'];
                                        if ($this->isUserActive($userLogin, $onlyActive)) {
                                            $op = $eachAttributeData['operator'];
                                            $template = $eachAttributeData['content'];
                                            $value = $this->getAttributeValue($userLogin, $userName, $template);

                                            $attributeCheck = $this->checkScenarioAttribute($scenario, $userLogin, $userName, $attribute, $op, $value);
                                            if ($attributeCheck == -2) {
                                                //dropping already changed attribute from this scenario
                                                $this->deleteScenarioAttribute($scenario, $userLogin, $userName, $attribute);
                                            }
                                            if (($attributeCheck == 0) OR ( $attributeCheck == -2)) {
                                                //creating new attribute with actual data
                                                $this->createScenarioAttribute($scenario, $userLogin, $userName, $attribute, $op, $value);
                                                $this->writeScenarioStats($eachNasId, $scenario, 'generated');
                                            }

                                            if ($attributeCheck == 1) {
                                                //attribute exists and not changed
                                                $this->writeScenarioStats($eachNasId, $scenario, 'skipped');
                                            }
                                        } else {
                                            //flush some not-active user attributes if required
                                            $this->flushBuriedUser($eachNasId, $scenario, $userLogin, $userName, $attribute);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Returns NAS label as IP - name
     * 
     * @param int $nasId
     * 
     * @return string
     */
    public function getNaslabel($nasId) {
        $result = '';
        if (isset($this->allNas[$nasId])) {
            $result.=$this->allNas[$nasId]['nasip'] . ' - ' . $this->allNas[$nasId]['nasname'];
        }
        return ($result);
    }

    /**
     * Renders some NAS attributes regeneration stats
     * 
     * @return strings
     */
    public function renderScenarioStats() {
        $result = '';

        if (!empty($this->scenarioStats)) {
            foreach ($this->scenarioStats as $nasId => $scenario) {
                $nasLabel = $this->getNaslabel($nasId);
                $result.=$this->messages->getStyledMessage($nasLabel, 'success');
                if (!empty($scenario)) {
                    foreach ($scenario as $scenarioName => $counters) {
                        if (!empty($counters)) {
                            foreach ($counters as $eachState => $eachCount) {
                                switch ($eachState) {
                                    case 'skipped':
                                        $stateName = __('Attributes is unchanged and generation was skipped');
                                        $stateStyle = 'info';
                                        break;
                                    case 'generated':
                                        $stateName = __('New or changed attributes generated');
                                        $stateStyle = 'warning';
                                        break;
                                    case 'buried':
                                        $stateName = __('Attributes was flushed due user inactivity');
                                        $stateStyle = 'warning';
                                        break;
                                    default :
                                        $stateName = $eachState;
                                        $stateStyle = 'info';
                                        break;
                                }
                                $result.=$this->messages->getStyledMessage($stateName . ' ' . __('for scenario') . ' ' . $scenarioName . ': ' . $eachCount, $stateStyle);
                            }
                        }
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Renders NAS controls panel
     * 
     * @param int $nasId
     * 
     * @return string
     */
    public function nasControlPanel($nasId) {
        $result = '';
        $result.=wf_BackLink('?module=nas') . ' ';
        if ($this->nasHaveOptions($nasId)) {
            $result.=wf_AjaxLoader();
            $result.=wf_AjaxLink(self::URL_ME . '&ajnasregen=true&editnasoptions=' . $nasId, wf_img('skins/refresh.gif') . ' ' . __('Base regeneration'), 'nascontrolajaxcontainer', false, 'ubButton');
            $result.=wf_AjaxContainer('nascontrolajaxcontainer');
        }
        return ($result);
    }

}

/**
 * Returns list of available free Juniper NASes
 * 
 * @return string
 */
function web_MultigenListClients() {
    $result = __('Nothing found');
    $query = "SELECT * from `mg_clients` GROUP BY `nasname`";
    $all = simple_queryall($query);
    if (!empty($all)) {
        $cells = wf_TableCell(__('IP'));
        $cells.= wf_TableCell(__('NAS name'));
        $cells.= wf_TableCell(__('Radius secret'));
        $rows = wf_TableRow($cells, 'row1');
        foreach ($all as $io => $each) {
            $cells = wf_TableCell($each['nasname']);
            $cells.= wf_TableCell($each['shortname']);
            $cells.= wf_TableCell($each['secret']);
            $rows.= wf_TableRow($cells, 'row3');
        }
        $result = wf_TableBody($rows, '100%', '0', 'sortable');
    }

    return ($result);
}

?>