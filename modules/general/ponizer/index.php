<?php

$altCfg = $ubillingConfig->getAlter();

if ($altCfg['PON_ENABLED']) {
    if (cfr('PON')) {
        
        $pon=new PONizer();
        deb($pon->controls());

    } else {
        show_error(__('You cant control this module'));
    }
} else {
    show_error(__('This module disabled'));
}
?>