<?php

$user_ip = zbs_UserDetectIp('debug');
$user_login = zbs_UserGetLoginByIp($user_ip);
$us_config = zbs_LoadConfig();

if (@$us_config['OM_ENABLED']) {
    $userData = zbs_UserGetStargazerData($user_login);
    //Denied tariffs checks if option set
    $tariffAllowed = true;
    if (isset($us_config['OM_TARIFFSDENIED'])) {
        if (!empty($us_config['OM_TARIFFSDENIED'])) {
            $tariffsDenyList = array();
            $denyTariffsTmp = explode(',', $us_config['OM_TARIFFSDENIED']);
            if (!empty($denyTariffsTmp)) {
                foreach ($denyTariffsTmp as $optionIndex => $eachDeniedTariffName) {
                    $cleanTariffName = trim($eachDeniedTariffName);
                    $tariffsDenyList[$cleanTariffName] = $eachDeniedTariffName;
                }
            }
            //strict tariff name check
            if (isset($tariffsDenyList[$userData['Tariff']])) {
                $tariffAllowed = false;
            }
        }
    }

    //bundle tariffs check
    $tariffBundle = false;
    if (isset($us_config['OM_TARIFFSBUNDLE'])) {
        $bundleTariffsList = array();
        $bundleTariffsTmp = explode(',', $us_config['OM_TARIFFSBUNDLE']);

        if (!empty($bundleTariffsTmp)) {
            foreach ($bundleTariffsTmp as $optionIndex => $eachBundleTariffName) {
                $cleanTariffName = trim($eachBundleTariffName);
                $bundleTariffsList[$cleanTariffName] = $eachBundleTariffName;
            }
        }
        //strict tariff name check
        if (isset($bundleTariffsList[$userData['Tariff']])) {
            $tariffBundle = true;
        }
    }



//Check for user active state
    if (($userData['Passive'] == 0) AND ( $userData['Down'] == 0 ) AND ( $userData['Cash'] >= '-' . $userData['Credit']) AND ( $tariffAllowed)) {
        $omegaFront = new OmegaTvFrontend();
        $omegaFront->setLogin($user_login);

        //device deletion
        if (la_CheckGet(array('deletedevice'))) {
            $omegaFront->pushDeviceDelete($_GET['deletedevice']);
            rcms_redirect('?module=omegatv');
        }

        //playlist deletion
        if (la_CheckGet(array('deleteplaylist'))) {
            $omegaFront->pushPlaylistDelete($_GET['deleteplaylist']);
            rcms_redirect('?module=omegatv');
        }

        //new playlist creation
        if (la_CheckGet(array('newplaylist'))) {
            $omegaFront->pushPlaylistAssign();
            rcms_redirect('?module=omegatv');
        }

        //subscription for some tariff
        if (la_CheckGet(array('subscribe'))) {
            $subscribeResult = $omegaFront->pushSubscribeRequest($_GET['subscribe']);
            if (empty($subscribeResult)) {
                rcms_redirect('?module=omegatv');
            } else {
                show_window(__('Sorry'), __($subscribeResult));
            }
        }

        //unsubscription of some tariff
        if (la_CheckGet(array('unsubscribe'))) {
            $unsubscribeResult = $omegaFront->pushUnsubscribeRequest($_GET['unsubscribe']);
            if (empty($unsubscribeResult)) {
                rcms_redirect('?module=omegatv');
            } else {
                show_window(__('Sorry'), __($unsubscribeResult));
            }
        }

        if (!$tariffBundle) {
            //default sub/unsub form
            show_window(__('Attention'), __('On unsubscription will be charged fee the equivalent value of the subscription.'));
            show_window(__('Available subscribtions'), $omegaFront->renderSubscribeForm());
            $accountIdLabel = la_tag('h3') . __('Your account ID is') . ': ' . $omegaFront->generateCustormerId($user_login) . la_tag('h3', true) . la_tag('br');
            show_window('', $accountIdLabel);
        }

        $subscribedTrariffs = $omegaFront->getSubscribedTariffs();
        if (!empty($subscribedTrariffs)) {
            //show list of available devices
            show_window(__('Devices'), $omegaFront->renderUserDevicesForm());
        }

        //link to web-player
        $viewUrl = $omegaFront->getViewButtonURL();
        $viewLink = la_tag('a', false, 'mgviewcontrol', 'href="' . $viewUrl . '" target="_BLANK"') . __('View online') . la_tag('a', true);
        show_window('', $viewLink);
    } else {
        show_window(__('Sorry'), __('You can not use this service'));
    }
} else {
    show_window(__('Sorry'), __('This module is disabled'));
}
?>