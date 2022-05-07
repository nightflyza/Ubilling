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
    public $smsRoutingFlag = false;

    /**
     * Placeholder for $SMSDirections object
     *
     * @var null
     */
    public $smsDirections = null;

    const QUEUE_PATH = 'content/tsms/';

    /**
     * Creates new UbillingSMS object instance
     */
    public function __construct() {
        global $ubillingConfig;
        $this->smsRoutingFlag = $ubillingConfig->getAlterParam('SMS_SERVICES_ADVANCED_ENABLED');

        if ($this->smsRoutingFlag) {
            $this->smsDirections = new SMSDirections();
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
     * @return string - filename in queue
     */
    public function sendSMS($number, $message, $translit = true, $module = '') {
        $result = '';
        $number = trim($number);
        $module = (!empty($module)) ? ' MODULE ' . $module : '';
        $prefix = 'usms_';
        if (!empty($number)) {
            if (ispos($number, '+')) {
                $message = str_replace(array("\n\r", "\n", "\r"), ' ', $message); //single line
                $message = str_replace(array("'", '"'), '`', $message); // dangerous quotes
                if ($translit) {
                    $message = zb_TranslitString($message, true);
                }

                $message = trim($message);
                $queueId = time();
                $offset = 0;
                $filename = self::QUEUE_PATH . $prefix . $queueId . '_' . $offset;
                if (file_exists($filename)) {
                    while (file_exists($filename)) {
                        $offset++; //incremeting number of messages per second
                        $filename = self::QUEUE_PATH . $prefix . $queueId . '_' . $offset;
                    }
                }

                $storedata = 'NUMBER="' . $number . '"' . "\n";
                $storedata .= 'MESSAGE="' . $message . '"' . "\n";
                file_put_contents($filename, $storedata);
                log_register('USMS SEND SMS FOR `' . $number . '` AS `' . $prefix . $queueId . '_' . $offset . '` ' . $module);
                $result = $prefix . $queueId . '_' . $offset;
            }
        }
        return ($result);
    }

    /**
     * Sets routing direction to SMS queue file
     *
     * @param string $queueFile
     * @param string $keyType - array key type in Ubilling cache(login, emploeeid, ukvid and so on)
     * @param string $entity - key of array associated with $KeyType
     * @param string $forceDirection
     *
     * @return void
     */
    public function setDirection($queueFile, $keyType, $entity, $forceDirection = '') {
        if ($this->smsRoutingFlag) {
            if (!empty($queueFile) and file_exists(self::QUEUE_PATH . $queueFile)) {
                if (empty($forceDirection)) {
                    $newDirection = $this->smsDirections->getDirection($keyType, $entity);
                } else {
                    $newDirection = $forceDirection;
                }

                //saving data to queue
                $newDirection = trim($newDirection);
                $storeData = 'SMSSRVID="' . $newDirection . '"' . "\n";
                file_put_contents(self::QUEUE_PATH . $queueFile, $storeData, FILE_APPEND);
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
                $result[$io]['smssrvid'] = (isset($smsData['SMSSRVID'])) ? $smsData['SMSSRVID'] : 0;
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