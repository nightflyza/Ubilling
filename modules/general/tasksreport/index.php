<?php

if (cfr('TASKREPORT')) {
    $altCfg = $ubillingConfig->getAlter();
    if ($altCfg['TASKREPORT_ENABLED']) {

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
             * Salary tax rates multiplier
             *
             * @var float
             */
            protected $salaryMultiplier = 0;

            /**
             * System caching object placeholder
             *
             * @var object
             */
            protected $cache = '';

            /**
             * Contains basic URL for task editing
             */
            const URL_TASK = '?module=taskman&edittask=';

            /**
             * Contains basic URL for user profile
             */
            const URL_USER = '?module=userprofile&username=';

            /**
             * Basic module URL
             */
            const URL_ME = '?module=tasksreport';

            /**
             * Printable temp file path
             */
            const PRINT_PATH = 'exports/taskreportprint.html';

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
                $this->initCache();
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

                if (isset($this->altCfg['TASKREPORT_SALARY_MULTIPLIER'])) {
                    $this->salaryMultiplier = $this->altCfg['TASKREPORT_SALARY_MULTIPLIER'];
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
                    if (wf_CheckGet(array('dateto', 'datefrom'))) {
                        $this->dateFrom = mysql_real_escape_string($_GET['datefrom']);
                        $this->dateTo = mysql_real_escape_string($_GET['dateto']);
                    } else {
                        $this->dateFrom = date("Y-m") . '-01';
                        $this->dateTo = curdate();
                    }
                }
            }

            /**
             * Returns dates from object instance
             * 
             * @return array
             */
            public function getDates() {
                $result = array('from' => $this->dateFrom, 'to' => $this->dateTo);
                return ($result);
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
             * Inits system cache for further usage
             * 
             * @return void
             */
            protected function initCache() {
                $this->cache = new UbillingCache();
            }

            /**
             * Cleans some cached data
             * 
             * @return void
             */
            public function cacheCleanup() {
                $this->cache->delete('TASKSJOBS');
                $this->cache->delete('TASKSOUTS');
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
                                $this->signupPayments[$each['login']] += $each['summ'];
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
                                    $this->userTags[$each['login']] .= @$this->tagTypes[$each['tagid']] . ' ';
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
                    if (!is_numeric($result)) {
                        $result = 0;
                    }
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
                $inputs .= wf_DatePickerPreset('dateto', $this->dateTo, true) . ' ' . __('To') . ' ';
                $inputs .= wf_Submit(__('Show'));
                $result = wf_Form('', 'POST', $inputs, 'glamour');
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
                $header = wf_tag('html', false);
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
                die($data);
            }

            /**
             * Renders report by preloaded data
             * 
             * @return string
             */
            public function renderReport() {
                $result = '';
                $resultPrintable = '';
                $count = 1;
                $signupsTotalSpent = 0;
                $signupsTotalPayments = 0;
                $signupsWarehouseTotalSpent = 0;
                $signupsSalaryTotalSpent = 0;
                $otherTasksTotalSpent = 0;
                $signupsTotalTariffPrices = 0;
                $tasksSummary = array();
                $warehouseSpent = 0;
                $salarySpent = 0;
                $signupMaterialsSpent = array();
                $nonSignupsMaterialsSpent = array();

                if (!empty($this->allTasks)) {
                    $cells = wf_TableCell('№');
                    $cells .= wf_TableCell(__('ID'));
                    $cells .= wf_TableCell(__('Done'));
                    $cells .= wf_TableCell(__('Contract'));
                    $cells .= wf_TableCell(__('Address'));
                    $cells .= wf_TableCell(__('Type'));
                    if ($this->warehouseFlag OR $this->salaryFlag) {
                        $cells .= wf_TableCell(__('Spent on task'));
                    }
                    $cells .= wf_TableCell(__('Paid by user'));
                    $cells .= wf_TableCell(__('Tariff fee'));
                    $cells .= wf_TableCell(__('Notes'));
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
                        $tariffPrice = 0;
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
                            @$userTariff = $this->userTariffs[$userLogin];
                            if ((!empty($userTariff)) AND ( $userTariff != '*_NO_TARIFF_*')) {
                                $tariffPrice = $this->tariffPrices[$userTariff];
                            }
                        }

                        $cells = wf_TableCell($count);
                        $cells .= wf_TableCell(wf_Link(self::URL_TASK . $each['id'], $each['id'], false));
                        $cells .= wf_TableCell(web_bool_led($each['status']), '', '', 'sorttable_customkey="' . $each['status'] . '"');
                        $cells .= wf_TableCell($userLink);
                        $cells .= wf_TableCell($styleStart . $each['address'] . $styleEnd);
                        $cells .= wf_TableCell($styleStart . $this->jobtypes[$each['jobtype']]['jobname'] . $styleEnd);
                        if ($this->warehouseFlag OR $this->salaryFlag) {
                            if ($this->warehouseFlag) {
                                $warehouseSpentRaw = $this->warehouse->taskMaterialsSpentPrice($each['id']);
                                //saving materials spent on signups only
                                if (isset($this->signupJobtypeId[$each['jobtype']])) {
                                    if (isset($warehouseSpentRaw['items'])) {
                                        $signupMaterialsSpent[] = $warehouseSpentRaw['items'];
                                    }
                                } else {
                                    if (isset($warehouseSpentRaw['items'])) {
                                        $nonSignupsMaterialsSpent[] = $warehouseSpentRaw['items'];
                                    }
                                }
                                $warehouseSpent = $warehouseSpentRaw['sum'];
                            }
                            if ($this->salaryFlag) {
                                if ($this->salaryMultiplier) {
                                    $salarySpent = $this->salary->getTaskPrice($each['id']) * $this->salaryMultiplier;
                                } else {
                                    $salarySpent = $this->salary->getTaskPrice($each['id']);
                                }
                            }
                            $totalTaskSpent = $warehouseSpent + $salarySpent;
                            $cells .= wf_TableCell($totalTaskSpent);
                        }
                        //detecting signup price and some counters only for signup tasks
                        if (isset($this->signupJobtypeId[$each['jobtype']])) {
                            $signupPrice = $this->getSignupPrice($userLogin);
                            $signupsSalaryTotalSpent += $salarySpent;
                            $signupsWarehouseTotalSpent += $warehouseSpent;
                            $signupsTotalSpent += $warehouseSpent + $salarySpent;
                            $signupsTotalPayments += $signupPrice;
                            if (!is_numeric($tariffPrice)) {
                                $tariffPrice = 0; //fix of non detected user tariffs pricing
                            }
                            $signupsTotalTariffPrices += $tariffPrice;
                        } else {
                            //other task types
                            $signupPrice = 0;
                            $otherTasksTotalSpent += $warehouseSpent + $salarySpent;
                        }
                        $cells .= wf_TableCell($signupPrice);
                        $cells .= wf_TableCell($tariffPrice);

                        $cells .= wf_TableCell(@$this->userTags[$userLogin]);

                        //row coloring
                        if (empty($userLogin)) {
                            $rowColor = 'undone';
                        } else {
                            if (@$totalTaskSpent > ($signupPrice + $tariffPrice)) {
                                $rowColor = 'ukvbankstadup';
                            } else {
                                $rowColor = 'row3';
                            }
                        }
                        //back coloring for non signup types
                        if (!isset($this->signupJobtypeId[$each['jobtype']])) {
                            $rowColor = 'row3';
                        }


                        $rows .= wf_TableRow($cells, $rowColor);

                        //report summary
                        if (isset($tasksSummary[$each['jobtype']])) {
                            $tasksSummary[$each['jobtype']]['count'] ++;
                            $tasksSummary[$each['jobtype']]['warehouse'] += $warehouseSpent;
                            $tasksSummary[$each['jobtype']]['salary'] += $salarySpent;
                            $tasksSummary[$each['jobtype']]['sigprice'] += $signupPrice;
                            if (isset($this->signupJobtypeId[$each['jobtype']])) {
                                $tasksSummary[$each['jobtype']]['tariffprice'] += $tariffPrice;
                            }
                        } else {
                            $tasksSummary[$each['jobtype']]['tasktype'] = $this->jobtypes[$each['jobtype']]['jobname'];
                            $tasksSummary[$each['jobtype']]['count'] = 1;
                            $tasksSummary[$each['jobtype']]['warehouse'] = $warehouseSpent;
                            $tasksSummary[$each['jobtype']]['salary'] = $salarySpent;
                            $tasksSummary[$each['jobtype']]['sigprice'] = $signupPrice;
                            if (isset($this->signupJobtypeId[$each['jobtype']])) {
                                $tasksSummary[$each['jobtype']]['tariffprice'] = $tariffPrice;
                            } else {
                                $tasksSummary[$each['jobtype']]['tariffprice'] = 0;
                            }
                        }

                        $count++;
                    }

                    $result = wf_TableBody($rows, '100%', 0, 'sortable');
                    $result .= wf_tag('br');

                    //detailed tasks summary
                    if (!empty($tasksSummary)) {
                        $cells = wf_TableCell(__('Job type'));
                        $cells .= wf_TableCell(__('Count'));
                        if ($this->warehouseFlag OR $this->salaryFlag) {
                            if ($this->warehouseFlag) {
                                $cells .= wf_TableCell(__('Spent materials'));
                            }
                            if ($this->salaryFlag) {
                                $cells .= wf_TableCell(__('Paid staff'));
                            }
                        }
                        $cells .= wf_TableCell(__('Tariff fee'));
                        $cells .= wf_TableCell(__('Signup payments total'));

                        $cells .= wf_TableCell(__('Profit'));
                        $rows = wf_TableRow($cells, 'row1');

                        foreach ($tasksSummary as $io => $each) {
                            $cells = wf_TableCell($each['tasktype']);
                            $cells .= wf_TableCell($each['count']);
                            if ($this->warehouseFlag OR $this->salaryFlag) {
                                if ($this->warehouseFlag) {
                                    $cells .= wf_TableCell($each['warehouse']);
                                }
                                if ($this->salaryFlag) {
                                    $cells .= wf_TableCell($each['salary']);
                                }
                            }
                            $cells .= wf_TableCell($each['tariffprice']);
                            $cells .= wf_TableCell($each['sigprice']);

                            $cells .= wf_TableCell((($each['tariffprice'] + $each['sigprice']) - ($each['warehouse'] + $each['salary'])));
                            $rows .= wf_TableRow($cells, 'row3');
                        }

                        $result .= wf_TableBody($rows, '100%', 0, 'sortable');
                        $result .= wf_tag('br');

                        //signup warehouse items spent summary stats
                        if (!empty($signupMaterialsSpent)) {
                            $warehouseSignupStats = array();
                            foreach ($signupMaterialsSpent as $io => $flow) {
                                if (!empty($flow)) {
                                    foreach ($flow as $ia => $each) {
                                        if (isset($warehouseSignupStats[$each['itemtypeid']])) {
                                            $warehouseSignupStats[$each['itemtypeid']]['count'] += $each['count'];
                                            $warehouseSignupStats[$each['itemtypeid']]['price'] += $each['price'] * $each['count'];
                                        } else {
                                            $warehouseSignupStats[$each['itemtypeid']]['count'] = $each['count'];
                                            $warehouseSignupStats[$each['itemtypeid']]['price'] = $each['price'] * $each['count'];
                                        }
                                    }
                                }
                            }

                            $cells = wf_TableCell(__('Category'));
                            $cells .= wf_TableCell(__('Warehouse item types'));
                            $cells .= wf_TableCell(__('Count'));
                            $cells .= wf_TableCell(__('Money'));
                            $rows = wf_TableRow($cells, 'row1');

                            foreach ($warehouseSignupStats as $io => $each) {
                                $cells = wf_TableCell($this->warehouse->itemtypeGetCategory($io));
                                $cells .= wf_TableCell($this->warehouse->itemtypeGetName($io));
                                $cells .= wf_TableCell($each['count'] . ' ' . $this->warehouse->itemtypeGetUnit($io));
                                $cells .= wf_TableCell($each['price']);
                                $rows .= wf_TableRow($cells, 'row3');
                            }


                            $resultPrintable .= wf_tag('b') . __('Total spent materials for signups') . wf_tag('b', true);
                            $resultPrintable .= wf_TableBody($rows, '100%', 0, 'sortable');
                            $resultPrintable .= wf_tag('br');
                        }

                        //warehouse items spent on non-signup tasks
                        if (!empty($nonSignupsMaterialsSpent)) {
                            $warehouseNonSignupStats = array();
                            foreach ($nonSignupsMaterialsSpent as $io => $flow) {
                                if (!empty($flow)) {
                                    foreach ($flow as $ia => $each) {
                                        if (isset($warehouseNonSignupStats[$each['itemtypeid']])) {
                                            $warehouseNonSignupStats[$each['itemtypeid']]['count'] += $each['count'];
                                            $warehouseNonSignupStats[$each['itemtypeid']]['price'] += $each['price'] * $each['count'];
                                        } else {
                                            $warehouseNonSignupStats[$each['itemtypeid']]['count'] = $each['count'];
                                            $warehouseNonSignupStats[$each['itemtypeid']]['price'] = $each['price'] * $each['count'];
                                        }
                                    }
                                }
                            }

                            $cells = wf_TableCell(__('Category'));
                            $cells .= wf_TableCell(__('Warehouse item types'));
                            $cells .= wf_TableCell(__('Count'));
                            $cells .= wf_TableCell(__('Money'));
                            $rows = wf_TableRow($cells, 'row1');

                            foreach ($warehouseNonSignupStats as $io => $each) {
                                $cells = wf_TableCell($this->warehouse->itemtypeGetCategory($io));
                                $cells .= wf_TableCell($this->warehouse->itemtypeGetName($io));
                                $cells .= wf_TableCell($each['count'] . ' ' . $this->warehouse->itemtypeGetUnit($io));
                                $cells .= wf_TableCell($each['price']);
                                $rows .= wf_TableRow($cells, 'row3');
                            }


                            $resultPrintable .= wf_tag('b') . __('Total spent for other tasks') . ' (' . __('From warehouse storage') . ')' . wf_tag('b', true);
                            $resultPrintable .= wf_TableBody($rows, '100%', 0, 'sortable');
                            $resultPrintable .= wf_tag('br');
                            $result .= $resultPrintable;
                        }

                        //appending totals counters
                        $cells = wf_TableCell(__('Counter'));
                        $cells .= wf_TableCell(__('Money'));
                        $rows = wf_TableRow($cells, 'row1');

                        $cells = wf_TableCell(__('Total spent on signups'), '', 'row2');
                        $cells .= wf_TableCell($signupsTotalSpent);
                        $rows .= wf_TableRow($cells, 'row3');

                        $cells = wf_TableCell(__('Total spent materials for signups'), '', 'row2');
                        $cells .= wf_TableCell($signupsWarehouseTotalSpent);
                        $rows .= wf_TableRow($cells, 'row3');

                        $cells = wf_TableCell(__('Total spent salary for signups'), '', 'row2');
                        $cells .= wf_TableCell($signupsSalaryTotalSpent);
                        $rows .= wf_TableRow($cells, 'row3');

                        $cells = wf_TableCell(__('Signup payments total'), '', 'row2');
                        $cells .= wf_TableCell($signupsTotalPayments);
                        $rows .= wf_TableRow($cells, 'row3');

                        $cells = wf_TableCell(__('Total spent for other tasks'), '', 'row2');
                        $cells .= wf_TableCell($otherTasksTotalSpent);
                        $rows .= wf_TableRow($cells, 'row3');

                        $signupsProfit = ($signupsTotalTariffPrices + $signupsTotalPayments) - $signupsTotalSpent;
                        $cells = wf_TableCell(__('Profit from users signups'), '', 'row2');
                        $cells .= wf_TableCell($signupsProfit);
                        $rows .= wf_TableRow($cells, 'row3');

                        $result .= wf_TableBody($rows, '50%', 0, 'sortable');
                        if ($this->salaryMultiplier) {
                            $result .= __('Including salary tax rates');
                        }
                    }
                } else {
                    $result = $this->messages->getStyledMessage(__('Nothing found'), 'info');
                }

                //saving printable results
                file_put_contents(self::PRINT_PATH, $resultPrintable);

                return ($result);
            }

        }

        set_time_limit(0);
        $report = new TasksReport();

        if (wf_CheckGet(array('cleancache'))) {
            $report->cacheCleanup();
            rcms_redirect($report::URL_ME);
        }

        if (wf_CheckGet(array('print'))) {
            if (file_exists($report::PRINT_PATH)) {
                if (filesize($report::PRINT_PATH) > 0) {
                    $printableData = file_get_contents($report::PRINT_PATH);
                    $datesFiltered = $report->getDates();
                    die($report->reportPrintable(__('Warehouse') . ': ' . $datesFiltered['from'] . '-' . $datesFiltered['to'], $printableData));
                }
            }
        }
        $cacheCleanupControl = wf_Link($report::URL_ME . '&cleancache=true', wf_img('skins/icon_cleanup.png', __('Cache cleanup')));
        show_window(__('Search') . ' ' . $cacheCleanupControl, $report->renderDatesForm());
        show_window(__('Tasks report'), $report->renderReport());
        $datesFiltered = $report->getDates();
        $printDates = '&datefrom=' . $datesFiltered['from'] . '&dateto=' . $datesFiltered['to'];
        $printControl = ((file_exists($report::PRINT_PATH)) AND ( filesize($report::PRINT_PATH) > 0) ) ? wf_Link($report::URL_ME . '&print=true' . $printDates, web_icon_print() . ' ' . __('Print'), false, 'ubButton', 'target="_blank"') : '';
        show_window('', $printControl);
    } else {
        show_error(__('This module disabled'));
    }
} else {
    show_error(__('Access denied'));
}
?>