<?php

/**
 * OLT V-Solution 1600D hardware abstraction layer
 */
class PONVsol extends PONProto {

    /**
     * Receives, preprocess and stores all required data from V-Solution 1600D
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

        $macIndexOID = $this->snmpTemplates[$oltModelId]['signal']['MACINDEX'];
        $macIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $macIndexOID, self::SNMPCACHE);
        $macIndex = str_replace($macIndexOID . '.', '', $macIndex);
        $macIndex = str_replace($this->snmpTemplates[$oltModelId]['signal']['MACVALUE'], '', $macIndex);
        $macIndex = explodeRows($macIndex);

        $VSOLMACsProcessed = $this->macParseVSOL($macIndex, $this->snmpTemplates[$oltModelId]);

        if (!empty($VSOLMACsProcessed)) {
            $this->signalParseVSOL($oltid, $sigIndex, $VSOLMACsProcessed);

            $distIndexOID = $this->snmpTemplates[$oltModelId]['misc']['DISTINDEX'];
            $distIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $distIndexOID, self::SNMPCACHE);
            $distIndex = str_replace($distIndexOID . '.', '', $distIndex);
            $distIndex = str_replace($this->snmpTemplates[$oltModelId]['misc']['DISTVALUE'], '', $distIndex);
            $distIndex = explodeRows($distIndex);

            $this->distanceParseVSOL($oltid, $distIndex, $VSOLMACsProcessed);

            $ifaceIndexOID = $this->snmpTemplates[$oltModelId]['misc']['IFACEDESCR'];
            $ifaceIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $ifaceIndexOID, self::SNMPCACHE);
            $ifaceIndex = str_replace($ifaceIndexOID . '.', '', $ifaceIndex);
            $ifaceIndex = str_replace(array($this->snmpTemplates[$oltModelId]['misc']['IFACEVALUE'], '"'), '', $ifaceIndex);
            $ifaceIndex = explodeRows($ifaceIndex);

            $ifaceCustDescrIndex = array();
            if (isset($this->snmpTemplates[$oltModelId]['misc']['IFACECUSTOMDESCR'])) {
                $ifaceCustDescrIndexOID = $this->snmpTemplates[$oltModelId]['misc']['IFACECUSTOMDESCR'];
                $ifaceCustDescrIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $ifaceCustDescrIndexOID, self::SNMPCACHE);
                $ifaceCustDescrIndex = str_replace($ifaceCustDescrIndexOID . '.', '', $ifaceCustDescrIndex);
                $ifaceCustDescrIndex = str_replace(array($this->snmpTemplates[$oltModelId]['misc']['IFACEVALUE'], '"'), '', $ifaceCustDescrIndex);
                $ifaceCustDescrIndex = explodeRows($ifaceCustDescrIndex);
            }

            $this->interfaceParseVSOL($oltid, $ifaceIndex, $VSOLMACsProcessed, $ifaceCustDescrIndex);

            $lastDeregIndexOID = $this->snmpTemplates[$oltModelId]['misc']['DEREGREASON'];
            $lastDeregIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $lastDeregIndexOID, self::SNMPCACHE);
            $lastDeregIndex = str_replace($lastDeregIndexOID . '.', '', $lastDeregIndex);
            $lastDeregIndex = str_replace($this->snmpTemplates[$oltModelId]['misc']['DEREGVALUE'], '', $lastDeregIndex);
            $lastDeregIndex = explodeRows($lastDeregIndex);

            $this->lastDeregParseVSOL($oltid, $lastDeregIndex, $VSOLMACsProcessed);

            if (!$oltNoFDBQ) {
                $fdbIndexOID = $this->snmpTemplates[$oltModelId]['misc']['FDBINDEX'];
                $fdbIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $fdbIndexOID, self::SNMPCACHE);
                $fdbIndex = str_replace($fdbIndexOID . '.', '', $fdbIndex);
                $fdbIndex = str_replace($this->snmpTemplates[$oltModelId]['misc']['FDBVALUE'], '', $fdbIndex);
                $fdbIndex = explodeRows($fdbIndex);

                $fdbMACVLANOID = $this->snmpTemplates[$oltModelId]['misc']['FDBVLANINDEX'];
                $fdbVLANIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $fdbMACVLANOID, self::SNMPCACHE);
                $fdbVLANIndex = str_replace($fdbMACVLANOID . '.', '', $fdbVLANIndex);
                $fdbVLANIndex = str_replace($this->snmpTemplates[$oltModelId]['misc']['FDBVLANVALUE'], '', $fdbVLANIndex);
                $fdbVLANIndex = explodeRows($fdbVLANIndex);

                $this->fdbParseVSOL($oltid, $VSOLMACsProcessed, $fdbIndex, $fdbVLANIndex);
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
     * Processes V-SOLUTION OLT MAC adresses and returns them in array: LLID=>MAC
     *
     * @param $macIndex
     * @param $snmpTemplate
     *
     * @return array
     */
    protected function macParseVSOL($macIndex, $snmpTemplate) {
        $ONUsMACs = array();

        if (!empty($macIndex)) {
//mac index preprocessing
            foreach ($macIndex as $io => $eachmac) {
                $line = explode('=', $eachmac);

                if (empty($line[0]) || empty($line[1])) {
                    continue;
                }

                $tmpONUPortLLID = trim($line[0]);

                if ($snmpTemplate['misc']['GETACTIVEONUMACONLY']) {
                    $tmpONUMAC = rtrim(chunk_split(str_replace(array('"', "0x"), '', trim($line[1])), 2, ':'), ':'); //mac address
                } else {
                    $tmpONUMAC = str_replace('"', '', trim($line[1])); //mac address
                }

//mac is present
                if (!empty($tmpONUPortLLID) and ! empty($tmpONUMAC)) {
                    $ONUsMACs[$tmpONUPortLLID] = $tmpONUMAC;
                }
            }
        }

        return ($ONUsMACs);
    }

    /**
     * Performs signal preprocessing for sig/mac index arrays and stores it into cache
     *
     * @param int $oltid
     * @param array $sigIndex
     * @param array $macIndex
     *
     * @return void
     */
    protected function signalParseVSOL($oltid, $sigIndex, $macIndexProcessed) {
        $ONUsModulesTemps = array();
        $ONUsModulesVoltages = array();
        $ONUsModulesCurrents = array();
        $ONUsSignals = array();
        $result = array();
        $macDevID = array();
        $curDate = curdatetime();
        $oltid = vf($oltid, 3);

//signal index preprocessing
        if ((!empty($sigIndex)) and ( !empty($macIndexProcessed))) {
            foreach ($sigIndex as $io => $eachsig) {
                $line = explode('=', $eachsig);

//signal is present
                if (isset($line[0])) {
                    $tmpOIDParamaterPiece = substr(trim($line[0]), 0, 1);
                    $tmpONUPortLLID = substr(trim($line[0]), 2);

// just because we can't(I dunno why - honestly) just query the
// .1.3.6.1.4.1.37950.1.1.5.12.2.1.8.1.6 and .1.3.6.1.4.1.37950.1.1.5.12.2.1.8.1.7 OIDs
// cause it's simply returns NOTHING - we need to take a start from the higher tree point - .1.3.6.1.4.1.37950.1.1.5.12.2.1.8.1
// and then we can extract all necessary values

                    switch ($tmpOIDParamaterPiece) {
                        case '3':
                            $ONUsModulesTemps[$tmpONUPortLLID] = trim($line[1]); // may be we'll show this somewhere in future
                            break;

                        case '4':
                            $ONUsModulesVoltages[$tmpONUPortLLID] = trim($line[1]); // may be we'll show this somewhere in future
                            break;

                        case '5':
                            $ONUsModulesCurrents[$tmpONUPortLLID] = trim($line[1]); // may be we'll show this somewhere in future
                            break;

// may be we'll show this somewhere in future
                        case '6':
                            $SignalRaw = trim($line[1]);
                            $ONUsSignals[$tmpONUPortLLID]['SignalTXRaw'] = $SignalRaw;
                            $ONUsSignals[$tmpONUPortLLID]['SignalTXdBm'] = trim(substr(stristr(stristr(stristr($SignalRaw, '('), ')', true), 'dBm', true), 1));
                            break;

                        case '7':
                            $SignalRaw = trim($line[1]);
                            $ONUsSignals[$tmpONUPortLLID]['SignalRXRaw'] = $SignalRaw;
                            $ONUsSignals[$tmpONUPortLLID]['SignalRXdBm'] = trim(substr(stristr(stristr(stristr($SignalRaw, '('), ')', true), 'dBm', true), 1));
                            break;
                    }
                }
            }

//storing results
            foreach ($macIndexProcessed as $devId => $eachMac) {
                if (isset($ONUsSignals[$devId])) {
//signal history filling

                    $signal = $ONUsSignals[$devId]['SignalRXdBm'];

                    if (!empty($signal)) {
                        $result[$eachMac] = $signal;
                    }

                    if (empty($signal) or $signal == 'Offline') {
                        $signal = $this->onuOfflineSignalLevel; //over 9000 offline signal level :P
                    }

                    //saving each ONU signal history
                    $this->olt->writeSignalHistory($eachMac, $signal);
                }
            }

            $macDevID = array_flip($macIndexProcessed);


            //writing signals cache
            $this->olt->writeSignals($result);


            //saving ONU cache
            $this->olt->writeOnuCache($macIndexProcessed);

            // saving macindex as MAC => devID
            $this->olt->writeMacIndex($macDevID);
        }
    }

    /**
     * Performs distance preprocessing for distance/mac index arrays and stores it into cache
     *
     * @param $oltid
     * @param $DistIndex
     * @param $macIndexProcessed
     */
    protected function distanceParseVSOL($oltid, $DistIndex, $macIndexProcessed) {
        $ONUDistances = array();
        $result = array();

        if (!empty($macIndexProcessed) and ! empty($DistIndex)) {
//last dereg index preprocessing
            foreach ($DistIndex as $io => $eachRow) {
                $line = explode('=', $eachRow);

                if (empty($line[0]) || empty($line[1])) {
                    continue;
                }

                $tmpONUPortLLID = trim($line[0]);
                $tmpONUDistance = trim($line[1]);

                $ONUDistances[$tmpONUPortLLID] = $tmpONUDistance;
            }

//storing results
            foreach ($macIndexProcessed as $devId => $eachMac) {
                if (isset($ONUDistances[$devId])) {
                    $result[$eachMac] = $ONUDistances[$devId];
                }
            }

            //saving distances
            $this->olt->writeDistances($result);
        }
    }

    /**
     * Performs interface preprocessing for interface/mac index arrays and stores it into cache
     *
     * @param $oltid
     * @param $IfaceIndex
     * @param $macIndexProcessed
     * @param $ifaceCustDescrRaw
     */
    protected function interfaceParseVSOL($oltid, $IfaceIndex, $macIndexProcessed, $ifaceCustDescrRaw = array()) {
        $ONUIfaces = array();
        $result = array();
        $processIfaceCustDescr = !empty($ifaceCustDescrRaw);
        $ifaceCustDescrIdx = array();
        $ifaceCustDescrArr = array();

// olt iface descr extraction
        if ($processIfaceCustDescr) {
            foreach ($ifaceCustDescrRaw as $io => $each) {
                if (empty($each)) {
                    continue;
                }

                $ifDescr = explode('=', str_replace(array(" ", "\t", "\n", "\r", "\0", "\x0B"), '', $each));

                if (empty($ifDescr[0]) && empty($ifDescr[1])) {
                    continue;
                }

                $ifaceCustDescrIdx[$ifDescr[0]] = $ifDescr[1];
            }
        }

        if (!empty($macIndexProcessed) and ! empty($IfaceIndex)) {
//OLT iface index preprocessing
            foreach ($IfaceIndex as $io => $eachRow) {
                if (empty($eachRow)) {
                    continue;
                }

                $line = explode('=', str_replace(array(" ", "\t", "\n", "\r", "\0", "\x0B"), '', $eachRow));

                if (empty($line[0]) || empty($line[1])) {
                    continue;
                }

                $tmpONUPortLLID = trim($line[0]);
                $tmpONUIface = trim($line[1]);

                $ONUIfaces[$tmpONUPortLLID] = $tmpONUIface;
            }

//storing results
            foreach ($macIndexProcessed as $devId => $eachMac) {
                $tPONIfaceNum = substr($devId, 0, 1);

                if (array_key_exists($tPONIfaceNum, $ONUIfaces)) {
                    $tPONIfaceName = $ONUIfaces[$tPONIfaceNum];
                    $tPONIfaceStr = $tPONIfaceName . ' / ' . str_replace('.', ':', $devId);
                    $cleanIface = strstr($tPONIfaceStr, ':', true);

                    if ($processIfaceCustDescr && !isset($ifaceCustDescrArr[$cleanIface]) && array_key_exists($tPONIfaceNum, $ifaceCustDescrIdx)) {
                        $ifaceCustDescrArr[$cleanIface] = $ifaceCustDescrIdx[$tPONIfaceNum];
                    }
                } else {
                    $tPONIfaceStr = str_replace('.', ':', $devId);
                }

                $result[$eachMac] = $tPONIfaceStr;
            }

            //saving ONU interfaces and interfaces descriptions
            $this->olt->writeInterfaces($result);
            $this->olt->writeInterfacesDescriptions($ifaceCustDescrArr);
        }
    }

    /**
     * Parses & stores to cache ONUs FDB cache (MACs behind ONU)
     *
     * @param $oltID
     * @param $onuMACIndex
     * @param $fdbIndex
     * @param $fdbVLANIndex
     */
    protected function fdbParseVSOL($oltID, $onuMACIndex, $fdbIndex, $fdbVLANIndex) {
        $fdbIdxMAC = array();
        $fdbIdxVLAN = array();
        $fdbCahce = array();

        if (!empty($fdbIndex)) {
// processing FDBIndex array to get FDB MAC => pon port number + ONU LLID mapping
            foreach ($fdbIndex as $each => $eachIdx) {
                $line = explode('=', $eachIdx);
// ONU LLID is present
                if (isset($line[1])) {
                    $portLLID = trim(str_replace(array('"', 'EPON0/'), '', $line[1]));        // pon port number + ONU LLID
                    $portLLID = str_replace(':', '.', $portLLID);
                    $fdbMAC = trim(convertMACDec2Hex($line[0]));                            // FDB MAC in dotted DEC format
                    $fdbIdxMAC[$fdbMAC] = $portLLID;             // FDB MAC => pon port number + ONU LLID
                }
            }
        }

        if (!empty($fdbVLANIndex)) {
// processing $fdbVLANIndex array to get FDB MAC => FDB VLAN mapping
            foreach ($fdbVLANIndex as $each => $eachIdx) {
                $line = explode('=', $eachIdx);
// FDB VLAN is present
                if (isset($line[1])) {
                    $fdbVLAN = trim($line[1]);                // pon port number + ONU LLID
                    $fdbMAC = trim(convertMACDec2Hex($line[0]));                            // FDB MAC in dotted DEC format
                    $fdbIdxVLAN[$fdbMAC] = $fdbVLAN;             // FDB MAC => FDB VLAN
                }
            }
        }

        if (!empty($onuMACIndex) and ! empty($fdbIdxMAC)) {
            foreach ($onuMACIndex as $eachLLID => $eachONUMAC) {
                $onuFDBIdxs = array_keys($fdbIdxMAC, $eachLLID);

                if (!empty($onuFDBIdxs)) {
                    $tmpFDBArr = array();

                    foreach ($onuFDBIdxs as $io => $eachFDBMAC) {
                        if (empty($eachFDBMAC) or $eachFDBMAC == $eachONUMAC) {
                            continue;
                        } else {
                            $tmpFDBVLAN = empty($fdbIdxVLAN[$eachFDBMAC]) ? '' : $fdbIdxVLAN[$eachFDBMAC];
                            // not applicable with PON HAL now
                            // i dont know nahooya this was here
                            //$tmpONUID = $this->getONUIDByMAC($eachONUMAC);
                            //$tmpONUID = (empty($tmpONUID)) ? $io : $tmpONUID;
                            $tmpONUID = $io;
                            $tmpFDBArr[$tmpONUID] = array('mac' => $eachFDBMAC, 'vlan' => $tmpFDBVLAN);
                        }
                    }

                    $fdbCahce[$eachONUMAC] = $tmpFDBArr;
                }
            }
        }

        //saving OLT FDB
        $this->olt->writeFdb($fdbCahce);
    }

    /**
     * Performs last dereg reason preprocessing for dereg reason/mac index arrays and stores it into cache
     *
     * @param $oltid
     * @param $LastDeregIndex
     * @param $macIndex
     * @param $snmpTemplate
     */
    protected function lastDeregParseVSOL($oltid, $LastDeregIndex, $macIndexProcessed) {
        $ONUDeRegs = array();
        $result = array();

        if (!empty($macIndexProcessed) and ! empty($LastDeregIndex)) {
//last dereg index preprocessing
            foreach ($LastDeregIndex as $io => $eachRow) {
                $line = explode('=', $eachRow);

                if (empty($line[0]) || empty($line[1])) {
                    continue;
                }

                $tmpONUPortLLID = trim($line[0]);
                $tmpONULastDeregReason = intval(trim($line[1]));

                switch ($tmpONULastDeregReason) {
                    case 0:
                        $TxtColor = '"#F80000"';
                        $tmpONULastDeregReasonStr = 'Wire down';
                        break;

                    case 1:
                        $TxtColor = '"#FF4400"';
                        $tmpONULastDeregReasonStr = 'Power off';
                        break;

                    default:
                        $TxtColor = '"#000000"';
                        $tmpONULastDeregReasonStr = 'Unknown';
                        break;
                }

                if (!empty($tmpONUPortLLID)) {
                    $tmpONULastDeregReasonStr = wf_tag('font', false, '', 'color=' . $TxtColor . '') .
                            $tmpONULastDeregReasonStr .
                            wf_tag('font', true);

                    $ONUDeRegs[$tmpONUPortLLID] = $tmpONULastDeregReasonStr;
                }
            }

//storing results
            foreach ($macIndexProcessed as $devId => $eachMac) {
                if (isset($ONUDeRegs[$devId])) {
                    $result[$eachMac] = $ONUDeRegs[$devId];
                }
            }

            //saving ONUs deregs reasons
            $this->olt->writeDeregs($result);
        }
    }

}
