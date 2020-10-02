<?php

class UbillingMail {

    /**
     * Contains telegram messages path
     */
    const QUEUE_PATH = 'content/mailqueue/';

    /**
     * Creates new Email queue class instance
     */
    public function __construct() {
        
    }

    /**
     * Stores message in email sending queue. Use this method in your modules.
     * 
     * @param string $email
     * @param string $subj
     * @param string $message
     * @param string $module
     * 
     * @return bool
     */
    public function sendEmail($email, $subj, $message, $module = '') {
        $result = false;
        $email = trim($email);
        $subj = trim($subj);
        $module = (!empty($module)) ? ' MODULE ' . $module : '';
        if (!empty($email)) {
            $message = trim($message);
            $filename = self::QUEUE_PATH . 'eml_' . zb_rand_string(8);
            $storedata['email'] = $email;
            $storedata['subj'] = $subj;
            $storedata['message'] = $message;
            $storedata = json_encode($storedata);

            file_put_contents($filename, $storedata);
            log_register('UEML SEND EMAIL `' . $email . '`' . $module);
            $result = true;
        }
        return ($result);
    }

    /**
     * Returns count of emails available in queue
     * 
     * @return int
     */
    public function getQueueCount() {
        $messagesQueueCount = rcms_scandir(self::QUEUE_PATH);
        $result = sizeof($messagesQueueCount);
        return ($result);
    }

    /**
     * Returns array containing all emails queue data as index=>data
     * 
     * @return array
     */
    public function getQueueData() {
        $result = array();
        $messagesQueue = rcms_scandir(self::QUEUE_PATH);
        if (!empty($messagesQueue)) {
            foreach ($messagesQueue as $io => $eachmessage) {
                $messageDate = date("Y-m-d H:i:s", filectime(self::QUEUE_PATH . $eachmessage));
                $messageData = file_get_contents(self::QUEUE_PATH . $eachmessage);
                $messageData = json_decode($messageData, true);
                $result[$io]['filename'] = $eachmessage;
                $result[$io]['date'] = $messageDate;
                $result[$io]['email'] = $messageData['email'];
                $result[$io]['subj'] = $messageData['subj'];
                $result[$io]['message'] = $messageData['message'];
            }
        }
        return ($result);
    }

    /**
     * Deletes message from local queue
     * 
     * @param string $filename Existing message filename
     * 
     * @return int 0 - ok, 1 - deletion unsuccessful, 2 - file not found 
     */
    public function deleteEmail($filename) {
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
     * Directly sends email message to recepient using PHP mail function.
     * 
     * @param string $email
     * @param string $subj
     * @param string $message
     * 
     * @return void
     */
    public function directPushEmail($email, $subj, $message) {
        $sender = __('Ubilling');
        $headers = 'From: =?UTF-8?B?' . base64_encode($sender) . '?= <' . $email . ">\n";
        $headers .= "MIME-Version: 1.0\n";
        $headers .= 'Message-ID: <' . md5(uniqid(time())) . "@" . $sender . ">\n";
        $headers .= 'Date: ' . gmdate('D, d M Y H:i:s T', time()) . "\n";
        $headers .= "Content-type: text/plain; charset=UTF-8\n";
        $headers .= "Content-transfer-encoding: 8bit\n";
        $headers .= "X-Mailer: Ubilling\n";
        $headers .= "X-MimeOLE: Ubilling\n";
        mail($email, '=?UTF-8?B?' . base64_encode($subj) . '?=', $message, $headers);
    }

}

?>