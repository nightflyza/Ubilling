<?php

/**
 * Uncomplicated process manager
 */
class StarDust {

    /**
     * Contains current process name/identifier
     *
     * @var string
     */
    protected $processName = '';

    /**
     * System caching object placeholder
     *
     * @var object
     */
    protected $cache = '';

    /**
     * Contains current process states as processName=>processStats
     *
     * @var array
     */
    protected $allProcessStates = array();

    /**
     * Store each process state in separate STARDUST_PID cache keys
     *
     * @var bool
     */
    protected $separateKeys = false;

    /**
     * Use flock mechanics instead of DB locks
     *
     * @var bool
     */
    protected $flockFlag = false;

    /**
     * Process flock file path
     *
     * @var string
     */
    protected $lockFile = '';

    /**
     * flock handle
     *
     * @var string
     */
    protected $handle = '';

    /**
     * Some predefined stuff
     */
    const LOCK_DIR = 'exports/';
    const LOCK_NAME = 'stardustLockfree';
    const LOCK_PREFIX = 'stardustPID_';
    const CACHE_TIMEOUT = 2592000;
    const CACHE_KEY = 'STARDUST';
    const REALTIME_PRECISSION = 5;

    public function __construct($processName = '', $separateKeys = false, $flockOnly = false) {
        $this->setFlock($flockOnly);
        $this->setProcess($processName);
        $this->setZaWarudo($separateKeys);
        $this->initCache();
        $this->loadCache();
    }

    //            ⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⡇⢿⢻⣉⣳⣄⣀⣹⠾⠿⠶⠶⠶⠾⠿⣯⣿⣿⣿⣏⣿⣠⣿⣿⣿⣿⡿⠄⠀⠑⢒⡲⠂⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
    //        ⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢠⢾⠼⣿⣧⠦⠴⢒⣒⣒⣒⣒⣒⣒⣒⣒⣀⣤⠶⢶⣤⡏⣻⣿⣿⠿⢧⣤⠤⡤⢼⡯⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
    //        ⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠸⣴⣞⠿⠛⠉⠉⠉⠉⠉⡭⠭⣭⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⠿⡿⣼⠃⠀⡨⠟⠁⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
    //        ⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⣠⠴⠋⠁⠀⠀⠀⠀⠀⠐⠀⢉⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⡿⢿⣟⣫⣤⡴⢷⡟⣦⡞⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
    //        ⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠰⠋⠉⠁⠒⠒⠀⠀⠀⠠⠤⠤⠶⠾⠿⠿⢿⡿⡟⠛⠟⠏⢉⣁⣤⠬⠒⢛⣫⣿⣿⣾⣟⣿⠆⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
    //        ⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠉⠉⠉⠉⠙⢒⣶⢶⡖⣶⣶⡶⢦⣤⣴⢶⣶⣶⣾⣟⣿⣻⣇⢤⣴⣾⠿⣿⢿⢹⣿⡿⡿⠃⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
    //        ⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢠⢴⣶⣒⡾⣿⣟⢧⠈⢿⡿⠺⠟⠓⣊⡴⢙⡏⠉⣏⢻⣉⠺⠿⣿⠾⠋⠘⡼⣿⡟⠁⠀⠀⠀⢀⡠⠤⠤⠤⢄⡀⠀⠀⠀⠀⠀⠀⠀⠀⠀
    //        ⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⣇⢯⣿⣞⣷⣿⣿⢆⠁⢿⠡⠔⠚⢛⣿⣿⠟⡇⠀⡟⢿⣯⠿⠗⠂⢤⢀⢧⣿⣟⡁⠀⠀⡠⠚⢁⣤⢔⣒⣒⠒⠚⢦⠀⠀⠀⠀⠀⠀⠀⠀
    //        ⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠈⠢⣿⣿⣿⣿⣟⣿⡇⠘⡇⠀⠀⠩⠻⡁⠀⣇⠀⠃⠀⠀⠈⠀⠀⠈⡟⣸⣿⣿⣯⣿⡿⠁⣴⣿⠋⠁⠀⠀⠙⣦⢰⡇⠀⠀⠀⠀⠀⠀⠀
    //        ⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢠⣤⣤⡈⢳⣿⣿⣿⣷⣧⠀⢃⠀⠀⠀⠀⢱⡀⡅⠀⡀⡄⠀⠀⠀⠀⣸⢡⣿⣿⣿⣿⣿⣇⣾⢻⣿⡻⣖⣦⣄⢠⣇⢀⡇⠀⠀⠀⠀⠀⠀⠀
    //        ⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢸⡟⣎⢿⡌⢻⣿⣿⣿⣿⡆⠸⡄⠀⠀⠀⠀⠛⢿⣿⠛⠁⠀⠀⠀⢀⡇⣼⣿⣿⣿⣿⣿⣇⢸⣽⣿⣿⣷⣽⠺⣓⢾⣿⠁⣠⣄⡀⠀⠀⠀⠀
    //        ⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢸⣇⠸⣮⢧⣏⡻⣿⣿⣿⣿⡷⢧⡀⠀⣀⣀⣤⣄⣠⣤⣀⡀⠀⢀⡜⡼⣿⢿⣿⣿⣿⣿⣿⣾⣿⣿⣿⣿⡮⣿⣮⠑⢌⠻⣋⣩⡙⢢⡀⠀⠀
    //        ⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢀⣴⣋⣮⣆⡾⣸⡿⠹⡞⣿⣿⣿⣇⣸⣳⡀⠉⣅⣀⣉⣉⣀⣉⠉⢀⣮⣾⠇⡌⠓⢝⣿⣿⣿⣿⣿⣿⠁⣉⠁⠀⠈⡝⢦⡀⡷⠈⣯⡻⣦⡈⢆⠀
    //        ⠀⠀⠀⠀⠀⣀⢤⢚⣉⡓⠚⠉⠲⣿⣿⣿⣿⣯⣿⡄⣽⣿⣿⣿⣿⣿⣷⣵⡀⠈⢻⠉⠙⠛⠋⣠⣿⣿⠏⠀⠰⡄⠀⠙⠻⣿⣿⣿⣿⣾⡇⠀⠀⢀⠇⠦⣣⠃⣼⠻⢿⡎⣿⣄⠱
    //        ⡀⠀⠴⠒⠉⠀⢿⣾⣾⣿⣷⣄⠀⢾⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⢈⠻⣿⣄⡟⠀⠀⠀⣰⣿⣿⡟⣰⠸⣆⢹⡄⣄⡀⠈⠛⠉⣻⣿⡇⠀⠀⡼⠈⡦⣿⣶⣿⣿⣾⠵⣶⣛⢧
    //        ⠀⠀⠀⠀⠀⠀⠈⠹⠽⣿⣿⣻⣶⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⠀⢻⣎⠻⢿⣶⣶⣶⣿⣿⣿⠀⢹⣇⠈⠳⣿⣾⣟⣷⣾⣾⡿⢻⣿⣄⠀⢣⠶⣱⣿⣿⣾⣿⣧⡒⠻⣿⠸
    //        ⠀⠀⠀⠀⠀⠀⢀⣀⣼⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⠀⠀⠻⡄⢈⣿⣟⢿⣿⡟⢸⡀⠈⣿⠀⠀⢈⣿⡿⠛⠋⠀⠈⠁⣿⣿⣶⠌⡧⣿⣿⣿⣿⣿⣿⣿⡄⠙⢣
    //        ⠀⠀⠀⢀⣰⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣧⣤⣶⣿⣿⣿⣿⡀⢻⠀⠀⣷⣄⣷⣀⡴⠏⠁⠀⠀⠀⠀⠀⣰⢟⣿⣿⣿⣾⣿⣿⣿⣿⣿⣿⣿⣿⣦⡀

    /**
     * Inits system caching instance for further usage
     * 
     * @return void
     */
    protected function initCache() {
        $this->cache = new UbillingCache();
    }

    /**
     * Loads current processes data from cache
     * 
     * @return void
     */
    protected function loadCache() {
        $this->allProcessStates = $this->getCachedData();
    }

    /**
     * Sets instance process name/identifier
     * 
     * @param string $processName
     * 
     * @return void
     */
    public function setProcess($processName = '') {
        $this->processName = $processName;
        if (!empty($this->processName)) {
            $this->lockFile = self::LOCK_DIR . self::LOCK_PREFIX . preg_replace('/[^a-z0-9_\-]/i', '_', $this->processName) . '.lock';
        }
    }

    /**
     * Sets instance process separate keys usage flag
     * 
     * @param string $processName
     * 
     * @return void
     */
    public function setZaWarudo($state = false) {
        $this->separateKeys = $state;
    }

    /**
     * Sets flock usage flag
     *
     * @param bool $state
     * 
     * @return void
     */
    public function setFlock($state = false) {
        global $ubillingConfig;
        //normal flock set
        $this->flockFlag = $state;
        //force by config option
        $forceFlag = $ubillingConfig->getAlterParam('STARDUST_FLOCK_FORCE');
        if ($forceFlag) {
            $this->flockFlag = true;
        }
    }

    /**
     * Returns process data from cache
     * 
     * @return array
     */
    protected function getCachedData() {
        $result = array();
        if ($this->separateKeys) {
            $allCacheKeys = $this->cache->getAllcache();
            if (!empty($allCacheKeys)) {
                $processKeyMask = UbillingCache::CACHE_PREFIX . self::CACHE_KEY . '_';
                foreach ($allCacheKeys as $io => $eachKey) {
                    if (strpos($eachKey, $processKeyMask) !== false) {
                        $processNameClean = str_replace($processKeyMask, '', $eachKey);
                        $processCacheKey = self::CACHE_KEY . '_' . $processNameClean;
                        $result[$processNameClean] = $this->cache->get($processCacheKey, self::CACHE_TIMEOUT);
                    }
                }
            }
        } else {
            $cachedData = $this->cache->get(self::CACHE_KEY, self::CACHE_TIMEOUT);
            if (!empty($cachedData)) {
                $result = $cachedData;
            }
        }
        return ($result);
    }

    /**
     * Saves current process data into cache
     * 
     * @return void
     */
    protected function saveCache() {
        if ($this->separateKeys) {
            $this->cache->set(self::CACHE_KEY . '_' . $this->processName, $this->allProcessStates[$this->processName], self::CACHE_TIMEOUT);
        } else {
            $this->cache->set(self::CACHE_KEY, $this->allProcessStates, self::CACHE_TIMEOUT);
        }
    }

    /**
     * Checks is process name not empty and valid for setting/getting locks?
     * 
     * @return bool
     * @throws Exception
     */
    protected function pidIsOk() {
        $result = false;
        if (!empty($this->processName)) {
            $result = true;
        } else {
            throw new Exception('EX_EMPTY_PID');
        }
        return ($result);
    }

    /**
     * Starts some process
     * 
     * @return void
     */
    public function start() {
        if ($this->pidIsOk()) {
            if ($this->flockFlag) {
                $this->handle = fopen($this->lockFile, 'c+');
                if (!$this->handle) {
                    throw new RuntimeException('Unable to open lock file ' . $this->lockFile);
                }

                if (!flock($this->handle, LOCK_EX | LOCK_NB)) {
                    throw new RuntimeException('Process ' . $this->processName . ' is already running.');
                }

                ftruncate($this->handle, 0);
                fwrite($this->handle, getmypid() . PHP_EOL);
                $this->processStateUpdate(false);
            } else {
                nr_query("SELECT GET_LOCK('" . self::LOCK_PREFIX . $this->processName . "',1)");
                $this->processStateUpdate(false);
            }
        }
    }

    /**
     * Stops some process
     * 
     * @return void
     */
    public function stop() {
        if ($this->pidIsOk()) {
            if ($this->flockFlag) {
                if ($this->handle) {
                    ftruncate($this->handle, 0);
                    fflush($this->handle);
                    flock($this->handle, LOCK_UN);
                    fclose($this->handle);
                    $this->processStateUpdate(true);
                }
            } else {
                nr_query("SELECT RELEASE_LOCK('" . self::LOCK_PREFIX . $this->processName . "')");
                $this->processStateUpdate(true);
            }
        }
    }

    /**
     * Performs check is database lock available or not?
     *
     * @return bool
     */
    protected function isLocked() {
        if ($this->pidIsOk()) {
            if ($this->flockFlag) {
                $fp = fopen($this->lockFile, 'c+');
                if (!$fp) {
                    return (false);
                }

                if (flock($fp, LOCK_EX | LOCK_NB)) {
                    flock($fp, LOCK_UN);
                    fclose($fp);
                    return (false);
                }

                fclose($fp);
                return (true);
            } else {
                $query = "SELECT IS_FREE_LOCK('" . self::LOCK_PREFIX . $this->processName . "') AS " . self::LOCK_NAME;
                $rawReply = simple_query($query);
                $result = ($rawReply[self::LOCK_NAME]) ? false : true;
                return ($result);
            }
        }
    }

    /**
     * Performs check for current process is running
     * 
     * @return bool 
     */
    public function isRunning() {
        $locked = $this->isLocked();
        $result = ($locked) ? true : false;
        return ($result);
    }

    /**
     * Performs check for current process is not running
     * 
     * @return bool 
     */
    public function notRunning() {
        $locked = $this->isLocked();
        $result = ($locked) ? false : true;
        return ($result);
    }

    /**
     * Returns current process execution stats
     * 
     * @return array
     */
    public function getState() {
        $result = array();
        if (!empty($this->processName)) {
            $processData = $this->getCachedData();
            if (isset($processData[$this->processName])) {
                $result = $processData[$this->processName];
                //process now running?
                if (!$result['finished']) {
                    $result['realtime'] = round((microtime(true) - $result['ms']), self::REALTIME_PRECISSION);
                }
            }
        }
        return ($result);
    }

    /**
     * Returns all process execution stats
     * 
     * @return array
     */
    public function getAllStates() {
        $result = array();
        $processData = $this->getCachedData();
        if (!empty($processData)) {
            $microTime = microtime(true);
            foreach ($processData as $processName => $eachProcessData) {
                //updating realtime if process is still running
                if (!$eachProcessData['finished']) {
                    $eachProcessData['realtime'] = round($microTime - $eachProcessData['ms'], self::REALTIME_PRECISSION);
                }
                //append to results
                $result[$processName] = $eachProcessData;
            }
        }
        return ($result);
    }

    /**
     * Updates some process execution stats
     * 
     * @param bool $finished process finished or not flag
     * 
     * @return void
     */
    protected function processStateUpdate($finished = false) {
        $startTime = 0;
        $endTime = 0;
        $realTime = 0;
        $ms = 0;
        $me = 0;

        $nowTime = time();
        $microTime = microtime(true);
        $finishedData = ($finished) ? 1 : 0;

        //process just started?
        if (!$finished) {
            $startTime = $nowTime;
            $ms = $microTime;
        }

        //process finished?
        if ($finished) {
            if (isset($this->allProcessStates[$this->processName])) {
                $startTime = $this->allProcessStates[$this->processName]['start'];
                $ms = $this->allProcessStates[$this->processName]['ms'];
            } else {
                $startTime = $nowTime;
            }
            $me = $microTime;
            $endTime = $nowTime;
        }

        //process duration counter
        if (isset($this->allProcessStates[$this->processName])) {
            if ($finished) {
                $realTime = $me - $ms;
            } else {
                $realTime = 0;
            }
        }

        //getting latest data from cache
        $this->loadCache();
        //updating current process properties
        $this->allProcessStates[$this->processName]['start'] = $startTime;
        $this->allProcessStates[$this->processName]['end'] = $endTime;
        $this->allProcessStates[$this->processName]['realtime'] = round($realTime, self::REALTIME_PRECISSION);
        $this->allProcessStates[$this->processName]['ms'] = $ms;
        $this->allProcessStates[$this->processName]['me'] = $me;
        $this->allProcessStates[$this->processName]['finished'] = $finishedData;
        $this->allProcessStates[$this->processName]['pid'] = getmypid();
        //save current process data into cache
        $this->saveCache();
    }

    /**
     * Runs execution of some command as background process
     * 
     * @param string $command full executable path to run
     * @param int $timeout optional timeout after executing command
     * 
     * @return void
     */
    public function runBackgroundProcess($command, $timeout = 0) {
        if (!empty($command)) {
            $pipes = array();
            proc_close(proc_open($command . ' > /dev/null 2>/dev/null &', array(), $pipes));
            if ($timeout) {
                sleep($timeout);
            }
        }
    }

    /**
     * Returns system PID or false if process not running
     *
     * @return void|int
     */
    public function getSystemPid() {
        $result = '';
        if ($this->flockFlag) {
            if (!file_exists($this->lockFile)) {
                $result = false;
            } else {
                $result = (int)trim(file_get_contents($this->lockFile));
            }
        } else {
            throw new RuntimeException('System PIDs not available with DB locks');
        }
        return ($result);
    }

    //
    //                 ⠀  (\__/)
    //                    (•ㅅ•)      SONO CHI NO SADAME
    //                 ＿ノヽ  ノ＼＿   
    //             `/　`/ ⌒Ｙ⌒ Ｙ  ヽ
    //             ( 　(三ヽ人　 /　  |
    //             |　ﾉ⌒＼ ￣￣ヽ   ノ
    //             ヽ＿＿＿＞､＿_／
    //                   ｜( 王 ﾉ〈  (\__/)
    //                    /ﾐ`ー―彡\  (•ㅅ•)
    //                   / ╰    ╯ \ /    \>
}
