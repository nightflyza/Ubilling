<?php

/**
 * System-wide previous periods statistics archive aka Existential Horse
 */
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
     * @var array
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
     * Is any PBX integration enabled?
     *
     * @var bool
     */
    protected $pbxFlag = false;

    /**
     * Is TelePony enabled?
     *
     * @var bool
     */
    protected $teleponyFlag = false;


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
     * Castypes array with cash values
     *
     * @var array
     */
    protected $cashIds = array();

    /**
     * Horse-data database abstraction layer placeholder
     *
     * @var object
     */
    protected $horseDb = '';

    /**
     * Some predefined urls, routes etc..
     */
    const URL_ME = '?module=exhorse';
    const PROUTE_YEAR = 'yearsel';
    const COLOR_GOOD = '009f04';
    const COLOR_BAD = 'b50000';
    const ICON_RISE = 'skins/rise_icon.png';
    const ICON_DRAIN = 'skins/drain_icon.png';

    /**
     * Some database data sources here
     */
    const TABLE_HORSE = 'exhorse';
    const TABLE_SIGNUPS = 'userreg';
    const TABLE_PAYMENTS = 'payments';
    const TABLE_CATV_USERS = 'ukv_users';
    const TABLE_CATV_TARIFFS = 'ukv_tariffs';
    const TABLE_CATV_PAYMENTS = 'ukv_payments';
    const TABLE_SWITCHES = 'switches';
    const TABLE_SWDEAD = 'switchdeadlog';
    const TABLE_ONU = 'pononu';
    const TABLE_DOCSIS = 'modems';
    const TABLE_WDYC = 'wdycinfo';
    const TABLE_SIGREQ = 'sigreq';
    const TABLE_CAPABS = 'capab';

//                   /\,%,_
//                   \%%%/,\
//                 _.-"%%|//%
//               .'  .-"  /%%%
//           _.-'_.-" 0)   \%%%
//          /.\.'           \%%%
//          \ /      _,      %%%
//           `"---"~`\   _,*'\%%'   _,--""""-,%%,
//                    )*^     `""~~`          \%%%,
//                  _/                         \%%%
//              _.-`/                           |%%,___
//          _.-"   /      ,           ,        ,|%%   .`\
//         /\     /      /             `\       \%'   \ /
//         \ \ _,/      /`~-._         _,`\      \`""~~`
//          `"` /-.,_ /'      `~"----"~    `\     \
//              \___,'                       \.-"`/
//                                            `--'

    /**
     * Creates new existential horse instance
     * 
     * @return void
     */
    public function __construct() {
        $this->loadConfig();
        $this->initTmp();
        $this->initDb();
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
     * Inits database abstraction layer
     * 
     * @return void
     */
    protected function initDb() {
        $this->horseDb = new NyanORM(self::TABLE_HORSE);
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

        
        //Asterisk integration (?)
        if ($this->altCfg['ASTERISK_ENABLED']) {
            $this->pbxFlag = true;
        }

        //TelePony integration
        if ($this->altCfg['TELEPONY_ENABLED']) {
            $this->pbxFlag = true;
            $this->teleponyFlag = true;
        }

        //PONizer enabled?
        if ($this->altCfg['PON_ENABLED']) {
            $this->ponFlag = true;
        }

        //is DOCSIS support enabled?
        if ($this->altCfg['DOCSIS_SUPPORT']) {
            $this->docsisFlag = true;
        }

        //custom cashtypeids for cash stats 
        $this->cashIds = array(1 => 1); // default cash cashtypeid
        if (isset($this->altCfg['EXHORSE_CASHIDS'])) {
            if (!empty($this->altCfg['EXHORSE_CASHIDS'])) {
                $rawCashIds = explode(',', $this->altCfg['EXHORSE_CASHIDS']);
                if (!empty($rawCashIds)) {
                    $rawCashIds = array_flip($rawCashIds);
                    $this->cashIds = $rawCashIds;
                }
            }
        }
    }

    /**
     * Sets year to render results
     * 
     * @param string $month
     * 
     * @return void
     */
    public function setYear($year) {
        $this->showYear = ubRouting::filters($year, 'int');
    }

    /**
     * Inits empty temporary array with default struct.
     * All keys of this struct will be mapped as-is to database record.
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
        $this->storeTmp['f_cashmoney'] = 0;
        $this->storeTmp['f_cashcount'] = 0;
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
        $this->storeTmp['a_recallunsuccess'] = 0;
        $this->storeTmp['a_recalltrytime'] = 0;
        $this->storeTmp['e_deadswintervals'] = 0;
        $this->storeTmp['t_sigreq'] = 0;
        $this->storeTmp['t_tickets'] = 0;
        $this->storeTmp['t_tasks'] = 0;
        $this->storeTmp['t_capabtotal'] = 0;
        $this->storeTmp['t_capabundone'] = 0;
        $this->storeTmp['a_outtotalcalls'] = 0;
        $this->storeTmp['a_outtotalanswered'] = 0;
        $this->storeTmp['a_outtotalcallsduration'] = 0;
        $this->storeTmp['a_outaveragecallduration'] = 0;
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
        $signupsDb = new NyanORM(self::TABLE_SIGNUPS);
        $signupsDb->where('date', 'LIKE', $this->curmonth . "-%");
        $this->monthSignups = $signupsDb->getAll();
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
                    $this->storeTmp['u_activeusers']++;
                }
                //inactive users
                if ($this->isActive($eachUser) == 0) {
                    $this->storeTmp['u_inactiveusers']++;
                }
                //just frozen bodies
                if ($this->isActive($eachUser) == -1) {
                    $this->storeTmp['u_frozenusers']++;
                }

                //complex users detection
                if ($this->isComplex($eachUser)) {
                    $this->storeTmp['u_complextotal']++;
                    //active complex users
                    if ($this->isActive($eachUser) == 1) {
                        $this->storeTmp['u_complexactive']++;
                    }
                }

                //total users count
                $this->storeTmp['u_totalusers']++;
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
                            $cityTmp[$userCity]++;
                        } else {
                            $cityTmp[$userCity] = 1;
                        }
                    }
                }
                //count each signup
                $this->storeTmp['u_signups']++;
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
        $paymentsDb = new NyanORM(self::TABLE_PAYMENTS);
        $paymentsDb->where('date', 'LIKE', $this->curmonth . '-%');
        $paymentsDb->where('summ', '>', '0');
        $allPayments = $paymentsDb->getAll();

        if (!empty($allPayments)) {
            foreach ($allPayments as $io => $each) {
                //total money counting
                $this->storeTmp['f_totalmoney'] += round($each['summ'], 2);
                //total payments count increment
                $this->storeTmp['f_paymentscount']++;

                //cash money processing
                if (($each['summ'] >= 0) AND ( isset($this->cashIds[$each['cashtypeid']]))) {
                    $this->storeTmp['f_cashmoney'] += round($each['summ'], 2);
                    $this->storeTmp['f_cashcount']++;
                }
            }

            //omg omg omg division by zero :)
            if ($this->storeTmp['f_paymentscount'] != 0) {
                //just ARPU - average revenue per user
                $this->storeTmp['f_arpu'] = round($this->storeTmp['f_totalmoney'] / $this->storeTmp['f_paymentscount'], 2);

                //ARPAU - average revenue per active user
                if ($this->storeTmp['u_activeusers'] != 0) {
                    $this->storeTmp['f_arpau'] = round($this->storeTmp['f_totalmoney'] / $this->storeTmp['u_activeusers'], 2);
                }
            }
        }
    }

    /**
     * Performs all UKV users/payments/signups preprocessing
     * 
     * @return void
     */
    protected function preprocessUkvData() {
        if ($this->ukvFlag) {
            //loading users
            $ukvUsersDb = new NyanORM(self::TABLE_CATV_USERS);
            $allUkvUsers = $ukvUsersDb->getAll('id');
            if (empty($allUkvUsers)) {
                $allUkvUsers = array();
            }

            //loading tariffs
            $allUkvTariffs = array();
            $ukvTariffPrices = array();
            $ukvTariffsDb = new NyanORM(self::TABLE_CATV_TARIFFS);
            $allUkvTariffs = $ukvTariffsDb->getAll('id');

            if (!empty($allUkvTariffs)) {
                foreach ($allUkvTariffs as $io => $each) {
                    $ukvTariffPrices[$each['id']] = $each['price'];
                }
            }

            //loding monthly payments
            $allUkvPayments = array();

            $ukvPaymentsDb = new NyanORM(self::TABLE_CATV_PAYMENTS);
            $ukvPaymentsDb->where('date', 'LIKE', $this->curmonth . '-%');
            $ukvPaymentsDb->where('summ', '>', '0');
            $ukvPaymentsDb->where('visible', '=', '1');
            $allUkvPayments = $ukvPaymentsDb->getAll();

            //counting monthly signups and other shit
            if (!empty($allUkvUsers)) {
                foreach ($allUkvUsers as $io => $eachUser) {
                    //total users count
                    $this->storeTmp['c_totalusers']++;

                    //total debt
                    if ($eachUser['cash'] < 0) {
                        $this->storeTmp['c_totaldebt'] += round($eachUser['cash'], 2);
                    }

                    //active users counting
                    if (isset($ukvTariffPrices[$eachUser['tariffid']])) {
                        $tariffPrice = $ukvTariffPrices[$eachUser['tariffid']];
                        $debtLimit = $this->ukvDebtLimit * $tariffPrice;
                        if (($eachUser['cash'] >= '-' . $debtLimit) AND ( $eachUser['active'] == 1)) {
                            $this->storeTmp['c_activeusers']++;
                        }
                    }

                    //illegal users count
                    if (!empty($this->ukvIllegal)) {
                        if ($eachUser['tariffid'] == $this->ukvIllegal) {
                            $this->storeTmp['c_illegal']++;
                        }
                    }

                    //complex users count
                    if (!empty($this->ukvComplex)) {
                        if ($this->complexFlag) {
                            if ($eachUser['tariffid'] == $this->ukvComplex) {
                                $this->storeTmp['c_complex']++;
                            }
                        }
                    }

                    //counting social users
                    if (!empty($this->ukvSocial)) {
                        if ($eachUser['tariffid'] == $this->ukvSocial) {
                            $this->storeTmp['c_social']++;
                        }
                    }


                    //current month ssignups
                    if (ispos($eachUser['regdate'], $this->curmonth . '-')) {
                        $this->storeTmp['c_signups']++;
                    }
                }

                //inactive users
                $this->storeTmp['c_inactiveusers'] = $this->storeTmp['c_totalusers'] - $this->storeTmp['c_activeusers'];
            }

            //preprocessing payments data
            if (!empty($allUkvPayments)) {
                foreach ($allUkvPayments as $io => $eachPayment) {
                    //total summ
                    $this->storeTmp['c_totalmoney'] += round($eachPayment['summ'], 2);
                    //payments count
                    $this->storeTmp['c_paymentscount']++;
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
        //collecting switches count
        $switchesDb = new NyanORM(self::TABLE_SWITCHES);
        $switchesDb->where('desc', 'NOT LIKE', '%NP%');
        $switchesCount = $switchesDb->getFieldsCount();
        $this->storeTmp['e_switches'] = $switchesCount;

        //collecting dead switches intervals count
        $deadSwitchesCount = 0;

        $swDeadDb = new NyanORM(self::TABLE_SWDEAD);
        $swDeadDb->where('date', 'LIKE', $this->curmonth . '-%');
        $allDead = $swDeadDb->getAll();

        if (!empty($allDead)) {
            foreach ($allDead as $io => $each) {
                if (!empty($each['swdead'])) {
                    $deadTmp = unserialize($each['swdead']);
                    $deadSwitchesCount = $deadSwitchesCount + sizeof($deadTmp);
                }
            }
        }
        $this->storeTmp['e_deadswintervals'] = $deadSwitchesCount;

        //collecting PON ONU count
        if ($this->ponFlag) {
            $onuDb = new NyanORM(self::TABLE_ONU);
            $onuCount = $onuDb->getFieldsCount();

            $this->storeTmp['e_pononu'] = $onuCount;
        }

        //collecting docsis modems count
        if ($this->docsisFlag) {
            $modemsDb = new NyanORM(self::TABLE_DOCSIS);
            $modemsCount = $modemsDb->getFieldsCount();
            $this->storeTmp['e_docsis'] = $modemsCount;
        }
    }

    /**
     * Telepony CDR data fetching and processing
     * 
     * @return void
     */
    protected function preprocessTeleponyData() {
        if ($this->teleponyFlag) {
            $telepony = new TelePony();
            if ($this->altCfg['TELEPONY_CDR']) {
                $teleponyData = $telepony->getHorseMonthData();
                //incoming calls
                $this->storeTmp['a_totalanswered'] = $teleponyData['a_totalanswered'];
                $this->storeTmp['a_totalcalls'] = $teleponyData['a_totalcalls'];
                $this->storeTmp['a_totalcallsduration'] = $teleponyData['a_totalcallsduration'];
                $this->storeTmp['a_averagecallduration'] = $teleponyData['a_averagecallduration'];
                //outgoing calls
                $this->storeTmp['a_outtotalanswered'] = $teleponyData['a_outtotalanswered'];
                $this->storeTmp['a_outtotalcalls'] = $teleponyData['a_outtotalcalls'];
                $this->storeTmp['a_outtotalcallsduration'] = $teleponyData['a_outtotalcallsduration'];
                $this->storeTmp['a_outaveragecallduration'] = $teleponyData['a_outaveragecallduration'];
            }
        }
    }

    /**
     * WDYC data loading and preprocessing
     * 
     * @return void
     */
    protected function preprocessWdycData() {
        if ($this->altCfg['WDYC_ENABLED']) {
            $totalMissed = 0;
            $totalRecalls = 0;
            $totalUnsucc = 0;
            $totalCalls = 0;
            $totalReactTime = 0;

            $wdycDb = new NyanORM(self::TABLE_WDYC);
            $wdycDb->where('date', 'LIKE', $this->curmonth . '-%');
            $allWdycStat = $wdycDb->getAll();

            if (!empty($allWdycStat)) {
                foreach ($allWdycStat as $io => $each) {
                    $totalMissed += $each['missedcount'];
                    $totalRecalls += $each['recallscount'];
                    $totalUnsucc += $each['unsucccount'];
                    $totalReactTime += $each['totaltrytime'];
                }
            }
            $totalCalls = $totalRecalls + $totalMissed;

            $this->storeTmp['a_recallunsuccess'] = zb_PercentValue($totalCalls, $totalMissed);
            $recallCallsTotals = $totalRecalls + $totalUnsucc;
            if ($recallCallsTotals != 0) {
                $this->storeTmp['a_recalltrytime'] = round(($totalReactTime / $recallCallsTotals));
            } else {
                $this->storeTmp['a_recalltrytime'] = 0;
            }
        }
    }

    /**
     * Preprocessing tickets, sigreqs, capabs and other single data inputs
     * 
     * @return void
     */
    protected function preprocessMisc() {
        //signup requests count per month
        if ($this->altCfg['SIGREQ_ENABLED']) {
            $sigReqDb = new NyanORM(self::TABLE_SIGREQ);
            $sigReqDb->where('date', 'LIKE', $this->curmonth . '-%');
            $sigreqCount = $sigReqDb->getFieldsCount();
            $this->storeTmp['t_sigreq'] = $sigreqCount;
        }

        //tickets per month count
        $ticketsTmp = zb_AnalyticsTicketingGetCountYear($this->curmonth);
        $this->storeTmp['t_tickets'] = $ticketsTmp[date("m")];

        //tasks in taskmanager for current month
        $taskTmp = zb_AnalyticsTaskmanGetCountYear(curyear());
        $this->storeTmp['t_tasks'] = $taskTmp[date("m")];

        //capabdir stats
        if ($this->altCfg['CAPABDIR_ENABLED']) {
            $capabUndone = 0;
            $capabTotal = 0;

            $capabDb = new NyanORM(self::TABLE_CAPABS);
            $capabDb->where('date', 'LIKE', $this->curmonth . '-%');
            $capabTmp = $capabDb->getAll();

            if (!empty($capabTmp)) {
                $capabTotal = sizeof($capabTmp);
                foreach ($capabTmp as $io => $each) {
                    if ($each['stateid'] == 0) {
                        $capabUndone++;
                    }
                }
            }

            $this->storeTmp['t_capabtotal'] = $capabTotal;
            $this->storeTmp['t_capabundone'] = $capabUndone;
        }
    }

    /**
     * Saves current storeTmp into database
     * 
     * @return void
     */
    protected function saveHorseData() {
        $curTime = curdatetime();

        $this->horseDb->data('date', $curTime);
        if (!empty($this->storeTmp)) {
            foreach ($this->storeTmp as $eachField => $eachValue) {
                $this->horseDb->data($eachField, $eachValue);
            }
        }

        $this->horseDb->create();
        log_register('EXHORSE SAVE DATA');
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
        $this->preprocessTeleponyData();
        $this->preprocessWdycData();
        $this->preprocessMisc();
        $this->saveHorseData();
        $this->cleanupDb();
    }

    /**
     * Loads stats data from database
     * 
     * @param bool $allTime - load stored data for all time, ignoring this->showYear
     * 
     * @return array
     */
    protected function loadStoredData($allTime = false) {
        $result = array();
        if (!empty($this->showYear)) {
            //from oldest to newest
            $this->horseDb->orderBy('id', 'ASC');
            //setting date filter if not all of data is required
            if (!$allTime) {
                $this->horseDb->where('date', 'LIKE', $this->showYear . '-%');
            }
            $all = $this->horseDb->getAll();

            if (!empty($all)) {
                foreach ($all as $io => $each) {
                    $timestamp = strtotime($each['date']);
                    $year = date("Y", $timestamp);
                    $month = date("m", $timestamp);
                    $result[$year][$month] = $each;
                }
            }
        }
        return ($result);
    }

    /**
     * Cleans previous days data if current month day is the last 
     * or all previous records for this this month.. i hope.
     * 
     * @return void
     */
    public function cleanupDb() {
        //In normal cases - cleanup previous data on last day of month
        if (!ubRouting::checkGet('ebobo')) {
            //now
            $curDay = date("d");
            //last day of month?
            if ($curDay == date("t")) {
                $curMonth = date("Y-m");
                $this->horseDb->where('date', 'LIKE', $curMonth . '-%');
                $this->horseDb->where('date', 'NOT LIKE', $curMonth . '-' . $curDay . '%');
                $this->horseDb->delete();
                log_register('EXHORSE CLEANUP MONTH `' . $curMonth . '`');
            }
        } else {
            //Pautina mode for some reason
            $query = 'DELETE `ex` FROM `' . self::TABLE_HORSE . '` AS `ex` LEFT JOIN (SELECT MAX(date) AS mDate FROM `' . self::TABLE_HORSE . '` GROUP BY DATE_FORMAT(date,"%Y-%m") ) AS `tmp` ON (`tmp`.`mDate`=`ex`.`date`) WHERE `tmp`.`mDate` IS NULL';
            nr_query($query);
        }
    }

    /**
     * Renders report for some year
     * 
     * @return string
     */
    public function renderReport() {
        $result = '';
        $months = months_array_localized();
        $inputs = wf_YearSelectorPreset('yearsel', __('Year'), false, $this->showYear, true) . ' ';
        $chartsFlag = (wf_CheckPost(array('showcharts'))) ? true : false;
        $allTimeFlag = ($this->showYear == '1488') ? true : false; // dont ask me why
        $inputs .= wf_CheckInput('showcharts', __('Graphs'), false, $chartsFlag) . ' ';
        $inputs .= wf_Submit(__('Show'));
        $yearForm = wf_Form('', 'POST', $inputs, 'glamour');
        $yearForm .= wf_CleanDiv();
        $result .= $yearForm;
        $riseOfTheNorthStar = array(); //userbase rise and drain stats
        $riseOfTheNorthStar['total'] = 0;
        $riseOfTheNorthStar['active'] = 0;
        $riseOfTheNorthStar['signups'] = 0;
        $totalSignups = 0;

        //first year month hack
        if (!$allTimeFlag) {
            $horseBase = new NyanORM(self::TABLE_HORSE);
            $previousDecember = (($this->showYear - 1) . '-12-%'); // december of previous year
            $horseBase->where('date', 'LIKE', $previousDecember);
            $horseBase->selectable(array('u_totalusers', 'u_activeusers', 'u_signups'));
            $prevYearStats = $horseBase->getAll();
            if (!empty($prevYearStats)) {
                $riseOfTheNorthStar['total'] = $prevYearStats[0]['u_totalusers'];
                $riseOfTheNorthStar['active'] = $prevYearStats[0]['u_activeusers'];
                $riseOfTheNorthStar['signups'] = $prevYearStats[0]['u_signups'];
            }
        }
        //data loading
        $yearData = $this->loadStoredData($allTimeFlag);

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

        $usersChartData = array(0 => array(__('Month'), __('Total'), __('Active'), __('Inactive'), __('Frozen')));
        $usersSignupsChartData = array(0 => array(__('Month'), __('Signups')));
        $complexChartData = array(0 => array(__('Month'), __('Total'), __('Active'), __('Inactive')));
        $financeChartsData = array(0 => array(__('Month'), __('Money'), __('Payments count')));
        $arpuChartsData = array(0 => array(__('Month'), __('ARPU'), __('ARPAU')));
        $ukvChartData = array(0 => array(__('Month'), __('Total'), __('Active'), __('Inactive'), __('Illegal'), __('Complex'), __('Social'), __('Signups')));
        $ukvfChartData = array(0 => array(__('Month'), __('Money'), __('Payments count'), __('Debt')));
        $ukvarpuChartData = array(0 => array(__('Month'), __('ARPU'), __('ARPAU')));
        $universeChartData = array(0 => array(__('Month'), __('Signup requests'), __('Tickets'), __('Tasks'), __('Signup capabilities'), __('Undone')));
        $telephonyChartData = array(0 => array(__('Month'), __('Total calls'), __('Total answered'), __('No answer')));
        $equipChartData = array(0 => array(__('Month'), __('Switches')));
        $citySignupsTmp = array();

        if ($this->ponFlag AND $this->docsisFlag) {
            $equipChartData = array(0 => array(__('Month'), __('Switches'), __('PON ONU'), __('DOCSIS modems')));
        }

        if (!$this->docsisFlag AND $this->ponFlag) {
            $equipChartData = array(0 => array(__('Month'), __('Switches'), __('PON ONU')));
        }
        if ($this->docsisFlag AND !$this->ponFlag) {
            $equipChartData = array(0 => array(__('Month'), __('Switches'), __('DOCSIS modems')));
        }


        if (!empty($yearData)) {
            //internet users
            $cells = wf_TableCell(__('Month'));
            $cells .= wf_TableCell(__('Total'));
            $cells .= wf_TableCell(__('Movement') . ' (' . __('Clean') . '/' . __('Dirty') . ')');
            $cells .= wf_TableCell(__('Active'));
            $cells .= wf_TableCell(__('Inactive'));
            $cells .= wf_TableCell(__('Frozen'));
            $cells .= wf_TableCell(__('Signups'));
            $rows = wf_TableRow($cells, 'row1');

            foreach ($yearData as $yearNum => $monthArr) {
                foreach ($monthArr as $monthNum => $each) {
                    $yearDisplay = ($allTimeFlag) ? $yearNum . ' ' : '';
                    $cells = wf_TableCell($yearDisplay . $months[$monthNum]);
                    $fontEnd = wf_tag('font', true);

                    if (!empty($riseOfTheNorthStar['active'])) {
                        $starDelimiter = ' / ';
                        $riseTotal = $each['u_activeusers'] - $riseOfTheNorthStar['active'];
                        if ($riseTotal > 0) {
                            $fontColor = wf_tag('font', false, '', 'color="#' . self::COLOR_GOOD . '"');
                            $riseUsersIcon = wf_img_sized(self::ICON_RISE, __('Increased'), '10', '10');
                        } else {
                            $fontColor = wf_tag('font', false, '', 'color="#' . self::COLOR_BAD . '"');
                            $riseUsersIcon = wf_img_sized(self::ICON_DRAIN, __('Decreased'), '10', '10');
                        }
                    } else {
                        $fontColor = '';
                        $riseUsersIcon = '';
                        $riseTotal = '';
                        $fontEnd = '';
                        $starDelimiter = '';
                    }


                    if (!empty($riseOfTheNorthStar['active'])) {
                        $riseActive = ($each['u_activeusers'] - ($riseOfTheNorthStar['active'] + $each['u_signups']));
                        if ($riseActive > 0) {
                            $fontColorActive = wf_tag('font', false, '', 'color="#' . self::COLOR_GOOD . '"');
                            $riseActiveIcon = wf_img_sized(self::ICON_RISE, __('Increased'), '10', '10');
                        } else {
                            $fontColorActive = wf_tag('font', false, '', 'color="#' . self::COLOR_BAD . '"');
                            $riseActiveIcon = wf_img_sized(self::ICON_DRAIN, __('Decreased'), '10', '10');
                        }
                    } else {
                        $fontColorActive = '';
                        $riseActiveIcon = '';
                        $riseActive = '';
                        $fontEnd = '';
                    }

                    $cells .= wf_TableCell($each['u_totalusers']);
                    $cells .= wf_TableCell($riseUsersIcon . ' ' . $fontColor . $riseTotal . $fontEnd . $starDelimiter . $riseActiveIcon . ' ' . $fontColorActive . $riseActive . $fontEnd);
                    $cells .= wf_TableCell($each['u_activeusers'] . ' (' . zb_PercentValue($each['u_totalusers'], $each['u_activeusers']) . '%)');
                    $cells .= wf_TableCell($each['u_inactiveusers'] . ' (' . zb_PercentValue($each['u_totalusers'], $each['u_inactiveusers']) . '%)');
                    $cells .= wf_TableCell($each['u_frozenusers'] . ' (' . zb_PercentValue($each['u_totalusers'], $each['u_frozenusers']) . '%)');
                    if (!empty($each['u_citysignups'])) {
                        $signupData = '';
                        $sigDataTmp = base64_decode($each['u_citysignups']);
                        $sigDataTmp = unserialize($sigDataTmp);
                        $citySigs = '';
                        $cityRows = '';
                        if (!empty($sigDataTmp)) {
                            $cityCells = wf_TableCell(__('City'));
                            $cityCells .= wf_TableCell(__('Signups'));
                            $cityRows .= wf_TableRow($cityCells, 'row1');
                            foreach ($sigDataTmp as $sigCity => $cityCount) {
                                $cityCells = wf_TableCell($sigCity);
                                $cityCells .= wf_TableCell($cityCount);
                                $cityRows .= wf_TableRow($cityCells, 'row5');
                                $citySignupsTmp[$sigCity][$yearDisplay . $months[$monthNum]] = $cityCount;
                            }
                            $containerStyle = 'max-height:500px; min-width:400px;';
                            $citySigs .= wf_AjaxContainer('ctsigs', 'style="' . $containerStyle . '"', wf_TableBody($cityRows, '100%', 0, 'sortable'));
                        }
                        $signupData .= wf_modalAuto($each['u_signups'], __('Cities'), $citySigs);
                    } else {
                        $signupData = $each['u_signups'];
                    }

                    $totalSignups += $each['u_signups']; //just signups counter for selected period

                    $cells .= wf_TableCell($signupData);
                    $rows .= wf_TableRow($cells, 'row3');
                    //chart data
                    $yearDisplay = ($monthNum == '01') ? $yearDisplay : '';
                    $usersChartData[] = array($yearDisplay . $months[$monthNum], $each['u_totalusers'], $each['u_activeusers'], $each['u_inactiveusers'], $each['u_frozenusers']);
                    $usersSignupsChartData[] = array($yearDisplay . $months[$monthNum], $each['u_signups']);
                    //rise and drain stats
                    $riseOfTheNorthStar['total'] = $each['u_totalusers'];
                    $riseOfTheNorthStar['active'] = $each['u_activeusers'];
                    $riseOfTheNorthStar['signups'] = $each['u_signups'];
                }
            }

            $result .= wf_tag('h2') . __('Internets users') . wf_tag('h2', true);
            $result .= wf_TableBody($rows, '100%', 0, '') . ' ';
            $result .= __('Total users registered') . ': ' . $totalSignups;

            if ($chartsFlag) {
                $result .= wf_gchartsLine($usersChartData, __('Internets users'), '100%', '300px', $chartsOptions);
                $result .= wf_gchartsLine($usersSignupsChartData, __('Signups'), '100%', '300px', $chartsOptions);
                if (!empty($citySignupsTmp)) {
                    $allSignupCities = array_keys($citySignupsTmp);
                    $citySignupsChartData[0] = $allSignupCities;
                    array_unshift($citySignupsChartData[0], __('Month'));
                    $csCount = 0;
                    foreach ($months as $csMonth => $csMonthName) {
                        $csCount++;
                        $monthLabel = $yearDisplay . $csMonthName;
                        $citySignupsChartData[$csCount] = array($monthLabel);
                        foreach ($citySignupsTmp as $eachCsCity => $csData) {
                            if (isset($citySignupsTmp[$eachCsCity][$monthLabel])) {
                                $emCount = $citySignupsTmp[$eachCsCity][$monthLabel];
                            } else {
                                $emCount = 0;
                            }
                            $citySignupsChartData[$csCount][] += $emCount;
                        }
                    }
                    $result .= wf_gchartsLine($citySignupsChartData, __('Cities'), '100%', '300px', $chartsOptions);
                }
            }




            //complex data
            if ($this->complexFlag) {
                $result .= wf_tag('h2') . __('Complex services') . wf_tag('h2', true);
                $cells = wf_TableCell(__('Month'));
                $cells .= wf_TableCell(__('Total'));
                $cells .= wf_TableCell(__('Active'));
                $cells .= wf_TableCell(__('Inactive'));
                $rows = wf_TableRow($cells, 'row1');
                foreach ($yearData as $yearNum => $monthArr) {
                    foreach ($monthArr as $monthNum => $each) {
                        $yearDisplay = ($allTimeFlag) ? $yearNum . ' ' : '';

                        $cells = wf_TableCell($yearDisplay . $months[$monthNum]);
                        $cells .= wf_TableCell($each['u_complextotal']);
                        $cells .= wf_TableCell($each['u_complexactive'] . ' (' . zb_PercentValue($each['u_complextotal'], $each['u_complexactive']) . '%)');
                        $cells .= wf_TableCell($each['u_complexinactive'] . ' (' . zb_PercentValue($each['u_complextotal'], $each['u_complexinactive']) . '%)');
                        $rows .= wf_TableRow($cells, 'row3');
                        //chart data
                        $yearDisplay = ($monthNum == '01') ? $yearDisplay : '';
                        $complexChartData[] = array($yearDisplay . $months[$monthNum], $each['u_complextotal'], $each['u_complexactive'], $each['u_complexinactive']);
                    }
                }
                $result .= wf_TableBody($rows, '100%', 0, '');
                if ($chartsFlag) {
                    $result .= wf_gchartsLine($complexChartData, __('Complex services'), '100%', '300px', $chartsOptions);
                }
            }

            if (cfr('REPORTFINANCE')) {
                //finance data
                $result .= wf_tag('h2') . __('Financial highlights') . wf_tag('h2', true);
                $cells = wf_TableCell(__('Month'));
                $cells .= wf_TableCell(__('Money'));
                $cells .= wf_TableCell(__('Payments count'));
                $cells .= wf_TableCell(__('Cash payments'));
                $cells .= wf_TableCell(__('Cash payments count'));
                $cells .= wf_TableCell(__('ARPU'));
                $cells .= wf_TableCell(__('ARPAU'));
                $rows = wf_TableRow($cells, 'row1');
                foreach ($yearData as $yearNum => $monthArr) {
                    foreach ($monthArr as $monthNum => $each) {
                        $yearDisplay = ($allTimeFlag) ? $yearNum . ' ' : '';
                        $cells = wf_TableCell($yearDisplay . $months[$monthNum]);
                        $cells .= wf_TableCell(zb_CashBigValueFormat($each['f_totalmoney']));
                        $cells .= wf_TableCell($each['f_paymentscount']);
                        $cells .= wf_TableCell($each['f_cashmoney'] . ' (' . zb_PercentValue($each['f_totalmoney'], $each['f_cashmoney']) . '%)');
                        $cells .= wf_TableCell($each['f_cashcount'] . ' (' . zb_PercentValue($each['f_paymentscount'], $each['f_cashcount']) . '%)');
                        $cells .= wf_TableCell($each['f_arpu']);
                        $cells .= wf_TableCell($each['f_arpau']);
                        $rows .= wf_TableRow($cells, 'row3');
                        //chart data
                        $yearDisplay = ($monthNum == '01') ? $yearDisplay : '';
                        $financeChartsData[] = array($yearDisplay . $months[$monthNum], $each['f_totalmoney'], $each['f_paymentscount']);
                        $arpuChartsData[] = array($yearDisplay . $months[$monthNum], $each['f_arpu'], $each['f_arpau']);
                    }
                }
                $result .= wf_TableBody($rows, '100%', 0, '');
                if ($chartsFlag) {
                    $result .= wf_gchartsLine($financeChartsData, __('Financial highlights'), '100%', '300px', $chartsOptions);
                    $result .= wf_gchartsLine($arpuChartsData, __('ARPU'), '100%', '300px', $chartsOptions);
                }
            }

            // UKV cable users
            if ($this->ukvFlag) {
                $result .= wf_tag('h2') . __('UKV users') . wf_tag('h2', true);
                $cells = wf_TableCell(__('Month'));
                $cells .= wf_TableCell(__('Total'));
                $cells .= wf_TableCell(__('Active'));
                $cells .= wf_TableCell(__('Inactive'));
                $cells .= wf_TableCell(__('Illegal'));
                if ($this->complexFlag) {
                    $cells .= wf_TableCell(__('Complex'));
                }
                $cells .= wf_TableCell(__('Social'));
                $cells .= wf_TableCell(__('Signups'));

                $rows = wf_TableRow($cells, 'row1');
                foreach ($yearData as $yearNum => $monthArr) {
                    foreach ($monthArr as $monthNum => $each) {
                        $yearDisplay = ($allTimeFlag) ? $yearNum . ' ' : '';
                        $cells = wf_TableCell($yearDisplay . $months[$monthNum]);
                        $cells .= wf_TableCell($each['c_totalusers']);
                        $cells .= wf_TableCell($each['c_activeusers'] . ' (' . zb_PercentValue($each['c_totalusers'], $each['c_activeusers']) . '%)');
                        $cells .= wf_TableCell($each['c_inactiveusers'] . ' (' . zb_PercentValue($each['c_totalusers'], $each['c_inactiveusers']) . '%)');
                        $cells .= wf_TableCell($each['c_illegal'] . ' (' . zb_PercentValue($each['c_totalusers'], $each['c_illegal']) . '%)');
                        if ($this->complexFlag) {
                            $cells .= wf_TableCell($each['c_complex'] . ' (' . zb_PercentValue($each['c_totalusers'], $each['c_complex']) . '%)');
                        }

                        $cells .= wf_TableCell($each['c_social'] . ' (' . zb_PercentValue($each['c_totalusers'], $each['c_social']) . '%)');
                        $cells .= wf_TableCell($each['c_signups']);

                        $rows .= wf_TableRow($cells, 'row3');
                        //chart data
                        $yearDisplay = ($monthNum == '01') ? $yearDisplay : '';
                        $ukvChartData[] = array($yearDisplay . $months[$monthNum], $each['c_totalusers'], $each['c_activeusers'], $each['c_inactiveusers'], $each['c_illegal'], $each['c_complex'], $each['c_social'], $each['c_signups']);
                    }
                }
                $result .= wf_TableBody($rows, '100%', 0, '');
                if ($chartsFlag) {
                    $result .= wf_gchartsLine($ukvChartData, __('UKV users'), '100%', '300px', $chartsOptions);
                }

                if (cfr('REPORTFINANCE')) {
                    //UKV financial data
                    $result .= wf_tag('h2') . __('UKV finance') . wf_tag('h2', true);
                    $cells = wf_TableCell(__('Month'));
                    $cells .= wf_TableCell(__('Money'));
                    $cells .= wf_TableCell(__('Payments count'));
                    $cells .= wf_TableCell(__('ARPU'));
                    $cells .= wf_TableCell(__('ARPAU'));
                    $cells .= wf_TableCell(__('Debt'));

                    $rows = wf_TableRow($cells, 'row1');
                    foreach ($yearData as $yearNum => $monthArr) {
                        foreach ($monthArr as $monthNum => $each) {
                            $yearDisplay = ($allTimeFlag) ? $yearNum . ' ' : '';
                            $cells = wf_TableCell($yearDisplay . $months[$monthNum]);
                            $cells .= wf_TableCell(zb_CashBigValueFormat($each['c_totalmoney']));
                            $cells .= wf_TableCell($each['c_paymentscount']);
                            $cells .= wf_TableCell($each['c_arpu']);
                            $cells .= wf_TableCell($each['c_arpau']);
                            $cells .= wf_TableCell($each['c_totaldebt']);
                            $rows .= wf_TableRow($cells, 'row3');
                            //chart data
                            $yearDisplay = ($monthNum == '01') ? $yearDisplay : '';
                            $ukvfChartData[] = array($yearDisplay . $months[$monthNum], $each['c_totalmoney'], $each['c_paymentscount'], $each['c_totaldebt']);
                            $ukvarpuChartData[] = array($yearDisplay . $months[$monthNum], $each['c_arpu'], $each['c_arpau']);
                        }
                    }
                    $result .= wf_TableBody($rows, '100%', 0, '');
                    if ($chartsFlag) {
                        $result .= wf_gchartsLine($ukvfChartData, __('UKV finance'), '100%', '300px', $chartsOptions);
                        $result .= wf_gchartsLine($ukvarpuChartData, __('UKV') . ' ' . __('ARPU'), '100%', '300px', $chartsOptions);
                    }
                }
            }

            //PBX integration
            if ($this->pbxFlag) {
                //incoming calls
                $result .= wf_tag('h2') . __('Telephony') . wf_tag('h2', true);
                $result .= wf_img('skins/calls/incoming.png') . ' ' . __('Incoming calls');
                $cells = wf_TableCell(__('Month'));
                $cells .= wf_TableCell(__('Incoming calls'));
                $cells .= wf_TableCell(__('Total answered'));
                $cells .= wf_TableCell(__('No answer'));
                $cells .= wf_TableCell(__('Total duration'));
                $cells .= wf_TableCell(__('Average duration'));
                $cells .= wf_TableCell(__('Answers percent'));
                $cells .= wf_TableCell(__('No reaction percent'));
                $cells .= wf_TableCell(__('Reaction time'));

                $rows = wf_TableRow($cells, 'row1');
                foreach ($yearData as $yearNum => $monthArr) {
                    foreach ($monthArr as $monthNum => $each) {
                        $yearDisplay = ($allTimeFlag) ? $yearNum . ' ' : '';
                        $cells = wf_TableCell($yearDisplay . $months[$monthNum]);
                        $cells .= wf_TableCell($each['a_totalcalls']);
                        $cells .= wf_TableCell($each['a_totalanswered']);
                        $cells .= wf_TableCell($each['a_totalcalls'] - $each['a_totalanswered']);
                        $cells .= wf_TableCell(zb_formatTime($each['a_totalcallsduration']));
                        $cells .= wf_TableCell(zb_formatTime($each['a_averagecallduration']));
                        $cells .= wf_TableCell(zb_PercentValue($each['a_totalcalls'], $each['a_totalanswered']) . '%');
                        $reactionPercent = ($each['a_recallunsuccess'] != NULL) ? $each['a_recallunsuccess'] . '%' : '';
                        $cells .= wf_TableCell($reactionPercent);
                        $cells .= wf_TableCell(zb_formatTime($each['a_recalltrytime']));
                        $rows .= wf_TableRow($cells, 'row3');
                        //chart data
                        $yearDisplay = ($monthNum == '01') ? $yearDisplay : '';
                        $telephonyChartData[] = array($yearDisplay . $months[$monthNum], $each['a_totalcalls'], $each['a_totalanswered'], ($each['a_totalcalls'] - $each['a_totalanswered']));
                    }
                }
                $result .= wf_TableBody($rows, '100%', 0, '');
                if ($chartsFlag) {
                    $result .= wf_gchartsLine($telephonyChartData, __('Telephony'), '100%', '300px', $chartsOptions);
                }

                //outcoming calls
                $result .= wf_img('skins/calls/outgoing.png') . ' ' . __('Outgoing calls') . wf_delimiter(0);
                $cells = wf_TableCell(__('Month'));
                $cells .= wf_TableCell(__('Outgoing calls'));
                $cells .= wf_TableCell(__('Total answered'));
                $cells .= wf_TableCell(__('No answer'));
                $cells .= wf_TableCell(__('Total duration'));
                $cells .= wf_TableCell(__('Average duration'));
                $cells .= wf_TableCell(__('Answers percent'));

                $rows = wf_TableRow($cells, 'row1');
                foreach ($yearData as $yearNum => $monthArr) {
                    foreach ($monthArr as $monthNum => $each) {
                        $yearDisplay = ($allTimeFlag) ? $yearNum . ' ' : '';
                        $cells = wf_TableCell($yearDisplay . $months[$monthNum]);
                        $cells .= wf_TableCell($each['a_outtotalcalls']);
                        $cells .= wf_TableCell($each['a_outtotalanswered']);
                        $cells .= wf_TableCell($each['a_outtotalcalls'] - $each['a_outtotalanswered']);
                        $cells .= wf_TableCell(zb_formatTime($each['a_outtotalcallsduration']));
                        $cells .= wf_TableCell(zb_formatTime($each['a_outaveragecallduration']));
                        $cells .= wf_TableCell(zb_PercentValue($each['a_outtotalcalls'], $each['a_outtotalanswered']) . '%');

                        $rows .= wf_TableRow($cells, 'row3');
                        //chart data
                        $yearDisplay = ($monthNum == '01') ? $yearDisplay : '';
                    }
                }
                $result .= wf_TableBody($rows, '100%', 0, '');
            }

            //Users relationship
            $result .= wf_tag('h2') . __('Relationships with the universe') . wf_tag('h2', true);
            $cells = wf_TableCell(__('Month'));
            if ($this->altCfg['SIGREQ_ENABLED']) {
                $cells .= wf_TableCell(__('Signup requests'));
            }
            $cells .= wf_TableCell(__('Helpdesk tickets'));
            $cells .= wf_TableCell(__('Tasks'));
            if ($this->altCfg['CAPABDIR_ENABLED']) {
                $cells .= wf_TableCell(__('Signup capabilities'));
                $cells .= wf_TableCell(__('Undone') . ' ' . __('Signup capabilities'));
            }

            $rows = wf_TableRow($cells, 'row1');
            foreach ($yearData as $yearNum => $monthArr) {
                foreach ($monthArr as $monthNum => $each) {
                    $yearDisplay = ($allTimeFlag) ? $yearNum . ' ' : '';
                    $cells = wf_TableCell($yearDisplay . $months[$monthNum]);
                    if ($this->altCfg['SIGREQ_ENABLED']) {
                        $cells .= wf_TableCell($each['t_sigreq']);
                    }
                    $cells .= wf_TableCell($each['t_tickets']);
                    $cells .= wf_TableCell($each['t_tasks']);
                    if ($this->altCfg['CAPABDIR_ENABLED']) {
                        $cells .= wf_TableCell($each['t_capabtotal']);
                        $cells .= wf_TableCell($each['t_capabundone']);
                    }
                    $rows .= wf_TableRow($cells, 'row3');
                    //chart data
                    $yearDisplay = ($monthNum == '01') ? $yearDisplay : '';
                    $universeChartData[] = array($yearDisplay . $months[$monthNum], $each['t_sigreq'], $each['t_tickets'], $each['t_tasks'], $each['t_capabtotal'], $each['t_capabundone']);
                }
            }
            $result .= wf_TableBody($rows, '100%', 0, '');
            if ($chartsFlag) {
                //deleting NULL values ugly hack
                if (!empty($universeChartData)) {
                    foreach ($universeChartData as $io => $each) {
                        if (!empty($each)) {
                            foreach ($each as $ia => $val) {
                                if ($val == NULL) {
                                    $universeChartData[$io][$ia] = 0;
                                }
                            }
                        }
                    }
                }
                $result .= wf_gchartsLine($universeChartData, __('Relationships with the universe'), '100%', '300px', $chartsOptions);
            }

            //Equipment
            $result .= wf_tag('h2') . __('Equipment') . wf_tag('h2', true);
            $cells = wf_TableCell(__('Month'));
            $cells .= wf_TableCell(__('Switches'));
            $cells .= wf_TableCell(__('Time') . ' ☠ ');

            if ($this->ponFlag) {
                $cells .= wf_TableCell(__('PON ONU'));
            }
            if ($this->docsisFlag) {
                $cells .= wf_TableCell(__('DOCSIS Modems'));
            }

            $rows = wf_TableRow($cells, 'row1');
            foreach ($yearData as $yearNum => $monthArr) {
                foreach ($monthArr as $monthNum => $each) {
                    $yearDisplay = ($allTimeFlag) ? $yearNum . ' ' : '';
                    $cells = wf_TableCell($yearDisplay . $months[$monthNum]);
                    $cells .= wf_TableCell($each['e_switches']);
                    $switchesRepingInterval = (@$this->altCfg['SWITCH_PING_INTERVAL']) ? $this->altCfg['SWITCH_PING_INTERVAL'] : 20;
                    $deadSwitchTime = ($switchesRepingInterval * $each['e_deadswintervals']) * 60;
                    $cells .= wf_TableCell(zb_formatTime($deadSwitchTime));
                    if ($this->ponFlag) {
                        $cells .= wf_TableCell($each['e_pononu']);
                    }
                    if ($this->docsisFlag) {
                        $cells .= wf_TableCell($each['e_docsis']);
                    }

                    $rows .= wf_TableRow($cells, 'row3');
                    //chart data
                    $yearDisplay = ($monthNum == '01') ? $yearDisplay : '';

                    $equipChartRow = array($yearDisplay . $months[$monthNum], $each['e_switches']);

                    if ($this->ponFlag AND $this->docsisFlag) {
                        $equipChartRow = array($yearDisplay . $months[$monthNum], $each['e_switches'], $each['e_pononu'], $each['e_docsis']);
                    }

                    if ($this->ponFlag AND !$this->docsisFlag) {
                        $equipChartRow = array($yearDisplay . $months[$monthNum], $each['e_switches'], $each['e_pononu']);
                    }

                    if ($this->docsisFlag AND !$this->ponFlag) {
                        $equipChartRow = array($yearDisplay . $months[$monthNum], $each['e_switches'], $each['e_docsis']);
                    }

                    $equipChartData[] = $equipChartRow;
                }
            }
            $result .= wf_TableBody($rows, '100%', 0, '');
            if ($chartsFlag) {
                $result .= wf_gchartsLine($equipChartData, __('Equipment'), '100%', '300px', $chartsOptions);
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing found'), 'info');
        }
        return ($result);
    }
}
