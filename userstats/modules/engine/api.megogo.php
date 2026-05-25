<?php

class MegogoFrontend {

    /**
     * Contains available megogo service tariffs id=>tariffdata
     *
     * @var array
     */
    protected $allTariffs = array();

    /**
     * Contains available and active megogo service subscriptions as id=>data
     *
     * @var array
     */
    protected $allSubscribers = array();

    /**
     * Contains all subscribtions history by all of users id=>data
     *
     * @var array
     */
    protected $allHistory = array();

    /**
     * Contains all of internet users data as login=>data
     *
     * @var array
     */
    protected $allUsers = array();

    /**
     * Contains system config as key=>value
     *
     * @var array
     */
    protected $usConfig = array();

    /**
     * Current instance user login
     *
     * @var string
     */
    protected $userLogin = '';

    /**
     * Contains Ubilling RemoteAPI URL
     *
     * @var string
     */
    protected $apiUrl = '';

    /**
     * Contains Ubilling RemoteAPI Key
     *
     * @var string
     */
    protected $apiKey = '';

    /**
     * Web-auth data database abstraction layer placeholder
     *
     * @var object
     */
    protected $credentialsDb = '';

    public function __construct() {
        $this->loadUsConfig();
        $this->setOptions();
        $this->loadUsers();
        $this->loadTariffs();
        $this->loadSubscribers();
        $this->loadHistory();
        $this->initCredentials();
    }

    /**
     * Sets current user login
     * 
     * @return void
     */
    public function setLogin($login) {
        $this->userLogin = $login;
    }

    /**
     * Loads userstats config into protected usConfig variable
     * 
     * @return void
     */
    protected function loadUsConfig() {
        $this->usConfig = zbs_LoadConfig();
    }

    /**
     * Sets required object options
     * 
     * @return void
     */
    protected function setOptions() {
        $this->apiUrl = $this->usConfig['API_URL'];
        $this->apiKey = $this->usConfig['API_KEY'];
    }

    /**
     * Loads existing tariffs from database for further usage
     * 
     * @return void
     */
    protected function loadTariffs() {
        $query = "SELECT * from `mg_tariffs` ORDER BY `primary` DESC, `fee` ASC";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allTariffs[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads existing subscribers data
     * 
     * @return void
     */
    protected function loadSubscribers() {
        $query = "SELECT * from `mg_subscribers`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allSubscribers[$each['id']] = $each;
            }
        }
    }

    /**
     * Performs init of credentials database abscration layer
     * 
     * @return void
     */
    protected function initCredentials() {
        $this->credentialsDb = new NyanORM('mg_credentials');
    }

    /**
     * Loads existing subscribers data
     * 
     * @return void
     */
    protected function loadHistory() {
        $query = "SELECT * from `mg_history`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allHistory[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads available users from database
     * 
     * @return void
     */
    protected function loadUsers() {
        $query = "SELECT * from `users`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allUsers[$each['login']] = $each;
            }
        }
    }

    /**
     * Checks is user subscribed for some tariff or not?
     * 
     * @param string $login
     * @param int $tariffid
     * 
     * @return bool
     */
    protected function isUserSubscribed($login, $tariffid) {
        $result = false;
        if (!empty($this->allSubscribers)) {
            foreach ($this->allSubscribers as $io => $each) {
                if (($each['login'] == $login) AND ( $each['tariffid'] == $tariffid)) {
                    $result = true;
                    break;
                }
            }
        }
        return ($result);
    }

    /**
     * Renders tariffs list with subscribtion form
     * 
     * @return string
     */
    public function renderSubscribeForm() {
        $result = '';
        $iconsPath = zbs_GetCurrentSkinPath($this->usConfig) . 'iconz/';
        $result .= wf_tag('b') . __('Attention!') . wf_tag('b', true) . ' ';
        $result .= __('When activated subscription account will be charged fee the equivalent value of the subscription.') . wf_delimiter();
        if (!empty($this->allTariffs)) {
            foreach ($this->allTariffs as $io => $each) {
                $headerType = ($each['primary']) ? 'mgheaderprimary' : 'mgheader';
                $freePeriodLabel = ($each['freeperiod']) ? wf_img($iconsPath . 'ok_small.png', __('Available')) : wf_img($iconsPath . 'unavail_small.png', __('Unavailable'));
                $freeAppend = wf_delimiter();
                $tariffFee = $each['fee'];
                if ($each['freeperiod']) {
                    if (!$this->checkFreePeriodAvail($this->userLogin)) {
                        $freePeriodLabel = wf_img($iconsPath . 'unavail_small.png', __('Unavailable'));
                        $freeAppend = wf_delimiter();
                    } else {
                        $freeAppend = wf_tag('center') . wf_tag('strong') . __('Try it now for free!') . wf_tag('strong', true) . wf_tag('center', true) . wf_tag('br');
                        $tariffFee = 0;
                    }
                }

                $primaryLabel = ($each['primary']) ? wf_img($iconsPath . 'ok_small.png') : wf_img($iconsPath . 'unavail_small.png');
                $tariffInfo = wf_tag('div', false, $headerType) . $each['name'] . wf_tag('div', true);
                $cells = wf_TableCell(wf_tag('b') . __('Fee') . wf_tag('b', true));
                $cells .= wf_TableCell($tariffFee . ' ' . $this->usConfig['currency']);
                $rows = wf_TableRow($cells);
                $cells = wf_TableCell(wf_tag('b') . __('Free period') . wf_tag('b', true));
                $cells .= wf_TableCell($freePeriodLabel);
                $rows .= wf_TableRow($cells);
                $cells = wf_TableCell(wf_tag('b') . __('Primary') . wf_tag('b', true));
                $cells .= wf_TableCell($primaryLabel);
                $rows .= wf_TableRow($cells);
                $tariffInfo .= wf_TableBody($rows, '100%', 0);
                $tariffInfo .= $freeAppend;



                if ($this->checkBalance()) {
                    if ($this->isUserSubscribed($this->userLogin, $each['id'])) {
                        $subscribeControl = wf_Link('?module=megogo&unsubscribe=' . $each['id'], __('Unsubscribe'), false, 'mgunsubcontrol');
                    } else {
                        $userProtection = $this->checkUserProtection($each['id']);
                        if ($userProtection) {
                            $alertText = __('I have thought well and understand that I activate this service for myself not by chance and completely meaningfully and I am aware of all the consequences.');
                            $subscribeControl = wf_ConfirmDialog('?module=megogo&subscribe=' . $each['id'], __('Subscribe'), $alertText, 'mgsubcontrol', '?module=megogo');
                        } else {
                            //money issues
                            if ($userProtection === false) {
                                $subscribeControl = __('The amount of money in your account is not sufficient to process subscription');
                            }
                            //tariff isnt allowed
                            if ($userProtection === 0) {
                                $subscribeControl = __('Your tariff does not provide this service');
                            }
                        }
                    }


                    $tariffInfo .= $subscribeControl;
                } else {
                    $tariffInfo .= __('The amount of money in your account is not sufficient to process subscription');
                }

                $result .= wf_tag('div', false, 'mgcontainer') . $tariffInfo . wf_tag('div', true);
            }
        }
        return ($result);
    }

    /**
     * Runs default subscribtion routine
     * 
     * @return void/string on error
     */
    public function pushSubscribeRequest($tariffid) {
        $action = $this->apiUrl . '?module=remoteapi&key=' . $this->apiKey . '&action=mgcontrol&param=subscribe&userlogin=' . $this->userLogin . '&tariffid=' . $tariffid;
        @$result = file_get_contents($action);
        return ($result);
    }

    /**
     * Runs default unsubscribtion routine
     * 
     * @return void/string on error
     */
    public function pushUnsubscribeRequest($tariffid) {
        $action = $this->apiUrl . '?module=remoteapi&key=' . $this->apiKey . '&action=mgcontrol&param=unsubscribe&userlogin=' . $this->userLogin . '&tariffid=' . $tariffid;
        @$result = file_get_contents($action);
        return ($result);
    }

    /**
     * Checks have  current user any subscribtions?
     * 
     * @return bool
     */
    public function haveSubscribtions() {
        $result = false;
        if (!empty($this->allSubscribers)) {
            foreach ($this->allSubscribers as $io => $each) {
                if ($each['login'] == $this->userLogin) {
                    $result = true;
                    break;
                }
            }
        }
        return ($result);
    }

    /**
     * Gets auth URL via remote API
     * 
     * @return string
     */
    public function getAuthButtonURL() {
        $result = '';
        $action = $this->apiUrl . '?module=remoteapi&key=' . $this->apiKey . '&action=mgcontrol&param=auth&userlogin=' . $this->userLogin;
        $result = file_get_contents($action);
        return ($result);
    }

    /**
     * Check user balance for subscribtion availability
     * 
     * @return bool
     */
    protected function checkBalance() {
        $result = false;
        if (!empty($this->userLogin)) {
            if (isset($this->allUsers[$this->userLogin])) {
                $userData = $this->allUsers[$this->userLogin];
                $userBalance = $this->allUsers[$this->userLogin]['Cash'];
                if ($userBalance >= 0) {
                    $result = true;
                }
            }
        }
        return ($result);
    }

    /**
     * Checks free period availability for user
     * 
     * @param string $login
     * 
     * @return bool
     */
    protected function checkFreePeriodAvail($login) {
        $result = true;
        if (!empty($this->allHistory)) {
            foreach ($this->allHistory as $io => $each) {
                if (($each['login'] == $login) AND ( $each['freeperiod'] == 1)) {
                    $result = false;
                    break;
                }
            }
        }
        return ($result);
    }

    /**
     * Checks is user protected from his own stupidity?
     * 
     * @param int $tariffId
     * 
     * @return bool/int true - everything is ok, false - lack of money / 0 - tariff issues
     */
    protected function checkUserProtection($tariffId) {
        $tariffId = vf($tariffId, 3);
        $result = true;
        if (isset($this->usConfig['MG_PROTECTION'])) {
            if ($this->usConfig['MG_PROTECTION']) {
                if (isset($this->allTariffs[$tariffId])) {
                    $tariffFee = $this->allTariffs[$tariffId]['fee'];
                    $tariffData = $this->allTariffs[$tariffId];
                    $userData = $this->allUsers[$this->userLogin];
                    $userBalance = $userData['Cash'];

                    if ($tariffData['freeperiod']) {
                        if ($this->checkFreePeriodAvail($this->userLogin)) {
                            $result = true;
                        } else {
                            if ($userBalance < $tariffFee) {
                                $result = false;
                            }
                        }
                    } else {
                        if ($userBalance < $tariffFee) {
                            $result = false;
                        }
                    }
                } else {
                    $result = false;
                }
            }
        }

        //per-tariff protection controls
        if (isset($this->usConfig['MG_TARIFFSALLOWED'])) {
            if (!empty($this->usConfig['MG_TARIFFSALLOWED'])) {
                $tariffsAllowed = explode(',', $this->usConfig['MG_TARIFFSALLOWED']);
                $tariffsAllowed = array_flip($tariffsAllowed);
                $userTariff = $this->allUsers[$this->userLogin]['Tariff'];
                if (!isset($tariffsAllowed[$userTariff])) {
                    $result = 0;
                }
            }
        }
        return ($result);
    }

    /**
     * Renders list of available subscribtions
     * 
     * @return string
     */
    public function renderSubscribtions() {
        $result = '';
        $iconsPath = zbs_GetCurrentSkinPath($this->usConfig) . 'iconz/';
        if (!empty($this->allSubscribers)) {
            $cells = wf_TableCell(__('Date'));
            $cells .= wf_TableCell(__('Tariff'));
            $cells .= wf_TableCell(__('Active'));
            $cells .= wf_TableCell(__('Primary'));
            $cells .= wf_TableCell(__('Free period'));
            $rows = wf_TableRow($cells, 'row1');

            foreach ($this->allSubscribers as $io => $each) {
                if ($each['login'] == $this->userLogin) {
                    $freePeriodFlag = ($each['freeperiod']) ? wf_img($iconsPath . 'anread.gif') : wf_img($iconsPath . 'anunread.gif');
                    $primaryFlag = ($each['primary']) ? wf_img($iconsPath . 'anread.gif') : wf_img($iconsPath . 'anunread.gif');
                    $activeFlag = ($each['active']) ? wf_img($iconsPath . 'anread.gif') : wf_img($iconsPath . 'anunread.gif');
                    $cells = wf_TableCell($each['actdate']);
                    $cells .= wf_TableCell(@$this->allTariffs[$each['tariffid']]['name']);
                    $cells .= wf_TableCell($activeFlag);
                    $cells .= wf_TableCell($primaryFlag);
                    $cells .= wf_TableCell($freePeriodFlag);
                    $rows .= wf_TableRow($cells, 'row2');
                }
            }

            $result = wf_TableBody($rows, '100%', 0, 'resp-table');
            $result .= wf_tag('br');
            //$result .= __('To view the purchased subscription register or log in to Megogo.net, by clicking the button below');
        }
        return ($result);
    }

    /**
     * Renders user credentials for web-auth on megogo service
     * 
     * @return string/void
     */
    public function renderCredentials() {
        $result = '';
        $this->credentialsDb->where('login', '=', $this->userLogin);
        $userCredentials = $this->credentialsDb->getAll();
        if (!empty($userCredentials)) {
            $userCredentials = $userCredentials[0];
            $containerStyle = 'style="border:1px solid; text-align:center; width:100%; display:block;"';
            $result .= wf_tag('span', false, 'resp-table', $containerStyle);
            $result .= __('Your login and password to usage with MEGOGO are') . ' ' . wf_delimiter(1);
            $result .= __('Login') . ': ' . wf_tag('b') . $userCredentials['email'] . wf_tag('b', true) . wf_tag('br');
            $result .= __('Password') . ': ' . wf_tag('b') . $userCredentials['password'] . wf_tag('b', true) . wf_delimiter(1);
            $result .= __('To start using the MEGOGO service, click the button') . ' ' . wf_Link('http://megogo.net/login', 'Continue', false, 'anreadbutton', 'target=_blank');
            $result .= wf_delimiter(1);
            $result .= wf_tag('span', true);
        }
        return($result);
    }

}

?>