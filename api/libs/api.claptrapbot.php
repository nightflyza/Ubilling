<?php

class ClapTrapBot extends WolfDispatcher {

     /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Current instance authorization state
     *
     * @var bool
     */
    protected $loggedIn = false;

    /**
     * Current conversation user login
     *
     * @var string
     */
    protected $myLogin = '';

    /**
     * Current conversation user password md5 hash
     *
     * @var string
     */
    protected $myPassword = '';

    /**
     * Current conversation client chatId
     *
     * @var int
     */
    protected $chatId = '';

    /**
     * Remote Ubilling userstats URL
     *
     * @var string
     */
    protected $apiUrl = '';

    /**
     * Auth database abstraction layer
     *
     * @var object
     */
    protected $authDb = '';

    /**
     * System caching object instance
     *
     * @var object
     */
    protected $cache='';
    
    /**
     * Current instance caching timeout
     *
     * @var int
     */
    protected $cacheTimeout=3600;

    /**
     * Contains available emoji icons as name=>icon
     *
     * @var array
     */
    protected $icons=array();

    /**
     * Contains current conversation context
     *
     * @var string
     */
    protected $context='';

    /**
     * Contains primary keyboard buttons optional count in row
     *
     * @var int
     */
    protected $primaryKbdInRow=2;

    /**
     * Contains enabled features list
     *
     * @var array
     */
    protected $featuresEnabled=array();

    /**
     * Contains available features struct
     *
     * @var array
     */
    protected $featuresAvailable=array();

    /**
     * Contains primary keyboard buttons set for authorized user
     *
     * @var array
     */
    protected $primaryKbdLoggedIn=array();

    /**
     * Contains primary keyboard buttons set for logged out user
     *
     * @var array
     */
    protected $primaryKbdLoggedOut=array();

    /**
     * Contains enabled commands list
     *
     * @var array
     */
    protected $commandsEnabled=array();

    /**
     * Flag to prevent actionLogIn from executing multiple times in one request
     *
     * @var bool
     */
    protected $loginActionCalled = false;
    
    /**
     * Contains system currency
     *
     * @var string
     */
    protected $systemCurrency='';

    /**
     * Some predefined stuff
     */
    const TABLE_AUTH = 'ct_auth';
    const KEY_CONTEXT = 'CT_CONTEXT';
    const OPTION_PKBD_COUNT='CLAPTRAPBOT_PKBD_ROW';
    const OPTION_FEATURES='CLAPTRAPBOT_FEATURES';
    const OPTION_SYSTEM_CURRENCY='TEMPLATE_CURRENCY';
    
    public function __construct($token) {
        $this->setBotName();
        $this->loadConfigs();
        $this->setOptions();
        $this->initCache();
        $this->initDb();
        $this->setIcons();
        if (!empty($token)) {
            $this->botToken = $token;
        }
        $this->initTelegram();
        $this->setFeaturesAvailable();
        $this->setCommands();
        $this->context=$this->getContext();
    }

    /**
     * Preloads all of required configs
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfigs() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Sets all of required options from config values
     * 
     * @return void
     */
    protected function setOptions() {
        $this->apiUrl = $this->altCfg['CLAPTRAPBOT_USERSTATS_URL'];

        if (isset($this->altCfg[self::OPTION_PKBD_COUNT])) {
            $this->primaryKbdInRow = ubRouting::filters($this->altCfg[self::OPTION_PKBD_COUNT], 'int');
        }

        if (isset($this->altCfg[self::OPTION_FEATURES])) {
            $this->featuresEnabled = explode(',', $this->altCfg[self::OPTION_FEATURES]);
        }

        if (isset($this->altCfg[self::OPTION_SYSTEM_CURRENCY])) {
            $this->systemCurrency = $this->altCfg[self::OPTION_SYSTEM_CURRENCY];
        }
    }

    /**
     * Inits auth database abstraction layer
     *
     * @return void
     */
    protected function initDb() {
        $this->authDb = new NyanORM(self::TABLE_AUTH);
    }

    /**
     * Inits system caching object instance
     * 
     * @return void
     */
    protected function initCache() {
        $this->cache = new UbillingCache();
    }

       /**
     * Sets all of available features for current instance
     * 
     * @return void
     */
    protected function setFeaturesAvailable() {
        $this->featuresAvailable['profile'] = array(
                'icon' => $this->icons['PROFILE'],
                'label' => __('Profile'),
                'command' => 'actionProfile',
            );

        $this->featuresAvailable['credit'] = array(
            'icon' => $this->icons['CREDIT'],
            'label' => __('Credit'),
            'command' => 'actionCredit',
        );

        $this->featuresAvailable['opayz'] = array(
            'icon' => $this->icons['PAY'],
            'label' => __('Online payments'),
            'command' => 'actionOpenpayz',
        );

        $this->featuresAvailable['sign_out'] = array(
            'icon' => $this->icons['SIGN_OUT'],
            'label' => __('Sign out'),
            'command' => 'actionLogOut',
        ); 

        $this->featuresAvailable['sign_in'] = array(
            'icon' => $this->icons['SIGN_IN'],
            'label' => __('Sign in'),
            'command' => 'actionLogIn',
        );

        $this->featuresAvailable['mypayments'] = array(
            'icon' => $this->icons['MYPAYMENTS'],
            'label' => __('My payments'),
            'command' => 'actionMyPayments',
        );

        $this->featuresAvailable['support'] = array(
            'icon' => $this->icons['PHONE'],
            'label' => __('Support'),
            'command' => 'actionSupport',
        );
    }

    /**
     * Sets available emoji icons as name=>icon
     * 
     * @return void
     */
    protected function setIcons() {
        $this->icons = array(
            'DELIMITER' => '➖➖➖➖➖➖➖➖➖➖➖➖➖',
            'PROFILE' => '👨‍💼',
            'REALNAME' => '👤',
            'CREDIT' => '⏱️',
            'PAY' => '💳',
            'SIGN_OUT' => '🔒',
            'SIGN_IN' => '🔑',
            'KEY' => '🔑',
            'TRY_AGAIN' => '🔄',
            'BACK' => '⬅️',
            'NEXT' => '➡️',
            'GOOD' => '👍',
            'SUCCESS' => '✅',
            'ERROR' => '❌',
            'INFO' => 'ℹ️',
            'WARNING' => '⚠️',
            'QUESTION' => '❓',
            'CLOCK' => '⏳',
            'BALANCE' => '💰',
            'ACCOUNT' => '💵',
            'COIN' => '🪙',
            'CHART'=>'📊',
            'PAYMENT_ID' => '🆔',
            'TARIFF' => '📜',
            'CONTEST' => '🏆',
            'CALENDAR' => '📆',
            'ADDRESS' => '🏠',
            'GLOBE' => '🌐',
            'WIFI' => '📶',
            'MOBILE' => '📱',
            'EMAIL' => '📧',
            'PHONE' => '📞',
            'SETTINGS' => '⚙️',
            'MYPAYMENTS' => '💸',
            'LI'=>'🔹',
            'DOWN'=>'🔽',
            'UNKNOWN' => '🤷',
        );
    }

    /**
     * Sets all of available commands for current instance
     * 
     * @return void
     */
    protected function setCommands() {
        //always available commands
        $commandsAvailable = array(
            '/start' => 'actionKeyboard',
            $this->icons['BACK'].' '.__('Back') => 'actionBack',
            $this->icons['SIGN_OUT'].' '.__('Sign out') => 'actionLogOut',
            $this->icons['SIGN_IN'].' '.__('Sign in') => 'actionLogIn',
        );
        
        //enabled features commands
        if (!empty($this->featuresEnabled)) {
            foreach ($this->featuresEnabled as $io=>$featureId) {
                if (isset($this->featuresAvailable[$featureId])) {
                    $featureData=$this->featuresAvailable[$featureId]; 

                    //commands which are available for all users
                    $commandsAvailable+= array(
                        $featureData['icon'].' '.$featureData['label'] 
                        => $featureData['command']);

                    //primary keyboard buttons
                    $this->primaryKbdLoggedIn[]= $featureData['icon'].' '.$featureData['label'];
                }
            }
        }

        //sign out button available for siged in users always
        $this->primaryKbdLoggedIn[]= $this->icons['SIGN_OUT'].' '.__('Sign out');

        //not logged in buttons set
        $this->primaryKbdLoggedOut[]= $this->icons['SIGN_IN'].' '.__('Sign in');
        $this->primaryKbdLoggedOut[]= $this->icons['SIGN_OUT'].' '.__('Sign out');


        if (!empty($commandsAvailable)) {
            $this->commandsEnabled = $commandsAvailable;
            $this->setActions($commandsAvailable);
        }
    }

 


    /**
     * Checks is some login/password auth valid or not?
     * 
     * @param string $login
     * @param string $password
     * 
     * @return bool
     */
    protected function checkAuth($login, $password) {
        $result = false;
        if (!empty($login) and !empty($password)) {
            $url = $this->apiUrl . '/?xmlagent=true&json=true&uberlogin=' . $login . '&uberpassword=' . $password;
            $api = new OmaeUrl($url);
            $reply = $api->response();
            if (!empty($reply)) {
                $replyDec = json_decode($reply, true);
                if (is_array($replyDec)) {
                    if (!empty($replyDec)) {
                        $result = true;
                    }
                }
            }
        }
        return($result);
    }

    /**
     * Pushes some request to Ubilling userstats XMLAgent API
     * 
     * @param string $request
     * 
     * @return array
     */
    protected function getApiData($request = '') {
        $result = array();
        if ($this->loggedIn) {
            $fullUrl = $this->apiUrl . '?xmlagent=true&json=true&uberlogin=' . $this->myLogin . '&uberpassword=' . $this->myPassword . $request;
            $remoteApi = new OmaeUrl($fullUrl);
            $requestData = $remoteApi->response();
            if (!empty($requestData)) {
                @$requestData = json_decode($requestData, true);
                if (is_array($requestData)) {
                    $result = $requestData;
                }
            }
        }
        return($result);
    }


    /**
     * Loads auth data from cache or database for current chatId
     * 
     * @return void
     */
    protected function loadAuthData() {
        if (!empty($this->chatId)) {
            $cacheKey = 'CTB_AUTH_' . $this->chatId;
            $authData = $this->cache->get($cacheKey, $this->cacheTimeout);
            
            if (empty($authData)) {
                $this->authDb->where('chatid', '=', $this->chatId);
                $this->authDb->where('active', '=', '1');
                $dbAuthData = $this->authDb->getAll();
                if (!empty($dbAuthData)) {
                    $dbAuthData = $dbAuthData[0];
                    $this->myLogin = ubRouting::filters($dbAuthData['login'], 'login');
                    $this->myPassword = $dbAuthData['password'];
                }
            } else {
                if (isset($authData['login'])) {
                    $this->myLogin = ubRouting::filters($authData['login'], 'login');
                }
                if (isset($authData['password'])) {
                    $this->myPassword = $authData['password'];
                }
            }
        }
    }

    /**
     * Saves auth data to database with active flag and clears cache
     * 
     * @return void
     */
    protected function saveAuthData() {
        if (!empty($this->chatId) and !empty($this->myLogin) and !empty($this->myPassword)) {
            $this->authDb->where('chatid', '=', $this->chatId);
            $existingAuth = $this->authDb->getAll();
            
            if (!empty($existingAuth)) {
                $this->authDb->where('chatid', '=', $this->chatId);
                $this->authDb->data('login', $this->myLogin);
                $this->authDb->data('password', $this->myPassword);
                $this->authDb->data('active', '1');
                $this->authDb->data('date', curdatetime());
                $this->authDb->save();
            } else {
                $this->authDb->data('chatid', $this->chatId);
                $this->authDb->data('login', $this->myLogin);
                $this->authDb->data('password', $this->myPassword);
                $this->authDb->data('active', '1');
                $this->authDb->data('date', curdatetime());
                $this->authDb->create();
            }
            
            $cacheKey = 'CTB_AUTH_' . $this->chatId;
            $this->cache->delete($cacheKey);
        }
    }

    /**
     * Sets current conversation context
     * 
     * @param mixed $context
     * 
     * @return void
     */
    protected function setContext($context) {
        if (!empty($this->chatId)) {
            $this->context = $context;
            $cachedContexts=$this->cache->get(self::KEY_CONTEXT, $this->cacheTimeout);
            if (empty($cachedContexts)) {
                $cachedContexts = array();
            }
            $cachedContexts[$this->chatId] = $this->context;
            $this->cache->set(self::KEY_CONTEXT, $cachedContexts, $this->cacheTimeout);
        }
    }

    /**
     * Gets current conversation context
     * 
     * @return mixed
     */
    protected function getContext() {
        $result='';
        if (!empty($this->chatId)) {
            $cachedContexts=$this->cache->get(self::KEY_CONTEXT, $this->cacheTimeout);
            if (empty($cachedContexts)) {
                $cachedContexts = array();
            }

            if (isset($cachedContexts[$this->chatId])) {
                $result = $cachedContexts[$this->chatId];
            }
        }
        return($result);
    }

     /**
     * Just sends some string content to current conversation
     * 
     * @param string $data
     * 
     * @return array
     */
    protected function sendToUser($data) {
        $result = array();
        if (!empty($this->chatId)) {
            $result = $this->telegram->directPushMessage($this->chatId, $data);
        }
        return($result);
    }

 

    /**
     * Just hook input data listener
     * 
     * @return array
     */
    public function listen() {
                //is something here?
                $this->receivedData = $this->telegram->getHookData();
                if (!empty($this->receivedData)) {
                    @$this->chatId = ubRouting::filters($this->receivedData['chat']['id'], 'int');
                    @$this->messageId = $this->receivedData['message_id'];
                    @$this->chatType = $this->receivedData['chat']['type'];

                //user auth subroutine
                $this->loadAuthData();
                if ($this->checkAuth($this->myLogin, $this->myPassword)) {
                    $this->loggedIn = true;
                }
                if (!$this->loggedIn and (empty($this->myLogin) or empty($this->myPassword))) {
                    $this->actionLogIn();
                }

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
     * Requsts or checks possibility of user credits via XMLAgent API
     * 
     * @param string $request
     * 
     * @return array
     */
    protected function getCreditData($request = '') {
        $result = array();
        if ($this->loggedIn) {
            $fullUrl = $this->apiUrl . '?module=creditor&agentcredit=true&json=true&uberlogin=' . $this->myLogin . '&uberpassword=' . $this->myPassword . $request;
            $remoteApi = new OmaeUrl($fullUrl);
            $requestData = $remoteApi->response();
            if (!empty($requestData)) {
                @$requestData = json_decode($requestData, true);
                if (is_array($requestData)) {
                    $result = $requestData;
                }
            }
        }
        return ($result);
    }

    /**
     * Setups some user auth data and stores it into cache/database
     * 
     * @return void
     */
    protected function actionLogIn() {
        if ($this->loginActionCalled) {
            return;
        }
        $this->loginActionCalled = true;

        if (!empty($this->chatId)) {
            if (!$this->loggedIn) {
                $cacheKey = 'CTB_AUTH_' . $this->chatId;
                $authData = $this->cache->get($cacheKey, $this->cacheTimeout);
                if (empty($authData)) {
                    $authData = array('login' => '', 'password' => '');
                }

                $signInMark=$this->featuresAvailable['sign_in']['icon'].' '.$this->featuresAvailable['sign_in']['label'];
                $signOutMark=$this->featuresAvailable['sign_out']['icon'].' '.$this->featuresAvailable['sign_out']['label'];
              
                if (!ispos($this->receivedData['text'], '/start')) {
                    if (empty($this->myLogin)) {
                        if ($this->receivedData['text'] != $signOutMark and $this->receivedData['text'] == $signInMark) {
                            $this->sendToUser($this->icons['QUESTION'].' '.__('Enter your login'));
                        }

                        if (!ispos($this->receivedData['text'], '/start') and $this->receivedData['text'] != $signInMark and $this->receivedData['text'] != $signOutMark) {
                            if (!empty($this->receivedData['text'])) {
                                $filteredLogin = ubRouting::filters(trim($this->receivedData['text']), 'login');
                                if (!empty($filteredLogin)) {
                                    $this->myLogin = $filteredLogin;
                                    $authData['login'] = $this->myLogin;
                                    $this->cache->set($cacheKey, $authData, $this->cacheTimeout);
                                    $this->sendToUser($this->icons['PROFILE'].' '.__('Your login') . ': ' . $this->myLogin);
                                    $this->sendToUser($this->icons['KEY'].' '.__('Enter your password'));
                                } else {
                                    $this->cache->delete($cacheKey);
                                    $this->sendToUser(__('Login must not be empty'));
                                    $this->actionKeyboard(__('Try again') . '?');
                                }
                            }
                        }
                    } else {
                        if (empty($this->myPassword)) {
                            if (!ispos($this->receivedData['text'], '/start') and $this->receivedData['text'] != $signInMark and $this->receivedData['text'] != $signOutMark) {
                                if (!empty($this->receivedData['text'])) {
                                    $rawPassword = trim($this->receivedData['text']);
                                    if (!empty($rawPassword)) {
                                        $this->myPassword = md5($rawPassword);
                                        if (!empty($this->myLogin) and !empty($this->myPassword)) {
                                            if ($this->checkAuth($this->myLogin, $this->myPassword)) {
                                                $this->loggedIn = true;
                                                $this->saveAuthData();
                                                $userData = $this->getApiData();
                                                $this->sendToUser($this->icons['GOOD'].' '.__('Welcome') . ', ' . $userData['realname']);
                                                $this->actionKeyboard(__('Whats next') . '?');
                                            } else {
                                                $this->cache->delete($cacheKey);
                                                $this->sendToUser($this->icons['ERROR'].' '.__('Incorrect login or password'));
                                                $this->actionKeyboard($this->icons['TRY_AGAIN'].' '.__('Try again') . '?');
                                            }
                                        } else {
                                            $this->cache->delete($cacheKey);
                                            $this->sendToUser($this->icons['ERROR'].' '.__('Login and password must not be empty'));
                                            $this->actionKeyboard($this->icons['TRY_AGAIN'].' '.__('Try again') . '?');
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }


    /**
     * Cleanups user auth data by setting active flag to 0
     * 
     * @return void
     */
    protected function actionLogout() {
        if (!empty($this->chatId)) {
            $this->authDb->where('chatid', '=', $this->chatId);
            $this->authDb->data('active', '0');
            $this->authDb->save();
            
            $cacheKey = 'CTB_AUTH_' . $this->chatId;
            $this->cache->delete($cacheKey);
            
            $this->loggedIn = false;
            $this->myLogin = '';
            $this->myPassword = '';
            $this->sendToUser(__('You are logged off now'). $this->icons['SIGN_OUT']);
            $this->actionKeyboard($this->icons['SIGN_IN'].' '.__('Sign in') . '?');
        }
    }

    /**
     * Rearranges flat buttons array into 2D array with specified buttons per row
     * 
     * 
     * @param array $buttonsArray
     * @param int $inRow
     * 
     * @return array
     */
    protected function rearrangeButtons($buttonsArray, $inRow = 2) {
        $result = array();
        if (!empty($buttonsArray)) {
            $result = array_chunk($buttonsArray, $inRow);
        }
        return($result);
    }


    /**
     * Pushes some keyboard to uses based on current context
     * 
     * @param string $title
     * 
     * @return void
     */
    protected function actionKeyboard($title = '') {
        if (empty($title)) {
            $title = '⌨️';
        }

        if (!$this->loggedIn) {
            if (!empty($this->receivedData['text'])) {
                if (ispos($this->receivedData['text'], '/start') and ispos($this->receivedData['text'], '-')) {
                    $cacheKey = 'CTB_AUTH_' . $this->chatId;
                    $rawAuth = str_replace('/start', '', $this->receivedData['text']);
                    $rawAuth = explode('-', $rawAuth);
                    if (!empty($rawAuth[0]) and !empty($rawAuth[1])) {
                        $tryLogin = ubRouting::filters(trim($rawAuth[0]), 'login');
                        $tryPassword = trim($rawAuth[1]);
                        if (!empty($tryLogin) and !empty($tryPassword)) {
                            if ($this->checkAuth($tryLogin, $tryPassword)) {
                                $this->myLogin = $tryLogin;
                                $this->myPassword = $tryPassword;
                                $this->loggedIn = true;
                                $this->saveAuthData();
                                $this->sendToUser($this->icons['GOOD'].' '.__('You are successfully signed in') . '. ' . __('Welcome') . '!');
                            } else {
                                $this->cache->delete($cacheKey);
                                $this->sendToUser($this->icons['ERROR'].' '.__('Incorrect login or password'));
                            }
                        } else {
                            $this->cache->delete($cacheKey);
                            $this->sendToUser($this->icons['ERROR'].' '.__('Login and password must not be empty'));
                        }
                    }
                }
            }
        }


        // buttons set used if user is logged in
        if ($this->loggedIn) {
            $oneTime = false;
            $buttonsArray = $this->primaryKbdLoggedIn;
            $buttonsArray = $this->rearrangeButtons($buttonsArray, $this->primaryKbdInRow);
        } else {
            // buttons set used if user is not logged and unknown
            $oneTime = true;
            $buttonsArray = $this->primaryKbdLoggedOut;
            $buttonsArray = $this->rearrangeButtons($buttonsArray, $this->primaryKbdInRow);
            
        }

        $keyboard = $this->telegram->makeKeyboard($buttonsArray, false, true, $oneTime);
        $this->telegram->directPushMessage($this->chatId, $title, $keyboard);
    }
    

    /**
     * Clears current conversation context and returns to main menu
     * 
     * @return void
     */
    protected function actionBack() {
        $this->setContext('');
        $this->actionKeyboard();
    }

    /**
     * Renders single profile row
     * 
     * @param string $icon
     * @param string $label
     * @param string $data
     * 
     * @return string
     */
    protected function profileRow($icon='',$label,$data) {
        $result = '';
        if (!empty($icon)) {
            if (isset($this->icons[$icon])) {
                $result = $this->icons[$icon].' ';
            } else {
                $result = $this->icons['ERROR'].' ';
            }
        }
        $result .= $label.': '.$data.PHP_EOL;
        return($result);
    }

    /**
     * Renders user profile data
     * 
     * @return void
     */
    protected function actionProfile() {
        if ($this->loggedIn) {
        $userData = $this->getApiData();
        if (!empty($userData)) {
            $this->setContext('profile');
            $reply = __('User profile') . ' ' .  PHP_EOL;
            $reply .= $this->icons['DELIMITER'].' ' . PHP_EOL;
            $reply .= $this->profileRow('REALNAME', __('RealName'), $userData['realname']);
            $reply .= $this->profileRow('ADDRESS', __('Address'), $userData['address']);
            $reply .= $this->profileRow('MOBILE', __('Mobile'), $userData['mobile']);
            $reply .= $this->profileRow('TARIFF', __('Your') . ' ' . __('tariff'), $userData['tariff']);
            $reply .= $this->profileRow('BALANCE', __('Your') . ' ' . __('balance'), $userData['cash'] . ' ' . $userData['currency']);
            $reply .= $this->profileRow('GLOBE', __('IP'), $userData['ip']);
            $reply .= $this->profileRow('CREDIT', __('Your') . ' ' . __('credit'), $userData['credit'] . ' ' . $userData['currency']);
            
            if ($userData['creditexpire'] != 'No') {
                $reply .= $this->profileRow('CALENDAR', __('Credit') . ' ' . __('till'), $userData['creditexpire']);
            }

            $reply .= $this->profileRow('ACCOUNT', __('Your') . ' ' . __('account'), __($userData['accountstate']));
            if (!empty($userData['payid'])) {
                $reply .= $this->profileRow('PAYMENT_ID', __('Your') . ' ' . __('payment ID'), $userData['payid']);
            }

            $this->sendToUser($reply);
            $this->actionKeyboard(__('Whats next') . '?');
            } else {
                $this->sendToUser($this->icons['ERROR']. ' ' .__('Something went wrong').': '.__('Query returned empty result'));
            }
        } else {
            $this->sendToUser($this->icons['ERROR']. ' ' .__('You are not logged in'));
        }
    }

    /**
     * Requests credit for user or check possibility of credit
     * 
     * @return void
     */
    protected function actionCredit() {
        if ($this->loggedIn) {
            $agreeMark= $this->icons['CREDIT'].' '.__('Yes, i want credit');
            if (ispos($this->receivedData['text'], $agreeMark)) {
                $this->setContext('creditditagree');
            }
            
            if ($this->context != 'creditditagree') {
               $this->setContext('creditdialog');
               $onlyTest = '&justcheck=true';
               $kbLabel= $this->icons['DOWN'].' '.__('Are you agree with the terms and conditions').' ?';
            } else {
                $onlyTest = '';
                $kbLabel=__('Whats next') . '?';
            }
            
            $creditCheck = $this->getCreditData($onlyTest);
            $creditButtons=array();
            $creditButtons[] = array($this->icons['BACK'].' '.__('Back'));
            $creditIntroText=$this->icons['INFO'].' '.$creditCheck['creditintro'];
            $this->sendToUser($creditIntroText);

        
            if ($creditCheck['status'] == 0) {
                if ($this->context == 'creditditagree') {
                    $this->sendToUser($this->icons['SUCCESS']. ' ' . __('Credit succefully set'));
                }
                
                if ($this->context == 'creditdialog') {
                    $creditButtons[] = array($this->icons['CREDIT'].' '.__('Yes, i want credit'));
                }
    
            } else {
                $errorLabel=(isset($creditCheck['fullmessage'])) ? $creditCheck['fullmessage'] : $creditCheck['message'];
                $this->sendToUser($this->icons['ERROR']. ' ' . __($errorLabel));
                $kbLabel=$this->icons['DOWN'].' '.__('Select an action');
            }

            //credit action specific keyboard
            $keyboard = $this->telegram->makeKeyboard($creditButtons, false, true, false);
            $this->telegram->directPushMessage($this->chatId, $kbLabel, $keyboard);
            
        } else {
            $this->sendToUser($this->icons['ERROR']. ' ' .__('You are not logged in'));
        }
    }


    /**
     * Renders payment methods available for user
     * 
     * @return void
     */
    protected function actionOpenpayz() {
        if ($this->loggedIn) {
            $allData = $this->getApiData('&opayz=true');
            if (!empty($allData)) {
                $opayzLabel='';
                $opayzLabel .= $this->icons['BALANCE'].' ' . __('List of available payment methods') . PHP_EOL;
                $opayzLabel .= $this->icons['DELIMITER'].' ' . PHP_EOL;
                $opayzLabel .= $this->icons['LI'].' ' . __('You can make payments through the following services') . PHP_EOL;
                $opayzLabel .= PHP_EOL;
                
                $buttonsArray = array();
                foreach ($allData as $io => $each) {
                    $buttonsArray[] = array(array('text' => $this->icons['PAY'].' ' . $each['name'] . '', 'url' => $each['url']));
                    $opPsDesc='';
                    if (!empty($each['description'])) {
                        $opPsDesc=' (' . $each['description'] . ')';
                    }
                    $opayzLabel.=$this->icons['LI'].' ' . $each['name'].$opPsDesc . PHP_EOL;
                }

                $opayzLabel.= PHP_EOL;
                $opayzLabel .= $this->icons['DOWN'].' ' . __('Select a system below to proceed to payment') . PHP_EOL;

                $keyboard = $this->telegram->makeKeyboard($buttonsArray, true, true, false);
                $this->telegram->directPushMessage($this->chatId, $opayzLabel, $keyboard);
            } else {
                $this->sendToUser($this->icons['ERROR']. ' ' .__('No available payment methods'));
            }
        } else {
            $this->sendToUser($this->icons['ERROR']. ' ' .__('You are not logged in'));
        }
    }

    /**
     * Renders user payments list
     * 
     * @return void
     */
    protected function actionMyPayments() {
        if ($this->loggedIn) {
            $allPayments = $this->getApiData('&payments=true');
            if (!empty($allPayments)) {
                $paymentsReply= $this->icons['INFO']. ' ' .__('My payments') . PHP_EOL;
                $paymentsReply .= $this->icons['DELIMITER']. ' ' . PHP_EOL;
                $allPayments = array_reverse($allPayments);
              
                foreach ($allPayments as $io => $each) {
                 $paymentsReply .= $this->icons['CALENDAR']. ' ' . __('Date') . ': ' . $each['date'] . PHP_EOL;
                 $paymentsReply .= $this->icons['BALANCE']. ' ' . __('Deposited to account') . ': ' . $each['summ'] . ' ' . $this->systemCurrency . PHP_EOL;
                 $paymentsReply .= $this->icons['COIN']. ' ' . __('Balance BEFORE') . ': ' . $each['balance'] . ' ' . $this->systemCurrency . PHP_EOL;
                 $paymentsReply .= $this->icons['CHART']. ' ' . __('Balance AFTER') . ': ' . ($each['balance'] + $each['summ']) . ' ' . $this->systemCurrency . PHP_EOL;
                 $paymentsReply .= PHP_EOL;
                 $paymentsReply .= PHP_EOL;
                }

                $this->sendToUser($paymentsReply);
            } else {
                $this->sendToUser($this->icons['UNKNOWN']. ' ' .__('No payments found'));
            }
        } else {
            $this->sendToUser($this->icons['ERROR']. ' ' .__('You are not logged in'));
        }
    }



}
