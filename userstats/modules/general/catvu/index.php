<?php

error_reporting(E_ALL);

$user_ip = zbs_UserDetectIp('debug');
$user_login = zbs_UserGetLoginByIp($user_ip);
$us_config = zbs_LoadConfig();

// if UKV enabled
if ($us_config['UKV_ENABLED']) {
    $usUkv = new UserstatsUkv();
    $ukvUserId = $usUkv->detectUserByLogin($user_login);
    if ($ukvUserId) {
        show_window(__('CaTV user profile'), $usUkv->renderUserProfile($ukvUserId));
        if (@$us_config['PAYMENTS_ENABLED']) {
            show_window(__('CaTV payments'), $usUkv->renderUserPayments($ukvUserId));
        }
    } else {
        show_window(__('Sorry'), __('No CaTV account associated with your Internet service'));
    }
} else {
    show_window(__('Sorry'), __('Unfortunately CaTV is disabled'));
}
