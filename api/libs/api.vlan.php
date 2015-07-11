<?php

class OnuConfigurator {

    protected $allOnu = array();
    protected $allOlt = array();
    protected $allOltModels = array();

    public function __construct() {
        $this->loadOnu();
        $this->LoadAllOlt();
        $this->loadOltModels();
        $this->snmp = new SNMPHelper();
    }

    /**
     * 
     * Load all from `switches` to $allswitches
     */
    protected function LoadAllOlt() {
        $query = "SELECT `id`,`ip`,`snmp`,`modelid` from `switches` WHERE `desc` LIKE '%OLT%'";
        $raw = simple_queryall($query);
        if (!empty($raw)) {
            foreach ($raw as $io => $each) {
                if (!empty($each['snmp'])) {
                    $this->allOlt[$each['id']]['ip'] = $each['ip'];
                    $this->allOlt[$each['id']]['snmp'] = $each['snmp'];
                    $this->allOlt[$each['id']]['modelid'] = $each['modelid'];
                }
            }
        }
    }

    /**
     * 
     * Load all from `pononu` to $allOnu
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
     * Loads all available snmp models data into private data property
     * 
     * @return void
     */
    protected function loadOltModels() {
        $rawModels = zb_SwitchModelsGetAll();
        foreach ($rawModels as $io => $each) {
            $this->allOltModels[$each['id']]['modelname'] = $each['modelname'];
            $this->allOltModels[$each['id']]['snmptemplate'] = $each['snmptemplate'];
        }
    }

    protected function GetOltModelTemplate($modelid) {
        $result = '';
        if (!empty($this->allOltModels)) {
            $data = $this->allOltModels[$modelid];
            $result = $data['snmptemplate'];
        }
        return($result);
    }

    /**
     * 
     * get olt data like ip and snmp community
     * @param type $id int
     * @return type array
     */
    protected function GetOltData($id) {
        $result = array();
        if (!empty($this->allOlt)) {
            $Olt = $this->allOlt[$id];
            $result[] = $Olt['ip'];
            $result[] = $Olt['snmp'];
            $result[] = $Olt['modelid'];
        }
        return($result);
    }

    /**
     * 
     * Get onu data mac and olt ID to which onu is linked
     * @param type $login string
     * @return type array
     */
    protected function GetOnuMac($login) {
        $allOnu = $this->allOnu;
        $result = array();
        foreach ($allOnu as $eachOnu => $each) {
            if ($each['login'] == $login) {
                $result[] = $each['mac'];
                $result[] = $each['oltid'];
            }
        }
        return $result;
    }

    /**
     * 
     * Format heximal mac address to decimal or show error
     * @param type $macOnu string
     * @return type string
     */
    protected function MacHexToDec($macOnu) {
        if (check_mac_format($macOnu)) {
            $res = array();
            $args = explode(":", $macOnu);
            foreach ($args as $each) {
                $res[] = hexdec($each);
            }
            $string = implode(".", $res);
            return ($string);
        } else
            show_error("Wrong mac format (shoud be XX:XX:XX:XX:XX:XX)");
    }

    /**
     * 
     * Get snmp index which linked to onu
     * @param type $macOnu string
     * @param type $oltIp string
     * @param type $oltCommunity string
     * @return type int
     */
    protected function GetClientIface($macOnu, $oltIp, $oltCommunity, $oid) {
        $macOnu = $this->MacHexToDec($macOnu);
        $interface = ($oid . "." . $macOnu);
        $OltInt = $this->snmp->walk($oltIp, $oltCommunity, $interface);
        $tmp = explode("=", $OltInt);
        $tmp = explode(":", $tmp[1]);
        $tmp = trim($tmp[1]);
        return($tmp);
    }

    /**
     * 
     * Check wheather vlan already exists (if exists return false, if not return true)
     * @param type $vlan
     * @param type $oltIp
     * @param type $oltCommunity
     * @return type bool
     */
    protected function CheckOltVlan($vlan, $oltIp, $oltCommunity, $oid) {
        $tmp = snmp2_get($oltIp, $oltCommunity, $oid . "." . $vlan);
        $tmp = trim($tmp);
        if ($tmp == '1') {
            $res = false;
        } else {
            $res = true;
        }
        return ($res);
    }

    /**
     * 
     * Changes onu pvid by snmp query and if needed creates vlan
     * @param type $login
     * @param type $vlan
     * @return type string
     */
    public function ChangeOnuPvid($login, $vlan, $onu_port = '1') {
        $OnuData = $this->GetOnuMac($login);
        $OnuMac = $OnuData[0];
        $oltId = $OnuData[1];
        $oltData = $this->GetOltData($oltId);
        $oltIp = $oltData[0];
        $oltCommunity = $oltData[1];
        $template = $this->GetOltModelTemplate($oltData[2]);
        $iniData = rcms_parse_ini_file('config/snmptemplates/' . $template, true);
        $vlanCreateOid = $iniData['vlan']['CREATE'];
        $ChangeOnuPvidOid = $iniData['vlan']['PVID'];
        $SaveConfigOid = $iniData['vlan']['SAVE'];
        $CheckVlanOid = $iniData['vlan']['CHECK'];
        $IfIndexOid = $iniData['vlan']['IFINDEX'];
        $IfIndex = $this->GetClientIface($OnuMac, $oltIp, $oltCommunity, $IfIndexOid);
        $VlanCheck = $this->CheckOltVlan($vlan, $oltIp, $oltCommunity, $CheckVlanOid);
        $data = array();
        if ($VlanCheck) {
            //create vlan on OLT
            $data[] = array(
                'oid' => $vlanCreateOid . "." . $vlan,
                'type' => 'i',
                'value' => '4'
            );
        }
        //Change pvid on onu port by defolt port 1
        $data[] = array(
            'oid' => $ChangeOnuPvidOid . "." . $IfIndex . "." . $onu_port,
            'type' => 'i',
            'value' => "$vlan"
        );
        $data[] = array(
            'oid' => $SaveConfigOid,
            'type' => 'i',
            'value' => '1'
        );        
        $result = $this->snmp->set($oltIp, $oltCommunity, $data);
        return ($result);
    }

}

//AUTO CONFIGURATOR
class AutoConfigurator {

    protected $allsw = array();
    protected $allswlogin = array();
    protected $allmodel = array();
    protected $allasing = array();
    protected $allterm = array();

    public function __construct() {
        $this->LoadModels();
        $this->LoadSwLogin();
        $this->LoadSwitches();
        $this->LoadAssign();
        $this->LoadTerminators();
        $this->SnmpHelper = new SNMPHelper();
    }

    protected function LoadTerminators() {
        $tmp = GetAllTerm();
        if (!empty($tmp)) {
            foreach ($tmp as $io) {
                $this->allterm[$io['id']] = $io;
            }
        }
    }

    protected function LoadAssign() {
        $tmp = get_all_swassign();
        if (!empty($tmp)) {
            foreach ($tmp as $io => $each) {
                $this->allasing[$each['id']] = $each;
            }
        }
    }

    protected function LoadModels() {
        $tmp = get_all_model();
        if (!empty($tmp)) {
            foreach ($tmp as $io => $each) {
                $this->allmodel[$each['id']] = $each;
            }
        }
    }

    protected function LoadSwitches() {
        $AllSwitchesTmp = zb_SwitchesGetAll();
        if (!empty($AllSwitchesTmp)) {
            foreach ($AllSwitchesTmp as $io => $each) {
                $this->allsw[$each['id']] = $each;
            }
        }
    }

    protected function LoadSwLogin() {
        $AllLoginTmp = get_all_swlogin();
        if (!empty($AllLoginTmp)) {
            foreach ($AllLoginTmp as $io => $each) {
                $this->allswlogin[$each['id']] = $each;
            }
        }
    }

    /**
     * 
     * check by ip wheather switch is vlan terminator
     * @param type $ip
     * @return type bool
     */
    protected function CheckTermIP($ip) {
        $tmp = $this->allterm;
        $res = '';
        if (!empty($tmp)) {
            foreach ($tmp as $io) {
                if ($io['ip'] == $ip) {
                    $res = true;
                } else {
                    $res = false;
                }
            }
        }
        return($res);
    }

    /**
     * 
     * get parentid for switch
     * @param type $swid
     * @return type int `id`
     */
    public function GetSwUplinkID($swid) {
        $result = '';
        foreach ($this->allsw as $io) {
            if ($io['id'] == $swid) {
                $result = $io['parentid'];
            }
        }
        if (isset($result)) {
            return($result);
        }
    }

    /**
     * 
     * get switch which port beyond to user
     * @param type $login
     * @return type array
     */
    protected function GetSwParam($login) {
        $AllAssign = $this->allasing;
        $param = array();
        foreach ($AllAssign as $io) {
            if ($io['login'] == $login) {
                $param[] = $io['switchid'];
                $param[] = $io['port'];
            }
        }
        if (!empty($param)) {
            return($param);
        }
    }

    /**
     * 
     * get switch connection data snmp\login + password
     * @param type $swid
     * @return type array
     */
    protected function GetConnParam($swid) {
        $AllSwitchesLogin = $this->allswlogin;
        $param = array();
        foreach ($AllSwitchesLogin as $io) {
            if ($io['swid'] == $swid) {
                if (!empty($io['community'])) {
                    $param[] = $io['community'];
                    $param[] = '';
                    $param[] = '';
                } else {
                    $param[] = '';
                    $param[] = $io['swlogin'];
                    $param[] = $io['swpass'];
                }
            }
        }
        if (!empty($param)) {
            return($param);
        }
    }

    /**
     * 
     * get uplink switch ip by parentid
     * @param type $parentid
     * @return type int `id`
     */
    protected function GetSwUplinkIP($parentid) {
        $result = '';
        foreach ($this->allsw as $io) {
            if ($io['id'] == $parentid) {
                $result = $io['ip'];
            }
        }
        return($result);
    }

    /**
     * 
     * get switch ip by id
     * @param type $swid
     * @return type string `ip`
     */
    protected function GetCurSwIP($swid) {
        $AllSwitches = $this->allsw;
        $swip = '';
        foreach ($AllSwitches as $io) {
            if ($io['id'] == $swid) {
                $swip = $io['ip'];
            }
        }
        if (!empty($swip)) {
            return($swip);
        }
    }

    /**
     * get switches id and modelid by its ip
     * @param type $ip
     * @return type array
     */
    protected function GetModelidByIP($ip) {
        $tmp = $this->allsw;
        $result = array();
        foreach ($tmp as $io) {
            if ($io['ip'] == $ip) {
                $result[] = $io['modelid'];
                $result[] = $io['id'];
            }
        }
        return($result);
    }

    /**
     * 
     * Get switch modelname and ports by it's id
     * @param type $swid
     * @return type array
     */
    protected function GetSwModelParam($swid) {
        $AllModels = $this->allmodel;
        $AllSwitches = $this->allsw;
        $param = array();
        foreach ($AllSwitches as $each) {
            if ($each['id'] == $swid) {
                $modelid = $each['modelid'];
            }
        }
        foreach ($AllModels as $io) {
            if ($io['id'] == $modelid) {
                $param[] = $io['ports'];
                $param[] = $io['modelname'];
            }
        }
        if (!empty($param)) {
            return($param);
        }
    }

    public function ShowSwAllLogin() {
        $tablecells = wf_TableCell(__('ID'));
        $tablecells .= wf_TableCell(__('SwID'));
        $tablecells .= wf_TableCell(__('Username'));
        $tablecells .= wf_TableCell(__('Password'));
        $tablecells .= wf_TableCell(__('method'));
        $tablecells .= wf_TableCell(__('community'));
        $tablecells .= wf_TableCell(__('enable'));
        $tablecells .= wf_TableCell(__('Actions'));
        $tablerows = wf_TableRow($tablecells, 'row1');
        foreach ($this->allswlogin as $login) {
            $query_switches = "SELECT * FROM `switches` WHERE `id`='" . $login['swid'] . "'";
            $tmp = simple_query($query_switches);
            $location = $tmp['location'];
            $tablecells = wf_TableCell($login['id']);
            $tablecells .= wf_TableCell(($location));
            $tablecells .= wf_TableCell($login['swlogin']);
            $tablecells .= wf_TableCell($login['swpass']);
            $tablecells .= wf_TableCell($login['method']);
            $tablecells .= wf_TableCell($login['community']);
            $tablecells .= wf_TableCell($login['enable']);
            $actionlinks = wf_JSAlert('?module=switchlogin&delete=' . $login['id'], web_delete_icon(), 'Removing this may lead to irreparable results');
            $actionlinks .= wf_JSAlert('?module=switchlogin&edit=' . $login['id'], web_edit_icon(), 'Are you serious');
            $tablecells .= wf_TableCell($actionlinks);
            $tablerows .= wf_TableRow($tablecells, 'row3');
        }
        $result = wf_TableBody($tablerows, '100%', '0', 'sortable');
        show_window(__('Switch Logins'), $result);
    }

    /**
     * 
     * SNMP create Vlans on uplink switches
     * @param type $UplinkId
     * @param type $termip
     * @param type $vlan
     */
    public function TerminatorSnmpControl($UplinkId, $vlan) {
        while (!empty($UplinkId)) {
            $upip = $this->GetSwUplinkIP($UplinkId);
            $TermData = $this->CheckTermIP($upip);
            if ($TermData) {
                break;
            }
            $upModelId = $this->GetModelidByIP($upip);
            $upSwid = $upModelId[1];
            $upModelParam = $this->GetSwModelParam($upSwid);
            $modelname = $upModelParam[1];
            $upConn = $this->GetConnParam($upSwid);
            $upCommunity = $upConn[0];
            $upSwLogin = $upConn[1];
            $upPassword = $upConn[2];
            if (file_exists('config/autoconfig/' . $modelname)) {
                $IniData = rcms_parse_ini_file('config/autoconfig/' . $modelname, true);
                if ($IniData['define']['TYPE'] == 'huawei') {
                    $VlanCreateOid = $IniData['oid']['VLANCREATE'] . $vlan;
                    $SaveConfigOid = $IniData['oid']['CONFIGSAVE'];
                    $TypeCreate = "i";
                    $TypeSave = "i";
                    $upData = array();
                    $upData[] = array(
                        'oid' => $VlanCreateOid,
                        'type' => $TypeCreate,
                        'value' => '4'
                    );
                    $upData[] = array(
                        'oid' => $SaveConfigOid,
                        'type' => $TypeSave,
                        'value' => '1'
                    );
                    $upset = $this->SnmpHelper->set($upip, $upCommunity, $upData);
                } elseif ($IniData['define']['TYPE'] == 'dlink') {
                    $data = array();
                    $VlanCreateOid = $IniData['oid']['VLANCREATE'] . "." . $vlan;
                    $TypeCreate = "i";
                    $CreateValue = $IniData['oid']['CREATEVALUE'];
                    $data[] = array(
                        'oid' => $VlanCreateOid,
                        'type' => $TypeCreate,
                        'value' => $CreateValue
                    );
                    if (isset($IniData['oid']['TAGGEDVLAN'])) {
                        $MakeTaggedVlan = $IniData['oid']['TAGGEDVLAN'] . $vlan;
                        $TypeAdd = "x";
                        $data[] = array(
                            'oid' => $MakeTaggedVlan,
                            'type' => $TypeAdd,
                            'value' => $plist_add_tagged
                        );
                    }
                    if (isset($IniData['oid']['MAKEACTIVE'])) {
                        $MakeActive = $IniData['oid']['MAKEACTIVE'] . $vlan;
                        $TypeMakeActive = "i";
                        $data[] = array(
                            'oid' => $MakeActive,
                            'type' => $TypeMakeActive,
                            'value' => '1'
                        );
                    }
                    if (isset($IniData['oid']['NAME'])) {
                        $NameVlan = $IniData['oid']['NAME'] . $vlan;
                        $TypeName = "s";
                        $data[] = array(
                            'oid' => $NameVlan,
                            'type' => $TypeName,
                            'value' => "vlan$vlan"
                        );
                    }
                    $SaveConfigOid = $IniData['oid']['CONFIGSAVE'];
                    $TypeSave = "i";
                    $SaveValue = $IniData['oid']['SAVEVALUE'];
                    $data[] = array(
                        'oid' => $SaveConfigOid,
                        'type' => $TypeSave,
                        'value' => $SaveValue
                    );
                    //$upset = $this->SnmpHelper->set($upip, $upCommunity, $data);
                    return($data);
                }
                $UplinkId = $this->GetSwUplinkID($UplinkId);
            } else {
                show_error("file for uplink not set (id $UplinkId)");
                break;
            }
        }
    }

    /**
     * 
     * Create vlans untag on abon's port and tagged on uplink ports by snmp
     * @param type $vlan
     * @param type $login
     * @return snmpresult (bad if empty)
     */
    public function sw_snmp_control2($vlan, $login) {
        $param = $this->GetSwParam($login);
        $swid = $param['0'];
        $swip = $this->GetCurSwIP($swid);
        $ModelParam = $this->GetSwModelParam($swid);
        $conn = $this->GetConnParam($swid);
        $swport = $param['1'];
        $modelname = $ModelParam[1];
        $swports = $ModelParam[0];
        $community = $conn[0];
        $swlogin = $conn[1];
        $password = $conn[2];
        $UplinkId = $this->GetSwUplinkID($swid);
        $TermData = $this->CheckTermIP($termip);
        if (file_exists(CONFIG_PATH . 'autoconfig/' . $modelname)) {
            if ($TermData == 'false') {
                $this->TerminatorSnmpControl($UplinkId, $vlan);
            }
            $IniData = parse_ini_file(CONFIG_PATH . 'autoconfig/' . $modelname, true);
            if ($IniData['define']['TYPE'] == 'huawei') {
                if ($swports == '26') {
                    $upPorts = explode(',', $IniData['ports']['UPLINK']);
                    if (empty($upPorts[1])) {
                        if ($upPorts[0] == '25') {
                            $plist_add = "000000200000000000";
                        } else {
                            $plist_add = "000000400000000000";
                        }
                    } else {
                        $plist_add = "000000600000000000";
                    }
                }
                include(CONFIG_PATH . 'autoconfig/huawei_offset.php');
                if (!empty($offset) or $offset == "0") {
                    $plist_add[$group] = $offset;
                }
                $VlanCreateOid = $IniData['oid']['VLANCREATE'] . $vlan;
                $VlanAddOid = $IniData['oid']['VLANADD'] . $vlan;
                $SaveConfigOid = $IniData['oid']['CONFIGSAVE'];
                $TypeCreate = "i";
                $TypeAdd = "x";
                $TypeSave = "i";
                $data = array();
                $data[] = array(
                    'oid' => $VlanCreateOid,
                    'type' => $TypeCreate,
                    'value' => '4'
                );
                $data[] = array(
                    'oid' => $VlanAddOid,
                    'type' => $TypeAdd,
                    'value' => $plist_add
                );
                $data[] = array(
                    'oid' => $SaveConfigOid,
                    'type' => $TypeSave,
                    'value' => '1'
                );
                $set = $this->SnmpHelper->set($swip, $community, $data);
            } elseif ($IniData['define']['TYPE'] == 'dlink') {
                if ($swports == '26') {
                    $upPorts = explode(',', $IniData['ports']['UPLINK']);
                    if (empty($upPorts[1])) {
                        switch ($upPorts[0]) {
                            case '25':
                                $plist_add_tagged = "0000008000000000";
                                break;
                            case '26':
                                $plist_add_tagged = "0000004000000000";
                                break;
                        }
                    } else {
                        $plist_add_tagged = "000000C000000000";
                    }
                } elseif ($swports == '28') {
                    $upPorts = explode(',', $IniData['ports']['UPLINK']);
                    include(CONFIG_PATH . 'autoconfig/dlink_ports.php');
                }
                include(CONFIG_PATH . 'autoconfig/dlink_port_add.php');
                $plist_add_untagged = "0000000000000000";
                $plist_add_untagged[$group] = $plist_add;
                $plist_add_tagged[$group] = $plist_add;
                $data = array();
                $VlanCreateOid = $IniData['oid']['VLANCREATE'] . $vlan;
                $TypeCreate = "i";
                $data[] = array(
                    'oid' => $VlanCreateOid,
                    'type' => $TypeCreate,
                    'value' => '5'
                );
                if (isset($IniData['oid']['TAGGEDVLAN'])) {
                    $TypeAdd = "x";
                    $MakeTaggedVlan = $IniData['oid']['TAGGEDVLAN'] . $vlan;
                    $data[] = array(
                        'oid' => $MakeTaggedVlan,
                        'type' => $TypeAdd,
                        'value' => $plist_add_tagged
                    );
                }
                if (isset($IniData['oid']['UNTAGGEDVLAN'])) {
                    $MakeUntuggedVlan = $IniData['oid']['UNTAGGEDVLAN'] . $vlan;
                    $data[] = array(
                        'oid' => $MakeUntuggedVlan,
                        'type' => $TypeAdd,
                        'value' => $plist_add_untagged
                    );
                }
                if (isset($IniData['oid']['MAKEACTIVE'])) {
                    $TypeMakeActive = "i";
                    $MakeActive = $IniData['oid']['MAKEACTIVE'] . $vlan;
                    $data[] = array(
                        'oid' => $MakeActive,
                        'type' => $TypeMakeActive,
                        'value' => '1'
                    );
                }
                if (isset($IniData['oid']['NAME'])) {
                    $TypeName = "s";
                    $NameVlan = $IniData['oid']['NAME'] . $vlan;
                    $data[] = array(
                        'oid' => $NameVlan,
                        'type' => $TypeName,
                        'value' => "vlan$vlan"
                    );
                }
                $SaveConfigOid = $IniData['oid']['CONFIGSAVE'];
                $TypeSave = "i";
                $data[] = array(
                    'oid' => $SaveConfigOid,
                    'type' => $TypeSave,
                    'value' => '3'
                );
                $set = $this->SnmpHelper->set($swip, $community, $data);
            }
            return ($set);
        } else {
            show_error('file not set');
        }
    }

}

/**
 * 
 * Select all from `switchportassign` and return it
 * @return type array
 */
function get_all_swassign() {
    $query = "SELECT * FROM `switchportassign`";
    $res = simple_queryall($query);
    return($res);
}

/**
 * 
 * Select all from `switchmodels` and return it
 * @return type array
 */
function get_all_model() {
    $query = "SELECT * FROM `switchmodels`";
    $res = simple_queryall($query);
    return($res);
}

/**
 * 
 * Select all from `switch_login` and return it
 * @return type array
 */
function get_all_swlogin() {
    $query = "SELECT * FROM `switch_login`";
    $result = simple_queryall($query);
    return($result);
}

/**
 * 
 * Select data from `switch_login` by `id` and return it
 * @param type $id
 * @return type array
 */
function get_swlogin_param($id) {
    $query = "SELECT * FROM `switch_login` WHERE `id`='" . $id . "'";
    $result = simple_query($query);
    return($result);
}

/**
 * 
 * Form for editing data for switches login (ip, snmp community, login, password)
 * @param type $id int
 */
function swlogin_edit_form($id) {
    $id = vf($id);
    $param = get_swlogin_param($id);
    $conn = array('SSH' => __('SSH'), 'TELNET' => __('TELNET'), 'SNMP' => __('SNMP'));
    $enable = array('yes' => __('yes'), 'no' => __('no'));
    $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
    $inputs = wf_HiddenInput('edit', 'true');
    $inputs.= sw_selector($param['swid']) . ' ' . __('Switch Model') . ' ' . wf_tag('br');
    $inputs .= wf_Selector('editconn', $conn, __('Connection method'), $param ['method'], true);
    $inputs.= wf_TextInput('editswlogin', __('Username') . $sup, $param ['swlogin'], true, '20');
    $inputs.= wf_TextInput('editswpassword', __('Password') . $sup, $param['swpass'], true, '20');
    $inputs.= wf_TextInput('editrwcommunity', __('SNMP RW Community ') . $sup, $param['community'], true, '20');

    $inputs.= wf_Selector('editenable', $enable, __('enable propmpt for cisco,bdcom,etc (should be same as password)'), $param['enable'], true);
    $inputs.= wf_Tag('br');

    $inputs.= wf_Submit(__('Save'));
    $form = wf_Form("", 'POST', $inputs, 'glamour');
    $form.=wf_Link('?module=switchlogin', 'Back', true, 'ubButton');
    show_window(__('Edit'), $form);
}

/**
 * 
 * delete login data for switch by id
 * @param type $id int
 */
function swlogin_delete($id) {
    $id = vf($id);

    $query = "DELETE FROM `switch_login` WHERE `id`='" . $id . "'";
    nr_query($query);
    log_register('DELETE Switch Login [' . $id . ']');
}

/**
 * 
 * Add login data for switch
 * @param type $swmodel string
 * @param type $login string
 * @param type $pass string
 * @param type $method string
 * @param type $community string
 * @param type $enable string
 */
function swlogin_add($swmodel, $login, $pass, $method, $community, $enable) {
    $swmodel = vf($swmodel);
    $login = vf($login);
    $pass = vf($pass);
    $method = vf($method);
    $community = vf($community);
    $enable = vf($enable);
    $query = " INSERT INTO `switch_login` (
                                `id`,
                                `swid`,
                                `swlogin`,
                                `swpass`,
                                `method`,
                                `community`,
				`enable`
		)
                VALUES (
                                NULL,
                                '" .
            $swmodel . "',
                                '" . $login . "',
                                '" . $pass . "',
                                '" . $method . "',
                                '" . $community . "',
				'" . $enable . "'
                )
                ";
    nr_query($query);
    log_register('ADD Switch login `' . $swmodel . '`');
}

/**
 * 
 * Get all from `switches` and return it
 * @return type array
 */
function get_all_sw() {
    $query = "SELECT * from `switches`";
    $result = simple_queryall($query);
    return($result);
}

/**
 * 
 * Get data from `switchmodels` by `id` from `switches` and return it
 * @param type $id
 * @return type array
 */ function get_sw_modelname($id) {
    $query = "SELECT * FROM `switchmodels` WHERE `id` IN (SELECT `modelid` FROM `switches` WHERE `id`='" . $id . "')";
    $modelid = simple_query($query);
    return($modelid['modelname']);
}

/**
 * 
 * @param type $current
 * @return type
 */ function sw_selector($current = '') {
    $allsw = get_all_sw();
    $result = '<select name="swmodel">';
    if (!empty($allsw)) {
        foreach ($allsw as $io => $eachsw) {
            if ($current == $eachsw['id']) {

                $flag = 'SELECTED';
            } else {
                $flag = '';
            }
            $query_switches = "SELECT * FROM `switches` WHERE `id`='" . $eachsw['id'] . "'";
            $location = simple_query($query_switches);
            $location = $location['location'];
            $result.='<option value="' . $eachsw['id'] . '" ' . $flag . '>' . $location . '</option>';
        }
    }
    $result.='</select>';
    return ( $result);
}

//VLANGEN
/**
 * 
 * @param type $login
 * @return type
 */ function web_ProfileVlanControlForm($login) {

    $login = mysql_real_escape_string($login);
    $query = "SELECT * from `vlanhosts` WHERE `login`='" . $login . "'";
    $formStyle = 'glamour';
    $alterconf = rcms_parse_ini_file(CONFIG_PATH . 'alter.ini');
    if ($alterconf['VLAN_IN_PROFILE'] == 1) {
        $data = simple_query($query);
        if (!empty($data)) {
            $current_vlan = $data ['vlan'];
            $current_vlan_pool = $data['vlanpoolid'];
            $query_desc = "SELECT * FROM `vlan_pools` WHERE `id`='" . $current_vlan_pool . "'";

            $current_vlan_pool_desc = simple_query($query_desc);
            $current_vlan_pool_descr = $current_vlan_pool_desc['desc'];

            $cells = wf_TableCell(__('Vlan Pool'), '30%', 'row2');

            $cells.= wf_TableCell($current_vlan_pool_descr);
            $rows = wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Vlan'), '30%', 'row2');
            $cells.= wf_TableCell($current_vlan);
            $rows.= wf_TableRow($cells, 'row3');

            $result = wf_TableBody($rows, '100%', '0');
            return($result);
        }
    }
}

/**
 * 
 * @param type $cur_vlan
 * @param type $new_vlan_pool_id
 * @param type $new_free_vlan
 * @param type $login
 * @param type $qinq
 */
function zb_VlanChange($cur_vlan, $new_vlan_pool_id, $new_free_vlan, $login, $qinq) {
    $ip = zb_UserGetIP($login);
    vlan_delete_host($login);
    vlan_qinq_delete_host($login);
    if ($qinq == 0) {
        vlan_add_host($new_vlan_pool_id, $new_free_vlan, $login);
    } else {
        $svlan = vlan_get_pool_params($new_vlan_pool_id);
        $svlan = $svlan['svlan'];

        vlan_pool_add_qinq_host($new_vlan_pool_id, $svlan, $new_free_vlan, $login);
    } OnVlanConnect($ip, $new_free_vlan);
}

/**
 * 
 * @param type $login
 * @return type
 */
function web_VlanDelete($login) {
    $inputs = wf_HiddenInput('vlandel', '', 'delete', true, '25');
    $inputs.= wf_Submit(__('Delete'));
    $result = wf_form("", 'POST', $inputs, 'floatpanels');
    return($result);
}

/**
 * 
 * @global type $cur_vlan
 * @return type
 */
function web_VlanChangeFormService() {
    $inputs = vlan_pool_selector() . ' ' . __('New VLAN');
    $inputs.= wf_delimiter();
    $inputs.= wf_Submit(__('Save'));
    $result = wf_Form("", 'POST', $inputs, 'floatpanels');
    return($result);
}

/**
 * 
 * @return type
 */
function GetAllTerm() {
    $query = "SELECT * FROM `vlan_terminators`";
    $tmp = simple_queryall($query);
    return($tmp);
}

/**
 * 
 * @param type $netid
 * @return type
 */
function GetTermRemoteByNetid($netid) {
    $query = "SELECT `remote-id` FROM `vlan_terminators` where `netid`='" . $netid . "'";
    $remote = simple_query($query);
    return($remote ['remote-id'] );
}

/**
 * 
 * @param type $netid
 * @return type
 */
function GetTermIdByNetid($netid) {
    $query = "SELECT `id` FROM `vlan_terminators` where `netid`='" . $netid . "'";

    $remote = simple_query($query);
    return($remote['id']);
}

/**
 * 
 * @param type $term_id
 * @return type
 */
function term_get_params($term_id) {
    $query = 'SELECT * from `vlan_terminators` WHERE `id`="' . $term_id . '"';
    $result = simple_query($query);
    return($result);
}

/**
 * 
 * @param type $netid
 * @param type $vlanpoolid
 * @param type $ip
 * @param type $type
 * @param type $username
 * @param type $password
 * @param type $remote
 * @param type $interface
 * @param type $relay
 */
function term_add($netid, $vlanpoolid, $ip, $type, $username, $password, $remote, $interface, $relay) {
    $netid = vf($netid);
    $vlanpoolid = vf($vlanpoolid);
    $ip = vf($ip);
    $type = vf($type);
    $username = vf($username);
    $password = vf($password);
    $remote = vf($remote);
    $interface = vf($interface);
    $relay = vf($relay);
    $query = " INSERT INTO `vlan_terminators` (
                                `id`,
                                `netid`,
                                `vlanpoolid`,
                                `ip`,
                                `type`,
                                `username`,
				`password`,
				`remote-id`,
				`interface`,
				`relay`
		)
                VALUES (
                                NULL,
                                '" . $netid . "',
                                '" . $vlanpoolid . "',
                                '" . $ip . "',
                                '" . $type . "',
                                '" . $username . "',
				'" . $password . "',
				'" . $remote . "',
				'" . $interface . "',
				'" . $relay . "'
                )
                ";
    nr_query($query);
    log_register('ADD Terminator `' . $type . '`');
}

/**
 * 
 * @param type $term_id
 */
function term_show_editform($term_id) {
    $term_id = vf($term_id);
    $termdata = term_get_params($term_id);
    $type = array('FreeBSD' => __('FreeBSD'), 'Linux' => __('Linux'), 'Cisco' => __('Cisco'));
    $sup = wf_tag(
                    'sup') . '*' . wf_tag('sup', true);
    $inputs = wf_HiddenInput('termedit', 'true');
    $inputs.= multinet_network_selector($termdata['netid']) . ' ' . __('Network') . ' ' . wf_tag('br');
    $inputs.= vlan_pool_selector($termdata['vlanpoolid']) . ' ' . __('Vlan Pool ID') . ' ' . wf_tag('br');
    $inputs.= wf_TextInput('editip', __('IP') . $sup, $termdata['ip'], true, '20');
    $inputs.= wf_Selector('edittype', $type, __('Type'), $termdata['type'], true);
    $inputs.= wf_TextInput('editusername', __('Username') . $sup, $termdata['username'], true, '20');
    $inputs .= wf_TextInput('editpassword', __('Password') . $sup, $termdata['password'], true, '20');
    $inputs.= wf_TextInput('editremoteid', __('Remote-ID') . $sup, $termdata['remote-id'], true, '20');
    $inputs.= wf_TextInput('editinterface', __('Interface') . $sup, $termdata['interface'], true, '20');
    $inputs.= wf_TextInput('editrelay', __('Relay Address') . $sup, $termdata['relay'], true, '20');
    $inputs.= wf_Tag('br');
    $inputs.= wf_Submit(__('Save'));
    $form = wf_Form("", 'POST', $inputs, 'glamour');

    $form.=wf_Link('?module=nas', 'Back', true, 'ubButton');
    show_window(__('Edit'), $form);
}

/**
 * 
 * @param type $term_id
 */
function delete_term($term_id) {
    $term_id = vf($term_id);
    $query = "DELETE FROM `vlan_terminators` WHERE `id`='" . $term_id . "'";
    nr_query($query);
    log_register('DELETE Terminator [' . $term_id . ']');
}

function terminators_show_form() {
    $type = array('FreeBSD' => __('FreeBSD'), 'Linux' => __('Linux'), 'Cisco' => __('Cisco'));
    $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
    $inputs = wf_HiddenInput('addterm', 'true');
    $inputs.= multinet_network_selector() . ' ' . __('Network') . ' ' . wf_tag('br');
    $inputs.= vlan_pool_selector() . ' ' . __('Vlan Pool ID') . ' ' . wf_tag('br');
    $inputs.= wf_TextInput('ip', __('IP') . $sup, '', true, '20');
    $inputs.= wf_Selector('type', $type, __('Type'), '', true);
    $inputs.= wf_TextInput('username', __('Username') . $sup, '', true, '20');
    $inputs.= wf_TextInput('password', __('Password') . $sup, '', true, '20');
    $inputs.= wf_TextInput('remoteid', __('Remote-ID') . $sup, '', true, '20');
    $inputs.= wf_TextInput('interface', __('Interface') . $sup, '', true, '20');
    $inputs.= wf_TextInput('relay', __('Relay Address') . $sup, '', true, '20');
    $inputs.= wf_Tag('br');
    $inputs.= wf_Submit(__('Add'));
    $form = wf_Form("", 'POST', $inputs, 'glamour');
    show_window(__('ADD Terminator'), $form);
}

function show_all_terminators() {
    $terminators = GetAllTerm();
    $tablecells = wf_TableCell(__('ID'));
    $tablecells .= wf_TableCell(__('Network'));
    $tablecells .= wf_TableCell(__('Vlan pool id'));

    $tablecells .= wf_TableCell(__('IP'));
    $tablecells .= wf_TableCell(__('Type'));
    $tablecells .= wf_TableCell(__('Username'));
    $tablecells .= wf_TableCell(__('Password'));
    $tablecells .= wf_TableCell(__('Remote-ID'));
    $tablecells .= wf_TableCell(__('Interface'));
    $tablecells .= wf_TableCell(__('Relay Address'));
    $tablecells .= wf_TableCell(__('Actions'));
    $tablerows = wf_TableRow($tablecells, 'row1');
    if (!empty($terminators)) {
        foreach ($terminators as $term) {
            $tablecells = wf_TableCell($term['id']);
            $tablecells .= wf_TableCell($term ['netid']);
            $tablecells .= wf_TableCell($term['vlanpoolid']);
            $tablecells .= wf_TableCell($term['ip']);
            $tablecells .= wf_TableCell($term['type']);
            $tablecells .= wf_TableCell($term['username']);
            $tablecells .= wf_TableCell($term['password']);
            $tablecells .= wf_TableCell($term['remote-id']);
            $tablecells .= wf_TableCell($term['interface']);
            $tablecells .= wf_TableCell($term['relay']);
            $actionlinks = wf_JSAlert('?module=nas&deleteterm=' . $term['id'], web_delete_icon(), 'Removing this may lead to irreparable results');

            $actionlinks .= wf_JSAlert('?module=nas&editterm=' . $term['id'], web_edit_icon(), '');
            $tablecells .= wf_TableCell($actionlinks);

            $tablerows .= wf_TableRow($tablecells, 'row3')

            ;
        }
    }
    $result = wf_TableBody($tablerows, '100%', '0', 'sortable');
    show_window(__('Terminators'), $result);
}

/* 	Work on dispatcher for
 * 	execute remote or local
 * 	scripts to create vlan, route
 * 	or relay and snooping
 * 	for FreeBSD, Linux, Cisco 35xx/37xx
 */

/**
 * 
 * @param type $ip
 * @param type $vlan
 */
function OnVlanConnect($ip, $vlan) {
    multinet_rebuild_all_handlers();
    $netid = GetNetidByIP($ip)

    ;
    $termid = GetTermIdByNetid($netid);
    $term_data = term_get_params($termid);
    $term_ip = $term_data ['ip'];
    $term_type = $term_data['type'];
    $term_user = $term_data['username'];
    $term_pass = $term_data['password'];
    $term_int = $term_data['interface'];
    $relay = $term_data['relay'];
    if ($term_ip == '127.0.0.1') {
        if ($term_type == 'FreeBSD') {
            $res = shell_exec("./config/scripts/bsd.local.sh $term_int $ip $vlan");
        }
        if ($term_type == 'Linux') {
            $res = shell_exec("./config/scripts/linux.local.sh");
        }
    } else {

        if ($term_type == 'FreeBSD') {
            $res = shell_exec("./config/scripts/bsd.remote.sh $term_user $term_pass $term_int $ip $vlan");
        }
        if ($term_type == 'Linux') {
            $res = shell_exec("

./config/scripts/linux.remote.sh $term_user $term_pass $term_int $ip $vlan");
        }
        if ($term_type == 'Cisco') {
            $res = shell_exec("./config/scripts/cisco.sh $term_user $term_pass $vlan $term_int $relay $term_ip");
        }
    }
}

/**
 * 
 * @param type $ip
 * @return type
 */
function GetNetidByIP($ip) {
    $query = "SELECT `netid` FROM `nethosts` WHERE `ip`='" . $ip . "'";
    $res = simple_query($query);
    return($res['netid']);
}

/**
 * 
 * @param type $ip
 * @return type
 */
function UserGetLoginByIP($ip) {
    $query = "SELECT * FROM `users` WHERE `ip`='" . $ip . "'";
    $res = simple_query($query
    );
    $login = $res['login'];
    return($login);
}

/**
 * 
 * @param type $login
 * @return type
 */
function UserGetVlan($login) {


    $query = "select vlan from vlanhosts where login='" . $login . "'";
    $vlan = simple_query($query);
    return($vlan['vlan']);
}

/**
 * 
 * @param type $login
 * @return type
 */
function UserGetQinQVlan($login) {
    $query = "SELECT `svlan`,`cvlan` FROM `vlanhosts_qinq` WHERE `login`='" . $login . "'";
    $vlans = simple_query($query);
    $svlan = $vlans['svlan'];
    $cvlan = $vlans['cvlan'];
    $array = array($svlan, $cvlan);
    $vlan = implode(".", $array);
    return($vlan);
}

/**
 * 
 * @param type $vlanpoolid
 * @return type
 */
function UserGetSvlan($vlanpoolid) {
    $query = "SELECT `svlan` FROM `vlan_pools` WHERE `id`='" . $vlanpoolid . "'";

    $svlan = simple_query($query);
    return($svlan['svlan']);
}

/**
 * 
 * @param type $vlanpoolid
 * @return type
 */
function vlan_pool_get_qinq($vlanpoolid) {
    $query = "SELECT `qinq` FROM `vlan_pools` WHERE `id`='" . $vlanpoolid . "'";
    $qinq = simple_query($query);
    return($qinq['qinq'] );
}

/**
 * 
 * @param type $currentvlanpoolid
 * @return type
 */ function vlan_pool_selector($currentvlanpoolid = '') {
    $allvlanpools = vlan_get_all_pools();
    $result = '<select name="vlanpoolselect">';
    if (!empty($allvlanpools)) {
        foreach ($allvlanpools as $io => $eachvlanpool) {
            if ($currentvlanpoolid == $eachvlanpool ['id']) {
                $flag = 'SELECTED';
            } else {
                $flag = '';
            } $result.= '<option value="' . $eachvlanpool['id'] . '" ' . $flag . '>' . $eachvlanpool['desc'] . '</option>';
        }
    } $result.= '</select>';
    return ($result);
}

/**
 * 
 * @return type
 */ function vlan_get_all_pools() {
    $query = "SELECT * from `vlan_pools`";
    $result = simple_queryall($query);
    return($result);
}

function vlan_show_pool_delete_form() {
    $allvlanpools = vlan_get_all_pools();
    if (!empty($allvlanpools)) {
        $form = '
		<form method="POST" action="" class="row3">
		<input type="hidden" name="deletevlanpool" value="true">
		' . vlan_pool_selector() . '
		<input type="submit" value="' . __('Delete') . '">
		</form>
		';
        show_window(__('Delete vlan pool'), $form);
    }
}

/**
 * 
 * @param type $vlanpool_id
 * @return type
 */
function vlan_get_pool_params($vlanpool_id) {
    $query = 'SELECT * from `vlan_pools` WHERE `id`="' . $vlanpool_id . '"';
    $result = simple_query($query);
    return($result);
}

/**
 * 
 * @param type $vlanpoolid
 */
function vlan_show_pooleditform($vlanpoolid) {
    $vlanpoolid = vf($vlanpoolid);
    $vlanpooldata = vlan_get_pool_params($vlanpoolid);
    $useQinQArr = array('0' => __('No'), '1' => __('Yes'))

    ;
    $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
    $inputs = wf_HiddenInput('vlanpooledit', 'true');
    $inputs.= wf_TextInput('editfirstvlan', __('First Vlan') . $sup, $vlanpooldata['firstvlan'], true, '20');
    $inputs.= wf_TextInput('editendvlan', __('Last Vlan') . $sup, $vlanpooldata ['endvlan'], true, '20');
    $inputs .= wf_TextInput('editdesc', __('Desc') . $sup, $vlanpooldata['desc'], true, '20');
    $inputs.= wf_Selector('edituse_qinq', $useQinQArr, __('Use qinq'), $vlanpooldata['qinq'], true);
    $inputs.= wf_TextInput('editsvlan', __('Svlan') . $sup, $vlanpooldata ['svlan'], true, '20');
    $inputs.= wf_Tag('br');
    $inputs .= wf_Submit(__('Save'));
    $form = wf_Form('', "POST", $inputs, 'glamour');

    $form .= wf_Link('?module=addvlan', 'Back', true, 'ubButton'
    );
    show_window(__('Edit'), $form);
}

/**
 * 
 */
function vlan_show_pools_form() {
    $useQinQArr = array('0' => __('No'), '1' => __('Yes'));
    $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
    $inputs = wf_HiddenInput('addvlan', 'true');
    $inputs.= wf_TextInput('firstvlan', __('First Vlan') . $sup, '', true, '20');
    $inputs.= wf_TextInput('lastvlan', __('Last Vlan') . $sup, '', true, '20');
    $inputs.= wf_TextInput('desc', __('Desc') . $sup, '', true, '20');
    $inputs.= wf_Selector('use_qinq', $useQinQArr, __('Use qinq'), '', true);
    $inputs.= wf_TextInput('svlan', __('Svlan') . $sup, '', true, '20');
    $inputs.= wf_Tag('br');
    $inputs.= wf_Submit(__('Add'));
    $form = wf_Form("", 'POST', $inputs, 'glamour');
    show_window(__('Add Vlan'), $form);
}

/**
 * 
 * @param type $desc
 * @param type $firstvlan
 * @param type $lastvlan
 * @param type $qinq
 * @param type $svlan
 */
function vlan_add_pool($desc, $firstvlan, $lastvlan, $qinq, $svlan) {
    $desc = mysql_real_escape_string($desc);
    $firstvlan = vf($firstvlan);
    $lastvlan = vf($lastvlan);
    $qinq = vf($qinq);
    if ($qinq == 0) {
        $query = " INSERT INTO `vlan_pools` (
				`id`,
				`desc`,
				`firstvlan`,
				`endvlan`,
				`qinq`
				)
		VALUES (
			NULL, 
			'" . $desc . "', 
			'" . $firstvlan . "', 
			'" . $lastvlan . "',
			'" . $qinq . "'
		)
	";
    } else {
        $query = " INSERT INTO `vlan_pools` (
				`id`,
				`desc`,
				`firstvlan`,
				`endvlan`,
				`qinq`,
				`svlan`
		)
		VALUES (
				NULL, 
				'" . $desc . "', 
				'" . $firstvlan . "', 
				'" . $lastvlan . "', 
				'" . $qinq . "', 
				'" . $svlan . "'
		)
		";
    }
    nr_query($query);
    log_register('ADD VlanPool `' . $desc . '`');
}

/**
 * 
 * @param type $vlanpool_id
 */
function vlan_delete_pool($vlanpool_id) {
    $vlanpool_id = vf($vlanpool_id, 3);
    $query = "DELETE FROM `vlan_pools` WHERE `id`='" . $vlanpool_id . "'";
    nr_query($query);

    log_register('DELETE VlanPool [' . $vlanpool_id . ']');
}

function vlan_show_available_pools() {
    $query = "SELECT * from `vlan_pools`";
    $vlans = simple_queryall($query);
    $tablecells = wf_TableCell(__('ID'));
    $tablecells .= wf_TableCell(__('First Vlan'
    ));
    $tablecells .= wf_TableCell(__('Last Vlan'));
    $tablecells .= wf_TableCell(__('Desc'));
    $tablecells .= wf_TableCell(__('qinq'));
    $tablecells .= wf_TableCell(__('svlan'));
    $tablecells .= wf_TableCell(__('Actions'));
    $tablerows = wf_TableRow($tablecells, 'row1');
    if (!empty($vlans)) {
        foreach ($vlans as $vlan) {
            $tablecells = wf_TableCell($vlan['id']);
            $tablecells .= wf_TableCell($vlan['firstvlan']);
            $tablecells .= wf_TableCell($vlan['endvlan']);
            $tablecells .= wf_TableCell($vlan['desc']);
            $tablecells .= wf_TableCell($vlan ['qinq']);
            if (isset($vlan['qinq'])) {
                $tablecells .= wf_TableCell($vlan['svlan']);
            }
            $actionlinks = wf_JSAlert(
                    '?module=addvlan&deletevlanpool=' . $vlan['id'], web_delete_icon(), 'Removing this may lead to irreparable results');
            $actionlinks .= wf_JSAlert('?module=addvlan&editvlanpool=' . $vlan['id'], web_edit_icon(), 'Are you serious');
            $tablecells .= wf_TableCell($actionlinks);
            $tablerows .= wf_TableRow($tablecells, 'row3');
        }
    }
    $result = wf_TableBody($tablerows, '100%', '0', 'sortable');
    show_window(__('Vlans'), $result);
}

/**
 * 
 * @param type $login
 */
function vlan_delete_host($login) {
    $query = "DELETE from `vlanhosts` WHERE `login`='" . $login . "'";
    nr_query($query);
    log_register("DELETE VLanHost (" . $login . ")");
}

/**
 * 
 * @param type $login
 */
function vlan_qinq_delete_host($login) {
    $query = "DELETE FROM `vlanhosts_qinq` WHERE `login`='" . $login . "'";
    nr_query($query);
    log_register("DELETE VlanHost " . $login);
}

/**
 * 
 * @param type $vlanpoolid
 * @param type $vlan
 * @param type $login
 */
function vlan_add_host($vlanpoolid, $vlan, $login) {
    $query = "
		INSERT INTO `vlanhosts` (
			`id` ,
			`vlanpoolid` ,
			`vlan` ,
			`login`
		)
		VALUES (
			NULL , 
			'" . $vlanpoolid . "', 
			'" . $vlan . "', 
			'" . $login . "'
		);
	";

    nr_query($query);
}

/**
 * 
 * @param type $vlanpoolid
 * @param type $svlan
 * @param type $cvlan
 * @param type $login
 */
function vlan_pool_add_qinq_host($vlanpoolid, $svlan, $cvlan, $login) {
    $query = "
		INSERT INTO `vlanhosts_qinq` (
			`id` ,
			`vlanpoolid` ,
			`svlan` ,
			`cvlan` ,
			`login`
		)
		VALUES (
			NULL , 
			'" . $vlanpoolid . "', 
			'" . $svlan . "', 
			'" . $cvlan . "', 
			'" . $login . "'
		);
	";

    nr_query($query);
}

/**
 * 
 * @param type $first_vlan
 * @param type $end_vlan
 * @return type
 */
function vlan_pool_expand($first_vlan, $end_vlan) {
    $first = $first_vlan;
    $last = $end_vlan;
    for ($i = $first; $i <= $last; $i++) {
        $totalpool[] = $i;
    }


    if (!empty($totalpool)) {
        foreach ($totalpool as $eachvlan) {
            if (isset($eachvlan)) {
                $filteredpool[] = $eachvlan;
            }
        }
    }
    return($filteredpool);
}

/**
 * 
 * @param type $table
 * @param type $field
 * @param type $vlanpoolid
 * @return type
 */
function vlan_pool_get_all_free_vlan($table, $field, $vlanpoolid) {
    $vlan_spec = vlan_get_pool_params($vlanpoolid);
    $first_vlan = $vlan_spec['firstvlan'];
    $last_vlan = $vlan_spec['endvlan'];

    $clear_vlans = array();
    $full_vlan_pool = vlan_pool_expand($first_vlan, $last_vlan);
    $current_state_q = 'SELECT `' . $field . '` from `' . $table . '`';
    $all_current_used_vlan = simple_queryall($current_state_q);
    if (!empty($all_current_used_vlan)) {
        foreach ($all_current_used_vlan as $io => $usedvlan) {
            $clear_vlans[] = $usedvlan[$field];
        }
        $free_vlan_pool = array_diff($full_vlan_pool, $clear_vlans);
    } else {
        $free_vlan_pool = $full_vlan_pool;
    }
    return($free_vlan_pool);
}

/**
 * 
 * @param type $table
 * @param type $svlan
 * @param type $field
 * @param type $vlanpoolid
 * @return type
 */
function vlan_pool_get_all_free_qinq_vlan($table, $svlan, $field, $vlanpoolid) {
    $vlan_spec = vlan_get_pool_params($vlanpoolid);
    $first_vlan = $vlan_spec['firstvlan'];
    $last_vlan = $vlan_spec['endvlan'];
    $clear_vlans = array();
    $full_vlan_pool = vlan_pool_expand($first_vlan, $last_vlan);
    $current_state_q = 'SELECT `' . $svlan . '`,`' . $field . '` from `' . $table . '`';
    $all_current_used_vlan = simple_queryall($current_state_q);
    if (!empty($all_current_used_vlan)) {
        foreach ($all_current_used_vlan as $io => $usedvlan) {
            $clear_vlans[] = $usedvlan[$field];
        }
        $free_vlan_pool = array_diff($full_vlan_pool, $clear_vlans);
    } else {
        $free_vlan_pool = $full_vlan_pool;
    }
    return($free_vlan_pool);
}

/**
 * 
 * @param type $table
 * @param type $field
 * @param type $vlanpoolid
 * @return type
 */
function vlan_pool_get_next_free_vlan($table, $field, $vlanpoolid) {
    $all_free_vlans = vlan_pool_get_all_free_vlan($table, $field, $vlanpoolid);
    $temp = array_keys($all_free_vlans);
    return(@$all_free_vlans[$temp[0]]);
}

/**
 * 
 * @param type $table
 * @param type $svlan
 * @param type $field
 * @param type $vlanpoolid
 * @return type
 */
function vlan_pool_get_next_free_qinq_vlan($table, $svlan, $field, $vlanpoolid) {
    $all_free_vlans = vlan_pool_get_all_free_qinq_vlan($table, $svlan, $field, $vlanpoolid);
    $temp = array_keys($all_free_vlans);
    return(@$all_free_vlans[$temp[0]]);
}

?>
