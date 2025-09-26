<?php

/**
 * UKV cable TV accounting implementation
 */
class UkvSystem {

    /**
     * Available tariffs as id=>data
     *
     * @var array
     */
    protected $tariffs = array();

    /**
     * Available users and therir data as id=>data
     *
     * @var array
     */
    protected $users = array();

    /**
     * Available cities from directory
     *
     * @var array
     */
    protected $cities = array('' => '-');

    /**
     * Available streets from directory
     *
     * @var array
     */
    protected $streets = array('' => '-');

    /**
     * Available system cashtypes
     *
     * @var array
     */
    protected $cashtypes = array();

    /**
     * Default month array with localized names
     * 
     * @var array
     */
    protected $month = array();

    /**
     * Currently assigned users contracts as contract=>userid
     *
     * @var array
     */
    protected $contracts = array();

    /**
     * Preprocessed banksta records
     *
     * @var array
     */
    protected $bankstarecords = array();

    /**
     * Some magic goes here
     *
     * @var array
     */
    protected $bankstafoundusers = array();

    /**
     * Contains all available tagtypes as id=>data
     *
     * @var array
     */
    protected $allTagtypes = array();

    /**
     * Contains all available user tags
     *
     * @var array
     */
    protected $allUserTags = array();

    /**
     * System alter.ini config represented as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * System message helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * UbillingConfig object placeholder
     *
     * @var null
     */
    protected $ubConfig = null;

    //static routing URLs

    const URL_TARIFFS_MGMT = '?module=ukv&tariffs=true'; //tariffs management
    const URL_USERS_MGMT = '?module=ukv&users=true'; //users management
    const URL_USERS_LIST = '?module=ukv&users=true&userslist=true'; //users list route
    const URL_USERS_PROFILE = '?module=ukv&users=true&showuser='; //user profile
    const URL_USERS_LIFESTORY = '?module=ukv&users=true&lifestory=true&showuser='; //user lifestory
    const URL_USERS_REGISTER = '?module=ukv&users=true&register=true'; //users registration route
    const URL_USERS_AJAX_SOURCE = '?module=ukv&ajax=true'; //ajax datasource for JQuery data tables
    const URL_INET_USER_PROFILE = '?module=userprofile&username='; //internet user profile
    const URTL_USERS_ANIHILATION = '?module=ukv&users=true&deleteuser='; // user extermination form
    const URL_BANKSTA_MGMT = '?module=ukv&banksta=true'; //bank statements processing url
    const URL_BANKSTA_PROCESSING = '?module=ukv&banksta=true&showhash='; // bank statement processing url
    const URL_BANKSTA_DETAILED = '?module=ukv&banksta=true&showdetailed='; //detailed banksta row display url
    const URL_REPORTS_LIST = '?module=ukv&reports=true&showreport=reportList'; //reports listing link
    const URL_REPORTS_MGMT = '?module=ukv&reports=true&showreport='; //reports listing link
    const URL_PHOTOSTORAGE = '?module=photostorage&scope=UKVUSERPROFILE&mode=list&itemid='; //photostorage link
    //registration options
    const REG_ACT = 1;
    const REG_CASH = 0;

    //misc options

    protected $debtLimit = 2; //debt limit in month count

    //bank statements options (Oschadbank)

    const BANKSTA_IN_CHARSET = 'cp866';
    const BANKSTA_OUT_CHARSET = 'utf-8';
    const BANKSTA_PATH = 'content/documents/ukv_banksta/';
    const BANKSTA_CONTRACT = 'ABCOUNT';
    const BANKSTA_ADDRESS = 'ADDR';
    const BANKSTA_REALNAME = 'FIO';
    const BANKSTA_SUMM = 'SUMM';
    const BANKSTA_NOTES = 'NAME_PLAT';
    const BANKSTA_TIME = 'PTIME';
    const BANKSTA_DATE = 'PDATE';
    //bank statements options (Oschadbank terminals)
    const OT_BANKSTA_CONTRACT = 'ABCOUNTT';
    const OT_BANKSTA_ADDRESS = 'ADDRT';
    const OT_BANKSTA_REALNAME = 'FIOTDT';
    const OT_BANKSTA_SUMM = 'SUMMT';
    const OT_BANKSTA_NOTES = 'NAME_PLAT';
    const OT_BANKSTA_TIME = 'PTIMETT';
    const OT_BANKSTA_DATE = 'PDATETT';
    //bank statements options (PrivatBank dbf)
    const PB_BANKSTA_CONTRACT = 'N_DOGOV';
    const PB_BANKSTA_ADDRESS = 'ADR_TEL';
    const PB_BANKSTA_REALNAME = 'FIO_PLAT';
    const PB_BANKSTA_SUMM = 'SUMMA';
    const PB_BANKSTA_NOTES = 'N_DOKUM';
    const PB_BANKSTA_TIME = 'NOPE';
    const PB_BANKSTA_DATE = 'OPERDEN';
    //finance coloring options
    const COLOR_FEE = 'a90000';
    const COLOR_PAYMENT = '005304';
    const COLOR_CORRECTING = 'ff6600';
    const COLOR_MOCK = '006699';
    //some exeptions
    const EX_TARIFF_FIELDS_EMPTY = 'EMPTY_TARIFF_OPTS_RECEIVED';
    const EX_USER_NOT_EXISTS = 'NO_EXISTING_UKV_USER';
    const EX_USER_NOT_SET = 'NO_VALID_USERID_RECEIVED';
    const EX_USER_NO_TARIFF_SET = 'NO_TARIFF_SET';
    const EX_USER_NOT_ACTIVE = 'USER_NOT_ACTIVE';
    const EX_BANKSTA_PREPROCESS_EMPTY = 'BANK_STATEMENT_INPUT_INVALID';

    /**
     * Creates new UKV instance
     */
    public function __construct() {
        $this->loadConfigs();
        $this->loadTariffs();
        $this->loadUsers();
        $this->loadCities();
        $this->loadStreets();
        $this->loadMonth();
        $this->loadDebtLimit();
        $this->loadTagtypes();
        $this->loadUsertags();
        $this->initMessages();
    }

    /**
     * Loads needed system configs into private data property
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfigs() {
        global $ubillingConfig;

        $this->ubConfig = $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * loads all tariffs into private tariffs prop
     * 
     * @return void
     */
    protected function loadTariffs() {
        $query = "SELECT * from `ukv_tariffs` ORDER by `tariffname` ASC;";
        $alltariffs = simple_queryall($query);
        if (!empty($alltariffs)) {
            foreach ($alltariffs as $io => $each) {
                $this->tariffs[$each['id']] = $each;
            }
        }
    }

    /**
     * loads all existing cities into private cities prop
     * 
     * @return void
     */
    protected function loadCities() {
        $query = "SELECT * from `city` ORDER BY `id` ASC;";
        $allcities = simple_queryall($query);
        if (!empty($allcities)) {
            foreach ($allcities as $io => $each) {
                $this->cities[$each['cityname']] = $each['cityname'];
            }
        }
    }

    /**
     * loads all existing streets into private streets prop
     * 
     * @return void
     */
    protected function loadStreets() {
        $query = "SELECT DISTINCT `streetname` from `street` ORDER BY `streetname` ASC;";
        $allstreets = simple_queryall($query);
        if (!empty($allstreets)) {
            foreach ($allstreets as $io => $each) {
                $this->streets[$each['streetname']] = $each['streetname'];
            }
        }
    }

    /**
     * load all existing cashtypes into private cashtypes prop
     * 
     * @return void
     */
    protected function loadCashtypes() {
        $query = "SELECT `id`,`cashtype` from `cashtype` ORDER BY `id` ASC;";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->cashtypes[$each['id']] = __($each['cashtype']);
            }
        }
    }

    /**
     * loads current month data into private props
     * 
     * @return void
     */
    protected function loadMonth() {
        $monthArr = months_array();
        $this->month['currentmonth'] = date("m");
        $this->month['currentyear'] = date("Y");;
        foreach ($monthArr as $num => $each) {
            $this->month['names'][$num] = rcms_date_localise($each);
        }
    }

    /**
     * loads current debt limit from global config
     * 
     * @return void
     */
    protected function loadDebtLimit() {
        global $ubillingConfig;
        $altCfg = $ubillingConfig->getAlter();
        $this->debtLimit = $altCfg['UKV_MONTH_DEBTLIMIT'];
    }

    /**
     * Loads all available tagstypes
     * 
     * @return void
     */
    protected function loadTagtypes() {
        $query = "SELECT * from `tagtypes`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allTagtypes[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads all available usertags
     * 
     * @return void
     */
    protected function loadUsertags() {
        $query = "SELECT * from `ukv_tags`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allUserTags[$each['id']] = $each;
            }
        }
    }

    /**
     * Inits message helper object for further usage
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * creates new tariff into database
     * 
     * @param string $name  tariff name
     * @param float $price tariff price 
     * 
     * @return void
     */
    public function tariffCreate($name, $price) {
        $name = mysql_real_escape_string($name);
        $name = trim($name);
        $price = mysql_real_escape_string($price);
        $price = trim($price);
        if (!empty($name)) {
            $price = (empty($price)) ? 0 : $price;
            $query = "INSERT INTO `ukv_tariffs` (`id`, `tariffname`, `price`) VALUES (NULL, '" . $name . "', '" . $price . "');";
            nr_query($query);
            log_register("UKV TARIFF CREATE `" . $name . "` WITH PRICE `" . $price . "`");
        } else {
            throw new Exception(self::EX_TARIFF_FIELDS_EMPTY);
        }
    }

    /**
     * check is tariff protected/used by some users
     * 
     * @param int $tariffid  existing tariff ID
     * 
     * @return bool
     */
    protected function tariffIsProtected($tariffid) {
        $tariffid = vf($tariffid, 3);
        $query = "SELECT `id` from `ukv_users` WHERE `tariffid`='" . $tariffid . "';";
        $data = simple_query($query);
        if (empty($data)) {
            return (false);
        } else {
            return (true);
        }
    }

    /**
     * deletes some existing tariff from database
     * 
     * @param int $tariffid existing tariff ID
     * 
     * @return void
     */
    public function tariffDelete($tariffid) {
        $tariffid = vf($tariffid, 3);
        //check - is tariff used by anyone?
        if (!$this->tariffIsProtected($tariffid)) {
            $tariffName = $this->tariffs[$tariffid]['tariffname'];
            $query = "DELETE from `ukv_tariffs` WHERE `id`='" . $tariffid . "'";
            nr_query($query);
            log_register("UKV TARIFF DELETE `" . $tariffName . "`  [" . $tariffid . "]");
        } else {
            log_register("UKV TARIFF DELETE PROTECTED TRY [" . $tariffid . "]");
        }
    }

    /**
     * saves some tariff params into database
     * 
     * @param int $tariffid    existing tariff ID
     * @param string $tariffname  new name of the tariff
     * @param float $price       new tariff price
     */
    public function tariffSave($tariffid, $tariffname, $price) {
        $tariffid = vf($tariffid, 3);
        $tariffname = mysql_real_escape_string($tariffname);
        $tariffname = trim($tariffname);
        $price = mysql_real_escape_string($price);
        $price = trim($price);

        if (!empty($tariffname)) {
            $price = (empty($price)) ? 0 : $price;
            $query = "UPDATE `ukv_tariffs` SET `tariffname` = '" . $tariffname . "', `price` = '" . $price . "' WHERE `id` = '" . $tariffid . "';";
            nr_query($query);
            log_register("UKV TARIFF CHANGE `" . $tariffname . "` WITH PRICE `" . $price . "`  [" . $tariffid . "]");
        } else {
            throw new Exception(self::EX_TARIFF_FIELDS_EMPTY);
        }
    }

    /**
     * returns tariff edit form 
     * 
     * @param int $tariffid existing tariff id
     * 
     * @rerturn string
     */
    protected function tariffEditForm($tariffid) {
        $tariffid = vf($tariffid, 3);

        $inputs = wf_HiddenInput('edittariff', $tariffid);
        $inputs .= wf_TextInput('edittariffname', __('Tariff name'), $this->tariffs[$tariffid]['tariffname'], true, '20');
        $inputs .= wf_TextInput('edittariffprice', __('Tariff Fee'), $this->tariffs[$tariffid]['price'], true, '5');
        $inputs .= wf_Submit(__('Save'));
        $result = wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * returns tariff creation form
     * 
     * @return string
     */
    protected function tariffCreateForm() {
        $inputs = wf_HiddenInput('createtariff', 'true');
        $inputs .= wf_TextInput('createtariffname', __('Tariff name'), '', true, '20');
        $inputs .= wf_TextInput('createtariffprice', __('Tariff Fee'), '', true, '5');
        $inputs .= wf_Submit(__('Create new tariff'));
        $result = wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * renders CaTV tariffs list with some controls
     * 
     * @return void
     */
    public function renderTariffs() {

        $cells = wf_TableCell(__('ID'));
        $cells .= wf_TableCell(__('Tariff name'));
        $cells .= wf_TableCell(__('Tariff Fee'));
        $cells .= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($this->tariffs)) {
            foreach ($this->tariffs as $io => $each) {
                $cells = wf_TableCell($each['id']);
                $cells .= wf_TableCell($each['tariffname']);
                $cells .= wf_TableCell($each['price']);
                $actlinks = wf_JSAlert(self::URL_TARIFFS_MGMT . '&tariffdelete=' . $each['id'], web_delete_icon(), __('Removing this may lead to irreparable results'));
                $actlinks .= wf_modal(web_edit_icon(), __('Edit') . ' ' . $each['tariffname'], $this->tariffEditForm($each['id']), '', '400', '200');
                $cells .= wf_TableCell($actlinks, '', '', $customkey = 'sorttable_customkey="0"'); //need this to keep table sortable
                $rows .= wf_TableRow($cells, 'row5');
            }
        }

        $result = wf_TableBody($rows, '100%', '0', 'sortable');
        $result .= wf_modal(wf_img('skins/plus.png', __('Create new tariff')), __('Create new tariff'), $this->tariffCreateForm(), '', '400', '200');
        return ($result);
    }

    /**
     * returns module control panel
     * 
     * @return string
     */
    public function panel() {
        $result = '';
        if (cfr('UKV')) {
            $result .= wf_Link(self::URL_USERS_LIST, wf_img('skins/ukv/users.png') . ' ' . __('Users'), false, 'ubButton');
        }
        if (cfr('UKVREG')) {
            $result .= wf_Link(self::URL_USERS_REGISTER, wf_img('skins/ukv/add.png') . ' ' . __('Users registration'), false, 'ubButton');
        }
        if (cfr('UKVTAR')) {
            $result .= wf_Link(self::URL_TARIFFS_MGMT, wf_img('skins/ukv/dollar.png') . ' ' . __('Tariffs'), false, 'ubButton');
        }
        if (cfr('UKVBST')) {
            $result .= wf_Link(self::URL_BANKSTA_MGMT, wf_img('skins/ukv/bank.png') . ' ' . __('Bank statements'), false, 'ubButton');
        }
        if (cfr('UKVREP')) {
            $result .= wf_Link(self::URL_REPORTS_LIST, wf_img('skins/ukv/report.png') . ' ' . __('Reports'), false, 'ubButton');
        }
        return ($result);
    }

    /**
     * loads all users from database to private prop users
     * 
     * @return void
     */
    protected function loadUsers() {
        $query = "SELECT * from `ukv_users`";
        $allusers = simple_queryall($query);
        if (!empty($allusers)) {
            foreach ($allusers as $io => $each) {
                $this->users[$each['id']] = $each;
                $this->contracts[$each['contract']] = $each['id'];
            }
        }
    }

    /**
     * just sets user balance to specified value
     * 
     * @param int $userid existing user id
     * @param float $cash   cash value to set
     * 
     * @return void
     */
    protected function userSetCash($userid, $cash) {
        if (isset($this->users[$userid])) {
            simple_update_field('ukv_users', 'cash', $cash, "WHERE `id`='" . $userid . "';");
        }
    }

    /**
     * logs payment to database
     * 
     * 
     * @param int $userid
     * @param float $summ
     * @param bool $visible
     * @param int $cashtypeid
     * @param string $notes
     * 
     * @return void
     */
    public function logPayment($userid, $summ, $visible = true, $cashtypeid = 1, $notes = '') {
        $userid = vf($userid, 3);
        $summ = mysql_real_escape_string($summ);
        $date = date("Y-m-d H:i:s");
        $admin = whoami();
        $currentBalance = $this->users[$userid]['cash'];
        $visible = ($visible) ? 1 : 0;
        $cashtypeid = vf($cashtypeid, 3);
        $notes = mysql_real_escape_string($notes);

        $query = "INSERT INTO `ukv_payments` (`id` , `userid` ,  `date` , `admin` , `balance` , `summ` , `visible` , `cashtypeid` , `note`)
        VALUES (NULL , '" . $userid . "', '" . $date . "', '" . $admin . "', '" . $currentBalance . "', '" . $summ . "', '" . $visible . "', '" . $cashtypeid . "', '" . $notes . "');";
        nr_query($query);
    }

    /**
     * External interface for private setCash method used in manual finance ops
     * 
     * @param int $userid
     * @param float $summ
     * @param bool $visible
     * @param int $cashtypeid
     * @param string $notes
     * 
     * @return void
     */
    public function userAddCash($userid, $summ, $visible = true, $cashtypeid = 1, $notes = '') {
        $userid = vf($userid, 3);
        $summ = mysql_real_escape_string($summ);
        $currentBalance = $this->users[$userid]['cash'];

        //create transaction record
        $this->logPayment($userid, $summ, $visible, $cashtypeid, $notes);
        //push payment to user
        $newCashValue = $currentBalance + $summ;
        $this->userSetCash($userid, $newCashValue);
        $this->users[$userid]['cash'] = $newCashValue;
        log_register('UKV BALANCEADD ((' . $userid . ')) ON ' . $summ);
    }

    /**
     * checks is input number valid money format or not?
     * 
     * @param float $number an string to check
     * 
     * @return bool 
     */
    public function isMoney($number) {
        return preg_match("/^-?[0-9]+(?:\.[0-9]{1,2})?$/", $number);
    }

    /**
     * charges month fee for some user
     * 
     * @param int $userid  existing user ID
     * 
     * @return void
     */
    protected function feeCharge($userid) {
        $userid = vf($userid, 3);
        if ($this->users[$userid]['tariffid']) {
            $tariffId = $this->users[$userid]['tariffid'];
            $tariffName = $this->tariffs[$tariffId]['tariffname'];
            $tariffPrice = $this->tariffs[$tariffId]['price'];
            $montlyFee = abs($tariffPrice);
            $currentBalance = $this->users[$userid]['cash'];
            $newCash = $currentBalance - $montlyFee;
            $currentMonth = $this->month['currentmonth'];
            $currentMonthName = $this->month['names'][$currentMonth];
            $currentYear = $this->month['currentyear'];
            if ($this->users[$userid]['active']) {
                $notes = 'UKVFEE:' . $tariffName . ' ' . $currentMonthName . ' ' . $currentYear;
                $this->logPayment($userid, ('-' . $montlyFee), false, 1, $notes);
                $this->userSetCash($userid, $newCash);
            } else {
                $notes = 'UKVFEE: ' . self::EX_USER_NOT_ACTIVE;
                $this->logPayment($userid, 0, false, 1, $notes);
            }
        } else {
            //no tariff set - skipping
            $this->logPayment($userid, 0, false, 1, 'UKVFEE: ' . self::EX_USER_NO_TARIFF_SET);
        }
    }

    /**
     * logs fee charge fact to database
     * 
     * @return void
     */
    protected function feeChargeLog() {
        $curyearmonth = date("Y-m");
        $query = "INSERT INTO `ukv_fees` (`id`, `yearmonth`) VALUES (NULL, '" . $curyearmonth . "');";
        nr_query($query);
    }

    /**
     * Changes all users tariffs and reloads users data if required
     * 
     * @return void
     */
    protected function tariffsMoveAll() {
        if (!empty($this->users)) {
            foreach ($this->users as $io => $each) {
                if (!empty($each['tariffnmid'])) {
                    $newTariffId = $each['tariffnmid'];
                    if (isset($this->tariffs[$newTariffId])) {
                        $changeNow=true;
                        //may be tariff must be changed little bit later?
                        if (!empty($each['tariffnmdate'])) {
                            $changeNow=false;
                            if ($each['tariffnmdate'] <= date("Y-m-d")) {
                                $changeNow=true;
                            }
                        }

                        if ($changeNow) {
                            $userId = $each['id'];
                            $where = "WHERE `id`='" . $userId . "'";
                            //change tariff in database
                            simple_update_field('ukv_users', 'tariffid', $newTariffId, $where);
                            //update tariffs state in current instance
                            $this->users[$userId]['tariffid'] = $newTariffId;
                            $this->users[$userId]['tariffnmid'] = '';
                            $this->users[$userId]['tariffnmdate'] = '';
                            //drop tariffnm
                            simple_update_field('ukv_users', 'tariffnmid', '', $where);
                            //and change date too
                            simple_update_field('ukv_users', 'tariffnmdate', '', $where);
                        }
                    }
                }
            }
        }
    }

    /**
     * charges fee for all users and controls per month validity
     * 
     * @return int
     */
    public function feeChargeAll() {
        $curyearmonth = date("Y-m");
        $query_check = "SELECT `id` from `ukv_fees` WHERE `yearmonth`='" . $curyearmonth . "'";
        $feesProcessed = simple_query($query_check);
        $chargeCounter = 0;
        if (!$feesProcessed) {
            if (!empty($this->users)) {
                //previously moving tariffs if required
                $this->tariffsMoveAll();
                foreach ($this->users as $io => $each) {
                    $this->feeCharge($each['id']);
                    $chargeCounter++;
                }
            }

            log_register('UKV FEE CHARGED FOR ' . $chargeCounter . ' USERS');
            $this->feeChargeLog();
        } else {
            log_register('UKV FEE CHARGE DOUBLE TRY');
        }
        return ($chargeCounter);
    }

    /**
     * public interface view for manual payments processing
     * 
     * @param int $userid - existing user ID
     * 
     * @return string
     */
    public function userManualPaymentsForm($userid) {
        $userid = vf($userid, 3);
        $this->loadCashtypes();
        $inputs = '';
        $inputs .= wf_HiddenInput('manualpaymentprocessing', $userid);
        $inputs .= wf_TextInput('paymentsumm', __('New cash'), '', true, '5', '', '', 'UkvPaymSum');
        $inputs .= wf_RadioInput('paymenttype', __('Add cash'), 'add', false, true);
        $inputs .= wf_RadioInput('paymenttype', __('Correct saldo'), 'correct', false, false);
        $inputs .= wf_RadioInput('paymenttype', __('Mock payment'), 'mock', true, false);
        $inputs .= wf_Selector('paymentcashtype', $this->cashtypes, __('Cash type'), '', true);
        $inputs .= wf_TextInput('paymentnotes', __('Payment notes'), '', true, '40');
        $inputs .= wf_delimiter(0);

        if ($this->ubConfig->getAlterParam('DREAMKAS_ENABLED')) {
            $DreamKas = new DreamKas();
            $inputs .= $DreamKas->web_FiscalizePaymentCtrls('ukv');
            $inputs .= wf_tag('script', false, '', 'type="text/javascript"');
            $inputs .= '$(document).ready(function() {
                    // dirty hack with setTimeout() to work in Chrome 
                    setTimeout(function(){
                            $(\'#UkvPaymSum\').focus();
                    }, 100);
                  });   
                 ';
            $inputs .= wf_tag('script', true);
            $inputs .= wf_delimiter(0);
        }

        $inputs .= wf_Submit(__('Payment'));

        $result = wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * returns user full address if this one exists
     * 
     * @param int $userid   existing user id
     * 
     * @return string
     */
    public function userGetFullAddress($userid) {
        if (isset($this->users[$userid])) {
            global $ubillingConfig;
            $altcfg = $ubillingConfig->getAlter();
            //zero apt numbers as private builds
            if ($altcfg['ZERO_TOLERANCE']) {
                $apt = ($this->users[$userid]['apt'] == '0') ? '' : '/' . $this->users[$userid]['apt'];
            } else {
                $apt = '/' . $this->users[$userid]['apt'];
            }
            //city display
            if ($altcfg['CITY_DISPLAY']) {
                $city = $this->users[$userid]['city'] . ' ';
            } else {
                $city = '';
            }
            $result = $city . $this->users[$userid]['street'] . ' ' . $this->users[$userid]['build'] . $apt;
        } else {
            $result = '';
        }
        return ($result);
    }

    /**
     * Returns real name field for some user
     * 
     * @param int $userid
     * 
     * @return string
     */
    public function userGetRealName($userid) {
        $result = '';
        if (isset($this->users[$userid])) {
            $result = $this->users[$userid]['realname'];
        }
        return ($result);
    }

    /**
     * Returns existing tariff name by tariffid
     * 
     * @param int  $tariffid
     * 
     * @return string
     */
    public function tariffGetName($tariffid) {
        if ($this->tariffs[$tariffid]['tariffname']) {
            $result = $this->tariffs[$tariffid]['tariffname'];
        } else {
            $result = '';
        }
        return ($result);
    }

    /**
     * user deletion form
     * 
     * @param int $userid existing user ID
     * 
     * @return string
     */
    public function userDeletionForm($userid) {
        $userid = vf($userid, 3);
        $inputs = __('Be careful, this module permanently deletes user and all data associated with it. Opportunities to raise from the dead no longer.') . ' <br>
               ' . __('To ensure that we have seen the seriousness of your intentions to enter the word сonfirm the field below.');
        $inputs .= wf_HiddenInput('userdeleteprocessing', $userid);
        $inputs .= wf_delimiter();
        $inputs .= wf_tag('input', false, '', 'type="text" name="deleteconfirmation" autocomplete="off"');
        $inputs .= wf_tag('br');
        $inputs .= wf_Submit(__('I really want to stop suffering User'));

        $result = wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * deletes some user from database
     * 
     * @param int $userid
     * 
     * @return void
     */
    public function userDelete($userid) {
        $userid = vf($userid, 3);
        if (isset($this->users[$userid])) {
            $query = "DELETE from `ukv_users` WHERE `id`='" . $userid . "';";
            nr_query($query);
            log_register('UKV USER DELETED ((' . $userid . '))');
        } else {
            throw new Exception(self::EX_USER_NOT_EXISTS);
        }
    }

    /**
     * Returns user registration form
     * 
     * @return string
     */
    public function userRegisterForm() {
        $aptsel = '';
        $currentStep = 0;
        $registerSteps = array(
            __('Step') . ' 1' => __('Select city'),
            __('Step') . ' 2' => __('Select street'),
            __('Step') . ' 3' => __('Select build'),
            __('Success') => __('Confirm'),
        );


        if (!isset($_POST['citysel'])) {
            $citysel = web_CitySelectorAc(); // onChange="this.form.submit();
            $streetsel = '';
        } else {
            $citydata = zb_AddressGetCityData($_POST['citysel']);
            $citysel = $citydata['cityname'] . wf_HiddenInput('citysel', $citydata['id']);
            $streetsel = web_StreetSelectorAc($citydata['id']);
            $currentStep = 1;
        }

        if (isset($_POST['streetsel'])) {
            $streetdata = zb_AddressGetStreetData($_POST['streetsel']);
            $streetsel = $streetdata['streetname'] . wf_HiddenInput('streetsel', $streetdata['id']);
            $buildsel = web_BuildSelectorAc($_POST['streetsel']);
            $currentStep = 2;
        } else {
            $buildsel = '';
        }

        if (isset($_POST['buildsel'])) {
            $submit_btn = '';
            $builddata = zb_AddressGetBuildData($_POST['buildsel']);
            $buildsel = $builddata['buildnum'] . wf_HiddenInput('buildsel', $builddata['id']);
            $aptsel = wf_TextInput('uregapt', __('Apartment'), '', true, '4');

            $submit_btn .= wf_HiddenInput('userregisterprocessing', 'true');
            $submit_btn .= wf_tag('tr', false, 'row3');
            $submit_btn .= wf_tag('td', false);
            $submit_btn .= wf_Submit(__('Let register that user'));
            $submit_btn .= wf_tag('td', true);
            $submit_btn .= wf_tag('td', false);
            $submit_btn .= wf_tag('td', true);
            $submit_btn .= wf_tag('tr', true);
            $currentStep = 3;
        } else {
            $submit_btn = '';
        }


        $formInputs = wf_tag('tr', false, 'row3');
        $formInputs .= wf_tag('td', false, '', 'width="50%"') . $citysel . wf_tag('td', true);
        $formInputs .= wf_tag('td', false) . __('City') . wf_tag('td', true);
        $formInputs .= wf_tag('tr', true);

        $formInputs .= wf_tag('tr', false, 'row3');
        $formInputs .= wf_tag('td', false) . $streetsel . wf_tag('td', true);
        $formInputs .= wf_tag('td', false) . __('Street') . wf_tag('td', true);
        $formInputs .= wf_tag('tr', true);

        $formInputs .= wf_tag('tr', false, 'row3');
        $formInputs .= wf_tag('td', false) . $buildsel . wf_tag('td', true);
        $formInputs .= wf_tag('td', false) . __('Build') . wf_tag('td', true);
        $formInputs .= wf_tag('tr', true);

        $formInputs .= wf_tag('tr', false, 'row3');
        $formInputs .= wf_tag('td', false) . $aptsel . wf_tag('td', true);
        $formInputs .= wf_tag('td', false) . __('Apartment') . wf_tag('td', true);
        $formInputs .= wf_tag('tr', true);
        $formInputs .= $submit_btn;

        $formData = wf_Form('', 'POST', $formInputs);
        $form = wf_TableBody($formData, '100%', '0', 'glamour');
        $form .= wf_tag('div', false, '', 'style="clear:both;"') . wf_tag('div', true);

        $form .= wf_StepsMeter($registerSteps, $currentStep);
        return ($form);
    }

    /**
     * registers new users into database and returns new user ID
     * 
     * @return int 
     */
    public function userCreate() {
        $curdate = date("Y-m-d H:i:s");
        $query = "
            INSERT INTO `ukv_users` (
                            `id` ,
                            `contract` ,
                            `tariffid` ,
                            `tariffnmid` ,
                            `cash` ,
                            `active` ,
                            `realname` ,
                            `passnum` ,
                            `passwho` ,
                            `passdate` ,
                            `paddr`,
                            `ssn` ,
                            `phone` ,
                            `mobile` ,
                            `regdate` ,
                            `city` ,
                            `street` ,
                            `build` ,
                            `apt` ,
                            `inetlogin` ,
                            `notes`
                            )
                            VALUES (
                            NULL ,
                            NULL ,
                            NULL ,
                            NULL ,
                            '" . self::REG_CASH . "',
                            '" . self::REG_ACT . "',
                            NULL ,
                            NULL ,
                            NULL ,
                            NULL ,
                            NULL ,
                            NULL ,
                            NULL ,
                            NULL ,
                            '" . $curdate . "',
                            NULL ,
                            NULL ,
                            NULL ,
                            NULL ,
                            NULL ,
                            NULL
                            );  ";
        nr_query($query);
        $newUserId = simple_get_lastid('ukv_users');
        $result = $newUserId;
        log_register("UKV REGISTER USER ((" . $newUserId . "))");

        //saving post registration data
        $this->userPostRegSave($newUserId);

        return ($result);
    }

    /**
     * Returns cable seal edit form
     * 
     * @param int $userid Existing user ID
     * @return string
     */
    protected function userCableSealForm($userid) {
        $userid = vf($userid, 3);
        $result = '';
        if (isset($this->users[$userid])) {
            $currentSeal = $this->users[$userid]['cableseal'];

            $inputs = wf_TextInput('ueditcableseal', __('Cable seal'), $currentSeal, true, 20);
            $inputs .= wf_HiddenInput('usercablesealprocessing', $userid);
            $inputs .= wf_Submit(__('Save'));
            $result = wf_Form('', 'POST', $inputs, 'glamour');
        }
        return ($result);
    }

    /**
     * returns user edit form for some userid
     * 
     * @param int $userid  existing user ID
     * 
     * @return string
     */
    protected function userEditForm($userid) {
        $userid = vf($userid, 3);
        if (isset($this->users[$userid])) {
            $userData = $this->users[$userid];
            $switchArr = array('1' => __('Yes'), '0' => __('No'));
            $tariffArr = array();
            $tariffnmArr = array('' => '-');

            if (!empty($this->tariffs)) {
                foreach ($this->tariffs as $io => $each) {
                    $tariffArr[$each['id']] = $each['tariffname'];
                }
            }

            if (!empty($this->tariffs)) {
                foreach ($this->tariffs as $io => $each) {
                    //excluding current tariff
                    if ($userData['tariffid'] != $each['id']) {
                        $tariffnmArr[$each['id']] = $each['tariffname'];
                    }
                }
            }



            $inputs = '';

            $inputs = wf_tag('tr', false);
            $inputs .= wf_tag('td', false, '', 'valign="top"');

            $inputs .= wf_HiddenInput('usereditprocessing', $userid);
            $inputs .= wf_tag('div', false, 'floatpanelswide');
            $inputs .= wf_tag('h3') . __('Full address') . wf_tag('h3', true);
            $inputs .= wf_Selector('ueditcity', $this->cities, __('City'), $userData['city'], true);
            $inputs .= wf_Selector('ueditstreet', $this->streets, __('Street'), $userData['street'], true);
            $inputs .= wf_TextInput('ueditbuild', __('Build'), $userData['build'], false, '5');
            $inputs .= wf_TextInput('ueditapt', __('Apartment'), $userData['apt'], true, '4');
            $inputs .= wf_tag('div', true);

            $inputs .= wf_tag('td', true);
            $inputs .= wf_tag('td', false, '', 'valign="top"');

            $inputs .= wf_tag('div', false, 'floatpanelswide');
            $inputs .= wf_tag('h3') . __('Contact info') . wf_tag('h3', true);
            $inputs .= wf_TextInput('ueditrealname', __('Real Name'), $userData['realname'], true, '30');
            $inputs .= wf_TextInput('ueditphone', __('Phone'), $userData['phone'], true, '20');
            $inputs .= wf_TextInput('ueditmobile', __('Mobile'), $userData['mobile'], true, '20');
            $inputs .= wf_tag('div', true);

            $inputs .= wf_tag('td', true);
            $inputs .= wf_tag('tr', true);
            $inputs .= wf_tag('td', false, '', 'valign="top"');

            $inputs .= wf_tag('div', false, 'floatpanelswide');
            $inputs .= wf_tag('h3') . __('Services') . wf_tag('h3', true);
            $inputs .= wf_TextInput('ueditcontract', __('Contract'), $userData['contract'], true, '10');
            $inputs .= wf_Selector('uedittariff', $tariffArr, __('Tariff'), $userData['tariffid'], true);
            $inputs .= wf_Selector('uedittariffnm', $tariffnmArr, __('Next month'), $userData['tariffnmid'], true);
            $inputs .= wf_DatePickerPreset('uedittariffnmdate', $userData['tariffnmdate'], true) . __('Move tariff after').wf_delimiter(0);
            $inputs .= wf_Selector('ueditactive', $switchArr, __('Connected'), $userData['active'], true);
            $inputs .= wf_TextInput('ueditregdate', __('Contract date'), $userData['regdate'], true, '20');
            $inputs .= wf_TextInput('ueditinetlogin', __('Login'), $userData['inetlogin'], true, '20');
            $inputs .= wf_tag('div', true);

            $inputs .= wf_tag('td', true);
            $inputs .= wf_tag('td', false, '', 'valign="top"');

            $inputs .= wf_tag('div', false, 'floatpanelswide');
            $inputs .= wf_tag('h3') . __('Passport data') . wf_tag('h3', true);
            $inputs .= wf_TextInput('ueditpassnum', __('Passport number'), $userData['passnum'], true, '20');
            $inputs .= wf_TextInput('ueditpasswho', __('Issuing authority'), $userData['passwho'], true, '20');
            $inputs .= wf_DatePickerPreset('ueditpassdate', $userData['passdate'], true) . __('Date of issue') . wf_tag('br');
            $inputs .= wf_TextInput('ueditssn', __('SSN'), $userData['ssn'], true, '20');
            $inputs .= wf_TextInput('ueditpaddr', __('Registration address'), $userData['paddr'], true, '20');
            $inputs .= wf_tag('div', true);

            $inputs .= wf_tag('td', true);
            $inputs .= wf_tag('tr', true);

            $inputs .= wf_tag('tr', false);
            $inputs .= wf_tag('td', false, '', 'colspan="2" valign="top"');

            $inputs .= wf_tag('div', false, 'floatpanelswide');
            $inputs .= wf_TextInput('ueditnotes', __('Notes'), $userData['notes'], false, '60');
            $inputs .= wf_tag('div', true);

            $inputs .= wf_tag('td', true);
            $inputs .= wf_tag('tr', true);

            $inputs .= wf_tag('tr', false);
            $inputs .= wf_tag('td', false, '', 'colspan="2" valign="top"');
            $inputs .= wf_Submit(__('Save'));
            $inputs .= wf_tag('td', true);
            $inputs .= wf_tag('tr', true);

            $inputs = wf_TableBody($inputs, '800', 0, '');


            $result = wf_Form('', 'POST', $inputs, 'ukvusereditform');



            return ($result);
        }
    }

    /**
     * returns user lifestory strict parsed from system log
     * 
     * @param int $userid existing user id
     * 
     * @return string
     */
    public function userLifeStoryForm($userid) {
        $userid = vf($userid, 3);
        $query = "SELECT * from `weblogs` WHERE `event` LIKE '%((" . $userid . "))%' ORDER BY `id` DESC;";
        $all = simple_queryall($query);

        $cells = wf_TableCell(__('ID'));
        $cells .= wf_TableCell(__('Who?'));
        $cells .= wf_TableCell(__('When?'));
        $cells .= wf_TableCell(__('What happen?'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $cells = wf_TableCell($each['id']);
                $cells .= wf_TableCell($each['admin']);
                $cells .= wf_TableCell($each['date']);
                $cells .= wf_TableCell($each['event']);
                $rows .= wf_TableRow($cells, 'row5');
            }
        }
        $result = wf_TableBody($rows, '100%', '0', 'sortable');
        return ($result);
    }

    /**
     * checks is user contract unique
     * 
     * @param string $contract - contract number to check
     * 
     * @return bool
     */
    protected function checkContract($contract) {
        if (isset($this->contracts[$contract])) {
            $result = false;
        } else {
            $result = true;
        }
        return ($result);
    }

    /**
     * Saves new cable seal value into database
     * 
     * @throws Exception
     * @return void
     */
    public function userCableSealSave() {
        if (wf_CheckPost(array('usercablesealprocessing'))) {
            $userId = vf($_POST['usercablesealprocessing']);
            $where = "WHERE `id`='" . $userId . "';";
            $tablename = 'ukv_users';
            $newSeal = vf($_POST['ueditcableseal']);

            if ($this->users[$userId]['cableseal'] != $newSeal) {
                simple_update_field($tablename, 'cableseal', $newSeal, $where);
                log_register('UKV USER ((' . $userId . ')) CHANGE CABLESEAL `' . $newSeal . '`');
            }
        } else {
            throw new Exception(self::EX_USER_NOT_SET);
        }
    }

    /**
     * saves some user params into database
     * 
     * @return void
     */
    public function userSave() {
        if (wf_CheckPost(array('usereditprocessing'))) {
            $userId = ubRouting::post('usereditprocessing', 'int');
            $where = "WHERE `id`='" . $userId . "';";
            $tablename = 'ukv_users';

            //saving city
            if ($this->users[$userId]['city'] != $_POST['ueditcity']) {
                simple_update_field($tablename, 'city', $_POST['ueditcity'], $where);
                log_register('UKV USER ((' . $userId . ')) CHANGE CITY `' . $_POST['ueditcity'] . '`');
            }

            //saving street
            if ($this->users[$userId]['street'] != $_POST['ueditstreet']) {
                simple_update_field($tablename, 'street', $_POST['ueditstreet'], $where);
                log_register('UKV USER ((' . $userId . ')) CHANGE STREET `' . $_POST['ueditstreet'] . '`');
            }

            //saving build
            if ($this->users[$userId]['build'] != $_POST['ueditbuild']) {
                $newBuild = $this->filterStringData($_POST['ueditbuild']);
                simple_update_field($tablename, 'build', $newBuild, $where);
                log_register('UKV USER ((' . $userId . ')) CHANGE BUILD `' . $newBuild . '`');
            }

            //saving apartment
            if ($this->users[$userId]['apt'] != $_POST['ueditapt']) {
                $newApt = $this->filterStringData($_POST['ueditapt']);
                simple_update_field($tablename, 'apt', $newApt, $where);
                log_register('UKV USER ((' . $userId . ')) CHANGE APT `' . $newApt . '`');
            }

            //saving realname
            if ($this->users[$userId]['realname'] != $_POST['ueditrealname']) {
                $newRealname = $this->filterStringData($_POST['ueditrealname']);
                simple_update_field($tablename, 'realname', $newRealname, $where);
                log_register('UKV USER ((' . $userId . ')) CHANGE REALNAME `' . $newRealname . '`');
            }

            //saving phone
            if ($this->users[$userId]['phone'] != $_POST['ueditphone']) {
                simple_update_field($tablename, 'phone', $_POST['ueditphone'], $where);
                log_register('UKV USER ((' . $userId . ')) CHANGE PHONE `' . $_POST['ueditphone'] . '`');
            }

            //saving mobile number
            if ($this->users[$userId]['mobile'] != $_POST['ueditmobile']) {
                simple_update_field($tablename, 'mobile', $_POST['ueditmobile'], $where);
                log_register('UKV USER ((' . $userId . ')) CHANGE MOBILE `' . $_POST['ueditmobile'] . '`');
            }

            //saving contract
            if ($this->users[$userId]['contract'] != $_POST['ueditcontract']) {
                $newContract = trim($_POST['ueditcontract']);
                if ($this->checkContract($newContract)) {
                    simple_update_field($tablename, 'contract', $newContract, $where);
                    log_register('UKV USER ((' . $userId . ')) CHANGE CONTRACT `' . $newContract . '`');
                } else {
                    log_register('UKV USER ((' . $userId . ')) CHANGE FAIL CONTRACT `' . $newContract . '` DUPLICATE');
                }
            }

            //saving tariff
            if ($this->users[$userId]['tariffid'] != ubRouting::post('uedittariff', 'int')) {
                simple_update_field($tablename, 'tariffid', ubRouting::post('uedittariff', 'int'), $where);
                log_register('UKV USER ((' . $userId . ')) CHANGE TARIFF [' . $_POST['uedittariff'] . ']');
            }

            //saving next month tariff
            if ($this->users[$userId]['tariffnmid'] != ubRouting::post('uedittariffnm', 'int')) {
                simple_update_field($tablename, 'tariffnmid', ubRouting::post('uedittariffnm', 'int'), $where);
                log_register('UKV USER ((' . $userId . ')) CHANGE TARIFFNM [' . $_POST['uedittariffnm'] . ']');
            }

            //saving next month tariff change date
            if ($this->users[$userId]['tariffnmdate'] != ubRouting::post('uedittariffnmdate')) {
                $newTariffNmDate = ubRouting::post('uedittariffnmdate','mres');
                simple_update_field($tablename, 'tariffnmdate', $newTariffNmDate, $where);
                log_register('UKV USER ((' . $userId . ')) CHANGE TARIFFNMDATE `' . $newTariffNmDate . '`');
            }

            //saving user activity
            if ($this->users[$userId]['active'] != $_POST['ueditactive']) {
                simple_update_field($tablename, 'active', $_POST['ueditactive'], $where);
                log_register('UKV USER ((' . $userId . ')) CHANGE ACTIVE `' . $_POST['ueditactive'] . '`');
            }

            //saving registration date
            if ($this->users[$userId]['regdate'] != $_POST['ueditregdate']) {
                simple_update_field($tablename, 'regdate', $_POST['ueditregdate'], $where);
                log_register('UKV USER ((' . $userId . ')) CHANGE REGDATE `' . $_POST['ueditregdate'] . '`');
            }

            //saving user internet backlinking
            if ($this->users[$userId]['inetlogin'] != $_POST['ueditinetlogin']) {
                simple_update_field($tablename, 'inetlogin', $_POST['ueditinetlogin'], $where);
                log_register('UKV USER ((' . $userId . ')) CHANGE INETLOGIN (' . $_POST['ueditinetlogin'] . ')');
            }

            //saving passport number
            if ($this->users[$userId]['passnum'] != $_POST['ueditpassnum']) {
                simple_update_field($tablename, 'passnum', $_POST['ueditpassnum'], $where);
                log_register('UKV USER ((' . $userId . ')) CHANGE PASSPORTNUM `' . $_POST['ueditpassnum'] . '`');
            }

            //saving passport issuing authority
            if ($this->users[$userId]['passwho'] != $_POST['ueditpasswho']) {
                simple_update_field($tablename, 'passwho', $_POST['ueditpasswho'], $where);
                log_register('UKV USER ((' . $userId . ')) CHANGE PASSPORTWHO `' . $_POST['ueditpasswho'] . '`');
            }

            //saving passport issue date
            if ($this->users[$userId]['passdate'] != $_POST['ueditpassdate']) {
                simple_update_field($tablename, 'passdate', $_POST['ueditpassdate'], $where);
                log_register('UKV USER ((' . $userId . ')) CHANGE PASSPORTDATE `' . $_POST['ueditpassdate'] . '`');
            }

            //saving user SSN
            if ($this->users[$userId]['ssn'] != $_POST['ueditssn']) {
                simple_update_field($tablename, 'ssn', $_POST['ueditssn'], $where);
                log_register('UKV USER ((' . $userId . ')) CHANGE SSN `' . $_POST['ueditssn'] . '`');
            }

            //saving user registration address
            if ($this->users[$userId]['paddr'] != $_POST['ueditpaddr']) {
                simple_update_field($tablename, 'paddr', $_POST['ueditpaddr'], $where);
                log_register('UKV USER ((' . $userId . ')) CHANGE  PASSADDRESS`' . $_POST['ueditpaddr'] . '`');
            }

            //saving user notes
            if ($this->users[$userId]['notes'] != $_POST['ueditnotes']) {
                simple_update_field($tablename, 'notes', $_POST['ueditnotes'], $where);
                log_register('UKV USER ((' . $userId . ')) CHANGE  NOTES `' . $_POST['ueditnotes'] . '`');
            }
        } else {
            throw new Exception(self::EX_USER_NOT_SET);
        }
    }

    /**
     * protected method using to save address data for newly registered user
     * 
     * @param int $userId - existin new user ID
     * 
     * @return void
     */
    protected function userPostRegSave($userId) {
        $citydata = zb_AddressGetCityData($_POST['citysel']);
        $streetdata = zb_AddressGetStreetData($_POST['streetsel']);
        $builddata = zb_AddressGetBuildData($_POST['buildsel']);
        $whereReg = "WHERE `id` = '" . $userId . "';";
        simple_update_field('ukv_users', 'city', $citydata['cityname'], $whereReg);
        log_register('UKV USER ((' . $userId . ')) CHANGE CITY `' . $citydata['cityname'] . '`');

        simple_update_field('ukv_users', 'street', $streetdata['streetname'], $whereReg);
        log_register('UKV USER ((' . $userId . ')) CHANGE STREET `' . $streetdata['streetname'] . '`');


        $newBuild = $this->filterStringData($builddata['buildnum']);
        simple_update_field('ukv_users', 'build', $newBuild, $whereReg);
        log_register('UKV USER ((' . $userId . ')) CHANGE BUILD `' . $builddata['buildnum'] . '`');

        $newApt = (!empty($_POST['uregapt'])) ? $_POST['uregapt'] : 0;
        $newApt = $this->filterStringData($newApt);
        simple_update_field('ukv_users', 'apt', $newApt, $whereReg);
        log_register('UKV USER ((' . $userId . ')) CHANGE APT `' . $newApt . '`');
    }

    /**
     * Returns tags edit interface for some user
     * 
     * @param int $userid
     * 
     * @return string
     */
    protected function profileTagsEditForm($userid) {
        $userid = vf($userid, 3);
        $result = '';
        $paramsTmp = array();
        if (!empty($this->allTagtypes)) {
            foreach ($this->allTagtypes as $io => $each) {
                $paramsTmp[$each['id']] = $each['tagname'];
            }
        }

        $inputs = wf_Selector('newtagtypeid', $paramsTmp, __('Add tag'), '', false) . ' ';
        $inputs .= wf_HiddenInput('newtaguserid', $userid);
        $inputs .= wf_Submit(__('Save'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        $result .= wf_CleanDiv();
        $result .= wf_delimiter();

        $paramsDelTmp = array();
        if (!empty($this->allUserTags)) {
            foreach ($this->allUserTags as $io => $eachtag) {
                if ($eachtag['userid'] == $userid) {
                    if (isset($this->allTagtypes[$eachtag['tagtypeid']])) {
                        $paramsDelTmp[$eachtag['id']] = $this->allTagtypes[$eachtag['tagtypeid']]['tagname'];
                    } else {
                        $paramsDelTmp[$eachtag['id']] = __('Deleted') . ': ' . $eachtag['tagtypeid'];
                    }
                }
            }
        }

        if (!empty($paramsDelTmp)) {
            $inputs = wf_Selector('deltagid', $paramsDelTmp, __('Delete tag'), '', false);
            $inputs .= wf_HiddenInput('deltaguserid', $userid);
            $inputs .= wf_Submit(__('Delete'));
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
            $result .= wf_CleanDiv();
        }



        return ($result);
    }

    /**
     * Catches and performs if required tagg adding/deletion for some user
     * 
     * @return void
     */
    protected function catchTagChangeRequest() {
        if (wf_CheckPost(array('newtagtypeid', 'newtaguserid'))) {
            $tagTypeId = vf($_POST['newtagtypeid'], 3);
            $userId = vf($_POST['newtaguserid'], 3);
            $query = "INSERT INTO `ukv_tags` (`id`,`tagtypeid`,`userid`) VALUES ";
            $query .= "(NULL,'" . $tagTypeId . "','" . $userId . "');";
            nr_query($query);
            log_register('UKV TAG ADD ((' . $userId . ')) TYPE [' . $tagTypeId . ']');
            rcms_redirect(self::URL_USERS_PROFILE . $userId);
        }

        if (wf_CheckPost(array('deltagid', 'deltaguserid'))) {
            $delTagId = vf($_POST['deltagid'], 3);
            $userId = vf($_POST['deltaguserid'], 3);
            $query = "DELETE from `ukv_tags` WHERE `id`='" . $delTagId . "';";
            nr_query($query);
            log_register('UKV TAG DEL ((' . $userId . ')) TAGID [' . $delTagId . ']');
            rcms_redirect(self::URL_USERS_PROFILE . $userId);
        }
    }

    /**
     * Returns tagtype data
     * 
     * @param int $tagtypeid
     * 
     * @return array
     */
    protected function getTagParams($tagtypeid) {
        $tagtypeid = vf($tagtypeid, 3);
        $result = array();
        if (isset($this->allTagtypes[$tagtypeid])) {
            $result = $this->allTagtypes[$tagtypeid];
        }
        return ($result);
    }

    /**
     * Returns array of available UKV tariffs
     * 
     * @return array
     */
    public function getTariffs() {
        $result = $this->tariffs;
        return ($result);
    }

    /**
     * Returns array of available UKV users
     * 
     * @return array
     */
    public function getUsers() {
        $result = $this->users;
        return ($result);
    }

    /**
     * Returns array with all user data for a certain UKV userID
     *
     * @return array
     */
    public function getUserData($userid) {
        $result = (isset($this->users[$userid])) ? $this->users[$userid] : array();

        return ($result);
    }

    /**
     * Returns tag html preprocessed body
     * 
     * @param int $id
     * @param bool $power
     * 
     * @return string
     */
    protected function getTagBody($id, $power = false) {
        $powerTmp = array();
        $result = '';
        if ($power) {
            foreach ($this->allUserTags as $io => $each) {
                if (isset($powerTmp[$each['tagtypeid']])) {
                    $powerTmp[$each['tagtypeid']]++;
                } else {
                    $powerTmp[$each['tagtypeid']] = 1;
                }
            }
            $tagPower = (isset($powerTmp[$id])) ? $powerTmp[$id] : 0;
            $powerSup = wf_tag('sup', false) . $tagPower . wf_tag('sup', true);
        } else {
            $powerSup = '';
        }
        $tagbody = $this->getTagParams($id);
        if (!empty($tagbody)) {
            $renderPower = ($power) ? $tagPower : $tagbody['tagsize'];
            $result = wf_tag('font', false, '', 'color="' . $tagbody['tagcolor'] . '" size="' . $renderPower . '"');
            $result .= wf_tag('a', false, '', 'href="' . self::URL_REPORTS_MGMT . 'reportTagcloud&tagid=' . $id . '" style="color: ' . $tagbody['tagcolor'] . ';"') . $tagbody['tagname'] . $powerSup . wf_tag('a', true);
            $result .= wf_tag('font', true);
            $result .= '&nbsp;';
        } else {
            $result .= __('Deleted') . ': ' . $id . '&nbsp;';
        }
        return ($result);
    }

    /**
     * Returns user applied tags as browsable html
     * 
     * @param int $userid
     * @return string
     */
    protected function renderUserTags($userid) {
        $result = '';
        if (!empty($this->allUserTags)) {
            foreach ($this->allUserTags as $io => $eachtag) {
                if ($eachtag['userid'] == $userid) {
                    $result .= $this->getTagBody($eachtag['tagtypeid']);
                }
            }
        }
        return ($result);
    }

    /**
     * Renders profile plugin container
     *
     * @param string $right
     * @param string $data
     * 
     * @return string
     */
    protected function renderPluginContainer($right, $data) {
        $result = '';
        if (cfr($right)) {
            $result .= wf_tag('div', false, 'dashtask', 'style="height:75px; width:75px;"');
            $result .= $data;
            $result .= wf_tag('div', true);
        }
        return ($result);
    }


    /**
     * returns some existing user profile
     * 
     * @param int $userid existing user`s ID
     * 
     * @return string
     */
    public function userProfile($userid) {
        global $ubillingConfig;

        $userid = vf($userid, 3);
        if (isset($this->users[$userid])) {
            $userData = $this->users[$userid];
            $rows = '';

            //zero apt numbers as private builds
            if ($this->altCfg['ZERO_TOLERANCE']) {
                $apt = ($userData['apt'] == '0') ? '' : '/' . $userData['apt'];
            } else {
                $apt = '/' . $userData['apt'];
            }

            //photostorage integration
            if ($this->altCfg['PHOTOSTORAGE_ENABLED']) {
                $photoControl = wf_Link(self::URL_PHOTOSTORAGE . $userid, wf_img_sized('skins/photostorage.png', __('Upload images'), '10'), false);
            } else {
                $photoControl = '';
            }

            //additional user comments
            if ($this->altCfg['ADCOMMENTS_ENABLED']) {
                $adcomments = new ADcomments('UKVUSERPROFILE');
            }

            //task creation control
            if ($this->altCfg['CREATETASK_IN_PROFILE']) {
                $customData = '';
                if ($this->altCfg['CONDET_ENABLED']) {
                    if (!empty($userData['cableseal'])) {
                        $userCableSeal = __('Cable seal') . ': ' . $userData['cableseal'] . '\r\n';
                        $customData = wf_HiddenInput('unifiedformtelegramappend', $userCableSeal);
                    }
                }
                $shortAddress = $userData['street'] . ' ' . $userData['build'] . $apt;
                $taskForm = ts_TaskCreateFormUnified($shortAddress, $userData['mobile'], $userData['phone'], '', $customData);
                $taskControl = wf_modal(wf_img('skins/createtask.gif', __('Create task')), __('Create task'), $taskForm, '', '420', '500');
            } else {
                $taskControl = '';
            }

            $cells = wf_TableCell(__('Full address') . ' ' . $taskControl, '20%', 'row2');
            $cells .= wf_TableCell($userData['city'] . ' ' . $userData['street'] . ' ' . $userData['build'] . $apt);
            $rows .= wf_TableRow($cells, 'row3');


            $cells = wf_TableCell(__('Real Name') . ' ' . $photoControl, '20%', 'row2');
            $cells .= wf_TableCell($userData['realname']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Phone'), '30%', 'row2');
            $cells .= wf_TableCell($userData['phone']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Mobile'), '30%', 'row2');
            $cells .= wf_TableCell($userData['mobile']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(wf_tag('b') . __('Contract') . wf_tag('b', true), '20%', 'row2');
            $cells .= wf_TableCell(wf_tag('b') . $userData['contract'] . wf_tag('b', true));
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Tariff'), '30%', 'row2');
            $cells .= wf_TableCell(@$this->tariffs[$userData['tariffid']]['tariffname']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Planned tariff change'), '30%', 'row2');
            $cells .= wf_TableCell(@$this->tariffs[$userData['tariffnmid']]['tariffname']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Move tariff after'), '30%', 'row2');
            $cells .= wf_TableCell($userData['tariffnmdate']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(wf_tag('b') . __('Cash') . wf_tag('b', true), '30%', 'row2');
            $cells .= wf_TableCell(wf_tag('b') . $userData['cash'] . wf_tag('b', true));
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Connected'), '30%', 'row2');
            $cells .= wf_TableCell(web_bool_led($userData['active']));
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('User contract date'), '30%', 'row2');
            $cells .= wf_TableCell($userData['regdate']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Internet account'), '30%', 'row2');
            $inetLink = (!empty($userData['inetlogin'])) ? wf_Link(self::URL_INET_USER_PROFILE . $userData['inetlogin'], web_profile_icon() . ' ' . $userData['inetlogin'], false, '') : '';
            $cells .= wf_TableCell($inetLink);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Cable seal'), '30%', 'row2');
            $cells .= wf_TableCell($userData['cableseal']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Notes'), '30%', 'row2');
            $cells .= wf_TableCell($userData['notes']);
            $rows .= wf_TableRow($cells, 'row3');

            $profileData = wf_TableBody($rows, '100%', 0, '');

            //tags area
            if (!empty($this->allTagtypes)) {
                $this->catchTagChangeRequest();
                $tagsArea = wf_modalAuto(web_add_icon(__('Add tag')), __('Add tag'), $this->profileTagsEditForm($userid));
                $tagsArea .= $this->renderUserTags($userid);
            } else {
                $tagsArea = '';
            }
            $lifeStoryUrl = self::URL_USERS_LIFESTORY . $userid;

            $profilePlugins = '';
            $profilePlugins .= $this->renderPluginContainer('UKV', wf_Link($lifeStoryUrl, wf_img('skins/icon_orb_big.gif', __('User lifestory'))) . __('Details'));
            $profilePlugins .= $this->renderPluginContainer('UKVCASH', wf_modalAuto(wf_img('skins/ukv/money.png', __('Cash')), __('Finance operations'), $this->userManualPaymentsForm($userid), '', '600', '250') . __('Cash'));
            $profilePlugins .= $this->renderPluginContainer('UKVUED', wf_modalAuto(wf_img('skins/ukv/useredit.png', __('Edit user')), __('Edit user'), $this->userEditForm($userid), '') . __('Edit'));
            $profilePlugins .= $this->renderPluginContainer('UKVSEAL', wf_modalAuto(wf_img('skins/ukv/cableseal.png', __('Cable seal')), __('Cable seal'), $this->userCableSealForm($userid), '') . __('Cable seal'));
            $profilePlugins .= $this->renderPluginContainer('EMPLOYEE', wf_Link('?module=prevtasks&address=' . $shortAddress . '&ukvuserid=' . $userid, wf_img('skins/worker.png', __('Jobs'))) . __('Jobs'));
            $profilePlugins .= $this->renderPluginContainer('UKVDEL', wf_modal(wf_img('skins/annihilation.gif', __('Deleting user')), __('Deleting user'), $this->userDeletionForm($userid), '', '800', '300') . __('Delete'));

            if ($ubillingConfig->getAlterParam('PRINT_RECEIPTS_ENABLED') and $ubillingConfig->getAlterParam('PRINT_RECEIPTS_IN_PROFILE') and cfr('PRINTRECEIPTS')) {
                $receiptsPrinter = new PrintReceipt();

                $profilePlugins .= wf_tag('div', false, 'dashtask', 'style="height:75px; width:75px;"');
                $profilePlugins .= $receiptsPrinter->renderWebFormForProfile($userid, 'ctvsrv', __('Cable television'), $userData['cash'], $userData['street'], $userData['build']);
                $profilePlugins .= wf_tag('br');
                $profilePlugins .= __('Print receipt');
                $profilePlugins .= wf_tag('div', true);
            }

            //main view construction
            $profilecells = wf_tag('td', false, '', 'valign="top"') . $profileData . wf_tag('td', true);

            $profilerows = wf_TableRow($profilecells);

            $profilecells = wf_tag('td', false, '', 'valign="top"') . $tagsArea . wf_tag('td', true);

            $profilerows .= wf_TableRow($profilecells);

            $profilecells = wf_tag('td', false, '', ' valign="top"') . $profilePlugins . wf_tag('td', true);
            $profilerows .= wf_TableRow($profilecells);

            $result = wf_TableBody($profilerows, '100%', '0');
            $result .= $this->userPaymentsRender($userid);


            //additional user comments
            if ($this->altCfg['ADCOMMENTS_ENABLED']) {
                $result .= wf_tag('h3') . __('Additional comments') . wf_tag('h3', true);
                $result .= $adcomments->renderComments($userid);
            }

            return ($result);
        } else {
            throw new Exception(self::EX_USER_NOT_EXISTS);
        }
    }

    /**
     * Filter for quotes etc
     * 
     * @param string $data
     * @return string
     */
    protected function filterStringData($data) {
        $result = str_replace('"', '`', $data);
        $result = str_replace("'", '`', $result);
        return ($result);
    }

    /**
     * renders full user list with some ajax data
     * 
     * @return string
     */
    public function renderUsers() {
        global $ubillingConfig;
        $altcfg = $ubillingConfig->getAlter();
        $columns = array('Full address', 'Real Name', 'Contract', 'Tariff', 'Connected');
        if ($altcfg['UKV_SHOW_REG_DATA']) {
            $columns[] = 'User contract date';
        }
        $columns[] = 'Cash';
        $result = wf_JqDtLoader($columns, self::URL_USERS_AJAX_SOURCE, false, 'users', 50);
        return ($result);
    }

    /**
     * Extracts ajax data for JQuery data tables
     * 
     * @return void
     */
    public function ajaxUsers() {
        global $ubillingConfig;
        $altcfg = $ubillingConfig->getAlter();
        $json = new wf_JqDtHelper();

        if (!empty($this->users)) {
            foreach ($this->users as $io => $each) {

                //zero apt numbers as private builds
                if ($altcfg['ZERO_TOLERANCE']) {
                    $apt = ($each['apt'] == '0') ? '' : '/' . $each['apt'];
                } else {
                    $apt = '/' . $each['apt'];
                }
                //city display
                if ($altcfg['CITY_DISPLAY']) {
                    $city = $each['city'] . ' ';
                } else {
                    $city = '';
                }
                //activity flag
                $activity = ($each['active']) ? web_bool_led($each['active']) . ' ' . __('Yes') : web_bool_led($each['active']) . ' ' . __('No');
                $activity = str_replace('"', '', $activity);
                //profile link
                $profileLink = wf_Link(self::URL_USERS_PROFILE . $each['id'], web_profile_icon(), false) . ' ';
                //building data array
                $data[] = $profileLink . $city . $each['street'] . ' ' . $each['build'] . $apt;
                $data[] = $each['realname'];
                $data[] = $each['contract'];
                $data[] = @$this->tariffs[$each['tariffid']]['tariffname'];
                $data[] = $activity;
                if ($altcfg['UKV_SHOW_REG_DATA']) {
                    $data[] = $each['regdate'];
                }
                $data[] = $each['cash'];

                $json->addRow($data);
                unset($data);
            }
        }

        $json->getJson();
    }

    /**
     * translates payment note for catv users
     * 
     * @param string $paynote some payment note to translate
     * 
     * @return string 
     */
    protected function translatePaymentNote($paynote) {
        if ($paynote == '') {
            $paynote = __('CaTV');
        }

        if (ispos($paynote, 'BANKSTA:')) {
            $paynote = str_replace('BANKSTA:', __('Bank statement') . ' ', $paynote);
        }

        if (ispos($paynote, 'ASCONTRACT')) {
            $paynote = str_replace('ASCONTRACT', __('by users contract') . ' ', $paynote);
        }

        if (ispos($paynote, 'MOCK:')) {
            $paynote = str_replace('MOCK:', __('Mock payment') . ' ', $paynote);
        }

        if (ispos($paynote, 'UKVFEE:')) {
            $paynote = str_replace('UKVFEE:', __('Fee') . '. ', $paynote);
        }

        if (ispos($paynote, self::EX_USER_NO_TARIFF_SET)) {
            $paynote = str_replace(self::EX_USER_NO_TARIFF_SET, __('Any tariff not set. Fee charge skipped.') . ' ', $paynote);
        }

        if (ispos($paynote, self::EX_USER_NOT_ACTIVE)) {
            $paynote = str_replace(self::EX_USER_NOT_ACTIVE, __('User not connected. Fee charge skipped.'), $paynote);
        }

        return ($paynote);
    }

    /**
     * renders all of user payments from database
     * 
     * @param string $userid existing user ID
     *
     * @return string
     */
    public function userPaymentsRender($userid) {
        global $ubillingConfig;
        $altcfg = $ubillingConfig->getAlter();
        $userid = vf($userid, 3);
        $curdate = curdate();

        $currentAdminLogin = whoami();
        //extract delete admin logins
        if (!empty($this->altCfg['CAN_DELETE_PAYMENTS'])) {
            $deletingAdmins = explode(',', $this->altCfg['CAN_DELETE_PAYMENTS']);
            $deletingAdmins = array_flip($deletingAdmins);
        }
        $iCanDeletePayments = (isset($deletingAdmins[$currentAdminLogin])) ? true : false;

        if (isset($this->users[$userid])) {
            if (empty($this->cashtypes)) {
                $this->loadCashtypes();
            }
            $query = "SELECT * from `ukv_payments` WHERE `userid`='" . $userid . "' ORDER BY `id` DESC;";
            $all = simple_queryall($query);

            $cells = wf_TableCell(__('ID'));
            $cells .= wf_TableCell(__('Date'));
            $cells .= wf_TableCell(__('Cash'));
            $cells .= wf_TableCell(__('From'));
            $cells .= wf_TableCell(__('To'));
            $cells .= wf_TableCell(__('Operation'));
            $cells .= wf_TableCell(__('Cash type'));
            $cells .= wf_TableCell(__('Notes'));
            $cells .= wf_TableCell(__('Admin'));
            if ($iCanDeletePayments) {
                $cells .= wf_TableCell(__('Actions'));
            }

            $rows = wf_TableRow($cells, 'row1');

            if (!empty($all)) {
                foreach ($all as $io => $eachpayment) {
                    $normalPayment = true;
                    $paymentCashtype = @$this->cashtypes[$eachpayment['cashtypeid']];

                    if ($eachpayment['visible']) {
                        if (!ispos($eachpayment['note'], 'MOCK:')) {
                            $operation = __('Payment');
                            $rowColor = '#' . self::COLOR_PAYMENT;
                        } else {
                            $operation = __('Mock payment');
                            $rowColor = '#' . self::COLOR_MOCK;
                            $normalPayment = false;
                        }
                    } else {

                        if (!ispos($eachpayment['note'], 'UKVFEE:')) {
                            $operation = __('Correcting');
                            $rowColor = '#' . self::COLOR_CORRECTING;
                        } else {
                            $operation = __('Fee');
                            $rowColor = '#' . self::COLOR_FEE;
                            $paymentCashtype = __('Fee');
                        }
                    }

                    $colorStart = wf_tag('font', false, '', 'color="' . $rowColor . '"');
                    $colorEnd = wf_tag('font', true);

                    if ($normalPayment) {
                        $newBalance = $eachpayment['balance'] + $eachpayment['summ'];
                    } else {
                        $newBalance = $eachpayment['balance'];
                    }

                    //payment notes translation
                    if ($altcfg['TRANSLATE_PAYMENTS_NOTES']) {
                        $notes = $this->translatePaymentNote($eachpayment['note']);
                    } else {
                        $notes = $eachpayment['note'];
                    }

                    //today payments highlight
                    $rowClass = 'row3';
                    if (ispos($eachpayment['date'], $curdate)) {
                        $rowClass = 'paytoday';
                    }
                    $cells = wf_TableCell($eachpayment['id']);
                    $cells .= wf_TableCell($eachpayment['date']);
                    $cells .= wf_TableCell($eachpayment['summ']);
                    $cells .= wf_TableCell($eachpayment['balance']);
                    $cells .= wf_TableCell($newBalance);
                    $cells .= wf_TableCell($colorStart . $operation . $colorEnd);
                    $cells .= wf_TableCell($paymentCashtype);
                    $cells .= wf_TableCell($notes);
                    $cells .= wf_TableCell($eachpayment['admin']);
                    if ($iCanDeletePayments) {
                        $deletionRoute = self::URL_USERS_PROFILE . $userid . '&deletepaymentid=' . $eachpayment['id'];
                        $deleteControls = wf_JSAlert($deletionRoute, wf_img('skins/delete_small.png', __('Delete')), $this->messages->getDeleteAlert());
                        $cells .= wf_TableCell($deleteControls);
                    }
                    $rows .= wf_TableRow($cells, $rowClass);
                }
            }

            $result = wf_TableBody($rows, '100%', '0', 'sortable');
            return ($result);
        } else {
            throw new Exception(self::EX_USER_NOT_EXISTS);
        }
    }

    /**
     * Deletes some existing payment from database
     * 
     * @param int $paymentId
     * @param int $userId
     * 
     * @return void
     */
    public function paymentDelete($paymentId, $userId) {
        $paymentId = vf($paymentId, 3);
        $userId = vf($userId, 3);
        if (!empty($paymentId) and !empty($userId)) {
            $currentAdminLogin = whoami();
            //extract delete admin logins
            if (!empty($this->altCfg['CAN_DELETE_PAYMENTS'])) {
                $deletingAdmins = explode(',', $this->altCfg['CAN_DELETE_PAYMENTS']);
                $deletingAdmins = array_flip($deletingAdmins);
            }
            $iCanDeletePayments = (isset($deletingAdmins[$currentAdminLogin])) ? true : false;

            if ($iCanDeletePayments) {
                $payments = new NyanORM('ukv_payments');
                $payments->where('id', '=', $paymentId);
                $payments->where('userid', '=', $userId);
                $payments->delete();
                log_register("UKV PAYMENT DELETE [" . $paymentId . "] ((" . $userId . "))");
            } else {
                log_register("UKV PAYMENT UNAUTH DELETION ATTEMPT [" . $paymentId . "] ((" . $userId . "))");
            }
        }
    }

    /*
     * Bank statements processing
     */

    /**
     * returns bank statement upload form
     * 
     * @return string
     */
    public function bankstaLoadForm() {
        $uploadinputs = wf_HiddenInput('uploadukvbanksta', 'true');
        $uploadinputs .= __('Bank statement') . wf_tag('br');
        $uploadinputs .= wf_tag('input', false, '', 'id="fileselector" type="file" name="ukvbanksta"') . wf_tag('br');
        $uploadinputs .= __('Bankstatement type');
        $uploadinputs .= wf_RadioInput('ukvbankstatype', __('Oschadbank'), 'oschad', false, true);
        $uploadinputs .= wf_RadioInput('ukvbankstatype', __('Oschadbank terminal'), 'oschadterm', false, false);
        $uploadinputs .= wf_RadioInput('ukvbankstatype', __('PrivatBank'), 'privatbankdbf', true, false);


        $uploadinputs .= wf_Submit('Upload');
        $uploadform = bs_UploadFormBody('', 'POST', $uploadinputs, 'glamour');
        return ($uploadform);
    }

    /**
     * checks is banksta hash unique?
     * 
     * @param string $hash  bank statement raw content hash
     * 
     * @return bool
     */
    protected function bankstaCheckHash($hash) {
        $query = "SELECT `id` from `ukv_banksta` WHERE `hash`='" . $hash . "';";
        $data = simple_query($query);
        if (empty($data)) {
            return (true);
        } else {
            return (false);
        }
    }

    /**
     * process of uploading of bank statement
     * 
     * @return array
     */
    public function bankstaDoUpload() {
        $uploaddir = self::BANKSTA_PATH;
        $allowedExtensions = array("dbf");
        $result = array();
        $extCheck = true;

        //check file type
        foreach ($_FILES as $file) {
            if ($file['tmp_name'] > '') {
                if (@!in_array(end(explode(".", strtolower($file['name']))), $allowedExtensions)) {
                    $extCheck = false;
                }
            }
        }

        if ($extCheck) {
            $filename = zb_rand_string(8) . '.dbf';
            $uploadfile = $uploaddir . $filename;

            if (move_uploaded_file($_FILES['ukvbanksta']['tmp_name'], $uploadfile)) {
                $fileContent = file_get_contents(self::BANKSTA_PATH . $filename);
                $fileHash = md5($fileContent);
                $fileContent = ''; //free some memory
                if ($this->bankstaCheckHash($fileHash)) {
                    $result = array(
                        'filename' => $_FILES['ukvbanksta']['name'],
                        'savedname' => $filename,
                        'hash' => $fileHash
                    );
                } else {
                    log_register('UKV BANKSTA DUPLICATE TRY ' . $fileHash);
                    show_error(__('Same bank statement already exists'));
                }
            } else {
                show_error(__('Cant upload file to') . ' ' . self::BANKSTA_PATH);
            }
        } else {
            show_error(__('Wrong file type'));
            log_register('UKV BANKSTA WRONG FILETYPE');
        }
        return ($result);
    }

    /**
     * Creates new banksta row in Database
     * 
     * @param string $newDate
     * @param string $newHash
     * @param string $newFilename
     * @param string $newAdmin
     * @param string $newContract
     * @param string $newSumm
     * @param string $newAddress
     * @param string $newRealname
     * @param string $newNotes
     * @param string $newPate
     * @param string $newPtime
     * @param int $payId
     * 
     * @return void
     */
    protected function bankstaCreateRow($newDate, $newHash, $newFilename, $newAdmin, $newContract, $newSumm, $newAddress, $newRealname, $newNotes, $newPdate, $newPtime, $payId) {
        $query = "INSERT INTO `ukv_banksta` (`id`, `date`, `hash`, `filename`, `admin`, `contract`, `summ`, `address`, `realname`, `notes`, `pdate`, `ptime`, `processed`, `payid`)
                                VALUES (
                                NULL ,
                                '" . $newDate . "',
                                '" . $newHash . "',
                                '" . $newFilename . "',
                                '" . $newAdmin . "',
                                '" . $newContract . "',
                                '" . $newSumm . "',
                                '" . $newAddress . "',
                                '" . $newRealname . "',
                                '" . $newNotes . "',
                                '" . $newPdate . "',
                                '" . $newPtime . "',
                                '0',
                                '" . $payId . "'
                                );
                            ";
        nr_query($query);
    }

    /**
     * new banksta store in database bankstaDoUpload() method and returns preprocessed
     * bank statement hash for further usage
     * 
     * @param $bankstadata   array returned from 
     * 
     * @return string
     */
    public function bankstaPreprocessing($bankstadata) {
        $result = '';
        if (!empty($bankstadata)) {
            if (file_exists(self::BANKSTA_PATH . $bankstadata['savedname'])) {
                //processing raw data
                $newHash = $bankstadata['hash'];
                $result = $newHash;
                $newFilename = $bankstadata['filename'];
                $newAdmin = whoami();
                $payId = vf($this->altCfg['UKV_BS_PAYID'], 3);

                $dbf = new dbf_class(self::BANKSTA_PATH . $bankstadata['savedname']);
                $num_rec = $dbf->dbf_num_rec;
                $importCounter = 0;
                for ($i = 0; $i <= $num_rec; $i++) {
                    $eachRow = $dbf->getRowAssoc($i);
                    if (!empty($eachRow)) {
                        if (@$eachRow[self::BANKSTA_CONTRACT] != '') {
                            $newDate = date("Y-m-d H:i:s");
                            $newContract = trim($eachRow[self::BANKSTA_CONTRACT]);
                            $newContract = mysql_real_escape_string($newContract);
                            $newSumm = trim($eachRow[self::BANKSTA_SUMM]);
                            $newSumm = mysql_real_escape_string($newSumm);
                            $newAddress = iconv(self::BANKSTA_IN_CHARSET, self::BANKSTA_OUT_CHARSET, $eachRow[self::BANKSTA_ADDRESS]);
                            $newAddress = mysql_real_escape_string($newAddress);
                            $newRealname = iconv(self::BANKSTA_IN_CHARSET, self::BANKSTA_OUT_CHARSET, $eachRow[self::BANKSTA_REALNAME]);
                            $newRealname = mysql_real_escape_string($newRealname);
                            $newNotes = iconv(self::BANKSTA_IN_CHARSET, self::BANKSTA_OUT_CHARSET, $eachRow[self::BANKSTA_NOTES]);
                            $newNotes = mysql_real_escape_string($newNotes);
                            $newPdate = iconv(self::BANKSTA_IN_CHARSET, self::BANKSTA_OUT_CHARSET, $eachRow[self::BANKSTA_DATE]);
                            $newPdate = mysql_real_escape_string($newPdate);
                            $newPtime = iconv(self::BANKSTA_IN_CHARSET, self::BANKSTA_OUT_CHARSET, $eachRow[self::BANKSTA_TIME]);
                            $newPtime = mysql_real_escape_string($newPtime);

                            $this->bankstaCreateRow($newDate, $newHash, $newFilename, $newAdmin, $newContract, $newSumm, $newAddress, $newRealname, $newNotes, $newPdate, $newPtime, $payId);

                            $importCounter++;
                        }
                    }
                }

                log_register('UKV BANKSTA IMPORTED ' . $importCounter . ' ROWS');
            } else {
                show_error(__('Strange exeption'));
            }
        } else {
            throw new Exception(self::EX_BANKSTA_PREPROCESS_EMPTY);
        }
        return ($result);
    }

    /**
     * new banksta store in database bankstaDoUpload() method and returns preprocessed
     * bank statement hash for further usage
     * 
     * @param string $bankstadata   array returned from 
     * 
     * @return string
     */
    public function bankstaPreprocessingTerminal($bankstadata) {
        $result = '';
        if (!empty($bankstadata)) {
            if (file_exists(self::BANKSTA_PATH . $bankstadata['savedname'])) {
                //processing raw data
                $newHash = $bankstadata['hash'];
                $result = $newHash;
                $newFilename = $bankstadata['filename'];
                $newAdmin = whoami();
                $payId = vf($this->altCfg['UKV_BS_PAYID'], 3);

                $dbf = new dbf_class(self::BANKSTA_PATH . $bankstadata['savedname']);
                $num_rec = $dbf->dbf_num_rec;
                $importCounter = 0;
                for ($i = 0; $i <= $num_rec; $i++) {
                    $eachRow = $dbf->getRowAssoc($i);

                    if (!empty($eachRow)) {
                        if (!empty($eachRow[self::OT_BANKSTA_CONTRACT])) {
                            $newDate = date("Y-m-d H:i:s");
                            $newContract = trim($eachRow[self::OT_BANKSTA_CONTRACT]);
                            $newContract = mysql_real_escape_string($newContract);
                            $newSumm = trim($eachRow[self::OT_BANKSTA_SUMM]);
                            $newSumm = mysql_real_escape_string($newSumm);
                            $newAddress = iconv(self::BANKSTA_IN_CHARSET, self::BANKSTA_OUT_CHARSET, $eachRow[self::OT_BANKSTA_ADDRESS]);
                            $newAddress = mysql_real_escape_string($newAddress);
                            $newRealname = iconv(self::BANKSTA_IN_CHARSET, self::BANKSTA_OUT_CHARSET, $eachRow[self::OT_BANKSTA_REALNAME]);
                            $newRealname = mysql_real_escape_string($newRealname);
                            $newNotes = iconv(self::BANKSTA_IN_CHARSET, self::BANKSTA_OUT_CHARSET, $eachRow[self::OT_BANKSTA_NOTES]);
                            $newNotes = mysql_real_escape_string($newNotes);
                            $newPdate = iconv(self::BANKSTA_IN_CHARSET, self::BANKSTA_OUT_CHARSET, $eachRow[self::OT_BANKSTA_DATE]);
                            $newPdate = mysql_real_escape_string($newPdate);
                            $newPtime = iconv(self::BANKSTA_IN_CHARSET, self::BANKSTA_OUT_CHARSET, $eachRow[self::OT_BANKSTA_TIME]);
                            $newPtime = mysql_real_escape_string($newPtime);

                            $this->bankstaCreateRow($newDate, $newHash, $newFilename, $newAdmin, $newContract, $newSumm, $newAddress, $newRealname, $newNotes, $newPdate, $newPtime, $payId);

                            $importCounter++;
                        }
                    }
                }

                log_register('UKV BANKSTA IMPORTED ' . $importCounter . ' ROWS');
            } else {
                show_error(__('Strange exeption'));
            }
        } else {
            throw new Exception(self::EX_BANKSTA_PREPROCESS_EMPTY);
        }
        return ($result);
    }

    /**
     * new banksta store in database bankstaDoUpload() method and returns preprocessed
     * bank statement hash for further usage
     * 
     * @param string $bankstadata   array returned from 
     * 
     * @return string
     */
    public function bankstaPreprocessingPrivatDbf($bankstadata) {
        $result = '';
        if (!empty($bankstadata)) {
            if (file_exists(self::BANKSTA_PATH . $bankstadata['savedname'])) {
                //processing raw data
                $newHash = $bankstadata['hash'];
                $result = $newHash;
                $newFilename = $bankstadata['filename'];
                $newAdmin = whoami();
                $payId = vf($this->altCfg['UKV_BSPB_PAYID'], 3);

                $dbf = new dbf_class(self::BANKSTA_PATH . $bankstadata['savedname']);
                $num_rec = $dbf->dbf_num_rec;
                $importCounter = 0;
                for ($i = 0; $i <= $num_rec; $i++) {
                    $eachRow = $dbf->getRowAssoc($i);

                    if (!empty($eachRow)) {
                        if (@$eachRow[self::PB_BANKSTA_CONTRACT] != '') {
                            $newDate = date("Y-m-d H:i:s");
                            $newContract = trim($eachRow[self::PB_BANKSTA_CONTRACT]);
                            $newContract = mysql_real_escape_string($newContract);
                            $newSumm = trim($eachRow[self::PB_BANKSTA_SUMM]);
                            $newSumm = mysql_real_escape_string($newSumm);
                            $newAddress = iconv(self::BANKSTA_IN_CHARSET, self::BANKSTA_OUT_CHARSET, $eachRow[self::PB_BANKSTA_ADDRESS]);
                            $newAddress = mysql_real_escape_string($newAddress);
                            $newRealname = iconv(self::BANKSTA_IN_CHARSET, self::BANKSTA_OUT_CHARSET, $eachRow[self::PB_BANKSTA_REALNAME]);
                            $newRealname = mysql_real_escape_string($newRealname);
                            $newNotes = iconv(self::BANKSTA_IN_CHARSET, self::BANKSTA_OUT_CHARSET, $eachRow[self::PB_BANKSTA_NOTES]);
                            $newNotes = mysql_real_escape_string($newNotes);
                            $pbDate = $eachRow[self::PB_BANKSTA_DATE];
                            $pbDate = strtotime($pbDate);
                            $pbDate = date("Y-m-d", $pbDate);
                            $newPdate = iconv(self::BANKSTA_IN_CHARSET, self::BANKSTA_OUT_CHARSET, $pbDate);
                            $newPdate = mysql_real_escape_string($newPdate);
                            $newPtime = iconv(self::BANKSTA_IN_CHARSET, self::BANKSTA_OUT_CHARSET, curtime());
                            $newPtime = mysql_real_escape_string($newPtime);

                            $this->bankstaCreateRow($newDate, $newHash, $newFilename, $newAdmin, $newContract, $newSumm, $newAddress, $newRealname, $newNotes, $newPdate, $newPtime, $payId);

                            $importCounter++;
                        }
                    }
                }

                log_register('UKV BANKSTA IMPORTED ' . $importCounter . ' ROWS');
            } else {
                show_error(__('Strange exeption'));
            }
        } else {
            throw new Exception(self::EX_BANKSTA_PREPROCESS_EMPTY);
        }
        return ($result);
    }

    /**
     * returns banksta processing form for some hash
     * 
     * @param string $hash  existing preprocessing bank statement hash
     * 
     * @return string
     */
    public function bankstaProcessingForm($hash) {
        $hash = mysql_real_escape_string($hash);
        $query = "SELECT * from `ukv_banksta` WHERE `hash`='" . $hash . "' ORDER BY `id` ASC;";
        $all = simple_queryall($query);
        $cashPairs = array();
        $totalSumm = 0;
        $rowsCount = 0;

        $cells = wf_TableCell(__('ID'));
        $cells .= wf_TableCell(__('Address'));
        $cells .= wf_TableCell(__('Real Name'));
        $cells .= wf_TableCell(__('Contract'));
        $cells .= wf_TableCell(__('Cash'));
        $cells .= wf_TableCell(__('Processed'));
        $cells .= wf_TableCell(__('Contract'));
        $cells .= wf_TableCell(__('Real Name'));
        $cells .= wf_TableCell(__('Address'));
        $cells .= wf_TableCell(__('Tariff'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($all)) {
            foreach ($all as $io => $each) {


                $AddInfoControl = wf_Link(self::URL_BANKSTA_DETAILED . $each['id'], $each['id'], false, '');
                $processed = ($each['processed']) ? true : false;

                $cells = wf_TableCell($AddInfoControl);
                $cells .= wf_TableCell($each['address']);
                $cells .= wf_TableCell($each['realname']);

                if (!$processed) {
                    $editInputs = wf_TextInput('newbankcontr', '', $each['contract'], false, '6');
                    $editInputs .= wf_CheckInput('lockbankstarow', __('Lock'), false, false);
                    $editInputs .= wf_HiddenInput('bankstacontractedit', $each['id']);
                    $editInputs .= wf_Submit(__('Save'));
                    $editForm = wf_Form('', 'POST', $editInputs);
                } else {
                    $editForm = $each['contract'];
                }
                $cells .= wf_TableCell($editForm);
                $cells .= wf_TableCell($each['summ']);
                $cells .= wf_TableCell(web_bool_led($processed));
                //user detection 
                if (isset($this->contracts[$each['contract']])) {
                    $detectedUser = $this->users[$this->contracts[$each['contract']]];
                    $detectedContract = wf_Link(self::URL_USERS_PROFILE . $detectedUser['id'], web_profile_icon() . ' ' . $detectedUser['contract'], false, '');
                    $detectedAddress = $detectedUser['street'] . ' ' . $detectedUser['build'] . '/' . $detectedUser['apt'];
                    $detectedRealName = $detectedUser['realname'];
                    $detectedTariff = $detectedUser['tariffid'];
                    $detectedTariff = $this->tariffs[$detectedTariff]['tariffname'];

                    if (!$processed) {
                        $cashPairs[$each['id']]['bankstaid'] = $each['id'];
                        $cashPairs[$each['id']]['userid'] = $detectedUser['id'];
                        $cashPairs[$each['id']]['usercontract'] = $detectedUser['contract'];
                        $cashPairs[$each['id']]['summ'] = $each['summ'];
                        $cashPairs[$each['id']]['payid'] = $each['payid'];
                    }

                    $rowClass = 'row3';
                    //try to highlight multiple payments
                    if (!isset($this->bankstafoundusers[$each['contract']])) {
                        $this->bankstafoundusers[$each['contract']] = $detectedUser['id'];
                    } else {
                        $rowClass = 'ukvbankstadup';
                    }
                } else {
                    $detectedContract = '';
                    $detectedAddress = '';
                    $detectedRealName = '';
                    $detectedTariff = '';
                    if ($each['processed'] == 1) {
                        $rowClass = 'row2';
                    } else {
                        $rowClass = 'undone';
                    }
                }

                $cells .= wf_TableCell($detectedContract);
                $cells .= wf_TableCell($detectedRealName);
                $cells .= wf_TableCell($detectedAddress);
                $cells .= wf_TableCell($detectedTariff);
                $rows .= wf_TableRow($cells, $rowClass);

                $totalSumm = $totalSumm + $each['summ'];
                $rowsCount++;
            }
        }

        //summary here
        $cells = wf_TableCell('');
        $cells .= wf_TableCell(__('Total'));
        $cells .= wf_TableCell($rowsCount);
        $cells .= wf_TableCell('');
        $cells .= wf_TableCell($totalSumm);
        $cells .= wf_TableCell('');
        $cells .= wf_TableCell('');
        $cells .= wf_TableCell('');
        $cells .= wf_TableCell('');
        $cells .= wf_TableCell('');
        $rows .= wf_TableRow($cells, 'row2');

        $result = wf_TableBody($rows, '100%', '0', '');

        if (!empty($cashPairs)) {
            $cashPairs = serialize($cashPairs);
            $cashPairs = base64_encode($cashPairs);
            $cashInputs = wf_HiddenInput('bankstaneedpaymentspush', $cashPairs);
            $cashInputs .= wf_Submit(__('Bank statement processing'));
            $result .= wf_FormDisabler();
            $result .= wf_Form('', 'POST', $cashInputs, 'glamour');
        }


        return ($result);
    }

    /**
     * returns detailed banksta row info
     * 
     * @param int $id   existing banksta ID
     * 
     * @return string
     */
    public function bankstaGetDetailedRowInfo($id) {
        $id = vf($id, 3);
        $query = "SELECT * from `ukv_banksta` WHERE `id`='" . $id . "'";
        $dataRaw = simple_query($query);
        $result = '';
        $result .= wf_BackLink(self::URL_BANKSTA_PROCESSING . $dataRaw['hash']);
        $result .= wf_delimiter();

        if (!empty($dataRaw)) {
            $result .= wf_tag('pre', false, 'floatpanelswide', '') . print_r($dataRaw, true) . wf_tag('pre', true);
            $result .= wf_CleanDiv();
        }


        return ($result);
    }

    /**
     * loads all of banksta rows to further checks to private prop
     * 
     * @return void
     */
    protected function loadBankstaAll() {
        $query = "SELECT * from `ukv_banksta`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->bankstarecords[$each['id']] = $each;
            }
        }
    }

    /**
     * checks is banksta row ID unprocessed?
     * 
     * @param int $bankstaid   existing banksta row ID
     * 
     * @return bool
     */
    protected function bankstaIsUnprocessed($bankstaid) {
        $result = false;
        if (isset($this->bankstarecords[$bankstaid])) {
            if ($this->bankstarecords[$bankstaid]['processed'] == 0) {
                $result = true;
            } else {
                $result = false;
            }
        }
        return ($result);
    }

    /**
     * sets banksta row as processed
     * 
     * @param int $bankstaid  existing bank statement ID
     * 
     * @return void
     */
    public function bankstaSetProcessed($bankstaid) {
        $bankstaid = vf($bankstaid, 3);
        simple_update_field('ukv_banksta', 'processed', 1, "WHERE `id`='" . $bankstaid . "'");
    }

    /**
     * push payments to some user accounts via bank statements
     * 
     * @return void
     */
    public function bankstaPushPayments() {
        if (wf_CheckPost(array('bankstaneedpaymentspush'))) {
            $rawData = base64_decode($_POST['bankstaneedpaymentspush']);
            $rawData = unserialize($rawData);
            if (!empty($rawData)) {
                if (empty($this->bankstarecords)) {
                    $this->loadBankstaAll();
                }

                foreach ($rawData as $io => $eachstatement) {
                    if ($this->bankstaIsUnprocessed($eachstatement['bankstaid'])) {
                        //all good is with this row
                        // push payment and mark banksta as processed
                        $payid = (!empty($eachstatement['payid'])) ? vf($eachstatement['payid'], 3) : 1; //default cash
                        $this->userAddCash($eachstatement['userid'], $eachstatement['summ'], 1, $payid, 'BANKSTA: [' . $eachstatement['bankstaid'] . '] ASCONTRACT ' . $eachstatement['usercontract']);
                        $this->bankstaSetProcessed($eachstatement['bankstaid']);
                    } else {
                        //duplicate payment try
                        log_register('UKV BANKSTA TRY DUPLICATE [' . $eachstatement['bankstaid'] . '] PAYMENT PUSH');
                    }
                }
            }
        }
    }

    /**
     * Renders bank statements list datatables json datasource
     * 
     * @return void
     */
    public function bankstaRenderAjaxList() {
        $query = "SELECT `filename`,`hash`,`date`,`admin`,`payid`,COUNT(`id`) AS `rowcount` FROM `ukv_banksta` GROUP BY `hash` ORDER BY `date` DESC;";
        $all = simple_queryall($query);
        $this->loadCashtypes();
        $jsonAAData = array();

        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $jsonItem = array();
                $jsonItem[] = $each['date'];
                $jsonItem[] = $each['filename'];
                $jsonItem[] = @$this->cashtypes[$each['payid']];
                $jsonItem[] = $each['rowcount'];
                $jsonItem[] = $each['admin'];
                $actLinks = wf_Link(self::URL_BANKSTA_PROCESSING . $each['hash'], wf_img('skins/icon_search_small.gif', __('Show')), false, '');
                $jsonItem[] = $actLinks;

                $jsonAAData[] = $jsonItem;
            }
        }
        $result = array("aaData" => $jsonAAData);
        die(json_encode($result));
    }

    /**
     * Renders bank statements list container
     * 
     * @return type
     */
    public function bankstaRenderList() {
        $result = '';
        $columns = array(__('Date'), __('Filename'), __('Type'), __('Rows'), __('Admin'), __('Actions'));
        $opts = '"order": [[ 0, "desc" ]]';
        $result .= wf_JqDtLoader($columns, self::URL_BANKSTA_MGMT . '&ajbslist=true', false, __('Bank statement'), 50, $opts);
        return ($result);
    }

    /**
     * cnahges banksta contract number for some existing row
     * 
     * @param int $bankstaid    existing bank statement transaction ID
     * @param string $contract     new contract number for this row
     */
    public function bankstaSetContract($bankstaid, $contract) {
        $bankstaid = vf($bankstaid, 3);
        $contract = mysql_real_escape_string($contract);
        $contract = trim($contract);
        if (empty($this->bankstarecords)) {
            $this->loadBankstaAll();
        }

        if (isset($this->bankstarecords[$bankstaid])) {
            $oldContract = $this->bankstarecords[$bankstaid]['contract'];
            simple_update_field('ukv_banksta', 'contract', $contract, "WHERE `id`='" . $bankstaid . "';");
            log_register('UKV BANKSTA [' . $bankstaid . '] CONTRACT `' . $oldContract . '` CHANGED ON `' . $contract . '`');
        } else {
            log_register('UKV BANKSTA NONEXIST [' . $bankstaid . '] CONTRACT CHANGE TRY');
        }
    }

    /*
     * and there is some reports for UKV subsystem
     */

    /**
     * returns report icon and link
     * 
     * @return string
     */
    protected function buildReportTask($link, $icon, $text) {
        $icon_path = 'skins/ukv/';

        $task_link = $link;
        $task_icon = $icon_path . $icon;
        $task_text = $text;

        if (isset($_COOKIE['tb_iconsize'])) {
            $tbiconsize = vf($_COOKIE['tb_iconsize'], 3);
        } else {
            $tbiconsize = '128';
        }
        $template = wf_tag('div', false, 'dashtask', 'style="height:' . ($tbiconsize + 30) . 'px; width:' . ($tbiconsize + 30) . 'px;"');
        $template .= wf_tag('a', false, '', 'href="' . $task_link . '"');
        $template .= wf_tag('img', false, '', 'src="' . $task_icon . '" border="0" width="' . $tbiconsize . '"  height="' . $tbiconsize . '" alt="' . $task_text . '" title="' . $task_text . '"');
        $template .= wf_tag('a', true);
        $template .= wf_tag('br');
        $template .= wf_tag('br');
        $template .= $task_text;
        $template .= wf_tag('div', true);
        return ($template);
    }

    /**
     * renders report list
     * 
     * @return void
     */
    public function reportList() {
        $reports = '';
        $reports .= $this->buildReportTask(self::URL_REPORTS_MGMT . 'reportDebtors', 'debtors.png', __('Debtors'));
        $reports .= $this->buildReportTask(self::URL_REPORTS_MGMT . 'reportAntiDebtors', 'antidebtors.png', __('AntiDebtors'));
        if (cfr('UKVCASH')) {
            $reports .= $this->buildReportTask(self::URL_REPORTS_MGMT . 'reportTariffs', 'tariffsreport.jpg', __('Tariffs report'));
            $reports .= $this->buildReportTask(self::URL_REPORTS_MGMT . 'reportFinance', 'financereport.jpg', __('Finance report'));
            $reports .= $this->buildReportTask(self::URL_REPORTS_MGMT . 'reportFees', 'feesreport.png', __('Money fees'));
        }
        if (cfr('UKVREG')) {
            $reports .= $this->buildReportTask(self::URL_REPORTS_MGMT . 'reportSignup', 'signupreport.jpg', __('Signup report'));
        }

        $reports .= $this->buildReportTask(self::URL_REPORTS_MGMT . 'reportStreets', 'streetsreport.png', __('Streets report'));
        $reports .= $this->buildReportTask(self::URL_REPORTS_MGMT . 'reportDebtAddr', 'debtaddr.png', __('Current debtors for delivery by address'));
        $reports .= $this->buildReportTask(self::URL_REPORTS_MGMT . 'reportDebtStreets', 'debtstreets.png', __('Current debtors for delivery by streets'));
        $reports .= $this->buildReportTask(self::URL_REPORTS_MGMT . 'reportTagcloud', 'tagcloud.jpg', __('Tag cloud'));
        $reports .= $this->buildReportTask(self::URL_REPORTS_MGMT . 'reportIntegrity', 'integrity.png', __('Integrity control'));
        if ($this->altCfg['COMPLEX_ENABLED']) {
            $reports .= $this->buildReportTask(self::URL_REPORTS_MGMT . 'reportComplexAssign', 'reportcomplexassign.png', __('Users with complex services'));
            $reports .= $this->buildReportTask(self::URL_REPORTS_MGMT . 'reportShouldbeComplex', 'shouldbecomplex.png', __('Users which should be complex in UKV'));
            $reports .= $this->buildReportTask(self::URL_REPORTS_MGMT . 'reportShouldNotbeComplex', 'shouldbecomplex.png', __('Users which should not be complex in UKV'));
        }

        if ($this->altCfg['CONDET_ENABLED']) {
            $reports .= $this->buildReportTask('?module=report_condet&ukv=true', 'report_condet.png', __('Connection details report'));
        }
        $reports .= wf_CleanDiv();
        show_window(__('Reports'), $reports);
    }

    /**
     * shows printable report content
     * 
     * @param $title report title
     * @param $data  report data to printable transform
     * 
     * @return void
     */
    protected function reportPrintable($title, $data) {

        $style = file_get_contents(CONFIG_PATH . "ukvprintable.css");

        $header = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
        <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru" lang="ru">
        <head>                                                        
        <title>' . $title . '</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <style type="text/css">
        ' . $style . '
        </style>
        <script src="modules/jsc/sorttable.js" language="javascript"></script>
        </head>
        <body>
        ';

        $footer = '</body> </html>';

        $title = (!empty($title)) ? wf_tag('h2') . $title . wf_tag('h2', true) : '';
        $data = $header . $title . $data . $footer;
        $profileIconMask = web_profile_icon();
        $connectedMask = web_bool_led(1, true);
        $disconnectedMask = web_bool_led(0, true);
        $frozenMask = wf_img('skins/icon_passive.gif');

        $data = str_replace($profileIconMask, '', $data);
        $data = str_replace($connectedMask, __('Connected'), $data);
        $data = str_replace($frozenMask, __('Freezed'), $data);
        $data = str_replace($disconnectedMask, wf_tag('b') . __('Disconnected') . wf_tag('b', true), $data);

        die($data);
    }

    /**
     * Renders debtors notifications by address selection
     * 
     * 
     * @return void
     */
    public function reportDebtAddr() {
        if (wf_CheckGet(array('aj_rdabuildsel'))) {
            if (!empty($_GET['aj_rdabuildsel'])) {
                $streetId = base64_decode($_GET['aj_rdabuildsel']);
                if ($streetId != '-') {
                    $buildParams = array();
                    if (!empty($this->users)) {
                        foreach ($this->users as $io => $each) {
                            if ($each['street'] == $streetId) {
                                $buildParams[$each['build']] = $each['build'];
                            }
                        }
                        natsort($buildParams);
                    }
                    $buildInputs = wf_Selector('buildsel', $buildParams, __('Build'), '', true);
                    $buildInputs .= wf_HiddenInput('streetsel', $streetId);
                    $buildInputs .= wf_TextInput('debtcash', __('The threshold at which the money considered user debtor'), '0', true, 4);
                    $buildInputs .= wf_Submit(__('Print'));
                    die($buildInputs);
                } else {
                    die('');
                }
            }
        }

        if (!wf_CheckPost(array('buildsel', 'streetsel'))) {
            $streetData = array();
            if (!empty($this->streets)) {
                foreach ($this->streets as $streetId => $eachStreetName) {
                    $streetId = base64_encode($eachStreetName);
                    $streetData[self::URL_REPORTS_MGMT . 'reportDebtAddr' . '&aj_rdabuildsel=' . $streetId] = $eachStreetName;
                }
            }

            $inputs = wf_AjaxLoader();
            $inputs .= wf_AjaxSelectorAC('aj_buildcontainer', $streetData, __('Street'), '', false);
            $inputs .= wf_AjaxContainer('aj_buildcontainer');

            $form = wf_Form('', 'POST', $inputs, 'glamour');
            show_window(__('Current debtors for delivery by address'), $form);
        } else {
            $searchBuild = mysql_real_escape_string($_POST['buildsel']);
            $searchStreet = mysql_real_escape_string($_POST['streetsel']);
            $debtCash = (wf_CheckPost(array('debtcash'))) ? ('-' . vf($_POST['debtcash'], 3)) : 0;
            $query = "SELECT * from `ukv_users` WHERE `cash`<'" . $debtCash . "' AND `street`='" . $searchStreet . "' AND `build`='" . $searchBuild . "' AND `active`='1' ORDER BY `street`";
            $allDebtors = simple_queryall($query);
            $rawTemplate = file_get_contents(CONFIG_PATH . "catv_debtors.tpl");
            $printableTemplate = '';
            if (!empty($allDebtors)) {
                foreach ($allDebtors as $io => $each) {
                    $rowtemplate = $rawTemplate;
                    $rowtemplate = str_ireplace('{REALNAME}', $each['realname'], $rowtemplate);
                    $rowtemplate = str_ireplace('{STREET}', $each['street'], $rowtemplate);
                    $rowtemplate = str_ireplace('{BUILD}', $each['build'], $rowtemplate);
                    $rowtemplate = str_ireplace('{APT}', $each['apt'], $rowtemplate);
                    $rowtemplate = str_ireplace('{DEBT}', $each['cash'], $rowtemplate);
                    $rowtemplate = str_ireplace('{CURDATE}', curdate(), $rowtemplate);
                    $rowtemplate = str_ireplace('{PAYDAY}', (date("Y-m-") . '01'), $rowtemplate);
                    $printableTemplate .= $rowtemplate;
                }
                $printableTemplate = wf_TableBody($printableTemplate, '100%', 0, 'sortable');
                $printableTemplate = $this->reportPrintable(__('Current debtors for delivery by address'), $printableTemplate);
            } else {
                show_window('', $this->messages->getStyledMessage(__('Nothing found'), 'info'));
            }
        }
    }

    /**
     * Renders debtors notifications by address selection
     * 
     * 
     * @return void
     */
    public function reportDebtStreets() {
        if (wf_CheckGet(array('aj_rdabuildsel'))) {
            if (!empty($_GET['aj_rdabuildsel'])) {
                $streetId = base64_decode($_GET['aj_rdabuildsel']);
                $buildInputs = wf_HiddenInput('streetsel', $streetId);
                $buildInputs .= wf_TextInput('debtcash', __('The threshold at which the money considered user debtor'), '0', true, 4);
                $buildInputs .= wf_Submit(__('Print'));
                die($buildInputs);
            } else {
                die('');
            }
        }


        if (!wf_CheckPost(array('streetsel'))) {
            $streetData = array();
            if (!empty($this->streets)) {
                foreach ($this->streets as $streetId => $eachStreetName) {
                    $streetId = base64_encode($eachStreetName);
                    $streetData[self::URL_REPORTS_MGMT . 'reportDebtStreets' . '&aj_rdabuildsel=' . $streetId] = $eachStreetName;
                }
            }

            $inputs = wf_AjaxLoader();
            $inputs .= wf_AjaxSelectorAC('aj_buildcontainer', $streetData, __('Street'), '', false);
            $inputs .= wf_AjaxContainer('aj_buildcontainer');

            $form = wf_Form('', 'POST', $inputs, 'glamour');
            show_window(__('Current debtors for delivery by streets'), $form);
        } else {
            $searchStreet = mysql_real_escape_string($_POST['streetsel']);
            $debtCash = (wf_CheckPost(array('debtcash'))) ? ('-' . vf($_POST['debtcash'], 3)) : 0;
            $query = "SELECT * from `ukv_users` WHERE `cash`<'" . $debtCash . "' AND `street`='" . $searchStreet . "'  AND `active`='1' ORDER BY `build`";
            $allDebtors = simple_queryall($query);
            $rawTemplate = file_get_contents(CONFIG_PATH . "catv_debtors.tpl");
            $printableTemplate = '';
            if (!empty($allDebtors)) {
                foreach ($allDebtors as $io => $each) {
                    $rowtemplate = $rawTemplate;
                    $rowtemplate = str_ireplace('{REALNAME}', $each['realname'], $rowtemplate);
                    $rowtemplate = str_ireplace('{STREET}', $each['street'], $rowtemplate);
                    $rowtemplate = str_ireplace('{BUILD}', $each['build'], $rowtemplate);
                    $rowtemplate = str_ireplace('{APT}', $each['apt'], $rowtemplate);
                    $rowtemplate = str_ireplace('{DEBT}', $each['cash'], $rowtemplate);
                    $rowtemplate = str_ireplace('{CURDATE}', curdate(), $rowtemplate);
                    $rowtemplate = str_ireplace('{PAYDAY}', (date("Y-m-") . '01'), $rowtemplate);
                    $printableTemplate .= $rowtemplate;
                }
                $printableTemplate = wf_TableBody($printableTemplate, '100%', 0, 'sortable');
                $printableTemplate = $this->reportPrintable(__('Current debtors for delivery by streets'), $printableTemplate);
            } else {
                show_window('', $this->messages->getStyledMessage(__('Nothing found'), 'info'));
            }
        }
    }

    /**
     * Returns UKV user id by contract
     * 
     * @param string $contract
     * 
     * @return int
     */
    protected function userGetByContract($contract) {
        $result = '';
        if (!empty($this->users)) {
            foreach ($this->users as $io => $each) {
                if ($each['contract'] == $contract) {
                    $result = $each['id'];
                    break;
                }
            }
        }
        return ($result);
    }

    /**
     * Returns array of available debtors
     * 
     * @return array
     */
    public function getDebtors() {
        $result = array();
        $debtorsArr = array();
        $counter = 0;
        $summDebt = 0;
        if ($this->altCfg['COMPLEX_ENABLED']) {
            $complexFlag = true;
            $inetAddress = zb_AddressGetFulladdresslistCached();
            $inetRealnames = zb_UserGetAllRealnames();
            $complexCfIds = $this->altCfg['COMPLEX_CFIDS'];
            $complexCfIds = explode(',', $complexCfIds);
            $complexContractCf = $complexCfIds[0];
            $complexActiveCf = $complexCfIds[1];
            $complexMasksTmp = $this->altCfg['COMPLEX_MASKS'];
            $complexMasksTmp = explode(',', $complexMasksTmp);
            $complexContracts = array();
            $complexActive = array();
            $inetCableseals = array();

            if (!empty($complexMasksTmp)) {
                foreach ($complexMasksTmp as $io => $each) {
                    $complexMasks[$each] = $each;
                }
            }
            $allComplexUsers = array(); //login=>userdata
            if (!empty($complexMasks)) {
                $allUsersRaw = zb_UserGetAllStargazerDataAssoc();
                if (!empty($allUsersRaw)) {
                    foreach ($allUsersRaw as $userLogin => $eachUser) {
                        foreach ($complexMasks as $ia => $eachComplexMask) {
                            if (ispos($eachUser['Tariff'], $eachComplexMask)) {
                                $allComplexUsers[$userLogin] = $eachUser;
                            }
                        }
                    }
                }
            }

            //getting complex active and contract fields
            $query_complex = "SELECT * from `cfitems`";
            $cfRaw = simple_queryall($query_complex);
            if (!empty($cfRaw)) {
                foreach ($cfRaw as $io => $eachCf) {
                    if ($eachCf['typeid'] == $complexContractCf) {
                        $complexContracts[$eachCf['login']] = $eachCf['content'];
                    }

                    if ($eachCf['typeid'] == $complexActiveCf) {
                        $complexActive[$eachCf['login']] = $eachCf['content'];
                    }
                }
            }
        } else {
            $complexFlag = false;
        }
        if (!empty($this->users)) {
            foreach ($this->users as $ix => $eachUser) {
                $userTariff = $eachUser['tariffid'];
                $tariffPrice = (isset($this->tariffs[$userTariff]['price'])) ? $this->tariffs[$userTariff]['price'] : 0;
                $debtMaxLimit = '-' . ($tariffPrice * $this->debtLimit);
                if (($eachUser['cash'] <= $debtMaxLimit) and ($eachUser['active'] == 1) and ($tariffPrice != 0)) {
                    $debtorsArr[$eachUser['street']][$eachUser['id']] = $eachUser;
                    $debtorsArr[$eachUser['street']][$eachUser['id']]['usertype'] = 'ukv';
                    $counter++;
                }
            }
        }


        //complex processing
        if ($complexFlag) {
            $userStreets = zb_AddressGetStreetUsers();
            if (!empty($allComplexUsers)) {
                foreach ($allComplexUsers as $io => $eachComplexUser) {
                    if (($eachComplexUser['Cash'] < -$eachComplexUser['Credit']) and (@$complexActive[$eachComplexUser['login']])) {
                        if (isset($complexContracts[$eachComplexUser['login']])) {
                            $ukvUserId = $this->userGetByContract($complexContracts[$eachComplexUser['login']]);
                            if (isset($this->users[$ukvUserId])) {
                                $userStreet = (isset($userStreets[$eachComplexUser['login']])) ? $userStreets[$eachComplexUser['login']] : __('Unknown');
                                $ukvUserData = $this->users[$ukvUserId];
                                $debtorsArr[$userStreet][$ukvUserId] = $ukvUserData;
                                $debtorsArr[$userStreet][$ukvUserId]['usertype'] = 'inet';
                                $debtorsArr[$userStreet][$ukvUserId]['cash'] = $eachComplexUser['Cash'];
                            }
                        }
                    }
                }
            }
        }



        if (!empty($debtorsArr)) {
            foreach ($debtorsArr as $streetName => $eachDebtorStreet) {
                if (!empty($eachDebtorStreet)) {
                    foreach ($eachDebtorStreet as $ia => $eachDebtor) {
                        $result[$eachDebtor['id']] = $eachDebtor['id'];
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * renders debtors report
     * 
     * @return void
     */
    public function reportDebtors() {
        $debtorsArr = array();
        $result = '';
        $counter = 0;
        $summDebt = 0;
        if ($this->altCfg['COMPLEX_ENABLED']) {
            $complexFlag = true;
            $inetAddress = zb_AddressGetFulladdresslistCached();
            $inetRealnames = zb_UserGetAllRealnames();
            $complexCfIds = $this->altCfg['COMPLEX_CFIDS'];
            $complexCfIds = explode(',', $complexCfIds);
            $complexContractCf = $complexCfIds[0];
            $complexActiveCf = $complexCfIds[1];
            $complexMasksTmp = $this->altCfg['COMPLEX_MASKS'];
            $complexMasksTmp = explode(',', $complexMasksTmp);
            $complexContracts = array();
            $complexActive = array();
            $inetCableseals = array();

            if (!empty($complexMasksTmp)) {
                foreach ($complexMasksTmp as $io => $each) {
                    $complexMasks[$each] = $each;
                }
            }
            $allComplexUsers = array(); //login=>userdata
            if (!empty($complexMasks)) {
                $allUsersRaw = zb_UserGetAllStargazerDataAssoc();
                if (!empty($allUsersRaw)) {
                    foreach ($allUsersRaw as $userLogin => $eachUser) {
                        foreach ($complexMasks as $ia => $eachComplexMask) {
                            if (ispos($eachUser['Tariff'], $eachComplexMask)) {
                                $allComplexUsers[$userLogin] = $eachUser;
                            }
                        }
                    }
                }
            }

            //getting complex active and contract fields
            $query_complex = "SELECT * from `cfitems`";
            $cfRaw = simple_queryall($query_complex);
            if (!empty($cfRaw)) {
                foreach ($cfRaw as $io => $eachCf) {
                    if ($eachCf['typeid'] == $complexContractCf) {
                        $complexContracts[$eachCf['login']] = $eachCf['content'];
                    }

                    if ($eachCf['typeid'] == $complexActiveCf) {
                        $complexActive[$eachCf['login']] = $eachCf['content'];
                    }
                }
            }
        } else {
            $complexFlag = false;
        }
        if (!empty($this->users)) {
            foreach ($this->users as $ix => $eachUser) {
                $userTariff = $eachUser['tariffid'];
                $tariffPrice = (isset($this->tariffs[$userTariff]['price'])) ? $this->tariffs[$userTariff]['price'] : 0;
                $debtMaxLimit = '-' . ($tariffPrice * $this->debtLimit);
                if (($eachUser['cash'] <= $debtMaxLimit) and ($eachUser['active'] == 1) and ($tariffPrice != 0)) {
                    $debtorsArr[$eachUser['street']][$eachUser['id']] = $eachUser;
                    $debtorsArr[$eachUser['street']][$eachUser['id']]['usertype'] = 'ukv';
                    $debtorsArr[$eachUser['street']][$eachUser['id']]['dsc'] = '';
                    $counter++;
                    $summDebt = $summDebt + $eachUser['cash'];
                }
            }
        }


        //complex processing
        if ($complexFlag) {
            $userStreets = zb_AddressGetStreetUsers();
            if (!empty($allComplexUsers)) {
                foreach ($allComplexUsers as $io => $eachComplexUser) {
                    if (
                        (($eachComplexUser['Cash'] < -$eachComplexUser['Credit']) and (@$complexActive[$eachComplexUser['login']]))
                        or (($eachComplexUser['Passive'] == 1) and (@$complexActive[$eachComplexUser['login']]))
                    ) {
                        if (isset($complexContracts[$eachComplexUser['login']])) {
                            $ukvUserId = $this->userGetByContract($complexContracts[$eachComplexUser['login']]);
                            if (isset($this->users[$ukvUserId])) {
                                $userStreet = (isset($userStreets[$eachComplexUser['login']])) ? $userStreets[$eachComplexUser['login']] : __('Unknown');
                                $ukvUserData = $this->users[$ukvUserId];
                                $debtorsArr[$userStreet][$ukvUserId] = $ukvUserData;
                                $debtorsArr[$userStreet][$ukvUserId]['usertype'] = 'inet';
                                $debtorsArr[$userStreet][$ukvUserId]['cash'] = $eachComplexUser['Cash'];
                                $debtorsArr[$userStreet][$ukvUserId]['active'] = @$complexActive[$eachComplexUser['login']];
                                $debtorsArr[$userStreet][$ukvUserId]['dsc'] = ($eachComplexUser['Passive']) ? ' ' . wf_img('skins/icon_passive.gif') : '';
                                $summDebt = $summDebt + $eachComplexUser['Cash'];
                                $counter++;
                            }
                        } else {
                            $result .= $this->messages->getStyledMessage(__('Missing registered UKV user with complex tariff') . ': ' . $eachComplexUser['login'], 'error');
                        }
                    }
                }
            }
        }



        //append report counter
        $result .= wf_tag('h4', false, 'row3') . __('Total') . ': ' . $counter . ' / ' . __('Debt') . ': ' . $summDebt . wf_tag('h4', true);


        if (!empty($debtorsArr)) {
            foreach ($debtorsArr as $streetName => $eachDebtorStreet) {
                if (!empty($eachDebtorStreet)) {
                    $result .= wf_tag('h3') . $streetName . wf_tag('h3', true);
                    $cells = wf_TableCell(__('Contract'), '10%');
                    $cells .= wf_TableCell(__('Full address'), '31%');
                    $cells .= wf_TableCell(__('Real Name'), '30%');
                    $cells .= wf_TableCell(__('Tariff'), '15%');
                    $cells .= wf_TableCell(__('Cash'), '7%');
                    $cells .= wf_TableCell(__('Seal'));
                    $cells .= wf_TableCell(__('Status'), '7%');
                    $rows = wf_TableRow($cells, 'row1');
                    foreach ($eachDebtorStreet as $ia => $eachDebtor) {
                        $debtorAddress = $this->userGetFullAddress($eachDebtor['id']);
                        $debtorLink = wf_Link(self::URL_USERS_PROFILE . $eachDebtor['id'], web_profile_icon() . ' ', false);
                        $userCash = $eachDebtor['cash'];
                        $cableSeal = $eachDebtor['cableseal'];
                        $userTariff = $this->tariffs[$eachDebtor['tariffid']]['tariffname'];
                        $activeLed = web_bool_led($eachDebtor['active'], true);
                        $userRealname = $eachDebtor['realname'];
                        $userContract = $eachDebtor['contract'];

                        $cells = wf_TableCell($userContract);
                        $cells .= wf_TableCell($debtorLink . $debtorAddress);
                        $cells .= wf_TableCell($userRealname);
                        $cells .= wf_TableCell($userTariff);
                        $cells .= wf_TableCell($userCash);
                        $cells .= wf_TableCell($cableSeal);
                        $cells .= wf_TableCell($activeLed . $eachDebtor['dsc']);
                        $rows .= wf_TableRow($cells, 'row3');
                    }
                    $result .= wf_TableBody($rows, '100%', '0', 'sortable');
                }
            }
        }


        $printableControl = wf_Link(self::URL_REPORTS_MGMT . 'reportDebtors&printable=true', wf_img('skins/icon_print.png', __('Print')));

        if (wf_CheckGet(array('printable'))) {
            $this->reportPrintable(__('Debtors'), $result);
        } else {
            show_window(__('Debtors') . ' ' . $printableControl, $result);
        }
    }

    /**
     * renders anti-debtors report
     * 
     * @return void
     */
    public function reportAntiDebtors() {
        $debtorsArr = array();
        $result = '';
        $counter = 0;

        if ($this->altCfg['COMPLEX_ENABLED']) {
            $complexFlag = true;
            $inetAddress = zb_AddressGetFulladdresslistCached();
            $inetRealnames = zb_UserGetAllRealnames();
            $complexCfIds = $this->altCfg['COMPLEX_CFIDS'];
            $complexCfIds = explode(',', $complexCfIds);
            $complexContractCf = $complexCfIds[0];
            $complexActiveCf = $complexCfIds[1];
            $complexMasksTmp = $this->altCfg['COMPLEX_MASKS'];
            $complexMasksTmp = explode(',', $complexMasksTmp);
            $complexContracts = array();
            $complexActive = array();
            $inetCableseals = array();

            if (!empty($complexMasksTmp)) {
                foreach ($complexMasksTmp as $io => $each) {
                    $complexMasks[$each] = $each;
                }
            }
            $allComplexUsers = array(); //login=>userdata
            if (!empty($complexMasks)) {
                $allUsersRaw = zb_UserGetAllStargazerDataAssoc();
                if (!empty($allUsersRaw)) {
                    foreach ($allUsersRaw as $userLogin => $eachUser) {
                        foreach ($complexMasks as $ia => $eachComplexMask) {
                            if (ispos($eachUser['Tariff'], $eachComplexMask)) {
                                $allComplexUsers[$userLogin] = $eachUser;
                            }
                        }
                    }
                }
            }

            //getting complex active and contract fields
            $query_complex = "SELECT * from `cfitems`";
            $cfRaw = simple_queryall($query_complex);
            if (!empty($cfRaw)) {
                foreach ($cfRaw as $io => $eachCf) {
                    if ($eachCf['typeid'] == $complexContractCf) {
                        $complexContracts[$eachCf['login']] = $eachCf['content'];
                    }

                    if ($eachCf['typeid'] == $complexActiveCf) {
                        $complexActive[$eachCf['login']] = $eachCf['content'];
                    }
                }
            }
        } else {
            $complexFlag = false;
        }


        if (!empty($this->users)) {
            foreach ($this->users as $ix => $eachUser) {
                $userTariff = $eachUser['tariffid'];
                $tariffPrice = (isset($this->tariffs[$userTariff]['price'])) ? $this->tariffs[$userTariff]['price'] : 0;
                if (($eachUser['cash'] >= 0) and ($eachUser['active'] == 0) and ($tariffPrice != 0)) {
                    $debtorsArr[$eachUser['street']][$eachUser['id']] = $eachUser;
                    $counter++;
                }
            }
        }
        //complex processing
        if ($complexFlag) {
            $userStreets = zb_AddressGetStreetUsers();
            if (!empty($allComplexUsers)) {
                foreach ($allComplexUsers as $io => $eachComplexUser) {
                    if (($eachComplexUser['Cash'] >= -$eachComplexUser['Credit']) and (!@$complexActive[$eachComplexUser['login']])) {
                        if (isset($complexContracts[$eachComplexUser['login']])) {
                            $ukvUserId = $this->userGetByContract($complexContracts[$eachComplexUser['login']]);
                            if (isset($this->users[$ukvUserId])) {
                                if (!@$eachComplexUser['Passive']) { //user is not frozen
                                    $userStreet = (isset($userStreets[$eachComplexUser['login']])) ? $userStreets[$eachComplexUser['login']] : __('Unknown');
                                    $ukvUserData = $this->users[$ukvUserId];
                                    $debtorsArr[$userStreet][$ukvUserId] = $ukvUserData;
                                    $debtorsArr[$userStreet][$ukvUserId]['usertype'] = 'inet';
                                    $debtorsArr[$userStreet][$ukvUserId]['cash'] = $eachComplexUser['Cash'];
                                    $debtorsArr[$userStreet][$ukvUserId]['active'] = @$complexActive[$eachComplexUser['login']];
                                    $counter++;
                                }
                            }
                        } else {
                            $result .= $this->messages->getStyledMessage(__('Missing registered UKV user with complex tariff') . ': ' . $eachComplexUser['login'], 'error');
                        }
                    }
                }
            }
        }

        //append report counter
        $result .= wf_tag('h4', false, 'row3') . __('Total') . ': ' . $counter . wf_tag('h4', true);

        if (!empty($debtorsArr)) {
            foreach ($debtorsArr as $streetName => $eachDebtorStreet) {
                if (!empty($eachDebtorStreet)) {
                    $result .= wf_tag('h3') . $streetName . wf_tag('h3', true);
                    $cells = wf_TableCell(__('Contract'), '10%');
                    $cells .= wf_TableCell(__('Full address'), '31%');
                    $cells .= wf_TableCell(__('Real Name'), '30%');
                    $cells .= wf_TableCell(__('Tariff'), '15%');
                    $cells .= wf_TableCell(__('Cash'), '7%');
                    $cells .= wf_TableCell(__('Seal'));
                    $cells .= wf_TableCell(__('Status'), '7%');
                    $rows = wf_TableRow($cells, 'row1');
                    foreach ($eachDebtorStreet as $ia => $eachDebtor) {
                        $cells = wf_TableCell($eachDebtor['contract']);
                        $debtorAddress = $this->userGetFullAddress($eachDebtor['id']);
                        $debtorLink = wf_Link(self::URL_USERS_PROFILE . $eachDebtor['id'], web_profile_icon() . ' ', false);
                        $cells .= wf_TableCell($debtorLink . $debtorAddress);
                        $cells .= wf_TableCell($eachDebtor['realname']);
                        $cells .= wf_TableCell($this->tariffs[$eachDebtor['tariffid']]['tariffname']);
                        $cells .= wf_TableCell($eachDebtor['cash']);
                        $cells .= wf_TableCell($eachDebtor['cableseal']);
                        $cells .= wf_TableCell(web_bool_led($eachDebtor['active'], true));
                        $rows .= wf_TableRow($cells, 'row3');
                    }

                    $result .= wf_TableBody($rows, '100%', '0', 'sortable');
                }
            }
        }

        $printableControl = wf_Link(self::URL_REPORTS_MGMT . 'reportAntiDebtors&printable=true', wf_img('skins/icon_print.png', __('Print')));

        if (wf_CheckGet(array('printable'))) {
            $this->reportPrintable(__('AntiDebtors'), $result);
        } else {
            show_window(__('AntiDebtors') . ' ' . $printableControl, $result);
        }
    }

    /**
     * renders tariffs popularity report
     * 
     * @return void
     */
    public function reportTariffs() {
        $tariffArr = array();
        $tariffUsers = array();
        $tariffCounter = array();
        $tariffMoves = array();
        $userTotalCount = sizeof($this->users);

        $result = '';
        if (!empty($this->tariffs)) {
            foreach ($this->tariffs as $io => $each) {
                $tariffArr[$each['id']] = $each['tariffname'];
                $tariffCounter[$each['id']]['all'] = 0;
                $tariffCounter[$each['id']]['alive'] = 0;
            }
        }

        if ((!empty($tariffArr)) and (!empty($this->users))) {
            foreach ($this->users as $io => $eachUser) {
                if (!empty($eachUser['tariffid'])) {
                    $tariffUsers[$eachUser['tariffid']][] = $eachUser;
                    $tariffCounter[$eachUser['tariffid']]['all'] = $tariffCounter[$eachUser['tariffid']]['all'] + 1;
                    if ($eachUser['active']) {
                        $tariffCounter[$eachUser['tariffid']]['alive'] = $tariffCounter[$eachUser['tariffid']]['alive'] + 1;
                    }
                    //next month movements
                    if ($eachUser['tariffnmid']) {
                        $tariffMoves[$eachUser['id']]['from'] = $eachUser['tariffid'];
                        $tariffMoves[$eachUser['id']]['to'] = $eachUser['tariffnmid'];
                    }
                }
            }
        }

        //tariff summary grid
        $cells = wf_TableCell(__('Tariff'));
        $cells .= wf_TableCell(__('Total'));
        $cells .= wf_TableCell(__('Visual'));
        $cells .= wf_TableCell(__('Active'));
        $rows = wf_TableRow($cells, 'row1');

        foreach ($tariffArr as $tariffId => $tariffName) {
            $tariffLink = wf_Link(self::URL_REPORTS_MGMT . 'reportTariffs&showtariffusers=' . $tariffId, $tariffName);
            $cells = wf_TableCell($tariffLink);
            $cells .= wf_TableCell($tariffCounter[$tariffId]['all']);
            $cells .= wf_TableCell(web_bar($tariffCounter[$tariffId]['all'], $userTotalCount));
            $cells .= wf_TableCell(web_barTariffs($tariffCounter[$tariffId]['alive'], ($tariffCounter[$tariffId]['all'] - $tariffCounter[$tariffId]['alive'])));
            $rows .= wf_TableRow($cells, 'row5');
        }

        $result .= wf_TableBody($rows, '100%', '0', 'sortable');
        $result .= wf_tag('b') . __('Total') . ': ' . $userTotalCount . wf_tag('b', true);
        //tariff move summary
        if (!empty($tariffMoves)) {
            if (!wf_CheckGet(array('showtariffusers'))) {
                $result .= wf_tag('br');
                $result .= wf_tag('h3') . __('Planned tariff changes') . wf_tag('h3', true);

                $cells = wf_TableCell(__('User'));
                $cells .= wf_TableCell(__('Real Name'));
                $cells .= wf_TableCell(__('Tariff'));
                $cells .= wf_TableCell(__('Next month'));
                $rows = wf_TableRow($cells, 'row1');
                foreach ($tariffMoves as $moveUserId => $moveData) {
                    $cells = wf_TableCell(wf_Link(self::URL_USERS_PROFILE . $moveUserId, web_profile_icon() . ' ' . $this->userGetFullAddress($moveUserId)));
                    $cells .= wf_TableCell($this->userGetRealName($moveUserId));
                    $cells .= wf_TableCell($this->tariffGetName($moveData['from']));
                    $cells .= wf_TableCell($this->tariffGetName($moveData['to']));
                    $rows .= wf_TableRow($cells, 'row3');
                }
                $result .= wf_TableBody($rows, '100%', 0, 'sortable');
                $result .= wf_tag('b') . __('Total') . ': ' . sizeof($tariffMoves) . wf_tag('b', true);
            }
        }

        //show per tariff users
        if (wf_CheckGet(array('showtariffusers'))) {
            $tariffSearch = vf($_GET['showtariffusers'], 3);
            if (isset($tariffUsers[$tariffSearch])) {
                if (!empty($tariffUsers[$tariffSearch])) {
                    $result .= wf_delimiter();
                    $result .= wf_tag('h2') . __('Tariff') . ': ' . $tariffArr[$tariffSearch] . wf_tag('h2', true);
                    $cells = wf_TableCell(__('Contract'), '10%');
                    $cells .= wf_TableCell(__('Full address'), '31%');
                    $cells .= wf_TableCell(__('Real Name'), '25%');
                    $cells .= wf_TableCell(__('Tariff'), '15%');
                    $cells .= wf_TableCell(__('Cash'), '7%');
                    $cells .= wf_TableCell(__('Seal'), '5%');
                    $cells .= wf_TableCell(__('Status'), '7%');
                    $rows = wf_TableRow($cells, 'row1');

                    foreach ($tariffUsers[$_GET['showtariffusers']] as $io => $eachUser) {
                        $cells = wf_TableCell($eachUser['contract']);
                        $fullAddress = $this->userGetFullAddress($eachUser['id']);
                        $profileLink = wf_Link(self::URL_USERS_PROFILE . $eachUser['id'], web_profile_icon() . ' ', false, '');
                        $cells .= wf_TableCell($profileLink . $fullAddress);
                        $cells .= wf_TableCell($eachUser['realname']);
                        $cells .= wf_TableCell($this->tariffs[$eachUser['tariffid']]['tariffname']);
                        $cells .= wf_TableCell($eachUser['cash']);
                        $cells .= wf_tablecell($eachUser['cableseal']);
                        $cells .= wf_TableCell(web_bool_led($eachUser['active'], true));
                        $rows .= wf_TableRow($cells, 'row3');
                    }

                    $result .= wf_TableBody($rows, '100%', '0', 'sortable');
                }
            }
            $printableControl = wf_Link(self::URL_REPORTS_MGMT . 'reportTariffs&showtariffusers=' . $tariffSearch . '&printable=true', wf_img('skins/icon_print.png', __('Print')));
        } else {
            $printableControl = wf_Link(self::URL_REPORTS_MGMT . 'reportTariffs&printable=true', wf_img('skins/icon_print.png', __('Print')));
        }

        if (!wf_CheckGet(array('printable'))) {
            show_window(__('Tariffs report') . ' ' . $printableControl, $result);
        } else {
            $this->reportPrintable(__('Tariffs report'), $result);
        }
    }

    /**
     * returns payments year summ by selected year
     * 
     * @param string $year year to show
     * 
     * @return string
     */
    protected function paymentsGetYearSumm($year) {
        $year = vf($year);
        $query = "SELECT SUM(`summ`) from `ukv_payments` WHERE `date` LIKE '" . $year . "-%' AND `summ` > 0 AND `visible`='1'";
        $result = simple_query($query);
        return ($result['SUM(`summ`)']);
    }

    /**
     * returns month payments summ by some year and month
     * 
     * @param string  $year year to select
     * @param string $month month to select
     * 
     * @return string
     */
    protected function paymentsGetMonthSumm($year, $month) {
        $year = vf($year);
        $query = "SELECT SUM(`summ`) from `ukv_payments` WHERE `date` LIKE '" . $year . "-" . $month . "%' AND `summ` > 0 AND `visible`='1'";
        $result = simple_query($query);
        return ($result['SUM(`summ`)']);
    }

    /**
     * returns month payments count by some year and month
     * 
     * @param $year year to select
     * @param $month month to select
     * 
     * @return string
     */
    protected function paymentsGetMonthCount($year, $month) {
        $year = vf($year);
        $query = "SELECT COUNT(`id`) from `ukv_payments` WHERE `date` LIKE '" . $year . "-" . $month . "%' AND `summ` > 0 AND `visible`='1'";
        $result = simple_query($query);
        return ($result['COUNT(`id`)']);
    }

    /**
     * shows payments graph for some year
     * 
     * @param string $year year to show
     * 
     * @return void
     */
    protected function paymentsShowGraph($year) {
        $months = months_array();
        $year_summ = $this->paymentsGetYearSumm($year);
        $curtime = time();
        $yearPayData = array();
        $cacheTime = 3600; //sec intervall to cache
        $cache = new UbillingCache();

        $cells = wf_TableCell('');
        $cells .= wf_TableCell(__('Month'));
        $cells .= wf_TableCell(__('Payments count'));
        $cells .= wf_TableCell(__('ARPU'));
        $cells .= wf_TableCell(__('Cash'));
        $cells .= wf_TableCell(__('Visual'), '50%');
        $rows = wf_TableRow($cells, 'row1');

        //caching subroutine
        $renewTime = $cache->get('UKVYPD_LAST', $cacheTime);
        if (empty($renewTime)) {
            //first usage
            $renewTime = $curtime;
            $cache->set('UKVYPD_LAST', $renewTime, $cacheTime);
            $updateCache = true;
        } else {
            //cache time already set
            $timeShift = $curtime - $renewTime;
            if ($timeShift > $cacheTime) {
                //cache update needed
                $updateCache = true;
            } else {
                //load data from cache or init new cache
                $yearPayData_raw = $cache->get('UKVYPD_CACHE', $cacheTime);
                if (empty($yearPayData_raw)) {
                    //first usage
                    $emptyCache = array();
                    $emptyCache = serialize($emptyCache);
                    $emptyCache = base64_encode($emptyCache);
                    $cache->set('UKVYPD_CACHE', $emptyCache, $cacheTime);
                    $updateCache = true;
                } else {
                    // data loaded from cache
                    $yearPayData = base64_decode($yearPayData_raw);
                    $yearPayData = unserialize($yearPayData);
                    $updateCache = false;
                    //check is current year already cached?
                    if (!isset($yearPayData[$year]['graphs'])) {
                        $updateCache = true;
                    }

                    //check is manual cache refresh is needed?
                    if (wf_CheckGet(array('forcecache'))) {
                        $updateCache = true;
                        rcms_redirect(self::URL_REPORTS_MGMT . 'reportFinance');
                    }
                }
            }
        }

        if ($updateCache) {
            foreach ($months as $eachmonth => $monthname) {
                $month_summ = $this->paymentsGetMonthSumm($year, $eachmonth);
                $paycount = $this->paymentsGetMonthCount($year, $eachmonth);

                $monthArpu = (empty($paycount)) ? 0 : @round($month_summ / $paycount, 2);

                if (is_nan($monthArpu)) {
                    $monthArpu = 0;
                }

                $cells = wf_TableCell($eachmonth);
                $cells .= wf_TableCell(wf_Link(self::URL_REPORTS_MGMT . 'reportFinance&month=' . $year . '-' . $eachmonth, rcms_date_localise($monthname)));
                $cells .= wf_TableCell($paycount);
                $cells .= wf_TableCell($monthArpu);
                $cells .= wf_TableCell(zb_CashBigValueFormat($month_summ), '', '', 'align="right"');
                $cells .= wf_TableCell(web_bar($month_summ, $year_summ));
                $rows .= wf_TableRow($cells, 'row3');
            }
            $result = wf_TableBody($rows, '100%', '0', 'sortable');
            $yearPayData[$year]['graphs'] = $result;
            //write to cache
            $cache->set('UKVYPD_LAST', $curtime, $cacheTime);
            $newCache = serialize($yearPayData);
            $newCache = base64_encode($newCache);
            $cache->set('UKVYPD_CACHE', $newCache, $cacheTime);
        } else {
            //take data from cache
            if (isset($yearPayData[$year]['graphs'])) {
                $result = $yearPayData[$year]['graphs'];
                $result .= __('Cache state at time') . ': ' . date("Y-m-d H:i:s", ($renewTime)) . ' ';
                $result .= wf_Link(self::URL_REPORTS_MGMT . 'reportFinance&forcecache=true', wf_img('skins/icon_cleanup.png', __('Renew')), false, '');
            } else {
                $result = __('Strange exeption');
            }
        }


        show_window(__('Payments by') . ' ' . $year, $result);
    }

    /**
     * returns UKV payments by some query
     * 
     * @param string $query raw SQL query to select data
     * 
     * @return string
     */
    protected function paymentsShow($query) {
        if (empty($this->cashtypes)) {
            $this->loadCashtypes();
        }
        $profitCalcFlag = (@$this->altCfg['FASTPROFITCALC_ENABLED']) ? true : false;
        $alltypes = $this->cashtypes;
        $allapayments = simple_queryall($query);
        $cashTypesStats = array();

        $total = 0;
        $totalPaycount = 0;

        $cells = wf_TableCell(__('ID'));
        $cells .= wf_TableCell(__('Date'));
        $cells .= wf_TableCell(__('Cash'));
        if ($profitCalcFlag) {
            $cells .= wf_TableCell(__('💲'));
        }
        //optional contract display
        if ($this->altCfg['FINREP_CONTRACT']) {
            $cells .= wf_TableCell(__('Contract'));
        }

        $cells .= wf_TableCell(__('Full address'));
        $cells .= wf_TableCell(__('Real Name'));
        $cells .= wf_TableCell(__('Cash type'));
        $cells .= wf_TableCell(__('Notes'));
        $cells .= wf_TableCell(__('Admin'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($allapayments)) {
            foreach ($allapayments as $io => $eachpayment) {
                @$userData = $this->users[$eachpayment['userid']];

                if ($this->altCfg['TRANSLATE_PAYMENTS_NOTES']) {
                    $eachpayment['note'] = $this->translatePaymentNote($eachpayment['note']);
                }

                $cells = wf_TableCell($eachpayment['id']);
                $cells .= wf_TableCell($eachpayment['date']);
                $cells .= wf_TableCell($eachpayment['summ']);
                if ($profitCalcFlag) {
                    $ourProfit = ($eachpayment['summ'] > 0) ? $eachpayment['summ'] : 0;
                    $cells .= wf_TableCell(wf_CheckInput('profitcalc', '', false, false, 'prcalc', '', 'pfstc="' . $ourProfit . '"'));
                }
                //optional contract display
                if ($this->altCfg['FINREP_CONTRACT']) {
                    $cells .= wf_TableCell(@$userData['contract']);
                }

                $userLink = wf_Link(self::URL_USERS_PROFILE . $eachpayment['userid'], web_profile_icon());
                $cells .= wf_TableCell($userLink . ' ' . $this->userGetFullAddress($eachpayment['userid']));
                $cells .= wf_TableCell(@$userData['realname']);
                $cells .= wf_TableCell(@__($alltypes[$eachpayment['cashtypeid']]));
                $cells .= wf_TableCell($eachpayment['note']);
                $cells .= wf_TableCell($eachpayment['admin']);
                $rows .= wf_TableRow($cells, 'row3');

                if ($eachpayment['summ'] > 0) {
                    $total = $total + $eachpayment['summ'];
                    $totalPaycount++;
                    //per cashtype tiny stats
                    if (isset($cashTypesStats[$eachpayment['cashtypeid']])) {
                        $cashTypesStats[$eachpayment['cashtypeid']]['count']++;
                        $cashTypesStats[$eachpayment['cashtypeid']]['summ'] += $eachpayment['summ'];
                    } else {
                        $cashTypesStats[$eachpayment['cashtypeid']]['count'] = 1;
                        $cashTypesStats[$eachpayment['cashtypeid']]['summ'] = $eachpayment['summ'];
                    }
                }
            }
        }

        $result = wf_TableBody($rows, '100%', '0', 'sortable');
        $result .= wf_tag('strong') . __('Cash') . ': ' . $total . wf_tag('strong', true) . wf_tag('br');
        $result .= wf_tag('strong') . __('Payments count') . ': ' . $totalPaycount . wf_tag('strong', true);
        if ($profitCalcFlag) {
            //inline profit calculator
            $profitCalc = '';
            $profitCalc .= wf_AjaxContainer('profitcalccontainer');
            $profitCalc .= wf_tag('link', false, '', 'rel="stylesheet" href="skins/profitcalc.css" type="text/css"');
            $profitCalc .= wf_tag('script', false, '', 'src="modules/jsc/profitcalc.js" language="javascript"') . wf_tag('script', true);
            $result .= $profitCalc;
            $result .= wf_delimiter(0);
        }

        //render cashtype stats
        if (!empty($cashTypesStats)) {
            $cells = wf_TableCell(__('Cash type'));
            $cells .= wf_TableCell(__('Count'));
            $cells .= wf_TableCell(__('Cash'));
            $rows = wf_TableRow($cells, 'row1');

            foreach ($cashTypesStats as $cashtypeid => $eachct) {
                $cells = wf_TableCell(@$this->cashtypes[$cashtypeid]);
                $cells .= wf_TableCell($eachct['count']);
                $cells .= wf_TableCell($eachct['summ']);
                $rows .= wf_TableRow($cells, 'row3');
            }
            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        }



        return ($result);
    }

    /**
     * Renders tagcloud report
     * 
     * @return void
     */
    public function reportTagcloud() {
        $result = '';
        $reportTmp = array();
        if (!empty($this->allTagtypes)) {
            if (!empty($this->allUserTags)) {
                foreach ($this->allUserTags as $io => $each) {
                    if (!isset($reportTmp[$each['tagtypeid']])) {
                        $result .= $this->getTagBody($each['tagtypeid'], true);
                        $reportTmp[$each['tagtypeid']] = $each['tagtypeid'];
                    }
                }
            }
            if (wf_CheckGet(array('tagid'))) {
                $showTagid = vf($_GET['tagid'], 3);


                $result .= wf_delimiter();
                $result .= wf_tag('h2') . __('Tag') . ': ' . @$this->allTagtypes[$showTagid]['tagname'] . wf_tag('h2', true);
                $cells = wf_TableCell(__('Contract'), '10%');
                $cells .= wf_TableCell(__('Full address'), '31%');
                $cells .= wf_TableCell(__('Real Name'), '25%');
                $cells .= wf_TableCell(__('Tariff'), '15%');
                $cells .= wf_TableCell(__('Cash'), '7%');
                $cells .= wf_TableCell(__('Seal'), '5%');
                $cells .= wf_TableCell(__('Status'), '7%');
                $rows = wf_TableRow($cells, 'row1');

                foreach ($this->allUserTags as $io => $eachtag) {
                    if ($eachtag['tagtypeid'] == $showTagid) {
                        $eachUser = @$this->users[$eachtag['userid']];
                        if (!empty($eachUser)) {
                            $cells = wf_TableCell($eachUser['contract']);
                            $fullAddress = $this->userGetFullAddress($eachUser['id']);
                            $profileLink = wf_Link(self::URL_USERS_PROFILE . $eachUser['id'], web_profile_icon() . ' ', false, '');
                            $cells .= wf_TableCell($profileLink . $fullAddress);
                            $cells .= wf_TableCell($eachUser['realname']);
                            $cells .= wf_TableCell($this->tariffs[$eachUser['tariffid']]['tariffname']);
                            $cells .= wf_TableCell($eachUser['cash']);
                            $cells .= wf_tablecell($eachUser['cableseal']);
                            $cells .= wf_TableCell(web_bool_led($eachUser['active'], true));
                            $rows .= wf_TableRow($cells, 'row3');
                        }
                    }
                }

                $result .= wf_TableBody($rows, '100%', '0', 'sortable');

                ////////////////
            }

            show_window(__('Tag cloud'), $result);
        } else {
            show_window(__('Tag cloud'), $this->messages->getStyledMessage(__('Nothing found'), 'warning'));
        }
    }

    /**
     * renders finance report
     * 
     * @return void
     */
    public function reportFinance() {

        $show_year = (!wf_CheckPost(array('yearsel'))) ? curyear() : $_POST['yearsel'];

        $dateSelectorPreset = (wf_CheckPost(array('showdatepayments'))) ? $_POST['showdatepayments'] : curdate();
        $dateinputs = wf_DatePickerPreset('showdatepayments', $dateSelectorPreset);
        $dateinputs .= wf_Submit(__('Show'));
        $dateform = wf_Form(self::URL_REPORTS_MGMT . 'reportFinance', 'POST', $dateinputs, 'glamour');


        $yearinputs = wf_YearSelector('yearsel');
        $yearinputs .= wf_Submit(__('Show'));
        $yearform = wf_Form(self::URL_REPORTS_MGMT . 'reportFinance', 'POST', $yearinputs, 'glamour');


        $controlcells = wf_TableCell(wf_tag('h3', false, 'title') . __('Year') . wf_tag('h3', true));
        $controlcells .= wf_TableCell(wf_tag('h3', false, 'title') . __('Payments by date') . wf_tag('h3', true));
        $controlcells .= wf_TableCell(wf_tag('h3', false, 'title') . __('Debt') . wf_tag('h3', true));
        $controlrows = wf_TableRow($controlcells);

        $controlcells = wf_TableCell($yearform);
        $controlcells .= wf_TableCell($dateform);
        //extract total debt summ
        $debt_q = "SELECT SUM(`cash`) as `totaldebt`, COUNT(`id`) as `debtcount` from `ukv_users` WHERE `cash`<0";
        $totalDebt = simple_query($debt_q);
        $debtData = __('Cash') . ': ' . wf_tag('b') . $totalDebt['totaldebt'] . wf_tag('b', true) . wf_tag('br');
        $debtData .= __('Count') . ': ' . wf_tag('b') . $totalDebt['debtcount'] . wf_tag('b', true);
        $controlcells .= wf_TableCell($debtData);
        $controlrows .= wf_TableRow($controlcells);

        $controlgrid = wf_TableBody($controlrows, '100%', 0, '');
        show_window('', $controlgrid);
        //show per month report
        $this->paymentsShowGraph($show_year);

        if (!isset($_GET['month'])) {

            // payments by somedate
            if (isset($_POST['showdatepayments'])) {
                $paydate = mysql_real_escape_string($_POST['showdatepayments']);
                $paydate = (!empty($paydate)) ? $paydate : curdate();
                show_window(__('Payments by date') . ' ' . $paydate, $this->paymentsShow("SELECT * from `ukv_payments` WHERE `date` LIKE '" . $paydate . "%' AND `visible`='1' ORDER by `date` DESC;"));
            } else {

                // today payments
                $today = curdate();
                show_window(__('Today payments'), $this->paymentsShow("SELECT * from `ukv_payments` WHERE `date` LIKE '" . $today . "%' AND `visible`='1' ORDER by `date` DESC;"));
            }
        } else {
            // show monthly payments
            $paymonth = mysql_real_escape_string($_GET['month']);

            show_window(__('Month payments'), $this->paymentsShow("SELECT * from `ukv_payments` WHERE `date` LIKE '" . $paymonth . "%'  AND `visible`='1' ORDER by `date` DESC;"));
        }
    }

    /**
     * renders users signup report
     * 
     * @return void
     */
    public function reportSignup() {
        $regdates = array();
        $months = months_array();
        $monthCount = array();
        $showYear = (wf_CheckPost(array('showyear'))) ? vf($_POST['showyear'], 3) : curyear();
        $showMonth = (wf_CheckGet(array('month'))) ? mysql_real_escape_string($_GET['month']) : curmonth();
        $yearCount = 0;
        $displayCount = 0;
        $displayTmp = array();

        if (!empty($this->users)) {
            foreach ($this->users as $io => $each) {
                if (!empty($each['regdate'])) {
                    $dateTime = explode(' ', $each['regdate']);
                    $regdates[$dateTime[0]][] = $each['id'];
                }
            }
        }

        // show year selector
        $yearInputs = wf_YearSelector('showyear', ' ', false);
        $yearInputs .= wf_Submit(__('Show'));
        $yearForm = wf_Form('', 'POST', $yearInputs, 'glamour');
        show_window(__('Year'), $yearForm);





        //extract year signup count data
        foreach ($months as $eachMonth => $monthName) {
            $sigcount = 0;
            if (!empty($regdates)) {
                foreach ($regdates as $eachRegDate => $userIds) {
                    $dateMark = $showYear . '-' . $eachMonth;
                    if (ispos($eachRegDate, $dateMark)) {
                        $sigcount = $sigcount + count($regdates[$eachRegDate]);
                    }
                    $monthCount[$eachMonth] = $sigcount;
                }
                $yearCount = $yearCount + $sigcount;
            }
        }

        //render per year grid
        $cells = wf_TableCell('');
        $cells .= wf_TableCell(__('Month'));
        $cells .= wf_TableCell(__('Signups'));
        $cells .= wf_TableCell(__('Visual'));
        $rows = wf_TableRow($cells, 'row1');

        foreach ($months as $eachMonth => $monthName) {
            $cells = wf_TableCell($eachMonth);
            $monthLink = wf_Link(self::URL_REPORTS_MGMT . 'reportSignup&month=' . $showYear . '-' . $eachMonth, rcms_date_localise($monthName), false);
            $cells .= wf_TableCell($monthLink);
            $cells .= wf_TableCell($monthCount[$eachMonth]);
            $cells .= wf_TableCell(web_bar($monthCount[$eachMonth], $yearCount));
            $rows .= wf_TableRow($cells, 'row3');
        }

        $result = wf_TableBody($rows, '100%', 0, 'sortable');
        $result .= __('Total') . ': ' . $yearCount;
        show_window(__('User signups by year') . ' ' . $showYear, $result);

        //render per month registrations
        $cells = wf_TableCell(__('ID'));
        $cells .= wf_TableCell(__('Date'));
        $cells .= wf_TableCell(__('Full address'));
        $cells .= wf_TableCell(__('Real Name'));
        $cells .= wf_TableCell(__('Tariff'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($regdates)) {
            foreach ($regdates as $eachRegDate => $eachRegUsers) {
                if (ispos($eachRegDate, $showMonth)) {
                    foreach ($eachRegUsers as $ix => $eachUserId) {
                        $displayTmp[] = $eachUserId;
                    }
                }
            }
        }

        if (!empty($displayTmp)) {
            rsort($displayTmp);
            foreach ($displayTmp as $ix => $eachUserId) {
                $cells = wf_TableCell($eachUserId);
                $cells .= wf_TableCell($this->users[$eachUserId]['regdate']);
                $userLink = wf_Link(self::URL_USERS_PROFILE . $eachUserId, web_profile_icon() . ' ', false);
                $cells .= wf_TableCell($userLink . $this->userGetFullAddress($eachUserId));
                $cells .= wf_TableCell($this->users[$eachUserId]['realname']);
                $cells .= wf_TableCell(@$this->tariffs[$this->users[$eachUserId]['tariffid']]['tariffname']);
                $rows .= wf_TableRow($cells, 'row3');
                $displayCount++;
            }
        }

        $result = wf_TableBody($rows, '100%', '0', 'sortable');
        $result .= __('Total') . ': ' . $displayCount;

        if ($showMonth == curmonth()) {
            $monthTitle = __('Current month user signups');
        } else {
            $monthTitle = __('User signups by month') . ' ' . $showMonth;
        }
        show_window($monthTitle, $result);
    }

    /**
     * renders fees report by selected month
     * 
     * @return void
     */
    public function reportFees() {
        $allFeesDates_q = "SELECT * from `ukv_fees` ORDER BY `id` DESC;";
        $allFeesDates = simple_queryall($allFeesDates_q);
        $result = '';
        $csvData = '';

        //existing report download
        if (wf_CheckGet(array('downloadfeereport'))) {
            $filenameToDownload = base64_decode($_GET['downloadfeereport']);
            zb_DownloadFile('exports/' . $filenameToDownload, 'docx');
        }

        //render fees list
        $cells = wf_TableCell(__('Month'));
        $rows = wf_TableRow($cells, 'row1');
        if (!empty($allFeesDates)) {
            foreach ($allFeesDates as $ia => $eachFee) {
                $feeLink = wf_Link(self::URL_REPORTS_MGMT . 'reportFees&showfees=' . $eachFee['yearmonth'], $eachFee['yearmonth'], false);
                $cells = wf_TableCell($feeLink);
                $rows .= wf_TableRow($cells, 'row3');
            }
        }
        $result .= wf_TableBody($rows, '30%', '0', 'sortable');
        show_window(__('By date'), $result);

        //render fees by selected month
        if (wf_CheckGet(array('showfees'))) {
            $feesSumm = 0;
            $feesCount = 0;
            $searchFees = mysql_real_escape_string($_GET['showfees']);
            $payments_q = "SELECT * from `ukv_payments` WHERE `date` LIKE '" . $searchFees . "%' AND `note` LIKE 'UKVFEE:%' ORDER BY `id` DESC";
            $allPayments = simple_queryall($payments_q);

            if (!empty($allPayments)) {
                $cells = wf_TableCell(__('ID'));
                $cells .= wf_TableCell(__('Date'));
                $cells .= wf_TableCell(__('Cash'));
                $cells .= wf_TableCell(__('Full address'));
                $cells .= wf_TableCell(__('Real Name'));
                $rowsf = wf_TableRow($cells, 'row1');

                foreach ($allPayments as $io => $eachPayment) {
                    if ($eachPayment['summ'] < 0) {
                        $userLink = wf_Link(self::URL_USERS_PROFILE . $eachPayment['userid'], web_profile_icon() . ' ', false);
                        $userAddress = $this->userGetFullAddress($eachPayment['userid']);
                        $userRealName = (empty($this->users[$eachPayment['userid']])) ? $eachPayment['userid'] . ' - ' . __('Unknown user') : $this->users[$eachPayment['userid']]['realname'];

                        $cells = wf_TableCell($eachPayment['id']);
                        $cells .= wf_TableCell($eachPayment['date']);
                        $cells .= wf_TableCell($eachPayment['summ']);
                        $cells .= wf_TableCell($userLink . $userAddress);
                        $cells .= wf_TableCell($userRealName);
                        $rowsf .= wf_TableRow($cells, 'row3');

                        $feesCount++;
                        $feesSumm = $feesSumm + $eachPayment['summ'];
                        $csvData .= $eachPayment['id'] . ';' . $eachPayment['date'] . ';' . $eachPayment['summ'] . ';' . $userAddress . ';' . $userRealName . "\r" . "\n";
                    }
                }

                //saving downloadable report
                $csvSaveName = $searchFees . '_ukvfeesreport.csv';
                $csvData = iconv('utf-8', 'windows-1251', $csvData);
                file_put_contents('exports/' . $csvSaveName, $csvData);
                $downloadLink = wf_Link(self::URL_REPORTS_MGMT . 'reportFees&downloadfeereport=' . base64_encode($csvSaveName), wf_img('skins/excel.gif', __('Download')), false);

                $result = wf_tag('strong') . __('Count') . ': ' . $feesCount;
                $result .= wf_tag('br');
                $result .= __('Money') . ': ' . $feesSumm;
                $result .= wf_tag('strong', true);
                $result .= wf_TableBody($rowsf, '100%', '0', 'sortable');

                show_window(__('Money fees') . ' ' . $searchFees . ' ' . $downloadLink, $result);
            }
        }
    }

    /**
     * renders streets report
     * 
     * @return void
     */
    public function reportStreets() {
        global $ubillingConfig;

        $withAddress = $ubillingConfig->getAlterParam('UKV_STREET_REP_BUILD_SEL');
        $ukvCities = array();
        $ukvStreets = array();
        $ukvBuilds = array('' => '-');

        //loads cities, streets and builds occupied by UKV users
        $ukvCities_q = "SELECT DISTINCT `city` from `ukv_users` ORDER BY `city` ASC";
        $ukvCitiesRaw = simple_queryall($ukvCities_q);
        if (!empty($ukvCitiesRaw)) {
            foreach ($ukvCitiesRaw as $ieuc => $euc) {
                $ukvCities[$euc['city']] = $euc['city'];
            }
        }

        $ukvStreets_q = "SELECT DISTINCT `street` from `ukv_users` ORDER BY `street` ASC";
        $ukvStreetsRaw = simple_queryall($ukvStreets_q);
        if (!empty($ukvStreetsRaw)) {
            foreach ($ukvStreetsRaw as $ieus => $eus) {
                $ukvStreets[$eus['street']] = $eus['street'];
            }
        }

        if ($withAddress) {
            $ukvBuilds_q = "SELECT `street`.`streetname`, `build`.`buildnum` FROM `street` RIGHT JOIN `build` ON `build`.`streetid` = `street`.`id` ORDER BY `buildnum`;";
            $ukvBuildsRaw = simple_queryall($ukvBuilds_q);

            if (!empty($ukvBuildsRaw)) {
                foreach ($ukvBuildsRaw as $io => $each) {
                    $ukvBuilds[trim($each['streetname']) . trim($each['buildnum'])] = trim($each['buildnum']);
                }
            }
        }

        //main codepart
        $citySelected = (wf_CheckPost(array('streetreportcity'))) ? $_POST['streetreportcity'] : '';
        $streetSelected = (wf_CheckPost(array('streetreportstreet'))) ? $_POST['streetreportstreet'] : '';
        $buildSelected = (wf_CheckPost(array('streetreportbuild'))) ? $_POST['streetreportbuild'] : '';

        $inputs = wf_Selector('streetreportcity', $ukvCities, __('City'), $citySelected, false);
        $inputs .= wf_Selector('streetreportstreet', $ukvStreets, __('Street'), $streetSelected, false, '', 'ReportStreetsSel');

        if ($withAddress) {
            $inputs .= wf_Selector('streetreportbuild', $ukvBuilds, __('Build'), $buildSelected, false, '', 'ReportBuildsSel');
            $inputs .= wf_HiddenInput('printthemall', base64_encode(json_encode($ukvBuilds)), 'TmpBuildsAll');

            $inputs .= wf_tag('script', false, '', 'type="text/javascript"');
            $inputs .= '$(document).ready(function() {                        
                        $(\'#ReportStreetsSel\').change(function(evt) {
                            var keyword = $(this).val();             
                            var source = JSON.parse(atob($(\'#TmpBuildsAll\').val()));
                            
                            filterBuildsSelect(keyword, source);
                        });
                        
                        function filterBuildsSelect(search_keyword, search_array) {
                            var newselect = $("<select id=\"ReportBuildsSel\" name=\"streetreportbuild\" />");
                            
                            $("<option />", {value: \'\', text: \'-\'}).appendTo(newselect);
                            
                            if (search_keyword.length > 0 && search_keyword.trim() !== "-") {
                                for (var key in search_array) {
                                    if ( key.toLowerCase() == search_keyword.toLowerCase() + search_array[key] && key.trim() !== "" ) {                                       
                                        $("<option />", {value: key, text: search_array[key]}).appendTo(newselect);
                                    }  
                                }
                            }
                            
                            $(\'#ReportBuildsSel\').replaceWith(newselect);
                        }
                        
                        var buildSelected = $(\'#ReportBuildsSel\').val()
                        var keyword = $(\'#ReportStreetsSel\').val();
                        var source = JSON.parse(atob($(\'#TmpBuildsAll\').val()));
                            
                        filterBuildsSelect(keyword, source);
                        $(\'#ReportBuildsSel\').val(buildSelected);
                   });
                  ';
            $inputs .= wf_tag('script', true);
        }
        $inputs .= wf_Submit(__('Show'));
        $form = wf_Form('', 'POST', $inputs, 'glamour');

        show_window(__('Streets report'), $form);

        if ((wf_CheckPost(array('streetreportcity', 'streetreportstreet'))) or (wf_CheckGet(array('rc', 'rs')))) {

            //set form data
            if (wf_CheckPost(array('streetreportcity', 'streetreportstreet'))) {
                $citySearch = $_POST['streetreportcity'];
                $streetSearch = $_POST['streetreportstreet'];
                $buildSearch = (wf_CheckPost(array('streetreportbuild'))) ? str_ireplace($streetSearch, '', $_POST['streetreportbuild']) : '';
            }

            //or printable report
            if (wf_CheckGet(array('rc', 'rs'))) {
                $citySearch = $_GET['rc'];
                $streetSearch = $_GET['rs'];
                $buildSearch = (wf_CheckGet(array('rb'))) ? $_GET['rb'] : '';
            }

            if (!empty($this->users)) {
                $counter = 0;

                $cells = wf_TableCell(__('Contract'), '10%');
                $cells .= wf_TableCell(__('Full address'), '31%');
                $cells .= wf_TableCell(__('Real Name'), '25%');
                $cells .= wf_TableCell(__('Tariff'), '15%');
                $cells .= wf_TableCell(__('Cash'), '7%');
                $cells .= wf_TableCell(__('Seal'), '5%');
                $cells .= wf_TableCell(__('Status'), '7%');
                $rows = wf_TableRow($cells, 'row1');

                foreach ($this->users as $io => $eachUser) {
                    if (($eachUser['city'] == $citySearch) and ($eachUser['street'] == $streetSearch) and (empty($buildSearch) ? true : $eachUser['build'] == $buildSearch)) {
                        $cells = wf_TableCell($eachUser['contract']);
                        $fullAddress = $this->userGetFullAddress($eachUser['id']);
                        $profileLink = wf_Link(self::URL_USERS_PROFILE . $eachUser['id'], web_profile_icon() . ' ', false, '');
                        $cells .= wf_TableCell($profileLink . $fullAddress);
                        $cells .= wf_TableCell($eachUser['realname']);
                        $cells .= wf_TableCell(@$this->tariffs[$eachUser['tariffid']]['tariffname']);
                        $cells .= wf_TableCell($eachUser['cash']);
                        $cells .= wf_TableCell($eachUser['cableseal']);
                        $cells .= wf_TableCell(web_bool_led($eachUser['active'], true));
                        $rows .= wf_TableRow($cells, 'row3');
                        $counter++;
                    }
                }

                $result = wf_TableBody($rows, '100%', '0', 'sortable');
                $result .= __('Total') . ': ' . $counter;

                $buildLinkPart = empty($buildSearch) ? '' : '&rb=' . $buildSearch;
                $buildCaptPart = empty($buildSearch) ? ' ' : ' / ' . $buildSearch . ' ';

                if (wf_CheckGet(array('printable'))) {
                    $this->reportPrintable($citySearch . ' / ' . $streetSearch . $buildCaptPart, $result);
                } else {
                    $printlink = wf_Link(self::URL_REPORTS_MGMT . 'reportStreets&rc=' . $citySearch . '&rs=' . $streetSearch . $buildLinkPart . '&printable=true', wf_img('skins/icon_print.png', __('Print')), false);
                    show_window($citySearch . ' / ' . $streetSearch . $buildCaptPart . $printlink, $result);
                }
            } else {
                show_window(__('Result'), __('Any users found'));
            }
        }
    }

    /**
     * Renders users stats with assigned internet account
     * 
     * @return string
     */
    protected function renderInetAssignStats() {
        $result = '';
        $count = 0;
        if (!empty($this->users)) {
            foreach ($this->users as $io => $each) {
                if (!empty($each['inetlogin'])) {
                    $count++;
                }
            }
        }
        $result = $this->messages->getStyledMessage(__('Users which already have associated internet account') . ': ' . wf_tag('b') . $count . wf_tag('b', true), 'info');
        return ($result);
    }

    /**
     * Renders complex users assign forms or something like that.
     * 
     * @return void
     */
    public function reportComplexAssign() {
        $nologinUsers = array();
        $ukvContracts = array();
        $inetContracts = array();
        $contractCfId = '';
        $result = '';

        //updating inet login if required
        if (wf_CheckPost(array('assignComplexLogin', 'assignComplexUkvId'))) {
            $updateUserId = vf($_POST['assignComplexUkvId'], 3);
            $updateInetLogin = $_POST['assignComplexLogin'];
            if ($this->users[$updateUserId]['inetlogin'] != $updateInetLogin) {
                simple_update_field('ukv_users', 'inetlogin', $updateInetLogin, "WHERE `id`='" . $updateUserId . "';");
                log_register('UKV USER ((' . $updateUserId . ')) ASSIGN INETLOGIN (' . $updateInetLogin . ')');
                rcms_redirect(self::URL_REPORTS_MGMT . 'reportComplexAssign');
            }
        }

        $allInetUsers = zb_UserGetAllStargazerDataAssoc();
        $allAddress = zb_AddressGetFulladdresslistCached();
        $allRealNames = zb_UserGetAllRealnames();

        //preparing ukv users
        if (!empty($this->users)) {
            foreach ($this->users as $io => $each) {
                if (empty($each['inetlogin'])) {
                    $nologinUsers[$each['id']] = $each;
                    $ukvContracts[$each['contract']] = $each['id'];
                }
            }
        }
        //getting complex contract CF id
        if (!empty($this->altCfg['COMPLEX_CFIDS'])) {
            $cfDataRaw = $this->altCfg['COMPLEX_CFIDS'];
            $cfData = explode(',', $cfDataRaw);
            $contractCfId = (isset($cfData[0])) ? vf($cfData[0], 3) : '';
        }

        //prepare cf logins=>contract pairs
        if (!empty($contractCfId)) {
            $query = "SELECT `login`,`content` from `cfitems` WHERE `typeid`='" . $contractCfId . "' AND `content` IS NOT NULL;";
            $rawCfs = simple_queryall($query);
            if (!empty($rawCfs)) {
                foreach ($rawCfs as $io => $each) {
                    $inetContracts[$each['login']] = $each['content'];
                }
            }
        }

        //rendering main form
        if (!empty($inetContracts)) {
            $cells = wf_TableCell(__('Full address'));
            $cells .= wf_TableCell(__('Real Name'));
            $cells .= wf_TableCell(__('Tariff'));
            $cells .= wf_TableCell(__('Contract'));
            $cells .= wf_TableCell(__('Login'));
            $cells .= wf_TableCell(__('Full address'));
            $cells .= wf_TableCell(__('Real Name'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');

            foreach ($inetContracts as $login => $contract) {
                if (isset($allInetUsers[$login])) {
                    if (!empty($contract)) {
                        @$ukvUserId = $ukvContracts[$contract];
                        if (!empty($ukvUserId)) {
                            if (isset($nologinUsers[$ukvUserId])) {
                                $ukvRealname = @$this->users[$ukvUserId]['realname'];
                                $inetRealname = @$allRealNames[$login];
                                $ukvAddress = $this->userGetFullAddress($ukvUserId);
                                $inetAddress = @$allAddress[$login];

                                $catvLink = wf_link(self::URL_USERS_PROFILE . $ukvUserId, web_profile_icon() . ' ' . $ukvAddress);
                                $cells = wf_TableCell($catvLink);
                                $cells .= wf_TableCell($ukvRealname);
                                $cells .= wf_TableCell(@$this->tariffs[$this->users[$ukvUserId]['tariffid']]['tariffname']);
                                $cells .= wf_TableCell($contract);
                                $profileLink = wf_Link('?module=userprofile&username=' . $login, web_profile_icon() . ' ' . $login, false);
                                $cells .= wf_TableCell($profileLink);
                                $cells .= wf_TableCell($inetAddress);
                                $cells .= wf_TableCell($inetRealname);
                                $assignInputs = wf_HiddenInput('assignComplexLogin', $login);
                                $assignInputs .= wf_HiddenInput('assignComplexUkvId', $ukvUserId);
                                $assignInputs .= wf_Submit(__('Assign'));
                                $assignContols = wf_Form('', 'POST', $assignInputs, '');
                                $cells .= wf_TableCell($assignContols);

                                $rowclass = 'row3';
                                //coloring results
                                if ((!empty($ukvRealname)) and (!empty($inetRealname))) {
                                    $ukvNameTmp = explode(' ', $ukvRealname);
                                    $inetNameTmp = explode(' ', $inetRealname);

                                    if (@$ukvNameTmp[0] == @$inetNameTmp[0]) {
                                        $rowclass = 'ukvassignnamerow';
                                    }

                                    if ((!empty($inetAddress)) and (!empty($ukvAddress))) {
                                        if (($inetAddress == $ukvAddress) and (@$ukvNameTmp[0] == @$inetNameTmp[0])) {
                                            $rowclass = 'ukvassignaddrrow';
                                        }
                                    }
                                }


                                $rows .= wf_TableRow($cells, $rowclass);
                            }
                        }
                    }
                }
            }

            $result .= $this->renderInetAssignStats() . wf_tag('br');
            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
            show_window(__('Assign UKV users to complex profiles'), $result);
        }
    }

    /**
     * Renders report that should be complex users
     * 
     * @return void
     */
    public function reportShouldbeComplex() {
        $complexCFids = $this->altCfg['COMPLEX_CFIDS'];
        $complexTariffs = array();
        $complexEnabledService = array();
        $userTariffs = array();
        $result = array();

        $reportData = '';
        if (!empty($complexCFids)) {
            $complexCFids = explode(',', $complexCFids);
            $cfitemTypeId = $complexCFids[0];
            $cfitemComplexEnabled = $complexCFids[1];

            $complexTariffId = $this->altCfg['UKV_COMPLEX_TARIFFID'];
            $complexTariffMasks = $this->altCfg['COMPLEX_MASKS'];
            if (!empty($complexTariffMasks)) {
                $complexTariffMasks = explode(',', $complexTariffMasks);
                if (!empty($complexTariffMasks)) {
                    foreach ($complexTariffMasks as $io => $eachmask) {
                        $eachmask = trim($eachmask);
                        if (!empty($eachmask)) {
                            $complexTariffs[$eachmask] = $eachmask;
                        }
                    }
                }


                $tariff_q = "SELECT `login`,`Tariff`,`TariffChange` from `users`";
                $loginsRaw = simple_queryall($tariff_q);
                $allLogins = array();
                if (!empty($loginsRaw)) {
                    foreach ($loginsRaw as $io => $each) {

                        foreach ($complexTariffs as $ia => $eachComplexTariff) {
                            if ((ispos($each['Tariff'], $eachComplexTariff)) or (ispos($each['TariffChange'], $eachComplexTariff))) {
                                $allLogins[$each['login']] = $io;
                                $userTariffs[$each['login']] = $each['Tariff'];
                            }
                        }
                    }
                }


                //getting contracts
                $cf_q = "SELECT * from `cfitems` WHERE `typeid`='" . $cfitemTypeId . "' AND `content` != ''";
                $allCfs = simple_queryall($cf_q);
                $allContracts = array();

                if (!empty($allCfs)) {
                    foreach ($allCfs as $io => $each) {
                        if (isset($allLogins[$each['login']])) {
                            $allContracts[$each['content']] = $each['login'];
                        }
                    }
                }

                //getting complex service enabled flag
                $cf_q = "SELECT * from `cfitems` WHERE `typeid`='" . $cfitemComplexEnabled . "' AND `content` != ''";
                $allCfs = simple_queryall($cf_q);

                if (!empty($allCfs)) {
                    foreach ($allCfs as $io => $each) {
                        if (isset($allLogins[$each['login']])) {
                            $complexEnabledService[$each['login']] = $each['content'];
                        }
                    }
                }

                $allUkvUsers = array();
                if (!empty($this->users)) {
                    foreach ($this->users as $userid => $userdata) {
                        if ($userdata['tariffid'] != $complexTariffId) {
                            $allUkvUsers[$userdata['contract']] = $userdata;
                        }
                    }
                }

                if (!empty($allContracts)) {
                    foreach ($allContracts as $io => $each) {
                        if (isset($allUkvUsers[$io])) {
                            $result[$io] = $allUkvUsers[$io]['id'];
                        }
                    }
                }

                if (!empty($result)) {
                    $cells = wf_TableCell(__('Contract'));
                    $cells .= wf_TableCell(__('Internet') . ' ' . __('tariff'));
                    $cells .= wf_TableCell(__('Complex') . ' ' . __('Active'));
                    $cells .= wf_TableCell(__('Full address'));
                    $cells .= wf_TableCell(__('Real Name'));
                    $cells .= wf_TableCell(__('Tariff') . ' ' . __('UKV'));
                    $cells .= wf_TableCell(__('Cash'));
                    $cells .= wf_TableCell(__('Status'));
                    $rows = wf_TableRow($cells, 'row1');

                    foreach ($result as $userContract => $userId) {
                        $userLogin = (isset($allContracts[$userContract])) ? $allContracts[$userContract] : '';
                        $userTariff = (isset($userTariffs[$userLogin])) ? $userTariffs[$userLogin] : __('No');
                        if (!empty($userLogin)) {
                            if (isset($complexEnabledService[$userLogin])) {
                                if ($complexEnabledService[$userLogin]) {
                                    $complexFlag = true;
                                } else {
                                    $complexFlag = false;
                                }
                            } else {
                                $complexFlag = false;
                            }
                        }

                        if (($this->users[$userId]['active']) or ($complexFlag)) {
                            $cells = wf_TableCell(wf_Link(self::URL_USERS_PROFILE . $userId, web_profile_icon(__('Profile') . ' ' . __('UKV'))) . ' ' . $userContract);
                            $cells .= wf_TableCell($userTariff);

                            $cells .= wf_TableCell(web_bool_led($complexFlag));
                            $cells .= wf_TableCell(wf_Link('?module=userprofile&username=' . $userLogin, web_profile_icon(__('Profile') . ' ' . __('Internet'))) . ' ' . $this->userGetFullAddress($userId));
                            $cells .= wf_TableCell($this->users[$userId]['realname']);
                            $cells .= wf_TableCell($this->tariffs[$this->users[$userId]['tariffid']]['tariffname']);
                            $cells .= wf_TableCell($this->users[$userid]['cash']);
                            $cells .= wf_TableCell(web_bool_led($this->users[$userId]['active']));
                            $rows .= wf_TableRow($cells, 'row3');
                        }
                    }

                    $reportData = wf_TableBody($rows, '100%', 0, 'sortable');
                    show_window(__('Users which should be complex in UKV'), $reportData);
                } else {
                    show_info(__('Nothing found'));
                }
            }
        } else {
            show_error(__('You missed an important option'));
        }
    }

    /**
     * Renders report that should not be complex users
     * 
     * @return void
     */
    public function reportShouldNotbeComplex() {
        $complexCFids = $this->altCfg['COMPLEX_CFIDS'];
        $complexTariffs = array();
        $complexEnabledService = array();
        $userTariffs = array();
        $result = array();

        $reportData = '';
        if (!empty($complexCFids)) {
            $complexCFids = explode(',', $complexCFids);
            $cfitemTypeId = $complexCFids[0];
            $cfitemComplexEnabled = $complexCFids[1];

            $complexTariffId = $this->altCfg['UKV_COMPLEX_TARIFFID'];
            $complexTariffMasks = $this->altCfg['COMPLEX_MASKS'];
            if (!empty($complexTariffMasks)) {
                $complexTariffMasks = explode(',', $complexTariffMasks);
                if (!empty($complexTariffMasks)) {
                    foreach ($complexTariffMasks as $io => $eachmask) {
                        $eachmask = trim($eachmask);
                        if (!empty($eachmask)) {
                            $complexTariffs[$eachmask] = $eachmask;
                        }
                    }
                }


                $tariff_q = "SELECT `login`,`Tariff`,`TariffChange` from `users`";
                $loginsRaw = simple_queryall($tariff_q);
                $allLogins = array();

                if (!empty($loginsRaw)) {
                    foreach ($loginsRaw as $ix => $each) {
                        $allLogins[$each['login']] = $each;
                        $userTariffs[$each['login']] = $each['Tariff'];
                    }
                }

                if (!empty($allLogins)) {
                    foreach ($allLogins as $io => $each) {
                        foreach ($complexTariffs as $ia => $eachComplexTariff) {
                            if ((ispos($each['Tariff'], $eachComplexTariff)) or (ispos($each['TariffChange'], $eachComplexTariff))) {
                                unset($allLogins[$each['login']]);
                            }
                        }
                    }
                }


                //getting contracts
                $cf_q = "SELECT * from `cfitems` WHERE `typeid`='" . $cfitemTypeId . "' AND `content` != ''";
                $allCfs = simple_queryall($cf_q);
                $allContracts = array();

                if (!empty($allCfs)) {
                    foreach ($allCfs as $io => $each) {
                        if (isset($allLogins[$each['login']])) {
                            $allContracts[$each['content']] = $each['login'];
                        }
                    }
                }

                //getting complex service enabled flag
                $cf_q = "SELECT * from `cfitems` WHERE `typeid`='" . $cfitemComplexEnabled . "' AND `content` != ''";
                $allCfs = simple_queryall($cf_q);

                if (!empty($allCfs)) {
                    foreach ($allCfs as $io => $each) {
                        if (isset($allLogins[$each['login']])) {
                            $complexEnabledService[$each['login']] = $each['content'];
                        }
                    }
                }

                $allUkvUsers = array();
                if (!empty($this->users)) {
                    foreach ($this->users as $userid => $userdata) {
                        if ($userdata['tariffid'] == $complexTariffId) {
                            $allUkvUsers[$userdata['contract']] = $userdata;
                        }
                    }
                }

                if (!empty($allContracts)) {
                    foreach ($allContracts as $io => $each) {
                        if (isset($allUkvUsers[$io])) {
                            if (isset($allLogins[$each])) {
                                $result[$io] = $allUkvUsers[$io]['id'];
                            }
                        }
                    }
                }

                if (!empty($result)) {
                    $cells = wf_TableCell(__('Contract'));
                    $cells .= wf_TableCell(__('Internet') . ' ' . __('tariff'));
                    $cells .= wf_TableCell(__('Complex') . ' ' . __('Active'));
                    $cells .= wf_TableCell(__('Full address'));
                    $cells .= wf_TableCell(__('Real Name'));
                    $cells .= wf_TableCell(__('Tariff') . ' ' . __('UKV'));
                    $cells .= wf_TableCell(__('Cash'));
                    $cells .= wf_TableCell(__('Status'));
                    $rows = wf_TableRow($cells, 'row1');

                    foreach ($result as $userContract => $userId) {
                        $userLogin = (isset($allContracts[$userContract])) ? $allContracts[$userContract] : '';
                        $userTariff = (isset($userTariffs[$userLogin])) ? $userTariffs[$userLogin] : __('No');
                        if (!empty($userLogin)) {
                            if (isset($complexEnabledService[$userLogin])) {
                                if ($complexEnabledService[$userLogin]) {
                                    $complexFlag = true;
                                } else {
                                    $complexFlag = false;
                                }
                            } else {
                                $complexFlag = false;
                            }
                        }

                        $cells = wf_TableCell(wf_Link(self::URL_USERS_PROFILE . $userId, web_profile_icon(__('Profile') . ' ' . __('UKV'))) . ' ' . $userContract);
                        $cells .= wf_TableCell($userTariff);

                        $cells .= wf_TableCell(web_bool_led($complexFlag));
                        $cells .= wf_TableCell(wf_Link('?module=userprofile&username=' . $userLogin, web_profile_icon(__('Profile') . ' ' . __('Internet'))) . ' ' . $this->userGetFullAddress($userId));
                        $cells .= wf_TableCell($this->users[$userId]['realname']);
                        $cells .= wf_TableCell($this->tariffs[$this->users[$userId]['tariffid']]['tariffname']);
                        $cells .= wf_TableCell($this->users[$userid]['cash']);
                        $cells .= wf_TableCell(web_bool_led($this->users[$userId]['active']));
                        $rows .= wf_TableRow($cells, 'row3');
                    }

                    $reportData = wf_TableBody($rows, '100%', 0, 'sortable');
                    show_window(__('Users which should not be complex in UKV'), $reportData);
                } else {
                    show_info(__('Nothing found'));
                }
            }
        } else {
            show_error(__('You missed an important option'));
        }
    }

    /**
     * Renders UKV users integrity report
     * 
     * @return void
     */
    public function reportIntegrity() {
        $result = '';
        $addressTmp = array();
        $contractsTmp = array();
        $problemUsers = array();
        $problemComplex = array();

        $problemTypes = array(
            'addressdup' => __('Address duplicate'),
            'contractdup' => __('Contract duplicate'),
            'addressempty' => __('Empty address'),
            'contractempty' => __('Empty contract'),
            'notariff' => __('No tariff'),
            'noukvuser' => __('Missing registered UKV user with complex tariff'),
            'activediff' => __('Account activity is different')
        );

        if (!empty($this->users)) {

            if ($this->altCfg['COMPLEX_ENABLED']) {
                $complexFlag = true;
                $inetAddress = zb_AddressGetFulladdresslistCached();
                $inetRealnames = zb_UserGetAllRealnames();
                $complexCfIds = $this->altCfg['COMPLEX_CFIDS'];
                $complexCfIds = explode(',', $complexCfIds);
                $complexContractCf = $complexCfIds[0];
                $complexActiveCf = $complexCfIds[1];
                $complexMasksTmp = $this->altCfg['COMPLEX_MASKS'];
                $complexMasksTmp = explode(',', $complexMasksTmp);
                $complexContracts = array();
                $complexActive = array();
                $inetCableseals = array();
                $contractsActivity = array();

                if (!empty($complexMasksTmp)) {
                    foreach ($complexMasksTmp as $io => $each) {
                        $complexMasks[$each] = $each;
                    }
                }
                $allComplexUsers = array(); //login=>userdata
                if (!empty($complexMasks)) {
                    $allUsersRaw = zb_UserGetAllStargazerDataAssoc();
                    if (!empty($allUsersRaw)) {
                        foreach ($allUsersRaw as $userLogin => $eachUser) {
                            foreach ($complexMasks as $ia => $eachComplexMask) {
                                if (ispos($eachUser['Tariff'], $eachComplexMask)) {
                                    $allComplexUsers[$userLogin] = $eachUser;
                                }
                            }
                        }
                    }
                }

                //getting complex active and contract fields
                $query_complex = "SELECT * from `cfitems`";
                $cfRaw = simple_queryall($query_complex);
                if (!empty($cfRaw)) {
                    foreach ($cfRaw as $io => $eachCf) {
                        if ($eachCf['typeid'] == $complexContractCf) {
                            $complexContracts[$eachCf['login']] = $eachCf['content'];
                        }

                        if ($eachCf['typeid'] == $complexActiveCf) {
                            $complexActive[$eachCf['login']] = $eachCf['content'];
                        }
                    }
                }
            } else {
                $complexFlag = false;
            }


            foreach ($this->users as $io => $eachUser) {
                //unique address
                $userAddress = $this->userGetFullAddress($eachUser['id']);
                if (!empty($userAddress)) {
                    if (isset($addressTmp[$userAddress])) {
                        $problemUsers[$eachUser['id']] = $eachUser;
                        $problemUsers[$eachUser['id']]['type'] = 'addressdup';
                        //first occurence
                        $firstUserId = $addressTmp[$userAddress];
                        $problemUsers[$firstUserId] = $this->users[$firstUserId];
                        $problemUsers[$firstUserId]['type'] = 'addressdup';
                    } else {
                        $addressTmp[$userAddress] = $eachUser['id'];
                    }
                }

                //unique contracts
                if (!empty($eachUser['contract'])) {
                    if (isset($contractsTmp[$eachUser['contract']])) {
                        $problemUsers[$eachUser['id']] = $eachUser;
                        $problemUsers[$eachUser['id']]['type'] = 'contractdup';
                        //first occurence
                        $firstUserId = $contractsTmp[$eachUser['contract']];
                        $problemUsers[$firstUserId] = $this->users[$firstUserId];
                        $problemUsers[$firstUserId]['type'] = 'contractdup';
                    } else {
                        $contractsTmp[$eachUser['contract']] = $eachUser['id'];
                    }
                }
                //empty contract
                if (empty($userAddress)) {
                    $problemUsers[$eachUser['id']] = $eachUser;
                    $problemUsers[$eachUser['id']]['type'] = 'addressempty';
                }

                //empty contract
                if (empty($eachUser['contract'])) {
                    $problemUsers[$eachUser['id']] = $eachUser;
                    $problemUsers[$eachUser['id']]['type'] = 'contractempty';
                }

                //empty tariff
                if (empty($eachUser['tariffid'])) {
                    $problemUsers[$eachUser['id']] = $eachUser;
                    $problemUsers[$eachUser['id']]['type'] = 'notariff';
                }

                //contracts actitivy temp fill
                if (!empty($eachUser['contract'])) {
                    $contractsActivity[$eachUser['contract']] = $eachUser['active'];
                }
            }

            //complex processing
            if ($complexFlag) {
                $userStreets = zb_AddressGetStreetUsers();
                if (!empty($allComplexUsers)) {
                    //No UKV user detected
                    foreach ($allComplexUsers as $io => $eachComplexUser) {
                        if (!isset($complexContracts[$eachComplexUser['login']])) {
                            $problemComplex[$eachComplexUser['login']]['login'] = $eachComplexUser['login'];
                            $problemComplex[$eachComplexUser['login']]['type'] = 'noukvuser';
                        }
                    }
                    //Activity state is different
                    foreach ($allComplexUsers as $io => $eachComplexUser) {
                        if (isset($complexContracts[$eachComplexUser['login']])) {
                            if (isset($complexActive[$eachComplexUser['login']])) {
                                $cpActFlag = $complexActive[$eachComplexUser['login']];
                            } else {
                                $cpActFlag = 0;
                            }

                            if (isset($contractsActivity[$complexContracts[$eachComplexUser['login']]])) {
                                $ukActFlag = $contractsActivity[$complexContracts[$eachComplexUser['login']]];
                            } else {
                                $ukActFlag = 0;
                            }
                            if ($cpActFlag != $ukActFlag) {
                                $problemComplex[$eachComplexUser['login']]['login'] = $eachComplexUser['login'];
                                $problemComplex[$eachComplexUser['login']]['type'] = 'activediff';
                            }
                        }
                    }

                    //UKV user contract missing
                    foreach ($allComplexUsers as $io => $eachComplexUser) {
                        if (!isset($this->contracts[@$complexContracts[$eachComplexUser['login']]])) {
                            $problemComplex[$eachComplexUser['login']]['login'] = $eachComplexUser['login'];
                            $problemComplex[$eachComplexUser['login']]['type'] = 'noukvuser';
                        }
                    }
                }
            }

            if (!empty($problemUsers)) {
                $cells = wf_TableCell(__('ID'));
                $cells .= wf_TableCell(__('Contract'));
                $cells .= wf_TableCell(__('Full address'));
                $cells .= wf_TableCell(__('Real Name'));
                $cells .= wf_TableCell(__('Tariff'));
                $cells .= wf_TableCell(__('Cash'));
                $cells .= wf_TableCell(__('Active'));
                $cells .= wf_TableCell(__('Type'));
                $rows = wf_TableRow($cells, 'row1');

                foreach ($problemUsers as $io => $each) {
                    $cells = wf_TableCell(wf_Link(self::URL_USERS_PROFILE . $each['id'], web_profile_icon() . ' ' . $each['id']));
                    $cells .= wf_TableCell($each['contract']);
                    $cells .= wf_TableCell($this->userGetFullAddress($each['id']));
                    $cells .= wf_TableCell($each['realname']);
                    $cells .= wf_TableCell(@$this->tariffs[$each['tariffid']]['tariffname']);
                    $cells .= wf_TableCell($each['cash']);
                    $cells .= wf_TableCell(web_bool_led($each['active']));
                    $cells .= wf_TableCell(@$problemTypes[$each['type']]);
                    $rows .= wf_TableRow($cells, 'row3');
                }

                $result .= wf_TableBody($rows, '100%', 0, 'sortable');
            } else {
                $result .= $this->messages->getStyledMessage(__('Nothing found'), 'success');
            }

            if ($complexFlag) {
                if (!empty($problemComplex)) {
                    $cells = wf_TableCell(__('Login'));
                    $cells .= wf_TableCell(__('Full address'));
                    $cells .= wf_TableCell(__('Real Name'));
                    $cells .= wf_TableCell(__('Tariff'));
                    $cells .= wf_TableCell(__('Cash'));
                    $cells .= wf_TableCell(__('Active'));
                    $cells .= wf_TableCell(__('Type'));
                    $rows = wf_TableRow($cells, 'row1');

                    foreach ($problemComplex as $io => $each) {
                        $cells = wf_TableCell(wf_Link(self::URL_INET_USER_PROFILE . $each['login'], web_profile_icon() . ' ' . $each['login']));
                        $cells .= wf_TableCell(@$inetAddress[$each['login']]);
                        $cells .= wf_TableCell(@$inetRealnames[$each['login']]);
                        $cells .= wf_TableCell(@$allUsersRaw[$each['login']]['Tariff']);
                        $cells .= wf_TableCell(@$allUsersRaw[$each['login']]['Cash']);
                        $activityLabel = web_bool_led(@$complexActive[$each['login']]);

                        if (@isset($contractsActivity[$complexContracts[$each['login']]])) {
                            $activityLabel .= ' ' . web_bool_led(@$contractsActivity[$complexContracts[$each['login']]]);
                        }
                        $cells .= wf_TableCell($activityLabel);
                        $cells .= wf_TableCell(@$problemTypes[$each['type']]);
                        $rows .= wf_TableRow($cells, 'row3');
                    }
                    $result .= wf_tag('br');
                    $result .= wf_TableBody($rows, '100%', 0, 'sortable');
                }
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Any users found'), 'warning');
        }
        show_window(__('Integrity control'), $result);
    }

    public function getUbMessagesInstance() {
        return ($this->messages);
    }
}
