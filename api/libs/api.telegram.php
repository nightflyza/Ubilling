<?php

/**
 * Telegram bot API implementation
 */
class UbillingTelegram {

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains current instance bot token
     *
     * @var string
     */
    protected $botToken = '';

    /**
     * Default debug flag wich enables telegram replies display
     *
     * @var bool
     */
    protected $debug = false;

    /**
     * Contains base Telegram API URL 
     */
    protected $apiUrl = 'https://api.telegram.org/bot';

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
        $this->loadAlter();
        $this->setOptions();
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
     * Object instance debug state setter
     * 
     * @param bool $state
     * 
     * @return void
     */
    public function setDebug($state) {
        $this->debug = $state;
    }

    /**
     * Loads system alter config into protected property for further usage
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadAlter() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Sets some current instance options if required
     * 
     * @return void
     */
    protected function setOptions() {
        //settin debug flag
        if (isset($this->altCfg['TELEGRAM_DEBUG'])) {
            if ($this->altCfg['TELEGRAM_DEBUG']) {
                $this->debug = true;
            }
        }

        if (isset($this->altCfg['TELEGRAM_API_URL'])) {
            if (!empty($this->altCfg['TELEGRAM_API_URL'])) {
                $this->setApiUrl($this->altCfg['TELEGRAM_API_URL']);
            }
        }
    }

    /**
     * Setter of custom API URL (legacy fallback)
     * 
     * @param string $url
     * 
     * @return void
     */
    protected function setApiUrl($url) {
        $this->apiUrl = $url;
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
            $message = str_replace(array("\n\r", "\n", "\r"), ' ', $message);
            if ($translit) {
                $message = zb_TranslitString($message);
            }
            $message = trim($message);
            $queueId = 'tlg_' . zb_rand_string(8);
            $filename = self::QUEUE_PATH . $queueId;
            $storedata = 'CHATID="' . $chatid . '"' . "\n";
            $storedata .= 'MESSAGE="' . $message . '"' . "\n";
            file_put_contents($filename, $storedata);
            log_register('UTLG SEND MESSAGE FOR `' . $chatid . '` AS `' . $queueId . '` ' . $module);
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
     * @param int $offset
     * @param int $limit
     * @param int $timeout
     * 
     * @return array
     * 
     * @throws Exception
     */
    protected function getUpdatesRaw($offset = '', $limit = '', $timeout = '') {
        $result = array();
        $timeout = vf($timeout, 3);
        $limit = vf($limit, 3);
        $offset = mysql_real_escape_string($offset);

        $timeout = (!empty($timeout)) ? $timeout : 0; //default timeout in seconds is 0
        $limit = (!empty($limit)) ? $limit : 100; //defult limit is 100
        /**
         * Identifier of the first update to be returned. Must be greater by one than the highest among the identifiers of previously received updates. 
         * By default, updates starting with the earliest unconfirmed update are returned. An update is considered confirmed as soon as getUpdates is 
         * called with an offset higher than its update_id. The negative offset can be specified to retrieve updates starting from -offset update from
         * the end of the updates queue. All previous updates will forgotten.
         */
        $offset = (!empty($offset)) ? '&offset=' . $offset : '';
        if (!empty($this->botToken)) {
            $options = '?timeout=' . $timeout . '&limit=' . $limit . $offset;
            $url = $this->apiUrl . $this->botToken . '/getUpdates' . $options;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($ch, CURLOPT_POST, 1);
            @$reply = curl_exec($ch);
            if ($this->debug) {
                $curlError = curl_error($ch);
                show_info($url);
                if (!empty($curlError)) {
                    show_error(__('Error') . ' ' . __('Telegram') . ': ' . $curlError);
                } else {
                    show_success(__('Telegram API connection to') . ' ' . $this->apiUrl . ' ' . __('success'));
                }
            }
            curl_close($ch);
            if (!empty($reply)) {
                $result = json_decode($reply, true);
            }

            if ($this->debug) {
                debarr($result);
            }
        } else {
            throw new Exception('EX_TOKEN_EMPTY');
        }
        return ($result);
    }

    /**
     * Returns all messages received by bot
     * 
     * @return array
     * @throws Exception
     */
    public function getBotChats() {
        $result = array();
        if (!empty($this->botToken)) {
            $rawUpdates = $this->getUpdatesRaw();
            if (!empty($rawUpdates)) {
                if (isset($rawUpdates['result'])) {
                    $allUpdates = $rawUpdates['result'];
                    foreach ($allUpdates as $io => $each) {
                        if (isset($each['message'])) {
                            if (isset($each['message']['chat'])) {
                                if (isset($each['message']['chat']['type'])) {
                                    $messageData = $each['message'];
                                    if ($messageData['chat']['type'] == 'private') {
                                        //direct message
                                        if (isset($messageData['message_id'])) {
                                            $messageId = $messageData['message_id'];
                                            $result[$messageId]['id'] = $messageId;
                                            $result[$messageId]['date'] = date("Y-m-d H:i:s", $messageData['date']);
                                            $result[$messageId]['chatid'] = $messageData['from']['id'];
                                            $result[$messageId]['from'] = @$messageData['from']['username'];
                                            $result[$messageId]['text'] = @$messageData['text'];
                                            $result[$messageId]['type'] = 'user';
                                            $result[$messageId]['chanid'] = '';
                                            $result[$messageId]['channame'] = '';
                                            $result[$messageId]['updateid'] = @$each['update_id'];
                                        }
                                    }

                                    //supergroup message
                                    if ($messageData['chat']['type'] == 'supergroup') {
                                        if (isset($messageData['message_id'])) {
                                            $messageId = $messageData['message_id'];
                                            $result[$messageId]['id'] = $messageId;
                                            $result[$messageId]['date'] = date("Y-m-d H:i:s", $messageData['date']);
                                            $result[$messageId]['chatid'] = $messageData['from']['id'];
                                            $result[$messageId]['from'] = @$messageData['from']['username'];
                                            $result[$messageId]['text'] = @$messageData['text'];
                                            $result[$messageId]['type'] = 'supergroup';
                                            $result[$messageId]['chanid'] = $messageData['chat']['id'];
                                            $result[$messageId]['channame'] = $messageData['chat']['username'];
                                            $result[$messageId]['updateid'] = '';
                                            $result[$messageId]['updateid'] = @$each['update_id'];
                                        }
                                    }
                                }
                            }
                        }

                        //channel message
                        if (isset($each['channel_post'])) {
                            $messageData = $each['channel_post'];
                            if (isset($messageData['message_id'])) {
                                $messageId = $messageData['message_id'];
                                $result[$messageId]['id'] = $messageId;
                                $result[$messageId]['date'] = date("Y-m-d H:i:s", $messageData['date']);
                                $result[$messageId]['chatid'] = $messageData['chat']['id'];
                                $result[$messageId]['from'] = @$messageData['chat']['username'];
                                $result[$messageId]['text'] = @$messageData['text'];
                                $result[$messageId]['type'] = 'channel';
                            }
                        }
                    }
                }
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
                        //supergroup messages
                        if (isset($each['message'])) {
                            if (isset($each['message']['chat'])) {
                                if (isset($each['message']['chat']['type'])) {
                                    if ($each['message']['chat']['type'] = 'supergroup') {
                                        $groupData = $each['message']['chat'];
                                        $result[$groupData['id']]['chatid'] = $groupData['id'];
                                        $groupName = (!empty($groupData['username'])) ? $groupData['username'] : @$groupData['title']; //only title for private groups
                                        $result[$groupData['id']]['name'] = $groupName;
                                        $result[$groupData['id']]['type'] = 'supergroup';
                                        $result[$groupData['id']]['lastmessage'] = strip_tags(@$each['message']['text']);
                                    }
                                }
                            }
                        }
                        //direct user message
                        if (isset($each['message'])) {
                            if (isset($each['message']['from'])) {
                                if (isset($each['message']['from']['id'])) {
                                    $messageData = $each['message']['from'];
                                    $result[$messageData['id']]['chatid'] = $messageData['id'];
                                    $result[$messageData['id']]['name'] = @$messageData['username']; //may be empty
                                    $result[$messageData['id']]['type'] = 'user';
                                    $result[$messageData['id']]['lastmessage'] = strip_tags(@$each['message']['text']);
                                }
                            }
                        }

                        //channel message
                        if (isset($each['channel_post'])) {
                            if (isset($each['channel_post']['chat'])) {
                                if (isset($each['channel_post']['chat']['id'])) {
                                    $chatData = $each['channel_post']['chat'];
                                    $result[$chatData['id']]['chatid'] = $chatData['id'];
                                    $result[$chatData['id']]['name'] = $chatData['username'];
                                    $result[$chatData['id']]['type'] = 'channel';
                                    $result[$messageData['id']]['lastmessage'] = strip_tags(@$each['message']['text']);
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
     * Preprocess keyboard for sending with directPushMessage
     * 
     * @param array $buttonsArray
     * @param bool $inline
     * @param bool $resize
     * @param bool  $oneTime
     * 
     * @return array
     */
    public function makeKeyboard($buttonsArray, $inline = false, $resize = true, $oneTime = false) {
        $result = array();
        if (!empty($buttonsArray)) {
            if (!$inline) {
                $result['type'] = 'keyboard';

                $keyboardMarkup = array(
                    'keyboard' => $buttonsArray,
                    'resize_keyboard' => $resize,
                    'one_time_keyboard' => $oneTime
                );

                $result['markup'] = $keyboardMarkup;
            }
        }
        return($result);
    }

    /**
     * Sends message to some chat id via Telegram API
     * 
     * @param int $chatid remote chatId
     * @param string $message text message to send
     * @param array $keyboard keyboard encoded with makeKeyboard method
     * @throws Exception
     * 
     * @return void
     */
    public function directPushMessage($chatid, $message, $keyboard = array()) {
        $data['chat_id'] = $chatid;
        $data['text'] = $message;

        if ($this->debug) {
            debarr($data);
        }


        //default sending method
        $method = 'sendMessage';

        //location sending
        if (ispos($message, 'sendLocation:')) {
            $cleanGeo = str_replace('sendLocation:', '', $message);
            $cleanGeo = explode(',', $cleanGeo);
            $geoLat = trim($cleanGeo[0]);
            $geoLon = trim($cleanGeo[1]);
            $locationParams = '?chat_id=' . $chatid . '&latitude=' . $geoLat . '&longitude=' . $geoLon;
            $method = 'sendLocation' . $locationParams;
        }

        //custom markdown
        if (ispos($message, 'parseMode:{')) {
            if (preg_match('!\{(.*?)\}!si', $message, $tmpMode)) {
                $cleanParseMode = $tmpMode[1];
                $parseModeMask = 'parseMode:{' . $cleanParseMode . '}';
                $cleanMessage = str_replace($parseModeMask, '', $message);
                $data['text'] = $cleanMessage;
                $method = 'sendMessage?parse_mode=' . $cleanParseMode;
            }
        }

        //venue sending
        if (ispos($message, 'sendVenue:')) {
            if (preg_match('!\[(.*?)\]!si', $message, $tmpGeo)) {
                $cleanGeo = $tmpGeo[1];
            }

            if (preg_match('!\((.*?)\)!si', $message, $tmpAddr)) {
                $cleanAddr = $tmpAddr[1];
            }

            if (preg_match('!\{(.*?)\}!si', $message, $tmpTitle)) {
                $cleanTitle = $tmpTitle[1];
            }

            $data['title'] = $cleanTitle;
            $data['address'] = $cleanAddr;


            $cleanGeo = explode(',', $cleanGeo);
            $geoLat = trim($cleanGeo[0]);
            $geoLon = trim($cleanGeo[1]);
            $locationParams = '?chat_id=' . $chatid . '&latitude=' . $geoLat . '&longitude=' . $geoLon;
            $method = 'sendVenue' . $locationParams;
        }

        //photo sending
        if (ispos($message, 'sendPhoto:')) {
            if (preg_match('!\[(.*?)\]!si', $message, $tmpPhoto)) {
                $cleanPhoto = $tmpPhoto[1];
            }

            if (preg_match('!\{(.*?)\}!si', $message, $tmpCaption)) {
                $cleanCaption = $tmpCaption[1];
            }

            $photoParams = '?chat_id=' . $chatid . '&photo=' . $cleanPhoto;
            if (!empty($cleanCaption)) {
                $photoParams .= '&caption=' . $cleanCaption;
            }
            $method = 'sendPhoto' . $photoParams;
        }

        //sending keyboard
        if (!empty($keyboard)) {
            if (isset($keyboard['type'])) {
                if ($keyboard['type'] == 'keyboard') {
                    $encodedKeyboard = json_encode($keyboard['markup']);
                    $data['reply_markup'] = $encodedKeyboard;
                }

                //TODO: inline keyboards

                $method = 'sendMessage';
            }
        }

        //removing keyboard
        if (ispos($message, 'removeKeyboard:')) {
            $keybRemove = array(
                'remove_keyboard' => true
            );
            $encodedMarkup = json_encode($keybRemove);
            $cleanMessage = str_replace('removeKeyboard:', '', $message);
            if (empty($cleanMessage)) {
                $cleanMessage = __('Keyboard deleted');
            }
            $data['text'] = $cleanMessage;
            $data['reply_markup'] = $encodedMarkup;
        }


        //POST data encoding
        $data_json = json_encode($data);

        if (!empty($this->botToken)) {
            $url = $this->apiUrl . $this->botToken . '/' . $method;
            if ($this->debug) {
                deb($url);
            }
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
            if ($this->debug) {
                deb(curl_exec($ch));
                $curlError = curl_error($ch);
                if (!empty($curlError)) {
                    show_error(__('Error') . ' ' . __('Telegram') . ': ' . $curlError);
                } else {
                    show_success(__('Telegram API sending via') . ' ' . $this->apiUrl . ' ' . __('success'));
                }
            } else {
                curl_exec($ch);
            }
            curl_close($ch);
        } else {
            throw new Exception('EX_TOKEN_EMPTY');
        }
    }

    /**
     * Sets HTTPS web hook URL for some bot
     * 
     * @param string $webHookUrl HTTPS url to send updates to. Use an empty string to remove webhook integration
     * @param int $maxConnections Maximum allowed number of simultaneous HTTPS connections to the webhook for update delivery, 1-100. Defaults to 40.
     * 
     * @return string
     */
    public function setWebHook($webHookUrl, $maxConnections = 40) {
        $result = '';
        if (!empty($this->botToken)) {
            $data = array();
            if (!empty($webHookUrl)) {
                $method = 'setWebhook';
                if (ispos($webHookUrl, 'https://')) {
                    $data['url'] = $webHookUrl;
                    $data['max_connections'] = $maxConnections;
                } else {
                    throw new Exception('EX_NOT_SSL_URL');
                }
            } else {
                $method = 'deleteWebhook';
            }

            $url = $this->apiUrl . $this->botToken . '/' . $method;
            if ($this->debug) {
                deb($url);
            }
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($ch, CURLOPT_POST, 1);
            if (!empty($data)) {
                $data_json = json_encode($data);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
            }

            if ($this->debug) {
                $result = curl_exec($ch);
                deb($result);
                $curlError = curl_error($ch);
                if (!empty($curlError)) {
                    show_error(__('Error') . ' ' . __('Telegram') . ': ' . $curlError);
                } else {
                    show_success(__('Telegram API sending via') . ' ' . $this->apiUrl . ' ' . __('success'));
                }
            } else {
                $result = curl_exec($ch);
            }
            curl_close($ch);
        } else {
            throw new Exception('EX_TOKEN_EMPTY');
        }
        return($result);
    }

    /**
     * Returns bot web hook info
     * 
     * @return string
     */
    public function getWebHookInfo() {
        $result = '';
        if (!empty($this->botToken)) {
            $method = 'getWebhookInfo';
            $url = $this->apiUrl . $this->botToken . '/' . $method;
            if ($this->debug) {
                deb($url);
            }
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            if ($this->debug) {
                $result = curl_exec($ch);
                deb($result);
                $curlError = curl_error($ch);
                if (!empty($curlError)) {
                    show_error(__('Error') . ' ' . __('Telegram') . ': ' . $curlError);
                } else {
                    show_success(__('Telegram API sending via') . ' ' . $this->apiUrl . ' ' . __('success'));
                }
            } else {
                $result = curl_exec($ch);
            }
            curl_close($ch);
        }

        return($result);
    }

    /**
     * Returns chat data array by its chatId
     * 
     * @param int chatId
     * 
     * @return array
     */
    public function getChatInfo($chatId) {
        $result = array();
        if (!empty($this->botToken) AND ( !empty($chatId))) {
            $method = 'getChat';
            $url = $this->apiUrl . $this->botToken . '/' . $method . '?chat_id=' . $chatId;
            if ($this->debug) {
                deb($url);
            }
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            if ($this->debug) {
                $result = curl_exec($ch);
                deb($result);
                $curlError = curl_error($ch);
                if (!empty($curlError)) {
                    show_error(__('Error') . ' ' . __('Telegram') . ': ' . $curlError);
                } else {
                    show_success(__('Telegram API sending via') . ' ' . $this->apiUrl . ' ' . __('success'));
                }
            } else {
                $result = curl_exec($ch);
            }

            curl_close($ch);

            if (!empty($result)) {
                $result = json_decode($result, true);
            }
        }

        return($result);
    }

    /**
     * Returns webhook data
     * 
     * @param bool $rawData receive raw reply or preprocess to something more simple.
     * 
     * @return array
     */
    public function getHookData($rawData = false) {
        $result = array();
        $postRaw = file_get_contents('php://input');
        if (!empty($postRaw)) {
            $postRaw = json_decode($postRaw, true);
            if ($this->debug) {
                debarr($result);
            }

            if (!$rawData) {
                if (isset($postRaw['message'])) {
                    if (isset($postRaw['message']['from'])) {
                        $result['message_id'] = $postRaw['message']['message_id'];
                        $result['from']['id'] = $postRaw['message']['from']['id'];
                        @$result['from']['username'] = $postRaw['message']['from']['username'];
                        $result['from']['first_name'] = $postRaw['message']['from']['first_name'];
                        @$result['from']['language_code'] = $postRaw['message']['from']['language_code'];
                        $result['chat']['id'] = $postRaw['message']['chat']['id'];
                        $result['date'] = $postRaw['message']['date'];
                        $result['chat']['type'] = $postRaw['message']['chat']['type'];
                        @$result['text'] = $postRaw['message']['text'];
                        @$result['photo'] = $postRaw['message']['photo'];
                        @$result['document'] = $postRaw['message']['document'];
                        @$result['voice'] = $postRaw['message']['voice'];
                        @$result['audio'] = $postRaw['message']['audio'];
                        @$result['video_note'] = $postRaw['message']['video_note'];
                    }
                }
            } else {
                $result = $postRaw;
            }
        }
        return($result);
    }

}

?>