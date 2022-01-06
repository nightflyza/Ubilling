<?php

if (cfr('SALARY')) {
    if ($ubillingConfig->getAlterPAram('SALARY_ENABLED')) {
        $greed = new Avarice();
        $beggar = $greed->runtime('SALARY');
        if (!empty($beggar)) {
            $laborTimeReport = new TasksLaborTime();
            show_window(__('Employee timeline'), $laborTimeReport->renderSearchForm());
            show_window(__('Planned tasks on') . ' ' . $laborTimeReport->getDateFilter(), $laborTimeReport->renderReport());
            show_window('', wf_BackLink('?module=taskman'));
        } else {
            show_error(__('No license key available'));
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}