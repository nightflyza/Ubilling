<?php

$user_ip = zbs_UserDetectIp('debug');
$user_login = zbs_UserGetLoginByIp($user_ip);
$us_config = zbs_LoadConfig();

if (@$us_config['MG_ENABLED']) {

    $megogo = new MegogoFrontend();
    $megogo->setLogin($user_login);

//try subscribe service
    if (la_CheckGet(array('subscribe'))) {
        $subscribeResult = $megogo->pushSubscribeRequest($_GET['subscribe']);
        if (!$subscribeResult) {
            rcms_redirect('?module=megogo');
        } else {
            show_window(__('Sorry'), $subscribeResult);
        }
    }
//  try unsubscribe service 
    if (la_CheckGet(array('unsubscribe'))) {
        $unsubscribeResult = $megogo->pushUnsubscribeRequest($_GET['unsubscribe']);
        if (!$unsubscribeResult) {
            rcms_redirect('?module=megogo');
        } else {
            show_window(__('Sorry'), $unsubscribeResult);
        }
    }

    show_window(__('Available subscribtions'), $megogo->renderSubscribeForm());
    show_window('', la_Link($megogo->getAuthButtonURL(), __('Start view'), true, 'mgviewcontrol'));
    
} else {
    show_window(__('Sorry'), __('This module is disabled'));
}
?>