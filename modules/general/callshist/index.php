<?php

$altcfg = $ubillingConfig->getAlter();
if ($altcfg['CALLSHIST_ENABLED']) {
    if (cfr('CALLSHIST')) {
        $report = new CallsHistory();

        if (wf_CheckGet(array('username'))) {
            //setting some login filtering if required
            $report->setLogin($_GET['username']);
        }

        //rendering report json data
        if (wf_CheckGet(array('ajaxcalls'))) {
            $report->renderCallsAjaxList();
        }

        //rendering report container
        if (!wf_CheckGet(array('updateusers'))) {
            if (cfr('ROOT')) {
                $updateControls = ' ' . wf_Link($report::URL_ME . '&updateusers=true', wf_img('skins/refresh.gif', __('User calls assign update')));
            } else {
                $updateControls = '';
            }
            show_window(__('Calls history') . $updateControls, $report->renderCalls());
        } else {
            //user logins telepathy update
            if (cfr('ROOT')) {
                show_window(__('User calls assign update'), $report->updateUnknownLogins());
                show_window('', wf_BackLink($report::URL_ME));
            } else {
                show_error(__('Access denied'));
            }
        }
        if (wf_CheckGet(array('username'))) {
            //optional profile-return links
            $controlsLinks = wf_BackLink($report::URL_PROFILE . $_GET['username']) . ' ';
            $controlsLinks .= wf_Link($report::URL_ME, wf_img('skins/done_icon.png') . ' ' . __('All calls'), false, 'ubButton');
            show_window('', $controlsLinks);
        }
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('This module is disabled'));
}