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
     * Vlan mode
     * 
     * @var string
     */
    protected $mode = 'none';

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
     * Placeholder for nyan_orm object for pononu table
     *
     * @var object
     */
    protected $ponDB = '';

    /**
     * General ubiiling config content
     * 
     * @var array
     */
    protected $altCfg = '';

    public function __construct($oltid = -1, $username = '') {
        $this->oltId = $oltid;
        $this->username = $username;
        $this->snmp = new SNMPHelper();
        $this->loadAlter();
        $this->switchesDB = new NyanORM('switches');
        $this->switchModelsDB = new NyanORM('switchmodels');
        $this->ponDB = new NyanORM('pononu');
        $this->setSnmpTemplateFile();
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
            throw new Exception('NO ONU WAS FOUND');
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
                                $this->guestOnus[$allOnuIndex[$onuId]] = array('id' => $allOnuIndex[$onuId], 'port' => $portNumber, 'vlan' => $value, 'interface' => $interfaceIndex[$onuId], 'type' => 'epon');
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
            $add .= "&username=" . ubRouting::get('username', 'mres');
        }
        $columns = array('Interface', 'MAC/Serial', 'Port', 'VLAN', 'Actions');
        $opts = '"order": [[ 0, "asc" ]]';
        $result .= wf_JqDtLoader($columns, self::MODULE_ONU_APPLY_AJAXONULIST . $add, false, __('Request'), 100, $opts);
        return($result);
    }

    /**
     * Generate ajax list of OLTs.
     * 
     * @return string
     */
    public function onuListAjaxRender() {
        $add = '';
        $json = new wf_JqDtHelper();
        $this->checkOltGuestVlan();

        if (!empty($this->guestOnus)) {
            foreach ($this->guestOnus as $each) {
                if (ubRouting::checkGet('username')) {
                    $controls = wf_modalAuto(wf_img_sized('skins/add_icon.png', '', '16', '16'), __('Assign VLAN'), $this->vlanAssignForm($each['id'], $each['port'], $each['vlan'], $each['type']));
                } else {
                    $controls = wf_modalAuto(wf_img_sized('skins/add_icon.png', '', '16', '16'), __('Assign VLAN'), $this->userNameForm($each['id'], $each['port'], $each['vlan'], $each['type']));
                }
                $data[] = trim($each['interface']);
                $data[] = trim($each['id']);
                $data[] = trim($each['port']);
                $data[] = trim($each['vlan']);
                $data[] = $controls;
                $json->addRow($data);

                unset($data);
            }
        }

        /*
          $countersSummary = wf_tag('br');
          $countersSummary .= wf_tag('br') . wf_tag('b') . __('Total') . ': ' . $countTotal . wf_tag('b', true) . wf_tag('br');
         * 
         */

        $json->getJson();
    }

    /**
     * Returns ONU creation form
     *
     * @return string
     */
    protected function userNameForm($onuId, $port, $vlan, $type) {
        $result = '';

        $inputs = wf_HiddenInput('vlanassign', 'true');
        $inputs .= wf_HiddenInput('oltid', ubRouting::get('oltid', 'mres'));
        $inputs .= wf_HiddenInput('onuid', $onuId);
        $inputs .= wf_HiddenInput('port', $port);
        $inputs .= wf_HiddenInput('vlan', $vlan);
        $inputs .= wf_HiddenInput('type', $type);
        $inputs .= wf_TextInput('username', __('Login'), '', true, 20);
        $inputs .= wf_Submit(__('Create'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');

        return ($result);
    }

}
