<?php

if (cfr('TASKMAN')) {

    $taskmap = new TasksMap();

    show_window(__('Filters'), $taskmap->renderFiltersForm());
    show_window(__('Tasks map'), $taskmap->renderMap());
    show_window('', $taskmap->renderStats());
    show_window('', wf_BackLink('?module=taskman'));
} else {
    show_error(__('Access denied'));
}
