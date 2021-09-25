<?php

/**
 * Tasks quality control report
 */
class TasksQualRep {

    /**
     * Current date
     *
     * @var string
     */
    protected $dateCurrentDay = '';

    /**
     * Date of start of current month
     *
     * @var string
     */
    protected $dateMonthBegin = '';

    /**
     * Date of end of current month
     *
     * @var string
     */
    protected $dateMonthEnd = '';

    /**
     * Date of begin of current week
     *
     * @var string
     */
    protected $dateWeekBegin = '';

    /**
     * Date of end of current week
     *
     * @var string
     */
    protected $dateWeekEnd = '';

    /**
     * Date of begin of the current year
     *
     * @var string
     */
    protected $dateYearBegin = '';

    /**
     * Date of end of current year
     *
     * @var string
     */
    protected $dateYearEnd = '';

    /**
     * Contains current year tasks array for future rendering
     *
     * @var array
     */
    protected $allTasksData = array();

    /**
     * Contains all employee names from database as id=>name
     *
     * @var array
     */
    protected $allEmployeeNames = array();

    /**
     * System message helper placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Task ranks (score) stigma source placeholder
     *
     * @var object
     */
    protected $taskRanks = '';

    /**
     * Task fails (anomalies) stigma source placeholder
     *
     * @var object
     */
    protected $taskFails = '';

    /**
     * What was done on tasks stigma source placeholder
     *
     * @var object
     */
    protected $tasksWhatDone = '';

    /**
     * Routes, URLs, etc..
     */
    const URL_TASKVIEW = '?module=taskman&edittask=';
    const URL_ME = '?module=tasksqualreport';
    const ROUTE_TASKRENDER = 'showtasks';

    public function __construct() {
        $this->setDates();
        $this->initMessages();
        $this->initDataSources();
    }

    /**
     * Inits system messages object instance for further usage
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Sets dates intervals to use it for report data filtering
     * 
     * @return void
     */
    protected function setDates() {
        $this->dateCurrentDay = curdate();
        $this->dateMonthBegin = curmonth() . '-01';
        $this->dateMonthEnd = curmonth() . '-' . date("t");
        $this->dateWeekBegin = date("Y-m-d", strtotime('monday this week'));
        $this->dateWeekEnd = date("Y-m-d", strtotime('sunday this week'));
        $this->dateYearBegin = curyear() . '-01-01';
        $this->dateYearBegin = curyear() . '-12-31';
    }

    /**
     * Inits datasources for reports
     * 
     * @return void
     */
    protected function initDataSources() {
        $this->taskRanks = new Stigma('TASKRANKS');
        $this->taskFails = new Stigma('TASKFAILS');
        $this->tasksWhatDone = new Stigma('TASKWHATIDO');
    }

    /**
     * Loads all tasks data from database
     * 
     * @return void
     */
    protected function loadTasks() {
        $this->allTasksData = ts_GetAllTasksQuickData();
    }

    /**
     * Loads all existing employee names from database
     * 
     * @return void
     */
    protected function loadEmployee() {
        $this->allEmployeeNames = ts_GetAllEmployee();
    }

    /**
     * Renders tasks list from stigma report data as modal
     * 
     * @param array $reportData
     * @param string $stateId
     * @param string $stateLabel
     * 
     * @return string
     */
    protected function renderTasksModal($reportData, $stateId, $stateLabel = '') {
        $result = '';
        $tasksList = '';
        $tasksCount = 0;
        if (!empty($reportData)) {
            if (isset($reportData[$stateId])) {
                if (isset($reportData[$stateId]['itemids'])) {
                    if (!empty($reportData[$stateId]['itemids'])) {
                        $cells = wf_TableCell(__('ID'));
                        $cells .= wf_TableCell(__('Date'));
                        $cells .= wf_TableCell(__('Address'));
                        $cells .= wf_TableCell(__('Worker'));
                        $rows = wf_TableRow($cells, 'row1');
                        foreach ($reportData[$stateId]['itemids'] as $io => $eachTaskId) {
                            if (isset($this->allTasksData[$eachTaskId])) {
                                $taskLink = wf_Link(self::URL_TASKVIEW . $eachTaskId, $this->allTasksData[$eachTaskId]['address'], false, '', 'TARGET="_BLANK"');
                                $taskEmployee = @$this->allEmployeeNames[$this->allTasksData[$eachTaskId]['employee']];
                                $cells = wf_TableCell($eachTaskId);
                                $cells .= wf_TableCell($this->allTasksData[$eachTaskId]['startdate']);
                                $cells .= wf_TableCell($taskLink);
                                $cells .= wf_TableCell($taskEmployee);
                                $rows .= wf_TableRow($cells, 'row5');
                                $tasksCount++;
                            }
                        }
                        $tasksList .= wf_TableBody($rows, '100%', 0, 'sortable');
                    }
                }
            }
        }

        if ($tasksCount > 0) {
            $result .= wf_modalAuto($tasksCount, $stateLabel, $tasksList);
        } else {
            $result .= '0';
        }
        return($result);
    }

    /**
     * Returns text representation of task execution ranks
     * 
     * @return string
     */
    public function getDailyRanksText($eol = '') {
        $result = '';
        $availRanks = $this->taskRanks->getAllStates();

        $dataDay = $this->taskRanks->getReportData($this->dateCurrentDay, $this->dateCurrentDay);
        if (!empty($dataDay)) {
            $totalCount = 0;
            foreach ($dataDay as $stateId => $stateData) {
                $totalCount += $stateData['count'];
            }
            $result .= __('Tasks processed') . ': ' . $totalCount . $eol;
            foreach ($dataDay as $stateId => $stateData) {
                $result .= __($availRanks[$stateId]) . ' - ' . $stateData['count'] . ' ' . $eol;
            }
        }
        return($result);
    }

    /**
     * Renders user scores of tasks execution
     * 
     * @return string
     */
    public function renderRanks() {
        $result = '';
        $tasksRenderFlag = (ubRouting::checkGet(self::ROUTE_TASKRENDER)) ? true : false;
        if ($tasksRenderFlag) {
            $this->loadTasks();
            $this->loadEmployee();
        }

        $availRanks = $this->taskRanks->getAllStates();

        $dataDay = $this->taskRanks->getReportData($this->dateCurrentDay, $this->dateCurrentDay);
        $dataWeek = $this->taskRanks->getReportData($this->dateWeekBegin, $this->dateWeekEnd);
        $dataMonth = $this->taskRanks->getReportData($this->dateMonthBegin, $this->dateMonthEnd);
        $dataYear = $this->taskRanks->getReportData($this->dateYearBegin, $this->dateYearEnd);
        $dataAllTime = $this->taskRanks->getReportData();


        if (!empty($availRanks)) {
            $cells = wf_TableCell(__('Score'), '30%');
            $cells .= wf_TableCell(__('Day'));
            $cells .= wf_TableCell(__('Week'));
            $cells .= wf_TableCell(__('Month'));
            $cells .= wf_TableCell(__('Year'));
            $cells .= wf_TableCell(__('All time'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($availRanks as $eachRankId => $eachRankDesc) {
                $rankLabel = __($eachRankDesc);
                $rankIcon = $this->taskRanks->getStateIcon($eachRankId);

                if ($tasksRenderFlag) {
                    $dayCount = $this->renderTasksModal($dataDay, $eachRankId, $rankLabel);
                    $weekCount = $this->renderTasksModal($dataWeek, $eachRankId, $rankLabel);
                    $monthCount = $this->renderTasksModal($dataMonth, $eachRankId, $rankLabel);
                    $yearCount = isset($dataYear[$eachRankId]['count']) ? $dataYear[$eachRankId]['count'] : 0;
                    $allTimeCount = isset($dataAllTime[$eachRankId]['count']) ? $dataAllTime[$eachRankId]['count'] : 0;
                } else {
                    $dayCount = isset($dataDay[$eachRankId]['count']) ? $dataDay[$eachRankId]['count'] : 0;
                    $weekCount = isset($dataWeek[$eachRankId]['count']) ? $dataWeek[$eachRankId]['count'] : 0;
                    $monthCount = isset($dataMonth[$eachRankId]['count']) ? $dataMonth[$eachRankId]['count'] : 0;
                    $yearCount = isset($dataYear[$eachRankId]['count']) ? $dataYear[$eachRankId]['count'] : 0;
                    $allTimeCount = isset($dataAllTime[$eachRankId]['count']) ? $dataAllTime[$eachRankId]['count'] : 0;
                }


                $cells = wf_TableCell(wf_img_sized($rankIcon, '', '10') . ' ' . $rankLabel);
                $cells .= wf_TableCell($dayCount);
                $cells .= wf_TableCell($weekCount);
                $cells .= wf_TableCell($monthCount);
                $cells .= wf_TableCell($yearCount);
                $cells .= wf_TableCell($allTimeCount);
                $rows .= wf_TableRow($cells, 'row5');
            }

            $result .= wf_TableBody($rows, '100%', 0, '');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }

        return($result);
    }

    /**
     * Renders user anomalies of tasks execution
     * 
     * @return string
     */
    public function renderFails() {
        $result = '';
        $tasksRenderFlag = (ubRouting::checkGet(self::ROUTE_TASKRENDER)) ? true : false;
        $availFails = $this->taskFails->getAllStates();

        $dataDay = $this->taskFails->getReportData($this->dateCurrentDay, $this->dateCurrentDay);
        $dataWeek = $this->taskFails->getReportData($this->dateWeekBegin, $this->dateWeekEnd);
        $dataMonth = $this->taskFails->getReportData($this->dateMonthBegin, $this->dateMonthEnd);
        $dataYear = $this->taskFails->getReportData($this->dateYearBegin, $this->dateYearEnd);
        $dataAllTime = $this->taskFails->getReportData();


        if (!empty($availFails)) {
            $cells = wf_TableCell(__('Fail'), '30%');
            $cells .= wf_TableCell(__('Day'));
            $cells .= wf_TableCell(__('Week'));
            $cells .= wf_TableCell(__('Month'));
            $cells .= wf_TableCell(__('Year'));
            $cells .= wf_TableCell(__('All time'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($availFails as $eachFailId => $eachFailDesc) {
                $failLabel = __($eachFailDesc);
                $failIcon = $this->taskFails->getStateIcon($eachFailId);

                if ($tasksRenderFlag) {
                    $dayCount = $this->renderTasksModal($dataDay, $eachFailId, $failLabel);
                    $weekCount = $this->renderTasksModal($dataWeek, $eachFailId, $failLabel);
                    $monthCount = $this->renderTasksModal($dataMonth, $eachFailId, $failLabel);
                    $yearCount = isset($dataYear[$eachFailId]['count']) ? $dataYear[$eachFailId]['count'] : 0;
                    $allTimeCount = isset($dataAllTime[$eachFailId]['count']) ? $dataAllTime[$eachFailId]['count'] : 0;
                } else {
                    $dayCount = isset($dataDay[$eachFailId]['count']) ? $dataDay[$eachFailId]['count'] : 0;
                    $weekCount = isset($dataWeek[$eachFailId]['count']) ? $dataWeek[$eachFailId]['count'] : 0;
                    $monthCount = isset($dataMonth[$eachFailId]['count']) ? $dataMonth[$eachFailId]['count'] : 0;
                    $yearCount = isset($dataYear[$eachFailId]['count']) ? $dataYear[$eachFailId]['count'] : 0;
                    $allTimeCount = isset($dataAllTime[$eachFailId]['count']) ? $dataAllTime[$eachFailId]['count'] : 0;
                }


                $cells = wf_TableCell(wf_img_sized($failIcon, '', '10') . ' ' . $failLabel);
                $cells .= wf_TableCell($dayCount);
                $cells .= wf_TableCell($weekCount);
                $cells .= wf_TableCell($monthCount);
                $cells .= wf_TableCell($yearCount);
                $cells .= wf_TableCell($allTimeCount);
                $rows .= wf_TableRow($cells, 'row5');
            }

            $result .= wf_TableBody($rows, '100%', 0, '');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }

        return($result);
    }

    /**
     * Renders what was done on tasks
     * 
     * @return string
     */
    public function renderWhatDone() {
        $result = '';
        $tasksRenderFlag = (ubRouting::checkGet(self::ROUTE_TASKRENDER)) ? true : false;
        $availStates = $this->tasksWhatDone->getAllStates();

        $dataDay = $this->tasksWhatDone->getReportData($this->dateCurrentDay, $this->dateCurrentDay);
        $dataWeek = $this->tasksWhatDone->getReportData($this->dateWeekBegin, $this->dateWeekEnd);
        $dataMonth = $this->tasksWhatDone->getReportData($this->dateMonthBegin, $this->dateMonthEnd);
        $dataYear = $this->tasksWhatDone->getReportData($this->dateYearBegin, $this->dateYearEnd);
        $dataAllTime = $this->tasksWhatDone->getReportData();


        if (!empty($availStates)) {
            $cells = wf_TableCell(__('Job'), '30%');
            $cells .= wf_TableCell(__('Day'));
            $cells .= wf_TableCell(__('Week'));
            $cells .= wf_TableCell(__('Month'));
            $cells .= wf_TableCell(__('Year'));
            $cells .= wf_TableCell(__('All time'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($availStates as $eachStateId => $eachStateDesc) {
                $stateLabel = __($eachStateDesc);
                $stateIcon = $this->tasksWhatDone->getStateIcon($eachStateId);

                if ($tasksRenderFlag) {
                    $dayCount = $this->renderTasksModal($dataDay, $eachStateId, $stateLabel);
                    $weekCount = $this->renderTasksModal($dataWeek, $eachStateId, $stateLabel);
                    $monthCount = $this->renderTasksModal($dataMonth, $eachStateId, $stateLabel);
                    $yearCount = isset($dataYear[$eachStateId]['count']) ? $dataYear[$eachStateId]['count'] : 0;
                    $allTimeCount = isset($dataAllTime[$eachStateId]['count']) ? $dataAllTime[$eachStateId]['count'] : 0;
                } else {
                    $dayCount = isset($dataDay[$eachStateId]['count']) ? $dataDay[$eachStateId]['count'] : 0;
                    $weekCount = isset($dataWeek[$eachStateId]['count']) ? $dataWeek[$eachStateId]['count'] : 0;
                    $monthCount = isset($dataMonth[$eachStateId]['count']) ? $dataMonth[$eachStateId]['count'] : 0;
                    $yearCount = isset($dataYear[$eachStateId]['count']) ? $dataYear[$eachStateId]['count'] : 0;
                    $allTimeCount = isset($dataAllTime[$eachStateId]['count']) ? $dataAllTime[$eachStateId]['count'] : 0;
                }


                $cells = wf_TableCell(wf_img_sized($stateIcon, '', '10') . ' ' . $stateLabel);
                $cells .= wf_TableCell($dayCount);
                $cells .= wf_TableCell($weekCount);
                $cells .= wf_TableCell($monthCount);
                $cells .= wf_TableCell($yearCount);
                $cells .= wf_TableCell($allTimeCount);
                $rows .= wf_TableRow($cells, 'row5');
            }

            $result .= wf_TableBody($rows, '100%', 0, '');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }

        return($result);
    }

    /**
     * Renders module controls
     * 
     * @return string
     */
    public function renderControls() {
        $result = '';
        $result .= wf_Link(self::URL_ME, web_icon_charts() . ' ' . __('Stats'), false, 'ubButton') . ' ';
        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_TASKRENDER . '=true', wf_img('skins/task_icon_small.png') . ' ' . __('Tasks'), false, 'ubButton') . ' ';
        return($result);
    }

}
