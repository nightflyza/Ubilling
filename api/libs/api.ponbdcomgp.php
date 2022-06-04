<?php

/**
 * OLT BDCOM GPXXXX hardware abstraction layer
 */
class PONBdcomGP extends PONBdcom {

    /**
     * Receives, preprocess and stores all required data from BDCOM GPXXXX OLT device
     * 
     * @return void
     */
    public function collect() {
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
                    $onuIndex = str_replace('"', '', $onuIndex);
                    $onuIndex = explodeRows($onuIndex);

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
        }

//getting others system data from OLT
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
//getting MAC index.
        $macIndexOID = $this->snmpTemplates[$oltModelId]['signal']['MACINDEX'];
        $macIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $macIndexOID, self::SNMPCACHE);
        $macIndex = str_replace($macIndexOID . '.', '', $macIndex);
        $macIndex = str_replace($this->snmpTemplates[$oltModelId]['signal']['MACVALUE'], '', $macIndex);
        $macIndex = str_replace('"', '', $macIndex);
        $macIndex = explodeRows($macIndex);

        $this->signalParse($oltid, $sigIndex, $macIndex, $this->snmpTemplates[$oltModelId]['signal']);
//This is here because BDCOM is BDCOM and another snmp queries cant be processed after MACINDEX query in some cases.
        if (isset($this->snmpTemplates[$oltModelId]['misc'])) {
            if (isset($this->snmpTemplates[$oltModelId]['misc']['DISTINDEX'])) {
                if (!empty($this->snmpTemplates[$oltModelId]['misc']['DISTINDEX'])) {
// processing distance data
                    $this->distanceParseGPBd($oltid, $distIndex, $onuIndex);
//processing interfaces data
                    $this->interfaceParseBd($oltid, $intIndex, $macIndex);
//processing FDB data
                    if (!$oltNoFDBQ) {
                        $this->FDBParseGPBd($oltid, $FDBIndex, $macIndex, $oltModelId);
                    }

                    if (isset($this->snmpTemplates[$oltModelId]['misc']['DEREGREASON'])) {
//processing last dereg reason data
                        $this->lastDeregParseBd($oltid, $deregIndex, $onuIndex);
                    }
                }
            }
        }
    }

    /**
     * Parses & stores in cache OLT ONU interfaces
     *
     * @param int $oltid
     * @param array $FDBIndex
     * @param array $macIndex
     * @param array $FDBDEVIndex
     * @param array $oltModelId
     *
     * @return void
     */
    protected function FDBParseGPBd($oltid, $FDBIndex, $macIndex, $oltModelId) {
        $oltid = vf($oltid, 3);
        $TmpArr = array();
        $FDBTmp = array();
        $macTmp = array();
        $result = array();

//fdb index preprocessing
        if ((!empty($FDBIndex)) and ( !empty($macIndex))) {
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

                //saving FDB data
                $this->olt->writeFdb($result);
            }
        }
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
    protected function distanceParseGPBd($oltid, $distIndex, $onuIndex) {
        $oltid = vf($oltid, 3);
        $distTmp = array();
        $onuTmp = array();
        $result = array();
        $curDate = curdatetime();

//distance index preprocessing
        if ((!empty($distIndex)) and ( !empty($onuIndex))) {
            foreach ($distIndex as $io => $eachdist) {
                $line = explode('=', $eachdist);
//distance is present
                if (isset($line[1])) {
                    $distanceRaw = trim($line[1]); // distance
                    $devIndex = trim($line[0]); // device index
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
                        if (!empty($distance)) {
                            $distance_m = substr($distance, 0, -1);
                            $distance_dm = substr($distance, -1);
                            $result[$eachMac] = $distance_m . '.' . $distance_dm;
                        } else {
                            $result[$eachMac] = 0;
                        }
                    }
                }
                //saving ONUs distances
                $this->olt->writeDistances($result);

                //saving ONUs cache
                $this->olt->writeOnuCache($onuTmp);
            }
        }
    }

}
