<?php

if (cfr('ROOT')) {
    $pmon = new ProcessMon();
    show_window(__('Background processes'), $pmon->renderControls());

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