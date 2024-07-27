<?php

if (cfr('NOTES')) {
    if (ubRouting::checkGet('username')) {
        $login = ubRouting::get('username', 'login');
        // change notes if need
        if (ubRouting::checkPost('newnotes', false)) {
            $notes = ubRouting::post('newnotes', 'safe');
            zb_UserDeleteNotes($login);
            zb_UserCreateNotes($login, $notes);
            rcms_redirect("?module=notesedit&username=" . $login);
        }

        $current_notes = zb_UserGetnotes($login);
        $useraddress = zb_UserGetFullAddress($login) . ' (' . $login . ')';


        // Edit form construct
        $fieldnames = array('fieldname1' => __('Current notes'), 'fieldname2' => __('New notes'));
        $fieldkey = 'newnotes';
        $form = web_EditorStringDataForm($fieldnames, $fieldkey, $useraddress, $current_notes);


        show_window(__('Edit notes'), $form);

        //additional notes
        $altCfg = $ubillingConfig->getAlter();
        if ($altCfg['ADCOMMENTS_ENABLED']) {
            $adcomments = new ADcomments('USERNOTES');
            show_window(__('Additional comments'), $adcomments->renderComments($login));
        }
        //user controls here
        show_window('', web_UserControls($login));
    }
} else {
    show_error(__('You cant control this module'));
}
