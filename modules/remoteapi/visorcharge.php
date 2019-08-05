<?php

//visor default cameras charge monthly run
if (ubRouting::get('action') == 'visorcharge') {
    if ($alterconf['VISOR_ENABLED']) {
        if (date("d") == date("t")) {
            //last day of month?
            $visor = new UbillingVisor();
            $visor->chargeProcessing();
            die('OK:VISORCHARGE');
        } else {
            die('OK:VISOR_SKIP');
        }
    } else {
        die('ERROR: VISOR DISABLED');
    }
}