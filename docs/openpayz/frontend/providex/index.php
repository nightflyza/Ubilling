<?php

// including API OpenPayz
include ("../../libs/api.openpayz.php");

class Providex extends PaySysProto {
    /**
     * Predefined stuff
     */
    const PATH_CONFIG     = 'config/providex.ini';
//    const PATH_AGENTCODES = 'config/agentcodes_mapping.ini';
//    const PATH_TRANSACTS  = 'tmp/';

    /**
     * Paysys specific predefines
     */
    const HASH_PREFIX = 'PROVIDEX_';
    const PAYSYS      = 'PROVIDEX';

    /**
     * Agent codes using flag
     *
     * @var bool
     */
//    protected $agentcodesON = false;

    /**
     * Non strict agent codes using flag
     *
     * @var bool
     */
//    protected $agentcodesNonStrict = false;

    /**
     * Contains values from agentcodes_mapping.ini
     *
     * @var array
     */
//    protected $agentcodesMapping = array();

    /**
     * Merchants ID => password mapping from Providex
     *
     * @var string
     */
    protected $merchantIDPasswd = array();

    /**
     * Current merchant ID from "preorder" request
     *
     * @var string
     */
    protected $curMerchantID = '';

    /**
     * Current merchant password
     *
     * @var string
     */
    protected $curMerchantPasswd = '';

    /**
     * Placeholder for UB API URL
     *
     * @var string
     */
//    protected $ubapiURL = '';

    /**
     * Placeholder for UB API key
     *
     * @var string
     */
//    protected $ubapiKey = '';

    /**
     * Placeholder for CITY_DISPLAY_IN_ADDRESS config option
     *
     * @var bool
     */
//    protected $addressCityDisplay = false;

    /**
     * Instance configuration as key=>value
     *
     * @var array
     */
//    protected $config = array();

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
    protected $paymentMethodsAvailable = array('preorder', 'confirmorder');

    /**
     * Request ID from Providex
     *
     * @var string
     */
//    protected $providexOrderID = null;

    /**
     * Subscriber's virtual payment ID
     *
     * @var string
     */
    protected $subscriberVirtualID = '';

    /**
     * Subscriber's login from Providex
     *
     * @var string
     */
    protected $subscriberLogin = '';

    /**
     * Contains received by listener preprocessed request data
     *
     * @var array
     */
    protected $receivedJSON = array();


    /**
     * Preloads all required configuration, sets needed object properties
     *
     * @return void
     */
    public function __construct() {
        parent::__construct(self::PATH_CONFIG);
        $this->setOptions();
        $this->loadAgentCodesMapping();
    }

//    /**
//     * Loads frontend configuration in protected prop
//     *
//     * @return void
//     */
//    protected function loadConfig($config_path = self::PATH_CONFIG) {
//        if (file_exists($config_path)) {
//            $this->config = parse_ini_file($config_path);
//        } else {
//            die('Fatal error: config file ' . $config_path . ' not found!');
//        }
//    }
//
//    /**
//     * Loads frontend agentcodes_mapping.ini in protected prop
//     *
//     * @return void
//     */
//    protected function loadACMapping() {
//        if ($this->agentcodesON) {
//            if (file_exists(self::PATH_AGENTCODES)) {
//                $this->agentcodesMapping = parse_ini_file(self::PATH_AGENTCODES);
//            } else {
//                die('Fatal error: agentcodes_mapping.ini file ' . self::PATH_AGENTCODES . ' not found!');
//            }
//        }
//    }

    /**
     * Sets object properties based on frontend config
     *
     * @return void
     */
    protected function setOptions() {
        if (!empty($this->config)) {
            $this->agentcodesON         = $this->config['USE_AGENTCODES'];
            $this->agentcodesNonStrict  = $this->config['NON_STRICT_AGENTCODES'];
            $this->ubapiURL             = $this->config['UBAPI_URL'];
            $this->ubapiKey             = $this->config['UBAPI_KEY'];
            $this->addressCityDisplay   = $this->config['CITY_DISPLAY_IN_ADDRESS'];
            $tmpMerchIDPasswd           = $this->config['MERCHANT_ID_PASSWORD_MAPPING'];

            $tmpMerchIDPasswd = explode(',', $tmpMerchIDPasswd);
            foreach ($tmpMerchIDPasswd as $eachPair) {
                $tmpPair = explode(':', $eachPair);
                $this->merchantIDPasswd[trim($tmpPair[0])] = trim($tmpPair[1]);
            }

        } else {
            die('Fatal: config is empty!');
        }
    }

    /**
     * Validates gets Providex merchant ID and password from contragents ext info by Ubilling agent ID
     *
     * @param $userLogin
     *
     * @return bool
     */
    protected function getMerchantCredsByAgentID($userLogin) {
        $result         = array();
        $agentID        = $this->getUBAgentAssignedID($userLogin);
        $providexData   = $this->getUBAgentDataExten($agentID, self::PAYSYS);
        $providexData   = (empty($providexData) ? array() : $providexData[0]);

        return ($providexData);
    }

//    /**
//     * Gets user associated agent data JSON
//     *
//     * @param string $userLogin
//     *
//     * @return string
//     */
//    protected function getUBAgentData($userLogin) {
//        $action = $this->ubapiURL . '?module=remoteapi&key=' . $this->ubapiKey . '&action=getagentdata&param=' . $userLogin;
//        @$result = file_get_contents($action);
//        return ($result);
//    }

//    /**
//     * Returns user stargazer data by login
//     *
//     * @param string $userLogin existing stargazer login
//     *
//     * @return array
//     */
//    protected function getUserStargazerData($userLogin) {
//        $userLogin = mysql_real_escape_string($userLogin);
//        $query     = "SELECT * from `users` WHERE `login`='" . $userLogin . "';";
//        $result    = simple_query($query);
//        return ($result);
//    }
//
//    /**
//     * Returns all user RealNames
//     *
//     * @param string $userLogin
//     *
//     * @return array
//     */
//    protected function getUserRealnames($userLogin = '') {
//        $result = array();
//        $whereStr = (empty($userLogin) ? '' : " WHERE `login` = '" . $userLogin . "'");
//
//        $query = "SELECT * from `realname`" . $whereStr;
//        $realnames = simple_queryall($query);
//
//        if (!empty($realnames)) {
//            foreach ($realnames as $io => $each) {
//                $result[$each['login']] = $each['realname'];
//            }
//        }
//
//        $result = (empty($userLogin) ? $result : $result[$userLogin]);
//        return($result);
//    }
//
//
//    /**
//     * Returns array of available or filtered by user login address as login => address
//     *
//     * @param string $userLogin
//     *
//     * @return array|string
//     */
//    protected function getUserAddresses($userLogin = '') {
//        $result = array();
//        $whereStr = (empty($userLogin) ? '' : " WHERE `address`.`login` = '" . $userLogin . "'");
//
//        $query = "
//            SELECT `address`.`login`,`city`.`cityname`,`street`.`streetname`,`build`.`buildnum`,`apt`.`apt`
//                FROM `address`
//                    INNER JOIN `apt` ON `address`.`aptid`= `apt`.`id`
//                    INNER JOIN `build` ON `apt`.`buildid`=`build`.`id`
//                    INNER JOIN `street` ON `build`.`streetid`=`street`.`id`
//                    INNER JOIN `city` ON `street`.`cityid`=`city`.`id`"
//                 . $whereStr;
//
//        $addresses = simple_queryall($query);
//
//        if (!empty($addresses)) {
//            foreach ($addresses as $eachAddress) {
//                // zero apt handle
//                $apartment_filtered = ($eachAddress['apt'] == 0) ? '' : '/' . $eachAddress['apt'];
//
//                if ($this->addressCityDisplay) {
//                    $result[$eachAddress['login']] = $eachAddress['cityname'] . ' ' . $eachAddress['streetname'] . ' ' . $eachAddress['buildnum'] . $apartment_filtered;
//                } else {
//                    $result[$eachAddress['login']] = $eachAddress['streetname'] . ' ' . $eachAddress['buildnum'] . $apartment_filtered;
//                }
//            }
//        }
//
//        $result = (empty($userLogin) ? $result : $result[$userLogin]);
//        return($result);
//    }
//
//    /**
//     * Check transaction hash for duplicates by returning transaction data if it exists
//     *
//     * @param string $transactHash
//     *
//     * @return array
//     */
//    protected function getOPHashData($transactHash) {
//        $result = array();
//
//        if (!empty($transactHash)) {
//            $transactData = simple_query("SELECT * from `op_transactions` WHERE `hash`='" . $transactHash . "'");
//
//            if (!empty($transactData)) {
//                $result = $transactData;
//            }
//        }
//
//        return($result);
//    }
//
//    /**
//     * Returns transaction data by $transactID
//     *
//     * @param $transactID
//     *
//     * @return mixed|string
//     */
//    protected function getTransactionData($transactID) {
//        $result = '';
//
//        if ($this->checkTransactionExists($transactID)) {
//            $result = unserialize(file_get_contents(self::PATH_TRANSACTS . $transactID));
//        }
//
//        return($result);
//    }
//
//    /**
//     * Checks is transaction already exists or not?
//     *
//     * @param string $transactID
//     *
//     * @return bool
//     */
//    protected function checkTransactionExists($transactID) {
//        $result = (!empty($transactID) and file_exists(self::PATH_TRANSACTS . $transactID));
//        return($result);
//    }
//
//    /**
//     * Saves transaction id to validate some possible duplicates
//     *
//     * @param string $transactID
//     *
//     * @return string
//     */
//    protected function saveTransaction($transactID) {
//        file_put_contents(self::PATH_TRANSACTS . $transactID, serialize($this->receivedJSON));
//        return($transactID);
//    }

    /**
     * Returns true/false by login/password auth
     *
     * @param string $login
     * @param string $password
     *
     * @return bool
     */
    protected function checkAuth($login, $password) {
        $result = false;
        $login = vf($login);
        $login = preg_replace('#[^a-z0-9A-Z\-_\.]#Uis', '', $login);
        $login = preg_replace('/\0/s', '', $login);
        $password = vf($password);
        $password = preg_replace('#[^a-z0-9A-Z\-_\.]#Uis', '', $password);
        $password = preg_replace('/\0/s', '', $password);

        if (!empty($login) AND (!empty($password))) {
            $query = "SELECT `IP` from `users` WHERE `login`='" . $login . "' AND MD5(`password`)='" . $password . "'";
            $data = simple_query($query);
            $result = !empty($data['IP']);
        }

        return ($result);
    }

    /**
     * Creates md5 sign string according to specs
     *
     * @return string
     */
    protected function createSign($merchantPasswd) {
        $email      = empty($this->receivedJSON['email']) ? '' : $this->receivedJSON['email'];
        $orderID    = $this->receivedJSON['order'];
        $cardNum    = $this->receivedJSON['card'];

        $sign       = md5(
                        strtoupper(
                    strrev($email) . $merchantPasswd . $orderID .
                          strrev(
                      substr($cardNum,0,6) .
                            substr($cardNum,-4)
                            )
                        )
                    );

        return($sign);
    }

    /**
     * Validates request's sign string from ClickUZ
     *
     * @return bool
     */
    protected function validateSign($merchantPasswd) {
        $providexSign   = $this->receivedJSON['sign'];
        $billingSign    = $this->createSign($merchantPasswd);
        $result         = ($providexSign == $billingSign);
        return($result);
    }

    /**
     * Generates random CRC32-like string
     *
     * @return string
     */
    protected function generateOrderID() {
        $orderID = crc32($this->receivedJSON['login'] . $this->receivedJSON['password']) . crc32(microtime(true));
        return ($orderID);
    }

    /**
     * [preorder] request reply implementation
     */
    protected function replyPreOrder() {
        $reply = '';
        $moneyAmount = $this->receivedJSON['amount'];
    //  check $moneyAmount is a correct integer
    //  or float which has no more than 2 decimals
    //  or 2 decimals and unlimited trailing zeros
        if ($this->checkPaySumCorrect($moneyAmount)) {
            $this->replyError(400, 'TRANSACTION_INCORRECT_AMOUNT_VALUE');
        }

        $billingTransactID = $this->generateOrderID();

        if ($this->checkTransactFileExists($billingTransactID)) {
            $this->replyError(400, 'TRANSACTION_ALREADY_EXISTS');
        } else {
            $merchantData = $this->getMerchantCredsByAgentID($this->subscriberLogin);

            if (empty($merchantData['internal_paysys_id']) or empty($merchantData['paysys_password'])) {
                $this->replyError(400, 'MERCHANT_NOT_FOUND');
            } else {
                $transactData = array(
                                'subscriberLogin'   => $this->subscriberLogin,
                                'merchantID'        => $merchantData['internal_paysys_id'],
                                'merchantPassword'  => $merchantData['paysys_password'],
                                'paymentSum'        => $moneyAmount
                                );
                $this->saveTransactFile($billingTransactID, $transactData);
                $reply = array('data' => array('order' => $billingTransactID));
                $reply = json_encode($reply);
                die($reply);
            }
        }
    }

    /**
     * [confirmorder] request reply implementation
     */
    protected function replyConfirmOrder() {
        $reply              = '';
        $billingTransactID  = $this->receivedJSON['order'];

        if ($this->checkTransactFileExists($billingTransactID)) {
            $transactData   = $this->getTransactFileData($billingTransactID);
            $transactSumm   = $transactData['paymentSum'];
            $merchantPasswd = $transactData['merchantPassword'];

            $pvdxTransactID = $this->receivedJSON['id'];
            $pvdxPaymentSum = $this->receivedJSON['amount'];

            if ($pvdxPaymentSum == $transactSumm) {
                if ($this->validateSign($merchantPasswd)) {
                    $opHash     = self::HASH_PREFIX . $billingTransactID;
                    $opHashData = $this->getOPTransactDataByHash($opHash);

                    if (empty($opHashData)) {
                        //push transaction to database
                        op_TransactionAdd($opHash, $pvdxPaymentSum, $this->subscriberVirtualID,
                                  self::PAYSYS, 'Providex payment ID: ' . $pvdxTransactID);
                        op_ProcessHandlers();

                        $reply = array(
                                    'transact_id' => $pvdxTransactID,
                                    'order'       => $billingTransactID,
                                    'amount'      => $pvdxPaymentSum,
                                    'login'       => $this->subscriberLogin,
                                    'state'       => 'SUCCESS'
                                    );

                        $reply = json_encode($reply);
                    } else {
                        $this->replyError(400, 'TRANSACTION_ALREADY_EXISTS');
                    }
                } else {
                    $this->replyError(422, 'TRANSACTION_INCORRECT_SIGN');
                }
            } else {
                $this->replyError(400, 'TRANSACTION_INCORRECT_AMOUNT_VALUE');
            }
        } else {
            $this->replyError(400, 'TRANSACTION_PREORDER_NOT_FOUND');
        }

        die($reply);
    }

    /**
     * Sets HTTP headers before reply
     */
    protected function setHTTPHeaders() {
        header('Content-Type: application/json; charset=UTF-8');
    }

    /**
     * Returns JSON-encoded error reply
     *
     * @param $errorCode
     *
     * @return false|string
     */
    protected function replyError($errorCode = 400, $errorMsg = 'SOMETHING WENT WRONG') {
        header('HTTP/1.1 ' . $errorCode  . ' ' . $errorMsg . '"', true, $errorCode);
        die ($errorCode . ' - ' . $errorMsg);
    }

    /**
     * Processes requests
     */
    protected function processRequests() {
        $this->opCustomersAll  = array_flip(op_CustomersGetAll());
        $this->subscriberLogin = $this->receivedJSON['login'];

        if (!empty($this->opCustomersAll[$this->subscriberLogin])) {
            $this->subscriberVirtualID = $this->opCustomersAll[$this->subscriberLogin];

            if ($this->agentcodesON and $this->getUBAgentAssignedID($this->subscriberLogin) == 0) {
                $this->replyError(400, 'SUBSCRIBER_NOT_FOUND');
            }

            switch ($this->paymentMethod) {
                case 'preorder':
                    $this->replyPreOrder();
                    break;

                case 'confirmorder':
                    $this->replyConfirmOrder();
                    break;

                default:
                    $this->replyError(422, 'PAYMENT_METHOD_UNKNOWN');
            }

        } else {
            $this->replyError(400, 'SUBSCRIBER_NOT_FOUND');
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
            $this->replyError(400, 'PAYLOAD_EMPTY');
        } else {
            $this->receivedJSON = (isset($this->receivedJSON['data']) ? $this->receivedJSON['data'] : $this->receivedJSON);

            if (empty($this->receivedJSON['providex'])) {
                $this->replyError(422, 'UNPROCESSABLE ENTITY');
            } else {
                $this->paymentMethod = (empty($this->receivedJSON['method']) ? '' : trim($this->receivedJSON['method']));

                if (in_array($this->paymentMethod, $this->paymentMethodsAvailable)) {
                    if ($this->checkAuth($this->receivedJSON['login'], $this->receivedJSON['password'])) {
                        $this->processRequests();
                    } else {
                        $this->replyError(401, 'UNAUTHORIZED');
                    }
                } else {
                    $this->replyError(422, 'PAYMENT_METHOD_UNKNOWN');
                }
            }
        }
    }
}