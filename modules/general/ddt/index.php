<?php

class DoomsDayTariffs {

    protected $allOptions = array();
    protected $allTariffs = array();
    protected $allTariffNames = array();
    protected $periods = array();
    protected $messages = '';

    const URL_ME = '?module=ddt';

    public function __construct() {
        $this->loadData();
    }

    protected function loadData() {
        $this->initMessages();
        $this->loadTariffs();
        $this->loadOptionsDDT();
        $this->loadUsersDDT();
        $this->setOptions();
    }

    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    protected function loadOptionsDDT() {
        $query = "SELECT * from `ddt_options`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allOptions[$each['id']] = $each;
            }
        }
    }

    protected function setOptions() {
        $this->periods['month'] = __('Month');
        $this->periods['day'] = __('Day');
    }

    protected function loadUsersDDT() {
        
    }

    protected function loadTariffs() {
        $this->allTariffs = zb_TariffGetAllData();
        if (!empty($this->allTariffs)) {
            foreach ($this->allTariffs as $tariffName => $tariffData) {
                $this->allTariffNames[$tariffName] = $tariffName;
            }
        }
    }

    public function renderCreateForm() {
        $result = '';
        $inputs = wf_HiddenInput('createnewddtsignal', 'true');
        $inputs.= wf_Selector('createnewddttariff', $this->allTariffNames, __('Tariff'), '', true);
        $inputs.= wf_Selector('createnewddtperiod', $this->periods, __('Period'), '', true);
        $inputs.= wf_TextInput('createnewddtduration', __('Duration'), '', true, 4, 'digits');
        $inputs.= wf_CheckInput('createnewddtstartnow', __('Take into account the current period'), true, false);
        $inputs.= wf_CheckInput('createnewddtchargefee', __('Charge current tariff fee'), true, false);
        $inputs.= wf_TextInput('createnewddtchargeuntilday', __('Charge current tariff fee if day less then'), '1', true, 2, 'digits');
        $inputs.= wf_Selector('createnewddttariffmove', $this->allTariffNames, __('Move to tariff after ending of periods'), '', true);
        $inputs.=wf_delimiter(0);
        $inputs.= wf_Submit(__('Create'));

        $result.=wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    public function createTariffDDT() {
        if (wf_CheckPost(array('createnewddtsignal', 'createnewddttariff', 'createnewddtperiod', 'createnewddtduration', 'createnewddttariffmove'))) {
            
        }
    }

}

$ddt = new DoomsDayTariffs();

if (wf_CheckPost(array('createnewddtsignal'))) {
    $ddt->createTariffDDT();
}


deb($ddt->renderCreateForm());
?>