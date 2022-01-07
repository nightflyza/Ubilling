<?php

/**
 * Extended tasks processing implementation based on task states
 */
class TaskFlow {

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains array of all active employee from directory as id=>name
     *
     * @var array
     */
    protected $allActiveEmployee = array();

    /**
     * Contains array of all available employee from directory as id=>name
     *
     * @var array
     */
    protected $allEmployee = array();

    /**
     * Contains all available job types as id=>name
     *
     * @var array
     */
    protected $allJobTypes = array();

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
     * Taskman database abstraction layer
     *
     * @var object
     */
    protected $taskmanDb = '';

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
    const URL_TASK = '?module=taskman&edittask=';
    const ROUTE_EMREPORT = 'employeereport';
    const PROUTE_STATE = 'searchtaskstate';
    const PROUTE_PHOTO = 'searchtaskphoto';
    const PROUTE_WAREHOUSE = 'searchtaskwarehouse';
    const PROUTE_ADCOMMENTS = 'searchtaskadcomments';
    const PROUTE_EMPLOYEE = 'searchtaskemployee';
    const PROUTE_STARTSEARCH = 'searchtaskbegin';
    const PROUTE_DATESTART = 'datestart';
    const PROUTE_DATEEND = 'dateend';
    const VAL_YES = 'yes';
    const VAL_NO = 'no';
    const VAL_ANY = 'any';

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
        $employeeTmp = ts_GetAllEmployeeData();
        foreach ($employeeTmp as $io => $each) {
            $this->allEmployee[$each['id']] = $each['name'];
            if ($each['active']) {
                $this->allActiveEmployee[$each['id']] = $each['name'];
            }
        }
    }

    /**
     * Loads all available jobtypes names from database
     * 
     * @return void
     */
    protected function loadJobTypes() {
        $this->allJobTypes = ts_GetAllJobtypes();
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
     * Inits taskman database abstraction layer
     * 
     * @return void
     */
    protected function initTaskmanDb() {
        $this->taskmanDb = new NyanORM('taskman');
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

        $filterParams = array(self::VAL_ANY => __('No difference'), self::VAL_YES => __('Yes'), self::VAL_NO => __('No'));

        if ($this->altCfg['PHOTOSTORAGE_ENABLED']) {
            $photoFlag = ubRouting::post(self::PROUTE_PHOTO);
            $inputs .= wf_SelectorAC(self::PROUTE_PHOTO, $filterParams, __('Image'), $photoFlag, false) . ' ';
        }

        if ($this->altCfg['WAREHOUSE_ENABLED']) {
            $whFlag = ubRouting::post(self::PROUTE_WAREHOUSE);
            $inputs .= wf_SelectorAC(self::PROUTE_WAREHOUSE, $filterParams, __('Warehouse'), $whFlag, false) . ' ';
        }

        if ($this->altCfg['ADCOMMENTS_ENABLED']) {
            $adFlag = ubRouting::post(self::PROUTE_ADCOMMENTS);
            $inputs .= wf_SelectorAC(self::PROUTE_ADCOMMENTS, $filterParams, __('Notes'), $adFlag, false) . ' ';
        }

        if (!empty($this->allActiveEmployee)) {
            $employeeParams = array('any' => __('No difference'));
            $employeeParams += $this->allActiveEmployee;

            $empFlag = ubRouting::post(self::PROUTE_EMPLOYEE);
            $inputs .= wf_SelectorAC(self::PROUTE_EMPLOYEE, $employeeParams, __('Worker'), $empFlag, false) . ' ';
        }

        $inputs .= wf_Submit(__('Search'));

        $result .= wf_Form('', 'POST', $inputs, 'glamour');

        return($result);
    }

    /**
     * Performs search of some tasks on selected params
     * 
     * @return string
     */
    public function performSearch() {
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
            $filterPhoto = ubRouting::post(self::PROUTE_PHOTO);
            $filterWarehouse = ubRouting::post(self::PROUTE_WAREHOUSE);
            $filterAdcomments = ubRouting::post(self::PROUTE_ADCOMMENTS);
            $filterEmployee = ubRouting::post(self::PROUTE_EMPLOYEE);

            if (!empty($allUndoneTasks)) {
                foreach ($allUndoneTasks as $taskId => $taskData) {
                    $filtersMatched = false;
                    //primary task state filtering
                    if ($this->taskStates->getTaskState($taskId) == $filterState) {
                        $filtersMatched = true;

                        //photostorage filering
                        if ($filterPhoto != self::VAL_ANY) {
                            $imagesCount = $this->photoStorage->getImagesCount($taskId);
                            if ($filterPhoto == self::VAL_YES AND $imagesCount == 0) {
                                $filtersMatched = false;
                            }
                            if ($filterPhoto == self::VAL_NO AND $imagesCount > 0) {
                                $filtersMatched = false;
                            }
                        }

                        //warehouse filtering
                        if ($filterWarehouse != self::VAL_ANY) {
                            $outcomesCount = 0;
                            if (isset($this->allWarehouseOutcomes[$taskId])) {
                                $outcomesCount = $this->allWarehouseOutcomes[$taskId];
                            }

                            if ($filterWarehouse == self::VAL_YES AND $outcomesCount == 0) {
                                $filtersMatched = false;
                            }

                            if ($filterWarehouse == self::VAL_NO AND $outcomesCount > 0) {
                                $filtersMatched = false;
                            }
                        }

                        //ADcomments filtering 
                        if ($filterAdcomments != self::VAL_ANY) {
                            $adCommentsCount = $this->adComments->getCommentsCount($taskId);

                            if ($filterAdcomments == self::VAL_YES AND $adCommentsCount == 0) {
                                $filtersMatched = false;
                            }

                            if ($filterAdcomments == self::VAL_NO AND $adCommentsCount > 0) {
                                $filtersMatched = false;
                            }
                        }

                        if ($filterEmployee != self::VAL_ANY) {
                            if ($filterEmployee != $taskData['employee']) {
                                $filtersMatched = false;
                            }
                        }
                    }

                    if ($filtersMatched) {
                        $filteredTasks[$taskId] = $taskData;
                    }
                }
                //display some tasks list
                if (!empty($filteredTasks)) {
                    $result .= $this->renderFilteredTasks($filteredTasks);
                } else {
                    $result .= $this->messages->getStyledMessage(__('Nothing found'), 'info');
                }
            } else {
                $result .= $this->messages->getStyledMessage(__('Nothing found'), 'info');
            }
        }

        return($result);
    }

    /**
     * Renders filtered tasks array
     * 
     * @param array $filteredTasks
     * 
     * @return string
     */
    protected function renderFilteredTasks($filteredTasks) {
        $result = '';
        if (!empty($filteredTasks)) {
            //preloading some data required for rendering
            $allStateIcons = $this->taskStates->getStateIcons();
            $this->loadJobTypes();

            $cells = wf_TableCell(__('ID'));
            $cells .= wf_TableCell(__('Task state'));
            if (@$this->altCfg['PHOTOSTORAGE_ENABLED']) {
                $cells .= wf_TableCell(__('Image'));
            }
            if (@$this->altCfg['WAREHOUSE_ENABLED']) {
                $cells .= wf_TableCell(__('Outcoming operations'));
            }
            $cells .= wf_TableCell(__('Date'));
            $cells .= wf_TableCell(__('Address'));
            $cells .= wf_TableCell(__('Job type'));
            $cells .= wf_TableCell(__('Worker'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');

            foreach ($filteredTasks as $taskId => $taskData) {
                $taskState = $this->taskStates->getTaskState($taskId);
                $taskStateName = $this->taskStates->getStateName($taskState);
                if (isset($allStateIcons[$taskState])) {
                    $taskStateLabel = wf_img_sized($allStateIcons[$taskState], $taskStateName, 16, 16) . ' ' . $taskStateName;
                } else {
                    $taskStateLabel = $taskState;
                }
                $cells = wf_TableCell($taskData['id']);
                $cells .= wf_TableCell($taskStateLabel);
                if (@$this->altCfg['PHOTOSTORAGE_ENABLED']) {
                    $imagesCount = $this->photoStorage->getImagesCount($taskId);
                    $cells .= wf_TableCell(web_bool_led($imagesCount));
                }
                if (@$this->altCfg['WAREHOUSE_ENABLED']) {
                    $whOutcomesCount = (isset($this->allWarehouseOutcomes[$taskId])) ? $this->allWarehouseOutcomes[$taskId] : 0;
                    $cells .= wf_TableCell(web_bool_led($whOutcomesCount));
                }
                $cells .= wf_TableCell($taskData['startdate']);
                $cells .= wf_TableCell($taskData['address']);
                $cells .= wf_TableCell(@$this->allJobTypes[$taskData['jobtype']]);
                $cells .= wf_TableCell($this->allEmployee[$taskData['employee']]);
                $taskControl = wf_Link(self::URL_TASK . $taskId, web_icon_search() . ' ' . __('Show'), false, 'ubButton', 'target="_BLANK"');
                $cells .= wf_TableCell($taskControl);
                $rows .= wf_TableRow($cells, 'row3');
            }

            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        }
        return($result);
    }

    /**
     * Gets advice of the day
     * 
     * @return string
     */
    public function getAwesomeAdvice() {
        $result = '';
        $advices = new FGA();
        $result = $advices->getAdviceOfTheDay();
        return($result);
    }

    /**
     * Renders employee report search form
     * 
     * @return string
     */
    public function renderEmployeeReportForm() {
        $result = '';

        $dateStart = (ubRouting::checkPost(self::PROUTE_DATESTART)) ? ubRouting::post(self::PROUTE_DATESTART, 'mres') : date("Y-m-" . '01');
        $dateEnd = (ubRouting::checkPost(self::PROUTE_DATEEND)) ? ubRouting::post(self::PROUTE_DATEEND, 'mres') : curdate();

        $inputs = wf_DatePickerPreset(self::PROUTE_DATESTART, $dateStart) . ' ' . __('From');
        $inputs .= wf_DatePickerPreset(self::PROUTE_DATEEND, $dateEnd) . ' ' . __('To') . ' ';
        $inputs .= wf_Submit('Search');

        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return($result);
    }

    /**
     * Performs employee report data preprocessing
     * 
     * @return string
     */
    public function getrEmployeeReportData() {
        $result = array();
        if (!empty($this->allActiveEmployee)) {
            $this->initTaskmanDb();
            $dateStart = (ubRouting::checkPost(self::PROUTE_DATESTART)) ? ubRouting::post(self::PROUTE_DATESTART, 'mres') : date("Y-m-" . '01');
            $dateEnd = (ubRouting::checkPost(self::PROUTE_DATEEND)) ? ubRouting::post(self::PROUTE_DATEEND, 'mres') : curdate();

            $allStateTypes = $this->taskStates->getStateTypes();
            //setting date filter
            $this->taskmanDb->whereRaw("`startdate` BETWEEN '" . $dateStart . "' AND '" . $dateEnd . "'");
            //getting tasks planned for this period
            $allTasks = $this->taskmanDb->getAll();

            //preparing report data
            if (!empty($allTasks)) {
                foreach ($allTasks as $io => $each) {
                    $taskState = $this->taskStates->getTaskState($each['id']);
                    if (isset($result[$each['employee']])) {
                        $result[$each['employee']]['TOTALTASKS'] ++;
                    } else {
                        //prefilling report data array
                        $result[$each['employee']]['TOTALTASKS'] = 1;
                        $result[$each['employee']]['NOSTATE'] = 0;
                        if (!empty($allStateTypes)) {
                            foreach ($allStateTypes as $stateType => $typeName) {
                                $result[$each['employee']][$stateType] = 0;
                            }
                        }
                    }

                    //task state is set
                    if (!empty($taskState)) {
                        @$result[$each['employee']][$taskState] ++;
                    } else {
                        $result[$each['employee']]['NOSTATE'] ++;
                    }
                }
            }
        }
        return($result);
    }

    /**
     * Renders task states report around employee
     * 
     * @return string
     */
    public function renderEmployeeReport() {
        $result = '';
        if (!empty($this->allActiveEmployee)) {
            $reportData = $this->getrEmployeeReportData();
            if (!empty($reportData)) {
                $allStateTypes = $this->taskStates->getStateTypes();
                $allStateIcons = $this->taskStates->getStateIcons();

                $cells = wf_TableCell(__('Employee'));
                foreach ($allStateTypes as $eachStateId => $eachStateName) {
                    $stateIcon = $allStateIcons[$eachStateId];
                    $cells .= wf_TableCell(wf_img_sized($stateIcon, $eachStateName, '10', '10') . ' ' . $eachStateName);
                }
                $cells .= wf_TableCell(__('Total'));
                $cells .= wf_TableCell(__('No state'));

                $rows = wf_TableRow($cells, 'row1');

                foreach ($reportData as $employeeId => $stateStats) {
                    $cells = wf_TableCell($this->allEmployee[$employeeId]);
                    foreach ($allStateTypes as $eachStateId => $eachStateName) {
                        $cells .= wf_TableCell($stateStats[$eachStateId]);
                    }
                    $cells .= wf_TableCell($stateStats['TOTALTASKS']);
                    $cells .= wf_TableCell($stateStats['NOSTATE']);

                    $rows .= wf_TableRow($cells, 'row5');
                }

                $result .= wf_TableBody($rows, '100%', 0, 'sortable');
            } else {
                $result .= $this->messages->getStyledMessage(__('Nothing found'), 'info');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('No job types and employee available'), 'warning');
        }

        $result .= wf_delimiter();
        $result .= wf_BackLink(self::URL_ME);

        return($result);
    }

}
