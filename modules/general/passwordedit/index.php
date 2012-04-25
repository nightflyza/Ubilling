<?php
if (cfr('PASSWORD')) {

if (isset ($_GET['username'])) {
    $login=vf($_GET['username']);
       // change password  if need
       if (isset ($_POST['newpassword'])) {
        $password=$_POST['newpassword'];
        $billing->setpassword($login,$password);
        log_register('CHANGE Password '.$login.' ON '.$password);
        rcms_redirect("?module=passwordedit&username=".$login);
    }
    
    $alter_conf=rcms_parse_ini_file(CONFIG_PATH.'alter.ini');
    $current_password=zb_UserGetStargazerData($login);
    $current_password=$current_password['Password'];
    if ($alter_conf['PASSWORDSHIDE']) {
        $current_password=__('Hidden');
    }
    
    $useraddress=zb_UserGetFullAddress($login).' ('.$login.')';


// Edit form construct
$fieldnames=array('fieldname1'=>__('Current password'),'fieldname2'=>__('New password'));
$fieldkey='newpassword';
$form=web_EditorStringDataForm($fieldnames, $fieldkey, $useraddress, $current_password);
$form.=web_UserControls($login);
// show form
show_window(__('Edit password'), $form);
}

} else {
      show_error(__('You cant control this module'));
}

?>
