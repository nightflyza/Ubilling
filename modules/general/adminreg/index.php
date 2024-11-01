<?php

if (cfr('PERMISSIONS')) {

    if (!ubRouting::checkPost('registernewadministrator') and ! ubRouting::checkGet('editadministrator')) {
        show_window('', wf_BackLink('?module=permissions'));
        show_window(__('Administrator registration'), web_AdministratorRegForm());
    }

    //deletion of administrator account
    if (ubRouting::checkGet('deleteadministrator')) {
        $adminForDeletion = ubRouting::get('deleteadministrator');
        user_delete($adminForDeletion);
        log_register('UBADMIN DELETE {' . $adminForDeletion . '}');
        //flushing IM cache
        $ubCache = new UbillingCache();
        $ubCache->delete('UBIM_ADM_LIST');
        $ubCache->delete('EMPLOYEE_LOGINS');
        $ubCache->delete('ADM_ONLINE');
        ubRouting::nav('?module=permissions');
    }

    //new administrator registration
    if (ubRouting::checkPost('registernewadministrator')) {
        if (ubRouting::checkPost(array('newadmusername', 'newadmpass', 'newadmconf', 'email'))) {
            $newAdmLogin = ubRouting::post('newadmusername');
            $newAdmNick = ubRouting::post('newadmusername'); //just similar with username
            $newAdmPass = ubRouting::post('newadmpass');
            $newAdmConfirm = ubRouting::post('newadmconf');
            $newAdmEmail = ubRouting::post('email');
            $newAdmUserData = ubRouting::post('userdata');

            $admRegResult = $system->registerUser($newAdmLogin, $newAdmNick, $newAdmPass, $newAdmConfirm, $newAdmEmail, $newAdmUserData);
            if ($admRegResult) {
                log_register('UBADMIN CREATE {' . $newAdmLogin . '} SUCCESS');
                show_success(__('Administrator registered'));
                $permControlLabel = web_edit_icon() . ' ' . __('His permissions you can setup via corresponding module');
                $permControl = wf_link('?module=permissions&edit=' . $newAdmLogin, $permControlLabel, false, 'ubButton');
                show_window('', $permControl);
                //flushing IM cache
                $ubCache = new UbillingCache();
                $ubCache->delete('UBIM_ADM_LIST');
                $ubCache->delete('EMPLOYEE_LOGINS');
                $ubCache->delete('ADM_ONLINE');
            } else {
                show_error(__('Something went wrong') . ': ' . $system->results['registration']);
                log_register('UBADMIN CREATE {' . $newAdmLogin . '} FAILED');
                show_window('', wf_BackLink('?module=adminreg'));
            }
        } else {
            show_error(__('No all of required fields is filled'));
            show_window('', wf_BackLink('?module=adminreg'));
        }
    }

    //editing admins password or other data
    if (ubRouting::checkGet('editadministrator')) {
        $edAdmLogin = ubRouting::get('editadministrator');
        if (ubRouting::checkPost(array('save', 'edadmusername'))) {
            $updUsername = ubRouting::post('edadmusername');
            $updNickname = ubRouting::post('edadmusername'); //same as username at this moment
            $updPassword = ubRouting::post('edadmpass');
            $updConfirmation = ubRouting::post('edadmconf');
            $updEmail = ubRouting::post('email');
            $updUserData = ubRouting::post('userdata');

            $updateResult = $system->updateUser($updUsername, $updNickname, $updPassword, $updConfirmation, $updEmail, $updUserData, true);
            if ($updateResult) {
                log_register('UBADMIN CHANGE {' . $updUsername . '} DATA SUCCESS');
                ubRouting::nav('?module=adminreg&editadministrator=' . $edAdmLogin);
            } else {
                log_register('UBADMIN CHANGE {' . $updUsername . '} DATA FAIL');
                show_error($system->results['profileupdate']);
            }
        }

        show_window('', wf_BackLink('?module=permissions'));
        show_window(__('Edit') . ': ' . $edAdmLogin, web_AdministratorEditForm($edAdmLogin));
    }
} else {
    show_error(__('Access denied'));
}
