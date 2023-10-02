<?php

/**
 * Administrator restricted stealth tariffs implementeation
 */
class StealthTariffs {

    /**
     * Contains array of all available stealth tariffs as tariffName=>stealthData
     * 
     * @var array
     */
    protected $allStealthTariffs = array();

    /**
     * Contains array of all available system tariffs as tariffName=>tariffData
     * 
     * @var array
     */
    protected $allTariffs = array();

    /**
     * Stealth tariffs database abstraction layer placeholder
     * 
     * @var object
     */
    protected $stealthDb = '';

    /**
     * System messages helper instance placeholder
     * 
     * @var object
     */
    protected $messages = '';

    //some other predefined stuff
    const TABLE_STEALTH = 'stealthtariffs';
    const RIGHT_STEALTH = 'STEALTHTARIFFS';
    const RIGHT_CONFIG = 'STEALTHTARIFFSCFG';
    const URL_ME = '?module=stealthtariffs';
    const ROUTE_DELETE = 'deletestealthtariff';
    const PROUTE_CREATE = 'createstealthtariff';

    public function __construct() {
        $this->initMessages();
        $this->initStealthDb();
        $this->loadStealtTariffs();
    }

    /**
     * Inits message helper instance
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Inits database abstraction layer object for further usage
     * 
     * @return void
     */
    protected function initStealthDb() {
        $this->stealthDb = new NyanORM(self::TABLE_STEALTH);
    }

    /**
     * Loads available stealth tariffs data from database
     * 
     * @return void
     */
    protected function loadStealtTariffs() {
        $this->allStealthTariffs = $this->stealthDb->getAll('tariff');
    }

    /**
     * Loads existing tariffs. Required only for cfg iface.
     * 
     * @return void
     */
    protected function loadAllTariffs() {
        $this->allTariffs = zb_TariffGetAllData();
    }

    /**
     * Checks is tariff marked as stealth?
     * 
     * @param string $tariffName
     * 
     * @return bool
     */
    protected function isStealth($tariffName) {
        $result = false;
        if (isset($this->allStealthTariffs[$tariffName])) {
            $result = true;
        }
        return($result);
    }

    /**
     * Checks is tariff not marked as stealth?
     * 
     * @param string $tariffName
     * 
     * @return bool
     */
    protected function isNotStealth($tariffName) {
        $result = true;
        if (isset($this->allStealthTariffs[$tariffName])) {
            $result = false;
        }
        return($result);
    }

    /**
     * Creates new stealth-tariff
     * 
     * @param string $tariffName
     * 
     * @return void/string on error
     */
    public function create($tariffName) {
        $result = '';
        //preloading existing tariffs
        $this->loadAllTariffs();
        $tariffNameF = ubRouting::filters($tariffName, 'mres');
        if (isset($this->allTariffs[$tariffName])) {
            if ($this->isNotStealth($tariffName)) {
                $this->stealthDb->data('tariff', $tariffNameF);
                $this->stealthDb->create();
                log_register('STEALTHTARIFFS CREATE `' . $tariffName . '`');
            } else {
                $result .= __('Tariff') . ' `' . $tariffName . '` ' . __('Already stealth');
            }
        } else {
            $result .= __('Strange exception') . ': ' . __('Tariff') . ' `' . $tariffName . '` ' . __('Not exists');
        }
        return($result);
    }

    /**
     * Deletes existing stealth tariff
     * 
     * @param string $tariffName
     * 
     * @return void/string on error
     */
    public function delete($tariffName) {
        $result = '';
        $tariffNameF = ubRouting::filters($tariffName, 'mres');
        if ($this->isStealth($tariffName)) {
            $this->stealthDb->where('tariff', '=', $tariffNameF);
            $this->stealthDb->delete();
            log_register('STEALTHTARIFFS DELETE `' . $tariffName . '`');
        } else {
            $result .= __('Stealth tariffs') . ' `' . $tariffName . '` ' . __('Not exists');
        }
        return($result);
    }

    /**
     * Returns array copy without stealth tariffs
     * 
     * @param array $tariffsArr
     * 
     * @return array
     */
    public function truncateStealth($tariffsArr) {
        $result = array();
        if (!empty($tariffsArr)) {
            foreach ($tariffsArr as $eachTariff => $eachData) {
                if ($this->isNotStealth($eachTariff)) {
                    $result[$eachTariff] = $eachData;
                }
            }
        }
        return($result);
    }

    /**
     * Renders new stealth-tariff creation form
     * 
     * @return string
     */
    protected function renderCreateForm() {
        $result = '';
        if (!empty($this->allTariffs)) {
            $params = array();
            foreach ($this->allTariffs as $eachTariffName => $tariffData) {
                //excluding already stealth tariffs
                if (!$this->isStealth($eachTariffName)) {
                    $params[$eachTariffName] = $eachTariffName;
                }
            }

            if (!empty($params)) {
                $inputs = wf_Selector(self::PROUTE_CREATE, $params, __('Tariff'), '', false) . ' ';
                $inputs .= wf_Submit(__('Mark this tariff as stealth'));
                $result .= wf_Form('', 'POST', $inputs, 'glamour');
            } else {
                $result .= $this->messages->getStyledMessage(__('All tariffs marked as stealth'), 'info');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Any tariffs available'), 'warning');
        }
        return($result);
    }

    /**
     * Renders existing stealth tariff deletion form
     * 
     * @param string $tariffName
     * 
     * @return string
     */
    protected function renderDeleteForm($tariffName) {
        $result = '';
        if ($this->isStealth($tariffName)) {
            $deleteUrl = self::URL_ME . '&' . self::ROUTE_DELETE . '=' . $tariffName;
            $cancelUrl = self::URL_ME;
            $control = web_delete_icon();
            $customTitle = __('Delete') . ' ' . $tariffName . '?';
            $label = $this->messages->getDeleteAlert();
            $result .= wf_ConfirmDialog($deleteUrl, $control, $label, '', $cancelUrl, $customTitle);
        }
        return($result);
    }

    /**
     * Renders list of available stealth tariffs
     * 
     * @return string
     */
    public function renderList() {
        $result = '';
        //preloading system data required in future
        $this->loadAllTariffs();

        if (!empty($this->allStealthTariffs)) {
            $cells = wf_TableCell(__('Tariff'));
            $cells .= wf_TableCell(__('Fee'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->allStealthTariffs as $io => $each) {
                if (isset($this->allTariffs[$each['tariff']])) {
                    $tariffFee = $this->allTariffs[$each['tariff']]['Fee'];
                    $rowClass = 'row5';
                } else {
                    $tariffFee = __('Deleted');
                    $rowClass = 'sigdeleteduser';
                }

                $cells = wf_TableCell($each['tariff']);
                $cells .= wf_TableCell($tariffFee);
                $cells .= wf_TableCell($this->renderDeleteForm($each['tariff']));
                $rows .= wf_TableRow($cells, $rowClass);
            }
            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'info');
        }
        $result .= wf_delimiter();
        $result .= $this->renderCreateForm();
        return($result);
    }

}
