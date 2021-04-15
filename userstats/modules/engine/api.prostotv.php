<?php

/**
 * ProstoTV users frontend basic class
 */
class PTVInterface {

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
     * Contains available tariffs data as serviceId=>tariffData
     *
     * @var array
     */
    protected $tariffsData = array();

    /**
     * Some predefined routes/URLs etc..
     */
    const REQ_BASE = '&action=ptvui&';

    /**
     * Creates new instance
     * 
     * @param string $userLogin
     */
    public function __construct($userLogin) {
        if (!empty($userLogin)) {
            $this->loadConfig();
            $this->setLogin($userLogin);
            $this->subscriberData = $this->getSubscriberData();
            if (!empty($this->subscriberData)) {
                $this->subscriberId = $this->subscriberData['subscriberid'];
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
            $mainTariff = $this->tariffsData[$this->subscriberData['maintariff']];

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
            $result .= la_TableBody($rows, '100%', 0);
        } else {
            $result = __('No subscriptions yet');
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
        $result = '';
        $result .= la_tag('b') . __('Attention!') . la_tag('b', true) . ' ';
        $result .= __('When activated subscription account will be charged fee the equivalent value of the subscription.') . la_delimiter();
        if (!empty($this->tariffsData)) {
            foreach ($this->tariffsData as $serviceId => $tariffData) {
                
            }
        }
        return($result);
    }

}
