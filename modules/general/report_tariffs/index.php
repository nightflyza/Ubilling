<?php

if (cfr('REPORTTARIFFS')) {
    $altCfg = $ubillingConfig->getAlter();
    $chartsCache = new UbillingCache();
    
    show_window(__('Popularity of tariffs among users'), web_TariffShowReport());
    show_window(__('Planned tariff changes'), web_TariffShowMoveReport());
   

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
        
        $tariffCharts = $chartsCache->getCallback('REPORT_TARIFFS_TARIFFHCHART', function () {
            return ( web_TariffShowTariffCharts());
        }, $cachingTime);
        
     
        //rendering charts
        show_window(__('Graphs'), $tariffCharts.  wf_delimiter().$moveCharts);
    }


   
    
} else {
    show_error(__('You cant control this module'));
}
?>
