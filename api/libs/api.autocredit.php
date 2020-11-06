<?php

/**
 * Automatic user credits setting class
 */
class AutoCredit {

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Custom field ID for mark of required credit setup
     *
     * @var int
     */
    protected $cfId = 0;

    /**
     * Contains array of available CFs of required type for all users as login=>day of month
     *
     * @var array
     */
    protected $cfData = array();

    /**
     * Contains all of available users in database
     *
     * @var array
     */
    protected $allUsers = array();

    /**
     * Contains all available tariff prices as name=>Fee
     *
     * @var string
     */
    protected $allTariffPrices = array();

    /**
     * Contains alter option name with CF ID
     */
    const OPTION_CFID = 'AUTOCREDIT_CFID';

    /**
     * Creates new automatic creditor instance
     */
    public function __construct() {
        $this->loadAter();
        $this->setOptions();
        $this->loadUsers();
        $this->loadTariffs();
        if (!empty($this->cfId)) {
            $this->loadCfs();
        }
    }

    /**
     * Preloads alter config into protected prop for further usage
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadAter() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Sets initial options due billing configuration files
     * 
     * @return void
     */
    protected function setOptions() {
        if (isset($this->altCfg[self::OPTION_CFID])) {
            $optionRaw = $this->altCfg[self::OPTION_CFID];
            $optionRaw = ubRouting::filters($optionRaw, 'int');
            if (!empty($optionRaw)) {
                $this->cfId = $optionRaw;
            }
        }
    }

    /**
     * Loads all available users from database
     * 
     * @return void
     */
    protected function loadUsers() {
        $this->allUsers = zb_UserGetAllStargazerDataAssoc();
    }

    /**
     * Loads all available tariff fees 
     * 
     * @return void
     */
    protected function loadTariffs() {
        $this->allTariffPrices = zb_TariffGetPricesAll();
    }

    /**
     * Loads all avaialble CFs content from database for all of existing users
     * 
     * @return void
     */
    protected function loadCfs() {
        if (!empty($this->cfId)) {
            $cfsRaw = cf_FieldsGetAll();
            if (!empty($cfsRaw)) {
                foreach ($cfsRaw as $io => $each) {
                    if ($each['typeid'] == $this->cfId) {
                        $userLogin = $each['login'];
                        if (isset($this->allUsers[$userLogin])) {
                            //user is available
                            $cfContent = ubRouting::filters($each['content'], 'int');
                            if (is_numeric($cfContent) AND $cfContent > 0 AND $cfContent < 32) {
                                //is valid day of month value
                                $this->cfData[$userLogin] = $cfContent;
                            } else {
                                log_register('AUTOCREDIT (' . $userLogin . ') FAIL WRONG CFDAY `' . $cfContent . '`');
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Returns next Year-month number with leading zero in format Y-m-
     * 
     * @return string
     */
    protected function getNextMonth() {
        $curYear = date("Y");
        $nextYear = $curYear;
        $curMonth = date("n");

        if ($curMonth == 12) {
            //December increases year and sets next month to January
            $nextMonth = 1;
            $nextYear = $nextYear + 1;
        } else {
            $nextMonth = $curMonth + 1;
        }

        if ($nextMonth < 10) {
            $nextMonth = '0' . $nextMonth;
        }

        $result = $nextYear . '-' . $nextMonth . '-';
        return($result);
    }

    /**
     * Performs automatic credit setup
     * 
     * @global object $billing
     * 
     * @param string $mode - user marker for credit setup
     * 
     * @return int
     */
    public function processing($mode = 'cf') {
        global $billing;
        $count = 0;
        if ($mode == 'cf') {
            //default processing mode. Left for extending in future on tags, triggers etc.
            if (!empty($this->cfData)) {
                $nextMonth = $this->getNextMonth();
                foreach ($this->cfData as $userLogin => $dayRaw) {
                    $userData = $this->allUsers[$userLogin];
                    $userTariff = $userData['Tariff'];
                    $userTariffFee = $this->allTariffPrices[$userTariff];
                    if ($dayRaw < 10) {
                        //fixing leading zero
                        $dayRaw = '0' . $dayRaw;
                    }
                    $creditExpireDay = $nextMonth . $dayRaw;
                    if ($userTariffFee > 0) {
                        //not free tariff
                        if (zb_checkDate($creditExpireDay)) {
                            $billing->setcredit($userLogin, $userTariffFee);
                            $billing->setcreditexpire($userLogin, $creditExpireDay);
                            log_register('AUTOCREDIT (' . $userLogin . ') ON `' . $userTariffFee . '` TO `' . $creditExpireDay . '`');
                            $count++;
                        } else {
                            log_register('AUTOCREDIT (' . $userLogin . ') FAIL WRONG CFDAY `' . $creditExpireDay . '`');
                        }
                    }
                }
            }
        }

        return($count);
    }

}
