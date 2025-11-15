<?php

/**
 * Financial data preprocessing and rendering class
 */
class FundsFlow {

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $alterConf = array();

    /**
     * Contains main billing config as key=>value
     *
     * @var array
     */
    protected $billingConf = array();

    /**
     * Contains all of available user data as login=>userdata
     *
     * @var array
     */
    protected $allUserData = array();

    /**
     * Contains available tariffs data as tariffname=>data
     *
     * @var array
     */
    protected $allTariffsData = array();

    /**
     * Storage os some temporary data
     * 
     * @var array
     */
    protected $fundsTmp = array();

    /**
     * Contains assigned user tags
     *
     * @var array
     */
    protected $userTags = array();

    /**
     * Placeholder for FF_REP_AVOID_DUPLICATE_DT_KEYS alter.ini option
     *
     * @var bool
     */
    public $avoidDTKeysDuplicates = false;

    /**
     * Is fees harvester enabled flag.
     *
     * @var bool
     */
    protected $feesHarvesterFlag = false;

    /**
     * Harvested fees database abstraction layer
     *
     * @var object
     */
    protected $feesDb = '';

    /**
     * Payments database abstraction layer
     *
     * @var object
     */
    protected $paymentsDb = '';

    /**
     * Balance corrections database abstraction layer
     *
     * @var object
     */
    protected $corrDb = '';

    /**
     * Contains date filter for massive queries
     *
     * @var string
     */
    protected $dateFilter = '';

    /**
     * Contains cemetery instance for further usage
     *
     * @var object
     */
    protected $cemetery = '';

    /**
     * Contains buried users
     *
     * @var array
     */
    protected $allBuriedUsers = array();

    /**
     * Placeholder for ONLINE_SHOW_LAST_FEECHARGE alter.ini option
     *
     * @var string
     */
    protected $onlineShowLastFeeChargeON = 0;

    /**
     * Rendering coloring settings
     */
    protected $colorPayment = '005304';
    protected $colorFee = 'a90000';
    protected $colorBonus = '007706';
    protected $colorAdditionalFee = 'd50000';
    protected $colorCorrecting = 'ff6600';
    protected $colorMock = '006699';
    protected $colorSet = '000000';
    protected $colorCreditViolet = '552c82';

    /**
     * some stargazer log data offsets here
     */
    const OFFSET_DATE = 0;
    const OFFSET_TIME = 1;
    const OFFSET_LOGIN = 7;
    const OFFSET_FROM = 12;
    const OFFSET_TO = 14;

    /**
     * other predefined stuff
     */
    const MASK_FEE = 'fee charge';
    const TABLE_FEES = 'fees';
    const TABLE_PAYS = 'payments';
    const TABLE_CORR = 'paymentscorr';

    /**
     * Creates new FundsFlow instance
     * 
     * @return void
     */
    public function __construct() {
        $this->loadConfigs();
        $this->initTmp();
        $this->initFinanceDb();
        if ($this->feesHarvesterFlag) {
            $this->initFeesDb();
        }

        if ($this->alterConf['CEMETERY_ENABLED']) {
            $this->loadCemetery();
        }
    }

    /**
     * Preloads system configs
     * 
     * @return void
     */
    protected function loadConfigs() {
        global $ubillingConfig;
        $this->alterConf = $ubillingConfig->getAlter();
        $this->billingConf = $ubillingConfig->getBilling();
        $this->avoidDTKeysDuplicates = $ubillingConfig->getAlterParam('FF_REP_AVOID_DUPLICATE_DT_KEYS');
        $this->feesHarvesterFlag = $ubillingConfig->getAlterParam('FEES_HARVESTER');
        $this->onlineShowLastFeeChargeON = $ubillingConfig->getAlterParam('ONLINE_SHOW_LAST_FEECHARGE', 0);
    }

    /**
     * Inits tmp data with empty values
     * 
     * @return void
     */
    protected function initTmp() {
        $this->fundsTmp['col1'] = 0;
        $this->fundsTmp['col2'] = 0;
        $this->fundsTmp['col3'] = 0;
        $this->fundsTmp['col4'] = 0;
    }

    /**
     * Inits fees database abstraction layer
     * 
     * @return void
     */
    protected function initFinanceDb() {
        $this->paymentsDb = new NyanORM(self::TABLE_PAYS);
        $this->corrDb = new NyanORM(self::TABLE_CORR);
    }

    /**
     * Inits fees database abstraction layer
     * 
     * @return void
     */
    protected function initFeesDb() {
        $this->feesDb = new NyanORM(self::TABLE_FEES);
    }

    /**
     * Loads all of available users data as login=>array
     * 
     * @return void
     */
    protected function loadAllUserData() {
        $this->allUserData = zb_UserGetAllStargazerDataAssoc();
    }

    /**
     * Inits cemetery and preloads all buried users
     * 
     * @return void
     */
    protected function loadCemetery() {
        $this->cemetery = new Cemetery();
        $this->allBuriedUsers = $this->cemetery->getAllTagged();
    }

    /**
     * Loads tariffs data from database into protected property
     * 
     * @return void
     */
    protected function loadAllTariffsData() {
        $this->allTariffsData = zb_TariffGetAllData();
    }

    /**
     * Loads existing tagtypes and usertags into protected props for further usage
     * 
     * @return void
     */
    protected function loadUserTags() {
        $this->userTags = zb_UserGetAllTags();
    }

    /**
     * Datetime mask for filtering payments/fees on massive queries
     * 
     * @param string $dateMask
     * 
     * @return void
     */
    public function setDateFilter($dateMask) {
        $this->dateFilter = ubRouting::filters($dateMask, 'mres');
    }

    /**
     * Returns array of fees by some login with parsing it from stargazer log
     * 
     * @param string $login existing user login
     * 
     * @return array
     */
    public function getLogFees($login) {
        $login = ubRouting::filters($login, 'mres');

        $sudo = $this->billingConf['SUDO'];
        $cat = $this->billingConf['CAT'];
        $grep = $this->billingConf['GREP'];
        $stglog = $this->alterConf['STG_LOG_PATH'];

        $result = array();

        $feeadmin = 'stargazer';
        $feenote = '';
        $feecashtype = 0;
        // monthly fees output
        $command = $sudo . ' ' . $cat . ' ' . $stglog . ' | ' . $grep . ' "' . self::MASK_FEE . '"' . ' | ' . $grep . ' "User \'' . $login . '\'" ';
        $rawdata = shell_exec($command);

        if (!empty($rawdata)) {
            $cleardata = exploderows($rawdata);
            foreach ($cleardata as $eachline) {
                $eachfee = explode(' ', $eachline);
                if (!empty($eachline)) {
                    if (isset($eachfee[self::OFFSET_TIME])) {
                        $counter = strtotime($eachfee[self::OFFSET_DATE] . ' ' . $eachfee[self::OFFSET_TIME]);

                        // trying to avoid duplicate keys
                        while ($this->avoidDTKeysDuplicates and array_key_exists($counter, $result)) {
                            $counter++;
                        }

                        $feefrom = str_replace("'.", '', $eachfee[self::OFFSET_FROM]);
                        $feeto = str_replace("'.", '', $eachfee[self::OFFSET_TO]);
                        $feefrom = str_replace("'", '', $feefrom);
                        $feeto = str_replace("'", '', $feeto);

                        $result[$counter]['login'] = $login;
                        $result[$counter]['date'] = $eachfee[self::OFFSET_DATE] . ' ' . $eachfee[self::OFFSET_TIME];
                        $result[$counter]['admin'] = $feeadmin;
                        $result[$counter]['summ'] = $feeto - $feefrom;
                        $result[$counter]['from'] = $feefrom;
                        $result[$counter]['to'] = $feeto;
                        $result[$counter]['operation'] = 'Fee';
                        $result[$counter]['note'] = $feenote;
                        $result[$counter]['cashtype'] = $feecashtype;
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Returns array of fees by some login from harvested fees database
     * 
     * @param string $login existing user login
     * 
     * @return array
     */
    public function getDbFees($login) {
        $result = array();
        $login = ubRouting::filters($login, 'mres');
        $this->feesDb->where('login', '=', $login);
        $allFees = $this->feesDb->getAll();
        if (!empty($allFees)) {
            foreach ($allFees as $io => $each) {
                $counter = strtotime($each['date']);
                // trying to avoid duplicate keys
                while ($this->avoidDTKeysDuplicates and array_key_exists($counter, $result)) {
                    $counter++;
                }

                $result[$counter]['login'] = $each['login'];
                $result[$counter]['date'] = $each['date'];
                $result[$counter]['admin'] = $each['admin'];
                $result[$counter]['summ'] = $each['summ'];
                $result[$counter]['from'] = $each['from'];
                $result[$counter]['to'] = $each['to'];
                $result[$counter]['operation'] = 'Fee';
                $result[$counter]['note'] = $each['note'];
                $result[$counter]['cashtype'] = $each['cashtype'];
            }
        }
        return ($result);
    }

    /**
     * Returns array of fees by some login with parsing it from stargazer log
     * 
     * @param string $login existing user login
     * 
     * @return array
     */
    public function getFees($login) {
        $result = array();
        if ($this->feesHarvesterFlag) {
            $result = $this->getDbFees($login);
        } else {
            $result = $this->getLogFees($login);
        }
        return ($result);
    }

    /**
     * Returns all payments/fees/corrections with optional date filter as login=>counter=>flows
     * 
     * @return array
     */
    public function getAllCashFlows() {
        $result = array();

        $payments = array();
        $paymentscorr = array();
        /**
         * Fees
         */
        if ($this->feesHarvesterFlag) {
            if ($this->dateFilter) {
                $this->feesDb->where('date', 'LIKE', $this->dateFilter . '%');
            }
            $allFees = $this->feesDb->getAll();
            if (!empty($allFees)) {
                foreach ($allFees as $io => $each) {
                    $counter = strtotime($each['date']);
                    // trying to avoid duplicate keys
                    while ($this->avoidDTKeysDuplicates and isset($result[$each['login']][$counter])) {
                        $counter++;
                    }

                    $result[$each['login']][$counter]['login'] = $each['login'];
                    $result[$each['login']][$counter]['date'] = $each['date'];
                    $result[$each['login']][$counter]['admin'] = $each['admin'];
                    $result[$each['login']][$counter]['summ'] = $each['summ'];
                    $result[$each['login']][$counter]['from'] = $each['from'];
                    $result[$each['login']][$counter]['to'] = $each['to'];
                    $result[$each['login']][$counter]['operation'] = 'Fee';
                    $result[$each['login']][$counter]['note'] = $each['note'];
                    $result[$each['login']][$counter]['cashtype'] = $each['cashtype'];
                }
            }
        } else {
            //log fees
            $sudo = $this->billingConf['SUDO'];
            $cat = $this->billingConf['CAT'];
            $grep = $this->billingConf['GREP'];
            $stglog = $this->alterConf['STG_LOG_PATH'];

            $command = $sudo . ' ' . $cat . ' ' . $stglog . ' | ' . $grep . ' "' . self::MASK_FEE . '"';
            if ($this->dateFilter) {
                $command .= ' | ' . $grep . ' ' . $this->dateFilter;
            }
            $feeadmin = 'stargazer';
            $feenote = '';
            $feecashtype = 0;
            $rawdata = shell_exec($command);

            if (!empty($rawdata)) {
                $cleardata = exploderows($rawdata);
                foreach ($cleardata as $eachline) {
                    $eachfee = explode(' ', $eachline);
                    if (!empty($eachline)) {
                        if (isset($eachfee[self::OFFSET_TIME])) {
                            $counter = strtotime($eachfee[self::OFFSET_DATE] . ' ' . $eachfee[self::OFFSET_TIME]);
                            $loginExtracted = zb_ParseTagData("User '", "': 'cash'", $eachline, false);
                            // trying to avoid duplicate keys
                            while ($this->avoidDTKeysDuplicates and isset($result[$loginExtracted][$counter])) {
                                $counter++;
                            }

                            $feefrom = str_replace("'.", '', $eachfee[self::OFFSET_FROM]);
                            $feeto = str_replace("'.", '', $eachfee[self::OFFSET_TO]);
                            $feefrom = str_replace("'", '', $feefrom);
                            $feeto = str_replace("'", '', $feeto);

                            $result[$loginExtracted][$counter]['login'] = $loginExtracted;
                            $result[$loginExtracted][$counter]['date'] = $eachfee[self::OFFSET_DATE] . ' ' . $eachfee[self::OFFSET_TIME];
                            $result[$loginExtracted][$counter]['admin'] = $feeadmin;
                            $result[$loginExtracted][$counter]['summ'] = $feeto - $feefrom;
                            $result[$loginExtracted][$counter]['from'] = $feefrom;
                            $result[$loginExtracted][$counter]['to'] = $feeto;
                            $result[$loginExtracted][$counter]['operation'] = 'Fee';
                            $result[$loginExtracted][$counter]['note'] = $feenote;
                            $result[$loginExtracted][$counter]['cashtype'] = $feecashtype;
                        }
                    }
                }
            }
        }

        /**
         * Payments
         */
        if ($this->dateFilter) {
            $this->paymentsDb->where('date', 'LIKE', $this->dateFilter . '%');
        }
        $allPayments = $this->paymentsDb->getAll();

        if (!empty($allPayments)) {
            foreach ($allPayments as $io => $each) {
                $counter = strtotime($each['date']);
                // trying to avoid duplicate keys
                while ($this->avoidDTKeysDuplicates and isset($result[$each['login']][$counter])) {
                    $counter++;
                }

                if (ispos($each['note'], 'MOCK:')) {
                    $cashto = $each['balance'];
                }

                if (ispos($each['note'], 'BALANCESET:')) {
                    $cashto = $each['summ'];
                }

                if ((!ispos($each['note'], 'MOCK:')) and (!ispos($each['note'], 'BALANCESET:'))) {
                    if (is_numeric($each['summ']) and is_numeric($each['balance'])) {
                        $cashto = $each['summ'] + $each['balance'];
                    } else {
                        $cashto = __('Corrupted');
                    }
                }

                $result[$each['login']][$counter]['login'] = $each['login'];
                $result[$each['login']][$counter]['date'] = $each['date'];
                $result[$each['login']][$counter]['admin'] = $each['admin'];
                $result[$each['login']][$counter]['summ'] = $each['summ'];
                $result[$each['login']][$counter]['from'] = $each['balance'];
                $result[$each['login']][$counter]['to'] = $cashto;
                $result[$each['login']][$counter]['operation'] = 'Payment';
                $result[$each['login']][$counter]['note'] = $each['note'];
                $result[$each['login']][$counter]['cashtype'] = $each['cashtypeid'];
            }
        }

        /**
         * Balance corrections
         */
        if ($this->dateFilter) {
            $this->corrDb->where('date', 'LIKE', $this->dateFilter . '%');
        }

        $allCorrs = $this->corrDb->getAll();
        if (!empty($allCorrs)) {
            foreach ($allCorrs as $io => $each) {
                $counter = strtotime($each['date']);

                // trying to avoid duplicate keys
                while ($this->avoidDTKeysDuplicates and isset($result[$each['login']][$counter])) {
                    $counter++;
                }

                $cashto = $each['summ'] + $each['balance'];
                $result[$each['login']][$counter]['login'] = $each['login'];
                $result[$each['login']][$counter]['date'] = $each['date'];
                $result[$each['login']][$counter]['admin'] = $each['admin'];
                $result[$each['login']][$counter]['summ'] = $each['summ'];
                $result[$each['login']][$counter]['from'] = $each['balance'];
                $result[$each['login']][$counter]['to'] = $cashto;
                $result[$each['login']][$counter]['operation'] = 'Correcting';
                $result[$each['login']][$counter]['note'] = $each['note'];
                $result[$each['login']][$counter]['cashtype'] = $each['cashtypeid'];
            }
        }
        return ($result);
    }

    /**
     * Harvests some fees from stargazer log to database
     * 
     * @param string $customDateMask parse only explicit date range log records instead of full log scan
     * 
     * @return int
     */
    public function harvestFees($customDateMask = '') {
        $stgLog = $this->alterConf['STG_LOG_PATH'];
        $feeadmin = 'stargazer';
        $feenote = '';
        $feecashtype = 0;
        $feeoperation = 'Fee';
        $result = 0;
        $lineCount = 0;
        $dateMaskFilter = '';
        if ($customDateMask) {
            $dateMaskFilter = $customDateMask;
        }

        if (file_exists($stgLog)) {
            $this->feesDb->selectable('id,hash');
            if ($customDateMask) {
                $this->feesDb->where('date', 'LIKE', $dateMaskFilter . '%');
            }
            $alreadyHarvested = $this->feesDb->getAll('hash');

            //here per-line file read to avoid memory overheads
            $handle = fopen($stgLog, "r");

            // checking for "handle" to be non-empty, e.g. the "fopen()" didn't return "false"
            // or we might find ourselves in an infinite loop
            if (empty($handle)) {
                log_register('FEES HARVESTER: FAILED TO OPEN "' . $stgLog . '"');
            } else {
                while (!feof($handle)) {
                    $eachline = fgets($handle);
                    if (!empty($eachline)) {
                        $requiredDateOffset = false;
                        if ($customDateMask) {
                            if (ispos($eachline, $dateMaskFilter)) {
                                $requiredDateOffset = true;
                            }
                        } else {
                            $requiredDateOffset = true;
                        }

                        if ($requiredDateOffset) {
                            if (ispos($eachline, self::MASK_FEE)) {
                                $eachfee = explode(' ', $eachline);
                                if (isset($eachfee[self::OFFSET_TIME])) {
                                    $feefrom = str_replace("'.", '', $eachfee[self::OFFSET_FROM]);
                                    $feeto   = str_replace("'.", '', $eachfee[self::OFFSET_TO]);
                                    $feefrom = str_replace("'", '', $feefrom);
                                    $feeto   = str_replace("'", '', $feeto);
                                    $login   = $eachfee[self::OFFSET_LOGIN];
                                    $login   = str_replace("'", '', $login);
                                    $login   = str_replace(':', '', $login);
                                    $login   = ubRouting::filters($login, 'mres');
                                    $date    = $eachfee[self::OFFSET_DATE] . ' ' . $eachfee[self::OFFSET_TIME];
                                    $summ    = $feeto - $feefrom;
                                    $hash    = md5($date . $login . $summ . $feefrom . $feeto);
                                    if (!isset($alreadyHarvested[$hash])) {
                                        $this->feesDb->data('hash', $hash);
                                        $this->feesDb->data('login', $login);
                                        $this->feesDb->data('date', $date);
                                        $this->feesDb->data('admin', $feeadmin);
                                        $this->feesDb->data('from', $feefrom);
                                        $this->feesDb->data('to', $feeto);
                                        $this->feesDb->data('summ', $summ);
                                        $this->feesDb->data('note', $feenote);
                                        $this->feesDb->data('cashtype', $feecashtype);
                                        $this->feesDb->create();
                                        $alreadyHarvested[$hash] = array('harvested');
                                        $result++;
                                    }
                                }
                                $lineCount++;
                            }
                        }
                    }
                }

                fclose($handle);
            }
        }

        $timeRange = (!empty($customDateMask)) ? $customDateMask : 'ALL_TIME';
        log_register('FEES HARVESTED `' . $result . '` OF `' . $lineCount . '` RECORDS PARSED BY `' . $timeRange . '`');

        if ($this->onlineShowLastFeeChargeON) {
            $this->getLastFeeChargesAll();
        }

        return ($result);
    }

    /**
     * Stores last fee charge data to system cache
     *
     * @return void
     */
    public static function getLastFeeChargesAll() {
        $feesDb = new NyanORM(self::TABLE_FEES);
        $feesDb->selectable('login, summ, MAX(`date`) as `max_date`');
        $feesDb->groupBy('login');
        $lastFeeChargeData = $feesDb->getAll('login');

        if (!empty($lastFeeChargeData)) {
            $ubCache = new UbillingCache();
            $ubCache->set('STG_LAST_FEE_CHARGE', $lastFeeChargeData);
            log_register('FEES HARVESTER: ONLINE_SHOW_LAST_FEECHARGE data cache have been updated');
        }
    }

    /**
     * Returns array of all payments by some user 
     * 
     * @param string $login existing user login
     * 
     * @return array
     */
    public function getPayments($login) {
        $login = ubRouting::filters($login, 'mres');
        $this->paymentsDb->where('login', '=', $login);
        $allpayments = $this->paymentsDb->getAll();

        $result = array();
        if (!empty($allpayments)) {
            foreach ($allpayments as $io => $eachpayment) {
                $counter = strtotime($eachpayment['date']);

                // trying to avoid duplicate keys
                while ($this->avoidDTKeysDuplicates and array_key_exists($counter, $result)) {
                    $counter++;
                }

                if (ispos($eachpayment['note'], 'MOCK:')) {
                    $cashto = $eachpayment['balance'];
                }

                if (ispos($eachpayment['note'], 'BALANCESET:')) {
                    $cashto = $eachpayment['summ'];
                }

                if ((!ispos($eachpayment['note'], 'MOCK:')) and (!ispos($eachpayment['note'], 'BALANCESET:'))) {
                    if (is_numeric($eachpayment['summ']) and is_numeric($eachpayment['balance'])) {
                        $cashto = $eachpayment['summ'] + $eachpayment['balance'];
                    } else {
                        $cashto = __('Corrupted');
                    }
                }

                $result[$counter]['login'] = $login;
                $result[$counter]['date'] = $eachpayment['date'];
                $result[$counter]['admin'] = $eachpayment['admin'];
                $result[$counter]['summ'] = $eachpayment['summ'];
                $result[$counter]['from'] = $eachpayment['balance'];
                $result[$counter]['to'] = $cashto;
                $result[$counter]['operation'] = 'Payment';
                $result[$counter]['note'] = $eachpayment['note'];
                $result[$counter]['cashtype'] = $eachpayment['cashtypeid'];
            }
        }

        return ($result);
    }

    /**
     * Returns array of all payments of user by some login
     *  
     * @param string $login existing user login
     * @return array
     */
    public function getPaymentsCorr($login) {
        $login = ubRouting::filters($login, 'mres');
        $this->corrDb->where('login', '=', $login);
        $allpayments = $this->corrDb->getAll();

        $result = array();

        if (!empty($allpayments)) {
            foreach ($allpayments as $io => $eachpayment) {
                $counter = strtotime($eachpayment['date']);

                // trying to avoid duplicate keys
                while ($this->avoidDTKeysDuplicates and array_key_exists($counter, $result)) {
                    $counter++;
                }

                $cashto = $eachpayment['summ'] + $eachpayment['balance'];
                $result[$counter]['login'] = $login;
                $result[$counter]['date'] = $eachpayment['date'];
                $result[$counter]['admin'] = $eachpayment['admin'];
                $result[$counter]['summ'] = $eachpayment['summ'];
                $result[$counter]['from'] = $eachpayment['balance'];
                $result[$counter]['to'] = $cashto;
                $result[$counter]['operation'] = 'Correcting';
                $result[$counter]['note'] = $eachpayment['note'];
                $result[$counter]['cashtype'] = $eachpayment['cashtypeid'];
            }
        }

        return ($result);
    }

    /**
     * Returns array of cashtype names
     * 
     * @return array
     */
    function getCashTypeNames() {
        $result = zb_CashGetTypesNamed();
        return ($result);
    }

    /**
     * Renders result of default fundsflow module
     * 
     * @param array $fundsflow
     */
    public function renderArray($fundsflow) {
        global $ubillingConfig;
        $timeIntervalPalette = $ubillingConfig->getAlterParam('FUNDSFLOW_EXTCOLORING');
        $timeIntervalColoringFlag = ($timeIntervalPalette) ? true : false;
        $allcashtypes = $this->getCashTypeNames();
        $allservicenames = zb_VservicesGetAllNamesLabeled();
        @$employeeNames = unserialize(ts_GetAllEmployeeLoginsCached());
        $result = '';

        $tablecells = '';
        if ($timeIntervalColoringFlag) {
            $tablecells .= wf_TableCell('');
        }
        $tablecells .= wf_TableCell(__('Date'));
        $tablecells .= wf_TableCell(__('Cash'));
        $tablecells .= wf_TableCell(__('From'));
        $tablecells .= wf_TableCell(__('To'));
        $tablecells .= wf_TableCell(__('Operation'));
        $tablecells .= wf_TableCell(__('Cash type'));
        $tablecells .= wf_TableCell(__('Notes'));
        $tablecells .= wf_TableCell(__('Admin'));
        $tablerows = wf_TableRow($tablecells, 'row1');

        if (!empty($fundsflow)) {
            foreach ($fundsflow as $io => $each) {
                //default operation type
                $operation = $each['operation'];
                //cashtype setting
                if ($each['cashtype'] != 0) {
                    @$cashtype = $allcashtypes[$each['cashtype']];
                } else {
                    $cashtype = __('Fee');
                }

                //coloring
                $efc = wf_tag('font', true);

                if ($each['operation'] == 'Fee') {
                    $fc = wf_tag('font', false, '', 'color="#' . $this->colorFee . '"');
                }

                if ($each['operation'] == 'Payment') {
                    $fc = wf_tag('font', false, '', 'color="#' . $this->colorPayment . '"');
                }

                if ($each['operation'] == 'Correcting') {
                    $fc = wf_tag('font', false, '', 'color="#' . $this->colorCorrecting . '"');
                }

                if (ispos($each['note'], 'MOCK:')) {
                    $fc = wf_tag('font', false, '', 'color="#' . $this->colorMock . '"');
                }

                if (ispos($each['note'], 'BALANCESET:')) {
                    $fc = wf_tag('font', false, '', 'color="#' . $this->colorSet . '"');
                }

                //virtual services fees
                if ((ispos($each['note'], 'Service:')) and ($each['summ'] < 0)) {
                    $fc = wf_tag('font', false, '', 'color="#' . $this->colorAdditionalFee . '"');
                    $operation = __('Virtual service');
                }

                //virtual services bonuses
                if ((ispos($each['note'], 'Service:')) and ($each['summ'] >= 0)) {
                    $fc = wf_tag('font', false, '', 'color="#' . $this->colorBonus . '"');
                    $operation = __('Bonus');
                }

                //Megogo fees
                if (ispos($each['note'], 'MEGOGO:')) {
                    $fc = wf_tag('font', false, '', 'color="#' . $this->colorAdditionalFee . '"');
                    $operation = __('Service') . ' ' . __('Megogo');
                }

                //OmegaTV fees
                if (ispos($each['note'], 'OMEGATV:')) {
                    $fc = wf_tag('font', false, '', 'color="#' . $this->colorAdditionalFee . '"');
                    $operation = __('Service') . ' ' . __('OmegaTV');
                }

                //ProstoTV fees
                if (ispos($each['note'], 'PROSTOTV:')) {
                    $fc = wf_tag('font', false, '', 'color="#' . $this->colorAdditionalFee . '"');
                    $operation = __('Service') . ' ' . __('ProstoTV');
                }

                //YouTV fees
                if (ispos($each['note'], 'YOUTV:')) {
                    $fc = wf_tag('font', false, '', 'color="#' . $this->colorAdditionalFee . '"');
                    $operation = __('Service') . ' ' . __('YouTV');
                }

                //TrinityTV/SweetTV fees
                if (ispos($each['note'], 'TRINITYTV:')) {
                    $fc = wf_tag('font', false, '', 'color="#' . $this->colorAdditionalFee . '"');
                    $operation = __('Service') . ' ' . __('TrinityTV');
                }

                //OllTV fees
                if (ispos($each['note'], 'OLLTV:')) {
                    $fc = wf_tag('font', false, '', 'color="#' . $this->colorAdditionalFee . '"');
                    $operation = __('Service') . ' ' . __('OllTV');
                }


                //Self crediting fees
                if (ispos($each['note'], 'SCFEE')) {
                    $creditColor = (@$this->alterConf['CREDIT_EVERGARDEN']) ? $this->colorCreditViolet : $this->colorAdditionalFee;
                    $fc = wf_tag('font', false, '', 'color="#' . $creditColor . '"');
                    $operation = __('Service') . ' ' . __('credit');
                }

                //Self freezing fees
                if (ispos($each['note'], 'AFFEE')) {
                    $fc = wf_tag('font', false, '', 'color="#' . $this->colorAdditionalFee . '"');
                    $operation = __('Service') . ' ' . __('freezing');
                }

                //Tariff changing fee
                if (ispos($each['note'], 'TCHANGE:')) {
                    $fc = wf_tag('font', false, '', 'color="#' . $this->colorAdditionalFee . '"');
                    $operation = __('Service') . ' ' . __('change tariff');
                }

                //Penalty fees
                if (ispos($each['note'], 'PENALTY')) {
                    $fc = wf_tag('font', false, '', 'color="#' . $this->colorAdditionalFee . '"');
                    $operation = __('Penalty');
                }

                //SMS reminder service activation
                if (ispos($each['note'], 'REMINDER')) {
                    $fc = wf_tag('font', false, '', 'color="#' . $this->colorAdditionalFee . '"');
                    $operation = __('Service') . ' ' . __('SMS reminder');
                }

                //discount bonuses
                if (ispos($each['note'], 'DISCOUNT:')) {
                    $fc = wf_tag('font', false, '', 'color="#' . $this->colorBonus . '"');
                    $operation = __('Discount');
                }

                //friendship bonuses
                if (ispos($each['note'], 'FRIENDSHIP')) {
                    $fc = wf_tag('font', false, '', 'color="#' . $this->colorBonus . '"');
                    $operation = __('Friendship');
                }

                //manual charged
                if (ispos($each['note'], 'ECHARGE')) {
                    $fc = wf_tag('font', false, '', 'color="#' . $this->colorAdditionalFee . '"');
                    $operation = __('Charged');
                }

                //DDT charged
                if (ispos($each['note'], 'DDT')) {
                    $fc = wf_tag('font', false, '', 'color="#' . $this->colorAdditionalFee . '"');
                    $operation = __('Charged');
                    $cashtype = __('Fee');
                }

                //Visor camera charged
                if (ispos($each['note'], 'VISORCHARGE')) {
                    $fc = wf_tag('font', false, '', 'color="#' . $this->colorAdditionalFee . '"');
                    $operation = __('Service') . ' ' . __('Camera');
                    $cashtype = __('Fee');
                }

                //Visor cash moved from primary account
                if (ispos($each['note'], 'VISORPUSH')) {
                    $fc = wf_tag('font', false, '', 'color="#' . $this->colorBonus . '"');
                    $operation = __('Charged');
                    $cashtype = __('Payment');
                }

                //PowerTariffs Internet service fee
                if (ispos($each['note'], 'PTFEE')) {
                    $fc = wf_tag('font', false, '', 'color="#' . $this->colorFee . '"');
                    $operation = __('Fee');
                    $cashtype = __('Fee');
                }

                //manual charged
                if (ispos($each['note'], 'EXTFEE')) {
                    $fc = wf_tag('font', false, '', 'color="#' . $this->colorAdditionalFee . '"');
                    $operation = __('Fee');
                    $cashtype = __('Fee');
                }

                //taxsup fees
                if (ispos($each['note'], 'TAXSUP:')) {
                    $fc = wf_tag('font', false, '', 'color="#' . $this->colorAdditionalFee . '"');
                    $operation = __('Fee');
                    $cashtype = __('Fee');
                }

                //deferred sale
                if (ispos($each['note'], 'DEFSALE')) {
                    $fc = wf_tag('font', false, '', 'color="#' . $this->colorAdditionalFee . '"');
                    $operation = __('Charged');
                    $cashtype = __('Fee');
                }

                //notes translation
                if ($this->alterConf['TRANSLATE_PAYMENTS_NOTES']) {
                    $displaynote = zb_TranslatePaymentNote($each['note'], $allservicenames);
                } else {
                    $displaynote = $each['note'];
                }

                //admin login detection
                $adminName = (isset($employeeNames[$each['admin']])) ? $employeeNames[$each['admin']] : $each['admin'];

                //time interval coloring
                if ($timeIntervalColoringFlag) {
                    $operationMonth = (!empty($each['date'])) ? date("m", strtotime($each['date'])) : '';
                    $intervalColor = wf_genColorCodeFromText($operationMonth, $timeIntervalPalette);
                }
                $tablecells = '';
                if ($timeIntervalColoringFlag) {
                    $tablecells .= wf_TableCell('&nbsp;', '', '', 'bgcolor="#' . $intervalColor . '"');
                }
                $tablecells .= wf_TableCell($fc . $each['date'] . $efc, '150');
                $tablecells .= wf_TableCell($fc . $each['summ'] . $efc);
                $tablecells .= wf_TableCell($fc . $each['from'] . $efc);
                $tablecells .= wf_TableCell($fc . $each['to'] . $efc);
                $tablecells .= wf_TableCell($fc . __($operation) . $efc);
                $tablecells .= wf_TableCell($cashtype);
                $tablecells .= wf_TableCell($displaynote);
                $tablecells .= wf_TableCell($adminName);
                $tablerows .= wf_TableRow($tablecells, 'row3');
            }

            $legendcells = wf_TableCell(__('Legend') . ':');
            $legendcells .= wf_TableCell(wf_tag('font', false, '', 'color="#' . $this->colorPayment . '"') . __('Payment') . $efc);
            $legendcells .= wf_TableCell(wf_tag('font', false, '', 'color="#' . $this->colorFee . '"') . __('Fee') . $efc);
            $legendcells .= wf_TableCell(wf_tag('font', false, '', 'color="#' . $this->colorBonus . '"') . __('Bonuses') . $efc);
            $legendcells .= wf_TableCell(wf_tag('font', false, '', 'color="#' . $this->colorAdditionalFee . '"') . __('Additional fees') . $efc);
            $legendcells .= wf_TableCell(wf_tag('font', false, '', 'color="#' . $this->colorCorrecting . '"') . __('Correct saldo') . $efc);
            $legendcells .= wf_TableCell(wf_tag('font', false, '', 'color="#' . $this->colorMock . '"') . __('Mock payment') . $efc);
            $legendcells .= wf_TableCell(wf_tag('font', false, '', 'color="#' . $this->colorSet . '"') . __('Set cash') . $efc);
            if (@$this->alterConf['CREDIT_EVERGARDEN']) {
                $legendcells .= wf_TableCell(wf_tag('font', false, '', 'color="#' . $this->colorCreditViolet . '"') . __('Credit') . $efc);
            }
            $legendrows = wf_TableRow($legendcells, 'row3');

            $legend = wf_TableBody($legendrows, '60%', 0, 'glamour');
            $legend .= wf_tag('div', false, '', 'style="clear:both;"') . wf_tag('div', true);
            $legend .= wf_delimiter();

            $result = wf_TableBody($tablerows, '100%', 0, 'sortable');
            $result .= $legend;
        } else {
            $messages = new UbillingMessageHelper();
            $result .= $messages->getStyledMessage(__('Nothing to show'), 'warning');
        }

        return ($result);
    }

    /**
     *  Transforms array for normal output
     * 
     * @param array $fundsflow
     * 
     * @return array
     */
    public function transformArray($fundsflow) {
        if (!empty($fundsflow)) {
            ksort($fundsflow);
            $fundsflow = array_reverse($fundsflow);
        }
        return ($fundsflow);
    }

    /**
     * Extracts funds only with some date pattern
     * 
     * @param array  $fundsflow standard fundsflow array
     * @param string $date
     * 
     * @return array
     */
    public function filterByDate($fundsflow, $date) {
        $result = array();
        if (!empty($fundsflow)) {
            foreach ($fundsflow as $timestamp => $flowdata) {
                if (ispos($flowdata['date'], $date)) {
                    $result[$timestamp] = $flowdata;
                }
            }
        }

        return ($result);
    }

    /**
     * Renders user tags if available
     * 
     * @param string $userLogin
     * 
     * @return string
     */
    protected function renderUserTags($userLogin) {
        $result = '';
        if (!empty($userLogin)) {
            if (isset($this->userTags[$userLogin])) {
                if (!empty($this->userTags[$userLogin])) {
                    $result .= implode(', ', $this->userTags[$userLogin]);
                }
            }
        }
        return ($result);
    }

    /**
     * Renders table for corps users payments/fees stats
     * 
     * @param array $fundsFlows
     * @param array $corpsData
     * @param array $corpUsers
     * @param array $allUserTariffs
     * @param array $allUserContracts
     * 
     * @return string
     */
    public function renderCorpsFlows($num, $fundsFlows, $corpsData, $corpUsers, $allUserContracts, $allUsersCash, $allUserTariffs, $allTariffPrices) {
        $result = '';
        $rawData = array();
        $rawData['balance'] = 0;
        $rawData['payments'] = 0;
        $rawData['paymentscorr'] = 0;
        $rawData['fees'] = 0;
        $rawData['login'] = '';
        $rawData['contract'] = '';
        $rawData['corpid'] = '';
        $rawData['corpname'] = '';
        $rawData['balance'] = 0;
        $rawData['used'] = 0;

        //loading some user tags
        if (empty($this->userTags)) {
            $this->loadUserTags();
        }

        if (!empty($fundsFlows)) {
            foreach ($fundsFlows as $io => $eachop) {
                if ($eachop['operation'] == 'Fee') {
                    $rawData['fees'] = $rawData['fees'] + abs($eachop['summ']);
                }

                if ($eachop['operation'] == 'Payment') {
                    $rawData['payments'] = $rawData['payments'] + abs($eachop['summ']);
                }

                if ($eachop['operation'] == 'Correcting') {
                    $rawData['paymentscorr'] = $rawData['paymentscorr'] + abs($eachop['summ']);
                }
            }


            $rawData['login'] = $eachop['login'];
            if (!isset($this->allBuriedUsers[$rawData['login']])) {
                @$rawData['contract'] = array_search($eachop['login'], $allUserContracts);
                @$rawData['corpid'] = $corpUsers[$eachop['login']];
                @$rawData['corpname'] = $corpsData[$rawData['corpid']]['corpname'];
                $rawData['balance'] = $allUsersCash[$eachop['login']];
                $rawData['used'] = $rawData['fees'];

                //forming result
                $cells = wf_TableCell($num);
                $corpLink = wf_Link('?module=corps&show=corps&editid=' . $rawData['corpid'], $rawData['corpname'], false, '');
                $cells .= wf_TableCell($corpLink);
                if ($rawData['contract']) {
                    $loginLink = wf_Link('?module=userprofile&username=' . $rawData['login'], $rawData['contract'], false, '');
                } else {
                    $loginLink = wf_Link('?module=userprofile&username=' . $rawData['login'], $rawData['login'], false, '');
                }
                if (!empty($rawData['login'])) {
                    $currentTags = $this->renderUserTags($rawData['login']);
                } else {
                    $currentTags = '';
                }
                $cells .= wf_TableCell($loginLink);
                $cells .= wf_TableCell($currentTags);
                $cells .= wf_TableCell(@$allTariffPrices[$allUserTariffs[$rawData['login']]]);
                $cells .= wf_TableCell(round($rawData['payments'], 2));
                $cells .= wf_TableCell(round($rawData['paymentscorr'], 2));
                $cells .= wf_TableCell(round($rawData['balance'], 2));
                $cells .= wf_TableCell(round($rawData['used'], 2));
                $result .= wf_TableRow($cells, 'row3');

                //fill summary data
                $this->fundsTmp['col1'] += $rawData['payments'];
                $this->fundsTmp['col2'] += $rawData['paymentscorr'];
                $this->fundsTmp['col3'] += $rawData['balance'];
                $this->fundsTmp['col4'] += $rawData['used'];
            }
        }
        return ($result);
    }

    /**
     * Returns totals data from previous renderCorpsFlows runs
     * 
     * @return string
     */
    public function renderCorpsFlowsTotal() {
        $result = '';
        if (!empty($this->fundsTmp)) {
            $cells = wf_TableCell('');
            $cells .= wf_TableCell(__('Total'));
            $cells .= wf_TableCell('');
            $cells .= wf_TableCell('');
            $cells .= wf_TableCell('');
            $cells .= wf_TableCell(round($this->fundsTmp['col1'], 2));
            $cells .= wf_TableCell(round($this->fundsTmp['col2'], 2));
            $cells .= wf_TableCell(round($this->fundsTmp['col3'], 2));
            $cells .= wf_TableCell(round($this->fundsTmp['col4'], 2));
            $result .= wf_TableRow($cells, 'row2');
        }
        return ($result);
    }

    /**
     * Returns corpsacts table headers
     * 
     * @param string $year
     * @param string $month
     * 
     * @return string
     */
    public function renderCorpsFlowsHeaders($year, $month) {
        $monthArr = months_array();
        $month = $monthArr[$month];
        $month = rcms_date_localise($month);

        $cd = wf_tag('p', false, '', 'align="center"') . wf_tag('b');
        $cde = wf_tag('b', true) . wf_tag('p', true);

        $result = wf_tag('tr', false, 'row2');
        $result .= wf_TableCell($cd . __('Num #') . $cde, '15', '', 'rowspan="3"');
        $result .= wf_TableCell($cd . __('Organisation') . $cde, '141', '', 'rowspan="3"');
        $result .= wf_TableCell('', '62', '', '');
        $result .= wf_TableCell('', '62', '', '');
        $result .= wf_TableCell($cd . $month . ' ' . $year . $cde, '240', '', 'colspan="5"');
        $result .= wf_tag('tr', true);

        $result .= wf_tag('tr', false, 'row2');
        $result .= wf_TableCell($cd . __('Contract') . $cde, '62', '', 'rowspan="2"');
        $result .= wf_TableCell($cd . __('Tags') . $cde, '62', '', 'rowspan="2"');
        $result .= wf_TableCell($cd . __('Fee') . $cde, '62', '', 'rowspan="2"');
        $result .= wf_TableCell($cd . __('Income') . $cde, '84', '', 'colspan="2"');
        $result .= wf_TableCell($cd . __('Current deposit') . $cde, '68', '', 'rowspan="2"');
        $result .= wf_TableCell($cd . __('Expenditure') . $cde, '84', '', 'rowspan="2"');
        $result .= wf_tag('tr', true);

        $result .= wf_tag('tr', false, 'row2');
        $result .= wf_TableCell($cd . __('on deposit') . $cde, '41');
        $result .= wf_TableCell($cd . __('corr.') . $cde, '41');
        $result .= wf_tag('tr', true);

        return ($result);
    }

    /**
     * Returns year/month selectors form
     * 
     * @return string
     */
    public function renderCorpsFlowsDateForm() {
        $allagents = zb_ContrAhentGetAllData();
        $tmpArr = array();
        $tmpArr[''] = __('Any');
        if (!empty($allagents)) {
            foreach ($allagents as $io => $eachagent) {
                $tmpArr[$eachagent['id']] = $eachagent['contrname'];
            }
        }
        /**
         * Again and again and again and again
         * We Smash the Game BUHA!!
         * And again and again and again and again
         * Remember our name / Furyo 'til I Die
         */
        $inputs = wf_YearSelectorPreset('yearsel', __('Year'), false, ubRouting::post('yearsel')) . ' ';
        $inputs .= wf_MonthSelector('monthsel', __('Month'), ubRouting::post('monthsel'), false) . ' ';
        $inputs .= wf_Selector('agentsel', $tmpArr, __('Contrahent name'), ubRouting::post('agentsel'), false);
        $inputs .= wf_Submit(__('Show'));
        $result = wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Returns user online left days
     * 
     * @param string $login existing users login
     * @param bool   $rawdays show only days count
     * 
     * @return string
     */
    public function getOnlineLeftCount($login, $rawDays = false, $includeVServices = false, $backControl = false) {
        $userData = zb_UserGetStargazerData($login);
        $balanceExpire = '';
        if (!empty($userData)) {
            $userTariff = $userData['Tariff'];
            $userBalanceRaw = $userData['Cash'];
            $userBalance = $userData['Cash'];
            $tariffData = zb_TariffGetData($userTariff);
            $userFeeOffset = 0;
            $daysOnLine = 0;
            $totalVsrvPrice = 0;

            if (empty($tariffData)) {
                //user have no tariff
                $tariffData['name'] = '*_NO_TARIFF_*';
                $tariffData['Fee'] = 0;
                $tariffData['period'] = 'month';
            }

            if ($backControl) {
                $balanceExpire .= wf_BackLink(UserProfile::URL_PROFILE . $login);
            }

            $tariffFee = $tariffData['Fee'];
            $tariffPeriod = isset($tariffData['period']) ? $tariffData['period'] : 'month';
            if ($this->alterConf['PT_ENABLED']) {
                $powerTariffs = new PowerTariffs();
                //suspicious tariff?
                if ($tariffFee == 0) {
                    //wow too much power!
                    if ($powerTariffs->isPowerTariff($userTariff)) {
                        $tariffFee = $powerTariffs->getPowerTariffPrice($userTariff);
                        $userFeeOffset = $powerTariffs->getUserOffsetDay($login);
                        $maxDay = ($powerTariffs->getMaxDay() - 1);

                        if ($userFeeOffset == 0) {
                            $userFeeOffset = (date('j') < $maxDay) ? date('j') + 1 : 1;
                            $daysOnLine = (date('j') < $maxDay) ? 0 : date('j');
                        } else {
                            if ($userFeeOffset < 0) {
                                $userFeeOffset = (date('j') < $maxDay) ? date('j') + 1 : 1;
                            }
                            $daysOnLine = ($userFeeOffset == date('j')) ? 0 : (($userFeeOffset < date('j')) ? -1 : date('j') * -1);
                        }
                        $userFeeOffset = $userFeeOffset - 1;
                    }
                }
            }

            if ($includeVServices) {
                $totalVsrvPrice = zb_VservicesGetUserPricePeriod($login, $tariffPeriod);
                $tariffFee += $totalVsrvPrice;
            }

            if (isset($this->alterConf['SPREAD_FEE'])) {
                if ($this->alterConf['SPREAD_FEE']) {
                    $spreadFee = true;
                } else {
                    $spreadFee = false;
                }
            } else {
                $spreadFee = false;
            }


            if ($userBalance >= 0) {
                if ($tariffFee > 0) {
                    //spread fee
                    if ($spreadFee) {
                        if ($tariffPeriod == 'month') {
                            //monthly period
                            while ($userBalance >= 0) {
                                $daysOnLine++;
                                $dayFee = $tariffFee / date('t', time() + ($daysOnLine * 24 * 60 * 60));
                                $userBalance = $userBalance - $dayFee;
                            }
                        } else {
                            //daily period
                            while ($userBalance >= 0) {
                                $daysOnLine++;
                                $userBalance = $userBalance - $tariffFee;
                            }
                        }
                    } else {
                        //non spread normal fee
                        if ($tariffPeriod == 'month') {
                            //monthly non spread fee
                            while ($userBalance >= 0) {
                                $daysOnLine = $daysOnLine + $userFeeOffset + date('t', time() + ($daysOnLine * 24 * 60 * 60)) - date('d', time() + ($daysOnLine * 24 * 60 * 60)) + 1;
                                $userBalance = $userBalance - $tariffFee;
                            }
                        } else {
                            //daily non spread fee
                            while ($userBalance >= 0) {
                                $daysOnLine++;
                                $userBalance = $userBalance - $tariffFee;
                            }
                        }
                    }
                    $daysLabel = $daysOnLine;
                    $dateLabel = date("d.m.Y", time() + ($daysOnLine * 24 * 60 * 60));
                } else {
                    $daysLabel = '&infin;';
                    $dateLabel = '&infin;';
                }


                $balanceExpire .= wf_tag('span', false, 'alert_info');
                $balanceExpire .= __('Current Cash state') . ': ' . wf_tag('b') . $userBalanceRaw . wf_tag('b', true) . ', ' . __('which should be enough for another');
                $balanceExpire .= ' ' . $daysLabel . ' ' . __('days') . ' ' . __('of service usage') . ' ';
                $balanceExpire .= __('or enought till the') . ' ' . $dateLabel . ' ';
                $balanceExpire .= __('according to the tariff') . ' ' . $userTariff . (($includeVServices) ? ' + ' . __('virtual services') : '') . ' (' . $tariffFee . ' / ' . __($tariffPeriod) . ')';
                $balanceExpire .= wf_tag('span', true);
            } else {
                $balanceExpire .= wf_tag('span', false, 'alert_warning') . __('Current Cash state') . ': ' . wf_tag('b') . $userBalanceRaw . wf_tag('b', true);
                $balanceExpire .= ', ' . __('indebtedness') . '!' . ' ' . wf_tag('span', true);
            }

            if ($rawDays) {
                $balanceExpire = $daysOnLine;
            }
        }
        return ($balanceExpire);
    }

    /**
     * Loads all user and tariffs data
     * 
     * @return void
     */
    public function runDataLoders() {
        $this->loadAllUserData();
        $this->loadAllTariffsData();
    }

    /**
     * Returns user online left days without additional DB queries
     * runDataLoaders() must be run once, before usage
     * 
     * @param string $login existing users login
     * 
     * @return int >=0: days left, -1: debt, -2: zero tariff price
     */
    public function getOnlineLeftCountFast($login, $includeVServices = false) {
        if (isset($this->allUserData[$login])) {
            $userData = $this->allUserData[$login];
        }

        $daysOnLine = 0;
        $totalVsrvPrice = 0;

        if (!empty($userData)) {
            $userTariff = $userData['Tariff'];
            $userBalanceRaw = $userData['Cash'];
            $userBalance = $userData['Cash'];
            if (isset($this->allTariffsData[$userTariff])) {
                $tariffData = $this->allTariffsData[$userTariff];
                $tariffFee = $tariffData['Fee'];
                $tariffPeriod = isset($tariffData['period']) ? $tariffData['period'] : 'month';

                if ($includeVServices) {
                    $totalVsrvPrice = zb_VservicesGetUserPricePeriod($login, $tariffPeriod);
                    $tariffFee += $totalVsrvPrice;
                }

                if (isset($this->alterConf['SPREAD_FEE'])) {
                    if ($this->alterConf['SPREAD_FEE']) {
                        $spreadFee = true;
                    } else {
                        $spreadFee = false;
                    }
                } else {
                    $spreadFee = false;
                }


                if ($userBalance >= 0) {
                    if ($tariffFee > 0) {
                        //spread fee
                        if ($spreadFee) {
                            if ($tariffPeriod == 'month') {
                                //monthly period
                                while ($userBalance >= 0) {
                                    $daysOnLine++;
                                    $dayFee = $tariffFee / date('t', time() + ($daysOnLine * 24 * 60 * 60));
                                    $userBalance = $userBalance - $dayFee;
                                }
                            } else {
                                //daily period
                                while ($userBalance >= 0) {
                                    $daysOnLine++;
                                    $userBalance = $userBalance - $tariffFee;
                                }
                            }
                        } else {
                            //non spread fee
                            if ($tariffPeriod == 'month') {
                                //monthly non spread fee
                                while ($userBalance >= 0) {
                                    $daysOnLine = $daysOnLine + date('t', time() + ($daysOnLine * 24 * 60 * 60)) - date('d', time() + ($daysOnLine * 24 * 60 * 60)) + 1;
                                    $userBalance = $userBalance - $tariffFee;
                                }
                            } else {
                                //daily non spread fee
                                while ($userBalance >= 0) {
                                    $daysOnLine++;
                                    $userBalance = $userBalance - $tariffFee;
                                }
                            }
                        }
                    } else {
                        $daysOnLine = '-2';
                    }
                } else {
                    $daysOnLine = '-1';
                }
            }
        }

        return ($daysOnLine);
    }

    /**
     * Charges month freezing fee (i dont know why this is here)
     * 
     * @return void
     */
    public function makeFreezeMonthFee($debug2log = false) {
        $cost = $this->alterConf['FREEZEMONTH_COST'];
        $cashType = $this->alterConf['FREEZEMONTH_CASHTYPE'];
        $processedUsers = 0;

        if ($debug2log) {
            log_register('FROZEN FEE CHARGE PROCESSING STARTED. COST: ' . $cost . '. CASHTYPE: ' . $cashType);
        }

        if (!empty($this->alterConf['FREEZEMONTH_ONLY_TAG'])) {
            log_register('FROZEN FEE CHARGE PROCESSING ONLY TAGS: ' . $this->alterConf['FREEZEMONTH_ONLY_TAG']);
            $allUsersWithFMOTag = zb_UserGetAllTagsUnique('', $this->alterConf['FREEZEMONTH_ONLY_TAG']);
            $allUserData = array_intersect_key($this->allUserData, $allUsersWithFMOTag);
        } else {
            $allUserData = $this->allUserData;
        }

        if (!empty($this->alterConf['FREEZEMONTH_EXCLUDE_TAG'])) {
            log_register('FROZEN FEE CHARGE EXCLUDING TAGS: ' . $this->alterConf['FREEZEMONTH_EXCLUDE_TAG']);
            $allUsersWithFMETag = zb_UserGetAllTagsUnique('', $this->alterConf['FREEZEMONTH_EXCLUDE_TAG']);
            $allUserData = array_diff_key($allUserData, $allUsersWithFMETag);
        }

        if (!empty($allUserData)) {
            foreach ($allUserData as $eachUser) {
                if ($eachUser['Passive'] == 1) {
                    zb_CashAdd($eachUser['login'], -1 * $cost, 'add', $cashType, 'FROZEN FEE CHARGE:' . $cost);
                    $processedUsers++;

                    if ($debug2log) {
                        log_register('FROZEN FEE CHARGE AMOUNT ' . -1 * $cost . ' FOR USER (' . $eachUser['login'] . ')');
                    }
                }
            }
        } elseif ($debug2log) {
            log_register('FROZEN FEE CHARGE PROCESSING: NO USERS TO PROCESS FOUND');
        }

        if ($debug2log) {
            log_register('FROZEN FEE CHARGE PROCESSING FINISHED FOR ' . $processedUsers . ' USERS');
        }
    }

    /**
     * Process possible duplicates and concatenates $fees, $payments and $corrections arrays
     *
     * @param array $fees
     * @param array $payments
     * @param array $corrections
     *
     * @return mixed
     */
    public function concatAvoidDuplicateKeys($fees, $payments, $corrections) {
        // searching and fixing duplicates in fees - payments array
        $duplicates = array_intersect_key($fees, $payments);

        if (!empty($duplicates)) {
            foreach ($duplicates as $key => $val) {
                // walking through duplicates array and trying to get
                // a unique key instead of existing duplicate key
                if (array_key_exists($key, $payments)) {
                    $tmpVal = $payments[$key];
                    $tmpKey = $key + 1;

                    while (
                        array_key_exists($tmpKey, $fees) or
                        array_key_exists($tmpKey, $payments) or
                        array_key_exists($tmpKey, $corrections)
                    ) {

                        $tmpKey++;
                    }

                    // remove array item with duplicate key and set it's preserved value to new key
                    // we're not worried about the keys order as concatenated array
                    // will be later sorted by keys anyway
                    unset($payments[$key]);
                    $payments[$tmpKey] = $tmpVal;
                }
            }
        }

        // searching and fixing duplicates in fees - corrections array
        $duplicates = array_intersect_key($fees, $corrections);

        if (!empty($duplicates)) {
            foreach ($duplicates as $key => $val) {
                if (array_key_exists($key, $corrections)) {
                    $tmpVal = $corrections[$key];
                    $tmpKey = $key + 1;

                    while (
                        array_key_exists($tmpKey, $fees) or
                        array_key_exists($tmpKey, $payments) or
                        array_key_exists($tmpKey, $corrections)
                    ) {

                        $tmpKey++;
                    }

                    unset($corrections[$key]);
                    $corrections[$tmpKey] = $tmpVal;
                }
            }
        }

        // searching and fixing duplicates in payments - corrections array
        $duplicates = array_intersect_key($payments, $corrections);

        if (!empty($duplicates)) {
            foreach ($duplicates as $key => $val) {
                if (array_key_exists($key, $corrections)) {
                    $tmpVal = $corrections[$key];
                    $tmpKey = $key + 1;

                    while (
                        array_key_exists($tmpKey, $fees) or
                        array_key_exists($tmpKey, $payments) or
                        array_key_exists($tmpKey, $corrections)
                    ) {

                        $tmpKey++;
                    }

                    unset($corrections[$key]);
                    $corrections[$tmpKey] = $tmpVal;
                }
            }
        }

        // concatenate fixed arrays
        $allFundsFlow = $fees + $payments + $corrections;

        return ($allFundsFlow);
    }
}
