<?php

if (cfr('ROOT')) {
    $dbmon = new DBmon();
    show_window('', $dbmon->renderControls());

    if (ubRouting::checkGet($dbmon::ROUTE_ZEN)) {
        $zen = new ZenFlow('ajdbmon', $dbmon->renderReport(), $dbmon->getTimeout());
        show_window(__('Zen') . ' ' . __('Database monitor'), $zen->render());
    } else {
        show_window(__('Database monitor'), $dbmon->renderReport());
        zb_BillingStats(true);
    }
} else {
    show_error(__('Access denied'));
}