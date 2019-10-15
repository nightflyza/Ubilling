<?php

// check for right of current admin on this module
if (cfr('ANNIHILATION')) {

    /**
     * Deletes existing user
     * 
     * @global object $billing
     * @param string $login
     * 
     * @return void
     */
    function zb_AnnihilateUser($login) {
        global $billing;
        $alter_conf = rcms_parse_ini_file(CONFIG_PATH . "alter.ini");
        $user_ip = zb_UserGetIP($login);
        $user_aptdata = zb_AddressGetAptData($login);
        @$user_aptid = $user_aptdata['aptid'];
        //disable user before deletion - for proper OnDisconnect
        $billing->setdown($login, 1);
        $billing->setao($login, 0);
        //Multigen workaround. Must be performed before real user deletion, and after its disconnected.
        if (@$alter_conf['MULTIGEN_ENABLED']) {
            $multigen = new MultiGen();
            $multigen->generateNasAttributes();
        }
        //start user deletion
        zb_AddressDeleteApartment($user_aptid);
        zb_AddressOrphanUser($login);
        zb_UserDeleteEmail($login);
        zb_UserDeleteNotes($login);
        zb_UserDeletePhone($login);
        zb_UserDeleteRealName($login);
        zb_UserDeleteSpeedOverride($login);
        if (!$alter_conf['STRICT_CONTRACTS_PROTECT']) {
            zb_UserDeleteContract($login);
        }
        zb_VserviceCashClear($login);
        log_register("DELETE VCASH (" . $login . ")");
        cf_FlushAllUserCF($login);
        zb_FlushAllUserTags($login);
        if (@$alter_conf['VLANGEN_SUPPORT']) {
            vlan_delete_host($login);
        }
        //delete user from branch
        if (@$alter_conf['BRANCHES_ENABLED']) {
            $branchObj = new UbillingBranches();
            $userBranch = $branchObj->userGetBranch($login);
            if (!empty($userBranch)) {
                $branchObj->userDeleteBranch($login);
            }
        }

        //delete user connection details
        if (@$alter_conf['CONDET_ENABLED']) {
            $condet = new ConnectionDetails();
            $condet->delete($login);
        }

        multinet_delete_host($user_ip);
        multinet_rebuild_all_handlers();
        //destroy stargazer user
        $billing->deleteuser($login);

        $query = "DELETE FROM `qinq_bindings` WHERE `login`='" . $login . "'";
        nr_query($query);

        log_register("StgUser DELETE (" . $login . ")");
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
        $inputs .= wf_tag('br');
        $inputs .= wf_tag('input', false, '', 'type="text" name="confirmation" autocomplete="off"');
        $inputs .= wf_HiddenInput('anihilation', 'true');
        $inputs .= wf_delimiter();
        $inputs .= wf_Submit(__('I really want to stop suffering User'));
        $form = wf_Form('', 'POST', $inputs, 'glamour');

        show_window(__('Deleting user') . ' ' . @$alladdress[$login] . ' (' . $login . ')', $form);
    }

    if (isset($_GET['username'])) {

        $login = $_GET['username'];
        web_AnnihilateFormShow($login);
        //check for delete confirmation
        if (isset($_POST['anihilation'])) {
            if (isset($_POST['confirmation'])) {
                if ($_POST['confirmation'] == 'confirm') {
                    zb_AnnihilateUser($login);
                    rcms_redirect("?module=index");
                }
            }
        }

        show_window('', web_UserControls($login));
    }
} else {
    show_error(__('You cant control this module'));
}
?>
