<?php

if ($ubillingConfig->getAlterParam('PON_SCRIPTS_ENABLED')) {
    if ($ubillingConfig->getAlterParam('SWITCHES_AUTH_ENABLED')) {
        if (cfr('PONSCRIPTS')) {
            set_time_limit(0);
            $ponizer = new PONizer();
            $allOltIps = $ponizer->getAllOltIps();
            $allOltModelIds = $ponizer->getAllOltModelIds();
            $allOltModelsData = $ponizer->getAllModelsData();
            $ponScripts = new PONScripts($allOltIps, $allOltModelIds, $allOltModelsData);

            //some interface script execution?
            if (ubRouting::checkGet($ponScripts::ROUTE_RUN_IFSCRIPT)) {
                if (ubRouting::checkGet(array($ponScripts::ROUTE_RUN_OLTID, $ponScripts::ROUTE_RUN_IFNAME))) {
                    $scriptId = ubRouting::get($ponScripts::ROUTE_RUN_IFSCRIPT, 'vf');
                    $oltId = ubRouting::get($ponScripts::ROUTE_RUN_OLTID, 'int');
                    $ifName = ubRouting::get($ponScripts::ROUTE_RUN_IFNAME, 'mres');
                    show_window(__('Result'), $ponScripts->runIfaceScript($scriptId, $oltId, $ifName));
                    zb_BillingStats();
                } else {
                    show_error(__('Something went wrong') . ': ' . __('Important parameter missed'));
                }
            }
        } else {
            show_error(__('Access denied'));
        }
    } else {
        show_error(__('Device authorization data') . ' ' . __('disabled'));
    }
} else {
    show_error(__('This module is disabled'));
}
