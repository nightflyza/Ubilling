<?php

if (cfr('DISTRICTS')) {

    $altCfg = $ubillingConfig->getAlter();

    if ($altCfg['DISTRICTS_ENABLED']) {
        $districts=new Districts();
        
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
?>