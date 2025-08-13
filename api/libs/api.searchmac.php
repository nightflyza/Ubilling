<?php

/**
 * SearchMAC class that performs MAC address vendor lookup
 */
class SearchMAC {

    protected $altCfg=array();
    /**
     * Vendor cache flag
     *
     * @var bool
     */
    protected $cacheFlag=false;

    /**
     * system cache object placeholder
     *
     * @var object
     */
    protected $cache='';

    /**
     * Cache time in seconds
     *
     * @var int
     */    
    protected $cacheTime=2592000; 

    /**
     * Preloaded vendor cache
     *
     * @var array
     */
    protected $vendorCache=array();
    
    /**
     * HTTP code from last request
     *
     * @var int
     */
    protected $httpCode=0;

    /**
     * User agent string
     *
     * @var string
     */
    protected $agentString='';
    
    /**
     * Cache key name
     *
     * @var string
     */
    const CACHE_KEY='MACVENDB';

    /**
     * imprisoned in a web of night
     * in search of an emerald sky
     */
    public function __construct() {
        $this->loadConfig();
        $this->initCache();
        $this->loadCache();
        $this->setUserAgent();
    }

    /**
     * Loads configuration and sets some options
     * 
     * @return void
     */
    protected function loadConfig() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
        if (isset($this->altCfg['MACVEN_CACHE'])) {
            if ($this->altCfg['MACVEN_CACHE']) {
                $this->cacheFlag=true;
            }
        }
    }

    /**
     * Initializes cache object
     * 
     * @return void
     */
    protected function initCache() {
        $this->cache = new UbillingCache();
    }

    /**
     * Loads cache from system cache
     * 
     * @return void
     */
    protected function loadCache() {
        if ($this->cacheFlag) {
            $this->vendorCache = $this->cache->get(self::CACHE_KEY, $this->cacheTime);
        }
    }

    /**
     * Sets user agent string
     * 
     * @return void
     */
    protected function setUserAgent() {
        $ubVer = file_get_contents('RELEASE');
        $this->agentString = 'MacVenUbilling/' . trim($ubVer);
    }

    /**
     * Returns vendor name for some MAC address using optional caching
     * 
     * @param string $mac
     * 
     * @return string
     */
    public function getVendor($mac) {
        $result='';
        if ($this->cacheFlag) {
                if (!empty($this->vendorCache) and is_array($this->vendorCache)) {
                    if (isset($this->vendorCache[$mac])) {
                        $result = $this->vendorCache[$mac];
                    } else {
                        $vendor = $this->lookupMacVendor($mac);
                        if ($this->httpCode == 200) {
                            $this->vendorCache[$mac] = $vendor;
                            $this->cache->set(self::CACHE_KEY, $this->vendorCache, $this->cacheTime);
                        }
                        $result = $vendor;
                    }
                } else {
                    //empty cache
                    $this->vendorCache = array();
                    $vendor = $this->lookupMacVendor($mac);
                    if ($this->httpCode == 200) {
                        $this->vendorCache[$mac] = $vendor;
                        $this->cache->set(self::CACHE_KEY, $this->vendorCache, $this->cacheTime);
                    }
                    $result = $vendor;
                }
        } else {
            $result = $this->lookupMacVendor($mac);
        }
        return ($result);
    }

    /**
     * Retuns vendor name for some MAC address
     * 
     * @param string $mac
     * 
     * @return string
     */
    protected function lookupMacVendor($mac) {
        $url = 'http://searchmac.com/api/v2/' . $mac;
        $api = new OmaeUrl($url);
        $api->setUserAgent($this->agentString);
        $rawdata = $api->response();
        $this->httpCode = $api->httpCode();
            if (!empty($rawdata)) {
                $result = strip_tags($rawdata);
            } else {
                $result = 'EMPTY';
            }
        return ($result);
        }
}
