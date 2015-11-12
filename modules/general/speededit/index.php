<?php
if (cfr('USERSPEED')) {

if (isset ($_GET['username'])) {
    $login=vf($_GET['username']);
       // change speed if need
       if (isset ($_POST['newspeed'])) {
        $speed=$_POST['newspeed'];
        zb_UserDeleteSpeedOverride($login);
        zb_UserCreateSpeedOverride($login, $speed);
        $billing->resetuser($login);
        log_register("RESET User (".$login.")");
        rcms_redirect("?module=speededit&username=".$login);
    }

    $current_speed=zb_UserGetSpeedOverride($login);
    $useraddress=zb_UserGetFullAddress($login).' ('.$login.')';


// Edit form construct
$fieldnames=array('fieldname1'=>__('Current speed override'),'fieldname2'=>__('New speed override'));
$fieldkey='newspeed';
$form=web_EditorStringDataForm($fieldnames, $fieldkey, $useraddress, $current_speed);
$form.=web_UserControls($login);

show_window(__('Edit speed override'), $form);
}

} else {
      show_error(__('You cant control this module'));
}

?>
