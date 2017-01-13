<?php

if (cfr('POLICEDOG')) {
    $altCfg = $ubillingConfig->getAlter();
    if ($altCfg['POLICEDOG_ENABLED']) {
        $policedog = new PoliceDog();
        show_window('', $policedog->panel());
        
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
?>