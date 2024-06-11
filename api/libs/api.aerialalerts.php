<?php

/**
 * Aerial raid notification class
 */
class AerialAlerts {

    /**
     * Alerts datasource API placeholder
     *
     * @var object
     */
    protected $api = '';

    /**
     * System caching object placeholder
     *
     * @var object
     */
    protected $cache = '';

    /**
     * Default alerts caching timeout. May be configurable in future.
     *
     * @var int
     */
    protected $alertsCachingTimeout = 10;

    /**
     * System message helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Contains current alerts data
     *
     * @var array
     */
    protected $allAlerts = array();

    /**
     * Some predefined routes, URLS, etc
     */
    const DATA_SOURCE = 'http://ubilling.net.ua/aerialalerts/';
    const MAP_SOURCE = 'http://ubilling.net.ua/aerialalerts/?map=true';
    const ALERTS_KEY = 'AERIALALERTS';
    const URL_ME = '?module=report_aerial';
    const ROUTE_ALL = 'allregions';
    const ROUTE_MAP = 'showmap';

    public function __construct() {
        $this->initMessages();
        $this->initCache();
        $this->initApi();
        $this->loadAlerts();
    }

    /**
     * Inits system messages helper
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Inits basic json api interraction layer
     * 
     * @return void
     */
    protected function initApi() {
        $this->api = new OmaeUrl(self::DATA_SOURCE);
        $this->api->setUserAgent('Ubilling AerialAlerts');
    }

    /**
     * Inits system caching instance for further usage
     * 
     * @return
     */
    protected function initCache() {
        $this->cache = new UbillingCache();
    }

    /**
     * Loads aerial alerts from cache or HTTP API
     * 
     * @return void
     */
    protected function loadAlerts() {
        $this->allAlerts = $this->cache->get(self::ALERTS_KEY, $this->alertsCachingTimeout);
        if (empty($this->allAlerts)) {
            $jsonRaw = $this->api->response();
            $this->allAlerts = json_decode($jsonRaw, true);
            $this->cache->set(self::ALERTS_KEY, $this->allAlerts, $this->alertsCachingTimeout);
        }
    }

    /**
     * Just renders module controls
     * 
     * @return string
     */
    public function renderControls() {
        $result = '';
        $result .= wf_Link(self::URL_ME, wf_img('skins/admannouncements.png') . ' ' . __('Alarm now'), false, 'ubButton');
        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_ALL . '=true', wf_img('skins/zbsannouncements.png') . ' ' . __('All'), false, 'ubButton');
        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_MAP . '=true', wf_img('skins/icon_map_small.png') . ' ' . __('Alerts map'), false, 'ubButton');
        return ($result);
    }

    /**
     * Renders aerial alerts basic report
     * 
     * @return string
     */
    public function renderReport() {
        $result = '';
        $alertCount = 0;
        $renderAllFlag = (ubRouting::checkGet(self::ROUTE_ALL)) ? true : false;
        if (!empty($this->allAlerts)) {
            if (isset($this->allAlerts['states'])) {
                foreach ($this->allAlerts['states'] as $stateName => $stateParams) {
                    if ($stateParams['alertnow']) {
                        $result .= $this->messages->getStyledMessage($stateName, 'error');
                        $alertCount++;
                    } else {
                        if ($renderAllFlag) {
                            $result .= $this->messages->getStyledMessage($stateName, 'success');
                        }
                    }
                }
                // following code seems to be unused ;/
                if (!$alertCount) {
                    $result .= $this->messages->getStyledMessage(__('Unbelievable, there are no air alarms at the moment. Does this happen at all?'), 'success');
                }
            } else {
                $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('Data') . ' ' . __('is corrupted'), 'error');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('Empty reply received'), 'error');
        }
        return ($result);
    }

    /**
     * Returns precached alerts map html image code
     * 
     * @return string
     */
    public function renderMap() {
        $result = '';
        $nowTime = time();
        $fileName = 'exports/alertsmap_' . date("Y-m-d_H_i", $nowTime) . '.dat';

        if (!file_exists($fileName)) {
            $mapApi = new OmaeUrl(self::MAP_SOURCE);
            $mapApi->setUserAgent('Ubilling AerialAlertsMap');
            $rawMap = $mapApi->response();
            if ($mapApi->httpCode() == 200) {
                $rawMap = base64_encode($rawMap);
                file_put_contents($fileName, $rawMap);
            } else {
                $rawMap = '';
            }
        } else {
            $rawMap = file_get_contents($fileName);
        }

        if (!empty($rawMap)) {
            $encodedImage = 'data:image/png;base64,' . $rawMap;
            $result = wf_tag('center') . wf_img_sized($encodedImage, date("Y-m-d H:i", $nowTime),'70%') . wf_tag('center', true);
        } else {
            $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('Unable to load data'), 'error');
        }

        return ($result);
    }

    /**
     * Renders DarkVoid notification if monitored region now under alarm
     * 
     * @param string $region
     * 
     * @return string
     */
    public function renderRegionNotification($region = '') {
        $result = '';
        if (!empty($region)) {
            $region = trim($region);
            if (!empty($this->allAlerts)) {
                if (isset($this->allAlerts['states'])) {
                    if (isset($this->allAlerts['states'][$region])) {
                        $regionAlarm = $this->allAlerts['states'][$region]['alertnow'];
                        if ($regionAlarm) {
                            $alarmStart = $this->allAlerts['states'][$region]['changed'];
                            $icon = wf_img_sized('skins/nuclear_bomb.png', __('Alarm now') . ' - ' . $region . ' ' . __('from') . ' ' . $alarmStart, 32, 32);
                            $result .= wf_Link(AerialAlerts::URL_ME, $icon);
                        }
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Returns json with notification region alert state
     * 
     * @return string
     */
    public function usCallback($region = '') {
        $tmp = array();
        $result = '';
        if (!empty($region)) {
            $region = trim($region);
            if (!empty($this->allAlerts)) {
                if (isset($this->allAlerts['states'])) {
                    if (isset($this->allAlerts['states'][$region])) {
                        $tmp['region'] = $region;
                        $tmp['alert'] = $this->allAlerts['states'][$region]['alertnow'];
                        $tmp['changed'] = $this->allAlerts['states'][$region]['changed'];
                        $result = json_encode($tmp);
                    }
                }
            }
        }

        return ($result);
    }
}
