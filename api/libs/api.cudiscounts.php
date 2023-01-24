<?php

/**
 * Cumulative discounts implementation
 */
class CumulativeDiscounts {

    protected $allDiscounts = array();
    protected $allUsers = array();
    protected $altCfg = array();
    protected $tariffPrices = array();
    protected $customDiscounts = array();
    protected $discountPullDays = 30; // via CUD_PULLDAYS
    protected $fillPercent = 1; //via CUD_PERCENT
    protected $discountPayId = 1; // via CUD_PAYID
    protected $discountLimit = 10; //via CUD_PERCENTLIMIT
    protected $customDiscountCfId = ''; //via CUD_CFID
    protected $cashMode = 'ADD'; //via CUD_OPERATION
    protected $debug = 0; //via CUD_ENABLED
    protected $logPath = '';
    protected $curdate = '';
    protected $login = '';

    public function __construct() {
        $this->loadAlter();
        $this->setOptions();
        $this->loadUsers();
        $this->loadDiscounts();
        $this->loadTariffPrices();
        $this->loadCustomDiscounts();
    }

    /**
     * Loads system-wide alter.ini for further usage
     * 
     * @return void
     */
    protected function loadAlter() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Sets default options
     * 
     * @return void
     */
    protected function setOptions() {
        $this->curdate = curdatetime();
        $this->discountPullDays = vf($this->altCfg['CUD_PULLDAYS'], 3);
        $this->fillPercent = vf($this->altCfg['CUD_PERCENT'], 3);
        $this->discountPayId = vf($this->altCfg['CUD_PAYID'], 3);
        $this->discountLimit = vf($this->altCfg['CUD_PERCENTLIMIT'], 3);
        $this->customDiscountCfId = vf($this->altCfg['CUD_CFID'], 3);
        $this->logPath = DATA_PATH . 'documents/cudiscounts.log';
        $this->setDebug($this->altCfg['CUD_ENABLED']);
        $this->customDiscountCfId = vf($this->altCfg['CUD_CFID'], 3);
        if (isset($this->altCfg['CUD_OPERATION'])) {
            $this->cashMode = $this->altCfg['CUD_OPERATION'];
        }
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
     * Loads custom discounts if its available for all users
     * 
     * @return void
     */
    protected function loadCustomDiscounts() {
        if (!empty($this->customDiscountCfId)) {
            $cf = new CustomFields();
            $raw = $cf->getAllFieldsData();
            if (!empty($raw)) {
                foreach ($raw as $io => $each) {
                    if ($each['typeid'] == $this->customDiscountCfId) {
                        $discount = vf($each['content'], 3); // numeric int
                        if ($discount) {
                            $this->customDiscounts[$each['login']] = $discount;
                        }
                    }
                }
            }
        }
    }

    /**
     * Getter for custom discount for some users
     * 
     * @param string $login
     * @return int
     */
    protected function getCustomDiscount($login) {
        $result = 0;
        if (isset($this->customDiscounts[$login])) {
            $result = $this->customDiscounts[$login];
        }
        return ($result);
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
     * @param int $state
     * 
     * @return void
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
        $this->debugLog("CUDISCOUNTS CREATE (" . $login . ")");
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
        $this->allDiscounts[$login]['days'] = $days;
        $this->allDiscounts[$login]['discount'] = $discount;
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
            file_put_contents($this->logPath, $this->curdate . ' ' . $data . "\n", FILE_APPEND); //append data to log
        }

        if ($this->debug > 1) {
            log_register($data);
        }
    }

    /**
     * Adds cash for user
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
                        if ($this->cashMode == 'CORR') {
                            $cashOperation = 'correct';
                        } else {
                            $cashOperation = 'add';
                        }

                        zb_CashAdd($login, $discountPayment, $cashOperation, $this->discountPayId, 'DISCOUNT:' . $discountPercent);
                        $this->debugLog('CUDISCOUNTS PUSH (' . $login . ') PERCENT:' . $discountPercent . ' DAYS:' . $discountData['days'] . ' CASH:' . $discountPayment . ' TARIFF:' . $userTariff);
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
                            //user active - normal processing
                            $daysFill = $discountData['days'] + 1;
                            $customDiscount = $this->getCustomDiscount($login);
                            if ($customDiscount) {
                                //is custom discount set for this user?
                                $newDiscount = $customDiscount;
                                $this->debugLog('CUDISCOUNTS OVERRIDE (' . $login . ') PERCENT:' . $customDiscount);
                            } else {
                                $newDiscount = $discountData['discount'];
                            }
                            $this->setDiscount($login, $daysFill, $newDiscount);
                            $this->debugLog('CUDISCOUNTS UPDATE (' . $login . ') DAYS:' . $daysFill . ' PERCENT:' . $discountData['discount']);
                        } else {
                            //discount pushing, clearing days counter
                            //may be override with custom field?
                            $customDiscount = $this->getCustomDiscount($login);
                            if ($customDiscount) {
                                //CF override
                                $newDiscount = $customDiscount;
                            } else {
                                //natural cumulative discount
                                $newDiscount = ($discountData['discount'] < $this->discountLimit) ? $discountData['discount'] + $this->fillPercent : $this->discountLimit;
                            }
                            $this->setDiscount($login, 0, $newDiscount);
                            $this->pushDiscount($login); // pay some money, flush counters
                        }
                    } else {
                        //passive user
                        //try to save mysql query count
                        if ($discountData['days'] != 0) {
                            $this->setDiscount($login, 0, 0);
                            $this->debugLog('CUDISCOUNTS SETDOWN (' . $login . ') DAYS: 0 PERCENT: 0');
                        }
                    }
                }
            }
        } else {
            $this->debugLog('CUDISCOUNTS NO USERS');
        }
    }

    /**
     * Sets filtering login private property
     * 
     * @param string $login
     * 
     * @return void
     */
    public function setLogin($login) {
        $this->login = $login;
    }

    /**
     * Parses log data for some user login
     * 
     * @return array
     */
    protected function getLogData() {
        $result = array();
        global $ubillingConfig;
        $billCfg = $ubillingConfig->getBilling();
        $cat = $billCfg['CAT'];
        $grep = $billCfg['GREP'];
        $i = 0;

        if (!empty($this->login)) {
            if (file_exists($this->logPath)) {
                $command = $cat . ' ' . $this->logPath . ' | grep "(' . $this->login . ')"';
                $raw = shell_exec($command);
                if (!empty($raw)) {
                    $raw = explodeRows($raw);
                    if (!empty($raw)) {
                        foreach ($raw as $io => $each) {
                            if (!empty($each)) {
                                $line = explode(' ', $each);
                                $date = $line[0] . ' ' . $line[1];
                                $event = $line[3];
                                $params = explode(')', $each);
                                $params = $params[1];
                                $result[$i]['date'] = $date;
                                $result[$i]['event'] = $event;
                                $result[$i]['params'] = $params;
                                $i++;
                            }
                        }
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Renders cumulative discounts report
     * 
     * @return string
     */
    public function renderReport() {
        $result = '';

        $currentData = $this->getDiscountData($this->login);
        $customDiscount = $this->getCustomDiscount($this->login);
        if (!empty($currentData)) {
            $cells = wf_TableCell(__('Discount'));
            $cells .= wf_TableCell(__('Day'));
            $cells .= wf_TableCell(__('Custom discount'));
            $rows = wf_TableRow($cells, 'row1');
            $cells = wf_TableCell($currentData['discount'] . '%');
            $cells .= wf_TableCell($currentData['days']);
            if ($customDiscount == 0) {
                $customDiscount = __('No');
            } else {
                $customDiscount = $customDiscount . '%';
            }
            $cells .= wf_TableCell($customDiscount);
            $rows .= wf_TableRow($cells, 'row3');
            $result .= wf_TableBody($rows, '100%', 0, 'glamour');
            $result .= wf_CleanDiv();
            $result .= wf_delimiter();
        }

        $logData = $this->getLogData();
        if (!empty($logData)) {
            $cells = wf_TableCell(__('Date'));
            $cells .= wf_TableCell(__('Event'));
            $cells .= wf_TableCell(__('Details'));
            $rows = wf_TableRow($cells, 'row1');

            foreach ($logData as $io => $each) {
                $fc = wf_tag('font', false);
                $efc = wf_tag('font', true);

                if ($each['event'] == 'CREATE') {
                    $fc = wf_tag('font', false, '', 'color="#ffac1b"');
                }

                if ($each['event'] == 'UPDATE') {
                    $fc = wf_tag('font', false, '', 'color="#6396ff"');
                }

                if ($each['event'] == 'PUSH') {
                    $fc = wf_tag('font', false, '', 'color="#1c7700"');
                }

                if ($each['event'] == 'SET') {
                    $fc = wf_tag('font', false, '', 'color="#a90000"');
                }

                if ($each['event'] == 'SETDOWN') {
                    $fc = wf_tag('font', false, '', 'color="#a90000"');
                }

                $params = $each['params'];
                $params = str_replace('DAYS', __('Day'), $params);
                $params = str_replace('PERCENT', __('Percent'), $params);
                $params = str_replace('CASH', __('Cash'), $params);
                $params = str_replace('TARIFF', __('Tariff'), $params);

                $cells = wf_TableCell($fc . $each['date'] . $efc);
                $cells .= wf_TableCell($fc . $each['event'] . $efc);
                $cells .= wf_TableCell($params);

                $rows .= wf_TableRow($cells, 'row3');
            }

            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result .= wf_tag('span', false, 'alert_warning') . __('Nothing found') . wf_tag('span', true);
        }
        return ($result);
    }

}

?>