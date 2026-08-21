<?php

/**
 * Minimalist DeyeCloud OpenAPI client for inverter monitoring
 * 
 * API documentation: https://developer.deyecloud.com/api
 * Application ID and secret are required: https://developer.deyecloud.com/app
  */
class DeyeCloud {

    /**
     * OpenAPI application ID
     *
     * @var string
     */
    protected $appId = '';

    /**
     * OpenAPI application secret
     *
     * @var string
     */
    protected $appSecret = '';

    /**
     * Regional API base URL without trailing slash
     *
     * @var string
     */
    protected $baseUrl = '';

    /**
     * Account email for token obtain
     *
     * @var string
     */
    protected $email = '';

    /**
     * Account username for token obtain
     *
     * @var string
     */
    protected $username = '';

    /**
     * Account mobile number for token obtain
     *
     * @var string
     */
    protected $mobile = '';

    /**
     * Country calling code without plus for mobile login
     *
     * @var string
     */
    protected $countryCode = '';

    /**
     * Plain account password (hashed as SHA-256 before request)
     *
     * @var string
     */
    protected $password = '';

    /**
     * Optional company ID for business member token
     *
     * @var string
     */
    protected $companyId = '';

    /**
     * Current access token
     *
     * @var string
     */
    protected $token = '';

    /**
     * Last HTTP status code
     *
     * @var int
     */
    protected $httpCode = 0;

    /**
     * Last transport error message
     *
     * @var string
     */
    protected $errorMessage = '';

    /**
     * Last decoded API response
     *
     * @var array
     */
    protected $lastResponse = array();

    /**
     * Request timeout in seconds
     *
     * @var int
     */
    protected $timeout = 15;

    /**
     * Max devices per /device/latest batch
     */
    const LATEST_BATCH_SIZE = 10;

    /**
     * Default EU developer API endpoint
     */
    const BASE_URL_EU = 'https://eu1-developer.deyecloud.com/v1.0';

    /**
     * Default US developer API endpoint
     */
    const BASE_URL_US = 'https://us1-developer.deyecloud.com/v1.0';

    /**
     * Deye success business codes (API variants)
     */
    const CODE_OK = '1000000';
    const CODE_OK_ALT = '100000';

    /**
     * Creates new DeyeCloud API instance
     *
     * @param string $appId
     * @param string $appSecret
     * @param string $baseUrl
     */
    public function __construct($appId = '', $appSecret = '', $baseUrl = '') {
        $this->setAppId($appId);
        $this->setAppSecret($appSecret);
        if (!empty($baseUrl)) {
            $this->setBaseUrl($baseUrl);
        } else {
            $this->setBaseUrl(self::BASE_URL_EU);
        }
    }

    /**
     * Sets OpenAPI appId
     *
     * @param string $appId
     *
     * @return void
     */
    public function setAppId($appId) {
        $this->appId = $appId;
    }

    /**
     * Sets OpenAPI appSecret
     *
     * @param string $appSecret
     *
     * @return void
     */
    public function setAppSecret($appSecret) {
        $this->appSecret = $appSecret;
    }

    /**
     * Sets regional API base URL
     *
     * @param string $baseUrl
     *
     * @return void
     */
    public function setBaseUrl($baseUrl) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * Sets account email for token obtain
     *
     * @param string $email
     *
     * @return void
     */
    public function setEmail($email) {
        $this->email = $email;
    }

    /**
     * Sets account username for token obtain
     *
     * @param string $username
     *
     * @return void
     */
    public function setUsername($username) {
        $this->username = $username;
    }

    /**
     * Sets mobile login credentials
     *
     * @param string $mobile
     * @param string $countryCode
     *
     * @return void
     */
    public function setMobile($mobile, $countryCode = '') {
        $this->mobile = $mobile;
        if ($countryCode !== '') {
            $this->countryCode = $countryCode;
        }
    }

    /**
     * Sets plain account password
     *
     * @param string $password
     *
     * @return void
     */
    public function setPassword($password) {
        $this->password = $password;
    }

    /**
     * Sets optional company ID for business member token
     *
     * @param string $companyId
     *
     * @return void
     */
    public function setCompanyId($companyId) {
        $this->companyId = $companyId;
    }

    /**
     * Sets already obtained access token
     *
     * @param string $token
     *
     * @return void
     */
    public function setToken($token) {
        $this->token = $token;
    }

    /**
     * Returns current access token
     *
     * @return string
     */
    public function getToken() {
        $result = $this->token;
        return ($result);
    }

    /**
     * Sets HTTP request timeout in seconds
     *
     * @param int $timeout
     *
     * @return void
     */
    public function setTimeout($timeout) {
        $timeout = preg_replace('#[^0-9]#Uis', '', $timeout);
        if (!empty($timeout)) {
            $this->timeout = $timeout;
        }
    }

    /**
     * Returns last HTTP code
     *
     * @return int
     */
    public function getHttpCode() {
        $result = $this->httpCode;
        return ($result);
    }

    /**
     * Returns last transport or API error message
     *
     * @return string
     */
    public function getError() {
        $result = $this->errorMessage;
        return ($result);
    }

    /**
     * Returns last decoded API response
     *
     * @return array
     */
    public function getLastResponse() {
        $result = $this->lastResponse;
        return ($result);
    }

    /**
     * Checks whether last API business code was successful
     *
     * @return bool
     */
    public function isOk() {
        $result = false;
        if (!empty($this->lastResponse)) {
            if (isset($this->lastResponse['success']) and $this->lastResponse['success']) {
                $result = true;
            } else {
                if (isset($this->lastResponse['code'])) {
                    $code = strval($this->lastResponse['code']);
                    if ($code == self::CODE_OK or $code == self::CODE_OK_ALT) {
                        $result = true;
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Obtains access token via /account/token
     * One of email, username or mobile+countryCode is required.
     *
     * @return string
     */
    public function obtainToken() {
        $result = '';
        $this->errorMessage = '';
        $payload = array(
            'appSecret' => $this->appSecret,
            'password' => hash('sha256', $this->password)
        );

        if (!empty($this->email)) {
            $payload['email'] = $this->email;
        } else {
            if (!empty($this->username)) {
                $payload['username'] = $this->username;
            } else {
                if (!empty($this->mobile)) {
                    $payload['mobile'] = $this->mobile;
                    $payload['countryCode'] = $this->countryCode;
                }
            }
        }

        if ($this->companyId !== '') {
            $payload['companyId'] = $this->companyId;
        }

        if (empty($this->appId) or empty($this->appSecret) or empty($this->password)) {
            $this->errorMessage = 'EX_DEYE_CREDENTIALS_EMPTY';
        } else {
            if (empty($this->email) and empty($this->username) and empty($this->mobile)) {
                $this->errorMessage = 'EX_DEYE_LOGIN_EMPTY';
            } else {
                $endpoint = '/account/token?appId=' . urlencode($this->appId);
                $response = $this->apiRequest($endpoint, $payload, false);
                $accessToken = $this->extractAccessToken($response);
                if ($this->isOk() and !empty($accessToken)) {
                    $this->token = $accessToken;
                    $result = $this->token;
                } else {
                    if (empty($this->errorMessage)) {
                        if ($this->isOk() and empty($accessToken)) {
                            $this->errorMessage = 'EX_DEYE_TOKEN_MISSING';
                        } else {
                            $this->errorMessage = $this->extractApiError($response);
                        }
                    }
                }
            }
        }

        return ($result);
    }

    /**
     * Returns station list
     *
     * @param int $page
     * @param int $size
     *
     * @return array
     */
    public function getStations($page = 1, $size = 10) {
        $result = array();
        $payload = array(
            'page' => intval($page),
            'size' => intval($size)
        );
        $response = $this->apiRequest('/station/list', $payload, true);
        if ($this->isOk()) {
            $result = $this->extractListField($response, 'stationList');
        } else {
            if (empty($this->errorMessage)) {
                $this->errorMessage = $this->extractApiError($response);
            }
        }
        return ($result);
    }

    /**
     * Returns station list with devices
     * deviceType examples: INVERTER, MICRO_INVERTER, COLLECTOR, BATTERY, METER
     *
     * @param int $page
     * @param int $size
     * @param string $deviceType
     *
     * @return array
     */
    public function getStationsWithDevices($page = 1, $size = 10, $deviceType = 'INVERTER') {
        $result = array();
        $payload = array(
            'page' => intval($page),
            'size' => intval($size),
            'deviceType' => $deviceType
        );
        $response = $this->apiRequest('/station/listWithDevice', $payload, true);
        if ($this->isOk()) {
            $result = $this->extractListField($response, 'stationList');
        } else {
            if (empty($this->errorMessage)) {
                $this->errorMessage = $this->extractApiError($response);
            }
        }
        return ($result);
    }

    /**
     * Returns devices for one or several stations
     *
     * @param array|int|string $stationIds
     * @param int $page
     * @param int $size
     *
     * @return array
     */
    public function getStationDevices($stationIds, $page = 1, $size = 10) {
        $result = array();
        $ids = $stationIds;
        if (!is_array($ids)) {
            $ids = array($ids);
        }
        $payload = array(
            'page' => intval($page),
            'size' => intval($size),
            'stationIds' => $ids
        );
        $response = $this->apiRequest('/station/device', $payload, true);
        if ($this->isOk()) {
            $result = $this->extractListField($response, 'deviceListItems');
            if (empty($result)) {
                $result = $this->extractListField($response, 'deviceList');
            }
        } else {
            if (empty($this->errorMessage)) {
                $this->errorMessage = $this->extractApiError($response);
            }
        }
        return ($result);
    }

    /**
     * Returns device list for business members
     *
     * @param int $page
     * @param int $size
     *
     * @return array
     */
    public function getDevices($page = 1, $size = 20) {
        $result = array();
        $payload = array(
            'page' => intval($page),
            'size' => intval($size)
        );
        $response = $this->apiRequest('/device/list', $payload, true);
        if ($this->isOk()) {
            $result = $this->extractListField($response, 'deviceListItems');
            if (empty($result)) {
                $result = $this->extractListField($response, 'deviceList');
            }
        } else {
            if (empty($this->errorMessage)) {
                $this->errorMessage = $this->extractApiError($response);
            }
        }
        return ($result);
    }

    /**
     * Returns latest telemetry for one or more device serial numbers
     * Automatically splits requests into batches of 10.
     *
     * @param array|string $deviceList
     *
     * @return array
     */
    public function getLatest($deviceList) {
        $result = array();
        $serials = $deviceList;
        if (!is_array($serials)) {
            $serials = array($serials);
        }

        if (empty($serials)) {
            $this->errorMessage = 'EX_DEYE_DEVICE_LIST_EMPTY';
        } else {
            $chunks = array_chunk(array_values($serials), self::LATEST_BATCH_SIZE);
            $merged = array();
            $failed = false;
            foreach ($chunks as $chunk) {
                if (!$failed) {
                    $payload = array(
                        'deviceList' => $chunk
                    );
                    $response = $this->apiRequest('/device/latest', $payload, true);
                    if ($this->isOk()) {
                        $batchDevices = $this->extractDeviceList($response);
                        foreach ($batchDevices as $eachDevice) {
                            $merged[] = $eachDevice;
                        }
                    } else {
                        $failed = true;
                        if (empty($this->errorMessage)) {
                            $this->errorMessage = $this->extractApiError($response);
                        }
                    }
                }
            }
            if (!$failed) {
                $result = $merged;
            }
        }

        return ($result);
    }

    /**
     * Performs JSON POST request via OmaeUrl
     *
     * @param string $endpoint
     * @param array $payload
     * @param bool $authRequired
     *
     * @return array
     */
    protected function apiRequest($endpoint, $payload = array(), $authRequired = true) {
        $result = array();
        $this->httpCode = 0;
        $this->errorMessage = '';
        $this->lastResponse = array();

        if ($authRequired and empty($this->token)) {
            $this->errorMessage = 'EX_DEYE_TOKEN_EMPTY';
        } else {
            $url = $this->baseUrl . $endpoint;
            $remote = new OmaeUrl($url);
            $remote->setTimeout($this->timeout);
            $remote->setUserAgent('UbillingDeyeCloud');
            $remote->setOpt(CURLOPT_POST, true);
            $remote->dataHeader('Content-Type', 'application/json');
            if ($authRequired) {
                $remote->dataHeader('Authorization', 'bearer ' . $this->token);
            }
            $remote->dataPostRaw(json_encode($payload));

            $rawReply = $remote->response();
            $this->httpCode = $remote->httpCode();
            $transportError = $remote->error();
            if (!empty($transportError)) {
                if (isset($transportError['errormessage'])) {
                    $this->errorMessage = $transportError['errormessage'];
                }
            }

            if (!empty($rawReply)) {
                $decoded = json_decode($rawReply, true);
                if (is_array($decoded)) {
                    $this->lastResponse = $decoded;
                    $result = $decoded;
                } else {
                    $this->errorMessage = 'EX_DEYE_JSON_DECODE';
                }
            } else {
                if (empty($this->errorMessage)) {
                    $this->errorMessage = 'EX_DEYE_EMPTY_REPLY';
                }
            }
        }

        return ($result);
    }

    /**
     * Extracts named list field from API response root or data wrapper
     *
     * @param array $response
     * @param string $fieldName
     *
     * @return array
     */
    protected function extractListField($response, $fieldName) {
        $result = array();
        if (is_array($response)) {
            if (isset($response[$fieldName]) and is_array($response[$fieldName])) {
                $result = $response[$fieldName];
            } else {
                if (isset($response['data'][$fieldName]) and is_array($response['data'][$fieldName])) {
                    $result = $response['data'][$fieldName];
                } else {
                    if (isset($response['data']) and is_array($response['data'])) {
                        if (isset($response['data'][0]) or empty($response['data'])) {
                            $result = $response['data'];
                        }
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Extracts access token from /account/token response variants
     *
     * @param array $response
     *
     * @return string
     */
    protected function extractAccessToken($response) {
        $result = '';
        if (is_array($response)) {
            if (isset($response['accessToken']) and $response['accessToken'] !== '') {
                $result = $response['accessToken'];
            } else {
                if (isset($response['data']['accessToken']) and $response['data']['accessToken'] !== '') {
                    $result = $response['data']['accessToken'];
                }
            }
        }
        return ($result);
    }

    /**
     * Extracts device records from /device/latest response variants
     *
     * @param array $response
     *
     * @return array
     */
    protected function extractDeviceList($response) {
        $result = array();
        if (is_array($response)) {
            if (isset($response['deviceDataList']) and is_array($response['deviceDataList'])) {
                $result = $response['deviceDataList'];
            } else {
                if (isset($response['data']['deviceDataList']) and is_array($response['data']['deviceDataList'])) {
                    $result = $response['data']['deviceDataList'];
                } else {
                    if (isset($response['data']) and is_array($response['data'])) {
                        if (isset($response['data'][0]) or empty($response['data'])) {
                            $result = $response['data'];
                        }
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Extracts human-readable error from API response
     *
     * @param array $response
     *
     * @return string
     */
    protected function extractApiError($response) {
        $result = 'EX_DEYE_API_ERROR';
        if (is_array($response)) {
            if (isset($response['msg']) and $response['msg'] !== '' and strtolower($response['msg']) != 'success') {
                $result = $response['msg'];
            } else {
                if (isset($response['code']) and $response['code'] !== '') {
                    $result = 'EX_DEYE_API_CODE_' . $response['code'];
                }
            }
        }
        return ($result);
    }
}
