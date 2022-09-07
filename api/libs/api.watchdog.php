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

    /**
     * Contains stardust process manager instance
     *
     * @var object
     */
    protected $processMgr = '';

    const PARAM_EX = 'NO_REQUIRED_TASK_PARAM_';
    const PARAMFMT_EX = 'WRONG_FORMAT_TASK_PARAM_';
    const SETTINGS_EX = 'NO_SETTINGS_LOADED';
    const PID_NAME = 'WATCHDOG';

    public function __construct() {
        //get all current watchdog tasks
        $this->loadTasks();

        //get all previous polling results
        $this->loadOldResults();

        //load watchdog settings from database
        $this->loadSettings();

        //inits process manager
        $this->initStarDust();

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
     * Inits process manager
     * 
     * @return void
     */
    protected function initStarDust() {
        $this->processMgr = new StarDust(self::PID_NAME);
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
                //do the TCP port check
                case 'tcpping':
                    if (!empty($this->taskData[$taskID]['param'])) {
                        if (ispos($this->taskData[$taskID]['param'], ':')) {
                            $tcpPingData = explode(':', $this->taskData[$taskID]['param']);
                            $tcpPingHost = $tcpPingData[0];
                            $tcpPingPort = $tcpPingData[1];
                            $tcpPingTimeout = 2;
                            $transport = 'tcp://';
                            @$connection = fsockopen($transport . $tcpPingHost, $tcpPingPort, $tcperrno, $tcperrstr, $tcpPingTimeout);
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
                //do the UDP port check
                case 'udpping':
                    if (!empty($this->taskData[$taskID]['param'])) {
                        if (ispos($this->taskData[$taskID]['param'], ':')) {
                            $udpPingData = explode(':', $this->taskData[$taskID]['param']);
                            $udpPingHost = $udpPingData[0];
                            $udpPingPort = $udpPingData[1];
                            //not using fsockopen/sockets here because they always returns non failed results on udp datagrams
                            global $ubillingConfig;
                            $altCfg = $ubillingConfig->getAlter();
                            $billCfg = $ubillingConfig->getBilling();
                            $cmd = $billCfg['SUDO'] . ' ' . $altCfg['NMAP_PATH'] . ' -p' . $udpPingPort . ' -sU ' . $udpPingHost . ' | ' . $billCfg['GREP'] . ' ' . $udpPingPort;
                            $rawResult = shell_exec($cmd);


                            if (ispos($rawResult, 'open')) {
                                $result = true;
                            } else {
                                $result = false;
                            }


                            $storeValue = ($result) ? 'true' : 'false';
                            $this->setOldValue($taskID, $storeValue);
                            $this->setCurValue($taskID, $storeValue);
                        } else {
                            throw new Exception(self::PARAMFMT_EX . "UDPPING");
                        }
                    } else {
                        throw new Exception(self::PARAM_EX . "UDPPING");
                    }
                    break;
                //perform some snmpwalk query
                case 'snmpwalk':
                    if (!empty($this->taskData[$taskID]['param'])) {
                        if (ispos($this->taskData[$taskID]['param'], ':')) {
                            $snmpData = explode(':', $this->taskData[$taskID]['param']);
                            $snmpHost = $snmpData[0];
                            $snmpCommunity = $snmpData[1];
                            $snmpOid = $snmpData[2];
                            $snmpHandle = new SNMPHelper();
                            $snmpHandle->setBackground(false);
                            $result = $snmpHandle->walk($snmpHost, $snmpCommunity, $snmpOid);
                            $result = trim($result);
                            $result = zb_SanitizeSNMPValue($result);
                            $storeValue = $result;
                            $this->setOldValue($taskID, $storeValue);
                            $this->setCurValue($taskID, $storeValue);
                        } else {
                            throw new Exception(self::PARAMFMT_EX . 'SNMPWALK');
                        }
                    } else {
                        throw new Exception(self::PARAM_EX . 'SNMPWALK');
                    }
                    break;
                case 'freediskspace':
                    if (!empty($this->taskData[$taskID]['param'])) {
                        if (ispos($this->taskData[$taskID]['param'], '/')) {
                            $rawSpace = disk_free_space($this->taskData[$taskID]['param']);
                            $result = $rawSpace / 1073741824; //in Gb
                            $result = round($result, 2);
                            $this->setOldValue($taskID, $result);
                            $this->setCurValue($taskID, $result);
                        } else {
                            throw new Exception(self::PARAMFMT_EX . 'FREEDISKSPACE');
                        }
                    } else {
                        throw new Exception(self::PARAM_EX . 'FREEDISKSPACE');
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
                //rised against previous results    
                case 'rised':
                    $oldValue = $this->oldResults[$taskID];
                    $currentValue = $this->doAction($taskID);
                    $changeLevel = (!empty($this->taskData[$taskID]['condition'])) ? $this->taskData[$taskID]['condition'] : 0;
                    if ($currentValue > ($oldValue + $changeLevel)) {
                        return (true);
                    } else {
                        return (false);
                    }
                    break;

                //decreased against previous results    
                case 'decreased':
                    $oldValue = $this->oldResults[$taskID];
                    $currentValue = $this->doAction($taskID);
                    $changeLevel = (!empty($this->taskData[$taskID]['condition'])) ? $this->taskData[$taskID]['condition'] : 0;
                    if ($currentValue < ($oldValue - $changeLevel)) {
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
            if ($this->processMgr->notRunning()) {
                $this->processMgr->start();
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
                $this->processMgr->stop();
            } else {
                log_register('WATCHDOG ALREADY RUNNING TASKS SKIPPED');
            }
        } else {
            log_register('WATCHDOG MAINTENANCE TASKS SKIPPED');
        }
    }

}

?>