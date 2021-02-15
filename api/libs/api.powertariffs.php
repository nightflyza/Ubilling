<?php

/**
 * Alternative tariffication model
 */
class PowerTariffs {

    /**
     * System alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Most essential property for this Porno Tariffs mechanics
     *
     * @var int
     */
    protected $currentDay = 0;

    /**
     * Default maximum day of month which will be rounded to 1st.
     * Configurable with PT_MAXDAY option.
     *
     * @var int 
     */
    protected $maxDay = 26;

    /**
     *
     * @var bool
     */
    protected $chargeOnRegister = true;

    /**
     * Contains names and prices of system tariffs as name=>fee
     *
     * @var array
     */
    protected $systemTariffs = array();

    /**
     * Contains available power tariffs as tariffname=>recordData
     *
     * @var array
     */
    protected $allTariffs = array();

    /**
     * Contains all existing power users as login=>day
     *
     * @var array
     */
    protected $allUsers = array();

    /**
     * Contains system users data as login=>userdata
     *
     * @var array
     */
    protected $systemUsers = array();

    /**
     * Power tariffs database abstraction placeholder
     *
     * @var object
     */
    protected $tariffsDb = '';

    /**
     * Users affected by power tariffs database abstraction placeholder
     *
     * @var object
     */
    protected $usersDb = '';

    /**
     * All stargazer users abstraction layer placeholder
     *
     * @var object
     */
    protected $stgDb = '';

    /**
     * Users day offset switching log database abstraction placeholder
     *
     * @var object
     */
    protected $journalDb = '';

    /**
     * Users fee charge database abstraction placeholder
     *
     * @var object
     */
    protected $feeDb = '';

    /**
     * System message helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Contains current administrator login
     *
     * @var string
     */
    protected $currentAdministrator = '';

    /**
     * Routes, tables, etc
     */
    const URL_ME = '?module=pt';
    const TABLE_TARIFFS = 'pt_tariffs';
    const TABLE_USERS = 'pt_users';
    const TABLE_PAYLOG = 'paymentscorr';
    const TABLE_LOG = 'pt_log';
    const ROUTE_DELETE = 'deletept';
    const ROUTE_EDIT = 'editpt';

    /**
     * Some config options here
     */
    const OPTION_MAXDAY = 'PT_MAXDAY';
    const OPTION_CHARGEON = 'PT_CHARGEONREG';
    const PROUTE_EDITOFFSET = 'newptdayoffset';
    const PROUTE_AGREE = 'newptdayoffsetagree';
    const ROUTE_BACK = '?module=pl_pt';

    /**
     * Creates new PT instance
     * 
     * @param bool $loadAll Load system users and tariffs too.
     */
    public function __construct($loadAll = true) {
        $this->initMessages();
        $this->loadAlter();
        $this->setOptions();
        $this->setCurrentDate();
        $this->setCurrentAdmin();
        $this->initPowerBase();
        if ($loadAll) {
            $this->loadSystemTariffs();
            $this->loadSystemUsers();
        }
        $this->loadPowerTariffs(); //Go Go Power Rangers
        $this->loadPowerUsers();
    }

    /**
     * Sets current day into protected prop
     * 
     * @return void
     */
    protected function setCurrentDate() {
        $currentDayOfMonth = date("d");
        if ($currentDayOfMonth >= $this->maxDay) {
            $currentDayOfMonth = 1;
        }
        $this->currentDay = $currentDayOfMonth;
    }

    /**
     * Sets administrator login for current PT instance once
     * 
     * @return void
     */
    protected function setCurrentAdmin() {
        $this->currentAdministrator = whoami();
    }

    /**
     * Inits system message helper
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Preloads system alter config into protected property for further usage
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadAlter() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Sets some custom PowerTariffs options
     * 
     * @return void
     */
    protected function setOptions() {
        //custom maximum day of month
        if (isset($this->altCfg[self::OPTION_MAXDAY])) {
            if ($this->altCfg[self::OPTION_MAXDAY]) {
                $this->maxDay = $this->altCfg[self::OPTION_MAXDAY];
            }
        }

        //charge on register flag setup
        if (isset($this->altCfg[self::OPTION_CHARGEON])) {
            $this->chargeOnRegister = ($this->altCfg[self::OPTION_CHARGEON]) ? true : false;
        }
    }

    /**
     * Loads available system tariffs from database
     * 
     * @return void
     */
    protected function loadSystemTariffs() {
        $this->systemTariffs = zb_TariffGetPricesAll();
    }

    /**
     * Inits all required database abstraction layers into internal props
     * 
     * @return void
     */
    protected function initPowerBase() {
        $this->tariffsDb = new NyanORM(self::TABLE_TARIFFS);
        $this->usersDb = new NyanORM(self::TABLE_USERS);
        $this->journalDb = new NyanORM(self::TABLE_LOG);
        $this->feeDb = new NyanORM(self::TABLE_PAYLOG);
    }

    /**
     * Loads all existing power users to protected property
     * 
     * @return void
     */
    protected function loadPowerUsers() {
        $usersTmp = $this->usersDb->getAll();
        if (!empty($usersTmp)) {
            foreach ($usersTmp as $io => $each) {
                $this->allUsers[$each['login']] = $each['day'];
            }
        }
    }

    /**
     * Loads all existing  system  users data to protected property
     * 
     * @return void
     */
    protected function loadSystemUsers() {
        $this->stgDb = new NyanORM('users');
        $this->stgDb->selectable(array('login', 'Tariff', 'Cash', 'Credit', 'Passive'));
        $this->systemUsers = $this->stgDb->getAll('login');
    }

    /**
     * Loads available power tariffs from database into protected prop
     * 
     * @return void
     */
    protected function loadPowerTariffs() {
        $this->allTariffs = $this->tariffsDb->getAll('tariff');
    }

    /**
     * Renders available power tariffs list with some controls
     * 
     * @return string
     */
    public function renderTariffsList() {
        $result = '';
        if (!empty($this->allTariffs)) {
            $cells = wf_TableCell(__('Tariff name'));
            $cells .= wf_TableCell(__('Tariff fee'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');

            foreach ($this->allTariffs as $io => $each) {
                $cells = wf_TableCell($each['tariff']);
                $cells .= wf_TableCell($each['fee']);
                $tariffControls = wf_JSAlert(self::URL_ME . '&' . self::ROUTE_DELETE . '=' . $each['tariff'], web_delete_icon(), $this->messages->getDeleteAlert());
                $tariffControls .= wf_modalAuto(web_edit_icon(), __('Edit') . ' ' . $each['tariff'], $this->renderTariffEditForm($each['tariff']));
                $cells .= wf_TableCell($tariffControls);
                $rowClass = (isset($this->systemTariffs[$each['tariff']])) ? 'row5' : 'sigdeleteduser';
                $rows .= wf_TableRow($cells, $rowClass);
            }

            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        return($result);
    }

    /**
     * Returns new power tariff creation form
     * 
     * @return string
     */
    public function renderTariffCreateForm() {
        $result = '';
        $tariffsTmp = array();
        if (!empty($this->systemTariffs)) {
            foreach ($this->systemTariffs as $eachTariff => $eachFee) {
                //only tariffs with no Stargazer processed fee can be so powerfull
                if ($eachFee == 0) {
                    if (!isset($this->allTariffs[$eachTariff])) {
                        //not power tariff assigned yet
                        $tariffsTmp[$eachTariff] = $eachTariff;
                    }
                }
            }
        }

        if (!empty($tariffsTmp)) {
            $inputs = wf_Selector('creatept', $tariffsTmp, __('Tariff name'), '', false) . ' ';
            $inputs .= wf_TextInput('createptfee', __('Fee'), '', false, 5, 'finance') . ' ';
            $inputs .= wf_Submit(__('Create'));

            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        }
        return($result);
    }

    /**
     * Returns existing power tariff editing form
     * 
     * @param string $tariffName
     * 
     * @return string
     */
    public function renderTariffEditForm($tariffName) {
        $result = '';

        if (isset($this->allTariffs[$tariffName])) {
            $tariffData = $this->allTariffs[$tariffName];
            $inputs = wf_HiddenInput('editpt', $tariffName);
            $inputs .= wf_TextInput('editptfee', __('Fee'), $tariffData['fee'], false, 5, 'finance');
            $inputs .= wf_Submit(__('Save'));

            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        }
        return($result);
    }

    /**
     * Creates new power tariff in database
     * 
     * @param string $tariffName
     * @param float $fee
     * 
     * @return void/string on error
     */
    public function createTariff($tariffName, $fee) {
        $result = '';
        $tariffNameF = ubRouting::filters($tariffName, 'mres');
        $feeF = ubRouting::filters($fee, 'mres');
        if (!isset($this->allTariffs[$tariffName])) {
            if ($feeF > 0) {
                if (isset($this->systemTariffs[$tariffName])) {
                    //seems ok, lets create new power tariff
                    $this->tariffsDb->data('tariff', $tariffNameF);
                    $this->tariffsDb->data('fee', $feeF);
                    $this->tariffsDb->create();
                    $newId = $this->tariffsDb->getLastId();
                    log_register('PT CREATE TARIFF [' . $newId . '] NAME `' . $tariffName . '` FEE `' . $fee . '`');
                } else {
                    $result .= 'System tariff not found';
                }
            } else {
                $result .= 'Power tariff price cant be zero';
            }
        } else {
            $result .= 'Tariff already exists';
        }
        return($result);
    }

    /**
     * Saves existing power tariff in database
     * 
     * @param string $tariffName
     * @param float $fee
     * 
     * @return void/string on error
     */
    public function saveTariff($tariffName, $fee) {
        $result = '';
        $tariffNameF = ubRouting::filters($tariffName, 'mres');
        $feeF = ubRouting::filters($fee, 'mres');
        if (isset($this->allTariffs[$tariffName])) {
            $tariffData = $this->allTariffs[$tariffName];
            if ($feeF > 0) {
                $tariffId = $tariffData['id'];
                //seems ok, lets save power tariff
                $this->tariffsDb->data('fee', $feeF);
                $this->tariffsDb->where('tariff', '=', $tariffNameF);
                $this->tariffsDb->save();
                log_register('PT EDIT TARIFF [' . $tariffId . '] NAME `' . $tariffName . '` FEE `' . $fee . '`');
            } else {
                $result .= 'Power tariff price cant be zero';
            }
        } else {
            $result .= 'Tariff not exists';
        }
        return($result);
    }

    /**
     * Deletes some existing power tariff from database
     * 
     * @param string $tariffName
     * 
     * @return void/string on error
     */
    public function deleteTariff($tariffName) {
        $result = '';
        $tariffNameF = ubRouting::filters($tariffName, 'mres');
        if (isset($this->allTariffs[$tariffName])) {
            $tariffData = $this->allTariffs[$tariffName];
            $this->tariffsDb->where('tariff', '=', $tariffNameF);
            $this->tariffsDb->delete();
            log_register('PT DELETE TARIFF [' . $tariffData['id'] . '] NAME `' . $tariffData['tariff'] . '` FEE `' . $tariffData['fee'] . '`');
        } else {
            $result .= 'Tariff not exists';
        }
        return($result);
    }

    /**
     * Checks is some tariff really have the power?
     * 
     * @param string $tariffName
     * 
     * @return bool
     */
    public function isPowerTariff($tariffName) {
        $result = false;
        if (isset($this->allTariffs[$tariffName])) {
            $result = true;
        }
        return($result);
    }

    /**
     * Returns existing power tariff price
     * 
     * @param string $tariffName
     * 
     * @return float
     */
    public function getPowerTariffPrice($tariffName) {
        $result = 0;
        if ($this->isPowerTariff($tariffName)) {
            $result = $this->allTariffs[$tariffName]['fee'];
        }
        return($result);
    }

    /**
     * Returns user personal day offset
     * 
     * @param string $userLogin
     * 
     * @return int / -2 - not power user issue
     */
    public function getUserOffsetDay($userLogin) {
        $result = 0;
        if (isset($this->allUsers[$userLogin])) {
            $result = $this->allUsers[$userLogin];
        } else {
            $result = -2;
        }
        return($result);
    }

    /**
     * Check is user using one of power tariffs?
     * 
     * @param array $userData
     * 
     * @return bool
     */
    protected function userHavePowerTariff($userData) {
        $result = false;
        $userTariff = $userData['Tariff'];
        if (isset($this->allTariffs[$userTariff])) {
            $result = true;
        }
        return($result);
    }

    /**
     * Checks is user active now?
     * 
     * @param array $userData
     * 
     * @return bool
     */
    protected function isUserActive($userData) {
        $result = false;
        //dont check credit state to avoid fee day offset change
        if (($userData['Cash'] >= 0) AND ( $userData['Passive'] == 0)) {
            $result = true;
        }
        return($result);
    }

    /**
     * Logs user day offset switching into 
     * 
     * @param string $userLogin
     * @param string $userTariff
     * @param int $dayOffset
     * 
     * @return void
     */
    protected function logUser($userLogin, $userTariff, $dayOffset) {
        $curDateTime = curdatetime();
        $this->journalDb->data('date', $curDateTime);
        $this->journalDb->data('login', $userLogin);
        $this->journalDb->data('tariff', $userTariff);
        $this->journalDb->data('day', $dayOffset);
        $this->journalDb->create();
    }

    /**
     * Runs for detecting of newly registered users or users which need to be power-users
     * 
     * @return
     */
    public function registerNewUsers() {
        if (!empty($this->systemUsers)) {
            foreach ($this->systemUsers as $userLogin => $userData) {
                //not registered yet
                if (!isset($this->allUsers[$userLogin])) {
                    //need to do something with this user at all?
                    if ($this->userHavePowerTariff($userData)) {
                        //user is not dead at all
                        if ($this->isUserActive($userData)) {
                            //write that user as real power users to DB
                            $this->usersDb->data('login', $userLogin);
                            $this->usersDb->data('day', $this->currentDay);
                            $this->usersDb->create();
                            $this->logUser($userLogin, $userData['Tariff'], $this->currentDay);

                            //charging fee on user detection if required
                            if ($this->chargeOnRegister) {
                                $realCurrentDay = date("d");
                                //avoid double tax rates :P
                                if ($realCurrentDay <= $this->maxDay) {
                                    $tariffData = $this->allTariffs[$userData['Tariff']];
                                    $tariffFee = $tariffData['fee'];
                                    $this->chargeFee($userLogin, $tariffFee, $userData['Cash']);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Charges fee from user account. Using this instead zb_CashAdd for avoid unnecessary logging.
     * 
     * @global object $billing
     * @param string $userLogin
     * @param float $fee
     * @param float $balance
     * 
     * @return void
     */
    protected function chargeFee($userLogin, $fee, $balance) {
        global $billing;
        $fee = '-' . abs($fee); //fee is negative i guess?
        $curDateTime = curdatetime(); //fee datetime is changing on each operation
        //charge fee from user balance
        $billing->addcash($userLogin, $fee);

        //logging financial operation
        $this->feeDb->data('login', $userLogin);
        $this->feeDb->data('date', $curDateTime);
        $this->feeDb->data('admin', $this->currentAdministrator);
        $this->feeDb->data('balance', $balance);
        $this->feeDb->data('summ', $fee);
        $this->feeDb->data('cashtypeid', '1');
        $this->feeDb->data('note', 'PTFEE');
        $this->feeDb->create();
    }

    /**
     * Performs user burial on cash exceed
     * 
     * @param string $userLogin
     * 
     * @return void
     */
    protected function userBurial($userLogin) {
        $this->usersDb->data('day', 0); //set offset day to zero
        $this->usersDb->where('login', '=', $userLogin);
        $this->usersDb->save();
    }

    /**
     * Performs user resurrection on restoring cash
     * 
     * @param string $userLogin
     * 
     * @return void
     */
    protected function userResurrect($userLogin) {
        $this->usersDb->data('day', $this->currentDay); //set offset day to current
        $this->usersDb->where('login', '=', $userLogin);
        $this->usersDb->save();
    }

    /**
     * Renders user day offset modification form
     * 
     * @param string $userLogin
     * 
     * @return string
     */
    public function renderUserOffsetEditForm($userLogin) {
        $result = '';
        if (isset($this->allUsers[$userLogin])) {
            $currentOffset = $this->allUsers[$userLogin];
            $daysParams = array();
            for ($i = 1; $i < $this->maxDay; $i++) {
                $daysParams[$i] = $i;
            }

            $inputs = wf_Selector(self::PROUTE_EDITOFFSET, $daysParams, __('Day'), $currentOffset, false) . ' ';
            $inputs .= wf_CheckInput(self::PROUTE_AGREE, __('I`m ready'), false, false) . ' ';
            $inputs .= wf_Submit(__('Save'));

            $result .= wf_Form('', 'POST', $inputs, 'glamour');
            $result .= $this->messages->getStyledMessage(__('Current day of fee') . ': ' . $currentOffset, 'info') . wf_delimiter(0);
        } else {
            $result .= $this->messages->getStyledMessage(__('This is user without power tariff'), 'warning');
        }
        return($result);
    }

    /**
     * Saves new user offset day in database
     * 
     * @param string $userLogin
     * @param int $day
     * 
     * @return void
     */
    public function saveUserOffsetDay($userLogin, $day) {
        $userLoginF = ubRouting::filters($userLogin, 'mres');
        $day = ubRouting::filters($day, 'int');
        if (!empty($day)) {
            if (isset($this->systemUsers[$userLogin])) {
                $userData = $this->systemUsers[$userLogin];
                $userTariff = $userData['Tariff'];
                $this->usersDb->where('login', '=', $userLoginF);
                $this->usersDb->data('day', $day);
                $this->usersDb->save();
                log_register('PT USER (' . $userLogin . ') SET DAY `' . $day . '`');
                $this->logUser($userLogin, $userTariff, $day);
            }
        }
    }

    /**
     * Renders powertariffs internal log data for some user
     * 
     * @param string $userLogin
     * 
     * @return string
     */
    public function renderPowerUserLog($userLogin) {
        $result = '';
        $userLoginF = ubRouting::filters($userLogin, 'mres');
        $this->journalDb->where('login', '=', $userLoginF);
        $this->journalDb->orderBy('id', 'DESC');
        $allEvents = $this->journalDb->getAll();
        if (!empty($allEvents)) {
            $cells = wf_TableCell(__('Date'));
            $cells .= wf_TableCell(__('Tariff'));
            $cells .= wf_TableCell(__('Day'));
            $rows = wf_TableRow($cells, 'row1');

            foreach ($allEvents as $io => $each) {
                $cells = wf_TableCell($each['date']);
                $cells .= wf_TableCell($each['tariff']);
                $cells .= wf_TableCell($each['day']);
                $rows .= wf_TableRow($cells, 'row5');
            }

            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        return($result);
    }

    /**
     * Performs fee processing for users affected by power tariffs
     * 
     * @return void
     */
    public function processingFee() {
        if (!empty($this->systemUsers)) {
            $realCurrentDay = date("d");
            foreach ($this->systemUsers as $userLogin => $userData) {
                //user is affected by some power tariff
                if (isset($this->allUsers[$userLogin])) {
                    $userDayOffset = $this->allUsers[$userLogin];
                    //now user is on the power tariff
                    if ($this->userHavePowerTariff($userData)) {
                        $tariffData = $this->allTariffs[$userData['Tariff']];
                        $tariffFee = $tariffData['fee'];

                        //now is user personal date for fee charge
                        if ($userDayOffset == $this->currentDay AND $realCurrentDay == $this->currentDay) { // A-A-A!!!!
                            //user is active, and we can charge some fee from him
                            if ($this->isUserActive($userData)) {
                                //charge some fee from this user
                                $this->chargeFee($userLogin, $tariffFee, $userData['Cash']);
                                //new user balance state after fee charge
                                $newBalanceState = $userData['Cash'] - $tariffFee;
                                if ($newBalanceState < '-' . $userData['Credit']) { //not <= because zero cash is valid value
                                    $this->userBurial($userLogin); //settin offset to zero
                                    $this->logUser($userLogin, $userData['Tariff'], 0); //log user burial
                                }
                            }
                        } else {
                            //not current user day or user is buried
                            if ($userDayOffset == 0) {
                                //yeah, he is really buried
                                if ($this->isUserActive($userData)) {
                                    //but he restored his account balance
                                    $this->userResurrect($userLogin); //set new offset day to current
                                    $this->logUser($userLogin, $userData['Tariff'], $this->currentDay); //log resurrection miracle
                                }
                            }
                        }
                    }
                }
            }
        }
    }

}
