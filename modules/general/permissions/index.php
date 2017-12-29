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

        $reginputsnames = '';
        $eginputsnames = '';
        $geoinputsnames = '';
        $sysinputsnames = '';
        $fininputsnames = '';
        $repinputsnames = '';
        $catvinputsnames = '';
        $branchesinputsnames = '';
        $miscinputsnames = '';

        $reginputsallchecked = true;
        $eginputsallchecked = true;
        $geoinputsallchecked = true;
        $sysinputsallchecked = true;
        $fininputsallchecked = true;
        $repinputsallchecked = true;
        $catvinputsallchecked = true;
        $branchesinputsallchecked = true;
        $miscinputsallchecked = true;

        $inputs = wf_BackLink('?module=permissions') . wf_delimiter();

        //$root = false;

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
                        $miscinputsnames.= '_rights[' . $right_id . '],';

                        if ( !user_check_right($login, $right_id) ) $miscinputsallchecked = false;
                    }
                    //user register rights
                    if (isset($regperms[$right_id])) {
                        $reginputs.=wf_CheckInput('_rights[' . $right_id . ']', $right_desc . ' - ' . $right_id, true, user_check_right($login, $right_id));
                        $reginputsnames.= '_rights[' . $right_id . '],';

                        if ( !user_check_right($login, $right_id) ) $reginputsallchecked = false;
                    }
                    //geo rights     
                    if (isset($geoperms[$right_id])) {
                        $geoinputs.=wf_CheckInput('_rights[' . $right_id . ']', $right_desc . ' - ' . $right_id, true, user_check_right($login, $right_id));
                        $geoinputsnames.= '_rights[' . $right_id . '],';

                        if ( !user_check_right($login, $right_id) ) $geoinputsallchecked = false;
                    }
                    //system config perms     
                    if (isset($sysperms[$right_id])) {
                        $sysinputs.=wf_CheckInput('_rights[' . $right_id . ']', $right_desc . ' - ' . $right_id, true, user_check_right($login, $right_id));
                        $sysinputsnames.= '_rights[' . $right_id . '],';

                        if ( !user_check_right($login, $right_id) ) $sysinputsallchecked = false;
                    }
                    //financial inputs     
                    if (isset($finperms[$right_id])) {
                        $fininputs.=wf_CheckInput('_rights[' . $right_id . ']', $right_desc . ' - ' . $right_id, true, user_check_right($login, $right_id));
                        $fininputsnames.= '_rights[' . $right_id . '],';

                        if ( !user_check_right($login, $right_id) ) $fininputsallchecked = false;
                    }

                    //reports rights     
                    if (isset($repperms[$right_id])) {
                        $repinputs.=wf_CheckInput('_rights[' . $right_id . ']', $right_desc . ' - ' . $right_id, true, user_check_right($login, $right_id));
                        $repinputsnames.= '_rights[' . $right_id . '],';

                        if ( !user_check_right($login, $right_id) ) $repinputsallchecked = false;
                    }

                    //catv rights     
                    if (isset($catvperms[$right_id])) {
                        $catvinputs.=wf_CheckInput('_rights[' . $right_id . ']', $right_desc . ' - ' . $right_id, true, user_check_right($login, $right_id));
                        $catvinputsnames.= '_rights[' . $right_id . '],';

                        if ( !user_check_right($login, $right_id) ) $catvinputsallchecked = false;
                    }
                    //branches inputs
                    if (isset($branchesperms[$right_id])) {
                        $branchesinputs.=wf_CheckInput('_rights[' . $right_id . ']', $right_desc . ' - ' . $right_id, true, user_check_right($login, $right_id));
                        $branchesinputsnames.= '_rights[' . $right_id . '],';

                        if ( !user_check_right($login, $right_id) ) $branchesinputsallchecked = false;
                    }
                }
            }
        }


        //rights grid
        $CheckLabelCaption = ($reginputsallchecked) ? 'Uncheck all' : 'Check all';
        $inputs .= wf_HiddenInput('reginputsnames', $reginputsnames);
        $label = wf_tag('h3') . __('Users registration') . '&emsp;&emsp;&emsp;&emsp;';
        $label .= wf_CheckInput('reginputscheck', __($CheckLabelCaption), true, $reginputsallchecked);
        $label .= wf_tag('h3', true);
        $label .= wf_tag('script', false, '', 'type="text/javascript"');
        /*if ($root) {
            $label .= '$(\'[name=reginputscheck]\').css(\'visibility\', \'hidden\');
                      $("label[for=\'"+$(\'[name=reginputscheck]\').attr("id")+"\']").css(\'visibility\', \'hidden\');';
        }*/
        $label .= '$(\'[name=reginputscheck]\').change( {InputNamesList : $(\'input[name=reginputsnames]\').val(), CheckVal : false},
                                                          function(EventObject) {
                                                                EventObject.data.CheckVal = $(this).is(\':checked\');
                                                                var LabelText = (EventObject.data.CheckVal) ? \'Uncheck all\' : \'Check all\';
                                                                $("label[for=\'"+$(this).attr("id")+"\']").html(LabelText);
                                                                checkThemAll(EventObject.data.InputNamesList, EventObject.data.CheckVal); 
                                                          } );';
        $label .= wf_tag('script', true);
        $tablecells = wf_TableCell($label . $reginputs, '', '', 'valign="top"');


        $CheckLabelCaption = ($sysinputsallchecked) ? 'Uncheck all' : 'Check all';
        $inputs .= wf_HiddenInput('sysinputsnames', $sysinputsnames);
        $label = wf_tag('h3') . __('System settings') . '&emsp;&emsp;&emsp;&emsp;';
        $label .= wf_CheckInput('sysinputscheck', __($CheckLabelCaption), true, $sysinputsallchecked);
        $label .= wf_tag('h3', true);
        $label .= wf_tag('script', false, '', 'type="text/javascript"');
        /*if ($root) {
            $label .= '$(\'[name=sysinputscheck]\').css(\'visibility\', \'hidden\');
                      $("label[for=\'"+$(\'[name=sysinputscheck]\').attr("id")+"\']").css(\'visibility\', \'hidden\');';
        }*/
        $label .= '$(\'[name=sysinputscheck]\').change( {InputNamesList : $(\'input[name=sysinputsnames]\').val(), CheckVal : false},
                                                          function(EventObject) {
                                                                EventObject.data.CheckVal = $(this).is(\':checked\');
                                                                var LabelText = (EventObject.data.CheckVal) ? \'Uncheck all\' : \'Check all\';
                                                                $("label[for=\'"+$(this).attr("id")+"\']").html(LabelText);
                                                                checkThemAll(EventObject.data.InputNamesList, EventObject.data.CheckVal); 
                                                          } );';
        $label .= wf_tag('script', true);
        $tablecells .=wf_TableCell($label . $sysinputs, '', '', 'valign="top"');
        $tablerows = wf_TableRow($tablecells);


        $CheckLabelCaption = ($repinputsallchecked) ? 'Uncheck all' : 'Check all';
        $inputs .= wf_HiddenInput('repinputsnames', $repinputsnames);
        $label = wf_tag('h3') . __('Reports') . '&emsp;&emsp;&emsp;&emsp;';
        $label .= wf_CheckInput('repinputscheck', __($CheckLabelCaption), true, $repinputsallchecked);
        $label .= wf_tag('h3', true);
        $label .= wf_tag('script', false, '', 'type="text/javascript"');
        /*if ($root) {
            $label .= '$(\'[name=repinputscheck]\').css(\'visibility\', \'hidden\');
                      $("label[for=\'"+$(\'[name=repinputscheck]\').attr("id")+"\']").css(\'visibility\', \'hidden\');';
        }*/
        $label .= '$(\'[name=repinputscheck]\').change( {InputNamesList : $(\'input[name=repinputsnames]\').val(), CheckVal : false},
                                                          function(EventObject) {
                                                                EventObject.data.CheckVal = $(this).is(\':checked\');
                                                                var LabelText = (EventObject.data.CheckVal) ? \'Uncheck all\' : \'Check all\';
                                                                $("label[for=\'"+$(this).attr("id")+"\']").html(LabelText);
                                                                checkThemAll(EventObject.data.InputNamesList, EventObject.data.CheckVal); 
                                                          } );';
        $label .= wf_tag('script', true);
        $tablecells = wf_TableCell($label . $repinputs, '', '', 'valign="top"');


        $CheckLabelCaption = ($fininputsallchecked) ? 'Uncheck all' : 'Check all';
        $inputs .= wf_HiddenInput('fininputsnames', $fininputsnames);
        $label = wf_tag('h3') . __('Financial management') . '&emsp;&emsp;&emsp;&emsp;';
        $label .= wf_CheckInput('fininputscheck', __($CheckLabelCaption), true, $fininputsallchecked);
        $label .= wf_tag('h3', true);
        $label .= wf_tag('script', false, '', 'type="text/javascript"');
        /*if ($root) {
            $label .= '$(\'[name=fininputsnames]\').css(\'visibility\', \'hidden\');
                      $("label[for=\'"+$(\'[name=fininputsnames]\').attr("id")+"\']").css(\'visibility\', \'hidden\');';
        }*/
        $label .= '$(\'[name=fininputscheck]\').change( {InputNamesList : $(\'input[name=fininputsnames]\').val(), CheckVal : false},
                                                          function(EventObject) {
                                                                EventObject.data.CheckVal = $(this).is(\':checked\');
                                                                var LabelText = (EventObject.data.CheckVal) ? \'Uncheck all\' : \'Check all\';
                                                                $("label[for=\'"+$(this).attr("id")+"\']").html(LabelText);
                                                                checkThemAll(EventObject.data.InputNamesList, EventObject.data.CheckVal); 
                                                          } );';
        $label .= wf_tag('script', true);
        $tablecells .=wf_TableCell($label . $fininputs, '', '', 'valign="top"');
        $tablerows .= wf_TableRow($tablecells);


        $CheckLabelCaption = ($catvinputsallchecked) ? 'Uncheck all' : 'Check all';
        $inputs .= wf_HiddenInput('catvinputsnames', $catvinputsnames);
        $label = wf_tag('h3') . __('CaTV') . '&emsp;&emsp;&emsp;&emsp;';
        $label .= wf_CheckInput('catvinputscheck', __($CheckLabelCaption), true, $catvinputsallchecked);
        $label .= wf_tag('h3', true);
        $label .= wf_tag('script', false, '', 'type="text/javascript"');
        /*if ($root) {
            $label .= '$(\'[name=catvinputscheck]\').css(\'visibility\', \'hidden\');
                      $("label[for=\'"+$(\'[name=catvinputscheck]\').attr("id")+"\']").css(\'visibility\', \'hidden\');';
        }*/
        $label .= '$(\'[name=catvinputscheck]\').change( {InputNamesList : $(\'input[name=catvinputsnames]\').val(), CheckVal : false},
                                                          function(EventObject) {
                                                                EventObject.data.CheckVal = $(this).is(\':checked\');
                                                                var LabelText = (EventObject.data.CheckVal) ? \'Uncheck all\' : \'Check all\';
                                                                $("label[for=\'"+$(this).attr("id")+"\']").html(LabelText);
                                                                checkThemAll(EventObject.data.InputNamesList, EventObject.data.CheckVal); 
                                                          } );';
        $label .= wf_tag('script', true);
        $tablecells = wf_TableCell($label . $catvinputs, '', '', 'valign="top"');


        $CheckLabelCaption = ($geoinputsallchecked) ? 'Uncheck all' : 'Check all';
        $inputs .= wf_HiddenInput('geoinputsnames', $geoinputsnames);
        $label = wf_tag('h3') . __('Geography') . '&emsp;&emsp;&emsp;&emsp;';
        $label .= wf_CheckInput('geoinputscheck', __($CheckLabelCaption), true, $geoinputsallchecked);
        $label .= wf_tag('h3', true);
        $label .= wf_tag('script', false, '', 'type="text/javascript"');
        $label .= '$(\'[name=geoinputscheck]\').change( {InputNamesList : $(\'input[name=geoinputsnames]\').val(), CheckVal : false},
                                                          function(EventObject) {
                                                                EventObject.data.CheckVal = $(this).is(\':checked\');
                                                                var LabelText = (EventObject.data.CheckVal) ? \'Uncheck all\' : \'Check all\';
                                                                $("label[for=\'"+$(this).attr("id")+"\']").html(LabelText);
                                                                checkThemAll(EventObject.data.InputNamesList, EventObject.data.CheckVal); 
                                                          } );';
        $label .= wf_tag('script', true);
        $tablecells .=wf_TableCell($label . $geoinputs, '', '', 'valign="top"');
        $tablerows .= wf_TableRow($tablecells);


        $CheckLabelCaption = ($miscinputsallchecked) ? 'Uncheck all' : 'Check all';
        $inputs .= wf_HiddenInput('miscinputsnames', $miscinputsnames);
        $label = wf_tag('h3') . __('Misc rights') . '&emsp;&emsp;&emsp;&emsp;';
        $label .= wf_CheckInput('miscinputscheck', __($CheckLabelCaption), true, $miscinputsallchecked);
        $label .= wf_tag('h3', true);
        $label .= wf_tag('script', false, '', 'type="text/javascript"');
        $label .= '$(\'[name=miscinputscheck]\').change( {InputNamesList : $(\'input[name=miscinputsnames]\').val(), CheckVal : false},
                                                          function(EventObject) {
                                                                EventObject.data.CheckVal = $(this).is(\':checked\');
                                                                var LabelText = (EventObject.data.CheckVal) ? \'Uncheck all\' : \'Check all\';
                                                                $("label[for=\'"+$(this).attr("id")+"\']").html(LabelText);
                                                                checkThemAll(EventObject.data.InputNamesList, EventObject.data.CheckVal); 
                                                          } );';
        $label .= wf_tag('script', true);
        $tablecells = wf_TableCell($label . $miscinputs, '', '', 'valign="top"');


        $CheckLabelCaption = ($branchesinputsallchecked) ? 'Uncheck all' : 'Check all';
        $inputs .= wf_HiddenInput('branchesinputsnames', $branchesinputsnames);
        $label = wf_tag('h3') . __('Branches') . '&emsp;&emsp;&emsp;&emsp;';
        $label .= wf_CheckInput('branchesinputscheck', __($CheckLabelCaption), true, $branchesinputsallchecked);
        $label .= wf_tag('h3', true);
        $label .= wf_tag('script', false, '', 'type="text/javascript"');
        $label .= '$(\'[name=branchesinputscheck]\').change( {InputNamesList : $(\'input[name=branchesinputsnames]\').val(), CheckVal : false},
                                                          function(EventObject) {
                                                                EventObject.data.CheckVal = $(this).is(\':checked\');
                                                                var LabelText = (EventObject.data.CheckVal) ? \'Uncheck all\' : \'Check all\';
                                                                $("label[for=\'"+$(this).attr("id")+"\']").html(LabelText);
                                                                checkThemAll(EventObject.data.InputNamesList, EventObject.data.CheckVal); 
                                                          } );';
        $label .= wf_tag('script', true);
        $tablecells .= wf_TableCell($label . $branchesinputs, '', '', 'valign="top"');
        $tablerows .= wf_TableRow($tablecells);


        $inputs .= wf_tag('script', false, '', 'type="text/javascript"');
        $inputs .= 'function checkThemAll(InputNamesList, CheckVal) {                        
                        InputNamesList = InputNamesList.substring(0, InputNamesList.length - 1);                        
                        var ElemArray = InputNamesList.split(",");                        
                        ElemArray.forEach( function(Item, Index) { $(\'[name="\'+Item+\'"]\').prop(\'checked\', CheckVal); } );                        
                    } ';

        $inputs .= wf_tag('script', true);

        $rightsgrid = $inputs;
        $rightsgrid .=wf_Submit('Save') . wf_delimiter();

        $rightsgrid .= wf_TableBody($tablerows, '100%', 0, 'glamour');

        if ($root) {
            $rightsgrid .= wf_tag('script', false, '', 'type="text/javascript"');
            $rightsgrid .= '$(\'[name=reginputscheck]\').css(\'visibility\', \'hidden\');
                            $("label[for=\'"+$(\'[name=reginputscheck]\').attr("id")+"\']").css(\'visibility\', \'hidden\');
                        
                            $(\'[name=sysinputscheck]\').css(\'visibility\', \'hidden\');
                            $("label[for=\'"+$(\'[name=sysinputscheck]\').attr("id")+"\']").css(\'visibility\', \'hidden\');
                          
                            $(\'[name=repinputscheck]\').css(\'visibility\', \'hidden\');
                            $("label[for=\'"+$(\'[name=repinputscheck]\').attr("id")+"\']").css(\'visibility\', \'hidden\');
                          
                            $(\'[name=fininputscheck]\').css(\'visibility\', \'hidden\');
                            $("label[for=\'"+$(\'[name=fininputscheck]\').attr("id")+"\']").css(\'visibility\', \'hidden\');
                          
                            $(\'[name=catvinputscheck]\').css(\'visibility\', \'hidden\');
                            $("label[for=\'"+$(\'[name=catvinputscheck]\').attr("id")+"\']").css(\'visibility\', \'hidden\');
                          
                            $(\'[name=geoinputscheck]\').css(\'visibility\', \'hidden\');
                            $("label[for=\'"+$(\'[name=geoinputscheck]\').attr("id")+"\']").css(\'visibility\', \'hidden\');
                            
                            $(\'[name=miscinputscheck]\').css(\'visibility\', \'hidden\');
                            $("label[for=\'"+$(\'[name=miscinputscheck]\').attr("id")+"\']").css(\'visibility\', \'hidden\');
                            
                            $(\'[name=branchesinputscheck]\').css(\'visibility\', \'hidden\');
                            $("label[for=\'"+$(\'[name=branchesinputscheck]\').attr("id")+"\']").css(\'visibility\', \'hidden\');';

            $rightsgrid .= wf_tag('script', true);
        }

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
