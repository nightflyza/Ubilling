<?php

/**
 * Class ZabbixAPI
 *
 * Zabbix 3.xx API implementation
 */
class ZabbixAPI {
    /**
     * Placeholder for $this->ubConfig object
     *
     * @var object
     */
    protected $ubConfig = null;

    /**
     * Zabbix host URL/IP
     *
     * @var string
     */
    protected $apiHostURL = '';

    /**
     * Zabbix login
     *
     * @var string
     */
    protected $authLogin = '';

    /**
     * Zabbix password
     *
     * @var string
     */
    protected $authPasswd = '';

    /**
     * Zabbix connection token for communication after successful auth
     *
     * @var string
     */
    protected $authToken = '';


    public function __construct() {
        global $ubillingConfig;
        $this->ubConfig = $ubillingConfig;

        $this->apiHostURL    = ($this->ubConfig->getAlterParam('ZABBIX_HOST_URL')) ? rtrim($this->ubConfig->getAlterParam('ZABBIX_HOST_URL'), '/') . '/api_jsonrpc.php' : '';
        $this->authLogin  = ($this->ubConfig->getAlterParam('ZABBIX_LOGIN')) ? $this->ubConfig->getAlterParam('ZABBIX_LOGIN') : '';
        $this->authPasswd = ($this->ubConfig->getAlterParam('ZABBIX_PASSWD')) ? $this->ubConfig->getAlterParam('ZABBIX_PASSWD') : '';

        $this->getConnectionToken();
    }

    /**
     * Connects to Zabbix host and returns a token string if successful
     *
     * @return string
     */
    public function getConnectionToken() {
        if (!empty($this->apiHostURL) and !empty($this->authLogin) and !empty($this->authPasswd)) {
            $authJSON = '{  
                            "jsonrpc": "2.0", 
                            "method": "user.login",
                            "params": {
                                        "user": "' . $this->authLogin . '",
                                        "password": "' . $this->authPasswd . '"
                                      },
                            "id": 0,
                            "auth": null
                         }';

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_URL, $this->apiHostURL);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json-rpc; charset=utf-8", "Cache-Control: no-cache"));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $authJSON);
            $curlResult = curl_exec($curl);
            //PHP 8.0+ has no need to close curl resource anymore
            if (PHP_VERSION_ID < 80000) {
                curl_close($curl); // Deprecated in PHP 8.5
            }

            if (!empty($curlResult)) {
                $authArr = json_decode($curlResult, true);
                $this->authToken = $authArr['result'];
            }
        }

        return ($this->authToken);
    }

    /**
     * Runs a data query to Zabbix host and returns result as a JSON string
     *
     * @param $apiMethod
     * @param array|string $methodParams
     * @param string $authToken
     * @param int $reqID
     *
     * @return string
     */
    public function runQuery($apiMethod, $methodParams = array(), $authToken = '', $reqID = 0) {
        $reqResult = '';
        $reqParams = (is_array($methodParams)) ? json_encode($methodParams) : $methodParams;
        $authToken = (empty($authToken)) ? $this->authToken : $authToken;

        $requestJSON =  '{
                            "jsonrpc": "2.0",
                            "method": "' . $apiMethod . '",
                            "params": ' . $reqParams . ',
                            "auth": "' . $authToken . '",
                            "id": ' . $reqID . '
                         }';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_URL, $this->apiHostURL);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json-rpc; charset=utf-8", "Cache-Control: no-cache"));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $requestJSON);
        $curlResult = curl_exec($curl);
        //PHP 8.0+ has no need to close curl resource anymore
        if (PHP_VERSION_ID < 80000) {
            curl_close($curl); // Deprecated in PHP 8.5
        }

        if (!empty($curlResult)) {
            $reqResult = $curlResult;
        }

        return ($reqResult);
    }

    /**
     * Auth token getter
     *
     * @return string
     */
    public function getAuthToken() {
        return ($this->authToken);
    }
}