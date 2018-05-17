<?php

$altCfg = $ubillingConfig->getAlter();
if ($altCfg['DEALWITHIT_ENABLED']) {
    if (cfr('DEALWITHIT')) {
        $dealWithIt = new DealWithIt();
        $controls = wf_Link('?module=report_dealwithit', wf_img('skins/dealwithitsmall.png') . ' ' . __('Available Held jobs for all users'), false, 'ubButton');
        $controls.= wf_Link('?module=report_dealwithit&history=true', wf_img('skins/icon_calendar.gif') . ' ' . __('History'), false, 'ubButton');
        $controls.= wf_Link('?module=pl_dealwithit', wf_img('skins/icon_dealwithit_cron.png') . ' ' . __('Bulk creation of tasks'), false, 'ubButton');
        show_window('', $controls);
        if (wf_CheckGet(array('history'))) {
            //json reply
            if (wf_CheckGet(array('ajax'))) {
                $dealWithIt->AjaxDataTasksHistory();
            }
            show_window(__('Scheduler history'),$dealWithIt->renderTasksHistoryAjax());
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