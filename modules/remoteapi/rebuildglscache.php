<?php

/**
 * GlobalSearch cache rebuild
 */
if (ubRouting::get('action') == 'rebuildglscache') {
    if (!$ubillingConfig->getAlterParam('SPHINX_SEARCH_ENABLED')) {
        $rebuildProcess = new StarDust('GLSREBUILD');
        if ($rebuildProcess->notRunning()) {
            $rebuildProcess->start();
            $globalSearch = new GlobalSearch();
            $globalSearch->ajaxCallback(true);
            $rebuildProcess->stop();
            die('OK:REBUILDGLSCACHE');
        } else {
            die('SKIPPED:ALREADY_RUNNING');
        }
    } else {
        die('SKIPPED:SPHINX_SEARCH_ENABLED');
    }
}
