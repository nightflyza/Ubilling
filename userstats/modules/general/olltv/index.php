<?php

$user_ip = zbs_UserDetectIp('debug');
$userLogin = zbs_UserGetLoginByIp($user_ip);
$us_config = zbs_LoadConfig();
if (@$us_config['OLLTV_ENABLED']) {
    $userData = zbs_UserGetStargazerData($userLogin);
    if (($userData['Passive'] == 0) AND ( $userData['Down'] == 0 )) {

        $ollTvIf = new OllTvInterface($userLogin);

        if ($ollTvIf->userHaveMobile()) {
            if (ubRouting::checkGet('unsubscribe')) {
                $ollTvIf->unsubscribe(ubRouting::get('unsubscribe'));
                ubRouting::nav($ollTvIf::URL_ME);
            }

            if (ubRouting::checkGet('subscribe')) {
                $ollTvIf->subscribe(ubRouting::get('subscribe'));
                ubRouting::nav($ollTvIf::URL_ME);
            }

            show_window(__('Your subscriptions'), $ollTvIf->renderSubscriptionDetails());
            $userUseService = $ollTvIf->userUseService();
            if ($userUseService) {
                show_window(__('Devices'), $ollTvIf->renderDevices());
            }
            show_window(__('Available subscribtions'), $ollTvIf->renderSubscribeForm());
        } else {
            show_window(__('Sorry'), __('You have no mobile number filled in your profile. Please contact your ISP.'));
        }
    } else {
        show_window(__('Sorry'), __('You can not use this service'));
    }
} else {
    show_window(__('Sorry'), __('This module is disabled'));
}