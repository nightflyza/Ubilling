<?php

class DynamicShaper {

    protected $allTariffs = array();
    protected $allSpeeds = array();
    protected $selectorParams = array();

    public function __construct() {
        $this->loadTariffs();
        $this->loadSpeeds();
        $this->preprocessTariffs();
    }

    /**
     * Loads existing tariffs from database
     * 
     * @return void
     */
    protected function loadTariffs() {
        $raw = zb_TariffsGetAll();
        if (!empty($raw)) {
            foreach ($raw as $io => $each) {
                $this->allTariffs[$each['name']] = $each['name'];
            }
        }
    }

    /**
     * Loads available tariff speeds from database
     * 
     * @return void
     */
    protected function loadSpeeds() {
        $this->allSpeeds = zb_TariffGetAllSpeeds();
    }

    /**
     * Preprocess tariffs for selector boxes
     * 
     * @return void
     */
    protected function preprocessTariffs() {
        if (!empty($this->allTariffs)) {
            foreach ($this->allTariffs as $io => $eachTariff) {
                $this->selectorParams[$eachTariff] = $eachTariff . $this->getSpeeds($eachTariff);
            }
        }
    }

    /**
     * Returns current tariff natural speeds
     * 
     * @param string $tariff
     * @return string
     */
    protected function getSpeeds($tariff) {
        $result = '';
        if (isset($this->allSpeeds[$tariff])) {
            $result = ' (' . $this->allSpeeds[$tariff]['speeddown'] . '/' . $this->allSpeeds[$tariff]['speedup'] . ')';
        }
        return ($result);
    }

    /**
     * Returns available time rules grid
     * 
     * @return string
     */
    public function renderList() {
        $messages = new UbillingMessageHelper();
        $allTariffs = zb_TariffGetPricesAll();
        $query = "SELECT * from `dshape_time` ORDER BY `id` ASC";
        $allrules = simple_queryall($query);

        $cells = wf_TableCell(__('ID'));
        $cells.= wf_TableCell(__('Tariff'));
        $cells.= wf_TableCell(__('Time from'));
        $cells.= wf_TableCell(__('Time to'));
        $cells.= wf_TableCell(__('Speed'));
        $cells.= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($allrules)) {
            foreach ($allrules as $io => $eachrule) {
                $rowClass = (isset($allTariffs[$eachrule['tariff']])) ? 'row3' : 'sigdeleteduser';
                $tariffControl = (cfr('TARIFFSPEED')) ? wf_Link('?module=tariffspeeds&tariff=' . $eachrule['tariff'], $eachrule['tariff'].' ', false) : $eachrule['tariff'];

                $cells = wf_TableCell($eachrule['id']);
                $cells.= wf_TableCell($tariffControl);
                $cells.= wf_TableCell($eachrule['threshold1']);
                $cells.= wf_TableCell($eachrule['threshold2']);
                $cells.= wf_TableCell($eachrule['speed']);
                $actions = wf_JSAlert('?module=dshaper&delete=' . $eachrule['id'], web_delete_icon(), $messages->getDeleteAlert());
                $actions.= wf_JSAlert('?module=dshaper&edit=' . $eachrule['id'], web_edit_icon(), $messages->getEditAlert());
                $cells.= wf_TableCell($actions);
                $rows.= wf_TableRow($cells, $rowClass);
            }
        }

        $result = wf_TableBody($rows, '100%', '0', 'sortable');
        return ($result);
    }

    /**
     * Deletes existing time rule from database
     * 
     * @param int $ruleid
     */
    public function delete($ruleid) {
        $ruleid = vf($ruleid, 3);
        $query = "DELETE from `dshape_time` where `id`='" . $ruleid . "'";
        nr_query($query);
        log_register("DSHAPE DELETE [" . $ruleid . ']');
    }

    /**
     * Deletes shaper rules from database by tariff name
     * 
     * @param string $tariff
     */
    public function flushTariff($tariff) {
        $tariff = mysql_real_escape_string($tariff);
        $query = "DELETE from `dshape_time` WHERE `tariff`='" . $tariff . "';";
        nr_query($query);
        log_register("DSHAPE FLUSH TARIFF `" . $tariff . '`');
    }

    /**
     * Returns time rule adding form
     * 
     * @return string
     */
    public function renderAddForm() {
        $sup = wf_tag('sup') . '*' . wf_tag('sup', true);

        $inputs = wf_Selector('newdshapetariff', $this->selectorParams, __('Tariff'), '', true);
        $inputs.= wf_TimePickerPresetSeconds('newthreshold1', '', __('Time from') . $sup . ' ', true);
        $inputs.= wf_TimePickerPresetSeconds('newthreshold2', '', __('Time to') . $sup . ' ', true);
        $inputs.= wf_TextInput('newspeed', __('Speed') . $sup, '', true, 8);
        $inputs.= wf_Submit(__('Create'));
        $result = wf_Form('', 'POST', $inputs, 'glamour');

        return ($result);
    }

    /**
     * Returns time rule editing form
     * 
     * @param int $timeruleid existing time rule database ID
     * @return string
     */
    public function renderEditForm($timeruleid) {
        $timeruleid = vf($timeruleid, 3);
        $query = "SELECT * from `dshape_time` WHERE `id`='" . $timeruleid . "'";
        $timerule_data = simple_query($query);

        $sup = wf_tag('sup') . '*' . wf_tag('sup', true);

        $inputs = wf_tag('select', false, '', 'DISABLED');
        $inputs.= wf_tag('option') . $timerule_data['tariff'] . $this->getSpeeds($timerule_data['tariff']) . wf_tag('option', true);
        $inputs.= wf_tag('select', true);
        $inputs.= wf_tag('br');
        $inputs.= wf_HiddenInput('editdshapetariff', $timerule_data['tariff']);
        $inputs.= wf_TimePickerPresetSeconds('editthreshold1', $timerule_data['threshold1'], __('Time from') . $sup, true);
        $inputs.= wf_TimePickerPresetSeconds('editthreshold2', $timerule_data['threshold2'], __('Time to') . $sup, true);
        $inputs.= wf_TextInput('editspeed', __('Speed') . $sup, $timerule_data['speed'], true, 8);
        $inputs.= wf_Submit(__('Save'));
        $form = wf_Form('', 'POST', $inputs, 'glamour');
        $form.= wf_CleanDiv();
        $form.=wf_tag('br');
        $form.= wf_Link('?module=dshaper', __('Back'), true, 'ubButton');


        return ($form);
    }

    /**
     * Creates new time rule in database
     * 
     * @param string $tariff existing tariff name
     * @param string $threshold1 event start time
     * @param string $threshold2 event stop time
     * @param integer $speed
     */
    public function create($tariff, $threshold1, $threshold2, $speed) {
        $tariff = mysql_real_escape_string($tariff);
        $threshold1 = mysql_real_escape_string($threshold1);
        $threshold2 = mysql_real_escape_string($threshold2);
        $speed = vf($speed);
        $query = "INSERT INTO `dshape_time` (`id` , `tariff` , `threshold1` , `threshold2` , `speed` ) "
                . "VALUES (NULL , '" . $tariff . "', '" . $threshold1 . "', '" . $threshold2 . "', '" . $speed . "');";
        nr_query($query);
        log_register("DSHAPE ADD `" . $tariff . '`');
    }

    /**
     * Edits existing timerule in database
     * 
     * @param type $timeruleid
     * @param type $threshold1 event start time
     * @param type $threshold2 event stop time
     * @param type $speed 
     */
    public function edit($timeruleid, $threshold1, $threshold2, $speed) {
        $timeruleid = vf($timeruleid);
        $threshold1 = mysql_real_escape_string($threshold1);
        $threshold2 = mysql_real_escape_string($threshold2);
        $speed = vf($speed);
        $query = "UPDATE `dshape_time` SET 
        `threshold1` = '" . $threshold1 . "',
        `threshold2` = '" . $threshold2 . "',
        `speed` = '" . $speed . "' WHERE `id` ='" . $timeruleid . "' LIMIT 1;
       ";
        nr_query($query);
        log_register("DSHAPE CHANGE [" . $timeruleid . '] ON `' . $speed . '`');
    }

}

?>