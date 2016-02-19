<?php

$user_ip = zbs_UserDetectIp('debug');
$user_login = zbs_UserGetLoginByIp($user_ip);
$us_config = zbs_LoadConfig();

if (@$us_config['MG_ENABLED']) {
    $userData = zbs_UserGetStargazerData($user_login);
    //Check for user active state
    if (($userData['Passive'] == 0) AND ( $userData['Down'] == 0 )) {
        $megogo = new MegogoFrontend();
        $megogo->setLogin($user_login);

//try subscribe service
        if (la_CheckGet(array('subscribe'))) {
            $subscribeResult = $megogo->pushSubscribeRequest($_GET['subscribe']);
            if (!$subscribeResult) {
                rcms_redirect('?module=megogo');
            } else {
                show_window(__('Sorry'), __($subscribeResult));
            }
        }
//  try unsubscribe service 
        if (la_CheckGet(array('unsubscribe'))) {
            $unsubscribeResult = $megogo->pushUnsubscribeRequest($_GET['unsubscribe']);
            if (!$unsubscribeResult) {
                rcms_redirect('?module=megogo');
            } else {
                show_window(__('Sorry'), __($unsubscribeResult));
            }
        }

        //view button if is some subscriptions here
        if ($megogo->haveSubscribtions()) {
            show_window(__('Your subscriptions'), $megogo->renderSubscribtions());
            show_window('', la_Link($megogo->getAuthButtonURL(), __('Go to MEGOGO'), true, 'mgviewcontrol'));
            show_window('', la_tag('br'));
        }


        //default sub/unsub form
        show_window(__('Available subscribtions'), $megogo->renderSubscribeForm());

        //user guide link
        if (isset($us_config['MG_GUIDE_URL'])) {
            if (!empty($us_config['MG_GUIDE_URL'])) {
                show_window('', la_Link($us_config['MG_GUIDE_URL'], __('Instructions for subscription'), false, 'mgguidecontrol'));
            }
        }
    } else {
        show_window(__('Sorry'), __('You can not use this service'));
    }
} else {
    show_window(__('Sorry'), __('This module is disabled'));
}
?>