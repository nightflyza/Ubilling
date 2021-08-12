<?php

/**
 * callback service implementation
 */
class CallMeBack {

    /**
     * Some calls data model placeholder
     */
    protected $calls = '';

    /**
     * System telepathy object placeholder
     *
     * @var object
     */
    protected $telepathy = '';

    /**
     * Contains available user address data as login=>address
     *
     * @var array
     */
    protected $allAddress = array();

    /**
     * Basic control module URL
     */
    const URL_ME = '?module=callmeback';

    /**
     * Contains user navigation URL
     */
    const URL_USERPROFILE = '?module=userprofile&username=';

    /**
     * Creates new callmeback instance
     */
    public function __construct() {
        /**
         * Through the darkness of future past
         * The magician longs to see.
         * One chanse out between two worlds
         * Fire walk with me
         */
        $this->initCalls();
    }

    /**
     * Inits calls model for further usage
     */
    protected function initCalls() {
        $this->calls = new NyanORM('callmeback');
    }

    /**
     * Inits system telepaty class
     */
    protected function initTelepathy() {
        $this->loadAddressData();
        $this->telepathy = new Telepathy(false, true, false, true);
        $this->telepathy->usePhones();
    }

    /**
     * Loads address data required for user telepathy into protected property
     * 
     * @return void
     */
    protected function loadAddressData() {
        $this->allAddress = zb_AddressGetFulladdresslistCached();
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
        $this->calls->data('statedate', curdatetime());
        $this->calls->data('admin', whoami());
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
     * Try to detect user by its mobile and returns its navigation control
     * 
     * @param string $number
     * 
     * @return string
     */
    protected function getUserLinkByPhone($number) {
        $result = '';
        $detectedLogin = $this->telepathy->getByPhoneFast($number, true, true);
        if (!empty($detectedLogin)) {
            $result .= wf_Link(self::URL_USERPROFILE . $detectedLogin, web_profile_icon() . ' ' . @$this->allAddress[$detectedLogin]);
        }
        return($result);
    }

    /**
     * Renders undone calls with some controls
     * 
     * @return string
     */
    public function renderUndoneCalls() {
        $result = '';
        $this->initTelepathy();
        $undoneCalls = $this->getUndoneCalls();
        if (!empty($undoneCalls)) {
            $cells = wf_TableCell(__('Date'));
            $cells .= wf_TableCell(__('Number'));
            $cells .= wf_TableCell(__('User'));
            $cells .= wf_TableCell(__('Actions'), '50%');
            $rows = wf_TableRow($cells, 'row1');
            foreach ($undoneCalls as $io => $each) {
                $callTimestamp = strtotime($each['date']);
                $cells = wf_TableCell($each['date']);
                $cells .= wf_TableCell($each['number']);
                $cells .= wf_TableCell($this->getUserLinkByPhone($each['number']));
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
            $columns = array('ID', 'Date', 'Number', 'User', 'End date', 'Admin', 'Status');
            $opts = '"order": [[ 0, "desc" ]]';
            $result .= wf_JqDtLoader($columns, self::URL_ME . '&ajaxdonecalls=true', false, 'Calls', 100, $opts);
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
            $allEmployeeLogins = ts_GetAllEmployeeLoginsAssocCached();
            $this->initTelepathy();
            $json = new wf_JqDtHelper();
            foreach ($allCalls as $io => $each) {
                $data[] = $each['id'];
                $data[] = $each['date'];
                $data[] = $each['number'];
                $data[] = $this->getUserLinkByPhone($each['number']);
                $data[] = $each['statedate'];
                $employeeLabel = '';
                if (!empty($each['admin'])) {
                    if (isset($allEmployeeLogins[$each['admin']])) {
                        $employeeLabel = $allEmployeeLogins[$each['admin']];
                    } else {
                        $employeeLabel = $each['admin'];
                    }
                }
                $data[] = $employeeLabel;
                $data[] = $this->getStateLabel($each['state']);
                $json->addRow($data);
                unset($data);
            }
            $json->getJson();
        }
    }

}
