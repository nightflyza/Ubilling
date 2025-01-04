<?php

/**
 * Polling PON OLT data
 */
if (ubRouting::get('action') == 'oltpoll') {
    if ($alterconf['PON_ENABLED']) {
        $compressorProcess = new StarDust(ONUSigCompressor::PID);
        if ($compressorProcess->notRunning()) {
            $pony = new PONizer();
            $pony->oltDevicesPolling();
            die('OK:OLTPOLL');
        } else {
            log_register('PON OLTPOLL SKIPPED DUE COMPRESSOR RUNNING');
            die('SKIPPED:OLTPOLL');
        }
    } else {
        die('ERROR:PON_DISABLED');
    }
}
