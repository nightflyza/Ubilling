<?php

if (cfr('ADDVLAN')) {
    $altcfg = rcms_parse_ini_file(CONFIG_PATH . 'alter.ini');
    if ($altcfg['VLANGEN_SUPPORT']) {
        $vlanGen = new VlanGen;
        if (isset($_POST['AddVlan'])) {
            $vlanAddRequire = array('FirstVlan', 'LastVlan', 'Desc');
            if (wf_CheckPost($vlanAddRequire)) {                
                $vlanGen->AddVlanPool($_POST['Desc'], $_POST['FirstVlan'], $_POST['LastVlan'], $_POST['UseQinQ'], $_POST['sVlan']);
                rcms_redirect(VlanGen::MODULE_URL_ADDVLAN);
            } else {
                show_window(__('Error'), __('No all of required fields is filled'));
            }
        }
        if (isset($_GET['DeleteVlanPool'])) {
            $vlanGen->DeleteVlanPool(vf($_GET['DeleteVlanPool'], 3));
            rcms_redirect(VlanGen::MODULE_URL_ADDVLAN);
        }
        if (!isset($_GET['EditVlanPool'])) {
            $vlanGen->ShowVlanPools();
            $vlanGen->AddVlanPoolForm();
        }
        if (isset($_GET['EditVlanPool'])) {
            $PoolID = vf($_GET['EditVlanPool'], 3);
            $vlanGen->VlanPoolEditForm($PoolID);
            if (isset($_POST['EditVlanPool'])) {
                $VlanEditRequire = array('FirstVlan', 'LastVlan', 'Desc');
                if (wf_CheckPost($VlanEditRequire)) {
                    $vlanGen->EditVlanPool($_POST['FirstVlan'], $_POST['LastVlan'], $_POST['Desc'], $_POST['UseQinQ'], $_POST['sVlan'], $PoolID);
                    rcms_redirect(VlanGen::MODULE_URL_ADDVLAN);
                } else {
                    show_window(__('Error'), __('No all of required fields is filled'));
                }
            }            
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__("You can't control this module"));
}
?>
