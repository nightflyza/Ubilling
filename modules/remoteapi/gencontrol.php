<?php

if (ubRouting::get('action') == 'gencontrol') {
    if ($alterconf['GENERATORS_ENABLED']) {
        $generators = new Generators();
        
        if (ubRouting::checkGet('start')) {
           $generatorId=ubRouting::get('start','int');
           $startResult=$generators->startDevice($generatorId);
           if ($startResult) {
            die('ERROR: START_FAILED '.$startResult);
           } else {
            die('OK: START_SUCCESS');
           }
        }

        if (ubRouting::checkGet('stop')) {
           $generatorId=ubRouting::get('stop','int');
           $stopResult=$generators->stopDevice($generatorId);
           if ($stopResult) {
            die('ERROR: STOP_FAILED '.$stopResult);
           } else {
            die('OK: STOP_SUCCESS');
           }
        }

        die('ERROR: UNKNOWN_ACTION');

    } else {
        die('ERROR:GENERATORS_DISABLED');
    }
}