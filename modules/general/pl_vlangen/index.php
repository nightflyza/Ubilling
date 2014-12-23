<?php
$altcfg=  rcms_parse_ini_file(CONFIG_PATH.'alter.ini');
if ($altcfg['VLANGEN_SUPPORT']) {
	if (cfr('PLVLANGEN')) {
		if (isset($_GET['username'])) {
			$login = mysql_real_escape_string($_GET['username']);
			$cur_vlan = UserGetVlan($login); // getting vlan by login
			$ip=zb_UserGetIP($login);
			if(!isset($cur_vlan)) {
				$cur_vlan= UserGetQinQVlan($login);
			}

			function web_VlanChangeFormService() {
				global $cur_vlan;
				$inputs = vlan_pool_selector() . ' ' . __('New VLAN');
				$inputs.= wf_delimiter();
				$inputs.= wf_Submit(__('Save'));
				$result = wf_Form("", 'POST', $inputs, 'floatpanels');
				return($result);
			}

			function zb_VlanChange($cur_vlan, $new_vlan_pool_id, $new_free_vlan, $login,$qinq) {
				global $ip;
				vlan_pool_delete_host($login);
				vlan_pool_qinq_delete_host($login);
				if($qinq==0) {
					vlan_pool_add_host($new_vlan_pool_id, $new_free_vlan, $login);
				} else {
					$svlan=vlan_get_pool_params($new_vlan_pool_id);
					$svlan=$svlan['svlan'];
					vlan_pool_add_qinq_host($new_vlan_pool_id, $svlan, $new_free_vlan, $login); 
				}
			OnVlanConnect($ip,$new_free_vlan); 
			}

        // primary module part    
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
	        }
		show_window('', web_UserControls($login));
		}
	} else {
		show_error(__('You cant control this module'));
	}
}
?>
