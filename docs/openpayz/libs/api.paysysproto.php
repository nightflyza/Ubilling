<?php

/**
 * Fronted paysys class to be inherited
 * Encapsulates all of the commonly used methods and props
 */
class PaySysProto {
    /**
     * Predefined stuff
     * Just as an example - override this in your paysys class, if needed
     */
    const PATH_CONFIG       = 'config/config.ini';
    const PATH_AGENTCODES   = 'config/agentcodes_mapping.ini';
    const PATH_TRANSACTS    = 'tmp/';
    const OP_TRANSACT_TABLE = 'op_transactions';

    /**
     * Paysys specific predefines.
     * Just as an example - OVERRIDE THIS IN YOUR PAYSYS CLASS
     */
    const HASH_PREFIX       = 'PAYSYS_HASH_';
    const PAYSYS            = 'PAYSYS_NAME';

    /**
     * Instance configuration as key => value
     *
     * @var array
     */
    protected $config = array();

    /**
     * Agent codes using flag
     *
     * @var bool
     */
    protected $agentcodesON = false;

    /**
     * Non strict agent codes using flag
     *
     * @var bool
     */
    protected $agentcodesNonStrict = false;

    /**
     * Contains values from agentcodes_mapping.ini
     *
     * @var array
     */
    protected $agentcodesMapping = array();

    /**
     * Placeholder for a DISABLE_AGENTCODES_MAPPING_FILE config option
     *
     * @var bool
     */
    protected $agentcodesMappingDisable = false;

    /**
     * Placeholder for a "last resort" config option DEFAULT_AGENT_CODE
     *
     * @var int
     */
    protected $agentcodeDefault = 1;

    /**
     * Contains data of UB agent the user is assigned to
     *
     * @var array
     */
    protected $agentData = array();

    /**
     * Contains ID of UB agent the user is assigned to or zero if no assignment found
     *
     * @var array
     */
    protected $agentID = 0;

    /**
     * Placeholder for UB API URL
     *
     * @var string
     */
    protected $ubapiURL = '';

    /**
     * Placeholder for UB API key
     *
     * @var string
     */
    protected $ubapiKey = '';

    /**
     * Placeholder for CITY_DISPLAY_IN_ADDRESS config option
     *
     * @var bool
     */
    protected $addressCityDisplay = false;


    /**
     * Preloads all required configurations, sets needed object properties
     *
     * @return void
     */
    public function __construct($config_path = self::PATH_CONFIG) {
        $this->loadConfig($config_path);
    }

    /**
     * Loads frontend configuration in protected prop
     *
     * @return void
     */
    protected function loadConfig($config_path = self::PATH_CONFIG) {
        if (file_exists($config_path)) {
            $this->config = parse_ini_file($config_path);
        } else {
            $this->replyError(500, 'Fatal error: config file ' . $config_path . ' not found!');
        }
    }

    /**
     * Sets object properties based on frontend config
     *
     * @return void
     */
    protected function setOptions() {
        if (!empty($this->config)) {
            $this->agentcodesON             = isset($this->config['USE_AGENTCODES']) ? $this->config['USE_AGENTCODES'] : false;
            $this->agentcodesMappingDisable = isset($this->config['DISABLE_AGENTCODES_MAPPING_FILE']) ? $this->config['DISABLE_AGENTCODES_MAPPING_FILE'] : false;
            $this->agentcodesNonStrict      = isset($this->config['NON_STRICT_AGENTCODES']) ? $this->config['NON_STRICT_AGENTCODES'] : false;
            $this->agentcodeDefault         = isset($this->config['DEFAULT_AGENT_CODE']) ? $this->config['DEFAULT_AGENT_CODE'] : 1;
            $this->ubapiURL                 = isset($this->config['UBAPI_URL']) ? $this->config['UBAPI_URL'] : '';
            $this->ubapiKey                 = isset($this->config['UBAPI_KEY']) ? $this->config['UBAPI_KEY'] : '';
            $this->addressCityDisplay       = isset($this->config['CITY_DISPLAY_IN_ADDRESS']) ? $this->config['CITY_DISPLAY_IN_ADDRESS'] : false;
        } else {
            $this->replyError(500, 'Fatal: config is empty!');
        }
    }

    /**
     * Loads frontend agentcodes_mapping.ini in protected prop
     *
     * @return void
     */
    protected function loadAgentCodesMapping($agent_codes_path = self::PATH_AGENTCODES) {
        if ($this->agentcodesON and !$this->agentcodesMappingDisable) {
            if (file_exists($agent_codes_path)) {
                $this->agentcodesMapping = parse_ini_file($agent_codes_path);
            } else {
                $this->replyError(500, 'Fatal error: agentcodes_mapping.ini file ' . $agent_codes_path . ' not found!');
            }
        }
    }

    /**
     * Gets user associated agent data JSON
     *
     * @param string $userLogin
     *
     * @return string
     */
    protected function getUBAgentData($userLogin) {
        $action = $this->ubapiURL . '?module=remoteapi&key=' . $this->ubapiKey . '&action=getagentdata&param=' . $userLogin;
        @$result = file_get_contents($action);

        if (empty($result)) {
            $result = array();
        } else {
            $result = json_decode($result, true);
        }

        $this->agentData = $result;
        return ($result);
    }

    /**
     * Returns user's assigned agent extended data, if available
     *
     * @param $gentID
     *
     * @return array|empty
     */
    protected function getUBAgentDataExten($agentID, $paysysName = '') {
        $result     = array();
        $whereStr   = (empty($paysysName) ? '' : ' and `internal_paysys_name` = "' . $paysysName . '"');

        if (!empty($agentID)) {
            $query       = 'select * from `contrahens_extinfo` where `agentid` = ' . $agentID . $whereStr;
            $queryResult = simple_queryall($query);

            $result = (empty($queryResult) ? array() : $queryResult);
        }

        return ($result);
    }

    /**
     * Returns user's assigned agent ID, if available
     *
     * @param $userLogin
     *
     * @return int|mixed|string
     */
    protected function getUBAgentAssignedID($userLogin) {
        if (empty($this->agentID)) {
            $agentData      = (empty($this->agentData) ? $this->getUBAgentData($userLogin) : $this->agentData);
            $this->agentID  = (empty($agentData['id'])
                                ? (empty($this->agentcodeDefault)
                                    ? 0 : (empty($this->agentcodesNonStrict)
                                        ? 0 : $this->agentcodeDefault)) : $agentData['id']);
        }

        return ($this->agentID);
    }

    /**
     * Validates Paysys Service/Merchant/Cashbox/etc ID and Ubilling agent ID correlation
     *
     * @param $userLogin
     *
     * @return bool
     */
    protected function checkUserAgentAssignment($userLogin, $paysysIDToCheck) {
        $result  = false;
        // get current subscriber agent ID
        $agentID = $this->getUBAgentAssignedID($userLogin);

        if (!empty($agentID)) {
            // get Service ID to Ubilling agent code mapping, if exists
            $mappedAgentBySrvID = (empty($this->agentcodesMapping[$paysysIDToCheck]) ? 'n0ne' : $this->agentcodesMapping[$paysysIDToCheck]);
            // compare the IDs
            $result  = ($agentID == $mappedAgentBySrvID);
        }

        // if $result is false and $this->agentcodesNonStrict is ON - make $result true
        $result = ((!$result and $this->agentcodesNonStrict) ? true : $result);
        return ($result);
    }

    /**
     * Tries to get Paysys Service/Merchant/Cashbox/etc ID by Ubilling agent ID from agentcodes_mapping.ini
     *
     * @param $userLogin
     *
     * @return mixed|string
     */
    protected function getPaySysIDToAgentAssigned($userLogin) {
        $paysysIDToGet = '';
        $agentcodesMappingReversed = array_flip($this->agentcodesMapping);
        $agentID = $this->getUBAgentAssignedID($userLogin);

        if (!empty($agentID)) {
            // get Ubilling agent code to Cashbox mapping, if exists
            $paysysIDToGet = (empty($agentcodesMappingReversed[$agentID]) ? '' : $agentcodesMappingReversed[$agentID]);
        }

        // if no mapped Service/Merchant/Cashbox/etc ID found or user does not have UB agent assigned
        // and $this->agentcodesNonStrict is ON - proceed with default UB agent ID
        // and a Service/Merchant/Cashbox/etc ID mapped to it
        // if no default UB agent ID is set - user not found error should be returned.
        if (empty($paysysIDToGet) and $this->agentcodesNonStrict and !empty($this->agentcodeDefault)) {
            $paysysIDToGet = (empty($agentcodesMappingReversed[$this->agentcodeDefault]) ? '' : $agentcodesMappingReversed[$this->agentcodeDefault]);
        }

        return ($paysysIDToGet);
    }

    /**
     * Returns user stargazer data by login
     *
     * @param string $userLogin existing stargazer login
     *
     * @return array
     */
    protected function getUserStargazerData($userLogin) {
        $userLogin = mysql_real_escape_string($userLogin);
        $query     = "SELECT * from `users` WHERE `login`='" . $userLogin . "';";
        $result    = simple_query($query);
        return ($result);
    }

    /**
     * Returns array of available or filtered by user login RealNames as login => realname
     *
     * @param string $userLogin
     *
     * @return array
     */
    protected function getUserRealnames($userLogin = '') {
        $result = array();
        $whereStr = (empty($userLogin) ? '' : " WHERE `login` = '" . $userLogin . "'");

        $query = "SELECT * from `realname`" . $whereStr;
        $realnames = simple_queryall($query);

        if (!empty($realnames)) {
            foreach ($realnames as $io => $each) {
                $result[$each['login']] = $each['realname'];
            }
        }

        $result = (empty($userLogin) ? $result : $result[$userLogin]);
        return($result);
    }

    /**
     * Returns array of available or filtered by user login addresses as login => address
     *
     * @param string $userLogin
     *
     * @return array|string
     */
    protected function getUserAddresses($userLogin = '') {
        $result = array();
        $whereStr = (empty($userLogin) ? '' : " WHERE `address`.`login` = '" . $userLogin . "'");

        $query = "
            SELECT `address`.`login`,`city`.`cityname`,`street`.`streetname`,`build`.`buildnum`,`apt`.`apt` 
                FROM `address`
                    INNER JOIN `apt` ON `address`.`aptid`= `apt`.`id`
                    INNER JOIN `build` ON `apt`.`buildid`=`build`.`id`
                    INNER JOIN `street` ON `build`.`streetid`=`street`.`id`
                    INNER JOIN `city` ON `street`.`cityid`=`city`.`id`"
                 . $whereStr;

        $addresses = simple_queryall($query);

        if (!empty($addresses)) {
            foreach ($addresses as $eachAddress) {
                // zero apt handle
                $apartment_filtered = ($eachAddress['apt'] == 0) ? '' : '/' . $eachAddress['apt'];

                if ($this->addressCityDisplay) {
                    $result[$eachAddress['login']] = $eachAddress['cityname'] . ' ' . $eachAddress['streetname'] . ' ' . $eachAddress['buildnum'] . $apartment_filtered;
                } else {
                    $result[$eachAddress['login']] = $eachAddress['streetname'] . ' ' . $eachAddress['buildnum'] . $apartment_filtered;
                }
            }
        }

        $result = (empty($userLogin) ? $result : $result[$userLogin]);
        return($result);
    }

    /**
     * Returns array of user's cellphones
     *
     * @param $userLogin
     * @param $includeExtMobiles
     *
     * @return array
     */
    protected function getUserCellPhone($userLogin = '', $includeExtMobiles = false) {
        $result = array();
        $query = 'select `login`, `mobile` from `phones`';
        $whereStr = (empty($userLogin) ? '' : ' where `login` = "' . $userLogin . '"');

        if ($includeExtMobiles) {
            $query = 'select `phones`.`login`, `phones`.`mobile`, `mobileext`.`mobile` as `extmobile` ' .
                     'from `phones` ' .
                     'left join `mobileext` on `phones`.`login` = `mobileext`.`login` ';
        }

        $query.= $whereStr;
        $queryResult = simple_queryall($query);

        if (!empty($queryResult)) {
            foreach ($queryResult as $io => $eachRec) {
                if (!empty($eachRec['mobile'])) {
                    $result[$eachRec['login']][] = $eachRec['mobile'];
                }

                if (!empty($eachRec['extmobile'])) {
                    $result[$eachRec['login']][] = $eachRec['extmobile'];
                }
            }
        }

        return($result);
    }

    /**
     * Returns all tariff prices array
     *
     * @return array
     */
    protected function getTariffPriceAll($userTariffName = '') {
        $whereStr = (empty($userTariffName) ? '' : ' where `name` = "' . $userTariffName .'"');
        $query = 'select `name`, `Fee` from `tariffs`' . $whereStr;
        $queryResult = simple_queryall($query);
        $result = array();

        if (!empty($queryResult)) {
            foreach ($queryResult as $io => $eachTariff) {
                $result[$eachTariff['name']] = $eachTariff['Fee'];
            }
        }

        return ($result);
    }

    /**
     * Returns transaction data from "op_transactions", if it exists
     * May be used for checking transaction hash for duplicates
     *
     * @param string $transactHash
     *
     * @return array
     */
    protected function getOPTransactDataByHash($transactHash) {
        $result = array();

        if (!empty($transactHash)) {
            $transactData = simple_query('select * from `' . self::OP_TRANSACT_TABLE . '` where `hash` = "' . $transactHash . '"');

            if (!empty($transactData)) {
                $result = $transactData;
            }
        }

        return($result);
    }

    /**
     * Checks is transaction already exists or not?
     *
     * @param string $transactID
     *
     * @return bool
     */
    protected function checkTransactFileExists($transactID, $transactDirectory = self::PATH_TRANSACTS) {
        $result = (!empty($transactID) and file_exists(rtrim($transactDirectory, '/') . '/' . $transactID));
        return($result);
    }

    /**
     * Returns transaction data by $transactID
     *
     * @param $transactID
     *
     * @return mixed|string
     */
    protected function getTransactFileData($transactID, $transactDirectory = self::PATH_TRANSACTS) {
        $result = '';

        if ($this->checkTransactFileExists($transactID, $transactDirectory = self::PATH_TRANSACTS)) {
            $result = unserialize(file_get_contents(rtrim($transactDirectory, '/') . '/' . $transactID));
        }

        return($result);
    }

    /**
     * Saves serialized transaction id to a specified directory
     *
     * @param string $transactID
     *
     * @return string
     */
    protected function saveTransactFile($transactID, $transactData, $transactDirectory = self::PATH_TRANSACTS) {
        $result = file_put_contents(rtrim($transactDirectory, '/') . '/' . $transactID, serialize($transactData));
        return($result);
    }

    /**
     * Returns transaction data from DB by $transactID
     *
     * @param string $transactID
     * @param string $tableName
     * @param string $searchFieldName
     *
     * @return mixed|string
     */
    protected function getTransactDataDB($transactID, $tableName, $searchFieldName) {
        $result = '';
        $tQuery = "SELECT * FROM `' . $tableName . '` WHERE `' . $searchFieldName . '` = '" . $transactID . "' ";
        $result = simple_query($tQuery);

        return($result);
    }

    /**
     * Saves transaction data to a dedicated for a certain paysys DB table
     *
     * NEEDS TO BE OVERRIDDEN
     *
     * @return void
     */
    protected function saveTransactDataDB($tableName = '', $transactData = '') {
        //todo: place your transaction to DB saving code here
    }

    /**
     * Returns current UNIX timestamp in milliseconds (13 digits)
     *
     * @return int
     */
    public static function getUnixTimestampMillisec() {
        $now    = DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''));
        $now_ms = (int)$now->format('Uv');
        return ($now_ms);
    }

    /**
     * Checks if payment sum is a number with no more than $maxDecimals decimal places
     * and with a dot as decimal delimiter
     *
     * @param $paysum
     *
     * @return bool
     */
    public static function checkPaySumCorrect($paysum, $maxDecimals = 2) {
        $paysum = str_ireplace(array('"', "'"), '', trim($paysum));
        $regex = '/^\d+(\.[0-9]{1,' . $maxDecimals . '}(0*))?$/';
        return (preg_match($regex, $paysum) != 1);
    }

    /**
     * Returns random numeric string, which will be used as unique transaction hash
     *
     * @param int $size
     *
     * @return string
     */
    public static function genRandNumString($size = 12) {
        $characters = '0123456789';
        $string = "";

        for ($p = 0; $p < $size; $p++) {
            $string.= $characters[mt_rand(0, (strlen($characters) - 1))];
        }

        return ($string);
    }

    /**
     * Encode a string with URL-safe Base64.
     *
     * @param string $input The string you want encoded
     *
     * @return string The base64 encode of what you passed in
     */
    public static function urlSafeBase64Encode($input) {
        return (str_replace('=', '', strtr(base64_encode($input), '+/', '-_')));
    }

    /**
     * Decode a string with URL-safe Base64.
     *
     * @param string $input A Base64 encoded string
     *
     * @return string A decoded string
     */
    public static function urlSafeBase64Decode($input) {
        $remainder = strlen($input) % 4;

        if ($remainder) {
            $padlen = 4 - $remainder;
            $input.= str_repeat('=', $padlen);
        }

        return (base64_decode(strtr($input, '-_', '+/')));
    }

    /**
     * Intended to spit out erroneous replies
     *
     * MIGHT BE OVERRIDDEN
     *
     * @param $errorCode
     * @param $errorMsg
     *
     * @return false|string|void
     */
    protected function replyError($errorCode = '', $errorMsg = '') {
        //todo: override with your error replying code here, if needed
        header('HTTP/1.1 ' . $errorCode  . ' ' . $errorMsg . '"', true, $errorCode);
        die($errorCode . ' - ' . $errorMsg);
    }

    /**
     * Requests processing routine
     *
     * NEEDS TO BE OVERRIDDEN
     *
     * @return void
     */
    protected function processRequests() {
        // todo: Your requests processing code here
    }

    /**
     * Listen to your heart when he's calling for you
     * Listen to your heart, there's nothing else you can do
     *
     * NEEDS TO BE OVERRIDDEN
     *
     * @return void
     */
    protected function listen() {
        //todo: Place your "listening" code here
    }
}