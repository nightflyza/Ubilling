<?php

if (cfr('TASKBAR')) {
    $altCfg = $ubillingConfig->getAlter();
    if (@$altCfg['AERIAL_ALERTS_ENABLED']) {
        $aerialAlerts = new AerialAlerts();
        show_window('', $aerialAlerts->renderControls());
        show_window(__('Aerial alerts'), $aerialAlerts->renderReport());
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}