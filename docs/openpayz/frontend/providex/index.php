<?php

// including API OpenPayz
// change the path according to your realities
include ("../../libs/api.openpayz.php");

class Providex extends PaySysProto {
    /**
     * Predefined stuff
     */
    const PATH_CONFIG = 'config/providex.ini';

    /**
     * Paysys specific predefines
     * If you need multiple instances of this paysys for somehow -
     * just add a numeric index to HASH_PREFIX and PAYSYS constants, like:
     * PROVIDEX1_, PROVIDEX2_, PROVIDEXn_
     * PROVIDEX1, PROVIDEX2, PROVIDEXn
     * or distinguish it in any other way, suitable for you
     */
    const HASH_PREFIX           = 'PROVIDEX_';
    const PAYSYS                = 'PROVIDEX';
    const BACKEND_SRC_FLD_VAL   = 'BACKEND';

    const DEBUG_IDENT4          = '    ';       // 4 SPACES
    const DEBUG_IDENT6          = '      ';     // 6 SPACES
    const DEBUG_IDENT8          = '        ';   // 8 SPACES

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
    protected $paymentMethodsAvailable = array('preorder', 'confirmorder', 'purchase');

    /**
     * Successful status codes to check for on payment confirmation
     *
     * @var array
     */
    protected $successfulStatusCodes = array('1000', '1002', '1004', '1009');

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
     * Paysys merchant credentials from CONTRAGENT EXT INFO module
     *
     * @var array()
     */
    protected $merchantCreds = array();

    /**
     * Contains received by listener preprocessed request data
     *
     * @var array
     */
    protected $receivedJSON = array();

    /**
     * Placeholder for [data] base64 encoded structure from Tranzzo response
     */
    protected $tranzzoTransactData = array();

    /**
     * Preloads all required configuration, sets needed object properties
     *
     * @return void
     */
    public function __construct() {
        parent::__construct(self::PATH_CONFIG);
        $this->setOptions();
    }

    /**
     * Validates gets Providex merchant ID and password from contragents ext info by Ubilling agent ID
     *
     * @param $userLogin
     *
     * @return array
     */
    protected function getMerchantCredsByAgentID($userLogin) {
        $agentID             = $this->getUBAgentAssignedID($userLogin);
        $providexData        = $this->getUBAgentDataExten($agentID, self::PAYSYS);
        $providexData        = (empty($providexData) ? array() : $providexData[0]);
        $this->merchantCreds = $providexData;

        return ($providexData);
    }

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
    protected function createSign() {
        $paymentData        = $this->receivedJSON['data'];
        $providexAPISecret  = $this->merchantCreds['paysys_secret_key'];
        $sign               = PaySysProto::urlSafeBase64Encode(sha1($providexAPISecret . $paymentData . $providexAPISecret, true), false);

        $this->writeDebugLog(self::DEBUG_IDENT6 . 'Created sign:', $this->debugModeON);
        $this->writeDebugLog(self::DEBUG_IDENT8 . 'paymentData:' . "\n" . $paymentData, $this->debugModeON);
        $this->writeDebugLog(self::DEBUG_IDENT8 . 'providexAPISecretKey:             ' . $providexAPISecret, $this->debugModeON);
        $this->writeDebugLog(self::DEBUG_IDENT8 . 'signature calculated on billing:  ' . $sign, $this->debugModeON);

        return($sign);
    }

    /**
     * Validates request's sign string from ClickUZ
     *
     * @return bool
     */
    protected function validateSign() {
        $this->writeDebugLog(self::DEBUG_IDENT6 . 'Sign validation:', $this->debugModeON, 1);

        $providexSign   = $this->receivedJSON['signature'];
        $billingSign    = $this->createSign();
        $result         = ($providexSign == $billingSign);

        $this->writeDebugLog(self::DEBUG_IDENT8 . 'signature from request JSON:      ' . $providexSign, $this->debugModeON);

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
     *
     * @param string $orderID
     * @param bool $dontDIE
     */
    protected function replyPreOrder($orderID = '', $dontDIE = false) {
        $reply = '';
        if ($this->receivedJSON['method'] = 'purchase') {
            if ($this->merchantCreds['payment_fee_info'] == 'subscriber' and !empty($this->tranzzoTransactData['fee']['amount'])) {
                $moneyAmount = $this->tranzzoTransactData['processed_amount'] - $this->tranzzoTransactData['fee']['amount'];
            } else {
                $moneyAmount = $this->tranzzoTransactData['processed_amount'];
            }
        } else {
            $moneyAmount = $this->receivedJSON['amount'];
        }

    //  check $moneyAmount is a correct integer
    //  or float which has no more than 2 decimals
    //  or 2 decimals and unlimited trailing zeros
        if (PaySysProto::checkPaySumCorrect($moneyAmount)) {
            $this->replyError(400, 'TRANSACTION_INCORRECT_AMOUNT_VALUE');
        }

        $billingTransactID = empty($orderID) ? $this->generateOrderID() : $orderID;

        if ($this->checkTransactFileExists($billingTransactID)) {
            $this->replyError(400, 'TRANSACTION_ALREADY_EXISTS');
        } else {
            $transactData = array(
                                    'subscriberLogin'   => $this->subscriberLogin,
                                    'merchantSecretKey' => $this->merchantCreds['paysys_secret_key'],
                                    'paymentSum'        => $moneyAmount
                                );
            $this->saveTransactFile($billingTransactID, $transactData);
            $reply = array('data' => array('order' => $billingTransactID));
            $reply = json_encode($reply);

            $this->writeDebugLog(self::DEBUG_IDENT6 . 'preOrder() passed', $this->debugModeON, 1);
            $this->writeDebugLog(self::DEBUG_IDENT8 . 'orderID:  ' . $orderID, $this->debugModeON);
            $this->writeDebugLog(self::DEBUG_IDENT8 . 'paymentSum:  ' . $moneyAmount, $this->debugModeON);

            if ($dontDIE) {
                return($reply);
            } else {
                die($reply);
            }
        }
    }

    /**
     * [confirmorder] request reply implementation
     */
    protected function replyConfirmOrder() {
        $reply                  = '';
        $billingTransactID      = $this->tranzzoTransactData['order_id'];

        $this->writeDebugLog(self::DEBUG_IDENT6 . 'confirmOrder() started', $this->debugModeON, 1);
        $this->writeDebugLog(self::DEBUG_IDENT8 . 'orderID:  ' . $billingTransactID, $this->debugModeON);

        if ($this->checkTransactFileExists($billingTransactID)) {
            $transactData   = $this->getTransactFileData($billingTransactID);
            $transactSumm   = $transactData['paymentSum'];
            $pvdxTransactID = $this->tranzzoTransactData['transaction_id'];
            //$pvdxPaymentSum = $this->tranzzoTransactData['amount'];

            if ($this->merchantCreds['payment_fee_info'] == 'subscriber' and !empty($this->tranzzoTransactData['fee']['amount'])) {
                $pvdxPaymentSum = $this->tranzzoTransactData['processed_amount'] - $this->tranzzoTransactData['fee']['amount'];
            } else {
                $pvdxPaymentSum = $this->tranzzoTransactData['processed_amount'];
            }

            $this->writeDebugLog(self::DEBUG_IDENT8 . 'pvdxPaymentSum:  ' . $pvdxPaymentSum, $this->debugModeON);
            $this->writeDebugLog(self::DEBUG_IDENT8 . 'transactSumm:    ' . $transactSumm, $this->debugModeON);

            if ($pvdxPaymentSum == $transactSumm) {
                if ($this->validateSign()) {
                    $opHash     = self::HASH_PREFIX . $billingTransactID;
                    $opHashData = $this->getOPTransactDataByHash($opHash);

                    $this->writeDebugLog(self::DEBUG_IDENT8 . 'Sign is valid:  TRUE', $this->debugModeON);
                    $this->writeDebugLog(self::DEBUG_IDENT8 . 'opHashData:  ' . "\n" . print_r($opHashData, true), $this->debugModeON);

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

                        $this->writeDebugLog(self::DEBUG_IDENT8 . 'processed transaction reply JSON:', $this->debugModeON);
                        $this->writeDebugLog(self::DEBUG_IDENT8 . $reply, $this->debugModeON);
                        $this->writeDebugLog(self::DEBUG_IDENT4 . 'FINISHED request processing', $this->debugModeON);
                        $this->writeDebugLog(str_repeat('*', 80), $this->debugModeON);
                    } else {
                        $this->replyError(400, 'TRANSACTION_ALREADY_EXISTS');
                    }
                } else {
                    $this->replyError(422, 'TRANSACTION_INCORRECT_SIGN');
                }
            } else {
                $this->replyError(400, 'TRANSACTION_AMOUNT_VALUE_MISMATCH');
            }
        } else {
            $this->replyError(404, 'TRANSACTION_PREORDER_NOT_FOUND');
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
        $this->writeDebugLog(self::DEBUG_IDENT4 . 'ERORR REPLY:  ' . $errorCode . ' - ' . $errorMsg . "\n\n", $this->debugModeON, 1);
        die ($errorCode . ' - ' . $errorMsg);
    }

    /**
     * Processes requests
     */
    protected function processRequests() {
        $opCustomersAll  = array_flip(op_CustomersGetAll());
        $this->subscriberLogin = $this->receivedJSON['login'];
        $this->writeDebugLog(self::DEBUG_IDENT8 . 'user login:  '  . $this->subscriberLogin, $this->debugModeON);

        if (!empty($opCustomersAll[$this->subscriberLogin])) {
            $this->subscriberVirtualID = $opCustomersAll[$this->subscriberLogin];
            $this->writeDebugLog(self::DEBUG_IDENT8 . 'user OPAZYZ ID:  '  . $this->subscriberVirtualID, $this->debugModeON);

            if ($this->getUBAgentAssignedID($this->subscriberLogin) == 0) {
                $this->replyError(404, 'SUBSCRIBER_NOT_FOUND');
            }

            $this->getMerchantCredsByAgentID($this->subscriberLogin);
            if (empty($this->merchantCreds)) {
                $this->replyError(400, 'MERCHANT_NOT_FOUND');
            } elseif (empty($this->merchantCreds['paysys_secret_key']) or empty($this->merchantCreds['payment_fee_info'])) {
                $this->replyError(400, 'MERCHANT_CREDS_INCOMPLETE');
            }

            $this->writeDebugLog(self::DEBUG_IDENT8 . 'merchant creds:  ', $this->debugModeON);
            $this->writeDebugLog(self::DEBUG_IDENT8 . print_r($this->merchantCreds, true), $this->debugModeON);

            switch ($this->paymentMethod) {
                case 'preorder':
                    $this->replyPreOrder();
                    break;

                case 'confirmorder':
                case 'purchase':
                    $pvdxTransactStatusCode = $this->tranzzoTransactData['status_code'];
                    $this->writeDebugLog(self::DEBUG_IDENT8 . 'received transaction status code:  '  . $pvdxTransactStatusCode, $this->debugModeON);

                    if (empty($pvdxTransactStatusCode) or !in_array($pvdxTransactStatusCode, $this->successfulStatusCodes)) {
                        $this->replyError(422, 'PROVIDEX_TRANSACTION_STATUS_CODE_UNSUCCESSFUL');
                    }

                    if ($this->paymentMethod == 'purchase') {
                        $this->replyPreOrder($this->receivedJSON['order'], true);
                    }

                    $this->replyConfirmOrder();
                    break;

                default:
                    $this->replyError(422, 'PAYMENT_METHOD_UNKNOWN');
            }

        } else {
            $this->replyError(404, 'SUBSCRIBER_NOT_FOUND');
        }
    }

    /**
     * Listen to your heart when he's calling for you
     * Listen to your heart, there's nothing else you can do
     *
     * @return void
     */
    public function listen() {
        $this->writeDebugLog(str_repeat('=', 80), $this->debugModeON, 4);
        $this->writeDebugLog( 'STARTING request processing', $this->debugModeON);

        $rawRequest = file_get_contents('php://input');
        $isFormURLEncoded = ($_SERVER['CONTENT_TYPE'] == 'application/x-www-form-urlencoded');

        if ($isFormURLEncoded) {
            parse_str(urldecode($rawRequest), $this->receivedJSON);
        } else {
            $this->receivedJSON = json_decode($rawRequest, true);
        }

        $this->writeDebugLog('receivedJSON:', $this->debugModeON);
        $this->writeDebugLog(print_r($this->receivedJSON, true), $this->debugModeON);

        $this->setHTTPHeaders();

        if (empty($this->receivedJSON)) {
            $this->replyError(400, 'PAYLOAD_EMPTY');
        } else {
            if ($this->receivedJSON['method'] != 'preorder') {
                if (empty($this->receivedJSON['data'])) {
                    $this->replyError(422, 'UNPROCESSABLE ENTITY');
                }

                $this->tranzzoTransactData = json_decode(PaySysProto::urlSafeBase64Decode($this->receivedJSON['data']), true);

                if (empty($this->tranzzoTransactData)) {
                    $this->replyError(422, 'UNPROCESSABLE ENTITY');
                }

                $this->writeDebugLog('tranzzoTransactData:', $this->debugModeON);
                $this->writeDebugLog(print_r($this->tranzzoTransactData, true), $this->debugModeON);

                if (!empty($this->tranzzoTransactData['payload'])) {
                    $customPayload = json_decode($this->tranzzoTransactData['payload'], true);

                    if (!empty($customPayload['source']) and $customPayload['source'] == self::BACKEND_SRC_FLD_VAL) {
                        $this->receivedJSON['login'] = $customPayload['L'];
                        $this->receivedJSON['password'] = $customPayload['P'];
                        $this->receivedJSON['method'] = $this->tranzzoTransactData['method'];
                        $this->receivedJSON['order'] = $this->tranzzoTransactData['order_id'];
                        $this->receivedJSON['amount'] = $this->tranzzoTransactData['processed_amount'];
                    }
                }
            }
        }

        if ($this->receivedJSON['method'] != 'purchase' and empty($this->receivedJSON['providex'])) {
            $this->replyError(422, 'UNPROCESSABLE ENTITY');
        }

        $this->writeDebugLog('receivedJSON after change:', $this->debugModeON);
        $this->writeDebugLog(print_r($this->receivedJSON, true), $this->debugModeON);

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

$frontend = new Providex();
$frontend->listen();