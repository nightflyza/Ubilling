<?php

/**
 * Basic payments-based discounts implementation
 */
class Discounts {

    /**
     * Contains all available user discounts as login=>discountData
     *
     * @var array
     */
    protected $allDiscounts = array();

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Discounts bindings database abstraction layer placeholder
     *
     * @var object
     */
    protected $discountsDb = '';

    /**
     * System messages helper placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * System caching object placeholder
     *
     * @var object
     */
    protected $cache = '';

    /**
     * Some predefined stuff here
     */
    const DISCOUNTS_TABLE = 'discounts';
    const PAYMENTS_TABLE = 'payments';
    const PROUTE_PERCENT = 'setdiscountpercent';
    const PROUTE_LOGIN = 'setdiscountlogin';
    const CACHE_KEY = 'DISCOUNTS';
    const CACHE_TIMEOUT = 86400;

    public function __construct() {
        $this->initMessages();
        $this->loadConfig();
        $this->initCache();
        $this->initDb();
        $this->loadAllDiscounts();
    }

    /**
     * Inits system messages helper
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Inits system caching instance for further usage
     * 
     * @return
     */
    protected function initCache() {
        $this->cache = new UbillingCache();
    }

    /**
     * Loads required configs data
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfig() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Inits database abstraction layer for further usage
     * 
     * @return void
     */
    protected function initDb() {
        $this->discountsDb = new NyanORM(self::DISCOUNTS_TABLE);
    }

    /**
     * Loads all available discounts data from cache or database into protected property
     * 
     * @return void
     */
    protected function loadAllDiscounts() {
        $cachedData = $this->cache->get(self::CACHE_KEY, self::CACHE_TIMEOUT);
        if (!empty($cachedData)) {
            $this->allDiscounts = $cachedData;
        } else {
            $this->allDiscounts = $this->discountsDb->getAll('login');
            $this->cache->set(self::CACHE_KEY, $this->allDiscounts, self::CACHE_TIMEOUT);
        }
    }

    /**
     * Flushes cached data and loads new from database
     * 
     * @return void
     */
    protected function flushCache() {
        $this->cache->delete(self::CACHE_KEY);
        $this->loadAllDiscounts();
    }

    /**
     * Returns current user discount
     * 
     * @param string $login
     * 
     * @return float
     */
    public function getUserDiscount($login) {
        $result = 0;
        if (isset($this->allDiscounts[$login])) {
            $result = $this->allDiscounts[$login]['percent'];
        }
        return($result);
    }

    /**
     * Renders user discount editing form
     * 
     * @param string $login
     * 
     * @return string
     */
    public function renderUserEditDiscountForm($login) {
        $result = '';
        $currentDiscountPercent = $this->getUserDiscount($login);
        $inputs = wf_HiddenInput(self::PROUTE_LOGIN, $login);
        $inputs .= wf_TextInput(self::PROUTE_PERCENT, __('Discount') . ' (%)', $currentDiscountPercent, false, 4, 'digits');
        $inputs .= wf_Submit(__('Save'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return($result);
    }

    /**
     * Renders user discount editing form
     * 
     * @param string $login
     * 
     * @return string
     */
    public function renderDiscountForm($login) {
        $result = '';
        $currentDiscountPercent = $this->getUserDiscount($login);
        $inputs = wf_HiddenInput(self::PROUTE_LOGIN, $login);
        $inputs .= wf_TextInput(self::PROUTE_PERCENT, __('Discount') . ' (%)', $currentDiscountPercent, false, 4, 'digits');
        $inputs .= wf_Submit(__('Save'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return($result);
    }

    /**
     * Saves user discount to database
     * 
     * @param string $login
     * 
     * @return void
     */
    public function saveDiscount($login = '') {
        $userLogin = '';
        if ($login) {
            $userLogin = $login;
        } else {
            $userLogin = ubRouting::post(self::PROUTE_LOGIN);
        }
        if ($userLogin AND ubRouting::checkPost(self::PROUTE_PERCENT, false)) {
            $userLoginF = ubRouting::filters($userLogin, 'mres');
            $newDiscountPercent = ubRouting::post(self::PROUTE_PERCENT, 'int');

            if (!empty($userLogin)) {
                //already have discount?
                if (isset($this->allDiscounts[$userLogin])) {
                    $recordId = $this->allDiscounts[$userLogin]['id'];
                    $this->discountsDb->data('percent', $newDiscountPercent);
                    $this->discountsDb->where('id', '=', $recordId);
                    $this->discountsDb->save();
                } else {
                    //creating new discount record
                    $this->discountsDb->data('login', $userLoginF);
                    $this->discountsDb->data('percent', $newDiscountPercent);
                    $this->discountsDb->create();
                }
                //load some new data for current instance
                $this->flushCache();
                log_register('DISCOUNT SET (' . $userLogin . ') PERCENT `' . $newDiscountPercent . '`');
            }
        }
    }

    /**
     * Returns all users discounts as login=>percent
     * 
     * @return array
     */
    protected function getAllUsersDiscounts() {
        $result = array();
        if (!empty($this->allDiscounts)) {
            foreach ($this->allDiscounts as $eachLogin => $eachDiscountData) {
                if ($eachDiscountData['percent']) {
                    $result[$eachLogin] = $eachDiscountData['percent'];
                }
            }
        }
        return ($result);
    }

    /**
     * Returns array of all month payments made during some month
     * 
     * @param string $month
     * 
     * @return array
     */
    protected function getAllMonthPayments($month) {
        $paymentsDb = new NyanORM(self::PAYMENTS_TABLE);
        $paymentsDb->where('date', 'LIKE', $month . '%');
        $paymentsDb->where('summ', '>', '0');
        $paymentsDb->where('note', 'NOT LIKE', 'DISCOUNT:%');
        $allPayments = $paymentsDb->getAll();

        $result = array();
        if (!empty($allPayments)) {
            foreach ($allPayments as $io => $each) {
                //sum of user month payments
                if (isset($result[$each['login']])) {
                    $result[$each['login']] = $result[$each['login']] + $each['summ'];
                } else {
                    $result[$each['login']] = $each['summ'];
                }
            }
        }
        return ($result);
    }

    /**
     * Do the processing of discounts by the payments
     * 
     * @param bool $debug
     */
    public function processPayments() {
        global $ubillingConfig;
        $cashtypeId = ($ubillingConfig->getAlterParam('DISCOUNT_CASHTYPEID')) ? $ubillingConfig->getAlterParam('DISCOUNT_CASHTYPEID') : 1;
        $targetMonth = ($ubillingConfig->getAlterParam('DISCOUNT_PREVMONTH')) ? prevmonth() : curmonth();
        $operation = ($ubillingConfig->getAlterParam('DISCOUNT_OPERATION') == 'CORR') ? 'correct' : 'add';
        $allUserDiscounts = $this->getAllUsersDiscounts();
        $allMonthPayments = $this->getAllMonthPayments($targetMonth);

        if ((!empty($allUserDiscounts) AND ( !empty($allMonthPayments)))) {
            foreach ($allMonthPayments as $login => $eachPayment) {
                //have this user any discount?
                if (isset($allUserDiscounts[$login])) {
                    //yes it have
                    $discountPercent = $allUserDiscounts[$login];
                    $discountPayment = ($eachPayment / 100) * $discountPercent;
                    zb_CashAdd($login, $discountPayment, $operation, $cashtypeId, 'DISCOUNT:' . $discountPercent);
                }
            }
        }
    }

}
