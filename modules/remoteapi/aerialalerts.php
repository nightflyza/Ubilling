<?php

if (ubRouting::get('action') == 'aerialalerts') {
    if (@$alterconf['AERIAL_ALERTS_ENABLED']) {
        if (@$alterconf['AERIAL_ALERTS_NOTIFY']) {
            $aerialAlerts = new AerialAlerts();
            die($aerialAlerts->usCallback($alterconf['AERIAL_ALERTS_NOTIFY']));
        } else {
            die('ERROR: AERIAL_ALERTS_NOTIFY_EMPTY');
        }
    } else {
        die('ERROR: AERIAL_ALERTS_DISABLED');
    }
}