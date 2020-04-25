<?php

if (cfr('USERPROFILE')) {
    $altCfg = $ubillingConfig->getAlter();
    if (@$altCfg['USERSIDE_NAV']) {
        $usersideUrl = $altCfg['USERSIDE_NAV'];
        $userLogin = ubRouting::get('username');
        if (!empty($usersideUrl)) {
            $userSearchUrl = $usersideUrl . '&core_section=customer_list&action=search_page&search=' . $userLogin;
            rcms_redirect($userSearchUrl);
        } else {
            show_error('USERSIDE_NAV ' . __('is empty'));
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}