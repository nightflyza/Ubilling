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
     * System messages helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

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
            $this->ukvIllegal = $this->altCfg['UKV_ILLEGEL_TARIFFID'];
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
     * Detects user activity state
     * 
     * @param array $userData
     * 
     * @return int 1 - active, 0 - inactive, -1 - frozen
     */
    protected function isActive($userData) {
        $result = '';
        if (($userData['Cash'] >= $userData['Credit']) AND ( $userData['AlwaysOnline'] == 1) AND ( $userData['Passive'] == 0)) {
            $result = 1;
        }
        if (($userData['Cash'] <= $userData['Credit']) AND ( $userData['AlwaysOnline'] == 1) AND ( $userData['Passive'] == 0)) {
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
                        if (($eachUser['cash'] > $debtLimit) AND ( $eachUser['active'] == 1)) {
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
                    $this->storeTmp['c_arpau'] = round($this->storeTmp['c_totalmoney'] / $this->storeTmp['c_activeusers'], 2);
                }
            }
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
                    'cdr_filter' => 'incoming',
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
                $rawResult = file_get_contents('exports/exhorseasktemp.dat');
                //uncomment following  - DEBUG
                // $rawResult = curl_exec($ch);
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
                        $this->storeTmp['a_totalcalls'] = sizeof($normalCalls);
                        foreach ($normalCalls as $io => $each) {
                            if (ispos($each[14], 'ANSWERED')) {
                                $this->storeTmp['a_totalanswered'] ++;
                            }

                            //call duration in seconds increment
                            $this->storeTmp['a_totalcallsduration']+=$each[13];
                        }

                        //average calls duration
                        $this->storeTmp['a_averagecallduration'] = $this->storeTmp['a_totalcallsduration'] / $this->storeTmp['a_totalanswered'];

                        // debarr($normalCalls);
                    }
                }
            }
        }
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
        $this->preprocessAskoziaData();
        debarr($this->storeTmp);
    }

}
