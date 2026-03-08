<?php

/**
 * User signup and payments extended stats
 */
class Metabolism {

    /**
     * System alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Payments database abstraction layer placeholder
     *
     * @var object
     */
    protected $payments = '';

    /**
     * Signups database abstraction layer placeholder
     *
     * @var object
     */
    protected $signups = '';

    /**
     * Signup requests database abstraction layer placeholder
     *
     * @var object
     */
    protected $sigreqDb = '';

    /**
     * Capabilities directory database abstraction layer placeholder
     *
     * @var object
     */
    protected $capabsDb = '';

    /**
     * System message helper placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Contains default year to display
     *
     * @var int
     */
    protected $year = '';

    /**
     * Contains default month to display with leading zero
     *
     * @var string
     */
    protected $month = '';

    /**
     * Contains all users last payments as login=>date/summ
     *
     * @var array
     */
    protected $lastPayments = array();

    /**
     * Contains all users signups as login=>date
     *
     * @var array
     */
    protected $allSignups = array();

    /**
     * Contains all users data as login=>userData
     *
     * @var array
     */
    protected $allUserData = array();

    /**
     * Routes etc.
     */
    const URL_ME = '?module=metabolism';
    const ROUTE_RENDER = 'render';
    const R_PAYMENTS = 'payments';
    const R_SIGNUPS = 'signups';
    const R_LIFECYCLE = 'lifecycle';
    const ROUTE_LIFECYCLE_USERS = 'lifecycle_users';
    const ROUTE_LIFECYCLE_YEAR = 'lifecycle_year';
    const ROUTE_LIFECYCLE_MONTH = 'lifecycle_month';
    const ROUTE_LIFECYCLE_TYPE = 'lifecycle_type';
    const PROUTE_SPLITCHARTS = 'splitcharts';

    /**
     * Creates new metabolism instance
     */
    public function __construct() {
        $this->initMessages();
        $this->loadAlter();
        $this->setDate();
        $this->initPayments();
        $this->initSignups();
        $this->initSigreq();
        $this->initCapabs();
    }

    /**
     * Loads system alter config into protected prop for further usage
     * 
     * @return void
     */
    protected function loadAlter() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Catches year/month selector data and sets internal props for further usage
     * 
     * @return void
     */
    protected function setDate() {
        if (ubRouting::checkPost(array('showyear', 'showmonth'))) {
            $this->year = ubRouting::post('showyear', 'int');
            $this->month = ubRouting::post('showmonth', 'int');
        } else {
            $this->year = curyear();
            $this->month = date("m");
        }
    }

    /**
     * Inits payments database layer instance
     * 
     * @return void
     */
    protected function initPayments() {
        $this->payments = new NyanORM('payments');
    }

    /**
     * Inits signups database layer instance
     * 
     * @return void
     */
    protected function initSignups() {
        $this->signups = new NyanORM('userreg');
    }

    /**
     * Inits signup requests database layer instance
     * 
     * @return void
     */
    protected function initSigreq() {
        $this->sigreqDb = new NyanORM('sigreq');
    }

    /**
     * Inits capabilities directory database layer instance
     * 
     * @return void
     */
    protected function initCapabs() {
        $this->capabsDb = new NyanORM('capab');
    }

    /**
     * Inits system message helper instance
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Loads all users data from database into protected prop for further usage
     *
     * @return void
     */
    protected function loadUserData() {
        $this->allUserData = zb_UserGetAllDataCache();
    }

    /**
     * Loads all users signups from database into protected prop for further usage
     *
     * @return void
     */
    protected function loadSignups() {
        $this->allSignups = $this->signups->getAll('login');
    }

    /**
     * Loads all users last payments from database into protected prop for further usage
     *
     * @return void
     */
    protected function loadLastPayments() {
        $this->lastPayments = zb_UserGetLatestPaymentsPositiveAll();
    }

    /**
     * Renders default module controls panel
     * 
     * @return string
     */
    public function renderPanel() {
        $result = '';
        
        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_RENDER . '=' . self::R_PAYMENTS, wf_img_sized('skins/icon_dollar.gif', '', '16', '16') . ' ' . __('Payments'), false, 'ubButton') . ' ';
        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_RENDER . '=' . self::R_SIGNUPS, web_icon_charts() . ' ' . __('Signups'), false, 'ubButton') . ' ';
        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_RENDER . '=' . self::R_LIFECYCLE, wf_img('skins/icon_lifecycle.png') . ' ' . __('Lifecycle'), false, 'ubButton') . ' ';
        $result .= wf_CleanDiv();
        
        //dateform required for payments and signups
        if (ubRouting::checkGet(self::ROUTE_RENDER)) {
            switch (ubRouting::get(self::ROUTE_RENDER)) {
                case self::R_PAYMENTS:
                case self::R_SIGNUPS:
                    $result .= wf_tag('br');
                    $result .= $this->renderDateForm();
                    break;
            }
        }
        
        return($result);
    }

    /**
     * Returns year-month selection form
     * 
     * @return string
     */
    protected function renderDateForm() {
        $result = '';
        $inputs = wf_YearSelectorPreset('showyear', __('Year'), false, $this->year) . ' ';
        $inputs .= wf_MonthSelector('showmonth', __('Month'), $this->month, false, true) . ' ';
        if (ubRouting::get(self::ROUTE_RENDER) == self::R_SIGNUPS and (!empty($this->altCfg['SIGREQ_ENABLED']) or !empty($this->altCfg['CAPABDIR_ENABLED']))) {
            $inputs .= wf_CheckInput(self::PROUTE_SPLITCHARTS, __('Split'), false, ubRouting::checkPost(self::PROUTE_SPLITCHARTS)) . ' ';
        }
        $inputs .= wf_Submit(__('Show'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return($result);
    }

    /**
     * Returns default chart options
     * 
     * @return string
     */
    protected function getChartOptions() {
        $result = "'focusTarget': 'category',
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
                            },
                            ";

        return($result);
    }

    /**
     * Renders payments metabolism report
     * 
     * @return string
     */
    public function renderPayments() {
        $result = '';
        $tmpArr = array();
        if (!empty($this->year) AND ! empty($this->month)) {
            if ($this->month == '1488') {
                //all time
                $dateFilter = $this->year . '-%';
            } else {
                //normal case
                $dateFilter = $this->year . '-' . $this->month . '-%';
            }
            //setting db props
            $this->payments->orderBy('date', 'asc');
            $this->payments->where('summ', '>', '0');
            $this->payments->where('date', 'LIKE', $dateFilter);
            $allPayments = $this->payments->getAll();
            if (!empty($allPayments)) {
                $chartsData[] = array(__('Date'), __('Count'), __('Money'));
                // prefill 24 hours for by-hour chart (0..23)
                $tmpArrByHour = array();
                for ($h = 0; $h < 24; $h++) {
                    $tmpArrByHour[$h] = array('count' => 0, 'summ' => 0);
                }
                // prefill 7 days for by-day-of-week chart (1=Monday..7=Sunday)
                $tmpArrByWeekday = array();
                for ($d = 1; $d <= 7; $d++) {
                    $tmpArrByWeekday[$d] = array('count' => 0);
                }
                foreach ($allPayments as $io => $each) {
                    $timeStamp = strtotime($each['date']); //need to be transformed to Y-m-d
                    $date = date("Y-m-d", $timeStamp);
                    if (isset($tmpArr[$date])) {
                        $tmpArr[$date]['count'] ++;
                        $tmpArr[$date]['summ'] += $each['summ'];
                    } else {
                        $tmpArr[$date]['count'] = 1;
                        $tmpArr[$date]['summ'] = $each['summ'];
                    }
                    $hour = (int) date('G', $timeStamp);
                    if (isset($tmpArrByHour[$hour])) {
                        $tmpArrByHour[$hour]['count']++;
                        $tmpArrByHour[$hour]['summ'] += $each['summ'];
                    }
                    $dayOfWeek = (int) date('N', $timeStamp);
                    if (isset($tmpArrByWeekday[$dayOfWeek])) {
                        $tmpArrByWeekday[$dayOfWeek]['count']++;
                    }
                }

                if (!empty($tmpArr)) {
                    foreach ($tmpArr as $date => $each) {
                        $chartsData[] = array($date, $each['count'], $each['summ']);
                    }
                    $result .= wf_gchartsLine($chartsData, __('Cash'), '100%', '400px;', $this->getChartOptions());
                }

                // by-hour chart
                $chartsDataByHour = array();
                $chartsDataByHour[] = array(__('Hour'), __('Count'));
                for ($h = 0; $h < 24; $h++) {
                    $hourLabel = $h . ':00';
                    $chartsDataByHour[] = array($hourLabel, $tmpArrByHour[$h]['count']);
                }
                $result .= wf_tag('br');
                $result .= wf_gchartsLine($chartsDataByHour, __('Payments').' '. __('by time of day'), '100%', '400px;', $this->getChartOptions());

                // by day of week chart
                $dayNames = daysOfWeek();
                $chartsDataByWeekday = array();
                $chartsDataByWeekday[] = array(__('Day'), __('Count'));
                for ($d = 1; $d <= 7; $d++) {
                    $dayLabel = isset($dayNames[$d]) ? $dayNames[$d] : $d;
                    $chartsDataByWeekday[] = array($dayLabel, $tmpArrByWeekday[$d]['count']);
                }
                $result .= wf_tag('br');
                $result .= wf_gchartsLine($chartsDataByWeekday, __('Payments').' '. __('by day of week'), '100%', '400px;', $this->getChartOptions());
            } else {
                $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Something went wrong'), 'error');
        }
        return($result);
    }

    /**
     * Renders payments metabolism report
     * 
     * @return string
     */
    public function renderSignups() {
        $result = '';
        $tmpArr = array();
        $tmpArrSigreq = array();
        $tmpArrCapab = array();
        if (!empty($this->year) AND ! empty($this->month)) {
            if ($this->month == '1488') {
                $dateFilter = $this->year . '-%';
            } else {
                $dateFilter = $this->year . '-' . $this->month . '-%';
            }
            $this->signups->orderBy('date', 'asc');
            $this->signups->where('date', 'LIKE', $dateFilter);
            $allSignups = $this->signups->getAll();

            // prefill days of month for signups chart
            if ($this->month != '1488') {
                $showMonth = strtotime($this->year . '-' . $this->month);
                $maxDay = date("t", $showMonth);
                for ($zeroDay = 1; $zeroDay <= $maxDay; $zeroDay++) {
                    $date = $this->year . '-' . $this->month . '-' . ($zeroDay < 10 ? '0' . $zeroDay : $zeroDay);
                    $tmpArr[$date]['count'] = 0;
                }
            }
            foreach ($allSignups as $io => $each) {
                $timeStamp = strtotime($each['date']);
                $date = date("Y-m-d", $timeStamp);
                if (isset($tmpArr[$date])) {
                    $tmpArr[$date]['count']++;
                } else {
                    $tmpArr[$date]['count'] = 1;
                }
            }

            $sigreqEnabled = !empty($this->altCfg['SIGREQ_ENABLED']);
            $capabEnabled = !empty($this->altCfg['CAPABDIR_ENABLED']);

            if ($sigreqEnabled) {
                if ($this->month != '1488') {
                    foreach (array_keys($tmpArr) as $date) {
                        $tmpArrSigreq[$date] = 0;
                    }
                }
                $this->sigreqDb->where('date', 'LIKE', $dateFilter);
                $allSigreq = $this->sigreqDb->getAll();
                foreach ($allSigreq as $io => $each) {
                    $date = date("Y-m-d", strtotime($each['date']));
                    if (isset($tmpArrSigreq[$date])) {
                        $tmpArrSigreq[$date]++;
                    } else {
                        $tmpArrSigreq[$date] = 1;
                    }
                }
            }

            if ($capabEnabled) {
                if ($this->month != '1488') {
                    foreach (array_keys($tmpArr) as $date) {
                        $tmpArrCapab[$date] = 0;
                    }
                }
                $this->capabsDb->where('date', 'LIKE', $dateFilter);
                $allCapab = $this->capabsDb->getAll();
                foreach ($allCapab as $io => $each) {
                    $date = date("Y-m-d", strtotime($each['date']));
                    if (isset($tmpArrCapab[$date])) {
                        $tmpArrCapab[$date]++;
                    } else {
                        $tmpArrCapab[$date] = 1;
                    }
                }
            }

            if ($this->month == '1488') {
                $chartDates = array_unique(array_merge(
                    array_keys($tmpArr),
                    array_keys($tmpArrSigreq),
                    array_keys($tmpArrCapab)
                ));
                sort($chartDates);
                foreach ($chartDates as $date) {
                    if (!isset($tmpArr[$date])) {
                        $tmpArr[$date] = array('count' => 0);
                    }
                    if ($sigreqEnabled and !isset($tmpArrSigreq[$date])) {
                        $tmpArrSigreq[$date] = 0;
                    }
                    if ($capabEnabled and !isset($tmpArrCapab[$date])) {
                        $tmpArrCapab[$date] = 0;
                    }
                }
            } else {
                $chartDates = array_keys($tmpArr);
                ksort($chartDates);
            }

            if (!empty($chartDates)) {
                $extendedCharts = $sigreqEnabled or $capabEnabled;
                // One combined chart by default; three separate charts only when form was submitted and checkbox is checked
                $splitCharts = ubRouting::checkPost(self::PROUTE_SPLITCHARTS);

                if ($splitCharts) {
                    
                    $chartsDataSignups = array(array(__('Date'), __('Signups')));
                    foreach ($chartDates as $date) {
                        $chartsDataSignups[] = array($date, isset($tmpArr[$date]) ? $tmpArr[$date]['count'] : 0);
                    }
                    $result .= wf_gchartsLine($chartsDataSignups, __('Signups'), '100%', '400px;', $this->getChartOptions());
                    if ($sigreqEnabled) {
                        $chartsDataSigreq = array(array(__('Date'), __('Signup requests')));
                        foreach ($chartDates as $date) {
                            $chartsDataSigreq[] = array($date, isset($tmpArrSigreq[$date]) ? $tmpArrSigreq[$date] : 0);
                        }
                        $result .= wf_tag('br');
                        $result .= wf_gchartsLine($chartsDataSigreq, __('Signup requests'), '100%', '400px;', $this->getChartOptions());
                    }
                    if ($capabEnabled) {
                        $chartsDataCapab = array(array(__('Date'), __('Signup capabilities')));
                        foreach ($chartDates as $date) {
                            $chartsDataCapab[] = array($date, isset($tmpArrCapab[$date]) ? $tmpArrCapab[$date] : 0);
                        }
                        $result .= wf_tag('br');
                        $result .= wf_gchartsLine($chartsDataCapab, __('Signup capabilities'), '100%', '400px;', $this->getChartOptions());
                    }
                } else {
                    $chartsData = array();
                    $header = array(__('Date'), __('Signups'));
                    if ($sigreqEnabled) {
                        $header[] = __('Signup requests');
                    }
                    if ($capabEnabled) {
                        $header[] = __('Signup capabilities');
                    }
                    $chartsData[] = $header;
                    foreach ($chartDates as $date) {
                        $row = array($date, isset($tmpArr[$date]) ? $tmpArr[$date]['count'] : 0);
                        if ($sigreqEnabled) {
                            $row[] = isset($tmpArrSigreq[$date]) ? $tmpArrSigreq[$date] : 0;
                        }
                        if ($capabEnabled) {
                            $row[] = isset($tmpArrCapab[$date]) ? $tmpArrCapab[$date] : 0;
                        }
                        $chartsData[] = $row;
                    }
                    $result .= wf_gchartsLine($chartsData, __('Signups'), '100%', '400px;', $this->getChartOptions());
                }
            } else {
                $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Something went wrong'), 'error');
        }
        return($result);
    }

    /**
     * Renders users lifecycle report.
     * For each year-month: connected (signed up), lost (churned in that period), still active (signed up then, active now).
     *
     * @return string
     */
    public function renderLifecycle() {
        $result = '';
        $this->loadUserData();
        $this->loadSignups();
        $this->loadLastPayments();

        if (!empty($this->allUserData) or !empty($this->allSignups)) {

        // [year][month] => connected, lost, active
        $stats = array();
        $totalLifetimeSumLost = 0;
        $totalLostCount = 0;

        foreach ($this->allUserData as $login => $userData) {
            if (!isset($this->allSignups[$login])) {
                continue;
            }
            $signupDate = $this->allSignups[$login]['date'];
            if (empty($signupDate)) {
                continue;
            }
            $signupTs = strtotime($signupDate);
            $signupY = date('Y', $signupTs);
            $signupM = date('m', $signupTs);

            $lastPaymentDate = isset($this->lastPayments[$login]['date']) ? $this->lastPayments[$login]['date'] : null;
            $isActive = zb_UserIsActive($userData);

            $endTs = $isActive ? time() : ($lastPaymentDate !== null ? strtotime($lastPaymentDate) : $signupTs);
            $lifetimeSeconds = max(0, $endTs - $signupTs);

            if (!isset($stats[$signupY][$signupM])) {
                $stats[$signupY][$signupM] = array(
                    'connected'        => 0,
                    'dead_souls'       => 0,
                    'lost'             => 0,
                    'active'           => 0,
                    'lifetime_sum'     => 0,
                    'connected_logins'  => array(),
                    'dead_souls_logins' => array(),
                    'lost_logins'      => array(),
                    'active_logins'    => array()
                );
            }
            $stats[$signupY][$signupM]['connected'] += 1;
            $stats[$signupY][$signupM]['connected_logins'][] = $login;
            // Dead souls: no payments at all and not active (excludes active free-tariff/service accounts)
            if ($lastPaymentDate === null and !$isActive) {
                $stats[$signupY][$signupM]['dead_souls'] += 1;
                $stats[$signupY][$signupM]['dead_souls_logins'][] = $login;
            }
            $stats[$signupY][$signupM]['lifetime_sum'] += $lifetimeSeconds;
            if ($isActive) {
                $stats[$signupY][$signupM]['active'] += 1;
                $stats[$signupY][$signupM]['active_logins'][] = $login;
            } else {
                if ($lastPaymentDate !== null) {
                    $totalLifetimeSumLost += $lifetimeSeconds;
                    $totalLostCount++;
                    $churnY = date('Y', strtotime($lastPaymentDate));
                    $churnM = date('m', strtotime($lastPaymentDate));
                    if (!isset($stats[$churnY][$churnM])) {
                        $stats[$churnY][$churnM] = array(
                            'connected'        => 0,
                            'dead_souls'       => 0,
                            'lost'             => 0,
                            'active'           => 0,
                            'lifetime_sum'     => 0,
                            'connected_logins' => array(),
                            'dead_souls_logins' => array(),
                            'lost_logins'      => array(),
                            'active_logins'    => array()
                        );
                    }
                    $stats[$churnY][$churnM]['lost'] += 1;
                    $stats[$churnY][$churnM]['lost_logins'][] = $login;
                } else {
                    $stats[$signupY][$signupM]['lost'] += 1;
                    $stats[$signupY][$signupM]['lost_logins'][] = $login;
                }
            }
        }

        if (empty($stats)) {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
            return ($result);
        }

        krsort($stats, SORT_NUMERIC);
        $months = months_array();

        $lifecycleUsersYear = ubRouting::get(self::ROUTE_LIFECYCLE_YEAR, 'int');
        $lifecycleUsersMonth = ubRouting::get(self::ROUTE_LIFECYCLE_MONTH, 'int');
        $lifecycleUsersType = ubRouting::get(self::ROUTE_LIFECYCLE_TYPE, 'mres');
        if (ubRouting::checkGet(self::ROUTE_LIFECYCLE_USERS) and $lifecycleUsersYear and in_array($lifecycleUsersType, array('connected', 'dead_souls', 'lost', 'active'))) {
            $loginsForShower = array();
            $typeKey = $lifecycleUsersType . '_logins';
            if ($lifecycleUsersMonth and isset($stats[$lifecycleUsersYear][$lifecycleUsersMonth][$typeKey])) {
                $loginsForShower = $stats[$lifecycleUsersYear][$lifecycleUsersMonth][$typeKey];
            } elseif (!$lifecycleUsersMonth and isset($stats[$lifecycleUsersYear])) {
                foreach ($stats[$lifecycleUsersYear] as $month => $row) {
                    if (!empty($row[$typeKey])) {
                        foreach ($row[$typeKey] as $login) {
                            $loginsForShower[] = $login;
                        }
                    }
                }
                $loginsForShower = array_unique($loginsForShower);
            }
            $loginsArr = array();
            $signupDateColumn = array();
            foreach ($loginsForShower as $idx => $login) {
                $loginsArr[$idx] = $login;
                $signupDateColumn[$login] = isset($this->allSignups[$login]['date']) ? $this->allSignups[$login]['date'] : '-';
            }
            $extraColumns = array('Registered' => $signupDateColumn);
            $result .= wf_BackLink(self::URL_ME . '&' . self::ROUTE_RENDER . '=' . self::R_LIFECYCLE) . wf_delimiter();
            $result .= web_UserArrayShower($loginsArr, $extraColumns);
            return ($result);
        }

        // Yearly aggregates report
        $byYear = array();
        foreach ($stats as $year => $byMonth) {
            if (!isset($byYear[$year])) {
                $byYear[$year] = array('connected' => 0, 'dead_souls' => 0, 'lost' => 0, 'active' => 0, 'lifetime_sum' => 0);
            }
            foreach ($byMonth as $row) {
                $byYear[$year]['connected'] += $row['connected'];
                $byYear[$year]['dead_souls'] += isset($row['dead_souls']) ? $row['dead_souls'] : 0;
                $byYear[$year]['lost'] += $row['lost'];
                $byYear[$year]['active'] += $row['active'];
                $byYear[$year]['lifetime_sum'] += isset($row['lifetime_sum']) ? $row['lifetime_sum'] : 0;
            }
        }

        
        $tablecells = wf_TableCell(__('Year'));
        $tablecells .= wf_TableCell(__('Signup'));
        $tablecells .= wf_TableCell(__('Dead souls'));
        $tablecells .= wf_TableCell(__('Lost'));
        $tablecells .= wf_TableCell(__('Still active'));
        $tablecells .= wf_TableCell(__('Survival rate'));
        $tablecells .= wf_TableCell(__('Average lifetime'));
        $tablerows = wf_TableRow($tablecells, 'row1');
        foreach ($byYear as $year => $row) {
            $pct = $row['connected'] > 0 ? zb_PercentValue($row['connected'], $row['active']) . '%' : '0%';
            $avgLifetime = $row['connected'] > 0 ? (int) round($row['lifetime_sum'] / $row['connected']) : 0;
            $lifecycleUrlBase = self::URL_ME . '&' . self::ROUTE_RENDER . '=' . self::R_LIFECYCLE . '&' . self::ROUTE_LIFECYCLE_USERS . '=1&' . self::ROUTE_LIFECYCLE_YEAR . '=' . $year;
            $deadSouls = isset($row['dead_souls']) ? (int) $row['dead_souls'] : 0;
            $connectedCell = $row['connected'] ? wf_Link($lifecycleUrlBase . '&' . self::ROUTE_LIFECYCLE_TYPE . '=connected', $row['connected']) : '0';
            $deadSoulsCell = $deadSouls ? wf_Link($lifecycleUrlBase . '&' . self::ROUTE_LIFECYCLE_TYPE . '=dead_souls', $deadSouls) : '0';
            $lostCell = $row['lost'] ? wf_Link($lifecycleUrlBase . '&' . self::ROUTE_LIFECYCLE_TYPE . '=lost', $row['lost']) : $row['lost'];
            $activeCell = $row['active'] ? wf_Link($lifecycleUrlBase . '&' . self::ROUTE_LIFECYCLE_TYPE . '=active', $row['active']) : $row['active'];
            $tablecells = wf_TableCell($year);
            $tablecells .= wf_TableCell($connectedCell);
            $tablecells .= wf_TableCell($deadSoulsCell);
            $tablecells .= wf_TableCell($lostCell);
            $tablecells .= wf_TableCell($activeCell);
            $tablecells .= wf_TableCell($pct);
            $tablecells .= wf_TableCell(zb_formatTimeDays($avgLifetime));
            $tablerows .= wf_TableRow($tablecells, 'row5');
        }
        $result .= wf_tag('b', false) . __('By year') . wf_tag('b', true) . wf_tag('br');
        $result .= wf_TableBody($tablerows, '100%', '0', 'sortable');
        $result .= wf_tag('br');

        //month summary report
        $tablecells = wf_TableCell(__('Year'));
        $tablecells .= wf_TableCell(__('Month'));
        $tablecells .= wf_TableCell(__('Signup'));
        $tablecells .= wf_TableCell(__('Dead souls'));
        $tablecells .= wf_TableCell(__('Lost'));
        $tablecells .= wf_TableCell(__('Still active'));
        $tablecells .= wf_TableCell(__('Survival rate'));
        $tablecells .= wf_TableCell(__('Average lifetime'));
        $tablerows = wf_TableRow($tablecells, 'row1');

        foreach ($stats as $year => $byMonth) {
            krsort($byMonth, SORT_NUMERIC);
            foreach ($byMonth as $month => $row) {
                $monthName = isset($months[$month]) ? rcms_date_localise($months[$month]) : $month;
                $pct = $row['connected'] > 0 ? zb_PercentValue($row['connected'], $row['active']) . '%' : '0%';
                $lifetimeSum = isset($row['lifetime_sum']) ? $row['lifetime_sum'] : 0;
                $avgLifetime = $row['connected'] > 0 ? (int) round($lifetimeSum / $row['connected']) : 0;
                $lifecycleUrlBase = self::URL_ME . '&' . self::ROUTE_RENDER . '=' . self::R_LIFECYCLE . '&' . self::ROUTE_LIFECYCLE_USERS . '=1&' . self::ROUTE_LIFECYCLE_YEAR . '=' . $year . '&' . self::ROUTE_LIFECYCLE_MONTH . '=' . $month;
                $deadSouls = isset($row['dead_souls']) ? (int) $row['dead_souls'] : 0;
                $connectedCell = $row['connected'] ? wf_Link($lifecycleUrlBase . '&' . self::ROUTE_LIFECYCLE_TYPE . '=connected', $row['connected']) : '0';
                $deadSoulsCell = $deadSouls ? wf_Link($lifecycleUrlBase . '&' . self::ROUTE_LIFECYCLE_TYPE . '=dead_souls', $deadSouls) : '0';
                $lostCell = $row['lost'] ? wf_Link($lifecycleUrlBase . '&' . self::ROUTE_LIFECYCLE_TYPE . '=lost', $row['lost']) : $row['lost'];
                $activeCell = $row['active'] ? wf_Link($lifecycleUrlBase . '&' . self::ROUTE_LIFECYCLE_TYPE . '=active', $row['active']) : $row['active'];
                $tablecells = wf_TableCell($year);
                $tablecells .= wf_TableCell($monthName);
                $tablecells .= wf_TableCell($connectedCell);
                $tablecells .= wf_TableCell($deadSoulsCell);
                $tablecells .= wf_TableCell($lostCell);
                $tablecells .= wf_TableCell($activeCell);
                $tablecells .= wf_TableCell($pct);
                $tablecells .= wf_TableCell(zb_formatTimeDays($avgLifetime));
                $tablerows .= wf_TableRow($tablecells, 'row5');
            }
        }

        $result .= wf_tag('b', false) . __('By month') . wf_tag('b', true) . wf_tag('br');
        $result .= wf_TableBody($tablerows, '100%', '0', 'sortable');

        $totalUsers = 0;
        $totalLifetimeSum = 0;
        foreach ($stats as $byMonth) {
            foreach ($byMonth as $row) {
                $totalUsers += $row['connected'];
                $totalLifetimeSum += isset($row['lifetime_sum']) ? $row['lifetime_sum'] : 0;
            }
        }
        $overallAvgLifetimeSec = $totalUsers > 0 ? (int) round($totalLifetimeSum / $totalUsers) : 0;
        $avgLifetimeLostSec = $totalLostCount > 0 ? (int) round($totalLifetimeSumLost / $totalLostCount) : 0;
        $result .= wf_tag('br');
        $result .= wf_tag('b', false) . __('Average user lifetime') . ': ' . zb_formatTimeDays($overallAvgLifetimeSec) . wf_tag('b', true);
        $result .= wf_tag('br');
        $result .= wf_tag('b', false) . __('Average lifetime').' ('. __('Lost') . '): ' . zb_formatTimeDays($avgLifetimeLostSec);
       
        $result .= wf_tag('b', true);
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        return ($result);
    }
}
