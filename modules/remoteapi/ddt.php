<?php

/**
 * DDT processing
 */
if (ubRouting::get('action') == 'ddt') {
    if ($alterconf['DDT_ENABLED']) {
        if ($alterconf['DEALWITHIT_ENABLED']) {
            $ddtApiRun = new DoomsDayTariffs();
            $ddtProcess = new StarDust($ddtApiRun::PID);
            if ($ddtProcess->notRunning()) {
                $ddtProcess->start();
                $ddtApiRun->runProcessing();
                $ddtProcess->stop();
                die('OK:DDTPROCESSING');
            } else {
                die('SKIP:DDT_ALREADY_RUNNING');
            }
        } else {
            die('ERROR:NO_DEALWITHIT_ENABLED');
        }
    } else {
        die('ERROR:NO_DDT_ENABLED');
    }
}
