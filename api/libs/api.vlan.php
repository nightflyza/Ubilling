<?php

class VlanMacHistory {

    const MODULE		 = "vlan_mac_history";
    const MODULE_URL	 = "?module=vlan_mac_history";
    const DB_NAME		 = "vlan_mac_history";
    const FLAGPREFIX	 = 'exports/ONLINEVLANS';

    /**
     * Contains all available switches data
     * 
     * @var array
     */
    protected $AllSwitches = array();

    /**
     * Contains all available switch models data
     * 
     * @var array
     */
    protected $AllSwitchModels = array();

    /**
     * Contains all vlan terminators data
     * 
     * @var array
     */
    protected $allTerminators = array();

    /**
     * Contains all vlan hosts data
     * 
     * @var array
     */
    protected $allVlanHosts = array();

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains all vlan and mac history
     * 
     * @var array
     */
    public $allHistory = array();

    public function __construct() {
	$this->LoadTerminators();
	$this->LoadAlter();
	$this->LoadVlanHosts();
	$this->LoadAllSwitches();
	$this->LoadAllSwitchModels();
	$this->LoadVlanMacHistory();
    }

    protected function LoadTerminators() {
	$query	 = "SELECT * FROM " . VlanTerminator::DB_TABLE;
	$data	 = simple_queryall($query);
	if (!empty($data)) {
	    foreach ($data as $each) {
		$this->allTerminators[$each['vlanpoolid']] = $each;
	    }
	}
    }

    /**
     * load alter.ini config     
     * 
     * @return void
     */
    protected function LoadAlter() {
	global $ubillingConfig;
	$this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * load all data from vlanhosts to $allVlanHosts
     * 
     * @return void
     */
    protected function LoadVlanHosts() {
	$query	 = "SELECT * FROM " . VlanGen::DB_TABLE;
	$data	 = simple_queryall($query);
	if (!empty($data)) {
	    foreach ($data as $each) {
		$this->allVlanHosts[$each['login']] = $each;
	    }
	}
    }

    /**
     * Function for getting all switches and place them to $AllSwitches
     * 
     * @return void
     */
    protected function LoadAllSwitches() {
	$data = zb_SwitchesGetAll();
	if (!empty($data)) {
	    foreach ($data as $each) {
		$this->AllSwitches[$each['ip']] = $each;
	    }
	}
    }

    /**
     * Function for getting all switch models and place them to $AllSwitchModels
     * 
     * @return void
     */
    protected function LoadAllSwitchModels() {
	$query	 = "SELECT * FROM `switchmodels`";
	$data	 = simple_queryall($query);
	if (!empty($data)) {
	    foreach ($data as $each) {
		$this->AllSwitchModels[$each['id']] = $each['snmptemplate'];
	    }
	}
    }

    protected function LoadVlanMacHistory() {
	$query	 = "SELECT * FROM " . self::DB_NAME;
	$data	 = simple_queryall($query);
	if (!empty($data)) {
	    foreach ($data as $each) {
		$this->allHistory[$each['login']] = $each;
	    }
	}
    }

    /**
     * Find vlan terminators snmp template
     * 
     * @param string $login
     * @return string
     */
    protected function GetTerminatorSnmpTemplate($login) {
	$data = $this->AllSwitchModels[$this->AllSwitches[$this->allTerminators[$this->allVlanHosts[$login]['vlanpoolid']]['ip']]['modelid']];
	return $data;
    }

    /**
     * Read online detect oid from snmp template 
     * 
     * @param string $login
     * @param int $vlan
     * @return string
     */
    protected function GetOnlineDetectOid($login, $vlan = false) {
	$oid		 = false;
	$template	 = $this->GetTerminatorSnmpTemplate($login);
	$snmpData	 = rcms_parse_ini_file(CONFIG_PATH . "/snmptemplates/" . $template, true);
	if (isset($snmpData['define']['ONLINEVLAN'])) {
	    if ($vlan) {
		$oid = $snmpData['define']['ONLINEVLAN'] . "." . $vlan;
	    } else {
		$oid = $snmpData['define']['ONLINEVLAN'];
	    }
	}
	return ($oid);
    }

    /**
     * Check weather user online
     * 
     * @param string $login
     * @param int $vlan
     * @return string
     */
    public function GetUserVlanOnline($login, $vlan) {
	snmp_set_oid_output_format(SNMP_OID_OUTPUT_NUMERIC);
	if ($this->GetOnlineDetectOid($login, $vlan)) {
	    @$data = snmp2_real_walk($this->allTerminators[$this->allVlanHosts[$login]['vlanpoolid']]['ip'], $this->AllSwitches[$this->allTerminators[$this->allVlanHosts[$login]['vlanpoolid']]['ip']]['snmp'], $this->GetOnlineDetectOid($login, $vlan));
	    if (empty($data)) {
		return "Offline" . " " . wf_img_sized('skins/icon_inactive.gif', '', '', '12');
	    } else {
		return "Online" . " " . wf_img_sized('skins/icon_active.gif', '', '', '12');
	    }
	} else {
	    return 'empty';
	}
    }

    /**
     * 
     * @return type
     */
    public function WriteVlanMacData() {
	$count = 0;
	if (!empty($this->allTerminators) AND ! empty($this->allVlanHosts)) {
	    foreach ($this->allTerminators as $eachTerminator) {
		$ip		 = $eachTerminator["ip"];
		$vlanPoolId	 = $eachTerminator['vlanpoolid'];
		$data		 = snmp2_real_walk($ip, $this->AllSwitches[$ip]['snmp'], '.1.3.6.1.4.1.9.9.380.1.4.1.1.3');
		foreach ($data as $each => $value) {
		    $decmac		 = str_replace('.1.3.6.1.4.1.9.9.380.1.4.1.1.3.', '', $each);
		    $vlanPlusMac	 = explode(".", $decmac, 2);
		    $vlan		 = $vlanPlusMac[0];
		    $mac		 = $this->dec2mac($vlanPlusMac[1]);
		    foreach ($this->allVlanHosts as $eachHost) {
			if ($eachHost['vlanpoolid'] == $vlanPoolId AND $eachHost['vlan'] == $vlan) {
			    $login = $eachHost['login'];
			}
		    }
		    if (!empty($this->allHistory)) {
			if ($this->allHistory[$login]['mac'] != $mac) {
			    $this->WriteHistory($login, $vlan, $mac);
			}
		    } else {
			$this->WriteHistory($login, $vlan, $mac);
		    }
		    $count++;
		}
	    }
	}
	file_put_contents(self::FLAGPREFIX, $count);
    }

    protected function WriteHistory($login, $vlan, $mac) {
	$query = "INSERT INTO " . self::DB_NAME . " (`id`, `login`, `vlan`, `mac`, `date`) VALUES (NULL,'" . $login . "','" . $vlan . "','" . $mac . "', NULL);";
	nr_query($query);
    }

    /**
     * Converts decimal (delimiter is dot) MAC to heximal (delimiter is semicolon)
     * 
     * @param string $mac
     * @return string
     */
    protected function dec2mac($mac) {
	$res	 = array();
	$args	 = explode(".", $mac);
	foreach ($args as $each) {
	    $each	 = dechex($each);
	    strlen($each) < 2 ? $res[]	 = "0$each" : $res[]	 = $each;
	}
	$string = implode(":", $res);
	return ($string);
    }

    public function RenderHistory($login) {
	$history	 = $this->allHistory;
	$tablecells	 = wf_TableCell(__('ID'));
	$tablecells.= wf_TableCell(__('Login'));
	$tablecells.= wf_TableCell(__('VLAN'));
	$tablecells.= wf_TableCell(__('MAC'));
	$tablecells.= wf_TableCell(__('Date'));
	$tablerows	 = wf_TableRow($tablecells, 'row1');
	if (!empty($history)) {
	    $tablecells = wf_TableCell($history[$login]['id']);
	    $tablecells .= wf_TableCell($history[$login]['login']);
	    $tablecells .= wf_TableCell($history[$login]['vlan']);
	    $tablecells .= wf_TableCell($history[$login]['mac']);
	    $tablecells .= wf_TableCell($history[$login]['date']);
	    $tablerows .= wf_TableRow($tablecells, 'row3');
	}
	$result = wf_TableBody($tablerows, '100%', '0', 'sortable');
	show_window(__('History'), $result);
    }

}

class VlanGen {

    /**
     * Contains all vlan pools as vlanPool['id'] => vlanPool['desc'] for vlan pool selector
     * 
     * @var array
     */
    protected $VlanPoolsSelector = array();

    /**
     * Contains all vlan hosts data
     * 
     * @var array
     */
    protected $AllVlanHosts = array();

    /**
     * Contains all vlan pools data
     * 
     * @var array
     */
    protected $AllVlanPools = array();

    /**
     * Contains all vlan terminators data
     * 
     * @var array
     */
    protected $AllTerminators = array();

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $AltCfg = array();

    const MODULE			 = 'VlanGen';
    const MODULE_URL		 = '?module=pl_vlangen';
    const DB_TABLE		 = 'vlanhosts';
    const QINQ_DB_TABLE		 = 'vlanhosts_qinq';
    const SCRIPT_PATH		 = './config/scripts/';
    const MODULE_ADDVLAN		 = 'addvlan';
    const MODULE_URL_ADDVLAN	 = '?module=addvlan';
    const POOL_DB_TABLE		 = 'vlan_pools';

    public function __construct() {
	$this->LoadVlanHosts();
	$this->LoadVlanPoolsSelector();
	$this->LoadTerminators();
	$this->loadAlter();
    }

    /**
     * select all data from vlan_terminators and load to $AllTerminators
     * 
     * @return void
     */
    protected function LoadTerminators() {
	$query	 = "SELECT * FROM " . VlanTerminator::DB_TABLE;
	$data	 = simple_queryall($query);
	if (!empty($data)) {
	    foreach ($data as $each) {
		$this->AllTerminators[$each['id']] = $each;
	    }
	}
    }

    /**
     * select all data from vlanhosts and load it to $allVlanHosts
     * 
     * @return void
     */
    protected function LoadVlanHosts() {
	$query	 = "SELECT * FROM " . self::DB_TABLE;
	$data	 = simple_queryall($query);
	if (!empty($data)) {
	    foreach ($data as $each) {
		$this->AllVlanHosts[$each['id']] = $each;
	    }
	}
    }

    /**
     * select data from vlan_pools and load data to $AllVlanPools and loading data for vlan pool selector
     * 
     * @return void
     */
    protected function LoadVlanPoolsSelector() {
	$query	 = "SELECT * FROM " . self::POOL_DB_TABLE;
	$data	 = simple_queryall($query);
	if (!empty($data)) {
	    foreach ($data as $each) {
		$this->AllVlanPools[$each['id']]	 = $each;
		$this->VlanPoolsSelector[$each['id']]	 = $each['desc'];
	    }
	}
    }

    /**
     * load alter.ini config     
     * 
     * @return void
     */
    protected function loadAlter() {
	global $ubillingConfig;
	$this->AltCfg = $ubillingConfig->getAlter();
    }

    /**
     * Searching vlan by login in AllVlanHosts
     * 
     * @param string $login
     * @return int $vlan
     */
    public function GetVlan($login) {
	foreach ($this->AllVlanHosts as $each => $io) {
	    if ($io['login'] == $login) {
		return($io['vlan']);
	    }
	}
    }

    /**
     * Searching QinQ value for vlan pool in AllVlanPools
     * 
     * @param type $VlanPoolID
     * @return int $QinQ
     */
    protected function GetVlanPoolQinQ($VlanPoolID) {
	foreach ($this->AllVlanPools as $Pool => $each) {
	    if ($each['id'] == $VlanPoolID) {
		return ($each['qinq']);
	    }
	}
    }

    /**
     * Fills array by every possible int value between $first and $end
     * 
     * @param int $first
     * @param int $end
     * @return array $pool
     */
    protected function VlanPoolExpand($first, $end) {
	for ($i = $first; $i <= $end; $i++) {
	    $total[] = $i;
	}
	if (!empty($total)) {
	    foreach ($total as $EachVlan) {
		$pool[] = $EachVlan;
	    }
	}
	return($pool);
    }

    /**
     * Find all unused values from all possible values from VlanPoolExpand
     * 
     * @param int $VlanPoolID
     * @return array $freePool
     */
    protected function GetAllFreeVlan($VlanPoolID) {
	$poolData	 = $this->AllVlanPools[$VlanPoolID];
	$clearVlans	 = array();
	$fullPool	 = $this->VlanPoolExpand($poolData['firstvlan'], $poolData['endvlan']);
	$queryUsed	 = "SELECT `vlan` from " . self::DB_TABLE;
	$allUsedVlan	 = simple_queryall($queryUsed);
	if (!empty($allUsedVlan)) {
	    foreach ($allUsedVlan as $io => $usedVlan) {
		$clearVlans[] = $usedVlan['vlan'];
	    }
	    $freePool = array_diff($fullPool, $clearVlans);
	} else {
	    $freePool = $fullPool;
	}
	return($freePool);
    }

    /**
     * Find all unused values from all possible values from VlanPoolExpand
     * 
     * @param int $vlanPoolID
     * @return array $freePool
     */
    protected function GetAllFreeVlanQinQ($vlanPoolID) {
	$poolData	 = $this->AllVlanPools[$vlanPoolID];
	$first		 = $poolData['firstvlan'];
	$end		 = $poolData['endvlan'];
	$clearVlans	 = array();
	$fullPool	 = $this->VlanPoolExpand($first, $end);
	$usedQuery	 = "SELECT `svlan`,`cvlan`, FROM" . self::QINQ_DB_TABLE;
	$allUsedVlan	 = simple_queryall($usedQuery);
	if (!empty($allUsedVlan)) {
	    foreach ($allUsedVlan as $io => $usedVlan) {
		$clearVlans[] = $usedVlan['cvlan'];
	    }
	    $freePool = array_diff($fullPool, $clearVlans);
	} else {
	    $freePool = $fullPool;
	}
	return($freePool);
    }

    /**
     * Getting first unused value in all possible values of vlan pool
     * 
     * @param int $vlanPoolID
     * @return int $allFreeVlans[$tmp[0]
     */
    protected function GetNextFreeVlan($vlanPoolID) {
	$allFreeVlans	 = $this->GetAllFreeVlan($vlanPoolID);
	$tmp		 = array_keys($allFreeVlans);
	return($allFreeVlans[$tmp[0]]);
    }

    /**
     * Getting first unused value in all possible values of vlan pool
     * 
     * @param int $vlanPoolID
     * @return int $allFreeVlans[$tmp[0]
     */
    protected function GetNextFreeVlanQinQ($vlanPoolID) {
	$allFreeVlans	 = $this->GetAllFreeVlanQinQ($vlanPoolID);
	$tmp		 = array_keys($allFreeVlans);
	return($allFreeVlans[$tmp[0]]);
    }

    /**
     * Find netid by user's IP
     * 
     * @param string $ip
     * @return int $data['netid']
     */
    protected function GetNetidByIP($ip) {
	$query	 = "SELECT `netid` FROM `nethosts` WHERE `ip`='" . $ip . "'";
	$data	 = simple_query($query);
	return($data['netid']);
    }

    /**
     * Find vlan terminator id by netid
     * 
     * @param int $netid
     * @return int $data['id']
     */
    protected function GetTermIdByNetid($netid) {
	$query	 = "SELECT `id` FROM `vlan_terminators` WHERE `netid`='" . $netid . "'";
	$data	 = simple_query($query);
	return($data['id']);
    }

    /**
     * Apply vlan to user and write to DB
     * 
     * @param int $VlanPoolID
     * @param int $vlan
     * @param string $login
     * @return void
     */
    protected function AddVlanHost($VlanPoolID, $vlan, $login) {
	$query = "INSERT INTO `vlanhosts` (`id` , `vlanpoolid` , `vlan` , `login`)
		VALUES (NULL , '" . $VlanPoolID . "', '" . $vlan . "', '" . $login . "');";
	nr_query($query);
    }

    /**
     * Adding vlan pool data to DB
     * 
     * @param string $Desc
     * @param int $FirstVlan
     * @param int $LastVlan
     * @param int $QinQ
     * @param int $sVlan
     * @return void
     */
    public function AddVlanPool($Desc, $FirstVlan, $LastVlan, $QinQ, $sVlan) {
	$Desc		 = vf(mysql_real_escape_string($Desc));
	$FirstVlan	 = vf(trim($FirstVlan), 3);
	$LastVlan	 = vf(trim($LastVlan), 3);
	$QinQ		 = vf(trim($QinQ), 3);
	$sVlan		 = vf(trim($sVlan), 3);
	if (empty($sVlan)) {
	    $sVlan = 'NULL';
	}
	$query = " INSERT INTO " . self::POOL_DB_TABLE . " (`id`, `desc`, `firstvlan`, `endvlan`, `qinq`, `svlan`)
		VALUES (NULL, '" . $Desc . "', '" . $FirstVlan . "', '" . $LastVlan . "', '" . $QinQ . "', '" . $sVlan . "')";
	nr_query($query);
	log_register('ADD VlanPool `' . $Desc . '`');
    }

    /**
     * Apply vlan to user and write to DB
     * 
     * @param int $vlanpoolid
     * @param int $svlan
     * @param int $cvlan
     * @param string $login
     * @return void
     */
    protected function AddVlanHostQinQ($vlanpoolid, $svlan, $cvlan, $login) {
	$query = "INSERT INTO `vlanhosts_qinq` (`id` , `vlanpoolid` , `svlan` , `cvlan` , `login`)
		VALUES (NULL , '" . $vlanpoolid . "', '" . $svlan . "', '" . $cvlan . "', '" . $login . "');";
	nr_query($query);
    }

    /**
     * Show all available data in vlan pools
     * 
     * @return void
     */
    public function ShowVlanPools() {
	$allVlanPool	 = $this->AllVlanPools;
	$tablecells	 = wf_TableCell(__('ID'));
	$tablecells .= wf_TableCell(__('First Vlan'));
	$tablecells .= wf_TableCell(__('Last Vlan'));
	$tablecells .= wf_TableCell(__('Desc'));
	$tablecells .= wf_TableCell(__('qinq'));
	$tablecells .= wf_TableCell(__('svlan'));
	$tablecells .= wf_TableCell(__('Actions'));
	$tablerows	 = wf_TableRow($tablecells, 'row1');
	if (!empty($allVlanPool)) {
	    foreach ($allVlanPool as $eachVlan => $io) {
		$tablecells = wf_TableCell($io['id']);
		$tablecells .= wf_TableCell($io['firstvlan']);
		$tablecells .= wf_TableCell($io['endvlan']);
		$tablecells .= wf_TableCell($io['desc']);
		$tablecells .= wf_TableCell($io ['qinq']);
		if (isset($io['qinq'])) {
		    $tablecells .= wf_TableCell($io['svlan']);
		}
		$actionlinks = wf_JSAlert(self::MODULE_URL_ADDVLAN . '&DeleteVlanPool=' . $io['id'], web_delete_icon(), 'Removing this may lead to irreparable results');
		$actionlinks .= wf_Link(self::MODULE_URL_ADDVLAN . '&EditVlanPool=' . $io['id'], web_edit_icon(), false);
		$tablecells .= wf_TableCell($actionlinks);
		$tablerows .= wf_TableRow($tablecells, 'row3');
	    }
	}
	$result = wf_TableBody($tablerows, '100%', '0', 'sortable');
	show_window(__('Vlans'), $result);
    }

    /**
     * Form for adding vlan pool data
     * 
     * @return void
     */
    public function AddVlanPoolForm() {
	$useQinQArr	 = array('0' => __('No'), '1' => __('Yes'));
	$sup		 = wf_tag('sup') . '*' . wf_tag('sup', true);
	$inputs		 = wf_HiddenInput('AddVlan', 'true');
	$inputs.= wf_TextInput('FirstVlan', __('First Vlan') . $sup, '', true, '20');
	$inputs.= wf_TextInput('LastVlan', __('Last Vlan') . $sup, '', true, '20');
	$inputs.= wf_TextInput('Desc', __('Desc') . $sup, '', true, '20');
	$inputs.= wf_Selector('UseQinQ', $useQinQArr, __('Use qinq'), '', true);
	$inputs.= wf_TextInput('sVlan', __('Svlan') . $sup, '', true, '20');
	$inputs.= wf_Tag('br');
	$inputs.= wf_Submit(__('Add'));
	$form		 = wf_Form("", 'POST', $inputs, 'glamour');
	show_window(__('Add Vlan'), $form);
    }

    /**
     * Delete users vlan data from DB
     * 
     * @param string $login
     * @return void
     */
    public function DeleteVlanHost($login) {
	$query = "DELETE from " . self::DB_TABLE . " WHERE `login`='" . $login . "'";
	nr_query($query);
	log_register("DELETE VLanHost (" . $login . ")");
    }

    /**
     * Delete users vlan data from DB
     * 
     * @param type $login
     * @return void
     */
    public function DeleteVlanHostQinQ($login) {
	$query = "DELETE from " . self::QINQ_DB_TABLE . " WHERE `login`='" . $login . "'";
	nr_query($query);
	log_register("DELETE VLanHost (" . $login . ")");
    }

    /**
     * Delete vlan pool data from DB
     * 
     * @param int $VlanPoolID
     * @return void
     */
    public function DeleteVlanPool($VlanPoolID) {
	$query = "DELETE FROM " . self::POOL_DB_TABLE . " WHERE `id`='" . $VlanPoolID . "'";
	nr_query($query);
	log_register('DELETE VlanPool [' . $VlanPoolID . ']');
    }

    public function ChangeOnOnuForm() {
	$Inputs	 = wf_SubmitClassed('true', 'vlanButton', 'ChangeOnuPvid', __('Change pvid on onu port'));
	$Form	 = wf_Form("", 'POST', $Inputs);
	return($Form);
    }

    /**
     * For for changing pvid on switch port
     * 
     * @return form
     */
    public function ChangeOnPortForm() {
	$inputs	 = wf_SubmitClassed('true', 'vlanButton', 'ChangeVlanOnPort', __('Change vlan on switch port'));
	$form	 = wf_Form("", 'POST', $inputs);
	return($form);
    }

    /**
     * Returns form for change\apply vlan on user
     * 
     * @return object
     */
    public function ChangeForm() {
	$inputs	 = wf_tag('label', false, 'vlanLabel');
	$inputs.= wf_SelectorClassed('VlanPoolSelected', $this->VlanPoolsSelector, '', '', false, 'vlanSelector');
	$inputs.= wf_tag('label', true);
	$inputs.= wf_delimiter();
	$inputs.= wf_SubmitClassed('true', 'vlanButton', 'AddVlanHost', __('Change user Vlan'));
	$inputs.= wf_delimiter(2);
	$result	 = wf_Form("", 'POST', $inputs);
	return($result);
    }

    /**
     * Returns form for delete users vlan
     * 
     * @return object
     */
    public function DeleteForm() {
	$inputs	 = wf_SubmitClassed('true', 'vlanButton', 'DeleteVlanHost', __('Delete user Vlan'));
	$inputs.= wf_delimiter(2);
	$result	 = wf_form("", 'POST', $inputs);
	return($result);
    }

    /**
     * Edit vlan pool data in DB
     * 
     * @param int $first
     * @param int $last
     * @param string $desc
     * @param int $qinq
     * @param int $svlan
     * @param int $id
     * @return void
     */
    public function EditVlanPool($first, $last, $desc, $qinq, $svlan, $id) {
	$first	 = vf(trim($first), 3);
	$last	 = vf(trim($last), 3);
	$desc	 = vf($desc);
	$qinq	 = vf(trim($qinq), 3);
	$svlan	 = vf(trim($svlan), 3);
	if (empty($sVlan)) {
	    $svlan = 'NULL';
	}
	simple_update_field(self::POOL_DB_TABLE, 'firstvlan', $first, "WHERE `id`='" . $id . "'");
	simple_update_field(self::POOL_DB_TABLE, 'endvlan', $last, "WHERE `id`='" . $id . "'");
	simple_update_field(self::POOL_DB_TABLE, 'desc', $desc, "WHERE `id`='" . $id . "'");
	simple_update_field(self::POOL_DB_TABLE, 'qinq', $qinq, "WHERE `id`='" . $id . "'");
	simple_update_field(self::POOL_DB_TABLE, 'svlan', $svlan, "WHERE `id`='" . $id . "'");
	log_register('MODIFY VlanPool [' . $id . ']');
    }

    /**
     * Form for editing vlan pool data
     * 
     * @param int $PoolID
     * @return object
     */
    public function VlanPoolEditForm($PoolID) {
	$PoolData	 = $this->AllVlanPools[$PoolID];
	$useQinQArr	 = array('0' => __('No'), '1' => __('Yes'));
	$sup		 = wf_tag('sup') . '*' . wf_tag('sup', true);
	$inputs		 = wf_HiddenInput('EditVlanPool', 'true');
	$inputs.= wf_TextInput('FirstVlan', __('First Vlan') . $sup, $PoolData['firstvlan'], true, '20');
	$inputs.= wf_TextInput('LastVlan', __('Last Vlan') . $sup, $PoolData['endvlan'], true, '20');
	$inputs .= wf_TextInput('Desc', __('Desc') . $sup, $PoolData['desc'], true, '20');
	$inputs.= wf_Selector('UseQinQ', $useQinQArr, __('Use qinq'), $PoolData['qinq'], true);
	$inputs.= wf_TextInput('sVlan', __('sVlan') . $sup, $PoolData['svlan'], true, '20');
	$inputs.= wf_Tag('br');
	$inputs .= wf_Submit(__('Save'));
	$form		 = wf_Form('', "POST", $inputs, 'glamour');
	$form .= wf_Link(self::MODULE_URL_ADDVLAN, 'Back', true, 'ubButton');
	show_window(__('Edit'), $form);
    }

    /**
     * Apply vlan on vlan terminator
     * 
     * @param string $ip
     * @param int $vlan
     * @return void
     */
    protected function OnVlanConnect($ip, $vlan) {
	multinet_rebuild_all_handlers();
	$networkID	 = $this->GetNetidByIP($ip);
	$terminatorID	 = $this->GetTermIdByNetid($networkID);
	$termData	 = $this->AllTerminators[$terminatorID];
	$term_ip	 = $termData ['ip'];
	$term_type	 = $termData['type'];
	$term_user	 = $termData['username'];
	$term_pass	 = $termData['password'];
	$term_int	 = $termData['interface'];
	$relay		 = $termData['relay'];
	if ($term_ip == '127.0.0.1') {
	    if ($term_type == 'FreeBSD') {
		$res = shell_exec(self::SCRIPT_PATH . "bsd.local.sh $term_int $ip $vlan");
	    }
	    if ($term_type == 'Linux') {
		$res = shell_exec(self::SCRIPT_PATH . "linux.local.sh");
	    }
	} else {

	    if ($term_type == 'FreeBSD') {
		$res = shell_exec(self::SCRIPT_PATH . "bsd.remote.sh $term_user $term_pass $term_int $ip $vlan");
	    }
	    if ($term_type == 'Linux') {
		$res = shell_exec(self::SCRIPT_PATH . "linux.remote.sh $term_user $term_pass $term_int $ip $vlan");
	    }
	    if ($term_type == 'Cisco') {
		$res = shell_exec(self::SCRIPT_PATH . "cisco.sh $term_user $term_pass $vlan $term_int $relay $term_ip");
	    }
	    if ($term_type == 'Cisco_static') {
		$res = shell_exec(self::SCRIPT_PATH . "cisco_static.sh $term_user $term_pass $vlan $term_int $relay $term_ip");
	    }
	}
    }

    /**
     * Changes\applies users vlan
     * 
     * @param int $newVlanPoolID
     * @param string $login
     * @return void
     */
    public function VlanChange($newVlanPoolID, $login) {
	$QinQ	 = $this->GetVlanPoolQinQ($newVlanPoolID);
	$ip	 = zb_UserGetIP($login);
	$this->DeleteVlanHost($login);
	$this->DeleteVlanHostQinQ($login);
	if ($QinQ == 0) {
	    $newVlan = $this->GetNextFreeVlan($newVlanPoolID);
	    if (empty($newVlan)) {
		$alert = wf_JSAlert(self::MODULE_URL_ADDVLAN, __("Error"), __("No free Vlan available in selected pool"));
		print($alert);
		rcms_redirect(self::MODULE_URL_ADDVLAN);
	    }
	    $this->AddVlanHost($newVlanPoolID, $newVlan, $login);
	} else {
	    $poolData	 = $this->AllVlanPools[$newVlanPoolID];
	    $svlan		 = $poolData['svlan'];
	    $this->AddVlanHostQinQ($newVlanPoolID, $svlan, $newVlan, $login);
	}
	$this->OnVlanConnect($ip, $newVlan);
	log_register(__("Change vlan") . " " . "(" . $login . ")" . " " . __("ON") . " " . $newVlan);
    }

}

class VlanTerminator {

    /**
     * Contains all vlan terminators data
     * 
     * @var array
     */
    protected $AllTerminators = array();

    /**
     * Contains all vlan pools as vlanPool['id'] => vlanPool['desc'] for vlan pool selector
     * 
     * @var array
     */
    protected $VlanPoolsSelector = array();

    /**
     * Contains all networks pool as network['id'] => network['desc'] for networks selector
     * 
     * @var type 
     */
    protected $NetworkSelector = array();

    const MODULE		 = 'Vlan Terminator';
    const MODULE_URL	 = '?module=nas';
    const DB_TABLE	 = 'vlan_terminators';

    public function __construct() {
	$this->LoadTerminators();
	$this->LoadVlanPoolsSelector();
	$this->LoadNetworkSelecor();
    }

    protected function LoadTerminators() {
	$query	 = "SELECT * FROM " . self::DB_TABLE;
	$data	 = simple_queryall($query);
	if (!empty($data)) {
	    foreach ($data as $each) {
		$this->AllTerminators[$each['id']] = $each;
	    }
	}
    }

    protected function LoadVlanPoolsSelector() {
	$query	 = "SELECT * FROM " . VlanGen::POOL_DB_TABLE;
	$data	 = simple_queryall($query);
	if (!empty($data)) {
	    foreach ($data as $each) {
		$this->VlanPoolsSelector[$each['id']] = $each['desc'];
	    }
	}
    }

    protected function LoadNetworkSelecor() {
	$query	 = "SELECT * FROM `networks`";
	$data	 = simple_queryall($query);
	if (!empty($data)) {
	    foreach ($data as $each) {
		$this->NetworkSelector[$each['id']] = $each['desc'];
	    }
	}
    }

    /**
     * Delete vlan terminator data from DB
     * 
     * @param int $id
     * @return void
     */
    public function delete($id) {
	$query = "DELETE FROM " . self::DB_TABLE . " WHERE `id`='" . $id . "'";
	nr_query($query);
	log_register('DELETE Terminator [' . $id . ']');
    }

    /**
     * Add vlan terminator data to DB
     * 
     * @param int $netid
     * @param int $vlanpoolid
     * @param string $ip
     * @param string $type
     * @param string $username
     * @param string $password
     * @param string $remote
     * @param string $interface
     * @param string $relay
     * @return void
     */
    public function add($netid, $vlanpoolid, $ip, $type, $username, $password, $remote, $interface, $relay) {
	$query = "INSERT INTO " . self::DB_TABLE . " (`id`, `netid`, `vlanpoolid`, `ip`, `type`, `username`, `password`, `remote-id`, `interface`, `relay`)
                VALUES (NULL, '" . $netid . "', '" . $vlanpoolid . "', '" . $ip . "', '" . $type . "', '" . $username . "', '" . $password . "', '" . $remote . "', '" . $interface . "', '" . $relay . "' )";
	nr_query($query);
	log_register('ADD Terminator `' . $type . '`');
    }

    /**
     * Editing vlan terminator data in DB
     * 
     * @param int $NetID
     * @param int $VlanPool
     * @param string $TerminatorIP
     * @param string $TerminatorType
     * @param string $TerminatorLogin
     * @param string $TerminatorPass
     * @param string $RemoteID
     * @param string $Interface
     * @param string $Relay
     * @param int $id
     * @return void
     */
    public function edit($NetID, $VlanPool, $TerminatorIP, $TerminatorType, $TerminatorLogin, $TerminatorPass, $RemoteID, $Interface, $Relay, $id) {
	simple_update_field(self::DB_TABLE, 'netid', $NetID, "WHERE `id`='" . $id . "'");
	simple_update_field(self::DB_TABLE, 'vlanpoolid', $VlanPool, "WHERE `id`='" . $id . "'");
	simple_update_field(self::DB_TABLE, 'ip', $TerminatorIP, "WHERE `id`='" . $id . "'");
	simple_update_field(self::DB_TABLE, 'type', $TerminatorType, "WHERE `id`='" . $id . "'");
	simple_update_field(self::DB_TABLE, 'username', $TerminatorLogin, "WHERE `id`='" . $id . "'");
	simple_update_field(self::DB_TABLE, 'password', $TerminatorPass, "WHERE `id`='" . $id . "'");
	simple_update_field(self::DB_TABLE, 'remote-id', $RemoteID, "WHERE `id`='" . $id . "'");
	simple_update_field(self::DB_TABLE, 'interface', $Interface, "WHERE `id`='" . $id . "'");
	simple_update_field(self::DB_TABLE, 'relay', $Relay, "WHERE `id`='" . $id . "'");
	log_register('MODIFY Vlan Terminator [' . $id . ']');
    }

    /**
     * Show's all vlan terminators data
     * 
     * @return object
     */
    public function RenderTerminators() {
	$tablecells	 = wf_TableCell(__('ID'));
	$tablecells .= wf_TableCell(__('Network'));
	$tablecells .= wf_TableCell(__('Vlan pool'));
	$tablecells .= wf_TableCell(__('IP'));
	$tablecells .= wf_TableCell(__('Type'));
	$tablecells .= wf_TableCell(__('Username'));
	$tablecells .= wf_TableCell(__('Password'));
	$tablecells .= wf_TableCell(__('Remote-ID'));
	$tablecells .= wf_TableCell(__('Interface'));
	$tablecells .= wf_TableCell(__('Relay Address'));
	$tablecells .= wf_TableCell(__('Actions'));
	$tablerows	 = wf_TableRow($tablecells, 'row1');
	if (!empty($this->AllTerminators)) {
	    foreach ($this->AllTerminators as $each => $term) {
		$tablecells	 = wf_TableCell($term['id']);
		$tablecells .= wf_TableCell($this->NetworkSelector[$term ['netid']]);
		$tablecells .= wf_TableCell($this->VlanPoolsSelector[$term['vlanpoolid']]);
		$tablecells .= wf_TableCell($term['ip']);
		$tablecells .= wf_TableCell($term['type']);
		$tablecells .= wf_TableCell($term['username']);
		$tablecells .= wf_TableCell($term['password']);
		$tablecells .= wf_TableCell($term['remote-id']);
		$tablecells .= wf_TableCell($term['interface']);
		$tablecells .= wf_TableCell($term['relay']);
		$actionlinks	 = wf_JSAlert(self::MODULE_URL . '&DeleteTerminator=' . $term['id'], web_delete_icon(), 'Removing this may lead to irreparable results');
		$actionlinks .= wf_Link(self::MODULE_URL . '&EditTerminator=' . $term['id'], web_edit_icon(), false);
		$tablecells .= wf_TableCell($actionlinks);
		$tablerows .= wf_TableRow($tablecells, 'row3');
	    }
	}
	$result = wf_TableBody($tablerows, '100%', '0', 'sortable');
	show_window(__('Terminators'), $result);
    }

    /**
     * Returns form for adding vlan terminator to DB
     * 
     * @return object
     */
    public function AddForm() {
	$type	 = array('FreeBSD' => 'FreeBSD', 'Linux' => 'Linux', 'Cisco' => 'Cisco', 'Cisco_static' => 'Cisco_static');
	$sup	 = wf_tag('sup') . '*' . wf_tag('sup', true);
	$inputs	 = wf_HiddenInput('AddTerminator', 'true');
	$inputs.= wf_Selector('NetworkSelected', $this->NetworkSelector, __('Network'), '', true);
	$inputs.= wf_Selector('VlanPoolSelected', $this->VlanPoolsSelector, __('Vlan Pool ID'), '', true);
	$inputs.= wf_TextInput('IP', __('IP') . $sup, '', true, '20');
	$inputs.= wf_Selector('Type', $type, __('Type'), '', true);
	$inputs.= wf_TextInput('Username', __('Username') . $sup, '', true, '20');
	$inputs.= wf_TextInput('Password', __('Password') . $sup, '', true, '20');
	$inputs.= wf_TextInput('RemoteID', __('Remote-ID') . $sup, '', true, '20');
	$inputs.= wf_TextInput('Interface', __('Interface') . $sup, '', true, '20');
	$inputs.= wf_TextInput('Relay', __('Relay Address') . $sup, '', true, '20');
	$inputs.= wf_Tag('br');
	$inputs.= wf_Submit(__('Add'));
	$form	 = wf_Form("", 'POST', $inputs, 'glamour');
	show_window(__('ADD Terminator'), $form);
    }

    /**
     * Returns form for editing vlan terminators data in DB
     * 
     * @param int $id
     * @return object     
     */
    public function EditForm($id) {
	$TermData	 = $this->AllTerminators[$id];
	$type		 = array('FreeBSD' => 'FreeBSD', 'Linux' => 'Linux', 'Cisco' => 'Cisco', 'Cisco_static' => 'Cisco_static');
	$sup		 = wf_tag('sup') . '*' . wf_tag('sup', true);
	$inputs		 = wf_HiddenInput('TerminatorEdit', 'true');
	$inputs.= wf_Selector('NetworkSelected', $this->NetworkSelector, __('Network'), $TermData['netid'], true);
	$inputs.= wf_Selector('VlanPoolSelected', $this->VlanPoolsSelector, __('Vlan Pool ID'), $TermData['vlanpoolid'], true);
	$inputs.= wf_TextInput('IP', __('IP') . $sup, $TermData['ip'], true, '20');
	$inputs.= wf_Selector('Type', $type, __('Type'), $TermData['type'], true);
	$inputs.= wf_TextInput('Username', __('Username') . $sup, $TermData['username'], true, '20');
	$inputs .= wf_TextInput('Password', __('Password') . $sup, $TermData['password'], true, '20');
	$inputs.= wf_TextInput('RemoteID', __('Remote-ID') . $sup, $TermData['remote-id'], true, '20');
	$inputs.= wf_TextInput('Interface', __('Interface') . $sup, $TermData['interface'], true, '20');
	$inputs.= wf_TextInput('Relay', __('Relay Address') . $sup, $TermData['relay'], true, '20');
	$inputs.= wf_Tag('br');
	$inputs.= wf_Submit(__('Save'));
	$form		 = wf_Form("", 'POST', $inputs, 'glamour');
	$form.=wf_Link(self::MODULE_URL, 'Back', true, 'ubButton');
	show_window(__('Edit'), $form);
    }

}

class SwitchLogin {

    /**
     * Contains all available switch logins
     * 
     * @var array
     */
    protected $AllSwLogin = array();

    /**
     * Contains all switches data
     * 
     * @var array
     */
    protected $AllSwitches = array();

    /**
     * Contains data for switch selector as switch['id'] => switch['location'] + IP + modelname
     * 
     * @var array
     */
    protected $SwitchSelector = array();

    /**
     * Contains all snmptemplates in config/autoconfig/
     * 
     * @var array
     */
    protected $AllAutoconfigSnmptemplates = array();

    /**
     * Contains all switchmodels data
     * 
     * @var array
     */
    protected $AllSwitchModels = array();

    const MODULE		 = 'SWITCHLOGIN';
    const MODULE_URL	 = '?module=switchlogin';
    const TABLE_NAME	 = 'switch_login';
    const PATH		 = 'config/autoconfig/';

    public function __construct() {
	$this->LoadSwLogin();
	$this->LoadAllSwitchModels();
	$this->LoadSwitchSelecor();
	$this->LoadAutoconfigSnmp();
    }

    /**
     * Load all switches logins to $AllSwLogin
     * 
     * @return void
     */
    protected function LoadSwLogin() {
	$query	 = "SELECT * FROM " . self::TABLE_NAME;
	$data	 = simple_queryall($query);
	if (!empty($data)) {
	    foreach ($data as $each) {
		$this->AllSwLogin[$each['id']] = $each;
	    }
	}
    }

    protected function LoadAllSwitchModels() {
	$query	 = "SELECT * FROM `switchmodels`";
	$data	 = simple_queryall($query);
	if (!empty($data)) {
	    foreach ($data as $each) {
		$this->AllSwitchModels[$each['id']] = $each['modelname'];
	    }
	}
    }

    protected function LoadSwitchSelecor() {
	$query	 = "SELECT * FROM `switches`";
	$data	 = simple_queryall($query);
	if (!empty($data)) {
	    foreach ($data as $each) {
		$this->SwitchSelector[$each['id']] = $this->AllSwitchModels[$each['modelid']] . " | " . $each['location'] . " | " . $each['ip'];
	    }
	}
    }

    protected function LoadAutoconfigSnmp() {
	$allTemplates	 = rcms_scandir(self::PATH);
	$templates	 = array();
	$result		 = array('' => __('No'));
	if (!empty($allTemplates)) {
	    foreach ($allTemplates as $each) {
		$templates[$each] = rcms_parse_ini_file(self::PATH . $each, true);
	    }
	}
	if (!empty($templates)) {
	    foreach ($templates as $io => $each) {
		$result[$io] = $each['define']['DEVICE'];
	    }
	}
	$this->AllAutoconfigSnmptemplates = $result;
    }

    /**
     * Shows form for adding new snmp login data for switch
     * 
     * @return object
     */
    public function SwLoginAddSnmpForm() {
	$cell	 = wf_HiddenInput('add', 'true');
	$cell.= wf_HiddenInput('SwMethod', 'SNMP');
	$cell.= wf_HiddenInput('SwLogin', '');
	$cell.= wf_HiddenInput('SwPass', '');
	$cell.= wf_HiddenInput('Enable', '');
	$cell.= wf_Selector('swmodel', $this->SwitchSelector, __('Model'), '', true);
	$cell.= wf_Selector('snmptemplate', $this->AllAutoconfigSnmptemplates, __('Template'), '', true);
	$cell.= wf_TextInput('RwCommunity', __('SNMP community'));
	$cell.= wf_Tag('br');
	$cell.= wf_Submit(__('Save'));
	$Row	 = wf_TableRow($cell, 'row1');
	$form	 = wf_Form("", 'POST', $Row, 'glamour');
	die($form);
    }

    /**
     * Shows form for editing existing snmp login for switch
     * 
     * @param int $id
     * @return object
     */
    public function SwLoginEditSnmpForm($id) {
	$params	 = $this->AllSwLogin[$id];
	$cell	 = wf_HiddenInput('edit', 'true');
	$cell.= wf_HiddenInput('EditConn', 'SNMP');
	$cell.= wf_HiddenInput('EditSwLogin', '');
	$cell.= wf_HiddenInput('EditSwPass', '');
	$cell.= wf_HiddenInput('EditEnable', '');
	$cell.= wf_Selector('swmodel', $this->SwitchSelector, __('Model'), $params['swid'], true);
	$cell.= wf_Selector('snmptemplate', $this->AllAutoconfigSnmptemplates, __('Template'), $params['snmptemplate'], true);
	$cell.= wf_TextInput('EditRwCommunity', __('SNMP community'), $params['community']);
	$cell.= wf_Tag('br');
	$cell.= wf_Submit(__('Save'));
	$Row	 = wf_TableRow($cell, 'row1');
	$form	 = wf_Form("", 'POST', $Row, 'glamour');
	$form.= wf_delimiter();
	die($form);
    }

    /**
     * Shows form for adding new ssh\telnet login data for switch
     * 
     * @return object
     */
    public function SwLoginAddConnForm() {
	$conn	 = array('SSH' => __('SSH'), 'TELNET' => __('TELNET'));
	$enable	 = array('no' => __('no'), 'yes' => __('yes'));
	$cell	 = wf_HiddenInput('add', 'true');
	$cell.= wf_HiddenInput('RwCommunity', '');
	$cell.= wf_HiddenInput('snmptemplate', '');
	$cell.= wf_Selector('swmodel', $this->SwitchSelector, __('Model'), '', true);
	$cell.= wf_Selector('SwMethod', $conn, __('Connection method'), 'SSH', true);
	$cell.= wf_Tag('br');
	$cell.= wf_TextInput('SwLogin', __('Login'));
	$cell.= wf_TextInput('SwPass', __('Password'));
	$cell.= wf_Tag('br');
	$cell.= wf_Selector('Enable', $enable, __('enable propmpt for cisco,bdcom,etc (should be same as password)'), '', true);
	$cell.= wf_Submit(__('Save'));
	$cell.= wf_delimiter();
	$Row	 = wf_TableRow($cell, 'row1');
	$form	 = wf_Form("", 'POST', $cell, 'glamour');
	$result	 = $form;
	die($result);
    }

    /**
     * Shows form for editing existing ssh\telnet login data for switch
     * 
     * @param int $id
     * @return object
     */
    public function SwLoginEditConnForm($id) {
	$params	 = $this->AllSwLogin[$id];
	$conn	 = array('SSH' => __('SSH'), 'TELNET' => __('TELNET'));
	$enable	 = array('no' => __('no'), 'yes' => __('yes'));
	$cell	 = wf_HiddenInput('edit', 'true');
	$cell.= wf_HiddenInput('EditRwCommunity', '');
	$cell.= wf_HiddenInput('snmptemplate', '');
	$cell.= wf_Selector('swmodel', $this->SwitchSelector, __('Model'), $params['swid'], true);
	$cell.= wf_Selector('EditConn', $conn, __('Connection method'), $params['method'], true);
	$cell.= wf_Tag('br');
	$cell.= wf_TextInput('EditSwLogin', __('Login'), $params['swlogin']);
	$cell.= wf_TextInput('EditSwPass', __('Password'), $params['swpass']);
	$cell.= wf_Tag('br');
	$cell.= wf_Selector('EditEnable', $enable, __('enable propmpt for cisco,bdcom,etc (should be same as password)'), $params['enable'], true);
	$cell.= wf_Submit(__('Save'));
	$cell.= wf_delimiter();
	$Row	 = wf_TableRow($cell, 'row1');
	$form	 = wf_Form("", 'POST', $cell, 'glamour');
	$result	 = $form;
	$result.=wf_delimiter();
	die($result);
    }

    /**
     * Adding login data for switch to database
     * 
     * @param int $swmodel
     * @param string $login
     * @param string $pass
     * @param string $method
     * @param string $community
     * @param int $enable
     * @return void
     */
    public function SwLoginAdd($SwModel, $SwLogin, $SwPass, $Method, $Community, $Enable, $snmpTemplate) {
	$SwLogin	 = vf(trim($SwLogin));
	$SwPass		 = vf(trim($SwPass));
	$Community	 = vf(trim($Community));
	$query		 = "INSERT INTO " . self::TABLE_NAME . " (`id`, `swid`, `swlogin`, `swpass`, `method`, `community`, `enable`, `snmptemplate`)
        VALUES (NULL, '" . $SwModel . "', '" . $SwLogin . "', '" . $SwPass . "', '" . $Method . "', '" . $Community . "', '" . $Enable . "', '" . $snmpTemplate . "')";
	nr_query($query);
	log_register('ADD Switch login `' . $SwModel . '`');
    }

    /**
     * Editing existing login data for switch in database
     * 
     * @param type $SwModel
     * @param string $SwLogin
     * @param string $SwPass
     * @param string $Conn
     * @param string $Community
     * @param int $Enable
     * @param int $id
     * @return void
     */
    public function SwLoginEditQuery($SwModel, $SwLogin, $SwPass, $Method, $Community, $Enable, $snmpTemplate, $id) {
	simple_update_field(self::TABLE_NAME, 'swid', $SwModel, "WHERE `id`='" . $id . "'");
	simple_update_field(self::TABLE_NAME, 'swlogin', trim(vf($SwLogin)), "WHERE `id`='" . $id . "'");
	simple_update_field(self::TABLE_NAME, 'swpass', trim(vf($SwPass)), "WHERE `id`='" . $id . "'");
	simple_update_field(self::TABLE_NAME, 'method', $Method, "WHERE `id`='" . $id . "'");
	simple_update_field(self::TABLE_NAME, 'community', trim(vf($Community)), "WHERE `id`='" . $id . "'");
	simple_update_field(self::TABLE_NAME, 'enable', $Enable, "WHERE `id`='" . $id . "'");
	simple_update_field(self::TABLE_NAME, 'snmptemplate', $snmpTemplate, "WHERE `id`='" . $id . "'");
	log_register('MODIFY Switch login [' . $SwModel . ']');
    }

    /**
     * Show all availables switch logins
     * 
     * @return object
     */
    public function ShowSwAllLogin() {
	$tablecells	 = wf_TableCell(__('ID'));
	$tablecells .= wf_TableCell(__('Model'));
	$tablecells .= wf_TableCell(__('Username'));
	$tablecells .= wf_TableCell(__('Password'));
	$tablecells .= wf_TableCell(__('Method'));
	$tablecells .= wf_TableCell(__('SNMP community'));
	$tablecells .= wf_TableCell(__('enable function'));
	$tablecells .= wf_TableCell(__('SNMP template'));
	$tablecells .= wf_TableCell(__('Actions'));
	$tablerows	 = wf_TableRow($tablecells, 'row1');
	foreach ($this->AllSwLogin as $login) {
	    $tablecells	 = wf_TableCell($login['id']);
	    $tablecells.= wf_TableCell($this->SwitchSelector[$login['swid']]);
	    $tablecells.= wf_TableCell($login['swlogin']);
	    $tablecells.= wf_TableCell($login['swpass']);
	    $tablecells.= wf_TableCell($login['method']);
	    $tablecells.= wf_TableCell($login['community']);
	    $tablecells.= wf_TableCell($login['enable']);
	    $tablecells.= wf_TableCell($login['snmptemplate']);
	    $actionlinks	 = wf_JSAlert(self::MODULE_URL . '&delete=' . $login['id'], web_delete_icon(), 'Removing this may lead to irreparable results');
	    $actionlinks.= wf_Link(self::MODULE_URL . '&edit=' . $login['id'], web_edit_icon(), false);
	    $tablecells.= wf_TableCell($actionlinks);
	    $tablerows.= wf_TableRow($tablecells, 'row3');
	}
	$result = wf_TableBody($tablerows, '100%', '0', 'sortable');
	$result.= wf_delimiter();
	show_window(__('Available switches login data'), $result);
    }

    /**
     * Delete login data for switch from database
     * 
     * @param int $id
     * @return void
     */
    public function SwLoginDelete($id) {
	$id	 = vf($id);
	$query	 = "DELETE FROM `switch_login` WHERE `id`='" . $id . "'";
	nr_query($query);
	log_register('DELETE Switch Login [' . $id . ']');
    }

}

class AutoConfigurator {

    /**
     * Contains all available switches data
     * 
     * @var array
     */
    protected $AllSwitches = array();

    /**
     * Contains all available switch models data
     * 
     * @var array
     */
    protected $AllSwitchModels = array();

    /**
     * Contain all available switch ports data
     * 
     * @var array
     */
    protected $AllSwitchPort = array();

    /**
     * Contains all available switch login data
     * 
     * @var array
     */
    protected $AllSwitchLogin = array();

    /**
     * Contains all available vlan terminators
     * 
     * @var array
     */
    protected $AllTerminators = array();

    /**
     * Placeholder for SNMPHelper() object
     * 
     * @var object 
     */
    protected $SnmpHelper = '';

    /**
     * Containt config of alter.ini
     * 
     * @var object
     */
    protected $AltCfg = '';

    const AUTOCONFIG = 'config/autoconfig/';

    public function __construct() {
	$this->LoadAllSwitches();
	$this->LoadAllSwitchPort();
	$this->LoadAllSwitchLogin();
	$this->LoadTerminators();
	$this->SnmpHelper = new SNMPHelper();
    }

    /**
     * Function for getting all switches and place them to $AllSwitches
     */
    protected function LoadAllSwitches() {
	$data = zb_SwitchesGetAll();
	if (!empty($data)) {
	    foreach ($data as $each) {
		$this->AllSwitches[$each['id']] = $each;
	    }
	}
    }

    /**
     * Function for getting all switchport data and place it to $AllSwitchPort
     */
    protected function LoadAllSwitchPort() {
	$query	 = "SELECT * FROM `switchportassign`";
	$data	 = simple_queryall($query);
	if (!empty($data)) {
	    foreach ($data as $each) {
		$this->AllSwitchPort[$each['id']] = $each;
	    }
	}
    }

    /**
     * Function for getting all switch login data and place it to $AllSwitchLogin
     */
    protected function LoadAllSwitchLogin() {
	$query	 = "SELECT * FROM " . SwitchLogin::TABLE_NAME;
	$data	 = simple_queryall($query);
	if (!empty($data)) {
	    foreach ($data as $each) {
		$this->AllSwitchLogin[$each['id']] = $each;
	    }
	}
    }

    /**
     * Get all available vlan terminators data and place it to $AllTerminators
     */
    protected function LoadTerminators() {
	$query	 = "SELECT * FROM " . VlanTerminator::DB_TABLE;
	$data	 = simple_queryall($query);
	if (!empty($data)) {
	    foreach ($data as $each) {
		$this->AllTerminators[$each['id']] = $each;
	    }
	}
    }

    /**
     * load alter.ini config     
     * 
     * @return void
     */
    protected function loadAlter() {
	global $ubillingConfig;
	$this->AltCfg = $ubillingConfig->getAlter();
    }

    protected function LoadAllSwitchModels() {
	$query	 = "SELECT * FROM `switchmodels`";
	$data	 = simple_queryall($query);
	if (!empty($data)) {
	    foreach ($data as $each) {
		$this->AllSwitchModels[$each['id']] = $each['modelname'];
	    }
	}
    }

    /**
     * Function for getting switchport data by login
     * 
     * @param string $login
     * @return array
     */
    protected function GetSwitchPortData($login) {
	$data = $this->AllSwitchPort;
	if (!empty($data)) {
	    foreach ($data as $each) {
		if ($each['login'] == $login) {
		    $SwitchPortData = $each;
		}
	    }
	    if (isset($SwitchPortData)) {
		return($SwitchPortData);
	    } else {
		return(false);
	    }
	} else {
	    return(false);
	}
    }

    /**
     * Function for getting switch login data by switchid
     * 
     * @param integer $switchid
     * @return array
     */
    protected function GetSwitchLoginData($switchid) {
	$data = $this->AllSwitchLogin;
	if (!empty($data)) {
	    foreach ($data as $each) {
		if ($each['swid'] == $switchid) {
		    $SwitchLoginData = $each;
		}
	    }
	    if (isset($SwitchLoginData)) {
		return ($SwitchLoginData);
	    } else {
		return (false);
	    }
	} else {
	    return (false);
	}
    }

    /**
     * Function for getting switch data by switchid
     * 
     * @param integer $switchid
     * @return array or false
     */
    protected function GetSwitchesData($switchid) {
	$data = $this->AllSwitches;
	if (!empty($data)) {
	    foreach ($data as $each) {
		if ($each['id'] == $switchid) {
		    $SwitchesData = $each;
		}
	    }
	    if (isset($SwitchesData)) {
		return($SwitchesData);
	    } else {
		return(false);
	    }
	} else {
	    return (false);
	}
    }

    /**
     * Get switch IP by ID
     * 
     * @param integer $parentid
     * @return string or false
     */
    protected function GetSwUplinkIP($parentid) {
	$data = $this->AllSwitches;
	if (!empty($data)) {
	    foreach ($data as $each) {
		if ($each['id'] == $parentid) {
		    $result = $each['ip'];
		}
	    }
	    if (isset($result)) {
		return($result);
	    } else {
		return(false);
	    }
	} else {
	    return (false);
	}
    }

    /**
     * Check if IP belongs to vlan terminator
     * 
     * @param string $ip
     * @return bool
     */
    protected function CheckTermIP($ip) {
	$result = false;
	if (!empty($this->AllTerminators)) {
	    foreach ($this->AllTerminators as $each) {
		if ($each['ip'] == $ip) {
		    $result = true;
		}
	    }
	}
	return($result);
    }

    /**
     * Check weather vlan already created on switch
     * 
     * @param string $ip
     * @param string $community
     * @param string $oid
     * @return bool
     */
    protected function CheckVlan($ip, $community, $oid) {
	$query	 = $this->SnmpHelper->walk($ip, $community, $oid, false);
	$query	 = trim($query);
	$tmp	 = explode("=", $query);
	if (isset($tmp[1])) {
	    if (!empty($tmp[1])) {
		return(true);
	    } else {
		return (false);
	    }
	} else {
	    return(false);
	}
    }

    /**
     * Check if vlan was applied on port
     * 
     * @param string $ip
     * @param string $community
     * @param string $oid
     * @param integer $vlan
     * @return bool
     */
    protected function CheckPvid($ip, $community, $oid, $vlan) {
	$query	 = $this->SnmpHelper->walk($ip, $community, $oid, false);
	$query	 = trim($query);
	$tmp	 = explode(":", $query);
	if (isset($tmp[1])) {
	    if ($tmp[1] == $vlan) {
		return(true);
	    } else {
		return(false);
	    }
	} else {
	    return(false);
	}
    }

    /**
     * Change pvid on users port
     * 
     * @param string $login
     * @param integer $vlan
     * @return void
     */
    public function ChangePvid($login, $vlan) {
	if ($this->GetSwitchPortData($login)) {
	    $SwitchPortData	 = $this->GetSwitchPortData($login);
	    $port		 = $SwitchPortData['port'];
	    $SwitchId	 = $SwitchPortData['switchid'];
	    $ModelId	 = $this->AllSwitches[$SwitchId]['modelid'];

	    if ($this->GetSwitchLoginData($SwitchId)) {
		$SwitchLoginData = $this->GetSwitchLoginData($SwitchId);
		$method		 = $SwitchLoginData['method'];
		if ($method == 'SNMP') {
		    $community	 = $SwitchLoginData['community'];
		    $snmpTemplate	 = $SwitchLoginData['snmptemplate'];

		    if ($this->GetSwitchesData($SwitchId)) {
			$SwitchesData	 = $this->GetSwitchesData($SwitchId);
			$ip		 = $SwitchesData['ip'];
			$ParentId	 = $SwitchesData['parentid'];

			if (file_exists(self::AUTOCONFIG . $snmpTemplate)) {
			    $SNMPData = rcms_parse_ini_file(self::AUTOCONFIG . $snmpTemplate, true);
			    if (isset($SNMPData['define']['HEX'])) {
				$group = 0;
				if ($port > 4) {
				    $portPlace = $port % 4;
				    if ($portPlace == 0) {
					$portPlace = 4;
				    }
				    $counter = $port;
				    while ($counter > 0) {
					$group++;
					$counter -= 4;
				    }
				} else {
				    $group		 = 1;
				    $portPlace	 = $port;
				}
				switch ($portPlace) {
				    case 1:
					$portPlaceHex	 = 8;
					break;
				    case 2:
					$portPlaceHex	 = 4;
					break;
				    case 3:
					$portPlaceHex	 = 2;
					break;
				    case 4:
					$portPlaceHex	 = 1;
					break;
				}
				$hexString		 = $SNMPData['define']['HEX'];
				$hexString		 = str_replace(' ', '', $hexString);
				$hexString[$group - 1]	 = $portPlaceHex;
				$split			 = str_split($hexString);
				$hexString		 = '';
				$stringCounter		 = 1;
				foreach ($split as $each) {
				    if (($stringCounter % 2) == 0) {
					$hexString.= $each . " ";
				    } else {
					$hexString.= $each;
				    }
				    $stringCounter++;
				}
				$pattern = array('/PORT/', '/VLAN/', '/HEX/', '/LOGIN/');
				$replace = array($port, $vlan, $hexString, $login);
			    } else {
				$pattern = array('/PORT/', '/VLAN/', '/LOGIN/');
				$replace = array($port, $vlan, $login);
			    }

			    if ($SNMPData['define']['TYPE'] == 'simple') {
				foreach ($SNMPData as $section => $eachpoll) {
				    if ($section != 'define') {
					if ($this->CheckVlan($ip, $community, $SNMPData['define']['CHECK'] . "." . $vlan)) {
					    if ($section != 'create') {
						$data[] = array(
						    'oid'	 => preg_replace($pattern, $replace, $eachpoll['OID']),
						    'type'	 => $eachpoll['TYPE'],
						    'value'	 => preg_replace($pattern, $replace, $eachpoll['VALUE'])
						);
					    }
					} else {
					    $data[] = array(
						'oid'	 => preg_replace($pattern, $replace, $eachpoll['OID']),
						'type'	 => $eachpoll['TYPE'],
						'value'	 => preg_replace($pattern, $replace, $eachpoll['VALUE'])
					    );
					}
				    }
				}

				$result = $this->SnmpHelper->set($ip, $community, $data);
				if (isset($result)) {
				    $CheckOid = preg_replace($pattern, $replace, $SNMPData['change']['OID']);
				    if ($this->CheckPvid($ip, $community, $CheckOid, $vlan)) {
					if (!empty($ParentId)) {
					    $this->CreateVlanLooped($ParentId, $vlan);
					} else {
					    show_warning(__("Switch has no uplink"));
					}
					log_register(__("Change PVID to") . " " . $vlan . " vlan " . __("on port") . " " . $port . " " . __("switch") . " " . $ip . " " . __("for") . " " . $login);
					show_success($result);
				    } else {
					show_error(__("Something goes wrong, vlan wasnt applied on port"));
				    }
				} else {
				    show_error(__('Nothing happend'));
				}
			    }
			    if ($SNMPData['define']['TYPE'] == 'alcatel') {
				$data = '';
				foreach ($SNMPData as $section => $eachpoll) {
				    if ($section != 'define') {
					if ($this->CheckVlan($ip, $community, $SNMPData['define']['CHECK'] . "." . $vlan)) {
					    if ($section != 'create') {
						$oid	 = preg_replace($pattern, $replace, $eachpoll['OID']);
						$type	 = $eachpoll['TYPE'];
						$value	 = preg_replace($pattern, $replace, $eachpoll['VALUE']);
						$data.= $oid . ' ' . $type . ' ' . $value . ' ';
					    }
					} else {
					    $oid	 = preg_replace($pattern, $replace, $eachpoll['OID']);
					    $type	 = $eachpoll['TYPE'];
					    $value	 = preg_replace($pattern, $replace, $eachpoll['VALUE']);
					    $data.= $oid . ' ' . $type . ' ' . $value . ' ';
					}
				    }
				}
				$this->loadAlter();
				if ($this->AltCfg['SNMP_MODE'] != 'system') {
				    $snmpSet = $this->AltCfg['SNMPSET_PATH'];
				    $snmpSet.= ' -c ' . $community . ' ' . $ip . ' ' . $data;
				    $result	 = shell_exec($snmpSet);
				} else {
				    $result = $this->SnmpHelper->set($ip, $community, $data);
				}
				if (isset($result)) {
				    $CheckOid = preg_replace($pattern, $replace, $SNMPData['change']['OID']);
				    if ($this->CheckPvid($ip, $community, $CheckOid, $vlan)) {
					if (!empty($ParentId)) {
					    $this->CreateVlanLooped($ParentId, $vlan);
					} else {
					    show_warning(__("Switch has no uplink"));
					}
					log_register(__("Change PVID to") . " " . $vlan . " vlan " . __("on port") . " " . $port . " " . __("switch") . " " . $ip . " " . __("for") . " " . $login);
					show_success($result);
				    } else {
					show_error(__("Something goes wrong, vlan wasnt applied on port"));
				    }
				} else {
				    show_error(__('Nothing happend'));
				}
			    }
			} else {
			    show_error(__("No suitable SNMP template found"));
			}
		    } else {
			show_error(__('Swich has no ip or parent for switchid' . ' ' . $SwitchId));
		    }
		} else {
		    $swlogin = $SwitchLoginData['swlogin'];
		    $swpass	 = $SwitchLoginData['swpass'];
		    if ($this->GetSwitchesData($SwitchId)) {
			$SwitchesData	 = $this->GetSwitchesData($SwitchId);
			$ip		 = $SwitchesData['ip'];
			$this->LoadAllSwitchModels();
			$swmodel	 = $this->AllSwitchModels[$ModelId];
			shell_exec(CONFIG_PATH . "scripts/$swmodel $swlogin $swpass $ip $vlan $port");
			show_success(__("Success"));
		    }
		}
	    } else {
		show_error(__('No switch login data found for switchid' . ' ' . $SwitchId));
	    }
	} else {
	    show_error(__('No switchport data found'));
	}
    }

    /**
     * Create vlan on transit switches
     * 
     * @param integer $SwitchId
     * @param integer $vlan
     * @return void
     */
    public function CreateVlanLooped($SwitchId, $vlan, $parent = true) {
	if (!$parent) {
	    if ($this->GetSwitchesData($SwitchId)) {
		$SwitchesDataParent	 = $this->GetSwitchesData($SwitchId);
		$ParentIdtmp		 = $SwitchesDataParent['parentid'];
		$SwitchId		 = $ParentIdtmp;
	    }
	}

	$result = '';

	while (!empty($SwitchId)) {
	    $SwitchIp = $this->GetSwUplinkIP($SwitchId);
	    if ($this->CheckTermIP($SwitchIp)) {
		break;
	    }

	    if ($this->GetSwitchLoginData($SwitchId)) {
		$SwitchLoginData = $this->GetSwitchLoginData($SwitchId);
		$method		 = $SwitchLoginData['method'];
		if ($method == 'SNMP') {
		    $community	 = $SwitchLoginData['community'];
		    $snmpTemplate	 = $SwitchLoginData['snmptemplate'];

		    if ($this->GetSwitchesData($SwitchId)) {
			$SwitchesData	 = $this->GetSwitchesData($SwitchId);
			$ip		 = $SwitchesData['ip'];
			$ParentId	 = $SwitchesData['parentid'];

			if (file_exists(self::AUTOCONFIG . $snmpTemplate)) {
			    $SNMPData	 = rcms_parse_ini_file(self::AUTOCONFIG . $snmpTemplate, true);
			    $pattern	 = '/VLAN/';
			    $replace	 = $vlan;
			    $CheckOid	 = preg_replace($pattern, $replace, $SNMPData['create']['OID']);

			    if ($SNMPData['define']['TYPE'] == 'simple') {
				if ($this->CheckVlan($ip, $community, $CheckOid)) {
				    foreach ($SNMPData as $section => $eachpoll) {
					if ($section == 'save') {
					    $data[] = array(
						'oid'	 => preg_replace($pattern, $replace, $eachpoll['OID']),
						'type'	 => $eachpoll['TYPE'],
						'value'	 => preg_replace($pattern, $replace, $eachpoll['VALUE'])
					    );
					}
				    }
				    log_register(__("VLAN") . " " . $vlan . " " . __("already created") . " " . __("on switch") . " " . $ip);
				} else {
				    foreach ($SNMPData as $section => $eachpoll) {
					if ($section != 'define') {
					    if ($section != 'change') {
						$data[] = array(
						    'oid'	 => preg_replace($pattern, $replace, $eachpoll['OID']),
						    'type'	 => $eachpoll['TYPE'],
						    'value'	 => preg_replace($pattern, $replace, $eachpoll['VALUE'])
						);
					    }
					}
				    }
				}

				$result.= $this->SnmpHelper->set($ip, $community, $data);
				if ($this->CheckVlan($ip, $community, $CheckOid)) {
				    log_register(__("Created vlan") . " " . $vlan . " " . __("on switch") . " " . $ip);
				}
				$SwitchId = $ParentId;
			    }
			} else {
			    break;
			}
		    } else {
			show_error(__('Swich has no ip or parent for switchid' . ' ' . $SwitchId));
		    }
		}
	    } else {
		show_error(__('No switch login data found for switchid' . ' ' . $SwitchId));
	    }
	}
	return ($result);
    }

}

class OnuConfigurator {

    /**
     * Contains all onu data
     * 
     * @var array
     */
    protected $allOnu = array();

    /**
     * Contains all OLT's data
     * 
     * @var array
     */
    protected $allOlt = array();

    /**
     * Contains all olt models and snmptemplates
     * 
     * @var array 
     */
    protected $allOltModels	 = array();
    protected $AutoConfig	 = array();

    public function __construct() {
	$this->loadOnu();
	$this->LoadAllOlt();
	$this->loadOltModels();
	$this->snmp		 = new SNMPHelper();
	$this->AutoConfig	 = new AutoConfigurator;
    }

    /**
     * 
     * Load all from `switches` to $allswitches
     */
    protected function LoadAllOlt() {
	$query	 = "SELECT `id`,`ip`,`snmp`,`modelid` from `switches` WHERE `desc` LIKE '%OLT%'";
	$raw	 = simple_queryall($query);
	if (!empty($raw)) {
	    foreach ($raw as $io => $each) {
		if (!empty($each['snmp'])) {
		    $this->allOlt[$each['id']]['ip']	 = $each['ip'];
		    $this->allOlt[$each['id']]['snmp']	 = $each['snmp'];
		    $this->allOlt[$each['id']]['modelid']	 = $each['modelid'];
		}
	    }
	}
    }

    /**
     * 
     * Load all from `pononu` to $allOnu
     */
    protected function loadOnu() {
	$query	 = "SELECT * from `pononu`";
	$all	 = simple_queryall($query);
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
	    $this->allOltModels[$each['id']]['modelname']	 = $each['modelname'];
	    $this->allOltModels[$each['id']]['snmptemplate'] = $each['snmptemplate'];
	}
    }

    /**
     * Getting olt's snmptemplate by it's ID
     * 
     * @param int $modelid
     * @return string
     */
    protected function GetOltModelTemplate($modelid) {
	$result = '';
	if (!empty($this->allOltModels)) {
	    $data	 = $this->allOltModels[$modelid];
	    $result	 = $data['snmptemplate'];
	}
	return($result);
    }

    /**
     * get olt data like ip and snmp community
     * 
     * @param int $id 
     * @return array
     */
    protected function GetOltData($id) {
	$result = array();
	if (!empty($this->allOlt)) {
	    $Olt		 = $this->allOlt[$id];
	    $result[]	 = $Olt['ip'];
	    $result[]	 = $Olt['snmp'];
	    $result[]	 = $Olt['modelid'];
	}
	return($result);
    }

    /**
     * Get onu data mac and olt ID to which onu is linked
     * 
     * @param string $login 
     * @return array
     */
    protected function GetOnuMac($login) {
	$allOnu	 = $this->allOnu;
	$result	 = array();
	foreach ($allOnu as $eachOnu => $each) {
	    if ($each['login'] == $login) {
		$result[]	 = $each['mac'];
		$result[]	 = $each['oltid'];
	    }
	}
	return $result;
    }

    /**
     * Format heximal mac address to decimal or show error
     * 
     * @param string $macOnu 
     * @return string
     */
    protected function MacHexToDec($macOnu) {
	if (check_mac_format($macOnu)) {
	    $res	 = array();
	    $args	 = explode(":", $macOnu);
	    foreach ($args as $each) {
		$res[] = hexdec($each);
	    }
	    $string = implode(".", $res);
	    return ($string);
	} else
	    show_error("Wrong mac format (shoud be XX:XX:XX:XX:XX:XX)");
    }

    /**
     * Get snmp index which linked to onu
     * 
     * @param string $macOnu
     * @param string $oltIp 
     * @param string $oltCommunity 
     * @return int
     */
    protected function GetClientIface($macOnu, $oltIp, $oltCommunity, $ifindex) {
	$macOnuRew	 = $this->MacHexToDec($macOnu);
	$interface	 = ($ifindex . "." . $macOnuRew);
	$OltInt		 = snmp2_get($oltIp, $oltCommunity, $interface);
	$index		 = explode(":", $OltInt);
	if (isset($index[1])) {
	    $tmp = trim($index[1]);
	    return($tmp);
	} else {
	    return (false);
	}
    }

    /**
     * Check wheather vlan already exists (if exists return false, if not return true)
     * 
     * @param int $vlan
     * @param string $oltIp
     * @param string $oltCommunity
     * @return bool
     */
    protected function CheckOltVlan($vlan, $oltIp, $oltCommunity, $oid) {
	@$tmp	 = snmp2_get($oltIp, $oltCommunity, $oid . "." . $vlan);
	$tmp	 = trim($tmp);
	if ($tmp == '1') {
	    $res = false;
	} else {
	    $res = true;
	}
	return ($res);
    }

    /**
     * Changes onu pvid by snmp query and if needed creates vlan
     * 
     * @param string $login
     * @param int $vlan
     * @return string
     */
    public function ChangeOnuPvid($login, $vlan, $onu_port = '1') {
	$OnuData = $this->GetOnuMac($login);
	if (!empty($OnuData)) {
	    $OnuMac	 = $OnuData[0];
	    $oltId	 = $OnuData[1];

	    $oltData = $this->GetOltData($oltId);
	    if (!empty($oltData)) {
		$oltIp		 = $oltData[0];
		$oltCommunity	 = $oltData[1];

		$template = $this->GetOltModelTemplate($oltData[2]);
		if (!empty($template)) {
		    if (file_exists('config/snmptemplates/' . $template)) {
			$iniData = rcms_parse_ini_file('config/snmptemplates/' . $template, true);

			if ($iniData['signal']['SIGNALMODE'] == 'BDCOM') {
			    $vlanCreateOid		 = $iniData['vlan']['CREATE'];
			    $ChangeOnuPvidOid	 = $iniData['vlan']['PVID'];
			    $SaveConfigOid		 = $iniData['vlan']['SAVE'];
			    $CheckVlanOid		 = $iniData['vlan']['CHECK'];
			    $IfIndexOid		 = $iniData['vlan']['IFINDEX'];
			    $IfIndex		 = $this->GetClientIface($OnuMac, $oltIp, $oltCommunity, $IfIndexOid);
			    if ($IfIndex) {
				$VlanCheck	 = $this->CheckOltVlan($vlan, $oltIp, $oltCommunity, $CheckVlanOid);
				$data		 = array();
				if ($VlanCheck) {
//create vlan on OLT
				    $data[] = array(
					'oid'	 => $vlanCreateOid . "." . $vlan,
					'type'	 => 'i',
					'value'	 => '4'
				    );
				}
//Change pvid on onu port by defolt port 1
				$data[]	 = array(
				    'oid'	 => $ChangeOnuPvidOid . "." . $IfIndex . "." . $onu_port,
				    'type'	 => 'i',
				    'value'	 => "$vlan"
				);
				$data[]	 = array(
				    'oid'	 => $SaveConfigOid,
				    'type'	 => 'i',
				    'value'	 => '1'
				);
				$result	 = $this->snmp->set($oltIp, $oltCommunity, $data);
				$result.= $this->AutoConfig->CreateVlanLooped($oltId, $vlan, false);
				return ($result);
			    } else {
				show_error('cant find onu');
			    }
			}
		    } else {
			show_error(__('SNMP template for OTL file not exists for modelid' . ' ' . $oltData[2]));
		    }
		} else {
		    show_error(__('No snmp template for OLT found') . ' modelid ' . $oltData[2]);
		}
	    } else {
		show_error(__('No olt data found for oltid' . ' ' . $oltId));
	    }
	} else {
	    show_error(__('No pair onu->login found in PONizer'));
	}
    }

}

/**
 * Get users login by IP
 * 
 * @param string $ip
 * @return string
 */
function UserGetLoginByIP($ip) {
    $query	 = "SELECT * FROM `users` WHERE `ip`='" . $ip . "'";
    $res	 = simple_query($query);
    $login	 = $res['login'];
    return($login);
}

/**
 * Get users vlan by login
 * 
 * @param string $login
 * @return int
 */
function UserGetVlan($login) {
    $query	 = "select vlan from vlanhosts where login='" . $login . "'";
    $vlan	 = simple_query($query);
    return($vlan['vlan']);
}

/**
 * Get network id by ip
 * 
 * @param string $ip
 * @return int
 */
function GetNetidByIP($ip) {
    $query	 = "SELECT `netid` FROM `nethosts` WHERE `ip`='" . $ip . "'";
    $res	 = simple_query($query);
    return($res['netid']);
}

/**
 * Get vlan terminators remote id by network id
 * 
 * @param int $netid
 * @return string
 */
function GetTermRemoteByNetid($netid) {
    $query	 = "SELECT `remote-id` FROM `vlan_terminators` where `netid`='" . $netid . "'";
    $remote	 = simple_query($query);
    return($remote ['remote-id'] );
}

/**
 * 
 * @param string $login
 * @return object
 */
function web_ProfileVlanControlForm($login) {
    $login		 = mysql_real_escape_string($login);
    $query		 = "SELECT * from `vlanhosts` WHERE `login`='" . $login . "'";
    $formStyle	 = 'glamour';
    $alterconf	 = rcms_parse_ini_file(CONFIG_PATH . 'alter.ini');
    if ($alterconf['VLAN_IN_PROFILE'] == 1) {
	$data = simple_query($query);
	if (!empty($data)) {
	    $current_vlan		 = $data ['vlan'];
	    $current_vlan_pool	 = $data['vlanpoolid'];
	    $query_desc		 = "SELECT * FROM `vlan_pools` WHERE `id`='" . $current_vlan_pool . "'";
	    $current_vlan_pool_desc	 = simple_query($query_desc);
	    $current_vlan_pool_descr = $current_vlan_pool_desc['desc'];
	    $cells			 = wf_TableCell(__('Vlan Pool'), '30%', 'row2');
	    $cells.= wf_TableCell($current_vlan_pool_descr);
	    $rows			 = wf_TableRow($cells, 'row3');
	    $cells			 = wf_TableCell(__('Vlan'), '30%', 'row2');
	    $cells.= wf_TableCell($current_vlan);
	    $rows.= wf_TableRow($cells, 'row3');
	    $result			 = wf_TableBody($rows, '100%', '0');
	    return($result);
	}
    }
}

function vlan_delete_host($login) {
    $query = "DELETE FROM `vlanhosts` WHERE `login`='" . $login . "'";
    nr_query($query);
    log_register("DELETE VLanHost (" . $login . ")");
}
