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
     * Deafault container refresh rate in ms.
     *
     * @var int
     */
    protected $timeout = 2000;

    /**
     * Some predefined URLs/routes etc
     */
    const URL_ME = '?module=dbmon';
    const ROUTE_FULL = 'renderfullqueries';
    const ROUTE_ZEN = 'dbmonzenmode';

    /**
     * creates new DBmon instance
     */
    public function __construct() {
        $this->initMessages();
        $this->setOptions();
    }

    /**
     * Inits system message helper for further usage
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Sets some current instance properties
     * 
     * @return void
     */
    protected function setOptions() {
        if (ubRouting::checkGet(self::ROUTE_FULL)) {
            $this->fullModifier = true;
        }
    }

    /**
     * Returns current instance refresh timeout
     * 
     * @return int
     */
    public function getTimeout() {
        return($this->timeout);
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
        $result .= wf_BackLink('?module=report_sysload') . ' ';
        if ($this->fullModifier) {
            $result .= wf_Link(self::URL_ME, wf_img('skins/icon_restoredb.png') . ' ' . __('Current database processes'), false, 'ubButton');
        } else {
            $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_FULL . '=true', wf_img('skins/icon_restoredb.png') . ' ' . __('Render full queries'), false, 'ubButton');
        }

        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_ZEN . '=true', wf_img('skins/zen.png') . ' ' . __('Zen'), false, 'ubButton');
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
            $count = 0;
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
                if (!ispos($each['Info'], 'PROCESSLIST')) {
                    $cells = wf_TableCell($each['Id']);
                    $cells .= wf_TableCell($each['User']);
                    $cells .= wf_TableCell($each['Host']);
                    $cells .= wf_TableCell($each['db']);
                    $cells .= wf_TableCell($each['Command']);
                    $cells .= wf_TableCell($each['Time']);
                    $cells .= wf_TableCell($each['State']);
                    $cells .= wf_TableCell($each['Info']);
                    $rows .= wf_TableRow($cells, 'row5');
                    $count++;
                }
            }


            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
            $result .= __('Total') . ': ' . $count;
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show') . ': ' . __('Collecting data'), 'warning');
        }
        return($result);
    }

}
