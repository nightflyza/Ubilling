<?php

/**
 * 2click.money protocol v2.30 OpenPayz frontend
 * https://protocol.2click.money/Content/Documents/2click_protocol_for_providers_service%202.30.pdf
 */
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
     * Config file path
     */
    const CONFIG_PATH = 'config/2click.ini';

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

    public function listen() {
        $this->receivedData = $this->getRequestData();
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
    public function getSign($action, $customerId, $serviceId, $payId) {
        if ($this->signMethod == 'md5') {
            $sign = md5($action . "_" . $customerId . "_" . $serviceId . "_" . $payId . "_" . $this->secret);
        }

        if ($this->signMethod == 'sha1') {
            $sign = sha1($action . "_" . $customerId . "_" . $serviceId . "_" . $payId . "_" . $this->secret);
        }
        $result = strtoupper($sign);
        return ($result);
    }

}

$frontend = new TwoClickFrontend();
$frontend->listen();

print('<pre>');
print_r($frontend);
