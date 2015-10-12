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
                show_window('', $VlanGen->ChangeForm());
                show_window('', $VlanGen->DeleteForm());
                show_window('', $VlanGen->ChangeOnPortForm());
                show_window('', $VlanGen->ChangeOnOnuForm());

//switch configuration start		                                    
                if (isset($_POST['ChangeVlanOnPort'])) {
                    $obj = new AutoConfigurator;
                    $set = $obj->sw_snmp_control2($cur_vlan, $login);
                    if (isset($set)) {
                        show_success($set);
                    }
                }
            
                if (isset($_POST['ChangeOnuPvid'])) {
                    $onuconfig = new OnuConfigurator();
                    $onu_cfg = $onuconfig->ChangeOnuPvid($login, $cur_vlan);
                    show_success($onu_cfg);
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
