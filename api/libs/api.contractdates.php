<?php

/**
 * Contract dates manipulation class
 */
class ContractDates {

    /**
     * Contract dates database abstraction layer
     *
     * @var object
     */
    protected $contractDatesDb = '';

    /**
     * some predefined stuff
     */
    const TABLE_CONDATES = 'contractdates';
    const PROUTE_RUNEDIT = 'newcontractnum';
    const PROUTE_DATE = 'newcontractdate';
    const PROUTE_FROM = 'newcontractfrom';
    const PROUTE_TILL = 'newcontracttill';

    public function __construct() {
        $this->initDb();
    }

    /**
     * Inits database abstraction layer
     * 
     * @return void
     */
    protected function initDb() {
        $this->contractDatesDb = new NyanORM(self::TABLE_CONDATES);
    }

    /**
     * Creates new contract date database record
     *  
     *  @param $contract - existing contract num
     *  @param $date - contract creation date
     *  @param $from - optional start of contract date
     *  @param $till - optional end of contract date
     * 
     *  @return void
     */
    protected function create($contract, $date, $from = '', $till = '') {
        $contract = ubRouting::filters($contract, 'mres');
        $date = ubRouting::filters($date, 'mres');
        $from = ubRouting::filters($from, 'mres');
        $till = ubRouting::filters($till, 'mres');

        //not empty contract and valid date?
        if (!empty($contract) AND zb_checkDate($date)) {
            $this->contractDatesDb->data('contract', $contract);
            $this->contractDatesDb->data('date', $date);
            $this->contractDatesDb->data('from', $from);
            $this->contractDatesDb->data('till', $till);


            $this->contractDatesDb->create();
            log_register('CONTRACT DATE CREATE [' . $contract . '] D`' . $date . '` F`' . $from . '` T`' . $till . '`');
        }
    }

    /**
     * Updates some existing contract date database record
     *  
     *  @param $contract - existing contract num
     *  @param $date - contract creation date
     *  @param $from - optional start of contract date
     *  @param $till - optional end of contract date
     * 
     *  @return void
     */
    protected function update($contract, $date, $from = '', $till = '') {
        $contract = ubRouting::filters($contract, 'mres');
        $date = ubRouting::filters($date, 'mres');
        $from = ubRouting::filters($from, 'mres');
        $till = ubRouting::filters($till, 'mres');

        //not empty contract and valid date?
        if (!empty($contract) AND zb_checkDate($date)) {
            $this->contractDatesDb->data('date', $date);
            $this->contractDatesDb->data('from', $from);
            $this->contractDatesDb->data('till', $till);

            $this->contractDatesDb->where('contract', '=', $contract);
            $this->contractDatesDb->save();
            log_register('CONTRACT DATE CHANGE [' . $contract . '] D`' . $date . '` F`' . $from . '` T`' . $till . '`');
        }
    }

    /**
     * Sets some contract date in database
     *  
     *  @param $contract - existing contract num
     *  @param $date - contract creation date
     *  @param $from - optional start of contract date
     *  @param $till - optional end of contract date
     * 
     *  @return void
     */
    public function set($contract, $date, $from = '', $till = '') {
        if (!empty($contract)) {
            $currentDatabaseRecord = $this->getAllDatesFull($contract);

            //create new 
            if (empty($currentDatabaseRecord)) {
                $this->create($contract, $date, $from, $till);
            } else {
                //or update existing?
                $this->update($contract, $date, $from, $till);
            }
        }
    }

    /**
     * Get all or selected existing contract basic dates as contract=>date
     * 
     *  @return array
     */
    public function getAllDatesBasic($contract = '') {
        $contract = ubRouting::filters($contract, 'mres');
        $result = array();
        if (!empty($contract)) {
            $this->contractDatesDb->where('contract', '=', $contract);
        }
        $all = $this->contractDatesDb->getAll();
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $result[$each['contract']] = $each['date'];
            }
        }
        return ($result);
    }

    /**
     * Get all or selected existing contract basic dates as contract=>datesData[id,date,from,till]
     * 
     *  @return array
     */
    public function getAllDatesFull($contract = '') {
        $contract = ubRouting::filters($contract, 'mres');
        $result = array();
        if (!empty($contract)) {
            $this->contractDatesDb->where('contract', '=', $contract);
        }

        $result = $this->contractDatesDb->getAll('contract');
        return ($result);
    }

    /**
     * Shows contract create date modify form
     * 
     * @param string $contract
     * 
     * @return string
     */
    public function renderChangeForm($contract = '') {
        $result = '';

        $curDate = '';
        $curFrom = '';
        $curTill = '';

        if (!empty($contract)) {
            $currentData = $this->getAllDatesFull($contract);
            if (!empty($currentData)) {
                $curDate = $currentData[$contract]['date'];
                $curFrom = $currentData[$contract]['from'];
                $curTill = $currentData[$contract]['till'];
            }
        }

        $curDateLabel = ($curDate) ? $curDate : __('None');
        $curFromLabel = ($curFrom) ? $curFrom : __('None');
        $curTillLabel = ($curTill) ? $curTill : __('None');

        $rows = wf_HiddenInput(self::PROUTE_RUNEDIT, $contract);
        $cells = wf_TableCell($curDateLabel, '30%', 'row2');
        $cells .= wf_TableCell(wf_DatePickerPreset(self::PROUTE_DATE, $curDate) . ' ' . __('User contract date'), '', 'row3');
        $rows .= wf_tablerow($cells);


        $cells = wf_TableCell($curFromLabel, '', 'row2');
        $cells .= wf_TableCell(wf_DatePickerPreset(self::PROUTE_FROM, $curFrom) . ' ' . __('Start date'), '', 'row3');
        $rows .= wf_tablerow($cells);

        $cells = wf_TableCell($curTillLabel, '', 'row2');
        $cells .= wf_TableCell(wf_DatePickerPreset(self::PROUTE_TILL, $curTill) . ' ' . __('End date'), '', 'row3');
        $rows .= wf_tablerow($cells);

        $form = wf_TableBody($rows, '100%', 0);

        $form .= wf_Submit('Save');
        $result .= wf_Form("", 'POST', $form, '');
        return ($result);
    }

}
