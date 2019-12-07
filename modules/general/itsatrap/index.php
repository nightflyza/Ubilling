<?php

$altCfg = $ubillingConfig->getAlter();
if ($altCfg['ITSATRAP_ENABLED']) {
    if (cfr('ITSATRAP')) {
        
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('This module is disabled'));
}

