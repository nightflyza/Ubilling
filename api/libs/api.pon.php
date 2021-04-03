<?php

/**
 * PON devices management/monitoring support
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
     * OLT devices snmp data as id=>snmp data array
     *
     * @var array
     */
    protected $allOltSnmp = array();

    /**
     * Available OLT models as id=>modelname + snmptemplate + ports
     *
     * @var array
     */
    protected $allOltModels = array();

    /**
     * Contains available SNMP templates for OLT modelids
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
     * System alter.ini config stored as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * SNMPHelper object instance
     *
     * @var array
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
     * @var null
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
     * PON interfaces object placeholder
     *
     * @var object
     */
    public $ponInterfaces = '';

    /**
     * Some predefined paths, etc
     */
    const SIGCACHE_PATH = 'exports/';
    const SIGCACHE_EXT = 'OLTSIGNALS';
    const DISTCACHE_PATH = 'exports/';
    const DISTCACHE_EXT = 'OLTDISTANCE';
    const ONUCACHE_PATH = 'exports/';
    const ONUCACHE_EXT = 'ONUINDEX';
    const INTCACHE_PATH = 'exports/';
    const INTCACHE_EXT = 'ONUINTERFACE';
    const INTDESCRCACHE_EXT = 'OLTINTERFACEDESCR';
    const FDBCACHE_PATH = 'exports/';
    const FDBCACHE_EXT = 'OLTFDB';
    const DEREGCACHE_PATH = 'exports/';
    const DEREGCACHE_EXT = 'ONUDEREGS';
    const UPTIME_PATH = 'exports/';
    const UPTIME_EXT = 'OLTUPTIME';
    const TEMPERATURE_PATH = 'exports/';
    const TEMPERATURE_EXT = 'OLTTEMPERATURE';
    const MACDEVIDCACHE_PATH = 'exports/';
    const MACDEVIDCACHE_EXT = 'ONUMACDEVINDEX';
    const SNMPCACHE = false;
    const SNMPPORT = 161;
    const ONUSIG_PATH = 'content/documents/onusig/';
    const POLL_STATS = 'exports/PONY_';

    /**
     * Some routes here
     */
    const URL_ME = '?module=ponizer';
    const URL_USERPROFILE = '?module=userprofile&username=';

    /**
     * Views/stats coloring
     */
    const COLOR_OK = '#005502';
    const COLOR_AVG = '#FF5500';
    const COLOR_BAD = '#AB0000';
    const COLOR_NOSIG = '#000000';
    const NO_SIGNAL = 'Offline';

    /**
     * Creates new PONizer object instance
     *
     * @return void
     */
    public function __construct() {
        global $ubillingConfig;
        $this->ubConfig = $ubillingConfig;

        $this->loadAlter();
        $this->loadOltDevices();
        $this->loadOltModels();
        $this->loadSnmpTemplates();
        $this->initSNMP();
        $this->loadOnu();
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
     * Loads all available devices set as OLT
     *
     * @return void
     */
    protected function loadOltDevices() {
        $query = "SELECT `id`,`ip`,`location`,`snmp`,`modelid`, `desc` from `switches` WHERE `desc` LIKE '%OLT%';";
        $raw = simple_queryall($query);
        if (!empty($raw)) {
            foreach ($raw as $io => $each) {
                $this->allOltDevices[$each['id']] = $each['ip'] . ' - ' . $each['location'];
                $this->allOltNames[$each['id']] = $each['location'];
                if (!empty($each['snmp'])) {
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
     * Performs snmp templates preprocessing for OLT devices
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
                            $templateFile = 'config/snmptemplates/' . $this->allOltModels[$oltModelid]['snmptemplate'];
                            $privateTemplateFile = DATA_PATH . 'documents/mysnmptemplates/' . $this->allOltModels[$oltModelid]['snmptemplate'];
                            if (file_exists($templateFile)) {
                                $this->snmpTemplates[$oltModelid] = rcms_parse_ini_file($templateFile, true);
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
     * Try get new ONU Array by assigned users login
     *
     * @param string $OltId
     * @return array
     */
    protected function getOnuArrayByOltID($OltId = '') {
        $result = array();
        if (!empty($this->allOnu) and ! empty($OltId)) {
            foreach ($this->allOnu as $io => $each) {
                if ($each['oltid'] == $OltId) {
                    $result[$io] = $each;
                }
            }
        }
        return ($result);
    }

    /**
     * Parses & stores in cache OLT ONU distances
     *
     * @param int $oltid
     * @param array $distIndex
     * @param array $onuIndex
     *
     * @return void
     */
    protected function distanceParseBd($oltid, $distIndex, $onuIndex) {
        $oltid = vf($oltid, 3);
        $distTmp = array();
        $onuTmp = array();
        $result = array();
        $curDate = curdatetime();

//distance index preprocessing
        if ((!empty($distIndex)) and ( !empty($onuIndex))) {
            foreach ($distIndex as $io => $eachdist) {
                $line = explode('=', $eachdist);
//distance is present
                if (isset($line[1])) {
                    $distanceRaw = trim($line[1]); // distance
                    $devIndex = trim($line[0]); // device index
                    $distTmp[$devIndex] = $distanceRaw;
                }
            }

//mac index preprocessing
            foreach ($onuIndex as $io => $eachmac) {
                $line = explode('=', $eachmac);
//mac is present
                if (isset($line[1])) {
                    $macRaw = trim($line[1]); //mac address
                    $devIndex = trim($line[0]); //device index
                    $macRaw = str_replace(' ', ':', $macRaw);
                    $macRaw = strtolower($macRaw);
                    $onuTmp[$devIndex] = $macRaw;
                }
            }

//storing results
            if (!empty($onuTmp)) {
                foreach ($onuTmp as $devId => $eachMac) {
                    if (isset($distTmp[$devId])) {
                        $distance = $distTmp[$devId];
                        $result[$eachMac] = $distance;
                    }
                }
                $result = serialize($result);
                file_put_contents(self::DISTCACHE_PATH . $oltid . '_' . self::DISTCACHE_EXT, $result);
                $onuTmp = serialize($onuTmp);
                file_put_contents(self::ONUCACHE_PATH . $oltid . '_' . self::ONUCACHE_EXT, $onuTmp);
            }
        }
    }

    /**
     * Parses & stores in cache OLT ONU distances
     *
     * @param int $oltid
     * @param array $distIndex
     * @param array $onuIndex
     *
     * @return void
     */
    protected function distanceParseGPBd($oltid, $distIndex, $onuIndex) {
        $oltid = vf($oltid, 3);
        $distTmp = array();
        $onuTmp = array();
        $result = array();
        $curDate = curdatetime();

//distance index preprocessing
        if ((!empty($distIndex)) and ( !empty($onuIndex))) {
            foreach ($distIndex as $io => $eachdist) {
                $line = explode('=', $eachdist);
//distance is present
                if (isset($line[1])) {
                    $distanceRaw = trim($line[1]); // distance
                    $devIndex = trim($line[0]); // device index
                    $distTmp[$devIndex] = $distanceRaw;
                }
            }

//mac index preprocessing
            foreach ($onuIndex as $io => $eachmac) {
                $line = explode('=', $eachmac);
//mac is present
                if (isset($line[1])) {
                    $macRaw = trim($line[1]); //mac address
                    $devIndex = trim($line[0]); //device index
                    $macRaw = str_replace(' ', ':', $macRaw);
                    $macRaw = strtolower($macRaw);
                    $onuTmp[$devIndex] = $macRaw;
                }
            }

//storing results
            if (!empty($onuTmp)) {
                foreach ($onuTmp as $devId => $eachMac) {
                    if (isset($distTmp[$devId])) {
                        $distance = $distTmp[$devId];
                        if (!empty($distance)) {
                            $distance_m = substr($distance, 0, -1);
                            $distance_dm = substr($distance, -1);
                            $result[$eachMac] = $distance_m . '.' . $distance_dm;
                        } else {
                            $result[$eachMac] = 0;
                        }
                    }
                }
                $result = serialize($result);
                file_put_contents(self::DISTCACHE_PATH . $oltid . '_' . self::DISTCACHE_EXT, $result);
                $onuTmp = serialize($onuTmp);
                file_put_contents(self::ONUCACHE_PATH . $oltid . '_' . self::ONUCACHE_EXT, $onuTmp);
            }
        }
    }

    /**
     * Parses & stores in cache OLT ONU dereg reaesons
     *
     * @param int $oltid
     * @param array $distIndex
     * @param array $onuIndex
     *
     * @return void
     */
    protected function lastDeregParseBd($oltid, $deregIndex, $onuIndex) {
        $oltid = vf($oltid, 3);
        $deregTmp = array();
        $onuTmp = array();
        $result = array();
        $curDate = curdatetime();

//dereg index preprocessing
        if ((!empty($deregIndex)) and ( !empty($onuIndex))) {
            foreach ($deregIndex as $io => $eachdereg) {
                $line = explode('=', $eachdereg);

//dereg is present
                if (isset($line[1])) {
                    $deregRaw = trim($line[1]); // dereg
                    $devIndex = trim($line[0]); // device index

                    switch ($deregRaw) {
                        case 2:
                            $TxtColor = '"#00B20E"';
                            $tmpONULastDeregReasonStr = 'Normal';
                            break;

                        case 3:
                            $TxtColor = '"#F80000"';
                            $tmpONULastDeregReasonStr = 'MPCP down';
                            break;

                        case 4:
                            $TxtColor = '"#F80000"';
                            $tmpONULastDeregReasonStr = 'OAM down';
                            break;

                        case 5:
                            $TxtColor = '"#6500FF"';
                            $tmpONULastDeregReasonStr = 'Firmware download';
                            break;

                        case 6:
                            $TxtColor = '"#F80000"';
                            $tmpONULastDeregReasonStr = 'Illegal MAC';
                            break;

                        case 7:
                            $TxtColor = '"#FF4400"';
                            $tmpONULastDeregReasonStr = 'LLID admin down';
                            break;

                        case 8:
                            $TxtColor = '"#F80000"';
                            $tmpONULastDeregReasonStr = 'Wire down';
                            break;

                        case 9:
                            $TxtColor = '"#6500FF"';
                            $tmpONULastDeregReasonStr = 'Power off';
                            break;

                        default:
                            $TxtColor = '"#000000"';
                            $tmpONULastDeregReasonStr = 'Unknown';
                            break;
                    }

                    $tmpONULastDeregReasonStr = wf_tag('font', false, '', 'color=' . $TxtColor . '') .
                            $tmpONULastDeregReasonStr .
                            wf_tag('font', true);

                    $deregTmp[$devIndex] = $tmpONULastDeregReasonStr;
                }
            }

//mac index preprocessing
            foreach ($onuIndex as $io => $eachmac) {
                $line = explode('=', $eachmac);
//mac is present
                if (isset($line[1])) {
                    $macRaw = trim($line[1]); //mac address
                    $devIndex = trim($line[0]); //device index
                    $macRaw = str_replace(' ', ':', $macRaw);
                    $macRaw = strtolower($macRaw);
                    $onuTmp[$devIndex] = $macRaw;
                }
            }

//storing results
            if (!empty($onuTmp)) {
                foreach ($onuTmp as $devId => $eachMac) {
                    if (isset($deregTmp[$devId])) {
                        $lastDereg = $deregTmp[$devId];
                        $result[$eachMac] = $lastDereg;
                    }
                }

                $result = serialize($result);
                file_put_contents(self::DEREGCACHE_PATH . $oltid . '_' . self::DEREGCACHE_EXT, $result);
            }
        }
    }

    /**
     * Parses & stores in cache OLT ONU interfaces
     *
     * @param int $oltid
     * @param array $intIndex
     * @param array $macIndex
     *
     * @return void
     */
    protected function interfaceParseStels12($oltid, $intIndex, $macIndex) {
        $oltid = vf($oltid, 3);
        $intTmp = array();
        $macTmp = array();
        $result = array();

//distance index preprocessing
        if ((!empty($intIndex)) and ( !empty($macIndex))) {
            foreach ($intIndex as $io => $eachint) {
                if (ispos($eachint, 'pon')) {
                    $line = explode('=', $eachint);

                    if (isset($line[1])) {
                        $interfaceRaw = trim($line[1]); // interface name
                        $devIndex = trim($line[0]); // interface index
                        $intTmp[$devIndex] = $interfaceRaw;
                    }
                }
            }

//mac index preprocessing
            foreach ($macIndex as $io => $eachmac) {
                $line = explode('=', $eachmac);
//mac is present
                if (isset($line[1])) {
                    $macRaw = trim($line[1]); //mac address
                    $devIndex = trim($line[0]); //device index
                    $macRaw = str_replace(' ', ':', $macRaw);
                    $macRaw = strtolower($macRaw);
                    $macTmp[$devIndex] = $macRaw;
                }
            }

//storing results
            if (!empty($macTmp)) {
                foreach ($macTmp as $devId => $eachMac) {
                    $currentInterface = '';
                    $onuNum = '';
                    if (!empty($intTmp)) {
                        foreach ($intTmp as $intefaceOffset => $interfaceName) {

                            // dirty hack for firmware > 1.4.0 - some shitty math used
                            $recalcIfaceOffset = $intefaceOffset;
                            if ($recalcIfaceOffset < 100) {
                                $recalcIfaceOffset = (($recalcIfaceOffset - 10) * 256) + 16779776;
                            }

                            if ($devId > $recalcIfaceOffset) {
                                $currentInterface = $intefaceOffset;
                                $onuNum = $devId - $recalcIfaceOffset;
                            }
                        }

                        $result[$eachMac] = (isset($intTmp[$currentInterface])) ? $intTmp[$currentInterface] . ':' . $onuNum : __('On ho');
                    }
                }

                $result = serialize($result);
                file_put_contents(self::INTCACHE_PATH . $oltid . '_' . self::INTCACHE_EXT, $result);
            }
        }
    }

    /**
     * Parses BDCom uptime data and saves it into uptime cache
     *
     * @param int $oltid
     * @param string $uptimeRaw
     *
     * @return void
     */
    protected function uptimeParseBd($oltid, $uptimeRaw) {
        $oltid = ubRouting::filters($oltid, 'int');
        if (!empty($oltid) and ! empty($uptimeRaw)) {
            $uptimeRaw = explode(')', $uptimeRaw);
            $uptimeRaw = $uptimeRaw[1];
            $uptimeRaw = trim($uptimeRaw);
            file_put_contents(self::UPTIME_PATH . $oltid . '_' . self::UPTIME_EXT, $uptimeRaw);
        }
    }

    /**
     * Parses BDCom temperature data and saves it into uptime cache
     *
     * @param int $oltid
     * @param string $uptimeRaw
     *
     * @return void
     */
    protected function temperatureParseBd($oltid, $tempRaw) {
        $oltid = ubRouting::filters($oltid, 'int');
        if (!empty($oltid) and ! empty($tempRaw)) {
            $tempRaw = explode(':', $tempRaw);
            $tempRaw = $tempRaw[1];
            $tempRaw = trim($tempRaw);
            file_put_contents(self::TEMPERATURE_PATH . $oltid . '_' . self::TEMPERATURE_EXT, $tempRaw);
        }
    }

    /**
     * Parses & stores in cache OLT ONU interfaces
     *
     * @param int $oltid
     * @param array $intIndex
     * @param array $macIndex
     * @param array $ifaceCustDescrRaw
     *
     * @return void
     */
    protected function interfaceParseBd($oltid, $intIndex, $macIndex, $ifaceCustDescrRaw = array()) {
        $oltid = vf($oltid, 3);
        $intTmp = array();
        $macTmp = array();
        $result = array();
        $processIfaceCustDescr = !empty($ifaceCustDescrRaw);
        $ifaceCustDescrIdx = array();
        $ifaceCustDescrArr = array();

// olt iface descr extraction
        if ($processIfaceCustDescr) {
            foreach ($ifaceCustDescrRaw as $io => $each) {
                if (empty($each)) {
                    continue;
                }

                $ifDescr = explode('=', str_replace(array(" ", "\t", "\n", "\r", "\0", "\x0B"), '', $each));

                if ((empty($ifDescr[0]) && empty($ifDescr[1])) || intval($ifDescr[0]) < 7) {
                    continue;
                }
                if ($ifDescr[0] > 10) {
                    break;
                }

                $ifaceCustDescrIdx[$ifDescr[0] - 6] = $ifDescr[1];
            }
        }

//interface index preprocessing
        if ((!empty($intIndex)) and ( !empty($macIndex))) {
            foreach ($intIndex as $io => $eachint) {
                $line = explode('=', $eachint);
//interface is present
                if (isset($line[1])) {
                    $interfaceRaw = trim($line[1]); // interface
                    $devIndex = trim($line[0]); // device index
                    $intTmp[$devIndex] = $interfaceRaw;
                }
            }

//mac index preprocessing
            foreach ($macIndex as $io => $eachmac) {
                $line = explode('=', $eachmac);
//mac is present
                if (isset($line[1])) {
                    $macRaw = trim($line[1]); //mac address
                    $devIndex = trim($line[0]); //device index
                    $macRaw = str_replace(' ', ':', $macRaw);
                    $macRaw = strtolower($macRaw);
                    $macTmp[$devIndex] = $macRaw;
                }
            }

//storing results
            if (!empty($macTmp)) {
                foreach ($macTmp as $devId => $eachMac) {
                    if (isset($intTmp[$devId])) {
                        $interface = $intTmp[$devId];
                        $result[$eachMac] = $interface;
                        $cleanIface = strstr($interface, ':', true);
                        $tPONIfaceNum = substr($cleanIface, -1, 1);

                        if ($processIfaceCustDescr && !isset($ifaceCustDescrArr[$cleanIface]) && array_key_exists($tPONIfaceNum, $ifaceCustDescrIdx)) {
                            $ifaceCustDescrArr[$cleanIface] = $ifaceCustDescrIdx[$tPONIfaceNum];
                        }
                    }
                }

                $result = serialize($result);
                $ifaceCustDescrArr = serialize($ifaceCustDescrArr);
                file_put_contents(self::INTCACHE_PATH . $oltid . '_' . self::INTCACHE_EXT, $result);
                file_put_contents(self::INTCACHE_PATH . $oltid . '_' . self::INTDESCRCACHE_EXT, $ifaceCustDescrArr);
            }
        }
    }

    /**
     * Parses & stores in cache OLT ONU interfaces
     *
     * @param int $oltid
     * @param array $FDBIndex
     * @param array $macIndex
     * @param array $oltModelId
     *
     * @return void
     */
    protected function FDBParseBd($oltid, $FDBIndex, $macIndex, $oltModelId) {
        $oltid = vf($oltid, 3);
        $FDBTmp = array();
        $macTmp = array();
        $result = array();

//fdb index preprocessing
        if ((!empty($FDBIndex)) and ( !empty($macIndex))) {
            foreach ($FDBIndex as $io => $eachfdb) {
                if (preg_match('/' . $this->snmpTemplates[$oltModelId]['misc']['FDBVALUE'] . '/', $eachfdb)) {
                    $eachfdb = str_replace($this->snmpTemplates[$oltModelId]['misc']['FDBVALUE'], '', $eachfdb);
                    $line = explode('=', $eachfdb);
//fdb is present
                    if (isset($line[1])) {
                        $FDBRaw = trim($line[1]); // FDB
                        $devOID = trim($line[0]); // FDB last OID
                        $devline = explode('.', $devOID);
                        $devIndex = trim($devline[0]); // FDB index
                        $FDBvlan = trim($devline[1]); // Vlan
                        $FDBnum = trim($devline[7]); // Count number of MAC

                        $FDBRaw = str_replace(' ', ':', $FDBRaw);
                        $FDBRaw = strtolower($FDBRaw);

                        $FDBTmp[$devIndex][$FDBnum]['mac'] = $FDBRaw;
                        $FDBTmp[$devIndex][$FDBnum]['vlan'] = $FDBvlan;
                    }
                }
            }

//mac index preprocessing
            foreach ($macIndex as $io => $eachmac) {
                $line = explode('=', $eachmac);
//mac is present
                if (isset($line[1])) {
                    $macRaw = trim($line[1]); //mac address
                    $devIndex = trim($line[0]); //device index
                    $macRaw = str_replace(' ', ':', $macRaw);
                    $macRaw = strtolower($macRaw);
                    $macTmp[$devIndex] = $macRaw;
                }
            }

//storing results
            if (!empty($macTmp)) {
                foreach ($macTmp as $devId => $eachMac) {
                    if (isset($FDBTmp[$devId])) {
                        $fdb = $FDBTmp[$devId];
                        $result[$eachMac] = $fdb;
                    }
                }
                $result = serialize($result);
                file_put_contents(self::FDBCACHE_PATH . $oltid . '_' . self::FDBCACHE_EXT, $result);
            }
        }
    }

    /**
     * Parses & stores in cache OLT FDB
     *
     * @param int $oltid
     * @param array $FDBIndex
     * @param array $macIndex
     * @param array $oltModelId
     *
     * @return void
     */
    protected function FDBParseBdFirmwareF($oltid, $FDBIndex, $macIndex, $oltModelId) {
        $oltid = vf($oltid, 3);
        $TmpArr = array();
        $FDBTmp = array();
        $macTmp = array();
        $result = array();

//fdb index preprocessing
        if ((!empty($FDBIndex)) and ( !empty($macIndex))) {
            foreach ($FDBIndex as $io => $eachfdbRaw) {
                if (preg_match('/' . $this->snmpTemplates[$oltModelId]['misc']['FDBVALUE'] . '|INTEGER:/', $eachfdbRaw)) {
                    $eachfdbRaw = str_replace(array($this->snmpTemplates[$oltModelId]['misc']['FDBVALUE'], 'INTEGER:'), '', $eachfdbRaw);
                    $line = explode('=', $eachfdbRaw);
//fdb is present
                    if (isset($line[1])) {
                        $devOID = trim($line[0]); // FDB last OID
                        $lineRaw = trim($line[1]); // FDB
                        $devline = explode('.', $devOID);
                        $FDBvlan = trim($devline[1]); // Vlan
                        $FDBnum = trim($devline[7]); // Count number of MAC
                        if (preg_match('/^1/', $devOID)) {
                            $FDBRaw = str_replace(' ', ':', $lineRaw);
                            $FDBRaw = strtolower($FDBRaw);
                            $TmpArr[$devOID]['mac'] = $FDBRaw;
                            $TmpArr[$devOID]['vlan'] = $FDBvlan;
                            $TmpArr[$devOID]['FDBnum'] = $FDBnum;
                        } elseif (preg_match('/^2/', $devOID)) {
                            $devIndexOid = substr_replace($devOID, '1', 0, 1);
                            $TmpArr[$devIndexOid]['index'] = $lineRaw;
                        } else {
                            continue;
                        }
                    }
                }
            }
            if (!empty($TmpArr)) {
                // Crete tmp Ubilling PON FDB array
                foreach ($TmpArr as $io => $each) {
                    if (count($each) == 4) {
                        $FDBTmp[$each['index']][$each['FDBnum']]['mac'] = $each['mac'];
                        $FDBTmp[$each['index']][$each['FDBnum']]['vlan'] = $each['vlan'];
                    }
                }
            }
//mac index preprocessing
            foreach ($macIndex as $io => $eachmac) {
                $line = explode('=', $eachmac);
//mac is present
                if (isset($line[1])) {
                    $macRaw = trim($line[1]); //mac address
                    $devIndex = trim($line[0]); //device index
                    $macRaw = str_replace(' ', ':', $macRaw);
                    $macRaw = strtolower($macRaw);
                    $macTmp[$devIndex] = $macRaw;
                }
            }
//storing results
            if (!empty($macTmp)) {
                foreach ($macTmp as $devId => $eachMac) {
                    if (isset($FDBTmp[$devId])) {
                        $fdb = $FDBTmp[$devId];
                        $result[$eachMac] = $fdb;
                    }
                }

                $result = serialize($result);
                file_put_contents(self::FDBCACHE_PATH . $oltid . '_' . self::FDBCACHE_EXT, $result);
            }
        }
    }

    /**
     * Parses & stores in cache OLT ONU interfaces
     *
     * @param int $oltid
     * @param array $FDBIndex
     * @param array $macIndex
     * @param array $FDBDEVIndex
     * @param array $oltModelId
     *
     * @return void
     */
    protected function FDBParseGPBd($oltid, $FDBIndex, $macIndex, $oltModelId) {
        $oltid = vf($oltid, 3);
        $TmpArr = array();
        $FDBTmp = array();
        $macTmp = array();
        $result = array();

//fdb index preprocessing
        if ((!empty($FDBIndex)) and ( !empty($macIndex))) {
            foreach ($FDBIndex as $io => $eachfdbRaw) {
                if (preg_match('/' . $this->snmpTemplates[$oltModelId]['misc']['FDBVALUE'] . '|INTEGER:/', $eachfdbRaw)) {
                    $eachfdbRaw = str_replace(array($this->snmpTemplates[$oltModelId]['misc']['FDBVALUE'], 'INTEGER:'), '', $eachfdbRaw);
                    $line = explode('=', $eachfdbRaw);
//fdb is present
                    if (isset($line[1])) {
                        $devOID = trim($line[0]); // FDB last OID
                        $lineRaw = trim($line[1]); // FDB
                        $devline = explode('.', $devOID);
                        $FDBvlan = trim($devline[1]); // Vlan
                        $FDBnum = trim($devline[7]); // Count number of MAC
                        if (preg_match('/^1/', $devOID)) {
                            $FDBRaw = str_replace(' ', ':', $lineRaw);
                            $FDBRaw = strtolower($FDBRaw);
                            $TmpArr[$devOID]['mac'] = $FDBRaw;
                            $TmpArr[$devOID]['vlan'] = $FDBvlan;
                            $TmpArr[$devOID]['FDBnum'] = $FDBnum;
                        } elseif (preg_match('/^2/', $devOID)) {
                            $devIndexOid = substr_replace($devOID, '1', 0, 1);
                            $TmpArr[$devIndexOid]['index'] = $lineRaw;
                        } else {
                            continue;
                        }
                    }
                }
            }
            if (!empty($TmpArr)) {
                // Crete tmp Ubilling PON FDB array
                foreach ($TmpArr as $io => $each) {
                    if (count($each) == 4) {
                        $FDBTmp[$each['index']][$each['FDBnum']]['mac'] = $each['mac'];
                        $FDBTmp[$each['index']][$each['FDBnum']]['vlan'] = $each['vlan'];
                    }
                }
            }
//mac index preprocessing
            foreach ($macIndex as $io => $eachmac) {
                $line = explode('=', $eachmac);
//mac is present
                if (isset($line[1])) {
                    $macRaw = trim($line[1]); //mac address
                    $devIndex = trim($line[0]); //device index
                    $macRaw = str_replace(' ', ':', $macRaw);
                    $macRaw = strtolower($macRaw);
                    $macTmp[$devIndex] = $macRaw;
                }
            }
//storing results
            if (!empty($macTmp)) {
                foreach ($macTmp as $devId => $eachMac) {
                    if (isset($FDBTmp[$devId])) {
                        $fdb = $FDBTmp[$devId];
                        $result[$eachMac] = $fdb;
                    }
                }

                $result = serialize($result);
                file_put_contents(self::FDBCACHE_PATH . $oltid . '_' . self::FDBCACHE_EXT, $result);
            }
        }
    }

    /**
     * Performs signal preprocessing for sig/mac index arrays and stores it into cache
     *
     * @param int $oltid
     * @param array $sigIndex
     * @param array $macIndex
     * @param array $snmpTemplate
     *
     * @return void
     */
    protected function signalParseBd($oltid, $sigIndex, $macIndex, $snmpTemplate) {
        $oltid = vf($oltid, 3);
        $sigTmp = array();
        $macTmp = array();
        $result = array();
        $curDate = curdatetime();

//signal index preprocessing
        if ((!empty($sigIndex)) and ( !empty($macIndex))) {
            foreach ($sigIndex as $io => $eachsig) {
                $line = explode('=', $eachsig);
//signal is present
                if (isset($line[1])) {
                    $signalRaw = trim($line[1]); // signal level
                    $devIndex = trim($line[0]); // device index
                    if ($signalRaw == $snmpTemplate['DOWNVALUE']) {
                        $signalRaw = 'Offline';
                    } else {
                        if ($snmpTemplate['OFFSETMODE'] == 'div') {
                            if ($snmpTemplate['OFFSET']) {
                                $signalRaw = $signalRaw / $snmpTemplate['OFFSET'];
                            }
                        }
                    }
                    $sigTmp[$devIndex] = $signalRaw;
                }
            }

//mac index preprocessing
            foreach ($macIndex as $io => $eachmac) {
                $line = explode('=', $eachmac);
//mac is present
                if (isset($line[1])) {
                    $macRaw = trim($line[1]); //mac address
                    $devIndex = trim($line[0]); //device index
                    $macRaw = str_replace(' ', ':', $macRaw);
                    $macRaw = strtolower($macRaw);
                    $macTmp[$devIndex] = $macRaw;
                }
            }

//storing results
            if (!empty($macTmp)) {
                foreach ($macTmp as $devId => $eachMac) {
                    if (isset($sigTmp[$devId])) {
                        $signal = $sigTmp[$devId];
                        $result[$eachMac] = $signal;
//signal history filling
                        $historyFile = self::ONUSIG_PATH . md5($eachMac);
                        if ($signal == 'Offline') {
                            $signal = -9000; //over 9000 offline signal level :P
                        }
                        file_put_contents($historyFile, $curDate . ',' . $signal . "\n", FILE_APPEND);
                    }
                }

                $result = serialize($result);
                file_put_contents(self::SIGCACHE_PATH . $oltid . '_' . self::SIGCACHE_EXT, $result);

                // saving macindex as MAC => devID
                $macTmp = array_flip($macTmp);
                $macTmp = serialize($macTmp);
                file_put_contents(self::MACDEVIDCACHE_PATH . $oltid . '_' . self::MACDEVIDCACHE_EXT, $macTmp);
            }
        }
    }

    /**
     * Performs signal preprocessing for sig/mac index arrays and stores it into cache
     *
     * @param int $oltid
     * @param array $sigIndex
     * @param array $macIndex
     * @param array $snmpTemplate
     *
     * @return void
     */
    public function signalParseStels($oltid, $sigIndex, $macIndex, $snmpTemplate) {
        $oltid = vf($oltid, 3);
        $sigTmp = array();
        $macTmp = array();
        $macDevIdx = array();
        $result = array();
        $curDate = curdatetime();
        $plasticIndexSig = 0;
        $plasticIndexMac = 0;

//signal index preprocessing
        if ((!empty($sigIndex)) and ( !empty($macIndex))) {
            foreach ($sigIndex as $io => $eachsig) {
                $line = explode('=', $eachsig);
//signal is present
                if (isset($line[1])) {
                    $signalRaw = trim($line[1]); // signal level
                    $signalOnuPort = str_replace($snmpTemplate['SIGINDEX'], '', $line[0]);
                    $signalOnuPort = explode('.', $signalOnuPort);
                    $plasticIndexSig = trim($signalOnuPort[1]);
//                    $plasticIndexSig = ($plasticIndexSig * 256) + 1; // realy shitty index
                    if ($signalRaw == $snmpTemplate['DOWNVALUE'] or empty($signalRaw)) {
                        $signalRaw = 'Offline';
                    } else {
                        if ($snmpTemplate['OFFSETMODE'] == 'logm') {
                            if ($snmpTemplate['OFFSET']) {
                                $signalRaw = round(10 * log10($signalRaw) - $snmpTemplate['OFFSET'], 2);
                            }
                        }
                    }

                    $sigTmp[$signalOnuPort[0] . ':' . $plasticIndexSig] = $signalRaw;
                }
            }


//mac index preprocessing
            foreach ($macIndex as $io => $eachmac) {
                $line = explode('=', $eachmac);
//mac is present
                if (isset($line[1])) {
                    $macRaw = trim($line[1]); //mac address
                    $macOnuPort = str_replace($snmpTemplate['MACINDEX'], '', $line[0]);
                    $macOnuPort = explode('.', $macOnuPort);
                    $plasticIndexMac = trim($macOnuPort[1]);
                    $macRaw = str_replace(' ', ':', $macRaw);
                    $macRaw = strtolower($macRaw);
                    $macTmp[$macOnuPort[0] . ':' . $plasticIndexMac] = $macRaw;
                    $macDevIdx[$macRaw] = $macOnuPort[0] . ':' . $plasticIndexMac;
//                    $macDevIdx[$macRaw] = $macOnuPort[0] . '.' . (($plasticIndexMac - 1) / 256);
                }
            }


//storing results
            if (!empty($macTmp)) {
                foreach ($macTmp as $devId => $eachMac) {
                    if (isset($sigTmp[$devId])) {
                        $signal = $sigTmp[$devId];
                        $result[$eachMac] = $signal;
//signal history filling
                        $historyFile = self::ONUSIG_PATH . md5($eachMac);
                        if ($signal == 'Offline') {
                            $signal = -9000; //over 9000 offline signal level :P
                        }
                        file_put_contents($historyFile, $curDate . ',' . $signal . "\n", FILE_APPEND);
                    }
                }

                $result = serialize($result);
                $onuTmp = serialize($macTmp);
                $macDevIdx = serialize($macDevIdx);

                file_put_contents(self::SIGCACHE_PATH . $oltid . '_' . self::SIGCACHE_EXT, $result);
                file_put_contents(self::ONUCACHE_PATH . $oltid . '_' . self::ONUCACHE_EXT, $onuTmp);
                file_put_contents(self::INTCACHE_PATH . $oltid . '_' . self::INTCACHE_EXT, $macDevIdx);
                file_put_contents(self::MACDEVIDCACHE_PATH . $oltid . '_' . self::MACDEVIDCACHE_EXT, $macDevIdx);
            }
        }
    }

    /**
     * Parses & stores in cache OLT ONU distances
     *
     * @param int $oltid
     * @param array $distIndex
     * @param array $macIndex
     *
     * @return void
     */
    protected function distanceParseStels($oltid, $distIndex, $macIndex) {
        $oltid = vf($oltid, 3);
        $distTmp = array();
        $onuTmp = array();
        $result = array();
        $curDate = curdatetime();

//distance index preprocessing
        if ((!empty($distIndex)) and ( !empty($macIndex))) {
            foreach ($distIndex as $io => $eachdist) {
                $line = explode('=', $eachdist);
//distance is present
                if (isset($line[1])) {
                    $distanceRaw = trim($line[1]); // distance
                    $devIndex = $line[0];
                    $devIndex = explode('.', $devIndex);
                    $portIndex = trim($devIndex[0]);
                    $devIndex = trim($devIndex[1]);
//                    $devIndex = (($devIndex * 256) + 1);
                    $distTmp[$portIndex . ':' . $devIndex] = $distanceRaw;
                }
            }


//mac index preprocessing
            foreach ($macIndex as $io => $eachmac) {
                $line = explode('=', $eachmac);
//mac is present
                if (isset($line[1])) {
                    $macRaw = trim($line[1]); //mac address
                    $devIndex = trim($line[0]);
                    $devIndex = explode('.', $devIndex);
                    $portIndex = trim($devIndex[0]);
                    $devIndex = $devIndex[1];
                    $macRaw = str_replace(' ', ':', $macRaw);
                    $macRaw = strtolower($macRaw);
                    $onuTmp[$portIndex . ':' . $devIndex] = $macRaw;
                }
            }


//storing results
            if (!empty($onuTmp)) {
                foreach ($onuTmp as $devId => $eachMac) {
                    if (isset($distTmp[$devId])) {
                        $distance = $distTmp[$devId];
                        $result[$eachMac] = $distance;
                    }
                }
                $result = serialize($result);
                file_put_contents(self::DISTCACHE_PATH . $oltid . '_' . self::DISTCACHE_EXT, $result);
            }
        }
    }

    /**
     * Parses & stores in cache OLT ONU interfaces
     *
     * @param int $oltid
     * @param array $intIndex
     * @param array $macIndex
     *
     * @return void
     */
    protected function interfaceParseStels($oltid, $intIndex, $macIndex) {
        $oltid = vf($oltid, 3);
        $intTmp = array();
        $macTmp = array();
        $result = array();

//distance index preprocessing
        if ((!empty($intIndex)) and ( !empty($macIndex))) {
            foreach ($intIndex as $io => $eachint) {
                $line = explode('=', $eachint);
//distance is present
                if (isset($line[1])) {
// distance
                    $devIndex = trim($line[0]); // device index
                    $devIndex = explode('.', $devIndex);
                    $portIndex = trim($devIndex[0]);
                    $interfaceRaw = $devIndex[0] . ':' . $devIndex[1];
//                    $devIndex = ($devIndex[1] * 256) + 1;
                    $intTmp[$portIndex . ':' . $devIndex] = $interfaceRaw;
                }
            }

//mac index preprocessing
            foreach ($macIndex as $io => $eachmac) {
                $line = explode('=', $eachmac);
//mac is present
                if (isset($line[1])) {
                    $macRaw = trim($line[1]); //mac address
                    $devIndex = trim($line[0]); //device index
                    $devIndex = explode('.', $devIndex);
                    $portIndex = trim($devIndex[0]);
                    $devIndex = $devIndex[1];
                    $macRaw = str_replace(' ', ':', $macRaw);
                    $macRaw = strtolower($macRaw);
                    $macTmp[$portIndex . ':' . $devIndex] = $macRaw;
                }
            }

//storing results
            if (!empty($macTmp)) {
                foreach ($macTmp as $devId => $eachMac) {
                    if (isset($intTmp[$devId])) {
                        $interface = $intTmp[$devId];
                        $result[$eachMac] = $interface;
                    }
                }
                $result = serialize($result);
                file_put_contents(self::INTCACHE_PATH . $oltid . '_' . self::INTCACHE_EXT, $result);
            }
        }
    }

    /**
     * Parses & stores in cache OLT ONU interfaces
     *
     * @param int $oltid
     * @param array $deregIndex
     * @param array $macIndex
     *
     * @return void
     */
    protected function lastDeregParseStels($oltid, $deregIndex, $macIndex) {
        $oltid = vf($oltid, 3);
        $deregTmp = array();
        $onuTmp = array();
        $result = array();

//dereg index preprocessing
        if ((!empty($deregIndex)) and ( !empty($macIndex))) {
            foreach ($deregIndex as $io => $eachdereg) {
                $line = explode('=', $eachdereg);
//dereg is present
                if (isset($line[1])) {
                    $deregRaw = trim(trim($line[1]), '"'); // dereg
                    $devIndex = $line[0];
                    $devIndex = explode('.', $devIndex);
                    $portIndex = trim($devIndex[0]);
                    $devIndex = trim($devIndex[1]);
//                    $devIndex = (($devIndex * 256) + 1);
                    $deregTmp[$portIndex . ':' . $devIndex] = $deregRaw;
                }
            }

//mac index preprocessing
            foreach ($macIndex as $io => $eachmac) {
                $line = explode('=', $eachmac);
//mac is present
                if (isset($line[1])) {
                    $macRaw = trim($line[1]); //mac address
                    $devIndex = trim($line[0]);
                    $devIndex = explode('.', $devIndex);
                    $portIndex = trim($devIndex[0]);
                    $devIndex = $devIndex[1];
                    $macRaw = str_replace(' ', ':', $macRaw);
                    $macRaw = strtolower($macRaw);
                    $onuTmp[$portIndex . ':' . $devIndex] = $macRaw;
                }
            }

//storing results
            if (!empty($onuTmp)) {
                foreach ($onuTmp as $devId => $eachMac) {
                    if (isset($deregTmp[$devId])) {
                        $distance = $deregTmp[$devId];
                        $result[$eachMac] = $distance;
                    }
                }
                $result = serialize($result);
                file_put_contents(self::DEREGCACHE_PATH . $oltid . '_' . self::DEREGCACHE_EXT, $result);
            }
        }
    }

    /**
     * Parses & stores to cache ONUs FDB cache (MACs behind ONU)
     *
     * @param $oltID
     * @param $onuMACIndex
     * @param $fdbIndex
     * @param $fdbMACIndex
     * @param $fdbVLANIndex
     */
    protected function fdbParseStels($oltID, $onuMACIndex, $fdbIndex, $fdbMACIndex, $fdbVLANIndex) {
        $macLLIDIndexes = array();
        $fdbLLIDIndexes = array();
        $fdbIdxMAC = array();
        $fdbIdxVLAN = array();
        $fdbCahce = array();

// processing $onuMACIndex array to get pon port number + ONU LLID => ONU MAC mapping
        if (!empty($onuMACIndex) and ! empty($fdbIndex)) {
            foreach ($onuMACIndex as $eachIdx => $eachONUMAC) {
                $line = explode('=', $eachONUMAC);
// MAC is present
                if (isset($line[1])) {
                    $onuMAC = trim($line[1]);
                    $tmpIndex = trim($line[0]);               // pon port number + device index
                    $tmpIndex = explode('.', $tmpIndex);

                    $portIndex = trim($tmpIndex[0]);           // pon port number
                    $devIndexRaw = $tmpIndex[1];
                    $devIndexLLID = ($devIndexRaw - 1) / 256;     // ONU LLID
                    $macLLIDIndexes[$portIndex . ':' . $devIndexLLID] = $onuMAC;     // pon port number + ONU LLID => ONU MAC
                }
            }

// processing FDBIndex array to get FDB index number => pon port number + ONU LLID mapping
            foreach ($fdbIndex as $each => $eachIdx) {
                $line = explode('=', $eachIdx);
// ONU LLID is present
                if (isset($line[1])) {
                    $devLLID = trim($line[1]);                   // ONU LLID
                    $tmpIndex = trim($line[0]);                   // pon port number + FDB index
                    $tmpIndex = explode('.', $tmpIndex);

                    $portIndex = trim($tmpIndex[0]);               // pon port number
                    $fdbIdxRaw = $tmpIndex[1];                     // FDB index number
                    $fdbLLIDIndexes[$fdbIdxRaw] = $portIndex . ':' . $devLLID;       // FDB index number => pon port number + ONU LLID
                }
            }

// processing $fdbMACIndex array to get FDB index number => FDB MAC mapping
            foreach ($fdbMACIndex as $each => $eachIdx) {
                $line = explode('=', $eachIdx);
// FDB MAC is present
                if (isset($line[1])) {
                    $fdbMAC = trim($line[1]);                   // FDB MAC
                    $tmpIndex = trim($line[0]);                   // pon port number + FDB index
                    $tmpIndex = explode('.', $tmpIndex);

                    $fdbIdxRaw = $tmpIndex[1];                     // FDB index number
                    $fdbIdxMAC[$fdbIdxRaw] = $fdbMAC;               // FDB index number => FDB MAC
                }
            }

// processing $fdbVLANIndex array to get FDB index number => FDB VLAN mapping
            foreach ($fdbVLANIndex as $each => $eachIdx) {
                $line = explode('=', $eachIdx);
// FDB VLAN is present
                if (isset($line[1])) {
                    $fdbVLAN = trim($line[1]);                   // FDB VLAN
                    $tmpIndex = trim($line[0]);                   // pon port number + FDB index
                    $tmpIndex = explode('.', $tmpIndex);

                    $fdbIdxRaw = $tmpIndex[1];                     // FDB index number
                    $fdbIdxVLAN[$fdbIdxRaw] = $fdbVLAN;             // FDB index number => FDB VLAN
                }
            }

            if (!empty($macLLIDIndexes) and ! empty($fdbLLIDIndexes)) {
                foreach ($macLLIDIndexes as $eachLLID => $eachONUMAC) {
                    $onuFDBIdxs = array_keys($fdbLLIDIndexes, $eachLLID);

                    if (!empty($onuFDBIdxs)) {
                        $tmpFDBArr = array();
                        $tmpONUMAC = strtolower(AddMacSeparator(RemoveMacAddressSeparator($eachONUMAC, array(':', '-', '.', ' '))));

                        foreach ($onuFDBIdxs as $io => $eachIdx) {
                            $tmpFDBMAC = empty($fdbIdxMAC[$eachIdx]) ? '' : $fdbIdxMAC[$eachIdx];

                            if (empty($tmpFDBMAC) or $tmpFDBMAC == $eachONUMAC) {
                                continue;
                            } else {
                                $tmpFDBMAC = strtolower(AddMacSeparator(RemoveMacAddressSeparator($tmpFDBMAC, array(':', '-', '.', ' '))));
                                $tmpFDBVLAN = empty($fdbIdxVLAN[$eachIdx]) ? '' : $fdbIdxVLAN[$eachIdx];
                                $tmpONUID = $this->getONUIDByMAC($tmpONUMAC);
                                $tmpONUID = (empty($tmpONUID)) ? $eachIdx : $tmpONUID;
                                $tmpFDBArr[$tmpONUID] = array('mac' => $tmpFDBMAC, 'vlan' => $tmpFDBVLAN);
                            }
                        }

                        $fdbCahce[$tmpONUMAC] = $tmpFDBArr;
                    }
                }
            }
        }

        $fdbCahce = serialize($fdbCahce);
        file_put_contents(self::FDBCACHE_PATH . $oltID . '_' . self::FDBCACHE_EXT, $fdbCahce);
    }

    /**
     * Processes V-SOLUTION OLT MAC adresses and returns them in array: LLID=>MAC
     *
     * @param $macIndex
     * @param $snmpTemplate
     *
     * @return array
     */
    protected function macParseVSOL($macIndex, $snmpTemplate) {
        $ONUsMACs = array();

        if (!empty($macIndex)) {
//mac index preprocessing
            foreach ($macIndex as $io => $eachmac) {
                $line = explode('=', $eachmac);

                if (empty($line[0]) || empty($line[1])) {
                    continue;
                }

                $tmpONUPortLLID = trim($line[0]);

                if ($snmpTemplate['misc']['GETACTIVEONUMACONLY']) {
                    $tmpONUMAC = rtrim(chunk_split(str_replace(array('"', "0x"), '', trim($line[1])), 2, ':'), ':'); //mac address
                } else {
                    $tmpONUMAC = str_replace('"', '', trim($line[1])); //mac address
                }

//mac is present
                if (!empty($tmpONUPortLLID) and ! empty($tmpONUMAC)) {
                    $ONUsMACs[$tmpONUPortLLID] = $tmpONUMAC;
                }
            }
        }

        return ($ONUsMACs);
    }

    /**
     * Performs signal preprocessing for sig/mac index arrays and stores it into cache
     *
     * @param int $oltid
     * @param array $sigIndex
     * @param array $macIndex
     *
     * @return void
     */
    protected function signalParseVSOL($oltid, $sigIndex, $macIndexProcessed) {
        $ONUsModulesTemps = array();
        $ONUsModulesVoltages = array();
        $ONUsModulesCurrents = array();
        $ONUsSignals = array();
        $result = array();
        $macDevID = array();
        $curDate = curdatetime();
        $oltid = vf($oltid, 3);

//signal index preprocessing
        if ((!empty($sigIndex)) and ( !empty($macIndexProcessed))) {
            foreach ($sigIndex as $io => $eachsig) {
                $line = explode('=', $eachsig);

//signal is present
                if (isset($line[0])) {
                    $tmpOIDParamaterPiece = substr(trim($line[0]), 0, 1);
                    $tmpONUPortLLID = substr(trim($line[0]), 2);

// just because we can't(I dunno why - honestly) just query the
// .1.3.6.1.4.1.37950.1.1.5.12.2.1.8.1.6 and .1.3.6.1.4.1.37950.1.1.5.12.2.1.8.1.7 OIDs
// cause it's simply returns NOTHING - we need to take a start from the higher tree point - .1.3.6.1.4.1.37950.1.1.5.12.2.1.8.1
// and then we can extract all necessary values

                    switch ($tmpOIDParamaterPiece) {
                        case '3':
                            $ONUsModulesTemps[$tmpONUPortLLID] = trim($line[1]); // may be we'll show this somewhere in future
                            break;

                        case '4':
                            $ONUsModulesVoltages[$tmpONUPortLLID] = trim($line[1]); // may be we'll show this somewhere in future
                            break;

                        case '5':
                            $ONUsModulesCurrents[$tmpONUPortLLID] = trim($line[1]); // may be we'll show this somewhere in future
                            break;

// may be we'll show this somewhere in future
                        case '6':
                            $SignalRaw = trim($line[1]);
                            $ONUsSignals[$tmpONUPortLLID]['SignalTXRaw'] = $SignalRaw;
                            $ONUsSignals[$tmpONUPortLLID]['SignalTXdBm'] = trim(substr(stristr(stristr(stristr($SignalRaw, '('), ')', true), 'dBm', true), 1));
                            break;

                        case '7':
                            $SignalRaw = trim($line[1]);
                            $ONUsSignals[$tmpONUPortLLID]['SignalRXRaw'] = $SignalRaw;
                            $ONUsSignals[$tmpONUPortLLID]['SignalRXdBm'] = trim(substr(stristr(stristr(stristr($SignalRaw, '('), ')', true), 'dBm', true), 1));
                            break;
                    }
                }
            }

//storing results
            foreach ($macIndexProcessed as $devId => $eachMac) {
                if (isset($ONUsSignals[$devId])) {
//signal history filling
                    $historyFile = self::ONUSIG_PATH . md5($eachMac);
                    $signal = $ONUsSignals[$devId]['SignalRXdBm'];

                    if (!empty($signal)) {
                        $result[$eachMac] = $signal;
                    }

                    if (empty($signal) or $signal == 'Offline') {
                        $signal = -9000; //over 9000 offline signal level :P
                    }

                    file_put_contents($historyFile, $curDate . ',' . $signal . "\n", FILE_APPEND);
                }
            }

            $result = serialize($result);
            $macDevID = serialize(array_flip($macIndexProcessed));
            $macIndexProcessed = serialize($macIndexProcessed);
            file_put_contents(self::SIGCACHE_PATH . $oltid . '_' . self::SIGCACHE_EXT, $result);
            file_put_contents(self::ONUCACHE_PATH . $oltid . '_' . self::ONUCACHE_EXT, $macIndexProcessed);
            file_put_contents(self::MACDEVIDCACHE_PATH . $oltid . '_' . self::MACDEVIDCACHE_EXT, $macDevID);
        }
    }

    /**
     * Performs distance preprocessing for distance/mac index arrays and stores it into cache
     *
     * @param $oltid
     * @param $DistIndex
     * @param $macIndexProcessed
     */
    protected function distanceParseVSOL($oltid, $DistIndex, $macIndexProcessed) {
        $ONUDistances = array();
        $result = array();

        if (!empty($macIndexProcessed) and ! empty($DistIndex)) {
//last dereg index preprocessing
            foreach ($DistIndex as $io => $eachRow) {
                $line = explode('=', $eachRow);

                if (empty($line[0]) || empty($line[1])) {
                    continue;
                }

                $tmpONUPortLLID = trim($line[0]);
                $tmpONUDistance = trim($line[1]);

                $ONUDistances[$tmpONUPortLLID] = $tmpONUDistance;
            }

//storing results
            foreach ($macIndexProcessed as $devId => $eachMac) {
                if (isset($ONUDistances[$devId])) {
                    $result[$eachMac] = $ONUDistances[$devId];
                }
            }

            $result = serialize($result);
            file_put_contents(self::DISTCACHE_PATH . $oltid . '_' . self::DISTCACHE_EXT, $result);
        }
    }

    /**
     * Performs interface preprocessing for interface/mac index arrays and stores it into cache
     *
     * @param $oltid
     * @param $IfaceIndex
     * @param $macIndexProcessed
     * @param $ifaceCustDescrRaw
     */
    protected function interfaceParseVSOL($oltid, $IfaceIndex, $macIndexProcessed, $ifaceCustDescrRaw = array()) {
        $ONUIfaces = array();
        $result = array();
        $processIfaceCustDescr = !empty($ifaceCustDescrRaw);
        $ifaceCustDescrIdx = array();
        $ifaceCustDescrArr = array();

// olt iface descr extraction
        if ($processIfaceCustDescr) {
            foreach ($ifaceCustDescrRaw as $io => $each) {
                if (empty($each)) {
                    continue;
                }

                $ifDescr = explode('=', str_replace(array(" ", "\t", "\n", "\r", "\0", "\x0B"), '', $each));

                if (empty($ifDescr[0]) && empty($ifDescr[1])) {
                    continue;
                }

                $ifaceCustDescrIdx[$ifDescr[0]] = $ifDescr[1];
            }
        }

        if (!empty($macIndexProcessed) and ! empty($IfaceIndex)) {
//OLT iface index preprocessing
            foreach ($IfaceIndex as $io => $eachRow) {
                if (empty($eachRow)) {
                    continue;
                }

                $line = explode('=', str_replace(array(" ", "\t", "\n", "\r", "\0", "\x0B"), '', $eachRow));

                if (empty($line[0]) || empty($line[1])) {
                    continue;
                }

                $tmpONUPortLLID = trim($line[0]);
                $tmpONUIface = trim($line[1]);

                $ONUIfaces[$tmpONUPortLLID] = $tmpONUIface;
            }

//storing results
            foreach ($macIndexProcessed as $devId => $eachMac) {
                $tPONIfaceNum = substr($devId, 0, 1);

                if (array_key_exists($tPONIfaceNum, $ONUIfaces)) {
                    $tPONIfaceName = $ONUIfaces[$tPONIfaceNum];
                    $tPONIfaceStr = $tPONIfaceName . ' / ' . str_replace('.', ':', $devId);
                    $cleanIface = strstr($tPONIfaceStr, ':', true);

                    if ($processIfaceCustDescr && !isset($ifaceCustDescrArr[$cleanIface]) && array_key_exists($tPONIfaceNum, $ifaceCustDescrIdx)) {
                        $ifaceCustDescrArr[$cleanIface] = $ifaceCustDescrIdx[$tPONIfaceNum];
                    }
                } else {
                    $tPONIfaceStr = str_replace('.', ':', $devId);
                }

                $result[$eachMac] = $tPONIfaceStr;
            }

            $result = serialize($result);
            $ifaceCustDescrArr = serialize($ifaceCustDescrArr);
            file_put_contents(self::INTCACHE_PATH . $oltid . '_' . self::INTCACHE_EXT, $result);
            file_put_contents(self::INTCACHE_PATH . $oltid . '_' . self::INTDESCRCACHE_EXT, $ifaceCustDescrArr);
        }
    }

    /**
     * Performs last dereg reason preprocessing for dereg reason/mac index arrays and stores it into cache
     *
     * @param $oltid
     * @param $LastDeregIndex
     * @param $macIndex
     * @param $snmpTemplate
     */
    protected function lastDeregParseVSOL($oltid, $LastDeregIndex, $macIndexProcessed) {
        $ONUDeRegs = array();
        $result = array();

        if (!empty($macIndexProcessed) and ! empty($LastDeregIndex)) {
//last dereg index preprocessing
            foreach ($LastDeregIndex as $io => $eachRow) {
                $line = explode('=', $eachRow);

                if (empty($line[0]) || empty($line[1])) {
                    continue;
                }

                $tmpONUPortLLID = trim($line[0]);
                $tmpONULastDeregReason = intval(trim($line[1]));

                switch ($tmpONULastDeregReason) {
                    case 0:
                        $TxtColor = '"#F80000"';
                        $tmpONULastDeregReasonStr = 'Wire down';
                        break;

                    case 1:
                        $TxtColor = '"#FF4400"';
                        $tmpONULastDeregReasonStr = 'Power off';
                        break;

                    default:
                        $TxtColor = '"#000000"';
                        $tmpONULastDeregReasonStr = 'Unknown';
                        break;
                }

                if (!empty($tmpONUPortLLID)) {
                    $tmpONULastDeregReasonStr = wf_tag('font', false, '', 'color=' . $TxtColor . '') .
                            $tmpONULastDeregReasonStr .
                            wf_tag('font', true);

                    $ONUDeRegs[$tmpONUPortLLID] = $tmpONULastDeregReasonStr;
                }
            }

//storing results
            foreach ($macIndexProcessed as $devId => $eachMac) {
                if (isset($ONUDeRegs[$devId])) {
                    $result[$eachMac] = $ONUDeRegs[$devId];
                }
            }

            $result = serialize($result);
            file_put_contents(self::DEREGCACHE_PATH . $oltid . '_' . self::DEREGCACHE_EXT, $result);
        }
    }

    /**
     * Parses & stores to cache ONUs FDB cache (MACs behind ONU)
     *
     * @param $oltID
     * @param $onuMACIndex
     * @param $fdbIndex
     * @param $fdbVLANIndex
     */
    protected function fdbParseVSOL($oltID, $onuMACIndex, $fdbIndex, $fdbVLANIndex) {
        $fdbIdxMAC = array();
        $fdbIdxVLAN = array();
        $fdbCahce = array();

        if (!empty($fdbIndex)) {
// processing FDBIndex array to get FDB MAC => pon port number + ONU LLID mapping
            foreach ($fdbIndex as $each => $eachIdx) {
                $line = explode('=', $eachIdx);
// ONU LLID is present
                if (isset($line[1])) {
                    $portLLID = trim(str_replace(array('"', 'EPON0/'), '', $line[1]));        // pon port number + ONU LLID
                    $portLLID = str_replace(':', '.', $portLLID);
                    $fdbMAC = trim(convertMACDec2Hex($line[0]));                            // FDB MAC in dotted DEC format
                    $fdbIdxMAC[$fdbMAC] = $portLLID;             // FDB MAC => pon port number + ONU LLID
                }
            }
        }

        if (!empty($fdbVLANIndex)) {
// processing $fdbVLANIndex array to get FDB MAC => FDB VLAN mapping
            foreach ($fdbVLANIndex as $each => $eachIdx) {
                $line = explode('=', $eachIdx);
// FDB VLAN is present
                if (isset($line[1])) {
                    $fdbVLAN = trim($line[1]);                // pon port number + ONU LLID
                    $fdbMAC = trim(convertMACDec2Hex($line[0]));                            // FDB MAC in dotted DEC format
                    $fdbIdxVLAN[$fdbMAC] = $fdbVLAN;             // FDB MAC => FDB VLAN
                }
            }
        }

        if (!empty($onuMACIndex) and ! empty($fdbIdxMAC)) {
            foreach ($onuMACIndex as $eachLLID => $eachONUMAC) {
                $onuFDBIdxs = array_keys($fdbIdxMAC, $eachLLID);

                if (!empty($onuFDBIdxs)) {
                    $tmpFDBArr = array();

                    foreach ($onuFDBIdxs as $io => $eachFDBMAC) {
                        if (empty($eachFDBMAC) or $eachFDBMAC == $eachONUMAC) {
                            continue;
                        } else {
                            $tmpFDBVLAN = empty($fdbIdxVLAN[$eachFDBMAC]) ? '' : $fdbIdxVLAN[$eachFDBMAC];
                            $tmpONUID = $this->getONUIDByMAC($eachONUMAC);
                            $tmpONUID = (empty($tmpONUID)) ? $io : $tmpONUID;
                            $tmpFDBArr[$tmpONUID] = array('mac' => $eachFDBMAC, 'vlan' => $tmpFDBVLAN);
                        }
                    }

                    $fdbCahce[$eachONUMAC] = $tmpFDBArr;
                }
            }
        }

        $fdbCahce = serialize($fdbCahce);
        file_put_contents(self::FDBCACHE_PATH . $oltID . '_' . self::FDBCACHE_EXT, $fdbCahce);
    }

    /**
     * Performs  OLT device polling with snmp
     *
     * @param int $oltid
     *
     * @return void
     */
    public function pollOltSignal($oltid) {
        $oltid = vf($oltid, 3);
        if (isset($this->allOltDevices[$oltid])) {
            if (isset($this->allOltSnmp[$oltid])) {
                $oltCommunity = $this->allOltSnmp[$oltid]['community'];
                $oltModelId = $this->allOltSnmp[$oltid]['modelid'];
                $oltIp = $this->allOltSnmp[$oltid]['ip'];
                $oltNoFDBQ = $this->allOltSnmp[$oltid]['nofdbquery'];
                if (isset($this->snmpTemplates[$oltModelId])) {
                    if (isset($this->snmpTemplates[$oltModelId]['signal'])) {
                        $pollingStart = time();

                        /**
                         *  BDCOM/Eltex devices polling
                         */
                        if ($this->snmpTemplates[$oltModelId]['signal']['SIGNALMODE'] == 'BDCOM') {
                            $sigIndexOID = $this->snmpTemplates[$oltModelId]['signal']['SIGINDEX'];
                            $sigIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $sigIndexOID, self::SNMPCACHE);
                            $sigIndex = str_replace($sigIndexOID . '.', '', $sigIndex);
                            $sigIndex = str_replace($this->snmpTemplates[$oltModelId]['signal']['SIGVALUE'], '', $sigIndex);
                            $sigIndex = explodeRows($sigIndex);
                            $ifaceCustDescrIndex = array();

//ONU distance polling for bdcom devices
                            if (isset($this->snmpTemplates[$oltModelId]['misc'])) {
                                if (isset($this->snmpTemplates[$oltModelId]['misc']['DISTINDEX'])) {
                                    if (!empty($this->snmpTemplates[$oltModelId]['misc']['DISTINDEX'])) {
                                        $distIndexOid = $this->snmpTemplates[$oltModelId]['misc']['DISTINDEX'];
                                        $distIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $distIndexOid, self::SNMPCACHE);
                                        $distIndex = str_replace($distIndexOid . '.', '', $distIndex);
                                        $distIndex = str_replace($this->snmpTemplates[$oltModelId]['misc']['DISTVALUE'], '', $distIndex);
                                        $distIndex = explodeRows($distIndex);

                                        $onuIndexOid = $this->snmpTemplates[$oltModelId]['misc']['ONUINDEX'];
                                        $onuIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $onuIndexOid, self::SNMPCACHE);
                                        $onuIndex = str_replace($onuIndexOid . '.', '', $onuIndex);
                                        $onuIndex = str_replace($this->snmpTemplates[$oltModelId]['misc']['ONUVALUE'], '', $onuIndex);
                                        $onuIndex = explodeRows($onuIndex);

                                        if (isset($this->snmpTemplates[$oltModelId]['misc']['DEREGREASON'])) {
                                            $deregIndexOid = $this->snmpTemplates[$oltModelId]['misc']['DEREGREASON'];
                                            $deregIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $deregIndexOid, self::SNMPCACHE);
                                            $deregIndex = str_replace($deregIndexOid . '.', '', $deregIndex);
                                            $deregIndex = str_replace($this->snmpTemplates[$oltModelId]['misc']['DEREGVALUE'], '', $deregIndex);
                                            $deregIndex = explodeRows($deregIndex);
                                        }

                                        $intIndexOid = $this->snmpTemplates[$oltModelId]['misc']['INTERFACEINDEX'];
                                        $intIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $intIndexOid, self::SNMPCACHE);
                                        $intIndex = str_replace($intIndexOid . '.', '', $intIndex);
                                        $intIndex = str_replace($this->snmpTemplates[$oltModelId]['misc']['INTERFACEVALUE'], '', $intIndex);
                                        $intIndex = explodeRows($intIndex);


                                        $FDBIndexOid = $this->snmpTemplates[$oltModelId]['misc']['FDBINDEX'];
                                        $FDBIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $FDBIndexOid, self::SNMPCACHE);
                                        $FDBIndex = str_replace($FDBIndexOid . '.', '', $FDBIndex);
                                        $FDBIndex = explodeRows($FDBIndex);
                                    }
                                }

                                if (isset($this->snmpTemplates[$oltModelId]['misc']['IFACECUSTOMDESCR'])) {
                                    $ifaceCustDescrIndexOID = $this->snmpTemplates[$oltModelId]['misc']['IFACECUSTOMDESCR'];
                                    $ifaceCustDescrIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $ifaceCustDescrIndexOID, self::SNMPCACHE);
                                    $ifaceCustDescrIndex = str_replace($ifaceCustDescrIndexOID . '.', '', $ifaceCustDescrIndex);
                                    $ifaceCustDescrIndex = str_replace(array($this->snmpTemplates[$oltModelId]['misc']['INTERFACEVALUE'], '"'), '', $ifaceCustDescrIndex);
                                    $ifaceCustDescrIndex = explodeRows($ifaceCustDescrIndex);
                                }
                            }
//getting other system data from OLT
                            if (isset($this->snmpTemplates[$oltModelId]['system'])) {
                                //OLT uptime
                                if (isset($this->snmpTemplates[$oltModelId]['system']['UPTIME'])) {
                                    $uptimeIndexOid = $this->snmpTemplates[$oltModelId]['system']['UPTIME'];
                                    $oltSystemUptimeRaw = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $uptimeIndexOid, self::SNMPCACHE);
                                    $this->uptimeParseBd($oltid, $oltSystemUptimeRaw);
                                }

                                //OLT temperature
                                if (isset($this->snmpTemplates[$oltModelId]['system']['TEMPERATURE'])) {
                                    $temperatureIndexOid = $this->snmpTemplates[$oltModelId]['system']['TEMPERATURE'];
                                    $oltTemperatureRaw = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $temperatureIndexOid, self::SNMPCACHE);
                                    $this->temperatureParseBd($oltid, $oltTemperatureRaw);
                                }
                            }
//getting MAC index.
                            $macIndexOID = $this->snmpTemplates[$oltModelId]['signal']['MACINDEX'];
                            $macIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $macIndexOID, self::SNMPCACHE);
                            $macIndex = str_replace($macIndexOID . '.', '', $macIndex);
                            $macIndex = str_replace($this->snmpTemplates[$oltModelId]['signal']['MACVALUE'], '', $macIndex);
                            $macIndex = explodeRows($macIndex);
                            $this->signalParseBd($oltid, $sigIndex, $macIndex, $this->snmpTemplates[$oltModelId]['signal']);
//This is here because BDCOM is BDCOM and another snmp queries cant be processed after MACINDEX query in some cases.
                            if (isset($this->snmpTemplates[$oltModelId]['misc'])) {
                                if (isset($this->snmpTemplates[$oltModelId]['misc']['DISTINDEX'])) {
                                    if (!empty($this->snmpTemplates[$oltModelId]['misc']['DISTINDEX'])) {
// processing distance data
                                        $this->distanceParseBd($oltid, $distIndex, $onuIndex);
//processing interfaces data
//and interface description data
                                        $this->interfaceParseBd($oltid, $intIndex, $macIndex, $ifaceCustDescrIndex);
//processing FDB data
                                        if (isset($this->snmpTemplates[$oltModelId]['misc']['FDBMODE']) and $this->snmpTemplates[$oltModelId]['misc']['FDBMODE'] == 'FIRMWARE-F') {
                                            $this->FDBParseBdFirmwareF($oltid, $FDBIndex, $macIndex, $oltModelId);
                                        } else {
                                            $this->FDBParseBd($oltid, $FDBIndex, $macIndex, $oltModelId);
                                        }
                                        if (isset($this->snmpTemplates[$oltModelId]['misc']['DEREGREASON'])) {
//processing last dereg reason data
                                            $this->lastDeregParseBd($oltid, $deregIndex, $onuIndex);
                                        }
                                    }
                                }
                            }
                        }
// BDCOM GP3600
                        if ($this->snmpTemplates[$oltModelId]['signal']['SIGNALMODE'] == 'GPBDCOM') {
                            $sigIndexOID = $this->snmpTemplates[$oltModelId]['signal']['SIGINDEX'];
                            $sigIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $sigIndexOID, self::SNMPCACHE);
                            $sigIndex = str_replace($sigIndexOID . '.', '', $sigIndex);
                            $sigIndex = str_replace($this->snmpTemplates[$oltModelId]['signal']['SIGVALUE'], '', $sigIndex);
                            $sigIndex = explodeRows($sigIndex);

//ONU distance polling for bdcom devices
                            if (isset($this->snmpTemplates[$oltModelId]['misc'])) {
                                if (isset($this->snmpTemplates[$oltModelId]['misc']['DISTINDEX'])) {
                                    if (!empty($this->snmpTemplates[$oltModelId]['misc']['DISTINDEX'])) {
                                        $distIndexOid = $this->snmpTemplates[$oltModelId]['misc']['DISTINDEX'];
                                        $distIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $distIndexOid, self::SNMPCACHE);
                                        $distIndex = str_replace($distIndexOid . '.', '', $distIndex);
                                        $distIndex = str_replace($this->snmpTemplates[$oltModelId]['misc']['DISTVALUE'], '', $distIndex);
                                        $distIndex = explodeRows($distIndex);

                                        $onuIndexOid = $this->snmpTemplates[$oltModelId]['misc']['ONUINDEX'];
                                        $onuIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $onuIndexOid, self::SNMPCACHE);
                                        $onuIndex = str_replace($onuIndexOid . '.', '', $onuIndex);
                                        $onuIndex = str_replace($this->snmpTemplates[$oltModelId]['misc']['ONUVALUE'], '', $onuIndex);
                                        $onuIndex = str_replace('"', '', $onuIndex);
                                        $onuIndex = explodeRows($onuIndex);

                                        $intIndexOid = $this->snmpTemplates[$oltModelId]['misc']['INTERFACEINDEX'];
                                        $intIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $intIndexOid, self::SNMPCACHE);
                                        $intIndex = str_replace($intIndexOid . '.', '', $intIndex);
                                        $intIndex = str_replace($this->snmpTemplates[$oltModelId]['misc']['INTERFACEVALUE'], '', $intIndex);
                                        $intIndex = explodeRows($intIndex);

                                        $FDBIndexOid = $this->snmpTemplates[$oltModelId]['misc']['FDBINDEX'];
                                        $FDBIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $FDBIndexOid, self::SNMPCACHE);
                                        $FDBIndex = str_replace($FDBIndexOid . '.', '', $FDBIndex);
                                        $FDBIndex = explodeRows($FDBIndex);
                                    }
                                }
                            }
//getting other system data from OLT
                            if (isset($this->snmpTemplates[$oltModelId]['system'])) {
                                //OLT uptime
                                if (isset($this->snmpTemplates[$oltModelId]['system']['UPTIME'])) {
                                    $uptimeIndexOid = $this->snmpTemplates[$oltModelId]['system']['UPTIME'];
                                    $oltSystemUptimeRaw = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $uptimeIndexOid, self::SNMPCACHE);
                                    $this->uptimeParseBd($oltid, $oltSystemUptimeRaw);
                                }

                                //OLT temperature
                                if (isset($this->snmpTemplates[$oltModelId]['system']['TEMPERATURE'])) {
                                    $temperatureIndexOid = $this->snmpTemplates[$oltModelId]['system']['TEMPERATURE'];
                                    $oltTemperatureRaw = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $temperatureIndexOid, self::SNMPCACHE);
                                    $this->temperatureParseBd($oltid, $oltTemperatureRaw);
                                }
                            }
//getting MAC index.
                            $macIndexOID = $this->snmpTemplates[$oltModelId]['signal']['MACINDEX'];
                            $macIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $macIndexOID, self::SNMPCACHE);
                            $macIndex = str_replace($macIndexOID . '.', '', $macIndex);
                            $macIndex = str_replace($this->snmpTemplates[$oltModelId]['signal']['MACVALUE'], '', $macIndex);
                            $macIndex = str_replace('"', '', $macIndex);
                            $macIndex = explodeRows($macIndex);

                            $this->signalParseBd($oltid, $sigIndex, $macIndex, $this->snmpTemplates[$oltModelId]['signal']);
//This is here because BDCOM is BDCOM and another snmp queries cant be processed after MACINDEX query in some cases.
                            if (isset($this->snmpTemplates[$oltModelId]['misc'])) {
                                if (isset($this->snmpTemplates[$oltModelId]['misc']['DISTINDEX'])) {
                                    if (!empty($this->snmpTemplates[$oltModelId]['misc']['DISTINDEX'])) {
// processing distance data
                                        $this->distanceParseGPBd($oltid, $distIndex, $onuIndex);
//processing interfaces data
                                        $this->interfaceParseBd($oltid, $intIndex, $macIndex);
//processing FDB data
                                        $this->FDBParseGPBd($oltid, $FDBIndex, $macIndex, $oltModelId);

                                        if (isset($this->snmpTemplates[$oltModelId]['misc']['DEREGREASON'])) {
//processing last dereg reason data
                                            $this->lastDeregParseBd($oltid, $deregIndex, $onuIndex);
                                        }
                                    }
                                }
                            }
                        }

                        /**
                         * Stels FD12XX devices polling
                         */
                        if ($this->snmpTemplates[$oltModelId]['signal']['SIGNALMODE'] == 'STELS12') {
                            $sigIndexOID = $this->snmpTemplates[$oltModelId]['signal']['SIGINDEX'];

                            $sigIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $sigIndexOID, self::SNMPCACHE);
                            $sigIndex = str_replace($sigIndexOID . '.', '', $sigIndex);
                            $sigIndex = str_replace($this->snmpTemplates[$oltModelId]['signal']['SIGVALUE'], '', $sigIndex);
                            $sigIndex = str_replace('.0.0 = ', ' = ', $sigIndex);
                            $sigIndex = explodeRows($sigIndex);
//ONU distance polling for stels12 devices
                            if (isset($this->snmpTemplates[$oltModelId]['misc'])) {
                                if (isset($this->snmpTemplates[$oltModelId]['misc']['DISTINDEX'])) {
                                    if (!empty($this->snmpTemplates[$oltModelId]['misc']['DISTINDEX'])) {
                                        $distIndexOid = $this->snmpTemplates[$oltModelId]['misc']['DISTINDEX'];
                                        $distIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $distIndexOid, self::SNMPCACHE);
                                        $distIndex = str_replace($distIndexOid . '.', '', $distIndex);
                                        $distIndex = str_replace($this->snmpTemplates[$oltModelId]['misc']['DISTVALUE'], '', $distIndex);
                                        $distIndex = explodeRows($distIndex);

                                        $onuIndexOid = $this->snmpTemplates[$oltModelId]['misc']['ONUINDEX'];
                                        $onuIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $onuIndexOid, self::SNMPCACHE);
                                        $onuIndex = str_replace($onuIndexOid . '.', '', $onuIndex);
                                        $onuIndex = str_replace($this->snmpTemplates[$oltModelId]['misc']['ONUVALUE'], '', $onuIndex);
                                        $onuIndex = str_replace('.0.0 = ', ' = ', $onuIndex);
                                        $onuIndex = explodeRows($onuIndex);

                                        $intIndexOid = $this->snmpTemplates[$oltModelId]['misc']['INTERFACEINDEX'];
                                        $intIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $intIndexOid, self::SNMPCACHE);
                                        $intIndex = str_replace($intIndexOid . '.', '', $intIndex);
                                        $intIndex = str_replace($this->snmpTemplates[$oltModelId]['misc']['INTERFACEVALUE'], '', $intIndex);
                                        $intIndex = explodeRows($intIndex);
                                    }
                                }
                            }

//getting MAC index.
                            $macIndexOID = $this->snmpTemplates[$oltModelId]['signal']['MACINDEX'];
                            $macIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $macIndexOID, self::SNMPCACHE);
                            $macIndex = str_replace($macIndexOID . '.', '', $macIndex);
                            $macIndex = str_replace($this->snmpTemplates[$oltModelId]['signal']['MACVALUE'], '', $macIndex);
                            $macIndex = explodeRows($macIndex);
                            $this->signalParseBd($oltid, $sigIndex, $macIndex, $this->snmpTemplates[$oltModelId]['signal']);

                            if (isset($this->snmpTemplates[$oltModelId]['misc'])) {
                                if (isset($this->snmpTemplates[$oltModelId]['misc']['DISTINDEX'])) {
                                    if (!empty($this->snmpTemplates[$oltModelId]['misc']['DISTINDEX'])) {
// processing distance data
                                        $this->distanceParseBd($oltid, $distIndex, $macIndex);
//processing interfaces data
                                        $this->interfaceParseStels12($oltid, $intIndex, $macIndex);
                                    }
                                }
                            }
                        }

// Stels FD11XX or V-Solution 1600D devices polling
                        if ($this->snmpTemplates[$oltModelId]['signal']['SIGNALMODE'] == 'STELSFD' or
                                $this->snmpTemplates[$oltModelId]['signal']['SIGNALMODE'] == 'VSOL') {

                            $sigIndexOID = $this->snmpTemplates[$oltModelId]['signal']['SIGINDEX'];
                            $sigIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $sigIndexOID, self::SNMPCACHE);
                            $sigIndex = str_replace($sigIndexOID . '.', '', $sigIndex);
                            $sigIndex = str_replace($this->snmpTemplates[$oltModelId]['signal']['SIGVALUE'], '', $sigIndex);
                            $sigIndex = explodeRows($sigIndex);

                            $macIndexOID = $this->snmpTemplates[$oltModelId]['signal']['MACINDEX'];
                            $macIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $macIndexOID, self::SNMPCACHE);
                            $macIndex = str_replace($macIndexOID . '.', '', $macIndex);
                            $macIndex = str_replace($this->snmpTemplates[$oltModelId]['signal']['MACVALUE'], '', $macIndex);
                            $macIndex = explodeRows($macIndex);

                            if (isset($this->snmpTemplates[$oltModelId]['system'])) {
                                //OLT uptime
                                if (isset($this->snmpTemplates[$oltModelId]['system']['UPTIME'])) {
                                    $uptimeIndexOid = $this->snmpTemplates[$oltModelId]['system']['UPTIME'];
                                    $oltSystemUptimeRaw = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $uptimeIndexOid, self::SNMPCACHE);
                                    $this->uptimeParseBd($oltid, $oltSystemUptimeRaw);
                                }

                                //OLT temperature
                                if (isset($this->snmpTemplates[$oltModelId]['system']['TEMPERATURE'])) {
                                    $temperatureIndexOid = $this->snmpTemplates[$oltModelId]['system']['TEMPERATURE'];
                                    $oltTemperatureRaw = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $temperatureIndexOid, self::SNMPCACHE);
                                    $this->temperatureParseBd($oltid, $oltTemperatureRaw);
                                }
                            }

                            if ($this->snmpTemplates[$oltModelId]['signal']['SIGNALMODE'] == 'STELSFD') {
                                $this->signalParseStels($oltid, $sigIndex, $macIndex, $this->snmpTemplates[$oltModelId]['signal']);
//ONU distance polling for stels devices
                                if (isset($this->snmpTemplates[$oltModelId]['misc'])) {
                                    if (isset($this->snmpTemplates[$oltModelId]['misc']['DISTINDEX'])) {
                                        if (!empty($this->snmpTemplates[$oltModelId]['misc']['DISTINDEX'])) {
                                            $distIndexOid = $this->snmpTemplates[$oltModelId]['misc']['DISTINDEX'];
                                            $distIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $distIndexOid, self::SNMPCACHE);
                                            $distIndex = str_replace($distIndexOid . '.', '', $distIndex);
                                            $distIndex = str_replace($this->snmpTemplates[$oltModelId]['misc']['DISTVALUE'], '', $distIndex);
                                            $distIndex = explodeRows($distIndex);

                                            $lastDeregIndexOID = $this->snmpTemplates[$oltModelId]['misc']['DEREGREASON'];
                                            $lastDeregIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $lastDeregIndexOID, self::SNMPCACHE);
                                            $lastDeregIndex = str_replace($lastDeregIndexOID . '.', '', $lastDeregIndex);
                                            $lastDeregIndex = str_replace($this->snmpTemplates[$oltModelId]['misc']['DEREGVALUE'], '', $lastDeregIndex);
                                            $lastDeregIndex = explodeRows($lastDeregIndex);

                                            /*                                            $onuIndexOid = $this->snmpTemplates[$oltModelId]['misc']['ONUINDEX'];
                                              $onuIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $onuIndexOid, self::SNMPCACHE);
                                              $onuIndex = str_replace($onuIndexOid . '.', '', $onuIndex);
                                              $onuIndex = str_replace($this->snmpTemplates[$oltModelId]['misc']['ONUVALUE'], '', $onuIndex);
                                              $onuIndex = explodeRows($onuIndex);
                                             */

                                            $this->distanceParseStels($oltid, $distIndex, $macIndex);
//                                            $this->interfaceParseStels($oltid, $sigIndex, $macIndex);
                                            $this->lastDeregParseStels($oltid, $lastDeregIndex, $macIndex);

                                            if (!$oltNoFDBQ) {
                                                $fdbIndexOID = $this->snmpTemplates[$oltModelId]['misc']['FDBINDEX'];
                                                $fdbIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $fdbIndexOID, self::SNMPCACHE);
                                                $fdbIndex = str_replace($fdbIndexOID . '.', '', $fdbIndex);
                                                $fdbIndex = str_replace($this->snmpTemplates[$oltModelId]['misc']['FDBVALUE'], '', $fdbIndex);
                                                $fdbIndex = explodeRows($fdbIndex);

                                                $fdbMACIndexOID = $this->snmpTemplates[$oltModelId]['misc']['FDBMACINDEX'];
                                                $fdbMACIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $fdbMACIndexOID, self::SNMPCACHE);
                                                $fdbMACIndex = str_replace($fdbMACIndexOID . '.', '', $fdbMACIndex);
                                                $fdbMACIndex = str_replace($this->snmpTemplates[$oltModelId]['misc']['FDBMACVALUE'], '', $fdbMACIndex);
                                                $fdbMACIndex = explodeRows($fdbMACIndex);

                                                $fdbVLANIndexOID = $this->snmpTemplates[$oltModelId]['misc']['FDBVLANINDEX'];
                                                $fdbVLANIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $fdbVLANIndexOID, self::SNMPCACHE);
                                                $fdbVLANIndex = str_replace($fdbVLANIndexOID . '.', '', $fdbVLANIndex);
                                                $fdbVLANIndex = str_replace($this->snmpTemplates[$oltModelId]['misc']['FDBVLANVALUE'], '', $fdbVLANIndex);
                                                $fdbVLANIndex = explodeRows($fdbVLANIndex);

                                                $this->fdbParseStels($oltid, $macIndex, $fdbIndex, $fdbMACIndex, $fdbVLANIndex);
                                            }
                                        }
                                    }
                                }
                            } else {
                                $VSOLMACsProcessed = $this->macParseVSOL($macIndex, $this->snmpTemplates[$oltModelId]);

                                if (!empty($VSOLMACsProcessed)) {
                                    $this->signalParseVSOL($oltid, $sigIndex, $VSOLMACsProcessed);

                                    $distIndexOID = $this->snmpTemplates[$oltModelId]['misc']['DISTINDEX'];
                                    $distIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $distIndexOID, self::SNMPCACHE);
                                    $distIndex = str_replace($distIndexOID . '.', '', $distIndex);
                                    $distIndex = str_replace($this->snmpTemplates[$oltModelId]['misc']['DISTVALUE'], '', $distIndex);
                                    $distIndex = explodeRows($distIndex);

                                    $this->distanceParseVSOL($oltid, $distIndex, $VSOLMACsProcessed);

                                    $ifaceIndexOID = $this->snmpTemplates[$oltModelId]['misc']['IFACEDESCR'];
                                    $ifaceIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $ifaceIndexOID, self::SNMPCACHE);
                                    $ifaceIndex = str_replace($ifaceIndexOID . '.', '', $ifaceIndex);
                                    $ifaceIndex = str_replace(array($this->snmpTemplates[$oltModelId]['misc']['IFACEVALUE'], '"'), '', $ifaceIndex);
                                    $ifaceIndex = explodeRows($ifaceIndex);

                                    $ifaceCustDescrIndex = array();
                                    if (isset($this->snmpTemplates[$oltModelId]['misc']['IFACECUSTOMDESCR'])) {
                                        $ifaceCustDescrIndexOID = $this->snmpTemplates[$oltModelId]['misc']['IFACECUSTOMDESCR'];
                                        $ifaceCustDescrIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $ifaceCustDescrIndexOID, self::SNMPCACHE);
                                        $ifaceCustDescrIndex = str_replace($ifaceCustDescrIndexOID . '.', '', $ifaceCustDescrIndex);
                                        $ifaceCustDescrIndex = str_replace(array($this->snmpTemplates[$oltModelId]['misc']['IFACEVALUE'], '"'), '', $ifaceCustDescrIndex);
                                        $ifaceCustDescrIndex = explodeRows($ifaceCustDescrIndex);
                                    }

                                    $this->interfaceParseVSOL($oltid, $ifaceIndex, $VSOLMACsProcessed, $ifaceCustDescrIndex);

                                    $lastDeregIndexOID = $this->snmpTemplates[$oltModelId]['misc']['DEREGREASON'];
                                    $lastDeregIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $lastDeregIndexOID, self::SNMPCACHE);
                                    $lastDeregIndex = str_replace($lastDeregIndexOID . '.', '', $lastDeregIndex);
                                    $lastDeregIndex = str_replace($this->snmpTemplates[$oltModelId]['misc']['DEREGVALUE'], '', $lastDeregIndex);
                                    $lastDeregIndex = explodeRows($lastDeregIndex);

                                    $this->lastDeregParseVSOL($oltid, $lastDeregIndex, $VSOLMACsProcessed);

                                    if (!$oltNoFDBQ) {
                                        $fdbIndexOID = $this->snmpTemplates[$oltModelId]['misc']['FDBINDEX'];
                                        $fdbIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $fdbIndexOID, self::SNMPCACHE);
                                        $fdbIndex = str_replace($fdbIndexOID . '.', '', $fdbIndex);
                                        $fdbIndex = str_replace($this->snmpTemplates[$oltModelId]['misc']['FDBVALUE'], '', $fdbIndex);
                                        $fdbIndex = explodeRows($fdbIndex);

                                        $fdbMACVLANOID = $this->snmpTemplates[$oltModelId]['misc']['FDBVLANINDEX'];
                                        $fdbVLANIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $fdbMACVLANOID, self::SNMPCACHE);
                                        $fdbVLANIndex = str_replace($fdbMACVLANOID . '.', '', $fdbVLANIndex);
                                        $fdbVLANIndex = str_replace($this->snmpTemplates[$oltModelId]['misc']['FDBVLANVALUE'], '', $fdbVLANIndex);
                                        $fdbVLANIndex = explodeRows($fdbVLANIndex);

                                        $this->fdbParseVSOL($oltid, $VSOLMACsProcessed, $fdbIndex, $fdbVLANIndex);
                                    }
                                }
                            }
                        }

//ZTE devices polling
                        if ($this->snmpTemplates[$oltModelId]['signal']['SIGNALMODE'] == 'ZTE') {
                            $ztePoller = new PonZte($oltModelId, $oltid, $oltIp, $oltCommunity);
                            $ztePoller->ponType = 'EPON';
                            $ztePoller->pollEpon();
                        }

                        if ($this->snmpTemplates[$oltModelId]['signal']['SIGNALMODE'] == 'ZTE_GPON') {
                            $ztePoller = new PonZte($oltModelId, $oltid, $oltIp, $oltCommunity);
                            $ztePoller->ponType = 'GPON';
                            $ztePoller->pollGpon();
                        }
                        if ($this->snmpTemplates[$oltModelId]['signal']['SIGNALMODE'] == 'HUAWEI_GPON') {
                            $ztePoller = new PonZte($oltModelId, $oltid, $oltIp, $oltCommunity);
                            $ztePoller->ponType = 'GPON';
                            $ztePoller->huaweiPollGpon();
                        }

                        //filling OLT polling stats
                        $pollingEnd = time();
                        $statsPath = self::POLL_STATS . $oltid;
                        $cachedStats = array();
                        $cachedStats['start'] = $pollingStart;
                        $cachedStats['end'] = $pollingEnd;
                        $cachedStats = serialize($cachedStats);
                        file_put_contents($statsPath, $cachedStats);
                    }
                }
            }
        }
    }

    /**
     * Performs available OLT devices polling. Use only in remote API.
     *
     * @param bool $quiet
     *
     * @return void
     */
    public function oltDevicesPolling($quiet = false) {
        if (!empty($this->allOltDevices)) {
            foreach ($this->allOltDevices as $oltid => $each) {
                if (!$quiet) {
                    print('POLLING:' . $oltid . ' ' . $each . "\n");
                }
                if (@!$this->altCfg['HERD_OF_PONIES']) {
                    $this->pollOltSignal($oltid);
                } else {
                    //starting herd of apocalypse pony here!
                    $pipes = array();
                    proc_close(proc_open('/bin/ubapi "herd&oltid=' . $oltid . '"> /dev/null 2>/dev/null &', array(), $pipes));
                }
            }
        }
    }

    /**
     * Loads avaliable ONUs from database into private data property
     *
     * @return void
     */
    protected function loadOnu() {
        $query = "SELECT * from `pononu`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allOnu[$each['id']] = $each;
                $this->onuMacIdList[$each['mac']] = $each['id'];
                $this->onuSerialIdList[$each['serial']] = $each['id'];
                $this->onuMacOltidList[$each['mac']] = $each['oltid'];
                $this->onuSerialOltidList[$each['serial']] = $each['oltid'];
            }
        }
    }

    /**
     * Loads avaliable ONUs additional users bindings from database into private data property
     *
     * @return void
     */
    protected function loadOnuExtUsers() {
        $query = "SELECT * from `pononuextusers`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allOnuExtUsers[$each['id']] = $each;
            }
        }
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
                    $result[$each['oltid']] ++;
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
     * Getter for loaded ONU devices
     *
     * @return array
     */
    public function getAllOnu() {
        return ($this->allOnu);
    }

    /**
     * Returns ONU ID by ONU MAC or 0 if not found
     * Now also checks serial number
     *
     * @param string $mac
     *
     * @return int
     */
    public function getONUIDByMAC($mac) {
        $mac = strtolower($mac);
        $sn = strtoupper($mac);
        $ONUID = 0;

        if (!empty($this->onuMacIdList)) {
            if (isset($this->onuMacIdList[$mac])) {
                $ONUID = $this->onuMacIdList[$mac];
            }
        }

        if (!empty($this->onuSerialIdList)) {
            if (isset($this->onuSerialIdList[$sn])) {
                $ONUID = $this->onuSerialIdList[$sn];
            }
        }

        return $ONUID;
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
            $macAsHex = dechex(('0x' . $macAsHex) + $macIncrementWith);

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
                }
            }
        }
        return ($result);
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
     * @return int
     */
    public function onuCreate($onumodelid, $oltid, $ip, $mac, $serial, $login) {
        $mac = strtolower($mac);
        $mac = trim($mac);
        $onumodelid = vf($onumodelid, 3);
        $oltid = vf($oltid, 3);
        $ip = mysql_real_escape_string($ip);
        $macRaw = $mac;
        $mac = mysql_real_escape_string($mac);
        $serial = mysql_real_escape_string($serial);
        $login = mysql_real_escape_string($login);
        $login = trim($login);
        $result = 0;
        $modelid = @$this->allOltSnmp[$oltid]['modelid'];
        if (!empty($mac)) {
            if (check_mac_format($mac) or @ $this->snmpTemplates[$modelid]['signal']['SIGNALMODE'] == 'GPBDCOM') {
                if ($this->checkMacUnique($mac)) {
                    $query = "INSERT INTO `pononu` (`id`, `onumodelid`, `oltid`, `ip`, `mac`, `serial`, `login`) " .
                            "VALUES (NULL, '" . $onumodelid . "', '" . $oltid . "', '" . $ip . "', '" . $mac . "', '" . $serial . "', '" . $login . "');";
                    nr_query($query);
                    $result = simple_get_lastid('pononu');
                    log_register('PON CREATE ONU [' . $result . '] MAC `' . $macRaw . '`');
                } else {
                    log_register('PON MACDUPLICATE TRY `' . $macRaw . '`');
                }
            } else {
                log_register('PON MACINVALID TRY `' . $macRaw . '`');
            }
        }
        return ($result);
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
     *
     * @return void
     */
    public function onuSave($onuId, $onumodelid, $oltid, $ip, $mac, $serial, $login) {
        $mac = strtolower($mac);
        $mac = trim($mac);
        $onuId = vf($onuId, 3);
        $onumodelid = vf($onumodelid, 3);
        $oltid = vf($oltid, 3);
        $ip = mysql_real_escape_string($ip);
        $mac = mysql_real_escape_string($mac);
        $serial = mysql_real_escape_string($serial);
        $login = mysql_real_escape_string($login);
        $login = trim($login);
        $where = " WHERE `id`='" . $onuId . "';";
        simple_update_field('pononu', 'onumodelid', $onumodelid, $where);
        simple_update_field('pononu', 'oltid', $oltid, $where);
        simple_update_field('pononu', 'ip', $ip, $where);
        if (!empty($mac)) {
            if (check_mac_format($mac)) {
                $currentMac = $this->allOnu[$onuId]['mac'];
                if ($currentMac != $mac) {
                    if ($this->checkMacUnique($mac)) {
                        simple_update_field('pononu', 'mac', $mac, $where);
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
        simple_update_field('pononu', 'serial', $serial, $where);
        simple_update_field('pononu', 'login', $login, $where);
        log_register('PON EDIT ONU [' . $onuId . '] MAC `' . $mac . '`');
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
        $onuid = vf($onuid, 3);
        if (isset($this->allOnu[$onuid])) {
            simple_update_field('pononu', 'login', $login, "WHERE `id`='" . $onuid . "'");
            log_register('PON ASSIGN ONU [' . $onuid . '] WITH (' . $login . ')');
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
        $onuId = vf($onuId, 3);
        $query = "DELETE from `pononu` WHERE `id`='" . $onuId . "';";
        nr_query($query);
        log_register('PON DELETE ONU [' . $onuId . ']');
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
                $inputs .= wf_TextInput('newmac', __('MAC') . $this->sup, '', true, 20);
                $inputs .= wf_TextInput('newserial', __('Serial number'), '', true, 20);
                $inputs .= wf_TextInput('newlogin', __('Login'), '', true, 20);
                $inputs .= wf_Submit(__('Create'));

                $result .= wf_Form('', 'POST', $inputs, 'glamour');
            } else {
                $messages = new UbillingMessageHelper();
                $result .= $messages->getStyledMessage(__('Any available ONU models exist'), 'error');
            }
        } else {
            $messages = new UbillingMessageHelper();
            $result .= $messages->getStyledMessage(__('Any available OLT devices exist'), 'error');
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

        if ($this->onuUknownUserByMACSearchTelepathy and ( empty($UserLogin) or empty($UserIP))) {
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
                $inputs .= wf_TextInput('newmac', __('MAC') . $this->sup, $onuMac, true, 20, '', '__NewONUMAC');
                $inputs .= wf_TextInput('newserial', __('Serial number'), '', true, 20);
                $inputs .= wf_TextInput('newlogin', __('Login'), $UserLogin, true, 20, '', '__NewONULogin');
                $inputs .= wf_Link('#', __('Check if ONU is assigned to any login already'), true, 'ubButton __CheckONUAssignmentBtn', 'style="width: 100%; text-align: center;padding: 6px 0; margin-top: 5px;"');
                $inputs .= wf_tag('span', false, '', 'id="onuassignment2" style="font-weight: 600; color: #000"');
                $inputs .= wf_tag('span', true);

                if (($this->onuUknownUserByMACSearchShow and ( empty($UserLogin) or empty($UserIP))) or $this->onuUknownUserByMACSearchShowAlways) {
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
                $messages = new UbillingMessageHelper();
                $result .= $messages->getStyledMessage(__('Any available ONU models exist'), 'error');
            }
        } else {
            $messages = new UbillingMessageHelper();
            $result .= $messages->getStyledMessage(__('Any available OLT devices exist'), 'error');
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
            if (!empty($mac)) {
                $vendorframe = wf_tag('iframe', false, '', 'src="?module=macvendor&mac=' . $mac . '" width="360" height="160" frameborder="0"');
                $vendorframe .= wf_tag('iframe', true);
                $result = wf_modalAuto(wf_img('skins/macven.gif', __('Device vendor')), __('Device vendor'), $vendorframe, '');
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
        $inputs = wf_HiddenInput('assignonulogin', $login);
        $inputs .= wf_Selector('assignonuid', $params, __('ONU'), '', false);
        $inputs .= wf_Submit(__('Save'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
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
        $extUserId = vf($extUserId, 3);
        if (isset($this->allOnuExtUsers[$extUserId])) {
            $oldData = $this->allOnuExtUsers[$extUserId];
            $query = "DELETE FROM `pononuextusers` WHERE `id`='" . $extUserId . "';";
            nr_query($query);
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
        $onuId = vf($onuId, 3);
        if (isset($this->allOnu[$onuId])) {
            $loginF = mysql_real_escape_string($login);
            $query = "INSERT INTO `pononuextusers` (`id`,`onuid`,`login`) VALUES "
                    . "(NULL,'" . $onuId . "','" . $loginF . "');";
            nr_query($query);
            log_register('PON EDIT ONU [' . $onuId . '] ASSIGN EXTUSER (' . $login . ')');
        }
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
            $this->loadSignalsCache();
            $onuData = $this->allOnu[$onuId];

            if (isset($this->signalCache[$onuData['mac']])) {
                $signal = $this->signalCache[$onuData['mac']];
                if (($signal > 0) or ( $signal < -27)) {
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
                }
            } elseif (isset($this->signalCache[$onuData['serial']])) {
                $signal = $this->signalCache[$onuData['serial']];
                if (($signal > 0) or ( $signal < -27)) {
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
                }
            } else {
                $ONUIsOffline = true;
                $signal = __('No');
                $sigColor = self::COLOR_NOSIG;
                $sigLabel = 'No signal';
            }

            $result .= wf_tag('div', false, 'onusignalbig');
            $result .= __('Current') . ' ' . __('Signal') . ' ' . __('ONU');
            $result .= wf_delimiter();
            $result .= wf_tag('font', false, '', 'color="' . $sigColor . '" size="16pt"') . $signal . wf_tag('font', true);
            $result .= wf_delimiter();
            $result .= __($sigLabel);
            $result .= wf_tag('div', true);
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
            simple_update_field('pononu', 'login', 'dead', "WHERE `id`='" . $onuId . "'");
            log_register('PON BURIAL ONU [' . $onuId . ']');
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
            simple_update_field('pononu', 'login', '', "WHERE `id`='" . $onuId . "'");
            log_register('PON RESURRECT ONU [' . $onuId . ']');
        } else {
            log_register('PON RESURRECT ONU [' . $onuId . '] FAILED');
        }
    }

    /**
     * Returns ONU edit form
     *
     * @param int $onuId
     *
     * @return string
     */
    public function onuEditForm($onuId) {
        $onuId = vf($onuId, 3);
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
            $inputs .= wf_Selector('editoltid', $this->allOltDevices, __('OLT device') . $this->sup, $this->allOnu[$onuId]['oltid'], true);
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

            if (!empty($onuExtUsers)) {
                foreach ($onuExtUsers as $io => $each) {
                    //Editing feature: 100$ donate or do it yourself. Im to lazy right now.
                    $inputs .= wf_tag('input', false, '', 'name="onuextlogin_' . $each['id'] . '" type="text" value="' . $each['login'] . '" size="20" DISABLED') . ' ';
                    $inputs .= wf_JSAlert(self::URL_ME . '&editonu=' . $onuId . '&deleteextuser=' . $each['id'], wf_img_sized('skins/icon_del.gif', __('Delete'), '13'), $messages->getDeleteAlert()) . ' ';
                    $inputs .= wf_Link(self::URL_USERPROFILE . $each['login'], web_profile_icon());
                    $inputs .= wf_tag('br');
                }
            }
            $inputs .= wf_Submit(__('Save'));

            $onuEditForm = wf_Form('', 'POST', $inputs, 'glamour');
            $gridCells = wf_TableCell($onuEditForm . wf_CleanDiv(), '50%', '');
            $gridCells .= wf_TableCell($this->renderOnuSignalBig($onuId));
            $gridRows = wf_TableRow($gridCells);

            $result = wf_TableBody($gridRows, '100%', 0, '');
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
                $result .= $ponBoxes->renderLinkedBoxes($linkedBoxes);
            }

            $result .= wf_delimiter();
            $result .= wf_BackLink(self::URL_ME);

            //back to primary user profile control
            if (!empty($this->allOnu[$onuId]['login'])) {
                if ($this->allOnu[$onuId]['login'] != 'dead') {
                    $result .= wf_Link(self::URL_USERPROFILE . $this->allOnu[$onuId]['login'], wf_img('skins/icon_user.gif') . ' ' . __('User profile'), false, 'ubButton');
                }
            }

            //ONU burial or resurrection controls
            if (!empty($this->allOnu[$onuId]['login'])) {
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

            //additional login append forms
            if (sizeof($onuExtUsers) < $onuMaxUsers) {
                $extCreationLabel = wf_img_sized('skins/add_icon.png', '', '13') . ' ' . __('Assign additional login');
                $result .= wf_modalAuto($extCreationLabel, __('Additional login') . ' (' . ($onuMaxUsers - $onuCurrentExtUsers) . ' ' . __('remains') . ')', $this->renderOnuExtUserForm($onuId), 'ubButton');
            }



            //ONU deletion control
            if (cfr('PONDEL')) {
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
        $onuId = vf($onuId, 3);
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
                    $result .= wf_Graph($todaySignal, '800', '300', false, $GraphTitle, $GraphXLabel, $GraphYLabel, $ShowRangeSelector);
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
                    $result .= wf_GraphCSV($historyKeyMonth, '800', '300', false, $GraphTitle, $GraphXLabel, $GraphYLabel, $ShowRangeSelector);
                    $result .= wf_delimiter(2);

//all time signal history
                    $GraphTitle = ($ShowTitle) ? __('All time graph') : '';
                    $result .= wf_GraphCSV($historyKey, '800', '300', false, $GraphTitle, $GraphXLabel, $GraphYLabel, $ShowRangeSelector);
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
        if (!wf_CheckGet(array('unknownonulist'))) {
            $result .= wf_modalAuto(wf_img_sized('skins/add_icon.png', '', '16', '16') . ' ' . __('Create') . ' ' . __('ONU'), __('Register new ONU'), $this->onuCreateForm(), 'ubButton') . ' ';
            $availOnuCache = rcms_scandir(self::ONUCACHE_PATH, '*_' . self::ONUCACHE_EXT);
            $result .= wf_Link(self::URL_ME . '&forcepoll=true', wf_img_sized('skins/refresh.gif', '', '16', '16') . ' ' . __('Force query'), false, 'ubButton');
            if (!empty($availOnuCache)) {
                $result .= wf_Link(self::URL_ME . '&unknownonulist=true', wf_img_sized('skins/question.png', '', '16', '16') . ' ' . __('Unknown ONU'), false, 'ubButton');
            }

            $availOnuFdbCache = rcms_scandir(self::FDBCACHE_PATH, '*_' . self::FDBCACHE_EXT);
            if (!empty($availOnuFdbCache)) {
                $result .= wf_Link(self::URL_ME . '&fdbcachelist=true', wf_img_sized('skins/icon_fdb.png', '', '16', '16') . ' ' . __('Current FDB cache'), false, 'ubButton');
            }

            if (@$this->altCfg['PON_ONU_PORT_MAX']) {
                $result .= wf_Link(self::URL_ME . '&oltstats=true', wf_img_sized('skins/icon_stats.gif', '', '16', '16') . ' ' . __('Stats'), false, 'ubButton');
            }

            if (@$this->altCfg['PONMAP_ENABLED']) {
                $result .= wf_Link('?module=ponmap', wf_img_sized('skins/ponmap_icon.png', '', '16', '16') . ' ' . __('ONU Map'), false, 'ubButton');
            }

            if (@$this->altCfg['PON_ONU_SEARCH_ENABLED']) {
                $result .= wf_modalAuto(web_icon_search() . ' ' . __('Search'), __('Search') . ' ' . __('ONU'), $this->renderOnuSearchForm(), 'ubButton');
            }
            if ($this->altCfg['ONUREG_ZTE']) {
                $zteControls = '';
                if (cfr('ONUREGZTE')) {
                    $zteControls .= wf_link('?module=ztevlanbinds', wf_img_sized('skins/register.png', '', '16', '16') . ' ' . __('Edit OLT Cards'), false, 'ubButton');
                }
                if (cfr('ZTEVLANBINDS')) {
                    $zteControls .= wf_link('?module=zteunreg', wf_img_sized('skins/check.png', '', '16', '16') . ' ' . __('Check for unauthenticated ONU/ONT'), false, 'ubButton');
                }
                $result .= wf_modalAuto(web_icon_extended() . ' ' . __('ZTE'), __('ZTE'), $zteControls, 'ubButton');
            }
        } else {
            $result .= wf_BackLink(self::URL_ME);
            $result .= wf_Link(self::URL_ME . '&forcepoll=true&uol=true', wf_img_sized('skins/refresh.gif', '', '16', '16') . ' ' . __('Force query'), false, 'ubButton');
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
     * @return string
     */
    public function loadonuSignalHistory($onuId, $ReturnInSpoiler) {
        $result = $this->onuSignalHistory($onuId, true, true, true, true);

        if ($ReturnInSpoiler) {
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
        $distCacheAvail = rcms_scandir(self::DISTCACHE_PATH, '*_' . self::DISTCACHE_EXT);
        $intCacheAvail = rcms_scandir(self::INTCACHE_PATH, '*_' . self::INTCACHE_EXT);
        $lastDeregCacheAvail = rcms_scandir(self::DEREGCACHE_PATH, '*_' . self::DEREGCACHE_EXT);
        $distCacheAvail = !empty($distCacheAvail) ? true : false;
        $intCacheAvail = !empty($intCacheAvail) ? true : false;
        $lastDeregCacheAvail = !empty($lastDeregCacheAvail) ? true : false;
        $oltOnuCounters = $this->getOltOnuCounts();
        $columns = array('ID');

        if ($intCacheAvail) {
            $columns[] = __('Interface');
        }

        $columns[] = 'Model';
        if (@$this->altCfg['PON_ONUIPASIF']) {
            $columns[] = 'Interface';
        } else {
            $columns[] = 'IP';
        }
        $columns[] = 'MAC';
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
        $opts = '"order": [[ 0, "desc" ]]';

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
                    $refresh_button = wf_tag('span', false, '', 'href="#" id="' . $OLTIDStr . '" title="' . __('Refresh data for this OLT') . '" style="cursor: pointer;"');
                    $refresh_button .= wf_img('skins/refresh.gif');
                    $refresh_button .= wf_tag('span', true);
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
                $tabsList[$QuickOLTLinkID] = array('options' => '',
                    'caption' => $refresh_button . wf_nbsp(4) . wf_img('skins/menuicons/switches.png') . wf_nbsp(2) . @$eachOltData,
                    'additional_data' => $tabClickScript
                );

                $tabsData[$QuickOLTLinkID] = array('options' => 'style="padding: 0 0 0 2px;"',
                    'body' => wf_JqDtLoader($columns, $AjaxURLStr, false, 'ONU', 100, $opts),
                    'additional_data' => ''
                );
            } else {
                $result .= show_window($refresh_button . wf_nbsp(4) . $QuickOLTLink . wf_nbsp(2) . @$eachOltData, wf_JqDtLoader($columns, $AjaxURLStr, false, 'ONU', 100, $opts) . $QuickOLTLinkInput
                );
            }
        }

        if ($this->ponizerUseTabUI) {
            $tabsDivOpts = 'style="border: none;padding: 0;"';
            $tabsLstOpts = 'style="border: none;background: #fff;"';

            if ($this->EnableQuickOLTLinks and ! empty($this->allOltDevices)) {
                $QuickOLTDDLName = 'QuickOLTDDL_100500';
                $QickOLTsArray = $this->allOltDevices;

                $QuickOLTLinkInput = wf_tag('div', false, '', 'style="margin-top: 15px;text-align: right;"') .
                        wf_tag('font', false, '', 'style="font-weight: 600"') . __('Go to OLT') . wf_tag('font', true) .
                        wf_nbsp(2) . wf_Selector($QuickOLTDDLName, $QickOLTsArray, '', '', true) .
                        wf_tag('script', false, '', 'type="text/javascript"') .
                        '$(\'[name="' . $QuickOLTDDLName . '"]\').change(function(evt) {
                                                    $(\'a[href="#QuickOLTLinkID_\'+$(this).val()+\'"]\').click();
                                                 });' .
                        wf_tag('script', true) .
                        wf_tag('div', true);
            } else {
                $QuickOLTLinkInput = '';
            }

            show_window('', $QuickOLTLinkInput . wf_delimiter(0) . wf_TabsCarouselInitLinking() .
                    wf_TabsGen('ui-tabs', $tabsList, $tabsData, $tabsDivOpts, $tabsLstOpts, true) .
                    $QuickOLTLinkInput);
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
        $oltOnuFilled = array();
        $oltOnuPonPortMax = array();
        $oltInterfacesFilled = array();
        $oltInterfaceDescrs = array();
        $signals = array();
        $badSignals = array();
        $avgSignals = array();
        $oltsTemps = array(); //oltId=>temperature

        $statsControls = wf_BackLink(self::URL_ME);
        if (!ubRouting::checkGet('temperature')) {
            $statsControls .= wf_Link(self::URL_ME . '&oltstats=true&temperature=true', wf_img('skins/temperature.png') . ' ' . __('Temperature'), false, 'ubButton');
        } else {
            $statsControls .= wf_Link(self::URL_ME . '&oltstats=true', wf_img('skins/notemperature.png') . ' ' . __('Temperature'), false, 'ubButton');
        }
        $statsControls .= wf_Link(self::URL_ME . '&oltstats=true&pollstats=true', wf_img('skins/log_icon_small.png') . ' ' . __('Devices polling stats'), false, 'ubButton');

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
                if ((!empty($oltModelId)) and ( !empty($oltPorts)) and ( !empty($onuMaxCount))) {
                    $maxOnuPerOlt = $oltPorts * $onuMaxCount;
                    $oltOnuFilled[$oltId] = zb_PercentValue($maxOnuPerOlt, $onuCount);
                    $oltOnuPonPortMax[$oltId] = $onuMaxCount;
                    $onuInterfacesCache = self::INTCACHE_PATH . $oltId . '_' . self::INTCACHE_EXT;
                    $onuSignalsCache = self::SIGCACHE_PATH . $oltId . '_' . self::SIGCACHE_EXT;
                    $oltInterfaceDescrsCache = self::INTCACHE_PATH . $oltId . '_' . self::INTDESCRCACHE_EXT;

                    if (file_exists($onuInterfacesCache)) {
                        $interfaces = file_get_contents($onuInterfacesCache);
                        $interfaces = unserialize($interfaces);
                        if (file_exists($onuSignalsCache)) {
                            $signals = file_get_contents($onuSignalsCache);
                            $signals = unserialize($signals);
                        }

                        if (!empty($interfaces)) {
                            $ifaceDescrs = array();
                            if (file_exists($oltInterfaceDescrsCache)) {
                                $ifaceDescrs = file_get_contents($oltInterfaceDescrsCache);
                                $ifaceDescrs = unserialize($ifaceDescrs);
                            }

                            foreach ($interfaces as $eachMac => $eachInterface) {
                                $cleanInterface = strstr($eachInterface, ':', true);

                                if (isset($oltInterfacesFilled[$oltId][$cleanInterface])) {
                                    $oltInterfacesFilled[$oltId][$cleanInterface] ++;
                                } else {
                                    $oltInterfacesFilled[$oltId][$cleanInterface] = 1;
                                }

                                if (isset($signals[$eachMac])) {
                                    $macSignal = $signals[$eachMac];
                                    if ((($macSignal > -27) and ( $macSignal < -25))) {
                                        if (isset($avgSignals[$oltId][$cleanInterface])) {
                                            $avgSignals[$oltId][$cleanInterface] ++;
                                        } else {
                                            $avgSignals[$oltId][$cleanInterface] = 1;
                                        }
                                    }
                                    if ((($macSignal > 0) or ( $macSignal < -27))) {
                                        if (isset($badSignals[$oltId][$cleanInterface])) {
                                            $badSignals[$oltId][$cleanInterface] ++;
                                        } else {
                                            $badSignals[$oltId][$cleanInterface] = 1;
                                        }
                                    }
                                }

                                //storing PON ifaces descriptions, if not stored yet
                                if (!isset($oltInterfaceDescrs[$oltId][$cleanInterface])
                                        and ! empty($ifaceDescrs) and ! empty($ifaceDescrs[$cleanInterface])) {

                                    $oltInterfaceDescrs[$oltId][$cleanInterface] = ' | ' . $ifaceDescrs[$cleanInterface];
                                }
                            }
                        }
                    }
                }
            }
        }

        if ((!empty($oltInterfacesFilled)) and ( !empty($oltOnuFilled))) {
            foreach ($oltOnuFilled as $oltId => $oltFilledPercent) {
                $result .= wf_tag('h3');
                $result .= $this->allOltDevices[$oltId] . ' ' . __('filled on') . ' ' . $oltFilledPercent . '%';
                $result .= ' (' . $oltOnuCounters[$oltId] . ' ' . __('ONU') . ' ' . __('Registered') . ')';
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

                        $oltIfaceDescr = ($this->showPONIfaceDescrStatsTab and ! empty($oltInterfaceDescrs[$oltId][$eachInterface])) ? $oltInterfaceDescrs[$oltId][$eachInterface] : '';

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
                            $controllerUrl = self::URL_ME . '&oltstats=true&oltid=' . $oltId . '&if=' . $eachInterface;
                            $ponIfDescr = $this->ponInterfaces->getDescription($oltId, $eachInterface);
                            if (!empty($ponIfDescr)) {
                                $ponIfDescr = ' ' . $ponIfDescr;
                            }
                            $eachInterfaceLabel = wf_Link($controllerUrl, $eachInterface) . $ponIfDescr;
                        }

                        $cells = wf_TableCell($eachInterfaceLabel . $oltIfaceDescr);
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
                    if (file_exists(self::UPTIME_PATH . $oltId . '_' . self::UPTIME_EXT)) {
                        $oltUptime = file_get_contents(self::UPTIME_PATH . $oltId . '_' . self::UPTIME_EXT);
                        $result .= __('Uptime') . ': ' . $oltUptime;
                    }
                    //getting temperature
                    if (file_exists(self::TEMPERATURE_PATH . $oltId . '_' . self::TEMPERATURE_EXT)) {
                        $oltTemperature = file_get_contents(self::TEMPERATURE_PATH . $oltId . '_' . self::TEMPERATURE_EXT);
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
                    $result .= wf_tag('script', false, '', 'type="text/javascript" src="https://www.gstatic.com/charts/loader.js"') . wf_tag('script', true);
                    foreach ($oltsTemps as $oltTempId => $oltTempValue) {
                        $result .= $this->renderTemperature($oltTempValue, $this->allOltDevices[$oltTempId]);
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
                    $cells = wf_TableCell(__('OLT'));
                    $cells .= wf_TableCell(__('from'));
                    $cells .= wf_TableCell(__('to'));
                    $cells .= wf_TableCell(__('time'));
                    $rows = wf_TableRow($cells, 'row1');
                    foreach ($this->allOltDevices as $oltId => $eachDevice) {
                        $pollStatsPath = self::POLL_STATS . $oltId;
                        if (file_exists($pollStatsPath)) {
                            $pollStatsRaw = file_get_contents($pollStatsPath);
                            if (!empty($pollStatsRaw)) {
                                $pollStats = unserialize($pollStatsRaw);
                                $devPollTime = $pollStats['end'] - $pollStats['start'];
                                $totalTime += $devPollTime;
                                $cells = wf_TableCell($eachDevice);
                                $cells .= wf_TableCell(date("Y-m-d H:i:s", $pollStats['start']));
                                $cells .= wf_TableCell(date("Y-m-d H:i:s", $pollStats['end']));
                                $cells .= wf_TableCell(zb_formatTime($devPollTime));
                                $rows .= wf_TableRow($cells, 'row5');
                            }
                        }
                    }

                    $result = $statsControls;
                    $result .= wf_tag('h3') . __('SNMP query') . wf_tag('h3', true);
                    $result .= wf_TableBody($rows, '100%', 0, '');
                    $result .= __('Total') . ' ' . __('time') . ': ' . zb_formatTime($totalTime);
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
     * Renders temperature gauge
     *
     * @param float $temperature
     * @param string $title
     *
     * @return string
     */
    protected function renderTemperature($temperature, $title = '') {
        $result = '';
        $gaugeId = wf_InputId();

        $containerStyle = 'width: 300px; height: 300px; float:left; ';
        $result .= wf_tag('div', false, '', 'style="' . $containerStyle . '"');
        $result .= wf_tag('div', false, '', 'id="temperature_div' . $gaugeId . '"');
        $result .= wf_tag('div', true);
        $result .= wf_tag('center') . wf_tag('b') . $title . wf_tag('b', true) . wf_tag('center', true);
        $result .= wf_tag('div', true);

        $result .= wf_tag('script');

        $result .= 'google.charts.load(\'current\', {\'packages\':[\'gauge\']});
          google.charts.setOnLoadCallback(drawChart);

          function drawChart() {

            var data = google.visualization.arrayToDataTable([
              [\'Label\', \'Value\'],
              [\'°C\', ' . $temperature . ']

            ]);

            var options = {
              max: 100,
              min: 0,
              width: 280, height: 280,
              greenFrom: 10, greenTo: 60,
              yellowFrom:60, yellowTo: 70,
              redFrom: 70, redTo: 100,
              minorTicks: 5
            };

            var chart = new google.visualization.Gauge(document.getElementById(\'temperature_div' . $gaugeId . '\'));

            chart.draw(data, options);
        
          } ';
        $result .= wf_tag('script', true);

        return ($result);
    }

    /**
     * Renders unknown ONU list container
     *
     * @return string
     */
    public function renderUnknownOnuList() {
        $result = '';
        $columns = array('OLT', 'Login', 'Address', 'Real Name', 'Tariff', 'IP', 'MAC', 'Actions');
        $opts = '"order": [[ 0, "desc" ]]';
        $result = wf_JqDtLoader($columns, self::URL_ME . '&ajaxunknownonu=true', false, 'ONU', 100, $opts);
        return ($result);
    }

    /**
     * Returns current FDB cache list container with controls
     *
     * @return string
     */
    public function renderOnuFdbCache() {
        $result = wf_BackLink(self::URL_ME);
        $result .= wf_delimiter();
        $columns = array('OLT', 'ONU', 'ID', 'Vlan', 'MAC', 'Address', 'Login', 'Real Name', 'Tariff');
        $opts = '"order": [[ 0, "desc" ]]';
        $result .= wf_JqDtLoader($columns, self::URL_ME . '&fdbcachelist=true&ajaxfdblist=true', false, 'ONU', 100, $opts);
        return ($result);
    }

    /**
     * Renders OLT FDB list container
     *
     * @return string
     */
    public function renderOltFdbList($onuid = '') {
        $result = '';
        $columns = array('ID', 'Vlan', 'MAC', 'Address', 'Real Name', 'Tariff');
        $opts = '"order": [[ 0, "desc" ]]';
        $result = wf_JqDtLoader($columns, self::URL_ME . '&ajaxoltfdb=true&onuid=' . $onuid . '', false, 'ONU', 100, $opts);
        return ($result);
    }

    /**
     * Just generates random MAC address to replace invalid ONU MAC
     *
     * @return string
     */
    protected function getRandomMac() {
        $result = 'ff:' . '00' . ':' . rand(10, 99) . ':' . rand(10, 99) . ':' . rand(10, 99) . ':' . '00';
        return ($result);
    }

    /**
     * Loads existing signal cache from FS
     *
     * @return void
     */
    protected function loadSignalsCache() {
        $availCacheData = rcms_scandir(self::SIGCACHE_PATH, '*_' . self::SIGCACHE_EXT);
        if (!empty($availCacheData)) {
            foreach ($availCacheData as $io => $each) {
                $raw = file_get_contents(self::SIGCACHE_PATH . $each);
                $raw = unserialize($raw);
                foreach ($raw as $mac => $signal) {
                    if ($this->validateONUMACEnabled and ! $this->validateONUMAC($mac)) {
                        if ($this->replaceInvalidONUMACWithRandom) {
                            $macRandom = $this->getRandomMac();
                            $this->signalCache[$macRandom] = $signal;
                        }

                        continue;
                    }

                    $this->signalCache[$mac] = $signal;
                }
            }
        }
    }

    /**
     * Loads ONU distance cache
     *
     * @return void
     */
    protected function loadDistanceCache() {
        $availCacheData = rcms_scandir(self::DISTCACHE_PATH, '*_' . self::DISTCACHE_EXT);
        if (!empty($availCacheData)) {
            foreach ($availCacheData as $io => $each) {
                $raw = file_get_contents(self::DISTCACHE_PATH . $each);
                $raw = unserialize($raw);
                foreach ($raw as $mac => $distance) {
                    if ($this->validateONUMACEnabled and ! $this->validateONUMAC($mac)) {
                        if ($this->replaceInvalidONUMACWithRandom) {
                            $macRandom = $this->getRandomMac();
                            $this->distanceCache[$macRandom] = $distance;
                        }

                        continue;
                    }

                    $this->distanceCache[$mac] = $distance;
                }
            }
        }
    }

    /**
     * Loads ONU last dereg reasons cache
     *
     * @return void
     */
    protected function loadLastDeregCache() {
        $availCacheData = rcms_scandir(self::DEREGCACHE_PATH, '*_' . self::DEREGCACHE_EXT);
        if (!empty($availCacheData)) {
            foreach ($availCacheData as $io => $each) {
                $raw = file_get_contents(self::DEREGCACHE_PATH . $each);
                $raw = unserialize($raw);
                foreach ($raw as $mac => $dereg) {
                    if ($this->validateONUMACEnabled and ! $this->validateONUMAC($mac)) {
                        if ($this->replaceInvalidONUMACWithRandom) {
                            $macRandom = $this->getRandomMac();
                            $this->lastDeregCache[$macRandom] = $dereg;
                        }

                        continue;
                    }

                    $this->lastDeregCache[$mac] = $dereg;
                }
            }
        }
    }

    /**
     * Loads ONU interface cache
     *
     * @return void
     */
    protected function loadInterfaceCache() {
        $availCacheData = rcms_scandir(self::INTCACHE_PATH, '*_' . self::INTCACHE_EXT);
        if (!empty($availCacheData)) {
            foreach ($availCacheData as $io => $each) {
                $raw = file_get_contents(self::INTCACHE_PATH . $each);
                $raw = unserialize($raw);
                foreach ($raw as $mac => $interface) {
                    if ($this->validateONUMACEnabled and ! $this->validateONUMAC($mac)) {
                        if ($this->replaceInvalidONUMACWithRandom) {
                            $macRandom = $this->getRandomMac();
                            $this->interfaceCache[$macRandom] = $interface;
                        }

                        continue;
                    }

                    $this->interfaceCache[$mac] = $interface;
                }
            }
        }
    }

    /**
     * Loads available OLTs PON interfaces descriptions
     *
     * @return void
     */
    protected function loadPONIfaceDescrCache() {
        $availCacheData = rcms_scandir(self::INTCACHE_PATH, '*_' . self::INTDESCRCACHE_EXT);

        if (!empty($availCacheData)) {
            foreach ($availCacheData as $io => $each) {
                $tmpArr = array();
                $oltID = strstr($each, '_', true);
                $raw = file_get_contents(self::INTCACHE_PATH . $each);
                $raw = unserialize($raw);

                foreach ($raw as $interface => $ifDescr) {
                    $tmpArr[$interface] = $ifDescr;
                }

                $this->ponIfaceDescrCache[$oltID] = $tmpArr;
            }
        }
    }

    /**
     * Loads OLT FDB cache
     *
     * @return void
     */
    protected function loadFDBCache() {
        $availCacheData = rcms_scandir(self::FDBCACHE_PATH, '*_' . self::FDBCACHE_EXT);
        if (!empty($availCacheData)) {
            foreach ($availCacheData as $io => $each) {
                $raw = file_get_contents(self::FDBCACHE_PATH . $each);
                $raw = unserialize($raw);
                foreach ($raw as $oidMac => $FDB) {
                    if ($this->validateONUMACEnabled and ! $this->validateONUMAC($oidMac)) {
                        if ($this->replaceInvalidONUMACWithRandom) {
                            $macRandom = $this->getRandomMac();
                            $this->FDBCache[$macRandom] = $FDB;
                        }

                        continue;
                    }

                    $this->FDBCache[$oidMac] = $FDB;
                }
            }
        }
    }

    /**
     * Loads ONU MAC cache
     *
     * @return void
     */
    protected function loadONUMACDevIDCache() {
        $availCacheData = rcms_scandir(self::MACDEVIDCACHE_PATH, '*_' . self::MACDEVIDCACHE_EXT);
        if (!empty($availCacheData)) {
            foreach ($availCacheData as $io => $each) {
                $raw = file_get_contents(self::MACDEVIDCACHE_PATH . $each);
                $raw = unserialize($raw);
                foreach ($raw as $mac => $devID) {
                    if ($this->validateONUMACEnabled and ! $this->validateONUMAC($mac)) {
                        if ($this->replaceInvalidONUMACWithRandom) {
                            $macRandom = $this->getRandomMac();
                            $this->interfaceCache[$macRandom] = $devID;
                        }

                        continue;
                    }

                    $this->onuMACDevIDCache[$mac] = $devID;
                }
            }
        }
    }

    /**
     * Fills onuIndexCache array
     *
     * @return void
     */
    protected function fillONUIndexCache() {
        $availCacheData = rcms_scandir(self::ONUCACHE_PATH, '*_' . self::ONUCACHE_EXT);

        if (!empty($availCacheData)) {
            foreach ($availCacheData as $io => $each) {
                $raw = file_get_contents(self::ONUCACHE_PATH . $each);
                $raw = unserialize($raw);
                $oltId = explode('_', $each);
                $oltId = @vf($oltId[0], 3);
                foreach ($raw as $index => $mac) {
                    if ($this->validateONUMACEnabled and ! $this->validateONUMAC($mac)) {
                        if ($this->replaceInvalidONUMACWithRandom) {
                            $macRandom = $this->getRandomMac();
                            $this->onuIndexCache[$macRandom] = $oltId;
                        }

                        continue;
                    }

                    $this->onuIndexCache[$mac] = $oltId;
                }
            }
        }
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
                if ($this->checkMacUnique($onuMac)) {
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

            foreach ($this->onuIndexCache as $onuMac => $oltId) {
//not registered?
                if ($this->checkMacUnique($onuMac)) {
                    $login = in_array($onuMac, array_map('strtolower', $allUsermacs)) ? array_search($onuMac, array_map('strtolower', $allUsermacs)) : '';
                    $userLink = $login ? wf_Link('?module=userprofile&username=' . $login, web_profile_icon() . ' ' . @$allUserData[$login]['login'] . '', false) : '';
                    $userLogin = $login ? @$allUserData[$login]['login'] : '';
                    $userRealnames = $login ? @$allUserData[$login]['realname'] : '';
                    $userTariff = $login ? @$allUserData[$login]['Tariff'] : '';
                    $userIP = $login ? @$allUserData[$login]['ip'] : '';
                    $LnkID = wf_InputId();

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
                        $data[] = $oltData;
                        $data[] = $userLink;
                        $data[] = @$allUserData[$login]['fulladress'];
                        $data[] = $userRealnames;
                        $data[] = $userTariff;
                        $data[] = $userIP;
                        $data[] = $onuMac;
                        $data[] = $actControls;

                        $json->addRow($data);
                        unset($data);
                    }
                }
            }
        }

        $json->getJson();
    }

    /**
     * Renders json formatted data for jquery data tables list
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

        if ($this->altCfg['ADCOMMENTS_ENABLED']) {
            $adcomments = new ADcomments('PONONU');
            $adc = true;
        } else {
            $adc = false;
        }

        $this->loadSignalsCache();

        $distCacheAvail = rcms_scandir(self::DISTCACHE_PATH, '*_' . self::DISTCACHE_EXT);
        if (!empty($distCacheAvail)) {
            $distCacheAvail = true;
            $this->loadDistanceCache();
        } else {
            $distCacheAvail = false;
        }

        $intCacheAvail = rcms_scandir(self::INTCACHE_PATH, '*_' . self::INTCACHE_EXT);
        if (!empty($intCacheAvail)) {
            $intCacheAvail = true;
            $this->loadInterfaceCache();
        } else {
            $intCacheAvail = false;
        }

        $intDescrCacheAvail = rcms_scandir(self::INTCACHE_PATH, '*_' . self::INTDESCRCACHE_EXT);
        $curOLTIfaceDescrs = array();
        if (!empty($intDescrCacheAvail)) {
            $this->loadPONIfaceDescrCache();

            if (!empty($this->ponIfaceDescrCache[$OltId])) {
                $intDescrCacheAvail = true;
                $curOLTIfaceDescrs = $this->ponIfaceDescrCache[$OltId];
            } else {
                $intDescrCacheAvail = false;
            }
        } else {
            $intDescrCacheAvail = false;
        }

        $lastDeregCacheAvail = rcms_scandir(self::DEREGCACHE_PATH, '*_' . self::DEREGCACHE_EXT);
        if (!empty($lastDeregCacheAvail)) {
            $lastDeregCacheAvail = true;
            $this->loadLastDeregCache();
        } else {
            $lastDeregCacheAvail = false;
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
                        if (($signal > 0) or ( $signal < -27)) {
                            $sigColor = self::COLOR_BAD;
                        } elseif ($signal > -27 and $signal < -25) {
                            $sigColor = self::COLOR_AVG;
                        } else {
                            $sigColor = self::COLOR_OK;
                        }

                        if ($signal == self::NO_SIGNAL) {
                            $ONUIsOffline = true;
                            $signal = __('No');
                            $sigColor = self::COLOR_NOSIG;
                        }
                    } elseif (isset($this->signalCache[$each['serial']])) {
                        $signal = $this->signalCache[$each['serial']];
                        if (($signal > 0) or ( $signal < -27)) {
                            $sigColor = self::COLOR_BAD;
                        } elseif ($signal > -27 and $signal < -25) {
                            $sigColor = self::COLOR_AVG;
                        } else {
                            $sigColor = self::COLOR_OK;
                        }

                        if ($signal == self::NO_SIGNAL) {
                            $ONUIsOffline = true;
                            $signal = __('No');
                            $sigColor = self::COLOR_NOSIG;
                        }
                    } else {
                        $ONUIsOffline = true;
                        $signal = __('No');
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
                        $oltIfaceDescr = ($this->showPONIfaceDescrMainTab and $intDescrCacheAvail and ! empty($curOLTIfaceDescrs[$cleanInterface])) ? $curOLTIfaceDescrs[$cleanInterface] . ' | ' : '';
                        $data[] = $oltIfaceDescr . $ponInterface;
                    }

                    $data[] = $this->getModelName($each['onumodelid']);
                    $data[] = $each['ip'];
                    $data[] = $each['mac'];
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

        $json->getJson();
    }

    /**
     * Renders json formatted data for jquery data tables list
     *
     * @param string $OnuId
     * @return void
     */
    public function ajaxOltFdbData($OnuId) {
        $json = new wf_JqDtHelper();
        $fdbPointer = '';
        if (!empty($OnuId)) {
            $allUserTariffs = zb_TariffsGetAllUsers();
            $onuMacId = @$this->allOnu[$OnuId]['mac'];
            $onuSerialId = @$this->allOnu[$OnuId]['serial'];
            $fdbCacheAvail = rcms_scandir(self::FDBCACHE_PATH, '*_' . self::FDBCACHE_EXT);
            if (!empty($fdbCacheAvail)) {
                $fdbCacheAvail = true;
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
            if ($fdbCacheAvail and $fdbPointer) {
                $GetLoginMac = zb_UserGetAllMACs();
                $allAddress = zb_AddressGetFulladdresslistCached();
                $allRealnames = zb_UserGetAllRealnames();

                foreach ($fdbPointer as $id => $FDBdata) {
                    $login = in_array($FDBdata['mac'], array_map('strtolower', $GetLoginMac)) ? array_search($FDBdata['mac'], array_map('strtolower', $GetLoginMac)) : '';
                    $userLink = $login ? wf_Link('?module=userprofile&username=' . $login, web_profile_icon() . ' ' . @$allAddress[$login], false) : '';
                    $userRealnames = $login ? @$allRealnames[$login] : '';
                    $userTariff = (isset($allUserTariffs[$login])) ? $allUserTariffs[$login] : '';

                    $data[] = $id;
                    $data[] = $FDBdata['vlan'];
                    $data[] = $FDBdata['mac'];
                    $data[] = @$userLink;
                    $data[] = @$userRealnames;
                    $data[] = $userTariff;

                    $json->addRow($data);
                    unset($data);
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
        $availOnuFdbCache = rcms_scandir(self::FDBCACHE_PATH, '*_' . self::FDBCACHE_EXT);
        if (!empty($availOnuFdbCache)) {
            $allAddress = zb_AddressGetFulladdresslistCached();
            $allRealnames = zb_UserGetAllRealnames();
            $allUserMac = zb_UserGetAllMACs();
            $allUserMac = array_map('strtolower', $allUserMac);
            $allUserMac = array_flip($allUserMac);
            $allUserTariffs = zb_TariffsGetAllUsers();

            foreach ($availOnuFdbCache as $io => $eachFile) {
                $oltId = explode('_', $eachFile);
                $oltId = $oltId[0];
                $oltDesc = @$this->allOltDevices[$oltId];
                $fileData = file_get_contents(self::FDBCACHE_PATH . '/' . $eachFile);
                if (!empty($fileData)) {
                    $fileData = unserialize($fileData);
                    if (!empty($fileData)) {
                        foreach ($fileData as $onuMac => $onuTmp) {
                            if (!empty($onuTmp)) {
                                foreach ($onuTmp as $id => $onuData) {
                                    $onuRealId = $this->getONUIDByMAC($onuMac);
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
     * Returns array like: $userLogin => $onuSignal
     *
     * @return array
     */
    public static function getAllONUSignals() {
        global $ubillingConfig;
        $allOnuSignals = array();
        $signalCache = array();
        $onuMACValidateRegex = '/^([[:xdigit:]]{2}[\s:.-]?){5}[[:xdigit:]]{2}$/';
        $validateONUMACEnabled = $ubillingConfig->getAlterParam('PON_ONU_MAC_VALIDATE');
        $availCacheData = rcms_scandir(self::SIGCACHE_PATH, '*_' . self::SIGCACHE_EXT);

        $query = "SELECT * from `pononu` WHERE `login` != '' and NOT ISNULL(`login`)";
        $allOnuRecs = simple_queryall($query);

        if (!empty($allOnuRecs) and ! empty($availCacheData)) {
            foreach ($availCacheData as $io => $each) {
                $raw = file_get_contents(self::SIGCACHE_PATH . $each);
                $raw = unserialize($raw);

                foreach ($raw as $mac => $signal) {
                    if ($validateONUMACEnabled) {
                        $matches = array();
                        preg_match($onuMACValidateRegex, $mac, $matches);

                        if (empty($matches[0])) {
                            continue;
                        }
                    }

                    $signalCache[$mac] = $signal;
                }
            }

            foreach ($allOnuRecs as $io => $each) {
                if (isset($signalCache[$each['mac']])) {
                    $allOnuSignals[$each['login']] = $signalCache[$each['mac']];
                }
                if (isset($signalCache[$each['serial']])) {
                    $allOnuSignals[$each['login']] = $signalCache[$each['serial']];
                }
            }
        }

        return ($allOnuSignals);
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

            $result .= wf_Form('', 'POST', $inputs, 'glamour');
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
                        $rawMac = str_replace(':', '', $eachOnuData['mac']);
                        if (ispos($eachOnuData['mac'], $searchQuery) or ispos($rawMac, $searchQuery)) {
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
            $cells .= wf_TableCell(__('IP'));
            $cells .= wf_TableCell(__('Serial number'));
            $cells .= wf_TableCell(__('MAC'));
            $cells .= wf_TableCell(__('User'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');

            foreach ($onuArray as $eachOnuId => $eachOnuData) {
                $cells = wf_TableCell($eachOnuId);
                $cells .= wf_TableCell(@$this->allOltNames[$eachOnuData['oltid']]);
                $cells .= wf_TableCell(@$this->allModelsData[$eachOnuData['onumodelid']]['modelname']);
                $cells .= wf_TableCell($eachOnuData['ip']);
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

            if (isset($this->snmpTemplates[$oltModelId])
                    and isset($this->snmpTemplates[$oltModelId]['signal'])
                    and isset($this->snmpTemplates[$oltModelId]['misc'])
                    and file_exists(self::MACDEVIDCACHE_PATH . $oltID . '_' . self::MACDEVIDCACHE_EXT)) {

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
                        if ($getTxSgnal) {
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

                                if (empty($signal) or ! is_numeric($signal) or $signal == $snmpSignalOIDs['DOWNVALUE']) {
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

                                if (empty($signal) or ! is_numeric($signal) or $signal == $snmpSignalOIDs['DOWNVALUE']) {
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

            if (isset($this->snmpTemplates[$oltModelId])
                    and isset($this->snmpTemplates[$oltModelId]['signal'])
                    and isset($this->snmpTemplates[$oltModelId]['misc'])
                    and file_exists(self::MACDEVIDCACHE_PATH . $oltID . '_' . self::MACDEVIDCACHE_EXT)) {

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

                    if (!empty($lastRegTime) or ! empty($lastDeregTime) or ! empty($lastAliveTime)) {
                        if ($snmpSignalOIDs['SIGNALMODE'] == 'BDCOM') {
                            /* if (ispos($snmpDevice, '3310')) {
                              $lastAliveTime = explode(')', $lastAliveTime);
                              $lastAliveTime = trim($lastAliveTime[1]);
                              } else {
                              $lastAliveTime = zb_formatTime($lastAliveTime);
                              } */

                            $lastAliveTime = (empty($lastAliveTime) or ! is_numeric($lastAliveTime)) ? 0 : $lastAliveTime;
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

}

/**
 * Ponizer legacy all-in-one-page renderer
 */
class PONizerLegacy extends PONizer {

    protected $json = '';

    public function __construct() {
        parent::__construct();
        $this->json = new wf_JqDtHelper();
    }

    /**
     * Renders json formatted data for jquery data tables list
     *
     * @return void
     */
    public function ajaxOnuData($OltId = '') {

        foreach ($this->allOltDevices as $OltId => $eachOltData) {


            $OnuByOLT = $this->getOnuArrayByOltID($OltId);

            $allRealnames = zb_UserGetAllRealnames();
            $allAddress = zb_AddressGetFulladdresslistCached();
            $allTariffs = zb_TariffsGetAllUsers();

            if ($this->altCfg['ADCOMMENTS_ENABLED']) {
                $adcomments = new ADcomments('PONONU');
                $adc = true;
            } else {
                $adc = false;
            }

            $this->loadSignalsCache();

            $distCacheAvail = rcms_scandir(self::DISTCACHE_PATH, '*_' . self::DISTCACHE_EXT);
            if (!empty($distCacheAvail)) {
                $distCacheAvail = true;
                $this->loadDistanceCache();
            } else {
                $distCacheAvail = false;
            }

            $intCacheAvail = rcms_scandir(self::INTCACHE_PATH, '*_' . self::INTCACHE_EXT);
            if (!empty($intCacheAvail)) {
                $intCacheAvail = true;
                $this->loadInterfaceCache();
            } else {
                $intCacheAvail = false;
            }

            $lastDeregCacheAvail = rcms_scandir(self::DEREGCACHE_PATH, '*_' . self::DEREGCACHE_EXT);
            if (!empty($lastDeregCacheAvail)) {
                $lastDeregCacheAvail = true;
                $this->loadLastDeregCache();
            } else {
                $lastDeregCacheAvail = false;
            }

            if (!empty($OnuByOLT)) {
                foreach ($OnuByOLT as $io => $each) {
                    $userTariff = '';
                    $ONUIsOffline = false;

                    if (!empty($each['login'])) {
                        $userLogin = trim($each['login']);
                        $userLink = wf_Link('?module=userprofile&username=' . $userLogin, web_profile_icon() . ' ' . @$allAddress[$userLogin], false);
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
                        if (($signal > 0) OR ( $signal < -25)) {
                            $sigColor = self::COLOR_BAD;
                        } else {
                            $sigColor = self::COLOR_OK;
                        }

                        if ($signal == self::NO_SIGNAL) {
                            $ONUIsOffline = true;
                            $signal = __('No');
                            $sigColor = self::COLOR_NOSIG;
                        }
                    } elseif (isset($this->signalCache[$each['serial']])) {
                        $signal = $this->signalCache[$each['serial']];
                        if (($signal > 0) OR ( $signal < -25)) {
                            $sigColor = self::COLOR_BAD;
                        } else {
                            $sigColor = self::COLOR_OK;
                        }

                        if ($signal == self::NO_SIGNAL) {
                            $ONUIsOffline = true;
                            $signal = __('No');
                            $sigColor = self::COLOR_NOSIG;
                        }
                    } else {
                        $ONUIsOffline = true;
                        $signal = __('No');
                        $sigColor = self::COLOR_NOSIG;
                    }

                    $data[] = $each['id'];
                    if ($this->altCfg['PONIZER_LEGACY_VIEW'] == 2) {
                        $data[] = $this->allOltNames[$each['oltid']];
                    }
                    if ($intCacheAvail) {
                        $data[] = @$this->interfaceCache[$each['mac']];
                    }
                    $data[] = $this->getModelName($each['onumodelid']);
                    $data[] = $each['ip'];
                    $data[] = $each['mac'];
                    $data[] = wf_tag('font', false, '', 'color=' . $sigColor . '') . $signal . wf_tag('font', true);

                    if ($distCacheAvail) {
                        if (isset($this->distanceCache[$each['mac']])) {
                            $data[] = @$this->distanceCache[$each['mac']];
                        } else {
                            $data[] = @$this->distanceCache[$each['serial']];
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

                    $this->json->addRow($data);
                    unset($data);
                }
            }
        }
        $this->json->getJson();
    }

    /**
     * Renders available ONU JQDT list container
     *
     * @return string
     */
    public function renderOnuList() {
        $distCacheAvail = rcms_scandir(self::DISTCACHE_PATH, '*_' . self::DISTCACHE_EXT);
        $intCacheAvail = rcms_scandir(self::INTCACHE_PATH, '*_' . self::INTCACHE_EXT);
        $lastDeregCacheAvail = rcms_scandir(self::DEREGCACHE_PATH, '*_' . self::DEREGCACHE_EXT);

        $distCacheAvail = !empty($distCacheAvail) ? true : false;
        $intCacheAvail = !empty($intCacheAvail) ? true : false;
        $lastDeregCacheAvail = !empty($lastDeregCacheAvail) ? true : false;
        $oltOnuCounters = $this->getOltOnuCounts();

        $columns = array('ID');
        if (@$this->altCfg['PONIZER_LEGACY_VIEW'] == 2) {
            $columns[] = __('OLT');
        }

        if ($intCacheAvail) {
            $columns[] = __('Interface');
        }

        $columns[] = 'Model';
        if (@$this->altCfg['PON_ONUIPASIF']) {
            $columns[] = 'Interface';
        } else {
            $columns[] = 'IP';
        }
        $columns[] = 'MAC';
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

        $opts = '"order": [[ 0, "desc" ]]';

        $result = '';

        $AjaxURLStr = '' . self::URL_ME . '&ajaxonu=true&legacyView=true';

        $result .= show_window('', wf_JqDtLoader($columns, $AjaxURLStr, false, 'ONU', 100, $opts));
        return ($result);
    }

}

?>
