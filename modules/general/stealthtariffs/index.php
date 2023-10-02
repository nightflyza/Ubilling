<?php

$altCfg = $ubillingConfig->getAlter();
if (@$altCfg['STEALTH_TARIFFS_ENABLED']) {
    if (cfr(StealthTariffs::RIGHT_CONFIG)) {
        $stealth = new StealthTariffs();

        //setting some existing tariff as stealth
        if (ubRouting::checkPost($stealth::PROUTE_CREATE)) {
            $newTariffName = ubRouting::post($stealth::PROUTE_CREATE);
            $creationResult = $stealth->create($newTariffName);
            if (empty($creationResult)) {
                ubRouting::nav($stealth::URL_ME);
            } else {
                show_error($creationResult);
            }
        }

        //deleting existing stealth tariff
        if (ubRouting::checkGet($stealth::ROUTE_DELETE)) {
            $tariffToDelete = ubRouting::get($stealth::ROUTE_DELETE);
            $deletionResult = $stealth->delete($tariffToDelete);
            if (empty($deletionResult)) {
                ubRouting::nav($stealth::URL_ME);
            } else {
                show_error($deletionResult);
            }
        }

        //rendering list/creation forms
        show_window(__('Available stealth tariffs'), $stealth->renderList());
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('This module is disabled'));
}