<?php

/**
 * Basic user-idle auto logout class
 */
class AutoLogout {
    /**
     * Contains raw value of idle config option in minutes
     *
     * @var int
     */
    protected $idleLogout = 0;

    /**
     * Contains list of excluded admins if set
     *
     * @var array
     */
    protected $excludedAdmins = array();

    //some predefined stuff
    const LOGOUT_URL = '?idleTimerAutoLogout=true';
    const OPTION_IDLE = 'AUTO_LOGOUT_IDLE';
    const OPTION_EXCLUDE = 'AUTO_LOGOUT_EXCLUDE';


    //         .-.-.
    //    ((  (__I__)  ))
    //      .'_....._'.
    //     / / .12 . \ \
    //    | | '  |  ' | |
    //    | | 9  /  3 | |
    //     \ \ '.6.' / /
    //      '.`-...-'.'
    //       /'-- --'\
    //      `"""""""""`    
    public function __construct() {
        $this->setOptions();
    }

    /**
     * Sets current instance properties
     *
     * @return void
     */
    protected function setOptions() {
        global $ubillingConfig;
        $this->idleLogout = $ubillingConfig->getAlterParam(self::OPTION_IDLE, 0);
        if ($this->idleLogout) {
            $excludeRaw = $ubillingConfig->getAlterParam(self::OPTION_EXCLUDE, '');
            if (!empty($excludeRaw)) {
                $excludeRaw =  explode(',', $excludeRaw);
                $this->excludedAdmins =  array_flip($excludeRaw);
            }
        }
    }

    /**
     * returns logout dialog localised template
     * 
     * @return string
     */
    protected function createDialog() {
        $autoLogoutTimerContainer =  wf_tag('div', false, '', 'id="idledialog" title="' . __('Your session is about to expire!') . '"');
        $autoLogoutTimerContainer .= wf_tag('span', false, 'ui-icon ui-icon-alert', 'style="float:left; margin:0 7px 50px 0;"') . wf_tag('span', true);
        $autoLogoutTimerContainer .= __('You will be logged off in') . ' ';
        $autoLogoutTimerContainer .= wf_tag('span', false, '', 'id="dialog-countdown" style="font-weight:bold"') . wf_tag('span', true);
        $autoLogoutTimerContainer .= ' ' . __('seconds') .  wf_delimiter();
        $autoLogoutTimerContainer .= wf_tag('center', false) .  wf_tag('img', false, '', 'src="skins/idleicon.gif" width="160"') .  wf_tag('center', true);
        $autoLogoutTimerContainer .=  wf_tag('div', true);
        return ($autoLogoutTimerContainer);
    }

    /**
     * returns JQuery subroutine for auto-logout
     * 
     * @return string
     */
    protected function createTimer() {
        $idle = $this->idleLogout * 60; //in seconds
        $autoLogoutTimerJs = wf_tag('script', false, '', 'type="text/javascript"');
        $autoLogoutTimerJs .= '
                $("#idledialog").dialog({
                autoOpen: false,
                modal: true,
                width: 400,
                height: 300,
                closeOnEscape: false,
                draggable: false,
                resizable: false,
                buttons: {
                        \'' . __('Yes, keep working') . '\': function(){
                             $(this).dialog(\'close\');
                        },
                        \'' . __('No, logout') . '\': function(){
                           $.idleTimeout.options.onTimeout.call(this);
                        }
                }
                });

           
            var $countdown = $("#dialog-countdown");

            $.idleTimeout(\'#idledialog\', \'div.ui-dialog-buttonpane button:first\', {
            idleAfter: ' . $idle . ',
            pollingInterval: 50,
            keepAliveURL: \'RELEASE\',
            serverResponseEquals: \'OK\',

            onTimeout: function(){
                 window.location = "' . self::LOGOUT_URL . '";
            },

            onIdle: function(){
                $(this).dialog("open");
            },

            onCountdown: function(counter){
                $countdown.html(counter);
            }
           });
        ';
        $autoLogoutTimerJs .= wf_tag('script', true);
        return ($autoLogoutTimerJs);
    }

    /**
     * renders idle timeout auto logout scripts id needed
     * 
     * @return string
     */
    public function render() {
        $result = '';
        if ($this->idleLogout) {
            $myLogin = whoami();
            if (file_exists(USERS_PATH . $myLogin)) {
                //push timer script
                if (!isset($this->excludedAdmins[$myLogin])) {
                    $result = $this->createDialog();
                    $result .=  $this->createTimer();
                }
            }
        }

        return ($result);
    }
}

/**
 * Runs idle auto logout object into template
 * 
 * @return string
 */
function zb_IdleAutologoutRun() {
    $result = '';
    if (LOGGED_IN) {
        $idleTimer = new AutoLogout();
        $result = $idleTimer->render();
    }
    return ($result);
}
