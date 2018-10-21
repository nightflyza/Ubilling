<?php

if (cfr('USERPROFILE')) {
    global $ubillingConfig;

    if ( $ubillingConfig->getAlterParam('SMS_SERVICES_ADVANCED_ENABLED') and wf_CheckGet(array('ajax')) ) {
        if ( wf_CheckPost(array('action')) == 'BindSMSSrv' ) {
            if ( wf_CheckPost(array('createrec')) ) {
                $tQuery = "INSERT INTO `sms_services_relations` (`sms_srv_id`, `user_login`)
                                                         VALUES ('" . $_POST['smssrvid'] . "', '" . $_POST['username'] . "')";
                nr_query($tQuery);
            } else {
                simple_update_field('sms_services_relations', 'sms_srv_id', $_POST['smssrvid'], "WHERE `user_login`='" . $_POST['username'] . "' ");
            }

            log_register("Prefered SMS service changed from [" . $_POST['oldsmssrvid'] . "] to [" . $_POST['smssrvid'] . "] for user (" . $_POST['username'] . ")");
        }
    }

    if (isset($_GET['username'])) {
        $login = vf($_GET['username']);
        $login = trim($login);
        try {
            $profile = new UserProfile($login);
            show_window(__('User profile'), $profile->render());
        } catch (Exception $exception) {
            show_window(__('Error'), __('Strange exeption') . ': ' . wf_tag('pre') . $exception->getMessage() . wf_tag('pre', true));
        }
    } else {
        throw new Exception('GET_NO_USERNAME');
    }
} else {
    show_error(__('Access denied'));
}
?>
