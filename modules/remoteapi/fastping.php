<?php

if (ubRouting::get('action') == 'fastping') {
    if (@$alterconf['FASTPING_ENABLED']) {
        $fastPing = new FastPing();
        $fastPing->repingDevices();
        die('OK:FASTPING');
    } else {
        die('ERROR:FASTPING_DISABLED');
    }
}