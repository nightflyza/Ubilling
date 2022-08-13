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
     * Loads required configs into protected props for further usage
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfig() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
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
            $this->telegram = new UbillingTelegram($this->botToken);
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
        return($result);
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
            if (isset($this->receivedData['from']) AND isset($this->receivedData['chat'])) {
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
        $this->installWebHook();
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
        return($this->receivedData);
    }

    /**
     * Checks is any image received?
     * 
     * @return bool
     */
    protected function isPhotoReceived() {
        $result = false;
        if ($this->receivedData['photo'] OR $this->receivedData['document']) {
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
        return($result);
    }

    /**
     * Checks is new chat member appeared in chat? Returns his data on this event.
     * Data fields: 
     *   id, is_bot, first_name, username, language_code, is_premium - normal users
     *   id, is_bot, first_name, username - bots
     * 
     * @return array/bool
     */
    protected function isNewChatMemberAppear() {
        $result = false;
        if ($this->receivedData['new_chat_member']) {
            $result = $this->receivedData['new_chat_member'];
        }
        return($result);
    }

    /**
     * Checks is any chat member lefts chat? Returns his chatId on this event.
     * Data fields: 
     *   id, is_bot, first_name, username, language_code, is_premium - normal users
     *   id, is_bot, first_name, username - bots
     * 
     * @return array/bool
     */
    protected function isChatMemberLeft() {
        $result = false;
        if ($this->receivedData['left_chat_member']) {
            $result = $this->receivedData['left_chat_member'];
        }
        return($result);
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
        return($result);
    }

    /**
     * Returns current received message as receivedData struct
     * 
     * @return array
     */
    protected function message() {
        return($this->receivedData);
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
        return($result);
    }

    /**
     * Saves received photo to the specified path on filesystem. Returns filepath on success.
     * 
     * @param string $savePath
     * 
     * @return string/void
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
        return($result);
    }

    /**
     * Sends fast reply to current chat member
     * 
     * @param string $message
     * @param array $keyboard
     * 
     * @return string/bool
     */
    protected function reply($message = '', $keyboard = array()) {
        $result = '';
        if (!empty($message)) {
            $replyResult = $this->telegram->directPushMessage($this->chatId, $message, $keyboard);
            if ($replyResult) {
                $result = json_decode($replyResult, true);
            }
        }
        return($result);
    }

    /**
     * Sends fast reply to current chat member for latest message or some specified messageId
     * 
     * @param string $message
     * @param array $keyboard
     * @param int $replyToMsg
     * 
     * @return string/bool
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
        return($result);
    }

    /**
     * Sends some keyboard to current chat
     * 
     * @param array $buttons
     * @param string $text
     * 
     * @return void
     */
    protected function castKeyboard($buttons, $text = '⌨️') {
        if (!empty($buttons)) {
            $keyboard = $this->telegram->makeKeyboard($buttons);
            $this->reply($text, $keyboard);
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
     * @return void
     */
    protected function installWebHook() {
        if ($this->hookAutoSetup) {
            $listenerUrl = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            $tokenHash = md5($this->botToken . $listenerUrl);
            $hookPidName = self::HOOK_PID_PATH . $this->botImplementation . $tokenHash . '.hook';
            //need to be installed?
            if (!file_exists($hookPidName)) {
                $hookInfo = json_decode($this->telegram->getWebHookInfo(), true);
                if ($hookInfo['result']['url'] != $listenerUrl) {
                    //need to be installed new URL
                    $this->telegram->setWebHook($listenerUrl, 100);
                    if (function_exists('show_success')) {
                        show_success($this->botImplementation . ' web-hook URL: ' . $hookInfo['result']['url']);
                    }
                } else {
                    //already set, but no PID
                    if (function_exists('show_warning')) {
                        show_warning($this->botImplementation . ' web-hook URL: ' . $hookInfo['result']['url']);
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
        }
    }

}
