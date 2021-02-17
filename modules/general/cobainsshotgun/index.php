<?php

if (cfr('SHOTGUN')) {
    $shotgunEnabled = $ubillingConfig->getAlterParam('COBAINS_SHOTGUN');
    if ($shotgunEnabled) {
        $shotgun = new CobainsShotgun();
        show_window(__('Cobains shotgun'),$shotgun->renderReport());
        
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
