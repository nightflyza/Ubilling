<?php

/*
 * GlobalSearch cache rebuild
 */
if (ubRouting::get('action') == 'rebuildglscache') {
    if (!$ubillingConfig->getAlterParam('SPHINX_SEARCH_ENABLED')) {
        $globalSearch = new GlobalSearch();
        $globalSearch->ajaxCallback(true);
        die('OK:REBUILDGLSCACHE');
    } else {
        die('SKIPPED:SPHINX_SEARCH_ENABLED');
    }
}
