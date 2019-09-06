<?php

class PonZte {

    CONST PONTYPE = 1;
    CONST SHELF = 2;
    CONST SLOT = 3;
    CONST OLT = 4;
    CONST ONU = 5;

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
    protected $ponType = '';

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

    /**
     * Parses & stores in cache ZTE OLT ONU ID
     *
     * @return void
     */
    protected function onuidParseEpon() {
        $macTmp = array();

        foreach ($this->macIndex as $ioIndex => $eachMac) {
            $eachMac = strtolower($eachMac);
            $eachMac = explode(" ", $eachMac);
            $eachMac = implode(":", $eachMac);
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
            $macPart[] = dechex($decParts[2]);
            $macPart[] = dechex($decParts[3]);
            $macPart[] = dechex($decParts[4]);
            $macPart[] = dechex($decParts[5]);
            $macPart[] = dechex($decParts[6]);
            $macPart[] = dechex($decParts[7]);
            foreach ($macPart as &$eachPart) {
                if (strlen($eachPart) < 2) {
                    $eachPart = '0' . $eachPart;
                }
            }
        }
        return($macPart);
    }

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
        $match[self::OLT] += 1;
        if (!empty($match)) {
            switch ($match[self::PONTYPE]) {
                case 9:
                    preg_match("/(\d{4})(\d{4})(\d{4})(\d{4})(\d{8})(\d{8})/", $binary, $match2);
                    break;
                case 10:
                    preg_match("/(\d{4})(\d{4})(\d{4})(\d{4})(\d{8})(\d{8})/", $binary, $match2);
                    break;
            }
            if (isset($match2[self::PONTYPE])) {
                foreach ($match2 as &$each) {
                    $each = bindec($each);
                }
                $match2[self::OLT] += 1;
                $match = $match2;
            } else {
                return array();
            }
        }
        return($match);
    }

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
            return($typeName[$match[self::PONTYPE]]
                    . '_'
                    . $match[self::SHELF]
                    . '/'
                    . $match[self::SLOT]
                    . '/'
                    . $match[self::OLT]
                    . ':'
                    . $match[self::ONU]
                    );
        } else {
            return($typeName[$match[self::PONTYPE]]
                    . '_'
                    . $match[self::SHELF]
                    . '/'
                    . $match[self::SLOT]
                    . '/'
                    . $match[self::OLT]
                    );
        }
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

        if (!empty($match) and isset($match[self::PONTYPE])) {
            switch ($match[self::PONTYPE]) {
                case 1:
                    return($this->stdDecodeOutput($match, false));
                case 3:
                    return($this->stdDecodeOutput($match));
                case 6:
                    return($match[self::SHELF] . '/' . $match[self::SLOT] . '/');
                case 8:
                    $match[self::SLOT] += $cardOffset;
                    $match[self::ONU] += 1;
                    return($this->stdDecodeOutput($match));
                case 9:
                    return($this->stdDecodeOutput($match));
                case 10:
                    $match[self::SLOT] += 1;
                    $match[self::ONU] += 1;
                    return($this->stdDecodeOutput($match));
                case 12:
                    return($this->stdDecodeOutput($match));
            }
        }
        return FALSE;
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
                $eachMac = explode(" ", $eachMac);
                $eachMac = implode(":", $eachMac);
                $interface = $this->intIndex[$ioIndex];
                $result[$eachMac] = $interface;
                $macTmp[$ioIndex] = $eachMac;
            } elseif ($this->interfaceDecode($ioIndex)) {
                $eachMac = strtolower($eachMac);
                $eachMac = explode(" ", $eachMac);
                $eachMac = implode(":", $eachMac);
                $result[$eachMac] = $this->interfaceDecode($ioIndex);
                $macTmp[$ioIndex] = $eachMac;
            }
        }
        $result = serialize($result);
        file_put_contents(PONizer::INTCACHE_PATH . $this->oltid . '_' . PONizer::INTCACHE_EXT, $result);
    }

    /**
     * Epon signals preprocessing
     * 
     * @return array
     */
    protected function signalIndexEponProcessing() {
        foreach ($this->sigIndex as $devIndex => &$eachsig) {
            $signalRaw = $eachsig; // signal level

            if ($eachsig == $this->currentSnmpTemplate['signal']['DOWNVALUE']) {
                $eachsig = -9000;
            } else {
                $eachsig = str_replace('"', '', $signalRaw);
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
     * Performs signal preprocessing for sig/mac index arrays and stores it into cache for ZTE OLT
     *
     * @return void
     */
    protected function signalParseEpon() {
        $result = array();
        if ((!empty($this->sigIndex)) AND ( !empty($this->macIndex))) {
            $this->signalIndexEponProcessing();
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
     * @param array $sigIndex     
     * @param array $snIndex
     *
     * @return void
     */
    protected function signalParseGpon($sigIndex, $snIndex, $snmpTemplate) {
        $sigTmp = array();
        $result = array();
        $curDate = curdatetime();

//signal index preprocessing
        if ((!empty($sigIndex)) AND ( !empty($snIndex))) {
            foreach ($sigIndex as $devIndex => $eachsig) {
                $signalRaw = $eachsig; // signal level
                $signalRaw = str_replace('"', '', $signalRaw);

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
                        $historyFile = PONizer::ONUSIG_PATH . md5($eachSn);
                        if ($signal == 'Offline') {
                            $signal = -9000; //over 9000 offline signal level :P
                        }

                        file_put_contents($historyFile, $curDate . ',' . $signal . "\n", FILE_APPEND);
                    }
                }

                $result = serialize($result);
                file_put_contents(PONizer::SIGCACHE_PATH . $this->oltid . '_' . PONizer::SIGCACHE_EXT, $result);
            }
        }
    }

    /**
     * Parsing distance for ZTE/Huawei GPON 
     * 
     * @param array $distIndex
     * @param array $snIndex
     * 
     * @return void
     */
    protected function distanceParseGpon($distIndex, $snIndex) {
        $result = array();

//distance index preprocessing
        if (!empty($distIndex) AND ! empty($snIndex)) {
            foreach ($snIndex as $io => $eachsn) {
                if (isset($distIndex[$io])) {
                    $distance = $distIndex[$io];
                    $result[$eachsn] = $distance;
                }
            }
            $result = serialize($result);
            file_put_contents(PONizer::DISTCACHE_PATH . $this->oltid . '_' . PONizer::DISTCACHE_EXT, $result);
        }
    }

    /**
     * Calculation ZTE epon interfaces indexes.
     * 
     * @return void
     */
    protected function intIndexCalcEpon() {
        $cards = $this->cardsEponCalc();
        $intIndex = array();
        $onu_id_start = 805830912;
        foreach ($cards as $card) {
            $onu_id = $onu_id_start + (524288 * ($card - 1));
            for ($port = 1; $port <= 16; $port++) {
                $tmp_id = $onu_id;
                for ($onu_num = 1; $onu_num <= 64; $onu_num++) {
                    $intIndex[$tmp_id] = 'epon-onu_' . $card . "/" . $port . ':' . $onu_num;
                    $tmp_id += 256;
                }
                $onu_id += 65536;
            }
        }
        $this->intIndex = $intIndex;
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
            if (!empty($allCards)) {
                foreach ($allCards as $io => $value) {
                    $split = explode("=", $value);
                    $oid = $this->strRemoveOidWithDot($this->currentSnmpTemplate['misc']['ALLCARDS'], $split[0]);
                    $oidParts = explode(".", $oid);
                    $cardNumber = last($oidParts);
                    $card = trim(str_replace("STRING:", '', $split[1]));
                    if (isset($this->eponCards[$card])) {
                        $cards[] = $cardNumber;
                    }
                }
            }
        } else {
            for ($card = $this->currentSnmpTemplate['misc']['CARDOFFSET']; $card <= 20; $card++) {
                $cards[] = $card;
            }
        }
        return($card);
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
        $macIndex = array();
        if (!empty($macIndexRaw)) {
            foreach ($macIndexRaw as $rawIo => $rawEach) {
                $explodeIndex = explode('=', $rawEach);
                if (!empty($explodeIndex)) {
                    $naturalIndex = trim($explodeIndex[0]);
                    $naturalMac = trim($explodeIndex[1]);
                    $macIndex[$naturalIndex] = $naturalMac;
                }
            }
        }
        $this->macIndex = $macIndex;
    }

    /**
     * Getting signals => snmp interface id.
     * 
     * @return array
     */
    protected function sigIndexCalc() {
        $sigIndexTmp = array();
        if (!empty($this->macIndex)) {
            foreach ($this->macIndex as $ioIndex => $eachMac) {
                $tmpSig = $this->snmpwalk($this->currentSnmpTemplate['signal']['SIGINDEX'] . $ioIndex);
                $sigIndex = $this->strRemoveOidWithDot($this->currentSnmpTemplate['signal']['SIGINDEX'], $tmpSig);
                $sigIndex = $this->strRemove($this->currentSnmpTemplate['signal']['SIGVALUE'], '', $sigIndex);
                $sigIndex = $this->strRemove($this->currentSnmpTemplate['signal']['SIGINDEX'], '', $sigIndex);
                $explodeSig = explode('=', $sigIndex);
                $naturalIndex = trim($explodeSig[0]);
                if (isset($explodeSig[1])) {
                    $naturalSig = trim($explodeSig[1]);
                    $sigIndexTmp[$naturalIndex] = $naturalSig;
                }
            }
        }
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

    /**
     * Polling EPON device
     * 
     * @return void
     */
    public function pollEpon() {
        $this->macIndexCalc();
        $this->sigIndexCalc();

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
        $snIndex = $this->snmpwalk($this->currentSnmpTemplate['signal']['SNINDEX']);
        $snIndex = str_replace($this->currentSnmpTemplate['signal']['SNVALUE'], '', $snIndex);
        $snIndex = str_replace($this->currentSnmpTemplate['signal']['SNINDEX'] . '.', '', $snIndex);
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
                    $check = trim($tmpSn[0]);
                    if ($check == 'STRING:') {
                        $tmpSn = bin2hex($tmpSn[1]);
                        if (strlen($tmpSn) == 20) {
                            $tmp[0] = $tmpSn[2] . $tmpSn[3];
                            $tmp[1] = $tmpSn[4] . $tmpSn[5];
                            $tmp[2] = $tmpSn[6] . $tmpSn[7];
                            $tmp[3] = $tmpSn[8] . $tmpSn[9];
                            $tmpStr = '';
                            for ($i = 10; $i <= 17; $i++) {
                                $tmpStr .= $tmpSn[$i];
                            }
                            $tmp[4] = $tmpStr;
                        } else {
                            $tmp[0] = $tmpSn[0] . $tmpSn[1];
                            $tmp[1] = $tmpSn[2] . $tmpSn[3];
                            $tmp[2] = $tmpSn[4] . $tmpSn[5];
                            $tmp[3] = $tmpSn[6] . $tmpSn[7];
                            $tmp[4] = $tmpSn[8] . $tmpSn[9] . $tmpSn[10] . $tmpSn[11] . $tmpSn[12] . $tmpSn[13] . $tmpSn[14] . $tmpSn[15];
                        }
                        if (!isset($tmpSn[12])) {
//                                                print_r($tmpSn);
//                                                echo '<br />';
                        }
                        $tmpSn = $tmp;
                    } else {
                        $tmp[0] = $tmpSn[0];
                        $tmp[1] = $tmpSn[1];
                        $tmp[2] = $tmpSn[2];
                        $tmp[3] = $tmpSn[3];
                        $tmp[4] = $tmpSn[4] . $tmpSn[5] . $tmpSn[6] . $tmpSn[7];
                        $tmpSn = $tmp;
                    }
                    if ($this->currentSnmpTemplate['signal']['SNMODE'] == 'STRING') {
                        $naturalSn = $this->hexToString($tmpSn[0]);
                        $naturalSn .= $this->hexToString($tmpSn[1]);
                        $naturalSn .= $this->hexToString($tmpSn[2]);
                        $naturalSn .= $this->hexToString($tmpSn[3]);
                        $naturalSn .= $tmpSn[4];
                    }
                    if ($this->currentSnmpTemplate['signal']['SNMODE'] == 'PURE') {
                        $naturalSn = implode('', $tmpSn);
                    }

                    $snIndexTmp[$naturalIndex] = $naturalSn;
                }
            }
        }

        $sigIndexTmp = array();
        if (!empty($snIndexTmp)) {
            foreach ($snIndexTmp as $ioIndex => $eachSn) {
                $tmpSig = $this->snmpwalk($this->currentSnmpTemplate['signal']['SIGINDEX'] . $ioIndex);
                $sigIndex = str_replace($this->currentSnmpTemplate['signal']['SIGINDEX'], '', $tmpSig);
                $sigIndex = str_replace($this->currentSnmpTemplate['signal']['SIGVALUE'], '', $sigIndex);
                $explodeSig = explode('=', $sigIndex);
                $naturalIndex = trim($explodeSig[0]);
                if (isset($explodeSig[1])) {
                    $naturalSig = trim($explodeSig[1]);
                    $sigIndexTmp[$naturalIndex] = $naturalSig;
                }
                if (isset($this->currentSnmpTemplate['signal']['DISTANCE'])) {
                    $tmpDist = $this->snmpwalk($this->currentSnmpTemplate['signal']['DISTANCE'] . $ioIndex);
                    $distIndex = str_replace($this->currentSnmpTemplate['signal']['DISTANCE'], '', $tmpDist);
                    $distIndex = str_replace($this->currentSnmpTemplate['signal']['DISTVALUE'], '', $distIndex);
                    $explodeDist = explode('=', $distIndex);
                    $naturalIndex = trim($explodeDist[0]);
                    if (isset($explodeDist[1])) {
                        $naturalDist = trim($explodeDist[1]);
                        $distIndexTmp[$naturalIndex] = $naturalDist;
                    }
                }
            }
        }
        $this->signalParseGpon($sigIndexTmp, $snIndexTmp, $this->currentSnmpTemplate['signal']);
        if (isset($this->currentSnmpTemplate['signal']['DISTANCE'])) {
            $this->distanceParseGpon($distIndexTmp, $snIndexTmp);
        }
    }

    /**
     * Polling Huawei GPON device
     * 
     * @return void
     */
    public function huaweiPollGpon() {
        $snIndex = $this->snmpwalk($this->currentSnmpTemplate['signal']['SNINDEX']);
        $snIndex = str_replace($this->currentSnmpTemplate['signal']['SNVALUE'], '', $snIndex);
        $snIndex = str_replace($this->currentSnmpTemplate['signal']['SNINDEX'] . '.', '', $snIndex);
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
                    $check = trim($tmpSn[0]);
                    if ($check == 'STRING:') {
                        $tmpSn = bin2hex($tmpSn[1]);
                        if (strlen($tmpSn) == 20) {
                            $tmp[0] = $tmpSn[2] . $tmpSn[3];
                            $tmp[1] = $tmpSn[4] . $tmpSn[5];
                            $tmp[2] = $tmpSn[6] . $tmpSn[7];
                            $tmp[3] = $tmpSn[8] . $tmpSn[9];
                            $tmpStr = '';
                            for ($i = 10; $i <= 17; $i++) {
                                $tmpStr .= $tmpSn[$i];
                            }
                            $tmp[4] = $tmpStr;
                        } else {
                            $tmp[0] = $tmpSn[0] . $tmpSn[1];
                            $tmp[1] = $tmpSn[2] . $tmpSn[3];
                            $tmp[2] = $tmpSn[4] . $tmpSn[5];
                            $tmp[3] = $tmpSn[6] . $tmpSn[7];
                            $tmp[4] = $tmpSn[8] . $tmpSn[9] . $tmpSn[10] . $tmpSn[11] . $tmpSn[12] . $tmpSn[13] . $tmpSn[14] . $tmpSn[15];
                        }
                        if (!isset($tmpSn[12])) {
//                                                print_r($tmpSn);
//                                                echo '<br />';
                        }
                        $tmpSn = $tmp;
                    } else {
                        $tmp[0] = $tmpSn[0];
                        $tmp[1] = $tmpSn[1];
                        $tmp[2] = $tmpSn[2];
                        $tmp[3] = $tmpSn[3];
                        $tmp[4] = $tmpSn[4] . $tmpSn[5] . $tmpSn[6] . $tmpSn[7];
                        $tmpSn = $tmp;
                    }
                    if ($this->currentSnmpTemplate['signal']['SNMODE'] == 'STRING') {
                        $naturalSn = $this->hexToString($tmpSn[0]);
                        $naturalSn .= $this->hexToString($tmpSn[1]);
                        $naturalSn .= $this->hexToString($tmpSn[2]);
                        $naturalSn .= $this->hexToString($tmpSn[3]);
                        $naturalSn .= $tmpSn[4];
                    }
                    if ($this->currentSnmpTemplate['signal']['SNMODE'] == 'PURE') {
                        $naturalSn = implode('', $tmpSn);
                    }

                    $snIndexTmp[$naturalIndex] = $naturalSn;
                }
            }
        }

        $sigIndexTmp = array();
        if (!empty($snIndexTmp)) {
            foreach ($snIndexTmp as $ioIndex => $eachSn) {
                $tmpSig = $this->snmpwalk($this->currentSnmpTemplate['signal']['SIGINDEX'] . $ioIndex);
                $sigIndex = str_replace($this->currentSnmpTemplate['signal']['SIGINDEX'], '', $tmpSig);
                $sigIndex = str_replace($this->currentSnmpTemplate['signal']['SIGVALUE'], '', $sigIndex);
                $explodeSig = explode('=', $sigIndex);
                $naturalIndex = trim($explodeSig[0]);
                if (isset($explodeSig[1])) {
                    $naturalSig = trim($explodeSig[1]);
                    $sigIndexTmp[$naturalIndex] = $naturalSig;
                }
                if (isset($this->currentSnmpTemplate['signal']['DISTANCE'])) {
                    $tmpDist = $this->snmpwalk($this->currentSnmpTemplate['signal']['DISTANCE'] . $ioIndex);
                    $distIndex = str_replace($this->currentSnmpTemplate['signal']['DISTANCE'], '', $tmpDist);
                    $distIndex = str_replace($this->currentSnmpTemplate['signal']['DISTVALUE'], '', $distIndex);
                    $explodeDist = explode('=', $distIndex);
                    $naturalIndex = trim($explodeDist[0]);
                    if (isset($explodeDist[1])) {
                        $naturalDist = trim($explodeDist[1]);
                        $distIndexTmp[$naturalIndex] = $naturalDist;
                    }
                }
            }
        }
        $this->signalParseGpon($sigIndexTmp, $snIndexTmp, $this->currentSnmpTemplate['signal']);
        if (isset($this->currentSnmpTemplate['signal']['DISTANCE'])) {
            $this->distanceParseGpon($distIndexTmp, $snIndexTmp);
        }
    }

}
