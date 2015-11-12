<?php

$altCfg = $ubillingConfig->getAlter();
if ($altCfg['DEALWITHIT_ENABLED']) {
    if (cfr('DEALWITHIT')) {
        $dealWithIt = new DealWithIt();
        show_window(__('Available Held jobs for all users'), $dealWithIt->renderTasksList());
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('This module is disabled'));
}
?>