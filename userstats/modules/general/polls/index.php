<?php

$usConfig = zbs_LoadConfig();
if ($usConfig['POLLS_ENABLED']) {
    $user_ip = zbs_UserDetectIp('debug');
    $user_login = zbs_UserGetLoginByIp($user_ip);
    //load needed APIs

    
    $poll = new Polls($user_login);
    
    show_window(__('My answers to polls'), $poll->renderUserVotes());
} else {
    show_window(__('Sorry'), __('This module is disabled'));
}
?>
