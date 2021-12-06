<?php

if (cfr('BUILDSREPORT')) {
    $buildReport = new ReportBuilds();
    if (ubRouting::checkGet($buildReport::ROUTE_AJLIST)) {
        $buildReport->renderAjBuildList();
    }

    show_window('', $buildReport->renderFiltersForm());
    if (!ubRouting::checkGet($buildReport::ROUTE_EXPORTS)) {
        $exportControls = wf_Link($buildReport::URL_ME . '&' . $buildReport::ROUTE_EXPORTS . '=true', web_icon_download(__('Export')));
    } else {
        $exportControls = wf_Link($buildReport::URL_ME, web_icon_search(__('View')));
    }
    show_window(__('Builds report') . ' ' . $exportControls, $buildReport->renderBuilds());
} else {
    show_error(__('Access denied'));
}