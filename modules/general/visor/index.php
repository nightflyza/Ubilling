<?php

if (cfr('VISOR')) {

    $altCfg = $ubillingConfig->getAlter();

    if ($altCfg['VISOR_ENABLED']) {
        $visor = new UbillingVisor();

        show_window('', $visor->panel());

        if (wf_CheckGet(array('ajaxusers'))) {
            $visor->ajaxUsersList();
        }

        if (wf_CheckPost(array('newusercreate', 'newusername'))) {
            $visor->createUser();
            rcms_redirect($visor::URL_ME . $visor::URL_USERS);
        }

        if (wf_CheckGet(array('users'))) {
            $userCreateForm = $visor->renderUserCreateForm();
            $userCreateControls = ' ' . wf_modalAuto(web_add_icon(__('User registration')), __('User registration'), $userCreateForm);
            show_window(__('Users') . $userCreateControls, $visor->renderUsers());
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
?>