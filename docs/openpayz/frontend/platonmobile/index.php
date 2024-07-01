<?php

// including API OpenPayz
// change the path according to your realities
include ("../../libs/api.openpayz.php");

class PlatonMobile extends PaySysProto {
    /**
     * Predefined stuff
     */
    const PATH_CONFIG = 'config/platonmobile.ini';

    /**
     * Paysys specific predefines
     * If you need multiple instances of this paysys for somehow -
     * just add a numeric index to HASH_PREFIX and PAYSYS constants, like:
     * PLATONMOBILE1_, PLATONMOBILE2_, PLATONMOBILEn_
     * PLATONMOBILE1, PLATONMOBILE2, PLATONMOBILEn
     * or distinguish it in any other way, suitable for you
     */
    const HASH_PREFIX = 'PLATONMOBILE_';
    const PAYSYS      = 'PLATONMOBILE';


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
     * Subscriber's virtual payment ID
     *
     * @var string
     */
    protected $subscriberVirtualID = '';

    /**
     * Subscriber's login from PlatonMobile
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
    }

    /**
     * Validates gets PlatonMobile merchant ID and password from contragents ext info by Ubilling agent ID
     *
     * @param $userLogin
     *
     * @return bool
     */
    protected function getMerchantCredsByAgentID($userLogin) {
        $agentID        = $this->getUBAgentAssignedID($userLogin);
        $platonData   = $this->getUBAgentDataExten($agentID, self::PAYSYS);
        $platonData   = (empty($platonData) ? array() : $platonData[0]);

        return ($platonData);
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
        $platonSign   = $this->receivedJSON['sign'];
        $billingSign    = $this->createSign($merchantPasswd);
        $result         = ($platonSign == $billingSign);

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
                                  self::PAYSYS, 'PlatonMobile payment ID: ' . $pvdxTransactID);
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
        //parse_str($rawRequest, $this->receivedJSON);
        $this->receivedJSON = json_decode($rawRequest, true);
        $this->setHTTPHeaders();

        if (empty($this->receivedJSON)) {
            $this->replyError(400, 'PAYLOAD_EMPTY');
        } else {
            $this->receivedJSON = (isset($this->receivedJSON['data']) ? $this->receivedJSON['data'] : $this->receivedJSON);

            if (empty($this->receivedJSON['platonmobile'])) {
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

$frontend = new PlatonMobile();
$frontend->listen();