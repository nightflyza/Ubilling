<?php

$user_ip = zbs_UserDetectIp('debug');
$user_login = zbs_UserGetLoginByIp($user_ip);
$us_config = zbs_LoadConfig();

if (@$us_config['TRINITYTV_ENABLED']) {
    $userData = zbs_UserGetStargazerData($user_login);
    //Check for user active state
    if (($userData['Passive'] == 0) AND ( $userData['Down'] == 0 )) {
        $trinitytvFront = new TrinityTvFrontend();
        $trinitytvFront->setLogin($user_login);

        //try subscribe service
        if (la_CheckGet(array('subscribe'))) {

            $subscribeResult = $trinitytvFront->pushSubscribeRequest($_GET['subscribe']);

            if (!$subscribeResult) {
                rcms_redirect('?module=trinitytv');
            } else {
                show_window(__('Sorry'), __($subscribeResult));
            }
        }

        //try unsubscribe service
        if (la_CheckGet(array('unsubscribe'))) {
            $unsubscribeResult = $trinitytvFront->pushUnsubscribeRequest($_GET['unsubscribe']);
            if (!$unsubscribeResult) {
                rcms_redirect('?module=trinitytv');
            } else {
                show_window(__('Sorry'), __($unsubscribeResult));
            }
        }

        //view button if is some subscriptions here
        if ($trinitytvFront->haveSubscribtions()) {
            show_window(__('Your subscriptions'), $trinitytvFront->renderSubscribtions());
            show_window('', la_tag('br'));
        }

        //default sub/unsub form
        show_window(__('Available subscribtions'), $trinitytvFront->renderSubscribeForm());

    } else {
        show_window(__('Sorry'), __('You can not use this service'));
    }
} else {
    show_window(__('Sorry'), __('This module is disabled'));
}
?>