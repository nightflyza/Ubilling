<?php

$user_ip = zbs_UserDetectIp('debug');
$user_login = zbs_UserGetLoginByIp($user_ip);
$us_config = zbs_LoadConfig();

if (@$us_config['SWEETTV_ENABLED']) {
    $userData = zbs_UserGetStargazerData($user_login);
    //Check for user active state
    if (($userData['Passive'] == 0) AND ( $userData['Down'] == 0 )) {
        $modUrl = '?module=omsweettv';
        $trinitytvFront = new TrinityTvFrontend($modUrl);

        $trinitytvFront->setLogin($user_login);

        //try subscribe service
        if (la_CheckGet(array('subscribe'))) {

            $subscribeResult = $trinitytvFront->pushSubscribeRequest($_GET['subscribe']);

            if (!$subscribeResult) {
                rcms_redirect($modUrl);
            } else {
                show_window(__('Sorry'), __($subscribeResult));
            }
        }

        //try unsubscribe service
        if (la_CheckGet(array('unsubscribe'))) {
            $unsubscribeResult = $trinitytvFront->pushUnsubscribeRequest($_GET['unsubscribe']);
            if (!$unsubscribeResult) {
                rcms_redirect($modUrl);
            } else {
                show_window(__('Sorry'), __($unsubscribeResult));
            }
        }

        //try delete device
        if (la_CheckGet(array('deletedevice'))) {
            $delDeviceResult = $trinitytvFront->pushDeviceDeleteRequest($_GET['deletedevice']);
            if (!$delDeviceResult) {
                rcms_redirect($modUrl);
            } else {
                show_window(__('Sorry'), __($delDeviceResult));
            }
        }

        //device deletion by its ID
        if (la_CheckGet(array('deletedeviceid'))) {
            $delDeviceResult = $trinitytvFront->pushDeviceIdDeleteRequest($_GET['deletedeviceid']);
            if (!$delDeviceResult) {
                rcms_redirect($modUrl);
            } else {
                show_window(__('Sorry'), __($delDeviceResult));
            }
        }

        // manual add device
        if (la_CheckPost(array('device'))) {
            if ($trinitytvFront->canAddMoreDevices()) {
                // add device by mac
                if (la_CheckPost(array('mac'))) {
                    $addDeviceResult = $trinitytvFront->pushDeviceAddMacRequest($_POST['mac']);
                    if (!$addDeviceResult) {
                        rcms_redirect($modUrl);
                    } else {
                        show_window(__('Sorry'), __($addDeviceResult));
                    }
                }

                // add device by code
                if (la_CheckPost(array('code'))) {
                    $addDeviceResult = $trinitytvFront->pushDeviceAddCodeRequest($_POST['code']);
                    if (!$addDeviceResult) {
                        rcms_redirect($modUrl);
                    } else {
                        show_window(__('Sorry'), __($addDeviceResult));
                    }
                }
            } else {
                show_window(__('Sorry'), __('Devices count limit is exceeded'));
            }
        }

        //view button if is some subscriptions here
        if ($trinitytvFront->haveSubscribtions()) {
            show_window(__('Your subscriptions'), $trinitytvFront->renderSubscribtions());
        }

        // device
        if ($trinitytvFront->haveSubscribtions()) {
            show_window(__('Devices'), $trinitytvFront->renderDevices());
            //display some guide links if required
            if (@$us_config['TRINITYTV_GUIDE_URL']) {
                $guideLink = la_Link($us_config['TRINITYTV_GUIDE_URL'], __('How to configure your devices and use service'), false, 'trinity-button');
                show_window('', $guideLink);
            }
            show_window('', la_tag('br'));
        }



        if (@!$us_config['TRINITYTV_NOUSERSUB']) {
            //default sub/unsub form
            show_window(__('Available subscribtions'), $trinitytvFront->renderSubscribeForm());
        } else {
            if (!$trinitytvFront->haveSubscribtions()) {
                show_window(__('Sorry'), __('Unavailable'));
            }
        }
    } else {
        show_window(__('Sorry'), __('You can not use this service'));
    }
} else {
    show_window(__('Sorry'), __('This module is disabled'));
}
