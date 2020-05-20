<?php

if (ubRouting::get('action') == 'pt') {

    if ($ubillingConfig->getAlterParam('PT_ENABLED')) {
        $pt=new PowerTariffs();
        $pt->registerNewUsers();
        $pt->processingFee();
    } else {
        die('ERROR: PT_DISABLED');
    }
}