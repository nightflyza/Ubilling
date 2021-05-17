<?php

$user_ip = zbs_UserDetectIp('debug');
$userLogin = zbs_UserGetLoginByIp($user_ip);
$us_config = zbs_LoadConfig();

if (@$us_config['YOUTV_ENABLED']) {
    require_once('modules/engine/api.youtv.php');
    $userData = zbs_UserGetStargazerData($userLogin);
    //Check for user active state
    if (($userData['Passive'] == 0) AND ( $userData['Down'] == 0 )) {
        $ytvIf = new YTVInterface($userLogin);

        if (ubRouting::checkGet('unsubscribe')) {
            $ytvIf->unsubscribe(ubRouting::get('unsubscribe'));
            ubRouting::nav($ytvIf::URL_ME);
        }

        if (ubRouting::checkGet('subscribe')) {
            $ytvIf->subscribe(ubRouting::get('subscribe'));
            ubRouting::nav($ytvIf::URL_ME);
        }

        show_window(__('Your subscriptions'), $ytvIf->renderSubscriptionDetails());
        $userUseService = $ytvIf->userUseService();

        show_window(__('Available subscribtions'), $ytvIf->renderSubscribeForm());
    } else {
        show_window(__('Sorry'), __('You can not use this service'));
    }
} else {
    show_window(__('Sorry'), __('This module is disabled'));
}