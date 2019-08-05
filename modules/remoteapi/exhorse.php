<?php

//existential horse
if ($_GET['action'] == 'exhorse') {
    if ($alterconf['EXHORSE_ENABLED']) {
        $exhorse = new ExistentialHorse();
        $exhorse->runHorse();

        die('OK: EXHORSE');
    } else {
        die('ERROR: EXHORSE DISABLED');
    }
}
