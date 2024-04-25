<?php

/**
 * OphanimFlow integration implementation
 */
class OphanimFlow {

    /**
     * Contains alter config as key=>value
     * 
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains current year
     * 
     * @var int
     */
    protected $currentYear = 0;

    /**
     * Contains current month number without leading zero
     * 
     * @var int
     */
    protected $currentMonth = 0;

    /**
     * Traff stats database abstraction layer
     * 
     * @var object
     */
    protected $traffDb = '';

    /**
     * Contains previously saved traffic stats as login=>id/login/month/year/d0/u0
     * 
     * @var array
     */
    protected $traffStats = array();

    /**
     * Contains current run traff
     * 
     * @var array
     */
    protected $currentTraff = array();

    /**
     * Contains array of all existing users IPs as IP=>login
     * 
     * @var array
     */
    protected $allUserIps = array();

    /**
     * Contains OphanimFlow URLs to pull traffic data as idx=>url
     * 
     * @var array
     */
    protected $ophanimUrls = array();

    //some predefined stuff here
    const TABLE_TRAFFDATA = 'ophtraff';
    const API_ENDPOINT = '/?module=gettraff';
    const OPTION_ENABLED = 'OPHANIMFLOW_ENABLED';
    const OPTION_URLS = 'OPHANIMFLOW_URLS';
    const OPTION_DIMENSIONS='OPHANIM_DIMENSIONS';
    const PID_SYNC='OPHANIMTRAFF';

    public function __construct() {
        // BE NOT AFRAID
        $this->setDates();
        $this->initDb();
        $this->loadConfigs();
    }

    /**
     * Sets current instance dates info
     * 
     * @return void
     */
    protected function setDates() {
        $this->currentYear = date("Y");
        $this->currentMonth = date("n");
    }

    /**
     * Inits database abstraction layer
     * 
     * @return void
     */
    protected function initDb() {
        $this->traffDb = new NyanORM(self::TABLE_TRAFFDATA);
    }

    /**
     * Load requred configs, sets some properties
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfigs() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
        if (isset($this->altCfg[self::OPTION_URLS])) {
            if (!empty($this->altCfg[self::OPTION_URLS])) {
                $urlsRaw = explode(',', $this->altCfg[self::OPTION_URLS]);
                if (!empty($urlsRaw)) {
                    foreach ($urlsRaw as $io => $eachUrl) {
                        $this->ophanimUrls[] = $eachUrl;
                    }
                }
            }
        }
    }

    /**
     * Loads all reqired user data
     * 
     * @return void
     */
    protected function loadUserData() {
        $loginIps = zb_UserGetAllIPs();
        if (!empty($loginIps)) {
            $this->allUserIps = array_flip($loginIps);
        }
    }

    /**
     * Loads saved traff stats
     * 
     * @return void
     */
    protected function loadTraffStats() {
        $this->traffDb->where('year', '=', $this->currentYear);
        $this->traffDb->where('month', '=', $this->currentMonth);
        $this->traffStats = $this->traffDb->getAll('login');
    }

    /**
     * Preprocess fetched data and update local database records
     * 
     * @param array $rawData
     * 
     * @return void
     */
    protected function processOphanimTraff($rawData) {
        if (!empty($rawData)) {
            foreach ($rawData as $eachIp => $eachTraffData) {
                if (isset($this->allUserIps[$eachIp])) {
                    $userLogin = $this->allUserIps[$eachIp];
                    if (isset($this->traffStats[$userLogin])) {
                        //existing record
                        $savedData = $this->traffStats[$userLogin];
                        $recordId = $savedData['id'];
                        $savedDl = $savedData['D0'];
                        $savedUl = $savedData['U0'];

                        $newDl = $eachTraffData['dl'];
                        $newUl = $eachTraffData['ul'];
                        //traffic counters changed?
                        if ($newDl != $savedDl OR $newUl != $savedUl) {
                            $this->traffDb->data('D0', $newDl);
                            $this->traffDb->data('U0', $newUl);
                            $this->traffDb->where('id', '=', $recordId);
                            $this->traffDb->save(true, true);
                        }
                    } else {
                        //new record
                        $this->traffDb->data('login', $userLogin);
                        $this->traffDb->data('month', $this->currentMonth);
                        $this->traffDb->data('year', $this->currentYear);
                        $this->traffDb->data('D0', $eachTraffData['dl']);
                        $this->traffDb->data('U0', $eachTraffData['ul']);
                        $this->traffDb->create();
                    }
                }
            }
        }
    }

    /**
     * Fetches data from ophanimUrls and updates local db
     * 
     * @return void
     */
    public function traffDataProcessing() {
        if (!empty($this->ophanimUrls)) {
            $this->loadTraffStats();
            $this->loadUserData();
            foreach ($this->ophanimUrls as $io => $eachUrl) {
                $apiEndpoint = new OmaeUrl($eachUrl . self::API_ENDPOINT);
                $rawData = $apiEndpoint->response();
                if (!empty($rawData)) {
                    @$rawData = json_decode($rawData, true);
                    if (is_array($rawData)) {
                        if (!empty($rawData)) {
                            $this->processOphanimTraff($rawData);
                        }
                    }
                }
            }
        }
    }

    /**
     * Returns array of current month traffic for some user as D0/U0 array
     * 
     * @param string $userLogin
     * 
     * @return array
     */
    public function getUserCurMonthTraff($userLogin) {
        $result = array(
            'D0' => 0,
            'U0' => 0
        );

        $userLogin = ubRouting::filters($userLogin, 'mres');
        $this->traffDb->where('login', '=', $userLogin);
        $this->traffDb->where('year', '=', $this->currentYear);
        $this->traffDb->where('month', '=', $this->currentMonth);
        $rawData = $this->traffDb->getAll();
        if (!empty($rawData)) {
            $result['D0'] = $rawData[0]['D0'];
            $result['U0'] = $rawData[0]['U0'];
        }
        return($result);
    }

    /**
     * Returns all of previous user traff data
     * 
     * @param string $userLogin
     * 
     * @return array
     */
    public function getUserAllTraff($userLogin) {
        $userLogin = ubRouting::filters($userLogin, 'mres');
        $result = array();
        $this->traffDb->where('login', '=', $userLogin);
        $this->traffDb->orderBy('year`,`month', 'DESC');
        $result = $this->traffDb->getAll();
        return($result);
    }

    /**
     * Returns all users current month aggregated traffic summ as login=>bytesCount
     * 
     * @return array
     */
    public function getAllUsersAggrTraff() {
        $result = array();
        $this->traffDb->selectable(array('login', 'D0', 'U0'));
        $this->traffDb->where('year', '=', $this->currentYear);
        $this->traffDb->where('month', '=', $this->currentMonth);
        $raw = $this->traffDb->getAll();
        if (!empty($raw)) {
            foreach ($raw as $io => $each) {
                $result[$each['login']] = $each['D0'] + $each['U0'];
            }
        }
        return($result);
    }

    /**
     * Життя майне, як хвилина
     * Кожна смертна година
     * Пручатись варто
     * Злому жарту?
     */
}
