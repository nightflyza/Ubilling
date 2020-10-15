<?php

class TaskFlow {

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains array of all available employee from directory
     *
     * @var array
     */
    protected $allActiveEmployee = array();

    /**
     * Task states instance placeholder
     *
     * @var object
     */
    protected $taskStates = '';

    /**
     * Photostorage object instance for tasks scope
     *
     * @var object
     */
    protected $photoStorage = '';

    /**
     * ADcomments object instance placeholder
     *
     * @var object
     */
    protected $adComments = '';

    /**
     * Contains all warehouse outcome counters as taskid=>count
     *
     * @vara array
     */
    protected $allWarehouseOutcomes = array();

    /**
     * System message helper instance placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Predefined routes/URLs/etc
     */
    const URL_ME = '?module=taskflow';
    const PROUTE_STATE = 'searchtaskstate';
    const PROUTE_PHOTO = 'searchtaskphoto';
    const PROUTE_WAREHOUSE = 'searchtaskwarehouse';
    const PROUTE_ADCOMMENTS = 'searchtaskadcomments';
    const PROUTE_EMPLOYEE = 'searchtaskemployee';
    const PROUTE_STARTSEARCH = 'searchtaskbegin';

    public function __construct() {
        $this->loadAlter();
        $this->initMessages();
        $this->loadEmployee();
        $this->initTaskStates();
    }

    /**
     * Loads system alter config into protected prop
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
     * Inits system messages helper protected instance
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Loads available employee from database
     * 
     * @return void
     */
    protected function loadEmployee() {
        $this->allActiveEmployee = ts_GetActiveEmployee();
    }

    /**
     * Inits TaskStates instance for further usage
     * 
     * @return void
     */
    protected function initTaskStates() {
        $this->taskStates = new TaskStates();
    }

    /**
     * Inits photostorage instance if required
     * 
     * @return void
     */
    protected function initPhotostorage() {
        if ($this->altCfg['PHOTOSTORAGE_ENABLED']) {
            $this->photoStorage = new PhotoStorage('TASKMAN');
        }
    }

    /**
     * Inits ADcomments instance for further usage
     * 
     * @return void
     */
    protected function initADcomments() {
        if ($this->altCfg['ADCOMMENTS_ENABLED']) {
            $this->adComments = new ADcomments('TASKMAN');
        }
    }

    /**
     * Loads existing warehouse outcome operations from database
     * 
     * @return void
     */
    protected function loadWarehouseOutcomes() {
        if ($this->altCfg['WAREHOUSE_ENABLED']) {
            $outcomes = new NyanORM('wh_out');
            $outcomes->where('desttype', '=', 'task');
            $all = $outcomes->getAll();
            if (!empty($all)) {
                foreach ($all as $io => $each) {
                    if (isset($this->allWarehouseOutcomes[$each['destparam']])) {
                        $this->allWarehouseOutcomes[$each['destparam']] ++;
                    } else {
                        $this->allWarehouseOutcomes[$each['destparam']] = 1;
                    }
                }
            }
        }
    }

    /**
     * Render primary module controls aka filters
     * 
     * @return string
     */
    public function renderControls() {
        $result = '';

        $inputs = wf_HiddenInput(self::PROUTE_STARTSEARCH, 'true'); //do some search flag

        $stateFlag = ubRouting::post(self::PROUTE_STATE);
        $inputs .= wf_SelectorAC(self::PROUTE_STATE, $this->taskStates->getStateTypes(), __('Task state'), $stateFlag, false) . ' ';

        $filterParams = array('any' => __('No difference'), 'yes' => __('Yes'), 'no' => __('No'));

        if ($this->altCfg['PHOTOSTORAGE_ENABLED']) {
            $photoFlag = ubRouting::post(self::PROUTE_PHOTO);
            $inputs .= wf_SelectorAC(self::PROUTE_PHOTO, $filterParams, __('Image'), $photoFlag, false) . ' ';
        }

        if ($this->altCfg['WAREHOUSE_ENABLED']) {
            $whFlag = ubRouting::post(self::PROUTE_WAREHOUSE);
            $inputs .= wf_SelectorAC(self::PROUTE_WAREHOUSE, $filterParams, __('Additionally spent materials'), $whFlag, false) . ' ';
        }

        if ($this->altCfg['ADCOMMENTS_ENABLED']) {
            $adFlag = ubRouting::post(self::PROUTE_ADCOMMENTS);
            $inputs .= wf_SelectorAC(self::PROUTE_ADCOMMENTS, $filterParams, __('Additional comments'), $adFlag, false) . ' ';
        }

        if (!empty($this->allActiveEmployee)) {
            $employeeParams = array('any' => __('No difference'));
            $employeeParams += $this->allActiveEmployee;

            $empFlag = ubRouting::post(self::PROUTE_EMPLOYEE);
            $inputs .= wf_SelectorAC(self::PROUTE_EMPLOYEE, $employeeParams, __('Employee'), $empFlag, false) . ' ';
        }

        // $inputs .= wf_Submit(__('Search'));

        $result .= wf_Form('', 'POST', $inputs, 'glamour');

        return($result);
    }

    /**
     * Performs search of some tasks on selected params
     * 
     * @return string
     */
    public function renderSearchResults() {
        $result = '';
        //realy search is required?
        if (ubRouting::checkPost(self::PROUTE_STARTSEARCH)) {
            //Preloading some data
            $this->initPhotostorage();
            $this->loadWarehouseOutcomes();
            $this->initADcomments();


            $allUndoneTasks = ts_GetUndoneTasksArray();
            $filteredTasks = array(); // mem overusage? heh.. who cares?! :P
            //search filters setup
            $filterState = ubRouting::post(self::PROUTE_STATE);

            if (!empty($allUndoneTasks)) {
                foreach ($allUndoneTasks as $taskId => $taskData) {
                    if ($this->taskStates->getTaskState($taskId) == $filterState) {
                        debarr($taskData); //TODO: take a decision about refilter or exclude from source
                    }
                }
            } else {
                $result .= $this->messages->getStyledMessage(__('Nothing found'), 'info');
            }
        }

        return($result);
    }

}
