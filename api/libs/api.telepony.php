<?php

/**
 * Uncomplicated telephony class
 */
class TelePony {

    /**
     * Contains system alter.ini config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains system billing.ini config as key=>value
     *
     * @var array
     */
    protected $billCfg = array();

    /**
     * All available phonebook contacts as number=>contact
     *
     * @var array
     */
    protected $allContacts = array();

    /**
     * System messages helper paceholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Some predefined routes, URLs, paths etc..
     */
    const URL_ME = '?module=telepony';
    const ROUTE_AJCALLSHIST = 'ajaxcallshist';
    const ROUTE_INCALLSTATS = 'incallstats';
    const ROUTE_CALLSHIST = 'callshistory';
    const ROUTE_NIGHTCALLS = 'nightcalls';
    const ROUTE_DFROM = 'datefrom';
    const ROUTE_DTO = 'dateto';
    const ROUTE_DLOADCDR = 'downloadcdr';
    const PATH_CDRDEBUG = 'exports/teleponycdrdebug.log';

    /**
     * Creates new TelePony instance
     * 
     * @return void
     */
    public function __construct() {
        $this->loadConfigs();
        $this->initMessages();
    }

    /**
     * Inits system messages helper
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Preloads required configs into protected properties
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfigs() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
        $this->billCfg = $ubillingConfig->getBilling();
    }

    /**
     * Renders basic module controls
     * 
     * @return string
     */
    public function renderControls() {
        $result = '';
        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_INCALLSTATS . '=true', wf_img('skins/icon_stats_16.gif') . ' ' . __('Stats') . ' ' . __('Incoming calls'), false, 'ubButton') . ' ';
        if ($this->altCfg['TELEPONY_CDR']) {
            $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_CALLSHIST . '=true', wf_img('skins/icon_cdr.png') . ' ' . __('Calls history'), false, 'ubButton') . ' ';
            $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_NIGHTCALLS . '=true', wf_img('skins/icon_moon.png') . ' ' . __('Calls during non-business hours'), false, 'ubButton') . ' ';
        }
        return($result);
    }

    /**
     * Renders incoming calls stats if it exists
     * 
     * @return string
     */
    public function renderNumLog() {

        $logPath = PBXNum::LOG_PATH;
        $catPath = $this->billCfg['CAT'];
        $grepPath = $this->billCfg['GREP'];

        $replyOffset = 5;
        $numberOffset = 2;
        $loginOffset = 7;
        $replyCount = 0;
        $replyStats = array();
        $replyNames = array(
            0 => __('Not found'),
            1 => __('Active'),
            2 => __('Debt'),
            3 => __('Frozen')
        );

        $result = '';
        if (file_exists($logPath)) {
            if (!wf_CheckPost(array('numyear', 'nummonth'))) {
                $curYear = curyear();
                $curMonth = date("m");
            } else {
                $curYear = ubRouting::post('numyear', 'int');
                $curMonth = ubRouting::post('nummonth', 'int');
            }
            $parseDate = $curYear . '-' . $curMonth;

            $dateInputs = wf_YearSelectorPreset('numyear', __('Year'), false, $curYear) . ' ';
            $dateInputs .= wf_MonthSelector('nummonth', __('Month'), $curMonth, false) . ' ';
            $dateInputs .= wf_Submit(__('Show'));
            $result .= wf_Form('', 'POST', $dateInputs, 'glamour');

            $rawLog = shell_exec($catPath . ' ' . $logPath . ' | ' . $grepPath . ' ' . $parseDate . '-');
            if (!empty($rawLog)) {
                $rawLog = explodeRows($rawLog);
                if (!empty($rawLog)) {
                    foreach ($rawLog as $io => $each) {
                        if (!empty($each)) {
                            $line = explode(' ', $each);
                            $callReply = $line[$replyOffset];
                            if (isset($replyStats[$callReply])) {
                                $replyStats[$callReply] ++;
                            } else {
                                $replyStats[$callReply] = 1;
                            }
                            $replyCount++;
                        }
                    }

                    if (!empty($replyStats)) {
                        $cells = wf_TableCell(__('Calls'));
                        $cells .= wf_TableCell(__('Count'));
                        $rows = wf_TableRow($cells, 'row1');
                        foreach ($replyStats as $replyCode => $callsCount) {
                            $cells = wf_TableCell($replyNames[$replyCode]);
                            $cells .= wf_TableCell($callsCount);
                            $rows .= wf_TableRow($cells, 'row5');
                        }
                        $result .= wf_TableBody($rows, '100%', 0, 'sortable');
                        $result .= __('Total') . ': ' . $replyCount;
                    }
                }
            } else {
                $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('File not exist') . ': ' . $logPath, 'error');
        }
        return($result);
    }

    /**
     * Returns raw CDR for selected period of time
     * 
     * @param string $cdrConf
     * @param string $dateFrom
     * @param string $dateTo
     * 
     * @return array/bool
     */
    public function getCDR($dateFrom = '', $dateTo = '') {
        $result = array();
        $cdrConf = $this->altCfg['TELEPONY_CDR'];
        if (!empty($cdrConf)) {
            $cdrConf = explode('|', $cdrConf);
            if (sizeof($cdrConf) == 5) {
                if ($dateFrom AND $dateTo) {
                    $dateFrom .= ' 00:00:00';
                    $dateTo .= ' 23:59:59';
                }

                $host = $cdrConf[0];
                $login = $cdrConf[1];
                $password = $cdrConf[2];
                $db = $cdrConf[3];
                $table = $cdrConf[4];

                $cdr = new PBXCdr($host, $login, $password, $db, $table);
                $result = $cdr->getCDR($dateFrom, $dateTo);
            } else {
                $result = false;
            }
        } else {
            $result = false;
        }

        return($result);
    }

    /**
     * Groups all calls from CDR as uniqueFlowId=>call records
     * 
     * @param array $rawCdr
     * 
     * @return array
     */
    protected function groupCDRflows($rawCdr) {
        $result = array();
        if (!empty($rawCdr)) {
            foreach ($rawCdr as $io => $each) {
                $flowId = $each['uniqueid'];
                $result[$flowId][] = $each;
            }
        }
        return($result);
    }

    /**
     * Parses channel data to extract peer number
     * 
     * @param string $channel
     * 
     * @return int
     */
    protected function parseChannel($channel) {
        $result = '';
        if (!empty($channel)) {
            $cleanChan = str_replace('SIP/', '', $channel);
            $explodedChan = explode('-', $cleanChan);
            if (isset($explodedChan[1])) {
                $result = $explodedChan[0];
            }
        }
        return($result);
    }

    /**
     * Parses flow data into humanic stats array.
     * Fields: flowid, callstart, callend, from, to, records, direction,
     * status,duration,realtime,takephone, context, app
     * 
     * @param array $flowData
     * 
     * @return string
     */
    protected function parseCDRFlow($flowData) {
        $result = array();
        if (!empty($flowData)) {
            $initialRecord = $flowData[0];
            $finalRecord = end($flowData);
            $recordCount = sizeof($flowData);
            $result['flowid'] = $initialRecord['uniqueid'];
            $result['callstart'] = $initialRecord['calldate'];
            $result['callend'] = $finalRecord['calldate'];
            $result['from'] = $initialRecord['src'];
            $destination = $finalRecord['dst'];
            //fuck this shit
            if ($destination == 's') {
                $destination = $this->parseChannel($finalRecord['dstchannel']);
            }
            $result['to'] = $destination;
            $result['records'] = $recordCount;
            $result['direction'] = $finalRecord['userfield'];
            $result['status'] = $finalRecord['disposition'];
            $result['duration'] = $finalRecord['duration'];
            $result['realtime'] = 0;
            $result['takephone'] = '';
            if ($finalRecord['disposition'] == 'ANSWERED') {
                $result['realtime'] = $finalRecord['billsec'];
                $result['takephone'] = $destination;
            }
            $result['context'] = $finalRecord['dcontext'];
            $result['app'] = $finalRecord['lastapp'];
        }
        return($result);
    }

    /**
     * Returns styled call status lable depend of disposiotion
     * 
     * @param string $rawStatus
     * 
     * @return string
     */
    protected function renderCallStatus($rawStatus) {
        $result = '';
        $callStatus = '';
        $statusIcon = '';
        switch ($rawStatus) {
            case 'ANSWERED':
                $callStatus = __('Answered');
                $statusIcon = wf_img('skins/calls/phone_green.png');
                break;

            case 'NO ANSWER':
                $callStatus = __('No answer');
                $statusIcon = wf_img('skins/calls/phone_red.png');
                break;

            case 'BUSY':
                $callStatus = __('Busy');
                $statusIcon = wf_img('skins/calls/phone_yellow.png');
                break;

            case 'FAILED':
                $callStatus = __('Failed');
                $statusIcon = wf_img('skins/calls/phone_fail.png');
                break;

            default :
                $callStatus = $rawStatus;
                break;
        }
        $result = $statusIcon . ' ' . $callStatus;
        return($result);
    }

    /**
     * Returns some contact if available in phonebook
     * 
     * @param string $number
     * 
     * @return string
     */
    protected function renderNumber($number) {
        $result = $number;
        if (!empty($number)) {
            if (isset($this->allContacts[$number])) {
                $result .= ' - ' . $this->allContacts[$number];
            }
        }
        return($result);
    }

    /**
     * Renders CDR date selection form
     * 
     * @return string
     */
    public function renderCdrDateForm() {
        $inputs = '';
        if (ubRouting::checkPost(array(self::ROUTE_DFROM, self::ROUTE_DTO))) {
            $dateFrom = ubRouting::post(self::ROUTE_DFROM);
            $dateTo = ubRouting::post(self::ROUTE_DTO);
        } else {
            $dateFrom = curdate();
            $dateTo = curdate();
        }
        $inputs .= wf_DatePickerPreset(self::ROUTE_DFROM, $dateFrom) . ' ' . __('From') . ' ';
        $inputs .= wf_DatePickerPreset(self::ROUTE_DTO, $dateTo) . ' ' . __('To') . ' ';
        $inputs .= wf_Submit(__('Show'));
        $result = wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Renders calls history data container
     * 
     * @return void
     */
    public function renderCDR() {
        $result = '';
        if (ubRouting::checkPost(array(self::ROUTE_DFROM, self::ROUTE_DTO))) {
            $dateFrom = ubRouting::post(self::ROUTE_DFROM);
            $dateTo = ubRouting::post(self::ROUTE_DTO);
        } else {
            $dateFrom = curdate();
            $dateTo = curdate();
        }
        $columns = array('#', __('Time'), __('From'), __('To'), __('Picked up'), __('Type'), __('Status'), __('Talk time'));
        $opts = '"order": [[ 0, "desc" ]]';
        $ajDataUrl = self::URL_ME . '&' . self::ROUTE_CALLSHIST . '=true' . '&' . self::ROUTE_AJCALLSHIST . '=true';
        $ajDataUrl .= '&' . self::ROUTE_DFROM . '=' . $dateFrom . '&' . self::ROUTE_DTO . '=' . $dateTo;
        $result .= wf_JqDtLoader($columns, $ajDataUrl, false, __('Calls'), 50, $opts);

        if (cfr('ROOT')) {
            if (file_exists(self::PATH_CDRDEBUG)) {
                $result .= wf_delimiter(0);
                $dlUrl = self::URL_ME . '&' . self::ROUTE_CALLSHIST . '=true' . '&' . self::ROUTE_DLOADCDR . '=true';
                $result .= wf_Link($dlUrl, wf_img('skins/icon_download.png') . ' ' . __('Download') . ' ' . __('CDR'), false, 'ubButton');
            }
        }
        return($result);
    }

    /**
     * Renders some calls history JSON data
     * 
     * @return void
     */
    public function getCDRJson() {
        $result = '';
        $dateFrom = ubRouting::get('datefrom');
        $dateTo = ubRouting::get('dateto');
        $rawCdr = $this->getCDR($dateFrom, $dateTo);
        $json = new wf_JqDtHelper();

        //preload phonebook contacts
        if ($this->altCfg['PHONEBOOK_ENABLED']) {
            $phoneBook = new PhoneBook();
            $this->allContacts = $phoneBook->getAllContacts();
        }

        if ($rawCdr !== false) {
            $flows = array();
            if (!empty($rawCdr)) {
                $flows = $this->groupCDRflows($rawCdr);
                $flowCounter = 0;

                if (!empty($flows)) {
                    foreach ($flows as $eachFlowId => $eachFlowData) {
                        $flowCounter++;
                        $callData = $this->parseCDRFlow($eachFlowData);
                        $callDirection = '';
                        //setting call direction icon
                        if ($callData['direction'] == 'in' OR $callData['app'] == 'Queue') {
                            $callDirection = wf_img('skins/calls/incoming.png') . ' ';
                        } else {
                            $callDirection = wf_img('skins/calls/outgoing.png') . ' ';
                        }

                        $data[] = $flowCounter;
                        $data[] = $callDirection . $callData['callstart'];
                        $data[] = $this->renderNumber($callData['from']);
                        $data[] = $this->renderNumber($callData['to']);
                        $data[] = $this->renderNumber($callData['takephone']);
                        $data[] = __($callData['app']);
                        $data[] = $this->renderCallStatus($callData['status']);
                        $data[] = zb_formatTime($callData['realtime']);
                        $json->addRow($data);
                        unset($data);
                    }
                }
            }
            //writing debug log
            file_put_contents(self::PATH_CDRDEBUG, print_r($rawCdr, true));
        }
        $json->getJson();
    }

    /**
     * Fetches and preprocess some missed calls from CDR
     * 
     * @return array
     */
    public function fetchMissedCalls() {
        $result = array(
            'unanswered' => array(),
            'recalled' => array()
        );

        $minNumLen = 5;
        $countryCode = '380';
        $unansweredCalls = array();
        $recalledCalls = array();
        $missedTries = array();
        $callsTmp = array();
        $normalCalls = array();
        $rawCalls = $this->getCDR(curdate(), curdate());

        if (!empty($rawCalls)) {
            $normalCalls = $this->groupCDRflows($rawCalls);
        }
        if (!empty($normalCalls)) {
            foreach ($normalCalls as $io => $each) {
                $callData = $this->parseCDRFlow($each);
                $startTime = $callData['callstart'];
                $incomingNumber = $callData['from'];
                $destinationNumber = $callData['to'];

                if (ispos($incomingNumber, $countryCode)) {
                    $incomingNumber = str_replace($countryCode, '', $incomingNumber);
                }

                if (ispos($destinationNumber, $countryCode)) {
                    $destinationNumber = str_replace($countryCode, '', $destinationNumber);
                }

                //not answered call
                if ($callData['status'] != 'ANSWERED' AND $callData['app'] == 'Queue') {
                    //excluding internal numbers
                    if (strlen((string) $incomingNumber) >= $minNumLen) {
                        $unansweredCalls[$incomingNumber] = $callData;
                        $unansweredCalls[$incomingNumber][9] = $startTime; //last time compat
                        //unanswered calls count
                        if (isset($missedTries[$incomingNumber])) {
                            $missedTries[$incomingNumber] ++;
                        } else {
                            $missedTries[$incomingNumber] = 1;
                        }
                    }
                }


                //incoming answered calls after miss
                if (isset($unansweredCalls[$incomingNumber])) {
                    if ($callData['from'] == $incomingNumber AND $callData['status'] == 'ANSWERED') {
                        unset($unansweredCalls[$incomingNumber]);
                    }
                }

                //recall try on missed number
                if (isset($unansweredCalls[$destinationNumber])) {
                    $reactionTime = 0;
                    $missTime = $unansweredCalls[$destinationNumber]['callend'];
                    $missTime = strtotime($missTime);
                    if ($callData['status'] == 'ANSWERED') {
                        unset($unansweredCalls[$destinationNumber]);
                        $answerTime = strtotime($callData['callstart']);
                        $reactionTime = $answerTime - $missTime;
                        $recalledCalls[$destinationNumber]['time'] = $callData['realtime'];
                        $recalledCalls[$destinationNumber]['count'] = 1;
                        $recalledCalls[$destinationNumber]['trytime'] = $reactionTime;
                    } else {
                        $reactionTime = time() - $missTime;
                        $recalledCalls[$destinationNumber]['time'] = 0;
                        @$recalledCalls[$destinationNumber]['count'] ++;
                        @$recalledCalls[$destinationNumber]['trytime'] = $reactionTime;
                    }
                }

                //Unknown numbers not require recall
                if (ispos($incomingNumber, 'Unknown')) {
                    unset($unansweredCalls[$incomingNumber]);
                }
            }
        }


        //appending tries to final result
        if (!empty($missedTries)) {
            foreach ($missedTries as $missedNumber => $missCount) {
                if (isset($unansweredCalls[$missedNumber])) {
                    $unansweredCalls[$missedNumber]['misscount'] = $missCount;
                }
            }
        }

        $result['unanswered'] = $unansweredCalls;
        $result['recalled'] = $recalledCalls;

        return($result);
    }

    /**
     * Returns calls stats data for current month for the exhorse
     * 
     * @return array
     */
    public function getHorseMonthData() {
        $result = array(
            'a_totalcalls' => 0,
            'a_totalanswered' => 0,
            'a_totalcallsduration' => 0,
            'a_averagecallduration' => 0,
        );
        //working time setup
        $rawWorkTime = $this->altCfg['WORKING_HOURS'];
        $rawWorkTime = explode('-', $rawWorkTime);
        $workStartTime = $rawWorkTime[0];
        $workEndTime = $rawWorkTime[1];

        $rawCdr = $this->getCDR(curmonth() . '-01', curdate());
        if ($rawCdr !== false) {
            if (!empty($rawCdr)) {
                $normalCalls = $this->groupCDRflows($rawCdr);

                foreach ($normalCalls as $eachFlow => $flowData) {
                    $callData = $this->parseCDRFlow($flowData);
                    //Only incoming calls
                    if ($callData['direction'] = 'in' OR $callData['app'] == 'Queue') {
                        $callStartTime = $callData['callstart'];
                        //Only work time
                        if (zb_isTimeBetween($workStartTime, $workEndTime, $callStartTime)) {
                            $result['a_totalcalls'] ++;
                            $result['a_totalcallsduration'] += $callData['realtime'];
                            if ($callData['status'] == 'ANSWERED') {
                                $result['a_totalanswered'] ++;
                            }
                        }
                    }
                }
                //prevent division by zero on no answered incoming calls
                if ($result['a_totalanswered'] != 0) {
                    $result['a_averagecallduration'] = $result['a_totalcallsduration'] / $result['a_totalanswered'];
                }
            }
        }

        return($result);
    }

    /**
     * Renders calls during non-business hours list
     * 
     * @return string
     */
    public function renderNightCalls() {
        $result = '';
        $nightMode = array();
        $minNumLen = 5;
        //date range setup
        $dateFrom = curmonth() . '-01';
        $dateTo = curdate();
        //working time setup
        $rawWorkTime = $this->altCfg['WORKING_HOURS'];
        $rawWorkTime = explode('-', $rawWorkTime);
        $workStartTime = $rawWorkTime[0];
        $workEndTime = $rawWorkTime[1];

        //filling WDYC recalled cache
        $recalledCache = array();
        if ($this->altCfg['WDYC_ENABLED']) {
            if (file_exists(WhyDoYouCall::CACHE_RECALLED)) {
                $recalledCache = file_get_contents(WhyDoYouCall::CACHE_RECALLED);
                $recalledCache = unserialize($recalledCache);
            }
        }

        $rawCdr = $this->getCDR($dateFrom, $dateTo);
        if ($rawCdr !== false) {
            $flows = array();
            if (!empty($rawCdr)) {
                $flows = $this->groupCDRflows($rawCdr);
                $flowCounter = 0;

                if (!empty($flows)) {
                    foreach ($flows as $eachFlowId => $eachFlowData) {
                        $flowCounter++;
                        $callData = $this->parseCDRFlow($eachFlowData);
                        $number = $callData['from'];

                        $date = date("Y-m-d", strtotime($callData['callstart']));
                        $cutTime = date("H:i:s", strtotime($callData['callstart']));

                        //night calls preprocessing
                        if (!zb_isTimeBetween($workStartTime, $workEndTime, $cutTime)) {
                            if (strlen((string) $number) >= $minNumLen) {
                                $nightMode[$date][$number][] = $cutTime;
                            }
                        }
                    }

                    //render data
                    if (!empty($nightMode)) {
                        krsort($nightMode);
                        $allAddress = zb_AddressGetFulladdresslistCached();
                        $telepathy = new Telepathy(false, true, false, true);
                        $telepathy->usePhones();
                        $cells = wf_TableCell(__('Date'));
                        $cells .= wf_TableCell(__('Phones'));

                        $rows = wf_TableRow($cells, 'row1');
                        foreach ($nightMode as $date => $numbers) {
                            $cells = wf_TableCell($date);
                            $nums = '';
                            $times = '';
                            $guessedLogin = '';
                            $ncells = wf_TableCell(__('Number'), '30%');
                            $ncells .= wf_TableCell(__('User'), '30%');
                            $ncells .= wf_TableCell(__('Time'), '30%');
                            $nrows = wf_TableRow($ncells, 'row1');
                            $nums .= wf_TableBody($nrows, '100%', 0);
                            foreach ($numbers as $eachNumber => $eachTime) {
                                $guessedLogin = $telepathy->getByPhoneFast($eachNumber, true, true);
                                if ($guessedLogin) {
                                    $userLabel = wf_link(UserProfile::URL_PROFILE . $guessedLogin, web_profile_icon() . ' ' . @$allAddress[$guessedLogin]);
                                } else {
                                    $userLabel = '';
                                }

                                $eachTime = implode(', ', $eachTime);
                                $ncells = wf_TableCell($eachNumber, '30%');
                                $ncells .= wf_TableCell($userLabel, '30%');
                                $ncells .= wf_TableCell($eachTime, '30%');
                                if (isset($recalledCache[$eachNumber])) {
                                    $numClass = 'todaysig';
                                } else {
                                    $numClass = 'row5';
                                }

                                $nrows = wf_TableRow($ncells, $numClass);
                                $nums .= wf_TableBody($nrows, '100%', 0);
                            }
                            $cells .= wf_TableCell($nums);

                            $rows .= wf_TableRow($cells, 'row3');
                        }


                        $result .= wf_TableBody($rows, '100%', 0, '');
                    } else {
                        $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'info');
                    }
                }
            } else {
                $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Strange exception') . ' EX_CDR_GET_FAIL', 'error');
        }
        return($result);
    }

}
