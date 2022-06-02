<?php

/**
 * OLT local data manipultaion abstraction layer
 */
class OLTAttractor {

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
     * @param int $oltId Existing OLT device ID
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
     * Returns some data container content
     * 
     * @param string $dataContainer Path to data container
     * @param bool $isArray is container data an serialized array?
     * 
     * @return array/string
     */
    protected function getData($dataContainer, $isArray = true) {
        $result = ($isArray) ? array() : '';
        if (file_exists($dataContainer)) {
            $result = file_get_contents($dataContainer);
            if ($isArray) {
                $result = unserialize($result);
            }
        }
        return($result);
    }

    /**
     * Saves some data in container
     * 
     * @param string $dataContainer
     * @param array/string $dataToSave data to save
     * 
     * @return void
     */
    protected function saveData($dataContainer, $dataToSave) {
        if (is_array($dataToSave)) {
            $dataToSave = serialize($dataToSave);
        }
        file_put_contents($dataContainer, $dataToSave);
    }

    /**
     * OLT data manipulation subroutines
     */

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
            $dataContainer = self::TEMPERATURE_PATH . $this->oltId . '_' . self::TEMPERATURE_EXT;
            $this->saveData($dataContainer, $dataToSave);
        }
    }

    /**
     * Returns current OLT temperature
     * 
     * @return float
     */
    public function readTemperature() {
        $dataContainer = self::TEMPERATURE_PATH . $this->oltId . '_' . self::TEMPERATURE_EXT;
        $result = $this->getData($dataContainer, false);
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
            $dataContainer = self::UPTIME_PATH . $this->oltId . '_' . self::UPTIME_EXT;
            $this->saveData($dataContainer, $dataToSave);
        }
    }

    /**
     * Returns last OLT uptime
     * 
     * @return string
     */
    public function readUptime() {
        $dataContainer = self::UPTIME_PATH . $this->oltId . '_' . self::UPTIME_EXT;
        $result = $this->getData($dataContainer, false);
        return($result);
    }

    /**
     * Saves latest OLT all ONUs signals
     * Input format: array  onuMac or onuSerial => signalString
     * 
     * @param array $signalsArr
     * 
     * @return void
     */
    public function writeSignals($signalsArr) {
        $dataToSave = $signalsArr;
        $dataContainer = self::SIGCACHE_PATH . $this->oltId . '_' . self::SIGCACHE_EXT;
        $this->saveData($dataContainer, $dataToSave);
    }

    /**
     * Returns latest OLT all ONUs signals
     * 
     * 
     * @return array as onuMac or onuSerial => signalString
     */
    public function readSignals() {
        $dataContainer = self::SIGCACHE_PATH . $this->oltId . '_' . self::SIGCACHE_EXT;
        $result = $this->getData($dataContainer);
        return($result);
    }

    /**
     * Saves latest OLT all ONUs MAC index
     * Input format: array onuMac=>deviceId
     * 
     * @param array $macIndexArr
     * 
     * @return void
     */
    public function writeMacIndex($macIndexArr) {
        $dataToSave = $macIndexArr;
        $dataContainer = self::MACDEVIDCACHE_PATH . $this->oltId . '_' . self::MACDEVIDCACHE_EXT;
        $this->saveData($dataContainer, $dataToSave);
    }

    /**
     * Returns latest OLT all ONUs MAC index
     * 
     * @return array as onuMac=>deviceId
     */
    public function readMacIndex() {
        $dataContainer = self::MACDEVIDCACHE_PATH . $this->oltId . '_' . self::MACDEVIDCACHE_EXT;
        $result = $this->getData($dataContainer);
        return($result);
    }

    /**
     * Creates single ONU signal history record
     * 
     * @param string $onuIdent onuMac or OnuSerial
     * @param float $signalLevel latest ONU signal in dB
     * 
     * @return void
     */
    public function writeSignalHistory($onuIdent, $signalLevel) {
        if (!empty($onuIdent)) {
            $dataContainer = self::ONUSIG_PATH . md5($onuIdent);
            file_put_contents($dataContainer, curdatetime() . ',' . $signalLevel . PHP_EOL, FILE_APPEND);
        }
    }

    /**
     * Saves latest OLT all ONUs distances
     * Input format: array  onuMac or onuSerial => onuDistance in meters
     * 
     * @param array $distArr
     * 
     * @return void
     */
    public function writeDistances($distsArr) {
        $dataToSave = $distsArr;
        $dataContainer = self::DISTCACHE_PATH . $this->oltId . '_' . self::DISTCACHE_EXT;
        $this->saveData($dataContainer, $dataToSave);
    }

    /**
     * Returns latest OLT all ONUs distances
     * 
     * @return array as onuMac or onuSerial => onuDistance in meters
     */
    public function readDistances() {
        $dataContainer = self::DISTCACHE_PATH . $this->oltId . '_' . self::DISTCACHE_EXT;
        $result = $this->getData($dataContainer);
        return($result);
    }

    /**
     * Saves latest OLT all ONUs devices cache
     * Input format: array  deviceId=>onuMac or onuSerial
     * 
     * @param array $onusArr
     * 
     * @return void
     */
    public function writeOnuCache($onusArr) {
        $dataToSave = $onusArr;
        $dataContainer = self::ONUCACHE_PATH . $this->oltId . '_' . self::ONUCACHE_EXT;
        $this->saveData($dataContainer, $dataToSave);
    }

    /**
     * Returns latest OLT all ONUs devices cache
     * 
     * @return array as deviceId=>onuMac or onuSerial
     */
    public function readOnuCache() {
        $dataContainer = self::ONUCACHE_PATH . $this->oltId . '_' . self::ONUCACHE_EXT;
        $result = $this->getData($dataContainer);
        return($result);
    }

}
