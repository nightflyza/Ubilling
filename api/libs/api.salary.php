<?

class Salary {

    protected $allEmployee = array();
    protected $allJobtypes = array();
    protected $allJobTimes = array();
    protected $allJobPrices = array();
    protected $allJobUnits = array();
    protected $allWages = array();
    protected $unitTypes = array();
    protected $allJobs = array();

    const URL_ME = '?module=salary';
    const URL_TS = '?module=taskman&edittask=';
    const URL_JOBPRICES = 'jobprices=true';
    const URL_WAGES = 'employeewages=true';

    public function __construct() {
        $this->setUnitTypes();
        $this->loadEmployee();
        $this->loadJobtypes();
        $this->loadJobprices();
        $this->loadWages();
        $this->loadSalaryJobs();
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
     * Loads available jobtypes from database
     * 
     * @return void
     */
    protected function loadJobtypes() {
        $this->allJobtypes = ts_GetAllJobtypes();
    }

    protected function setUnitTypes() {
        $this->unitTypes['quantity'] = __('quantity');
        $this->unitTypes['meter'] = __('meter');
        $this->unitTypes['kilometer'] = __('kilometer');
        $this->unitTypes['money'] = __('money');
        $this->unitTypes['time'] = __('time');
        $this->unitTypes['litre'] = __('litre');
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
            $inputs = wf_Selector('newjobtypepriceid', $this->allJobtypes, __('Job type'), '', false) . ' ';
            $inputs.= wf_Selector('newjobtypepriceunit', $this->unitTypes, __('Units'), '', false) . ' ';
            $inputs.= wf_TextInput('newjobtypeprice', __('Price'), '', false, 5) . ' ';
            $inputs.= wf_TextInput('newjobtypepricetime', __('Hours'), '', false, 2) . ' ';
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
            $inputs.= wf_TextInput('editjobtypepricetime', __('Hours'), $this->allJobTimes[$jobtypeid], true, 2) . ' ';
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
            log_register('SALARY CREATE JOBPRICE FAILED EXIST JOBID [' . $jobtypeid . ']');
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
        $cells.= wf_TableCell(__('Hours'));
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
        $result.= wf_Link(self::URL_ME . '&' . self::URL_JOBPRICES, wf_img('skins/shovel.png') . ' ' . __('Job types'), false, 'ubButton');
        $result.= wf_Link(self::URL_ME . '&' . self::URL_WAGES, wf_img('skins/icon_user.gif') . ' ' . __('Employee wages'), false, 'ubButton');
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

            $inputs = wf_HiddenInput('newsalarytaskid', $taskid);
            $inputs.= wf_Selector('newsalaryemployeeid', $this->allEmployee, __('Worker'), '', false);
            $inputs.= wf_Selector('newsalaryjobtypeid', $jobtypes, __('Job type'), '', false);
            $inputs.= wf_TextInput('newsalaryfactor', __('Factor'), '0', false, 4);
            $inputs.= wf_TextInput('newsalaryoverprice', __('Price override'), '', false, 4);
            $inputs.= wf_TextInput('newsalarynotes', __('Notes'), '', false, 25);
            $inputs.= wf_Submit(__('Save'));
            $result = wf_Form('', 'POST', $inputs, 'glamour');
            $result.=wf_CleanDiv();
            $result.=$this->renderTaskJobs($taskid);
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
        $cells.= wf_TableCell(__('Status'));
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
                $cells.= wf_TableCell(web_bool_led($this->allJobs[$each['id']]['state']));
                $cells.= wf_TableCell(@$this->allEmployee[$each['employeeid']]);
                $cells.= wf_TableCell(@$this->allJobtypes[$each['jobtypeid']]);
                $cells.= wf_TableCell($each['factor'] . ' / ' . $unit);
                $cells.= wf_TableCell($each['overprice']);
                $cells.= wf_TableCell($each['note']);
                $jobPrice = $this->getJobPrice($each['id']);
                $cells.= wf_TableCell($jobPrice);
                $actLinks = wf_JSAlert(self::URL_TS . $taskid . '&deletejobid=' . $each['id'], web_delete_icon(), $messages->getDeleteAlert());
                $actLinks.= wf_modalAuto(web_edit_icon(), __('Edit'), $this->jobEditForm($each['id']));
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
     * 
     * @return void
     */
    public function employeeWageCreate($employeeid, $wage, $bounty) {
        $employeeid = vf($employeeid, 3);
        if (!isset($this->allWages[$employeeid])) {
            $wage = str_replace(',', '.', $wage);
            $bounty = str_replace(',', '.', $bounty);
            $wageF = mysql_real_escape_string($wage);
            $bountyF = mysql_real_escape_string($bounty);
            $query = "INSERT INTO `salary_wages` (`id`, `employeeid`, `wage`, `bounty`) VALUES (NULL, '" . $employeeid . "', '" . $wage . "', '" . $bounty . "');";
            nr_query($query);
            log_register('SALARY CREATE WAGE EMPLOYEE [' . $employeeid . '] WAGE `' . $wageF . '` BOUNTY `' . $bountyF . '`');
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
            $inputs = wf_Selector('newemployeewageemployeeid', $this->allEmployee, __('Worker'), '', false) . ' ';
            $inputs.= wf_TextInput('newemployeewage', __('Wage'), '', false, 5) . ' ';
            $inputs.= wf_TextInput('newemployeewagebounty', __('Bounty'), '', false, 5) . ' ';
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
            $inputs.= wf_TextInput('editemployeewage', __('Wage'), $this->allWages[$employeeid]['wage'], false, 5) . ' ';
            $inputs.= wf_TextInput('editemployeewagebounty', __('Bounty'), $this->allWages[$employeeid]['bounty'], false, 5) . ' ';
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
     * 
     * @return void
     */
    public function employeeWageEdit($employeeid, $wage, $bounty) {
        $employeeid = vf($employeeid, 3);
        $wage = str_replace(',', '.', $wage);
        $bounty = str_replace(',', '.', $bounty);
        $where = " WHERE `employeeid`='" . $employeeid . "'";
        simple_update_field('salary_wages', 'wage', $wage, $where);
        simple_update_field('salary_wages', 'bounty', $bounty, $where);
        log_register('SALARY EDIT WAGE EMPLOYEE [' . $employeeid . '] WAGE `' . $wage . '` BOUNTY`' . $bounty . '`');
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
        $cells.= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($this->allWages)) {
            foreach ($this->allWages as $io => $each) {
                $cells = wf_TableCell(@$this->allEmployee[$io]);
                $cells.= wf_TableCell($this->allWages[$io]['wage']);
                $cells.= wf_TableCell($this->allWages[$io]['bounty']);
                $actlinks = wf_JSAlertStyled('?module=salary&employeewages=true&deletewage=' . $io, web_delete_icon(), $messages->getDeleteAlert());
                $actlinks.= wf_modalAuto(web_edit_icon(), __('Edit'), $this->employeeWageEditForm($io));
                $cells.= wf_TableCell($actlinks);
                $rows.= wf_TableRow($cells, 'row3');
            }
        }

        $result = wf_TableBody($rows, '100%', 0, 'sortable');
        return ($result);
    }

}

?>