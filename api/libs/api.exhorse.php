<?php

class ExistentialHorse {

    /**
     * System alter.ini config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * All of available internet users as login=>userdata
     *
     * @var array
     */
    protected $allInetUsers = array();

    /**
     * Temporary storage for horseRun saving data
     *
     * @var array
     */
    protected $storeTmp = array();

    /**
     * Is complex tariffs feature enabled?
     *
     * @var bool
     */
    protected $complexFlag = false;

    /**
     * Array of complex tariffs strings masks
     *
     * @var array
     */
    protected $complexMasks = array();

    /**
     * Contains all current month internet user signups
     *
     * @var string
     */
    protected $monthSignups = array();

    /**
     * Contains users to cities bindings as login=>cityname
     *
     * @var array
     */
    protected $usersCities = array();

    /**
     * Contains current year and month in format Y-m
     *
     * @var string
     */
    protected $curmonth = '';

    /**
     * Is UKB subsystem enabled?
     *
     * @var bool
     */
    protected $ukvFlag = false;

    /**
     * Contains UKV illegal users tariff id
     *
     * @var int
     */
    protected $ukvIllegal = '';

    /**
     * Contains social users tariff id
     *
     * @var int
     */
    protected $ukvSocial = '';

    /**
     * Contains complex users tariff id
     *
     * @var int
     */
    protected $ukvComplex = '';

    /**
     * Contains month count for debt limit calculation
     *
     * @var int
     */
    protected $ukvDebtLimit = '';

    /**
     * Is Askozia PBX integration enabled?
     *
     * @var bool
     */
    protected $askoziaFlag = false;

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
     * PON enabled flag
     *
     * @var bool
     */
    protected $ponFlag = false;

    /**
     * Docsis enabled flag
     *
     * @var bool
     */
    protected $docsisFlag = false;

    /**
     * Year to display results
     *
     * @var string
     */
    protected $showYear = '';

    /**
     * System messages helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Base module URL
     */
    const URL_ME = '?module=exhorse';

    /**
     * Just debug flag
     */
    const DEBUG = false;

    public function __construct() {
        $this->loadConfig();
        $this->initTmp();
        $this->initMessages();
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
     * Preloads alter config, for further usage as key=>value
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfig() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
        //sets current month
        $this->curmonth = curmonth();

        //loading complex tariffs config
        if ($this->altCfg['COMPLEX_ENABLED']) {
            $this->complexFlag = true;

            if (!empty($this->altCfg['COMPLEX_MASKS'])) {
                $masksRaw = explode(",", $this->altCfg['COMPLEX_MASKS']);
                if (!empty($masksRaw)) {
                    foreach ($masksRaw as $eachmask) {
                        $this->complexMasks[] = trim($eachmask);
                    }
                }
            } else {
                throw new Exception(self::CPL_EMPTY_EX);
            }
        }

        //loading UKV options
        if ($this->altCfg['UKV_ENABLED']) {
            $this->ukvFlag = true;
            $this->ukvComplex = $this->altCfg['UKV_COMPLEX_TARIFFID'];
            $this->ukvIllegal = $this->altCfg['UKV_ILLEGAL_TARIFFID'];
            $this->ukvSocial = $this->altCfg['UKV_SOCIAL_TARIFFID'];
            $this->ukvDebtLimit = $this->altCfg['UKV_MONTH_DEBTLIMIT'];
        }

        //Askozia PBX integration
        if ($this->altCfg['ASKOZIA_ENABLED']) {
            $this->askoziaFlag = true;
            $this->askoziaUrl = zb_StorageGet('ASKOZIAPBX_URL');
            $this->askoziaLogin = zb_StorageGet('ASKOZIAPBX_LOGIN');
            $this->askoziaPassword = zb_StorageGet('ASKOZIAPBX_PASSWORD');
        }

        //PONizer enabled?
        if ($this->altCfg['PON_ENABLED']) {
            $this->ponFlag = true;
        }

        //is DOCSIS support enabled?
        if ($this->altCfg['DOCSIS_SUPPORT']) {
            $this->docsisFlag = true;
        }
    }

    /**
     * Inits empty temporary array with default struct.
     * 
     * @return void
     */
    protected function initTmp() {
        $this->storeTmp['u_totalusers'] = 0;
        $this->storeTmp['u_activeusers'] = 0;
        $this->storeTmp['u_inactiveusers'] = 0;
        $this->storeTmp['u_frozenusers'] = 0;
        $this->storeTmp['u_complextotal'] = 0;
        $this->storeTmp['u_complexactive'] = 0;
        $this->storeTmp['u_complexinactive'] = 0;
        $this->storeTmp['u_signups'] = 0;
        $this->storeTmp['u_citysignups'] = '';
        $this->storeTmp['f_totalmoney'] = 0;
        $this->storeTmp['f_paymentscount'] = 0;
        $this->storeTmp['f_arpu'] = 0;
        $this->storeTmp['f_arpau'] = 0;
        $this->storeTmp['c_totalusers'] = 0;
        $this->storeTmp['c_activeusers'] = 0;
        $this->storeTmp['c_inactiveusers'] = 0;
        $this->storeTmp['c_illegal'] = 0;
        $this->storeTmp['c_complex'] = 0;
        $this->storeTmp['c_social'] = 0;
        $this->storeTmp['c_totalmoney'] = 0;
        $this->storeTmp['c_paymentscount'] = 0;
        $this->storeTmp['c_arpu'] = 0;
        $this->storeTmp['c_arpau'] = 0;
        $this->storeTmp['c_totaldebt'] = 0;
        $this->storeTmp['c_signups'] = 0;
        $this->storeTmp['a_totalcalls'] = 0;
        $this->storeTmp['a_totalanswered'] = 0;
        $this->storeTmp['a_totalcallsduration'] = 0;
        $this->storeTmp['a_averagecallduration'] = 0;
        $this->storeTmp['e_switches'] = 0;
        $this->storeTmp['e_pononu'] = 0;
        $this->storeTmp['e_docsis'] = 0;
    }

    /**
     * Loads all of available inet users into protected property
     * 
     * @return void
     */
    protected function loadUserData() {
        $this->allInetUsers = zb_UserGetAllStargazerDataAssoc();
    }

    /**
     * Loads signups by current month
     * 
     * @return void
     */
    protected function loadSignups() {
        $query = "SELECT * from `userreg` WHERE `date` LIKE '" . $this->curmonth . "-%'";
        $raw = simple_queryall($query);
        if (!empty($raw)) {
            $this->monthSignups = $raw;
        }
    }

    /**
     * Preloads user and cities  mappings into protected property
     * 
     * @return void
     */
    protected function loadUserCities() {
        $this->usersCities = zb_AddressGetCityUsers();
    }

    /**
     * Sets year to render results
     * 
     * @param string $month
     * 
     * @return void
     */
    public function setYear($year) {
        $this->showYear = vf($year, 3);
    }

    /**
     * Detects user activity state
     * 
     * @param array $userData
     * 
     * @return int 1 - active, 0 - inactive, -1 - frozen
     */
    protected function isActive($userData) {
        /**
         * Ой, чий то кінь стоїть,
         * Що сива гривонька.
         * Сподобалась мені,
         * Сподобалась мені
         * Тая дівчинонька.
         */
        $result = '';
        if (($userData['Cash'] >= '-' . $userData['Credit']) AND ( $userData['AlwaysOnline'] == 1) AND ( $userData['Passive'] == 0) AND ( $userData['Down'] == 0)) {
            $result = 1;
        }
        if (($userData['Cash'] < '-' . $userData['Credit']) AND ( $userData['AlwaysOnline'] == 1) AND ( $userData['Passive'] == 0) AND ( $userData['Down'] == 0)) {
            $result = 0;
        }
        if (($userData['Cash'] < '-' . $userData['Credit']) AND ( $userData['AlwaysOnline'] == 1) AND ( $userData['Passive'] == 0) AND ( $userData['Down'] == 1)) {
            $result = 0;
        }
        if ($userData['Passive'] == 1) {
            $result = -1;
        }
        return ($result);
    }

    /**
     * Detect is user complex or not?
     * 
     * @param array $userArray
     * 
     * @return bool
     */
    protected function isComplex($userArray) {
        $result = false;
        if ($this->complexFlag) {
            if (!empty($this->complexMasks)) {
                foreach ($this->complexMasks as $io => $eachMask) {
                    if (ispos($userArray['Tariff'], $eachMask)) {
                        $result = true;
                        break;
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Do some user preprocessing
     * 
     * @return void
     */
    protected function preprocessUserData() {
        $this->loadUserData();
        $this->loadUserCities();
        $this->loadSignups();

        if (!empty($this->allInetUsers)) {
            foreach ($this->allInetUsers as $io => $eachUser) {
                //active users
                if ($this->isActive($eachUser) == 1) {
                    $this->storeTmp['u_activeusers'] ++;
                }
                //inactive users
                if ($this->isActive($eachUser) == 0) {
                    $this->storeTmp['u_inactiveusers'] ++;
                }
                //just frozen bodies
                if ($this->isActive($eachUser) == -1) {
                    $this->storeTmp['u_frozenusers'] ++;
                }

                //complex users detection
                if ($this->isComplex($eachUser)) {
                    $this->storeTmp['u_complextotal'] ++;
                    //active complex users
                    if ($this->isActive($eachUser) == 1) {
                        $this->storeTmp['u_complexactive'] ++;
                    }
                }

                //total users count
                $this->storeTmp['u_totalusers'] ++;
            }

            //inactive complex users
            $this->storeTmp['u_complexinactive'] = $this->storeTmp['u_complextotal'] - $this->storeTmp['u_complexactive'];
        }


        //collecting monthly signups
        if (!empty($this->monthSignups)) {
            $cityTmp = array();
            foreach ($this->monthSignups as $io => $eachSignup) {
                if (!empty($eachSignup['login'])) {
                    if (isset($this->usersCities[$eachSignup['login']])) {
                        $userCity = $this->usersCities[$eachSignup['login']];
                        if (isset($cityTmp[$userCity])) {
                            $cityTmp[$userCity] ++;
                        } else {
                            $cityTmp[$userCity] = 1;
                        }
                    }
                }
                //count each signup
                $this->storeTmp['u_signups'] ++;
            }

            $this->storeTmp['u_citysignups'] = base64_encode(serialize($cityTmp));
        }
    }

    /**
     * Preprocess monthly signups data
     * 
     * @return void
     */
    protected function preprocessFinanceData() {
        $query = "SELECT * from `payments` WHERE `date` LIKE '" . $this->curmonth . "-%' AND `summ`>0";
        $allPayments = simple_queryall($query);
        if (!empty($allPayments)) {
            foreach ($allPayments as $io => $each) {
                //total money counting
                $this->storeTmp['f_totalmoney']+=round($each['summ'], 2);
                //total payments count increment
                $this->storeTmp['f_paymentscount'] ++;
            }

            //omg omg division by zero :)
            if ($this->storeTmp['f_paymentscount'] != 0) {
                //just ARPU
                $this->storeTmp['f_arpu'] = round($this->storeTmp['f_totalmoney'] / $this->storeTmp['f_paymentscount'], 2);

                //funny ARPAU!!!11111 - average revenue per active user
                if ($this->storeTmp['u_activeusers'] != 0) {
                    $this->storeTmp['f_arpau'] = round($this->storeTmp['f_totalmoney'] / $this->storeTmp['u_activeusers'], 2);
                }
            }
        }
    }

    /**
     * Do all UKV users/payments/signups preprocessing
     * 
     * @return void
     */
    protected function preprocessUkvData() {
        if ($this->ukvFlag) {
            //loading users
            $allUkvUsers = array();
            $queryUkvUsers = "SELECT * from `ukv_users`";
            $rawUkvUsers = simple_queryall($queryUkvUsers);
            if (!empty($rawUkvUsers)) {
                foreach ($rawUkvUsers as $io => $each) {
                    $allUkvUsers[$each['id']] = $each;
                }
            }

            //loading tariffs
            $allUkvTariffs = array();
            $ukvTariffPrices = array();
            $queryUkvTariffs = "SELECT * from `ukv_tariffs`";
            $rawUkvTariffs = simple_queryall($queryUkvTariffs);

            if (!empty($rawUkvTariffs)) {
                foreach ($rawUkvTariffs as $io => $each) {
                    $allUkvTariffs[$each['id']] = $each;
                    $ukvTariffPrices[$each['id']] = $each['price'];
                }
            }

            //loding monthly payments
            $allUkvPayments = array();
            $queryUkvPayments = "SELECT * from `ukv_payments` WHERE `date` LIKE '" . $this->curmonth . "-%' AND `summ`>0 AND `visible`=1;";
            $allUkvPayments = simple_queryall($queryUkvPayments);

            //counting monthly signups and other shit
            if (!empty($allUkvUsers)) {
                foreach ($allUkvUsers as $io => $eachUser) {
                    //total users count
                    $this->storeTmp['c_totalusers'] ++;

                    //total debt
                    if ($eachUser['cash'] < 0) {
                        $this->storeTmp['c_totaldebt']+=round($eachUser['cash'], 2);
                    }

                    //active users counting
                    if (isset($ukvTariffPrices[$eachUser['tariffid']])) {
                        $tariffPrice = $ukvTariffPrices[$eachUser['tariffid']];
                        $debtLimit = $this->ukvDebtLimit * $tariffPrice;
                        if (($eachUser['cash'] >= '-' . $debtLimit) AND ( $eachUser['active'] == 1)) {
                            $this->storeTmp['c_activeusers'] ++;
                        }
                    }

                    //illegal users count
                    if (!empty($this->ukvIllegal)) {
                        if ($eachUser['tariffid'] == $this->ukvIllegal) {
                            $this->storeTmp['c_illegal'] ++;
                        }
                    }

                    //complex users count
                    if (!empty($this->ukvComplex)) {
                        if ($this->complexFlag) {
                            if ($eachUser['tariffid'] == $this->ukvComplex) {
                                $this->storeTmp['c_complex'] ++;
                            }
                        }
                    }

                    //counting social users
                    if (!empty($this->ukvSocial)) {
                        if ($eachUser['tariffid'] == $this->ukvSocial) {
                            $this->storeTmp['c_social'] ++;
                        }
                    }


                    //current month ssignups
                    if (ispos($eachUser['regdate'], $this->curmonth . '-')) {
                        $this->storeTmp['c_signups'] ++;
                    }
                }

                //inactive users
                $this->storeTmp['c_inactiveusers'] = $this->storeTmp['c_totalusers'] - $this->storeTmp['c_activeusers'];
            }

            //preprocessing payments data
            if (!empty($allUkvPayments)) {
                foreach ($allUkvPayments as $io => $eachPayment) {
                    //total summ
                    $this->storeTmp['c_totalmoney']+=round($eachPayment['summ'], 2);
                    //payments count
                    $this->storeTmp['c_paymentscount'] ++;
                }

                //div by zero lol
                if ($this->storeTmp['c_paymentscount'] != 0) {
                    $this->storeTmp['c_arpu'] = round($this->storeTmp['c_totalmoney'] / $this->storeTmp['c_paymentscount'], 2);
                }

                if ($this->storeTmp['c_activeusers'] != 0) {
                    if (($this->complexFlag) AND ( $this->complexMasks)) {
                        $this->storeTmp['c_arpau'] = round($this->storeTmp['c_totalmoney'] / ($this->storeTmp['c_activeusers'] - $this->storeTmp['c_complex']), 2);
                    } else {
                        //no complex services enabled
                        $this->storeTmp['c_arpau'] = round($this->storeTmp['c_totalmoney'] / $this->storeTmp['c_activeusers'], 2);
                    }
                }
            }
        }
    }

    /**
     * Collects and stores equipment data
     * 
     * @return void
     */
    protected function preprocessEquipmentData() {
        //collecting switches
        $qeurySwitches = "SELECT COUNT(`id`) AS `count` from `switches` WHERE `desc` NOT LIKE '%NP%'";
        $switchesCount = simple_query($qeurySwitches);
        $this->storeTmp['e_switches'] = $switchesCount['count'];

        //collecting PON
        if ($this->ponFlag) {
            $queryOnu = "SELECT COUNT(`id`) AS `count` from `pononu`";
            $onuCount = simple_query($queryOnu);
            $this->storeTmp['e_pononu'] = $onuCount['count'];
        }

        //collecting docsis modems count
        if ($this->docsisFlag) {
            $queryModems = "SELECT COUNT(`id`) AS `count` from `modems`";
            $modemsCount = simple_query($queryModems);
            $this->storeTmp['e_docsis'] = $modemsCount['count'];
        }
    }

    /**
     * Askozia PBX data fetching and processing
     * 
     * @return void
     */
    protected function preprocessAskoziaData() {
        if ($this->askoziaFlag) {
            if ((!empty($this->askoziaUrl)) AND ( !empty($this->askoziaLogin)) AND ( !empty($this->askoziaPassword))) {
                $callsTmp = array();
                $normalCalls = array();
                $fields = array(
                    'extension_number' => 'all',
                    'cdr_filter' => 'incomingoutgoing',
                    'period_from' => $this->curmonth . '-01',
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
                            if ($each[16] != 'outbound') {
                                if (ispos($each[14], 'ANSWERED')) {
                                    $this->storeTmp['a_totalanswered'] ++;
                                }
                                $this->storeTmp['a_totalcalls'] ++;
                                //call duration in seconds increment
                                $this->storeTmp['a_totalcallsduration']+=$each[13];
                            }
                        }

                        //average calls duration
                        $this->storeTmp['a_averagecallduration'] = $this->storeTmp['a_totalcallsduration'] / $this->storeTmp['a_totalanswered'];
                    }
                }
            }
        }
    }

    /**
     * Saves current storeTmp into database
     * 
     * @return void
     */
    protected function saveHorseData() {
        $curTime = curdatetime();
        $query = "INSERT INTO `exhorse` (`id`, `date`, `u_totalusers`, `u_activeusers`, `u_inactiveusers`, `u_frozenusers`, `u_complextotal`, `u_complexactive`, `u_complexinactive`, `u_signups`, `u_citysignups`, `f_totalmoney`, `f_paymentscount`, `f_arpu`, `f_arpau`, `c_totalusers`, `c_activeusers`, `c_inactiveusers`, `c_illegal`, `c_complex`, `c_social`, `c_totalmoney`, `c_paymentscount`, `c_arpu`, `c_arpau`, `c_totaldebt`, `c_signups`, `a_totalcalls`, `a_totalanswered`, `a_totalcallsduration`, `a_averagecallduration`, `e_switches`, `e_pononu`, `e_docsis`) "
                . "VALUES (
             NULL,
              '" . $curTime . "',
               '" . $this->storeTmp['u_totalusers'] . "',
               '" . $this->storeTmp['u_activeusers'] . "',
               '" . $this->storeTmp['u_inactiveusers'] . "',
               '" . $this->storeTmp['u_frozenusers'] . "',
               '" . $this->storeTmp['u_complextotal'] . "',
               '" . $this->storeTmp['u_complexactive'] . "',
               '" . $this->storeTmp['u_complexinactive'] . "',
               '" . $this->storeTmp['u_signups'] . "',
               '" . $this->storeTmp['u_citysignups'] . "',
               '" . $this->storeTmp['f_totalmoney'] . "',
               '" . $this->storeTmp['f_paymentscount'] . "',
               '" . $this->storeTmp['f_arpu'] . "',
               '" . $this->storeTmp['f_arpau'] . "',
               '" . $this->storeTmp['c_totalusers'] . "',
               '" . $this->storeTmp['c_activeusers'] . "',
               '" . $this->storeTmp['c_inactiveusers'] . "',
               '" . $this->storeTmp['c_illegal'] . "',
               '" . $this->storeTmp['c_complex'] . "',
               '" . $this->storeTmp['c_social'] . "',
               '" . $this->storeTmp['c_totalmoney'] . "',
               '" . $this->storeTmp['c_paymentscount'] . "',
               '" . $this->storeTmp['c_arpu'] . "',
               '" . $this->storeTmp['c_arpau'] . "',
               '" . $this->storeTmp['c_totaldebt'] . "',
               '" . $this->storeTmp['c_signups'] . "',
               '" . $this->storeTmp['a_totalcalls'] . "',
               '" . $this->storeTmp['a_totalanswered'] . "',
               '" . $this->storeTmp['a_totalcallsduration'] . "',
               '" . $this->storeTmp['a_averagecallduration'] . "',
               '" . $this->storeTmp['e_switches'] . "',
               '" . $this->storeTmp['e_pononu'] . "',
               '" . $this->storeTmp['e_docsis'] . "');";
        nr_query($query);
    }

    /**
     * Do all data preprocessing and store results into database
     * 
     * @return void
     */
    public function runHorse() {
        $this->preprocessUserData();
        $this->preprocessFinanceData();
        $this->preprocessUkvData();
        $this->preprocessEquipmentData();
        $this->preprocessAskoziaData();
        $this->saveHorseData();

        if (self::DEBUG) {
            debarr($this->storeTmp);
        }
    }

    /**
     * Loads stats data from database
     * 
     * @return array
     */
    protected function loadStoredData() {
        $result = array();
        if (!empty($this->showYear)) {
            $query = "SELECT * from `exhorse` WHERE `date` LIKE '" . $this->showYear . "-%';";
            $all = simple_queryall($query);
            if (!empty($all)) {
                foreach ($all as $io => $each) {
                    $timestamp = strtotime($each['date']);
                    $month = date("m", $timestamp);
                    $result[$month] = $each;
                }
            }
        }
        return ($result);
    }

    /**
     * Counts percentage between two values
     * 
     * @param float $valueTotal
     * @param float $value
     * 
     * @return float
     */
    protected function percentValue($valueTotal, $value) {
        $result = 0;
        if ($valueTotal != 0) {
            $result = round((($value * 100) / $valueTotal), 2);
        }
        return ($result);
    }

    /**
     * Formats time from seconds to human readable string
     * 
     * @param int $seconds
     * 
     * @return string
     */
    protected function formatTime($seconds) {
        $init = $seconds;
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds / 60) % 60);
        $seconds = $seconds % 60;

        if ($init < 3600) {
            //less than 1 hour
            if ($init < 60) {
                //less than minute
                $result = $seconds . ' ' . __('sec.');
            } else {
                //more than one minute
                $result = $minutes . ' ' . __('minutes') . ' ' . $seconds . ' ' . __('seconds');
            }
        } else {
            //more than hour
            $result = $hours . ' ' . __('hour') . ' ' . $minutes . ' ' . __('minutes') . ' ' . $seconds . ' ' . __('seconds');
        }
        return ($result);
    }

    /**
     * Renders report for some year
     * 
     * @return string
     */
    public function renderReport() {
        $result = '';
        $months = months_array_localized();
        $yearData = $this->loadStoredData();
        $inputs = wf_YearSelectorPreset('yearsel', __('Year'), false, $this->showYear) . ' ';
        $chartsFlag = (wf_CheckPost(array('showcharts'))) ? true : false;
        $inputs.= wf_CheckInput('showcharts', __('Graphs'), false, $chartsFlag) . ' ';
        $inputs.= wf_Submit(__('Show'));
        $yearForm = wf_Form('', 'POST', $inputs, 'glamour');
        $yearForm.=wf_CleanDiv();
        $result.=$yearForm;

        //charts presets
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
        $usersChartData = array(0 => array(__('Month'), __('Total'), __('Active'), __('Inactive'), __('Frozen'), __('Signups'),));
        $complexChartData = array(0 => array(__('Month'), __('Total'), __('Active'), __('Inactive')));
        $financeChartsData = array(0 => array(__('Month'), __('Money'), __('Payments count'), __('ARPU'), __('ARPAU')));
        $ukvChartData = array(0 => array(__('Month'), __('Total'), __('Active'), __('Inactive'), __('Illegal'), __('Complex'), __('Social'), __('Signups')));
        $ukvfChartData = array(0 => array(__('Month'), __('Money'), __('Payments count'), __('ARPU'), __('ARPAU'), __('Debt')));
        $askoziaChartData = array(0 => array(__('Month'), __('Total calls'), __('Total answered'), __('No answer')));
        $equipChartData = array(0 => array(__('Month'), __('Switches'), __('PON ONU'), __('DOCSIS modems')));


        if (!empty($yearData)) {
            //internet users
            $cells = wf_TableCell(__('Month'));
            $cells.= wf_TableCell(__('Total'));
            $cells.= wf_TableCell(__('Active'));
            $cells.= wf_TableCell(__('Inactive'));
            $cells.= wf_TableCell(__('Frozen'));
            $cells.= wf_TableCell(__('Signups'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($yearData as $monthNum => $each) {
                $cells = wf_TableCell($months[$monthNum]);
                $cells.= wf_TableCell($each['u_totalusers']);
                $cells.= wf_TableCell($each['u_activeusers'] . ' (' . $this->percentValue($each['u_totalusers'], $each['u_activeusers']) . '%)');
                $cells.= wf_TableCell($each['u_inactiveusers'] . ' (' . $this->percentValue($each['u_totalusers'], $each['u_inactiveusers']) . '%)');
                $cells.= wf_TableCell($each['u_frozenusers'] . ' (' . $this->percentValue($each['u_totalusers'], $each['u_frozenusers']) . '%)');
                if (!empty($each['u_citysignups'])) {
                    $signupData = '';
                    $sigDataTmp = base64_decode($each['u_citysignups']);
                    $sigDataTmp = unserialize($sigDataTmp);
                    $citySigs = '';
                    $cityRows = '';
                    if (!empty($sigDataTmp)) {
                        $cityCells = wf_TableCell(__('City'));
                        $cityCells.= wf_TableCell(__('Signups'));
                        $cityRows.=wf_TableRow($cityCells, 'row1');
                        foreach ($sigDataTmp as $sigCity => $cityCount) {
                            $cityCells = wf_TableCell($sigCity);
                            $cityCells.= wf_TableCell($cityCount);
                            $cityRows.=wf_TableRow($cityCells, 'row3');
                        }
                        $citySigs.=wf_TableBody($cityRows, '100%', 0, '');
                    }
                    $signupData.=wf_modalAuto($each['u_signups'], __('Cities'), $citySigs);
                } else {
                    $signupData = $each['u_signups'];
                }

                $cells.= wf_TableCell($signupData);
                $rows.= wf_TableRow($cells, 'row3');
                //chart data
                $usersChartData[] = array($months[$monthNum], $each['u_totalusers'], $each['u_activeusers'], $each['u_inactiveusers'], $each['u_frozenusers'], $each['u_signups']);
            }


            $result.=wf_tag('h2') . __('Internets users') . wf_tag('h2', true);
            $result.=wf_TableBody($rows, '100%', 0, '');
            if ($chartsFlag) {
                $result.=wf_gchartsLine($usersChartData, __('Internets users'), '100%', '300px', $chartsOptions);
            }



            //complex data
            if ($this->complexFlag) {
                $result.=wf_tag('h2') . __('Complex services') . wf_tag('h2', true);
                $cells = wf_TableCell(__('Month'));
                $cells.= wf_TableCell(__('Total'));
                $cells.= wf_TableCell(__('Active'));
                $cells.= wf_TableCell(__('Inactive'));
                $rows = wf_TableRow($cells, 'row1');
                foreach ($yearData as $monthNum => $each) {
                    $cells = wf_TableCell($months[$monthNum]);
                    $cells.= wf_TableCell($each['u_complextotal']);
                    $cells.= wf_TableCell($each['u_complexactive'] . ' (' . $this->percentValue($each['u_complextotal'], $each['u_complexactive']) . '%)');
                    $cells.= wf_TableCell($each['u_complexinactive'] . ' (' . $this->percentValue($each['u_complextotal'], $each['u_complexinactive']) . '%)');
                    $rows.= wf_TableRow($cells, 'row3');
                    //chart data
                    $complexChartData[] = array($months[$monthNum], $each['u_complextotal'], $each['u_complexactive'], $each['u_complexinactive']);
                }
                $result.=wf_TableBody($rows, '100%', 0, '');
                if ($chartsFlag) {
                    $result.=wf_gchartsLine($complexChartData, __('Complex services'), '100%', '300px', $chartsOptions);
                }
            }


            //finance data
            $result.=wf_tag('h2') . __('Financial highlights') . wf_tag('h2', true);
            $cells = wf_TableCell(__('Month'));
            $cells.= wf_TableCell(__('Money'));
            $cells.= wf_TableCell(__('Payments count'));
            $cells.= wf_TableCell(__('ARPU'));
            $cells.= wf_TableCell(__('ARPAU'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($yearData as $monthNum => $each) {
                $cells = wf_TableCell($months[$monthNum]);
                $cells.= wf_TableCell($each['f_totalmoney']);
                $cells.= wf_TableCell($each['f_paymentscount']);
                $cells.= wf_TableCell($each['f_arpu']);
                $cells.= wf_TableCell($each['f_arpau']);
                $rows.= wf_TableRow($cells, 'row3');
                //chart data
                $financeChartsData[] = array($months[$monthNum], $each['f_totalmoney'], $each['f_paymentscount'], $each['f_arpu'], $each['f_arpau']);
            }
            $result.=wf_TableBody($rows, '100%', 0, '');
            if ($chartsFlag) {
                $result.=wf_gchartsLine($financeChartsData, __('Financial highlights'), '100%', '300px', $chartsOptions);
            }

            // UKV cable users
            if ($this->ukvFlag) {
                $result.=wf_tag('h2') . __('UKV users') . wf_tag('h2', true);
                $cells = wf_TableCell(__('Month'));
                $cells.= wf_TableCell(__('Total'));
                $cells.= wf_TableCell(__('Active'));
                $cells.= wf_TableCell(__('Inactive'));
                $cells.= wf_TableCell(__('Illegal'));
                if ($this->complexFlag) {
                    $cells.= wf_TableCell(__('Complex'));
                }
                $cells.= wf_TableCell(__('Social'));
                $cells.= wf_TableCell(__('Signups'));

                $rows = wf_TableRow($cells, 'row1');
                foreach ($yearData as $monthNum => $each) {
                    $cells = wf_TableCell($months[$monthNum]);
                    $cells.= wf_TableCell($each['c_totalusers']);
                    $cells.= wf_TableCell($each['c_activeusers'] . ' (' . $this->percentValue($each['c_totalusers'], $each['c_activeusers']) . '%)');
                    $cells.= wf_TableCell($each['c_inactiveusers'] . ' (' . $this->percentValue($each['c_totalusers'], $each['c_inactiveusers']) . '%)');
                    $cells.= wf_TableCell($each['c_illegal'] . ' (' . $this->percentValue($each['c_totalusers'], $each['c_illegal']) . '%)');
                    if ($this->complexFlag) {
                        $cells.= wf_TableCell($each['c_complex'] . ' (' . $this->percentValue($each['c_totalusers'], $each['c_complex']) . '%)');
                    }

                    $cells.= wf_TableCell($each['c_social'] . ' (' . $this->percentValue($each['c_totalusers'], $each['c_social']) . '%)');
                    $cells.= wf_TableCell($each['c_signups']);

                    $rows.= wf_TableRow($cells, 'row3');
                    //chart data
                    $ukvChartData[] = array($months[$monthNum], $each['c_totalusers'], $each['c_activeusers'], $each['c_inactiveusers'], $each['c_illegal'], $each['c_complex'], $each['c_social'], $each['c_signups']);
                }
                $result.=wf_TableBody($rows, '100%', 0, '');
                if ($chartsFlag) {
                    $result.=wf_gchartsLine($ukvChartData, __('UKV users'), '100%', '300px', $chartsOptions);
                }

                //UKV financial data
                $result.=wf_tag('h2') . __('UKV finance') . wf_tag('h2', true);
                $cells = wf_TableCell(__('Month'));
                $cells.= wf_TableCell(__('Money'));
                $cells.= wf_TableCell(__('Payments count'));
                $cells.= wf_TableCell(__('ARPU'));
                $cells.= wf_TableCell(__('ARPAU'));
                $cells.= wf_TableCell(__('Debt'));

                $rows = wf_TableRow($cells, 'row1');
                foreach ($yearData as $monthNum => $each) {
                    $cells = wf_TableCell($months[$monthNum]);
                    $cells.= wf_TableCell($each['c_totalmoney']);
                    $cells.= wf_TableCell($each['c_paymentscount']);
                    $cells.= wf_TableCell($each['c_arpu']);
                    $cells.= wf_TableCell($each['c_arpau']);
                    $cells.= wf_TableCell($each['c_totaldebt']);
                    $rows.= wf_TableRow($cells, 'row3');
                    //chart data
                    $ukvfChartData[] = array($months[$monthNum], $each['c_totalmoney'], $each['c_paymentscount'], $each['c_arpu'], $each['c_arpau'], $each['c_totaldebt']);
                }
                $result.=wf_TableBody($rows, '100%', 0, '');
                if ($chartsFlag) {
                    $result.=wf_gchartsLine($ukvfChartData, __('UKV users'), '100%', '300px', $chartsOptions);
                }
            }

            //Askozia PBX
            if ($this->askoziaFlag) {
                $result.=wf_tag('h2') . __('AskoziaPBX integration') . wf_tag('h2', true);
                $cells = wf_TableCell(__('Month'));
                $cells.= wf_TableCell(__('Incoming calls'));
                $cells.= wf_TableCell(__('Total answered'));
                $cells.= wf_TableCell(__('No answer'));
                $cells.= wf_TableCell(__('Total duration'));
                $cells.= wf_TableCell(__('Average duration'));
                $cells.= wf_TableCell(__('Answers percent'));

                $rows = wf_TableRow($cells, 'row1');
                foreach ($yearData as $monthNum => $each) {
                    $cells = wf_TableCell($months[$monthNum]);
                    $cells.= wf_TableCell($each['a_totalcalls']);
                    $cells.= wf_TableCell($each['a_totalanswered']);
                    $cells.= wf_TableCell($each['a_totalcalls'] - $each['a_totalanswered']);
                    $cells.= wf_TableCell($this->formatTime($each['a_totalcallsduration']));
                    $cells.= wf_TableCell($this->formatTime($each['a_averagecallduration']));
                    $cells.= wf_TableCell($this->percentValue($each['a_totalcalls'], $each['a_totalanswered']) . '%');
                    $rows.= wf_TableRow($cells, 'row3');
                    //chart data
                    $askoziaChartData[] = array($months[$monthNum], $each['a_totalcalls'], $each['a_totalanswered'], ($each['a_totalcalls'] - $each['a_totalanswered']));
                }
                $result.=wf_TableBody($rows, '100%', 0, '');
                if ($chartsFlag) {
                    $result.=wf_gchartsLine($askoziaChartData, __('Askozia'), '100%', '300px', $chartsOptions);
                }
            }

            //Equipment
            $result.=wf_tag('h2') . __('Equipment') . wf_tag('h2', true);
            $cells = wf_TableCell(__('Month'));
            $cells.= wf_TableCell(__('Switches'));
            if ($this->ponFlag) {
                $cells.= wf_TableCell(__('PON ONU'));
            }
            if ($this->docsisFlag) {
                $cells.= wf_TableCell(__('DOCSIS Modems'));
            }

            $rows = wf_TableRow($cells, 'row1');
            foreach ($yearData as $monthNum => $each) {
                $cells = wf_TableCell($months[$monthNum]);
                $cells.= wf_TableCell($each['e_switches']);
                if ($this->ponFlag) {
                    $cells.= wf_TableCell($each['e_pononu']);
                }
                if ($this->docsisFlag) {
                    $cells.= wf_TableCell($each['e_docsis']);
                }

                $rows.= wf_TableRow($cells, 'row3');
                //chart data
                $equipChartData[] = array($months[$monthNum], $each['e_switches'], $each['e_pononu'], $each['e_docsis']);
            }
            $result.=wf_TableBody($rows, '100%', 0, '');
            if ($chartsFlag) {
                $result.=wf_gchartsLine($equipChartData, __('Equipment'), '100%', '300px', $chartsOptions);
            }
        } else {
            $result.= $this->messages->getStyledMessage(__('Nothing found'), 'info');
        }
        return ($result);
    }

}
