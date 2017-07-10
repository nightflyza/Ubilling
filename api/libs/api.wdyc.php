<?php

class WhyDoYouCall {

    /**
     * System alter.ini config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains array of available user phones as phonenumber=>login
     *
     * @var array
     */
    protected $phoneBase = array();

    /**
     * Askozia PBX web-interface URL
     *
     * @var string
     */
    protected $askoziaUrl = '';

    /**
     * Askozia PBX administrators login
     *
     * @var string
     */
    protected $askoziaLogin = '';

    /**
     * Askozia PBX administrators password
     *
     * @var string
     */
    protected $askoziaPassword = '';

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

    public function __construct() {
        $this->loadConfig();
        $this->initMessages();
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
        if ($this->altCfg['ASKOZIA_ENABLED']) {
            $this->askoziaUrl = zb_StorageGet('ASKOZIAPBX_URL');
            $this->askoziaLogin = zb_StorageGet('ASKOZIAPBX_LOGIN');
            $this->askoziaPassword = zb_StorageGet('ASKOZIAPBX_PASSWORD');
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
     * Askozia PBX data fetching and processing
     * 
     * @return array
     */
    protected function fetchAskoziaCalls() {
        $unansweredCalls = array();
        $recalledCalls = array();
        $missedTries = array();
        if ((!empty($this->askoziaUrl)) AND ( !empty($this->askoziaLogin)) AND ( !empty($this->askoziaPassword))) {
            $callsTmp = array();
            $normalCalls = array();

            $fields = array(
                'extension_number' => 'all',
                'cdr_filter' => 'incomingoutgoing',
                'period_from' => curdate(),
                'period_to' => curdate(),
                'date_format' => 'Y-m-d',
                'time_format' => 'H:i:s',
                'page_format' => 'A4',
                'SubmitCSVCDR' => 'Download CSV');

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_URL, $this->askoziaUrl . '/status_cdr.php');
            curl_setopt($ch, CURLOPT_USERPWD, $this->askoziaLogin . ":" . $this->askoziaPassword);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
            $rawResult = curl_exec($ch);

            curl_close($ch);

            if (!empty($rawResult)) {
                $callsTmp = explodeRows($rawResult);
                if (!empty($callsTmp)) {
                    foreach ($callsTmp as $eachline) {
                        $explode = explode(';', $eachline); //in 2.2.8 delimiter changed from ," to ;
                        if (!empty($eachline)) {
                            $normalCalls[] = str_replace('"', '', $explode);
                        }
                    }
                }

                if (!empty($normalCalls)) {
                    unset($normalCalls[0]);
                    foreach ($normalCalls as $io => $each) {
                        $startTime = explode(' ', $each[9]);
                        @$startTime = $startTime[1];
                        $incomingNumber = $each[1];
                        $destinationNumber = $each[2];
                        //calls with less then 24 hours duration
                        if ($each['13'] < 86400) {
                            //not answered call
                            if (ispos($each[14], 'NO ANSWER') OR ( ispos($each[7], 'VoiceMail'))) {
                                if (!ispos($each[16], 'outbound')) {
                                    //excluding internal numbers
                                    if (strlen((string) $incomingNumber) > 3) {
                                        $unansweredCalls[$incomingNumber] = $each;
                                        //unanswered calls count
                                        if (isset($missedTries[$incomingNumber])) {
                                            $missedTries[$incomingNumber] ++;
                                        } else {
                                            $missedTries[$incomingNumber] = 1;
                                        }
                                    }
                                }
                            } else {
                                //call was answered after this
                                if (isset($unansweredCalls[$incomingNumber])) {
                                    unset($unansweredCalls[$incomingNumber]);
                                }
                            }

                            //outcoming answered calls
                            if (($each[2] == $incomingNumber) AND ( ispos($each[14], 'ANSWERED'))) {
                                if (isset($unansweredCalls[$incomingNumber])) {
                                    unset($unansweredCalls[$incomingNumber]);
                                }
                            }

                            //outcoming call success - deleting form unanswered, adding it to recalled cache
                            if (ispos($each[16], 'outbound')) {
                                if (ispos($each[14], 'ANSWERED')) {
                                    if ((isset($unansweredCalls[$destinationNumber]))) {
                                        unset($unansweredCalls[$destinationNumber]);
                                        if (isset($recalledCalls[$destinationNumber])) {
                                            $recalledCalls[$destinationNumber]['time']+= $each[13];
                                            $recalledCalls[$destinationNumber]['count'] ++;
                                        } else {
                                            $recalledCalls[$destinationNumber]['time'] = $each[13];
                                            $recalledCalls[$destinationNumber]['count'] = 1;
                                        }
                                    }
                                    $uglyHack = '38' . $destinationNumber; //lol
                                    if (isset($unansweredCalls[$uglyHack])) {
                                        unset($unansweredCalls[$uglyHack]);
                                        if (isset($recalledCalls[$uglyHack])) {
                                            $recalledCalls[$uglyHack]['time']+= $each[13];
                                            $recalledCalls[$uglyHack]['count'] ++;
                                        } else {
                                            $recalledCalls[$uglyHack]['time'] = $each[13];
                                            $recalledCalls[$uglyHack]['count'] = 1;
                                        }
                                    }
                                } else {
                                    //unsuccessful recall try
                                    if ((isset($unansweredCalls[$destinationNumber]))) {
                                        if (isset($recalledCalls[$destinationNumber])) {
                                            $recalledCalls[$destinationNumber]['time']+= $each[13];
                                            $recalledCalls[$destinationNumber]['count'] ++;
                                        } else {
                                            $recalledCalls[$destinationNumber]['time'] = $each[13];
                                            $recalledCalls[$destinationNumber]['count'] = 1;
                                        }
                                    }
                                    $uglyHack = '38' . $destinationNumber;
                                    if (isset($unansweredCalls[$uglyHack])) {
                                        if (isset($recalledCalls[$uglyHack])) {
                                            $recalledCalls[$uglyHack]['time']+= $each[13];
                                            $recalledCalls[$uglyHack]['count'] ++;
                                        } else {
                                            $recalledCalls[$uglyHack]['time'] = $each[13];
                                            $recalledCalls[$uglyHack]['count'] = 1;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        //appending trys to final result
        if (!empty($missedTries)) {
            foreach ($missedTries as $missedNumber => $missCount) {
                if (isset($unansweredCalls[$missedNumber])) {
                    $unansweredCalls[$missedNumber]['misscount'] = $missCount;
                }
            }
        }

        //filling recalled calls cache
        file_put_contents(self::CACHE_RECALLED, serialize($recalledCalls));
        return ($unansweredCalls);
    }

    /**
     * Fetches unanswered calls data from Askozia and stored it into cache
     * 
     * @return void
     */
    public function pollUnansweredCalls() {
        $unansweredCalls = $this->fetchAskoziaCalls();
        $storeData = serialize($unansweredCalls);
        file_put_contents(self::CACHE_FILE, $storeData);
    }

    /**
     * Loads and prepares all existing users phones
     * 
     * @return void
     */
    protected function loadPhonebase() {
        $query = "SELECT * from `phones`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $cleanMobile = vf($each['mobile'], 3);
                $cleanPhone = vf($each['phone'], 3);
                if (!empty($cleanMobile)) {
                    $this->phoneBase[$cleanMobile] = $each['login'];
                }

                if ((!isset($this->altCfg['WDYC_ONLY_MOBILE'])) OR ( !@$this->altCfg['WDYC_ONLY_MOBILE'])) {
                    if (!empty($cleanPhone)) {
                        $this->phoneBase[$cleanPhone] = $each['login'];
                    }
                }
            }
        }
    }

    /**
     * Trys to detect user login by phone number
     * 
     * @param string $phoneNumber
     * 
     * @return string
     */
    protected function userLoginTelepathy($phoneNumber) {
        $result = '';
        if (!empty($this->phoneBase)) {
            foreach ($this->phoneBase as $baseNumber => $userLogin) {
                if (ispos((string) $phoneNumber, (string) $baseNumber)) {
                    $result = $userLogin;
                    return ($result);
                }
            }
        }
        return ($result);
    }

    /**
     * Renders module controls
     * 
     * @return string
     */
    public function panel() {
        $result = '';
        if (!wf_CheckGet(array('renderstats'))) {
            $result.= wf_Link(self::URL_ME, wf_img_sized('skins/icon_phone.gif', '', '16', '16') . ' ' . __('Calls'), false, 'ubButton') . ' ';
            $result.= wf_Link(self::URL_ME . '&renderstats=true', wf_img_sized('skins/icon_stats.gif', '', '16', '16') . ' ' . __('Stats'), false, 'ubButton');
        } else {
            $result.=wf_BackLink(self::URL_ME);
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
        $this->loadPhonebase();
        $this->allUserNames = zb_UserGetAllRealnames();
        $this->allUserAddress = zb_AddressGetFulladdresslistCached();

        if (file_exists(self::CACHE_FILE)) {
            $rawData = file_get_contents(self::CACHE_FILE);
            if (!empty($rawData)) {
                $rawData = unserialize($rawData);
                if (!empty($rawData)) {
                    $totalCount = 0;
                    $cells = wf_TableCell(__('Number'));
                    $cells.= wf_TableCell(__('Last call time'));
                    $cells.= wf_TableCell(__('Number of attempts to call'));
                    $cells.= wf_TableCell(__('User'));

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
                        $cells.= wf_TableCell($callData[9]);
                        $cells.= wf_TableCell($callData['misscount']);
                        $cells.= wf_TableCell($profileLink);

                        $rows.= wf_TableRow($cells, 'row5');
                        $totalCount++;
                    }
                    $result = wf_TableBody($rows, '100%', 0, 'sortable');
                    $result.= __('Total') . ': ' . $totalCount;
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
                    $cells.= wf_TableCell(__('Number of attempts to call'));
                    $cells.= wf_TableCell(__('Talk time'));
                    $cells.= wf_TableCell(__('Status'));
                    $cells.= wf_TableCell(__('User'));

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
                        $cells.= wf_TableCell($callData['count']);
                        $cells.= wf_TableCell($callTimeFormated, '', '', 'sorttable_customkey="' . $callTime . '"');
                        $cells.= wf_TableCell($callStatus, '', '', 'sorttable_customkey="' . $callStatusFlag . '"');
                        $cells.= wf_TableCell($profileLink);
                        $rows.= wf_TableRow($cells, 'row5');
                        $totalCount++;
                    }
                    $result = wf_TableBody($rows, '100%', 0, 'sortable');
                    $result.= __('Total') . ': ' . $totalCount;
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
        $missedCallsNumbers = '';
        //missed calls stats
        if (file_exists(self::CACHE_FILE)) {
            $rawData = file_get_contents(self::CACHE_FILE);
            if (!empty($rawData)) {
                $rawData = unserialize($rawData);
                if (!empty($rawData)) {
                    $missedCallsCount = sizeof($rawData);
                    foreach ($rawData as $missedNumber => $callData) {
                        $missedCallsNumbers.= $missedNumber . ' ';
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
                    }
                }
            }
        }
        $missedCallsNumbers = mysql_real_escape_string($missedCallsNumbers);
        $query = "INSERT INTO `wdycinfo` (`id`, `date`, `missedcount`, `recallscount`, `unsucccount`, `missednumbers`) VALUES "
                . "(NULL, '" . $date . "', '" . $missedCallsCount . "', '" . $recallsCount . "', '" . $unsuccCount . "', '" . $missedCallsNumbers . "');";
        nr_query($query);
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
        $inputs.= wf_Selector('monthsel', $monthArr, __('Month'), $curMonth, false) . ' ';
        $inputs.= wf_Submit(__('Show'));
        $result.=wf_Form('', 'POST', $inputs, 'glamour');
        $result.=wf_CleanDiv();

        return ($result);
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

        $result.= $this->statsDateForm($year, $month);

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
        $columns = array('ID', 'Date', 'Missed calls', 'Recalled calls', 'Unsuccessful recalls', 'Phones');

        $query = "SELECT * from `wdycinfo` WHERE `date` LIKE '" . $year . "-" . $month . "-%';";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $gchartsData[] = array($each['date'], $each['missedcount'], $each['recallscount'], $each['unsucccount']);
                $totalMissed += $each['missedcount'];
                $totalRecalls += $each['recallscount'];
                $totalUnsucc += $each['unsucccount'];
            }

            $totalCalls+=$totalMissed + $totalRecalls + $totalUnsucc;
            $result.=wf_gchartsLine($gchartsData, __('Calls'), '100%', '300px;', $chartsOptions);
            $result.= wf_tag('strong') . __('Total') . ': ' . wf_tag('strong', true) . wf_tag('br');
            $result.= __('Missed calls') . ' - ' . $totalMissed . wf_tag('br');
            $result.= __('Recalled calls') . ' - ' . $totalRecalls . wf_tag('br');
            $result.= __('Unsuccessful recalls') . ' - ' . $totalUnsucc . wf_tag('br');
            $result.= __('Percent') . ' ' . __('Missed calls') . ' - ' . zb_PercentValue($totalCalls, abs($totalMissed - $totalUnsucc)) . '%';
            $result.= wf_tag('br');
            $result.= wf_tag('br');
            $result.= wf_JqDtLoader($columns, self::URL_ME . '&renderstats=true&ajaxlist=true&year=' . $year . '&month=' . $month, false, __('Calls'), 25, $jqDtOpts);
        } else {
            $result.= $this->messages->getStyledMessage(__('Nothing found'), 'warning');
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
     * Renders json for previous calls stats
     * 
     * @param int $year
     * @param int $month
     * 
     * @return void
     */
    public function jsonPreviousStats($year, $month) {
        $json = new wf_JqDtHelper();
        $query = "SELECT * from `wdycinfo` WHERE `date` LIKE '" . $year . "-" . $month . "-%';";
        $all = simple_queryall($query);
        $data = array();
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $data[] = $each['id'];
                $data[] = $each['date'];
                $data[] = $this->colorMissed($each['missedcount']);
                $data[] = $each['recallscount'];
                $data[] = $each['unsucccount'];
                $data[] = $this->cutString($each['missednumbers'], 45);
                $json->addRow($data);
                unset($data);
            }
        }
        $json->getJson();
    }

}

?>