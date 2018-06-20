<?php

if (cfr('ROOT')) {

    //key deletion
    if (wf_CheckGet(array('licensedelete'))) {
        $avarice = new Avarice();
        $avarice->deleteKey($_GET['licensedelete']);
        rcms_redirect('?module=licensekeys');
    }

    //key installation
    if (wf_CheckPost(array('createlicense'))) {
        $avarice = new Avarice();
        if ($avarice->createKey($_POST['createlicense'])) {
            rcms_redirect('?module=licensekeys');
        } else {
            show_error(__('Unacceptable license key'));
        }
    }
    //key editing
    if (wf_CheckPost(array('editlicense', 'editdbkey'))) {
        $avarice = new Avarice();
        if ($avarice->updateKey($_POST['editdbkey'], $_POST['editlicense'])) {
            rcms_redirect('?module=licensekeys');
        } else {
            show_error(__('Unacceptable license key'));
        }
    }

    //displaying Ubilling serial for license offering
    $hostid_q = "SELECT `value` from `ubstats` WHERE `key`='ubid'";
    $hostid = simple_query($hostid_q);
    if (empty($hostid)) {
        //on second refresh, key will be generated
        rcms_redirect('?module=licensekeys');
    } else {
        //render current Ubilling serial info
        show_info(__('Use this Ubilling serial for license keys purchase') . ': ' . wf_tag('b') . $hostid['value'] . wf_tag('b', true));
    }

    //show available license keys
    zb_LicenseLister();

    zb_BillingStats(true);
} else {
    show_error(__('Access denied'));
}
?>


