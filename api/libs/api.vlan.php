<?php

function zb_VlanChange($cur_vlan, $new_vlan_pool_id, $new_free_vlan, $login,$qinq) {
	$ip=zb_UserGetIP($login);
	vlan_delete_host($login);
	vlan_qinq_delete_host($login);
	if($qinq==0) {
		vlan_add_host($new_vlan_pool_id, $new_free_vlan, $login);
	} else {
		$svlan=vlan_get_pool_params($new_vlan_pool_id);
		$svlan=$svlan['svlan'];
		vlan_pool_add_qinq_host($new_vlan_pool_id, $svlan, $new_free_vlan, $login);
	}
	OnVlanConnect($ip,$new_free_vlan);
}

function web_VlanDelete($login) {
	$inputs = wf_HiddenInput('vlandel', '', 'delete',true, '25');
	$inputs.= wf_Submit(__('Delete'));
	$result = wf_form("", 'POST', $inputs, 'floatpanels');
	return($result);
}

function web_VlanChangeFormService() {
	global $cur_vlan;
	$inputs = vlan_pool_selector() . ' ' . __('New VLAN');
	$inputs.= wf_delimiter();
	$inputs.= wf_Submit(__('Save'));
	$result = wf_Form("", 'POST', $inputs, 'floatpanels');
	return($result);
}

function GetTermRemoteByNetid($netid) {
$query="SELECT `remote-id` FROM `vlan_terminators` where `netid`='".$netid."'";
$remote=simple_query($query);
return($remote['remote-id']);
}

function GetTermIdByNetid($netid) {
$query="SELECT `id` FROM `vlan_terminators` where `netid`='".$netid."'";
$remote=simple_query($query);
return($remote['id']);
}

function term_get_params($term_id) {
        $query='SELECT * from `vlan_terminators` WHERE `id`="'.$term_id.'"';
        $result=simple_query($query);
        return($result);
}

function term_add($netid,$vlanpoolid,$ip,$type,$username,$password,$remote,$interface,$relay) {
        $netid=vf($netid);
        $vlanpoolid=vf($vlanpoolid);
        $ip=vf($ip);
	$type=vf($type);
	$username=vf($username);
	$password=vf($password);
	$remote=vf($remote);
	$interface=vf($interface);
	$relay=vf($relay);
                $query=" INSERT INTO `vlan_terminators` (
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
                                '".$netid."',
                                '".$vlanpoolid."',
                                '".$ip."',
                                '".$type."',
                                '".$username."',
				'".$password."',
				'".$remote."',
				'".$interface."',
				'".$relay."'
                )
                ";
        nr_query($query);
        log_register('ADD Terminator `'.$type.'`');
}

function term_show_editform($term_id) {
        $term_id=vf($term_id);
        $termdata=term_get_params($term_id);
        $type=array('FreeBSD'=>__('FreeBSD'), 'Linux'=>__('Linux'), 'Cisco'=>__('Cisco'));
        $sup=  wf_tag('sup').'*'.wf_tag('sup', true);
        $inputs=  wf_HiddenInput('termedit', 'true');
        $inputs.= multinet_network_selector($termdata['netid']) . ' ' . __('Network') . ' ' . wf_tag('br');
        $inputs.= vlan_pool_selector($termdata['vlanpoolid']) . ' ' . __('Vlan Pool ID') . ' ' . wf_tag('br');
        $inputs.= wf_TextInput('editip', __('IP').$sup, $termdata['ip'], true, '20');
        $inputs.= wf_Selector('edittype', $type, __('Type'), $termdata['type'],true);
        $inputs.= wf_TextInput('editusername', __('Username').$sup, $termdata['username'], true, '20');
	$inputs.= wf_TextInput('editpassword', __('Password').$sup, $termdata['password'], true, '20');
	$inputs.= wf_TextInput('editremoteid', __('Remote-ID').$sup, $termdata['remote-id'], true, '20');
	$inputs.= wf_TextInput('editinterface', __('Interface').$sup, $termdata['interface'], true, '20');
	$inputs.= wf_TextInput('editrelay', __('Relay Address').$sup, $termdata['relay'], true, '20');
        $inputs.= wf_Tag('br');
        $inputs.= wf_Submit(__('Save'));
        $form=  wf_Form("", 'POST', $inputs, 'glamour');
 
        $form.=wf_Link('?module=nas', 'Back', true, 'ubButton');
        show_window(__('Edit'), $form);
}

function delete_term($term_id) {
        $term_id=vf($term_id);
        $query="DELETE FROM `vlan_terminators` WHERE `id`='".$term_id."'";
        nr_query($query);
        log_register('DELETE Terminator ['.$term_id.']');
}

function terminators_show_form() {
        $type=array('FreeBSD'=>__('FreeBSD'), 'Linux'=>__('Linux'), 'Cisco'=>__('Cisco'));
        $sup=  wf_tag('sup').'*'.wf_tag('sup', true);
        $inputs=  wf_HiddenInput('addterm', 'true');
        $inputs.= multinet_network_selector() . ' ' . __('Network') . ' ' . wf_tag('br');
        $inputs.= vlan_pool_selector() . ' ' . __('Vlan Pool ID') . ' ' . wf_tag('br');
        $inputs.= wf_TextInput('ip', __('IP').$sup, '', true, '20');
        $inputs.= wf_Selector('type', $type, __('Type'), '',true);
        $inputs.= wf_TextInput('username', __('Username').$sup, '', true, '20');
		$inputs.= wf_TextInput('password', __('Password').$sup, '', true, '20');
		$inputs.= wf_TextInput('remoteid', __('Remote-ID').$sup, '', true, '20');
		$inputs.= wf_TextInput('interface', __('Interface').$sup, '', true, '20');
		$inputs.= wf_TextInput('relay', __('Relay Address').$sup, '', true, '20');
        $inputs.= wf_Tag('br');
        $inputs.= wf_Submit(__('Add'));
        $form=  wf_Form("", 'POST', $inputs, 'glamour');
        show_window(__('ADD Terminator'),$form);
}

function show_all_terminators() {
        $query = "SELECT * from `vlan_terminators`";
        $terminators = simple_queryall($query);
        $tablecells   = wf_TableCell(__('ID'));
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
        if ( !empty($terminators) ) {
                foreach ($terminators as $term) {
                        $tablecells  = wf_TableCell($term['id']);
                        $tablecells .= wf_TableCell($term['netid']);
                        $tablecells .= wf_TableCell($term['vlanpoolid']);
                        $tablecells .= wf_TableCell($term['ip']);
                        $tablecells .= wf_TableCell($term['type']);
						$tablecells .= wf_TableCell($term['username']);
						$tablecells .= wf_TableCell($term['password']);
						$tablecells .= wf_TableCell($term['remote-id']);
						$tablecells .= wf_TableCell($term['interface']);
						$tablecells .= wf_TableCell($term['relay']);
                        $actionlinks  = wf_JSAlert('?module=nas&deleteterm=' . $term['id'], web_delete_icon(), 'Removing this may lead to irreparable results');
                        $actionlinks .= wf_JSAlert('?module=nas&editterm=' . $term['id'], web_edit_icon(),'');
                        $tablecells .= wf_TableCell($actionlinks);
                        $tablerows .= wf_TableRow($tablecells, 'row3');
                }
        }
        $result = wf_TableBody($tablerows, '100%', '0', 'sortable');
        show_window(__('Terminators'), $result);
}



/*	Work on dispatcher for
*	execute remote or local
*	scripts to create vlan, route
*	or relay and snooping
*	for FreeBSD, Linux, Cisco 35xx/37xx
*/
function OnVlanConnect ($ip,$vlan) {
	multinet_rebuild_all_handlers();
	$netid=GetNetidByIP($ip);
	$termid=GetTermIdByNetid($netid);
	$term_data=term_get_params($termid);
	$term_ip=$term_data['ip'];
	$term_type=$term_data['type'];
	$term_user=$term_data['username'];
	$term_pass=$term_data['password'];
	$term_int=$term_data['interface'];
	$relay=$term_data['relay'];
	if($term_ip=='127.0.0.1') {
		if($term_type=='FreeBSD') {
			$res=shell_exec("./config/scripts/bsd.local.sh $term_int $ip $vlan");
		}  
		if($term_type=='Linux') {
			$res=shell_exec("./config/scripts/linux.local.sh");
		}
	} else {
		if($term_type=='FreeBSD') {
			$res=shell_exec("./config/scripts/bsd.remote.sh $term_user $term_pass $term_int $ip $vlan");
		}
		if($term_type=='Linux') {
			$res=shell_exec("./config/scripts/linux.remote.sh $term_user $term_pass $term_int $ip $vlan");
		}
		if($term_type=='Cisco') {
			$res=shell_exec("./config/scripts/cisco.sh $term_user $term_pass $vlan $term_int $relay $term_ip");
		}
	}	
}

// 	Get users netid
function GetNetidByIP ($ip) {
	$query="SELECT `netid` FROM `nethosts` WHERE `ip`='".$ip."'";
	$res=simple_query($query);
	return($res['netid']);
} 

//Get user's login by it's ip
function UserGetLoginByIP($ip) {
	$query="SELECT * FROM `users` WHERE `ip`='".$ip."'";
	$res=simple_query($query);
	$login=$res['login'];
	return($login);
}

//Get user's vlan by it's login
function UserGetVlan($login) {
	$query="select vlan from vlanhosts where login='".$login."'";
	$vlan=simple_query($query);
	return($vlan['vlan']);
}

//Check wheather user get q-in-q vlan get it if exists
function UserGetQinQVlan($login) {
	$query="SELECT `svlan`,`cvlan` FROM `vlanhosts_qinq` WHERE `login`='".$login."'";
	$vlans=simple_query($query);
	$svlan=$vlans['svlan'];
	$cvlan=$vlans['cvlan'];
	$array = array ($svlan, $cvlan);
	$vlan=implode(".", $array);
	return($vlan);
}

//Get svlan for user
function UserGetSvlan($vlanpoolid) {
	$query="SELECT `svlan` FROM `vlan_pools` WHERE `id`='".$vlanpoolid."'";
	$svlan=simple_query($query);
	return($svlan['svlan']);
}

//Check wheather vlan pool supports qinq and get it
function vlan_pool_get_qinq($vlanpoolid) {
	$query="SELECT `qinq` FROM `vlan_pools` WHERE `id`='".$vlanpoolid."'";
	$qinq=simple_query($query);
	return($qinq['qinq']);
}

//Form for selecting vlan pool
function vlan_pool_selector($currentvlanpoolid='') {
	$allvlanpools=vlan_get_all_pools();
	$result='<select name="vlanpoolselect">';
	if (!empty ($allvlanpools)) {
		foreach ($allvlanpools as $io=>$eachvlanpool) {
			if ($currentvlanpoolid==$eachvlanpool['id']) {
				$flag='SELECTED';
			} else {
				$flag='';
			}
			$result.='<option value="'.$eachvlanpool['id'].'" '.$flag.'>'.$eachvlanpool['desc'].'</option>';
		}
	}
	$result.='</select>';
	return ($result);
}

//Get all vlan pools
function vlan_get_all_pools() {
	$query="SELECT * from `vlan_pools`";
	$result=simple_queryall($query);
	return($result);
}

//Form for deleting vlan
function vlan_show_pool_delete_form() {
	$allvlanpools=vlan_get_all_pools();
	if (!empty ($allvlanpools)) {
		$form='
		<form method="POST" action="" class="row3">
		<input type="hidden" name="deletevlanpool" value="true">
		'.vlan_pool_selector().'
		<input type="submit" value="'.__('Delete').'">
		</form>
		';
		show_window(__('Delete vlan pool'), $form);
	}
}

//Get vlan pool parameters
function vlan_get_pool_params($vlanpool_id) {
	$query='SELECT * from `vlan_pools` WHERE `id`="'.$vlanpool_id.'"';
	$result=simple_query($query);
	return($result);
}

//Form for editing vlan pool
function vlan_show_pooleditform($vlanpoolid) {
	$vlanpoolid=vf($vlanpoolid);
	$vlanpooldata=vlan_get_pool_params($vlanpoolid);
	$useQinQArr=array('0'=>__('No'), '1'=>__('Yes'));
	$sup=  wf_tag('sup').'*'.wf_tag('sup', true);
	$inputs=  wf_HiddenInput('vlanpooledit', 'true');
	$inputs.= wf_TextInput('editfirstvlan', __('First Vlan').$sup, $vlanpooldata['firstvlan'], true, '20');
	$inputs.= wf_TextInput('editendvlan', __('Last Vlan').$sup, $vlanpooldata['endvlan'], true, '20');
	$inputs.= wf_TextInput('editdesc', __('Desc').$sup, $vlanpooldata['desc'], true, '20');
	$inputs.= wf_Selector('edituse_qinq', $useQinQArr, __('Use qinq'), $vlanpooldata['qinq'],true);
	$inputs.= wf_TextInput('editsvlan', __('Svlan').$sup, $vlanpooldata['svlan'], true, '20');
	$inputs.= wf_Tag('br');
	$inputs.= wf_Submit(__('Save'));
	$form=  wf_Form('', "POST", $inputs, 'glamour');

	$form.=wf_Link('?module=addvlan', 'Back', true, 'ubButton');
	show_window(__('Edit'), $form);
}

//Form to choose vlan pool
function vlan_show_pools_form() {
	$useQinQArr=array('0'=>__('No'), '1'=>__('Yes'));
	$sup=  wf_tag('sup').'*'.wf_tag('sup', true);
	$inputs=  wf_HiddenInput('addvlan', 'true');
	$inputs.= wf_TextInput('firstvlan', __('First Vlan').$sup, '', true, '20');
	$inputs.= wf_TextInput('lastvlan', __('Last Vlan').$sup, '', true, '20');
	$inputs.= wf_TextInput('desc', __('Desc').$sup, '', true, '20');
	$inputs.= wf_Selector('use_qinq', $useQinQArr, __('Use qinq'), '',true);
	$inputs.= wf_TextInput('svlan', __('Svlan').$sup, '', true, '20');
	$inputs.= wf_Tag('br');
	$inputs.= wf_Submit(__('Add'));
	$form=  wf_Form("", 'POST', $inputs, 'glamour');
	show_window(__('Add Vlan'),$form);
}

//Create vlan pool
function vlan_add_pool($desc,$firstvlan,$lastvlan,$qinq,$svlan) {
	$desc=mysql_real_escape_string($desc);
	$firstvlan=vf($firstvlan);
	$lastvlan=vf($lastvlan);
	$qinq=vf($qinq);
	if($qinq==0) {
		$query=" INSERT INTO `vlan_pools` (
				`id`,
				`desc`,
				`firstvlan`,
				`endvlan`,
				`qinq`
				)
		VALUES (
			NULL, 
			'".$desc."', 
			'".$firstvlan."', 
			'".$lastvlan."',
			'".$qinq."'
		)
	";
	}
	else {
		$query=" INSERT INTO `vlan_pools` (
				`id`,
				`desc`,
				`firstvlan`,
				`endvlan`,
				`qinq`,
				`svlan`
		)
		VALUES (
				NULL, 
				'".$desc."', 
				'".$firstvlan."', 
				'".$lastvlan."', 
				'".$qinq."', 
				'".$svlan."'
		)
		";
	}
	nr_query($query);
	log_register('ADD VlanPool `'.$desc.'`');
}

//Delete vlan pool
function vlan_delete_pool($vlanpool_id) {
	$vlanpool_id=vf($vlanpool_id,3);
	$query="DELETE FROM `vlan_pools` WHERE `id`='".$vlanpool_id."'";
	nr_query($query);
	log_register('DELETE VlanPool ['.$vlanpool_id.']');
}

//Look for all pools
function vlan_show_available_pools() {
	$alter = rcms_parse_ini_file(CONFIG_PATH . "alter.ini");
	$query = "SELECT * from `vlan_pools`";
	$vlans = simple_queryall($query);
	$tablecells   = wf_TableCell(__('ID'));
	$tablecells .= wf_TableCell(__('First Vlan'));
	$tablecells .= wf_TableCell(__('Last Vlan'));
	$tablecells .= wf_TableCell(__('Desc'));
	$tablecells .= wf_TableCell(__('qinq'));
	$tablecells .= wf_TableCell(__('svlan'));
	$tablecells .= wf_TableCell(__('Actions'));	
	$tablerows = wf_TableRow($tablecells, 'row1');
	if ( !empty($vlans) ) {
		foreach ($vlans as $vlan) {
			$tablecells  = wf_TableCell($vlan['id']);
			$tablecells .= wf_TableCell($vlan['firstvlan']);
			$tablecells .= wf_TableCell($vlan['endvlan']);
			$tablecells .= wf_TableCell($vlan['desc']);
			$tablecells .= wf_TableCell($vlan['qinq']);
			if(isset($vlan['qinq'])) { 
				$tablecells .= wf_TableCell($vlan['svlan']); 
			}
			$actionlinks  = wf_JSAlert('?module=addvlan&deletevlanpool=' . $vlan['id'], web_delete_icon(), 'Removing this may lead to irreparable results');
			$actionlinks .= wf_JSAlert('?module=addvlan&editvlanpool=' . $vlan['id'], web_edit_icon(), 'Are you serious');
			$tablecells .= wf_TableCell($actionlinks);
			$tablerows .= wf_TableRow($tablecells, 'row3');
		}
	}
	$result = wf_TableBody($tablerows, '100%', '0', 'sortable');
	show_window(__('Vlans'), $result);
}

//Unassign vlan from host
function vlan_delete_host($login) {
	$query="DELETE from `vlanhosts` WHERE `login`='".$login."'";
	nr_query($query);
	log_register("DELETE VLanPoolHost ".$login);
}

//Unassign qinq vlan from host
function vlan_qinq_delete_host($login) {
	$query="DELETE FROM `vlanhosts_qinq` WHERE `login`='".$login."'";
	nr_query($query);
	log_register("DELETE VlanPoolHost ".$login);
}

//Assign vlan for host
function vlan_add_host($vlanpoolid,$vlan,$login) {
	$query="
		INSERT INTO `vlanhosts` (
			`id` ,
			`vlanpoolid` ,
			`vlan` ,
			`login`
		)
		VALUES (
			NULL , 
			'".$vlanpoolid."', 
			'".$vlan."', 
			'".$login."'
		);
	";
	
	nr_query($query);
}

//Assign qinq vlan for host
function vlan_pool_add_qinq_host ($vlanpoolid, $svlan, $cvlan, $login) {
	$query="
		INSERT INTO `vlanhosts_qinq` (
			`id` ,
			`vlanpoolid` ,
			`svlan` ,
			`cvlan` ,
			`login`
		)
		VALUES (
			NULL , 
			'".$vlanpoolid."', 
			'".$svlan."', 
			'".$cvlan."', 
			'".$login."'
		);
	";

	nr_query($query);
} 

//Expand range of vlan pool
function vlan_pool_expand($first_vlan,$end_vlan) {
	$first=$first_vlan;
	$last=$end_vlan;
	for ($i=$first;$i<=$last;$i++) {
		$totalpool[]=$i;
	}
	if (!empty ($totalpool)) {
		foreach ($totalpool as $eachvlan) {
			if (isset ($eachvlan)) {
				$filteredpool[]=$eachvlan;
			}
		}
	}
	return($filteredpool);
}

//Get all free vlan from pool
function vlan_pool_get_all_free_vlan($table,$field,$vlanpoolid) {
	$vlan_spec=vlan_get_pool_params($vlanpoolid);
	$first_vlan=$vlan_spec['firstvlan'];
	$last_vlan=$vlan_spec['endvlan'];
	$clear_vlans=array();
	$full_vlan_pool=vlan_pool_expand($first_vlan, $last_vlan);
	$current_state_q='SELECT `'.$field.'` from `'.$table.'`';
	$all_current_used_vlan=simple_queryall($current_state_q);
	if (!empty ($all_current_used_vlan)) {
		foreach ($all_current_used_vlan as $io=>$usedvlan) {
			$clear_vlans[]=$usedvlan[$field];
		}
		$free_vlan_pool=array_diff($full_vlan_pool,$clear_vlans);
	} else {
		$free_vlan_pool=$full_vlan_pool;
	}
	return($free_vlan_pool);
}


//Get all free vlan from qinq pool
function vlan_pool_get_all_free_qinq_vlan($table,$svlan,$field,$vlanpoolid) {
	$vlan_spec=vlan_get_pool_params($vlanpoolid);
	$first_vlan=$vlan_spec['firstvlan'];
	$last_vlan=$vlan_spec['endvlan'];
	$clear_vlans=array();
	$full_vlan_pool=vlan_pool_expand($first_vlan, $last_vlan);
	$current_state_q='SELECT `'.$svlan.'`,`'.$field.'` from `'.$table.'`';
	$all_current_used_vlan=simple_queryall($current_state_q);
	if (!empty ($all_current_used_vlan)) {
		foreach ($all_current_used_vlan as $io=>$usedvlan) {
			$clear_vlans[]=$usedvlan[$field];
		}
		$free_vlan_pool=array_diff($full_vlan_pool,$clear_vlans);
	} else {
		$free_vlan_pool=$full_vlan_pool;
	}
	return($free_vlan_pool);
}

//Get next free vlan from vlan pool
function vlan_pool_get_next_free_vlan($table,$field,$vlanpoolid) {
	$all_free_vlans=vlan_pool_get_all_free_vlan($table, $field, $vlanpoolid);
	$temp = array_keys($all_free_vlans);
	return(@$all_free_vlans[$temp[0]]);
}

//Get next free vlan from qinq vlan pool
function vlan_pool_get_next_free_qinq_vlan($table,$svlan,$field,$vlanpoolid) {
	$all_free_vlans=vlan_pool_get_all_free_qinq_vlan($table, $svlan, $field, $vlanpoolid);
	$temp = array_keys($all_free_vlans);
	return(@$all_free_vlans[$temp[0]]);
}

?>
