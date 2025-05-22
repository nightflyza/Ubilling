<?php

if (cfr('TASKMANTRACK')) {
    $taskTracking = new TaskmanTracking();

    //create new task tracking
    if (ubRouting::checkGet($taskTracking::ROUTE_TRACK)) {
        $trackingResult = $taskTracking->setTaskTracked(ubRouting::get($taskTracking::ROUTE_TRACK));
        if ($trackingResult) {
            ubRouting::nav($taskTracking::URL_ME);
        } else {
            show_error(__('Strange exeption') . ': EX_NON_EXISTING_TASKID');
        }
    }

    //delete task tracking
    if (ubRouting::checkGet($taskTracking::ROUTE_UNTRACK)) {
        $taskTracking->setTaskUntracked(ubRouting::get($taskTracking::ROUTE_UNTRACK));
        ubRouting::nav($taskTracking::URL_ME);
    }

    //rendering tracking list
    show_window(__('Tasks tracking'), $taskTracking->render());
} else {
    show_error(__('Access denied'));
}
