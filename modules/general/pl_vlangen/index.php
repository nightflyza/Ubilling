<?php

$altcfg = rcms_parse_ini_file(CONFIG_PATH . 'alter.ini');
if ($altcfg['VLANGEN_SUPPORT']) {
    if (cfr('PLVLANGEN')) {
        if (isset($_GET['username'])) {
            $VlanGen = new VlanGen;
            $login = $_GET['username'];            
            $cur_vlan = $VlanGen->GetVlan($login);            
   
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
                show_window(__('Change user Vlan'), $VlanGen->ChangeForm());
                show_window(__('Delete user Vlan'), $VlanGen->DeleteForm());

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
                        show_success($onu_cfg);
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
