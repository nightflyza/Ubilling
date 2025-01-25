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
     * Maximum message length
     */
    const MESSAGE_LIMIT = 4095;

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
        $prefix = 'tlg_';
        if (!empty($chatid)) {
            $message = str_replace(array("\n\r", "\n", "\r"), ' ', $message);
            if ($translit) {
                $message = zb_TranslitString($message);
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


            $storedata = 'CHATID="' . $chatid . '"' . "\n";
            $storedata .= 'MESSAGE="' . $message . '"' . "\n";
            file_put_contents($filename, $storedata);
            log_register('UTLG SEND MESSAGE FOR `' . $chatid . '` AS `' . $prefix . $queueId . '_' . $offset . '` ' . $module);
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
                                        $result[$groupData['id']]['first_name'] = @$groupData['title'];
                                        $result[$groupData['id']]['last_name'] = '';
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
                                    $result[$messageData['id']]['first_name'] = @$messageData['first_name'];
                                    $result[$messageData['id']]['last_name'] = @$messageData['last_name'];
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
                                    $result[$chatData['id']]['first_name'] = '';
                                    $result[$chatData['id']]['last_name'] = '';
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

            if ($inline) {
                $result['type'] = 'inline';
                $keyboardMarkup = $buttonsArray;

                $result['markup'] = $keyboardMarkup;
            }
        }
        return ($result);
    }

    /**
     * Split message into chunks of safe size
     * 
     * @param string $message
     * 
     * @return array
     */
    protected function splitMessage($message) {
        $result = preg_split('~~u', $message, -1, PREG_SPLIT_NO_EMPTY);
        $chunks = array_chunk($result, self::MESSAGE_LIMIT);
        foreach ($chunks as $i => $chunk) {
            $chunks[$i] = join('', (array) $chunk);
        }
        $result = $chunks;

        return ($result);
    }

    /**
     * Sends message to some chat id using Telegram API
     * 
     * @param int $chatid remote chatId
     * @param string $message text message to send
     * @param array $keyboard keyboard encoded with makeKeyboard method
     * @param bool $nosplit dont automatically split message into 4096 slices
     * @param int $replyToMsgId optional message ID which is reply for
     * 
     * @return string/bool
     */
    public function directPushMessage($chatid, $message, $keyboard = array(), $noSplit = false, $replyToMsgId = '') {
        $result = '';
        if ($noSplit) {
            $result = $this->apiSendMessage($chatid, $message, $keyboard, $replyToMsgId);
        } else {
            $messageSize = mb_strlen($message, 'UTF-8');
            if ($messageSize > self::MESSAGE_LIMIT) {
                $messageSplit = $this->splitMessage($message);
                if (!empty($messageSplit)) {
                    foreach ($messageSplit as $io => $eachMessagePart) {
                        $result = $this->apiSendMessage($chatid, $eachMessagePart, $keyboard, $replyToMsgId);
                    }
                }
            } else {
                $result = $this->apiSendMessage($chatid, $message, $keyboard, $replyToMsgId);
            }
        }
        return ($result);
    }

    /**
     * Sends message to some chat id via Telegram API
     * 
     * @param int $chatid remote chatId
     * @param string $message text message to send
     * @param array $keyboard keyboard encoded with makeKeyboard method
     * @param int $replyToMsgId optional message ID which is reply for
     * @throws Exception
     * 
     * @return string/bool
     */
    protected function apiSendMessage($chatid, $message, $keyboard = array(), $replyToMsgId = '') {
        $result = '';
        $data['chat_id'] = $chatid;
        $data['text'] = $message;

        if ($this->debug) {
            debarr($data);
        }


        //default sending method
        $method = 'sendMessage';

        //setting optional replied message ID for normal messages
        if ($replyToMsgId) {
            $method = 'sendMessage?reply_to_message_id=' . $replyToMsgId;
        }

        //location sending
        if (ispos($message, 'sendLocation:')) {
            $cleanGeo = str_replace('sendLocation:', '', $message);
            $cleanGeo = explode(',', $cleanGeo);
            $geoLat = trim($cleanGeo[0]);
            $geoLon = trim($cleanGeo[1]);
            $locationParams = '?chat_id=' . $chatid . '&latitude=' . $geoLat . '&longitude=' . $geoLon;
            if ($replyToMsgId) {
                $locationParams .= '&reply_to_message_id=' . $replyToMsgId;
            }
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
                if ($replyToMsgId) {
                    $method .= '&reply_to_message_id=' . $replyToMsgId;
                }
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
            if ($replyToMsgId) {
                $locationParams .= '&reply_to_message_id=' . $replyToMsgId;
            }
            $method = 'sendVenue' . $locationParams;
        }

        //photo sending
        if (ispos($message, 'sendPhoto:')) {
            if (preg_match('!\[(.*?)\]!si', $message, $tmpPhoto)) {
                $cleanPhoto = $tmpPhoto[1];
            }

            if (preg_match('!\{(.*?)\}!si', $message, $tmpCaption)) {
                $cleanCaption = $tmpCaption[1];
                $cleanCaption = urlencode($cleanCaption);
            }

            $photoParams = '?chat_id=' . $chatid . '&photo=' . $cleanPhoto;
            if (!empty($cleanCaption)) {
                $photoParams .= '&caption=' . $cleanCaption;
            }

            if ($replyToMsgId) {
                $photoParams .= '&reply_to_message_id=' . $replyToMsgId;
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

                if ($keyboard['type'] == 'inline') {
                    $encodedKeyboard = json_encode(array('inline_keyboard' => $keyboard['markup']));
                    $data['reply_markup'] = $encodedKeyboard;
                    $data['parse_mode'] = 'HTML';
                }

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

        //banChatMember
        if (ispos($message, 'banChatMember:')) {
            if (preg_match('!\[(.*?)\]!si', $message, $tmpBanString)) {
                $cleanBanString = explode('@', $tmpBanString[1]);
                $banUserId = $cleanBanString[0];
                $banChatId = $cleanBanString[1];
            }

            $banParams = '?chat_id=' . $banChatId . '&user_id=' . $banUserId;
            $method = 'banChatMember' . $banParams;
        }

        //unbanChatMember
        if (ispos($message, 'unbanChatMember:')) {
            if (preg_match('!\[(.*?)\]!si', $message, $tmpUnbanString)) {
                $cleanUnbanString = explode('@', $tmpUnbanString[1]);
                $unbanUserId = $cleanUnbanString[0];
                $unbanChatId = $cleanUnbanString[1];
            }

            $unbanParams = '?chat_id=' . $unbanChatId . '&user_id=' . $unbanUserId;
            $method = 'unbanChatMember' . $unbanParams;
        }

        //deleting message by its id
        if (ispos($message, 'removeChatMessage:')) {
            if (preg_match('!\[(.*?)\]!si', $message, $tmpRemoveString)) {
                $cleanRemoveString = explode('@', $tmpRemoveString[1]);
                $removeMessageId = $cleanRemoveString[0];
                $removeChatId = $cleanRemoveString[1];
                $removeParams = '?chat_id=' . $removeChatId . '&message_id=' . $removeMessageId;
                $method = 'deleteMessage' . $removeParams;
            }
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
        return ($result);
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
        return ($result);
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

        return ($result);
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
        if (!empty($this->botToken) and (!empty($chatId))) {
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

        return ($result);
    }

    /**
     * Returns file path by its file ID
     * 
     * @param string $fileId
     * 
     * @return string
     */
    public function getFilePath($fileId) {
        $result = '';
        if (!empty($this->botToken)) {
            $method = 'getFile';
            $url = $this->apiUrl . $this->botToken . '/' . $method . '?file_id=' . $fileId;
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
                if (@$result['ok']) {
                    //we got it!
                    $result = $result['result']['file_path'];
                } else {
                    //something went wrong
                    $result = '';
                }
            }
        }
        return ($result);
    }

    /**
     * Returns some file content
     * 
     * @param string $filePath
     * 
     * @return mixed
     */
    public function downloadFile($filePath) {
        $result = '';
        if (!empty($this->botToken)) {
            $cleanApiUrl = str_replace('bot', '', $this->apiUrl);
            $url = $cleanApiUrl . 'file/bot' . $this->botToken . '/' . $filePath;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            $result = curl_exec($ch);
            curl_close($ch);
        }
        return ($result);
    }

    /**
     * Returns preprocessed message in standard, fixed fields format
     * 
     * @param array $messageData
     * @param bool $isChannel
     * 
     * @return array
     */
    protected function preprocessMessageData($messageData, $isChannel = false) {
        $result = array();
        $result['message_id'] = $messageData['message_id'];

        if (!$isChannel) {
            //normal messages/groups
            $result['from']['id'] = $messageData['from']['id'];
            $result['from']['first_name'] = $messageData['from']['first_name'];
            @$result['from']['username'] = $messageData['from']['username'];
            @$result['from']['language_code'] = $messageData['from']['language_code'];
        } else {
            //channel posts
            $result['from']['id'] = $messageData['sender_chat']['id'];
            $result['from']['first_name'] = $messageData['sender_chat']['title'];
            @$result['from']['username'] = $messageData['sender_chat']['username'];
            @$result['from']['language_code'] = '';
        }
        $result['chat']['id'] = $messageData['chat']['id'];
        $result['date'] = $messageData['date'];
        $result['chat']['type'] = $messageData['chat']['type'];
        @$result['text'] = $messageData['text'];
        @$result['photo'] = $messageData['photo'];
        @$result['document'] = $messageData['document'];
        //photos and documents have only caption
        if (!empty($result['photo']) or ! empty($result['document'])) {
            @$result['text'] = $messageData['caption'];
        }
        @$result['voice'] = $messageData['voice'];
        @$result['audio'] = $messageData['audio'];
        @$result['video_note'] = $messageData['video_note'];
        @$result['location'] = $messageData['location'];
        @$result['sticker'] = $messageData['sticker'];
        @$result['new_chat_member'] = $messageData['new_chat_member'];
        @$result['new_chat_members'] = $messageData['new_chat_members'];
        @$result['new_chat_participant'] = $messageData['new_chat_participant'];
        @$result['left_chat_member'] = $messageData['left_chat_member'];
        @$result['left_chat_participant'] = $messageData['left_chat_participant'];
        @$result['reply_to_message'] = $messageData['reply_to_message'];
        //decode replied message too if received
        if ($result['reply_to_message']) {
            $result['reply_to_message'] = $this->preprocessMessageData($result['reply_to_message']);
        }

        //Uncomment following for total debug
        //@$result['rawMessageData'] = $messageData;

        return ($result);
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
                        $result = $this->preprocessMessageData($postRaw['message']);
                    }
                } else {
                    if (isset($postRaw['channel_post'])) {
                        $result = $this->preprocessMessageData($postRaw['channel_post'], true);
                    }
                }
            } else {
                $result = $postRaw;
            }
        }

        return ($result);
    }

    /**
     * Sends an action to a chat using the Telegram API.
     *
     * @param string $chatid The ID of the chat.
     * @param string $action The action to be sent. Like "typing".
     *
     * @return string The result of the API request.
     * @throws Exception If the bot token is empty.
     */
    public function apiSendAction($chatid, $action) {
        $result = '';
        $method = 'sendChatAction';
        $data['chat_id'] = $chatid;
        $data['action'] = $action;
        if ($this->debug) {
            debarr($data);
        }

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
        return ($result);
    }
}
