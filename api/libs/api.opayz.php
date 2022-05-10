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
     * @var null
     */
    protected $ubConfig = null;

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
     * Transactions list ajax callback URL
     */
    const URL_AJAX_SOURCE = '?module=openpayz&ajax=true';

    /**
     * Payment systems charts URL
     */
    const URL_CHARTS = '?module=openpayz&graphs=true';

    /**
     * Creates new OpenPayz instance
     * 
     * @return void
     */
    public function __construct() {
        global $ubillingConfig;
        $this->ubConfig = $ubillingConfig;

        $this->loadAlter();
        $this->loadOptions();
        $this->initMessages();
        $this->loadPaySys();

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
        $this->smsNotysPullInterval     = ubRouting::filters($this->ubConfig->getAlterParam('OP_SMS_NOTIFY_PAYMENTS_PULL_INTERVAL', 5), 'int');
        $this->smsUseExtMobiles         = ubRouting::filters($this->ubConfig->getAlterParam('OP_SMS_NOTIFY_USE_EXTMOBILES'), 'fi', FILTER_VALIDATE_BOOLEAN);
        $this->smsForceTranslit         = ubRouting::filters($this->ubConfig->getAlterParam('OP_SMS_NOTIFY_FORCED_TRANSLIT'), 'fi', FILTER_VALIDATE_BOOLEAN);
        $this->smsDebugON               = ubRouting::filters($this->ubConfig->getAlterParam('OP_SMS_NOTIFY_DEBUG_ON'), 'fi', FILTER_VALIDATE_BOOLEAN);
        $this->smsNotysText             = $this->ubConfig->getAlterParam('OP_SMS_NOTIFY_TEXT', '');
        $this->smsRespectReminderTagID  = ubRouting::filters($this->ubConfig->getAlterParam('OP_SMS_NOTIFY_RESPECT_REMINDER_TAGID'), 'fi', FILTER_VALIDATE_BOOLEAN);
        $this->smsReminderTagID         = ubRouting::filters($this->ubConfig->getAlterParam('REMINDER_TAGID', 0), 'int');
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
        $query = "SELECT * from `op_customers`";
        $allcustomers = simple_queryall($query);

        if (!empty($allcustomers)) {
            foreach ($allcustomers as $io => $eachcustomer) {
                $this->allCustomers[$eachcustomer['virtualid']] = $eachcustomer['realid'];
            }
        }
    }

    /**
     * Loads available openpayz transactions into private data property
     * 
     * @param int $year
     * 
     * @return void
     */
    protected function loadTransactions($year = '') {
        $year = vf($year, 3);
        $where = '';
        if (!empty($year) AND $year != '1488') {
            $where = "WHERE `date` LIKE '" . $year . "-%'";
        }
        $query = "SELECT * from `op_transactions` " . $where . " ORDER by `id` ASC;";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allTransactions[$each['id']] = $each;
            }
        }
    }

    /**
     * Public getter of preloaded users mappings
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
     * Loads array of available payment systems
     * 
     * @return void
     */
    protected function loadPaySys() {
        $result = array();
        $query = "SELECT DISTINCT `paysys` from `op_transactions`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allPaySys[$each['paysys']] = $each['paysys'];
            }
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
     * Returns openpayz search form
     * 
     * @return string
     */
    public function renderSearchForm() {
        $curYear = (wf_CheckPost(array('searchyear'))) ? vf($_POST['searchyear'], 3) : date("Y");
        $curMonth = (wf_CheckPost(array('searchmonth'))) ? vf($_POST['searchmonth'], 3) : date("m");
        $curPaysys = (wf_CheckPost(array('searchpaysys'))) ? vf($_POST['searchpaysys']) : '';
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
        $result = wf_Form("", 'POST', $inputs, 'glamour');
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
                    if (( $eachtransaction['paysys'] == $paysys) OR ( ( $paysys == 'ANY'))) {
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
            //fuck this legacy
            //$csvdata = iconv('utf-8', 'windows-1251', $csvdata);
            file_put_contents($exportFilename, $csvdata);
            $exportLink = wf_Link('?module=openpayz&dload=' . base64_encode($exportFilename), wf_img('skins/excel.gif', __('Export')), false, '');
        } else {
            $exportLink = '';
        }

        show_window(__('Search results') . ' ' . $paysys . ': ' . $year . '-' . $month . ' ' . $exportLink, $result);
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
        return($result);
    }

    /**
     * Renders per-payment system openpayz transaction charts
     * 
     * @return string
     */
    public function renderGraphs() {
        $showYear = ubRouting::checkPost('chartsyear') ? ubRouting::post('chartsyear', 'int') : curyear();

        $cache = new UbillingCache();
        $cacheTimeout = 86400;
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

        $result = wf_BackLink('?module=openpayz', '', true);
        //cahche data extraction
        $chacheKeyName = 'OPCHARTS_' . $showYear;
        $cahcheDataRaw = $cache->get($chacheKeyName, $cacheTimeout);
        //something in cache
        if (!empty($cahcheDataRaw)) {
            $psysdata = $cahcheDataRaw['psysdata'];
            $gcYearData = $cahcheDataRaw['gcYearData'];
            $gcMonthData = $cahcheDataRaw['gcMonthData'];
            $gcDayData = $cahcheDataRaw['gcDayData'];
        } else {
            $cahcheDataRaw = array();
            //real data loading
            $this->loadTransactions($showYear);
            if (!empty($this->allTransactions)) {
                foreach ($this->allTransactions as $io => $each) {
                    $timestamp = strtotime($each['date']);

                    $date = date("Y-m", $timestamp);
                    $dateFull = date("Y-m-d", $timestamp);
                    if (isset($psysdata[$each['paysys']][$date]['count'])) {
                        $psysdata[$each['paysys']][$date]['count'] ++;
                        $psysdata[$each['paysys']][$date]['summ'] = $psysdata[$each['paysys']][$date]['summ'] + $each['summ'];
                    } else {
                        $psysdata[$each['paysys']][$date]['count'] = 1;
                        $psysdata[$each['paysys']][$date]['summ'] = $each['summ'];
                    }

                    //current year stats
                    if (ispos($date, $curYear)) {
                        if (isset($gcYearData[$each['paysys']])) {
                            $gcYearData[$each['paysys']] ++;
                        } else {
                            $gcYearData[$each['paysys']] = 1;
                        }
                    }

                    //current month stats
                    if (ispos($date, $curMonth)) {
                        if (isset($gcMonthData[$each['paysys']])) {
                            $gcMonthData[$each['paysys']] ++;
                        } else {
                            $gcMonthData[$each['paysys']] = 1;
                        }
                    }

                    //current day stats
                    if (ispos($dateFull, $curDay)) {
                        if (isset($gcDayData[$each['paysys']])) {
                            $gcDayData[$each['paysys']] ++;
                        } else {
                            $gcDayData[$each['paysys']] = 1;
                        }
                    }
                }
            }

            //store in cache
            $cahcheDataRaw['psysdata'] = $psysdata;
            $cahcheDataRaw['gcYearData'] = $gcYearData;
            $cahcheDataRaw['gcMonthData'] = $gcMonthData;
            $cahcheDataRaw['gcDayData'] = $gcDayData;
            $cache->set($chacheKeyName, $cahcheDataRaw, $cacheTimeout);
        }

        $chartOpts = "chartArea: {  width: '90%', height: '90%' }, legend : {position: 'right'}, ";
        $fixedColors = @$this->altCfg['OPENPAYZ_PALETTE'];
        

        if (!empty($gcDayData)) {
            $gcDayPie = wf_gcharts3DPie($gcDayData, __('Today'), '300px', '300px', $chartOpts, $fixedColors);
        } else {
            $gcDayPie = '';
        }

        if (!empty($gcMonthData)) {
            $gcMonthPie = wf_gcharts3DPie($gcMonthData, __('Current month'), '300px', '300px', $chartOpts, $fixedColors);
        } else {
            $gcMonthPie = '';
        }

        if (!empty($gcYearData)) {
            $gcYearPie = wf_gcharts3DPie($gcYearData, __('Current year'), '300px', '300px', $chartOpts, $fixedColors);
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
     * Sets openpayz transaction as processed in database
     * 
     * @param int $transactionid
     * 
     * @return void
     */
    public function transactionSetProcessed($transactionid) {
        $transactionid = vf($transactionid, 3);
        $query = "UPDATE `op_transactions` SET `processed` = '1' WHERE `id`='" . $transactionid . "'";
        nr_query($query);
        log_register('OPENPAYZ PROCESSED [' . $transactionid . ']');
    }

    /**
     * Pushes user payment with some payment system
     * 
     * @param string $login
     * @param float  $cash
     * @param string $paysys
     * 
     * @return void
     */
    public function cashAdd($login, $cash, $paysys) {
        $note = 'OP:' . $paysys;
        zb_CashAdd($login, $cash, 'add', $this->altCfg['OPENPAYZ_CASHTYPEID'], $note);
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
     * Retruns json data for jquery data tables with transactions list
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    public function transactionAjaxSource() {
        $this->loadCustomers();
        $this->loadAddress();
        $this->loadRealname();
        $curYear = curyear();
        $manual_mode = $this->altCfg['OPENPAYZ_MANUAL'];
        $query = "SELECT * from `op_transactions` WHERE `date` LIKE '" . $curYear . "-%' ORDER by `id` DESC;";
        $alltransactions = simple_queryall($query);
        $json = new wf_JqDtHelper();


        if (!empty($alltransactions)) {
            foreach ($alltransactions as $io => $eachtransaction) {
                $control = '';

                if ($manual_mode) {
                    if ($eachtransaction['processed'] == 0) {
                        $control .= ' ' . wf_Link('?module=openpayz&process=' . $eachtransaction['id'], web_add_icon('Payment'));
                    }
                }

                @$user_login = $this->allCustomers[$eachtransaction['customerid']];
                @$user_realname = $this->allRealnames[$user_login];
                @$user_address = $this->allAddress[$user_login];

                if (!empty($user_login)) {
                    $profileLink = wf_Link('?module=userprofile&username=' . $user_login, web_profile_icon() . ' ' . @$user_address);
                } else {
                    $profileLink = '';
                }

                $stateIcon = web_bool_led($eachtransaction['processed']);
                $detailsControl = ' ' . wf_Link('?module=openpayz&showtransaction=' . $eachtransaction['id'], $eachtransaction['id']);
                $data[] = $detailsControl;
                $data[] = $eachtransaction['date'];
                $data[] = $eachtransaction['summ'];
                $data[] = $eachtransaction['customerid'];
                $data[] = $user_realname;
                $data[] = $profileLink;
                $data[] = $eachtransaction['paysys'];
                $data[] = $stateIcon . $control;

                $json->addRow($data);
                unset($data);
            }
        }


        $json->getJson();
    }

    /**
     * Renders transaction list container
     * 
     * @return void
     */
    public function renderTransactionList() {
        $opts = '"order": [[ 0, "desc" ]]';
        $columns = array('ID', 'Date', 'Cash', 'Payment ID', 'Real Name', 'Full address', 'Payment system', 'Processed');
        $graphsUrl = wf_Link(self::URL_CHARTS, wf_img('skins/icon_stats.gif', __('Graphs')), false, '');
        show_window(__('OpenPayz transactions') . ' ' . $graphsUrl, wf_JqDtLoader($columns, self::URL_AJAX_SOURCE, false, 'payments', 100, $opts));
    }

    /**
     * Renders transaction details
     * 
     * @param int $transactionId
     * 
     * @return void
     */
    public function renderTransactionDetails($transactionId) {
        $this->loadTransactions();
        $transactionId = vf($transactionId, 3);
        $result = '';
        $result .= wf_BackLink('?module=openpayz', '', true);
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
        $paymentsFound      = array();
        $paymentsFoundCnt   = 0;
        $tabPayments        = new NyanORM('payments');
        $tabNotifications   = new NyanORM('op_sms_notifications');

        if ($this->smsDebugON) {
            $tabPayments->setDebug(true, true);
            $tabNotifications->setDebug(true, true);
        }

        // trying to get timestamp of the very last pulled payment
        // if nothing was pulled yet - current datetime used
        $tabNotifications->selectable('MAX(date) AS max_date');
        $pullDateTimeLast = $tabNotifications->getAll();

        if (!empty($pullDateTimeLast) and isset($pullDateTimeLast[0]['max_date'])) {
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
                                log_register('OPAYZ SMS NOTIFY: skipping payment with ID: '. $eachID . ' for login: (' . $tmpLogin . ') as it doesn\'t have REMINDER_TAGID [' . $this->smsReminderTagID . '] assigned');
                            }

                            continue;
                        }
                    }

                    $tmpRec = array('payment_id' => $eachID,
                                    'date'       => $eachRec['date'],
                                    'login'      => $tmpLogin,
                                    'balance'    => $eachRec['balance'] + $eachRec['summ'],
                                    'summ'       => $eachRec['summ']
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
            $ubSMS      = new UbillingSMS();
            $allPhones  = zb_GetAllAllPhonesCache();

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
}

?>