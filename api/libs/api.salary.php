<?

class Salary {

    /**
     * Available active employee as employeeid=>name
     *
     * @var array
     */
    protected $allEmployee = array();
    
    /**
     * Available active and inactive employee
     * 
     * @var array
     */
    protected $allEmployeeRaw=array();



    /**
     * Available jobtypes as jobtypeid=>name
     *
     * @var array
     */
    protected $allJobtypes = array();

    /**
     * Typical jobtypes required time in hours as jobtypeid=>time
     *
     * @var array
     */
    protected $allJobTimes = array();

    /**
     * Default jobtype pricing as jobtypeid=>price
     *
     * @var array
     */
    protected $allJobPrices = array();

    /**
     * Available jobtype units as  jobtypeid=>unit
     *
     * @var string
     */
    protected $allJobUnits = array();

    /**
     * Available employee wages, bounty and work day length
     *
     * @var string
     */
    protected $allWages = array();

    /**
     * Available unit types as unittype=>localized name
     *
     * @var array
     */
    protected $unitTypes = array();

    /**
     * All available salary jobs
     *
     * @var array
     */
    protected $allJobs = array();

    /**
     * Alredy paid jobs as array jobid=>paid data
     *
     * @var array
     */
    protected $allPaid = array();

    /**
     * All available timesheets as array date=>timesheets
     *
     * @var array
     */
    protected $allTimesheets = array();

    /**
     * Timesheets dates
     *
     * @var array
     */
    protected $allTimesheetDates = array();

    /**
     * Contains all employee appointments as employeeid=>appointment
     *
     * @var string
     */
    protected $allAppointments = array();

    const URL_ME = '?module=salary';
    const URL_TS = '?module=taskman&edittask=';
    const URL_JOBPRICES = 'jobprices=true';
    const URL_WAGES = 'employeewages=true';
    const URL_PAYROLL = 'payroll=true';
    const URL_FACONTROL = 'factorcontrol=true';
    const URL_TWJ = 'twjreport=true';
    const URL_TSHEETS = 'timesheets=true';

    public function __construct() {
        $this->setUnitTypes();
        $this->loadEmployee();
        $this->loadEmployeeRaw();
        $this->loadJobtypes();
        $this->loadJobprices();
        $this->loadWages();
        $this->loadSalaryJobs();
        $this->loadPaid();
        $this->loadTimesheets();
    }

    /**
     * Loads active employees from database
     * 
     * @return void
     */
    protected function loadEmployee() {
        $this->allEmployee = ts_GetActiveEmployee();
    }
    
     /**
     * Loads all of employees from database
     * 
     * @return void
     */
    protected function loadEmployeeRaw() {
        $this->allEmployeeRaw = ts_GetAllEmployee();
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
     * Sets default unit types
     * 
     * @return void
     */
    protected function setUnitTypes() {
        $this->unitTypes['quantity'] = __('quantity');
        $this->unitTypes['meter'] = __('meter');
        $this->unitTypes['kilometer'] = __('kilometer');
        $this->unitTypes['money'] = __('money');
        $this->unitTypes['time'] = __('time');
        $this->unitTypes['litre'] = __('litre');
        $this->unitTypes['pieces'] = __('pieces');
    }

    /**
     * Loads existing job prices from database 
     * 
     * @return void
     */
    protected function loadJobprices() {
        $query = "SELECT * from `salary_jobprices`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allJobPrices[$each['jobtypeid']] = $each['price'];
                $this->allJobUnits[$each['jobtypeid']] = $each['unit'];
                $this->allJobTimes[$each['jobtypeid']] = $each['time'];
            }
        }
    }

    /**
     * Loads existing employee wages from database 
     * 
     * @return void
     */
    protected function loadWages() {
        $query = "SELECT * from `salary_wages`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allWages[$each['employeeid']]['wage'] = $each['wage'];
                $this->allWages[$each['employeeid']]['bounty'] = $each['bounty'];
                $this->allWages[$each['employeeid']]['worktime'] = $each['worktime'];
            }
        }
    }

    /**
     * Loads paid jobs log from database into private property
     * 
     * @return void
     */
    protected function loadPaid() {
        $query = "SELECT * from `salary_paid`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allPaid[$each['jobid']] = $each;
            }
        }
    }

    /**
     * Loads all existing timesheets from database into protected property
     * 
     * @return void
     */
    protected function loadTimesheets() {
        $query = "SELECT * from `salary_timesheets` ORDER BY `id` DESC";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allTimesheets[$each['id']] = $each;
                if (isset($this->allTimesheetDates[$each['date']])) {
                    $this->allTimesheetDates[$each['date']] ++;
                } else {
                    $this->allTimesheetDates[$each['date']] = 1;
                }
            }
        }
    }

    /**
     * Loads all employee appointments from database
     * 
     * @return void
     */
    protected function loadAppointments() {
        $query = "SELECT `id`,`appointment` FROM `employee`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allAppointments[$each['id']] = $each['appointment'];
            }
        }
    }

    /**
     * Renders job price creation form
     * 
     * @return string
     */
    public function jobPricesCreateForm() {
        $result = '';
        if (!empty($this->allJobtypes)) {
            $inputs = wf_Selector('newjobtypepriceid', $this->allJobtypes, __('Job type'), '', true) . ' ';
            $inputs.= wf_Selector('newjobtypepriceunit', $this->unitTypes, __('Units'), '', true) . ' ';
            $inputs.= wf_TextInput('newjobtypeprice', __('Price'), '', true, 5) . ' ';
            $inputs.= wf_TextInput('newjobtypepricetime', __('Typical execution time') . ' (' . __('minutes') . ')', '', true, 5) . ' ';
            $inputs.= wf_Submit(__('Create'));
            $result = wf_Form('', 'POST', $inputs, 'glamour');
            $result.= wf_CleanDiv();
        }
        return ($result);
    }

    /**
     * Renders job price editing form
     * 
     * @param int $jobtypeid
     * 
     * @return string
     */
    protected function jobPricesEditForm($jobtypeid) {
        $result = '';
        if (isset($this->allJobPrices[$jobtypeid])) {
            $inputs = wf_HiddenInput('editjobtypepriceid', $jobtypeid);
            $inputs.= wf_Selector('editjobtypepriceunit', $this->unitTypes, __('Units'), $this->allJobUnits[$jobtypeid], true);
            $inputs.= wf_TextInput('editjobtypeprice', __('Price'), $this->allJobPrices[$jobtypeid], true, 5);
            $inputs.= wf_TextInput('editjobtypepricetime', __('Minutes'), $this->allJobTimes[$jobtypeid], true, 2) . ' ';
            $inputs.= wf_Submit(__('Save'));
            $result = wf_Form('', 'POST', $inputs, 'glamour');
        }
        return ($result);
    }

    /**
     * Creates job type pricing database record
     * 
     * @param int $jobtypeid
     * @param float $price
     * @param string $unit
     * @param int $time
     * 
     * @return void
     */
    public function jobPriceCreate($jobtypeid, $price, $unit, $time) {
        $jobtypeid = vf($jobtypeid, 3);
        $price = str_replace(',', '.', $price);
        $time = vf($time);
        if (!isset($this->allJobPrices[$jobtypeid])) {
            $priceF = mysql_real_escape_string($price);
            $unit = mysql_real_escape_string($unit);
            $query = "INSERT INTO `salary_jobprices` (`id`, `jobtypeid`, `price`, `unit`,`time`) VALUES (NULL ,'" . $jobtypeid . "', '" . $priceF . "', '" . $unit . "', '" . $time . "');";
            nr_query($query);
            log_register('SALARY CREATE JOBPRICE JOBID [' . $jobtypeid . '] PRICE `' . $price . '` TIME `' . $time . '`');
        } else {
            log_register('SALARY CREATE JOBPRICE FAIL EXIST JOBID [' . $jobtypeid . ']');
        }
    }

    /**
     * Renders job prices list with required controls
     * 
     * @return string
     */
    public function jobPricesRender() {
        $result = '';
        $messages = new UbillingMessageHelper();
        $cells = wf_TableCell(__('Job type'));
        $cells.= wf_TableCell(__('Units'));
        $cells.= wf_TableCell(__('Price'));
        $cells.= wf_TableCell(__('Minutes'));
        $cells.= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($this->allJobPrices)) {
            foreach ($this->allJobPrices as $jobtypeid => $eachprice) {
                $cells = wf_TableCell(@$this->allJobtypes[$jobtypeid]);
                $cells.= wf_TableCell(__($this->allJobUnits[$jobtypeid]));
                $cells.= wf_TableCell($eachprice);
                $cells.= wf_TableCell($this->allJobTimes[$jobtypeid]);
                $actLinks = wf_JSAlert('?module=salary&deletejobprice=' . $jobtypeid, web_delete_icon(), $messages->getDeleteAlert());
                $actLinks.= wf_modalAuto(web_edit_icon(), __('Edit'), $this->jobPricesEditForm($jobtypeid));
                $cells.= wf_TableCell($actLinks);
                $rows.= wf_TableRow($cells, 'row3');
            }
        }
        $result = wf_TableBody($rows, '100%', 0, 'sortable');
        return ($result);
    }

    /**
     * Deletes jobprice by jobtype id from database
     * 
     * @param int $jobtypeid
     * 
     * @return void
     */
    public function jobPriceDelete($jobtypeid) {
        $jobtypeid = vf($jobtypeid, 3);
        $query = "DELETE from `salary_jobprices` WHERE `jobtypeid`='" . $jobtypeid . "';";
        nr_query($query);
        log_register('SALARY DELETE JOBPRICE JOBID [' . $jobtypeid . ']');
    }

    /**
     * Edits existing job price in database
     * 
     * @param int $jobtypeid
     * 
     * @return void
     */
    public function jobPriceEdit($jobtypeid) {
        $jobtypeid = vf($jobtypeid, 3);
        $price = str_replace(',', '.', $_POST['editjobtypeprice']);
        $time = vf($_POST['editjobtypepricetime'], 3);
        $where = " WHERE `jobtypeid`='" . $jobtypeid . "';";
        simple_update_field('salary_jobprices', 'price', $price, $where);
        simple_update_field('salary_jobprices', 'unit', $_POST['editjobtypepriceunit'], $where);
        simple_update_field('salary_jobprices', 'time', $time, $where);
        log_register('SALARY EDIT JOBPRICE JOBID [' . $jobtypeid . '] PRICE `' . $_POST['editjobtypeprice'] . '` UNIT `' . $_POST['editjobtypepriceunit'] . '` TIME `' . $time . '`');
    }

    /**
     * Renders controls panel
     * 
     * @return string
     */
    public function renderControls() {
        $result = '';
        $result.= wf_Link(self::URL_ME . '&' . self::URL_PAYROLL, wf_img('skins/ukv/report.png') . ' ' . __('Payroll'), false, 'ubButton');
        $result.= wf_Link(self::URL_ME . '&' . self::URL_TSHEETS, wf_img('skins/icon_calendar.gif') . ' ' . __('Timesheet'), false, 'ubButton');
        $result.= wf_Link(self::URL_ME . '&' . self::URL_FACONTROL, wf_img('skins/factorcontrol.png') . ' ' . __('Factor control'), false, 'ubButton');
        $result.= wf_Link(self::URL_ME . '&' . self::URL_TWJ, wf_img('skins/question.png') . ' ' . __('Tasks without jobs'), false, 'ubButton');

        $directoriesControls = wf_Link(self::URL_ME . '&' . self::URL_JOBPRICES, wf_img('skins/shovel.png') . ' ' . __('Job types'), false, 'ubButton');
        $directoriesControls.= wf_Link(self::URL_ME . '&' . self::URL_WAGES, wf_img('skins/icon_user.gif') . ' ' . __('Employee wages'), false, 'ubButton');
        $result.= wf_modalAuto(web_icon_extended().' '.__('Directories'), __('Directories'), $directoriesControls, 'ubButton');


        return ($result);
    }

    /**
     * Returns job for task creation form
     * 
     * @param int $taskid
     * @return string
     */
    public function taskJobCreateForm($taskid) {
        $taskid = vf($taskid, 3);
        $result = '';
        $jobtypes = array();

        if (cfr('SALARYTASKSVIEW')) {
            $result.=$this->renderTaskJobs($taskid);
        }

        if (cfr('SALARYTASKS')) {
            if (!empty($this->allJobPrices)) {
                if (!empty($this->allJobtypes)) {
                    foreach ($this->allJobtypes as $io => $each) {
                        if (isset($this->allJobUnits[$io])) {
                            $jobUnit = __($this->allJobUnits[$io]);
                        } else {
                            $jobUnit = '?';
                        }
                        $jobtypes[$io] = $each . ' (' . $jobUnit . ')';
                    }
                }


                $inputs = zb_JSHider();
                $inputs.= wf_HiddenInput('newsalarytaskid', $taskid);
                $inputs.= wf_Selector('newsalaryemployeeid', $this->allEmployee, __('Worker'), '', true);
                $inputs.= wf_Selector('newsalaryjobtypeid', $jobtypes, __('Job type'), '', true);
                $inputs.= wf_TextInput('newsalaryfactor', __('Factor'), '0', true, 4);
                $inputs.=wf_tag('input', false, '', 'type="checkbox" id="overpricebox" name="overpricebox" onclick="showhide(\'overpricecontainer\');" ');
                $inputs.= wf_tag('label', false, '', 'for="overpricebox"') . __('Price override') . wf_tag('label', true);
                $inputs.= wf_tag('span', false, '', 'id="overpricecontainer" style="display:none;"') . ' ';
                $inputs.= wf_TextInput('newsalaryoverprice', '', '', false, 4);
                $inputs.= wf_tag('span', true);
                $inputs.= wf_tag('br');
                $inputs.= wf_TextInput('newsalarynotes', __('Notes'), '', true, 25);
                $inputs.= wf_Submit(__('Save'));
                $result.= wf_modalAuto(wf_img('skins/icon_ok.gif') . ' ' . __('Create new job'), __('Create new job'), wf_Form('', 'POST', $inputs, 'glamour'), 'ubButton');
                $result.=wf_CleanDiv();
            }
        }
        return ($result);
    }

    /**
     * Loads all available salary jobs from database
     * 
     * @return void
     */
    protected function loadSalaryJobs() {
        $query = "SELECT * from `salary_jobs` ORDER BY `id` ASC";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allJobs[$each['id']] = $each;
            }
        }
    }

    /**
     * Creates new salary job for some task
     * 
     * @param int $taskid
     * @param int $employeeid
     * @param int $jobtypeid
     * @param float $factor
     * @param float $overprice
     * @param string $notes
     * 
     * @return void
     */
    public function createSalaryJob($taskid, $employeeid, $jobtypeid, $factor, $overprice, $notes) {
        $taskid = vf($taskid, 3);
        $employeeid = vf($employeeid, 3);
        $jobtypeid = vf($jobtypeid, 3);
        $factor = str_replace(',', '.', $factor);
        $overprice = str_replace(',', '.', $overprice);
        $notes = mysql_real_escape_string($notes);
        $overprice = mysql_real_escape_string($overprice);
        $date = curdatetime();
        $state = 0;
        $query = "INSERT INTO `salary_jobs` (`id`, `date`, `state` ,`taskid`, `employeeid`, `jobtypeid`, `factor`, `overprice`, `note`)"
                . " VALUES (NULL, '" . $date . "', '" . $state . "' ,'" . $taskid . "', '" . $employeeid . "', '" . $jobtypeid . "', '" . $factor . "', '" . $overprice . "', '" . $notes . "');";

        nr_query($query);
        $newId = simple_get_lastid('salary_jobs');
        log_register('SALARY CREATE JOB [' . $newId . '] TASK [' . $taskid . '] EMPLOYEE [' . $employeeid . '] JOBTYPE [' . $jobtypeid . '] FACTOR `' . $factor . '` OVERPRICE `' . $overprice . '`');
    }

    /**
     * Filters available jobs for some task
     * 
     * @param int $taskid
     * @return array
     */
    protected function filterTaskJobs($taskid) {
        $taskid = vf($taskid, 3);
        $result = array();
        if (!empty($this->allJobs)) {
            foreach ($this->allJobs as $io => $each) {
                if ($each['taskid'] == $taskid) {
                    $result[$each['id']] = $each;
                }
            }
        }
        return ($result);
    }

    /**
     * Filters available jobs by date
     * 
     * @param string $date
     * @return array
     */
    protected function jobsFilterDate($date) {
        $result = array();
        if (!empty($this->allJobs)) {
            foreach ($this->allJobs as $io => $each) {
                if (ispos($each['date'], $date)) {
                    $result[$each['id']] = $each;
                }
            }
        }
        return ($result);
    }

    /**
     * Returns job salary by its factor/overprice
     * 
     * @param int $jobid
     * @return float
     */
    protected function getJobPrice($jobid) {
        $jobid = vf($jobid, 3);
        $result = 0;
        if (isset($this->allJobs[$jobid])) {
            if (empty($this->allJobs[$jobid]['overprice'])) {
                if (isset($this->allJobPrices[$this->allJobs[$jobid]['jobtypeid']])) {
                    $result = $this->allJobPrices[$this->allJobs[$jobid]['jobtypeid']] * $this->allJobs[$jobid]['factor'];
                }
            } else {
                $result = $this->allJobs[$jobid]['overprice'];
            }
        }
        return ($result);
    }

    /**
     * Returns existing job editing form
     * 
     * @param int $jobid
     * @return string
     */
    protected function jobEditForm($jobid) {
        $jobid = vf($jobid, 3);
        $result = '';
        if (isset($this->allJobs[$jobid])) {
            $inputs = wf_HiddenInput('editsalaryjobid', $jobid);
            $inputs.= wf_Selector('editsalaryemployeeid', $this->allEmployee, __('Worker'), $this->allJobs[$jobid]['employeeid'], true);
            $inputs.= wf_Selector('editsalaryjobtypeid', $this->allJobtypes, __('Job type'), $this->allJobs[$jobid]['jobtypeid'], true);
            $inputs.= wf_TextInput('editsalaryfactor', __('Factor'), $this->allJobs[$jobid]['factor'], true, 4);
            $inputs.= wf_TextInput('editsalaryoverprice', __('Price override'), $this->allJobs[$jobid]['overprice'], true, 4);
            $inputs.= wf_TextInput('editsalarynotes', __('Notes'), $this->allJobs[$jobid]['note'], true, 25);
            $inputs.= wf_Submit(__('Save'));
            $result = wf_Form('', 'POST', $inputs, 'glamour');
        } else {
            $result = __('Strange exeption') . ': NOT_EXISTING_JOBID';
        }
        return ($result);
    }

    /**
     * Edits some existing job in database
     * 
     * @param int $jobid
     * @param int $employeeid
     * @param int $jobtypeid
     * @param float $factor
     * @param float $overprice
     * @param string $notes
     *
     * @return void
     */
    public function jobEdit($jobid, $employeeid, $jobtypeid, $factor, $overprice, $notes) {
        $jobid = vf($jobid, 3);
        $factor = str_replace(',', '.', $factor);
        $overprice = str_replace(',', '.', $overprice);
        if (isset($this->allJobs[$jobid])) {
            $where = " WHERE `id`='" . $jobid . "';";
            simple_update_field('salary_jobs', 'employeeid', $employeeid, $where);
            simple_update_field('salary_jobs', 'jobtypeid', $jobtypeid, $where);
            simple_update_field('salary_jobs', 'factor', $factor, $where);
            simple_update_field('salary_jobs', 'overprice', $overprice, $where);
            simple_update_field('salary_jobs', 'note', $notes, $where);
            log_register('SALARY EDIT JOB [' . $jobid . '] EMPLOYEE [' . $employeeid . '] JOBTYPE [' . $jobtypeid . '] FACTOR `' . $factor . '` OVERPRICE `' . $overprice . '`');
        }
    }

    /**
     * Renders jobs list for some task
     * 
     * @param int $taskid
     * @return string
     */
    protected function renderTaskJobs($taskid) {
        $taskid = vf($taskid, 3);
        $result = '';
        $totalSumm = 0;
        $messages = new UbillingMessageHelper();
        $all = $this->filterTaskJobs($taskid);

        $cells = wf_TableCell(__('Date'));
        $cells.= wf_TableCell(__('Paid'));
        $cells.= wf_TableCell(__('Worker'));
        $cells.= wf_TableCell(__('Job type'));
        $cells.= wf_TableCell(__('Factor'));
        $cells.= wf_TableCell(__('Price override'));
        $cells.= wf_TableCell(__('Notes'));
        $cells.= wf_TableCell(__('Cash'));
        $cells.= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($all)) {
            foreach ($all as $io => $each) {
                if (isset($this->allJobUnits[$each['jobtypeid']])) {
                    $unit = $this->unitTypes[$this->allJobUnits[$each['jobtypeid']]];
                } else {
                    $unit = __('No');
                }
                $cells = wf_TableCell($each['date']);
                $cells.= wf_TableCell($this->renderPaidDataLed($each['id']));
                $cells.= wf_TableCell(@$this->allEmployee[$each['employeeid']]);
                $cells.= wf_TableCell(@$this->allJobtypes[$each['jobtypeid']]);
                $cells.= wf_TableCell($each['factor'] . ' / ' . $unit);
                $cells.= wf_TableCell($each['overprice']);
                $cells.= wf_TableCell($each['note']);
                $jobPrice = $this->getJobPrice($each['id']);
                $cells.= wf_TableCell($jobPrice);
                if (cfr('SALARYTASKS')) {
                    $actLinks = wf_JSAlert(self::URL_TS . $taskid . '&deletejobid=' . $each['id'], web_delete_icon(), $messages->getDeleteAlert());
                    $actLinks.= wf_modalAuto(web_edit_icon(), __('Edit'), $this->jobEditForm($each['id']));
                } else {
                    $actLinks = '';
                }
                $cells.= wf_TableCell($actLinks);

                $rows.= wf_TableRow($cells, 'row3');
                $totalSumm = $totalSumm + $jobPrice;
            }

            $cells = wf_TableCell(__('Total'));
            $cells.= wf_TableCell('');
            $cells.= wf_TableCell('');
            $cells.= wf_TableCell('');
            $cells.= wf_TableCell('');
            $cells.= wf_TableCell('');
            $cells.= wf_TableCell('');
            $cells.= wf_TableCell($totalSumm);
            $cells.= wf_TableCell('');
            $rows.= wf_TableRow($cells, 'row2');
        }

        $result = wf_TableBody($rows, '100%', 0, '');
        return ($result);
    }

    /**
     * Deletes existing job from database by ID
     * 
     * @param int $jobid
     * 
     * @return void
     */
    public function deleteJob($jobid) {
        $jobid = vf($jobid, 3);
        if (isset($this->allJobs[$jobid])) {
            $jobData = $this->allJobs[$jobid];
            $query = "DELETE from `salary_jobs` WHERE `id`='" . $jobid . "';";
            nr_query($query);
            log_register('SALARY DELETE JOB TASK [' . $jobData['taskid'] . '] EMPLOYEE [' . $jobData['employeeid'] . '] JOBTYPE [' . $jobData['jobtypeid'] . '] FACTOR `' . $jobData['factor'] . '` OVERPRICE `' . $jobData['overprice'] . '`');
        }
    }

    /**
      All we do is run in circles
      We’ll run until my voice will disappear
      Until my sound will break the silence
      And in the world will be no violence
     */

    /**
     * Creates new employee wage record
     * 
     * @param int $employeeid
     * @param float $wage
     * @param float $bounty
     * @param int $worktime
     * 
     * @return void
     */
    public function employeeWageCreate($employeeid, $wage, $bounty, $worktime) {
        $employeeid = vf($employeeid, 3);
        $worktime = vf($worktime);
        if (!isset($this->allWages[$employeeid])) {
            $wage = str_replace(',', '.', $wage);
            $bounty = str_replace(',', '.', $bounty);
            $wageF = mysql_real_escape_string($wage);
            $bountyF = mysql_real_escape_string($bounty);
            $query = "INSERT INTO `salary_wages` (`id`, `employeeid`, `wage`, `bounty`,`worktime`) VALUES (NULL, '" . $employeeid . "', '" . $wage . "', '" . $bounty . "','" . $worktime . "');";
            nr_query($query);
            log_register('SALARY CREATE WAGE EMPLOYEE [' . $employeeid . '] WAGE `' . $wageF . '` BOUNTY `' . $bountyF . '` WORKTIME `' . $worktime . '`');
        } else {
            log_register('SALARY CREATE WAGE FAIL EXISTS EMPLOYEE [' . $employeeid . ']');
        }
    }

    /**
     * Deletes existing employee wage from database
     * 
     * @param int $employeeid
     * 
     * @return void
     */
    public function employeeWageDelete($employeeid) {
        $employeeid = vf($employeeid, 3);
        $query = "DELETE from `salary_wages` WHERE `employeeid`='" . $employeeid . "';";
        nr_query($query);
        log_register('SALARY DELETE WAGE EMPLOYEE [' . $employeeid . ']');
    }

    /**
     * Returns employee wage creation form
     * 
     * @return string
     */
    public function employeeWageCreateForm() {
        $result = '';
        if (!empty($this->allEmployee)) {
            $inputs = wf_Selector('newemployeewageemployeeid', $this->allEmployee, __('Worker'), '', true) . ' ';
            $inputs.= wf_TextInput('newemployeewage', __('Wage'), '', true, 5) . ' ';
            $inputs.= wf_TextInput('newemployeewagebounty', __('Bounty'), '', true, 5) . ' ';
            $inputs.= wf_TextInput('newemployeewageworktime', __('Work hours'), '', true, 5);
            $inputs.=wf_Submit(__('Create'));
            $result = wf_Form('', 'POST', $inputs, 'glamour');
            $result.= wf_CleanDiv();
        }
        return ($result);
    }

    /**
     * Returns existing employee wage editing form
     * 
     * @param int $employeeid
     * @return string
     */
    protected function employeeWageEditForm($employeeid) {
        $employeeid = vf($employeeid, 3);
        $result = '';
        if (isset($this->allWages[$employeeid])) {
            $inputs = wf_HiddenInput('editemployeewageemployeeid', $employeeid);
            $inputs.= wf_TextInput('editemployeewage', __('Wage'), $this->allWages[$employeeid]['wage'], true, 5) . ' ';
            $inputs.= wf_TextInput('editemployeewagebounty', __('Bounty'), $this->allWages[$employeeid]['bounty'], true, 5) . ' ';
            $inputs.= wf_TextInput('editemployeewageworktime', __('Work hours'), $this->allWages[$employeeid]['worktime'], true, 5) . ' ';
            $inputs.=wf_Submit(__('Save'));
            $result = wf_Form('', 'POST', $inputs, 'glamour');
            $result.= wf_CleanDiv();
        } else {
            $result = __('Strange exeption') . ': NOT_EXISTING_EMPLOYEID';
        }
        return ($result);
    }

    /**
     * Changes existing employee wage in database
     * 
     * @param int $employeeid
     * @param float $wage
     * @param float $bounty
     * @param int $worktime
     * 
     * @return void
     */
    public function employeeWageEdit($employeeid, $wage, $bounty, $worktime) {
        $employeeid = vf($employeeid, 3);
        $wage = str_replace(',', '.', $wage);
        $bounty = str_replace(',', '.', $bounty);
        $worktime = vf($worktime, 3);
        $where = " WHERE `employeeid`='" . $employeeid . "'";
        simple_update_field('salary_wages', 'wage', $wage, $where);
        simple_update_field('salary_wages', 'bounty', $bounty, $where);
        simple_update_field('salary_wages', 'worktime', $worktime, $where);
        log_register('SALARY EDIT WAGE EMPLOYEE [' . $employeeid . '] WAGE `' . $wage . '` BOUNTY `' . $bounty . '` WORKTIME `' . $worktime . '`');
    }

    /**
     * Renders available employee wages list with some controls
     * 
     * @return string
     */
    public function employeeWagesRender() {
        $result = '';
        $messages = new UbillingMessageHelper();

        $cells = wf_TableCell(__('Employee'));
        $cells.= wf_TableCell(__('Wage'));
        $cells.= wf_TableCell(__('Bounty'));
        $cells.= wf_TableCell(__('Work hours'));
        $cells.= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($this->allWages)) {
            foreach ($this->allWages as $io => $each) {
                $rowClass= (isset($this->allEmployee[$io])) ? 'row3' : 'sigdeleteduser'  ;
                $cells = wf_TableCell(@$this->allEmployeeRaw[$io]);
                $cells.= wf_TableCell($this->allWages[$io]['wage']);
                $cells.= wf_TableCell($this->allWages[$io]['bounty']);
                $cells.= wf_TableCell($this->allWages[$io]['worktime']);
                $actlinks = wf_JSAlertStyled('?module=salary&employeewages=true&deletewage=' . $io, web_delete_icon(), $messages->getDeleteAlert());
                $actlinks.= wf_modalAuto(web_edit_icon(), __('Edit'), $this->employeeWageEditForm($io));
                $cells.= wf_TableCell($actlinks);
                $rows.= wf_TableRow($cells, $rowClass);
            }
        }

        $result = wf_TableBody($rows, '100%', 0, 'sortable');
        return ($result);
    }

    /**
     * Renders payroll report search form
     * 
     * @return string
     */
    public function payrollRenderSearchForm() {
        $result = '';
        $empParams = array('' => __('Any'));
        if (!empty($this->allEmployee)) {
            foreach ($this->allEmployee as $io => $each) {
                $empParams[$io] = $each;
            }
        }
        $inputs = wf_DatePickerPreset('prdatefrom', curdate(), true) . ' ';
        $inputs.= wf_DatePickerPreset('prdateto', curdate(), true) . ' ';
        $inputs.= wf_Selector('premployeeid', $empParams, __('Worker'), '', false);
        $inputs.= wf_Submit(__('Show'));
        $result = wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Renders payroll report search results
     * 
     * @param string $datefrom
     * @param string $dateto
     * @param int $employeeid
     * @return string
     */
    public function payrollRenderSearch($datefrom, $dateto, $employeeid) {
        $datefrom = mysql_real_escape_string($datefrom);
        $dateto = mysql_real_escape_string($dateto);
        $employeeid = vf($employeeid, 3);
        $allTasks = ts_GetAllTasks();

        $chartData = array();
        $chartDataCash = array();

        $result = '';
        $totalSum = 0;
        $payedSum = 0;
        $jobCount = 0;

        $query = "SELECT * from `salary_jobs` WHERE CAST(`date` AS DATE) BETWEEN '" . $datefrom . "' AND  '" . $dateto . "' AND `employeeid`='" . $employeeid . "';";
        $all = simple_queryall($query);

        $cells = wf_TableCell(__('Date'));
        $cells.= wf_TableCell(__('Task'));
        $cells.= wf_TableCell(__('Job type'));
        $cells.= wf_TableCell(__('Factor'));
        $cells.= wf_TableCell(__('Price override'));
        $cells.= wf_TableCell(__('Notes'));
        $cells.= wf_TableCell(__('Paid'));
        $cells.= wf_TableCell(__('Money'));
        $cells.= wf_TableCell(__(''));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $jobName = @$this->allJobtypes[$each['jobtypeid']];
                $jobPrice = $this->getJobPrice($each['id']);

                if (!empty($jobName)) {
                    if (isset($chartData[$jobName])) {
                        $chartData[$jobName] ++;
                        $chartDataCash[$jobName] = $chartDataCash[$jobName] + $jobPrice;
                    } else {
                        $chartData[$jobName] = 1;
                        $chartDataCash[$jobName] = $jobPrice;
                    }
                }

                if (isset($this->allJobUnits[$each['jobtypeid']])) {
                    $unit = $this->unitTypes[$this->allJobUnits[$each['jobtypeid']]];
                } else {
                    $unit = __('No');
                }


                $cells = wf_TableCell($each['date']);
                $cells.= wf_TableCell(wf_Link(self::URL_TS . $each['taskid'], $each['taskid']) . ' ' . @$allTasks[$each['taskid']]['address']);
                $cells.= wf_TableCell($jobName);
                $cells.= wf_TableCell($each['factor'] . ' / ' . $unit);
                $cells.= wf_TableCell($each['overprice']);
                $cells.= wf_TableCell($each['note']);
                $cells.= wf_TableCell($this->renderPaidDataLed($each['id']));

                $cells.= wf_TableCell($jobPrice);
                if (!$each['state']) {
                    $actControls = wf_CheckInput('_prstatecheck[' . $each['id'] . ']', '', true, false);
                } else {
                    $actControls = '';
                }
                $cells.= wf_TableCell($actControls);
                $rows.= wf_TableRow($cells, 'row3');

                if ($each['state'] == 0) {
                    $totalSum = $totalSum + $jobPrice;
                    $jobCount++;
                } else {
                    $payedSum = $payedSum + $jobPrice;
                }
            }
        }

        $result = wf_TableBody($rows, '100%', 0, '');
        $result.= wf_HiddenInput('prstateprocessing', 'true');
        if ($jobCount > 0) {
            $result.= wf_Submit(__('Processing')) . wf_delimiter();
        }

        $result = wf_Form('', 'POST', $result, '');

        $result.= __('Total') . ' ' . __('money') . ': ' . $totalSum . wf_tag('br');
        $result.= __('Paid') . ' ' . __('money') . ': ' . $payedSum;

        if (!empty($chartData)) {
            $result.= wf_CleanDiv();

            $chartOpts = "chartArea: {  width: '90%', height: '90%' }, legend : {position: 'right'}, ";
            $chartCells = wf_TableCell(wf_gcharts3DPie($chartData, __('Job types'), '400px', '400px', $chartOpts));
            $chartCells.= wf_TableCell(wf_gcharts3DPie($chartDataCash, __('Money'), '400px', '400px', $chartOpts));
            $chartRows = wf_TableRow($chartCells);
            $result.= wf_TableBody($chartRows, '100%', 0, '');
        }
        return ($result);
    }

    /**
     * Filters available timesheets by date range
     * 
     * @param string $datefrom
     * @param string $dateto
     * 
     * @return array
     */
    protected function timesheetFilterDateRange($datefrom, $dateto) {
        $result = array();
        $datefrom = strtotime($datefrom);
        $dateto = strtotime($dateto);
        if (!empty($this->allTimesheets)) {
            foreach ($this->allTimesheets as $io => $each) {
                $timesheetDate = strtotime($each['date']);
                if (($timesheetDate >= $datefrom) AND ( $timesheetDate <= $dateto)) {
                    $result[$each['id']] = $each;
                }
            }
        }
        return ($result);
    }

    /**
     * Renders payroll report search results for all employee
     * 
     * @param string $datefrom
     * @param string $dateto
     * @return string
     */
    public function payrollRenderSearchDate($datefrom, $dateto) {
        $datefrom = mysql_real_escape_string($datefrom);
        $dateto = mysql_real_escape_string($dateto);

        $result = '';
        $totalSum = 0;
        $totalPayedSum = 0;
        $totalWage = 0;
        $totalBounty = 0;
        $totalWorkTime = 0;
        $jobCount = 0;
        $jobsTmp = array();
        $employeeCharts = array();
        $employeeChartsMoney = array();
        $perEmployeeTimesheets = array();

        $rangeTimesheets = $this->timesheetFilterDateRange($datefrom, $dateto);

        if (!empty($rangeTimesheets)) {
            foreach ($rangeTimesheets as $io => $each) {
                if (isset($perEmployeeTimesheets[$each['employeeid']])) {
                    $perEmployeeTimesheets[$each['employeeid']]+=$each['hours'];
                } else {
                    $perEmployeeTimesheets[$each['employeeid']] = $each['hours'];
                }
            }
        }


        $query = "SELECT * from `salary_jobs` WHERE CAST(`date` AS DATE) BETWEEN '" . $datefrom . "' AND  '" . $dateto . "';";
        $all = simple_queryall($query);



        //jobs preprocessing
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $jobPrice = $this->getJobPrice($each['id']);
                $jobTime = (isset($this->allJobTimes[$each['jobtypeid']])) ? $this->allJobTimes[$each['jobtypeid']] * $each['factor'] : 0;
                if (!isset($jobsTmp[$each['employeeid']])) {
                    $payedSum = ($each['state']) ? $jobPrice : 0;
                    $jobsTmp[$each['employeeid']]['count'] = 1;
                    $jobsTmp[$each['employeeid']]['sum'] = $jobPrice;
                    $jobsTmp[$each['employeeid']]['payed'] = $payedSum;
                    $jobsTmp[$each['employeeid']]['time'] = $jobTime;
                } else {
                    $payedSum = ($each['state']) ? $jobPrice : 0;
                    $jobsTmp[$each['employeeid']]['count'] ++;
                    $jobsTmp[$each['employeeid']]['sum']+=$jobPrice;
                    $jobsTmp[$each['employeeid']]['payed']+=$payedSum;
                    $jobsTmp[$each['employeeid']]['time']+=$jobTime;
                }
                $totalPayedSum+=$payedSum;
                $totalSum+=$jobPrice;
            }
        }

        $cells = wf_TableCell(__('Worker'));
        $cells.= wf_TableCell(__('Wage'));
        $cells.= wf_TableCell(__('Bounty'));
        $cells.= wf_TableCell(__('Work hours'));
        $cells.= wf_TableCell(__('Jobs'));
        $cells.= wf_TableCell(__('Spent time') . ' (' . __('hours') . ')');
        $cells.= wf_TableCell(__('Earned money'));
        $cells.= wf_TableCell(__('Paid'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($this->allEmployee)) {
            foreach ($this->allEmployee as $io => $each) {
                $cells = wf_TableCell($each);
                $wage = (isset($this->allWages[$io]['wage'])) ? $this->allWages[$io]['wage'] : __('No');
                $bounty = (isset($this->allWages[$io]['bounty'])) ? $this->allWages[$io]['bounty'] : __('No');
                $worktime = (isset($this->allWages[$io]['worktime'])) ? $this->allWages[$io]['worktime'] : __('No');
                $workerJobsData = (isset($jobsTmp[$io])) ? $jobsTmp[$io] : array('count' => 0, 'sum' => 0, 'payed' => 0, 'time' => 0);

                $cells.= wf_TableCell($wage);
                $cells.= wf_TableCell($bounty);
                $cells.= wf_TableCell(@$perEmployeeTimesheets[$io]);
                $cells.= wf_TableCell($workerJobsData['count']);
                $cells.= wf_TableCell(round(($workerJobsData['time'] / 60), 2));
                $cells.= wf_TableCell($workerJobsData['sum']);
                $cells.= wf_TableCell($workerJobsData['payed']);
                $rows.= wf_TableRow($cells, 'row3');

                $totalWage+=$wage;
                $totalBounty+=$bounty;
                $totalWorkTime+=$workerJobsData['time'];
                $jobCount+=$workerJobsData['count'];
                $employeeCharts[$each] = $workerJobsData['count'];
                $employeeChartsMoney[$each] = $workerJobsData['sum'];
            }
        }

        $cells = wf_TableCell(__('Total'));
        $cells.= wf_TableCell($totalWage);
        $cells.= wf_TableCell($totalBounty);
        $cells.= wf_TableCell('');
        $cells.= wf_TableCell($jobCount);
        $cells.= wf_TableCell(round(($totalWorkTime / 60), 2));
        $cells.= wf_TableCell($totalSum);
        $cells.= wf_TableCell($totalPayedSum);
        $rows.= wf_TableRow($cells, 'row2');

        $result = wf_TableBody($rows, '100%', 0, '');
        $result.= wf_delimiter();
        //charts
        $chartOpts = "chartArea: {  width: '90%', height: '90%' }, legend : {position: 'right'},";
        ;
        $sumCharts = array(__('Earned money') => $totalSum - $totalPayedSum, __('Paid') => $totalPayedSum);

        $cells = wf_TableCell(wf_gcharts3DPie($sumCharts, __('Money'), '400px', '400px', $chartOpts));
        $cells.= wf_TableCell(wf_gcharts3DPie($employeeChartsMoney, __('Money') . ' / ' . __('Worker'), '400px', '400px', $chartOpts));
        $rows = wf_TableRow($cells);
        $cells = wf_TableCell(wf_gcharts3DPie($employeeCharts, __('Jobs'), '400px', '400px', $chartOpts));
        $cells.= wf_TableCell('');
        $rows.= wf_TableRow($cells);
        $result.= wf_TableBody($rows, '100%', 0, '');

        return ($result);
    }

    /**
     * Renders available tasks list as human-readable table
     * 
     * @param array $taskArr
     * 
     * @return string
     */
    protected function renderJobList($taskArr) {
        $result = '';
        $totalSum = 0;
        $payedSum = 0;

        $cells = wf_TableCell(__('Date'));
        $cells.= wf_TableCell(__('Task'));
        $cells.= wf_TableCell(__('Job type'));
        $cells.= wf_TableCell(__('Factor'));
        $cells.= wf_TableCell(__('Price override'));
        $cells.= wf_TableCell(__('Notes'));
        $cells.= wf_TableCell(__('Paid'));
        $cells.= wf_TableCell(__('Money'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($taskArr)) {
            foreach ($taskArr as $io => $each) {
                $jobData = $this->allJobs[$io];
                if (isset($this->allJobUnits[$jobData['jobtypeid']])) {
                    $unit = $this->unitTypes[$this->allJobUnits[$jobData['jobtypeid']]];
                } else {
                    $unit = __('No');
                }
                $cells = wf_TableCell($jobData['date']);
                $cells.= wf_TableCell($jobData['taskid']);
                $cells.= wf_TableCell(@$this->allJobtypes[$jobData['jobtypeid']]);
                $cells.= wf_TableCell($jobData['factor'] . ' / ' . $unit);
                $cells.= wf_TableCell($jobData['overprice']);
                $cells.= wf_TableCell($jobData['note']);
                $jobPrice = $this->getJobPrice($jobData['id']);
                $cells.= wf_TableCell(web_bool_led($jobData['state']));
                $cells.= wf_TableCell($jobPrice);
                $rows.= wf_TableRow($cells, 'row3');
                if (!$jobData['state']) {
                    $totalSum = $totalSum + $jobPrice;
                } else {
                    $payedSum = $payedSum + $jobPrice;
                }
            }
        }
        $result = wf_TableBody($rows, '100%', 0, 'sortable');
        $result.= __('Total') . ' ' . __('money') . ': ' . $totalSum . wf_tag('br');
        $result.= __('Processed') . ' ' . __('money') . ': ' . $payedSum;

        return ($result);
    }

    /**
     * Performs job states processing agreement form
     * 
     * @return string
     */
    public function payrollStateProcessingForm() {
        $result = '';
        $result.=wf_HiddenInput('prstateprocessingconfirmed', 'true');
        $tmpArr = array();
        if (wf_CheckPost(array('_prstatecheck'))) {
            if (!empty($_POST['_prstatecheck'])) {
                $checksRaw = $_POST['_prstatecheck'];

                foreach ($checksRaw as $io => $each) {
                    $tmpArr[$io] = $each;
                    $result.= wf_HiddenInput('_prstatecheck[' . $io . ']', 'on');
                }
                $result.= $this->renderJobList($tmpArr);
                $result.= wf_delimiter();
                $result.= wf_Submit(__('Payment confirmation'));
                $result = wf_Form('', 'POST', $result, '');
            }
        }

        return ($result);
    }

    /**
     * Performs job states processing
     * 
     * @return void
     */
    public function payrollStateProcessing() {
        $jobCount = 0;
        if (wf_CheckPost(array('_prstatecheck'))) {
            $checksRaw = $_POST['_prstatecheck'];
            if (!empty($checksRaw)) {
                foreach ($checksRaw as $io => $each) {
                    $jobId = vf($io, 3);
                    simple_update_field('salary_jobs', 'state', '1', " WHERE `id`='" . $jobId . "';");
                    $this->pushPaid($jobId);
                    $jobCount++;
                }

                show_success(__('Job payment processing finished'));
                log_register('SALARY JOBS PROCESSED `' . $jobCount . '`');
            } else {
                log_register('SALARY JOBS PROCESSING FAIL EMPTY_JOBIDS');
            }
        }
    }

    /**
     * Returns existing employee name
     * 
     * @param int $employeeid
     * @return string
     */
    public function getEmployeeName($employeeid) {
        $result = '';
        if (isset($this->allEmployee[$employeeid])) {
            $result = $this->allEmployee[$employeeid];
        }
        return ($result);
    }

    /**
     * Renders factor control search form :P
     * 
     * @return string
     */
    public function facontrolRenderSearchForm() {
        $result = '';
        if (!empty($this->allJobtypes)) {
            $inputs = wf_Selector('facontroljobtypeid', $this->allJobtypes, __('Job type'), '', false);
            $inputs.= wf_TextInput('facontrolmaxfactor', '> ' . __('Factor'), '', false, '4');
            $inputs.= wf_Submit(__('Show'));
            $result = wf_Form('', 'POST', $inputs, 'glamour');
        }
        return ($result);
    }

    /**
     * Renders factor control report search results
     * 
     * @param int $jobtypeid
     * @param float $factor
     * 
     * @return string
     */
    public function facontrolRenderSearch($jobtypeid, $factor) {
        $result = '';
        $jobtypeid = vf($jobtypeid, 3);
        $messages = new UbillingMessageHelper();

        $tmpArr = array();
        $allTasks = ts_GetAllTasks();

        if (!empty($this->allJobs)) {
            foreach ($this->allJobs as $io => $each) {
                if ($jobtypeid == $each['jobtypeid']) {
                    if (isset($tmpArr[$each['taskid']])) {
                        $tmpArr[$each['taskid']]+=$each['factor'];
                    } else {
                        $tmpArr[$each['taskid']] = $each['factor'];
                    }
                }
            }
        }

        if (!empty($tmpArr)) {
            if (isset($this->allJobUnits[$jobtypeid])) {
                $unit = $this->unitTypes[$this->allJobUnits[$jobtypeid]];
            } else {
                $unit = __('No');
            }
            $cells = wf_TableCell(__('Task'));
            $cells.= wf_TableCell(__('Address'));
            $cells.= wf_TableCell(__('Target date'));
            $cells.= wf_TableCell(__('Job type'));
            $cells.= wf_TableCell(__('Who should do'));
            $cells.= wf_TableCell(__('Factor') . ' (' . $unit . ')');
            $rows = wf_TableRow($cells, 'row1');


            foreach ($tmpArr as $taskid => $factorOverflow) {
                if ($factorOverflow > $factor) {
                    $cells = wf_TableCell(wf_Link(self::URL_TS . $taskid, $taskid));
                    $cells.= wf_TableCell(@$allTasks[$taskid]['address']);
                    $cells.= wf_TableCell(@$allTasks[$taskid]['startdate']);
                    $cells.= wf_TableCell(@$this->allJobtypes[$jobtypeid]);
                    $cells.= wf_TableCell(@$this->allEmployeeRaw[$allTasks[$taskid]['employee']]);
                    $cells.= wf_TableCell($factorOverflow);
                    $rows.= wf_TableRow($cells, 'row3');
                }
            }

            $result.=wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result = $messages->getStyledMessage(__('Nothing found'), 'info');
        }



        return ($result);
    }

    /**
     * Renders tasks without jobs report search form
     * 
     * @return string
     */
    public function twjReportSearchForm() {
        $result = '';
        $curdate = curdate();
        $inputs = wf_DatePickerPreset('twfdatefrom', $curdate, true);
        $inputs.= wf_DatePickerPreset('twfdateto', $curdate, true);
        $inputs.= wf_Submit(__('Show'));
        $result = wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Renders tasks without jobs report
     * 
     * @param string $datefrom
     * @param string $dateto
     * 
     * @return string
     */
    public function twjReportSearch($datefrom, $dateto) {
        $datefrom = mysql_real_escape_string($datefrom);
        $dateto = mysql_real_escape_string($dateto);
        $result = '';
        $tmpArr = array();
        $messages = new UbillingMessageHelper();
        $query = "SELECT * from `taskman` WHERE CAST(`startdate` AS DATE) BETWEEN '" . $datefrom . "' AND  '" . $dateto . "';";

        $allTasks = simple_queryall($query);
        if (!empty($allTasks)) {
            foreach ($allTasks as $io => $eachTask) {
                $taskJobs = $this->filterTaskJobs($eachTask['id']);
                if (empty($taskJobs)) {
                    $tmpArr[$eachTask['id']] = $eachTask;
                }
            }

            if (!empty($tmpArr)) {

                $cells = wf_TableCell(__('Task'));
                $cells.= wf_TableCell(__('Address'));
                $cells.= wf_TableCell(__('Target date'));
                $cells.= wf_TableCell(__('Job type'));
                $cells.= wf_TableCell(__('Who should do'));
                $cells.= wf_TableCell(__('Done'));

                $rows = wf_TableRow($cells, 'row1');


                foreach ($tmpArr as $io => $eachTask) {
                    $taskid = $eachTask['id'];
                    $cells = wf_TableCell(wf_Link(self::URL_TS . $taskid, $taskid));
                    $cells.= wf_TableCell(@$eachTask['address']);
                    $cells.= wf_TableCell(@$eachTask['startdate']);
                    $cells.= wf_TableCell(@$this->allJobtypes[$eachTask['jobtype']]);
                    $cells.= wf_TableCell(@$this->allEmployeeRaw[$eachTask['employee']]);
                    $cells.= wf_TableCell(web_bool_led($eachTask['status']));

                    $rows.= wf_TableRow($cells, 'row3');
                }

                $result.=wf_TableBody($rows, '100%', 0, 'sortable');
            } else {
                $result = $messages->getStyledMessage(__('Nothing found'), 'info');
            }
        } else {
            $result = $messages->getStyledMessage(__('Nothing found'), 'info');
        }

        return ($result);
    }

    /**
      Far across the distance
      And spaces between us
      You have come to show you go on
     */

    /**
     * Pushes payment action for some processed salary job
     * 
     * @param int $jobid
     * 
     * @return void
     */
    protected function pushPaid($jobid) {
        $jobid = vf($jobid, 3);
        $date = curdatetime();
        if (isset($this->allJobs[$jobid])) {
            $jobData = $this->allJobs[$jobid];
            if ($jobData['state'] == 0) {
                $cash = $this->getJobPrice($jobid);
                $employeeid = $jobData['employeeid'];
                $query = "INSERT INTO `salary_paid` (`id`, `jobid`, `employeeid`, `paid`, `date`) VALUES (NULL, '" . $jobid . "', '" . $employeeid . "', '" . $cash . "', '" . $date . "');";
                nr_query($query);
            } else {
                log_register('SALARY JOB PROCESSING FAIL [' . $jobid . '] DUPLICATE');
            }
        } else {
            log_register('SALARY JOB PROCESSING FAIL [' . $jobid . '] NOT_EXIST');
        }
    }

    /**
     * Returns paid Data for some paid job
     * 
     * @param int $jobid
     * 
     * @return array
     */
    protected function getPaidData($jobid) {
        $result = array();
        if (isset($this->allPaid[$jobid])) {
            $result = $this->allPaid[$jobid];
        }
        return ($result);
    }

    /**
     * Returns some human-readable paid indication
     * 
     * @param int $jobid
     * 
     * @return string
     */
    protected function renderPaidDataLed($jobid) {
        $result = '';
        if (isset($this->allJobs[$jobid])) {
            if ($this->allJobs[$jobid]['state']) {
                $paidData = $this->getPaidData($jobid);
                if (!empty($paidData)) {
                    $title = $paidData['paid'] . ' ' . __('money') . ' - ' . @$this->allEmployee[$paidData['employeeid']] . ', ' . $paidData['date'];
                    $result = wf_tag('abbr', false, '', 'title="' . $title . '"') . web_bool_led($this->allJobs[$jobid]['state']) . wf_tag('abbr', true);
                } else {
                    $result = wf_img('skins/yellow_led.png');
                }
            } else {
                $result = web_bool_led(0);
            }
        }

        return ($result);
    }

    /**
     * shows printable report content
     * 
     * @param $title report title
     * @param $data  report data to printable transform
     * 
     * @return void
     */
    public function reportPrintable($title, $data) {

        $style = file_get_contents(CONFIG_PATH . "ukvprintable.css");

        $header = wf_tag('!DOCTYPE', false, '', 'html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"');
        $header.= wf_tag('html', false, '', 'xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru" lang="ru"');
        $header.= wf_tag('head', false);
        $header.= wf_tag('title') . $title . wf_tag('title', true);
        $header.= wf_tag('meta', false, '', 'http-equiv="Content-Type" content="text/html; charset=UTF-8" /');
        $header.= wf_tag('style', false, '', 'type="text/css"');
        $header.= $style;
        $header.=wf_tag('style', true);
        $header.= wf_tag('script', false, '', 'src="modules/jsc/sorttable.js" language="javascript"') . wf_tag('script', true);
        $header.=wf_tag('head', true);
        $header.= wf_tag('body', false);

        $footer = wf_tag('body', true);
        $footer.= wf_tag('html', true);

        $title = (!empty($title)) ? wf_tag('h2') . $title . wf_tag('h2', true) : '';
        $data = $header . $title . $data . $footer;
        $payedIconMask = web_bool_led(1);
        $unpayedIconMask = web_bool_led(0);
        $submitInputMask = wf_Submit(__('Processing'));

        $data = str_replace($payedIconMask, __('Paid'), $data);
        $data = str_replace($unpayedIconMask, __('Not paid'), $data);
        $data = str_replace($submitInputMask, '', $data);

        die($data);
    }

    /**
     * Renders timesheet create form
     * 
     * @return string
     */
    public function timesheetCreateForm() {
        $result = '';
        if (!empty($this->allEmployee)) {
            $result.= '<!--ugly hack to prevent datepicker autoopen --> <input type="text" name="shittyhack" style="width: 0; height: 0; top: -100px; position: absolute;"/>';
            $result.= wf_HiddenInput('newtimesheet', 'true');
            $result.= wf_DatePickerPreset('newtimesheetdate', curdate(), false);

            $headers = wf_TableCell(__('Worker'));
            $headers.= wf_TableCell(__('Hours'));
            $headers.= wf_TableCell(__('Hospitalized'));
            $headers.= wf_TableCell(__('Holidays'));
            $rows = wf_TableRow($headers, 'row1');

            foreach ($this->allEmployee as $employeeid => $employeename) {
                $defaultWorkTime = (isset($this->allWages[$employeeid]['worktime'])) ? $this->allWages[$employeeid]['worktime'] : 0;
                $cells = wf_TableCell($employeename);
                $cells.= wf_TableCell(wf_TextInput('_employeehours[' . $employeeid . ']', '', $defaultWorkTime, false, '2'));
                $cells.= wf_TableCell(wf_CheckInput('_hospital[' . $employeeid . ']', '', false, false));
                $cells.= wf_TableCell(wf_CheckInput('_holidays[' . $employeeid . ']', '', false, false));
                $rows.= wf_TableRow($cells, 'row3');
            }

            $result.= wf_TableBody($rows, '100%', '0', '');
            $result.= wf_tag('br', false);
            $result.= wf_Submit(__('Create'));
            $result = wf_Form('', 'POST', $result, '');
        }
        return ($result);
    }

    /**
     * Checks is timesheet protected?
     * 
     * @param string $date
     * @return bool
     */
    protected function timesheetProtected($date) {
        if (isset($this->allTimesheetDates[$date])) {
            $result = true;
        } else {
            $result = false;
        }
        return ($result);
    }

    /**
     * Creates new timesheet if date is unique
     * 
     * @return int
     */
    public function timesheetCreate() {
        $result = 0;
        if (wf_CheckPost(array('newtimesheet', 'newtimesheetdate', '_employeehours'))) {
            $date = $_POST['newtimesheetdate'];
            $dateF = mysql_real_escape_string($_POST['newtimesheetdate']);
            if (!$this->timesheetProtected($date)) {
                $counter = 0;
                $employeeHours = $_POST['_employeehours'];
                $hospitalArr = (isset($_POST['_hospital'])) ? $_POST['_hospital'] : array();
                $holidaysArr = (isset($_POST['_holidays'])) ? $_POST['_holidays'] : array();
                if (!empty($employeeHours)) {
                    foreach ($employeeHours as $employeeId => $hours) {
                        $hospitalFlag = (isset($hospitalArr[$employeeId])) ? 1 : 0;
                        $holidaysFlag = (isset($holidaysArr[$employeeId])) ? 1 : 0;
                        $query = "INSERT INTO `salary_timesheets` (`id`,`date`,`employeeid`,`hours`,`holiday`,`hospital`) VALUES "
                                . "(NULL, '" . $dateF . "','" . $employeeId . "','" . $hours . "','" . $holidaysFlag . "','" . $hospitalFlag . "');";
                        nr_query($query);
                    }
                }
                log_register('SALARY CREATE TIMESHEET EMPLOYEECOUUNT `' . $counter . '`');
            } else {
                $result = 1;
                log_register('SALARY CREATE TIMESHEET FAIL EXISTING DATE `' . $date . '`');
            }
        }
        return ($result);
    }

    /**
     * Renders list of timesheets
     * 
     * @param string $baseUrl alternative link destination for viewing timesheet
     * 
     * @return string
     */
    public function timesheetsListRender($baseUrl='') {
        $result = '';
        $linkBase = (empty($baseUrl)) ? self::URL_ME . '&' . self::URL_TSHEETS : $baseUrl;
        if (!empty($this->allTimesheetDates)) {
            $cells = wf_TableCell(__('Date'));
            $cells.= wf_TableCell(__('Rows'));
            $rows = wf_TableRow($cells, 'row1');

            foreach ($this->allTimesheetDates as $date => $count) {
                $cells = wf_TableCell(wf_Link($linkBase . '&showdate=' . $date, $date));
                $cells.= wf_TableCell($count);
                $rows.= wf_TableRow($cells, 'row3');
            }

            $result = wf_TableBody($rows, '100%', '0', 'sortable');
        }
        return ($result);
    }

    /**
     * Returns array of timesheet records filtered by date
     * 
     * @param string $date
     * 
     * @return array
     */
    protected function timesheetFilterDate($date) {
        $result = array();
        if (!empty($this->allTimesheets)) {
            foreach ($this->allTimesheets as $io => $each) {
                if ($each['date'] == $date) {
                    $result[$each['id']] = $each;
                }
            }
        }
        return ($result);
    }

    /**
     * Returns array of timesheet records filtered by Year/month in MySQL date format Y-m
     * 
     * @param string $yearMonth
     * 
     * @return array
     */
    protected function timesheetFilterMonth($yearMonth) {
        $result = array();
        if (!empty($this->allTimesheets)) {
            foreach ($this->allTimesheets as $io => $each) {
                if (ispos($each['date'], $yearMonth)) {
                    $result[$each['id']] = $each;
                }
            }
        }
        return ($result);
    }

    /**
     * Renders timesheet editing form
     * 
     * @param string $timesheetDate
     * 
     * @return string
     */
    public function timesheetEditForm($timesheetDate) {
        $result = '';
        $timesheetData = $this->timesheetFilterDate($timesheetDate);
        if (!empty($timesheetData)) {

            $headers = wf_TableCell(__('Worker'));
            $headers.= wf_TableCell(__('Hours'));
            $headers.= wf_TableCell(__('Hospitalized'));
            $headers.= wf_TableCell(__('Holidays'));
            $headers.= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($headers, 'row1');

            foreach ($timesheetData as $io => $each) {
                $hospitalFlag = ($each['hospital']) ? true : false;
                $holidayFlag = ($each['holiday']) ? true : false;
                $cells = wf_TableCell(@$this->allEmployeeRaw[$each['employeeid']]);
                $cells.= wf_TableCell(wf_TextInput('editemployeehours', '', $each['hours'], false, '2'));
                $cells.= wf_TableCell(wf_CheckInput('edithospital', '', false, $hospitalFlag));
                $cells.= wf_TableCell(wf_CheckInput('editholiday', '', false, $holidayFlag));
                $cells.= wf_TableCell(wf_HiddenInput('edittimesheetid', $each['id']) . wf_Submit(__('Save')));
                $cells = wf_Form('', 'POST', $cells, '');
                $rows.= wf_TableRow($cells, 'row3');
            }

            $result.= wf_TableBody($rows, '100%', '0', '');
            $result.= wf_tag('br', false);
        }
        return ($result);
    }

    /**
     * Saves timesheet editing results into database
     * 
     * @return void
     */
    public function timesheetSaveChanges() {
        if (wf_CheckPost(array('edittimesheetid'))) {
            $id = vf($_POST['edittimesheetid'], 3);
            if (isset($this->allTimesheets[$id])) {
                $timesheetData = $this->allTimesheets[$id];
                $timesheetDate = $timesheetData['date'];
                $employee = $timesheetData['employeeid'];
                $hours = vf($_POST['editemployeehours'], 3);
                $hospitalFlag = (wf_CheckPost(array('edithospital'))) ? 1 : 0;
                $holidayFlag = (wf_CheckPost(array('editholiday'))) ? 1 : 0;
                $where = " WHERE `id`='" . $id . "';";

                if ($timesheetData['hours'] != $hours) {
                    simple_update_field('salary_timesheets', 'hours', $hours, $where);
                }

                if ($timesheetData['holiday'] != $holidayFlag) {
                    simple_update_field('salary_timesheets', 'holiday', $holidayFlag, $where);
                }

                if ($timesheetData['hospital'] != $hospitalFlag) {
                    simple_update_field('salary_timesheets', 'hospital', $hospitalFlag, $where);
                }

                log_register('SALARY EDIT TIMESHEET [' . $id . '] `' . $timesheetDate . '` EMPLOYEE  [' . $employee . '] HOURS `' . $hours . '` HOSPITAL `' . $hospitalFlag . '` HOLIDAY `' . $holidayFlag . '`');
            } else {
                log_register('SALARY EDIT FAIL TIMESHEET [' . $id . '] NOT_EXISTING_ID');
            }
        }
    }

    /**
     * Returns 
     * 
     * @return string
     */
    public function timesheetRenderPrintableForm() {
        $result = '';
        $inputs = wf_YearSelector('tsheetprintyear', __('Year') . ' ', false);
        $inputs.= wf_MonthSelector('tsheetprintmonth', __('Month') . ' ', date('m'), false);
        $inputs.= wf_Submit(__('Show'));
        $result = wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Renders printable timesheets by some month
     * 
     * @param int $year
     * @param string $month
     * 
     * @return string
     */
    public function timesheetRenderPrintable($year, $month) {
        $result = '';
        $dateOffset = $year . '-' . $month;
        $allTimesheets = $this->timesheetFilterMonth($dateOffset);
        $this->loadAppointments();
        $tmpArr = array();


        $cells = wf_TableCell(__('Worker'));
        $cells.= wf_TableCell(__('Appointment'));
        for ($i = 1; $i <= 31; $i++) {
            $dayCellHeader = ($i < 10) ? '0' . $i : $i;
            $cells.=wf_TableCell($dayCellHeader);
        }
        $cells.= wf_TableCell(__('Total') . ' ' . __('days'));
        $cells.= wf_TableCell(__('Holidays'));
        $cells.= wf_TableCell(__('Total') . ' ' . __('hours'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($allTimesheets)) {
            foreach ($allTimesheets as $io => $each) {
                $timestamp = strtotime($each['date']);
                $dayNum = date('j', $timestamp);
                if (!isset($tmpArr[$each['employeeid']])) {
                    $tmpArr[$each['employeeid']]['totalhours'] = $each['hours'];
                    $tmpArr[$each['employeeid']]['holidays'] = $each['holiday'];
                    $tmpArr[$each['employeeid']]['hospital'] = $each['hospital'];
                    if ($each['hours'] != 0) {
                        $tmpArr[$each['employeeid']]['totaldays'] = 1;
                    } else {
                        $tmpArr[$each['employeeid']]['totaldays'] = 0;
                    }
                    $tmpArr[$each['employeeid']]['day_' . $dayNum] = $each['hours'];
                    if ($each['hospital']) {
                        $tmpArr[$each['employeeid']]['dayhospital_' . $dayNum] = 1;
                    } else {
                        $tmpArr[$each['employeeid']]['dayhospital_' . $dayNum] = 0;
                    }
                    if ($each['holiday']) {
                        $tmpArr[$each['employeeid']]['dayholiday_' . $dayNum] = 1;
                    } else {
                        $tmpArr[$each['employeeid']]['dayholiday_' . $dayNum] = 0;
                    }
                } else {
                    $tmpArr[$each['employeeid']]['totalhours']+=$each['hours'];
                    $tmpArr[$each['employeeid']]['holidays']+=$each['holiday'];
                    $tmpArr[$each['employeeid']]['hospital']+=$each['hospital'];
                    if ($each['hours'] != 0) {
                        $tmpArr[$each['employeeid']]['totaldays'] ++;
                    }
                    $tmpArr[$each['employeeid']]['day_' . $dayNum] = $each['hours'];
                    if ($each['hospital']) {
                        $tmpArr[$each['employeeid']]['dayhospital_' . $dayNum] = 1;
                    } else {
                        $tmpArr[$each['employeeid']]['dayhospital_' . $dayNum] = 0;
                    }
                    if ($each['holiday']) {
                        $tmpArr[$each['employeeid']]['dayholiday_' . $dayNum] = 1;
                    } else {
                        $tmpArr[$each['employeeid']]['dayholiday_' . $dayNum] = 0;
                    }
                }
            }
        }
      //  print_r($tmpArr);
        if (!empty($tmpArr)) {
            foreach ($tmpArr as $employeeid => $each) {
                $cells = wf_TableCell(@$this->allEmployeeRaw[$employeeid]);
                $cells.= wf_TableCell(@$this->allAppointments[$employeeid]);
                for ($i = 1; $i <= 31; $i++) {
                    $dayCell = (isset($each['day_' . $i])) ? $each['day_' . $i] : 0;
                    $dayCellSuffix='';
                    if (@$each['dayholiday_'.$i]) {
                        $dayCellSuffix.=wf_tag('sup').'v'.wf_tag('sup',true);
                    }
                    if (@$each['dayhospital_'.$i]) {
                        $dayCellSuffix.=wf_tag('sup').'h'.wf_tag('sup',true);
                    }
                    $cells.=wf_TableCell($dayCell.$dayCellSuffix);
                }
                $cells.= wf_TableCell($each['totaldays']);
                $cells.= wf_TableCell($each['holidays']);
                $cells.= wf_TableCell($each['totalhours']);
                $rows.= wf_TableRow($cells, 'row3');
            }
        }


        $result.= wf_TableBody($rows, '100%', 0, 'sortable');
        $result.= 'v - '.__('Holidays').wf_tag('br');
        $result.= 'h - '.__('Hospitalized');
        $result = $this->reportPrintable(__('Timesheets') . ' ' . $dateOffset, $result);
        return ($result);
    }

    /**
     * Shows salary summary report
     * 
     * @return void
     */
    public function summaryReport() {
        $result = '';
        if ($_SERVER['QUERY_STRING'] == 'module=salary') {
            $messages = new UbillingMessageHelper();
            if (empty($this->allEmployee)) {
                $result.= $messages->getStyledMessage(__('No available workers for wage creation'), 'error');
            } else {
                $result.= $messages->getStyledMessage(__('Total existing employees') . ': ' . sizeof($this->allEmployee), 'info');
            }

            if (empty($this->allJobtypes)) {
                $result.=$messages->getStyledMessage(__('No available job types for pricing'), 'error');
            } else {
                $result.= $messages->getStyledMessage(__('Total existing job types') . ': ' . sizeof($this->allJobtypes), 'info');
            }

            if (empty($this->allJobPrices)) {
                $result.=$messages->getStyledMessage(__('There is no set prices for job types'), 'warning');
            } else {
                $result.=$messages->getStyledMessage(__('Total paid types of work') . ': ' . sizeof($this->allJobPrices), 'info');
            }

            if (empty($this->allWages)) {
                $result.=$messages->getStyledMessage(__('There is no set wages for workers'), 'warning');
            }

            if (empty($this->allJobs)) {
                $result.=$messages->getStyledMessage(__('Not done yet any paid work'), 'warning');
            } else {
                $todayJobs = $this->jobsFilterDate(curdate());
                $todayJobsCount = sizeof($todayJobs);
                $monthJobs = $this->jobsFilterDate(curmonth());
                $monthJobsCount = sizeof($monthJobs);
                $result.= $messages->getStyledMessage(__('Today performed paid work') . ': ' . $todayJobsCount, 'success');
                $result.= $messages->getStyledMessage(__('Month performed paid work') . ': ' . $monthJobsCount, 'success');
                $result.= $messages->getStyledMessage(__('Total performed paid work') . ': ' . sizeof($this->allJobs), 'success');
            }

            if (empty($this->allTimesheetDates)) {
                $result.=$messages->getStyledMessage(__('No filled timesheets'), 'warning');
            } else {
                if (!isset($this->allTimesheetDates[curdate()])) {
                    $result.=$messages->getStyledMessage(__('For today is not filled timesheets'), 'warning');
                } else {
                    $result.=$messages->getStyledMessage(__('For today timesheets is filled'), 'success');
                }

                $result.=$messages->getStyledMessage(__('Filled timesheets for') . ' ' . sizeof($this->allTimesheetDates) . ' ' . __('days'), 'success');
            }


            if (!empty($result)) {
                show_window(__('Stats'), $result);
            }
        }
    }

}

?>