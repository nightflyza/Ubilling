<?php

if (cfr('PASSWORD')) {
    $altCfg = $ubillingConfig->getAlter();
    if (ubRouting::checkGet('username')) {
        $login = ubRouting::get('username','login');
        // change password  if need
        if (ubRouting::checkPost('newpassword')) {
            $password = ubRouting::post('newpassword');
            if (!$altCfg['IGNORE_PASSWORD_UNIQUE']) {
                if (zb_CheckPasswordUnique($password)) {
                    $billing->setpassword($login, $password);
                    log_register('CHANGE Password (' . $login . ') ON `' . $password . '`');
                    ubRouting::nav("?module=passwordedit&username=" . $login);
                } else {
                    show_error(__('We do not recommend using the same password for different users. Try another.'));
                }
            } else {
                $billing->setpassword($login, $password);
                log_register('CHANGE Password (' . $login . ') ON `' . $password . '`');
                ubRouting::nav("?module=passwordedit&username=" . $login);
            }
        }

        $current_password = zb_UserGetStargazerData($login);
        $current_password = $current_password['Password'];

        $useraddress = zb_UserGetFullAddress($login) . ' (' . $login . ')';


        // Edit form construct
        $fieldnames = array('fieldname1' => __('Current password'), 'fieldname2' => __('New password'));
        $fieldkey = 'newpassword';
        $form = web_EditorStringDataFormPassword($fieldnames, $fieldkey, $useraddress, $current_password);
        // show form
        show_window(__('Edit password'), $form);

        //check non unique passwords
        if (!$altCfg['IGNORE_PASSWORD_UNIQUE']) {
            $duppasswords = zb_GetNonUniquePasswordUsers();
            if (!empty($duppasswords)) {
                show_window(__('These users have identical passwords'), web_UserArrayShower($duppasswords));
            }
        }

        show_window('', web_UserControls($login));
    }
} else {
    show_error(__('You cant control this module'));
}
