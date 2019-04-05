<?php

class DoomsDayTariffs {

    /**
     * Contains available DDT options aka tariffs as id=>data
     *
     * @var array
     */
    protected $allOptions = array();

    /**
     * Contains available system tariffs as tariffname=>tariffdata
     *
     * @var array
     */
    protected $allTariffs = array();

    /**
     * Contains available system tariffs as tariffname=>tariffname
     *
     * @var array
     */
    protected $allTariffNames = array();

    /**
     * Contains default periods descriptions as period=>periodname
     *
     * @var array
     */
    protected $periods = array();

    /**
     * System message helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Default control module URL
     */
    const URL_ME = '?module=ddt';

    /**
     * Creates new DoomsDay instance
     */
    public function __construct() {
        $this->loadData();
    }

    /**
     * Loads required datasets at object init
     * 
     * @return void
     */
    protected function loadData() {
        $this->initMessages();
        $this->loadTariffs();
        $this->loadOptionsDDT();
        $this->loadUsersDDT();
        $this->setOptions();
    }

    /**
     * Inits default system message helper object into protected prop
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Loads existing doomsday tariffs
     * 
     * @return void
     */
    protected function loadOptionsDDT() {
        $query = "SELECT * from `ddt_options`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allOptions[$each['id']] = $each;
            }
        }
    }

    /**
     * Sets default periods id-s and their localized names
     * 
     * @return void
     */
    protected function setOptions() {
        $this->periods['month'] = __('Month');
        $this->periods['day'] = __('Day');
    }

    /**
     * Loads existing DDT users database
     * 
     * @return void
     */
    protected function loadUsersDDT() {
        
    }

    /**
     * Loads available system tariffs into protected prop for further usage
     * 
     * @return void
     */
    protected function loadTariffs() {
        $this->allTariffs = zb_TariffGetAllData();
        if (!empty($this->allTariffs)) {
            foreach ($this->allTariffs as $tariffName => $tariffData) {
                $this->allTariffNames[$tariffName] = $tariffName;
            }
        }
    }

    /**
     * Renders default DDT tariff creation form
     * 
     * @return string
     */
    public function renderCreateForm() {
        $result = '';

        if (!empty($this->allTariffNames)) {
            $tariffsNewAvail = $this->allTariffNames;
            $currentTariffsDDT = $this->getCurrentTariffsDDT();
            if (!empty($currentTariffsDDT)) {
                foreach ($currentTariffsDDT as $io => $each) {
                    unset($tariffsNewAvail[$io]);
                }
            }

            if (!empty($tariffsNewAvail)) {
                $inputs = wf_HiddenInput('createnewddtsignal', 'true');
                $inputs.= wf_Selector('createnewddttariff', $tariffsNewAvail, __('Tariff'), '', true);
                $inputs.= wf_Selector('createnewddtperiod', $this->periods, __('Period'), '', true);
                $inputs.= wf_TextInput('createnewddtduration', __('Duration'), '1', true, 4, 'digits');
                $inputs.= wf_CheckInput('createnewddtstartnow', __('Take into account the current period'), true, false);
                $inputs.= wf_CheckInput('createnewddtchargefee', __('Charge current tariff fee'), true, false);
                $inputs.= wf_TextInput('createnewddtchargeuntilday', __('Charge current tariff fee if day less then'), '1', true, 2, 'digits');
                $inputs.= wf_Selector('createnewddttariffmove', $this->allTariffNames, __('Move to tariff after ending of periods'), '', true);
                $inputs.=wf_delimiter(0);
                $inputs.= wf_Submit(__('Create'));
                $result.=wf_Form('', 'POST', $inputs, 'glamour');
            } else {
                $result.=$this->messages->getStyledMessage(__('You already planned doomsday for all of available tariffs'), 'success');
            }
        } else {
            $result.=$this->messages->getStyledMessage(__('No existing tariffs available at all'), 'error');
        }


        return ($result);
    }

    /**
     * Catches DDT Tariff creation request and creates it into database
     * 
     * @return void/string on error
     */
    public function createTariffDDT() {
        $result = '';
        if (wf_CheckPost(array('createnewddtsignal', 'createnewddttariff', 'createnewddtperiod', 'createnewddtduration', 'createnewddttariffmove'))) {
            $newTariff = $_POST['createnewddttariff'];
            $newTariff_f = mysql_real_escape_string($newTariff);
            $newTariffMove = $_POST['createnewddttariffmove'];
            $newTariffMove_f = mysql_real_escape_string($_POST['createnewddttariffmove']);
            $newPeriod = vf($_POST['createnewddtperiod']);
            $newDuration = vf($_POST['createnewddtduration'], 3);
            $newStartNow = (wf_CheckPost(array('createnewddtstartnow'))) ? 1 : 0;
            $newChargeFee = (wf_CheckPost(array('createnewddtchargefee'))) ? 1 : 0;
            $newChargeDay = vf($_POST['createnewddtchargeuntilday'], 3);
            $currentTariffsDDT = $this->getCurrentTariffsDDT();
            if ($newTariff != $newTariffMove) {
                if (!empty($newDuration)) {
                    if (!isset($currentTariffsDDT[$newTariff])) {
                        $query = "INSERT INTO `ddt_options` (`id`,`tariffname`,`period`,`startnow`,`duration`,`chargefee`,`chargeuntilday`,`tariffmove`)"
                                . " VALUES (NULL,'" . $newTariff_f . "','" . $newPeriod . "','" . $newStartNow . "','" . $newDuration . "','" . $newChargeFee . "','" . $newChargeDay . "','" . $newTariffMove_f . "'); ";
                        nr_query($query);
                        $newId = simple_get_lastid('ddt_options');
                        log_register('DDT CREATE [' . $newId . '] TARIFF `' . $newTariff . '` MOVE ON `' . $newTariffMove . '` IN ' . $newDuration . ' `' . $newPeriod . '`');
                    } else {
                        $result = __('You already have doomsday assigned for tariff') . ' ' . $newTariff;
                        log_register('DDT CREATE FAIL DUPLICATE TARIFF `' . $newTariff . '`');
                    }
                } else {
                    $result = __('Duration cannot be empty');
                    log_register('DDT CREATE FAIL EMPTY DURATION');
                }
            } else {
                $result = __('Tariffs must be different');
                log_register('DDT CREATE FAIL SAME TARIFFS `' . $newTariff . '`');
            }
        }
        return ($result);
    }

    /**
     * Returns list of available ddt tariffs as tariffname=>options
     * 
     * @return array
     */
    public function getCurrentTariffsDDT() {
        $result = array();
        if (!empty($this->allOptions)) {
            foreach ($this->allOptions as $io => $each) {
                $result[$each['tariffname']] = $each;
            }
        }
        return ($result);
    }

    /**
     * Renders available DDT tariffs list with some controls
     * 
     * @return string
     */
    public function renderTariffsList() {
        $result = '';
        if (!empty($this->allOptions)) {
            $cells = wf_TableCell(__('ID'));
            $cells.= wf_TableCell(__('Tariff'));
            $cells.= wf_TableCell(__('Period'));
            $cells.= wf_TableCell(__('Start at this period'));
            $cells.= wf_TableCell(__('Duration'));
            $cells.= wf_TableCell(__('Charge fee'));
            $cells.= wf_TableCell(__('Charge until day'));
            $cells.= wf_TableCell(__('New tariff'));
            $cells.= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');

            foreach ($this->allOptions as $io => $each) {
                $cells = wf_TableCell($each['id']);
                $cells.= wf_TableCell($each['tariffname']);
                $cells.= wf_TableCell($this->periods[$each['period']]);
                $cells.= wf_TableCell(web_bool_led($each['startnow']));
                $cells.= wf_TableCell($each['duration']);
                $cells.= wf_TableCell(web_bool_led($each['chargefee']));
                $cells.= wf_TableCell($each['chargeuntilday']);
                $cells.= wf_TableCell($each['tariffmove']);

                $actLinks = wf_JSAlert(self::URL_ME . '&deleteddtariff=' . $each['id'], web_delete_icon(), $this->messages->getDeleteAlert());
                $cells.= wf_TableCell($actLinks);
                $rows.= wf_TableRow($cells, 'row5');
            }

            $result.=wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result.=$this->messages->getStyledMessage(__('There is nothing to watch') . '.', 'info');
        }
        return ($result);
    }

}

$ddt = new DoomsDayTariffs();

if (wf_CheckPost(array('createnewddtsignal'))) {
    $creationResult = $ddt->createTariffDDT();
    if (empty($creationResult)) {
        rcms_redirect($ddt::URL_ME);
    } else {
        show_error($creationResult);
        show_window('', wf_BackLink($ddt::URL_ME));
    }
}


show_window(__('Create new doomsday tariff'), $ddt->renderCreateForm());
show_window(__('Available doomsday tariffs'), $ddt->renderTariffsList());
?>