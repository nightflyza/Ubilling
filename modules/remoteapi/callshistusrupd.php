<?php

if (ubRouting::get('action') == 'callshistusrupd') {
    if ($alterconf['CALLSHIST_ENABLED']) {
        $callsHistUpdProcess = new StarDust('CALLSHIST_UPD');
        if ($callsHistUpdProcess->notRunning()) {
            $callsHistUpdProcess->start();
            $callsHistory = new CallsHistory();
            $updateResult = $callsHistory->updateUnknownLogins(true);
            print('GUESSED:' . $updateResult['GUESSED'] . ' ');
            print('MISSED:' . $updateResult['MISSED'] . ' ');
            $callsHistUpdProcess->stop();
            die('OK:CALLSHIST_USERS_UPDATE');
        } else {
            die('ERROR:CALLSHIST_ALREADY_RUNS');
        }
    } else {
        die('ERROR:CALLSHIST_DISABLED');
    }
}
