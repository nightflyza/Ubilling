<?php


class TrinityTvFrontend {

    /**
     * Contains available TrinityTv service tariffs id=>tariffdata
     *
     * @var array
     */
    protected $allTariffs = array();

    /**
     * Contains available and active TrinityTV service subscriptions as id=>data
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

    const TABLE_SUBS = 'trinitytv_subscribers';
    const TABLE_TARIFFS = 'trinitytv_tariffs';
    const TABLE_DEVICES = 'trinitytv_devices';
    const TABLE_SUSPENDS = 'trinitytv_suspend';
    const TABLE_QUEUE = 'trinitytv_queue';

    public function __construct() {
        $this->loadUsConfig();
        $this->setOptions();
        $this->loadUsers();
        $this->loadTariffs();
        $this->loadSubscribers();
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
    protected function loadTariffs()
    {
        $query = "SELECT * from " . self::TABLE_TARIFFS;
        $tariffs = simple_queryall($query);
        if (!empty($tariffs)) {
            foreach ($tariffs as $tariff) {
                $this->allTariffs[$tariff['id']] = $tariff;
            }
        }
    }

    /**
     * Loads existing subscribers data
     * 
     * @return void
     */
    protected function loadSubscribers()
    {
        $query = "SELECT * from " . self::TABLE_SUBS;
        $subscribers = simple_queryall($query);
        if (!empty($subscribers)) {
            foreach ($subscribers as $subscriber) {
                $this->allSubscribers[$subscriber['id']] = $subscriber;
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
            foreach ($this->allSubscribers as $subscriber) {
                if (($subscriber['login'] == $login) AND $subscriber['tariffid'] == $tariffid AND  $subscriber['active']) {
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
        $result.=la_tag('b') . __('Attention!') . la_tag('b', true) . ' ';
        $result.=__('When activated subscription account will be charged fee the equivalent value of the subscription.') . la_delimiter();

        if (!empty($this->allTariffs)) {
            foreach ($this->allTariffs as $tariff) {
                $freeAppend = la_delimiter();
                $tariffFee = $tariff['fee'];

                $tariffInfo = la_tag('div', false, 'mgheaderprimary') . $tariff['name'] . la_tag('div', true);
                $cells = la_TableCell(la_tag('b') . __('Fee') . la_tag('b', true));
                $cells.= la_TableCell($tariffFee . ' ' . $this->usConfig['currency']);
                $rows = la_TableRow($cells);
                $tariffInfo.=la_TableBody($rows, '100%', 0);
                $tariffInfo.=$freeAppend;

                if ($this->checkBalance()) {
                    if ($this->isUserSubscribed($this->userLogin, $tariff['id'])) {
                        $subscribeControl = la_Link('?module=trinitytv&unsubscribe=' . $tariff['id'], __('Unsubscribe'), false, 'mgunsubcontrol');
                    } else {
                        if ($this->checkUserProtection($tariff['id'])) {
                            $subscribeControl = la_Link('?module=trinitytv&subscribe=' . $tariff['id'], __('Subscribe'), false, 'mgsubcontrol');
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
        $action = $this->apiUrl . '?module=remoteapi&key=' . $this->apiKey . '&action=trinitytvcontrol&param=subscribe&userlogin=' . $this->userLogin . '&tariffid=' . $tariffid;
        @$result = file_get_contents($action);

        return ($result);
    }

    /**
     * Runs default unsubscribtion routine
     * 
     * @return void/string on error
     */
    public function pushUnsubscribeRequest($tariffid) {
        $action = $this->apiUrl . '?module=remoteapi&key=' . $this->apiKey . '&action=trinitytvcontrol&param=unsubscribe&userlogin=' . $this->userLogin . '&tariffid=' . $tariffid;
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
            foreach ($this->allSubscribers as $subscriber) {
                if ($subscriber['login'] == $this->userLogin) {
                    $result = true;
                    break;
                }
            }
        }
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
     * Checks is user protected from his own stupidity?
     * 
     * @param int $tariffId
     * @return bool
     */
    protected function checkUserProtection($tariffId) {
        $tariffId = vf($tariffId, 3);
        $result = true;
        if (isset($this->usConfig['TRINITYTV_PROTECTION'])) {
            if ($this->usConfig['TRINITYTV_PROTECTION']) {
                if (isset($this->allTariffs[$tariffId])) {
                    $tariffFee = $this->allTariffs[$tariffId]['fee'];
                    $tariffData = $this->allTariffs[$tariffId];
                    $userData = $this->allUsers[$this->userLogin];
                    $userBalance = $userData['Cash'];

                    if ($userBalance < $tariffFee) {
                        $result = false;
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
        $iconsPath = zbs_GetCurrentSkinPath($this->usConfig) . 'iconz/';
        if (!empty($this->allSubscribers)) {
            $cells = la_TableCell(__('Date'));
            $cells.= la_TableCell(__('Tariff'));
            $cells.= la_TableCell(__('Active'));
            $rows = la_TableRow($cells, 'row1');

            foreach ($this->allSubscribers as $io => $each) {
                if ($each['login'] == $this->userLogin) {
                    $activeFlag = ($each['active']) ? la_img($iconsPath . 'anread.gif') : la_img($iconsPath . 'anunread.gif');
                    $cells = la_TableCell($each['actdate']);
                    $cells.= la_TableCell(@$this->allTariffs[$each['tariffid']]['name']);
                    $cells.= la_TableCell($activeFlag);
                    $rows.= la_TableRow($cells, 'row2');
                }
            }

            $result = la_TableBody($rows, '100%', 0);
            $result.= la_tag('br');
        }
        return ($result);
    }

}

?>