<?php

class UbillingTelegram {

    /**
     * Contains current instance bot token
     *
     * @var string
     */
    protected $botToken = '';

    /**
     * Contains base Telegram API URL 
     */
    const URL_API = 'https://api.telegram.org/bot';

    /**
     * Contains telegram messages path
     */
    const QUEUE_PATH = 'content/telegram/';

    /**
     * Creates new Telegram object instance
     * 
     * @param string $token
     */
    public function __construct($token = '') {
        if (!empty($token)) {
            $this->botToken = $token;
        }
    }

    /**
     * Sets current instance auth token
     * 
     * @param string $token
     * 
     * @return void
     */
    public function setToken($token) {
        $this->botToken = $token;
    }

    /**
     * Stores message in telegram sending queue. Use this method in your modules.
     * 
     * @param int $chatid
     * @param string $message
     * @param bool $translit
     * @param string $module
     * 
     * @return bool
     */
    public function sendMessage($chatid, $message, $translit = false, $module = '') {
        $result = false;
        $chatid = trim($chatid);
        $module = (!empty($module)) ? ' MODULE ' . $module : '';
        if (!empty($chatid)) {
            $message = str_replace('\r\n', ' ', $message);
            if ($translit) {
                $message = zb_TranslitString($message);
            }
            $message = trim($message);
            $filename = self::QUEUE_PATH . 'tlg_' . zb_rand_string(8);
            $storedata = 'CHATID="' . $chatid . '"' . "\n";
            $storedata.='MESSAGE="' . $message . '"' . "\n";
            file_put_contents($filename, $storedata);
            log_register('UTLG SEND MESSAGE `' . $chatid . '`' . $module);
            $result = true;
        }
        return ($result);
    }

    /**
     * Returns count of messages available in queue
     * 
     * @return int
     */
    public function getQueueCount() {
        $messagesQueueCount = rcms_scandir(self::QUEUE_PATH);
        $result = sizeof($messagesQueueCount);
        return ($result);
    }

    /**
     * Returns array containing all messages queue data as index=>data
     * 
     * @return array
     */
    public function getQueueData() {
        $result = array();
        $messagesQueue = rcms_scandir(self::QUEUE_PATH);
        if (!empty($messagesQueue)) {
            foreach ($messagesQueue as $io => $eachmessage) {
                $messageDate = date("Y-m-d H:i:s", filectime(self::QUEUE_PATH . $eachmessage));
                $messageData = rcms_parse_ini_file(self::QUEUE_PATH . $eachmessage);
                $result[$io]['filename'] = $eachmessage;
                $result[$io]['date'] = $messageDate;
                $result[$io]['chatid'] = $messageData['CHATID'];
                $result[$io]['message'] = $messageData['MESSAGE'];
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
    public function deleteMessage($filename) {
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
     * Returns raw updates array
     * 
     * @return array
     */
    protected function getUpdatesRaw() {
        $result = array();
        if (!empty($this->botToken)) {
            $url = self::URL_API . $this->botToken . '/getUpdates';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($ch, CURLOPT_POST, 1);
            @$reply = curl_exec($ch);
            curl_close($ch);
            if (!empty($reply)) {
                $result = json_decode($reply, true);
            }
        } else {
            throw new Exception('EX_TOKEN_EMPTY');
        }
        return ($result);
    }

    /**
     * Returns current bot contacts list as chat_id=>name
     * 
     * @return array
     */
    public function getBotContacts() {
        $result = array();
        $updatesRaw = $this->getUpdatesRaw();
        if (!empty($updatesRaw)) {
            if (isset($updatesRaw['result'])) {
                if (!empty($updatesRaw['result'])) {
                    foreach ($updatesRaw['result'] as $io => $each) {
                        //debarr($each);
                        if (isset($each['message'])) {
                            if (isset($each['message']['from'])) {
                                if (isset($each['message']['from']['id'])) {
                                    $messageData = $each['message']['from'];
                                    $result[$messageData['id']]['chatid'] = $messageData['id'];
                                    $result[$messageData['id']]['name'] = $messageData['username'];
                                    $result[$messageData['id']]['type'] = 'user';
                                }
                            }
                        }

                        if (isset($each['channel_post'])) {
                            if (isset($each['channel_post']['chat'])) {
                                if (isset($each['channel_post']['chat']['id'])) {
                                    $chatData = $each['channel_post']['chat'];
                                    $result[$chatData['id']]['chatid'] = $chatData['id'];
                                    $result[$chatData['id']]['name'] = $chatData['username'];
                                    $result[$chatData['id']]['type'] = 'channel';
                                }
                            }
                        }
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Sends message to some chat id via Telegram API
     * 
     * @param int $chatid
     * @param string $message
     * @throws Exception
     * 
     * @return void
     */
    public function directPushMessage($chatid, $message) {
        $data['chat_id'] = $chatid;
        $data['text'] = $message;
        $data_json = json_encode($data);

        if (!empty($this->botToken)) {
            $url = self::URL_API . $this->botToken . '/sendMessage';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
            curl_exec($ch);
            curl_close($ch);
        } else {
            throw new Exception('EX_TOKEN_EMPTY');
        }
    }

}

?>