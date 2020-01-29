<?php

class TrassirServer {

    /**
     * Object instance debug flag
     *
     * @var bool
     */
    protected $debug = false;

    /**
     * Contains current instance Trassir Server hostname
     * 
     * @var string $name
     */
    protected $name = '';

    /**
     * Contains IP address of Trassir server host
     * 
     * @var string|null $ip
     */
    protected $ip = null;

    /**
     * Basic transport protocol for Trassir SDK interraction
     *
     * @var string
     */
    protected $sdkProtocol = 'https';

    /**
     * Contains Trassir server HTTPS port for further API requests
     *
     * @var int
     */
    protected $port = 8080;

    /**
     * Contains current instance GUID
     * 
     * @var string $guid
     */
    protected $guid = '';

    /**
     * Current instance session ID
     * 
     * @var string|false $sid
     */
    protected $sid = false;

    /**
     * Username for connecting Trassir Server NVR
     * 
     * @var string $userName
     */
    protected $userName = '';

    /**
     * Password for connecting Trassir Server NVR
     * 
     * @var string $password
     */
    protected $password = '';

    /**
     * Trassir SDK API key
     * 
     * @var string $sdkPassword
     */
    protected $sdkPassword;

    /**
     * Caching object placeholder
     *
     * @var object
     */
    protected $cache = '';

    /**
     * Session caching interval in seconds
     *
     * @var int
     */
    protected $cacheTimeout = 900; //15 mins

    /**
     * Current instance server objects tree
     *
     * @var array
     */
    protected $serverObjects = array();

    /**
     * Available Trassir Server channels as index=>chandata
     * 
     * @var array $channels
     */
    protected $channels = array();

    /**
     * Contains available users array as index=>username or guid
     *
     * @var array
     */
    protected $trassirUsers = array();

    /**
     * Contains default service users account names which will be ignored by some safe methods
     *
     * @var array
     */
    protected $serviceAccountNames = array('Admin', 'Operator', 'Script', 'Demo', 'user_add', 'Monitoring');

    /**
     * Stream context for working with self-signed certs
     *
     * @var resource
     */
    protected $stream_context;

    /**
     * Creates new instance of TrassirServer object
     * 
     * @param string $ip
     * @param string $userName
     * @param string $password
     * @param string $sdkPassword
     * 
     * @return void
     */
    public function __construct($ip, $userName = null, $password = null, $sdkPassword = null, $port = 8080, $debug = false) {
        $this->setDebug($debug);
        $this->initCache();
        $this->setIp($ip);
        $this->setUserName($userName);
        $this->setPassword($password);
        $this->setSdkPassword($sdkPassword);
        $this->setPort($port);
        $this->stream_context = stream_context_create(array('ssl' => array(//allowing self-signed certs for NVR
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
                'verify_depth' => 0)));

        $this->initialConnect(); // initalizing connection to server, setting some SIDs and other stuff
    }

    /**
     * Performs initial Server object connection
     * 
     * @return bool
     */
    protected function initialConnect() {
        $result = true;
        $connnectCheck = $this->checkConnection();

        if ($connnectCheck) {
            $this->logDebug('HTTP connection to IP ' . $this->ip . ' OK', 'success');
        } else {
            $this->logDebug('HTTP connection to IP ' . $this->ip . ' Failed', 'error');
        }

        $loginCheck = $this->login();

        if ($loginCheck) {
            $this->logDebug('NVR login seems to be OK', 'success');
        } else {
            $this->logDebug('NVR login failed', 'error');
        }

        return($result);
    }

    /**
     * Inits system caching object for further usage and storing SID
     * 
     * @return void
     */
    protected function initCache() {
        $this->cache = new UbillingCache();
    }

    /**
     * Sets current instance IP
     * 
     * @param string $ip
     * 
     * @return void
     */
    protected function setIp($ip) {
        $this->ip = $ip;
    }

    /**
     * Sets current instance HTTPS port
     * 
     * @param int $port
     * 
     * @return void
     */
    protected function setPort($port) {
        $this->port = $port;
    }

    /**
     * Sets current instance username
     * 
     * @param string $userName
     * 
     * @return void
     */
    protected function setUserName($userName) {
        $this->userName = $userName;
    }

    /**
     * Modify current instance debug flag
     * 
     * @param bool $state
     * 
     * @return void
     */
    protected function setDebug($state) {
        $this->debug = $state;
    }

    /**
     * Sets current instance password
     * 
     * @param string $password
     * 
     * @return void
     */
    protected function setPassword($password) {
        $this->password = $password;
    }

    /**
     * Sets Trassir SDK API key into current instance
     * 
     * @param string $sdkpassword
     * 
     * @return void
     */
    protected function setSdkPassword($sdkpassword) {
        $this->sdkPassword = $sdkpassword;
    }

    /**
     * Returns current instance server hostname
     * 
     * @return string|null
     */
    public function getName() {
        return ($this->name);
    }

    /**
     * Returns curren instance GUID
     * 
     * @return string
     */
    public function getGuid() {
        return $this->guid;
    }

    /**
     * Checking is NVR online or not to prevent further errors
     * 
     * 
     * @return bool
     */
    protected function checkConnection() {
        $status = false;
        $url = 'http://' . trim($this->ip) . ':80/';
        $curlInit = curl_init($url);
        curl_setopt($curlInit, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($curlInit, CURLOPT_HEADER, true);
        curl_setopt($curlInit, CURLOPT_NOBODY, true);
        curl_setopt($curlInit, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curlInit);
        curl_close($curlInit);
        if ($response) {
            $status = true;
        }
        return ($status);
    }

    /**
     * Do some debug output and logging in the future
     * 
     * @param string $data
     * @param string $type success/info/warning/error
     * 
     * @return void
     */
    protected function logDebug($data, $type) {
        if ($this->debug) {
            $curDate = curdatetime();
            $data = $curDate . ' ' . $data;
            switch ($type) {
                case 'success':
                    show_success($data);
                    break;
                case 'info':
                    show_info($data);
                    break;
                case 'warning':
                    show_warning($data);
                    break;
                case 'error':
                    show_error($data);
                    break;
            }
        }
    }

    /**
     * Get current NVR session ID (sid) using login and password
     * 
     * @return bool|string
     */
    protected function login() {
        ///trying get it from cache
        $cacheKey = 'TRASSIRSID_' . $this->ip;
        $cachedSid = $this->cache->get($cacheKey, $this->cacheTimeout);

        //SID found in cache and seems it may be ok
        if (!empty($cachedSid)) {
            $this->logDebug('SID from cache: ' . $cachedSid . ' from cache key ' . $cacheKey, 'success');
            $this->sid = $cachedSid;
        } else {
            $this->logDebug('No cached SID available. Trying to receive new one', 'warning');
        }

        if (isset($this->sid) AND ( $this->sid !== false)) {
            //updating SID in cache
            $this->cache->set($cacheKey, $this->sid, $this->cacheTimeout);
            $this->logDebug('Updating SID ' . $this->sid . ' in cache key', 'info');
            return ($this->sid);
        }

        //New SID receiving
        $url = 'https://' . trim($this->ip) . ':' . trim($this->port) . '/login?username=' . trim($this->userName) . '&password=' . trim($this->password);
        if (false === ($responseJson_str = @file_get_contents($url, NULL, $this->stream_context))) {
            return (false); //connection failed
        }

        $server_auth = json_decode($responseJson_str, true);
        if ($server_auth['success'] == 1) {
            $this->sid = $server_auth['sid'];
            $this->logDebug('New SID received: ' . $this->sid, 'success');

            //setting new SID to cache
            $this->cache->set($cacheKey, $this->sid, $this->cacheTimeout);
        } else {
            show_error(__('SDK reply') . ': ' . $responseJson_str);
            $this->sid = false;
        }
        return ($this->sid);
    }

    /**
     * Returns clean JSON data without shitty comments at the end
     * 
     * @param string $data
     * 
     * @return string
     */
    protected function clearReply($data) {
        $commentPosition = strripos($data, '/*');
        $data = substr($data, 0, $commentPosition);
        return($data);
    }

    /**
     * Performs SDK API request to connected Trassir Server
     * 
     * @param string $request request string
     * @param string $authType possible: apikey, sid
     * 
     * @return array
     */
    protected function apiRequest($request, $authType) {
        $result = array();
        $authString = '';
        $host = $this->sdkProtocol . '://' . $this->ip . ':' . $this->port;
        switch ($authType) {
            case 'apikey':
                $authString = '?password=' . $this->sdkPassword;
                break;
            case 'sid':
                $authString = '?sid=' . $this->sid;
                break;
        }

        $url = $host . $request . $authString;

        $rawResponse = file_get_contents($url, null, $this->stream_context);
        $rawResponse = $this->clearReply($rawResponse);
        $result = json_decode($rawResponse, true);
        return($result);
    }

    /**
     * Returns all of server objects (channels, IP-devices etc.)
     * 
     * also fills $this->channels array
     *
     * @return array
     */
    public function getServerObjects() {
        $objects = $this->apiRequest('/objects/', 'apikey');

        foreach ($objects as $obj) {
            if ($obj['class'] == 'Server') {
                $this->name = $obj['name'];
                $this->guid = $obj['guid'];
            }
            if ($obj['class'] == 'Channel') {
                $this->channels[] = array(
                    'name' => $obj['name'],
                    'guid' => $obj ['guid'],
                    'parent' => $obj ['parent'],
                );
            }
        }

        $objects['Users'] = $this->getUsers();
        $objects['UserNames'] = $this->getUserNames();
        $this->serverObjects = $objects;

        return ($objects);
    }

    /**
     * Returns array of system health indicators. Also fills channels_health
     * 
     * @return array
     */
    public function getHealth() {
        if (empty($this->channels)) {
            $this->getServerObjects();
        }
        $server_health = $this->apiRequest('/health', 'sid');
        $channelsHealth = array();
        $result = $server_health;

        if (!empty($this->channels)) {
            foreach ($this->channels as $channel) {
                $chanHealth = $this->apiRequest('/settings/channels/' . $channel['guid'] . '/flags/signal', 'sid');
                $channelsHealth[] = array(
                    'name' => $channel['name'],
                    'guid' => $channel['guid'],
                    'signal' => $chanHealth['value']
                );
            }
            if (isset($channelsHealth) AND ! empty($channelsHealth) AND is_array($channelsHealth)) {
                $result['channels_health'] = $channelsHealth;
            }
        }

        return ($result);
    }

    /**
     * Returns server settings main tree
     * 
     * @return array
     */
    public function getServerSettings() {
        $result = $this->apiRequest('/settings/', 'sid');
        return($result);
    }

    /**
     * Returns array of server users/their guids
     * 
     * @return array
     */
    protected function getUsers() {
        $rawUsers = $this->apiRequest('/settings/users/', 'sid');
        $this->trassirUsers = $rawUsers['subdirs'];
        return ($this->trassirUsers);
    }

    /**
     * Creates user on Trassir Server. Be careful: user will be with full rights set!
     * 
     * @param string $login
     * @param string $password
     * 
     * @return void
     */
    public function createUser($login, $password) {
        $this->apiRequest('/settings/users/user_add/new_user_name=' . $login, 'sid');
        $this->apiRequest('/settings/users/user_add/new_user_password=' . $password, 'sid');
        $this->apiRequest('/settings/users/user_add/create_now=1', 'sid');
    }

    /**
     * Returns registered non system users as login=>guid
     * 
     * @return array
     */
    public function getUserNames() {
        $userNames = array();
        $tmp = array();
        if (empty($this->trassirUsers)) {
            $this->getServerObjects();
        }

        if (!empty($this->trassirUsers)) {
            foreach ($this->trassirUsers as $user) {
                $tmp[] = $this->apiRequest('/settings/users/' . $user . '/name', 'apikey');
            }
        }

        if (!empty($tmp)) {
            foreach ($tmp as $userDetails) {
                if (isset($userDetails['value']) && (!in_array($userDetails['value'], $this->serviceAccountNames))) {
                    $userGuid = str_replace('users/', '', $userDetails['directory']);
                    $userGuid = str_replace('/', '', $userGuid);
                    $userNames[$userDetails['value']] = $userGuid;
                }
            }
        }
        return ($userNames);
    }

    /**
     * Returns full array of some user settings by its guid
     * 
     * @param string $guid
     * 
     * @return array
     */
    public function getUserSettings($guid) {
        $result = array();
        $userSettingsTmp = $this->apiRequest('/settings/users/' . $guid . '/', 'sid');
        if (!empty($userSettingsTmp)) {
            $result = $userSettingsTmp;
            if (isset($userSettingsTmp['values'])) {
                foreach ($userSettingsTmp['values'] as $io => $each) {
                    $optionData = $this->apiRequest('/settings/users/' . $guid . '/' . $each, 'sid');
                    $result['fulldata'][$each] = $optionData;
                }
            }
        }
        return($result);
    }

    /**
     * Sets some user setting on Trassir Server by its guid
     * 
     * @param string $guid
     * @param string $setting
     * @param string $value
     * 
     * @return array
     */
    public function setUserSettings($guid, $setting, $value) {
        $result = $this->apiRequest('/settings/users/' . $guid . '/' . $setting . '=' . $value, 'sid');
        return($result);
    }

    /**
     * Saves channel screenshot to local file system
     * 
     * @param string $channel One of channel guids
     * 
     * @param string $folder folder to save shots
     * @param DateTime|null $timestamp take last available shot if timestamp is null
     * 
     * @return string url to image
     */
    public function saveScreenshot($channel, $folder = 'exports', $timestamp = null) {
        $path = '';
        if ($timestamp) {
            $time = $timestamp->format('Ymd-His');
        } else {
            $time = '0';
        }

        $img = $this->sdkProtocol . '://' . $this->ip . ':' . $this->port . '/screenshot/' . $channel . '?timestamp=' . $time . '&sid=' . $this->sid;
        $path = $folder . '/shot_' . $channel . '_' . zb_rand_string(8) . $time . '.jpg';
        $curl = curl_init($img);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_BINARYTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        $content = curl_exec($curl);
        $response = json_decode($content, true);
        if ($response['success'] === 0) {
            //return ($response['error_code']);
        } else {
            curl_close($curl);
            if (file_exists($path)) {
                unlink($path);
            }
            $fp = fopen($path, 'x');
            if ($fp !== false) {
                fwrite($fp, $content);
                fclose($fp);
            }
        }
        return ($path);
    }
    
    /**
     * TODO: refactor that at morning
     */

    /**
     * @param array $channel
     * @param string $stream should be main or sub
     * @param string $container should be mjpeg|flv|jpeg
     * @return bool|string return url to live video stream or false if failure
     */
    public function getLiveVideoStream($channel, $stream = 'main', $container = 'mjpeg') {
        if (!$this->checkConnection()) {
            return false;
        }
        if (!$this->login()) {
            return false;
        }

        $tokenUrl = 'https://' . trim($this->ip) . ':8080/get_video?channel=' . $channel . '&container=' . $container . '&stream=' . $stream . '&sid=' . $this->sid;
        //die($tokenUrl);
        $responseJson_str = file_get_contents($tokenUrl, null, $this->stream_context);
        $comment_position = strripos($responseJson_str, '/*');    //отрезаем комментарий в конце ответа сервера
        if ($comment_position) {
            $responseJson_str = substr($responseJson_str, 0, $comment_position);
        }
        $token = json_decode($responseJson_str, true);
        //die($token);

        if ($token['success'] == 1) {
            $videoToken = $token['token'];
        } else {
            throw new \InvalidArgumentException('Cannot get video token');
        }

        $result = 'http://' . trim($this->ip) . ':555/' . $videoToken;
        return $result;
    }

    public function getCameras() {
        $result = array();
        $url = 'https://' . trim($this->ip) . ':8080/settings/ip_cameras/' . '?sid=' . trim($this->sid);
        $responseJson_str = file_get_contents($url, null, $this->stream_context);
        $comment_position = strripos($responseJson_str, '/*');
        $responseJson_str = substr($responseJson_str, 0, $comment_position);
        $tmp = json_decode($responseJson_str, true);
        if (isset($tmp['subdirs'])) {
            if (!empty($tmp['subdirs'])) {
                foreach ($tmp['subdirs'] as $io => $each) {
                    if ($each != 'ip_camera_add') {
                        $result[$each] = $each;
                    }
                }
            }
        }

        return($result);
    }

    public function getCameraIp($guid) {
        $result = array();
        $url = 'https://' . trim($this->ip) . ':8080/settings/ip_cameras/' . $guid . '/connection_ip' . '?sid=' . trim($this->sid);
        $responseJson_str = file_get_contents($url, null, $this->stream_context);
        $comment_position = strripos($responseJson_str, '/*');
        $responseJson_str = substr($responseJson_str, 0, $comment_position);
        $result = json_decode($responseJson_str, true);
        $result = $result['value'];
        return($result);
    }

    public function getCameraProtocols() {
        $result = array();
        $url = 'https://' . trim($this->ip) . ':8080/settings/ip_cameras/ip_camera_add/' . '?sid=' . trim($this->sid);
        $responseJson_str = file_get_contents($url, null, $this->stream_context);
        $comment_position = strripos($responseJson_str, '/*');
        $responseJson_str = substr($responseJson_str, 0, $comment_position);
        $result = json_decode($responseJson_str, true);
        $result = $result['subdirs'];
        return($result);
    }

    public function createCamera($protocol, $model, $ip, $port, $username, $password) {
        $result = array();

        //IP
        $url = 'https://' . trim($this->ip) . ':8080/settings/ip_cameras/ip_camera_add/' . $protocol . '/create_address=' . $ip . '?sid=' . trim($this->sid);
        $responseJson_str = file_get_contents($url, null, $this->stream_context);
        //Port
        $url = 'https://' . trim($this->ip) . ':8080/settings/ip_cameras/ip_camera_add/' . $protocol . '/create_port=' . $port . '?sid=' . trim($this->sid);
        $responseJson_str = file_get_contents($url, null, $this->stream_context);

        //login
        $url = 'https://' . trim($this->ip) . ':8080/settings/ip_cameras/ip_camera_add/' . $protocol . '/create_username=' . $username . '?sid=' . trim($this->sid);
        $responseJson_str = file_get_contents($url, null, $this->stream_context);

        //password
        $url = 'https://' . trim($this->ip) . ':8080/settings/ip_cameras/ip_camera_add/' . $protocol . '/create_password=' . $password . '?sid=' . trim($this->sid);
        $responseJson_str = file_get_contents($url, null, $this->stream_context);

        //Model
        $url = 'https://' . trim($this->ip) . ':8080/settings/ip_cameras/ip_camera_add/' . $protocol . '/create_model=' . $model . '?sid=' . trim($this->sid);
        $responseJson_str = file_get_contents($url, null, $this->stream_context);


        //create now
        $url = 'https://' . trim($this->ip) . ':8080/settings/ip_cameras/ip_camera_add/' . $protocol . '/create_now=1' . '?sid=' . trim($this->sid);
        $responseJson_str = file_get_contents($url, null, $this->stream_context);

        $comment_position = strripos($responseJson_str, '/*');
        $responseJson_str = substr($responseJson_str, 0, $comment_position);
        $result = json_decode($responseJson_str, true);
        return($result);
    }

    public function getAllCameraIps() {
        $result = array();
        $allCameras = $this->getCameras();
        if (!empty($allCameras)) {
            foreach ($allCameras as $io => $eachGuid) {
                $cameraIp = $this->getCameraIp($eachGuid);
                $result[$cameraIp] = $eachGuid;
            }
        }
        return($result);
    }

}
