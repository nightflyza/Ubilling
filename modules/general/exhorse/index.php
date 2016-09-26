<?php

$altCfg=$ubillingConfig->getAlter();
if ($altCfg['EXHORSE_ENABLED']) {
    if (cfr('EXHORSE')) {
        
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('This module is disabled'));
}

?>