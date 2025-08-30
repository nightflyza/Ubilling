<?php

if (ubRouting::get('action') == 'frost' or ubRouting::get('action') == 'defrost') {
    if ($ubillingConfig->getAlterParam(FrostRay::OPTION_ENABLED)) {
        $ip = ubRouting::get('ip');
        if ($ip) {
            $frayPid = FrostRay::PROCESS_PID;
            $frayProcess = new StarDust($frayPid);
            if ($frayProcess->notRunning()) {
                $frayProcess->start();
                $frostRay = new FrostRay($ip);
                if (ubRouting::get('action') == 'frost') {
                    $frostRay->frost();
                }

                if (ubRouting::get('action') == 'defrost') {
                    $frostRay->defrost();
                }

                $frayProcess->stop();
                die('OK:FROSTRAY_DONE');
            } else {
                die('SKIP:FROSTRAY_RUNNING');
            }
        } else {
            die('ERROR:FROSTRAY_NO_IP');
        }
    } else {
        die('ERROR:FROSTRAY_DISABLED');
    }
}
