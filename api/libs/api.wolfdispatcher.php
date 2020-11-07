<?php

/**
 * Universal Telegram bot hooks extendable class
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
     * Dispatcher debugging flag
     * 
     * @var bool
     */
    protected $debugFlag = false;

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
        $this->initTelegram();
    }

    /**
     * Instance debugging flag setter
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
     * @param array $commands
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
     * Sets new dispatcher actions dataset
     * 
     * @param array $commands
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
     * Sets allowed chat IDs for this instance
     * 
     * @param array $chatIds
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
     * @param array $chatIds
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
     * @return string
     */
    protected function runAction($actionName) {
        $result = '';
        if (!empty($actionName)) {
            if (method_exists($this, $actionName)) {
                //class methods have priority
                $this->$actionName();
            } else {
                if (function_exists($actionName)) {
                    $actionName($this->receivedData);
                } else {
                    if ($this->debugFlag) {
                        //any command/reaction handler found
                        $chatId = $this->receivedData['chat']['id'];
                        $message = __('Any existing function on method named') . ' `' . $actionName . '` ' . __('not found by dispatcher');
                        $this->telegram->directPushMessage($chatId, $message);
                    }
                }
            }
        }
        return($result);
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
     * Listens for some events
     * 
     * @return array
     */
    public function listen() {
        $this->receivedData = $this->telegram->getHookData();
        if (!empty($this->receivedData)) {
            $this->reactInput();
        }
        return($this->receivedData);
    }

}
