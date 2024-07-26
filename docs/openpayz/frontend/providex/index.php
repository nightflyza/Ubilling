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
    const HASH_PREFIX = 'PROVIDEX_';
    const PAYSYS      = 'PROVIDEX';


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
     * @var string
     */
    protected $merchantCreds = '';

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
    }

    /**
     * Validates gets Providex merchant ID and password from contragents ext info by Ubilling agent ID
     *
     * @param $userLogin
     *
     * @return bool
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

        return($sign);
    }

    /**
     * Validates request's sign string from ClickUZ
     *
     * @return bool
     */
    protected function validateSign() {
        $this->getMerchantCredsByAgentID($this->subscriberLogin);

        $providexSign   = $this->receivedJSON['signature'];
        $billingSign    = $this->createSign();
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
        if (PaySysProto::checkPaySumCorrect($moneyAmount)) {
            $this->replyError(400, 'TRANSACTION_INCORRECT_AMOUNT_VALUE');
        }

        $billingTransactID = $this->generateOrderID();

        if ($this->checkTransactFileExists($billingTransactID)) {
            $this->replyError(400, 'TRANSACTION_ALREADY_EXISTS');
        } else {
            $merchantData = $this->getMerchantCredsByAgentID($this->subscriberLogin);

            if (empty($merchantData['paysys_secret_key'])) {
                $this->replyError(400, 'MERCHANT_NOT_FOUND');
            } else {
                $transactData = array(
                                        'subscriberLogin'   => $this->subscriberLogin,
                                        'merchantSecretKey' => $merchantData['paysys_secret_key'],
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
        $reply                  = '';
        $pvdxTransactData       = json_decode(PaySysProto::urlSafeBase64Decode($this->receivedJSON['data']), true);
        $billingTransactID      = $pvdxTransactData['order_id'];
        $pvdxTransactStatusCode = $pvdxTransactData['status_code'];

        if (!empty($pvdxTransactStatusCode) and in_array($pvdxTransactStatusCode, $this->successfulStatusCodes)) {
            if ($this->checkTransactFileExists($billingTransactID)) {
                $transactData = $this->getTransactFileData($billingTransactID);
                $transactSumm = $transactData['paymentSum'];

                $pvdxTransactID = $pvdxTransactData['transaction_id'];
                $pvdxPaymentSum = $pvdxTransactData['amount'];

                if ($pvdxPaymentSum == $transactSumm) {
                    if ($this->validateSign()) {
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
                        }
                        else {
                            $this->replyError(400, 'TRANSACTION_ALREADY_EXISTS');
                        }
                    }
                    else {
                        $this->replyError(422, 'TRANSACTION_INCORRECT_SIGN');
                    }
                }
                else {
                    $this->replyError(400, 'TRANSACTION_AMOUNT_VALUE_MISMATCH');
                }
            }
            else {
                $this->replyError(404, 'TRANSACTION_PREORDER_NOT_FOUND');
            }
        } else {
            $this->replyError(422, 'PROVIDEX_TRANSACTION_STATUS_CODE_UNSUCCESSFUL');
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
        $opCustomersAll  = array_flip(op_CustomersGetAll());
        $this->subscriberLogin = $this->receivedJSON['login'];

        if (!empty($opCustomersAll[$this->subscriberLogin])) {
            $this->subscriberVirtualID = $opCustomersAll[$this->subscriberLogin];

            if ($this->getUBAgentAssignedID($this->subscriberLogin) == 0) {
                $this->replyError(404, 'SUBSCRIBER_NOT_FOUND');
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
        $rawRequest = file_get_contents('php://input');
        $isFormURLEncoded = ($_SERVER['CONTENT_TYPE'] == 'application/x-www-form-urlencoded');

        if ($isFormURLEncoded) {
            parse_str(urldecode($rawRequest), $this->receivedJSON);
        } else {
            $this->receivedJSON = json_decode($rawRequest, true);
        }

        $this->setHTTPHeaders();

        if (empty($this->receivedJSON)) {
            $this->replyError(400, 'PAYLOAD_EMPTY');
        } else {
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

$frontend = new Providex();
$frontend->listen();