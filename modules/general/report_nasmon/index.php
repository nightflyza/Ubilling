<?php

$altCfg = $ubillingConfig->getAlter();
if ($altCfg['NASMON_ENABLED']) {
    if (cfr('NASMON')) {
        $nasMon = new NasMon();
        $nasMonControls = '';
        $refreshAppend = '';
        if (ubRouting::checkGet('callback')) {
            $nasMonControls .= wf_BackLink('?module=nas');
            $refreshAppend .= '&callback=nas';
        }
        $nasMonControls .= wf_Link($nasMon::URL_ME . '&refresh=true' . $refreshAppend, wf_img('skins/refresh.gif') . ' ' . __('Force query'), false, 'ubButton');

        if (ubRouting::checkGet('refresh')) {
            $nasMon->saveCheckResults();
            ubRouting::nav($nasMon::URL_ME . $refreshAppend);
        }
        show_window('', $nasMonControls);
        show_window(__('NAS servers state'), $nasMon->renderList());
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('This module is disabled'));
}
