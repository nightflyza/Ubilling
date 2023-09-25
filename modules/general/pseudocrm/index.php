<?php

if (cfr('PSEUDOCRM')) {
    $altCfg = $ubillingConfig->getAlter();
    if ($altCfg['PSEUDOCRM_ENABLED']) {
        $crm = new PseudoCRM();
        debarr($crm);
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}