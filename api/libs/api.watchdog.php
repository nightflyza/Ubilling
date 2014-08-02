<?php

/*
 * 
 * System watchdog base class
 * 
 */

class WatchDog {

    private $taskData = array();
    private $oldResults = array();
    private $curResults= array();
    private $settings = array();

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
    }

    /*
     * Returns current watchdog settings
     * 
     * @return array
     */

    public function getSettings() {
        $result = $this->settings;
        return ($result);
    }

    /*
     * Loads all previous tasks execution results into private property oldResults
     * 
     * @return void
     */

    private function loadOldResults() {
        if (!empty($this->taskData)) {
            foreach ($this->taskData as $iy => $eachPrevResult) {
                $this->oldResults[$eachPrevResult['id']] = $eachPrevResult['oldresult'];
            }
        }
    }

    /*
     * Loads an active task list from database and pushes it into private property taskData
     * 
     * @return void
     */

    private function loadTasks() {
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

    /*
     * Gets watchdog settings from database and load it into settings property
     * 
     * @return void
     */

    private function loadSettings() {
        $alert = zb_StorageGet('WATCHDOG_ALERT');
        $phones = zb_StorageGet('WATCHDOG_PHONES');
        $emails = zb_StorageGet('WATCHDOG_EMAILS');
        $smsgateway = zb_StorageGet('WATCHDOG_TSMS_GATEWAY');
        $smslogin = zb_StorageGet('WATCHDOG_TSMS_LOGIN');
        $smspassword = zb_StorageGet('WATCHDOG_TSMS_PASSWORD');
        $smssign=zb_StorageGet('WATCHDOG_TSMS_SIGN');
        
        $this->settings['WATCHDOG_ALERT'] = $alert;
        $this->settings['WATCHDOG_PHONES'] = $phones;
        $this->settings['WATCHDOG_EMAILS'] = $emails;
        $this->settings['WATCHDOG_TSMS_GATEWAY'] = $smsgateway;
        $this->settings['WATCHDOG_TSMS_LOGIN'] = $smslogin;
        $this->settings['WATCHDOG_TSMS_PASSWORD'] = $smspassword;
        $this->settings['WATCHDOG_TSMS_SIGN'] = $smssign;

        if (empty($this->settings['WATCHDOG_ALERT'])) {
            throw new Exception (self::SETTINGS_EX);
        }
    }
    
    /*
     * stores sms for deffered sending via TurboSMS
     * 
     * @param number - number to send sms in internaitional format
     * @message - text message in utf8 encoding
     * 
     * @return array
     */
    public function sendSMS($number,$message) {
        $number=trim($number);
        $filename='content/tsms/wd_'.zb_rand_string(8);
        $storedata='NUMBER="'.$this->safeEscapeString($number).'"'."\n";
        $storedata.='MESSAGE="'.$this->safeEscapeString($message).'"'."\n";
        file_put_contents($filename, $storedata);
        log_register("WATCHDOG SEND SMS `".$number."`");
    }
    
    /*
     *  sends all sms from local storage
     *  
     * @return void
     */
    public function backgroundSMSProcessing() {
        $smsPath='content/tsms/';
        $tsms_host = $this->settings['WATCHDOG_TSMS_GATEWAY'];
        $tsms_db = 'users';
        $tsms_login = $this->settings['WATCHDOG_TSMS_LOGIN'];
        $tsms_password = $this->settings['WATCHDOG_TSMS_PASSWORD'];
        $tsms_table = $this->settings['WATCHDOG_TSMS_LOGIN'];
        $sign=     $this->safeEscapeString($this->settings['WATCHDOG_TSMS_SIGN']);
        $result = array();
        //time shift settings
        $timezone='2';
        $tz_offset=(2-$timezone)*3600;
        $date=date("Y-m-d H:i:s",time()+$tz_offset);
        
        $allSmsStore=  rcms_scandir($smsPath);
        if (!empty($allSmsStore)) {
        //open new database connection
        $TsmsDB = new DbConnect($tsms_host, $tsms_login, $tsms_password, $tsms_db, $error_reporting = true, $persistent = false);
        $TsmsDB->open() or die($TsmsDB->error());
        $TsmsDB->query('SET NAMES utf8;');
            foreach ($allSmsStore as $eachfile) {
                $fileData= rcms_parse_ini_file($smsPath.$eachfile);
                if ((isset($fileData['NUMBER'])) AND (isset($fileData['MESSAGE']))) {
                $query="
                INSERT INTO `".$tsms_table."`
                    ( `number`, `sign`, `message`, `wappush`,  `send_time`) 
                    VALUES
                    ('".$fileData['NUMBER']."', '".$sign."', '".$fileData['MESSAGE']."', '', '".$date."');
                ";
              
                //push new sms to database
                $TsmsDB->query($query);
 
                while ($row = $TsmsDB->fetchassoc()) {
                $result[] = $row;
                 }
                }
                //remove old send task
                unlink($smsPath.$eachfile);
            }
            //close old datalink
            $TsmsDB->close();
        }
        return ($result);
    }
    
    /*
     * sends email notification
     * 
     * @param $email - target email
     * @param $message - message
     * 
     * @return void
     */
    private function sendEmail($email,$message) {
        $sender=__('Watchdog');
        $subj='Ubilling '.__('Watchdog');
        $message.=' '.date("Y-m-d H:i:s");
        $headers = 'From: =?UTF-8?B?' . base64_encode($sender) . '?= <' . $email . ">\n";
        $headers .= "MIME-Version: 1.0\n";
        $headers .= 'Message-ID: <' . md5(uniqid(time())) . "@" . $sender . ">\n";
        $headers .= 'Date: ' . gmdate('D, d M Y H:i:s T', time()) . "\n";
        $headers .= "Content-type: text/plain; charset=UTF-8\n";
        $headers .= "Content-transfer-encoding: 8bit\n";
        $headers .= "X-Mailer: Ubilling\n";
        $headers .= "X-MimeOLE: Ubilling\n";
        mail($email, '=?UTF-8?B?' . base64_encode($subj). '?=', $message, $headers);
        log_register("WATCHDOG SEND EMAIL `".$email."`");
    }
    
    /*
     * ugly hack to dirty input data filtering in php 5.4 with multiple DB links
     * 
     * @param $string - string to filter
     * 
     * @return string
     */
     private function safeEscapeString($string) {
            @$result=preg_replace("#[~@\?\%\/\;=\*\>\<\"\']#Uis",'',$string);;
            return ($result);
        }

    /*
     * Updates previous poll data in database
     * 
     * @param $taskID - watchdog task id
     * @param $value  - data to set as oldresult
     * 
     * @return void
     */

    private function setOldValue($taskID, $value) {
        simple_update_field('watchdog', 'oldresult', $value, "WHERE `id`='" . $taskID . "'");
    }
    
    /*
     * Updates current run task results
     * 
     * @param $taskID - watchdog task id
     * @param $value  - data to set as newresult
     * 
     * @return void
     */
    
     private function setCurValue($taskID, $value) {
        $this->curResults[$taskID]=$value;
    }

    /*
     * Execute some action for task
     * 
     * @param $taskID - id of existing monitoring task to run
     * 
     * @return mixed
     */

    private function doAction($taskID = NULL) {
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
            }
        } else {
            $result = 0;
        }
        return ($result);
    }

    /*
     * gets oldresult from some task id
     * 
     * @param $taskID - existing task id
     * 
     * @return string
     */

    private function getOldValue($taskID = NULL) {
        $result = $this->oldResults[$taskID];
        return ($result);
    }

    /*
     * Checks condition for selected task
     * 
     * @param $taskID - existing task id
     * 
     * @return string
     */

    private function checkCondition($taskID = NULL) {
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
                    $cond=trim($this->taskData[$taskID]['condition']);
                    $actres=trim($this->doAction($taskID));
                    if ($actres == $cond) {
                        return (true);
                    } else {
                        return (false);
                    }
                    break;
                 //boolean not equals    
                case '!=':
                    $cond=trim($this->taskData[$taskID]['condition']);
                    $actres=trim($this->doAction($taskID));
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
                //changes against previous results    
                case 'changed':
                    $oldValue = $this->oldResults[$taskID];
                    $currentValue = $this->doAction($taskID);
                    if(is_bool($currentValue)) {
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
                    if(is_bool($currentValue)) {
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

    /*
     * Main task processing subroutine
     * 
     * @return void
     */

    public function processTask() {
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
                        $notifyLogMessage='WATCHDOG NOTIFY THAT `'.$alertTaskName;
                                  //attach old result to log message if needed
                                  if (ispos($taskActions, 'oldresult')) {
                                   $notifyLogMessage.=' '.$this->taskData[$taskID]['oldresult']; 
                                  }
                                  
                                  //attach current results to log message
                                  if (ispos($taskActions, 'andresult')) {
                                   $notifyLogMessage.=' '.$this->curResults[$taskID]; 
                                  }
                        $notifyLogMessage.='`';
                        log_register($notifyLogMessage);
                    }
                    //send emails with alerts
                    if (ispos($taskActions, 'email')) {
                         if (!empty($this->settings['WATCHDOG_EMAILS'])) {
                             $allNotifyEmails=  explode(',', $this->settings['WATCHDOG_EMAILS']);
                             if (!empty($allNotifyEmails)) {
                                  $notifyMessageMail=$this->settings['WATCHDOG_ALERT'].' '.$alertTaskName;
                                  //attach old result to email if needed
                                  if (ispos($taskActions, 'oldresult')) {
                                   $notifyMessageMail.=' '.$this->taskData[$taskID]['oldresult']; 
                                  }
                                  
                                  //attach current results
                                  if (ispos($taskActions, 'andresult')) {
                                   $notifyMessageMail.=' '.$this->curResults[$taskID]; 
                                  }
                                  
                                  foreach ($allNotifyEmails as $im=>$eachmail) {
                                      $this->sendEmail($eachmail, $notifyMessageMail);
                                  }
                             }
                         }
                    }
                    //run some script with path like [path]
                    if (ispos($taskActions, 'script')) {
                        if (preg_match('!\[(.*?)\]!si', $taskActions, $tmpArr)) { 
                            $runScriptPath = $tmpArr[1];
                        } else {
                            $runScriptPath='';
                        }
                        if (!empty($runScriptPath)) {
                            shell_exec($runScriptPath);
                            log_register("WATCHDOG RUN SCRIPT `".$runScriptPath."`");
                        }
                    }
                    
                    //send sms via turboSMS
                    if (ispos($taskActions, 'sms')) {
                        if (!empty($this->settings['WATCHDOG_PHONES'])) {
                            $allNotifyPhones=explode(',', $this->settings['WATCHDOG_PHONES']);
                            $additionalPhones=array();
                            if (preg_match('!\{(.*?)\}!si', $taskActions, $tmpAddPhones)) {
                                $additionalPhones=  explode(',', $tmpAddPhones[1]);
                               if (!empty($additionalPhones)) {
                                   if (!ispos($taskActions, 'noprimary')) {
                                   foreach ($additionalPhones as $ig=>$eachAdditionalPhone) {
                                       if (!empty($eachAdditionalPhone)) {
                                       $allNotifyPhones[]=$eachAdditionalPhone;
                                       }
                                   }
                                   } else {
                                       $allNotifyPhones=$additionalPhones;
                                   }
                                   
                               }
                            }
                            
                            
                            if (!empty($allNotifyPhones)) {
                                $notifyMessage=$this->settings['WATCHDOG_ALERT'].' '.$alertTaskName;
                                //attach old result to sms if needed
                                if (ispos($taskActions, 'oldresult')) {
                                    $notifyMessage.=' '.$this->taskData[$taskID]['oldresult']; 
                                }
                                
                                //attach current result to sms if needed
                                if (ispos($taskActions, 'andresult')) {
                                    $notifyMessage.=' '.$this->curResults[$taskID]; 
                                }
             
                                foreach ($allNotifyPhones as $iu=>$eachmobile) {
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
        
        $this->backgroundSMSProcessing();
    }

}

/*
 * 
 * Watchdog view controls class
 * 
 */

class WatchDogInterface {

    private $allTasks = array();
    private $settings = array();
    private $previousAlerts=array();
    
    const TASKID_EX  = 'NO_REQUIRED_TASK_ID';
    const TASKADD_EX = 'MISSING_REQUIRED_OPTION';
    
    
    /*
     * load all watchdog tasks intoo private prop allTasks
     * 
     * @return void
     */

    public function loadAllTasks() {
        $taskQuery = "SELECT * from `watchdog`;";
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
    
     /*
     * load all watchdog previous alerts into private data prop
     * 
     * @return void
     */

    public function loadAllPreviousAlerts() {
        //select year to load
        if (wf_CheckPost(array('alertsyearsel'))) {
            $curYear=vf($_POST['alertsyearsel'],3);
        } else {
            $curYear=curyear();
        }
        
        $query = "SELECT `id`,`date`,`event` from `weblogs` WHERE `event` LIKE 'WATCHDOG NOTIFY THAT%' AND `date` LIKE '".$curYear."-%';";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io=>$each) {
                $this->previousAlerts[$each['id']]['id']=$each['id'];
                $this->previousAlerts[$each['id']]['date']=$each['date'];
                $event=  str_replace('WATCHDOG NOTIFY THAT', '', $each['event']);
                $event=  str_replace('`', '', $event);
                $this->previousAlerts[$each['id']]['event']=$event;
            }
        }
    }

    /*
     * private property allTasks getter
     * 
     * @return array
     */

    public function getAllTasks() {
        $result = $this->allTasks;
        return ($result);
    }

    /*
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
        $smsgateway = zb_StorageGet('WATCHDOG_TSMS_GATEWAY');
        if (empty($smsgateway)) {
            $altcfg = rcms_parse_ini_file(CONFIG_PATH . 'alter.ini');
            $smsgateway = $altcfg['TSMS_GATEWAY'];
            zb_StorageSet('WATCHDOG_TSMS_GATEWAY', $smsgateway);
        }
        $smslogin = zb_StorageGet('WATCHDOG_TSMS_LOGIN');
        if (empty($smslogin)) {
            $smslogin = $altcfg['TSMS_LOGIN'];
            zb_StorageSet('WATCHDOG_TSMS_LOGIN', $smslogin);
        }
        $smspassword = zb_StorageGet('WATCHDOG_TSMS_PASSWORD');
        if (empty($smspassword)) {
            $smspassword = $altcfg['TSMS_PASSWORD'];
            zb_StorageSet('WATCHDOG_TSMS_PASSWORD', $smspassword);
        }
        $smssign= zb_StorageGet('WATCHDOG_TSMS_SIGN');
        if (empty($smssign)) {
            $smssign='Ubilling';
            zb_StorageSet('WATCHDOG_TSMS_SIGN', $smssign);
        }

        $this->settings['WATCHDOG_ALERT'] = $alert;
        $this->settings['WATCHDOG_PHONES'] = $phones;
        $this->settings['WATCHDOG_EMAILS'] = $emails;
        $this->settings['WATCHDOG_TSMS_GATEWAY'] = $smsgateway;
        $this->settings['WATCHDOG_TSMS_LOGIN'] = $smslogin;
        $this->settings['WATCHDOG_TSMS_PASSWORD'] = $smspassword;
        $this->settings['WATCHDOG_TSMS_SIGN'] = $smssign;
    }

    /*
     * Returns current watchdog settings
     * 
     * @return array
     */

    public function getSettings() {
        $result = $this->settings;
        return ($result);
    }

    /*
     * shows all available tasks list
     * 
     * @return string
     */

    public function listAllTasks() {
        $cells=  wf_TableCell(__('ID'));
        $cells.= wf_TableCell(__('Active'));
        $cells.= wf_TableCell(__('Name'));
        $cells.= wf_TableCell(__('Check type'));
        $cells.= wf_TableCell(__('Parameter'));
        $cells.= wf_TableCell(__('Operator'));
        $cells.= wf_TableCell(__('Condition'));
        $cells.= wf_TableCell(__('Actions'));
        $cells.= wf_TableCell(__('Manage'));
        $rows= wf_TableRow($cells, 'row1');
        $lighter='onmouseover="this.className = \'row2\';" onmouseout="this.className = \'row3\';" ';
        
        if (!empty($this->allTasks)) {
            foreach ($this->allTasks as $io=>$eachtask) {
                $details=wf_tag('pre').print_r($eachtask,true).wf_tag('pre',true);
                $detailLink=  wf_modal($eachtask['id'], $eachtask['name'], $details, '', '600', '400');
                $cells=  wf_TableCell($detailLink);
                $cells.= wf_TableCell(web_bool_led($eachtask['active']));
                $cells.= wf_TableCell($eachtask['name']);
                $cells.= wf_TableCell($eachtask['checktype']);
                $cells.= wf_TableCell($eachtask['param']);
                $cells.= wf_TableCell($eachtask['operator']);
                $cells.= wf_TableCell($eachtask['condition']);
                $cells.= wf_TableCell($eachtask['action']);
               
                $controls=   wf_JSAlert('?module=watchdog&delete='.$eachtask['id'], web_delete_icon(),__('Removing this may lead to irreparable results'));
                $controls.=  wf_JSAlert('?module=watchdog&edit='.$eachtask['id'], web_edit_icon(),__('Are you serious'));
                
                $cells.= wf_TableCell($controls);
                $rows.=wf_tag('tr', false, 'row3', $lighter);
                $rows.=$cells;
                $rows.=wf_tag('tr', true);
            }
        }
        
        $result=  wf_TableBody($rows, '100%', '0', 'sortable');
        return ($result);
    }
    
    
    /*
     * shows new task creation form
     * 
     * @return string
     */
    
    public function newTaskForm() {
        
        $checktypes=array(
            'icmpping'=>'icmpping',
            'tcpping'=>'tcpping',
            'script'=>'script',
            'getusertraff'=>'getusertraff',
            'fileexists'=>'fileexists'
        );
        
        $operators=array(
            '=true'=>'=true',
            '=false'=>'=false',
            '=='=>'==',
            '!='=>'!=',
            '>'=>'>',
            '<'=>'<',
            'empty'=>'empty',
            'changed'=>'changed',
            'notchanged'=>'notchanged',
            'like'=>'like',
            'notlike'=>'notlike',
        );
        
        $inputs=  wf_TextInput('newname', __('Name'), '', true);
        $inputs.= wf_Selector('newchecktype', $checktypes, __('Check type'), '', true);
        $inputs.= wf_TextInput('newparam', __('Parameter'), '',true);
        $inputs.= wf_Selector('newoperator', $operators, __('Operator'), '', true);
        $inputs.= wf_TextInput('newcondition', __('Condition'), '', true);
        $inputs.= wf_TextInput('newaction', __('Actions'), '', true);
        $inputs.=wf_CheckInput('newactive', __('Active'), true, true);
        $inputs.= wf_Submit(__('Create'));
        
        $form=  wf_Form("", 'POST', $inputs, 'glamour');
        return ($form);
        
    }
    
    /*
     * shows modify form for some existing task
     *
     * @param $taskID existing task id
     * 
     * @return string
     */
      public function editTaskForm($taskID) {
          
        $taskID=vf($taskID,3);
        if (empty($taskID)) {
            throw new Exception (self::TASKID_EX);
        }
        
        $checktypes=array(
            'icmpping'=>'icmpping',
            'tcpping'=>'tcpping',
            'script'=>'script',
            'getusertraff'=>'getusertraff',
            'fileexists'=>'fileexists'
        );
        
        $operators=array(
            '=true'=>'=true',
            '=false'=>'=false',
            '=='=>'==',
            '!='=>'!=',
            '>'=>'>',
            '<'=>'<',
            'empty'=>'empty',
            'changed'=>'changed',
            'notchanged'=>'notchanged',
            'like'=>'like',
            'notlike'=>'notlike'
        );
        
        $inputs=  wf_TextInput('editname', __('Name'), $this->allTasks[$taskID]['name'], true);
        $inputs.= wf_Selector('editchecktype', $checktypes, __('Check type'), $this->allTasks[$taskID]['checktype'], true);
        $inputs.= wf_TextInput('editparam', __('Parameter'), $this->allTasks[$taskID]['param'],true);
        $inputs.= wf_Selector('editoperator', $operators, __('Operator'), $this->allTasks[$taskID]['operator'], true);
        $inputs.= wf_TextInput('editcondition', __('Condition'), $this->allTasks[$taskID]['condition'], true);
        $inputs.= wf_TextInput('editaction', __('Actions'), $this->allTasks[$taskID]['action'], true);
        $inputs.= wf_CheckInput('editactive', __('Active'), true, $this->allTasks[$taskID]['active']);
        $inputs.= wf_Submit(__('Save'));
        
        $form=  wf_Form("", 'POST', $inputs, 'glamour');
        $form.= wf_Link("?module=watchdog", __('Back'), true, 'ubButton');
        return ($form);
        
    }
    /*
     * saves changes in the watchdog task as selected in editTaskForm
     * 
     * @return void
     */
    public function changeTask() {
        $taskID=vf($_GET['edit'],3);
        if (wf_CheckPost(array('editname','editaction','editparam'))) {
         if (!empty($taskID)) {
             if (isset($_POST['editactive'])) {
                 $actFlag=1;
             } else {
                 $actFlag=0;
             }
             simple_update_field('watchdog', 'name', $_POST['editname'], "WHERE `id`='".$taskID."'");
             simple_update_field('watchdog', 'checktype', $_POST['editchecktype'], "WHERE `id`='".$taskID."'");
             simple_update_field('watchdog', 'param', $_POST['editparam'], "WHERE `id`='".$taskID."'");
             simple_update_field('watchdog', 'operator', $_POST['editoperator'], "WHERE `id`='".$taskID."'");
             simple_update_field('watchdog', 'condition', $_POST['editcondition'], "WHERE `id`='".$taskID."'");
             simple_update_field('watchdog', 'action', $_POST['editaction'], "WHERE `id`='".$taskID."'");
             simple_update_field('watchdog', 'active', $actFlag, "WHERE `id`='".$taskID."'");
             
             log_register("WATCHDOG CHANGE TASK [".$taskID."] `".$_POST['editname']."`");
             
         } else {
             throw new Exception(self::TASKID_EX);
         }
        } else {
            throw new Exception(self::TASKADD_EX);
        }
        
    }
    
    /* 
     * delete some existing watchdog task
     * 
     * @param $taskID - existing task id
     * 
     * @return void
     */
    public function deleteTask($taskID) {
        $taskID=vf($taskID,3);
        if (empty($taskID)) {
            throw new Exception(self::TASKID_EX);
        }
        $query="DELETE from `watchdog` WHERE `id`='".$taskID."'";
        nr_query($query);
        log_register("WATCHDOG DELETE TASK [".$taskID."]");
    }
    
    /*
     * creates new watchdog task
     * 
     * @param $name - task name
     * @param $checktype - task check type
     * @param $param - parameter
     * @param $operator - operator
     * @param $condition - condition for action
     * @param $action - actions list
     * @param $active - activity tinyint flag
     * 
     * @return void
     */
    public function createTask($name,$checktype,$param,$operator,$condition,$action,$active=0) {
        $active=  mysql_real_escape_string($active);
        $name=  mysql_real_escape_string($name);
        $checktype=  mysql_real_escape_string($checktype);
        $param=  mysql_real_escape_string($param);
        $operator=  mysql_real_escape_string($operator);
        $condition=  mysql_real_escape_string($condition);
        $action=  mysql_real_escape_string($action);
        
        if ((empty($name)) OR  (empty($param)) OR (empty($action))) {
            throw new Exception(self::TASKADD_EX);
        }
        
        
        $query="INSERT INTO `watchdog` (
                `id` ,
                `active` ,
                `name` ,
                `checktype` ,
                `param` ,
                `operator` ,
                `condition` ,
                `action` ,
                `oldresult`
                )
                VALUES (
                NULL , '".$active."', '".$name."', '".$checktype."', '".$param."', '".$operator."', '".$condition."', '".$action."', NULL
                );";
        nr_query($query);
        log_register("WATCHDOG CREATE TASK `".$name."`");
    }
    
    /*
     * Shows watchdog control panel
     * 
     * @return string
     */
    public function panel() {
        $createWindow=$this->newTaskForm();
        $settingsWindow=$this->settingsForm();
        $result=  wf_modal(__('Create new task'), __('Create new task'), $createWindow, 'ubButton', '400', '300');
        $result.= wf_Link("?module=watchdog", __('Show all tasks'), false, 'ubButton');
        $result.= wf_Link("?module=watchdog&manual=true", __('Manual run'), false, 'ubButton');
        $result.= wf_Link("?module=watchdog&showsmsqueue=true", __('View SMS sending queue'), false, 'ubButton');
        $result.= wf_Link("?module=watchdog&previousalerts=true", __('Previous alerts'), false, 'ubButton');
        $result.= wf_modal(__('Settings'), __('Settings'), $settingsWindow, 'ubButton', '750', '350');
        
        return ($result);
    }
    
    /*
     * returns watchdog settings edit form
     * 
     * @return string
     */
    public function settingsForm() {

        $inputs= wf_TextInput('changealert', __('Watchdog alert text'), $this->settings['WATCHDOG_ALERT'], true,'30');
        $inputs.= wf_TextInput('changephones', __('Phone numbers to send alerts'), $this->settings['WATCHDOG_PHONES'], true,'30');
        $inputs.= wf_TextInput('changeemails', __('Emails to send alerts'), $this->settings['WATCHDOG_EMAILS'], true,'30');
        $inputs.= wf_TextInput('changetsmsgateway', __('TurboSMS gateway address'), $this->settings['WATCHDOG_TSMS_GATEWAY'], true);
        $inputs.= wf_TextInput('changetsmslogin', __('User login to access TurboSMS gateway'), $this->settings['WATCHDOG_TSMS_LOGIN'], true);
        $inputs.= wf_TextInput('changetsmspassword', __('User password for access TurboSMS gateway'), $this->settings['WATCHDOG_TSMS_PASSWORD'], true);
        $inputs.= wf_TextInput('changetsmssign', __('TurboSMS').' '. __('Sign'), $this->settings['WATCHDOG_TSMS_SIGN'], true);
        $inputs.= wf_Submit(__('Save'));
        $form=  wf_Form("", 'POST', $inputs, 'glamour');
        return ($form);
        
    }
    
    /*
     * save the current settings of watchdog as it posted in settingsForm
     * 
     * @return void
     */
    public function saveSettings() {
        if (wf_CheckPost(array('changealert'))) {
            
            zb_StorageSet('WATCHDOG_ALERT',$_POST['changealert']);
            zb_StorageSet('WATCHDOG_PHONES', $_POST['changephones']);
            zb_StorageSet('WATCHDOG_EMAILS', $_POST['changeemails']);
            zb_StorageSet('WATCHDOG_TSMS_GATEWAY',$_POST['changetsmsgateway']);
            zb_StorageSet('WATCHDOG_TSMS_LOGIN',$_POST['changetsmslogin']);
            zb_StorageSet('WATCHDOG_TSMS_PASSWORD',$_POST['changetsmspassword']);
            zb_StorageSet('WATCHDOG_TSMS_SIGN',$_POST['changetsmssign']);
                        
            log_register("WATCHDOG SETTINGS CHANGED");
        }
    }
    
    /*
     * Shows Turbo SMS sending queue
     * 
     * @return string
     */
    public function showSMSqueue() {
        $smsPath='content/tsms/';
        $tsms_host = $this->settings['WATCHDOG_TSMS_GATEWAY'];
        $tsms_db = 'users';
        $tsms_login = $this->settings['WATCHDOG_TSMS_LOGIN'];
        $tsms_password = $this->settings['WATCHDOG_TSMS_PASSWORD'];
        $tsms_table = $this->settings['WATCHDOG_TSMS_LOGIN'];
        $smsArray=array();
        
        $TsmsDB = new DbConnect($tsms_host, $tsms_login, $tsms_password, $tsms_db, $error_reporting = true, $persistent = false);
        $TsmsDB->open() or die($TsmsDB->error());
        $TsmsDB->query('SET NAMES utf8;');
        
        if (wf_CheckPost(array('showdate'))) {
            $date=  mysql_real_escape_string($_POST['showdate']);
        } else {
            $date='';
        }
        
        if (!empty($date)) {
            $where=" WHERE `send_time` LIKE '".$date."%' ORDER BY `id` DESC;";
        } else {
            $where='  ORDER BY `id` DESC LIMIT 50;';
        }
            
        $query="SELECT * from `".$tsms_table."`".$where;
        $TsmsDB->query($query);
 
                while ($row = $TsmsDB->fetchassoc()) {
                $smsArray[] = $row;
                }
                
                
            //close old datalink
            $TsmsDB->close();
            
            //rendering result
            $inputs=  wf_DatePickerPreset('showdate', curdate());
            $inputs.= wf_Submit(__('Show'));
            $dateform=  wf_Form("", 'POST', $inputs, 'glamour');
            
            $lighter='onmouseover="this.className = \'row2\';" onmouseout="this.className = \'row3\';" ';
            
            $cells=  wf_TableCell(__('ID'));
            $cells.= wf_TableCell(__('Msg ID'));
            $cells.= wf_TableCell(__('Mobile'));
            $cells.= wf_TableCell(__('Sign'));
            $cells.= wf_TableCell(__('Message'));
            $cells.= wf_TableCell(__('WAP'));
            $cells.= wf_TableCell(__('Cost'));
            $cells.= wf_TableCell(__('Send time'));
            $cells.= wf_TableCell(__('Sended'));
            $cells.= wf_TableCell(__('Status'));
            $rows=wf_TableRow($cells, 'row1');
            
            if (!empty($smsArray)) {
                foreach ($smsArray as $io=>$each) {
                        $cells=  wf_TableCell($each['id']);
                        $cells.= wf_TableCell($each['msg_id']);
                        $cells.= wf_TableCell($each['number']);
                        $cells.= wf_TableCell($each['sign']);
                        $msg=  wf_modal(__('Show'), __('SMS'), $each['message'], '', '300', '200');
                        $cells.= wf_TableCell($msg);
                        $cells.= wf_TableCell($each['wappush']);
                        $cells.= wf_TableCell($each['cost']);
                        $cells.= wf_TableCell($each['send_time']);
                        $cells.= wf_TableCell($each['sended']);
                        $cells.= wf_TableCell($each['status']);
                        $rows.=wf_tag('tr', false, 'row3', $lighter);
                        $rows.=$cells;
                        $rows.=wf_tag('tr', true);
                }
            }
            
            $result= $dateform;
            $result.= wf_TableBody($rows, '100%', '0', 'sortable');
            return ($result);
            
    }
    
    /*
     * returns year selector to load alerts
     * 
     * @return string
     */
    public function yearSelectorAlerts(){
        $inputs=  wf_YearSelector('alertsyearsel', __('Year'), false);
        $inputs.= wf_Submit(__('Show'));
        $result=  wf_Form("", 'POST', $inputs, 'glamour');
        return ($result);
    }
    
    
    /*
     * preprocess and return full calendar data for alerts report
     * 
     * @retun string
     */
    public function renderAlertsCalendar() {
        $result=$this->yearSelectorAlerts();
        if (!empty($this->previousAlerts)) {
            $calendarData='';
            foreach ($this->previousAlerts as $io=>$each) {
                $timestamp=strtotime($each['date']);
                $date=date("Y, n-1, j",$timestamp);
                $rawTime=date("H:i:s",$timestamp);
                $calendarData.="
                      {
                        title: '".$rawTime.' '.$each['event']."',
                        start: new Date(".$date."),
                        end: new Date(".$date."),
                        className : 'undone'
		      },
                    ";
            }
            $result.=  wf_FullCalendar($calendarData);
        } else {
            $result.=__('Nothing found');
        }
        
        
        return ($result);
    } 
    
    
}

?>