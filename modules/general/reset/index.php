<?php

if (cfr('RESET')) {

    if (isset($_GET['username'])) {
        $altCfg = $ubillingConfig->getAlter();
        $login = vf($_GET['username']);
        // reset user if need
        $billing->resetuser($login);
        log_register("RESET User (" . $login . ")");
        //resurrect if user is disconnected
        if (@$altCfg['RESETHARD']) {
            zb_UserResurrect($login);
        }
        rcms_redirect("?module=userprofile&username=" . $login);
    }
} else {
    show_error(__('You cant control this module'));
}
?>
