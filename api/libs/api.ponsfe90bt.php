<?php

/**
 * OLT SmartFiber E9004-BT hardware abstraction layer
 */
class PONSFE90BT extends PONProto {

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
        $oltIPPORT = $oltIp . ':' . self::SNMPPORT;

        $sigIndex = $this->walkCleared($oltIPPORT, $oltCommunity,
                                       $this->snmpTemplates[$oltModelId]['signal']['SIGINDEX'],
                                       $this->snmpTemplates[$oltModelId]['signal']['SIGVALUE'],
                                       self::SNMPCACHE);

        $macIndex = $this->walkCleared($oltIPPORT, $oltCommunity,
                                      $this->snmpTemplates[$oltModelId]['signal']['MACINDEX'],
                                      $this->snmpTemplates[$oltModelId]['signal']['MACVALUE'],
                                      self::SNMPCACHE);

        $SFMACsProcessed = $this->macParseSF($macIndex);

        if (!empty($SFMACsProcessed)) {
            $this->signalParseSF($sigIndex, $SFMACsProcessed);

            $distIndex = $this->walkCleared($oltIPPORT, $oltCommunity,
                                            $this->snmpTemplates[$oltModelId]['misc']['DISTINDEX'],
                                            $this->snmpTemplates[$oltModelId]['misc']['DISTVALUE'],
                                            self::SNMPCACHE);

            $this->distanceParseSF($distIndex, $SFMACsProcessed);

            $ifaceIndex = $this->walkCleared($oltIPPORT, $oltCommunity,
                                             $this->snmpTemplates[$oltModelId]['misc']['IFACEDESCR'],
                                             array($this->snmpTemplates[$oltModelId]['misc']['IFACEVALUE'], '"'),
                                             self::SNMPCACHE);

            $ifaceCustDescrIndex = array();
            if (isset($this->snmpTemplates[$oltModelId]['misc']['IFACECUSTOMDESCR'])) {
                $ifaceCustDescrIndex = $this->walkCleared($oltIPPORT, $oltCommunity,
                                                          $this->snmpTemplates[$oltModelId]['misc']['IFACECUSTOMDESCR'],
                                                          array($this->snmpTemplates[$oltModelId]['misc']['IFACEVALUE'], '"'),
                                                          self::SNMPCACHE);
            }

            $this->interfaceParseSF($oltid, $ifaceIndex, $SFMACsProcessed, $ifaceCustDescrIndex);

            $lastDeregIndex = $this->walkCleared($oltIPPORT, $oltCommunity,
                                                 $this->snmpTemplates[$oltModelId]['misc']['DEREGREASON'],
                                                 $this->snmpTemplates[$oltModelId]['misc']['DEREGVALUE'],
                                                 self::SNMPCACHE);

            $this->lastDeregParseSF($lastDeregIndex, $SFMACsProcessed);

            if (!$oltNoFDBQ) {
                // for some reason fdbVLANIndex for this OLT should be queried first
                // to prevent losing of the very first record from fdbVLANIndex
                $fdbVLANIndex = $this->walkCleared($oltIPPORT, $oltCommunity,
                                                   $this->snmpTemplates[$oltModelId]['misc']['FDBVLANINDEX'],
                                                   $this->snmpTemplates[$oltModelId]['misc']['FDBVLANVALUE'],
                                                   self::SNMPCACHE);

                $fdbIndex = $this->walkCleared($oltIPPORT, $oltCommunity,
                                               $this->snmpTemplates[$oltModelId]['misc']['FDBMACINDEX'],
                                               $this->snmpTemplates[$oltModelId]['misc']['FDBMACVALUE'],
                                               self::SNMPCACHE);

                $this->fdbParseSF($SFMACsProcessed, $fdbIndex, $fdbVLANIndex);
            }
        }


        //getting others system data from OLT
        if (isset($this->snmpTemplates[$oltModelId]['system'])) {
            //OLT uptime
            if (isset($this->snmpTemplates[$oltModelId]['system']['UPTIME'])) {
                $uptimeIndexOid = $this->snmpTemplates[$oltModelId]['system']['UPTIME'];
                $oltSystemUptimeRaw = $this->snmp->walk($oltIPPORT, $oltCommunity, $uptimeIndexOid, self::SNMPCACHE);
                $this->uptimeParse($oltid, $oltSystemUptimeRaw);
            }

            //OLT temperature
            if (isset($this->snmpTemplates[$oltModelId]['system']['TEMPERATURE'])) {
                $temperatureIndexOid = $this->snmpTemplates[$oltModelId]['system']['TEMPERATURE'];
                $oltTemperatureRaw = $this->snmp->walk($oltIPPORT, $oltCommunity, $temperatureIndexOid, self::SNMPCACHE);
                $this->temperatureParse($oltid, $oltTemperatureRaw);
            }
        }
    }

    /**
     * Processes V-SOLUTION OLT MAC adresses and returns them in array: LLID=>MAC
     *
     * @param $macIndex
     *
     * @return array
     */
    protected function macParseSF($macIndex) {
        $ONUsMACs = array();

        if (!empty($macIndex)) {
//mac index preprocessing
            foreach ($macIndex as $io => $eachmac) {
                $line = explode('=', $eachmac);

                if (empty($line[0]) || empty($line[1])) {
                    continue;
                }

                $tmpONUPortLLID = trim($line[0]);
                $tmpONUMAC = strtolower(AddMacSeparator(RemoveMacAddressSeparator(trim($line[1]), array(':', '-', '.', ' '))));     //mac address


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
     * @param array $sigIndex
     * @param array $macIndexProcessed
     *
     * @return void
     */
    protected function signalParseSF($sigIndex, $macIndexProcessed) {
        $ONUsModulesTemps = array();
        $ONUsModulesVoltages = array();
        $ONUsModulesCurrents = array();
        $ONUsSignals = array();
        $result = array();
        $macDevID = array();
        $curDate = curdatetime();

//signal index preprocessing
        if ((!empty($sigIndex)) and ( !empty($macIndexProcessed))) {
            foreach ($sigIndex as $io => $eachsig) {
                $line = explode('=', $eachsig);

//signal is present
                if (isset($line[0])) {
                    $tmpONUPortLLID = trim($line[0]);
                    $SignalRaw = trim($line[1]);
                    $ONUsSignals[$tmpONUPortLLID]['SignalRXRaw'] = $SignalRaw;
                    $ONUsSignals[$tmpONUPortLLID]['SignalRXdBm'] = trim(substr(stristr(stristr(stristr($SignalRaw, '('), ')', true), 'dBm', true), 1));
                    }
                }

//storing results
            foreach ($macIndexProcessed as $devId => $eachMac) {
                if (isset($ONUsSignals[$devId])) {
//signal history filling
                    $signal = $ONUsSignals[$devId]['SignalRXdBm'];

                    if (!empty($signal)) {
                        $signal = round($signal, 2);
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
     * @param $DistIndex
     * @param $macIndexProcessed
     */
    protected function distanceParseSF($DistIndex, $macIndexProcessed) {
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
     * @param $IfaceIndex
     * @param $macIndexProcessed
     * @param $ifaceCustDescrRaw
     */
    protected function interfaceParseSF($oltid, $IfaceIndex, $macIndexProcessed, $ifaceCustDescrRaw = array()) {
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

                if (empty($line[0]) || empty($line[1]) || trim($line[0]) < 9) {
                    continue;
                }

                $tmpONUPortLLID = trim($line[0]) - 8;   // some shitty math
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
     * @param $onuMACIndex
     * @param $fdbIndex
     * @param $fdbVLANIndex
     */
    protected function fdbParseSF($onuMACIndex, $fdbIndex, $fdbVLANIndex) {
        if (!empty($onuMACIndex)) {
            $fdbDevIdxMAC   = array();
            $fdbIdxVLAN     = array();
            $fdbCahce       = array();

            if (!empty($fdbIndex)) {
// processing FDBIndex array to get pon [port number + ONU LLID + dev idx => FDB MAC] mapping
                foreach ($fdbIndex as $each => $eachIdx) {
                    $line = explode('=', $eachIdx);
// FDB MAC is present
                    if (isset($line[1])) {
                        $fdbMAC = trim(str_replace(array('"', 'STRING:', 'dev/ro'), '', $line[1]));

                        if (empty($fdbMAC)) continue;

                        $fdbMAC = strtolower(AddMacSeparator(RemoveMacAddressSeparator($fdbMAC, array(':', '-', '.', ' '))));  // FDB MAC in space-separated format
                        $portLLIDDevIdx = trim($line[0]);                           // pon port number + ONU LLID + dev idx
                        //$portLLID = substr($portLLIDDevIdx, 0, 3);      // pon port number + ONU LLID

                        $fdbDevIdxMAC[$portLLIDDevIdx] = $fdbMAC;       // pon port number + ONU LLID + dev idx => FDB MAC
                        //$fdbIdxMAC[$fdbMAC] = $portLLID;                // FDB MAC => pon port number + ONU LLID
                    }
                }
            }

            if (!empty($fdbVLANIndex)) {
// processing $fdbVLANIndex array to get [pon port number + ONU LLID + dev idx mapping => FDB VLAN] mapping
                foreach ($fdbVLANIndex as $each => $eachIdx) {
                    $line = explode('=', $eachIdx);
// FDB VLAN is present
                    if (isset($line[1])) {
                        $fdbVLAN = trim($line[1]);                      // FDB VLAN
                        $portLLIDDevIdx = trim($line[0]);               // pon port number + ONU LLID + dev idx
                        $fdbIdxVLAN[$portLLIDDevIdx] = $fdbVLAN;        // pon port number + ONU LLID + dev idx => FDB MAC
                    }
                }
            }

            if (!empty($fdbDevIdxMAC)) {
// processing $fdbIdxMAC and $fdbIdxVLAN to prepare [pon port number + ONU LLID => ['mac' => $eachFDBMAC, 'vlan' => $tmpFDBVLAN]] array
                foreach ($fdbDevIdxMAC as $eachPortLLIDDevIdx => $eachFDBMAC) {
                    $portLLID = substr($eachPortLLIDDevIdx, 0, 3);
                    $devIdx = substr($eachPortLLIDDevIdx, -1, 1);
                    $tmpFDBVLAN = empty($fdbIdxVLAN[$eachPortLLIDDevIdx]) ? '' : $fdbIdxVLAN[$eachPortLLIDDevIdx];
                    $fdbMACVLAN[$portLLID][$devIdx] = array('mac' => $eachFDBMAC, 'vlan' => $tmpFDBVLAN);
                }

                if (!empty($fdbMACVLAN)) {
                    foreach ($onuMACIndex as $eachLLID => $eachONUMAC) {
                        if (!empty($fdbMACVLAN[$eachLLID])) {
                            $fdbCahce[$eachONUMAC] = $fdbMACVLAN[$eachLLID];
                        }
                    }
                }
            }

            //saving OLT FDB
            $this->olt->writeFdb($fdbCahce);
        }
    }

    /**
     * Performs last dereg reason preprocessing for dereg reason/mac index arrays and stores it into cache
     *
     * @param $LastDeregIndex
     * @param $macIndex
     * @param $snmpTemplate
     */
    protected function lastDeregParseSF($LastDeregIndex, $macIndexProcessed) {
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
                    case 1:
                        $TxtColor = '"#F80000"';
                        $tmpONULastDeregReasonStr = 'Wire down';
                        break;

                    case 3:
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
