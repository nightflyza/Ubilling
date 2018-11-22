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
    CONST ALT_ONU_ID_START = 2417492224;
    CONST GPON_RETRIES = 5;
    CONST SNMP_TEMPLATE_SECTION = 'onu_reg';
    CONST EMPTY_FIELD = '';
    CONST TYPE_FIELD = 'type';
    CONST INTERFACE_FIELD = 'interface';
    CONST OLTIP_FIELD = 'oltip';
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
    protected $eponCards = array('EPFC' => 4, 'EPFCB' => 4, 'ETGO' => 8, 'ETGOD' => 8, 'ETGH' => 16, 'ETGHG' => 16, 'ETGHK' => 16);

    /**
     * Array for checking ports count for GPON cards
     * 
     * @var array
     */
    protected $gponCards = array('GPFA' => 4, 'GPFAE' => 4, 'GTGO' => 8, 'GTGH' => 16, 'GTGHG' => 16);

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
     * Placeholder for already registered ONU IDs.
     * 
     * @var array
     */
    protected $existId = array();

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
    protected $currentOltSwId = '';

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
     * Placeholder for ONU identifier. Mac or Serial.
     * 
     * @var string
     */
    public $onuIdentifier = '';

    /**
     * Base class construction.
     * 
     * @return void
     */
    public function __construct() {
        $this->initGreed();
        $this->initMessages();
        $this->loadAllZteOlt();
        $this->loadAllSwLogin();
        $this->loadZteCards();
        $this->loadOnuModels();
        $this->loadConfig();
        $this->initSNMP();
        $this->loadOnu();
        snmp_set_oid_output_format(SNMP_OID_OUTPUT_NUMERIC);
    }

    //Load and init section.

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
     * Loads all onu models into onuModels.
     * 
     * @return void
     */
    protected function loadOnuModels() {
        $query = "SELECT * FROM `switchmodels` WHERE `modelname` LIKE '%ONU%'";
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
     * Load all data from `switch_login` table.
     * 
     * @return void
     */
    protected function loadAllSwLogin() {
        $query = "SELECT * FROM `switch_login`";
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
        $query = "SELECT * FROM `" . self::BIND_TABLE . "` WHERE `swid` = '" . $swid . "' GROUP BY `slot_number`, `port_number`";
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
        $query = "SELECT * FROM `" . self::CARDS_TABLE . "` ORDER BY `slot_number` ASC";
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
                $this->cardSelector[$eachCard['slot_number']] = $this->allZteOlt[$eachCard['swid']]['ip'] . ' | ' . $eachCard['slot_number'] . " | " . $eachCard['card_name'];
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
     * Calculating snmp indexes for each OLT.
     * 
     * @param int $swid     
     * 
     * @return void
     */
    protected function loadCalculatedData() {
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
        if (!empty($cards)) {
            $this->ponArray = array();
            $this->onuArray = array();
            $inherit = @$this->avidity['Z']['LSD'];
            foreach ($cards as $index => $value) {
                eval($inherit);
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
    }

    /**
     * Find vlan binds for curtain pon interface.
     * 
     * @return string
     */
    protected function getBindVlan() {
        $interface = explode("/", $this->currentOltInterface);
        $slot = $interface[1];
        $port = $interface[2];
        $this->loadZteBind($this->getOltId($this->currentOltIp));

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
            $rawSn = trim(str_replace("Hex-STRING:", '', $rawSn));
            $tmp = explode(" ", $rawSn);
            $sn = $this->hexToString($tmp[0]) . $this->hexToString($tmp[1]) . $this->hexToString($tmp[2]) . $this->hexToString($tmp[3]);
            $sn .= $tmp[4] . $tmp[5] . $tmp[6] . $tmp[7];
            foreach ($this->ponArray as $slot => $each_id) {
                if ($each_id == $interfaceId) {
                    $result[] = $this->currentOltIp . '|' . $slot . '|' . $sn;
                }
            }
        }

        return ($result);
    }

    /**
     * Get all unautheticated ONUs/ONTs.
     * 
     * @return array
     */
    protected function getAllUnauth() {
        $allUnreg = array();

        if (!empty($this->allZteOlt)) {
            foreach ($this->allZteOlt as $this->currentOltSwId => $eachOlt) {
                if (file_exists(CONFIG_PATH . "/snmptemplates/" . $eachOlt['snmptemplate'])) {
                    $this->currentSnmpTemplate = rcms_parse_ini_file(CONFIG_PATH . "/snmptemplates/" . $eachOlt['snmptemplate'], true);
                    $this->currentPonType = $this->currentSnmpTemplate [self::SNMP_TEMPLATE_SECTION]['TYPE'];
                    $this->currentOltIp = $eachOlt['ip'];
                    $this->currentSnmpCommunity = $eachOlt['snmp'];
                    $this->loadCalculatedData();

                    if (isset($this->allCards[$this->currentOltSwId]) AND ! empty($this->allCards[$this->currentOltSwId])) {
                        if ($this->currentSnmpTemplate [self::SNMP_TEMPLATE_SECTION]['TYPE'] == 'EPON') {
                            $allUnreg['EPON'][] = $this->getAllUnauthEpon();
                        }
                        if ($this->currentSnmpTemplate [self::SNMP_TEMPLATE_SECTION]['TYPE'] == 'GPON') {
                            $allUnreg['GPON'][] = $this->getAllUnauthGpon();
                        }
                    }
                }
            }
        }

        return ($allUnreg);
    }

    /**
     * Check for unauthenticated EPON ONU for specified OLT.
     * 
     * @return array
     */
    protected function getAllUnauthEpon() {
        $result = array();

        $allUnreg = @snmp2_real_walk($this->currentOltIp, $this->currentSnmpCommunity, $this->currentSnmpTemplate[self::SNMP_TEMPLATE_SECTION]['UNCFGLIST']);
        if (!empty($allUnreg)) {
            foreach ($allUnreg as $eachUncfgPort => $value) {
                $value = trim(str_replace("Hex-STRING:", '', $value));
                $mac = str_replace(" ", ':', $value);
                $interfaceIdNum = str_replace($this->currentSnmpTemplate[self::SNMP_TEMPLATE_SECTION]['UNCFGLIST'] . '.', '', $eachUncfgPort);
                $interfaceId = substr($interfaceIdNum, 0, 9);

                foreach ($this->ponArray as $slot => $each_id) {
                    if ($each_id == $interfaceId) {
                        $result[] = $this->currentOltIp . '|' . $slot . '|' . $mac;
                    }
                }
            }
        }

        return ($result);
    }

    /**
     * Check for unautheticated GPON ONT for specified OLT.
     * 
     * @return array
     */
    protected function getAllUnauthGpon() {
        $allUncfgOid = $this->currentSnmpTemplate[self::SNMP_TEMPLATE_SECTION]['UNCFGLIST'];
        $getUncfgSn = $this->currentSnmpTemplate[self::SNMP_TEMPLATE_SECTION]['UNCFGSN'];
        $result = array();

        $allUnreg = @snmp2_real_walk($this->currentOltIp, $this->currentSnmpCommunity, $allUncfgOid);
        if (!empty($allUnreg)) {
            foreach ($allUnreg as $eachUncfgPort => $value) {
                $value = str_replace("INTEGER:", '', $value);
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
                    $result = $this->parseUncfgGpon($uncfgSn, $interfaceId);
                }
            }
        }

        return ($result);
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
        $getAllId = @snmp2_real_walk($this->currentOltIp, $this->currentSnmpCommunity, $this->currentSnmpTemplate[self::SNMP_TEMPLATE_SECTION]['LLIDLIST'] . $this->ponArray[$this->currentOltInterface]);
        for ($i = 1; $i <= 128; $i++) {
            $allID[$i] = $i;
        }
        if (!empty($getAllId)) {
            foreach ($getAllId as $oid => $value) {
                $number = explode(":", $value);
                $number = trim($number[1]);
                $this->existId[] = $number;
            }
        }
        $free = array_diff($allID, $this->existId);
        reset($free);
        $this->lastOnuId = current($free);
    }

    /**
     * Used to change mac format from xx:xx:xx:xx:xx:xx to xxxx.xxxx.xxxx
     *      
     * @return void
     */
    protected function transformMac() {
        $macRaw = explode(":", $this->onuIdentifier);
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
     * @param int $onuModel
     * @param int $vlan
     * @param string $login
     * @param bool $save
     * @param bool $router
     * @param type $addMac
     * @param bool $PONizerAdd
     * 
     * @return string Result of shell_exec + expect
     */
    public function RegisterOnu($onuModel, $vlan, $login = '', $save = false, $router = false, $addMac = '', $PONizerAdd = false) {
        $this->currentOltSwId = $this->getOltId($this->currentOltIp);
        $this->currentSnmpCommunity = $this->allZteOlt[$this->currentOltSwId]['snmp'];
        $this->loadCalculatedData();
        //set serial number empty as default value because epon    
        $serial = '';
        $result = '';

        if (!empty($this->allSwLogin) and isset($this->allSwLogin[$this->currentOltSwId])) {
            $oltData = $this->allSwLogin[$this->currentOltSwId];
            $swlogin = $oltData['swlogin'];
            $swpassword = $oltData['swpass'];
            $method = $oltData['method'];
            if (file_exists(CONFIG_PATH . "/snmptemplates/" . $this->allZteOlt[$this->currentOltSwId]['snmptemplate'])) {
                $this->currentSnmpTemplate = rcms_parse_ini_file(CONFIG_PATH . "/snmptemplates/" . $this->allZteOlt[$this->currentOltSwId]['snmptemplate'], true);
                if ($this->currentPonType == 'EPON') {
                    $addMac = $this->onuIdentifier;
                    $this->onuIdentifier = $this->transformMac();
                    $this->checkRegisterdEponOnu();
                }
                if ($this->currentPonType == 'GPON') {
                    $this->onuIdentifier = strtoupper($this->onuIdentifier);
                    $serial = $this->onuIdentifier;
                    $this->checkRegisteredGponOnu();
                }

                $onuInterface = str_replace('olt', 'onu', $this->currentOltInterface);
                $scriptPath = CONFIG_PATH . 'scripts/' . $this->currentPonType . '_' . $this->onuModels[$onuModel]['ports'];
                if ($router) {
                    $scriptPath .= '_R';
                }
                if (file_exists($scriptPath)) {
                    $command = $this->billingCfg['EXPECT_PATH'];
                    $command .= ' ' . $scriptPath . ' ';
                    $command .= $this->currentOltIp;
                    $command .= ' ' . $swlogin . ' ' . $swpassword . ' ' . $method . ' ';
                    $command .= $this->currentOltInterface;
                    $command .= ' ' . $onuInterface . ' ';
                    $command .= $this->lastOnuId;
                    $command .= ' ' . $vlan . ' ';
                    $command .= $this->onuIdentifier;
                    $result .= shell_exec($command);
                    if ($save) {
                        $command = $this->billingCfg['EXPECT_PATH'];
                        $command .= ' ' . CONFIG_PATH . 'scripts/save' . ' ';
                        $command .= $this->currentOltIp;
                        $command .= ' ' . $swlogin . ' ' . $swpassword . ' ' . $method;

                        $result .= shell_exec($command);
                    }
                    $result = str_replace("\n", '<br />', $result);
                    log_register('ONUREG REGISTER ONU. ONU ID: ' . $this->onuIdentifier . '. OLT INTERFACE: ' . $this->currentOltInterface . '. ONU NUMBER: ' . $this->lastOnuId);

                    if ($PONizerAdd) {
                        if (!empty($login) and ! empty($addMac)) {
                            $pon = new PONizer();
                            $pon->onuCreate($onuModel, $this->currentOltSwId, '', $addMac, $serial, $login);
                        } else {
                            log_register('ONUREG PONIZER WRONG DATA. Login: ' . $login . '. MAC: ' . $addMac);
                        }
                    }
                }
            }
        }

        return ($result);
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
        log_register("ZTE Registered new card. OLT ID: " . $swid . "Slot: `" . $slot . "`. Card name: `" . $card . "`.");
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
        log_register("ZTE Edited card. OLT ID: " . $swid . ". Slot: `" . $slot . "`. Card name: `" . $card . "`.");

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
        $query = 'DELETE FROM `' . self::CARDS_TABLE . '` WHERE `swid` ="' . $swid . '" AND `slot_number` = "' . $slot . '"';
        nr_query($query);
        log_register("ZTE Deleted card. OLT ID: " . $swid . ". Slot: `" . $slot . "`");

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
        log_register("ZTE Created new vlan bind. OLT ID: " . $swid . ". Slot: `" . $slot . "`. Port: `" . $port . "`. VLAN: `" . $vlan . "`");

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
        $query = 'DELETE FROM `' . self::BIND_TABLE . '` WHERE `swid` ="' . $swid . '" AND `slot_number` = "' . $slot . '" AND `port_number` = "' . $port . '"';
        nr_query($query);
        log_register("ZTE Deleted vlan bind. OLT ID: " . $swid . ". Slot: `" . $slot . "`. Port: `" . $port . "`.");

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
        $query = 'UPDATE `' . self::BIND_TABLE . '` SET `vlan` = "' . $vlan . '" WHERE `swid` ="' . $swid . '" AND `slot_number` = "' . $slot . '" AND `port_number` ="' . $port . '"';
        nr_query($query);
        log_register("ZTE Edited vlan bind. OLT ID: " . $swid . ". Slot: `" . $slot . "`. Port: `" . $port . "`. VLAN: `" . $vlan . "`");

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
            $eachOlt = $this->allZteOlt[$oltid];
            $tablecells = wf_TableCell($eachOlt['id']);
            $tablecells .= wf_TableCell($eachOlt['ip']);
            $tablecells .= wf_TableCell($eachOlt['desc'] . ' | ' . $eachOlt['location']);
            $tablerows .= wf_TableRow($tablecells, 'row3');
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
        $form = wf_Form("", 'POST', $Row, 'glamour');

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
        $form = wf_Form("", 'POST', $Row, 'glamour');

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
        $form = wf_Form("", 'GET', $Row, 'glamour');

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
            $oltData = $this->allZteOlt[$swid];
            if (file_exists(CONFIG_PATH . "/snmptemplates/" . $oltData['snmptemplate'])) {
                $snmpTemplate = rcms_parse_ini_file(CONFIG_PATH . "/snmptemplates/" . $oltData['snmptemplate'], true);
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
        $cell .= wf_tag('div', false, 'changePorts', 'style="width: 100%;"') . wf_tag('div', true);
        $cell .= wf_TextInput('vlan', 'VLAN');
        $cell .= wf_Tag('br');
        $cell .= wf_Submit(__('Save'));
        $Row = wf_TableRow($cell, 'row1');
        $form = wf_Form("", 'POST', $Row, 'glamour');
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

        if (!empty($this->allBinds) AND ! empty($this->allZteOlt)) {
            foreach ($this->allBinds as $each => $eachBind) {
                if (isset($this->allZteOlt[$eachBind['swid']])) {
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
        $form = wf_Form("", 'POST', $Row, 'glamour');

        return ($form);
    }

    /**
     * Collect and show all unregistered onu.
     * 
     * @return string
     */
    public function listAllUncfg() {
        $tablecells = wf_TableCell(__('OLT IP'));
        $tablecells .= wf_TableCell(__('Type'));
        $tablecells .= wf_TableCell(__('Interface'));
        $tablecells .= wf_TableCell('MAC/SN');
        $tablecells .= wf_TableCell(__('Actions'));
        $tablerows = wf_TableRow($tablecells, 'row1');
        $allOnu = $this->getAllUnauth();

        if (!empty($allOnu)) {
            foreach ($allOnu as $eachType => $io) {
                foreach ($io as $eachNumber => $eachOnu) {
                    foreach ($eachOnu as $eachData) {
                        $eachData = explode("|", $eachData);
                        $ip = $eachData[0];
                        $interface = $eachData[1];
                        $macOnu = strtolower($eachData[2]);
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
                        $actionLinks = wf_Link(self::UNREG_ACT_URL . $ip . '&interface=' . $interface . $identifier . $macOnu . '&type=' . $eachType, wf_img('skins/add_icon.png', __('Register')), false);
                        $tablecells .= wf_TableCell($actionLinks);
                        $tablerows .= wf_TableRow($tablecells, 'row3');
                    }
                }
            }
        }
        $result = wf_TableBody($tablerows, '100%', '0', 'sortable');
        $result .= wf_delimiter();

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

        switch ($this->currentPonType) {
            case 'EPON':
                $cell = wf_HiddenInput(self::TYPE_FIELD, $this->currentPonType);
                $cell .= wf_HiddenInput(self::INTERFACE_FIELD, $this->currentOltInterface);
                $cell .= wf_HiddenInput(self::OLTIP_FIELD, $this->currentOltIp);
                $cell .= wf_HiddenInput(self::MAC_FIELD, $this->onuIdentifier);
                $cell .= wf_Selector(self::MODELID_FIELD, $this->onuModelsSelector, __('Choose ONU model'), '', true);
                $cell .= wf_TextInput(self::VLAN_FIELD, 'VLAN', $vlan, true);
                $cell .= wf_TextInput(self::LOGIN_FIELD, __('Login'), '', true);
                $cell .= wf_CheckInput(self::PONIZER_ADD_FIELD, __("Add ONU to PONizer"), true, true);
                $cell .= wf_Tag('br');
                $cell .= wf_CheckInput(self::SAVE_FIELD, __("Save config"), true);

                break;
            case 'GPON':
                $cell = wf_HiddenInput(self::TYPE_FIELD, $this->currentPonType);
                $cell .= wf_HiddenInput(self::INTERFACE_FIELD, $this->currentOltInterface);
                $cell .= wf_HiddenInput(self::OLTIP_FIELD, $this->currentOltIp);
                $cell .= wf_HiddenInput(self::SN_FIELD, $this->onuIdentifier);
                $cell .= wf_Selector(self::MODELID_FIELD, $this->onuModelsSelector, __('Choose ONU model'), '', true);
                $cell .= wf_TextInput(self::VLAN_FIELD, 'VLAN', $vlan, true);
                $cell .= wf_TextInput(self::LOGIN_FIELD, __('Login'), '', true);
                $cell .= wf_TextInput(self::MAC_ONU_FIELD, __('MAC ONU for PONizer'), '', true);
                $cell .= wf_CheckInput(self::RANDOM_MAC_FIELD, __("Generate random mac"), true, true);
                $cell .= wf_CheckInput(self::PONIZER_ADD_FIELD, __("Add ONU to PONizer"), true, true);
                $cell .= wf_Tag('br');
                $cell .= wf_CheckInput(self::SAVE_FIELD, __("Save config"), true);
                $cell .= wf_CheckInput(self::ROUTER_FIELD, __("Router ONU mode"), true);

                break;
        }
        $cell .= wf_delimiter();
        $cell .= wf_Submit(__('Register'));
        $Row = wf_TableRow($cell, 'row1');
        $form = wf_Form("", 'POST', $Row, 'glamour');

        return ($form);
    }

}
