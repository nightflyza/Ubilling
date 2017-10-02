<?php

if (cfr('UHW')) {

    $uhw = new UHW();

    //module control panel display
    show_window('', $uhw->panel());

    if (!wf_CheckGet(array('showbrute'))) {
        //json reply
        if (wf_CheckGet(array('ajax'))) {
            $loginFilter = (wf_CheckGet(array('username'))) ? $_GET['username'] : '';
            $uhw->ajaxGetData($loginFilter);
        }
        //list all UHW usage list
        $searchLogin = (wf_CheckGet(array('username'))) ? $_GET['username'] : '';
        show_window(__('UHW successful log'), $uhw->renderUsageList($searchLogin));
        if (!empty($searchLogin)) {
            show_window('', web_UserControls($searchLogin));
        }
    } else {
        //deleting brute
        if (wf_CheckGet(array('delbrute'))) {
            $uhw->deleteBrute($_GET['delbrute']);
            rcms_redirect("?module=uhw&showbrute=true");
        }

        //cleanup of all brutes
        if (wf_CheckGet(array('cleanallbrute'))) {
            $uhw->flushAllBrute();
            rcms_redirect("?module=uhw&showbrute=true");
        }


        $cleanupLink = wf_JSAlert('?module=uhw&showbrute=true&cleanallbrute=true', wf_img('skins/icon_cleanup.png', __('Cleanup')), 'Are you serious');
        show_window(__('Brute attempts') . ' ' . $cleanupLink, $uhw->renderBruteAttempts());
    }
} else {
    show_error(__('You cant control this module'));
}
?>
