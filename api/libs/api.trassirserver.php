<?php

class TrassirServer {

    /**
     * @var string $name
     */
    private $name;

    /**
     * @var string|null $ip
     */
    private $ip;

    /**
     * @var string $guid
     */
    private $guid;

    /**
     * array for handle list of channels from NMR
     * @var array $channels
     */
    private $channels = array();

    /**
     * Variable for storage session id
     * @var string|false $sid
     */
    private $sid;
    private $sidExpiresAt;

    /**
     * @var string $userName
     */
    private $userName;

    /**
     * @var string $password
     */
    private $password;

    /**
     * @var string $sdkPassword
     */
    private $sdkPassword;
    private $serverObjects;
    private $trassirUsers;
    private $serviceAccountNames = array('Admin', 'Operator', 'Script', 'Demo', 'user_add', 'Monitoring');
    private $stream_context; //тут храним контекст который нужен CURL или file_get_content для работы с неподписанными сертификатами

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

    public function setUserName($userName) {
        $this->userName = $userName;
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
    private function checkConnection() {
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
    private function login() {
        if (isset($this->sid) && ($this->sid !== false) && isset($this->sidExpiresAt) && ($this->sidExpiresAt > new \DateTime())) {
            return $this->sid;
        }
        $url = 'https://' . trim($this->ip) . ':8080/login?username=' . trim($this->userName) . '&password=' . trim($this->password);
        if (false === ($responseJson_str = @file_get_contents($url, NULL, $this->stream_context))) {
            return false;
        }
        $server_auth = json_decode($responseJson_str, true); //переводим JSON в массив
        if ($server_auth['success'] == 1) {
            $this->sid = $server_auth['sid'];
            $this->sidExpiresAt = new \DateTime();
            $this->sidExpiresAt->modify('+15 minutes');
        } else {
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
        $url = 'https://' . trim($this->ip) . ':8080/objects/operatorgui_efXwC0I5/archive_export?channel_name_or_guid=Elqs3W6I&start_time_YYYYMMDD_HHMMSS=2019-11-27 13:00:00&end_time_YYYYMMDD_HHMMSS=2019-11-27 13:55:00&filename=export.avi&archive_on_device=0&sid=' . trim($this->sid);
        deb($url);
        $responseJson_str = file_get_contents($url, null, $this->stream_context);
        debarr($responseJson_str);
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

    private function getUserSettings($guid) {
        $UserSettings = array();
        $url = 'https://' . trim($this->ip) . ':8080/settings/users/' . $guid . '/name?password=' . trim($this->sdkPassword);
        $responseJson_str = file_get_contents($url, null, $this->stream_context);
        $comment_position = strripos($responseJson_str, '/*');    //отрезаем комментарий в конце ответа сервера
        $responseJson_str = substr($responseJson_str, 0, $comment_position);
        $UserSettings = json_decode($responseJson_str, true);
        return $UserSettings;
    }

    /**
     * @param array $channel One of private $channels
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

        $tokenUrl = 'https://' . trim($this->ip) . ':8080/get_video?channel=' . $channel['guid'] . '&container=' . $container . '&stream=' . $stream . '&sid=' . $this->sid;
        //die($tokenUrl);
        $responseJson_str = file_get_contents($tokenUrl, null, $this->stream_context);
        $comment_position = strripos($responseJson_str, '/*');    //отрезаем комментарий в конце ответа сервера
        if ($comment_position) {
            $responseJson_str = substr($responseJson_str, 0, $comment_position);
        }
        $token = json_decode($responseJson_str, true);

        if ($token['success'] == 1) {
            $videoToken = $token['token'];
        } else {
            throw new \InvalidArgumentException('Cann not get vieotoken');
        }

        $result = 'http://' . trim($this->ip) . ':555/' . $videoToken;
        return $result;
    }

}
