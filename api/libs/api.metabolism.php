<?php

/**
 * User signup and payments extended stats
 */
class Metabolism {

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
     * Contains default month to dislay with leading zero
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
    const ROUTE_RENDER='render';
    const R_PAYMENTS = 'payments';
    const R_SIGNUPS = 'signups';
    const R_LIFECYCLE = 'lifecycle';

    /**
     * Creates new metabolism instance
     */
    public function __construct() {
        $this->initMessages();
        $this->setDate();
        $this->initPayments();
        $this->initSignups();
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
                }

                if (!empty($tmpArr)) {
                    foreach ($tmpArr as $date => $each) {
                        $chartsData[] = array($date, $each['count'], $each['summ']);
                    }
                    $result .= wf_gchartsLine($chartsData, __('Cash'), '100%', '400px;', $this->getChartOptions());
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
     * Renders payments metabolism report
     * 
     * @return string
     */
    public function renderSignups() {
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
            $this->signups->orderBy('date', 'asc');
            $this->signups->where('date', 'LIKE', $dateFilter);
            $allSignups = $this->signups->getAll();
            if (!empty($allSignups)) {
                //prefill tmparr with zero day values
                if ($this->month != '1488') {
                    $showMonth = strtotime($this->year . '-' . $this->month);
                    $maxDay = date("t", $showMonth);
                    for ($zeroDay = 1; $zeroDay <= $maxDay; $zeroDay++) {
                        if ($zeroDay < 10) {
                            $tmpArr[$this->year . '-' . $this->month . '-0' . $zeroDay]['count'] = 0;
                        } else {
                            $tmpArr[$this->year . '-' . $this->month . '-' . $zeroDay]['count'] = 0;
                        }
                    }
                }

                $chartsData[] = array(__('Date'), __('Count'));
                foreach ($allSignups as $io => $each) {
                    $timeStamp = strtotime($each['date']); //need to be transformed to Y-m-d
                    $date = date("Y-m-d", $timeStamp);
                    if (isset($tmpArr[$date])) {
                        $tmpArr[$date]['count'] ++;
                    } else {
                        $tmpArr[$date]['count'] = 1;
                    }
                }

                if (!empty($tmpArr)) {
                    foreach ($tmpArr as $date => $each) {
                        $chartsData[] = array($date, $each['count']);
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
                $stats[$signupY][$signupM] = array('connected' => 0, 'dead_souls' => 0, 'lost' => 0, 'active' => 0, 'lifetime_sum' => 0);
            }
            $stats[$signupY][$signupM]['connected'] += 1;
            if ($lastPaymentDate === null) {
                $stats[$signupY][$signupM]['dead_souls'] += 1;
            }
            $stats[$signupY][$signupM]['lifetime_sum'] += $lifetimeSeconds;
            if ($isActive) {
                $stats[$signupY][$signupM]['active'] += 1;
            } else {
                if ($lastPaymentDate !== null) {
                    $churnY = date('Y', strtotime($lastPaymentDate));
                    $churnM = date('m', strtotime($lastPaymentDate));
                    if (!isset($stats[$churnY][$churnM])) {
                        $stats[$churnY][$churnM] = array('connected' => 0, 'dead_souls' => 0, 'lost' => 0, 'active' => 0, 'lifetime_sum' => 0);
                    }
                    $stats[$churnY][$churnM]['lost'] += 1;
                } else {
                    $stats[$signupY][$signupM]['lost'] += 1;
                }
            }
        }

        if (empty($stats)) {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
            return ($result);
        }

        krsort($stats, SORT_NUMERIC);
        $months = months_array();

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
        $tablecells .= wf_TableCell(__('Connected'));
        $tablecells .= wf_TableCell(__('Dead souls'));
        $tablecells .= wf_TableCell(__('Lost'));
        $tablecells .= wf_TableCell(__('Still active'));
        $tablecells .= wf_TableCell(__('Survival rate'));
        $tablecells .= wf_TableCell(__('Average lifetime'));
        $tablerows = wf_TableRow($tablecells, 'row1');
        foreach ($byYear as $year => $row) {
            $pct = $row['connected'] > 0 ? zb_PercentValue($row['connected'], $row['active']) . '%' : '0%';
            $avgLifetime = $row['connected'] > 0 ? (int) round($row['lifetime_sum'] / $row['connected']) : 0;
            $tablecells = wf_TableCell($year);
            $tablecells .= wf_TableCell($row['connected']);
            $tablecells .= wf_TableCell(isset($row['dead_souls']) ? $row['dead_souls'] : 0);
            $tablecells .= wf_TableCell($row['lost']);
            $tablecells .= wf_TableCell($row['active']);
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
        $tablecells .= wf_TableCell(__('Connected'));
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
                $tablecells = wf_TableCell($year);
                $tablecells .= wf_TableCell($monthName);
                $tablecells .= wf_TableCell($row['connected']);
                $tablecells .= wf_TableCell(isset($row['dead_souls']) ? $row['dead_souls'] : 0);
                $tablecells .= wf_TableCell($row['lost']);
                $tablecells .= wf_TableCell($row['active']);
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
        $result .= wf_tag('br');
        $result .= wf_tag('b', false) . __('Average user lifetime') . ': ' . zb_formatTimeDays($overallAvgLifetimeSec)  . wf_tag('b', true);
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        return ($result);
    }
}
