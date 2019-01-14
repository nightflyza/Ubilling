<?php

$altcfg = $ubillingConfig->getAlter();
if ($altcfg['CALLSHIST_ENABLED']) {
    if (cfr('CALLSHIST')) {

        class CallsHist {
            
        }

    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('This module is disabled'));
}
?>