<?php

$altCfg = $ubillingConfig->getAlter();
if ($altCfg['JUNGEN_ENABLED']) {
    if (cfr('JUNGEN')) {
        if (wf_CheckGet(array('username'))) {
            $userLogin = $_GET['username'];
            $juncast = new JunCast();
            $juncast->terminateUser($userLogin);
            rcms_redirect('?module=userprofile&username=' . $userLogin);
        } else {
            show_error(__('Something went wrong'));
        }
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('This module is disabled'));
}