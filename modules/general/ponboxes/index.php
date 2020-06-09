<?php

if (cfr('PON')) {
    $altCfg = $ubillingConfig->getAlter();
    if ($altCfg['PONBOXES_ENABLED']) {
        $boxes = new PONBoxes();
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
    