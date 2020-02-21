<?php

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
     * 
     *
     * @var array
     */
    protected $postData = array();

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
    public function setUrl($url = '') {
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
            $this->flushData();
        }
    }

    /**
     * Flushes current instance postData set
     * 
     * @return void
     */
    protected function flushData() {
        $this->postData = array();
    }

    /**
     * Returns some data from remote source URL
     * 
     * @return string
     * 
     * @throws Exception
     */
    public function get($url = '') {
        $result = '';
        if (!empty($url)) {
            $this->setUrl($url);
        }
        if (!empty($this->url)) {
            $ch = curl_init($this->url);

            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            if (!empty($this->postData)) {
                $postFields = array();
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
