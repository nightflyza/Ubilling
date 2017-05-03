<?php

if (cfr('SCINFO')) {
    if (isset($_GET['username'])) {
        $login = $_GET['username'];
        $config = $ubillingConfig->getBilling();
        $alterconfig = $ubillingConfig->getAlter();
        if (isset($alterconfig['SC'])) {
            if (!empty($alterconfig['SC'])) {
                $sc_path = $alterconfig['SC'];
                $sudo_path = $config['SUDO'];
                $userdata = zb_UserGetStargazerData($login);
                $user_ip = $userdata['IP'];
                $command = $sudo_path . ' ' . $sc_path . ' show ' . $user_ip;
                $sc_result = wf_tag('pre') . shell_exec($command) . wf_tag('pre', true);
                show_window(__('Show SC shaper info'), $sc_result);
                show_window('', web_UserControls($login));
            } else {
                show_window(__('Error'), __('This module disabled'));
            }
        } else {
            show_window(__('Error'), __('This module disabled'));
        }
    }
} else {
    show_error(__('You cant control this module'));
}
?>