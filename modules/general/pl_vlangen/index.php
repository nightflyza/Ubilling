<?php

$altcfg = rcms_parse_ini_file(CONFIG_PATH . 'alter.ini');
if ($altcfg['VLANGEN_SUPPORT']) {
    if (cfr('PLVLANGEN')) {
        if (isset($_GET['username'])) {
            $login = mysql_real_escape_string($_GET['username']);
            $cur_vlan = UserGetVlan($login); // getting vlan by login
            if (!isset($cur_vlan)) {
                $cur_vlan = UserGetQinQVlan($login);
            }
// primary module part    
            if (isset($_POST['vlandel'])) {
                vlan_delete_host($login);
                rcms_redirect("?module=pl_vlangen&username=" . $login);
            }
            if (isset($_POST['vlanpoolselect'])) {
                $new_vlan_pool_id = $_POST['vlanpoolselect'];
                $qinq = vlan_pool_get_qinq($new_vlan_pool_id);
                if ($qinq == 0) {
                    $new_free_vlan = vlan_pool_get_next_free_vlan('vlanhosts', 'vlan', $new_vlan_pool_id);
                } else {
                    $new_free_vlan = vlan_pool_get_next_free_qinq_vlan('vlanhosts_qinq', 'svlan', 'cvlan', $new_vlan_pool_id);
                }
                if (empty($new_free_vlan)) {
                    $alert = wf_tag('script', false, '', 'type="text/javascript"') . 'alert("' . __('Error') . ': ' . __('No free Vlan available in selected pool') . '");' . wf_tag('script', true);
                    print($alert);
                    rcms_redirect("?module=addvlan");
                    die();
                }
                zb_VlanChange($cur_vlan, $new_vlan_pool_id, $new_free_vlan, $login, $qinq);
                log_register("CHANGE Vlan (" . $login . ") FROM " . $cur_vlan . " ON " . $new_free_vlan . "");
                rcms_redirect("?module=pl_vlangen&username=" . $login);
            } else {
                show_window(__('Current user Vlan'), wf_tag('h2', false, 'floatpanels', '') . ' ' . $cur_vlan . wf_tag('h2', true) . '<br clear="both" />');
                show_window(__('Change user Vlan'), web_VlanChangeFormService());
                show_window(__('Delete user Vlan'), web_VlanDelete($login));

//switch configuration start		
                if ($altcfg['SWITCH_AUTOCONFIG']) {
                    $obj = new AutoConfigurator;
                    $tbinputs = wf_HiddenInput('change_vlan_on_port', 'true');
                    $tbinputs.= wf_Submit(__('Change'));
                    $form = wf_Form("", 'POST', $tbinputs, 'glamour');
                    if (isset($_POST['change_vlan_on_port'])) {
                        $set = $obj->sw_snmp_control2($cur_vlan, $login);
                        if (isset($set)) {
                            show_success($set);
                        }
                    }
                    show_window(__('Change vlan on switch port'), $form);
                }
                if ($altcfg['ONUAUTO_CONFIG']) {
                    $onuconfig = new OnuConfigurator();
                    $onuInputs = wf_HiddenInput('change_onu_pvid', 'true');
                    $onuInputs.= wf_Submit(__('Change'));
                    $onuForm = wf_Form("", 'POST', $onuInputs);
                    show_window(__('Change pvid on onu port'), $onuForm);
                    if (isset($_POST['change_onu_pvid'])) {
                        $onu_cfg = $onuconfig->ChangeOnuPvid($login, $cur_vlan);                     
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
