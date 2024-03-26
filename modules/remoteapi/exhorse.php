<?php

//existential horse
if (ubRouting::get('action') == 'exhorse') {
    if ($alterconf['EXHORSE_ENABLED']) {
        $existentialHorseProcess = new StarDust('EXHORSE');
        if ($existentialHorseProcess->notRunning()) {
            $existentialHorseProcess->start();
            $exhorse = new ExistentialHorse();
            $exhorse->runHorse();
            $existentialHorseProcess->stop();
            die('OK:EXHORSE');
        } else {
            die('SKIP:EXHORSE_ALREADY_RUNNING');
        }
    } else {
        die('ERROR:EXHORSE_DISABLED');
    }
}
