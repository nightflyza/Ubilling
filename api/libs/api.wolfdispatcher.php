<?php

/**
 * Universal Telegram bot hooks processing extendable class
 */
class WolfDispatcher {

    /**
     * Contains current instance bot token
     *
     * @var string
     */
    protected $botToken = '';

    /**
     * Contains text commands=>actions mappings
     *
     * @var array
     */
    protected $commands = array();

    /**
     * Group chats commands array which overrides normal actions only for group chats
     *
     * @var array
     */
    protected $groupChatCommands = array();

    /**
     * Chats commands array which required user set in adminChatIds struct to be executed.
     *
     * @var array
     */
    protected $adminCommands = array();

    /**
     * Contains text reactions=>actions mappings
     *
     * @var array
     */
    protected $textReacts = array();

    /**
     * Array of chatIds which is denied for any actions performing
     *
     * @var array
     */
    protected $ignoredChatIds = array();

    /**
     * Contains administrator users chatIds as chatId=>index
     *
     * @var array
     */
    protected $adminChatIds = array();

    /**
     * Array of chatIds which is allowed for actions execution. Ignored if empty.
     *
     * @var array
     */
    protected $allowedChatIds = array();

    /**
     * Telegram interraction layer object placeholder
     *
     * @var object
     */
    protected $telegram = '';

    /**
     * Input data storage
     *
     * @var array
     */
    protected $receivedData = array();

    /**
     * Current conversation client chatId
     *
     * @var int
     */
    protected $chatId = 0;

    /**
     * Current conversation latest messageId
     *
     * @var int
     */
    protected $messageId = 0;

    /**
     * Current converstation chat type, like private,group 
     *
     * @var string
     */
    protected $chatType = '';

    /**
     * Method name which will be executed on any callback query receive
     * 
     * @var string
     */
    protected $callbackQueryMethod = '';

    /**
     * Method name which will be executed on any image receive
     *
     * @var string
     */
    protected $photoHandleMethod = '';

    /**
     * Method name which will be executed if any new chat member appears
     *
     * @var string
     */
    protected $chatMemberAppearMethod = '';

    /**
     * Method name which will be executed if any new chat member left chat
     *
     * @var string
     */
    protected $chatMemberLeftMethod = '';

    /**
     * Dispatcher debugging flag
     * 
     * @var bool
     */
    protected $debugFlag = false;

    /**
     * Contains all called actions/methods
     *
     * @var string
     */
    protected $calledActions = array();

    /**
     * Contains current bot instance class name as is 
     *
     * @var string
     */
    protected $botImplementation = '';

    /**
     * Web-hook automatic installation flag
     *
     * @var bool
     */
    protected $hookAutoSetup = false;

    /**
     * Hook allowed updates array
     *
     * @var array
     */
    protected $allowedUpdates = array();

    /**
     * Contains default webhook maxConnections value
     *
     * @var int
     */
    protected $maxConnections = 100;

    /**
     * Contains default debug log path
     */
    const LOG_PATH = 'exports/';

    /**
     * Contains path to save web hooks PIDs due autosetup.
     */
    const HOOK_PID_PATH = 'exports/';

    /**
     * Creates new dispatcher instance
     * 
     * @param string $token
     * 
     * @return void
     */
    public function __construct($token) {
        if (!empty($token)) {
            $this->botToken = $token;
        }

        //                                ,     ,
        //                                |\---/|
        //                               /  , , |
        //                          __.-'|  / \ /
        //                 __ ___.-'        ._O|
        //              .-'  '        :      _/
        //             / ,    .        .     |
        //            :  ;    :        :   _/
        //            |  |   .'     __:   /
        //            |  :   /'----'| \  |
        //            \  |\  |      | /| |
        //             '.'| /       || \ |
        //             | /|.'       '.l \\_
        //             || ||             '-'
        //             '-''-'

        $this->initTelegram();
        $this->setBotName();
    }

    /**
     * Sets current bot instance implementation property
     * 
     * @return void
     */
    protected function setBotName() {
        $this->botImplementation = get_class($this);
    }

    /**
     * Instance debugging flag setter. Debug log: exports/botname_debug.log
     * 
     * @param bool $state
     * 
     * @return void
     */
    public function setDebug($state) {
        if ($state) {
            $this->debugFlag = true;
        }
    }

    /**
     * Inits protected telegram instance
     * 
     * @throws Exception
     * 
     * @return void
     */
    protected function initTelegram() {
        if (!empty($this->botToken)) {
            if (class_exists('UbillingTelegram')) {
                $this->telegram = new UbillingTelegram($this->botToken);
            } else {
                if (class_exists('WolfGram')) {
                    $this->telegram = new WolfGram($this->botToken);
                }
            }

            if (empty($this->telegram)) {
                throw new Exception('EX_NO_TELEGRAM_LIB');
            }
        } else {
            throw new Exception('EX_EMPTY_TOKEN');
        }
    }

    /**
     * Sets new dispatcher actions dataset
     * 
     * @param array $commands dataset as text input=>method or function name
     * 
     * @return void
     */
    public function setActions($commands) {
        if (!empty($commands)) {
            if (is_array($commands)) {
                $this->commands = $commands;
            }
        }
    }

    /**
     * Sets group commands data set
     * If not empty data set its overrides all default actions for not private chats
     * 
     * @param array $groupCommands dataset as text input=>method or function name
     * 
     * @return void
     */
    public function setGroupActions($groupCommands) {
        $this->groupChatCommands = $groupCommands;
    }

    /**
     * Sets admin commands data set. This actions requires user to be isAdmin() for execution
     * 
     * @param array $adminCommands dataset as text input=>method or function name
     * 
     * @return void
     */
    public function setAdminActions($adminCommands) {
        $this->adminCommands = $adminCommands;
    }

    /**
     * Sets administrative user chatIDs
     * 
     * @param array $chatIds just array of chatids of administrative users like array('111111','222222')
     * 
     * @return void
     */
    public function setAdminChatId($chatIds) {
        if (!empty($chatIds)) {
            $chatIds = array_flip($chatIds);
            $this->adminChatIds = $chatIds;
        }
    }

    /**
     * Sets new dispatcher text reactions dataset. Basic setActions dataset overrides this.
     * 
     * @param array $commands dataset as text input=>method or function name
     * 
     * @return void
     */
    public function setTextReactions($commands) {
        if (!empty($commands)) {
            if (is_array($commands)) {
                $this->textReacts = $commands;
            }
        }
    }

    /**
     * Sets method name which will be executed on any callback query received
     * 
     * @param string $method
     * 
     * @return void
     */
    public function setCallbackQueryHandler($method) {
        if (!empty($method)) {
            $this->callbackQueryMethod = $method;
        }
    }

    /**
     * Sets method name which will be executed on any image input
     * 
     * @param string $name existing method name to process received images
     * 
     * @return void
     */
    public function setPhotoHandler($name) {
        if (!empty($name)) {
            $this->photoHandleMethod = $name;
        }
    }

    /**
     * Sets method names which will be executed if some member appears or left chat
     * 
     * @param string $methodAppear
     * @param string $methodLeft
     * 
     * @return void
     */
    public function setOnChatMemberActions($methodAppear = '', $methodLeft = '') {
        if (!empty($methodAppear)) {
            $this->chatMemberAppearMethod = $methodAppear;
        }

        if (!empty($methodLeft)) {
            $this->chatMemberLeftMethod = $methodLeft;
        }
    }

    /**
     * Sets allowed hook updates list. Example: array('update_id', 'message', 'chat_member')
     * 
     * https://core.telegram.org/bots/api#update
     * https://core.telegram.org/bots/api#setwebhook
     *
     * @param array $allowedHookUpdates
     * 
     * @return void
     */
    public function setAllowedUpdates($allowedHookUpdates = array()) {
        $this->allowedUpdates = $allowedHookUpdates;
    }

    /**
     * Sets hook max connections limit
     *
     * @param int $limit
     * 
     * @return void
     */
    public function setMaxConnections($limit = 100) {
        $this->maxConnections = $limit;
    }

    /**
     * Sets allowed chat IDs for this instance
     * 
     * @param array $chatIds chatIds which only allowed to interract this bot instance just like array('1234','4321')
     * 
     * @return void
     */
    public function setAllowedChatIds($chatIds) {
        if (!empty($chatIds)) {
            if (is_array($chatIds)) {
                $this->allowedChatIds = array_flip($chatIds);
            }
        }
    }

    /**
     * Sets denied chat IDs for this instance
     * 
     * @param array $chatIds chatIds which is denied from interraction with this instance just like array('1234','4321')
     * 
     * @return void
     */
    public function setIgnoredChatIds($chatIds) {
        if (!empty($chatIds)) {
            if (is_array($chatIds)) {
                $this->ignoredChatIds = array_flip($chatIds);
            }
        }
    }

    /**
     * Getting current input text action name if it exists
     * 
     * @return string
     */
    protected function getTextAction() {
        $result = '';
        //basic commands processing
        if (!empty($this->commands)) {
            foreach ($this->commands as $eachCommand => $eachAction) {
                if (mb_stripos($this->receivedData['text'], $eachCommand, 0, 'UTF-8') !== false) {
                    $result = $eachAction;
                }
            }
        }

        //administrative commands processing
        if (!empty($this->adminCommands)) {
            if ($this->isAdmin()) {
                foreach ($this->adminCommands as $eachCommand => $eachAction) {
                    if (mb_stripos($this->receivedData['text'], $eachCommand, 0, 'UTF-8') !== false) {
                        $result = $eachAction;
                    }
                }
            }
        }

        //text reactions if no of main commands detected
        if (empty($result)) {
            if (!empty($this->textReacts)) {
                foreach ($this->textReacts as $eachTextReact => $eachAction) {
                    if (mb_stripos($this->receivedData['text'], $eachTextReact, 0, 'UTF-8') !== false) {
                        $result = $eachAction;
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Performs run of some action into current dispatcher instance
     * 
     * @param string $actionName
     * 
     * @return void
     */
    protected function runAction($actionName) {
        if (!empty($actionName)) {
            if (method_exists($this, $actionName)) {
                //class methods have priority
                $this->$actionName();
                if ($this->debugFlag) {
                    $this->calledActions[] = 'METHOD: ' . $actionName;
                }
            } else {
                if (function_exists($actionName)) {
                    $actionName($this->receivedData);
                    if ($this->debugFlag) {
                        $this->calledActions[] = 'FUNC: ' . $actionName;
                    }
                } else {
                    if ($this->debugFlag) {
                        //any command/reaction handler found
                        $chatId = $this->receivedData['chat']['id'];
                        $message = __('Any existing function on method named') . ' `' . $actionName . '` ' . __('not found by dispatcher');
                        $this->telegram->directPushMessage($chatId, $message);
                        if ($this->debugFlag) {
                            $this->calledActions[] = 'FAILED: ' . $actionName;
                        }
                    }
                }
            }
        }
    }

    /**
     * Run some actions on non empty input data received
     * 
     * @return void
     */
    protected function reactInput() {
        $currentInputAction = '';
        if (!empty($this->receivedData)) {
            if (isset($this->receivedData['from']) and isset($this->receivedData['chat'])) {
                $chatId = $this->receivedData['chat']['id']; //yeah, we waiting for preprocessed data here
                $interractionAllowed = true;
                //separate allows here
                if (!empty($this->allowedChatIds)) {
                    if (!isset($this->allowedChatIds[$chatId])) {
                        $interractionAllowed = false;
                    }
                }
                //something like ban list
                if (isset($this->ignoredChatIds[$chatId])) {
                    $interractionAllowed = false;
                }

                if ($interractionAllowed) {
                    //interraction with this chat id is allowed
                    if (!empty($this->receivedData['text'])) {
                        $currentInputAction = $this->getTextAction();
                        if (!empty($currentInputAction)) {
                            $this->runAction($currentInputAction);
                        } else {
                            //empty actions here. No of existing commands or reactions found here.
                            $this->handleEmptyAction();
                        }
                    } else {
                        //empty text actions here
                        $this->handleEmptyText();
                    }


                    //this method will be executed on image receive if set
                    if (!empty($this->photoHandleMethod)) {
                        if ($this->isPhotoReceived()) {
                            $this->runAction($this->photoHandleMethod);
                        }
                    }

                    //following methods will be executed while new member appears in chat or lefts the chat
                    if (!empty($this->chatMemberAppearMethod)) {
                        if ($this->isNewChatMemberAppear()) {
                            $this->runAction($this->chatMemberAppearMethod);
                        }
                    }

                    if (!empty($this->chatMemberLeftMethod)) {
                        if ($this->isChatMemberLeft()) {
                            $this->runAction($this->chatMemberLeftMethod);
                        }
                    }

                    //this will be executed if some image received anyway
                    if ($this->isPhotoReceived()) {
                        $this->handlePhotoReceived();
                    }
                }
            }

            //callback query processing
            if (!empty($this->callbackQueryMethod)) {
                if ($this->isCallbackQueryReceived()) {
                    $this->runAction($this->callbackQueryMethod);
                }
            }

            //this will be executed while any callback query received
            if ($this->isCallbackQueryReceived()) {
                $this->handleCallbackQuery();
            }

            //this shall be executed on any non empty data recieve
            $this->handleAnyWay();
        }
    }

    /**
     * Dummy method which will be executed on receive empty text actions on listener
     * 
     * @return void
     */
    protected function handleEmptyAction() {
        //will be executed on messages with no detected action for message
    }

    /**
     * Dummy method which will be executed on receive empty text actions on listener
     * 
     * @return void
     */
    protected function handleEmptyText() {
        //will be executed on messages with empty text field
    }

    /**
     * Dummy method which will be executed on receive any non empty data on listener
     * 
     * @return void
     */
    protected function handleAnyWay() {
        //will be executed on eny non empty received data
    }

    /**
     * Dummy method which will be executed on receive any image file
     * 
     * @return void
     */
    protected function handlePhotoReceived() {
        //will be executed if any image received
    }

    /**
     * Dummy method which will be executed on receive any callback query
     * 
     * @return void
     */
    protected function handleCallbackQuery() {
        //will be executed if any callback query received
    }

    /**
     * Writes debug data to separate per-class log if debugging flag enabled.
     * 
     * @global int $starttime
     * @global int $query_counter
     *      
     * @return void
     */
    protected function writeDebugLog() {
        global $starttime, $query_counter;
        if ($this->debugFlag) {
            $nowmtime = explode(' ', microtime());
            $wtotaltime = $nowmtime[0] + $nowmtime[1] - $starttime;
            $logData = $this->botImplementation . ': ' . curdatetime() . PHP_EOL;
            $logData .= print_r($this->receivedData, true) . PHP_EOL;
            $logData .= 'GT: ' . round($wtotaltime, 4) . ' QC: ' . $query_counter . PHP_EOL;
            if (!empty($this->calledActions)) {
                $logData .= PHP_EOL . 'Called actions: ' . print_r($this->calledActions, true) . PHP_EOL;
            } else {
                $logData .= PHP_EOL . 'Called actions: NONE' . PHP_EOL;
            }
            $logData .= '==================' . PHP_EOL;
            $logFileName = strtolower($this->botImplementation) . '_debug.log';
            file_put_contents(self::LOG_PATH . $logFileName, $logData, FILE_APPEND);
        }
    }

    /**
     * Listens for some events
     * 
     * @return array
     */
    public function listen() {
        //may be automatic setup required?
        if ($this->hookAutoSetup) {
            $this->installWebHook();
        }
        //is something here?
        $this->receivedData = $this->telegram->getHookData();
        if (!empty($this->receivedData)) {
            @$this->chatId = $this->receivedData['chat']['id'];
            @$this->messageId = $this->receivedData['message_id'];
            @$this->chatType = $this->receivedData['chat']['type'];
            //wow, some separate group commands here. They overrides all another actions.
            if (!empty($this->groupChatCommands)) {
                if ($this->chatType != 'private') {
                    //override actions with another group set
                    $this->setActions($this->groupChatCommands);
                }
            }
            $this->reactInput();
        }
        $this->writeDebugLog();
        return ($this->receivedData);
    }

    /**
     * Checks is any callback query received?
     * 
     * @return array|bool
     */
    protected function isCallbackQueryReceived() {
        $result = false;
        if (isset($this->receivedData['callback_query'])) {
            $result = $this->receivedData;
        }
        return ($result);
    }

    /**
     * Checks is any image received?
     * 
     * @return bool
     */
    protected function isPhotoReceived() {
        $result = false;
        if ($this->receivedData['photo'] or $this->receivedData['document']) {
            $imageMimeTypes = array('image/png', 'image/jpeg');
            if ($this->receivedData['photo']) {
                $result = true;
            } else {
                if ($this->receivedData['document']) {
                    $imageMimeTypes = array_flip($imageMimeTypes);
                    if (isset($imageMimeTypes[$this->receivedData['document']['mime_type']])) {
                        $result = true;
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Checks is new chat member appeared in chat? Returns his data on this event.
     * 
     * Data fields: 
     *   id, is_bot, first_name, username, language_code, is_premium - normal users
     *   id, is_bot, first_name, username - bots
     * 
     * @return array|bool
     */
    protected function isNewChatMemberAppear() {
        $result = false;
        if (@$this->receivedData['new_chat_member']) {
            $result = $this->receivedData['new_chat_member'];
        }
        return ($result);
    }

    /**
     * Checks is any chat member lefts chat? Returns his chatId on this event.
     * Data fields: 
     *   id, is_bot, first_name, username, language_code, is_premium - normal users
     *   id, is_bot, first_name, username - bots
     * 
     * @return array|bool
     */
    protected function isChatMemberLeft() {
        $result = false;
        if ($this->receivedData['left_chat_member']) {
            $result = $this->receivedData['left_chat_member'];
        }
        return ($result);
    }

    /**
     * Checks if a new chat member has appeared in a group chat.
     *
     * This method inspects the received data to determine if a new chat member
     * has joined a group chat. It returns the received data if the new chat member's
     * status is 'member' and the chat ID is negative (indicating a group chat).
     *
     * @return array|bool Returns the received data if a new chat member has appeared in a group chat, otherwise false.
     */
    protected function isGroupMemberAppear() {
        $result = false;
        //chat_member object update
        if (isset($this->receivedData['chat_member'])) {
            $data = $this->receivedData['chat_member'];
            if ($data['new_chat_member']['status'] == 'member') {
                if ($data['chat']['id'] < 0) {
                    $result = $this->receivedData;
                }
            }
        }
        return ($result);
    }


    /**
     * Checks if a group member has left the chat by the own will
     *
     * This method inspects the received data to determine if a chat member's status
     * has changed to 'left' in a group chat. If the status is 'left' and the chat ID
     * is less than 0 (indicating a group chat), it returns the received data.
     *
     * @return array|false Returns the received data if a group member has left, otherwise false.
     */
    protected function isGroupMemberLeft() {
        $result = false;
        //chat_member object update
        if (isset($this->receivedData['chat_member'])) {
            $data = $this->receivedData['chat_member'];
            if ($data['new_chat_member']['status'] == 'left' and $data['old_chat_member']['status'] == 'member') {
                if ($data['chat']['id'] < 0) {
                    $result = $this->receivedData;
                }
            }
        }
        return ($result);
    }
    /**
     * Checks if a group member has been kicked/banned from the chat.
     *
     * This method inspects the received data to determine if a member's status
     * has been updated to 'kicked' in a group chat. If the member has been kicked
     * and the chat ID is negative (indicating a group chat), it returns the
     * received data.
     *
     * @return array|false Returns the received data if a member has been kicked
     *                     from a group chat, otherwise returns false.
     */
    protected function isGroupMemberBanned() {
        $result = false;
        //chat_member object update
        if (isset($this->receivedData['chat_member'])) {
            $data = $this->receivedData['chat_member'];
            if ($data['new_chat_member']['status'] == 'kicked' and ($data['old_chat_member']['status'] == 'member' or $data['old_chat_member']['status'] == 'left')) {
                if ($data['chat']['id'] < 0) {
                    $result = $this->receivedData;
                }
            }
        }
        return ($result);
    }

    /**
     * Checks if a group member has been unbanned.
     *
     * This method verifies if the received data indicates that a member who was previously kicked
     * from the group has now left the group, which implies they have been unbanned.
     *
     * @return mixed Returns the received data if the member has been unbanned, otherwise false.
     */
    protected function isGroupMemberUnbanned() {
        $result = false;
        if (isset($this->receivedData['chat_member'])) {
            $data = $this->receivedData['chat_member'];
            if ($data['new_chat_member']['status'] == 'left' and $data['old_chat_member']['status'] == 'kicked') {
                if ($data['chat']['id'] < 0) {
                    $result = $this->receivedData;
                }
            }
        }
        return ($result);
    }

    /**
     * Checks is current user chatId listed as administrator? 
     * 
     * @return bool
     */
    protected function isAdmin() {
        $result = false;
        if (!empty($this->adminChatIds)) {
            //direct message in private chat?
            if (isset($this->adminChatIds[$this->chatId])) {
                $result = true;
            } else {
                //may be group chat message from admin user?
                if (isset($this->adminChatIds[$this->receivedData['from']['id']])) {
                    $result = true;
                }
            }
        }
        return ($result);
    }

    /**
     * Returns current received message as receivedData struct
     * 
     * @return array
     */
    protected function message() {
        return ($this->receivedData);
    }

    /**
     * Returns received image file content
     * 
     * @return mixed
     */
    protected function getPhoto() {
        $result = '';
        $filePath = '';
        $fileId = '';
        $imageMimeTypes = array('image/png', 'image/jpeg');
        //normal compressed image
        if ($this->receivedData['photo']) {
            $maxSizeFile = end($this->receivedData['photo']);
            $fileId = $maxSizeFile['file_id'];
        } else {
            //image received as-is without compression
            $imageMimeTypes = array_flip($imageMimeTypes);
            if ($this->receivedData['document']) {
                if (isset($imageMimeTypes[$this->receivedData['document']['mime_type']])) {
                    $fileId = $this->receivedData['document']['file_id'];
                }
            }
        }

        //downloading remote file
        if ($fileId) {
            $filePath = $this->telegram->getFilePath($fileId);
            if ($filePath) {
                $result = $this->telegram->downloadFile($filePath);
            }
        }
        return ($result);
    }

    /**
     * Saves received photo to the specified path on filesystem. Returns filepath on success.
     * 
     * @param string $savePath
     * 
     * @return string|void
     */
    protected function savePhoto($savePath) {
        $result = '';
        if (!empty($savePath)) {
            if ($this->isPhotoReceived()) {
                $receivedPhoto = $this->getPhoto();
                if ($receivedPhoto) {
                    file_put_contents($savePath, $receivedPhoto);
                    if (file_exists($savePath)) {
                        $result = $savePath;
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Sends fast reply to current chat member
     * 
     * @param string $message
     * @param array $keyboard
     * 
     * @return string|bool
     */
    protected function reply($message = '', $keyboard = array()) {
        $result = '';
        if (!empty($message)) {
            $replyResult = $this->telegram->directPushMessage($this->chatId, $message, $keyboard);
            if ($replyResult) {
                $result = json_decode($replyResult, true);
            }
        }
        return ($result);
    }

    /**
     * Sends fast reply to current chat member for latest message or some specified messageId
     * 
     * @param string $message
     * @param array $keyboard
     * @param int $replyToMsg
     * 
     * @return string|bool
     */
    protected function replyTo($message = '', $keyboard = array(), $replyToMsg = '') {
        $result = '';
        if (!empty($message)) {
            if (empty($replyToMsg)) {
                $replyToMsg = $this->messageId;
            }
            $replyResult = $this->telegram->directPushMessage($this->chatId, $message, $keyboard, false, $replyToMsg);
            if ($replyResult) {
                $result = json_decode($replyResult, true);
            }
        }
        return ($result);
    }

    /**
     * Universal method for sending different types of media and files to chat.
     * 
     * Usage examples:
     *   $this->sendMedia('photo', 'https://example.com/image.jpg', 'Photo caption');
     *   $this->sendMedia('video', 'https://example.com/video.mp4', 'Video description');
     *   $this->sendMedia('audio', 'https://example.com/audio.mp3', 'Song title');
     *   $this->sendMedia('document', 'https://example.com/file.pdf', 'Document name');
     *   $this->sendMedia('location', '48.253449, 24.926184');
     *   $this->sendMedia('venue', '48.253449, 24.926184', '', '', '', array('address' => 'Mountain', 'title' => 'Jesus lives here'));
     *
     * @param string $type media type: photo, video, audio, document, location, venue
     * @param string $urlOrData media URL or data
     * @param string $caption media caption
     * @param int $chatId chat ID (optional, default is current chat ID)
     * @param int $replyToMsgId reply to message ID 
     * @param array $additionalData additional data for venue (optional, default is empty array)
     *
     * @return array
     */
    protected function sendMedia($type, $urlOrData, $caption = '', $chatId = '', $replyToMsgId = '', $additionalData = array()) {
        $result = array();
        $message = '';
        if (empty($chatId)) {
            $chatId = $this->chatId;
        }

        $allowedTypes = array('photo', 'video', 'audio', 'document', 'location', 'venue');
        if (!in_array($type, $allowedTypes)) {
            return ($result);
        }

        switch ($type) {
            case 'photo':
            case 'video':
            case 'audio':
            case 'document':
                if (!empty($urlOrData)) {
                    $captionPart = !empty($caption) ? '{' . $caption . '}' : '';
                    $methodName = 'send' . ucfirst($type);
                    $message = $methodName . ':[' . $urlOrData . ']' . $captionPart;
                }
                break;

            case 'location':
                if (!empty($urlOrData)) {
                    $message = 'sendLocation:' . $urlOrData;
                }
                break;

            case 'venue':
                if (!empty($urlOrData) and isset($additionalData['address']) and isset($additionalData['title'])) {
                    $message = 'sendVenue:[' . $urlOrData . '](' . $additionalData['address'] . '){' . $additionalData['title'] . '}';
                }
                break;
        }

        if (!empty($message)) {
            $sendResult = $this->telegram->directPushMessage($chatId, $message, array(), false, $replyToMsgId);
            if ($sendResult) {
                $result = json_decode($sendResult, true);
            }
        }

        return ($result);
    }

    /**
     * Removes a chat message by its ID from a specified chat.
     *
     * @param int $messageId The ID of the message to be removed.
     * @param int $chatId The ID of the chat from which the message will be removed.
     * 
     * @return array
     */
    protected function removeChatMessage($messageId, $chatId) {
        $result = array();
        $deleteResult = $this->telegram->directPushMessage($chatId, 'removeChatMessage:[' . $messageId . '@' . $chatId . ']');
        if ($deleteResult) {
            $result = json_decode($deleteResult, true);
        }
        return ($result);
    }

    /**
     * Edits a message by its ID in a specified chat.
     *
     * @param string $message The new text of the message.
     * @param int $chatId The ID of the chat in which the message will be edited.
     * @param int $messageId The ID of the message to be edited.
     * 
     * @return array
     */
    protected function editMessageText($messageId, $chatId, $messageText) {
        $result = array();
        $editResult = $this->telegram->directPushMessage($chatId, 'editMessageText:[' . $messageId . '@' . $chatId . ']' . $messageText);
        if ($editResult) {
            $result = json_decode($editResult, true);
        }
        return ($result);
    }

    /**
     * Pins a message by its ID in a specified chat.
     *
     * @param int $messageId
     * @param int $chatId
     * @param bool $disableNotification
     *
     * @return array
     */
    protected function pinChatMessage($messageId, $chatId, $disableNotification = false) {
        $result = array();
        $disableFlag = $disableNotification ? '@1' : '';
        $pinResult = $this->telegram->directPushMessage($chatId, 'pinChatMessage:[' . $messageId . '@' . $chatId . $disableFlag . ']');
        if ($pinResult) {
            $result = json_decode($pinResult, true);
        }
        return ($result);
    }

    /**
     * Unpins a message by its ID in a specified chat or unpins all messages if messageId is empty.
     *
     * @param int $chatId
     * @param int $messageId
     *
     * @return array
     */
    protected function unpinChatMessage($chatId, $messageId = '') {
        $result = array();
        if (empty($messageId)) {
            $unpinResult = $this->telegram->directPushMessage($chatId, 'unpinChatMessage:[' . $chatId . ']');
        } else {
            $unpinResult = $this->telegram->directPushMessage($chatId, 'unpinChatMessage:[' . $messageId . '@' . $chatId . ']');
        }
        if ($unpinResult) {
            $result = json_decode($unpinResult, true);
        }
        return ($result);
    }

    
    

    /**
     * Bans a member from a specified group.
     *
     * This function sends a direct push message to the Telegram API to ban a member from a group.
     *
     * @param int $memberId The ID of the member to be banned.
     * @param int $groupId The ID of the group from which the member will be banned.
     * 
     * @return array
     */
    protected function banGroupMember($memberId, $groupId) {
        $result = array();
        $banResult = $this->telegram->directPushMessage($groupId, 'banChatMember:[' . $memberId . '@' . $groupId . ']');
        if ($banResult) {
            $result = json_decode($banResult, true);
        }
        return ($result);
    }

    /**
     * Unbans a member from a group.
     *
     * This method sends a direct push message to unban a member from a specified group using the Telegram API.
     *
     * @param int $memberId The ID of the member to be unbanned.
     * @param int $groupId The ID of the group from which the member will be unbanned.
     * 
     * @return array 
     */
    protected function unbanGroupMember($memberId, $groupId) {
        $result = array();
        $unbanResult = $this->telegram->directPushMessage($groupId, 'unbanChatMember:[' . $memberId . '@' . $groupId . ']');
        if ($unbanResult) {
            $result = json_decode($unbanResult, true);
        }
        return ($result);
    }

    /**
     * Rearranges flat buttons array into 2D array with specified buttons per row
     * 
     * @param array $buttonsArray flat buttons array
     * @param int $inRow buttons per row
     * 
     * @return array
     */
    protected function rearrangeButtons($buttonsArray, $inRow = 2) {
        $result = array();
        if (!empty($buttonsArray)) {
            $result = array_chunk($buttonsArray, $inRow);
        }
        return ($result);
    }

    /**
     * Sends some keyboard to current chat
     * 
     * @param array $buttons
     * @param string $text
     * 
     * @return void
     */
    protected function castKeyboard($buttons, $text = '⌨️', $inline = false) {
        if (!empty($buttons)) {
            $keyboard = $this->telegram->makeKeyboard($buttons, $inline);
            $this->reply($text, $keyboard);
        }
    }


    /**
     * Confirms a current callback query with some text and optional show alert flag
     * 
     * @param string $text text to show in callback query balloon or alert text
     * @param bool $showAlert show alert flag
     * 
     * @return void
     */
    protected function confirmCallbackQuery($text = '', $showAlert = false) {
        if (!empty($this->receivedData['callback_query']['id'])) {
            $this->telegram->answerCallbackQuery($this->receivedData['callback_query']['id'], $text, $showAlert);
        }
    }

    /**
     * Enables or disables web-hook automatic installation
     * 
     * @param bool $enabled is web-hook autosetup enabled?
     * 
     * @return void
     */
    public function hookAutosetup($enabled = true) {
        $this->hookAutoSetup = $enabled;
    }

    /**
     * Automatically registers new web-hook URL for bot if isnt registered yet.
     * 
     * @param string $customUrl custom web-hook listener URL
     *
     * @return string
     */
    public function installWebHook($customUrl = '') {
        $result = '';
        if (empty($customUrl)) {
            //automatic current listener URL detection
            $listenerUrl = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        } else {
            //overriding with explict custom URL
            $listenerUrl = $customUrl;
        }

        $tokenHash = md5($this->botToken . $listenerUrl);
        $hookPidName = self::HOOK_PID_PATH . $this->botImplementation . $tokenHash . '.hook';
        //need to be installed?
        if (!file_exists($hookPidName)) {
            $hookInfo = json_decode($this->telegram->getWebHookInfo(), true);
            $hookInfo = $hookInfo['result'];
            $hookAllowedUpdates = isset($hookInfo['allowed_updates']) ? $hookInfo['allowed_updates'] : array();

            if ($hookInfo['url'] != $listenerUrl or $hookAllowedUpdates != $this->allowedUpdates or $hookInfo['max_connections'] != $this->maxConnections) {
                //need to be installed new URL with new params
                $result = $this->telegram->setWebHook($listenerUrl, $this->maxConnections, $this->allowedUpdates);
                if (function_exists('show_success')) {
                    show_success($this->botImplementation . 'installed web-hook URL: ' . $hookInfo['url']);
                }
            } else {
                //already set, but no PID
                if (function_exists('show_warning')) {
                    show_warning($this->botImplementation . ' PID saved for web-hook URL: ' . $hookInfo['url']);
                }
            }
            //write hook pid
            file_put_contents($hookPidName, $listenerUrl);
            //some logging
            if ($this->debugFlag) {
                $logFileName = strtolower($this->botImplementation) . '_debug.log';
                $logData = $this->botImplementation . ': ' . curdatetime() . PHP_EOL;
                $logData .= 'INSTALLED WEB HOOK: ' . $listenerUrl . PHP_EOL;
                $logData .= 'HOOK PID: ' . $hookPidName . PHP_EOL;
                file_put_contents(self::LOG_PATH . $logFileName, $logData, FILE_APPEND);
            }
        } else {
            //ok, hook is already installed
            $currentHookUrl = file_get_contents($hookPidName);
            if (function_exists('show_info')) {
                show_info($this->botImplementation . ' web-hook URL: ' . $currentHookUrl);
            }
        }
        return ($result);
    }
}
