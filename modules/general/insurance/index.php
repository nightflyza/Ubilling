<?php

if (cfr('INSURANCE')) {
    $insuranceEnabled = $ubillingConfig->getAlterParam('INSURANCE_ENABLED');
    if ($insuranceEnabled) {
        
    } else {
        show_error(__('This module is disabled'));
    }
}
