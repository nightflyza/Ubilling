<?php

if (cfr('DISTRICTS')) {

    $altCfg = $ubillingConfig->getAlter();

    if ($altCfg['DISTRICTS_ENABLED']) {
        $districts = new Districts();

        //new district creation
        if (wf_CheckPost(array('newdistrictname'))) {
            $districts->createDistrict($_POST['newdistrictname']);
            rcms_redirect($districts::URL_ME);
        }

        //district deletion
        if (wf_CheckGet(array('deletedistrict'))) {
            $districts->deleteDistrict($_GET['deletedistrict']);
            rcms_redirect($districts::URL_ME);
        }

        //saving district name
        if (wf_CheckPost(array('editdistrictid', 'editdistrictname'))) {
            $districts->saveDistrictName($_POST['editdistrictid'], $_POST['editdistrictname']);
            rcms_redirect($districts::URL_ME);
        }


        show_window(__('Districts'), $districts->renderDistrictsList());
        show_window('', $districts->renderDistrictsCreateForm());
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
?>