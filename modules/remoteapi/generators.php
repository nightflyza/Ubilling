<?php

if (ubRouting::get('action') == 'generators') {
    if ($alterconf['GENERATORS_ENABLED']) {
        $generators = new Generators();
        $generatorsWatcherProcess=new StarDust($generators::WATCHER_PID);
        if ($generatorsWatcherProcess->notRunning()) {
            $generatorsWatcherProcess->start();
            $generators->runGeneratorsWatcher();
            $generatorsWatcherProcess->stop();
            die('OK:GENERATORS_WATCHER');
        } else {
            die('SKIP:GENERATORS_WATCHER_RUNNING');
        }
    } else {
        die('ERROR:GENERATORS_DISABLED');
    }
}