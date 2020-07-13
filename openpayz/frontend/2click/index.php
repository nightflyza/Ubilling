<?php

/**
 * 2click.money protocol v2.30 OpenPayz frontend
 * https://protocol.2click.money/Content/Documents/2click_protocol_for_providers_service%202.30.pdf
 * 
 * Supports:
 * - GET/POST requests
 * - MD5/SHA1 signs
 * - Passes the retarded validator https://protocol.2click.money/
 * 
 */
error_reporting(E_ALL);


include ("../../libs/api.openpayz.php");

class TwoClickFrontend {

    /**
     * current instance request method
     *
     * @var string
     */
    protected $requestMethod = '';

    /**
     * Personal secret key for further generation of signs
     *
     * @var string
     */
    protected $secret = '';

    /**
     * Minimum amount of valid payment
     *
     * @var int
     */
    protected $minAmount = 0;

    /**
     * Maximum amount of valid payment
     *
     * @var int
     */
    protected $maxAmount = 0;

    /**
     * Contains text service name
     *
     * @var string
     */
    protected $serviceName = '';

    /**
     * Sign generation method here
     *
     * @var string
     */
    protected $signMethod = '';

    /**
     * Sign validation flag
     *
     * @var bool
     */
    protected $checkSign = true;

    /**
     * Optional POST variable name for XML receiving
     *
     * @var string
     */
    protected $postVar = '';

    /**
     * Instance configuration as key=>value
     *
     * @var array
     */
    protected $config = array();

    /**
     * Contains received by listener preprocessed request data
     *
     * @var array
     */
    protected $receivedData = array();

    /**
     * Cumulative reply array as tag=>value
     *
     * @var array
     */
    protected $replyData = array();

    /**
     * Cumulative transaction reply array as tag=>value
     *
     * @var array
     */
    protected $replyTransaction = array();

    /**
     * Predefined stuff
     */
    const CONFIG_PATH = 'config/2click.ini';
    const TRANS_PATH = 'tmp/';

    /**
     * Paysys specific predefines
     */
    const HASH_PREFIX = '2CLICK_';
    const PSYS = '2CLICK';

    /**
     * Actions codes here
     */
    const ACT_INFO = 1;
    const ACT_PAY = 4;
    const ACT_STATUS = 7;

    /**
     * Reply codes here
     */
    const REPLY_POSSIBLE = 21;
    const REPLY_PAYOK = 22;
    const REPLY_BADPARAMS = -101;
    const REPLY_DUPLICATE = -100;
    const REPLY_NOCLIENT = -40;
    const REPLY_NOTRANS = -10;
    const REPLY_OKTRANS = 11;
    const STATUS_SUCCESS = 111;
    const STATUS_PROCESSING = 120;
    const STATUS_CANCELLED = 130;

    /**
     * Preloads all required configuration, sets needed object properties
     * 
     * @return void
     */
    public function __construct() {
        $this->loadConfig();
        $this->setOptions();
    }

    /**
     * Loads frontend configuration in protected prop
     * 
     * @return void
     */
    protected function loadConfig() {
        if (file_exists(self::CONFIG_PATH)) {
            $this->config = parse_ini_file(self::CONFIG_PATH);
        } else {
            die('Fatal error: config file ' . self::CONFIG_PATH . ' not found!');
        }
    }

    /**
     * Sets object properties based on frontend config
     * 
     * @return void
     */
    protected function setOptions() {
        if (!empty($this->config)) {
            $this->requestMethod = $this->config['METHOD'];
            $this->postVar = $this->config['POST_VAR'];
            $this->secret = $this->config['SECRET'];
            $this->minAmount = $this->config['MIN_AMOUNT'];
            $this->maxAmount = $this->config['MAX_AMOUNT'];
            $this->serviceName = $this->config['SERVICE_NAME'];
            $this->signMethod = $this->config['SIGN_METHOD'];
            $this->checkSign = ($this->config['CHECK_SIGN']) ? true : false;
        } else {
            die('Fatal: config is empty!');
        }
    }

    /**
     * Returns raw GET request data
     * 
     * @return array
     */
    protected function getRequestRaw() {
        $result = array();
        if (isset($_GET['ACT'])) {
            $result = $_GET;
        }
        return($result);
    }

    /**
     * Returns received raw POST request data
     * 
     * @return array
     */
    protected function postRequestRaw() {
        $result = array();
        if (empty($this->postVar)) {
            $resultTmp = file_get_contents('php://input');
        } else {
            if (isset($_POST[$this->postVar])) {
                $resultTmp = $_POST[$this->postVar];
            }
        }

        if (!empty($resultTmp)) {
            $resultArr = xml2array($resultTmp);
            if (isset($resultArr['pay-request'])) {
                $result = $resultArr['pay-request'];
            }
        }

        return($result);
    }

    /**
     * Returns preprocessed request data to listener
     * 
     * @return array
     */
    protected function getRequestData() {
        $result = array();
        if ($this->requestMethod == 'POST') {
            $requestDataRaw = $this->postRequestRaw();
        }

        if ($this->requestMethod == 'GET') {
            $requestDataRaw = $this->getRequestRaw();
        }

        $result = $this->preprocessRequestData($requestDataRaw);
        return($result);
    }

    /**
     * Performs received data preprocessing to unified internal format
     * 
     * @param array $requestDataRaw
     * 
     * @return array
     */
    protected function preprocessRequestData($requestDataRaw) {
        $result = array();
        if (!empty($requestDataRaw)) {
            if ($this->requestMethod == 'GET') {
                $result = $requestDataRaw;
            }

            if ($this->requestMethod == 'POST') {
                if (!empty($requestDataRaw)) {
                    if (is_array($requestDataRaw)) {
                        foreach ($requestDataRaw as $postKey => $postValue) {
                            $keyUnified = strtoupper($postKey);
                            $result[$keyUnified] = $postValue;
                        }
                    }
                }
            }
        }
        return($result);
    }

    /**
     * Saves transaction id to validate some possible duplicates
     * 
     * @return void
     */
    protected function saveTransaction() {
        if (!empty($this->receivedData)) {
            if (isset($this->receivedData['PAY_ID'])) {
                file_put_contents(self::TRANS_PATH . $this->receivedData['PAY_ID'], serialize($this->receivedData));
            }
        }
    }

    /**
     * Checks is transaction already exists or not?
     * 
     * @return bool
     */
    protected function checkTransaction() {
        $result = false;
        if (!empty($this->receivedData)) {
            if (isset($this->receivedData['PAY_ID'])) {
                if (file_exists(self::TRANS_PATH . $this->receivedData['PAY_ID'])) {
                    $result = true;
                }
            }
        }
        return($result);
    }

    /**
     * Check transaction hash for duplicates by returning transaction data if it exists
     * 
     * @param string $transactionHash
     * 
     * @return array
     */
    protected function checkHash($transactionHash) {
        $result = array();
        if (!empty($transactionHash)) {
            $transactionData = simple_query("SELECT * from `op_transactions` WHERE `hash`='" . $transactionHash . "'");
            if (!empty($transactionData)) {
                $result = $transactionData;
            }
        }
        return($result);
    }

    /**
     * Runs some action handler depends on received data action identifier
     * 
     * @return void
     */
    protected function runAction() {
        if (!empty($this->receivedData)) {
            if (isset($this->receivedData['ACT'])) {
                if ($this->validateSign()) {
                    switch ($this->receivedData['ACT']) {
                        case self::ACT_INFO:
                            $this->actionInfo();
                            break;
                        case self::ACT_PAY:
                            $this->actionPay();
                            break;
                        case self::ACT_STATUS:
                            $this->actionStatus();
                            break;
                    }
                } else {
                    $this->replyData = array();
                    $this->replyAdd('status_code', self::REPLY_BADPARAMS);
                    $this->replyAdd('time_stamp', date("d.m.Y H:i:s"));
                    $this->renderReply();
                }
            }
        }
    }

    /**
     * Validates current request sign if required
     * 
     * @return bool
     */
    protected function validateSign() {
        $result = true;
        if ($this->checkSign) {
            if (isset($this->receivedData['SIGN'])) {
                $requestSign = $this->receivedData['SIGN'];
                $validSign = $this->getSign($this->receivedData['ACT'], @$this->receivedData['PAY_ACCOUNT'], $this->receivedData['SERVICE_ID'], $this->receivedData['PAY_ID']);
//                print($validSign.'->');
//                die($requestSign);
                if ($requestSign != $validSign) {
                    $result = false;
                }
            } else {
                $result = false;
            }
        }
        return($result);
    }

    /**
     * Performs transaction status check action
     * 
     * @return void
     */
    protected function actionStatus() {
        if (isset($this->receivedData['PAY_ID'])) {
            $hash = self::HASH_PREFIX . $this->receivedData['PAY_ID'];
            $transactionData = $this->checkHash($hash);
            if (!empty($transactionData)) {
                $this->replyAdd('status_code', self::REPLY_OKTRANS);
                //may be processed little bit later
                if ($transactionData['processed']) {
                    $this->replyTransactionAdd('status', self::STATUS_SUCCESS);
                } else {
                    $this->replyAdd('status', self::STATUS_PROCESSING);
                }

                $this->replyTransactionAdd('pay_id', $this->receivedData['PAY_ID']);
                $this->replyTransactionAdd('service_id', $this->receivedData['SERVICE_ID']);
                $this->replyTransactionAdd('amount', $transactionData['summ']);
                $this->replyTransactionAdd('description', $this->serviceName);
                $transactionTimestamp = strtotime($transactionData['date']);
                $transactionDate = date("d.m.Y H:i:s", $transactionTimestamp);
                $this->replyTransactionAdd('time_stamp', $transactionDate);
            } else {
                $this->replyAdd('status_code', self::REPLY_NOTRANS);
            }
        } else {
            $this->replyAdd('status_code', self::REPLY_NOTRANS);
        }

        $this->replyAdd('time_stamp', date("d.m.Y H:i:s"));

        $this->renderReply();
    }

    /**
     * Performs payment info/check request action
     * 
     * @return void
     */
    protected function actionInfo() {
        if (!$this->checkTransaction()) {
            $allCustomers = op_CustomersGetAll();
            if (isset($allCustomers[$this->receivedData['PAY_ACCOUNT']])) {
                $customerId = $this->receivedData['PAY_ACCOUNT'];
                $userLogin = $allCustomers[$this->receivedData['PAY_ACCOUNT']];
                $userData = simple_query("SELECT * from `users` WHERE `login`='" . $userLogin . "'");

                if (!empty($userData)) {
                    $userBalance = $userData['Cash'];
                    $userTariff = $userData['Tariff'];
                    $tariffData = simple_query("SELECT * from `tariffs` WHERE `name`='" . $userTariff . "'");
                    if (isset($tariffData['Fee'])) {
                        $tariffFee = $tariffData['Fee'];
                    } else {
                        $tariffFee = 0;
                    }
                    $nameData = simple_query("SELECT * from `realname` WHERE `login`='" . $userLogin . "'");
                    $userRealname = @$nameData['realname'];

                    $this->replyAdd('status_code', self::REPLY_POSSIBLE);
                    $this->replyAdd('balance', $userBalance);
                    $this->replyAdd('name', $userRealname);
                    $this->replyAdd('account', $customerId);
                    $this->replyAdd('service_id', $this->receivedData['SERVICE_ID']);
                    $this->replyAdd('abonplata', $tariffFee);
                    $this->replyAdd('min_amount', $this->minAmount);
                    $this->replyAdd('max_amount', $this->maxAmount);
                } else {
                    $this->replyAdd('status_code', self::REPLY_NOCLIENT);
                }
            } else {
                $this->replyAdd('status_code', self::REPLY_NOCLIENT);
            }
        } else {
            $this->replyAdd('status_code', self::REPLY_DUPLICATE);
        }

        $this->replyAdd('time_stamp', date("d.m.Y H:i:s"));
        $this->saveTransaction();

        $this->renderReply();
    }

    /**
     * Performs payment payment request action
     * 
     * @return void
     */
    protected function actionPay() {
        if ($this->checkTransaction()) {
            $allCustomers = op_CustomersGetAll();
            if (isset($allCustomers[$this->receivedData['PAY_ACCOUNT']])) {
                $customerId = $this->receivedData['PAY_ACCOUNT'];
                $userLogin = $allCustomers[$this->receivedData['PAY_ACCOUNT']];
                $userData = simple_query("SELECT * from `users` WHERE `login`='" . $userLogin . "'");

                if (!empty($userData)) {
                    $paymentSumm = $this->receivedData['PAY_AMOUNT'];
                    $hash = self::HASH_PREFIX . $this->receivedData['PAY_ID'];

                    if ($paymentSumm >= $this->minAmount AND $paymentSumm <= $this->maxAmount) {
                        //not duplicate?
                        if (!$this->checkHash($hash)) {
                            //push transaction to database
                            op_TransactionAdd($hash, $paymentSumm, $customerId, self::PSYS, 'no debug info yet');
                            op_ProcessHandlers();

                            //reply construction
                            $this->replyAdd('status_code', self::REPLY_PAYOK);
                            $this->replyAdd('pay_id', $this->receivedData['PAY_ID']);
                            $this->replyAdd('service_id', $this->receivedData['SERVICE_ID']);
                            $this->replyAdd('amount', $paymentSumm);
                            $this->replyAdd('description', $this->serviceName);
                        } else {
                            //transaction duplicate fail here
                            $this->replyAdd('status_code', self::REPLY_BADPARAMS);
                        }
                    } else {
                        $this->replyAdd('status_code', self::REPLY_BADPARAMS);
                    }
                } else {
                    $this->replyAdd('status_code', self::REPLY_NOCLIENT);
                }
            } else {
                $this->replyAdd('status_code', self::REPLY_BADPARAMS);
            }
        } else {
            $this->replyAdd('status_code', self::REPLY_BADPARAMS);
        }
        $this->replyAdd('time_stamp', date("d.m.Y H:i:s"));

        $this->renderReply();
    }

    /**
     * Adds some tag to reply
     * 
     * @param string $tag
     * @param string $value
     * 
     * @return void
     */
    protected function replyAdd($tag, $value = '') {
        if (!empty($tag)) {
            $this->replyData[$tag] = $value;
        }
    }

    /**
     * Adds some tag to transaction reply
     * 
     * @param string $tag
     * @param string $value
     * 
     * @return void
     */
    protected function replyTransactionAdd($tag, $value = '') {
        if (!empty($tag)) {
            $this->replyTransaction[$tag] = $value;
        }
    }

    /**
     * Renders and flushes action reply
     * 
     * @return void
     */
    protected function renderReply() {
        $result = '';
        if (!empty($this->replyData)) {
            $result .= '<?xml version="1.0" encoding="UTF-8" ?>' . "\n";
            $result .= '<pay-response>' . "\n";
            foreach ($this->replyData as $eachTag => $eachValue) {
                if (!empty($eachTag)) {
                    $result .= '<' . $eachTag . '>' . $eachValue . '</' . $eachTag . '>' . "\n";
                }
            }

            if (!empty($this->replyTransaction)) {
                $result .= '<transaction>';
                foreach ($this->replyTransaction as $eachTrTag => $eachTrValue) {
                    if (!empty($eachTrTag)) {
                        $result .= '<' . $eachTrTag . '>' . $eachTrValue . '</' . $eachTrTag . '>' . "\n";
                    }
                }
                $result .= '</transaction>';
            }
            $result .= '</pay-response>';
        }
        $this->replyData = array();
        die($result);
    }

    /**
     * Listen to your heart when he's calling for you
     * Listen to your heart, there's nothing else you can do
     * 
     * @return void
     */
    public function listen() {
        $this->receivedData = $this->getRequestData();
        if (!empty($this->receivedData)) {
            $this->runAction();
        }
    }

    /**
     * Calculates secret sign for request processing
     * 
     * @param int $action 1 - info, 4 - pay, 7 - status
     * @param string $customerId
     * @param string $serviceId
     * @param string $payId
     * 
     * @return string
     */
    protected function getSign($action, $customerId, $serviceId, $payId) {
        switch ($this->receivedData['ACT']) {
            case self::ACT_INFO:
                $sign = $action . "_" . $customerId . "_" . $serviceId . "_" . $payId . "_" . $this->secret;
                break;
            case self::ACT_PAY:
                $sign = $action . "_" . $customerId . "_" . $serviceId . "_" . $payId . "_" . $this->receivedData['PAY_AMOUNT'] . "_" . $this->secret;
                break;
            case self::ACT_STATUS:
                $sign = $action . "__" . $serviceId . "_" . $payId . "_" . $this->secret;
                break;
        }

        if ($this->signMethod == 'md5') {
            $sign = md5($sign);
        }
        if ($this->signMethod == 'sha1') {
            $sign = sha1($sign);
        }

        $result = strtoupper($sign);
        return ($result);
    }

}

$frontend = new TwoClickFrontend();
$frontend->listen();
