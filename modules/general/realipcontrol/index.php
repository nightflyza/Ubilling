<?php

if (cfr('REALIPCONTROL')) {
    $realipcontrol = new RealIPControl();

    //settings update
    if (ubRouting::checkPost(array($realipcontrol::PROUTE_GRAYMASK, $realipcontrol::PROUTE_DEBTLIM))) {
        $realipcontrol->saveSettings();
        ubRouting::nav($realipcontrol::URL_ME);
    }

    show_window(__('Settings'), $realipcontrol->renderConfigForm());
    $realipcontrol->renderReport();
} else {
    show_error(__('Access denied'));
}