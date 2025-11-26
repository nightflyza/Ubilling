<?php

/**
 * PON devices management and monitoring implementation
 */
class PONizer {

    /**
     * All available ONU devices as id=>onudata
     *
     * @var array
     */
    protected $allOnu = array();

    /**
     * List for mac = id
     *
     * @var array
     */
    protected $onuMacIdList = array();

    /**
     * List for serial = id
     *
     * @var type
     */
    protected $onuSerialIdList = array();

    /**
     * List for mac = oltid
     *
     * @var array
     */
    protected $onuMacOltidList = array();

    /**
     * List for serial = oltid
     *
     * @var array
     */
    protected $onuSerialOltidList = array();

    /**
     * Contains array of additional ONU users as id=>binddata
     *
     * @var array
     */
    protected $allOnuExtUsers = array();

    /**
     * OLT models data as id=>model data array
     *
     * @var array
     */
    protected $allModelsData = array();

    /**
     * All available OLT devices as id=>ip - location
     *
     * @var array
     */
    protected $allOltDevices = array();

    /**
     * All available OLT devices locations as id=>location
     *
     * @var array
     */
    protected $allOltNames = array();

    /**
     * Contains all OLT devices id=>modelId mappings
     *
     * @var array
     */
    protected $allOltModelIds = array();

    /**
     * OLT devices snmp data as id=>snmp data array
     *
     * @var array
     */
    protected $allOltSnmp = array();

    /**
     * OLT devices IPs as id=>ip
     *
     * @var array
     */
    protected $allOltIps = array();

    /**
     * Available OLT models as id=>modelname + snmptemplate + ports
     *
     * @var array
     */
    protected $allOltModels = array();

    /**
     * Contains available SNMP templates for OLT modelids as modelId=>snmpTemplateData
     *
     * @var array
     */
    protected $snmpTemplates = array();

    /**
     * Contains current ONU signal cache data as mac=>signal
     *
     * @var array
     */
    protected $signalCache = array();

    /**
     * Contains current ONU signal cache data as mac=>distance
     *
     * @var array
     */
    protected $distanceCache = array();

    /**
     * Contains current ONU last dereg reasons cache data as mac=>last dereg reason
     *
     * @var array
     */
    protected $lastDeregCache = array();

    /**
     * Contains ONU indexes cache as mac=>oltid
     *
     * @var array
     */
    protected $onuIndexCache = array();

    /**
     * Contains ONU indexes cache as mac=>interface
     *
     * @var array
     */
    protected $interfaceCache = array();

    /**
     * Contains FDB indexes cache as id=>mac
     *
     * @var array
     */
    protected $FDBCache = array();

    /**
     * Contains ONU devices indexes cache as mac => devID
     *
     * @var array
     */
    protected $onuMACDevIDCache = array();

    /**
     * Contains ONU UNI ports cache as MAC/Serial => (EtherPort => Status)
     *
     * @var array
     */
    protected $uniOperStatsCache = array();

    /**
     * System alter.ini config stored as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * SNMPHelper object instance
     *
     * @var object
     */
    protected $snmp = '';

    /**
     * Prepared HTML for asterisk determining mandatory form field
     *
     * @var string
     */
    protected $sup = '';

    /**
     * Are QuickOLTLinks enabled?
     *
     * @var bool
     */
    protected $EnableQuickOLTLinks = false;

    /**
     * Are OLTs polled individually via AJAX?
     *
     * @var bool
     */
    protected $OLTIndividualRepollAJAX = false;

    /**
     * Is PON signal history charts spoiler initially closed?
     *
     * @var bool
     */
    protected $ONUChartsSpoilerClosed = false;

    /**
     * Is user search by MAC for unknown ONU registering form enabled?
     *
     * @var bool
     */
    protected $onuUknownUserByMACSearchShow = false;

    /**
     * Increment for user search by MAC telepathy for unknown ONU registering form
     *
     * @var string
     */
    protected $onuUknownUserByMACSearchIncrement = 0;

    /**
     * Is user search by MAC for unknown ONU registering form enabled mandatory?
     *
     * @var bool
     */
    protected $onuUknownUserByMACSearchShowAlways = false;

    /**
     * Is user search by MAC telepathy for unknown ONU registering form enabled?
     *
     * @var bool
     */
    protected $onuUknownUserByMACSearchTelepathy = false;

    /**
     * Is tab UI for ponizer active?
     *
     * @var bool
     */
    protected $ponizerUseTabUI = false;

    /**
     * Placeholder for onu MAC validation regex
     *
     * @var string
     */
    protected $onuMACValidateRegex = '/^([[:xdigit:]]{2}[\s:.-]?){5}[[:xdigit:]]{2}$/';

    /**
     * Perform ONU MAC validation against $onuMACValidateRegex?
     *
     * @var bool
     */
    protected $validateONUMACEnabled = false;

    /**
     * Replace ONU's MAC if invalid with a random one?
     *
     * @var string
     */
    protected $replaceInvalidONUMACWithRandom = false;

    /**
     * Show PON interfaces descriptions in main ONU list tab if present?
     *
     * @var bool
     */
    protected $showPONIfaceDescrMainTab = false;

    /**
     * Show PON interfaces descriptions in OLT stats tab if present?
     *
     * @var bool
     */
    protected $showPONIfaceDescrStatsTab = false;

    /**
     * Contains OLT PON interfaces description as $oltID => array($cleanIfaceName => $ifaceDescr)
     *
     * @var array
     */
    protected $ponIfaceDescrCache = array();

    /**
     * Placeholder for UbillingConfig object
     *
     * @var object
     */
    protected $ubConfig = null;

    /**
     * Array of MAC address of ONU devices which will be hidden from unknown ONU list
     *
     * @var array
     */
    protected $hideOnuMac = array();

    /**
     * OLT intefaces manual descriptions flag
     *
     * @var bool
     */
    protected $ponIfDescribe = false;

    /**
     * Deferred loading flag
     *
     * @var boolt
     */
    protected $deferredLoadingFlag = false;

    /**
     * Placeholder for PON_ONU_OFFLINE_SIGNAL alter.ini option
     *
     * @var string
     */
    protected $onuOfflineSignalLevel = '-9000';

    /**
     * PON interfaces object placeholder
     *
     * @var object
     */
    public $ponInterfaces = '';

    /**
     * IP column rendering flag
     *
     * @var bool
     */
    protected $ipColumnVisible = true;

    /**
     * Placeholder for PON_UKNKOWN_ONU_LLID_SHOW alter.ini option
     *
     * @var bool
     */
    protected $llidColVisibleUnknownONU = false;

    /**
     * Placeholder for PON_ONU_UNI_STATUS_ENABLED alter.ini option
     *
     * @var bool
     */
    protected $onuUniStatusEnabled = false;

    /**
     * Placeholder for PON_ONU_SERIAL_CASE_MODE alter.ini option
     *  0 - no case convert
     *  1 - lowercase
     *  2 - uppercase
     *
     * @var bool
     */
    protected $onuSerialCaseMode = 0;

    /**
     * Contains all busy ONU MAC/serials as lowercase onuIdent=>onuId
     *
     * @var array
     */
    protected $existingOnuIdents = array();

    /**
     * System message helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * System caching object placeholder
     *
     * @var object
     */
    protected $cache = '';

    /**
     * Onu data caching timeout
     *
     * @var int
     */
    protected $onuCacheTimeout = 0;

    /**
     * Contains instance of OltAttractor
     *
     * @var object
     */
    protected $oltData = '';

    /**
     * Quiet mode flag
     *
     * @var bool
     */
    protected $supressOutput = false;

    /**
     * ONUs database abstraction layer
     *
     * @var object
     */
    protected $onuDb = '';

    /**
     * ONUs database abstraction layer
     *
     * @var object
     */
    protected $onuExtUsersDb = '';

    /**
     * Contains process manager instance
     *
     * @var object
     */
    protected $stardust = '';

    /**
     * Some predefined paths, marks etc. 
     * This is here for legacy purpoces for external modules.
     */
    const SIGCACHE_PATH = OLTAttractor::SIGCACHE_PATH;
    const SIGCACHE_EXT = OLTAttractor::SIGCACHE_EXT;
    const DISTCACHE_PATH = OLTAttractor::DISTCACHE_PATH;
    const DISTCACHE_EXT = OLTAttractor::DISTCACHE_EXT;
    const ONUCACHE_PATH = OLTAttractor::ONUCACHE_PATH;
    const ONUCACHE_EXT = OLTAttractor::ONUCACHE_EXT;
    const INTCACHE_PATH = OLTAttractor::INTCACHE_PATH;
    const INTCACHE_EXT = OLTAttractor::INTCACHE_EXT;
    const INTDESCRCACHE_EXT = OLTAttractor::INTDESCRCACHE_EXT;
    const FDBCACHE_PATH = OLTAttractor::FDBCACHE_PATH;
    const FDBCACHE_EXT = OLTAttractor::FDBCACHE_EXT;
    const DEREGCACHE_PATH = OLTAttractor::DEREGCACHE_PATH;
    const DEREGCACHE_EXT = OLTAttractor::DEREGCACHE_EXT;
    const UPTIME_PATH = OLTAttractor::UPTIME_PATH;
    const UPTIME_EXT = OLTAttractor::UPTIME_EXT;
    const TEMPERATURE_PATH = OLTAttractor::TEMPERATURE_PATH;
    const TEMPERATURE_EXT = OLTAttractor::TEMPERATURE_EXT;
    const MACDEVIDCACHE_PATH = OLTAttractor::MACDEVIDCACHE_PATH;
    const MACDEVIDCACHE_EXT = OLTAttractor::MACDEVIDCACHE_EXT;
    const ONUSIG_PATH = OLTAttractor::ONUSIG_PATH;
    const POLL_PID = 'OLTPOLL_';
    const POLL_STATS = 'exports/pondata/races/PONYRUN_';
    const POLL_LOG = 'exports/oltpoll.log';

    /**
     * Other predefined constants
     */
    const SNMPCACHE = false;
    const SNMPPORT = 161;
    const TABLE_ONUS = 'pononu';
    const TABLE_SWITCHES = 'switches';
    const TABLE_ONUEXTUSERS = 'pononuextusers';
    const KEY_ALLONU = 'ALLONU';
    const KEY_ONUOLT = 'ONUOLTID_';
    const KEY_ONULISTAJ = 'ONULISTAJ_';
    const SNMP_TEMPLATES_PATH = 'config/snmptemplates/';
    const SNMP_PRIVATE_TEMPLATES_PATH = 'documents/mysnmptemplates/';

    /**
     * Some URLs here
     */
    const URL_ME = '?module=ponizer';
    const URL_ONULIST = '?module=ponizer&onulist=true';
    const URL_USERPROFILE = '?module=userprofile&username=';
    const URL_ONU = '?module=ponizer&editonu=';
    const ROUTE_GOTO_OLT='gotosomeoltid';

    /**
     * Views/stats coloring
     */
    const COLOR_OK = '#005502';
    const COLOR_AVG = '#FF5500';
    const COLOR_BAD = '#AB0000';
    const COLOR_NOSIG = '#000000';
    const NO_SIGNAL = 'Offline';
    const POLL_RUNNING = '🏁';

    /**
     * Creates new PONizer object instance
     * 
     * @param int $oltId load ONU data only for selected OLT. Loads all if empty.
     *
     * @return void
     */
    public function __construct($oltId = '') {
        global $ubillingConfig;
        $this->ubConfig = $ubillingConfig;

        $this->loadAlter();
        $this->initMessages();
        $this->initStarDust();
        $this->initOltAttractor();
        $this->initOnuDb();
        $this->initOnuExtUsersDb();
        $this->initCache();
        $this->loadOltDevices();
        $this->loadOltModels();
        $this->loadSnmpTemplates();
        $this->initSNMP();
        $this->loadOnu($oltId);
        $this->loadOnuExtUsers();
        $this->loadModels();
        $this->sup = wf_tag('sup') . '*' . wf_tag('sup', true);
        $this->EnableQuickOLTLinks = $this->ubConfig->getAlterParam('PON_QUICK_OLT_LINKS');
        $this->OLTIndividualRepollAJAX = $this->ubConfig->getAlterParam('PON_OLT_INDIVIDUAL_REPOLL_AJAX');
        $this->ONUChartsSpoilerClosed = $this->ubConfig->getAlterParam('PON_ONU_CHARTS_SPOILER_CLOSED');
        $this->onuUknownUserByMACSearchShow = $this->ubConfig->getAlterParam('PON_UONU_USER_BY_MAC_SEARCH_SHOW');
        $this->onuUknownUserByMACSearchIncrement = ($this->ubConfig->getAlterParam('PON_UONU_USER_BY_MAC_SEARCH_INCREMENT')) ? $this->ubConfig->getAlterParam('PON_UONU_USER_BY_MAC_SEARCH_INCREMENT') : 0;
        $this->onuUknownUserByMACSearchShowAlways = $this->ubConfig->getAlterParam('PON_UONU_USER_BY_MAC_SEARCH_SHOW_ALWAYS');
        $this->onuUknownUserByMACSearchTelepathy = $this->ubConfig->getAlterParam('PON_UONU_USER_BY_MAC_SEARCH_TELEPATHY');
        $this->ponizerUseTabUI = $this->ubConfig->getAlterParam('PON_UI_USE_TABS');
        $this->validateONUMACEnabled = $this->ubConfig->getAlterParam('PON_ONU_MAC_VALIDATE');
        $this->replaceInvalidONUMACWithRandom = $this->ubConfig->getAlterParam('PON_ONU_MAC_MAKE_RANDOM_IF_INVALID');
        $this->showPONIfaceDescrMainTab = $this->ubConfig->getAlterParam('PON_IFACE_DESCRIPTION_IN_MAINTAB');
        $this->showPONIfaceDescrStatsTab = $this->ubConfig->getAlterParam('PON_IFACE_DESCRIPTION_IN_STATSTAB');
        $this->ponIfDescribe = $this->ubConfig->getAlterParam('PON_IFDESC');
        $this->onuOfflineSignalLevel = $this->ubConfig->getAlterParam('PON_ONU_OFFLINE_SIGNAL', $this->onuOfflineSignalLevel);
        $this->deferredLoadingFlag = $this->ubConfig->getAlterParam('PON_DEFERRED_LOADING', false);
        $this->ipColumnVisible = ($this->ubConfig->getAlterParam('PONIZER_NO_IP_COLUMN')) ? false : true;
        $this->llidColVisibleUnknownONU = $this->ubConfig->getAlterParam('PON_UKNKOWN_ONU_LLID_SHOW', false);
        $this->onuUniStatusEnabled = $this->ubConfig->getAlterParam('PON_ONU_UNI_STATUS_ENABLED', false);
        $this->onuSerialCaseMode = $this->ubConfig->getAlterParam('PON_ONU_SERIAL_CASE_MODE', 0);

        if ($this->ponIfDescribe) {
            $this->ponInterfaces = new PONIfDesc();
        }
        //optional ONU MAC hiding
        if (@$this->altCfg['PON_ONU_HIDE']) {
            $tmpHideOnuList = explode(',', $this->altCfg['PON_ONU_HIDE']);
            $tmpHideOnuList = array_flip($tmpHideOnuList);
            $this->hideOnuMac = $tmpHideOnuList;
        }
    }

    /**
     * Loads system alter.ini config into private data property
     *
     * @return void
     */
    protected function loadAlter() {
        $this->altCfg = $this->ubConfig->getAlter();
    }

    /**
     * Inits system messages helper for further usage
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Inits anonymous OLT attractor instance for further usage
     * 
     * @return void
     */
    protected function initOltAttractor() {
        $this->oltData = new OLTAttractor();
    }

    /**
     * Inits ONUs database abstraction layer
     * 
     * @return void
     */
    protected function initOnuDb() {
        $this->onuDb = new NyanORM(self::TABLE_ONUS);
    }

    /**
     * Inits ONUs additional users database abstraction layer
     * 
     * @return void
     */
    protected function initOnuExtUsersDb() {
        $this->onuExtUsersDb = new NyanORM(self::TABLE_ONUEXTUSERS);
    }

    /**
     * Inits process manager
     * 
     * @return void
     */
    protected function initStarDust() {
        $this->stardust = new StarDust();
    }

    /**
     * Loads all available devices set as OLT
     *
     * @return void
     */
    protected function loadOltDevices() {
        $switchesDb = new NyanORM(self::TABLE_SWITCHES);
        $requiredFields = array('`id`', '`ip`', '`location`', '`snmp`', '`modelid`', '`desc`');
        $switchesDb->selectable($requiredFields);
        $switchesDb->where('desc', 'LIKE', '%OLT%');
        //custom field sorting?
        $oltLoadOrderField = $this->ubConfig->getAlterParam('PON_OLT_ORDER');
        if (!empty($oltLoadOrderField)) {
            //is fileld valid?
            if (array_search('`' . $oltLoadOrderField . '`', $requiredFields) !== false) {
                $switchesDb->orderBy($oltLoadOrderField, 'ASC');
            } else {
                show_error(__('Wrong value') . ' PON_OLT_ORDER: ' . '"' . $oltLoadOrderField . '"');
            }
        }

        $raw = $switchesDb->getAll();

        if (!empty($raw)) {
            foreach ($raw as $io => $each) {
                //OLT must have non empty community
                if (!empty($each['snmp'])) {
                    $this->allOltDevices[$each['id']] = $each['ip'] . ' - ' . $each['location'];
                    $this->allOltNames[$each['id']] = $each['location'];
                    $this->allOltModelIds[$each['id']] = $each['modelid'];
                    $this->allOltIps[$each['id']] = $each['ip'];

                    $this->allOltSnmp[$each['id']]['community'] = $each['snmp'];
                    $this->allOltSnmp[$each['id']]['modelid'] = $each['modelid'];
                    $this->allOltSnmp[$each['id']]['ip'] = $each['ip'];
                    $this->allOltSnmp[$each['id']]['nofdbquery'] = ispos($each['desc'], 'NOFDBQUERY');
                }
            }
        }
    }

    /**
     * Getter for allOltDevices array
     *
     * @return array
     */
    public function getAllOltDevices() {
        return $this->allOltDevices;
    }
    /**
     * Retrieves all OLT IP addresses.
     *
     * @return array An array containing all OLT IP addresses.
     */
    public function getAllOltIps() {
        return $this->allOltIps;
    }
    /**
     * Retrieves all OLT model IDs.
     *
     * @return array An array containing all OLT model IDs.
     */
    public function getAllOltModelIds() {
        return $this->allOltModelIds;
    }

    /**
     * Loads all available snmp models data into private data property
     *
     * @return void
     */
    protected function loadOltModels() {
        $rawModels = zb_SwitchModelsGetAll();
        if (!empty($rawModels)) {
            foreach ($rawModels as $io => $each) {
                $this->allOltModels[$each['id']]['modelname'] = $each['modelname'];
                $this->allOltModels[$each['id']]['snmptemplate'] = $each['snmptemplate'];
                $this->allOltModels[$each['id']]['ports'] = $each['ports'];
            }
        }
    }

    /**
     * Performs SNMP templates preprocessing for OLT devices
     *
     * @return void
     */
    protected function loadSnmpTemplates() {
        if (!empty($this->allOltDevices)) {
            foreach ($this->allOltDevices as $oltId => $eachOltData) {
                if (isset($this->allOltSnmp[$oltId])) {
                    $oltModelid = $this->allOltSnmp[$oltId]['modelid'];
                    if ($oltModelid) {
                        if (isset($this->allOltModels[$oltModelid])) {
                            $templateFileName = $this->allOltModels[$oltModelid]['snmptemplate'];
                            if (!empty($templateFileName)) {
                                $basicTemplateFile = self::SNMP_TEMPLATES_PATH . $templateFileName;
                                $privateTemplateFile = DATA_PATH . self::SNMP_PRIVATE_TEMPLATES_PATH . $templateFileName;
                                //loading basic SNMP template
                                if (file_exists($basicTemplateFile)) {
                                    $this->snmpTemplates[$oltModelid] = rcms_parse_ini_file($basicTemplateFile, true);
                                }
                                //custom SNMP templates is separated and overrides original templates
                                if (file_exists($privateTemplateFile)) {
                                    $this->snmpTemplates[$oltModelid] = rcms_parse_ini_file($privateTemplateFile, true);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Creates single instance of SNMPHelper object
     *
     * @return void
     */
    protected function initSNMP() {
        $this->snmp = new SNMPHelper();
    }

    /**
     * Inits system caching engine
     * 
     * @return void
     */
    protected function initCache() {
        $this->cache = new UbillingCache();
        $this->onuCacheTimeout = $this->ubConfig->getAlterParam('PON_ONU_CACHING', 0);
        if ($this->onuCacheTimeout) {
            $this->onuCacheTimeout = $this->onuCacheTimeout * 60; //in minutes
        }
    }

    /**
     * Try to detect ONU id by assigned users login
     *
     * @param string $login
     * @return int/bool
     */
    public function getOnuIdByUser($login) {
        $result = 0;
        if (!empty($this->allOnu)) {
            foreach ($this->allOnu as $io => $each) {
                if ($each['login'] == $login) {
                    $result = $each['id'];
                    break;
                }
            }

            if (!empty($this->allOnuExtUsers)) {
                foreach ($this->allOnuExtUsers as $io => $each) {
                    if ($each['login'] == $login) {
                        $result = $each['onuid'];
                        break;
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Trys to detect all ONU IDs by assigned users login as idx=>onuId
     *
     * @param string $login
     * 
     * @return array
     */
    public function getOnuIdByUserAll($login) {
        $result = array();
        if (!empty($this->allOnu)) {
            foreach ($this->allOnu as $io => $each) {
                if ($each['login'] == $login) {
                    $result[] = $each['id'];
                }
            }

            if (!empty($this->allOnuExtUsers)) {
                foreach ($this->allOnuExtUsers as $io => $each) {
                    if ($each['login'] == $login) {
                        $result[] = $each['onuid'];
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Returns array of ONUs assigned on some OLT
     *
     * @param string $OltId
     * @return array
     */
    protected function getOnuArrayByOltID($OltId = '') {
        $result = array();
        if (!empty($this->allOnu) and !empty($OltId)) {
            foreach ($this->allOnu as $io => $each) {
                if ($each['oltid'] == $OltId) {
                    $result[$io] = $each;
                }
            }
        }
        return ($result);
    }

    /**
     * Performs OLT device polling via PON HAL instance
     *
     * @param int $oltid Existing OLT id to perform polling
     * @param bool $quiet dont output debug info to viewport
     *
     * @return void
     */
    public function pollOltSignal($oltid, $quiet = false) {
        $oltid = ubRouting::filters($oltid, 'int');
        $this->supressOutput = $quiet;
        $this->logPoll($oltid, 'STARTING: polling');
        if (isset($this->allOltDevices[$oltid])) {
            if (isset($this->allOltSnmp[$oltid])) {
                $this->flushOnuAjListCache($oltid);
                $oltCommunity = $this->allOltSnmp[$oltid]['community'];
                $oltModelId = $this->allOltSnmp[$oltid]['modelid'];
                $oltIp = $this->allOltSnmp[$oltid]['ip'];
                $oltNoFDBQ = $this->allOltSnmp[$oltid]['nofdbquery'];
                if (isset($this->snmpTemplates[$oltModelId])) {
                    $this->logPoll($oltid, 'Using device SNMP template "' . $this->snmpTemplates[$oltModelId]['define']['DEVICE'] . '"');
                    if (isset($this->snmpTemplates[$oltModelId]['signal'])) {
                        //logging collector signalmode and collector
                        $logTemplate = 'Template mode:"' . $this->snmpTemplates[$oltModelId]['signal']['SIGNALMODE'] . '" ';
                        $logTemplate .= 'collector name:"' . @$this->snmpTemplates[$oltModelId]['signal']['COLLECTORNAME'] . '"';
                        $this->logPoll($oltid, $logTemplate);
                        //preventing simultaneously device polling within different processes
                        if (!$this->isPollingLocked($oltid)) {
                            //prefilling polling stats
                            $pollingStartTime = time();
                            $this->pollingStatsUpdate($oltid, $pollingStartTime, 0, false);

                            $collector = '';
                            $collectorName = '';
                            $collectorMethod = 'collect';
                            $oltParameters = array(
                                'MODELID' => $oltModelId,
                                'ID' => $oltid,
                                'IP' => $oltIp,
                                'COMMUNITY' => $oltCommunity,
                                'NOFDB' => $oltNoFDBQ,
                                'TYPE' => 'PON'
                            );

                            switch ($this->snmpTemplates[$oltModelId]['signal']['SIGNALMODE']) {
                                /**
                                 * Switchable OLT devices polling abstraction layer
                                 */
                                case 'HAL':
                                    //setting collector class name
                                    $collectorName = $this->snmpTemplates[$oltModelId]['signal']['COLLECTORNAME'];
                                    //setting optional primary collector method name to call
                                    if (isset($this->snmpTemplates[$oltModelId]['signal']['COLLECTORMETHOD'])) {
                                        $collectorMethod = $this->snmpTemplates[$oltModelId]['signal']['COLLECTORMETHOD'];
                                    }
                                    break;
                                /**
                                     * Following cases is legacy for old or custom device templates 
                                     * without collector hardware abstraction layer specified explictly
                                     */
                                case 'BDCOM':
                                    /**
                                     * BDCOM/Eltex/Extralink devices polling
                                     */
                                    $collectorName = 'PONBdcom';
                                    break;
                                case 'GPBDCOM':
                                    /**
                                     * BDCOM GP3600
                                     */
                                    $collectorName = 'PONBdcomGP';
                                    break;
                                case 'STELS12':
                                    /**
                                     * Stels FD12XX devices polling
                                     */
                                    $collectorName = 'PONStels12';
                                    break;
                                case 'STELSFD':
                                    /**
                                     * Stels FD11XX devices polling
                                     */
                                    $collectorName = 'PONStels11';
                                    break;
                                case 'VSOL':
                                    /**
                                     * V-Solution 1600D devices polling
                                     */
                                    $collectorName = 'PONVsol';
                                    break;

                                /**
                                     * ZTE-like EPON OLTs polling
                                     */
                                case 'ZTE':
                                    $collectorName = 'PonZte';
                                    $collectorMethod = 'pollEpon';
                                    $oltParameters['TYPE'] = 'EPON';

                                    break;
                                /**
                                     * ZTE GPON OLTs polling
                                     */
                                case 'ZTE_GPON':
                                    $collectorName = 'PonZte';
                                    $collectorMethod = 'pollGpon';
                                    $oltParameters['TYPE'] = 'GPON';
                                    break;
                                /**
                                     * Huawei EPON OLTs polling
                                     */
                                case 'HUAWEI_GPON':
                                    $collectorName = 'PonZte';
                                    $collectorMethod = 'huaweiPollGpon';
                                    $oltParameters['TYPE'] = 'GPON';
                                    break;
                            }

                            //Run OLT HAL instance for device polling
                            if (!empty($collectorName)) {
                                if (class_exists($collectorName)) {
                                    $collector = new $collectorName($oltParameters, $this->snmpTemplates);
                                    $logCollector = 'Polling prepare using PON HAL collector:"' . $collectorName . '" ';
                                    $logCollector .= 'with parameters OLT ID: ' . $oltParameters['ID'] . ' IP:' . $oltParameters['IP'];
                                    $this->logPoll($oltid, $logCollector);
                                    if (method_exists($collector, 'setOfflineSignal')) {
                                        $collector->setOfflineSignal($this->onuOfflineSignalLevel);
                                    }
                                    if (method_exists($collector, $collectorMethod)) {
                                        $this->logPoll($oltid, 'RUNNING: PON HAL collector method:' . $collectorName . '->' . $collectorMethod);
                                        $collector->$collectorMethod();
                                        $this->logPoll($oltid, 'COMPLETED: PON HAL collector method:' . $collectorName . '->' . $collectorMethod);
                                    } else {
                                        $this->logPoll($oltid, 'FAILED run PON HAL collector:' . $collectorName . '->' . $collectorMethod . ' METHOD_NOT_EXISTS');
                                    }
                                } else {
                                    $this->logPoll($oltid, 'FATAL run PON HAL collector:' . $collectorName . ' EX_HAL_COLLECTOR_NOT_EXISTS');
                                }
                            } else {
                                $this->logPoll($oltid, 'Failed: collector name not defined');
                            }


                            //finishing OLT polling stats
                            $pollingEndTime = time();
                            $this->pollingStatsUpdate($oltid, $pollingStartTime, $pollingEndTime, true);
                            $this->logPoll($oltid, 'FINISHED: polled successfully');
                        } else {
                            $this->logPoll($oltid, 'FINISHED: skipped, polling already in progress');
                        }
                    } else {
                        $this->logPoll($oltid, 'Failed polling due signal section is not exists');
                    }
                } else {
                    $this->logPoll($oltid, 'SKIPPING MODELID:' . $oltModelId . ' NO_SNMP_TEMPLATE_BODY');
                }
            } else {
                $this->logPoll($oltid, 'SKIPPING No snmp data for this OLT');
            }
        } else {
            $this->logPoll($oltid, 'SKIPPING Not in OLT devices list');
        }
    }

    /**
     * Performs available OLT devices polling. Use only in remote API.
     *
     * @param bool $quiet dont output debug data into viewport
     *
     * @return void
     */
    public function oltDevicesPolling($quiet = false) {
        if (!empty($this->allOltDevices)) {
            foreach ($this->allOltDevices as $oltid => $each) {
                if (!$quiet) {
                    print('POLLING:' . $oltid . ' ' . $each . PHP_EOL);
                }

                if (@!$this->altCfg['HERD_OF_PONIES']) {
                    $this->pollOltSignal($oltid, $quiet);
                } else {
                    //starting herd of apocalypse pony here!
                    $herdTimeout = 0;
                    if ($this->altCfg['HERD_OF_PONIES'] > 1) {
                        $herdTimeout = ubRouting::filters($this->altCfg['HERD_OF_PONIES'], 'int');
                    }
                    $this->stardust->runBackgroundProcess('/bin/ubapi "herd&oltid=' . $oltid . '"', $herdTimeout);
                }
            }
        }
    }

    /**
     * Fast check some OLT for running collector process.
     * 
     * @param int $oltId Existing OLT device ID
     * 
     * @return bool
     */
    protected function isPollingNow($oltId) {
        $result = false;
        //Не плач, моє серце, не плач,
        //Не муч душу свою картонну!
        $pollingStats = $this->pollingStatsRead($oltId);
        if (!empty($pollingStats)) {
            $result = $pollingStats['finished'] ? false : true;
        }
        return ($result);
    }

    /**
     * Returns polling stats for some OLT
     * 
     * @param int $oltId
     * 
     * @return array
     */
    protected function pollingStatsRead($oltId) {
        $result = array();
        $statsPath = self::POLL_STATS . $oltId;
        if (file_exists($statsPath)) {
            $resultRaw = file_get_contents($statsPath);
            $result = json_decode($resultRaw, true);
        }
        return ($result);
    }

    /**
     * Updates some OLT polling stats
     * 
     * @param int $oltId Existing OLT ID
     * @param int $pollingStartTime polling start timestame
     * @param int $pollingEndTime polling end timestamp
     * @param bool $finished polling finished or not flag
     * 
     * @return void
     */
    protected function pollingStatsUpdate($oltId, $pollingStartTime = 0, $pollingEndTime = 0, $finished = false) {
        $oltId = ubRouting::filters($oltId, 'int');
        $statsPath = self::POLL_STATS . $oltId;
        $finishedData = ($finished) ? 1 : 0;
        $dataToSave['start'] = $pollingStartTime;
        $dataToSave['end'] = $pollingEndTime;
        $dataToSave['finished'] = $finishedData;
        $dataToSave = json_encode($dataToSave);
        file_put_contents($statsPath, $dataToSave);
        //collector process locking and releasing of locks here
        if ($finished) {
            //release lock
            $this->stardust->setProcess(self::POLL_PID . $oltId);
            $this->stardust->stop();
        } else {
            //set lock for polling of some OLT
            $this->stardust->setProcess(self::POLL_PID . $oltId);
            $this->stardust->start();
        }
    }

    /**
     * Performs logging of OLT polling 
     * 
     * @param int $oltId
     * @param string $logData
     * 
     * @return void
     */
    public function logPoll($oltId, $logData) {
        $curdate = curdatetime();
        $logData = $curdate . ' | OLT[' . $oltId . '] | ' . $logData . PHP_EOL;
        if (!$this->supressOutput) {
            print($logData); // for manual debug of oltpoll and herd remoteapi calls
        }
        file_put_contents(self::POLL_LOG, $logData, FILE_APPEND);
    }

    /**
     * Performs check of OLT polling lock via DB. 
     * Using this only for checks of possibility real collector runs.
     * 
     * @param int $oltId
     * 
     * @return bool 
     */
    protected function isPollingLocked($oltId) {
        $oltId = ubRouting::filters($oltId, 'int');
        $this->stardust->setProcess(self::POLL_PID . $oltId);
        $result = $this->stardust->isRunning();
        return ($result);
    }

    /**
     * Returns some polllog viewer controls
     * 
     * @return string
     */
    public function renderLogControls() {
        $result = '';
        $result .= wf_BackLink(self::URL_ME . '&oltstats=true') . ' ';
        $result .= wf_Link(self::URL_ME . '&polllogs=true', wf_img('skins/log_icon_small.png') . ' ' . __('Log'), false, 'ubButton') . '';
        $result .= wf_Link(self::URL_ME . '&polllogs=true&zenlog=true', wf_img('skins/zen.png') . ' ' . __('Zen'), false, 'ubButton') . '';
        return ($result);
    }

    /**
     * Renders last lines from OLT polling log
     * 
     * @return string
     */
    public function renderPollingLog() {
        $result = '';
        $renderLimit = 100;
        if (file_exists(self::POLL_LOG)) {
            $rawLog = zb_ReadLastLines(self::POLL_LOG, $renderLimit);
            if (!empty($rawLog)) {
                $rawLog = explodeRows($rawLog);
                $rawLog = array_reverse($rawLog);
                if (!empty($rawLog)) {
                    $cells = wf_TableCell(__('Date'));
                    $cells .= wf_TableCell(__('OLT'));
                    $cells .= wf_TableCell(__('Event'));
                    $rows = wf_TableRow($cells, 'row1');
                    foreach ($rawLog as $io => $eachLine) {
                        if (!empty($eachLine)) {
                            $eachLine = explode('|', $eachLine);
                            //normal format: time|OLT|event
                            if (sizeof($eachLine) == 3) {
                                $oltId = ubRouting::filters($eachLine[1], 'int');
                                $cells = wf_TableCell($eachLine[0]);
                                $cells .= wf_TableCell('[' . $oltId . '] ' . @$this->allOltDevices[$oltId]);
                                $cells .= wf_TableCell($eachLine[2]);
                                $rows .= wf_TableRow($cells, 'row5');
                            }
                        }
                    }
                    $result .= wf_TableBody($rows, '100%', 0, 'sortable');
                }
            } else {
                $result .= $this->messages->getStyledMessage(__('Nothing to show') . ': ' . __('Logs') . ' ' . __('is empty'), 'warning');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show') . ': ' . __('OLT polling log') . ' ' . __('does not exist'), 'warning');
        }
        return ($result);
    }

    /**
     * Renders some ONUs navigation list
     * 
     * @param array $onuArr
     * @param string $customOnuUrl
     * 
     * @return string
     */
    public function renderOnuNavBar($onuArr, $customOnuUrl = '') {
        $result = '';
        if (!empty($onuArr)) {
            $result .= wf_tag('div');
            foreach ($onuArr as $io => $eachOnuId) {
                if (isset($this->allOnu[$eachOnuId])) {
                    $onuData = $this->allOnu[$eachOnuId];
                    if ($customOnuUrl) {
                        $onuUrl = $customOnuUrl . $eachOnuId;
                    } else {
                        $onuUrl = self::URL_ONU . $eachOnuId;
                    }
                    $onuLabel = '';
                    if (!empty($onuData['mac'])) {
                        $onuLabel .= ' ' . $onuData['mac'];
                    }
                    if (!empty($onuData['serial'])) {
                        $onuLabel .= ' ' . $onuData['serial'];
                    }
                    $result .= wf_tag('div', false, 'dashtask');
                    $result .= wf_Link($onuUrl, wf_img('skins/onudev.png'));
                    $result .= wf_delimiter(0);
                    $result .= __('ONU') . ' ' . $onuLabel;
                    $result .= wf_tag('div', true);
                }
            }
            $result .= wf_tag('div', true);
            $result .= wf_CleanDiv();
        } else {
            $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        return ($result);
    }

    /**
     * Loads avaliable ONUs from database into private data property
     * 
     * @param int $oltId load ONU only for selected OLT
     *
     * @return void
     */
    protected function loadOnu($oltId = '') {
        $oltId = ubRouting::filters($oltId, 'int');
        $fromCache = false;

        if ($this->onuCacheTimeout) {
            //specific OLT ONU data
            if ($oltId) {
                $cachedOnus = $this->cache->get(self::KEY_ONUOLT . $oltId, $this->onuCacheTimeout);
                if (!empty($cachedOnus)) {
                    $all = $cachedOnus;
                    $fromCache = true;
                }
            } else {
                //all OLTs ONU data
                $cachedOnus = $this->cache->get(self::KEY_ALLONU, $this->onuCacheTimeout);
                if (!empty($cachedOnus)) {
                    $all = $cachedOnus;
                    $fromCache = true;
                }
            }
        }

        //perform database query if no cached data available
        if (!$fromCache) {
            //optional OLT ID filter
            if ($oltId) {
                $this->onuDb->where('oltid', '=', $oltId);
            }
            $all = $this->onuDb->getAll();
        }

        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allOnu[$each['id']] = $each;
                $this->onuMacIdList[$each['mac']] = $each['id'];
                $this->onuSerialIdList[$each['serial']] = $each['id'];
                $this->onuMacOltidList[$each['mac']] = $each['oltid'];
                $this->onuSerialOltidList[$each['serial']] = $each['oltid'];
                //filling used onuIdents array
                $this->existingOnuIdents[strtolower($each['mac'])] = $each['id'];
                $this->existingOnuIdents[strtolower($each['serial'])] = $each['id'];
            }
        }

        //cache requires update
        if ($this->onuCacheTimeout and !$fromCache) {
            if ($oltId) {
                $this->cache->set(self::KEY_ONUOLT . $oltId, $all, $this->onuCacheTimeout);
            } else {
                //all OLTs ONU data
                $this->cache->set(self::KEY_ALLONU, $all, $this->onuCacheTimeout);
            }
        }
    }

    /**
     * Loads avaliable ONUs additional users bindings from database into private data property
     *
     * @return void
     */
    protected function loadOnuExtUsers() {
        $this->allOnuExtUsers = $this->onuExtUsersDb->getAll('id');
    }

    /**
     * Returns Available OLT devices ONU counts
     *
     * @return string
     */
    public function getOltOnuCounts() {
        $result = array();
        if (!empty($this->allOnu)) {
            foreach ($this->allOnu as $io => $each) {
                if (isset($result[$each['oltid']])) {
                    $result[$each['oltid']]++;
                } else {
                    $result[$each['oltid']] = 1;
                }
            }
        }
        return ($result);
    }

    /**
     * Returns int for ONU has or has not some of subscribers login assignment
     * May return array with status, login and OLT location and IP
     *
     * 0 - has no assignment
     * 1 - has assignment, but login does not exist
     * 2 - has assignment
     *
     * @param int $onuid
     * @param bool $getLogin
     * @param bool $getOLTData
     *
     * @return int|array
     */
    public function checkONUAssignment($onuid, $getLogin = false, $getOLTData = false) {
        $result = 0;
        $tLogin = '';
        $oltData = '';
        $tArray = array();

        if (isset($this->allOnu[$onuid])) {
            $onuRec = $this->allOnu[$onuid];

            if (!empty($onuRec)) {
                $tLogin = $onuRec['login'];

                if (empty($tLogin)) {
                    $result = 1;
                } else {
                    $query = "SELECT * from `users` WHERE `login`='" . $tLogin . "'";
                    $loginRec = simple_query($query);

                    (empty($loginRec)) ? $result = 1 : $result = 2;
                }

                if ($getOLTData and isset($this->allOltDevices[$onuRec['oltid']])) {
                    $oltData = $this->allOltDevices[$onuRec['oltid']];
                }
            }
        }

        if ($getLogin or $getOLTData) {
            $tArray['status'] = $result;
            $tArray['login'] = $tLogin;
            $tArray['oltdata'] = $oltData;
            $result = $tArray;
        }

        return ($result);
    }

    /**
     * Getter for loaded ONU devices as id=>onuData
     *
     * @return array
     */
    public function getAllOnu() {
        return ($this->allOnu);
    }

    /**
     * Returns ONU ID by ONU MAC or serial
     *
     * @param string $onuIdent existing ONU MAC or serial 
     *
     * @return int/0 - on not found
     */
    public function getOnuIDbyIdent($onuIdent) {
        $result = 0;
        $onuIdent = strtolower($onuIdent);
        $sn = strtoupper($onuIdent);

        if (!empty($this->onuMacIdList)) {
            if (isset($this->onuMacIdList[$onuIdent])) {
                $result = $this->onuMacIdList[$onuIdent];
            }
        }

        if (!empty($this->onuSerialIdList)) {
            if (isset($this->onuSerialIdList[$sn])) {
                $result = $this->onuSerialIdList[$sn];
            }
        }

        return ($result);
    }

    /**
     * Performs search in nethosts for a MAC and a login linked to it
     *
     * @param string $mac
     * @param int $macIncrementWith
     *
     * @return array
     */
    public function getUserByONUMAC($mac, $macIncrementWith = 0, $doSerialize = false) {
        if (!empty($macIncrementWith)) {
            $macAsHex = str_replace(':', '', $mac);
            $macAsHex = dechex(hexdec($macAsHex) + $macIncrementWith);
            $macAsHex = (strlen($macAsHex) < 12) ? str_pad($macAsHex, 12, '0', STR_PAD_LEFT) : $macAsHex;

            $mac = implode(":", str_split($macAsHex, 2));
        }

        $query = "SELECT `users`.`login`, `users`.`ip`, `nethosts`.`mac` FROM `users` RIGHT JOIN `nethosts` USING(ip) WHERE mac = '" . $mac . "'";
        $queryResult = simple_queryall($query);

        if (empty($queryResult)) {
            //$result = array('login' => '', 'ip' => '');
            $result = array();
        } else {
            $result = $queryResult[0];
        }

        $result = ($doSerialize) ? json_encode($result) : $result;

        return ($result);
    }

    /**
     * Loads available device models from database
     *
     * @return void
     */
    protected function loadModels() {
        $tmpModels = zb_SwitchModelsGetAll();
        if (!empty($tmpModels)) {
            foreach ($tmpModels as $io => $each) {
                $this->allModelsData[$each['id']] = $each;
            }
        }
    }

    /**
     * Getter for allModelsData array
     *
     * @return array
     */
    public function getAllModelsData() {
        return $this->allModelsData;
    }

    /**
     * Returns model name by its id
     *
     * @param int $id
     * @return string
     */
    protected function getModelName($id) {
        $result = '';
        if (isset($this->allModelsData[$id])) {
            $result = $this->allModelsData[$id]['modelname'];
        }
        return ($result);
    }

    /**
     * Returns model ports count by its id
     *
     * @param int $id
     *
     * @return string
     */
    protected function getModelPorts($id) {
        $result = '';
        if (isset($this->allModelsData[$id])) {
            $result = $this->allModelsData[$id]['ports'];
        }
        return ($result);
    }

    /**
     * Check ONU MAC address unique or not?
     *
     * @param string $mac
     * @return bool
     */
    public function checkMacUnique($mac) {
        $mac = strtolower($mac);
        $result = true;
        if (!empty($this->allOnu)) {
            foreach ($this->allOnu as $io => $each) {
                if ($each['mac'] == $mac) {
                    $result = false;
                    break;
                }
            }
        }
        return ($result);
    }

    /**
     * Check ONU MAC address or Serial unique or not?
     *
     * @param string $onuIdent
     * 
     * @return bool
     */
    public function checkOnuUnique($onuIdent) {
        $result = true;
        if (!empty($onuIdent)) {
            $onuIdent = strtolower($onuIdent);
            // We are heroes
            // Heroes of the night
            // We are ready to live forevermore
            // Our gods lead us through this fight
            if (isset($this->existingOnuIdents[$onuIdent])) {
                $result = false;
            }
        }
        return ($result);
    }

    /**
     * Flushes all ONU related cache keys
     * 
     * @return void
     */
    public function flushOnuCache() {
        if ($this->onuCacheTimeout) {
            $this->cache->delete(self::KEY_ALLONU);
            $allCacheKeys = $this->cache->getAllcache();
            if (!empty($allCacheKeys)) {
                foreach ($allCacheKeys as $io => $eachKey) {
                    if (ispos($eachKey, self::KEY_ONULISTAJ) or ispos($eachKey, self::KEY_ONUOLT)) {
                        $cleanKey = str_replace(UbillingCache::CACHE_PREFIX, '', $eachKey);
                        $this->cache->delete($cleanKey);
                    }
                }
            }
        }
    }

    /**
     * Flushes some OLT precached list
     * 
     * @param int $oltId
     * 
     * @return void
     */
    public function flushOnuAjListCache($oltId) {
        if ($this->onuCacheTimeout) {
            if (!empty($oltId)) {
                $allCacheKeys = $this->cache->getAllcache();
                if (!empty($allCacheKeys)) {
                    foreach ($allCacheKeys as $io => $eachKey) {
                        if ((UbillingCache::CACHE_PREFIX . self::KEY_ONULISTAJ . $oltId) == $eachKey) {
                            $cleanKey = str_replace(UbillingCache::CACHE_PREFIX, '', $eachKey);
                            $this->cache->delete($cleanKey);
                        }
                    }
                }
            }
        }
    }

    /**
     * Creates new ONU in database and returns it Id or 0 if action fails
     *
     * @param int $onumodelid
     * @param int $oltid
     * @param string $ip
     * @param string $mac
     * @param string $serial
     * @param string $login
     *
     * @return int/0 - if something went wrong
     */
    public function onuCreate($onumodelid, $oltid, $ip, $mac, $serial, $login) {
        $macF = strtolower($mac);
        $macF = trim($macF);
        $macF = ubRouting::filters($macF, 'mres');
        $onumodelid = ubRouting::filters($onumodelid, 'int');
        $oltid = ubRouting::filters($oltid, 'int');
        $ip = ubRouting::filters($ip, 'mres');
        $serial = ubRouting::filters($serial, 'mres');

        if ($this->onuSerialCaseMode == 1) {
            $serial = strtolower($serial);
        } elseif ($this->onuSerialCaseMode == 2) {
            $serial = strtoupper($serial);
        }

        $login = trim($login);
        $login = ubRouting::filters($login, 'mres');

        $newId = 0;
        $modelid = @$this->allOltSnmp[$oltid]['modelid'];
        //empty MAC workaround for GPON devices
        if (empty($macF) and !empty($serial)) {
            $macF = zb_MacGetRandom();
            log_register('PON CREATE ONU MAC EMPTY TRY REPLACED WITH `' . $macF . '`');
        }

        if (!empty($macF)) {
            if ($this->checkOnuUnique($macF) and $this->checkOnuUnique($serial)) {
                if (check_mac_format($macF)) {
                    $this->onuDb->data('onumodelid', $onumodelid);
                    $this->onuDb->data('oltid', $oltid);
                    $this->onuDb->data('ip', $ip);
                    $this->onuDb->data('mac', $macF);
                    $this->onuDb->data('serial', $serial);
                    $this->onuDb->data('login', $login);
                    $this->onuDb->create();

                    $newId = $this->onuDb->getLastId();
                    log_register('PON CREATE ONU [' . $newId . '] MAC `' . $macF . '` SERIAL `' . $serial . '`');
                } else {
                    log_register('PON CREATE ONU MACINVALID TRY `' . $mac . '`');
                }
            } else {
                log_register('PON CREATE ONU DUPLICATE TRY `' . $macF . '` SERIAL `' . $serial . '`');
            }
        } else {
            log_register('PON CREATE ONU MAC EMPTY TRY');
        }

        $this->flushOnuCache();
        return ($newId);
    }

    /**
     * Saves ONU changes into database
     *
     * @param int $onuId
     * @param int $onumodelid
     * @param int $oltid
     * @param string $ip
     * @param string $mac
     * @param string $serial
     * @param string $login
     * @param string $geo
     *
     * @return void
     */
    public function onuSave($onuId, $onumodelid, $oltid, $ip, $mac, $serial, $login, $geo='') {
        $macF = strtolower($mac);
        $macF = trim($macF);
        $macF = ubRouting::filters($macF, 'mres');
        $onuId = ubRouting::filters($onuId, 'int');
        $onumodelid = ubRouting::filters($onumodelid, 'int');
        $oltid = ubRouting::filters($oltid, 'int');
        $ip = ubRouting::filters($ip, 'mres');
        $onuId = ubRouting::filters($onuId, 'int');

        $serial = ubRouting::filters($serial, 'mres');
        $login = ubRouting::filters($login, 'mres');
        $login = trim($login);

        $currentMac = $this->allOnu[$onuId]['mac'];
        $currentSerial = $this->allOnu[$onuId]['serial'];
        $currentLogin = $this->allOnu[$onuId]['login'];

        $this->onuDb->where('id', '=', $onuId);
        $this->onuDb->data('onumodelid', $onumodelid);
        $this->onuDb->data('oltid', $oltid);
        $this->onuDb->data('ip', $ip);
        $this->onuDb->data('geo', $geo);
        

        if (!empty($macF)) {
            if (check_mac_format($macF)) {

                if ($currentMac != $macF) {
                    if ($this->checkOnuUnique($macF)) {
                        $this->onuDb->data('mac', $macF);
                    } else {
                        log_register('PON MACDUPLICATE TRY `' . $mac . '`');
                    }
                }
            } else {
                log_register('PON MACINVALID TRY `' . $mac . '`');
            }
        } else {
            log_register('PON MACEMPTY TRY `' . $mac . '`');
        }

        if ($currentSerial != $serial) {
            if ($this->checkOnuUnique($serial)) {
                $this->onuDb->data('serial', $serial);
                if (!empty($serial)) {
                    if (empty($currentSerial)) {
                        log_register('PON EDIT ONU [' . $onuId . '] SET SERIAL `' . $serial . '`');
                    } else {
                        log_register('PON EDIT ONU [' . $onuId . '] SET SERIAL `' . $serial . '` INSTEAD `' . $currentSerial . '`');
                    }
                } else {
                    if (empty($currentSerial)) {
                        log_register('PON EDIT ONU [' . $onuId . '] SET SERIAL EMPTY');
                    } else {
                        log_register('PON EDIT ONU [' . $onuId . '] SET SERIAL EMPTY INSTEAD `' . $currentSerial . '`');
                    }
                }
            } else {
                log_register('PON SERIALDUPLICATE TRY `' . $serial . '`');
            }
        }

        if ($currentLogin != $login) {
            $this->onuDb->data('login', $login);
            if (!empty($login)) {
                if (empty($currentLogin)) {
                    log_register('PON EDIT ONU [' . $onuId . '] SET LOGIN (' . $login . ')');
                } else {
                    log_register('PON EDIT ONU [' . $onuId . '] SET LOGIN (' . $login . ') INSTEAD (' . $currentLogin . ')');
                }
            } else {
                if (empty($currentLogin)) {
                    log_register('PON EDIT ONU [' . $onuId . '] SET LOGIN EMPTY');
                } else {
                    log_register('PON EDIT ONU [' . $onuId . '] SET LOGIN EMPTY INSTEAD (' . $currentLogin . ')');
                }
            }
        }

        $this->onuDb->save();

        log_register('PON EDIT ONU [' . $onuId . '] MAC `' . $mac . '`');
        $this->flushOnuCache();
    }

    /**
     * Assigns exinsting ONU with some login
     *
     * @param int $onuid
     * @param string $login
     *
     * @return void
     */
    public function onuAssign($onuid, $login) {
        $onuid = ubRouting::filters($onuid, 'int');
        if (isset($this->allOnu[$onuid])) {
            $this->onuDb->where('id', '=', $onuid);
            $this->onuDb->data('login', $login);
            $this->onuDb->save();
            log_register('PON ASSIGN ONU [' . $onuid . '] WITH (' . $login . ')');
            $this->flushOnuCache();
        } else {
            log_register('PON ASSIGN ONU [' . $onuid . '] FAILED');
        }
    }

    /**
     * Deletes onu from database by its ID
     *
     * @param int $onuId
     */
    public function onuDelete($onuId) {
        $onuId = ubRouting::filters($onuId, 'int');
        $this->onuDb->where('id', '=', $onuId);
        $this->onuDb->delete();
        log_register('PON DELETE ONU [' . $onuId . ']');
        $this->flushOnuCache();
    }

    /**
     * Returns ONU creation form
     *
     * @return string
     */
    protected function onuCreateForm() {
        $models = array();
        $result = '';
        if (!empty($this->allModelsData)) {
            foreach ($this->allModelsData as $io => $each) {
                if (@$this->altCfg['ONUMODELS_FILTER']) {
                    if (ispos($each['modelname'], 'ONU')) {
                        $models[$each['id']] = $each['modelname'];
                    }
                } else {
                    $models[$each['id']] = $each['modelname'];
                }
            }
        }

        if (!empty($this->allOltDevices)) {
            if (!empty($models)) {
                $inputs = wf_HiddenInput('createnewonu', 'true');
                $inputs .= wf_Selector('newoltid', $this->allOltDevices, __('OLT device') . $this->sup, '', true);
                $inputs .= wf_Selector('newonumodelid', $models, __('ONU model') . $this->sup, '', true);
                if (@$this->altCfg['PON_ONUIPASIF']) {
                    $ipFieldLabel = __('Interface');
                } else {
                    $ipFieldLabel = __('IP');
                }
                $inputs .= wf_TextInput('newip', $ipFieldLabel, '', true, 20);
                $inputs .= wf_TextInput('newmac', __('MAC') . $this->sup, '', true, 20, 'mac');
                $inputs .= wf_TextInput('newserial', __('Serial number'), '', true, 20);
                $inputs .= wf_TextInput('newlogin', __('Login'), '', true, 20);
                $inputs .= wf_Submit(__('Create'));

                $result .= wf_Form('', 'POST', $inputs, 'glamour');
            } else {
                $result .= $this->messages->getStyledMessage(__('Any available ONU models exist'), 'error');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Any available OLT devices exist'), 'error');
        }

        return ($result);
    }

    /**
     * Returns ONU fast registration form
     *
     * @param int $oltId
     * @param string $onuMac
     *
     * @return string
     */
    public function onuRegisterForm($oltId, $onuMac, $UserLogin = '', $UserIP = '', $RenderedOutside = false, $PageReloadAfterDone = false, $CtrlIDToReplaceAfterDone = '', $ModalWindowID = '') {
        $models = array();
        $telepathyArray = array();
        $result = '';
        $onuSerial = '';

        if (!empty($onuMac)) {
            if (!check_mac_format($onuMac)) {
                $onuSerial = $onuMac; //this is something like not mac
                $onuMac = zb_MacGetRandom(); //replacing MAC with random one
            }
        }

        if (!empty($this->allModelsData)) {
            foreach ($this->allModelsData as $io => $each) {
                if (@$this->altCfg['ONUMODELS_FILTER']) {
                    if (ispos($each['modelname'], 'ONU')) {
                        $models[$each['id']] = $each['modelname'];
                    }
                } else {
                    $models[$each['id']] = $each['modelname'];
                }
            }
        }

        if ($this->onuUknownUserByMACSearchTelepathy and (empty($UserLogin) or empty($UserIP))) {
            $telepathyArray = $this->getUserByONUMAC($onuMac, $this->onuUknownUserByMACSearchIncrement);

            if (!empty($telepathyArray)) {
                $UserLogin = $telepathyArray['login'];
                $UserIP = $telepathyArray['ip'];
            }
        }

        if (!empty($this->allOltDevices)) {
            if (!empty($models)) {
                $inputs = wf_HiddenInput('createnewonu', 'true');
                $inputs .= wf_Selector('newoltid', $this->allOltDevices, __('OLT device') . $this->sup, $oltId, true);
                $inputs .= wf_Selector('newonumodelid', $models, __('ONU model') . $this->sup, '', true);
                $inputs .= wf_TextInput('newip', __('IP'), $UserIP, true, 20, '', '__NewONUIP');
                $inputs .= wf_TextInput('newmac', __('MAC') . $this->sup, $onuMac, true, 20, 'mac', '__NewONUMAC');
                $inputs .= wf_TextInput('newserial', __('Serial number'), $onuSerial, true, 20);
                $inputs .= wf_TextInput('newlogin', __('Login'), $UserLogin, true, 20, '', '__NewONULogin');
                $inputs .= wf_Link('#', __('Check if ONU is assigned to any login already'), true, 'ubButton __CheckONUAssignmentBtn', 'style="width: 100%; text-align: center;padding: 6px 0; margin-top: 5px;"');
                $inputs .= wf_tag('span', false, '', 'id="onuassignment2" style="font-weight: 600; color: #000"');
                $inputs .= wf_tag('span', true);

                if (($this->onuUknownUserByMACSearchShow and (empty($UserLogin) or empty($UserIP))) or $this->onuUknownUserByMACSearchShowAlways) {
                    $inputs .= wf_delimiter(0) . wf_tag('div', false, '', 'style="padding: 2px 8px;"');
                    $inputs .= __('Try to find user by MAC') . ':';
                    $inputs .= wf_tag('div', false, '', 'style="margin-top: 5px;"');
                    $inputs .= wf_nbsp(2) . wf_tag('span', false, '', 'style="width: 444px;display: inline-block;float: left;"') .
                        __('increase/decrease searched MAC address on (use negative value to decrease MAC)') . wf_tag('span', true) .
                        wf_tag('span', false, '', 'style="display: inline-block;padding: 5px 0;"') .
                        wf_TextInput('macincrementwith', '', $this->onuUknownUserByMACSearchIncrement, true, '4', '', '__MACIncrementWith') .
                        wf_tag('span', true);
                    $inputs .= wf_tag('div', true);
                    $inputs .= wf_Link('#', __('Search'), true, 'ubButton __UserByMACSearchBtn', 'style="width: 100%; text-align: center; padding: 6px 0; margin-top: 5px;"');
                    $inputs .= wf_tag('div', true);
                }

                $NoRedirChkID = 'NoRedirChk_' . wf_InputId();
                $ReloadChkID = 'ReloadChk_' . wf_InputId();
                $SubmitID = 'Submit_' . wf_InputId();
                $FormID = 'Form_' . wf_InputId();
                $HiddenReplID = 'ReplaceCtrlID_' . wf_InputId();
                $HiddenModalID = 'ModalWindowID_' . wf_InputId();

                $inputs .= wf_tag('br');
                $inputs .= (($RenderedOutside) ? wf_CheckInput('NoRedirect', __('Do not redirect anywhere: just add & close'), true, true, $NoRedirChkID, '__ONUAACFormNoRedirChck') : '');
                $inputs .= (($PageReloadAfterDone) ? wf_CheckInput('', __('Reload page after action'), true, true, $ReloadChkID, '__ONUAACFormPageReloadChck') : '');

                $inputs .= wf_tag('br');
                $inputs .= wf_Submit(__('Create'), $SubmitID);

                $result = wf_Form(self::URL_ME, 'POST', $inputs, 'glamour __ONUAssignAndCreateForm', '', $FormID);
                $result .= wf_HiddenInput('', $CtrlIDToReplaceAfterDone, $HiddenReplID, '__ONUAACFormReplaceCtrlID');
                $result .= wf_HiddenInput('', $ModalWindowID, $HiddenModalID, '__ONUAACFormModalWindowID');
                $result .= wf_tag('script', false, '', 'type="text/javascript"');
                $result .= '
                    $(\'#' . $FormID . '\').submit(function(evt) {
                        if ( $(\'#' . $NoRedirChkID . '\').is(\':checked\') ) {
                            evt.preventDefault();
                             
                            $.ajax({
                                type: "POST",
                                url: "' . self::URL_ME . '",
                                data: $(\'#' . $FormID . '\').serialize(),
                                success: function() {
                                            if ( $(\'#' . $ReloadChkID . '\').is(\':checked\') ) { location.reload();}
                                            $( \'#\'+$(\'#' . $HiddenReplID . '\').val() ).replaceWith(\'' . web_ok_icon() . '\');
                                            $( \'#\'+$(\'#' . $HiddenModalID . '\').val() ).dialog("close");
                                         }
                            });
                        }
                    });
                    ';
                $result .= wf_tag('script', true);
            } else {
                $result .= $this->messages->getStyledMessage(__('Any available ONU models exist'), 'error');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Any available OLT devices exist'), 'error');
        }
        return ($result);
    }

    /**
     * returns vendor by MAC search control if this enabled in config
     *
     * @return string
     */
    protected function getSearchmacControl($mac) {
        $result = '';
        if ($this->altCfg['MACVEN_ENABLED']) {
            if (cfr('MACVEN')) {
                if (!empty($mac)) {
                    $optionState = $this->altCfg['MACVEN_ENABLED'];
                    switch ($optionState) {
                        case 1:
                            $lookupUrl = '?module=macvendor&modalpopup=true&mac=' . $mac;
                            $result .= wf_AjaxLink($lookupUrl, wf_img('skins/macven.gif', __('Device vendor')), 'macvendorcontainer', false);
                            $result .= wf_AjaxContainerSpan('macvendorcontainer', '', '');
                            break;
                        case 2:
                            $vendorframe = wf_tag('iframe', false, '', 'src="?module=macvendor&mac=' . $mac . '" width="360" height="160" frameborder="0"');
                            $vendorframe .= wf_tag('iframe', true);
                            $result = wf_modalAuto(wf_img('skins/macven.gif', __('Device vendor')), __('Device vendor'), $vendorframe, '');
                            break;
                        case 3:
                            $lookupUrl = '?module=macvendor&raw=true&mac=' . $mac;
                            $result .= wf_AjaxLink($lookupUrl, wf_img('skins/macven.gif', __('Device vendor')), 'macvendorcontainer', false);
                            $result .= wf_AjaxContainerSpan('macvendorcontainer', '', '');
                            break;
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Renders ONU assigning form
     *
     * @param string $login
     * @return string
     */
    public function onuAssignForm($login) {
        $result = '';
        $params = array();
        $allRealnames = zb_UserGetAllRealnames();
        $allAddress = zb_AddressGetFulladdresslistCached();
        @$userAddress = $allAddress[$login];
        @$userRealname = $allRealnames[$login];

        if (!empty($this->allOnu)) {
            foreach ($this->allOnu as $io => $each) {
                if (empty($each['login'])) {
                    $onuLabel = (empty($each['ip'])) ? $each['mac'] : $each['mac'] . ' - ' . $each['ip'];
                    $params[$each['id']] = $onuLabel;
                }
            }
        }

        //user data
        $cells = wf_TableCell(__('Real Name'), '30%', 'row2');
        $cells .= wf_TableCell($userRealname);
        $rows = wf_TableRow($cells, 'row3');
        $cells = wf_TableCell(__('Full address'), '30%', 'row2');
        $cells .= wf_TableCell($userAddress);
        $rows .= wf_TableRow($cells, 'row3');
        $result .= wf_TableBody($rows, '100%', 0, '');
        $result .= wf_delimiter();
        if (!empty($params)) {
            $inputs = wf_HiddenInput('assignonulogin', $login);
            $inputs .= wf_Selector('assignonuid', $params, __('ONU'), '', false);
            $inputs .= wf_Submit(__('Save'));
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        } else {
            $result .= $this->messages->getStyledMessage(__('No ONUs not assigned to users were found'), 'success');
        }
        $result .= wf_CleanDiv();
        $result .= wf_delimiter();
        $result .= web_UserControls($login);
        return ($result);
    }

    /**
     * Returns array of additional ONU assigned users
     *
     * @param int $onuId
     *
     * @return array
     */
    protected function getOnuExtUsers($onuId) {
        $result = array();
        if (!empty($this->allOnuExtUsers)) {
            foreach ($this->allOnuExtUsers as $io => $each) {
                if ($each['onuid'] == $onuId) {
                    $result[$each['id']] = $each;
                }
            }
        }
        return ($result);
    }

    /**
     * Deletes existing user binding to ONU by user Id
     *
     * @param int $extUserId
     *
     * @return void
     */
    public function deleteOnuExtUser($extUserId) {
        $extUserId = ubRouting::filters($extUserId, 'int');
        if (isset($this->allOnuExtUsers[$extUserId])) {
            $oldData = $this->allOnuExtUsers[$extUserId];
            $this->onuExtUsersDb->where('id', '=', $extUserId);
            $this->onuExtUsersDb->delete();
            log_register('PON EDIT ONU [' . $oldData['onuid'] . '] DELETE EXTUSER (' . $oldData['login'] . ')');
        }
    }

    /**
     * Renders additional user creation form
     *
     * @param int $onuId
     *
     * @return string
     */
    protected function renderOnuExtUserForm($onuId) {
        $result = '';
        $onuId = vf($onuId, 3);
        if (isset($this->allOnu[$onuId])) {
            $inputs = wf_HiddenInput('newpononuextid', $onuId);
            $inputs .= wf_TextInput('newpononuextlogin', __('Login'), '', false, 20) . ' ';
            $inputs .= wf_Submit(__('Create'));
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        }
        return ($result);
    }

    /**
     * Creates new ONU additional user binding
     *
     * @param int $onuId
     * @param string $login
     *
     * @return void
     */
    public function createOnuExtUser($onuId, $login) {
        $onuId = ubRouting::filters($onuId, 'int');
        if (isset($this->allOnu[$onuId])) {
            $loginF = ubRouting::filters($login, 'mres');
            $this->onuExtUsersDb->data('onuid', $onuId);
            $this->onuExtUsersDb->data('login', $loginF);
            $this->onuExtUsersDb->create();
            log_register('PON EDIT ONU [' . $onuId . '] ASSIGN EXTUSER (' . $login . ')');
        }
    }

    /**
     * Returns existing ONU data or empty array if it not exists
     * 
     * @param int $onuId
     * 
     * @return array
     */
    public function getOnuData($onuId) {
        $result = array();
        if (isset($this->allOnu[$onuId])) {
            $result = $this->allOnu[$onuId];
        }
        return ($result);
    }

    /**
     *  Returns some ONU signal level as array with following keys: raw/color/type/styled/isoffline
     * 
     * @param int $onuId
     * 
     * @return array
     */
    public function getOnuSignalLevelData($onuId) {
        $result = array();
        if (isset($this->allOnu[$onuId])) {
            $onuData = $this->allOnu[$onuId];
            $oltId = $onuData['oltid'];
            $oltPollingStats = $this->pollingStatsRead($oltId);

            //load cache once
            if (empty($this->signalCache)) {
                if ($this->oltData->isSignalsAvailable()) {
                    $this->loadSignalsCache();
                }
            }

            $offlineFlag = false;

            if (isset($this->signalCache[$onuData['mac']])) {
                $signal = $this->signalCache[$onuData['mac']];
                if (($signal > 0) or ($signal < -27)) {
                    $sigColor = self::COLOR_BAD;
                    $sigLabel = 'Bad signal';
                } elseif ($signal > -27 and $signal < -25) {
                    $sigColor = self::COLOR_AVG;
                    $sigLabel = 'Mediocre signal';
                } else {
                    $sigColor = self::COLOR_OK;
                    $sigLabel = 'Normal';
                }

                if ($signal == self::NO_SIGNAL) {
                    $ONUIsOffline = true;
                    $signal = __('No');
                    $sigColor = self::COLOR_NOSIG;
                    $sigLabel = 'No signal';
                    $offlineFlag = true;
                }
            } elseif (isset($this->signalCache[$onuData['serial']])) {
                $signal = $this->signalCache[$onuData['serial']];
                if (($signal > 0) or ($signal < -27)) {
                    $sigColor = self::COLOR_BAD;
                    $sigLabel = 'Bad signal';
                } elseif ($signal > -27 and $signal < -25) {
                    $sigColor = self::COLOR_AVG;
                    $sigLabel = 'Mediocre signal';
                } else {
                    $sigColor = self::COLOR_OK;
                    $sigLabel = 'Normal';
                }

                if ($signal == self::NO_SIGNAL) {
                    $ONUIsOffline = true;
                    $signal = __('No');
                    $sigColor = self::COLOR_NOSIG;
                    $sigLabel = 'No signal';
                    $offlineFlag = true;
                }
            } else {
                $ONUIsOffline = true;
                $signal = __('No');
                $sigColor = self::COLOR_NOSIG;
                $sigLabel = 'No signal';
                $offlineFlag = true;
            }

            $result['raw'] = $signal;
            $result['color'] = $sigColor;
            $result['type'] = $sigLabel;
            $result['styled'] = wf_tag('font', false, '', 'color="' . $sigColor . '"') . $signal . wf_tag('font', true);
            $result['isoffline'] = $offlineFlag;
            $result['polltime'] = '';
            $result['pollnow'] = 0;
            if (!empty($oltPollingStats)) {
                if (!$oltPollingStats['finished']) {
                    $result['pollnow'] = 1;
                }

                if ($oltPollingStats['end']) {
                    $result['polltime'] = $oltPollingStats['end'];
                }
            }
        }
        return ($result);
    }

    /**
     * Returns styled current ONU signal
     *
     * @param int $onuId
     *
     * @return string
     */
    protected function renderOnuSignalBig($onuId) {
        $result = '';
        if (isset($this->allOnu[$onuId])) {
            $allDeadSwitches = zb_SwitchesGetAllDead();
            $oltId = $this->allOnu[$onuId]['oltid'];
            $oltIp = $this->allOltIps[$oltId];
            $deadOltFlag = (isset($allDeadSwitches[$oltIp])) ? true : false;
            $onuSignal = $this->getOnuSignalLevelData($onuId);
            if (!empty($onuSignal)) {
                $sigTypeLabel = ($deadOltFlag) ? __('Latest') :  __('Current');
                $result .= wf_tag('div', false, 'onusignalbig');
                $result .= $sigTypeLabel . ' ' . __('Signal') . ' ' . __('ONU');
                $result .= wf_delimiter();
                $result .= wf_tag('font', false, '', 'color="' . $onuSignal['color'] . '" size="16pt"') . $onuSignal['raw'] . wf_tag('font', true);
                $result .= wf_delimiter();
                if ($deadOltFlag) {
                    $result .= wf_img('skins/skull.png') . ' ' . __('OLT is dead now');
                } else {
                    $result .= __($onuSignal['type']);
                }

                $result .= $this->renderOnuMiscStats($onuId, $onuSignal);
                $result .= ($this->onuUniStatusEnabled) ? $this->renderONUUniStats($onuId, $onuSignal) : '';
                $result .= wf_tag('div', true);
            }
        }
        return ($result);
    }

    /**
     * Renders ONU interface, distance and last dereg reason if available
     * 
     * @param int $onuId
     * @param array $signalStatsData
     * 
     * @return string 
     */
    protected function renderOnuMiscStats($onuId, $signalStatsData) {
        $result = '';
        $offlineFlag = ($signalStatsData['isoffline']) ? true : false;

        if (isset($this->allOnu[$onuId])) {
            $this->loadInterfaceCache();
            $this->loadDistanceCache();
            $this->loadLastDeregCache();
            $onuData = $this->allOnu[$onuId];
            $onuMiscStats = '';

            // interface
            $interfaceIcon = wf_img_sized('skins/pon_icon.gif', __('Interface'), '12');
            if (isset($this->interfaceCache[$onuData['mac']])) {
                $onuMiscStats .= $interfaceIcon . ' ' . $this->interfaceCache[$onuData['mac']] . ' ';
            } else {
                if (isset($this->interfaceCache[$onuData['serial']])) {
                    $onuMiscStats .= $interfaceIcon . ' ' . $this->interfaceCache[$onuData['serial']] . ' ';
                }
            }

            //distance
            if (!$offlineFlag) {
                $distanceIcon = wf_img_sized('skins/distance_icon.png', __('Distance'), '12');
                if (isset($this->distanceCache[$onuData['mac']])) {
                    $onuMiscStats .= $distanceIcon . ' ' . $this->distanceCache[$onuData['mac']] . __('m') . ' ';
                } else {
                    if (isset($this->distanceCache[$onuData['serial']])) {
                        $onuMiscStats .= $distanceIcon . ' ' . $this->distanceCache[$onuData['serial']] . __('m') . ' ';
                    }
                }
            }

            //last dereg reason
            if ($offlineFlag) {
                $offlineIcon = wf_img_sized('skins/offline_icon.png', __('Last dereg reason'), '12');
                if (isset($this->lastDeregCache[$onuData['mac']])) {
                    $onuMiscStats .= $offlineIcon . ' ' . $this->lastDeregCache[$onuData['mac']] . ' ';
                } else {
                    if (isset($this->lastDeregCache[$onuData['serial']])) {
                        $onuMiscStats .= $offlineIcon . ' ' . $this->lastDeregCache[$onuData['serial']] . ' ';
                    }
                }
            }

            //polling time here
            $fullTime = '';
            $shortTime = '';
            if (!empty($signalStatsData['polltime'])) {
                $fullTime = __('Time') . ': ' . date("Y-m-d H:i:s", $signalStatsData['polltime']);
                $shortTime = date("H:i:s", $signalStatsData['polltime']);
            } else {
                $shortTime .= __('In progress now');
            }

            $pollTimeIcon = ($signalStatsData['pollnow']) ? self::POLL_RUNNING : wf_img_sized('skins/icon_time_small.png', $fullTime, '12');
            $onuMiscStats .= $pollTimeIcon . ' ' . $shortTime;

            $containerStyle = 'style="font-size:10pt; padding:10px;"';
            $result .= wf_tag('div', false, '', $containerStyle);
            $result .= $onuMiscStats;
            $result .= wf_tag('div', true);
        }
        return ($result);
    }

    /**
     * Renders ONU UNI port operational status if available
     *
     * @param $onuId
     * @param $signalStatsData
     *
     * @return string
     */
    protected function renderONUUniStats($onuId, $signalStatsData) {
        $result = '';
        $onuMAC = '';
        $onuSerial = '';
        $uniStatsData = '';
        $onuUniOperStats = '';
        $offlineFlag = ($signalStatsData['isoffline']) ? true : false;

        if (isset($this->allOnu[$onuId]) and !$offlineFlag) {
            $this->loadUniOperStatsCache();

            if (!empty($this->allOnu[$onuId]['mac'])) {
                $onuMAC = $this->allOnu[$onuId]['mac'];
            }

            if (!empty($this->allOnu[$onuId]['serial'])) {
                $onuSerial = $this->allOnu[$onuId]['serial'];
            }

            if (!empty($this->uniOperStatsCache[$onuMAC])) {
                $uniStatsData = $this->uniOperStatsCache[$onuMAC];
            } elseif (!empty($this->uniOperStatsCache[$onuSerial])) {
                $uniStatsData = $this->uniOperStatsCache[$onuSerial];
            }

            if (!empty($uniStatsData)) {
                foreach ($uniStatsData as $eachPort => $eachStatus) {
                    $curEtherStatus = false;
                    $curEtherSpeed = '';

                    if (is_array($eachStatus) and isset($eachStatus['unistatus']) and isset($eachStatus['unispeed'])) {
                        $curEtherStatus = $eachStatus['unistatus'];
                        $curEtherSpeed = $eachStatus['unispeed'];
                    } else {
                        $curEtherStatus = $eachStatus;
                    }

                    if ($curEtherStatus) {
                        $interfaceIcon = wf_img_sized('skins/icon_ether.gif', __('Interface')) . wf_nbsp()
                            . wf_img_sized('skins/rise_icon.png', __('Up'), '8', '10') . ' ' . $curEtherSpeed;
                    } else {

                        $interfaceIcon = wf_img_sized('skins/icon_ether_down.png', __('Interface')) . wf_nbsp()
                            . wf_img_sized('skins/drain_icon.png', __('Down'), '8', '10');
                    }

                    $onuUniOperStats .= $eachPort . ': ' . $interfaceIcon . wf_nbsp(4);
                }

                $containerStyle = 'style="font-size:10pt; padding:10px;"';
                $result .= wf_tag('div', false, '', $containerStyle);
                $result .= $onuUniOperStats;
                $result .= wf_tag('div', true);
            }
        }

        return ($result);
    }

    /**
     * Performs burial of some ONU
     *
     * @param int $onuId
     *
     * @return void
     */
    public function onuBurial($onuId) {
        $onuid = ubRouting::filters($onuId, 'int');
        if (isset($this->allOnu[$onuId])) {
            $this->onuDb->where('id', '=', $onuId);
            $this->onuDb->data('login', 'dead');
            $this->onuDb->save();
            log_register('PON BURIAL ONU [' . $onuId . ']');
            $this->flushOnuCache();
        } else {
            log_register('PON BURIAL ONU [' . $onuId . '] FAILED');
        }
    }

    /**
     * Performs resurrection of some buried ONU
     *
     * @param int $onuId
     *
     * @return void
     */
    public function onuResurrect($onuId) {
        $onuid = ubRouting::filters($onuId, 'int');
        if (isset($this->allOnu[$onuId])) {
            $this->onuDb->where('id', '=', $onuId);
            $this->onuDb->data('login', '');
            $this->onuDb->save();

            log_register('PON RESURRECT ONU [' . $onuId . ']');
        } else {
            log_register('PON RESURRECT ONU [' . $onuId . '] FAILED');
        }
    }

    /**
     * Returns ONU edit form aka "ONU profile"
     *
     * @param int $onuId
     * @param bool $limitedControls
     *
     * @return string
     */
    public function onuEditForm($onuId, $limitedControls = false) {
        $onuId = ubRouting::filters($onuId, 'int');
        $result = '';

        if (isset($this->allOnu[$onuId])) {
            $messages = new UbillingMessageHelper();

            $models = array();
            if (!empty($this->allModelsData)) {
                foreach ($this->allModelsData as $io => $each) {
                    if (@$this->altCfg['ONUMODELS_FILTER']) {
                        if (ispos($each['modelname'], 'ONU')) {
                            $models[$each['id']] = $each['modelname'];
                        }
                    } else {
                        $models[$each['id']] = $each['modelname'];
                    }
                }
            }

            $onuPortsCount = $this->allModelsData[$this->allOnu[$onuId]['onumodelid']]['ports'];
            $onuMaxUsers = $onuPortsCount - 1;
            $onuExtUsers = $this->getOnuExtUsers($onuId);
            $onuCurrentExtUsers = sizeof($onuExtUsers);

            $inputs = wf_HiddenInput('editonu', $onuId);
            $oltNavControl='';
            if ($this->ponizerUseTabUI) {
                if (isset($this->allOltDevices[$this->allOnu[$onuId]['oltid']])) {
                    $oltNavIcon= wf_img('skins/pon_icon.gif', __('Go to OLT'), '16', '16');
                    $oltNavControl =' '. wf_Link(self::URL_ONULIST . '&' . self::ROUTE_GOTO_OLT . '=' . $this->allOnu[$onuId]['oltid'], $oltNavIcon, false, '');
                }
            }
            if ($this->altCfg['OLTSEL_SEARCHBL']) {
                $inputs .= wf_SelectorSearchable('editoltid', $this->allOltDevices, __('OLT device') . $this->sup, $this->allOnu[$onuId]['oltid'], false, false);
            } else {
                $inputs .= wf_Selector('editoltid', $this->allOltDevices, __('OLT device'). $this->sup, $this->allOnu[$onuId]['oltid'], false, false);
            }
            $inputs.= $oltNavControl;
            $inputs .= wf_delimiter(0);

            $inputs .= wf_Selector('editonumodelid', $models, __('ONU model') . $this->sup, $this->allOnu[$onuId]['onumodelid'], true);
            if (@$this->altCfg['PON_ONUIPASIF']) {
                $ipFieldLabel = __('Interface');
            } else {
                $ipFieldLabel = __('IP');
            }
            $inputs .= wf_TextInput('editip', $ipFieldLabel, $this->allOnu[$onuId]['ip'], true, 20);
            $inputs .= wf_TextInput('editmac', __('MAC') . $this->sup . ' ' . $this->getSearchmacControl($this->allOnu[$onuId]['mac']), $this->allOnu[$onuId]['mac'], true, 20);
            $inputs .= wf_TextInput('editserial', __('Serial number'), $this->allOnu[$onuId]['serial'], true, 20);
            $burialLabel = ($this->allOnu[$onuId]['login'] == 'dead') ? ' ' . wf_img('skins/skull.png', __('Buried')) : '';
            $inputs .= wf_TextInput('editlogin', __('Login') . $burialLabel, $this->allOnu[$onuId]['login'], true, 20);
            if (@$this->altCfg['PON_ONU_CUSTOM_GEO']) {
                $geoMiniControls = '';
                if (!empty($this->allOnu[$onuId]['geo'])) {
                    $geoMiniControls .= wf_Link(PONONUMap::URL_ME . '&' . PONONUMap::ROUTE_PLACEFIND . '=' . $this->allOnu[$onuId]['geo'], wf_img_sized('skins/icon_search_small.gif', __('Find on map'), '10'));
                } else {
                    if (cfr('PONEDIT')) {
                        $geoMiniControls .= wf_link(PONONUMap::URL_ME . '&' . PONONUMap::ROUTE_PLACEONU . '=' . $onuId, wf_img_sized('skins/ymaps/target.png', __('Place on map'), '10'));
                    }
                }
                $inputs .= wf_TextInput('editgeo', $geoMiniControls . ' ' . __('Custom location'), $this->allOnu[$onuId]['geo'], true, 20, 'geo');
            }

            if (!empty($onuExtUsers)) {
                foreach ($onuExtUsers as $io => $each) {
                    //Editing feature: 100$ donate or do it yourself. Im to lazy right now.
                    $inputs .= wf_tag('input', false, '', 'name="onuextlogin_' . $each['id'] . '" type="text" value="' . $each['login'] . '" size="20" DISABLED') . ' ';
                    if (cfr('PONEDIT')) {
                        $controllerUrl = self::URL_ME;
                        if ($limitedControls) {
                            $controllerUrl = '?module=pl_branchesonuview';
                        }
                        $inputs .= wf_JSAlert($controllerUrl . '&editonu=' . $onuId . '&deleteextuser=' . $each['id'], wf_img_sized('skins/icon_del.gif', __('Delete'), '13'), $messages->getDeleteAlert()) . ' ';
                    }
                    $inputs .= wf_Link(self::URL_USERPROFILE . $each['login'], web_profile_icon());
                    $inputs .= wf_tag('br');
                }
            }

            if (cfr('PONEDIT')) {
                $inputs .= wf_tag('br');
                $inputs .= wf_Submit(__('Save'));
            }

            $onuEditForm = wf_Form('', 'POST', $inputs, 'onueditsbig');

            $contentGrid=array($onuEditForm, $this->renderOnuSignalBig($onuId));
            
            $result .= wf_FlexContentGrid($contentGrid,2);
            $result .= wf_CleanDiv();

            ///ponboxes here. We hope.
            if (@$this->altCfg['PONBOXES_ENABLED']) {
                $ponBoxes = new PONBoxes(true);
                //linking if required
                if (ubRouting::checkPost(array($ponBoxes::PROUTE_NEWLINKBOX, $ponBoxes::PROUTE_NEWLINKONU, $ponBoxes::PROUTE_NEWLINKTYPE))) {
                    $newLinkBoxId = ubRouting::post($ponBoxes::PROUTE_NEWLINKBOX);
                    $newLinkType = ubRouting::post($ponBoxes::PROUTE_NEWLINKTYPE);
                    $newLinkOnuId = ubRouting::post($ponBoxes::PROUTE_NEWLINKONU);
                    $ponBoxLinkResult = $ponBoxes->createLinkONU($newLinkBoxId, $newLinkOnuId, $newLinkType);
                    if (empty($ponBoxLinkResult)) {
                        ubRouting::nav(self::URL_ME . '&editonu=' . $newLinkOnuId);
                    } else {
                        show_error($ponBoxLinkResult);
                    }
                }
                //interface render
                $result .= wf_delimiter();
                $result .= $ponBoxes->renderBoxAssignForm($this->allOnu[$onuId]);
                //rendering associated boxes
                $linkedBoxes = $ponBoxes->getLinkedBoxes($this->allOnu[$onuId]);

                if (count($linkedBoxes) > 1) {
                    $result .= $ponBoxes->renderCrossLinkWarning();
                }

                $result .= $ponBoxes->renderLinkedBoxes($linkedBoxes);
            }

            $result .= wf_delimiter();
            if (!$limitedControls) {
                $result .= wf_BackLinkAuto(self::URL_ONULIST);
            }

            //back to primary user profile control
            if (!empty($this->allOnu[$onuId]['login'])) {
                if ($this->allOnu[$onuId]['login'] != 'dead') {
                    $result .= wf_Link(self::URL_USERPROFILE . $this->allOnu[$onuId]['login'], wf_img('skins/icon_user_16.gif') . ' ' . __('User profile'), false, 'ubButton');
                }
            }

            //ONU burial or resurrection controls
            if (!empty($this->allOnu[$onuId]['login'])) {
                if (cfr('PONEDIT') and !$limitedControls) {
                    if (@$this->altCfg['ONU_BURIAL_ENABLED']) {
                        if ($this->allOnu[$onuId]['login'] != 'dead') {
                            //this ONU is owned by some user. Burial controls here.
                            $burCancelUrl = self::URL_ME . '&editonu=' . $onuId;
                            $burConfirmUrl = self::URL_ME . '&onuburial=' . $onuId;
                            $burAlertLabel = __('Bury this ONU') . '? ' . $messages->getEditAlert();
                            $result .= wf_ConfirmDialog($burConfirmUrl, wf_img('skins/skull.png') . __('Bury this ONU'), $burAlertLabel, 'ubButton', $burCancelUrl);
                        } else {
                            //this ONU is already buried. Ressurection controls here.
                            $resCancelUrl = self::URL_ME . '&editonu=' . $onuId;
                            $resConfirmUrl = self::URL_ME . '&onuresurrect=' . $onuId;
                            $resAlertLabel = __('Resurrect this ONU') . '? ' . $messages->getEditAlert() . ' ';
                            $resAlertLabel .= __('After resurrection device will be marked as not belonging to anyone') . '.';
                            $result .= wf_ConfirmDialog($resConfirmUrl, wf_img('skins/pigeon_icon.png') . ' ' . __('Resurrect this ONU'), $resAlertLabel, 'ubButton', $resCancelUrl);
                        }
                    }
                }
            }

            //additional login append forms
            if (cfr('PONEDIT')) {
                if (sizeof($onuExtUsers) < $onuMaxUsers) {
                    $extCreationLabel = wf_img_sized('skins/add_icon.png', '', '13') . ' ' . __('Assign additional login');
                    $result .= wf_modalAuto($extCreationLabel, __('Additional login') . ' (' . ($onuMaxUsers - $onuCurrentExtUsers) . ' ' . __('remains') . ')', $this->renderOnuExtUserForm($onuId), 'ubButton');
                }
            }

            //ONU deletion control
            if (cfr('PONDEL') and !$limitedControls) {
                $delCancelUrl = self::URL_ME . '&editonu=' . $onuId;
                $delConfirmUrl = self::URL_ME . '&deleteonu=' . $onuId;
                $result .= wf_ConfirmDialog($delConfirmUrl, web_delete_icon() . ' ' . __('Delete') . ' ' . __('ONU'), $messages->getDeleteAlert(), 'ubButton', $delCancelUrl);
            }

        } else {
            $result = wf_tag('div', false, 'alert_error') . __('Strange exeption') . ': ONUID_NOT_EXISTS' . wf_tag('div', true);
        }

        //additional comments handling
        if ($this->altCfg['ADCOMMENTS_ENABLED']) {
            $adcomments = new ADcomments('PONONU');
            $result .= wf_delimiter();
            $result .= wf_tag('h3') . __('Additional comments') . wf_tag('h3', true);
            $result .= $adcomments->renderComments($onuId);
        }

        return ($result);
    }

    /**
     * Renders ONU signal history chart
     *
     * @param int $onuId
     * @return string
     */
    protected function onuSignalHistory($onuId, $ShowTitle = false, $ShowXLabel = false, $ShowYLabel = false, $ShowRangeSelector = false) {
        $billCfg = $this->ubConfig->getBilling();
        $chartsWidth = '90%';
        $chartsHeight = '300';
        if ($this->ONUChartsSpoilerClosed) {
            $chartsWidth = '800';
        }
        $onuId = ubRouting::filters($onuId, 'int');
        $result = '';
        if (isset($this->allOnu[$onuId])) {
            //not empty MAC
            if ($this->allOnu[$onuId]['mac']) {
                if (file_exists(self::ONUSIG_PATH . md5($this->allOnu[$onuId]['mac']))) {
                    $historyKey = self::ONUSIG_PATH . md5($this->allOnu[$onuId]['mac']);
                    $historyKeyMonth = self::ONUSIG_PATH . md5($this->allOnu[$onuId]['mac']) . '_month';
                } elseif (file_exists(self::ONUSIG_PATH . md5($this->allOnu[$onuId]['serial']))) {
                    $historyKey = self::ONUSIG_PATH . md5($this->allOnu[$onuId]['serial']);
                    $historyKeyMonth = self::ONUSIG_PATH . md5($this->allOnu[$onuId]['serial']) . '_month';
                } else {
                    $historyKey = '';
                    $historyKeyMonth = '';
                }
                if (!empty($historyKey)) {
                    $curdate = curdate();
                    $curmonth = curmonth() . '-';
                    $getMonthDataCmd = $billCfg['CAT'] . ' ' . $historyKey . ' | ' . $billCfg['GREP'] . ' ' . $curmonth;
                    $rawData = shell_exec($getMonthDataCmd);
                    $result .= wf_delimiter();
                    //current day signal levels
                    $todaySignal = '';

                    if (!empty($rawData)) {
                        $todayTmp = explodeRows($rawData);
                        if (!empty($todayTmp)) {
                            foreach ($todayTmp as $io => $each) {
                                if (ispos($each, $curdate)) {
                                    $todaySignal .= $each . "\n";
                                }
                            }
                        }
                    }

                    $GraphTitle = ($ShowTitle) ? __('Today') : '';
                    $GraphXLabel = ($ShowXLabel) ? __('Time') : '';
                    $GraphYLabel = ($ShowYLabel) ? __('Signal') : '';
                    $result .= wf_Graph($todaySignal, $chartsWidth, $chartsHeight, false, $GraphTitle, $GraphXLabel, $GraphYLabel, $ShowRangeSelector);
                    $result .= wf_delimiter(2);

                    //current month signal levels
                    $monthSignal = '';
                    $curmonth = curmonth();
                    if (!empty($rawData)) {
                        $monthTmp = explodeRows($rawData);
                        if (!empty($monthTmp)) {
                            foreach ($monthTmp as $io => $each) {
                                if (ispos($each, $curmonth)) {
                                    $monthSignal .= $each . "\n";
                                }
                            }
                        }
                    }

                    $GraphTitle = ($ShowTitle) ? __('Monthly graph') : '';
                    $GraphXLabel = ($ShowXLabel) ? __('Date') : '';
                    file_put_contents($historyKeyMonth, $monthSignal);
                    $result .= wf_GraphCSV($historyKeyMonth, $chartsWidth, $chartsHeight, false, $GraphTitle, $GraphXLabel, $GraphYLabel, $ShowRangeSelector);
                    $result .= wf_delimiter(2);

                    //all time signal history
                    $GraphTitle = ($ShowTitle) ? __('All time graph') : '';
                    $result .= wf_GraphCSV($historyKey, $chartsWidth, $chartsHeight, false, $GraphTitle, $GraphXLabel, $GraphYLabel, $ShowRangeSelector);
                    $result .= wf_delimiter();
                }
            }
        }
        return ($result);
    }

    /**
     * Returns default list controls
     *
     * @return string
     */
    public function controls() {
        $result = '';
        if (!ubRouting::checkGet('unknownonulist')) {
            if (cfr('PONEDIT')) {
                $result .= wf_modalAuto(wf_img_sized('skins/add_icon.png', '', '16', '16') . ' ' . __('Create') . ' ' . __('ONU'), __('Register new ONU'), $this->onuCreateForm(), 'ubButton') . ' ';
            }
            $availOnuCache = $this->oltData->isOnusAvailable();
            $result .= wf_Link(self::URL_ME . '&forcepoll=true', wf_img_sized('skins/refresh.gif', '', '16', '16') . ' ' . __('Force query'), false, 'ubButton');
            if (!empty($availOnuCache)) {
                if (cfr('PONEDIT')) {
                    $result .= wf_Link(self::URL_ME . '&unknownonulist=true', wf_img_sized('skins/question.png', '', '16', '16') . ' ' . __('Unknown ONU'), false, 'ubButton');
                }
            }

            $availOnuFdbCache = $this->oltData->isFdbAvailable();
            if (!empty($availOnuFdbCache)) {
                $result .= wf_Link(self::URL_ME . '&fdbcachelist=true', wf_img_sized('skins/icon_fdb.png', '', '16', '16') . ' ' . __('Current FDB cache'), false, 'ubButton');
            }

            if (@$this->altCfg['PON_ONU_PORT_MAX']) {
                $result .= wf_Link(self::URL_ME . '&oltstats=true', wf_img_sized('skins/icon_stats.gif', '', '16', '16') . ' ' . __('Stats'), false, 'ubButton');
            }

            if (@$this->altCfg['PONMAP_ENABLED']) {
                if (cfr('ONUMAP')) {
                    $result .= wf_Link('?module=ponmap&bl=ponizer', wf_img_sized('skins/ponmap_icon.png', '', '16', '16') . ' ' . __('ONU Map'), false, 'ubButton');
                }
            }

            if (@$this->altCfg['PON_ONU_SEARCH_ENABLED']) {
                $result .= wf_modalAuto(web_icon_search() . ' ' . __('Search'), __('Search') . ' ' . __('ONU'), $this->renderOnuSearchForm(), 'ubButton');
            }
            if ($this->altCfg['ONUREG_ZTE']) {
                $zteControls = '';
                if (cfr(OnuRegister::REG_MODULE_RIGHTS)) {
                    $zteControls .= wf_link(OnuRegister::UNREG_URL, wf_img_sized('skins/check.png', '', '16', '16') . ' ' . __('Check for unauthenticated ONU/ONT') . ' (' . __('All') . ' OLT)', false, 'ubButton') . wf_delimiter();
                    $zteControls .= wf_link(OnuRegister::UNREG_OLTLIST_URL, wf_img_sized('skins/pon_icon.gif', '', '16', '16') . ' ' . __('Check for unauthenticated ONU/ONT') . ' OLT', false, 'ubButton') . wf_delimiter();
                    $zteControls .= wf_link(OnuRegister::UNREG_MASS_FIX_PREVIEW_URL, wf_img_sized('skins/brain.png', '', '16', '16') . ' ' . __('Mass fix'), false, 'ubButton') . wf_delimiter();
                }
                if (cfr(OnuRegister::VLAN_MODULE_RIGHTS)) {
                    $zteControls .= wf_link(OnuRegister::VLAN_MODULE_URL, wf_img_sized('skins/register.png', '', '16', '16') . ' ' . __('Edit OLT Cards'), false, 'ubButton') . wf_delimiter();
                }

                $result .= wf_modalAuto(web_icon_extended() . ' ' . __('ZTE'), __('ZTE'), $zteControls, 'ubButton');
            }
        } else {
            $result .= wf_BackLink(self::URL_ONULIST);
            $result .= wf_Link(self::URL_ME . '&forcepoll=true&uol=true', wf_img_sized('skins/refresh.gif', '', '16', '16') . ' ' . __('Force query'), false, 'ubButton');
            if (cfr('ROOT')) {
                //ONU batch registration accessible only for ROOT users now
                $massRegUrl = self::URL_ME . '&onumassreg=true';
                $massRegCancelUrl = '?module=ponizer&unknownonulist=true';
                $alertLabel = __('Register all unknown ONUs') . '? ' . __('Are you serious');
                $dialogLink = wf_img('skins/icon_addrow.png') . ' ' . __('Register all unknown ONUs');
                $result .= wf_ConfirmDialog($massRegUrl, $dialogLink, $alertLabel, 'ubButton', $massRegCancelUrl, __('Are you serious'));
            }
        }

        $result .= wf_tag('script', false, '', 'type="text/javascript"');
        $result .= wf_JSEmptyFunc();
        $result .= wf_JSElemInsertedCatcherFunc();
        $result .= '
                    function checkONUAssignment() {
                        if ( typeof( $(\'input[name=newmac]\').val() ) === "string" && $(\'input[name=newmac]\').val().length > 0 ) {
                            $.ajax({
                                type: "GET",
                                url: "?module=ponizer",
                                data: {action:\'checkONUAssignment\', onumac:$(\'input[name=newmac]\').val()},
                                success: function(result) {
                                            $(\'#onuassignment2\').text(result);
                                         }
                            });
                        } else {$(\'#onuassignment2\').text(\'\');}
                    }        
        
                    function dynamicBindClick(ctrlClassName) {
                        $(document).on("click", ctrlClassName, function(evt) {
                            evt.preventDefault();
                            checkONUAssignment($(ctrlClassName).val());                            
                            return false;            
                        });
                    }
        
                    onElementInserted(\'body\', \'.__CheckONUAssignmentBtn\', function(element) {
                        dynamicBindClick(\'.__CheckONUAssignmentBtn\');
                    });
                    
                    function OLTIndividualRefresh(OLTID, JQAjaxTab, RefreshButtonSelector) {  
                        $.ajax({
                            type: "GET",
                            url: "' . self::URL_ME . '",
                            data: {IndividualRefresh:true, forceoltidpoll:OLTID},
                            success: function(result) {
                                        if ($.type(JQAjaxTab) === \'string\') {
                                            $("#"+JQAjaxTab).DataTable().ajax.reload();
                                        } else {
                                            $(JQAjaxTab).DataTable().ajax.reload();
                                        }
                                        
                                        if ($.type(RefreshButtonSelector) === \'string\') {
                                            $("#"+RefreshButtonSelector).find(\'img\').toggleClass("image_rotate");
                                        } else {
                                            $(RefreshButtonSelector).find(\'img\').toggleClass("image_rotate");
                                        }
                                     }
                        });
                    };

                    function getOLTInfo(OLTID, InfoBlckSelector, ReturnHTML = false, InSpoiler = false) {
                        $.ajax({
                            type: "GET",
                            url: "' . self::URL_ME . '",
                            data: { IndividualRefresh:true, 
                                    GetOLTInfo:true, 
                                    apid:OLTID,
                                    returnAsHTML:ReturnHTML,
                                    returnInSpoiler:InSpoiler
                                  },
                            success: function(result) { 
                                        var InfoBlck = $(InfoBlckSelector);
                                        if ( !InfoBlck.length || !(InfoBlck instanceof jQuery)) {return false;}
                                              
                                        $(InfoBlck).html(result);
                                     }
                        });
                    }
                    ';

        // making an event binding for "DelUserAssignment" button("red cross" near user's login) on "ONU create&assign form"
        // to be able to create "ONU create&assign form" dynamically and not to put it's content to every "Create ONU" button in JqDt tables
        // creating of "ONU create&assign form" dynamically reduces the amount of text and page weight dramatically
        $result .= '$(document).on("click", ".__UsrDelAssignButton", function(evt) {
                            $("[name=assignoncreate]").val("");
                            $(\'.__UsrAssignBlock\').html("' . __('Do not assign WiFi equipment to any user') . '");
                            evt.preventDefault();
                            return false;
                    });
                    
                    ';

        // making an event binding for "ONU create&assign form" 'Submit' action to be able to create "ONU create&assign form" dynamically
        $result .= '$(document).on("submit", ".__ONUAssignAndCreateForm", function(evt) {
                            if ($(document.activeElement).attr("class") == \'__MACIncrementWith\') {
                                evt.preventDefault();
                                $(".__UserByMACSearchBtn").click();
                                return false;
                            }
                            
                            //var FrmAction = \'"\' + $(".__ONUAssignAndCreateForm").attr("action") + \'"\';
                            var FrmAction = $(".__ONUAssignAndCreateForm").attr("action");
                            
                            if ( $(".__ONUAACFormNoRedirChck").is(\':checked\') ) {
                                evt.preventDefault();
                                
                                $.ajax({
                                    type: "POST",
                                    url: FrmAction,
                                    data: $(".__ONUAssignAndCreateForm").serialize(),
                                    success: function() {
                                                if ( $(".__ONUAACFormPageReloadChck").is(\':checked\') ) { location.reload();}
                                                
                                                $( \'#\'+$(".__ONUAACFormReplaceCtrlID").val() ).replaceWith(\'' . web_ok_icon() . '\');
                                                $( \'#\'+$(".__ONUAACFormModalWindowID").val() ).dialog("close");
                                            }
                                });
                            }                            
                        });
                        
                        ';

        $result .= '$(document).on("click", ".__UserByMACSearchBtn", function(evt) {
                        //__NewONULogin, __NewONUIP, __NewONUMAC, __MACIncrementWith
                        
                        $.ajax({
                            type: "GET",
                            url: "' . self::URL_ME . '",
                            data: { 
                                    searchunknownonu:true,
                                    searchunknownmac:$(".__NewONUMAC").val(), 
                                    searchunknownincrement:$(".__MACIncrementWith").val(),
                                    searchunknownserialize:true
                                   },
                            success: function(result) {
                                        var tObj = JSON.parse(result);
                                        
                                        if ( empty(tObj.login) && empty(tObj.ip) ) {
                                            alert(\'' . __('User is not found') . '\');
                                        } else {
                                            $(".__NewONULogin").val(tObj.login);
                                            $(".__NewONUIP").val(tObj.ip);
                                        }
                                     }
                        });
                                                                        
                        evt.preventDefault();
                        return false;
                    });
                    ';

        $result .= wf_tag('script', true);
        $result .= wf_delimiter();
        return ($result);
    }

    /**
     * Returns ONU signal history chart
     *
     * @param int $onuId
     * 
     * @return string
     */
    public function loadonuSignalHistory($onuId) {
        $result = $this->onuSignalHistory($onuId, true, true, false, true);

        if ($this->ONUChartsSpoilerClosed) {
            $result = wf_Spoiler($result, __('Signal levels history graphs'), $this->ONUChartsSpoilerClosed, '', '', '', '', 'style="margin: 10px auto;display: table;"');
        }

        $result = show_window(__('ONU signal history'), $result);
        return ($result);
    }

    /**
     * Renders available ONU JQDT list container
     *
     * @return string
     */
    public function renderOnuList() {
        $distCacheAvail = $this->oltData->isDistancesAvailable();
        $intCacheAvail = $this->oltData->isInterfacesAvailable();
        $lastDeregCacheAvail = $this->oltData->isDeregsAvailable();
        $oltOnuCounters = $this->getOltOnuCounts();
        $gotoOltId = ubRouting::get(self::ROUTE_GOTO_OLT, 'int');

        $opts = '"order": [[ 0, "desc" ]]';
        if ($this->deferredLoadingFlag) {
            $opts .= ', "deferLoading": 100';
        }

        $result = '';
        $tabClickScript = '';
        $tabsList = array();
        $tabsData = array();
        // to prevent changing the keys order of $this->allOLTDevices we are using "+" opreator and not all those "array_merge" and so on
        $QickOLTsArray = array(-9999 => '') + $this->allOltDevices;

        foreach ($this->allOltDevices as $oltId => $eachOltData) {
            $AjaxURLStr = '' . self::URL_ME . '&ajaxonu=true&oltid=' . $oltId . '';
            $JQDTId = 'jqdt_' . md5($AjaxURLStr);
            $OLTIDStr = 'OLTID_' . $oltId;
            $InfoButtonID = 'InfID_' . $oltId;
            $InfoBlockID = 'InfBlck_' . $oltId;
            $QuickOLTLinkID = 'QuickOLTLinkID_' . $oltId;
            $QuickOLTDDLName = 'QuickOLTDDL_' . wf_InputId();
            $QuickOLTLink = wf_tag('span', false, '', 'id="' . $QuickOLTLinkID . '"') .
                wf_img('skins/menuicons/switches.png') . wf_tag('span', true);
            $oltRenderMode = $this->getOltOnuRenderMode($oltId);

            $columns = array('ID');
            if ($intCacheAvail) {
                $columns[] = __('Interface');
            }
            $columns[] = 'Model';
            if ($this->ipColumnVisible) {
                if (@$this->altCfg['PON_ONUIPASIF']) {
                    $columns[] = 'Interface';
                } else {
                    $columns[] = 'IP';
                }
            }
            $onuIdentColumn = '';
            if ($oltRenderMode == 'mac') {
                $onuIdentColumn = __('MAC');
            } else {
                $onuIdentColumn = __('Serial');
            }
            $columns[] = $onuIdentColumn;
            $columns[] = 'Signal';

            if ($distCacheAvail) {
                $columns[] = __('Distance') . ' (' . __('m') . ')';
            }
            if ($lastDeregCacheAvail) {
                $columns[] = __('Last dereg reason');
            }
            $columns[] = 'Address';
            $columns[] = 'Real Name';
            $columns[] = 'Tariff';
            $columns[] = 'Actions';

            if ($this->EnableQuickOLTLinks) {
                if ($this->ponizerUseTabUI) {
                    $QuickOLTDDLName = 'QuickOLTDDL_100500';
                    $tabClickScript = wf_tag('script', false, '', 'type="text/javascript"');
                    $tabClickScript .= '$(\'a[href="#' . $QuickOLTLinkID . '"]\').click(function(evt) {
                                            var tmpID = $(this).attr("href").replace("#QuickOLTLinkID_", "");
                                            if ($(\'[name="' . $QuickOLTDDLName . '"]\').val() != tmpID) {
                                                $(\'[name="' . $QuickOLTDDLName . '"]\').val(tmpID);
                                            }
                                        });
                                        ';
                    $tabClickScript .= wf_tag('script', true);
                } else {
                    $QuickOLTLinkInput = wf_tag('div', false, '', 'style="width: 100%;text-align: right;margin-top: 15px;margin-bottom: 20px"') .
                        wf_tag('font', false, '', 'style="font-weight: 600"') . __('Go to OLT') . wf_tag('font', true) .
                        wf_nbsp(2) . wf_Selector($QuickOLTDDLName, $QickOLTsArray, '', '', true) .
                        wf_tag('script', false, '', 'type="text/javascript"') .
                        '$(\'[name="' . $QuickOLTDDLName . '"]\').change(function(evt) {
                                                        var LinkIDObjFromVal = $(\'#QuickOLTLinkID_\'+$(this).val());
                                                        $(\'body,html\').scrollTop( $(LinkIDObjFromVal).offset().top - 25 );
                                                     });' .
                        wf_tag('script', true) .
                        wf_tag('div', true);
                }
            } else {
                $QuickOLTLinkInput = '';
            }

            if ($this->OLTIndividualRepollAJAX) {
                if ($this->ponizerUseTabUI) {
                    if ($this->isPollingNow($oltId)) {
                        $refresh_button = wf_tag('span', false, '', 'title="' . __('In progress now') . '"') . self::POLL_RUNNING . wf_tag('span', true);
                    } else {
                        $refresh_button = wf_tag('span', false, '', 'href="#" id="' . $OLTIDStr . '" title="' . __('Refresh data for this OLT') . '" style="cursor: pointer;"');
                        $refresh_button .= wf_img('skins/refresh.gif');
                        $refresh_button .= wf_tag('span', true);
                    }
                } else {
                    $refresh_button = wf_tag('a', false, '', 'href="#" id="' . $OLTIDStr . '" title="' . __('Refresh data for this OLT') . '"');
                    $refresh_button .= wf_img('skins/refresh.gif');
                    $refresh_button .= wf_tag('a', true);
                }

                $refresh_button .= wf_tag('script', false, '', 'type="text/javascript"');
                $refresh_button .= '$(\'#' . $OLTIDStr . '\').click(function(evt) {
                                        $(\'img\', this).addClass("image_rotate");
                                        OLTIndividualRefresh(' . $oltId . ', ' . $JQDTId . ', ' . $OLTIDStr . ');
                                        evt.preventDefault();
                                        return false;
                                    });';
                $refresh_button .= wf_tag('script', true);
            } else {
                $refresh_button = wf_Link(self::URL_ME . '&forceoltidpoll=' . $oltId, wf_img('skins/refresh.gif', __('Refresh data for this OLT')));
            }



            if ($this->ponizerUseTabUI) {
                $tabsList[$QuickOLTLinkID] = array(
                    'options' => '',
                    'caption' => $refresh_button . wf_nbsp(4) . wf_img('skins/menuicons/switches.png') . wf_nbsp(2) . @$eachOltData,
                    'additional_data' => $tabClickScript
                );

                $tabsData[$QuickOLTLinkID] = array(
                    'options' => 'style="padding: 0 0 0 2px;"',
                    'body' => wf_JqDtLoader($columns, $AjaxURLStr, false, 'ONU', 100, $opts),
                    'additional_data' => ''
                );
            } else {
                $result .= show_window($refresh_button . wf_nbsp(4) . $QuickOLTLink . wf_nbsp(2) . @$eachOltData, wf_JqDtLoader($columns, $AjaxURLStr, false, 'ONU', 100, $opts) . $QuickOLTLinkInput);
            }
        }

        if ($this->ponizerUseTabUI) {
            $tabsDivOpts = 'style="border: none;padding: 0;"';
            $tabsLstOpts = 'style="border: none;background: #fff;"';

            if ($this->EnableQuickOLTLinks and !empty($this->allOltDevices)) {
                $QuickOLTDDLName = 'QuickOLTDDL_100500';
                $QickOLTsArray = $this->allOltDevices;
                $oltSelectorBody = wf_Selector($QuickOLTDDLName, $QickOLTsArray, '', '', true, false, 'someid');
                $QuickOLTLinkInput = wf_tag('div', false, '', 'style="margin-top: 15px;text-align: right;"') .
                    wf_tag('font', false, '', 'style="font-weight: 600"') . __('Go to OLT') . wf_tag('font', true) .
                    wf_nbsp(2) . $oltSelectorBody .
                    wf_tag('script', false, '', 'type="text/javascript"') .
                    '$(\'[name="' . $QuickOLTDDLName . '"]\').change(function(evt) {
                                                    $(\'a[href="#QuickOLTLinkID_\'+$(this).val()+\'"]\').click();
                                                 });' .
                    wf_tag('script', true) .
                    wf_tag('div', true);
            } else {
                $QuickOLTLinkInput = '';
            }

            //interface grid construction
            $ponizerGrid = '';
            $ponizerGrid .= $QuickOLTLinkInput . wf_delimiter(0);
            $ponizerGrid .= wf_TabsCarouselInitLinking();
            $ponizerGrid .= wf_TabsGen('ui-tabs', $tabsList, $tabsData, $tabsDivOpts, $tabsLstOpts, true);
            $ponizerGrid .= $QuickOLTLinkInput;

            //get route navigtion to specific OLT
            if (!empty($gotoOltId) and isset($this->allOltDevices[$gotoOltId])) {
                $ponizerGrid .= wf_tag('script', false, '', 'type="text/javascript"');
                $ponizerGrid .= '$(function(){var t=$(\'a[href="#QuickOLTLinkID_' . $gotoOltId . '"]\');if(t.length){t.click();}});';
                $ponizerGrid .= wf_tag('script', true);
            }
            //rendering it
            show_window('', $ponizerGrid);
        } else {
            return ($result);
        }
    }

    /**
     * Renders OLT stats
     *
     * @return string
     */
    public function renderOltStats() {
        $oltOnuCounters = $this->getOltOnuCounts();
        $onuMaxCountConf = @$this->altCfg['PON_ONU_PORT_MAX'];
        $herdEnabledFlag = (@$this->altCfg['HERD_OF_PONIES']) ? true : false;
        $oltOnuFilled = array();
        $oltOnuPonPortMax = array();
        $oltInterfacesFilled = array();
        $oltInterfaceDescrs = array();
        $signals = array();
        $badSignals = array();
        $avgSignals = array();
        $oltsTemps = array(); //oltId=>temperature
        $oltData = new OLTAttractor();
        $ponScriptsFlag = ($this->altCfg['SWITCHES_AUTH_ENABLED'] and $this->altCfg['PON_SCRIPTS_ENABLED']) ? true : false;
        $ponscriptsOltRender = 0;
        if ($ponScriptsFlag) {
            $ponScripts = new PONScripts($this->allOltIps, $this->allOltModelIds, $this->allModelsData);
            $ponscriptsOltRender = ubRouting::get($ponScripts::ROUTE_RENDER_OLT_SCRIPTS, 'int');
        }

        $statsControls = wf_BackLink(self::URL_ONULIST);
        $statsControls .= wf_Link(self::URL_ME . '&oltstats=true', wf_img('skins/icon_stats_16.gif') . ' ' . __('Stats') . ' ' . __('OLT'), false, 'ubButton') . ' ';
        if (!ubRouting::checkGet('temperature')) {
            $statsControls .= wf_Link(self::URL_ME . '&oltstats=true&temperature=true', wf_img('skins/temperature.png') . ' ' . __('Temperature'), false, 'ubButton') . ' ';
        } else {
            $statsControls .= wf_Link(self::URL_ME . '&oltstats=true', wf_img('skins/notemperature.png') . ' ' . __('Temperature'), false, 'ubButton') . ' ';
        }
        $statsControls .= wf_Link(self::URL_ME . '&oltstats=true&pollstats=true', wf_img('skins/icon_time_small.png') . ' ' . __('Devices polling stats'), false, 'ubButton') . ' ';
        $statsControls .= wf_Link(self::URL_ME . '&polllogs=true', wf_img('skins/log_icon_small.png') . ' ' . __('OLT polling log'), false, 'ubButton') . ' ';
        if (cfr('ROOT')) {
            $cleanupUrl = self::URL_ME . '&oltstats=true&pondatacleanup=true';
            $cleanupCancel = self::URL_ME . '&oltstats=true';
            $cleanupLabel = wf_img('skins/icon_cleanup.png') . ' ' . __('Cache cleanup');
            $cleanupAlert = __('Clear all cache') . '?';
            $statsControls .= wf_ConfirmDialog($cleanupUrl, $cleanupLabel, $cleanupAlert, 'ubButton', $cleanupCancel, __('Cleanup') . '?');
        }

        $result = '';
        $result .= $statsControls;
        $result .= wf_tag('br');

        foreach ($this->allOltDevices as $oltId => $eachOltData) {
            if (isset($oltOnuCounters[$oltId])) {
                $onuCount = $oltOnuCounters[$oltId];
                $oltModelId = @$this->allOltSnmp[$oltId]['modelid'];
                $oltPorts = @$this->allOltModels[$oltModelId]['ports'];
                $snmpTemplatesMaxPort = @$this->snmpTemplates[$oltModelId]['define']['PON_ONU_PORT_MAX'];
                $onuMaxCount = (!empty($snmpTemplatesMaxPort)) ? $snmpTemplatesMaxPort : $onuMaxCountConf;
                if ((!empty($oltModelId)) and (!empty($oltPorts)) and (!empty($onuMaxCount))) {
                    $oltData->setOltId($oltId); //switching attractor scope
                    $maxOnuPerOlt = $oltPorts * $onuMaxCount;
                    $oltOnuFilled[$oltId] = zb_PercentValue($maxOnuPerOlt, $onuCount);
                    $oltOnuPonPortMax[$oltId] = $onuMaxCount;

                    $interfaces = $oltData->readInterfaces();
                    //is any ONU interfaces here?
                    if (!empty($interfaces)) {
                        $signals = $oltData->readSignals();
                        $ifaceDescrs = $oltData->readInterfacesDescriptions();

                        foreach ($interfaces as $eachMac => $eachInterface) {
                            $cleanInterface = strstr($eachInterface, ':', true);

                            if (isset($oltInterfacesFilled[$oltId][$cleanInterface])) {
                                $oltInterfacesFilled[$oltId][$cleanInterface]++;
                            } else {
                                $oltInterfacesFilled[$oltId][$cleanInterface] = 1;
                            }

                            if (isset($signals[$eachMac])) {
                                $macSignal = $signals[$eachMac];
                                if ((($macSignal > -27) and ($macSignal < -25))) {
                                    if (isset($avgSignals[$oltId][$cleanInterface])) {
                                        $avgSignals[$oltId][$cleanInterface]++;
                                    } else {
                                        $avgSignals[$oltId][$cleanInterface] = 1;
                                    }
                                }
                                if ((($macSignal > 0) or ($macSignal < -27))) {
                                    if (isset($badSignals[$oltId][$cleanInterface])) {
                                        $badSignals[$oltId][$cleanInterface]++;
                                    } else {
                                        $badSignals[$oltId][$cleanInterface] = 1;
                                    }
                                }
                            }

                            //storing PON ifaces descriptions, if not stored yet
                            if (
                                !isset($oltInterfaceDescrs[$oltId][$cleanInterface])
                                and !empty($ifaceDescrs) and !empty($ifaceDescrs[$cleanInterface])
                            ) {
                                $oltInterfaceDescrs[$oltId][$cleanInterface] = ' | ' . $ifaceDescrs[$cleanInterface];
                            }
                        }
                    }
                }
            }
        }

        if ((!empty($oltInterfacesFilled)) and (!empty($oltOnuFilled))) {
            foreach ($oltOnuFilled as $oltId => $oltFilledPercent) {
                $oltData->setOltId($oltId);
                $oltControls = '';
                $result .= wf_tag('a', false, '', 'name="go' . $oltId . '"') . wf_tag('a', true);
                $result .= wf_tag('h3');
                $result .= $this->allOltDevices[$oltId] . ' ' . __('filled on') . ' ' . $oltFilledPercent . '%';
                $result .= ' (' . $oltOnuCounters[$oltId] . ' ' . __('ONU') . ' ' . __('Registered') . ')';

                if (@$this->altCfg['PONMAP_ENABLED']) {
                    $oltControls .= ' ' . wf_Link(PONONUMap::URL_ME . '&' . PONONUMap::ROUTE_FILTER_OLT . '=' . $oltId, wf_img('skins/ponmap_icon.png', __('ONU Map')), false);
                }

                if ($ponScriptsFlag) {
                    if (cfr('PONSCRIPTS')) {
                        $ponScriptGoUrl = self::URL_ME . '&oltstats=true&' . $ponScripts::ROUTE_RENDER_OLT_SCRIPTS . '=' . $oltId . '#go' . $oltId;
                        $oltControls .= ' ' . wf_Link($ponScriptGoUrl, wf_img('skins/script16.png', __('Scripts')));
                    }
                }

                $result .= $oltControls;
                $result .= wf_tag('h3', true);
                if (isset($oltInterfacesFilled[$oltId])) {
                    $cells = wf_TableCell(__('Interface'));
                    $cells .= wf_TableCell(__('Count'));
                    $cells .= wf_TableCell(__('Mediocre signal'));
                    $cells .= wf_TableCell(__('Mediocre signal') . ' %');
                    $cells .= wf_TableCell(__('Bad signal'));
                    $cells .= wf_TableCell(__('Bad signal') . ' %');
                    $cells .= wf_TableCell(__('Visual'));
                    $rows = wf_TableRow($cells, 'row1');
                    foreach ($oltInterfacesFilled[$oltId] as $eachInterface => $eachInterfaceCount) {
                        $eachInterfacePercent = zb_PercentValue($oltOnuPonPortMax[$oltId], $eachInterfaceCount);

                        $oltIfaceDescr = ($this->showPONIfaceDescrStatsTab and !empty($oltInterfaceDescrs[$oltId][$eachInterface])) ? $oltInterfaceDescrs[$oltId][$eachInterface] : '';

                        $avgSignalCount = @$avgSignals[$oltId][$eachInterface];
                        $badSignalCount = @$badSignals[$oltId][$eachInterface];
                        $avgSignalColor = '';
                        $avgSignalColorEnd = '';
                        $avgSignalPercent = '';
                        $badSignalColor = '';
                        $badSignalColorEnd = '';
                        $badSignalPercent = '';
                        $interfaceFillColor = '';
                        $interfaceFillColorEnd = '';
                        $interfaceScriptsControls = '';
                        if ($ponScriptsFlag) {
                            if (cfr('PONSCRIPTS')) {
                                if ($oltId == $ponscriptsOltRender) {
                                    $interfaceScriptsControls = $ponScripts->renderIfaceControls($oltId, $eachInterface);
                                }
                            }
                        }

                        if ($eachInterfacePercent > 80) {
                            $interfaceFillColor = wf_tag('font', false, '', 'color="' . self::COLOR_AVG . '"') . wf_tag('b', false);
                            $interfaceFillColorEnd = wf_tag('b', true) . wf_tag('font', true);
                        }

                        if ($eachInterfacePercent > 90) {
                            $interfaceFillColor = wf_tag('font', false, '', 'color="' . self::COLOR_BAD . '"') . wf_tag('b', false);
                            $interfaceFillColorEnd = wf_tag('b', true) . wf_tag('font', true);
                        }


                        $interfaceFillLabel = $interfaceFillColor . $eachInterfaceCount . ' (' . $eachInterfacePercent . '%)' . $interfaceFillColorEnd;

                        if (!empty($avgSignalCount)) {
                            if ($avgSignalCount >= 3) {
                                $avgSignalColor = wf_tag('font', false, '', 'color="' . self::COLOR_AVG . '"') . wf_tag('b', false);
                                $avgSignalColorEnd = wf_tag('b', true) . wf_tag('font', true);
                            } else {
                                $avgSignalColor = '';
                                $avgSignalColorEnd = '';
                            }
                            $avgSignalPercent = zb_PercentValue($eachInterfaceCount, $avgSignalCount) . '%';
                        } else {
                            $avgSignalCount = '';
                        }

                        if (!empty($badSignalCount)) {
                            if ($badSignalCount >= 3) {
                                $badSignalColor = wf_tag('font', false, '', 'color="' . self::COLOR_BAD . '"') . wf_tag('b', false);
                                $badSignalColorEnd = wf_tag('b', true) . wf_tag('font', true);
                            } else {
                                $badSignalColor = '';
                                $badSignalColorEnd = '';
                            }
                            $badSignalPercent = zb_PercentValue($eachInterfaceCount, $badSignalCount) . '%';
                        } else {
                            $badSignalCount = '';
                        }

                        $eachInterfaceLabel = $eachInterface;
                        if ($this->ponIfDescribe) {
                            $controllerUrl = self::URL_ME . '&oltid=' . $oltId . '&if=' . $eachInterface;
                            $ponIfDescr = $this->ponInterfaces->getDescription($oltId, $eachInterface);
                            if (!empty($ponIfDescr)) {
                                $ponIfDescr = ' ' . $ponIfDescr;
                            }

                            if (cfr('PONEDIT')) {
                                $eachInterfaceLabel = wf_Link($controllerUrl, $eachInterface) . $ponIfDescr;
                            } else {
                                $eachInterfaceLabel = $eachInterface . ' ' . $ponIfDescr;
                            }
                        }

                        $cells = wf_TableCell($eachInterfaceLabel . $oltIfaceDescr . $interfaceScriptsControls);
                        $cells .= wf_TableCell($interfaceFillLabel, '', '', 'sorttable_customkey="' . $eachInterfaceCount . '"');
                        $cells .= wf_TableCell($avgSignalColor . $avgSignalCount . $avgSignalColorEnd);
                        $cells .= wf_TableCell($avgSignalPercent);
                        $cells .= wf_TableCell($badSignalColor . $badSignalCount . $badSignalColorEnd);
                        $cells .= wf_TableCell($badSignalPercent);
                        $cells .= wf_TableCell(web_bar($eachInterfaceCount, $oltOnuPonPortMax[$oltId]), '', '', 'sorttable_customkey="' . $eachInterfaceCount . '"');
                        $rows .= wf_TableRow($cells, 'row5');
                    }
                    $result .= wf_TableBody($rows, '100%', 0, 'sortable');
                    //gettin uptime
                    $oltUptime = $oltData->readUptime();
                    if (!empty($oltUptime)) {
                        $result .= __('Uptime') . ': ' . $oltUptime;
                    }
                    //getting temperature
                    $oltTemperature = $oltData->readTemperature();
                    if (!empty($oltTemperature)) {
                        $oltsTemps[$oltId] = $oltTemperature; //filling temp array
                        $result .= ' / ' . __('Temperature') . ': ' . $oltTemperature . '  °C';
                    }

                    $result .= wf_delimiter(0);
                }
            }

            //temperature gauges here
            if (ubRouting::checkGet('temperature')) {
                $result = $statsControls . wf_tag('br');
                if (!empty($oltsTemps)) {
                    foreach ($oltsTemps as $oltTempId => $oltTempValue) {
                        $result .= wf_renderTemperature($oltTempValue, $this->allOltDevices[$oltTempId]);
                    }
                    $result .= wf_CleanDiv();
                } else {
                    $messages = new UbillingMessageHelper();
                    $result .= $messages->getStyledMessage(__('Nothing to show'), 'warning');
                }
            }

            //or OLT polling timing stats
            if (ubRouting::checkGet('pollstats')) {
                if (!empty($this->allOltDevices)) {
                    $totalTime = 0;
                    $devicesPolled = 0;
                    $pollTimings = array();

                    $cells = wf_TableCell(__('ID'));
                    $cells .= wf_TableCell(__('OLT'));
                    $cells .= wf_TableCell(__('Model'));
                    $cells .= wf_TableCell('⏳ ' . __('from'));
                    $cells .= wf_TableCell('⌛ ' . __('to'));
                    $cells .= wf_TableCell('⏱️ ' . __('time'));
                    $cells .= wf_TableCell('📊 ' . __('Visual'));
                    $rows = wf_TableRow($cells, 'row1');

                    //poll timing preprocessing
                    foreach ($this->allOltDevices as $oltId => $eachDevice) {
                        $pollStats = $this->pollingStatsRead($oltId);
                        if (!empty($pollStats)) {
                            $devPollTime = 0;
                            if (!empty($pollStats['start']) and !empty($pollStats['end'])) {
                                $devPollTime = $pollStats['end'] - $pollStats['start'];
                                if ($herdEnabledFlag) {
                                    if ($devPollTime > $totalTime) {
                                        $totalTime = $devPollTime;
                                    }
                                } else {
                                    $totalTime += $devPollTime;
                                }
                            }

                            $pollTimings[$oltId]['start'] = $pollStats['start'];
                            $pollTimings[$oltId]['end'] = $pollStats['end'];
                            $pollTimings[$oltId]['finished'] = $pollStats['finished'];
                            $pollTimings[$oltId]['time'] = $devPollTime;
                        }
                    }

                    //rendering stats
                    if (!empty($pollTimings)) {
                        foreach ($pollTimings as $oltId => $pollStats) {
                            $pollingFinished = $pollStats['finished'];
                            if (!empty($pollStats['start'])) {
                                $pollingStartLabel = date("Y-m-d H:i:s", $pollStats['start']);
                            } else {
                                $pollingStartLabel = '-';
                            }
                            if (($pollingFinished) and (!empty($pollStats['start'])) and (!empty($pollStats['end']))) {
                                $pollingTimeLabel = zb_formatTime($pollStats['time']);
                                $pollingEndLabel = date("Y-m-d H:i:s", $pollStats['end']);
                                $visualLabel = web_bar($pollStats['time'], $totalTime);
                            } else {
                                $pollingTimeLabel = wf_tag('span', false, '', 'title="' . __('In progress now') . '"') . self::POLL_RUNNING . wf_tag('span', true);
                                $pollingTimeLabel .= ' ' . __('In progress now');
                                $pollingEndLabel = '-';
                                $visualLabel = '∞';
                            }

                            $oltModelLabel = '';
                            $oltModelId = (isset($this->allOltModelIds[$oltId])) ? $this->allOltModelIds[$oltId] : 0;
                            if ($oltModelId) {
                                if (isset($this->allOltModels[$oltModelId])) {
                                    $oltModelLabel = $this->allOltModels[$oltModelId]['modelname'];
                                }
                            }

                            $cells = wf_TableCell($oltId);
                            $cells .= wf_TableCell($this->allOltDevices[$oltId]);
                            $cells .= wf_TableCell($oltModelLabel);
                            $cells .= wf_TableCell($pollingStartLabel);
                            $cells .= wf_TableCell($pollingEndLabel);
                            $cells .= wf_TableCell($pollingTimeLabel, '', '', 'sorttable_customkey="' . $pollStats['time'] . '"');
                            $cells .= wf_TableCell($visualLabel, '20%', '', 'sorttable_customkey="' . $pollStats['time'] . '"');
                            $rows .= wf_TableRow($cells, 'row5');
                            $devicesPolled++;
                        }
                    }

                    $result = $statsControls;
                    $result .= wf_tag('h3') . __('SNMP query') . wf_tag('h3', true);
                    $result .= wf_TableBody($rows, '100%', 0, 'sortable');
                    $result .= wf_delimiter(0);
                    $result .= wf_tag('b') . __('Total') . ' ' . __('time') . ': ' . wf_tag('b', true) . zb_formatTime($totalTime) . wf_tag('br');
                    $result .= wf_tag('b') . __('Total') . ' ' . __('OLT') . ': ' . wf_tag('b', true) . $devicesPolled . wf_tag('br');
                } else {
                    $messages = new UbillingMessageHelper();
                    $result .= $messages->getStyledMessage(__('Nothing to show'), 'warning');
                }
            }
        } else {
            $messages = new UbillingMessageHelper();
            $result .= $messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        return ($result);
    }

    /**
     * Renders unknown ONU list container
     *
     * @return string
     */
    public function renderUnknownOnuList() {
        $result = '';

        if ($this->llidColVisibleUnknownONU) {
            $columns = array('OLT', 'Login', 'Address', 'Real Name', 'Tariff', 'IP', 'Interface', __('MAC') . ' ' . __('or') . ' ' . __('Serial'), 'Actions');
        } else {
            $columns = array('OLT', 'Login', 'Address', 'Real Name', 'Tariff', 'IP', __('MAC') . ' ' . __('or') . ' ' . __('Serial'), 'Actions');
        }
        $opts = '"order": [[ 0, "desc" ]]';
        $result = wf_JqDtLoader($columns, self::URL_ME . '&ajaxunknownonu=true', false, 'ONU', 100, $opts);
        $result .= wf_delimiter(0);
        return ($result);
    }

    /**
     * Returns current FDB cache list container with controls
     *
     * @return string
     */
    public function renderOnuFdbCache() {
        $result = wf_BackLink(self::URL_ONULIST);

        if (cfr('ROOT')) {
            //auto OLT associtation fixing interface
            $fixCancelUrl = self::URL_ME . '&fdbcachelist=true';
            $fixConfirmUrl = self::URL_ME . '&fdbcachelist=true&fixonuoltassings=true';
            $fixDialogLabel = wf_img('skins/icon_repair.gif') . ' ' . __('Fix OLT inconsistencies');
            $fixDialogNotice = __('This operation automatically remaps ONU assigns whith OLT devices from where last data was received for this ONUs');
            $result .= wf_ConfirmDialog($fixConfirmUrl, $fixDialogLabel, $fixDialogNotice, 'ubButton', $fixCancelUrl);
        }

        $result .= wf_delimiter();
        $columns = array('OLT', 'ONU', 'ID', 'Vlan', 'MAC', 'Address', 'Login', 'Real Name', 'Tariff');
        $opts = '"order": [[ 0, "desc" ]]';
        $result .= wf_JqDtLoader($columns, self::URL_ME . '&fdbcachelist=true&ajaxfdblist=true', false, 'ONU', 100, $opts);
        return ($result);
    }

    /**
     * Renders OLT FDB list container
     * 
     * @param int $onuid
     * @param string $customDataSource
     *
     * @return string
     */
    public function renderOltFdbList($onuid = '', $customDataSource = '') {
        $result = '';
        $columns = array('ID', 'Vlan', 'MAC', 'Address', 'Real Name', 'Tariff');
        $opts = '"order": [[ 0, "desc" ]]';
        if ($customDataSource) {
            $dataSource = $customDataSource . $onuid;
        } else {
            $dataSource = self::URL_ME . '&ajaxoltfdb=true&onuid=' . $onuid;
        }
        $result = wf_JqDtLoader($columns, $dataSource, false, 'ONU', 100, $opts);
        return ($result);
    }

    /**
     * Loads existing signal cache from FS
     *
     * @return void
     */
    protected function loadSignalsCache() {
        $this->signalCache = $this->reviewDataSet($this->oltData->getSignalsAll());
    }

    /**
     * Loads ONU distance cache
     *
     * @return void
     */
    protected function loadDistanceCache() {
        $this->distanceCache = $this->reviewDataSet($this->oltData->getDistancesAll());
    }

    /**
     * Loads ONU last dereg reasons cache
     *
     * @return void
     */
    protected function loadLastDeregCache() {
        $this->lastDeregCache = $this->reviewDataSet($this->oltData->getDeregsAll());
    }

    /**
     * Loads ONU interface cache
     *
     * @return void
     */
    protected function loadInterfaceCache() {
        $this->interfaceCache = $this->reviewDataSet($this->oltData->getInterfacesAll());
    }

    /**
     * Loads available OLTs PON interfaces descriptions
     * 
     * @return void
     */
    protected function loadPONIfaceDescrCache() {
        $this->ponIfaceDescrCache = $this->oltData->getInterfacesDescriptions();
    }

    /**
     * Loads OLT FDB cache
     *
     * @return void
     */
    protected function loadFDBCache() {
        $this->FDBCache = $this->reviewDataSet($this->oltData->getFdbAll());
    }

    protected function loadUniOperStatsCache() {
        $this->uniOperStatsCache = $this->reviewDataSet($this->oltData->getUniOperStatsAll());
    }

    /**
     * Fills onuIndexCache array
     * 
     * NOTICE: not similar with previous all - in readOnuCache() is [onuIdx]=>onuMac
     * REQUIRED: onuMac=>oltId
     *
     * @return void
     */
    protected function fillONUIndexCache() {
        $this->onuIndexCache = $this->reviewDataSet($this->oltData->getONUonOLTAll());
    }

    /**
     * Returns array of unknown ONUs MACs which can be filtered by OLT ID and returned just like simple array
     * or formed HTML selector ready to use on web page
     *
     * @param int $FilterByOLTID
     * @param bool $ReturnAsHTMLSelector
     * @param bool $AddEmptyFirsSelectorItem
     * @param string $HTMLSelectorID
     * @param string $HTMLSelectorName
     * @param string $HTMLSelectorLabel
     * @param string $HTMLSelectorSelectedItem
     * @param bool $HTMLSelectorBR
     * @param bool $HTMLSelectorSort
     *
     * @return array|string
     */
    public function getUnknownONUMACList($FilterByOLTID = 0, $ReturnAsHTMLSelector = false, $AddEmptyFirsSelectorItem = false, $HTMLSelectorID = 'nonameselectorid', $HTMLSelectorName = 'nonameselector', $HTMLSelectorLabel = '', $HTMLSelectorSelectedItem = '', $HTMLSelectorBR = false, $HTMLSelectorSort = false) {
        $UnknownONUList = ($ReturnAsHTMLSelector and $AddEmptyFirsSelectorItem) ? array('' => '-') : array();
        $this->fillONUIndexCache();

        if (!empty($this->onuIndexCache)) {
            foreach ($this->onuIndexCache as $onuMac => $oltId) {
                if (!empty($FilterByOLTID) and $oltId != $FilterByOLTID) {
                    continue;
                }

                //not registered?
                if ($this->checkOnuUnique($onuMac)) {
                    $UnknownONUList[$onuMac] = $onuMac;
                }
            }
        }

        return (($ReturnAsHTMLSelector) ? wf_Selector($HTMLSelectorName, $UnknownONUList, $HTMLSelectorLabel, $HTMLSelectorSelectedItem, $HTMLSelectorBR, $HTMLSelectorSort, $HTMLSelectorID) : $UnknownONUList);
    }

    /**
     * Renders json formatted data about unregistered ONU
     *
     * @return void
     */
    public function ajaxOnuUnknownData() {
        $json = new wf_JqDtHelper();
        $this->fillONUIndexCache();

        if (!empty($this->onuIndexCache)) {
            $allUsermacs = zb_UserGetAllMACs();
            $allUserData = zb_UserGetAllDataCache();

            if ($this->llidColVisibleUnknownONU) {
                $this->loadInterfaceCache();
            }

            foreach ($this->onuIndexCache as $onuMac => $oltId) {
                //not registered?
                if ($this->checkOnuUnique($onuMac)) {
                    $login = in_array($onuMac, array_map('strtolower', $allUsermacs)) ? array_search($onuMac, array_map('strtolower', $allUsermacs)) : '';
                    $userLink = $login ? wf_Link('?module=userprofile&username=' . $login, web_profile_icon() . ' ' . @$allUserData[$login]['login'] . '', false) : '';
                    $userLogin = $login ? @$allUserData[$login]['login'] : '';
                    $userRealnames = $login ? @$allUserData[$login]['realname'] : '';
                    $userTariff = $login ? @$allUserData[$login]['Tariff'] : '';
                    $userIP = $login ? @$allUserData[$login]['ip'] : '';
                    $LnkID = wf_InputId();

                    if ($this->llidColVisibleUnknownONU) {
                        $onuLLID = (empty($this->interfaceCache[$onuMac]) ? '' : $this->interfaceCache[$onuMac]);
                    }

                    $actControls = wf_tag('a', false, '', 'id="' . $LnkID . '" href="#" title="' . __('Register new ONU') . '"');
                    $actControls .= web_icon_create();
                    $actControls .= wf_tag('a', true);
                    $actControls .= wf_tag('script', false, '', 'type="text/javascript"');
                    $actControls .= '
                                        $(\'#' . $LnkID . '\').click(function(evt) {
                                            $.ajax({
                                                type: "GET",
                                                url: "' . self::URL_ME . '",
                                                data: { 
                                                        renderCreateForm:true,
                                                        renderDynamically:true, 
                                                        renderedOutside:true,
                                                        reloadPageAfterDone:false,
                                                        userLogin:"' . $userLogin . '",
                                                        userIP:"' . $userIP . '",                                                         
                                                        onumac:"' . $onuMac . '",                                                        
                                                        oltid:"' . $oltId . '",                                                        
                                                        ModalWID:"pon_dialog-modal_' . $LnkID . '", 
                                                        ModalWBID:"body_pon_dialog-modal_' . $LnkID . '",
                                                        ActionCtrlID:"' . $LnkID . '"
                                                       },
                                                success: function(result) {
                                                            $(document.body).append(result);
                                                            $(\'#pon_dialog-modal_' . $LnkID . '\').dialog("open");
                                                         }
                                            });
                    
                                            evt.preventDefault();
                                            return false;
                                        });
                                      ';
                    $actControls .= wf_tag('script', true);

                    $oltData = @$this->allOltDevices[$oltId];

                    if (!isset($this->hideOnuMac[$onuMac])) {
                        //brand new BDCOM issue temorary workaround. Broken serials too.
                        if (!ispos($onuMac, 'no:such') and !ispos($onuMac, PHP_EOL)) {
                            $data[] = $oltData;
                            $data[] = $userLink;
                            $data[] = @$allUserData[$login]['fulladress'];
                            $data[] = $userRealnames;
                            $data[] = $userTariff;
                            $data[] = $userIP;

                            if ($this->llidColVisibleUnknownONU) {
                                $data[] = $onuLLID;
                            }

                            $data[] = $onuMac;
                            $data[] = $actControls;

                            $json->addRow($data);
                            unset($data);
                        }
                    }
                }
            }
        }

        $json->getJson();
    }

    /**
     * Returns state of ONU_RENDER_MODE misc section option for some olt, if it exists.
     * 
     * @param int $oltId
     * 
     * @return string mac/serial
     */
    protected function getOltOnuRenderMode($oltId) {
        $result = 'mac';
        if (isset($this->allOltSnmp[$oltId])) {
            $oltModelId = $this->allOltSnmp[$oltId]['modelid'];

            if (isset($this->snmpTemplates[$oltModelId])) {
                $oltTemplate = $this->snmpTemplates[$oltModelId];
                if (isset($oltTemplate['misc'])) {
                    if (isset($oltTemplate['misc']['ONU_RENDER_MODE'])) {
                        $result = $oltTemplate['misc']['ONU_RENDER_MODE'];
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Renders json formatted data for jquery data tables list with ONU signals list
     *
     * @param string $OltId
     * @return void
     */
    public function ajaxOnuData($OltId) {
        $OnuByOLT = $this->getOnuArrayByOltID($OltId);
        $json = new wf_JqDtHelper();
        $allRealnames = zb_UserGetAllRealnames();
        $allAddress = zb_AddressGetFulladdresslistCached();
        $allTariffs = zb_TariffsGetAllUsers();
        $burialEnabled = @$this->altCfg['ONU_BURIAL_ENABLED'];
        $noSignalLabel = __('No');
        $fromCache = false;
        $oltOnuRenderMode = $this->getOltOnuRenderMode($OltId);

        //try to get all data from cache
        if ($this->onuCacheTimeout) {
            $ajData = $this->cache->get(self::KEY_ONULISTAJ . $OltId, $this->onuCacheTimeout);
            if (!empty($ajData)) {
                $fromCache = true;
            }
        }


        if (!$fromCache) {
            if ($this->altCfg['ADCOMMENTS_ENABLED']) {
                $adcomments = new ADcomments('PONONU');
                $adc = true;
            } else {
                $adc = false;
            }

            $this->loadSignalsCache();

            $distCacheAvail = $this->oltData->isDistancesAvailable();
            if ($distCacheAvail) {
                $this->loadDistanceCache();
            }

            $intCacheAvail = $this->oltData->isInterfacesAvailable();
            if ($intCacheAvail) {
                $this->loadInterfaceCache();
            }

            $intDescrCacheAvail = $this->oltData->isInterfacesDescriptionsAvailable();
            $curOLTIfaceDescrs = array();
            if ($intDescrCacheAvail) {
                $this->loadPONIfaceDescrCache();
                if (!empty($this->ponIfaceDescrCache[$OltId])) {
                    $intDescrCacheAvail = true;
                    $curOLTIfaceDescrs = $this->ponIfaceDescrCache[$OltId];
                } else {
                    $intDescrCacheAvail = false;
                }
            }

            $lastDeregCacheAvail = $this->oltData->isDeregsAvailable();
            if ($lastDeregCacheAvail) {
                $this->loadLastDeregCache();
            }

            if (!empty($OnuByOLT)) {
                foreach ($OnuByOLT as $io => $each) {
                    $renderThisOnu = true;
                    //not show buried ONUs
                    if ($burialEnabled) {
                        if ($each['login'] == 'dead') {
                            $renderThisOnu = false;
                        }
                    }

                    if ($renderThisOnu) {
                        $userTariff = '';
                        $ONUIsOffline = false;

                        if (!empty($each['login'])) {
                            $userLogin = trim($each['login']);
                            if (isset($allAddress[$userLogin])) {
                                $userLink = wf_Link('?module=userprofile&username=' . $userLogin, web_profile_icon() . ' ' . $allAddress[$userLogin], false);
                            } else {
                                $userLink = wf_Link('?module=userprofile&username=' . $userLogin, web_profile_icon(), false) . ' ' . $userLogin;
                            }

                            @$userRealName = $allRealnames[$userLogin];

                            //tariff data
                            if (isset($allTariffs[$userLogin])) {
                                $userTariff = $allTariffs[$userLogin];
                            }
                        } else {
                            $userLink = '';
                            $userRealName = '';
                        }
                        //checking adcomments availability
                        if ($adc) {
                            $indicatorIcon = $adcomments->getCommentsIndicator($each['id']);
                        } else {
                            $indicatorIcon = '';
                        }

                        $actLinks = wf_Link('?module=ponizer&editonu=' . $each['id'], web_edit_icon(), false);
                        $actLinks .= ' ' . $indicatorIcon;

                        //coloring signal
                        if (isset($this->signalCache[$each['mac']])) {
                            $signal = $this->signalCache[$each['mac']];
                            if (($signal > 0) or ($signal < -27)) {
                                $sigColor = self::COLOR_BAD;
                            } elseif ($signal > -27 and $signal < -25) {
                                $sigColor = self::COLOR_AVG;
                            } else {
                                $sigColor = self::COLOR_OK;
                            }

                            if ($signal == self::NO_SIGNAL) {
                                $ONUIsOffline = true;
                                $signal = $noSignalLabel;
                                $sigColor = self::COLOR_NOSIG;
                            }
                        } elseif (isset($this->signalCache[$each['serial']])) {
                            $signal = $this->signalCache[$each['serial']];
                            if (($signal > 0) or ($signal < -27)) {
                                $sigColor = self::COLOR_BAD;
                            } elseif ($signal > -27 and $signal < -25) {
                                $sigColor = self::COLOR_AVG;
                            } else {
                                $sigColor = self::COLOR_OK;
                            }

                            if ($signal == self::NO_SIGNAL) {
                                $ONUIsOffline = true;
                                $signal = $noSignalLabel;
                                $sigColor = self::COLOR_NOSIG;
                            }
                        } else {
                            $ONUIsOffline = true;
                            $signal = $noSignalLabel;
                            $sigColor = self::COLOR_NOSIG;
                        }

                        $data[] = $each['id'];

                        if ($intCacheAvail) {
                            if (isset($this->interfaceCache[$each['mac']])) {
                                $ponInterface = $this->interfaceCache[$each['mac']];
                            } else {
                                if (isset($this->interfaceCache[$each['serial']])) {
                                    $ponInterface = $this->interfaceCache[$each['serial']];
                                } else {
                                    $ponInterface = '';
                                }
                            }

                            $cleanInterface = strstr($ponInterface, ':', true);
                            $oltIfaceDescr = ($this->showPONIfaceDescrMainTab and $intDescrCacheAvail and !empty($curOLTIfaceDescrs[$cleanInterface])) ? $curOLTIfaceDescrs[$cleanInterface] . ' | ' : '';
                            $data[] = $oltIfaceDescr . $ponInterface;
                        }

                        $data[] = $this->getModelName($each['onumodelid']);
                        if ($this->ipColumnVisible) {
                            $data[] = $each['ip'];
                        }

                        //MAC/Serial column here
                        if ($oltOnuRenderMode == 'mac') {
                            $onuIdent = $each['mac'];
                        } else {
                            $onuIdent = $each['serial'];
                        }
                        $data[] = $onuIdent;

                        $data[] = wf_tag('font', false, '', 'color=' . $sigColor . '') . $signal . wf_tag('font', true);

                        if ($distCacheAvail) {
                            if (isset($this->distanceCache[$each['mac']])) {
                                $data[] = $this->distanceCache[$each['mac']];
                            } else {
                                if (isset($this->distanceCache[$each['serial']])) {
                                    $data[] = $this->distanceCache[$each['serial']];
                                } else {
                                    $data[] = '';
                                }
                            }
                        }

                        if ($lastDeregCacheAvail) {
                            if ($ONUIsOffline) {
                                $data[] = @$this->lastDeregCache[$each['mac']];
                            } else {
                                $data[] = '';
                            }
                        }

                        $data[] = $userLink;
                        $data[] = $userRealName;
                        $data[] = $userTariff;
                        $data[] = $actLinks;

                        $json->addRow($data);
                        unset($data);
                    }
                }
            }

            //extract json data
            $ajData = $json->extractJson();

            //update cache if required
            if ($this->onuCacheTimeout and !$fromCache) {
                $this->cache->set(self::KEY_ONULISTAJ . $OltId, $ajData, $this->onuCacheTimeout);
            }
        }

        die($ajData);
    }

    /**
     * Renders json formatted data for jquery data tables list
     *
     * @param string $onuId
     * @return void
     */
    public function ajaxOltFdbData($onuId) {
        $json = new wf_JqDtHelper();
        $fdbPointer = '';
        $selfFilterFlag = (@$this->altCfg['PON_ONU_FDB_SELFFILTER']) ? true : false;

        if (!empty($onuId)) {
            $allUserTariffs = zb_TariffsGetAllUsers();
            $onuMacId = @$this->allOnu[$onuId]['mac'];
            $onuSerialId = @$this->allOnu[$onuId]['serial'];
            $fdbCacheAvail = $this->oltData->isFdbAvailable();

            if ($fdbCacheAvail) {
                $this->loadFDBCache();
            } else {
                $fdbCacheAvail = false;
            }

            if (isset($this->FDBCache[$onuMacId])) {
                $fdbPointer = $this->FDBCache[$onuMacId];
            }

            if (isset($this->FDBCache[$onuSerialId])) {
                $fdbPointer = $this->FDBCache[$onuSerialId];
            }

            if ($fdbCacheAvail && $fdbPointer) {
                $getLoginMac = zb_UserGetAllMACs();
                $allAddress = zb_AddressGetFulladdresslistCached();
                $allRealnames = zb_UserGetAllRealnames();

                foreach ($fdbPointer as $id => $fdbData) {
                    $filtered = true;
                    if ($selfFilterFlag) {
                        $filtered = false;
                        if ($fdbData['mac'] != $onuMacId) {
                            $filtered = true;
                        }
                    }

                    if ($filtered) {
                        $login = in_array($fdbData['mac'], array_map('strtolower', $getLoginMac)) ? array_search($fdbData['mac'], array_map('strtolower', $getLoginMac)) : '';
                        $userLink = $login ? wf_Link('?module=userprofile&username=' . $login, web_profile_icon() . ' ' . @$allAddress[$login], false) : '';
                        $userRealnames = $login ? @$allRealnames[$login] : '';
                        $userTariff = (isset($allUserTariffs[$login])) ? $allUserTariffs[$login] : '';

                        $data[] = $id;
                        $data[] = $fdbData['vlan'];
                        $data[] = $fdbData['mac'];
                        $data[] = @$userLink;
                        $data[] = @$userRealnames;
                        $data[] = $userTariff;

                        $json->addRow($data);
                        unset($data);
                    }
                }
            }
        }

        $json->getJson();
    }

    /**
     * Checks is ONU really associated with some OLT
     *
     * @param string $onuMac
     * @param int $oltId
     * @return bool
     */
    protected function checkOnuOLTid($onuMac, $oltId) {
        $result = true;
        $sn = strtoupper($onuMac);
        if (!empty($this->onuMacOltidList)) {
            if (isset($this->onuMacOltidList[$onuMac])) {
                if ($this->onuMacOltidList[$onuMac] != $oltId) {
                    $result = false;
                }
            }
        }
        if (!empty($this->onuSerialOltidList)) {
            if (isset($this->onuSerialOltidList[$sn])) {
                if ($this->onuSerialOltidList[$sn] != $oltId) {
                    $result = false;
                }
            }
        }

        return ($result);
    }

    /**
     * Checks is ONU associated with some login or not
     *
     * @param int $onuId
     * @param string $userLogin
     *
     * @return bool
     */
    protected function checkOnuUserAssign($onuId, $userLogin) {
        $result = true;
        if (@$this->altCfg['PON_USERLINK_CHECK']) {
            //ONU is registered
            if ($onuId != 0) {
                @$associatedUserLogin = $this->allOnu[$onuId]['login'];
            } else {
                $associatedUserLogin = '';
            }

            if (!empty($associatedUserLogin)) {
                if ($userLogin != $associatedUserLogin) {
                    $result = false;
                } else {
                    $result = true;
                }
            }

            //something strange
            if ($result == false) {
                $onuExtUsers = $this->getOnuExtUsers($onuId);
                if (!empty($onuExtUsers)) {
                    foreach ($onuExtUsers as $io => $each) {
                        if ($each['login'] == $userLogin) {
                            $result = true;
                        }
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Renders json for current all OLT FDB list
     *
     * @return void
     */
    public function ajaxFdbCacheList() {
        $json = new wf_JqDtHelper();
        $availOnuFdbCache = $this->oltData->isFdbAvailable();
        $selfFilterFlag = (@$this->altCfg['PON_ONU_FDB_SELFFILTER']) ? true : false;

        if (!empty($availOnuFdbCache)) {
            $availOnuFdbCache = $this->oltData->getFdbOLTAll();
            $allAddress = zb_AddressGetFulladdresslistCached();
            $allRealnames = zb_UserGetAllRealnames();
            $allUserMac = zb_UserGetAllMACs();
            $allUserMac = array_map('strtolower', $allUserMac);
            $allUserMac = array_flip($allUserMac);
            $allUserTariffs = zb_TariffsGetAllUsers();
            $allSwtiches = zb_SwitchesGetAll();
            $allSwitchesMacs = array();
            if (!empty($allSwtiches)) {
                foreach ($allSwtiches as $io => $each) {
                    if (!empty($each['swid'])) {
                        $allSwitchesMacs[$each['swid']] = $each['ip'] . ' ' . $each['location'];
                    }
                }
            }

            foreach ($availOnuFdbCache as $oltId => $eachOltFdb) {
                $oltDesc = @$this->allOltDevices[$oltId];
                if (!empty($eachOltFdb)) {
                    foreach ($eachOltFdb as $onuMac => $onuTmp) {
                        if (!empty($onuTmp)) {
                            foreach ($onuTmp as $id => $onuData) {
                                $filtered = true;
                                if ($selfFilterFlag) {
                                    $filtered = false;
                                    if ($onuData['mac'] != $onuMac) {
                                        $filtered = true;
                                    }
                                }

                                if ($filtered) {
                                    $onuRealId = $this->getOnuIDbyIdent($onuMac);
                                    if ($onuRealId) {
                                        $associatedUserLogin = $this->allOnu[$onuRealId]['login'];
                                    } else {
                                        $associatedUserLogin = '';
                                    }
                                    $userLogin = (isset($allUserMac[$onuData['mac']])) ? $allUserMac[$onuData['mac']] : '';
                                    $onuLink = ($onuRealId) ? wf_Link(self::URL_ME . '&editonu=' . $onuRealId, $id) : $id;
                                    @$userAddress = $allAddress[$userLogin];
                                    @$userRealName = $allRealnames[$userLogin];
                                    @$userTariff = $allUserTariffs[$userLogin];
                                    $userLink = (!empty($userLogin)) ? wf_Link('?module=userprofile&username=' . $userLogin, web_profile_icon() . ' ' . $userAddress) : '';
                                    $oltCheck = (!$this->checkOnuOLTid($onuMac, $oltId)) ? ' ' . wf_img('skins/createtask.gif', __('Wrong OLT')) . ' ' . __('Oh no') : '';
                                    $userCheck = (!$this->checkOnuUserAssign($onuRealId, $userLogin)) ? ' ' . wf_img('skins/createtask.gif', __('Wrong associated user')) . ' ' . __('Oh no') : '';
                                    if (isset($allSwitchesMacs[$onuData['mac']])) {
                                        $userCheck = ' ' . wf_img('skins/menuicons/switches.png', __('Switch behind ONU')) . ' ' . __('Switch') . '!';
                                        $userLink .= $allSwitchesMacs[$onuData['mac']];
                                    }
                                    $data[] = $oltDesc . $oltCheck;
                                    $data[] = $onuMac;
                                    $data[] = $onuLink;
                                    $data[] = $onuData['vlan'];
                                    $data[] = $onuData['mac'] . $userCheck;
                                    $data[] = $userLink;
                                    $data[] = $associatedUserLogin;
                                    $data[] = $userRealName;
                                    $data[] = $userTariff;

                                    $json->addRow($data);
                                    unset($data);
                                }
                            }
                        }
                    }
                }
            }
        }
        $json->getJson();
    }

    /**
     * Automatically fixes ONU to OLT associations due the actual FDB cache data
     *
     * @return void
     */
    public function fixOnuOltAssigns() {
        $result = '';
        $result = wf_BackLink(self::URL_ME . '&fdbcachelist=true');

        $failedOnuFound = false;
        $repairConfirmed = (ubRouting::checkGet('autorepairconfirmed')) ? true : false;
        $totalCount = 0;
        $availOnuSigCache = $this->oltData->getSignalsOLTAll();
        if (!empty($availOnuSigCache)) {
            foreach ($availOnuSigCache as $oltId => $eachOltSignals) {
                $oltDesc = @$this->allOltDevices[$oltId];
                if (!empty($eachOltSignals)) {
                    foreach ($eachOltSignals as $onuMac => $onuSignal) {
                        $onuRealId = $this->getOnuIDbyIdent($onuMac);
                        $onuLink = ($onuRealId) ? wf_Link(self::URL_ME . '&editonu=' . $onuRealId, $onuRealId) : '';
                        if ($onuRealId) {
                            $wrongOltFlag = (!$this->checkOnuOLTid($onuMac, $oltId)) ? true : false;
                            if ($wrongOltFlag) {
                                $totalCount++;
                                $failedOnuFound = true; //set once
                                $onuData = $this->allOnu[$onuRealId];
                                $wrongOltId = $onuData['oltid'];
                                $wrongOltDesc = @$this->allOltDevices[$wrongOltId];
                                if (empty($wrongOltDesc)) {
                                    $wrongOltDesc = '[' . $wrongOltId . '] ' . __('Unknown');
                                }

                                $missmatchLabel = __('ONU') . ' [ ' . $onuLink . '] ' . __('wrong') . ' ' . __('OLT') . ' ' . $wrongOltDesc . ', ';
                                $missmatchLabel .= __('must be') . ' ' . $oltDesc;
                                $result .= $this->messages->getStyledMessage($missmatchLabel, 'warning');
                                if ($repairConfirmed) {
                                    if (isset($this->allOltDevices[$oltId])) {
                                        if (isset($this->allOnu[$onuRealId])) {
                                            $this->onuDb->where('id', '=', $onuRealId);
                                            $this->onuDb->data('oltid', $oltId);
                                            $this->onuDb->save();
                                            log_register('PON REMAP ONU [' . $onuRealId . '] MAC `' . $onuData['mac'] . '` OLT [' . $wrongOltId . '] TO [' . $oltId . ']');
                                            $repairLabel = __('ONU') . ' [ ' . $onuLink . '] ' . __('assigned') . ' ' . __('OLT') . ' ' . $oltDesc . '!';
                                            $result .= $this->messages->getStyledMessage($repairLabel, 'success');
                                        } else {
                                            $result .= $this->messages->getStyledMessage(__('ONU') . ' [' . $onuRealId . '] ' . __('Not exists'), 'error');
                                        }
                                    } else {
                                        $result .= $this->messages->getStyledMessage(__('OLT') . ' [' . $oltId . '] ' . __('Not exists'), 'error');
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }

        if ($failedOnuFound) {
            //totals rendering
            $result .= $this->messages->getStyledMessage(__('Total') . ' ' . __('ONU') . ' ' . __('wrong') . ': ' . $totalCount, 'info');
            $result .= wf_delimiter();
            $repairConfirmUrl = self::URL_ME . '&fdbcachelist=true&fixonuoltassings=true&autorepairconfirmed=true';
            $result .= wf_JSAlert($repairConfirmUrl, wf_img('skins/icon_repair.gif') . ' ' . __('Fix') . '?', $this->messages->getEditAlert(), '', 'ubButton');
        } else {
            $result .= $this->messages->getStyledMessage(__('Everything is Ok'), 'success');
        }
        return ($result);
    }

    /**
     * Returns ONU create and assign form for user profile module
     *
     * @param $userLogin
     * @param $allUserData
     *
     * @return string
     */
    public function renderCpeUserControls($userLogin, $allUserData) {
        $result = '';
        $userHasCPE = false;

        // if there is no assigned ONU with $userLogin yet
        $userHasCPE = $this->getOnuIdByUser($userLogin);

        if (empty($userHasCPE)) {
            $LnkID = wf_InputId();
            $userIP = $allUserData[$userLogin]['ip'];
            $userMAC = $allUserData[$userLogin]['mac'];

            $result .= wf_tag('br') . wf_tag('b') . __('Users PON equipment') . wf_tag('b', true) . wf_tag('br');
            $result .= wf_Link(self::URL_ME . '&unknownonulist=true', wf_img('skins/icon_link.gif') . ' ' . __('Assign PON equipment to user'), false, 'ubButton') . '&nbsp';
            $result .= wf_modalAutoForm(__('Create new CPE'), '', 'dialog-modal_' . $LnkID, 'body_dialog-modal_' . $LnkID);
            $result .= wf_tag('a', false, 'ubButton', 'id="' . $LnkID . '" href="#"');
            $result .= web_icon_create() . ' ' . __('Create new CPE');
            $result .= wf_tag('a', true);
            $result .= wf_tag('script', false, '', 'type="text/javascript"');
            $result .= wf_JSElemInsertedCatcherFunc();
            $result .= wf_JSEmptyFunc();
            $result .= '
                        function checkONUAssignment() {
                            if ( typeof( $(\'input[name=newmac]\').val() ) === "string" && $(\'input[name=newmac]\').val().length > 0 ) {
                                $.ajax({
                                    type: "GET",
                                    url: "?module=ponizer",
                                    data: {action:\'checkONUAssignment\', onumac:$(\'input[name=newmac]\').val()},
                                    success: function(result) {
                                                $(\'#onuassignment2\').text(result);
                                             }
                                });
                            } else {$(\'#onuassignment2\').text(\'\');}
                        }        
            
                        function dynamicBindClick(ctrlClassName) {
                            $(document).on("click", ctrlClassName, function(evt) {
                                evt.preventDefault();
                                checkONUAssignment($(ctrlClassName).val());                                
                                return false;         
                            });
                        }
            
                        onElementInserted(\'body\', \'.__CheckONUAssignmentBtn\', function(element) {
                            dynamicBindClick(\'.__CheckONUAssignmentBtn\');
                        });
                
                        $(\'#' . $LnkID . '\').click(function(evt) {
                            $.ajax({
                                type: "GET",
                                url: "' . self::URL_ME . '",                              
                                data: {
                                    renderCreateForm:true,
                                    renderedOutside:true,
                                    reloadPageAfterDone:true,
                                    userLogin:"' . $userLogin . '",
                                    onumac:"' . $userMAC . '",
                                    userIP:"' . $userIP . '",
                                    oltid:"",
                                    ActionCtrlID:"' . $LnkID . '",
                                    ModalWID:"dialog-modal_' . $LnkID . '"
                                },
                                success: function(result) { 
                                            $(\'#body_dialog-modal_' . $LnkID . '\').html(result);
                                            $(\'#dialog-modal_' . $LnkID . '\').dialog("open");
                                         }
                            });
                            
                            evt.preventDefault();
                            return false;
                        });
                        ';
            $result .= wf_tag('script', true);
            $result .= wf_delimiter();
        }

        return ($result);
    }

    /**
     * Returns ONU signals array like: $onuId => $onuSignal
     *
     * @return array
     */
    public function getAllONUSignalsById() {
        $result = array();
        $allOnu = $this->getAllOnu();
    
        if (!empty($allOnu)) {
            foreach ($allOnu as $eachOnu) {
                $result[$eachOnu['id']] = $eachOnu['signal'];
            }
        }
        return ($result);
    }

    /**
     * Returns ONU signals array like: $userLogin => $onuSignal
     *
     * @return array
     */
    public static function getAllONUSignals() {
        global $ubillingConfig;
        $result = array();
        $oltData = new OLTAttractor();
        $signalCache = $oltData->getSignalsAll();
        $onuMACValidateRegex = '/^([[:xdigit:]]{2}[\s:.-]?){5}[[:xdigit:]]{2}$/';
        $validateONUMACEnabled = $ubillingConfig->getAlterParam('PON_ONU_MAC_VALIDATE');

        //not using $this->onuDb here, because static method call possible
        $onuDb = new NyanORM(self::TABLE_ONUS);
        $onuDb->whereRaw("`login` != '' and NOT ISNULL(`login`)");
        $allOnuRecs = $onuDb->getAll();

        if (!empty($allOnuRecs) and !empty($signalCache)) {
            //Preprocess MACs if enabled. 
            //Not using reviewDataSet here, because static method call possible
            if ($validateONUMACEnabled) {
                foreach ($signalCache as $mac => $signal) {
                    if ($validateONUMACEnabled) {
                        $matches = array();
                        preg_match($onuMACValidateRegex, $mac, $matches);

                        if (empty($matches[0])) {
                            unset($signalCache[$mac]);
                        }
                    }
                }
            }

            foreach ($allOnuRecs as $io => $each) {
                if (isset($signalCache[$each['mac']])) {
                    $result[$each['login']] = $signalCache[$each['mac']];
                }
                if (isset($signalCache[$each['serial']])) {
                    $result[$each['login']] = $signalCache[$each['serial']];
                }
            }
        }

        return ($result);
    }

    /**
     * Return all of last dereg reasons as userLogin=>deregReason[raw/styled]
     * 
     * @return array
     */
    public function getAllONUDeregReasons() {
        global $ubillingConfig;
        $result = array();
        $onuMACValidateRegex = '/^([[:xdigit:]]{2}[\s:.-]?){5}[[:xdigit:]]{2}$/';
        $validateONUMACEnabled = $ubillingConfig->getAlterParam('PON_ONU_MAC_VALIDATE');

        $oltData = new OLTAttractor();
        $deregsCache = $oltData->getDeregsAll();
        $onuDb = new NyanORM(self::TABLE_ONUS);
        $onuDb->whereRaw("`login` != '' and NOT ISNULL(`login`)");
        $allOnuRecs = $onuDb->getAll();

        if (!empty($allOnuRecs) and !empty($deregsCache)) {
            //Preprocess MACs if enabled. 
            if ($validateONUMACEnabled) {
                foreach ($deregsCache as $mac => $dereg) {
                    if ($validateONUMACEnabled) {
                        $matches = array();
                        preg_match($onuMACValidateRegex, $mac, $matches);

                        if (empty($matches[0])) {
                            unset($deregsCache[$mac]);
                        }
                    }
                }
            }

            foreach ($allOnuRecs as $io => $each) {
                if (isset($deregsCache[$each['mac']])) {
                    $result[$each['login']]['raw'] = strip_tags($deregsCache[$each['mac']]);
                    $result[$each['login']]['styled'] = $deregsCache[$each['mac']];
                }
                if (isset($deregsCache[$each['serial']])) {
                    $result[$each['login']]['raw'] = strip_tags($deregsCache[$each['serial']]);
                    $result[$each['login']]['styled'] = $deregsCache[$each['serial']];
                }
            }
        }
        return ($result);
    }

    /**
     * Just generates random MAC address to replace invalid ONU MAC
     *
     * @return string
     */
    protected function getRandomMac() {
        $result = $result = 'ff:' . '00' . ':' . rand(10, 99) . ':' . rand(10, 99) . ':' . rand(10, 99) . ':' . '00';
        return ($result);
    }

    /**
     * Validate ONUs MAC against regex and return bool value
     *
     * @param $onuMAC
     *
     * @return bool
     */
    public function validateONUMAC($onuMAC) {
        $matches = array();
        preg_match($this->onuMACValidateRegex, $onuMAC, $matches);
        return (!empty($matches[0]));
    }

    /**
     * Returns validated MAC or replaces it with random one
     * 
     * @param string $mac
     * 
     * @return string
     */
    protected function validatedMac($mac) {
        if ($this->validateONUMACEnabled and !$this->validateONUMAC($mac)) {
            if ($this->replaceInvalidONUMACWithRandom) {
                $mac = $this->getRandomMac();
            }
        }
        return ($mac);
    }

    /**
     * Performs validation of some data set if required as onuMac=>someValue
     * 
     * @param array $dataSet
     * 
     * @return array
     */
    protected function reviewDataSet($dataSet) {
        $result = array();
        if ($this->validateONUMACEnabled) {
            if (!empty($dataSet)) {
                foreach ($dataSet as $onuIdent => $someValue) {
                    $result[$this->validatedMac($onuIdent)] = $someValue;
                }
            }
        } else {
            return ($dataSet);
        }
        return ($result);
    }

    /**
     * Renders ONU search form
     *
     * @return string
     */
    public function renderOnuSearchForm() {
        $result = '';
        if (!empty($this->allOnu)) {
            $inputs = '';
            $searchQueryPreset = (ubRouting::checkPost('onusearchquery')) ? ubRouting::post('onusearchquery', 'mres') : '';

            $inputs .= wf_TextInput('onusearchquery', '', $searchQueryPreset, true, 40) . ' ';
            $inputs .= __('Search by') . ':' . wf_delimiter(0);
            if (ubRouting::checkPost('onusearchquery')) {
                //saving checkbox state between queries
                $macChecked = (ubRouting::checkPost('searchmac')) ? true : false;
                $loginChecked = (ubRouting::checkPost('searchlogin')) ? true : false;
                $serialChecked = (ubRouting::checkPost('searchserial')) ? true : false;
                $ipChecked = (ubRouting::checkPost('searchip')) ? true : false;
                $idChecked = (ubRouting::checkPost('searchonuid')) ? true : false;
            } else {
                //default checkbox state
                $macChecked = true;
                $loginChecked = true;
                $serialChecked = true;
                $ipChecked = true;
                $idChecked = true;
            }

            $inputs .= wf_CheckInput('searchmac', __('MAC'), true, $macChecked);
            $inputs .= wf_CheckInput('searchlogin', __('Login'), true, $loginChecked);
            $inputs .= wf_CheckInput('searchserial', __('Serial number'), true, $serialChecked);
            $inputs .= wf_CheckInput('searchip', __('IP'), true, $ipChecked);
            $inputs .= wf_CheckInput('searchonuid', __('ONU') . ' ' . __('ID'), true, $idChecked);
            $inputs .= wf_delimiter(0);
            $inputs .= wf_Submit(__('Search'));

            $result .= wf_Form(self::URL_ME . '&onusearch=true', 'POST', $inputs, 'glamour');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing found'), 'warning', 'style="width:300px;"');
        }

        return ($result);
    }

    /**
     * Catches ONU search request and renders some result
     *
     * @return string
     */
    public function renderOnuSearchResult() {
        $result = '';
        $resultTmp = array();
        $messages = new UbillingMessageHelper();

        if (!empty($this->allOnu)) {
            $searchQuery = ubRouting::post('onusearchquery', 'mres');
            if (!empty($searchQuery)) {
                //search fields flags 
                $macChecked = (ubRouting::checkPost('searchmac')) ? true : false;
                $loginChecked = (ubRouting::checkPost('searchlogin')) ? true : false;
                $serialChecked = (ubRouting::checkPost('searchserial')) ? true : false;
                $ipChecked = (ubRouting::checkPost('searchip')) ? true : false;
                $idChecked = (ubRouting::checkPost('searchonuid')) ? true : false;

                //processing some search
                foreach ($this->allOnu as $eachOnuId => $eachOnuData) {
                    if ($macChecked) {
                        $rawMac = str_replace(array(':', '.', '-'), '', $eachOnuData['mac']);
                        $macSearchQuery = str_replace(array(':', '.', '-'), '', $searchQuery);
                        if (ispos($eachOnuData['mac'], $searchQuery) or ispos($rawMac, $macSearchQuery)) {
                            $resultTmp[$eachOnuId] = $eachOnuData;
                        }
                    }

                    if ($loginChecked) {
                        if (ispos($eachOnuData['login'], $searchQuery)) {
                            $resultTmp[$eachOnuId] = $eachOnuData;
                        }
                    }

                    if ($serialChecked) {
                        if (ispos($eachOnuData['serial'], $searchQuery)) {
                            $resultTmp[$eachOnuId] = $eachOnuData;
                        }
                    }

                    if ($ipChecked) {
                        if (ispos($eachOnuData['ip'], $searchQuery)) {
                            $resultTmp[$eachOnuId] = $eachOnuData;
                        }
                    }

                    if ($idChecked) {
                        if ($eachOnuData['id'] === $searchQuery) {
                            $resultTmp[$eachOnuId] = $eachOnuData;
                        }
                    }
                }
                //something found
                if (!empty($resultTmp)) {
                    $result .= $this->renderOnuArray($resultTmp);
                } else {
                    $result .= $messages->getStyledMessage(__('Nothing found'), 'warning');
                }
            }
        } else {
            $result .= $messages->getStyledMessage(__('Nothing to show'), 'error');
        }
        return ($result);
    }

    /**
     * Renders ONU Array just as table list with some controls
     *
     * @param array $onuArray
     *
     * @return string
     */
    protected function renderOnuArray($onuArray) {
        $result = '';

        if (!empty($onuArray)) {
            $count = 0;
            $cells = wf_TableCell(__('ID'));
            $cells .= wf_TableCell(__('OLT'));
            $cells .= wf_TableCell(__('Model'));
            if ($this->ipColumnVisible) {
                $cells .= wf_TableCell(__('IP'));
            }
            $cells .= wf_TableCell(__('Serial number'));
            $cells .= wf_TableCell(__('MAC'));
            $cells .= wf_TableCell(__('User'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');

            foreach ($onuArray as $eachOnuId => $eachOnuData) {
                $cells = wf_TableCell($eachOnuId);
                $cells .= wf_TableCell(@$this->allOltNames[$eachOnuData['oltid']]);
                $cells .= wf_TableCell(@$this->allModelsData[$eachOnuData['onumodelid']]['modelname']);
                if ($this->ipColumnVisible) {
                    $cells .= wf_TableCell($eachOnuData['ip']);
                }
                $cells .= wf_TableCell($eachOnuData['serial']);
                $cells .= wf_TableCell($eachOnuData['mac']);
                if (!empty($eachOnuData['login'])) {
                    $userLink = wf_Link(self::URL_USERPROFILE . $eachOnuData['login'], web_profile_icon() . ' ' . $eachOnuData['login']);
                } else {
                    $userLink = '';
                }

                $cells .= wf_TableCell($userLink);
                $actControls = wf_Link(self::URL_ME . '&editonu=' . $eachOnuId, web_edit_icon(__('Edit') . ' ' . __('ONU')));
                $cells .= wf_TableCell($actControls);
                $rows .= wf_TableRow($cells, 'row5');
                $count++;
            }
            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
            $result .= wf_tag('b') . __('Total') . ': ' . $count . wf_tag('b', true);
        }
        return ($result);
    }

    /**
     * Tries to return the current "realtime" ONU signal value
     *
     * @param $oltID
     * @param $onumac
     *
     * @return float|int|string
     */
    public function getONURealtimeSignal($oltID, $onumac, $getTxSgnal = false) {
        $signal = '';
        $oltID = vf($oltID, 3);

        if (isset($this->allOltDevices[$oltID]) and isset($this->allOltSnmp[$oltID])) {
            $oltCommunity = $this->allOltSnmp[$oltID]['community'];
            $oltModelId = $this->allOltSnmp[$oltID]['modelid'];
            $oltIp = $this->allOltSnmp[$oltID]['ip'];
            $cacheMACDevID = array();

            if (
                isset($this->snmpTemplates[$oltModelId])
                and isset($this->snmpTemplates[$oltModelId]['signal'])
                and isset($this->snmpTemplates[$oltModelId]['misc'])
                and file_exists(self::MACDEVIDCACHE_PATH . $oltID . '_' . self::MACDEVIDCACHE_EXT)
            ) {

                $cacheMACDevID = file_get_contents(self::MACDEVIDCACHE_PATH . $oltID . '_' . self::MACDEVIDCACHE_EXT);
                $cacheMACDevID = unserialize($cacheMACDevID);

                if (!empty($cacheMACDevID[$onumac])) {
                    $snmpSignalOIDs = $this->snmpTemplates[$oltModelId]['signal'];
                    $snmpMiscOIDs = $this->snmpTemplates[$oltModelId]['misc'];
                    $onuDevID = $cacheMACDevID[$onumac];

                    if ($snmpSignalOIDs['SIGNALMODE'] == 'VSOL') {
                        $sigOIDPart = ($getTxSgnal) ? '.6.' : '.7.';
                        $sigIndexOID = $snmpSignalOIDs['SIGINDEX'] . $sigOIDPart . $onuDevID;
                        $sigIndexVal = $snmpSignalOIDs['SIGVALUE'];
                    } else {
                        if (
                            $getTxSgnal
                            and isset($snmpMiscOIDs['ONUTXSIGNAL'])
                            and isset($snmpMiscOIDs['ONUTXSIGNALVAL'])
                        ) {

                            $sigIndexOID = $snmpMiscOIDs['ONUTXSIGNAL'] . '.' . $onuDevID;
                            $sigIndexVal = $snmpMiscOIDs['ONUTXSIGNALVAL'];
                        } else {
                            $sigIndexOID = $snmpSignalOIDs['SIGINDEX'] . '.' . $onuDevID;
                            $sigIndexVal = $snmpSignalOIDs['SIGVALUE'];
                        }
                    }

                    $sigIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $sigIndexOID, self::SNMPCACHE);
                    $sigIndex = str_replace($sigIndexOID . '.', '', $sigIndex);
                    $sigIndex = str_replace($sigIndexVal, '', $sigIndex);

                    if (!empty($sigIndex)) {
                        if ($snmpSignalOIDs['SIGNALMODE'] == 'BDCOM') {
                            $line = explode('=', $sigIndex);
                            //signal is present
                            if (isset($line[1])) {
                                $signal = trim($line[1]); // signal level

                                if (empty($signal) or !is_numeric($signal) or $signal == $snmpSignalOIDs['DOWNVALUE']) {
                                    $signal = 'Offline';
                                } else {
                                    if ($snmpSignalOIDs['OFFSETMODE'] == 'div') {
                                        if ($snmpSignalOIDs['OFFSET']) {
                                            $signal = $signal / $snmpSignalOIDs['OFFSET'];
                                        }
                                    }
                                }
                            }
                        }

                        if ($snmpSignalOIDs['SIGNALMODE'] == 'STELSFD') {
                            $line = explode('=', $sigIndex);
                            //signal is present
                            if (isset($line[1])) {
                                $signal = trim($line[1]); // signal level

                                if (empty($signal) or !is_numeric($signal) or $signal == $snmpSignalOIDs['DOWNVALUE']) {
                                    $signal = 'Offline';
                                } else {
                                    if ($snmpSignalOIDs['OFFSETMODE'] == 'logm') {
                                        if ($snmpSignalOIDs['OFFSET']) {
                                            $signal = round(10 * log10($signal) - $snmpSignalOIDs['OFFSET'], 2);
                                        }
                                    }
                                }
                            }
                        }

                        if ($snmpSignalOIDs['SIGNALMODE'] == 'VSOL') {
                            $signal = trim(substr(stristr(stristr(stristr($sigIndex, '('), ')', true), 'dBm', true), 1));
                        }
                    }
                }
            }
        }

        return ($signal);
    }

    /**
     * Tries to return some of the extended "realtime" ONU info, like Tx signal, last reg/dereg time, alive time
     *
     * @param $oltID
     * @param $onumac
     *
     * @return array
     */
    public function getONUExtenInfo($oltID, $onumac) {
        $result = array();
        $oltID = vf($oltID, 3);

        if (isset($this->allOltDevices[$oltID]) and isset($this->allOltSnmp[$oltID])) {
            $oltCommunity = $this->allOltSnmp[$oltID]['community'];
            $oltModelId = $this->allOltSnmp[$oltID]['modelid'];
            $oltIp = $this->allOltSnmp[$oltID]['ip'];
            $cacheMACDevID = array();

            if (
                isset($this->snmpTemplates[$oltModelId])
                and isset($this->snmpTemplates[$oltModelId]['signal'])
                and isset($this->snmpTemplates[$oltModelId]['misc'])
                and file_exists(self::MACDEVIDCACHE_PATH . $oltID . '_' . self::MACDEVIDCACHE_EXT)
            ) {

                if ($this->snmpTemplates[$oltModelId]['signal']['SIGNALMODE'] == 'STELSFD') {
                    return ($result);
                }

                $cacheMACDevID = file_get_contents(self::MACDEVIDCACHE_PATH . $oltID . '_' . self::MACDEVIDCACHE_EXT);
                $cacheMACDevID = unserialize($cacheMACDevID);

                if (!empty($cacheMACDevID[$onumac])) {
                    $snmpDevice = $this->snmpTemplates[$oltModelId]['define']['DEVICE'];
                    $snmpSignalOIDs = $this->snmpTemplates[$oltModelId]['signal'];
                    $snmpMiscOIDs = $this->snmpTemplates[$oltModelId]['misc'];
                    $onuDevID = $cacheMACDevID[$onumac];
                    $onuIdxDevID = '';
                    $lastRegTime = '';
                    $lastDeregTime = '';
                    $lastAliveTime = '';

                    if (ispos($snmpDevice, 'OLT P36')) {
                        $onuIdx = file_get_contents(self::ONUCACHE_PATH . $oltID . '_' . self::ONUCACHE_EXT);
                        if (!empty($onuIdx)) {
                            $onuIdx = array_flip(unserialize($onuIdx));

                            if (!empty($onuIdx[$onumac])) {
                                $onuIdxDevID = $onuIdx[$onumac];
                            }
                        }
                    }

                    if (!empty($snmpMiscOIDs['LASTREGTIME'])) {
                        if (ispos($snmpDevice, 'OLT P36')) {
                            $lastRegTimeOID = $snmpMiscOIDs['LASTREGTIME'] . '.' . $onuIdxDevID;
                        } else {
                            $lastRegTimeOID = $snmpMiscOIDs['LASTREGTIME'] . '.' . $onuDevID;
                        }
                        $lastRegTimeVal = $snmpMiscOIDs['LASTREGTIMEVAL'];
                        $lastRegTime = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $lastRegTimeOID, self::SNMPCACHE);
                        $lastRegTime = trimSNMPOutput($lastRegTime, $lastRegTimeOID);
                        $lastRegTime = (empty($lastRegTime[1]) ? '' : $lastRegTime[1]);
                    }

                    if (!empty($snmpMiscOIDs['LASTDEREGTIME'])) {
                        if (ispos($snmpDevice, 'OLT P36')) {
                            $lastDeregTimeOID = $snmpMiscOIDs['LASTDEREGTIME'] . '.' . $onuIdxDevID;
                        } else {
                            $lastDeregTimeOID = $snmpMiscOIDs['LASTDEREGTIME'] . '.' . $onuDevID;
                        }
                        $lastDeregTimeVal = $snmpMiscOIDs['LASTDEREGTIMEVAL'];
                        $lastDeregTime = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $lastDeregTimeOID, self::SNMPCACHE);
                        $lastDeregTime = trimSNMPOutput($lastDeregTime, $lastDeregTimeOID);
                        $lastDeregTime = empty($lastDeregTime[1]) ? '' : $lastDeregTime[1];
                    }

                    if (!empty($snmpMiscOIDs['LASTALIVETIME'])) {
                        $lastAliveTimeOID = $snmpMiscOIDs['LASTALIVETIME'] . '.' . $onuDevID;
                        $lastAliveTimeVal = $snmpMiscOIDs['LASTALIVETIMEVAL'];
                        $lastAliveTime = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $lastAliveTimeOID, self::SNMPCACHE);
                        $lastAliveTime = trimSNMPOutput($lastAliveTime, $lastAliveTimeOID);
                        $lastAliveTime = empty($lastAliveTime[1]) ? '' : $lastAliveTime[1];
                    }

                    if (!empty($lastRegTime) or !empty($lastDeregTime) or !empty($lastAliveTime)) {
                        if ($snmpSignalOIDs['SIGNALMODE'] == 'BDCOM' or ispos($snmpDevice, 'FD12XXS') or ispos($snmpDevice, 'FD16XXS')) {
                            $lastAliveTime = (empty($lastAliveTime) or !is_numeric($lastAliveTime)) ? 0 : $lastAliveTime;
                            $lastAliveTime = zb_formatTime($lastAliveTime);

                            $lastRegTime = $this->convertBDCOMTime($lastRegTime);
                            $lastDeregTime = $this->convertBDCOMTime($lastDeregTime);
                        }

                        $result['lastreg'] = trim(trim($lastRegTime), '"');
                        $result['lastdereg'] = trim(trim($lastDeregTime), '"');
                        $result['lastalive'] = trim(trim($lastAliveTime), '"');
                    }
                }
            }
        }

        return ($result);
    }

    /**
     * Tries to return the current "realtime" OLT uptime value
     *
     * @param $oltID
     * @param bool $fromCache
     *
     * @return bool|string
     */
    public function getOLTUptime($oltID, $fromCache = true) {
        $oltUptime = '';

        if ($fromCache and file_exists(self::UPTIME_PATH . $oltID . '_' . self::UPTIME_EXT)) {
            $oltUptime = file_get_contents(self::UPTIME_PATH . $oltID . '_' . self::UPTIME_EXT);
        } else {
            $oltID = vf($oltID, 3);

            if (isset($this->allOltDevices[$oltID]) and isset($this->allOltSnmp[$oltID])) {
                $oltCommunity = $this->allOltSnmp[$oltID]['community'];
                $oltModelId = $this->allOltSnmp[$oltID]['modelid'];
                $oltIp = $this->allOltSnmp[$oltID]['ip'];

                if (isset($this->snmpTemplates[$oltModelId]['system'])) {
                    //OLT uptime
                    if (isset($this->snmpTemplates[$oltModelId]['system']['UPTIME'])) {
                        $uptimeIndexOid = $this->snmpTemplates[$oltModelId]['system']['UPTIME'];
                        $oltUptime = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $uptimeIndexOid, self::SNMPCACHE);

                        if (!empty($oltUptime)) {
                            $oltUptime = explode(')', $oltUptime);
                            $oltUptime = $oltUptime[1];
                            $oltUptime = trim($oltUptime);
                        }
                    }
                }
            }
        }

        return ($oltUptime);
    }

    /**
     * Tries to make BDCOM Reg/Dereg dates human readable
     *
     * @param $hexOIDVal
     *
     * @return string
     */
    public function convertBDCOMTime($hexOIDVal) {
        $result = '';

        if (!empty($hexOIDVal)) {
            $hexOIDVal = substr(str_replace(' ', '', $hexOIDVal), 0, 14);

            $bdcomYear = hexdec(substr($hexOIDVal, 0, 4));

            $bdcomMonth = hexdec(substr($hexOIDVal, 4, 2));
            $bdcomMonth = (strlen($bdcomMonth) < 2) ? '0' . $bdcomMonth : $bdcomMonth;

            $bdcomDay = hexdec(substr($hexOIDVal, 6, 2));
            $bdcomDay = (strlen($bdcomDay) < 2) ? '0' . $bdcomDay : $bdcomDay;

            $bdcomHour = hexdec(substr($hexOIDVal, 8, 2));
            $bdcomHour = (strlen($bdcomHour) < 2) ? '0' . $bdcomHour : $bdcomHour;

            $bdcomMin = hexdec(substr($hexOIDVal, 10, 2));
            $bdcomMin = (strlen($bdcomMin) < 2) ? '0' . $bdcomMin : $bdcomMin;

            $bdcomSec = hexdec(substr($hexOIDVal, 12, 2));
            $bdcomSec = (strlen($bdcomSec) < 2) ? '0' . $bdcomSec : $bdcomSec;

            $result = $bdcomYear . '.' . $bdcomMonth . '.' . $bdcomDay . ' ' . $bdcomHour . ':' . $bdcomMin . ':' . $bdcomSec;
        }

        return ($result);
    }

    /**
     * Performs reply on ONU assigment check
     */
    public function checkONUAssignmentReply() {
        $tString = '';
        $tStatus = 0;
        $tLogin = '';
        $oltData = '';
        $onuMAC = ubRouting::get('onumac');

        $ONUAssignment = $this->checkONUAssignment($this->getOnuIDbyIdent($onuMAC), true, true);

        $tStatus = $ONUAssignment['status'];
        $tLogin = $ONUAssignment['login'];
        $oltData = $ONUAssignment['oltdata'];

        switch ($tStatus) {
            case 0:
                $tString = __('ONU is not assigned');
                break;

            case 1:
                $tString = __('ONU is already assigned, but such login is not exists anymore') . '. ' . __('Login') . ': ' . $tLogin . '. OLT: ' . $oltData;
                break;

            case 2:
                $tString = __('ONU is already assigned') . '. ' . __('Login') . ': ' . $tLogin . '. OLT: ' . $oltData;
                break;
        }
        die($tString);
    }

    /**
     * Returns filtered array of unknown ONUs as mac/serial=>oltId
     * 
     * @return array
     */
    protected function getOnuUnknownAll() {
        $result = array();
        $this->fillONUIndexCache();
        if (!empty($this->onuIndexCache)) {
            foreach ($this->onuIndexCache as $onuMac => $oltId) {
                //ONU not registered yet?
                if ($this->checkOnuUnique($onuMac)) {
                    if (!isset($this->hideOnuMac[$onuMac])) {
                        if (!ispos($onuMac, 'no:such')) {
                            $result[$onuMac] = $oltId;
                        }
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Renders batch unknown ONU registration list
     * 
     * @return string
     */
    public function renderBatchOnuRegList() {
        $result = '';
        $allUnknownOnus = $this->getOnuUnknownAll();
        if (!empty($allUnknownOnus)) {
            $onuLabel = __('Oh you are a lazy ass') . '... ' . sizeof($allUnknownOnus) . ' ' . __('Unknown ONU') . '!';
            $result .= $this->messages->getStyledMessage($onuLabel, 'info');
            foreach ($allUnknownOnus as $eachOnuIdent => $eachOnuOltId) {
                if (check_mac_format($eachOnuIdent)) {
                    //valid MAC?
                    $identLabel = __('ONU') . ' ' . __('MAC');
                } else {
                    //looks like serial
                    $identLabel = __('ONU') . ' ' . __('Serial');
                }
                $onuLabel = $identLabel . ' ' . $eachOnuIdent . ' ' . __('on') . ' ' . __('OLT') . ' ' . @$this->allOltDevices[$eachOnuOltId];
                $result .= $this->messages->getStyledMessage($onuLabel, 'warning');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'success');
        }
        return ($result);
    }

    /**
     * Renders batch unknown ONU registration form
     * 
     * @return string
     */
    public function renderBatchOnuRegForm() {
        $result = '';
        $models = array();
        $allUnknownOnus = $this->getOnuUnknownAll();
        if (!empty($allUnknownOnus)) {
            if (!empty($this->allModelsData)) {
                foreach ($this->allModelsData as $io => $each) {
                    if (@$this->altCfg['ONUMODELS_FILTER']) {
                        if (ispos($each['modelname'], 'ONU')) {
                            $models[$each['id']] = $each['modelname'];
                        }
                    } else {
                        $models[$each['id']] = $each['modelname'];
                    }
                }
            }

            if (!empty($models)) {
                $inputs = wf_HiddenInput('runmassonureg', 'true');
                $inputs .= wf_Selector('massonuregonumodelid', $models, __('ONU model') . $this->sup, '', true);
                $inputs .= wf_delimiter(0);
                $confirmLabel = __('I also understand well that no one will correct my mistakes for me and only I bear full financial responsibility for my mistakes');
                $inputs .= wf_CheckInput('massonuregconfirmation', $confirmLabel, true, false);
                $inputs .= wf_delimiter(0);
                $inputs .= wf_Submit(__('Register all unknown ONUs'));
                $result .= wf_Form('', 'POST', $inputs, 'glamour');
            } else {
                $result .= $this->messages->getStyledMessage(__('Any available ONU models exist'), 'error');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'success');
        }

        return ($result);
    }

    /**
     * Performs batch unknown ONUs registration
     * 
     * @param string $customBackLink
     * 
     * @return string
     */
    public function runBatchOnuRegister($customBackLink = '') {
        set_time_limit(0);
        $result = '';
        $onuList = '';
        if ($customBackLink) {
            $result .= wf_BackLink($customBackLink);
        } else {
            $result .= wf_BackLink('?module=ponizer&onumassreg=true');
        }
        $result .= wf_delimiter(0);
        $errorCount = 0;
        $succCount = 0;
        if (ubRouting::checkPost('massonuregconfirmation')) {
            if (ubRouting::checkPost('massonuregonumodelid')) {
                $newOnusModelId = ubRouting::post('massonuregonumodelid', 'int');
                if (!empty($newOnusModelId) and isset($this->allModelsData)) {
                    $allUnknownOnus = $this->getOnuUnknownAll();
                    if (!empty($allUnknownOnus)) {
                        $onuLabel = __('Oh you are a lazy ass') . '... ' . sizeof($allUnknownOnus) . ' ' . __('Unknown ONU') . '!';
                        $onuList .= $this->messages->getStyledMessage($onuLabel, 'warning');
                        foreach ($allUnknownOnus as $eachOnuIdent => $eachOnuOltId) {
                            $oltLabel = ' [' . $eachOnuOltId . '] ' . $this->allOltDevices[$eachOnuOltId];
                            $onuLabel = __('Registering') . ' ' . __('MAC') . '/' . __('Serial') . ' ' . $eachOnuIdent . ' ' . __('on') . ' ' . __('OLT') . ' ' . $oltLabel;
                            $onuList .= $this->messages->getStyledMessage($onuLabel, 'info');
                            if (isset($this->allOltDevices[$eachOnuOltId])) {
                                if ($this->checkOnuUnique($eachOnuIdent)) {

                                    if (check_mac_format($eachOnuIdent)) {
                                        //looks like normal MAC
                                        $newOnuMac = $eachOnuIdent;
                                        $newOnuSerial = '';
                                    } else {
                                        //seems its GPON device serial
                                        $newOnuMac = zb_MacGetRandom();
                                        $newOnuSerial = $eachOnuIdent;
                                    }
                                    $newOnuId = $this->onuCreate($newOnusModelId, $eachOnuOltId, '', $newOnuMac, $newOnuSerial, '');
                                    if ($newOnuId) {
                                        $oltLabel = ' [' . $eachOnuOltId . '] ' . $this->allOltDevices[$eachOnuOltId] . '. ';
                                        $onuLabel = __('Registered') . ' ' . __('MAC') . ' ' . __('or') . ' ' . __('Serial') . ' ' . $eachOnuIdent . ' ' . __('ONU') . ' [' . $newOnuId . '] ' . __('on') . ' ' . __('OLT') . ' ' . $oltLabel;
                                        $onuLabel .= __('Success') . '!';
                                        $onuList .= $this->messages->getStyledMessage($onuLabel, 'success');
                                        $succCount++;
                                    } else {
                                        $errorCount++;
                                        $onuList .= $this->messages->getStyledMessage(__('Registering') . ' ' . __('Failed') . ' "' . $eachOnuIdent . '" ', 'error');
                                    }
                                } else {
                                    $errorCount++;
                                    $onuList .= $this->messages->getStyledMessage(__('MAC duplicate') . ' ' . $eachOnuIdent . ' ', 'error');
                                }
                            } else {
                                $errorCount++;
                                $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('OLT') . ' [' . $eachOnuOltId . '] ' . __('Not exists'), 'error');
                            }
                        }
                    } else {
                        $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'success');
                    }
                } else {
                    $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('ONU model') . ' ' . __('Not exists'), 'error');
                }
            } else {
                $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('No') . ' ' . __('ONU model'), 'error');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('You are not mentally prepared for this'), 'error');
        }

        //some summary here
        if ($succCount > 0) {
            $result .= $this->messages->getStyledMessage(__('Registered') . ': ' . $succCount, 'success');
        }
        if ($errorCount > 0) {
            $result .= $this->messages->getStyledMessage(__('Error') . ': ' . $errorCount, 'error');
        }
        $result .= $onuList;
        return ($result);
    }
}
