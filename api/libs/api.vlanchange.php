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
    protected $allRealnames = array();

    /**
     * Vlan mode
     * 
     * @var string
     */
    protected $mode = 'none';
    protected $oltCvlans = array();
    protected $usedCvlans = array();
    protected $universalAssign = array();
    protected $cvlan = -1;
    protected $svlan = -1;

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
            $this->setSnmpTemplateFile();
        }
    }

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

    protected function loadAllOnu() {
        $this->allMac = $data = $this->ponDB->getAll('mac');
        $this->alLSerial = $this->ponDB->getAll('serial');
    }

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
                if (isset(alLSerial[$onuid])) {
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
        $this->loadSnmpTemplate();
        $this->loadAllOnu();
        $this->loadAllRealnames();
        $this->setVlanMode();
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

    protected function loadOltDetails() {
        $this->switchesDB->where('id', '=', $this->oltId);
        $data = $this->switchesDB->getAll('id');
        return ($data);
    }

    protected function loadCvlans() {
        $this->loadUniversalAssign();
        $this->loadOltCvlans();
        $this->loadUsedCvlans();
    }

    protected function loadOltCvlans() {
        $this->oltqinqDB->where('swid', '=', $this->oltId);
        $this->oltqinqDB->where('port', '=', $this->oltInterface);
        $this->oltCvlans = $this->oltqinqDB->getAll('swid');
    }

    protected function loadUsedCvlans() {
        if (!empty($this->oltCvlans)) {
            if (isset($this->oltCvlans[$this->oltId])) {
                $this->cvlanDB->where('svlan_id', '=', $this->oltCvlans[$this->oltId]['svlan_id']);
                $this->usedCvlans = $this->cvlanDB->getAll('cvlan');
            }
        }
    }

    protected function loadUniversalAssign() {
        $this->cvlanDB->where('login', '=', $this->username);
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

    protected function setSvlanFromPool() {
        $this->svlanDB->where('id', '=', $this->oltCvlans[$this->oltId]['svlan_id']);
        $data = $this->svlanDB->getAll('id');
        $this->svlan = $data[$this->oltCvlans[$this->oltId]['svlan_id']]['svlan'];
    }

    protected function setSvlanFromUniversal() {
        $this->svlanDB->where('id', '=', $this->universalAssign[$this->username]['svlan_id']);
        $data = $this->svlanDB->getAll('id');
        $this->svlan = $data[$this->universalAssign[$this->username]['svlan_id']]['svlan'];
    }

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

    protected function setSvlan() {
        if ($this->cvlan) {
            if (empty($this->universalAssign)) {
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
                                $this->guestOnus[$allOnuIndex[$onuId]] = array('id' => $allOnuIndex[$onuId], 'port' => $portNumber, 'vlan' => $value, 'interface' => $interfaceIndex[$onuId], 'interface_olt' => $interfaceOltIndex[$onuId], 'type' => 'epon');
                            }
                        }
                    }
                }
            }
        }
        /*
          debarr($allOnuIndex);
          debarr($allOnuPvid);
          debarr($this->guestOnus);
         * 
         */
    }

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
                    $controls = wf_modalAuto(wf_img_sized('skins/add_icon.png', '', '16', '16'), __('Assign VLAN'), $this->changeVlanForm($each['id'], $each['port'], $each['vlan'], $each['type'], $each['interface'], $each['interface_olt']));
                } else {
                    $controls = wf_modalAuto(wf_img_sized('skins/add_icon.png', '', '16', '16'), __('Assign VLAN'), $this->userNameForm($each['id'], $each['port'], $each['vlan'], $each['type'], $each['interface'], $each['interface_olt']));
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

    /**
     * Returns ONU creation form
     *
     * @return string
     */
    protected function userNameForm($onuId, $port, $vlan, $type, $interface, $interface_olt) {
        $result = '';
        $inputs = wf_TextInput('username', __('Login'), '', true, 20, '', '', 'usernameInput---' . ubRouting::get('oltid', 'mres') . '---' . $onuId . '---' . $port);
        $inputs .= wf_Submit(__('Validate'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour', '', 'usernameForm---' . ubRouting::get('oltid', 'mres') . '---' . $onuId . '---' . $port, '', 'onsubmit="return validateVlanUsernameForm(' . ubRouting::get('oltid', 'mres') . ",'" . $onuId . "', " . $port . ',' . $vlan . ",'" . $type . "','" . $interface . "','" . $interface_olt . "'" . ')"');

        return ($result);
    }

    public function changeVlanForm($onuId, $port, $vlan, $type, $interface, $interface_olt) {
        $result = '';
        $oltDetails = $this->loadOltDetails();
        $this->clearInterface($interface_olt);
        $this->getVlanPair();

        $inputs = wf_TextInput('username', __('Login'), $this->username, true, 20);
        $checkedUsername = $this->getUsernameByOnu($onuId, true);
        if (!empty($checkedUsername)) {
            if ($this->username != $checkedUsername) {
                $inputs .= 'this onu is assigned to another user: ' . wf_Link('?module=userprofile&username=' . $checkedUsername, $checkedUsername);
            }
        }
        $inputs .= wf_delimiter() . $oltDetails[$this->oltId]['ip'] . ' ' . $oltDetails[$this->oltId]['location'] . wf_delimiter();
        if ($this->cvlan) {
            if (!empty($this->universalAssign)) {
                if (isset($this->universalAssign[$this->username])) {
                    $inputs .= 'Found UniversalQINQ assignment: ';
                } else {
                    $inputs .= 'New vlan pair assigned from pool: ';
                }
            } else {
                $inputs .= 'New vlan pair assigned from pool: ';
            }
            $inputs .= 'svlan - ' . $this->svlan . ", ";
            $inputs .= 'cvlan - ' . $this->cvlan . wf_delimiter();
        } else {
            $inputs .= 'There is no vlan pool for OLT or UniversalQINQ assignment for user' . wf_delimiter();
        }
        $inputs .= 'olt interface ' . $interface_olt . wf_delimiter();
        $inputs .= 'onu interface ' . $interface . wf_delimiter();
        $inputs .= 'onu identifier ' . $onuId . wf_delimiter();
        $inputs .= 'port ' . $port . wf_delimiter();
        $inputs .= 'guest vlan ' . $vlan . wf_delimiter();
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return($result);
    }

}
