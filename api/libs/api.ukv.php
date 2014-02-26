<?php

/*
 * UKV cable TV accounting implementation
 */

class UkvSystem {
    
    protected $tariffs = array();
    protected $users = array();
    protected $cities = array('' => '-');
    protected $streets = array('' => '-');
    protected $cashtypes = array();
    protected $month = array();
    protected $contracts = array();
    protected $bankstarecords = array();

    //static routing URLs

    const URL_TARIFFS_MGMT = '?module=ukv&tariffs=true'; //tariffs management
    const URL_USERS_MGMT = '?module=ukv&users=true'; //users management
    const URL_USERS_LIST = '?module=ukv&users=true&userslist=true'; //users list route
    const URL_USERS_PROFILE = '?module=ukv&users=true&showuser='; //user profile
    const URL_USERS_REGISTER = '?module=ukv&users=true&register=true'; //users registration route
    const URL_USERS_AJAX_SOURCE = '?module=ukv&ajax=true'; //ajax datasource for JQuery data tables
    const URL_INET_USER_PROFILE = '?module=userprofile&username='; //internet user profile
    const URTL_USERS_ANIHILATION = '?module=ukv&users=true&deleteuser='; // user extermination form
    const URL_BANKSTA_MGMT = '?module=ukv&banksta=true'; //bank statements processing url
    const URL_BANKSTA_PROCESSING = '?module=ukv&banksta=true&showhash='; // bank statement processing url
    const URL_BANKSTA_DETAILED = '?module=ukv&banksta=true&showdetailed='; //detailed banksta row display url
    const URL_REPORTS_LIST = '?module=ukv&reports=true&showreport=reportList'; //reports listing link
    const URL_REPORTS_MGMT = '?module=ukv&reports=true&showreport='; //reports listing link
    //registration options
    const REG_ACT = 1;
    const REG_CASH = 0;

    //misc options
    
    protected $debtLimit = 2; //debt limit in month count
    
    //bank statements options
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

    public function __construct() {
        $this->loadTariffs();
        $this->loadUsers();
        $this->loadCities();
        $this->loadStreets();
        $this->loadMonth();
        $this->loadDebtLimit();
    }

    /*
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

    /*
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

    /*
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

    /*
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

    /*
     * loads current month data into private props
     * 
     * @return void
     */

    protected function loadMonth() {
        $monthArr = months_array();
        $this->month['currentmonth'] = date("m");
        $this->month['currentyear'] = date("Y");
        ;
        foreach ($monthArr as $num => $each) {
            $this->month['names'][$num] = rcms_date_localise($each);
        }
    }
    
    /*
     * loads current debt limit from global config
     * 
     * @return void
     */
    function loadDebtLimit() {
        global $ubillingConfig;
        $altCfg=$ubillingConfig->getAlter();
        $this->debtLimit=$altCfg['UKV_MONTH_DEBTLIMIT'];
    }

    /*
     * creates new tariff into database
     * 
     * @param $name  tariff name
     * @param $price tariff price 
     * 
     * @return void
     */

    public function tariffCreate($name, $price) {
        $name = mysql_real_escape_string($name);
        $name = trim($name);
        $price = mysql_real_escape_string($price);
        $price = trim($price);
        if (!empty($name)) {
            $price=(empty($price)) ? 0 : $price;
            $query = "INSERT INTO `ukv_tariffs` (`id`, `tariffname`, `price`) VALUES (NULL, '" . $name . "', '" . $price . "');";
            nr_query($query);
            log_register("UKV TARIFF CREATE `" . $name . "` WITH PRICE `" . $price . "`");
        } else {
            throw new Exception(self::EX_TARIFF_FIELDS_EMPTY);
        }
    }

    /*
     * check is tariff protected/used by some users
     * 
     * @param @tariffid  existing tariff ID
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
            return(true);
        }
    }

    /*
     * deletes some existing tariff from database
     * 
     * @param $tariffid existing tariff ID
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

    /*
     * saves some tariff params into database
     * 
     * @param $tariffid    existing tariff ID
     * @param $tariffname  new name of the tariff
     * @param $price       new tariff price
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

    /*
     * returns tariff edit form 
     * 
     * @param $tariffid existing tariff id
     * 
     * @rerturn string
     */

    protected function tariffEditForm($tariffid) {
        $tariffid = vf($tariffid, 3);

        $inputs = wf_HiddenInput('edittariff', $tariffid);
        $inputs.= wf_TextInput('edittariffname', __('Tariff name'), $this->tariffs[$tariffid]['tariffname'], true, '20');
        $inputs.= wf_TextInput('edittariffprice', __('Tariff Fee'), $this->tariffs[$tariffid]['price'], true, '5');
        $inputs.= wf_Submit(__('Save'));
        $result = wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /*
     * returns tariff creation form
     * 
     * @return string
     */

    protected function tariffCreateForm() {
        $inputs = wf_HiddenInput('createtariff', 'true');
        $inputs.= wf_TextInput('createtariffname', __('Tariff name'), '', true, '20');
        $inputs.= wf_TextInput('createtariffprice', __('Tariff Fee'), '', true, '5');
        $inputs.= wf_Submit(__('Create new tariff'));
        $result = wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /*
     * renders CaTV tariffs list with some controls
     * 
     * @return void
     */

    public function renderTariffs() {

        $cells = wf_TableCell(__('ID'));
        $cells.= wf_TableCell(__('Tariff name'));
        $cells.= wf_TableCell(__('Tariff Fee'));
        $cells.= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($this->tariffs)) {
            foreach ($this->tariffs as $io => $each) {
                $cells = wf_TableCell($each['id']);
                $cells.= wf_TableCell($each['tariffname']);
                $cells.= wf_TableCell($each['price']);
                $actlinks = wf_JSAlert(self::URL_TARIFFS_MGMT . '&tariffdelete=' . $each['id'], web_delete_icon(), __('Removing this may lead to irreparable results'));
                $actlinks.= wf_modal(web_edit_icon(), __('Edit') . ' ' . $each['tariffname'], $this->tariffEditForm($each['id']), '', '400', '200');
                $cells.= wf_TableCell($actlinks, '', '', $customkey = 'sorttable_customkey="0"'); //need this to keep table sortable
                $rows.= wf_TableRow($cells, 'row3');
            }
        }

        $result = wf_TableBody($rows, '100%', '0', 'sortable');
        $result.= wf_modal(wf_img('skins/plus.png', __('Create new tariff')), __('Create new tariff'), $this->tariffCreateForm(), '', '400', '200');
        return ($result);
    }

    /*
     * returns module control panel
     * 
     * @return string
     */

    public function panel() {
        $result = wf_Link(self::URL_USERS_LIST, wf_img('skins/ukv/users.png') . ' ' . __('Users'), false, 'ubButton');
        $result.= wf_Link(self::URL_USERS_REGISTER, wf_img('skins/ukv/add.png') . ' ' . __('Users registration'), false, 'ubButton');
        $result.= wf_Link(self::URL_TARIFFS_MGMT, wf_img('skins/ukv/dollar.png') . ' ' . __('Tariffs'), false, 'ubButton');
        $result.= wf_Link(self::URL_BANKSTA_MGMT, wf_img('skins/ukv/bank.png') . ' ' . __('Bank statements'), false, 'ubButton');
        $result.= wf_Link(self::URL_REPORTS_LIST, wf_img('skins/ukv/report.png') . ' ' . __('Reports'), false, 'ubButton');
        return ($result);
    }

    /*
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

    /*
     * just sets user balance to specified value
     * 
     * @param $userid existing user id
     * @param $cash   cash value to set
     * 
     * @return void
     */

    protected function userSetCash($userid, $cash) {
        if (isset($this->users[$userid])) {
            simple_update_field('ukv_users', 'cash', $cash, "WHERE `id`='" . $userid . "';");
        }
    }

    /*
     * logs payment to database
     * 
     * 
     * @param $userid
     * @param $summ
     * @param $visible
     * @param $cashtypeid
     * @param $notes
     * 
     * @return void
     * 
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

    /*
     * External interface for private setCash method used in manual finance ops
     * 
     * @param $userid
     * @param $summ
     * @param $visible
     * @param $cashtypeid
     * @param $notes
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

        log_register('UKV BALANCEADD ((' . $userid . ')) ON ' . $summ);
    }

    /*
     * charges month fee for some user
     * 
     * @param $userid  existing user ID
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

    /*
     * logs fee charge fact to database
     * 
     * @return void
     */

    protected function feeChargeLog() {
        $curyearmonth = date("Y-m");
        $query = "INSERT INTO `ukv_fees` (`id`, `yearmonth`) VALUES (NULL, '" . $curyearmonth . "');";
        nr_query($query);
    }

    /*
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

    /*
     * public interface view for manual payments processing
     * 
     * @param $userid - existing user ID
     * 
     * @return string
     */

    public function userManualPaymentsForm($userid) {
        $userid = vf($userid, 3);
        $this->loadCashtypes();
        $inputs = '';
        $inputs.= wf_HiddenInput('manualpaymentprocessing', $userid);
        $inputs.= wf_TextInput('paymentsumm', __('New cash'), '', true, 5);
        $inputs.= wf_RadioInput('paymenttype', __('Add cash'), 'add', false, true);
        $inputs.= wf_RadioInput('paymenttype', __('Correct saldo'), 'correct', false, false);
        $inputs.= wf_RadioInput('paymenttype', __('Mock payment'), 'mock', false, false);
        $inputs.= wf_Selector('paymentcashtype', $this->cashtypes, __('Cash type'), '', true);
        $inputs.= wf_TextInput('paymentnotes', __('Payment notes'), '', true, '40');
        $inputs.= wf_tag('br');
        $inputs.= wf_Submit(__('Payment'));

        $result = wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /*
     * user deletion form
     * 
     * @param $userid existing user ID
     * 
     * @return string
     */

    public function userDeletionForm($userid) {
        $userid = vf($userid, 3);
        $inputs = __('Be careful, this module permanently deletes user and all data associated with it. Opportunities to raise from the dead no longer.') . ' <br>
               ' . __('To ensure that we have seen the seriousness of your intentions to enter the word сonfirm the field below.');
        $inputs.= wf_HiddenInput('userdeleteprocessing', $userid);
        $inputs.= wf_delimiter();
        $inputs.= wf_tag('input', false, '', 'type="text" name="deleteconfirmation" autocomplete="off"');
        $inputs.= wf_tag('br');
        $inputs.= wf_Submit(__('I really want to stop suffering User'));

        $result = wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /*
     * deletes some user from database
     * 
     * @param userid
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

    /*
     * Returns user registration form
     * 
     * @return string
     */

    public function userRegisterForm() {
        $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
        $inputs = '';
        $inputs = wf_HiddenInput('userregisterprocessing', 'true');
        $inputs.= wf_Selector('uregcity', $this->cities, __('City') . $sup, '', true);
        $inputs.= wf_Selector('uregstreet', $this->streets, __('Street') . $sup, '', true);
        $inputs.= wf_TextInput('uregbuild', __('Build') . $sup, '', true, '5');
        $inputs.= wf_TextInput('uregapt', __('Apartment'), '', true, '4');
        $inputs.= wf_delimiter();
        $inputs.=wf_Submit(__('Let register that user'));
        $result = wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /*
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

    /*
     * returns user edit form for some userid
     * 
     * @param $userid  existing user ID
     * 
     * @return string
     */

    protected function userEditForm($userid) {
        $userid = vf($userid, 3);
        if (isset($this->users[$userid])) {
            $switchArr = array('1' => __('Yes'), '0' => __('No'));
            $tariffArr = array();
            if (!empty($this->tariffs)) {
                foreach ($this->tariffs as $io => $each) {
                    $tariffArr[$each['id']] = $each['tariffname'];
                }
            }

            $userData = $this->users[$userid];

            $inputs = '';
            $inputs.= wf_HiddenInput('usereditprocessing', $userid);
            $inputs.= wf_tag('div', false, 'floatpanels');
            $inputs.= wf_tag('h3') . __('Full address') . wf_tag('h3', true);
            $inputs.= wf_Selector('ueditcity', $this->cities, __('City'), $userData['city'], true);
            $inputs.= wf_Selector('ueditstreet', $this->streets, __('Street'), $userData['street'], true);
            $inputs.= wf_TextInput('ueditbuild', __('Build'), $userData['build'], false, '5');
            $inputs.= wf_TextInput('ueditapt', __('Apartment'), $userData['apt'], true, '4');
            $inputs.= wf_tag('div', true);

            $inputs.=wf_tag('div', false, 'floatpanels');
            $inputs.= wf_tag('h3') . __('Contact info') . wf_tag('h3', true);
            $inputs.= wf_TextInput('ueditrealname', __('Real Name'), $userData['realname'], true, '30');
            $inputs.= wf_TextInput('ueditphone', __('Phone'), $userData['phone'], true, '20');
            $inputs.= wf_TextInput('ueditmobile', __('Mobile'), $userData['mobile'], true, '20');
            $inputs.= wf_tag('div', true);

            $inputs.=wf_tag('div', false, 'floatpanels');
            $inputs.= wf_tag('h3') . __('Services') . wf_tag('h3', true);
            $inputs.= wf_TextInput('ueditcontract', __('Contract'), $userData['contract'], true, '10');
            $inputs.= wf_Selector('uedittariff', $tariffArr, __('Tariff'), $userData['tariffid'], true);
            $inputs.= wf_Selector('ueditactive', $switchArr, __('Connected'), $userData['active'], true);
            $inputs.= wf_TextInput('ueditregdate', __('Contract date'), $userData['regdate'], true, '20');
            $inputs.= wf_TextInput('ueditinetlogin', __('Login'), $userData['inetlogin'], true, '20');
            $inputs.= wf_tag('div', true);


            $inputs.=wf_tag('div', false, 'floatpanels');
            $inputs.= wf_tag('h3') . __('Passport data') . wf_tag('h3', true);
            $inputs.= wf_TextInput('ueditpassnum', __('Passport number'), $userData['passnum'], true, '20');
            $inputs.= wf_TextInput('ueditpasswho', __('Issuing authority'), $userData['passwho'], true, '20');
            $inputs.= wf_DatePickerPreset('ueditpassdate', $userData['passdate'], true) . __('Date of issue') . wf_tag('br');
            $inputs.= wf_TextInput('ueditssn', __('SSN'), $userData['ssn'], true, '20');
            $inputs.= wf_TextInput('ueditpaddr', __('Registration address'), $userData['paddr'], true, '20');
            $inputs.= wf_tag('div', true);

            $inputs.=wf_tag('div', false, 'floatpanelswide');
            $inputs.= wf_TextInput('ueditnotes', __('Notes'), $userData['notes'], false, '60');
            $inputs.= wf_tag('div', true);
            $inputs.= wf_delimiter();
            $inputs.= wf_Submit(__('Save'));

            $result = wf_Form('', 'POST', $inputs, 'ukvusereditform');

            return ($result);
        }
    }

    /*
     * saves some user params into database
     * 
     * @return void
     */

    public function userSave() {
        if (wf_CheckPost(array('usereditprocessing'))) {
            $userId = vf($_POST['usereditprocessing']);
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
                simple_update_field($tablename, 'build', $_POST['ueditbuild'], $where);
                log_register('UKV USER ((' . $userId . ')) CHANGE BUILD `' . $_POST['ueditbuild'] . '`');
            }

            //saving apartment
            if ($this->users[$userId]['apt'] != $_POST['ueditapt']) {
                simple_update_field($tablename, 'apt', $_POST['ueditapt'], $where);
                log_register('UKV USER ((' . $userId . ')) CHANGE APT `' . $_POST['ueditapt'] . '`');
            }

            //saving realname
            if ($this->users[$userId]['realname'] != $_POST['ueditrealname']) {
                $newRealname = str_replace('"', '`', $_POST['ueditrealname']);
                $newRealname = str_replace("'", '`', $newRealname);
                simple_update_field($tablename, 'realname', $newRealname, $where);
                log_register('UKV USER ((' . $userId . ')) CHANGE REALNAME `' . $_POST['ueditrealname'] . '`');
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
                simple_update_field($tablename, 'contract', $newContract, $where);
                log_register('UKV USER ((' . $userId . ')) CHANGE CONTRACT `' . $newContract . '`');
            }

            //saving tariff
            if ($this->users[$userId]['tariffid'] != $_POST['uedittariff']) {
                simple_update_field($tablename, 'tariffid', $_POST['uedittariff'], $where);
                log_register('UKV USER ((' . $userId . ')) CHANGE TARIFF [' . $_POST['uedittariff'] . ']');
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
                log_register('UKV USER ((' . $userId . ')) CHANGE INETLOGIN `' . $_POST['ueditinetlogin'] . '`');
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

    /*
     * protected method using to save address data for newly registered user
     * 
     * @param $userId - existin new user ID
     * 
     * @return void
     */

    protected function userPostRegSave($userId) {
        $whereReg = "WHERE `id` = '" . $userId . "';";
        simple_update_field('ukv_users', 'city', $_POST['uregcity'], $whereReg);
        log_register('UKV USER ((' . $userId . ')) CHANGE CITY `' . $_POST['uregcity'] . '`');

        simple_update_field('ukv_users', 'street', $_POST['uregstreet'], $whereReg);
        log_register('UKV USER ((' . $userId . ')) CHANGE STREET `' . $_POST['uregstreet'] . '`');

        simple_update_field('ukv_users', 'build', $_POST['uregbuild'], $whereReg);
        log_register('UKV USER ((' . $userId . ')) CHANGE BUILD `' . $_POST['uregbuild'] . '`');

        $newApt = (!empty($_POST['uregapt'])) ? $_POST['uregapt'] : 0;
        simple_update_field('ukv_users', 'apt', $newApt, $whereReg);
        log_register('UKV USER ((' . $userId . ')) CHANGE APT `' . $newApt . '`');
    }

    /*
     * returns some existing user profile
     * 
     * @param $userid existing user`s ID
     * 
     * @return string
     */

    public function userProfile($userid) {
        global $ubillingConfig;
        $altcfg = $ubillingConfig->getAlter();
        $userid = vf($userid, 3);
        if (isset($this->users[$userid])) {
            $userData = $this->users[$userid];
            $rows = '';

            //zero apt numbers as private builds
            if ($altcfg['ZERO_TOLERANCE']) {
                $apt = ($userData['apt'] == 0) ? '' : '/' . $userData['apt'];
            } else {
                $apt = '/' . $userData['apt'];
            }


            $cells = wf_TableCell(__('Full address'), '20%', 'row2');
            $cells.= wf_TableCell($userData['city'] . ' ' . $userData['street'] . ' ' . $userData['build'] . $apt);
            $rows.= wf_TableRow($cells, 'row3');


            $cells = wf_TableCell(__('Real Name'), '20%', 'row2');
            $cells.= wf_TableCell($userData['realname']);
            $rows.= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Phone'), '20%', 'row2');
            $cells.= wf_TableCell($userData['phone']);
            $rows.= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Mobile'), '20%', 'row2');
            $cells.= wf_TableCell($userData['mobile']);
            $rows.= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(wf_tag('b') . __('Contract') . wf_tag('b', true), '20%', 'row2');
            $cells.= wf_TableCell(wf_tag('b') . $userData['contract'] . wf_tag('b', true));
            $rows.= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Tariff'), '20%', 'row2');
            $cells.= wf_TableCell(@$this->tariffs[$userData['tariffid']]['tariffname']);
            $rows.= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(wf_tag('b') . __('Cash') . wf_tag('b', true), '20%', 'row2');
            $cells.= wf_TableCell(wf_tag('b') . $userData['cash'] . wf_tag('b', true));
            $rows.= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Connected'), '20%', 'row2');
            $cells.= wf_TableCell(web_bool_led($userData['active']));
            $rows.= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('User contract date'), '20%', 'row2');
            $cells.= wf_TableCell($userData['regdate']);
            $rows.= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Internet account'), '20%', 'row2');
            $inetLink = (!empty($userData['inetlogin'])) ? wf_Link(self::URL_INET_USER_PROFILE . $userData['inetlogin'], web_profile_icon() . ' ' . $userData['inetlogin'], false, '') : '';
            $cells.= wf_TableCell($inetLink);
            $rows.= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Notes'), '20%', 'row2');
            $cells.= wf_TableCell($userData['notes']);
            $rows.= wf_TableRow($cells, 'row3');

            $profileData = wf_TableBody($rows, '100%', 0, '');

            $profilePlugins = wf_modal(wf_img('skins/icon_user_edit_big.gif', __('Edit user')), __('Edit user'), $this->userEditForm($userid), '', '900', '530');
            $profilePlugins.= wf_modal(wf_img('skins/icon_cash_big.gif', __('Cash')), __('Finance operations'), $this->userManualPaymentsForm($userid), '', '600', '250');
            $profilePlugins.= wf_modal(wf_img('skins/annihilation.gif', __('Deleting user')), __('Deleting user'), $this->userDeletionForm($userid), '', '800', '300');

            //main view construction
            $profilecells = wf_tag('td', false, '', 'valign="top"') . $profileData . wf_tag('td', true);
            $profilecells.= wf_tag('td', false, '', 'width="74" valign="top"') . $profilePlugins . wf_tag('td', true);
            $profilerows = wf_TableRow($profilecells);

            $result = wf_TableBody($profilerows, '100%', '0');
            $result.= $this->userPaymentsRender($userid);

            return ($result);
        } else {
            throw new Exception(self::EX_USER_NOT_EXISTS);
        }
    }

    /*
     * renders full user list with some ajax data
     * 
     * @return string
     */

    public function renderUsers() {
        $jqDt = '
          <script type="text/javascript" charset="utf-8">
                
		$(document).ready(function() {
		$(\'#ukvusershp\').dataTable( {
 	       "oLanguage": {
			"sLengthMenu": "' . __('Show') . ' _MENU_",
			"sZeroRecords": "' . __('Nothing found') . '",
			"sInfo": "' . __('Showing') . ' _START_ ' . __('to') . ' _END_ ' . __('of') . ' _TOTAL_ ' . __('users') . '",
			"sInfoEmpty": "' . __('Showing') . ' 0 ' . __('to') . ' 0 ' . __('of') . ' 0 ' . __('users') . '",
			"sInfoFiltered": "(' . __('Filtered') . ' ' . __('from') . ' _MAX_ ' . __('Total') . ')",
                        "sSearch":       "' . __('Search') . '",
                        "sProcessing":   "' . __('Processing') . '..."
		},
           
                "aoColumns": [
                null,
                null,
                null,
                null,
                null,
                null
            ],      
         
        "bPaginate": true,
        "bLengthChange": true,
        "bFilter": true,
        "bSort": true,
        "bInfo": true,
        "bAutoWidth": false,
        "bProcessing": true,
        "bStateSave": false,
        "iDisplayLength": 50,
        "sAjaxSource": \'' . self::URL_USERS_AJAX_SOURCE . '\',
	"bDeferRender": true,
        "bJQueryUI": true

                } );
		} );
		</script>

          ';

        $result = $jqDt;

        $result.= wf_tag('table', false, '', 'width="100%" id="ukvusershp"');
        $result.= wf_tag('thead');
        $cells = wf_TableCell(__('Full address'));
        $cells.= wf_TableCell(__('Real Name'));
        $cells.= wf_TableCell(__('Contract'));
        $cells.= wf_TableCell(__('Tariff'));
        $cells.= wf_TableCell(__('Connected'));
        $cells.= wf_TableCell(__('Cash'));
        $result.= wf_TableRow($cells, 'row1');
        $result.= wf_tag('thead', true);

        $result.= wf_tag('table', true);


        return ($result);
    }

    /*
     * extract ajax data for JQuery data tables
     */

    public function ajaxUsers() {
        global $ubillingConfig;
        $altcfg = $ubillingConfig->getAlter();

        $result = '{ 
                  "aaData": [ ';
        if (!empty($this->users)) {
            foreach ($this->users as $io => $each) {

                //zero apt numbers as private builds
                if ($altcfg['ZERO_TOLERANCE']) {
                    $apt = ($each['apt'] == 0) ? '' : '/' . $each['apt'];
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
                $profileLink = str_replace('"', '', $profileLink);
                $profileLink = str_replace("\n", '', $profileLink);


                $result.='
                    [
                    "' . $profileLink . $city . $each['street'] . ' ' . $each['build'] . $apt . '",
                    "' . $each['realname'] . '",
                    "' . $each['contract'] . '",
                    "' . @$this->tariffs[$each['tariffid']]['tariffname'] . '",
                    "' . $activity . '",
                    "' . $each['cash'] . '"
                    ],';
            }
            $result = substr($result, 0, -1);
        }
        $result.='] 
        }';
        die($result);
    }

    /*
     * translates payment note for catv users
     * 
     * @param $paynote some payment note to translate
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
            $paynote = str_replace('UKVFEE:', __('Fee') . ' ', $paynote);
        }

        if (ispos($paynote, self::EX_USER_NO_TARIFF_SET)) {
            $paynote = str_replace(self::EX_USER_NO_TARIFF_SET, __('Any tariff not set. Fee charge skipped.') . ' ', $paynote);
        }

        if (ispos($paynote, self::EX_USER_NOT_ACTIVE)) {
            $paynote = str_replace(self::EX_USER_NOT_ACTIVE, __('User not connected. Fee charge skipped.'), $paynote);
        }

        return ($paynote);
    }

    /*
     * renders all of user payments from database
     * 
     * @param $userid existing user ID
     *
     * @return string
     */

    public function userPaymentsRender($userid) {
        global $ubillingConfig;
        $altcfg = $ubillingConfig->getAlter();
        $userid = vf($userid, 3);

        if (isset($this->users[$userid])) {
            if (empty($this->cashtypes)) {
                $this->loadCashtypes();
            }
            $query = "SELECT * from `ukv_payments` WHERE `userid`='" . $userid . "' ORDER BY `id` DESC;";
            $all = simple_queryall($query);

            $cells = wf_TableCell(__('ID'));
            $cells.= wf_TableCell(__('Date'));
            $cells.= wf_TableCell(__('Cash'));
            $cells.= wf_TableCell(__('From'));
            $cells.= wf_TableCell(__('To'));
            $cells.= wf_TableCell(__('Operation'));
            $cells.= wf_TableCell(__('Cash type'));
            $cells.= wf_TableCell(__('Notes'));
            $cells.= wf_TableCell(__('Admin'));
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

                    $cells = wf_TableCell($eachpayment['id']);
                    $cells.= wf_TableCell($eachpayment['date']);
                    $cells.= wf_TableCell($eachpayment['summ']);
                    $cells.= wf_TableCell($eachpayment['balance']);
                    $cells.= wf_TableCell($newBalance);
                    $cells.= wf_TableCell($colorStart . $operation . $colorEnd);
                    $cells.= wf_TableCell($paymentCashtype);
                    $cells.= wf_TableCell($notes);
                    $cells.= wf_TableCell($eachpayment['admin']);
                    $rows.= wf_TableRow($cells, 'row3');
                }
            }

            $result = wf_TableBody($rows, '100%', '0', 'sortable');
            return ($result);
        } else {
            throw new Exception(self::EX_USER_NOT_EXISTS);
        }
    }

    /*     * ******************************
     * Bank statements processing
     * ****************************** */

    /*
     * returns bank statement upload form
     * 
     * @return string
     */

    public function bankstaLoadForm() {
        $uploadinputs = wf_HiddenInput('uploadukvbanksta', 'true');
        $uploadinputs.=__('Bank statement') . wf_tag('br');
        $uploadinputs.=wf_tag('input', false, '', 'id="fileselector" type="file" name="ukvbanksta"') . wf_tag('br');

        $uploadinputs.=wf_Submit('Upload');
        $uploadform = bs_UploadFormBody('', 'POST', $uploadinputs, 'glamour');
        return ($uploadform);
    }

    /*
     * checks is banksta hash unique?
     * 
     * @param $hash  bank statement raw content hash
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

    /*
     * process of uploading of bank statement
     * 
     * @return void
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
                    show_window(__('Error'), __('Same bank statement already exists'));
                }
            } else {
                show_window(__('Error'), __('Cant upload file to') . ' ' . self::BANKSTA_PATH);
            }
        } else {
            show_window(__('Error'), __('Wrong file type'));
            log_register('UKV BANKSTA WRONG FILETYPE');
        }
        return ($result);
    }

    /*
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

                $dbf = new dbf_class(self::BANKSTA_PATH . $bankstadata['savedname']);
                $num_rec = $dbf->dbf_num_rec;
                $importCounter = 0;
                for ($i = 0; $i <= $num_rec; $i++) {
                    $eachRow = $dbf->getRowAssoc($i);
                    if (!empty($eachRow)) {
                        if (!empty($eachRow[self::BANKSTA_CONTRACT])) {
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

                            $query = "INSERT INTO `ukv_banksta` (
                                    `id` ,
                                    `date` ,
                                    `hash` ,
                                    `filename` ,
                                    `admin` ,
                                    `contract` ,
                                    `summ` ,
                                    `address` ,
                                    `realname` ,
                                    `notes` ,
                                    `pdate` ,
                                    `ptime` ,
                                    `processed`
                                    )
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
                                '0'
                                );
                            ";
                            nr_query($query);

                            $importCounter++;
                        }
                    }
                }

                log_register('UKV BANKSTA IMPORTED ' . $importCounter . ' ROWS');
            } else {
                show_window(__('Error'), __('Strange exeption'));
            }
        } else {
            throw new Exception(self::EX_BANKSTA_PREPROCESS_EMPTY);
        }
        return ($result);
    }

    /*
     * returns banksta processing form for some hash
     * 
     * @param $hash  existing preprocessing bank statement hash
     * 
     * @return string
     */

    public function bankstaProcessingForm($hash) {
        $hash = mysql_real_escape_string($hash);
        $query = "SELECT * from `ukv_banksta` WHERE `hash`='" . $hash . "' ORDER BY `id` ASC;";
        $all = simple_queryall($query);
        $cashPairs = array();

        $cells = wf_TableCell(__('ID'));
        $cells.= wf_TableCell(__('Address'));
        $cells.= wf_TableCell(__('Real Name'));
        $cells.= wf_TableCell(__('Contract'));
        $cells.= wf_TableCell(__('Cash'));
        $cells.= wf_TableCell(__('Processed'));
        $cells.= wf_TableCell(__('Contract'));
        $cells.= wf_TableCell(__('Real Name'));
        $cells.= wf_TableCell(__('Address'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($all)) {
            foreach ($all as $io => $each) {


                $AddInfoControl = wf_Link(self::URL_BANKSTA_DETAILED . $each['id'], $each['id'], false, '');
                $processed = ($each['processed']) ? true : false;

                $cells = wf_TableCell($AddInfoControl);
                $cells.= wf_TableCell($each['address']);
                $cells.= wf_TableCell($each['realname']);

                if (!$processed) {
                    $editInputs = wf_TextInput('newbankcontr', '', $each['contract'], false, '6');
                    $editInputs.= wf_CheckInput('lockbankstarow', __('Lock'), false, false);
                    $editInputs.= wf_HiddenInput('bankstacontractedit', $each['id']);
                    $editInputs.= wf_Submit(__('Save'));
                    $editForm = wf_Form('', 'POST', $editInputs);
                } else {
                    $editForm = $each['contract'];
                }
                $cells.= wf_TableCell($editForm);
                $cells.= wf_TableCell($each['summ']);
                $cells.= wf_TableCell(web_bool_led($processed));
                //user detection 
                if (isset($this->contracts[$each['contract']])) {
                    $detectedUser = $this->users[$this->contracts[$each['contract']]];
                    $detectedContract = wf_Link(self::URL_USERS_PROFILE . $detectedUser['id'], web_profile_icon() . ' ' . $detectedUser['contract'], false, '');
                    $detectedAddress = $detectedUser['street'] . ' ' . $detectedUser['build'] . '/' . $detectedUser['apt'];
                    $detectedRealName = $detectedUser['realname'];
                    if (!$processed) {
                        $cashPairs[$each['id']]['bankstaid'] = $each['id'];
                        $cashPairs[$each['id']]['userid'] = $detectedUser['id'];
                        $cashPairs[$each['id']]['usercontract'] = $detectedUser['contract'];
                        $cashPairs[$each['id']]['summ'] = $each['summ'];
                    }
                    $rowClass = 'row3';
                } else {
                    $detectedContract = '';
                    $detectedAddress = '';
                    $detectedRealName = '';
                    if ($each['processed']==1) {
                        $rowClass = 'row2';
                    } else {
                        $rowClass = 'undone';
                    }
                }

                $cells.= wf_TableCell($detectedContract);
                $cells.= wf_TableCell($detectedRealName);
                $cells.= wf_TableCell($detectedAddress);
                $rows.= wf_TableRow($cells, $rowClass);
            }
        }

        $result = wf_TableBody($rows, '100%', '0', '');

        if (!empty($cashPairs)) {
            $cashPairs = serialize($cashPairs);
            $cashPairs = base64_encode($cashPairs);
            $cashInputs = wf_HiddenInput('bankstaneedpaymentspush', $cashPairs);
            $cashInputs.= wf_Submit(__('Bank statement processing'));
            $result.= wf_Form('', 'POST', $cashInputs, 'glamour');
        }


        return ($result);
    }

    /*
     * returns detailed banksta row info
     * 
     * @param $id   existing banksta ID
     * 
     * @return string
     */

    public function bankstaGetDetailedRowInfo($id) {
        $id = vf($id, 3);
        $query = "SELECT * from `ukv_banksta` WHERE `id`='" . $id . "'";
        $dataRaw = simple_query($query);
        $result = '';
        $result.= wf_Link(self::URL_BANKSTA_PROCESSING . $dataRaw['hash'], __('Back'), false, 'ubButton');
        $result.= wf_delimiter();

        if (!empty($dataRaw)) {
            $result.= wf_tag('pre', false, 'floatpanelswide', '') . print_r($dataRaw, true) . wf_tag('pre', true);
        }


        return ($result);
    }

    /*
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

    /*
     * checks is banksta row ID unprocessed?
     * 
     * @param $bankstaid   existing banksta row ID
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
    
   /*
    * sets banksta row as processed
    * 
    * @param $bankstaid  existing bank statement ID
    * 
    * @return void
    */ 
   public function bankstaSetProcessed($bankstaid) {
       $bankstaid=vf($bankstaid,3);
       simple_update_field('ukv_banksta', 'processed', 1, "WHERE `id`='" . $bankstaid . "'");
   }

    /*
     * push payments to some user accounts via bank statements
     * 
     * @return void
     */

    public function bankstaPushPayments() {
        if (wf_CheckPost(array('bankstaneedpaymentspush'))) {
            global $ubillingConfig;
            $altcfg = $ubillingConfig->getAlter();
            $cashtype = $altcfg['UKV_BS_PAYID'];
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
                        $this->userAddCash($eachstatement['userid'], $eachstatement['summ'], 1, $cashtype, 'BANKSTA: [' . $eachstatement['bankstaid'] . '] ASCONTRACT ' . $eachstatement['usercontract']);
                        $this->bankstaSetProcessed($eachstatement['bankstaid']);
                        
                    } else {
                        //duplicate payment try
                        log_register('UKV BANKSTA TRY DUPLICATE [' . $eachstatement['bankstaid'] . '] PAYMENT PUSH');
                    }
                }
            }
        }
    }

    /*
     * renders bank statements list 
     * 
     * @return string
     */

    public function bankstaRenderList() {
        $query = "SELECT `filename`,`hash`,`date`,`admin` FROM `ukv_banksta` GROUP BY `hash` ORDER BY `date` DESC;";
        $all = simple_queryall($query);

        $cells = wf_TableCell(__('Date'));
        $cells.= wf_TableCell(__('Filename'));
        $cells.= wf_TableCell(__('Admin'));
        $cells.= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $cells = wf_TableCell($each['date']);
                $cells.= wf_TableCell($each['filename']);
                $cells.= wf_TableCell($each['admin']);
                $actLinks = wf_Link(self::URL_BANKSTA_PROCESSING . $each['hash'], wf_img('skins/icon_search_small.gif', __('Show')), false, '');
                $cells.= wf_TableCell($actLinks);
                $rows.= wf_TableRow($cells, 'row3');
            }
        }
        $result = wf_TableBody($rows, '100%', '0', 'sortable');

        return ($result);
    }

    /*
     * cnahges banksta contract number for some existing row
     * 
     * @param $bankstaid    existing bank statement transaction ID
     * @param $contract     new contract number for this row
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

    /*
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
        $template.= wf_tag('a', false, '', 'href="' . $task_link . '"');
        $template.= wf_tag('img', false, '', 'src="' . $task_icon . '" border="0" width="' . $tbiconsize . '"  height="' . $tbiconsize . '" alt="' . $task_text . '" title="' . $task_text . '"');
        $template.= wf_tag('a', true);
        $template.= wf_tag('br');
        $template.= wf_tag('br');
        $template.= $task_text;
        $template.= wf_tag('div', true);
        return ($template);
    }

    /*
     * renders report list
     * 
     * @return void
     */

    public function reportList() {
        $reports = '';
        $reports.= $this->buildReportTask(self::URL_REPORTS_MGMT . 'reportDebtors', 'debtors.png', __('Debtors'));
        $reports.= $this->buildReportTask(self::URL_REPORTS_MGMT . 'reportAntiDebtors', 'antidebtors.png', __('AntiDebtors'));
        show_window(__('Reports'), $reports);
    }
    
    /*
     * shows printable report content
     * 
     * @param $title report title
     * @param $data  report data to printable transform
     * 
     * @return void
     */
    protected function reportPrintable($title,$data) {
        $style='
        <style type="text/css">
        table.printable {
	border-width: 1px;
	border-spacing: 2px;
	border-style: outset;
	border-color: gray;
	border-collapse: separate;
	background-color: white;
        }
        table.printable th {
	border-width: 1px;
	padding: 1px;
	border-style: dashed;
	border-color: gray;
	background-color: white;
	-moz-border-radius: ;
        }
        table.printable td {
	border-width: 1px;
	padding: 1px;
	border-style: dashed;
	border-color: gray;
	background-color: white;
	-moz-border-radius: ;
        }
        </style>
        ';
        $title= (!empty($title)) ? wf_tag('h2').$title.  wf_tag('h2',  true) : '' ;
        $data=$style.$title.$data;
        $data=str_replace('sortable', 'printable', $data);
        die($data);
    }
            

    /*
     * renders debtors report
     * 
     * @return void
     */

    public function reportDebtors() {
        $debtorsArr=array();
        $result='';
       
            if (!empty($this->users)) {
            foreach ($this->users as $ix => $eachUser) {
                $userTariff = $eachUser['tariffid'];
                $tariffPrice = $this->tariffs[$userTariff]['price'];
                $debtMaxLimit = '-' . ($tariffPrice * $this->debtLimit);
                if (($eachUser['cash'] <= $debtMaxLimit) AND ($eachUser['active'] == 1)) {
                   $debtorsArr[$eachUser['street']][$eachUser['id']]=$eachUser;
                }
            }
           }
  
        if (!empty($debtorsArr)) {
            foreach ($debtorsArr as $streetName=>$eachDebtorStreet) {
                if (!empty($eachDebtorStreet)) {
                    $result.=wf_tag('h3').$streetName.  wf_tag('h3', true);
                     $cells= wf_TableCell(__('Contract'),'10%');
                     $cells.= wf_TableCell(__('Full address'),'31%');
                     $cells.= wf_TableCell(__('Real Name'),'30%');
                     $cells.= wf_TableCell(__('Tariff'),'15%');
                     $cells.= wf_TableCell(__('Cash'),'7%');
                     $cells.= wf_TableCell(__('Connected'),'7%');
                     $rows = wf_TableRow($cells, 'row1');
                     foreach ($eachDebtorStreet as $ia=>$eachDebtor) {
                            $cells= wf_TableCell($eachDebtor['contract']);
                            $debtorAddress=$eachDebtor['street'].' '.$eachDebtor['build'].'/'.$eachDebtor['apt'];
                            $cells.= wf_TableCell($debtorAddress);
                            $cells.= wf_TableCell($eachDebtor['realname']);
                            $cells.= wf_TableCell($this->tariffs[$eachDebtor['tariffid']]['tariffname']);
                            $cells.= wf_TableCell($eachDebtor['cash']);
                            $cells.= wf_TableCell(web_bool_led($eachDebtor['active'], true));
                            $rows.=  wf_TableRow($cells, 'row3');
                     }
                     
                     $result .= wf_TableBody($rows, '100%', '0', 'sortable');
                }
                
            }
        }
        
        $printableControl=  wf_Link(self::URL_REPORTS_MGMT.'reportDebtors&printable=true', wf_img('skins/icon_print.png',__('Print')));
        
        if (wf_CheckGet(array('printable'))) {
            $this->reportPrintable(__('Debtors'),$result);
        } else {
             show_window(__('Debtors').' '.$printableControl,$result);
        }
    }
    
    
    
    /*
     * renders anti-debtors report
     * 
     * @return void
     */

    public function reportAntiDebtors() {
        $debtorsArr=array();
        $result='';
       
            if (!empty($this->users)) {
            foreach ($this->users as $ix => $eachUser) {
                $userTariff = $eachUser['tariffid'];
                $tariffPrice = $this->tariffs[$userTariff]['price'];
                if (($eachUser['cash'] >= 0) AND ($eachUser['active'] == 0)) {
                   $debtorsArr[$eachUser['street']][$eachUser['id']]=$eachUser;
                }
            }
           }
  
        if (!empty($debtorsArr)) {
            foreach ($debtorsArr as $streetName=>$eachDebtorStreet) {
                if (!empty($eachDebtorStreet)) {
                    $result.=wf_tag('h3').$streetName.  wf_tag('h3', true);
                     $cells= wf_TableCell(__('Contract'),'10%');
                     $cells.= wf_TableCell(__('Full address'),'31%');
                     $cells.= wf_TableCell(__('Real Name'),'30%');
                     $cells.= wf_TableCell(__('Tariff'),'15%');
                     $cells.= wf_TableCell(__('Cash'),'7%');
                     $cells.= wf_TableCell(__('Connected'),'7%');
                     $rows = wf_TableRow($cells, 'row1');
                     foreach ($eachDebtorStreet as $ia=>$eachDebtor) {
                            $cells= wf_TableCell($eachDebtor['contract']);
                            $debtorAddress=$eachDebtor['street'].' '.$eachDebtor['build'].'/'.$eachDebtor['apt'];
                            $cells.= wf_TableCell($debtorAddress);
                            $cells.= wf_TableCell($eachDebtor['realname']);
                            $cells.= wf_TableCell($this->tariffs[$eachDebtor['tariffid']]['tariffname']);
                            $cells.= wf_TableCell($eachDebtor['cash']);
                            $cells.= wf_TableCell(web_bool_led($eachDebtor['active'], true));
                            $rows.=  wf_TableRow($cells, 'row3');
                     }
                     
                     $result .= wf_TableBody($rows, '100%', '0', 'sortable');
                }
                
            }
        }
        
        $printableControl=  wf_Link(self::URL_REPORTS_MGMT.'reportAntiDebtors&printable=true', wf_img('skins/icon_print.png',__('Print')));
        
        if (wf_CheckGet(array('printable'))) {
            $this->reportPrintable(__('AntiDebtors'),$result);
        } else {
             show_window(__('AntiDebtors').' '.$printableControl,$result);
        }
    }
   

}




?>