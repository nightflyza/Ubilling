<?php
if (cfr('REALNAME')) {

if (isset ($_GET['username'])) {
    $login=vf($_GET['username']);
       // change realname if need
       if (isset ($_POST['newrealname'])) {
        $realname=$_POST['newrealname'];
        zb_UserChangeRealName($login, $realname);
        rcms_redirect("?module=realnameedit&username=".$login);
    }

    $current_realname=zb_UserGetRealName($login);
    $useraddress=zb_UserGetFullAddress($login).' ('.$login.')';


// Edit form construct
$fieldnames=array('fieldname1'=>__('Current Real Name'),'fieldname2'=>__('New Real Name'));
$fieldkey='newrealname';
$form=web_EditorStringDataForm($fieldnames, $fieldkey, $useraddress, $current_realname);
$form.=web_UserControls($login);
// show form
show_window(__('Edit realname'), $form);
}

} else {
      show_error(__('You cant control this module'));
}

?>
