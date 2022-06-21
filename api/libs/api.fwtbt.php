<?php

/**
 * Incoming calls notifications class
 */
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
    protected $pollingInterval = 7000;

    /**
     * Notification display timeout in ms.
     *
     * @var int
     */
    protected $popupTimeout = 10000;

    /**
     * System cache object placeholder
     *
     * @var object
     */
    protected $cache = '';

    /**
     * Caching  timeout based on polling timeout in seconds.
     *
     * @var int
     */
    protected $cachingTimeout = 7;

    /**
     * Default number position offset
     *
     * @var int
     */
    protected $offsetNumber = 3;

    /**
     * Default call status position offset
     *
     * @var int
     */
    protected $offsetStatus = 5;

    /**
     * Default detected login offset
     *
     * @var int
     */
    protected $offsetLogin = 7;

    /**
     * Render notification code everywhere in web interface or just on taskbar
     *
     * @var bool
     */
    protected $anywhere = false;

    /**
     * Array of administrators for whom display notifications.
     *
     * @var array
     */
    protected $showFor = array();

    /**
     * Contains current instance user login
     *
     * @var string
     */
    protected $myLogin = '';

    /**
     * Default log path to parse
     */
    protected $dataSource = '';

    /**
     * Reply cache key name
     */
    const CACHE_KEY = 'FWTBT_REPLY';

    /**
     * URL with json list of recieved calls
     */
    const URL_CALLS = '?module=fwtbt&getcalls=true';

    /**
     * URL of user profile route
     */
    const URL_PROFILE = '?module=userprofile&username=';

    /**
     * Creates new FWTBT instance
     */
    public function __construct() {
        $this->loadConfig();
        $this->setOptions();
        $this->initCache();
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
     * Inits system cache
     * 
     * @return void
     */
    protected function initCache() {
        $this->cache = new UbillingCache();
    }

    /**
     * Sets basic object instance options
     * 
     * @return void
     */
    protected function setOptions() {
        /**
         * Make his fight on the hill in the early day
         * Constant chill deep inside
         */
        $this->myLogin = whoami();
        $this->dataSource= PBXNum::LOG_PATH;

        if (@$this->altCfg['FWTBT_INTERVAL']) {
            $this->pollingInterval = $this->altCfg['FWTBT_INTERVAL'] * 1000; //option is in seconds
            $this->cachingTimeout = $this->altCfg['FWTBT_INTERVAL'];
        }

        if (@$this->altCfg['FWTBT_TIMER']) {
            $this->popupTimeout = $this->altCfg['FWTBT_TIMER'] * 1000; //option is in seconds
        }

        if (@$this->altCfg['FWTBT_ANYWHERE']) {
            $this->anywhere = true;
        }

        if (@$this->altCfg['FWTBT_ADMINS']) {
            $this->showFor = explode(',', $this->altCfg['FWTBT_ADMINS']);
            $this->showFor = array_flip($this->showFor);
        }
        /**
         * Shouting gun, on they run through the endless grey
         * On the fight, for they are right, yes, by who's to say?
         */
    }
    

    /**
     * Renders calls data by last minute
     * 
     * @return void
     */
    public function getCalls() {
        if (wf_CheckGet(array('getcalls'))) {
            $reply = array();
            $cachedReply = $this->cache->get(self::CACHE_KEY, $this->cachingTimeout);
            if (empty($cachedReply)) {
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
                                    if (ispos($line, $curMinute)) {
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
                                                $icon = 'skins/call_info.png';
                                                break;
                                            case '1':
                                                //user found and active
                                                $style = 'success';
                                                $icon = 'skins/call_success.png';
                                                break;
                                            case '2':
                                                //user is debtor
                                                $style = 'error';
                                                $icon = 'skins/wdycnotify.png';
                                                break;
                                            case '3':
                                                //user is frozen
                                                $style = 'warning';
                                                $icon = 'skins/call_warning.png';
                                                break;
                                           default:
                                                //user not found
                                                $style = 'info';
                                                $icon = 'skins/call_info.png';
                                                break;
                                        }
                                        if (!empty($login)) {
                                            $profileControl = ' ' . wf_Link(self::URL_PROFILE . $login, web_profile_icon(), false, 'ubButton fwtbtprofile') . ' ';
                                            $callerName = isset($allAddress[$login]) ? $allAddress[$login] : '';
                                            $link = self::URL_PROFILE . $login;
                                        } else {
                                            $profileControl = '';
                                            $callerName = '';
                                            $link = '';
                                        }


                                        $notificationText = wf_tag('div', false, 'fwtbttext');
                                        $notificationText.= __('Calling') . ' ' . $number . ' ' . $callerName;
                                        $notificationText.= wf_tag('div', true);
                                        $notificationText.= $profileControl;
                                        

                                        $reply[$count]['text'] = $notificationText;
                                        $reply[$count]['cleartext'] = $number . ' ' . $callerName;
                                        $reply[$count]['type'] = $style;
                                        $reply[$count]['icon'] = $icon;
                                        $reply[$count]['link'] = $link;
                                        $reply[$count]['queue'] = 'q' . $count;
                                        $reply[$count]['number'] = $number;

                                        $count++;
                                    }
                                }
                            }
                        }
                    }
                }
                $this->cache->set(self::CACHE_KEY, $reply, $this->cachingTimeout);
            } else {
                $reply = $cachedReply;
            }

            die(json_encode($reply));
        }
    }

    /**
     * Returns notification frontend with some background polling
     * 
     * @return string
     */
    protected function getCallsNotification() {
        $result = '';
        //some custom style
        $result.= wf_tag('style');
        //this style is inline for preventing of css caching
        $result.= '
                #noty_layout__bottomRight {
                width: 425px !important;
                }

                .fwtbttext {
                 float: left;
                 display: block;
                 height: 32px;
                }

                .fwtbtprofile {
                 float: right;
                 margin-bottom: 5px;
                }
            ';

        if(@$this->altCfg['FWTBT_DESKTOP']) {
            $result.= '
                #noty_layout__bottomRight {
                margin-bottom: 120px !important;
                }
            ';
        }

        $result.= wf_tag('style', true);
        //basic notification frontend
        $result.= wf_tag('script');
        $result.= '
                $(document).ready(function() {

                Notification.requestPermission().then(function(result) {
                    console.log(result);
                });

                $(".dismiss").click(function(){$("#notification").fadeOut("slow");});
                   setInterval(
                   function() {
                    $.get("' . self::URL_CALLS . '&reqadm=' . $this->myLogin . '",function(message) {
                    if (message) {
                    var data= JSON.parse(message);
                    data.forEach(function(key) {  
                    new Noty({
                        theme: \'relax\',
                        timeout: \'' . $this->popupTimeout . '\',
                        progressBar: true,
                        type: key.type,
                        layout: \'bottomRight\',
                        killer: key.number,
                        queue: key.number,
                        text: key.text
                        }).show();

                        if (typeof (sendNotificationDesktop) === "function") {
                            var title = "' . __('Calling') .'";
                            var options = {
                                body: key.cleartext,
                                icon: key.icon,
                                tag: key.number,
                                dir: "auto"
                            };
                                sendNotificationDesktop(title, options, key.link);
                        }
                    });
                        }
                      }
                    )
                    },
                    ' . $this->pollingInterval . ');
                })
                ';
        $result.=  wf_tag('script', true);

        if(@$this->altCfg['FWTBT_DESKTOP']) {
            $result.= wf_tag('script');
            $result.= '
                   function sendNotificationDesktop(title, options, link) {
                        if (Notification.permission === "granted") {
                            var notification = new Notification(title, options);
                            if(link) {
                                notification.onclick = function() {
                                    window.open(link,"_self");
                                }
                            }
                        } else if (Notification.permission !== "denied") {
                            Notification.requestPermission(function (permission) {
                                if (permission === "granted") {
                                    var notification = new Notification(title, options);
                                    if(link) {
                                        notification.onclick = function() {
                                            window.open(link,"_self");
                                        }
                                    }
                                }
                            });
                        }
                        };

                    ';
            $result.=  wf_tag('script', true);
        }
        return ($result);
    }

    /**
     * Renders widget code if it required for current situation
     * 
     * @return string/void
     */
    public function renderWidget() {
        $result = '';
        if (cfr('FWTBT')) {
            if (@$this->altCfg['FWTBT_ENABLED']) {
                $widget = $this->getCallsNotification();
                if ($this->anywhere) {
                    $result.= $widget;
                } else {
                    if ((@$_GET['module'] == 'taskbar') OR ( !isset($_GET['module']))) {
                        $result.= $widget;
                    }
                }

                //per-admin controls
                if ((!empty($this->showFor) AND ( !isset($this->showFor[$this->myLogin])))) {
                    $result = '';
                }
                return($result);
            }
        }
    }

}
