<?php

if (cfr('TASKMANSEARCH')) {

    class TaskmanSearch {

        /**
         * Contains all available employee as employeeid=>name
         *
         * @var array
         */
        protected $allEmployee = array();

        /**
         * Contains all active employee as employeeid=>name
         *
         * @var array
         */
        protected $activeEmployee = array();

        /**
         * System alter.ini config stored as array key=>value
         *
         * @var array
         */
        protected $altCfg = array();

        /**
         * Available jobtypes as jobtypeid=>name
         *
         * @var array
         */
        protected $allJobtypes = array();

        const URL_TASKVIEW = '?module=taskman&edittask=';

        public function __construct() {
            $this->loadAllEmployee();
            $this->loadActiveEmployee();
            $this->loadAltcfg();
            $this->loadJobtypes();
        }

        /**
         * Loads all existing employees from database
         * 
         * @return void
         */
        protected function loadAllEmployee() {
            $this->allEmployee = ts_GetAllEmployee();
        }

        /**
         * Loads all existing employees from database
         * 
         * @return void
         */
        protected function loadActiveEmployee() {
            $this->activeEmployee = ts_GetActiveEmployee();
        }

        /**
         * Loads system alter config
         * 
         * @global object $ubillingConfig
         * 
         * @return void
         */
        protected function loadAltcfg() {
            global $ubillingConfig;
            $this->altCfg = $ubillingConfig->getAlter();
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
         * Renders search form. Deal with it.
         * 
         * @return string
         */
        public function renderSearchForm() {
            $result = '';
            $datefromDefault = (wf_CheckPost(array('datefrom'))) ? $_POST['datefrom'] : curdate();
            $datetoDefault = (wf_CheckPost(array('dateto'))) ? $_POST['dateto'] : curdate();

            $inputs = __('Date') . ' ' . wf_DatePickerPreset('datefrom', $datefromDefault, true) . ' ' . __('From') . ' ' . wf_DatePickerPreset('dateto', $datetoDefault, true) . ' ' . __('To');
            $inputs.= wf_tag('br');
            $inputs.= wf_CheckInput('cb_id', '', false, false);
            $inputs.= wf_TextInput('taskid', __('ID'), '', true, 4);
            $inputs.= wf_CheckInput('cb_taskdays', '', false, false);
            $inputs.= wf_TextInput('taskdays', __('Implementation took more days'), '', true, 4);
            $inputs.= wf_CheckInput('cb_taskaddress', '', false, false);
            $inputs.= wf_TextInput('taskaddress', __('Task address'), '', true, 20);
            $inputs.= wf_CheckInput('cb_taskphone', '', false, false);
            $inputs.= wf_TextInput('taskphone', __('Phone'), '', true, 20);
            $inputs.= wf_CheckInput('cb_taskjobtype', '', false, false);
            $inputs.= wf_Selector('taskjobtype', $this->allJobtypes, __('Job type'), '', true);
            $inputs.= wf_CheckInput('cb_employee', '', false, false);
            $inputs.= wf_Selector('employee', $this->activeEmployee, __('Who should do'), '', true);
            $inputs.= wf_CheckInput('cb_employeedone', '', false, false);
            $inputs.= wf_Selector('employeedone', $this->activeEmployee, __('Worker done'), '', true);
            $inputs.= wf_CheckInput('cb_duplicateaddress', __('Duplicate address'), true, false);
            $inputs.= wf_CheckInput('cb_showlate', __('Show late'), true, false);
            $inputs.= wf_CheckInput('cb_onlydone', __('Done tasks'), true, false);
            $inputs.= wf_CheckInput('cb_onlyundone', __('Undone tasks'), true, false);
            if ($this->altCfg['SALARY_ENABLED']) {
                $inputs.=wf_CheckInput('cb_nosalsaryjobs', __('Tasks without jobs'), true, false);
            }

            $inputs.=wf_Submit(__('Search'));
            $result = wf_Form('', 'POST', $inputs, 'glamour');
            $result.= wf_CleanDiv();

            return ($result);
        }

        /**
         * Do some search actions by captured POST request
         * 
         * @return array
         */
        public function commonSearch() {
            $result = array();
            if (wf_CheckPost(array('datefrom', 'dateto'))) {
                $dateFrom = mysql_real_escape_string($_POST['datefrom']);
                $dateTo = mysql_real_escape_string($_POST['dateto']);
                $baseQuery = "SELECT * from `taskman` WHERE `startdate` BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "' ";
                $appendQuery = '';
                //task id
                if (wf_CheckPost(array('cb_id', 'taskid'))) {
                    $taskid = vf($_POST['taskid'], 3);
                    $appendQuery.=" AND `id`='" . $taskid . "' ";
                }
                //more than some days count
                if (wf_CheckPost(array('cb_taskdays', 'taskdays'))) {
                    $taskdays = vf($_POST['taskdays'], 3);
                    $appendQuery.=" AND DATEDIFF(`enddate`, `startdate`) > '" . $taskdays . "' ";
                }

                //task address non strict 
                if (wf_CheckPost(array('cb_taskaddress', 'taskaddress'))) {
                    $taskaddress = mysql_real_escape_string($_POST['taskaddress']);
                    $appendQuery.=" AND `address` LIKE '%" . $taskaddress . "%' ";
                }

                //task phone non strict 
                if (wf_CheckPost(array('cb_taskphone', 'taskphone'))) {
                    $taskphone = mysql_real_escape_string($_POST['taskphone']);
                    $appendQuery.=" AND `phone` LIKE '%" . $taskphone . "%' ";
                }

                //task job type
                if (wf_CheckPost(array('cb_taskjobtype', 'taskjobtype'))) {
                    $taskjobtypeid = vf($_POST['taskjobtype'], 3);
                    $appendQuery.=" AND `jobtype` LIKE '" . $taskjobtypeid . "' ";
                }

                //original task employee
                if (wf_CheckPost(array('cb_employee', 'employee'))) {
                    $employee = mysql_real_escape_string($_POST['employee']);
                    $appendQuery.=" AND `employee`='" . $employee . "' ";
                }

                //original task employeedone
                if (wf_CheckPost(array('cb_employeedone', 'employeedone'))) {
                    $employeedone = mysql_real_escape_string($_POST['employeedone']);
                    $appendQuery.=" AND `employeedone`='" . $employeedone . "' ";
                }

                //address duplicate search
                if (wf_CheckPost(array('cb_duplicateaddress'))) {
                    // $appendQuery.=" AND `address` IN (SELECT `address` FROM `taskman` WHERE `startdate` BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "' GROUP BY `address` HAVING COUNT(*) > 1) ";
                    $baseQuery = "SELECT st1.*, st2.`address` FROM `taskman`  st1  INNER JOIN taskman st2 ON (st2.`startdate` BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "' AND st1.address = st2.address)  GROUP BY st1.id HAVING COUNT(*) > 1 AND `startdate` BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "' ";
                }

                //late jobs
                if (wf_CheckPost(array('cb_showlate'))) {
                    $curdate = curdate();
                    $appendQuery.=" AND `status`='0' AND `startdate`< '" . $curdate . "' ";
                }

                //only done jobs
                if (wf_CheckPost(array('cb_onlydone'))) {
                    $appendQuery.=" AND `status`='1' ";
                }

                //only undone jobs
                if (wf_CheckPost(array('cb_onlyundone'))) {
                    $appendQuery.=" AND `status`='0' ";
                }

                $query = $baseQuery . $appendQuery;
                // deb($query);
                $raw = simple_queryall($query);
                if (!empty($raw)) {
                    foreach ($raw as $io => $each) {
                        $result[$each['id']] = $each;
                    }
                }

                //salary no jobs processing/excluding
                if (wf_CheckPost(array('cb_nosalsaryjobs'))) {
                    $salaryTasks = array();
                    $greed = new Avarice();
                    $beggar = $greed->runtime('SALARY');
                    if (!empty($beggar)) {
                        $querySalaryJobs = "SELECT `id`,`taskid` from `salary_jobs`";
                        $salaryJobsRaw = simple_queryall($querySalaryJobs);
                        if (!empty($salaryJobsRaw)) {
                            foreach ($salaryJobsRaw as $io => $each) {
                                if (!empty($each['taskid'])) {
                                    $salaryTasks[$each['taskid']] = $each['id'];
                                }
                            }
                        }

                        if (!empty($salaryTasks)) {
                            foreach ($salaryTasks as $jobTaskid => $eachJobId) {
                                if (isset($result[$jobTaskid])) {
                                    unset($result[$jobTaskid]);
                                }
                            }
                        }
                    } else {
                        show_error(__('No license key available'));
                    }
                }
            }
            return ($result);
        }

        /**
         * Renders tasks list as human readable view
         * 
         * @param array $tasksArray
         * 
         * @return string
         */
        public function renderTasks($tasksArray) {
            $result = '';
            $totalCount = 0;
            if (!empty($tasksArray)) {
                $cells = wf_TableCell(__('ID'));
                $cells.= wf_TableCell(__('Address'));
                $cells.= wf_TableCell(__('Job type'));
                $cells.= wf_TableCell(__('Phone'));
                $cells.= wf_TableCell(__('Who should do'));
                $cells.= wf_TableCell(__('Worker done'));
                $cells.= wf_TableCell(__('Target date'));
                $cells.= wf_TableCell(__('Finish date'));
                $cells.= wf_TableCell(__('Status'));
                $cells.= wf_TableCell(__('Actions'));
                $rows = wf_TableRow($cells, 'row1');

                foreach ($tasksArray as $io => $each) {
                    $cells = wf_TableCell($each['id']);
                    $cells.= wf_TableCell($each['address']);
                    $cells.= wf_TableCell(@$this->allJobtypes[$each['jobtype']]);
                    $cells.= wf_TableCell($each['phone']);
                    $cells.= wf_TableCell(@$this->allEmployee[$each['employee']]);
                    $cells.= wf_TableCell(@$this->allEmployee[$each['employeedone']]);
                    $cells.= wf_TableCell($each['startdate'] . ' ' . $each['starttime']);
                    $cells.= wf_TableCell($each['enddate']);
                    $cells.= wf_TableCell(web_bool_led($each['status']), '', '', 'sorttable_customkey="' . $each['status'] . '"');
                    $actLinks = wf_Link(self::URL_TASKVIEW . $each['id'], web_edit_icon(), false);
                    $cells.= wf_TableCell($actLinks);
                    $rows.= wf_TableRow($cells, 'row3');
                    $totalCount++;
                }

                $result = wf_TableBody($rows, '100%', 0, 'sortable');
                $result.=__('Total') . ': ' . $totalCount;
            } else {
                $messages = new UbillingMessageHelper();
                $result = $messages->getStyledMessage(__('Nothing found'), 'warning');
            }
            return ($result);
        }

    }

    $taskmanSearch = new TaskmanSearch();
    show_window(__('Tasks search'), $taskmanSearch->renderSearchForm());
    if (wf_CheckPost(array('datefrom', 'dateto'))) {
        $searchResults = $taskmanSearch->commonSearch();
        show_window(__('Search results'), $taskmanSearch->renderTasks($searchResults));
    }

    show_window('', wf_Link('?module=taskman', __('Back'), true, 'ubButton'));
} else {
    show_error(__('You cant control this module'));
}
?>