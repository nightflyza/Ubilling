<?php

/**
 * Notification area aka DarkVoid class
 */
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
     * Contains non-cachable alerts & notifications
     *
     * @var string
     */
    protected $dynamicArea = '';

    /**
     * Contains default cache timeout in minutes
     *
     * @var int
     */
    protected $cacheTime = '10';

    /**
     * UbillingConfig object placeholder
     *
     * @var object
     */
    protected $ubConfig = null;

    /**
     * Array of modules that must be skipped on alert updates
     *
     * @var array
     */
    protected $skipOnModules = array();

    /**
     * Contains current module
     *
     * @var string
     */
    protected $currentModule = '';

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
            $this->setCurrentModule();
            $this->setModSkip();
            $this->setMyLogin();
            $this->loadAlter();
            $this->loadAlerts();
            $this->loadDynamicArea();
        }
    }

    /**
     * Sets current instance current route module name
     *
     * @return void
     */
    protected function setCurrentModule() {
        if (ubRouting::checkGet('module')) {
            $this->currentModule = ubRouting::get('module', 'vf');
        }
    }

    /**
     * Sets modules array to be skipped on alert updates to prevent DB ops
     * 
     * @return void
     */
    protected function setModSkip() {
        $this->skipOnModules = array('turbosms', 'senddog', 'remoteapi', 'updatemanager');
        $this->skipOnModules = array_flip($this->skipOnModules);
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
            if (!empty($this->currentModule)) {
                if (!isset($this->skipOnModules[$this->currentModule])) {
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
     * Loads dynamic, non-cachable dark-void conten
     *
     * @return void
     */
    protected function loadDynamicArea() {
        //Taskbar quick search
        if (isset($this->altCfg['TB_QUICKSEARCH_ENABLED'])) {
            if ($this->altCfg['TB_QUICKSEARCH_ENABLED']) {
                if (@$this->altCfg['TB_QUICKSEARCH_INLINE'] == 1) {
                    if ($this->currentModule == 'taskbar' or empty($this->currentModule)) {
                        $this->dynamicArea .= web_TaskBarQuickSearchForm();
                        //overriding default style
                        $this->dynamicArea .= wf_tag('style');
                        $this->dynamicArea .= '
                        .tbqsearchform {
                                float: right;
                                margin-right: 0px;
                                margin-left: 5px;
                                position: relative;
                                display: flex;
                                align-items: center;
                        }
                        ';
                        $this->dynamicArea .= wf_tag('style', true);
                    }
                }
            }
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
        $this->ubConfig = $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
        if (isset($this->altCfg['DARKVOID_CACHETIME'])) {
            if ($this->altCfg['DARKVOID_CACHETIME']) {
                $this->cacheTime = $this->altCfg['DARKVOID_CACHETIME'];
            }
        }
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
                $this->alerts .= wf_Link('?module=ticketing', wf_img('skins/ticketnotify.gif', $newticketcount . ' ' . __('support tickets expected processing')), false);
            }
        }

        //new signups notification
        if ($this->altCfg['SIGREQ_ENABLED']) {
            $signups = new SignupRequests();
            $newreqcount = $signups->getAllNewCount();
            if ($newreqcount != 0) {
                $this->alerts .= wf_Link('?module=sigreq', wf_img('skins/sigreqnotify.gif', $newreqcount . ' ' . __('signup requests expected processing')), false);
            }
        }

        //check for unread messages in instant messanger
        if ($this->altCfg['TB_UBIM']) {
            if (cfr('UBIM')) {
                $ubIm = new UBMessenger();
                $unreadMessageCount = $ubIm->checkForUnreadMessages();
                if ($unreadMessageCount) {
                    //yes, we have new messages
                    $unreadIMNotify = __('You received') . ' ' . $unreadMessageCount . ' ' . __('new messages');
                    $this->alerts .= wf_Link($ubIm::URL_ME . '&' . $ubIm::ROUTE_REFRESH . '=true', wf_img("skins/ubim_blink.gif", $unreadMessageCount . ' ' . __('new message received')), false, '');
                }
            }
        }

        //check sms sending queue 
        if ($this->altCfg['SENDDOG_ENABLED']) {
            $smsQueueCount = rcms_scandir(DATA_PATH . 'tsms/');
            $smsQueueCount = sizeof($smsQueueCount);
            if ($smsQueueCount > 0) {
                $this->alerts .= wf_Link('?module=tsmsqueue', wf_img('skins/sms.png', $smsQueueCount . ' ' . __('SMS in queue')), false, '');
            }

            if ($this->altCfg['SENDDOG_PARALLEL_MODE']) {
                $sendDogPid = SendDog::PID_PATH;
                if (file_exists($sendDogPid)) {
                    $this->alerts .= wf_Link('?module=tsmsqueue', wf_img('skins/dog_stand.png', __('SendDog is working')), false, '');
                }
            }
        }

        //police dog alerts
        if ($this->altCfg['POLICEDOG_ENABLED']) {
            $policeDogQuery = "SELECT COUNT(`id`) from `policedogalerts`";
            $policeDogCount = simple_query($policeDogQuery);
            $policeDogCount = $policeDogCount['COUNT(`id`)'];
            if ($policeDogCount > 0) {
                $this->alerts .= wf_Link('?module=policedog&show=fastscan', wf_img('skins/policedogalert.png', $policeDogCount . ' ' . __('Wanted MAC detected')), false, '');
            }
        }


        if ($this->altCfg['TB_TASKMANNOTIFY']) {
            //only "for me" tasks notification
            if ($this->altCfg['TB_TASKMANNOTIFY'] == 1) {
                $undoneTasksCount = ts_GetUndoneCountersMy();
                if ($undoneTasksCount > 0) {
                    $undoneAlert = $undoneTasksCount . ' ' . __('Undone tasks') . ' ' . __('for me');
                    $this->alerts .= wf_Link("?module=taskman&show=undone", wf_img("skins/jobnotify.png", $undoneAlert), false, '');
                }
            }
            //total undone tasks count notification
            if ($this->altCfg['TB_TASKMANNOTIFY'] == 2) {
                $undoneTasksCount = ts_GetUndoneCountersAll();
                if ($undoneTasksCount > 0) {
                    $undoneAlert = $undoneTasksCount . ' ' . __('Undone tasks') . ' ' . __('for all');
                    $this->alerts .= wf_Link("?module=taskman&show=undone", wf_img("skins/jobnotify.png", $undoneAlert), false, '');
                }
            }

            //total+my undone tasks count notification
            if ($this->altCfg['TB_TASKMANNOTIFY'] == 3) {
                $undoneTasksCount = ts_GetUndoneCountersAll();
                if ($undoneTasksCount > 0) {
                    $undoneTasksCountMy = ts_GetUndoneCountersMy();
                    $undoneAlert = $undoneTasksCount . ' ' . __('Undone tasks') . ': ' . __('for all') . ' ' . ($undoneTasksCount - $undoneTasksCountMy) . ' / ' . __('for me') . ' ' . $undoneTasksCountMy;
                    $this->alerts .= wf_Link("?module=taskman&show=undone", wf_img("skins/jobnotify.png", $undoneAlert), false, '');
                }
            }
        }

        //missed calls notification
        if (@$this->altCfg['WDYC_ENABLED']) {
            $wdycCache = 'exports/whydoyoucall.dat';
            if (file_exists($wdycCache)) {
                $cacheData = file_get_contents($wdycCache);
                if (!empty($wdycCache)) {
                    $cacheData = unserialize($cacheData);
                    $missedCallsCount = sizeof($cacheData);
                    if ($missedCallsCount > 0) {
                        $missedCallsAlert = $missedCallsCount . ' ' . __('users tried to contact you but could not');
                        $this->alerts .= wf_Link('?module=whydoyoucall', wf_img("skins/wdycnotify.png", $missedCallsAlert), false, '');
                    }
                }
            }
        }

        //callback services
        if (@$this->altCfg['CALLMEBACK_ENABLED']) {
            $callMeBack = new CallMeBack();
            $undoneCallsCount = $callMeBack->getUndoneCount();
            if ($undoneCallsCount > 0) {
                $callmeBackAlert = $undoneCallsCount . ' ' . __('Users are waiting for your call');
                $this->alerts .= wf_Link('?module=callmeback', wf_img("skins/cmbnotify.png", $callmeBackAlert), false, '');
            }
        }

        //NAS servers monitoring
        if (isset($this->altCfg['NASMON_ENABLED'])) {
            if ($this->altCfg['NASMON_ENABLED']) {
                $nasMon = new NasMon();
                $this->alerts .= $nasMon->getNasAlerts();
            }
        }

        //BGP sessions monitoring
        if (isset($this->altCfg['BGPMON_ENABLED'])) {
            if ($this->altCfg['BGPMON_ENABLED']) {
                $bgpMon = new BGPMon();
                $this->alerts .= $bgpMon->getPeersAlerts();
            }
        }

        //watchdog maintenance mode notification
        if (isset($this->altCfg['WATCHDOG_ENABLED'])) {
            if ($this->altCfg['WATCHDOG_ENABLED']) {
                $watchDogMaintenance = zb_StorageGet('WATCHDOG_MAINTENANCE');
                $watchDogSmsSilence = zb_StorageGet('WATCHDOG_SMSSILENCE');
                if ($watchDogMaintenance) {
                    $this->alerts .= wf_Link('?module=watchdog', wf_img('skins/maintenance.png', __('Watchdog') . ': ' . __('Disabled')));
                }

                if ($watchDogSmsSilence) {
                    $this->alerts .= wf_Link('?module=watchdog', wf_img('skins/smssilence.png', __('Watchdog') . ': ' . __('SMS silence')));
                }
            }
        }

        //switchmon at notify area
        if ($this->altCfg['TB_SWITCHMON']) {
            $dead_raw = zb_StorageGet('SWDEAD');
            $last_pingtime = zb_StorageGet('SWPINGTIME');

            if (!is_numeric($last_pingtime)) {
                $last_pingtime = 0;
            }
            $deathTime = zb_SwitchesGetAllDeathTime();
            $deadarr = array();
            $content = '';

            if ($this->altCfg['SWYMAP_ENABLED']) {
                $content = wf_Link('?module=switchmap', wf_img('skins/swmapsmall.png', __('Switches map')), false);
            }

            $content .= wf_AjaxLoader() . wf_AjaxLink("?module=switches&forcereping=true&ajaxping=true", wf_img('skins/refresh.gif', __('Force ping')), 'switchping', true, '');



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
                    $content .= wf_tag('div', false, '', 'id="switchping"');

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
                        $content .= $devicefind . ' ' . $switchLocator . ' ' . $deathClock . $ip . ' - ' . $switch . '<br>';
                    }

                    //ajax container end
                    $content .= wf_delimiter() . __('Cache state at time') . ': ' . date("H:i:s", $last_pingtime) . wf_tag('div', true);

                    $this->alerts .= wf_tag('div', false, 'ubButton') . wf_modal(__('Dead switches') . ': ' . $deadcount, __('Dead switches'), $content, '', '500', '400') . wf_tag('div', true);
                } else {
                    $content .= wf_tag('div', false, '', 'id="switchping"') . __('Switches are okay, everything is fine - I guarantee') . wf_delimiter() . __('Cache state at time') . ': ' . date("H:i:s", $last_pingtime) . wf_tag('div', true);
                    $this->alerts .= wf_tag('div', false, 'ubButton') . wf_modal(__('All switches alive'), __('All switches alive'), $content, '', '500', '400') . wf_tag('div', true);
                }
            } else {
                $content .= wf_tag('div', false, '', 'id="switchping"') . __('Switches are okay, everything is fine - I guarantee') . wf_delimiter() . __('Cache state at time') . ': ' . @date("H:i:s", $last_pingtime) . wf_tag('div', true);
                $this->alerts .= wf_tag('div', false, 'ubButton') . wf_modal(__('All switches alive'), __('All switches alive'), $content, '', '500', '400') . wf_tag('div', true);
            }
        }

        //ForWhotTheBellTolls notification widget
        if (isset($this->altCfg['FWTBT_ENABLED'])) {
            if ($this->altCfg['FWTBT_ENABLED']) {
                $fwtbtFront = new ForWhomTheBellTolls();
                $this->alerts .= $fwtbtFront->renderWidget();
            }
        }

        //Dreamkas notification
        if ($this->ubConfig->getAlterParam('DREAMKAS_ENABLED') and $this->ubConfig->getAlterParam('DREAMKAS_NOTIFICATIONS_ENABLED')) {
            $dsNotifyFront = new DreamKasNotifications();
            $this->alerts .= $dsNotifyFront->renderWidget();
        }

        //I hope this service died.
        if ($this->ubConfig->getAlterParam('INSURANCE_ENABLED')) {
            $insurance = new Insurance(false);
            $hinsReqCount = $insurance->getUnprocessedHinsReqCount();
            if ($hinsReqCount > 0) {
                $insuranceRequestsAlert = $hinsReqCount . ' ' . __('insurance requests waiting for your reaction');
                $this->alerts .= wf_Link($insurance::URL_ME, wf_img('skins/insurance_notify.png', $insuranceRequestsAlert));
            }
        }

        //aerial alerts basic notification
        if ($this->ubConfig->getAlterParam('AERIAL_ALERTS_ENABLED')) {
            if ($this->ubConfig->getAlterParam('AERIAL_ALERTS_NOTIFY')) {
                $monitorRegion = $this->ubConfig->getAlterParam('AERIAL_ALERTS_NOTIFY');
                $aerialAlerts = new AerialAlerts($monitorRegion);
                $regionAlert = $aerialAlerts->renderRegionNotification($monitorRegion);
                if (!empty($regionAlert)) {
                    $this->alerts .= $regionAlert;
                }
            }
        }

        //birthday reminder
        if ($this->ubConfig->getAlterParam('BIRTHDAY_REMINDER')) {
            if (cfr('EMPLOYEEDIR')) {
                $todayBirthdays=em_EmployeeGetTodayBirthdays();
                if (!empty($todayBirthdays)) {
                    $birthdayList='';
                    foreach ($todayBirthdays as $eachId=>$eachData) {
                        $birthdayList.=$eachData['name'].' '; 
                    }
                    $this->alerts .= wf_Link('?module=employee', wf_img('skins/cake32.png', __('Birthday').' '.__('today').': '.$birthdayList));
                }
            }
        }

        //running generators alerts
        if ($this->ubConfig->getAlterParam('GENERATORS_ENABLED')) {
            if ($this->ubConfig->getAlterParam('TB_GENERATORS_NOTIFY')) {
            $generatorsDevicesDb=new NyanORM(Generators::TABLE_DEVICES);
            $generatorsDevicesDb->where('running', '=', 1);

            $generatorsDevices=$generatorsDevicesDb->getAll();
            if (!empty($generatorsDevices)) {
                $runningGeneratorsCount=sizeof($generatorsDevices);
                if ($runningGeneratorsCount > 0) {
                    $this->alerts .= wf_Link(Generators::URL_ME.'&'.Generators::ROUTE_DEVICES.'=true', wf_img('skins/generator32.png', __('Generators running now').': '.$runningGeneratorsCount));
                }
            }
         }
        }

        //appending some debug string to validate cache expire
        $this->alerts .= '<!-- DarkVoid saved: ' . curdatetime() . ' -->';

        //saving per-admin cache data
        file_put_contents(self::CACHE_PATH . self::CACHE_PREFIX . $this->myLogin, $this->alerts);
    }

    /**
     * Returns raw alerts data
     * 
     * @return string
     */
    public function render() {
        $result = $this->alerts;
        $result .= $this->dynamicArea;
        return ($result);
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
