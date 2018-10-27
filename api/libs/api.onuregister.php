<?php

/**
 * Class for registering ONU/ONT on ZTE OLTs.
 */
class OnuRegister {

    CONST MODULE_URL = '?module=ztevlanbinds';
    CONST MODULE_URL_EDIT_CARD = '?module=ztevlanbinds&edit_card=';
    CONST MODULE_URL_EDIT_BIND = '?module=ztevlanbinds&edit_bind=';
    CONST UNREG_URL = '?module=zteunreg';
    CONST UNREG_ACT_URL = '?module=zteunreg&register=true&oltip=';
    CONST CARDS_TABLE = 'zte_cards';
    CONST BIND_TABLE = 'zte_vlan_bind';
    CONST PORT_ID_START = 268501248;
    CONST ONU_ID_START = 805830912;
    CONST ALT_ONU_ID_START = 2416967936;

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
    protected $allZTEOlt = array();

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
    protected $EponCards = array('EPFC' => 4, 'EPFCB' => 4, 'ETGO' => 8, 'ETGOD' => 8, 'ETGH' => 16, 'ETGHG' => 16, 'ETGHK' => 16);

    /**
     * Array for checking ports count for GPON cards
     * 
     * @var array
     */
    protected $GponCards = array('GPFA' => 4, 'GPFAE' => 4, 'GTGO' => 8, 'GTGH' => 16, 'GTGHG' => 16);

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
     * Base class construction.
     * 
     * @return void
     */
    public function __construct() {
        $this->initGreed();
        $this->initMessages();
        $this->loadAllZTEOlt();
        $this->loadAllSwLogin();
        $this->loadZTECards();
        $this->loadOnuModels();
        $this->loadConfig();
        $this->initSNMP();
        $this->loadOnu();
        snmp_set_oid_output_format(SNMP_OID_OUTPUT_NUMERIC);
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
     * 
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
    protected function loadAllZTEOlt() {
        $query = 'SELECT `sw`.`id`,`sw`.`ip`,`sw`.`location`,`sw`.`snmp`,`sw`.`desc`,`model`.`snmptemplate` FROM `switches` AS `sw` JOIN `switchmodels` AS `model` ON (`sw`.`modelid` = `model`.`id`) WHERE `sw`.`desc` LIKE "%OLT%" AND `model`.`snmptemplate` LIKE "ZTE%"';
        $allOlt = simple_queryall($query);
        if (!empty($allOlt)) {
            foreach ($allOlt as $eachOlt) {
                $this->allZTEOlt[$eachOlt['id']] = $eachOlt;
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
    protected function loadZTEBind($swid) {
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
    protected function loadZTECards() {
        $query = "SELECT * FROM `" . self::CARDS_TABLE . "` ORDER BY `slot_number` ASC";
        $allCards = simple_queryall($query);
        if (!empty($allCards)) {
            foreach ($allCards as $eachCard) {
                $this->allCards[$eachCard['swid']][$eachCard['id']] = $eachCard;
            }
        }
    }

    protected function getOltId($ip) {
        if (!empty($this->allZTEOlt)) {
            foreach ($this->allZTEOlt as $id => $eachOlt) {
                if ($eachOlt['ip'] == $ip) {
                    return $id;
                }
            }
        }
    }

    protected function getBindVlan($swid, $interface) {
        $interface = explode("/", $interface);
        $slot = $interface[1];
        $port = $interface[2];
        $this->loadZTEBind($swid);
        if (!empty($this->allBinds)) {
            foreach ($this->allBinds as $id => $eachBind) {
                if ($eachBind['slot_number'] == $slot AND $eachBind['port_number'] == $port) {
                    return $eachBind['vlan'];
                }
            }
        }
        return '';
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
                $this->cardSelector[$eachCard['slot_number']] = $this->allZTEOlt[$eachCard['swid']]['ip'] . ' | ' . $eachCard['slot_number'] . " | " . $eachCard['card_name'];
            }
        }
    }

    /**
     * Loading all available ports for specified card.
     * 
     * @param string $type
     * @param array $exclude
     * 
     * @return void
     */
    protected function loadPortSelector($type, $exclude = array()) {
        $count = 0;
        if (isset($this->EponCards[$type])) {
            $count = $this->EponCards[$type];
        } elseif (isset($this->GponCards[$type])) {
            $count = $this->GponCards[$type];
        }
        $this->portSelector['======'] = '======';
        for ($i = 1; $i <= $count; $i++) {
            $this->portSelector[$i] = $i;
        }
        if (!empty($exclude)) {
            $this->portSelector = array_diff($this->portSelector, $exclude);
        }
    }

    protected function loadOnuModelSelector() {
        $this->onuModelsSelector['======'] = '======';
        if (!empty($this->onuModels)) {
            foreach ($this->onuModels as $id => $eachModel) {
                $this->onuModelsSelector[$id] = $eachModel['modelname'];
            }
        }
    }

    /**
     * Convert card name to interface name.
     * 
     * @param string $cardName
     * 
     * @return string
     */
    protected function correctInt($cardName) {
        if (isset($this->EponCards[$cardName])) {
            return 'epon-olt_';
        }
        if (isset($this->GponCards[$cardName])) {
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
     * Calculating snmp indexes for each OLT.
     * 
     * @param int $swid
     * @param string $type
     * 
     * @return void
     */
    protected function loadCalculatedData($swid, $type = '') {
        $cards = array();
        if (isset($this->allCards[$swid]) AND ! empty($this->allCards[$swid])) {
            foreach ($this->allCards[$swid] as $eachId => $eachCard) {
                if ($type == 'EPON') {
                    if (isset($this->EponCards[$eachCard['card_name']])) {
                        $cards[$eachCard['slot_number']]['ports'] = $this->EponCards[$eachCard['card_name']];
                        $cards[$eachCard['slot_number']]['description'] = $eachCard['card_name'];
                        $cards[$eachCard['slot_number']]['chasis'] = $eachCard['chasis_number'];
                    }
                }
                if ($type == 'GPON') {
                    if (isset($this->GponCards[$eachCard['card_name']])) {
                        $cards[$eachCard['slot_number']]['ports'] = $this->GponCards[$eachCard['card_name']];
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

    /**
     * Get all unautheticated ONUs/ONTs.
     * 
     * @return array
     */
    protected function getAllUnauth() {
        $allUnreg = array();
        if (!empty($this->allZTEOlt)) {
            foreach ($this->allZTEOlt as $eachOltId => $eachOlt) {
                if (file_exists(CONFIG_PATH . "/snmptemplates/" . $eachOlt['snmptemplate'])) {
                    $snmpTemplate = rcms_parse_ini_file(CONFIG_PATH . "/snmptemplates/" . $eachOlt['snmptemplate'], true);
                    $this->loadCalculatedData($eachOlt['id'], $snmpTemplate['onu_reg']['TYPE']);
                    if (isset($this->allCards[$eachOlt['id']]) AND ! empty($this->allCards[$eachOlt['id']])) {
                        if ($snmpTemplate['onu_reg']['TYPE'] == 'EPON') {
                            $allUnreg['EPON'][] = $this->getAllUnauthEpon($eachOlt['ip'], $eachOlt['snmp'], $snmpTemplate['onu_reg']['UNCFGLIST']);
                        }
                        if ($snmpTemplate['onu_reg']['TYPE'] == 'GPON') {
                            $allUnreg['GPON'][] = $this->getAllUnauthGpon($eachOlt['ip'], $eachOlt['snmp'], $snmpTemplate);
                        }
                    }
                }
            }
        }
        return $allUnreg;
    }

    /**
     * Check for unauthenticated EPON ONU for specified OLT.
     * 
     * @param string $ip
     * @param string $snmp
     * @param string $uncfg
     * 
     * @return array
     */
    protected function getAllUnauthEpon($ip, $snmp, $uncfg) {
        $result = array();
        $allUnreg = @snmp2_real_walk($ip, $snmp, $uncfg);
        if (!empty($allUnreg)) {
            foreach ($allUnreg as $eachUncfgPort => $value) {
                $value = trim(str_replace("Hex-STRING:", '', $value));
                $mac = str_replace(" ", ':', $value);
                $interfaceIDnum = str_replace($uncfg . '.', '', $eachUncfgPort);
                $interfaceID = substr($interfaceIDnum, 0, 9);

                foreach ($this->ponArray as $slot => $each_id) {
                    if ($each_id == $interfaceID) {
                        $result[] = $ip . '|' . $slot . '|' . $mac;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * Check for unautheticated GPON ONT for specified OLT.
     * 
     * @param string $ip
     * @param string $snmp
     * @param array $template
     * 
     * @return array
     */
    protected function getAllUnauthGpon($ip, $snmp, $template) {
        $AllUncfgOid = $template['onu_reg']['UNCFGLIST'];
        $GetUncfgSn = $template['onu_reg']['UNCFGSN'];
        $result = array();
        $allUnreg = @snmp2_real_walk($ip, $snmp, $AllUncfgOid);
        if (!empty($allUnreg)) {
            foreach ($allUnreg as $eachUncfgPort => $value) {
                $value = str_replace("INTEGER:", '', $value);
                $value = trim($value);
                if ($value > 0) {
                    $interfaceID = str_replace($AllUncfgOid . '.', '', $eachUncfgPort);
                    $UncfgSN = $this->snmp->walk($ip, $snmp, $GetUncfgSn . $interfaceID, false);
                    if (empty($UncfgSN)) {
                        $UncfgSN = $this->snmp->walk($ip, $snmp, $GetUncfgSn . $interfaceID, false);
                        if (empty($UncfgSN)) {
                            $UncfgSN = $this->snmp->walk($ip, $snmp, $GetUncfgSn . $interfaceID, false);
                        }
                    }
                    $UncfgSN = explodeRows(trim($UncfgSN));
                    foreach ($UncfgSN as $eachIndex => $rawValue) {
                        $rawValue = explode('=', $rawValue);
                        $rawMac = trim($rawValue[1]);
                        $rawMac = trim(str_replace("Hex-STRING:", '', $rawMac));
                        $tmp = explode(" ", $rawMac);
                        $sn = $this->hexToString($tmp[0]) . $this->hexToString($tmp[1]) . $this->hexToString($tmp[2]) . $this->hexToString($tmp[3]);
                        $sn .= $tmp[4] . $tmp[5] . $tmp[6] . $tmp[7];
                        foreach ($this->ponArray as $slot => $each_id) {
                            if ($each_id == $interfaceID) {
                                $result[] = $ip . '|' . $slot . '|' . $sn;
                            }
                        }
                    }
                }
            }
        }
        return $result;
    }

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
    public function createZTECard($swid, $chasis, $slot, $card) {
        if (isset($this->allCards[$swid]) AND ! empty($this->allCards[$swid])) {
            foreach ($this->allCards[$swid] as $eachNumber => $eachCard) {
                if ($eachCard['slot_number'] == $slot) {
                    rcms_redirect(self::MODULE_URL_EDIT_CARD . $swid);
                    return false;
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
        return true;
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
    public function editZTECard($swid, $slot, $card) {
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
    public function deleteZTECard($swid, $slot) {
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
    public function createZTEBind($swid, $slot, $port, $vlan) {
        $this->loadZTEBind($swid);
        if (!empty($this->allBinds)) {
            foreach ($this->allBinds as $each => $eachBind) {
                if ($eachBind['slot_number'] == $slot AND $eachBind['port_number'] == $port) {
                    rcms_redirect(self::MODULE_URL_EDIT_BIND . $swid);
                    return false;
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
    public function deleteZTEBind($swid, $slot, $port) {
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
    public function editZTEBind($swid, $slot, $port, $vlan) {
        $swid = vf($swid, 3);
        $slot = vf($slot, 3);
        $port = vf($port, 3);
        $vlan = vf($vlan, 3);
        $query = 'UPDATE `' . self::BIND_TABLE . '` SET `vlan` = "' . $vlan . '" WHERE `swid` ="' . $swid . '" AND `slot_number` = "' . $slot . '" AND `port_number` ="' . $port . '"';
        nr_query($query);
        log_register("ZTE Edited vlan bind. OLT ID: " . $swid . ". Slot: `" . $slot . "`. Port: `" . $port . "`. VLAN: `" . $vlan . "`");
        rcms_redirect(self::MODULE_URL_EDIT_BIND . $swid);
    }

    /**
     * List all available ZTE devices.
     * 
     * @return string
     */
    public function listAllZTEDevices() {
        $tablecells = wf_TableCell(__('ID'));
        $tablecells .= wf_TableCell(__('OLT IP'));
        $tablecells .= wf_TableCell(__('Description'));
        $tablecells .= wf_TableCell(__('Actions'));
        $tablerows = wf_TableRow($tablecells, 'row1');
        if (!empty($this->allZTEOlt)) {
            foreach ($this->allZTEOlt as $eachNumber => $eachOlt) {
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
        return $result;
    }

    /**
     * List all registered cards.
     * 
     * @param int $swid
     * 
     * @return string
     */
    public function listZTECard($swid) {
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
        return $result;
    }

    /**
     * Form for registering new card.
     * 
     * @param int $swid
     * 
     * @return string
     */
    public function createZTECardForm($swid) {
        $cell = wf_HiddenInput('createZTECard', 'true');
        $cell .= wf_HiddenInput('swid', $swid);
        $cell .= wf_TextInput('chasis_number', __('Chasis number'));
        $cell .= wf_TextInput('slot_number', __('Slot number'));
        $cell .= wf_TextInput('card_name', __('Card name'));
        $cell .= wf_Tag('br');
        $cell .= wf_Submit(__('Save'));
        $Row = wf_TableRow($cell, 'row1');
        $form = wf_Form("", 'POST', $Row, 'glamour');
        return $form;
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
    public function editZTECardForm($swid, $slot, $card) {
        $cell = wf_HiddenInput('editZTECard', 'true');
        $cell .= wf_HiddenInput('swid', $swid);
        $cell .= wf_HiddenInput('slot_number', $slot);
        $cell .= __('Slot number') . ': ' . $slot;
        $cell .= wf_tag('br', true);
        $cell .= wf_TextInput('card_name', __('Card name'), $card);
        $cell .= wf_Tag('br');
        $cell .= wf_Submit(__('Save'));
        $Row = wf_TableRow($cell, 'row1');
        $form = wf_Form("", 'POST', $Row, 'glamour');
        return $form;
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
        return $form;
    }

    /**
     * Form for selecting available ports for specified card.
     * 
     * @param int $type
     * @param int $swid
     * 
     * @return string
     */
    public function portSelectorForm($type, $swid) {
        $search = array();
        $exclude = array();
        $result = '';
        if (isset($this->allCards[$swid]) AND ! empty($this->allCards[$swid])) {
            foreach ($this->allCards[$swid] as $each) {
                $search[$each['slot_number']] = $each['card_name'];
            }
        }
        if (!empty($search)) {
            $this->loadZTEBind($swid);
            if (!empty($this->allBinds)) {
                foreach ($this->allBinds as $each => $eachBind) {
                    if ($eachBind['slot_number'] == $type) {
                        $exclude[] = $eachBind['port_number'];
                    }
                }
            }
            $type = $search[$type];
            $this->loadPortSelector($type, $exclude);
            $result = wf_Selector('port_number', $this->portSelector, __('Choose port'), '', true);
        }
        return $result;
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
        if (!empty($this->allZTEOlt)) {
            $oltData = $this->allZTEOlt[$swid];
            if (file_exists(CONFIG_PATH . "/snmptemplates/" . $oltData['snmptemplate'])) {
                $snmpTemplate = rcms_parse_ini_file(CONFIG_PATH . "/snmptemplates/" . $oltData['snmptemplate'], true);
                if (isset($snmpTemplate['onu_reg']['ALLCARDS'])) {
                    $allCards = @snmp2_real_walk($oltData['ip'], $oltData['snmp'], $snmpTemplate['onu_reg']['ALLCARDS']);
                }
                if (!empty($allCards)) {
                    foreach ($allCards as $eachOid => $eachCard) {
                        $cardType = 'other';
                        $eachOid = trim(str_replace($snmpTemplate['onu_reg']['ALLCARDS'] . '.', '', $eachOid));
                        $eachOid = explode('.', $eachOid);
                        $eachCard = trim(str_replace(array('STRING:', '"'), '', $eachCard));
                        $tablecells = wf_TableCell($eachOid[2]);
                        if (isset($this->EponCards[$eachCard])) {
                            $cardType = 'EPON';
                        }
                        if (isset($this->GponCards[$eachCard])) {
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
        return $result;
    }

    /**
     * Form for creating new vlan binding.
     * 
     * @param int $swid
     * 
     * @return string
     */
    public function createZTEBindForm($swid) {
        $this->loadCardSelector($swid);
        $cell = wf_HiddenInput('createZTEBind', 'true');
        $cell .= wf_HiddenInput('swid', $swid);
        $cell .= wf_SelectorClassed('slot_number', $this->cardSelector, __('IP | Slot number | Card name'), '', true, 'changeType');
        $cell .= wf_tag('div', false, 'changePorts', 'style="width: 100%;"') . wf_tag('div', true);
        $cell .= wf_TextInput('vlan', 'VLAN');
        $cell .= wf_Tag('br');
        $cell .= wf_Submit(__('Save'));
        $Row = wf_TableRow($cell, 'row1');
        $form = wf_Form("", 'POST', $Row, 'glamour');
        $form .= '<script>$(".changeType").change(function () {
    $.ajax({
        url: "",
        type: "POST",
        data: { json_type_changed: $(".changeType").val() },
        success: function (html) {
            $(".changePorts").html(html);
        }
    });
});</script>';
        return $form;
    }

    /**
     * Lists all vlan bindings.
     * 
     * @param int $swid
     * 
     * @return string
     */
    public function listZTEBind($swid) {
        $this->loadZTEBind($swid);
        $tablecells = wf_TableCell(__('ID'));
        $tablecells .= wf_TableCell(__('IP'));
        $tablecells .= wf_TableCell(__('Slot number'));
        $tablecells .= wf_TableCell(__('Port number'));
        $tablecells .= wf_TableCell('VLAN');
        $tablecells .= wf_TableCell(__('Actions'));
        $tablerows = wf_TableRow($tablecells, 'row1');
        if (!empty($this->allBinds) AND ! empty($this->allZTEOlt)) {
            foreach ($this->allBinds as $each => $eachBind) {
                if (isset($this->allZTEOlt[$eachBind['swid']])) {
                    $tablecells = wf_TableCell($eachBind['id']);
                    $tablecells .= wf_TableCell($this->allZTEOlt[$eachBind['swid']]['ip']);
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
        return $result;
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
    public function editZTEBindForm($swid, $slot, $port, $vlan) {
        $cell = wf_HiddenInput('editZTEBind', 'true');
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
        return $form;
    }

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
                foreach ($io as $eachNumber => $Onus) {
                    foreach ($Onus as $eachData) {
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
                                $identifier = '&serial=';
                                break;
                            case 'EPON':
                                $identifier = '&maconu=';
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
        return $result;
    }

    public function RegisterOnuForm($type, $interface, $oltip, $onuIdentifier) {
        $this->loadOnuModelSelector();
        $swid = $this->getOltId($oltip);
        $vlan = $this->getBindVlan($swid, $interface);
        $cell = wf_HiddenInput('type', $type);
        $cell .= wf_HiddenInput('interface', $interface);
        $cell .= wf_HiddenInput('oltip', $oltip);
        switch ($type) {
            case 'EPON':
                $cell .= wf_HiddenInput('mac', $onuIdentifier);
                break;
            case 'GPON':
                $cell .= wf_HiddenInput('sn', $onuIdentifier);
                break;
        }
        $cell .= wf_Selector('modelid', $this->onuModelsSelector, __('Choose ONU model'), '', true);
        $cell .= wf_TextInput('vlan', 'VLAN', $vlan, true);
        $cell .= wf_TextInput('login', __('Login'), '', true);
        if ($type == 'GPON') {
            $cell .= wf_TextInput('mac_onu', __('MAC ONU for PONizer'), '', true);
            $cell .= wf_CheckInput("random_mac", __("Generate random mac"), true, true);
        }
        $cell .= wf_CheckInput("ponizer_add", __("Add ONU to PONizer"), true, true);
        $cell .= wf_Tag('br');
        $cell .= wf_CheckInput('save', __("Save config"), true);
        if ($type == 'GPON') {
            $cell .= wf_CheckInput('router', __("Router ONU mode"), true);
        }
        $cell .= wf_delimiter();
        $cell .= wf_Submit(__('Register'));
        $Row = wf_TableRow($cell, 'row1');
        $form = wf_Form("", 'POST', $Row, 'glamour');
        return $form;
    }

    public function RegisterOnu($oltip, $type, $ponInterface, $onuIdentifier, $onuModel, $vlan, $login = '', $save = false, $router = false, $add_mac, $PONizerAdd = false) {
        $swid = $this->getOltId($oltip);
        $this->loadCalculatedData($swid, $type);
        $LastID = 1;
        $result = '';
        $ExistID = array();
        if (!empty($this->allSwLogin) and isset($this->allSwLogin[$swid])) {
            $oltData = $this->allSwLogin[$swid];
            $swlogin = $oltData['swlogin'];
            $swpassword = $oltData['swpass'];
            $method = $oltData['method'];
            $onuPorts = $this->onuModels[$onuModel]['ports'];
            $snmp = $this->allZTEOlt[$swid]['snmp'];
            $templateName = $this->allZTEOlt[$swid]['snmptemplate'];
            if (file_exists(CONFIG_PATH . "/snmptemplates/" . $templateName)) {
                $snmpTemplate = rcms_parse_ini_file(CONFIG_PATH . "/snmptemplates/" . $templateName, true);
                if ($type == 'EPON') {
                    $add_mac = $onuIdentifier;
                    $onuIdentifierRaw = explode(":", $onuIdentifier);
                    $onuIdentifier = $onuIdentifierRaw[0] . $onuIdentifierRaw[1] . '.' . $onuIdentifierRaw[2] . $onuIdentifierRaw[3] . '.' . $onuIdentifierRaw[4] . $onuIdentifierRaw[5];
                    foreach ($this->onuArray[$ponInterface] as $eachOnuNumber => $eachOnuID) {
                        $check = @snmp2_real_walk($oltip, $snmp, $snmpTemplate['onu_reg']['EACHLLID'] . $eachOnuID);
                        if (!empty($check)) {
                            foreach ($check as $oid => $tmp) {
                                $ExistID[] = $eachOnuID;
                            }
                        }
                    }
                    foreach ($this->onuArrayAlt[$ponInterface] as $eachOnuNumber => $eachOnuID) {
                        $check = @snmp2_real_walk($oltip, $snmp, $snmpTemplate['onu_reg']['EACHLLID'] . $eachOnuID);
                        if (!empty($check)) {
                            foreach ($check as $oid => $tmp) {
                                $ExistID[] = $eachOnuID;
                            }
                        }
                    }
                    if (!empty($ExistID)) {
                        $free = array_flip(array_diff($this->onuArray[$ponInterface], $ExistID));
                        $LastID = current($free);
                    }
                    $serial = '';
                }
                if ($type == 'GPON') {
                    $onuIdentifier = strtoupper($onuIdentifier);
                    $serial = $onuIdentifier;
                    $GetAllID = @snmp2_real_walk($oltip, $snmp, $snmpTemplate['onu_reg']['LLIDLIST'] . $this->ponArray[$ponInterface]);
                    for ($i = 1; $i <= 128; $i++) {
                        $allID[$i] = $i;
                    }
                    if (!empty($GetAllID)) {
                        foreach ($GetAllID as $oid => $value) {
                            $number = explode(":", $value);
                            $number = trim($number[1]);
                            $ExistID[] = $number;
                        }
                    }
                    $free = array_diff($allID, $ExistID);
                    reset($free);
                    $LastID = current($free);
                }

                $onuInterface = str_replace('olt', 'onu', $ponInterface);
                $scriptPath = CONFIG_PATH . 'scripts/' . $type . '_' . $onuPorts;
                if ($router) {
                    $scriptPath .= '_R';
                }
                if (file_exists($scriptPath)) {
                    $command = $this->billingCfg['EXPECT_PATH'] . ' ' . $scriptPath . ' ';
                    $command .= $oltip . ' ' . $swlogin . ' ' . $swpassword . ' ';
                    $command .= $method . ' ' . $ponInterface . ' ' . $onuInterface . ' ';
                    $command .= $LastID . ' ' . $vlan . ' ' . $onuIdentifier;
                    $result .= shell_exec($command);
                    if ($save) {
                        $command = $this->billingCfg['EXPECT_PATH'] . ' ' . CONFIG_PATH . 'scripts/save';
                        $command .= ' ' . $oltip . ' ' . $swlogin . ' ' . $swpassword . ' ' . $method;
                        $result .= shell_exec($command);
                    }
                    $result = str_replace("\n", '<br />', $result);
                    if ($PONizerAdd) {
                        $pon = new PONizer();
                        $pon->onuCreate($onuModel, $swid, '', $add_mac, $serial, $login);
                    }
                }
            }
        }
        return $result;
    }

}
