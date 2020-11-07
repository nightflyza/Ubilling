<?php

/**
 * Ubilling SNMP abstraction class
 */
class SNMPHelper {

    /**
     * System-wide alter.ini config as array
     * @var array
     */
    protected $altCfg = array();

    /**
     * Pre-configured SNMP work mode - system/native/class
     * @var string
     */
    protected $mode = '';

    /**
     * System snmpwalk background multi-threaded mode
     * @var bool
     */
    protected $background = false;

    /**
     * SNMP raw data caching timeout in minutes
     * @var int
     */
    protected $cacheTime = 60;

    /**
     * System snmpwalk binary path with -On and version params
     * @var string
     */
    protected $pathWalk = '';

    /**
     * System snmpset binary path with -On and version params
     * @var string
     */
    protected $pathSet = '';

    /**
     * Native PHP snmp functions retries
     * @var int
     */
    protected $retriesNative = 1;

    /**
     * Native PHP snmp functions timeout
     * @var int
     */
    protected $timeoutNative = 1000000;

    const CACHE_PATH = 'exports/'; //raw SNMP data cache path
    const EX_NOT_IMPL = 'NOT_IMPLEMENTED_MODE'; //not yet implemented SNMP mode exception
    const EX_WRONG_DATA = 'WRONG_DATA_FORMAT_RECEIVED';

    public function __construct() {
        $this->loadAlter();
        $this->setOptions();
    }

    /**
     * Loads system alter config at startup
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
     * Sets all needed options to protected props
     * 
     * @return void
     */
    protected function setOptions() {
        if (!empty($this->altCfg)) {
            $this->mode = $this->altCfg['SNMP_MODE'];
            $this->background = ($this->altCfg['SNMPWALK_BACKGROUND']) ? true : false;
            $this->cacheTime = ($this->altCfg['SNMPCACHE_TIME'] * 60); //in minutes
            $this->pathWalk = $this->altCfg['SNMPWALK_PATH'];
            $this->pathSet = $this->altCfg['SNMPSET_PATH'];
        }
    }

    /**
     * Public background mode setter
     * 
     * @param bool $value
     * 
     * @return void
     */
    public function setBackground($value) {
        $this->background = ($value) ? true : false;
    }

    /**
     * Public getter of background mode
     * 
     * @return bool
     */
    public function getBackground() {
        return ($this->background);
    }

    /**
     * Set SNMP run mode (system/native/class)
     * 
     * @param string $value
     * 
     * @return void
     */
    public function setMode($value) {
        $this->mode = $value;
    }

    /**
     * Public getter of SNMP run mode
     * 
     * @return bool
     */
    public function getMode() {
        return ($this->mode);
    }

    /**
     * Executes system SNMP walk interface
     * 
     * @param string $ip
     * @param string $community
     * @param string $oid
     * @param bool   $cache
     * @param bool   $nowait
     * @return string
     */
    protected function snmpWalkSystem($ip, $community, $oid, $cache = true) {
        $command = $this->pathWalk . ' -c ' . $community . ' -Cc ' . $ip . ' ' . $oid;
        $cachetime = time() - $this->cacheTime;
        $cachepath = self::CACHE_PATH;
        $cacheFile = $cachepath . $ip . '_' . $oid;
        $result = '';
        if ($this->background) {
            $command = $command . ' > ' . $cacheFile . '&';
        }

        //cache handling
        if (file_exists($cacheFile)) {
            //cache not expired
            if ((filemtime($cacheFile) > $cachetime) AND ( $cache == true)) {
                $result = file_get_contents($cacheFile);
            } else {
                //cache expired - refresh data
                $result = shell_exec($command);
                if (!$this->background) {
                    file_put_contents($cacheFile, $result);
                }
            }
        } else {
            //no cached file exists
            $result = shell_exec($command);
            if (!$this->background) {
                file_put_contents($cacheFile, $result);
            }
        }

        return ($result);
    }

    /**
     * Executes native SNMP walk interface
     * 
     * @param string $ip
     * @param string $community
     * @param string $oid
     * @param bool   $cache
     * @param bool   $nowait
     * @return string
     */
    protected function snmpWalkNative($ip, $community, $oid, $cache = true) {
        $cachetime = time() - $this->cacheTime;
        $cachepath = self::CACHE_PATH;
        $cacheFile = $cachepath . $ip . '_' . $oid;
        $result = '';
        //cache handling
        if (file_exists($cacheFile)) {
            //cache not expired
            if ((filemtime($cacheFile) > $cachetime) AND ( $cache == true)) {
                $result = file_get_contents($cacheFile);
            } else {
                //cache expired - refresh data
                snmp_set_oid_output_format(SNMP_OID_OUTPUT_NUMERIC);
                @$raw = snmpwalkoid($ip, $community, $oid, $this->timeoutNative, $this->retriesNative);
                if (!empty($raw)) {
                    foreach ($raw as $oid => $value) {
                        $result.=$oid . ' = ' . $value . "\n";
                    }
                } else {
                    @$value = snmpget($ip, $community, $oid, $this->timeoutNative, $this->retriesNative);
                    $result = $oid . ' = ' . $value;
                }
                file_put_contents($cacheFile, $result);
            }
        } else {
            //no cached file exists
            snmp_set_oid_output_format(SNMP_OID_OUTPUT_NUMERIC);
            @$raw = snmprealwalk($ip, $community, $oid, $this->timeoutNative, $this->retriesNative);

            if (!empty($raw)) {
                foreach ($raw as $oid => $value) {
                    $result.=$oid . ' = ' . $value . "\n";
                }
            } else {
                @$value = snmpget($ip, $community, $oid, $this->timeoutNative, $this->retriesNative);
                $result = $oid . ' = ' . $value;
            }
            file_put_contents($cacheFile, $result);
        }


        return ($result);
    }

    /**
     * Executes php 5.4 SNMP class walk interface
     * 
     * @param string $ip
     * @param string $community
     * @param string $oid
     * @param bool   $cache
     * @param bool   $nowait
     * @return string
     */
    protected function snmpWalkClass($ip, $community, $oid, $cache = true) {
        $cachetime = time() - $this->cacheTime;
        $cachepath = self::CACHE_PATH;
        $cacheFile = $cachepath . $ip . '_' . $oid;
        $result = '';
        //cache handling
        if (file_exists($cacheFile)) {
            //cache not expired
            if ((filemtime($cacheFile) > $cachetime) AND ( $cache == true)) {
                $result = file_get_contents($cacheFile);
            } else {
                //cache expired - refresh data
                snmp_set_oid_output_format(SNMP_OID_OUTPUT_NUMERIC);
                $session = new SNMP(SNMP::VERSION_1, $ip, $community, $this->timeoutNative, $this->retriesNative);
                $session->oid_increasing_check = false;
                $raw = $session->walk($oid);
                $session->close();

                if (!empty($raw)) {
                    foreach ($raw as $oid => $value) {
                        $result.=$oid . ' = ' . $value . "\n";
                    }
                }
                file_put_contents($cacheFile, $result);
            }
        } else {
            //no cached file exists
            snmp_set_oid_output_format(SNMP_OID_OUTPUT_NUMERIC);
            $session = new SNMP(SNMP::VERSION_1, $ip, $community, $this->timeoutNative, $this->retriesNative);
            $raw = $session->walk($oid);
            $session->close();

            if (!empty($raw)) {
                foreach ($raw as $oid => $value) {
                    $result.=$oid . ' = ' . $value . "\n";
                }
            }

            file_put_contents($cacheFile, $result);
        }


        return ($result);
    }

    /**
     * Executes native SNMP set interface
     * 
     * @param string $ip
     * @param string $community
     * @param array  $data
     * @return string
     */
    protected function snmpSetSystem($ip, $community, $data) {
        $result = '';
        if (!empty($data)) {
            if (is_array($data)) {
                $command = $this->pathSet . ' -c ' . $community . ' ' . $ip . ' ';
                foreach ($data as $io => $each) {
                    if (isset($each['oid']) AND ( isset($each['type']) AND ( isset($each['value'])))) {
                        $command.=' ' . $each['oid'] . ' ' . $each['type'] . ' ' . $each['value'];
                    } else {
                        throw new Exception(self::EX_WRONG_DATA);
                    }
                }

                $result.= shell_exec($command);
            } else {
                throw new Exception(self::EX_WRONG_DATA);
            }
        }
        return ($result);
    }

    /**
     * Executes native SNMP set interface
     * 
     * @param string $ip
     * @param string $community
     * @param array  $data
     * @return string
     */
    protected function snmpSetNative($ip, $community, $data) {
        $result = '';
        if (!empty($data)) {
            if (is_array($data)) {
                foreach ($data as $io => $each) {
                    if (isset($each['oid']) AND ( isset($each['type']) AND ( isset($each['value'])))) {
                        @$pushResult = snmp2_set($ip, $community, $each['oid'], $each['type'], $each['value'], $this->timeoutNative, $this->retriesNative);
                        if ($pushResult) {
                            $result.=trim($this->snmpWalkNative($ip, $community, $each['oid'], false)) . "\n";
                        }
                    } else {
                        throw new Exception(self::EX_WRONG_DATA);
                    }
                }
            } else {
                throw new Exception(self::EX_WRONG_DATA);
            }
        }
        return ($result);
    }
    
     /**
     * Executes PHP 5.4 SNMP set interface
     * 
     * @param string $ip
     * @param string $community
     * @param array  $data
     * @return string
     */
    protected function snmpSetClass($ip, $community, $data) {
        $result = '';
        if (!empty($data)) {
            if (is_array($data)) {
                foreach ($data as $io => $each) {
                    if (isset($each['oid']) AND ( isset($each['type']) AND ( isset($each['value'])))) {
                        $session = new SNMP(SNMP::VERSION_2c, $ip, $community, $this->timeoutNative, $this->retriesNative);
                        @$pushResult = $session->set($each['oid'],$each['type'],$each['value']);
                        $session->close();
                        if ($pushResult) {
                            $result.=trim($this->snmpWalkClass($ip, $community, $each['oid'], false)) . "\n";
                        }
                     
                    } else {
                        throw new Exception(self::EX_WRONG_DATA);
                    }
                }
            } else {
                throw new Exception(self::EX_WRONG_DATA);
            }
        }
        return ($result);
    }

    /**
     * Public SNMP walk interface
     * 
     * @param string $ip
     * @param string $community
     * @param string $oid
     * @param bool   $cache
     * @param bool   $nowait
     * @return string
     */
    public function walk($ip, $community, $oid, $cache = true) {
        switch ($this->mode) {
            case 'system':
                $result = $this->snmpWalkSystem($ip, $community, $oid, $cache);
                break;

            case 'native':
                $result = $this->snmpWalkNative($ip, $community, $oid, $cache);
                break;

            case 'class':
                $result = $this->snmpWalkClass($ip, $community, $oid, $cache);
                break;

            default :
                throw new Exception(self::EX_NOT_IMPL);
        }

        return ($result);
    }

    /**
     * Public SNMP set interface 
     * 
     *  data format example: 
     *               $data[]=array(
     *                           'oid' => '.1.3.6.1.2.1.1.6.0',
     *                           'type' => 's',
     *                           'value' => 'some location'
     *              );
     * 
     * @param string $ip
     * @param string $community
     * @param array  $data
     * @return string
     */
    public function set($ip, $community, $data) {
        switch ($this->mode) {
            case 'system':
                $result = $this->snmpSetSystem($ip, $community, $data);
                break;

            case 'native':
                $result = $this->snmpSetNative($ip, $community, $data);
                break;

            case 'class':
                $result = $this->snmpSetClass($ip, $community, $data);
                break;

            default :
                throw new Exception(self::EX_NOT_IMPL);
        }
        return ($result);
    }

}

?>