<?php

if (ubRouting::get('action')  == 'pbxmonrefill') {
    if ($alterconf['PBXMON_ENABLED']) {
        $pbxMon = new PBXMonitor();
        $telepathy = new Telepathy(false, true, false, true);
        $telepathy->usePhones();
        $telepathy->flushPhoneTelepathyCache();
        $pbxMon->refillCache();
        die('OK:PBXMONREFILL');
    } else {
        die('ERROR:PBXMON_DISABLED');
    }
}
