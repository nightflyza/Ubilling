<?php

/**
 * Most awesome FreeRADIUS support implementation ever
 */
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
     * Contains available QinQ data
     *
     * @var array
     */
    protected $switchesQinQ = array();

    /**
     * Contains available users qinq bindings
     * 
     * @var array
     */
    protected $usersQinQ = array();

    /**
     * Contains available s-vlans
     * 
     * @var array
     */
    protected $allSvlan = array();

    /**
     * Contains all realms
     * 
     * @var array
     */
    protected $allRealms = array();

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
     * Contains all users with ext networks assign
     * 
     * @var array
     */
    protected $netExtUsers = array();

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
     * Contains additional fields from database acct table
     *
     * @var array
     */
    protected $acctFieldsAdditional = array();

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
     * Contains innodb usage/optimization flag from OPTION_INNO
     *
     * @var int
     */
    protected $inno = 0;

    /**
     * Contains unfinished acct flag from OPTION_UNFFLAG
     *
     * @var int
     */
    protected $unfinished = 0;

    /**
     * Contains default accounting display days from OPTION_DAYS
     *
     * @var int
     */
    protected $days = 1;

    /**
     * System caching object instance
     *
     * @var object
     */
    protected $cache = '';

    /**
     * Contains previous accounting traffic stats as login=>data
     *
     * @var array
     */
    protected $previousTraffic = array();

    /**
     * Contains ishimura archived year/month traffic stats for current month
     *
     * @var array
     */
    protected $trafficArchive = array();

    /**
     * Contains current accounting traffic stats as login=>data
     *
     * @var array
     */
    protected $currentTraffic = array();

    /**
     * Contains current stargazer users traffic stats as login=>data D0/U0
     *
     * @var array
     */
    protected $usersTraffic = array();

    /**
     * Contains users current balance cash amount as login=>cash
     *
     * @var array
     */
    protected $allUsersCash = array();

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
     * Ishimura enabled flag
     *
     * @var int
     */
    protected $ishimuraFlag = 0;

    /**
     * Contains default usernames caching timeout in seconds
     *
     * @var int
     */
    protected $usernamesCachingTimeout = 0;

    /**
     * Contains current multigen instance unique ID
     *
     * @var string
     */
    protected $instanceId = '';

    /**
     * stardust process manager instance
     *
     * @var object
     */
    protected $stardust = '';

    /**
     * Mea culpa protected instance
     * 
     * @var object
     */
    protected $meaCulpa = '';

    /**
     * Is mea culpa enabled flag?
     * 
     * @var bool
     */
    protected $meaCulpaFlag = false;

    /**
     * Contains basic module path
     */
    const URL_ME = '?module=multigen';

    /**
     * Contains default user profile link
     */
    const URL_PROFILE = '?module=userprofile&username=';

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
     * Default postauth table name
     */
    const NAS_POSTAUTH = 'mlg_postauth';

    /**
     * Default traffic aggregation table name
     */
    const NAS_TRAFFIC = 'mlg_traffic';

    /**
     * Default previous/current traffic stats table name
     */
    const NAS_ISHIMURA = 'mlg_ishimura';

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
     * Default inno-db performance fix option name
     */
    const OPTION_INNO = 'MULTIGEN_MAKE_INNODB_GREAT_AGAIN';

    /**
     * Default switches QinQ management option name
     */
    const OPTION_QINQ = 'QINQ_ENABLED';

    /**
     * Default user switchport assign option name
     */
    const OPTION_SWASSIGN = 'SWITCHPORT_IN_PROFILE';

    /**
     * Default universal QinQ management option name
     */
    const OPTION_UNIVERSALQINQ = 'UNIVERSAL_QINQ_ENABLED';

    /**
     * Default additional fields option
     */
    const OPTION_FIELDS = 'MULTIGEN_FIELDSACCT';

    /**
     * Default unfinished sessions flag option name
     */
    const OPTION_UNFFLAG = 'MULTIGEN_UNFACCT';

    /**
     * Default accounting days option name
     */
    const OPTION_DAYS = 'MULTIGEN_DAYSACCT';

    /**
     * Default ishimura mech enabling option name
     */
    const OPTION_ISHIMURA = 'ISHIMURA_ENABLED';

    /**
     * Usernames cache expiring timeout option name
     */
    const OPTION_USERNAMESTIMEOUT = 'MULTIGEN_UNTIMEOUT';

    /**
     * Extended networks option name
     */
    const OPTION_EXTNETS = 'NETWORKS_EXT';

    /**
     * Mea maxima culpa coinfig option name
     */
    const OPTION_CULPA = 'MEACULPA_ENABLED';

    /**
     * Default log path
     */
    const LOG_PATH = 'exports/multigen.log';

    /**
     * Contains default path to PoD scripts queue
     */
    const POD_PATH = 'exports/pod_queue_';

    /**
     * Contains default path to CoA scripts queue
     */
    const COA_PATH = 'exports/coa_queue_';

    /**
     * Default RemoteAPI lock name
     */
    const MULTIGEN_PID = 'MULTIGEN';

    /**
     * Creates new MultiGen instance
     * 
     * @return void
     */
    public function __construct() {
        $this->loadConfigs();
        $this->setOptions();
        $this->initMessages();
        $this->initStarDust();
        $this->initCache();
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
        $this->loadAllQinQ();
        $this->loadScenarios();
        $this->loadUserStates();
        $this->loadNetExtUsers();
        $this->loadMeaCulpa();
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

        if (isset($this->altCfg[self::OPTION_INNO])) {
            if ($this->altCfg[self::OPTION_INNO]) {
                $this->inno = $this->altCfg[self::OPTION_INNO];
            }
        }

        if (isset($this->altCfg[self::OPTION_UNFFLAG])) {
            if ($this->altCfg[self::OPTION_UNFFLAG]) {
                $this->unfinished = $this->altCfg[self::OPTION_UNFFLAG];
            }
        }

        if (isset($this->altCfg[self::OPTION_DAYS])) {
            if ($this->altCfg[self::OPTION_DAYS]) {
                $this->days = $this->altCfg[self::OPTION_DAYS];
            }
        }

        if (isset($this->altCfg[self::OPTION_ISHIMURA])) {
            if ($this->altCfg[self::OPTION_ISHIMURA]) {
                $this->ishimuraFlag = $this->altCfg[self::OPTION_ISHIMURA];
            }
        }

        if (isset($this->altCfg[self::OPTION_USERNAMESTIMEOUT])) {
            if ($this->altCfg[self::OPTION_USERNAMESTIMEOUT]) {
                $this->usernamesCachingTimeout = $this->altCfg[self::OPTION_USERNAMESTIMEOUT];
            }
        }

        $this->instanceId = 'MLG' . zb_rand_string(8);

        $this->usernameTypes = array(
            'login' => __('Login'),
            'ip' => __('IP'),
            'mac' => __('MAC') . ' ' . __('default'),
            'macup' => __('MAC') . ' ' . __('upper case'),
            'macju' => __('MAC') . ' ' . __('JunOS like')
        );

        //some additional username types
        if ((isset($this->altCfg[self::OPTION_SWASSIGN])) AND ( isset($this->altCfg[self::OPTION_QINQ]))) {
            if (($this->altCfg[self::OPTION_SWASSIGN]) AND ( $this->altCfg[self::OPTION_QINQ])) {
                $this->usernameTypes['qinq'] = __('QinQ') . ' ' . __('default');
                $this->usernameTypes['qinqju'] = __('QinQ') . ' ' . __('JunOS like');
            }
        }

        // quia peccavi nimis
        // cogitatione, verbo
        // opere et omissione
        if (isset($this->altCfg[self::OPTION_CULPA])) {
            if ($this->altCfg[self::OPTION_CULPA]) {
                $this->usernameTypes['meaculpa'] = __('Mea culpa');
                $this->meaCulpaFlag = true;
            }
        }


        $this->serviceTypes = array(
            'none' => __('No'),
            'coa' => __('CoA'),
            'pod' => __('PoD'),
            'podcoa' => __('PoD') . '+' . __('CoA')
        );

        $this->scenarios = array(
            'check' => 'check',
            'reply' => 'reply',
            'groupreply' => 'groupreply'
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

        if (isset($this->altCfg[self::OPTION_FIELDS])) {
            if ($this->altCfg[self::OPTION_FIELDS]) {
                $this->acctFieldsAdditional = explode(',', $this->altCfg[self::OPTION_FIELDS]);
            }
        }
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
     * Inits system caching object for further usage
     * 
     * @return void
     */
    protected function initCache() {
        $this->cache = new UbillingCache();
    }

    /**
     * Inits process manager
     * 
     * @return void
     */
    protected function initStarDust() {
        $this->stardust = new StarDust(self::MULTIGEN_PID);
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
     * Loads all existing users data from database
     * 
     * @return void
     */
    protected function loadUserCash() {
        $this->allUsersCash = zb_UserGetAllBalance();
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
        if (@$this->altCfg[self::OPTION_SWASSIGN]) {
            $query = "SELECT * from `switchportassign`";
            $all = simple_queryall($query);
            if (!empty($all)) {
                foreach ($all as $io => $each) {
                    $this->userSwitchAssigns[$each['login']] = $each;
                }
            }
        }
    }

    /**
     * Loads switches QinQ data from database
     * 
     * @return void
     */
    protected function loadAllQinQ() {
        if ((@$this->altCfg[self::OPTION_QINQ]) AND ( @$this->altCfg[self::OPTION_SWASSIGN])) {
            $qinq = new SwitchesQinQ();
            $this->switchesQinQ = $qinq->getAllQinQ();
        }
        if (@$this->altCfg[self::OPTION_UNIVERSALQINQ]) {
            $universalqinq = new UniversalQINQ();
            $this->usersQinQ = $universalqinq->getAll();
            $svlanObj = new VlanManagement();
            $this->allSvlan = $svlanObj->getAllSvlan();
            $this->allRealms = $svlanObj->getAllRealms();
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
     * Loads users extended networks data
     * 
     * @return void
     */
    protected function loadNetExtUsers() {
        if (isset($this->altCfg[self::OPTION_EXTNETS])) {
            if ($this->altCfg[self::OPTION_EXTNETS]) {
                $netExtUsers_q = "SELECT * from `netextpools` WHERE `login` <> '';";
                $rawNetExtUsers = simple_queryall($netExtUsers_q);
                if (!empty($rawNetExtUsers)) {
                    foreach ($rawNetExtUsers as $io => $each) {
                        $this->netExtUsers[$each['login']] = $each['pool'] . "/" . $each['netmask'];
                    }
                }
            }
        }
    }

    /**
     * Loads mea culpa instance for further usage
     * 
     * @return void
     */
    protected function loadMeaCulpa() {
        if ($this->meaCulpaFlag) {
            $this->meaCulpa = new MeaCulpa();
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
        if (!empty($this->acctFieldsAdditional)) {
            $fieldsList .= ', ' . implode(', ', $this->acctFieldsAdditional);
        }
        if (wf_CheckGet(array('datefrom', 'dateto'))) {
            $searchDateFrom = mysql_real_escape_string($_GET['datefrom']);
            $searchDateTo = mysql_real_escape_string($_GET['dateto']);
        } else {
            $curTime = time();
            $dayAgo = $curTime - (86400 * $this->days);
            $dayAgo = date("Y-m-d", $dayAgo);
            $dayTomorrow = $curTime + 86400;
            $dayTomorrow = date("Y-m-d", $dayTomorrow);
            $searchDateFrom = $dayAgo;
            $searchDateTo = $dayTomorrow;
        }

        if (wf_CheckGet(array('showunfinished'))) {
            $unfQueryfilter = "AND `acctstoptime` IS NULL ";
        } else {
            $unfQueryfilter = '';
        }

        if (wf_CheckGet(array('lastsessions'))) {
            $query = "SELECT * FROM `" . self::NAS_ACCT . "` GROUP BY `username` DESC ORDER BY `acctstarttime`;";
        } else {
            $query = "SELECT " . $fieldsList . " FROM `" . self::NAS_ACCT . "` WHERE `acctstarttime` BETWEEN '" . $searchDateFrom . "' AND '" . $searchDateTo . "'"
                    . " " . $unfQueryfilter . "  ORDER BY `radacctid` DESC ;";
        }

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
            $inputs .= wf_Selector('editnasservice', $this->serviceTypes, __('Service'), @$this->nasOptions[$nasId]['service'], false) . ' ';
            $inputs .= wf_Selector('editnasonlyactive', $onlyActiveParams, __('Only active users'), @$this->nasOptions[$nasId]['onlyactive'], false) . ' ';
            $nasPort = @$this->nasOptions[$nasId]['port'];
            $nasPort = (!empty($nasPort)) ? $nasPort : $this->remotePort;
            $inputs .= wf_TextInput('editnasport', __('Port'), $nasPort, false, 6, 'digits') . ' ';
            $inputs .= wf_HiddenInput('editnasid', $nasId);
            $inputs .= wf_Submit(__('Save'));

            $result .= wf_Form(self::URL_ME . '&editnasoptions=' . $nasId, 'POST', $inputs, 'glamour');
        } else {
            $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('NAS not exists'), 'error');
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
                $result .= __('Something went wrong') . ': ' . __('NAS not exists');
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
                $result .= __('Something went wrong') . ': ' . __('NAS not exists');
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
     * Returns list of services for some NAS
     * 
     * @param int $nasId
     * 
     * @return array
     */
    protected function getNasServices($nasId) {
        $result = array();
        if (!empty($this->services)) {
            foreach ($this->services as $io => $each) {
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
            $result .= __('Something went wrong') . ': ' . __('not existing attribute');
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
     * Performs cleanup of accounting data for some period
     * 
     * @param int $days
     * @param int $unfinished
     * 
     * @return void
     */
    public function cleanupAccounting($daysCount = 0, $unfinished = 0) {
        $daysCount = ubRouting::filters($daysCount, 'int');
        if ($daysCount) {
            $intervalq = "<= NOW() - INTERVAL " . $daysCount . " DAY ";
            //old finished sessions, accounting data
            $query = "DELETE FROM `" . self::NAS_ACCT . "` WHERE `acctstarttime` " . $intervalq . " AND `acctstoptime` IS NOT NULL";
            nr_query($query);
            //postauth
            $query = "DELETE FROM `" . self::NAS_POSTAUTH . "` WHERE `authdate` " . $intervalq;
            nr_query($query);
            if ($unfinished) {
                //old unfinished sessions (seems its dead)
                $query = "DELETE FROM `" . self::NAS_ACCT . "` WHERE `acctupdatetime` " . $intervalq . " AND `acctstoptime` IS NULL";
                nr_query($query);
            }
            log_register('MULTIGEN ACCOUNTING CLEANUP `' . $daysCount . '` DAYS');
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
                $result .= $this->messages->getStyledMessage(__('All attributes in scenario was deleted') . ': ' . $scenarioId, 'error');
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
                $cells .= wf_TableCell(__('Scenario'));
                $cells .= wf_TableCell(__('Attribute'));
                $cells .= wf_TableCell(__('Operator'));
                $cells .= wf_TableCell(__('Value'));
                $cells .= wf_TableCell(__('Actions'));
                $rows = wf_TableRow($cells, 'row1');

                foreach ($currentAtrributes as $io => $each) {
                    $cells = wf_TableCell($this->attrModifiers[$each['modifier']]);
                    $cells .= wf_TableCell($each['scenario']);
                    $cells .= wf_TableCell($each['attribute']);
                    $cells .= wf_TableCell($each['operator']);
                    $cells .= wf_TableCell($each['content']);
                    $attributeControls = wf_JSAlert(self::URL_ME . '&editnasoptions=' . $nasId . '&deleteattributeid=' . $each['id'], web_delete_icon(), $this->messages->getDeleteAlert()) . ' ';
                    $attributeControls .= wf_modalAuto(web_edit_icon(), __('Edit'), $this->renderAttributeTemplateEditForm($each['id']));
                    $cells .= wf_TableCell($attributeControls);
                    $rows .= wf_TableRow($cells, 'row5');
                }

                $result .= wf_TableBody($rows, '100%', 0, 'sortable');
            } else {
                $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('NAS not exists'), 'error');
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
            $inputs .= wf_Selector('newmodifier', $this->attrModifiers, __('Users'), '', false) . ' ';
            $inputs .= wf_Selector('newscenario', $this->scenarios, __('Scenario'), '', false) . ' ';
            $inputs .= wf_TextInput('newattribute', __('Attribute'), '', false, 20) . ' ';
            $inputs .= wf_Selector('newoperator', $this->operators, __('Operator'), '', false) . ' ';
            $inputs .= wf_TextInput('newcontent', __('Value'), '', false, 20) . ' ';
            $inputs .= wf_HiddenInput('newattributenasid', $nasId);
            $inputs .= wf_Submit(__('Create'));
            //form assembly
            $result .= wf_Form(self::URL_ME . '&editnasoptions=' . $nasId, 'POST', $inputs, 'glamour');
        } else {
            $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('NAS not exists'), 'error');
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
            $inputs .= wf_Selector('chmodifier', $this->attrModifiers, __('Users'), $attributeData['modifier'], false) . ' ';
            $inputs .= wf_Selector('chscenario', $this->scenarios, __('Scenario'), $attributeData['scenario'], false) . ' ';
            $inputs .= wf_TextInput('chattribute', __('Attribute'), $attributeData['attribute'], false, 20) . ' ';
            $inputs .= wf_Selector('choperator', $this->operators, __('Operator'), $attributeData['operator'], false) . ' ';
            $currentContent = htmlspecialchars($attributeData['content']);
            $inputs .= wf_TextInput('chcontent', __('Value'), $currentContent, false, 20) . ' ';
            $inputs .= wf_HiddenInput('chattributenasid', $nasId);
            $inputs .= wf_HiddenInput('chattributeid', $attributeId);
            $inputs .= wf_Submit(__('Save'));
            //form assembly
            $result .= wf_Form(self::URL_ME . '&editnasoptions=' . $nasId, 'POST', $inputs, 'glamour');
        } else {
            $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': EX_ATTRIBUTEID_NOT_EXIST', 'error');
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
                if (ubRouting::checkPost(array('newscenario', 'newattribute', 'newoperator', 'newmodifier'))) {
                    $newScenario = ubRouting::post('newscenario');
                    $newScenario_f = ubRouting::filters($newScenario, 'mres');
                    $newModifier = ubRouting::post('newmodifier');
                    $newModifier_f = ubRouting::filters($newModifier, 'mres');
                    $newAttribute = ubRouting::post('newattribute');
                    $newAttribute_f = ubRouting::filters($newAttribute, 'mres');
                    $newAttribute_f = trim($newAttribute_f);
                    $newOperator = ubRouting::post('newoperator');
                    $newOperator_f = ubRouting::filters($newOperator, 'mres');
                    $newContent = ubRouting::post('newcontent');
                    $newContent_f = ubRouting::filters($newContent, 'mres');
                    $newContent_f = trim($newContent_f);

                    $query = "INSERT INTO `" . self::NAS_ATTRIBUTES . "` (`id`,`nasid`,`scenario`,`modifier`,`attribute`,`operator`,`content`) VALUES "
                            . "(NULL,'" . $nasId . "','" . $newScenario_f . "','" . $newModifier_f . "','" . $newAttribute_f . "','" . $newOperator_f . "','" . $newContent_f . "');";
                    nr_query($query);
                    $newId = simple_get_lastid(self::NAS_ATTRIBUTES);
                    log_register('MULTIGEN NAS [' . $nasId . '] CREATE ATTRIBUTE `' . $newAttribute . '` ID [' . $newId . ']');
                }
            } else {
                $result .= __('Something went wrong') . ': ' . __('NAS not exists');
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
                if (ubRouting::checkPost(array('chscenario', 'chattribute', 'choperator', 'chmodifier'))) {
                    $attributeId = vf($_POST['chattributeid'], 3);
                    if (isset($this->nasAttributes[$attributeId])) {
                        $chAttribute = ubRouting::post('chattribute');
                        $chAttribute = trim($chAttribute);
                        $chContent = ubRouting::post('chcontent');
                        $chContent = trim($chContent);
                        $where = "WHERE `id`='" . $attributeId . "';";
                        simple_update_field(self::NAS_ATTRIBUTES, 'scenario', ubRouting::post('chscenario'), $where);
                        simple_update_field(self::NAS_ATTRIBUTES, 'modifier', ubRouting::post('chmodifier'), $where);
                        simple_update_field(self::NAS_ATTRIBUTES, 'attribute', $chAttribute, $where);
                        simple_update_field(self::NAS_ATTRIBUTES, 'operator', ubRouting::post('choperator'), $where);
                        simple_update_field(self::NAS_ATTRIBUTES, 'content', $chContent, $where);
                        log_register('MULTIGEN NAS [' . $nasId . '] CHANGE ATTRIBUTE [' . $attributeId . ']');
                    } else {
                        $result .= __('Something went wrong') . ': EX_ATTRIBUTE_NOT_EXIST';
                    }
                }
            } else {
                $result .= __('Something went wrong') . ': ' . __('NAS not exists');
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
        $query .= "(NULL,'" . $login . "'," . $state . ");";
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
                        $this->logEvent('USERSTATE CREATED ' . $login . ' STATE ' . $state['current'], 3);
                    }
                } else {
                    //user state changed
                    if ($state['current'] != $state['previous']) {
                        if (isset($this->allUserData[$login])) {
                            $this->changeUserState($login, $state['current']);
                            $this->logEvent('USERSTATE CHANGED ' . $login . ' STATE ' . $state['previous'] . ' ON ' . $state['current'], 3);
                        }
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
        if ((!isset($this->scenarioStats[$nasId])) OR (!isset($this->scenarioStats[$nasId][$scenario])) OR (!isset($this->scenarioStats[$nasId][$scenario][$attributeState]))) {
            $this->scenarioStats[$nasId][$scenario][$attributeState] = 1;
        } else {
            $this->scenarioStats[$nasId][$scenario][$attributeState]++;
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

                if (strpos($template, '{NETEXT}') !== false) {
                    if (isset($this->netExtUsers[$userLogin])) {
                        $netExtData = $this->netExtUsers[$userLogin];
                        $template = str_replace('{NETEXT}', $netExtData, $template);
                    }
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
                    $nasPort = $this->nasOptions[$nasId]['port'];
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
     * Returns array of all possible radius-preprocessed usernames
     * 
     * @return array
     */
    public function getAllUserNames() {
        $result = array();
        if (empty($this->allUserData)) {
            $this->loadUserData();
            if ((isset($this->altCfg[self::OPTION_SWASSIGN])) AND ( isset($this->altCfg[self::OPTION_QINQ]))) {
                if (($this->altCfg[self::OPTION_SWASSIGN]) AND ( $this->altCfg[self::OPTION_QINQ])) {
                    $this->loadSwitches();
                    $this->loadSwithchAssigns();
                    $this->loadAllQinQ();
                }
            } else {
                if (isset($this->altCfg[self::OPTION_UNIVERSALQINQ])) {
                    if ($this->altCfg[self::OPTION_UNIVERSALQINQ]) {
                        $this->loadAllQinQ();
                    }
                }
            }

            //preloading culpa instance
            if ($this->meaCulpaFlag) {
                $this->loadMeaCulpa();
            }
        }
        if (!empty($this->allUserData)) {
            foreach ($this->allUserData as $eachUserLogin => $eachUserData) {
                foreach ($this->usernameTypes as $eachUsernameType => $usernameTypeName) {
                    $userName = $this->getLoginUsername($eachUserLogin, $eachUserData, $eachUsernameType);
                    $result[$eachUserLogin][] = (string) $userName;
                }
            }
        }
        return($result);
    }

    /**
     * Returns transformed username by some type
     * 
     * @param string $userLogin
     * @param array  $userdata
     * @param string $usernameType
     * 
     * @return string
     */
    protected function getLoginUsername($userLogin, $userData, $userNameType) {
        $result = '';
        switch ($userNameType) {
            case 'login':
                $result = $userLogin;
                break;
            case 'ip':
                $result = @$userData['ip'];
                break;
            case 'mac':
                $result = @$userData['mac'];
                break;
            case 'macup':
                $result = @strtoupper($userData['mac']);
                break;
            case 'macju':
                $result = @$this->transformMacDotted($userData['mac']);
                break;
            case 'qinq':
                $result = $this->getQinQUsername($userLogin, '.');
                break;
            case 'qinqju':
                $result = $this->getQinQUsername($userLogin, '-');
                break;
            case 'meaculpa':
                $result = $this->meaCulpa->get($userLogin);
                break;
        }
        return ($result);
    }

    /**
     * Returns default switch based QinQ username
     * 
     * @param string $userLogin
     * @param string $delimiter
     * 
     * @return string/void
     */
    protected function getQinQUsername($userLogin, $delimiter = '.') {
        $result = '';
        if (isset($this->usersQinQ[$userLogin])) {
            $qinqData = $this->usersQinQ[$userLogin];
            if ($this->allSvlan[$qinqData['svlan_id']]['svlan'] === '0') {
                $result .= $qinqData['cvlan'];
            } else {
                $result .= $this->allSvlan[$qinqData['svlan_id']]['svlan'] . $delimiter . $qinqData['cvlan'];
            }
        } elseif (isset($this->userSwitchAssigns[$userLogin])) {
            $assignData = $this->userSwitchAssigns[$userLogin];
            $assignedSwitchId = $assignData['switchid'];
            $assignedPort = $assignData['port'];
            if (isset($this->switchesQinQ[$assignedSwitchId])) {
                $qinqData = $this->switchesQinQ[$assignedSwitchId];
                if (!empty($assignedPort)) {
                    if ($this->allSvlan[$qinqData['svlan_id']]['svlan'] === '0') {
                        $result .= ($qinqData['cvlan'] + ($assignedPort - 1));
                    } else {
                        $result .= $this->allSvlan[$qinqData['svlan_id']]['svlan'] . $delimiter . ($qinqData['cvlan'] + ($assignedPort - 1));
                    }
                }
            }
        }
        if (!empty($result)) {
            if (isset($this->allSvlan[$qinqData['svlan_id']])) {
                $realmId = $this->allSvlan[$qinqData['svlan_id']]['realm_id'];
                if ($realmId != 1) {
                    $result .= '@' . $this->allRealms[$realmId]['realm'];
                }
            }
        }
        return($result);
    }

    /**
     * Returns user login if some username for him found
     * 
     * @param string $userName
     * @param array $allUserNames
     * 
     * @return string
     */
    public function getUserLogin($userName, $allUserNames) {
        $result = '';
        if (!empty($allUserNames)) {
            $userName = (string) $userName;
            foreach ($allUserNames as $login => $each) {
                if (array_search($userName, $each, true) !== false) {
                    $result = $login;
                    break;
                }
            }
        }
        return($result);
    }

    /**
     * Need to disconnect user when data like username changed.
     * 
     * @param string $userLogin
     * @param array $userData
     * 
     * @return void
     */
    public function podOnExternalEvent($userLogin, $userData, $newUserData = array()) {
        $this->loadHugeRegenData();
        if (!empty($this->allUserData)) {
            $this->preprocessUserData();
            if (isset($this->userNases[$userLogin])) {
                $userNases = $this->userNases[$userLogin];
                if (!empty($userNases)) {
                    foreach ($userNases as $eachNasId) {
                        @$nasOptions = $this->nasOptions[$eachNasId];
                        @$userNameType = $nasOptions['usernametype'];
                        if ($userNameType != 'login') {
                            $userName = $this->getLoginUsername($userLogin, $userData, $userNameType);
                            if (!empty($userName)) {
                                if (!empty($nasOptions)) {
                                    if ($nasOptions['service'] != 'none') {
                                        $nasServices = @$this->services[$eachNasId];
                                        if (!empty($nasServices)) {
                                            $this->allUserData[$userLogin]['ip'] = $userData['ip'];
                                            $this->allUserData[$userLogin]['mac'] = $userData['mac'];
                                            if (strpos($nasOptions['service'], 'pod') !== false) {
                                                $podCommand = $this->getAttributeValue($userLogin, $userName, $eachNasId, $nasServices['pod']) . "\n";
                                                $this->savePodQueue($podCommand);
                                                if (!empty($newUserData)) {
                                                    $newUserName = $this->getLoginUsername($userLogin, $newUserData, $userNameType);
                                                    $this->replaceSingleUser($newUserName, $userName);
                                                    $this->changeFramedIp($newUserData['ip'], $userData['ip'], $newUserName);
                                                }

                                                //adding else to avoid user double kill when use pod + coa services
                                            } else {
                                                if (strpos($nasOptions['service'], 'coa') !== false) {
                                                    $podCommand = $this->getAttributeValue($userLogin, $userName, $eachNasId, $nasServices['pod']) . "\n";
                                                    $this->saveCoaQueue($podCommand);
                                                    if (!empty($newUserData)) {
                                                        $newUserName = $this->getLoginUsername($userLogin, $newUserData, $userNameType);
                                                        $this->replaceSingleUser($newUserName, $userName);
                                                        $this->changeFramedIp($newUserData['ip'], $userData['ip'], $newUserName);
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
            }
            //run PoD queue
            if (isset($this->runServices['pod'])) {
                $this->runPodQueue();
            }

            //run CoA queue
            if (isset($this->runServices['coa'])) {
                $this->runCoaQueue();
            }
        }
    }

    /**
     * Replaces old username in mlg_* tables
     * 
     * @param string $newUserName
     * @param striing $oldUserName
     * 
     * @return void
     */
    protected function replaceSingleUser($newUserName, $oldUserName) {
        if (!empty($newUserName) and !empty($oldUserName)) {
            foreach ($this->scenarios as $eachScenario) {
                $query = 'UPDATE `' . self::SCENARIO_PREFIX . $eachScenario . '` SET `username`="' . $newUserName . '" WHERE `username`="' . $oldUserName . '"';
                nr_query($query);
            }
        }
    }

    /**
     * Replaces old Framed-IP-Address in mlg_reply table
     * 
     * @param string $newIp
     * @param string $oldIp
     * @param string $userName
     * 
     * @return void
     */
    protected function changeFramedIp($newIp, $oldIp, $userName) {
        if (!empty($newIp) and !empty($oldIp)) {
            $query = 'UPDATE `' . self::SCENARIO_PREFIX . 'reply' . '` SET `Value`="' . $newIp . '" WHERE `attribute`="Framed-IP-Address" AND `value`="' . $oldIp . '" AND `username`="' . $userName . '"';
            nr_query($query);
        }
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
            //starting regeneration transaction if required
            if ($this->inno) {
                nr_query("START TRANSACTION;");
            }
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
                    if (!empty($userNases)) {
                        foreach ($userNases as $eachNasId) {
                            @$nasOptions = $this->nasOptions[$eachNasId];
                            @$userNameType = $nasOptions['usernametype'];

                            //overriding username type if required
                            $userName = $this->getLoginUsername($userLogin, $eachUser, $userNameType);
                            //yeah, this is possible in some unusual cases with QinQ or something like that
                            if (!empty($userName)) {
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
                                                    // Check if is $value {MACROS} ?)
                                                    if (!preg_match('/(^{\w+}$)/', $value)) {
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
                                                    // Check if is $value {MACROS} ?)
                                                    if (!preg_match('/(^{\w+}$)/', $value)) {
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
                                                }
                                                //this attribute is actual only for active users
                                                if ($modifier == 'active') {
                                                    if ($userRealState) {
                                                        $value = $this->getAttributeValue($userLogin, $userName, $eachNasId, $template);
                                                        // Check if is $value {MACROS} ?)
                                                        if (!preg_match('/(^{\w+}$)/', $value)) {
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
                                                if ((!empty($nasServices['coadisconnect'])) AND (!empty($nasServices['coaconnect']))) {
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

            //commiting changes to database if required
            if ($this->inno) {
                nr_query("COMMIT;");
            }
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
        file_put_contents(self::POD_PATH . $this->instanceId, $data, FILE_APPEND);
        $this->logEvent('POD_QUEUE_ADD ' . $this->instanceId . ': ' . trim($data), 3); //Omae wa mou shindeiru
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
        file_put_contents(self::COA_PATH . $this->instanceId, $data, FILE_APPEND);
        $this->logEvent('COA_QUEUE_ADD ' . $this->instanceId . ': ' . trim($data), 3);
    }

    /**
     * Runs PoD queue if not empty and flushes after it
     * 
     * @return void
     */
    protected function runPodQueue() {
        if (file_exists(self::POD_PATH . $this->instanceId)) {
            chmod(self::POD_PATH . $this->instanceId, 0755);
            $podQueueCleanup = $this->echoPath . ' "" > ' . getcwd() . '/' . self::POD_PATH . $this->instanceId . "\n";
            $this->savePodQueue($podQueueCleanup);
            if ($this->logging >= 4) {
                shell_exec(self::POD_PATH . $this->instanceId . ' >>' . self::LOG_PATH . ' 2>> ' . self::LOG_PATH);
            } else {
                shell_exec(self::POD_PATH . $this->instanceId . ' >/dev/null 2>/dev/null &');
            }
            $this->logEvent('POD_QUEUE_RUN: ' . $this->instanceId, 3); //nani?
        }
    }

    /**
     * Runs CoA queue if not empty and flushes after it
     * 
     * @return void
     */
    protected function runCoaQueue() {
        if (file_exists(self::COA_PATH . $this->instanceId)) {
            chmod(self::COA_PATH . $this->instanceId, 0755);
            $coaQueueCleanup = $this->echoPath . ' "" > ' . getcwd() . '/' . self::COA_PATH . $this->instanceId . "\n";
            $this->saveCoaQueue($coaQueueCleanup);
            if ($this->logging >= 4) {
                shell_exec(self::COA_PATH . $this->instanceId . ' >>' . self::LOG_PATH . ' 2>> ' . self::LOG_PATH);
            } else {
                shell_exec(self::COA_PATH . $this->instanceId . ' >/dev/null 2>/dev/null &');
            }
            $this->logEvent('COA_QUEUE_RUN: ' . $this->instanceId, 3);
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
            $result .= $this->allNas[$nasId]['nasip'] . ' - ' . $this->allNas[$nasId]['nasname'];
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
                $result .= $this->messages->getStyledMessage($nasLabel, 'success');
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
                                $totalAttributeCount += $eachCount;
                                $result .= $this->messages->getStyledMessage($stateName . ' ' . __('for scenario') . ' ' . $scenarioName . ': ' . $eachCount, $stateStyle);
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
            $timeStats .= __('Total time spent') . ': ' . round($totalTime, 2) . ' ' . __('sec.') . ' ';
            $timeStats .= __('Data loading time') . ': ' . round($dataLoadingTime, 2) . ' ' . __('sec.') . ' ';
            $timeStats .= __('Attributes processing time') . ': ' . round(($totalTime - $dataLoadingTime), 2) . ' ' . __('sec.') . ' ';
            $timeStats .= __('Memory used') . ': ~' . stg_convert_size(memory_get_usage(true));

            $perfStats .= __('Total attributes processed') . ': ' . $totalAttributeCount . ' ';
            if ($totalTime > 0) {
                //preventing zero divisions
                $perfStats .= __('Performance') . ': ' . round($totalAttributeCount / ($totalTime - $dataLoadingTime), 2) . ' ' . __('attributes/sec');
                $perfStats .= ' ( ' . round($totalAttributeCount / ($totalTime), 2) . ' ' . ' ' . __('brutto') . ')';
            } else {
                $perfStats .= __('Performance') . ': ' . wf_tag('b') . __('Black magic') . wf_tag('b', true);
            }

            $result .= $this->messages->getStyledMessage($timeStats, 'success');
            $result .= $this->messages->getStyledMessage($perfStats, 'success');
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
        $result .= wf_BackLink('?module=nas') . ' ';
        $result .= wf_modal(wf_img('skins/icon_clone.png') . ' ' . __('Clone NAS configuration'), __('Clone NAS configuration'), $this->renderNasCloneForm($nasId), 'ubButton', '750', '390');
        if ($this->nasHaveOptions($nasId)) {
            $result .= wf_AjaxLoader();
            $result .= wf_AjaxLink(self::URL_ME . '&ajnasregen=true&editnasoptions=' . $nasId, wf_img('skins/refresh.gif') . ' ' . __('Base regeneration'), 'nascontrolajaxcontainer', false, 'ubButton');
            $result .= wf_AjaxLink(self::URL_ME . '&ajscenarioflush=true&editnasoptions=' . $nasId, wf_img('skins/skull.png') . ' ' . __('Flush all attributes in all scenarios'), 'nascontrolajaxcontainer', false, 'ubButton');
            if ($this->nasOptions[$nasId]['service'] != 'none') {
                $result .= wf_modalAuto(web_icon_extended() . ' ' . __('Service'), __('Service'), $this->renderNasServicesEditForm($nasId), 'ubButton');
            }
            $result .= wf_AjaxContainer('nascontrolajaxcontainer');
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
                $inputs .= __('PoD') . wf_tag('br');
                $inputs .= wf_TextArea('newnasservicepod', '', @$nasServices['pod'], true, '90x2');
                $inputs .= __('CoA Connect') . wf_tag('br');
                $inputs .= wf_TextArea('newnasservicecoaconnect', '', @$nasServices['coaconnect'], true, '90x2');
                $inputs .= __('CoA Disconnect') . wf_tag('br');
                $inputs .= wf_TextArea('newnasservicecoadisconnect', '', @$nasServices['coadisconnect'], true, '90x2');
                $inputs .= wf_Submit(__('Save'));
                $result .= wf_Form(self::URL_ME . '&editnasoptions=' . $nasId, 'POST', $inputs, 'glamour');
            }
        }
        return ($result);
    }

    /**
     * Returns some NAS text-based configuration info for copy/paste settings
     * 
     * @param int $nasId
     * 
     * @return array/string if empty
     */
    protected function getNasCopyString($nasId) {
        $result = array();
        if (isset($this->nasOptions[$nasId])) {
            $result['options'] = $this->nasOptions[$nasId];
            $result['attributes'] = $this->getNasAttributes($nasId);
            $result['services'] = $this->getNasServices($nasId);
            $result = json_encode($result);
            $result = base64_encode($result);
        } else {
            $result = '';
        }
        return ($result);
    }

    /**
     * Renders NAS copy&paste settings form
     * 
     * @param int $nasId
     * 
     * @return string
     */
    protected function renderNasCopyPasteForm($nasId) {
        $result = '';
        $inputs = __('You can copy&paste current NAS configuration as text');
        $inputs .= wf_tag('br');
        $inputs .= wf_TextInput('nascopypastetext', __('Settings'), $this->getNasCopyString($nasId), true, 55);
        $inputs .= wf_CheckInput('nascopypasteagree', __('I understand that changing that completely destroys all current NAS settings if they exist and will replace them with the another configuration'), true, false);
        $inputs .= wf_delimiter();
        $inputs .= wf_Submit(__('Save'));

        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Renders nas options/atributes/services cloning form
     * 
     * @param int $nasId
     *
     * @return string
     * 
     */
    protected function renderNasCloneForm($nasId) {
        $result = '';
        $nasId = vf($nasId, 3);
        $nasParamsTmp = array();
        $otherNasCount = 0;
        if (isset($this->allNas[$nasId])) {
            /**
             * Get the fuck off
             * Mother fucker
             * Back the fuck off
             * Fucking hustler
             * What the fuck? What the fuck?
             * I don't wanna
             * I don't need ya
             * Watch out the fire
             * Of the Saiya
             */
            if ((!empty($this->nasOptions)) AND (!empty($this->allNas))) {
                foreach ($this->nasOptions as $io => $each) {
                    if (($io != $nasId) AND ( isset($this->allNas[$io]))) {
                        $nasBasicData = $this->allNas[$io];
                        $nasExtendedOptions = $each;
                        $nasUsernameType = @$this->usernameTypes[$nasExtendedOptions['usernametype']];
                        $nasService = $this->serviceTypes[$nasExtendedOptions['service']];
                        $attributeTemplates = $this->getNasAttributes($io);
                        $attributeTemplatesCount = sizeof($attributeTemplates);
                        $nasLabel = $nasBasicData['nasip'] . ' - ' . $nasBasicData['nasname'] . ' (' . $nasUsernameType . ' / ' . $nasService . ' / ' . $attributeTemplatesCount . ' ' . __('NAS attributes') . ')';
                        $nasParamsTmp[$nasBasicData['id']] = $nasLabel;
                        $otherNasCount++;
                    }
                }

                if (!empty($nasParamsTmp)) {
                    $inputs = wf_Selector('clonenasfromid', $nasParamsTmp, __('Clone') . ' ' . __('NAS'), '', true);
                    $inputs .= wf_HiddenInput('clonenastoid', $nasId);
                    $inputs .= wf_CheckInput('clonenasagree', __('I understand that cloning completely destroys all current NAS settings if they exist and will replace them with the configuration of another NAS'), true, false);
                    $inputs .= wf_delimiter();
                    $inputs .= wf_Submit(__('Clone'));
                    $result .= wf_Form('', 'POST', $inputs, 'glamour');
                }
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('NAS not exists'), 'error');
        }

        if ($otherNasCount < 1) {
            $result .= $this->messages->getStyledMessage(__('No other configured NAS to cloning'), 'warning');
        }
        //copy&paste form
        $result .= wf_tag('br');
        $result .= $this->renderNasCopyPasteForm($nasId);

        return ($result);
    }

    /**
     * Flushes all NAS configuration from database
     * 
     * @param int $nasId
     * 
     * @return void
     */
    public function deleteAllNasConfiguration($nasId) {
        $nasId = vf($nasId, 3);
        $query = "DELETE FROM `" . self::NAS_OPTIONS . "` WHERE `nasid`='" . $nasId . "';";
        nr_query($query);
        log_register('MULTIGEN NAS [' . $nasId . '] FLUSH OPTIONS');

        $query = "DELETE FROM `" . self::NAS_ATTRIBUTES . "` WHERE `nasid`='" . $nasId . "';";
        nr_query($query);
        log_register('MULTIGEN NAS [' . $nasId . '] FLUSH ATTRIBUTES');

        $query = "DELETE FROM `" . self::NAS_SERVICES . "` WHERE `nasid`='" . $nasId . "';";
        nr_query($query);
        log_register('MULTIGEN NAS [' . $nasId . '] FLUSH SERVICES');
    }

    /**
     * Clones all NAS options, attributes and services
     * 
     * @param int $fromId
     * @param int $toId
     * 
     * @return void/string on error
     */
    public function cloneNasConfiguration($fromId, $toId) {
        $result = '';
        $fromId = vf($fromId, 3);
        $toId = vf($toId, 3);
        if ((isset($this->allNas[$fromId])) AND ( $this->allNas[$toId])) {
            if (isset($this->nasOptions[$fromId])) {
                $sourceNasOptions = $this->nasOptions[$fromId];
                $sourceNasAttributes = $this->getNasAttributes($fromId);
                $sourceNasServices = $this->getNasServices($fromId);
                //deleting all old NAS setup
                $this->deleteAllNasConfiguration($toId);
                //Creating new NAS options
                if (!empty($sourceNasOptions)) {
                    log_register('MULTIGEN NAS [' . $toId . '] CLONE CONFIGURATION FROM [' . $fromId . ']');
                    $newUserName = $sourceNasOptions['usernametype'];
                    $newService = $sourceNasOptions['service'];
                    $newOnlyActive = $sourceNasOptions['onlyactive'];
                    $newPort = $sourceNasOptions['port'];

                    //new NAS options creation
                    $newUserName_f = mysql_real_escape_string($newUserName);
                    $newService_f = mysql_real_escape_string($newService);
                    $newOnlyActive_f = mysql_real_escape_string($newOnlyActive);
                    $newPort_f = vf($newPort, 3);
                    $query = "INSERT INTO `" . self::NAS_OPTIONS . "` (`id`,`nasid`,`usernametype`,`service`,`onlyactive`,`port`) VALUES "
                            . "(NULL,'" . $toId . "','" . $newUserName_f . "','" . $newService_f . "','" . $newOnlyActive_f . "','" . $newPort_f . "');";
                    nr_query($query);
                    log_register('MULTIGEN NAS [' . $toId . '] CLONE USERNAME `' . $newUserName . '` SERVICE `' . $newService . '` ONLYAACTIVE `' . $newOnlyActive . '` PORT `' . $newPort . '` FROM NAS [' . $fromId . ']');
                }
                //Creating new NAS attribute templates
                if (!empty($sourceNasAttributes)) {
                    foreach ($sourceNasAttributes as $io => $each) {
                        $newScenario = $each['scenario'];
                        $newScenario_f = mysql_real_escape_string($newScenario);
                        $newModifier = $each['modifier'];
                        $newModifier_f = mysql_real_escape_string($newModifier);
                        $newAttribute = $each['attribute'];
                        $newAttribute_f = mysql_real_escape_string($newAttribute);
                        $newOperator = $each['operator'];
                        $newOperator_f = mysql_real_escape_string($newOperator);
                        $newContent = $each['content'];
                        $newContent_f = mysql_real_escape_string($newContent);
                        $query = "INSERT INTO `" . self::NAS_ATTRIBUTES . "` (`id`,`nasid`,`scenario`,`modifier`,`attribute`,`operator`,`content`) VALUES "
                                . "(NULL,'" . $toId . "','" . $newScenario_f . "','" . $newModifier_f . "','" . $newAttribute_f . "','" . $newOperator_f . "','" . $newContent_f . "');";
                        nr_query($query);
                        $newId = simple_get_lastid(self::NAS_ATTRIBUTES);
                        log_register('MULTIGEN NAS [' . $toId . '] CLONE ATTRIBUTE `' . $newAttribute . '` ID [' . $newId . '] FROM NAS [' . $fromId . ']');
                    }
                }

                //Creating new NAS services 
                if (!empty($sourceNasServices)) {
                    $newPod = $sourceNasServices[$fromId]['pod'];
                    $newPod_f = mysql_real_escape_string($newPod);
                    $newCoaConnect = $sourceNasServices[$fromId]['coaconnect'];
                    $newCoaConnect_f = mysql_real_escape_string($newCoaConnect);
                    $newCoaDisconnect = $sourceNasServices[$fromId]['coadisconnect'];
                    $newCoaDisconnect_f = mysql_real_escape_string($newCoaDisconnect);
                    $query = "INSERT INTO `" . self::NAS_SERVICES . "` (`id`,`nasid`,`pod`,`coaconnect`,`coadisconnect`) VALUES "
                            . "(NULL,'" . $toId . "','" . $newPod_f . "','" . $newCoaConnect_f . "','" . $newCoaDisconnect_f . "');";
                    nr_query($query);
                    log_register('MULTIGEN NAS [' . $toId . '] CLONE SERVICES FROM NAS [' . $fromId . ']');
                }
            } else {
                $result .= __('Something went wrong') . ': ' . __('Configuration') . ' ' . __('NAS not exists');
            }
        } else {
            $result .= __('Something went wrong') . ': ' . __('NAS not exists');
        }
        return ($result);
    }

    /**
     * Pastes some configuration string data to some NAS
     * 
     * @param int $nasId
     * @param string $confString
     * 
     * @return string
     */
    public function pasteNasConfiguration($nasId, $confString) {
        $result = '';
        $nasId = vf($nasId, 3);
        $confString = trim($confString);
        if (!empty($nasId)) {
            if (isset($this->allNas[$nasId])) {
                if (!empty($confString)) {
                    $confString = base64_decode($confString);
                    $confString = json_decode($confString, true);
                    if (!empty($confString)) {
                        if (is_array($confString)) {
                            if ((isset($confString['options'])) AND ( isset($confString['attributes'])) AND ( isset($confString['services']))) {
                                $sourceNasOptions = $confString['options'];
                                $sourceNasAttributes = $confString['attributes'];
                                $sourceNasServices = $confString['services'];
                                //deleting all old NAS setup
                                $this->deleteAllNasConfiguration($nasId);
                                //Creating new NAS options
                                if (!empty($sourceNasOptions)) {
                                    log_register('MULTIGEN NAS [' . $nasId . '] PASTE CONFIGURATION');
                                    $newUserName = $sourceNasOptions['usernametype'];
                                    $newService = $sourceNasOptions['service'];
                                    $newOnlyActive = $sourceNasOptions['onlyactive'];
                                    $newPort = $sourceNasOptions['port'];

                                    //new NAS options creation
                                    $newUserName_f = mysql_real_escape_string($newUserName);
                                    $newService_f = mysql_real_escape_string($newService);
                                    $newOnlyActive_f = mysql_real_escape_string($newOnlyActive);
                                    $newPort_f = vf($newPort, 3);
                                    $query = "INSERT INTO `" . self::NAS_OPTIONS . "` (`id`,`nasid`,`usernametype`,`service`,`onlyactive`,`port`) VALUES "
                                            . "(NULL,'" . $nasId . "','" . $newUserName_f . "','" . $newService_f . "','" . $newOnlyActive_f . "','" . $newPort_f . "');";
                                    nr_query($query);
                                    log_register('MULTIGEN NAS [' . $nasId . '] PASTE USERNAME `' . $newUserName . '` SERVICE `' . $newService . '` ONLYAACTIVE `' . $newOnlyActive . '` PORT `' . $newPort . '`');
                                }
                                //Creating new NAS attribute templates
                                if (!empty($sourceNasAttributes)) {
                                    foreach ($sourceNasAttributes as $io => $each) {
                                        $newScenario = $each['scenario'];
                                        $newScenario_f = mysql_real_escape_string($newScenario);
                                        $newModifier = $each['modifier'];
                                        $newModifier_f = mysql_real_escape_string($newModifier);
                                        $newAttribute = $each['attribute'];
                                        $newAttribute_f = mysql_real_escape_string($newAttribute);
                                        $newOperator = $each['operator'];
                                        $newOperator_f = mysql_real_escape_string($newOperator);
                                        $newContent = $each['content'];
                                        $newContent_f = mysql_real_escape_string($newContent);
                                        $query = "INSERT INTO `" . self::NAS_ATTRIBUTES . "` (`id`,`nasid`,`scenario`,`modifier`,`attribute`,`operator`,`content`) VALUES "
                                                . "(NULL,'" . $nasId . "','" . $newScenario_f . "','" . $newModifier_f . "','" . $newAttribute_f . "','" . $newOperator_f . "','" . $newContent_f . "');";
                                        nr_query($query);
                                        $newId = simple_get_lastid(self::NAS_ATTRIBUTES);
                                        log_register('MULTIGEN NAS [' . $nasId . '] PASTE ATTRIBUTE `' . $newAttribute . '` ID [' . $newId . ']');
                                    }
                                }

                                //Creating new NAS services 
                                if (!empty($sourceNasServices)) {
                                    $sourceNasServices = array_shift($sourceNasServices); //getting first service element
                                    $newPod = $sourceNasServices['pod'];
                                    $newPod_f = mysql_real_escape_string($newPod);
                                    $newCoaConnect = $sourceNasServices['coaconnect'];
                                    $newCoaConnect_f = mysql_real_escape_string($newCoaConnect);
                                    $newCoaDisconnect = $sourceNasServices['coadisconnect'];
                                    $newCoaDisconnect_f = mysql_real_escape_string($newCoaDisconnect);
                                    $query = "INSERT INTO `" . self::NAS_SERVICES . "` (`id`,`nasid`,`pod`,`coaconnect`,`coadisconnect`) VALUES "
                                            . "(NULL,'" . $nasId . "','" . $newPod_f . "','" . $newCoaConnect_f . "','" . $newCoaDisconnect_f . "');";
                                    nr_query($query);
                                    log_register('MULTIGEN NAS [' . $nasId . '] PASTE SERVICES');
                                }
                            } else {
                                $result .= __('Something went wrong') . ': EX_CONFSTRING_INCOMPLETE';
                            }
                        } else {
                            $result .= __('Something went wrong') . ': EX_CONFSTRING_CORRUPTED';
                        }
                    } else {
                        $result .= __('Something went wrong') . ': EX_CONFSTRING_CORRUPTED';
                    }
                } else {
                    $result .= __('Something went wrong') . ': EX_EMPTY_CONFSTRING';
                }
            } else {
                $result .= __('Something went wrong') . ': ' . __('NAS not exists');
            }
        } else {
            $result .= __('Something went wrong') . ': ' . __('NAS not exists');
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
        $dayAgo = $curTime - (86400 * $this->days);
        $dayAgo = date("Y-m-d", $dayAgo);
        $dayTomorrow = $curTime + 86400;
        $dayTomorrow = date("Y-m-d", $dayTomorrow);
        $preDateFrom = (wf_CheckPost(array('datefrom'))) ? $_POST['datefrom'] : $dayAgo;
        $preDateTo = (wf_CheckPost(array('dateto'))) ? $_POST['dateto'] : $dayTomorrow;
        $unfinishedFlag = (wf_CheckPost(array('showunfinished'))) ? true : false;
        if (!wf_CheckPost(array('showunfinished'))) {
            $unfinishedFlag = $this->unfinished;
        }
        $inputs = wf_DatePickerPreset('datefrom', $preDateFrom, false);
        $inputs .= wf_DatePickerPreset('dateto', $preDateTo, false);
        $inputs .= wf_CheckInput('showunfinished', __('Show only unfinished'), false, $unfinishedFlag);
        $inputs .= wf_Submit(__('Show'));
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
        if ($this->usernamesCachingTimeout) {
            $guessedUsernames = $this->cache->get('MLG_TELEPATHY', $this->usernamesCachingTimeout); //contains already guessed logins by usernames
            if (empty($guessedUsernames)) {
                $guessedUsernames = array();
            }
        } else {
            $guessedUsernames = array();
        }
        $this->loadAcctData();
        //login filtering
        if ($this->usernamesCachingTimeout) {
            $allUserNames = $this->cache->get('MLG_USERNAMES', $this->usernamesCachingTimeout);
            if (empty($allUserNames)) {
                $allUserNames = $this->getAllUserNames();
            } else {
                //here required to preload some users data
                if (empty($this->allUserData)) {
                    $this->loadUserData();
                    if ((isset($this->altCfg[self::OPTION_SWASSIGN])) AND ( isset($this->altCfg[self::OPTION_QINQ]))) {
                        if (($this->altCfg[self::OPTION_SWASSIGN]) AND ( $this->altCfg[self::OPTION_QINQ])) {
                            $this->loadSwitches();
                            $this->loadSwithchAssigns();
                            $this->loadAllQinQ();
                        }
                    } else {
                        if (isset($this->altCfg[self::OPTION_UNIVERSALQINQ])) {
                            if ($this->altCfg[self::OPTION_UNIVERSALQINQ]) {
                                $this->loadAllQinQ();
                            }
                        }
                    }
                }

                //preloading culpa instance
                if ($this->meaCulpaFlag) {
                    $this->loadMeaCulpa();
                }
            }
        } else {
            $allUserNames = $this->getAllUserNames();
        }

        if (ubRouting::checkGet('login')) {
            $filterLogin = ubRouting::get('login');
        } else {
            $filterLogin = '';
        }

        if (!empty($this->userAcctData)) {
            foreach ($this->userAcctData as $io => $each) {
                $fc = '';
                $efc = wf_tag('font', true);

                //time filtering
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

                //try to speed up that search
                if (isset($guessedUsernames[$each['username']])) {
                    $eachUserLogin = $guessedUsernames[$each['username']];
                } else {
                    $eachUserLogin = $this->getUserLogin($each['username'], $allUserNames);
                    $guessedUsernames[$each['username']] = $eachUserLogin;
                }

                if (!empty($eachUserLogin)) {
                    $userLink = wf_Link(self::URL_PROFILE . $eachUserLogin, web_profile_icon() . ' ' . @$this->allUserData[$eachUserLogin]['fulladress']);
                } else {
                    $userLink = '';
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
                if (!empty($this->acctFieldsAdditional)) {
                    foreach ($this->acctFieldsAdditional as $ia => $eachField) {
                        $data[] = $each[$eachField];
                    }
                }
                $data[] = $timeOffset;
                $data[] = $userLink;

                if (!empty($filterLogin)) {
                    if ($filterLogin == $eachUserLogin) {
                        $json->addRow($data);
                    }
                } else {
                    $json->addRow($data);
                }
                unset($data);
            }
        }

        if ($this->usernamesCachingTimeout) {
            $this->cache->set('MLG_TELEPATHY', $guessedUsernames, $this->usernamesCachingTimeout);
            $this->cache->set('MLG_USERNAMES', $allUserNames, $this->usernamesCachingTimeout);
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
        $columns = array('acctsessionid', 'username', 'nasipaddress', 'nasportid', 'acctstarttime', 'acctstoptime', 'acctinputoctets', 'acctoutputoctets', 'framedipaddress', 'acctterminatecause');
        if (!empty($this->acctFieldsAdditional)) {
            foreach ($this->acctFieldsAdditional as $io => $each) {
                $columns[] = $each;
            }
        }
        $columns[] = 'Time';
        $columns[] = 'User';

        if (wf_CheckPost(array('datefrom', 'dateto'))) {
            $searchDateFrom = mysql_real_escape_string($_POST['datefrom']);
            $searchDateTo = mysql_real_escape_string($_POST['dateto']);
        } else {
            $curTime = time();
            $dayAgo = $curTime - (86400 * $this->days);
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

        if (wf_CheckGet(array('lastsessions'))) {
            $lastFlag = '&lastsessions=true';
        } else {
            $lastFlag = '';
        }

        if (wf_CheckGet(array('username'))) {
            $userLogin = mysql_real_escape_string($_GET['username']);
            $userNameFilter = '&login=' . $userLogin;
        } else {
            $userNameFilter = '';
            $userLogin = '';
        }

        $ajUrl = self::URL_ME . '&ajacct=true&datefrom=' . $searchDateFrom . '&dateto=' . $searchDateTo . $unfinishedFlag . $userNameFilter . $lastFlag;

        $options = '"order": [[ 0, "desc" ]]';
        $result = wf_JqDtLoader($columns, $ajUrl, false, __('sessions'), 50, $options);
        $result .= wf_tag('br');
        if (!empty($userLogin)) {
            $result .= wf_BackLink(self::URL_PROFILE . $userLogin);
            $result .= wf_Link(self::URL_ME . '&userattributes=true&username=' . $userLogin, wf_img('skins/dna_icon.png') . ' ' . __('User attributes'), false, 'ubButton') . ' ';
            $result .= wf_Link(self::URL_ME . '&manualpod=true&username=' . $userLogin, wf_img('skins/skull.png') . ' ' . __('Terminate user session'), false, 'ubButton') . ' ';
        } else {
            if (!wf_CheckGet(array('lastsessions'))) {
                $result .= wf_Link(self::URL_ME . '&lastsessions=true', wf_img('skins/clock.png') . ' ' . __('Last sessions'), false, 'ubButton');
            } else {
                $result .= wf_BackLink(self::URL_ME);
            }
        }
        return ($result);
    }

    /**
     * Renders user manual user POD form
     * 
     * @param string $userLogin
     * 
     * @return string
     */
    public function renderManualPodForm($userLogin) {
        $result = '';
        if (!empty($userLogin)) {
            //preloading some required data
            if (empty($this->userNases)) {
                $this->loadUserData();
                $this->loadNethosts();
                $this->loadNetworks();
                $this->preprocessUserData();
            }

            if (isset($this->allUserData[$userLogin])) {
                if (isset($this->userNases[$userLogin])) {
                    if (!empty($this->userNases[$userLogin])) {
                        $nasTmp = array();
                        foreach ($this->userNases[$userLogin] as $io => $each) {
                            $nasTmp[$each] = $this->allNas[$each]['nasip'] . ' - ' . $this->allNas[$each]['nasname'];
                        }

                        $inputs = wf_HiddenInput('manualpod', 'true');
                        $inputs .= wf_HiddenInput('manualpodlogin', $userLogin);
                        $inputs .= wf_Selector('manualpodnasid', $nasTmp, __('NAS'), '', false) . ' ';
                        $inputs .= wf_Submit(__('Send') . ' ' . __('PoD'));

                        $result .= wf_Form('', 'POST', $inputs, 'glamour');
                    } else {
                        $result .= $this->messages->getStyledMessage(__('NAS not exists') . ' ' . __('for') . ' ' . $userLogin, 'error');
                    }
                } else {
                    $result .= $this->messages->getStyledMessage(__('NAS not exists') . ' ' . __('for') . ' ' . $userLogin, 'error');
                }
            } else {
                $result .= $this->messages->getStyledMessage(__('User not exists') . ': ' . $userLogin, 'error');
            }
        }

        $result .= wf_tag('br');
        $result .= wf_BackLink(self::URL_ME . '&username=' . $userLogin);
        return ($result);
    }

    /**
     * Renders actual user attributes data in all available scenarios.
     * 
     * @param string $userLogin
     * 
     * @return string
     */
    public function renderUserAttributes($userLogin) {
        $result = '';
        $tmpArr = array();
        if (empty($this->allUserData)) {
            //preloading userdata
            $this->loadUserData();
            //loading data required for QinQ users
            if ((isset($this->altCfg[self::OPTION_SWASSIGN])) AND ( isset($this->altCfg[self::OPTION_QINQ]))) {
                if (($this->altCfg[self::OPTION_SWASSIGN]) AND ( $this->altCfg[self::OPTION_QINQ])) {
                    $this->loadSwitches();
                    $this->loadSwithchAssigns();
                    $this->loadAllQinQ();
                }
            }

            //preloading culpa instance
            if ($this->meaCulpaFlag) {
                $this->loadMeaCulpa();
            }
        }


        if (isset($this->allUserData[$userLogin])) {
            $userData = $this->allUserData[$userLogin];
            $allPossibleUserNames = $this->getAllUserNames();

            if (isset($allPossibleUserNames[$userLogin])) {
                if (!empty($allPossibleUserNames[$userLogin])) {
                    $possibleUserNames = $allPossibleUserNames[$userLogin];
                    if (!empty($this->scenarios)) {
                        foreach ($this->scenarios as $scenarioId => $scenarioName) {
                            $scenarioTable = self::SCENARIO_PREFIX . $scenarioId;
                            $scenarioDb = new NyanORM($scenarioTable);
                            foreach ($possibleUserNames as $io => $eachPossibleUserName) {
                                $scenarioDb->orWhere('username', '=', $eachPossibleUserName);
                            }
                            $tmpArr[$scenarioId] = $scenarioDb->getAll();
                        }
                    } else {
                        $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('cant read scenarios'), 'error');
                    }
                } else {
                    $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('cant detect username for') . ' ' . $userLogin, 'error');
                }
            } else {
                $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('cant detect username for') . ' ' . $userLogin, 'error');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('User not exists') . ': ' . $userLogin, 'error');
        }

        //render available attributes data
        if (!empty($tmpArr)) {
            foreach ($tmpArr as $scenarioId => $attributes) {
                $result .= wf_tag('h3') . __('Scenario') . ' ' . $scenarioId . wf_tag('h3', true);
                if (!empty($attributes)) {
                    if (isset($attributes[0])) {
                        $scenarioColumns = array_keys($attributes[0]);
                        $cells = '';
                        //list available data colums
                        foreach ($scenarioColumns as $io => $each) {
                            $cells .= wf_TableCell($each);
                        }
                        $cells .= wf_TableCell(__('Actions'));
                        $rows = wf_TableRow($cells, 'row1');

                        //list available user attributes
                        foreach ($attributes as $io => $eachAttribute) {
                            $cells = '';
                            foreach ($eachAttribute as $column => $value) {
                                $cells .= wf_TableCell($value);
                            }
                            $delControl = self::URL_ME . '&userattributes=true&username=' . $userLogin . '&delattr=' . $eachAttribute['id'] . '&delscenario=' . $scenarioId;
                            $delCancelControl = self::URL_ME . '&userattributes=true&username=' . $userLogin;
                            $attrControls = wf_ConfirmDialogJS($delControl, web_delete_icon() . ' ' . __('Delete'), $this->messages->getDeleteAlert(), '', $delCancelControl);
                            $cells .= wf_TableCell($attrControls);
                            $rows .= wf_TableRow($cells, 'row5');
                        }

                        $result .= wf_TableBody($rows, '100%', 0, 'sortable');
                    } else {
                        $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('cant detect data columns'), 'warning');
                    }
                } else {
                    $result .= $this->messages->getStyledMessage(__('Nothing found'), 'info');
                }
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing found'), 'warning');
        }

        $result .= wf_delimiter(0);
        $result .= wf_BackLink(self::URL_ME . '&username=' . $userLogin);

        return($result);
    }

    /**
     * Deletes some user attribute from some scenario by its ID
     * 
     * @param int $attributeId
     * @param string $scenario
     * 
     * @return void/string on error
     */
    public function deleteUserAttribute($attributeId, $scenario) {
        $result = '';
        if (isset($this->scenarios[$scenario])) {
            $scenarioTable = self::SCENARIO_PREFIX . $scenario;
            $scenarioDb = new NyanORM($scenarioTable);
            $scenarioDb->where('id', '=', $attributeId);
            $scenarioDb->delete();
            log_register('MULTIGEN DELETE SCENARIO `' . $scenario . '` ATTRIBUTE [' . $attributeId . ']');
        } else {
            $result .= __('Something went wrong') . ': ' . __('Scenario') . ' ' . $scenario . ' ' . __('Not exists');
        }
        return($result);
    }

    /**
     * Executes manual PoD sending if its possilble
     * 
     * @return void/string on error
     */
    public function runManualPod() {
        $result = '';
        if (wf_CheckPost(array('manualpod', 'manualpodlogin', 'manualpodnasid'))) {
            $nasId = vf($_POST['manualpodnasid'], 3);
            $userLogin = $_POST['manualpodlogin'];
            if (empty($this->allUserData)) {
                //preloading userdata
                $this->loadUserData();
                //loading data required for QinQ users
                if ((isset($this->altCfg[self::OPTION_SWASSIGN])) AND ( isset($this->altCfg[self::OPTION_QINQ]))) {
                    if (($this->altCfg[self::OPTION_SWASSIGN]) AND ( $this->altCfg[self::OPTION_QINQ])) {
                        $this->loadSwitches();
                        $this->loadSwithchAssigns();
                        $this->loadAllQinQ();
                    }
                }

                //preloading culpa instance
                if ($this->meaCulpaFlag) {
                    $this->loadMeaCulpa();
                }
            }

            if (isset($this->allUserData[$userLogin])) {
                $userData = $this->allUserData[$userLogin];
                if (isset($this->allNas[$nasId])) {
                    $nasData = $this->allNas[$nasId];
                    if (isset($this->nasOptions[$nasId])) {
                        $nasOptions = $this->nasOptions[$nasId];
                        $userName = $this->getLoginUsername($userLogin, $userData, $nasOptions['usernametype']);
                        if (!empty($userName)) {
                            //try to use custom PoD service or default with username
                            if (!empty($this->services[$nasId]['pod'])) {
                                $podCommand = $this->services[$nasId]['pod'];
                            } else {
                                $podCommand = '{PRINTF} "User-Name= {USERNAME}" | {SUDO} {RADCLIENT} {NASIP}:{NASPORT} disconnect {NASSECRET}';
                            }
                            $podCommand = $this->getAttributeValue($userLogin, $userName, $nasId, $podCommand);
                            shell_exec($podCommand);
                            $this->logEvent('POD MANUAL ' . $userLogin . ' AS ' . $userName, 3);
                            log_register('MULTIGEN POD MANUAL (' . $userLogin . ') AS `' . $userName . '` NASID [' . $nasId . ']');
                        } else {
                            $result .= __('Something went wrong') . ': ' . __('cant detect username for') . ' ' . $userLogin;
                        }
                    } else {
                        $result .= __('No') . ' ' . __('NAS options') . ': ' . $nasId;
                    }
                } else {
                    $result .= __('NAS not exists') . ': ' . $nasId;
                }
            } else {
                $result .= __('User not exists') . ': ' . $userLogin;
            }
        } else {
            //what?
        }
        return ($result);
    }

    /**
     * Renders multigen logs control
     * 
     * @global object $ubillingConfig
     * 
     * @return string
     */
    public function renderLogControl() {
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
            $renderData .= __('Showing') . ' ' . $recordsLimit . ' ' . __('last events') . wf_tag('br');
            $renderData .= wf_Link(self::URL_ME . '&dlmultigenlog=true', wf_img('skins/icon_download.png', __('Download')) . ' ' . __('Download full log'), true);

            if (!empty($rawResult)) {
                $logData = explodeRows($rawResult);
                $logData = array_reverse($logData); //from new to old list
                if (!empty($logData)) {


                    $cells = wf_TableCell(__('Date'));
                    $cells .= wf_TableCell(__('Event'));
                    $rows .= wf_TableRow($cells, 'row1');

                    foreach ($logData as $io => $each) {
                        if (!empty($each)) {

                            $eachEntry = explode(' ', $each);
                            $cells = wf_TableCell($eachEntry[0] . ' ' . $eachEntry[1]);
                            $cells .= wf_TableCell(str_replace(($eachEntry[0] . ' ' . $eachEntry[1]), '', $each));
                            $rows .= wf_TableRow($cells, 'row3');
                        }
                    }
                    $renderData .= wf_TableBody($rows, '100%', 0, 'sortable');
                }
            } else {
                $renderData .= $this->messages->getStyledMessage(__('Nothing found'), 'warning');
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

    /**
     * Loads existing aggregated traffic data from database
     * 
     * @return void
     */
    protected function loadAcctTraffData() {
        $query = "select * from `" . self::NAS_TRAFFIC . "`;";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->previousTraffic[$each['login']] = $each;
            }
        }

        if ($this->ishimuraFlag) {
            $query = "select * from `" . self::NAS_ISHIMURA . "` WHERE `year`='" . curyear() . "' AND `month`='" . date("n") . "';";
            $all = simple_queryall($query);
            if (!empty($all)) {
                foreach ($all as $io => $each) {
                    $this->trafficArchive[$each['login']] = $each;
                }
            }
        }
    }

    /**
     * Loads existing aggregated traffic data from database
     * 
     * @return void
     */
    protected function loadUserTraffData() {
        $trafficDataTable = (!$this->ishimuraFlag) ? 'users' : self::NAS_ISHIMURA;
        $query = "SELECT `login`,`D0`,`U0` FROM `" . $trafficDataTable . "`;";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->usersTraffic[$each['login']]['D0'] = $each['D0'];
                $this->usersTraffic[$each['login']]['U0'] = $each['U0'];
            }
        }
    }

    /**
     * Performs preprocessing of current accounting traffic and stores it into database
     * 
     * @return void
     */
    public function aggregateTraffic() {
        $this->loadAcctTraffData();
        $this->loadUserTraffData();
        if ($this->ishimuraFlag) {
            $this->loadUserCash();
        }

        $currentTimestamp = time();
        $dateTo = date("Y-m-d H:i:s", $currentTimestamp);
        $lastRunTimestamp = $this->cache->get('MLG_TRAFFLASTRUN', 2592000);
        if (empty($lastRunTimestamp)) {
            $lastRunTimestamp = $currentTimestamp - 3600;
        }
        $dateFrom = date("Y-m-d H:i:s", $lastRunTimestamp);
        $allUserNames = $this->getAllUserNames();
        $this->cache->set('MLG_TRAFFLASTRUN', $currentTimestamp, 2592000);

        $query = "SELECT `username`,`acctoutputoctets`,`acctinputoctets`,`acctupdatetime`,`acctstoptime` from `" . self::NAS_ACCT . "`"
                . " WHERE `acctupdatetime` BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "' ORDER BY `radacctid` DESC;";

        $all = simple_queryall($query);

        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $loginDetect = $this->getUserLogin($each['username'], $allUserNames);
                if (!empty($loginDetect)) {
                    $activity = (empty($each['acctstoptime'])) ? 1 : 0;
                    if (isset($this->currentTraffic[$loginDetect])) {
                        $this->currentTraffic[$loginDetect]['down'] += $each['acctoutputoctets'];
                        $this->currentTraffic[$loginDetect]['up'] += $each['acctinputoctets'];
                    } else {
                        $this->currentTraffic[$loginDetect]['down'] = $each['acctoutputoctets'];
                        $this->currentTraffic[$loginDetect]['up'] = $each['acctinputoctets'];
                        $this->currentTraffic[$loginDetect]['activity'] = $activity;
                    }
                }
            }


            if (!empty($this->currentTraffic)) {
                foreach ($this->currentTraffic as $changedLogin => $currentTrafficData) {
                    //preventing first run issues
                    if ($this->ishimuraFlag) {
                        if (!isset($this->usersTraffic[$changedLogin])) {
                            $this->usersTraffic[$changedLogin]['D0'] = 0;
                            $this->usersTraffic[$changedLogin]['U0'] = 0;
                        }
                    }

                    if (isset($this->usersTraffic[$changedLogin])) {
                        $stgDownTraffic = $this->usersTraffic[$changedLogin]['D0'];
                        $stgUpTraffic = $this->usersTraffic[$changedLogin]['U0'];
                        if (isset($this->previousTraffic[$changedLogin])) {
                            $previousDownTraffic = $this->previousTraffic[$changedLogin]['down'];
                            $previousUpTraffic = $this->previousTraffic[$changedLogin]['up'];
                        } else {
                            $previousDownTraffic = 0;
                            $previousUpTraffic = 0;
                        }

                        $lastActivity = $currentTrafficData['activity'];

                        $diffDownTraffic = $currentTrafficData['down'] - $previousDownTraffic;

                        $diffUpTraffic = $currentTrafficData['up'] - $previousUpTraffic;

                        $newDownTraffic = $stgDownTraffic + $diffDownTraffic;
                        $newUpTraffic = $stgUpTraffic + $diffUpTraffic;

                        if (($diffDownTraffic != 0) OR ( $diffUpTraffic != 0)) {
                            $this->saveTrafficData($changedLogin, $newDownTraffic, $newUpTraffic);
                            $newPreviousDown = $previousDownTraffic + $diffDownTraffic;
                            $newPreviousUp = $previousUpTraffic + $diffUpTraffic;
                            $this->savePreviousTraffic($changedLogin, $newPreviousDown, $newPreviousUp, $lastActivity);
                            $this->logEvent('MULTIGEN TRAFFIC SAVE (' . $changedLogin . ') ' . $newDownTraffic . '/' . $newUpTraffic, 4);
                        }
                    }
                }
            }
        }
    }

    /**
     * Sets traffic value via stargazer configurator or ishimura ne configurator, lol
     * 
     * @param string $login
     * @param int $trafficDown
     * @param int $trafficUp
     * 
     * @return void
     */
    protected function saveTrafficData($login, $trafficDown, $trafficUp) {
        if (!$this->ishimuraFlag) {
            $command = $this->billCfg['SGCONF'] . ' set -s ' . $this->billCfg['STG_HOST'] . ' -p ' . $this->billCfg['STG_PORT'] . ' -a ' . $this->billCfg['STG_LOGIN'] . ' -w ' . $this->billCfg['STG_PASSWD'] . ' -u ' . $login . ' --d0 ' . $trafficDown . ' --u0 ' . $trafficUp;
            shell_exec($command);
        } else {
            $curyear = curyear();
            $curmonth = date("n");
            $currentCash = (isset($this->allUsersCash[$login])) ? $this->allUsersCash[$login] : 0;
            if (isset($this->trafficArchive[$login])) {
                $where = "WHERE `login`='" . $login . "' AND `year`='" . $curyear . "' AND `month`='" . $curmonth . "'";
                simple_update_field(self::NAS_ISHIMURA, 'D0', $trafficDown, $where);
                simple_update_field(self::NAS_ISHIMURA, 'U0', $trafficUp, $where);
                simple_update_field(self::NAS_ISHIMURA, 'cash', $currentCash, $where);
            } else {
                $query = "INSERT INTO `" . self::NAS_ISHIMURA . "` (`login`,`month`,`year`,`U0`,`D0`,`cash`) VALUES"
                        . "('" . $login . "','" . $curmonth . "','" . $curyear . "','" . $trafficUp . "','" . $trafficDown . "','" . $currentCash . "');";
                nr_query($query);
            }
        }
    }

    /**
     * Saves current run traffic data into database
     * 
     * @param string $login
     * @param int $trafficDown
     * @param int $trafficUp
     * @param int $activity
     * 
     * @return void
     */
    protected function savePreviousTraffic($login, $trafficDown, $trafficUp, $activity) {
        $login = mysql_real_escape_string($login);
        if (isset($this->previousTraffic[$login])) {
            $query = "UPDATE `" . self::NAS_TRAFFIC . "` SET `down`='" . $trafficDown . "', `up`='" . $trafficUp . "', `act`='" . $activity . "' WHERE `login`='" . $login . "';";
            nr_query($query);
        } else {
            //new user appeared
            $query = "INSERT INTO `" . self::NAS_TRAFFIC . "` (`login`,`down`,`up`,`act`) VALUES "
                    . "('" . $login . "','" . $trafficDown . "','" . $trafficUp . "'," . $activity . ");";
            nr_query($query);
        }
    }

    /**
     * Performs check of multigen-rebuild lock
     * 
     * @return bool 
     */
    public function isMultigenRunning() {
        return($this->stardust->isRunning());
    }

    /**
     * Locks Multigen regeneration
     * 
     * @return void
     */
    public function runPidStart() {
        $this->stardust->start();
    }

    /**
     * Releases Multigen regeneration lock
     * 
     * @return void
     */
    public function runPidEnd() {
        $this->stardust->stop();
    }
}
