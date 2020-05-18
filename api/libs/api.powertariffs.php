<?php

class PowerTariffs {

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
     * System message helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Routes, tables, etc
     */
    const URL_ME = '?module=pt';
    const TABLE_TARIFFS = 'pt_tariffs';
    const TABLE_USERS = 'pt_users';
    const ROUTE_DELETE = 'deletept';
    const ROUTE_EDIT = 'editpt';

    /**
     * Creates new PT instance
     */
    public function __construct() {
        $this->initMessages();
        $this->initPowerBase();
        $this->loadSystemTariffs();
        $this->loadPowerTariffs();
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
                $tariffControls .= wf_JSAlert(self::URL_ME . '&' . self::ROUTE_EDIT . '=' . $each['tariff'], web_edit_icon(), $this->messages->getEditAlert());
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

}
