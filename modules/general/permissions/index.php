<?php
if (cfr('PERMISSIONS')) {
    
//$userdata = load_user_info('demo');
function web_list_admins() {
    $alladmins=rcms_scandir(USERS_PATH);
    $cells=  wf_TableCell(__('Admin'));
    $cells.= wf_TableCell(__('Actions'));
    $rows=  wf_TableRow($cells, 'row1');
    
    if (!empty ($alladmins)) {
        foreach ($alladmins as $eachadmin) {
            $actions=wf_JSAlert('?module=permissions&delete='.$eachadmin, web_delete_icon(), 'Removing this may lead to irreparable results');
            $actions.= wf_Link('?module=permissions&passwd='.$eachadmin, web_key_icon());
            $actions.= wf_Link('?module=permissions&edit='.$eachadmin, web_edit_icon('Rights'));
            
            $cells=  wf_TableCell($eachadmin);
            $cells.= wf_TableCell($actions);
            $rows.=  wf_TableRow($cells, 'row3');
        }
    }
    
    $form=  wf_TableBody($rows, '100%', '0', 'sortable');
    return($form);
 }
 
 function web_permissions_box($login) {
     global $system;
     $frm =new InputForm ('', 'post', __('Submit'));
     //$frm->hidden('rights', $_POST['rights']);
     $frm->hidden('save', '1');
     if($system->getRightsForUser($login, $rights, $root, $level)){
    if($root){
        $frm->addrow('', $frm->checkbox('rootuser', '1', __('Root administrator'), true));
    } else {
        $frm->addrow('', $frm->checkbox('rootuser', '1',__('Root administrator'), false));
        foreach ($system->rights_database as $right_id => $right_desc){
            $frm->addrow('', $frm->checkbox('_rights[' . $right_id . ']', '1', $right_desc, user_check_right($login, $right_id)));
        }
    }
    }
     show_window(__('Rights for').' '.$login,$frm->show(true));
 }
 
 
 // new custom permissions form
 function zb_PermissionGroup($groupname) {
     $path=CONFIG_PATH."permgroups.ini";
     $result=array();
     $rawdata=rcms_parse_ini_file($path);
     $rawperms=explode(',', $rawdata[$groupname]);
     if (!empty($groupname)) {
         $result=$rawperms;
         $result=array_flip($result);
     }
     return ($result);
 }
 
 
  function web_permissions_editor($login) {
     global $system;
     $regperms=  zb_PermissionGroup('USERREG');
     $geoperms=  zb_PermissionGroup('GEO');
     $sysperms=  zb_PermissionGroup('SYSTEM');
     $finperms=  zb_PermissionGroup('FINANCE');
     $repperms=  zb_PermissionGroup('REPORTS');
     $catvperms= zb_PermissionGroup('CATV');
     
     $reginputs='';
     $geoinputs='';
     $sysinputs='';
     $fininputs='';
     $repinputs='';
     $catvinputs='';
     $miscinputs='';
     
    
     $inputs=  wf_Link('?module=permissions', 'Back', true, 'ubButton').'<br>';
     
     $inputs.=wf_HiddenInput('save', '1');
     if($system->getRightsForUser($login, $rights, $root, $level)){
      if($root){
          $inputs.='<p class="glamour">'.wf_CheckInput('rootuser', __('Root administrator'), true, true).'</p><div style="clear:both;"></div>';
        } else {
          $inputs.='<p class="glamour">'.wf_CheckInput('rootuser', __('Root administrator'), true, false).'</p><div style="clear:both;"></div>';
        foreach ($system->rights_database as $right_id => $right_desc){
            //sorting inputs
            if ((!isset($regperms[$right_id])) AND (!isset($geoperms[$right_id])) AND (!isset($sysperms[$right_id])) AND (!isset($finperms[$right_id])) AND (!isset($repperms[$right_id])) AND (!isset($catvperms[$right_id]))) {
                $miscinputs.=wf_CheckInput('_rights[' . $right_id . ']', $right_desc, true, user_check_right($login, $right_id));
            }
            //user register rights
                if (isset($regperms[$right_id])) {
                    $reginputs.=wf_CheckInput('_rights[' . $right_id . ']', $right_desc, true, user_check_right($login, $right_id));
                }
           //geo rights     
                if (isset($geoperms[$right_id])) {
                    $geoinputs.=wf_CheckInput('_rights[' . $right_id . ']', $right_desc, true, user_check_right($login, $right_id));
                }
           //system config perms     
                if (isset($sysperms[$right_id])) {
                    $sysinputs.=wf_CheckInput('_rights[' . $right_id . ']', $right_desc, true, user_check_right($login, $right_id));
                }
           //financial inputs     
           if (isset($finperms[$right_id])) {
                    $fininputs.=wf_CheckInput('_rights[' . $right_id . ']', $right_desc, true, user_check_right($login, $right_id));
                }
                
           //reports rights     
           if (isset($repperms[$right_id])) {
                    $repinputs.=wf_CheckInput('_rights[' . $right_id . ']', $right_desc, true, user_check_right($login, $right_id));
                }
                
            //catv rights     
           if (isset($catvperms[$right_id])) {
                    $catvinputs.=wf_CheckInput('_rights[' . $right_id . ']', $right_desc, true, user_check_right($login, $right_id));
                }

                
                
            }
        }
    }
    
    
    //rights grid
    $label=  wf_tag('h3').__('Users registration').wf_tag('h3',true);
    $tablecells=wf_TableCell($label.$reginputs,'','','valign="top"');
    $label=wf_tag('h3').__('System settings').wf_tag('h3',true);
    $tablecells.=wf_TableCell($label.$sysinputs,'','','valign="top"');
    $label=wf_tag('h3').__('Reports').wf_tag('h3',true);
    $tablecells.=wf_TableCell($label.$repinputs,'','','valign="top"');
    $tablerows=  wf_TableRow($tablecells);
    
    $label=wf_tag('h3').__('Financial management').wf_tag('h3',true);
    $tablecells=wf_TableCell($label.$fininputs,'','','valign="top"');
    $label=wf_tag('h3').__('CaTV').wf_tag('h3',true);
    $tablecells.=wf_TableCell($label.$catvinputs,'','','valign="top"');
    $label=wf_tag('h3').__('Geography').wf_tag('h3',true);
    $tablecells.=wf_TableCell($label.$geoinputs,'','','valign="top"');
    $tablerows.=  wf_TableRow($tablecells);
    
    $label=wf_tag('h3').__('Misc rights').wf_tag('h3',true);
    $tablecells=wf_TableCell($label.$miscinputs,'','','valign="top"');
    $tablerows.=  wf_TableRow($tablecells);
    
    
    $rightsgrid=$inputs;
    $rightsgrid.=wf_Submit('Save').  wf_delimiter();
    
    
    $rightsgrid.= wf_TableBody($tablerows, '100%', 0, 'glamour');
    
    $permission_forms=  wf_Form("", 'POST', $rightsgrid, '');
    
    show_window(__('Rights for').' '.$login,$permission_forms);
    
 }
 
 function web_admineditform($login) {
     $userdata = load_user_info($login);
     $frm =new InputForm ('', 'post', __('Submit'));
     $frm->hidden('username', $userdata['username']);
     $frm->hidden('save', '1');
     $frm->addrow(__('Username'), $userdata['username']);
     $frm->addrow(__('New password') . '<br><small>' . __('if you do not want change password you must leave this field empty'), $frm->text_box('password', ''));
     $frm->addrow(__('Confirm password'), $frm->text_box('confirmation', ''));
     $frm->addrow(__('Nickname'), $frm->text_box('nickname', $userdata['nickname']));
     $frm->addrow(__('E-mail'), $frm->text_box('email', $userdata['email']));
     $frm->addrow(__('Hide e-mail from other users'), $frm->checkbox('userdata[hideemail]', '1', '', ((!isset($userdata['hideemail'])) ? true : ($userdata['hideemail']) ? true : false)));
     $frm->addrow(__('Time zone'), user_tz_select($userdata['tz'], 'userdata[tz]'));
    
      show_window(__('Edit').' '.$login, $frm->show(true));
 }

 //if someone editing administrator
 if (isset($_GET['edit'])) {
   $editname=vf($_GET['edit']);
   if(!empty($_POST['save'])){
    if($system->setRightsForUser($editname, @$_POST['_rights'], @$_POST['rootuser'], @$_POST['level'])) {
       show_window('',__('Rights changed'));
       log_register("CHANGE AdminPermissions ".$editname);
       rcms_redirect("?module=permissions&edit=".$editname);
    } else {
       show_error(__('Error occurred'));
    }
  }

     web_permissions_editor($editname);
     }
     
    //if someone deleting admin
     if (isset($_GET['delete'])) {
         user_delete($_GET['delete']);
         log_register("DELETE AdminAccount ".$_GET['delete']);
         rcms_redirect("?module=permissions");
     }
     
   //if editing admins password
     if (isset($_GET['passwd'])) {
         if(!empty($_POST['username']) && !empty($_POST['save'])){
              $system->updateUser($_POST['username'], $_POST['nickname'], $_POST['password'], $_POST['confirmation'], $_POST['email'], $_POST['userdata'], true);
              log_register("CHANGE AdminAccountData ".$_POST['username']);
              rcms_redirect("?module=permissions");
         }
         web_admineditform($_GET['passwd']);
        
     }

show_window(__('Admins'),web_list_admins());


} else {
      show_error(__('You cant control this module'));
}

?>
