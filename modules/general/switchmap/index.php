<?php
if (cfr('SWITCHMAP')) {
    $altercfg =  $ubillingConfig->getAlter();
    if ($altercfg['SWYMAP_ENABLED']) {
        $switchMap = new SwitchMap();
        $switchMap->saveSwitchPlacement();
        $switchMap->render();
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
