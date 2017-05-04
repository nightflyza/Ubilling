<?php
if (cfr('PASSIVE')) {

if (isset ($_GET['username'])) {
    $login=vf($_GET['username']);
       // change passive  if need
       if (isset ($_POST['newpassive'])) {
        $passive=$_POST['newpassive'];
        $billing->setpassive($login,$passive);
        log_register('CHANGE Passive ('.$login.') ON '.$passive);
        rcms_redirect("?module=passiveedit&username=".$login);
    }

    $current_passive=zb_UserGetStargazerData($login);
    $current_passive=$current_passive['Passive'];
    $useraddress=zb_UserGetFullAddress($login).' ('.$login.')';


// Edit form construct
$fieldname=__('Current passive state');
$fieldkey='newpassive';
$form=web_EditorTrigerDataForm($fieldname, $fieldkey, $useraddress, $current_passive);
$form.=web_UserControls($login);
// show form
show_window(__('Edit passive'), $form);
}

} else {
      show_error(__('You cant control this module'));
}

?>
