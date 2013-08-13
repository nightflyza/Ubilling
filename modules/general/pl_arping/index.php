<?php
if (cfr('PLARPING')) {
   
    if (isset($_GET['username'])) {
        $login=$_GET['username'];
        $config=rcms_parse_ini_file(CONFIG_PATH.'billing.ini');
        $alterconfig=rcms_parse_ini_file(CONFIG_PATH.'alter.ini');
        $parseMe='';
        $cloneFlag=false;
        $arping_path=$alterconfig['ARPING'];
        $arping_iface=$alterconfig['ARPING_IFACE'];
        $arping_options=$alterconfig['ARPING_EXTRA_OPTIONS'];
        $sudo_path=$config['SUDO'];
        $userdata=zb_UserGetStargazerData($login);
        $user_ip=$userdata['IP'];
        $command=$sudo_path.' '.$arping_path.' '.$arping_iface.' '.$arping_options.' '.$user_ip;
        $raw_result=  shell_exec($command);
        if (wf_CheckGet(array('ajax'))) {
            die($raw_result);
        }
        $ping_result=  wf_AjaxLoader();
        $ping_result.= wf_AjaxLink('?module=pl_arping&username='.$login.'&ajax=true', __('Renew'), 'ajaxarping', true, 'ubButton');
        $ping_result.=  wf_tag('pre',false,'','id="ajaxarping"').$raw_result.  wf_tag('pre',true);
        //detecting duplicate MAC
        $rawArray=  explodeRows($raw_result);
        if (!empty($rawArray)) {
            foreach ($rawArray as $io=>$eachline) {
                if (ispos($eachline, 'packets transmitted')) {
                    $parseMe=$eachline;
                }
            }
        }
        
        if (!empty($parseMe)) {
            $parseMe=  explode(',', $parseMe);
            if (sizeof($parseMe)==3) {
                $txCount=vf($parseMe[0],3);
                $rxCount=vf($parseMe[1],3);
                if ($rxCount>$txCount) {
                    $cloneFlag=true;
                }
            }
        }
        
        if ($cloneFlag) {
            $ping_result.=wf_tag('font', false, '', 'color="#ff0000" size="4"').__('It looks like this MAC addresses has duplicate on the network').  wf_tag('font',true);
        }
        
        show_window(__('User ARP pinger'),$ping_result);
        show_window('',  web_UserControls($login));
    }

} else {
      show_error(__('You cant control this module'));
}

?>
