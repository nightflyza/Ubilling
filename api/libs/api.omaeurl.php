<?php

/**
 * Basic remote URLs interaction class
 */
class OmaeUrl {

    /**
     * Contains current instance URL
     *
     * @var string
     */
    protected $url = '';

    /**
     * Contains default connection timeout in seconds
     *
     * @var int
     */
    protected $timeout = 2;

    /**
     * Last curl error description
     *
     * @var string
     */
    protected $errorMessage = '';

    /**
     * Last curl error code
     *
     * @var int
     */
    protected $errorCode = 0;

    /**
     * Contains last request http code
     *
     * @var int
     */
    protected $httpCode = 0;

    /**
     * Contains last request curl info array or false on error
     *
     * @var array/bool
     */
    protected $lastRequestInfo = array();

    /**
     * Is error happens flag
     *
     * @var bool
     */
    protected $error = false;

    /**
     * Contains post data array that will be pushed to remote URL
     * 
     *
     * @var array
     */
    protected $postData = array();

    /**
     * Contains get data that will be mixed into URL on requests
     *
     * @var array
     */
    protected $getData = array();

    /**
     * Contains cookie data as cookiename=>data
     *
     * @var array
     */
    protected $cookieData = array();

    /**
     * Contains current instance headers as headername=>value
     *
     * @var array
     */
    protected $headersData = array();

    /**
     * Contains default user agent
     *
     * @var string
     */
    protected $userAgent = '';

    /**
     * Contains current instance curl options array as option=>value
     *
     * @var array
     */
    protected $curlOpts = array();

    /**
     * Get headers flag
     *
     * @var bool
     */
    protected $headersFlag = false;

    /**
     * Request referrer
     *
     * @var string
     */
    protected $referrer = '';

    /**
     * Creates new omae wa mou shindeiru instance
     * 
     * @param string $url
     * 
     * @throws Exception
     */
    public function __construct($url = '') {
        if ($this->checkModCurl()) {
            $this->setUrl($url);
            $this->loadOpts();
        } else {
            throw new Exception('SHINDEIRU_NO_CURL_EXTENSION');
        }
    }

    /**
     * Sets instance URL
     * 
     * @param string $url
     * 
     * @return void
     */
    protected function setUrl($url = '') {
        $this->url = $url;
    }

    /**
     * Checks is curl PHP extension loaded?
     * 
     * @return bool
     */
    protected function checkModCurl() {
        $result = true;
        if (!extension_loaded('curl')) {
            $result = false;
        }
        return($result);
    }

    /**
     * Sets return headers flag
     * 
     * @param bool $state
     * 
     * @return void
     */
    public function setHeadersReturn($state) {
        $this->headersFlag = $state;
        $this->setOpt(CURLOPT_HEADER, $this->headersFlag);
    }

    /**
     * Sets instance referrer URL
     * 
     * @param string $url
     * 
     * @return void
     */
    public function setReferrer($url) {
        $this->referrer = $url;
        $this->setOpt(CURLOPT_REFERER, $this->referrer);
    }

    /**
     * Sets default instance curl options
     * 
     * @return void
     */
    protected function loadOpts() {
        $this->setOpt(CURLOPT_CONNECTTIMEOUT, $this->timeout);
        $this->setOpt(CURLOPT_HEADER, false);
        $this->setOpt(CURLOPT_FOLLOWLOCATION, true);
        $this->setOpt(CURLOPT_MAXREDIRS, 10);
        $this->setOpt(CURLOPT_SSL_VERIFYHOST, false);
        $this->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $this->setOpt(CURLOPT_RETURNTRANSFER, true);
    }

    /**
     * Puts some data into protected postData property for further usage
     * 
     * @param string $field record field name to push data
     * @param string $value field content to push
     * 
     * @return void
     */
    public function dataPost($field = '', $value = '') {
        if (!empty($field)) {
            $this->postData[$field] = $value;
        } else {
            $this->flushPostData();
        }
    }

    /**
     * Puts some data into protected getData property for further usage
     * 
     * @param string $field record field name to push data
     * @param string $value field content to push
     * 
     * @return void
     */
    public function dataGet($field = '', $value = '') {
        if (!empty($field)) {
            $this->getData[$field] = $value;
        } else {
            $this->flushGetData();
        }
    }

    /**
     * Puts some data into protected cookieData property for further usage
     * 
     * @param string $name record field name to push data
     * @param string $value field content to push
     * 
     * @return void
     */
    public function dataCookie($name = '', $value = '') {
        if (!empty($name)) {
            $this->cookieData[$name] = $value;
        } else {
            $this->flushCookieData();
        }
    }

    /**
     * Puts some data into protected headersData property for further usage
     * 
     * @param string $name record field name to push data
     * @param string $value field content to push
     * 
     * @return void
     */
    public function dataHeader($name = '', $value = '') {
        if (!empty($name)) {
            $this->headersData[$name] = $value;
        } else {
            $this->flushHeadersData();
        }
    }

    /**
     * Flushes current instance postData set
     * 
     * @return void
     */
    protected function flushPostData() {
        $this->postData = array();
    }

    /**
     * Flushes current instance getData set
     * 
     * @return void
     */
    protected function flushGetData() {
        $this->getData = array();
    }

    /**
     * Flushes current instance cookieData set
     * 
     * @return void
     */
    protected function flushCookieData() {
        $this->cookieData = array();
    }

    /**
     * Flushes current instance headersData set
     * 
     * @return void
     */
    protected function flushHeadersData() {
        $this->headersData = array();
    }

    /**
     * Sets curl resource option for further usage
     * 
     * @param string $option
     * @param mixed $value
     * 
     * @return void
     */
    public function setOpt($option, $value) {
        $this->curlOpts[$option] = $value;
    }

    /**
     * Returns some data from remote source URL
     * 
     * @return string
     * 
     * @throws Exception
     */
    public function response($url = '') {
        $result = '';
        if (!empty($url)) {
            $this->setUrl($url);
        }

        if (!empty($this->url)) {
            $remoteUrl = $this->url;
            //appending GET vars to URL
            if (!empty($this->getData)) {
                if (strpos($this->url, '?') === false) {
                    $remoteUrl .= '?';
                }
                foreach ($this->getData as $getKey => $getValue) {
                    $remoteUrl .= '&' . $getKey . '=' . $getValue . '&';
                }
            }

            //appending POST vars into options
            if (!empty($this->postData)) {
                $postFields = '';
                foreach ($this->postData as $postKey => $postValue) {
                    $postFields .= $postKey . '=' . $postValue . '&';
                }
                $this->setOpt(CURLOPT_POSTFIELDS, $postFields);
            }

            //appending cookie data into options
            if (!empty($this->cookieData)) {
                $this->setOpt(CURLOPT_COOKIE, implode('; ', array_map(function ($k, $v) {
                                    return $k . '=' . $v;
                                }, array_keys($this->cookieData), array_values($this->cookieData))));
            }

            //and some custom headers
            if (!empty($this->headersData)) {
                $headersTmp = array();
                foreach ($this->headersData as $headerKey => $headerValue) {
                    $headersTmp[] = $headerKey . ':' . $headerValue;
                }
                $this->setOpt(CURLOPT_HTTPHEADER, $headersTmp);
            }

            /**
             * Ora ora ora ora ora ora
             */
            $ch = curl_init($remoteUrl);
            //setting resource options before exec
            if (!empty($this->curlOpts)) {
                curl_setopt_array($ch, $this->curlOpts);
            }
            //executing request
            $result .= curl_exec($ch);
            $this->errorCode = curl_errno($ch);
            $this->errorMessage = curl_error($ch);
            $this->lastRequestInfo = curl_getinfo($ch);
            if (is_array($this->lastRequestInfo)) {
                $this->httpCode = $this->lastRequestInfo['http_code'];
            }
            if ($this->errorCode OR $this->errorMessage) {
                $this->error = true;
            }
            curl_close($ch);
        } else {
            throw new Exception('SHINDEIRU_URL_EMPTY');
        }
        return($result);
    }

    /**
     * Returns current error state as empty or not array
     * 
     * @return array
     */
    public function error() {
        $result = array();
        if ($this->error) {
            $result['errorcode'] = $this->errorCode;
            $result['errormessage'] = $this->errorMessage;
        }
        return($result);
    }

    /**
     * Returns last request http code. 0 - on fail.
     * 
     * @return int
     */
    public function httpCode() {
        return($this->httpCode);
    }

    /**
     * Returns last request full info
     * 
     * @return array/bool
     */
    public function lastRequestInfo() {
        return($this->lastRequestInfo);
    }

    /**
     * Sets user agent for current instance
     * 
     * @param string $userAgent
     * 
     * @return void
     */
    public function setUserAgent($userAgent) {
        if (!empty($userAgent)) {
            $this->userAgent = $userAgent;
            $this->setOpt(CURLOPT_USERAGENT, $this->userAgent);
        }
    }

    /**
     * Sets instance connection timeout in seconds
     * 
     * @param int $timeout
     * 
     * @return void
     */
    public function setTimeout($timeout) {
        $timeout = preg_replace("#[^0-9]#Uis", '', $timeout);
        if (!empty($timeout)) {
            $this->timeout = $timeout;
            $this->setOpt(CURLOPT_CONNECTTIMEOUT, $this->timeout);
        }
    }

    /**
     * Sets HTTP basic auth params
     * 
     * @param string $login
     * @param string $password
     * 
     * @return void
     */
    public function setBasicAuth($login, $password) {
        $this->setOpt(CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        $this->setOpt(CURLOPT_USERPWD, $login . ':' . $password);
    }

}
