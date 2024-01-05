<?php

/**
 * Employee salary accounting implementation
 */
class Salary {

    /**
     * System alter.ini config stored as array key=>value
     *
     * @var array
     */
    protected $altCfg = array();

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
    protected $allEmployeeRaw = array();

    /**
     * Contains available employee telegram chatid data as id=>chatid
     *
     * @var array
     */
    protected $allEmployeeTelegram = array();

    /**
     * Contains all available employee realnames as login=>name
     *
     * @var array
     */
    protected $allEmployeeLogins = array();

    /**
     * Available jobtypes as jobtypeid=>name
     *
     * @var array
     */
    protected $allJobtypes = array();

    /**
     * Typical jobtypes required time in minutes as jobtypeid=>time
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
     * All available salary jobs as id=>jobdata
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
     * All available timesheets as array id=>timesheetdata
     *
     * @var array
     */
    protected $allTimesheets = array();

    /**
     * Timesheets dates as date=>timesheet count
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

    /**
     * Default factor value for newly created salary jobs
     *
     * @var int
     */
    protected $defaultFactor = 0;

    /**
     * Contains previously detected tasks jobs mappings
     *
     * @var array
     */
    protected $taskJobsCache = array();

    /**
     * System caching object placeholder
     *
     * @var object
     */
    protected $cache = '';

    /**
     * System telegram object placeholder
     *
     * @var object
     */
    protected $telegram = '';

    /**
     * Telegram force notification flag
     *
     * @var bool
     */
    protected $telegramNotify = false;

    /**
     * Contains start date that large data must be loaded
     *
     * @var string
     */
    protected $dateFrom = '';

    /**
     * Contains end date that large data must be loaded to
     *
     * @var string
     */
    protected $dateTo = '';

    const URL_ME = '?module=salary';
    const URL_TS = '?module=taskman&edittask=';
    const URL_JOBPRICES = 'jobprices=true';
    const URL_WAGES = 'employeewages=true';
    const URL_PAYROLL = 'payroll=true';
    const URL_FACONTROL = 'factorcontrol=true';
    const URL_TWJ = 'twjreport=true';
    const URL_LTR = 'ltreport=true';
    const URL_TSHEETS = 'timesheets=true';
    const URL_YRREP = 'yearreport=true';
    const CACHE_TIMEOUT = 2592000;

    /**
     * Creates new Salary instance
     * 
     * @param int $taskid Existing taskId for jobs loading
     * 
     * @return void
     */
    public function __construct($taskid = '') {
        $this->loadAltCfg();
        $this->setOptions();
        $this->setDates();
        $this->catchDateOffsets();
        $this->setUnitTypes();
        $this->loadEmployeeData();
        $this->loadJobtypes();
        $this->loadJobprices();
        $this->loadWages();
        $this->loadSalaryJobs($taskid);
        $this->loadPaid();
        $this->initTelegram();
        $this->initCache();
        $this->loadTaskJobsCache();
        if (empty($taskid)) {
            $this->loadTimesheets();
        }
    }

    /**
     * Loads system alter config
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadAltCfg() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Sets some config based options
     * 
     * @return void
     */
    protected function setOptions() {
        if (isset($this->altCfg['SALARY_TELEGRAM']) AND $this->altCfg['SALARY_TELEGRAM']) {
            $this->telegramNotify = true;
        }

        if (isset($this->altCfg['SALARY_FACTOR_DEFAULT']) AND $this->altCfg['SALARY_FACTOR_DEFAULT']) {
            $this->defaultFactor = $this->altCfg['SALARY_FACTOR_DEFAULT'];
        }
    }

    /**
     * Catches some date offsets and points internal props into required values
     * 
     * @return void
     */
    protected function catchDateOffsets() {

        //payroll dates
        if (ubRouting::checkPost(array('prdatefrom', 'prdateto'))) {
            $this->setDates(ubRouting::post('prdatefrom', 'mres'), ubRouting::post('prdateto', 'mres'));
        }

        //payroll processing
        if (ubRouting::checkPost('prstateprocessing')) {
            $curYear = curyear();
            $prevYear = $curYear - 1;
            $this->setDates($prevYear . date("-m-d"));
        }

        //timesheets for previous year too
        if (ubRouting::checkGet('timesheets') or ubRouting::get('module') == 'salary_timesheets') {
            $curYear = date("Y");
            $prevYear = $curYear - 1;
            $this->setDates($prevYear . date("-m-d"));
        }

        //tasks without jobs report
        if (ubRouting::checkPost(array('twfdatefrom', 'twfdateto'))) {
            $this->setDates(ubRouting::post('twfdatefrom', 'mres'), ubRouting::post('twfdateto', 'mres'));
        }

        //ltreport
        if (ubRouting::checkPost(array('datefrom', 'dateto'))) {
            $this->setDates(ubRouting::post('datefrom', 'mres'), ubRouting::post('dateto', 'mres'));
        }

        //payroll printing
        if (ubRouting::checkGet(array('df', 'dt', 'print'))) {
            $this->setDates(ubRouting::get('df', 'mres'), ubRouting::get('dt', 'mres'));
        }

        //year payments report
        if (ubRouting::checkGet(array('yearreport'))) {
            if (ubRouting::checkPost('showyear')) {
                $this->setDates(ubRouting::post('showyear', 'int') . '-01-01');
            } else {
                //begin of current year
                $curYear = curyear();
                $this->setDates($curYear . '-01-01');
            }
        }

        //tsheets printing
        if (ubRouting::checkPost(array('tsheetprintmonth', 'tsheetprintyear'))) {
            $this->setDates(ubRouting::post('tsheetprintyear', 'int') . '-' . ubRouting::post('tsheetprintmonth') . '-01');
        }
    }

    /**
     * Sets start and end dates if requred. 
     * Default on begin of month and end of current month.
     * 
     * @param string $dateFrom
     * @param string $dateTo
     * 
     * @return void
     */
    protected function setDates($dateFrom = '', $dateTo = '') {
        $startTime = '00:00:00';
        $endTime = '23:56:59';

        if (empty($dateFrom)) {
            $this->dateFrom = date("Y-m") . '-01' . ' ' . $startTime;
        } else {
            $this->dateFrom = $dateFrom . ' ' . $startTime;
        }

        if (empty($dateTo)) {
            $this->dateTo = date("Y-m-d") . ' ' . $endTime;
        } else {
            $this->dateTo = $dateTo . ' ' . $endTime;
        }
    }

    /**
     * Loads active employees from database
     * 
     * @return void
     */
    protected function loadEmployeeData() {
        $query = "SELECT * from `employee`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allEmployeeRaw[$each['id']] = $each['name'];
                if ($each['active']) {
                    $this->allEmployee[$each['id']] = $each['name'];
                }
                if (!empty($each['admlogin'])) {
                    $this->allEmployeeLogins[$each['admlogin']] = $each['name'];
                }
                $this->allEmployeeTelegram[$each['id']] = $each['telegram'];
            }
        }
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
        $where = "WHERE `date` BETWEEN '" . $this->dateFrom . "' AND '" . $this->dateTo . "'";
        $query = "SELECT * from `salary_paid` " . $where;
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
        $where = "WHERE `date` BETWEEN '" . $this->dateFrom . "' AND '" . $this->dateTo . "'";
        $query = "SELECT * from `salary_timesheets` " . $where . " ORDER BY `id` DESC";
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
            $inputs .= wf_Selector('newjobtypepriceunit', $this->unitTypes, __('Units'), '', true) . ' ';
            $inputs .= wf_TextInput('newjobtypeprice', __('Price'), '', true, 5) . ' ';
            $inputs .= wf_TextInput('newjobtypepricetime', __('Typical execution time') . ' (' . __('minutes') . ')', '', true, 5) . ' ';
            $inputs .= wf_Submit(__('Create'));
            $result = wf_Form('', 'POST', $inputs, 'glamour');
            $result .= wf_CleanDiv();
        }
        return ($result);
    }

    /**
     * Inits telegram object as protected instance for further usage
     * 
     * @return void
     */
    protected function initTelegram() {
        if ($this->altCfg['SENDDOG_ENABLED']) {
            $this->telegram = new UbillingTelegram();
        }
    }

    /**
     * Inits system cache for further usage
     * 
     * @return void
     */
    protected function initCache() {
        $this->cache = new UbillingCache();
    }

    /**
     * Loads tasks=>jobs cache
     * 
     * @return void
     */
    protected function loadTaskJobsCache() {
        $this->taskJobsCache = $this->cache->get('TASKSJOBS', self::CACHE_TIMEOUT);
        if (empty($this->taskJobsCache)) {
            $this->taskJobsCache = array();
        }
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
            $inputs .= wf_Selector('editjobtypepriceunit', $this->unitTypes, __('Units'), $this->allJobUnits[$jobtypeid], true);
            $inputs .= wf_TextInput('editjobtypeprice', __('Price'), $this->allJobPrices[$jobtypeid], true, 5);
            $inputs .= wf_TextInput('editjobtypepricetime', __('Minutes'), $this->allJobTimes[$jobtypeid], true, 5) . ' ';
            $inputs .= wf_Submit(__('Save'));
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
        $time = str_replace(',', '.', $time);
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
     * Stores Telegram message for some employee
     * 
     * @param int $employeeid
     * @param string $message
     * 
     * @return void
     */
    protected function sendTelegram($employeeId, $message) {
        if ($this->altCfg['SENDDOG_ENABLED']) {
            $chatId = @$this->allEmployeeTelegram[$employeeId];
            if (!empty($chatId)) {
                $this->telegram->sendMessage($chatId, $message, false, 'SALARY');
            }
        }
    }

    /**
     * Sends some notificaton about salary job creation to employee
     * 
     * @param int $JobId
     * @param int $taskid
     * @param int $employeeid
     * @param int $jobtypeid
     * @param float $factor
     * @param float $overprice
     * @param string $notes
     * 
     * @return void
     */
    protected function salaryCreationNotify($jobId, $taskid, $employeeid, $jobtypeid, $factor, $overprice, $notes) {
        if ($this->telegramNotify) {
            $taskData = ts_GetTaskData($taskid);
            $message = '';
            $jobName = @$this->allJobtypes[$jobtypeid];
            $jobPrice = 0;

            if (empty($overprice)) {
                if (isset($this->allJobPrices[$jobtypeid])) {
                    $jobPrice = $this->allJobPrices[$jobtypeid] * $factor;
                }
            } else {
                $jobPrice = $overprice . ' (' . __('Price override') . ')';
            }

            $unitType = @$this->allJobUnits[$jobtypeid];

            // manually calculate jobtyme instead of jus using getJobTime 
            // because allJobs property not updated yet at this moment
            $jobTime = 0;
            if (isset($this->allJobTimes[$jobtypeid])) {
                $jobTime = $this->allJobTimes[$jobtypeid] * $factor;
            }

            $message .= '🔥 ' . __('Job added on') . ' ' . @$taskData['address'] . '\r\n ';
            $message .= __('Job type') . ': ' . $jobName . '\r\n ';
            $message .= __('Factor') . ': ' . $factor . ' / ' . __($unitType) . '\r\n ';
            $message .= __('Job price') . ': ' . $jobPrice . '\r\n ';
            $message .= __('Labor time') . ': ' . $jobTime . ' ' . __('minutes') . '\r\n ';

            $this->sendTelegram($employeeid, $message);
        }
    }

    /**
     * Sends notification for jobs created by current day
     * 
     * @return void
     */
    public function telegramDailyNotify() {
        if (!empty($this->allJobs)) {
            if ($this->altCfg['SENDDOG_ENABLED']) {
                $curdate = curdate();
                $sendTmp = array(); //employeeid => text aggregated
                $employeeSumm = array(); //employeeid => summ 
                $employeeTime = array(); //employeeid=>time in minutes

                foreach ($this->allJobs as $io => $eachJob) {
                    if (ispos($eachJob['date'], $curdate)) {
                        $employeeId = $eachJob['employeeid'];
                        $chatId = @$this->allEmployeeTelegram[$employeeId];
                        $factor = $eachJob['factor'];
                        $jobtypeid = $eachJob['jobtypeid'];
                        $overprice = $eachJob['overprice'];
                        $priceOverrided = false;
                        if (!empty($chatId)) {
                            if (!isset($sendTmp[$employeeId])) {
                                $sendTmp[$employeeId] = '';
                            }
                            $message = '';
                            $taskid = $eachJob['taskid'];
                            $taskData = ts_GetTaskData($taskid);

                            $jobName = @$this->allJobtypes[$jobtypeid];
                            $jobPrice = 0;

                            if (empty($overprice)) {
                                if (isset($this->allJobPrices[$jobtypeid])) {
                                    $jobPrice = $this->allJobPrices[$jobtypeid] * $factor;
                                }
                            } else {
                                $priceOverrided = true;
                                $jobPrice = $overprice;
                            }

                            $jobTime = $this->getJobTime($eachJob['id']);

                            //per day summary
                            if (isset($employeeSumm[$employeeId])) {
                                $employeeSumm[$employeeId] += $jobPrice;
                            } else {
                                $employeeSumm[$employeeId] = $jobPrice;
                            }

                            if (isset($employeeTime[$employeeId])) {
                                $employeeTime[$employeeId] += $jobTime;
                            } else {
                                $employeeTime[$employeeId] = $jobTime;
                            }


                            $unitType = @$this->allJobUnits[$jobtypeid];
                            $overLabel = ($priceOverrided) ? ' (' . __('Price override') . ')' : '';
                            $message .= __('Job added on') . ' ' . @$taskData['address'] . '\r\n ';
                            $message .= __('Job type') . ': ' . $jobName . '\r\n ';
                            $message .= __('Factor') . ': ' . $factor . ' / ' . __($unitType) . '\r\n ';
                            $message .= __('Spent time') . ': ' . $jobTime . ' ' . __('minutes') . '\r\n ';
                            $message .= __('Job price') . ': ' . $jobPrice . $overLabel . '\r\n ';
                            $message .= '💵💵💵' . '\r\n '; // vsrate emoji

                            $message .= '' . '\r\n ';
                            $sendTmp[$employeeId] .= $message;
                        }
                    }
                }

                //appending daily time to each employee message
                if (!empty($employeeTime)) {
                    foreach ($employeeTime as $io => $eachDayTime) {
                        $sendTmp[$io] .= '⏱️ ';   //another vsrate emoji
                        $sendTmp[$io] .= __('Labor time') . ': ' . zb_formatTime(($eachDayTime * 60)) . '\r\n ';
                    }
                }

                //appending daily summ to each employee message
                if (!empty($employeeSumm)) {
                    foreach ($employeeSumm as $io => $eachDaySumm) {
                        if (isset($sendTmp[$io])) {
                            $sendTmp[$io] .= '💰 ';   //very vsrate emoji
                            $sendTmp[$io] .= __('Total money') . ': ' . $eachDaySumm . '\r\n ';
                        }
                    }
                }

                //sending prepared messages for all employee with jobs today
                if (!empty($sendTmp)) {
                    foreach ($sendTmp as $io => $eachMessage) {
                        $this->sendTelegram($io, $eachMessage);
                    }
                }
            }
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
        $cells .= wf_TableCell(__('Units'));
        $cells .= wf_TableCell(__('Price'));
        $cells .= wf_TableCell(__('Minutes'));
        $cells .= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($this->allJobPrices)) {
            foreach ($this->allJobPrices as $jobtypeid => $eachprice) {
                $cells = wf_TableCell(@$this->allJobtypes[$jobtypeid]);
                $cells .= wf_TableCell(__($this->allJobUnits[$jobtypeid]));
                $cells .= wf_TableCell($eachprice);
                $cells .= wf_TableCell($this->allJobTimes[$jobtypeid]);
                $actLinks = wf_JSAlert('?module=salary&deletejobprice=' . $jobtypeid, web_delete_icon(), $messages->getDeleteAlert());
                $actLinks .= wf_modalAuto(web_edit_icon(), __('Edit'), $this->jobPricesEditForm($jobtypeid));
                $cells .= wf_TableCell($actLinks);
                $rows .= wf_TableRow($cells, 'row5');
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
        $time = ubRouting::filters($_POST['editjobtypepricetime'], 'mres');
        $time = str_replace(',', '.', $time);
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
        $result .= wf_Link(self::URL_ME . '&' . self::URL_PAYROLL, wf_img('skins/ukv/dollar.png') . ' ' . __('Payroll'), false, 'ubButton');
        $result .= wf_Link(self::URL_ME . '&' . self::URL_TSHEETS, wf_img('skins/icon_calendar.gif') . ' ' . __('Timesheet'), false, 'ubButton');

        $directoriesControls = wf_Link(self::URL_ME . '&' . self::URL_JOBPRICES, wf_img('skins/shovel.png') . ' ' . __('Job types'), false, 'ubButton');
        $directoriesControls .= wf_Link(self::URL_ME . '&' . self::URL_WAGES, wf_img('skins/icon_user.gif') . ' ' . __('Employee wages'), false, 'ubButton');
        $result .= wf_modalAuto(web_icon_extended() . ' ' . __('Directories'), __('Directories'), $directoriesControls, 'ubButton');

        $reports = wf_Link(self::URL_ME . '&' . self::URL_FACONTROL, wf_img('skins/factorcontrol.png') . ' ' . __('Factor control'), false, 'ubButton');
        $reports .= wf_Link(self::URL_ME . '&' . self::URL_TWJ, wf_img('skins/question.png') . ' ' . __('Tasks without jobs'), false, 'ubButton');
        $reports .= wf_Link(self::URL_ME . '&' . self::URL_LTR, wf_img('skins/clock.png') . ' ' . __('Labor time'), false, 'ubButton');
        $reports .= wf_Link(self::URL_ME . '&' . self::URL_YRREP, wf_img('skins/icon_table.png') . ' ' . __('Year salary reports'), false, 'ubButton');
        $result .= wf_modalAuto(wf_img('skins/ukv/report.png') . ' ' . __('Reports'), __('Reports'), $reports, 'ubButton');
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
        $employeeTmp = array();

        if (cfr('SALARYTASKSVIEW')) {
            $result .= $this->renderTaskJobs($taskid);
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

                if (!empty($this->allEmployee)) {
                    foreach ($this->allEmployee as $empid => $empname) {
                        if ($this->checkEmployeeWage($empid)) {
                            $employeeTmp[$empid] = $empname;
                        }
                    }
                }

                if (@$this->altCfg['SALARY_EMPLOYEE_PRESET']) {
                    $taskData = ts_GetTaskData($taskid);
                    $taskEmployeeId = @$taskData['employee'];
                    if (isset($employeeTmp[$taskEmployeeId])) {
                        $defaultEmployeeSelected = $taskEmployeeId;
                    } else {
                        $defaultEmployeeSelected = '';
                    }
                } else {
                    $defaultEmployeeSelected = '';
                }

                if (@$this->altCfg['SALARY_JOBTYPE_PRESET']) {
                    if (empty($taskData)) {
                        $taskData = ts_GetTaskData($taskid);
                    }
                    $taskJobtypeId = @$taskData['jobtype'];
                    if (isset($jobtypes[$taskJobtypeId])) {
                        $defaultJobtypeSelected = $taskJobtypeId;
                    } else {
                        $defaultJobtypeSelected = '';
                    }
                } else {
                    $defaultJobtypeSelected = '';
                }

                $inputs = zb_JSHider();
                $inputs .= wf_HiddenInput('newsalarytaskid', $taskid);
                $inputs .= wf_Selector('newsalaryemployeeid', $employeeTmp, __('Worker'), $defaultEmployeeSelected, true);
                $inputs .= wf_Selector('newsalaryjobtypeid', $jobtypes, __('Job type'), $defaultJobtypeSelected, true);
                $inputs .= wf_TextInput('newsalaryfactor', __('Factor'), $this->defaultFactor, true, 4);
                $inputs .= wf_tag('input', false, '', 'type="checkbox" id="overpricebox" name="overpricebox" onclick="showhide(\'overpricecontainer\');" ');
                $inputs .= wf_tag('label', false, '', 'for="overpricebox"') . __('Price override') . wf_tag('label', true);
                $inputs .= wf_tag('span', false, '', 'id="overpricecontainer" style="display:none;"') . ' ';
                $inputs .= wf_TextInput('newsalaryoverprice', '', '', false, 4);
                $inputs .= wf_tag('span', true);
                $inputs .= wf_tag('br');
                $inputs .= wf_TextInput('newsalarynotes', __('Notes'), '', true, 25);
                $inputs .= wf_Submit(__('Save'));
                $result .= wf_modalAuto(wf_img('skins/icon_ok.gif') . ' ' . __('Create new job'), __('Create new job'), wf_Form('', 'POST', $inputs, 'glamour'), 'ubButton');
                $result .= wf_CleanDiv();
            }
        }
        return ($result);
    }

    /**
     * Loads all available salary jobs from database
     * 
     * @param int $taskid existing task ID for jobs loading
     * 
     * @return void
     */
    protected function loadSalaryJobs($taskid = '') {
        $taskid = vf($taskid, 3);
        $where = '';

        if (!empty($taskid)) {
            $where = "WHERE `taskid`='" . $taskid . "'";
        } else {
            $where = "WHERE `date` BETWEEN '" . $this->dateFrom . "' AND '" . $this->dateTo . "'";
        }

        $query = "SELECT * from `salary_jobs` " . $where . " ORDER BY `id` ASC";
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
        //some telegram notification
        $this->salaryCreationNotify($newId, $taskid, $employeeid, $jobtypeid, $factor, $overprice, $notes);
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
     * Filters available jobs for some task
     * 
     * @param int $taskid
     * @return array
     */
    protected function filterTaskJobsCached($taskid) {
        $taskid = vf($taskid, 3);
        $result = array();
        if (!empty($this->allJobs)) {
            if (!isset($this->taskJobsCache[$taskid])) {
                foreach ($this->allJobs as $io => $each) {
                    if ($each['taskid'] == $taskid) {
                        $result[$each['id']] = $each;
                    }
                }
                $this->taskJobsCache[$taskid] = $result;
                $this->cache->set('TASKSJOBS', $this->taskJobsCache, self::CACHE_TIMEOUT);
            } else {
                $result = $this->taskJobsCache[$taskid];
            }
        }
        return ($result);
    }

    /**
     * Checks is employee active for timesheets and salary accounting or not
     * 
     * @param int $employeeId
     * 
     * @return bool
     */
    protected function checkEmployeeWage($employeeId) {
        if (isset($this->allWages[$employeeId])) {
            $result = true;
        } else {
            $result = false;
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
     * Returns all available job labor times in minutes as jobtypeId=>time 
     * 
     * @return array
     */
    public function getAllJobTimes() {
        return($this->allJobTimes);
    }

    /**
     * Returns time in minutes spent to perform some job
     * 
     * @param int $jobid
     * 
     * @return int
     */
    protected function getJobTime($jobid) {
        $result = 0;
        if (isset($this->allJobs[$jobid])) {
            $jobTypeId = $this->allJobs[$jobid]['jobtypeid'];
            if (isset($this->allJobTimes[$jobTypeId])) {
                $result = $this->allJobTimes[$jobTypeId] * $this->allJobs[$jobid]['factor'];
            }
        }
        return($result);
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
            $inputs .= wf_Selector('editsalaryemployeeid', $this->allEmployee, __('Worker'), $this->allJobs[$jobid]['employeeid'], true);
            $inputs .= wf_Selector('editsalaryjobtypeid', $this->allJobtypes, __('Job type'), $this->allJobs[$jobid]['jobtypeid'], true);
            $inputs .= wf_TextInput('editsalaryfactor', __('Factor'), $this->allJobs[$jobid]['factor'], true, 4);
            $inputs .= wf_TextInput('editsalaryoverprice', __('Price override'), $this->allJobs[$jobid]['overprice'], true, 4);
            $inputs .= wf_TextInput('editsalarynotes', __('Notes'), $this->allJobs[$jobid]['note'], true, 25);
            $inputs .= wf_Submit(__('Save'));
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
        $totalTime = 0;
        $messages = new UbillingMessageHelper();
        $all = $this->filterTaskJobs($taskid);

        $cells = wf_TableCell(__('Date'));
        $cells .= wf_TableCell(__('Paid'));
        $cells .= wf_TableCell(__('Worker'));
        $cells .= wf_TableCell(__('Job type'));
        $cells .= wf_TableCell(__('Factor'));
        $cells .= wf_TableCell(__('Price override'));
        $cells .= wf_TableCell(__('Notes'));
        $cells .= wf_TableCell(__('Cash'));
        $cells .= wf_TableCell(__('Time') . ' (' . __('minutes') . ')');
        $cells .= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $factor = $each['factor'];
                if (isset($this->allJobUnits[$each['jobtypeid']])) {
                    $unit = $this->unitTypes[$this->allJobUnits[$each['jobtypeid']]];
                } else {
                    $unit = __('No');
                }
                $cells = wf_TableCell($each['date']);
                $cells .= wf_TableCell($this->renderPaidDataLed($each['id']));
                $cells .= wf_TableCell(@$this->allEmployeeRaw[$each['employeeid']]);
                $cells .= wf_TableCell(@$this->allJobtypes[$each['jobtypeid']]);
                $cells .= wf_TableCell($factor . ' / ' . $unit);
                $cells .= wf_TableCell($each['overprice']);
                $cells .= wf_TableCell($each['note']);
                $jobPrice = $this->getJobPrice($each['id']);
                $cells .= wf_TableCell($jobPrice);
                $jobTime = $this->getJobTime($each['id']);
                $totalTime += $jobTime;
                $cells .= wf_TableCell($jobTime);
                if (cfr('SALARYTASKS')) {
                    $actLinks = wf_JSAlert(self::URL_TS . $taskid . '&deletejobid=' . $each['id'], web_delete_icon(), $messages->getDeleteAlert());
                    $actLinks .= wf_modalAuto(web_edit_icon(), __('Edit'), $this->jobEditForm($each['id']));
                } else {
                    $actLinks = '';
                }
                $cells .= wf_TableCell($actLinks);

                $rows .= wf_TableRow($cells, 'row3');
                $totalSumm = $totalSumm + $jobPrice;
            }

            $cells = wf_TableCell(__('Total'));
            $cells .= wf_TableCell('');
            $cells .= wf_TableCell('');
            $cells .= wf_TableCell('');
            $cells .= wf_TableCell('');
            $cells .= wf_TableCell('');
            $cells .= wf_TableCell('');
            $cells .= wf_TableCell($totalSumm);
            $timeNorm = zb_formatTime(($totalTime * 60));
            $cells .= wf_TableCell($timeNorm);
            $cells .= wf_TableCell('');
            $rows .= wf_TableRow($cells, 'row2');
        }

        $result = wf_TableBody($rows, '100%', 0, '');
        return ($result);
    }

    /**
     * Renders jobs total price for some task
     * 
     * @param int $taskid
     * @return float
     */
    public function getTaskPrice($taskid) {
        $taskid = vf($taskid, 3);
        $result = '';
        $totalSumm = 0;
        $all = $this->filterTaskJobsCached($taskid);

        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $jobPrice = $this->getJobPrice($each['id']);
                $totalSumm = $totalSumm + $jobPrice;
            }
        }

        $result = $totalSumm;
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
        $employeeTmp = array();
        if (!empty($this->allEmployee)) {
            foreach ($this->allEmployee as $io => $each) {
                if (!$this->checkEmployeeWage($io)) {
                    $employeeTmp[$io] = $each;
                }
            }
        }

        if (!empty($this->allEmployee)) {
            $inputs = wf_Selector('newemployeewageemployeeid', $employeeTmp, __('Worker'), '', true) . ' ';
            $inputs .= wf_TextInput('newemployeewage', __('Wage'), '', true, 5) . ' ';
            $inputs .= wf_TextInput('newemployeewagebounty', __('Bounty'), '', true, 5) . ' ';
            $inputs .= wf_TextInput('newemployeewageworktime', __('Work hours'), '', true, 5);
            $inputs .= wf_Submit(__('Create'));
            $result = wf_Form('', 'POST', $inputs, 'glamour');
            $result .= wf_CleanDiv();
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
            $inputs .= wf_TextInput('editemployeewage', __('Wage'), $this->allWages[$employeeid]['wage'], true, 5) . ' ';
            $inputs .= wf_TextInput('editemployeewagebounty', __('Bounty'), $this->allWages[$employeeid]['bounty'], true, 5) . ' ';
            $inputs .= wf_TextInput('editemployeewageworktime', __('Work hours'), $this->allWages[$employeeid]['worktime'], true, 5) . ' ';
            $inputs .= wf_Submit(__('Save'));
            $result = wf_Form('', 'POST', $inputs, 'glamour');
            $result .= wf_CleanDiv();
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
        $cells .= wf_TableCell(__('Wage'));
        $cells .= wf_TableCell(__('Bounty'));
        $cells .= wf_TableCell(__('Work hours'));
        $cells .= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($this->allWages)) {
            foreach ($this->allWages as $io => $each) {
                $rowClass = (isset($this->allEmployee[$io])) ? 'row3' : 'sigdeleteduser';
                $cells = wf_TableCell(@$this->allEmployeeRaw[$io]);
                $cells .= wf_TableCell($this->allWages[$io]['wage']);
                $cells .= wf_TableCell($this->allWages[$io]['bounty']);
                $cells .= wf_TableCell($this->allWages[$io]['worktime']);
                $actlinks = wf_JSAlertStyled('?module=salary&employeewages=true&deletewage=' . $io, web_delete_icon(), $messages->getDeleteAlert());
                $actlinks .= wf_modalAuto(web_edit_icon(), __('Edit'), $this->employeeWageEditForm($io));
                $cells .= wf_TableCell($actlinks);
                $rows .= wf_TableRow($cells, $rowClass);
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
                if ($this->checkEmployeeWage($io)) {
                    $empParams[$io] = $each;
                }
            }
        }

        $jobtypeParams = array('' => __('Any'));
        $jobtypeParams += $this->allJobtypes;

        $fromDate = (wf_CheckPost(array('prdatefrom'))) ? $_POST['prdatefrom'] : curdate();
        $toDate = (wf_CheckPost(array('prdateto'))) ? $_POST['prdateto'] : curdate();
        $currentEmployee = (wf_CheckPost(array('premployeeid'))) ? $_POST['premployeeid'] : '';
        $currentJobTypeId = (wf_CheckPost(array('prjobtypeid'))) ? $_POST['prjobtypeid'] : '';
        $currentChartsFlag = (wf_CheckPost(array('prnocharts'))) ? true : false;

        $inputs = wf_DatePickerPreset('prdatefrom', $fromDate, true) . ' ';
        $inputs .= wf_DatePickerPreset('prdateto', $toDate, true) . ' ';
        $inputs .= wf_Selector('premployeeid', $empParams, __('Worker'), $currentEmployee, false);
        $inputs .= wf_Selector('prjobtypeid', $jobtypeParams, __('Job type'), $currentJobTypeId, false, false);
        $inputs .= wf_CheckInput('prnocharts', __('No charts'), false, $currentChartsFlag);
        $inputs .= wf_Submit(__('Show'));
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
    public function payrollRenderSearch($datefrom, $dateto, $employeeid, $jobtypeid = '') {
        $datefrom = mysql_real_escape_string($datefrom);
        $dateto = mysql_real_escape_string($dateto);
        $jobtypeid = vf($jobtypeid, 3);
        $employeeid = vf($employeeid, 3);
        $allTasks = ts_GetAllTasksQuickData();
        $totalTimeSpent = 0; //in minutes
        $timeSheetsTimeSpent = 0; // in minutes
        $rangeTimesheets = $this->timesheetFilterDateRange($datefrom, $dateto);
        $currentChartsFlag = (wf_CheckPost(array('prnocharts'))) ? true : false;
        if (wf_CheckGet(array('nc'))) {
            $currentChartsFlag = true;
        }

        $chartData = array();
        $chartDataCash = array();
        $timeChartData = array();

        $result = '';


        $totalSum = 0;
        $payedSum = 0;
        $jobCount = 0;
        $litreCountUnpaid = 0;
        $litreCountPaid = 0;

        $jobtypeFilter = (!empty($jobtypeid)) ? "AND `jobtypeid`='" . $jobtypeid . "'" : '';
        $query = "SELECT * from `salary_jobs` WHERE CAST(`date` AS DATE) BETWEEN '" . $datefrom . "' AND  '" . $dateto . "' AND `employeeid`='" . $employeeid . "' " . $jobtypeFilter . ";";
        $all = simple_queryall($query);

        $selectAllControl = wf_CheckInput('selectallbears', __('All'), false, false, '', 'gummybear');

        $cells = wf_TableCell(__('Date'));
        $cells .= wf_TableCell(__('Task'));
        $cells .= wf_TableCell(__('Job type'));
        $cells .= wf_TableCell(__('Factor'));
        $cells .= wf_TableCell(__('Time'));
        $cells .= wf_TableCell(__('Price override'));
        $cells .= wf_TableCell(__('Notes'));
        $cells .= wf_TableCell(__('Paid'));
        $cells .= wf_TableCell(__('Money'));
        $cells .= wf_TableCell($selectAllControl);
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $jobName = @$this->allJobtypes[$each['jobtypeid']];
                $jobPrice = $this->getJobPrice($each['id']);
                $unitType = @$this->allJobUnits[$each['jobtypeid']];

                if (!empty($jobName)) {
                    if (isset($chartData[$jobName])) {
                        $chartData[$jobName]['count'] += $each['factor'];
                        if ($each['state'] == 0) {
                            $chartData[$jobName]['unpaid'] += $each['factor'];
                        } else {
                            $chartData[$jobName]['paid'] += $each['factor'];
                        }

                        $chartDataCash[$jobName]['cash'] = $chartDataCash[$jobName]['cash'] + $jobPrice;
                        if ($each['state'] == 0) {
                            $chartDataCash[$jobName]['unpaid'] += $jobPrice;
                        } else {
                            $chartDataCash[$jobName]['paid'] += $jobPrice;
                        }
                    } else {
                        $chartData[$jobName]['count'] = $each['factor'];
                        if ($each['state'] == 0) {
                            $chartData[$jobName]['unpaid'] = $each['factor'];
                            $chartData[$jobName]['paid'] = 0;
                        } else {
                            $chartData[$jobName]['unpaid'] = 0;
                            $chartData[$jobName]['paid'] = $each['factor'];
                        }
                        $chartDataCash[$jobName]['cash'] = $jobPrice;
                        if ($each['state'] == 0) {
                            $chartDataCash[$jobName]['unpaid'] = $jobPrice;
                            $chartDataCash[$jobName]['paid'] = 0;
                        } else {
                            $chartDataCash[$jobName]['unpaid'] = 0;
                            $chartDataCash[$jobName]['paid'] = $jobPrice;
                        }
                    }
                }

                if (isset($this->allJobUnits[$each['jobtypeid']])) {
                    $unit = $this->unitTypes[$unitType];
                } else {
                    $unit = __('No');
                }

//job time spent collecting
                $jobTimeSpent = 0;
                if (isset($this->allJobTimes[$each['jobtypeid']])) {
                    $jobFactor = $each['factor'];
                    $jobTimePlanned = $this->allJobTimes[$each['jobtypeid']];
                    $jobTimeSpent = $jobFactor * $jobTimePlanned;
                    $totalTimeSpent += $jobTimeSpent;
                    $jobTypeName = $this->allJobtypes[$each['jobtypeid']];
                    if (!empty($jobTypeName)) {
                        if (isset($timeChartData[$jobTypeName])) {
                            $timeChartData[$jobTypeName] += $jobTimeSpent;
                        } else {
                            $timeChartData[$jobTypeName] = $jobTimeSpent;
                        }
                    }
                }

                $cells = wf_TableCell($each['date']);
                $cells .= wf_TableCell(wf_Link(self::URL_TS . $each['taskid'], $each['taskid']) . ' ' . @$allTasks[$each['taskid']]['address']);
                $cells .= wf_TableCell($jobName);
                $cells .= wf_TableCell($each['factor'] . ' / ' . $unit);
                $cells .= wf_TableCell($this->formatTime($jobTimeSpent * 60));
                $cells .= wf_TableCell($each['overprice']);
                $cells .= wf_TableCell($each['note']);
                $cells .= wf_TableCell($this->renderPaidDataLed($each['id']));

                $cells .= wf_TableCell($jobPrice);
                if (!$each['state']) {
                    $actControls = wf_CheckInput('_prstatecheck[' . $each['id'] . ']', '', true, false, '', 'someonelikeyou');
                } else {
                    $actControls = '';
                }
                $cells .= wf_TableCell($actControls);
                $rows .= wf_TableRow($cells, 'row3');

                if ($each['state'] == 0) {
                    if ($unitType != 'litre') {
                        $totalSum = $totalSum + $jobPrice;
                    } else {
                        $litreCountUnpaid += $jobPrice;
                    }
                    $jobCount++;
                } else {
                    if ($unitType != 'litre') {
                        $payedSum = $payedSum + $jobPrice;
                    } else {
                        $litreCountPaid += $jobPrice;
                    }
                }
            }
        }

//timesheets processing
        if (!empty($rangeTimesheets)) {
            foreach ($rangeTimesheets as $io => $each) {
                if ($each['employeeid'] == $employeeid) {
                    $timeSheetsTimeSpent += $each['hours'] * 60; // rly in minutes
                }
            }
        }

        $result .= wf_TableBody($rows, '100%', 0, '');
        $result .= wf_HiddenInput('prstateprocessing', 'true');
        $result .= wf_tag('script', false);
        $result .= '
       var checkboxes = document.querySelectorAll(".someonelikeyou");
         //Never mind Ill find someone like you
         //I wish nothing but the best for you too
        function selectCheckbox() {
         var newstate=$(".gummybear").is(\':checked\');
          checkboxes.forEach(function(checkbox) {
          checkbox.checked = newstate;
         })
        }
        ';
        $result .= wf_tag('script', true);


        if ($jobCount > 0) {
            $result .= wf_Submit(__('Processing')) . wf_delimiter();
        }



        $result = wf_Form('', 'POST', $result, '');

        $result .= __('Not paid money') . ': ' . $totalSum . wf_tag('br');
        $result .= __('Not paid fuel') . ': ' . $litreCountUnpaid . ' ' . __('litre') . wf_tag('br');
        $result .= __('Paid money') . ': ' . $payedSum . wf_tag('br');
        $result .= __('Paid fuel') . ': ' . $litreCountPaid . ' ' . __('litre') . wf_tag('br');
        $result .= __('Total money') . ': ' . ($payedSum + $totalSum) . wf_tag('br');
        $result .= __('Total') . ' ' . __('time') . ': ' . $this->formatTime($totalTimeSpent * 60) . wf_tag('br');
        $result .= __('Total') . ' ' . __('Work hours') . ': ' . $this->formatTime($timeSheetsTimeSpent * 60);

        if (!empty($chartData)) {
            $result .= wf_CleanDiv();
//chart data postprocessing
            if (!empty($timeChartData)) {
                foreach ($timeChartData as $io => $each) {
                    $timeChartData[$io . ' ' . $each] = $each;
                    unset($timeChartData[$io]);
                }
            }

            if (!empty($chartData)) {
                foreach ($chartData as $io => $each) {
                    $chartData[$io . ' ' . $each['count'] . ' (' . $each['paid'] . '/' . $each['unpaid'] . ')'] = $each['count'];
                    unset($chartData[$io]);
                }
            }


            if (!empty($chartDataCash)) {
                foreach ($chartDataCash as $io => $each) {
                    $chartDataCash[$io . ' ' . $each['cash'] . ' (' . $each['paid'] . '/' . $each['unpaid'] . ')'] = $each['cash'];
                    unset($chartDataCash[$io]);
                }
            }

            $result .= wf_tag('div', false, '', 'style="page-break-after: always;"') . wf_tag('div', true);
            if (!$currentChartsFlag) {
                $chartOpts = "chartArea: {  width: '100%', height: '80%' }, legend : {position: 'right', textStyle: {fontSize: 12 }},  pieSliceText: 'value-and-percentage',";
                $chartCells = wf_TableCell(wf_gcharts3DPie($timeChartData, __('Time') . ' (' . __('hours') . ')', '600px', '400px', $chartOpts));
                $chartCells .= wf_TableCell(wf_gcharts3DPie($chartData, __('Job types') . ' (' . __('Paid') . '/' . __('Unpaid') . ')', '600px', '400px', $chartOpts));
                $chartRows = wf_TableRow($chartCells);
                $chartCells = wf_TableCell(wf_gcharts3DPie($chartDataCash, __('Money') . ' (' . __('Paid') . '/' . __('Unpaid') . ')', '600px', '400px', $chartOpts));
                $chartRows .= wf_TableRow($chartCells);
                $result .= wf_TableBody($chartRows, '100%', 0, '');
            } else {
                $result .= wf_tag('br');
                $result .= $this->renderTableViewStats($timeChartData, __('Time') . ' (' . __('hours') . ')', true);
                $result .= $this->renderTableViewStats($chartData, __('Job types') . ' (' . __('Paid') . '/' . __('Unpaid') . ')', true, true);
                $result .= $this->renderTableViewStats($chartDataCash, __('Money') . ' (' . __('Paid') . '/' . __('Unpaid') . ')', true, true);
            }
        }
        return ($result);
    }

    /**
     * Renders default pie-chart data as table summary
     * 
     * @param array $chartData
     * @param string $title
     * @param bool $filterType
     * @param boolt $replaceValue
     * @return string
     */
    protected function renderTableViewStats($chartData, $title = '', $filterType = true, $replaceValue = false) {
        $result = '';
        $result .= wf_tag('b') . __($title) . wf_tag('b', true);
        if (!empty($chartData)) {
            $cells = wf_TableCell(__('Type'));
            $cells .= wf_TableCell(__('Value'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($chartData as $io => $each) {
                $type = $io;
                $value = $each;

                if ($replaceValue) {
                    if (preg_match('/\([^)]+\)/', $type, $match)) {
                        $value = $match[0];
                        $value = str_replace('(', '', $value);
                        $value = str_replace(')', '', $value);
                    }
                }

                if ($filterType) {
                    $type = str_replace($each, '', $type);
                    $type = preg_replace("/\([^)]+\)/", '', $type);
                }

                $cells = wf_TableCell($type);
                $cells .= wf_TableCell($value);
                $rows .= wf_TableRow($cells, 'row3');
            }
            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
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
    public function payrollRenderSearchDate($datefrom, $dateto, $jobtypeid = '') {
        $datefrom = mysql_real_escape_string($datefrom);
        $dateto = mysql_real_escape_string($dateto);
        $jobtypeid = vf($jobtypeid, 3);

        $currentChartsFlag = (wf_CheckPost(array('prnocharts'))) ? true : false;

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
                    $perEmployeeTimesheets[$each['employeeid']] += $each['hours'];
                } else {
                    $perEmployeeTimesheets[$each['employeeid']] = $each['hours'];
                }
            }
        }


        $jobtypeFilter = (!empty($jobtypeid)) ? "AND `jobtypeid`='" . $jobtypeid . "'" : '';
        $query = "SELECT * from `salary_jobs` WHERE CAST(`date` AS DATE) BETWEEN '" . $datefrom . "' AND  '" . $dateto . "' " . $jobtypeFilter . ";";
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
                    $jobsTmp[$each['employeeid']]['sum'] += $jobPrice;
                    $jobsTmp[$each['employeeid']]['payed'] += $payedSum;
                    $jobsTmp[$each['employeeid']]['time'] += $jobTime;
                }
                $totalPayedSum += $payedSum;
                $totalSum += $jobPrice;
            }
        }

        $cells = wf_TableCell(__('Worker'));
        $cells .= wf_TableCell(__('Wage'));
        $cells .= wf_TableCell(__('Bounty'));
        $cells .= wf_TableCell(__('Work hours'));
        $cells .= wf_TableCell(__('Jobs'));
        $cells .= wf_TableCell(__('Spent time') . ' (' . __('hours') . ')');
        $cells .= wf_TableCell(__('Earned money'));
        $cells .= wf_TableCell(__('Paid'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($this->allEmployee)) {
            foreach ($this->allEmployee as $io => $each) {
                if ($this->checkEmployeeWage($io)) {
                    $cells = wf_TableCell($each);
                    $wage = (isset($this->allWages[$io]['wage'])) ? $this->allWages[$io]['wage'] : __('No');
                    $bounty = (isset($this->allWages[$io]['bounty'])) ? $this->allWages[$io]['bounty'] : __('No');
                    $worktime = (isset($this->allWages[$io]['worktime'])) ? $this->allWages[$io]['worktime'] : __('No');
                    $workerJobsData = (isset($jobsTmp[$io])) ? $jobsTmp[$io] : array('count' => 0, 'sum' => 0, 'payed' => 0, 'time' => 0);

                    $cells .= wf_TableCell($wage);
                    $cells .= wf_TableCell($bounty);
                    $cells .= wf_TableCell(@$perEmployeeTimesheets[$io]);
                    $cells .= wf_TableCell($workerJobsData['count']);
                    $cells .= wf_TableCell(round(($workerJobsData['time'] / 60), 2));
                    $cells .= wf_TableCell($workerJobsData['sum']);
                    $cells .= wf_TableCell($workerJobsData['payed']);
                    $rows .= wf_TableRow($cells, 'row3');

                    $totalWage += $wage;
                    $totalBounty += $bounty;
                    $totalWorkTime += $workerJobsData['time'];
                    $jobCount += $workerJobsData['count'];
                    $employeeCharts[$each] = $workerJobsData['count'];
                    $employeeChartsMoney[$each] = $workerJobsData['sum'];
                }
            }
        }

        $cells = wf_TableCell(__('Total'));
        $cells .= wf_TableCell($totalWage);
        $cells .= wf_TableCell($totalBounty);
        $cells .= wf_TableCell('');
        $cells .= wf_TableCell($jobCount);
        $cells .= wf_TableCell(round(($totalWorkTime / 60), 2));
        $cells .= wf_TableCell($totalSum);
        $cells .= wf_TableCell($totalPayedSum);
        $rows .= wf_TableRow($cells, 'row2');

        $result = wf_TableBody($rows, '100%', 0, '');
        $result .= wf_delimiter();
//charts
        $sumCharts = array(__('Earned money') => $totalSum - $totalPayedSum, __('Paid') => $totalPayedSum);

        if (!$currentChartsFlag) {
            $chartOpts = "chartArea: {  width: '100%', height: '80%' }, legend : {position: 'right', textStyle: {fontSize: 12 }},  pieSliceText: 'value-and-percentage',";

            $cells = wf_TableCell(wf_gcharts3DPie($sumCharts, __('Money'), '400px', '400px', $chartOpts));
            $cells .= wf_TableCell(wf_gcharts3DPie($employeeChartsMoney, __('Money') . ' / ' . __('Worker'), '400px', '400px', $chartOpts));
            $rows = wf_TableRow($cells);
            $cells = wf_TableCell(wf_gcharts3DPie($employeeCharts, __('Jobs'), '400px', '400px', $chartOpts));
            $cells .= wf_TableCell('');
            $rows .= wf_TableRow($cells);
            $result .= wf_TableBody($rows, '100%', 0, '');
        } else {
            $result .= $this->renderTableViewStats($sumCharts, __('Money'), true);
            $result .= $this->renderTableViewStats($employeeChartsMoney, __('Money') . ' / ' . __('Worker'), true);
            $result .= $this->renderTableViewStats($employeeCharts, __('Jobs'), true);
        }

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
        $cells .= wf_TableCell(__('Task'));
        $cells .= wf_TableCell(__('Job type'));
        $cells .= wf_TableCell(__('Factor'));
        $cells .= wf_TableCell(__('Price override'));
        $cells .= wf_TableCell(__('Notes'));
        $cells .= wf_TableCell(__('Paid'));
        $cells .= wf_TableCell(__('Money'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($taskArr)) {
            foreach ($taskArr as $io => $each) {
                if (isset($this->allJobs[$io])) {
                    $jobData = $this->allJobs[$io];
                    if (isset($this->allJobUnits[$jobData['jobtypeid']])) {
                        $unit = $this->unitTypes[$this->allJobUnits[$jobData['jobtypeid']]];
                    } else {
                        $unit = __('No');
                    }
                    $cells = wf_TableCell($jobData['date']);
                    $cells .= wf_TableCell($jobData['taskid']);
                    $cells .= wf_TableCell(@$this->allJobtypes[$jobData['jobtypeid']]);
                    $cells .= wf_TableCell($jobData['factor'] . ' / ' . $unit);
                    $cells .= wf_TableCell($jobData['overprice']);
                    $cells .= wf_TableCell($jobData['note']);
                    $jobPrice = $this->getJobPrice($jobData['id']);
                    $cells .= wf_TableCell(web_bool_led($jobData['state']));
                    $cells .= wf_TableCell($jobPrice);
                    $rows .= wf_TableRow($cells, 'row3');
                    if (!$jobData['state']) {
                        $totalSum = $totalSum + $jobPrice;
                    } else {
                        $payedSum = $payedSum + $jobPrice;
                    }
                } else {
                    show_error(__('Job') . ' [' . $io . '] ' . __('is not loaded'));
                }
            }
        }
        $result = wf_TableBody($rows, '100%', 0, 'sortable');
        $result .= __('Total') . ' ' . __('money') . ': ' . $totalSum . wf_tag('br');
        $result .= __('Processed') . ' ' . __('money') . ': ' . $payedSum;

        return ($result);
    }

    /**
     * Performs job states processing agreement form
     * 
     * @return string
     */
    public function payrollStateProcessingForm() {
        $result = '';
        $result .= wf_HiddenInput('prstateprocessingconfirmed', 'true');
        $tmpArr = array();
        if (wf_CheckPost(array('_prstatecheck'))) {
            if (!empty($_POST['_prstatecheck'])) {
                $checksRaw = $_POST['_prstatecheck'];

                foreach ($checksRaw as $io => $each) {
                    $tmpArr[$io] = $each;
                    $result .= wf_HiddenInput('_prstatecheck[' . $io . ']', 'on');
                }
                $result .= $this->renderJobList($tmpArr);
                $result .= wf_delimiter();
                $result .= wf_Submit(__('Payment confirmation'));
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
            $inputs .= wf_TextInput('facontrolmaxfactor', '> ' . __('Factor'), '', false, '4');
            $inputs .= wf_Submit(__('Show'));
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
        $allTasks = ts_GetAllTasksQuickData();

        if (!empty($this->allJobs)) {
            foreach ($this->allJobs as $io => $each) {
                if ($jobtypeid == $each['jobtypeid']) {
                    if (isset($tmpArr[$each['taskid']])) {
                        $tmpArr[$each['taskid']] += $each['factor'];
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
            $cells .= wf_TableCell(__('Address'));
            $cells .= wf_TableCell(__('Target date'));
            $cells .= wf_TableCell(__('Job type'));
            $cells .= wf_TableCell(__('Who should do'));
            $cells .= wf_TableCell(__('Factor') . ' (' . $unit . ')');
            $rows = wf_TableRow($cells, 'row1');


            foreach ($tmpArr as $taskid => $factorOverflow) {
                if ($factorOverflow > $factor) {
                    $cells = wf_TableCell(wf_Link(self::URL_TS . $taskid, $taskid));
                    $cells .= wf_TableCell(@$allTasks[$taskid]['address']);
                    $cells .= wf_TableCell(@$allTasks[$taskid]['startdate']);
                    $cells .= wf_TableCell(@$this->allJobtypes[$jobtypeid]);
                    $cells .= wf_TableCell(@$this->allEmployeeRaw[$allTasks[$taskid]['employee']]);
                    $cells .= wf_TableCell($factorOverflow);
                    $rows .= wf_TableRow($cells, 'row3');
                }
            }

            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
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
        $inputs .= wf_DatePickerPreset('twfdateto', $curdate, true);
        $inputs .= wf_Submit(__('Show'));
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
                $cells .= wf_TableCell(__('Address'));
                $cells .= wf_TableCell(__('Target date'));
                $cells .= wf_TableCell(__('Job type'));
                $cells .= wf_TableCell(__('Who should do'));
                $cells .= wf_TableCell(__('Done'));

                $rows = wf_TableRow($cells, 'row1');


                foreach ($tmpArr as $io => $eachTask) {
                    $taskid = $eachTask['id'];
                    $cells = wf_TableCell(wf_Link(self::URL_TS . $taskid, $taskid));
                    $cells .= wf_TableCell(@$eachTask['address']);
                    $cells .= wf_TableCell(@$eachTask['startdate']);
                    $cells .= wf_TableCell(@$this->allJobtypes[$eachTask['jobtype']]);
                    $cells .= wf_TableCell(@$this->allEmployeeRaw[$eachTask['employee']]);
                    $cells .= wf_TableCell(web_bool_led($eachTask['status']));

                    $rows .= wf_TableRow($cells, 'row3');
                }

                $result .= wf_TableBody($rows, '100%', 0, 'sortable');
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
        $header .= wf_tag('html', false, '', 'xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru" lang="ru"');
        $header .= wf_tag('head', false);
        $header .= wf_tag('title') . $title . wf_tag('title', true);
        $header .= wf_tag('meta', false, '', 'http-equiv="Content-Type" content="text/html; charset=UTF-8" /');
        $header .= wf_tag('style', false, '', 'type="text/css"');
        $header .= $style;
        $header .= wf_tag('style', true);
        $header .= wf_tag('script', false, '', 'src="modules/jsc/sorttable.js" language="javascript"') . wf_tag('script', true);
        $header .= wf_tag('head', true);
        $header .= wf_tag('body', false);

        $footer = wf_tag('body', true);
        $footer .= wf_tag('html', true);

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
            $result .= '<!--ugly hack to prevent datepicker autoopen --> <input type="text" name="shittyhack" style="width: 0; height: 0; top: -100px; position: absolute;"/>';
            $result .= wf_HiddenInput('newtimesheet', 'true');
            $result .= wf_DatePickerPreset('newtimesheetdate', curdate(), false);

            $headers = wf_TableCell(__('Worker'));
            $headers .= wf_TableCell(__('Hours'));
            $headers .= wf_TableCell(__('Hospitalized'));
            $headers .= wf_TableCell(__('Holidays'));
            $rows = wf_TableRow($headers, 'row1');

            foreach ($this->allEmployee as $employeeid => $employeename) {
                if ($this->checkEmployeeWage($employeeid)) {
                    $defaultWorkTime = (isset($this->allWages[$employeeid]['worktime'])) ? $this->allWages[$employeeid]['worktime'] : 0;
                    $cells = wf_TableCell($employeename);
                    $cells .= wf_TableCell(wf_TextInput('_employeehours[' . $employeeid . ']', '', $defaultWorkTime, false, '2'));
                    $cells .= wf_TableCell(wf_CheckInput('_hospital[' . $employeeid . ']', '', false, false));
                    $cells .= wf_TableCell(wf_CheckInput('_holidays[' . $employeeid . ']', '', false, false));
                    $rows .= wf_TableRow($cells, 'row3');
                }
            }

            $result .= wf_TableBody($rows, '100%', '0', '');
            $result .= wf_tag('br', false);
            $result .= wf_Submit(__('Create'));
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
                log_register('SALARY CREATE TIMESHEET EMPLOYEECOUNT `' . $counter . '`');
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
    public function timesheetsListRender($baseUrl = '') {
        $result = '';
        $tableData = new wf_JqDtHelper();
        $linkBase = (empty($baseUrl)) ? self::URL_ME . '&' . self::URL_TSHEETS : $baseUrl;

        $columns = array(__('Date'), __('Rows'));
        if (wf_CheckGet(array('ajaxtimesheetsdata'))) {
            if (!empty($this->allTimesheetDates)) {
                foreach ($this->allTimesheetDates as $date => $count) {
                    $rawData[] = wf_Link($linkBase . '&showdate=' . $date, $date);
                    $rawData[] = $count;
                    $tableData->addRow($rawData);
                    unset($rawData);
                }
            }
            $tableData->getJson();
        } else {
            $opts = '"order": [[ 0, "desc" ]]';
            $result = wf_JqDtLoader($columns, $linkBase . '&ajaxtimesheetsdata=true', false, __('Timesheets'), 100, $opts);
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
            $headers .= wf_TableCell(__('Hours'));
            $headers .= wf_TableCell(__('Hospitalized'));
            $headers .= wf_TableCell(__('Holidays'));
            $headers .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($headers, 'row1');

            foreach ($timesheetData as $io => $each) {
                $hospitalFlag = ($each['hospital']) ? true : false;
                $holidayFlag = ($each['holiday']) ? true : false;
                $cells = wf_TableCell(@$this->allEmployeeRaw[$each['employeeid']]);
                $cells .= wf_TableCell(wf_TextInput('editemployeehours', '', $each['hours'], false, '2'));
                $cells .= wf_TableCell(wf_CheckInput('edithospital', '', false, $hospitalFlag));
                $cells .= wf_TableCell(wf_CheckInput('editholiday', '', false, $holidayFlag));
                $cells .= wf_TableCell(wf_HiddenInput('edittimesheetid', $each['id']) . wf_Submit(__('Save')));
                $cells = wf_Form('', 'POST', $cells, '');
                $rows .= wf_TableRow($cells, 'row3');
            }

            $result .= wf_TableBody($rows, '100%', '0', '');
            $result .= wf_tag('br', false);
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
        $inputs .= wf_MonthSelector('tsheetprintmonth', __('Month') . ' ', date('m'), false);
        $inputs .= wf_Submit(__('Show'));
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
        $cells .= wf_TableCell(__('Appointment'));
        for ($i = 1; $i <= 31; $i++) {
            $dayCellHeader = ($i < 10) ? '0' . $i : $i;
            $cells .= wf_TableCell($dayCellHeader);
        }
        $cells .= wf_TableCell(__('Total') . ' ' . __('days'));
        $cells .= wf_TableCell(__('Holidays'));
        $cells .= wf_TableCell(__('Total') . ' ' . __('hours'));
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
                    $tmpArr[$each['employeeid']]['totalhours'] += $each['hours'];
                    $tmpArr[$each['employeeid']]['holidays'] += $each['holiday'];
                    $tmpArr[$each['employeeid']]['hospital'] += $each['hospital'];
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
                $cells .= wf_TableCell(@$this->allAppointments[$employeeid]);
                for ($i = 1; $i <= 31; $i++) {
                    $dayCell = (isset($each['day_' . $i])) ? $each['day_' . $i] : 0;
                    $dayCellSuffix = '';
                    if (@$each['dayholiday_' . $i]) {
                        $dayCellSuffix .= wf_tag('sup') . 'v' . wf_tag('sup', true);
                    }
                    if (@$each['dayhospital_' . $i]) {
                        $dayCellSuffix .= wf_tag('sup') . 'h' . wf_tag('sup', true);
                    }
                    $cells .= wf_TableCell($dayCell . $dayCellSuffix);
                }
                $cells .= wf_TableCell($each['totaldays']);
                $cells .= wf_TableCell($each['holidays']);
                $cells .= wf_TableCell($each['totalhours']);
                $rows .= wf_TableRow($cells, 'row3');
            }
        }


        $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        $result .= 'v - ' . __('Holidays') . wf_tag('br');
        $result .= 'h - ' . __('Hospitalized');
        $result = $this->reportPrintable(__('Timesheets') . ' ' . $dateOffset, $result);
        return ($result);
    }

    /**
     * Counts percentage between two values
     * 
     * @param float $valueTotal
     * @param float $value
     * 
     * @return float
     */
    protected function percentValue($valueTotal, $value) {
        $result = 0;
        if ($valueTotal != 0) {
            $result = round((($value * 100) / $valueTotal), 2);
        }
        return ($result);
    }

    /**
     * Renders time duration in seconds into formatted human-readable view
     *      
     * @param int $seconds
     * 
     * @return string
     */
    protected function formatTime($seconds) {
        $init = $seconds;
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds / 60) % 60);
        $seconds = $seconds % 60;

        if ($init < 3600) {
//less than 1 hour
            if ($init < 60) {
//less than minute
                $result = $seconds . ' ' . __('sec.');
            } else {
//more than one minute
                $result = $minutes . ' ' . __('minutes');
            }
        } else {
//more than hour
            $result = $hours . ' ' . __('hour') . ' ' . $minutes . ' ' . __('minutes');
        }
        return ($result);
    }

    /**
     * Returns labor time search form
     * 
     * @return string
     */
    public function ltReportRenderForm() {
        $result = '';
//getting previous state
        $curdateFrom = (wf_CheckPost(array('datefrom'))) ? $_POST['datefrom'] : curdate();
        $curdateTo = (wf_CheckPost(array('dateto'))) ? $_POST['dateto'] : curdate();
        $curJobTypeId = (wf_CheckPost(array('jobtypeid'))) ? $_POST['jobtypeid'] : '-';

        $inputs = wf_DatePickerPreset('datefrom', $curdateFrom) . ' ';
        $inputs .= wf_DatePickerPreset('dateto', $curdateTo) . ' ';
        $jobtypeSelectorParams = array('-' => __('Any'));
        if (!empty($this->allJobtypes)) {
            foreach ($this->allJobtypes as $io => $each) {
                $jobtypeSelectorParams[$io] = $each;
            }
        }
        $inputs .= wf_Selector('jobtypeid', $jobtypeSelectorParams, __('Job type'), $curJobTypeId, false);
        $inputs .= wf_Submit(__('Show'));

        $result = wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Renders labor time report results
     * 
     * @return string
     */
    function ltReportRenderResults() {
        $result = '';
        if (wf_CheckPost(array('datefrom', 'dateto', 'jobtypeid'))) {
            $messages = new UbillingMessageHelper();
            $dateFrom = mysql_real_escape_string($_POST['datefrom']) . ' 00:00:00';
            $dateTo = mysql_real_escape_string($_POST['dateto']) . ' 23:59:59';
            $fromTimestamp = strtotime($dateFrom);
            $toTimestamp = strtotime($dateTo);
            $jobtypeId = mysql_real_escape_string($_POST['jobtypeid']);


//any job type
            if ($jobtypeId == '-') {
                $employeeJobsTmp = array();
                if (!empty($this->allEmployee)) {
                    foreach ($this->allEmployee as $employeeId => $employeeName) {
                        if ($this->checkEmployeeWage($employeeId)) {
                            if (!empty($this->allJobs)) {
                                foreach ($this->allJobs as $jobId => $jobData) {
                                    if ($jobData['employeeid'] == $employeeId) {
                                        $jobTimestamp = strtotime($jobData['date']);
                                        if (($jobTimestamp >= $fromTimestamp) AND ( $jobTimestamp <= $toTimestamp)) {
                                            if (isset($employeeJobsTmp[$employeeId])) {
                                                $jobFactor = $jobData['factor'];
                                                $jobMinutes = @$this->allJobTimes[$jobData['jobtypeid']];
                                                $jobTimeSpent = $jobFactor * $jobMinutes;
                                                $employeeJobsTmp[$employeeId]['timespent'] += $jobTimeSpent;
                                                $employeeJobsTmp[$employeeId]['timesheet'] = 0;
                                            } else {
                                                $jobFactor = $jobData['factor'];
                                                $jobMinutes = @$this->allJobTimes[$jobData['jobtypeid']];
                                                $jobTimeSpent = $jobFactor * $jobMinutes;
                                                $employeeJobsTmp[$employeeId]['timespent'] = $jobTimeSpent;
                                                $employeeJobsTmp[$employeeId]['timesheet'] = 0;
                                            }
                                        }
                                    }
                                }
                            }

                            if (!empty($this->allTimesheets)) {
                                foreach ($this->allTimesheets as $timesheetId => $eachTimesheetData) {

                                    if ($employeeId == $eachTimesheetData['employeeid']) {
                                        $timeSheetTimestamp = strtotime($eachTimesheetData['date']);
                                        if (($timeSheetTimestamp >= $fromTimestamp) AND ( $timeSheetTimestamp <= $toTimestamp)) {
                                            if (isset($employeeJobsTmp[$employeeId])) {
                                                $employeeJobsTmp[$employeeId]['timesheet'] += ($eachTimesheetData['hours'] * 60);
                                            } else {
                                                $employeeJobsTmp[$employeeId]['timesheet'] = ($eachTimesheetData['hours'] * 60);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                if (!empty($employeeJobsTmp)) {
                    $cells = wf_TableCell(__('Employee'));
                    $cells .= wf_TableCell(__('Job type'));
                    $cells .= wf_TableCell(__('Timesheet') . ' (' . __('hours') . ')');
                    $cells .= wf_TableCell(__('Spent time'));
                    $rows = wf_TableRow($cells, 'row1');

                    foreach ($employeeJobsTmp as $io => $each) {
                        $cells = wf_TableCell(@$this->allEmployee[$io]);
                        $cells .= wf_TableCell(__('Any'));
                        $cells .= wf_TableCell(@$each['timesheet'] / 60);
                        $cells .= wf_TableCell(@$this->formatTime(@$each['timespent'] * 60) . ' (' . @$this->percentValue($each['timesheet'], $each['timespent']) . '%)');
                        $rows .= wf_TableRow($cells, 'row3');
                    }

                    $result = wf_TableBody($rows, '100%', 0, 'sortable');
                } else {
                    $result = $messages->getStyledMessage(__('Nothing found'), 'info');
                }
            } else {
//some other job types
                $employeeJobsTmp = array();
                $totalTimeSpent = 0;
                $chartData = array();
                if (!empty($this->allEmployee)) {
                    foreach ($this->allEmployee as $employeeId => $employeeName) {
                        if ($this->checkEmployeeWage($employeeId)) {
                            if (!empty($this->allJobs)) {
                                foreach ($this->allJobs as $jobId => $jobData) {
                                    if ($jobData['jobtypeid'] == $jobtypeId) {
                                        if ($jobData['employeeid'] == $employeeId) {
                                            $jobTimestamp = strtotime($jobData['date']);
                                            if (($jobTimestamp >= $fromTimestamp) AND ( $jobTimestamp <= $toTimestamp)) {
                                                if (isset($employeeJobsTmp[$employeeId])) {
                                                    $jobFactor = $jobData['factor'];
                                                    $jobMinutes = @$this->allJobTimes[$jobData['jobtypeid']];
                                                    $jobTimeSpent = $jobFactor * $jobMinutes;
                                                    $employeeJobsTmp[$employeeId]['timespent'] += $jobTimeSpent;
                                                } else {
                                                    $jobFactor = $jobData['factor'];
                                                    $jobMinutes = @$this->allJobTimes[$jobData['jobtypeid']];
                                                    $jobTimeSpent = $jobFactor * $jobMinutes;
                                                    $employeeJobsTmp[$employeeId]['timespent'] = $jobTimeSpent;
                                                }
                                                $totalTimeSpent += $jobTimeSpent;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                if (!empty($employeeJobsTmp)) {
                    $cells = wf_TableCell(__('Employee'));
                    $cells .= wf_TableCell(__('Job type'));
                    $cells .= wf_TableCell(__('Spent time') . ' (' . __('hours') . ')');
                    $cells .= wf_TableCell(__('Percent of spent time'));
                    $rows = wf_TableRow($cells, 'row1');

                    foreach ($employeeJobsTmp as $io => $each) {
                        $cells = wf_TableCell(@$this->allEmployee[$io]);
                        $cells .= wf_TableCell(@$this->allJobtypes[$jobtypeId]);
                        $cells .= wf_TableCell(@$this->formatTime(@$each['timespent'] * 60));
                        $cells .= wf_TableCell(@$this->percentValue($totalTimeSpent, $each['timespent']) . '%');
                        $rows .= wf_TableRow($cells, 'row3');

//chart data
                        $chartData[$this->allEmployee[$io]] = $each['timespent'];
                    }

                    $result = wf_TableBody($rows, '100%', 0, 'sortable');
                    if (!empty($chartData)) {
                        $chartOptions = '';
                        $result .= wf_gcharts3DPie($chartData, __('Stats') . ' ' . @$this->allJobtypes[$jobtypeId], '800px', '500px', $chartOptions);
                    }
                } else {
                    $result = $messages->getStyledMessage(__('Nothing found'), 'info');
                }
            }
        }

        return ($result);
    }

    /**
     * Renders per year salary report
     * 
     * @return string
     */
    public function renderYearReport() {
        $result = '';
        $monthArr = months_array_localized();
        $showYear = (ubRouting::checkPost('showyear')) ? ubRouting::post('showyear', 'int') : curyear();
        $yearSummaryArr = array();
        $employeSummaryArr = array();
        $jobTypesSummaryArr = array();

        $totalJobPrices = 0;

        foreach ($monthArr as $monthNum => $monthName) {
            $yearSummaryArr[$monthNum]['monthname'] = $monthName;
            $yearSummaryArr[$monthNum]['paid'] = 0;
            $yearSummaryArr[$monthNum]['unpaid'] = 0;
            $yearSummaryArr[$monthNum]['total'] = 0;
            $yearSummaryArr[$monthNum]['jobscount'] = 0;
        }

        $inputs = wf_YearSelectorPreset('showyear', __('Year'), false, $showYear) . ' ';
        $inputs .= wf_Submit(__('Show'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        $result .= wf_delimiter();

        if (!empty($this->allJobs)) {
//debarr($this->allJobs);
            foreach ($this->allJobs as $io => $each) {
                $timestamp = strtotime($each['date']);
                $year = date("Y", $timestamp);
                if ($year == $showYear) {
                    $month = date("m", $timestamp);
                    $jobPrice = $this->getJobPrice($each['id']);
///filling year summary report
                    $jobPaid = ($each['state']) ? true : false;
                    if ($jobPaid) {
                        $yearSummaryArr[$month]['paid'] += $jobPrice;
                    } else {
                        $yearSummaryArr[$month]['unpaid'] += $jobPrice;
                    }
                    $yearSummaryArr[$month]['total'] += $jobPrice;
                    $yearSummaryArr[$month]['jobscount'] ++;
                    $totalJobPrices += $jobPrice;
//filling employee summary
                    if (isset($employeSummaryArr[$each['employeeid']])) {
                        $employeSummaryArr[$each['employeeid']][$month] += $jobPrice;
                    } else {
                        foreach ($monthArr as $monthNum => $monthName) {
                            $employeSummaryArr[$each['employeeid']][$monthNum] = 0;
                        }
                        $employeSummaryArr[$each['employeeid']][$month] += $jobPrice;
                    }
//filling jobtypes summary
                    if (isset($jobTypesSummaryArr[$each['jobtypeid']][$month])) {
                        $jobTypesSummaryArr[$each['jobtypeid']][$month] += $jobPrice;
                    } else {
                        foreach ($monthArr as $monthNum => $monthName) {
                            $jobTypesSummaryArr[$each['jobtypeid']][$monthNum] = 0;
                        }
                        $jobTypesSummaryArr[$each['jobtypeid']][$month] = $jobPrice;
                    }
                }
            }

//rendering year summary report
            if (!empty($yearSummaryArr)) {
                $result .= wf_tag('h3') . __('Employee wages') . ' ' . $showYear . wf_tag('h3', true);
                $cells = wf_TableCell('');
                $cells .= wf_TableCell(__('Month'));
                $cells .= wf_TableCell(__('Jobs'));
                $cells .= wf_TableCell(__('Paid'));
                $cells .= wf_TableCell(__('Unpaid'));
                $cells .= wf_TableCell(__('Total money'));
                $cells .= wf_TableCell(__('Visual'), '50%');
                $rows = wf_TableRow($cells, 'row1');

                foreach ($yearSummaryArr as $io => $each) {
                    $cells = wf_TableCell($io);
                    $cells .= wf_TableCell($each['monthname']);
                    $cells .= wf_TableCell($each['jobscount']);
                    $cells .= wf_TableCell(zb_CashBigValueFormat($each['paid']));
                    $cells .= wf_TableCell(zb_CashBigValueFormat($each['unpaid']));
                    $cells .= wf_TableCell(zb_CashBigValueFormat($each['total']));
                    $cells .= wf_TableCell(web_bar($each['total'], $totalJobPrices), '', '', 'sorttable_customkey="' . $each['total'] . '"');
                    $rows .= wf_TableRow($cells, 'row3');
                }

                $result .= wf_TableBody($rows, '100%', 0, 'sortable');
            }

//rendering per employee year report
            if (!empty($employeSummaryArr)) {
                $cells = wf_TableCell('');
                foreach ($monthArr as $monthNum => $monthName) {
                    $cells .= wf_TableCell($monthName);
                }
                $cells .= wf_TableCell(__('Total'));
                $rows = wf_TableRow($cells, 'row1');

                foreach ($employeSummaryArr as $employeeId => $each) {
                    $employeeSalaryTotal = 0;
                    $cells = wf_TableCell(@$this->allEmployeeRaw[$employeeId]);
                    foreach ($monthArr as $ia => $mn) {
                        $cells .= wf_TableCell(zb_CashBigValueFormat($each[$ia]));
                        $employeeSalaryTotal += $each[$ia];
                    }
                    $cells .= wf_TableCell(zb_CashBigValueFormat($employeeSalaryTotal));
                    $rows .= wf_TableRow($cells, 'row5');
                }
                $result .= wf_tag('h3') . __('Employee') . wf_tag('h3', true);
                $result .= wf_TableBody($rows, '100%', 0, 'sortable');
            }
//rendering jobtypes year summary
            if (!empty($jobTypesSummaryArr)) {
                $cells = wf_TableCell('');
                foreach ($monthArr as $monthNum => $monthName) {
                    $cells .= wf_TableCell($monthName);
                }
                $cells .= wf_TableCell(__('Total'));
                $rows = wf_TableRow($cells, 'row1');

                foreach ($jobTypesSummaryArr as $jobtypeId => $each) {
                    $jobTypePriceTotal = 0;
                    $jobName = (isset($this->allJobtypes[$jobtypeId])) ? $this->allJobtypes[$jobtypeId] : __('Job type') . ' ' . __('Deleted');
                    $cells = wf_TableCell($jobName);
                    foreach ($monthArr as $ia => $mn) {
                        $cells .= wf_TableCell(zb_CashBigValueFormat($each[$ia]));
                        $jobTypePriceTotal += $each[$ia];
                    }
                    $cells .= wf_TableCell(zb_CashBigValueFormat($jobTypePriceTotal));
                    $rows .= wf_TableRow($cells, 'row5');
                }
                $result .= wf_tag('h3') . __('Job types') . wf_tag('h3', true);
                $result .= wf_TableBody($rows, '100%', 0, 'sortable');
//and visual charts for jobtypes
                $chartsOptions = "
                     
            'focusTarget': 'category',
                        'hAxis': {
                        
                        'color': 'none',
                            'baselineColor': 'none',
                    },
                        'vAxis': {
                        'color': 'none',
                            'baselineColor': 'none',
                    },
                        'curveType': 'function',
                        'pointSize': 5,
                        'crosshair': {
                        trigger: 'none'
                    },
                    legend: { position: 'right',  orientation: 'vertical', aligment: 'end' },";
                $charsData = array();
                $columns = array();
                $columns[] = __('Date');

                $jobsInYear = array();


                foreach ($jobTypesSummaryArr as $jobTypeId => $each) {
                    $jobName = (isset($this->allJobtypes[$jobTypeId])) ? $this->allJobtypes[$jobTypeId] : __('Deleted');
                    $columns[] = $jobName;

                    foreach ($each as $mn => $jtsumm) {
                        $jobsInYear[$showYear . '-' . $mn][$jobTypeId] = $jtsumm;
                    }
                }

                $charsData[] = $columns;
                $columns = array();
                if (!empty($jobsInYear)) {
                    foreach ($jobsInYear as $date => $jobStats) {
                        $columns = array();
                        $columns[] = $date;
                        foreach ($jobStats as $jobtypeId => $jtsumm) {
                            $columns[] = $jtsumm;
                        }
                        $charsData[] = $columns;
                    }
                }

                $result .= wf_gchartsLine($charsData, __('Job types') . ' ' . $showYear, '100%', '400px', $chartsOptions);
            }
        } else {
            $messages = new UbillingMessageHelper();
            $result .= $messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
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
                $result .= $messages->getStyledMessage(__('No available workers for wage creation'), 'error');
            } else {
                $result .= $messages->getStyledMessage(__('Total existing employees') . ': ' . sizeof($this->allEmployee), 'info');
            }

            if (empty($this->allJobtypes)) {
                $result .= $messages->getStyledMessage(__('No available job types for pricing'), 'error');
            } else {
                $result .= $messages->getStyledMessage(__('Total existing job types') . ': ' . sizeof($this->allJobtypes), 'info');
            }

            if (empty($this->allJobPrices)) {
                $result .= $messages->getStyledMessage(__('There is no set prices for job types'), 'warning');
            } else {
                $result .= $messages->getStyledMessage(__('Total paid types of work') . ': ' . sizeof($this->allJobPrices), 'info');
            }

            if (empty($this->allWages)) {
                $result .= $messages->getStyledMessage(__('There is no set wages for workers'), 'warning');
            }

            if (empty($this->allJobs)) {
                $result .= $messages->getStyledMessage(__('Not done yet any paid work'), 'warning');
            } else {
                $todayJobs = $this->jobsFilterDate(curdate());
                $todayJobsCount = sizeof($todayJobs);
                $monthJobs = $this->jobsFilterDate(curmonth());
                $monthJobsCount = sizeof($monthJobs);
                $result .= $messages->getStyledMessage(__('Today performed paid work') . ': ' . $todayJobsCount, 'success');
                $result .= $messages->getStyledMessage(__('Month performed paid work') . ': ' . $monthJobsCount, 'success');
                $result .= $messages->getStyledMessage(__('Total performed paid work') . ': ' . sizeof($this->allJobs), 'success');
            }

            if (empty($this->allTimesheetDates)) {
                $result .= $messages->getStyledMessage(__('No filled timesheets'), 'warning');
            } else {
                if (!isset($this->allTimesheetDates[curdate()])) {
                    $result .= $messages->getStyledMessage(__('For today is not filled timesheets'), 'warning');
                } else {
                    $result .= $messages->getStyledMessage(__('For today timesheets is filled'), 'success');
                }

                $result .= $messages->getStyledMessage(__('Filled timesheets for') . ' ' . sizeof($this->allTimesheetDates) . ' ' . __('days'), 'success');
            }


            if (!empty($result)) {
                show_window(__('Stats'), $result);
                zb_BillingStats(true);
            }
        }
    }

}

?>