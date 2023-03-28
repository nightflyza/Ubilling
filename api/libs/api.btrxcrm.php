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
     * Some predefined stuff here
     */
    const CACHE_KEY = 'BTRX_DATA';
    const PID_NAME = 'BITRX24_UPD';

    public function __construct() {
        $this->loadConfig();
        $this->initCache();
        $this->initApiCrm();
        $this->initOpenPayz();
        $this->loadUserData();
        $this->loadTariffsData();
        $this->loadUserTags();
        $this->loadPayIds();
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
            $userUniqId = $this->getUserUniqId($userLogin);
            $fullAddress = $userData['fulladress'];
            $cuttedAddress = mb_substr($fullAddress, 0, 7); // why 7???
            $userPaymentId = (isset($this->allPaymentIds[$userLogin])) ? $this->allPaymentIds[$userLogin] : '';
            $userTariff = $userData['Tariff'];
            $userPhone = $userData['mobile'];
            $userRealName = $userData['realname'];
            $userContract = $userData['contract'];
            $userBalance = $userData['Cash'];
            $userTags = $this->getUserTagsList($userLogin);
            $latTimestamp = 0;
            if ($this->exportLatFlag) {
                $latTimestamp = $this->allStgRawData[$userLogin]['LastActivityTime'];
            }

            $speedDown = (isset($this->allTariffsSpeeds[$userTariff]['speeddown'])) ? $this->allTariffsSpeeds[$userTariff]['speeddown'] : 0;
            $speedUp = (isset($this->allTariffsSpeeds[$userTariff]['speedup'])) ? $this->allTariffsSpeeds[$userTariff]['speedup'] : 0;
            $tariffFee = isset($this->allTariffsPricess[$userTariff]) ? $this->allTariffsPricess[$userTariff] : 0;

            $result = array(
                'contact_id' => $userUniqId,
                'phone' => $userPhone,
                'fio' => $userRealName,
                'first_7' => $cuttedAddress,
                'deal_id' => $userContract,
                'pay_id' => $userPaymentId,
                'login' => $userLogin,
                'tariff' => $userTariff,
                'balance' => $userBalance,
                'last_act' => $latTimestamp,
                'tegs' => $userTags,
                'speed_up' => $speedUp,
                'speed_down' => $speedDown,
                'abonplata' => $tariffFee,
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
