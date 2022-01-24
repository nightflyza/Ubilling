<?php

/**
 * Implements basic tasks per-address dupicates report
 */
class TasksDuplicates {

    /**
     * Contains default from-date filter
     *
     * @var string
     */
    protected $dateFrom = '';

    /**
     * Contains default to-date filter
     *
     * @var string
     */
    protected $dateTo = '';

    /**
     * Contains optional job type to filter
     *
     * @var int
     */
    protected $jobTypeId = '';

    /**
     * Contains optional secondary job type filter
     *
     * @var int
     */
    protected $secondaryJobType = '';

    /**
     * Contains available jobtypes as id=>jobtypename
     *
     * @var array
     */
    protected $allJobtypes = array();

    /**
     * Contains array of available employee as id=>name
     *
     * @var array
     */
    protected $allEmployee = array();

    /**
     * System messages helper placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Tasks datasource database abstraction layer placeholder
     *
     * @var object
     */
    protected $tasksDB = '';

    /**
     * Some predefined URLs, routes etc
     */
    const URL_ME = '?module=report_taskduplicates';
    const URL_SHOWTASK = '?module=taskman&edittask=';
    const PROUTE_DATEFROM = 'datefromfilter';
    const PROUTE_DATETO = 'datetofilter';
    const PROUTE_JOBTYPE = 'jobtypeidfilter';
    const PROUTE_SECJOBTYPE = 'secondaryjobtypeidfilter';
    const PROUTE_SHOWREPORT = 'renderthisreport';
    const TABLE_DATASOURCE = 'taskman';

    /**
     * Creates new report instance
     */
    public function __construct() {
        $this->initMessages();
        $this->setDates();
        $this->setJobtype();
        $this->loadJobTypes();
        $this->loadEmployee();
        $this->initTasksDB();
    }

    /**
     * Sets actual date filters properties
     * 
     * @return void
     */
    protected function setDates() {
        if (ubRouting::checkPost(array(self::PROUTE_DATEFROM, self::PROUTE_DATETO))) {
            $this->dateFrom = ubRouting::post(self::PROUTE_DATEFROM, 'mres');
            $this->dateTo = ubRouting::post(self::PROUTE_DATETO, 'mres');
        } else {
            $this->dateFrom = date("Y-m-01");
            $this->dateTo = curdate();
        }
    }

    /**
     * Sets optional jobtype filter property
     * 
     * @return void
     */
    protected function setJobtype() {
        if (ubRouting::checkPost(self::PROUTE_JOBTYPE)) {
            $this->jobTypeId = ubRouting::post(self::PROUTE_JOBTYPE, 'int');
        }

        if (ubRouting::checkPost(self::PROUTE_SECJOBTYPE)) {
            $this->secondaryJobType = ubRouting::post(self::PROUTE_SECJOBTYPE, 'int');
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
     * Inits tasks abstraction database layer
     * 
     * @return void
     */
    protected function initTasksDB() {
        $this->tasksDB = new NyanORM(self::TABLE_DATASOURCE);
    }

    /**
     * Loads available jobtypes from database
     * 
     * @return void
     */
    protected function loadJobTypes() {
        $this->allJobtypes = ts_GetAllJobtypes();
    }

    /**
     * Loads existing employee data from database
     * 
     * @return void
     */
    protected function loadEmployee() {
        $this->allEmployee = ts_GetAllEmployee();
    }

    /**
     * Renders module search form
     * 
     * @return string
     */
    public function renderSearchForm() {
        $result = '';
        $inputs = wf_HiddenInput(self::PROUTE_SHOWREPORT, 'true');
        $inputs .= wf_DatePickerPreset(self::PROUTE_DATEFROM, $this->dateFrom) . ' ' . __('Date from') . ' ';
        $inputs .= wf_DatePickerPreset(self::PROUTE_DATETO, $this->dateTo) . ' ' . __('Date to') . ' ';
        $jobTypesArr = array(0 => __('Any'));
        $jobTypesArr += $this->allJobtypes;
        $inputs .= wf_Selector(self::PROUTE_JOBTYPE, $jobTypesArr, __('Job type'), $this->jobTypeId, false) . ' ';
        if ($this->jobTypeId) {
            $inputs .= wf_Selector(self::PROUTE_SECJOBTYPE, $jobTypesArr, __('Or'), $this->secondaryJobType, false) . ' ';
        }
        $inputs .= wf_Submit(__('Search'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return($result);
    }

    /**
     * Preloads and process report data from database
     * 
     * @return array
     */
    protected function getDuplicatesTasks() {
        $result = array();
        $addrTmp = array();
        $this->tasksDB->where('startdate', 'BETWEEN', $this->dateFrom . "' AND '" . $this->dateTo);
        $this->tasksDB->orderBy('startdate', 'DESC');
        $allTasks = $this->tasksDB->getAll('id');

        if (!empty($allTasks)) {
            foreach ($allTasks as $io => $each) {
                if ($this->jobTypeFilter($each)) {
                    $addrTmp[$each['address']][] = $each;
                }
            }

            if (!empty($addrTmp)) {
                foreach ($addrTmp as $io => $each) {
                    $tasksCount = sizeof($each);
                    if ($tasksCount > 1) {
                        $result[$io] = $each;
                    }
                }
            }
        }
        return($result);
    }

    /**
     * Check is some job pass some jobtype filters if its applied?
     * 
     * @param array $jobData
     * 
     * @return bool 
     */
    protected function jobTypeFilter($taskData) {
        $result = false;
        if (!empty($this->jobTypeId)) {
            if ($taskData['jobtype'] == $this->jobTypeId) {
                $result = true;
            } else {
                //additional jobtype filter may be?
                if ($this->secondaryJobType) {
                    if ($taskData['jobtype'] == $this->secondaryJobType) {
                        $result = true;
                    }
                }
            }
        } else {
            $result = true;
        }
        return($result);
    }

    /**
     * Renders report itself.
     * 
     * @return string
     */
    public function renderReport() {
        $result = '';
        $allTasks = $this->getDuplicatesTasks();
        if (!empty($allTasks)) {

            foreach ($allTasks as $eachAddress => $tasksArr) {
                $dupCount = sizeof($tasksArr);
                $result .= wf_tag('strong') . $eachAddress . ', ' . __('Tasks') . ': ' . $dupCount . wf_tag('strong', true);

                $cells = wf_TableCell(__('ID'), '5%');
                $cells .= wf_TableCell(__('Address'), '25%');
                $cells .= wf_TableCell(__('Job type'), '10%');
                $cells .= wf_TableCell(__('Phone'), '20%');
                $cells .= wf_TableCell(__('Employee'), '20%');
                $cells .= wf_TableCell(__('Target date'), '10%');
                $cells .= wf_TableCell(__('Done'), '5%');
                $cells .= wf_TableCell(__('Actions'), '5%');
                $rows = wf_TableRow($cells, 'row1');

                foreach ($tasksArr as $index => $taskData) {
                    $cells = wf_TableCell($taskData['id']);
                    $cells .= wf_TableCell($taskData['address']);
                    $cells .= wf_TableCell(@$this->allJobtypes[$taskData['jobtype']]);
                    $cells .= wf_TableCell($taskData['phone']);
                    $cells .= wf_TableCell(@$this->allEmployee[$taskData['employee']]);
                    $cells .= wf_TableCell($taskData['startdate']);
                    $cells .= wf_TableCell(web_bool_led($taskData['status']));
                    $taskActs = wf_Link(self::URL_SHOWTASK . $taskData['id'], web_edit_icon());
                    $cells .= wf_TableCell($taskActs);
                    $rows .= wf_TableRow($cells, 'row5');
                }
                $result .= wf_TableBody($rows, '100%', 0, 'sortable');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing found'), 'success');
        }
        return($result);
    }

    /**
     * Renders advice of the day
     * 
     * @return string
     */
    public function renderAdviceOfTheDay() {
        $result = '';
        $fga = new FGA();
        $adviceLabel = __('Advice of the day') . ': ' . $fga->getAdviceOfTheDay();
        $result .= $this->messages->getStyledMessage($adviceLabel, 'info');
        return($result);
    }

}
