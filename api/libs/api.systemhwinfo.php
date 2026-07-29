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
     * This variable represents the operating system release of the system.
     *
     * @var string
     */
    protected $osRelease = '';

    /**
     * Represents the full system OS information.
     *
     * @var string $osFullRelease The full release of the operating system.
     */
    protected $osFullRelease = '';

    /**
     * Contains type of the current hardware platform
     *
     * @var string
     */
    protected $machineArch = '';

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
     * Contains disk IO rates as deviceName=>rateData after sampling
     *
     * @var array
     */
    protected $diskIoRatesByDevice = array();

    /**
     * Contains disk IO rates as mountPoint=>rateData
     *
     * @var array
     */
    protected $diskIoStats = array();

    /**
     * Flag: disk IO rates already sampled for this instance
     *
     * @var bool
     */
    protected $diskIoSampleDone = false;

    /**
     * Seconds used for the last disk IO sample interval
     *
     * @var int
     */
    protected $diskIoSampleSeconds = 1;

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
    protected $dfPath = '/bin/df';
    protected $iostatPath = '/usr/sbin/iostat';
    protected $zpoolPath = '/sbin/zpool';
    protected $glabelPath = '/sbin/glabel';


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

            if (is_string($rawOutput)) {
                $result .= trim($rawOutput);
            }
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
        $this->osRelease = trim(php_uname('r'));
        $this->osFullRelease = trim(php_uname('a'));
        $this->machineArch=trim(php_uname('m'));
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
                $this->dfPath = '/bin/df';
                $this->iostatPath = '/usr/sbin/iostat';
                $this->zpoolPath = '/sbin/zpool';
                $this->glabelPath = '/sbin/glabel';
                break;
            case 'Linux':
                $this->sysctlPath = '/usr/sbin/sysctl';
                $this->catPath = '/usr/bin/cat';
                $this->dfPath = '/usr/bin/df';
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
                //normal x86 format
                if (!empty($raw)) {
                    $raw = str_replace('model name  :', '', $raw);
                } else {
                    //non x86 arch workaround
                    $raw = $this->grabCmdOutput($this->catPath, ' /proc/cpuinfo | ' . $this->grepPath . ' "Model" | ' . $this->headPath . ' -n 1');
                    $raw = str_replace('Model', '', $raw);
                    $raw = str_replace(':', '', $raw);
                }
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
                if (!empty($raw)) {
                    $coresCount = preg_replace("#[^0-9]#Uis", '', $raw);
                } else {
                    //non x86
                    $raw = $this->grabCmdOutput($this->catPath, ' /proc/cpuinfo | ' . $this->grepPath . ' "processor" | wc -l');
                    $coresCount = preg_replace("#[^0-9]#Uis", '', $raw);
                }
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
     * Normalizes filesystem device string from df into a kernel diskstats/iostat/zpool name
     *
     * @param string $deviceRaw
     *
     * @return string
     */
    protected function normalizeDiskDeviceName($deviceRaw) {
        $result = '';
        $deviceRaw = trim($deviceRaw);
        if (!empty($deviceRaw)) {
            if (strpos($deviceRaw, '/dev/') === 0) {
                $isLabelPath = preg_match('#^/dev/(?:gpt|ufs|label|diskid)/#', $deviceRaw);
                // FreeBSD GPT/UFS/glabel providers often do not realpath() to ada/nvd names
                $labelResolved = $this->resolveFreeBsdLabelDevice($deviceRaw);
                if (!empty($labelResolved)) {
                    $result = $labelResolved;
                } else {
                    if (!$isLabelPath) {
                        $resolved = $deviceRaw;
                        if (file_exists($deviceRaw)) {
                            $realPath = realpath($deviceRaw);
                            if ($realPath) {
                                $resolved = $realPath;
                            }
                        }
                        $result = basename($resolved);
                    }
                }
            } else {
                // ZFS dataset/pool names from df: zroot, zroot/ROOT/default, tank/wrstorage
                if (!$this->isPseudoFilesystem($deviceRaw)) {
                    $result = $deviceRaw;
                }
            }
        }
        return ($result);
    }

    /**
     * Checks for pseudo/virtual filesystems that have no useful block IO stats
     *
     * @param string $fsName
     *
     * @return bool
     */
    protected function isPseudoFilesystem($fsName) {
        $result = false;
        $pseudo = array(
            'devfs' => true,
            'procfs' => true,
            'fdescfs' => true,
            'tmpfs' => true,
            'linprocfs' => true,
            'linsysfs' => true,
            'none' => true,
        );
        if (isset($pseudo[$fsName])) {
            $result = true;
        }
        return ($result);
    }

    /**
     * Resolves FreeBSD /dev/gpt|/dev/ufs|/dev/label name into geom component (e.g. ada0p3)
     *
     * @param string $deviceRaw
     *
     * @return string
     */
    protected function resolveFreeBsdLabelDevice($deviceRaw) {
        $result = '';
        if ($this->os == 'FreeBSD' and preg_match('#^/dev/((?:gpt|ufs|label|diskid)/.+)$#', $deviceRaw, $m)) {
            $labelKey = $m[1];
            $statusRaw = '';
            if (file_exists($this->glabelPath)) {
                $statusRaw = $this->grabCmdOutput($this->glabelPath, 'status');
            }
            if (empty($statusRaw) and file_exists('/sbin/geom')) {
                $statusRaw = $this->grabCmdOutput('/sbin/geom', 'label status');
            }
            if (!empty($statusRaw)) {
                $lines = preg_split("/\n/", $statusRaw, -1, PREG_SPLIT_NO_EMPTY);
                if (!empty($lines)) {
                    foreach ($lines as $io => $line) {
                        $parts = preg_split("/\s+/", trim($line));
                        if (count($parts) >= 3 and $parts[0] == $labelKey) {
                            $component = $parts[count($parts) - 1];
                            $result = basename($component);
                        }
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Extracts ZFS pool name from dataset string (zroot/ROOT/default -> zroot)
     *
     * @param string $dataset
     *
     * @return string
     */
    protected function extractZfsPoolName($dataset) {
        $result = '';
        if (!empty($dataset) and strpos($dataset, '/dev/') !== 0 and !$this->isPseudoFilesystem($dataset)) {
            // block-like names must not be treated as ZFS pools
            if (!preg_match('/^\/dev\//', $dataset)) {
                if (strpos($dataset, '/') !== false) {
                    $parts = explode('/', $dataset);
                    if (!empty($parts[0])) {
                        $result = $parts[0];
                    }
                } else {
                    // bare pool name mounted somewhere
                    if (!preg_match('/^(ada|da|nvd|nda|md|cd|pass|vtbd|mfid|aacd)[0-9]/', $dataset)) {
                        $result = $dataset;
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Returns parent whole-disk name for a partition device if detectable
     *
     * @param string $deviceName
     *
     * @return string
     */
    protected function getParentDiskDevice($deviceName) {
        $result = '';
        if (!empty($deviceName)) {
            if (preg_match('/^(nvme[0-9]+n[0-9]+)p[0-9]+$/', $deviceName, $m)) {
                $result = $m[1];
            } else {
                if (preg_match('/^(mmcblk[0-9]+)p[0-9]+$/', $deviceName, $m)) {
                    $result = $m[1];
                } else {
                    if (preg_match('/^([a-z]+[0-9]+)p[0-9]+$/', $deviceName, $m)) {
                        // FreeBSD GEOM GPT: ada0p1, nvd0p1, da0p2
                        $result = $m[1];
                    } else {
                        if (preg_match('/^([a-z]+[0-9]+)s[0-9]+[a-z]?$/', $deviceName, $m)) {
                            // FreeBSD MBR slices: ada0s1a
                            $result = $m[1];
                        } else {
                            if (preg_match('/^([a-z]+)[0-9]+$/', $deviceName, $m)) {
                                // Linux partitions: sda1, vdb3
                                $result = $m[1];
                            }
                        }
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Resolves block device name for a path/mountpoint via df -P
     *
     * @param string $mountPoint
     *
     * @return string
     */
    public function getDiskDevice($mountPoint) {
        $result = '';
        if (!empty($mountPoint) and file_exists($this->dfPath)) {
            $raw = $this->grabCmdOutput($this->dfPath, '-P ' . escapeshellarg($mountPoint));
            if (!empty($raw)) {
                $lines = preg_split("/\n/", $raw, -1, PREG_SPLIT_NO_EMPTY);
                if (count($lines) >= 2) {
                    $parts = preg_split("/\s+/", trim($lines[1]));
                    if (!empty($parts[0])) {
                        $result = $this->normalizeDiskDeviceName($parts[0]);
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Reads Linux /proc/diskstats cumulative IO counters as device=>counterData
     *
     * @return array
     */
    protected function readLinuxDiskIoCounters() {
        $result = array();
        $diskStatsPath = '/proc/diskstats';
        if (file_exists($diskStatsPath)) {
            $raw = file_get_contents($diskStatsPath);
            if (is_string($raw) and !empty($raw)) {
                $lines = preg_split("/\n/", $raw, -1, PREG_SPLIT_NO_EMPTY);
                if (!empty($lines)) {
                    foreach ($lines as $io => $line) {
                        $parts = preg_split("/\s+/", trim($line));
                        // name + at least writes/sectors fields
                        if (count($parts) >= 10) {
                            $deviceName = $parts[2];
                            $result[$deviceName] = array(
                                'device' => $deviceName,
                                'reads' => floatval($parts[3]),
                                'writes' => floatval($parts[7]),
                                'sectors_read' => floatval($parts[5]),
                                'sectors_written' => floatval($parts[9]),
                            );
                        }
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Calculates IO rates from two cumulative counter snapshots
     *
     * @param array $before
     * @param array $after
     * @param float $elapsedSeconds
     *
     * @return array
     */
    protected function calcDiskIoRatesFromCounters($before, $after, $elapsedSeconds) {
        $result = array();
        if (!empty($after) and $elapsedSeconds > 0) {
            foreach ($after as $deviceName => $afterCounters) {
                if (isset($before[$deviceName])) {
                    $beforeCounters = $before[$deviceName];
                    $deltaReads = $afterCounters['reads'] - $beforeCounters['reads'];
                    $deltaWrites = $afterCounters['writes'] - $beforeCounters['writes'];
                    $deltaSectorsRead = $afterCounters['sectors_read'] - $beforeCounters['sectors_read'];
                    $deltaSectorsWritten = $afterCounters['sectors_written'] - $beforeCounters['sectors_written'];

                    if ($deltaReads < 0) {
                        $deltaReads = 0;
                    }
                    if ($deltaWrites < 0) {
                        $deltaWrites = 0;
                    }
                    if ($deltaSectorsRead < 0) {
                        $deltaSectorsRead = 0;
                    }
                    if ($deltaSectorsWritten < 0) {
                        $deltaSectorsWritten = 0;
                    }

                    $readIops = $deltaReads / $elapsedSeconds;
                    $writeIops = $deltaWrites / $elapsedSeconds;
                    // diskstats sectors are always 512-byte units
                    $readBps = ($deltaSectorsRead * 512) / $elapsedSeconds;
                    $writeBps = ($deltaSectorsWritten * 512) / $elapsedSeconds;

                    $result[$deviceName] = array(
                        'device' => $deviceName,
                        'read_bps' => round($readBps, 2),
                        'write_bps' => round($writeBps, 2),
                        'read_iops' => round($readIops, 2),
                        'write_iops' => round($writeIops, 2),
                        'iops' => round(($readIops + $writeIops), 2),
                        'sample_seconds' => round($elapsedSeconds, 2),
                    );
                }
            }
        }
        return ($result);
    }

    /**
     * Samples Linux disk IO rates via /proc/diskstats dual snapshot
     *
     * @param int $sampleSeconds
     *
     * @return array
     */
    protected function sampleLinuxDiskIoRates($sampleSeconds) {
        $result = array();
        $before = $this->readLinuxDiskIoCounters();
        if (!empty($before)) {
            $startedAt = microtime(true);
            sleep($sampleSeconds);
            $after = $this->readLinuxDiskIoCounters();
            $elapsed = microtime(true) - $startedAt;
            if ($elapsed <= 0) {
                $elapsed = $sampleSeconds;
            }
            $result = $this->calcDiskIoRatesFromCounters($before, $after, $elapsed);
        }
        return ($result);
    }

    /**
     * Samples FreeBSD disk IO rates via iostat -x (second report = interval rates)
     *
     * @param int $sampleSeconds
     *
     * @return array
     */
    protected function sampleFreeBsdDiskIoRates($sampleSeconds) {
        $result = array();
        if (file_exists($this->iostatPath)) {
            $raw = $this->grabCmdOutput($this->iostatPath, '-x -w ' . intval($sampleSeconds) . ' -c 2');
            if (!empty($raw)) {
                $lines = preg_split("/\n/", $raw, -1, PREG_SPLIT_NO_EMPTY);
                $blocks = array();
                $currentBlock = array();
                if (!empty($lines)) {
                    foreach ($lines as $io => $line) {
                        $lineTrim = trim($line);
                        if ($lineTrim === '') {
                            // skip
                        } else {
                            if (stripos($lineTrim, 'extended device statistics') !== false) {
                                if (!empty($currentBlock)) {
                                    $blocks[] = $currentBlock;
                                    $currentBlock = array();
                                }
                            } else {
                                if (stripos($lineTrim, 'device') === 0) {
                                    // header inside a block
                                } else {
                                    $currentBlock[] = $lineTrim;
                                }
                            }
                        }
                    }
                    if (!empty($currentBlock)) {
                        $blocks[] = $currentBlock;
                    }
                }

                $rateLines = array();
                if (!empty($blocks)) {
                    // last block is the sampled interval; if only one - use it
                    $rateLines = $blocks[count($blocks) - 1];
                }

                if (!empty($rateLines)) {
                    foreach ($rateLines as $idx => $lineTrim) {
                        $parts = preg_split("/\s+/", $lineTrim);
                        // device r/s w/s kr/s kw/s ...
                        if (count($parts) >= 5) {
                            $deviceName = $parts[0];
                            // skip CAM pass-through nodes
                            if (strpos($deviceName, 'pass') !== 0) {
                                $readIops = floatval($parts[1]);
                                $writeIops = floatval($parts[2]);
                                $readBps = floatval($parts[3]) * 1024;
                                $writeBps = floatval($parts[4]) * 1024;
                                $result[$deviceName] = array(
                                    'device' => $deviceName,
                                    'read_bps' => round($readBps, 2),
                                    'write_bps' => round($writeBps, 2),
                                    'read_iops' => round($readIops, 2),
                                    'write_iops' => round($writeIops, 2),
                                    'iops' => round(($readIops + $writeIops), 2),
                                    'sample_seconds' => $sampleSeconds,
                                );
                            }
                        }
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Samples FreeBSD ZFS pool IO rates via zpool iostat -Hp
     *
     * @param int $sampleSeconds
     *
     * @return array
     */
    protected function sampleFreeBsdZpoolIoRates($sampleSeconds) {
        $result = array();
        if (file_exists($this->zpoolPath)) {
            $raw = $this->grabCmdOutput($this->zpoolPath, 'iostat -Hp ' . intval($sampleSeconds) . ' 2');
            if (!empty($raw)) {
                $lines = preg_split("/\n/", $raw, -1, PREG_SPLIT_NO_EMPTY);
                if (!empty($lines)) {
                    foreach ($lines as $io => $line) {
                        $parts = preg_split("/\s+/", trim($line));
                        // name alloc free read_ops write_ops read_bytes write_bytes
                        if (count($parts) >= 7) {
                            $poolName = $parts[0];
                            if ($poolName != 'pool' and strpos($poolName, '-') !== 0) {
                                $readIops = floatval($parts[3]);
                                $writeIops = floatval($parts[4]);
                                $readBps = floatval($parts[5]);
                                $writeBps = floatval($parts[6]);
                                // last occurrence per pool is the interval sample
                                $result[$poolName] = array(
                                    'device' => $poolName,
                                    'read_bps' => round($readBps, 2),
                                    'write_bps' => round($writeBps, 2),
                                    'read_iops' => round($readIops, 2),
                                    'write_iops' => round($writeIops, 2),
                                    'iops' => round(($readIops + $writeIops), 2),
                                    'sample_seconds' => $sampleSeconds,
                                );
                            }
                        }
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Ensures disk IO rates were sampled once for this instance (all devices)
     *
     * @param int $sampleSeconds
     *
     * @return void
     */
    protected function ensureDiskIoRates($sampleSeconds = 1) {
        if (!$this->diskIoSampleDone) {
            $sampleSeconds = intval($sampleSeconds);
            if ($sampleSeconds < 1) {
                $sampleSeconds = 1;
            }
            $this->diskIoSampleSeconds = $sampleSeconds;
            $rates = array();
            switch ($this->os) {
                case 'Linux':
                    $rates = $this->sampleLinuxDiskIoRates($sampleSeconds);
                    break;
                case 'FreeBSD':
                    $rates = $this->sampleFreeBsdDiskIoRates($sampleSeconds);
                    $zpoolRates = $this->sampleFreeBsdZpoolIoRates($sampleSeconds);
                    if (!empty($zpoolRates)) {
                        foreach ($zpoolRates as $poolName => $poolRates) {
                            $rates[$poolName] = $poolRates;
                        }
                    }
                    break;
            }
            $this->diskIoRatesByDevice = $rates;
            $this->diskIoSampleDone = true;
        }
    }

    /**
     * Looks up sampled IO rates for a device name with partition→disk and ZFS pool fallback
     *
     * @param string $deviceName
     *
     * @return array
     */
    protected function lookupDiskIoRatesByDevice($deviceName) {
        $result = array();
        if (!empty($deviceName) and !empty($this->diskIoRatesByDevice)) {
            if (isset($this->diskIoRatesByDevice[$deviceName])) {
                $result = $this->diskIoRatesByDevice[$deviceName];
            } else {
                $parentDevice = $this->getParentDiskDevice($deviceName);
                if (!empty($parentDevice) and isset($this->diskIoRatesByDevice[$parentDevice])) {
                    $result = $this->diskIoRatesByDevice[$parentDevice];
                } else {
                    $zfsPool = $this->extractZfsPoolName($deviceName);
                    if (!empty($zfsPool) and isset($this->diskIoRatesByDevice[$zfsPool])) {
                        $result = $this->diskIoRatesByDevice[$zfsPool];
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Returns current disk IO rates for a mountpoint/path.
     * Uses a single dual-sample per SystemHwInfo instance.
     *
     * Result keys: device, read_bps, write_bps, read_iops, write_iops, iops, sample_seconds
     *
     * @param string $mountPoint
     * @param int $sampleSeconds
     *
     * @return array
     */
    public function getDiskIoStat($mountPoint, $sampleSeconds = 1) {
        $result = array();
        if (!empty($mountPoint)) {
            $this->ensureDiskIoRates($sampleSeconds);
            $deviceName = $this->getDiskDevice($mountPoint);
            if (!empty($deviceName)) {
                $rates = $this->lookupDiskIoRatesByDevice($deviceName);
                if (!empty($rates)) {
                    $result = $rates;
                    // keep requested partition name visible when fallback to parent was used
                    $result['device'] = $deviceName;
                    if ($rates['device'] != $deviceName) {
                        $result['device_stats'] = $rates['device'];
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Returns disk IO rates for all preset mountpoints as mountPoint=>ioStat
     *
     * @param int $sampleSeconds
     *
     * @return array
     */
    public function getAllDiskIoStats($sampleSeconds = 1) {
        $result = array();
        $this->ensureDiskIoRates($sampleSeconds);
        if (!empty($this->mountPoints)) {
            foreach ($this->mountPoints as $idx => $eachMountPoint) {
                $eachIoStat = $this->getDiskIoStat($eachMountPoint, $sampleSeconds);
                if (!empty($eachIoStat)) {
                    $result[$eachMountPoint] = $eachIoStat;
                }
            }
        }
        $this->diskIoStats = $result;
        return ($result);
    }

    /**
     * Returns sampled disk IO rates keyed by device name
     *
     * @param int $sampleSeconds
     *
     * @return array
     */
    public function getAllDiskIoRatesByDevice($sampleSeconds = 1) {
        $this->ensureDiskIoRates($sampleSeconds);
        return ($this->diskIoRatesByDevice);
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
     * Gets the release of the operating system.
     *
     * @return string
     */
    public function getOsRelease() {
        return ($this->osRelease);
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

    /**
     * Returns type of the current hardware platform e.g. amd64, arm64, x86_64 etc
     *
     * @return void
     */
    public function getMachineArch() {
        return($this->machineArch);
    }
}
