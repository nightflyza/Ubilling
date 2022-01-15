<?php

/**
 * Simple database query performance monitor
 */
class DBmon {

    /**
     * Contains full query rendering modifier
     *
     * @var bool
     */
    protected $fullModifier = false;

    /**
     * System messages helper placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * creates new DBmon instance
     */
    public function __construct() {
        $this->initMessages();
    }

    /**
     * Inits system message helper for further usage
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Returns currently running MySQL processes
     * 
     * @return array
     */
    protected function getProcessList() {
        $result = array();
        if ($this->fullModifier) {
            $query = "SHOW FULL PROCESSLIST";
        } else {
            $query = "SHOW PROCESSLIST";
        }
        $result = simple_queryall($query);
        return($result);
    }

    /**
     * Renders module interface controls
     * 
     * @return string
     */
    public function renderControls() {
        $result = '';
        //TODO
        return($result);
    }

    /**
     * Renders basic report
     * 
     * @return string
     */
    public function renderReport() {
        $result = '';
        $all = $this->getProcessList();
        if (!empty($all)) {
            $cells = wf_TableCell(__('ID'));
            $cells .= wf_TableCell(__('User'));
            $cells .= wf_TableCell(__('Host'));
            $cells .= wf_TableCell(__('DB'));
            $cells .= wf_TableCell(__('Command'));
            $cells .= wf_TableCell(__('Time'));
            $cells .= wf_TableCell(__('Status'));
            $cells .= wf_TableCell(__('Info'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($all as $io => $each) {
                $cells = wf_TableCell($each['Id']);
                $cells .= wf_TableCell($each['User']);
                $cells .= wf_TableCell($each['Host']);
                $cells .= wf_TableCell($each['db']);
                $cells .= wf_TableCell($each['Command']);
                $cells .= wf_TableCell($each['Time']);
                $cells .= wf_TableCell($each['State']);
                $cells .= wf_TableCell($each['Info']);
                $rows .= wf_TableRow($cells, 'row5');
            }
            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show') . ': ' . __('Collecting data'), 'warning');
        }
        return($result);
    }

}
