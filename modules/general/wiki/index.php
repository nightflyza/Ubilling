<?php

if (cfr('TASKBAR')) {
    $altCfg = $ubillingConfig->getAlter();
    if (@$altCfg['WIKI_URL']) {
        $wikiUrl = $altCfg['WIKI_URL'];
        if (!empty($wikiUrl)) {
            if (ispos($wikiUrl, '?blank')) {
                $wikiUrl = str_replace('?blank', '', $wikiUrl);
                $backUrl = (@$_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
                $redirect = wf_tag('script');
                $redirect .= 'window.open("' . $wikiUrl . '", "_blank");';
                if ($backUrl) {
                    $redirect .= 'window.open("' . $backUrl . '", "_self");';
                }
                $redirect .= wf_tag('script', true);
                show_window(__('Redirecting'), $redirect);
            } else {
                ubRouting::nav($wikiUrl);
            }
        } else {
            show_error('WIKI_URL ' . __('is empty'));
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
