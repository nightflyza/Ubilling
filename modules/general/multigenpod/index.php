<?php

if ($ubillingConfig->getAlterParam('MULTIGEN_ENABLED')) {
    if (cfr('MULTIGEN')) {
        if (ubRouting::checkGet('username')) {
            $userLogin = ubRouting::get('username');
            ubRouting::nav(MultiGen::URL_ME . '&manualpod=true&username=' . $userLogin);
        } else {
            show_error(__('Something went wrong') . ': ' . __('Empty login'));
        }
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('This module is disabled'));
}