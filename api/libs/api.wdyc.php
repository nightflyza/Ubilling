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
     * Contains path to the calls cache
     */
    const CACHE_FILE = 'exports/whydoyoucall.dat';

    /**
     * Contains user profile base URL
     */
    const URL_PROFILE = '?module=userprofile&username=';

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
                                    $unansweredCalls[$incomingNumber] = $each;
                                    //unanswered calls count
                                    if (isset($missedTries[$incomingNumber])) {
                                        $missedTries[$incomingNumber] ++;
                                    } else {
                                        $missedTries[$incomingNumber] = 1;
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

                            //outcoming call success
                            if (ispos($each[16], 'outbound')) {
                                if (ispos($each[14], 'ANSWERED')) {
                                    if ((isset($unansweredCalls[$destinationNumber]))) {
                                        unset($unansweredCalls[$destinationNumber]);
                                    }
                                    $uglyHack = '38' . $destinationNumber; //lol
                                    if (isset($unansweredCalls[$uglyHack])) {
                                        unset($unansweredCalls[$uglyHack]);
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

                if (!empty($cleanPhone)) {
                    $this->phoneBase[$cleanPhone] = $each['login'];
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
     * Renders report of missed calls that required to be processed
     * 
     * @return string
     */
    public function renderMissedCallsReport() {
        $result = '';
        $this->loadPhonebase();
        $allUserNames = zb_UserGetAllRealnames();
        $allUserAddress = zb_AddressGetFulladdresslistCached();

        if (file_exists(self::CACHE_FILE)) {
            $rawData = file_get_contents(self::CACHE_FILE);
            if (!empty($rawData)) {
                $rawData = unserialize($rawData);
                if (!empty($rawData)) {
                    $cells = wf_TableCell(__('Number'));
                    $cells.= wf_TableCell(__('Last call time'));
                    $cells.= wf_TableCell(__('Number of attempts to call'));
                    $cells.= wf_TableCell(__('User'));

                    $rows = wf_TableRow($cells, 'row1');
                    foreach ($rawData as $number => $callData) {
                        $loginDetect = $this->userLoginTelepathy($number);

                        if (!empty($loginDetect)) {
                            $userAddress = @$allUserAddress[$loginDetect];
                            $userRealName = @$allUserNames[$loginDetect];
                            $profileLink = wf_Link(self::URL_PROFILE . $loginDetect, web_profile_icon() . ' ' . $userAddress, false) . ' ' . $userRealName;
                        } else {
                            $profileLink = '';
                        }
                        $cells = wf_TableCell(wf_tag('strong') . $number . wf_tag('strong', true));
                        $cells.= wf_TableCell($callData[9]);
                        $cells.= wf_TableCell($callData['misscount']);
                        $cells.= wf_TableCell($profileLink);

                        $rows.= wf_TableRow($cells, 'row5');
                    }
                    $result = wf_TableBody($rows, '100%', 0, 'sortable');
                } else {
                    $result = $this->messages->getStyledMessage(__('No missed calls at this time'), 'success');
                }
            }
        } else {
            $result = $this->messages->getStyledMessage(__('No unanswered calls cache available'), 'warning');
        }
        return ($result);
    }

}
?>