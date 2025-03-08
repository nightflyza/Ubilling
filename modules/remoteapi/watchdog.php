<?php

/*
 * Watchdog tasks processing
 */
if (ubRouting::get('action')  == 'watchdog') {
    if ($alterconf['WATCHDOG_ENABLED']) {
        $runWatchDog = new WatchDog();
        $runWatchDog->processTask();
        die('OK:WATCHDOG');
    } else {
        die('ERROR:NO_WATCHDOG_ENABLED');
    }
}
