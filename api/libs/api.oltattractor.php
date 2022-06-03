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
        }
    }

    /**
     * Sets current instance OLT device ID
     * 
     * @param int $oltId
     * 
     * @return void
     */
    public function setOltId($oltId) {
        $this->oltId = $oltId;
        //                                    __    _                                   
        //                               _wr""        "-q__                             
        //                            _dP                 9m_     
        //                          _#P                     9#_                         
        //                         d#@                       9#m                        
        //                        d##                         ###                       
        //                       J###                         ###L                      
        //                       {###K                       J###K                      
        //                       ]####K      ___aaa___      J####F                      
        //                   __gmM######_  w#P""   ""9#m  _d#####Mmw__                  
        //                _g##############mZ_         __g##############m_               
        //              _d####M@PPPP@@M#######Mmp gm#########@@PPP9@M####m_             
        //             a###""          ,Z"#####@" '######"\g          ""M##m            
        //            J#@"             0L  "*##     ##@"  J#              *#K           
        //            #"               `#    "_gmwgm_~    dF               `#_          
        //           7F                 "#_   ]#####F   _dK                 JE          
        //           ]                    *m__ ##### __g@"                   F          
        //                                  "PJ#####LP"                                 
        //            `                       0######_                      '           
        //                                  _0########_                                   
        //                .               _d#####^#####m__              ,              
        //                 "*w_________am#####P"   ~9#####mw_________w*"                  
        //                     ""9@#####@M""           ""P@#####@M""                    
    }

    /**
     * Returns some data container unpacked content
     * 
     * @param string $dataContainer Path to data container
     * @param bool $isArray is container data an serialized array?
     * 
     * @return array/string
     */
    protected function getData($dataContainer, $isArray = true) {
        $result = ($isArray) ? array() : '';
        if (!empty($this->oltId)) {
            if (file_exists($dataContainer)) {
                $result = file_get_contents($dataContainer);
                if ($isArray) {
                    $result = unserialize($result);
                }
            }
        } else {
            throw new Exception('EX_OLTID_EMPTY');
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
        if (!empty($this->oltId)) {
            if (is_array($dataToSave)) {
                $dataToSave = serialize($dataToSave);
            }
            file_put_contents($dataContainer, $dataToSave);
        } else {
            throw new Exception('EX_OLTID_EMPTY');
        }
    }

    /**
     * Extracts OLT ID from data container name
     * 
     * @param string $dataContainerName
     * 
     * @return int/bool on error
     */
    protected function extractOltID($dataContainerName) {
        $result = false;
        if (!empty($dataContainerName)) {
            $anyDigits = preg_replace("#[^0-9]#Uis", '', $dataContainerName);
            if (!empty($anyDigits)) {
                $result = $anyDigits;
            }
        }
        return($result);
    }

    /**
     * Return list of available OLT data containers as oltId=>containerName
     * 
     * @param string $containerPath
     * @param string $containerMark
     * 
     * @return array
     */
    protected function getContainers($containerPath, $containerMark) {
        $result = array();
        $availContainers = rcms_scandir($containerPath, '*_' . $containerMark);
        if (!empty($availContainers)) {
            foreach ($availContainers as $io => $eachContainer) {
                $oltId = $this->extractOltID($eachContainer);
                if ($oltId !== false) {
                    $result[$oltId] = $eachContainer;
                }
            }
        }
        return($result);
    }

    /**
     * OLT data manipulation subroutines
     */

    /**
     * Saves current OLT temperature
     * 
     * @param float $tempRaw current OLT temperature in celsius
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
     * Returns current OLT temperature in celsius
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
     * 
     * @param string $uptimeRaw readable string like "666 days"
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
     * Returns last OLT uptime just as readable string
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
     * 
     * @param array $signalsArr array of [onuMac or onuSerial] => signalString
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
     * @return array as [onuMac or onuSerial] => signalString
     */
    public function readSignals() {
        $dataContainer = self::SIGCACHE_PATH . $this->oltId . '_' . self::SIGCACHE_EXT;
        $result = $this->getData($dataContainer);
        return($result);
    }

    /**
     * Saves latest OLT all ONUs MAC index
     * 
     * @param array $macIndexArr array of [onuMac]=>deviceId
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
     * @return array as [onuMac]=>deviceId
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
     * 
     * @param array $distsArr array of [onuMac or onuSerial] => onuDistance in meters
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
     * @return array as [onuMac or onuSerial] => onuDistance in meters
     */
    public function readDistances() {
        $dataContainer = self::DISTCACHE_PATH . $this->oltId . '_' . self::DISTCACHE_EXT;
        $result = $this->getData($dataContainer);
        return($result);
    }

    /**
     * Saves latest OLT all ONUs devices cache
     * 
     * @param array $onusArr array of [onuIdx]=>onuMac or onuSerial
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
     * @return array as [onuIdx]=>onuMac or onuSerial
     */
    public function readOnuCache() {
        $dataContainer = self::ONUCACHE_PATH . $this->oltId . '_' . self::ONUCACHE_EXT;
        $result = $this->getData($dataContainer);
        return($result);
    }

    /**
     * Saves latest OLT all ONUs interfaces cache
     * 
     * @param array $ifacesArr array of [onuMac or onuSerial]=>InterfaceName like EPON0/5:1
     * 
     * @return void
     */
    public function writeInterfaces($ifacesArr) {
        $dataToSave = $ifacesArr;
        $dataContainer = self::INTCACHE_PATH . $this->oltId . '_' . self::INTCACHE_EXT;
        $this->saveData($dataContainer, $dataToSave);
    }

    /**
     * Returns latest OLT all ONUs interfaces cache
     * 
     * @return array as array [onuMac or onuSerial]=>interfaceName like EPON0/5:1
     */
    public function readInterfaces() {
        $dataContainer = self::INTCACHE_PATH . $this->oltId . '_' . self::INTCACHE_EXT;
        $result = $this->getData($dataContainer);
        return($result);
    }

    /**
     * Saves latest OLT all interfaces description cache
     * 
     * @param array $ifdescrsArr array of [interfaceName]=>description
     * 
     * @return void
     */
    public function writeInterfacesDescriptions($ifdescrsArr) {
        $dataToSave = $ifdescrsArr;
        $dataContainer = self::INTCACHE_PATH . $this->oltId . '_' . self::INTDESCRCACHE_EXT;
        $this->saveData($dataContainer, $dataToSave);
    }

    /**
     * Returns latest OLT all interfaces description cache
     * 
     * @return array as [interfaceName]=>description
     */
    public function readInterfacesDescriptions() {
        $dataContainer = self::INTCACHE_PATH . $this->oltId . '_' . self::INTDESCRCACHE_EXT;
        $result = $this->getData($dataContainer);
        return($result);
    }

    /**
     * Saves OLT full FDB table into cache
     * 
     * @param array $fdbStruct array of [onuMac/onuSerial][id]=>mac+vlan
     * 
     * @return void
     */
    public function writeFdb($fdbStruct) {
        /**
         * Expected input data structure:
         * 
         * [onuMac/onuSerial] => Array
         *    (
         *        [someId] => Array
         *           (
         *                [mac] => e8:ba:70:c6:49:aa
         *                [vlan] => 1
         *            )
         *
         *        [anotherId] => Array
         *            (
         *                [mac] => e8:ba:70:c6:49:bb
         *                [vlan] => 1
         *            )
         * */
        $dataToSave = $fdbStruct;
        $dataContainer = self::FDBCACHE_PATH . $this->oltId . '_' . self::FDBCACHE_EXT;
        $this->saveData($dataContainer, $dataToSave);
    }

    /**
     * Returns OLT full FDB table from cache
     * 
     * @return array as array of [onuMac/onuSerial][id]=>mac+vlan
     */
    public function readFdb() {
        $dataContainer = self::FDBCACHE_PATH . $this->oltId . '_' . self::FDBCACHE_EXT;
        $result = $this->getData($dataContainer);
        /**
         * Expected return data struct:
         * 
         * [onuMac/onuSerial] => Array
         *    (
         *        [someId] => Array
         *           (
         *                [mac] => e8:ba:70:c6:49:aa
         *                [vlan] => 1
         *            )
         *
         *        [anotherId] => Array
         *            (
         *                [mac] => e8:ba:70:c6:49:bb
         *                [vlan] => 1
         *            )
         * */
        return($result);
    }

    /**
     * Saves OLT all ONUs deregistrations reasons
     * 
     * @param array $onuDeregsArr array of [onuMac/onuSerial]=>deregReason like "wire down"
     * 
     * @return void
     */
    public function writeDeregs($onuDeregsArr) {
        $dataToSave = $onuDeregsArr;
        $dataContainer = self::DEREGCACHE_PATH . $this->oltId . '_' . self::DEREGCACHE_EXT;
        $this->saveData($dataContainer, $dataToSave);
    }

    /**
     * Returns OLT all ONUs deregistrations reasons
     * 
     * @return array as array of [onuMac/onuSerial]=>deregReason like "wire down"
     */
    public function readDeregs() {
        $dataContainer = self::DEREGCACHE_PATH . $this->oltId . '_' . self::DEREGCACHE_EXT;
        $result = $this->getData($dataContainer);
    }

    /**
     * Available contained data listers
     */

    /**
     * Returns list of available distances containers as oltId=>containerName
     * 
     * @return array
     */
    protected function listDistances() {
        $containerPath = self::DISTCACHE_PATH;
        $containerMark = self::DISTCACHE_EXT;
        $result = $this->getContainers($containerPath, $containerMark);
        return($result);
    }

    /**
     * Public data getters here
     */

    /**
     * Returns list of all OLTs available ONU distances as [onuMac/onuSerial]=>distance in meters
     * 
     * @return array
     */
    public function getDistancesAll() {
        $result = array();
        $oltData = new OLTAttractor();
        $availDataContainers = $oltData->listDistances();
        if (!empty($availDataContainers)) {
            foreach ($availDataContainers as $eachContainerKey => $eachContainerName) {
                $oltData->setOltId($eachContainerKey);
                //need to replace this with parametric container data loader like getData
                $containerData = $oltData->readDistances();
                if (!empty($containerData)) {
                    $result += $containerData;
                }
            }
        }
        return($result);
    }

}
