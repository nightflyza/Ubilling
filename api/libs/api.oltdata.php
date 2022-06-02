<?php

/**
 * OLT local data abstraction layer
 */
class OLTData {

    /**
     * Contains current instance OLT device ID
     *
     * @var int
     */
    protected $oltId = '';

    /**
     * Some paths and data parameters here
     */
    const SIGCACHE_PATH = 'exports/';
    const SIGCACHE_EXT = 'OLTSIGNALS';
    const DISTCACHE_PATH = 'exports/';
    const DISTCACHE_EXT = 'OLTDISTANCE';
    const ONUCACHE_PATH = 'exports/';
    const ONUCACHE_EXT = 'ONUINDEX';
    const INTCACHE_PATH = 'exports/';
    const INTCACHE_EXT = 'ONUINTERFACE';
    const INTDESCRCACHE_EXT = 'OLTINTERFACEDESCR';
    const FDBCACHE_PATH = 'exports/';
    const FDBCACHE_EXT = 'OLTFDB';
    const DEREGCACHE_PATH = 'exports/';
    const DEREGCACHE_EXT = 'ONUDEREGS';
    const UPTIME_PATH = 'exports/';
    const UPTIME_EXT = 'OLTUPTIME';
    const TEMPERATURE_PATH = 'exports/';
    const TEMPERATURE_EXT = 'OLTTEMPERATURE';
    const MACDEVIDCACHE_PATH = 'exports/';
    const MACDEVIDCACHE_EXT = 'ONUMACDEVINDEX';
    const ONUSIG_PATH = 'content/documents/onusig/';

    /**
     * Creates new OLT data manipulation instance
     * 
     * @param int $oltId
     */
    public function __construct($oltId = '') {
        if (!empty($oltId)) {
            $this->oltId = $oltId;
        } else {
            throw new Exception('EX_OLTID_EMPTY');
        }
    }

    /**
     * Sets current instance OLT device ID
     * 
     * @param int $oltId
     * 
     * @return void
     */
    protected function setOltId($oltId) {
        $this->oltId = $oltId;
    }

    /**
     * Saves current OLT temperature
     * Input format: float
     * 
     * @param float $tempRaw
     * 
     * @return void
     */
    public function writeTemperature($tempRaw) {
        $dataToSave = trim($tempRaw);
        if (!empty($dataToSave)) {
            file_put_contents(self::TEMPERATURE_PATH . $this->oltId . '_' . self::TEMPERATURE_EXT, $dataToSave);
        }
    }

    /**
     * Returns current OLT temperature
     * 
     * @return float
     */
    public function readTemperature() {
        $result = '';
        if (file_exists(self::TEMPERATURE_PATH . $this->oltId . '_' . self::TEMPERATURE_EXT)) {
            $result = file_get_contents(self::TEMPERATURE_PATH . $this->oltId . '_' . self::TEMPERATURE_EXT);
        }
        return($result);
    }

    /**
     * Saves last OLT uptime
     * Input format: string
     * 
     * @param float $uptimeRaw
     * 
     * @return void
     */
    public function writeUptime($uptimeRaw) {
        $dataToSave = trim($uptimeRaw);
        if (!empty($dataToSave)) {
            file_put_contents(self::UPTIME_PATH . $this->oltId . '_' . self::UPTIME_EXT, $dataToSave);
        }
    }

    /**
     * Returns last OLT uptime
     * 
     * @return string
     */
    public function readUptime() {
        $result = '';
        if (file_exists(self::UPTIME_PATH . $this->oltId . '_' . self::UPTIME_EXT)) {
            $result = file_get_contents(self::UPTIME_PATH . $this->oltId . '_' . self::UPTIME_EXT);
        }
        return($result);
    }

}
