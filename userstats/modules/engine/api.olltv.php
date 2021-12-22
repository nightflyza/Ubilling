<?php

/**
 * OllTV users frontend basic class
 */
class OllTvInterface {

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
     * Contains service subscriber ID
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
    const URL_ME = '?module=olltv';
    const REQ_BASE = '&action=olltvui&';

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
                $this->subscriberId = $this->subscriberData['id'];
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
            if ($this->subscriberData['tariffid']) {
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
        $result = ($state) ? la_img($iconsPath . 'anread.gif', __('Yes')) : la_img($iconsPath . 'anunread.gif', __('No'));
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
            $mainTariff = @$this->tariffsData[$this->subscriberData['tariffid']];

            if (!empty($mainTariff)) {
                $cells = la_TableCell(__('Active'));
                $cells .= la_TableCell(__('Tariff'));
                $cells .= la_TableCell(__('Primary'));
                $cells .= la_TableCell(__('Fee'));
                $rows = la_TableRow($cells, 'row1');
                $cells = la_TableCell($this->webBoolLed($this->subscriberData['active']));
                $cells .= la_TableCell($mainTariff['name']);
                $cells .= la_TableCell($this->webBoolLed($mainTariff['main']));
                $cells .= la_TableCell($mainTariff['fee'] . ' ' . $this->usConfig['currency']);
                $rows .= la_TableRow($cells, 'row3');

                $additionalTariff = @$this->tariffsData[$this->subscriberData['addtariffid']];
                if ($additionalTariff) {
                    $cells = la_TableCell($this->webBoolLed($this->subscriberData['active']));
                    $cells .= la_TableCell($additionalTariff['name']);
                    $cells .= la_TableCell($this->webBoolLed($additionalTariff['main']));
                    $cells .= la_TableCell($additionalTariff['fee'] . ' ' . $this->usConfig['currency']);
                    $rows .= la_TableRow($cells, 'row3');
                }

                $result .= la_TableBody($rows, '100%', 0, 'resp-table');
            } else {
                $result = __('No subscriptions yet');
            }
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
        $tariffId = ubRouting::filters($tariffId, 'int');
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
                if ($this->subscriberData['tariffid'] == $tariffid OR $this->subscriberData['addtariffid'] == $tariffid) {
                    $result = true;
                }
            }
        }
        return ($result);
    }

    /**
     * Checks have user filled mobile phone number on his profile
     * 
     * @return bool
     */
    public function userHaveMobile() {
        $result = false;
        $phonesDb = new NyanORM('phones');
        $phonesDb->where('login', '=', $this->myLogin);
        $phonesDb->selectable('mobile');
        $mobile = $phonesDb->getAll();
        //TODO: maybe some checks of mobile number validity required
        if (isset($mobile[0])) {
            if (isset($mobile[0]['mobile'])) {
                if (!empty($mobile[0]['mobile'])) {
                    $result = true;
                }
            }
        }

        return($result);
    }

    /**
     * Renders available subscriptions list
     * 
     * @return string
     */
    public function renderSubscribeForm() {
        $result = '';
        $result .= la_tag('b') . __('Attention!') . la_tag('b', true) . ' ';
        $result .= __('When activated subscription account will be charged fee the equivalent value of the subscription.') . '!' . la_delimiter();
        if (!empty($this->tariffsData)) {
            foreach ($this->tariffsData as $serviceId => $tariff) {
                $subControl = '';
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

                    if ($this->isUserSubscribed($tariff['id'])) {
                        $subControl .= la_Link(self::URL_ME . '&unsubscribe=' . $tariff['id'], __('Unsubscribe'), false, 'trinity-button-u');
                        $tariffInfo .= $subControl;
                    } else {
                        if ($this->checkUserProtection($tariff['id'])) {
                            $alertText = __('I have thought well and understand that I activate this service for myself not by chance and completely meaningfully and I am aware of all the consequences.');
                            if ($tariff['id'] == @$this->subscriberData['tariffid']) {
                                $controlLabel = __('Resume');
                            } else {
                                $controlLabel = __('Subscribe');
                            }

                            $subControl .= la_ConfirmDialog(self::URL_ME . '&subscribe=' . $tariff['id'], $controlLabel, $alertText, 'trinity-button-s', self::URL_ME);
                            //hide case of resurrection via additional tariffs
                            if (!$tariff['main'] AND @ !$this->subscriberData['active']) {
                                $subControl = __('Additional services');
                            }

                            //hide another main tariffs subscription if user already have one
                            if ($tariff['main'] AND @ $this->subscriberData['tariffid'] != $tariff['id'] AND @ $this->subscriberData['tariffid']) {
                                $subControl = __('Already subscribed') . ' ' . __('on another tariff');
                            }
                            $tariffInfo .= $subControl;
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
     *  Renders devices of some subscriber
     * 
     * @return string
     */
    public function renderDevices() {
        $result = '';
        $subDevices = $this->getRemoteData('devdata=' . $this->myLogin);
        $devCount = 0;
        if (!empty($subDevices)) {
            $cells = la_TableCell(__('ID'));
            $cells .= la_TableCell(__('Date'));
            $cells .= la_TableCell(__('Serial'));
            $cells .= la_TableCell(__('MAC'));
            $cells .= la_TableCell(__('Code'));

            $rows = la_TableRow($cells, 'row1');

            foreach ($subDevices as $io => $eachDevice) {
                $cells = la_TableCell($eachDevice['ID']);
                $cells .= la_TableCell($eachDevice['date_added']);
                $cells .= la_TableCell($eachDevice['serial_number']);
                $cells .= la_TableCell($eachDevice['mac']);
                $cells .= la_TableCell($eachDevice['binding_code']);
                $rows .= la_TableRow($cells, 'row5');
                $devCount++;
            }
            $result .= la_TableBody($rows, '100%', 0, 'resp-table');
            $result .= la_delimiter();
        }


        if ($this->subscriberData['code']) {
            $containerStyle = 'style="border:1px solid; text-align:center; width:100%; display:block;"';
            $result .= la_tag('span', false, 'resp-table', $containerStyle);
            $result .= __('You can activate your new devices by logging on oll.tv with mobile') . ' ';
            $result .= la_tag('b') . $this->subscriberData['phone'] . la_tag('b', true) . ' ';
            $result .= __('and code') . ' ' . la_tag('b') . $this->subscriberData['code'] . la_tag('b', true);
            $result .= la_delimiter(0);
            $result .= la_tag('span', true);
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
        $request = 'unsub=' . $tariffId . '&sublogin=' . $this->myLogin;
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
