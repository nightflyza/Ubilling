<?php

class Lightning {

    /**
     * Threshold of calls after caching will be used
     *
     * @var int
     */
    protected $massThreshold = 10;

    /**
     * User data caching timeout in seconds
     *
     * @var int
     */
    protected $cachingTimeout = 60;

    /**
     * Summary count of calls on current minute
     *
     * @var int
     */
    protected $startsCount = 0;

    /**
     * Memcached object placeholder
     *
     * @var object
     */
    protected $memcached = '';

    /**
     * default memcached server IP
     *
     * @var string
     */
    protected $memcachedServer = '127.0.0.1';

    /**
     * default memcached server port
     *
     * @var type 
     */
    protected $memcachedPort = 11211;

    /**
     * Massive user initialization flag
     *
     * @var bool
     */
    protected $massrun = false;

    /**
     * Contains all users data array
     *
     * @var array
     */
    protected $allUserData = array();

    /**
     * Predefined constants, etc..
     */
    const CACHE_PREFIX = 'LINIT_';
    const MASS_KEY = 'MASSRUNCOUNT';
    const USERDATA = 'LIGHTNING_DATA';
    const MAC = 'MAC_';
    const SPEED = 'SPEED_';
    const SPEEDUP = 'SPEEDUP_';

    public function __construct() {
        $this->initMemcached();
        $this->detectMassRun();
        $this->loadCacheData();
    }

    /**
     * Inits memcached object for further usage
     * 
     * @return void
     */
    protected function initMemcached() {
        $this->memcached = new Memcached();
        $this->memcached->addServer($this->memcachedServer, $this->memcachedPort);
    }

    /**
     * Generates key storable internal name
     * 
     * @param string $key
     * 
     * @return string
     */
    protected function genCacheKey($key) {
        $result = self::CACHE_PREFIX . $key;
        return ($result);
    }

    /**
     * Returns data by key name. Empty if no data exists or cache expired.
     * 
     * @param string $key Storage key name
     * @param int   $expiration Expiration time in seconds
     * 
     * @return mixed
     */
    protected function getCache($key, $expiration = 2592000) {
        $result = '';
        $keyRaw = $key;
        $key = $this->genCacheKey($key);
        $result = $this->memcached->get($key);
        if (!$result) {
            $result = '';
        }


        return ($result);
    }

    /**
     * Puts data into cache storage
     * 
     * @param string $key
     * @param string $data
     * @param int $expiration
     * 
     * @return void
     */
    protected function setCache($key, $data, $expiration = 2592000) {
        $key = $this->genCacheKey($key);
        // Set expiration time not more 1 month
        $expiration = ($expiration > 2592000) ? '2592000' : $expiration;
        $this->memcached->set($key, $data, $expiration);
    }

    /**
     * Detects massive user init. Sets specific flag for the instance.
     * 
     * @return void
     */
    protected function detectMassRun() {
        $currentMinute = date("H:i");
        $this->startsCount = $this->getCache(self::MASS_KEY . $currentMinute, $this->cachingTimeout);
        if (empty($this->startsCount)) {
            $this->startsCount=0;
        }
        $this->startsCount++;

        if ($this->startsCount >= $this->massThreshold) {
            $this->massrun = true;
        }
        $this->setCache(self::MASS_KEY . $currentMinute, $this->startsCount, $this->cachingTimeout);
    }

    /**
     * Preloads all users data on mass-run detected and stores it in cache
     * 
     * @return void
     */
    protected function loadCacheData() {
        if ($this->massrun) {
            $this->allUserData = $this->getCache(self::USERDATA, $this->cachingTimeout);
            if (empty($this->allUserData)) {
                $config = parse_ini_file(dirname(__FILE__) . "/config");
                $dbport = (empty($config['port'])) ? 3306 : $config['port'];
                $loginDB = new mysqli($config['host'], $config['username'], $config['password'], $config['database'], $dbport);


                $resultTmp = array();
                $queryUsers = "SELECT `login`,`IP`,`Tariff` from `users`";
                $usersRaw = $loginDB->query($queryUsers);
                if (!empty($usersRaw)) {
                    while ($eachLine = mysqli_fetch_assoc($usersRaw)) {
                        if (!empty($eachLine)) {
                            $resultTmp[$eachLine['login']]['IP'] = $eachLine['IP'];
                            $resultTmp[$eachLine['login']]['Tariff'] = $eachLine['Tariff'];
                            $resultTmp[$eachLine['login']]['mac'] = '';
                            $resultTmp[$eachLine['login']]['speeddown'] = '';
                            $resultTmp[$eachLine['login']]['speedup'] = '';
                            $resultTmp[$eachLine['login']]['speedoverride'] = '';
                        }
                    }

                    $queryMac = "SELECT `ip`,`mac` FROM `nethosts`";
                    $macTmp = array();
                    $macRaw = $loginDB->query($queryMac);
                    if (!empty($macRaw)) {
                        while ($eachLine = mysqli_fetch_assoc($macRaw)) {
                            $macTmp[$eachLine['ip']] = $eachLine['mac'];
                        }
                    }

                    $queryTariffSpeeds = "SELECT `tariff`,`speeddown`,`speedup` from `speeds`";
                    $tariffSpeedsRaw = $loginDB->query($queryTariffSpeeds);
                    $tariffSpeedsTmp = array();
                    if (!empty($tariffSpeedsRaw)) {
                        while ($eachLine = mysqli_fetch_assoc($tariffSpeedsRaw)) {
                            $tariffSpeedsTmp[$eachLine['tariff']]['speeddown'] = $eachLine['speeddown'];
                            $tariffSpeedsTmp[$eachLine['tariff']]['speedup'] = $eachLine['speedup'];
                        }
                    }

                    $querySpeedOverrides = "SELECT `login`,`speed` FROM `userspeeds` WHERE `speed`!='0'";
                    $speedOverridesRaw = $loginDB->query($querySpeedOverrides);
                    $speedOverridesTmp = array();
                    if (!empty($speedOverridesRaw)) {
                        while ($eachLine = mysqli_fetch_assoc($speedOverridesRaw)) {
                            $speedOverridesTmp[$eachLine['login']] = $eachLine['speed'];
                        }
                    }


                    //building final result
                    foreach ($resultTmp as $eachLogin => $eachData) {
                        $userTariff = $eachData['Tariff'];
                        if (isset($macTmp[$eachData['IP']])) {
                            $userMac = $macTmp[$eachData['IP']];
                            $resultTmp[$eachLogin]['mac'] = $userMac;
                            $this->setCache(self::MAC . $eachLogin, $userMac, $this->cachingTimeout);
                        }

                        if (isset($tariffSpeedsTmp[$userTariff])) {
                            $speedDown = $tariffSpeedsTmp[$userTariff]['speeddown'];
                            $speedUp = $tariffSpeedsTmp[$userTariff]['speedup'];
                            $resultTmp[$eachLogin]['speeddown'] = $speedDown;
                            $resultTmp[$eachLogin]['speedup'] = $speedUp;
                            $this->setCache(self::SPEED . $eachLogin, $speedDown, $this->cachingTimeout);
                            $this->setCache(self::SPEEDUP . $eachLogin, $speedUp, $this->cachingTimeout);
                        }

                        if (isset($speedOverridesTmp[$eachLogin])) {
                            $userSpeedOverride = $speedOverridesTmp[$eachLogin];
                            $resultTmp[$eachLogin]['speedoverride'] = $userSpeedOverride;
                            $this->setCache(self::SPEED . $eachLogin, $userSpeedOverride, $this->cachingTimeout);
                            $this->setCache(self::SPEEDUP . $eachLogin, $userSpeedOverride, $this->cachingTimeout);
                        }
                    }
                }
                $loginDB->close();

                $this->allUserData = $resultTmp;
                $this->setCache('LIGHTNING_DATA', $this->allUserData, $this->cachingTimeout);
            }
        }
    }

    /**
     * Runs separate database query
     * 
     * @param string $query
     * 
     * @return array
     */
    protected function runQuery($query) {
        $result = array();
        $config = parse_ini_file(dirname(__FILE__) . "/config");
        $dbport = (empty($config['port'])) ? 3306 : $config['port'];
        $loginDB = new mysqli($config['host'], $config['username'], $config['password'], $config['database'], $dbport);
        $rawData = $loginDB->query($query);

        if (!empty($rawData)) {
            while ($eachLine = mysqli_fetch_assoc($rawData)) {
                $result[] = $eachLine;
            }
        }

        $loginDB->close();
        return($result);
    }

    /**
     * Returns user MAC
     * 
     * @param string $login
     * 
     * @return string
     */
    public function getMac($login) {
        $result = '';
        if (isset($this->allUserData[$login])) {
            $result = $this->allUserData[$login]['mac'];
        } else {
            $query = "SELECT `nethosts`.`mac` FROM `users` INNER JOIN `nethosts` USING (`ip`) WHERE `users`.`login`='" . $login . "'";
            $resultTmp = $this->runQuery($query);
            if (isset($resultTmp[0]['mac'])) {
                $result = $resultTmp[0]['mac'];
            }
        }
        return($result);
    }

    /**
     * Returns user download speed
     * 
     * @param string $login
     * 
     * @return string
     */
    public function getSpeed($login) {
        $result = '';
        if (isset($this->allUserData[$login])) {
            if (empty($this->allUserData[$login]['speedoverride'])) {
                $result = $this->allUserData[$login]['speeddown'];
            } else {
                $result = $this->allUserData[$login]['speedoverride'];
            }
        } else {
            $query = "SELECT `speed` FROM `userspeeds` where `login`='" . $login . "';";
            $speedOverride = $this->runQuery($query);
            if (!empty($speedOverride)) {
                $override = $speedOverride[0]['speed'];
            }

            if (empty($override)) {
                $query = "SELECT `speeddown` from `speeds` INNER JOIN (SELECT `Tariff` FROM users WHERE `login`='" . $login . "') AS t_u USING (`Tariff`)";
                $tariffSpeed = $this->runQuery($query);
                if (isset($tariffSpeed[0])) {
                    $result = $tariffSpeed[0]['speeddown'];
                }
            } else {
                $result = $override;
            }
        }
        return($result);
    }

    /**
     * Returns user upload speed
     * 
     * @param string $login
     * 
     * @return string
     */
    public function getSpeedUp($login) {
        $result = '';
        if (isset($this->allUserData[$login])) {
            if (empty($this->allUserData[$login]['speedoverride'])) {
                $result = $this->allUserData[$login]['speedup'];
            } else {
                $result = $this->allUserData[$login]['speedoverride'];
            }
        } else {
            $query = "SELECT `speed` FROM `userspeeds` where `login`='" . $login . "';";
            $speedOverride = $this->runQuery($query);
            if (!empty($speedOverride)) {
                $override = $speedOverride[0]['speed'];
            }

            if (empty($override)) {
                $query = "SELECT `speedup` from `speeds` INNER JOIN (SELECT `Tariff` FROM users WHERE `login`='" . $login . "') AS t_u USING (`Tariff`)";
                $tariffSpeed = $this->runQuery($query);
                if (isset($tariffSpeed[0])) {
                    $result = $tariffSpeed[0]['speedup'];
                }
            } else {
                $result = $override;
            }
        }
        return($result);
    }

}
