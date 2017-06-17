<?php

if (cfr('USERREG')) {
    $alter_conf = $ubillingConfig->getAlter();
    if ((!isset($_POST['apt'])) AND ( !isset($_POST['IP']))) {
        show_window(__('User registration step 1 (location)'), web_UserRegFormLocation());
    } else {

        if (isset($_POST['apt'])) {
            $newuser_data['city'] = $_POST['citysel'];
            $newuser_data['street'] = $_POST['streetsel'];
            $newuser_data['build'] = $_POST['buildsel'];
            @$newuser_data['entrance'] = $_POST['entrance'];
            @$newuser_data['floor'] = $_POST['floor'];
            $newuser_data['apt'] = $_POST['apt'];
            $newuser_data['service'] = $_POST['serviceselect'];
            //pack contrahent data
            if (isset($alter_conf['LOGIN_GENERATION'])) {
                if ($alter_conf['LOGIN_GENERATION'] == 'DEREBAN') {
                    $newuser_data['contrahent'] = $_POST['regagent'];
                }
            }
        } else {
            $newuser_data = unserialize(base64_decode($_POST['repostdata']));
        }

        show_window(__('User registration step 2 (Services)'), web_UserRegFormNetData($newuser_data));
        zb_BillingStats(true);

        if (isset($_POST['IP'])) {
            $newuser_data['IP'] = $_POST['IP'];
            $newuser_data['login'] = $_POST['login'];
            $newuser_data['password'] = $_POST['password'];
            zb_UserRegister($newuser_data);
        }
    }


    if ($alter_conf['CRM_MODE']) {
        show_window('', wf_Link("?module=expressuserreg", __('Express registration'), false, 'ubButton'));
    }

    show_window('', wf_FormDisabler());
} else {
    show_error(__('Access denied'));
}
?>