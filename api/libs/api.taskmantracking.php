<?php

/**
 * Tasks execution tracking report
 */
class TaskmanTracking {

    /**
     * Contains currently tracked tasks as taskid=>trackid
     *
     * @var array
     */
    protected $trackingTasks = array();

    /**
     * Contains all of available taskman tasks as id=>taskdata
     *
     * @var array
     */
    protected $allTasks = array();

    /**
     * Messages object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Available jobtypes as jobtypeid=>name
     *
     * @var array
     */
    protected $allJobtypes = array();

    /**
     * Available active and inactive employee
     * 
     * @var array
     */
    protected $allEmployee = array();

    /**
     * Contains current administrators login
     *
     * @var string
     */
    protected $myLogin = '';

    /**
     * Tracked tasks database abstraction layer
     *
     * @var object
     */
    protected $trackDb = '';

    public function __construct() {
        $this->setLogin();
        $this->initMessages();
        $this->initDbs();
        $this->loadTrackedTasks();
        $this->loadAllTasks();
        $this->loadEmployee();
        $this->loadJobtypes();
    }

    /**
     * Sets current administrator login, for further usage
     * 
     * @return void
     */
    protected function setLogin() {
        $this->myLogin = ubRouting::filters(whoami(), 'login');
    }

    /**
     * Inits messages helper object
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Inits required database abstraction layers
     *
     * @return void
     */
    protected function initDbs() {
        $this->trackDb = new NyanORM('taskmantrack');
    }

    /**
     * Loads all of employees from database
     * 
     * @return void
     */
    protected function loadEmployee() {
        $this->allEmployee = ts_GetAllEmployee();
    }

    /**
     * Loads available jobtypes from database
     * 
     * @return void
     */
    protected function loadJobtypes() {
        $this->allJobtypes = ts_GetAllJobtypes();
    }

    /**
     * Loads existing tasks tracks from database where 
     * 
     * @return void
     */
    protected function loadTrackedTasks() {
        $this->trackDb->where('admin', '=', $this->myLogin);
        $this->trackingTasks = $this->trackDb->getAll('taskid');
    }

    /**
     * Loads all of existing taskman tasks into protected property
     * 
     * @return void
     */
    protected function loadAllTasks() {
        $this->allTasks = ts_GetAllTasks();
    }

    /**
     * Sets some task as tracked
     * 
     * @param int $taskid
     * 
     * @return bool
     */
    public function setTaskTracked($taskid) {
        $result = true;
        $taskid = ubRouting::filters($taskid, 'int');
        if (isset($this->allTasks[$taskid])) {
            if (!isset($this->trackingTasks[$taskid])) {
                $this->trackDb->data('taskid', $taskid);
                $this->trackDb->data('admin', $this->myLogin);
                $this->trackDb->create();
                log_register('TASKMAN TRACK [' . $taskid . ']');
            }
        } else {
            $result = false;
        }
        return ($result);
    }

    /**
     * Sets some task as untracked
     * 
     * @param int $taskid
     * 
     * @return void
     */
    public function setTaskUntracked($taskid) {
        $taskid = ubRouting::filters($taskid, 'int');
        $this->trackDb->where('taskid', '=', $taskid);
        $this->trackDb->delete();
        log_register('TASKMAN UNTRACK [' . $taskid . ']');
    }


    /**
     * Renders list of currently tracked tasks
     * 
     * @return string
     */
    public function render() {
        $result = '';
        if (!empty($this->trackingTasks)) {
            $cells = wf_TableCell(__('ID'));
            $cells .= wf_TableCell(__('Address'));
            $cells .= wf_TableCell(__('Job type'));
            $cells .= wf_TableCell(__('Phone'));
            $cells .= wf_TableCell(__('Who should do'));
            $cells .= wf_TableCell(__('Worker done'));
            $cells .= wf_TableCell(__('Target date'));
            $cells .= wf_TableCell(__('Finish date'));
            $cells .= wf_TableCell(__('Time'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');

            foreach ($this->trackingTasks as $taskid => $trackid) {
                if (isset($this->allTasks[$taskid])) {
                    $taskData = $this->allTasks[$taskid];
                    $taskCreationTime = strtotime($taskData['date']);
                    $taskEndTime = ($taskData['status']) ? strtotime($taskData['enddate']) : time();
                    $timeSpent = $taskEndTime - $taskCreationTime;
                    $rowStyle = ($taskData['status']) ? 'donetask' : 'undone';

                    $cells = wf_TableCell($taskid);
                    $cells .= wf_TableCell($taskData['address']);
                    $cells .= wf_TableCell(@$this->allJobtypes[$taskData['jobtype']]);
                    $cells .= wf_TableCell($taskData['phone']);
                    $cells .= wf_TableCell(@$this->allEmployee[$taskData['employee']]);
                    $cells .= wf_TableCell(@$this->allEmployee[$taskData['employeedone']]);
                    $cells .= wf_TableCell($taskData['startdate']);
                    $cells .= wf_TableCell($taskData['enddate']);
                    $cells .= wf_TableCell(zb_formatTimeDays($timeSpent));
                    $actLinks = wf_Link('?module=taskman&edittask=' . $taskid, web_edit_icon()) . ' ';
                    $actLinks .= wf_JSAlert('?module=taskmantrack&untrackid=' . $taskid, wf_img('skins/icon_cleanup.png', __('Stop tracking this task')), $this->messages->getEditAlert());
                    $cells .= wf_TableCell($actLinks);
                    $rows .= wf_TableRow($cells, $rowStyle);
                } else {
                    //task is deleted
                }
            }
            $result = wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result = $this->messages->getStyledMessage(__('You are currently not tracking any tasks'), 'info');
        }
        return ($result);
    }
}
