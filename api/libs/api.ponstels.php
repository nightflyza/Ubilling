<?php

class PONStels extends PONProto {

    /**
     * Stels FD12XX devices polling
     * 
     * @param int $oltModelId
     * @param int $oltid
     * @param string $oltIp
     * @param string $oltCommunity
     * @param bool $oltNoFDBQ
     * 
     * @return void
     */
    public function collect($oltModelId, $oltid, $oltIp, $oltCommunity, $oltNoFDBQ) {
        $sigIndexOID = $this->snmpTemplates[$oltModelId]['signal']['SIGINDEX'];

        $sigIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $sigIndexOID, self::SNMPCACHE);
        $sigIndex = str_replace($sigIndexOID . '.', '', $sigIndex);
        $sigIndex = str_replace($this->snmpTemplates[$oltModelId]['signal']['SIGVALUE'], '', $sigIndex);
        $sigIndex = str_replace('.0.0 = ', ' = ', $sigIndex);
        $sigIndex = explodeRows($sigIndex);
//ONU distance polling for stels12 devices
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
                    $onuIndex = str_replace('.0.0 = ', ' = ', $onuIndex);
                    $onuIndex = explodeRows($onuIndex);

                    $intIndexOid = $this->snmpTemplates[$oltModelId]['misc']['INTERFACEINDEX'];
                    $intIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $intIndexOid, self::SNMPCACHE);
                    $intIndex = str_replace($intIndexOid . '.', '', $intIndex);
                    $intIndex = str_replace($this->snmpTemplates[$oltModelId]['misc']['INTERFACEVALUE'], '', $intIndex);
                    $intIndex = str_replace('"', '', $intIndex);
                    $intIndex = explodeRows($intIndex);
                }
            }

            if (isset($this->snmpTemplates[$oltModelId]['misc']['DEREGREASON'])) {
                if (!empty($this->snmpTemplates[$oltModelId]['misc']['DEREGREASON'])) {
                    $lastDeregIndexOID = $this->snmpTemplates[$oltModelId]['misc']['DEREGREASON'];
                    $lastDeregIndex = $this->snmp->walk($oltIp . ':' .
                            self::SNMPPORT, $oltCommunity, $lastDeregIndexOID, self::SNMPCACHE);
                    $lastDeregIndex = str_replace($lastDeregIndexOID . '.', '', $lastDeregIndex);
                    $lastDeregIndex = str_replace($this->snmpTemplates[$oltModelId]['misc']['DEREGVALUE'], '', $lastDeregIndex);
                    $lastDeregIndex = explodeRows($lastDeregIndex);
                }
            }
        }

//getting MAC index.
        $macIndexOID = $this->snmpTemplates[$oltModelId]['signal']['MACINDEX'];
        $macIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $macIndexOID, self::SNMPCACHE);
        $macIndex = str_replace($macIndexOID . '.', '', $macIndex);
        $macIndex = str_replace($this->snmpTemplates[$oltModelId]['signal']['MACVALUE'], '', $macIndex);
        $macIndex = explodeRows($macIndex);
        $this->signalParse($oltid, $sigIndex, $macIndex, $this->snmpTemplates[$oltModelId]['signal']);

        if (isset($this->snmpTemplates[$oltModelId]['misc'])) {
            if (isset($this->snmpTemplates[$oltModelId]['misc']['DISTINDEX'])) {
                if (!empty($this->snmpTemplates[$oltModelId]['misc']['DISTINDEX'])) {
// processing distance data
                    $this->distanceParseBd($oltid, $distIndex, $macIndex);
//processing interfaces data
                    $this->interfaceParseStels12($oltid, $intIndex, $macIndex);
                }
            }

            if (isset($this->snmpTemplates[$oltModelId]['misc']['DEREGREASON'])) {
                if (!empty($this->snmpTemplates[$oltModelId]['misc']['DEREGREASON'])) {
                    $this->lastDeregParseStels12($oltid, $lastDeregIndex, $macIndex);
                }
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
    public function signalParseStels($oltid, $sigIndex, $macIndex, $snmpTemplate) {
        $oltid = vf($oltid, 3);
        $sigTmp = array();
        $macTmp = array();
        $macDevIdx = array();
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
                        $historyFile = self::ONUSIG_PATH . md5($eachMac);
                        if ($signal == 'Offline') {
                            $signal = $this->onuOfflineSignalLevel; //over 9000 offline signal level :P
                        }
                        file_put_contents($historyFile, $curDate . ',' . $signal . "\n", FILE_APPEND);
                    }
                }

                $result = serialize($result);
                $onuTmp = serialize($macTmp);
                $macDevIdx = serialize($macDevIdx);

                file_put_contents(self::SIGCACHE_PATH . $oltid . '_' . self::SIGCACHE_EXT, $result);
                file_put_contents(self::ONUCACHE_PATH . $oltid . '_' . self::ONUCACHE_EXT, $onuTmp);
                file_put_contents(self::INTCACHE_PATH . $oltid . '_' . self::INTCACHE_EXT, $macDevIdx);
                file_put_contents(self::MACDEVIDCACHE_PATH . $oltid . '_' . self::MACDEVIDCACHE_EXT, $macDevIdx);
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
    protected function interfaceParseStels12($oltid, $intIndex, $macIndex) {
        $oltid = vf($oltid, 3);
        $intTmp = array();
        $macTmp = array();
        $result = array();

//distance index preprocessing
        if ((!empty($intIndex)) and ( !empty($macIndex))) {
            foreach ($intIndex as $io => $eachint) {
                if (ispos($eachint, 'pon')) {
                    $line = explode('=', $eachint);

                    if (isset($line[1])) {
                        $interfaceRaw = trim($line[1]); // interface name
                        $devIndex = trim($line[0]); // interface index
                        $intTmp[$devIndex] = $interfaceRaw;
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
                    $currentInterface = '';
                    $onuNum = '';
                    if (!empty($intTmp)) {
                        foreach ($intTmp as $intefaceOffset => $interfaceName) {

                            // dirty hack for firmware > 1.4.0 - some shitty math used
                            $recalcIfaceOffset = $intefaceOffset;
                            if ($recalcIfaceOffset < 100) {
                                $recalcIfaceOffset = (($recalcIfaceOffset - 10) * 256) + 16779776;
                            }

                            if ($devId >= $recalcIfaceOffset) {
                                $currentInterface = $intefaceOffset;
                                $onuNum = $devId - $recalcIfaceOffset;
                            }
                        }

                        $result[$eachMac] = (isset($intTmp[$currentInterface])) ? $intTmp[$currentInterface] . ':' . $onuNum : __('On ho');
                    }
                }

                $result = serialize($result);
                file_put_contents(self::INTCACHE_PATH . $oltid . '_' . self::INTCACHE_EXT, $result);
            }
        }
    }

    /**
     * Parses & stores in cache ONU last dereg reasons
     *
     * @param int   $oltid
     * @param array $deregIndex
     * @param array $macIndex
     *
     * @return void
     */
    protected function lastDeregParseStels12($oltid, $deregIndex, $macIndex) {
        $oltid = vf($oltid, 3);
        $deregTmp = array();
        $macTmp = array();
        $result = array();

//dereg index preprocessing
        if ((!empty($deregIndex)) and ( !empty($macIndex))) {
            foreach ($deregIndex as $io => $eachdereg) {
                $line = explode('=', $eachdereg);
//dereg is present

                if (isset($line[1])) {
                    $lastDeregRaw = trim($line[1]); // last dereg reason
                    $devIndex = trim($line[0]); // device index
                    $deregTmp[$devIndex] = $lastDeregRaw;
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
                    $currentInterface = '';

                    if (!empty($deregTmp)) {
                        foreach ($deregTmp as $intefaceOffset => $interfaceName) {

                            // dirty hack for firmware > 1.4.0 - some shitty math used
                            $recalcIfaceOffset = $intefaceOffset;
                            if ($recalcIfaceOffset < 100) {
                                $recalcIfaceOffset = (($recalcIfaceOffset - 10) * 256) + 16779776;
                            }

                            if ($devId >= $recalcIfaceOffset) {
                                $currentInterface = $intefaceOffset;
                            }
                        }

                        $result[$eachMac] = (isset($deregTmp[$currentInterface])) ? $deregTmp[$currentInterface] : __('On ho');
                    }
                }

                $result = serialize($result);
                file_put_contents(self::DEREGCACHE_PATH . $oltid . '_' . self::DEREGCACHE_EXT, $result);
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
                $result = serialize($result);
                file_put_contents(self::DISTCACHE_PATH . $oltid . '_' . self::DISTCACHE_EXT, $result);
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
    protected function interfaceParseStels($oltid, $intIndex, $macIndex) {
        $oltid = vf($oltid, 3);
        $intTmp = array();
        $macTmp = array();
        $result = array();

//distance index preprocessing
        if ((!empty($intIndex)) and ( !empty($macIndex))) {
            foreach ($intIndex as $io => $eachint) {
                $line = explode('=', $eachint);
//distance is present
                if (isset($line[1])) {
// distance
                    $devIndex = trim($line[0]); // device index
                    $devIndex = explode('.', $devIndex);
                    $portIndex = trim($devIndex[0]);
                    $interfaceRaw = $devIndex[0] . ':' . $devIndex[1];
//                    $devIndex = ($devIndex[1] * 256) + 1;
                    $intTmp[$portIndex . ':' . $devIndex] = $interfaceRaw;
                }
            }

//mac index preprocessing
            foreach ($macIndex as $io => $eachmac) {
                $line = explode('=', $eachmac);
//mac is present
                if (isset($line[1])) {
                    $macRaw = trim($line[1]); //mac address
                    $devIndex = trim($line[0]); //device index
                    $devIndex = explode('.', $devIndex);
                    $portIndex = trim($devIndex[0]);
                    $devIndex = $devIndex[1];
                    $macRaw = str_replace(' ', ':', $macRaw);
                    $macRaw = strtolower($macRaw);
                    $macTmp[$portIndex . ':' . $devIndex] = $macRaw;
                }
            }

//storing results
            if (!empty($macTmp)) {
                foreach ($macTmp as $devId => $eachMac) {
                    if (isset($intTmp[$devId])) {
                        $interface = $intTmp[$devId];
                        $result[$eachMac] = $interface;
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
     * @param array $deregIndex
     * @param array $macIndex
     *
     * @return void
     */
    protected function lastDeregParseStels($oltid, $deregIndex, $macIndex) {
        $oltid = vf($oltid, 3);
        $deregTmp = array();
        $onuTmp = array();
        $result = array();

//dereg index preprocessing
        if ((!empty($deregIndex)) and ( !empty($macIndex))) {
            foreach ($deregIndex as $io => $eachdereg) {
                $line = explode('=', $eachdereg);
//dereg is present
                if (isset($line[1])) {
                    $deregRaw = trim(trim($line[1]), '"'); // dereg
                    $devIndex = $line[0];
                    $devIndex = explode('.', $devIndex);
                    $portIndex = trim($devIndex[0]);
                    $devIndex = trim($devIndex[1]);
//                    $devIndex = (($devIndex * 256) + 1);
                    $deregTmp[$portIndex . ':' . $devIndex] = $deregRaw;
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
                    if (isset($deregTmp[$devId])) {
                        $result[$eachMac] = $deregTmp[$devId];
                    }
                }
                $result = serialize($result);
                file_put_contents(self::DEREGCACHE_PATH . $oltid . '_' . self::DEREGCACHE_EXT, $result);
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
                    $devIndexLLID = ($devIndexRaw - 1) / 256;     // ONU LLID
                    $macLLIDIndexes[$portIndex . ':' . $devIndexLLID] = $onuMAC;     // pon port number + ONU LLID => ONU MAC
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
                                $tmpONUID = $this->getONUIDByMAC($tmpONUMAC);
                                $tmpONUID = (empty($tmpONUID)) ? $eachIdx : $tmpONUID;
                                $tmpFDBArr[$tmpONUID] = array('mac' => $tmpFDBMAC, 'vlan' => $tmpFDBVLAN);
                            }
                        }

                        $fdbCahce[$tmpONUMAC] = $tmpFDBArr;
                    }
                }
            }
        }

        $fdbCahce = serialize($fdbCahce);
        file_put_contents(self::FDBCACHE_PATH . $oltID . '_' . self::FDBCACHE_EXT, $fdbCahce);
    }

}
