<?php

if (cfr('PHONE')) {

    if (isset($_GET['username'])) {
        $login = vf($_GET['username']);
        // change phone routine
        if (isset($_POST['newphone'])) {
            $phone = $_POST['newphone'];
            zb_UserChangePhone($login, $phone);
            rcms_redirect("?module=phoneedit&username=" . $login);
        }

        $current_phone = zb_UserGetPhone($login);
        $user_address = zb_UserGetFullAddress($login) . ' (' . $login . ')';


        // Edit form construct
        $fieldnames = array('fieldname1' => __('Current phone'), 'fieldname2' => __('New Phone'));
        $fieldkey = 'newphone';
        $form = web_EditorStringDataForm($fieldnames, $fieldkey, $user_address, $current_phone);

        $form.=web_UserControls($login);
        show_window(__('Edit phone'), $form);
    }
} else {
    show_error(__('You cant control this module'));
}
?>
