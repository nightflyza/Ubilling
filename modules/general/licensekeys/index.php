<?php

if (cfr('ROOT')) {

    //key deletion
    if (ubRouting::checkGet('licensedelete')) {
        $avarice = new Avarice();
        $avarice->deleteKey(ubRouting::get('licensedelete'));
        ubRouting::nav('?module=licensekeys');
    }

    //key installation
    if (ubRouting::checkPost('createlicense')) {
        $avarice = new Avarice();
        if ($avarice->createKey(ubRouting::post('createlicense'))) {
            ubRouting::nav('?module=licensekeys');
        } else {
            show_error(__('Unacceptable license key'));
        }
    }
    //key editing
    if (ubRouting::checkPost(array('editlicense', 'editdbkey'))) {
        $avarice = new Avarice();
        if ($avarice->updateKey(ubRouting::post('editdbkey'), ubRouting::post('editlicense'))) {
            ubRouting::nav('?module=licensekeys');
        } else {
            show_error(__('Unacceptable license key'));
        }
    }

    //displaying Ubilling serial for license offering
    $hostid_q = "SELECT `value` from `ubstats` WHERE `key`='ubid'";
    $hostid = simple_query($hostid_q);
    if (empty($hostid)) {
        //on second refresh, key will be generated
        ubRouting::nav('?module=licensekeys');
    } else {
        //render current Ubilling serial info
        show_info(__('Use this Ubilling serial for license keys purchase') . ': ' . wf_tag('b') . $hostid['value'] . wf_tag('b', true));
    }

    //show available license keys
    zb_LicenseLister();

    zb_BillingStats();
} else {
    show_error(__('Access denied'));
}
?>


