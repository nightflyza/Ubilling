<?php

class MultiGen {

    /**
     * Contains system alter.ini config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains system billing.ini config as key=>value
     *
     * @var array
     */
    protected $billCfg = array();

    /**
     * Contains all stargazer user data
     *
     * @var array
     */
    protected $allUserData = array();

    /**
     * Contains all tariff speeds as tariffname=>speeddata(speeddown/speedup) Kbit/s
     *
     * @var array
     */
    protected $tariffSpeeds = array();

    /**
     * Contains user speed overrides as login=>override speed in Kbit/s
     *
     * @var array
     */
    protected $userSpeedOverrides = array();

    /**
     * Contains array of user switch assigns as login=>asigndata
     *
     * @var array
     */
    protected $userSwitchAssigns = array();

    /**
     * Contains array of available switches as id=>switchdata
     *
     * @var array
     */
    protected $allSwitches = array();

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
     * Contains previous user states as login=>previous/current/changed state
     *
     * @var array
     */
    protected $userStates = array();

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
     * Contains default NAS attributes modifiers as id=>name
     *
     * @var array
     */
    protected $attrModifiers = array();

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
     * Contains performance timers and stats
     * 
     * @var array
     */
    protected $perfStats = array();

    /**
     * Contains interesting fields from database acct table
     *
     * @var array
     */
    protected $acctFieldsRequired = array();

    /**
     * Contains preloaded user accounting data
     *
     * @var array
     */
    protected $userAcctData = array();

    /**
     * Contains NAS services templates as nasid=>services data
     *
     * @var array
     */
    protected $services = array();

    /**
     * Contains services names which requires run, like pod or coa
     *
     * @var array
     */
    protected $runServices = array();

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
     * Contains default echo path
     *
     * @var string
     */
    protected $echoPath = '/bin/echo';

    /**
     * Contains default path and options for radclient
     *
     * @var string
     */
    protected $radclienPath = '/usr/local/bin/radclient -r 3 -t 1';

    /**
     * Contains default path to sudo
     *
     * @var string
     */
    protected $sudoPath = '/usr/local/bin/sudo';

    /**
     * Contains default path to printf
     *
     * @var string
     */
    protected $printfPath = '/usr/bin/printf';

    /**
     * Default remote radclient port
     *
     * @var int
     */
    protected $remotePort = 3799;

    /**
     * Contains basic module path
     */
    const URL_ME = '?module=multigen';

    /**
     * Default radius clients table/view name
     */
    const CLIENTS = 'mlg_clients';

    /**
     * Default NAS options table name
     */
    const NAS_OPTIONS = 'mlg_nasoptions';

    /**
     * Default services templates table name
     */
    const NAS_SERVICES = 'mlg_services';

    /**
     * Default NAS attributes templates table name
     */
    const NAS_ATTRIBUTES = 'mlg_nasattributes';

    /**
     * Default accounting table name
     */
    const NAS_ACCT = 'mlg_acct';

    /**
     * Default user states database table name
     */
    const USER_STATES = 'mlg_userstates';

    /**
     * Default scenario tables prefix
     */
    const SCENARIO_PREFIX = 'mlg_';

    /**
     * Attributes generation logging option name
     */
    const OPTION_LOGGING = 'MULTIGEN_LOGGING';

    /**
     * RADIUS client option name
     */
    const OPTION_RADCLIENT = 'MULTIGEN_RADCLIENT';

    /**
     * sudo path option name
     */
    const OPTION_SUDO = 'SUDO';

    /**
     * log path
     */
    const LOG_PATH = 'exports/multigen.log';

    /**
     * Contains default path to PoD scripts queue
     */
    const POD_PATH = 'content/documents/pod_queue';

    /**
     * Contains default path to CoA scripts queue
     */
    const COA_PATH = 'content/documents/coa_queue';

    /**
     * Creates new MultiGen instance
     * 
     * @return void
     */
    public function __construct() {
        $this->loadConfigs();
        $this->setOptions();
        $this->initMessages();
        $this->loadNases();
        $this->loadNasAttributes();
        $this->loadNasOptions();
        $this->loadNasServices();
    }

    /**
     * Loads huge amounts of data, required only for attributes generation/processing
     * 
     * @return void
     */
    protected function loadHugeRegenData() {
        $this->loadUserData();
        $this->loadNethosts();
        $this->loadNetworks();
        $this->loadTariffSpeeds();
        $this->loadUserSpeedOverrides();
        $this->preprocessUserData();
        $this->loadSwitches();
        $this->loadSwithchAssigns();
        $this->loadScenarios();
        $this->loadUserStates();
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
        $this->billCfg = $ubillingConfig->getBilling();
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

        if (isset($this->altCfg[self::OPTION_RADCLIENT])) {
            $this->radclienPath = $this->altCfg[self::OPTION_RADCLIENT];
        }

        if (isset($this->billCfg[self::OPTION_SUDO])) {
            $this->sudoPath = $this->billCfg[self::OPTION_SUDO];
        }

        $this->usernameTypes = array(
            'login' => __('Login'),
            'ip' => __('IP'),
            'mac' => __('MAC') . ' ' . __('default'),
            'macju' => __('MAC') . ' ' . __('JunOS like'),
        );

        $this->serviceTypes = array(
            'none' => __('No'),
            'coa' => __('CoA'),
            'pod' => __('PoD'),
            'podcoa' => __('PoD') . '+' . __('CoA')
        );

        $this->scenarios = array(
            'check' => 'check',
            'reply' => 'reply',
            'groupreply'=>'groupreply'
        );

        $this->attrModifiers = array(
            'all' => __('All'),
            'active' => __('Active'),
            'inactive' => __('Inactive')
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

        $this->acctFieldsRequired = array(
            'acctsessionid',
            'username',
            'nasipaddress',
            'nasportid',
            'acctstarttime',
            'acctstoptime',
            'acctinputoctets',
            'acctoutputoctets',
            'framedipaddress',
            'acctterminatecause'
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
     * Loads existing tariffs speeds from database
     * 
     * @return void
     */
    protected function loadTariffSpeeds() {
        $this->tariffSpeeds = zb_TariffGetAllSpeeds();
    }

    /**
     * Loads user speed overrides if they assigned for user
     * 
     * @return void
     */
    protected function loadUserSpeedOverrides() {
        $speedOverrides_q = "SELECT * from `userspeeds` WHERE `speed` NOT LIKE '0';";
        $rawOverrides = simple_queryall($speedOverrides_q);
        if (!empty($rawOverrides)) {
            foreach ($rawOverrides as $io => $each) {
                $this->userSpeedOverrides[$each['login']] = $each['speed'];
            }
        }
    }

    /**
     * Loads switch port assigns from database
     * 
     * @return void
     */
    protected function loadSwithchAssigns() {
        $query = "SELECT * from `switchportassign`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->userSwitchAssigns[$each['login']] = $each;
            }
        }
    }

    /**
     * Loads all existing switches data from database
     * 
     * @return void
     */
    protected function loadSwitches() {
        $switchesTmp = zb_SwitchesGetAll();
        if (!empty($switchesTmp)) {
            foreach ($switchesTmp as $io => $each) {
                $this->allSwitches[$each['id']] = $each;
            }
        }
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
     * Loads previous user states from database
     * 
     * @return void
     */
    protected function loadUserStates() {
        $query = "SELECT * from `" . self::USER_STATES . "`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->userStates[$each['login']]['previous'] = $each['state'];
                $this->userStates[$each['login']]['current'] = '';
                $this->userStates[$each['login']]['changed'] = '';
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
     * Loads NAS services presets
     * 
     * @return void
     */
    protected function loadNasServices() {
        $query = "SELECT * from `" . self::NAS_SERVICES . "`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->services[$each['nasid']] = $each;
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
     * Loading some accounting data from database
     * 
     * @return void
     */
    protected function loadAcctData() {
        $fieldsList = implode(', ', $this->acctFieldsRequired);
        if (wf_CheckGet(array('datefrom', 'dateto'))) {
            $searchDateFrom = mysql_real_escape_string($_GET['datefrom']);
            $searchDateTo = mysql_real_escape_string($_GET['dateto']);
        } else {
            $curTime = time();
            $dayAgo = $curTime - 86400;
            $dayAgo = date("Y-m-d", $dayAgo);
            $dayTomorrow = $curTime + 86400;
            $dayTomorrow = date("Y-m-d", $dayTomorrow);
            $searchDateFrom = $dayAgo;
            $searchDateTo = $dayTomorrow;
        }

        if (wf_CheckGet(array('showunfinished'))) {
            $unfQueryfilter = "OR `acctstoptime` IS NULL ";
        } else {
            $unfQueryfilter = '';
        }

        $query = "SELECT " . $fieldsList . " FROM `" . self::NAS_ACCT . "` WHERE `acctstarttime` BETWEEN '" . $searchDateFrom . "' AND '" . $searchDateTo . "'"
                . " " . $unfQueryfilter . "  ORDER BY `radacctid` DESC ;";
        $this->userAcctData = simple_queryall($query);
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
            $nasPort = @$this->nasOptions[$nasId]['port'];
            $nasPort = (!empty($nasPort)) ? $nasPort : $this->remotePort;
            $inputs.= wf_TextInput('editnasport', __('Port'), $nasPort, false, 6, 'digits') . ' ';
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
        if (wf_CheckPost(array('editnasid', 'editnasusername', 'editnasservice', 'editnasport'))) {
            $nasId = vf($_POST['editnasid'], 3);
            if (isset($this->allNas[$nasId])) {
                $newUserName = $_POST['editnasusername'];
                $newService = $_POST['editnasservice'];
                $newOnlyActive = $_POST['editnasonlyactive'];
                $newPort = $_POST['editnasport'];
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

                    if ($currentNasOptions['port'] != $newPort) {
                        simple_update_field(self::NAS_OPTIONS, 'port', $newPort, $where);
                        log_register('MULTIGEN NAS [' . $nasId . '] CHANGE PORT `' . $newPort . '`');
                    }
                } else {
                    //new NAS options creation
                    $newUserName_f = mysql_real_escape_string($newUserName);
                    $newService_f = mysql_real_escape_string($newService);
                    $newOnlyActive_f = mysql_real_escape_string($newOnlyActive);
                    $newPort_f = vf($newPort, 3);
                    $quyery = "INSERT INTO `" . self::NAS_OPTIONS . "` (`id`,`nasid`,`usernametype`,`service`,`onlyactive`,`port`) VALUES "
                            . "(NULL,'" . $nasId . "','" . $newUserName_f . "','" . $newService_f . "','" . $newOnlyActive_f . "','" . $newPort_f . "');";
                    nr_query($quyery);
                    log_register('MULTIGEN NAS [' . $nasId . '] CREATE USERNAME `' . $newUserName . '` SERVICE `' . $newService . '` ONLYAACTIVE `' . $newOnlyActive . '` PORT `' . $newPort . '`');
                }
            } else {
                $result.=__('Something went wrong') . ': ' . __('NAS not exists');
            }
        }
        return ($result);
    }

    /**
     * Saves NAS services templates
     * 
     * @return void/string on error
     */
    public function saveNasServices() {
        $result = '';
        if (wf_CheckPost(array('newnasservicesid'))) {
            $nasId = vf($_POST['newnasservicesid'], 3);
            if (isset($this->allNas[$nasId])) {
                $newPod = $_POST['newnasservicepod'];
                $newCoaConnect = $_POST['newnasservicecoaconnect'];
                $newCoaDisconnect = $_POST['newnasservicecoadisconnect'];
                //some NAS services already exists
                if (isset($this->services[$nasId])) {
                    $currentNasServices = $this->services[$nasId];
                    $currentRecordId = $currentNasServices['id'];
                    $where = "WHERE `id`='" . $currentRecordId . "'";
                    //update pod script template
                    if ($currentNasServices['pod'] != $newPod) {
                        simple_update_field(self::NAS_SERVICES, 'pod', $newPod, $where);
                        log_register('MULTIGEN NAS [' . $nasId . '] CHANGE SERVICE POD');
                    }
                    //update coa connect script template
                    if ($currentNasServices['coaconnect'] != $newCoaConnect) {
                        simple_update_field(self::NAS_SERVICES, 'coaconnect', $newCoaConnect, $where);
                        log_register('MULTIGEN NAS [' . $nasId . '] CHANGE SERVICE COACONNECT');
                    }

                    //update coa disconnect script template
                    if ($currentNasServices['coadisconnect'] != $newCoaDisconnect) {
                        simple_update_field(self::NAS_SERVICES, 'coadisconnect', $newCoaDisconnect, $where);
                        log_register('MULTIGEN NAS [' . $nasId . '] CHANGE SERVICE COADISCONNECT');
                    }
                } else {
                    //new NAS services creation
                    $newPod_f = mysql_real_escape_string($newPod);
                    $newCoaConnect_f = mysql_real_escape_string($newCoaConnect);
                    $newCoaDisconnect_f = mysql_real_escape_string($newCoaDisconnect);
                    $quyery = "INSERT INTO `" . self::NAS_SERVICES . "` (`id`,`nasid`,`pod`,`coaconnect`,`coadisconnect`) VALUES "
                            . "(NULL,'" . $nasId . "','" . $newPod_f . "','" . $newCoaConnect_f . "','" . $newCoaDisconnect_f . "');";
                    nr_query($quyery);
                    log_register('MULTIGEN NAS [' . $nasId . '] CREATE  SERVICES');
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
     * Flushes all attributes for all scenarios
     * 
     * @return void
     */
    public function flushAllScenarios() {
        if (!empty($this->scenarios)) {
            foreach ($this->scenarios as $scenarioId => $scenarioName) {
                $query = "TRUNCATE TABLE `" . self::SCENARIO_PREFIX . $scenarioId . "`;";
                nr_query($query);
                log_register('MULTIGEN FLUSH SCENARIO `' . $scenarioId . '`');
            }
        }
    }

    /**
     * Renders list of flushed scenarios
     * 
     * @return string
     */
    public function renderFlushAllScenariosNotice() {
        $result = '';
        if (!empty($this->scenarios)) {
            foreach ($this->scenarios as $scenarioId => $scenarioName) {
                $result.=$this->messages->getStyledMessage(__('All attributes in scenario was deleted') . ': ' . $scenarioId, 'error');
            }
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
                $cells = wf_TableCell(__('Users'));
                $cells.= wf_TableCell(__('Scenario'));
                $cells.= wf_TableCell(__('Attribute'));
                $cells.=wf_TableCell(__('Operator'));
                $cells.= wf_TableCell(__('Value'));
                $cells.= wf_TableCell(__('Actions'));
                $rows = wf_TableRow($cells, 'row1');

                foreach ($currentAtrributes as $io => $each) {
                    $cells = wf_TableCell($this->attrModifiers[$each['modifier']]);
                    $cells.= wf_TableCell($each['scenario']);
                    $cells.= wf_TableCell($each['attribute']);
                    $cells.=wf_TableCell($each['operator']);
                    $cells.= wf_TableCell($each['content']);
                    $attributeControls = wf_JSAlert(self::URL_ME . '&editnasoptions=' . $nasId . '&deleteattributeid=' . $each['id'], web_delete_icon(), $this->messages->getDeleteAlert()) . ' ';
                    $attributeControls.= wf_modalAuto(web_edit_icon(), __('Edit'), $this->renderAttributeTemplateEditForm($each['id']));
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
     * Renders NAS attributes creation form
     * 
     * @param int $nasId
     * 
     * @return string
     */
    public function renderNasAttributesCreateForm($nasId) {
        $result = '';
        $nasId = vf($nasId, 3);
        if (isset($this->allNas[$nasId])) {
            $inputs = '';
            $inputs.= wf_Selector('newmodifier', $this->attrModifiers, __('Users'), '', false) . ' ';
            $inputs.= wf_Selector('newscenario', $this->scenarios, __('Scenario'), '', false) . ' ';
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
     * Renders existing NAS attribute template editing forms
     * 
     * @param int $attributeId
     * 
     * @return string
     */
    public function renderAttributeTemplateEditForm($attributeId) {
        $result = '';
        $attributeId = vf($attributeId, 3);
        if (isset($this->nasAttributes[$attributeId])) {
            $attributeData = $this->nasAttributes[$attributeId];
            $nasId = $attributeData['nasid'];
            $inputs = '';
            $inputs.= wf_Selector('chmodifier', $this->attrModifiers, __('Users'), $attributeData['modifier'], false) . ' ';
            $inputs.= wf_Selector('chscenario', $this->scenarios, __('Scenario'), $attributeData['scenario'], false) . ' ';
            $inputs.= wf_TextInput('chattribute', __('Attribute'), $attributeData['attribute'], false, 20) . ' ';
            $inputs.= wf_Selector('choperator', $this->operators, __('Operator'), $attributeData['operator'], false) . ' ';
            $currentContent= htmlspecialchars($attributeData['content']);
            $inputs.= wf_TextInput('chcontent', __('Value'), $currentContent, false, 20) . ' ';
            $inputs.= wf_HiddenInput('chattributenasid', $nasId);
            $inputs.= wf_HiddenInput('chattributeid', $attributeId);
            $inputs.= wf_Submit(__('Save'));
            //form assembly
            $result.=wf_Form(self::URL_ME . '&editnasoptions=' . $nasId, 'POST', $inputs, 'glamour');
        } else {
            $result.=$this->messages->getStyledMessage(__('Something went wrong') . ': EX_ATTRIBUTEID_NOT_EXIST', 'error');
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
                if (wf_CheckPost(array('newscenario', 'newattribute', 'newoperator', 'newcontent', 'newmodifier'))) {
                    $newScenario = $_POST['newscenario'];
                    $newScenario_f = mysql_real_escape_string($newScenario);
                    $newModifier = $_POST['newmodifier'];
                    $newModifier_f = mysql_real_escape_string($newModifier);
                    $newAttribute = $_POST['newattribute'];
                    $newAttribute_f = mysql_real_escape_string($newAttribute);
                    $newOperator = $_POST['newoperator'];
                    $newOperator_f = mysql_real_escape_string($newOperator);
                    $newContent = $_POST['newcontent'];
                    $newContent_f = mysql_real_escape_string($newContent);


                    $query = "INSERT INTO `" . self::NAS_ATTRIBUTES . "` (`id`,`nasid`,`scenario`,`modifier`,`attribute`,`operator`,`content`) VALUES "
                            . "(NULL,'" . $nasId . "','" . $newScenario_f . "','" . $newModifier_f . "','" . $newAttribute_f . "','" . $newOperator_f . "','" . $newContent_f . "');";
                    nr_query($query);
                    $newId = simple_get_lastid(self::NAS_ATTRIBUTES);
                    log_register('MULTIGEN NAS [' . $nasId . '] CREATE ATTRIBUTE `' . $newAttribute . '` ID [' . $newId . ']');
                }
            } else {
                $result.=__('Something went wrong') . ': ' . __('NAS not exists');
            }
        }
        return ($result);
    }

    /**
     * Creates new NAS attribute preset
     * 
     * 
     * @return void/string on error
     */
    public function saveNasAttribute() {
        $result = '';
        if (wf_CheckPost(array('chattributenasid'))) {
            $nasId = vf($_POST['chattributenasid'], 3);
            if (isset($this->allNas[$nasId])) {
                if (wf_CheckPost(array('chscenario', 'chattribute', 'choperator', 'chcontent', 'chmodifier'))) {
                    $attributeId = vf($_POST['chattributeid'], 3);
                    if (isset($this->nasAttributes[$attributeId])) {
                        $where = "WHERE `id`='" . $attributeId . "';";
                        simple_update_field(self::NAS_ATTRIBUTES, 'scenario', $_POST['chscenario'], $where);
                        simple_update_field(self::NAS_ATTRIBUTES, 'modifier', $_POST['chmodifier'], $where);
                        simple_update_field(self::NAS_ATTRIBUTES, 'attribute', $_POST['chattribute'], $where);
                        simple_update_field(self::NAS_ATTRIBUTES, 'operator', $_POST['choperator'], $where);
                        simple_update_field(self::NAS_ATTRIBUTES, 'content', $_POST['chcontent'], $where);
                        log_register('MULTIGEN NAS [' . $nasId . '] CHANGE ATTRIBUTE [' . $attributeId . ']');
                    } else {
                        $result.=__('Something went wrong') . ': EX_ATTRIBUTE_NOT_EXIST';
                    }
                }
            } else {
                $result.=__('Something went wrong') . ': ' . __('NAS not exists');
            }
        }
        return ($result);
    }

    /**
     * Creates user state if its not exists
     * 
     * @param string $login
     * @param int $state
     * 
     * @return void
     */
    protected function createUserState($login, $state) {
        $login = mysql_real_escape_string($login);
        $state = mysql_real_escape_string($state);
        $query = "INSERT INTO `" . self::USER_STATES . "` (`id`,`login`,`state`) VALUES ";
        $query.="(NULL,'" . $login . "'," . $state . ");";
        nr_query($query);
    }

    /**
     * Deletes some user state from states table
     * 
     * @param string $login
     * 
     * @return void
     */
    protected function deleteUserState($login) {
        $login = mysql_real_escape_string($login);
        $query = "DELETE FROM `" . self::USER_STATES . "` WHERE `login`='" . $login . "';";
        nr_query($query);
    }

    /**
     * Changes user state in database
     * 
     * @param string $login
     * @param int $state
     * 
     * @return void
     */
    protected function changeUserState($login, $state) {
        $where = "WHERE `login`='" . $login . "'";
        simple_update_field(self::USER_STATES, 'state', $state, $where);
    }

    /**
     * Saves user states into database if something changed
     * 
     * @return void
     */
    protected function saveUserStates() {
        if (!empty($this->userStates)) {
            foreach ($this->userStates as $login => $state) {
                if ($state['changed'] == 3) {
                    //new user state appeared
                    if ($state != '') {
                        $this->createUserState($login, $state['current']);
                    }
                } else {
                    //user state changed
                    if ($state['current'] != $state['previous']) {
                        $this->changeUserState($login, $state['current']);
                    }
                }
            }
        }
    }

    /**
     * Checks is user active or not
     * 
     * @param string $userLogin
     * 
     * @return bool
     */
    protected function isUserActive($userLogin) {
        $result = false;
        if (isset($this->allUserData[$userLogin])) {
            //not existing users is inactive by default
            if ($this->accurateUserActivity) {
                //full checks for users activity, may be optional in future
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
                //just check financial data
                $result = ($this->allUserData[$userLogin]['Cash'] >= '-' . $this->allUserData[$userLogin]['Credit']) ? true : false;
            }
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
     * Stores performance timing data for future stats
     * 
     * @return void
     */
    public function writePerformanceTimers($key) {
        if (isset($this->perfStats[$key])) {
            $this->perfStats[$key] = microtime(true) - $this->perfStats[$key];
        } else {
            $this->perfStats[$key] = microtime(true);
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
     * @param bool $caps
     * 
     * @return string
     */
    protected function transformMacMinused($mac, $caps = false) {
        $result = str_replace(':', '-', $mac);
        if ($caps) {
            $result = strtoupper($result);
        }
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
     * Returns current user speeds including personal override in Kbit/s
     * 
     * @param string $userLogin
     * 
     * @return array as speeddown/speedup=>values
     */
    protected function getUserSpeeds($userLogin) {
        $result = array('speeddown' => 0, 'speedup' => 0);
        if (isset($this->allUserData[$userLogin])) {
            $userTariff = $this->allUserData[$userLogin]['Tariff'];
            if (isset($this->tariffSpeeds[$userTariff])) {
                //basic tariff speed
                $result = array('speeddown' => $this->tariffSpeeds[$userTariff]['speeddown'], 'speedup' => $this->tariffSpeeds[$userTariff]['speedup']);
            }
            if (isset($this->userSpeedOverrides[$userLogin])) {
                //personal speed overrides
                $result = array('speeddown' => $this->userSpeedOverrides[$userLogin], 'speedup' => $this->userSpeedOverrides[$userLogin]);
            }
        }
        return ($result);
    }

    /**
     * Returns speed transformed from kbit/s to bit/s by some offset
     * 
     * @param int $speed
     * @param int $offset
     * 
     * @return int
     */
    protected function transformSpeedBits($speed, $offset = 1024) {
        $result = $speed * $offset;

        return ($result);
    }

    /**
     * Returns attribute templates value with replaced macro
     * 
     * @param string $userLogin
     * @param string $userName
     * @param int    $nasId
     * @param string $template
     * 
     * @return string
     */
    public function getAttributeValue($userLogin, $userName, $nasId, $template) {
        if (strpos($template, '{') !== false) {
            //skipping templates with no macro inside
            if (isset($this->allUserData[$userLogin])) {
                if (strpos($template, '{IP}') !== false) {
                    $template = str_replace('{IP}', $this->allUserData[$userLogin]['ip'], $template);
                }

                if (strpos($template, '{MAC}') !== false) {
                    $template = str_replace('{MAC}', $this->allUserData[$userLogin]['mac'], $template);
                }

                if (strpos($template, '{MACFDL}') !== false) {
                    $template = str_replace('{MACFDL}', $this->transformMacDotted($this->allUserData[$userLogin]['mac']), $template);
                }

                if (strpos($template, '{MACFML}') !== false) {
                    $template = str_replace('{MACFML}', str_replace('.', '-', $this->transformMacDotted($this->allUserData[$userLogin]['mac'])), $template);
                }

                if (strpos($template, '{MACTMU}') !== false) {
                    $template = str_replace('{MACTMU}', $this->transformMacMinused($this->allUserData[$userLogin]['mac'], true), $template);
                }

                if (strpos($template, '{MACTML}') !== false) {
                    $template = str_replace('{MACTML}', $this->transformMacMinused($this->allUserData[$userLogin]['mac'], false), $template);
                }

                if (strpos($template, '{LOGIN}') !== false) {
                    $template = str_replace('{LOGIN}', $userLogin, $template);
                }

                if (strpos($template, '{USERNAME}') !== false) {
                    $template = str_replace('{USERNAME}', $userName, $template);
                }

                if (strpos($template, '{PASSWORD}') !== false) {
                    $template = str_replace('{PASSWORD}', $this->allUserData[$userLogin]['Password'], $template);
                }

                if (strpos($template, '{TARIFF}') !== false) {
                    $template = str_replace('{TARIFF}', $this->allUserData[$userLogin]['Tariff'], $template);
                }

                if (strpos($template, '{NETID}') !== false) {
                    $template = str_replace('{NETID}', $this->nethostsNetworks[$this->allUserData[$userLogin]['ip']], $template);
                }

                if (strpos($template, '{NETADDR}') !== false) {
                    $netDesc = $this->allNetworks[$this->nethostsNetworks[$this->allUserData[$userLogin]['ip']]]['desc'];
                    $netDesc = $this->parseNetworkDesc($netDesc);
                    $netAddr = $netDesc['addr'];
                    $template = str_replace('{NETADDR}', $netAddr, $template);
                }

                if (strpos($template, '{NETCIDR}') !== false) {
                    $netDesc = $this->allNetworks[$this->nethostsNetworks[$this->allUserData[$userLogin]['ip']]]['desc'];
                    $netDesc = $this->parseNetworkDesc($netDesc);
                    $netCidr = $netDesc['cidr'];
                    $template = str_replace('{NETCIDR}', $netCidr, $template);
                }

                if (strpos($template, '{NETSTART}') !== false) {
                    $template = str_replace('{NETSTART}', $this->allNetworks[$this->nethostsNetworks[$this->allUserData[$userLogin]['ip']]]['startip'], $template);
                }

                if (strpos($template, '{NETEND}') !== false) {
                    $template = str_replace('{NETEND}', $this->allNetworks[$this->nethostsNetworks[$this->allUserData[$userLogin]['ip']]]['endip'], $template);
                }

                if (strpos($template, '{NETDESC}') !== false) {
                    $template = str_replace('{NETDESC}', $this->allNetworks[$this->nethostsNetworks[$this->allUserData[$userLogin]['ip']]]['desc'], $template);
                }

                if (strpos($template, '{NETMASK}') !== false) {
                    $netDesc = $this->allNetworks[$this->nethostsNetworks[$this->allUserData[$userLogin]['ip']]]['desc'];
                    $netDesc = $this->parseNetworkDesc($netDesc);
                    $netCidr = $netDesc['cidr'];
                    $netMask = $this->transformCidrtoMask($netCidr);
                    $template = str_replace('{NETMASK}', $netMask, $template);
                }

                if (strpos($template, '{SPEEDDOWN}') !== false) {
                    $userSpeeds = $this->getUserSpeeds($userLogin);
                    $speedDown = $userSpeeds['speeddown'];
                    $template = str_replace('{SPEEDDOWN}', $speedDown, $template);
                }

                if (strpos($template, '{SPEEDUP}') !== false) {
                    $userSpeeds = $this->getUserSpeeds($userLogin);
                    $speedUp = $userSpeeds['speedup'];
                    $template = str_replace('{SPEEDUP}', $speedUp, $template);
                }

                if (strpos($template, '{SPEEDDOWNB}') !== false) {
                    $userSpeeds = $this->getUserSpeeds($userLogin);
                    $speedDown = $this->transformSpeedBits($userSpeeds['speeddown'], 1024);
                    $template = str_replace('{SPEEDDOWNB}', $speedDown, $template);
                }

                if (strpos($template, '{SPEEDUPB}') !== false) {
                    $userSpeeds = $this->getUserSpeeds($userLogin);
                    $speedUp = $this->transformSpeedBits($userSpeeds['speedup'], 1024);
                    $template = str_replace('{SPEEDUPB}', $speedUp, $template);
                }

                if (strpos($template, '{SPEEDDOWNBD}') !== false) {
                    $userSpeeds = $this->getUserSpeeds($userLogin);
                    $speedDown = $this->transformSpeedBits($userSpeeds['speeddown'], 1000);
                    $template = str_replace('{SPEEDDOWNBD}', $speedDown, $template);
                }

                if (strpos($template, '{SPEEDUPBD}') !== false) {
                    $userSpeeds = $this->getUserSpeeds($userLogin);
                    $speedUp = $this->transformSpeedBits($userSpeeds['speedup'], 1000);
                    $template = str_replace('{SPEEDUPBD}', $speedUp, $template);
                }

                if (strpos($template, '{SPEEDDOWNBC}') !== false) {
                    $userSpeeds = $this->getUserSpeeds($userLogin);
                    $speedDown = $this->transformSpeedBits($userSpeeds['speeddown'], 1024) / 8;
                    $template = str_replace('{SPEEDDOWNBC}', $speedDown, $template);
                }

                if (strpos($template, '{SPEEDUPBC}') !== false) {
                    $userSpeeds = $this->getUserSpeeds($userLogin);
                    $speedUp = $this->transformSpeedBits($userSpeeds['speedup'], 1024) / 8;
                    $template = str_replace('{SPEEDUPBC}', $speedUp, $template);
                }

                if (strpos($template, '{SPEEDMRL}') !== false) {
                    $userSpeeds = $this->getUserSpeeds($userLogin);
                    $template = str_replace('{SPEEDMRL}', $userSpeeds['speedup'] . 'k/' . $userSpeeds['speeddown'] . 'k', $template);
                }

                if (strpos($template, '{USERSWITCHIP}') !== false) {
                    $userSwitchId = @$this->userSwitchAssigns[$userLogin]['switchid'];
                    $switchData = @$this->allSwitches[$userSwitchId];
                    $switchIp = @$switchData['ip'];
                    $template = str_replace('{USERSWITCHIP}', $switchIp, $template);
                }

                if (strpos($template, '{USERSWITCHPORT}') !== false) {
                    $userSwitchPort = @$this->userSwitchAssigns[$userLogin]['port'];
                    $template = str_replace('{USERSWITCHPORT}', $userSwitchPort, $template);
                }

                if (strpos($template, '{USERSWITCHMAC}') !== false) {
                    $userSwitchId = @$this->userSwitchAssigns[$userLogin]['switchid'];
                    $switchData = @$this->allSwitches[$userSwitchId];
                    $switchMac = @$switchData['swid'];
                    $template = str_replace('{USERSWITCHMAC}', $switchMac, $template);
                }
            }

            if (isset($this->allNas[$nasId])) {
                $nasIp = $this->allNas[$nasId]['nasip'];
                if (strpos($template, '{NASIP}') !== false) {
                    $template = str_replace('{NASIP}', $nasIp, $template);
                }

                if (strpos($template, '{NASSECRET}') !== false) {
                    $nasSecret = substr(md5(ip2int($nasIp)), 0, 12);
                    $template = str_replace('{NASSECRET}', $nasSecret, $template);
                }

                if (strpos($template, '{NASPORT}') !== false) {
                    $nasPort=  $this->nasOptions[$nasId]['port'];
                    $template = str_replace('{NASPORT}', $nasPort, $template);
                }
            }

            if (strpos($template, '{STATE}') !== false) {
                $template = str_replace('{STATE}', $this->getUserStateString($userLogin), $template);
            }

            if (strpos($template, '{RADCLIENT}') !== false) {
                $template = str_replace('{RADCLIENT}', $this->radclienPath, $template);
            }

            if (strpos($template, '{SUDO}') !== false) {
                $template = str_replace('{SUDO}', $this->sudoPath, $template);
            }

            if (strpos($template, '{PRINTF}') !== false) {
                $template = str_replace('{PRINTF}', $this->printfPath, $template);
            }
        }

        return ($template);
    }

    /**
     * Performs generation of user attributes if their NAS requires it.
     * 
     * @return void
     */
    public function generateNasAttributes() {
        $this->writePerformanceTimers('genstart');
        //loading huge amount of required data
        $this->loadHugeRegenData();
        $this->writePerformanceTimers('dataloaded');
        if (!empty($this->allUserData)) {
            foreach ($this->allUserData as $io => $eachUser) {
                $userLogin = $eachUser['login'];
                //user actual state right now
                $userRealState = $this->isUserActive($userLogin);
                //getting previous user state, setting current state
                if (isset($this->userStates[$userLogin])) {
                    $userPreviousState = $this->userStates[$userLogin]['previous'];
                    $this->userStates[$userLogin]['current'] = ($userRealState) ? 1 : 0;
                } else {
                    $userPreviousState = 3;
                    $this->userStates[$userLogin]['previous'] = $userPreviousState;
                    $this->userStates[$userLogin]['current'] = ($userRealState) ? 1 : 0;
                    $this->userStates[$userLogin]['changed'] = 3;
                }

                if (isset($this->userNases[$userLogin])) {
                    $userNases = $this->userNases[$userLogin];
                    //for debug only
                    //$userNases = array(1 => 1);
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
                                case 'macju':
                                    $userName = $this->transformMacDotted($eachUser['mac']);
                                    break;
                            }

                            if (!empty($nasOptions)) {
                                $nasAttributes = $this->getNasAttributes($eachNasId);
                                $onlyActive = $nasOptions['onlyactive'];

                                //Processing NAS attributes only for active users
                                if ($onlyActive == 1) {
                                    if (!empty($nasAttributes)) {
                                        foreach ($nasAttributes as $eachAttributeId => $eachAttributeData) {
                                            $scenario = $eachAttributeData['scenario'];
                                            $attribute = $eachAttributeData['attribute'];
                                            $op = $eachAttributeData['operator'];
                                            $template = $eachAttributeData['content'];
                                            if ($userRealState) {
                                                $value = $this->getAttributeValue($userLogin, $userName, $eachNasId, $template);
                                                $attributeCheck = $this->checkScenarioAttribute($scenario, $userLogin, $userName, $attribute, $op, $value);
                                                if ($attributeCheck == -2) {
                                                    //dropping already changed attribute from this scenario
                                                    $this->deleteScenarioAttribute($scenario, $userLogin, $userName, $attribute);
                                                    //setting current user state as changed
                                                    $this->userStates[$userLogin]['changed'] = -2;
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
                                //Processing NAS attributes for all users using attributes modifiers
                                if ($onlyActive == 0) {
                                    if (!empty($nasAttributes)) {
                                        foreach ($nasAttributes as $eachAttributeId => $eachAttributeData) {
                                            $scenario = $eachAttributeData['scenario'];
                                            $modifier = $eachAttributeData['modifier'];
                                            $attribute = $eachAttributeData['attribute'];
                                            $op = $eachAttributeData['operator'];
                                            $template = $eachAttributeData['content'];
                                            //this attribute template is actual for all users
                                            if ($modifier == 'all') {
                                                $value = $this->getAttributeValue($userLogin, $userName, $eachNasId, $template);
                                                $attributeCheck = $this->checkScenarioAttribute($scenario, $userLogin, $userName, $attribute, $op, $value);
                                                if ($attributeCheck == -2) {
                                                    //dropping already changed attribute from this scenario
                                                    $this->deleteScenarioAttribute($scenario, $userLogin, $userName, $attribute);
                                                    //setting current user state as changed
                                                    $this->userStates[$userLogin]['changed'] = -2;
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
                                            }
                                            //this attribute is actual only for active users
                                            if ($modifier == 'active') {
                                                if ($userRealState) {
                                                    $value = $this->getAttributeValue($userLogin, $userName, $eachNasId, $template);
                                                    $attributeCheck = $this->checkScenarioAttribute($scenario, $userLogin, $userName, $attribute, $op, $value);
                                                    if ($attributeCheck == -2) {
                                                        //dropping already changed attribute from this scenario
                                                        $this->deleteScenarioAttribute($scenario, $userLogin, $userName, $attribute);
                                                        //setting current user state as changed
                                                        $this->userStates[$userLogin]['changed'] = -2;
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
                                                    //flush some not-active user attribute if required
                                                    $this->flushBuriedUser($eachNasId, $scenario, $userLogin, $userName, $attribute);
                                                }
                                            }
                                            //this attribute is actual only for inactive users
                                            if ($modifier == 'inactive') {
                                                if (!$userRealState) {
                                                    $value = $this->getAttributeValue($userLogin, $userName, $eachNasId, $template);
                                                    $attributeCheck = $this->checkScenarioAttribute($scenario, $userLogin, $userName, $attribute, $op, $value);
                                                    if ($attributeCheck == -2) {
                                                        //dropping already changed attribute from this scenario
                                                        $this->deleteScenarioAttribute($scenario, $userLogin, $userName, $attribute);
                                                        //setting current user state as changed
                                                        $this->userStates[$userLogin]['changed'] = -2;
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
                                                    //flush some active user attribute if required
                                                    $this->flushBuriedUser($eachNasId, $scenario, $userLogin, $userName, $attribute);
                                                }
                                            }
                                        }
                                    }
                                }

                                //processing Per-NAS services
                                if ($nasOptions['service'] != 'none') {
                                    $nasServices = @$this->services[$eachNasId];
                                    if (!empty($nasServices)) {
                                        if (strpos($nasOptions['service'], 'pod') !== false) {
                                            if (!empty($nasServices['pod'])) {
                                                if (($userPreviousState == 1) AND ( $this->userStates[$userLogin]['current'] == 0)) {
                                                    $newPodContent = $this->getAttributeValue($userLogin, $userName, $eachNasId, $nasServices['pod']) . "\n";
                                                    $this->savePodQueue($newPodContent);
                                                }
                                            }
                                        }

                                        if (strpos($nasOptions['service'], 'coa') !== false) {
                                            //sending some disconnect
                                            if (!empty($nasServices['coadisconnect'])) {
                                                if (($userPreviousState == 1) AND ( $this->userStates[$userLogin]['current'] == 0)) {
                                                    //user out of money
                                                    $newCoADisconnectContent = $this->getAttributeValue($userLogin, $userName, $eachNasId, $nasServices['coadisconnect']) . "\n";
                                                    $this->saveCoaQueue($newCoADisconnectContent);
                                                }
                                            }
                                            //and connect services
                                            if (!empty($nasServices['coaconnect'])) {
                                                if (($userPreviousState == 0) AND ( $this->userStates[$userLogin]['current'] == 1)) {
                                                    //user now restores his activity
                                                    $newCoAConnectContent = $this->getAttributeValue($userLogin, $userName, $eachNasId, $nasServices['coaconnect']) . "\n";
                                                    $this->saveCoaQueue($newCoAConnectContent);
                                                }
                                            }

                                            //emulating reset action if something changed in user attributes
                                            if ((!empty($nasServices['coadisconnect'])) AND ( !empty($nasServices['coaconnect']))) {
                                                if (($this->userStates[$userLogin]['changed'] == -2) AND ( $this->userStates[$userLogin]['current'] == 1) AND ( $this->userStates[$userLogin]['previous'] == 1)) {
                                                    $newCoADisconnectContent = $this->getAttributeValue($userLogin, $userName, $eachNasId, $nasServices['coadisconnect']) . "\n";
                                                    $this->saveCoaQueue($newCoADisconnectContent);
                                                    $newCoAConnectContent = $this->getAttributeValue($userLogin, $userName, $eachNasId, $nasServices['coaconnect']) . "\n";
                                                    $this->saveCoaQueue($newCoAConnectContent);
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

            //run PoD queue
            if (isset($this->runServices['pod'])) {
                $this->runPodQueue();
            }


            //run CoA queue
            if (isset($this->runServices['coa'])) {
                $this->runCoaQueue();
            }

            //saving user states
            $this->saveUserStates();
        }

        $this->writePerformanceTimers('genend');
    }

    /**
     * Saves data to PoD queue for furtner run
     * 
     * @param string $data
     * 
     * @return void
     */
    protected function savePodQueue($data) {
        $this->runServices['pod'] = 1;
        file_put_contents(self::POD_PATH, $data, FILE_APPEND);
        $this->logEvent('POD_QUEUE_ADD: ' . trim($data), 3); //Omae wa mou shindeiru
    }

    /**
     * Saves data to CoA queue for furtner run
     * 
     * @param string $data
     * 
     * @return void
     */
    protected function saveCoaQueue($data) {
        $this->runServices['coa'] = 1;
        file_put_contents(self::COA_PATH, $data, FILE_APPEND);
        $this->logEvent('COA_QUEUE_ADD: ' . trim($data), 3);
    }

    /**
     * Runs PoD queue if not empty and flushes after it
     * 
     * @return void
     */
    protected function runPodQueue() {
        if (file_exists(self::POD_PATH)) {
            chmod(self::POD_PATH, 0755);
            $podQueueCleanup = $this->echoPath . ' "" > ' . getcwd() . '/' . self::POD_PATH . "\n";
            $this->savePodQueue($podQueueCleanup);
            if ($this->logging >= 4) {
                shell_exec(self::POD_PATH . ' >' . self::LOG_PATH . ' 2> ' . self::LOG_PATH);
            } else {
                shell_exec(self::POD_PATH . ' >/dev/null 2>/dev/null &');
            }
            $this->logEvent('POD_QUEUE_RUN', 3); //nani?
        }
    }

    /**
     * Runs CoA queue if not empty and flushes after it
     * 
     * @return void
     */
    protected function runCoaQueue() {
        if (file_exists(self::COA_PATH)) {
            chmod(self::COA_PATH, 0755);
            $coaQueueCleanup = $this->echoPath . ' "" > ' . getcwd() . '/' . self::COA_PATH . "\n";
            $this->saveCoaQueue($coaQueueCleanup);
            if ($this->logging >= 4) {
                shell_exec(self::COA_PATH . ' >' . self::LOG_PATH . ' 2> ' . self::LOG_PATH);
            } else {
                shell_exec(self::COA_PATH . ' >/dev/null 2>/dev/null &');
            }
            $this->logEvent('COA_QUEUE_RUN', 3);
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
        $totalAttributeCount = 0;
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
                                $totalAttributeCount+=$eachCount;
                                $result.=$this->messages->getStyledMessage($stateName . ' ' . __('for scenario') . ' ' . $scenarioName . ': ' . $eachCount, $stateStyle);
                            }
                        }
                    }
                }
            }
        }

        if (!empty($this->perfStats)) {
            $timeStats = '';
            $perfStats = '';

            $dataLoadingTime = $this->perfStats['dataloaded'] - $this->perfStats['genstart'];
            $totalTime = $this->perfStats['genend'] - $this->perfStats['genstart'];
            $timeStats.= __('Total time spent') . ': ' . round($totalTime, 2) . ' ' . __('sec.') . ' ';
            $timeStats.= __('Data loading time') . ': ' . round($dataLoadingTime, 2) . ' ' . __('sec.') . ' ';
            $timeStats.= __('Attributes processing time') . ': ' . round(($totalTime - $dataLoadingTime), 2) . ' ' . __('sec.') . ' ';
            $timeStats.=__('Memory used') . ': ~' . stg_convert_size(memory_get_usage(true));

            $perfStats.= __('Total attributes processed') . ': ' . $totalAttributeCount . ' ';
            if ($totalTime > 0) {
                //preventing zero divisions
                $perfStats.=__('Performance') . ': ' . round($totalAttributeCount / ($totalTime - $dataLoadingTime), 2) . ' ' . __('attributes/sec');
                $perfStats.=' ( ' . round($totalAttributeCount / ($totalTime), 2) . ' ' . ' ' . __('brutto') . ')';
            } else {
                $perfStats.=__('Performance') . ': ' . wf_tag('b') . __('Black magic') . wf_tag('b', true);
            }

            $result.=$this->messages->getStyledMessage($timeStats, 'success');
            $result.=$this->messages->getStyledMessage($perfStats, 'success');
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
            $result.= wf_AjaxLink(self::URL_ME . '&ajnasregen=true&editnasoptions=' . $nasId, wf_img('skins/refresh.gif') . ' ' . __('Base regeneration'), 'nascontrolajaxcontainer', false, 'ubButton');
            $result.= wf_AjaxLink(self::URL_ME . '&ajscenarioflush=true&editnasoptions=' . $nasId, wf_img('skins/skull.png') . ' ' . __('Flush all attributes in all scenarios'), 'nascontrolajaxcontainer', false, 'ubButton');
            if ($this->nasOptions[$nasId]['service'] != 'none') {
                $result.= wf_modalAuto(web_icon_extended() . ' ' . __('Service'), __('Service'), $this->renderNasServicesEditForm($nasId), 'ubButton');
            }
            $result.=wf_AjaxContainer('nascontrolajaxcontainer');
        }
        return ($result);
    }

    /**
     * Returns NAS services editing form
     * 
     * @param int $nasId
     * 
     * @return string
     */
    protected function renderNasServicesEditForm($nasId) {
        $result = '';
        $nasId = vf($nasId, 3);
        if ($this->nasHaveOptions($nasId)) {
            $nasOptions = $this->nasOptions[$nasId];
            if ($nasOptions['service'] != 'none') {
                $nasServices = @$this->services[$nasId];
                $inputs = wf_HiddenInput('newnasservicesid', $nasId);
                $inputs.=__('PoD') . wf_tag('br');
                $inputs.= wf_TextArea('newnasservicepod', '', @$nasServices['pod'], true, '90x2');
                $inputs.=__('CoA Connect') . wf_tag('br');
                $inputs.= wf_TextArea('newnasservicecoaconnect', '', @$nasServices['coaconnect'], true, '90x2');
                $inputs.=__('CoA Disconnect') . wf_tag('br');
                $inputs.= wf_TextArea('newnasservicecoadisconnect', '', @$nasServices['coadisconnect'], true, '90x2');
                $inputs.= wf_Submit(__('Save'));
                $result.= wf_Form(self::URL_ME . '&editnasoptions=' . $nasId, 'POST', $inputs, 'glamour');
            }
        }
        return ($result);
    }

    /**
     * Renders controls for acct date search
     * 
     * @return string
     */
    public function renderDateSerachControls() {
        $result = '';
        $curTime = time();
        $dayAgo = $curTime - 86400;
        $dayAgo = date("Y-m-d", $dayAgo);
        $dayTomorrow = $curTime + 86400;
        $dayTomorrow = date("Y-m-d", $dayTomorrow);
        $preDateFrom = (wf_CheckPost(array('datefrom'))) ? $_POST['datefrom'] : $dayAgo;
        $preDateTo = (wf_CheckPost(array('dateto'))) ? $_POST['dateto'] : $dayTomorrow;
        $unfinishedFlag = (wf_CheckPost(array('showunfinished'))) ? true : false;

        $inputs = wf_DatePickerPreset('datefrom', $preDateFrom, false);
        $inputs.= wf_DatePickerPreset('dateto', $preDateTo, false);
        $inputs.= wf_CheckInput('showunfinished', __('Show unfinished'), false, $unfinishedFlag);
        $inputs.= wf_Submit(__('Show'));
        $result = wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Renders preloaded accounting data into JSON
     * 
     * @return void
     */
    public function renderAcctStatsAjlist() {
        $result = '';
        $totalCount = 0;
        $json = new wf_JqDtHelper();

        $this->loadAcctData();
        if (!empty($this->userAcctData)) {
            foreach ($this->userAcctData as $io => $each) {
                $fc = '';
                $efc = wf_tag('font', true);
                if (!empty($each['acctstoptime'])) {
                    $startTime = strtotime($each['acctstarttime']);
                    $endTime = strtotime($each['acctstoptime']);
                    $timeOffsetRaw = $endTime - $startTime;
                    $timeOffset = zb_formatTime($timeOffsetRaw);
                } else {
                    $timeOffset = '';
                    $timeOffsetRaw = '';
                }

                //some coloring
                if (empty($each['acctstoptime'])) {
                    $fc = wf_tag('font', false, '', 'color="#ff6600"');
                } else {
                    $fc = wf_tag('font', false, '', 'color="#005304"');
                }


                $data[] = $fc . $each['acctsessionid'] . $efc;
                $data[] = $each['username'];
                $data[] = $each['nasipaddress'];
                $data[] = $each['nasportid'];
                $data[] = $each['acctstarttime'];
                $data[] = $each['acctstoptime'];
                $data[] = stg_convert_size($each['acctinputoctets']);
                $data[] = stg_convert_size($each['acctoutputoctets']);
                $data[] = $each['framedipaddress'];
                $data[] = $each['acctterminatecause'];
                $data[] = $timeOffset;


                $json->addRow($data);
                unset($data);
            }
        }

        $json->getJson();
    }

    /**
     * Renders multigen acoounting stats container
     * 
     * @return string
     */
    public function renderAcctStatsContainer() {
        $result = '';
        $columns = array('acctsessionid', 'username', 'nasipaddress', 'nasportid', 'acctstarttime', 'acctstoptime', 'acctinputoctets', 'acctoutputoctets', 'framedipaddress', 'acctterminatecause', 'Time');

        if (wf_CheckPost(array('datefrom', 'dateto'))) {
            $searchDateFrom = mysql_real_escape_string($_POST['datefrom']);
            $searchDateTo = mysql_real_escape_string($_POST['dateto']);
        } else {
            $curTime = time();
            $dayAgo = $curTime - 86400;
            $dayAgo = date("Y-m-d", $dayAgo);
            $dayTomorrow = $curTime + 86400;
            $dayTomorrow = date("Y-m-d", $dayTomorrow);
            $searchDateFrom = $dayAgo;
            $searchDateTo = $dayTomorrow;
        }

        if (wf_CheckPost(array('showunfinished'))) {
            $unfinishedFlag = "&showunfinished=true";
        } else {
            $unfinishedFlag = '';
        }

        $ajUrl = self::URL_ME . '&ajacct=true&datefrom=' . $searchDateFrom . '&dateto=' . $searchDateTo . $unfinishedFlag;
        $options = '"order": [[ 0, "desc" ]]';
        $result = wf_JqDtLoader($columns, $ajUrl, false, __('sessions'), 50, $options);
        return ($result);
    }

    /**
     * Renders multigen logs control
     * 
     * @global object $ubillingConfig
     * 
     * @return string
     */
    function renderLogControl() {
        global $ubillingConfig;
        $result = '';
        $logData = array();
        $renderData = '';
        $rows = '';
        $recordsLimit = 200;
        $prevTime = '';
        $curTimeTime = '';
        $diffTime = '';

        if (file_exists(self::LOG_PATH)) {
            $billCfg = $ubillingConfig->getBilling();
            $tailCmd = $billCfg['TAIL'];
            $runCmd = $tailCmd . ' -n ' . $recordsLimit . ' ' . self::LOG_PATH;
            $rawResult = shell_exec($runCmd);
            $renderData.= __('Showing') . ' ' . $recordsLimit . ' ' . __('last events') . wf_tag('br');
            $renderData.= wf_Link(self::URL_ME . '&dlmultigenlog=true', wf_img('skins/icon_download.png', __('Download')) . ' ' . __('Download full log'), true);

            if (!empty($rawResult)) {
                $logData = explodeRows($rawResult);
                $logData = array_reverse($logData); //from new to old list
                if (!empty($logData)) {


                    $cells = wf_TableCell(__('Date'));
                    $cells.= wf_TableCell(__('Event'));
                    $rows.=wf_TableRow($cells, 'row1');

                    foreach ($logData as $io => $each) {
                        if (!empty($each)) {

                            $eachEntry = explode(' ', $each);
                            $cells = wf_TableCell($eachEntry[0] . ' ' . $eachEntry[1]);
                            $cells.= wf_TableCell(str_replace(($eachEntry[0] . ' ' . $eachEntry[1]), '', $each));
                            $rows.=wf_TableRow($cells, 'row3');
                        }
                    }
                    $renderData.= wf_TableBody($rows, '100%', 0, 'sortable');
                }
            } else {
                $renderData.= $this->messages->getStyledMessage(__('Nothing found'), 'warning');
            }

            $result = wf_modal(wf_img('skins/log_icon_small.png', __('Attributes regeneration log')), __('Attributes regeneration log'), $renderData, '', '1280', '600');
        }
        return ($result);
    }

    /**
     * Performs downloading of log
     * 
     * @return void
     */
    public function logDownload() {
        if (file_exists(self::LOG_PATH)) {
            zb_DownloadFile(self::LOG_PATH);
        } else {
            show_error(__('Something went wrong') . ': EX_FILE_NOT_FOUND ' . self::LOG_PATH);
        }
    }

}

/**
 * Returns list of available free Juniper NASes
 * 
 * @return string
 */
function web_MultigenListClients() {
    $result = __('Nothing found');
    $query = "SELECT * from `" . MultiGen::CLIENTS . "` GROUP BY `nasname`";
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