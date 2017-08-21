<?php

$altCfg = $ubillingConfig->getAlter();
if ($altCfg['NASMON_ENABLED']) {
    if (cfr('NASMON')) {
        $nasMon = new NasMon();
        $nasMonControls = wf_Link($nasMon::URL_ME . '&refresh=true', wf_img('skins/refresh.gif') . ' ' . __('Force query'), false, 'ubButton');
        if (wf_CheckGet(array('refresh'))) {
            $nasMon->saveCheckResults();
            rcms_redirect($nasMon::URL_ME);
        }
        show_window('', $nasMonControls);
        show_window(__('NAS servers state'), $nasMon->renderList());
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('This module is disabled'));
}
?>