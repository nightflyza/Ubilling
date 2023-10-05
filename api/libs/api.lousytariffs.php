<?php

/**
 * The class designed to manage rarely used tariffs
 */
class LousyTariffs {

    /**
     * Contains array of all available lousy tariffs as tariffName=>lousyhData[id/tariff]
     * 
     * @var array
     */
    protected $allLousyTariffs = array();

    /**
     * Contains array of all available system tariffs as tariffName=>tariffData
     * 
     * @var array
     */
    protected $allTariffs = array();

    /**
     * Lousy tariffs database abstraction layer placeholder
     * 
     * @var object
     */
    protected $lousyDb = '';

    /**
     * System messages helper instance placeholder
     * 
     * @var object
     */
    protected $messages = '';

    //some other predefined stuff
    const TABLE_LOUSY = 'lousytariffs';
    const RIGHT_CONFIG = 'LOUSYTARIFFS';
    const URL_ME = '?module=lousytariffs';
    const ROUTE_DELETE = 'deletelousytariff';
    const PROUTE_CREATE = 'createlousytariff';

//
//                     _,._
//                 __.'   _)
//                <_,)'.-"a\
//                  /' (    \
//      _.-----..,-'   (`"--^
//     //              |
//    (|   `;      ,   |
//      \   ;.----/  ,/
//       ) // /   | |\ \
//       \ \\`\   | |/ /
//        \ \\ \  | |\/
//         `" `"  `"`
//    
    public function __construct() {
        $this->initMessages();
        $this->initLousyDb();
        $this->loadLousyTariffs();
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
    protected function initLousyDb() {
        $this->lousyDb = new NyanORM(self::TABLE_LOUSY);
    }

    /**
     * Loads available lousy tariffs data from database
     * 
     * @return void
     */
    protected function loadLousyTariffs() {
        $this->allLousyTariffs = $this->lousyDb->getAll('tariff');
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
     * Checks is tariff marked as lousy?
     * 
     * @param string $tariffName
     * 
     * @return bool
     */
    protected function isLousy($tariffName) {
        $result = false;
        if (isset($this->allLousyTariffs[$tariffName])) {
            $result = true;
        }
        return($result);
    }

    /**
     * Checks is tariff not marked as lousy?
     * 
     * @param string $tariffName
     * 
     * @return bool
     */
    protected function isNotLousy($tariffName) {
        $result = true;
        if (isset($this->allLousyTariffs[$tariffName])) {
            $result = false;
        }
        return($result);
    }

    /**
     * Creates new lousy tariff
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
            if ($this->isNotLousy($tariffName)) {
                $this->lousyDb->data('tariff', $tariffNameF);
                $this->lousyDb->create();
                log_register('LOUSYTARIFF CREATE `' . $tariffName . '`');
            } else {
                $result .= __('Lousy tariff') . ' `' . $tariffName . '` ' . __('Already exists');
            }
        } else {
            $result .= __('Strange exception') . ': ' . __('Tariff') . ' `' . $tariffName . '` ' . __('Not exists');
        }
        return($result);
    }

    /**
     * Deletes existing lousy tariff
     * 
     * @param string $tariffName
     * 
     * @return void/string on error
     */
    public function delete($tariffName) {
        $result = '';
        $tariffNameF = ubRouting::filters($tariffName, 'mres');
        if ($this->isLousy($tariffName)) {
            $this->lousyDb->where('tariff', '=', $tariffNameF);
            $this->lousyDb->delete();
            log_register('LOUSYTARIFF DELETE `' . $tariffName . '`');
        } else {
            $result .= __('Lousy tariff') . ' `' . $tariffName . '` ' . __('Not exists');
        }
        return($result);
    }

    /**
     * Flushes existing lousy tariff on system tariff deletion
     * 
     * @param string $tariffName
     * 
     * @return void
     */
    public function flush($tariffName) {
        $result = '';
        $tariffNameF = ubRouting::filters($tariffName, 'mres');
        $this->lousyDb->where('tariff', '=', $tariffNameF);
        $this->lousyDb->delete();
        log_register('LOUSYTARIFF FLUSH `' . $tariffName . '`');
    }

    /**
     * Returns array copy without lousy tariffs
     * 
     * @param array $tariffsArr
     * 
     * @return array
     */
    public function truncateLousy($tariffsArr) {
        $result = array();

        if (!empty($tariffsArr)) {
            foreach ($tariffsArr as $eachTariff => $eachData) {
                if ($this->isNotLousy($eachTariff)) {
                    $result[$eachTariff] = $eachData;
                }
            }
        }
        return($result);
    }

    /**
     * Renders new  lousy tariff creation form
     * 
     * @return string
     */
    protected function renderCreateForm() {
        $result = '';
        if (!empty($this->allTariffs)) {
            $params = array();
            foreach ($this->allTariffs as $eachTariffName => $tariffData) {
                //excluding already lousy tariffs
                if (!$this->isLousy($eachTariffName)) {
                    $params[$eachTariffName] = $eachTariffName;
                }
            }

            if (!empty($params)) {
                $inputs = wf_Selector(self::PROUTE_CREATE, $params, __('Tariff'), '', false) . ' ';
                $inputs .= wf_Submit(__('Mark this tariff as not popular'));
                $result .= wf_Form('', 'POST', $inputs, 'glamour');
            } else {
                $result .= $this->messages->getStyledMessage(__('All tariffs marked as lousy'), 'info');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Any tariffs available'), 'warning');
        }
        return($result);
    }

    /**
     * Renders existing lousy tariff deletion form
     * 
     * @param string $tariffName
     * 
     * @return string
     */
    protected function renderDeleteForm($tariffName) {
        $result = '';
        if ($this->isLousy($tariffName)) {
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
     * Renders list of available lousy tariffs
     * 
     * @return string
     */
    public function renderList() {
        $result = '';
        //preloading system data required in future
        $this->loadAllTariffs();

        if (!empty($this->allLousyTariffs)) {
            $cells = wf_TableCell(__('Tariff'));
            $cells .= wf_TableCell(__('Fee'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->allLousyTariffs as $io => $each) {
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
