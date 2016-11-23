<?php

class UbillingTelegram {

    /**
     * Contains current instance bot token
     *
     * @var string
     */
    protected $botToken = '';

    const URL_API = 'https://api.telegram.org/bot';

    public function __construct() {
        
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
    public function directSendMessage($chatid, $message) {
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