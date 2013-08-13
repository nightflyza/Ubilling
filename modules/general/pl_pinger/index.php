<?php
if (cfr('PLPINGER')) {
    
  
    if (isset($_GET['username'])) {
        $login=$_GET['username'];
        $config=rcms_parse_ini_file(CONFIG_PATH.'billing.ini');
        $ping_path=$config['PING'];
        $sudo_path=$config['SUDO'];
        $userdata=zb_UserGetStargazerData($login);
        $user_ip=$userdata['IP'];
        $command=$sudo_path.' '.$ping_path.' -i 0.01 -c 10 '.$user_ip;
        $ping_result=  wf_AjaxLoader();
        $ping_result.= wf_AjaxLink('?module=pl_pinger&username='.$login.'&ajax=true', __('Renew'), 'ajaxping', true, 'ubButton');
        $rawResult=shell_exec($command);
        if (wf_CheckGet(array('ajax'))) {
            die($rawResult);
        }
        $ping_result.=wf_tag('pre', false, '', 'id="ajaxping"').$rawResult.  wf_tag('pre', true);
        show_window(__('User pinger'),$ping_result);
        show_window('',  web_UserControls($login));
    }

} else {
      show_error(__('You cant control this module'));
}

?>
