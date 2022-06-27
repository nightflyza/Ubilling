<?php

$altCfg = $ubillingConfig->getAlter();
if ($altCfg['TELEPONY_ENABLED']) {

    if (cfr('TELEPONY')) {
        $telePony = new TelePony();

        //rendering module controls
        show_window('', $telePony->renderControls());

        if (ubRouting::checkGet($telePony::ROUTE_INCALLSTATS)) {
            //basic calls stats
            show_window(__('Stats') . ' ' . __('Incoming calls'), $telePony->renderNumLog());
        }

        if ($altCfg['TELEPONY_CDR']) {
            //CDR renderers here
            if (ubRouting::checkGet($telePony::ROUTE_CALLSHIST)) {
                //showing call history form
                show_window(__('Calls history'), $telePony->renderCdrDateForm());

                //downloading raw CDR as array dump
                if (ubRouting::checkGet($telePony::ROUTE_DLOADCDR)) {
                    zb_DownloadFile($telePony::PATH_CDRDEBUG, 'text');
                }

                //getting calls history json data
                if (ubRouting::checkGet($telePony::ROUTE_AJCALLSHIST)) {
                    $telePony->getCDRJson();
                }

                //rendering calls history container here
                show_window(__('TelePony') . ' - ' . __('Calls history'), $telePony->renderCDR());
            }

            if (ubRouting::checkGet($telePony::ROUTE_NIGHTCALLS)) {
                show_window(__('TelePony') . ' - ' . __('Calls during non-business hours'), $telePony->renderNightCalls());
            }
        }

        //no extra routes or extra post data received
        if (sizeof(ubRouting::rawGet()) == 1 AND sizeof(ubRouting::rawPost()) == 1) {
            show_error(__('Strange exception'));
            show_window('', wf_tag('center') . wf_img('skins/teleponywrong.png') . wf_tag('center', true));
        }

        zb_BillingStats(true);
    } else {
        show_error(__('Permission denied'));
    }
} else {
    show_error(__('This module is disabled'));
}

