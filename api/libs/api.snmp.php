<?php

class SNMPHelper {

    protected $altCfg = array();
    protected $mode = '';
    protected $background = false;
    protected $cacheTime = 60;
    protected $pathWalk = '';
    protected $pathSet = '';

    const CACHE_PATH = 'exports/';

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
            $this->background = ($this->altCfg['SNMP_MODE']) ? true : false;
            $this->cacheTime = ($this->altCfg['SNMPCACHE_TIME'] * 60); //in minutes
            $this->pathWalk = $this->altCfg['SNMPWALK_PATH'];
            $this->pathSet = $this->altCfg['SNMPSET_PATH'];
        }
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
        $command = $this->pathWalk . ' -c ' . $community . ' ' . $ip . ' ' . $oid;
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
        snmp_set_oid_output_format(SNMP_OID_OUTPUT_NUMERIC);
        $raw= snmp2_real_walk($ip , $community , $oid , 1000000 ,2);
        $result='';
        debarr($raw);
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
        if ($this->mode == 'system') {
            $result = $this->snmpWalkSystem($ip, $community, $oid, $cache);
        }
        
        if ($this->mode=='native') {
            $result = $this->snmpWalkNative($ip, $community, $oid, $cache);
        }


        return ($result);
    }

}

?>