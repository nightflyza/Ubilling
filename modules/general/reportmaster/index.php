<?php

if (cfr('REPORTMASTER')) {

    $reportMaster = new ReportMaster();

//new report creation
    if (ubRouting::checkPost(array($reportMaster::PROUTE_NEWTYPE, $reportMaster::PROUTE_NEWNAME, $reportMaster::PROUTE_NEWQUERY))) {
        if (cfr('REPORTMASTERADM')) {
            $newReportType = ubRouting::post($reportMaster::PROUTE_NEWTYPE);
            $newReportName = ubRouting::post($reportMaster::PROUTE_NEWNAME);
            $newReportQuery = ubRouting::post($reportMaster::PROUTE_NEWQUERY);
            $newReportKeys = ubRouting::post($reportMaster::PROUTE_NEWKEYS);
            $newReportFields = ubRouting::post($reportMaster::PROUTE_NEWFIELDS);
            $newReportAddr = ubRouting::post($reportMaster::PROUTE_NEWADDR);
            $newReportRenderNames = ubRouting::post($reportMaster::PROUTE_NEWRNAMES);
            $newReportRowCount = ubRouting::post($reportMaster::PROUTE_NEWROWCOUNT);
            $reportMaster->createReport($newReportType, $newReportName, $newReportQuery, $newReportKeys, $newReportFields, $newReportAddr, $newReportRenderNames, $newReportRowCount);
            ubRouting::nav($reportMaster::URL_ME);
        } else {
            show_error(__('You cant control this module'));
        }
    }

//existing reports list
    if (!ubRouting::checkGet($reportMaster::ROUTE_EDIT) AND ! ubRouting::checkGet($reportMaster::ROUTE_VIEW) AND ! ubRouting::checkGet($reportMaster::ROUTE_ADD)) {
        $listingControls = '';
        if (cfr('ROOT')) {
            $listingControls .= wf_Link($reportMaster::URL_ME . '&' . $reportMaster::ROUTE_BASEEXPORT . '=excel', web_icon_download(__('Export userbase')), false);
        }

        if (cfr('REPORTMASTERADM')) {
            $listingControls .= wf_Link($reportMaster::URL_ME . '&' . $reportMaster::ROUTE_ADD . '=sql', web_icon_create(__('Create new report')), false);
        }

        show_window(__('Available reports') . ' ' . $listingControls, $reportMaster->renderReportsList());
        zb_BillingStats(true);
    }

//new report creation form
    if (ubRouting::checkGet($reportMaster::ROUTE_ADD)) {
        if (cfr('REPORTMASTERADM')) {
            show_window(__('Create new report'), $reportMaster->renderCreateForm(ubRouting::get($reportMaster::ROUTE_ADD)));
            show_window('', wf_BackLink($reportMaster::URL_ME));
        } else {
            show_error(__('You cant control this module'));
        }
    }


//report deletion
    if (ubRouting::checkGet($reportMaster::ROUTE_DELETE)) {
        if (cfr('REPORTMASTERADM')) {
            $reportToDelete = ubRouting::get($reportMaster::ROUTE_DELETE, 'mres');
            if ($reportMaster->isMeAllowed($reportToDelete)) {
                $reportMaster->deleteReport($reportToDelete);
                ubRouting::nav($reportMaster::URL_ME);
            } else {
                show_error(__('Access denied'));
                log_register('REPORTMASTER DELETE FAIL REPORT `' . $reportToDelete . '` ACCESS VIOLATION');
            }
        } else {
            show_error(__('You cant control this module'));
        }
    }


//existing report editing
    if (ubRouting::checkGet($reportMaster::ROUTE_EDIT)) {
        if (cfr('REPORTMASTERADM')) {
            $reportToEdit = ubRouting::get($reportMaster::ROUTE_EDIT);
            if ($reportMaster->isMeAllowed($reportToEdit)) {
                //save changes if required
                if (ubRouting::checkPost($reportMaster::PROUTE_EDNAME)) {
                    $saveResult = $reportMaster->saveReport($reportToEdit);
                    if (empty($saveResult)) {
                        ubRouting::nav($reportMaster::URL_ME . '&' . $reportMaster::ROUTE_EDIT . '=' . $reportToEdit);
                    } else {
                        show_error($saveResult);
                    }
                }
                //render edit form
                show_window(__('Edit report'), $reportMaster->renderEditForm($reportToEdit));
            } else {
                show_error(__('Access denied'));
                log_register('REPORTMASTER EDIT FAIL REPORT `' . $reportToEdit . '` ACCESS VIOLATION');
            }
        } else {
            show_error(__('You cant control this module'));
        }
    }

//userbase exporting
    if (ubRouting::checkGet($reportMaster::ROUTE_BASEEXPORT)) {
        if (ubRouting::get($reportMaster::ROUTE_BASEEXPORT) == 'excel') {
            if (cfr('ROOT')) {
                $reportMaster->exportUserbaseCsv();
            } else {
                show_error(__('Access denied'));
                log_register('REPORTMASTER FAIL USERBASE EXPORT DENIED');
            }
        }
    }


// view reports
    if (ubRouting::checkGet($reportMaster::ROUTE_VIEW)) {
        $reportCode = $reportMaster->renderReport(ubRouting::get($reportMaster::ROUTE_VIEW));
        if (!empty($reportCode)) {
            //oh.. here is some embedded code to execute!
            eval($reportCode);
            show_window('', $reportMaster->renderBackControl());
        }
    }
} else {
    show_error(__('You cant control this module'));
}
