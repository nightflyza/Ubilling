<?php

class SormYahont {

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains stargazer users table as login=>userdata
     *
     * @var array
     */
    protected $allUsers = array();

    /**
     * Contains users data with fields like address, realname, etc as login=>userdata
     *
     * @var array
     */
    protected $allUsersData = array();

    /**
     * Contains all contract dates as contract=>date
     *
     * @var array
     */
    protected $allContractDates = array();

    /**
     * Contains users passport data as login=>passportdata
     *
     * @var array
     */
    protected $AllPassportData = array();

    /**
     * Default branch ID
     *
     * @var int
     */
    protected $branchId = 1;

    /**
     * Export date format
     */
    const DATE_FORMAT = 'd.m.Y H:i:s';

    /**
     * Ubilling database charset
     */
    const IN_CHARSET = 'utf-8';

    /**
     * Output charset
     */
    const OUT_CHARSET = 'windows-1251';

    /**
     * Default output CSV delimiter
     */
    const DELIMITER = ';';

    /**
     * Default CSV enclosure
     */
    const ENCLOSURE = '"';

    /**
     * Creates new SormYahont instance
     *
     * @return void 
     */
    public function __construct() {
        $this->loadAlter();
        $this->setOptions();
        $this->loadUsersData();
        $this->loadContractDates();
        $this->loadPassportData();
    }

    /**
     * Loads system alter config into protected property for further usage
     * 
     * @global type $ubillingConfig
     * 
     * @return void
     */
    protected function loadAlter() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Sets some object config-based options if required
     * 
     * @return void
     */
    protected function setOptions() {
        //nothing yet here
    }

    /**
     * Loads users data from database into protected object props
     * 
     * @return void
     */
    protected function loadUsersData() {
        $this->allUsers = zb_UserGetAllStargazerDataAssoc();
        $this->allUsersData = zb_UserGetAllDataCache();
    }

    /**
     * Loads all contract dates
     * 
     * @return void
     */
    protected function loadContractDates() {
        $this->allContractDates = zb_UserContractDatesGetAll();
    }

    /**
     * Loads all users passport data
     * 
     * @return void
     */
    protected function loadPassportData() {
        $this->AllPassportData = zb_UserPassportDataGetAll();
    }

    /**
     * Little workaround for future multiple branches support
     * 
     * @param string $userLogin
     * 
     * @return int
     */
    protected function getUserBranchId($userLogin) {
        return ($this->branchId);
    }

    /**
     * Encodes data to output charset before export
     * 
     * @param string $data
     * 
     * @return string
     */
    protected function changeCharset($data) {
        $data = iconv(self::IN_CHARSET, self::OUT_CHARSET, $data);
        return ($data);
    }

    /**
     * Casts date in required format
     * 
     * @param string $date
     * 
     * @return string
     */
    protected function formatDate($date) {
        $result = '';
        if (!empty($date)) {
            $timestamp = strtotime($date);
            $result = date(self::DATE_FORMAT, $timestamp);
        }
        return ($result);
    }

    /**
     * Converts single dimension array into CSV string data
     * 
     * @param array $fields
     * @param string $delimiter
     * @param string $enclosure
     * @param bool $encloseAll
     * @param bool $nullToMysqlNull
     * 
     * @return string
     */
    protected function arrayToCsv(array &$fields, $delimiter = ';', $enclosure = '"', $encloseAll = false, $nullToMysqlNull = false) {
        $delimiter_esc = preg_quote($delimiter, '/');
        $enclosure_esc = preg_quote($enclosure, '/');

        $output = array();
        foreach ($fields as $field) {
            if ($field === null && $nullToMysqlNull) {
                $output[] = 'NULL';
                continue;
            }

            // Enclose fields containing $delimiter, $enclosure or whitespace
            if ($encloseAll || preg_match("/(?:${delimiter_esc}|${enclosure_esc}|\s)/", $field)) {
                $output[] = $enclosure . str_replace($enclosure, $enclosure . $enclosure, $field) . $enclosure;
            } else {
                $output[] = $field;
            }
        }

        return implode($delimiter, $output);
    }

    /**
     * Returns  user data squense 4.1
     * 
     * @return string
     */
    public function getUserData() {
        $result = '';
        if (!empty($this->allUsersData)) {
            foreach ($this->allUsersData as $io => $each) {
                $userLogin = $each['login'];
                $stgData = $this->allUsers[$userLogin];
                $userContract = $each['contract'];
                $userContractDate = @$this->allContractDates[$userContract];
                $userState = 1;
                //detecting user state
                if (($each['Cash'] <= $each['Credit']) OR ( $each['Passive'] == 1) OR ( $stgData['Down'] == 1) OR ( $each['AlwaysOnline'] == 0)) {
                    $userState = 0;
                }
                $dataTmp = array(
                    $this->getUserBranchId($userLogin), //default branch
                    $userLogin, // login
                    $each['ip'], // ip
                    $each['email'], // email
                    $each['mobile'], // phone
                    $each['mac'], // mac
                    $this->formatDate($userContractDate), //contract date
                    $userContract, //contract number
                    $userState, //user state
                    $this->formatDate($userContractDate), //using contract date as service activation date
                    '', //using empty value as service deactivation date
                    0, // by default home user, may be we can detect corporative users (1) if CORPS_ENABLED
                    1, //single string user data fields
                    //empty struct realname data for 3 fields , using type 1
                    '', // first name
                    '', // patronymic
                    '', // surname
                    $each['realname'], //realname as single string
                    $this->formatDate(@$this->AllPassportData[$userLogin]['birthdate']), // birthdate
                    1, //single string passport data
                    //empty struct passport data for 3 fields, using type 1
                    '', // passport series
                    '', // passport number
                    '', // when and who applied
                    //unsctruct passport data below
                    @$this->AllPassportData[$userLogin]['passportnum'] . ' ' . @$this->AllPassportData[$userLogin]['passportdate'] . ' ' . @$this->AllPassportData[$userLogin]['passportwho'],
                    1, // i guess 1 is passport
                    '', //empty user bank
                    '', //empty bank account
                    //corporate users data below, now its unprocessed
                    '', //empty corp name
                    '', //empty INN
                    '', //empty contact person
                    '', //empty phones/faxes
                    '', //empty corp bank name
                    '', //empty corp bank account
                    1, // single string address data
                    //empty struct address 9 fields, using type 1
                    '', // postal index aka zip
                    '', // country
                    '', // region
                    '', // district
                    '', // city name
                    '', // street
                    '', // build num
                    '', // housing
                    '', // apartment
                    $each['fulladress'], //single string address
                    1, //single string device address
                    //empty 9 fields for struct device address
                    '', // postal index aka zip
                    '', // country
                    '', // region
                    '', // district
                    '', // city name
                    '', // street
                    '', // build num
                    '', // housing
                    '', // apt
                    $each['fulladress'], //using user address as device address
                );
                $result.= $this->arrayToCsv($dataTmp, self::DELIMITER, self::ENCLOSURE, true) . PHP_EOL;
            }
        }
        return ($result);
    }

    /**
     * Returns user services data squense 4.2
     * 
     * @return string
     */
    public function getServicesData() {
        $result = '';
        if (!empty($this->allUsersData)) {
            foreach ($this->allUsersData as $io => $each) {
                $userLogin = $each['login'];
                $stgData = $this->allUsers[$userLogin];
                $userContract = $each['contract'];
                $userContractDate = @$this->allContractDates[$userContract];
                $dataTmp = array(
                    $this->getUserBranchId($userLogin), //default branch
                    $userLogin, // login
                    $userContract, //contract number
                    1, //using something like service ID
                    $this->formatDate($userContractDate), //contract date
                    '', //using empty value as service deactivation date
                    '', // using empty service custom parameters
                );
                $result.= $this->arrayToCsv($dataTmp, self::DELIMITER, self::ENCLOSURE, true) . PHP_EOL;
            }
        }
        return ($result);
    }

    /**
     * Banks transactons data squense 6.1 returns empty data because no mechanics for detecting it
     * 
     * @return string
     */
    public function getBankTransactions() {
        $result = '';
        return ($result);
    }

    /**
     * Payment cards usage data squense 6.2
     * 
     * @return string
     */
    public function getPaycardsTransactions() {
        $result = '';
        $query = "SELECT * from `cardbank` WHERE `usedlogin`!='';";
        $allCards = simple_queryall($query);
        if (!empty($allCards)) {
            foreach ($allCards as $io => $each) {
                $userLogin = $each['usedlogin'];
                //not showing card payments for users that not exists anymore
                if (isset($this->allUsersData[$userLogin])) {
                    $userData = $this->allUsersData[$userLogin];
                    $userContract = $userData['contract'];

                    $dataTmp = array(
                        $this->getUserBranchId($userLogin), //default branch ID
                        $userContract, //user contract
                        $userData['ip'], //user IP
                        $this->formatDate($each['usedate']), //card usage aka payment date
                        $each['part'] . $each['serial'], //card part and number
                        $each['cash'] // card price
                    );
                    $result.= $this->arrayToCsv($dataTmp, self::DELIMITER, self::ENCLOSURE, true) . PHP_EOL;
                }
            }
        }
        return ($result);
    }

    /**
     * Returns existing OpenPays transactions data squense 6.3
     * 
     * @return string
     */
    public function getOpenPayzTransactions() {
        $result = '';
        //is openpayz used on this host?
        if (zb_CheckTableExists('op_transactions')) {
            $allPayIds = array();
            $queryPayIds = "SELECT * from `op_customers`";
            $allPayIdsTmp = simple_queryall($queryPayIds);
            //payment IDs preprocessing
            if (!empty($allPayIdsTmp)) {
                foreach ($allPayIdsTmp as $io => $each) {
                    $allPayIds[$each['virtualid']] = $each['realid'];
                }
            }
            //transactions processing
            $query = "SELECT * from `op_transactions`";
            $allTransactions = simple_queryall($query);
            if (!empty($allTransactions)) {
                foreach ($allTransactions as $io => $each) {
                    //detecting user login by its PaymentID
                    if (isset($allPayIds[$each['customerid']])) {
                        $userLogin = $allPayIds[$each['customerid']];
                        //not showing transactions for users that not exists anymore
                        if (isset($this->allUsersData[$userLogin])) {
                            $userData = $this->allUsersData[$userLogin];
                            $dataTmp = array(
                                $this->getUserBranchId($userLogin), //user branch ID
                                $userData['contract'], // user contract number
                                $this->formatDate($each['date']), //transaction processing aka payment date
                                $each['paysys'] . ' ' . $each['hash'], //using payment system name + hash as terminal ID
                                '', //we dont know anything about terminal number
                                '', //and nothing about its address
                                $each['summ'], //but we know transaction summ
                            );
                            $result.= $this->arrayToCsv($dataTmp, self::DELIMITER, self::ENCLOSURE, true) . PHP_EOL;
                        }
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Returns data squense 6.4 for users cash payments
     * 
     * @return string
     */
    public function getCashPayments() {
        $result = '';
        $query = "SELECT * from `payments` WHERE `cashtypeid`='1'  AND `summ`>0;";
        $allPayments = simple_queryall($query);
        if (!empty($allPayments)) {
            foreach ($allPayments as $io => $each) {
                $userLogin = $each['login'];
                //no export payments for users that not exists anymore
                if (isset($this->allUsersData[$userLogin])) {
                    $userData = $this->allUsersData[$userLogin];
                    $dataTmp = array(
                        $this->getUserBranchId($userLogin), //user branch ID
                        $userData['contract'], //user contract number
                        $userData['ip'], //user IP address
                        $this->formatDate($each['date']), //payment date
                        'cashbox', // its cash payment point
                        //6 empty fields for cashbox address 
                        '', // country
                        '', // region
                        '', // district
                        '', // city name
                        '', // street
                        '', //build num
                        $each['summ'], // payment sum
                    );
                    $result.= $this->arrayToCsv($dataTmp, self::DELIMITER, self::ENCLOSURE, true) . PHP_EOL;
                }
            }
        }
        return ($result);
    }

    /**
     * Returns data about payments summary aka data squense 6.7
     * 
     * @return string
     */
    public function getPaymentsSummary() {
        $result = '';
        $query = "SELECT * from `payments` WHERE  `summ`>0;";
        $allPayments = simple_queryall($query);
        if (!empty($allPayments)) {
            foreach ($allPayments as $io => $each) {
                $userLogin = $each['login'];
                //no export payments for users that not exists anymore
                if (isset($this->allUsersData[$userLogin])) {
                    $userData = $this->allUsersData[$userLogin];
                    $dataTmp = array(
                        $this->getUserBranchId($userLogin), //user branch ID
                        $each['cashtypeid'], //cash type id
                        $userData['contract'], //user contract number
                        $userData['ip'], //user IP address
                        $this->formatDate($each['date']), //payment date
                        $each['summ'], // payment sum
                        $each['note'], // payment notes
                    );
                    $result.= $this->arrayToCsv($dataTmp, self::DELIMITER, self::ENCLOSURE, true) . PHP_EOL;
                }
            }
        }
        return ($result);
    }

}

?>