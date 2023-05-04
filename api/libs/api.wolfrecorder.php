<?php

/**
 * WolfRecorder NVR REST APIv1 implementation
 */
class WolfRecorder {

    /**
     * Current instance WolfRecorder URL
     *
     * @var string
     */
    protected $url = '';

    /**
     * Current instance API key aka Serial
     *
     * @var string
     */
    protected $apiKey = '';

    /**
     * Some predefined stuff
     */
    const ROUTE_CALL = '/?module=remoteapi&action=rest&key=';

    /**
     * Creates new WR API instance
     * 
     * @param string $url
     * @param string $apiKey
     */
    public function __construct($url, $apiKey) {
        $this->setUrl($url);
        $this->setApiKey($apiKey);
    }

    /**
     * Sets current instance URL
     * 
     * @param string $url
     * 
     * @throws exception
     */
    protected function setUrl($url) {
        if (!empty($url)) {
            $this->url = $url;
        } else {
            throw Exception('EX_EMPTY_URL');
        }
    }

    /**
     * Sets current instance API key
     * 
     * @param string $url
     * 
     * @throws exception
     */
    protected function setApiKey($apiKey) {
        if (!empty($apiKey)) {
            $this->apiKey = $apiKey;
        } else {
            throw Exception('EX_EMPTY_APIKEY');
        }
    }

    /**
     * Performs request to remote WolfRecorder REST API, returns it result
     * 
     * @param string $object
     * @param string $method
     * @param array $requestData
     * 
     * @return array
     */
    protected function executeRequest($object, $method, $requestData = array()) {
        $result = array();
        $fullUrl = $this->url . self::ROUTE_CALL . $this->apiKey . '&' . $object . '=' . $method;
        $apiHandle = new OmaeUrl($fullUrl);
        if (!empty($requestData)) {
            $apiHandle->dataPost('data', json_encode($requestData));
        }
        $rawReply = $apiHandle->response();
        if (!$apiHandle->error() AND $apiHandle->httpCode() == 200) {
            @$replyDecode = json_decode($rawReply, true);
            if (is_array($replyDecode)) {
                $result = $replyDecode;
            } else {
                $result = array('error' => 666, 'message' => __('Something went wrong') . ': ' . __('API') . ' ' . __('Failed'));
            }
        }
        return($result);
    }

    /**
     * Fast check for some request is returning error or not?
     * 
     * @param array $requestReply
     * 
     * @return bool
     */
    public function noError($requestReply) {
        $result = true;
        if (is_array($requestReply)) {
            if (isset($requestReply['error'])) {
                if ($requestReply['error']) {
                    $result = false;
                }
            }
        }
        return($result);
    }

    /**
     * Returns list of all available models as modelId=>modelsData[id/modelname/template]
     * 
     * @return array
     */
    public function modelsGetAll() {
        return($this->executeRequest('models', 'getall'));
    }

    /**
     * Returns list of available storages as storageId=>storagesData[id/path/name]
     * 
     * @return array
     */
    public function storagesGetAll() {
        return($this->executeRequest('storages', 'getall'));
    }

    /**
     * Returns list of all storages states as storageId=>statesArr[state/total/used/free]
     * 
     * @return array
     */
    public function storagesGetStates() {
        return($this->executeRequest('storages', 'getstates'));
    }

    /**
     * Returns array of all available cameras data as:
     * 
     * cameraId[CAMERA]=>id/modelid/ip/login/password/active/storageid/channel/comment
     * cameraId[TEMPLATE]=>DEVICE/PROTO/MAIN_STREAM/SUB_STREAM/RTSP_PORT/HTTP_PORT/SOUND
     * cameraId[STORAGE]=>id/path/name
     * 
     * @return array
     */
    public function camerasGetAll() {
        return($this->executeRequest('cameras', 'getall'));
    }

    /**
     * Creates new camera on NVR
     * 
     * @param int $modelId
     * @param string $ip
     * @param string $login
     * @param string $password
     * @param int $active
     * @param int $storage
     * @param string $description
     * 
     * @return array
     */
    public function camerasCreate($modelId, $ip, $login, $password, $active = 0, $storage = 0, $description = '') {
        $requestData = array(
            'modelid' => $modelId,
            'ip' => $ip,
            'login' => $login,
            'password' => $password,
            'active' => $active,
            'storageid' => $storage,
            'description' => $description
        );
        return($this->executeRequest('cameras', 'create', $requestData));
    }

    /**
     * Activates existing camera
     * 
     * @param int $cameraId
     * 
     * @return array
     */
    public function camerasActivate($cameraId) {
        $requestData = array(
            'cameraid' => $cameraId
        );
        return($this->executeRequest('cameras', 'activate', $requestData));
    }

    /**
     * Deactivates existing camera
     * 
     * @param int $cameraId
     * 
     * @return array
     */
    public function camerasDeactivate($cameraId) {
        $requestData = array(
            'cameraid' => $cameraId
        );
        return($this->executeRequest('cameras', 'deactivate', $requestData));
    }

    /**
     * Changes existing camera description
     * 
     * @param int $cameraId
     * @param string $description
     * 
     * @return array
     */
    public function camerasSetDescription($cameraId, $description = '') {
        $requestData = array(
            'cameraid' => $cameraId,
            'description' => $description
        );
        return($this->executeRequest('cameras', 'setdescription', $requestData));
    }

    /**
     * Deletes existing camera
     * 
     * @param int $cameraId
     * 
     * @return array
     */
    public function camerasDelete($cameraId) {
        $requestData = array(
            'cameraid' => $cameraId
        );
        return($this->executeRequest('cameras', 'delete', $requestData));
    }

    /**
     * Checks is camera registered or not by its IP
     * 
     * @param string $ip
     * 
     * @return array
     */
    public function camerasIsRegistered($ip) {
        $requestData = array(
            'ip' => $ip
        );
        return($this->executeRequest('cameras', 'isregistered', $requestData));
    }

    /**
     * Returns system health data as storages/database/network/channels_total/cahnnels_online/uptime/loadavg
     * 
     * @return array
     */
    public function systemGetHealth() {
        return($this->executeRequest('system', 'gethealth'));
    }

    /**
     * Returns all available users data
     * 
     * @return array
     */
    public function usersGetAll() {
        return($this->executeRequest('users', 'getall'));
    }

    /**
     * Creates new limited user
     * 
     * @param string $login
     * @param string $password
     * 
     * @return array
     */
    public function usersCreate($login, $password) {
        $requestData = array(
            'login' => $login,
            'password' => $password
        );
        return($this->executeRequest('users', 'create', $requestData));
    }

    /**
     * Changes some existing user password to new one
     * 
     * @param string $login
     * @param string $password
     * 
     * @return array
     */
    public function usersChangePassword($login, $password) {
        $requestData = array(
            'login' => $login,
            'password' => $password
        );
        return($this->executeRequest('users', 'changepassword', $requestData));
    }

    /**
     * Checks is user registered or not
     * 
     * @param string $login
     * 
     * @return array
     */
    public function usersIsRegistered($login) {
        $requestData = array(
            'login' => $login
        );
        return($this->executeRequest('users', 'isregistered', $requestData));
    }

    /**
     * Deletes an existing user
     * 
     * @param string $login
     * 
     * @return array
     */
    public function usersDelete($login) {
        $requestData = array(
            'login' => $login
        );
        return($this->executeRequest('users', 'delete', $requestData));
    }

    /**
     * Checks can be user authorized or not
     * 
     * @param string $login
     * @param string $password
     * 
     * @return array
     */
    public function usersCheckAuth($login, $password) {
        $requestData = array(
            'login' => $login,
            'password' => $password
        );
        return($this->executeRequest('users', 'checkauth', $requestData));
    }

    /**
     * Returns all ACLs raw data
     * 
     * @return array
     */
    public function aclsGetAll() {
        return($this->executeRequest('acls', 'getall'));
    }

    /**
     * Returns array of all available user to cameras ACLs
     * 
     * @return array
     */
    public function aclsGetAllCameras() {
        return($this->executeRequest('acls', 'getallcameras'));
    }

    /**
     * Returns array of all available user to channels ACLs
     * 
     * @return array
     */
    public function aclsGetAllChannels() {
        return($this->executeRequest('acls', 'getallchannels'));
    }

    /**
     * Returns array of channels assigned to some user as channelId=>cameraId
     * 
     * @param string $login
     * 
     * @return array
     */
    public function aclsGetChannels($login) {
        $requestData = array(
            'login' => $login
        );
        return($this->executeRequest('acls', 'getchannels', $requestData));
    }

    /**
     * Returns array of channels assigned to some user as channelId=>cameraId
     * 
     * @param string $login
     * 
     * @return array
     */
    public function aclsGetCameras($login) {
        $requestData = array(
            'login' => $login
        );
        return($this->executeRequest('acls', 'getcameras', $requestData));
    }

    /**
     * Creates ACL for some user by cameraId
     * 
     * @param string $login
     * @param int $cameraId
     * 
     * @return array
     */
    public function aclsAssignCamera($login, $cameraId) {
        $requestData = array(
            'login' => $login,
            'cameraid' => $cameraId
        );
        return($this->executeRequest('acls', 'assigncamera', $requestData));
    }

    /**
     * Creates ACL for some user by channelId
     * 
     * @param string $login
     * @param int $channelId
     * 
     * @return array
     */
    public function aclsAssignChannel($login, $channelId) {
        $requestData = array(
            'login' => $login,
            'channelid' => $channelId
        );
        return($this->executeRequest('acls', 'assignchannel', $requestData));
    }

    /**
     * Returns all available channels as channelId=>cameraId
     * 
     * @return array
     */
    public function channelsGetAll() {
        return($this->executeRequest('channels', 'getall'));
    }

    /**
     * Returns some channel screenshot URL
     * 
     * @return array
     */
    public function channelsGetScreenshot($channelId) {
        $requestData = array(
            'channelid' => $channelId
        );
        return($this->executeRequest('channels', 'getscreenshot', $requestData));
    }

    /**
     * Returns list of running recorders as cameraId=>PID
     * 
     * @return array
     */
    public function recordersGetAll() {
        return($this->executeRequest('recorders', 'getall'));
    }

    /**
     * Returns some camera recording process state
     * 
     * @param int $cameraId
     * 
     * @return array
     */
    public function recordersIsRunning($cameraId) {
        $requestData = array(
            'cameraid' => $cameraId
        );
        return($this->executeRequest('recorders', 'isrunning', $requestData));
    }

}
