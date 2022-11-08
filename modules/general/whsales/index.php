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
                $reportEditUrl = $salesReport::URL_ME . '&' . $salesReport::ROUTE_REPORT_EDIT . '=' . ubRouting::get($salesReport::ROUTE_REPORT_EDIT);
                //deleting some itemtype record
                if (ubRouting::checkGet($salesReport::ROUTE_ITEM_DEL)) {
                    $salesReport->deleteReportItem(ubRouting::get($salesReport::ROUTE_REPORT_EDIT), ubRouting::get($salesReport::ROUTE_ITEM_DEL));
                    ubRouting::nav($reportEditUrl);
                }

                //adding some itemtype to report
                if (ubRouting::checkPost(array($salesReport::PROUTE_NEWREPORTITEM, $salesReport::PROUTE_NEWREPORTITEMID))) {
                    $reportAddId = ubRouting::post($salesReport::PROUTE_NEWREPORTITEM);
                    $reportAddItemId = ubRouting::post($salesReport::PROUTE_NEWREPORTITEMID);
                    $itemAppendResult = $salesReport->addReportItem($reportAddId, $reportAddItemId);
                    if (empty($itemAppendResult)) {
                        ubRouting::nav($reportEditUrl);
                    } else {
                        show_error($itemAppendResult);
                    }
                }

                show_window(__('Edit report'), $salesReport->renderEditForm(ubRouting::get($salesReport::ROUTE_REPORT_EDIT)));
                show_window('', wf_BackLink($salesReport::URL_ME));
            }

            //rendering available reports list
            if (!ubRouting::checkGet($salesReport::ROUTE_REPORT_EDIT) AND ! ubRouting::checkGet($salesReport::ROUTE_REPORT_RENDER)) {
                $creationControl = $salesReport->renderCreationForm();
                show_window(__('Available reports') . ' ' . $creationControl, $salesReport->renderReportsList());
            }

            //rendering existing report
            if (ubRouting::checkGet($salesReport::ROUTE_REPORT_RENDER)) {
                $reportIdToRender = ubRouting::get($salesReport::ROUTE_REPORT_RENDER);
                show_window(__('Sales report') . ': ' . $salesReport->getReportName($reportIdToRender), $salesReport->renderReport($reportIdToRender));
            }
            zb_BillingStats();
        } else {
            show_error(__('No license key available'));
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Permission denied'));
}