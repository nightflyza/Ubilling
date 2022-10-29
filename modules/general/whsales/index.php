<?php

if (cfr('WAREHOUSE')) {
    if ($ubillingConfig->getAlterParam('WAREHOUSE_ENABLED')) {
        $greed = new Avarice();
        $avidity = $greed->runtime('WAREHOUSE');
        if (!empty($avidity)) {

            $salesReport = new WHSales();

            //new report creation
            if (ubRouting::checkPost($salesReport::PROUTE_NEWREPORT)) {
                $creationResult = $salesReport->createReport(ubRouting::post($salesReport::PROUTE_NEWREPORT));
                if (empty($creationResult)) {
                    ubRouting::nav($salesReport::URL_ME);
                } else {
                    show_error(__('Something went wrong') . ': ' . $creationResult);
                }
            }

            //existing report deletion
            if (ubRouting::checkGet($salesReport::ROUTE_REPORT_DEL)) {
                $deletionResult = $salesReport->deleteReport(ubRouting::get($salesReport::ROUTE_REPORT_DEL));
                if (empty($deletionResult)) {
                    ubRouting::nav($salesReport::URL_ME);
                } else {
                    show_error(__('Something went wrong') . ': ' . $deletionResult);
                }
            }

            //editing existing report
            if (ubRouting::checkGet($salesReport::ROUTE_REPORT_EDIT)) {
                show_window(__('Edit report'), $salesReport->renderEditForm(ubRouting::get($salesReport::ROUTE_REPORT_EDIT)));
                show_window('', wf_BackLink($salesReport::URL_ME));
            }

            //rendering available reports 
            if (!ubRouting::checkGet($salesReport::ROUTE_REPORT_EDIT) AND ! ubRouting::checkGet($salesReport::ROUTE_REPORT_RENDER)) {
                $creationControl = $salesReport->renderCreationForm();
                show_window(__('Available reports') . ' ' . $creationControl, $salesReport->renderReportsList());
            }
        } else {
            show_error(__('No license key available'));
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Permission denied'));
}