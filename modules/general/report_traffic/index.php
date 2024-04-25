<?php

if (cfr('REPORTTRAFFIC')) {

    $traffStats = new TraffStats();

    show_window(__('Traffic report'), $traffStats->renderTrafficReport());
    $nasCharts = $traffStats->renderTrafficReportNasCharts();
    if ($nasCharts) {
        show_window(__('Network Access Servers'), $nasCharts);
    }
    zb_BillingStats(true);
} else {
    show_error(__('You cant control this module'));
}
