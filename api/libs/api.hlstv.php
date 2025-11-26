<?php

/**
 * OmegaTV low-level API implementation
 */
class HlsTV {

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains public key
     *
     * @var string
     */
    protected $publicKey = '';

    /**
     * Contains private key
     *
     * @var string
     */
    protected $privateKey = '';

    /**
     * Current timestamp for all API requests
     *
     * @var int
     */
    protected $currentTimeStamp = 0;

    /**
     * Debug flag
     *
     * @var bool
     */
    protected $debug = false;

    /**
     * Default HLS API URL
     */
    const URL_API = 'https://api.hls.tv/';

    /**
     * Default debug log path
     */
    const LOG_PATH = 'exports/omegatv.log';

    /**
     * Configs options naming
     */
    const OPTION_PUBLIC = 'OMEGATV_PUBLIC_KEY';
    const OPTION_PRIVATE = 'OMEGATV_PRIVATE_KEY';

    /**
     * Creates new low-level API object instance
     * 
     * @return void
     */
    public function __construct() {
        $this->loadConfigs();
        $this->setOptions();
    }

    /**
     * Loads required configs into protected properties for further usage
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfigs() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Sets default options to object instance properties
     * 
     * @return void
     */
    protected function setOptions() {
        if ((isset($this->altCfg[self::OPTION_PUBLIC])) AND ( (isset($this->altCfg[self::OPTION_PRIVATE])))) {
            $this->publicKey = $this->altCfg[self::OPTION_PUBLIC];
            $this->privateKey = $this->altCfg[self::OPTION_PRIVATE];
        }

        if (isset($this->altCfg['OMEGATV_DEBUG'])) {
            if ($this->altCfg['OMEGATV_DEBUG']) {
                $this->debug = true;
            }
        }
        $this->currentTimeStamp = time();
    }

    /**
     * Returns new API_HASH for some message
     * 
     * @param array $message
     * 
     * @return string
     */
    protected function generateApiHash($message = array()) {
        $message = $this->currentTimeStamp . $this->publicKey . http_build_query($message, '', '&');
        $result = hash_hmac('sha256', $message, $this->privateKey);
        return ($result);
    }

    /**
     * Pushes some request to remote API and returns decoded array or raw JSON reply.
     * 
     * @param string $request
     * @param array  $data
     * @param bool $raw
     * 
     * @return array/json
     */
    public function pushApiRequest($request, $data = array(), $raw = false) {
        if ($this->debug) {
            file_put_contents(self::LOG_PATH, curdatetime() . "\n", FILE_APPEND);
            file_put_contents(self::LOG_PATH, '>>>>>QUERY>>>>>' . "\n", FILE_APPEND);
            file_put_contents(self::LOG_PATH, print_r($request, true) . "\n", FILE_APPEND);
            file_put_contents(self::LOG_PATH, print_r($data, true) . "\n", FILE_APPEND);
        }

        $curl = curl_init(self::URL_API . $request);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'API_ID: ' . $this->publicKey,
            'API_TIME: ' . $this->currentTimeStamp,
            'API_HASH:' . $this->generateApiHash($data)
        ));
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        $jsonResponse = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($status != 200) {
            show_error('Error: call to URL ' . self::URL_API . ' failed with status ' . $status . ', response ' . $jsonResponse . ', curl_error ' . curl_error($curl) . ', curl_errno ' . curl_errno($curl));
        }
        //PHP 8.0+ has no need to close curl resource anymore
        if (PHP_VERSION_ID < 80000) {
            curl_close($curl); // Deprecated in PHP 8.5
        }
        if (!$raw) {
            $result = json_decode($jsonResponse, true);
        } else {
            $result = $jsonResponse;
        }

        if ($this->debug) {
            file_put_contents(self::LOG_PATH, '<<<<<RESPONSE<<<<<' . "\n", FILE_APPEND);
            file_put_contents(self::LOG_PATH, print_r($result, true) . "\n", FILE_APPEND);
            file_put_contents(self::LOG_PATH, '==================' . "\n", FILE_APPEND);
        }
        return ($result);
    }

    /**
     * Returns list of promo tariffs
     * 
     * @return array
     */
    public function getTariffsPromo() {
        $result = $this->pushApiRequest('tariff/promo/list');
        return ($result);
    }

    /**
     * Returns list of main tariffs
     * 
     * @return array
     */
    public function getTariffsBase() {
        $result = $this->pushApiRequest('tariff/base/list');
        return ($result);
    }

    /**
     * Returns list of bundle tariffs
     * 
     * @return array
     */
    public function getTariffsBundle() {
        $result = $this->pushApiRequest('tariff/bundle/list');
        return ($result);
    }

    /**
     * Get all user info.
     * 
     * @param int $customerId Unique user ID
     * 
     * @return array
     */
    public function getUserInfo($customerId) {
        $result = $this->pushApiRequest('customer/get', array('customer_id' => $customerId));
        return ($result);
    }

    /**
     * Sets base tariff or some additional tariffs
     * 
     * @param int $customerId unique user ID
     * @param array $tariffs example: array('base' =>1036, 'bundle' => 1046)
     * 
     * @return array
     */
    public function setUserTariff($customerId, $tariffs) {
        $data = array('customer_id' => $customerId);
        if (!empty($tariffs)) {
            foreach ($tariffs as $io => $each) {
                $data[$io] = $each;
            }
        }
        $result = $this->pushApiRequest('customer/tariff/set', $data);
        return ($result);
    }

    /**
     * Sets user as blocked
     * 
     * @param int $customerId
     * 
     * @return array
     */
    public function setUserBlock($customerId) {
        $result = $this->pushApiRequest('customer/block', array('customer_id' => $customerId));
        return ($result);
    }

    /**
     * Sets user as unblocked
     * 
     * @param int $customerId
     * 
     * @return array
     */
    public function setUserActivate($customerId) {
        $result = $this->pushApiRequest('customer/activate', array('customer_id' => $customerId));
        return ($result);
    }

    /**
     * Returns user device activation code
     * 
     * @param int $customerId
     *      
     * @return array
     */
    public function getDeviceCode($customerId) {
        $result = $this->pushApiRequest('customer/device/get_code', array('customer_id' => $customerId));
        return ($result);
    }

    /**
     * Removes user device
     * 
     * @param int $customerId
     * @param string $deviceId
     * 
     * @return array
     */
    public function deleteDevice($customerId, $deviceId) {
        $result = $this->pushApiRequest('customer/device/remove', array('customer_id' => $customerId, 'uniq' => $deviceId));
        return ($result);
    }

    /**
     * Adds user device
     * 
     * @param int $customerId
     * @param string $deviceId
     * 
     * @return array
     */
    public function addDevice($customerId, $deviceId) {
        $result = $this->pushApiRequest('customer/device/add', array('uniq' => $deviceId, 'customer_id' => $customerId));
        return ($result);
    }

    /**
     * Returns list of all devices of company
     * 
     * @return array
     */
    public function getDeviceList() {
        $result = array();
        $tmp = $this->pushApiRequest('device/list');

        //devices is now in items key
        if (isset($tmp['result'])) {
            if (isset($tmp['result']['items'])) {
                if (!empty($tmp['result']['items'])) {
                    foreach ($tmp['result']['items'] as $io => $each) {
                        $result['result'][] = $each;
                    }
                }
            }
        }

        //shitty pagination processing here
        if (isset($tmp['result']['pages_count'])) {
            if ($tmp['result']['pages_count'] > 1) {
                $pagesCount = $tmp['result']['pages_count'];
                for ($i = 1; $i <= $pagesCount; $i++) {
                    $tmp = $this->pushApiRequest('device/list', array('page' => $i));
                    if (isset($tmp['result']['items'])) {
                        if (!empty($tmp['result']['items'])) {
                            foreach ($tmp['result']['items'] as $io => $each) {
                                $result['result'][] = $each;
                            }
                        }
                    }
                }
            }
        }

        return ($result);
    }

    /**
     * Assigns new playlist to some customer
     * 
     * @param int $customerId
     * 
     * @return array
     */
    public function addPlayList($customerId) {
        $result = $this->pushApiRequest('customer/url/add', array('customer_id' => $customerId));
        return ($result);
    }

    /**
     * Deletes playlist by its uniq
     * 
     * @param int $customerId
     * @param string $playlistId 
     * 
     * @return array
     */
    public function deletePlayList($customerId, $playlistId) {
        $result = $this->pushApiRequest('customer/url/remove', array('customer_id' => $customerId, 'uniq' => $playlistId));
        return ($result);
    }

}
