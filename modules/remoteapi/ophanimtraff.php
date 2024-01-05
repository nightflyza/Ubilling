<?php

if (ubRouting::get('action') == 'ophanimtraff') {
    if ($ubillingConfig->getAlterParam(OphanimFlow::OPTION_ENABLED)) {
        $ophTraffPid = OphanimFlow::PID_SYNC;
        $ophanimSyncProcess = new StarDust($ophTraffPid);
        if ($ophanimSyncProcess->notRunning()) {
            $ophanimSyncProcess->start();
            set_time_limit(600);
            $ophTraff = new OphanimFlow();
            $ophTraff->traffDataProcessing();
            $ophanimSyncProcess->stop();
            die('OK:OPHANIMTRAFF_DONE');
        } else {
            die('SKIP:OPHANIMTRAFF_RUNNING');
        }
    } else {
        die('ERROR:OPHANIMTRAFF_DIABLED');
    }
}