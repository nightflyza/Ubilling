<?php

if (cfr('USERREG')) {
    $altCfg = $ubillingConfig->getAlter();
    $registerUserONU = $ubillingConfig->getAlterParam('ONUAUTO_USERREG');

    //check if exclusive database locking is enabled
    $dbLockEnabled = false;
    if (isset($altCfg['DB_LOCK_ENABLED'])) {
        if ($altCfg['DB_LOCK_ENABLED']) {
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
    if (ubRouting::checkGet(array('getunknownlist', 'oltid'))) {
        $Pon = new PONizer();
        die($Pon->getUnknownONUMACList(ubRouting::get('oltid', 'int'), true, true, ubRouting::get('selectorid'), ubRouting::get('selectorname')));
    }

    if (!ubRouting::checkPost('apt', false) and !ubRouting::checkPost('IP')) {
        show_window(__('User registration part 1 (location)'), web_UserRegFormLocation());
    } else {

        if (isset($_POST['apt'])) {
            $newUserData['city'] = ubRouting::post('citysel');
            $newUserData['street'] = ubRouting::post('streetsel');
            $newUserData['build'] = ubRouting::post('buildsel');
            $newUserData['entrance'] = ubRouting::post('entrance');
            $newUserData['floor'] = ubRouting::post('floor');
            $newUserData['apt'] = ubRouting::post('apt');
            $newUserData['service'] = ubRouting::post('serviceselect');
            //pack contrahent data
            if (isset($altCfg['LOGIN_GENERATION'])) {
                if ($altCfg['LOGIN_GENERATION'] == 'DEREBAN') {
                    $newUserData['contrahent'] = ubRouting::post('regagent');
                }
            }

            //pack extended address info data
            if (isset($altCfg['ADDRESS_EXTENDED_ENABLED']) and $altCfg['ADDRESS_EXTENDED_ENABLED']) {
                $newUserData['postalcode'] = (ubRouting::checkPost('postalcode')) ? ubRouting::post('postalcode') : '';
                $newUserData['towndistr'] = (ubRouting::checkPost('towndistr')) ? ubRouting::post('towndistr') : '';
                $newUserData['addressexten'] = (ubRouting::checkPost('addressexten')) ? ubRouting::post('addressexten') : '';
            }
        } else {
            $newUserData = unserialize(base64_decode(ubRouting::post('repostdata')));
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
        show_window(__('User registration part 2 (Services)'), web_UserRegFormNetData($newUserData));
        zb_BillingStats(true);

        if (ubRouting::checkPost('IP')) {
            $newUserData['IP'] = ubRouting::post('IP');
            $newUserData['login'] = ubRouting::post('login');
            $newUserData['password'] = ubRouting::post('password');

            //ONU auto assign additional options
            if ($registerUserONU) {
                if (ubRouting::checkPost('nooltsfound')) {
                    $newUserData['oltid'] = '';
                    $newUserData['onumodelid'] = '';
                    $newUserData['onuip'] = '';
                    $newUserData['onumac'] = '';
                    $newUserData['onuserial'] = '';
                } else {
                    $newUserData['oltid'] = ubRouting::checkPost('oltid') ? ubRouting::post('oltid') : '';
                    $newUserData['onumodelid'] = ubRouting::checkPost('onumodelid') ? ubRouting::post('onumodelid') : '';
                    $newUserData['onuip'] = ubRouting::checkPost('onuipproposal') ? ubRouting::post('IP') : ubRouting::post('onuip');
                    $newUserData['onumac'] = (ubRouting::checkPost('onumac')) ? ubRouting::post('onumac') : '';
                    $newUserData['onuserial'] = (ubRouting::checkPost('onuserial')) ? ubRouting::post('onuserial') : '';
                }
            }

            if (isset($altCfg['USERREG_MAC_INPUT_ENABLED']) and $altCfg['USERREG_MAC_INPUT_ENABLED']) {
                $newMac = '';

                if (ubRouting::checkPost('userMAC')) {
                    $newMac = ubRouting::post('userMAC');
                    $newMac = trim($newMac);
                    $newMac = strtolower($newMac);
                    //check mac for free
                    $allUsedMacs = zb_getAllUsedMac();
                    if (!zb_checkMacFree($newMac, $allUsedMacs)) {
                        $alert = wf_tag('script', false, '', 'type="text/javascript"');
                        $alert .= 'alert("' . __('Error') . ': ' . __('This MAC is currently used') . '");';
                        $alert .= wf_tag('script', true);
                        print($alert);
                        ubRouting::nav("?module=userreg");
                        die();
                    }

                    //validate mac format
                    if (!check_mac_format($newMac)) {
                        $alert = wf_tag('script', false, '', 'type="text/javascript"');
                        $alert .= 'alert("' . __('Error') . ': ' . __('This MAC have wrong format') . '");';
                        $alert .= wf_tag('script', true);
                        print($alert);
                        ubRouting::nav("?module=userreg");
                        die();
                    }
                }

                $newUserData['userMAC'] = $newMac;
            }
            //registering user, hell yeah!
            zb_UserRegister($newUserData);
            //release db lock
            if ($dbLockEnabled) {
                $dbUnlockQuery = 'SELECT RELEASE_LOCK("ipBind")';
                nr_query($dbUnlockQuery);
            }
        }
    }



    if (ubRouting::checkGet('branchesback')) {
        show_window('', wf_BackLink('?module=branches&userlist=true'));
    }

    show_window('', wf_FormDisabler());
} else {
    show_error(__('Access denied'));
}
