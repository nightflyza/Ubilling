<?php

if (cfr('TASKMANQR')) {
    $altCfg = $ubillingConfig->getAlter();
    if (@$altCfg['TASKRANKS_ENABLED']) {

        $report = new TasksQualRep();
        show_window('', $report->renderControls());

        if (ubRouting::checkGet($report::ROUTE_CALLSRENDER)) {
            show_window(__('Calls'), $report->renderCallsReport());
        } else {
            show_window(__('User rating of tasks completion'), $report->renderRanks());
            show_window(__('Anomalies in the performance of tasks'), $report->renderFails());

            if (@$altCfg['TASKWHATIDO_ENABLED']) {
                show_window(__('What was going on') . '?', $report->renderWhatDone());
            }
        }

        zb_BillingStats(true);
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}