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
            $loginFilter = (wf_CheckGet(array('loginfilter'))) ? $_GET['loginfilter'] : '';
            $askMon->jsonCallsList($loginFilter);
        }

        //manual cache cleanup
        if (ubRouting::checkGet('cleantelepathycache')) {
            $telepathy = new Telepathy(false, true, false, true);
            $telepathy->usePhones();
            $telepathy->flushPhoneTelepathyCache();
            rcms_redirect($askMon::URL_ME);
        }

        $windowControls = wf_Link($askMon::URL_ME . '&cleantelepathycache=true', wf_img('skins/icon_cleanup.png', __('Cache cleanup')), false);
        show_window(__('Askozia calls records') . ' ' . $windowControls, $askMon->renderCallsList());

        if (ubRouting::checkGet('username')) {
            //optional profile-return links
            $controlsLinks = wf_BackLink($askMon::URL_PROFILE . $_GET['username']) . ' ';
            $controlsLinks .= wf_Link($askMon::URL_ME, wf_img('skins/done_icon.png') . ' ' . __('All calls'), false, 'ubButton');
            show_window('', $controlsLinks);
        }
    } else {
        show_error(__('Permission denied'));
    }
} else {
    show_error(__('AskoziaPBX integration now disabled'));
}
?>