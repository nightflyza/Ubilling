<?php

if (cfr('BUILDSREPORT')) {
    $buildReport = new ReportBuilds();
    if (ubRouting::checkGet($buildReport::ROUTE_AJLIST)) {
        $buildReport->renderAjBuildList();
    }

    show_window('', $buildReport->renderFiltersForm());

    $exportSuffix = (ubRouting::checkGet($buildReport::ROUTE_GEO)) ? '&' . $buildReport::ROUTE_GEO . '=true' : '';
    $geoSuffix = (ubRouting::checkGet($buildReport::ROUTE_EXPORTS)) ? '&' . $buildReport::ROUTE_EXPORTS . '=true' : '';

    if (!ubRouting::checkGet($buildReport::ROUTE_EXPORTS)) {
        $exportControls = wf_Link($buildReport::URL_ME . '&' . $buildReport::ROUTE_EXPORTS . '=true' . $exportSuffix, web_icon_download(__('Export')));
    } else {
        $exportControls = wf_Link($buildReport::URL_ME . $exportSuffix, web_icon_search(__('Report')));
    }

    if (!ubRouting::checkGet($buildReport::ROUTE_GEO)) {
        $geoControls = wf_Link($buildReport::URL_ME . '&' . $buildReport::ROUTE_GEO . '=true' . $geoSuffix, wf_img('skins/icon_fullmap16.png', __('Geo location')));
    } else {
        $geoControls = wf_Link($buildReport::URL_ME . $geoSuffix, wf_img('skins/icon_briefmap16.png'));
    }

    show_window(__('Builds report') . ' ' . $exportControls . ' ' . $geoControls, $buildReport->renderBuilds());
} else {
    show_error(__('Access denied'));
}
