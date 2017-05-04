<?php
if (cfr('EMAIL')) {

if (isset ($_GET['username'])) {
    $login=vf($_GET['username']);
       // change mail if need
       if (isset ($_POST['newmail'])) {
        $mail=$_POST['newmail'];
        zb_UserChangeEmail($login, $mail);
        rcms_redirect("?module=mailedit&username=".$login);
    }

    $current_mail=zb_UserGetEmail($login);
    $useraddress=zb_UserGetFullAddress($login).' ('.$login.')';


// Edit form construct
$fieldnames=array('fieldname1'=>__('Current mail'),'fieldname2'=>__('New mail'));
$fieldkey='newmail';
$form=web_EditorStringDataForm($fieldnames, $fieldkey, $useraddress, $current_mail);
$form.=web_UserControls($login);

show_window(__('Edit mail'), $form);
}

} else {
      show_error(__('You cant control this module'));
}

?>
