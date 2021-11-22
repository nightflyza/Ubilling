<?php

if (cfr('BUILDSREPORT')) {
    $buildReport = new ReportBuilds();
    if (ubRouting::checkGet($buildReport::ROUTE_AJLIST)) {
        $buildReport->renderAjBuildList();
    }

    show_window('', $buildReport->renderFiltersForm());
    show_window(__('Builds report'), $buildReport->renderBuilds());
} else {
    show_error(__('Access denied'));
}