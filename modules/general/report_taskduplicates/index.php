<?php

if (cfr('TASKMANSEARCH')) {

    $tasksDuplicates = new TasksDuplicates();
    show_window(__('Tasks duplicates'), $tasksDuplicates->renderSearchForm());
    if (ubRouting::checkPost($tasksDuplicates::PROUTE_SHOWREPORT)) {
        show_window(__('Search results'), $tasksDuplicates->renderReport());
    } else {
        show_window('', $tasksDuplicates->renderAdviceOfTheDay());
    }
    show_window('', wf_BackLink('?module=taskman'));
} else {
    show_error(__('Access denied'));
}
