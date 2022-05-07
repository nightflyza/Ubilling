<?php

if (cfr('PERMISSIONS')) {

    if (!ubRouting::checkPost('registernewadministrator')) {
        show_window('', wf_BackLink('?module=permissions'));
        show_window(__('Administrator registration'), web_AdministratorRegForm());
    }

    if (ubRouting::checkPost('registernewadministrator')) {
        if (ubRouting::checkPost(array('username', 'nickname', 'password', 'confirmation', 'email'))) {
            $newAdmLogin = ubRouting::post('username');
            $newAdmNick = ubRouting::post('nickname');
            $newAdmPass = ubRouting::post('password');
            $newAdmConfirm = ubRouting::post('confirmation');
            $newAdmEmail = ubRouting::post('email');
            $newAdmUserData = ubRouting::post('userdata');

            $admRegResult = $system->registerUser($newAdmLogin, $newAdmNick, $newAdmPass, $newAdmConfirm, $newAdmEmail, $newAdmUserData);
            if ($admRegResult) {
                log_register('ADMREG {' . $newAdmLogin . '} SUCCESS');
                show_success(__('Administrator registered'));
                $permControlLabel = web_edit_icon() . ' ' . __('His permissions you can setup via corresponding module');
                $permControl = wf_link('?module=permissions&edit=' . $newAdmLogin, $permControlLabel, false, 'ubButton');
                show_window('', $permControl);
            } else {
                show_error(__('Something went wrong') . ': ' . $system->results['registration']);
                log_register('ADMREG {' . $newAdmLogin . '} FAILED');
                show_window('', wf_BackLink('?module=adminreg'));
            }
        } else {
            show_error(__('No all of required fields is filled'));
            show_window('', wf_BackLink('?module=adminreg'));
        }
    }
} else {
    show_error(__('Access denied'));
}
