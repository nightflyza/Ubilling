<?php

/**
 * Represents a class for retrieving system information.
 */
class SystemHwInfo {
    /**
     * This variable represents the operating system name of the system.
     *
     * @var string
     */
    protected $os = '';
    /**
     * Represents the full system OS information.
     *
     * @var string $osFullRelease The full release of the operating system.
     */
    protected $osFullRelease = '';
    /**
     * This variable represents the hostname of the system.
     *
     * @var string
     */
    protected $hostname = '';
    /**
     * This variable represents the PHP version used in the system. like 7.4.29.
     *
     * @var string
     */
    protected $phpVersion = '';

    /**
     * Property that holds the name of the CPU.
     *
     * @var string
     */
    protected $cpuName = '';
    /**
     * This variable represents the number of CPU cores.
     *
     * @var int
     */
    protected $cpuCores = 1;

    /**
     * This variable represents the total memory available in bytes.
     *
     * @var int
     */
    protected $memTotal = 0;
    /**
     * This variable represents the free memory in bytes.
     *
     * @var int
     */
    protected $memFree = 0;
    /**
     * This variable represents the used memory in bytes.
     *
     * @var int
     */
    protected $memUsed = 0;
    /**
     * This variable represents the number of seconds the system has been running.
     *
     * @var int
     */
    protected $uptimeSeconds = 0;
    /**
     * This variable represents the load average of the system.
     *
     * @var array
     */
    protected $loadAverage = array();
    /**
     * This variable represents the load average of the system in 1 minute
     *
     * @var float
     */
    protected $la1 = 0;
    /**
     * This variable represents the load average of the system in 5 minute
     *
     * @var float
     */
    protected $la5 = 0;
    /**
     * This variable represents the load average of the system in 15 minutes
     *
     * @var float
     */
    protected $la15 = 0;

    /**
     * This variable represents the latest system resources load percent depends on cores count.
     *
     * @var float
     */
    protected $systemLoadPercent = 0;
    /**
     * This variable represents the system resources load percent depends on cores count in 1 minute
     *
     * @var float
     */
    protected $loadPercent1 = 0;
    /**
     * This variable represents the system resources load percent depends on cores count in 5 minutes
     *
     * @var float
     */
    protected $loadPercent5 = 0;
    /**
     * This variable represents the system resources load percent depends on cores count in 15 minutes
     *
     * @var float
     */
    protected $loadPercent15 = 0;

    /**
     * This variable represents the average system resources load percent depends on cores count
     *
     * @var float
     */
    protected $loadAvgPercent = 0;

    /**
     * Contains mountpoints to load disk stats
     *
     * @var array
     */
    protected $mountPoints = array();

    /**
     * Contains loaded all mountpoints stats
     *
     * @var array
     */
    protected $diskStats = array();

    /**
     * The paths for some of system executable binaries
     *
     * @var string 
     */
    protected $sysctlPath = '/sbin/sysctl';
    protected $vmstatPath = '/usr/bin/vmstat';
    protected $catPath = '/bin/cat';
    protected $grepPath = '/usr/bin/grep';
    protected $headPath = '/usr/bin/head';


    public function __construct() {
        $this->setOS();
        $this->setPhpVersion();
        $this->setPaths();
        $this->setLoadAverage();
        $this->setCpuCores();
        $this->setCpuName();
        $this->setSystemLoadPercent();
        $this->setUptime();
        $this->setMemory();
    }

    /**
     * Retrieves the output of a command execution.
     *
     * @param string $command The command to execute.
     * @param string $params Additional parameters for the command (optional).
     * @return string The output of the command execution.
     */
    protected function grabCmdOutput($command, $params = '') {
        $result = '';
        if (file_exists($command)) {
            if (!empty($params)) {
                $params = ' ' . $params;
            }
            $rawOutput = shell_exec($command . $params);
            $result .= trim($rawOutput);
        }
        return ($result);
    }

    /**
     * Sets the operating system information.
     *
     * @return void
     */
    protected function setOS() {
        $this->os = trim(php_uname('s'));
        $this->osFullRelease = trim(php_uname('a'));
        $this->hostname = trim(php_uname('n'));
    }

    /**
     * Sets the paths for some binaries
     *
     * @return void
     */
    protected function setPaths() {
        switch ($this->os) {
            case 'FreeBSD':
                $this->sysctlPath = '/sbin/sysctl';
                $this->catPath = '/bin/cat';
                break;
            case 'Linux':
                $this->sysctlPath = '/usr/sbin/sysctl';
                $this->catPath = '/usr/bin/cat';
                break;
        }
    }


    /**
     * Sets the load average.
     *
     * This method is responsible for setting the load average.
     * 
     * @return void
     */
    protected function setLoadAverage() {
        $this->loadAverage = sys_getloadavg();
        $this->la1 = round($this->loadAverage[0], 2);
        $this->la5 = round($this->loadAverage[1], 2);
        $this->la15 = round($this->loadAverage[2], 2);
    }

    /**
     * Sets the CPU name.
     * 
     * @return void
     */
    protected function setCpuName() {
        $cpuName = '';

        switch ($this->os) {
            case 'FreeBSD':
                $cpuName = $this->grabCmdOutput($this->sysctlPath, '-n hw.model');
                break;
            case 'Linux':
                $raw = $this->grabCmdOutput($this->catPath, ' /proc/cpuinfo | ' . $this->grepPath . ' "model name" | ' . $this->headPath . ' -n 1');
                $raw = str_replace('model name	:', '', $raw);
                $cpuName = $raw;
                break;
        }

        $this->cpuName = trim($cpuName);
    }

    /**
     * Sets the number of CPU cores.
     * 
     * @return void
     */
    protected function setCpuCores() {
        $coresCount = 0;

        switch ($this->os) {
            case 'FreeBSD':
                $coresCount = $this->grabCmdOutput($this->sysctlPath, '-n hw.ncpu');
                break;
            case 'Linux':
                $raw = $this->grabCmdOutput($this->catPath, ' /proc/cpuinfo | ' . $this->grepPath . ' "siblings" | ' . $this->headPath . ' -n 1');
                $coresCount = preg_replace("#[^0-9]#Uis", '', $raw);
                break;
        }

        if ($coresCount > 0) {
            $this->cpuCores = $coresCount;
        }
    }

    /**
     * Sets the uptime proterty for the system.
     *
     * @return void
     */
    protected function setUptime() {
        $uptime = 0;
        $currentTimestamp = time();
        switch ($this->os) {
            case 'FreeBSD':
                $uptimeRaw = $this->grabCmdOutput($this->sysctlPath, '-n kern.boottime');
                if (preg_match("/sec = ([0-9]+)/", $uptimeRaw, $parts)) {
                    $uptime = $currentTimestamp - $parts[1];
                }
                break;
            case 'Linux':
                $uptimeRaw = $this->grabCmdOutput($this->catPath, '/proc/uptime');
                if (!empty($uptimeRaw)) {
                    $uptimeRaw = explode(' ', $uptimeRaw);
                    $uptime = round($uptimeRaw[0]);
                }
                break;
        }

        $this->uptimeSeconds = $uptime;
    }

    /**
     * Sets the system load percentages.
     * 
     * @return void
     */
    protected function setSystemLoadPercent() {
        if ($this->cpuCores != 0) {
            $this->loadPercent1 = round(($this->la1 / $this->cpuCores) * 100, 2);
            $this->loadPercent5 = round(($this->la5 / $this->cpuCores) * 100, 2);
            $this->loadPercent15 = round(($this->la15 / $this->cpuCores) * 100, 2);
            $this->systemLoadPercent = $this->loadPercent1;
            $this->loadAvgPercent = round(($this->loadPercent1 + $this->loadPercent5 + $this->loadPercent15) / 3, 2);
        }
    }

    /**
     * Sets the PHP version prop.
     * 
     * @return void
     */
    protected function setPhpVersion() {
        $this->phpVersion = phpversion();
    }


    /**
     * Sets the memory stats for the system.
     *
     * @return void
     */
    protected function setMemory() {
        $memTotal = 0;
        $memFree = 0;
        $memUsed = 0;
        switch ($this->os) {
            case 'FreeBSD':
                $pageSize = $this->grabCmdOutput($this->sysctlPath, '-n hw.pagesize');
                $memTotal = $this->grabCmdOutput($this->sysctlPath, '-n hw.physmem');
                $vmStatRaw = $this->grabCmdOutput($this->vmstatPath);
                $lines = preg_split("/\n/", $vmStatRaw, -1, PREG_SPLIT_NO_EMPTY);
                $mem_buf = preg_split("/\s+/", trim($lines[2]), 19);
                $memFree = $mem_buf[4] * $pageSize;
                $memUsed = $memTotal - $memFree;
                break;
            case 'Linux':
                $memInfoRaw = $this->grabCmdOutput($this->catPath, '/proc/meminfo');
                $bufe = preg_split("/\n/", $memInfoRaw, -1, PREG_SPLIT_NO_EMPTY);
                foreach ($bufe as $buf) {
                    if (preg_match('/^MemTotal:\s+(\d+)\s*kB/i', $buf, $ar_buf)) {
                        $memTotal = $ar_buf[1] * 1024;
                    } elseif (preg_match('/^MemFree:\s+(\d+)\s*kB/i', $buf, $ar_buf)) {
                        $memFree = $ar_buf[1] * 1024;
                    }
                    $memUsed = $memTotal - $memFree;
                }
                break;
        }

        $this->memTotal = $memTotal;
        $this->memFree = $memFree;
        $this->memUsed = $memUsed;
    }


    /**
     * Sets the mount points for the system hardware information.
     *
     * @param array $mountPoints An array of mount points.
     * @return void
     */
    public function setMountPoints($mountPoints = array()) {
        if (!empty($mountPoints)) {
            foreach ($mountPoints as $idx => $eachMountPoint) {
                if (strpos($eachMountPoint, '/') !== false) {
                    $this->mountPoints[] = $eachMountPoint;
                }
            }
        }
    }

    /**
     * Counts percentage between two values
     * 
     * @param float $valueTotal
     * @param float $value
     * 
     * @return float
     */
    protected function calcPercentValue($valueTotal, $value) {
        $result = 0;
        if ($valueTotal != 0) {
            $result = round((($value * 100) / $valueTotal), 2);
        }
        return ($result);
    }

    /**
     * Retrieves disk statistics for a given mount point. Returns An array containing
     * disk statistics including mount point, total space, free space, used space, used percentage, and free percentage.
     *
     * @param string $mountPoint The mount point of the disk.
     * 
     * @return array 
     */
    public function getDiskStat($mountPoint) {
        $result = array();
        if (!empty($mountPoint)) {
            $totalSpace = disk_total_space($mountPoint);
            if (!empty($totalSpace)) {
                $freeSpace = disk_free_space($mountPoint);
                $usedSpace = $totalSpace - $freeSpace;
                $result['mountpoint'] = $mountPoint;
                $result['total'] = $totalSpace;
                $result['free'] = $freeSpace;
                $result['used'] = $usedSpace;
                $result['usedpercent'] = $this->calcPercentValue($totalSpace, $usedSpace);
                $result['freepercent'] = $this->calcPercentValue($totalSpace, $freeSpace);
            }
        }
        return ($result);
    }

    /**
     * Sets the disk statistics property dewpends on preset mountpoints
     * 
     * @return void
     */
    protected function setDiskStats() {
        if (!empty($this->mountPoints)) {
            foreach ($this->mountPoints as $idx => $eachMountPoint) {
                $eachDiskStat = $this->getDiskStat($eachMountPoint);
                if (!empty($eachDiskStat)) {
                    $this->diskStats[$eachMountPoint] = $eachDiskStat;
                }
            }
        }
    }

    /**
     * Retrieves all disk statistics.
     *
     * @return array
     */
    public function getAllDiskStats() {
        $this->setDiskStats();
        return ($this->diskStats);
    }


    /**
     * Gets the operating system name.
     *
     * @return string
     */
    public function getOs() {
        return ($this->os);
    }

    /**
     * Gets the full release of the operating system.
     *
     * @return string
     */
    public function getOsFullRelease() {
        return ($this->osFullRelease);
    }

    /**
     * Gets the hostname of the system.
     *
     * @return string
     */
    public function getHostname() {
        return ($this->hostname);
    }

    /**
     * Gets the PHP version used in the system.
     *
     * @return string
     */
    public function getPhpVersion() {
        return ($this->phpVersion);
    }

    /**
     * Gets the name of the CPU.
     *
     * @return string
     */
    public function getCpuName() {
        return ($this->cpuName);
    }

    /**
     * Gets the number of CPU cores.
     *
     * @return int
     */
    public function getCpuCores() {
        return ($this->cpuCores);
    }

    /**
     * Gets the total memory available in bytes.
     *
     * @return int
     */
    public function getMemTotal() {
        return ($this->memTotal);
    }

    /**
     * Gets the free memory in bytes.
     *
     * @return int
     */
    public function getMemFree() {
        return ($this->memFree);
    }

    /**
     * Gets the used memory in bytes.
     *
     * @return int
     */
    public function getMemUsed() {
        return ($this->memUsed);
    }

    /**
     * Gets the number of seconds the system has been running.
     *
     * @return int
     */
    public function getUptime() {
        return ($this->uptimeSeconds);
    }

    /**
     * Gets the system's load average over 1, 5, and 15 minutes.
     *
     * @return array
     */
    public function getLoadAverage() {
        return ($this->loadAverage);
    }

    /**
     * Gets the system's load average over 1 minute.
     *
     * @return float
     */
    public function getLa1() {
        return ($this->la1);
    }

    /**
     * Gets the system's load average over 5 minutes.
     *
     * @return float
     */
    public function getLa5() {
        return ($this->la5);
    }

    /**
     * Gets the system's load average over 15 minutes.
     *
     * @return float
     */
    public function getLa15() {
        return ($this->la15);
    }

    /**
     * Gets the system's load percentage based on the number of CPU cores.
     *
     * @return float
     */
    public function getSystemLoadPercent() {
        return ($this->systemLoadPercent);
    }

    /**
     * Gets the system's load percentage over 1 minute.
     *
     * @return float
     */
    public function getLoadPercent1() {
        return ($this->loadPercent1);
    }

    /**
     * Gets the system's load percentage over 5 minutes.
     *
     * @return float
     */
    public function getLoadPercent5() {
        return ($this->loadPercent5);
    }

    /**
     * Gets the system's load percentage over 15 minutes.
     *
     * @return float
     */
    public function getLoadPercent15() {
        return ($this->loadPercent15);
    }

    /**
     * Gets the average system load percentage.
     *
     * @return float
     */
    public function getLoadAvgPercent() {
        return ($this->loadAvgPercent);
    }

    /**
     * Gets the mount points used for disk statistics.
     *
     * @return array
     */
    public function getMountPoints() {
        return ($this->mountPoints);
    }
}
