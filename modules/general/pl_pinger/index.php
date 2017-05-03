<?php
if (cfr('PLPINGER')) {
    
    function wf_PlPingerOptionsForm() {
        //previous setting
        if (wf_CheckPost(array('packet'))) {
            $currentpack=vf($_POST['packet'],3);
        } else {
            $currentpack='';
        }
        
        if (wf_CheckPost(array('count'))) {
            $getCount=vf($_POST['count'],3);
            if ($getCount<=10000) {
                $currentcount=$getCount;
            } else {
                $currentcount='';
            }
        } else {
            $currentcount='';
        }
        
       $inputs=  wf_TextInput('packet', __('Packet size'), $currentpack, false,5);
       $inputs.=  wf_TextInput('count', __('Count'), $currentcount, false,5);
       $inputs.= wf_Submit(__('Save'));
       $result=  wf_Form('', 'POST', $inputs, 'glamour');
       return ($result);
    }  
  
    if (isset($_GET['username'])) {
        $login=$_GET['username'];
        $config=rcms_parse_ini_file(CONFIG_PATH.'billing.ini');
        $ping_path=$config['PING'];
        $sudo_path=$config['SUDO'];
        $userdata=zb_UserGetStargazerData($login);
        $user_ip=$userdata['IP'];
        //setting ping parameters
        $addParams='';
        if (wf_CheckGet(array('packsize'))) {
            $addParams.=' -s '.vf($_GET['packsize'],3);
        } 
        
        if (wf_CheckGet(array('packcount'))) {
            $addParams.=' -c '.vf($_GET['packcount'],3);
        } 
        
        //setting ajax background params
        $addAjax='';
        if (wf_CheckPost(array('packet'))) {
            $addAjax.="&packsize=".vf($_POST['packet'],3);
            $addParams.=' -s '.vf($_POST['packet'],3);
        } 
        
        if (wf_CheckPost(array('count'))) {
            $addAjax.="&packcount=".vf($_POST['count'],3);
            $addParams.=' -c '.vf($_POST['count'],3);
        } 
        
        
        $command=$sudo_path.' '.$ping_path.' -i 0.01 -c 10 '.$addParams.' '.$user_ip;
        $ping_result=  wf_AjaxLoader();
        $ping_result.= wf_AjaxLink('?module=pl_pinger&username='.$login.'&ajax=true'.$addAjax, __('Renew'), 'ajaxping', true, 'ubButton');
        $rawResult=shell_exec($command);
        if (wf_CheckGet(array('ajax'))) {
            die($rawResult);
        }
        $ping_result.=wf_tag('pre', false, '', 'id="ajaxping"').$rawResult.  wf_tag('pre', true);
        show_window(__('Settings'), wf_PlPingerOptionsForm());
        show_window(__('User pinger'),$ping_result);
        
        show_window('',  web_UserControls($login));
    }

} else {
      show_error(__('You cant control this module'));
}

?>
