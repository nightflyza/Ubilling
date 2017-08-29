<?php

if (cfr('USERREG')) {
    $alter_conf = $ubillingConfig->getAlter();
    //check if exclusive database locking is enabled
    $dbLockEnabled = false;
    if (isset($alter_conf['DB_LOCK_ENABLED'])) {
        if ($alter_conf['DB_LOCK_ENABLED']) {
            $dbLockEnabled = true;
        }
    }
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

        //create exclusive lock or wait until previous lock will be released
        //lock name "ipBind" is shared between userreg and pl_ipchange
        if ($dbLockEnabled) {
            $dbLockQuery = 'SELECT GET_LOCK("ipBind",1)';
            $dbLock = false;
            while (!$dbLock) {
                $dbLock = simple_query($dbLockQuery);
            }
        }
        show_window(__('User registration step 2 (Services)'), web_UserRegFormNetData($newuser_data));
        zb_BillingStats(true);

        if (isset($_POST['IP'])) {
            $newuser_data['IP'] = $_POST['IP'];
            $newuser_data['login'] = $_POST['login'];
            $newuser_data['password'] = $_POST['password'];
            zb_UserRegister($newuser_data);
            //release db lock
            if ($dbLockEnabled) {
                $dbUnlockQuery = 'SELECT RELEASE_LOCK("ipBind")';
                nr_query($dbUnlockQuery);
            }
        }
    }


    if ($alter_conf['CRM_MODE']) {
        show_window('', wf_Link("?module=expressuserreg", __('Express registration'), false, 'ubButton'));
    }
    if (wf_CheckGet(array('branchesback'))) {
        show_window('', wf_BackLink('?module=branches&userlist=true'));
    }

    show_window('', wf_FormDisabler());
} else {
    show_error(__('Access denied'));
}
?>