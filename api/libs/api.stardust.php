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
     * Some predefined stuff
     */
    const LOCK_NAME = 'stardustLockfree';
    const LOCK_PREFIX = 'stardustPID_';
    const CACHE_TIMEOUT = 2592000;
    const CACHE_KEY = 'STARDUST_PROCESSES';

    public function __construct($processName = '') {
        $this->setProcess($processName);
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
        $this->allProcessStates = $this->cache->get(self::CACHE_KEY, self::CACHE_TIMEOUT);
        if (empty($this->allProcessStates)) {
            $this->allProcessStates = array();
        }
    }

    /**
     * Saves current process data into cache
     * 
     * @return void
     */
    protected function saveCache() {
        $this->cache->set(self::CACHE_KEY, $this->allProcessStates, self::CACHE_TIMEOUT);
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
    }

    /**
     * Checks is process name not ampty and valid for setting/getting locks?
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
        return($result);
    }

    /**
     * Starts some process and 
     * 
     * @return void
     */
    public function start() {
        $this->processStateUpdate(false);
        nr_query("SELECT GET_LOCK('" . self::LOCK_PREFIX . $this->processName . "',1)");
    }

    /**
     * 
     * @return void
     */
    public function stop() {
        $this->processStateUpdate(true);
        nr_query("SELECT RELEASE_LOCK('" . self::LOCK_PREFIX . $this->processName . "')");
    }

    /**
     * Performs check for current process is running
     * 
     * @return bool 
     */
    public function isRunning() {
        if ($this->pidIsOk()) {
            $query = "SELECT IS_FREE_LOCK('" . self::LOCK_PREFIX . $this->processName . "') AS " . self::LOCK_NAME;
            $rawReply = simple_query($query);
            $result = ($rawReply[self::LOCK_NAME]) ? false : true;
        }
        return($result);
    }

    /**
     * Performs check for current process is not running
     * 
     * @return bool 
     */
    public function notRunning() {
        if ($this->pidIsOk()) {
            $query = "SELECT IS_FREE_LOCK('" . self::LOCK_PREFIX . $this->processName . "') AS " . self::LOCK_NAME;
            $rawReply = simple_query($query);
            $result = ($rawReply[self::LOCK_NAME]) ? true : false;
        }
        return($result);
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

        $nowTime = time();
        $finishedData = ($finished) ? 1 : 0;

        //process just started?
        if (!$finished) {
            $startTime = $nowTime;
        }

        //process finished?
        if (($startTime == 0) AND $finished) {
            if (isset($this->allProcessStates[$this->processName])) {
                $startTime = $this->allProcessStates[$this->processName]['start'];
            } else {
                $startTime = $nowTime;
            }
            $endTime = $nowTime;
        }
        $this->allProcessStates[$this->processName]['start'] = $startTime;
        $this->allProcessStates[$this->processName]['end'] = $endTime;
        $this->allProcessStates[$this->processName]['realtime'] = 0; //TODO
        $this->allProcessStates[$this->processName]['finished'] = $finishedData;
        //save current process data into cache
        $this->saveCache();
    }

}
