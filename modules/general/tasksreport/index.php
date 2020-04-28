<?php

if (cfr('TASKREPORT')) {
    $altCfg = $ubillingConfig->getAlter();
    if ($altCfg['TASKREPORT_ENABLED']) {


        set_time_limit(0);
        $report = new TasksReport();

        if (wf_CheckGet(array('cleancache'))) {
            $report->cacheCleanup();
            rcms_redirect($report::URL_ME);
        }

        if (wf_CheckGet(array('print'))) {
            if (file_exists($report::PRINT_PATH)) {
                if (filesize($report::PRINT_PATH) > 0) {
                    $printableData = file_get_contents($report::PRINT_PATH);
                    $datesFiltered = $report->getDates();
                    die($report->reportPrintable(__('Warehouse') . ': ' . $datesFiltered['from'] . '-' . $datesFiltered['to'], $printableData));
                }
            }
        }
        $cacheCleanupControl = wf_Link($report::URL_ME . '&cleancache=true', wf_img('skins/icon_cleanup.png', __('Cache cleanup')));
        show_window(__('Search') . ' ' . $cacheCleanupControl, $report->renderDatesForm());
        show_window(__('Tasks report'), $report->renderReport());
        $datesFiltered = $report->getDates();
        $printDates = '&datefrom=' . $datesFiltered['from'] . '&dateto=' . $datesFiltered['to'];
        $printControl = ((file_exists($report::PRINT_PATH)) AND ( filesize($report::PRINT_PATH) > 0) ) ? wf_Link($report::URL_ME . '&print=true' . $printDates, web_icon_print() . ' ' . __('Print'), false, 'ubButton', 'target="_blank"') : '';
        show_window('', $printControl);
    } else {
        show_error(__('This module disabled'));
    }
} else {
    show_error(__('Access denied'));
}
?>