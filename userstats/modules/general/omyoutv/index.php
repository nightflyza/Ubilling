<?php

$user_ip = zbs_UserDetectIp('debug');
$userLogin = zbs_UserGetLoginByIp($user_ip);
$us_config = zbs_LoadConfig();

if (@$us_config['YOUTV_ENABLED']) {
    $userData = zbs_UserGetStargazerData($userLogin);
    //Check for user active state
    if (($userData['Passive'] == 0) AND ( $userData['Down'] == 0 )) {

            $ytvIf = new YTVInterface($userLogin);

            if (ubRouting::checkGet('unsubscribe',false)) {
                $ytvIf->unsubscribe(ubRouting::get('unsubscribe'));
                ubRouting::nav($ytvIf::URL_ME);
            }

            if (ubRouting::checkGet('subscribe',false)) {
                $ytvIf->subscribe(ubRouting::get('subscribe'));
                ubRouting::nav($ytvIf::URL_ME);
            }

            //render auth data if available
            $info  = $ytvIf->renderInfoForm();
            if(!empty($info)){
                show_window('', $info);
            }

            show_window(__('Available subscribtions'), $ytvIf->renderSubscribeForm());

            show_window(__('Your subscriptions'), $ytvIf->renderSubscriptionDetails());
            $userUseService = $ytvIf->userUseService();

      
    } else {
        show_window(__('Sorry'), __('You can not use this service'));
    }
} else {
    show_window(__('Sorry'), __('This module is disabled'));
}