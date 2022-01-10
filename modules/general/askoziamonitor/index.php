<?php

$altcfg = $ubillingConfig->getAlter();
if ($altcfg['ASKOZIA_ENABLED']) {
    if (cfr('ASKOZIAMON')) {
        set_time_limit(0);
        $askMon = new AskoziaMonitor();

        //catching voice record download
        $askMon->catchFileDownload();

        //rendering ajax datatables data
        if (ubRouting::checkGet('ajax')) {
            $loginFilter = (ubRouting::checkGet('loginfilter')) ? ubRouting::get('loginfilter') : '';
            $renderAll = (ubRouting::checkGet('renderall')) ? true : false;
            $askMon->jsonCallsList($loginFilter, $renderAll);
        }

        //manual cache cleanup
        if (ubRouting::checkGet('cleantelepathycache')) {
            $telepathy = new Telepathy(false, true, false, true);
            $telepathy->usePhones();
            $telepathy->flushPhoneTelepathyCache();
            rcms_redirect($askMon::URL_ME);
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
            $windowControls .= wf_Link($askMon::URL_ME . '&cleantelepathycache=true', wf_img('skins/icon_cleanup.png', __('Cache cleanup')), false);
        }

        if ($allTime) {
            $windowControls .= ' ' . wf_Link($askMon::URL_ME, wf_img('skins/done_icon.png', __('Current year')));
        } else {
            $windowControls .= ' ' . wf_Link($askMon::URL_ME . '&renderall=true', wf_img('skins/allcalls.png', __('All time')));
        }
        show_window(__('Askozia calls records') . ' ' . $windowControls, $askMon->renderCallsList());

        if (ubRouting::checkGet('username')) {
            //optional profile-return links
            $controlsLinks = wf_BackLink($askMon::URL_PROFILE . ubRouting::get('username')) . ' ';
            $controlsLinks .= wf_Link($askMon::URL_ME, wf_img('skins/done_icon.png') . ' ' . __('All calls'), false, 'ubButton');
            show_window('', $controlsLinks);
        }

        zb_BillingStats(true);
    } else {
        show_error(__('Permission denied'));
    }
} else {
    show_error(__('AskoziaPBX integration now disabled'));
}
?>