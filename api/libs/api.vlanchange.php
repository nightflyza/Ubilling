<?php

/**
 * Apply vlan on device
 */
class VlanChange {

    CONST MODULE_ONU_APPLY_AJAXONULIST = '?module=vlanmanagement_onu_apply&ajaxOnuList=true';
    CONST MODULE_ONU_APPLY_VLAN = '?module=vlanmanagement_onu_apply&apply_vlan=true';

    /**
     * OLT ID
     * 
     * @var int
     */
    protected $oltId = -1;

    /**
     * User's login
     * 
     * @var string
     */
    protected $username = '';

    /**
     * OLT IP
     * 
     * @var string
     */
    protected $oltIp = '';

    /**
     * OLT read snmp community
     * 
     * @var string
     */
    protected $oltCommunityRead = '';

    /**
     * OLT write snmp community
     * 
     * @var string
     */
    protected $oltCommunityWrite = '';

    /**
     * 
     * 
     * @var string
     */
    protected $oltInterface = '';

    /**
     * OLT's SNMP template file name
     * 
     * @var string
     */
    protected $snmpTemplateFile = '';

    /**
     * OLT's SNMP template content
     * 
     * @var array
     */
    protected $snmpTemplate = array();

    /**
     * Guest vlans list
     * 
     * @var array
     */
    protected $guestVlans = array();

    /**
     * Contains all guest ONUs for certain OLT
     * 
     * @var array
     */
    protected $guestOnus = array();

    /**
     * ONU MAC
     * 
     * @var string
     */
    protected $onuMac = '';

    /**
     * ONU Serial
     * 
     * @var string
     */
    protected $onuSerial = '';

    /**
     * Contains all realnames
     * 
     * @var array
     */
    protected $allRealnames = array();

    /**
     * Vlan mode
     * 
     * @var string
     */
    protected $mode = 'none';

    /**
     * Contains all possible cvlans for olt
     * 
     * @var array
     */
    protected $oltCvlans = array();

    /**
     * Contains all used cvlans
     * 
     * @var array
     */
    protected $usedCvlans = array();

    /**
     * Contains all universal qinq assignments 
     * 
     * @var array
     */
    protected $universalAssign = array();

    /**
     * CVLAN placeholder
     * 
     * @var int
     */
    protected $cvlan = -1;

    /**
     * SVLAN placeholder
     * 
     * @var int
     */
    protected $svlan = -1;

    /**
     * SVLAN id placeholder
     * 
     * @var int
     */
    protected $svlanid = -1;

    /**
     * Contains all switchmodels
     * 
     * @var array
     */
    protected $allModelsData = array();

    /**
     * Containts all pononu data with serial key
     * 
     * @var array
     */
    protected $allSerial = array();

    /**
     * Containts all pononu data with mac key
     * 
     * @var array
     */
    protected $allMac = array();

    /**
     * Placeholder for snmp helper object
     * 
     * @var object
     */
    protected $snmp = '';

    /**
     * Placeholder for nyan_orm instance for switches table
     * 
     * @var object
     */
    protected $switchesDB = '';

    /**
     * Placeholder for nyan_orm instance for switchmodels table
     * 
     * @var object
     */
    protected $switchModelsDB = '';

    /**
     * 
     * 
     * @var object 
     */
    protected $oltqinqDB = '';

    /**
     * 
     * 
     * @var object
     */
    protected $cvlanDB = '';

    /**
     * 
     * 
     * @var object
     */
    protected $svlanDB = '';

    /**
     * Placeholder for nyan_orm object for pononu table
     *
     * @var object
     */
    protected $ponDB = '';

    /**
     * 
     * 
     * @var object
     */
    protected $realNameDB = '';

    /**
     * General ubiiling config content
     * 
     * @var array
     */
    protected $altCfg = '';

    public function __construct($oltid = -1, $username = '') {
        $this->oltId = $oltid;
        $this->snmp = new SNMPHelper();
        $this->loadAlter();
        $this->switchesDB = new NyanORM('switches');
        $this->switchModelsDB = new NyanORM('switchmodels');
        $this->oltqinqDB = new NyanORM('olt_qinq');
        $this->cvlanDB = new NyanORM('qinq_bindings');
        $this->svlanDB = new NyanORM('qinq_svlan');
        $this->ponDB = new NyanORM('pononu');
        $this->realNameDB = new NyanORM('realname');
        $this->usernameGuess($username);
        if ($this->oltId) {
            $this->allModelsData = $this->switchModelsDB->getAll('id');
            $this->setSnmpTemplateFile();
            $this->loadSnmpTemplate();
            $this->loadAllOnu();
            $this->loadAllRealnames();
            $this->setVlanMode();
        }
    }

    /**
     * Dirty hacks to set up environment with $_GET param or by onu
     * 
     * @param string $username
     * 
     * @return void
     */
    protected function usernameGuess($username) {
        if (!empty($username)) {
            $this->username = $username;
        } else {
            if (ubRouting::checkGet('username')) {
                $this->username = ubRouting::get('username', 'mres');
            } else {
                if (ubRouting::checkGet('onuid')) {
                    $this->getUsernameByOnu();
                }
            }
        }
    }

    /**
     * Load all ONU
     * 
     * @return void
     */
    protected function loadAllOnu() {
        $this->allMac = $this->ponDB->getAll('mac');
        $this->alLSerial = $this->ponDB->getAll('serial');
    }

    /**
     * Check if onu already exist and linked to some user
     * 
     * @param string $onuid
     * @param bool $check
     * @return string
     */
    protected function getUsernameByOnu($onuid = '', $check = false) {
        if (empty($onuid)) {
            $onuid = ubRouting::get('onuid', 'mres');
        }

        if (!empty($this->allMac)) {
            if (isset($this->allMac[$onuid])) {
                if (!empty($this->allMac[$onuid]['login'])) {
                    if (!$check) {
                        $this->username = $this->allMac[$onuid]['login'];
                    } else {
                        return ($this->allMac[$onuid]['login']);
                    }
                }
            }
        } else {
            if (!empty($data)) {
                if (isset($this->allSerial[$onuid])) {
                    if (!empty($this->allMac[$onuid]['login'])) {
                        if (!$check) {
                            $this->username = $this->alLSerial[$onuid]['login'];
                        } else {
                            return ($this->alLSerial[$onuid]['login']);
                        }
                    }
                }
            }
        }
    }

    /**
     * Poll selected OLT is there is ONU with guest vlan
     * 
     * @return void
     */
    public function checkOltGuestVlan() {
        $this->setGuestVlans();
        $this->checkUserOnu();
        $this->pollOlt();
    }

    /**
     * Loads system alter.ini config for further usage
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadAlter() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Loads all realnames
     * 
     * @return void
     */
    protected function loadAllRealnames() {
        $this->allRealnames = $this->realNameDB->getAll('login');
    }

    /**
     * Returns html string to load JS file.
     * 
     * @return string
     */
    protected function loadVlanmanagementJs() {
        $result = '<script src = "./modules/jsc/vlanmanagement.js" type = "text/javascript"></script>';
        return ($result);
    }

    /**
     * Find and set snmp template file name 
     * 
     * @return void
     */
    protected function setSnmpTemplateFile() {
        $this->switchesDB->where('id', '=', $this->oltId);
        $switchData = $this->switchesDB->getAll('id');
        $modelId = $switchData[$this->oltId]['modelid'];
        $this->oltIp = $switchData[$this->oltId]['ip'];
        $this->oltCommunityRead = $switchData[$this->oltId]['snmp'];
        if (empty($switchData[$this->oltId]['snmpwrite'])) {
            $this->oltCommunityWrite = $switchData[$this->oltId]['snmp'];
        } else {
            $this->oltCommunityWrite = $switchData[$this->oltId]['snmpwrite'];
        }


        $this->switchModelsDB->where('id', '=', $modelId);
        $modelData = $this->switchModelsDB->getAll('id');
        $this->snmpTemplateFile = $modelData[$modelId]['snmptemplate'];

        if (empty($switchData[$this->oltId]['snmp'])) {
            throw new Exception('EMPTY OLT SNMP COMMUNITY');
        }
    }

    /**
     * Find and set available guest vlans from config
     * 
     * @return void
     */
    protected function setGuestVlans() {
        if (isset($this->altCfg['VLAN_MANAGEMENT_ONU_GUEST_VLANS'])) {
            if (!empty($this->altCfg['VLAN_MANAGEMENT_ONU_GUEST_VLANS'])) {
                if (strpos($this->altCfg['VLAN_MANAGEMENT_ONU_GUEST_VLANS'], ',') !== false) {
                    $vlans = explode(",", trim($this->altCfg['VLAN_MANAGEMENT_ONU_GUEST_VLANS']));
                    foreach ($vlans as $each) {
                        array_push($this->guestVlans, trim($each));
                    }
                } else {
                    array_push($this->guestVlans, trim($this->altCfg['VLAN_MANAGEMENT_ONU_GUEST_VLANS']));
                }
            }
        }
    }

    /**
     * Check if user has assigned ONU with proper OLT link
     * 
     * @return void
     * 
     * @throws Exception
     */
    protected function checkUserOnu() {
        $this->ponDB->where('login', '=', $this->username);
        $this->ponDB->where('oltid', '=', $this->oltId);
        $onuData = $this->ponDB->getAll('login');
        if (!empty($onuData)) {
            $this->onuMac = $onuData[$this->username]['mac'];
            $this->onuSerial = $onuData[$this->username]['serial'];
        }
        $this->ponDB->where('login', '=', $this->username);
        $onuDataAlt = $this->ponDB->getAll('login');
        if (!empty($onuDataAlt)) {
            //throw new Exception('OLT MISMATCH');
        } else {
            //throw new Exception('NO ONU WAS FOUND');
        }
    }

    /**
     * Load content of snmp template file if found
     * 
     * @return void
     * 
     * @throws Exception
     */
    protected function loadSnmpTemplate() {
        $templateFile = 'config/snmptemplates/' . $this->snmpTemplateFile;
        $privateTemplateFile = DATA_PATH . 'documents/mysnmptemplates/' . $this->snmpTemplateFile;
        if (file_exists($templateFile)) {
            $this->snmpTemplate = rcms_parse_ini_file($templateFile, true);
            if (file_exists($privateTemplateFile)) {
                $this->snmpTemplate = rcms_parse_ini_file($privateTemplateFile, true);
            }
        }
        if (empty($this->snmpTemplate)) {
            throw new Exception('NO SNMP TEMPLATE FILE WAS FOUND');
        }
    }

    /**
     * load all data from table switches related to olt
     * 
     * @return array
     */
    protected function loadOltDetails() {
        $this->switchesDB->where('id', '=', $this->oltId);
        $data = $this->switchesDB->getAll('id');
        return ($data);
    }

    /**
     * Load all available assigns
     * 
     * @return void
     */
    protected function loadCvlans() {
        $this->loadUniversalAssign();
        $this->loadOltCvlans();
        $this->loadUsedCvlans();
    }

    /**
     * Load olt pool
     * 
     * @return void
     */
    protected function loadOltCvlans() {
        $this->oltqinqDB->where('swid', '=', $this->oltId);
        $this->oltqinqDB->where('port', '=', $this->oltInterface);
        $this->oltCvlans = $this->oltqinqDB->getAll('swid');
    }

    /**
     * Load all assignments from universal qinq by svlan
     * 
     * @return void
     */
    protected function loadUsedCvlans() {
        if (!empty($this->oltCvlans)) {
            if (isset($this->oltCvlans[$this->oltId])) {
                $this->cvlanDB->where('svlan_id', '=', $this->oltCvlans[$this->oltId]['svlan_id']);
                $this->usedCvlans = $this->cvlanDB->getAll('cvlan');
            }
        }
    }

    /**
     * load assignment from universal qinq if exist for user
     * 
     * @return void
     */
    protected function loadUniversalAssign() {
        $this->universalAssign = $this->cvlanDB->getAll('login');
    }

    protected function clearInterface($interface = '') {
        if (empty($interface)) {
            $interface = ubRouting::get('interface_olt', 'mres');
        }
        if (strpos($interface, '/') !== false) {
            $interfaceExplode = explode("/", $interface);
            $this->oltInterface = $interfaceExplode[1];
        } else {
            $this->oltInterface = $interface;
        }
    }

    /**
     * Compare available and used arrays to get free one
     * 
     * @return void
     */
    protected function setCvlanFromPool() {
        $freeCvlans = array();
        $usedRange = array();
        if (!empty($this->oltCvlans)) {
            if (isset($this->oltCvlans[$this->oltId])) {
                $oltRange = range($this->oltCvlans[$this->oltId]['cvlan'], $this->oltCvlans[$this->oltId]['cvlan'] + 63);
                foreach ($this->usedCvlans as $eachCvlan => $io) {
                    $usedRange[$eachCvlan] = $eachCvlan;
                }
                $freeCvlans = array_diff($oltRange, $usedRange);
            }
        }
        reset($freeCvlans);
        $firstCvlan = current($freeCvlans);
        $this->cvlan = $firstCvlan;
    }

    /**
     * Find svlan number by id
     * 
     * @return void
     */
    protected function setSvlanFromPool() {
        $this->svlanDB->where('id', '=', $this->oltCvlans[$this->oltId]['svlan_id']);
        $data = $this->svlanDB->getAll('id');
        $this->svlan = $data[$this->oltCvlans[$this->oltId]['svlan_id']]['svlan'];
        $this->svlanid = $this->oltCvlans[$this->oltId]['svlan_id'];
    }

    /**
     * Set svlan number from universal assignment 
     * 
     * @return void
     */
    protected function setSvlanFromUniversal() {
        $this->svlanDB->where('id', '=', $this->universalAssign[$this->username]['svlan_id']);
        $data = $this->svlanDB->getAll('id');
        $this->svlan = $data[$this->universalAssign[$this->username]['svlan_id']]['svlan'];
        $this->svlanid = $this->universalAssign[$this->username]['svlan_id'];
    }

    /**
     * Get cvlan from universal qinq or from pool
     * 
     * @return void
     */
    protected function setFreeCvlan() {
        if (empty($this->universalAssign)) {
            $this->setCvlanFromPool();
        } else {
            if (!isset($this->universalAssign[$this->username])) {
                $this->setCvlanFromPool();
            } else {
                $this->cvlan = $this->universalAssign[$this->username]['cvlan'];
            }
        }
    }

    /**
     * Set svlan from universal qinq or from pool
     * 
     * @return void
     */
    protected function setSvlan() {
        if ($this->cvlan) {
            if (!empty($this->universalAssign)) {
                $this->setSvlanFromPool();
            } else {
                if (!isset($this->universalAssign[$this->username])) {
                    $this->setSvlanFromPool();
                } else {
                    $this->setSvlanFromUniversal();
                }
            }
        }
    }

    /**
     * Set svlan and cvlan
     * 
     * @return void
     */
    protected function getVlanPair() {
        $this->loadCvlans();
        $this->setFreeCvlan();
        $this->setSvlan();
    }

    /**
     * Set vlan mode if was found in snmp template
     * 
     * @return void
     * 
     * @throws Exception
     */
    protected function setVlanMode() {
        if (isset($this->snmpTemplate['vlan'])) {
            if (isset($this->snmpTemplate['vlan']['VLANMODE'])) {
                switch ($this->snmpTemplate['vlan']['VLANMODE']) {
                    case 'BDCOM_B':
                        $this->mode = 'bdcom_b';
                        break;
                    default:
                        $this->mode = 'none';
                        break;
                }
            } else {
                throw new Exception('no vlanmode set in snmp template file');
            }
        } else {
            throw new Exception('no vlan section in snmp template file');
        }
    }

    /**
     * List all unknown onu from olt
     * 
     * @return void
     */
    protected function pollOlt() {
        switch ($this->mode) {
            case 'bdcom_b':
                $this->pollOltBdcom();
                break;
            default:
                return ('');
        }
    }

    /**
     * Get ONUs with guest vlan configured
     * 
     * @return void
     */
    protected function pollOltBdcom() {
        $allOnuIndex = array();
        $interfaceIndex = array();
        $interfaceOltIndex = array();
        //$allOnuPvid = array();

        $allOnuIndexRaw = $this->snmp->walk($this->oltIp, $this->oltCommunityRead, $this->snmpTemplate['vlan']['IFINDEX'], false);
        $allOnuIndexRaw = trim($allOnuIndexRaw);
        if (!empty($allOnuIndexRaw)) {
            $allOnuIndexRows = explodeRows($allOnuIndexRaw);
            foreach ($allOnuIndexRows as $eachRow) {
                $eachRow = trim($eachRow);
                $eachRowExplode = explode("=", $eachRow);
                $oid = $eachRowExplode[0];
                $oid = trim(str_replace($this->snmpTemplate['vlan']['IFINDEX'] . '.', '', $oid));
                $mac = convertMACDec2Hex($oid);
                $value = $eachRowExplode[1];
                $value = trim(str_replace("INTEGER:", '', $value));
                $allOnuIndex[$value] = $mac;
            }

            unset($value);
            unset($oid);
            unset($eachRow);
            unset($eachRowExplode);

            $interfaceIndexRaw = $this->snmp->walk($this->oltIp, $this->oltCommunityRead, $this->snmpTemplate['misc']['INTERFACEINDEX'], false);
            $interfaceIndexRaw = trim($interfaceIndexRaw);
            if (!empty($interfaceIndexRaw)) {
                $interfaceIndexRows = explodeRows($interfaceIndexRaw);
                foreach ($interfaceIndexRows as $eachRow) {
                    $eachRow = trim($eachRow);
                    $eachRowExplode = explode("=", $eachRow);
                    $oidRaw = $eachRowExplode[0];
                    $oid = trim(str_replace($this->snmpTemplate['misc']['INTERFACEINDEX'] . ".", '', $oidRaw));
                    $valueRaw = $eachRowExplode[1];
                    $value = trim(str_replace("STRING:", '', $valueRaw));
                    $interfaceIndex[$oid] = $value;
                    $valueExplode = explode(":", $value);
                    $interfaceOltIndex[$oid] = $valueExplode[0];
                }
            }

            unset($value);
            unset($oid);
            unset($eachRow);
            unset($eachRowExplode);

            $allOnuPvidRaw = $this->snmp->walk($this->oltIp, $this->oltCommunityRead, $this->snmpTemplate['vlan']['PVID'], false);
            $allOnuPvidRaw = trim($allOnuPvidRaw);
            if (!empty($allOnuPvidRaw)) {
                $allOnuPvidRows = explodeRows($allOnuPvidRaw);
                foreach ($allOnuPvidRows as $eachRow) {
                    $eachRow = trim($eachRow);
                    $eachRowExplode = explode("=", $eachRow);
                    $oidRaw = $eachRowExplode[0];
                    $oidRaw = trim(str_replace($this->snmpTemplate['vlan']['PVID'] . ".", '', $oidRaw));
                    $oidRawExplode = explode(".", $oidRaw);
                    $onuId = trim($oidRawExplode[0]);
                    $portNumber = trim($oidRawExplode[1]);
                    $value = $eachRowExplode[1];
                    $value = trim(str_replace("INTEGER:", '', $value));
                    foreach ($this->guestVlans as $eachGuest) {
                        if ($eachGuest == $value) {
                            if (isset($allOnuIndex[$onuId])) {
                                //$allOnuPvid[$onuId][$portNumber] = $value;
                                $this->guestOnus[$allOnuIndex[$onuId]] = array('id' => $allOnuIndex[$onuId], 'port' => $portNumber, 'vlan' => $value, 'interface' => $interfaceIndex[$onuId], 'interface_olt' => $interfaceOltIndex[$onuId], 'type' => 'epon', 'snmp_index' => $onuId);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Ajax loader for onu list
     * 
     * @return string
     */
    public function onuListShow() {
        $result = '';
        $add = '&oltid=' . ubRouting::get('oltid', 'mres');
        if (ubRouting::checkGet('username')) {
            $add .= "&username=" . $this->username;
        }
        $columns = array('Interface', 'MAC/Serial', 'Port', 'VLAN', 'Realname', 'Actions');
        $opts = '"order": [[ 0, "asc" ]]';
        $result .= wf_JqDtLoader($columns, self::MODULE_ONU_APPLY_AJAXONULIST . $add, false, __('Request'), 100, $opts);
        $result .= $this->loadVlanmanagementJs();
        return($result);
    }

    /**
     * Generate ajax list of OLTs.
     * 
     * @return string
     */
    public function onuListAjaxRender() {
        $json = new wf_JqDtHelper();
        $this->checkOltGuestVlan();

        $currentUsername = $this->username;

        if (!empty($this->guestOnus)) {
            foreach ($this->guestOnus as $each) {
                if (ubRouting::checkGet('username')) {
                    $this->$currentUsername = ubRouting::get('username', 'mres');
                    $this->username = ubRouting::get('username', 'mres');
                }
                $altUsername = $this->getUsernameByOnu($each['id'], true);
                if (!$currentUsername) {
                    $this->getUsernameByOnu($each['id']);
                    $currentUsername = $altUsername;
                }
                if ($currentUsername) {
                    $controls = wf_modalAuto(wf_img_sized('skins/add_icon.png', '', '16', '16'), __('Assign VLAN'), $this->changeVlanForm($each['id'], $each['port'], $each['vlan'], $each['type'], $each['interface'], $each['interface_olt'], $each['snmp_index']));
                } else {
                    $controls = wf_modalAuto(wf_img_sized('skins/add_icon.png', '', '16', '16'), __('Assign VLAN'), $this->userNameForm($each['id'], $each['port'], $each['vlan'], $each['type'], $each['interface'], $each['interface_olt'], $each['snmp_index']));
                }
                $data[] = trim($each['interface']);
                $data[] = trim($each['id']);
                $data[] = trim($each['port']);
                $data[] = trim($each['vlan']);
                $data[] = wf_link('?module=userprofile&username=', trim(@$this->allRealnames[$altUsername]['realname']));
                $data[] = $controls;
                $json->addRow($data);

                $currentUsername = '';
                unset($data);
            }
        }

        $json->getJson();
    }

    protected function onuModelsSelector() {
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
        return($models);
    }

    /**
     * Returns ONU creation form
     *
     * @return string
     */
    protected function userNameForm($onuId, $port, $vlan, $type, $interface, $interface_olt, $snmp_index) {
        $result = '';
        $inputs = wf_TextInput('username', __('Login'), '', true, 20, '', '', 'usernameInput---' . ubRouting::get('oltid', 'mres') . '---' . $onuId . '---' . $port);
        $inputs .= wf_Submit(__('Validate'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour', '', 'usernameForm---' . ubRouting::get('oltid', 'mres') . '---' . $onuId . '---' . $port, '', 'onsubmit="return validateVlanUsernameForm(' . ubRouting::get('oltid', 'mres') . ",'" . $onuId . "', " . $port . ',' . $vlan . ",'" . $type . "','" . $interface . "','" . $interface_olt . "'," . $snmp_index . ')"');

        return ($result);
    }

    /**
     * Show form to change cvlan on onu
     * 
     * @param string $onuId
     * @param int $port
     * @param int $vlan
     * @param string $type
     * @param string $interface
     * @param string $interface_olt
     * 
     * @return string
     */
    public function changeVlanForm($onuId, $port, $vlan, $type, $interface, $interface_olt, $snmp_index) {
        $result = '';
        $oltDetails = $this->loadOltDetails();
        $this->clearInterface($interface_olt);
        $this->getVlanPair();
        $models = $this->onuModelsSelector();
        $inputs = wf_HiddenInput('change_cvlan', true);
        $inputs .= wf_HiddenInput('interface_olt', $interface_olt);
        $inputs .= wf_HiddenInput('interface', $interface);
        $inputs .= wf_HiddenInput('onuid', $onuId);
        $inputs .= wf_HiddenInput('port', $port);
        $inputs .= wf_HiddenInput('cvlan', $this->cvlan);
        $inputs .= wf_HiddenInput('svlan', $this->svlan);
        $inputs .= wf_HiddenInput('svlanid', $this->svlanid);
        $inputs .= wf_HiddenInput('snmp_index', $snmp_index);
        $inputs .= wf_HiddenInput('type', $type);

        $cells = wf_TableCell(__('Login'));
        $cells .= wf_TableCell(wf_TextInput('username', '', $this->username, false, 20));
        $rows = wf_TableRow($cells);
        $checkedUsername = $this->getUsernameByOnu($onuId, true);
        if (!empty($checkedUsername)) {
            if ($this->username != $checkedUsername) {
                $rows .= 'this onu is assigned to another user: ' . wf_Link('?module=userprofile&username=' . $checkedUsername, $checkedUsername);
            }
        }
        $cells = wf_TableCell(__('OLT'));
        $cells .= wf_TableCell($oltDetails[$this->oltId]['ip'] . ' ' . $oltDetails[$this->oltId]['location']);
        $rows .= wf_TableRow($cells);
        $cells = wf_TableCell(__('Model'));
        $cells .= wf_tableCell(wf_Selector('onumodelid', $models, '', '', ''));
        $rows .= wf_TableRow($cells);
        if ($this->cvlan) {
            if (!empty($this->universalAssign)) {
                if (isset($this->universalAssign[$this->username])) {
                    $cells = wf_TableCell('Found UniversalQINQ assignment: ');
                } else {
                    $cells = wf_TableCell('New vlan pair assigned from pool: ');
                }
            } else {
                $cells = wf_TableCell('New vlan pair assigned from pool: ');
            }
            $rows .= wf_TableRow($cells);
            $cells = wf_TableCell('svlan');
            $cells .= wf_TableCell($this->svlan);
            $rows .= wf_TableRow($cells);
            $cells = wf_TableCell('cvlan');
            $cells .= wf_TableCell($this->cvlan);
            $rows .= wf_TableRow($cells);
        } else {
            $rows .= 'There is no vlan pool for OLT or UniversalQINQ assignment for user' . wf_delimiter();
        }
        $cells = wf_TableCell('olt interface');
        $cells .= wf_TableCell($interface_olt);
        $rows .= wf_TableRow($cells);
        $cells = wf_TableCell('onu interface');
        $cells .= wf_TableCell($interface);
        $rows .= wf_TableRow($cells);
        $cells = wf_TableCell('onu identifier');
        $cells .= wf_TableCell($onuId);
        $rows .= wf_TableRow($cells);
        $cells = wf_TableCell('port');
        $cells .= wf_TableCell($port);
        $rows .= wf_TableRow($cells);
        $cells = wf_TableCell('guest vlan');
        $cells .= wf_TableCell($vlan);
        $rows .= wf_TableRow($cells);
        if ($this->cvlan) {
            $cells = wf_TableCell(wf_Submit(__('Apply')));
            $rows .= wf_TableRow($cells);
        }
        $inputs .= wf_TableBody($rows, '100%', 0, 'sortable');
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return($result);
    }

    /**
     * Apply cvlan on onu
     * 
     * @return void
     */
    public function changeVlan() {
        switch ($this->mode) {
            case 'bdcom_b':
                $this->changeOltBdcom();
                break;
            default:
                return ('');
        }
        $this->routineAdd();
    }

    protected function changeOltBdcom() {
        $this->loadCvlans();

        $pvidData['oid'] = $this->snmpTemplate['vlan']['PVID'] . '.' . ubRouting::post('snmp_index', 'vf') . '.' . ubRouting::post('port', 'vf');
        $pvidData['type'] = 'i';
        $pvidData['value'] = ubRouting::post('cvlan', 'vf');

        $saveData['oid'] = $this->snmpTemplate['vlan']['SAVE'];
        $saveData['type'] = 'i';
        $saveData['value'] = '1';

        $setData[] = $pvidData;
        $setData[] = $saveData;
        @$this->snmp->set($this->oltIp, $this->oltCommunityWrite, $setData);

        rcms_redirect(VlanManagement::MODULE_ONU_APPLY . '&oltid=' . ubRouting::get('oltid', 'mres'));
    }

    protected function routineAdd() {
        $this->ponizerRoutineAdd();
        $this->universalRoutineAdd();
    }

    protected function ponizerRoutineAdd() {
        $type = ubRouting::post('type', 'mres');
        $onuid = ubRouting::post('onuid', 'mres');
        $oltid = ubRouting::post('oltid', 'mres');
        $login = ubRouting::post('username', 'mres');
        $onumodelid = ubRouting::post('onumodelid', 'mres');
        if (!isset($this->onuMac[$onuid])) {
            $this->ponizerAdd($type, $oltid, $onumodelid, $onuid, $login);
        } else {
            if ($this->onuMac[$onuid]['oltid'] !== $oltid) {
                $this->ponizerFixOlt($type, $oltid, $onumodelid, $onuid, $login);
            }
        }
    }

    protected function ponizerAdd($type, $oltid, $onumodelid, $onuid, $login) {
        $pon = new PONizer();
        switch ($type) {
            case 'epon':
                $pon->onuCreate($onumodelid, $oltid, '', $onuid, '', $login);
        }
    }

    protected function ponizerFixOlt($type, $oltid, $onumodelid, $onuid, $login) {
        $pon = new PONizer();
        switch ($type) {
            case 'epon':
                $pon->onuCreate($onumodelid, $oltid, '', $onuid, '', $login);
        }
    }

    protected function universalRoutineAdd() {
        $login = trim(ubRouting::post('username', 'mres'));
        $svlanid = trim(ubRouting::post('svlanid', 'int'));
        $svlan = trim(ubRouting::post('svlan', 'int'));
        $cvlan = trim(ubRouting::post('cvlan', 'int'));
        if (!isset($this->universalAssign[$login])) {
            $universal = new UniversalQINQ();
            $this->cvlanDB->data('login', $login);
            $this->cvlanDB->data('svlan_id', $svlanid);
            $this->cvlanDB->data('cvlan', $cvlan);
            $this->cvlanDB->create();
            $universal->logAdd($login, $svlan, $cvlan);
        }
    }

}
