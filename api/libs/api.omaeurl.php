<?php

/**
 * TODO: setCookie, setUserAgent, setBasicAuth, setHeader, and mb getHeaders(?)
 * 
 * and responce codes getter like 200 or 302
 * and debug mode required
 * and multform part data etc for sending like real forms
 * and referrers
 */

/**
 * Basic remote URLs interraction class
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
     * Creates new omae wa mou shindeiru instance
     * 
     * @param string $url
     * 
     * @throws Exception
     */
    public function __construct($url = '') {
        if ($this->checkModCurl()) {
            $this->setUrl($url);
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
     * Puts some data into protected postData property for further usage
     * 
     * @param string $field record field name to push data
     * @param string $value field content to push
     * 
     * @return void
     */
    public function postData($field = '', $value = '') {
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
    public function getData($field = '', $value = '') {
        if (!empty($field)) {
            $this->getData[$field] = $value;
        } else {
            $this->flushGetData();
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
            if (!empty($this->getData)) {
                if (strpos($this->url, '?') === false) {
                    $remoteUrl .= '?';
                }
                foreach ($this->getData as $getKey => $getValue) {
                    $remoteUrl .= '&' . $getKey . '=' . $getValue . '&';
                }
            }
            /**
             * Ora ora ora ora ora ora
             */
            $ch = curl_init($remoteUrl);

            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            if (!empty($this->postData)) {
                $postFields = '';
                foreach ($this->postData as $postKey => $postValue) {
                    $postFields .= $postKey . '=' . $postValue . '&';
                }
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
            }
            $result .= curl_exec($ch);
            $this->errorCode = curl_errno($ch);
            $this->errorMessage = curl_error($ch);
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

}
