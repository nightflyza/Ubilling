<?php

/**
 * CL4P-TP implementation
 */
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
     * Contains limit of requests per minute (APM)
     *
     * @var int
     */
    protected $throttleLimit=0;
    
    /**
     * Contains throttle ban time in seconds
     *
     * @var int
     */
    protected $throttleBanTime=0;

    /**
     * Contains limit of payments to show in mypayments list by default
     *
     * @var int
     */
    protected $myPaymentsLimit=4;

    /**
     * Contains limit of ticket text length
     *
     * @var int
     */
    protected $ticketTextLimit=1000;

    /**
     * Contains existing tag type ID to mark authorized users
     *
     * @var int
     */
    protected $userTagId=0;

    /**
     * Some predefined stuff
     */
    const TABLE_AUTH = 'ct_auth';
    const KEY_AUTH_TMP='CT_AUTH_TMP';
    const KEY_CONTEXT = 'CT_CONTEXT';
    const KEY_THROTTLE = 'CT_THROTTLE';
    const OPTION_PKBD_COUNT='CLAPTRAPBOT_PKBD_ROW';
    const OPTION_FEATURES='CLAPTRAPBOT_FEATURES';
    const OPTION_SYSTEM_CURRENCY='TEMPLATE_CURRENCY';
    const OPTION_THROTTLE_LIMIT='CLAPTRAPBOT_THROTTLE_LIMIT';
    const OPTION_THROTTLE_BAN_TIME='CLAPTRAPBOT_THROTTLE_BAN_TIME';
    const OPTION_PAY_LIMIT='CLAPTRAPBOT_MY_PAYMENTS_LIMIT';
    const OPTION_TAG_ID='CLAPTRAPBOT_USERS_TAGID';
    const OPTION_TOKEN='CLAPTRAPBOT_TOKEN';
    const OPTION_AUTH_STRING='CLAPTRAPBOT_AUTH_STRING';
    const OPTION_HOOK_URL='CLAPTRAPBOT_HOOK_URL';
    const PROUTE_VALIDATE='ctbvalidator';
    const VALIDATION_RESULT='HA! I was MADE to open doors!';
    
    /**
     * STAIRS?! NOOOOOOOOOOOOOOOOO!
     *
     * @param string $token
     */
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

        if (isset($this->altCfg[self::OPTION_THROTTLE_LIMIT])) {
            $this->throttleLimit = ubRouting::filters($this->altCfg[self::OPTION_THROTTLE_LIMIT], 'int');
        }

        if (isset($this->altCfg[self::OPTION_THROTTLE_BAN_TIME])) {
            $this->throttleBanTime = ubRouting::filters($this->altCfg[self::OPTION_THROTTLE_BAN_TIME], 'int');
        }

        if (isset($this->altCfg[self::OPTION_PAY_LIMIT])) {
            $this->myPaymentsLimit = ubRouting::filters($this->altCfg[self::OPTION_PAY_LIMIT], 'int');
        }

        if (isset($this->altCfg[self::OPTION_TAG_ID])) {
            $this->userTagId = ubRouting::filters($this->altCfg[self::OPTION_TAG_ID], 'int');
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

        $this->featuresAvailable['announcements'] = array(
            'icon' => $this->icons['ANNOUNCEMENT'],
            'label' => __('Announcements'),
            'command' => 'actionAnnouncements',
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

        $this->featuresAvailable['catv'] = array(
            'icon' => $this->icons['TELEVISION'],
            'label' => __('CaTV'),
            'command' => 'actionCATV',
        );


        $this->featuresAvailable['testing'] = array(
            'icon' => $this->icons['SEARCH'],
            'label' => __('Testing'),
            'command' => 'actionTesting',
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
            'ALERT'=>'🚨',
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
            'TELEVISION' => '📺',
            'ANNOUNCEMENT' => '📢',
            'READ' => '✅',
            'SEARCH' => '🔍',
            'CONTRACT' => '📄',
            'REDC'=>'🔴',
            'GREENC'=>'🟢',
            'SOS'=>'🆘',
            'KEYBOARD'=>'⌨️',
        );
    }

    /**
     * Checks if feature is enabled
     * 
     * @param string $featureId
     * 
     * @return bool
     */
    protected function isFeatureEnabled($featureId) {
        $result = false;
        if (!empty($featureId)) {
            if (in_array($featureId, $this->featuresEnabled)) {
                $result = true;
            }
        }

        return($result);
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

        //custom and optional commands here
        if ($this->isFeatureEnabled('announcements')) {
            $commandsAvailable[$this->icons['READ'].' '.__('Mark all as read')] = 'actionAnnouncements';
        }

        if ($this->isFeatureEnabled('mypayments')) {
            $commandsAvailable[$this->icons['SEARCH'].' '.__('Show all payments')] = 'actionMyPayments';
            $commandsAvailable[$this->icons['SEARCH'].' '.__('Show more payments')] = 'actionMyPayments';
        }

        if ($this->isFeatureEnabled('support')) {
            $commandsAvailable[$this->icons['SEARCH'].' '.__('View request').'# '] = 'actionSupport';
            $commandsAvailable[$this->icons['BACK'].' '.__('Back to requests')] = 'actionSupport';
        }

        
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


        // Minion, you've gotta go on without me! Do your master proud!
        if (!empty($commandsAvailable)) {
            $this->commandsEnabled = $commandsAvailable;
            $this->setActions($commandsAvailable);
        }

        $this->setCallbackQueryHandler('testCB');
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
            $url = $this->apiUrl . '/?xmlagent=true&json=true&justauth=true&uberlogin=' . $login . '&uberpassword=' . $password;
            $api = new OmaeUrl($url);
            $reply = $api->response();
            if (!empty($reply)) {
                $replyDec = json_decode($reply, true);
                if (is_array($replyDec)) {
                    if (!empty($replyDec)) {
                        if ($replyDec['auth'] and $replyDec['login'] == $login) {
                            $result = true;
                        }
                    }
                }
            }
        }
        return($result);
    }

    /**
     * Pushes some request to Ubilling userstats XMLAgent API and returns response
     * 
     * @param string $request
     * 
     * @return array
     */
    protected function getApiData($request = '') {
        $result = array();
        if ($this->loggedIn) {
            $fullUrl = $this->apiUrl . '/?xmlagent=true&json=true&uberlogin=' . $this->myLogin . '&uberpassword=' . $this->myPassword . $request;
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
            $cacheKey = self::KEY_AUTH_TMP . $this->chatId;
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
            
            $cacheKey = self::KEY_AUTH_TMP . $this->chatId;
            $this->cache->delete($cacheKey);
            $this->setUserTag(true);
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
     * Gets remaining ban time in seconds for current user
     * 
     * @return int
     */
    protected function getThrottleBanTime() {
        $result = 0;
        if (!empty($this->chatId)) {
            $throttleData = $this->cache->get(self::KEY_THROTTLE, $this->cacheTimeout);
            if (!empty($throttleData[$this->chatId]['banned_until'])) {
                $banTimeLeft = $throttleData[$this->chatId]['banned_until'] - time();
                if ($banTimeLeft > 0) {
                    $result = $banTimeLeft;
                }
            }
        }
        return($result);
    }

    /**
     * Checks if user is throttled by APM limit
     * 
     * @return bool
     */
    protected function checkThrottle() {
        $result = true;
        if (!empty($this->chatId)) {
            if ($this->throttleLimit > 0 and $this->throttleBanTime > 0) {
                $throttleData = $this->cache->get(self::KEY_THROTTLE, $this->cacheTimeout);
                if (empty($throttleData)) {
                    $throttleData = array();
                }
                
                $currentTime = time();
                $chatIdKey = $this->chatId;
                
                if (!isset($throttleData[$chatIdKey])) {
                    $throttleData[$chatIdKey] = array(
                        'actions' => array(),
                        'banned_until' => 0
                    );
                }
                
                if ($throttleData[$chatIdKey]['banned_until'] > $currentTime) {
                    $result = false;
                } else {
                    $throttleData[$chatIdKey]['actions'] = array_filter(
                        $throttleData[$chatIdKey]['actions'],
                        function($timestamp) use ($currentTime) {
                            return ($currentTime - $timestamp) < 60;
                        }
                    );
                    
                    if (count($throttleData[$chatIdKey]['actions']) >= $this->throttleLimit) {
                        $throttleData[$chatIdKey]['banned_until'] = $currentTime + $this->throttleBanTime;
                        $result = false;
                    } else {
                        $throttleData[$chatIdKey]['actions'][] = $currentTime;
                    }
                }
                
                $this->cache->set(self::KEY_THROTTLE, $throttleData, $this->cacheTimeout);
            }
        }
        return($result);
    }


    /**
     * Sets user tag for current user on authorization or logout
     * 
     * @param bool $loggedIn
     * 
     * @return void
     */
    protected function setUserTag($loggedIn) {
        if (!empty($this->userTagId)) {
            if (!empty($this->chatId)) {
                    if (!empty($this->myLogin)) {
                        if ($loggedIn) {
                            stg_add_user_tag($this->myLogin, $this->userTagId);
                        } else {
                            stg_del_user_tagid($this->myLogin, $this->userTagId);
                        }
                    } 
              }
            }
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

                    if ($this->checkThrottle()) {
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
                            } else {
                                $banTimeLeft = $this->getThrottleBanTime();
                                $banTimeLabel=zb_formatTime($banTimeLeft);
                                $banLabel = $this->icons['ERROR'].' '.__('To many requests').'! '.__('Not so fast please').'. ';
                                $banLabel .= PHP_EOL.__('Wait for').' '.$banTimeLabel.' '.__('to continue');
                                $this->sendToUser($banLabel);
                            }
                }
                $this->writeDebugLog();
                return ($this->receivedData);
    }


    /**
     * Magic middleware to handle raw-text inputs from user
     *
     * @return void
     */
    public function handleEmptyAction() {
        $currentContext=$this->getContext();
        if (!empty($currentContext)) {

            //handling new support requests or existing tickets threads
            if ($this->isFeatureEnabled('support')) {
                if ($currentContext=='support' or ispos($currentContext, 'viewsupportthread_')) {
                    //calling subrouting for new ticket creation or reply to current or any existing opened thread
                    $this->actionCreateSupportTicket();
                } else {
                    if ($this->loggedIn) {
                        if ($currentContext!='auth') {
                         $this->sendToUser($this->icons['WARNING'].' '.__('This will not work').': '.__('Unknown command'));
                        }
                    }
                }
            }
        }
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

//
//       ,
//       |
//    ]  |.-._
//     \|"(0)"| _]
//     `|=\#/=|\/
//      :  _  :
//       \/_\/ 
//        |=| 
//        `-' 
//  LET'S TEAR THIS PLANET A NEW ASSHOLE! YAAAAAAGHHHHH! 
//


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
                $cacheKey = self::KEY_AUTH_TMP . $this->chatId;
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
                                                $this->setContext('auth');
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
            $this->setUserTag(false);
            $this->authDb->where('chatid', '=', $this->chatId);
            $this->authDb->data('active', '0');
            $this->authDb->save();
            
            
            $cacheKey = self::KEY_AUTH_TMP . $this->chatId;
            $this->cache->delete($cacheKey);
            
            $this->loggedIn = false;
            $this->myLogin = '';
            $this->myPassword = '';
            $this->sendToUser(__('You are logged off now'). $this->icons['SIGN_OUT']);
            $this->actionKeyboard($this->icons['SIGN_IN'].' '.__('Sign in') . '?');
        }
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
                    $cacheKey = self::KEY_AUTH_TMP . $this->chatId;
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
            $buttonsArray = $this->rearrangeButtons($this->primaryKbdLoggedIn, $this->primaryKbdInRow);
        } else {
            // buttons set used if user is not logged and unknown
            $oneTime = true;
            $buttonsArray = $this->rearrangeButtons($this->primaryKbdLoggedOut, $this->primaryKbdInRow);
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
        $this->setContext('mainmenu');
        $this->actionKeyboard(__('Select an action').' '. $this->icons['DOWN']);
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
    protected function profileRow($icon='',$label='',$data='') {
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
            $reply .= $this->profileRow('TARIFF', __('Your') . ' ' . __('tariff'), __($userData['tariff']));
            if (!empty($userData['tariffnm']) and $userData['tariffnm'] != 'No') {
                $reply .= $this->profileRow('TARIFF', __('Planned tariff change'), __($userData['tariffnm']));
            }
            $reply .= $this->profileRow('BALANCE', __('Your') . ' ' . __('balance'), $userData['cash'] . ' ' . $this->systemCurrency);
            $reply .= $this->profileRow('GLOBE', __('IP'), $userData['ip']);
            $reply .= $this->profileRow('CREDIT', __('Your') . ' ' . __('credit'), $userData['credit'] . ' ' . $this->systemCurrency);
            
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
                $limitLabel='';
                $limitCount=$this->myPaymentsLimit;
                if ($this->receivedData['text'] == $this->icons['SEARCH'].' '.__('Show more payments')) {
                    $limitCount=$limitCount*2;
                }

                if ($this->receivedData['text'] == $this->icons['SEARCH'].' '.__('Show all payments')) {
                    $limitCount=0;
                }

                if ($limitCount > 0) {
                    $limitLabel=' ('.__('latest') . ' ' . $limitCount . ')';
                }

                $paymentsReply= $this->icons['INFO']. ' ' .__('My payments') . $limitLabel . PHP_EOL;
                $paymentsReply .= $this->icons['DELIMITER']. ' ' . PHP_EOL;
                
                //limit payments to show
                if ($limitCount > 0) {
                    $allPayments = array_slice($allPayments, 0, $limitCount);
                }

                //newer payments at the end
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

                //custom mypayments keyboard
                if ($this->myPaymentsLimit > 0) {
                    $myPaymentsButtons = array();
                    $myPaymentsButtons[] = array($this->icons['BACK'].' '.__('Back'));
                    $myPaymentsButtons[] = array($this->icons['SEARCH'].' '.__('Show more payments'));
                    $myPaymentsButtons[] = array($this->icons['SEARCH'].' '.__('Show all payments'));
                    $keyboard = $this->telegram->makeKeyboard($myPaymentsButtons, false, true, false);
                    $this->telegram->directPushMessage($this->chatId, $this->icons['DOWN'].' '.__('Select an action'), $keyboard);
                }

            
            } else {
                $this->sendToUser($this->icons['UNKNOWN']. ' ' .__('No payments found'));
            }
        } else {
            $this->sendToUser($this->icons['ERROR']. ' ' .__('You are not logged in'));
        }
    }

    /**
     * Renders announcements list
     * 
     * @return void
     */
    protected function actionAnnouncements() {
        if ($this->loggedIn) {
            if ($this->receivedData['text'] == $this->icons['READ'].' '.__('Mark all as read')) {
                $this->setContext('annreadall');
                $this->getApiData('&annreadall=true');
                $this->sendToUser($this->icons['INFO'].' '.__('All announcements marked as read'));
            } else {
                $this->setContext('annlist');
            }

            $allAnnouncements = $this->getApiData('&announcements=true');
            if (!empty($allAnnouncements)) {
                $unreadCount=0;
                $announcementsReply = $this->icons['ANNOUNCEMENT']. ' ' .__('Announcements available').': ' . PHP_EOL.PHP_EOL;
                foreach ($allAnnouncements as $io => $each) {
                    $readFlag=($each['read']) ? $this->icons['READ'] : $this->icons['ALERT'];
                    if ($each['read'] == 0) {
                        $unreadCount++;
                    }
                    $announcementsReply .= $readFlag. ' '.wf_tag('b') . $each['title'].wf_tag('b', true) . PHP_EOL;
                    $announcementsReply .= $each['text'] . PHP_EOL;
                    $announcementsReply .= $this->icons['DELIMITER'].PHP_EOL;
                }

                $announcementsReply .= 'parseMode:{html}';
                $this->sendToUser($announcementsReply);
                
                //custom keyboard for announcements list
                $announcementsButtons = array();
                $announcementsButtons[] = array($this->icons['BACK'].' '.__('Back'));
                    if ($this->context == 'annlist') {
                        if ($unreadCount > 0) {
                            $announcementsButtons[] = array($this->icons['READ'].' '.__('Mark all as read'));
                    }
                }

                    $keyboard = $this->telegram->makeKeyboard($announcementsButtons, false, true, false);
                    $this->telegram->directPushMessage($this->chatId, $this->icons['DOWN'].' '.__('Select an action'), $keyboard);
                
            } else {
                $this->sendToUser($this->icons['UNKNOWN']. ' ' .__('No important announcements found'));
            }
        } else {
            $this->sendToUser($this->icons['ERROR']. ' ' .__('You are not logged in'));
        }
    }

    /**
     * Renders CaTV user profile
     * 
     * @return void
     */
    protected function actionCATV() {
        if ($this->loggedIn) {
            $this->setContext('catv');
            $catvData = $this->getApiData('&ukv=true');
            if (!empty($catvData)) {
                $catvReply = $this->icons['TELEVISION'].' '.__('User profile').' '.__('CaTV').' ' .PHP_EOL;
                $catvReply .= $this->icons['DELIMITER'].' ' . PHP_EOL;

                $catvReply.= $this->profileRow('ADDRESS', __('Address'), $catvData['address']);
                $catvReply.= $this->profileRow('REALNAME', __('RealName'), $catvData['realname']);
                $catvReply.= $this->profileRow('CONTRACT', __('Contract'), $catvData['contract']);
                $catvReply.= $this->profileRow('PHONE', __('Phone'), $catvData['phone']);
                $catvReply.= $this->profileRow('MOBILE', __('Mobile'), $catvData['mobile']);
                $catvReply.= $this->profileRow('TARIFF', __('Your') . ' ' . __('tariff'), $catvData['tariff']);
                if (!empty($catvData['tariffnm'])) {
               $catvReply.= $this->profileRow('TARIFF', __('Planned tariff change'), $catvData['tariffnm']);
                    if (!empty($catvData['tariffnmdate'])) {
                        $catvReply .= $this->profileRow('CALENDAR', __('Move tariff after'), $catvData['tariffnmdate']);
                    }
                }
                $catvReply.= $this->profileRow('BALANCE', __('Your') . ' ' . __('balance'), $catvData['cash'] . ' ' . $this->systemCurrency);
                
                
                $this->sendToUser($catvReply);
            } else {
                $this->sendToUser($this->icons['UNKNOWN']. ' ' .__('No CaTV account found'));
            }
        } else {
            $this->sendToUser($this->icons['ERROR']. ' ' .__('You are not logged in'));
        }
    }

    /**
     * Checks is given ticket ID existing thread ID?
     *
     * @param array $allTickets
     * @param int $ticketId
     * 
     * @return bool
     */
    protected function isThreadId($allTickets, $ticketId) {
        $result=false;
        $ticketId=ubRouting::filters($ticketId,'int');
        if (!empty($allTickets) and !empty($ticketId)) {
            foreach ($allTickets as $io => $each) {
                if ($each['id'] == $ticketId and empty($each['replyid'])) {
                    $result=true;
                    break;
                }
            }

        }
        return ($result);
    }

    /**
     * Renders support request thread by its ID only if its not reply
     *
     * @param array $allTickets
     * @param int $ticketId
     * 
     * @return string
     */
    protected function renderTicketThread($allTickets,$ticketId) {
        $ticketId=ubRouting::filters($ticketId, 'int');
        $result='';
        $threadData=array();
        $threadOpen=false;
        if (!empty($allTickets) and !empty($ticketId)) {
            if ($this->isThreadId($allTickets, $ticketId)) {
            foreach ($allTickets as $io => $each) {
                if ($each['id'] == $ticketId or $each['replyid'] == $ticketId) {
                    $threadData[]=$each;
                }
            }

            if (!empty($threadData)) {
                $result.=$this->icons['PHONE'].' '.__('Support request').'#  '.$ticketId . PHP_EOL;
                
                $result.=$this->icons['DELIMITER'].PHP_EOL;
                foreach ($threadData as $io => $each) {
                    if (empty($each['replyid'])) {
                        //initial ticket
                        $ticketStatus=($each['status']) ? $this->icons['GREENC'] : $this->icons['REDC'];
                        if ($each['status']==1) {
                            $threadOpen=true;
                        }
                    } else {
                        $ticketStatus='';
                    }
                    $messageAuthor=($each['from']==$this->myLogin) ? __('User') : __('Support');
                    $result.=$this->icons['CALENDAR'] .' '. $each['date'] .' ('.$messageAuthor.') '.$ticketStatus. PHP_EOL;
                    $ticketText=strip_tags($each['text']);
                    $ticketText=ubRouting::filters($ticketText,'safe');
                    $result.= $each['text'].PHP_EOL;
                    $result.= $this->icons['DELIMITER'].PHP_EOL;
                }
            } else {
                $result=$this->icons['ERROR'].' '.__('Something went wrong');    
            }
        } else {
            $result=$this->icons['ERROR'].' '.__('Not existing ticket');
        }
        } else {
            $result=$this->icons['ERROR'].' '.__('Nothing to show');
        }
        return($result);
    }

    /**
     * Handles support action
     * 
     * @return void
     */
    protected function actionSupport() {
        if ($this->loggedIn) {
            $this->setContext('support');
            $allTickets=$this->getApiData('&tickets=true');
            $supportReply='';
            $viewCommandMark=$this->icons['SEARCH'].' '.__('View request').'# ';
            $supportButtons=array();

            $supportButtons[] = array($this->icons['BACK'].' '.__('Back'));

            
            if (!empty($allTickets)) {
                //from oldest to newest
                $allTickets=array_reverse($allTickets);

                //here we show exact ticket thread
                if (ispos($this->receivedData['text'], $viewCommandMark)) {
                    $this->setContext('viewsupportthread');
                    //override thread back button with requests back control
                    $supportButtons=array();
                    $supportButtons[] = array($this->icons['BACK'].' '.__('Back to requests'));
                    $cleanTicketId=str_replace($viewCommandMark,'',$this->receivedData['text']);
                    $cleanTicketId=ubRouting::filters($cleanTicketId,'int');
                    $supportReply.=$this->renderTicketThread($allTickets,$cleanTicketId);
                    $this->setContext('viewsupportthread_'.$cleanTicketId);
                }

                //here we render available tickets list
                if ($this->getContext()=='support') {
                $supportReply.=$this->icons['PHONE'].' '.__('Your previous technical support requests').': '.PHP_EOL;
                $supportReply.=$this->icons['DELIMITER'].PHP_EOL;
                foreach ($allTickets as $io => $each) {
                    if ($each['from'] == $this->myLogin and empty($each['to']) and $each['replyid']==0) {
                        $ticketStatus=($each['status']) ? $this->icons['GREENC'] : $this->icons['REDC'];
                        $textPreview=zb_cutString($each['text'], 20);
                        $supportReply.= $ticketStatus.' ' . $each['id'] . ': ' . $each['date'] . ' (' . $textPreview.')'. PHP_EOL;
                        //appending view button for each ticket
                        $ticketsButtons[] = array($this->icons['SEARCH'].' ' .__('View request').'# '. $each['id'].' '.$ticketStatus);
                        }
                    }

                     //reversing tickets buttons array
                    if (!empty($ticketsButtons) and $this->getContext()=='support') {
                        $ticketsButtons=array_reverse($ticketsButtons);
                        $supportButtons=array_merge($supportButtons, $ticketsButtons);
                    }
                }

               
            } else {
                $supportReply.= $this->icons['UNKNOWN'].' '.__('No previous support requests found');
            }

            $this->sendToUser($supportReply);
            
            //here some support custom keyboard
            $keyboard = $this->telegram->makeKeyboard($supportButtons, false, true, false);
            $supportKbdLabel=$this->icons['DOWN'].' '.__('Select an action').', '.__('or write message for techsupport');
            $this->telegram->directPushMessage($this->chatId, $supportKbdLabel, $keyboard);

            
        } else {
            $this->sendToUser($this->icons['ERROR']. ' ' .__('You are not logged in'));
        }
    }


    /**
     * Returns ticket thread ID from context or lates opened thread ID or 0 if not found
     *
     * @return int
     */
    protected function getTicketReplyId() {
        $result=0;
        if ($this->loggedIn) {
            $currentContext=$this->getContext();
            $availableOpenThreads=array();

            $allTickets=$this->getApiData('&tickets=true');
            if (!empty($allTickets)) {
                foreach ($allTickets as $io => $each) {
                    if ($each['from'] == $this->myLogin and empty($each['to']) and $each['replyid']==0 and $each['status']==0) {
                        $availableOpenThreads[$each['id']]=$each['id'];
                    }
                }

                //any open tickets found?
                if (!empty($availableOpenThreads)) {
                    //try to detect current opened thread
                    if (ispos($currentContext, 'viewsupportthread_')) {
                        $currentThreadId=str_replace('viewsupportthread_','',$currentContext);
                        if (isset($availableOpenThreads[$currentThreadId])) {
                            $result=$currentThreadId;
                        } else {
                            //current opened thread not found or closed - getting first available open thread
                            $result=array_shift($availableOpenThreads);
                        }
                    } else {
                        //getting first available open thread
                        $result=array_shift($availableOpenThreads);
                    }
                }
            }
        }
        
        return($result);
    }


    /**
     * Handles support ticket creation or reply
     * 
     * @return void
     */
    protected function actionCreateSupportTicket() {
        if ($this->loggedIn) {

            $currentContext=$this->getContext();
            if ($currentContext=='support' or ispos($currentContext, 'viewsupportthread_')) {
                $replyToTicketId=$this->getTicketReplyId();
                $newTicketText=$this->receivedData['text']; 
                $newTicketText=strip_tags($newTicketText);
                $newTicketText=ubRouting::filters($newTicketText,'safe');
                if (!empty($newTicketText)) {
                    //text length check now is limited by GET request size limit
                    if (mb_strlen($newTicketText, 'UTF-8') <= $this->ticketTextLimit) {
                    //subroutine for new ticket creation or reply to current or any existing opened thread
                    $textEnc=base64_encode($newTicketText);
                    $textEnc=urlencode($textEnc);
                    $callback='&ticketcreate=true&tickettype=support_request&tickettext='.$textEnc;
                    if (!empty($replyToTicketId)) {
                        $callback.='&reply_id='.$replyToTicketId;
                    }
                    $ticketCreationResult=$this->getApiData($callback);
                    if (!empty($ticketCreationResult['created']) and $ticketCreationResult['created']=='success') {
                        $newTicketId=$ticketCreationResult['id'];
                        $creationLabel='';
                        if (empty($replyToTicketId)) {
                            $creationLabel=__('A new technical support request has been created').'# '.$newTicketId;
                        } else {
                            $creationLabel=__('Ticket created').', '.__('as reply to').'# '.$replyToTicketId;
                        }
                        $this->sendToUser($this->icons['GOOD'].' '.$creationLabel);
                        $this->actionSupport();
                    } else {
                        $this->sendToUser($this->icons['ERROR'].' '.__('Ticket creation failed'));
                    }
                } else {
                    $this->sendToUser($this->icons['ERROR'].' '.__('Sorry').', '.__('Message text is too long'));
                }
                } else {
                    $this->sendToUser($this->icons['ERROR'].' '.__('Message text is empty'));
                }
            }
            
          
        } else {
            $this->sendToUser($this->icons['ERROR']. ' ' .__('You are not logged in'));
        }
    }


    protected function actionTesting() {
        
    }


 

//
//    AND I AM GOING TO TEABAG YOUR COOOOOOORPSE!!!!
//

}
