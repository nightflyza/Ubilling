<?php

/*
 * Crime And Punishment processing
 */
if (ubRouting::get('action') == 'crimeandpunishment') {
    if ($alterconf['CAP_ENABLED']) {
        $dostoevsky = new CrimeAndPunishment();
        $dostoevsky->processing();
        die('OK:CRIMEANDPUNISHMENT');
    } else {
        die('ERROR:CRIMEANDPUNISHMENT_DISABLED');
    }
}