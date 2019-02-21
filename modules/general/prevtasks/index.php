<?php

$prevTasksResult = '';
if (cfr('TASKMAN')) {
    if (wf_CheckGet(array('username'))) {
        $prevTasksResult = ts_PreviousUserTasksRender($_GET['username']);
        die($prevTasksResult);
    } else {
        if (wf_CheckGet(array('address'))) {
            $prevTasksResult = ts_PreviousUserTasksRender('', $_GET['address'], true);
            if (empty($prevTasksResult)) {
                $messages=new UbillingMessageHelper();
                $prevTasksResult=$messages->getStyledMessage(__('Nothing found'), 'warning');
            }
            show_window(__('Previous user tasks'), $prevTasksResult);
            if (wf_CheckGet(array('ukvuserid'))) {
                show_window('', wf_BackLink(UkvSystem::URL_USERS_PROFILE.$_GET['ukvuserid']));
            }
        }
    }
} else {
    show_error(__('Access denied'));
}
?>
