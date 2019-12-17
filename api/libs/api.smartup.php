<?php

/**
 * Basic SmartUP interconnection class
 */
class SmartUP {

    /**
     * Contains all available users data as login=>data
     *
     * @var array
     */
    protected $allUserData = array();

    /**
     * Contains all available paymentIDs as login=>paymentID
     * 
     * @var array
     */
    protected $allPaymenIds = array();

    /**
     * Using of cached data flag.
     *
     * @var bool
     */
    protected $useCaching = true;

    /**
     * System caching abstraction layer placeholder
     *
     * @var object
     */
    protected $cache = '';

    /**
     * Default caching timeout. May be configurable in future.
     *
     * @var int
     */
    protected $cacheTimeout = 86400;

    /**
     * Storage keys etc.
     */
    const PAYID_KEY = 'SMARTUP_PAYIDS';

    /**
     * Creates some magic instance
     */
    public function __construct() {
        $this->initCache();
        $this->loadUserData();
        $this->loadPaymenIds();
    }

    /**
     * Loads all avilable users data from database into protected prop for further usage
     * 
     * @return void
     */
    protected function loadUserData() {
        if ($this->useCaching) {
            $this->allUserData = zb_UserGetAllDataCache();
        } else {
            $this->allUserData = zb_UserGetAllData();
        }
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
     * Loads payment IDs from database for further usage
     * 
     * @return void
     */
    protected function loadPaymenIds() {
        $cachedPaymentIds = $this->cache->get(self::PAYID_KEY, $this->cacheTimeout);
        if (empty($cachedPaymentIds)) {
            $opCustomers = new NyanORM('op_customers');
            $payIdsTmp = $opCustomers->getAll();
            if (!empty($payIdsTmp)) {
                foreach ($payIdsTmp as $io => $each) {
                    $this->allPaymenIds[$each['realid']] = $each['virtualid'];
                }
            }
            //store updated data to cache
            $this->cache->set(self::PAYID_KEY, $this->allPaymenIds, $this->cacheTimeout);
        } else {
            $this->allPaymenIds = $cachedPaymentIds;
        }
    }

    /**
     * Returns some login by assigned IP
     * 
     * @param string $ip
     * 
     * @return string
     */
    protected function getUserByIp($ip) {
        $result = '';
        if (!empty($ip)) {
            if (!empty($this->allUserData)) {
                foreach ($this->allUserData as $io => $each) {
                    if ($each['ip'] == $ip) {
                        $result .= $each['login'];
                        break;
                    }
                }
            }
        }
        return($result);
    }

    /**
     * Returns reply for user and tariff existense
     * 
     * @param string $ip
     * 
     * @return array
     */
    public function getAuthByIP($ip) {
        $result = array();
        $userLogin = $this->getUserByIp($ip);
        if (!empty($userLogin)) {
            $result = array(
                'login' => $userLogin,
                'tp' => $this->allUserData[$userLogin]['Tariff']
            );
        } else {
            //no user with such IP assigned
            $result = array(
                'error' => 'user not exists'
            );
        }
        return($result);
    }

    /**
     * Returns some data by user login
     * 
     * @param string $login
     * 
     * @return array
     */
    public function getUserInfo($login) {
        $result = array();
        if (isset($this->allUserData[$login])) {
            $userData = $this->allUserData[$login];  //fuck memory economy lol :P
            $result = array(
                'fio' => $userData['realname'],
                'balance' => $userData['Cash'],
                'tariff' => $userData['Tariff'],
                'account' => @$this->allPaymenIds[$login]
            );
        } else {
            $result = array(
                'error' => __('User not exists')
            );
        }
        return($result);
    }

    /**
     * Renders data array as JSON encoded string
     * 
     * @param array $data
     * 
     * @return void
     */
    public function renderReply($data) {
        $json = json_encode($data);
        die($json);
    }

}
