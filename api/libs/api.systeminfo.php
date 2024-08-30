<?php

class SystemInfo {
    protected $os = '';
    protected $osFullRelease = '';
    protected $hostname = '';
    protected $cpuName = '';
    protected $cpuCores = 1;
    protected $memTotal = 0;
    protected $memFree = 0;
    protected $memUsed = 0;
    protected $uptime = '';
    protected $loadAverage = 0;
    protected $la1 = 0;
    protected $la5 = 0;
    protected $la15 = 0;
    protected $loadPercent = 0;
    protected $uptimePath = '/usr/bin/uptime';
    protected $sysctlPath = '/sbin/sysctl';

    public function __construct() {
        $this->setOS();
        $this->setPaths();
        $this->setLoadAverage();
        $this->setCpuCores();
        $this->setCpuName();
    }

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
        $this->os = php_uname('s');
        $this->osFullRelease = php_uname('a');
        $this->hostname = php_uname('n');
    }

    protected function setPaths() {
        switch ($this->os) {
            case 'FreeBSD':
                $this->sysctlPath = '/sbin/sysctl';
                break;
            case 'Linux':
                $this->sysctlPath = '/usr/sbin/sysctl';
                break;
        }
    }


    protected function setLoadAverage() {
        $this->loadAverage = sys_getloadavg();
        $this->la1 = round($this->loadAverage[0], 2);
        $this->la5 = round($this->loadAverage[1], 2);
        $this->la15 = round($this->loadAverage[2], 2);
    }

    protected function setCpuName() {
        $cpuName = '';

        switch ($this->os) {
            case 'FreeBSD':
                $cpuName = $this->grabCmdOutput($this->sysctlPath, '-n hw.model');
                break;
            case 'Linux':
                $raw = $this->grabCmdOutput('cat /proc/cpuinfo | grep "model name" | head -n 1');
                $cpuName = $raw;
                break;
        }

        $this->cpuName = $cpuName;
    }

    protected function setCpuCores() {
        $coresCount = 0;

        switch ($this->os) {
            case 'FreeBSD':
                $coresCount = $this->grabCmdOutput($this->sysctlPath, '-n hw.ncpu');
                break;
            case 'Linux':
                $raw = $this->grabCmdOutput('cat /proc/cpuinfo | grep "siblings" | head -n 1');
                $coresCount = (int)$raw;
                break;
        }

        $this->cpuCores = $coresCount;
    }
}
