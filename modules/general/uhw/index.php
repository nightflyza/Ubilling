<?php

if (cfr('UHW')) {

    $uhw = new UHW();

    //module control panel display
    show_window('', $uhw->panel());

    if (!ubRouting::checkGet($uhw::ROUTE_BRUTE_SHOW)) {
        //json reply
        if (ubRouting::checkGet($uhw::ROUTE_AJAX_LOG)) {
            $loginFilter = (ubRouting::checkGet($uhw::ROUTE_LOGIN)) ? ubRouting::get($uhw::ROUTE_LOGIN) : '';
            $uhw->ajaxGetData($loginFilter);
        }

        //list all UHW usages
        $searchLogin = (ubRouting::checkGet($uhw::ROUTE_LOGIN)) ? ubRouting::get($uhw::ROUTE_LOGIN) : '';
        show_window(__('UHW successful log'), $uhw->renderUsageList($searchLogin));
        if (!empty($searchLogin)) {
            show_window('', web_UserControls($searchLogin));
        }
    } else {
        //deleting brute attempt
        if (ubRouting::checkGet($uhw::ROUTE_BRUTE_DEL)) {
            $uhw->deleteBrute(ubRouting::get($uhw::ROUTE_BRUTE_DEL));
            ubRouting::nav($uhw::URL_ME . '&' . $uhw::ROUTE_BRUTE_SHOW . '=true');
        }

        //cleanup of all brutes
        if (ubRouting::checkGet($uhw::ROUTE_BRUTE_FLUSH)) {
            $uhw->flushAllBrute();
            ubRouting::nav($uhw::URL_ME . '&' . $uhw::ROUTE_BRUTE_SHOW . '=true');
        }

        //rendering brute attempts list
        $cleanupUrl = $uhw::URL_ME . '&' . $uhw::ROUTE_BRUTE_SHOW . '=true&' . $uhw::ROUTE_BRUTE_FLUSH . '=true';
        $cancelUrl = $uhw::URL_ME . '&' . $uhw::ROUTE_BRUTE_SHOW . '=true';
        $cleanupLink = wf_ConfirmDialog($cleanupUrl, wf_img('skins/icon_cleanup.png', __('Cleanup')), __('Are you serious'), '', $cancelUrl, __('Cleanup') . '?');
        show_window(__('Brute attempts') . ' ' . $cleanupLink, $uhw->renderBruteAttempts());
    }
} else {
    show_error(__('Permission denied'));
}
