<?php

if (cfr('VISOR')) {
    $alterCfg = $ubillingConfig->getAlter();
    if ($alterCfg['VISOR_ENABLED']) {
        if ($alterCfg['SWYMAP_ENABLED']) {
            $cameraMap = new CameraMap();
            $cameraMap->render();
        } else {
            show_window(__('Error'), __('This module is disabled'));
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
