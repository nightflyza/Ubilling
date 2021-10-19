<?php

/**
 * JuniperMX NAS accounting data processing
 */
class JunAcct {

    /**
     * Contains preloaded user accounting data
     *
     * @var array
     */
    protected $userAcctData = array();

    /**
     * Contains current user login
     *
     * @var string
     */
    protected $userLogin = '';

    /**
     * Contains interesting fields from database acct table
     *
     * @var array
     */
    protected $fieldsRequired = array();

    /**
     * Contains name of accounting table
     *
     * @var string
     */
    protected $tableName = 'jun_acct';

    /**
     * Messages helper placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * log path
     */
    const LOG_PATH = 'exports/jungen.log';

    public function __construct($login = '') {
        $this->setLogin($login);
        $this->setFields();
        $this->loadAcctData();
        $this->initMessages();
    }

    /**
     * Inits system messages object
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Sets current user login
     * 
     * @param string $login
     * 
     * @return void
     */
    protected function setLogin($login) {
        $this->userLogin = mysql_real_escape_string($login);
    }

    /**
     * Sets interesting fields from accounting table for selecting data
     * 
     * @return void
     */
    protected function setFields() {
        $this->fieldsRequired = array(
            'acctsessionid',
            'username',
            'nasipaddress',
            'nasportid',
            'acctstarttime',
            'acctstoptime',
            'acctinputoctets',
            'acctoutputoctets',
            'framedipaddress',
            'acctterminatecause'
        );
    }

    /**
     * Transforms mac from xx:xx:xx:xx:xx:xx format to xxxx.xxxx.xxxx
     * 
     * @param string $mac
     * 
     * @return string
     */
    protected function transformMac($mac) {
        $result = implode(".", str_split(str_replace(":", "", $mac), 4));
        return ($result);
    }

    /**
     * Transforms mac from xxxx.xxxx.xxxx format to xx:xx:xx:xx:xx:xx
     * 
     * @param string $mac
     * 
     * @return string
     */
    protected function transformMacNormal($mac) {
        $result = implode(":", str_split(str_replace(".", "", $mac), 2));
        return ($result);
    }

    /**
     * Renders controls for acct date search
     * 
     * @return string
     */
    public function renderDateSerachControls() {
        $result = '';
        $curTime = time();
        $dayAgo = $curTime - 86400;
        $dayAgo = date("Y-m-d", $dayAgo);
        $dayTomorrow = $curTime + 86400;
        $dayTomorrow = date("Y-m-d", $dayTomorrow);
        $preDateFrom = (wf_CheckPost(array('datefrom'))) ? $_POST['datefrom'] : $dayAgo;
        $preDateTo = (wf_CheckPost(array('dateto'))) ? $_POST['dateto'] : $dayTomorrow;
        $unfinishedFlag = (wf_CheckPost(array('showunfinished'))) ? true : false;

        $inputs = wf_DatePickerPreset('datefrom', $preDateFrom, false);
        $inputs .= wf_DatePickerPreset('dateto', $preDateTo, false);
        $inputs .= wf_CheckInput('showunfinished', __('Show unfinished'), false, $unfinishedFlag);
        $inputs .= wf_Submit(__('Show'));
        $result = wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Loading some data from database
     * 
     * @return void
     */
    protected function loadAcctData() {
        $fieldsList = implode(', ', $this->fieldsRequired);
        if (!empty($this->userLogin)) {
            $userIp = zb_UserGetIP($this->userLogin);
            $userMac = zb_MultinetGetMAC($userIp);
            $userMacJ = $this->transformMac($userMac);
            if (!empty($userIp)) {
                $query = "SELECT " . $fieldsList . " FROM `" . $this->tableName . "` WHERE `username`='" . $userMacJ . "' ORDER BY `radacctid` DESC;";
                $this->userAcctData = simple_queryall($query);
            }
        } else {
            if (wf_CheckPost(array('datefrom', 'dateto'))) {
                $searchDateFrom = mysql_real_escape_string($_POST['datefrom']);
                $searchDateTo = mysql_real_escape_string($_POST['dateto']);
            } else {
                $curTime = time();
                $dayAgo = $curTime - 86400;
                $dayAgo = date("Y-m-d", $dayAgo);
                $dayTomorrow = $curTime + 86400;
                $dayTomorrow = date("Y-m-d", $dayTomorrow);
                $searchDateFrom = $dayAgo;
                $searchDateTo = $dayTomorrow;
            }

            if (wf_CheckPost(array('showunfinished'))) {
                $unfQueryfilter = "OR `acctstoptime` IS NULL ";
            } else {
                $unfQueryfilter = '';
            }

            $query = "SELECT " . $fieldsList . " FROM `" . $this->tableName . "` WHERE `acctstarttime` BETWEEN '" . $searchDateFrom . "' AND '" . $searchDateTo . "'"
                    . " " . $unfQueryfilter . "  ORDER BY `radacctid` DESC ;";
            $this->userAcctData = simple_queryall($query);
        }
    }

    /**
     * Renders preloaded accounting data in human-readable view
     * 
     * @return string
     */
    public function renderAcctStats() {
        $result = '';
        $totalCount = 0;

        if (!empty($this->userAcctData)) {
            $allUserMacs = zb_UserGetAllMACs();
            $allUserMacs = array_flip($allUserMacs);
            $allUserAddress = zb_AddressGetFulladdresslistCached();

            $cells = wf_TableCell('acctsessionid');
            $cells .= wf_TableCell('username');
            $cells .= wf_TableCell('nasipaddress');
            $cells .= wf_TableCell('nasportid');
            $cells .= wf_TableCell('acctstarttime');
            $cells .= wf_TableCell('acctstoptime');
            $cells .= wf_TableCell('acctinputoctets');
            $cells .= wf_TableCell('acctoutputoctets');
            $cells .= wf_TableCell('framedipaddress');
            $cells .= wf_TableCell('acctterminatecause');
            $cells .= wf_TableCell(__('Time'));
            $cells .= wf_TableCell(__('User'));
            $rows = wf_TableRow($cells, 'row1');

            foreach ($this->userAcctData as $io => $each) {
                $fc = '';
                $efc = wf_tag('font', true);
                if (!empty($each['acctstoptime'])) {
                    $startTime = strtotime($each['acctstarttime']);
                    $endTime = strtotime($each['acctstoptime']);
                    $timeOffsetRaw = $endTime - $startTime;
                    $timeOffset = zb_formatTime($timeOffsetRaw);
                } else {
                    $timeOffset = '';
                    $timeOffsetRaw = '';
                }

                //some coloring
                if (empty($each['acctstoptime'])) {
                    $fc = wf_tag('font', false, '', 'color="#ff6600"');
                } else {
                    $fc = wf_tag('font', false, '', 'color="#005304"');
                }

                //user detection
                $normalMac = $this->transformMacNormal($each['username']);
                $loginDetect = (isset($allUserMacs[$normalMac])) ? $allUserMacs[$normalMac] : '';
                $userAddress = (!empty($loginDetect)) ? @$allUserAddress[$loginDetect] : '';
                $profileLink = (!empty($loginDetect)) ? wf_Link('?module=userprofile&username=' . $loginDetect, web_profile_icon() . ' ' . $userAddress, false) : '';


                $cells = wf_TableCell($fc . $each['acctsessionid'] . $efc);
                $cells .= wf_TableCell($each['username']);
                $cells .= wf_TableCell($each['nasipaddress']);
                $cells .= wf_TableCell($each['nasportid']);
                $cells .= wf_TableCell($each['acctstarttime']);
                $cells .= wf_TableCell($each['acctstoptime']);
                $cells .= wf_TableCell(stg_convert_size($each['acctinputoctets']), '', '', 'sorttable_customkey="' . $each['acctinputoctets'] . '"');
                $cells .= wf_TableCell(stg_convert_size($each['acctoutputoctets']), '', '', 'sorttable_customkey="' . $each['acctoutputoctets'] . '"');
                $cells .= wf_TableCell($each['framedipaddress']);
                $cells .= wf_TableCell($each['acctterminatecause']);
                $cells .= wf_TableCell($timeOffset, '', '', 'sorttable_customkey="' . $timeOffsetRaw . '"');
                $cells .= wf_TableCell($profileLink);
                $rows .= wf_TableRow($cells, 'row3');
                $totalCount++;
            }

            $result = wf_TableBody($rows, '100%', 0, 'sortable');
            $result .= __('Total') . ': ' . $totalCount;
        } else {
            $result = $this->messages->getStyledMessage(__('Nothing found'), 'warning');
        }
        return ($result);
    }

    /**
     * Renders jungen logs control
     * 
     * @global object $ubillingConfig
     * 
     * @return string
     */
    function renderLogControl() {
        global $ubillingConfig;
        $result = '';
        $logData = array();
        $renderData = '';
        $rows = '';
        $recordsLimit = 200;
        $prevTime = '';
        $curTimeTime = '';
        $diffTime = '';

        if (file_exists(self::LOG_PATH)) {
            $billCfg = $ubillingConfig->getBilling();
            $tailCmd = $billCfg['TAIL'];
            $runCmd = $tailCmd . ' -n ' . $recordsLimit . ' ' . self::LOG_PATH;
            $rawResult = shell_exec($runCmd);
            $renderData .= __('Showing') . ' ' . $recordsLimit . ' ' . __('last events') . wf_tag('br');
            $renderData .= wf_Link('?module=report_jungen&dljungenlog=true', wf_img('skins/icon_download.png', __('Download')) . ' ' . __('Download full log'), true);

            if (!empty($rawResult)) {
                $logData = explodeRows($rawResult);
                $logData = array_reverse($logData); //from new to old list
                if (!empty($logData)) {


                    $cells = wf_TableCell(__('Date'));
                    $cells .= wf_TableCell(__('Event'));
                    $rows .= wf_TableRow($cells, 'row1');

                    foreach ($logData as $io => $each) {
                        if (!empty($each)) {

                            $eachEntry = explode(' ', $each);
                            $cells = wf_TableCell($eachEntry[0] . ' ' . $eachEntry[1]);
                            $cells .= wf_TableCell(str_replace(($eachEntry[0] . ' ' . $eachEntry[1]), '', $each));
                            $rows .= wf_TableRow($cells, 'row3');
                        }
                    }
                    $renderData .= wf_TableBody($rows, '100%', 0, 'sortable');
                }
            } else {
                $renderData .= $this->messages->getStyledMessage(__('Nothing found'), 'warning');
            }

            $result = wf_modal(wf_img('skins/log_icon_small.png', __('Attributes regeneration log')), __('Attributes regeneration log'), $renderData, '', '1280', '600');
        }
        return ($result);
    }

    /**
     * Performs downloading of log
     * 
     * @return void
     */
    public function logDownload() {
        if (file_exists(self::LOG_PATH)) {
            zb_DownloadFile(self::LOG_PATH);
        } else {
            show_error(__('Something went wrong') . ': EX_FILE_NOT_FOUND ' . self::LOG_PATH);
        }
    }

}
