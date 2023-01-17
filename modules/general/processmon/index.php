<?php

if (cfr('ROOT')) {
    $pmon = new ProcessMon();
    show_window(__('Background processes'), $pmon->renderControls());

    if (ubRouting::checkGet($pmon::ROUTE_STOP)) {
        $brutalityFlag = (ubRouting::checkGet($pmon::ROUTE_BRUTAL)) ? true : false;
        $processShutdownResult = $pmon->stopProcess(ubRouting::get($pmon::ROUTE_STOP), $brutalityFlag);
        if (empty($processShutdownResult)) {
            sleep(1);
            ubRouting::nav($pmon::URL_ME);
        } else {
            show_error($processShutdownResult);
        }
    }

    if (ubRouting::checkGet($pmon::ROUTE_ZENMODE)) {
        $zen = new ZenFlow('ajprocmon', $pmon->renderProcessList(), $pmon::ZEN_TIMEOUT);
        show_window(__('Zen') . ' ' . __('Process'), $zen->render());
    } else {
        show_window('', $pmon->renderProcessList());
        zb_BillingStats(true);
    }
} else {
    show_error(__('Access denied'));
}