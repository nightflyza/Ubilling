<?php

/**
 * DDT processing
 */
if ($_GET['action'] == 'ddt') {
    if ($alterconf['DDT_ENABLED']) {
        if ($alterconf['DEALWITHIT_ENABLED']) {
            $ddtApiRun = new DoomsDayTariffs();
            $ddtApiRun->runProcessing();
            die('OK:DDTPROCESSING:');
        } else {
            die('ERROR:NO_DEALWITHIT_ENABLED');
        }
    } else {
        die('ERROR:NO_DDT_ENABLED');
    }
}


             