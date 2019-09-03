<?php

class CallMeBack {

    /**
     * Some calls data model placeholder
     */
    protected $calls = '';

    /**
     * Basic control module URL
     */
    const URL_ME = '?module=callmeback';

    /**
     * Creates new callmeback instance
     */
    public function __construct() {
        $this->initCalls();
    }

    /**
     * Inits calls model for further usage
     */
    protected function initCalls() {
        $this->calls = new NyanORM('callmeback');
    }

    /**
     * Returns array of all calls which required some reaction as id=>calldata
     * 
     * @return array
     */
    protected function getUndoneCalls() {
        $this->calls->where('state', '=', 'undone');
        return($this->calls->getAll('id'));
    }

    /**
     * Returns all processed calls array as id=>calldata
     * 
     * @return array
     */
    protected function getDoneCalls() {
        $this->calls->where('state', '!=', 'undone');
        return($this->calls->getAll('id'));
    }

    /**
     * Create some callback record in database for further employee reactions.
     * 
     * @param int $number
     * 
     * @return void
     */
    public function createCall($number) {
        $number = ubRouting::filters($number, 'int');
        $this->calls->data('date', curdatetime());
        $this->calls->data('number', $number);
        $this->calls->data('state', 'undone');
        $this->calls->create();
    }

    /**
     * Sets call state in database
     * 
     * @param int $callId
     * @param string $state
     * 
     * @return void
     */
    public function setCallState($callId, $state) {
        $callId = ubRouting::filters($callId, 'int');
        $stateF = ubRouting::filters($state, 'mres');
        $this->calls->where('id', '=', $callId);
        $this->calls->data('state', $stateF);
        $this->calls->save();
        log_register('CALLMEBACK SET [' . $callId . '] STATE `' . $state . '`');
    }

    /**
     * Returns unprocessed calls count
     * 
     * @return int
     */
    public function getUndoneCount() {
        $this->calls->where('state', '=', 'undone');
        $result = $this->calls->getFieldsCount();
        return($result);
    }

    /**
     * Returns all processed calls count
     * 
     * @return array
     */
    protected function getDoneCallsCount() {
        $this->calls->where('state', '!=', 'undone');
        return($this->calls->getFieldsCount());
    }

    /**
     * Renders undone calls with some controls
     * 
     * @return string
     */
    public function renderUndoneCalls() {
        $result = '';
        $undoneCalls = $this->getUndoneCalls();
        if (!empty($undoneCalls)) {
            $cells = wf_TableCell(__('Date'));
            $cells .= wf_TableCell(__('Number'));
            $cells .= wf_TableCell(__('Actions'), '50%');
            $rows = wf_TableRow($cells, 'row1');
            foreach ($undoneCalls as $io => $each) {
                $callTimestamp = strtotime($each['date']);
                $cells = wf_TableCell($each['date']);
                $cells .= wf_TableCell($each['number']);
                $callControls = wf_Link(self::URL_ME . '&setcall=' . $each['id'] . '&state=done', wf_img('skins/calls/phone_green.png') . ' ' . __('Recalled'), false, 'ubButton') . ' ';
                $callControls .= wf_Link(self::URL_ME . '&setcall=' . $each['id'] . '&state=noanswer', wf_img('skins/calls/phone_red.png') . ' ' . __('No answer'), false, 'ubButton') . ' ';
                $callControls .= wf_Link(self::URL_ME . '&setcall=' . $each['id'] . '&state=wrongnum', wf_img('skins/calls/phone_fail.png') . ' ' . __('Wrong number'), false, 'ubButton') . ' ';
                $cells .= wf_TableCell($callControls);
                $rows .= wf_TableRow($cells, 'row5');
            }

            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $messages = new UbillingMessageHelper();
            $result .= $messages->getStyledMessage(__('Nothing to show'), 'success');
        }
        return($result);
    }

    /**
     * Returns main module control panel
     * 
     * @return string
     */
    public function renderPanel() {
        $result = '';
        $result .= wf_Link(self::URL_ME, wf_img('skins/undone_icon.png') . ' ' . __('Undone calls'), false, 'ubButton') . ' ';
        $result .= wf_Link(self::URL_ME . '&showdone=true', wf_img('skins/done_icon.png') . ' ' . __('Processed calls'), false, 'ubButton') . ' ';
        return($result);
    }

    /**
     * Renders processed calls container
     * 
     * @return string
     */
    public function renderProcessedCalls() {
        $result = '';
        $doneCalls = $this->getDoneCallsCount();
        if ($doneCalls > 0) {
            $columns = array('ID', 'Date', 'Number', 'Status');
            $opts = '"order": [[ 0, "desc" ]]';
            $result .= wf_JqDtLoader($columns, self::URL_ME . '&ajaxdonecalls=true', false, 'calls', 100, $opts);
        } else {
            $messages = new UbillingMessageHelper();
            $result .= $messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        return($result);
    }

    /**
     * Performs formatting/localizing call state
     * 
     * @param strings $state
     * 
     * @return string
     */
    protected function getStateLabel($state) {
        $result = '';
        switch ($state) {
            case 'done':
                $result = wf_img('skins/calls/phone_green.png') . ' ' . __('Done');
                break;
            case 'noanswer':
                $result = wf_img('skins/calls/phone_red.png') . ' ' . __('No answer');
                break;
            case 'wrongnum':
                $result = wf_img('skins/calls/phone_fail.png') . ' ' . __('Wrong number');
                break;
            default :
                $result = $state;
                break;
        }
        return($result);
    }

    /**
     * Renders processed calls JSON data
     * 
     * @return void
     */
    public function getAjProcessedList() {
        $allCalls = $this->getDoneCalls();
        if (!empty($allCalls)) {
            $json = new wf_JqDtHelper();
            foreach ($allCalls as $io => $each) {
                $data[] = $each['id'];
                $data[] = $each['date'];
                $data[] = $each['number'];

                $data[] = $this->getStateLabel($each['state']);
                $json->addRow($data);
                unset($data);
            }
            $json->getJson();
        }
    }

}
