<?php

/**
 * Class for registering ONU/ONT on ZTE OLTs.
 */
class OnuRegister {

    const MODULE_CONFIG = 'ONUREG_ZTE';
    const VLAN_MODULE_RIGHTS = 'ZTEVLANBINDS';
    const REG_MODULE_RIGHTS = 'ONUREGZTE';
    const VLAN_MODULE_URL = '?module=ztevlanbinds';
    const MODULE_URL_EDIT_CARD = '?module=ztevlanbinds&edit_card=';
    const MODULE_URL_EDIT_BIND = '?module=ztevlanbinds&edit_bind=';
    const UNREG_URL = '?module=zteunreg';
    const UNREG_OLTLIST_URL = '?module=zteunreg&oltlist=true';
    const UNREG_ACT_URL = '?module=zteunreg&register=true&oltip=';
    const UNREG_MASS_FIX_PREVIEW_URL = '?module=zteunreg&massfix=true&preview=true';
    const UNREG_MASS_FIX_URL = '?module=zteunreg&massfix=true';
    const UNREG_MASS_FIX_ACT_URL = '?module=zteunreg&massfix=true&oltid=';
    const CARDS_TABLE = 'zte_cards';
    const BIND_TABLE = 'zte_vlan_bind';
    const PORT_ID_START = 268501248;
    const ONU_ID_START = 805830912;
    const ALT_ONU_ID_START = 2416967936;
    const GPON_RETRIES = 5;
    const SNMP_TEMPLATE_SECTION = 'onu_reg';
    const EMPTY_FIELD = '';
    const TYPE_FIELD = 'type';
    const INTERFACE_FIELD = 'interface';
    const OLTIP_FIELD = 'oltip';
    const OLTID_FIELD = 'swid';
    const MODELID_FIELD = 'modelid';
    const MODELID_PLACEHOLDER = '======';
    const VLAN_FIELD = 'vlan';
    const MACONU_FIELD = 'maconu';
    const MAC_ONU_FIELD = 'mac_onu';
    const SERIAL_FIELD = 'serial';
    const SN_FIELD = 'sn';
    const LOGIN_FIELD = 'login';
    const MAC_FIELD = 'mac';
    const RANDOM_MAC_FIELD = 'random_mac';
    const ROUTER_FIELD = 'router';
    const SAVE_FIELD = 'save';
    const PONIZER_ADD_FIELD = 'ponizer_add';
    const ONUDESCRIPTION_FIELD = 'onu_description';
    const DHCP_SNOOPING_FIELD = 'dhcp_snooping';
    const LOOPDETECT_FIELD = 'loopdetect';
    const ONUDESCRIPTION_AS_LOGIN_FIELD = 'onu_description_as_login';
    const GET_UNIVERSALQINQ_NONE = 'none';
    const GET_UNIVERSALQINQ_CVLAN = 'cvlan';
    const GET_UNIVERSALQINQ_PAIR = 'pair';
    const GET_UNIVERSALQINQ_CVLAN_POOL = 'cvlan_pool';
    const GET_UNIVERSALQINQ_PAIR_POOL = 'pair_pool';
    const GET_UNIVERSALQINQ = 'use_qinq';
    const NO_ERROR_CONNECTION = 'OK';
    const FIXABLE_FILE = 'exports/onureg_mass_update';
    const ERROR_NO_LOGIN_AVAILABLE = 'No connection data found. Switchlogin is empty or not set.';
    const ERROR_SNMP_CONNECTION_SET = 'SNMP connection type has set for this OLT. Use telnet/ssh instead.';
    const ERROR_NO_LICENSE = 'No license key available';
    const ERROR_NO_RIGHTS = 'Access denied';
    const ERROR_NOT_ENABLED = 'This module is disabled';
    const ERROR_WRONG_MODELID = 'Wrong modelid found. Do not use placeholder.';
    const ERROR_NOT_ALL_FIELDS = 'Some fields were not set.';
    const ERROR_NO_INTERFACE_SET = 'No interface value found.';
    const ERROR_NO_OLTIP_SET = 'No OLT IP address value found.';
    const ERROR_NO_VLAN_SET = 'No VLAN value found.';
    const ERROR_TOO_MANY_REGISTERED_ONU = 'Registered ONU count is';
    const ERROR_NEED_LICENSE_REISSUE_02 = 'Ask for new license. ETTO cards not supported for 64+ ONT installation in this license version.';
    const HUAWEI_NATIVE_VLAN_OPTION = 'ONUREG_HUAWEI_NATIVE_VLAN';
    const ERROR_ONU_EXISTS = 'ONU ALREADY EXISTS';

    /**
     * Contains all data from billing.ini
     * 
     * @var array 
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
     * Merge olt arrays into one
     * 
     * @var array
     */
    protected $allOlt = array();

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
     * Contains all ponizer ONUs
     * 
     * @var array
     */
    protected $allOnu = array();

    /**
     * Contains all onu serial numbers
     * 
     * @var array
     */
    protected $allOnuSerial = array();

    /**
     * Contains all onu mac addresses
     * 
     * @var array
     */
    protected $allOnuMac = array();

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
     * Version to handle different config within different software versions.
     * 
     * @var string 
     */
    public $currentPonVersion = "0";

    /**
     * Contains vendor name capital letters
     * Default - ZTE
     * 
     * @var string
     */
    protected $vendor = 'ZTE';

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
     * @var int
     */
    public $vlan = -1;

    /**
     * Placeholder for ONU cvlan
     * @var int
     */
    public $cvlan = -1;

    /**
     * Contains SVLAN database id.
     * @var int
     */
    protected $svlanId = -1;

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
     * Placeholder for VPORT interface name.
     * 
     * @var string
     */
    public $vportInterface = '';

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
     * Placeholder for onu serial number
     * 
     * @var string
     */
    protected $serial = '';

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
    public $useUniversalQINQ = 'none';

    /**
     * Contains onu description or '__empty'
     * 
     * @var string
     */
    public $onuDescription = '__empty';

    /**
     * Flag for enabling dhcp snooping
     * 
     * @var string 
     */
    public $onuDhcpSnooping = '__empty';

    /**
     * Flag for enabling loopdetec
     * 
     * @var string 
     */
    public $onuLoopdetect = '__empty';

    /**
     * All unreg ONU
     * @var array
     */
    protected $allUnreg = array();

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
     * Workaround for HUAWEI OLT gpon native vlan.
     * 
     * @var int
     */
    protected $nativeVlan = 0;
    protected $labels = array();

    protected $currentRunMac = '';
    protected $currentRunSerial = '';

    /**
     * Base class construction.
     * 
     * @return void
     */
    public function __construct() {
        $this->initGreed();
        $this->initMessages();
        $this->loadAllOlt();
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
        $this->labels = array(
            'cvlan' => __('Use') . ' ' . __('Universal QINQ') . ' CVLAN',
            'pair' => __('Use') . ' ' . __('Universal QINQ pair'),
            'cvlan_pool' => __('Use') . ' QINQ CVLAN ' . __('pool'),
            'pair_pool' => __('Use') . ' ' . __('QINQ pair') . ' ' . __('pool')
        );
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
                $this->allOnuSerial[$each['serial']] = $each;
                $this->allOnuMac[$each['mac']] = $each;
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
        return (
            array(
                'EPFC' => 4,
                'EPFCB' => 4,
                'ETGO' => 8,
                'ETGOD' => 8,
                'ETTO' => 8,
                'ETTOD' => 8,
                'ETTOK' => 8,
                'ETGH' => 16,
                'ETGHG' => 16,
                'ETGHK' => 16
            )
        );
    }

    /**
     * Setter for gpon cards.
     * 
     * @return array
     */
    public static function allGponCards() {
        return (
            array(
                'GPFA' => 4,
                'GPFAE' => 4,
                'GTGO' => 8,
                'GTGOG' => 8,
                'GTGOD' => 8,
                'GTGOE' => 8,
                'GTGH' => 16,
                'GTGHG' => 16,
                'GTGHK' => 16,
                'GPBD' => 8,
                'GPFD' => 16,
                'GPBH' => 8,
                'GPMD' => 8,
                'H806G' => 8,
                'H803G' => 16,
                'H805G' => 16,
                'GVGH' => 16,
                'GFGL' => 16,
                'GVGO' => 8
            )
        );
    }

    /**
     * Setter for huawei gpon cards.
     * 
     * @return array
     */
    protected static function allHuaweiCards() {
        return (
            array(
                'GPBD',
                'GPFD',
                'GPBH',
                'GPMD',
                'H806G',
                'H803G',
                'H805G'
            )
        );
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
     * Init all array per vendor and merge into single
     * 
     * @return void
     */
    protected function loadAllOlt() {
        $this->loadAllZteOlt();
        $this->loadAllHuaweiOlt();
        //fuck you array_merge
        //$this->allOlt = array_merge($this->allZteOlt, $this->allHuaweiOlt);
        $this->allOlt = $this->allZteOlt + $this->allHuaweiOlt;
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
        if (isset($this->allCards[$swid]) and !empty($this->allCards[$swid])) {
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
        if (array_search($cardName, self::allHuaweiCards())) {
            $i = 0;
            $count -= 1;
        } else {
            $i = 1;
        }
        $this->portSelector['======'] = '======';
        for ($i; $i <= $count; $i++) {
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
        if (isset($this->allCards[$this->currentOltSwId]) and !empty($this->allCards[$this->currentOltSwId])) {
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
        return ($cards);
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
                    if ($value['description'] == 'GVGH' or $value['description'] == 'GFGL' or $value['description'] == 'GVGO') {
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
                    } else {
                        eval($inherit);
                    }
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
        if (!empty($this->allOlt)) {
            foreach ($this->allOlt as $id => $eachOlt) {
                if ($eachOlt['ip'] == $ip) {
                    return ($id);
                }
            }
        }
        return -1;
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
                if ($eachBind['slot_number'] == $slot and $eachBind['port_number'] == $port) {
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
     * @param string $hex hex-string
     * 
     * @return string
     */
    protected function hexToString($hex) {
        return (@pack('H*', $hex));
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
                $mac = zb_MacGetRandom();
                $check = true;
                foreach ($this->allOnu as $io => $each) {
                    if ($each['mac'] == $mac) {
                        $check = false;
                        break;
                    }
                }
            }
        } else {
            $mac = zb_MacGetRandom();
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

        if (!empty($this->allOlt)) {
            foreach ($this->allOlt as $this->currentOltSwId => $io) {
                //skip cycle if looking only for certain OLT
                if (wf_CheckGet(array('oltlist', 'oltid'))) {
                    $oltid = ubRouting::get('oltid', 'int');
                    if ($this->currentOltSwId != $oltid) {
                        continue;
                    }
                }
                $this->oltParseUnauth();
            }
        }
    }

    /**
     * Setting vendor name to call proper function
     */
    protected function vendorSet() {
        if (isset($this->allZteOlt[$this->currentOltSwId])) {
            $this->vendor = 'ZTE';
        }
        if (isset($this->allHuaweiOlt[$this->currentOltSwId])) {
            $this->vendor = 'HUAWEI';
        }
    }

    protected function oltParseUnauth() {
        if (file_exists(CONFIG_PATH . '/snmptemplates/' . $this->allOlt[$this->currentOltSwId]['snmptemplate'])) {
            $this->currentSnmpTemplate = rcms_parse_ini_file(CONFIG_PATH . '/snmptemplates/' . $this->allOlt[$this->currentOltSwId]['snmptemplate'], true);
            $this->currentPonType = $this->currentSnmpTemplate[self::SNMP_TEMPLATE_SECTION]['TYPE'];
            $this->currentOltIp = $this->allOlt[$this->currentOltSwId]['ip'];
            $this->currentSnmpCommunity = $this->allOlt[$this->currentOltSwId]['snmp'];
            $this->vendorSet();
            $this->loadCalculatedData();
            $pollMethod = 'getAllUnauth_' . $this->currentPonType . '_' . $this->vendor;

            if (isset($this->allCards[$this->currentOltSwId]) and !empty($this->allCards[$this->currentOltSwId])) {
                $this->$pollMethod();
            }
        }
    }

    /**
     * Get unautheticated epon ONT for specified OLT.
     * 
     * @return void
     */
    protected function getAllUnauth_EPON_ZTE() {
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
    protected function getAllUnauth_GPON_ZTE() {
        $getUncfgSn = $this->currentSnmpTemplate[self::SNMP_TEMPLATE_SECTION]['UNCFGSN'];

        $allUnreg = trim($this->snmp->walk($this->currentOltIp, $this->currentSnmpCommunity, $getUncfgSn, false));
        if (!empty($allUnreg)) {
            $allUnreg = explodeRows($allUnreg);
            foreach ($allUnreg as $io => $value) {
                $value = trim($value);
                $parts = explode("=", $value);
                if (!empty($parts[1])) {
                    $interfaceIdRaw = str_replace($getUncfgSn . '.', '', $parts[0]);
                    $interfaceIdSplit = explode(".", $interfaceIdRaw);
                    $interfaceId = $interfaceIdSplit[0];

                    $fixedSn = $this->parseUncfgGpon($parts[1]);
                    foreach ($this->ponArray as $slot => $each_id) {
                        if ($each_id == $interfaceId) {
                            array_push($this->allUnreg['GPON'], array('oltip' => $this->currentOltIp, 'slot' => $slot, 'identifier' => $fixedSn, 'swid' => $this->currentOltSwId));
                        }
                    }
                }
            }
        }
    }

    /**
     * Getting unauthenticated gpon ONT from Huawei OLT.
     * 
     * @return void
     */
    protected function getAllUnauth_GPON_HUAWEI() {
        $allUnreg = @snmp2_real_walk($this->currentOltIp, $this->currentSnmpCommunity, $this->currentSnmpTemplate[self::SNMP_TEMPLATE_SECTION]['UNCFGSN']);
        $oltInterface = @snmp2_real_walk($this->currentOltIp, $this->currentSnmpCommunity, $this->currentSnmpTemplate[self::SNMP_TEMPLATE_SECTION]['INTERFACENAME']);
        if (!empty($allUnreg) and !empty($oltInterface)) {
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
     * Getting unauthenticated gpon ONT from Huawei OLT.
     * 
     * @return void
     */
    protected function getAllUnauth_EPON_HUAWEI() {
        //TODO
    }

    /**
     * Parse and transform raw snmp data into suitable array.
     * 
     * @param string $uncfgSn
     * @param string $interfaceId
     * 
     * @return string
     */
    protected function parseUncfgGpon($rawSn) {
        $sn = '';
        $rawSn = trim($rawSn);
        $tmpSn = explode(" ", $rawSn);
        $check = trim($tmpSn[0]);
        $tmpStr = '';
        if ($check == 'STRING:') {
            $sn = $this->serialNumberBinaryParse($tmpSn[1]);
        } else {
            $tmp[0] = $tmpSn[1];
            $tmp[1] = $tmpSn[2];
            $tmp[2] = $tmpSn[3];
            $tmp[3] = $tmpSn[4];
            @$tmp[4] = $tmpSn[5] . $tmpSn[6] . $tmpSn[7] . $tmpSn[8];
            $sn = $this->hexToString($tmp[0]) . $this->hexToString($tmp[1]) . $this->hexToString($tmp[2]) . $this->hexToString($tmp[3]) . $tmp[4];
        }
        return ($sn);
    }

    /**
     * Parsing serial number in binary format and coverting it to needed format.
     * 
     * @param array $rawSn
     * 
     * @return string
     */
    protected function serialNumberBinaryParse($rawSn) {
        $parts = array();
        $hexSn = bin2hex($rawSn);
        if (strlen($hexSn) == 20) {
            $parts[0] = $this->serialNumberPartsTranslate($hexSn[2] . $hexSn[3]);
            $parts[1] = $this->serialNumberPartsTranslate($hexSn[4] . $hexSn[5]);
            $parts[2] = $this->serialNumberPartsTranslate($hexSn[6] . $hexSn[7]);
            $parts[3] = $this->serialNumberPartsTranslate($hexSn[8] . $hexSn[9]);
            $parts[4] = '';
            for ($i = 10; $i <= 17; $i++) {
                $parts[4] .= $hexSn[$i];
            }
        } elseif (strlen($hexSn) == 22) {
            $parts[0] = $this->serialNumberPartsTranslate($hexSn[2] . $hexSn[3]);
            $parts[1] = $this->serialNumberPartsTranslate($hexSn[4] . $hexSn[5]);
            $parts[2] = $this->serialNumberPartsTranslate($hexSn[6] . $hexSn[7]);
            $parts[3] = $this->serialNumberPartsTranslate($hexSn[8] . $hexSn[9]);
            $parts[4] = '';
            for ($i = 14; $i <= 19; $i++) {
                $parts[4] .= $hexSn[$i];
            }
        } else {
            $parts[0] = $this->serialNumberPartsTranslate($hexSn[0] . $hexSn[1]);
            $parts[1] = $this->serialNumberPartsTranslate($hexSn[2] . $hexSn[3]);
            $parts[2] = $this->serialNumberPartsTranslate($hexSn[4] . $hexSn[5]);
            $parts[3] = $this->serialNumberPartsTranslate($hexSn[6] . $hexSn[7]);
            $parts[4] = '';
            for ($i = 8; $i <= 15; $i++) {
                $parts[4] .= $hexSn[$i];
            }
        }
        $result = strtolower(implode("", $parts));
        return ($result);
    }

    /**
     * Check mode to convert serial number string vs raw.
     * 
     * @param string $part
     * 
     * @return string
     */
    protected function serialNumberPartsTranslate($part) {
        if ($this->currentSnmpTemplate['signal']['SNMODE'] == 'STRING') {
            return ($this->hexToString($part));
        }
        return ($part);
    }

    /**
     * Magic constant returns function name to concat it with pon type
     * 
     * @return void
     */
    protected function checkRegisteredOnu() {
        $callable = __FUNCTION__ . $this->currentPonType;
        $this->$callable();
    }

    protected function checkRegisteredOnuGPON() {
        $callable = __FUNCTION__ . $this->vendor;
        $this->$callable();
        $allID = range(1, 128);
        $free = array_diff($allID, $this->existId);
        reset($free);
        $this->lastOnuId = current($free);

    }
    protected function checkRegisteredOnuEPON() {
        $callable = __FUNCTION__ . $this->vendor;
        $this->$callable();
    }

    protected function checkRegisteredOnuGPONZTE() {
        $getAllId = @snmp2_real_walk($this->currentOltIp, $this->currentSnmpCommunity, $this->currentSnmpTemplate[self::SNMP_TEMPLATE_SECTION]['LLIDLIST'] . $this->ponArray[$this->currentOltInterface]);
        if (!empty($getAllId)) {
            foreach ($getAllId as $oid => $value) {
                if ($this->currentSnmpTemplate[self::SNMP_TEMPLATE_SECTION]['VERSION'] == 2 or $this->currentSnmpTemplate[self::SNMP_TEMPLATE_SECTION]['VERSION'] == "C6XX") {
                    $number = str_replace($this->currentSnmpTemplate[self::SNMP_TEMPLATE_SECTION]['LLIDLIST'] . $this->ponArray[$this->currentOltInterface] . '.', "", $oid);
                    $this->existId[] = trim($number);
                } else {
                    $number = explode(':', $value);
                    $number = trim($number[1]);
                    $this->existId[] = $number;
                }
            }
        }
    }
    protected function checkRegisteredOnuEPONZTE() {
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
    protected function checkRegisteredOnuGPONHUAWEI() {
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
    protected function checkRegisteredOnuEPONHUAWEI() {
        //todo one day it will be added
    }

    /**
     * Used to change mac format from xx:xx:xx:xx:xx:xx to xxxx.xxxx.xxxx
     *      
     * @return string
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
        if (isset($this->allOlt[$this->currentOltSwId])) {
            $this->vendorSet();
            $this->currentSnmpCommunity = $this->allOlt[$this->currentOltSwId]['snmp'];
            $snmpTemplateName = $this->allOlt[$this->currentOltSwId]['snmptemplate'];

            $this->loadCalculatedData();

            if (!empty($this->allSwLogin) and isset($this->allSwLogin[$this->currentOltSwId])) {
                if (file_exists(CONFIG_PATH . '/snmptemplates/' . $snmpTemplateName)) {
                    $this->currentSnmpTemplate = rcms_parse_ini_file(CONFIG_PATH . '/snmptemplates/' . $snmpTemplateName, true);
                    if (isset($this->currentSnmpTemplate[self::SNMP_TEMPLATE_SECTION]['VERSION'])) {
                        $this->currentPonVersion = $this->currentSnmpTemplate[self::SNMP_TEMPLATE_SECTION]['VERSION'];
                    }
                    if ($this->currentPonType == 'EPON') {
                        $this->serial = '';
                        $this->addMac = $this->onuIdentifier;
                        $this->onuIdentifier = $this->transformMac();
                    }
                    if ($this->currentPonType == 'GPON') {
                        $this->onuIdentifier = strtoupper($this->onuIdentifier);
                        $this->serial = $this->onuIdentifier;
                    }
                    $this->checkRegisteredOnu();

                    //Exit if onu number is 65+ for epon and 128+ for gpon.
                    if (!$this->onuCountControl()) {
                        exit();
                    }

                    //Executing register and save config scripts
                    $this->sendRegisterCommand();
                    $log_str = 'ONUREG REGISTER ONU. ONU ID: ' . $this->onuIdentifier;
                    $log_str .= ' OLT IP: ' . $this->currentOltIp;
                    $log_str .= ' OLT INTERFACE: ' . $this->currentOltInterface;
                    $log_str .= ' ONU NUMBER: ' . $this->lastOnuId;
                    log_register($log_str);
                    $this->qinqControl();
                    $this->ponizerControl();
                }
            }
        }

    }


    /**
     * Check options before running scripts
     * 
     * @return void
     */
    protected function sendRegisterCommand() {
        if ($this->useUniversalQINQ != 'none' and $this->login) {
            $this->scriptExec();
        }
        if ($this->useUniversalQINQ == 'none') {
            $this->scriptExec();
        }
    }

    /**
     * Execute script
     */
    protected function scriptExec() {
        $this->result .= shell_exec($this->getRegisterOnuCommand());
        $this->result .= shell_exec($this->getSaveConfigCommand());
        $this->result = nl2br($this->result);
    }

    /**
     * Check if all options are set to get qinq binding
     * 
     * @return void
     */
    protected function qinqControl() {
        if ($this->useUniversalQINQ != 'none') {
            if ($this->login) {
                if ($this->useUniversalQINQ == self::GET_UNIVERSALQINQ_CVLAN_POOL or $this->useUniversalQINQ == self::GET_UNIVERSALQINQ_PAIR_POOL) {
                    $this->createQinqBinding();
                }
            }
        }
    }

    /**
     * Write qinq binding to database
     * 
     * @return void
     */
    protected function createQinqBinding() {
        $universalQuery = "INSERT INTO `qinq_bindings` (`id`,`login`,`svlan_id`,`cvlan`) VALUES (NULL,'" . $this->login . "'," . $this->svlanId . ',' . $this->cvlan . ')';
        nr_query($universalQuery);

    }

    /**
     * Delete qinq binding by login
     * 
     * @return void
     */
    protected function deleteQinqBinding() {
        $universalQuery = "DELETE FROM `qinq_bindings` WHERE `login`='" . $this->login . '"';
        nr_query($universalQuery);
    }

    /**
     * Count based on pon type onu count limits
     * 
     * @return bool
     */
    protected function onuCountControl() {
        if ($this->currentPonType == 'EPON') {
            $intParts = explode("/", $this->currentOltInterface);
            $slot = $intParts[1];
            foreach ($this->allCards[$this->currentOltSwId] as $each => $io) {
                if ($io['slot_number'] == $slot) {
                    if ($io['card_name'] == "ETTO" or $io['card_name'] == "ETTOK") {
                        if ($this->avidity['VERSION'] != '0.0.1') {
                            if (count($this->existId) >= 128) {
                                $this->error = self::ERROR_TOO_MANY_REGISTERED_ONU;
                                return (false);
                            }
                        } else {
                            if (count($this->existId) >= 64) {
                                $this->error = self::ERROR_NEED_LICENSE_REISSUE_02;
                                return (false);
                            }
                        }
                    } else {
                        if (count($this->existId) >= 64) {
                            $this->error = self::ERROR_TOO_MANY_REGISTERED_ONU;
                            return (false);
                        }
                    }
                }
            }
        }

        if ($this->currentPonType == 'GPON') {
            if (count($this->existId) >= 128) {
                $this->error = self::ERROR_TOO_MANY_REGISTERED_ONU;
                return (false);
            }
        }
        return (true);
    }

    /**
     * Add onu to PONizer if options was set.
     * Check if ONU has unique paramteres.
     * 
     * @return void
     */
    protected function ponizerControl() {
        switch ($this->currentPonType) {
            case 'GPON':
                if ($this->checkSerialOnuExists($this->serial)) {
                    if ($this->ponizerAdd) {
                        $this->error = self::ERROR_ONU_EXISTS . ' SN: ' . $this->serial;
                    }
                    $this->ponizerAdd = false;
                }
                break;
            case 'EPON':
                if ($this->checkMacOnuExists($this->addMac)) {
                    if ($this->ponizerAdd) {
                        $this->error = self::ERROR_ONU_EXISTS . ' MAC: ' . $this->addMac;
                    }
                    $this->ponizerAdd = false;
                }
                break;
        }

        if ($this->ponizerAdd) {
            if (!empty($this->addMac)) {
                $pon = new PONizer();
                $pon->onuCreate($this->onuModel, $this->currentOltSwId, '', $this->addMac, $this->serial, $this->login);
            } else {
                log_register('ONUREG PONIZER WRONG DATA. Login: ' . $this->login . '. MAC: ' . $this->addMac);
            }
        }
    }

    /**
     * Compose path to scripts.
     * 
     * @return string $scriptPath
     */
    protected function getRegisterOnuScriptPath() {
        $scriptPath = CONFIG_PATH . 'scripts/onureg/';
        if (isset($this->allHuaweiOlt[$this->currentOltSwId])) {
            $scriptPath .= 'huawei/';
            $splitName = explode(' ', $this->currentOltInterface);
            $splitInterface = explode('/', $splitName[1]);
            $this->currentOltInterface = $splitInterface[0] . '/' . $splitInterface[1];
            $this->onuInterface = $splitInterface[2];
            $this->nativeVlan = $this->altCfg[self::HUAWEI_NATIVE_VLAN_OPTION];
        } else {
            $scriptPath .= 'zte/';
            $this->onuInterface = str_replace('olt', 'onu', $this->currentOltInterface);
            if ($this->currentPonVersion == 2) {
                $scriptPath .= 'v2/';
            } elseif ($this->currentPonVersion == "C6XX") {
                $this->vportInterface = str_replace('gpon_olt', 'vport', $this->currentOltInterface);
                $scriptPath .= 'C6XX/';
            } else {
                $scriptPath .= 'v1.2.5/';
            }
        }
        $scriptPath .= $this->currentPonType . '_' . $this->onuModels[$this->onuModel]['ports'];
        if ($this->router) {
            $scriptPath .= '_R';
        }
        if ($this->useUniversalQINQ == self::GET_UNIVERSALQINQ_CVLAN or $this->useUniversalQINQ == self::GET_UNIVERSALQINQ_CVLAN_POOL) {
            $scriptPath .= '_CVLAN';
        }
        if ($this->useUniversalQINQ == self::GET_UNIVERSALQINQ_PAIR or $this->useUniversalQINQ == self::GET_UNIVERSALQINQ_PAIR_POOL) {
            $scriptPath .= '_QINQ';
        }

        return ($scriptPath);
    }

    /**
     * Check if user has assiigned qinq pair and which mode was set for registration.
     */
    protected function checkQinq() {
        if ($this->useUniversalQINQ == self::GET_UNIVERSALQINQ_CVLAN or $this->useUniversalQINQ == self::GET_UNIVERSALQINQ_PAIR) {
            if (isset($this->usersQinQ[$this->login])) {
                $user = $this->usersQinQ[$this->login];
                $this->vlan = $user['cvlan'];
                $this->cvlan = $user['cvlan'];
                if ($this->useUniversalQINQ == self::GET_UNIVERSALQINQ_PAIR) {
                    $this->svlan = $this->allSvlan[$user['svlan_id']]['svlan'];
                    $this->vlan .= ' ' . $this->svlan;
                }
            }
        }
        if ($this->useUniversalQINQ == self::GET_UNIVERSALQINQ_CVLAN_POOL or $this->useUniversalQINQ == self::GET_UNIVERSALQINQ_PAIR_POOL) {
            $vlans = $this->getQinqPairPool();
            if (!empty($vlans)) {
                $this->vlan = $vlans['cvlan'];
                $this->cvlan = $vlans['cvlan'];
                if ($this->useUniversalQINQ == self::GET_UNIVERSALQINQ_PAIR_POOL) {
                    $this->vlan .= ' ' . $vlans['svlan'];
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
            if (isset($this->allHuaweiOlt[$this->currentOltSwId])) {
                $command .= ' ' . $this->nativeVlan;
                $command .= ' ' . $this->servicePort;
            }
            $command .= ' ' . $this->onuDescription;
            $command .= ' ' . $this->onuDhcpSnooping;
            $command .= ' ' . $this->onuLoopdetect;
            if ($this->currentPonVersion == "C6XX") {
                $command .= ' ' . $this->vportInterface;
            }
        }
        return ($command);
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
            $command .= ' ' . CONFIG_PATH . 'scripts/onureg/';
            if (isset($this->allHuaweiOlt[$this->currentOltSwId])) {
                $command .= 'huawei/';
            } else {
                $command .= 'zte/';
                if ($this->currentPonVersion == 2) {
                    $command .= 'v2/';
                } elseif ($this->currentPonVersion == "C6XX") {
                    $command .= 'C6XX/';
                } else {
                    $command .= 'v1.2.5/';
                }
            }
            $command .= 'save';
            $command .= ' ' . $this->currentOltIp;
            $command .= ' ' . $this->allSwLogin[$this->currentOltSwId]['swlogin'];
            $command .= ' ' . $this->allSwLogin[$this->currentOltSwId]['swpass'];
            $command .= ' ' . $this->allSwLogin[$this->currentOltSwId]['method'];
        }
        return ($command);
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
        if (isset($this->allCards[$swid]) and !empty($this->allCards[$swid])) {
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
     * @param string $swid
     * @param string $slot
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
                if ($eachBind['slot_number'] == $slot and $eachBind['port_number'] == $port) {
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
        return (true);
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
    public function listAllZteDevices($vlanbind = true) {
        $tablecells = wf_TableCell(__('ID'));
        $tablecells .= wf_TableCell(__('OLT IP'));
        $tablecells .= wf_TableCell(__('Description'));
        $tablecells .= wf_TableCell(__('Actions'));
        $tablerows = wf_TableRow($tablecells, 'row1');

        if (!empty($this->allOlt)) {
            foreach ($this->allOlt as $eachNumber => $eachOlt) {
                $tablecells = wf_TableCell($eachOlt['id']);
                $tablecells .= wf_TableCell($eachOlt['ip']);
                $tablecells .= wf_TableCell($eachOlt['desc'] . ' | ' . $eachOlt['location']);
                if ($vlanbind) {
                    $actionLinks = wf_Link(self::MODULE_URL_EDIT_CARD . $eachOlt['id'], wf_img('skins/chasis.png', __('Edit cards')), false);
                    $actionLinks .= wf_Link(self::MODULE_URL_EDIT_BIND . $eachOlt['id'], wf_img('skins/bind.png', __('Edit VLAN bindings')), false);
                } else {
                    $actionLinks = wf_Link(self::UNREG_OLTLIST_URL . '&oltid=' . $eachOlt['id'], wf_img('skins/check.png', __('Check for unauthenticated ONU/ONT')), false);
                    $actionLinks .= wf_Link(self::UNREG_MASS_FIX_ACT_URL . $eachOlt['id'], wf_img('skins/brain.png', 'OLT ' . __('fix')), false);
                }
                $tablecells .= wf_TableCell($actionLinks);
                $tablerows .= wf_TableRow($tablecells, 'row5');
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

        if (!empty($this->allOlt)) {
            if (isset($this->allOlt[$oltid])) {
                $eachOlt = $this->allOlt[$oltid];
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

        if (isset($this->allCards[$swid]) and !empty($this->allCards[$swid])) {
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

        if (isset($this->allCards[$swid]) and !empty($this->allCards[$swid])) {
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

        if (!empty($this->allOlt)) {
            if (isset($this->allOlt[$swid])) {
                $oltData = $this->allOlt[$swid];
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
                            if (isset($snmpTemplate[self::SNMP_TEMPLATE_SECTION]['VERSION']) and $snmpTemplate[self::SNMP_TEMPLATE_SECTION]['VERSION'] == "C6XX") {
                                $tablecells = wf_TableCell($eachOid[0]);
                            } else {
                                $tablecells = wf_TableCell($eachOid[2]);
                            }
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
                $tablecells .= wf_TableCell($oltType[$eachBind['swid']]['ip']);
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
            if (!empty($this->allOlt)) {
                $tablerows .= $this->getListZteBind($this->allOlt, $swid);
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
        if (wf_CheckGet(array('oltlist', 'oltid'))) {
            $oltlist = '&oltlist=true&oltid=' . ubRouting::get('oltid', 'int');
        } else {
            $oltlist = '';
        }
        $tablecells = wf_TableCell(__('OLT IP'));
        $tablecells .= wf_TableCell(__('Type'));
        $tablecells .= wf_TableCell(__('Interface'));
        $tablecells .= wf_TableCell('MAC/SN');
        $tablecells .= wf_TableCell(__('PONizer'));
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
                    $existFlag = false;
                    switch ($eachType) {
                        case 'GPON':
                            $identifier = '&' . self::SERIAL_FIELD . '=';
                            if ($this->checkSerialOnuExists($macOnu)) {
                                $existFlag = true;
                            }
                            break;
                        case 'EPON':
                            $identifier = '&' . self::MACONU_FIELD . '=';
                            if ($this->checkMacOnuExists($macOnu)) {
                                $existFlag = true;
                            }
                            break;
                    }
                    if ($existFlag) {
                        $existParams = wf_img('skins/icon_ok.gif', __('Good'));
                    } else {
                        $existParams = wf_img('skins/icon_minus.png', __('Bad'));
                    }
                    $tablecells .= wf_TableCell($existParams);
                    $actionLinks = wf_Link(self::UNREG_ACT_URL . $ip . $oltlist . '&interface=' . $interface . $identifier . $macOnu . '&type=' . $eachType . '&swid=' . $oltId, wf_img('skins/add_icon.png', __('Register')), false);
                    $tablecells .= wf_TableCell($actionLinks);
                    $tablerows .= wf_TableRow($tablecells, 'row3');
                }
            }
        }
        $result = wf_TableBody($tablerows, '100%', '0', 'sortable');
        $result .= wf_delimiter();

        return ($result);
    }

    /**
     * Fetching all qinq bindings by svlan_id.
     * 
     * @param int $svlanId
     * 
     * @return array
     */
    protected function getAllUniversalCvlans($svlanId) {
        $query = "SELECT * FROM `qinq_bindings` WHERE `svlan_id`=" . $svlanId;
        $data = simple_queryall($query);
        if (!empty($data)) {
            foreach ($data as $io => $each) {
                $result[] = $each['cvlan'];
            }
            return ($result);
        }
        return (array());
    }

    /**
     * Find current card name.
     * 
     * @return string
     */
    protected function getCardName($interface = '', $swid = '') {
        if (!$interface and !$swid) {
            if (wf_CheckGet(array('interface', 'swid'))) {
                $swid = ubRouting::get('swid', 'mres');
                $interface = ubRouting::get('interface', 'mres');
            }

        }
        $intParts = explode("/", $interface);
        $slot = mysql_real_escape_string($intParts[1]);

        $query = "SELECT * FROM `zte_cards` WHERE `swid`=" . $swid . ' AND slot_number=' . $slot;
        $cardData = simple_query($query);
        return ($cardData['card_name']);
    }

    /**
     * Find qinq pair pool
     * 
     * @return array
     */
    protected function getQinqPairPool($interface = '', $swid = '') {
        $qinqBind = '';
        $result = array();
        if (!$interface and !$swid) {
            if (wf_CheckGet(array('interface', 'swid'))) {
                $intParts = explode("/", $_GET['interface']);
                $slot = mysql_real_escape_string($intParts[1]);
                $port = mysql_real_escape_string($intParts[2]);
                $swid = vf($_GET['swid'], 3);
                $cardName = $this->getCardName();
                $query = "SELECT * FROM `zte_qinq` WHERE `swid`=" . $swid . ' AND `slot_number`=' . $slot . ' AND `port`=' . $port;
                $qinqBind = simple_query($query);
            }
        } else {
            $intParts = explode("/", $interface);
            $slot = mysql_real_escape_string($intParts[1]);
            $port = mysql_real_escape_string($intParts[2]);
            $cardName = $this->getCardName($interface, $swid);
            $query = "SELECT * FROM `zte_qinq` WHERE `swid`=" . $swid . ' AND `slot_number`=' . $slot . ' AND `port`=' . $port;
            $qinqBind = simple_query($query);
        }
        if (!empty($qinqBind)) {
            $cvlan = $qinqBind['cvlan'];
            $this->svlanId = $qinqBind['svlan_id'];
            $query = "SELECT * FROM `qinq_svlan` WHERE `id`=" . $this->svlanId;
            $vlanData = simple_query($query);
            if (!empty($vlanData)) {
                $maxOnuCount = 128;
                if ($this->currentPonType == 'EPON') {
                    if ($cardName != 'ETTO' and $cardName != 'ETTOK') {
                        $maxOnuCount = 64;
                    }
                }
                $lastCvlan = $cvlan + $maxOnuCount - 1;
                $allUniversal = $this->getAllUniversalCvlans($this->svlanId);
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
        return ($result);
    }

    /**
     * Web form for register onu.
     * 
     * @return string
     */
    public function registerOnuForm() {
        $this->loadOnuModelSelector();
        $vlan = $this->getBindVlan();
        $cell = '<script src="./modules/jsc/vlanmanagement.js" type="text/javascript"></script>';

        switch ($this->currentPonType) {
            case 'EPON':
                $cell .= wf_HiddenInput(self::TYPE_FIELD, $this->currentPonType);
                $cell .= wf_HiddenInput(self::INTERFACE_FIELD, $this->currentOltInterface);
                $cell .= wf_HiddenInput(self::OLTIP_FIELD, $this->currentOltIp);
                $cell .= wf_HiddenInput(self::MAC_FIELD, $this->onuIdentifier);
                $cell .= wf_HiddenInput(self::OLTID_FIELD, $this->currentOltSwId);
                $cell .= wf_Selector(self::MODELID_FIELD, $this->onuModelsSelector, __('Choose ONU model'), '', true);
                $cell .= wf_TextInput(self::ONUDESCRIPTION_FIELD, __('ONU description'), '', true);
                $cell .= wf_TextInput(self::VLAN_FIELD, 'VLAN', $vlan, true);
                if ($this->altCfg[VlanManagement::VLANMANAGEMENT_OPTION] and $this->altCfg[VlanManagement::UNIVERSAL_QINQ_OPTION] and $this->altCfg[VlanManagement::ONUREG_QINQ_OPTION]) {
                    $paramString = "this.value,'" . $_GET['interface'] . "'," . $_GET['swid'];
                    $cell .= wf_TextInput(self::LOGIN_FIELD, __('Login'), '', true, '', '', '', '', 'oninput="getQinqByLogin(' . $paramString . ');"');
                } else {
                    $cell .= wf_TextInput(self::LOGIN_FIELD, __('Login'), '', true);
                }
                $cell .= wf_CheckInput(self::ONUDESCRIPTION_AS_LOGIN_FIELD, __('ONU description same as login'), true, false);
                $cell .= wf_CheckInput(self::PONIZER_ADD_FIELD, __('Add ONU to PONizer'), true, true);
                $cell .= wf_Tag('br');
                $cell .= wf_CheckInput(self::SAVE_FIELD, __('Save config'), true);

                break;
            case 'GPON':
                $cell .= wf_HiddenInput(self::TYPE_FIELD, $this->currentPonType);
                $cell .= wf_HiddenInput(self::INTERFACE_FIELD, $this->currentOltInterface);
                $cell .= wf_HiddenInput(self::OLTIP_FIELD, $this->currentOltIp);
                $cell .= wf_HiddenInput(self::SN_FIELD, $this->onuIdentifier);
                $cell .= wf_HiddenInput(self::OLTID_FIELD, $this->currentOltSwId);
                $cell .= wf_Selector(self::MODELID_FIELD, $this->onuModelsSelector, __('Choose ONU model'), '', true);
                $cell .= wf_TextInput(self::ONUDESCRIPTION_FIELD, __('ONU description'), '', true);
                $cell .= wf_TextInput(self::VLAN_FIELD, 'VLAN', $vlan, true);
                if ($this->altCfg[VlanManagement::VLANMANAGEMENT_OPTION] and $this->altCfg[VlanManagement::UNIVERSAL_QINQ_OPTION] and $this->altCfg[VlanManagement::ONUREG_QINQ_OPTION]) {
                    $paramString = "this.value,'" . $_GET['interface'] . "'," . $_GET['swid'];
                    $cell .= wf_TextInput(self::LOGIN_FIELD, __('Login'), '', true, '', '', '', '', 'oninput="getQinqByLogin(' . $paramString . ');"');
                } else {
                    $cell .= wf_TextInput(self::LOGIN_FIELD, __('Login'), '', true);
                }
                $cell .= wf_TextInput(self::MAC_ONU_FIELD, __('MAC ONU for PONizer'), '', true);
                $cell .= wf_CheckInput(self::RANDOM_MAC_FIELD, __('Generate random mac'), true, true);
                $cell .= wf_CheckInput(self::ONUDESCRIPTION_AS_LOGIN_FIELD, __('ONU description same as login'), true, false);
                $cell .= wf_CheckInput(self::PONIZER_ADD_FIELD, __('Add ONU to PONizer'), true, true);
                $cell .= wf_Tag('br');
                $cell .= wf_CheckInput(self::SAVE_FIELD, __('Save config'), true);
                $cell .= wf_CheckInput(self::ROUTER_FIELD, __('Router ONU mode'), true);
                $cell .= wf_CheckInput(self::DHCP_SNOOPING_FIELD, __('Enable DHCP snooping') . '?', false, $this->altCfg['ONUREG_DHCP_SNOOPING_DEFAULT']);
                $cell .= wf_CheckInput(self::LOOPDETECT_FIELD, __('Enable loopdetect') . '?', false, $this->altCfg['ONUREG_LOOPDETECT_DEFAULT']);

                break;
        }

        $cell .= wf_tag('div', false, '', 'id="qinqcontainer"') . $this->qinqForm() . wf_tag('div', true);
        $cell .= wf_delimiter();
        $cell .= wf_Submit(__('Register'));
        $Row = wf_TableRow($cell, 'row1');
        $form = wf_Form('', 'POST', $Row, 'glamour');

        return ($form);
    }

    public function getQinqByLogin($login = '') {
        $result = array('svlan' => 'none', 'cvlan' => 'none');
        if (!empty($login)) {
            $login = mysql_real_escape_string($login);
            $query = "SELECT * FROM `qinq_bindings` WHERE `login`='" . $login . "'";
            $data = simple_query($query);
            if (!empty($data)) {
                $svlanQuery = "SELECT * FROM `qinq_svlan` WHERE `id`='" . $data['svlan_id'] . "'";
                $svlanData = simple_query($svlanQuery);
                if (!empty($svlanData)) {
                    $result['svlan'] = $svlanData['svlan'];
                    $result['cvlan'] = $data['cvlan'];
                    $result['cell1'] = wf_RadioInput(self::GET_UNIVERSALQINQ, $this->labels[self::GET_UNIVERSALQINQ_CVLAN], self::GET_UNIVERSALQINQ_CVLAN, true, false);
                    $result['cell2'] = 'VLAN: (' . $data['cvlan'] . ')';
                    $result['cell3'] = wf_RadioInput(self::GET_UNIVERSALQINQ, $this->labels[self::GET_UNIVERSALQINQ_PAIR], self::GET_UNIVERSALQINQ_PAIR, true, false);
                    $result['cell4'] = 'VLAN: (' . $svlanData['svlan'] . '/' . $data['cvlan'] . ')';
                }
            }
        }
        $result['main'] = $this->qinqForm();
        $json = json_encode($result);
        return ($json);
    }

    protected function qinqForm() {
        $cell = '';
        $cells = '';
        $row = '';
        if ($this->altCfg[VlanManagement::VLANMANAGEMENT_OPTION] and $this->altCfg[VlanManagement::UNIVERSAL_QINQ_OPTION] and $this->altCfg[VlanManagement::ONUREG_QINQ_OPTION]) {
            $cell .= wf_delimiter();
            $cell .= __('Universal QINQ') . wf_delimiter();
            if (cfr('UNIVERSALQINQCONFIG')) {
                $vlans = $this->getQinqPairPool();
                $cells = wf_TableCell(wf_RadioInput(self::GET_UNIVERSALQINQ, __('Do not use QINQ'), self::GET_UNIVERSALQINQ_NONE, true, true));
                $row .= wf_TableRow($cells);
                if (!empty($vlans)) {
                    $cells = wf_TableCell(wf_RadioInput(self::GET_UNIVERSALQINQ, $this->labels[self::GET_UNIVERSALQINQ_CVLAN_POOL], self::GET_UNIVERSALQINQ_CVLAN_POOL, false, false));
                    $cells .= wf_TableCell('VLAN: (' . $vlans['cvlan'] . ')');
                    $row .= wf_TableRow($cells);
                    $cells = wf_TableCell(wf_RadioInput(self::GET_UNIVERSALQINQ, $this->labels[self::GET_UNIVERSALQINQ_PAIR_POOL], self::GET_UNIVERSALQINQ_PAIR_POOL, false, false));
                    $cells .= wf_TableCell('VLAN: (' . $vlans['svlan'] . '/' . $vlans['cvlan'] . ')');
                    $row .= wf_TableRow($cells);
                }
                $cell .= wf_TableBody($row, '', '', '', 'id="qinqoptions"');
            }
        }

        return ($cell);
    }

    public function universalQinqForm($login) {
        $row = '';
        $vlans = $this->getQinqByLogin($login);
        if (!empty($vlans)) {
            $cells = wf_TableCell(wf_RadioInput(self::GET_UNIVERSALQINQ, $this->labels[self::GET_UNIVERSALQINQ_CVLAN], self::GET_UNIVERSALQINQ_CVLAN, true, false));
            $cells .= wf_TableCell('VLAN: (' . $vlans['cvlan'] . ')');
            $row .= wf_TableRow($cells);
            $cells = wf_TableCell(wf_RadioInput(self::GET_UNIVERSALQINQ, $this->labels[self::GET_UNIVERSALQINQ_PAIR], self::GET_UNIVERSALQINQ_PAIR, true, false));
            $cells .= wf_TableCell('VLAN: (' . $vlans['svlan'] . '/' . $vlans['cvlan'] . ')');
            $row .= wf_TableRow($cells);
        }
        return ($row);
    }

    public function listFixable() {
        $list = $this->getFixable();
        debarr($list);
    }

    protected function getFixable() {
        $trueArray = array();
        $this->getAllUnauth();
        if (!empty($this->allUnreg)) {
            foreach ($this->allUnreg as $eachType => $io) {
                foreach ($io as $eachOnu) {
                    $this->currentOltInterface = $eachOnu['slot'];
                    $this->currentOltSwId = $eachOnu['swid'];
                    $eachOnu['ponizer'] = 0;
                    $eachOnu['qinq'] = 0;
                    $vlans = $this->getQinqPairPool($eachOnu['slot'], $eachOnu['swid']);
                    if (!empty($vlans)) {
                        $eachOnu['svlan'] = $vlans['svlan'];
                        $eachOnu['cvlan'] = $vlans['cvlan'];
                        $eachOnu['qinq'] = 1;
                    } else {
                        $vlans = $this->getBindVlan();
                        $eachOnu['svlan'] = 0;
                        $eachOnu['cvlan'] = $vlans;
                    }
                    switch ($eachType) {
                        case 'GPON':
                            if ($this->checkSerialOnuExists(strtolower($eachOnu['identifier']))) {
                                $eachOnu['ponizer'] = 1;
                            }
                            break;
                        case 'EPON':
                            if ($this->checkMacOnuExists(strtolower($eachOnu['identifier']))) {
                                $eachOnu['ponizer'] = 1;
                            }
                            break;
                    }
                    $trueArray[$eachOnu['oltip']][$eachType][] = $eachOnu;
                }
            }
        }
        $this->storeFixable($trueArray);
        return ($trueArray);
    }

    protected function storeFixable($trueArray) {
        $stored = serialize($trueArray);
        file_put_contents(self::FIXABLE_FILE, $stored, LOCK_EX);
    }

    public function onuMassRegister() {
        $tablecells = wf_TableCell(__(''));

        $fixable = file_get_contents(self::FIXABLE_FILE);
        $data = unserialize($fixable);
        foreach ($data as $oltip => $io) {
            foreach ($io as $type => $eachOnu) {
                if ($eachOnu['qinq'] == 1) {
                    $this->useUniversalQINQ = self::GET_UNIVERSALQINQ_PAIR_POOL;
                }
                $this->currentPonType = $type;
                $this->currentOltInterface = $$eachOnu['slot'];
                $this->currentOltSwId = $eachOnu['swid'];
                $this->currentOltIp = $eachOnu['oltip'];
                $this->checkRegisteredOnu();
                $this->checkQinq();
                switch ($this->currentPonType) {
                    case 'GPON':
                        $this->currentRunSerial = $eachOnu['identifier'];
                        break;
                    case 'EPON':
                        $this->currentRunMac = $eachOnu['identifier'];
                        break;
                }
                shell_exec($this->getRegisterOnuCommand());
                if (!$this->onuUnique()) {
                    $this->fixPonizer();
                }

            }
            shell_exec($this->getSaveConfigCommand());
        }
    }

    protected function onuUnique() {
        switch ($this->currentPonType) {
            case 'EPON':
                if ($this->checkMacOnuExists($this->currentRunMac)) {
                    return (false);
                }
                break;
            case 'GPON':
                if ($this->checkSerialOnuExists($this->currentRunSerial)) {
                    return (false);
                }
                break;
        }
        return (true);
    }

    protected function checkSerialOnuExists($serial) {
        if (isset($this->allOnuSerial[$serial])) {
            return true;
        }
        return false;
    }

    protected function checkMacOnuExists($mac) {
        if (isset($this->allOnuMac[$mac])) {
            return true;
        }
        return false;

    }

    protected function fixPonizer() {

    }

    protected function fixQinq() {

    }

}