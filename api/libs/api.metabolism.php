<?php

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
     * Routes etc.
     */
    const URL_ME = '?module=metabolism';
    const URL_BACK = '?module=report_finance&analytics=true';

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
     * Renders default module controls panel
     * 
     * @return string
     */
    public function renderPanel() {
        $result = '';
        $result .= wf_BackLink(self::URL_BACK);
        $result .= wf_Link(self::URL_ME, wf_img_sized('skins/icon_dollar.gif', '', '16', '16') . ' ' . __('Payments'), false, 'ubButton') . ' ';
        $result .= wf_Link(self::URL_ME . '&signups=true', web_icon_charts() . ' ' . __('Signups'), false, 'ubButton') . ' ';
        $result .= wf_CleanDiv();
        $result .= wf_tag('br');
        $result .= $this->renderDateForm();
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

}
