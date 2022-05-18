<?php

if (cfr('TASKBAR')) {
    if ($ubillingConfig->getAlterParam('AERIAL_ALERTS_ENABLED')) {
        $aerialAlerts = new AerialAlerts();
        show_window('', $aerialAlerts->renderControls());
        if (ubRouting::checkGet($aerialAlerts::ROUTE_MAP)) {
            //alerts map
            show_window(__('Alerts map'), $aerialAlerts->renderMap());
        } else {
            //just report here
            show_window(__('Aerial alerts'), $aerialAlerts->renderReport());
        }
        zb_BillingStats(true);
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}