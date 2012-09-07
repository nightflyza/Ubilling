<?php

// load libs
include('modules/engine/api.mysql.php');
include('modules/engine/api.compat.php');
include('modules/engine/api.userstats.php');
$db=new MySQLDB;




// Send main headers
header('Last-Modified: ' . date('r')); 
header("Cache-Control: no-store, no-cache, must-revalidate"); 
header("Pragma: no-cache");



//actions hander
$user_ip=zbs_UserDetectIp('debug');
$user_login=zbs_UserGetLoginByIp($user_ip);
$us_config=zbs_LoadConfig();


if ($user_ip) {
    if ($us_config['auth']=='login') {
    zbs_LogoutForm(); //if already signed
    }
    
if (!isset($_GET['module'])) {
    if ($us_config['UBA_ENABLED']) {
        //UBAgent support
        if (isset($_GET['ubagent'])) {
        zbs_UserShowAgentData($user_login);
        }
    }
    
show_window(__('User profile'),zbs_UserShowProfile($user_login));
} else {
    zbs_LoadModule($_GET['module']);
}
} else {
    if ($us_config['auth']=='login') {
        zbs_LoginForm(); 
    }
}
// template load
zbs_ShowTemplate();


?>
