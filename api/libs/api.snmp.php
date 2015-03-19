<?php

class SNMPHelper {

    protected $altCfg = array();

    const CACHE_PATH = 'exports/';

    public function __construct() {
        $this->loadAlter();
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
     * Executes system SNMP walk interface
     * 
     * @param string $ip
     * @param string $community
     * @param string $oid
     * @param bool   $cache
     * @param bool   $nowait
     * @return string
     */
    protected function snmpWalkSystem($ip, $community, $oid, $cache = true, $nowait = false) {
        $snmpwalk = $this->altCfg['SNMPWALK_PATH'];
        $command = $snmpwalk . ' -c ' . $community . ' ' . $ip . ' ' . $oid;
        $cachetimeout = ($this->altCfg['SNMPCACHE_TIME'] * 60); //in minutes
        $cachetime = time() - $cachetimeout;
        $cachepath = self::CACHE_PATH;
        $cacheFile = $cachepath . $ip . '_' . $oid;
        $result = '';
        if ($nowait) {
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
                if (!$nowait) {
                    file_put_contents($cacheFile, $result);
                }
            }
        } else {
            //no cached file exists
            $result = shell_exec($command);
            if (!$nowait) {
                file_put_contents($cacheFile, $result);
            }
        }
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
    public function walk($ip, $community, $oid, $cache = true, $nowait = false) {
        $result = $this->snmpWalkSystem($ip, $community, $oid, $cache, $nowait);

        return ($result);
    }

}

?>