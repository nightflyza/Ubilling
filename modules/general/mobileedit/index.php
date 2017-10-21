<?php

if (cfr('MOBILE')) {

    if (isset($_GET['username'])) {
        $login = vf($_GET['username']);
        // change mobile if need
        if (isset($_POST['newmobile'])) {
            $mobile = $_POST['newmobile'];
            zb_UserChangeMobile($login, $mobile);
            rcms_redirect("?module=mobileedit&username=" . $login);
        }

        $current_mobile = zb_UserGetMobile($login);
        $useraddress = zb_UserGetFullAddress($login) . ' (' . $login . ')';


// Edit form construct
        $fieldnames = array('fieldname1' => __('Current mobile'), 'fieldname2' => __('New mobile'));
        $fieldkey = 'newmobile';
        $form = web_EditorStringDataForm($fieldnames, $fieldkey, $useraddress, $current_mobile, 'mobile');
        $form.=web_UserControls($login);

        show_window(__('Edit mobile'), $form);
    }
} else {
    show_error(__('You cant control this module'));
}
?>
