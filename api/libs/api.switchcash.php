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
     * Some static defines etc
     */
    const TABLE_FINANCE = 'swcash';
    const FILESTORAGE_SCOPE = 'SWCASH';
    const URL_ME = '?module=swcash';
    const URL_SWITCHPROFILE = '?module=switches&edit=';
    const ROUTE_EDIT = 'switchid';
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

}
