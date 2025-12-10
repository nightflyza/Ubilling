<?php

/**
 * OLT BDCOM 36xx/33xx or Eltex and Extralink hardware abstraction layer
 */
class PONBdcom extends PONProto {


    /**
     * Contains system UbillingConfig object instance
     *
     * @var object
     */
    protected $ubConfig = '';
    
    /**
     * Contains flag that enables UNI port oper status polling
     *
     * @var bool
     */
    protected $onuUniStatusEnabled = false;

    /**
     * Receives, preprocess and stores all required data from BDCOM 36xx/33xx or Eltex OLT device
     * 
     * @return void
     */
    public function collect() {
        global $ubillingConfig;
        $this->ubConfig = $ubillingConfig;
        $this->onuUniStatusEnabled = $this->ubConfig->getAlterParam('PON_ONU_UNI_STATUS_ENABLED', false);

        $oltModelId = $this->oltParameters['MODELID'];
        $oltid = $this->oltParameters['ID'];
        $oltIp = $this->oltParameters['IP'];
        $oltCommunity = $this->oltParameters['COMMUNITY'];
        $oltNoFDBQ = $this->oltParameters['NOFDB'];

        $sigIndexOID = $this->snmpTemplates[$oltModelId]['signal']['SIGINDEX'];
        $sigIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $sigIndexOID, self::SNMPCACHE);
        $sigIndex = str_replace($sigIndexOID . '.', '', $sigIndex);
        $sigIndex = str_replace($this->snmpTemplates[$oltModelId]['signal']['SIGVALUE'], '', $sigIndex);
        $sigIndex = explodeRows($sigIndex);
        $ifaceCustDescrIndex = array();
        // ONU distance polling for bdcom devices
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

                    if (isset($this->snmpTemplates[$oltModelId]['misc']['DEREGREASON'])) {
                        $deregIndexOid = $this->snmpTemplates[$oltModelId]['misc']['DEREGREASON'];
                        $deregIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $deregIndexOid, self::SNMPCACHE);
                        $deregIndex = str_replace($deregIndexOid . '.', '', $deregIndex);
                        $deregIndex = str_replace($this->snmpTemplates[$oltModelId]['misc']['DEREGVALUE'], '', $deregIndex);
                        $deregIndex = explodeRows($deregIndex);
                    }

                    $intIndexOid = $this->snmpTemplates[$oltModelId]['misc']['INTERFACEINDEX'];
                    $intIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $intIndexOid, self::SNMPCACHE);
                    $intIndex = str_replace($intIndexOid . '.', '', $intIndex);
                    $intIndex = str_replace($this->snmpTemplates[$oltModelId]['misc']['INTERFACEVALUE'], '', $intIndex);
                    $intIndex = explodeRows($intIndex);

                    if (!$oltNoFDBQ) {
                        $FDBIndexOid = $this->snmpTemplates[$oltModelId]['misc']['FDBINDEX'];
                        $FDBIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $FDBIndexOid, self::SNMPCACHE);
                        $FDBIndex = str_replace($FDBIndexOid . '.', '', $FDBIndex);
                        $FDBIndex = explodeRows($FDBIndex);
                    }
                }
            }

            if (isset($this->snmpTemplates[$oltModelId]['misc']['IFACECUSTOMDESCR'])) {
                $ifaceCustDescrIndexOID = $this->snmpTemplates[$oltModelId]['misc']['IFACECUSTOMDESCR'];
                $ifaceCustDescrIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $ifaceCustDescrIndexOID, self::SNMPCACHE);
                $ifaceCustDescrIndex = str_replace($ifaceCustDescrIndexOID . '.', '', $ifaceCustDescrIndex);
                $ifaceCustDescrIndex = str_replace(array($this->snmpTemplates[$oltModelId]['misc']['INTERFACEVALUE'], '"'), '', $ifaceCustDescrIndex);
                $ifaceCustDescrIndex = explodeRows($ifaceCustDescrIndex);
            }
            // Get UniOperStatusIndex
            if (isset($this->snmpTemplates[$oltModelId]['misc']['UNIOPERSTATUS']) and $this->onuUniStatusEnabled) {
                $uniOperStatusIndexOID = $this->snmpTemplates[$oltModelId]['misc']['UNIOPERSTATUS'];
                $uniOperStatusIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $uniOperStatusIndexOID, self::SNMPCACHE);
                $uniOperStatusIndex = str_replace($uniOperStatusIndexOID . '.', '', $uniOperStatusIndex);
                $uniOperStatusIndex = str_replace(array($this->snmpTemplates[$oltModelId]['misc']['UNIOPERSTATUSVALUE'], '"'), '', $uniOperStatusIndex);
                $uniOperStatusIndex = explodeRows($uniOperStatusIndex);
            }
        }
        // getting other system data from OLT
        if (isset($this->snmpTemplates[$oltModelId]['system'])) {
            //OLT uptime
            if (isset($this->snmpTemplates[$oltModelId]['system']['UPTIME'])) {
                $uptimeIndexOid = $this->snmpTemplates[$oltModelId]['system']['UPTIME'];
                $oltSystemUptimeRaw = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $uptimeIndexOid, self::SNMPCACHE);
                $this->uptimeParse($oltid, $oltSystemUptimeRaw);
            }

            //OLT temperature
            if (isset($this->snmpTemplates[$oltModelId]['system']['TEMPERATURE'])) {
                $temperatureIndexOid = $this->snmpTemplates[$oltModelId]['system']['TEMPERATURE'];
                $oltTemperatureRaw = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $temperatureIndexOid, self::SNMPCACHE);
                $this->temperatureParse($oltid, $oltTemperatureRaw);
            }
        }
        // getting MAC index.
        $macIndexOID = $this->snmpTemplates[$oltModelId]['signal']['MACINDEX'];
        $macIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $macIndexOID, self::SNMPCACHE);
        $macIndex = str_replace($macIndexOID . '.', '', $macIndex);
        $macIndex = str_replace($this->snmpTemplates[$oltModelId]['signal']['MACVALUE'], '', $macIndex);
        $macIndex = explodeRows($macIndex);
        $this->signalParse($oltid, $sigIndex, $macIndex, $this->snmpTemplates[$oltModelId]['signal']);

        // Start proccesing for get ONU id and MAC
        $this->onuMacProcessing($macIndex);
        $this->onuDevIndexProcessing($onuIndex);

        /**
         * This is here because BDCOM is BDCOM and another SNMP queries cant be processed after MACINDEX query in some cases. 
         */
        if (isset($this->snmpTemplates[$oltModelId]['misc'])) {
            if (isset($this->snmpTemplates[$oltModelId]['misc']['DISTINDEX'])) {
                if (!empty($this->snmpTemplates[$oltModelId]['misc']['DISTINDEX'])) {
                    // processing distance data
                    $this->distanceParse($oltid, $distIndex, $onuIndex);
                    // processing interfaces data and interface description data
                    $this->interfaceParseBd($intIndex, $ifaceCustDescrIndex);
                    // processing FDB data
                    if (!$oltNoFDBQ) {
                        if (isset($this->snmpTemplates[$oltModelId]['misc']['FDBMODE']) and $this->snmpTemplates[$oltModelId]['misc']['FDBMODE'] == 'FIRMWARE-F') {
                            $this->FDBParseBdFirmwareF($FDBIndex, $oltModelId);
                        } else {
                            $this->FDBParseBd($FDBIndex, $oltModelId);
                        }
                    }
                    // processing last dereg reason data
                    if (isset($this->snmpTemplates[$oltModelId]['misc']['DEREGREASON'])) {
                        $this->lastDeregParseBd($deregIndex);
                    }
                    // processing UniOperStauts
                    if (isset($this->snmpTemplates[$oltModelId]['misc']['UNIOPERSTATUS']) and $this->onuUniStatusEnabled) {
                        $this->uniParseBd($uniOperStatusIndex);
                    }
                }
            }
        }
    }

    /**
     * Parses & stores in cache OLT ONU interfaces
     *
     * @param array $intIndex
     * @param array $ifaceCustDescrRaw
     *
     * @return void
     */
    protected function interfaceParseBd($intIndex, $ifaceCustDescrRaw = array()) {
        $intTmp = array();
        $result = array();
        $processIfaceCustDescr = ! empty($ifaceCustDescrRaw);
        $ifaceCustDescrIdx = array();
        $ifaceCustDescrArr = array();

        // olt iface descr extraction
        if ($processIfaceCustDescr) {
            foreach ($ifaceCustDescrRaw as $io => $each) {
                if (empty($each)) {
                    continue;
                }

                $ifDescr = explode('=', str_replace(array(" ", "\t", "\n", "\r", "\0", "\x0B"), '', $each));

                if ((empty($ifDescr[0]) && empty($ifDescr[1])) || intval($ifDescr[0]) < 7) {
                    continue;
                }
                if ($ifDescr[0] > 10) {
                    break;
                }

                $ifaceCustDescrIdx[$ifDescr[0] - 6] = $ifDescr[1];
            }
        }

        // interface index preprocessing
        if ((! empty($intIndex)) and ( ! empty($this->macIndexProcessed))) {
            foreach ($intIndex as $io => $eachint) {
                $line = explode('=', $eachint);
                // interface is present
                if (isset($line[1])) {
                    $interfaceRaw = trim($line[1]); // interface
                    $devIndex = trim($line[0]); // device index
                    $intTmp[$devIndex] = $interfaceRaw;
                }
            }

            // storing results
            foreach ($this->macIndexProcessed as $devId => $eachMac) {
                if (isset($intTmp[$devId])) {
                    $interface = $intTmp[$devId];
                    $result[$eachMac] = $interface;
                    $cleanIface = strstr($interface, ':', true);
                    $tPONIfaceNum = substr($cleanIface, -1, 1);

                    if ($processIfaceCustDescr && !isset($ifaceCustDescrArr[$cleanIface]) && array_key_exists($tPONIfaceNum, $ifaceCustDescrIdx)) {
                        $ifaceCustDescrArr[$cleanIface] = $ifaceCustDescrIdx[$tPONIfaceNum];
                    }
                }
            }

            //saving interfaces cache as mac=>interface name
            $this->olt->writeInterfaces($result);
            //saving interfaces custom descriptions as interface=>desctription
            $this->olt->writeInterfacesDescriptions($ifaceCustDescrArr);
        }
    }

    /**
     * Parses & stores in cache OLT ONU interfaces
     *
     * @param array $FDBIndex
     * @param array $oltModelId
     *
     * @return void
     */
    protected function FDBParseBd($FDBIndex, $oltModelId) {
        $FDBTmp = array();
        $result = array();

        // fdb index preprocessing
        if ((! empty($FDBIndex)) and ( ! empty($this->macIndexProcessed))) {
            foreach ($FDBIndex as $io => $eachfdb) {
                if (preg_match('/' . $this->snmpTemplates[$oltModelId]['misc']['FDBVALUE'] . '/', $eachfdb)) {
                    $eachfdb = str_replace($this->snmpTemplates[$oltModelId]['misc']['FDBVALUE'], '', $eachfdb);
                    $line = explode('=', $eachfdb);
                    // fdb is present
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

            // storing results
            foreach ($this->macIndexProcessed as $devId => $eachMac) {
                if (isset($FDBTmp[$devId])) {
                    $fdb = $FDBTmp[$devId];
                    $result[$eachMac] = $fdb;
                }
            }

            //saving FDB cache
            $this->olt->writeFdb($result);
        }
    }

    /**
     * Parses & stores in cache OLT FDB
     *
     * @param array $FDBIndex
     * @param array $oltModelId
     *
     * @return void
     */
    protected function FDBParseBdFirmwareF($FDBIndex, $oltModelId) {
        $TmpArr = array();
        $FDBTmp = array();
        $result = array();

        //fdb index preprocessing
        if ((! empty($FDBIndex)) and ( ! empty($this->macIndexProcessed))) {
            foreach ($FDBIndex as $io => $eachfdbRaw) {
                if (preg_match('/' . $this->snmpTemplates[$oltModelId]['misc']['FDBVALUE'] . '|INTEGER:/', $eachfdbRaw)) {
                    $eachfdbRaw = str_replace(array($this->snmpTemplates[$oltModelId]['misc']['FDBVALUE'], 'INTEGER:'), '', $eachfdbRaw);
                    $line = explode('=', $eachfdbRaw);
                    //fdb is present
                    if (isset($line[1])) {
                        $devOID = trim($line[0]); // FDB last OID
                        $lineRaw = trim($line[1]); // FDB
                        $devline = explode('.', $devOID);
                        $FDBvlan = trim($devline[1]); // Vlan
                        $FDBnum = trim($devline[7]); // Count number of MAC
                        if (preg_match('/^1/', $devOID)) {
                            $FDBRaw = str_replace(' ', ':', $lineRaw);
                            $FDBRaw = strtolower($FDBRaw);
                            $TmpArr[$devOID]['mac'] = $FDBRaw;
                            $TmpArr[$devOID]['vlan'] = $FDBvlan;
                            $TmpArr[$devOID]['FDBnum'] = $FDBnum;
                        } elseif (preg_match('/^2/', $devOID)) {
                            $devIndexOid = substr_replace($devOID, '1', 0, 1);
                            $TmpArr[$devIndexOid]['index'] = $lineRaw;
                        } else {
                            continue;
                        }
                    }
                }
            }
            if (!empty($TmpArr)) {
                // Crete tmp Ubilling PON FDB array
                foreach ($TmpArr as $io => $each) {
                    if (count($each) == 4) {
                        $FDBTmp[$each['index']][$each['FDBnum']]['mac'] = $each['mac'];
                        $FDBTmp[$each['index']][$each['FDBnum']]['vlan'] = $each['vlan'];
                    }
                }
            }

            //storing results
            foreach ($this->macIndexProcessed as $devId => $eachMac) {
                if (isset($FDBTmp[$devId])) {
                    $fdb = $FDBTmp[$devId];
                    $result[$eachMac] = $fdb;
                }
            }
            //saving FDB cache
            $this->olt->writeFDB($result);
        }
    }

    /**
     * Parses & stores in cache OLT ONU dereg reaesons
     *
     * @param array $distIndex
     *
     * @return void
     */
    protected function lastDeregParseBd($deregIndex) {
        $result = array();

        //dereg index preprocessing
        if ((!empty($deregIndex)) and ( !empty($this->onuDevIndexProcessed))) {
            foreach ($deregIndex as $io => $eachdereg) {
                $line = explode('=', $eachdereg);

                //dereg is present
                if (isset($line[1])) {
                    $deregRaw = trim($line[1]); // dereg
                    $devIndex = trim($line[0]); // device index

                    switch ($deregRaw) {
                        case 2:
                            $TxtColor = '"#00B20E"';
                            $tmpONULastDeregReasonStr = 'Normal';
                            break;

                        case 3:
                            $TxtColor = '"#F80000"';
                            $tmpONULastDeregReasonStr = 'MPCP down';
                            break;

                        case 4:
                            $TxtColor = '"#F80000"';
                            $tmpONULastDeregReasonStr = 'OAM down';
                            break;

                        case 5:
                            $TxtColor = '"#6500FF"';
                            $tmpONULastDeregReasonStr = 'Firmware download';
                            break;

                        case 6:
                            $TxtColor = '"#F80000"';
                            $tmpONULastDeregReasonStr = 'Illegal MAC';
                            break;

                        case 7:
                            $TxtColor = '"#FF4400"';
                            $tmpONULastDeregReasonStr = 'LLID admin down';
                            break;

                        case 8:
                            $TxtColor = '"#F80000"';
                            $tmpONULastDeregReasonStr = 'Wire down';
                            break;

                        case 9:
                            $TxtColor = '"#6500FF"';
                            $tmpONULastDeregReasonStr = 'Power off';
                            break;

                        default:
                            $TxtColor = '"#000000"';
                            $tmpONULastDeregReasonStr = 'Unknown';
                            break;
                    }

                    $tmpONULastDeregReasonStr = wf_tag('font', false, '', 'color=' . $TxtColor . '') .
                            $tmpONULastDeregReasonStr .
                            wf_tag('font', true);

                    if (isset($this->onuDevIndexProcessed[$devIndex])) {
                        $result[$this->onuDevIndexProcessed[$devIndex]] = $tmpONULastDeregReasonStr;
                    }
                }
            }

            //saving dereg reasons cache
            $this->olt->writeDeregs($result);
        }
    }

    /**
    * Performs UNI port oper status preprocessing for index array and stores it into cache
    *
    * @param $uniOperStatusIndex
    *
    * @return void
    */
    protected function uniParseBd($uniOperStatusIndex) {
        $result = array();
        if (! empty($this->macIndexProcessed) and ! empty($uniOperStatusIndex)) {
            foreach ($uniOperStatusIndex as $io => $eachRow) {
                $line = explode('=', $eachRow);
                if (empty($line[0]) || empty($line[1])) {
                    continue;
                }

                // LLID + ether port index
                $tmpLLIDEtherIdx = trim($line[0]);
                $tmpLLIDEtherIdx = ltrim($tmpLLIDEtherIdx, '.');
                $tmpLLIDEtherIdxLen = strlen($tmpLLIDEtherIdx);

                // ehter port index
                $tmpEtherIdx = strrchr($tmpLLIDEtherIdx, '.');
                $tmpEtherIdxLen = strlen($tmpEtherIdx);
                $tmpEtherIdx = 'eth' . trim($tmpEtherIdx, '.');

                //LLID
                $tmpONUPortLLID = substr($tmpLLIDEtherIdx, 0, $tmpLLIDEtherIdxLen - $tmpEtherIdxLen);
                $tmpUniStatus = trim($line[1]);
                $tmpUniStatus = ($tmpUniStatus == 1) ? 1 : 0;

                if (isset($this->macIndexProcessed[$tmpONUPortLLID])) {
                    $result[$this->macIndexProcessed[$tmpONUPortLLID]][$tmpEtherIdx] = $tmpUniStatus;
                }
            }
            //saving UniOperStats
            $this->olt->writeUniOperStats($result);
        }
    }
}