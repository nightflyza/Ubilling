<?php

/**
 * Unknown Hardware Helper
 */
class UHW {

    /**
     * log database abstraction layer
     *
     * @var object
     */
    protected $logDb = '';

    /**
     * Brute attempts database abstraction layer
     *
     * @var object
     */
    protected $bruteDb = '';

    /**
     * System message helper placeholder
     *
     * @var object
     */
    protected $messages = '';

    //some predefined stuff
    const TABLE_LOG = 'uhw_log';
    const TABLE_BRUTE = 'uhw_brute';
    const URL_ME = '?module=uhw';
    const ROUTE_BRUTE_SHOW = 'showbrute';
    const ROUTE_BRUTE_DEL = 'delbrute';
    const ROUTE_BRUTE_FLUSH = 'cleanallbrute';
    const ROUTE_LOGIN = 'username';
    const ROUTE_AJAX_LOG='ajax';

    public function __construct() {
        $this->initMessages();
        $this->initDbs();
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
     * Inits some database abstraction layers
     *
     * @return void
     */
    protected function initDbs() {
        $this->logDb = new NyanORM(self::TABLE_LOG);
        $this->bruteDb = new NyanORM(self::TABLE_BRUTE);
    }

    /**
     * Returns UHW control panel widget
     * 
     * @return string
     */
    public function panel() {
        $result = '';
        if (!ubRouting::checkGet(self::ROUTE_LOGIN)) {
            $result = wf_Link(self::URL_ME, wf_img('skins/ukv/report.png') . ' ' . __('Usage report'), false, 'ubButton');
            $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_BRUTE_SHOW . '=true', wf_img('skins/icon_key.gif') . ' ' . __('Brute attempts'), false, 'ubButton');
        }
        return ($result);
    }

    /**
     * Returns JSON reply for jquery datatables with full list of available UHW usages
     * 
     * @param string $loginFilter
     * 
     * @return void
     */
    public function ajaxGetData($loginFilter = '') {
        $loginFilter = ubRouting::filters($loginFilter, 'login');
        if (!empty($loginFilter)) {
            $this->logDb->where('login', '=', $loginFilter);
        }
        $this->logDb->orderBy('id', 'DESC');
        $all = $this->logDb->getAll();

        $alladdress = zb_AddressGetFulladdresslist();
        $allrealnames = zb_UserGetAllRealnames();
        $json = new wf_JqDtHelper();

        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $profileLink = wf_Link(UserProfile::URL_PROFILE . $each['login'], web_profile_icon() . ' ' . $each['login'], false);
                $userAddress = @$alladdress[$each['login']];
                $userRealname = @$allrealnames[$each['login']];

                $data[] = $each['id'];
                $data[] = $each['date'];
                $data[] = $each['password'];
                $data[] = $profileLink;
                $data[] = $userAddress;
                $data[] = $userRealname;
                $data[] = $each['ip'];
                $data[] = $each['nhid'];
                $data[] = $each['oldmac'];
                $data[] = $each['newmac'];
                $json->addRow($data);
                unset($data);
            }
        }

        $json->getJson();
    }

    /**
     * Returns container of successful UHW usages
     * 
     * @param string $searchLogin
     * 
     * @return string
     */
    public function renderUsageList($searchLogin = '') {
        $result = '';
        $columns = array('ID', 'Date', 'Password', 'Login', 'Address', 'Real name', 'IP', 'NHID', 'Old MAC', 'New MAC');
        $opts = '"order": [[ 0, "desc" ]]';
        $loginFilter = (!empty($searchLogin)) ? '&'.self::ROUTE_LOGIN.'=' . $searchLogin : '';
        $result = wf_JqDtLoader($columns, self::URL_ME.'&'.self::ROUTE_AJAX_LOG.'=true' . $loginFilter, false, 'users', 100, $opts);
        return ($result);
    }

    /**
     * Deletes uhw brute attempt from DB by its id
     * 
     * @param int $bruteid
     * 
     * @return void
     */
    public function deleteBrute($bruteid) {
        $bruteid = vf($bruteid, 3);
        $query = "DELETE from `uhw_brute` WHERE `id`='" . $bruteid . "'";
        nr_query($query);
        log_register("UHW BRUTE DELETE [" . $bruteid . "]");
    }

    /**
     * Flushes all UHW brute attempts
     * 
     * @retrun void
     */
    public function flushAllBrute() {
        $query = "TRUNCATE TABLE `uhw_brute` ;";
        nr_query($query);
        log_register("UHW CLEANUP BRUTE");
    }

    /**
     * Shows list of available UHW brute attempts with cleanup controls
     * 
     * @return string
     */
    public function renderBruteAttempts() {
        $query = "SELECT * from `uhw_brute` ORDER by `id` ASC";
        $allbrutes = simple_queryall($query);

        $tablecells = wf_TableCell(__('ID'));
        $tablecells .= wf_TableCell(__('Date'));
        $tablecells .= wf_TableCell(__('Password'));
        $tablecells .= wf_TableCell(__('Login'));
        $tablecells .= wf_TableCell(__('MAC'));
        $tablecells .= wf_TableCell(__('Actions'));
        $tablerows = wf_TableRow($tablecells, 'row1');

        if (!empty($allbrutes)) {
            foreach ($allbrutes as $io => $each) {
                $tablecells = wf_TableCell($each['id']);
                $tablecells .= wf_TableCell($each['date']);
                $tablecells .= wf_TableCell(strip_tags($each['password']));
                $tablecells .= wf_TableCell(strip_tags($each['login']));
                $tablecells .= wf_TableCell($each['mac']);
                $actlinks = wf_JSAlert('?module=uhw&showbrute=true&delbrute=' . $each['id'], web_delete_icon(), 'Are you serious');
                $tablecells .= wf_TableCell($actlinks);
                $tablerows .= wf_TableRow($tablecells, 'row3');
            }
        }

        $result = wf_TableBody($tablerows, '100%', 0, 'sortable');
        return ($result);
    }
}
