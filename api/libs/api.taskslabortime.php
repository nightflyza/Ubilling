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

    //predefined URLS, routes, etc..
    const URL_ME = '?module=report_taskslabortime';
    const PROUTE_DATE = 'tasklabortimedatefilter';

    public function __construct() {
        $this->setDateFilter();
        $this->initSalary();
        $this->loadJobTimes();
        $this->loadTasks();
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
     * Renders basic report
     * 
     * @return string
     */
    public function renderReport() {
        $result = '';
        return($result);
    }

}
