<?php

/**
 * SMS queue handling class
 */
class UbillingSMS {
    /**
     * SMS_SERVICES_ADVANCED_ENABLED option state
     *
     * @var bool
     */
    protected $SMSRoutingFlag = false;

    /**
     * Placeholder for $SMSDirections object
     *
     * @var null
     */
    protected $SMSDirections = null;

    const QUEUE_PATH = 'content/tsms/';

    /**
     * Creates new UbillingSMS object instance
     */
    public function __construct() {
        global $ubillingConfig;
        $this->SMSRoutingFlag = $ubillingConfig->getAlterParam('SMS_SERVICES_ADVANCED_ENABLED');
        $this->SMSDirections = new SMSDirections();
    }

    /**
     * Stores SMS in sending queue 
     * 
     * @param string $number Mobile number in international format. Eg: +380506666666
     * @param string $message Text message for sending
     * @param bool $translit force message transliteration
     * @param string $module module that inits SMS sending
     * 
     * @return string - filename in queue
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
                file_put_contents($filename, $storedata);
                log_register('USMS SEND SMS `' . $number . '`' . $module);
                $result = $queueId;
            }
        }
        return ($result);
    }

    /**
     * Sets routing direction to SMS queue file
     *
     * @param string $QueueFile
     * @param string $KeyType - array key type in Ubilling cache(login, emploeeid, ukvid and so on)
     * @param string $Entity - key of array associated with $KeyType
     * @param string $ForceDirection
     *
     * @return void
     */
    public function setDirection($QueueFile, $KeyType, $Entity, $ForceDirection = '') {
        if ($this->SMSRoutingFlag) {
            if (file_exists(self::QUEUE_PATH . $QueueFile)) {
                if (empty($ForceDirection)) {
                    $NewDirection = $this->SMSDirections->getDirection($KeyType, $Entity);
                } else {
                    $NewDirection = $ForceDirection;
                }

                //saving data to queue
                $NewDirection = trim($NewDirection);
                $StoreData = 'SMSSRVID="' . $NewDirection . '"' . "\n";
                file_put_contents(self::QUEUE_PATH . $QueueFile, $StoreData, FILE_APPEND);
            }
        }
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
}

?>