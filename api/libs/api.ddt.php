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
     * Users/history database abstraction layer
     *
     * @var object
     */
    protected $usersDb = '';

    /**
     * Tariff options database abstraction layer
     *
     * @var object
     */
    protected $optionsDb = '';

    /**
     * Charge tariffs database abstraction layer
     *
     * @var object
     */
    protected $chargeOptsDb = '';

    /**
     * Charges history database absctraction layer
     *
     * @var object
     */
    protected $chargeHistDb = '';

    /**
     * Registered users log database abstraction layer
     *
     * @var object
     */
    protected $userRegDb = '';

    /**
     * Contains all charge opts as id=>data
     *
     * @var array
     */
    protected $allChargeOpts = array();

    /**
     * Contains all tariff charge rules as tariff=>ruleData
     *
     * @var array
     */
    protected $allTariffChargeRules = array();

    /**
     * Contains full charges history as id=>data
     *
     * @var array
     */
    protected $fullChargeHist = array();

    /**
     * Default control module URL
     */
    const URL_ME = '?module=ddt';

    /**
     * Default history report URL
     */
    const URL_HIST = '?module=ddt&history=true';

    /**
     * Forced charges history URL
     */
    const URL_CH_HIST = '&mode=fch';

    /**
     * Default user profile link URL
     */
    const URL_PROFILE = '?module=userprofile&username=';

    /**
     * Default deal with it search url
     */
    const URL_DWI = '?module=pl_dealwithit&username=';

    /**
     * Other predefined stuff
     */
    const PID = 'DDT';
    const TABLE_USERS = 'ddt_users';
    const TABLE_OPTIONS = 'ddt_options';
    const TABLE_CHARGESHIST = 'ddt_charges';
    const TABLE_CHARGEOPTS = 'ddt_chargeopts';
    const TABLE_USERREG = 'userreg';

    const ROUTE_CH_DELETE = 'deletechargeruleid';
    const ROUTE_CH_HISTAJX = 'forcedchargehistoryajax';
    const PROUTE_CH_CREATE = 'newchargetariffcreation';
    const PROUTE_CH_TARIFF = 'newchargetariff';
    const PROUTE_CH_UDAY = 'newchargeuntilday';
    const PROUTE_CH_FEE = 'newchargefeeflag';
    const PROUTE_CH_ABS = 'newchargeabsolute';
    const PROUTE_CH_CREDITDAYS = 'newchargecreditdays';

    /**
     * Creates new DoomsDay instance
     * 
     * @param bool $fast
     */
    public function __construct($fast = false) {
        $this->initMessages();
        $this->loadConfigs();
        $this->initDbs();
        $this->loadOptionsDDT();
        $this->loadChargeOptions();
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
            if (!empty($this->altCfg['DDT_ENDPREVDAYS']) and is_numeric($this->altCfg['DDT_ENDPREVDAYS'])) {
                $this->prevDaysOffset = $this->altCfg['DDT_ENDPREVDAYS'];
            }
        }
    }

    /**
     * Inits all requuired database abstraction layers
     *
     * @return void
     */
    protected function initDbs() {
        $this->usersDb = new NyanORM(self::TABLE_USERS);
        $this->optionsDb = new NyanORM(self::TABLE_OPTIONS);
        $this->chargeHistDb = new NyanORM(self::TABLE_CHARGESHIST);
        $this->chargeOptsDb = new NyanORM(self::TABLE_CHARGEOPTS);
        $this->userRegDb = new NyanORM(self::TABLE_USERREG);
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
        $this->allOptions = $this->optionsDb->getAll('id');
    }

    /**
     * Loads direct charge options
     *
     * @return void
     */
    protected function loadChargeOptions() {
        $this->allChargeOpts = $this->chargeOptsDb->getAll('id');
        if (!empty($this->allChargeOpts)) {
            foreach ($this->allChargeOpts as $io => $each) {
                $this->allTariffChargeRules[$each['tariff']] = $each;
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
        $this->allDDTUsers = $this->usersDb->getAll('id');
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
            $currentTariffsCharge = $this->getCurrentChargeTariffs();

            if (!empty($currentTariffsDDT)) {
                foreach ($currentTariffsDDT as $io => $each) {
                    unset($tariffsNewAvail[$io]);
                }
            }

            if (!empty($currentTariffsCharge)) {
                foreach ($currentTariffsCharge as $io => $each) {
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
        if (ubRouting::checkPost(array('createnewddtsignal', 'createnewddttariff', 'createnewddtperiod', 'createnewddtduration', 'createnewddttariffmove'))) {
            $newTariff = ubRouting::post('createnewddttariff');
            $newTariff_f = ubRouting::filters($newTariff, 'mres');
            $newTariffMove = ubRouting::post('createnewddttariffmove');
            $newTariffMove_f = ubRouting::filters($newTariffMove, 'mres');
            $newPeriod = ubRouting::post('createnewddtperiod', 'vf');
            $newDuration = ubRouting::post('createnewddtduration', 'int');
            $newStartNow = (ubRouting::checkPost('createnewddtstartnow')) ? 1 : 0;
            $newChargeFee = (ubRouting::checkPost('createnewddtchargefee')) ? 1 : 0;
            $newChargeDay = ubRouting::post('createnewddtchargeuntilday', 'int');
            $newSetCredit = (ubRouting::checkPost('createnewddtsetcredit')) ? 1 : 0;
            $currentTariffsDDT = $this->getCurrentTariffsDDT();
            $currentTariffsCharge = $this->getCurrentChargeTariffs();
            if ($newTariff != $newTariffMove) {
                if (!empty($newDuration)) {
                    if (!isset($currentTariffsDDT[$newTariff]) and !isset($currentTariffsCharge[$newTariff])) {
                        $this->optionsDb->data('tariffname', $newTariff_f);
                        $this->optionsDb->data('period', $newPeriod);
                        $this->optionsDb->data('startnow', $newStartNow);
                        $this->optionsDb->data('duration', $newDuration);
                        $this->optionsDb->data('chargefee', $newChargeFee);
                        $this->optionsDb->data('chargeuntilday', $newChargeDay);
                        $this->optionsDb->data('setcredit', $newSetCredit);
                        $this->optionsDb->data('tariffmove', $newTariffMove_f);
                        $this->optionsDb->create();

                        $newId = $this->optionsDb->getLastId();
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
        $tariffId = ubRouting::filters($tariffId, 'int');
        if (isset($this->allOptions[$tariffId])) {
            $tariffData = $this->allOptions[$tariffId];
            $this->optionsDb->where('id', '=', $tariffId);
            $this->optionsDb->delete();
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
     * Returns list of available charge tariffs as tariffname=>options
     *
     * @return array
     */
    public function getCurrentChargeTariffs() {
        $result = array();
        $result = $this->allTariffChargeRules;
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
        return ($result);
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

        $result .= wf_delimiter();
        $result .= wf_modalAuto(web_icon_create() . ' ' . __('Create new doomsday tariff'), __('Create new doomsday tariff'), $this->renderCreateForm(), 'ubButton');
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
                if ($each['login'] == $userLogin and $each['action'] == 'tariffchange') {
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
                if ($each['login'] == $userLogin and $each['action'] == 'tariffchange') {
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
        $this->usersDb->data('login', $login);
        $this->usersDb->data('active', '1');
        $this->usersDb->data('startdate', $curDateTime);
        $this->usersDb->data('curtariff', $userTariff);
        $this->usersDb->data('enddate', $targetDate);
        $this->usersDb->data('nexttariff', $nextTariff);
        $this->usersDb->data('dwiid', $dwiid);
        $this->usersDb->create();
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
                                            log_register('DDT FEE CHARGE (' . $eachUserLogin . ') TARIFF `' . $currentUserTariff . '` ON -' . $nativeTariffFee);
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
        $result .= wf_Link(self::URL_HIST, wf_img('skins/icon_calendar.gif') . ' ' . __('Doomsday tariffs history'), false, 'ubButton');
        $result .=  wf_Link(self::URL_HIST . self::URL_CH_HIST, wf_img('skins/icon_dollar_16.gif') . ' ' . __('Forced charges history'), false, 'ubButton');

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
        $loginFilter = ubRouting::get('username');
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


    /**
     * Render available tariffs charge opts here
     *
     * @return void
     */
    public function renderChargeOpsList() {
        $result = '';
        if (!empty($this->allChargeOpts)) {
            $cells = wf_TableCell(__('ID'));
            $cells .= wf_TableCell(__('Tariff'));
            $cells .= wf_TableCell(__('Fee'));
            $cells .= wf_TableCell(__('Charge until day'));
            $cells .= wf_TableCell(__('Charge fee'));
            $cells .= wf_TableCell(__('Additional amount'));
            $cells .= wf_TableCell(__('Credit days'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->allChargeOpts as $io => $each) {
                $tariffFee = $this->getTariffFee($each['tariff']);
                $cells = wf_TableCell($each['id']);
                $cells .= wf_TableCell($each['tariff']);
                $cells .= wf_TableCell($tariffFee);
                $udayLabel = ($each['untilday']) ? $each['untilday'] : __('Any');
                $cells .= wf_TableCell($udayLabel);
                $cells .= wf_TableCell(web_bool_led($each['chargefee']));
                $absLabel = ($each['absolute']) ? $each['absolute'] : __('No');
                $cells .= wf_TableCell($absLabel);
                $credLabel = ($each['creditdays']) ? $each['creditdays'] : __('No');
                $cells .= wf_TableCell($credLabel);

                $deleteUrl = self::URL_ME . '&' . self::ROUTE_CH_DELETE . '=' . $each['id'];
                $cancelUrl = self::URL_ME;
                $alertLabel = __('Delete') . ' ' . __('rule for') . ' ' . $each['tariff'] . '? ' . $this->messages->getDeleteAlert();
                $actLinks = wf_ConfirmDialog($deleteUrl, web_delete_icon(), $alertLabel, '', $cancelUrl, __('Delete'));
                $cells .= wf_TableCell($actLinks);
                $rows .= wf_TableRow($cells, 'row5');
            }
            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'info');
        }

        $result .= wf_delimiter(0);
        $result .= wf_modalAuto(wf_img('skins/icon_dollar_16.gif') . ' ' . __('Create new forced charge rule'), __('Create new forced charge rule'), $this->renderChargeOptsCreateForm(), 'ubButton');
        return ($result);
    }

    /**
     * Renders charge opts rule creation form
     *
     * @return void
     */
    public function renderChargeOptsCreateForm() {
        $result = '';
        $tariffParams = array();
        $dayParams = array();
        $currentTariffsDDT = $this->getCurrentTariffsDDT();
        $currentTariffsCharge = $this->getCurrentChargeTariffs();

        if (!empty($this->allTariffNames)) {
            foreach ($this->allTariffNames as $io => $each) {
                if (!isset($currentTariffsDDT[$each]) and !isset($currentTariffsCharge[$each])) {
                    $tariffParams[$each] = $each;
                }
            }
        }

        $dayParams[0] = __('Any');
        for ($i = 1; $i <= 31; $i++) {
            $dayParams[$i] = $i;
        }

        if (!empty($tariffParams)) {
            $inputs = wf_HiddenInput(self::PROUTE_CH_CREATE, 'true');
            $inputs .= wf_Selector(self::PROUTE_CH_TARIFF, $tariffParams, __('Tariff'), '', true);
            $inputs .= wf_Selector(self::PROUTE_CH_UDAY, $dayParams, __('Charge current tariff fee if day less then'), '', true);
            $inputs .= wf_CheckInput(self::PROUTE_CH_FEE, __('Charge current tariff fee'), true, true);
            $inputs .= wf_TextInput(self::PROUTE_CH_ABS, __('Also additionally withdraw the following amount'), '', true, 4, 'digits');
            $inputs .= wf_TextInput(self::PROUTE_CH_CREDITDAYS, __('Set a credit, for so many days'), '', true, 4, 'digits');
            $inputs .= wf_delimiter(0);
            $inputs .= wf_Submit(__('Create'));
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        } else {
            $result .= $this->messages->getStyledMessage(__('Something went wrong'), 'error');
        }

        return ($result);
    }

    /**
     * Creates new forsed charge rule database record
     *
     * @return void|string on error
     */
    public function createChargeRule() {
        $result = '';
        if (ubRouting::checkPost(array(self::PROUTE_CH_CREATE, self::PROUTE_CH_TARIFF))) {
            $newTariff = ubRouting::post(self::PROUTE_CH_TARIFF);
            $newTariff_f = ubRouting::filters($newTariff, 'mres');
            $uDay = ubRouting::post(self::PROUTE_CH_UDAY, 'int');
            $feeFlag = (ubRouting::checkPost(self::PROUTE_CH_FEE)) ? 1 : 0;
            $absValue = (ubRouting::checkPost(self::PROUTE_CH_ABS)) ? ubRouting::post(self::PROUTE_CH_ABS, 'int') : 0;
            $creditDays = (ubRouting::checkPost(self::PROUTE_CH_CREDITDAYS)) ? ubRouting::post(self::PROUTE_CH_CREDITDAYS, 'int') : 0;

            $currentTariffsDDT = $this->getCurrentTariffsDDT();
            $currentTariffsCharge = $this->getCurrentChargeTariffs();
            if (!isset($currentTariffsDDT[$newTariff]) and !isset($currentTariffsCharge[$newTariff])) {
                $this->chargeOptsDb->data('tariff', $newTariff_f);
                $this->chargeOptsDb->data('untilday', $uDay);
                $this->chargeOptsDb->data('chargefee', $feeFlag);
                $this->chargeOptsDb->data('absolute', $absValue);
                $this->chargeOptsDb->data('creditdays', $creditDays);
                $this->chargeOptsDb->create();

                $newId = $this->chargeOptsDb->getLastId();
                log_register('DDT CHARGE CREATE [' . $newId . '] TARIFF `' . $newTariff . '`');
            } else {
                $result = __('You already have doomsday assigned for tariff') . ' ' . $newTariff;
                log_register('DDT CHARGE CREATE FAIL DUPLICATE TARIFF `' . $newTariff . '`');
            }

            return ($result);
        }
    }

    /**
     * Deletes some tariff charge rule by its ID
     * 
     * @param int $tariffId
     * 
     * @return void/string on error
     */
    public function deleteChargeRule($ruleId) {
        $result = '';
        $ruleId = ubRouting::filters($ruleId, 'int');
        if (isset($this->allChargeOpts[$ruleId])) {
            $ruleData = $this->allChargeOpts[$ruleId];
            $this->chargeOptsDb->where('id', '=', $ruleId);
            $this->chargeOptsDb->delete();
            log_register('DDT DELETE [' . $ruleId . '] TARIFF `' . $ruleData['tariff'] . '`');
        } else {
            $result .= __('Forced tariffs charge') . ' [' . $ruleId . '] ' . __('Not exists');
            log_register('DDT CHARGE DELETE FAIL [' . $ruleId . '] NOT_EXISTS');
        }
        return ($result);
    }

    /**
     * Returns array of users charged today as login=>histData
     *
     * @return array
     */
    protected function getTodayChargedUsers() {
        $result = array();
        $curDay = curdate();
        $this->chargeHistDb->where('chargedate', '=', $curDay);
        $result = $this->chargeHistDb->getAll('login');
        return ($result);
    }

    /**
     * Returns array of users registered today as login=>regData
     *
     * @return array
     */
    protected function getUsersRegisteredToday() {
        $result = array();
        $curDay = curdate();
        $this->userRegDb->where('date', 'LIKE', $curDay . '%');
        $result = $this->userRegDb->getAll('login');
        return ($result);
    }

    /**
     * Creates new history record for some user forsed tariff charge
     *
     * @param string $login
     * @param string $chargeDate
     * @param string $tariff
     * @param foat $summ
     * 
     * @return void
     */
    protected function logCharge($login, $chargeDate, $tariff, $summ) {
        $login = ubRouting::filters($login, 'login');
        $chargeDate = ubRouting::filters($chargeDate, 'mres');
        $tariff = ubRouting::filters($tariff, 'mres');
        $summ = ubRouting::filters($summ, 'mres');

        $this->chargeHistDb->data('login', $login);
        $this->chargeHistDb->data('chargedate', $chargeDate);
        $this->chargeHistDb->data('tariff', $tariff);
        $this->chargeHistDb->data('summ', $summ);
        $this->chargeHistDb->create();
    }

    /**
     * Performs forced tariffs charge ruleset processing
     *
     * @return void
     */
    public function runChargeRules() {
        global $billing;
        if (!empty($this->allTariffChargeRules)) {
            $todayUsers = $this->getUsersRegisteredToday();
            if (!empty($todayUsers)) {
                $chargedUsers = $this->getTodayChargedUsers();
                $chargeTariffs = $this->getCurrentChargeTariffs();
                foreach ($todayUsers as $eachUserLogin => $regData) {
                    $eachUserData = $this->allUserData[$eachUserLogin];
                    $currentUserTariff = $eachUserData['Tariff'];

                    if (isset($chargeTariffs[$currentUserTariff])) {
                        //yep, this this tariff have charge rule
                        if (!isset($chargedUsers[$eachUserLogin])) {
                            //not charged today yet
                            $chargeRuleData = $chargeTariffs[$currentUserTariff];
                            $chargeUntilDay = $chargeRuleData['untilday'] ? $chargeRuleData['untilday'] : 0;
                            $chargeAllowed = ($chargeUntilDay == 0) ? true : false;
                            $absValue = $chargeRuleData['absolute'];
                            $feeFlag = ($chargeRuleData['chargefee']) ? true : false;
                            $creditDays = ($chargeRuleData['creditdays']) ? $chargeRuleData['creditdays'] : 0;

                            $currentDate = curdate();
                            $currentDayNum = date("j");
                            if (!$chargeAllowed) {
                                if ($currentDayNum <= $chargeUntilDay) {
                                    $chargeAllowed = true;
                                }
                            }

                            $nativeTariffData = $this->allTariffs[$currentUserTariff];
                            $nativeTariffFee = $nativeTariffData['Fee'];
                            $nativeTariffPeriod = (isset($nativeTariffData['period'])) ? $nativeTariffData['period'] : 'month';
                            $currentUserBalance = $eachUserData['Cash'];
                            $expectedUserBalance = $currentUserBalance;
                            $chargeFeeAmount = 0;

                            //is nowdays valid for charging this tariff?
                            if ($chargeAllowed) {
                                //native tariff fee charge?
                                if ($feeFlag) {
                                    $expectedUserBalance = $expectedUserBalance - $nativeTariffFee;
                                    $chargeFeeAmount += $nativeTariffFee;
                                }

                                //charge absolute value
                                if ($absValue) {
                                    $expectedUserBalance = $expectedUserBalance - $absValue;
                                    $chargeFeeAmount += $absValue;
                                }

                                //is any credit required?
                                if ($expectedUserBalance < 0) {
                                    if ($creditDays) {
                                        $newUserCredit = abs($expectedUserBalance);
                                        //set some credit
                                        $billing->setcredit($eachUserLogin, $newUserCredit);
                                        log_register('DDT CHANGE Credit (' . $eachUserLogin . ') ON ' . $newUserCredit);

                                        //and expire date
                                        $tariffExpireDate = date('Y-m-d', strtotime("+" . $creditDays . " days", strtotime($currentDate)));
                                        $billing->setcreditexpire($eachUserLogin, $tariffExpireDate);
                                        log_register('DDT CHANGE CreditExpire (' . $eachUserLogin . ') ON ' . $tariffExpireDate);
                                    }
                                }

                                //charging from user
                                log_register('DDT FORCED CHARGE (' . $eachUserLogin . ') TARIFF `' . $currentUserTariff . '` ON -' . $chargeFeeAmount);
                                $chargeComment = 'DDT: ' . $currentUserTariff;
                                zb_CashAdd($eachUserLogin, '-' . $chargeFeeAmount, 'correct', 1, $chargeComment);
                                $this->logCharge($eachUserLogin, $currentDate, $currentUserTariff, $chargeFeeAmount);
                                $chargedUsers[$eachUserLogin] = array(); //preventing multiple charge this user

                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Renders forced charges history report container
     * 
     * @return string
     */
    public function renderChargesHistoryContainer() {
        $result = '';
        if (!empty($this->allDDTUsers)) {
            $opts = '"order": [[ 1, "desc" ]]';
            $ajaxUrl = self::URL_ME . '&' . self::ROUTE_CH_HISTAJX . '=true';

            $columns = array('User', 'Date', 'Tariff', 'Sum');
            $result .= wf_JqDtLoader($columns, $ajaxUrl, false, __('Users'), 100, $opts);
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
    public function getChargesHistoryAjax() {
        $json = new wf_JqDtHelper();
        $allChargesHistory = $this->chargeHistDb->getAll();

        if (!empty($allChargesHistory)) {
            $userFullData = zb_UserGetAllDataCache();
            foreach ($allChargesHistory as $io => $each) {
                $userLink = isset($userFullData[$each['login']]) ? wf_Link(self::URL_PROFILE . $each['login'], web_profile_icon() . ' ' . $userFullData[$each['login']]['fulladress']) : $each['login'];
                $data[] = $userLink;
                $data[] = $each['chargedate'];
                $data[] = $each['tariff'];
                $data[] = $each['summ'];
                $json->addRow($data);
                unset($data);
            }
        }
        $json->getJson();
    }
}
