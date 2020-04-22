<?php

if (cfr('TASKBAR')) {
    $altCfg = $ubillingConfig->getAlter();
    if (@$altCfg['WIKI_URL']) {
        $wikiUrl = $altCfg['WIKI_URL'];
        if (!empty($wikiUrl)) {
            rcms_redirect($wikiUrl);
        } else {
            show_error('WIKI_URL ' . __('is empty'));
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}