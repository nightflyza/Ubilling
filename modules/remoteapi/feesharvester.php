<?php

/*
 * Fees harvester processing
 */
if (ubRouting::get('action') == 'feesharvester') {
    if ($ubillingConfig->getAlterParam('FEES_HARVESTER')) {
        $harvesterProcess = new StarDust('FEESHARVESTER');
        if ($harvesterProcess->notRunning()) {
            $harvesterProcess->start();
            $fundsFlow = new FundsFlow();
            $harvestedFees = $fundsFlow->harvestFees();
            $harvesterProcess->stop();
            die('OK: FEES HARVESTED `' . $harvestedFees . '`');
        } else {
            die('WARNING:FEES_HARVESTER_ALREADY_RUNING');
        }
    } else {
        die('ERROR:FEES_HARVESTER_DISABLED');
    }
}