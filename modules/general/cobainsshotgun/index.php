<?php

if (cfr('SHOTGUN')) {
    $shotgunEnabled = $ubillingConfig->getAlterParam('COBAINS_SHOTGUN');
    if ($shotgunEnabled) {
        $shotgun = new CobainsShotgun();

        // Show primary module controls
        show_window('', $shotgun->renderControls());
        if (ubRouting::checkGet($shotgun::ROUTE_ZEN)) {
            $shotgunZen = new ZenFlow('zncobainshotgun', $shotgun->renderReportZen());
            show_window(__('Zen'), $shotgunZen->render());
        } else {
            show_window(__('Cobains shotgun'), $shotgun->renderReport());
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
