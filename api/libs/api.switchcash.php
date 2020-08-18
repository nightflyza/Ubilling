<?php

class SwitchCash {

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains all available switches financial data as switchId=>data
     *
     * @var array
     */
    protected $allCashData = array();

    /**
     * Contains database abstraction layer for financial data
     *
     * @var object
     */
    protected $swCashDb = '';

    /**
     * System message helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Filestorage instance object placeholder
     *
     * @var object
     */
    protected $filestorage = '';

    /**
     * Contains all user data as login=>userdata
     *
     * @var array
     */
    protected $allUsersData = array();

    /**
     * 
     * Contains all switch assigns as login=>assignData
     *
     * @var array
     */
    protected $allSwitchAssigns = array();

    /**
     * Contains all switches that contains report mark as switchId=>switchData
     *
     * @var array
     */
    protected $allReportSwitches = array();

    /**
     * Contains all available tariff prices as tariffname=>Fee
     *
     * @var array
     */
    protected $allTariffPrices = array();

    /**
     * Contains bad colored switches count
     *
     * @var int
     */
    protected $counterBad = 0;

    /**
     * Contains good colored switches count
     *
     * @var int
     */
    protected $counterGood = 0;

    /**
     * Contains equal colored switches count
     *
     * @var int
     */
    protected $counterEqual = 0;

    /**
     * Some static defines etc
     */
    const TABLE_FINANCE = 'swcash';
    const FILESTORAGE_SCOPE = 'SWCASH';
    const REPORT_MASK = 'SWCASH';
    const URL_ME = '?module=swcash';
    const URL_SWITCHPROFILE = '?module=switches&edit=';
    const ROUTE_EDIT = 'switchid';
    const ROUTE_REPORT = 'renderreport';
    const ROUTE_USERS = 'renderswusers';
    const PROUTE_CREATE = 'createswitchid';
    const PROUTE_SAVE = 'saveswitchid';
    const PROUTE_RECORD = 'swcashrecordid';
    const PROUTE_PLACECONTRACT = 'newplacecontract';
    const PROUTE_PLACEPRICE = 'newplaceprice';
    const PROUTE_POWERCONTRACT = 'newpowercontract';
    const PROUTE_POWERPRICE = 'newpoweprice';
    const PROUTE_TRANSPORTCONTRACT = 'newtransportcontract';
    const PROUTE_TRANSPORTPRICE = 'newtransportprice';
    const PROUTE_SWITCHPRICE = 'newswitchprice';
    const PROUTE_SWITCHDATE = 'newswitchdate';
    const COLOR_BAD = 'bc0000';
    const COLOR_GOOD = '007603';
    const COLOR_EQUAL = 'f47900';

    /**
     *   ___     ___
     *  (o o)   (o o)
     * (  V  ) (  V  )
     * /--m-m- /--m-m-
     */
    public function __construct() {
        $this->loadAlter();
        $this->initMessages();
        $this->initFilestorage();
        $this->initDatabase();
        $this->loadAllCashData();
    }

    /**
     * Inits database abstraction layer for further usage
     * 
     * @return void
     */
    protected function initDatabase() {
        $this->swCashDb = new NyanORM(self::TABLE_FINANCE);
    }

    /**
     * Inits system message helper object
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Inits filestorage instance if enabled
     * 
     * @return void
     */
    protected function initFilestorage() {
        if (@$this->altCfg['FILESTORAGE_ENABLED']) {
            $this->filestorage = new FileStorage(self::FILESTORAGE_SCOPE);
        }
    }

    /**
     * Loads system alter.ini config into protected prop
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
     * Performs loading and preprocessing of available financial data
     * 
     * @return void
     */
    protected function loadAllCashData() {
        $this->allCashData = $this->swCashDb->getAll('switchid');
    }

    /**
     * Loads all data required for basic report. 
     * Must be called manually to save some resources.
     * 
     * @return void
     */
    protected function loadReportData() {
        $this->loadUserData();
        $this->loadTariffPrices();
        $this->loadSwitchesData();
        $this->loadSwitchPortAssigns();
    }

    /**
     * Loads all users data into protected prop
     * 
     * @return void
     */
    protected function loadUserData() {
        $this->allUsersData = zb_UserGetAllDataCache();
    }

    /**
     * Loads all available tariff fees
     * 
     * @return void
     */
    protected function loadTariffPrices() {
        $this->allTariffPrices = zb_TariffGetPricesAll();
    }

    /**
     * Loads switches data into protected property
     * 
     * @return void
     */
    protected function loadSwitchesData() {
        $this->allReportSwitches = zb_SwitchesGetAllMask(self::REPORT_MASK);
    }

    /**
     * Loads all available switchport assigns into protected prop
     * 
     * @return void
     */
    protected function loadSwitchPortAssigns() {
        $this->allSwitchAssigns = zb_SwitchesGetAssignsAll();
    }

    /**
     * Checks have some switch some financial data or not?
     * 
     * @param int $switchId
     * 
     * @return bool
     */
    public function haveFinancialData($switchId) {
        $result = (isset($this->allCashData[$switchId])) ? true : false;
        return($result);
    }

    /**
     * Creates new database record on request
     * 
     * @return void/string on error
     */
    public function catchCreate() {
        $result = '';
        if (ubRouting::checkPost(self::PROUTE_CREATE)) {
            $switchId = ubRouting::post(self::PROUTE_CREATE, 'int');
            $placecontract = ubRouting::post(self::PROUTE_PLACECONTRACT, 'mres');
            $placeprice = ubRouting::post(self::PROUTE_PLACEPRICE, 'mres');
            $powercontract = ubRouting::post(self::PROUTE_POWERCONTRACT, 'mres');
            $powerprice = ubRouting::post(self::PROUTE_POWERPRICE, 'mres');
            $transportcontract = ubRouting::post(self::PROUTE_TRANSPORTCONTRACT, 'mres');
            $transportprice = ubRouting::post(self::PROUTE_TRANSPORTPRICE, 'mres');
            $switchprice = ubRouting::post(self::PROUTE_SWITCHPRICE, 'mres');
            $switchdate = ubRouting::post(self::PROUTE_SWITCHDATE, 'mres');

            if (zb_checkDate($switchdate)) {
                $this->swCashDb->data('switchid', $switchId);
                $this->swCashDb->data('placecontract', $placecontract);
                $this->swCashDb->data('placeprice', $placeprice);
                $this->swCashDb->data('powercontract', $powercontract);
                $this->swCashDb->data('powerprice', $powerprice);
                $this->swCashDb->data('transportcontract', $transportcontract);
                $this->swCashDb->data('transportprice', $transportprice);
                $this->swCashDb->data('switchprice', $switchprice);
                $this->swCashDb->data('switchdate', $switchdate);
                $this->swCashDb->create();
                log_register('SWCASH CREATE SWID [' . $switchId . ']');
            } else {
                $result .= __('Wrong date format');
            }
        }

        return($result);
    }

    /**
     * Saves database record on request
     * 
     * @return void/string on error
     */
    public function catchSave() {
        $result = '';
        if (ubRouting::checkPost(self::PROUTE_SAVE) AND ubRouting::checkPost(self::PROUTE_RECORD)) {
            $switchId = ubRouting::post(self::PROUTE_SAVE, 'int');
            $recordId = ubRouting::post(self::PROUTE_RECORD, 'int');

            $placecontract = ubRouting::post(self::PROUTE_PLACECONTRACT, 'mres');
            $placeprice = ubRouting::post(self::PROUTE_PLACEPRICE, 'mres');
            $powercontract = ubRouting::post(self::PROUTE_POWERCONTRACT, 'mres');
            $powerprice = ubRouting::post(self::PROUTE_POWERPRICE, 'mres');
            $transportcontract = ubRouting::post(self::PROUTE_TRANSPORTCONTRACT, 'mres');
            $transportprice = ubRouting::post(self::PROUTE_TRANSPORTPRICE, 'mres');
            $switchprice = ubRouting::post(self::PROUTE_SWITCHPRICE, 'mres');
            $switchdate = ubRouting::post(self::PROUTE_SWITCHDATE, 'mres');

            if (zb_checkDate($switchdate)) {
                $this->swCashDb->where('id', '=', $recordId);
                $this->swCashDb->data('placecontract', $placecontract);
                $this->swCashDb->data('placeprice', $placeprice);
                $this->swCashDb->data('powercontract', $powercontract);
                $this->swCashDb->data('powerprice', $powerprice);
                $this->swCashDb->data('transportcontract', $transportcontract);
                $this->swCashDb->data('transportprice', $transportprice);
                $this->swCashDb->data('switchprice', $switchprice);
                $this->swCashDb->data('switchdate', $switchdate);
                $this->swCashDb->save();
                log_register('SWCASH EDIT SWID [' . $switchId . ']');
            } else {
                $result .= __('Wrong date format');
            }
        }

        return($result);
    }

    /**
     * Renders switch financial data creation form
     * 
     * @param int $switchId
     * 
     * @return string
     */
    public function renderCreateForm($switchId) {
        $result = '';

        $switchId = ubRouting::filters($switchId, 'int');
        //creation flag
        $inputs = wf_HiddenInput(self::PROUTE_CREATE, $switchId);
        //placement data
        $inputs .= wf_TextInput(self::PROUTE_PLACECONTRACT, __('Placement contract'), '', true, 20);
        $inputs .= wf_TextInput(self::PROUTE_PLACEPRICE, __('Placement price') . ' / ' . __('month'), '0', true, 5, 'finance');
        //power data
        $inputs .= wf_TextInput(self::PROUTE_POWERCONTRACT, __('Power contract'), '', true, 20);
        $inputs .= wf_TextInput(self::PROUTE_POWERPRICE, __('Power price') . ' / ' . __('month'), '0', true, 5, 'finance');
        //transport data
        $inputs .= wf_TextInput(self::PROUTE_TRANSPORTCONTRACT, __('Transport contract'), '', true, 20);
        $inputs .= wf_TextInput(self::PROUTE_TRANSPORTPRICE, __('Transport price') . ' / ' . __('month'), '0', true, 5, 'finance');
        //switch pricing and installation date
        $inputs .= wf_TextInput(self::PROUTE_SWITCHPRICE, __('Switch price'), '0', true, 5, 'finance');
        $inputs .= wf_DatePickerPreset(self::PROUTE_SWITCHDATE, curdate(), true) . ' ' . __('Switch installation date');
        $inputs .= wf_delimiter();
        $inputs .= wf_Submit(__('Create'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');

        return($result);
    }

    /**
     * Renders switch financial data editing form
     * 
     * @param int $switchId
     * 
     * @return string
     */
    public function renderEditForm($switchId) {
        $result = '';

        $switchId = ubRouting::filters($switchId, 'int');
        if (isset($this->allCashData[$switchId])) {
            $switchData = $this->allCashData[$switchId];
            //save flag and record id
            $inputs = wf_HiddenInput(self::PROUTE_SAVE, $switchId);
            $inputs .= wf_HiddenInput(self::PROUTE_RECORD, $switchData['id']);

            //placement data
            $inputs .= wf_TextInput(self::PROUTE_PLACECONTRACT, __('Placement contract'), $switchData['placecontract'], true, 20);
            $inputs .= wf_TextInput(self::PROUTE_PLACEPRICE, __('Placement price') . ' / ' . __('month'), $switchData['placeprice'], true, 5, 'finance');
            if (!empty($this->filestorage)) {
                $this->filestorage->setItemid('place' . $switchId);
                $inputs .= $this->filestorage->renderFilesPreview(true);
            }
            //power data
            $inputs .= wf_TextInput(self::PROUTE_POWERCONTRACT, __('Power contract'), $switchData['powercontract'], true, 20);
            $inputs .= wf_TextInput(self::PROUTE_POWERPRICE, __('Power price') . ' / ' . __('month'), $switchData['powerprice'], true, 5, 'finance');
            if (!empty($this->filestorage)) {
                $this->filestorage->setItemid('power' . $switchId);
                $inputs .= $this->filestorage->renderFilesPreview(true);
            }
            //transport data
            $inputs .= wf_TextInput(self::PROUTE_TRANSPORTCONTRACT, __('Transport contract'), $switchData['transportcontract'], true, 20);
            $inputs .= wf_TextInput(self::PROUTE_TRANSPORTPRICE, __('Transport price') . ' / ' . __('month'), $switchData['transportprice'], true, 5, 'finance');
            if (!empty($this->filestorage)) {
                $this->filestorage->setItemid('transport' . $switchId);
                $inputs .= $this->filestorage->renderFilesPreview(true);
            }
            //switch pricing and installation date
            $inputs .= wf_TextInput(self::PROUTE_SWITCHPRICE, __('Switch price'), $switchData['switchprice'], true, 5, 'finance');
            $inputs .= wf_DatePickerPreset(self::PROUTE_SWITCHDATE, $switchData['switchdate'], true) . ' ' . __('Switch installation date');
            $inputs .= wf_delimiter();
            $inputs .= wf_Submit(__('Save'));
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        } else {
            $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': EX_NO_SWCASHDATA', 'error');
        }
        return($result);
    }

    /**
     * Returns switch month price for the first year
     * 
     * @param int $switchId
     * 
     * @return float
     */
    protected function getSwitchPrice($switchId) {
        $result = 0;
        if (isset($this->allCashData[$switchId])) {
            $curDateTimestamp = time();
            $switchCashData = $this->allCashData[$switchId];
            if ($switchCashData['switchprice'] > 0) {
                if (!empty($switchCashData['switchdate'])) {
                    $switchSetupTimestamp = strtotime($switchCashData['switchdate']);
                    $timeFromSetup = $curDateTimestamp - $switchSetupTimestamp;
                    $daysFromSetup = round($timeFromSetup / 86400);
                    if ($daysFromSetup < 365) { //switch installed less than one year ago
                        $result = $switchCashData['switchprice'] / 12; //price per month
                    }
                }
            }
        }
        return($result);
    }

    /**
     * Returns total of switch expenses per month
     * 
     * @param int $switchId
     * 
     * @return float
     */
    protected function getSwitchExpenses($switchId) {
        $result = 0;
        if (isset($this->allCashData[$switchId])) {
            $switchCashData = $this->allCashData[$switchId];
            $result += $this->getSwitchPrice($switchId); //switch price for the first year
            $result += $switchCashData['placeprice']; //placement price
            $result += $switchCashData['powerprice']; //power price
            $result += $switchCashData['transportprice']; //transport price
        }
        return($result);
    }

    /**
     * Returns total switch profit per month
     * 
     * @param int $switchId
     * 
     * @return float
     */
    protected function getSwitchProfit($switchId) {
        $result = 0;
        if (!empty($this->allSwitchAssigns)) {
            foreach ($this->allSwitchAssigns as $eachLogin => $assignData) {
                if ($assignData['switchid'] == $switchId) {
                    if (isset($this->allUsersData[$eachLogin])) {
                        $userTariff = $this->allUsersData[$eachLogin]['Tariff'];
                        if (isset($this->allTariffPrices[$userTariff])) {
                            $userFee = $this->allTariffPrices[$userTariff];
                            $result += $userFee;
                        }
                    }
                }
            }
        }
        return($result);
    }

    /**
     * Colorize switch name based on profit
     * 
     * @param string $switchName
     * @param float $expenses
     * @param float $profit
     * 
     * @return string
     */
    protected function colorizeSwitch($switchName, $expenses, $profit) {
        $result = $switchName;
        $textColor = '';

        if ($profit > $expenses) {
            $textColor = self::COLOR_GOOD;
            $this->counterGood++;
        }
        if ($profit < $expenses) {
            $textColor = self::COLOR_BAD;
            $this->counterBad++;
        }
        if ($profit == $expenses) {
            $textColor = self::COLOR_EQUAL;
            $this->counterEqual++;
        }

        if ($textColor) {
            $result = wf_tag('font', false, '', 'color="#' . $textColor . '"') . $switchName . wf_tag('font', true);
        }
        return($result);
    }

    /**
     * Renders chart of switches profitability percents
     * 
     * @return string
     */
    protected function renderCharts() {
        $result = '';
        if ($this->counterBad OR $this->counterGood OR $this->counterEqual) {
            $chartOpts = "chartArea: {  width: '100%', height: '80%' }, legend : {position: 'right', textStyle: {fontSize: 12 }},  pieSliceText: 'value-and-percentage',";
            $chartData = array(
                __('Good payback') => $this->counterGood,
                __('Bad payback') => $this->counterBad,
                __('Equal') => $this->counterEqual,
            );
            $result .= wf_gcharts3DPie($chartData, __('Payback'), '400px', '300px', $chartOpts);
        }
        return($result);
    }

    /**
     * Renders basic report with switches profitability
     * 
     * @return string
     */
    public function renderBasicReport() {
        $result = '';
        //loading all data required for this report
        $this->loadReportData();

        if (!empty($this->allReportSwitches)) {
            $cells = wf_TableCell(__('Address'));
            $cells .= wf_TableCell(__('Monthly expenses'));
            $cells .= wf_TableCell(__('Monthly profit'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->allReportSwitches as $eachSwitchId => $eachSwitchData) {
                $switchExpenses = $this->getSwitchExpenses($eachSwitchId);
                $switchProfit = $this->getSwitchProfit($eachSwitchId);
                $switchName = (!empty($eachSwitchData['location'])) ? $eachSwitchData['location'] : $eachSwitchData['ip'];
                $cells = wf_TableCell($this->colorizeSwitch($switchName, $switchExpenses, $switchProfit));
                $cells .= wf_TableCell($switchExpenses);
                $cells .= wf_TableCell($switchProfit);
                $swControls = '';
                $swControls .= wf_Link(self::URL_SWITCHPROFILE . $eachSwitchId, web_edit_icon(__('Switch'))) . ' ';
                $swControls .= wf_Link(self::URL_ME . '&' . self::ROUTE_EDIT . '=' . $eachSwitchId, wf_img('skins/ukv/dollar.png', __('Financial data'))) . ' ';
                $swControls .= wf_Link(self::URL_ME . '&' . self::ROUTE_USERS . '=' . $eachSwitchId, web_profile_icon(__('Users'))) . ' ';
                $cells .= wf_TableCell($swControls);
                $rows .= wf_TableRow($cells, 'row5');
            }
            $result .= wf_TableBody($rows, '100%', 0, 'sortable');

            //charts rendering
            $result .= $this->renderCharts();
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        return($result);
    }

    /**
     * Renders users list assigned for some switch
     * 
     * @param int $switchId
     * 
     * @return string
     */
    public function renderUsersReport($switchId) {
        $result = '';
        //loading all data required for this report
        $this->loadReportData();
        $switchId = ubRouting::filters($switchId, 'int');
        $usersTmp = array();
        if (!empty($this->allCashData)) {
            if (!empty($this->allSwitchAssigns)) {
                foreach ($this->allSwitchAssigns as $eachLogin => $eachAssignData) {
                    if ($eachAssignData['switchid'] == $switchId) {
                        if (isset($this->allUsersData[$eachLogin])) {
                            $usersTmp[$eachLogin] = $eachLogin;
                        }
                    }
                }
            }
        }

        $result .= web_UserCorpsArrayShower($usersTmp, $switchId);
        return($result);
    }

}
