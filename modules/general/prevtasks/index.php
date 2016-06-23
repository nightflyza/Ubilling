<?php

$prevTasksResult = '';
if (cfr('TASKMAN')) {
    if (wf_CheckGet(array('username'))) {
        $prevTasksResult = ts_PreviousUserTasksRender($_GET['username']);
    } else {
        $prevTasksResult = __('Strange exeption') . ': ' . __('Empty login');
    }
} else {
    $prevTasksResult = __('Access denied');
}

die($prevTasksResult);
?>
