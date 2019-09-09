<?php

class PonZte {

    CONST DESC_PONTYPE = 1;
    CONST DESC_SHELF = 2;
    CONST DESC_SLOT = 3;
    CONST DESC_OLT = 4;
    CONST DESC_ONU = 5;

    /**
     * Array for checking ports count for EPON cards
     * 
     * @var array
     */
    protected $eponCards = array(
        'EPFC' => 4,
        'EPFCB' => 4,
        'ETGO' => 8,
        'ETGOD' => 8,
        'ETGH' => 16,
        'ETGHG' => 16,
        'ETGHK' => 16
    );

    /**
     * Array for checking ports count for GPON cards
     * 
     * @var array
     */
    protected $gponCards = array(
        'GPFA' => 4,
        'GPFAE' => 4,
        'GTGO' => 8,
        'GTGH' => 16,
        'GTGHG' => 16,
        'GTGHK' => 16,
        'GPBD' => 8,
        'GPFD' => 16,
        'GPBH' => 8,
        'GPMD' => 8
    );

    /**
     * Contains snmp helper object
     * 
     * @var object
     */
    protected $snmp;

    /**
     * Contains all OLTs snmp tmplates
     * 
     * @var array
     */
    protected $snmpTemplates = array();

    /**
     * Contains all OLTs devices with proper snmp config
     * 
     * @var array
     */
    protected $allOltSnmp = array();

    /**
     * Contains all olt models
     * 
     * @var array
     */
    protected $allOltModels = array();

    /**
     * Contains all OLTs devices
     * 
     * @var array
     */
    protected $allOltDevices = array();

    /**
     * Current OLT switch id
     * 
     * @var int
     */
    protected $oltid = 0;

    /**
     * Current OLT IP
     * 
     * @var string
     */
    protected $oltFullIp = '';

    /**
     * Current OLT snmp community
     * 
     * @var string
     */
    protected $oltCommunity = '';

    /**
     * Take only needed SNMP template for current OLT.
     * 
     * @var array
     */
    protected $currentSnmpTemplate;

    /**
     * Contains all ONUs MAC addresses.
     * 
     * @var array
     */
    protected $macIndex = array();

    /**
     * Contains all signals
     * 
     * @var array
     */
    protected $sigIndex = array();

    /**
     * Contains all interface names => ONU ID
     * 
     * @var array
     */
    protected $intIndex = array();

    /**
     * Contains FDB
     * 
     * @var array
     */
    protected $fdbIndex = array();

    /**
     * Contains type epon or gpon.
     * 
     * @var string
     */
    public $ponType = '';

    /**
     * Contains all serial numbers => index
     * 
     * @var array
     */
    protected $snIndex = array();

    /**
     * Contains distances for ONTs
     * 
     * @var array
     */
    protected $distanceIndex = array();

    public function __construct($oltModelId, $oltid, $oltIp, $oltCommunity) {
        $this->oltid = $oltid;
        $this->oltCommunity = $oltCommunity;
        $this->oltFullAddress = $oltIp . ':' . PONizer::SNMPPORT;

        $this->initSNMP();
        $this->loadOltDevices();
        $this->loadOltModels();
        $this->loadSnmpTemplates();
        $this->currentSnmpTemplate = $this->snmpTemplates[$oltModelId];
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
                $this->allOltModels[$each['id']]['ports'] = $each['ports'];
            }
        }
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
                            $privateTemplateFile = DATA_PATH . 'documents/mysnmptemplates/' . $this->allOltModels[$oltModelid]['snmptemplate'];
                            if (file_exists($templateFile)) {
                                $this->snmpTemplates[$oltModelid] = rcms_parse_ini_file($templateFile, true);
                                if (file_exists($privateTemplateFile)) {
                                    $this->snmpTemplates[$oltModelid] = rcms_parse_ini_file($privateTemplateFile, true);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    //wrappers

    /**
     * Wrapper around $this->snmp->walk method and explodeRows function to get less string length.
     * 
     * @param string $oid
     * 
     * @return array
     */
    protected function snmpwalk($oid) {
        $data = $this->snmp->walk($this->oltFullAddress, $this->oltCommunity, $oid, PONizer::SNMPCACHE);
        return(explodeRows($data));
    }

    /**
     * Converts hex to string value
     *
     * @param string $hex
     * @return string
     */
    protected function hexToString($hex) {
        return pack('H*', $hex);
    }

    /**
     * Remove oid + dot from string
     * 
     * @param string $oid
     * @param string $str
     * 
     * @return string
     */
    protected function strRemoveOidWithDot($oid, $str) {
        return(trim(str_replace($oid . ".", '', $str)));
    }

    /**
     * Wrapper around str_replace to make code more pretty
     * 
     * @param string $search
     * @param string $str
     * 
     * @return string 
     */
    protected function strRemove($search, $str) {
        return(trim(str_replace($search, '', $str)));
    }

    //processing functions

    /**
     * Epon signals preprocessing
     * 
     * @return array
     */
    protected function signalIndexProcessing() {
        foreach ($this->sigIndex as $devIndex => &$eachsig) {
            if ($eachsig == $this->currentSnmpTemplate['signal']['DOWNVALUE']) {
                $eachsig = -9000;
            } else {
                $eachsig = str_replace('"', '', $eachsig);
                if ($this->currentSnmpTemplate['signal']['OFFSETMODE'] == 'div') {
                    if ($this->currentSnmpTemplate['signal']['OFFSET']) {
                        $eachsig = $eachsig / $this->currentSnmpTemplate['signal']['OFFSET'];
                    }
                }
            }
        }
    }

    /**
     * Epon mac indexes preprocessing
     * 
     * @return array
     */
    protected function macIndexEponProcessing() {
        foreach ($this->macIndex as $devIndex => &$eachmac) {
            $eachmac = str_replace(' ', ':', $eachmac);
            $eachmac = strtolower($eachmac);
        }
    }

    /**
     * Serial number indexes preprocessing.
     * 
     * @return void
     */
    protected function serialIndexGponProcessing() {
        foreach ($this->snIndex as $devIndex => &$eachSn) {
            $eachSn = str_replace(' ', ':', $eachSn);
            $eachSn = strtoupper($eachSn);
        }
    }

    /**
     * Coverts dec value to binary with byte offset.
     * 
     * @param int $binary
     * 
     * @return array()
     */
    protected function getDecodeType($binary) {
        $match = array();
        $match2 = array();
        switch (strlen($binary)) {
            case 30:
                preg_match("/(\d{4})(\d{3})(\d{4})(\d{3})(\d{8})(\d{8})/", $binary, $match);
                break;
            case 31:
                preg_match("/(\d{4})(\d{4})(\d{4})(\d{3})(\d{8})(\d{8})/", $binary, $match);
                break;
            case 32:
                preg_match("/(\d{4})(\d{4})(\d{5})(\d{3})(\d{8})(\d{8})/", $binary, $match);
                break;
        }
        foreach ($match as &$each) {
            $each = bindec($each);
        }
        $match[self::DESC_OLT] += 1;
        if (!empty($match)) {
            switch ($match[self::DESC_PONTYPE]) {
                case 9:
                    preg_match("/(\d{4})(\d{4})(\d{4})(\d{4})(\d{8})(\d{8})/", $binary, $match2);
                    break;
                case 10:
                    preg_match("/(\d{4})(\d{4})(\d{4})(\d{4})(\d{8})(\d{8})/", $binary, $match2);
                    break;
            }
            if (isset($match2[self::DESC_PONTYPE])) {
                foreach ($match2 as &$each) {
                    $each = bindec($each);
                }
                $match2[self::DESC_OLT] += 1;
                $match = $match2;
            } else {
                return array();
            }
        }
        return($match);
    }

    /**
     * Converts binary string to human readable format like epon-olt_1/1/10:16
     * 
     * @param array $match
     * @param boolg $default
     * 
     * @return string
     */
    protected function stdDecodeOutput($match, $default = true) {
        $typeName = array(
            1 => 'type_olt_virtualIfBER',
            3 => 'type-onu',
            8 => 'type-onu',
            9 => 'type-onu',
            10 => 'type-onu',
            12 => 'type-onu'
        );
        //rename interface to epon (or gpon if needed)
        foreach ($typeName as &$name) {
            if ($this->ponType == 'EPON') {
                $name = str_replace('type', 'epon', $name);
            } else {
                $name = str_replace('type', 'gpon', $name);
            }
        }

        if ($default) {
            return($typeName[$match[self::DESC_PONTYPE]]
                    . '_'
                    . $match[self::DESC_SHELF]
                    . '/'
                    . $match[self::DESC_SLOT]
                    . '/'
                    . $match[self::DESC_OLT]
                    . ':'
                    . $match[self::DESC_ONU]
                    );
        } else {
            return($typeName[$match[self::DESC_PONTYPE]]
                    . '_'
                    . $match[self::DESC_SHELF]
                    . '/'
                    . $match[self::DESC_SLOT]
                    . '/'
                    . $match[self::DESC_OLT]
                    );
        }
    }

    /**
     * Calculation ZTE epon interfaces indexes.
     * 
     * @return void
     */
    protected function intIndexCalcEpon() {
        $cards = $this->cardsEponCalc();
        $onu_id_start = 805830912;
        foreach ($cards as $card) {
            $onu_id = $onu_id_start + (524288 * ($card - 1));
            for ($port = 1; $port <= 16; $port++) {
                $tmp_id = $onu_id;
                for ($onu_num = 1; $onu_num <= 64; $onu_num++) {
                    $this->intIndex[$tmp_id] = 'epon-onu_' . $card . "/" . $port . ':' . $onu_num;
                    $tmp_id += 256;
                }
                $onu_id += 65536;
            }
        }
    }

    /**
     * Check out which cards are installed
     * 
     * @return array
     */
    protected function cardsEponCalc() {
        $cards = array();
        if (isset($this->currentSnmpTemplate['misc']['ALLCARDS'])) {
            $allCards = $this->snmpwalk($this->currentSnmpTemplate['misc']['ALLCARDS']);
            foreach ($allCards as $io => $value) {
                $split = explode("=", $value);
                if (isset($split[1])) {
                    $oid = $this->strRemoveOidWithDot($this->currentSnmpTemplate['misc']['ALLCARDS'], $split[0]);
                    $oidParts = explode(".", $oid);
                    $cardNumber = end($oidParts);
                    $card = trim(str_replace("STRING:", '', $split[1]));
                    $card = str_replace('"', '', $card);
                    if (isset($this->eponCards[$card])) {
                        $cards[] = $cardNumber;
                    }
                }
            }
        }

        if (empty($cards)) {
            if (isset($this->currentSnmpTemplate['misc']['CARDOFFSET'])) {
                $start = $this->currentSnmpTemplate['misc']['CARDOFFSET'];
            } else {
                $start = 1;
            }
            for ($card = $start; $card <= 20; $card++) {
                $cards[] = $card;
            }
        }
        return($cards);
    }

    /**
     * Getting raw snmp interface index => mac onu
     * 
     * @return array
     */
    protected function macIndexRawCalc() {
        $macIndex = array();
        $macIndexTmp = $this->snmpwalk($this->currentSnmpTemplate['signal']['MACINDEX']);
        foreach ($macIndexTmp as $io => $value) {
            $value = $this->strRemoveOidWithDot($this->currentSnmpTemplate['signal']['MACINDEX'], $value);
            $value = $this->strRemove($this->currentSnmpTemplate['signal']['MACVALUE'], $value);
            $macIndex[$io] = $value;
        }
        return($macIndex);
    }

    /**
     * Prettyfying result of macIndexRawCalc
     * 
     * @return void
     */
    protected function macIndexCalc() {
        $macIndexRaw = $this->macIndexRawCalc();
        if (!empty($macIndexRaw)) {
            foreach ($macIndexRaw as $rawIo => $rawEach) {
                $explodeIndex = explode('=', $rawEach);
                if (isset($explodeIndex[1])) {
                    $naturalIndex = trim($explodeIndex[0]);
                    $naturalMac = trim($explodeIndex[1]);
                    $this->macIndex[$naturalIndex] = $naturalMac;
                }
            }
        }
    }

    /**
     * Getting signals => snmp interface id.
     * 
     * @return array
     */
    protected function sigIndexCalc($data) {
        $sigIndexTmp = array();
        if (!empty($data)) {
            foreach ($data as $ioIndex => $eachVal) {
                $tmpSig = $this->snmpwalk($this->currentSnmpTemplate['signal']['SIGINDEX'] . $ioIndex);
                $sigIndex = $this->strRemove($this->currentSnmpTemplate['signal']['SIGVALUE'], $tmpSig[0]);
                $sigIndex = $this->strRemove($this->currentSnmpTemplate['signal']['SIGINDEX'], $sigIndex);
                $explodeSig = explode('=', $sigIndex);
                $naturalIndex = trim($explodeSig[0]);
                if (isset($explodeSig[1])) {
                    $naturalSig = trim($explodeSig[1]);
                    $sigIndexTmp[$naturalIndex] = $naturalSig;
                }
            }
        }
        unset($this->sigIndex);
        $this->sigIndex = $sigIndexTmp;
    }

    /**
     * Getting FDB
     * 
     * @return void
     */
    protected function fdbCalcEpon() {
        $FDBIndexTmp = $this->snmpwalk($this->currentSnmpTemplate['misc']['FDBINDEX']);
        foreach ($FDBIndexTmp as $id => &$value) {
            $value = $this->strRemoveOidWithDot($this->currentSnmpTemplate['misc']['FDBINDEX'], $value);
        }
        $this->fdbIndex = $FDBIndexTmp;
    }

    /**
     * 
     * Function for fixing fucking zte interfaces snmp id.
     * 
     * @param int $uuid
     * @param int $cardOffset     
     * @return string
     */
    protected function interfaceDecode($uuid, $cardOffset = 0) {
        $binary = decbin($uuid);
        $match = $this->getDecodeType($binary);

        if (!empty($match) and isset($match[self::DESC_PONTYPE])) {
            switch ($match[self::DESC_PONTYPE]) {
                case 1:
                    return($this->stdDecodeOutput($match, false));
                case 3:
                    return($this->stdDecodeOutput($match));
                case 6:
                    return($match[self::DESC_SHELF] . '/' . $match[self::DESC_SLOT] . '/');
                case 8:
                    $match[self::DESC_SLOT] += $cardOffset;
                    $match[self::DESC_ONU] += 1;
                    return($this->stdDecodeOutput($match));
                case 9:
                    return($this->stdDecodeOutput($match));
                case 10:
                    $match[self::DESC_SLOT] += 1;
                    $match[self::DESC_ONU] += 1;
                    return($this->stdDecodeOutput($match));
                case 12:
                    return($this->stdDecodeOutput($match));
            }
        }
        return FALSE;
    }

    /**
     * Preprocessing serial index array with removing unneded substrings.
     * 
     * @return void
     */
    protected function snIndexProcess() {
        $this->snIndex = $this->snmpwalk($this->currentSnmpTemplate['signal']['SNINDEX']);
        foreach ($this->snIndex as $io => &$value) {
            $value = $this->strRemove($this->currentSnmpTemplate['signal']['SNVALUE'], $value);
            $value = $this->strRemoveOidWithDot($this->currentSnmpTemplate['signal']['SNINDEX'], $value);
            $value = trim($value);
        }
    }

    /**
     * Preproccess distances indexes.
     * 
     * @return void
     */
    protected function distanceIndexProcess() {
        foreach ($this->snIndex as $ioIndex => $eachSn) {
            $tmpDist = $this->snmpwalk($this->currentSnmpTemplate['signal']['DISTANCE'] . $ioIndex);
            $distIndex = $this->strRemove($this->currentSnmpTemplate['signal']['DISTANCE'], $tmpDist[0]);
            $distIndex = $this->strRemove($this->currentSnmpTemplate['signal']['DISTVALUE'], $distIndex);
            $explodeDist = explode('=', $distIndex);
            $naturalIndex = trim($explodeDist[0]);
            if (isset($explodeDist[1])) {
                $naturalDist = trim($explodeDist[1]);
                $$this->distanceIndex[$naturalIndex] = $naturalDist;
            }
        }
    }

    //parser functions

    /**
     * Parses & stores in cache ZTE OLT ONU ID
     *
     * @return void
     */
    protected function onuidParseEpon() {
        $macTmp = array();

        foreach ($this->macIndex as $ioIndex => $eachMac) {
            $eachMac = strtolower($eachMac);
            $eachMac = str_replace(" ", ":", $eachMac);
            $macTmp[$ioIndex] = $eachMac;
        }
        $macTmp = serialize($macTmp);
        file_put_contents(PONizer::ONUCACHE_PATH . $this->oltid . '_' . PONizer::ONUCACHE_EXT, $macTmp);
    }

    /**
     * Parsing and validating input array. Getting hex from dec values.
     * 
     * @param array $decParts
     * 
     * @return array
     */
    protected function macPartParse($decParts) {
        $macPart = array();
        if (isset($decParts[1])) {
            for ($i = 2; $i <= 7; $i++) {
                $macPart[] = dechex($decParts[$i]);
            }
            foreach ($macPart as &$eachPart) {
                if (strlen($eachPart) < 2) {
                    $eachPart = '0' . $eachPart;
                }
            }
        }
        return($macPart);
    }

    /**
     * Parses & stores in cache OLT ONU interfaces
     *
     * @return void
     */
    protected function fdbParseEpon() {
        $cardOffset = $this->currentSnmpTemplate['misc']['CARDOFFSET'];
        $counter = 1;
        $fdbTmp = array();
        $macTmp = array();
        $result = array();
        //fdb index preprocessing
        if ((!empty($this->FDBIndex)) AND ( !empty($this->macIndex))) {
            foreach ($this->FDBIndex as $io => $eachfdb) {
                $line = explode('=', $eachfdb);
                $devOID = trim($line[0]);
                $decParts = explode('.', $devOID);
                $devIndex = trim($decParts[0]);
                $interfaceName = $this->interfaceDecode($devIndex, $cardOffset);
                if ($interfaceName) {
                    if (isset($decParts[1])) {
                        $FDBvlan = trim($decParts[1]);
                        $FDBmac = implode(':', $this->macPartParse($decParts));
                        $fdbTmp[$interfaceName][$counter]['mac'] = $FDBmac;
                        $fdbTmp[$interfaceName][$counter]['vlan'] = $FDBvlan;
                        $counter++;
                    }
                }
            }
            //mac index preprocessing            
            foreach ($this->macIndex as $devIndex => $eachMac) {
                if ($this->interfaceDecode($devIndex, $cardOffset)) {
                    $macTmp[$this->interfaceDecode($devIndex, $cardOffset)] = $eachMac;
                }
            }

            $realData = array_intersect_key($macTmp, $fdbTmp);
            //storing results            
            foreach ($realData as $devId => $eachMac) {
                $result[$macTmp[$devId]] = $fdbTmp[$devId];
            }
        }
        file_put_contents(PONizer::FDBCACHE_PATH . $this->oltid . '_' . PONizer::FDBCACHE_EXT, serialize($result));
    }

    /**
     * Parses & stores in cache ZTE OLT ONU interfaces
     *
     * @return void
     */
    protected function interfaceParseEpon() {
        $result = array();
        $macTmp = array();

        //storing results

        foreach ($this->macIndex as $ioIndex => $eachMac) {
            if (isset($this->intIndex[$ioIndex])) {
                $eachMac = strtolower($eachMac);
                $eachMac = str_replace(" ", ":", $eachMac);
                $interface = $this->intIndex[$ioIndex];
                $result[$eachMac] = $interface;
                $macTmp[$ioIndex] = $eachMac;
            } elseif ($this->interfaceDecode($ioIndex)) {
                $eachMac = strtolower($eachMac);
                $eachMac = str_replace(" ", ":", $eachMac);
                $result[$eachMac] = $this->interfaceDecode($ioIndex);
                $macTmp[$ioIndex] = $eachMac;
            }
        }
        $result = serialize($result);
        file_put_contents(PONizer::INTCACHE_PATH . $this->oltid . '_' . PONizer::INTCACHE_EXT, $result);
    }

    /**
     * Performs signal preprocessing for sig/mac index arrays and stores it into cache for ZTE OLT
     *
     * @return void
     */
    protected function signalParseEpon() {
        $result = array();
        if ((!empty($this->sigIndex)) AND ( !empty($this->macIndex))) {
            $this->signalIndexProcessing();
            $this->macIndexEponProcessing();
            $realData = array_intersect_key($this->macIndex, $this->sigIndex);

            foreach ($realData as $devId => $io) {
                $result[$this->macIndex[$devId]] = $this->sigIndex[$devId];

                $historyFile = PONizer::ONUSIG_PATH . md5($this->macIndex[$devId]);
                file_put_contents($historyFile, curdatetime() . ',' . $this->sigIndex[$devId] . "\n", FILE_APPEND);
            }
        }
        file_put_contents(PONizer::SIGCACHE_PATH . $this->oltid . '_' . PONizer::SIGCACHE_EXT, serialize($result));
    }

    /**
     * Performs signal preprocessing for sig/sn index arrays and stores it into cache for ZTE OLT          
     *
     * @return void
     */
    protected function signalParseGpon() {

        $result = array();
        $curDate = curdatetime();

        //signal index preprocessing
        if ((!empty($this->sigIndex)) AND ( !empty($this->snIndex))) {
            $this->signalIndexProcessing();
            $this->serialIndexGponProcessing();
            $realData = array_intersect_key($this->snIndex, $this->sigIndex);

            //storing results            
            foreach ($realData as $devId => $eachSn) {
                $result[$this->snIndex[$devId]] = $this->sigIndex[$devId];
                //signal history filling
                $historyFile = PONizer::ONUSIG_PATH . md5($this->snIndex[$devId]);

                file_put_contents($historyFile, $curDate . ',' . $this->sigIndex[$devId] . "\n", FILE_APPEND);
            }
        }
        $result = serialize($result);
        file_put_contents(PONizer::SIGCACHE_PATH . $this->oltid . '_' . PONizer::SIGCACHE_EXT, $result);
    }

    /**
     * Parsing distance for ZTE/Huawei GPON 
     * 
     * @param array $distIndex     
     * 
     * @return void
     */
    protected function distanceParseGpon() {
        $result = array();

        //distance index preprocessing
        if (!empty($this->distanceIndex) AND ! empty($this->snIndex)) {
            $realData = array_intersect_key($this->snIndex, $this->distanceIndex);
            foreach ($this->$realData as $io => $eachsn) {
                $result[$this->snIndex[$io]] = $this->distanceIndex[$io];
            }
        }
        $result = serialize($result);
        file_put_contents(PONizer::DISTCACHE_PATH . $this->oltid . '_' . PONizer::DISTCACHE_EXT, $result);
    }

    /**
     * Parsing serial numbers;
     * 
     * @return void
     */
    protected function serialNumberParse() {
        $result = array();
        foreach ($this->snIndex as $rawIo => $rawEach) {
            $result = array();
            $explodeIndex = explode('=', $rawEach);
            if (isset($explodeIndex[1])) {
                $naturalIndex = trim($explodeIndex[0]);
                $tmpSn = trim($explodeIndex[1]);
                $tmpSn = explode(" ", $tmpSn);
                $check = trim($tmpSn[0]);
                if ($check == 'STRING:') {
                    $naturalSn = $this->serialNumberBinaryParse($tmpSn[1]);
                } else {
                    $naturalSn = $this->serialNumberHexParse($tmpSn);
                }

                $result[$naturalIndex] = $naturalSn;
            }
        }
        unset($this->snIndex);
        $this->snIndex = $result;
    }

    /**
     * Parsing serial number in binary format and coverting it to needed format.
     * 
     * @param array $rawSn
     * 
     * @return string
     */
    protected function serialNumberBinaryParse($rawSn) {
        $parts = array();
        $hexSn = bin2hex($rawSn);
        if (strlen($hexSn) == 20) {
            $parts[0] = $this->serialNumberPartsTranslate($hexSn[2] . $hexSn[3]);
            $parts[1] = $this->serialNumberPartsTranslate($hexSn[4] . $hexSn[5]);
            $parts[2] = $this->serialNumberPartsTranslate($hexSn[6] . $hexSn[7]);
            $parts[3] = $this->serialNumberPartsTranslate($hexSn[8] . $hexSn[9]);
            $parts[4] = '';
            for ($i = 10; $i <= 17; $i++) {
                $parts[4] .= $hexSn[$i];
            }
        } else {
            $parts[0] = $this->serialNumberPartsTranslate($hexSn[0] . $hexSn[1]);
            $parts[1] = $this->serialNumberPartsTranslate($hexSn[2] . $hexSn[3]);
            $parts[2] = $this->serialNumberPartsTranslate($hexSn[4] . $hexSn[5]);
            $parts[3] = $this->serialNumberPartsTranslate($hexSn[6] . $hexSn[7]);
            $parts[4] = '';
            for ($i = 8; $i <= 15; $i++) {
                $parts[4] .= $hexSn[$i];
            }
        }
        $result = implode("", $parts);
        return($result);
    }

    /**
     * Parsing serial number in hex format and coverting it to needed format.
     * 
     * @param array $rawSn
     * 
     * @return string
     */
    protected function serialNumberHexParse($rawSn) {
        $parts[0] = $this->serialNumberPartsTranslate($rawSn[0]);
        $parts[1] = $this->serialNumberPartsTranslate($rawSn[1]);
        $parts[2] = $this->serialNumberPartsTranslate($rawSn[2]);
        $parts[3] = $this->serialNumberPartsTranslate($rawSn[3]);
        $parts[4] = $rawSn[4] . $rawSn[5] . $rawSn[6] . $rawSn[7];
        $result = implode("", $parts);
        return($result);
    }

    /**
     * Check mode to convert serial number string vs raw.
     * 
     * @param string $part
     * 
     * @return string
     */
    protected function serialNumberPartsTranslate($part) {
        if ($this->currentSnmpTemplate['signal']['SNMODE'] == 'STRING') {
            return($this->hexToString($part));
        }
        if ($this->currentSnmpTemplate['signal']['SNMODE'] == 'PURE') {
            return($part);
        }
    }

//Main section

    /**
     * Polling EPON device
     * 
     * @return void
     */
    public function pollEpon() {
        $this->macIndexCalc();
        $this->sigIndexCalc($this->macIndex);

        $this->signalParseEpon();

        if (isset($this->currentSnmpTemplate['misc'])) {
            if (isset($this->currentSnmpTemplate['misc']['CARDOFFSET'])) {
                $this->intIndexCalcEpon();
                $this->fdbCalcEpon();
                $this->fdbParseEpon();
                $this->interfaceParseEpon();
                $this->onuidParseEpon();
            }
        }
    }

    /**
     * Polling GPON device
     * 
     * @return void
     */
    public function pollGpon() {
        $this->snIndexProcess();
        $this->serialNumberParse();
        $this->sigIndexCalc($this->snIndex);
        $this->signalParseGpon();

        if (isset($this->currentSnmpTemplate['signal']['DISTANCE'])) {
            $this->distanceIndexProcess();
            $this->distanceParseGpon();
        }
    }

    /**
     * Polling Huawei GPON device
     * 
     * @return void
     */
    public function huaweiPollGpon() {
        $this->snIndexProcess();
        $this->serialNumberParse();
        $this->sigIndexCalc($this->snIndex);
        $this->signalParseGpon();

        if (isset($this->currentSnmpTemplate['signal']['DISTANCE'])) {
            $this->distanceIndexProcess();
            $this->distanceParseGpon();
        }
    }

}
