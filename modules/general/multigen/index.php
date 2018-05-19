<?php

if (cfr('MULTIGEN')) {
    $altCfg = $ubillingConfig->getAlter();
    if ($altCfg['MULTIGEN_ENABLED']) {
        
        $mg = new MultiGen();
        debarr($mg);
        
        
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
?>