<?php

if (cfr('PT')) {
    $altCfg = $ubillingConfig->getAlter();
    if ($altCfg['PT_ENABLED']) {
        $pt = new PowerTariffs();

        //new tariff creation
        if (ubRouting::checkPost(array('creatept', 'createptfee'))) {
            $tariffCreationResult = $pt->createTariff(ubRouting::post('creatept'), ubRouting::post('createptfee'));
            if (empty($tariffCreationResult)) {
                ubRouting::nav($pt::URL_ME);
            } else {
                show_error(__($tariffCreationResult));
            }
        }

        //tariff deletion
        if (ubRouting::checkGet($pt::ROUTE_DELETE)) {
            $tariffDeletionResult = $pt->deleteTariff(ubRouting::get($pt::ROUTE_DELETE));
            if (empty($tariffDeletionResult)) {
                ubRouting::nav($pt::URL_ME);
            } else {
                show_error(__($tariffDeletionResult));
            }
        }

        show_window(__('Available tariffs'), $pt->renderTariffsList());
        show_window(__('Create new tariff'), $pt->renderTariffCreateForm());
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}