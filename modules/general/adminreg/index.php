<?php

if (cfr('STGNEWADMIN')) {


    /**
     * Returns simple new administrator registration form
     * 
     * @return string
     */
    function web_AdministratorRegForm() {
        $result = '';
        $inputs = wf_HiddenInput('registernewadministrator', 'true');
        $inputs .= wf_TextInput('username', __('Username'), '', true, 20, 'alphanumeric') . wf_delimiter(0);
        $inputs .= wf_PasswordInput('password', __('Password'), '', true, 20) . wf_delimiter(0);
        $inputs .= wf_PasswordInput('confirmation', __('Confirm password'), '', true, 20) . wf_delimiter(0);
        $inputs .= wf_TextInput('nickname', __('Nickname'), '', true, 20, 'alphanumeric') . wf_delimiter(0);
        $inputs .= wf_TextInput('email', __('Email'), '', true, 20, 'email') . wf_delimiter(1);
        $inputs .= wf_HiddenInput('userdata[hideemail]', '1');
        $inputs .= wf_HiddenInput('userdata[tz]', '2');
        $inputs .= wf_Submit(__('Create'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return($result);
    }

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
                // $system->updateUser($newAdmLogin, $newAdmNick, $newAdmPass, $newAdmConfirm, $newAdmEmail, $newAdmUserData);
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
