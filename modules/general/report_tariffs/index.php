<?php

if (cfr('REPORTTARIFFS')) {
    $altCfg = $ubillingConfig->getAlter();
    $chartsCache = new UbillingCache();

    if (!isset($altCfg['GCHARTS_ENABLED'])) {
        $chartsEnabled = true;
    } else {
        if ($altCfg['GCHARTS_ENABLED']) {
            $chartsEnabled = true;
        } else {
            $chartsEnabled = false;
        }
    }

    //google charts
    if ($chartsEnabled) {
        $cachingTime = 3600;
        $moveCharts = $chartsCache->getCallback('REPORT_TARIFFS_MOVECHART', function () {
            return ( web_TariffShowMoveCharts());
        }, $cachingTime);
        $chartsControlMove = ' ' . wf_modalAuto(wf_img('skins/icon_stats.gif', __('Graphs')), __('Planned tariff changes'), $moveCharts);

        $tariffCharts = $chartsCache->getCallback('REPORT_TARIFFS_TARIFFHCHART', function () {
            return ( web_TariffShowTariffCharts());
        }, $cachingTime);

        $chartsControlTariffs = ' ' . wf_modalAuto(wf_img('skins/icon_stats.gif', __('Graphs')), __('Popularity of tariffs among users'), $tariffCharts);
    } else {
        $chartsControlMove = '';
        $chartsControlTariffs = '';
    }




    show_window(__('Popularity of tariffs among users') . $chartsControlTariffs, web_TariffShowReport());
    show_window(__('Planned tariff changes') . $chartsControlMove, web_TariffShowMoveReport());
} else {
    show_error(__('You cant control this module'));
}
?>
