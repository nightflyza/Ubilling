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
     * Some basic paths and data parameters here
     */
    const CACHE_ROOT_PATH = 'exports/pondata/';
    const SIGCACHE_PATH = 'exports/pondata/signals/';
    const SIGCACHE_EXT = 'OLTSIGNALS';
    const DISTCACHE_PATH = 'exports/pondata/dist/';
    const DISTCACHE_EXT = 'OLTDISTANCE';
    const ONUCACHE_PATH = 'exports/pondata/onucache/';
    const ONUCACHE_EXT = 'ONUINDEX';
    const INTCACHE_PATH = 'exports/pondata/iface/';
    const INTCACHE_EXT = 'ONUINTERFACE';
    const INTDESCRCACHE_EXT = 'OLTINTERFACEDESCR';
    const FDBCACHE_PATH = 'exports/pondata/fdb/';
    const FDBCACHE_EXT = 'OLTFDB';
    const DEREGCACHE_PATH = 'exports/pondata/dereg/';
    const DEREGCACHE_EXT = 'ONUDEREGS';
    const UPTIME_PATH = 'exports/pondata/uptime/';
    const UPTIME_EXT = 'OLTUPTIME';
    const TEMPERATURE_PATH = 'exports/pondata/temp/';
    const TEMPERATURE_EXT = 'OLTTEMPERATURE';
    const MACDEVIDCACHE_PATH = 'exports/pondata/macdev/';
    const MACDEVIDCACHE_EXT = 'ONUMACDEVINDEX';
    const UNIOPERSTATS_PATH = 'exports/pondata/unioperstats/';
    const UNIOPERSTATS_EXT = 'UNIOPERSTATS';

    /**
     * ONUs signal history path
     */
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
     * Returns full content of data container
     * 
     * @param string $dataContainer
     * 
     * @return array
     */
    public function loadContainerData($dataContainer) {
        return($this->getData($dataContainer));
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
                    $result[$oltId] = $containerPath . $eachContainer;
                }
            }
        }
        return($result);
    }

    /**
     * Returns content of all OLT data containers with some path and mark
     * 
     * @param string $containerPath
     * @param string $containerPath
     * 
     * @return array 
     */
    protected function getContainersContent($containerPath, $containerMark) {
        $result = array();
        $oltData = new OLTAttractor();
        $availDataContainers = $this->getContainers($containerPath, $containerMark);
        if (!empty($availDataContainers)) {
            foreach ($availDataContainers as $eachContainerKey => $eachContainerName) {
                $oltData->setOltId($eachContainerKey);
                $containerData = $oltData->loadContainerData($eachContainerName);
                if (!empty($containerData)) {
                    $result += $containerData;
                }
            }
        }
        return($result);
    }

    /**
     * Checks is any data containers available for some path/mark?
     * 
     * @param string $containerPath
     * @param string $containerMark
     * 
     * @return bool
     */
    protected function checkContainersAvailable($containerPath, $containerMark) {
        $result = false;
        $availContainers = rcms_scandir($containerPath, '*_' . $containerMark);
        if (!empty($availContainers)) {
            $result = true;
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
        return ($result);
    }

    /**
     * Saves OLT all ONUs UNI-ports operational statuses
     *
     * @param array $uniStatsArr array of [onuMac/onuSerial] => (ethPort => Status) like 1(up) or 0(down)
     *
     * @return void
     *
     * @throws Exception
     */
    public function writeUniOperStats($uniStatsArr) {
        $dataToSave = $uniStatsArr;
        $dataContainer = self::UNIOPERSTATS_PATH . $this->oltId . '_' . self::UNIOPERSTATS_EXT;
        $this->saveData($dataContainer, $dataToSave);
    }

    /**
     * Returns OLT all ONUs UNI-ports operational statuses
     *
     * @return array as array of [onuMac/onuSerial] => (ethPort => Status) like 1(up) or 0(down)
     *
     * @throws Exception
     */
    public function readUniOperStats() {
        $dataContainer = self::UNIOPERSTATS_PATH . $this->oltId . '_' . self::UNIOPERSTATS_EXT;
        $result = $this->getData($dataContainer);
        return ($result);
    }

    /**
     * Public data getters here
     */

    /**
     * Returns list of all OLTs available ONUs distances as [onuMac/onuSerial]=>distance in meters
     * 
     * @return array
     */
    public function getDistancesAll() {
        $containerPath = self::DISTCACHE_PATH;
        $containerMark = self::DISTCACHE_EXT;
        return($this->getContainersContent($containerPath, $containerMark));
    }

    /**
     * Returns list of all OLTs available ONUs dereg reasons as [onuMac/onuSerial]=>reason
     * 
     * @return array
     */
    public function getDeregsAll() {
        $containerPath = self::DEREGCACHE_PATH;
        $containerMark = self::DEREGCACHE_EXT;
        return($this->getContainersContent($containerPath, $containerMark));
    }

    /**
     * Returns list of all OLTs available ONUs signals as [onuMac/onuSerial]=>signal in db
     * 
     * @return array
     */
    public function getSignalsAll() {
        $containerPath = self::SIGCACHE_PATH;
        $containerMark = self::SIGCACHE_EXT;
        return($this->getContainersContent($containerPath, $containerMark));
    }

    /**
     * Returns list of all OLTs available ONUs interfaces as [onuMac/onuSerial]=>interfaceName
     * 
     * @return array
     */
    public function getInterfacesAll() {
        $containerPath = self::INTCACHE_PATH;
        $containerMark = self::INTCACHE_EXT;
        return($this->getContainersContent($containerPath, $containerMark));
    }

    /**
     * Returns list of all ONUs FDB data as [onuMac/onuSerial]=>fdbStruct
     * 
     * @return array
     */
    public function getFdbAll() {
        $containerPath = self::FDBCACHE_PATH;
        $containerMark = self::FDBCACHE_EXT;
        return($this->getContainersContent($containerPath, $containerMark));
    }

    /**
     * Returns list of all OLTs available ONUs UNI-ports operational statuses [onuMac/onuSerial] => (ethPort => Status) like 1(up) or 0(down)
     *
     * @return array
     */
    public function getUniOperStatsAll() {
        $containerPath = self::UNIOPERSTATS_PATH;
        $containerMark = self::UNIOPERSTATS_EXT;
        return($this->getContainersContent($containerPath, $containerMark));
    }

    /**
     * Returns list of all OLTs available ONUs interfaces as [oltId][interfaceName]=>interfaceDescr
     * 
     * @return array
     */
    public function getInterfacesDescriptions() {
        $containerPath = self::INTCACHE_PATH;
        $containerMark = self::INTDESCRCACHE_EXT;
        $oltData = new OLTAttractor();
        $result = array();
        $allContainers = $this->getContainers($containerPath, $containerMark);
        if (!empty($allContainers)) {
            foreach ($allContainers as $eachOltId => $eachContainer) {
                $oltData->setOltId($eachOltId);
                $result[$eachOltId] = $oltData->readInterfacesDescriptions();
            }
        }
        return($result);
    }

    /**
     * Returns list of all ONUs MAC index as [onuMac]=>deviceId
     * 
     * @return array
     */
    public function getMacIndexAll() {
        $containerPath = self::MACDEVIDCACHE_PATH;
        $containerMark = self::MACDEVIDCACHE_EXT;
        return($this->getContainersContent($containerPath, $containerMark));
    }

    /**
     * Returns list of all ONUs MACs on OLTs as [onuMac]=>oltId
     * 
     * @return array
     */
    public function getONUonOLTAll() {
        $containerPath = self::ONUCACHE_PATH;
        $containerMark = self::ONUCACHE_EXT;
        $oltData = new OLTAttractor();
        $result = array();
        $allContainers = $this->getContainers($containerPath, $containerMark);
        if (!empty($allContainers)) {
            foreach ($allContainers as $eachOltId => $eachContainer) {
                $oltData->setOltId($eachOltId);
                $allOltOnus = $oltData->readOnuCache();
                if (!empty($allOltOnus)) {
                    foreach ($allOltOnus as $eachDevId => $eachOnuMac) {
                        $result[$eachOnuMac] = $eachOltId;
                    }
                }
            }
        }
        return($result);
    }

    /**
     * Returns list of all ONUs signals on OLTS as [oltId][onuMac/onuSerial]=>signal in db
     * 
     * @return array
     */
    public function getSignalsOLTAll() {
        $containerPath = self::SIGCACHE_PATH;
        $containerMark = self::SIGCACHE_EXT;
        $oltData = new OLTAttractor();
        $result = array();
        $allContainers = $this->getContainers($containerPath, $containerMark);
        if (!empty($allContainers)) {
            foreach ($allContainers as $eachOltId => $eachContainer) {
                $oltData->setOltId($eachOltId);
                $eachOltSignals = $oltData->readSignals();
                $result[$eachOltId] = $eachOltSignals;
            }
        }
        return($result);
    }

    /**
     * Returns per-OLT ONUs FDB cache as [oltId]=>fdbStruct (see readFdb)
     * 
     * @return array
     */
    public function getFdbOLTAll() {
        $containerPath = self::FDBCACHE_PATH;
        $containerMark = self::FDBCACHE_EXT;
        $oltData = new OLTAttractor();
        $result = array();
        $allContainers = $this->getContainers($containerPath, $containerMark);
        if (!empty($allContainers)) {
            foreach ($allContainers as $eachOltId => $eachContainer) {
                $oltData->setOltId($eachOltId);
                $eachOltFdb = $oltData->readFdb();
                $result[$eachOltId] = $eachOltFdb;
            }
        }
        return($result);
    }

    /**
     * Public methods to perform fast data availability checks without containers reading
     */

    /**
     * Checks is any distances data available?
     * 
     * @return bool
     */
    public function isDistancesAvailable() {
        $containerPath = self::DISTCACHE_PATH;
        $containerMark = self::DISTCACHE_EXT;
        return($this->checkContainersAvailable($containerPath, $containerMark));
    }

    /**
     * Checks is any interfaces data available?
     * 
     * @return bool
     */
    public function isInterfacesAvailable() {
        $containerPath = self::INTCACHE_PATH;
        $containerMark = self::INTCACHE_EXT;
        return($this->checkContainersAvailable($containerPath, $containerMark));
    }

    /**
     * Checks is any deregs data available?
     * 
     * @return bool
     */
    public function isDeregsAvailable() {
        $containerPath = self::DEREGCACHE_PATH;
        $containerMark = self::DEREGCACHE_EXT;
        return($this->checkContainersAvailable($containerPath, $containerMark));
    }

    /**
     * Checks is any interface descriptions data available?
     * 
     * @return bool
     */
    public function isInterfacesDescriptionsAvailable() {
        $containerPath = self::INTCACHE_PATH;
        $containerMark = self::INTDESCRCACHE_EXT;
        return($this->checkContainersAvailable($containerPath, $containerMark));
    }

    /**
     * Checks is any ONU FDB data available?
     * 
     * @return bool
     */
    public function isFdbAvailable() {
        $containerPath = self::FDBCACHE_PATH;
        $containerMark = self::FDBCACHE_EXT;
        return($this->checkContainersAvailable($containerPath, $containerMark));
    }

    /**
     * Checks is any ONU cache data available?
     * 
     * @return bool
     */
    public function isOnusAvailable() {
        $containerPath = self::ONUCACHE_PATH;
        $containerMark = self::ONUCACHE_EXT;
        return($this->checkContainersAvailable($containerPath, $containerMark));
    }

    /**
     * Checks is any ONU signals cache data available?
     * 
     * @return bool
     */
    public function isSignalsAvailable() {
        $containerPath = self::SIGCACHE_PATH;
        $containerMark = self::SIGCACHE_EXT;
        return($this->checkContainersAvailable($containerPath, $containerMark));
    }

    /**
     * Performs cleanup of all available cached data 
     * 
     * @return int
     */
    public function flushAllCacheData() {
        $allContainers = rcms_scandir(self::CACHE_ROOT_PATH, '*', 'dir');
        $result = 0;
        if (!empty($allContainers)) {
            foreach ($allContainers as $io => $each) {
                $containerPath = self::CACHE_ROOT_PATH . $each . '/';
                $containersList = rcms_scandir($containerPath);
                if (!empty($containersList)) {
                    foreach ($containersList as $index => $eachContainer)
                        if ($eachContainer != 'placeholder') {
                            rcms_delete_files($containerPath . $eachContainer);
                            $result++;
                        }
                }
            }
        }
        return($result);
    }

}
