<?php

/**
 * Allows to receive some data about IPs and domains
 */
class UbillingWhois {

    /**
     * Current IP for lookup
     *
     * @var string
     */
    protected $ip = '';

    /**
     * System cache object placeholder
     *
     * @var object
     */
    protected $cache = '';

    /**
     * Contains current IP data as ip=>data
     *
     * @var array
     */
    protected $ipData = array();

    /**
     * Data caching timeout
     */
    const CACHE_TIMEOUT = 2592000;

    /**
     * Some predefined routes, URLs etc..
     */
    const CACHE_KEY = 'WHOISDATA';
    const API_IPINFO = 'http://ip-api.com/json/';
    const API_PARAMS = '?fields=status,message,country,countryCode,region,regionName,city,lat,lon,isp,org,as,asname,reverse,query';
    const URL_ASINFO = 'http://bgp.he.net/';

    /**
     * Creates new Whois object instance
     * 
     * @param string $ip
     * @throws Exception
     */
    public function __construct($ip) {
        if (!empty($ip)) {
            $this->setIp($ip);
        } else {
            throw new Exception('EX_EMPTY_IP');
        }

        $this->initCache();
        $this->loadIpData();
    }

    /**
     * Sets current IP
     * 
     * @param string $ip
     * 
     * @return void
     */
    protected function setIp($ip) {
        $this->ip = $ip;
    }

    /**
     * Creates new cache instance for further usage
     * 
     * @return void
     */
    protected function initCache() {
        $this->cache = new UbillingCache();
    }

    /**
     * Runs data loaders for current IP
     * 
     * @return void
     */
    protected function loadIpData() {
        $cachedData = $this->cache->get(self::CACHE_KEY, self::CACHE_TIMEOUT);
        if (empty($cachedData)) {
            $cachedData = array();
        }
        $this->ipData = $cachedData;
        $updateCache = false;
        if (empty($cachedData)) {
            $updateCache = true;
        } else {
            if (!isset($cachedData[$this->ip])) {
                $updateCache = true;
            }
        }

        //cache needs to be updated
        if ($updateCache) {
            $queryUrl = self::API_IPINFO . $this->ip . self::API_PARAMS;
            $api = new OmaeUrl($queryUrl);
            $apiResponse = $api->response();
            if (!empty($apiResponse)) {
                $ipInfoData = json_decode($apiResponse, true);
                $this->ipData[$this->ip] = $ipInfoData;
                $this->cache->set(self::CACHE_KEY, $this->ipData, self::CACHE_TIMEOUT);
            }
        }
    }

    /**
     * Renders minimap if long/lat is present
     * 
     * @global object $ubillingConfig
     * 
     * @return string
     */
    protected function renderMinimap() {
        $result = '';
        if (!empty($this->ipData[$this->ip])) {
            global $ubillingConfig;
            $ipData = $this->ipData[$this->ip];
            $ymconf = $ubillingConfig->getYmaps();
            $result = generic_MapContainer('100%', '400px', 'whoismap');
            $placemarks = generic_MapAddMark($ipData['lat'] . ',' . $ipData['lon'], @$ipData['city'], @$ipData['city'] . ' ' . @$ipData['isp']);
            $result .= generic_MapInit($ipData['lat'] . ',' . $ipData['lon'], 8, $ymconf['TYPE'], $placemarks, '', $ymconf['LANG'], 'whoismap');
        }
        return ($result);
    }

    /**
     * Renders IP ISP/Geo data in human readable view
     * 
     * @return string
     */
    public function renderData() {
        $result = '';
        $rows = '';
        $miniMap = '';

        if (!empty($this->ipData[$this->ip])) {
            $ipData = $this->ipData[$this->ip];
            if ($ipData['status'] != 'fail') {
                $asData = explode(' ', $ipData['as']);
                $asNum = (!empty($asData[0])) ? $asData[0] : '';
                $asLink = (!empty($asNum)) ? wf_Link((self::URL_ASINFO . $asNum), $asNum) : '-';

                $cells = wf_TableCell(__('IP'), '', 'row2');
                $cells .= wf_TableCell($ipData['query']);
                $rows .= wf_TableRow($cells, 'row3');
                $cells = wf_TableCell(__('Reverse DNS'), '', 'row2');
                $cells .= wf_TableCell($ipData['reverse']);
                $rows .= wf_TableRow($cells, 'row3');
                $cells = wf_TableCell(__('ISP name'), '', 'row2');
                $cells .= wf_TableCell($ipData['isp']);
                $rows .= wf_TableRow($cells, 'row3');
                $cells = wf_TableCell(__('AS Name'), '', 'row2');
                $cells .= wf_TableCell($ipData['asname']);
                $rows .= wf_TableRow($cells, 'row3');
                $cells = wf_TableCell(__('AS'), '', 'row2');
                $cells .= wf_TableCell($asLink);
                $rows .= wf_TableRow($cells, 'row3');
                $cells = wf_TableCell(__('ORG'), '', 'row2');
                $cells .= wf_TableCell($ipData['org']);
                $rows .= wf_TableRow($cells, 'row3');

                $cells = wf_TableCell(__('Country'), '', 'row2');
                $cells .= wf_TableCell($ipData['country']);
                $rows .= wf_TableRow($cells, 'row3');
                $cells = wf_TableCell(__('Region'), '', 'row2');
                $cells .= wf_TableCell($ipData['regionName']);
                $rows .= wf_TableRow($cells, 'row3');
                $cells = wf_TableCell(__('City'), '', 'row2');
                $cells .= wf_TableCell($ipData['city']);
                $rows .= wf_TableRow($cells, 'row3');
                $miniMap = $this->renderMinimap();
            } else {
                $cells = wf_TableCell(__('IP'), '', 'row2');
                $cells .= wf_TableCell($ipData['query']);
                $rows .= wf_TableRow($cells, 'row3');
                $cells = wf_TableCell(__('Message'), '', 'row2');
                $cells .= wf_TableCell($ipData['message']);
                $rows .= wf_TableRow($cells, 'row3');
            }
        }


        if (!empty($rows)) {
            $result = wf_TableBody($rows, '100%', 0, '');
            $result .= $miniMap;
        }

        return ($result);
    }

}
