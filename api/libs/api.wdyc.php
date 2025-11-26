<?php

/**
 * Missed calls notification subsystem
 */
class WhyDoYouCall {

    /**
     * System alter.ini config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Telepathy object placeholder
     *
     * @var object
     */
    protected $telepathy = array();

    /**
     * Contains only mobile flag mapped from WDYC_ONLY_MOBILE config option
     *
     * @var bool
     */
    protected $onlyMobileFlag = false;


    /**
     * System messages helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Contains array of all available user names as login=>reanlnames
     *
     * @var array
     */
    protected $allUserNames = array();

    /**
     * Contains array of all available users address as login=>fulladdress
     *
     * @var array
     */
    protected $allUserAddress = array();

    /**
     * Stats database abstraction layer placeholder
     *
     * @var object
     */
    protected $statsDb = '';

    /**
     * Contains path to the unanswered calls cache
     */
    const CACHE_FILE = 'exports/whydoyoucall.dat';

    /**
     * Contains path to recalled phone numbers cache
     */
    const CACHE_RECALLED = 'exports/whydoyourecall.dat';

    /**
     * Contains user profile base URL
     */
    const URL_PROFILE = '?module=userprofile&username=';

    /**
     * Contains primary module URL
     */
    const URL_ME = '?module=whydoyoucall';

    /**
     * Default wdyc stats table name
     */
    const TABLE_STATS = 'wdycinfo';

    public function __construct() {
        $this->loadConfig();
        $this->initMessages();
        $this->initTelepathy();
        $this->initStatsDb();
    }

    /**
     * Preloads alter config, for further usage as key=>value
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfig() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
        if ((!isset($this->altCfg['WDYC_ONLY_MOBILE'])) OR ( !@$this->altCfg['WDYC_ONLY_MOBILE'])) {
            $this->onlyMobileFlag = false;
        } else {
            $this->onlyMobileFlag = true;
        }
    }

    /**
     * Creates message helper object for further usage
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Inits telepathy object instance
     * 
     * @return void
     */
    protected function initTelepathy() {
        $this->telepathy = new Telepathy();
        $this->telepathy->usePhones();
    }

    /**
     * Inits stats database abstraction layer
     * 
     * @return void
     */
    protected function initStatsDb() {
        $this->statsDb = new NyanORM(self::TABLE_STATS);
    }

    /**
     * Fetches unanswered calls data from PBX and stores it into cache
     * 
     * @return void
     */
    public function pollUnansweredCalls() {
        $unansweredCalls = array();
        $recalledCalls = array();

        if ($this->altCfg['TELEPONY_ENABLED']) {
            if ($this->altCfg['TELEPONY_CDR']) {
                $telePony = new TelePony();
                $fetchedData = $telePony->fetchMissedCalls();
                $unansweredCalls = $fetchedData['unanswered'];
                $recalledCalls = $fetchedData['recalled'];
                print('TELEPONY:FETCHED' . PHP_EOL);
            }
        }


        //filling recalled calls cache
        file_put_contents(self::CACHE_RECALLED, serialize($recalledCalls));
        //storing missed calls
        file_put_contents(self::CACHE_FILE, serialize($unansweredCalls));
    }

    /**
     * Trys to detect user login by phone number
     * 
     * @param string $phoneNumber
     * 
     * @return string
     */
    protected function userLoginTelepathy($phoneNumber) {
        $result = $this->telepathy->getByPhone($phoneNumber, $this->onlyMobileFlag, $this->onlyMobileFlag); //here only mobile flag is used for number normalization
        return ($result);
    }

    /**
     * Renders module controls
     * 
     * @return string
     */
    public function panel() {
        $result = '';
        if (!ubRouting::checkGet('renderstats') AND ! ubRouting::checkGet('nightmode')) {
            $result .= wf_Link(self::URL_ME, wf_img_sized('skins/icon_phone.gif', '', '16', '16') . ' ' . __('Calls'), false, 'ubButton') . ' ';

            if ($this->altCfg['TELEPONY_CDR']) {
                $result .= wf_Link(self::URL_ME . '&nightmode=true', wf_img_sized('skins/icon_moon.png', '', '16', '16') . ' ' . __('Calls during non-business hours'), false, 'ubButton') . ' ';
            }

            $result .= wf_Link(self::URL_ME . '&renderstats=true', wf_img_sized('skins/icon_stats.gif', '', '16', '16') . ' ' . __('Stats'), false, 'ubButton');
        } else {
            $result .= wf_BackLink(self::URL_ME);
        }
        return ($result);
    }

    /**
     * Renders report of missed calls that required to be processed
     * 
     * @return string
     */
    public function renderMissedCallsReport() {
        $result = '';
        $this->allUserNames = zb_UserGetAllRealnames();
        $this->allUserAddress = zb_AddressGetFulladdresslistCached();

        if (file_exists(self::CACHE_FILE)) {
            $rawData = file_get_contents(self::CACHE_FILE);
            if (!empty($rawData)) {
                $rawData = unserialize($rawData);
                if (!empty($rawData)) {
                    $totalCount = 0;
                    $cells = wf_TableCell(__('Number'));
                    $cells .= wf_TableCell(__('Last call time'));
                    $cells .= wf_TableCell(__('Number of attempts to call'));
                    $cells .= wf_TableCell(__('User'));

                    $rows = wf_TableRow($cells, 'row1');
                    foreach ($rawData as $number => $callData) {
                        $loginDetect = $this->userLoginTelepathy($number);

                        if (!empty($loginDetect)) {
                            $userAddress = @$this->allUserAddress[$loginDetect];
                            $userRealName = @$this->allUserNames[$loginDetect];
                            $profileLink = wf_Link(self::URL_PROFILE . $loginDetect, web_profile_icon() . ' ' . $userAddress, false) . ' ' . $userRealName;
                        } else {
                            $profileLink = '';
                        }
                        $cells = wf_TableCell(wf_tag('strong') . $number . wf_tag('strong', true));
                        $cells .= wf_TableCell($callData[9]);
                        $cells .= wf_TableCell($callData['misscount']);
                        $cells .= wf_TableCell($profileLink);

                        $rows .= wf_TableRow($cells, 'row5');
                        $totalCount++;
                    }
                    $result = wf_TableBody($rows, '100%', 0, 'sortable');
                    $result .= __('Total') . ': ' . $totalCount;
                } else {
                    $result = $this->messages->getStyledMessage(__('No missed calls at this time'), 'success');
                }
            }
        } else {
            $result = $this->messages->getStyledMessage(__('No unanswered calls cache available'), 'warning');
        }
        return ($result);
    }

    /**
     * Returns report of recalled numbers
     * 
     * @return string
     */
    public function renderRecalledCallsReport() {
        $result = '';
        if (file_exists(self::CACHE_RECALLED)) {
            $rawData = file_get_contents(self::CACHE_RECALLED);
            if (!empty($rawData)) {
                $rawData = unserialize($rawData);
                if (!empty($rawData)) {
                    $totalCount = 0;
                    $cells = wf_TableCell(__('Number'));
                    $cells .= wf_TableCell(__('Number of attempts to call'));
                    $cells .= wf_TableCell(__('Reaction time'));
                    $cells .= wf_TableCell(__('Talk time'));
                    $cells .= wf_TableCell(__('Status'));
                    $cells .= wf_TableCell(__('User'));

                    $rows = wf_TableRow($cells, 'row1');
                    foreach ($rawData as $number => $callData) {
                        $callTime = $callData['time'];
                        $callTimeFormated = zb_formatTime($callTime);
                        $loginDetect = $this->userLoginTelepathy($number);

                        if (!empty($loginDetect)) {
                            $userAddress = @$this->allUserAddress[$loginDetect];
                            $userRealName = @$this->allUserNames[$loginDetect];
                            $profileLink = wf_Link(self::URL_PROFILE . $loginDetect, web_profile_icon() . ' ' . $userAddress, false) . ' ' . $userRealName;
                        } else {
                            $profileLink = '';
                        }
                        $callStatus = ($callTime > 0) ? wf_img('skins/calls/phone_green.png') . ' ' . __('Answered') : wf_img('skins/calls/phone_red.png') . ' ' . __('No answer');
                        $callStatusFlag = ($callTime > 0) ? 1 : 0;
                        $cells = wf_TableCell(wf_tag('strong') . $number . wf_tag('strong', true));
                        $cells .= wf_TableCell($callData['count']);
                        $cells .= wf_TableCell(zb_formatTime($callData['trytime']));
                        $cells .= wf_TableCell($callTimeFormated, '', '', 'sorttable_customkey="' . $callTime . '"');
                        $cells .= wf_TableCell($callStatus, '', '', 'sorttable_customkey="' . $callStatusFlag . '"');
                        $cells .= wf_TableCell($profileLink);
                        $rows .= wf_TableRow($cells, 'row5');
                        $totalCount++;
                    }
                    $result = wf_TableBody($rows, '100%', 0, 'sortable');
                    $result .= __('Total') . ': ' . $totalCount;
                } else {
                    $result = $this->messages->getStyledMessage(__('No recalled calls at this time'), 'info');
                }
            }
        } else {
            $result = $this->messages->getStyledMessage(__('No recalled calls cache available'), 'warning');
        }
        return ($result);
    }

    /**
     * Saves day unansweres/recalls stats into database
     * 
     * @return void
     */
    public function saveStats() {
        $date = curdate();
        $missedCallsCount = 0;
        $recallsCount = 0;
        $unsuccCount = 0;
        $totalTryTime = 0;
        $missedCallsNumbers = '';
        //missed calls stats
        if (file_exists(self::CACHE_FILE)) {
            $rawData = file_get_contents(self::CACHE_FILE);
            if (!empty($rawData)) {
                $rawData = unserialize($rawData);
                if (!empty($rawData)) {
                    foreach ($rawData as $missedNumber => $callData) {
                        if (!ispos($missedNumber, 'anonymous')) {
                            $missedCallsNumbers .= $missedNumber . ' ';
                            $missedCallsCount++;
                        }
                    }
                }
            }
        }

        //recalled calls stats
        if (file_exists(self::CACHE_RECALLED)) {
            $rawData = file_get_contents(self::CACHE_RECALLED);
            if (!empty($rawData)) {
                $rawData = unserialize($rawData);
                if (!empty($rawData)) {
                    $recallsCount = sizeof($rawData);
                    foreach ($rawData as $recalledNumber => $callData) {
                        if ($callData['time'] == 0) {
                            $unsuccCount++;
                        }

                        if (isset($callData['trytime'])) {
                            //realistic?
                            if ($callData['trytime'] > 0) {
                                $trytime = $callData['trytime'];
                            } else {
                                //negative?
                                $trytime = 0;
                                //seems this case is better than abs() value to prevent trytime exhaustion
                            }
                            $totalTryTime = $totalTryTime + $trytime;
                        }
                    }
                }
            }
        }
        $missedCallsNumbers = mysql_real_escape_string($missedCallsNumbers);

        $this->statsDb->data('date', $date);
        $this->statsDb->data('missedcount', $missedCallsCount);
        $this->statsDb->data('recallscount', $recallsCount);
        $this->statsDb->data('unsucccount', $unsuccCount);
        $this->statsDb->data('missednumbers', $missedCallsNumbers);
        $this->statsDb->data('totaltrytime', $totalTryTime);
        $this->statsDb->create();
    }

    /**
     * Returns date search form
     * 
     * @param int $year
     * @param int $month
     * 
     * @return string
     */
    protected function statsDateForm($year = '', $month = '') {
        $result = '';
        $curYear = (empty($year)) ? date("Y") : $year;
        $curMonth = (empty($month)) ? date("m") : $month;
        $monthArr = months_array_localized();

        $inputs = wf_YearSelectorPreset('yearsel', __('Year'), false, $curYear) . ' ';
        $inputs .= wf_Selector('monthsel', $monthArr, __('Month'), $curMonth, false) . ' ';
        $inputs .= wf_Submit(__('Show'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        $result .= wf_CleanDiv();

        return ($result);
    }

    /**
     * Renders unanswered night-mode calls
     * 
     * @return string
     */
    public function renderNightModeCalls() {
        $result = '';
        if ($this->altCfg['TELEPONY_ENABLED'] AND $this->altCfg['TELEPONY_CDR']) {
            $telePony = new TelePony();
            $result .= $telePony->renderNightCalls();
        }
        return($result);
    }

   

    /**
     * Renders previous days stats
     * 
     * @return string
     */
    public function renderStats() {
        $result = '';
        $year = (wf_CheckPost(array('yearsel'))) ? vf($_POST['yearsel'], 3) : date("Y");
        $month = (wf_CheckPost(array('monthsel'))) ? vf($_POST['monthsel'], 3) : date("m");
        $totalMissed = 0;
        $totalRecalls = 0;
        $totalUnsucc = 0;
        $totalCalls = 0;
        $totalReactTime = 0;

        $result .= $this->statsDateForm($year, $month);

        $gchartsData = array();
        $gchartsData[] = array(__('Date'), __('Missed calls'), __('Recalled calls'), __('Unsuccessful recalls'));
        $chartsOptions = "
            'focusTarget': 'category',
                        'hAxis': {
                        'color': 'none',
                            'baselineColor': 'none',
                    },
                        'vAxis': {
                        'color': 'none',
                            'baselineColor': 'none',
                    },
                        'curveType': 'function',
                        'pointSize': 5,
                        'crosshair': {
                        trigger: 'none'
                    },";

        $jqDtOpts = '"order": [[ 0, "desc" ]]';

        $columns = array('ID', 'Date', 'Missed calls', 'Recalled calls', 'Unsuccessful recalls', 'Reaction time', 'Phones');
        if (cfr('ROOT')) {
            $columns[] = 'Actions';
        }

        $this->statsDb->where('date', 'LIKE', $year . "-" . $month . "-%");
        $all = $this->statsDb->getAll();

        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $gchartsData[] = array($each['date'], $each['missedcount'], $each['recallscount'], $each['unsucccount']);
                $totalMissed += $each['missedcount'];
                $totalRecalls += $each['recallscount'];
                $totalUnsucc += $each['unsucccount'];
                $totalReactTime += $each['totaltrytime'];
            }

            $totalCalls += $totalMissed + $totalRecalls;
            $result .= wf_gchartsLine($gchartsData, __('Calls'), '100%', '300px;', $chartsOptions);
            $result .= wf_tag('strong') . __('Total') . ': ' . wf_tag('strong', true) . wf_tag('br');
            $result .= __('Missed calls') . ' - ' . $totalMissed . wf_tag('br');
            $result .= __('Recalled calls') . ' - ' . $totalRecalls . wf_tag('br');
            $result .= __('Unsuccessful recalls') . ' - ' . $totalUnsucc . wf_tag('br');
            $result .= __('Percent') . ' ' . __('Missed calls') . ' - ' . zb_PercentValue($totalCalls, $totalMissed) . '%' . wf_tag('br');
            $reactTimeStat = (!empty($totalReactTime)) ? zb_formatTime($totalReactTime / ($totalRecalls + $totalUnsucc)) : __('No');
            $result .= __('Reaction time') . ' - ' . $reactTimeStat;
            $result .= wf_tag('br');
            $result .= wf_tag('br');
            $result .= wf_JqDtLoader($columns, self::URL_ME . '&renderstats=true&ajaxlist=true&year=' . $year . '&month=' . $month, false, __('Calls'), 50, $jqDtOpts);
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing found'), 'warning');
        }
        return ($result);
    }

    /**
     * Cuts string to some normal length
     * 
     * @param string $string
     * @param int $count
     * @return string
     */
    protected function cutString($string, $count) {
        if (strlen($string) <= $count) {
            $result = $string;
        } else {
            $result = substr($string, 0, $count) . '...';
        }
        return ($result);
    }

    /**
     * Do some coloring of missed counts
     * 
     * @param int $missedCount
     * 
     * @return string
     */
    protected function colorMissed($missedCount) {
        if ($missedCount > 0) {
            if ($missedCount <= 5) {
                $result = $missedCount;
            } else {
                $result = wf_tag('font', false, '', 'color="#FF0000"') . $missedCount . wf_tag('font', true);
            }
        } else {
            $result = wf_tag('font', false, '', 'color="#118819"') . $missedCount . wf_tag('font', true);
        }
        return ($result);
    }

    /**
     * Renders stats records editing form
     * 
     * @param array $statsRecordData
     * 
     * @return string
     */
    protected function renderStatsEditForm($statsRecordData) {
        $result = '';
        if (!empty($statsRecordData)) {
            $inputs = wf_HiddenInput('editwdycstatsid', $statsRecordData['id']);
            $inputs .= wf_TextInput('editwdycstatsmissedcount', __('Missed calls'), $statsRecordData['missedcount'], true, 2, 'digits');
            $inputs .= wf_TextInput('editwdycstatsrecallscount', __('Recalled calls'), $statsRecordData['recallscount'], true, 2, 'digits');
            $inputs .= wf_TextInput('editwdycstatsmissednumbers', __('Phones'), $statsRecordData['missednumbers'], true, 20, '');
            $inputs .= wf_Submit(__('Save'));
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        }
        return($result);
    }

    /**
     * Catches existing stats modification request and performs editing
     * 
     * @return void
     */
    public function saveEditedStats() {
        if (ubRouting::checkPost('editwdycstatsid')) {
            $editId = ubRouting::post('editwdycstatsid', 'int');
            $newMissCount = ubRouting::post('editwdycstatsmissedcount', 'int');
            $newRecallsCount = ubRouting::post('editwdycstatsrecallscount', 'int');
            $newPhones = ubRouting::post('editwdycstatsmissednumbers', 'mres');


            if (!empty($editId)) {
                $this->statsDb->where('id', '=', $editId);
                $recordData = $this->statsDb->getAll();
                if (!empty($recordData)) {
                    $recordData = $recordData[0];
                    $oldMissCount = $recordData['missedcount'];
                    $oldRecallsCount = $recordData['recallscount'];
                    $oldPhones = $recordData['missednumbers'];

                    //is anything changed?
                    if ($newMissCount != $oldMissCount OR $newRecallsCount != $oldRecallsCount OR $newPhones != $oldPhones) {
                        $this->statsDb->where('id', '=', $editId);
                        $this->statsDb->data('missedcount', $newMissCount);
                        $this->statsDb->data('recallscount', $newRecallsCount);
                        $this->statsDb->data('missednumbers', $newPhones);
                        $this->statsDb->save();
                        log_register('WDYC CHANGED [' . $editId . '] MISSED `' . $oldMissCount . '` ON `' . $newMissCount . '` RECALLS `' . $oldRecallsCount . '` ON `' . $newRecallsCount . '`');
                    }
                }
            }
        }
    }

    /**
     * Renders json for previous calls stats
     * 
     * @param int $year
     * @param int $month
     * 
     * @return void
     */
    public function jsonPreviousStats($year, $month) {
        $json = new wf_JqDtHelper();

        $this->statsDb->where('date', 'LIKE', $year . "-" . $month . "-%");
        $all = $this->statsDb->getAll();

        $data = array();
        $actColumnFlag = (cfr('ROOT')) ? true : false;
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $data[] = $each['id'];
                $data[] = $each['date'];
                $data[] = $this->colorMissed($each['missedcount']);
                $data[] = $each['recallscount'];
                $data[] = $each['unsucccount'];
                $reactTime = (!empty($each['totaltrytime'])) ? zb_formatTime(($each['totaltrytime'] / ($each['recallscount'] + $each['unsucccount']))) : '-';
                $data[] = $reactTime;
                $data[] = $this->cutString($each['missednumbers'], 45);
                if ($actColumnFlag) {
                    $data[] = wf_modalAuto(web_edit_icon(), __('Edit') . ' ' . $each['date'], $this->renderStatsEditForm($each));
                }
                $json->addRow($data);
                unset($data);
            }
        }
        $json->getJson();
    }

}
