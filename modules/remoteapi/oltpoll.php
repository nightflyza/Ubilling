<?php

/**
 * Polling PON OLT data
 */
if (ubRouting::get('action') == 'oltpoll') {
    if ($alterconf['PON_ENABLED']) {
        $pony = new PONizer();
        $pony->oltDevicesPolling();
        die('OK:OLTPOLL');
    } else {
        die('ERROR:PON_DISABLED');
    }
}