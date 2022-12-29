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
     * Some predefined stuff here
     */
    const DB_TABLE = 'discounts';
    const PROUTE_PERCENT = 'setdiscountpercent';
    const PROUTE_LOGIN = 'setdiscountlogin';

    public function __construct() {
        $this->initMessages();
        $this->loadConfig();
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
        $this->discountsDb = new NyanORM(self::DB_TABLE);
    }

    /**
     * Loads all available discounts data from database into protected property
     * 
     * @return void
     */
    protected function loadAllDiscounts() {
        $this->allDiscount = $this->discountsDb->getAll('login');
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
    public function renderDiscountForm($login) {
        $result = '';
        $currentDiscountPercent = $this->getUserDiscount($login);
        $inputs = wf_HiddenInput(self::PROUTE_LOGIN, $login);
        $inputs .= wf_TextInput(self::PROUTE_PERCENT, __('Discount'), $currentDiscountPercent, false, 4, 'digits');
        $inputs .= wf_Submit(__('Save'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return($result);
    }

    /**
     * Saves user discount to database
     * 
     * @return void
     */
    public function saveDiscount() {
        if (ubRouting::checkPost(self::PROUTE_LOGIN) AND ubRouting::checkPost(self::PROUTE_PERCENT, false)) {
            $userLogin = ubRouting::post(self::PROUTE_LOGIN);
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

                log_register('DISCOUNT SET (' . $userLogin . ') PERCENT `' . $newDiscountPercent . '`');
            }
        }
    }

}
