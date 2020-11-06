<?php

/**
 * Basic user-idle auto logout class
 */
class AutoLogout {
    
    const LOGOUT_URL='?idleTimerAutoLogout=true';
    
    /**
     * returns logout dialog localised template
     * 
     * @return string
     */
    protected function createDialog() {
        $autoLogoutTimerContainer=  wf_tag('div', false, '', 'id="idledialog" title="'.__('Your session is about to expire!').'"');
        $autoLogoutTimerContainer.= wf_tag('span', false, 'ui-icon ui-icon-alert', 'style="float:left; margin:0 7px 50px 0;"').wf_tag('span', true);
        $autoLogoutTimerContainer.= __('You will be logged off in').' ';
        $autoLogoutTimerContainer.= wf_tag('span', false, '', 'id="dialog-countdown" style="font-weight:bold"').wf_tag('span', true);
        $autoLogoutTimerContainer.= ' '. __('seconds').  wf_delimiter();
        $autoLogoutTimerContainer.= wf_tag('center',false).  wf_tag('img', false, '', 'src="skins/idleicon.gif" width="160"').  wf_tag('center',true);
        $autoLogoutTimerContainer.=  wf_tag('div', true);
        return ($autoLogoutTimerContainer);
    }
    
    /**
     * returns JQuery subroutine for auto-logout
     * 
     * @return string
     */
    protected function createTimer($idle) {
        $idle=$idle*60;
        $autoLogoutTimerJs='
           <script type="text/javascript">
                // setup the dialog
                $("#idledialog").dialog({
                autoOpen: false,
                modal: true,
                width: 400,
                height: 300,
                closeOnEscape: false,
                draggable: false,
                resizable: false,
                buttons: {
                        \''.__('Yes, keep working').'\': function(){
                        $(this).dialog(\'close\');
                        },
                        \''.__('No, logout').'\': function(){
                        $.idleTimeout.options.onTimeout.call(this);
                        }
                }
                });

            // cache a reference to the countdown element so we don\'t have to query the DOM for it on each ping.
            var $countdown = $("#dialog-countdown");

            // start the idle timer plugin
            $.idleTimeout(\'#idledialog\', \'div.ui-dialog-buttonpane button:first\', {
            idleAfter: '.$idle.',
            pollingInterval: 50,
            keepAliveURL: \'RELEASE\',
            serverResponseEquals: \'OK\',
            onTimeout: function(){
            window.location = "'.self::LOGOUT_URL.'";
            },
            onIdle: function(){
            $(this).dialog("open");
            },
            onCountdown: function(counter){
            $countdown.html(counter); // update the counter
            }
           });
           
        </script>
        ';
        
        return ($autoLogoutTimerJs);

    }
    
    /**
     * renders idle timeout auto logout scripts id needed
     * 
     * @return string
     */
      public function render() {
        global $ubillingConfig;
        $altCfg=$ubillingConfig->getAlter();
        $excludeAdmins=array();
        $result='';
        
        if (isset($altCfg['AUTO_LOGOUT_IDLE'])) {
            if ($altCfg['AUTO_LOGOUT_IDLE']) {
                 $myLogin=whoami();
                 $idleTimeout=$altCfg['AUTO_LOGOUT_IDLE'];
                 if (file_exists(USERS_PATH.$myLogin)) {
                   if (isset($altCfg['AUTO_LOGOUT_EXCLUDE'])) {
                       //extract exclude admins
                       if (!empty($altCfg['AUTO_LOGOUT_EXCLUDE'])) {
                           $excludeAdmins=  explode(',', $altCfg['AUTO_LOGOUT_EXCLUDE']);
                           $excludeAdmins=  array_flip($excludeAdmins);
                       }
                       //push timer script
                       if (!isset($excludeAdmins[$myLogin])) {
                       $result=$this->createDialog();
                       $result.=  $this->createTimer($altCfg['AUTO_LOGOUT_IDLE']);
                       } 
                   } 
                 }
                   
            }
        }
      
        
        return ($result);
      }
    
}

/**
 * run idle auto logout object into template
 * 
 * @return string
 */
function zb_IdleAutologoutRun() {
    $result='';
        if (LOGGED_IN) {
        $idleTimer=new AutoLogout();
        $result=$idleTimer->render();
    }
    return($result);
}





?>
