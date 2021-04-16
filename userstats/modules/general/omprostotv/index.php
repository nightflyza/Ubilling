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

        if (ubRouting::checkGet('newdev')) {
            $ptvIf->createNewDevice();
            ubRouting::nav($ptvIf::URL_ME);
        }

        if (ubRouting::checkGet('deldev')) {
            $ptvIf->deleteDevice(ubRouting::get('deldev'));
            ubRouting::nav($ptvIf::URL_ME);
        }

        if (ubRouting::checkGet('newpl')) {
            $ptvIf->createPlaylist();
            ubRouting::nav($ptvIf::URL_ME);
        }

        if (ubRouting::checkGet('delpl')) {
            $ptvIf->deletePlaylist(ubRouting::get('delpl'));
            ubRouting::nav($ptvIf::URL_ME);
        }

        if (ubRouting::checkGet('unsubscribe')) {
            $ptvIf->unsubscribe(ubRouting::get('unsubscribe'));
            ubRouting::nav($ptvIf::URL_ME);
        }

        if (ubRouting::checkGet('subscribe')) {
            $ptvIf->subscribe(ubRouting::get('subscribe'));
            ubRouting::nav($ptvIf::URL_ME);
        }

        show_window(__('Your subscriptions'), $ptvIf->renderSubscriptionDetails());
        $userUseService = $ptvIf->userUseService();
        if ($userUseService) {
            show_window(__('Devices'), $ptvIf->renderDevices());
            show_window(__('Playlists'), $ptvIf->renderPlaylists());
        }
        show_window(__('Available subscribtions'), $ptvIf->renderSubscribeForm());
    } else {
        show_window(__('Sorry'), __('You can not use this service'));
    }
} else {
    show_window(__('Sorry'), __('This module is disabled'));
}