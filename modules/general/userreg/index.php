<?php

if (cfr('USERREG')) {
    $alter_conf = $ubillingConfig->getAlter();
    $registerUserONU = $ubillingConfig->getAlterParam('ONUAUTO_USERREG');

    //check if exclusive database locking is enabled
    $dbLockEnabled = false;
    if (isset($alter_conf['DB_LOCK_ENABLED'])) {
        if ($alter_conf['DB_LOCK_ENABLED']) {
            $dbLockEnabled = true;
        }
    }

    $registerSteps = array(
        __('Step') . ' 1' => __('Select city'),
        __('Step') . ' 2' => __('Select street'),
        __('Step') . ' 3' => __('Select build'),
        __('Step') . ' 4' => __('Select service'),
        __('Success') => __('Confirm'),
    );

    //getting UnknonwsSelector for userreg
    if (wf_CheckGet(array('getunknownlist', 'oltid'))) {
        $Pon = new PONizer();
        die($Pon->getUnknownONUMACList(vf($_GET['oltid'], 3), true, true, $_GET['selectorid'], $_GET['selectorname']));
    }

    if ((!isset($_POST['apt'])) AND ( !isset($_POST['IP']))) {
        show_window(__('User registration part 1 (location)'), web_UserRegFormLocation());
    } else {

        if (isset($_POST['apt'])) {
            $newuser_data['city'] = ubRouting::post('citysel');
            $newuser_data['street'] = ubRouting::post('streetsel');
            $newuser_data['build'] = ubRouting::post('buildsel');
            @$newuser_data['entrance'] = ubRouting::post('entrance');
            @$newuser_data['floor'] = ubRouting::post('floor');
            $newuser_data['apt'] = ubRouting::post('apt');
            $newuser_data['service'] = ubRouting::post('serviceselect');
            //pack contrahent data
            if (isset($alter_conf['LOGIN_GENERATION'])) {
                if ($alter_conf['LOGIN_GENERATION'] == 'DEREBAN') {
                    $newuser_data['contrahent'] = $_POST['regagent'];
                }
            }

            //pack extended address info data
            if (isset($alter_conf['ADDRESS_EXTENDED_ENABLED']) and $alter_conf['ADDRESS_EXTENDED_ENABLED']) {
                $newuser_data['postalcode'] = (isset($_POST['postalcode'])) ? $_POST['postalcode'] : '';
                $newuser_data['towndistr'] = (isset($_POST['towndistr'])) ? $_POST['towndistr'] : '';
                $newuser_data['addressexten'] = (isset($_POST['addressexten'])) ? $_POST['addressexten'] : '';
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
        show_window(__('User registration part 2 (Services)'), web_UserRegFormNetData($newuser_data));
        zb_BillingStats(true);

        if (isset($_POST['IP'])) {
            $newuser_data['IP'] = $_POST['IP'];
            $newuser_data['login'] = $_POST['login'];
            $newuser_data['password'] = $_POST['password'];

            //ONU auto assign additional options
            if ($registerUserONU) {
                if (wf_CheckPost(array('nooltsfound'))) {
                    $newuser_data['oltid'] = '';
                    $newuser_data['onumodelid'] = '';
                    $newuser_data['onuip'] = '';
                    $newuser_data['onumac'] = '';
                    $newuser_data['onuserial'] = '';
                } else {
                    $newuser_data['oltid'] = @$_POST['oltid'];
                    $newuser_data['onumodelid'] = @$_POST['onumodelid'];
                    $newuser_data['onuip'] = wf_CheckPost(array('onuipproposal')) ? $_POST['IP'] : @$_POST['onuip'];
                    $newuser_data['onumac'] = @$_POST['onumac'];
                    $newuser_data['onuserial'] = @$_POST['onuserial'];
                }
            }

            if (isset($alter_conf['USERREG_MAC_INPUT_ENABLED']) and $alter_conf['USERREG_MAC_INPUT_ENABLED']) {
                $newMac = '';

                if (isset($_POST['userMAC']) and ! empty($_POST['userMAC'])) {
                    $newMac = $_POST['userMAC'];
                    $newMac = trim($newMac);
                    $newMac = strtolower($newMac);
                    //check mac for free
                    $allUsedMacs = zb_getAllUsedMac();
                    if (!zb_checkMacFree($newMac, $allUsedMacs)) {
                        $alert = wf_tag('script', false, '', 'type="text/javascript"');
                        $alert .= 'alert("' . __('Error') . ': ' . __('This MAC is currently used') . '");';
                        $alert .= wf_tag('script', true);
                        print($alert);
                        rcms_redirect("?module=userreg");
                        die();
                    }

                    //validate mac format
                    if (!check_mac_format($newMac)) {
                        $alert = wf_tag('script', false, '', 'type="text/javascript"');
                        $alert .= 'alert("' . __('Error') . ': ' . __('This MAC have wrong format') . '");';
                        $alert .= wf_tag('script', true);
                        print($alert);
                        rcms_redirect("?module=userreg");
                        die();
                    }
                }

                $newuser_data['userMAC'] = $newMac;
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