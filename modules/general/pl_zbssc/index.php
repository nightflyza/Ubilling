<?php

if (cfr('CREDIT')) {
    $altCfg = $ubillingConfig->getAlter();
    if ($altCfg['SCREP_ENABLED']) {

        class ZBSSC {

            /**
             * System message helper object placeholder
             *
             * @var object
             */
            protected $messages = '';

            /**
             * current instance user login
             *
             * @var string
             */
            protected $userLogin = '';

            /**
             * Self credit log data source
             *
             * @var object
             */
            protected $scLog = '';

            /**
             * System manual credit data source
             *
             * @var object
             */
            protected $eventLog = '';

            /**
             * Contains raw report data loaded from datasource
             *
             * @var array
             */
            protected $allData = array();

            /**
             * Some other predefined stuff
             */
            const COLOR_MONTH = '#ed9c00';
            const COLOR_TODAY = '#ba0000';

            /**
             * Creates new report instance
             * 
             * @param string $userLogin
             * 
             * @return void
             */
            public function __construct($userLogin) {
                $this->setLogin($userLogin);
                $this->initMessages();
                $this->initDataSource();
            }

            /**
             * Inits system message helper
             * 
             * @return void
             */
            protected function initMessages() {
                $this->messages = new UbillingMessageHelper();
            }

            /**
             * Inits data sources abstraction layers
             * 
             * @return void
             */
            protected function initDataSource() {
                $this->scLog = new NyanORM('zbssclog');
                $this->eventLog = new NyanORM('weblogs');
            }

            /**
             * Sets current instance user login
             * 
             * @param string $userLogin
             * 
             * @return void
             */
            protected function setLogin($userLogin) {
                $this->userLogin = ubRouting::filters($userLogin, 'mres');
            }

            /**
             * Loads data from datasources into protected prop for further processing
             * 
             * @return void
             */
            protected function loadData() {
                $this->scLog->where('login', '=', $this->userLogin);
                $tmpScData = $this->scLog->getAll('date');

                $this->eventLog->where('event', 'LIKE', '%CHANGE Credit (' . $this->userLogin . ')%');
                $this->eventLog->selectable(array('id', 'date', 'admin'));
                $tmpEventData = $this->eventLog->getAll('date');
                $dataTmp = $tmpScData + $tmpEventData;
                if (!empty($dataTmp)) {
                    ksort($dataTmp); //normal ordering
                    $this->allData = array_reverse($dataTmp);
                }
            }

            /**
             * Performs current day/month hilighting
             * 
             * @param string $date
             * @param string $currentMonth
             * @param string $currentDay
             * 
             * @return string
             */
            protected function colorize($date, $currentMonth, $currentDay) {
                $result = $date;
                if (ispos($date, $currentMonth)) {
                    $result = wf_tag('font', false, '', 'color="' . self::COLOR_MONTH . '"') . $date . wf_tag('font', 'true');
                }

                if (ispos($date, $currentDay)) {
                    $result = wf_tag('font', false, '', 'color="' . self::COLOR_TODAY . '"') . $date . wf_tag('font', 'true');
                }
                return($result);
            }

            /**
             * Renders self credit service report
             * 
             * @return string
             */
            public function render() {
                $result = '';
                $curMonth = curmonth();
                $curDay = curdate();
                @$employeeNames = unserialize(ts_GetAllEmployeeLoginsCached());
                if (!empty($this->userLogin)) {
                    $this->loadData();
                }
                if (!empty($this->allData)) {
                    $cells = wf_TableCell(__('Date'));
                    $cells .= wf_TableCell(__('Administrator'));
                    $rows = wf_TableRow($cells, 'row1');
                    foreach ($this->allData as $io => $each) {
                        $cells = wf_TableCell($this->colorize($each['date'], $curMonth, $curDay));
                        $adminLogin = 'external';

                        if (isset($each['admin'])) {
                            $adminLogin = $each['admin'];
                            //existing employee admin
                            if (isset($employeeNames[$adminLogin])) {
                                $adminLogin = $employeeNames[$adminLogin];
                            }
                        }
                        //userstats credit service
                        if ($adminLogin == 'guest' OR $adminLogin == 'external') {
                            $adminLogin = __('Userstats');
                        }

                        $cells .= wf_TableCell($adminLogin);
                        $rows .= wf_TableRow($cells, 'row5');
                    }

                    $result .= wf_TableBody($rows, '100%', 0, 'sortable');
                } else {
                    $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'info');
                }

                $result .= wf_delimiter();
                $result .= web_UserControls($this->userLogin);

                return($result);
            }

        }

        if (ubRouting::checkGet('username')) {
            $report = new ZBSSC(ubRouting::get('username'));

            show_window(__('Credits report'), $report->render());
        } else {
            show_error(__('Something went wrong') . ': ' . __('User not exists'));
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Permission denied'));
}