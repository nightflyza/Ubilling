<?php

$altCfg = $ubillingConfig->getAlter();
if (@$altCfg['SW_CASH_ENABLED']) {
    if (cfr('SWITCHESEDIT')) {
        set_time_limit(0);
        $swCash = new SwitchCash();

        //creating new financial data for some switch
        if (ubRouting::checkPost($swCash::PROUTE_CREATE)) {
            $creationResult = $swCash->catchCreate();
            if (empty($creationResult)) {
                ubRouting::nav($swCash::URL_ME . '&' . $swCash::ROUTE_EDIT . '=' . ubRouting::post($swCash::PROUTE_CREATE));
            } else {
                show_error($creationResult);
            }
        }

        //editing existing financial data for some switch
        if (ubRouting::checkPost($swCash::PROUTE_SAVE)) {
            $saveResult = $swCash->catchSave();

            if (empty($saveResult)) {
                ubRouting::nav($swCash::URL_ME . '&' . $swCash::ROUTE_EDIT . '=' . ubRouting::post($swCash::PROUTE_SAVE));
            } else {
                show_error($saveResult);
            }
        }

        //rendering create/edit forms
        if (ubRouting::checkGet($swCash::ROUTE_EDIT)) {
            $switchId = ubRouting::get($swCash::ROUTE_EDIT, 'int');
            if (!$swCash->haveFinancialData($switchId)) {
                //creation form
                show_window(__('Create') . ' ' . __('Financial data'), $swCash->renderCreateForm($switchId));
                show_window('', wf_BackLink($swCash::URL_SWITCHPROFILE . $switchId));
            } else {
                //editing form
                show_window(__('Edit') . ' ' . __('Financial data'), $swCash->renderEditForm($switchId));
                show_window('', wf_BackLink($swCash::URL_SWITCHPROFILE . $switchId));
            }
        }

        //rendering basic report
        if (ubRouting::checkGet($swCash::ROUTE_REPORT)) {
            $swCash->loadReportData();
            show_window(__('Switches profitability'), $swCash->renderBasicReport());
        }
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('This module is disabled'));
}