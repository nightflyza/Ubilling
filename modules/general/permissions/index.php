<?php
if (cfr('PERMISSIONS')) {
    
//$userdata = load_user_info('demo');
function web_list_admins() {
    $alladmins=rcms_scandir(USERS_PATH);
    $form='<table width="100%" class="sortable" border="0">';
    $form.='<tr class="row1">
                <td>
                '.__('Admin').'
                </td>
                <td>
                '.__('Actions').'
                </td>
                </tr>
                ';
    if (!empty ($alladmins)) {
        foreach ($alladmins as $eachadmin) {
            $form.='<tr class="row3">
                <td>
                '.$eachadmin.'
                </td>
                <td>
               '.  wf_JSAlert('?module=permissions&delete='.$eachadmin, web_delete_icon(), 'Removing this may lead to irreparable results').'
                <a href="?module=permissions&passwd='.$eachadmin.'">'.  web_key_icon().'</a> 
                <a href="?module=permissions&edit='.$eachadmin.'">'.web_edit_icon('Rights').'</a>
                </td>
                </tr>
                ';
        }
    }
    $form.='</table>';
    return($form);
 }
 
 function web_permissions_box($login) {
     global $system;
     $frm =new InputForm ('', 'post', __('Submit'));
     //$frm->hidden('rights', $_POST['rights']);
     $frm->hidden('save', '1');
     if($system->getRightsForUser($login, $rights, $root, $level)){
    if($root){
        $frm->addrow(__('Root administrator'), $frm->checkbox('rootuser', '1', '', true));
    } else {
        $frm->addrow(__('Root administrator'), $frm->checkbox('rootuser', '1', '', false));
        foreach ($system->rights_database as $right_id => $right_desc){
            $frm->addrow($right_desc, $frm->checkbox('_rights[' . $right_id . ']', '1', '', user_check_right($login, $right_id)));
        }
    }
    }
     show_window(__('Rights for').' '.$login,$frm->show(true));
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
    } else {
       show_error(__('Error occurred'));
    }
  }

     web_permissions_box($editname);
     }
     
    //if someone deleting admin
     if (isset($_GET['delete'])) {
         user_delete($_GET['delete']);
         rcms_redirect("?module=permissions");
     }
     
   //if editing admins password
     if (isset($_GET['passwd'])) {
         if(!empty($_POST['username']) && !empty($_POST['save'])){
              $system->updateUser($_POST['username'], $_POST['nickname'], $_POST['password'], $_POST['confirmation'], $_POST['email'], $_POST['userdata'], true);
              rcms_redirect("?module=permissions");
         }
         web_admineditform($_GET['passwd']);
        
     }

show_window(__('Admins'),web_list_admins());


} else {
      show_error(__('You cant control this module'));
}

?>
