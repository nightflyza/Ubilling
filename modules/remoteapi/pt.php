<?php

if (ubRouting::get('action') == 'pt') {

    if ($ubillingConfig->getAlterParam('PT_ENABLED')) {
        $pt = new PowerTariffs();
        $pt->registerNewUsers();
        $pt->processingFee();
        die('OK: PT_ROCESSING');
    } else {
        die('ERROR: PT_DISABLED');
    }
}