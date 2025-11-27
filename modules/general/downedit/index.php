<?php
if (cfr('DOWN')) {

if (isset ($_GET['username'])) {
    $login=vf($_GET['username']);
       // change down  if need
       if (isset ($_POST['newdown'])) {
        $down=$_POST['newdown'];
        $billing->setdown($login,$down);
        log_register('DOWN CHANGE ('.$login.') ON `'.$down.'`');
        rcms_redirect("?module=downedit&username=".$login);
    }

    $current_down=zb_UserGetStargazerData($login);
    $current_down=$current_down['Down'];
    $useraddress=zb_UserGetFullAddress($login).' ('.$login.')';


// Edit form construct
$fieldname=__('Current Down state');
$fieldkey='newdown';
$form=web_EditorTrigerDataForm($fieldname, $fieldkey, $useraddress, $current_down);
$form.=web_UserControls($login);
// show form
show_window(__('Edit Down'), $form);
}

} else {
      show_error(__('You cant control this module'));
}

?>
