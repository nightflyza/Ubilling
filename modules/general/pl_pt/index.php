<?php

if (cfr('PT')) {
    $enableOption = $ubillingConfig->getAlterParam('PT_ENABLED');
    if ($enableOption) {
        $userLogin = ubRouting::get('username');
        if (!empty($userLogin)) {
            $powerTariffs = new PowerTariffs(true);
            if (ubRouting::checkPost($powerTariffs::PROUTE_EDITOFFSET)) {
                if (ubRouting::checkPost($powerTariffs::PROUTE_AGREE)) {
                    $powerTariffs->saveUserOffsetDay($userLogin, ubRouting::post($powerTariffs::PROUTE_EDITOFFSET));
                    ubRouting::nav($powerTariffs::ROUTE_BACK . '&username=' . $userLogin);
                } else {
                    show_error(__('You are not mentally prepared for this'));
                }
            }
            show_window(__('Edit personal fee date'), $powerTariffs->renderUserOffsetEditForm($userLogin));
            show_window(__('Logs'), $powerTariffs->renderPowerUserLog($userLogin));
            show_window('', web_UserControls($userLogin));
        } else {
            show_error(__('Something went wrong'));
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}