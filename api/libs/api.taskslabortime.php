<?php

class TasksLaborTime {

    /**
     * Salary object placeholder
     *
     * @var object
     */
    protected $salary = '';

    /**
     * Contains all jobtypes expected labor times as jobtypeid=>time in minutes
     *
     * @var array
     */
    protected $allJobTimes = array();

    /**
     * Contains all tasks filtered by date
     *
     * @var array
     */
    protected $allTasksFiltered = array();

    /**
     * System message helper instance
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Contains date for rendering basic report
     *
     * @var string
     */
    protected $showDate = '';

    /**
     * Contains all employee as id=>name
     *
     * @var array
     */
    protected $allEmployee = array();

    //predefined URLS, routes, etc..
    const URL_ME = '?module=report_taskslabortime';
    const PROUTE_DATE = 'tasklabortimedatefilter';

    public function __construct() {
        $this->setDateFilter();
        $this->initMessages();
        $this->initSalary();
        $this->loadJobTimes();
        $this->loadTasks();
        $this->loadEmployee();
    }

    /**
     * Sets date to render report based on search controls state
     * 
     * @return void
     */
    protected function setDateFilter() {
        if (ubRouting::checkPost(self::PROUTE_DATE)) {
            $this->showDate = ubRouting::post(self::PROUTE_DATE, 'mres');
        } else {
            $this->showDate = curdate();
        }
    }

    /**
     * Inits system messages helper
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Inits salary instance for further usage
     * 
     * @rerutn void
     */
    protected function initSalary() {
        $this->salary = new Salary();
    }

    /**
     * Loads expected jobtype labor times into protected property
     * 
     * @return void
     */
    protected function loadJobTimes() {
        $this->allJobTimes = $this->salary->getAllJobTimes();
    }

    /**
     * Loads all tasks by some date from database
     * 
     * @return void
     */
    protected function loadTasks() {
        $this->allTasksFiltered = ts_getAllTasksByDate($this->showDate);
    }

    /**
     * Loads all employee data from database
     * 
     * @return void
     */
    protected function loadEmployee() {
        $this->allEmployee = ts_GetAllEmployee();
    }

    /**
     * Renders default module search form with some controls
     * 
     * @return string
     */
    public function renderSearchForm() {
        $result = '';
        $inputs = wf_DatePickerPreset(self::PROUTE_DATE, $this->showDate, true) . ' ';
        $inputs .= wf_Submit(__('Show'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return($result);
    }

    /**
     * Returns job type timing from salary directory
     * 
     * @param int $jobTypeId
     * 
     * @return int
     */
    protected function getJobtypeTiming($jobTypeId) {
        $result = 0;
        if (isset($this->allJobTimes[$jobTypeId])) {
            $result = $this->allJobTimes[$jobTypeId];
        }
        return($result);
    }

    /**
     * Returns current instance date filter value
     * 
     * @return string
     */
    public function getDateFilter() {
        return($this->showDate);
    }

    /**
     * Renders report in human-viewable format
     * 
     * @return string
     */
    protected function renderTimeline($timelineData) {
        $result = '';
        $containerId = 'timeline' . wf_InputId();
        $timelineCode = "['Activity', 'Start Time', 'End Time'],";

        if (!empty($timelineData)) {
            foreach ($timelineData as $key => $eachTime) {
                $startTime = $this->showDate . ' 08:00:00';
                $startTime = strtotime($startTime);
                $startYear = date("Y", $startTime);
                $startMonth = date("n", $startTime) - 1;
                $startDay = date("d", $startTime);
                $startHour = date("H", $startTime);
                $startMinute = date("i", $startTime);

                $endTime = $startTime + ($eachTime['time'] * 60);
                $endYear = date("Y", $endTime);
                $endMonth = date("n", $endTime) - 1;
                $endDay = date("d", $endTime);
                $endHour = date("H", $endTime);
                $endMinute = date("i", $endTime);

                $timelineCode .= "
                            ['" . $key . "',
                             new Date(" . $startYear . ", " . $startMonth . ", " . $startDay . ", " . $startHour . ", " . $startMinute . "),
                             new Date(" . $endYear . ", " . $endMonth . ", " . $endDay . ", " . $endHour . ", " . $endMinute . ")],";
            }
            $timelineCode = zb_CutEnd($timelineCode);
        }


        $result .= wf_tag('div', false, '', 'id="' . $containerId . '"') . wf_tag('div', true);
        $result .= wf_tag('script', false, '', 'type="text/javascript" src="https://www.gstatic.com/charts/loader.js"') . wf_tag('script', true);
        $result .= wf_tag('script', false);
        $result .= "google.charts.load('current', {'packages':['timeline']});";
        $result .= "google.charts.setOnLoadCallback(drawChart);";
        $result .= " function drawChart() {
                          var data = google.visualization.arrayToDataTable([
                          " . $timelineCode . "
                          ]);

                            var options = {
                              height: 800,
                              hAxis: {
                                     format: 'HH:mm'
                                     }
                            };

                            var chart = new google.visualization.Timeline(document.getElementById('" . $containerId . "'));

                            chart.draw(data, options);
                          }
                        ";
        $result .= wf_tag('script', true);

        return($result);
    }

    /**
     * Renders basic report
     * 
     * @return string
     */
    public function renderReport() {
        $result = '';
        $timelineData = array();
        $totalTasksTime = 0;
        if (!empty($this->allTasksFiltered)) {
            foreach ($this->allTasksFiltered as $io => $eachTask) {
                $taskEmployeeName = $this->allEmployee[$eachTask['employee']];
                $jobTiming = $this->getJobtypeTiming($eachTask['jobtype']);
                if (!empty($taskEmployeeName)) {
                    if (isset($timelineData[$taskEmployeeName])) {
                        $timelineData[$taskEmployeeName]['time'] += $jobTiming;
                        $timelineData[$taskEmployeeName]['taskscount'] ++;
                    } else {
                        $timelineData[$taskEmployeeName]['time'] = $jobTiming;
                        $timelineData[$taskEmployeeName]['taskscount'] = 1;
                    }
                    $totalTasksTime += $jobTiming;
                }
            }

            if (!empty($timelineData)) {
                $cells = wf_TableCell(__('Employee'));
                $cells .= wf_TableCell(__('Time'));
                $cells .= wf_TableCell(__('Tasks'));
                $cells .= wf_TableCell(__('Percent'), '50%');
                $rows = wf_TableRow($cells, 'row1');
                foreach ($timelineData as $employeeName => $tasksTiming) {
                    $cells = wf_TableCell($employeeName);
                    $cells .= wf_TableCell(zb_formatTime(($tasksTiming['time'] * 60)), '', '', 'sorttable_customkey="' . $tasksTiming['time'] . '"');
                    $cells .= wf_TableCell($tasksTiming['taskscount']);
                    $cells .= wf_TableCell(web_bar($tasksTiming['time'], $totalTasksTime));
                    $rows .= wf_TableRow($cells, 'row5');
                }
                $result .= wf_TableBody($rows, '100%', 0, 'sortable');
                $result .= wf_delimiter(0);
                $result .= $this->renderTimeline($timelineData);
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'info');
        }
        return($result);
    }

}
