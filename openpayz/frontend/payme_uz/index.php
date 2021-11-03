<?php

/**
 * Payme.UZ API frontend for OpenPayz
 *
 * https://developer.help.paycom.uz/ru/protokol-merchant-api
 *
 */

// подключаем API OpenPayz
include ("../../libs/api.openpayz.php");

class PaymeUZ {
    /**
     * Predefined stuff
     */
    const PATH_CONFIG       = 'config/paymeuz.ini';
    const PATH_AGENTCODES   = 'config/agentcodes_mapping.ini';

    /**
     * Paysys specific predefines
     */
    const HASH_PREFIX = 'PAYME_UZ_';
    const PAYSYS = 'PAYME_UZ';

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
     * Default UB agent code from config/paymeuz.ini
     *
     * @var
     */
    protected $agentcodeDefault = '';

    /**
     * Merchant login from PaymeUZ
     *
     * @var string
     */
    protected $paymeLogin = '';

    /**
     * Merchant cashbox password from PaymeUZ
     * https://developer.help.paycom.uz/ru/poisk-klyucha-i-id-kassy-v-lichnom-kabinete/poisk-klyucha-parolya-ot-kassy
     *
     * @var string
     */
    protected $paymePassword = '';

    /**
     * Request ID from Payme
     *
     * @var string
     */
    protected $paymeRequestID = null;

    /**
     * Placeholder for PaymeUZ transaction ID value
     *
     * @var string
     */
    protected $paymeTransactID = '';

    /**
     * Placeholder for PaymeUZ cashbox ID
     * 
     * @var string 
     */
    protected $paymeCashBoxID = '';
    
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
     * Placeholder for a "payment_method" GET parameter
     *
     * @var string
     */
    protected $paymentMethod = '';

    /**
     * Placeholder for available payment methods
     *
     * @var array
     */
    protected $paymentMethodsAvailable = array('CheckPerformTransaction', 'CreateTransaction', 'PerformTransaction', 'CancelTransaction', 'CheckTransaction', 'GetStatement');

    /**
     * Placeholder for PaymeUZ customer ID value
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
    protected $errorCodes = array('-32700' => array('ru' => 'Ошибка парсинга JSON', 'uz' => 'JSON парсинг хатоси', 'en' => 'JSON parse error'),
                                  '-32600' => array('ru' => 'Отсутствуют обязательные поля в RPC-запросе или тип полей не соответствует спецификации.', 'uz' => 'RPC сўровида мажбутий майдонлар йўқ ёки майдон тури спецификацияга мос келмайди.', 'en' => 'Missing required fields in RPC request or fields types does not correspond to specs'),
                                  '-32601' => array('ru' => 'Запрашиваемый метод не найден', 'uz' => 'Сўралган усул топилмади.', 'en' => 'Requested method not found'),
                                  '-32504' => array('ru' => 'Недостаточно привилегий для выполнения метода', 'uz' => 'Усулни бажариш учун етарли ваколат йўқ', 'en' => 'Not enough privileges to perform request'),
                                  '-31001' => array('ru' => 'Неверная сумма платежа', 'uz' => 'Тўлов миқдори нотўғри', 'en' => 'Incorrect payment amount'),
                                  '-31003' => array('ru' => 'Транзакция не найдена', 'uz' => 'Транзакция топилмади', 'en' => 'Transaction not found'),
                                  '-31007' => array('ru' => 'Невозможно отменить транзакцию. Услуга предоставлена потребителю в полном объеме.', 'uz' => 'Транзакцияни бекор қилиб бўлмайди. Хизмат истеъмолчига тўлиқ ҳажмда тақдим этилди', 'en' => 'Transaction can not be canceled. The service is provided to the consumer in full amount'),
                                  '-31008' => array('ru' => 'Невозможно выполнить операцию', 'uz' => 'Операцияни амалга ошириб бўлмайди', 'en' => 'Operation can not be performed'),
                                  '-31050' => array('ru' => 'Номер телефона не найден', 'uz' => 'Телефон рақами топилмади', 'en' => 'Phone number not found'),
                                  '-31051' => array('ru' => 'Транзакция с таким ID уже существует', 'uz' => 'Бундай ID билан транзакция мавжуд', 'en' => 'Transaction with such ID already exists'),
                                  '-31052' => array('ru' => 'Платёж с таким ID уже успешно проведён на стороне провайдера', 'uz' => 'Бундай ID билан тўлов провайдер томонидан муваффақиятли амалга оширилди', 'en' => 'Payment with this ID has already been successfully completed on the ISP side already'),
                                  '-31099' => array('ru' => 'Лицевой счёт не найден', 'uz' => 'Шаҳсий ҳисоб топилмади', 'en' => 'Customer ID not found')
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
            $this->customerIDFieldName = $this->config['CUSTOMERID_FIELD_NAME'];
            $this->paymeLogin          = $this->config['LOGIN'];
            $this->paymePassword       = $this->config['PASSWORD'];
            $this->ubapiURL            = $this->config['UBAPI_URL'];
            $this->ubapiKey            = $this->config['UBAPI_KEY'];
            $this->addressCityDisplay  = $this->config['CITY_DISPLAY_IN_ADDRESS'];
        } else {
            die('Fatal: config is empty!');
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
        return ($result);
    }

    /**
     * Validates PaymeUZ service ID and Ubilling agent ID correlation
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

        // if no mapped cashbox ID found or user does not have UB agent assigned to
        // and $this->agentcodesNonStrict is ON - proceed with default UB agent and a cashbox ID mapped to it
        // if no default cashbox ID is set - returns user not found error
        if (empty($cashboxID) and $this->agentcodesNonStrict and !empty($this->agentcodeDefault)) {
            $cashboxID = (empty($this->agentcodesMapping[$this->agentcodeDefault]) ? '' : $this->agentcodesMapping[$this->agentcodeDefault]);
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
        $result = (empty($userLogin) ? array() : '');
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
        $result = (empty($userLogin) ? array() : '');
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
     * Returns transaction data by $transactID
     *
     * @param string $transactID
     * @param int $fromTimeStamp
     * @param int $toTimestamp
     *
     * @return mixed|string
     */
    protected function getTransactionData($transactID, $fromTimeStamp = 0, $toTimestamp = 0) {
        $result = '';
        $whereFrom = (empty($fromTimeStamp) ? "" : " and `payme_transact_timestamp` >= " . $fromTimeStamp);
        $whereTo = (empty($toTimeStamp) ? "" : " and `payme_transact_timestamp` <= " . $toTimeStamp);
        $orderBy = (!empty($whereFrom) or !empty($whereTo) ? " ORDER BY `payme_transact_timestamp` ASC " : "");
        $tQuery = "SELECT * FROM `paymeuz_transactions` WHERE `transact_id` ='" . $transactID . "' " . $whereFrom . $whereTo . $orderBy;

        if (!empty($whereFrom) or !empty($whereTo)) {
            $result = simple_queryall($tQuery);
        } else {
            $result = simple_query($tQuery);
        }
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
        $tQuery = "SELECT `transact_id`, `state` FROM `paymeuz_transactions` WHERE `transact_id` ='" . $transactID . "'";
        $result = simple_query($tQuery);
        $transactState = (empty($result['state']) ? 0 : $result['state']);
        return($transactState);
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
        $transactState = 1;
        $opTransactDTCreate  = curdatetime();
        $opTransactTimestamp = strtotime($opTransactDTCreate);

        $tQuery = "INSERT INTO `paymeuz_transactions` (`date_create`, `transact_id`, `op_transact_id`, `op_customer_id`,
                                                       `amount`, `state`, `payme_transact_timestamp`, `create_timestamp`, `receivers`) 
                          VALUES ('" . $opTransactDTCreate . "', '" . $transactID . "', '" . $opTransactID . "', '" . $opCustomerID
                                  . "', '" . $transactAmount . "', " . $transactState . ", " . $transactTimestamp . ", " . $opTransactTimestamp
                                  . ", '" . $transactReceivers . "')";
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
        $payTimeStamp = strtotime(curdatetime());
        $tQuery = "UPDATE `paymeuz_transactions` SET `state` = 2, `perform_timestamp` = " . $payTimeStamp . " WHERE `transact_id` ='" . $transactID . "'";
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
        $cancelTimeStamp = strtotime(curdatetime());
        $tQuery = "UPDATE `paymeuz_transactions` SET `state` = -1, `cancel_timestamp` = " . $cancelTimeStamp . ", `cancel_reason` = '" . $cancelReason . "' WHERE `transact_id` ='" . $transactID . "'";
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
        $headers = getallheaders();

        return (!$headers
                or !isset($headers['Authorization'])
                or !preg_match('/^\s*Basic\s+(\S+)\s*$/i', $headers['Authorization'], $matches)
                or base64_decode($matches[1]) != $this->paymeLogin . ":" . $this->paymePassword);
    }

    /**
     * "CheckPerformTransaction" request reply implementation
     */
    protected function replyPaymentAbilityCheck() {
        $reply = '';
        $userData = $this->getUserStargazerData($this->userLogin);

        if (empty($userData)) {
            $reply = $this->replyError('-31099');
        } else {
            $userBalance    = $userData['Cash'];
            $userRealName   = $this->getUserRealnames($this->userLogin);
            $userAddress    = $this->getUserAddresses($this->userLogin);

            $reply = array('result' => array('allow' => true,
                                             'additional' => array('account' => $this->customerID,
                                                                   'full_name' => $userRealName,
                                                                   'address' => $userAddress,
                                                                   'balance' => $userBalance
                                                                  )
                                            ),
                           'id' => $this->paymeRequestID
                         );

            $reply = json_encode($reply);
        }

        //$this->setHTTPHeaders();
        die($reply);
    }

    /**
     * "CreateTransaction" request reply implementation
     */
    protected function replyCreateTransact() {
        $reply = '';
        $transactState = $this->checkTransactionExists($this->paymeTransactID);
        if (empty($transactState)) {
            $reply = $this->replyError('-31051');
        } else {
            $transactTimestamp  = $this->receivedJSON['time'];
            $opTransactID       = self::HASH_PREFIX . $this->paymeTransactID;
            $transactReceivers  = array(array('id' => $this->paymeCashBoxID, 'amount' => $this->paymentSum));

            $opTransactTimestamp = $this->saveTransaction($this->paymeTransactID, $opTransactID, $this->customerID,
                                                          $this->paymentSum, $transactTimestamp, json_encode($transactReceivers));

            $reply = array('result' => array('create_time'  => $opTransactTimestamp,
                                             'transaction'  => $opTransactID,
                                             'state'        => 1,
                                             'receivers'    => $transactReceivers
                                            ),
                           'id' => $this->paymeRequestID
                          );
            $reply = json_encode($reply);
        }

        //$this->setHTTPHeaders();
        die($reply);
    }

    /**
     * "PerformTransaction" request reply implementation
     */
    protected function replyPerformTransact() {
        $reply          = '';
        $transactData   = $this->getTransactionData($this->paymeTransactID);

        if (empty($transactData)) {
            $reply = $this->replyError('-31003');
        } else {
            $transactState = $transactData['state'];

            if ($transactState != 1) {
                $reply = $this->replyError('-31008');
            } else {
                $paymentSumm = $transactData['amount'];
                $opHash      = $transactData['op_transact_id'];
                $opHashData  = $this->getOPHashData($opHash);

                if (empty($opHashData)) {
                    //push transaction to database
                    op_TransactionAdd($opHash, $paymentSumm, $this->customerID, self::PAYSYS, 'PaymeUZ payment ID: ' . $this->paymeTransactID);
                    op_ProcessHandlers();
                    $payTimeStamp = $this->markTransactAsPaid($this->paymeTransactID);

                    $reply = array('result' => array('transaction'  => $opHash,
                                                     'perform_time' => $payTimeStamp,
                                                     'state'        => 2,
                                                    ),
                                   'id'     => $this->paymeRequestID
                                  );
                    $reply = json_encode($reply);
                } else {
                    $reply = $this->replyError('-31052');
                }
            }
        }

        //$this->setHTTPHeaders();
        die($reply);
    }

    /**
     * "CancelTransaction" request reply implementation
     * We can cancel only unpaid transactions with state "1"
     */
    protected function replyCancelTransact() {
        $reply          = '';
        $transactData   = $this->getTransactionData($this->paymeTransactID);

        if (empty($transactData)) {
            $reply = $this->replyError('-31003');
        } else {
            $transactState = $transactData['state'];
            $opTransactID  = $transactData['op_transact_id'];

            if ($transactState != 1) {
                if ($transactState == 2) {
                    $reply = $this->replyError('-31007');
                } else {
                    $reply = $this->replyError('-31008');
                }
            } else {
                $cancelTimeStamp = $this->markTransactAsCanceled($this->paymeTransactID);

                $reply = array('result' => array('transaction'  => $opTransactID,
                                                 'cancel_time'  => $cancelTimeStamp,
                                                 'state'        => -1,
                                                ),
                               'id'     => $this->paymeRequestID
                              );
                $reply = json_encode($reply);
            }
        }

        //$this->setHTTPHeaders();
        die($reply);
    }

    /**
     * "CheckTransaction" request reply implementation
     */
    protected function replyCheckTransact() {
        $reply          = '';
        $transactData   = $this->getTransactionData($this->paymeTransactID);

        if (empty($transactData)) {
            $reply = $this->replyError('-31003');
        } else {
            $opTransactID       = $transactData['op_transact_id'];
            $transactState      = $transactData['state'];
            $createTimeStamp    = $transactData['create_timestamp'];
            $payTimeStamp       = $transactData['perform_timestamp'];
            $cancelTimeStamp    = $transactData['cancel_timestamp'];
            $cancelReason       = (empty($transactData['cancel_reason']) ? null : $transactData['cancel_reason']);

            $reply = array('result' => array('create_time'  => $createTimeStamp,
                                             'perform_time' => $payTimeStamp,
                                             'cancel_time'  => $cancelTimeStamp,
                                             'transaction'  => $opTransactID,
                                             'state'        => $transactState,
                                             'reason'       => $cancelReason,
                                            ),
                           'id'     => $this->paymeRequestID
                          );
            $reply = json_encode($reply);
        }

        //$this->setHTTPHeaders();
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
        $transactsData = $this->getTransactionData($this->paymeTransactID, $dtFrom, $dtTo);

        if (!empty($transactsData)) {
            foreach ($transactsData as $eachRec => $eachData) {
                $receivers = json_decode($eachData['receivers'], true);
                $transactions[] = array('id'            => $eachData['transact_id'],
                                        'time'          => $eachData['payme_transact_timestamp'],
                                        'amount'        => $eachData['amount'],
                                        'account'       => array($this->customerIDFieldName => $this->customerID),
                                        'create_time'   => $eachData['create_timestamp'],
                                        'perform_time'  => $eachData['perform_timestamp'],
                                        'cancel_time'   => $eachData['cancel_timestamp'],
                                        'transaction'   => $eachData['op_transact_id'],
                                        'state'         => $eachData['state'],
                                        'reason'        => (empty($eachData['cancel_reason']) ? null : $eachData['cancel_reason']),
                                        'receivers'     => (empty($receivers) ? null : $receivers)
                                        );
            }
        }

        $reply = array('result' => array('transactions' => $transactions));
        $reply = json_encode($reply);
        //$this->setHTTPHeaders();
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
        $reply = array('error' => array('code'      => $errorCode,
                                        'message'   => $this->errorCodes[$errorCode]
                                       ),
                       'id'    => $this->paymeRequestID
                      );
        $reply = json_encode($reply);
        return ($reply);
    }

    /**
     * Processes requests
     */
    protected function processRequest() {
        $this->opCustomersAll   = op_CustomersGetAll();
        $this->customerID       = (empty($this->receivedJSON['account'][$this->customerIDFieldName])
                                   ? '' : $this->receivedJSON['account'][$this->customerIDFieldName]);
        $this->paymeTransactID  = (empty($this->receivedJSON['params']['id']) ? '' : $this->receivedJSON['params']['id']);
        $this->paymentSum       = (empty($this->receivedJSON['params']['amount']) ? '' : $this->receivedJSON['params']['amount']);
        $statementFrom          = (empty($this->receivedJSON['params']['from']) ? '' : $this->receivedJSON['params']['from']);
        $statementTo            = (empty($this->receivedJSON['params']['to']) ? '' : $this->receivedJSON['params']['to']);

        //$this->setHTTPHeaders();

// some fields and values validations
        if (empty($this->paymeRequestID)) {
            if (in_array($this->paymentMethod, array('CheckPerformTransaction', 'CreateTransaction'))) {
                if (empty($this->opCustomersAll[$this->customerID])) {
                    die($this->replyError('-31099'));
                }

                if (!is_numeric($this->paymentSum)) {
                    die($this->replyError('-31001'));
                }
            }

            if ((in_array($this->paymentMethod, array('CreateTransaction', 'PerformTransaction', 'CancelTransaction', 'CheckTransaction'))
                and empty($this->paymeTransactID))
                or ($this->paymentMethod == 'GetStatement' and (empty($statementFrom) or empty($statementTo)))
               ) {
                die($this->replyError('-32600'));
            }

            $this->userLogin = $this->opCustomersAll[$this->customerID];
            $this->paymeCashBoxID = $this->getCashBoxIDAgentAssigned($this->userLogin);

            if ($this->agentcodesON and empty($this->paymeCashBoxID)) {
                die($this->replyError('-31099'));
            }
// some fields and values validations

// ('CheckPerformTransaction', 'CreateTransaction', 'PerformTransaction', 'CancelTransaction', 'CheckTransaction', 'GetStatement');
            switch ($this->paymentMethod) {
                case 'CheckPerformTransaction':
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
        } else {
            die($this->replyError('-32600'));
        }

    }

    /**
     * Listen to your heart when he's calling for you
     * Listen to your heart, there's nothing else you can do
     *
     * @return void
     */
    public function listen() {
        //$request_body  = file_get_contents('php://input');
        //$this->payload = json_decode($request_body, true);
        //parse_str(file_get_contents('php://input'), $this->receivedJSON);
        $this->receivedJSON = json_decode(file_get_contents('php://input'), true);
        $this->setHTTPHeaders();

        if (!empty($this->receivedJSON)) {
            $this->paymeRequestID = (empty($this->receivedJSON['id']) ? null : $this->receivedJSON['id']);

            if ($this->checkAuth()) {
                $this->paymentMethod = (empty($this->receivedJSON['method']) ? '' : $this->receivedJSON['method']);

                if (in_array($this->paymentMethod, $this->paymentMethodsAvailable)) {
                    $this->processRequest();
                } else {
                    $this->replyError('-32601');
                }
            } else {
                $this->replyError('-32504');
            }
        } else {
            $this->replyError('-32700');
        }
    }
}

$frontend = new PaymeUZ();
$frontend->listen();