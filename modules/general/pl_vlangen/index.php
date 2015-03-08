<?php
$altcfg=  rcms_parse_ini_file(CONFIG_PATH.'alter.ini');
if ($altcfg['VLANGEN_SUPPORT']) {
	if (cfr('PLVLANGEN')) {
		if (isset($_GET['username'])) {
			$login = mysql_real_escape_string($_GET['username']);
			$cur_vlan = UserGetVlan($login); // getting vlan by login
			if(!isset($cur_vlan)) {
				$cur_vlan= UserGetQinQVlan($login);
			}
        // primary module part    
		if (isset($_POST['vlandel'])) {
			vlan_delete_host($login);
			rcms_redirect("?module=pl_vlangen&username=" . $login);
		}
		if (isset($_POST['vlanpoolselect'])) {
			$new_vlan_pool_id = $_POST['vlanpoolselect'];
			$qinq=vlan_pool_get_qinq($new_vlan_pool_id);
			if($qinq==0) {
				@$new_free_vlan = vlan_pool_get_next_free_vlan('vlanhosts', 'vlan', $new_vlan_pool_id);}
			else {
				@$new_free_vlan = vlan_pool_get_next_free_qinq_vlan('vlanhosts_qinq', 'svlan','cvlan', $new_vlan_pool_id);
			}
			if (empty($new_free_vlan)) {
				$alert = wf_tag('script', false, '', 'type="text/javascript"') . 'alert("' . __('Error') . ': ' . __('No free Vlan available in selected pool') . '");' . wf_tag('script', true);
				print($alert);
				rcms_redirect("?module=addvlan");
				die();
			}

			zb_VlanChange($cur_vlan, $new_vlan_pool_id, $new_free_vlan, $login, $qinq);
			log_register("CHANGE Vlan (" . $login . ") FROM " . $cur_vlan . " ON " . $new_free_vlan."");
                        rcms_redirect("?module=pl_vlangen&username=" . $login);
		} else {
			show_window(__('Current user Vlan'), wf_tag('h2', false, 'floatpanels', '') . ' ' . $cur_vlan . wf_tag('h2', true) . '<br clear="both" />');
			show_window(__('Change user Vlan'), web_VlanChangeFormService());
			show_window(__('Delete user Vlan'), web_VlanDelete($login));
			$swparam=get_user_switch_modelname($login);
//switch configuration start
		if(isset($_POST['change_vlan_on_port'])) {
			$swport=get_user_port($login);
			$port=$swport['port'];
			$modelname=get_user_switch_modelname($login);
			$swtype=$modelname['modelname'];
			if(strpos($swtype,'Huawei')===false && strpos($swtype,'HUAWEI')===false && strpos($swtype,'huawei')===false) {
				$type="dlink";
			} else {
				$type="huawei";
			}
			$ports=$modelname['ports'];
			$swip=get_sw_ip($login);
			$swip=$swip['ip'];
			$rwcommunity=get_swlogin_param_swid($swport['switchid']);
			$rwcommunity=$rwcommunity['community'];
			sw_snmp_control($port,$type,$ports,$cur_vlan,$rwcommunity,$swip);
			log_register('Added vlan '.$cur_vlan.' on switch ip' .$swip);
		}
			if ($altcfg['SWITCH_AUTOCONFIG']) {
				$tbinputs = wf_HiddenInput('change_vlan_on_port', 'true');
				$tbinputs.= wf_Submit(__('Change'));
				$form=  wf_Form("", 'POST', $tbinputs, 'glamour');
			}
			show_window(__('Change vlan on switch port'),$form);
		}
		show_window('', web_UserControls($login));
		}
	} else {
		show_error(__('You cant control this module'));
	}
} else {
     show_error(__('This module is disabled'));
}
?>
