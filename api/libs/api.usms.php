<?php

/**
 * SMS queue handling class
 */
class UbillingSMS {

    const QUEUE_PATH = 'content/tsms/';

    /**
     * Stores SMS in sending queue 
     * 
     * @param string $number Mobile number in international format. Eg: +380506666666
     * @param string $message Text message for sending
     * @param bool $translit force message transliteration
     * @return bool
     */
    public function sendSMS($number, $message, $translit = true) {
        $result = false;
        $number = trim($number);
        if (!empty($number)) {
            if (ispos($number, '+')) {
                $message = str_replace('\r\n', ' ', $message);
                if ($translit) {
                    $message = zb_TranslitString($message);
                }
                $message = trim($message);
                $filename = self::QUEUE_PATH . 'us_' . zb_rand_string(8);
                $storedata = 'NUMBER="' . $number . '"' . "\n";
                $storedata.='MESSAGE="' . $message . '"' . "\n";
                $result['number'] = $number;
                $result['message'] = $message;
                file_put_contents($filename, $storedata);
                log_register("USMS SEND SMS `" . $number . "`");
                $result = true;
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