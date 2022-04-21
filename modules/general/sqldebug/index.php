<?php

if (cfr('ROOT')) {
    if (SQL_DEBUG) {
        $controls = '';
        $result = '';
        $backUrl = '';
        $baseModuleUrl = '?module=sqldebug';
        if (ubRouting::checkGet('back')) {
            $backUrl = '&back=' . ubRouting::get('back');
            $controls .= wf_BackLink(base64_decode(ubRouting::get('back')));
        }

        if (ubRouting::checkGet('fushsqldebuglog')) {
            zb_SqlDebugLogFlush();
            ubRouting::nav($baseModuleUrl . $backUrl);
        }

        $controls .= wf_Link($baseModuleUrl . $backUrl, wf_img('skins/log_icon_small.png') . ' ' . __('All SQL queries log'), false, 'ubButton');
        $controls .= wf_Link($baseModuleUrl . '&zenmode=true' . $backUrl, wf_img('skins/zen.png') . ' ' . __('SQL') . ' ' . __('Zen'), false, 'ubButton');
        $logFlushUrl = $baseModuleUrl . '&fushsqldebuglog=true' . $backUrl;
        $cancelUrl = $baseModuleUrl . $backUrl;
        $controls .= wf_ConfirmDialog($logFlushUrl, wf_img('skins/icon_cleanup.png') . ' ' . __('Flush log'), __('Are you serious'), 'ubButton', $cancelUrl, __('Flush log'));


        show_window(__('All SQL queries log'), $controls);

        if (!ubRouting::checkGet('zenmode')) {
            show_window('', web_SqlDebugLogParse());
        } else {
            $sqlDebugZen = new ZenFlow('sqldebugzenflow', web_SqlDebugLogParse(), 1000);
            show_window('', $sqlDebugZen->render());
        }
    } else {
        show_error(__('SQL queries debug') . ': ' . __('Disabled'));
    }
} else {
    show_error(__('Access denied'));
}