<?php

$altcfg = $ubillingConfig->getAlter();
if ($altcfg['CALLSHIST_ENABLED']) {
    if (cfr('CALLSHIST')) {
        $report = new CallsHistory();

        if (ubRouting::checkGet('username')) {
            //setting some login filtering if required
            $report->setLogin(ubRouting::get('username'));
        }

        //rendering report json data
        if (ubRouting::checkGet('ajaxcalls')) {
            $report->renderCallsAjaxList();
        }

        //rendering report container
        if (!ubRouting::checkGet('updateusers')) {
            if (cfr('ROOT')) {
                $updateControls = ' ' . wf_Link($report::URL_ME . '&updateusers=true', wf_img('skins/refresh.gif', __('User calls assign update')));
            } else {
                $updateControls = '';
            }
            show_window(__('Calls history') . $updateControls, $report->renderCalls());
        } else {
            //user logins telepathy update
            if (cfr('ROOT')) {
                $callsHistUpdProcess = new StarDust('CALLSHIST_UPD');
                if ($callsHistUpdProcess->notRunning()) {
                    $callsHistUpdProcess->start();
                    show_window(__('User calls assign update'), $report->updateUnknownLogins());
                    $callsHistUpdProcess->stop();
                } else {
                    show_error(__('User calls assign update') . ' ' . __('Already running'));
                }
                show_window('', wf_BackLink($report::URL_ME));
            } else {
                show_error(__('Access denied'));
            }
        }
        if (ubRouting::checkGet('username')) {
            //optional profile-return links
            $controlsLinks = wf_BackLink($report::URL_PROFILE . ubRouting::get('username')) . ' ';
            $controlsLinks .= wf_Link($report::URL_ME, wf_img('skins/done_icon.png') . ' ' . __('All calls'), false, 'ubButton');
            show_window('', $controlsLinks);
        }
        zb_BillingStats(true);
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('This module is disabled'));
}
