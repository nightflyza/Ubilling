<?php

if (cfr('TASKFLOW')) {
    if ($ubillingConfig->getAlterParam('TASKSTATES_ENABLED')) {
        $taskFlow = new TaskFlow();
        
        //Search form rendering
        show_window(__('Task flow'), $taskFlow->renderControls());

        //Do some fucking search!
        if (ubRouting::checkPost($taskFlow::PROUTE_STARTSEARCH)) {
            show_window(__('Search results'), $taskFlow->performSearch());
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
