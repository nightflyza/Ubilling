<?php

/**
 * Class for registering ONU/ONT on ZTE OLTs.
 */
class OnuRegister {

    CONST MODULE_URL = '?module=ztevlanbinds';
    CONST MODULE_URL_EDIT_CARD = '?module=ztevlanbinds&edit_card=';
    CONST MODULE_URL_EDIT_BIND = '?module=ztevlanbinds&edit_bind=';
    CONST MODULE_CONFIG = 'ONUREG_ZTE';
    CONST MODULE_RIGHTS = 'ZTEVLANBINDS';
    CONST UNREG_URL = '?module=zteunreg';
    CONST UNREG_ACT_URL = '?module=zteunreg&register=true&oltip=';
    CONST CARDS_TABLE = 'zte_cards';
    CONST BIND_TABLE = 'zte_vlan_bind';
    CONST PORT_ID_START = 268501248;
    CONST ONU_ID_START = 805830912;
    CONST ALT_ONU_ID_START = 2416967936;
    CONST GPON_RETRIES = 5;
    CONST SNMP_TEMPLATE_SECTION = 'onu_reg';
    CONST EMPTY_FIELD = '';
    CONST TYPE_FIELD = 'type';
    CONST INTERFACE_FIELD = 'interface';
    CONST OLTIP_FIELD = 'oltip';
    CONST OLTID_FIELD = 'swid';
    CONST MODELID_FIELD = 'modelid';
    CONST MODELID_PLACEHOLDER = '======';
    CONST VLAN_FIELD = 'vlan';
    CONST MACONU_FIELD = 'maconu';
    CONST MAC_ONU_FIELD = 'mac_onu';
    CONST SERIAL_FIELD = 'serial';
    CONST SN_FIELD = 'sn';
    CONST LOGIN_FIELD = 'login';
    CONST MAC_FIELD = 'mac';
    CONST RANDOM_MAC_FIELD = 'random_mac';
    CONST ROUTER_FIELD = 'router';
    CONST SAVE_FIELD = 'save';
    CONST PONIZER_ADD_FIELD = 'ponizer_add';
    CONST GET_UNIVERSALQINQ_NONE = 'none';
    CONST GET_UNIVERSALQINQ_CVLAN = 'cvlan';
    CONST GET_UNIVERSALQINQ_PAIR = 'pair';
    CONST GET_UNIVERSALQINQ_CVLAN_POOL = 'cvlan_pool';
    CONST GET_UNIVERSALQINQ_PAIR_POOL = 'pair_pool';
    CONST GET_UNIVERSALQINQ = 'use_qinq';
    CONST NO_ERROR_CONNECTION = 'OK';
    CONST ERROR_NO_LOGIN_AVAILABLE = 'No connection data found. Switchlogin is empty or not set.';
    CONST ERROR_SNMP_CONNECTION_SET = 'SNMP connection type has set for this OLT. Use telnet/ssh instead.';
    CONST ERROR_NO_LICENSE = 'No license key available';
    CONST ERROR_NO_RIGHTS = 'Access denied';
    CONST ERROR_NOT_ENABLED = 'This module is disabled';
    CONST ERROR_WRONG_MODELID = 'Wrong modelid found. Do not use placeholder.';
    CONST ERROR_NOT_ALL_FIELDS = 'Some fields were not set.';
    CONST ERROR_NO_INTERFACE_SET = 'No interface value found.';
    CONST ERROR_NO_OLTIP_SET = 'No OLT IP address value found.';
    CONST ERROR_NO_VLAN_SET = 'No VLAN value found.';
    CONST ERROR_TOO_MANY_REGISTERED_ONU = 'Registered ONU count is';

    /**
     * Contains all data from billing.ini
     * 
     * @var type 
     */
    protected $billingCfg = array();

    /**
     * Contains all ZTE OLT devices.
     * 
     * @var array
     */
    protected $allZteOlt = array();

    /**
     * Contains all HUAWEI OLT devices.
     * 
     * @var array
     */
    protected $allHuaweiOlt = array();

    /**
     * Contains all switches login and passwords.
     * 
     * @var array
     */
    protected $allSwLogin = array();

    /**
     * Contains all data from zte_cards table.
     */
    protected $allCards = array();

    /**
     * Contains all data from zte_vlan_binds table.
     * 
     * @var array
     */
    protected $allBinds = array();

    /**
     * Array for cards selector.
     * 
     * @var array
     */
    protected $cardSelector = array();

    /**
     * Array for ports selector.
     * 
     * @var array
     */
    protected $portSelector = array();

    /**
     * Array for pon port snmp counter.
     * 
     * @var array
     */
    protected $ponArray = array();

    /**
     * Array for ONU snmp counter.
     * 
     * @var array
     */
    protected $onuArray = array();

    /**
     * Alternative array for ONU snmp counter.
     * 
     * @var array
     */
    protected $onuArrayAlt = array();

    /**
     * Contains all onu models
     * 
     * @var array
     */
    protected $onuModels = array();

    /**
     * Array for selecting onumodel;
     * 
     * @var array
     */
    protected $onuModelsSelector = array();

    /**
     * Array for checking ports count for EPON cards
     * 
     * @var array
     */
    protected $eponCards = array();

    /**
     * Array for checking ports count for GPON cards
     * 
     * @var array
     */
    protected $gponCards = array();

    /**
     * Greed placeholder
     *
     * @var object
     */
    protected $greed = '';

    /**
     * Avidity runtime placeholder
     *
     * @var array
     */
    protected $avidity = '';

    /**
     * System messages helper placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * SNMPHelper object instance
     *
     * @var array
     */
    protected $snmp = '';

    /**
     * Contains last registered onu id.
     * 
     * @var int
     */
    protected $lastOnuId = 1;

    /**
     * Placeholder for current OLT's SNMP template.
     * 
     * @var array
     */
    protected $currentSnmpTemplate = array();

    /**
     * Placeholder for current OLT's SNMP community.
     * 
     * @var string
     */
    protected $currentSnmpCommunity = '';

    /**
     * Placeholder for switch id for current OLT.
     * 
     * @var integer
     */
    public $currentOltSwId = '';

    /**
     * Placeholder for already registered ONU IDs.
     * 
     * @var array
     */
    public $existId = array();

    /**
     * Placeholder for current OLT's ip.
     * 
     * @var string
     */
    public $currentOltIp = '';

    /**
     * Placeholder for current OLT's PON interface;
     * 
     * @var string
     */
    public $currentOltInterface = '';

    /**
     * Placeholder for PON interface type. Can be EPON or GPON.
     * 
     * @var string
     */
    public $currentPonType = '';

    /**
     * Contains count of all service ports on HUAWEI OLT
     * 
     * @var integer
     */
    protected $servicePort = 1;

    /**
     * Placeholder for ONU identifier. Mac or Serial.
     * 
     * @var string
     */
    public $onuIdentifier = '';

    /**
     * Contains error message
     * 
     * @var string
     */
    public $error = '';

    /**
     * Contains ONU registration process result.
     * 
     * @var string
     */
    public $result = '';

    /**
     * Placeholder for ONU SVLAN.
     * 
     * @var string
     */
    public $svlan = '';

    /**
     * Placeholder for ONU VLAN.
     * 
     * @var string
     */
    public $vlan = '';

    /**
     * Contains all svlans
     * 
     * @var array
     */
    protected $allSvlan = array();

    /**
     * Contains all universal qinq bindings.
     * 
     * @var array
     */
    protected $usersQinQ = array();

    /**
     * Placeholder for ONU interface name.
     * 
     * @var string
     */
    public $onuInterface = '';

    /**
     * Save config after ONU registration?
     * 
     * @var bool
     */
    public $save = false;

    /**
     * Should ONU act like router?
     *
     * @var bool
     */
    public $router = false;

    /**
     * ONU model id
     * 
     * @var int
     */
    public $onuModel = '';

    /**
     * Users login for adding data to ponizer.
     * 
     * @var string
     */
    public $login = '';

    /**
     * Placeholder for Ponizer mac field.
     * 
     * @var string 
     */
    public $addMac = '';

    /**
     * Add ONU to Ponizer?
     * 
     * @var bool
     */
    public $ponizerAdd = false;

    /**
     * Should we use universal qinq and which type if yes?
     * 
     * @var string
     */
    public $useUniversalQINQ = '';

    /**
     * Contains all alter.ini options
     * 
     * @var array
     */
    protected $altCfg = array();

    /**
     * Instance of UniversalQINQ class.
     * 
     * @var object
     */
    protected $universalQinq;

    /**
     * Instance of VlanManagement class.
     * 
     * @var object
     */
    protected $vlanManagement;

    /**
     * Base class construction.
     * 
     * @return void
     */
    public function __construct() {
        $this->initGreed();
        $this->initMessages();
        $this->loadAllZteOlt();
        $this->loadAllHuaweiOlt();
        $this->loadAllSwLogin();
        $this->loadZteCards();
        $this->loadOnuModels();
        $this->loadConfig();
        $this->initSNMP();
        $this->loadOnu();
        snmp_set_oid_output_format(SNMP_OID_OUTPUT_NUMERIC);
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
        $this->eponCards = self::allEponCards();
        $this->gponCards = self::allGponCards();
        $this->universalQinq = new UniversalQINQ();
        $this->usersQinQ = $this->universalQinq->getAll();
        $this->vlanManagement = new VlanManagement();
        $this->allSvlan = $this->vlanManagement->getAllSvlan();
    }

//Load and init section.

    /**
     * Loads avaliable ONUs from database into private data property
     *
     * @return void
     */
    protected function loadOnu() {
        $query = 'SELECT * from `pononu`';
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allOnu[$each['id']] = $each;
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
     * data loader functions section
     * 
     * @return void
     */
    protected function loadConfig() {
        global $ubillingConfig;
        $this->billingCfg = $ubillingConfig->getBilling();
    }

    /**
     * Initializes system message helper object instance
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Setter for epon cards
     * 
     * @return array
     */
    public static function allEponCards() {
        return(array(
            'EPFC' => 4,
            'EPFCB' => 4,
            'ETGO' => 8,
            'ETGOD' => 8,
            'ETTO' => 8,
            'ETTOK' => 8,
            'ETGH' => 16,
            'ETGHG' => 16,
            'ETGHK' => 16
        ));
    }

    /**
     * Setter for gpon cards.
     * 
     * @return array
     */
    public static function allGponCards() {
        return(array(
            'GPFA' => 4,
            'GPFAE' => 4,
            'GTGO' => 8,
            'GTGH' => 16,
            'GTGHG' => 16,
            'GTGHK' => 16,
            'GPBD' => 8,
            'GPFD' => 16,
            'GPBH' => 8,
            'GPMD' => 8,
            'H806G' => 8,
            'H803G' => 16,
            'H805G' => 16
        ));
    }

    /**
     * Loads all onu models into onuModels.
     * 
     * @return void
     */
    protected function loadOnuModels() {
        $query = 'SELECT * FROM `switchmodels` WHERE `modelname` LIKE "%ONU%"';
        $data = simple_queryall($query);
        if (!empty($data)) {
            foreach ($data as $each) {
                $this->onuModels[$each['id']] = $each;
            }
        }
    }

    /**
     * Initializes greed mechanics
     * 
     * @return void
     */
    protected function initGreed() {
        $this->greed = new Avarice();
        $this->avidity = $this->greed->runtime('ZTE');
    }

    /**
     * Returns current runtime
     * 
     * @return array
     */
    public function getAvidity() {
        return ($this->avidity);
    }

    /**
     * Load all OLTs that contain 'ZTE' word in snmp template name from `switches` and `switchmodels`.
     * 
     * @return void
     */
    protected function loadAllZteOlt() {
        $query = 'SELECT `sw`.`id`,`sw`.`ip`,`sw`.`location`,`sw`.`snmp`,`sw`.`desc`,`model`.`snmptemplate` FROM `switches` AS `sw` JOIN `switchmodels` AS `model` ON (`sw`.`modelid` = `model`.`id`) WHERE `sw`.`desc` LIKE "%OLT%" AND `model`.`snmptemplate` LIKE "ZTE%"';
        $allOlt = simple_queryall($query);
        if (!empty($allOlt)) {
            foreach ($allOlt as $eachOlt) {
                $this->allZteOlt[$eachOlt['id']] = $eachOlt;
            }
        }
    }

    /**
     * Load all OLTs that contain 'ZTE' word in snmp template name from `switches` and `switchmodels`.
     * 
     * @return void
     */
    protected function loadAllHuaweiOlt() {
        $query = 'SELECT `sw`.`id`,`sw`.`ip`,`sw`.`location`,`sw`.`snmp`,`sw`.`desc`,`model`.`snmptemplate` FROM `switches` AS `sw` JOIN `switchmodels` AS `model` ON (`sw`.`modelid` = `model`.`id`) WHERE `sw`.`desc` LIKE "%OLT%" AND `model`.`snmptemplate` LIKE "Huawei-MA%"';
        $allOlt = simple_queryall($query);
        if (!empty($allOlt)) {
            foreach ($allOlt as $eachOlt) {
                $this->allHuaweiOlt[$eachOlt['id']] = $eachOlt;
            }
        }
    }

    /**
     * Load all data from `switch_login` table.
     * 
     * @return void
     */
    protected function loadAllSwLogin() {
        $query = 'SELECT * FROM `switch_login`';
        $allLogin = simple_queryall($query);
        if (!empty($allLogin)) {
            foreach ($allLogin as $eachLogin) {
                $this->allSwLogin[$eachLogin['swid']] = $eachLogin;
            }
        }
    }

    /**
     * Load all data from `zte_vlan_binds` table filtered by switch id.
     * 
     * @param int $swid
     * 
     * @return void
     */
    protected function loadZteBind($swid) {
        $swid = mysql_real_escape_string(strip_tags($swid));
        $query = 'SELECT * FROM `' . self::BIND_TABLE . '` WHERE `swid` = ' . $swid . ' GROUP BY `slot_number`, `port_number`';
        $data = simple_queryall($query);
        if (!empty($data)) {
            foreach ($data as $each) {
                $this->allBinds[$each['id']] = $each;
            }
        }
    }

    /**
     * Load all data from `zte_cards` table.
     * 
     * @return void
     */
    protected function loadZteCards() {
        $query = 'SELECT * FROM `' . self::CARDS_TABLE . '` ORDER BY `slot_number` ASC';
        $allCards = simple_queryall($query);
        if (!empty($allCards)) {
            foreach ($allCards as $eachCard) {
                $this->allCards[$eachCard['swid']][$eachCard['id']] = $eachCard;
            }
        }
    }

    /**
     * Loading all cards for specified OLT to array.
     * 
     * @param int $swid
     * 
     * @return void
     */
    protected function loadCardSelector($swid) {
        $this->cardSelector['======'] = '======';
        if (isset($this->allCards[$swid]) AND ! empty($this->allCards[$swid])) {
            foreach ($this->allCards[$swid] as $eachNumber => $eachCard) {
                if (isset($this->allZteOlt[$eachCard['swid']])) {
                    $this->cardSelector[$eachCard['slot_number']] = $this->allZteOlt[$eachCard['swid']]['ip'];
                    $this->cardSelector[$eachCard['slot_number']] .= ' | ' . $eachCard['slot_number'];
                    $this->cardSelector[$eachCard['slot_number']] .= ' | ' . $eachCard['card_name'];
                }
                if (isset($this->allHuaweiOlt[$eachCard['swid']])) {
                    $this->cardSelector[$eachCard['slot_number']] = $this->allHuaweiOlt[$eachCard['swid']]['ip'];
                    $this->cardSelector[$eachCard['slot_number']] .= ' | ' . $eachCard['slot_number'];
                    $this->cardSelector[$eachCard['slot_number']] .= ' | ' . $eachCard['card_name'];
                }
            }
        }
    }

    /**
     * Loading all available ports for specified card.
     * 
     * @param string $cardName
     * @param array $exclude
     * 
     * @return void
     */
    protected function loadPortSelector($cardName, $exclude = array()) {
        $count = 0;
        if (isset($this->eponCards[$cardName])) {
            $count = $this->eponCards[$cardName];
        } elseif (isset($this->gponCards[$cardName])) {
            $count = $this->gponCards[$cardName];
        }
        $this->portSelector['======'] = '======';
        for ($i = 1; $i <= $count; $i++) {
            $this->portSelector[$i] = $i;
        }
        if (!empty($exclude)) {
            $this->portSelector = array_diff($this->portSelector, $exclude);
        }
    }

    /**
     * Load all onu models.
     * 
     * @return void
     */
    protected function loadOnuModelSelector() {
        $this->onuModelsSelector['======'] = '======';
        if (!empty($this->onuModels)) {
            foreach ($this->onuModels as $id => $eachModel) {
                $this->onuModelsSelector[$id] = $eachModel['modelname'];
            }
        }
    }

    /**
     * Loads cards information 
     * 
     * @return array
     */
    protected function loadCards() {
        $cards = array();
        if (isset($this->allCards[$this->currentOltSwId]) AND ! empty($this->allCards[$this->currentOltSwId])) {
            foreach ($this->allCards[$this->currentOltSwId] as $eachId => $eachCard) {
                if ($this->currentPonType == 'EPON') {
                    if (isset($this->eponCards[$eachCard['card_name']])) {
                        $cards[$eachCard['slot_number']]['ports'] = $this->eponCards[$eachCard['card_name']];
                        $cards[$eachCard['slot_number']]['description'] = $eachCard['card_name'];
                        $cards[$eachCard['slot_number']]['chasis'] = $eachCard['chasis_number'];
                    }
                }
                if ($this->currentPonType == 'GPON') {
                    if (isset($this->gponCards[$eachCard['card_name']])) {
                        $cards[$eachCard['slot_number']]['ports'] = $this->gponCards[$eachCard['card_name']];
                        $cards[$eachCard['slot_number']]['description'] = $eachCard['card_name'];
                        $cards[$eachCard['slot_number']]['chasis'] = $eachCard['chasis_number'];
                    }
                }
            }
        }
        return($cards);
    }

    /**
     * Calculating snmp indexes for each OLT.
     * 
     * @param int $swid     
     * 
     * @return void
     */
    protected function loadCalculatedData() {
        $cards = $this->loadCards();
        if (!empty($cards)) {
            $this->ponArray = array();
            $this->onuArray = array();
            if (isset($this->allZteOlt[$this->currentOltSwId])) {
                $inherit = @$this->avidity['Z']['LSD'];
                foreach ($cards as $index => $value) {
                    eval($inherit);
                }
            }
            if (isset($this->allHuaweiOlt[$this->currentOltSwId])) {
                $oltInterface = @snmp2_real_walk($this->currentOltIp, $this->currentSnmpCommunity, $this->currentSnmpTemplate[self::SNMP_TEMPLATE_SECTION]['INTERFACENAME']);
                if (!empty($oltInterface)) {
                    foreach ($oltInterface as $eachOid => $name) {
                        $interfaceId = trim(str_replace($this->currentSnmpTemplate[self::SNMP_TEMPLATE_SECTION]['INTERFACENAME'] . '.', '', $eachOid));
                        $name = str_replace('STRING:', '', $name);
                        $name = str_replace('"', '', $name);
                        $name = trim($name);
                        $this->ponArray[$name] = $interfaceId;
                    }
                }
            }
        }
    }

//Getters section.

    /**
     * Find OLT id by IP.
     * Needed for adding ONU to PONizer.    
     * 
     * @param string $ip
     * 
     * @return int $id
     */
    protected function getOltId($ip) {
        if (!empty($this->allZteOlt)) {
            foreach ($this->allZteOlt as $id => $eachOlt) {
                if ($eachOlt['ip'] == $ip) {
                    return ($id);
                }
            }
        }
        if (!empty($this->allHuaweiOlt)) {
            foreach ($this->allHuaweiOlt as $id => $eachOlt) {
                if ($eachOlt['ip'] == $ip) {
                    return ($id);
                }
            }
        }
    }

    /**
     * Find vlan binds for curtain pon interface.
     * 
     * @return string
     */
    protected function getBindVlan() {
        $interface = explode('/', $this->currentOltInterface);
        $slot = $interface[1];
        $port = $interface[2];
        $this->loadZteBind($this->currentOltSwId);

        if (!empty($this->allBinds)) {
            foreach ($this->allBinds as $id => $eachBind) {
                if ($eachBind['slot_number'] == $slot AND $eachBind['port_number'] == $port) {
                    return ($eachBind['vlan']);
                }
            }
        }

        return '';
    }

    /**
     * Convert card name to interface name.
     * 
     * @param string $cardName
     * 
     * @return string
     */
    protected function correctInt($cardName) {
        if (isset($this->eponCards[$cardName])) {
            return 'epon-olt_';
        }
        if (isset($this->gponCards[$cardName])) {
            return 'gpon-olt_';
        }

        return '';
    }

    /**
     * Convert hex to string.
     * 
     * @param hex-string $hex
     * 
     * @return string
     */
    protected function hexToString($hex) {
        return pack('H*', $hex);
    }

//Main section

    /*
     * Recursive function to generate random and unique MAC.
     * 
     * @return string
     */
    public function generateRandomOnuMac() {
        $check = false;

        if (!empty($this->allOnu)) {
            while (!$check) {
                $mac = '14:' . '88' . ':' . rand(10, 99) . ':' . rand(10, 99) . ':' . rand(10, 99) . ':' . rand(10, 99);
                $check = true;
                foreach ($this->allOnu as $io => $each) {
                    if ($each['mac'] == $mac) {
                        $check = false;
                        break;
                    }
                }
            }
        } else {
            $mac = '14:' . '88' . ':' . rand(10, 99) . ':' . rand(10, 99) . ':' . rand(10, 99) . ':' . rand(10, 99);
        }

        return ($mac);
    }

    /**
     * Get all unautheticated ONUs/ONTs.
     * 
     * @return void
     */
    protected function getAllUnauth() {
        $this->allUnreg = array();
        $this->allUnreg['GPON'] = array();
        $this->allUnreg['EPON'] = array();

        $this->getAllZteUnauth();
        $this->getAllHuaweiUnauth();
    }

    /**
     * Check for unautheticated GPON ONT for specified OLT.
     * 
     * @return void
     */
    protected function getAllUnauthGpon() {
        if (isset($this->allZteOlt[$this->currentOltSwId])) {
            $this->getAllUnauthGponZte();
        }
        if (isset($this->allHuaweiOlt[$this->currentOltSwId])) {
            $this->getAllUnauthGponHuawei();
        }
    }

    /**
     * Check for unauthenticated EPON ONU for specified OLT.
     * 
     * @return void
     */
    protected function getAllUnauthEpon() {
        if (isset($this->allZteOlt[$this->currentOltSwId])) {
            $this->getAllUnauthEponZte();
        }
        if (isset($this->allHuaweiOlt[$this->currentOltSwId])) {
            
        }
    }

    /**
     * Polling ZTE OLTs to check unauthenticated ONT.
     * 
     * @return void
     */
    protected function getAllZteUnauth() {
        if (!empty($this->allZteOlt)) {
            foreach ($this->allZteOlt as $this->currentOltSwId => $eachOlt) {
                if (file_exists(CONFIG_PATH . '/snmptemplates/' . $eachOlt['snmptemplate'])) {
                    $this->currentSnmpTemplate = rcms_parse_ini_file(CONFIG_PATH . '/snmptemplates/' . $eachOlt['snmptemplate'], true);
                    $this->currentPonType = $this->currentSnmpTemplate[self::SNMP_TEMPLATE_SECTION]['TYPE'];
                    $this->currentOltIp = $eachOlt['ip'];
                    $this->currentSnmpCommunity = $eachOlt['snmp'];
                    $this->loadCalculatedData();

                    if (isset($this->allCards[$this->currentOltSwId]) AND ! empty($this->allCards[$this->currentOltSwId])) {
                        if ($this->currentPonType == 'EPON') {
                            $this->getAllUnauthEpon();
                        }
                        if ($this->currentPonType == 'GPON') {
                            $this->getAllUnauthGpon();
                        }
                    }
                }
            }
        }
    }

    /**
     * Polling Huawei OLTs to check unauthenticated ONT.
     * 
     * @return void
     */
    protected function getAllHuaweiUnauth() {
        if (!empty($this->allHuaweiOlt)) {
            foreach ($this->allHuaweiOlt as $this->currentOltSwId => $eachOlt) {
                if (file_exists(CONFIG_PATH . '/snmptemplates/' . $eachOlt['snmptemplate'])) {
                    $this->currentSnmpTemplate = rcms_parse_ini_file(CONFIG_PATH . '/snmptemplates/' . $eachOlt['snmptemplate'], true);
                    $this->currentPonType = $this->currentSnmpTemplate[self::SNMP_TEMPLATE_SECTION]['TYPE'];
                    $this->currentOltIp = $eachOlt['ip'];
                    $this->currentSnmpCommunity = $eachOlt['snmp'];
                    $this->loadCalculatedData();

                    if (isset($this->allCards[$this->currentOltSwId]) AND ! empty($this->allCards[$this->currentOltSwId])) {
                        if ($this->currentPonType == 'EPON') {
                            $this->getAllUnauthEpon();
                        }
                        if ($this->currentPonType == 'GPON') {
                            $this->getAllUnauthGpon();
                        }
                    }
                }
            }
        }
    }

    /**
     * Get unautheticated epon ONT for specified OLT.
     * 
     * @return void
     */
    protected function getAllUnauthEponZte() {
        $allUnreg = @snmp2_real_walk($this->currentOltIp, $this->currentSnmpCommunity, $this->currentSnmpTemplate[self::SNMP_TEMPLATE_SECTION]['UNCFGLIST']);
        if (!empty($allUnreg)) {
            foreach ($allUnreg as $eachUncfgPort => $value) {
                $value = trim(str_replace('Hex-STRING:', '', $value));
                $mac = str_replace(' ', ':', $value);
                $interfaceIdNum = str_replace($this->currentSnmpTemplate[self::SNMP_TEMPLATE_SECTION]['UNCFGLIST'] . '.', '', $eachUncfgPort);
                $interfaceId = substr($interfaceIdNum, 0, 9);

                foreach ($this->ponArray as $slot => $each_id) {
                    if ($each_id == $interfaceId) {
                        array_push($this->allUnreg['EPON'], array('oltip' => $this->currentOltIp, 'slot' => $slot, 'identifier' => $mac, 'swid' => $this->currentOltSwId));
                    }
                }
            }
        }
    }

    /**
     * Getting unauthenticated gpon ONT from ZTE OLT.
     * 
     * @return void
     */
    protected function getAllUnauthGponZte() {
        $allUncfgOid = $this->currentSnmpTemplate[self::SNMP_TEMPLATE_SECTION]['UNCFGLIST'];
        $getUncfgSn = $this->currentSnmpTemplate[self::SNMP_TEMPLATE_SECTION]['UNCFGSN'];

        $allUnreg = @snmp2_real_walk($this->currentOltIp, $this->currentSnmpCommunity, $allUncfgOid);
        if (!empty($allUnreg)) {
            foreach ($allUnreg as $eachUncfgPort => $value) {
                $value = str_replace('INTEGER:', '', $value);
                $value = trim($value);
                if ($value > 0) {
                    $interfaceId = str_replace($allUncfgOid . '.', '', $eachUncfgPort);
                    $uncfgSn = $this->snmp->walk($this->currentOltIp, $this->currentSnmpCommunity, $getUncfgSn . $interfaceId, false);
                    for ($i = 0; $i <= self::GPON_RETRIES; $i++) {
                        if (!empty($uncfgSn)) {
                            break;
                        }
                        $uncfgSn = $this->snmp->walk($this->currentOltIp, $this->currentSnmpCommunity, $getUncfgSn . $interfaceId, false);
                    }
                    $this->parseUncfgGpon($uncfgSn, $interfaceId);
                }
            }
        }
    }

    /**
     * Getting unauthenticated gpon ONT from Huawei OLT.
     * 
     * @return void
     */
    protected function getAllUnauthGponHuawei() {
        $allUnreg = @snmp2_real_walk($this->currentOltIp, $this->currentSnmpCommunity, $this->currentSnmpTemplate[self::SNMP_TEMPLATE_SECTION]['UNCFGSN']);
        $oltInterface = @snmp2_real_walk($this->currentOltIp, $this->currentSnmpCommunity, $this->currentSnmpTemplate[self::SNMP_TEMPLATE_SECTION]['INTERFACENAME']);
        if (!empty($allUnreg) and ! empty($oltInterface)) {
            foreach ($oltInterface as $eachOid => $name) {
                $interfaceId = trim(str_replace($this->currentSnmpTemplate[self::SNMP_TEMPLATE_SECTION]['INTERFACENAME'] . '.', '', $eachOid));
                $name = str_replace('STRING:', '', $name);
                $name = str_replace('"', '', $name);
                $name = trim($name);
                $interfaceList[$interfaceId] = $name;
            }
            foreach ($allUnreg as $eachUncfgPort => $value) {
                $eachUncfgPort = trim(str_replace($this->currentSnmpTemplate[self::SNMP_TEMPLATE_SECTION]['UNCFGSN'] . '.', '', $eachUncfgPort));
                $eachUncfgPort = explode('.', $eachUncfgPort);
                $uncfgPort = $eachUncfgPort[0];
                $value = trim(str_replace('Hex-STRING:', '', $value));
                if (strpos($value, 'STRING:') !== false) {
                    $value = bin2hex(trim(str_replace('STRING:', '', $value)));
                }
                $sn = str_replace(' ', '', $value);
                $slot = $interfaceList[$uncfgPort];
                array_push($this->allUnreg['GPON'], array('oltip' => $this->currentOltIp, 'slot' => $slot, 'identifier' => $sn, 'swid' => $this->currentOltSwId));
            }
        }
    }

    /**
     * Parse and transform raw snmp data into suitable array.
     * 
     * @param string $uncfgSn
     * @param string $interfaceId
     * 
     * @return array
     */
    protected function parseUncfgGpon($uncfgSn, $interfaceId) {
        $uncfgSn = explodeRows(trim($uncfgSn));

        foreach ($uncfgSn as $eachIndex => $rawValue) {
            $rawValue = explode('=', $rawValue);
            $rawSn = trim($rawValue[1]);
            $tmpSn = explode(" ", $rawSn);
            $check = trim($tmpSn[0]);
            $tmpStr = '';
            if ($check == 'STRING:') {
                $tmpSn = bin2hex($tmpSn[1]);
                if (strlen($tmpSn) == 20) {
                    $tmp[0] = $tmpSn[2] . $tmpSn[3];
                    $tmp[1] = $tmpSn[4] . $tmpSn[5];
                    $tmp[2] = $tmpSn[6] . $tmpSn[7];
                    $tmp[3] = $tmpSn[8] . $tmpSn[9];
                    for ($i = 10; $i <= 17; $i++) {
                        $tmpStr .= $tmpSn[$i];
                    }
                    $tmp[4] = $tmpStr;
                } elseif (strlen($tmpSn) == 22) {
                    $tmp[0] = $tmpSn[2] . $tmpSn[3];
                    $tmp[1] = $tmpSn[4] . $tmpSn[5];
                    $tmp[2] = $tmpSn[6] . $tmpSn[7];
                    $tmp[3] = $tmpSn[8] . $tmpSn[9];
                    for ($i = 10; $i <= 11; $i++) {
                        $tmpStr .= $tmpSn[$i];
                    }
                    for ($i = 14; $i <= 19; $i++) {
                        $tmpStr .= $tmpSn[$i];
                    }
                    $tmp[4] = $tmpStr;
                } else {
                    print_r($tmpSn);
                    $tmp[0] = $tmpSn[0] . $tmpSn[1];
                    $tmp[1] = $tmpSn[2] . $tmpSn[3];
                    $tmp[2] = $tmpSn[4] . $tmpSn[5];
                    $tmp[3] = $tmpSn[6] . $tmpSn[7];
                    for ($i = 8; $i <= 15; $i++) {
                        $tmpStr .= $tmpSn[$i];
                    }
                    $tmp[4] = $tmpStr;
                }
            } else {
                $tmp[0] = $tmpSn[1];
                $tmp[1] = $tmpSn[2];
                $tmp[2] = $tmpSn[3];
                $tmp[3] = $tmpSn[4];
                $tmp[4] = $tmpSn[5] . $tmpSn[6] . $tmpSn[7] . $tmpSn[8];
                $tmpSn = $tmp;
            }
            $sn = $this->hexToString($tmp[0]) . $this->hexToString($tmp[1]) . $this->hexToString($tmp[2]) . $this->hexToString($tmp[3]) . $tmp[4];
            foreach ($this->ponArray as $slot => $each_id) {
                if ($each_id == $interfaceId) {
                    array_push($this->allUnreg['GPON'], array('oltip' => $this->currentOltIp, 'slot' => $slot, 'identifier' => $sn, 'swid' => $this->currentOltSwId));
                }
            }
        }
    }

    /**
     * Check for already registered EPON ONU and set lastOnuId.
     *     
     * @return void
     */
    protected function checkRegisterdEponOnu() {
        $alternative = false;

        foreach ($this->onuArray[$this->currentOltInterface] as $eachOnuNumber => $eachOnuID) {
            $check = @snmp2_real_walk($this->currentOltIp, $this->currentSnmpCommunity, $this->currentSnmpTemplate[self::SNMP_TEMPLATE_SECTION]['EACHLLID'] . $eachOnuID);
            if (!empty($check)) {
                $this->existId[] = $eachOnuID;
            }
        }
        if (empty($this->existId)) {
            foreach ($this->onuArrayAlt[$this->currentOltInterface] as $eachOnuNumber => $eachOnuID) {
                $check = @snmp2_real_walk($this->currentOltIp, $this->currentSnmpCommunity, $this->currentSnmpTemplate[self::SNMP_TEMPLATE_SECTION]['EACHLLID'] . $eachOnuID);
                if (!empty($check)) {
                    $this->existId[] = $eachOnuID;
                    $alternative = true;
                }
            }
        }
        if (!empty($this->existId)) {
            if (!$alternative) {
                $free = array_flip(array_diff($this->onuArray[$this->currentOltInterface], $this->existId));
            } else {
                $free = array_flip(array_diff($this->onuArrayAlt[$this->currentOltInterface], $this->existId));
            }
            reset($free);
            $this->lastOnuId = current($free);
        }
    }

    /**
     * Check for already registered GPON ONT and set lastOnuId.
     * 
     * @return void
     */
    protected function checkRegisteredGponOnu() {
        $allID = range(1, 128);
        if (isset($this->allZteOlt[$this->currentOltSwId])) {
            $this->getRegisteredGponZteOnu();
        }
        if (isset($this->allHuaweiOlt[$this->currentOltSwId])) {
            $this->getRegisteredGponHuaweiOnu();
        }
        $free = array_diff($allID, $this->existId);
        reset($free);
        $this->lastOnuId = current($free);
    }

    /**
     * Fetch registered ONU and service ports.
     * 
     * @return void
     */
    protected function getRegisteredGponHuaweiOnu() {
        $getAllId = @snmp2_real_walk($this->currentOltIp, $this->currentSnmpCommunity, $this->currentSnmpTemplate[self::SNMP_TEMPLATE_SECTION]['LLIDLIST'] . $this->ponArray[$this->currentOltInterface]);
        if (!empty($getAllId)) {
            foreach ($getAllId as $oid => $value) {
                $number = trim(str_replace($this->currentSnmpTemplate[self::SNMP_TEMPLATE_SECTION]['LLIDLIST'] . $this->ponArray[$this->currentOltInterface] . '.', '', $oid));
                $this->existId[] = $number;
            }
        }
        $allServicePorts = @snmp2_real_walk($this->currentOltIp, $this->currentSnmpCommunity, $this->currentSnmpTemplate[self::SNMP_TEMPLATE_SECTION]['SERVICEPORTS']);
        if (!empty($allServicePorts)) {
            $count = count($allServicePorts);
            $allPorts = range(1, 65536);
            foreach ($allServicePorts as $eachOid => $value) {
                $split = explode(':', $value);
                $number = trim($split[1]);
                $number--;
                $usedPorts[$number] = $number;
            }
            $freePorts = array_diff($allPorts, $usedPorts);
            $this->servicePort = current($freePorts);
        }
    }

    /**
     * Fetch registered ONU
     * 
     * @return void
     */
    protected function getRegisteredGponZteOnu() {
        $getAllId = @snmp2_real_walk($this->currentOltIp, $this->currentSnmpCommunity, $this->currentSnmpTemplate[self::SNMP_TEMPLATE_SECTION]['LLIDLIST'] . $this->ponArray[$this->currentOltInterface]);
        if (!empty($getAllId)) {
            foreach ($getAllId as $oid => $value) {
                $number = explode(':', $value);
                $number = trim($number[1]);
                $this->existId[] = $number;
            }
        }
    }

    /**
     * Used to change mac format from xx:xx:xx:xx:xx:xx to xxxx.xxxx.xxxx
     *      
     * @return void
     */
    protected function transformMac() {
        $macRaw = explode(':', $this->onuIdentifier);
        $macPart[] = $macRaw[0] . $macRaw[1];
        $macPart[] = $macRaw[2] . $macRaw[3];
        $macPart[] = $macRaw[4] . $macRaw[5];
        return implode('.', $macPart);
    }

    /**
     * Used to check if connection values exact as we need.
     * 
     * @return string
     */
    public function checkOltParams() {
        if (!empty($this->allSwLogin) and isset($this->allSwLogin[$this->currentOltSwId])) {
            if ($this->allSwLogin[$this->currentOltSwId]['method'] == 'SNMP') {
                return self::ERROR_SNMP_CONNECTION_SET;
            }
            return self::NO_ERROR_CONNECTION;
        } else {
            return self::ERROR_NO_LOGIN_AVAILABLE;
        }
    }

    /**
     * Make final checks with some data preparing.
     * And finally register ONU.
     * 
     * @return void
     */
    public function RegisterOnu() {
        if (isset($this->allHuaweiOlt[$this->currentOltSwId])) {
            $this->currentSnmpCommunity = $this->allHuaweiOlt[$this->currentOltSwId]['snmp'];
            $snmpTemplateName = $this->allHuaweiOlt[$this->currentOltSwId]['snmptemplate'];
        }
        if (isset($this->allZteOlt[$this->currentOltSwId])) {
            $this->currentSnmpCommunity = $this->allZteOlt[$this->currentOltSwId]['snmp'];
            $snmpTemplateName = $this->allZteOlt[$this->currentOltSwId]['snmptemplate'];
        }

        $this->loadCalculatedData();

//set serial number empty as default value because epon    
        $serial = '';

        if (!empty($this->allSwLogin) and isset($this->allSwLogin[$this->currentOltSwId])) {
            if (file_exists(CONFIG_PATH . '/snmptemplates/' . $snmpTemplateName)) {
                $this->currentSnmpTemplate = rcms_parse_ini_file(CONFIG_PATH . '/snmptemplates/' . $snmpTemplateName, true);
                if ($this->currentPonType == 'EPON') {
                    $this->addMac = $this->onuIdentifier;
                    $this->onuIdentifier = $this->transformMac();
                    $this->checkRegisterdEponOnu();
                }
                if ($this->currentPonType == 'GPON') {
                    $this->onuIdentifier = strtoupper($this->onuIdentifier);
                    $serial = $this->onuIdentifier;
                    $this->checkRegisteredGponOnu();
                }

//Exit if onu number is 65+ for epon and 128+ for gpon.
                if ($this->currentPonType == 'EPON') {
                    $intParts = explode("/", $this->currentOltInterface);
                    $slot = $intParts[1];
                    foreach ($this->allCards[$this->currentOltSwId] as $each => $io) {
                        if ($io['slot_number'] == $slot) {
                            if ($io['card_name'] == "ETTO" or $io['card_name'] == "ETTOK") {
                                if (count($this->existId) >= 128) {
                                    $this->error = self::ERROR_TOO_MANY_REGISTERED_ONU;
                                    return('');
                                }
                            } else {
                                if (count($this->existId) >= 64) {
                                    $this->error = self::ERROR_TOO_MANY_REGISTERED_ONU;
                                    return('');
                                }
                            }
                        }
                    }
                }

                if ($this->currentPonType == 'GPON') {
                    if (count($this->existId) >= 128) {
                        $this->error = self::ERROR_TOO_MANY_REGISTERED_ONU;
                        return('');
                    }
                }

                $this->result .= shell_exec($this->getRegisterOnuCommand());
                $this->result .= shell_exec($this->getSaveConfigCommand());
                $this->result = nl2br($this->result);
                log_register('ONUREG REGISTER ONU. ONU ID: ' . $this->onuIdentifier . ' OLT IP: ' . $this->currentOltIp . 'OLT INTERFACE: ' . $this->currentOltInterface . ' ONU NUMBER: ' . $this->lastOnuId);

                if ($this->ponizerAdd) {
                    if (!empty($this->addMac)) {
                        $pon = new PONizer();
                        $pon->onuCreate($this->onuModel, $this->currentOltSwId, '', $this->addMac, $serial, $this->login);
                    } else {
                        log_register('ONUREG PONIZER WRONG DATA. Login: ' . $this->login . '. MAC: ' . $this->addMac);
                    }
                }
            }
        }
    }

    /**
     * Compose path to scripts.
     * 
     * @return string $scriptPath
     */
    protected function getRegisterOnuScriptPath() {
        $scriptPath = CONFIG_PATH . 'scripts/';
        if (isset($this->allHuaweiOlt[$this->currentOltSwId])) {
            $scriptPath .= 'HUAWEI_';
            $splitName = explode(' ', $this->currentOltInterface);
            $splitInterface = explode('/', $splitName[1]);
            $this->currentOltInterface = $splitInterface[0] . '/' . $splitInterface[1];
            $this->onuInterface = $splitInterface[2];
        } else {
            $this->onuInterface = str_replace('olt', 'onu', $this->currentOltInterface);
        }
        $scriptPath .= $this->currentPonType . '_' . $this->onuModels[$this->onuModel]['ports'];
        if ($this->router) {
            $scriptPath .= '_R';
        }
        if ($this->useUniversalQINQ == 'cvlan') {
            $scriptPath .= '_CVLAN';
        }
        if ($this->useUniversalQINQ == 'pair') {
            $scriptPath .= '_QINQ';
        }

        return ($scriptPath);
    }

    /**
     * Check if user has assiigned qinq pair and which mode was set for registration.
     */
    protected function checkQinq() {
        if ($this->useUniversalQINQ != 'none') {
            if (isset($this->usersQinQ[$this->login])) {
                $user = $this->usersQinQ[$this->login];
                $this->vlan = $user['cvlan'];
                if ($this->useUniversalQINQ == 'pair') {
                    $this->svlan = $this->allSvlan[$user['svlan_id']]['svlan'];
                    $this->vlan .= ' ' . $this->svlan;
                }
            }
        }
    }

    /**
     * Compose command for onu registration.
     * 
     * @return string $command
     */
    protected function getRegisterOnuCommand() {
        $this->checkQinq();
        $scriptPath = $this->getRegisterOnuScriptPath();
        if (file_exists($scriptPath)) {
            $command = $this->billingCfg['EXPECT_PATH'];
            $command .= ' ' . $scriptPath;
            $command .= ' ' . $this->currentOltIp;
            $command .= ' ' . $this->allSwLogin[$this->currentOltSwId]['swlogin'];
            $command .= ' ' . $this->allSwLogin[$this->currentOltSwId]['swpass'];
            $command .= ' ' . $this->allSwLogin[$this->currentOltSwId]['method'];
            $command .= ' ' . $this->currentOltInterface;
            $command .= ' ' . $this->onuInterface;
            $command .= ' ' . $this->lastOnuId;
            $command .= ' ' . $this->vlan;
            $command .= ' ' . $this->onuIdentifier;
        }
        return($command);
    }

    /**
     * Compose command to save OLT configuration.
     * 
     * @return string
     */
    protected function getSaveConfigCommand() {
        $command = '/bin/true';
        if ($this->save) {
            $command = $this->billingCfg['EXPECT_PATH'];
            $command .= ' ' . CONFIG_PATH . 'scripts/';
            if (isset($this->allHuaweiOlt[$this->currentOltSwId])) {
                $command .= 'HUAWEI_';
            }
            $command .= 'save';
            $command .= ' ' . $this->currentOltIp;
            $command .= ' ' . $this->allSwLogin[$this->currentOltSwId]['swlogin'];
            $command .= ' ' . $this->allSwLogin[$this->currentOltSwId]['swpass'];
            $command .= ' ' . $this->allSwLogin[$this->currentOltSwId]['method'];
        }
        return($command);
    }

//handling persistent changes

    /**
     * Register new card for specified OLT. Also checking for duplicates.
     * 
     * @param int $swid
     * @param int $chasis
     * @param int $slot
     * @param string $card
     * 
     * @return boolean
     */
    public function createZteCard($swid, $chasis, $slot, $card) {
        if (isset($this->allCards[$swid]) AND ! empty($this->allCards[$swid])) {
            foreach ($this->allCards[$swid] as $eachNumber => $eachCard) {
                if ($eachCard['slot_number'] == $slot) {
                    rcms_redirect(self::MODULE_URL_EDIT_CARD . $swid);
                    return (false);
                }
            }
        }
        $swid = vf($swid, 3);
        $chasis = vf($chasis, 3);
        $slot = vf($slot, 3);
        $card = mysql_real_escape_string($card);
        $query = 'INSERT INTO `' . self::CARDS_TABLE . '` (`id`, `swid`, `slot_number`, `card_name`, `chasis_number`) VALUE (NULL, "' . $swid . '", "' . $slot . '", "' . $card . '", "' . $chasis . '")';
        nr_query($query);
        log_register('ZTE Registered new card. OLT ID: ' . $swid . 'Slot: `' . $slot . '`. Card name: `' . $card . '`.');
        rcms_redirect(self::MODULE_URL_EDIT_CARD . $swid);

        return (true);
    }

    /**
     * Edit card name for specified slot number.
     * 
     * @param int $swid
     * @param int $slot
     * @param string $card
     * 
     * @return void
     */
    public function editZteCard($swid, $slot, $card) {
        $swid = vf($swid, 3);
        $slot = vf($slot, 3);
        $card = mysql_real_escape_string($card);
        $query = 'UPDATE ' . self::CARDS_TABLE . ' SET `card_name` = "' . $card . '" WHERE `swid` = "' . $swid . '" AND `slot_number` = "' . $slot . '"';
        nr_query($query);
        log_register('ZTE Edited card. OLT ID: ' . $swid . '. Slot: `' . $slot . '`. Card name: `' . $card . '`.');

        rcms_redirect(self::MODULE_URL_EDIT_CARD . $swid);
    }

    /**
     * Delete card entry from DB.
     * 
     * @param type $swid
     * @param type $slot
     * 
     * @return void
     */
    public function deleteZteCard($swid, $slot) {
        $swid = vf($swid, 3);
        $slot = vf($slot, 3);
        $query = 'DELETE FROM `' . self::CARDS_TABLE . '` WHERE `swid` = "' . $swid . '" AND `slot_number` = "' . $slot . '"';
        nr_query($query);
        log_register('ZTE Deleted card. OLT ID: ' . $swid . '. Slot: `' . $slot . '`');

        rcms_redirect(self::MODULE_URL_EDIT_CARD . $swid);
    }

    /**
     * Create new vlan binding.
     * 
     * @param int $swid
     * @param int $slot
     * @param int $port
     * @param int $vlan
     * 
     * @return boolean
     */
    public function createZteBind($swid, $slot, $port, $vlan) {
        $this->loadZteBind($swid);
        if (!empty($this->allBinds)) {
            foreach ($this->allBinds as $each => $eachBind) {
                if ($eachBind['slot_number'] == $slot AND $eachBind['port_number'] == $port) {
                    rcms_redirect(self::MODULE_URL_EDIT_BIND . $swid);
                    return (false);
                }
            }
        }
        $swid = vf($swid, 3);
        $slot = vf($slot, 3);
        $port = vf($port, 3);
        $vlan = vf($vlan, 3);
        $query = 'INSERT INTO `' . self::BIND_TABLE . '` (`id`, `swid`, `slot_number`, `port_number`, `vlan`) VALUE (NULL, "' . $swid . '", "' . $slot . '", "' . $port . '", "' . $vlan . '")';
        nr_query($query);
        log_register('ZTE Created new vlan bind. OLT ID: ' . $swid . '. Slot: `' . $slot . '`. Port: `' . $port . '`. VLAN: `' . $vlan . '`');

        rcms_redirect(self::MODULE_URL_EDIT_BIND . $swid);
    }

    /**
     * Delete vlan binding entry from DB.
     * 
     * @param int $swid
     * @param int $slot
     * @param int $port
     * 
     * @return void
     */
    public function deleteZteBind($swid, $slot, $port) {
        $swid = vf($swid, 3);
        $slot = vf($slot, 3);
        $port = vf($port, 3);
        $query = 'DELETE FROM `' . self::BIND_TABLE . '` WHERE `swid` = "' . $swid . '" AND `slot_number` = "' . $slot . '" AND `port_number` = "' . $port . '"';
        nr_query($query);
        log_register('ZTE Deleted vlan bind. OLT ID: ' . $swid . '. Slot: `' . $slot . '`. Port: `' . $port . '`.');

        rcms_redirect(self::MODULE_URL_EDIT_BIND . $swid);
    }

    /**
     * Edit vlan bind entry.
     * 
     * @param int $swid
     * @param int $slot
     * @param int $port
     * @param int $vlan
     * 
     * @return void
     */
    public function editZteBind($swid, $slot, $port, $vlan) {
        $swid = vf($swid, 3);
        $slot = vf($slot, 3);
        $port = vf($port, 3);
        $vlan = vf($vlan, 3);
        $query = 'UPDATE `' . self::BIND_TABLE . '` SET `vlan` = "' . $vlan . '" WHERE `swid` = "' . $swid . '" AND `slot_number` = "' . $slot . '" AND `port_number` = "' . $port . '"';
        nr_query($query);
        log_register('ZTE Edited vlan bind. OLT ID: ' . $swid . '. Slot: `' . $slot . '`. Port: `' . $port . '`. VLAN: `' . $vlan . '`');

        rcms_redirect(self::MODULE_URL_EDIT_BIND . $swid);
    }

//Web forms/render section.

    /**
     * List all available ZTE devices.
     * 
     * @return string
     */
    public function listAllZteDevices() {
        $tablecells = wf_TableCell(__('ID'));
        $tablecells .= wf_TableCell(__('OLT IP'));
        $tablecells .= wf_TableCell(__('Description'));
        $tablecells .= wf_TableCell(__('Actions'));
        $tablerows = wf_TableRow($tablecells, 'row1');

        if (!empty($this->allZteOlt)) {
            foreach ($this->allZteOlt as $eachNumber => $eachOlt) {
                $tablecells = wf_TableCell($eachOlt['id']);
                $tablecells .= wf_TableCell($eachOlt['ip']);
                $tablecells .= wf_TableCell($eachOlt['desc'] . ' | ' . $eachOlt['location']);
                $actionLinks = wf_Link(self::MODULE_URL_EDIT_CARD . $eachOlt['id'], wf_img('skins/chasis.png', __('Edit cards')), false);
                $actionLinks .= wf_Link(self::MODULE_URL_EDIT_BIND . $eachOlt['id'], wf_img('skins/bind.png', __('Edit VLAN bindings')), false);
                $tablecells .= wf_TableCell($actionLinks);
                $tablerows .= wf_TableRow($tablecells, 'row3');
            }
        }
        if (!empty($this->allHuaweiOlt)) {
            foreach ($this->allHuaweiOlt as $eachNumber => $eachOlt) {
                $tablecells = wf_TableCell($eachOlt['id']);
                $tablecells .= wf_TableCell($eachOlt['ip']);
                $tablecells .= wf_TableCell($eachOlt['desc'] . ' | ' . $eachOlt['location']);
                $actionLinks = wf_Link(self::MODULE_URL_EDIT_CARD . $eachOlt['id'], wf_img('skins/chasis.png', __('Edit cards')), false);
                $actionLinks .= wf_Link(self::MODULE_URL_EDIT_BIND . $eachOlt['id'], wf_img('skins/bind.png', __('Edit VLAN bindings')), false);
                $tablecells .= wf_TableCell($actionLinks);
                $tablerows .= wf_TableRow($tablecells, 'row3');
            }
        }
        $result = wf_TableBody($tablerows, '100%', '0', 'sortable');
        $result .= wf_delimiter();

        return ($result);
    }

    /**
     * Show selected ZTE device.
     * 
     * @param $oltid int
     * 
     * @return string
     */
    public function listZteDevice($oltid) {
        $tablecells = wf_TableCell(__('ID'));
        $tablecells .= wf_TableCell(__('OLT IP'));
        $tablecells .= wf_TableCell(__('Description'));
        $tablerows = wf_TableRow($tablecells, 'row1');

        if (!empty($this->allZteOlt)) {
            if (isset($this->allZteOlt[$oltid])) {
                $eachOlt = $this->allZteOlt[$oltid];
                $tablecells = wf_TableCell($eachOlt['id']);
                $tablecells .= wf_TableCell($eachOlt['ip']);
                $tablecells .= wf_TableCell($eachOlt['desc'] . ' | ' . $eachOlt['location']);
                $tablerows .= wf_TableRow($tablecells, 'row3');
            }
        }
        if (!empty($this->allHuaweiOlt)) {
            if (isset($this->allHuaweiOlt[$oltid])) {
                $eachOlt = $this->allHuaweiOlt[$oltid];
                $tablecells = wf_TableCell($eachOlt['id']);
                $tablecells .= wf_TableCell($eachOlt['ip']);
                $tablecells .= wf_TableCell($eachOlt['desc'] . ' | ' . $eachOlt['location']);
                $tablerows .= wf_TableRow($tablecells, 'row3');
            }
        }

        $result = wf_TableBody($tablerows, '100%', '0', 'sortable');
        $result .= wf_delimiter();

        return ($result);
    }

    /**
     * List all registered cards.
     * 
     * @param int $swid
     * 
     * @return string
     */
    public function listZteCard($swid) {
        $tablecells = wf_TableCell(__('ID'));
        $tablecells .= wf_TableCell(__('Chasis number'));
        $tablecells .= wf_TableCell(__('Slot number'));
        $tablecells .= wf_TableCell(__('Card name'));
        $tablecells .= wf_TableCell(__('Actions'));
        $tablerows = wf_TableRow($tablecells, 'row1');

        if (isset($this->allCards[$swid]) AND ! empty($this->allCards[$swid])) {
            foreach ($this->allCards[$swid] as $each => $eachCard) {
                $tablecells = wf_TableCell($eachCard['id']);
                $tablecells .= wf_TableCell($eachCard['chasis_number']);
                $tablecells .= wf_TableCell($eachCard['slot_number']);
                $tablecells .= wf_TableCell($eachCard['card_name']);
                $actionLinks = wf_JSAlert(self::MODULE_URL_EDIT_CARD . $swid . '&edit=true&slot_number=' . $eachCard['slot_number'] . '&card_name=' . $eachCard['card_name'], web_edit_icon(), $this->messages->getEditAlert());
                $actionLinks .= wf_JSAlert(self::MODULE_URL_EDIT_CARD . $swid . '&delete=true&slot_number=' . $eachCard['slot_number'], web_delete_icon(), $this->messages->getDeleteAlert());
                $tablecells .= wf_TableCell($actionLinks);
                $tablerows .= wf_TableRow($tablecells, 'row3');
            }
        }
        $result = wf_TableBody($tablerows, '100%', '0', 'sortable');
        $result .= wf_delimiter();

        return ($result);
    }

    /**
     * Form for registering new card.
     * 
     * @param int $swid
     * 
     * @return string
     */
    public function createZteCardForm($swid) {
        $cell = wf_HiddenInput('createZteCard', 'true');
        $cell .= wf_HiddenInput('swid', $swid);
        $cell .= wf_TextInput('chasis_number', __('Chasis number'));
        $cell .= wf_TextInput('slot_number', __('Slot number'));
        $cell .= wf_TextInput('card_name', __('Card name'));
        $cell .= wf_Tag('br');
        $cell .= wf_Submit(__('Save'));
        $Row = wf_TableRow($cell, 'row1');
        $form = wf_Form('', 'POST', $Row, 'glamour');

        return ($form);
    }

    /**
     * Form for editing card name.
     * 
     * @param int $swid
     * @param int $slot
     * @param string $card
     * 
     * @return string
     */
    public function editZteCardForm($swid, $slot, $card) {
        $cell = wf_HiddenInput('editZteCard', 'true');
        $cell .= wf_HiddenInput('swid', $swid);
        $cell .= wf_HiddenInput('slot_number', $slot);
        $cell .= __('Slot number') . ': ' . $slot;
        $cell .= wf_tag('br', true);
        $cell .= wf_TextInput('card_name', __('Card name'), $card);
        $cell .= wf_Tag('br');
        $cell .= wf_Submit(__('Save'));
        $Row = wf_TableRow($cell, 'row1');
        $form = wf_Form('', 'POST', $Row, 'glamour');

        return ($form);
    }

    /**
     * Set hidden input for sending SNMP request to list all installed cards.
     * 
     * @param int $swid
     * 
     * @return string
     */
    public function setSNMPRequest($swid) {
        $cell = wf_HiddenInput('module', $_GET['module']);
        $cell .= wf_HiddenInput('edit_card', $swid);
        $cell .= wf_HiddenInput('show_snmp', 'true');
        $cell .= wf_Submit(__('Request'));
        $Row = wf_TableRow($cell, 'row1');
        $form = wf_Form('', 'GET', $Row, 'glamour');

        return ($form);
    }

    /**
     * Form for selecting available ports for specified card.
     * 
     * @param string $cardName
     * @param int $swid
     * 
     * @return string
     */
    public function portSelectorForm($cardName, $swid) {
        $search = array();
        $exclude = array();
        $result = '';

        if (isset($this->allCards[$swid]) AND ! empty($this->allCards[$swid])) {
            foreach ($this->allCards[$swid] as $each) {
                $search[$each['slot_number']] = $each['card_name'];
            }
        }
        if (!empty($search)) {
            $this->loadZteBind($swid);
            if (!empty($this->allBinds)) {
                foreach ($this->allBinds as $each => $eachBind) {
                    if ($eachBind['slot_number'] == $cardName) {
                        $exclude[] = $eachBind['port_number'];
                    }
                }
            }
            $cardName = $search[$cardName];
            $this->loadPortSelector($cardName, $exclude);
            $result = wf_Selector('port_number', $this->portSelector, __('Choose port'), '', true);
        }

        return ($result);
    }

    /**
     * Lists all installed cards.
     * 
     * @param int $swid
     * 
     * @return string
     */
    public function showAllInstalledCards($swid) {
        $allCards = array();
        $tablecells = wf_TableCell(__('Slot number'));
        $tablecells .= wf_TableCell(__('Card name'));
        $tablecells .= wf_TableCell(__('Card type'));
        $tablerows = wf_TableRow($tablecells, 'row1');

        if (!empty($this->allZteOlt)) {
            if (isset($this->allZteOlt[$swid])) {
                $oltData = $this->allZteOlt[$swid];
            }
            if (isset($this->allHuaweiOlt[$swid])) {
                $oltData = $this->allHuaweiOlt[$swid];
            }
            if (file_exists(CONFIG_PATH . '/snmptemplates/' . $oltData['snmptemplate'])) {
                $snmpTemplate = rcms_parse_ini_file(CONFIG_PATH . '/snmptemplates/' . $oltData['snmptemplate'], true);
                if (isset($snmpTemplate[self::SNMP_TEMPLATE_SECTION]['ALLCARDS'])) {
                    $allCards = @snmp2_real_walk($oltData['ip'], $oltData['snmp'], $snmpTemplate[self::SNMP_TEMPLATE_SECTION]['ALLCARDS']);
                }
                if (!empty($allCards)) {
                    foreach ($allCards as $eachOid => $eachCard) {
                        $cardType = 'other';
                        $eachOid = trim(str_replace($snmpTemplate[self::SNMP_TEMPLATE_SECTION]['ALLCARDS'] . '.', '', $eachOid));
                        $eachOid = explode('.', $eachOid);
                        $eachCard = trim(str_replace(array('STRING:', '"'), '', $eachCard));
                        $tablecells = wf_TableCell($eachOid[2]);
                        if (isset($this->eponCards[$eachCard])) {
                            $cardType = 'EPON';
                        }
                        if (isset($this->gponCards[$eachCard])) {
                            $cardType = 'GPON';
                        }
                        $tablecells .= wf_TableCell($eachCard);
                        $tablecells .= wf_TableCell($cardType);
                        $tablerows .= wf_TableRow($tablecells, 'row1');
                    }
                }
            }
        }
        $result = wf_TableBody($tablerows, '100%', '0', 'sortable');
        $result .= wf_delimiter();

        return ($result);
    }

    /**
     * Form for creating new vlan binding.
     * 
     * @param int $swid
     * 
     * @return string
     */
    public function createZteBindForm($swid) {
        $this->loadCardSelector($swid);
        $cell = wf_HiddenInput('createZteBind', 'true');
        $cell .= wf_HiddenInput('swid', $swid);
        $cell .= wf_SelectorClassed('slot_number', $this->cardSelector, __('IP | Slot number | Card name'), '', true, 'changeType');
        $cell .= wf_tag('div', false, 'changePorts', 'style="width: 100%;
"') . wf_tag('div', true);
        $cell .= wf_TextInput('vlan', 'VLAN');
        $cell .= wf_Tag('br');
        $cell .= wf_Submit(__('Save'));
        $Row = wf_TableRow($cell, 'row1');
        $form = wf_Form('', 'POST', $Row, 'glamour');
        $form .= '
<script>
$(".changeType").change(function () {
    $.ajax({
        url: "",
        type: "POST",
        data: { json_type_changed: $(".changeType").val() },
        success: function (html) {
            $(".changePorts").html(html);
        }
    });
});
</script>';

        return ($form);
    }

    protected function getListZteBind($oltType, $swid) {
        $tablerows = '';
        foreach ($this->allBinds as $each => $eachBind) {
            if (isset($oltType[$eachBind['swid']])) {
                $tablecells = wf_TableCell($eachBind['id']);
                $tablecells .= wf_TableCell($this->allZteOlt[$eachBind['swid']]['ip']);
                $tablecells .= wf_TableCell($eachBind['slot_number']);
                $tablecells .= wf_TableCell($eachBind['port_number']);
                $tablecells .= wf_TableCell($eachBind['vlan']);
                $actionLinks = wf_JSAlert(self::MODULE_URL_EDIT_BIND . $swid . '&edit=true&slot_number=' . $eachBind['slot_number'] . '&port_number=' . $eachBind['port_number'] . '&vlan=' . $eachBind['vlan'], web_edit_icon(), $this->messages->getEditAlert());
                $actionLinks .= wf_JSAlert(self::MODULE_URL_EDIT_BIND . $swid . '&delete=true&slot_number=' . $eachBind['slot_number'] . '&port_number=' . $eachBind['port_number'], web_delete_icon(), $this->messages->getDeleteAlert());
                $tablecells .= wf_TableCell($actionLinks);
                $tablerows .= wf_TableRow($tablecells, 'row3');
            }
        }
        return ($tablerows);
    }

    /**
     * Lists all vlan bindings.
     * 
     * @param int $swid
     * 
     * @return string
     */
    public function listZteBind($swid) {
        $this->loadZteBind($swid);
        $tablecells = wf_TableCell(__('ID'));
        $tablecells .= wf_TableCell(__('IP'));
        $tablecells .= wf_TableCell(__('Slot number'));
        $tablecells .= wf_TableCell(__('Port number'));
        $tablecells .= wf_TableCell('VLAN');
        $tablecells .= wf_TableCell(__('Actions'));
        $tablerows = wf_TableRow($tablecells, 'row1');

        if (!empty($this->allBinds)) {
            if (!empty($this->allZteOlt)) {
                $tablerows .= $this->getListZteBind($this->allZteOlt, $swid);
            }
            if (!empty($this->allHuaweiOlt)) {
                $tablerows .= $this->getListZteBind($this->allHuaweiOlt, $swid);
            }
        }

        $result = wf_TableBody($tablerows, '100%', '0', 'sortable');
        $result .= wf_delimiter();

        return ($result);
    }

    /**
     * Form for editing vlan binding.
     * 
     * @param int $swid
     * @param int $slot
     * @param int $port
     * @param int $vlan
     * 
     * @return string
     */
    public function editZteBindForm($swid, $slot, $port, $vlan) {
        $cell = wf_HiddenInput('editZteBind', 'true');
        $cell .= wf_HiddenInput('swid', $swid);
        $cell .= wf_HiddenInput('slot_number', $slot);
        $cell .= wf_HiddenInput('port_number', $port);
        $cell .= __('Slot number') . ': ' . $slot;
        $cell .= wf_delimiter();
        $cell .= __('Port number ') . ': ' . $port;
        $cell .= wf_delimiter();
        $cell .= wf_TextInput('vlan', 'VLAN', $vlan);
        $cell .= wf_delimiter();
        $cell .= wf_Submit(__('Save'));
        $Row = wf_TableRow($cell, 'row1');
        $form = wf_Form('', 'POST', $Row, 'glamour');

        return ($form);
    }

    /**
     * Collect and show all unregistered onu.
     * 
     * @return string
     */
    public function listAllUncfg() {
        $this->getAllUnauth();
        $tablecells = wf_TableCell(__('OLT IP'));
        $tablecells .= wf_TableCell(__('Type'));
        $tablecells .= wf_TableCell(__('Interface'));
        $tablecells .= wf_TableCell('MAC/SN');
        $tablecells .= wf_TableCell(__('Actions'));
        $tablerows = wf_TableRow($tablecells, 'row1');

        if (!empty($this->allUnreg)) {
            foreach ($this->allUnreg as $eachType => $io) {
                foreach ($io as $eachOnu) {
                    $ip = $eachOnu['oltip'];
                    $interface = $eachOnu['slot'];
                    $macOnu = strtolower($eachOnu['identifier']);
                    $oltId = $eachOnu['swid'];
                    $tablecells = wf_TableCell($ip);
                    $tablecells .= wf_TableCell($eachType);
                    $tablecells .= wf_TableCell($interface);
                    $tablecells .= wf_TableCell($macOnu);
                    switch ($eachType) {
                        case 'GPON':
                            $identifier = '&' . self::SERIAL_FIELD . '=';
                            break;
                        case 'EPON':
                            $identifier = '&' . self::MACONU_FIELD . '=';
                            break;
                    }
                    $actionLinks = wf_Link(self::UNREG_ACT_URL . $ip . '&interface=' . $interface . $identifier . $macOnu . '&type=' . $eachType . '&swid=' . $oltId, wf_img('skins/add_icon.png', __('Register')), false);
                    $tablecells .= wf_TableCell($actionLinks);
                    $tablerows .= wf_TableRow($tablecells, 'row3');
                }
            }
        }
        $result = wf_TableBody($tablerows, '100%', '0', 'sortable');
        $result .= wf_delimiter();

        return ($result);
    }

    protected function getAllUniversalCvlans($svlanId) {
        $query = "SELECT * FROM `qinq_bindings` WHERE `svlan_id`=" . $svlanId;
        $data = simple_queryall($query);
        if (!empty($data)) {
            foreach ($data as $io => $each) {
                $result[] = $each['cvlan'];
            }
            return($result);
        }
        return(array());
    }

    protected function getCardName() {
        $intParts = explode("/", $_GET['interface']);
        $slot = mysql_real_escape_string($intParts[1]);
        $swid = vf($_GET['swid'], 3);

        $query = "SELECT * FROM `zte_cards` WHERE `swid`=" . $swid . ' AND slot_number=' . $slot;
        $cardData = simple_query($query);
        return($cardData['card_name']);
    }

    protected function getQinqPairPool() {
        $result = array();
        $intParts = explode("/", $_GET['interface']);
        $slot = mysql_real_escape_string($intParts[1]);
        $port = mysql_real_escape_string($intParts[2]);
        $swid = vf($_GET['swid'], 3);
        $cardName = $this->getCardName();
        $query = "SELECT * FROM `zte_qinq` WHERE `swid`=" . $swid . ' AND `slot_number`=' . $slot . ' AND `port`=' . $port;
        $qinqBind = simple_query($query);
        if (!empty($qinqBind)) {
            $cvlan = $qinqBind['cvlan'];
            $svlanId = $qinqBind['svlan_id'];
            $query = "SELECT * FROM `qinq_svlan` WHERE `id`=" . $svlanId;
            $vlanData = simple_query($query);
            if (!empty($vlanData)) {
                $maxOnuCount = 128;
                if ($this->currentPonType == 'EPON') {
                    if ($cardName != 'ETTO' and $cardName != 'ETTOK') {
                        $maxOnuCount = 64;
                    }
                }
                $lastCvlan = $cvlan + $maxOnuCount - 1;
                $allUniversal = $this->getAllUniversalCvlans($svlanId);
                for ($cvlanCounter = $cvlan; $cvlanCounter <= $lastCvlan; $cvlanCounter++) {
                    $possibleCvlans[] = $cvlanCounter;
                }
                $freeCvlans = array_diff($possibleCvlans, $allUniversal);
                reset($freeCvlans);
                $firstFree = current($freeCvlans);

                $result['cvlan'] = $firstFree;
                $result['svlan'] = $vlanData['svlan'];
            }
        }
        return($result);
    }

    /**
     * Web form for register onu.
     * 
     * @return string
     */
    public function registerOnuForm() {
        $this->loadOnuModelSelector();
        $vlan = $this->getBindVlan();

        switch ($this->currentPonType) {
            case 'EPON':
                $cell = wf_HiddenInput(self::TYPE_FIELD, $this->currentPonType);
                $cell .= wf_HiddenInput(self::INTERFACE_FIELD, $this->currentOltInterface);
                $cell .= wf_HiddenInput(self::OLTIP_FIELD, $this->currentOltIp);
                $cell .= wf_HiddenInput(self::MAC_FIELD, $this->onuIdentifier);
                $cell .= wf_HiddenInput(self::OLTID_FIELD, $this->currentOltSwId);
                $cell .= wf_Selector(self::MODELID_FIELD, $this->onuModelsSelector, __('Choose ONU model'), '', true);
                $cell .= wf_TextInput(self::VLAN_FIELD, 'VLAN', $vlan, true);
                $cell .= wf_TextInput(self::LOGIN_FIELD, __('Login'), '', true);
                $cell .= wf_CheckInput(self::PONIZER_ADD_FIELD, __('Add ONU to PONizer'), true, true);
                $cell .= wf_Tag('br');
                $cell .= wf_CheckInput(self::SAVE_FIELD, __('Save config'), true);

                break;
            case 'GPON':
                $cell = wf_HiddenInput(self::TYPE_FIELD, $this->currentPonType);
                $cell .= wf_HiddenInput(self::INTERFACE_FIELD, $this->currentOltInterface);
                $cell .= wf_HiddenInput(self::OLTIP_FIELD, $this->currentOltIp);
                $cell .= wf_HiddenInput(self::SN_FIELD, $this->onuIdentifier);
                $cell .= wf_HiddenInput(self::OLTID_FIELD, $this->currentOltSwId);
                $cell .= wf_Selector(self::MODELID_FIELD, $this->onuModelsSelector, __('Choose ONU model'), '', true);
                $cell .= wf_TextInput(self::VLAN_FIELD, 'VLAN', $vlan, true);
                $cell .= wf_TextInput(self::LOGIN_FIELD, __('Login'), '', true);
                $cell .= wf_TextInput(self::MAC_ONU_FIELD, __('MAC ONU for PONizer'), '', true);
                $cell .= wf_CheckInput(self::RANDOM_MAC_FIELD, __('Generate random mac'), true, true);
                $cell .= wf_CheckInput(self::PONIZER_ADD_FIELD, __('Add ONU to PONizer'), true, true);
                $cell .= wf_Tag('br');
                $cell .= wf_CheckInput(self::SAVE_FIELD, __('Save config'), true);
                $cell .= wf_CheckInput(self::ROUTER_FIELD, __('Router ONU mode'), true);

                break;
        }
        if ($this->altCfg[VlanManagement::VLANMANAGEMENT_OPTION] and $this->altCfg[VlanManagement::UNIVERSAL_QINQ_OPTION] and $this->altCfg[VlanManagement::ONUREG_QINQ_OPTION]) {
            $cell .= wf_delimiter();
            $cell .= __('Universal QINQ') . wf_delimiter();
            if (cfr('UNIVERSALQINQCONFIG')) {
                $labels = array(
                    'cvlan' => __('Use') . ' ' . __('Universal QINQ') . ' CVLAN',
                    'pair' => __('Use') . ' ' . __('Universal QINQ pair'),
                    'cvlan_pool' => __('Use') . ' QINQ CVLAN ' . __('pool'),
                    'pair_pool' => __('Use') . ' ' . __('QINQ pair') . ' ' . __('pool')
                );
                $vlans = $this->getQinqPairPool();
                $cells = wf_TableCell(wf_RadioInput(self::GET_UNIVERSALQINQ, __('Do not use QINQ'), self::GET_UNIVERSALQINQ_NONE, true, true));
                $row = wf_TableRow($cells);
                $cells = wf_TableCell(wf_RadioInput(self::GET_UNIVERSALQINQ, $labels['cvlan'], self::GET_UNIVERSALQINQ_CVLAN, true, false));
                $cells .= wf_TableCell('');
                $row .= wf_TableRow($cells);
                $cells = wf_TableCell(wf_RadioInput(self::GET_UNIVERSALQINQ, $labels['pair'], self::GET_UNIVERSALQINQ_PAIR, true, false));
                $cells .= wf_TableCell('');
                $row .= wf_TableRow($cells);
                if (!empty($vlans)) {
                    $cells = wf_TableCell(wf_RadioInput(self::GET_UNIVERSALQINQ, $labels['cvlan_pool'], self::GET_UNIVERSALQINQ_CVLAN_POOL, false, false));
                    $cells .= wf_TableCell('VLAN: (' . $vlans['cvlan'] . ')');
                    $row .= wf_TableRow($cells);
                    $cells = wf_TableCell(wf_RadioInput(self::GET_UNIVERSALQINQ, $labels['pair_pool'], self::GET_UNIVERSALQINQ_PAIR_POOL, false, false));
                    $cells .= wf_TableCell('VLAN: (' . $vlans['svlan'] . '/' . $vlans['cvlan'] . ')');
                    $row .= wf_TableRow($cells);
                }
                $cell .= wf_TableBody($row);
            }
        }

        $cell .= wf_delimiter();
        $cell .= wf_Submit(__('Register'));
        $Row = wf_TableRow($cell, 'row1');
        $form = wf_Form('', 'POST', $Row, 'glamour');

        return ($form);
    }

}
