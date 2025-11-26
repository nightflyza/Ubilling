<?php

if (cfr('ANNIHILATION')) {

    /**
     * Totally deletes existing user with all his data
     * 
     * @global object $billing
     * @param string $login
     * 
     * @return void
     */
    function zb_AnnihilateUser($login) {
        global $billing;
        global $ubillingConfig;
        $altCfg = $ubillingConfig->getAlter();
        $user_ip = zb_UserGetIP($login);
        $user_aptdata = zb_AddressGetAptData($login);
        @$user_aptid = $user_aptdata['aptid'];
        //disable user before deletion - for proper OnDisconnect
        $billing->setdown($login, 1);
        $billing->setao($login, 0);
        //Multigen workaround. Must be performed before real user deletion, and after its disconnected.
        if ($altCfg['MULTIGEN_ENABLED']) {
            $multigen = new MultiGen();
            $multigen->generateNasAttributes();
        }
        //cleaning basic user data
        zb_AddressDeleteApartment($user_aptid);
        zb_AddressOrphanUser($login);

        if ($ubillingConfig->getAlterParam('ADDRESS_EXTENDED_ENABLED')) {
            zb_AddAddressExtenDelete($login);
        }

        zb_UserDeleteEmail($login);
        zb_UserDeleteNotes($login);
        zb_UserDeletePhone($login);
        zb_UserDeleteRealName($login);
        zb_UserDeleteSpeedOverride($login);

        //optional contract deletion
        if (!$altCfg['STRICT_CONTRACTS_PROTECT']) {
            zb_UserDeleteContract($login);
        }

        //flushing vcash
        zb_VserviceCashClear($login);
        log_register('USER VCASH DELETE (' . $login . ')');

        //custom fields and tags
        $cf = new CustomFields($login);
        $cf->flushAllUserFieldsData();
        zb_FlushAllUserTags($login);
        if (@$altCfg['VLANGEN_SUPPORT']) {
            vlan_delete_host($login);
        }
        //delete user from branch
        if (@$altCfg['BRANCHES_ENABLED']) {
            $branchObj = new UbillingBranches();
            $userBranch = $branchObj->userGetBranch($login);
            if (!empty($userBranch)) {
                $branchObj->userDeleteBranch($login);
            }
        }
        //openpayz static payment ID deletion
        if ($altCfg['OPENPAYZ_SUPPORT']) {
            if ($altCfg['OPENPAYZ_STATIC_ID']) {
                $openPayz = new OpenPayz(false, true);
                $openPayz->degisterStaticPaymentId($login);
            }
        }

        //delete user connection details
        if ($altCfg['CONDET_ENABLED']) {
            $condet = new ConnectionDetails();
            $condet->delete($login);
            log_register('USER CONDET FLUSH (' . $login . ')');
        }

        //switch port bindings deletion
        if ($altCfg['SWITCHPORT_IN_PROFILE']) {
            $switchPortAssigns = new SwitchPortAssign();
            $switchPortAssigns->delete($login);
        }

        //taxsup user fee deletion
        if ($altCfg['TAXSUP_ENABLED']) {
            $taxsup = new TaxSup();
            $taxsup->deleteUserFee($login);
        }

        //flushing some QinQ bindings
        $qinqBindingsDb = new NyanORM('qinq_bindings');
        $qinqBindingsDb->where('login', '=', $login);
        $qinqBindingsDb->delete();

        //cleaning multinet data
        multinet_delete_host($user_ip);
        multinet_rebuild_all_handlers();
        //destroing stargazer user
        $billing->deleteuser($login);

        log_register('USER STG DELETE (' . $login . ')');
    }

    /**
     * Renders user deletion form
     * 
     * @param string $login
     * 
     * @return void
     */
    function web_AnnihilateFormShow($login) {
        $alladdress = zb_AddressGetFulladdresslist();
        $inputs = __('Be careful, this module permanently deletes user and all data associated with it. Opportunities to raise from the dead no longer.');
        $inputs .= wf_tag('br');
        $inputs .= __('To ensure that we have seen the seriousness of your intentions to enter the word сonfirm the field below.');
        $inputs .= wf_delimiter();
        $inputs .= wf_tag('input', false, '', 'type="text" name="confirmation" autocomplete="off"');
        $inputs .= wf_HiddenInput('anihilation', 'true');
        $inputs .= wf_delimiter();
        $inputs .= wf_Submit(__('I really want to stop suffering User'));
        $form = wf_Form('', 'POST', $inputs, 'glamour');
        show_window(__('Deleting user') . ' ' . @$alladdress[$login] . ' (' . $login . ')', $form);
    }

    if (ubRouting::checkGet('username')) {
        $login = ubRouting::get('username');
        web_AnnihilateFormShow($login);
        //check for delete confirmation
        if (ubRouting::checkPost('anihilation')) {
            if (ubRouting::checkPost('confirmation')) {
                if (ubRouting::post('confirmation') == 'confirm') {
                    zb_AnnihilateUser($login);
                    ubRouting::nav('?module=index');
                } else {
                    show_error(__('You are not mentally prepared for this') . '. ' . __('Confirmation') . ' ' . __('Failed') . '.');
                }
            } else {
                show_error(__('You are not mentally prepared for this'));
            }
        }

        show_window('', web_UserControls($login));
    } else {
        show_error(__('Strange exception') . ': GET_NO_USERNAME');
        show_window('', wf_tag('center') . wf_img('skins/unicornchainsawwrong.png') . wf_tag('center', true));
    }
} else {
    show_error(__('You cant control this module'));
}

