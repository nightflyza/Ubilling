<?php

if (cfr('TASKFLOW')) {
    if ($ubillingConfig->getAlterParam('TASKSTATES_ENABLED')) {
        $taskFlow = new TaskFlow();
        show_window(__('Task flow'), $taskFlow->renderControls());
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
