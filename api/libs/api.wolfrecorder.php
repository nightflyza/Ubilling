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
     * Returns system health data as storages/database/network/channels_total/cahnnels_online/uptime/loadavg
     * 
     * @return array
     */
    public function systemGetHealth() {
        return($this->executeRequest('system', 'gethealth'));
    }

}
