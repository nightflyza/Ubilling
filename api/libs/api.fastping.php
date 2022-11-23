<?php

/**
 * Fast ping implementation
 */
class FastPing {

    /**
     * Contains system alter.ini config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains system billing.ini config as key=>value
     *
     * @var array
     */
    protected $billCfg = array();

    /**
     * StarDust process manager instance placeholder
     *
     * @var object
     */
    protected $pid = '';

    /**
     * System caching engine instance placeholder
     *
     * @var object
     */
    protected $cache = '';

    /**
     * Contains cached data from previous runs as ip=>state[1/0]
     *
     * @var array
     */
    protected $cachedData = array();

    /**
     * Contains cached dead devices data as ip=>location
     *
     * @var array
     */
    protected $deadCache = array();

    /**
     * Contains system sudo full path
     *
     * @var string
     */
    protected $sudoPath = '/usr/local/bin/sudo';

    /**
     * Contains default fping path
     *
     * @var string
     */
    protected $fpingPath = '/usr/local/sbin/fping -r 1 -t 10';

    /**
     * Contains some predefined stuff
     */
    const PID_NAME = 'FASTPING';
    const CACHE_KEY = 'FASTPING';
    const CACHE_DEAD = 'FASTDEAD';
    const LIST_PATH = 'exports/fastping_iplist';
    const MASK_ALIVE = 'is alive';
    const CACHE_TIMEOUT = 2592000;

    public function __construct() {
        $this->loadConfigs();
        $this->setOptions();
        $this->initStarDust();
        $this->initCache();
        $this->loadCache();
    }

    /**
     * Loads all required configs in protected propeties for futher usage
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfigs() {
        global $ubillingConfig;
        $this->billCfg = $ubillingConfig->getBilling();
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Sets required system options
     * 
     * @return void
     */
    protected function setOptions() {
        $this->fpingPath = $this->altCfg['FPING_PATH'];
        $this->sudoPath = $this->billCfg['SUDO'];
    }

    /**
     * Inits system process manager
     * 
     * @return void 
     */
    protected function initStarDust() {
        $this->pid = new StarDust(self::PID_NAME);
    }

    /**
     * Inits system cache
     * 
     * @return void
     */
    protected function initCache() {
        $this->cache = new UbillingCache();
    }

    /**
     * Loads previous runs results into protected property cachedData
     * 
     * @return void
     */
    protected function loadCache() {
        $this->cachedData = $this->cache->get(self::CACHE_KEY, self::CACHE_TIMEOUT);
        $this->deadCache = $this->cache->get(self::CACHE_DEAD, self::CACHE_TIMEOUT);
        if (empty($this->cachedData)) {
            $this->cachedData = array();
        }
        if (empty($this->deadCache)) {
            $this->deadCache = array();
        }
    }

    /**
     * Saves fastping results to cache
     * 
     * @param array $data
     * @param array $deadSwitches
     * 
     * @return void
     */
    protected function saveCache($data, $deadSwitches) {
        if (empty($data)) {
            $data = array();
        }
        if (empty($deadSwitches)) {
            $deadSwitches = array();
        }
        $this->cache->set(self::CACHE_KEY, $data, self::CACHE_TIMEOUT);
        $this->cache->set(self::CACHE_DEAD, $deadSwitches, self::CACHE_TIMEOUT);
    }

    /**
     * Returns all devices states from previous run
     * 
     * @return array
     */
    public function getAllStates() {
        return($this->cachedData);
    }

    /**
     * Returns selected IP last state
     * 
     * @param string $ip
     * 
     * @return int
     */
    public function getState($ip) {
        $result = false;
        if (isset($this->cachedData[$ip])) {
            $result = $this->cachedData[$ip];
        }
        return($result);
    }

    /**
     * Performs fast check is some IP alive?
     * 
     * @param string $ip
     * 
     * @return bool
     */
    public function isAlive($ip) {
        $result = ( $this->getState($ip)) ? true : false;
        return($result);
    }

    /**
     * Performs fast check is some IP dead?
     * 
     * @param string $ip
     * 
     * @return bool
     */
    public function isDead($ip) {
        $result = ( $this->getState($ip)) ? false : true;
        return($result);
    }

    /**
     * Runs fping system binary and returns it result
     * 
     * @return array
     */
    protected function runPing() {
        $result = '';
        if (file_exists(self::LIST_PATH)) {
            $command = $this->sudoPath . ' ' . $this->fpingPath . ' -f ' . self::LIST_PATH;
            $result = shell_exec($command);
        }
        return($result);
    }

    /**
     * Performs fast ping of all available active devices from switches directory
     * 
     * @return array dead siwtches as ip=>location
     */
    public function repingDevices() {
        $result = array();
        $deadSwitches = array();
        if ($this->pid->notRunning()) {
            //starting process
            $this->pid->start();
            $allSwitches = zb_SwitchesGetAll();
            $ipsList = '';
            if (!empty($allSwitches)) {
                $uniqueIps = array();
                //preprocessing switches
                foreach ($allSwitches as $io => $each) {
                    if (!empty($each['ip']) AND ! ispos($each['desc'], 'NP')) {
                        if (!isset($uniqueIps[$each['ip']])) {
                            $ipsList .= $each['ip'] . PHP_EOL;
                            $uniqueIps[$each['ip']] = $each['location'];
                        }
                    }
                }

                //saving IPs list and running fping
                if (!empty($ipsList)) {
                    file_put_contents(self::LIST_PATH, $ipsList);
                    $fpingRaw = $this->runPing();
                    if (!empty($fpingRaw)) {
                        foreach ($uniqueIps as $devIp => $devLoc) {
                            $aliveFilter = $devIp . ' ' . self::MASK_ALIVE;
                            if (ispos($fpingRaw, $aliveFilter)) {
                                $result[$devIp] = 1;
                            } else {
                                $result[$devIp] = 0;
                                $deadSwitches[$devIp] = $devLoc;
                            }
                        }
                    }

                    //update cache
                    $this->cachedData = $result;
                    $this->saveCache($result, $deadSwitches);
                    //stopping process
                    $this->pid->stop();
                }
            }
        } else {
            //data from cache?
            if (!empty($this->cachedData)) {
                
            }
        }

        return($deadSwitches);
    }

}
