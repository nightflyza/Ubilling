<?php

/**
 * Bitrix24 CRM integration
 */
class BtrxCRM {

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains all available users data as login=>userData
     *
     * @var array
     */
    protected $allUserData = array();

    /**
     * Contains all stargazer raw DB data
     *
     * @var array
     */
    protected $allStgRawData = array();

    /**
     * Export URL HTTP abstraction placeholder
     *
     * @var object
     */
    protected $apiCrm = '';

    /**
     * Contains OpenPayz instance
     * 
     * @var object
     */
    protected $openPayz = '';

    /**
     * Contains all customers payemntIds as login=>paymentId
     *
     * @var array
     */
    protected $allPaymentIds = array();

    /**
     * Contains all users tags as login=>tagsArr
     *
     * @var string
     */
    protected $allUserTags = array();

    /**
     * Contains all of available tariffs prices as tariffname=>fee
     *
     * @var array
     */
    protected $allTariffsPricess = array();

    /**
     * Contains all of available tariff speeds as tariffname=>data (speeddown/speedup keys)
     *
     * @var array
     */
    protected $allTariffsSpeeds = array();

    /**
     * Contains extended mobiles instance
     *
     * @var object
     */
    protected $extMobiles = '';

    /**
     * Contains all user extmobiles info as login=>mobilesExtData
     *
     * @var array
     */
    protected $allExtMobiles = array();

    /**
     * Contains current instance URL to push some data
     *
     * @var string
     */
    protected $exportUrl = '';

    /**
     * Name of POST variable to export updated users data
     *
     * @var string
     */
    protected $exportVar = '';

    /**
     * LastActivityTime export flag
     *
     * @var bool
     */
    protected $exportLatFlag = false;

    /**
     * System caching instance placeholder
     *
     * @var object
     */
    protected $cache = '';

    /**
     * Current instance caching timeout
     *
     * @var int
     */
    protected $cacheTimeout = 2592000;

    /**
     * Contains already exported user data as login=>hash(?)
     *
     * @var array
     */
    protected $cachedUsers = array();

    /**
     * Deal With It database abstraction layer
     *
     * @var object
     */
    protected $dealWithItDb = '';

    /**
     * Payments database abstraction layer
     *
     * @var object
     */
    protected $paymentsDb = '';

    /**
     * Contains records about first user payments dates as login=>date
     *
     * @var array
     */
    protected $firstUserPayments = array();

    /**
     * Contains records about latest user payments dates login=>date
     *
     * @var array
     */
    protected $latestUserPayments = array();

    /**
     * Contains all user payments summ as login=>summ
     *
     * @var array
     */
    protected $userPaymentsSumm = array();

    /**
     * Contains all users deal with it data about tariff changes as login=>date/tariff as param
     * 
     * @var array
     */
    protected $allDealWithItChanges = array();

    /**
     * Contains all user assigned agents data as login=>agentName
     *
     * @var array
     */
    protected $allUserAgents = array();

    /**
     * Contains all user assigned ONU signals as login=>signal
     *
     * @var array
     */
    protected $allUserOnuSignals = array();

    /**
     * Some predefined stuff here
     */
    const CACHE_KEY = 'BTRX_DATA';
    const PID_NAME = 'BITRX24_UPD';

    /**
     * Point me to the sky above
     * I can't get there on my own
     * Walk me to the graveyard
     * Dig up her bones
     */
    public function __construct() {
        $this->loadConfig();
        $this->initCache();
        $this->initApiCrm();
        $this->initOpenPayz();
        $this->initDealWithItDb();
        $this->initPaymentsDb();
        $this->initExtMobiles();
        $this->loadUserData();
        $this->loadPaymentsData();
        $this->loadExtMobiles();
        $this->loadTariffsData();
        $this->loadDealWithitData();
        $this->loadUserTags();
        $this->loadPayIds();
        $this->loadAgentsData();
        $this->loadPonizerData();
        $this->loadCachedData();
    }

    /**
     * Preloads some required configs for further usage
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfig() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
        if (isset($this->altCfg['BTRX24_EXPORT_URL'])) {
            $this->exportUrl = $this->altCfg['BTRX24_EXPORT_URL'];
        }

        if (isset($this->altCfg['BTRX24_EXPORT_VAR'])) {
            $this->exportVar = $this->altCfg['BTRX24_EXPORT_VAR'];
        }

        if (isset($this->altCfg['BTRX24_EXPORT_LAT'])) {
            if ($this->altCfg['BTRX24_EXPORT_LAT']) {
                $this->exportLatFlag = true;
            }
        }
    }

    /**
     * Inits system caching layer
     * 
     * @return void
     */
    protected function initCache() {
        $this->cache = new UbillingCache();
    }

    /**
     * Inits OpenPayz instance
     * 
     * @return void
     */
    protected function initOpenPayz() {
        $this->openPayz = new OpenPayz();
    }

    /**
     * Inits scheduler database abstraction layer
     * 
     * @return void
     */
    protected function initDealWithItDb() {
        $this->dealWithItDb = new NyanORM('dealwithit');
    }

    /**
     * Loads deal with it tariff changes
     * 
     * @return void
     */
    protected function loadDealWithitData() {
        $this->dealWithItDb->where('action', '=', 'tariffchange');
        $this->dealWithItDb->orderBy('date', 'DESC');
        $this->allDealWithItChanges = $this->dealWithItDb->getAll('login');
    }

    /**
     * Inits extended mobiles instance
     * 
     * @return void
     */
    protected function initExtMobiles() {
        $this->extMobiles = new MobilesExt();
    }

    /**
     * Loads all users extmobiles data
     * 
     * @return void
     */
    protected function loadExtMobiles() {
        $this->allExtMobiles = $this->extMobiles->getAllUsersMobileNumbers();
    }

    /**
     * Inits payments database abstraction layer
     * 
     * @return void
     */
    protected function initPaymentsDb() {
        $this->paymentsDb = new NyanORM('payments');
    }

    /**
     * Preloads first/last user payments data
     * 
     * @return void
     */
    protected function loadPaymentsData() {
        //user payments summ total
        $this->paymentsDb->where('summ', '>', 0);
        $this->paymentsDb->selectable(array('id', 'login', 'date', 'summ'));
        $this->paymentsDb->orderBy('id', 'ASC');
        $rawPayments = $this->paymentsDb->getAll();

        if (!empty($rawPayments)) {
            foreach ($rawPayments as $io => $each) {
                if (is_numeric($each['summ'])) {
                    if (isset($this->userPaymentsSumm[$each['login']])) {
                        $this->userPaymentsSumm[$each['login']] += $each['summ'];
                        //latest user payment date here
                        $this->latestUserPayments[$each['login']] = $each['date'];
                    } else {
                        //first occurency
                        $this->userPaymentsSumm[$each['login']] = $each['summ'];
                        //looks like first payment
                        $this->firstUserPayments[$each['login']] = $each['date'];
                    }
                }
            }
        }
    }

    /**
     * Loads all available users PaymentIds
     * 
     * @return void
     */
    protected function loadPayIds() {
        $opCustomers = $this->openPayz->getCustomers();
        $this->allPaymentIds = array_flip($opCustomers); // login=>payId
    }

    /**
     * Inits CRM HTTP abstraction layer
     * 
     * @return void
     */
    protected function initApiCrm() {
        if (!empty($this->exportUrl)) {
            $this->apiCrm = new OmaeUrl($this->exportUrl);
        } else {
            throw new Exception('EX_NO_EXPORT_URL');
        }
    }

    /**
     * Loads all users agent assigns data
     * 
     * @return void
     */
    protected function loadAgentsData() {
        if (!empty($this->allUserData)) {
            $allAssigns = zb_AgentAssignGetAllData();
            $allStrictAssigns = zb_AgentAssignStrictGetAllData();
            $allAgentsData = zb_ExportAgentsLoadAll();
            foreach ($this->allUserData as $io => $each) {
                $assignedAgentId = zb_AgentAssignCheckLoginFast($each['login'], $allAssigns, $each['fulladress'], $allStrictAssigns);
                if ($assignedAgentId) {
                    if (isset($allAgentsData[$assignedAgentId])) {
                        $this->allUserAgents[$each['login']] = $allAgentsData[$assignedAgentId]['contrname'];
                    }
                }
            }
        }
    }

    /**
     * Loads all PONizer related data
     * 
     * @return void
     */
    protected function loadPonizerData() {
        $ponizer = new PONizer();
        $this->allUserOnuSignals = $ponizer->getAllONUSignals();
    }

    /**
     * Loads available tariffs data
     * 
     * @return void
     */
    protected function loadTariffsData() {
        $this->allTariffsPricess = zb_TariffGetPricesAll();
        $this->allTariffsSpeeds = zb_TariffGetAllSpeeds();
    }

    /**
     * Preloads all existing users data
     * 
     * @return void
     */
    protected function loadUserData() {
        $this->allUserData = zb_UserGetAllDataCache();
        $this->allStgRawData = zb_UserGetAllStargazerDataAssoc();
    }

    /**
     * Preloads previously exported to CRM data from cache
     * 
     * @return void
     */
    protected function loadCachedData() {
        $rawCachedData = $this->cache->get(self::CACHE_KEY, $this->cacheTimeout);
        if (!empty($rawCachedData)) {
            $this->cachedUsers = $rawCachedData; // fuck mem economy, lol
        }
    }

    /**
     * Loads existing tag types from database
     * 
     * @return void
     */
    protected function loadUserTags() {
        $this->allUserTags = zb_UserGetAllTags();
    }

    /**
     * Returns unique user numeric ID
     * 
     * @param string $userLogin
     * 
     * @return int
     */
    protected function getUserUniqId($userLogin) {
        $result = 0;
        if (!empty($userLogin)) {
            $result = crc32($userLogin);
        }
        return($result);
    }

    /**
     * Returns users tags list as string, if assigned
     * 
     * @param string $userLogin
     * 
     * @return string
     */
    protected function getUserTagsList($userLogin) {
        $result = '';
        if (isset($this->allUserTags[$userLogin])) {
            if (!empty($this->allUserTags[$userLogin])) {
                $result .= implode(',', $this->allUserTags[$userLogin]);
            }
        }
        return($result);
    }

    /**
     * 
     * @param array $userData
     * 
     * @return array
     */
    protected function getUserStruct($userData) {
        $result = array();
        if (!empty($userData)) {
            $userLogin = $userData['login'];
            $userPassword = $userData['Password'];
            $fullAddress = $userData['fulladress'];
            $userIp = $userData['ip'];
            $userMac = $userData['mac'];
            $userPaymentId = (isset($this->allPaymentIds[$userLogin])) ? $this->allPaymentIds[$userLogin] : '';
            $userRealName = $userData['realname'];
            $userBalance = $userData['Cash'];
            $userCredit = $userData['Credit'];
            $creditExpire = $this->allStgRawData[$userLogin]['CreditExpire'];
            $userFrozen = $userData['Passive'];
            $userTags = $this->getUserTagsList($userLogin);
            $latTimestamp = 0;
            if ($this->exportLatFlag) {
                $latTimestamp = $this->allStgRawData[$userLogin]['LastActivityTime'];
            }

            $userAgent = (isset($this->allUserAgents[$userLogin])) ? $this->allUserAgents[$userLogin] : '';
            $onuSignal = (isset($this->allUserOnuSignals[$userLogin])) ? $this->allUserOnuSignals[$userLogin] : 0;

            //tariffs related data
            $userTariff = $userData['Tariff'];
            $speedDown = (isset($this->allTariffsSpeeds[$userTariff]['speeddown'])) ? $this->allTariffsSpeeds[$userTariff]['speeddown'] : 0;
            $speedUp = (isset($this->allTariffsSpeeds[$userTariff]['speedup'])) ? $this->allTariffsSpeeds[$userTariff]['speedup'] : 0;
            $tariffFee = isset($this->allTariffsPricess[$userTariff]) ? $this->allTariffsPricess[$userTariff] : 0;
            $tariffChange = (isset($this->allDealWithItChanges[$userLogin])) ? $this->allDealWithItChanges[$userLogin]['param'] : '';
            $tariffChangeDate = (isset($this->allDealWithItChanges[$userLogin])) ? $this->allDealWithItChanges[$userLogin]['date'] : 0;
            if ($tariffChangeDate) {
                $tariffChangeDate = strtotime($tariffChangeDate . ' 02:10:00');
            }

            //payments related data
            $firstPaymentDate = (isset($this->firstUserPayments[$userLogin])) ? strtotime($this->firstUserPayments[$userLogin]) : 0;
            $latestPaymentDate = (isset($this->latestUserPayments[$userLogin])) ? strtotime($this->latestUserPayments[$userLogin]) : 0;
            $userPaymentsSumm = (isset($this->userPaymentsSumm[$userLogin])) ? $this->userPaymentsSumm[$userLogin] : 0;

            //phone related data
            $userPhone = $userData['mobile'];
            $mobileExt = '';
            if (isset($this->allExtMobiles[$userLogin])) {
                $mobileExt = $this->allExtMobiles[$userLogin][0];
            }

//
//
//                                _(\_/) 
//                              ,((((^`\
//                             ((((  (6 \ 
//                           ,((((( ,    \
//       ,,,_              ,(((((  /"._  ,`,
//      ((((\\ ,...       ,((((   /    `-.-'
//      )))  ;'    `"'"'""((((   (      
//     (((  /            (((      \
//      )) |                      |
//     ((  |        .       '     |
//     ))  \     _ '      `t   ,.')
//     (   |   y;- -,-""'"-.\   \/  
//     )   / ./  ) /         `\  \
//        |./   ( (           / /'
//        ||     \\          //'|
//        ||      \\       _//'|| CIRCUS WITH THE HORSES
//        ||       ))     |_/  ||
//        \_\     |_/          ||
//        `'"                  \_\


            $result = array(
                'phone' => $userPhone,
                'fio' => $userRealName,
                'pay_id' => $userPaymentId,
                'login' => $userLogin,
                'password' => $userPassword,
                'tariff' => $userTariff,
                'balance' => $userBalance,
                'tegs' => $userTags,
                'speed_up' => $speedUp,
                'speed_down' => $speedDown,
                'abonplata' => $tariffFee,
                'cash_first_pay' => $firstPaymentDate,
                'mobile2' => $mobileExt,
                'cash_last_pay' => $latestPaymentDate,
                'full_adress' => $fullAddress,
                'cash_all_pays' => $userPaymentsSumm,
                'deal_with_it1' => $tariffChangeDate,
                'deal_with_it2' => $tariffChange,
                'owner' => $userAgent,
                'credit' => $userCredit,
                'credit_day' => $creditExpire,
                'ip' => $userIp,
                'mac' => $userMac,
                'onu_signal' => $onuSignal
            );
        }
        return($result);
    }

    /**
     * Pushes changed users struct into CRM hook
     * 
     * @param array $changedUsersStruct
     * 
     * @return void
     */
    protected function pushCrmData($changedUsersStruct) {
        $jsonData = json_encode($changedUsersStruct);
        $this->apiCrm->dataPost($this->exportVar, $jsonData);
        $this->apiCrm->response();
    }

    /**
     * Processing of existing user data to deicide export this to CRM or nothing changed?
     * 
     * @return void
     */
    public function runExport() {
        $somethingChanged = array();
        if (!empty($this->allUserData)) {
            foreach ($this->allUserData as $eachUserLogin => $eachUserData) {
                $updateFlag = false;
                $eachUserStruct = $this->getUserStruct($eachUserData);
                if (isset($this->cachedUsers[$eachUserLogin])) {
                    //user data was changed someway
                    if ($eachUserStruct != $this->cachedUsers[$eachUserLogin]) {
                        $updateFlag = true;
                    }
                } else {
                    //newly registered user?
                    $updateFlag = true;
                }

                if ($updateFlag) {
                    $somethingChanged[] = $eachUserStruct;
                    $this->cachedUsers[$eachUserLogin] = $eachUserStruct;
                }
            }

            //pushing data to CRM
            if (!empty($somethingChanged)) {
                $this->pushCrmData($somethingChanged);
            }

            //saving cache
            $this->cache->set(self::CACHE_KEY, $this->cachedUsers, $this->cacheTimeout);
        }
    }

}
