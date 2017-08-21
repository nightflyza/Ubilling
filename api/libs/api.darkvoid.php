<?php

class DarkVoid {

    /**
     * 
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains current user login
     *
     * @var string
     */
    protected $myLogin = '';

    /**
     * Contains alerts cache
     *
     * @var string
     */
    protected $alerts = '';

    /**
     * Contains default cache timeout in minutes
     *
     * @var int
     */
    protected $cacheTime = '10';

    /**
     * Cache storage path
     */
    const CACHE_PATH = 'exports/';

    /**
     * Cache prefix
     */
    const CACHE_PREFIX = 'darkvoid.';

    public function __construct() {
        if (LOGGED_IN) {
            $this->setMyLogin();
            $this->loadAlter();
            $this->loadAlerts();
        }
    }

    /**
     * Loads alerts from per-user cache or from database if needed
     * 
     * @return void
     */
    protected function loadAlerts() {
        $cacheName = self::CACHE_PATH . self::CACHE_PREFIX . $this->myLogin;
        $cacheTime = time() - ($this->cacheTime * 60); //in minutes

        $updateCache = false;
        if (file_exists($cacheName)) {
            $updateCache = false;
            if ((filemtime($cacheName) > $cacheTime)) {
                $updateCache = false;
            } else {
                $updateCache = true;
            }
        } else {
            $updateCache = true;
        }

        if ($updateCache) {
            //ugly hack to prevent alerts update on tsms and senddog modules
            if (isset($_GET['module'])) {
                if (($_GET['module'] != 'turbosms') AND ( ( $_GET['module'] != 'senddog')) AND ( ( $_GET['module'] != 'remoteapi'))) {
                    //renew cache
                    $this->updateAlerts();
                }
            } else {
                //renew cache
                $this->updateAlerts();
            }
        } else {
            //read from cache
            @$this->alerts = file_get_contents($cacheName);
        }
    }

    /**
     * Sets private login property
     * 
     * @return
     */
    protected function setMyLogin() {
        $this->myLogin = whoami();
    }

    /**
     * Loads global alter.ini config into protected property
     * 
     * @global type $ubillingConfig
     * @return void
     */
    protected function loadAlter() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Renders available and enabled alerts into  DarkVoid notification area
     * 
     * @return void
     */
    protected function updateAlerts() {
        //new tickets alert
        if ($this->altCfg['TB_NEWTICKETNOTIFY']) {
            $newticketcount = zb_TicketsGetAllNewCount();
            if ($newticketcount != 0) {
                $this->alerts.=wf_Link('?module=ticketing', wf_img('skins/ticketnotify.gif', $newticketcount . ' ' . __('support tickets expected processing')), false);
            }
        }

        //new signups notification
        if ($this->altCfg['SIGREQ_ENABLED']) {
            $signups = new SignupRequests();
            $newreqcount = $signups->getAllNewCount();
            if ($newreqcount != 0) {
                $this->alerts.= wf_Link('?module=sigreq', wf_img('skins/sigreqnotify.gif', $newreqcount . ' ' . __('signup requests expected processing')), false);
            }
        }

        //check for unread messages in instant messanger
        if ($this->altCfg['TB_UBIM']) {
            if (cfr('UBIM')) {
                $unreadMessageCount = im_CheckForUnreadMessages();
                if ($unreadMessageCount) {
                    //we have new messages
                    $unreadIMNotify = __('You received') . ' ' . $unreadMessageCount . ' ' . __('new messages');
                    $urlIM = $unreadIMNotify . wf_delimiter() . wf_Link("?module=ubim&checknew=true", __('Click here to go to the instant messaging service.'), false, 'ubButton');
                    $this->alerts.=wf_Link("?module=ubim&checknew=true", wf_img("skins/ubim_blink.gif", $unreadMessageCount . ' ' . __('new message received')), false, '');
                    //$this->alerts.=wf_modalOpened(__('New messages received'), $urlIM, '450', '200');
                }
            }
        }

        //check sms sending queue 
        if ($this->altCfg['SENDDOG_ENABLED']) {
            $smsQueueCount = rcms_scandir(DATA_PATH . 'tsms/');
            $smsQueueCount = sizeof($smsQueueCount);
            if ($smsQueueCount > 0) {
                $this->alerts.=wf_Link("?module=tsmsqueue", wf_img("skins/sms.png", $smsQueueCount . ' ' . __('SMS in queue')), false, '');
            }
        }

        //police dog alerts
        if ($this->altCfg['POLICEDOG_ENABLED']) {
            $policeDogQuery = "SELECT COUNT(`id`) from `policedogalerts`";
            $policeDogCount = simple_query($policeDogQuery);
            $policeDogCount = $policeDogCount['COUNT(`id`)'];
            if ($policeDogCount > 0) {
                $this->alerts.=wf_Link('?module=policedog&show=fastscan', wf_img('skins/policedogalert.png', $policeDogCount . ' ' . __('Wanted MAC detected')), false, '');
            }
        }


        if ($this->altCfg['TB_TASKMANNOTIFY']) {
            //only "for me" tasks notification
            if ($this->altCfg['TB_TASKMANNOTIFY'] == 1) {
                $undoneTasksCount = ts_GetUndoneCountersMy();
                if ($undoneTasksCount > 0) {
                    $undoneAlert = $undoneTasksCount . ' ' . __('Undone tasks') . ' ' . __('for me');
                    $this->alerts.=wf_Link("?module=taskman&show=undone", wf_img("skins/jobnotify.png", $undoneAlert), false, '');
                }
            }
            //total undone tasks count notification
            if ($this->altCfg['TB_TASKMANNOTIFY'] == 2) {
                $undoneTasksCount = ts_GetUndoneCountersAll();
                if ($undoneTasksCount > 0) {
                    $undoneAlert = $undoneTasksCount . ' ' . __('Undone tasks') . ' ' . __('for all');
                    $this->alerts.=wf_Link("?module=taskman&show=undone", wf_img("skins/jobnotify.png", $undoneAlert), false, '');
                }
            }

            //total+my undone tasks count notification
            if ($this->altCfg['TB_TASKMANNOTIFY'] == 3) {
                $undoneTasksCount = ts_GetUndoneCountersAll();
                if ($undoneTasksCount > 0) {
                    $undoneTasksCountMy = ts_GetUndoneCountersMy();
                    $undoneAlert = $undoneTasksCount . ' ' . __('Undone tasks') . ': ' . __('for all') . ' ' . ($undoneTasksCount - $undoneTasksCountMy) . ' / ' . __('for me') . ' ' . $undoneTasksCountMy;
                    $this->alerts.=wf_Link("?module=taskman&show=undone", wf_img("skins/jobnotify.png", $undoneAlert), false, '');
                }
            }
        }

        //missed Askozia calls notification
        if ($this->altCfg['ASKOZIA_ENABLED']) {
            $wdycCache = 'exports/whydoyoucall.dat';
            if (file_exists($wdycCache)) {
                $cacheData = file_get_contents($wdycCache);
                if (!empty($wdycCache)) {
                    $cacheData = unserialize($cacheData);
                    $missedCallsCount = sizeof($cacheData);
                    if ($missedCallsCount > 0) {
                        $missedCallsAlert = $missedCallsCount . ' ' . __('users tried to contact you but could not');
                        $this->alerts.=wf_Link('?module=whydoyoucall', wf_img("skins/wdycnotify.png", $missedCallsAlert), false, '');
                    }
                }
            }
        }

        //NAS servers monitoring
        if (isset($this->altCfg['NASMON_ENABLED'])) {
            if ($this->altCfg['NASMON_ENABLED']) {
                $nasMon = new NasMon();
                $this->alerts.=$nasMon->getNasAlerts();
            }
        }

        //switchmon at notify area
        if ($this->altCfg['TB_SWITCHMON']) {
            $dead_raw = zb_StorageGet('SWDEAD');
            $last_pingtime = zb_StorageGet('SWPINGTIME');
            $deathTime = zb_SwitchesGetAllDeathTime();
            $deadarr = array();
            $content = '';

            if ($this->altCfg['SWYMAP_ENABLED']) {
                $content = wf_Link('?module=switchmap', wf_img('skins/swmapsmall.png', __('Switches map')), false);
            }

            $content.= wf_AjaxLoader() . wf_AjaxLink("?module=switches&forcereping=true&ajaxping=true", wf_img('skins/refresh.gif', __('Force ping')), 'switchping', true, '');



            if ($dead_raw) {
                $deadarr = unserialize($dead_raw);
                if (!empty($deadarr)) {
                    //there is some dead switches
                    $deadcount = sizeof($deadarr);
                    if ($this->altCfg['SWYMAP_ENABLED']) {
                        //getting geodata
                        $switchesGeo = zb_SwitchesGetAllGeo();
                    }
                    //ajax container
                    $content.=wf_tag('div', false, '', 'id="switchping"');

                    foreach ($deadarr as $ip => $switch) {
                        if ($this->altCfg['SWYMAP_ENABLED']) {
                            if (isset($switchesGeo[$ip])) {
                                if (!empty($switchesGeo[$ip])) {
                                    $devicefind = wf_Link('?module=switchmap&finddevice=' . $switchesGeo[$ip], wf_img('skins/icon_search_small.gif', __('Find on map'))) . ' ';
                                } else {
                                    $devicefind = '';
                                }
                            } else {
                                $devicefind = '';
                            }
                        } else {
                            $devicefind = '';
                        }
                        //check morgue records for death time
                        if (isset($deathTime[$ip])) {
                            $deathClock = wf_img('skins/clock.png', __('Switch dead since') . ' ' . $deathTime[$ip]) . ' ';
                        } else {
                            $deathClock = '';
                        }
                        //switch location link
                        $switchLocator = wf_Link('?module=switches&gotoswitchbyip=' . $ip, web_edit_icon(__('Go to switch')));
                        //add switch as dead
                        $content.=$devicefind . ' ' . $switchLocator . ' ' . $deathClock . $ip . ' - ' . $switch . '<br>';
                    }

                    //ajax container end
                    $content.=wf_delimiter() . __('Cache state at time') . ': ' . date("H:i:s", $last_pingtime) . wf_tag('div', true);

                    $this->alerts.=wf_tag('div', false, 'ubButton') . wf_modal(__('Dead switches') . ': ' . $deadcount, __('Dead switches'), $content, '', '500', '400') . wf_tag('div', true);
                } else {
                    $content.=wf_tag('div', false, '', 'id="switchping"') . __('Switches are okay, everything is fine - I guarantee') . wf_delimiter() . __('Cache state at time') . ': ' . date("H:i:s", $last_pingtime) . wf_tag('div', true);
                    $this->alerts.=wf_tag('div', false, 'ubButton') . wf_modal(__('All switches alive'), __('All switches alive'), $content, '', '500', '400') . wf_tag('div', true);
                }
            } else {
                $content.=wf_tag('div', false, '', 'id="switchping"') . __('Switches are okay, everything is fine - I guarantee') . wf_delimiter() . __('Cache state at time') . ': ' . @date("H:i:s", $last_pingtime) . wf_tag('div', true);
                $this->alerts.=wf_tag('div', false, 'ubButton') . wf_modal(__('All switches alive'), __('All switches alive'), $content, '', '500', '400') . wf_tag('div', true);
            }
        }


        file_put_contents(self::CACHE_PATH . self::CACHE_PREFIX . $this->myLogin, $this->alerts);
    }

    /**
     * Returns raw alerts data
     * 
     * @return string
     */
    public function render() {
        return ($this->alerts);
    }

    /**
     * Flushes all users alert cache
     * 
     * @return void
     */
    public function flushCache() {
        $allCache = rcms_scandir(self::CACHE_PATH, self::CACHE_PREFIX . '*', 'file');
        if (!empty($allCache)) {
            foreach ($allCache as $io => $each) {
                @unlink(self::CACHE_PATH . $each);
            }
        }
    }

}

?>