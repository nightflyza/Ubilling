<?php

if (cfr(PseudoCRM::RIGHT_VIEW)) {
    $altCfg = $ubillingConfig->getAlter();
    if ($altCfg['PSEUDOCRM_ENABLED']) {
        $crm = new PseudoCRM();
        //some module controls
        show_window('', $crm->renderPanel());

        //rendering existing leads ajax data
        if (ubRouting::checkGet($crm::ROUTE_LEADS_LIST_AJ)) {
            $crm->ajLeadsList();
        }

        //rendering existing leads list
        if (ubRouting::checkGet($crm::ROUTE_LEADS_LIST)) {
            show_window(__('Existing leads'), $crm->renderLeadsList());
        }
        zb_BillingStats();
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}