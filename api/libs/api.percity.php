<?php

Class ColorTagging {

    protected $allTags = array();
    protected $allTagTypes = array();
    protected $altCfg = array();

    public function __construct() {
        $this->LoadAllTags();
        $this->LoadAllTagTypes();
        $this->loadAlter();
    }

    protected function LoadAllTags() {
        $query = "SELECT * FROM `tags`";
        $allData = simple_queryall($query);
        if (!empty($allData)) {
            foreach ($allData as $eachData) {
                $this->allTags[$eachData['login']] = $eachData['tagid'];
            }
        }
    }

    protected function LoadAllTagTypes() {
        $query = "SELECT * FROM `tagtypes`";
        $allData = simple_queryall($query);
        if (!empty($allData)) {
            foreach ($allData as $eachData) {
                $this->allTagTypes[$eachData['id']] = $eachData;
            }
        }
    }

    protected function loadAlter() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    public function GetUsersColor($login) {
        $color = '';
        $allowed = '';
        $equal = false;
        if (isset($this->allTags[$login])) {
            if (isset($this->altCfg['ALLOWED_COLORS'])) {
                if (is_numeric($this->altCfg['ALLOWED_COLORS'])) {
                    $allowed = $this->altCfg['ALLOWED_COLORS'];
                    if ($this->allTagTypes[$this->allTags[$login]]['id'] == $allowed) {
                        $equal = true;
                    }
                } else {
                    $allowed = explode(",", $this->altCfg['ALLOWED_COLORS']);
                    foreach ($allowed as $each) {
                        if ($this->allTagTypes[$this->allTags[$login]]['id'] == $each) {
                            $equal = true;
                        }
                    }
                }
                if ($equal) {
                    $color = $this->allTagTypes[$this->allTags[$login]]['tagcolor'];
                }
            } else {
                $color = $this->allTagTypes[$this->allTags[$login]]['tagcolor'];
            }
        }
        return($color);
    }

}

class PerCityAction {

    const MODULE_NAME = "?module=per_city_action";
    const PERMISSION_PATH = "content/documents/per_city_permission/";

    /**
     * Contains all addresses array as login=>address
     * 
     * @var array
     */
    protected $allAddresses = array();

    /**
     * Contains all realnames array as login=>realname
     * 
     * @var array
     */
    protected $allRealNames = array();

    /**
     * Contains array of all available cashtypes as id=>name
     * 
     * @var array
     */
    protected $allCashTypes = array();

    /**
     * Contains all data for opts usersearch, debtors, payments
     * usersearch - query selects all users in certain city
     * debtors - query selects all debtors in certain city
     * payments - query select all payments in certain city by month (by default current month)
     * 
     * @var array
     */
    protected $allData = array();

    /**
     * Contains array of available virtualservices as Service:id=>tagname
     * 
     * @var array
     */
    protected $allServiceNames = array();

    /**
     * Contains all users that took credit by month
     * 
     * @var aray
     */
    protected $allCredited = array();

    /**
     * Contains all contracts as array contract=>login
     * 
     * @var array
     */
    protected $allContracts = array();

    /**
     * Contains all of user tariffs as login=>tariff array
     * 
     * @var array
     */
    protected $allTariffs = array();

    /**
     * Contains all onus as login=>mac_onu
     * 
     * @var array
     */
    protected $allOnu = array();

    /**
     * Contains all users notes as login=>note
     * 
     * @var array
     */
    protected $allNotes = array();

    /**
     * Contains all users comments as login=>comment
     * 
     * @var array
     */
    protected $allComments = array();

    /**
     * Contains all users phone data as 
     * login[phone] = phone
     * login[mobile] = mobile
     * 
     * @var array
     */
    protected $allPhoneData = array();

    /**
     * Contains all config alter.ini data
     * 
     * @var array
     */
    protected $altCfg = array();

    public function __construct() {
        $this->LoadAddresses();
        $this->LoadRealNames();
        $this->loadAlter();
    }

    protected function LoadAddresses() {
        $this->allAddresses = zb_AddressGetFulladdresslist();
    }

    protected function LoadRealNames() {
        $this->allRealNames = zb_UserGetAllRealnames();
    }

    protected function LoadCashTypes() {
        $this->allCashTypes = zb_CashGetAllCashTypes();
    }

    public function LoadAllData($currentDate, $cityId, $opt, $from = '', $to = '', $by_day = '') {
        switch ($opt) {
            case "payments":
                $query = "SELECT * FROM `payments` WHERE `date` LIKE '" . $currentDate . "%'  AND `login` IN (SELECT `login` FROM `address` WHERE `aptid` IN (SELECT `id` FROM `apt` WHERE `buildid` IN (SELECT `id` FROM `build` WHERE `streetid` IN (SELECT `id` FROM `street` WHERE `cityid`='" . $cityId . "'))))";
                $data = simple_queryall($query);
                if (!empty($data)) {
                    foreach ($data as $each => $io) {
                        $this->allData[$io['id']] = $io;
                    }
                }
                break;
            case "usersearch":
                $query = "SELECT * FROM `users` WHERE `login` IN (SELECT `login` FROM `address` WHERE `aptid` IN (SELECT `id` FROM `apt` WHERE `buildid` IN (SELECT `id` FROM `build` WHERE `streetid` IN (SELECT `id` FROM `street` WHERE `cityid`='" . $cityId . "'))))";
                $data = simple_queryall($query);
                if (!empty($data)) {
                    $this->allData = $data;
                }
                break;
            case "debtors":
                $query = "SELECT * FROM `users` WHERE `cash` < 0 AND `login` IN (SELECT `login` FROM `address` WHERE `aptid` IN (SELECT `id` FROM `apt` WHERE `buildid` IN (SELECT `id` FROM `build` WHERE `streetid` IN (SELECT `id` FROM `street` WHERE `cityid`='" . $cityId . "'))))";
                $data = simple_queryall($query);
                if (!empty($data)) {
                    $this->allData = $data;
                }
                break;
            case "analytics":
                if (empty($by_day)) {
                    $query = "SELECT * FROM `payments` WHERE `date` BETWEEN CAST('" . $from . "' AS DATE) AND CAST('" . $to . "' AS DATE) AND `login` IN (SELECT `login` FROM `address` WHERE `aptid` IN (SELECT `id` FROM `apt` WHERE `buildid` IN (SELECT `id` FROM `build` WHERE `streetid` IN (SELECT `id` FROM `street` WHERE `cityid`='" . $cityId . "'))))";
                } else {
                    $query = "SELECT * FROM `payments` WHERE `date` LIKE '" . $by_day . "%'  AND `login` IN (SELECT `login` FROM `address` WHERE `aptid` IN (SELECT `id` FROM `apt` WHERE `buildid` IN (SELECT `id` FROM `build` WHERE `streetid` IN (SELECT `id` FROM `street` WHERE `cityid`='" . $cityId . "'))))";
                }
                $data = simple_queryall($query);
                if (!empty($data)) {
                    $this->allData = $data;
                }
                break;
        }
    }

    protected function LoadAllServiceNames() {
        $this->allServiceNames = zb_VservicesGetAllNamesLabeled();
    }

    public function LoadAllCredited($date = '') {
        if (empty($date)) {
            $date = $this->GetCurrentDate();
        }
        $query = "SELECT * FROM `zbssclog` WHERE `date` LIKE '" . $date . "%';";
        $allCredited = simple_queryall($query);
        if (!empty($allCredited)) {
            foreach ($allCredited as $eachCredited) {
                $this->allCredited[$eachCredited['login']] = $eachCredited['date'];
            }
        }
    }

    protected function LoadAllContracts() {
        $this->allContracts = array_flip(zb_UserGetAllContracts());
    }

    protected function LoadAllTariffs() {
        $this->allTariffs = zb_TariffsGetAllUsers();
    }

    protected function LoadAllOnu() {
        $query = "SELECT * FROM `pononu`";
        $allonu = simple_queryall($query);
        $onu = array();
        if (!empty($allonu)) {
            foreach ($allonu as $io => $each) {
                $this->allOnu[$each['login']] = $each['mac'];
            }
        }
    }

    protected function LoadAllNotes() {
        $query = "SELECT * FROM `notes`";
        $allNotes = simple_queryall($query);
        if (!empty($allNotes)) {
            foreach ($allNotes as $ia => $eachnote) {
                $this->allNotes[$eachnote['login']] = $eachnote['note'];
            }
        }
    }

    protected function LoadAllComments() {
        $query = "SELECT * FROM `adcomments`";
        $allComments = simple_queryall($query);
        if (!empty($allComments)) {
            foreach ($allComments as $ia => $eachcomment) {
                $this->allComments[$eachcomment['item']] = $eachcomment['text'];
            }
        }
    }

    protected function LoadAllPhoneData() {
        $this->allPhoneData = zb_UserGetAllPhoneData();
    }

    protected function loadAlter() {
        $this->altCfg = rcms_parse_ini_file(CONFIG_PATH . 'alter.ini');
    }

    /**
     * By default getting current date in YYYY-MM format
     * or in case of some parameters returns only YYYY or MM
     * 
     * @param bool $onlyMonth
     * @param bool $onlyYear
     * @return string
     */
    public function GetCurrentDate($onlyMonth = false, $onlyYear = false) {
        if ($onlyMonth) {
            return (date("m"));
        } elseif ($onlyYear) {
            return (date("o"));
        } else {
            return (date("Y-m"));
        }
    }

    /**
     * Returns form for payments by city within some month (by default - current month)
     * 
     * @return string
     */
    public function PaymentsShow() {
        $total = 0;
        $totalPayCount = 0;
        $this->LoadCashTypes();
        $cells = wf_TableCell(__('IDENC'));
        $cells.= wf_TableCell(__('Date'));
        $cells.= wf_TableCell(__('Cash'));
        if ($this->altCfg['FINREP_CONTRACT']) {
            $cells.= wf_TableCell(__('Contract'));
            $this->LoadAllContracts();
        }
        if ($this->altCfg['TRANSLATE_PAYMENTS_NOTES']) {
            $this->LoadAllServiceNames();
        }
        $cells.= wf_TableCell(__('Login'));
        $cells.= wf_TableCell(__('Full address'));
        $cells.= wf_TableCell(__('Real Name'));
        if ($this->altCfg['FINREP_TARIFF']) {
            $cells.=wf_TableCell(__('Tariff'));
            $this->LoadAllTariffs();
        }
        $cells.= wf_TableCell(__('Cash type'));
        $cells.= wf_TableCell(__('Credited'));
        $cells.= wf_TableCell(__('Notes'));
        $cells.= wf_TableCell(__('Admin'));
        $rows = wf_TableRow($cells, 'row1');
        if (!empty($this->allData)) {
            foreach ($this->allData as $io => $eachpayment) {
                if ($this->altCfg['TRANSLATE_PAYMENTS_NOTES']) {
                    $eachpayment['note'] = zb_TranslatePaymentNote($eachpayment['note'], $this->allServiceNames);
                }
                $cells = wf_TableCell(zb_NumEncode($eachpayment['id']));
                $cells.= wf_TableCell($eachpayment['date']);
                $cells.= wf_TableCell($eachpayment['summ']);
                if ($this->altCfg['FINREP_CONTRACT']) {
                    $cells.= wf_TableCell(@$this->allContracts[$eachpayment['login']]);
                }
                $cells.= wf_TableCell(wf_Link('?module=userprofile&username=' . $eachpayment['login'], (web_profile_icon() . ' ' . $eachpayment['login']), false, ''));
                $cells.= wf_TableCell(@$this->allAddresses[$eachpayment['login']]);
                $cells.= wf_TableCell(@$this->allRealNames[$eachpayment['login']]);
                if ($this->altCfg['FINREP_TARIFF']) {
                    $cells.= wf_TableCell(@$this->allTariffs[$eachpayment['login']]);
                }
                $cells.= wf_TableCell(@__($this->allCashTypes[$eachpayment['cashtypeid']]));
                $cells.= wf_TableCell(@$this->allCredited[$eachpayment['login']]);
                $cells.= wf_TableCell($eachpayment['note']);
                $cells.= wf_TableCell($eachpayment['admin']);
                $rows.= wf_TableRow($cells, 'row4');
                $total = $total + $eachpayment['summ'];
                $totalPayCount++;
            }
        }
        $result = wf_TableBody($rows, '100%', '0', 'sortable id');
        $result.=wf_tag('strong') . __('Cash') . ': ' . $total . wf_tag('strong', true) . wf_tag('br');
        $result.=wf_tag('strong') . __('Count') . ': ' . $totalPayCount . wf_tag('strong', true);
        return($result);
    }

    /**
     * Returns form for usersearch and debtors by city
     * 
     * @return string
     */
    public function PerCityDataShow() {
        $total = 0;
        $totalPayCount = 0;
        $colors = new ColorTagging();
        $this->LoadAllOnu();
        $this->LoadAllComments();
        $this->LoadAllNotes();
        $this->LoadAllPhoneData();
        $this->LoadAllCredited();
        $cells = wf_TableCell(__('Full address'));
        $cells.= wf_TableCell(__('Real Name'));
        $cells.= wf_TableCell(__('Credited'));
        $cells.= wf_TableCell(__('Cash'));
        if ($this->altCfg['FINREP_TARIFF']) {
            $cells.=wf_TableCell(__('Tariff'));
            $this->LoadAllTariffs();
        }
        $cells.= wf_TableCell(__('Comment'));
        $cells.= wf_TableCell(__('MAC ONU/ONT'));
        $cells.= wf_TableCell(__('Login'));
        $rows = wf_TableRow($cells, 'row1');
        if (!empty($this->allData)) {
            foreach ($this->allData as $eachdebtor) {
                $userColor = $colors->GetUsersColor($eachdebtor['login']);
                $cell = wf_TableCell(@$this->allAddresses[$eachdebtor['login']]);
                $cell.= wf_TableCell(@$this->allRealNames[$eachdebtor['login']] . "&nbsp&nbsp" . @$this->allPhoneData[$eachdebtor['login']]['mobile']);
                $cell.= wf_TableCell(@$this->allCredited[$eachdebtor['login']]);
                $cell.= wf_TableCell($eachdebtor['Cash']);
                if ($this->altCfg['FINREP_TARIFF']) {
                    $cell.= wf_TableCell($this->allTariffs[$eachdebtor['login']]);
                }
                $cell.= wf_TableCell(@$this->allNotes[$eachdebtor['login']] . "&nbsp&nbsp" . @$this->allComments[$eachdebtor['login']]);
                $cell.= wf_TableCell(@$this->allOnu[$eachdebtor['login']]);
                $cell.= wf_TableCell(wf_Link('?module=userprofile&username=' . $eachdebtor['login'], (web_profile_icon() . ' ' . $eachdebtor['login']), false, ''));
                if (!empty($userColor)) {
                    $style = "background-color:$userColor";
                    $rows.= wf_TableRowStyled($cell, 'row4', $style);
                } else {
                    $rows.= wf_TableRow($cell, 'row4');
                }
                $total = $total + $eachdebtor['Cash'];
                $totalPayCount++;
            }
        }
        $result = wf_TableBody($rows, '100%', '0', 'sortable id');
        $result.=wf_tag('strong') . __('Cash') . ': ' . $total . wf_tag('strong', true) . wf_tag('br');
        $result.=wf_tag('strong') . __('Count') . ': ' . $totalPayCount . wf_tag('strong', true);
        return($result);
    }

    public function AnalyticsShow() {
        $total = 0;
        $totalPayCount = 0;
        $cardPays = 0;
        $paySystems = array();
        $adminPays = array();
        if (!empty($this->allData)) {
            foreach ($this->allData as $io => $eachPayment) {
                if ($eachPayment['admin'] != 'external' && $eachPayment['admin'] != 'openpayz' && $eachPayment['admin'] != 'guest') {
                    if (!isset($adminPays[$eachPayment['admin']])) {
                        $adminPays[$eachPayment['admin']] = $eachPayment['summ'];
                    } else {
                        if ($eachPayment['summ'] > 0) {
                            $adminPays[$eachPayment['admin']] += $eachPayment['summ'];
                        }
                    }
                }
                $findPaySystems = explode(':', $eachPayment['note']);
                if ($findPaySystems[0] == 'OP') {
                    if (!isset($paySystems[$findPaySystems[1]])) {
                        $paySystems[$findPaySystems[1]] = $eachPayment['summ'];
                    } else {
                        $paySystems[$findPaySystems[1]] += $eachPayment['summ'];
                    }
                } elseif ($findPaySystems[0] == 'CARD') {
                    $cardPays += $eachPayment['summ'];
                }
                if ($eachPayment['summ'] > 0) {
                    $total += $eachPayment['summ'];
                }

                $totalPayCount++;
            }
        }

        $cells = __('Admin');
        $cells.= wf_TableCell(__('Type'), '50%');
        $cells.= wf_TableCell(__('Cash'));
        $rows = wf_TableRow($cells, 'row1');
        foreach ($adminPays as $eachAdmin => $summ) {
            $cells = wf_TableCell($eachAdmin);
            $cells.= wf_TableCell($summ);
            $rows.= wf_TableRow($cells, 'row3');
        }
        $form = wf_TableBody($rows, '100%', '0', 'sortable');


        $cells = __('Internet');
        $cells.= wf_TableCell(__('Type'), '50%');
        $cells.= wf_TableCell(__('Cash'));
        $rows = wf_TableRow($cells, 'row1');
        foreach ($paySystems as $eachPaySystem => $summ) {
            $cells = wf_TableCell($eachPaySystem);
            $cells.= wf_TableCell($summ);
            $rows.= wf_TableRow($cells, 'row3');
        }
        $form.= wf_tag('br');
        $form.= wf_TableBody($rows, '100%', '0', 'sortable');

        $cells = __('Cards');
        $cells.= wf_TableCell(__('Type'), '50%');
        $cells.= wf_TableCell(__('Cash'));
        $rows = wf_TableRow($cells, 'row1');
        $cells = wf_TableCell(__("Cards"));
        $cells.= wf_TableCell($cardPays);
        $rows.= wf_TableRow($cells, 'row3');


        $form.= wf_tag('br');
        $form.= wf_TableBody($rows, '100%', '0', 'sortable');
        $form.=wf_tag('strong') . __('Cash') . ': ' . $total . wf_tag('strong', true) . wf_tag('br');
        $form.=wf_tag('strong') . __('Count') . ': ' . $totalPayCount . wf_tag('strong', true);
        return($form);
    }

    public function CitySelector($admin, $action) {
        $form = wf_tag('form', false, '', 'action="" method="GET"');
        $form.= wf_tag('table', false, '', 'width="100%" border="0"');
        if (!isset($_GET['citysel'])) {
            $cells = wf_TableCell(__('City'), '40%');
            $cells.= wf_HiddenInput("module", "per_city_action");
            $cells.= wf_HiddenInput("action", $action);
            if (isset($_GET['monthsel'])) {
                $cells.= wf_HiddenInput('monthsel', $_GET['monthsel']);
            }
            if (isset($_GET['from_date'])) {
                $cells.= wf_HiddenInput("from_date", $_GET['from_date']);
            }
            if (isset($_GET['to_date'])) {
                $cells.= wf_HiddenInput("to_date", $_GET['to_date']);
            }
            if (isset($_GET['by_day'])) {
                $cells.= wf_HiddenInput("by_day", $_GET['by_day']);
            }
            $cells.= wf_TableCell($this->CitySelectorPermissioned($admin));
            $form.= wf_TableRow($cells, 'row3');
        } else {
            $cityname = zb_AddressGetCityData($_GET['citysel']);
            $cityname = $cityname['cityname'];
            $cells = wf_TableCell(__('City'), '40%');
            $cells.= wf_HiddenInput("module", "per_city_action");
            $cells.= wf_HiddenInput("action", $action);
            if (isset($_GET['monthsel'])) {
                $cells.= wf_HiddenInput('monthsel', $_GET['monthsel']);
            }
            if (isset($_GET['from_date'])) {
                $cells.= wf_HiddenInput("from_date", $_GET['from_date']);
            }
            if (isset($_GET['to_date'])) {
                $cells.= wf_HiddenInput("to_date", $_GET['to_date']);
            }
            if (isset($_GET['by_day'])) {
                $cells.= wf_HiddenInput("by_day", $_GET['by_day']);
            }
            $cells.= wf_TableCell(web_ok_icon() . ' ' . $cityname . wf_HiddenInput('citysearch', $_GET['citysel']));
            $cells.= wf_TableCell(wf_Submit(__('Find')));
            $form.= wf_TableRow($cells, 'row3');
        }
        $form.=wf_tag('table', true);
        $form.=wf_tag('form', true);
        return($form);
    }

    /**
     * Returns auto-clicking city selector
     * 
     * @return string
     */
    protected function CitySelectorPermissioned($admin) {
        $allcity = array();
        $tmpCity = zb_AddressGetCityAllData();
        $allcity['-'] = '-'; //placeholder
        if (!empty($tmpCity)) {
            if (file_exists(self::PERMISSION_PATH . $admin)) {
                $data = file_get_contents(self::PERMISSION_PATH . $admin);
                $eachId = explode(",", $data);
                foreach ($tmpCity as $io => $each) {
                    $check = false;
                    foreach ($eachId as $id) {
                        if ($each['id'] == $id) {
                            $check = true;
                        }
                    }
                    if ($check) {
                        $allcity[$each['id']] = $each['cityname'];
                    }
                }
            } else {
                foreach ($tmpCity as $io => $each) {
                    $allcity[$each['id']] = $each['cityname'];
                }
            }
        }
        $selector = wf_SelectorAC('citysel', $allcity, '', '', false);
        $selector.= wf_tag('a', false, '', 'href="?module=city" target="_BLANK"') . web_city_icon() . wf_tag('a', true);
        return ($selector);
    }

    /**
     * Returns check box cities selecor
     * 
     * @return string
     */
    public function CityChecker($admin) {
        $tmpCity = zb_AddressGetCityAllData();
        $checker = '';
        $i = 0;
        if (!empty($tmpCity)) {
            if (file_exists(self::PERMISSION_PATH . $admin)) {
                $data = file_get_contents(self::PERMISSION_PATH . $admin);
                if (!empty($data)) {
                    $eachId = explode(",", $data);
                    foreach ($tmpCity as $io => $each) {
                        $checked = false;
                        foreach ($eachId as $id) {
                            if ($each['id'] == $id) {
                                $checked = true;
                            }
                        }
                        $checker.= $this->CheckInput("city[$i]", $each['cityname'], $each['id'], true, $checked);
                        $i++;
                    }
                }
            } else {
                foreach ($tmpCity as $io => $each) {
                    $checker.= $this->CheckInput("city[$i]", $each['cityname'], $each['id'], true, false);
                    $i++;
                }
            }
            $checker.= wf_delimiter(0);
            $checker.= wf_Submit(__('Send'));
        }
        $form = wf_Form('', 'POST', $checker);
        return ($form);
    }

    /**
     * Returns available administrators list
     * 
     * @return string
     */
    public function ListAdmins() {
        $alladmins = rcms_scandir(USERS_PATH);
        $cells = wf_TableCell(__('Admin'));
        $cells.= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');
        if (!empty($alladmins)) {
            foreach ($alladmins as $eachadmin) {
                $actions = wf_Link(self::MODULE_NAME . '&action=permission&edit=' . $eachadmin, web_edit_icon('Rights'));
                $actions.= wf_Link(self::MODULE_NAME . '&action=permission&delete=' . $eachadmin, web_delete_icon());
                $cells = wf_TableCell($eachadmin);
                $cells.= wf_TableCell($actions);
                $rows.= wf_TableRow($cells, 'row3');
            }
        }
        $form = wf_TableBody($rows, '100%', '0', 'sortable');
        return($form);
    }

    /**
     * Return check box Web From element 
     *
     * @param string  $name name of element
     * @param string  $label text label for input
     * @param bool    $br append new line
     * @param bool    $checked is checked?
     * @return  string
     *
     */
    protected function CheckInput($name, $label = '', $value = '', $br = false, $checked = false) {
        $inputid = wf_InputId();
        if ($br) {
            $newline = '<br>';
        } else {
            $newline = '';
        }
        if ($checked) {
            $check = 'checked=""';
        } else {
            $check = '';
        }
        if ($value != '') {
            $result = '<input type="checkbox" id="' . $inputid . '" name="' . $name . '" ' . $check . ' value=' . $value . ' />';
        } else {
            $result = '<input type="checkbox" id="' . $inputid . '" name="' . $name . '" ' . $check . ' />';
        }

        if ($label != '') {
            $result.=' <label for="' . $inputid . '">' . __($label) . '</label>' . "\n";
            ;
        }
        $result.=$newline . "\n";
        return ($result);
    }

    public function CheckRigts($cityID, $admin) {
        $result = false;
        if (file_exists(self::PERMISSION_PATH . $admin)) {
            $data = file_get_contents(self::PERMISSION_PATH . $admin);
            $data = explode(",", $data);
            foreach ($data as $each) {
                if ($each == $cityID) {
                    $result = true;
                }
            }
        } else {
            return true;
        }
        return ($result);
    }

    public function ChooseDateForm($action) {
        $inputs = wf_HiddenInput("module", "per_city_action");
        $inputs.= wf_HiddenInput("action", $action);
        if (isset($_GET['citysearch'])) {
            $inputs.= wf_HiddenInput("citysearch", $_GET['citysearch']);
        }
        if (isset($_GET['citysel'])) {
            $inputs.= wf_HiddenInput("citysel", $_GET['citysel']);
        }
        $inputs.= wf_DatePicker('from_date', true);
        $inputs.= __('From');
        $inputs.= wf_tag('br');
        $inputs.= wf_DatePicker('to_date', true);
        $inputs.= __('To');
        $inputs.= wf_delimiter();
        $inputs.= wf_Submit(__('Send'));
        $formBetween = wf_Form('', 'GET', $inputs);
        $cells = wf_TableCell($formBetween);
        $inputs = wf_HiddenInput("module", "per_city_action");
        $inputs.= wf_HiddenInput("action", $action);
        if (isset($_GET['citysearch'])) {
            $inputs.= wf_HiddenInput("citysearch", $_GET['citysearch']);
        }
        if (isset($_GET['citysel'])) {
            $inputs.= wf_HiddenInput("citysel", $_GET['citysel']);
        }
        $inputs.= wf_DatePicker('by_day', true);
        $inputs.= __('By day');
        $inputs.= wf_delimiter();
        $inputs.= wf_Submit(__('Send'));
        $formByDate = wf_Form('', 'GET', $inputs);
        $cells.= wf_TableCell($formByDate);
        $rows = wf_TableRow($cells);
        $result = wf_TableBody($rows, "100%", '0', '');
        return($result);
    }

}

function GetAllCreditedUsers() {
    $date = date("Y-m");
    $query = "SELECT * FROM `zbssclog` WHERE `date` LIKE '" . $date . "%';";
    $allCredited = simple_queryall($query);
    if (!empty($allCredited)) {
        foreach ($allCredited as $eachCredited) {
            $creditedUsers[$eachCredited['login']] = $eachCredited['date'];
        }
        return($creditedUsers);
    } else {
        return(false);
    }
}

function web_ReportCityShowPrintable($titles, $keys, $alldata, $address = 0, $realnames = 0, $rowcount = 0) {
    $alter_conf = rcms_parse_ini_file(CONFIG_PATH . 'alter.ini');
    $report_name = wf_tag('h2') . __("Debtors by city") . wf_tag('h2', true);
    $allrealnames = zb_UserGetAllRealnames();
    $alladdress = zb_AddressGetFulladdresslist();
    if ($alter_conf['FINREP_TARIFF']) {
        $alltariffs = zb_TariffsGetAllUsers();
    }
    $allphonedata = zb_UserGetAllPhoneData();
    $allnotes = GetAllNotes();
    $allcomments = GetAllComments();
    $allonu = GetAllOnu();
    $allCredited = GetAllCreditedUsers();

    $i = 0;
    $style = '
        <script src="modules/jsc/sorttable.js" language="javascript"></script>
        <style type="text/css">
            table.printrm tbody {
                counter-reset: sortabletablescope;
                border-width: 1px;
                padding: 1px;
                border-style: dashed;
                border-color: gray;
                background-color: white;
                -moz-border-radius: ;
            }
            table.printrm thead tr::before {
                border-width: 1px;
                padding: 1px;
                border-style: dashed;
                border-color: gray;
                background-color: white;
                -moz-border-radius: ;
                text-align: center;
                vertical-align: middle;
                content: "ID";
                display: table-cell;
            }
            table.printrm tbody tr::before {
                border-width: 1px;
                padding: 1px;
                border-style: dashed;
                border-color: gray;
                background-color: white;
                -moz-border-radius: ;
                text-align: center;
                vertical-align: middle;
                content: counter(sortabletablescope);
                counter-increment: sortabletablescope;
                display: table-cell;
            }
            table.printrm {
                border-width: 1px;
                border-spacing: 2px;
                border-style: outset;
                border-color: gray;
                border-collapse: separate;
                background-color: white;
            }
            table.printrm th {
                border-width: 1px;
                padding: 1px;
                border-style: dashed;
                border-color: gray;
                background-color: white;
                -moz-border-radius: ;
            }
            table.printrm td {
                border-width: 1px;
                padding: 1px;
                border-style: dashed;
                border-color: gray;
                background-color: white;
                -moz-border-radius: ;
            }
        </style>';
    $cells = '';
    if ($address) {
        $cells.= wf_TableCell(__('Full address'));
    }
    if ($realnames) {
        $cells.= wf_TableCell(__('Real Name'));
    }
    foreach ($titles as $eachtitle) {
        $cells.= wf_TableCell(__($eachtitle));
    }

    $rows = wf_TableRow($cells);
    if (!empty($alldata)) {
        foreach ($alldata as $io => $eachdata) {
            $i++;
            $cells = '';
            if ($address) {
                $cells.= wf_TableCell(@$alladdress[$eachdata['login']]);
            }
            if ($realnames) {
                $cells.= wf_TableCell(@$allrealnames[$eachdata['login']] . "&nbsp" . @$allphonedata[$eachdata['login']]['mobile']);
            }
            if ($alter_conf['FINREP_TARIFF']) {
                $cells.= wf_TableCell(@$alltariffs[$eachdata['login']]);
            }
            $cells.= wf_TableCell(@$allnotes[$eachdata['login']] . " " . @$allcomments[$eachdata['login']]);
            $cells.= wf_TableCell(@$allonu[$eachdata['login']]);
            $cells.= wf_TableCell(@$allCredited[$eachdata['login']]);
            foreach ($keys as $eachkey) {
                if (array_key_exists($eachkey, $eachdata)) {
                    $cells.= wf_TableCell($eachdata[$eachkey]);
                }
            }
            $rows.=wf_TableRow($cells);
        }
    }

    $result = wf_TableBody($rows, '100%', '0', 'sortable printrm');
    if ($rowcount) {
        $result.=wf_tag('strong') . __('Total') . ': ' . $i . wf_tag('strong', true);
    }
    print($style . $report_name . $result);
    die();
}

function web_MonthSelector() {
    $mcells = '';
    $allmonth = months_array_localized();
    foreach ($allmonth as $io => $each) {
        if (isset($_GET['citysel'])) {
            $mcells.= wf_TableCell(wf_Link("?module=per_city_action&action=city_payments&monthsel=" . $io . "&citysel=" . $_GET['citysel'], $each, false, 'ubButton'));
        } elseif (isset($_GET['citysearch'])) {
            $mcells.= wf_TableCell(wf_Link("?module=per_city_action&action=city_payments&monthsel=" . $io . "&citysearch=" . $_GET['citysearch'], $each, false, 'ubButton'));
        } else {
            $mcells.= wf_TableCell(wf_Link("?module=per_city_action&action=city_payments&monthsel=" . $io, $each, false, 'ubButton'));
        }
    }
    return ($mcells);
}

function web_ReportDebtorsShowPrintable($titles, $keys, $alldata, $address = 0, $realnames = 0, $rowcount = 0) {
    $alter_conf = rcms_parse_ini_file(CONFIG_PATH . 'alter.ini');
    $report_name = wf_tag('h2') . __("Debtors by city") . wf_tag('h2', true);
    $allrealnames = zb_UserGetAllRealnames();
    $alladdress = zb_AddressGetFulladdresslist();
    if ($alter_conf['FINREP_TARIFF']) {
        $alltariffs = zb_TariffsGetAllUsers();
    }
    $allphonedata = zb_UserGetAllPhoneData();
    $allnotes = GetAllNotes();
    $allcomments = GetAllComments();
    $allonu = GetAllOnu();
    $allCredited = GetAllCreditedUsers();
    $i = 0;
    $style = '
        <script src="modules/jsc/sorttable.js" language="javascript"></script>
        <style type="text/css">
            table.printrm tbody {
                counter-reset: sortabletablescope;
                border-width: 1px;
                padding: 1px;
                border-style: dashed;
                border-color: gray;
                background-color: white;
                -moz-border-radius: ;
            }
            table.printrm thead tr::before {
                border-width: 1px;
                padding: 1px;
                border-style: dashed;
                border-color: gray;
                background-color: white;
                -moz-border-radius: ;
                text-align: center;
                vertical-align: middle;
                content: "ID";
                display: table-cell;
            }
            table.printrm tbody tr::before {
                border-width: 1px;
                padding: 1px;
                border-style: dashed;
                border-color: gray;
                background-color: white;
                -moz-border-radius: ;
                text-align: center;
                vertical-align: middle;
                content: counter(sortabletablescope);
                counter-increment: sortabletablescope;
                display: table-cell;
            }
            table.printrm {
                border-width: 1px;
                border-spacing: 2px;
                border-style: outset;
                border-color: gray;
                border-collapse: separate;
                background-color: white;
            }
            table.printrm th {
                border-width: 1px;
                padding: 1px;
                border-style: dashed;
                border-color: gray;
                background-color: white;
                -moz-border-radius: ;
            }
            table.printrm td {
                border-width: 1px;
                padding: 1px;
                border-style: dashed;
                border-color: gray;
                background-color: white;
                -moz-border-radius: ;
            }
        </style>';
    $cells = '';
    if ($address) {
        $cells.=wf_TableCell(__('Full address'));
    }
    if ($realnames) {
        $cells.=wf_TableCell(__('Real Name'));
    }
    foreach ($titles as $eachtitle) {
        $cells.= wf_TableCell(__($eachtitle));
    }
    $rows = wf_TableRow($cells);

    if (!empty($alldata)) {
        foreach ($alldata as $io => $eachdata) {
            $i++;
            $cells = '';
            if ($address) {
                $cells.=wf_TableCell(@$alladdress[$eachdata['login']]);
            }
            if ($realnames) {
                $cells.=wf_TableCell(@$allrealnames[$eachdata['login']] . "&nbsp " . @$allphonedata[$eachdata['login']]['mobile']);
            }
            if ($alter_conf['FINREP_TARIFF']) {
                $cells.=wf_TableCell(@$alltariffs[$eachdata['login']]);
            }
            $cells.= wf_TableCell(@$allnotes[$eachdata['login']] . " " . @$allcomments[$eachdata['login']]);
            $cells.= wf_TableCell(@$allonu[$eachdata['login']]);
            $cells.= wf_TableCell(@$allCredited[$eachdata['login']]);
            foreach ($keys as $eachkey) {
                if (array_key_exists($eachkey, $eachdata)) {
                    $cells.=wf_TableCell($eachdata[$eachkey]);
                }
            }
            $rows.=wf_TableRow($cells);
        }
    }
    $result = wf_TableBody($rows, '100%', '0', 'sortable printrm');
    if ($rowcount) {
        $result.='<strong>' . __('Total') . ': ' . $i . '</strong>';
    }
    print($style . $report_name . $result);
    die();
}
