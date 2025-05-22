<?php

$prevTasksResult = '';
if (cfr('TASKMAN')) {
    if (ubRouting::checkGet('username')) {
        $noFwFlag = (ubRouting::checkGet('nofw')) ? true : false;
        $prevTasksResult = ts_PreviousUserTasksRender(ubRouting::get('username', 'login'), '', $noFwFlag);
        die($prevTasksResult);
    } else {
        if (ubRouting::checkGet('address')) {
            $prevTasksResult = ts_PreviousUserTasksRender('', ubRouting::get('address'), true);
            if (empty($prevTasksResult)) {
                $messages = new UbillingMessageHelper();
                $prevTasksResult = $messages->getStyledMessage(__('Nothing found'), 'warning');
            }
            show_window(__('Previous user tasks'), $prevTasksResult);
            if (ubRouting::checkGet('ukvuserid')) {
                show_window('', wf_BackLink(UkvSystem::URL_USERS_PROFILE . ubRouting::get('ukvuserid', 'int')));
            }
        }
    }
} else {
    show_error(__('Access denied'));
}
