<?php

if (ubRouting::get('action') == 'multienvy') {
    if ($alterconf['ENVY_ENABLED'] AND $alterconf['MULTI_ENVY_PROC']) {
        if (ubRouting::checkGet('devid')) {
            $devId = ubRouting::get('devid', 'int');
            $envy = new Envy();
            $envy->procStoreArchiveData($devId);
            die('OK:MULTI_ENVY_PROC');
        } else {
            die('ERROR:NO_DEVID');
        }
    } else {
        die('ERROR:ENVY DISABLED');
    }
}    