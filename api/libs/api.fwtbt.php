<?php

class ForWhomTheBellTolls {

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains system billing config as key=>value
     *
     * @var array
     */
    protected $billingCfg = array();

    /**
     * Calls polling interval in ms.
     *
     * @var int
     */
    protected $pollingInterval = 5000;

    /**
     * Notification display timeout
     *
     * @var int
     */
    protected $popupTimeout = 6000;

    /**
     *
     * @var type 
     */
    protected $offsetNumber = 3;
    protected $offsetStatus = 5;
    protected $offsetLogin = 7;

    /**
     * Default log path to parse
     */
    protected $dataSource = 'content/documents/askozianum.log';

    /**
     * URL with json list of recieved calls
     */
    const URL_CALLS = '?module=testing&getcalls=true';

    /**
     * URL of user profile route
     */
    const URL_PROFILE = '?module=userprofile&username=';

    /**
     * Creates new FWTBT instance
     */
    public function __construct() {
        $this->loadConfig();
    }

    /**
     * Loads required configs and sets some options
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfig() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
        $this->billingCfg = $ubillingConfig->getBilling();
    }

    /**
     * Renders calls data by last minute
     * 
     * @return void
     */
    public function getCalls() {
        if (wf_CheckGet(array('getcalls'))) {
            $reply = array();
            $allAddress = zb_AddressGetFulladdresslistCached();

            if (file_exists($this->dataSource)) {
                $curMinute = date("Y-m-d H:i:");
                $command = $this->billingCfg['TAIL'] . ' -n 20 ' . $this->dataSource;
                $rawData = shell_exec($command);
                if (!empty($rawData)) {
                    $rawData = explodeRows($rawData);
                    $count = 0;
                    if (!empty($rawData)) {
                        foreach ($rawData as $io => $line) {
                            if (!empty($line)) {
                                //  if (ispos($line, $curMinute)) {
                                $line = explode(' ', $line);
                                @$number = $line[$this->offsetNumber]; //phone number offset
                                @$status = $line[$this->offsetStatus]; //call status offset
                                if (isset($line[$this->offsetLogin])) { //detected login offset
                                    $login = $line[$this->offsetLogin];
                                } else {
                                    $login = '';
                                }
                                switch ($status) {
                                    case '0':
                                        //user not found
                                        $style = 'info';
                                        break;
                                    case '1':
                                        //user found and active
                                        $style = 'success';
                                        break;
                                    case '2':
                                        //user is debtor
                                        $style = 'error';
                                        break;
                                    case '3':
                                        //user is frozen
                                        $style = 'warning';
                                        break;
                                }
                                if (!empty($login)) {
                                    $profileControl = ' ' . wf_Link(self::URL_PROFILE . $login, web_profile_icon(), false, 'ubButton') . ' ';
                                    $callerName = isset($allAddress[$login]) ? $allAddress[$login] : '';
                                } else {
                                    $profileControl = '';
                                    $callerName = '';
                                }

                                $reply[$count]['text'] = __('Calling') . ' ' . $number . ' ' . $callerName . ' ' . $profileControl;
                                $reply[$count]['type'] = $style;
                                // }
                                $count++;
                            }
                            
                        }
                    }
                }
            }
            debarr($reply);
            die(json_encode($reply));
        }
    }

    /**
     * Renders notification frontend with some background polling
     * 
     * @return string
     */
    public function renderCallsNotification() {
        $result = wf_tag('script');
        $result.= '
        $(document).ready(function() {
        $(".dismiss").click(function(){$("#notification").fadeOut("slow");});
           setInterval(
           function() {
            $.get("' . self::URL_CALLS . '",function(message) {
            if (message) {
            var data= JSON.parse(message);
            data.forEach(function(key) {  
            new Noty({
                theme: \'relax\',
                timeout: \'' . $this->popupTimeout . '\',
                progressBar: true,
                type: key.type,
                layout: \'bottomRight\',
                text: key.text
                }).show(); });
                }
              }
            )
            },
            ' . $this->pollingInterval . ');
        })';
        $result.=wf_tag('script', true);
        return ($result);
    }

}
