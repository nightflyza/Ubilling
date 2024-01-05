<?php

/*
 * Fees harvester processing
 */
if (ubRouting::get('action') == 'feesharvester') {
    if ($ubillingConfig->getAlterParam('FEES_HARVESTER')) {
        $harvesterProcess = new StarDust('FEESHARVESTER');
        if ($harvesterProcess->notRunning()) {
            if (ubRouting::checkGet('full', false)) {
                $customMonth = '';
            } else {
                $customMonth = curmonth();
            }

            if (ubRouting::checkGet('today', false)) {
                $customMonth = curdate();
            }

            $harvesterProcess->start();
            $fundsFlow = new FundsFlow();
            $harvestedFees = $fundsFlow->harvestFees($customMonth);
            $harvesterProcess->stop();
            die('OK: FEES HARVESTED `' . $harvestedFees . '`');
        } else {
            die('WARNING:FEES_HARVESTER_ALREADY_RUNING');
        }
    } else {
        die('ERROR:FEES_HARVESTER_DISABLED');
    }
}