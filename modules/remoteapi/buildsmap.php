<?php

//periodic builds map cache update
if (ubRouting::get('action') == 'buildsmap') {
    if ($alterconf['SWYMAP_ENABLED']) {
        $buildsMapProcess=new StarDust('BUILDSMAP');
        if ($buildsMapProcess->notRunning()) {
            $buildsMapProcess->start();
            $buildsMap = new BuildsMap();
            $buildsMap->drawBuilds();
            $buildsMapProcess->stop();
            die('OK:BUILDSMAP');
        } else {
            die('SKIP:BUILDSMAP_ALREADY_RUNNING');
        }
    } else {
        die('ERROR: SWYMAP DISABLED');
    }
}