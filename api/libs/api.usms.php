<?php

/**
 * SMS queue handling class
 */
class UbillingSMS {

    /**
     * Contains system alter.ini config as key=>value
     */
    protected $altCfg = array();

    /**
     * SMS_SERVICES_ADVANCED_ENABLED option state
     *
     * @var bool
     */
    protected $smsRoutingFlag = false;

    const QUEUE_PATH = 'content/tsms/';

    /**
     * Creates new UbillingSMS object instance
     */
    public function __construct() {
        $this->loadConfig();
    }

    /**
     * Loads required configs into protected props for further usage
     *
     * @global object $ubillingConfig
     *
     * @return void
     */
    protected function loadConfig() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
        if (@$this->altCfg['SMS_SERVICES_ADVANCED_ENABLED']) {
            $this->smsRoutingFlag = true;
        }
    }

    /**
     * Stores SMS in sending queue 
     * 
     * @param string $number Mobile number in international format. Eg: +380506666666
     * @param string $message Text message for sending
     * @param bool $translit force message transliteration
     * @param string $module module that inits SMS sending
     * 
     * @return void/string - filename in queue
     */
    public function sendSMS($number, $message, $translit = true, $module = '') {
        $result = '';
        $number = trim($number);
        $module = (!empty($module)) ? ' MODULE ' . $module : '';
        if (!empty($number)) {
            if (ispos($number, '+')) {
                $message = str_replace(array("\n\r", "\n", "\r"), ' ', $message);
                if ($translit) {
                    $message = zb_TranslitString($message);
                }
                $message = trim($message);
                $queueId = 'us_' . zb_rand_string(8);
                $filename = self::QUEUE_PATH . $queueId;
                $storedata = 'NUMBER="' . $number . '"' . "\n";
                $storedata.= 'MESSAGE="' . $message . '"' . "\n";
                //$storedata.= 'SMSSRVID="' . $SMSServID . '"' . "\n";
                file_put_contents($filename, $storedata);
                log_register('USMS SEND SMS `' . $number . '`' . $module);
                $result = $queueId;
            }
        }
        return ($result);
    }

    /**
     * Returns count of SMS available in queue
     * 
     * @return int
     */
    public function getQueueCount() {
        $smsQueueCount = rcms_scandir(self::QUEUE_PATH);
        $result = sizeof($smsQueueCount);
        return ($result);
    }

    /**
     * Returns array containing all SMS queue data as index=>data
     * 
     * @return array
     */
    public function getQueueData() {
        $result = array();
        $smsQueue = rcms_scandir(self::QUEUE_PATH);
        if (!empty($smsQueue)) {
            foreach ($smsQueue as $io => $eachsmsfile) {
                $smsDate = date("Y-m-d H:i:s", filectime(self::QUEUE_PATH . $eachsmsfile));
                $smsData = rcms_parse_ini_file(self::QUEUE_PATH . $eachsmsfile);
                $result[$io]['filename'] = $eachsmsfile;
                $result[$io]['date'] = $smsDate;
                $result[$io]['number'] = $smsData['NUMBER'];
                $result[$io]['message'] = $smsData['MESSAGE'];
                $result[$io]['smssrvid'] = $smsData['SMSSRVID'];
            }
        }
        return ($result);
    }

    /**
     * Deletes SMS from local queue
     * 
     * @param string $filename Existing sms filename
     * 
     * @return int 0 - ok, 1 - deletion unsuccessful, 2 - file not found 
     */
    public function deleteSms($filename) {
        if (file_exists(self::QUEUE_PATH . $filename)) {
            rcms_delete_files(self::QUEUE_PATH . $filename);
            $result = 0;
            if (file_exists(self::QUEUE_PATH . $filename)) {
                $result = 1;
            }
        } else {
            $result = 2;
        }
        return ($result);
    }

    /**
     * Sets routing direction to SMS queue file
     *
     * @param string $queueFile
     * @param string $type
     * @param string $entity
     * @param string $forceDirection
     *
     * @return void
     */
    public function setDirection($queueFile, $type, $entity, $forceDirection = '') {
        if ($this->smsRoutingFlag) {
            if (file_exists(self::QUEUE_PATH . $queueFile)) {
                if (empty($forceDirection)) {
                    //here we detects direction of SMS
                } else {
                    $newDirection = $forceDirection;
                }

                //saving data to queue
                $newDirection = trim($newDirection);
                $storedata = 'SMSSRVID="' . $newDirection . '"' . "\n";
                file_put_contents(self::QUEUE_PATH . $queueFile, $storedata, FILE_APPEND);
            }
        }
    }

}

?>