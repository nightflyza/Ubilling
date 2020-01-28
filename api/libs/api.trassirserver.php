<?php

class TrassirServer {

    protected $debug = false;

    /**
     * @var string $name
     */
    protected $name;

    /**
     * @var string|null $ip
     */
    protected $ip;

    /**
     * @var string $guid
     */
    protected $guid;

    /**
     * array for handle list of channels from NMR
     * @var array $channels
     */
    protected $channels = array();

    /**
     * Variable for storage session id
     * @var string|false $sid
     */
    protected $sid;
    protected $sidExpiresAt;

    /**
     * @var string $userName
     */
    protected $userName;

    /**
     * @var string $password
     */
    protected $password;
    protected $cache = '';
    protected $cacheTimeout = 900; //15 mins

    /**
     * @var string $sdkPassword
     */
    protected $sdkPassword;
    protected $serverObjects;
    protected $trassirUsers;
    protected $serviceAccountNames = array('Admin', 'Operator', 'Script', 'Demo', 'user_add', 'Monitoring');
    protected $stream_context; //тут храним контекст который нужен CURL или file_get_content для работы с неподписанными сертификатами

    /**
     * 
     * @param string $ip
     * @param string $userName
     * @param string $password
     * @param string $sdkPassword
     * @throws \InvalidArgumentException
     * 
     * @return void
     */

    public function __construct($ip, $userName = null, $password = null, $sdkPassword = null) {
        $this->initCache();
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            $this->ip = $ip;
            $this->userName = $userName;
            $this->password = $password;
            $this->sdkPassword = $sdkPassword;
            $this->stream_context = stream_context_create(array('ssl' => array(//разрешаем принимать самоподписанные сертификаты от NVR
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                    'verify_depth' => 0)));
        } else {
            throw new \InvalidArgumentException('Please enter valid IP address.');
        }
    }

    protected function initCache() {
        $this->cache = new UbillingCache();
    }

    public function setUserName($userName) {
        $this->userName = $userName;
    }

    public function setDebug($state) {
        $this->debug = $state;
    }

    public function getUserName() {
        return $this->userName;
    }

    public function setPassword($password) {
        $this->password = $password;
    }

    public function getPassword() {
        return $this->password;
    }

    public function setSdkPassword($sdkpassword) {
        $this->sdkPassword = $sdkpassword;
    }

    public function getSdkPassword() {
        return $this->sdkPassword;
    }

    /**
     * @return array
     */
    public function getChannels() {
        return $this->channels;
    }

    /**
     * @return string|null
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @return null|string
     */
    public function getIp() {
        return $this->ip;
    }

    /**
     * @return string
     */
    public function getGuid() {
        return $this->guid;
    }

    /**
     * Checking is NVR online or not to prevent errors
     * @return bool
     */
    protected function checkConnection() {
        $status = false;
        $url = 'http://' . trim($this->ip) . ':80/';
        $curlInit = curl_init($url);
        curl_setopt($curlInit, CURLOPT_CONNECTTIMEOUT, 2); //третий параметр - время ожидания ответа сервера в секундах
        curl_setopt($curlInit, CURLOPT_HEADER, true);
        curl_setopt($curlInit, CURLOPT_NOBODY, true);
        curl_setopt($curlInit, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curlInit);
        curl_close($curlInit);
        if ($response) {
            $status = true;
        }
        return $status;
    }

    /**
     * Get sessionId (sid) using login and password
     * @return bool|string
     */
    protected function login() {
        ///trying get from cache
        $cacheKey = 'TRASSIRSID_' . $this->ip;
        $cachedSid = $this->cache->get($cacheKey, $this->cacheTimeout);

        if (!empty($cachedSid)) {
            if ($this->debug) {
                show_success('SID from cache' . ': ' . $cachedSid . ' ON KEY ' . $cacheKey);
            }
            $this->sid = $cachedSid;
        }

        if (isset($this->sid) AND ( $this->sid !== false)) {
            //updating SID in cache
            $this->cache->set($cacheKey, $this->sid, $this->cacheTimeout);
            return $this->sid;
        }

        $url = 'https://' . trim($this->ip) . ':8080/login?username=' . trim($this->userName) . '&password=' . trim($this->password);
        if (false === ($responseJson_str = @file_get_contents($url, NULL, $this->stream_context))) {
            return false;
        }

        $server_auth = json_decode($responseJson_str, true); //переводим JSON в массив
        if ($server_auth['success'] == 1) {
            $this->sid = $server_auth['sid'];
            if ($this->debug) {
                show_success('New SID: ' . $this->sid);
            }

            //setting new SID to cache
            $this->cache->set($cacheKey, $this->sid, $this->cacheTimeout);
        } else {
            show_error('SDK: ' . print_r($server_auth, true));
            show_warning('SID: ' . $this->sid);
            $this->sid = false;
        }
        return $this->sid;
    }

    /**
     * function to get all server objects (channels, IP-devices etc.) Also it set up servers Name and Guid
     * also fills $this->channels array
     *
     * @return array|bool
     */
    public function getServerObjects() {
        if (!$this->checkConnection()) {
            return false;
        }

        if (!$this->login()) {
            return false;
        }

        $url = 'https://' . trim($this->ip) . ':8080/objects/?password=' . trim($this->sdkPassword); //получения объектов сервера
        $responseJson_str = file_get_contents($url, NULL, $this->stream_context);
        $comment_position = strripos($responseJson_str, '/*');    //отрезаем комментарий в конце ответа сервера
        $responseJson_str = substr($responseJson_str, 0, $comment_position);
        $objects = json_decode($responseJson_str, true);

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

        return $objects;
    }

    /**
     * return array of indicators
     * @return array|mixed
     */
    public function getHealth() {
        if (!$this->checkConnection()) {
            return false;
        }

        if (!$this->login()) {
            return false;
        }

        if (!$this->getServerObjects()) {
            return false;
        }
        $url = 'https://' . trim($this->ip) . ':8080/health?sid=' . trim($this->sid);

        $responseJson_str = file_get_contents($url, null, $this->stream_context);
        $comment_position = strripos($responseJson_str, '/*');    //отрезаем комментарий в конце ответа сервера
        $responseJson_str = substr($responseJson_str, 0, $comment_position);
        $server_health = json_decode($responseJson_str, true);

        $channelsHealth = array();
        $result = $server_health;
        if (!empty($this->channels)) {
            foreach ($this->channels as $channel) {
                $url = 'https://' . trim($this->ip) . ':8080/settings/channels/' . $channel['guid'] . '/flags/signal?sid=' . trim($this->sid); //получения статуса канала
                $responseJson_str = file_get_contents($url, NULL, $this->stream_context);
                $comment_position = strripos($responseJson_str, '/*');    //отрезаем комментарий в конце ответа сервера
                $responseJson_str = substr($responseJson_str, 0, $comment_position);
                $channelHealth = json_decode($responseJson_str, true);

                $channelsHealth[] = array(
                    'name' => $channel['name'],
                    'guid' => $channel['guid'],
                    'signal' => $channelHealth['value']
                );
            }
            if (isset($channelsHealth) && !empty($channelsHealth) && is_array($channelsHealth)) {
                //$result = array_merge($server_health, $channelsHealth);
                $result['channels_health'] = $channelsHealth;
            }
        }

        return $result;
    }

    public function getServerSettings() {
        $serverSettings = array();
        $url = 'https://' . trim($this->ip) . ':8080/settings/?sid=' . trim($this->sid);
        $responseJson_str = file_get_contents($url, null, $this->stream_context);
        $comment_position = strripos($responseJson_str, '/*');    //отрезаем комментарий в конце ответа сервера
        $responseJson_str = substr($responseJson_str, 0, $comment_position);
        $serverSettings = json_decode($responseJson_str, true);

        return $serverSettings;
    }

    public function getUsers() {
        $Users = array();
        $url = 'https://' . trim($this->ip) . ':8080/settings/users/?sid=' . trim($this->sid);
        $responseJson_str = file_get_contents($url, null, $this->stream_context);
        $comment_position = strripos($responseJson_str, '/*');    //отрезаем комментарий в конце ответа сервера
        $responseJson_str = substr($responseJson_str, 0, $comment_position);
        $Users = json_decode($responseJson_str, true);
        $this->trassirUsers = $Users['subdirs'];
        return $this->trassirUsers;
    }

    public function getArchive() {
        /**
         * Some auth issues here. TODO.
         */
        $url = 'https://' . trim($this->ip) . ':8080/objects/operatorgui_efXwC0I5/archive_export?channel_name_or_guid=LL0c1qK0&start_time_YYYYMMDD_HHMMSS=2019-12-19 13:00:00&end_time_YYYYMMDD_HHMMSS=2019-12-19 13:55:00&filename=export.avi&archive_on_device=0&sid=' . trim($this->sid);
        $responseJson_str = file_get_contents($url, null, $this->stream_context);
    }

    public function createUser($login, $password) {
        $url = 'https://' . trim($this->ip) . ':8080/settings/users/user_add/new_user_name=' . $login . '?sid=' . trim($this->sid);
        $responseJson_str = file_get_contents($url, null, $this->stream_context);
        $url = 'https://' . trim($this->ip) . ':8080/settings/users/user_add/new_user_password=' . $password . '?sid=' . trim($this->sid);
        $responseJson_str = file_get_contents($url, null, $this->stream_context);
        $url = 'https://' . trim($this->ip) . ':8080/settings/users/user_add/create_now=1?&sid=' . trim($this->sid);
        $responseJson_str = file_get_contents($url, null, $this->stream_context);
    }

    public function getUserNames() {
        $UserNames = array();
        foreach ($this->trassirUsers as $user) {
            $url = 'https://' . trim($this->ip) . ':8080/settings/users/' . $user . '/name?password=' . trim($this->sdkPassword);
            $responseJson_str = file_get_contents($url, null, $this->stream_context);
            $comment_position = strripos($responseJson_str, '/*');    //отрезаем комментарий в конце ответа сервера
            $responseJson_str = substr($responseJson_str, 0, $comment_position);
            $res[] = json_decode($responseJson_str, true);
        }

        if (!empty($res)) {
            foreach ($res as $userDetails) {
                if (isset($userDetails['value']) && (!in_array($userDetails['value'], $this->serviceAccountNames))) {
                    $UserNames[] = $userDetails['value'];
                }
            }
        }
        return $UserNames;
    }

    /**
     * 
     * @param string $guid
     * 
     * @return array
     */
    public function getUserSettings($guid) {
        $result = array();
        $url = 'https://' . trim($this->ip) . ':8080/settings/users/' . $guid . '/?sid=' . trim($this->sid);
        $responseJson_str = file_get_contents($url, null, $this->stream_context);
        $comment_position = strripos($responseJson_str, '/*');    //отрезаем комментарий в конце ответа сервера
        $responseJson_str = substr($responseJson_str, 0, $comment_position);
        $userSettingsTmp = json_decode($responseJson_str, true);
        if (!empty($userSettingsTmp)) {
            $result = $userSettingsTmp;
            if (isset($userSettingsTmp['values'])) {
                foreach ($userSettingsTmp['values'] as $io => $each) {
                    $url = 'https://' . trim($this->ip) . ':8080/settings/users/' . $guid . '/' . $each . '?sid=' . trim($this->sid);
                    $responseJson_str = file_get_contents($url, null, $this->stream_context);
                    $comment_position = strripos($responseJson_str, '/*');
                    $responseJson_str = substr($responseJson_str, 0, $comment_position);
                    $optionData = json_decode($responseJson_str, true);
                    $result['fulldata'][$each] = $optionData;
                }
            }
        }
        return($result);
    }

    public function setUserSettings($guid, $setting, $value) {
        $result = array();
        $url = 'https://' . trim($this->ip) . ':8080/settings/users/' . $guid . '/' . $setting . '=' . $value . '?sid=' . trim($this->sid);
        $responseJson_str = file_get_contents($url, null, $this->stream_context);
        $comment_position = strripos($responseJson_str, '/*');
        $responseJson_str = substr($responseJson_str, 0, $comment_position);
        $optionData = json_decode($responseJson_str, true);
        $result = $optionData;
    }

    /**
     * @param array $channel One of protected $channels
     * @param string $folder folder to save shots
     * @param \DateTime|null $timestamp take last available shot if timestamp is null
     * @return string url to image
     */
    public function saveScreenshot(array $channel, $folder = 'shots', \DateTime $timestamp = null) {
        if ($timestamp) {
            $time = $timestamp->format('Ymd-His');
        } else {
            $time = '0';
        }

        $img = 'https://' . trim($this->ip) . ':8080/screenshot/' . $channel['guid'] . '?timestamp=' . $time . '&sid=' . trim($this->sid);
        $path = $folder . '/shot_' . $channel['name'] . rand(1, 1000) . $time . '.jpg';
        $curl = curl_init($img);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_BINARYTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        $content = curl_exec($curl);
        $response = json_decode($content, true);
        if ($response['success'] === 0) {
            return $response['error_code'];
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
            return $path;
        }
    }

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
