<?php

$altcfg = $ubillingConfig->getAlter();
if ($altcfg['ASKOZIA_ENABLED']) {
    if (cfr('WHYDOYOUCALL')) {

        $whydoyoucall = new WhyDoYouCall();
        show_window('', $whydoyoucall->panel());
        if (!ubRouting::checkGet('renderstats') AND ! ubRouting::checkGet('nightmode')) {
            show_window(__('Missed calls that require your response'), $whydoyoucall->renderMissedCallsReport());
            show_window(__('We tried to call back these numbers, and sometimes it even happened'), $whydoyoucall->renderRecalledCallsReport());
        } else {
            //editing stats
            if (ubRouting::checkPost('editwdycstatsid')) {
                if (cfr('ROOT')) {
                    $whydoyoucall->saveEditedStats();
                    ubRouting::nav($whydoyoucall::URL_ME . '&renderstats=true');
                } else {
                    show_error('Access denied');
                }
            }

            //rendering stats
            if (ubRouting::checkGet('renderstats')) {
                if (ubRouting::checkGet(array('ajaxlist', 'year', 'month'))) {
                    $whydoyoucall->jsonPreviousStats(ubRouting::get('year'), ubRouting::get('month'));
                }
                show_window(__('Stats'), $whydoyoucall->renderStats());
            }
            //rendering night-mode calls
            if (ubRouting::checkGet('nightmode')) {
                show_window(__('Calls during non-business hours'), $whydoyoucall->renderNightModeCalls());
            }
        }
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('This module is disabled'));
}