<?php

class MegogoApi {

    /**
     * System alter.ini config stored as array key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Partner ID property via MG_PARTNERID
     *
     * @var string
     */
    protected $partnerId = '';

    /**
     * Users ID prefixes via MG_PREFIX
     *
     * @var string
     */
    protected $prefix = '';

    /**
     * Auth salt value via MG_SALT
     *
     * @var string
     */
    protected $salt = '';

    /**
     * subscribe/unsubscribe API URL
     *
     * @var string 
     */
    protected $urlApi = '';

    /**
     * Authorization API URL
     *
     * @var string
     */
    protected $urlAuth = '';

    public function __construct() {
        $this->loadAlter();
        $this->setOptions();
    }

    /**
     * Loads system alter config into private prop
     * 
     * @return void
     */
    protected function loadAlter() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Sets basic configurable options for further usage
     * 
     * @return void
     */
    protected function setOptions() {
        $this->partnerId = $this->altCfg['MG_PARTNERID'];
        $this->prefix = $this->altCfg['MG_PREFIX'];
        $this->salt = $this->altCfg['MG_SALT'];
        $this->urlApi = 'http://billing.megogo.net/partners/';
        $this->urlAuth = 'http://megogo.net/auth/by_partners/';
    }

    /**
     * Subscribes user to some service
     * 
     * @param string $login Existing user login to subscribe
     * @param string $service Valid serviceid
     * 
     * @return bool
     */
    public function subscribe($login, $service) {
        $result = false;
        $query = $this->urlApi . $this->partnerId . '/subscription/subscribe?userId=' . $this->prefix . $login . '&serviceId=' . $service;
        $queryResult = file_get_contents($query);
        if (!empty($queryResult)) {
            $queryResult = json_decode($queryResult);
            if ($queryResult->successful) {
                $result = true;
            }
        }
        return ($result);
    }

    /**
     * Unsubscribes user for some service
     * 
     * @param string $login Existing user login to subscribe
     * @param string $service Valid serviceid
     * 
     * @return bool
     */
    public function unsubscribe($login, $service) {
        $result = false;
        $query = $this->urlApi . $this->partnerId . '/subscription/unsubscribe?userId=' . $this->prefix . $login . '&serviceId=' . $service;
        $queryResult = file_get_contents($query);
        if (!empty($queryResult)) {
            $queryResult = json_decode($queryResult);
            if ($queryResult->successful) {
                $result = true;
            }
        }
        return ($result);
    }

    /**
     * Returns auth codes
     * 
     * @param string $login Existing user login
     * @return strig
     */
    public function authCode($login) {
        $result = '';
        $hashData = $this->prefix . $login . $this->partnerId . $this->salt;
        $token = md5($hashData);
        $result = $this->urlAuth . 'dialog?isdn=' . $this->prefix . $login . '&partner_key=' . $this->partnerId . '&token=' . $token;
        return ($result);
    }

}

class MegogoInterface {

    /**
     * System alter.ini config stored as array key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * System messages object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Contains available megogo service tariffs id=>tariffdata
     *
     * @var array
     */
    protected $allTariffs = array();

    /**
     * Contains available and active megogo service subscriptions as id=>data
     *
     * @var array
     */
    protected $allSubscribers = array();

    /**
     * Contains all subscribtions history by all of users id=>data
     *
     * @var array
     */
    protected $allHistory = array();

    /**
     * Contains all of available scheduled actions queue as id => queue data
     *
     * @var array
     */
    protected $allQueue = array();

    /**
     * Contains all of internet users data as login=>data
     *
     * @var array
     */
    protected $allUsers = array();

    const URL_ME = '?module=megogo';
    const URL_TARIFFS = 'tariffs=true';
    const URL_SUBS = 'subscriptions=true';
    const URL_AJSUBS = 'ajsubs=true';
    const URL_SUBVIEW = 'subview=true';
    const URL_REPORTS = 'reports=true';

    public function __construct() {
        $this->loadAlter();
        $this->initMessages();
        $this->loadUsers();
        $this->loadTariffs();
        $this->loadSubscribers();
        $this->loadHistory();
        $this->loadQueue();
    }

    /**
     * Loads system alter config into private prop
     * 
     * @return void
     */
    protected function loadAlter() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Initializes system message helper
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Loads existing tariffs from database for further usage
     * 
     * @return void
     */
    protected function loadTariffs() {
        $query = "SELECT * from `mg_tariffs`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allTariffs[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads existing subscribers data
     * 
     * @return void
     */
    protected function loadSubscribers() {
        $query = "SELECT * from `mg_subscribers`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allSubscribers[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads existing subscribers data
     * 
     * @return void
     */
    protected function loadHistory() {
        $query = "SELECT * from `mg_history`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allHistory[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads scheduled queue from database
     * 
     * @return void
     */
    protected function loadQueue() {
        $query = "SELECT * from `mg_queue`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allQueue[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads available users from database
     * 
     * @return void
     */
    protected function loadUsers() {
        $all = zb_UserGetAllStargazerData();
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allUsers[$each['login']] = $each;
            }
        }
    }

    /**
     * Returns tariffs Megogo service ID
     * 
     * @param int $tariffid
     * 
     * @return string
     */
    public function getTariffServiceId($tariffid) {
        $tariffid = vf($tariffid, 3);
        $result = '';
        if (!empty($this->allTariffs)) {
            foreach ($this->allTariffs as $io => $each) {
                if ($each['id'] == $tariffid) {
                    $result = $each['serviceid'];
                    break;
                }
            }
        }
        return ($result);
    }

    /**
     * Returns tariffs price
     * 
     * @param int $tariffid
     * 
     * @return float
     */
    public function getTariffFee($tariffid) {
        $tariffid = vf($tariffid, 3);
        $result = 0;
        if (!empty($this->allTariffs)) {
            foreach ($this->allTariffs as $io => $each) {
                if ($each['id'] == $tariffid) {
                    $result = $each['fee'];
                    break;
                }
            }
        }
        return ($result);
    }

    /**
     * Checks free period availability for user
     * 
     * @param string $login
     * 
     * @return bool
     */
    protected function checkFreePeriodAvail($login) {
        $query = "SELECT * from `mg_history` WHERE `login`='" . $login . "' AND `freeperiod`='1';";
        $raw = simple_query($query);
        $result = (empty($raw)) ? true : false;
        return ($result);
    }

    /**
     * Check user tariff subscribtion possibility
     * 
     * @param string $login
     * @param int $tariffid
     * 
     * @return bool
     */
    protected function checkTariffAvail($login, $tariffid) {
        $result = true;
        $tariffid = vf($tariffid, 3);
        if (!empty($this->allSubscribers)) {
            foreach ($this->allSubscribers as $io => $each) {
                if (($each['login'] == $login) AND ( $each['tariffid'] == $tariffid)) {
                    $result = false;
                    break;
                }
            }
        }
        return ($result);
    }

    /**
     * Checks user for only one primary subscription
     * 
     * @param string $login
     * 
     * @return bool
     */
    protected function checkTariffPrimary($login, $tariffid) {
        $result = true;
        $tariffData = $this->allTariffs[$tariffid];
        $tariffPrimary = $tariffData['primary'];
        if ($tariffPrimary) {
            if (!empty($this->allSubscribers)) {
                foreach ($this->allSubscribers as $io => $each) {
                    if ($each['primary'] == 1) {
                        $result = false;
                        break;
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Returns valid dayly fee for some tariff/month
     * 
     * @param float $tariffFee
     * 
     * @return float
     */
    protected function getDaylyFee($tariffFee) {
        $monthDays = date("t");
        $result = round(($tariffFee / $monthDays), 2);
        return ($result);
    }

    /**
     * Create subscription
     * 
     * @param string $login
     * @param int $tariffid
     * 
     * @return void/strin on error
     */
    public function createSubscribtion($login, $tariffid) {
        $curdatetime = curdatetime();
        $loginF = mysql_real_escape_string($login);
        $tariffid = vf($tariffid, 3);
        $activeFlag = 1;
        $freePeriodFlag = 0;
        $result = '';
        if (isset($this->allUsers[$login])) {
            if (isset($this->allTariffs[$tariffid])) {
                if ($this->checkTariffAvail($login, $tariffid)) {
                    if ($this->checkTariffPrimary($login, $tariffid)) {
                        $tariffData = $this->allTariffs[$tariffid];
                        if ($tariffData['freeperiod']) {
                            $freePeriodFlag = ($this->checkFreePeriodAvail($login)) ? 1 : 0;
                        }
                        $query = "INSERT INTO `mg_subscribers` (`id`,`login`,`tariffid`,`actdate`,`active`,`primary`,`freeperiod`) VALUES";
                        $query.="(NULL,'" . $loginF . "','" . $tariffid . "','" . $curdatetime . "','" . $activeFlag . "','" . $tariffData['primary'] . "','" . $freePeriodFlag . "');";
                        nr_query($query);
                        log_register('MEGOGO SUBSCRIBE (' . $login . ') TARIFF [' . $tariffid . ']');
                        $mgApi = new MegogoApi();
                        $mgApi->subscribe($login, $tariffData['serviceid']);
                        log_register('MEGOGO ACTIVATED (' . $login . ') SERVICE [' . $tariffData['serviceid'] . ']');

                        //force fee
                        if (!$freePeriodFlag) {
                            if ($this->altCfg['MG_SPREAD']) {
                                //charge fee only for current day
                                if ($this->altCfg['MG_SPREAD'] == 1) {
                                    $tariffFee = $this->getTariffFee($tariffid);
                                    $tariffFee = $this->getDaylyFee($tariffFee);
                                }
                                //charge fee to the end of month
                                if ($this->altCfg['MG_SPREAD'] == 2) {
                                    $tariffFee = $this->getTariffFee($tariffid);
                                    $currentDayOfMonth = date("d");
                                    $currentMonthDayCount = date("t");
                                    $tariffFeeDaily = $this->getDaylyFee($tariffFee);
                                    $tariffFee = ($currentMonthDayCount - $currentDayOfMonth) * $tariffFeeDaily;
                                }
                            } else {
                                //charge full monthly fee
                                $tariffFee = $this->getTariffFee($tariffid);
                            }
                            zb_CashAdd($login, '-' . $tariffFee, 'add', 1, 'MEGOGO:' . $tariffid);
                            log_register('MEGOGO FEE (' . $login . ') -' . $tariffFee);
                        } else {
                            //free period mark for reports
                            zb_CashAdd($login, '-0', 'add', 1, 'MEGOGO:' . $tariffid);
                            log_register('MEGOGO FEE (' . $login . ') -0');
                        }

                        $queryHistory = "INSERT INTO `mg_history` (`id`,`login`,`tariffid`,`actdate`,`freeperiod`) VALUES";
                        $queryHistory.="(NULL,'" . $loginF . "','" . $tariffid . "','" . $curdatetime . "','" . $freePeriodFlag . "');";
                        nr_query($queryHistory);
                    } else {
                        $result = 'Only one primary tariff allowed';
                    }
                } else {
                    $result = 'Already subscribed';
                }
            } else {
                $result = 'Wrong tariff';
            }
        } else {
            $result = 'Non existent user';
        }
        return ($result);
    }

    /**
     * Deletes existing subscription
     * 
     * @param string $login
     * @param int $tariffid
     * 
     * @return void
     */
    public function deleteSubscribtion($login, $tariffid) {
        $curdatetime = curdatetime();
        $loginF = mysql_real_escape_string($login);
        $tariffid = vf($tariffid, 3);
        $activeFlag = 1;
        if (isset($this->allTariffs[$tariffid])) {
            $tariffData = $this->allTariffs[$tariffid];
            $query = "DELETE from `mg_subscribers` WHERE `login`='" . $loginF . "' AND `tariffid`='" . $tariffid . "';";
            nr_query($query);
            log_register('MEGOGO UNSUBSCRIBE (' . $login . ') TARIFF [' . $tariffid . ']');
            $mgApi = new MegogoApi();
            $mgApi->unsubscribe($login, $tariffData['serviceid']);
            log_register('MEGOGO DEACTIVATED (' . $login . ') SERVICE [' . $tariffData['serviceid'] . ']');
        }
    }

    /**
     * Returns primary controls panel
     * 
     * @return string
     */
    public function renderPanel() {
        $result = '';
        $result.=wf_Link(self::URL_ME . '&' . self::URL_SUBS, wf_img('skins/ukv/users.png') . ' ' . __('Subscriptions'), false, 'ubButton') . ' ';
        $result.=wf_Link(self::URL_ME . '&' . self::URL_TARIFFS, wf_img('skins/ukv/dollar.png') . ' ' . __('Tariffs'), false, 'ubButton') . ' ';
        $result.=wf_Link(self::URL_ME . '&' . self::URL_REPORTS, wf_img('skins/ukv/report.png') . ' ' . __('Reports'), false, 'ubButton') . ' ';
        return ($result);
    }

    /**
     * Renders available tariffs list
     * 
     * @return string
     */
    public function renderTariffs() {
        $result = '';

        $cells = wf_TableCell(__('ID'));
        $cells.= wf_TableCell(__('Tariff name'));
        $cells.= wf_TableCell(__('Fee'));
        $cells.= wf_TableCell(__('Service ID'));
        $cells.= wf_TableCell(__('Primary'));
        $cells.= wf_TableCell(__('Free period'));
        $cells.= wf_TableCell(__('Actions'));

        $rows = wf_TableRow($cells, 'row1');

        if (!empty($this->allTariffs)) {
            foreach ($this->allTariffs as $io => $each) {
                $cells = wf_TableCell($each['id']);
                $cells.= wf_TableCell($each['name']);
                $cells.= wf_TableCell($each['fee']);
                $cells.= wf_TableCell($each['serviceid']);
                $cells.= wf_TableCell(web_bool_led($each['primary']));
                $cells.= wf_TableCell(web_bool_led($each['freeperiod']));
                $actLinks = wf_JSAlert(self::URL_ME . '&' . self::URL_TARIFFS . '&deletetariffid=' . $each['id'], web_delete_icon(), $this->messages->getDeleteAlert());
                $cells.= wf_TableCell($actLinks);
                $rows.= wf_TableRow($cells, 'row3');
            }
        }

        $result = wf_TableBody($rows, '100%', 0, 'sortable');

        return ($result);
    }

    /**
     * Returns tariff creation form
     * 
     * @return string
     */
    public function tariffCreateForm() {
        $result = '';

        $inputs = wf_TextInput('newtariffname', __('Tariff name'), '', true, '10');
        $inputs.= wf_TextInput('newtarifffee', __('Fee'), '', true, '5');
        $inputs.= wf_TextInput('newtariffserviceid', __('Service ID'), '', true, '10');
        $inputs.= wf_CheckInput('newtariffprimary', __('Primary'), true, false);
        $inputs.= wf_CheckInput('newtarifffreeperiod', __('Free period'), true, false);
        $inputs.= wf_Submit(__('Create'));

        $result = wf_Form('', 'POST', $inputs, 'glamour');

        return ($result);
    }

    /**
     * Creates new tariff in database
     * 
     * @return void/string on error
     */
    public function tariffCreate() {
        $result = '';
        if (wf_CheckPost(array('newtariffname', 'newtarifffee', 'newtariffserviceid'))) {
            $nameF = mysql_real_escape_string($_POST['newtariffname']);
            $feeF = mysql_real_escape_string($_POST['newtarifffee']);
            $serviceidF = mysql_real_escape_string($_POST['newtariffserviceid']);
            $primary = wf_CheckPost(array('newtariffprimary')) ? 1 : 0;
            $freePeriod = wf_CheckPost(array('newtarifffreeperiod')) ? 1 : 0;

            if (zb_checkMoney($feeF)) {
                $query = "INSERT INTO `mg_tariffs` (`id`,`name`,`fee`,`serviceid`,`primary`,`freeperiod`) VALUES ";
                $query.= "(NULL,'" . $nameF . "','" . $feeF . "','" . $serviceidF . "','" . $primary . "','" . $freePeriod . "')";
                nr_query($query);
                $newId = simple_get_lastid('mg_tariffs');
                log_register('MEGOGO TARIFF CREATE [' . $newId . '] `' . $_POST['newtariffname'] . '` FEE `' . $_POST['newtarifffee'] . '`');
            } else {
                $result = $this->messages->getStyledMessage(__('Wrong format of a sum of money to pay'), 'error');
            }
        } else {
            $result = $this->messages->getStyledMessage(__('No all of required fields is filled'), 'error');
        }
        return ($result);
    }

    /**
     * Checks is tariff used by some users
     * 
     * @param int $tariffid
     * 
     * @return bool
     */
    protected function tariffProtected($tariffid) {
        $result = false;
        if (!empty($this->allSubscribers)) {
            foreach ($this->allSubscribers as $io => $each) {
                if ($each['tariffid'] == $tariffid) {
                    $result = true;
                    break;
                }
            }
        }
        return ($result);
    }

    /**
     * Deletes existing tariff from database
     * 
     * @param int $tariffId
     * 
     * @return void/string
     */
    public function tariffDelete($tariffId) {
        $tariffId = vf($tariffId, 3);
        $result = '';
        if (isset($this->allTariffs[$tariffId])) {
            if (!$this->tariffProtected($tariffId)) {
                $query = "DELETE from `mg_tariffs` WHERE `id`='" . $tariffId . "';";
                nr_query($query);
                log_register('MEGOGO TARIFF DELETE [' . $tariffId . ']');
            } else {
                $result = $this->messages->getStyledMessage(__('Tariff is used by some users'), 'error');
            }
        } else {
            $result = $this->messages->getStyledMessage(__('Not existing item'), 'error');
        }
        return ($result);
    }

    /**
     * Renders default subscriptions report
     * 
     * @return string
     */
    public function renderSubscribtionsReportMonthly() {
        $result = '';
        $inputs = wf_YearSelector('yearsel', __('Year'), false) . ' ';
        $inputs.= wf_MonthSelector('monthsel', __('Month'), date("m"), false) . ' ';
        $inputs.= wf_Submit(__('Show'));
        $result.= wf_Form('', 'POST', $inputs, 'glamour');
        $curYear = (wf_CheckPost(array('yearsel'))) ? vf($_POST['yearsel'], 3) : curyear();
        $curMonth = (wf_CheckPost(array('monthsel'))) ? vf($_POST['monthsel'], 3) : date("m");

        $query = "SELECT * from `payments` WHERE `date` LIKE '" . $curYear . "-" . $curMonth . "%' AND `note` LIKE 'MEGOGO:%';";
        $raw = simple_queryall($query);
        $tmpArr = array();

        if (!empty($raw)) {
            foreach ($raw as $io => $each) {
                $tariffId = explode(':', $each['note']);
                $tariffId = $tariffId[1];
                if (isset($tmpArr[$tariffId])) {
                    $tmpArr[$tariffId]['summ'] = $tmpArr[$tariffId]['summ'] + abs($each['summ']);
                    $tmpArr[$tariffId]['count'] ++;
                    //try&buy user
                    if ($each['summ'] == 0) {
                        $tmpArr[$tariffId]['freeperiod'] ++;
                    }
                } else {
                    $tmpArr[$tariffId]['summ'] = abs($each['summ']);
                    $tmpArr[$tariffId]['count'] = 1;
                    //try&buy user
                    if ($each['summ'] == 0) {
                        $tmpArr[$tariffId]['freeperiod'] = 1;
                    } else {
                        $tmpArr[$tariffId]['freeperiod'] = 0;
                    }
                }
            }
        }

        if (!empty($tmpArr)) {
            $cells = wf_TableCell(__('Tariff'));
            $cells.= wf_TableCell(__('Fee'));
            $cells.= wf_TableCell(__('Users'));
            $cells.= wf_TableCell(__('Free period'));
            $cells.= wf_TableCell(__('Total payments'));
            $cells.= wf_TableCell(__('Profit'));
            $rows = wf_TableRow($cells, 'row1');
            $totalUsers = 0;
            $totalFree = 0;
            $totalSumm = 0;

            foreach ($tmpArr as $io => $each) {
                $totalUsers = $totalUsers + $each['count'];
                $totalFree = $totalFree + $each['freeperiod'];
                $totalSumm = $totalSumm + $each['summ'];

                $cells = wf_TableCell(@$this->allTariffs[$io]['name']);
                $cells.= wf_TableCell(@$this->allTariffs[$io]['fee']);
                $cells.= wf_TableCell($each['count']);
                $cells.= wf_TableCell($each['freeperiod']);
                $cells.= wf_TableCell($each['summ']);
                $cells.= wf_TableCell(zb_Percent($each['summ'], $this->altCfg['MG_PERCENT']));
                $rows.= wf_TableRow($cells, 'row3');
            }

            $cells = wf_TableCell('');
            $cells.= wf_TableCell('');
            $cells.= wf_TableCell($totalUsers);
            $cells.= wf_TableCell($totalFree);
            $cells.= wf_TableCell($totalSumm);
            $cells.= wf_TableCell(zb_Percent($totalSumm, $this->altCfg['MG_PERCENT']));
            $rows.= wf_TableRow($cells, 'row2');

            $result.=wf_TableBody($rows, '100%', 0, '');
        } else {
            $result.=$this->messages->getStyledMessage(__('Nothing found'), 'info');
        }


        return ($result);
    }

    /**
     * Returns data container for active subscriptions
     * 
     * @return string
     */
    public function renderSubscribtions() {
        $result = '';
        $columns = array(__('ID'), __('User'), __('Current tariff'), __('Date'), __('Active'), __('Primary'), __('Free period'), __('Actions'));
        $result = wf_JqDtLoader($columns, self::URL_ME . '&' . self::URL_SUBS . '&' . self::URL_AJSUBS, true, __('Subscriptions'), '100');
        return ($result);
    }

    /**
     * Renders ajax data subscriptions
     * 
     * @return void
     */
    public function subscribtionsListAjax() {
        $result = '{ 
                  "aaData": [ ';
        if (!empty($this->allSubscribers)) {
            foreach ($this->allSubscribers as $io => $each) {
                $userLink = wf_Link('?module=userprofile&username=' . $each['login'], web_profile_icon() . ' ' . $each['login'], false);
                $userLink = trim($userLink);
                $userLink = str_replace('"', '', $userLink);
                $actFlag = web_bool_led($each['active'], false);
                $actFlag = str_replace('"', '', $actFlag);
                $primFlag = web_bool_led($each['primary'], false);
                $primFlag = str_replace('"', '', $primFlag);
                $freeperiodFlag = web_bool_led($each['freeperiod'], false);
                $freeperiodFlag = str_replace('"', '', $freeperiodFlag);
                $actLinks = wf_Link(self::URL_ME . '&' . self::URL_SUBVIEW . '&subid=' . $each['id'], wf_img('skins/icon_edit.gif'));
                $actLinks = str_replace('"', '', $actLinks);
                $actLinks = trim($actLinks);

                $result.='
                    [
                    "' . $each['id'] . '",
                    "' . $userLink . '",
                    "' . @$this->allTariffs[$each['tariffid']]['name'] . '",
                    "' . $each['actdate'] . '",
                    "' . $actFlag . '",
                    "' . $primFlag . '",
                    "' . $freeperiodFlag . '",
                    "' . $actLinks . '"
                    ],';
            }
        }

        $result = zb_CutEnd($result);
        $result.='] 
        }';
        die($result);
    }

    /**
     * Returns some user balance
     * 
     * @return float
     */
    protected function getUserBalance($login) {
        $result = 0;
        if (isset($this->allUsers[$login])) {
            $result = $this->allUsers[$login]['Cash'];
        }
        return ($result);
    }

    /**
     * Creates scheduler task in database
     * 
     * @param string $login
     * @param string $action
     * @param int $tariffid
     * 
     * @return void
     */
    protected function createQueue($login, $action, $tariffid) {
        $loginF = mysql_real_escape_string($login);
        $actionF = mysql_real_escape_string($action);
        $tariffid = vf($tariffid, 3);
        $curdate = curdatetime();
        $query = "INSERT INTO `mg_queue` (`id`,`login`,`date`,`action`,`tariffid`) VALUES";
        $query.= "(NULL,'" . $loginF . "','" . $curdate . "','" . $actionF . "','" . $tariffid . "')";
        nr_query($query);
        log_register('MEGOGO QUEUE CREATE (' . $login . ') TARIFF [' . $tariffid . '] ACTION `' . $action . '`');
    }

    /**
     * Checks is queue for this login/tariff clean?
     * 
     * @param string $login
     * @param int $tariffid
     * 
     * @return bool
     */
    protected function checkSchedule($login, $tariffid) {
        $result = true;
        if (!empty($this->allQueue)) {
            foreach ($this->allQueue as $io => $each) {
                if (($each['login'] == $login) AND ( $each['tariffid'] == $tariffid)) {
                    $result = false;
                    break;
                }
            }
        }
        return ($result);
    }

    /**
     * Schedules tariff unsubscribe from next month
     * 
     * @param string $login
     * @param int $tariffid
     * 
     * @return string
     */
    public function scheduleUnsubscribe($login, $tariffid) {
        if ($this->checkSchedule($login, $tariffid)) {
            $this->createQueue($login, 'unsub', $tariffid);
            $result = 'The service will be disabled on the first day of the following month';
        } else {
            $result = 'Already scheduled';
        }
        return ($result);
    }

    /**
     * Performs scheduler queue actions
     * 
     * @return string
     */
    public function scheduleProcessing() {
        $result = '';
        if (!empty($this->allQueue)) {
            foreach ($this->allQueue as $io => $each) {
                //unsubscription management
                if ($each['action'] == 'unsub') {
                    $query = "DELETE from `mg_queue` WHERE `id`='" . $each['id'] . "';";
                    nr_query($query);
                    $this->deleteSubscribtion($each['login'], $each['tariffid']);
                    $result.=$each['login'] . ' SCHEDULE UNSUB [' . $each['tariffid'] . ']' . "\n";
                }
            }
        }
        return ($result);
    }

    /**
     * Performs available active subscriptions fee processing
     * 
     * @return string
     */
    public function subscriptionFeeProcessing() {
        $result = '';
        $megogoApi = new MegogoApi();
        if (!empty($this->allSubscribers)) {
            foreach ($this->allSubscribers as $io => $each) {
                if (!$each['freeperiod']) {
                    //active subscription - normal fee
                    $tariffFee = $this->getTariffFee($each['tariffid']);
                    if ($this->altCfg['MG_SPREAD']) {
                        //possible spread fee charge
                        $tariffFee = $this->getDaylyFee($tariffFee);
                    }
                    if ($each['active']) {
                        $userBalance = $this->getUserBalance($each['login']);
                        if ($userBalance >= 0) {
                            zb_CashAdd($each['login'], '-' . $tariffFee, 'add', 1, 'MEGOGO:' . $each['tariffid']);
                            log_register('MEGOGO FEE (' . $each['login'] . ') -' . $tariffFee);
                            $result.=$each['login'] . ' FEE ' . $tariffFee . "\n";
                        } else {
                            $this->deleteSubscribtion($each['login'], $each['tariffid']);
                            $result.=$each['login'] . ' UNSUB [' . $each['tariffid'] . ']' . "\n";
                        }
                    }
                } else {
                    
                    //TODO: fix daily processing
                    $this->deleteSubscribtion($each['login'], $each['tariffid']);
                    log_register('MEGOGO (' . $each['login'] . ') FREE PERIOD EXPIRED');
                    $result.=$each['login'] . ' UNSUB [' . $each['tariffid'] . '] FREE' . "\n";
                }
            }
        }
        return ($result);
    }

}

?>