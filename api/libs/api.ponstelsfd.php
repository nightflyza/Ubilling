<?php

/**
 * OLT Stels FD11XX  hardware abstraction layer
 */
class PONStelsFD extends PONStels {

    /**
     * Receives, preprocess and stores all required data from Stels FD11XX or V-Solution 1600D
     * 
     * @return void
     */
    public function collect() {
        $oltModelId = $this->oltParameters['MODELID'];
        $oltid = $this->oltParameters['ID'];
        $oltIp = $this->oltParameters['IP'];
        $oltCommunity = $this->oltParameters['COMMUNITY'];
        $oltNoFDBQ = $this->oltParameters['NOFDB'];
        $ponPrefixAdd = (empty($this->snmpTemplates[$oltModelId]['misc']['INTERFACEADDPONPREFIX'])
                         ? '' : $this->snmpTemplates[$oltModelId]['misc']['INTERFACEADDPONPREFIX']);

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

        $this->signalParseStels($oltid, $sigIndex, $macIndex, $this->snmpTemplates[$oltModelId]['signal'], $ponPrefixAdd);
//ONU distance polling for stels devices
        if (isset($this->snmpTemplates[$oltModelId]['misc'])) {
            if (isset($this->snmpTemplates[$oltModelId]['misc']['DISTINDEX'])) {
                if (!empty($this->snmpTemplates[$oltModelId]['misc']['DISTINDEX'])) {
                    $distIndexOid = $this->snmpTemplates[$oltModelId]['misc']['DISTINDEX'];
                    $distIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $distIndexOid, self::SNMPCACHE);
                    $distIndex = str_replace($distIndexOid . '.', '', $distIndex);
                    $distIndex = str_replace($this->snmpTemplates[$oltModelId]['misc']['DISTVALUE'], '', $distIndex);
                    $distIndex = explodeRows($distIndex);

                    $lastDeregIndexOID = $this->snmpTemplates[$oltModelId]['misc']['DEREGREASON'];
                    $lastDeregIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $lastDeregIndexOID, self::SNMPCACHE);
                    $lastDeregIndex = str_replace($lastDeregIndexOID . '.', '', $lastDeregIndex);
                    $lastDeregIndex = str_replace($this->snmpTemplates[$oltModelId]['misc']['DEREGVALUE'], '', $lastDeregIndex);
                    $lastDeregIndex = explodeRows($lastDeregIndex);

                    $this->distanceParseStels($oltid, $distIndex, $macIndex);
                    $this->lastDeregParseStels($oltid, $lastDeregIndex, $macIndex);

                    if (!$oltNoFDBQ) {
                        $fdbIndexOID = $this->snmpTemplates[$oltModelId]['misc']['FDBINDEX'];
                        $fdbIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $fdbIndexOID, self::SNMPCACHE);
                        $fdbIndex = str_replace($fdbIndexOID . '.', '', $fdbIndex);
                        $fdbIndex = str_replace($this->snmpTemplates[$oltModelId]['misc']['FDBVALUE'], '', $fdbIndex);
                        $fdbIndex = explodeRows($fdbIndex);

                        $fdbMACIndexOID = $this->snmpTemplates[$oltModelId]['misc']['FDBMACINDEX'];
                        $fdbMACIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $fdbMACIndexOID, self::SNMPCACHE);
                        $fdbMACIndex = str_replace($fdbMACIndexOID . '.', '', $fdbMACIndex);
                        $fdbMACIndex = str_replace($this->snmpTemplates[$oltModelId]['misc']['FDBMACVALUE'], '', $fdbMACIndex);
                        $fdbMACIndex = explodeRows($fdbMACIndex);

                        $fdbVLANIndexOID = $this->snmpTemplates[$oltModelId]['misc']['FDBVLANINDEX'];
                        $fdbVLANIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $fdbVLANIndexOID, self::SNMPCACHE);
                        $fdbVLANIndex = str_replace($fdbVLANIndexOID . '.', '', $fdbVLANIndex);
                        $fdbVLANIndex = str_replace($this->snmpTemplates[$oltModelId]['misc']['FDBVLANVALUE'], '', $fdbVLANIndex);
                        $fdbVLANIndex = explodeRows($fdbVLANIndex);

                        $this->fdbParseStels($oltid, $macIndex, $fdbIndex, $fdbMACIndex, $fdbVLANIndex);
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
    }

    /**
     * Performs signal preprocessing for sig/mac index arrays and stores it into cache
     *
     * @param int $oltid
     * @param array $sigIndex
     * @param array $macIndex
     * @param array $snmpTemplate
     *
     * @return void
     */
    public function signalParseStels($oltid, $sigIndex, $macIndex, $snmpTemplate, $ponPrefixAdd = '') {
        $oltid = vf($oltid, 3);
        $sigTmp = array();
        $macTmp = array();
        $macDevIdx = array();
        $ifacesIdx = array();
        $result = array();
        $curDate = curdatetime();
        $plasticIndexSig = 0;
        $plasticIndexMac = 0;

//signal index preprocessing
        if ((!empty($sigIndex)) and ( !empty($macIndex))) {
            foreach ($sigIndex as $io => $eachsig) {
                $line = explode('=', $eachsig);
//signal is present
                if (isset($line[1])) {
                    $signalRaw = trim($line[1]); // signal level
                    $signalOnuPort = str_replace($snmpTemplate['SIGINDEX'], '', $line[0]);
                    $signalOnuPort = explode('.', $signalOnuPort);
                    $plasticIndexSig = trim($signalOnuPort[1]);
//                    $plasticIndexSig = ($plasticIndexSig * 256) + 1; // realy shitty index
                    if ($signalRaw == $snmpTemplate['DOWNVALUE'] or empty($signalRaw)) {
                        $signalRaw = 'Offline';
                    } else {
                        if ($snmpTemplate['OFFSETMODE'] == 'logm') {
                            if ($snmpTemplate['OFFSET']) {
                                $signalRaw = round(10 * log10($signalRaw) - $snmpTemplate['OFFSET'], 2);
                            }
                        }
                    }

                    $sigTmp[$signalOnuPort[0] . ':' . $plasticIndexSig] = $signalRaw;
                }
            }


//mac index preprocessing
            foreach ($macIndex as $io => $eachmac) {
                $line = explode('=', $eachmac);
//mac is present
                if (isset($line[1])) {
                    $macRaw = trim($line[1]); //mac address
                    $macOnuPort = str_replace($snmpTemplate['MACINDEX'], '', $line[0]);
                    $macOnuPort = explode('.', $macOnuPort);
                    $plasticIndexMac = trim($macOnuPort[1]);
                    $macRaw = str_replace(' ', ':', $macRaw);
                    $macRaw = strtolower($macRaw);
                    $macTmp[$macOnuPort[0] . ':' . $plasticIndexMac] = $macRaw;
                    $macDevIdx[$macRaw] = $macOnuPort[0] . ':' . $plasticIndexMac;
                    $ifacesIdx[$macRaw] = $ponPrefixAdd . $macOnuPort[0] . ':' . $plasticIndexMac;
//                    $macDevIdx[$macRaw] = $macOnuPort[0] . '.' . (($plasticIndexMac - 1) / 256);
                }
            }


//storing results
            if (!empty($macTmp)) {
                foreach ($macTmp as $devId => $eachMac) {
                    if (isset($sigTmp[$devId])) {
                        $signal = $sigTmp[$devId];
                        $result[$eachMac] = $signal;
//signal history filling

                        if ($signal == 'Offline') {
                            $signal = $this->onuOfflineSignalLevel; //over 9000 offline signal level :P
                        }

                        //saving each ONU signal history
                        $this->olt->writeSignalHistory($eachMac, $signal);
                    }
                }

                //saving ONUs signals
                $this->olt->writeSignals($result);

                //saving ONUs cache
                $this->olt->writeOnuCache($macTmp);

                //saving ONUs interfaces
                $this->olt->writeInterfaces($ifacesIdx);

                //saving ONUs MAC index
                $this->olt->writeMacIndex($macDevIdx);
            }
        }
    }

    /**
     * Parses & stores in cache OLT ONU distances
     *
     * @param int $oltid
     * @param array $distIndex
     * @param array $macIndex
     *
     * @return void
     */
    protected function distanceParseStels($oltid, $distIndex, $macIndex) {
        $oltid = vf($oltid, 3);
        $distTmp = array();
        $onuTmp = array();
        $result = array();
        $curDate = curdatetime();

//distance index preprocessing
        if ((!empty($distIndex)) and ( !empty($macIndex))) {
            foreach ($distIndex as $io => $eachdist) {
                $line = explode('=', $eachdist);
//distance is present
                if (isset($line[1])) {
                    $distanceRaw = trim($line[1]); // distance
                    $devIndex = $line[0];
                    $devIndex = explode('.', $devIndex);
                    $portIndex = trim($devIndex[0]);
                    $devIndex = trim($devIndex[1]);
//                    $devIndex = (($devIndex * 256) + 1);
                    $distTmp[$portIndex . ':' . $devIndex] = $distanceRaw;
                }
            }


//mac index preprocessing
            foreach ($macIndex as $io => $eachmac) {
                $line = explode('=', $eachmac);
//mac is present
                if (isset($line[1])) {
                    $macRaw = trim($line[1]); //mac address
                    $devIndex = trim($line[0]);
                    $devIndex = explode('.', $devIndex);
                    $portIndex = trim($devIndex[0]);
                    $devIndex = $devIndex[1];
                    $macRaw = str_replace(' ', ':', $macRaw);
                    $macRaw = strtolower($macRaw);
                    $onuTmp[$portIndex . ':' . $devIndex] = $macRaw;
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

                //saving distances
                $this->olt->writeDistances($result);
            }
        }
    }


    /**
     * Parses & stores to cache ONUs FDB cache (MACs behind ONU)
     *
     * @param $oltID
     * @param $onuMACIndex
     * @param $fdbIndex
     * @param $fdbMACIndex
     * @param $fdbVLANIndex
     */
    protected function fdbParseStels($oltID, $onuMACIndex, $fdbIndex, $fdbMACIndex, $fdbVLANIndex) {
        $macLLIDIndexes = array();
        $fdbLLIDIndexes = array();
        $fdbIdxMAC = array();
        $fdbIdxVLAN = array();
        $fdbCahce = array();

// processing $onuMACIndex array to get pon port number + ONU LLID => ONU MAC mapping
        if (!empty($onuMACIndex) and ! empty($fdbIndex)) {
            foreach ($onuMACIndex as $eachIdx => $eachONUMAC) {
                $line = explode('=', $eachONUMAC);
// MAC is present
                if (isset($line[1])) {
                    $onuMAC = trim($line[1]);
                    $tmpIndex = trim($line[0]);               // pon port number + device index
                    $tmpIndex = explode('.', $tmpIndex);

                    $portIndex = trim($tmpIndex[0]);           // pon port number
                    $devIndexRaw = $tmpIndex[1];

                    // seems next lines no needed no more
                    // $devIndexLLID = ($devIndexRaw - 1) / 256;     // ONU LLID
                    // $macLLIDIndexes[$portIndex . ':' . $devIndexLLID] = $onuMAC;     // pon port number + ONU LLID => ONU MAC

                    $macLLIDIndexes[$portIndex . ':' . $devIndexRaw] = $onuMAC;     // pon port number + ONU LLID => ONU MAC
                }
            }

// processing FDBIndex array to get FDB index number => pon port number + ONU LLID mapping
            foreach ($fdbIndex as $each => $eachIdx) {
                $line = explode('=', $eachIdx);
// ONU LLID is present
                if (isset($line[1])) {
                    $devLLID = trim($line[1]);                   // ONU LLID
                    $tmpIndex = trim($line[0]);                   // pon port number + FDB index
                    $tmpIndex = explode('.', $tmpIndex);

                    $portIndex = trim($tmpIndex[0]);               // pon port number
                    $fdbIdxRaw = $tmpIndex[1];                     // FDB index number
                    $fdbLLIDIndexes[$fdbIdxRaw] = $portIndex . ':' . $devLLID;       // FDB index number => pon port number + ONU LLID
                }
            }

// processing $fdbMACIndex array to get FDB index number => FDB MAC mapping
            foreach ($fdbMACIndex as $each => $eachIdx) {
                $line = explode('=', $eachIdx);
// FDB MAC is present
                if (isset($line[1])) {
                    $fdbMAC = trim($line[1]);                   // FDB MAC
                    $tmpIndex = trim($line[0]);                   // pon port number + FDB index
                    $tmpIndex = explode('.', $tmpIndex);

                    $fdbIdxRaw = $tmpIndex[1];                     // FDB index number
                    $fdbIdxMAC[$fdbIdxRaw] = $fdbMAC;               // FDB index number => FDB MAC
                }
            }

// processing $fdbVLANIndex array to get FDB index number => FDB VLAN mapping
            foreach ($fdbVLANIndex as $each => $eachIdx) {
                $line = explode('=', $eachIdx);
// FDB VLAN is present
                if (isset($line[1])) {
                    $fdbVLAN = trim($line[1]);                   // FDB VLAN
                    $tmpIndex = trim($line[0]);                   // pon port number + FDB index
                    $tmpIndex = explode('.', $tmpIndex);

                    $fdbIdxRaw = $tmpIndex[1];                     // FDB index number
                    $fdbIdxVLAN[$fdbIdxRaw] = $fdbVLAN;             // FDB index number => FDB VLAN
                }
            }

            if (!empty($macLLIDIndexes) and ! empty($fdbLLIDIndexes)) {
                foreach ($macLLIDIndexes as $eachLLID => $eachONUMAC) {
                    $onuFDBIdxs = array_keys($fdbLLIDIndexes, $eachLLID);

                    if (!empty($onuFDBIdxs)) {
                        $tmpFDBArr = array();
                        $tmpONUMAC = strtolower(AddMacSeparator(RemoveMacAddressSeparator($eachONUMAC, array(':', '-', '.', ' '))));

                        foreach ($onuFDBIdxs as $io => $eachIdx) {
                            $tmpFDBMAC = empty($fdbIdxMAC[$eachIdx]) ? '' : $fdbIdxMAC[$eachIdx];

                            if (empty($tmpFDBMAC) or $tmpFDBMAC == $eachONUMAC) {
                                continue;
                            } else {
                                $tmpFDBMAC = strtolower(AddMacSeparator(RemoveMacAddressSeparator($tmpFDBMAC, array(':', '-', '.', ' '))));
                                $tmpFDBVLAN = empty($fdbIdxVLAN[$eachIdx]) ? '' : $fdbIdxVLAN[$eachIdx];
                                // not applicable with PON HAL now.
                                // i dont know nahooya this was here
                                //$tmpONUID = $this->getONUIDByMAC($tmpONUMAC);
                                //$tmpONUID = (empty($tmpONUID)) ? $eachIdx : $tmpONUID;
                                $tmpONUID=$eachIdx;
                                $tmpFDBArr[$tmpONUID] = array('mac' => $tmpFDBMAC, 'vlan' => $tmpFDBVLAN);
                            }
                        }

                        $fdbCahce[$tmpONUMAC] = $tmpFDBArr;
                    }
                }
            }
        }

        //saving OLT FDB
        $this->olt->writeFdb($fdbCahce);
    }
}

