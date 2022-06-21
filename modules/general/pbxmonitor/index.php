<?php

$altcfg = $ubillingConfig->getAlter();
if ($altcfg['PBXMON_ENABLED']) {
    if (cfr('PBXMON')) {
        set_time_limit(0);
        $pbxMon = new PBXMonitor();

        //catching voice record download
        $pbxMon->catchFileDownload();

        //rendering ajax datatables data
        if (ubRouting::checkGet('ajax')) {
            $loginFilter = (ubRouting::checkGet('loginfilter')) ? ubRouting::get('loginfilter') : '';
            $renderAll = (ubRouting::checkGet('renderall')) ? true : false;
            $pbxMon->jsonCallsList($loginFilter, $renderAll);
        }

        //manual cache cleanup
        if (ubRouting::checkGet('cleantelepathycache')) {
            $telepathy = new Telepathy(false, true, false, true);
            $telepathy->usePhones();
            $telepathy->flushPhoneTelepathyCache();
            ubRouting::nav($pbxMon::URL_ME);
        }

        $windowControls = '';
        if (!ubRouting::checkGet('renderall') AND ! ubRouting::checkGet('username')) {
            $allTime = false;
        } else {
            $allTime = true;
        }
        if (!$allTime) {
            $windowControls .= ' (' . curyear() . ') ';
        } else {
            $windowControls .= ' (' . __('All time') . ') ';
        }

        if (cfr('ROOT')) {
            $windowControls .= wf_Link($pbxMon::URL_ME . '&cleantelepathycache=true', wf_img('skins/icon_cleanup.png', __('Cache cleanup')), false);
        }

        if ($allTime) {
            $windowControls .= ' ' . wf_Link($pbxMon::URL_ME, wf_img('skins/done_icon.png', __('Current year')));
        } else {
            $windowControls .= ' ' . wf_Link($pbxMon::URL_ME . '&renderall=true', wf_img('skins/allcalls.png', __('All time')));
        }

        //renders calls archive
        show_window(__('Telephony calls records') . ' ' . $windowControls, $pbxMon->renderCallsList());

        //user-related controls here
        if (ubRouting::checkGet('username')) {
            //optional profile-return links
            $controlsLinks = wf_BackLink($pbxMon::URL_PROFILE . ubRouting::get('username')) . ' ';
            $controlsLinks .= wf_Link($pbxMon::URL_ME, wf_img('skins/done_icon.png') . ' ' . __('All calls'), false, 'ubButton');
            show_window('', $controlsLinks);
        }

        zb_BillingStats(true);
    } else {
        show_error(__('Permission denied'));
    }
} else {
    show_error(__('This module is disabled'));
}
