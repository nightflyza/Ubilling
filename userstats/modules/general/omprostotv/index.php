<?php

$user_ip = zbs_UserDetectIp('debug');
$userLogin = zbs_UserGetLoginByIp($user_ip);
$us_config = zbs_LoadConfig();

if (@$us_config['PROSTOTV_ENABLED']) {
    require_once('modules/engine/api.prostotv.php');
    $userData = zbs_UserGetStargazerData($userLogin);
    //Check for user active state
    if (($userData['Passive'] == 0) AND ( $userData['Down'] == 0 )) {
        $ptvIf = new PTVInterface($userLogin);

        show_window(__('Your subscriptions'), $ptvIf->renderSubscriptionDetails());
        show_window(__('Available subscribtions'), $ptvIf->renderSubscribeForm());
    } else {
        show_window(__('Sorry'), __('You can not use this service'));
    }
} else {
    show_window(__('Sorry'), __('This module is disabled'));
}