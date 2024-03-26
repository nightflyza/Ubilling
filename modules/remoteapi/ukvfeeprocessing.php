<?php

/*
 * UKV charge fee processing
 */
if (ubRouting::get('action')  == 'ukvfeeprocessing') {
    if ($alterconf['UKV_ENABLED']) {
        $ukvApiRun = new UkvSystem();
        $ukvFee = $ukvApiRun->feeChargeAll();
        die('OK:UKVFEEPROCESSING:' . $ukvFee);
    } else {
        die('ERROR:NO_UKV_ENABLED');
    }
}

