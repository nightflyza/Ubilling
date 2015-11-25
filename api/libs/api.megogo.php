<?php

class MegogoApi {

    /**
     * System alter.ini config stored as array key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Partner ID property via MG_PARTNERID
     *
     * @var string
     */
    protected $partnerId = '';

    /**
     * Users ID prefixes via MG_PREFIX
     *
     * @var string
     */
    protected $prefix = '';

    /**
     * Auth salt value via MG_SALT
     *
     * @var string
     */
    protected $salt = '';

    /**
     * subscribe/unsubscribe API URL
     *
     * @var string 
     */
    protected $urlApi = '';

    /**
     * Authorization API URL
     *
     * @var string
     */
    protected $urlAuth = '';

    public function __construct() {
        $this->loadAlter();
        $this->setOptions();
    }

    /**
     * Loads system alter config into private prop
     * 
     * @return void
     */
    protected function loadAlter() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Sets basic configurable options for further usage
     * 
     * @return void
     */
    protected function setOptions() {
        $this->partnerId = $this->altCfg['MG_PARTNERID'];
        $this->prefix = $this->altCfg['MG_PREFIX'];
        $this->salt = $this->altCfg['MG_SALT'];
        $this->urlApi = 'http://billing.megogo.net/partners/';
        $this->urlAuth = 'http://megogo.net/auth/by_partners/';
    }

    /**
     * Subscribes user to some service
     * 
     * @param string $login Existing user login to subscribe
     * @param string $service Valid serviceid
     * 
     * @return bool
     */
    public function subscribe($login, $service) {
        $result = false;
        $query = $this->urlApi . $this->partnerId . '/subscription/subscribe?userId=' . $this->prefix . $login . '&serviceId=' . $service;
        $queryResult = file_get_contents($query);
        if (!empty($queryResult)) {
            $queryResult = json_decode($queryResult);
            if ($queryResult->successful) {
                $result = true;
            }
        }
        return ($result);
    }

    /**
     * Unsubscribes user for some service
     * 
     * @param string $login Existing user login to subscribe
     * @param string $service Valid serviceid
     * 
     * @return bool
     */
    public function unsubscribe($login, $service) {
        $result = false;
        $query = $this->urlApi . $this->partnerId . '/subscription/unsubscribe?userId=' . $this->prefix . $login . '&serviceId=' . $service;
        $queryResult = file_get_contents($query);
        if (!empty($queryResult)) {
            $queryResult = json_decode($queryResult);
            if ($queryResult->successful) {
                $result = true;
            }
        }
        return ($result);
    }

    /**
     * Returns auth codes
     * 
     * @param string $login Existing user login
     * @return strig
     */
    public function authCode($login) {
        $result = '';
        $hashData = $this->prefix . $login . $this->partnerId . $this->salt;
        $token = md5($hashData);
        $result = $this->urlAuth . 'dialog?isdn=' . $this->prefix . $login . '&partner_key=' . $this->partnerId . '&token=' . $token;
        return ($result);
    }

}

class MegogoInterface {
    
}

?>