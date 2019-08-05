<?php

/*
 * UKV charge fee processing
 */
if ($_GET['action'] == 'ukvfeeprocessing') {
    if ($alterconf['UKV_ENABLED']) {
        $ukvApiRun = new UkvSystem();
        $ukvFee = $ukvApiRun->feeChargeAll();
        die('OK:UKVFEEPROCESSING:' . $ukvFee);
    } else {
        die('ERROR:NO_UKV_ENABLED');
    }
}

