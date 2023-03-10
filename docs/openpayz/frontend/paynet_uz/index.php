<?php

/**
 * Paynet.UZ API frontend for OpenPayz
 */

// подключаем API OpenPayz
include ("../../libs/api.openpayz.php");

class PaynetUZ {
    /**
     * Predefined stuff
     */
    const PATH_CONFIG       = 'config/paynetuz.ini';
    const PATH_AGENTCODES   = 'config/agentcodes_mapping.ini';

    /**
     * Paysys specific predefines
     */
    const HASH_PREFIX = 'PAYNET_UZ_';
    const PAYSYS = 'PAYNET_UZ';

    /** Transaction expiration time in milliseconds. 43 200 000 ms = 12 hours. */
    const EXPIRE_TIMEOUT = 43200000;

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
     * Default UB agent code from config/paynetuz.ini
     *
     * @var
     */
    protected $agentcodeDefault = '';

    /**
     * Merchant login from PaynetUZ
     *
     * @var string
     */
    protected $paynetLogin = '';

    /**
     * Merchant cashbox password from PaynetUZ
     * https://developer.help.paycom.uz/ru/poisk-klyucha-i-id-kassy-v-lichnom-kabinete/poisk-klyucha-parolya-ot-kassy
     *
     * @var string
     */
    protected $paynetPassword = '';

    /**
     * Request ID from Paynet
     *
     * @var string
     */
    protected $paynetRequestID = null;

    /**
     * Service ID from Paynet
     *
     * @var string
     */
    protected $paynetServiceID = null;

    /**
     * Placeholder for PaynetUZ transaction ID value
     *
     * @var string
     */
    protected $paynetTransactID = '';

    /**
     * Placeholder for PaynetUZ cashbox ID
     * 
     * @var string 
     */
    protected $paynetCashBoxID = '';

    /**
     * Placeholder for DEFAULT_CASHBOX_ID option
     *
     * @var string
     */
    protected $defaultCashBoxID = '';
    
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
     * Instance configuration as key=>value
     *
     * @var array
     */
    protected $config = array();

    /**
     * Payment sum from request
     *
     * @var int
     */
    protected $paymentSum = 0;

    /**
     * Placeholder for a payment method JSON property
     *
     * @var string
     */
    protected $paymentMethod = '';

    /**
     * Placeholder for available payment methods
     *
     * @var array
     */
    protected $paymentMethodsAvailable = array('GetInformation', 'PerformTransaction', 'CheckTransaction', 'CancelTransaction', 'GetStatement');

    /**
     * Placeholder for PaynetUZ customer ID value
     *
     * @var string
     */
    protected $customerID = '';

    /**
     *
     * @var
     */
    protected $customerIDFieldName = '';

    /**
     * Contains received by listener preprocessed request data
     *
     * @var array
     */
    protected $receivedJSON = array();

    /**
     * Contains all existent op_customers as virtualid => realid(login) mapping
     *
     * @var array
     */
    protected $opCustomersAll = array();

    /**
     * Placeholder for OP customer login
     *
     * @var string
     */
    protected $userLogin = '';

    /**
     * Placeholder for error codes and their descr
     *
     * @var string
     */
    protected $errorCodes = array('-32300' => array('ru' => 'Метод запроса не POST', 'uz' => 'Метод запроса не POST', 'en' => 'Not a POST method request'),
                                  '-32700' => array('ru' => 'Ошибка парсинга JSON', 'uz' => 'JSON парсинг хатоси', 'en' => 'JSON parse error'),
                                  '-32600' => array('ru' => 'Отсутствуют обязательные поля в RPC-запросе или тип полей не соответствует спецификации.', 'uz' => 'RPC сўровида мажбутий майдонлар йўқ ёки майдон тури спецификацияга мос келмайди.', 'en' => 'Missing required fields in RPC request or fields types does not correspond to specs'),
                                  '-32601' => array('ru' => 'Запрашиваемый метод не найден', 'uz' => 'Сўралган усул топилмади', 'en' => 'Requested method not found'),
                                  '-32602' => array('ru' => 'Отсутствуют обязательные поля параметров', 'uz' => 'Отсутствуют обязательные поля параметров.', 'en' => 'Mandatory parameters fields missing'),
                                  '0'      => array('ru' => 'Проведено успешно', 'uz' => 'Проведено успешно', 'en' => 'Transaction successful'),
                                  '77'     => array('ru' => 'Невозможно отменить транзакцию. Услуга предоставлена потребителю в полном объеме.', 'uz' => 'Транзакцияни бекор қилиб бўлмайди. Хизмат истеъмолчига тўлиқ ҳажмда тақдим этилди', 'en' => 'Transaction can not be canceled. The service is provided to the consumer in full amount'),
                                  '100'    => array('ru' => 'Услуга временно не поддерживается', 'uz' => 'Услуга временно не поддерживается', 'en' => 'Service temporary unsupported'),
                                  '101'    => array('ru' => 'Квота исчерпана', 'uz' => 'Квота исчерпана', 'en' => 'Quota exhausted'),
                                  '102'    => array('ru' => 'Системная ошибка', 'uz' => 'Системная ошибка', 'en' => 'System error'),
                                  '103'    => array('ru' => 'Неизвестная ошибка', 'uz' => 'Неизвестная ошибка', 'en' => 'Unknown error'),
                                  '201'    => array('ru' => 'Транзакция с таким ID уже существует', 'uz' => 'Бундай ID билан транзакция мавжуд', 'en' => 'Transaction with such ID already exists'),
                                  '202'    => array('ru' => 'Транзакция уже отменена', 'uz' => 'Транзакция уже отменена', 'en' => 'Transaction is canceled already'),
                                  '203'    => array('ru' => 'Транзакция не найдена', 'uz' => 'Транзакция топилмади', 'en' => 'Transaction not found'),
                                  '301'    => array('ru' => 'Номер телефона не найден', 'uz' => 'Телефон рақами топилмади', 'en' => 'Phone number not found'),
                                  '302'    => array('ru' => 'Лицевой счёт не найден', 'uz' => 'Шаҳсий ҳисоб топилмади', 'en' => 'Customer ID not found'),
                                  '411'    => array('ru' => 'Не заданы один или несколько обязательных параметров', 'uz' => 'Не заданы один или несколько обязательных параметров', 'en' => 'Some mandatory parameters missing'),
                                  '412'    => array('ru' => 'Неверный логин или пароль', 'uz' => 'Неверный логин или пароль', 'en' => 'Login or password incorrect'),
                                  '413'    => array('ru' => 'Неверная сумма платежа', 'uz' => 'Тўлов миқдори нотўғри', 'en' => 'Incorrect payment amount'),
                                  '501'    => array('ru' => 'Недостаточно привилегий для выполнения метода', 'uz' => 'Усулни бажариш учун етарли ваколат йўқ', 'en' => 'Not enough privileges to perform request'),
                                  '603'    => array('ru' => 'Невозможно выполнить операцию', 'uz' => 'Операцияни амалга ошириб бўлмайди', 'en' => 'Operation can not be performed'),
                                  '-31052' => array('ru' => 'Платёж с таким ID уже успешно проведён на стороне провайдера', 'uz' => 'Бундай ID билан тўлов провайдер томонидан муваффақиятли амалга оширилди', 'en' => 'Payment with this ID has already been successfully completed on the ISP side already')
                                  );

    /**
     * Preloads all required configuration, sets needed object properties
     *
     * @return void
     */
    public function __construct() {
        // fallback for getallheaders(), e.g. for nginx
        if (!function_exists('getallheaders')) {
            function getallheaders() {
                $headers = '';
                foreach ($_SERVER as $name => $value) {
                    if (substr($name, 0, 5) == 'HTTP_') {
                        $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                    }
                }
                return $headers;
            }
        }

        $this->loadConfig();
        $this->setOptions();
        $this->loadACMapping();
    }

    /**
     * Loads frontend configuration in protected prop
     *
     * @return void
     */
    protected function loadConfig() {
        if (file_exists(self::PATH_CONFIG)) {
            $this->config = parse_ini_file(self::PATH_CONFIG);
        } else {
            die('Fatal error: config file ' . self::PATH_CONFIG . ' not found!');
        }
    }

    /**
     * Loads frontend agentcodes_mapping.ini in protected prop
     *
     * @return void
     */
    protected function loadACMapping() {
        if ($this->agentcodesON) {
            if (file_exists(self::PATH_AGENTCODES)) {
                $this->agentcodesMapping = parse_ini_file(self::PATH_AGENTCODES);
            } else {
                die('Fatal error: agentcodes_mapping.ini file ' . self::PATH_AGENTCODES . ' not found!');
            }
        }
    }

    /**
     * Sets object properties based on frontend config
     *
     * @return void
     */
    protected function setOptions() {
        if (!empty($this->config)) {
            $this->agentcodesON        = $this->config['USE_AGENTCODES'];
            $this->agentcodesNonStrict = $this->config['NON_STRICT_AGENTCODES'];
            $this->agentcodeDefault    = $this->config['DEFAULT_AGENTCODE'];
            $this->defaultCashBoxID    = $this->config['DEFAULT_CASHBOX_ID'];
            $this->customerIDFieldName = $this->config['CUSTOMERID_FIELD_NAME'];
            $this->paynetLogin          = (empty($this->config['LOGIN']) ? '' : $this->config['LOGIN']);
            $this->paynetPassword       = (empty($this->config['PASSWORD']) ? '' : $this->config['PASSWORD']);
            $this->ubapiURL            = $this->config['UBAPI_URL'];
            $this->ubapiKey            = $this->config['UBAPI_KEY'];
            $this->addressCityDisplay  = $this->config['CITY_DISPLAY_IN_ADDRESS'];
        } else {
            die('Fatal: config is empty!');
        }
    }

    /**
     * Returns current UNIX timestamp in milliseconds (13 digits)
     *
     * @return int
     */
    protected function getUnixTimestampMillisec() {
        $now    = DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''));
        $now_ms = (int)$now->format('Uv');
        return ($now_ms);
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
        return ($result);
    }

    /**
     * Validates PaynetUZ service ID and Ubilling agent ID correlation
     *
     * @param $userLogin
     *
     * @return bool
     */
    protected function getCashBoxIDAgentAssigned($userLogin) {
        $cashboxID = '';
        $agentData = json_decode($this->getUBAgentData($userLogin), true);

        if (!empty($agentData['id'])) {
            $agentID = $agentData['id'];
            // get Ubilling agent code to Cashbox mapping, if exists
            $cashboxID = (empty($this->agentcodesMapping[$agentID]) ? '' : $this->agentcodesMapping[$agentID]);
        }

        // if no mapped cashbox ID found or user does not have UB agent assigned
        // and $this->agentcodesNonStrict is ON - proceed with default UB agent
        // and a cashbox ID mapped to it or use DEFAULT_CASHBOX_ID.
        // if no default cashbox ID is set - user not found error will be returned.
        if (empty($cashboxID) and $this->agentcodesNonStrict and !empty($this->agentcodeDefault)) {
            $cashboxID = (empty($this->agentcodesMapping[$this->agentcodeDefault]) ? $this->defaultCashBoxID : $this->agentcodesMapping[$this->agentcodeDefault]);
        }

        return ($cashboxID);
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
     * Returns all user RealNames
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
     * Returns array of available or filtered by user login address as login => address
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
     * Check transaction hash for duplicates by returning transaction data if it exists
     *
     * @param string $transactHash
     *
     * @return array
     */
    protected function getOPHashData($transactHash) {
        $result = array();

        if (!empty($transactHash)) {
            $transactData = simple_query("SELECT * from `op_transactions` WHERE `hash`='" . $transactHash . "'");

            if (!empty($transactData)) {
                $result = $transactData;
            }
        }

        return($result);
    }

    /**
     * Tries to get customer ID from existing transaction
     *
     * @param $transactID
     *
     * @return mixed|string
     */
    protected function getCustomerIDFromSavedTransact($transactID) {
        $transactData = $this->getTransactionData($transactID);
        $result = (empty($transactData['op_customer_id']) ? '' : $transactData['op_customer_id']);
        return ($result);
    }

    /**
     * Returns transaction data by $transactID
     *
     * @param string $transactID
     *
     * @return mixed|string
     */
    protected function getTransactionData($transactID) {
        $result = '';
        $tQuery = "SELECT * FROM `paynetuz_transactions` WHERE `transact_id` ='" . $transactID . "' ";
        $result = simple_query($tQuery);

        return($result);
    }

    /**
     * Returns transaction data by $transactID
     *
     * @param int $fromTimeStamp
     * @param int $toTimestamp
     *
     * @return mixed|string
     */
    protected function getTransactionsDataAll($fromTimeStamp = 0, $toTimestamp = 0) {
        $result     = '';
        $whereStr   = " WHERE ";
        $whereFrom  = (empty($fromTimeStamp) ? "" : " `paynet_transact_timestamp` >= '" . $fromTimeStamp . "'");
        $whereTo    = (empty($toTimeStamp) ? "" : " `paynet_transact_timestamp` <= '" . $toTimeStamp . "'");

        if (empty($whereFrom) and empty($whereTo)) {
            $whereStr = '';
        } elseif (!empty($whereFrom) and empty($whereTo)) {
            $whereStr.= $whereFrom;
        } elseif (!empty($whereFrom) and !empty($whereTo)) {
            $whereStr.= $whereFrom . " and " . $whereTo;
        } elseif (empty($whereFrom) and !empty($whereTo)) {
            $whereStr.= $whereTo;
        }

        $orderBy    = (!empty($whereStr) ? " ORDER BY `paynet_transact_timestamp` ASC " : "");
        $tQuery     = "SELECT * FROM `paynetuz_transactions` " . $whereStr . $orderBy;
        $result     = simple_queryall($tQuery);

        return($result);
    }
    /**
     * Checks if transaction already exists and returns it's state
     *
     * @param string $transactID
     *
     * @return bool
     */
    protected function checkTransactionExists($transactID) {
        $transactState = 0;
        $tQuery = "SELECT `transact_id`, `state` FROM `paynetuz_transactions` WHERE `transact_id` ='" . $transactID . "'";
        $result = simple_query($tQuery);
        $transactState = (empty($result['state']) ? 0 : $result['state']);
        return($transactState);
    }

    /**
     * Returns transaction expired status: true - expired, false - not expired
     *
     * @param $transactData
     *
     * @return bool
     */
    protected function checkTransactionExpired($transactData) {
        //$curTimestamp = $this->getUnixTimestampMillisec();
        $curTimestamp = curdatetime();
        $transactCreateTimestamp = strtotime($transactData['create_timestamp']);
        $result = (($curTimestamp - $transactCreateTimestamp) > self::EXPIRE_TIMEOUT);
//file_put_contents('tmstmps', "curTimestamp:  $curTimestamp\n");
//file_put_contents('tmstmps', "transactCreateTimestamp:  $transactCreateTimestamp\n", 8);
//file_put_contents('tmstmps', "curTimestamp - transactCreateTimestamp:  $curTimestamp - $transactCreateTimestamp\n", 8);
//file_put_contents('tmstmps', "result:  $result\n", 8);
        return ($result);
    }

    /**
     * Transaction creation in DB implementation
     *
     * @return string|void
     */
    protected function createTransaction() {
        $reply = '';
        $opTransactTimestamp = '';
        $transactState = $this->checkTransactionExists($this->paynetTransactID);

        if (empty($transactState)) {
            $transactTimestamp  = $this->receivedJSON['params']['transactionTime'];
            $opTransactID       = self::HASH_PREFIX . $this->paynetTransactID;

            if ($this->paynetCashBoxID == $this->defaultCashBoxID) {
                // we don't want to send money from a certain cashbox to itself
                $transactReceivers = null;
            } else {
                $transactReceivers = array(array('id' => $this->paynetCashBoxID, 'amount' => $this->paymentSum));
            }

            $opTransactTimestamp = $this->saveTransaction($this->paynetTransactID, $opTransactID, $this->customerID,
                                                          $this->paymentSum, $transactTimestamp, json_encode($transactReceivers));
        } else {
            if ($transactState == 8) {
                $transactData = $this->getTransactionData($this->paynetTransactID);

                if ($this->checkTransactionExpired($transactData)) {
                    $this->markTransactAsCanceled($this->paynetTransactID, 4);
                    $reply = $this->replyError('603');
                } /*else {
                    $receivers = json_decode($transactData['receivers'], true);
                    $reply = array('result' => array('create_time'  => (int)$transactData['create_timestamp'],
                                                     'transaction'  => $transactData['op_transact_id'],
                                                     'state'        => (int)$transactData['state'],
                                                     'receivers'    => ((empty($receivers) or trim($receivers) == 'null' or trim($receivers) == 'NULL')
                                                                        ? null : $receivers)
                                                    ),
                                   'id' => $this->paynetRequestID,
                                   'jsonrpc' => '2.0'
                             );
                    $reply = json_encode($reply);
                }*/
            } elseif ($transactState == 1) {
                // transaction with such ID already exists
                $reply = $this->replyError('201');
            } elseif ($transactState == 2) {
                $reply = $this->replyError('202');
            } else {
                $reply = $this->replyError('603');
            }
        }

        if (!empty($reply)) {
            die($reply);
        } else {
            return ($opTransactTimestamp);
        }
    }

    /**
     * Saves transaction id to validate some possible duplicates
     *
     * @param string $transactID
     * @param int $transactTimestamp
     * @param int $transactAmount
     * @param string $opTransactID
     * @param string $opCustomerID
     * @param string $transactReceivers
     *
     * @return string
     */
    protected function saveTransaction($transactID, $opTransactID, $opCustomerID, $transactAmount, $transactTimestamp, $transactReceivers) {
        $transactState = 8;     //dirty hack for initial transaction state, as this paysys doesn't support that natively
        $opTransactDTCreate  = curdatetime();
        //$opTransactTimestamp = $this->getUnixTimestampMillisec();
        $opTransactTimestamp = curdatetime();

        $tQuery = "INSERT INTO `paynetuz_transactions` (`date_create`, `transact_id`, `op_transact_id`, `op_customer_id`,
                                                       `amount`, `state`, `paynet_transact_timestamp`, `create_timestamp`, `receivers`) 
                          VALUES ('" . $opTransactDTCreate . "', '" . $transactID . "', '" . $opTransactID . "', '" . $opCustomerID
                                  . "', " . $transactAmount . ", " . $transactState . ", '" . $transactTimestamp . "', '" . $opTransactTimestamp
                                  . "', '" . $transactReceivers . "')";

        nr_query($tQuery);
        return($opTransactTimestamp);
    }

    /**
     * Marks saved earlier transaction as paid and returns $payTimeStamp
     *
     * @param string $transactID
     *
     * @return int
     */
    protected function markTransactAsPaid($transactID) {
        //$payTimeStamp = $this->getUnixTimestampMillisec();
        $payTimeStamp = curdatetime();
        $tQuery = "UPDATE `paynetuz_transactions` SET `state` = 1, `perform_timestamp` = '" . $payTimeStamp . "' WHERE `transact_id` ='" . $transactID . "'";
        nr_query($tQuery);
        return($payTimeStamp);
    }

    /**
     * Marks saved earlier and not yet paid transaction as canceled and returns $cancelTimeStamp
     *
     * @param string $transactID
     * @param string $cancelReason
     *
     * @return int
     */
    protected function markTransactAsCanceled($transactID, $cancelReason = '') {
        //$cancelTimeStamp = $this->getUnixTimestampMillisec();
        $cancelTimeStamp = curdatetime();
        $tQuery = "UPDATE `paynetuz_transactions` SET `state` = 2, `cancel_timestamp` = '" . $cancelTimeStamp . "', `cancel_reason` = '" . $cancelReason . "' WHERE `transact_id` ='" . $transactID . "'";
        nr_query($tQuery);
        return ($cancelTimeStamp);
    }

    /**
     * Sets HTTP headers before reply
     */
    protected function setHTTPHeaders() {
        header('Content-Type: application/json; charset=UTF-8');
    }

    /**
     * Checks request auth and returns result
     * implementation is adopted from:
     * https://github.com/PaycomUZ/paycom-integration-php-template/blob/master/Paycom/Merchant.php
     *
     * @return bool
     */
    protected function checkAuth() {
        $result  = false;
        $headers = getallheaders();

        if (!empty($headers['Authorization'])) {
            preg_match('/^\s*Basic\s+(\S+)\s*$/i', $headers['Authorization'], $matches);
            $decodedAuth = base64_decode($matches[1]);
            $result = ($decodedAuth == $this->paynetLogin . ":" . $this->paynetPassword);
        }

        return ($result);
    }

    /**
     * "GetInformation" request reply implementation
     */
    protected function replyPaymentAbilityCheck() {
        $reply = '';
        $userData = $this->getUserStargazerData($this->userLogin);

        if (empty($userData)) {
            $reply = $this->replyError('302');
        } else {
            $userBalance    = $userData['Cash'];
            $userRealName   = $this->getUserRealnames($this->userLogin);
            $userAddress    = $this->getUserAddresses($this->userLogin);

            $reply = array('result' => array('status'    => 0,
                                             'timestamp' => curdatetime(),
                                             'fields'    => array('account' => $this->customerID,
                                                                  'name'    => $userRealName,
                                                                  'address' => $userAddress,
                                                                  'balance' => $userBalance
                                                                 )
                                            ),
                           'id' => $this->paynetRequestID,
                           'jsonrpc' => '2.0'
                         );

            $reply = json_encode($reply);
        }

        die($reply);
    }

    /**
     * "CreateTransaction" request reply implementation - DOESN'T USED CURRENTLY
     */
    protected function replyCreateTransact() {
        $reply = '';
        $transactState = $this->checkTransactionExists($this->paynetTransactID);

        if (empty($transactState)) {
            $transactTimestamp  = $this->receivedJSON['params']['time'];
            $opTransactID       = self::HASH_PREFIX . $this->paynetTransactID;

            if ($this->paynetCashBoxID == $this->defaultCashBoxID) {
                // we don't want to send money from a certain cashbox to itself
                $transactReceivers = null;
            } else {
                $transactReceivers = array(array('id' => $this->paynetCashBoxID, 'amount' => $this->paymentSum));
            }

            $opTransactTimestamp = $this->saveTransaction($this->paynetTransactID, $opTransactID, $this->customerID,
                                                          $this->paymentSum, $transactTimestamp, json_encode($transactReceivers));

            $reply = array('result' => array('create_time'  => $opTransactTimestamp,
                                             'transaction'  => $opTransactID,
                                             'state'        => 1,
                                             'receivers'    => $transactReceivers
                                            ),
                           'id' => $this->paynetRequestID,
                           'jsonrpc' => '2.0'
                          );
            $reply = json_encode($reply);
        } else {
            if ($transactState == 1) {
                $transactData = $this->getTransactionData($this->paynetTransactID);

                if ($this->checkTransactionExpired($transactData)) {
                    $this->markTransactAsCanceled($this->paynetTransactID, 4);
                    $reply = $this->replyError('603');
                } else {
                    $receivers = json_decode($transactData['receivers'], true);
                    $reply = array('result' => array('create_time'  => (int)$transactData['create_timestamp'],
                                                     'transaction'  => $transactData['op_transact_id'],
                                                     'state'        => (int)$transactData['state'],
                                                     'receivers'    => ((empty($receivers) or trim($receivers) == 'null' or trim($receivers) == 'NULL')
                                                                        ? null : $receivers)
                                                    ),
                                   'id' => $this->paynetRequestID,
                                   'jsonrpc' => '2.0'
                                  );
                    $reply = json_encode($reply);
                }
            } else {
                $reply = $this->replyError('603');
            }
        }

        die($reply);
    }

    /**
     * "PerformTransaction" request reply implementation
     */
    protected function replyPerformTransact() {
        $reply                  = '';
        $opTransactTimestamp    = $this->createTransaction();
        $transactData           = $this->getTransactionData($this->paynetTransactID);

        if (empty($transactData)) {
            $reply = $this->replyError('203');
        } else {
            $transactState  = $transactData['state'];
            $opHash         = $transactData['op_transact_id'];
            $opHashData     = $this->getOPHashData($opHash);

            if ($transactState != 8) {
                if ($transactState == 2) {
                    // status 2 - transaction canceled

                    /*$reply = array('result' => array('providerTrnId'    => $transactData['op_transact_id'],
                                                     'timestamp'        => (int)$transactData['perform_timestamp'],
                                                    ),
                                   'fields' => array('transactionState' => (int)$transactData['state']),
                                   'id'     => $this->paynetRequestID,
                                   'jsonrpc' => '2.0'
                                  );
                    $reply = json_encode($reply);*/

                    $reply = $this->replyError('202');
                } elseif (empty($opHashData)) {
                    // transaction with such ID already exists
                    $reply = $this->replyError('201');
                } else {
                    // status unknown
                    $reply = $this->replyError('603');
                }
            } elseif ($this->checkTransactionExpired($transactData)) {
                // 'expired' status is not applicable for current paysys
                $this->markTransactAsCanceled($this->paynetTransactID, 4);
                $reply = $this->replyError('603');
            } else {
                $paymentSumm = round($transactData['amount'] / 100, 2);

                if (empty($opHashData)) {
                    //push transaction to database
                    op_TransactionAdd($opHash, $paymentSumm, $this->customerID, self::PAYSYS, 'PaynetUZ payment ID: ' . $this->paynetTransactID);
                    op_ProcessHandlers();
                    $payTimeStamp = $this->markTransactAsPaid($this->paynetTransactID);

                    $reply = array('result' => array('providerTrnId'    => crc32($opHash),
                                                     'timestamp'        => $payTimeStamp,
                                                     'status'           => 0
                                                    ),
                                   'fields' => array('transactionState' => 1),
                                   'id'     => $this->paynetRequestID,
                                   'jsonrpc' => '2.0'
                                  );
                    $reply = json_encode($reply);
                } else {
                    // transaction with such ID already exists
                    $reply = $this->replyError('201');
                }
            }
        }

        die($reply);
    }

    /**
     * "CheckTransaction" request reply implementation
     */
    protected function replyCheckTransact() {
        $reply          = '';
        $transactData   = $this->getTransactionData($this->paynetTransactID);

        if (empty($transactData)) {
            $reply = $this->replyError('203');
        } else {
            $opTransactID       = $transactData['op_transact_id'];
            $transactState      = $transactData['state'];
            $createTimeStamp    = $transactData['create_timestamp'];
            $payTimeStamp       = $transactData['perform_timestamp'];
            $cancelTimeStamp    = $transactData['cancel_timestamp'];
            $cancelReason       = (empty($transactData['cancel_reason']) ? null : (int)$transactData['cancel_reason']);
            $replyTimestamp     = '';

            if ($transactState == 1) {
                // status 1 - transaction successfully committed
                $replyTimestamp = $createTimeStamp;
            } elseif ($transactState == 2) {
                // status 2 - transaction canceled
                $replyTimestamp = $cancelTimeStamp;
            }

            $reply = array('result' => array('transactionState' => (int)$transactState,
                                             'timestamp'        => $replyTimestamp,
                                             'providerTrnId'    => crc32($opTransactID),
                                            ),
                           'id'     => $this->paynetRequestID,
                           'jsonrpc' => '2.0'
                          );
            $reply = json_encode($reply);
        }

        die($reply);
    }

    /**
     * "CancelTransaction" request reply implementation
     * We can cancel only unpaid transactions with state "1"
     */
    protected function replyCancelTransact() {
        $reply          = '';
        $transactData   = $this->getTransactionData($this->paynetTransactID);

        if (empty($transactData)) {
            $reply = $this->replyError('203');
        } else {
            $transactState = $transactData['state'];
            $opTransactID  = $transactData['op_transact_id'];

            if ($transactState != 8) {
                if ($transactState == 1) {
                    $reply = $this->replyError('77');
                } else {
                    /*$reply = array('result' => array('providerTrnId'    => $transactData['op_transact_id'],
                                                     'timestamp'        => (int)$transactData['cancel_timestamp'],
                                                     'transactionState' => (int)$transactData['state'],
                                                    ),
                                   'id'     => $this->paynetRequestID,
                                   'jsonrpc' => '2.0'
                                  );
                    $reply = json_encode($reply);*/

                    $reply = $this->replyError('202');
                }
            } else {
                //$cancelReason    = $this->receivedJSON['params']['reason'];
                $cancelReason    = 'Cancellation';
                $cancelTimeStamp = $this->markTransactAsCanceled($this->paynetTransactID, $cancelReason);

                $reply = array('result' => array('providerTrnId'    => crc32($opTransactID),
                                                 'timestamp'        => $cancelTimeStamp,
                                                 'transactionState' => 2,
                                                ),
                               'id'     => $this->paynetRequestID,
                               'jsonrpc' => '2.0'
                              );
                $reply = json_encode($reply);
            }
        }

        die($reply);
    }

    /**
     * "GetStatement" request reply implementation
     *
     * @param int $dtFrom
     * @param int $dtTo
     */
    protected function replyStatement($dtFrom, $dtTo) {
        $reply = '';
        $transactions = array();
        $transactsData = $this->getTransactionsDataAll($dtFrom, $dtTo);

        if (!empty($transactsData)) {
            foreach ($transactsData as $eachRec => $eachData) {
                //$receivers = json_decode($eachData['receivers'], true);
                $transactions[] = array('amount'        => $eachData['amount'],
                                        'transactionId' => $eachData['transact_id'],
                                        'providerTrnId' => crc32($eachData['op_transact_id']),
                                        'timestamp'     => $eachData['create_timestamp'],
                                        );
            }
        }

        $reply = array('result'     => array('statements' => $transactions),
                       'id'         => $this->paynetRequestID,
                       'jsonrpc'    => '2.0'
                      );
        $reply = json_encode($reply);
        die($reply);
    }

    /**
     * Returns JSON-encoded error reply
     *
     * @param $errorCode
     *
     * @return false|string
     */
    protected function replyError($errorCode) {
        $reply = array('error' => array('code'      => (int)$errorCode,
                                        'message'   => $this->errorCodes[$errorCode]
                                       ),
                       'id'    => $this->paynetRequestID,
                       'jsonrpc' => '2.0'
                      );
        $reply = json_encode($reply);
        return ($reply);
    }

    /**
     * Processes requests
     */
    protected function processRequest() {
        $this->opCustomersAll   = op_CustomersGetAll();
        $this->paynetServiceID  = (empty($this->receivedJSON['params']['serviceId']) ? '' : $this->receivedJSON['params']['serviceId']);
        $this->paynetTransactID = (empty($this->receivedJSON['params']['transactionId']) ? '' : $this->receivedJSON['params']['transactionId']);
        $this->paymentSum       = (empty($this->receivedJSON['params']['amount']) ? '' : $this->receivedJSON['params']['amount']);
        $statementFrom          = (empty($this->receivedJSON['params']['dateFrom']) ? '' : $this->receivedJSON['params']['dateFrom']);
        $statementTo            = (empty($this->receivedJSON['params']['dateTo']) ? '' : $this->receivedJSON['params']['dateTo']);
        $this->customerID       = (empty($this->receivedJSON['params']['fields'][$this->customerIDFieldName])
                                  ? $this->getCustomerIDFromSavedTransact($this->paynetTransactID)
                                  : $this->receivedJSON['params']['fields'][$this->customerIDFieldName]);

// some fields and values validations
        if ((in_array($this->paymentMethod, array('PerformTransaction', 'CancelTransaction', 'CheckTransaction'))
            and empty($this->paynetTransactID))
            or ($this->paymentMethod == 'GetStatement' and (empty($statementFrom) or empty($statementTo)))
        ) {
            die($this->replyError('-32600'));
        }

        if ($this->paymentMethod == 'CheckTransaction' and !$this->checkTransactionExists($this->paynetTransactID)) {
            die($this->replyError('203'));
        }

        if (empty($this->opCustomersAll[$this->customerID]) and $this->paymentMethod != 'GetStatement') {
            die($this->replyError('302'));
        }

        if (in_array($this->paymentMethod, array('PerformTransaction'))
            and !is_numeric($this->paymentSum)
           ) {
            die($this->replyError('413'));
        }

        $this->userLogin = $this->opCustomersAll[$this->customerID];
        $this->paynetCashBoxID = $this->defaultCashBoxID;

        if ($this->agentcodesON) {
            $this->paynetCashBoxID = $this->getCashBoxIDAgentAssigned($this->userLogin);

            if (empty($this->paynetCashBoxID)) {
                die($this->replyError('302'));
            }
        }
// some fields and values validations

// ('CheckPerformTransaction', 'CreateTransaction', 'PerformTransaction', 'CancelTransaction', 'CheckTransaction', 'GetStatement');
        switch ($this->paymentMethod) {
            case 'GetInformation':
                $this->replyPaymentAbilityCheck();
                break;

            case 'CreateTransaction':
                $this->replyCreateTransact();
                break;

            case 'PerformTransaction':
                $this->replyPerformTransact();
                break;

            case 'CancelTransaction':
                $this->replyCancelTransact();
                break;

            case 'CheckTransaction':
                $this->replyCheckTransact();
                break;

            case 'GetStatement':
                $this->replyStatement($statementFrom, $statementTo);
                break;

            default:
                die($this->replyError('-32601'));
        }
    }

    /**
     * Listen to your heart when he's calling for you
     * Listen to your heart, there's nothing else you can do
     *
     * @return void
     */
    public function listen() {
        $rawRequest = file_get_contents('php://input');
        //parse_str($rawRequest, $this->receivedJSON);
        $this->receivedJSON = json_decode($rawRequest, true);

        $this->setHTTPHeaders();

        if (empty($this->receivedJSON)) {
            die($this->replyError('-32700'));
        } else {
            $this->paynetRequestID = (empty($this->receivedJSON['id']) ? null : $this->receivedJSON['id']);

            if (empty($this->paynetRequestID)) {
                die($this->replyError('-32600'));
            } elseif ($this->checkAuth()) {
                $this->paymentMethod = (empty($this->receivedJSON['method']) ? '' : $this->receivedJSON['method']);

                if (in_array($this->paymentMethod, $this->paymentMethodsAvailable)) {
                    $this->processRequest();
                } else {
                    die($this->replyError('-32601'));
                }
            } else {
                die($this->replyError('501'));
            }
        }
    }
}

date_default_timezone_set('Asia/Tashkent');
$frontend = new PaynetUZ();
$frontend->listen();