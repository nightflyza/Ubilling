<?php

$altcfg = rcms_parse_ini_file(CONFIG_PATH . 'alter.ini');
if ($altcfg['VLANGEN_SUPPORT']) {
    if (cfr('PLVLANGEN')) {
	if (isset($_GET['username'])) {
	    $VlanGen	 = new VlanGen;
	    $login		 = $_GET['username'];
	    $cur_vlan	 = $VlanGen->GetVlan($login);
	    $reboot		 = new RebootOnu($login);
	    $describe	 = new DescribeOnu($login);
	    $onuDescription = $describe->GetOnuDescription();
	    $form		 = wf_Link("?module=vlan_mac_history&username=" . $login . "&vlan=" . $cur_vlan, __('Users MAC and VLAN history'), false, 'ubButton');
	    show_window(__('Actions'), $form);

	    if (isset($_POST['DeleteVlanHost'])) {
		$VlanGen->DeleteVlanHost($login);
		$VlanGen->DeleteVlanHostQinQ($login);
		rcms_redirect(VlanGen::MODULE_URL . "&username=" . $login);
	    }
	    if (isset($_POST['VlanPoolSelected'])) {
		$newVlanPoolID = $_POST['VlanPoolSelected'];
		$VlanGen->VlanChange($newVlanPoolID, $login);
		rcms_redirect(VlanGen::MODULE_URL . "&username=" . $login);
	    } else {
		show_window(__('Current user Vlan'), wf_tag('h2', false, 'floatpanels', '') . ' ' . $cur_vlan . wf_tag('h2', true) . '<br clear="both" />');
		if($altcfg['ONUAUTO_CONFIG_DESCRIBE']) {
			show_window(__('Current description'), wf_tag('h2', false, 'floatpanels', '') . ' ' . $onuDescription . wf_tag('h2', true) . '<br clear="both" />');
		}
		show_window('', $VlanGen->ChangeForm());
		show_window('', wf_JSAlert(VlanGen::MODULE_URL . "&username=" . $login . "&DeleteVlanHost=true", $VlanGen->DeleteForm(), __('Removing this may lead to irreparable results')));
		if ($altcfg['SWITCH_AUTOCONFIG']) {
		    show_window('', $VlanGen->ChangeOnPortForm());
		}
		if ($altcfg['ONUAUTO_CONFIG']) {
		    show_window('', $VlanGen->ChangeOnOnuForm());
		}
		if ($altcfg['ONUAUTO_CONFIG_REBOOT']) {
		    show_window('', $reboot->RebootForm());
		}
		if ($altcfg['ONUAUTO_CONFIG_DESCRIBE']) {
		    show_window('', $describe->DescribeForm($login));
		}

		if (isset($_POST['ChangeVlanOnPort'])) {
		    $obj	 = new AutoConfigurator;
		    $set	 = $obj->ChangePvid($login, $cur_vlan);
		    if (isset($set)) {
			show_success($set);
		    }
		}

		if (isset($_POST['ChangeOnuPvid'])) {
		    $onuconfig	 = new OnuConfigurator();
		    $onu_cfg	 = $onuconfig->ChangeOnuPvid($login, $cur_vlan);
		    show_success($onu_cfg);
		}
		if (isset($_POST['RebootOnu'])) {
		    $rebootResult = $reboot->RebootOnu();
		    if ($rebootResult) {
			show_success('DONE');
		    } else {
			show_error('ONU NOT FOUND');
		    }
		}

		if (isset($_POST['DescribeOnu'])) {
		    $describeResult = $describe->DescribeOnu($_POST['onuDescription']);
		    if (!empty($describeResult)) {
			show_success($describeResult);
		    } else {
			show_error('Unsuccessful');
		    }
		}
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
