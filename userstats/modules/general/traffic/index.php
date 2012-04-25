<?php

$user_ip=zbs_UserDetectIp('debug');
$user_login=zbs_UserGetLoginByIp($user_ip);

show_window(__('Traffic stats'),zbs_UserTraffStats($user_login));

?>
