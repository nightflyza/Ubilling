<?php

/**
 * Bitrix24 polls updates handler
 */
if (ubRouting::get('action') == 'btrxpolls') {
    if ($ubillingConfig->getAlterParam('BTRX24_ENABLED')) {
        $exportProcess = new StarDust(PollsExport::EXPORT_PID);
        if ($exportProcess->notRunning()) {
            $exportProcess->start();
            $pollsExport = new PollsExport();
            $pollsExport->runExport();
            $exportProcess->stop();
            die('OK:BTRXPOLLS');
        } else {
            die('SKIPPED:BTRXPOLLS_ALREADY_RUNNING');
        }
    } else {
        die('ERROR:BTRX24_DISABLED');
    }
}
