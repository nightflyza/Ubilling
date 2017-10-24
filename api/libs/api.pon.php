<?php

class PONizer {

    /**
     * All available ONU devices
     *
     * @var array
     */
    protected $allOnu = array();

    /**
     * OLT models data as id=>model data array
     *
     * @var array
     */
    protected $allModelsData = array();

    /**
     * All available OLT devices
     *
     * @var array
     */
    protected $allOltDevices = array();

    /**
     * OLT devices snmp data as id=>snmp data array
     *
     * @var array
     */
    protected $allOltSnmp = array();

    /**
     * Available OLT models as id=>modelname + snmptemplate
     *
     * @var array
     */
    protected $allOltModels = array();

    /**
     * Contains available SNMP templates for OLT modelids
     *
     * @var array
     */
    protected $snmpTemplates = array();

    /**
     * Contains current ONU signal cache data as mac=>signal
     *
     * @var array
     */
    protected $signalCache = array();

    /**
     * Contains current ONU signal cache data as mac=>distance
     *
     * @var array
     */
    protected $distanceCache = array();

    /**
     * Contains ONU indexes cache as mac=>oltid
     *
     * @var array
     */
    protected $onuIndexCache = array();

    /**
     * Contains ONU indexes cache as mac=>interface
     *
     * @var array
     */
    protected $interfaceCache = array();

    /**
     * Contains FDB indexes cache as id=>mac
     *
     * @var array
     */
    protected $FDBCache = array();

    /**
     * System alter.ini config stored as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * SNMPHelper object instance
     *
     * @var array
     */
    protected $snmp = '';
    protected $sup = '';

    const SIGCACHE_PATH = 'exports/';
    const SIGCACHE_EXT = 'OLTSIGNALS';
    const DISTCACHE_PATH = 'exports/';
    const DISTCACHE_EXT = 'OLTDISTANCE';
    const ONUCACHE_PATH = 'exports/';
    const ONUCACHE_EXT = 'ONUINDEX';
    const INTCACHE_PATH = 'exports/';
    const INTCACHE_EXT = 'ONUINTERFACE';
    const FDBCACHE_PATH = 'exports/';
    const FDBCACHE_EXT = 'OLTFDB';
    const URL_ME = '?module=ponizer';
    const SNMPCACHE = false;
    const SNMPPORT = 161;
    const ONUSIG_PATH = 'content/documents/onusig/';

    public function __construct() {
        $this->loadAlter();
        $this->loadOltDevices();
        $this->loadOltModels();
        $this->loadSnmpTemplates();
        $this->initSNMP();
        $this->loadOnu();
        $this->loadModels();
        $this->sup = wf_tag('sup') . '*' . wf_tag('sup', true);
    }

    /**
     * Loads system alter.ini config into private data property
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
     * Loads all available devices set as OLT
     * 
     * @return void
     */
    protected function loadOltDevices() {
        $query = "SELECT `id`,`ip`,`location`,`snmp`,`modelid` from `switches` WHERE `desc` LIKE '%OLT%';";
        $raw = simple_queryall($query);
        if (!empty($raw)) {
            foreach ($raw as $io => $each) {
                $this->allOltDevices[$each['id']] = $each['ip'] . ' - ' . $each['location'];
                if (!empty($each['snmp'])) {
                    $this->allOltSnmp[$each['id']]['community'] = $each['snmp'];
                    $this->allOltSnmp[$each['id']]['modelid'] = $each['modelid'];
                    $this->allOltSnmp[$each['id']]['ip'] = $each['ip'];
                }
            }
        }
    }

    /**
     * Getter for allOltDevices array
     *
     * @return array
     */
    public function getAllOltDevices() {
        return $this->allOltDevices;
    }

    /**
     * Loads all available snmp models data into private data property
     * 
     * @return void
     */
    protected function loadOltModels() {
        $rawModels = zb_SwitchModelsGetAll();
        if (!empty($rawModels)) {
            foreach ($rawModels as $io => $each) {
                $this->allOltModels[$each['id']]['modelname'] = $each['modelname'];
                $this->allOltModels[$each['id']]['snmptemplate'] = $each['snmptemplate'];
            }
        }
    }

    /**
     * Performs snmp templates preprocessing for OLT devices
     * 
     * @return void
     */
    protected function loadSnmpTemplates() {
        if (!empty($this->allOltDevices)) {
            foreach ($this->allOltDevices as $oltId => $eachOltData) {
                if (isset($this->allOltSnmp[$oltId])) {
                    $oltModelid = $this->allOltSnmp[$oltId]['modelid'];
                    if ($oltModelid) {
                        if (isset($this->allOltModels[$oltModelid])) {
                            $templateFile = 'config/snmptemplates/' . $this->allOltModels[$oltModelid]['snmptemplate'];
                            if (file_exists($templateFile)) {
                                $this->snmpTemplates[$oltModelid] = rcms_parse_ini_file($templateFile, true);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Creates single instance of SNMPHelper object
     * 
     * @return void
     */
    protected function initSNMP() {
        $this->snmp = new SNMPHelper();
    }

    /**
     * Try to detect ONU id by assigned users login
     * 
     * @param string $login
     * @return int/bool
     */
    public function getOnuIdByUser($login) {
        $result = 0;
        if (!empty($this->allOnu)) {
            foreach ($this->allOnu as $io => $each) {
                if ($each['login'] == $login) {
                    $result = $each['id'];
                    break;
                }
            }
        }
        return ($result);
    }

    /**
     * Try get new ONU Array by assigned users login
     *
     * @param string $OltId
     * @return void
     */
    protected function getOnuArrayByOltID($OltId = '') {
        $result = array();
        if (!empty($this->allOnu) and ! empty($OltId)) {
            foreach ($this->allOnu as $io => $each) {
                if ($each['oltid'] == $OltId) {
                    $result[$io] = $each;
                }
            }
        }
        return ($result);
    }

    /**
     * Parses & stores in cache OLT ONU distances
     * 
     * @param int $oltid
     * @param array $distIndex
     * @param array $onuIndex
     * 
     * @return void
     */
    protected function distanceParseBd($oltid, $distIndex, $onuIndex) {
        $oltid = vf($oltid, 3);
        $distTmp = array();
        $onuTmp = array();
        $result = array();
        $curDate = curdatetime();

        //distance index preprocessing
        if ((!empty($distIndex)) AND ( !empty($onuIndex))) {
            foreach ($distIndex as $io => $eachdist) {
                $line = explode('=', $eachdist);
                //distance is present
                if (isset($line[1])) {
                    $distanceRaw = trim($line[1]); // distance
                    $devIndex = trim($line[0]); // device index


                    if ($distanceRaw == 0) {
                        // $distanceRaw = ''; //not sure about this
                    }
                    $distTmp[$devIndex] = $distanceRaw;
                }
            }

            //mac index preprocessing
            foreach ($onuIndex as $io => $eachmac) {
                $line = explode('=', $eachmac);
                //mac is present
                if (isset($line[1])) {
                    $macRaw = trim($line[1]); //mac address
                    $devIndex = trim($line[0]); //device index
                    $macRaw = str_replace(' ', ':', $macRaw);
                    $macRaw = strtolower($macRaw);
                    $onuTmp[$devIndex] = $macRaw;
                }
            }


            //storing results
            if (!empty($onuTmp)) {
                foreach ($onuTmp as $devId => $eachMac) {
                    if (isset($distTmp[$devId])) {
                        $distance = $distTmp[$devId];
                        $result[$eachMac] = $distance;
                    }
                }
                $result = serialize($result);
                file_put_contents(self::DISTCACHE_PATH . $oltid . '_' . self::DISTCACHE_EXT, $result);
                $onuTmp = serialize($onuTmp);
                file_put_contents(self::ONUCACHE_PATH . $oltid . '_' . self::ONUCACHE_EXT, $onuTmp);
            }
        }
    }

    /**
     * Parses & stores in cache OLT ONU interfaces
     *
     * @param int $oltid
     * @param array $intIndex
     * @param array $macIndex
     *
     * @return void
     */
    protected function interfaceParseBd($oltid, $intIndex, $macIndex) {
        $oltid = vf($oltid, 3);
        $intTmp = array();
        $macTmp = array();
        $result = array();

        //distance index preprocessing
        if ((!empty($intIndex)) AND ( !empty($macIndex))) {
            foreach ($intIndex as $io => $eachint) {
                $line = explode('=', $eachint);
                //distance is present
                if (isset($line[1])) {
                    $interfaceRaw = trim($line[1]); // distance
                    $devIndex = trim($line[0]); // device index

                    if ($interfaceRaw == 0) {
                        // $interfaceRaw = ''; //not sure about this
                    }
                    $intTmp[$devIndex] = $interfaceRaw;
                }
            }

            //mac index preprocessing
            foreach ($macIndex as $io => $eachmac) {
                $line = explode('=', $eachmac);
                //mac is present
                if (isset($line[1])) {
                    $macRaw = trim($line[1]); //mac address
                    $devIndex = trim($line[0]); //device index
                    $macRaw = str_replace(' ', ':', $macRaw);
                    $macRaw = strtolower($macRaw);
                    $macTmp[$devIndex] = $macRaw;
                }
            }

            //storing results
            if (!empty($macTmp)) {
                foreach ($macTmp as $devId => $eachMac) {
                    if (isset($intTmp[$devId])) {
                        $inteface = $intTmp[$devId];
                        $result[$eachMac] = $inteface;
                    }
                }
                $result = serialize($result);
                file_put_contents(self::INTCACHE_PATH . $oltid . '_' . self::INTCACHE_EXT, $result);
            }
        }
    }

    /**
     * Parses & stores in cache OLT ONU interfaces
     *
     * @param int $oltid
     * @param array $FDBIndex
     * @param array $macIndex
     * @param array $oltModelId
     *
     * @return void
     */
    protected function FDBParseBd($oltid, $FDBIndex, $macIndex, $oltModelId) {
        $oltid = vf($oltid, 3);
        $FDBTmp = array();
        $macTmp = array();
        $result = array();

        //fdb index preprocessing
        if ((!empty($FDBIndex)) AND ( !empty($macIndex))) {
            foreach ($FDBIndex as $io => $eachfdb) {
                if (preg_match('/' . $this->snmpTemplates[$oltModelId]['misc']['FDBVALUE'] . '/', $eachfdb)) {
                    $eachfdb = str_replace($this->snmpTemplates[$oltModelId]['misc']['FDBVALUE'], '', $eachfdb);
                    $line = explode('=', $eachfdb);
                    //fdb is present
                    if (isset($line[1])) {
                        $FDBRaw = trim($line[1]); // FDB
                        $devOID = trim($line[0]); // FDB last OID
                        $devline = explode('.', $devOID);
                        $devIndex = trim($devline[0]); // FDB index
                        $FDBvlan = trim($devline[1]); // Vlan
                        $FDBnum = trim($devline[7]); // Count number of MAC

                        $FDBRaw = str_replace(' ', ':', $FDBRaw);
                        $FDBRaw = strtolower($FDBRaw);

                        $FDBTmp[$devIndex][$FDBnum]['mac'] = $FDBRaw;
                        $FDBTmp[$devIndex][$FDBnum]['vlan'] = $FDBvlan;
                    }
                }
            }

            //mac index preprocessing
            foreach ($macIndex as $io => $eachmac) {
                $line = explode('=', $eachmac);
                //mac is present
                if (isset($line[1])) {
                    $macRaw = trim($line[1]); //mac address
                    $devIndex = trim($line[0]); //device index
                    $macRaw = str_replace(' ', ':', $macRaw);
                    $macRaw = strtolower($macRaw);
                    $macTmp[$devIndex] = $macRaw;
                }
            }

            //storing results
            if (!empty($macTmp)) {
                foreach ($macTmp as $devId => $eachMac) {
                    if (isset($FDBTmp[$devId])) {
                        $fdb = $FDBTmp[$devId];
                        $result[$eachMac] = $fdb;
                    }
                }
                $result = serialize($result);
                file_put_contents(self::FDBCACHE_PATH . $oltid . '_' . self::FDBCACHE_EXT, $result);
            }
        }
    }

    /**
     * Performs signal preprocessing for sig/mac index arrays and stores it into cache
     * 
     * @param int   $oltid
     * @param array $sigIndex
     * @param array $macIndex
     * @param array $snmpTemplate
     * 
     * @return void
     */
    protected function signalParseBd($oltid, $sigIndex, $macIndex, $snmpTemplate) {
        $oltid = vf($oltid, 3);
        $sigTmp = array();
        $macTmp = array();
        $result = array();
        $curDate = curdatetime();

        //signal index preprocessing
        if ((!empty($sigIndex)) AND ( !empty($macIndex))) {
            foreach ($sigIndex as $io => $eachsig) {
                $line = explode('=', $eachsig);
                //signal is present
                if (isset($line[1])) {
                    $signalRaw = trim($line[1]); // signal level
                    $devIndex = trim($line[0]); // device index
                    if ($signalRaw == $snmpTemplate['DOWNVALUE']) {
                        $signalRaw = 'Offline';
                    } else {
                        if ($snmpTemplate['OFFSETMODE'] == 'div') {
                            if ($snmpTemplate['OFFSET']) {
                                $signalRaw = $signalRaw / $snmpTemplate['OFFSET'];
                            }
                        }
                    }
                    $sigTmp[$devIndex] = $signalRaw;
                }
            }

            //mac index preprocessing
            foreach ($macIndex as $io => $eachmac) {
                $line = explode('=', $eachmac);
                //mac is present
                if (isset($line[1])) {
                    $macRaw = trim($line[1]); //mac address
                    $devIndex = trim($line[0]); //device index
                    $macRaw = str_replace(' ', ':', $macRaw);
                    $macRaw = strtolower($macRaw);
                    $macTmp[$devIndex] = $macRaw;
                }
            }

            //storing results
            if (!empty($macTmp)) {
                foreach ($macTmp as $devId => $eachMac) {
                    if (isset($sigTmp[$devId])) {
                        $signal = $sigTmp[$devId];
                        $result[$eachMac] = $signal;
                        //signal history filling
                        $historyFile = self::ONUSIG_PATH . md5($eachMac);
                        if ($signal == 'Offline') {
                            $signal = -9000; //over 9000 offline signal level :P
                        }
                        file_put_contents($historyFile, $curDate . ',' . $signal . "\n", FILE_APPEND);
                    }
                }
                $result = serialize($result);
                file_put_contents(self::SIGCACHE_PATH . $oltid . '_' . self::SIGCACHE_EXT, $result);
            }
        }
    }

    /**
     * Performs signal preprocessing for sig/mac index arrays and stores it into cache for ZTE OLT
     * 
     * @param int   $oltid
     * @param array $sigIndex
     * @param array $macIndex
     * @param array $snmpTemplate
     * 
     * @return void
     */
    protected function signalParseZte($oltid, $sigIndex, $macIndex, $snmpTemplate) {
        $oltid = vf($oltid, 3);
        $sigTmp = array();
        $macTmp = array();
        $result = array();
        $curDate = curdatetime();

        //signal index preprocessing
        if ((!empty($sigIndex)) AND ( !empty($macIndex))) {
            foreach ($sigIndex as $devIndex => $eachsig) {
                $signalRaw = $eachsig; // signal level

                if ($signalRaw == $snmpTemplate['DOWNVALUE']) {
                    $signalRaw = 'Offline';
                } else {
                    if ($snmpTemplate['OFFSETMODE'] == 'div') {
                        if ($snmpTemplate['OFFSET']) {
                            $signalRaw = $signalRaw / $snmpTemplate['OFFSET'];
                        }
                    }
                }
                $signalRaw = str_replace('"', '', $signalRaw);
                $sigTmp[$devIndex] = $signalRaw;
            }

            //mac index preprocessing
            foreach ($macIndex as $devIndex => $eachmac) {
                $macRaw = $eachmac; //mac address
                $macRaw = str_replace(' ', ':', $macRaw);
                $macRaw = strtolower($macRaw);
                $macTmp[$devIndex] = $macRaw;
            }

            //storing results
            if (!empty($macTmp)) {
                foreach ($macTmp as $devId => $eachMac) {
                    if (isset($sigTmp[$devId])) {
                        $signal = $sigTmp[$devId];
                        $result[$eachMac] = $signal;
                        //signal history filling
                        $historyFile = self::ONUSIG_PATH . md5($eachMac);
                        if ($signal == 'Offline') {
                            $signal = -9000; //over 9000 offline signal level :P
                        }

                        file_put_contents($historyFile, $curDate . ',' . $signal . "\n", FILE_APPEND);
                    }
                }

                $result = serialize($result);
                file_put_contents(self::SIGCACHE_PATH . $oltid . '_' . self::SIGCACHE_EXT, $result);
            }
        }
    }

    /**
     * Performs signal preprocessing for sig/sn index arrays and stores it into cache for ZTE OLT
     * 
     * @param int   $oltid
     * @param array $sigIndex
     * @param array $macIndex
     * @param array $snmpTemplate
     * 
     * @return void
     */
    protected function signalParseZteGpon($oltid, $sigIndex, $snIndex, $snmpTemplate) {
        $oltid = vf($oltid, 3);
        $sigTmp = array();
        $macTmp = array();
        $result = array();
        $curDate = curdatetime();

        //signal index preprocessing
        if ((!empty($sigIndex)) AND ( !empty($snIndex))) {
            foreach ($sigIndex as $devIndex => $eachsig) {
                $signalRaw = $eachsig; // signal level

                if ($signalRaw == $snmpTemplate['DOWNVALUE']) {
                    $signalRaw = 'Offline';
                } else {
                    if ($snmpTemplate['OFFSETMODE'] == 'div') {
                        if ($snmpTemplate['OFFSET']) {
                            $signalRaw = $signalRaw / $snmpTemplate['OFFSET'];
                        }
                    }
                }
                $signalRaw = str_replace('"', '', $signalRaw);
                $sigTmp[$devIndex] = $signalRaw;
            }

            //mac index preprocessing
            foreach ($snIndex as $devIndex => $eachSn) {
                $snRaw = $eachSn; //serial
                $snRaw = str_replace(' ', ':', $snRaw);
                $snRaw = strtoupper($snRaw);
                $snTmp[$devIndex] = $snRaw;
            }

            //storing results
            if (!empty($snTmp)) {
                foreach ($snTmp as $devId => $eachSn) {
                    if (isset($sigTmp[$devId])) {
                        $signal = $sigTmp[$devId];
                        $result[$eachSn] = $signal;
                        //signal history filling
                        $historyFile = self::ONUSIG_PATH . md5($eachSn);
                        if ($signal == 'Offline') {
                            $signal = -9000; //over 9000 offline signal level :P
                        }

                        file_put_contents($historyFile, $curDate . ',' . $signal . "\n", FILE_APPEND);
                    }
                }

                $result = serialize($result);
                file_put_contents(self::SIGCACHE_PATH . $oltid . '_' . self::SIGCACHE_EXT, $result);
            }
        }
    }

    /**
     * Performs  OLT device polling with snmp
     * 
     * @param int $oltid
     * 
     * @return void
     */
    protected function pollOltSignal($oltid) {
        $oltid = vf($oltid, 3);
        if (isset($this->allOltDevices[$oltid])) {
            if (isset($this->allOltSnmp[$oltid])) {
                $oltCommunity = $this->allOltSnmp[$oltid]['community'];
                $oltModelId = $this->allOltSnmp[$oltid]['modelid'];
                $oltIp = $this->allOltSnmp[$oltid]['ip'];
                if (isset($this->snmpTemplates[$oltModelId])) {
                    if (isset($this->snmpTemplates[$oltModelId]['signal'])) {
                        // BDCOM/Eltex devices polling
                        if ($this->snmpTemplates[$oltModelId]['signal']['SIGNALMODE'] == 'BDCOM') {
                            $sigIndexOID = $this->snmpTemplates[$oltModelId]['signal']['SIGINDEX'];
                            $sigIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $sigIndexOID, self::SNMPCACHE);
                            $sigIndex = str_replace($sigIndexOID . '.', '', $sigIndex);
                            $sigIndex = str_replace($this->snmpTemplates[$oltModelId]['signal']['SIGVALUE'], '', $sigIndex);
                            $sigIndex = explodeRows($sigIndex);

                            $macIndexOID = $this->snmpTemplates[$oltModelId]['signal']['MACINDEX'];
                            $macIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $macIndexOID, self::SNMPCACHE);

                            $macIndex = str_replace($macIndexOID . '.', '', $macIndex);
                            $macIndex = str_replace($this->snmpTemplates[$oltModelId]['signal']['MACVALUE'], '', $macIndex);
                            $macIndex = explodeRows($macIndex);
                            $this->signalParseBd($oltid, $sigIndex, $macIndex, $this->snmpTemplates[$oltModelId]['signal']);

                            //ONU distance polling for bdcom devices
                            if (isset($this->snmpTemplates[$oltModelId]['misc'])) {
                                if (isset($this->snmpTemplates[$oltModelId]['misc']['DISTINDEX'])) {
                                    if (!empty($this->snmpTemplates[$oltModelId]['misc']['DISTINDEX'])) {
                                        $distIndexOid = $this->snmpTemplates[$oltModelId]['misc']['DISTINDEX'];
                                        $distIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $distIndexOid, self::SNMPCACHE);
                                        $distIndex = str_replace($distIndexOid . '.', '', $distIndex);
                                        $distIndex = str_replace($this->snmpTemplates[$oltModelId]['misc']['DISTVALUE'], '', $distIndex);
                                        $distIndex = explodeRows($distIndex);

                                        $onuIndexOid = $this->snmpTemplates[$oltModelId]['misc']['ONUINDEX'];
                                        $onuIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $onuIndexOid, self::SNMPCACHE);
                                        $onuIndex = str_replace($onuIndexOid . '.', '', $onuIndex);
                                        $onuIndex = str_replace($this->snmpTemplates[$oltModelId]['misc']['ONUVALUE'], '', $onuIndex);
                                        $onuIndex = explodeRows($onuIndex);
                                        $this->distanceParseBd($oltid, $distIndex, $onuIndex);

                                        $intIndexOid = $this->snmpTemplates[$oltModelId]['misc']['INTERFACEINDEX'];
                                        $intIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $intIndexOid, self::SNMPCACHE);
                                        $intIndex = str_replace($intIndexOid . '.', '', $intIndex);
                                        $intIndex = str_replace($this->snmpTemplates[$oltModelId]['misc']['INTERFACEVALUE'], '', $intIndex);
                                        $intIndex = explodeRows($intIndex);
                                        $this->interfaceParseBd($oltid, $intIndex, $macIndex);

                                        $FDBIndexOid = $this->snmpTemplates[$oltModelId]['misc']['FDBINDEX'];
                                        $FDBIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $FDBIndexOid, self::SNMPCACHE);
                                        $FDBIndex = str_replace($FDBIndexOid . '.', '', $FDBIndex);
                                        $FDBIndex = explodeRows($FDBIndex);
                                        $this->FDBParseBd($oltid, $FDBIndex, $macIndex, $oltModelId);
                                    }
                                }
                            }
                        }
                        //ZTE devices polling
                        if ($this->snmpTemplates[$oltModelId]['signal']['SIGNALMODE'] == 'ZTE') {
                            $macIndexOID = $this->snmpTemplates[$oltModelId]['signal']['MACINDEX'];
                            $macIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $macIndexOID, self::SNMPCACHE);
                            $macIndex = str_replace($this->snmpTemplates[$oltModelId]['signal']['MACVALUE'], '', $macIndex);
                            $macIndex = str_replace($macIndexOID . '.', '', $macIndex);
                            $macIndex = trim($macIndex);
                            $macIndex = explodeRows($macIndex);
                            $macIndexTmp = array();
                            if (!empty($macIndex)) {
                                foreach ($macIndex as $rawIo => $rawEach) {
                                    $rawEach = trim($rawEach);
                                    $explodeIndex = explode('=', $rawEach);
                                    if (!empty($explodeIndex)) {
                                        $naturalIndex = trim($explodeIndex[0]);
                                        $naturalMac = trim($explodeIndex[1]);
                                        $macIndexTmp[$naturalIndex] = $naturalMac;
                                    }
                                }
                            }


                            $sigIndexOID = $this->snmpTemplates[$oltModelId]['signal']['SIGINDEX'];
                            $sigIndexTmp = array();
                            if (!empty($macIndexTmp)) {
                                foreach ($macIndexTmp as $ioIndex => $eachMac) {
                                    $tmpSig = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $sigIndexOID . $ioIndex, self::SNMPCACHE);
                                    $sigIndex = str_replace($sigIndexOID . '.', '', $tmpSig);
                                    $sigIndex = str_replace($this->snmpTemplates[$oltModelId]['signal']['SIGVALUE'], '', $sigIndex);
                                    $sigIndex = str_replace($this->snmpTemplates[$oltModelId]['signal']['SIGINDEX'], '', $sigIndex);
                                    $explodeSig = explode('=', $sigIndex);
                                    $naturalIndex = trim($explodeSig[0]);
                                    if (isset($explodeSig[1])) {
                                        $naturalSig = trim($explodeSig[1]);
                                        $sigIndexTmp[$naturalIndex] = $naturalSig;
                                    }
                                }
                            }
                            $this->signalParseZte($oltid, $sigIndexTmp, $macIndexTmp, $this->snmpTemplates[$oltModelId]['signal']);
                        }
                        if ($this->snmpTemplates[$oltModelId]['signal']['SIGNALMODE'] == 'ZTE_GPON') {
                            $snIndexOID = $this->snmpTemplates[$oltModelId]['signal']['SNINDEX'];
                            $snIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $snIndexOID, self::SNMPCACHE);
                            $snIndex = str_replace($this->snmpTemplates[$oltModelId]['signal']['SNVALUE'], '', $snIndex);
                            $snIndex = str_replace($snIndexOID . '.', '', $snIndex);
                            $snIndex = trim($snIndex);
                            $snIndex = explodeRows($snIndex);
                            $snIndexTmp = array();
                            if (!empty($snIndex)) {
                                foreach ($snIndex as $rawIo => $rawEach) {
                                    $rawEach = trim($rawEach);
                                    $explodeIndex = explode('=', $rawEach);
                                    if (!empty($explodeIndex)) {
                                        $naturalIndex = trim($explodeIndex[0]);
                                        $tmpSn = trim($explodeIndex[1]);
                                        $tmpSn = explode(" ", $tmpSn);
                                        $naturalSn = $this->HexToString($tmpSn[0]);
                                        $naturalSn.= $this->HexToString($tmpSn[1]);
                                        $naturalSn.= $this->HexToString($tmpSn[2]);
                                        $naturalSn.= $this->HexToString($tmpSn[3]);
                                        $naturalSn.= $tmpSn[4] . $tmpSn[5] . $tmpSn[6] . $tmpSn[7];
                                        $snIndexTmp[$naturalIndex] = $naturalSn;
                                    }
                                }
                            }


                            $sigIndexOID = $this->snmpTemplates[$oltModelId]['signal']['SIGINDEX'];
                            $sigIndexTmp = array();
                            if (!empty($snIndexTmp)) {
                                foreach ($snIndexTmp as $ioIndex => $eachSn) {
                                    $tmpSig = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $sigIndexOID . $ioIndex, self::SNMPCACHE);
                                    $sigIndex = str_replace($sigIndexOID . '.', '', $tmpSig);
                                    $sigIndex = str_replace($this->snmpTemplates[$oltModelId]['signal']['SIGVALUE'], '', $sigIndex);
                                    $sigIndex = str_replace($this->snmpTemplates[$oltModelId]['signal']['SIGINDEX'], '', $sigIndex);
                                    $explodeSig = explode('=', $sigIndex);
                                    $naturalIndex = trim($explodeSig[0]);
                                    if (isset($explodeSig[1])) {
                                        $naturalSig = trim($explodeSig[1]);
                                        $sigIndexTmp[$naturalIndex] = $naturalSig;
                                    }
                                }
                            }
                            $this->signalParseZteGpon($oltid, $sigIndexTmp, $snIndexTmp, $this->snmpTemplates[$oltModelId]['signal']);
                        }
                    }
                }
            }
        }
    }

    /**
     * Converts hex to string value
     * 
     * @param string $hex
     * @return string
     */
    protected function HexToString($hex) {
        return pack('H*', $hex);
    }

    /**
     * Performs available OLT devices polling. Use only in remote API.
     * 
     * @param bool $quiet
     * 
     * @return void
     */
    public function oltDevicesPolling($quiet = false) {
        if (!empty($this->allOltDevices)) {
            foreach ($this->allOltDevices as $oltid => $each) {
                if (!$quiet) {
                    print('POLLING:' . $oltid . ' ' . $each . "\n");
                }
                $this->pollOltSignal($oltid);
            }
        }
    }

    /**
     * Loads avaliable ONUs from database into private data property
     * 
     * @return void
     */
    protected function loadOnu() {
        $query = "SELECT * from `pononu`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allOnu[$each['id']] = $each;
            }
        }
    }

    /**
     * Returns int for ONU has or has not some of subscribers login assignment
     * 0 - has no assignment
     * 1 - has assignment, but login does not exist
     * 2 - has assignment
     *
     * @param int $onuid
     *
     * @return int
     */
    public function checkONUAssignment($onuid) {
        $result = 0;
        $tLogin = '';

        if (empty($onuid))
            return $result;

        $query = "SELECT * from `pononu` WHERE `id`='" . $onuid . "'";
        $all = simple_queryall($query);
        if (!empty($all)) {
            $tLogin = $all[0]['login'];

            if (!empty($tLogin)) {
                $query = "SELECT * from `users` WHERE `login`='" . $tLogin . "'";
                $LoginRec = simple_queryall($query);

                empty($LoginRec) ? $result = 1 : $result = 2;
            }
        }

        return $result;
    }

    /**
     * Getter for loaded ONU devices
     * 
     * @return array
     */
    public function getAllOnu() {
        return ($this->allOnu);
    }

    /**
     * Returns ONU ID by ONU MAC or 0 if not found
     *
     * @param string $mac
     *
     * @return int
     */
    public function getONUIDByMAC($mac) {
        $mac = strtolower($mac);
        $ONUID = 0;

        if (!empty($this->allOnu)) {
            foreach ($this->allOnu as $io => $each) {
                if ($each['mac'] == $mac) {
                    $ONUID = $each['id'];
                }
            }
        }

        return $ONUID;
    }

    /**
     * Loads available device models from database
     * 
     * @return void
     */
    protected function loadModels() {
        $tmpModels = zb_SwitchModelsGetAll();
        if (!empty($tmpModels)) {
            foreach ($tmpModels as $io => $each) {
                $this->allModelsData[$each['id']] = $each;
            }
        }
    }

    /**
     * Getter for allModelsData array
     *
     * @return array
     */
    public function getAllModelsData() {
        return $this->allModelsData;
    }

    /**
     * Returns model name by its id
     * 
     * @param int $id
     * @return string
     */
    protected function getModelName($id) {
        $result = '';
        if (isset($this->allModelsData[$id])) {
            $result = $this->allModelsData[$id]['modelname'];
        }
        return ($result);
    }

    /**
     * Check ONU MAC address unique or not?
     * 
     * @param string $mac
     * @return bool
     */
    public function checkMacUnique($mac) {
        $mac = strtolower($mac);
        $result = true;
        if (!empty($this->allOnu)) {
            foreach ($this->allOnu as $io => $each) {
                if ($each['mac'] == $mac) {
                    $result = false;
                }
            }
        }
        return ($result);
    }

    /**
     * Creates new ONU in database and returns it Id or 0 if action fails
     * 
     * @param int $onumodelid
     * @param int $oltid
     * @param string $ip
     * @param string $mac
     * @param string $serial
     * @param string $login
     * 
     * @return int
     */
    public function onuCreate($onumodelid, $oltid, $ip, $mac, $serial, $login) {
        $mac = strtolower($mac);
        $mac = trim($mac);
        $onumodelid = vf($onumodelid, 3);
        $oltid = vf($oltid, 3);
        $ip = mysql_real_escape_string($ip);
        $macRaw = $mac;
        $mac = mysql_real_escape_string($mac);
        $serial = mysql_real_escape_string($serial);
        $login = mysql_real_escape_string($login);
        $login = trim($login);
        $result = 0;
        if (!empty($mac)) {
            if (check_mac_format($mac)) {
                if ($this->checkMacUnique($mac)) {
                    $query = "INSERT INTO `pononu` (`id`, `onumodelid`, `oltid`, `ip`, `mac`, `serial`, `login`) "
                            . "VALUES (NULL, '" . $onumodelid . "', '" . $oltid . "', '" . $ip . "', '" . $mac . "', '" . $serial . "', '" . $login . "');";
                    nr_query($query);
                    $result = simple_get_lastid('pononu');
                    log_register('PON CREATE ONU [' . $result . '] MAC `' . $macRaw . '`');
                } else {
                    log_register('PON MACDUPLICATE TRY `' . $macRaw . '`');
                }
            } else {
                log_register('PON MACINVALID TRY `' . $macRaw . '`');
            }
        }
        return ($result);
    }

    /**
     * Saves ONU changes into database
     * 
     * @param int $onuId
     * @param int $onumodelid
     * @param int $oltid
     * @param string $ip
     * @param string $mac
     * @param string $serial
     * @param string $login
     * 
     * @return void
     */
    public function onuSave($onuId, $onumodelid, $oltid, $ip, $mac, $serial, $login) {
        $mac = strtolower($mac);
        $mac = trim($mac);
        $onuId = vf($onuId, 3);
        $onumodelid = vf($onumodelid, 3);
        $oltid = vf($oltid, 3);
        $ip = mysql_real_escape_string($ip);
        $mac = mysql_real_escape_string($mac);
        $serial = mysql_real_escape_string($serial);
        $login = mysql_real_escape_string($login);
        $login = trim($login);
        $where = " WHERE `id`='" . $onuId . "';";
        simple_update_field('pononu', 'onumodelid', $onumodelid, $where);
        simple_update_field('pononu', 'oltid', $oltid, $where);
        simple_update_field('pononu', 'ip', $ip, $where);
        if (!empty($mac)) {
            if (check_mac_format($mac)) {
                if ($this->checkMacUnique($mac)) {
                    simple_update_field('pononu', 'mac', $mac, $where);
                } else {
                    log_register('PON MACDUPLICATE TRY `' . $mac . '`');
                }
            } else {
                log_register('PON MACINVALID TRY `' . $mac . '`');
            }
        } else {
            log_register('PON MACEMPTY TRY `' . $mac . '`');
        }
        simple_update_field('pononu', 'serial', $serial, $where);
        simple_update_field('pononu', 'login', $login, $where);
        log_register('PON EDIT ONU [' . $onuId . ']');
    }

    /**
     * Assigns exinsting ONU with some login
     * 
     * @param int $onuid
     * @param string $login
     * 
     * @return void
     */
    public function onuAssign($onuid, $login) {
        $onuid = vf($onuid, 3);
        if (isset($this->allOnu[$onuid])) {
            simple_update_field('pononu', 'login', $login, "WHERE `id`='" . $onuid . "'");
            log_register('PON ASSIGN ONU [' . $onuid . '] WITH (' . $login . ')');
        } else {
            log_register('PON ASSIGN ONU [' . $onuid . '] FAILED');
        }
    }

    /**
     * Deletes onu from database by its ID
     * 
     * @param int $onuId
     */
    public function onuDelete($onuId) {
        $onuId = vf($onuId, 3);
        $query = "DELETE from `pononu` WHERE `id`='" . $onuId . "';";
        nr_query($query);
        log_register('PON DELETE ONU [' . $onuId . ']');
    }

    /**
     * Returns ONU creation form
     * 
     * @return string
     */
    protected function onuCreateForm() {
        $models = array();
        if (!empty($this->allModelsData)) {
            foreach ($this->allModelsData as $io => $each) {
                if (@$this->altCfg['ONUMODELS_FILTER']) {
                    if (ispos($each['modelname'], 'ONU')) {
                        $models[$each['id']] = $each['modelname'];
                    }
                } else {
                    $models[$each['id']] = $each['modelname'];
                }
            }
        }

        $inputs = wf_HiddenInput('createnewonu', 'true');
        $inputs.= wf_Selector('newoltid', $this->allOltDevices, __('OLT device') . $this->sup, '', true);
        $inputs.= wf_Selector('newonumodelid', $models, __('ONU model') . $this->sup, '', true);
        if (@$this->altCfg['PON_ONUIPASIF']) {
            $ipFieldLabel = __('Interface');
        } else {
            $ipFieldLabel = __('IP');
        }
        $inputs.= wf_TextInput('newip', $ipFieldLabel, '', true, 20);
        $inputs.= wf_TextInput('newmac', __('MAC') . $this->sup, '', true, 20);
        $inputs.= wf_TextInput('newserial', __('Serial number'), '', true, 20);
        $inputs.= wf_TextInput('newlogin', __('Login'), '', true, 20);
        $inputs.= wf_Submit(__('Create'));

        $result = wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Returns ONU fast registration form
     * 
     * @param int $oltId
     * @param string $onuMac
     * 
     * @return string
     */
    public function onuRegisterForm($oltId, $onuMac) {
        $models = array();
        if (!empty($this->allModelsData)) {
            foreach ($this->allModelsData as $io => $each) {
                if (@$this->altCfg['ONUMODELS_FILTER']) {
                    if (ispos($each['modelname'], 'ONU')) {
                        $models[$each['id']] = $each['modelname'];
                    }
                } else {
                    $models[$each['id']] = $each['modelname'];
                }
            }
        }

        $inputs = wf_HiddenInput('createnewonu', 'true');
        $inputs.= wf_Selector('newoltid', $this->allOltDevices, __('OLT device') . $this->sup, $oltId, true);
        $inputs.= wf_Selector('newonumodelid', $models, __('ONU model') . $this->sup, '', true);
        $inputs.= wf_TextInput('newip', __('IP'), '', true, 20);
        $inputs.= wf_TextInput('newmac', __('MAC') . $this->sup, $onuMac, true, 20);
        $inputs.= wf_TextInput('newserial', __('Serial number'), '', true, 20);
        $inputs.= wf_TextInput('newlogin', __('Login'), '', true, 20);
        $inputs.= wf_Submit(__('Create'));
        $result = wf_Form(self::URL_ME, 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * returns vendor by MAC search control if this enabled in config
     * 
     * @return string
     */
    protected function getSearchmacControl($mac) {
        $result = '';
        if ($this->altCfg['MACVEN_ENABLED']) {
            if (!empty($mac)) {
                $vendorframe = wf_tag('iframe', false, '', 'src="?module=macvendor&mac=' . $mac . '" width="360" height="160" frameborder="0"');
                $vendorframe.= wf_tag('iframe', true);
                $result = wf_modalAuto(wf_img('skins/macven.gif', __('Device vendor')), __('Device vendor'), $vendorframe, '');
            }
        }
        return ($result);
    }

    /**
     * Renders ONU assigning form
     * 
     * @param string $login
     * @return string
     */
    public function onuAssignForm($login) {
        $result = '';
        $params = array();

        $allRealnames = zb_UserGetAllRealnames();
        $allAddress = zb_AddressGetFulladdresslistCached();
        @$userAddress = $allAddress[$login];
        @$userRealname = $allRealnames[$login];

        if (!empty($this->allOnu)) {
            foreach ($this->allOnu as $io => $each) {
                if (empty($each['login'])) {
                    $onuLabel = (empty($each['ip'])) ? $each['mac'] : $each['mac'] . ' - ' . $each['ip'];
                    $params[$each['id']] = $onuLabel;
                }
            }
        }

        //user data
        $cells = wf_TableCell(__('Real Name'), '30%', 'row2');
        $cells.= wf_TableCell($userRealname);
        $rows = wf_TableRow($cells, 'row3');
        $cells = wf_TableCell(__('Full address'), '30%', 'row2');
        $cells.= wf_TableCell($userAddress);
        $rows.= wf_TableRow($cells, 'row3');
        $result.= wf_TableBody($rows, '100%', 0, '');
        $result.= wf_delimiter();

        $inputs = wf_HiddenInput('assignonulogin', $login);
        $inputs.= wf_Selector('assignonuid', $params, __('ONU'), '', false);
        $inputs.= wf_Submit(__('Save'));
        $result.= wf_Form('', 'POST', $inputs, 'glamour');

        $result.= wf_CleanDiv();
        $result.= wf_delimiter();
        $result.= web_UserControls($login);
        return ($result);
    }

    /**
     * Returns ONU edit form
     * 
     * @param int $onuId
     * 
     * @return string
     */
    public function onuEditForm($onuId) {
        $onuId = vf($onuId, 3);
        $result = '';
        if (isset($this->allOnu[$onuId])) {
            $messages = new UbillingMessageHelper();

            $models = array();
            if (!empty($this->allModelsData)) {
                foreach ($this->allModelsData as $io => $each) {
                    if (@$this->altCfg['ONUMODELS_FILTER']) {
                        if (ispos($each['modelname'], 'ONU')) {
                            $models[$each['id']] = $each['modelname'];
                        }
                    } else {
                        $models[$each['id']] = $each['modelname'];
                    }
                }
            }

            $inputs = wf_HiddenInput('editonu', $onuId);
            $inputs.= wf_Selector('editoltid', $this->allOltDevices, __('OLT device') . $this->sup, $this->allOnu[$onuId]['oltid'], true);
            $inputs.= wf_Selector('editonumodelid', $models, __('ONU model') . $this->sup, $this->allOnu[$onuId]['onumodelid'], true);
            if (@$this->altCfg['PON_ONUIPASIF']) {
                $ipFieldLabel = __('Interface');
            } else {
                $ipFieldLabel = __('IP');
            }
            $inputs.= wf_TextInput('editip', $ipFieldLabel, $this->allOnu[$onuId]['ip'], true, 20);
            $inputs.= wf_TextInput('editmac', __('MAC') . $this->sup . ' ' . $this->getSearchmacControl($this->allOnu[$onuId]['mac']), $this->allOnu[$onuId]['mac'], true, 20);
            $inputs.= wf_TextInput('editserial', __('Serial number'), $this->allOnu[$onuId]['serial'], true, 20);
            $inputs.= wf_TextInput('editlogin', __('Login'), $this->allOnu[$onuId]['login'], true, 20);
            $inputs.= wf_Submit(__('Save'));


            $result = wf_Form('', 'POST', $inputs, 'glamour');
            $result.= wf_CleanDiv();
            $result.= wf_delimiter();

            $result.= wf_BackLink(self::URL_ME);
            if (!empty($this->allOnu[$onuId]['login'])) {
                $result.= wf_Link('?module=userprofile&username=' . $this->allOnu[$onuId]['login'], wf_img('skins/icon_user.gif') . ' ' . __('User profile'), false, 'ubButton');
            }
            $result.= wf_JSAlertStyled(self::URL_ME . '&deleteonu=' . $onuId, web_delete_icon() . ' ' . __('Delete'), $messages->getDeleteAlert(), 'ubButton');
        } else {
            $result = wf_tag('div', false, 'alert_error') . __('Strange exeption') . ': ONUID_NOT_EXISTS' . wf_tag('div', true);
        }

        //Signal history chart
        //$result.=$this->onuSignalHistory($onuId);
        //additional comments handling
        if ($this->altCfg['ADCOMMENTS_ENABLED']) {
            $adcomments = new ADcomments('PONONU');
            $result.=wf_delimiter();
            $result.=wf_tag('h3') . __('Additional comments') . wf_tag('h3', true);
            $result.=$adcomments->renderComments($onuId);
        }

        return ($result);
    }

    /**
     * Renders ONU signal history chart
     * 
     * @param int $onuId
     * @return string
     */
    protected function onuSignalHistory($onuId) {
        global $ubillingConfig;
        $billCfg = $ubillingConfig->getBilling();
        $onuId = vf($onuId, 3);
        $result = '';
        if (isset($this->allOnu[$onuId])) {

            //not empty MAC
            if ($this->allOnu[$onuId]['mac']) {
                if (file_exists(self::ONUSIG_PATH . md5($this->allOnu[$onuId]['mac']))) {
                    $historyKey = self::ONUSIG_PATH . md5($this->allOnu[$onuId]['mac']);
                } elseif (file_exists(self::ONUSIG_PATH . md5($this->allOnu[$onuId]['serial']))) {
                    $historyKey = self::ONUSIG_PATH . md5($this->allOnu[$onuId]['serial']);
                } else {
                    $historyKey = '';
                }
                if (!empty($historyKey)) {
                    $curdate = curdate();
                    $curmonth = curmonth() . '-';
                    $getMonthDataCmd = $billCfg['CAT'] . ' ' . $historyKey . ' | ' . $billCfg['GREP'] . ' ' . $curmonth;
                    $rawData = shell_exec($getMonthDataCmd);
                    //commented due performance issues with 1 minute OLT polling.
                    //$rawData = file_get_contents($historyKey);
                    $result.=wf_delimiter();
                    //$result.= wf_tag('h2') . __('ONU signal history') . wf_tag('h2', true);
                    //current day signal levels
                    $todaySignal = '';

                    if (!empty($rawData)) {
                        $todayTmp = explodeRows($rawData);
                        if (!empty($todayTmp)) {
                            foreach ($todayTmp as $io => $each) {
                                if (ispos($each, $curdate)) {
                                    $todaySignal.=$each . "\n";
                                }
                            }
                        }
                    }
                    $result.= __('Today');
                    $result.= wf_tag('div', false, '', '');
                    $result.= wf_Graph($todaySignal, '800', '300', false);
                    $result.= wf_tag('div', true);
                    $result.= wf_tag('br');

                    //current month signal levels
                    $monthSignal = '';
                    $curmonth = curmonth();
                    if (!empty($rawData)) {
                        $monthTmp = explodeRows($rawData);
                        if (!empty($monthTmp)) {
                            foreach ($monthTmp as $io => $each) {
                                if (ispos($each, $curmonth)) {
                                    $monthSignal.=$each . "\n";
                                }
                            }
                        }
                    }
                    $result.= __('Month');
                    $result.= wf_tag('div', false, '', '');
                    $result.= wf_Graph($monthSignal, '800', '300', false);
                    $result.= wf_tag('div', true);
                    $result.= wf_tag('br');

                    //all time signal history
                    $result.= __('All time');
                    $result.= wf_tag('div', false, '', '');
                    $result.= wf_GraphCSV($historyKey, '800', '300', false);
                    $result.= wf_tag('div', true);
                }
            }
        }
        return ($result);
    }

    /**
     * Returns default list controls
     * 
     * @return string
     */
    public function controls() {
        $result = '';
        if (!wf_CheckGet(array('unknownonulist'))) {
            $result.=wf_modalAuto(wf_img('skins/add_icon.png') . ' ' . __('Register new ONU'), __('Create') . ' ' . __('ONU'), $this->onuCreateForm(), 'ubButton') . ' ';
            $availOnuCache = rcms_scandir(self::ONUCACHE_PATH, '*_' . self::ONUCACHE_EXT);
            $result.= wf_Link(self::URL_ME . '&forcepoll=true', wf_img('skins/refresh.gif') . ' ' . __('Force query'), false, 'ubButton');
            if (!empty($availOnuCache)) {
                $result.=wf_Link(self::URL_ME . '&unknownonulist=true', wf_img('skins/question.png') . ' ' . __('Unknown ONU'), false, 'ubButton');
            }

            $availOnuFdbCache = rcms_scandir(self::FDBCACHE_PATH, '*_' . self::FDBCACHE_EXT);
            if (!empty($availOnuFdbCache)) {
                $result.=wf_Link(self::URL_ME . '&fdbcachelist=true', wf_img('skins/fdbmacsearch.png') . ' ' . __('Current FDB cache'), false, 'ubButton');
            }
        } else {
            $result.=wf_BackLink(self::URL_ME);
            $result.= wf_Link(self::URL_ME . '&forcepoll=true&uol=true', wf_img('skins/refresh.gif') . ' ' . __('Force query'), false, 'ubButton');
        }
        $result.=wf_delimiter();
        return ($result);
    }

    /**
     * Returns ONU signal history chart
     * 
     * @param int $onuId
     * @return string
     */
    public function loadonuSignalHistory($onuId) {
        $result = '';
        $result.= show_window(__('ONU signal history'), $this->onuSignalHistory($onuId));
        return ($result);
    }

    /**
     * Renders available ONU JQDT list container
     * 
     * @return string
     */
    public function renderOnuList() {
        $distCacheAvail = rcms_scandir(self::DISTCACHE_PATH, '*_' . self::DISTCACHE_EXT);
        $intCacheAvail = rcms_scandir(self::INTCACHE_PATH, '*_' . self::INTCACHE_EXT);

        $distCacheAvail = !empty($distCacheAvail) ? true : false;
        $intCacheAvail = !empty($intCacheAvail) ? true : false;

        $columns = array('ID');

        if ($intCacheAvail) {
            $columns[] = __('Interface');
        }

        $columns[] = 'Model';
        if (@$this->altCfg['PON_ONUIPASIF']) {
            $columns[] = 'Interface';
        } else {
            $columns[] = 'IP';
        }
        $columns[] = 'MAC';
        $columns[] = 'Signal';

        if ($distCacheAvail) {
            $columns[] = __('Distance') . ' (' . __('m') . ')';
        }

        $columns[] = 'Address';
        $columns[] = 'Real Name';
        $columns[] = 'Tariff';
        $columns[] = 'Actions';

        $opts = '"order": [[ 0, "desc" ]]';

        $result = '';
        foreach ($this->allOltDevices as $oltId => $eachOltData) {
            $result .= show_window(__(@$eachOltData), wf_JqDtLoader($columns, '?module=ponizer&ajaxonu=true&oltid=' . $oltId . '', false, 'ONU', 100, $opts));
        }
        return ($result);
    }

    /**
     * Renders unknown ONU list container
     * 
     * @return string
     */
    public function renderUnknowOnuList() {
        $result = '';
        $columns = array('OLT', 'MAC', 'Actions');
        $opts = '"order": [[ 0, "desc" ]]';
        $result = wf_JqDtLoader($columns, self::URL_ME . '&ajaxunknownonu=true', false, 'ONU', 100, $opts);
        return ($result);
    }

    /**
     * Returns current FDB cache list container with controls
     * 
     * @return string
     */
    public function renderOnuFdbCache() {
        $result = wf_BackLink(self::URL_ME);
        $result.=wf_delimiter();
        $columns = array('OLT', 'ONU', 'ID', 'Vlan', 'MAC', 'Address', 'Real Name', 'Tariff');
        $opts = '"order": [[ 0, "desc" ]]';
        $result.= wf_JqDtLoader($columns, self::URL_ME . '&fdbcachelist=true&ajaxfdblist=true', false, 'ONU', 100, $opts);
        return ($result);
    }

    /**
     * Renders OLT FDB list container
     * 
     * @return string
     */
    public function renderOltFdbList($onuid = '') {
        $result = '';
        $columns = array('ID', 'Vlan', 'MAC', 'Address', 'Real Name', 'Tariff');
        $opts = '"order": [[ 0, "desc" ]]';
        $result = wf_JqDtLoader($columns, self::URL_ME . '&ajaxoltfdb=true&onuid=' . $onuid . '', false, 'ONU', 100, $opts);
        return ($result);
    }

    /**
     * Loads existing signal cache from FS
     * 
     * @return void
     */
    protected function loadSignalsCache() {
        $availCacheData = rcms_scandir(self::SIGCACHE_PATH, '*_' . self::SIGCACHE_EXT);
        if (!empty($availCacheData)) {
            foreach ($availCacheData as $io => $each) {
                $raw = file_get_contents(self::SIGCACHE_PATH . $each);
                $raw = unserialize($raw);
                foreach ($raw as $mac => $signal) {
                    $this->signalCache[$mac] = $signal;
                }
            }
        }
    }

    /**
     * Loads ONU distance cache
     * 
     * @return void
     */
    protected function loadDistanceCache() {
        $availCacheData = rcms_scandir(self::DISTCACHE_PATH, '*_' . self::DISTCACHE_EXT);
        if (!empty($availCacheData)) {
            foreach ($availCacheData as $io => $each) {
                $raw = file_get_contents(self::DISTCACHE_PATH . $each);
                $raw = unserialize($raw);
                foreach ($raw as $mac => $distance) {
                    $this->distanceCache[$mac] = $distance;
                }
            }
        }
    }

    /**
     * Loads ONU interface cache
     *
     * @return void
     */
    protected function loadInterfaceCache() {
        $availCacheData = rcms_scandir(self::INTCACHE_PATH, '*_' . self::INTCACHE_EXT);
        if (!empty($availCacheData)) {
            foreach ($availCacheData as $io => $each) {
                $raw = file_get_contents(self::INTCACHE_PATH . $each);
                $raw = unserialize($raw);
                foreach ($raw as $mac => $interface) {
                    $this->interfaceCache[$mac] = $interface;
                }
            }
        }
    }

    /**
     * Loads OLT FDB cache
     *
     * @return void
     */
    protected function loadFDBCache() {
        $availCacheData = rcms_scandir(self::FDBCACHE_PATH, '*_' . self::FDBCACHE_EXT);
        if (!empty($availCacheData)) {
            foreach ($availCacheData as $io => $each) {
                $raw = file_get_contents(self::FDBCACHE_PATH . $each);
                $raw = unserialize($raw);
                foreach ($raw as $oidMac => $FDB) {
                    $this->FDBCache[$oidMac] = $FDB;
                }
            }
        }
    }

    /**
     * Renders json formatted data about unregistered ONU
     * 
     * @return void
     */
    public function ajaxOnuUnknownData() {
        $json = new wf_JqDtHelper();
        $availCacheData = rcms_scandir(self::ONUCACHE_PATH, '*_' . self::ONUCACHE_EXT);
        if (!empty($availCacheData)) {
            foreach ($availCacheData as $io => $each) {
                $raw = file_get_contents(self::ONUCACHE_PATH . $each);
                $raw = unserialize($raw);
                $oltId = explode('_', $each);
                $oltId = @vf($oltId[0], 3);
                foreach ($raw as $index => $mac) {
                    $this->onuIndexCache[$mac] = $oltId;
                }
            }
        }

        if (!empty($this->onuIndexCache)) {
            foreach ($this->onuIndexCache as $onuMac => $oltId) {
                //not registered?
                if ($this->checkMacUnique($onuMac)) {
                    $oltData = @$this->allOltDevices[$oltId];
                    $data[] = $oltData;
                    $data[] = $onuMac;
                    $actControls = wf_Link(self::URL_ME . '&unknownonulist=true&fastreg=true&oltid=' . $oltId . '&onumac=' . $onuMac, wf_img('skins/add_icon.png', __('Register')));
                    $data[] = $actControls;
                    $json->addRow($data);
                    unset($data);
                }
            }
        }

        $json->getJson();
    }

    /**
     * Renders json formatted data for jquery data tables list
     * 
     * @param string $OltId
     * @return void
     */
    public function ajaxOnuData($OltId) {
        $OnuByOLT = $this->getOnuArrayByOltID($OltId);
        $json = new wf_JqDtHelper();
        $allRealnames = zb_UserGetAllRealnames();
        $allAddress = zb_AddressGetFulladdresslistCached();
        $allTariffs = zb_TariffsGetAllUsers();

        if ($this->altCfg['ADCOMMENTS_ENABLED']) {
            $adcomments = new ADcomments('PONONU');
            $adc = true;
        } else {
            $adc = false;
        }

        $this->loadSignalsCache();

        $distCacheAvail = rcms_scandir(self::DISTCACHE_PATH, '*_' . self::DISTCACHE_EXT);
        if (!empty($distCacheAvail)) {
            $distCacheAvail = true;
            $this->loadDistanceCache();
        } else {
            $distCacheAvail = false;
        }

        $intCacheAvail = rcms_scandir(self::INTCACHE_PATH, '*_' . self::INTCACHE_EXT);
        if (!empty($intCacheAvail)) {
            $intCacheAvail = true;
            $this->loadInterfaceCache();
        } else {
            $intCacheAvail = false;
        }

        if (!empty($OnuByOLT)) {
            foreach ($OnuByOLT as $io => $each) {
                $userTariff = '';
                if (!empty($each['login'])) {
                    $userLogin = trim($each['login']);
                    $userLink = wf_Link('?module=userprofile&username=' . $userLogin, web_profile_icon() . ' ' . @$allAddress[$userLogin], false);
                    @$userRealName = $allRealnames[$userLogin];

                    //tariff data
                    if (isset($allTariffs[$userLogin])) {
                        $userTariff = $allTariffs[$userLogin];
                    }
                } else {
                    $userLink = '';
                    $userRealName = '';
                }
                //checking adcomments availability
                if ($adc) {
                    $indicatorIcon = $adcomments->getCommentsIndicator($each['id']);
                } else {
                    $indicatorIcon = '';
                }

                $actLinks = wf_Link('?module=ponizer&editonu=' . $each['id'], web_edit_icon(), false);

                $actLinks.= ' ' . $indicatorIcon;


                //coloring signal
                if (isset($this->signalCache[$each['mac']])) {
                    $signal = $this->signalCache[$each['mac']];
                    if (($signal > 0) OR ( $signal < -25)) {
                        $sigColor = '#ab0000';
                    } else {
                        $sigColor = '#005502';
                    }
                } elseif (isset($this->signalCache[$each['serial']])) {
                    $signal = $this->signalCache[$each['serial']];
                    if (($signal > 0) OR ( $signal < -25)) {
                        $sigColor = '#ab0000';
                    } else {
                        $sigColor = '#005502';
                    }
                } else {
                    $signal = __('No');
                    $sigColor = '#000000';
                }

                $data[] = $each['id'];
                if ($intCacheAvail) {
                    $data[] = @$this->interfaceCache[$each['mac']];
                }
                $data[] = $this->getModelName($each['onumodelid']);
                $data[] = $each['ip'];
                $data[] = $each['mac'];
                $data[] = wf_tag('font', false, '', 'color=' . $sigColor . '') . $signal . wf_tag('fornt', true);
                if ($distCacheAvail) {
                    $data[] = @$this->distanceCache[$each['mac']];
                }
                $data[] = $userLink;
                $data[] = $userRealName;
                $data[] = $userTariff;
                $data[] = $actLinks;

                $json->addRow($data);
                unset($data);
            }
        }


        $json->getJson();
    }

    /**
     * Renders json formatted data for jquery data tables list
     * 
     * @param string $OnuId
     * @return void
     */
    public function ajaxOltFdbData($OnuId) {
        $json = new wf_JqDtHelper();
        if (!empty($OnuId)) {
            $allUserTariffs = zb_TariffsGetAllUsers();
            $onuMacId = $this->allOnu[$OnuId]['mac'];

            $fdbCacheAvail = rcms_scandir(self::FDBCACHE_PATH, '*_' . self::FDBCACHE_EXT);
            if (!empty($fdbCacheAvail)) {
                $fdbCacheAvail = true;
                $this->loadFDBCache();
            } else {
                $fdbCacheAvail = false;
            }
            if ($fdbCacheAvail and isset($this->FDBCache[$onuMacId])) {
                $GetLoginMac = zb_UserGetAllMACs();
                $allAddress = zb_AddressGetFulladdresslistCached();
                $allRealnames = zb_UserGetAllRealnames();

                foreach ($this->FDBCache[$onuMacId] as $id => $FDBdata) {
                    $login = in_array($FDBdata['mac'], array_map('strtolower', $GetLoginMac)) ? array_search($FDBdata['mac'], array_map('strtolower', $GetLoginMac)) : '';

                    $userLink = $login ? wf_Link('?module=userprofile&username=' . $login, web_profile_icon() . ' ' . @$allAddress[$login], false) : '';
                    $userRealnames = $login ? @$allRealnames[$login] : '';
                    $userTariff = (isset($allUserTariffs[$login])) ? $allUserTariffs[$login] : '';

                    $data[] = $id;
                    $data[] = $FDBdata['vlan'];
                    $data[] = $FDBdata['mac'];
                    $data[] = @$userLink;
                    $data[] = @$userRealnames;
                    $data[] = $userTariff;

                    $json->addRow($data);
                    unset($data);
                }
            }
        }


        $json->getJson();
    }

    /**
     * Checks is ONU really associated with some OLT
     * 
     * @param string $onuMac
     * @param  int $oltId
     * @return bool
     */
    protected function checkOnuOLTid($onuMac, $oltId) {
        $result = true;
        if (!empty($this->allOnu)) {
            foreach ($this->allOnu as $io => $each) {
                if ($each['mac'] == $onuMac) {
                    if ($oltId != $each['oltid']) {
                        $result = false;
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Checks is ONU associated with some login or not
     * 
     * @param int $onuId
     * @param string $userLogin
     * 
     * @return bool
     */
    protected function checkOnuUserAssign($onuId, $userLogin) {
        $result = true;
        if (@$this->altCfg['PON_USERLINK_CHECK']) {
            //ONU is registered
            if ($onuId != 0) {
                @$associatedUserLogin = $this->allOnu[$onuId]['login'];
            } else {
                $associatedUserLogin = '';
            }

            if (!empty($associatedUserLogin)) {
                if ($userLogin != $associatedUserLogin) {
                    $result = false;
                } else {
                    $result = true;
                }
            }
        }
        return ($result);
    }

    /**
     * Renders json for current all OLT FDB list
     * 
     * @return void
     */
    public function ajaxFdbCacheList() {
        $json = new wf_JqDtHelper();
        $availOnuFdbCache = rcms_scandir(self::FDBCACHE_PATH, '*_' . self::FDBCACHE_EXT);
        if (!empty($availOnuFdbCache)) {
            $allAddress = zb_AddressGetFulladdresslistCached();
            $allRealnames = zb_UserGetAllRealnames();
            $allUserMac = zb_UserGetAllMACs();
            $allUserMac = array_map('strtolower', $allUserMac);
            $allUserMac = array_flip($allUserMac);
            $allUserTariffs = zb_TariffsGetAllUsers();

            foreach ($availOnuFdbCache as $io => $eachFile) {
                $oltId = explode('_', $eachFile);
                $oltId = $oltId[0];
                $oltDesc = @$this->allOltDevices[$oltId];
                $fileData = file_get_contents(self::FDBCACHE_PATH . '/' . $eachFile);
                if (!empty($fileData)) {
                    $fileData = unserialize($fileData);
                    if (!empty($fileData)) {
                        foreach ($fileData as $onuMac => $onuTmp) {
                            if (!empty($onuTmp)) {
                                foreach ($onuTmp as $id => $onuData) {
                                    $onuRealId = $this->getONUIDByMAC($onuMac);
                                    if ($onuRealId) {
                                        $associatedUserLogin = $this->allOnu[$onuRealId]['login'];
                                    } else {
                                        $associatedUserLogin = '';
                                    }
                                    $userLogin = (isset($allUserMac[$onuData['mac']])) ? $allUserMac[$onuData['mac']] : '';

                                    $onuLink = ($onuRealId) ? wf_Link(self::URL_ME . '&editonu=' . $onuRealId, $id) : $id;
                                    @$userAddress = $allAddress[$userLogin];
                                    @$userRealName = $allRealnames[$userLogin];
                                    @$userTariff = $allUserTariffs[$userLogin];
                                    $userLink = (!empty($userLogin)) ? wf_Link('?module=userprofile&username=' . $userLogin, web_profile_icon() . ' ' . $userAddress) : '';
                                    $oltCheck = (!$this->checkOnuOLTid($onuMac, $oltId)) ? ' ' . wf_img('skins/createtask.gif', __('Wrong OLT')) : '';
                                    $userCheck = (!$this->checkOnuUserAssign($onuRealId, $userLogin)) ? ' ' . wf_img('skins/createtask.gif', __('Wrong associated user')) : '';

                                    $data[] = $oltDesc . $oltCheck;
                                    $data[] = $onuMac;
                                    $data[] = $onuLink;
                                    $data[] = $onuData['vlan'];
                                    $data[] = $onuData['mac'] . $userCheck;
                                    $data[] = $userLink;
                                    $data[] = $userRealName;
                                    $data[] = $userTariff;
                                    $json->addRow($data);
                                    unset($data);
                                }
                            }
                        }
                    }
                }
            }
        }
        $json->getJson();
    }

}

?>