<?php

if (ubRouting::get('action') == 'opprocessing') {
    if ($ubillingConfig->getAlterParam('OPENPAYZ_SUPPORT')) {
        if ($ubillingConfig->getAlterParam('OPENPAYZ_HIGHLOAD_ENABLE')) {
            $openPayz = new OpenPayz(false, true);
            $opProcessingResult = $openPayz->transactionsProcessingAll();
            if ($opProcessingResult !== false) {
                die('OK:TRANSACTIONS_PROCESSED ' . $opProcessingResult);
            } else {
                die('SKIP:OPPROCESSING_RUNNING');
            }
        } else {
            die('ERROR:OPENPAYZ_HIGHLOAD_DISABLED');
        }
    } else {
        die('ERROR:OPENPAYZ_DISABLED');
    }
}
