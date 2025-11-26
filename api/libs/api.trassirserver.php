<?php

/**
 * Basic low-level Trassir Server NVRs interraction class.
 * Based on: https://github.com/dglushakov/TrassirNVR
 */
class TrassirServer {

    /**
     * Object instance debug flag
     *
     * @var bool/int
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
     * HTTP Video Streaming port
     *
     * @var int
     */
    protected $httpVideoPort = 555;

    /**
     * Default http video streaming protocol
     *
     * @var string
     */
    protected $httpVideoProtocol = 'http';

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
     * Contains array of available channel names as guid=>name
     *
     * @var array
     */
    protected $channelNames = array();

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
     * Contains default log path
     */
    const LOG_PATH = 'exports/trassirdebug.log';

    /**
     * Creates new instance of TrassirServer object
     * 
     * @param string $ip
     * @param string $userName
     * @param string $password
     * @param string $sdkPassword
     * @param int $port 
     * @param bool/int debug: false/0/1/2/3
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
            $this->logDebug('HTTPS connection to IP ' . $this->ip . ' OK', 'success');
        } else {
            $this->logDebug('HTTPS connection to IP ' . $this->ip . ' Failed', 'error');
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
     * @param bool/int $state
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
        $url = 'https://' . trim($this->ip) . ':' . $this->port . '/';
        $apiHandler=new OmaeUrl($url);
        $response = $apiHandler->response();
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
            $dataDisplay = $curDate . ' ' . $data;
            if ($this->debug == 1 OR $this->debug == 2) {
                switch ($type) {
                    case 'success':
                        show_success($dataDisplay);
                        break;
                    case 'info':
                        show_info($dataDisplay);
                        break;
                    case 'warning':
                        show_warning($dataDisplay);
                        break;
                    case 'error':
                        show_error($dataDisplay);
                        break;
                }
            }

            $logData = $curDate . ' ' . $type . ' ' . $data . PHP_EOL;
            file_put_contents(self::LOG_PATH, $logData, FILE_APPEND);
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
        if ($commentPosition !== false) {
            $data = substr($data, 0, $commentPosition);
        }
        return($data);
    }

    /**
     * Performs SDK API request to connected Trassir Server
     * 
     * @param string $request request string
     * @param string $authType possible: apikey, sid, sidamp
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
            case 'sidamp':
                $authString = '&sid=' . $this->sid;
                break;
        }

        $url = $host . $request . $authString;
        if ($this->debug >= 2) {
            $this->logDebug($url, 'lld');
        }

        $rawResponse = file_get_contents($url, null, $this->stream_context);
        $rawResponse = $this->clearReply($rawResponse);

        $result = json_decode($rawResponse, true);
        if ($this->debug >= 2) {
            $this->logDebug('Response: ' . print_r($result, true), 'lld');
        }

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

                $this->channelNames[$obj['guid']] = $obj['name'];
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
     * Returns array of available channels as guid=>name
     * 
     * @return array
     */
    public function getChannels() {
        if (empty($this->serverObjects)) {
            $this->getServerObjects();
        }
        return($this->channelNames);
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
     * Creates user on Trassir Server.
     * 
     * @param string $login
     * @param string $password
     * 
     * @return bool
     */
    public function createUser($login, $password) {
        $result = false;
        $userExists = $this->getUserGuid($login);
        if (!$userExists) {
            $this->apiRequest('/settings/users/user_add/new_user_name=' . $login, 'sid');
            $this->apiRequest('/settings/users/user_add/new_user_password=' . $password, 'sid');
            $this->apiRequest('/settings/users/user_add/create_now=1', 'sid');
            $result = true;
            $this->getServerObjects(); //update object instance for preloading of some new users
            $this->logDebug('New user registered: ' . $login, 'info');
            //restricting new user rights
            $this->restrictUserRighs($login);
        } else {
            $this->logDebug('User already registered and found in server objects tree: ' . $login, 'warning');
        }
        return($result);
    }

    /**
     * 
     * @param type $userLogin
     * 
     * @return bool
     */
    protected function restrictUserRighs($userLogin) {
        $result = false;
        $guid = $this->getUserGuid($userLogin);
        if ($guid) {
            $this->setUserSettings($guid, 'base_rights', 0); //no rights at all
            $this->setUserSettings($guid, 'templates_managing', 0);
            $this->setUserSettings($guid, 'enable_web', 1);
            $this->setUserSettings($guid, 'enable_remote', 1);
            $this->setUserSettings($guid, 'view_button', 1);
            $this->setUserSettings($guid, 'settings_button', 0);
            $this->setUserSettings($guid, 'shutdown_button', 0);
            $this->setUserSettings($guid, 'enable_local', 0);
            $this->setUserSettings($guid, 'base_rights', 256); //no rights at all
            $result = true;
            $this->logDebug('User rights restricted on login: ' . $userLogin, 'info');
        } else {
            $this->logDebug('User not found in server objects tree: ' . $userLogin, 'error');
        }
        return($result);
    }

    /**
     * Sets user ACL for some channels permissions. 
     * Use manual POST because of "cannot find 'acl=" issue.
     * 
     * @param string $userGuid
     * @param string $acl
     * 
     * @return bool/array
     */
    protected function setUserACL($userGuid, $acl = '') {
        $result = false;
        $post = 'acl=' . $acl;

        $url = $this->sdkProtocol . '://' . $this->ip . ':' . $this->port . '/settings/users/' . $userGuid . '/?sid=' . $this->sid;
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_BINARYTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post);

        $response = curl_exec($curl);
        //PHP 8.0+ has no need to close curl resource anymore
        if (PHP_VERSION_ID < 80000) {
            curl_close($curl); // Deprecated in PHP 8.5
        }
        $response = $this->clearReply($response);
        if (!empty($response)) {
            $result = json_decode($response, true);
            $this->logDebug('Setting user ' . $userGuid . ' ACL to ' . $acl, 'info');
        }
        return($result);
    }

    /**
     * Set some user ACL to allow him basic usage of his cameras
     * 
     * @param string $login
     * @param array $channels
     * 
     * @return bool
     */
    public function assignUserChannels($login, $channels = array()) {
        $result = false;
        $userGuid = $this->getUserGuid($login);
        if ($userGuid) {
            $rightsMask = '1539'; // Oo
            $aclString = '';
            if (!empty($channels)) {
                foreach ($channels as $io => $eachChan) {
                    if (isset($this->channelNames[$eachChan])) {
                        $aclString .= '/' . $this->guid . '/channels/' . $eachChan . ',' . $rightsMask . ',';
                    } else {
                        $this->logDebug('Channel assign failed: ' . $eachChan . ' not found on server', 'error');
                    }
                }
            } else {
                $this->logDebug('User ' . $login . ' Channel assign failed - empty channels array', 'warning');
            }

            $aclString = zb_CutEnd($aclString);
            $this->logDebug('Setting user ' . $login . ' ACL: ' . $aclString, 'info');
            $aclChangeResult = $this->setUserACL($userGuid, $aclString); //push that to user
            $this->logDebug('User ' . $login . ' ACL setting result: ' . print_r($aclChangeResult, true), 'info');
        } else {
            $this->logDebug('User not found in server objects tree: ' . $login, 'error');
        }
        return($result);
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
     * Returns existing user GUID by its login
     * 
     * @param string $userLogin
     * 
     * @return string/bool
     */
    public function getUserGuid($userLogin) {
        $result = false;
        if (empty($this->trassirUsers)) {
            $this->getServerObjects();
        }

        if (isset($this->serverObjects['UserNames'])) {
            if (isset($this->serverObjects['UserNames'][$userLogin])) {
                $result = $this->serverObjects['UserNames'][$userLogin];
            }
        }
        return($result);
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
    protected function setUserSettings($guid, $setting, $value) {
        $result = $this->apiRequest('/settings/users/' . $guid . '/' . $setting . '=' . $value, 'sid');
        $this->logDebug('Setting GUID '.$guid.' user setting: '.$setting.'='.$value, 'lld');
        return($result);
    }

    /**
     * Saves channel screenshot to local file system
     * 
     * @param string $channel One of channel guids
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
            //PHP 8.0+ has no need to close curl resource anymore
            if (PHP_VERSION_ID < 80000) {
                curl_close($curl); // Deprecated in PHP 8.5
            }
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
     * Returns URL to some channel video stream
     * 
     * @param string $channel
     * @param string $stream should be main or sub
     * @param string $container should be mjpeg|flv|jpeg|hls
     * @param int $quality jpg container type quality between 0-100 (percents) 
     * @param int $framerate 0 - realtime / 60000 - 1 frame per minute
     * @param string $customUrl - optional custom preview URL
     * 
     * @return bool|string return url to live video stream or false on failure
     */
    public function getLiveVideoStream($channel, $stream = 'main', $container = 'mjpeg', $quality = 100, $framerate = 0, $customUrl = '') {
        $result = false;
        if ($container == 'mjpeg' OR $container == 'jpeg') {
            $requestUrl = '/get_video?channel=' . $channel . '&container=' . $container . '&stream=' . $stream . '&quality=' . $quality . '%&framerate=' . $framerate;
        } else {
            $requestUrl = '/get_video?channel=' . $channel . '&container=' . $container . '&stream=' . $stream . '&framerate=' . $framerate;
        }
        $token = $this->apiRequest($requestUrl, 'sidamp');
        if ($token['success'] == 1) {
            $videoToken = $token['token'];
            if (!empty($customUrl)) {
                //using custom preview URL
                if ($container != 'hls') {
                    $result = $customUrl . '/' . $videoToken;
                } else {
                    $result = $customUrl . '/hls/' . $videoToken . '/master.m3u8';
                }
            } else {
                //default case
                if ($container != 'hls') {
                    $result = $this->httpVideoProtocol . '://' . trim($this->ip) . ':' . $this->httpVideoPort . '/' . $videoToken;
                } else {
                    $result = $this->sdkProtocol . '://' . trim($this->ip) . ':' . $this->port . '/hls/' . $videoToken . '/master.m3u8';
                }
            }
        }
        return ($result);
    }

    /**
     * Returns channel recording type. Possible values: 1 - permanent, 2 - manual, 3 - on detector
     * 
     * @param string $channel
     * 
     * @return array
     */
    public function getChannelRecordMode($channel) {
        $result = array();
        $result = $this->apiRequest('/settings/channels/' . $channel . '/record_mode_local', 'apikey');
        if (!empty($result)) {
            $result = $result['value'];
        }
        return($result);
    }

    /**
     * Sets channel record mode.  Possible values: 1 - permanent, 2 - manual, 3 - on detector
     * 
     * @param string $channel
     * @param int $mode
     * 
     * @return array
     */
    public function setChannelRecordMode($channel, $mode) {
        $result = $this->apiRequest('/settings/channels/' . $channel . '/record_mode_local=' . $mode, 'apikey');
        return($result);
    }

    /**
     * Returns array of all available IP cameras GUIDs
     * 
     * @return array
     */
    protected function getCameras() {
        $result = array();
        $tmp = $this->apiRequest('/settings/ip_cameras/', 'sid');
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

    /**
     * Returns camera IP by its GUID
     * 
     * @param string $guid
     * 
     * @return string
     */
    public function getCameraIp($guid) {
        $result = $this->apiRequest('/settings/ip_cameras/' . $guid . '/connection_ip', 'sid');
        $result = $result['value'];
        return($result);
    }

    /**
     * Returns array of available supported camera protocols (vendors)
     * 
     * @return array
     */
    public function getCameraProtocols() {
        $result = $this->apiRequest('/settings/ip_cameras/ip_camera_add/', 'sid');
        $result = $result['subdirs'];
        return($result);
    }

    /**
     * Returns array of supported cameras for selected protocol
     * 
     * @param string $protocol
     * 
     * @return array
     */
    public function getCameraModels($protocol) {
        $result = array();
        $requestRaw = $this->apiRequest('/settings/ip_cameras/ip_camera_add/' . $protocol . '/available_models', 'sid');
        $requestRaw = @$requestRaw['value'];
        if (!empty($requestRaw)) {
            $requestRaw = explode(',', $requestRaw);
            if (!empty($requestRaw)) {
                foreach ($requestRaw as $io => $each) {
                    $cleanName = str_replace('%20', ' ', $each);
                    $result[$each] = $cleanName;
                }
            }
        }
        return($result);
    }

    /**
     * Creates new camera device on remote Trassir Server NVR
     * 
     * @param string $protocol
     * @param string $model
     * @param string $ip
     * @param string $port
     * @param string $username
     * @param string $password
     * 
     * @return array
     */
    public function createCamera($protocol, $model, $ip, $port, $username, $password) {
        //Setting camera IP
        $this->apiRequest('/settings/ip_cameras/ip_camera_add/' . $protocol . '/create_address=' . $ip, 'sid');
        //Setting camera port
        $this->apiRequest('/settings/ip_cameras/ip_camera_add/' . $protocol . '/create_port=' . $port, 'sid');
        //Setting camera login
        $this->apiRequest('/settings/ip_cameras/ip_camera_add/' . $protocol . '/create_username=' . $username, 'sid');
        //Setting camera password
        $this->apiRequest('/settings/ip_cameras/ip_camera_add/' . $protocol . '/create_password=' . $password, 'sid');
        //Setting camera model
        $this->apiRequest('/settings/ip_cameras/ip_camera_add/' . $protocol . '/create_model=' . $model, 'sid');

        //Camera creation
        $cameraCreateResult = $this->apiRequest('/settings/ip_cameras/ip_camera_add/' . $protocol . '/create_now=1', 'sid');

        return($cameraCreateResult);
    }

    /**
     * Returns array of all registered cameras as IP=>guid
     * 
     * @return array
     */
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

    /**
     * Disables/enables model mismatch warning on some cameras.
     * 
     * @param string $cameraIp
     * @param int $state
     * 
     * @return void
     */
    public function setModelMismatch($cameraIp, $state) {
        $allCams = $this->getAllCameraIps();
        if (isset($allCams[$cameraIp])) {
            $cameraGuid = $allCams[$cameraIp];
            if (!empty($cameraGuid)) {
                $this->apiRequest('/settings/ip_cameras/' . $cameraGuid . '/model_missmatch_off=' . $state, 'sid');
                $this->apiRequest('/settings/ip_cameras/' . $cameraGuid . '/grabber_enabled=0', 'sid');
                $this->apiRequest('/settings/ip_cameras/' . $cameraGuid . '/grabber_enabled=1', 'sid');
            }
        }
    }

    /**
     * Returns current some camera model mismatch warning state
     * 
     * @param string $cameraIp
     * 
     * @return int
     */
    public function getModelMismatch($cameraIp) {
        $result = '';
        $allCams = $this->getAllCameraIps();
        if (isset($allCams[$cameraIp])) {
            $cameraGuid = $allCams[$cameraIp];
            if (!empty($cameraGuid)) {
                $stateRaw = $this->apiRequest('/settings/ip_cameras/' . $cameraGuid . '/model_missmatch_off', 'sid');
                if (is_array($stateRaw)) {
                    $result .= $stateRaw['value'];
                }
            }
        }
        return($result);
    }

}
