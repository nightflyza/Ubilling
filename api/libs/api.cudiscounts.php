<?php

class CumulativeDiscounts {

    protected $allDiscounts = array();
    protected $allUsers = array();
    protected $altCfg=array();
    protected $tariffPrices = array();
    protected $discountPullDays = 30; // via CUD_PULLDAYS
    protected $fillPercent = 1; //via CUD_PERCENT
    protected $discountPayId = 1; // via CUD_PAYID
    protected $discountLimit=10; //via CUD_PERCENTLIMIT
    protected $customDiscountCfId=''; //via CUD_CFID
    protected $debug = 0; //via CUD_ENABLED
    protected $logPath='';
    protected $curdate='';

    public function __construct() {
        $this->loadAlter();
        $this->setOptions();
        $this->loadUsers();
        $this->loadDiscounts();
        $this->loadTariffPrices();
    }
    
    /**
     * Loads system-wide alter.ini for further usage
     * 
     * @return void
     */
    protected function loadAlter() {
        global $ubillingConfig;
        $this->altCfg=$ubillingConfig->getAlter();
    }




    /**
     * Sets default options
     * 
     * @return void
     */
    protected function setOptions() {
        $this->curdate=curdatetime();
        $this->discountPullDays=  $this->altCfg['CUD_PULLDAYS'];
        $this->fillPercent=  $this->altCfg['CUD_PERCENT'];
        $this->discountPayId=  $this->altCfg['CUD_PAYID'];
        $this->discountLimit=  $this->altCfg['CUD_PERCENTLIMIT'];
        $this->customDiscountCfId=  $this->altCfg['CUD_CFID'];
        $this->logPath=DATA_PATH.'documents/cudiscounts.log';
        $this->setDebug($this->altCfg['CUD_ENABLED']);
    }

    /**
     * Loads all available users into private data property
     * 
     * @return void 
     */
    protected function loadUsers() {
        $query = "SELECT * from `users`"; // WHERE `Cash`>= -`Credit` AND `Passive`='0' AND `Down`=0; ?
        $tmp = zb_UserGetAllStargazerData();
        if (!empty($tmp)) {
            foreach ($tmp as $io => $each) {
                $this->allUsers[$each['login']] = $each;
            }
        }
    }

    /**
     * Load prices of all available tariffs
     * 
     * @return void
     */
    protected function loadTariffPrices() {
        $raw = zb_TariffGetPricesAll();
        if (!empty($raw)) {
            foreach ($raw as $io => $each) {
                $this->tariffPrices[$io] = $each;
            }
        }
    }

    /**
     * Loads all available cummulative discounts from database
     * 
     * @return void
     */
    protected function loadDiscounts() {
        $query = "SELECT * from `cudiscounts`";
        $raw = simple_queryall($query);
        if (!empty($raw)) {
            foreach ($raw as $io => $each) {
                $this->allDiscounts[$each['login']] = $each;
            }
        }
    }

    /**
     * Basic setter for the debugging mode
     * 
     * @param bool $state
     */
    public function setDebug($state) {
        if ($state) {
            $this->debug = $state;
        }
    }

    /**
     * Creates discount field in database
     * 
     * @param string $login
     * 
     * @return void
     */
    protected function createDiscount($login, $days) {
        $login = mysql_real_escape_string($login);
    
        $currentDiscount = 0;
        $days = vf($days, 3);
        $query = "INSERT INTO `cudiscounts` (`id`, `login`, `discount`, `date`, `days`) "
                . "VALUES (NULL,'" . $login . "','" . $currentDiscount . "','" . $this->curdate . "','" . $days . "');";
        nr_query($query);
        $this->debugLog("CUDISC CREATE (" . $login . ")");
    }

    /**
     * Changes discount data in database
     * 
     * @param string $login
     * @param int $days
     * @param float $discount
     */
    protected function setDiscount($login, $days, $discount) {
        $days = vf($days, 3);
        $discount = mysql_real_escape_string($discount);
        $login = mysql_real_escape_string($login);
        $this->allDiscounts[$login]['days']=$days;
        $this->allDiscounts[$login]['discount']=$discount;
        $query = "UPDATE `cudiscounts` SET `days`='" . $days . "', `discount`='" . $discount . "' WHERE `login`='" . $login . "'; ";
        nr_query($query);
    }

    /**
     * Returns discount data for some login
     * 
     * @param string $login
     * @return array
     */
    protected function getDiscountData($login) {
        $result = array();
        if (isset($this->allDiscounts[$login])) {
            $result = $this->allDiscounts[$login];
        }
        return ($result);
    }

    /**
     * Pushes log data if debugging mode is enabled
     * 
     * @param string $data
     */
    protected function debugLog($data) {
        if ($this->debug) {
          file_put_contents($this->logPath, $this->curdate.' '.$data."\n", FILE_APPEND); //append data to log
        }
        
        if ($this->debug>1) {
            log_register($data);
        }
    }

    /**
     * Adds cash for user, flushes counters
     * 
     * @param string $login
     * 
     * @return void
     */
    protected function pushDiscount($login) {
        if (isset($this->allUsers[$login])) {
            $discountData = $this->getDiscountData($login);
            if (!empty($discountData)) {
                $userTariff = $this->allUsers[$login]['Tariff'];
                if (isset($this->tariffPrices[$userTariff])) {
                    $tariffPrice = $this->tariffPrices[$userTariff];
                    if ($tariffPrice != 0) {
                        $discountPercent = $discountData['discount'];
                        $discountPayment = ($tariffPrice / 100) * $discountPercent;
                        zb_CashAdd($login, $discountPayment, 'add', $this->discountPayId, 'DISCOUNT:' . $discountPercent);
                        $this->debugLog('CUDISCOUNTS PUSH (' . $login . ') PERCENT:' . $discountPercent . ' DAYS:' . $discountData['days'] . ' TARIFF:' . $userTariff);
                    } else {
                        $this->debugLog('CUDISCOUNTS IGNORE (' . $login . ') TARIFF ' . $userTariff . ' ZERO PRICE');
                    }
                } else {
                    $this->debugLog('CUDISCOUNTS IGNORE (' . $login . ') TARIFF ' . $userTariff . ' NOT EXISTS');
                }
            } else {
                $this->debugLog('CUDISCOUNTS IGNORE (' . $login . ') EMPTY DISCOUNT DATA');
            }
        } else {
            $this->debugLog('CUDISCOUNTS IGNORE (' . $login . ') LOGIN NOT EXISTS');
        }
    }

    /**
     * Do the discounts preprocessing
     * 
     * @return void
     */
    public function processDiscounts() {
        if (!empty($this->allUsers)) {
            foreach ($this->allUsers as $login => $each) {
                //maybe first run?
                if (!isset($this->allDiscounts[$login])) {
                    if (($each['Cash'] >= -$each['Credit']) AND ( $each['Passive'] == 0) AND ( $each['Down'] == 0)) {
                        $this->createDiscount($login, 1); // yep, nice day 
                    } else {
                        $this->createDiscount($login, 0); // you are looser, man
                    }
                } else {
                    //discount already available
                    $discountData = $this->getDiscountData($login);
                    if (($each['Cash'] >= -$each['Credit']) AND ( $each['Passive'] == 0) AND ( $each['Down'] == 0)) {
                        if ($discountData['days'] < $this->discountPullDays) {
                            $daysFill = $discountData['days'] + 1;
                            $this->setDiscount($login, $daysFill, $discountData['discount']);
                            $this->debugLog('CUDISCOUNTS UPDATE ('.$login.') DAYS:'.$daysFill.' PERCENT:'.$discountData['discount']);
                        } else {
                            $newDiscount = ($discountData['discount']<$this->discountLimit) ? $discountData['discount']+$this->fillPercent : $this->discountLimit;
                            $this->setDiscount($login, 0, $newDiscount);
                            $this->pushDiscount($login); // pay some money, flush counters
                        }
                    } else {
                        //passive user
                        //try to save mysql query count
                        if ($discountData['days'] != 0) {
                            $this->setDiscount($login, 0, 0);
                            $this->debugLog('CUDISCOUNTS SET DOWN (' . $login . ') PERCENT:' . $discountData['discount'] . ' DAYS:' . $discountData['days']);
                        }
                    }
                }
            }
        } else {
            $this->debugLog('CUDISCOUNTS NO USERS');
        }
    }

}

?>