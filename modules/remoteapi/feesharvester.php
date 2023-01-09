<?php

/*
 * Fees harvester processing
 */
if (ubRouting::get('action') == 'feesharvester') {
    if ($ubillingConfig->getAlterParam('FEES_HARVESTER')) {
        $harvesterProcess = new StarDust('FEESHARVESTER');
        if ($harvesterProcess->notRunning()) {
            if (ubRouting::checkGet('full', false)) {
                $curMonth = false;
            } else {
                $curMonth = true;
            }
            $harvesterProcess->start();
            $fundsFlow = new FundsFlow();
            $harvestedFees = $fundsFlow->harvestFees($curMonth);
            $harvesterProcess->stop();
            die('OK: FEES HARVESTED `' . $harvestedFees . '`');
        } else {
            die('WARNING:FEES_HARVESTER_ALREADY_RUNING');
        }
    } else {
        die('ERROR:FEES_HARVESTER_DISABLED');
    }
}