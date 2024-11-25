<?php

if (cfr('TASKMANTRACK')) {
    $taskTracking = new TaskmanTracking();

    //create new task tracking
    if (ubRouting::checkGet('trackid')) {
        $trackingResult = $taskTracking->setTaskTracked(ubRouting::get('trackid'));
        if ($trackingResult) {
            ubRouting::nav('?module=taskmantrack');
        } else {
            show_error(__('Strange exeption') . ': EX_NON_EXISTING_TASKID');
        }
    }

    //delete task tracking
    if (ubRouting::checkGet('untrackid')) {
        $taskTracking->setTaskUntracked(ubRouting::get('untrackid'));
        ubRouting::nav('?module=taskmantrack');
    }

    //rendering tracking list
    show_window(__('Tasks tracking'), $taskTracking->render());
} else {
    show_error(__('Access denied'));
}
