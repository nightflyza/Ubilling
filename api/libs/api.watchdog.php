<?php

/**
 * Flexible system monitoring aka WatchDog implementation
 */
class WatchDog {

    /**
     * Contains watchdog tasks data
     *
     * @var array
     */
    protected $taskData = array();

    /**
     * Contains old results returned by watchdog tasks
     *
     * @var array
     */
    protected $oldResults = array();

    /**
     * Contains current watchdog run tasks results
     *
     * @var array
     */
    protected $curResults = array();

    /**
     * Contains watchdog configuration as key=>value
     *
     * @var array
     */
    protected $settings = array();

    /**
     * System SMS object placeholder
     *
     * @var object
     */
    protected $sms = '';

    /**
     * System Email object placeholder
     *
     * @var object
     */
    protected $email = '';

    /**
     * One-Punch object placeholder
     *
     * @var object
     */
    protected $onePunch = '';

    /**
     * System Telegram object placeholder
     *
     * @var object
     */
    protected $telegram = '';

    const PARAM_EX = 'NO_REQUIRED_TASK_PARAM_';
    const PARAMFMT_EX = 'WRONG_FORMAT_TASK_PARAM_';
    const SETTINGS_EX = 'NO_SETTINGS_LOADED';

    public function __construct() {
        //get all current watchdog tasks
        $this->loadTasks();

        //get all previous polling results
        $this->loadOldResults();

        //load watchdog settings from database
        $this->loadSettings();

        //init sms class
        $this->initSMS();

        //init mail class
        $this->initEmail();

        //init telegram class
        $this->initTelegram();

        //inits onepunch scripts
        $this->initOnePunch();
    }

    /**
     * Returns current watchdog settings
     * 
     * @return array
     */
    public function getSettings() {
        $result = $this->settings;
        return ($result);
    }

    /**
     * Loads all previous tasks execution results into private property oldResults
     * 
     * @return void
     */
    protected function loadOldResults() {
        if (!empty($this->taskData)) {
            foreach ($this->taskData as $iy => $eachPrevResult) {
                $this->oldResults[$eachPrevResult['id']] = $eachPrevResult['oldresult'];
            }
        }
    }

    /**
     * Loads an active task list from database and pushes it into private property taskData
     * 
     * @return void
     */
    protected function loadTasks() {
        $taskQuery = "SELECT * from `watchdog` WHERE `active`='1';";
        $alltasks = simple_queryall($taskQuery);
        if (!empty($alltasks)) {
            foreach ($alltasks as $iz => $eachTask) {
                $this->taskData[$eachTask['id']]['id'] = $eachTask['id'];
                $this->taskData[$eachTask['id']]['active'] = $eachTask['active'];
                $this->taskData[$eachTask['id']]['name'] = $eachTask['name'];
                $this->taskData[$eachTask['id']]['checktype'] = $eachTask['checktype'];
                $this->taskData[$eachTask['id']]['param'] = $eachTask['param'];
                $this->taskData[$eachTask['id']]['operator'] = $eachTask['operator'];
                $this->taskData[$eachTask['id']]['condition'] = $eachTask['condition'];
                $this->taskData[$eachTask['id']]['action'] = $eachTask['action'];
                $this->taskData[$eachTask['id']]['oldresult'] = $eachTask['oldresult'];
            }
        }
    }

    /**
     * Gets watchdog settings from database and load it into settings property
     * 
     * @return void
     */
    protected function loadSettings() {
        $alert = zb_StorageGet('WATCHDOG_ALERT');
        $phones = zb_StorageGet('WATCHDOG_PHONES');
        $emails = zb_StorageGet('WATCHDOG_EMAILS');
        $telegramchats = zb_StorageGet('WATCHDOG_TELEGRAM');
        $maintenanceMode = zb_StorageGet('WATCHDOG_MAINTENANCE');

        $this->settings['WATCHDOG_ALERT'] = $alert;
        $this->settings['WATCHDOG_PHONES'] = $phones;
        $this->settings['WATCHDOG_EMAILS'] = $emails;
        $this->settings['WATCHDOG_TELEGRAM'] = $telegramchats;
        $this->settings['WATCHDOG_MAINTENANCE'] = $maintenanceMode;

        if (empty($this->settings['WATCHDOG_ALERT'])) {
            throw new Exception(self::SETTINGS_EX);
        }
    }

    /**
     * Inits system SMS queue object
     * 
     * @return void
     */
    protected function initSMS() {
        $this->sms = new UbillingSMS();
    }

    /**
     * Inits system email queue object
     * 
     * @return void
     */
    protected function initEmail() {
        $this->email = new UbillingMail();
    }

    /**
     * Inits onepunch object
     * 
     * @return void
     */
    protected function initOnePunch() {
        $this->onePunch = new OnePunch();
    }

    /**
     * Inits system telegram messages queue object
     * 
     * @return void
     */
    protected function initTelegram() {
        $this->telegram = new UbillingTelegram();
    }

    /**
     * stores sms for deffered sending via senddog
     * 
     * @param string $number - number to send sms in internaitional format
     * @message string $message in utf8 encoding
     * 
     * @return array
     */
    public function sendSMS($number, $message) {
        $this->sms->sendSMS($this->safeEscapeString($number), $this->safeEscapeString($message), false, 'WATCHDOG');
    }

    /**
     * sends email notification
     * 
     * @param string $email - target email
     * @param string $message - message
     * 
     * @return void
     */
    protected function sendEmail($email, $message) {
        $subj = 'Ubilling ' . __('Watchdog');
        $message .= ' ' . date("Y-m-d H:i:s");
        $this->email->sendEmail($email, $subj, $message, 'WATCHDOG');
    }

    /**
     * ugly hack to dirty input data filtering with multiple DB links
     * 
     * @param $string - string to filter
     * 
     * @return string
     */
    protected function safeEscapeString($string) {
        @$result = preg_replace("#[~@\?\%\/\;=\*\>\<\"\']#Uis", '', $string);
        return ($result);
    }

    /**
     * Updates previous poll data in database
     * 
     * @param int $taskID - watchdog task id
     * @param string $value  - data to set as oldresult
     * 
     * @return void
     */
    protected function setOldValue($taskID, $value) {
        simple_update_field('watchdog', 'oldresult', $value, "WHERE `id`='" . $taskID . "'");
    }

    /**
     * Updates current run task results
     * 
     * @param int $taskID - watchdog task id
     * @param string $value  - data to set as newresult
     * 
     * @return void
     */
    protected function setCurValue($taskID, $value) {
        $this->curResults[$taskID] = $value;
    }

    /**
     * Execute some action for task
     * 
     * @param int $taskID - id of existing monitoring task to run
     * 
     * @return mixed
     */
    protected function doAction($taskID = NULL) {
        if (!empty($taskID)) {
            switch ($this->taskData[$taskID]['checktype']) {
                //do the system icmp ping 
                case 'icmpping':
                    if (!empty($this->taskData[$taskID]['param'])) {
                        $result = zb_PingICMP($this->taskData[$taskID]['param']);
                        $storeValue = ($result) ? 'true' : 'false';
                        $this->setOldValue($taskID, $storeValue);
                        $this->setCurValue($taskID, $storeValue);
                    } else {
                        throw new Exception(self::PARAM_EX . "ICMPPING");
                    }

                    break;
                //do the system icmp ping three times with hope of some result
                case 'hopeping':
                    if (!empty($this->taskData[$taskID]['param'])) {
                        $result = zb_PingICMPHope($this->taskData[$taskID]['param']);
                        $storeValue = ($result) ? 'true' : 'false';
                        $this->setOldValue($taskID, $storeValue);
                        $this->setCurValue($taskID, $storeValue);
                    } else {
                        throw new Exception(self::PARAM_EX . "HOPEPING");
                    }
                    break;
                //get raw http result    
                case 'httpget':
                    if (!empty($this->taskData[$taskID]['param'])) {
                        $httpUrl = $this->taskData[$taskID]['param'];
                        $result = @file_get_contents($httpUrl);
                        $result = trim($result);
                        $this->setOldValue($taskID, $result);
                        $this->setCurValue($taskID, $result);
                    } else {
                        throw new Exception(self::PARAM_EX . "HTTPGET");
                    }
                    break;
                //run some script    
                case 'script':
                    if (!empty($this->taskData[$taskID]['param'])) {
                        $command = $this->taskData[$taskID]['param'];
                        $result = shell_exec($command);
                        $result = trim($result);
                        $this->setOldValue($taskID, $result);
                        $this->setCurValue($taskID, $result);
                    } else {
                        throw new Exception(self::PARAM_EX . "SCRIPT");
                    }
                    break;
                //run one-punch script
                case 'onepunch':
                    if (!empty($this->taskData[$taskID]['param'])) {
                        $onePunchScriptData = $this->onePunch->getScriptContent($this->taskData[$taskID]['param']);
                        eval($onePunchScriptData);
                        $result = @$watchdogCallbackResult;
                        $this->setOldValue($taskID, $result);
                        $this->setCurValue($taskID, $result);
                    } else {
                        throw new Exception(self::PARAM_EX . "ONEPUNCH");
                    }
                    break;
                //do the tcp ping via some port    
                case 'tcpping':
                    if (!empty($this->taskData[$taskID]['param'])) {
                        if (ispos($this->taskData[$taskID]['param'], ':')) {
                            $tcpPingData = explode(':', $this->taskData[$taskID]['param']);
                            $tcpPingHost = $tcpPingData[0];
                            $tcpPingPort = $tcpPingData[1];
                            $tcpPingTimeout = 2;
                            @$connection = fsockopen($tcpPingHost, $tcpPingPort, $tcperrno, $tcperrstr, $tcpPingTimeout);
                            if (!$connection) {
                                $result = false;
                            } else {
                                $result = true;
                            }
                            $storeValue = ($result) ? 'true' : 'false';
                            $this->setOldValue($taskID, $storeValue);
                            $this->setCurValue($taskID, $storeValue);
                        } else {
                            throw new Exception(self::PARAMFMT_EX . "TCPPING");
                        }
                    } else {
                        throw new Exception(self::PARAM_EX . "TCPPING");
                    }
                    break;
                // gets some user traffic by his login
                case 'getusertraff':
                    if (!empty($this->taskData[$taskID]['param'])) {
                        $userLoging = mysql_real_escape_string($this->taskData[$taskID]['param']);
                        $traffQuery = "SELECT SUM(`D0`+`U0`) from `users` WHERE login='" . $userLogin . "'";
                        $traffData = simple_query($traffQuery);
                        $result = $traffData['SUM(`D0`+`U0`)'];
                        $this->setOldValue($taskID, $result);
                        $this->setCurValue($taskID, $result);
                    } else {
                        throw new Exception(self::PARAM_EX . "GETUSERTRAFF");
                    }
                    break;
                //check is some file exist?
                case 'fileexists':
                    if (!empty($this->taskData[$taskID]['param'])) {
                        $result = file_exists($this->taskData[$taskID]['param']);
                        $storeValue = ($result) ? 'true' : 'false';
                        $this->setOldValue($taskID, $storeValue);
                        $this->setCurValue($taskID, $storeValue);
                    } else {
                        throw new Exception(self::PARAM_EX . "FILEEXISTS");
                    }
                    break;
                //open helpdesk tickets count check    
                case 'opentickets':
                    if (!empty($this->taskData[$taskID]['param'])) {
                        $result = zb_TicketsGetAllNewCount();
                        $this->setOldValue($taskID, $result);
                        $this->setCurValue($taskID, $result);
                    } else {
                        throw new Exception(self::PARAM_EX . "OPENTICKETS");
                    }
                    break;
            }
        } else {
            $result = 0;
        }
        return ($result);
    }

    /**
     * gets oldresult from some task id
     * 
     * @param int $taskID - existing task id
     * 
     * @return string
     */
    protected function getOldValue($taskID = NULL) {
        $result = $this->oldResults[$taskID];
        return ($result);
    }

    /**
     * Checks condition for selected task
     * 
     * @param int $taskID - existing task id
     * 
     * @return string
     */
    protected function checkCondition($taskID = NULL) {
        if (!empty($taskID)) {
            switch ($this->taskData[$taskID]['operator']) {
                //boolean true
                case '=true':
                    if ($this->doAction($taskID)) {
                        return (true);
                    } else {
                        return (false);
                    }
                    break;
                //boolean false    
                case '=false':
                    if (!$this->doAction($taskID)) {
                        return (true);
                    } else {
                        return (false);
                    }
                    break;
                //boolean equals    
                case '==':
                    $cond = trim($this->taskData[$taskID]['condition']);
                    $actres = trim($this->doAction($taskID));
                    if ($actres == $cond) {
                        return (true);
                    } else {
                        return (false);
                    }
                    break;
                //boolean not equals    
                case '!=':
                    $cond = trim($this->taskData[$taskID]['condition']);
                    $actres = trim($this->doAction($taskID));
                    if ($actres != $cond) {
                        return (true);
                    } else {
                        return (false);
                    }
                    break;
                //boolean gt    
                case '>':
                    $currentValue = $this->doAction($taskID);
                    $currentValue = trim($currentValue);
                    if ($currentValue > $this->taskData[$taskID]['condition']) {
                        return (true);
                    } else {
                        return (false);
                    }
                    break;
                //boolean lt    
                case '<':
                    $currentValue = $this->doAction($taskID);
                    $currentValue = trim($currentValue);
                    if ($currentValue < $this->taskData[$taskID]['condition']) {
                        return (true);
                    } else {
                        return (false);
                    }
                    break;
                //boolean gt or equal
                case '>=':
                    $currentValue = $this->doAction($taskID);
                    $currentValue = trim($currentValue);
                    if ($currentValue >= $this->taskData[$taskID]['condition']) {
                        return (true);
                    } else {
                        return (false);
                    }
                    break;
                //boolean lt or equal   
                case '<=':
                    $currentValue = $this->doAction($taskID);
                    $currentValue = trim($currentValue);
                    if ($currentValue <= $this->taskData[$taskID]['condition']) {
                        return (true);
                    } else {
                        return (false);
                    }
                    break;
                //changes against previous results    
                case 'changed':
                    $oldValue = $this->oldResults[$taskID];
                    $currentValue = $this->doAction($taskID);
                    if (is_bool($currentValue)) {
                        $currentValue = ($currentValue) ? 'true' : 'false';
                    }

                    if ($currentValue != $oldValue) {
                        return (true);
                    } else {
                        return (false);
                    }
                    break;
                //any changes against previois results    
                case 'notchanged':
                    $oldValue = $this->oldResults[$taskID];
                    $currentValue = $this->doAction($taskID);
                    if (is_bool($currentValue)) {
                        $currentValue = ($currentValue) ? 'true' : 'false';
                    }
                    if ($currentValue == $oldValue) {
                        return (true);
                    } else {
                        return (false);
                    }
                    break;
                //empty check
                case 'empty':
                    $emptyCheck = $this->doAction($taskID);
                    $emptyCheck = trim($emptyCheck);
                    if (empty($emptyCheck)) {
                        return (true);
                    } else {
                        return (false);
                    }
                    break;
                //not empty check
                case 'notempty':
                    $emptyCheck = $this->doAction($taskID);
                    $emptyCheck = trim($emptyCheck);
                    if (!empty($emptyCheck)) {
                        return (true);
                    } else {
                        return (false);
                    }
                    break;
                //like substring check
                case 'like':
                    $currentResult = $this->doAction($taskID);
                    $needCondition = $this->taskData[$taskID]['condition'];
                    if (ispos($currentResult, $needCondition)) {
                        return (true);
                    } else {
                        return (false);
                    }
                    break;
                //not like substring check
                case 'notlike':
                    $currentResult = $this->doAction($taskID);
                    $needCondition = $this->taskData[$taskID]['condition'];
                    if (!ispos($currentResult, $needCondition)) {
                        return (true);
                    } else {
                        return (false);
                    }
                    break;
            }
        } else
            return (0);
    }

    /**
     * Main task processing subroutine
     * 
     * @return void
     */
    public function processTask() {
        if (!$this->settings['WATCHDOG_MAINTENANCE']) {
            foreach ($this->taskData as $taskID => $eachProcessTask) {
                if ($this->checkCondition($taskID)) {
                    //task details collecting
                    $alertTaskName = $this->taskData[$taskID]['name'];


                    //if condition happens - do some task actions
                    $taskActions = $this->taskData[$taskID]['action'];
                    if (!empty($taskActions)) {
                        /* different actions handling */
                        // system log write
                        if (ispos($taskActions, 'log')) {
                            $notifyLogMessage = 'WATCHDOG NOTIFY THAT `' . $alertTaskName;
                            //attach old result to log message if needed
                            if (ispos($taskActions, 'oldresult')) {
                                $notifyLogMessage .= ' ' . $this->taskData[$taskID]['oldresult'];
                            }

                            //attach current results to log message
                            if (ispos($taskActions, 'andresult')) {
                                $notifyLogMessage .= ' ' . $this->curResults[$taskID];
                            }
                            $notifyLogMessage .= '`';
                            log_register($notifyLogMessage);
                        }
                        //send emails with alerts
                        if (ispos($taskActions, 'email')) {
                            if (!empty($this->settings['WATCHDOG_EMAILS'])) {
                                $allNotifyEmails = explode(',', $this->settings['WATCHDOG_EMAILS']);
                                if (!empty($allNotifyEmails)) {
                                    $notifyMessageMail = $this->settings['WATCHDOG_ALERT'] . ' ' . $alertTaskName;
                                    //attach old result to email if needed
                                    if (ispos($taskActions, 'oldresult')) {
                                        $notifyMessageMail .= ' ' . $this->taskData[$taskID]['oldresult'];
                                    }

                                    //attach current results
                                    if (ispos($taskActions, 'andresult')) {
                                        $notifyMessageMail .= ' ' . $this->curResults[$taskID];
                                    }

                                    foreach ($allNotifyEmails as $im => $eachmail) {
                                        $this->sendEmail($eachmail, $notifyMessageMail);
                                    }
                                }
                            }
                        }
                        //send telegram messages with alerts
                        if (ispos($taskActions, 'telegram')) {
                            if (!empty($this->settings['WATCHDOG_TELEGRAM'])) {
                                $allNotifyTelegramChats = explode(',', $this->settings['WATCHDOG_TELEGRAM']);
                                $additionalChats = array();
                                if (preg_match('!\((.*?)\)!si', $taskActions, $tmpAddChats)) {
                                    $additionalChats = explode(',', $tmpAddChats[1]);
                                    if (!empty($additionalChats)) {
                                        if (!ispos($taskActions, 'no_tg_primary')) {
                                            foreach ($additionalChats as $ig => $eachAdditionalChat) {
                                                if (!empty($eachAdditionalChat)) {
                                                    $allNotifyTelegramChats[] = $eachAdditionalChat;
                                                }
                                            }
                                        } else {
                                            $allNotifyTelegramChats = $additionalChats;
                                        }
                                    }
                                }

                                if (!empty($allNotifyTelegramChats)) {
                                    $notifyMessageTlg = $this->settings['WATCHDOG_ALERT'] . ' ' . $alertTaskName;
                                    //attach old result to email if needed
                                    if (ispos($taskActions, 'oldresult')) {
                                        $notifyMessageTlg .= ' ' . $this->taskData[$taskID]['oldresult'];
                                    }

                                    //attach current results
                                    if (ispos($taskActions, 'andresult')) {
                                        $notifyMessageTlg .= ' ' . $this->curResults[$taskID];
                                    }

                                    foreach ($allNotifyTelegramChats as $tlgm => $eachtlgchat) {
                                        $this->telegram->sendMessage($eachtlgchat, $notifyMessageTlg, false, 'WATCHDOG');
                                    }
                                }
                            }
                        }
                        //run some script with path like [path]
                        if (ispos($taskActions, 'script')) {
                            if (preg_match('!\[(.*?)\]!si', $taskActions, $tmpArr)) {
                                $runScriptPath = $tmpArr[1];
                            } else {
                                $runScriptPath = '';
                            }
                            if (!empty($runScriptPath)) {
                                shell_exec($runScriptPath);
                                log_register("WATCHDOG RUN SCRIPT `" . $runScriptPath . "`");
                            }
                        }

                        //send sms messages
                        if (ispos($taskActions, 'sms')) {
                            if (!empty($this->settings['WATCHDOG_PHONES'])) {
                                $allNotifyPhones = explode(',', $this->settings['WATCHDOG_PHONES']);
                                $additionalPhones = array();
                                if (preg_match('!\{(.*?)\}!si', $taskActions, $tmpAddPhones)) {
                                    $additionalPhones = explode(',', $tmpAddPhones[1]);
                                    if (!empty($additionalPhones)) {
                                        if (!ispos($taskActions, 'noprimary')) {
                                            foreach ($additionalPhones as $ig => $eachAdditionalPhone) {
                                                if (!empty($eachAdditionalPhone)) {
                                                    $allNotifyPhones[] = $eachAdditionalPhone;
                                                }
                                            }
                                        } else {
                                            $allNotifyPhones = $additionalPhones;
                                        }
                                    }
                                }


                                if (!empty($allNotifyPhones)) {
                                    $notifyMessage = $this->settings['WATCHDOG_ALERT'] . ' ' . $alertTaskName;
                                    //attach old result to sms if needed
                                    if (ispos($taskActions, 'oldresult')) {
                                        $notifyMessage .= ' ' . $this->taskData[$taskID]['oldresult'];
                                    }

                                    //attach current result to sms if needed
                                    if (ispos($taskActions, 'andresult')) {
                                        $notifyMessage .= ' ' . $this->curResults[$taskID];
                                    }

                                    foreach ($allNotifyPhones as $iu => $eachmobile) {
                                        $this->sendSMS($eachmobile, $notifyMessage);
                                    }
                                }
                            }
                        }
                    } else {
                        throw new Exception("NO_AVAILABLE_TASK_ACTIONS");
                    }
                }
            }
        } else {
            log_register('WATCHDOG MAINTENANCE TASKS SKIPPED');
        }
    }

}

/**
 * Watchdog tasks management and other interfaces
 */
class WatchDogInterface {

    /**
     * Contains all watchdog tasks
     *
     * @var array
     */
    protected $allTasks = array();

    /**
     * Contains watchdog settings as key=>value
     *
     * @var array
     */
    protected $settings = array();

    /**
     * Contains previous watchdog alerts parsed from log
     *
     * @var array
     */
    protected $previousAlerts = array();

    /**
     * Contains available checktypes
     *
     * @var array
     */
    protected $checktypes = array();

    /**
     * Contains available operators
     *
     * @var array
     */
    protected $operators = array();

    const TASKID_EX = 'NO_REQUIRED_TASK_ID';
    const TASKADD_EX = 'MISSING_REQUIRED_OPTION';

    /**
     * load all watchdog tasks intoo private prop allTasks
     * 
     * @return void
     */
    public function loadAllTasks() {
        $taskQuery = "SELECT * from `watchdog` ORDER BY `id` DESC;";
        $alltasks = simple_queryall($taskQuery);
        if (!empty($alltasks)) {
            foreach ($alltasks as $iz => $eachTask) {
                $this->allTasks[$eachTask['id']]['id'] = $eachTask['id'];
                $this->allTasks[$eachTask['id']]['active'] = $eachTask['active'];
                $this->allTasks[$eachTask['id']]['name'] = $eachTask['name'];
                $this->allTasks[$eachTask['id']]['checktype'] = $eachTask['checktype'];
                $this->allTasks[$eachTask['id']]['param'] = $eachTask['param'];
                $this->allTasks[$eachTask['id']]['operator'] = $eachTask['operator'];
                $this->allTasks[$eachTask['id']]['condition'] = $eachTask['condition'];
                $this->allTasks[$eachTask['id']]['action'] = $eachTask['action'];
                $this->allTasks[$eachTask['id']]['oldresult'] = $eachTask['oldresult'];
            }
        }
    }

    /**
     * load all watchdog previous alerts into private data prop
     * 
     * @return void
     */
    public function loadAllPreviousAlerts() {
        //select year to load
        if (wf_CheckPost(array('alertsyearsel'))) {
            $curYear = vf($_POST['alertsyearsel'], 3);
        } else {
            $curYear = curyear();
        }

        $query = "SELECT `id`,`date`,`event` from `weblogs` WHERE `event` LIKE 'WATCHDOG NOTIFY THAT%' AND `date` LIKE '" . $curYear . "-%';";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->previousAlerts[$each['id']]['id'] = $each['id'];
                $this->previousAlerts[$each['id']]['date'] = $each['date'];
                $event = str_replace('WATCHDOG NOTIFY THAT', '', $each['event']);
                $event = str_replace('`', '', $event);
                $this->previousAlerts[$each['id']]['event'] = $event;
            }
        }
    }

    /**
     * protected property allTasks getter
     * 
     * @return array
     */
    public function getAllTasks() {
        $result = $this->allTasks;
        return ($result);
    }

    /**
     * Gets watchdog settings from database and load it into settings property
     * Also it sets default values into the database
     * 
     * @return void
     */
    public function loadSettings() {
        $alert = zb_StorageGet('WATCHDOG_ALERT');
        if (empty($alert)) {
            $alert = __('Watchdog notifies that');
            zb_StorageSet('WATCHDOG_ALERT', $alert);
        }
        $phones = zb_StorageGet('WATCHDOG_PHONES');
        if (empty($phones)) {
            zb_StorageSet('WATCHDOG_PHONES', '');
        }
        $emails = zb_StorageGet('WATCHDOG_EMAILS');
        if (empty($emails)) {
            zb_StorageSet('WATCHDOG_EMAILS', '');
        }
        $telegramchats = zb_StorageGet('WATCHDOG_TELEGRAM');
        $maintenanceMode = zb_StorageGet('WATCHDOG_MAINTENANCE');



        $this->settings['WATCHDOG_ALERT'] = $alert;
        $this->settings['WATCHDOG_PHONES'] = $phones;
        $this->settings['WATCHDOG_EMAILS'] = $emails;
        $this->settings['WATCHDOG_TELEGRAM'] = $telegramchats;
        $this->settings['WATCHDOG_MAINTENANCE'] = $maintenanceMode;

        $this->checktypes = array(
            'icmpping' => 'icmpping',
            'tcpping' => 'tcpping',
            'hopeping' => 'hopeping',
            'script' => 'script',
            'httpget' => 'httpget',
            'getusertraff' => 'getusertraff',
            'fileexists' => 'fileexists',
            'opentickets' => 'opentickets',
            'onepunch' => 'onepunch'
        );

        $this->operators = array(
            '=true' => '=true',
            '=false' => '=false',
            '==' => '==',
            '!=' => '!=',
            '>' => '>',
            '<' => '<',
            '>=' => '>=',
            '<=' => '<=',
            'empty' => 'empty',
            'notempty' => 'notempty',
            'changed' => 'changed',
            'notchanged' => 'notchanged',
            'like' => 'like',
            'notlike' => 'notlike'
        );
    }

    /**
     * Returns current watchdog settings
     * 
     * @return array
     */
    public function getSettings() {
        $result = $this->settings;
        return ($result);
    }

    /**
     * shows all available tasks list
     * 
     * @return string
     */
    public function listAllTasks() {
        $cells = wf_TableCell(__('ID'));
        $cells .= wf_TableCell(__('Active'));
        $cells .= wf_TableCell(__('Name'));
        $cells .= wf_TableCell(__('Check type'));
        $cells .= wf_TableCell(__('Parameter'));
        $cells .= wf_TableCell(__('Operator'));
        $cells .= wf_TableCell(__('Condition'));
        $cells .= wf_TableCell(__('Actions'));
        $cells .= wf_TableCell(__('Manage'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($this->allTasks)) {
            foreach ($this->allTasks as $io => $eachtask) {
                $details = wf_tag('pre') . print_r($eachtask, true) . wf_tag('pre', true);
                $detailLink = wf_modal($eachtask['id'], $eachtask['name'], $details, '', '600', '400');
                $cells = wf_TableCell($detailLink, '', '', 'sorttable_customkey="' . $eachtask['id'] . '"');
                $cells .= wf_TableCell(web_bool_led($eachtask['active']), '', '', 'sorttable_customkey="' . $eachtask['active'] . '"');
                $cells .= wf_TableCell($eachtask['name']);
                $cells .= wf_TableCell($eachtask['checktype']);
                $cells .= wf_TableCell($eachtask['param']);
                $cells .= wf_TableCell($eachtask['operator']);
                $cells .= wf_TableCell($eachtask['condition']);
                $cells .= wf_TableCell($eachtask['action']);

                $controls = wf_JSAlert('?module=watchdog&delete=' . $eachtask['id'], web_delete_icon(), __('Removing this may lead to irreparable results'));
                $controls .= wf_JSAlert('?module=watchdog&edit=' . $eachtask['id'], web_edit_icon(), __('Are you serious'));

                $cells .= wf_TableCell($controls);
                $rows .= wf_tag('tr', false, 'row5');
                $rows .= $cells;
                $rows .= wf_tag('tr', true);
            }
        }

        $result = wf_TableBody($rows, '100%', '0', 'sortable');
        return ($result);
    }

    /**
     * shows new task creation form
     * 
     * @return string
     */
    public function newTaskForm() {
        $inputs = wf_TextInput('newname', __('Name'), '', true);
        $inputs .= wf_Selector('newchecktype', $this->checktypes, __('Check type'), '', true);
        $inputs .= wf_TextInput('newparam', __('Parameter'), '', true);
        $inputs .= wf_Selector('newoperator', $this->operators, __('Operator'), '', true);
        $inputs .= wf_TextInput('newcondition', __('Condition'), '', true);
        $inputs .= wf_TextInput('newaction', __('Actions'), '', true);
        $inputs .= wf_CheckInput('newactive', __('Active'), true, true);
        $inputs .= wf_Submit(__('Create'));

        $form = wf_Form("", 'POST', $inputs, 'glamour');
        return ($form);
    }

    /**
     * shows modify form for some existing task
     *
     * @param int $taskID existing task id
     * 
     * @return string
     */
    public function editTaskForm($taskID) {

        $taskID = vf($taskID, 3);
        if (empty($taskID)) {
            throw new Exception(self::TASKID_EX);
        }

        $inputs = wf_TextInput('editname', __('Name'), $this->allTasks[$taskID]['name'], true);
        $inputs .= wf_Selector('editchecktype', $this->checktypes, __('Check type'), $this->allTasks[$taskID]['checktype'], true);
        $inputs .= wf_TextInput('editparam', __('Parameter'), $this->allTasks[$taskID]['param'], true);
        $inputs .= wf_Selector('editoperator', $this->operators, __('Operator'), $this->allTasks[$taskID]['operator'], true);
        $inputs .= wf_TextInput('editcondition', __('Condition'), $this->allTasks[$taskID]['condition'], true);
        $inputs .= wf_TextInput('editaction', __('Actions'), $this->allTasks[$taskID]['action'], true);
        $inputs .= wf_CheckInput('editactive', __('Active'), true, $this->allTasks[$taskID]['active']);
        $inputs .= wf_Submit(__('Save'));

        $form = wf_Form("", 'POST', $inputs, 'glamour');
        $form .= wf_BackLink("?module=watchdog");
        return ($form);
    }

    /**
     * saves changes in the watchdog task as selected in editTaskForm
     * 
     * @return void
     */
    public function changeTask() {
        $taskID = vf($_GET['edit'], 3);
        if (wf_CheckPost(array('editname', 'editaction', 'editparam'))) {
            if (!empty($taskID)) {
                if (isset($_POST['editactive'])) {
                    $actFlag = 1;
                } else {
                    $actFlag = 0;
                }
                simple_update_field('watchdog', 'name', $_POST['editname'], "WHERE `id`='" . $taskID . "'");
                simple_update_field('watchdog', 'checktype', $_POST['editchecktype'], "WHERE `id`='" . $taskID . "'");
                simple_update_field('watchdog', 'param', $_POST['editparam'], "WHERE `id`='" . $taskID . "'");
                simple_update_field('watchdog', 'operator', $_POST['editoperator'], "WHERE `id`='" . $taskID . "'");
                simple_update_field('watchdog', 'condition', $_POST['editcondition'], "WHERE `id`='" . $taskID . "'");
                simple_update_field('watchdog', 'action', $_POST['editaction'], "WHERE `id`='" . $taskID . "'");
                simple_update_field('watchdog', 'active', $actFlag, "WHERE `id`='" . $taskID . "'");

                log_register("WATCHDOG CHANGE TASK [" . $taskID . "] `" . $_POST['editname'] . "`");
            } else {
                throw new Exception(self::TASKID_EX);
            }
        } else {
            throw new Exception(self::TASKADD_EX);
        }
    }

    /**
     * delete some existing watchdog task
     * 
     * @param int $taskID - existing task id
     * 
     * @return void
     */
    public function deleteTask($taskID) {
        $taskID = vf($taskID, 3);
        if (empty($taskID)) {
            throw new Exception(self::TASKID_EX);
        }
        $query = "DELETE from `watchdog` WHERE `id`='" . $taskID . "'";
        nr_query($query);
        log_register("WATCHDOG DELETE TASK [" . $taskID . "]");
    }

    /**
     * creates new watchdog task
     * 
     * @param string $name - task name
     * @param string $checktype - task check type
     * @param string $param - parameter
     * @param string $operator - operator
     * @param string $condition - condition for action
     * @param string $action - actions list
     * @param int $active - activity tinyint flag
     * 
     * @return void
     */
    public function createTask($name, $checktype, $param, $operator, $condition, $action, $active = 0) {
        $active = mysql_real_escape_string($active);
        $name = mysql_real_escape_string($name);
        $checktype = mysql_real_escape_string($checktype);
        $param = mysql_real_escape_string($param);
        $operator = mysql_real_escape_string($operator);
        $condition = mysql_real_escape_string($condition);
        $action = mysql_real_escape_string($action);

        if ((empty($name)) OR ( empty($param)) OR ( empty($action))) {
            throw new Exception(self::TASKADD_EX);
        }

        $query = "INSERT INTO `watchdog` (`id` , `active` , `name` , `checktype` , `param` ,`operator` ,  `condition` ,`action` ,`oldresult`)
                VALUES (NULL , '" . $active . "', '" . $name . "', '" . $checktype . "', '" . $param . "', '" . $operator . "', '" . $condition . "', '" . $action . "', NULL);";
        nr_query($query);
        log_register("WATCHDOG CREATE TASK `" . $name . "`");
    }

    /**
     * Shows watchdog control panel
     * 
     * @return string
     */
    public function panel() {
        $createWindow = $this->newTaskForm();
        $settingsWindow = $this->settingsForm();
        $result = '';

        $result .= wf_modalAuto(wf_img('skins/add_icon.png') . ' ' . __('Create new task'), __('Create new task'), $createWindow, 'ubButton');
        $result .= wf_Link("?module=watchdog", wf_img('skins/icon_search_small.gif') . ' ' . __('Show all tasks'), false, 'ubButton');
        $result .= wf_Link("?module=watchdog&manual=true", wf_img('skins/refresh.gif') . ' ' . __('Manual run'), false, 'ubButton');
        $result .= wf_Link("?module=watchdog&previousalerts=true", wf_img('skins/time_machine.png') . ' ' . __('Previous alerts'), false, 'ubButton');
        $result .= wf_modalAuto(wf_img('skins/settings.png') . ' ' . __('Settings'), __('Settings'), $settingsWindow, 'ubButton');


        return ($result);
    }

    /**
     * Sets maincente mode state
     * 
     * @param string $action
     * 
     * @return void
     */
    public function setMaintenance($action) {
        if ($action == 'enable') {
            zb_StorageSet('WATCHDOG_MAINTENANCE', 'enabled');
            log_register('WATCHDOG MAINTENANCE ENABLED');
        }

        if ($action == 'disable') {
            zb_StorageDelete('WATCHDOG_MAINTENANCE');
            log_register('WATCHDOG MAINTENANCE DISABLED');
        }

        //update notification area
        $darkVoid = new DarkVoid();
        $darkVoid->flushCache();
    }

    /**
     * returns watchdog settings edit form
     * 
     * @return string
     */
    public function settingsForm() {
        $result = '';
        $inputs = wf_TextInput('changealert', __('Watchdog alert text'), $this->settings['WATCHDOG_ALERT'], true, '30');
        $inputs .= wf_TextInput('changephones', __('Phone numbers to send alerts'), $this->settings['WATCHDOG_PHONES'], true, '30');
        $inputs .= wf_TextInput('changeemails', __('Emails to send alerts'), $this->settings['WATCHDOG_EMAILS'], true, '30');
        $inputs .= wf_TextInput('changetelegram', __('Telegram chat ids to send alerts'), $this->settings['WATCHDOG_TELEGRAM'], true, '30');
        $inputs .= wf_Submit(__('Save'));
        $result .= wf_Form("", 'POST', $inputs, 'glamour');

        if (cfr('ROOT')) {
            $result .= wf_tag('br');
            if (!$this->settings['WATCHDOG_MAINTENANCE']) {
                $result .= wf_Link('?module=watchdog&maintenance=enable', wf_img('skins/icon_ok.gif') . ' ' . __('Watchdog') . ': ' . __('Enabled'), false, 'ubButton');
            } else {
                $result .= wf_Link('?module=watchdog&maintenance=disable', wf_img('skins/icon_minus.png') . ' ' . __('Watchdog') . ': ' . __('Disabled'), false, 'ubButton');
            }
        }
        return ($result);
    }

    /**
     * save the current settings of watchdog as it posted in settingsForm
     * 
     * @return void
     */
    public function saveSettings() {
        if (wf_CheckPost(array('changealert'))) {
            zb_StorageSet('WATCHDOG_ALERT', $_POST['changealert']);
            zb_StorageSet('WATCHDOG_PHONES', $_POST['changephones']);
            zb_StorageSet('WATCHDOG_EMAILS', $_POST['changeemails']);
            zb_StorageSet('WATCHDOG_TELEGRAM', $_POST['changetelegram']);
            log_register("WATCHDOG SETTINGS CHANGED");
        }
    }

    /**
     * returns year selector to load alerts
     * 
     * @return string
     */
    public function yearSelectorAlerts() {
        $inputs = wf_YearSelector('alertsyearsel', __('Year') . ' ', false);
        $inputs .= wf_Submit(__('Show'));
        $result = wf_Form("", 'POST', $inputs, 'glamour');
        $result .= wf_tag('br');
        return ($result);
    }

    /**
     * preprocess and return full calendar data for alerts report
     * 
     * @retun string
     */
    public function renderAlertsCalendar() {
        $result = '';
        $controls = wf_TableCell($this->yearSelectorAlerts());
        $controls .= wf_TableCell($this->alertsSearchForm());
        $controls = wf_TableRow($controls);
        $result = wf_TableBody($controls, '60%', 0, '');

        if (!empty($this->previousAlerts)) {
            $calendarData = '';
            foreach ($this->previousAlerts as $io => $each) {
                $timestamp = strtotime($each['date']);
                $date = date("Y, n-1, j", $timestamp);
                $rawTime = date("H:i:s", $timestamp);
                $calendarData .= "
                      {
                        title: '" . $rawTime . ' ' . $each['event'] . "',
                        start: new Date(" . $date . "),
                        end: new Date(" . $date . "),
                        className : 'undone'
		      },
                    ";
            }
            $result .= wf_FullCalendar($calendarData);
        } else {
            $result .= __('Nothing found');
        }


        return ($result);
    }

    /**
     * Returns previous alerts search form
     * 
     * @return string
     */
    public function alertsSearchForm() {
        $result = '';
        $availTaskNames = array();
        if (!empty($this->allTasks)) {
            foreach ($this->allTasks as $io => $each) {
                $availTaskNames[$each['name']] = $each['name'];
            }
        }

        $inputs = wf_Selector('previousalertsearch', $availTaskNames, __('Name'), '', false);
        $inputs .= wf_Submit(__('Search'));
        $result = wf_Form("", 'POST', $inputs, 'glamour');

        return ($result);
    }

    /**
     * Returns previousa alerts search results
     * 
     * @param string $request
     * @return string
     */
    public function alertSearchResults($request) {
        $result = $this->alertsSearchForm();
        $cells = wf_TableCell(__('Date'));
        $cells .= wf_TableCell(__('Event'));
        $rows = wf_TableRow($cells, 'row1');
        $counter = 0;
        if (!empty($this->previousAlerts)) {
            foreach ($this->previousAlerts as $io => $each) {
                if (ispos($each['event'], $request)) {
                    $cells = wf_TableCell($each['date']);
                    $cells .= wf_TableCell($each['event']);
                    $rows .= wf_TableRow($cells, 'row3');
                    $counter++;
                }
            }
        }
        $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        $result .= __('Total') . ': ' . $counter;

        return ($result);
    }

}

?>