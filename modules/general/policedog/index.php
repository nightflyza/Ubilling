<?php

if (cfr('POLICEDOG')) {
    $altCfg = $ubillingConfig->getAlter();
    if ($altCfg['POLICEDOG_ENABLED']) {
        $policedog = new PoliceDog();

     
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
?>