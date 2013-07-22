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
                            
                        
                      if ($altconf['TB_LABELED']) {
                       if ($tbiconsize>63) {  
			$template='<div class="dashtask" style="height:'.($tbiconsize+30).'px; width:'.($tbiconsize+30).'px;"> <a href="'.$task_link.'"><img  src="'.$task_icon.'" border="0" width="'.$tbiconsize.'"  height="'.$tbiconsize.'" alt="'.$task_text.'" title="'.$task_text.'"></a> <br><br>'.$task_text.' </div>';
                        } else {
                            $template='<a href="'.$task_link.'"><img  src="'.$task_icon.'" border="0" width="'.$tbiconsize.'"  height="'.$tbiconsize.'" alt="'.$task_text.'" title="'.$task_text.'"></a><img src="'.$icon_path.'spacer.gif">  ';
                        }
                      } else {
                        $template='<a href="'.$task_link.'"><img  src="'.$task_icon.'" border="0" width="'.$tbiconsize.'"  height="'.$tbiconsize.'" alt="'.$task_text.'" title="'.$task_text.'"></a><img src="'.$icon_path.'spacer.gif">  ';
                      }
                        
                   
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

//check for unread messages in instant messanger
if ($altconf['TB_UBIM']) {
    if (cfr('UBIM')) {
    $unreadMessageCount=  im_CheckForUnreadMessages();
    if ($unreadMessageCount) {
    //we have new messages
    $unreadIMNotify=__('You received').' '.$unreadMessageCount.' '.__('new messages');
    $urlIM= $unreadIMNotify.  wf_delimiter().wf_Link("?module=ubim&checknew=true", __('Click here to go to the instant messaging service.'), false, 'ubButton');
    $ticketnotify.=wf_Link("?module=ubim&checknew=true", wf_img("skins/ubim_blink.gif", $unreadMessageCount.' '.__('new message received')), false, '');
    $ticketnotify.=wf_modalOpened(__('New messages received'), $urlIM, '450', '200');
    }
 }
} 

//switchmon at nptify area
if ($altconf['TB_SWITCHMON']) {
$dead_raw=zb_StorageGet('SWDEAD');
$last_pingtime=zb_StorageGet('SWPINGTIME');
$deathTime=  zb_SwitchesGetAllDeathTime();
$deadarr=array();
$content='';

if ($altconf['SWYMAP_ENABLED']) {
    $content='<a href="?module=switchmap"><img src="skins/swmapsmall.png" border="0" title="'.__('Switches map').'"></a>';
}

$content.= wf_AjaxLoader(). wf_AjaxLink("?module=switches&forcereping=true&ajaxping=true", wf_img('skins/refresh.gif', __('Force ping')),'switchping', true, '');



if ($dead_raw) {
$deadarr=unserialize($dead_raw);
if (!empty($deadarr)) {
//there is some dead switches
$deadcount=sizeof($deadarr);    
if ($altconf['SWYMAP_ENABLED']) {
    //getting geodata
    $switchesGeo=  zb_SwitchesGetAllGeo();
}
//ajax container
$content.=wf_tag('div', false, '', 'id="switchping"');

foreach ($deadarr as $ip=>$switch) {
    if ($altconf['SWYMAP_ENABLED']) {
        if (isset($switchesGeo[$ip])) {
          if (!empty($switchesGeo[$ip])) {
          $devicefind= wf_Link('?module=switchmap&finddevice='.$switchesGeo[$ip], wf_img('skins/icon_search_small.gif',__('Find on map'))).' ';   
          } else {
              $devicefind='';
          }
        } else {
          $devicefind='';
        }
        
    } else {
        $devicefind='';
    }
    //check morgue records for death time
    if (isset($deathTime[$ip])) {
        $deathClock=  wf_img('skins/clock.png', __('Switch dead since').' '.$deathTime[$ip]).' ';
    } else {
        $deathClock='';
    }
    //add switch as dead
    $content.=$devicefind.'&nbsp;'.$deathClock.$ip.' - '.$switch.'<br>';
    
}

//ajax container end
$content.=wf_delimiter().__('Cache state at time').': '.date("H:i:s",$last_pingtime).wf_tag('div',true);

$ticketnotify.='<div class="ubButton">'.wf_modal(__('Dead switches').': '.$deadcount, __('Dead switches'), $content, '', '500', '400').'</div>';
} else {
   $content.=wf_tag('div', false, '', 'id="switchping"').__('Switches are okay, everything is fine - I guarantee').wf_delimiter().__('Cache state at time').': '.date("H:i:s",$last_pingtime).wf_tag('div',true);
   $ticketnotify.='<div class="ubButton">'.wf_modal(__('All switches alive'), __('All switches alive'), $content, '', '500', '400').'</div>';
}



} else {
   $content.=wf_tag('div', false, '', 'id="switchping"').__('Switches are okay, everything is fine - I guarantee').wf_delimiter().__('Cache state at time').': '.@date("H:i:s",$last_pingtime).wf_tag('div',true);
   $ticketnotify.='<div class="ubButton">'.wf_modal(__('All switches alive'), __('All switches alive'), $content, '', '500', '400').'</div>';
}
}



  show_window(__('Taskbar').' '.$ticketnotify,$taskbar);
  
//refresh IM container with notify
if ($altconf['TB_UBIM']) {
if ($altconf['TB_UBIM_REFRESH']) {
    if (cfr('UBIM')) {
    im_RefreshContainer($altconf['TB_UBIM_REFRESH']);
    }
}
 }
 
}
else {
	show_error(__('Access denied'));
}

?>