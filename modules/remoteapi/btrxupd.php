<?php

/**
 * Bitrix24 userbase updates 
 */
if (ubRouting::get('action') == 'btrxupd') {
    if ($ubillingConfig->getAlterParam('BTRX24_ENABLED')) {
        $updateProcess = new StarDust(BtrxCRM::PID_NAME);
        if ($updateProcess->notRunning()) {
            $updateProcess->start();
            $crm = new BtrxCRM();
            $crm->runExport();
            $updateProcess->stop();
            die('OK:BTRXUPD');
        } else {
            die('SKIPPED:BTRXUPD_ALREADY_RUNNING');
        }
    } else {
        die('ERROR:BTRX24_DISABLED');
    }
}
