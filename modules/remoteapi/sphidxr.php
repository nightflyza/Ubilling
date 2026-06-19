<?php

/**
 * Sphinx indexer callback
 */
if (ubRouting::get('action') == 'sphidxr') {
    if ($ubillingConfig->getAlterParam('SPHINX_SEARCH_ENABLED')) {
        $indexerProcess = new StarDust('SPHINXINDEXER');
        if ($indexerProcess->notRunning()) {
            $indexerProcess->start();
            $billingConfig=$ubillingConfig->getBilling();
            $sudoPath=$billingConfig['SUDO'];
            $command = $sudoPath . ' /opt/sphinx/bin/indexer --config /opt/sphinx/etc/sphinx.conf --all --rotate';
            $indexingResult=shell_exec($command);
            $indexerProcess->stop();
            die('OK:SPHINXINDEXER_DONE');
        } else {
            die('SKIPPED:ALREADY_RUNNING');
        }
    } else {
        die('SKIPPED:SPHINX_SEARCH_DISABLED');
    }
}