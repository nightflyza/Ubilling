<?php
if(cfr('TASKBAR')) {
    
    if (isset ($_POST['iconsize'])) {
        $iconsize=vf($_POST['iconsize'],3);
        setcookie("tb_iconsize", $iconsize, time() + 86400);
        rcms_redirect("?module=taskbar");
    }

    $altconf=rcms_parse_ini_file(CONFIG_PATH."alter.ini");
    
    function build_task($right,$link,$icon,$text) {
		global $system;
                global $billing_config;
                global $altconf;
                $icon_path=CUR_SKIN_PATH.'taskbar/';
		if (cfr($right)) {
			$task_link=$link;
                        $task_icon=$icon_path.$icon;
                        if (!file_exists($task_icon)) {
                         $task_icon='skins/taskbar/'.$icon;
                        }
			$task_text=$text;
                 
                        
                        if(isset($_COOKIE['tb_iconsize'])) {
                        //is icon customize enabled?
                        if ($altconf['TB_ICONCUSTOMSIZE']) {  
                            $tbiconsize=vf($_COOKIE['tb_iconsize'],3);
                        } else {
                            $tbiconsize=$billing_config['TASKBAR_ICON_SIZE'];
                        }
                        } else {
                            $tbiconsize=$billing_config['TASKBAR_ICON_SIZE'];
                        }
                            
                        
                        
			$template='  <a href="'.$task_link.'"><img src="'.$task_icon.'" border="0" width="'.$tbiconsize.'"  alt="'.$task_text.'" title="'.$task_text.'"></a><img src="'.$icon_path.'spacer.gif"> ';
                   
		} else {
		$template='';
		}
		
		return ($template);
	}

$taskbar='';
        
$taskbar_modules=file_get_contents(CONFIG_PATH.'taskbar_modules.php');

// load taskbar modules
eval ($taskbar_modules);

     $ressizelinks=' ';
     $iconsizes=array(
         '128'=>__('Normal icons'),
         '96'=>__('Lesser'),
         '64'=>__('Macro'),
         '48'=>__('Micro'),
         '32'=>__('Nano')
         );
     
     if (isset($_COOKIE['tb_iconsize'])) {
         $currentsize=vf($_COOKIE['tb_iconsize'],3);
     } else {
         $currentsize=$billing_config['TASKBAR_ICON_SIZE'];
     }
     $resizeinputs=wf_SelectorAC('iconsize', $iconsizes, '', $currentsize, false);
     $resizeform=wf_Form('', 'POST', $resizeinputs);
     
     
if ($altconf['TB_ICONCUSTOMSIZE']) {     
 $taskbar.='<br>'.$resizeform;
}
 
// new tickets notify
if ($altconf['TB_NEWTICKETNOTIFY']) {
    $newticketcount=zb_TicketsGetAllNewCount();
    if ($newticketcount!=0) {
        $ticketnotify=  wf_Link('?module=ticketing', '<img src="skins/ticketnotify.gif" title="'.$newticketcount.' '.__('support tickets expected processing').'" border="0">', false);
    } else {
        $ticketnotify='';
    } 
} else {
    $ticketnotify='';
}

//new signups notify
if ($altconf['SIGREQ_ENABLED']) {
    $newreqcount= zb_SigreqsGetAllNewCount();
    if ($newreqcount!=0) {
        $ticketnotify.=  wf_Link('?module=sigreq', ' <img src="skins/sigreqnotify.gif" title="'.$newreqcount.' '.__('signup requests expected processing').'" border="0">', false);
    } else {
        $ticketnotify.='';
    } 
} 

  show_window(__('Taskbar').' '.$ticketnotify,$taskbar);

}
else {
	show_error(__('Access denied'));
}

?>