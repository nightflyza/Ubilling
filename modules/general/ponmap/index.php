<?php

$altCfg = $ubillingConfig->getAlter();

if (@$altCfg['PONMAP_ENABLED']) {
    if ($altCfg['PON_ENABLED']) {
        if (cfr('PON')) {
            $ponMap = new PONONUMAP();
            show_window(__('ONU Map'), $ponMap->renderOnu());
        } else {
            show_error(__('Access denied'));
        }
    } else {
        show_error(__('This module is disabled') . '. ' . __('You missed an important option') . ': PON_ENABLED');
    }
} else {
    show_error(__('This module is disabled'));
}