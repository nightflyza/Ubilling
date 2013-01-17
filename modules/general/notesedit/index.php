<?php
if (cfr('NOTES')) {

if (isset ($_GET['username'])) {
    $login=vf($_GET['username']);
       // change notes if need
       if (isset ($_POST['newnotes'])) {
        $notes=$_POST['newnotes'];
        zb_UserDeleteNotes($login);
        zb_UserCreateNotes($login,$notes);
        rcms_redirect("?module=notesedit&username=".$login);
    }

    $current_notes=zb_UserGetnotes($login);
    $useraddress=zb_UserGetFullAddress($login).' ('.$login.')';


// Edit form construct
$fieldnames=array('fieldname1'=>__('Current notes'),'fieldname2'=>__('New notes'));
$fieldkey='newnotes';
$form=web_EditorStringDataForm($fieldnames, $fieldkey, $useraddress, $current_notes);
$form.=web_UserControls($login);

show_window(__('Edit notes'), $form);
}

} else {
      show_error(__('You cant control this module'));
}

?>
