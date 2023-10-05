<?php

if (cfr(LousyTariffs::RIGHT_CONFIG)) {
    $lousy = new LousyTariffs();
    //setting some existing tariff as lousy
    if (ubRouting::checkPost($lousy::PROUTE_CREATE)) {
        $newTariffName = ubRouting::post($lousy::PROUTE_CREATE);
        $creationResult = $lousy->create($newTariffName);
        if (empty($creationResult)) {
            ubRouting::nav($lousy::URL_ME);
        } else {
            show_error($creationResult);
        }
    }

    //deleting existing lousy tariff
    if (ubRouting::checkGet($lousy::ROUTE_DELETE)) {
        $tariffToDelete = ubRouting::get($lousy::ROUTE_DELETE);
        $deletionResult = $lousy->delete($tariffToDelete);
        if (empty($deletionResult)) {
            ubRouting::nav($lousy::URL_ME);
        } else {
            show_error($deletionResult);
        }
    }

    //rendering list/creation forms
    show_window(__('Rarely used tariffs'), $lousy->renderList());
} else {
    show_error(__('You cant control this module'));
}