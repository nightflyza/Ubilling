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

    //ONU assigment check
    if (@$alter_conf['ONUAUTO_USERREG']) {
        if ($_GET['action'] = 'checkONUAssignment' and isset($_GET['onumac'])) {
            $PONAPIObject = new PONizer();
            $ONUMAC = $_GET['onumac'];
            $ONUAssignment = $PONAPIObject->checkONUAssignment($PONAPIObject->getONUIDByMAC($ONUMAC));

            switch ($ONUAssignment) {
                case 0:
                    $tString = __('ONU is not assigned');
                    break;

                case 1:
                    $tString = __('ONU is already assigned, but such login is not exists anymore');
                    break;

                case 2:
                    $tString = __('ONU is already assigned');
                    break;
            }

            echo $tString;
            die();
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
            $dbLockQuery = 'SELECT GET_LOCK("ipBind",1) AS result';
            $dbLock = false;
            while (!$dbLock) {
                $dbLockCheck = simple_query($dbLockQuery);
                $dbLock = $dbLockCheck['result'];
            }
        }
        show_window(__('User registration step 2 (Services)'), web_UserRegFormNetData($newuser_data));
        zb_BillingStats(true);

        if (isset($_POST['IP'])) {
            $newuser_data['IP'] = $_POST['IP'];
            $newuser_data['login'] = $_POST['login'];
            $newuser_data['password'] = $_POST['password'];
            //ONU auto assign additional options
            if (@$alter_conf['ONUAUTO_USERREG']) {
                $newuser_data['oltid'] = $_POST['oltid'];
                $newuser_data['onumodelid'] = $_POST['onumodelid'];
                $newuser_data['onuip'] = wf_CheckPost(array('onuipproposal')) ? $_POST['IP'] : $_POST['onuip'];
                $newuser_data['onumac'] = $_POST['onumac'];
            }
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