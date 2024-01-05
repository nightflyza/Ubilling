<?php

//NAS monitoring periodic polling
if (ubRouting::get('action') == 'nasmon') {
    if ($alterconf['NASMON_ENABLED']) {
        $nasMonProcess = new StarDust('NASMON');
        if ($nasMonProcess->notRunning()) {
            $nasMonProcess->start();
            $nasMon = new NasMon();
            $nasMon->saveCheckResults();
            $nasMonProcess->stop();
            die('OK: NASMON');
        } else {
            die('SKIP:NASMON_ALREADY_RUNNING');
        }
    } else {
        die('ERROR: NASMON DISABLED');
    }
}

        