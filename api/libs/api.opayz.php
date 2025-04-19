<?php

/**
 * Basic OpenPayz implementation
 */
class OpenPayz {

    /**
     * Contains available virtualid=>realid mappings
     *
     * @var array
     */
    protected $allCustomers = array();

    /**
     * Contains existing transactions data
     *
     * @var array
     */
    protected $allTransactions = array();

    /**
     * Contains full list of preloaded transactions
     *
     * @var array
     */
    protected $wholeTransactions = array();

    /**
     * Existing payment systems names
     *
     * @var array
     */
    protected $allPaySys = array();

    /**
     * Contains system alter.ini config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains available users address data as login=>address
     *
     * @var array
     */
    protected $allAddress = array();

    /**
     * Contains available users realnames as login=>realname
     *
     * @var array
     */
    protected $allRealnames = array();

    /**
     * System message helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Placeholder for UbillingConfig object
     *
     * @var object
     */
    protected $ubConfig = '';

    /**
     * Placeholder for OP_SMS_NOTIFY_PAYMENTS_PULL_INTERVAL alter.ini option
     *
     * @var int
     */
    protected $smsNotysPullInterval = 10;

    /**
     * Placeholder for OP_SMS_NOTIFY_USE_EXTMOBILES alter.ini option
     *
     * @var bool
     */
    protected $smsUseExtMobiles = false;

    /**
     * Placeholder for OP_SMS_NOTIFY_FORCED_TRANSLIT alter.ini option
     *
     * @var bool
     */
    protected $smsForceTranslit = false;

    /**
     * Placeholder for OP_SMS_NOTIFY_DEBUG_ON alter.ini option
     *
     * @var bool
     */
    protected $smsDebugON = false;

    /**
     * Placeholder for OP_SMS_NOTIFY_TEXT alter.ini option
     *
     * @var string
     */
    protected $smsNotysText = '';

    /**
     * Placeholder for OP_SMS_NOTIFY_RESPECT_REMINDER_TAGID alter.ini option
     *
     * @var bool
     */
    protected $smsRespectReminderTagID = false;

    /**
     * Placeholder for REMINDER_TAGID alter.ini option
     *
     * @var int
     */
    protected $smsReminderTagID = 0;

    /**
     * All users tags to use if $smsRespectReminderTagID is true
     *
     * @var array
     */
    protected $allUsersTags = array();

    /**
     * Customers database abstraction layer placeholder
     * 
     * @var object
     */
    protected $customersDb = '';

    /**
     * Transactions database abstraction layer placeholder
     * 
     * @var object
     */
    protected $transactionsDb = '';

    /**
     * Static customers database abstraction layer placeholder
     * 
     * @var object
     */
    protected $staticDb = '';

    /**
     * Length of generated static payment ID
     * 
     * @var int
     */
    protected $payidStaticLen = 0;

    /**
     * Prefix for newly generated static payment IDs
     * 
     * @var string
     */
    protected $payIdStaticPrefix = '';

    /**
     * Funds flow instance placeholder
     * 
     * @var object
     */
    protected $fundsFlow = '';

    /**
     * System caching object placeholder
     * 
     * @var object
     */
    protected $cache = '';

    /**
     * Default cached data timeout. May be configurable in future?
     * 
     * @var int
     */
    protected $cacheTimeout = 86400;

    /**
     * Default on-page transactions number
     *
     * @var int
     */
    protected $onPage = 50;

    /**
     * Contains count of transactions available
     *
     * @var int
     */
    protected $totalTransactionsCount = 0;

    /**
     * Contains filtered transactions count
     *
     * @var int
     */
    protected $filteredTransactionsCount = 0;

    /**
     * Is OPENPAYZ_HIGHLOAD_ENABLE option enabled in alter.ini?
     *
     * @var bool
     */
    protected $hiLoadFlag = false;

    /**
     * Transactions processing StarDust placeholder
     *
     * @var object
     */
    protected $transactionsProcess = '';

    /**
     * Contains default cash type id for transactions deffered processing
     *
     * @var int
     */
    protected $defaultCashTypeId = 1;

    /**
     * Contains default payment systems coloring
     * 
     * @var array
     */
    protected $paySysColors = array(
        'PBANK' => '4ea524',
        'PRIVAT' => '4ea524',
        'PBANKX' => '4ea524',
        'PBANKNEW' => '4ea524',
        'PB_MULTISERV' => '4ea524',
        'EASYPAY' => '136bb5',
        'FBANK' => '541e00',
        'ABANK' => '00d352',
        'IPAY' => '7b9aa9',
        'LIQPAY' => '54b09c',
        'MONOBANK' => '27292f',
        'MONO' => '27292f',
        'PLATEZHKA' => 'ffe007',
        'PLATON' => 'ee6623',
        'PORTMONE' => 'fc3131',
        'PROVIDEX' => 'ed4c6f',
    );

    /**
     * Transactions list ajax callback URL
     */
    const URL_AJAX_SOURCE = '?module=openpayz&ajax=true';

    /**
     * Payment systems charts URL
     */
    const URL_CHARTS = '?module=openpayz&graphs=true';

    /**
     * Default module URL
     */
    const URL_ME = '?module=openpayz';

    /**
     * Some other predefined stuff
     */
    const TABLE_CUSTOMERS = 'op_customers';
    const TABLE_TRANSACTIONS = 'op_transactions';
    const TABLE_STATIC = 'op_static';
    const KEY_PSYS = 'OPPAYSYS';
    const KEY_CHARTS = 'OPCHARTS_';
    const PID_PROCESSING = 'OP_PROCESSING';
    const CUSTOM_CASHTYPE_PREFIX = 'CASHTYPEID_';

    /**
     * Creates new OpenPayz instance
     * 
     * @param bool $loadPaySys
     * @param bool $loadCustomers
     * 
     * @return void
     */
    public function __construct($loadPaySys = false, $loadCustomers = false) {
        global $ubillingConfig;
        $this->ubConfig = $ubillingConfig;

        $this->loadAlter();
        $this->loadOptions();
        $this->initMessages();
        $this->initDbLayers();
        $this->initCache();
        $this->initStarDust();

        //preloading some optional data
        if ($loadPaySys) {
            $this->loadPaySys();
        }

        if ($loadCustomers) {
            $this->loadCustomers();
        }

        if ($this->smsRespectReminderTagID) {
            $this->allUsersTags = zb_UserGetAllTags();
        }
    }

    /**
     * Loads global alter config into protected property
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadAlter() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Getting an alter.ini options
     */
    protected function loadOptions() {
        $this->smsNotysPullInterval = ubRouting::filters($this->ubConfig->getAlterParam('OP_SMS_NOTIFY_PAYMENTS_PULL_INTERVAL', 5), 'int');
        $this->smsUseExtMobiles = ubRouting::filters($this->ubConfig->getAlterParam('OP_SMS_NOTIFY_USE_EXTMOBILES'), 'fi', FILTER_VALIDATE_BOOLEAN);
        $this->smsForceTranslit = ubRouting::filters($this->ubConfig->getAlterParam('OP_SMS_NOTIFY_FORCED_TRANSLIT'), 'fi', FILTER_VALIDATE_BOOLEAN);
        $this->smsDebugON = ubRouting::filters($this->ubConfig->getAlterParam('OP_SMS_NOTIFY_DEBUG_ON'), 'fi', FILTER_VALIDATE_BOOLEAN);
        $this->smsNotysText = $this->ubConfig->getAlterParam('OP_SMS_NOTIFY_TEXT', '');
        $this->smsRespectReminderTagID = ubRouting::filters($this->ubConfig->getAlterParam('OP_SMS_NOTIFY_RESPECT_REMINDER_TAGID'), 'fi', FILTER_VALIDATE_BOOLEAN);
        $this->smsReminderTagID = ubRouting::filters($this->ubConfig->getAlterParam('REMINDER_TAGID', 0), 'int');
        $this->payidStaticLen = ubRouting::filters($this->ubConfig->getAlterParam('OPENPAYZ_STATIC_ID', 0), 'int');
        $this->payIdStaticPrefix = $this->ubConfig->getAlterParam('OPENPAYZ_STATIC_ID_PREFIX', '');
        $this->hiLoadFlag = $this->ubConfig->getAlterParam('OPENPAYZ_HIGHLOAD_ENABLE', false);
        $this->defaultCashTypeId = $this->ubConfig->getAlterParam('OPENPAYZ_CASHTYPEID', 1);
    }

    /**
     * Inits all required database abstraction layers
     * 
     * @return void
     */
    protected function initDbLayers() {
        $this->customersDb = new NyanORM(self::TABLE_CUSTOMERS);
        $this->transactionsDb = new NyanORM(self::TABLE_TRANSACTIONS);
        $this->staticDb = new NyanORM(self::TABLE_STATIC);
    }

    /**
     * Inits Ubilling caching engine for further usage
     * 
     * @return void
     */
    protected function initCache() {
        $this->cache = new UbillingCache();
    }

    /**
     * Inits StarDust process manager for payment transactions processing
     *
     * @return void
     */
    protected function initStarDust() {
        $this->transactionsProcess = new StarDust(self::PID_PROCESSING);
    }

    /**
     * Loads users address list into protected property
     * 
     * @return void
     */
    protected function loadAddress() {
        $this->allAddress = zb_AddressGetFulladdresslistCached();
    }

    /**
     * Loads users realnames list into protected property
     * 
     * @return void
     */
    protected function loadRealname() {
        $this->allRealnames = zb_UserGetAllRealnames();
    }

    /**
     * Loads all op_customers from database into protected prop
     * 
     * @return void
     */
    protected function loadCustomers() {
        $allCustomers = $this->customersDb->getAll();
        if (!empty($allCustomers)) {
            foreach ($allCustomers as $io => $eachcustomer) {
                $this->allCustomers[$eachcustomer['virtualid']] = $eachcustomer['realid'];
            }
        }
    }

    /**
     * Loads available openpayz transactions into private data property
     * 
     * @param int $year
     * @param int $transactionId
     * 
     * @return void
     */
    protected function loadTransactions($year = '', $transactionId = 0) {
        $year = ubRouting::filters($year, 'int');
        $transactionId = ubRouting::filters($transactionId, 'int');
        if (!empty($year) and $year != '1488') {
            $this->transactionsDb->where('date', 'LIKE', $year . '-%');
        }

        if ($transactionId) {
            //only one transaction
            $this->transactionsDb->where('id', '=', $transactionId);
        } else {
            //or natural ordering?
            $this->transactionsDb->orderBy('id', 'ASC');
        }

        $this->allTransactions = $this->transactionsDb->getAll('id');
    }

    /**
     * Public getter of preloaded users mappings as paymentId=>userLogin
     * 
     * @return array
     */
    public function getCustomers() {
        if (empty($this->allCustomers)) {
            $this->loadCustomers();
        }
        return ($this->allCustomers);
    }

    /**
     * Public getter of preloaded users mappings as userLogin=>paymentId
     * 
     * @return array
     */
    public function getCustomersPaymentIds() {
        $result = array();
        if (empty($this->allCustomers)) {
            $this->loadCustomers();
        }
        if (!empty($this->allCustomers)) {
            $result = array_flip($this->allCustomers);
        }
        return ($result);
    }

    /**
     * Returns some specified user paymentId
     *
     * @param string $userLogin
     * 
     * @return string
     */
    public function getCustomerPaymentId($userLogin) {
        $result = '';
        $userLogin = ubRouting::filters($userLogin, 'mres');
        if (!empty($this->allCustomers)) {
            //already preloaded from database
            $tmp = array_flip($this->allCustomers);
            if (isset($tmp[$userLogin])) {
                $result = $tmp[$userLogin];
            }
        } else {
            //not loaded at start, performing database query
            $this->customersDb->where('realid', '=', $userLogin);
            $rawData = $this->customersDb->getAll();
            if (!empty($rawData)) {
                $result = $rawData[0]['virtualid'];
            }
        }
        return ($result);
    }

    /**
     * Loads array of available payment systems
     * 
     * @return void
     */
    protected function loadPaySys() {
        $paySysCached = $this->cache->get(self::KEY_PSYS, $this->cacheTimeout);
        if (empty($paySysCached)) {
            $all = $this->transactionsDb->getAll('', true, 'paysys');
            if (!empty($all)) {
                foreach ($all as $io => $each) {
                    $this->allPaySys[$each['paysys']] = $each['paysys'];
                }
            }
            $this->cache->set(self::KEY_PSYS, $this->allPaySys, $this->cacheTimeout);
        } else {
            $this->allPaySys = $paySysCached;
        }
    }

    /**
     * Inits system messages helper
     * 
     * @return voids
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Generates unique payment ID of configurable length
     * 
     * @return int
     */
    protected function generateUniquePaymentId() {
        $result = 0;
        if ($this->payidStaticLen > 0) {
            $result = $this->payIdStaticPrefix . zb_rand_digits($this->payidStaticLen);
            while (isset($this->allCustomers[$result])) {
                $result = $this->payIdStaticPrefix . zb_rand_digits($this->payidStaticLen);
            }
        }
        return ($result);
    }

    /**
     * Creates new static payment ID in database for some user
     * 
     * @param string $userLogin
     * 
     * @return int 
     */
    public function registerStaticPaymentId($userLogin) {
        $result = '';
        $userLoginF = ubRouting::filters($userLogin, 'mres');
        if ($this->payidStaticLen > 0) {
            $noPaymentId = true; //payment ID registered flag
            $existingPayId = ''; //contains existing payment ID if it exists now
            if (!empty($this->allCustomers)) {
                foreach ($this->allCustomers as $eachPayId => $eachLogin) {
                    if ($eachLogin == $userLogin) {
                        $noPaymentId = false;
                        $existingPayId = $eachPayId;
                        break;
                    }
                }
            }

            //user have no payment ID yet?
            if ($noPaymentId) {
                $newPaymentId = $this->generateUniquePaymentId();
                $this->staticDb->data('realid', $userLoginF);
                $this->staticDb->data('virtualid', $newPaymentId);
                $this->staticDb->create();
                log_register('OPENPAYZ STATIC REGISTER (' . $userLogin . ') PAYID `' . $newPaymentId . '`');
                $result = $newPaymentId;
            } else {
                log_register('OPENPAYZ STATIC REGISTER FAIL (' . $userLogin . ') ALREADY `' . $existingPayId . '`');
            }
        }
        return ($result);
    }

    /**
     * Deregisters static payment ID by username
     * 
     * @param string $userLogin
     * 
     * @return void
     */
    public function degisterStaticPaymentId($userLogin) {
        $userLoginF = ubRouting::filters($userLogin, 'mres');
        $this->staticDb->where('realid', '=', $userLoginF);
        $this->staticDb->delete();
        log_register('OPENPAYZ STATIC DELETE (' . $userLogin . ')');
    }

    /**
     * Returns openpayz search form
     * 
     * @return string
     */
    public function renderSearchForm() {
        $result = '';
        $curYear = (ubRouting::checkPost('searchyear')) ? ubRouting::post('searchyear', 'int') : date("Y");
        $curMonth = (ubRouting::checkPost('searchmonth')) ? ubRouting::post('searchmonth', 'int') : date("m");
        $curPaysys = (ubRouting::checkPost('searchpaysys')) ? ubRouting::post('searchpaysys', 'mres') : '';
        /**
         * No lights, no sights, every fright, every night
         * Alone, hurt and cold, she‘s shackled to the pipes
         */
        $paySysSelector['ANY'] = __('All');
        $paySysSelector += $this->allPaySys;

        $inputs = wf_YearSelectorPreset('searchyear', __('Year'), false, $curYear) . ' ';
        $inputs .= wf_MonthSelector('searchmonth', __('Month'), $curMonth, false) . ' ';
        $inputs .= wf_Selector('searchpaysys', $paySysSelector, __('Payment system'), $curPaysys, false) . ' ';
        $inputs .= wf_Submit(__('Search'));

        $result .= wf_BackLink(self::URL_ME);
        $result .= wf_delimiter();
        $result .= wf_Form("", 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Performs openpayz search in database and shows result
     * 
     * @param int    $year
     * @param string $month
     * @param string $paysys
     * 
     * @return void
     */
    public function doSearch($year, $month, $paysys) {
        $this->loadTransactions();
        $this->loadRealname();
        $this->loadAddress();
        $this->loadCustomers();

        $csvdata = '';
        $totalsumm = 0;
        $totalcount = 0;

        $cells = wf_TableCell(__('ID'));
        $cells .= wf_TableCell(__('Date'));
        $cells .= wf_TableCell(__('Cash'));
        $cells .= wf_TableCell(__('Payment ID'));
        $cells .= wf_TableCell(__('Real Name'));
        $cells .= wf_TableCell(__('Full address'));
        $cells .= wf_TableCell(__('Payment system'));
        $cells .= wf_TableCell(__('Processed'));
        $cells .= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($this->allTransactions)) {
            $csvdata = __('ID') . ';' . __('Date') . ';' . __('Cash') . ';' . __('Payment ID') . ';' . __('Real Name') . ';' . __('Full address') . ';' . __('Payment system') . "\n";
            foreach ($this->allTransactions as $io => $eachtransaction) {
                if (ispos($eachtransaction['date'], $year . '-' . $month)) {
                    if (($eachtransaction['paysys'] == $paysys) or (($paysys == 'ANY'))) {
                        @$user_login = $this->allCustomers[$eachtransaction['customerid']];
                        @$user_realname = $this->allRealnames[$user_login];
                        @$user_address = $this->allAddress[$user_login];

                        $cells = wf_TableCell($eachtransaction['id']);
                        $cells .= wf_TableCell($eachtransaction['date']);
                        $cells .= wf_TableCell($eachtransaction['summ']);
                        $cells .= wf_TableCell($eachtransaction['customerid']);
                        $cells .= wf_TableCell($user_realname);
                        $cells .= wf_TableCell($user_address);
                        $cells .= wf_TableCell($eachtransaction['paysys']);
                        $cells .= wf_TableCell(web_bool_led($eachtransaction['processed']));
                        $cells .= wf_TableCell(wf_Link('?module=userprofile&username=' . $user_login, web_profile_icon()));
                        $rows .= wf_TableRow($cells, 'row3');
                        if ($eachtransaction['summ'] > 0) {
                            $totalsumm = $totalsumm + $eachtransaction['summ'];
                            $totalcount = $totalcount + 1;
                        }

                        $csvSumm = str_replace('.', ',', $eachtransaction['summ']);
                        $csvdata .= $eachtransaction['id'] . ';' . $eachtransaction['date'] . ';' . $csvSumm . ';' . $eachtransaction['customerid'] . ';' . $user_realname . ';' . $user_address . ';' . $eachtransaction['paysys'] . "\n";
                    }
                }
            }
        }

        $result = wf_TableBody($rows, '100%', '0', 'sortable');
        $result .= __('Total') . ': ' . $totalcount . ' ' . __('payments') . ' ' . __('with total amount') . ' ' . $totalsumm;

        if (!empty($csvdata)) {
            $exportFilename = 'exports/opsearch_' . $paysys . '_' . $year . '-' . $month . '.csv';
            file_put_contents($exportFilename, $csvdata);
            $exportLink = wf_Link(self::URL_ME . '&dload=' . base64_encode($exportFilename), wf_img('skins/excel.gif', __('Export')), false, '');
        } else {
            $exportLink = '';
        }

        show_window(__('Search results') . ' ' . $paysys . ': ' . $year . '-' . $month . ' ' . $exportLink, $result);
    }

    /**
     * Inits funds flow object instance
     * 
     * @return void
     */
    protected function initFundsFlow() {
        $this->fundsFlow = new FundsFlow();
        $this->fundsFlow->runDataLoders();
    }

    /**
     * Returns user online left days without additional DB queries
     * runDataLoaders() must be run once, before usage
     * 
     * @param string $login existing users login
     * 
     * @return int >=0: days left, -1: debt, -2: zero tariff price
     */
    protected function getUserOnlineLeftDayCount($login) {
        $result = 0;
        $onlineLeftCount = $this->fundsFlow->getOnlineLeftCountFast($login);
        if ($onlineLeftCount >= 0) {
            $result = $onlineLeftCount;
        }
        return ($result);
    }

    /**
     * Returns user online to date
     * 
     * @param string $login existing users login
     * 
     * @return string
     */
    protected function getUserOnlineToDate($login) {
        $result = date("d.m.Y");
        $daysOnLine = $this->fundsFlow->getOnlineLeftCountFast($login);
        if ($daysOnLine >= 0) {
            $result = date("d.m.Y", time() + ($daysOnLine * 24 * 60 * 60));
        }
        return ($result);
    }

    /**
     * Renders year selection form for charts
     * 
     * @return string
     */
    protected function renderChartsYearForm() {
        $result = '';
        $curYear = ubRouting::checkPost('chartsyear') ? ubRouting::post('chartsyear', 'int') : curyear();
        $inputs = wf_YearSelectorPreset('chartsyear', __('Year'), false, $curYear, true) . ' ';
        $inputs .= wf_Submit(__('Show'));

        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Renders per-payment system openpayz transaction charts
     * 
     * @return string
     */
    public function renderGraphs() {
        $showYear = ubRouting::checkPost('chartsyear') ? ubRouting::post('chartsyear', 'int') : curyear();

        $curMonth = curmonth();
        $curDay = curdate();
        $curYear = curyear();
        $psysdata = array();
        $gcAllData = array();
        $gcYearData = array();
        $gcMonthData = array();
        $gcDayData = array();
        $gchartsData = array();

        $chartsOptions = "
            'focusTarget': 'category',
                        'hAxis': {
                        'color': 'none',
                            'baselineColor': 'none',
                    },
                        'vAxis': {
                        'color': 'none',
                            'baselineColor': 'none',
                    },
                        'curveType': 'function',
                        'pointSize': 5,
                        'crosshair': {
                        trigger: 'none'
                    },";

        $result = wf_BackLink(self::URL_ME, '', true);
        //cahche data extraction
        $cacheKeyName = self::KEY_CHARTS . $showYear;
        $cacheDataRaw = $this->cache->get($cacheKeyName, $this->cacheTimeout);
        //something in cache
        if (!empty($cacheDataRaw)) {
            $psysdata = $cacheDataRaw['psysdata'];
            $gcYearData = $cacheDataRaw['gcYearData'];
            $gcMonthData = $cacheDataRaw['gcMonthData'];
            $gcDayData = $cacheDataRaw['gcDayData'];
        } else {
            $cacheDataRaw = array();
            //real data loading
            $this->loadTransactions($showYear);
            if (!empty($this->allTransactions)) {
                foreach ($this->allTransactions as $io => $each) {
                    $timestamp = strtotime($each['date']);

                    $date = date("Y-m", $timestamp);
                    $dateFull = date("Y-m-d", $timestamp);
                    if (isset($psysdata[$each['paysys']][$date]['count'])) {
                        $psysdata[$each['paysys']][$date]['count']++;
                        $psysdata[$each['paysys']][$date]['summ'] = $psysdata[$each['paysys']][$date]['summ'] + $each['summ'];
                    } else {
                        $psysdata[$each['paysys']][$date]['count'] = 1;
                        $psysdata[$each['paysys']][$date]['summ'] = $each['summ'];
                    }

                    //current year stats
                    if (ispos($date, $curYear)) {
                        if (isset($gcYearData[$each['paysys']])) {
                            $gcYearData[$each['paysys']]++;
                        } else {
                            $gcYearData[$each['paysys']] = 1;
                        }
                    }

                    //current month stats
                    if (ispos($date, $curMonth)) {
                        if (isset($gcMonthData[$each['paysys']])) {
                            $gcMonthData[$each['paysys']]++;
                        } else {
                            $gcMonthData[$each['paysys']] = 1;
                        }
                    }

                    //current day stats
                    if (ispos($dateFull, $curDay)) {
                        if (isset($gcDayData[$each['paysys']])) {
                            $gcDayData[$each['paysys']]++;
                        } else {
                            $gcDayData[$each['paysys']] = 1;
                        }
                    }
                }
            }

            //store in cache
            $cacheDataRaw['psysdata'] = $psysdata;
            $cacheDataRaw['gcYearData'] = $gcYearData;
            $cacheDataRaw['gcMonthData'] = $gcMonthData;
            $cacheDataRaw['gcDayData'] = $gcDayData;
            $this->cache->set($cacheKeyName, $cacheDataRaw, $this->cacheTimeout);
        }

        $chartOpts = "chartArea: {  width: '90%', height: '90%' }, legend : {position: 'right'}, ";
        $customPalette = @$this->altCfg['OPENPAYZ_PALETTE'];

        if (!empty($gcDayData)) {
            $gcDayPie = wf_gcharts3DPie($gcDayData, __('Today'), '300px', '300px', $chartOpts, $customPalette, $this->paySysColors);
        } else {
            $gcDayPie = '';
        }

        if (!empty($gcMonthData)) {
            $gcMonthPie = wf_gcharts3DPie($gcMonthData, __('Current month'), '300px', '300px', $chartOpts, $customPalette, $this->paySysColors);
        } else {
            $gcMonthPie = '';
        }

        if (!empty($gcYearData)) {
            $gcYearPie = wf_gcharts3DPie($gcYearData, __('Current year'), '300px', '300px', $chartOpts, $customPalette, $this->paySysColors);
        } else {
            $gcYearPie = '';
        }


        $gcells = wf_TableCell($gcYearPie);
        $gcells .= wf_TableCell($gcMonthPie);
        $gcells .= wf_TableCell($gcDayPie);
        $grows = wf_TableRow($gcells);

        $result .= wf_tag('br');
        $result .= $this->renderChartsYearForm();
        $result .= wf_CleanDiv();
        $result .= wf_TableBody($grows, '100%', 0, '');

        if (!empty($psysdata)) {
            foreach ($psysdata as $psys => $opdate) {
                $gchartsData[] = array(__('Date'), __('Count'), __('Cash'));
                foreach ($opdate as $datestamp => $optrans) {
                    $gchartsData[] = array($datestamp, $optrans['count'], $optrans['summ']);
                }

                $result .= wf_gchartsLine($gchartsData, $psys, '100%', '300px;', $chartsOptions);
                $gchartsData = array();
            }
        }
        return ($result);
    }


    /**
     * Returns openpayz transaction data by its ID
     * 
     * @param int $transactionid
     * 
     * @return array
     */
    function transactionGetData($transactionid) {
        $result = array();
        if (empty($this->allTransactions)) {
            $this->loadTransactions();
        }
        if (isset($this->allTransactions[$transactionid])) {
            $result = $this->allTransactions[$transactionid];
        }
        return ($result);
    }


    /**
     * Renders transaction details
     * 
     * @param int $transactionId
     * 
     * @return void
     */
    public function renderTransactionDetails($transactionId) {
        $transactionId = ubRouting::filters($transactionId, 'int');
        $this->loadTransactions('', $transactionId);

        $result = '';
        $result .= wf_BackLink(self::URL_ME, '', true);
        if (isset($this->allTransactions[$transactionId])) {
            $result .= wf_tag('pre', false, 'floatpanelswide', '') . print_r($this->allTransactions[$transactionId], true) . wf_tag('pre', true);
            $result .= wf_CleanDiv();
        } else {
            $result .= $this->messages->getStyledMessage(__('Non existent transaction ID'), 'error');
        }

        show_window(__('Transaction') . ': ' . $transactionId, $result);
    }

    /**
     * Pulls payments from payment DB for further SMS notifications processing
     *
     * @throws Exception
     *
     * @return void
     */
    public function pullNotysPayments() {
        $paymentsFound = array();
        $paymentsFoundCnt = 0;
        $tabPayments = new NyanORM('payments');
        $tabNotifications = new NyanORM('op_sms_notifications');

        if ($this->smsDebugON) {
            $tabPayments->setDebug(true, true);
            $tabNotifications->setDebug(true, true);
        }

        // trying to get timestamp of the very last pulled payment
        // if nothing was pulled yet - current datetime used
        $tabNotifications->selectable('MAX(date) AS max_date');
        $pullDateTimeLast = $tabNotifications->getAll();

        if (!empty($pullDateTimeLast[0]['max_date'])) {
            $pullDateTimeLast = $pullDateTimeLast[0]['max_date'];
        } else {
            $pullDateTimeLast = curdatetime();
        }

        $pullDateTimeFrom = date('Y:m:d H:i:s', strtotime($pullDateTimeLast . '-' . $this->smsNotysPullInterval . 'min'));

        $tabPayments->where('date', '>=', $pullDateTimeFrom);
        $tabPayments->where('admin', '=', 'openpayz');
        $paymentsFound = $tabPayments->getAll('id');

        if (!empty($paymentsFound)) {
            // get the payments IDs
            $tmpPaymIDs = array_keys($paymentsFound);
            $tmpPaymIDs = implode(',', $tmpPaymIDs);

            if ($this->smsDebugON) {
                log_register('OPAYZ SMS NOTIFY: found payments IDs: ' . $tmpPaymIDs);
            }

            // try to select those IDs from 'op_sms_notifications'
            $tabNotifications->whereRaw(' `payment_id` IN (' . $tmpPaymIDs . ') ');
            $tabNotifications->selectable(array('payment_id'));
            $tmpNotysFound = $tabNotifications->getAll('payment_id');

            // exclude found payments IDs
            if (!empty($tmpNotysFound)) {
                $paymentsFound = array_diff_key($paymentsFound, $tmpNotysFound);

                if ($this->smsDebugON) {
                    log_register('OPAYZ SMS NOTIFY: found payments IDs with already sent notifications: ' . print_r($tmpNotysFound, true));
                    log_register('OPAYZ SMS NOTIFY: found payments IDs after exclusion of IDs with already sent notifications: ' . print_r($paymentsFound, true));
                }
            }

            if (!empty($paymentsFound)) {
                foreach ($paymentsFound as $eachID => $eachRec) {
                    $tmpLogin = $eachRec['login'];

                    // check logins for REMINDER_TAGID presence if $this->smsRespectReminderTagID is true
                    if ($this->smsRespectReminderTagID and !empty($this->allUsersTags)) {
                        // skip this payment if login doesn't have REMINDER_TAGID assigned
                        if (empty($this->allUsersTags[$tmpLogin][$this->smsReminderTagID])) {
                            if ($this->smsDebugON) {
                                log_register('OPAYZ SMS NOTIFY: skipping payment with ID: ' . $eachID . ' for login: (' . $tmpLogin . ') as it doesn\'t have REMINDER_TAGID [' . $this->smsReminderTagID . '] assigned');
                            }

                            continue;
                        }
                    }

                    $tmpRec = array(
                        'payment_id' => $eachID,
                        'date' => $eachRec['date'],
                        'login' => $tmpLogin,
                        'balance' => $eachRec['balance'] + $eachRec['summ'],
                        'summ' => $eachRec['summ']
                    );

                    $tabNotifications->dataArr($tmpRec);
                    $tabNotifications->create();
                    $paymentsFoundCnt++;
                }
            }
        }

        if (!empty($paymentsFoundCnt) or $this->smsDebugON) {
            log_register('OPAYZ SMS NOTIFY: pulled ' . $paymentsFoundCnt . ' payment records, starting from: ' . $pullDateTimeFrom);
        }
    }

    /**
     * Handles notifications processing for collected payments
     *
     * @throws Exception
     *
     * @return void
     */
    public function processNotys() {
        $sentCount = 0;
        $tabNotifications = new NyanORM('op_sms_notifications');

        if ($this->smsDebugON) {
            $tabNotifications->setDebug(true, true);
        }

        $tabNotifications->where('processed', '=', '0');
        $notysToPush = $tabNotifications->getAll('id');

        if (!empty($notysToPush)) {
            $ubSMS = new UbillingSMS();
            $allPhones = zb_GetAllAllPhonesCache();
            $this->initFundsFlow();

            // init SMS directions cache
            if ($this->ubConfig->getAlterParam('SMS_SERVICES_ADVANCED_ENABLED')) {
                $smsDirections = new SMSDirections();
            }

            foreach ($notysToPush as $eachID => $eachRec) {
                $login = $eachRec['login'];
                $noMobilesfound = false;

                if ($this->smsDebugON) {
                    log_register('OPAYZ SMS NOTIFY: sending message for payment: ' . print_r($eachRec, true));
                }

                if (isset($allPhones[$login])) {
                    $usrMobiles = array($allPhones[$login]['mobile']);

                    if ($this->smsUseExtMobiles) {
                        $usrMobiles = array_merge($usrMobiles, $allPhones[$login]['mobiles']);
                    }

                    if (!empty($usrMobiles)) {
                        if ($this->smsDebugON) {
                            log_register('OPAYZ SMS NOTIFY: found cell numbers: ' . implode(',', $usrMobiles) . ' for user (' . $login . ')');
                        }

                        $msgText = $this->smsNotysText;
                        $msgText = str_ireplace('{LOGIN}', $login, $msgText);
                        $msgText = str_ireplace('{USERONLINELEFTDAY}', $this->getUserOnlineLeftDayCount($login), $msgText);
                        $msgText = str_ireplace('{USERONLINETODATE}', $this->getUserOnlineToDate($login), $msgText);
                        $msgText = str_ireplace('{ROUNDBALANCE}', round($eachRec['balance'], 2), $msgText);
                        $msgText = str_ireplace('{ROUNDPAYMENTAMOUNT}', round($eachRec['summ'], 2), $msgText);

                        foreach ($usrMobiles as $mobile) {
                            $mobile = zb_CleanMobileNumber($mobile);
                            $queueFile = $ubSMS->sendSMS($mobile, $msgText, $this->smsForceTranslit, 'OPENPAYZ');
                            $ubSMS->setDirection($queueFile, 'user_login', $login);

                            if ($this->smsDebugON) {
                                log_register('OPAYZ SMS NOTIFY: sent message for user (' . $login . ') to ' . $mobile);
                            }
                        }

                        $tabNotifications->where('id', '=', $eachID);
                        $tabNotifications->data('processed', '1');
                        $tabNotifications->save();
                        $sentCount++;
                    } else {
                        $noMobilesfound = true;
                    }
                } else {
                    $noMobilesfound = true;
                }

                if ($this->smsDebugON and $noMobilesfound) {
                    log_register('OPAYZ SMS NOTIFY: unable to send message for user (' . $login . ') - no assigned cell phone numbers found');
                }
            }
        }

        if ($sentCount > 0 or $this->smsDebugON) {
            log_register('OPAYZ SMS NOTIFY: sent ' . $sentCount . ' messages');
        }
    }

    /**
     * Returns transactions list container
     * 
     * @return void
     */
    public function renderTransactionsList() {
        $filterNumber = '';
        $filtercustomerId = '';
        $opts = '"order": [[ 0, "desc" ]]';
        $columns = array(__('Date'), __('Number'), __('User'), __('Tags'), __('File'));
        if (ubRouting::checkGet('filtercustomerid')) {
            $filtercustomerId = '&filtercustomerid=' . ubRouting::get('username');
        }
        if (ubRouting::checkGet('renderall')) {
            $filterNumber = '&renderall=true';
        }

        $renderAllControl = (ubRouting::checkGet('renderall')) ? wf_Link(self::URL_ME, wf_img('skins/done_icon.png', __('Current year'))) : wf_Link(self::URL_ME . '&renderall=true', wf_img('skins/allcalls.png', __('All time')));
        $columns = array('ID', 'Date', 'Cash', 'Payment ID', 'Real Name', 'Full address', 'Payment system', 'Processed');

        $controls = $renderAllControl;
        $controls .= wf_Link(self::URL_CHARTS, wf_img('skins/icon_stats.gif', __('Graphs')), false, '') . ' ';
        $controls .= wf_Link(self::URL_ME . '&transactionsearch=true', web_icon_search(), false, '');


        $container = wf_JqDtLoader($columns, self::URL_ME . '&ajax=true' . $filtercustomerId . $filterNumber, false, __('Payments'), $this->onPage, $opts, false, '', '', true);
        show_window(__('OpenPayz transactions') . ' ' . $controls, $container);
    }

    /**
     * Renders json transactions list
     * 
     * @param string $filtercustomerId
     * @param bool $renderAll
     * 
     * @return void
     */
    public function jsonTransactionsList($filtercustomerId = '', $renderAll = false) {
        $this->loadCustomers();
        $this->loadAddress();
        $this->loadRealname();


        $this->transactionsLoader($filtercustomerId, $renderAll);
        $json = new wf_JqDtHelper(true);
        $json->setTotalRowsCount($this->totalTransactionsCount);
        $json->setFilteredRowsCount($this->filteredTransactionsCount);

        //current year filter for all transactions
        if (empty($filtercustomerId) and ! $renderAll) {
            $renderAll = false;
        } else {
            $renderAll = true;
        }

        if (!empty($this->wholeTransactions)) {
            foreach ($this->wholeTransactions as $io => $each) {
                $detailsControl = wf_Link(self::URL_ME . '&showtransaction=' . $each['id'], $each['id']);
                @$userLogin = $this->allCustomers[$each['customerid']];
                @$userRealname = $this->allRealnames[$userLogin];
                @$userAddress = $this->allAddress[$userLogin];
                $stateIcon = web_bool_led($each['processed']);
                $userLink = (!empty($userLogin)) ? wf_Link('?module=userprofile&username=' . $userLogin, web_profile_icon() . ' ' . $userAddress) : '';

                //append data to results
                $data[] = $detailsControl;
                $data[] = $each['date'];
                $data[] = $each['summ'];
                $data[] = $each['customerid'];
                $data[] = $userRealname;
                $data[] = $userLink;
                $data[] = $each['paysys'];
                $data[] = $stateIcon;

                $json->addRow($data);
                unset($data);
            }
        }

        $json->getJson();
    }


    /**
     * Performs transactions filtering, ordering and load for ajax list
     * 
     * @param string $filtercustomerId
     * @param bool $renderAll
     *
     * @return void
     */
    public function transactionsLoader($filtercustomerId = '', $renderAll = false) {
        $filtercustomerId = ubRouting::filters($filtercustomerId, 'mres');

        $this->onPage = (ubRouting::checkGet('iDisplayLength')) ? ubRouting::get('iDisplayLength') : $this->onPage;

        //login filtering
        if ($filtercustomerId) {
            $this->transactionsDb->where('customerid', '=', $filtercustomerId);
        } else {
            //date current year filtering 
            if (!$renderAll) {
                $this->transactionsDb->where('date', 'LIKE', curyear() . '-%');
            }
        }


        $sortField = 'date';
        $sortDir = 'desc';
        if (ubRouting::checkGet('iSortCol_0', false)) {
            $sortingColumn = ubRouting::get('iSortCol_0', 'int');
            $sortDir = ubRouting::get('sSortDir_0', 'gigasafe');
            switch ($sortingColumn) {
                case 0:
                    $sortField = 'id';
                    break;
                case 1:
                    $sortField = 'date';
                    break;
                case 2:
                    $sortField = 'summ';
                    break;
                case 3:
                    $sortField = 'customerid';
                    break;
                case 4:
                    $sortField = 'customerid';
                    break;
                case 5:
                    $sortField = 'customerid';
                    break;
                case 6:
                    $sortField = 'paysys';
                    break;
                case 7:
                    $sortField = 'processed';
                    break;
            }
        }
        $this->transactionsDb->orderBy($sortField, $sortDir);
        $this->totalTransactionsCount = $this->transactionsDb->getFieldsCount('id', false);



        $offset = 0;
        if (ubRouting::checkGet('iDisplayStart')) {
            $offset = ubRouting::get('iDisplayStart', 'int');
        }

        //optional live search
        $searchQuery = '';
        if (ubRouting::checkGet('sSearch')) {
            $searchQuery = ubRouting::get('sSearch', 'mres');
            if (!$filtercustomerId) {
                $dateQuery = ubRouting::filters($searchQuery, 'gigasafe', '-: ');
                $this->transactionsDb->where('customerid', 'LIKE', '%' . $searchQuery . '%');
                $this->transactionsDb->orWhere('date', 'LIKE', '%' . $dateQuery . '%');
                $this->transactionsDb->orWhere('summ', 'LIKE', '%' . $searchQuery . '%');
                $this->transactionsDb->orWhere('paysys', 'LIKE', '%' . $searchQuery . '%');
            }
        }


        //optional live search happens
        if ($searchQuery) {
            $this->filteredTransactionsCount = $this->transactionsDb->getFieldsCount('id', false) - 1;
        } else {
            $this->filteredTransactionsCount = $this->totalTransactionsCount;
        }
        $this->transactionsDb->limit($this->onPage, $offset);
        $this->wholeTransactions = $this->transactionsDb->getAll();
    }

    /**
     * Returns plain array of not processed transactions
     * 
     * @return array
     */
    protected function getUnprocessedTransactions() {
        $this->transactionsDb->where('processed', '=', '0');
        $result = $this->transactionsDb->getAll();
        return ($result);
    }

    /**
     * Sets transaction processed by its ID
     *
     * @param int $transactionId
     * 
     * @return void
     */
    protected function setTransactionProcessed($transactionId) {
        $transactionId = ubRouting::filters($transactionId, 'int');
        if ($transactionId) {
            $this->transactionsDb->data('processed', '1');
            $this->transactionsDb->where('id', '=', $transactionId);
            $this->transactionsDb->save();
        }
    }

    /**
     * Performs transaction processing
     *
     * @param array $transactionData
     * 
     * @return void
     */
    protected function processTransaction($transactionData) {
        if (!empty($transactionData)) {
            $transactionId = $transactionData['id'];
            $customerId = $transactionData['customerid'];
            $paymentSumm = $transactionData['summ'];
            $paySys = $transactionData['paysys'];
            $paymentNote = (!empty($paySys)) ? 'OP:' . $paySys : 'UNKNOWN';
            $cashTypeId = $this->defaultCashTypeId;
            //some custom cashtype for this payment system defined?
            if (isset($this->altCfg[self::CUSTOM_CASHTYPE_PREFIX . $paySys])) {
                $cashTypeId = ubRouting::filters($this->altCfg[self::CUSTOM_CASHTYPE_PREFIX . $paySys], 'int');
            }

            if (isset($this->allCustomers[$customerId])) {
                //existing user?
                $userLogin = $this->allCustomers[$customerId];
                //push some cash to his balance
                zb_CashAdd($userLogin, $paymentSumm, 'op', $cashTypeId, $paymentNote, 'openpayz');
                //setting this transaction as processed
                $this->setTransactionProcessed($transactionId);
            }
        }
    }

    /**
     * Performs processing of all unprocessed transctions in database
     *
     * @return int|bool
     */
    public function transactionsProcessingAll() {
        $result = 0;
        if ($this->hiLoadFlag) {
            if ($this->transactionsProcess->notRunning()) {
                $this->transactionsProcess->start();
                $allTransactions = $this->getUnprocessedTransactions();
                if (!empty($allTransactions)) {
                    foreach ($allTransactions as $io => $eachTransaction) {
                        $this->processTransaction($eachTransaction);
                        $result++;
                    }
                }
                $this->transactionsProcess->stop();
            } else {
                $result = false;
                log_register('OPENPAYZ PROCESSING SKIPPED ALREADY RUNNING');
            }
        }
        return ($result);
    }
}
