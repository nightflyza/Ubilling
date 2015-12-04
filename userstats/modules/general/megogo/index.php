<?php

$user_ip = zbs_UserDetectIp('debug');
$user_login = zbs_UserGetLoginByIp($user_ip);
$us_config = zbs_LoadConfig();

if (@$us_config['MG_ENABLED']) {
    
    $megogo=new MegogoFrontend();
    deb($megogo->renderSubscribeForm());
    
} else {
    show_window(__('Sorry'), __('This module is disabled'));
}
?>