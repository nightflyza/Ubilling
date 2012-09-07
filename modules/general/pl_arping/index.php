<?php
if (cfr('PLARPING')) {
   
    if (isset($_GET['username'])) {
        $login=$_GET['username'];
        $config=rcms_parse_ini_file(CONFIG_PATH.'billing.ini');
        $alterconfig=rcms_parse_ini_file(CONFIG_PATH.'alter.ini');
        $arping_path=$alterconfig['ARPING'];
        $arping_iface=$alterconfig['ARPING_IFACE'];
        $sudo_path=$config['SUDO'];
        $userdata=zb_UserGetStargazerData($login);
        $user_ip=$userdata['IP'];
        $command=$sudo_path.' '.$arping_path.' '.$arping_iface.' -c 10 -w 10000 '.$user_ip;
        $ping_result='<pre>'.shell_exec($command).'</pre>';
        show_window(__('User ARP pinger'),$ping_result);
        show_window('',  web_UserControls($login));
    }

} else {
      show_error(__('You cant control this module'));
}

?>
