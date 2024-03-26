<?php

/*
 * PoliceDog processing
 */
if (ubRouting::get('action') == 'policedog') {
    if ($alterconf['POLICEDOG_ENABLED']) {
        $runPoliceDog = new PoliceDog();
        $runPoliceDog->fastScan();
        die('OK:POLICEDOG');
    } else {
        die('ERROR:POLICEDOG_DISABLED');
    }
}

