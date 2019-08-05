<?php

/*
 * PoliceDog processing
 */
if ($_GET['action'] == 'policedog') {
    if ($alterconf['POLICEDOG_ENABLED']) {
        $runPoliceDog = new PoliceDog();
        $runPoliceDog->fastScan();
        die('OK:POLICEDOG');
    } else {
        die('ERROR:POLICEDOG_DISABLED');
    }
}

