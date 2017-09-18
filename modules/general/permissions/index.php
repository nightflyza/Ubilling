<?php

if (cfr('PERMISSIONS')) {

    /**
     * Performs replication of administrators rights to existing user
     * 
     * @global object $system
     * @param string $sourceUser
     * @param string $targetUser
     */
    function zb_PermissionsCopyAdminRights($sourceUser, $targetUser) {
        global $system;
        $targetRights = array();
        $rootUser = '';
        $rights = array();
        $system->getRightsForUser($sourceUser, $rights, $root, $level);
        if ($root) {
            $rootUser = 1;
        } else {
            if (!empty($rights)) {
                foreach ($rights as $eachright => $desc) {
                    $targetRights[$eachright] = 'on';
                }
            }
        }
//writing changes
        if ($system->setRightsForUser($targetUser, $targetRights, $rootUser, '1')) {
            show_window('', __('Rights cloned'));
            log_register("CLONE AdminPermissions FROM {" . $sourceUser . "} TO {" . $targetUser . "}");
            rcms_redirect("?module=permissions&edit=" . $targetUser);
        } else {
            show_error(__('Error occurred'));
        }
    }

    /**
     * Returns login selector with all of administrator users
     * 
     * @param string $excludeuser
     * @return string
     */
    function web_AdminLoginSelector($excludeuser = '') {
        $alladdmins = rcms_scandir(USERS_PATH);
        $alllogins = array();
        if (!empty($alladdmins)) {
            foreach ($alladdmins as $eachlogin) {
                $alllogins[$eachlogin] = $eachlogin;
            }
        }
        if (!empty($excludeuser)) {
            unset($alllogins[$excludeuser]);
        }
        $result = wf_Selector('admincopyselector', $alllogins, __('Copy rights of this administrator for current user'), '', false);
        return ($result);
    }

    /**
     * Returns available administrators list
     * 
     * @return string
     */
    function web_list_admins() {
        $alladmins = rcms_scandir(USERS_PATH);
        $cells = wf_TableCell(__('Admin'));
        $cells.= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($alladmins)) {
            foreach ($alladmins as $eachadmin) {
                $actions = wf_JSAlert('?module=permissions&delete=' . $eachadmin, web_delete_icon(), 'Removing this may lead to irreparable results');
                $actions.= wf_Link('?module=permissions&passwd=' . $eachadmin, web_key_icon());
                $actions.= wf_Link('?module=permissions&edit=' . $eachadmin, web_edit_icon('Rights'));

                $cells = wf_TableCell($eachadmin);
                $cells.= wf_TableCell($actions);
                $rows.= wf_TableRow($cells, 'row5');
            }
        }

        $form = wf_TableBody($rows, '100%', '0', 'sortable');
        return($form);
    }

    /**
     * Returns available permissions groups
     * 
     * @param string $groupname
     * @return array
     */
    function zb_PermissionGroup($groupname) {
        $path = CONFIG_PATH . "permgroups.ini";
        $result = array();
        $rawdata = rcms_parse_ini_file($path);
        $rawperms = explode(',', $rawdata[$groupname]);
        if (!empty($groupname)) {
            $result = $rawperms;
            $result = array_flip($result);
        }
        return ($result);
    }

    /**
     * Shows permissions editor for some user
     * 
     * @global object $system
     * @param string $login
     */
    function web_permissions_editor($login) {
        global $system;
        $regperms = zb_PermissionGroup('USERREG');
        $geoperms = zb_PermissionGroup('GEO');
        $sysperms = zb_PermissionGroup('SYSTEM');
        $finperms = zb_PermissionGroup('FINANCE');
        $repperms = zb_PermissionGroup('REPORTS');
        $catvperms = zb_PermissionGroup('CATV');
        $branchesperms = zb_PermissionGroup('BRANCHES');

        $reginputs = '';
        $geoinputs = '';
        $sysinputs = '';
        $fininputs = '';
        $repinputs = '';
        $catvinputs = '';
        $branchesinputs = '';
        $miscinputs = '';


        $inputs = wf_BackLink('?module=permissions') . wf_delimiter();

        $inputs.=wf_HiddenInput('save', '1');
        if ($system->getRightsForUser($login, $rights, $root, $level)) {
            if ($root) {
                $inputs.=wf_tag('p', false, 'glamour') . wf_CheckInput('rootuser', __('Root administrator'), true, true) . wf_tag('p', true) . wf_CleanDiv();
            } else {
                $inputs.=wf_tag('p', false, 'glamour') . wf_CheckInput('rootuser', __('Root administrator'), true, false) . wf_tag('p', true) . wf_CleanDiv();
                foreach ($system->rights_database as $right_id => $right_desc) {
                    //sorting inputs
                    if ((!isset($regperms[$right_id])) AND ( !isset($geoperms[$right_id])) AND ( !isset($sysperms[$right_id])) AND ( !isset($finperms[$right_id])) AND ( !isset($repperms[$right_id])) AND ( !isset($catvperms[$right_id])) AND ( !isset($branchesperms[$right_id]))) {
                        $miscinputs.=wf_CheckInput('_rights[' . $right_id . ']', $right_desc . ' - ' . $right_id, true, user_check_right($login, $right_id));
                    }
                    //user register rights
                    if (isset($regperms[$right_id])) {
                        $reginputs.=wf_CheckInput('_rights[' . $right_id . ']', $right_desc . ' - ' . $right_id, true, user_check_right($login, $right_id));
                    }
                    //geo rights     
                    if (isset($geoperms[$right_id])) {
                        $geoinputs.=wf_CheckInput('_rights[' . $right_id . ']', $right_desc . ' - ' . $right_id, true, user_check_right($login, $right_id));
                    }
                    //system config perms     
                    if (isset($sysperms[$right_id])) {
                        $sysinputs.=wf_CheckInput('_rights[' . $right_id . ']', $right_desc . ' - ' . $right_id, true, user_check_right($login, $right_id));
                    }
                    //financial inputs     
                    if (isset($finperms[$right_id])) {
                        $fininputs.=wf_CheckInput('_rights[' . $right_id . ']', $right_desc . ' - ' . $right_id, true, user_check_right($login, $right_id));
                    }

                    //reports rights     
                    if (isset($repperms[$right_id])) {
                        $repinputs.=wf_CheckInput('_rights[' . $right_id . ']', $right_desc . ' - ' . $right_id, true, user_check_right($login, $right_id));
                    }

                    //catv rights     
                    if (isset($catvperms[$right_id])) {
                        $catvinputs.=wf_CheckInput('_rights[' . $right_id . ']', $right_desc . ' - ' . $right_id, true, user_check_right($login, $right_id));
                    }
                    //branches inputs
                    if (isset($branchesperms[$right_id])) {
                        $branchesinputs.=wf_CheckInput('_rights[' . $right_id . ']', $right_desc . ' - ' . $right_id, true, user_check_right($login, $right_id));
                    }
                }
            }
        }


        //rights grid
        $label = wf_tag('h3') . __('Users registration') . wf_tag('h3', true);
        $tablecells = wf_TableCell($label . $reginputs, '', '', 'valign="top"');
        $label = wf_tag('h3') . __('System settings') . wf_tag('h3', true);
        $tablecells.=wf_TableCell($label . $sysinputs, '', '', 'valign="top"');
        $tablerows = wf_TableRow($tablecells);

        $label = wf_tag('h3') . __('Reports') . wf_tag('h3', true);
        $tablecells = wf_TableCell($label . $repinputs, '', '', 'valign="top"');
        $label = wf_tag('h3') . __('Financial management') . wf_tag('h3', true);
        $tablecells.=wf_TableCell($label . $fininputs, '', '', 'valign="top"');
        $tablerows.= wf_TableRow($tablecells);


        $label = wf_tag('h3') . __('CaTV') . wf_tag('h3', true);
        $tablecells = wf_TableCell($label . $catvinputs, '', '', 'valign="top"');
        $label = wf_tag('h3') . __('Geography') . wf_tag('h3', true);
        $tablecells.=wf_TableCell($label . $geoinputs, '', '', 'valign="top"');
        $tablerows.= wf_TableRow($tablecells);

        $label = wf_tag('h3') . __('Misc rights') . wf_tag('h3', true);
        $tablecells = wf_TableCell($label . $miscinputs, '', '', 'valign="top"');
        $label = wf_tag('h3') . __('Branches') . wf_tag('h3', true);
        $tablecells.= wf_TableCell($label . $branchesinputs, '', '', 'valign="top"');
        $tablerows.= wf_TableRow($tablecells);


        $rightsgrid = $inputs;
        $rightsgrid.=wf_Submit('Save') . wf_delimiter();


        $rightsgrid.= wf_TableBody($tablerows, '100%', 0, 'glamour');

        $permission_forms = wf_Form("", 'POST', $rightsgrid, '');
        $permission_forms.=wf_CleanDiv();
        $permission_forms.=wf_tag('br');

        //copy permissions form
        $copyinputs = wf_tag('h2') . __('Rights cloning') . wf_tag('h2', true);
        $copyinputs.= web_AdminLoginSelector($login);
        $copyinputs.= wf_HiddenInput('clonerightsnow', 'true');
        $copyinputs.= wf_Submit(__('Clone'));
        $copyform = wf_Form("", 'POST', $copyinputs, 'glamour');

        $permission_forms.=$copyform;



        show_window(__('Rights for') . ' ' . $login, $permission_forms);
    }

    /**
     * Shows administrator editing form
     * 
     * @param string $login
     */
    function web_admineditform($login) {
        $userdata = load_user_info($login);
        $frm = new InputForm('', 'post', __('Submit'));
        $frm->hidden('username', $userdata['username']);
        $frm->hidden('save', '1');
        $frm->addrow(__('Username'), $userdata['username']);
        $frm->addrow(__('New password') . '<br><small>' . __('if you do not want change password you must leave this field empty'), $frm->text_box('password', ''));
        $frm->addrow(__('Confirm password'), $frm->text_box('confirmation', ''));
        $frm->addrow(__('Nickname'), $frm->text_box('nickname', $userdata['nickname']));
        $frm->addrow(__('E-mail'), $frm->text_box('email', $userdata['email']));
        $frm->addrow(__('Hide e-mail from other users'), $frm->checkbox('userdata[hideemail]', '1', '', ((!isset($userdata['hideemail'])) ? true : ($userdata['hideemail']) ? true : false)));
        $frm->addrow(__('Time zone'), user_tz_select($userdata['tz'], 'userdata[tz]'));

        show_window(__('Edit') . ' ' . $login, $frm->show(true));
    }

    //if someone editing administrator permissions
    if (isset($_GET['edit'])) {
        $editname = vf($_GET['edit']);
        if (!empty($_POST['save'])) {
            if ($system->setRightsForUser($editname, @$_POST['_rights'], @$_POST['rootuser'], @$_POST['level'])) {
                show_window('', __('Rights changed'));
                log_register("CHANGE AdminPermissions {" . $editname . "}");
                rcms_redirect("?module=permissions&edit=" . $editname);
            } else {
                show_error(__('Error occurred'));
            }
        }

        web_permissions_editor($editname);
    }

    //if someone deleting admin
    if (isset($_GET['delete'])) {
        user_delete($_GET['delete']);
        log_register("DELETE AdminAccount {" . $_GET['delete'] . "}");
        rcms_redirect("?module=permissions");
    }

    //if editing admins password
    if (isset($_GET['passwd'])) {
        if (!empty($_POST['username']) && !empty($_POST['save'])) {
            $system->updateUser($_POST['username'], $_POST['nickname'], $_POST['password'], $_POST['confirmation'], $_POST['email'], $_POST['userdata'], true);
            log_register("CHANGE AdminAccountData {" . $_POST['username'] . "}");
            rcms_redirect("?module=permissions");
        }
        web_admineditform($_GET['passwd']);
    }

    //if cloning some rights
    if (wf_CheckPost(array('clonerightsnow', 'admincopyselector'))) {
        if (wf_CheckGet(array('edit'))) {
            $targetUser = $_GET['edit'];
            $sourceUser = $_POST['admincopyselector'];
            zb_PermissionsCopyAdminRights($sourceUser, $targetUser);
        }
    }

    show_window(__('Admins'), web_list_admins());

    show_window('', wf_Link('?module=adminreg', web_icon_create() . ' ' . __('Administrators registration'), true, 'ubButton'));
} else {
    show_error(__('You cant control this module'));
}
?>
