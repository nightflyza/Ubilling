<?php

/*
 * Watchdog tasks processing
 */
if ($_GET['action'] == 'watchdog') {
    if ($alterconf['WATCHDOG_ENABLED']) {
        $runWatchDog = new WatchDog();
        $runWatchDog->processTask();
        die('OK:WATCHDOG');
    } else {
        die('ERROR:NO_WATCHDOG_ENABLED');
    }
}