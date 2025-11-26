<?php
if (cfr('ALWAYSONLINE')) {

if (isset ($_GET['username'])) {
    $login=vf($_GET['username']);
       // change alwaysonline  if need
       if (isset ($_POST['newalwaysonline'])) {
        $alwaysonline=$_POST['newalwaysonline'];
        $billing->setao($login,$alwaysonline);
        log_register('USER ALWAYSONLINE CHANGE ('.$login.') ON `'.$alwaysonline.'`');
        rcms_redirect("?module=aoedit&username=".$login);
    }

    $current_alwaysonline=zb_UserGetStargazerData($login);
    $current_alwaysonline=$current_alwaysonline['AlwaysOnline'];
    $useraddress=zb_UserGetFullAddress($login).' ('.$login.')';


// Edit form construct
$fieldname=__('Current AlwaysOnline state');
$fieldkey='newalwaysonline';
$form=web_EditorTrigerDataForm($fieldname, $fieldkey, $useraddress, $current_alwaysonline);
$form.=web_UserControls($login);
// show form
show_window(__('Edit AlwaysOnline'), $form);
}

} else {
      show_error(__('You cant control this module'));
}


