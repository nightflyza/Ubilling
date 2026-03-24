<?php

if (cfr('SALARY')) {
    if ($ubillingConfig->getAlterPAram('SALARY_ENABLED')) {
        $laborTimeReport = new TasksLaborTime();
        show_window(__('Employee timeline'), $laborTimeReport->renderSearchForm());
        show_window(__('Planned tasks on') . ' ' . $laborTimeReport->getDateFilter(), $laborTimeReport->renderReport());
        show_window('', wf_BackLink('?module=taskman'));
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}