<?php

/**
 * CL4P-TP management interface
 */    
class ClapTrapMgr  {
    protected $altCfg = array();
    protected $token = '';
    protected $hookUrl = '';
    protected $authString = '';

    /**
     * Telegram API object instance
     *
     * @var object
     */
    protected $telegram='';

    /**
     * ClapTrapBot object instance
     *
     * @var object
     */
    protected $botInstance='';

    /**
     * Contains messages helper object instance
     *
     * @var object
     */
    protected $messages='';

    //other predefined stuff
    const URL_ME = '?module=cltpmgr';
    const ROUTE_INSTALL='install';
    const PROUTE_HOOK_URL = 'newhookinstall';
    

    public function __construct() { 
        $this->loadAlter();
        $this->initMessages();
        $this->setOptions();
        $this->initTelegram();
        $this->initBotInstance();
    }

    protected function loadAlter() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }


    protected function setOptions() {
        $this->token = $this->altCfg[ClapTrapBot::OPTION_TOKEN];
        $this->hookUrl = $this->altCfg[ClapTrapBot::OPTION_HOOK_URL];
        $this->authString = $this->altCfg[ClapTrapBot::OPTION_AUTH_STRING];
    }

    protected function initTelegram() {
        if (!empty($this->token)) {
            $this->telegram = new UbillingTelegram($this->token);
        }
    }

    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    protected function initBotInstance() {
        if (!empty($this->token)) { 
            $this->botInstance = new ClapTrapBot($this->token);
        }
    }


    /**
     * Returns actual bot hook state as array:
     * 
     * array(
     *     'ok' => 1,
     *     'result' => array(
     *         'url' => 'https://somehost.com/tgtinygate/',
     *         'has_custom_certificate' => '',
     *         'pending_update_count' => 0,
     *         'last_error_date' => 1761048866,
     *         'last_error_message' => 'Connection reset by peer',
     *         'max_connections' => 100,
     *         'ip_address' => '1.2.3.4'
     *     )
     * )
     * 
     * @return array
     */
    public function getActualHookInfo() {
        $result = array();
        if (!empty($this->token)) {
            $rawData = $this->telegram->getWebHookInfo();
            if (!empty($rawData)) {
             if (json_validate($rawData)) {
                $result = json_decode($rawData, true);
             }
            }
        }
        return($result);
    }


    protected function isHookUrlValid($hookUrl) {
        $result = '';
        if (!empty($hookUrl)) {
            //only https allowed
            if (!strpos($hookUrl, 'https://') === 0) {
                $result = __('Only HTTPS allowed');
            }

            //check if hook URL is already to accept requests
            $urlHandle=new OmaeUrl($hookUrl);
            $urlHandle->setTimeout(10);
            $urlHandle->setUserAgent('Ubilling/ClapTrapMgr');
            $urlHandle->dataPost(ClapTrapBot::PROUTE_VALIDATE,'true');
            $handlerReply=$urlHandle->response();
            if ($urlHandle->error() or $urlHandle->httpCode() != 200) {
                $result = __('Hook URL is not accepting requests').': '.__('Connection error');
            } else {
                    //check if hook URL is valid ClapTrapBot hook URL
                    if (!ispos($handlerReply, ClapTrapBot::VALIDATION_RESULT)) {
                        $result = __('Not valid ClapTrapBot hook URL');
                    }
            }

           

        } else {
            $result = __('Hook URL is empty');
        }
        return($result);
    }

    public function renderInstallHookForm() {
        $result = '';
        if (!empty($this->token) and !empty($this->hookUrl)) {
            $newHookUrl = $this->hookUrl;
            $inputs = wf_TextInput(self::PROUTE_HOOK_URL, __('Hook URL'), $newHookUrl, false, 50, 'url').' ';
            $inputs .= wf_Submit(__('Install'));
            $result = wf_Form('', 'POST', $inputs, 'glamour');
        } else {
            $result = $this->messages->getStyledMessage(__('Token or hook URL is empty'), 'error');
            $result .= wf_BackLink(self::URL_ME);
        }
        return($result);
    }

    public function installHook($hookUrl) {
        $result = '';
        if (!empty($this->token) and !empty($hookUrl)) {
            $validationError = $this->isHookUrlValid($hookUrl);
            if (empty($validationError)) {
                //removing all old hook pids
                $allHookPids = rcms_scandir(ClapTrapBot::HOOK_PID_PATH,'*.hook');
                if (!empty($allHookPids)) {
                    foreach ($allHookPids as $eachHookPid) {
                        if (ispos($eachHookPid, 'ClapTrapBot')) {
                            unlink(ClapTrapBot::HOOK_PID_PATH . $eachHookPid);
                        }
                    }
                }
                
                $this->botInstance->installWebHook($hookUrl);
            } else {
                $result = $this->messages->getStyledMessage($validationError, 'error');
            }
        }
        return($result);
    }


    /**
     * Renders actual bot hook state as readable table
     * 
     * @param array $hookInfo
     * 
     * @return string
     */
    public function renderHookInfo($hookInfo) {
        $result = '';
        if (!empty($hookInfo) and !empty($hookInfo['ok'])) {
            if (!empty($hookInfo['result'])) {
                $hData=$hookInfo['result'];
                if (!empty($hData['url'])) {
                $cells = wf_TableCell(__('Hook URL'));
                $cells .= wf_TableCell($hData['url']);
                $rows = wf_TableRow($cells, 'row3');
                $cells = wf_TableCell(__('Has custom certificate'));
                $cells .= wf_TableCell($hData['has_custom_certificate']);
                $rows .= wf_TableRow($cells, 'row3');
                $cells = wf_TableCell(__('Pending update count'));
                $cells .= wf_TableCell($hData['pending_update_count']);
                $rows .= wf_TableRow($cells, 'row3');
                $cells = wf_TableCell(__('Last error date'));
                $cells .= wf_TableCell(@$hData['last_error_date']);
                $rows .= wf_TableRow($cells, 'row3');
                $cells = wf_TableCell(__('Last error message'));
                $cells .= wf_TableCell(@$hData['last_error_message']);
                $rows .= wf_TableRow($cells, 'row3');
                $cells = wf_TableCell(__('Max connections'));
                $cells .= wf_TableCell($hData['max_connections']);
                $rows .= wf_TableRow($cells, 'row3');
                $cells = wf_TableCell(__('IP address'));
                $cells .= wf_TableCell($hData['ip_address']);
                $rows .= wf_TableRow($cells, 'row3');

                $result = wf_TableBody($rows, '100%', 0);
                } else {
                    $result=$this->messages->getStyledMessage(__('No web hook URL has been set up for this bot'),'warning');
                }
            } else {
                $result = $this->messages->getStyledMessage(__('Empty hook info received'), 'error');    
            }
        } else {
            $result = $this->messages->getStyledMessage(__('Invalid hook info'), 'error');
        }
        return($result); 
    }

}