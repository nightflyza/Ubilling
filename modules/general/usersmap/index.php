<?php

if (cfr('USERSMAP')) {
    $altercfg = $ubillingConfig->getAlter();
    if ($altercfg['SWYMAP_ENABLED']) {
        set_time_limit(0);
        $buildsMap = new BuildsMap();
        $buildsMap->saveBuildPlacement();

        //AJAX build data from cache
        if (ubRouting::checkGet('getbuildusers')) {
            die($buildsMap->getBuildData(ubRouting::get('getbuildusers')));
        }
        $buildsMap->render();
    } else {
        show_window(__('Error'), __('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
