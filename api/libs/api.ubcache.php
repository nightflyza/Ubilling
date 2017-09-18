<?php

class UbillingCache {

    /**
     * System alter.ini config content
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Cache storage type: files, memcached, fake
     * via UBCACHE_STORAGE option
     *
     * @var string
     */
    protected $storage = '';

    /**
     * Memcached server IP/Hostname
     * via MEMCACHED_SERVER option
     * 
     * @var string
     */
    protected $memcachedServer = '';

    /**
     * Memcached server port
     * via MEMCACHED_PORT option
     *
     * @var int
     */
    protected $memcachedPort = '';

    /**
     * Redis server IP/Hostname
     * via REDIS_SERVER option
     * 
     * @var string
     */
    protected $redisServer = '';

    /**
     * Redis server port
     * via REDIS_PORT option
     *
     * @var int
     */
    protected $redisPort = '';

    /**
     * File storage path: "exports/" by default
     *
     * @var string
     */
    protected $storagePath = '';

    /**
     * Single instance of memcached object
     *
     * @var object
     */
    protected $memcached = ''; // single memcached object

    const CACHE_PREFIX = 'UBCACHE_';

    /**
     * Creates new UbillingCache instance
     * 
     * @return void
     */
    public function __construct() {
        $this->loadAlter();
        $this->setOptions();
        $this->initStorageServerCache();
    }

    /**
     * Loads global alter config into protected property
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadAlter() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Sets object storage mode
     * 
     * @return void
     */
    protected function setOptions() {
        if (isset($this->altCfg['UBCACHE_STORAGE'])) {
            $this->storage = $this->altCfg['UBCACHE_STORAGE'];
        } else {
            $this->storage = 'fake';
        }

        if ($this->storage == 'memcached') {
            if (isset($this->altCfg['MEMCACHED_SERVER'])) {
                $this->memcachedServer = $this->altCfg['MEMCACHED_SERVER'];
            } else {
                $this->memcachedServer = 'localhost';
            }
            if (isset($this->altCfg['MEMCACHED_PORT'])) {
                $this->memcachedPort = $this->altCfg['MEMCACHED_PORT'];
            } else {
                $this->memcachedPort = 11211;
            }
        }

        if ($this->storage == 'redis') {
            if (isset($this->altCfg['REDIS_SERVER'])) {
                $this->redisServer = $this->altCfg['REDIS_SERVER'];
            } else {
                $this->redisServer = 'localhost';
            }
            if (isset($this->altCfg['REDIS_PORT'])) {
                $this->redisPort = $this->altCfg['REDIS_PORT'];
            } else {
                $this->redisPort = 6379;
            }
        }

        if ($this->storage == 'files') {
            $this->storagePath = 'exports/';
        }
    }

    /**
     * Inits storage server cache if it needed
     * 
     * @return void
     */
    protected function initStorageServerCache() {
        // Init Memcached
        if ($this->storage == 'memcached') {
            $this->memcached = new Memcached();
            $this->memcached->addServer($this->memcachedServer, $this->memcachedPort);
        }
        // Init Redis
        if ($this->storage == 'redis') {
            $this->redis = new Redis();
            $this->redis->connect($this->redisServer, $this->redisPort);
            $this->redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
        }
    }

    /**
     * Generates key storable internal name
     * 
     * @param string $key
     * 
     * @return string
     */
    protected function genKey($key) {
        $result = self::CACHE_PREFIX . vf($key);
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
    public function set($key, $data, $expiration = 2592000) {
        $key = $this->genKey($key);
        // Set expiration time not more 1 month
        $expiration = ($expiration > 2592000) ? '2592000' : $expiration;

        //files storage
        if ($this->storage == 'files') {
            file_put_contents($this->storagePath . $key, serialize($data));
        }

        //memcached storage
        if ($this->storage == 'memcached') {
            $this->memcached->set($key, $data, $expiration);
        }

        //redis storage
        if ($this->storage == 'redis') {
            $this->redis->set($key, $data);
            $this->redis->setTimeout($key, $expiration);
        }
    }

    /**
     * Returns data by key name. Empty if no data exists or cache expired.
     * 
     * @param string $key Storage key name
     * @param int   $expiration Expiration time in seconds
     * 
     * @return string
     */
    public function get($key, $expiration = 2592000) {
        $result = '';
        $keyRaw = $key;
        $key = $this->genKey($key);
        //files storage
        if ($this->storage == 'files') {
            $cacheName = $this->storagePath . $key;
            $cacheTime = time() - $expiration;
            $updateCache = false;
            if (file_exists($cacheName)) {
                $updateCache = false;
                if ((filemtime($cacheName) > $cacheTime)) {
                    $updateCache = false;
                } else {
                    $updateCache = true;
                }
            } else {
                $updateCache = true;
            }

            if (!$updateCache) {
                //read data directly from cache
                $data = file_get_contents($cacheName);
                $result = unserialize($data);
            } else {
                //cache expired, return empty result
                $result = '';
                $this->delete($keyRaw);
            }
            return ($result);
        }

        //memcached storage
        if ($this->storage == 'memcached') {
            $result = $this->memcached->get($key);
            if (!$result) {
                $result = '';
            }
            return ($result);
        }

        //redis storage
        if ($this->storage == 'redis') {
            $result = $this->redis->get($key);
            if (!$result) {
                $result = '';
            }
            return ($result);
        }

        //fake storage
        if ($this->storage == 'fake') {
            $result = '';
            return ($result);
        }

        return ($result);
    }

    /**
     * Returns data from cache by key or runs callback and fills new cache data
     * 
     * @param string $key  Storage key
     * @param Closure $callback Callback function 
     * @param int $expiration Expiration time in seconds
     * 
     * @return string
     */
    public function getCallback($key, Closure $callback, $expiration = 2592000) {
        // Use this class get function
        $result = $this->get($key, $expiration);
        if (!$result) {
            // If not have result from class get function
            // return $callback data function and set new cache
            $result = $callback();
            $this->set($key, $result, $expiration);
        }
        return ($result);
    }

    /**
     * Deletes data from cache by key name
     * 
     * @param string $key
     * 
     * @return void
     */
    public function delete($key) {
        $key = $this->genKey($key);

        //files storage
        if ($this->storage == 'files') {
            if (file_exists($this->storagePath . $key)) {
                unlink($this->storagePath . $key);
            }
        }

        //memcached storage
        if ($this->storage == 'memcached') {
            $this->memcached->delete($key);
        }

        //redis storage
        if ($this->storage == 'redis') {
            $this->redis->delete($key);
        }
    }

    /**
     * Show all data from cache
     * 
     * @return void
     */
    public function getAllcache($show_data = '') {
        $result = array();
        //files storage
        if ($this->storage == 'files') {
            $cache = scandir($this->storagePath);
            $keys = array_diff($cache, array('..', '.', '.gitignore', '.htaccess'));
            $keys = preg_grep("/^" . self::CACHE_PREFIX . "/", $keys);
            if ($show_data) {
                $result = array();
                foreach ($keys as $key => $file) {
                    $result[$key]['key'] = $file;
                    $result[$key]['value'] = unserialize(file_get_contents($this->storagePath . $file));
                }
            } else {
                $result = $keys;
            }
            return($result);
        }

        //memcached storage
        if ($this->storage == 'memcached') {
            $keys = $this->memcached->getAllKeys();
            $keys = preg_grep("/^" . self::CACHE_PREFIX . "/", $keys);
            if ($show_data) {
                $this->memcached->getDelayed($keys);
                $result = $this->memcached->fetchAll();
            } else {
                $result = $keys;
            }
            return($result);
        }

        //redis storage
        if ($this->storage == 'redis') {
            $keys = $this->redis->keys(self::CACHE_PREFIX . '*');
            if ($show_data) {
                $value = $this->redis->mGet($keys);
                foreach ($keys as $id => $key) {
                    $result[$id]['key'] = $key;
                    $result[$id]['value'] = $value[$id];
                }
            } else {
                $result = $keys;
            }
            return($result);
        }
    }

    /**
     * Delete all data from cache
     * 
     * @return void
     */
    public function deleteAllcache() {
        $cache_data = $this->getAllcache();

        //files storage
        if ($this->storage == 'files' and ! empty($cache_data)) {
            foreach ($cache_data as $cache) {
                unlink($this->storagePath . $cache);
            }
        }

        //memcached storage
        if ($this->storage == 'memcached' and ! empty($cache_data)) {
            $result = $this->memcached->deleteMulti($cache_data);
            return($result);
        }

        //redis storage
        if ($this->storage == 'redis' and ! empty($cache_data)) {
            $result = $this->redis->delete($cache_data);
            return($result);
        }
    }

}

?>