<?php

$altCfg = $ubillingConfig->getAlter();
if ($altCfg['DEALWITHIT_ENABLED']) {
    if (cfr('DEALWITHIT')) {
        $dealWithIt = new DealWithIt();
        $controls = wf_Link('?module=report_dealwithit', wf_img('skins/dealwithitsmall.png') . ' ' . __('Available Held jobs for all users'), false, 'ubButton');
        $controls.= wf_Link('?module=report_dealwithit&history=true', wf_img('skins/icon_calendar.gif') . ' ' . __('History'), false, 'ubButton');
        show_window('', $controls);
        if (wf_CheckGet(array('history'))) {
            show_window(__('Scheduler history'),$dealWithIt->renderTasksHistory());
        } else {
            show_window(__('Available Held jobs for all users'), $dealWithIt->renderTasksList());
        }
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('This module is disabled'));
}
?>