<?php

if (cfr('PLIPCHANGE')) {
	if (isset($_GET['username'])) {
		$alter_conf=rcms_parse_ini_file(CONFIG_PATH."alter.ini");
		$login = mysql_real_escape_string($_GET['username']);
		$current_ip = zb_UserGetIP($login); // getting IP by login
		$current_mac = zb_MultinetGetMAC($current_ip); //extracting current user MAC
		$billingConf = $ubillingConfig->getBilling(); //getting billing.ini config

		/**
		 * Build HTML selector from array $ips
		 *
		 * @param string $ips array of IP addresses
		 * @return string
		 */
		function multinet_ip_selector($ips)
		{
			$result='';
			if (!empty ($ips)) {
				foreach ($ips as $io => $eachip) {
					$result .= '<option value="' . $eachip . '">' . $eachip . '</option>';
				}
			}
			return ($result);
		}

		/**
		 * Returns net_id assigned to current user ip
		 *
		 * @return int
		 */
		function get_netid()
		{
			global $current_ip;
			If(empty($_POST['serviceselect'])){
				$query = 'SELECT netid FROM nethosts WHERE ip="' . $current_ip . '"';
				$ret = simple_query($query);
				return $ret['netid'];
			}else{
				return mysql_real_escape_string($_POST['serviceselect']);
			}

		}

		/**
		 * Returns id of service, which assigned to netid
		 *
		 * @param int $netid netid
		 * @return int
		 */
		function netid2serviceid($netid){
			$query = 'SELECT id FROM services WHERE netid='.$netid;
			$ret = simple_query($query);
			return $ret['id'];
		}

		/**
		 * Returns all free IP in selected service
		 *
		 * @param int $netid netid
		 * @return array
		 */
		function get_free_ips($netid)
		{
			return multinet_get_all_free_ip('nethosts', 'ip', $netid);
		}

		/**
		 * Returns new user service select form
		 *
		 * @return string
		 */
		function web_IPChangeFormService()
		{
			global $alter_conf;
			$netid = get_netid();
			$service_id=netid2serviceid($netid);
			$inputs = str_replace('"'.$service_id.'"','"'.$service_id.'" SELECTED' , multinet_service_selector()) . ' ' . __('New user service');
			$inputs .= wf_delimiter();


			if (isset($alter_conf['IP_CUSTOM_SELECT'])) {
				$free_ips = get_free_ips($netid);
				$disabled = count($free_ips)>0 ? '' : 'disabled';				
				$inputs .= '<select '.$disabled.' name="ipselect">'.multinet_ip_selector($free_ips).'</select> '. __('New avaliable IP');;
				$inputs .= wf_delimiter();
				$inputs .= '<input type="hidden" name="save_trigger" value="0">';			
				$type = 'button';
			}else{
				$type = 'submit';
			}
			$inputs .= '<input '.$disabled.' type="'.$type.'" value="' . __('Save') . '">';
			$result = wf_Form("", 'POST', $inputs, 'floatpanels');
			return($result);
		}

		/**
		 * Returns array with subnets usage stats
		 *
		 * @return array
		 */
		function zb_FreeIpStats()
		{
			$result = array();
			$allServices = array();
			$allNets = array();
			$nethostsUsed = array();

			$servicesTmp = multinet_get_services();
			$netsTmp = multinet_get_all_networks();
			$neth_q = "SELECT COUNT(id) as count, netid from `nethosts` group by `netid`";
			$nethTmp = simple_queryall($neth_q);

			if (!empty($nethTmp)) {
				foreach ($nethTmp as $io => $each) {
					$nethostsUsed[$each['netid']] = $each['count'];
				}
			}

			if (!empty($servicesTmp)) {
				foreach ($servicesTmp as $io => $each) {
					$allServices[$each['netid']] = $each['desc'];
				}
			}

			if (!empty($netsTmp)) {
				foreach ($netsTmp as $io => $each) {
					$totalIps = multinet_expand_network($each['startip'], $each['endip']);
					$allNets[$each['id']]['desc'] = $each['desc'];
					$allNets[$each['id']]['total'] = count($totalIps);
					// finding used hosts count
					if (isset($nethostsUsed[$each['id']])) {
						$allNets[$each['id']]['used'] = $nethostsUsed[$each['id']];
					} else {
						$allNets[$each['id']]['used'] = 0;
					}
					// finding network associated service
					if (isset($allServices[$each['id']])) {
						$allNets[$each['id']]['service'] = $allServices[$each['id']];
					} else {
						$allNets[$each['id']]['service'] = '';
					}
				}
			}

			return ($allNets);
		}

		/**
		 * Renders subnets usage stats
		 *
		 * @return string
		 */
		function web_FreeIpStats()
		{
			$result = '';
			$data = zb_FreeIpStats();

			$cells = wf_TableCell(__('ID'));
			$cells .= wf_TableCell(__('Network/CIDR'));
			$cells .= wf_TableCell(__('Total') . ' ' . __('IP'));
			$cells .= wf_TableCell(__('Used') . ' ' . __('IP'));
			$cells .= wf_TableCell(__('Free') . ' ' . __('IP'));
			$cells .= wf_TableCell(__('Service'));
			$rows = wf_TableRow($cells, 'row1');

			if (!empty($data)) {
				foreach ($data as $io => $each) {
					$free = $each['total'] - $each['used'];
					$fontColor = ($free <= 5) ? '#a90000' : '';
					$cells = wf_TableCell($io);
					$cells .= wf_TableCell($each['desc']);
					$cells .= wf_TableCell($each['total']);
					$cells .= wf_TableCell($each['used']);
					$cells .= wf_TableCell(wf_tag('font', false, '', 'color="' . $fontColor . '"') . $free . wf_tag('font', false));
					$cells .= wf_TableCell($each['service']);
					$rows .= wf_TableRow($cells, 'row3');
				}
			}

			$result = wf_TableBody($rows, '100%', 0, 'sortable');
			return ($result);
		}

		/**
		 * Flushes all old user`s networking data and applies new one
		 *
		 * @param string $current_ip current users`s IP
		 * @param string $current_mac current users`s MAC address
		 * @param int $new_multinet_id new network ID extracted from service
		 * @param string $new_free_ip new IP address which be applied for user
		 * @param string $login existing stargazer user login
		 * @return void
		 */
		function zb_IPChange($current_ip, $current_mac, $new_multinet_id, $new_free_ip, $login)
		{
			global $billing;
			global $billingConf;
			// force user disconnect
			if ($billingConf['RESET_AO']) {
				$billing->setao($login, 0);
			} else {
				$billing->setdown($login, 1);
			}

			$billing->setip($login, $new_free_ip);
			multinet_delete_host($current_ip);
			multinet_add_host($new_multinet_id, $new_free_ip, $current_mac);
			multinet_rebuild_all_handlers();
			multinet_RestartDhcp();
			// back teh user online
			if ($billingConf['RESET_AO']) {
				$billing->setao($login, 1);
			} else {
				$billing->setdown($login, 0);
			}
		}
		// primary module part
		if (isset($_POST['serviceselect'])){
			if(isset($alter_conf['IP_CUSTOM_SELECT'])){
				if(!empty($_POST['save_trigger']) && isset($_POST['ipselect']) && $_POST['save_trigger']=='1'){
					$new_multinet_id = multinet_get_service_networkid($_POST['serviceselect']);
					@$new_free_ip = mysql_real_escape_string($_POST['ipselect']);
					zb_IPChange($current_ip, $current_mac, $new_multinet_id, $new_free_ip, $login);
					log_register("CHANGE MultiNetIP (" . $login . ") FROM " . $current_ip . " ON " . $new_free_ip . "");
					rcms_redirect("?module=pl_ipchange&username=" . $login);
				}else{
					$new_multinet_id = mysql_real_escape_string($_POST['serviceselect']);
					$free_ips = get_free_ips(multinet_get_service_networkid($new_multinet_id));
					if (!empty($free_ips)) {
						echo json_encode(
							array(
							'result'=>true,
							'data'=>multinet_ip_selector($free_ips)));
						die();
					}else{
						echo json_encode(
						array(
						'result'=>false,
						'data'=>''));
						die();
					}
				};
			}else{
	            $new_multinet_id = multinet_get_service_networkid($_POST['serviceselect']);
	            @$new_free_ip = multinet_get_next_freeip('nethosts', 'ip', $new_multinet_id);
	            if (empty($new_free_ip)) {
	                $alert = wf_tag('script', false, '', 'type="text/javascript"') . 'alert("' . __('Error') . ': ' . __('No free IP available in selected pool') . '");' . wf_tag('script', true);
	                print($alert);
	                rcms_redirect("?module=multinet");
	                die();
	            }	
	            zb_IPChange($current_ip, $current_mac, $new_multinet_id, $new_free_ip, $login);
	            log_register("CHANGE MultiNetIP (" . $login . ") FROM " . $current_ip . " ON " . $new_free_ip . "");
	            rcms_redirect("?module=pl_ipchange&username=" . $login);
        	}			
		} 
		else {
			$out=web_FreeIpStats();
			if(isset($alter_conf['IP_CUSTOM_SELECT'])){
				$out.= '<script type="text/javascript">
function setEnabled(enabled){
	$("[name=ipselect]").attr("disabled", enabled);
	$("[type=button]").attr("disabled", enabled);
}

function getIPlist(){
	$.ajax({
		type: "POST",
		data: $("form.floatpanels").serializeArray(),
		success: function(data){
			var data = jQuery.parseJSON(data);
			setEnabled(!data.result);
			$("[name=ipselect]").html(data.data);
			if(data.result == true){
			}else{
				alert("' . __('Error') . ': ' . __('No free IP available') . '");
			}
		}
	});		
}
					
$(document).ready(function() {			
	$("[type=button]").click(function(){
		$("[name=save_trigger]").val(1);
		$("form.floatpanels").submit();
		$("[name=save_trigger]").val(0);
	});

	$("[name=serviceselect]").change(function(){
		$("[name=save_trigger]").val(0);
		getIPlist();
	});				
});
</script>';				
			};
			show_window(__('Current user IP'), wf_tag('h2', false, 'floatpanels', '') . ' ' . $current_ip . wf_tag('h2', true) . '<br clear="both" />');
			show_window(__('Change user IP'), web_IPChangeFormService());
			show_window(__('IP usage stats'), $out);
		};
		show_window('', web_UserControls($login));
	};
} else {
	show_error(__('You cant control this module'));
}

?>
