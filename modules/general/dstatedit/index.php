<?php

if (cfr('DSTAT')) {
    $altCfg = $ubillingConfig->getAlter();
    if (@$altCfg['DSTAT_ENABLED']) {
        if (isset($_GET['username'])) {
            $login = vf($_GET['username']);
            // change dstat  if need
            if (isset($_POST['newdstat'])) {
                $dstat = $_POST['newdstat'];
                $billing->setdstat($login, $dstat);
                log_register('CHANGE dstat (' . $login . ') ON ' . $dstat);
                rcms_redirect("?module=dstatedit&username=" . $login);
            }

            $current_dstat = zb_UserGetStargazerData($login);
            $current_dstat = $current_dstat['DisabledDetailStat'];
            $useraddress = zb_UserGetFullAddress($login) . ' (' . $login . ')';


// Edit form construct
            $fieldname = __('Disable detailed stats');
            $fieldkey = 'newdstat';
            $form = web_EditorTrigerDataForm($fieldname, $fieldkey, $useraddress, $current_dstat);
            $form .= web_UserControls($login);
// show form
            show_window(__('Edit detailed stats'), $form);
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('You cant control this module'));
}
?>
