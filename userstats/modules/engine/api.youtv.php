<?php

/**
 * YouTV users frontend basic class
 */
class YTVInterface {

    /**
     * Contains current instance user login
     *
     * @var string
     */
    protected $myLogin = '';

    /**
     * Contains userstats config as key=>value
     *
     * @var array
     */
    protected $usConfig = array();

    /**
     * Contains service-side subscriber ID
     *
     * @var int
     */
    protected $subscriberId = 0;

    /**
     * Contains current instance subscriber data
     *
     * @var array
     */
    protected $subscriberData = array();

    /**
     * Contains remote subscriber full data
     *
     * @var array
     */
    protected $fullData = array();

    /**
     * Contains available tariffs data as serviceId=>tariffData
     *
     * @var array
     */
    protected $tariffsData = array();

    /**
     * Contains all users data as login=>userdata
     *
     * @var string
     */
    protected $allUsers = array();

    /**
     * Some predefined routes/URLs etc..
     */
    const URL_ME = '?module=omyoutv';
    const REQ_BASE = '&action=youtvui&';

    /**
     * Creates new instance
     * 
     * @param string $userLogin
     */
    public function __construct($userLogin) {
        if (!empty($userLogin)) {
            $this->loadConfig();
            $this->setLogin($userLogin);
            $this->loadUsers();
            $this->subscriberData = $this->getSubscriberData();
            if (!empty($this->subscriberData)) {
                $this->subscriberId = $this->subscriberData['subscriberid'];
                $this->fullData = $this->getFullData();
            }
            $this->tariffsData = $this->getTariffsData();
        } else {
            die('ERROR:NO_USER_LOGIN');
        }
    }

    /**
     * Sets current instance user login
     * 
     * @param string $userLogin
     * 
     * @return void
     */
    protected function setLogin($userLogin) {
        $this->myLogin = $userLogin;
    }

    /**
     * Preloads userstats config to protected property
     * 
     * @global array $us_config
     * 
     * @return void
     */
    protected function loadConfig() {
        global $us_config;
        $this->usConfig = $us_config;
    }

    /**
     * Performs some RemoteAPI request and returns its results as array
     * 
     * @param string $request
     * 
     * @return array/bool on error
     */
    protected function getRemoteData($request) {
        $result = false;
        if (!empty($request)) {
            $requestUrl = self::REQ_BASE . $request;
            $rawReply = zbs_remoteApiRequest($requestUrl);

            if (!empty($rawReply)) {
                $result = json_decode($rawReply, true);
            }
        }
        return($result);
    }

    /**
     * Returns some subscriber data assigned to s
     * 
     * @return array
     */
    protected function getSubscriberData() {
        $request = 'subdata=' . $this->myLogin;
        $result = $this->getRemoteData($request);

        return($result);
    }

    /**
     * Returns current subscriberId or void if user is unregistered yet.
     * 
     * @return int/void
     */
    public function getSubscriberId() {
        return($this->subscriberId);
    }

    /**
     * Checks is user use service?
     * 
     * @return bool
     */
    public function userUseService() {
        $result = false;
        if (!empty($this->subscriberData)) {
            if ($this->subscriberData['maintariff']) {
                $result = true;
            }
        }
        return($result);
    }

    /**
     * Returns available tariffs data
     * 
     * @return array
     */
    protected function getTariffsData() {
        $request = 'tardata=true';
        $result = $this->getRemoteData($request);
        return($result);
    }

    /**
     * Returns full subscriber data
     * 
     * @return array
     */
    protected function getFullData() {
        $request = 'fulldata=' . $this->myLogin;
        $result = $this->getRemoteData($request);
        return($result);
    }

    /**
     * Loads available users data from database
     * 
     * @return void
     */
    protected function loadUsers() {
        $query = "SELECT * from `users` WHERE `login`='" . $this->myLogin . "'";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allUsers[$each['login']] = $each;
            }
        }
    }

    /**
     * Renders standard bool led
     * 
     * @param mixed $state
     * 
     * @return string
     */
    protected function webBoolLed($state) {
        $iconsPath = zbs_GetCurrentSkinPath($this->usConfig) . 'iconz/';
        $result = ($state) ? la_img($iconsPath . 'anread.gif') : la_img($iconsPath . 'anunread.gif');
        return($result);
    }

    /**
     * Renders current subscription details
     * 
     * @return string
     */
    public function renderSubscriptionDetails() {
        $result = '';

        if (!empty($this->subscriberData)) {
            $mainTariff = @$this->tariffsData[$this->subscriberData['maintariff']];

            $cells = la_TableCell(__('Active'));
            $cells .= la_TableCell(__('Tariff'));
            $cells .= la_TableCell(__('Primary'));
            $cells .= la_TableCell(__('Fee'));
            $rows = la_TableRow($cells, 'row1');
            if (!empty($mainTariff)) {
                $cells = la_TableCell($this->webBoolLed($this->subscriberData['active']));
                $cells .= la_TableCell($mainTariff['name']);
                $cells .= la_TableCell($this->webBoolLed($mainTariff['main']));
                $cells .= la_TableCell($mainTariff['fee'] . ' ' . $this->usConfig['currency']);
                $rows .= la_TableRow($cells, 'row1');
            }
            $result .= la_TableBody($rows, '100%', 0, 'resp-table');
        } else {
            $result = __('No subscriptions yet');
        }

        return($result);
    }

    /**
     * Check user balance for subscribtion availability
     * 
     * @return bool
     */
    protected function checkBalance() {
        $result = false;
        if (!empty($this->myLogin)) {
            if (isset($this->allUsers[$this->myLogin])) {
                $userBalance = $this->allUsers[$this->myLogin]['Cash'];
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
     * 
     * @return bool
     */
    protected function checkUserProtection($tariffId) {
        $tariffId = vf($tariffId, 3);
        $result = true;

        if (isset($this->tariffsData[$tariffId])) {
            $tariffFee = $this->tariffsData[$tariffId]['fee'];
            $userData = $this->allUsers[$this->myLogin];
            $userBalance = $userData['Cash'];
            if ($userBalance < $tariffFee) {
                $result = false;
            }
        } else {
            $result = false;
        }
        return ($result);
    }

    /**
     * Checks is user subscribed for some tariff or not?
     * 
     * @param int $tariffid
     * 
     * @return bool
     */
    protected function isUserSubscribed($tariffid) {
        $result = false;
        if (!empty($this->subscriberData)) {
            if ($this->subscriberData['active']) {
                if ($this->subscriberData['maintariff'] == $tariffid) {
                    $result = true;
                }
            }
        }
        return ($result);
    }

    /**
     * Renders available subscriptions list
     * 
     * @return string
     */
    public function renderSubscribeForm() {
        $result = '';
        $result = '';
        $result .= la_tag('b') . __('Attention!') . la_tag('b', true) . ' ';
        $result .= __('When activated subscription account will be charged fee the equivalent value of the subscription.') . la_delimiter();
        if (!empty($this->tariffsData)) {
            foreach ($this->tariffsData as $serviceId => $tariff) {

                $tariffFee = $tariff['fee'];

                $tariffInfo = la_tag('div', false, 'trinity-col') . la_tag('div', false, 'trinity-bl1');

                $tariffInfo .= la_tag('div', false, 'trinity-price');
                $tariffInfo .= la_tag('b', false, 's') . $tariffFee . la_tag('b', true, 's');
                $tariffInfo .= la_tag('sup', false) . $this->usConfig['currency'] . ' ' . la_tag('br') . ' ' . __('per month') . la_tag('sup', true);
                $tariffInfo .= la_tag('div', true, 'trinity-price');


                $tariffInfo .= la_tag('div', false, 'trinity-green s') . $tariff['name'] . la_tag('div', true, 'trinity-green s');
                $tariffInfo .= la_tag('br');

                if (!empty($tariff['chans'])) {
                    $desc = $tariff['chans'];
                } else {
                    $desc = '';
                }

                $descriptionLabel = $desc;

                $tariffInfo .= la_tag('div', false, 'trinity-list') . $descriptionLabel . la_tag('div', true, 'trinity-list');

                if ($this->checkBalance()) {

                    if ($this->isUserSubscribed($tariff['serviceid'])) {
                        $tariffInfo .= la_Link(self::URL_ME . '&unsubscribe=' . $tariff['serviceid'], __('Unsubscribe'), false, 'trinity-button-u');
                    } else {
                        if ($this->checkUserProtection($tariff['serviceid'])) {
                            $alertText = __('I have thought well and understand that I activate this service for myself not by chance and completely meaningfully and I am aware of all the consequences.');
                            $tariffInfo .= la_ConfirmDialog(self::URL_ME . '&subscribe=' . $tariff['serviceid'], __('Subscribe'), $alertText, 'trinity-button-s', self::URL_ME);
                        } else {
                            $tariffInfo .= la_tag('div', false, 'trinity-list') . __('The amount of money in your account is not sufficient to process subscription') . la_tag('div', true, 'trinity-list');
                        }
                    }
                } else {
                    $tariffInfo .= la_tag('div', false, 'trinity-list') . __('The amount of money in your account is not sufficient to process subscription') . la_tag('div', true, 'trinity-list');
                }

                $tariffInfo .= la_tag('div', true, 'trinity-bl1') . la_tag('div', true, 'trinity-col');


                $result .= $tariffInfo;
            }
        }
        return($result);
    }


    /**
     * Deactivates user service due deleting of tariff
     * 
     * @param int $tariffId
     * 
     * @return void
     */
    public function unsubscribe($tariffId) {
        $request = 'unsub=' . $tariffId . '&subid=' . $this->subscriberId;
        $this->getRemoteData($request);
    }

    /**
     * Activates new service for user
     * 
     * @param int $tariffId
     * 
     * @return void
     */
    public function subscribe($tariffId) {
        $request = 'subserv=' . $tariffId . '&sublogin=' . $this->myLogin;
        $this->getRemoteData($request);
    }

}
