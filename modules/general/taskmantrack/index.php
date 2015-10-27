<?php

if (cfr('TASKMANTRACK')) {

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

        public function __construct() {
            $this->setLogin();
            $this->initMessages();
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
            $this->myLogin = mysql_real_escape_string(whoami());
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
            $query = "SELECT * from `taskmantrack` WHERE `admin`='" . $this->myLogin . "'";
            $all = simple_queryall($query);
            if (!empty($all)) {
                foreach ($all as $io => $each) {
                    $this->trackingTasks[$each['taskid']] = $each['id'];
                }
            }
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
            $taskid = vf($taskid, 3);
            if (isset($this->allTasks[$taskid])) {
                if (!isset($this->trackingTasks[$taskid])) {
                    $query = "INSERT INTO `taskmantrack` (`id`, `taskid`, `admin`) VALUES (NULL, '" . $taskid . "', '" . $this->myLogin . "'); ";
                    nr_query($query);
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
            $taskid = vf($taskid, 3);
            $query = "DELETE from `taskmantrack` WHERE `taskid`='" . $taskid . "'";
            nr_query($query);
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
                $cells.= wf_TableCell(__('Address'));
                $cells.= wf_TableCell(__('Job type'));
                $cells.= wf_TableCell(__('Phone'));
                $cells.= wf_TableCell(__('Who should do'));
                $cells.= wf_TableCell(__('Worker done'));
                $cells.= wf_TableCell(__('Target date'));
                $cells.= wf_TableCell(__('Finish date'));
                $cells.= wf_TableCell(__('Actions'));
                $rows = wf_TableRow($cells, 'row1');

                foreach ($this->trackingTasks as $taskid => $trackid) {
                    if (isset($this->allTasks[$taskid])) {
                        $taskData = $this->allTasks[$taskid];

                        if ($taskData['status']) {
                            $rowStyle = 'donetask';
                        } else {
                            $rowStyle = 'undone';
                        }

                        $cells = wf_TableCell($taskid);
                        $cells.= wf_TableCell($taskData['address']);
                        $cells.= wf_TableCell(@$this->allJobtypes[$taskData['jobtype']]);
                        $cells.= wf_TableCell($taskData['phone']);
                        $cells.= wf_TableCell(@$this->allEmployee[$taskData['employee']]);
                        $cells.= wf_TableCell(@$this->allEmployee[$taskData['employeedone']]);
                        $cells.= wf_TableCell($taskData['startdate']);
                        $cells.= wf_TableCell($taskData['enddate']);
                        $actLinks = wf_Link('?module=taskman&edittask=' . $taskid, web_edit_icon()) . ' ';
                        $actLinks.= wf_JSAlert('?module=taskmantrack&untrackid=' . $taskid, wf_img('skins/icon_cleanup.png', __('Stop tracking this task')), $this->messages->getEditAlert());
                        $cells.= wf_TableCell($actLinks);
                        $rows.= wf_TableRow($cells, $rowStyle);
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

    $taskTracking = new TaskmanTracking();

    //create new task tracking
    if (wf_CheckGet(array('trackid'))) {
        $trackingResult = $taskTracking->setTaskTracked($_GET['trackid']);
        if ($trackingResult) {
            rcms_redirect('?module=taskmantrack');
        } else {
            show_error(__('Strange exeption') . ': EX_NON_EXISTING_TASKID');
        }
    }

    //delete task tracking
    if (wf_CheckGet(array('untrackid'))) {
        $taskTracking->setTaskUntracked($_GET['untrackid']);
        rcms_redirect('?module=taskmantrack');
    }

    //rendering tracking list
    show_window(__('Tasks tracking'), $taskTracking->render());
} else {
    show_error(__('Access denied'));
}
?>