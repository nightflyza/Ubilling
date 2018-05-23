<?php

if (cfr('DISTRICTS')) {

    $altCfg = $ubillingConfig->getAlter();

    if ($altCfg['DISTRICTS_ENABLED']) {
        $districts = new Districts(true);

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


        if (!wf_CheckGet(array('editdistrict'))) {
            //main interface here
            show_window(__('Districts'), $districts->renderDistrictsList());
            show_window('', $districts->renderDistrictsCreateForm());
        } else {
            if (wf_CheckGet(array('editdistrict'))) {
                //creating new district data
                if (wf_CheckPost(array('citysel', 'allchoicesdone'))) {
                    $districts->catchDistrictDataCreate();
                    rcms_redirect($districts::URL_ME . '&editdistrict=' . $_GET['editdistrict']);
                }
                //deleting some data row
                if (wf_CheckGet(array('deletedata'))) {
                    $districts->deleteDistrictData($_GET['deletedata']);
                    rcms_redirect($districts::URL_ME . '&editdistrict=' . $_GET['editdistrict']);
                }
                //render create form
                show_window('', wf_BackLink($districts::URL_ME));
                show_window(__('District') . ': ' . $districts->getDistrictName($_GET['editdistrict']), $districts->renderDistrictDataCreateForm($_GET['editdistrict']));
                show_window(__('Places'), $districts->renderDistrictData($_GET['editdistrict']));
            }
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
?>