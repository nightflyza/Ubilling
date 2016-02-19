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

    public function __construct() {
        $this->loadUsConfig();
        $this->setOptions();
        $this->loadUsers();
        $this->loadTariffs();
        $this->loadSubscribers();
        $this->loadHistory();
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
        $result.=la_tag('b').__('Attention!').la_tag('b',true).' ';
        $result.=__('When activated subscription account will be charged fee the equivalent value of the subscription.').  la_delimiter();
        if (!empty($this->allTariffs)) {
            foreach ($this->allTariffs as $io => $each) {
                $headerType = ($each['primary']) ? 'mgheaderprimary' : 'mgheader';
                $freePeriodLabel = ($each['freeperiod']) ? la_img('iconz/ok_small.png', __('Available')) : la_img('iconz/unavail_small.png', __('Unavailable'));
                $primaryLabel = ($each['primary']) ? la_img('iconz/ok_small.png') : la_img('iconz/unavail_small.png');
                $tariffInfo = la_tag('div', false, $headerType) . $each['name'] . la_tag('div', true);
                $cells = la_TableCell(la_tag('b') . __('Fee') . la_tag('b', true));
                $cells.= la_TableCell($each['fee'] . ' ' . $this->usConfig['currency']);
                $rows = la_TableRow($cells);
                $cells = la_TableCell(la_tag('b') . __('Free period') . la_tag('b', true));
                $cells.= la_TableCell($freePeriodLabel);
                $rows.= la_TableRow($cells);
                $cells = la_TableCell(la_tag('b') . __('Primary') . la_tag('b', true));
                $cells.= la_TableCell($primaryLabel);
                $rows.= la_TableRow($cells);
                $tariffInfo.=la_TableBody($rows, '100%', 0);
                $tariffInfo.=la_delimiter();


                if ($this->checkBalance()) {
                    if ($this->isUserSubscribed($this->userLogin, $each['id'])) {
                        $subscribeControl = la_Link('?module=megogo&unsubscribe=' . $each['id'], __('Unsubscribe'), false, 'mgunsubcontrol');
                    } else {
                        if ($this->checkUserProtection($each['id'])) {
                            $subscribeControl = la_Link('?module=megogo&subscribe=' . $each['id'], __('Subscribe'), false, 'mgsubcontrol');
                        } else {
                            $subscribeControl = __('The amount of money in your account is not sufficient to process subscription');
                        }
                    }


                    $tariffInfo.=$subscribeControl;
                } else {
                    $tariffInfo.=__('The amount of money in your account is not sufficient to process subscription');
                }

                $result.=la_tag('div', false, 'mgcontainer') . $tariffInfo . la_tag('div', true);
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
     * @return bool
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
        return ($result);
    }

    /**
     * Renders list of available subscribtions
     * 
     * @return string
     */
    public function renderSubscribtions() {
        $result = '';
        if (!empty($this->allSubscribers)) {
            $cells = la_TableCell(__('Date'));
            $cells.= la_TableCell(__('Tariff'));
            $cells.= la_TableCell(__('Active'));
            $cells.= la_TableCell(__('Primary'));
            $cells.= la_TableCell(__('Free period'));
            $rows = la_TableRow($cells, 'row1');

            foreach ($this->allSubscribers as $io => $each) {
                if ($each['login'] == $this->userLogin) {
                    $freePeriodFlag = ($each['freeperiod']) ? la_img('iconz/anread.gif') : la_img('iconz/anunread.gif');
                    $primaryFlag = ($each['primary']) ? la_img('iconz/anread.gif') : la_img('iconz/anunread.gif');
                    $activeFlag = ($each['active']) ? la_img('iconz/anread.gif') : la_img('iconz/anunread.gif');
                    $cells = la_TableCell($each['actdate']);
                    $cells.= la_TableCell(@$this->allTariffs[$each['tariffid']]['name']);
                    $cells.= la_TableCell($activeFlag);
                    $cells.= la_TableCell($primaryFlag);
                    $cells.= la_TableCell($freePeriodFlag);
                    $rows.= la_TableRow($cells, 'row2');
                }
            }

            $result = la_TableBody($rows, '100%', 0);
            $result.= la_tag('br');
            $result.= __('To view the purchased subscription register or log in to Megogo.net, by clicking the button below');
        }
        return ($result);
    }

}

?>