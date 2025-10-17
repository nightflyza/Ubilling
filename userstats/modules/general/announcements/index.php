<?php

$user_ip = zbs_UserDetectIp('debug');
$user_login = zbs_UserGetLoginByIp($user_ip);
$us_config = zbs_LoadConfig();


if ($us_config['AN_ENABLED']) {
    // set announcement as read by get request
    if (ubrouting::checkGet('anmarkasread',false)) {
        $anReadId = ubrouting::get('anmarkasread', 'int');
        zbs_AnnouncementsLogPush($user_login, $anReadId);
    }

    // set announcement as read by post request
    if (ubrouting::checkPost('anmarkasread',false)) {
        $anReadId = ubrouting::post('anmarkasread', 'int');
        zbs_AnnouncementsLogPush($user_login, $anReadId);
    }

    // mark announcement as unread by get request
    if (ubrouting::checkGet('anmarkasunread',false)) {
        $anReadId = ubrouting::get('anmarkasunread', 'int');
        zbs_AnnouncementsLogDel($user_login, $anReadId);
    }


    //rendering list of available announcements
    zbs_AnnouncementsShow();
} else {
    show_window(__('Sorry'), __('This module is disabled'));
}
