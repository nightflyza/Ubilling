<?php

// Load MIKROTIK and UBNT Signal data
if ($_GET['action'] == 'mtsigmonpoll') {
    if ($alterconf['MTSIGMON_ENABLED']) {
        $sigmon = new MTsigmon();
        $sigmon->MTDevicesPolling();
        die('OK:MTPOLL');
    } else {
        die('ERROR:MTSIGMON_DISABLED');
    }
}

              