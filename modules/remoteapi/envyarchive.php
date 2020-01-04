<?php

//extracting and storing envy-devices configs into database
if (ubRouting::get('action') == 'envyarchive') {
    if ($alterconf['ENVY_ENABLED']) {
        $envy = new Envy();
        $envy->storeArchiveAllDevices();
        die('OK:ENVYARCHIVE');
    } else {
        die('ERROR:ENVY DISABLED');
    }
}