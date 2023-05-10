<?php

/**
 * Automatic user tariff migration aka  Dooms Day Tariffs class
 */
class DoomsDayTariffs {

    /**
     * Contains system alter config as key=>value
     */
    protected $altCfg = array();

    /**
     * Contains available DDT options aka tariffs as id=>data
     *
     * @var array
     */
    protected $allOptions = array();

    /**
     * Contains available system tariffs as tariffname=>tariffdata
     *
     * @var array
     */
    protected $allTariffs = array();

    /**
     * Contains available system tariffs as tariffname=>tariffname
     *
     * @var array
     */
    protected $allTariffNames = array();

    /**
     * Contains default periods descriptions as period=>periodname
     *
     * @var array
     */
    protected $periods = array();

    /**
     * Contains all available users data as login=>userdata
     *
     * @var array
     */
    protected $allUserData = array();

    /**
     * Contains all of available ddt users history data
     *
     * @var array
     */
    protected $allDDTUsers = array();

    /**
     * System message helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * DealWith it object instance placeholder
     *
     * @var object
     */
    protected $dealwithit = '';

    /**
     * Contains currently scheduled dealwithit tasks for tariff changes as id=>taskdata
     *
     * @var array
     */
    protected $allTasks = array();

    /**
     * Mapped from DDT_ENDPREVDAYS option
     * 
     * @var int
     */
    protected $prevDaysOffset = 0;

    /**
     * Default control module URL
     */
    const URL_ME = '?module=ddt';

    /**
     * Default history report URL
     */
    const URL_HIST = '?module=ddt&history=true';

    /**
     * Default user profile link URL
     */
    const URL_PROFILE = '?module=userprofile&username=';

    /**
     * Default deal with it search url
     */
    const URL_DWI = '?module=pl_dealwithit&username=';

    /**
     * Creates new DoomsDay instance
     * 
     * @param bool $fast
     */
    public function __construct($fast = false) {
        $this->initMessages();
        $this->loadConfigs();
        $this->loadOptionsDDT();
        $this->setOptions();
        $this->initDealWithIt();
        if (!$fast) {
            //full data set load
            $this->loadTariffs();
            $this->loadUsersDDT();
            $this->loadUserData();
        }
    }

    /**
     * Inits default system message helper object into protected prop
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Preloads some required configs for further usage
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfigs() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
        if (isset($this->altCfg['DDT_ENDPREVDAYS'])) {
            if (!empty($this->altCfg['DDT_ENDPREVDAYS']) AND is_numeric($this->altCfg['DDT_ENDPREVDAYS'])) {
                $this->prevDaysOffset = $this->altCfg['DDT_ENDPREVDAYS'];
            }
        }
    }

    /**
     * Inits dealwithit protected property
     * 
     * @return void
     */
    protected function initDealWithIt() {
        $this->dealwithit = new DealWithIt();
        $tmpTasks = $this->dealwithit->getAvailableTasks();
        if (!empty($tmpTasks)) {
            foreach ($tmpTasks as $io => $each) {
                if ($each['action'] == 'tariffchange') {
                    $this->allTasks[$each['id']] = $each;
                }
            }
        }
    }

    /**
     * Loads existing doomsday tariffs
     * 
     * @return void
     */
    protected function loadOptionsDDT() {
        $query = "SELECT * from `ddt_options`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allOptions[$each['id']] = $each;
            }
        }
    }

    /**
     * Sets default periods id-s and their localized names
     * 
     * @return void
     */
    protected function setOptions() {
        $this->periods['month'] = __('Month');
        $this->periods['day'] = __('Day');
    }

    /**
     * Loads all users data into protected prop
     * 
     * @return void
     */
    protected function loadUserData() {
        $this->allUserData = zb_UserGetAllStargazerDataAssoc();
    }

    /**
     * Loads existing DDT users database
     * 
     * @return void
     */
    protected function loadUsersDDT() {
        $query = "SELECT * from `ddt_users`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allDDTUsers[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads available system tariffs into protected prop for further usage
     * 
     * @return void
     */
    protected function loadTariffs() {
        $this->allTariffs = zb_TariffGetAllData();
        if (!empty($this->allTariffs)) {
            foreach ($this->allTariffs as $tariffName => $tariffData) {
                $this->allTariffNames[$tariffName] = $tariffName;
            }
        }
    }

    /**
     * Renders default DDT tariff creation form
     * 
     * @return string
     */
    public function renderCreateForm() {
        $result = '';

        if (!empty($this->allTariffNames)) {
            $tariffsNewAvail = $this->allTariffNames;
            $currentTariffsDDT = $this->getCurrentTariffsDDT();
            if (!empty($currentTariffsDDT)) {
                foreach ($currentTariffsDDT as $io => $each) {
                    unset($tariffsNewAvail[$io]);
                }
            }

            if (!empty($tariffsNewAvail)) {
                $inputs = wf_HiddenInput('createnewddtsignal', 'true');
                $inputs .= wf_Selector('createnewddttariff', $tariffsNewAvail, __('Tariff'), '', true);
                $inputs .= wf_Selector('createnewddtperiod', $this->periods, __('Period'), '', true);
                $inputs .= wf_TextInput('createnewddtduration', __('Duration'), '1', true, 4, 'digits');
                $inputs .= wf_CheckInput('createnewddtstartnow', __('Take into account the current period'), true, false);
                $inputs .= wf_CheckInput('createnewddtchargefee', __('Charge current tariff fee'), true, false);
                $inputs .= wf_TextInput('createnewddtchargeuntilday', __('Charge current tariff fee if day less then'), '1', true, 2, 'digits');
                $inputs .= wf_CheckInput('createnewddtsetcredit', __('Set a user credit if the money is not enough to use the service now'), true, false);
                $inputs .= wf_Selector('createnewddttariffmove', $this->allTariffNames, __('Move to tariff after ending of periods'), '', true);
                $inputs .= wf_delimiter(0);
                $inputs .= wf_Submit(__('Create'));
                $result .= wf_Form('', 'POST', $inputs, 'glamour');
            } else {
                $result .= $this->messages->getStyledMessage(__('You already planned doomsday for all of available tariffs'), 'success');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('No existing tariffs available at all'), 'error');
        }


        return ($result);
    }

    /**
     * Catches DDT Tariff creation request and creates it into database
     * 
     * @return void/string on error
     */
    public function createTariffDDT() {
        $result = '';
        if (wf_CheckPost(array('createnewddtsignal', 'createnewddttariff', 'createnewddtperiod', 'createnewddtduration', 'createnewddttariffmove'))) {
            $newTariff = $_POST['createnewddttariff'];
            $newTariff_f = mysql_real_escape_string($newTariff);
            $newTariffMove = $_POST['createnewddttariffmove'];
            $newTariffMove_f = mysql_real_escape_string($_POST['createnewddttariffmove']);
            $newPeriod = vf($_POST['createnewddtperiod']);
            $newDuration = vf($_POST['createnewddtduration'], 3);
            $newStartNow = (wf_CheckPost(array('createnewddtstartnow'))) ? 1 : 0;
            $newChargeFee = (wf_CheckPost(array('createnewddtchargefee'))) ? 1 : 0;
            $newChargeDay = vf($_POST['createnewddtchargeuntilday'], 3);
            $newSetCredit = (wf_CheckPost(array('createnewddtsetcredit'))) ? 1 : 0;
            $currentTariffsDDT = $this->getCurrentTariffsDDT();
            if ($newTariff != $newTariffMove) {
                if (!empty($newDuration)) {
                    if (!isset($currentTariffsDDT[$newTariff])) {
                        $query = "INSERT INTO `ddt_options` (`id`,`tariffname`,`period`,`startnow`,`duration`,`chargefee`,`chargeuntilday`,`setcredit`,`tariffmove`)"
                                . " VALUES (NULL,'" . $newTariff_f . "','" . $newPeriod . "','" . $newStartNow . "','" . $newDuration . "','" . $newChargeFee . "','" . $newChargeDay . "','" . $newSetCredit . "','" . $newTariffMove_f . "'); ";
                        nr_query($query);
                        $newId = simple_get_lastid('ddt_options');
                        log_register('DDT CREATE [' . $newId . '] TARIFF `' . $newTariff . '` MOVE ON `' . $newTariffMove . '` IN ' . $newDuration . ' `' . $newPeriod . '`');
                    } else {
                        $result = __('You already have doomsday assigned for tariff') . ' ' . $newTariff;
                        log_register('DDT CREATE FAIL DUPLICATE TARIFF `' . $newTariff . '`');
                    }
                } else {
                    $result = __('Duration cannot be empty');
                    log_register('DDT CREATE FAIL EMPTY DURATION');
                }
            } else {
                $result = __('Tariffs must be different');
                log_register('DDT CREATE FAIL SAME TARIFFS `' . $newTariff . '`');
            }
        }
        return ($result);
    }

    /**
     * Deletes some doomsday tariff by its ID
     * 
     * @param int $tariffId
     * 
     * @return void/string on error
     */
    public function deleteTariffDDT($tariffId) {
        $result = '';
        $tariffId = vf($tariffId, 3);
        if (isset($this->allOptions[$tariffId])) {
            $tariffData = $this->allOptions[$tariffId];
            $query = "DELETE from `ddt_options` WHERE `id`='" . $tariffId . "';";
            nr_query($query);
            log_register('DDT DELETE [' . $tariffId . '] TARIFF `' . $tariffData['tariffname'] . '`');
        } else {
            $result .= __('Tariff') . ' ' . $tariffId . ' ' . __('Not exists');
            log_register('DDT DELETE FAIL [' . $tariffId . '] NOT_EXISTS');
        }
        return ($result);
    }

    /**
     * Returns list of available ddt tariffs as tariffname=>options
     * 
     * @return array
     */
    public function getCurrentTariffsDDT() {
        $result = array();
        if (!empty($this->allOptions)) {
            foreach ($this->allOptions as $io => $each) {
                $result[$each['tariffname']] = $each;
            }
        }
        return ($result);
    }

    /**
     * Returns some tariff price
     * 
     * @param string $tariffName
     * 
     * @return float
     */
    protected function getTariffFee($tariffName) {
        $result = 0;
        if (isset($this->allTariffs[$tariffName])) {
            $result = $this->allTariffs[$tariffName]['Fee'];
        }
        return($result);
    }

    /**
     * Renders available DDT tariffs list with some controls
     * 
     * @return string
     */
    public function renderTariffsList() {
        $result = '';
        if (!empty($this->allOptions)) {
            $cells = wf_TableCell(__('ID'));
            $cells .= wf_TableCell(__('Tariff'));
            $cells .= wf_TableCell(__('Fee'));
            $cells .= wf_TableCell(__('Period'));
            $cells .= wf_TableCell(__('Start at this period'));
            $cells .= wf_TableCell(__('Duration'));
            $cells .= wf_TableCell(__('Charge fee'));
            $cells .= wf_TableCell(__('Charge until day'));
            $cells .= wf_TableCell(__('Set credit'));
            $cells .= wf_TableCell(__('New tariff'));
            $cells .= wf_TableCell(__('Fee'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');

            foreach ($this->allOptions as $io => $each) {
                $cells = wf_TableCell($each['id']);
                $cells .= wf_TableCell($each['tariffname']);
                $cells .= wf_TableCell($this->getTariffFee($each['tariffname']));
                $cells .= wf_TableCell($this->periods[$each['period']]);
                $cells .= wf_TableCell(web_bool_led($each['startnow']));
                $cells .= wf_TableCell($each['duration']);
                $cells .= wf_TableCell(web_bool_led($each['chargefee']));
                $cells .= wf_TableCell($each['chargeuntilday']);
                $cells .= wf_TableCell(web_bool_led($each['setcredit']));
                $cells .= wf_TableCell($each['tariffmove']);
                $cells .= wf_TableCell($this->getTariffFee($each['tariffmove']));

                $deleteUrl = self::URL_ME . '&deleteddtariff=' . $each['id'];
                $cancelUrl = self::URL_ME;
                $alertLabel = __('Delete') . ' ' . __('Doomsday tariff') . ' ' . $each['tariffname'] . '? ' . $this->messages->getDeleteAlert();
                $actLinks = wf_ConfirmDialog($deleteUrl, web_delete_icon(), $alertLabel, '', $cancelUrl, __('Delete'));
                $cells .= wf_TableCell($actLinks);
                $rows .= wf_TableRow($cells, 'row5');
            }

            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result .= $this->messages->getStyledMessage(__('There is nothing to watch') . '.', 'info');
        }
        return ($result);
    }

    /**
     * Checks is user have some tariff moving tasks created already
     * 
     * @param string $userLogin
     * 
     * @return bool
     */
    protected function isTaskCreated($userLogin) {
        $result = false;
        if (!empty($this->allTasks)) {
            foreach ($this->allTasks as $io => $each) {
                if ($each['login'] == $userLogin AND $each['action'] == 'tariffchange') {
                    $result = true;
                }
            }
        }
        return ($result);
    }

    /**
     * Checks is user have some tariff moving tasks created already and return task data.
     * 
     * @param string $userLogin
     * 
     * @return array
     */
    public function getTaskCreated($userLogin) {
        $result = array();
        if (!empty($this->allTasks)) {
            foreach ($this->allTasks as $io => $each) {
                if ($each['login'] == $userLogin AND $each['action'] == 'tariffchange') {
                    $result = $each;
                }
            }
        }
        return ($result);
    }

    /**
     * Logs first user appear for some DDT tariff
     * 
     * @param string $login
     * @param string $userTariff
     * @param string $targetDate
     * @param string $nextTariff
     * @param int $dwiid
     * 
     * @return void
     */
    protected function logSchedule($login, $userTariff, $targetDate, $nextTariff, $dwiid) {
        $curDateTime = curdatetime();
        $query = "INSERT INTO `ddt_users` (`id`,`login`,`active`,`startdate`,`curtariff`,`enddate`,`nexttariff`,`dwiid`) VALUES "
                . "(NULL,'" . $login . "','1','" . $curDateTime . "', '" . $userTariff . "','" . $targetDate . "','" . $nextTariff . "','" . $dwiid . "');";
        nr_query($query);
    }

    /**
     * Runs DDT tariffs periodic processing
     * 
     * @global object $billing
     * 
     * @return void
     */
    public function runProcessing() {
        global $billing;
        if (!empty($this->allOptions)) {
            if (!empty($this->allUserData)) {
                $ddtTariffs = $this->getCurrentTariffsDDT();
                foreach ($this->allUserData as $eachUserLogin => $eachUserData) {
                    $currentUserTariff = $eachUserData['Tariff'];
                    if (isset($ddtTariffs[$currentUserTariff])) {
                        //yep, this is DDT user
                        if (!$this->isTaskCreated($eachUserLogin)) {
                            //no existing move task
                            $currentTariffOptions = $ddtTariffs[$currentUserTariff];
                            $tariffPeriod = $currentTariffOptions['period'];
                            $tariffDuration = $currentTariffOptions['duration'];
                            $moveTariff = $currentTariffOptions['tariffmove'];
                            $currentDate = curdate();
                            $currentDayNum = date("j");

                            $targetDate = '';


                            if ($tariffPeriod == 'month') {
                                if ($currentTariffOptions['startnow']) {
                                    $tariffDuration = $tariffDuration - 1;
                                }
                                $targetDate = date('Y-m-t', strtotime("+" . $tariffDuration . " months", strtotime($currentDate)));
                                //optional "before end of month" date offset
                                if ($this->prevDaysOffset) {
                                    $targetDate = date('Y-m-d', strtotime("-" . $this->prevDaysOffset . " days", strtotime($targetDate)));
                                }
                            }

                            if ($tariffPeriod == 'day') {
                                if ($currentTariffOptions['startnow']) {
                                    $tariffDuration = $tariffDuration - 1;
                                }
                                $targetDate = date('Y-m-d', strtotime("+" . $tariffDuration . " days", strtotime($currentDate)));
                            }

                            if (!empty($targetDate)) {
                                //creating scheduled task for move
                                $newDwiid = $this->dealwithit->createTask($targetDate, $eachUserLogin, 'tariffchange', $moveTariff, __('Doomsday tariff') . ': ' . $currentTariffOptions['tariffname']);
                                //writing some history 
                                $this->logSchedule($eachUserLogin, $currentUserTariff, $targetDate, $moveTariff, $newDwiid);

                                //charge some fee if required
                                if ($currentTariffOptions['chargefee']) {
                                    if ($currentTariffOptions['chargeuntilday']) {
                                        if ($currentTariffOptions['chargeuntilday'] >= $currentDayNum) {
                                            $nativeTariffData = $this->allTariffs[$currentUserTariff];
                                            $nativeTariffFee = $nativeTariffData['Fee'];
                                            $nativeTariffPeriod = (isset($nativeTariffData['period'])) ? $nativeTariffData['period'] : 'month';
                                            zb_CashAdd($eachUserLogin, '-' . $nativeTariffFee, 'correct', 1, 'DDT: ' . $currentUserTariff);

                                            //setting credit if required
                                            if ($currentTariffOptions['setcredit']) {
                                                $currentUserBalance = $eachUserData['Cash'];
                                                $nextUserBalance = $currentUserBalance - $nativeTariffFee;
                                                if ($nextUserBalance < '-' . $eachUserData['Credit']) {
                                                    $newUserCredit = abs($nextUserBalance);

                                                    //set credit
                                                    $billing->setcredit($eachUserLogin, $newUserCredit);
                                                    log_register('DDT CHANGE Credit (' . $eachUserLogin . ') ON ' . $newUserCredit);
                                                    //set credit expire date

                                                    if ($tariffPeriod == 'month') {
                                                        $tariffExpireDate = date('Y-m-t');
                                                    }

                                                    if ($tariffPeriod == 'day') {
                                                        $tariffExpireDate = date('Y-m-d', strtotime("+3 days", strtotime($currentDate)));
                                                    }

                                                    $billing->setcreditexpire($eachUserLogin, $tariffExpireDate);
                                                    log_register('DDT CHANGE CreditExpire (' . $eachUserLogin . ') ON ' . $tariffExpireDate);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                log_register('DDT RUN FAIL NO_USERS');
            }
        } else {
            log_register('DDT RUN FAIL NO_TARIFFS');
        }
    }

    /**
     * Renders module controls
     * 
     * @return string
     */
    public function renderControls() {
        $result = '';
        if (cfr('DDTCONF')) {
            $result .= wf_Link(self::URL_ME, web_icon_extended() . ' ' . __('Configuration'), false, 'ubButton');
        }
        $result .= wf_Link(self::URL_HIST, wf_img('skins/icon_calendar.gif') . ' ' . __('History'), false, 'ubButton');
        return ($result);
    }

    /**
     * Renders history report container
     * 
     * @param string $userLogin
     * 
     * @return string
     */
    public function renderHistoryContainer($userLogin = '') {
        $result = '';
        if (!empty($this->allDDTUsers)) {
            $opts = '"order": [[ 1, "desc" ]]';
            $ajaxUrl = self::URL_HIST . '&ajax=true';
            $userControls = '';
            if ($userLogin) {
                $ajaxUrl .= '&username=' . $userLogin;
                $userControls = wf_delimiter(0) . web_UserControls($userLogin);
            }
            $columns = array('User', 'Date', 'Tariff', 'End date', 'New tariff', 'Deal with it');
            $result .= wf_JqDtLoader($columns, $ajaxUrl, false, __('Users'), 100, $opts);
            $result .= $userControls;
        } else {
            $result .= $this->messages->getStyledMessage(__('There is nothing to watch'), 'warning');
        }
        return ($result);
    }

    /**
     * Renders DDT history report json data
     * 
     * @return void
     */
    public function getHistoryAjax() {
        $json = new wf_JqDtHelper();
        $loginFilter = wf_CheckGet(array('username')) ? $_GET['username'] : '';
        if (!empty($this->allDDTUsers)) {
            $userFullData = zb_UserGetAllDataCache();
            foreach ($this->allDDTUsers as $io => $each) {
                $userLink = isset($userFullData[$each['login']]) ? wf_Link(self::URL_PROFILE . $each['login'], web_profile_icon() . ' ' . $userFullData[$each['login']]['fulladress']) : $each['login'];
                $data[] = $userLink;
                $data[] = $each['startdate'];
                $data[] = $each['curtariff'];
                $data[] = $each['enddate'];
                $data[] = $each['nexttariff'];
                $dwiLink = wf_Link(self::URL_DWI . $each['login'], $each['dwiid']);
                $data[] = $dwiLink;
                if (empty($loginFilter)) {
                    $json->addRow($data);
                } else {
                    if ($each['login'] == $loginFilter) {
                        $json->addRow($data);
                    }
                }

                unset($data);
            }
        }
        $json->getJson();
    }

}
