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
     * @param $login
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
     * Get subscriber devices
     *
     * @return array
     */
    private function getSubscriberDevices() {
        $result = array();

        $subscriberId = $this->getSubscriberId($this->userLogin);

        $query = "SELECT * from `" . self::TABLE_DEVICES . "` WHERE `subscriber_id` = " . $subscriberId;
        $devices = simple_queryall($query);

        if (!empty($devices)) {
            foreach ($devices AS $device) {
                $result[$device['id']] = $device;
            }
        }

        return $result;
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
     * Returns local subscriber ID from database
     *
     * @param string $userLogin
     *
     * @return int
     */
    public function getSubscriberId($userLogin) {
        $result = '';
        if (!empty($this->allSubscribers)) {
            foreach ($this->allSubscribers as $subscriber) {
                if ($subscriber['login'] == $userLogin) {
                    $result = $subscriber['id'];
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
        $result.=la_tag('b') . __('Attention!') . la_tag('b', true) . ' ';
        $result.=__('When activated subscription account will be charged fee the equivalent value of the subscription.') . la_delimiter();

        if (!empty($this->allTariffs)) {
            foreach ($this->allTariffs as $tariff) {

                $tariffFee = $tariff['fee'];

                $tariffInfo = la_tag('div', false, 'trinity-col'). la_tag('div', false, 'trinity-bl1');

                $tariffInfo .= la_tag('div', false, 'trinity-price');
                $tariffInfo .= la_tag('b', false, 's') . $tariffFee . la_tag('b', true, 's');
                $tariffInfo .= la_tag('sup', false) .  $this->usConfig['currency'] . ' <br> ' .__('per month'). la_tag('sup', true);
                $tariffInfo .= la_tag('div', true, 'trinity-price');


                $tariffInfo .= la_tag('div', false, 'trinity-green s') . $tariff['name'] . la_tag('div', true, 'trinity-green s');
                $tariffInfo .= '<br/>';

                if(!empty($tariff['description'])){
                    $desc= $tariff['description'];
                }else{
                    $desc= 'Описание тарифа: <br> '.$tariff['name'].'<br><br>';
                }
                $tariffInfo .= la_tag('div', false, 'trinity-list') . $desc . la_tag('div', true, 'trinity-list');


                if ($this->checkBalance()) {

                    if ($this->isUserSubscribed($this->userLogin, $tariff['id'])) {
                        $tariffInfo .= la_Link('?module=trinitytv&unsubscribe=' . $tariff['id'], __('Unsubscribe'), false, 'trinity-button-u');
                    }else{
                        if ($this->checkUserProtection($tariff['id'])) {
                            $tariffInfo .= la_Link('?module=trinitytv&subscribe=' . $tariff['id'], __('Subscribe'), false, 'trinity-button-s');
                        } else {
                            $tariffInfo .= la_tag('div', false, 'trinity-list') .  __('The amount of money in your account is not sufficient to process subscription') . la_tag('div', true, 'trinity-list');
                        }
                    }
                } else {
                    $tariffInfo .= la_tag('div', false, 'trinity-list') .  __('The amount of money in your account is not sufficient to process subscription') . la_tag('div', true, 'trinity-list');
                }

                $tariffInfo .= la_tag('div', true, 'trinity-bl1'). la_tag('div', true, 'trinity-col');


                $result.= $tariffInfo;
            }
        }
        return ($result);
    }

    /**
     * Runs default add device mac routine
     *
     * @param $mac
     * @return bool|string
     */
    public function pushDeviceAddMacRequest($mac) {
        $action = $this->apiUrl . '?module=remoteapi&key=' . $this->apiKey . '&action=trinitytvcontrol&param=adddevice&userlogin=' . $this->userLogin . '&mac=' . $mac;
        @$result = file_get_contents($action);

        return ($result);
    }

    /**
     * Runs default add device by code routine
     *
     * @param $code
     * @return bool|string
     */
    public function pushDeviceAddCodeRequest($code) {
      $action = $this->apiUrl . '?module=remoteapi&key=' . $this->apiKey . '&action=trinitytvcontrol&param=adddevice&userlogin=' . $this->userLogin . '&code=' . $code;
      @$result = file_get_contents($action);

        return ($result);
    }

    /**
     * Runs default delete device by code routine
     *
     * @param $mac
     * @return bool|string
     */
    public function pushDeviceDeleteRequest($mac) {
        $action = $this->apiUrl . '?module=remoteapi&key=' . $this->apiKey . '&action=trinitytvcontrol&param=deldevice&userlogin=' . $this->userLogin . '&mac=' . $mac;
        @$result = file_get_contents($action);

        return ($result);
    }

    /**
     * Runs default subscribtion routine
     *
     * @param $tariffid
     * @return bool|string
     */
    public function pushSubscribeRequest($tariffid) {
        $action = $this->apiUrl . '?module=remoteapi&key=' . $this->apiKey . '&action=trinitytvcontrol&param=subscribe&userlogin=' . $this->userLogin . '&tariffid=' . $tariffid;
        @$result = file_get_contents($action);

        return ($result);
    }

    /**
     * Runs default unsubscribtion routine
     *
     * @param $tariffid
     * @return bool|string
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
            $cells = la_TableCell(__('Tariff'));
            $cells .= la_TableCell(__('Date'));
            $cells .= la_TableCell(__('Active'));
            $rows = la_TableRow($cells, 'row1');

            foreach ($this->allSubscribers as $io => $each) {
                if ($each['login'] == $this->userLogin) {
                    $cells= la_TableCell(@$this->allTariffs[$each['tariffid']]['name']);
                    $activeFlag = ($each['active']) ? la_img($iconsPath . 'anread.gif') : la_img($iconsPath . 'anunread.gif');
                    $cells .= la_TableCell($each['actdate']);
                    $cells .= la_TableCell($activeFlag);
                    $rows .= la_TableRow($cells, 'row2');
                }
            }

            $result = la_TableBody($rows, '100%', 0);
            $result.= la_tag('br');
        }
        return ($result);
    }


    /**
     * Return device
     *
     * @return string
     */
    public function renderDevices(){
        $result = '';
        if (!empty($this->userLogin)) {

            //available devices
            $devices = $this->getSubscriberDevices();

            // Add device
            $result .= la_modal( __('Assign device by MAC') , __('Assign device'), $this->renderDeviceAddForm(), 'trinity-button', 260,160);

            // Add device by MAC
            $result .= la_modal( __('Assign device by Code') , __('Assign device'), $this->renderDeviceByCodeAddForm(), 'trinity-button',  260,160);

            $result .= "<br><br> ";

            $cells = la_TableCell(__('MAC address'));
            $cells .= la_TableCell(__('Date'));
            $cells .= la_TableCell(__('Actions'));
            $rows = la_TableRow($cells, 'row1');

            if (!empty($devices)) {
                foreach ($devices as $device) {

                    $cells = la_TableCell($device['mac']);
                    $cells .= la_TableCell($device['created_at']);

                    $deviceControls = la_JSAlert('?module=trinitytv&deletedevice=' . $device['mac'], __('Delete'), __('Are you sure') . '?');
                    $cells.= la_TableCell($deviceControls);

                    $rows .= la_TableRow($cells, 'row3');
                }
            }

            $result .= la_TableBody($rows, '100%', 0, 'sortable');

        }
        return ($result);
    }


    /**
     * Renders manual device assign form
     *
     * @return string
     */
    protected function renderDeviceAddForm() {
        $result = '';
        $inputs = la_HiddenInput('device', 'true');
        $inputs .= la_TextInput('mac', __('MAC'), '', true, 20, 'mac');
        $inputs .= la_Submit(__('Assign device'));
        $result .= la_Form('', 'POST', $inputs, 'glamour', '' , false);
        return ($result);
    }

    /**
     * Renders manual device assign form
     *
     * @return string
     */
    protected function renderDeviceByCodeAddForm() {
        $result = '';
        $inputs = la_HiddenInput('device', 'true');
        $inputs .= la_TextInput('code', __('Code'), '', true, 20);
        $inputs .= la_Submit(__('Assign device'));
        $result .= la_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }


}

?>