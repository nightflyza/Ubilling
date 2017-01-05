<?php

if (cfr('TASKREPORT')) {

    class TasksReport {

        /**
         * System alter config stored as key=>value
         *
         * @var array
         */
        protected $altCfg = array();

        /**
         * jobtypes IDs for report
         *
         * @var array
         */
        protected $reportJobtypes = array();

        /**
         * Signup tasks jobtype IDs array
         *
         * @var array
         */
        protected $signupJobtypeId = array();

        /**
         * Available jobtypes data as jobtypeid=>data
         *
         * @var array
         */
        protected $jobtypes = array();

        /**
         * Report date from 
         *
         * @var string
         */
        protected $dateFrom = '';

        /**
         * Report date to
         *
         * @var string
         */
        protected $dateTo = '';

        /**
         * Contains all tasks with reportJobtypes perfromed between search intervals
         *
         * @var array
         */
        protected $allTasks = array();

        /**
         * System messages helper object placeholder
         *
         * @var object
         */
        protected $messages = '';

        /**
         * Warehouse usage flag
         *
         * @var bool
         */
        protected $warehouseFlag = false;

        /**
         * Salary usage flag
         *
         * @var bool
         */
        protected $salaryFlag = false;

        /**
         * Connection details usage flag
         *
         * @var bool
         */
        protected $condetFlag = false;

        /**
         * Warehouse object placeholder
         *
         * @var object
         */
        protected $warehouse = '';

        /**
         * Salary object placeholder
         *
         * @var object
         */
        protected $salary = '';

        /**
         * Telepathy object placeholder
         *
         * @var object
         */
        protected $telepathy = '';

        /**
         * Available user contracts
         *
         * @var array
         */
        protected $userContracts = array();

        /**
         * Available tariff prices
         *
         * @var array
         */
        protected $tariffPrices = array();

        /**
         * Contains current users tariffs
         *
         * @var array
         */
        protected $userTariffs = array();

        /**
         * Contains all signup payments as login=>summ
         *
         * @var array
         */
        protected $signupPayments = array();

        /**
         * Contains tagids for notes column
         *
         * @var array
         */
        protected $notesTagids = array();

        /**
         * Contains tags assigned for users
         *
         * @var array
         */
        protected $userTags = array();

        /**
         * Contains all available tagtypes as id=>name
         *
         * @var array
         */
        protected $tagTypes = array();

        /**
         * Contains basic URL for task editing
         */
        const URL_TASK = '?module=taskman&edittask=';

        /**
         * Contains basic URL for user profile
         */
        const URL_USER = '?module=userprofile&username=';

        /**
         * Creates new TasksReport object instance
         * 
         * @return void
         */
        public function __construct() {
            $this->loadConfigs();
            $this->preprocessConfigs();
            $this->initMessages();
            $this->loadJobtypes();
            $this->setDates();
            $this->loadTasks();
            $this->loadTariffsData();
            $this->loadContracts();
            $this->loadSignupPayments();
            $this->loadTagsData();
            $this->initWarehouse();
            $this->initSalary();
            $this->initTelepathy();
        }

        /**
         * Loads main configuration options
         * 
         * @global object $ubillingConfig
         * 
         * @return void
         */
        protected function loadConfigs() {
            global $ubillingConfig;
            $this->altCfg = $ubillingConfig->getAlter();
        }

        /**
         * Preprocess config options into protected properties
         * 
         * @return void
         */
        protected function preprocessConfigs() {
            if (!empty($this->altCfg['TASKREPORT_JOBTYPES'])) {
                $jobtypesTmp = explode(',', $this->altCfg['TASKREPORT_JOBTYPES']);
                $this->reportJobtypes = array_flip($jobtypesTmp);
            }

            if (!empty($this->altCfg['TASKREPORT_SIGNUPJOBTYPES'])) {
                $signupJobtypeIdtmp = explode(',', $this->altCfg['TASKREPORT_SIGNUPJOBTYPES']);
                $this->signupJobtypeId = array_flip($signupJobtypeIdtmp);
            }

            if ($this->altCfg['WAREHOUSE_ENABLED']) {
                $this->warehouseFlag = true;
            }

            if ($this->altCfg['SALARY_ENABLED']) {
                $this->salaryFlag = true;
            }

            if ($this->altCfg['CONDET_ENABLED']) {
                $this->condetFlag = true;
            }

            if ($this->altCfg['TASKREPORT_NOTESTAGIDS']) {
                $notesTagidsTmp = explode(',', $this->altCfg['TASKREPORT_NOTESTAGIDS']);
                $this->notesTagids = array_flip($notesTagidsTmp);
            }
        }

        /**
         * Sets current report dates
         * 
         * @return void
         */
        protected function setDates() {
            if (wf_CheckPost(array('dateto', 'datefrom'))) {
                $this->dateFrom = mysql_real_escape_string($_POST['datefrom']);
                $this->dateTo = mysql_real_escape_string($_POST['dateto']);
            } else {
                $this->dateFrom = date("Y-m") . '-01';
                $this->dateTo = curdate();
            }
        }

        /**
         * Loads available jobtypes data
         * 
         * @return void
         */
        protected function loadJobtypes() {
            $query = "SELECT * from `jobtypes`";
            $all = simple_queryall($query);
            if (!empty($all)) {
                foreach ($all as $io => $each) {
                    $this->jobtypes[$each['id']] = $each;
                }
            }
        }

        /**
         * Loads available users contracts
         * 
         * @return void
         */
        protected function loadContracts() {
            $this->userContracts = array_flip(zb_UserGetAllContracts());
        }

        /**
         * Inits system messages object
         * 
         * @return void
         */
        protected function initMessages() {
            $this->messages = new UbillingMessageHelper();
        }

        /**
         * Inits warehouse object instance
         * 
         * @return void
         */
        protected function initWarehouse() {
            if ($this->warehouseFlag) {
                $this->warehouse = new Warehouse();
            }
        }

        /**
         * Inits salary object instance
         * 
         * @return void
         */
        protected function initSalary() {
            if ($this->salaryFlag) {
                $this->salary = new Salary();
            }
        }

        /**
         * Inits telepathy object
         * 
         * @return void
         */
        protected function initTelepathy() {
            $this->telepathy = new Telepathy(false, true);
        }

        /**
         * Loads tasks for report in selected time range, into protected property for further usage
         * 
         * @return void
         */
        protected function loadTasks() {
            $query = "SELECT * from `taskman` WHERE `startdate`  BETWEEN '" . $this->dateFrom . "' AND '" . $this->dateTo . "'";
            $all = simple_queryall($query);
            if (!empty($all)) {
                foreach ($all as $io => $each) {
                    if (isset($this->reportJobtypes[$each['jobtype']])) {
                        $this->allTasks[$each['id']] = $each;
                    }
                }
            }
        }

        /**
         * Loads users tariffs and tariffs prices data
         * 
         * @return void
         */
        protected function loadTariffsData() {
            $this->tariffPrices = zb_TariffGetPricesAll();
            $this->userTariffs = zb_TariffsGetAllUsers();
        }

        /**
         * Loads all signup payments from database into protected prop
         * 
         * @return void
         */
        protected function loadSignupPayments() {
            $cahtypeId = vf($this->altCfg['TASKREPORT_SIGPAYID'], 3);
            if ($cahtypeId) {
                //natural payments
                $query = "SELECT * from `payments` WHERE `cashtypeid`='" . $cahtypeId . "';";
                $all = simple_queryall($query);
                if (!empty($all)) {
                    foreach ($all as $io => $each) {
                        if (isset($this->signupPayments[$each['login']])) {
                            $this->signupPayments[$each['login']]+=$each['summ'];
                        } else {
                            $this->signupPayments[$each['login']] = $each['summ'];
                        }
                    }
                }
            } else {
                //payments from condet
                if ($this->altCfg['CONDET_ENABLED']) {
                    $query = "SELECT * from `condet`";
                    $all = simple_queryall($query);
                    if (!empty($all)) {
                        foreach ($all as $io => $each) {
                            $this->signupPayments[$each['login']] = $each['price'];
                        }
                    }
                }
            }
        }

        /**
         * Loads and do some preprocessing tags and tagtypes data
         * 
         * @return void
         */
        protected function loadTagsData() {
            if (!empty($this->notesTagids)) {
                //preprocessing tagtypes
                $query = "SELECT * from `tagtypes`";
                $all = simple_queryall($query);
                if (!empty($all)) {
                    foreach ($all as $io => $each) {
                        $this->tagTypes[$each['id']] = $each['tagname'];
                    }
                }

                //preprocessing usertags
                $query = "SELECT * from `tags`";
                $all = simple_queryall($query);
                if (!empty($all)) {
                    foreach ($all as $io => $each) {
                        if (isset($this->notesTagids[$each['tagid']])) {
                            if (isset($this->userTags[$each['login']])) {
                                $this->userTags[$each['login']].=@$this->tagTypes[$each['tagid']] . ' ';
                            } else {
                                $this->userTags[$each['login']] = @$this->tagTypes[$each['tagid']] . ' ';
                            }
                        }
                    }
                }
            }
        }

        /**
         * Returns user signup price by its login
         * 
         * @param string $login
         * 
         * @return float
         */
        protected function getSignupPrice($login) {
            $result = 0;
            if (isset($this->signupPayments[$login])) {
                $result = $this->signupPayments[$login];
            }
            return ($result);
        }

        /**
         * Renders default from-to date controls form
         * 
         * @return string
         */
        public function renderDatesForm() {
            $result = '';
            $inputs = __('Date') . ' ' . wf_DatePickerPreset('datefrom', $this->dateFrom, true) . ' ' . __('From') . ' ';
            $inputs.= wf_DatePickerPreset('dateto', $this->dateTo, true) . ' ' . __('To') . ' ';
            $inputs.= wf_Submit(__('Show'));
            $result = wf_Form('', 'POST', $inputs, 'glamour');
            return ($result);
        }

        /**
         * Renders report by preloaded data
         * 
         * @return string
         */
        public function renderReport() {
            $result = '';
            $count = 1;
            $signupsTotalSpent = 0;
            $signupsTotalPayments = 0;
            $signupsWarehouseTotalSpent = 0;
            $signupsSalaryTotalSpent = 0;
            $otherTasksTotalSpent = 0;
            $signupsTotalTariffPrices = 0;
            $tasksSummary = array();

            if (!empty($this->allTasks)) {
                $cells = wf_TableCell('№');
                $cells.= wf_TableCell(__('ID'));
                $cells.= wf_TableCell(__('Done'));
                $cells.= wf_TableCell(__('Contract'));
                $cells.= wf_TableCell(__('Address'));
                $cells.= wf_TableCell(__('Type'));
                if ($this->warehouseFlag OR $this->salaryFlag) {
                    $cells.= wf_TableCell(__('Spent on task'));
                }
                $cells.= wf_TableCell(__('Paid by user'));
                $cells.= wf_TableCell(__('Tariff fee'));
                $cells.= wf_TableCell(__('Notes'));
                $rows = wf_TableRow($cells, 'row1');

                foreach ($this->allTasks as $io => $each) {
                    $typeColor = (!empty($this->jobtypes[$each['jobtype']]['jobcolor'])) ? $this->jobtypes[$each['jobtype']]['jobcolor'] : '';
                    if (!empty($typeColor)) {
                        $styleStart = wf_tag('font', false, '', 'color="' . $typeColor . '"');
                        $styleEnd = wf_tag('font', true);
                    } else {
                        $styleStart = '';
                        $styleEnd = '';
                    }

                    $userLogin = '';
                    $userLink = '';
                    $userTariff = '';
                    $tariffPrice = '';
                    if (!empty($each['login'])) {
                        $userLogin = $each['login'];
                        @$userContract = $this->userContracts[$userLogin];
                        $userLink = wf_Link(self::URL_USER . $userLogin, web_profile_icon() . ' ' . $userContract, false);
                    } else {
                        $userLogin = $this->telepathy->getLogin($each['address']);
                        @$userContract = $this->userContracts[$userLogin];
                        $guessed = wf_tag('sup') . wf_tag('abbr', false, '', 'title="' . __('telepathically guessed') . '"') . '(?)' . wf_tag('abbr', true) . wf_tag('sup', true);
                        if (!empty($userLogin)) {
                            $userLink = wf_Link(self::URL_USER . $userLogin, web_profile_icon() . ' ' . $userContract . $guessed, false);
                        }
                    }

                    if (!empty($userLogin)) {
                        $userTariff = $this->userTariffs[$userLogin];
                        if ((!empty($userTariff)) AND ( $userTariff != '*_NO_TARIFF_*')) {
                            $tariffPrice = $this->tariffPrices[$userTariff];
                        }
                    }

                    $cells = wf_TableCell($count);
                    $cells.= wf_TableCell(wf_Link(self::URL_TASK . $each['id'], $each['id'], false));
                    $cells.= wf_TableCell(web_bool_led($each['status']), '', '', 'sorttable_customkey="' . $each['status'] . '"');
                    $cells.= wf_TableCell($userLink);
                    $cells.= wf_TableCell($styleStart . $each['address'] . $styleEnd);
                    $cells.= wf_TableCell($styleStart . $this->jobtypes[$each['jobtype']]['jobname'] . $styleEnd);
                    if ($this->warehouseFlag OR $this->salaryFlag) {
                        $warehouseSpent = 0;
                        $salarySpent = 0;
                        if ($this->warehouseFlag) {
                            $warehouseSpent = $this->warehouse->taskMaterialsSpentPrice($each['id']);
                        }
                        if ($this->salaryFlag) {
                            $salarySpent = $this->salary->getTaskPrice($each['id']);
                        }
                        $cells.= wf_TableCell(($warehouseSpent + $salarySpent));
                    }
                    //detecting signup price and some counters only for signup tasks
                    if (isset($this->signupJobtypeId[$each['jobtype']])) {
                        $signupPrice = $this->getSignupPrice($userLogin);
                        $signupsSalaryTotalSpent+=$salarySpent;
                        $signupsWarehouseTotalSpent+=$warehouseSpent;
                        $signupsTotalSpent+=$warehouseSpent + $salarySpent;
                        $signupsTotalPayments+=$signupPrice;
                        $signupsTotalTariffPrices+=$tariffPrice;
                    } else {
                        //other task types
                        $signupPrice = '';
                        $otherTasksTotalSpent+=$warehouseSpent + $salarySpent;
                    }
                    $cells.= wf_TableCell($signupPrice);
                    $cells.= wf_TableCell($tariffPrice);

                    $cells.= wf_TableCell(@$this->userTags[$userLogin]);
                    $rows.= wf_TableRow($cells, 'row3');

                    //report summary
                    if (isset($tasksSummary[$each['jobtype']])) {
                        $tasksSummary[$each['jobtype']]['count'] ++;
                        $tasksSummary[$each['jobtype']]['warehouse']+=$warehouseSpent;
                        $tasksSummary[$each['jobtype']]['salary']+=$salarySpent;
                        $tasksSummary[$each['jobtype']]['sigprice']+=$signupPrice;
                    } else {
                        $tasksSummary[$each['jobtype']]['tasktype'] = $this->jobtypes[$each['jobtype']]['jobname'];
                        $tasksSummary[$each['jobtype']]['count'] = 1;
                        $tasksSummary[$each['jobtype']]['warehouse'] = $warehouseSpent;
                        $tasksSummary[$each['jobtype']]['salary'] = $salarySpent;
                        $tasksSummary[$each['jobtype']]['sigprice'] = $signupPrice;
                    }

                    $count++;
                }


                $result = wf_TableBody($rows, '100%', 0, 'sortable');
                $result.= wf_tag('br');

                //detailed tasks summary
                if (!empty($tasksSummary)) {
                    $cells = wf_TableCell(__('Job type'));
                    $cells.= wf_TableCell(__('Count'));
                    $cells.= wf_TableCell(__('Spent materials'));
                    $cells.= wf_TableCell(__('Paid staff'));
                    $cells.= wf_TableCell(__('Signup payments total'));
                    $cells.= wf_TableCell(__('Profit'));
                    $rows = wf_TableRow($cells, 'row1');

                    foreach ($tasksSummary as $io => $each) {
                        $cells = wf_TableCell($each['tasktype']);
                        $cells.= wf_TableCell($each['count']);
                        $cells.= wf_TableCell($each['warehouse']);
                        $cells.= wf_TableCell($each['salary']);
                        $cells.= wf_TableCell($each['sigprice']);
                        $cells.= wf_TableCell(($each['sigprice'] - ($each['warehouse'] + $each['salary'])));
                        $rows.= wf_TableRow($cells, 'row3');
                    }

                    $result.= wf_TableBody($rows, '100%', 0, 'sortable');
                    $result.= wf_tag('br');

                    //appending totals counters
                    $cells = wf_TableCell(__('Counter'));
                    $cells.= wf_TableCell(__('Money'));
                    $rows = wf_TableRow($cells, 'row1');

                    $cells = wf_TableCell(__('Total spent on signups'), '', 'row2');
                    $cells.= wf_TableCell($signupsTotalSpent);
                    $rows.= wf_TableRow($cells, 'row3');

                    $cells = wf_TableCell(__('Total spent materials for signups'), '', 'row2');
                    $cells.= wf_TableCell($signupsWarehouseTotalSpent);
                    $rows.= wf_TableRow($cells, 'row3');

                    $cells = wf_TableCell(__('Total spent salary for signups'), '', 'row2');
                    $cells.= wf_TableCell($signupsSalaryTotalSpent);
                    $rows.= wf_TableRow($cells, 'row3');

                    $cells = wf_TableCell(__('Signup payments total'), '', 'row2');
                    $cells.= wf_TableCell($signupsTotalPayments);
                    $rows.= wf_TableRow($cells, 'row3');

                    $cells = wf_TableCell(__('Total spent for other tasks'), '', 'row2');
                    $cells.= wf_TableCell($otherTasksTotalSpent);
                    $rows.= wf_TableRow($cells, 'row3');

                    $signupsProfit = ($signupsTotalTariffPrices + $signupsTotalPayments) - $signupsTotalSpent;
                    $cells = wf_TableCell(__('Profit from users signups'), '', 'row2');
                    $cells.= wf_TableCell($signupsProfit);
                    $rows.= wf_TableRow($cells, 'row3');

                    $result.= wf_TableBody($rows, '50%', 0, 'sortable');
                }
            } else {
                $result = $this->messages->getStyledMessage(__('Nothing found'), 'info');
            }
            return ($result);
        }

    }

    $report = new TasksReport();
    show_window(__('Search'), $report->renderDatesForm());
    show_window(__('Tasks report'), $report->renderReport());
} else {
    show_error(__('Access denied'));
}
?>