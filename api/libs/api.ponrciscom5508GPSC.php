<?php

/**
 * OLT RAISECOM ISCOM5508-GPSC GPON hardware abstraction layer
 */
class PONRC5508GPSC extends PONProto {

    /**
     * Receives, preprocess and stores all required data from OLT
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
        $onuSerialPrefix = $this->snmpTemplates[$oltModelId]['signal']['ONUSERIALREMOVEPREFIX'];
        $onuIfacePrefix = $this->snmpTemplates[$oltModelId]['misc']['ONUIFACEPREFIX'];
        $ponIfacePrefix = $this->snmpTemplates[$oltModelId]['misc']['PONIFACEPREFIX'];
        $vlanIfacePrefix = $this->snmpTemplates[$oltModelId]['misc']['VLANIFACEPREFIX'];
        $vlanIfaceNamePrefix = $this->snmpTemplates[$oltModelId]['misc']['VLANIFACENAMEPREFIX'];
        
        $this->onuSerialCaseMode = (isset($this->snmpTemplates[$oltModelId]['onu']['ONU_SERIAL_CASE_MODE'])
                                    ? $this->snmpTemplates[$oltModelId]['onu']['ONU_SERIAL_CASE_MODE'] : 0);

        $signalsIndex = $this->walkCleared($oltIPPORT, $oltCommunity,
                                       $this->snmpTemplates[$oltModelId]['signal']['SIGINDEX'],
                                       '',
                                       array($this->snmpTemplates[$oltModelId]['signal']['SIGVALUE'], '"'),
                                       self::SNMPCACHE);

        $onuSerialsIndex = $this->walkCleared($oltIPPORT, $oltCommunity,
                                       $this->snmpTemplates[$oltModelId]['signal']['ONUSERIALINDEX'],
                                       '',
                                       array($this->snmpTemplates[$oltModelId]['signal']['ONUSERIALVALUE'], '"', $onuSerialPrefix),
                                       self::SNMPCACHE);

        $ifaceDescrIndex = $this->walkCleared($oltIPPORT, $oltCommunity,
                                       $this->snmpTemplates[$oltModelId]['misc']['IFACEDESCR'],
                                       '',
                                       array($this->snmpTemplates[$oltModelId]['misc']['IFACEVALUE'], '"', $onuIfacePrefix),
                                       self::SNMPCACHE);   
                                       
        $ifaceNamesIndex = $this->walkCleared($oltIPPORT, $oltCommunity,
                                       $this->snmpTemplates[$oltModelId]['misc']['IFACENAME'],
                                       '',
                                       array($this->snmpTemplates[$oltModelId]['misc']['IFACEVALUE'], '"', $onuIfacePrefix),
                                       self::SNMPCACHE);                                      

        $rcomgSerialsProcessed = $this->onuSerialsParse($onuSerialsIndex);                                                   

        if (!empty($rcomgSerialsProcessed)) {
            $rcomgSerialsIfacesProcessed = $this->interfacesParseRCOMG($rcomgSerialsProcessed, $ifaceDescrIndex, $ifaceNamesIndex);
            $this->signalsParseRCOMG($signalsIndex, $rcomgSerialsIfacesProcessed);

            // $distIndex = $this->walkCleared($oltIPPORT, $oltCommunity,
            //                                 $this->snmpTemplates[$oltModelId]['misc']['DISTINDEX'],
            //                                 '',
            //                                 array($this->snmpTemplates[$oltModelId]['misc']['DISTVALUE'], '"'),
            //                                 self::SNMPCACHE);

            // $this->distanceParseGCOMG($distIndex, $rcomgSerialsProcessed);


            // $lastDeregIndex = $this->walkCleared($oltIPPORT, $oltCommunity,
            //                                      $this->snmpTemplates[$oltModelId]['misc']['DEREGREASON'],
            //                                      '',
            //                                      array($this->snmpTemplates[$oltModelId]['misc']['IFACEVALUE'], '"'),
            //                                      self::SNMPCACHE);

            // $this->lastDeregParseGCOMG($lastDeregIndex, $rcomgSerialsProcessed);

            // if (!$oltNoFDBQ) {
            //     // for some reason fdbVLANIndex for this OLT should be queried first
            //     // to prevent losing of the very first record from fdbVLANIndex
            //     $fdbVLANIndex = $this->walkCleared($oltIPPORT, $oltCommunity,
            //                                        $this->snmpTemplates[$oltModelId]['misc']['FDBVLANINDEX'],
            //                                        '',
            //                                        $this->snmpTemplates[$oltModelId]['misc']['FDBVLANVALUE'],
            //                                        self::SNMPCACHE);

            //     $fdbIndex = $this->walkCleared($oltIPPORT, $oltCommunity,
            //                                    $this->snmpTemplates[$oltModelId]['misc']['FDBMACINDEX'],
            //                                    '',
            //                                    $this->snmpTemplates[$oltModelId]['misc']['FDBMACVALUE'],
            //                                    self::SNMPCACHE);

            //     $this->fdbParseGCOMG($rcomgSerialsProcessed, $fdbIndex, $fdbVLANIndex);
            // }

            // $uniOperStatusIndex = array();
            // if (isset($this->snmpTemplates[$oltModelId]['misc']['UNIOPERSTATUS'])) {
            //     $uniOperStatusIndex = $this->walkCleared($oltIPPORT, $oltCommunity,
            //                                              $this->snmpTemplates[$oltModelId]['misc']['UNIOPERSTATUS'],
            //                                              '',
            //                                              array($this->snmpTemplates[$oltModelId]['misc']['UNIOPERSTATUSVALUE'], '"'),
            //                                              self::SNMPCACHE);

            //     $this->uniParseGCOMG($uniOperStatusIndex, $rcomgSerialsProcessed);
            // }
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
     * Processes OLT MAC adresses and returns them in array: LLID=>MAC
     *
     * @param $onuSerialsIndex
     *
     * @return array
     */
    protected function onuSerialsParse($onuSerialsIndex) {
        $onuSerials = array();

        if (!empty($onuSerialsIndex)) {
//serials index preprocessing
            foreach ($onuSerialsIndex as $io => $eachSerial) {
                $line = explode('=', $eachSerial);

                if (empty($line[0]) || empty($line[1])) {
                    continue;
                }

                $onuPlasticIdx = trim($line[0]);
//                $tmpONUMAC = strtolower(AddMacSeparator(RemoveMacAddressSeparator(trim($line[1]), array(':', '-', '.', ' '))));     //mac address
                $tmpONUSerial = trim($line[1]);

                if ($this->onuSerialCaseMode == 1) {
                    $tmpONUSerial = strtolower($tmpONUSerial);
                } elseif ($this->onuSerialCaseMode == 2) {
                    $tmpONUSerial = strtoupper($tmpONUSerial);
                }


//mac is present
                if (!empty($onuPlasticIdx) and !empty($tmpONUSerial)) {
                    $onuSerials[$onuPlasticIdx] = $tmpONUSerial;
                }
            }
        }

        return ($onuSerials);
    }

    /**
     * Performs interface preprocessing for interface/mac index arrays and stores it into cache
     *
     * @param $IfaceIndex
     * @param $onuSerialsIndexProcessed
     * @param $ifaceCustDescrRaw
     *
     * @return void
     */
    protected function interfacesParseRCOMG($serialsProcessed, $ifaceDescrIndex, $ifaceNamesIndex) {
        $ONUIfaces = array();
        $PONIfaces = array();        
        $ifaceIdxNameArr = array();
        $ifaceIdxDescrArr = array();

// iface names processing
        if (!empty($ifaceNamesIndex)) {
            foreach ($ifaceNamesIndex as $io => $eachIfaceName) {
                if (empty($eachIfaceName)) {
                    continue;
                }

                $tmpIface = explode('=', str_replace(array(" ", "\t", "\n", "\r", "\0", "\x0B"), '', $eachIfaceName));
                $tmpPlasticIdx = trim($tmpIface[0]);
                $tmpIfaceName = trim($tmpIface[1]);

                if (empty($tmpPlasticIdx) || empty($tmpIfaceName)) {
                    continue;
                }

                $ifaceIdxNameArr[$tmpPlasticIdx] = $tmpIfaceName;
            }
        }

// iface descrs processing
        if (!empty($ifaceDescrIndex)) {
            foreach ($ifaceDescrIndex as $io => $eachIfaceDescr) {
                if (empty($eachIfaceName)) {
                    continue;
                }

                $tmpIface = explode('=', str_replace(array(" ", "\t", "\n", "\r", "\0", "\x0B"), '', $eachIfaceDescr));
                $tmpPlasticIdx = trim($tmpIface[0]);
                $tmpIfaceDescr = trim($tmpIface[1]);

// if descr is empty for some reason - try to substitute with value from iface names array                
                if (empty($tmpIfaceDescr) and !empty($ifaceIdxNameArr[$tmpPlasticIdx])) {
                    $tmpIfaceDescr = $ifaceIdxNameArr[$tmpPlasticIdx];
                }

                if (empty($tmpPlasticIdx) || empty($tmpIfaceDescr)) {
                    continue;
                }

                $ifaceIdxDescrArr[$tmpPlasticIdx] = $tmpIfaceDescr;
            }
        }        

        if (!empty($serialsProcessed) and !empty($ifaceIdxDescrArr)) {
//ONU serials to ifaces mapping
            foreach ($ifaceIdxDescrArr as $eachPlasticIdx => $eachIface) {
                if (empty($eachIface)) {
                    continue;
                }

// storing ONU's LLID in a form of ["onuSerial" => "boardID/ponPortID:ONUID"]
                if (!empty($serialsProcessed[$eachPlasticIdx])) {
                    $tmpONUSerial = $serialsProcessed[$eachPlasticIdx];
                    $tmpONUBoardPortLLID = substr_replace($eachIface, ':', strrpos($eachIface, '/'), 1);                    
                    $ONUIfaces[$tmpONUSerial] = $tmpONUBoardPortLLID;
                }
        
                if (strpos($eachIface, $ponIfacePrefix)) {
                    $tmpPONBoardPort = str_replace($ponIfacePrefix, '', $eachIface);
// as the iface descr is smotheing like this: "gpon-olt3/1" - it's a simple way to add space between numbers and name to get "gpon-olt 3/1"                   
                    $tmpPONIfaceDescr = $ponIfacePrefix . ' ' . $tmpPONBoardPort;
                    $PONIfaces[$tmpPONBoardPort] = $tmpPONIfaceDescr;
                }
            }

//storing results
            // foreach ($onuSerialsIndexProcessed as $devId => $eachMac) {
            //     $tPONIfaceNum = substr($devId, 0, 1);

            //     if (array_key_exists($tPONIfaceNum, $ONUIfaces)) {
            //         $tPONIfaceName = $ONUIfaces[$tPONIfaceNum];
            //         $tPONIfaceStr = $tPONIfaceName . ' / ' . str_replace('.', ':', $devId);
            //         $cleanIface = strstr($tPONIfaceStr, ':', true);

            //         if ($processIfaceCustDescr && !isset($ifaceCustDescrArr[$cleanIface]) && array_key_exists($tPONIfaceNum, $ifaceCustDescrIdx)) {
            //             $ifaceCustDescrArr[$cleanIface] = $ifaceCustDescrIdx[$tPONIfaceNum];
            //         }
            //     } else {
            //         $tPONIfaceStr = str_replace('.', ':', $devId);
            //     }

            //     $result[$eachMac] = $tPONIfaceStr;
            // }

//saving ONU interfaces and interfaces descriptions
            $this->olt->writeInterfaces($ONUIfaces);
            $this->olt->writeInterfacesDescriptions($PONIfaces);
        }
    }


    /**
     * Performs signal preprocessing for sig/mac index arrays and stores it into cache
     *
     * @param array $signalsIndex
     * @param array $onuSerialsIndexProcessed
     *
     * @return void
     */
    protected function signalsParseRCOMG($signalsIndex, $onuSerialsIndexProcessed) {
        $ONUsModulesTemps = array();
        $ONUsModulesVoltages = array();
        $ONUsModulesCurrents = array();
        $ONUsSignals = array();
        $result = array();
        $macDevID = array();
        $curDate = curdatetime();

//signal index preprocessing
        if ((!empty($signalsIndex)) and ( !empty($onuSerialsIndexProcessed))) {
            foreach ($signalsIndex as $io => $eachsig) {
                if (empty($eachsig) or !ispos($eachsig, '=')) { continue; }

                $line = explode('=', $eachsig);

//signal is present
                if (isset($line[0])) {
                    $tmpONUPortLLID = trim($line[0]);
                    $SignalRaw = trim($line[1]);
//                    $ONUsSignals[$tmpONUPortLLID]['SignalRXRaw'] = trim($SignalRaw, '"');
//                    $ONUsSignals[$tmpONUPortLLID]['SignalRXdBm'] = trim(substr(stristr(stristr(stristr($SignalRaw, '('), ')', true), 'dBm', true), 1));
                    $ONUsSignals[$tmpONUPortLLID]['SignalRXdBm'] = trim($SignalRaw);
                    }
                }

//storing results
            foreach ($onuSerialsIndexProcessed as $devId => $eachMac) {
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

            $macDevID = array_flip($onuSerialsIndexProcessed);

            //writing signals cache
            $this->olt->writeSignals($result);

            //saving ONU cache
            $this->olt->writeOnuCache($onuSerialsIndexProcessed);

            // saving macindex as MAC => devID
            $this->olt->writeMacIndex($macDevID);
        }
    }

    /**
     * Performs distance preprocessing for distance/mac index arrays and stores it into cache
     *
     * @param $DistIndex
     * @param $onuSerialsIndexProcessed
     */
    protected function distanceParseGCOMG($DistIndex, $onuSerialsIndexProcessed) {
        $ONUDistances = array();
        $result = array();

        if (!empty($onuSerialsIndexProcessed) and ! empty($DistIndex)) {
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
            foreach ($onuSerialsIndexProcessed as $devId => $eachMac) {
                if (isset($ONUDistances[$devId])) {
                    $result[$eachMac] = $ONUDistances[$devId];
                }
            }

            //saving distances
            $this->olt->writeDistances($result);
        }
    }

    /**
     * Parses & stores to cache ONUs FDB cache (MACs behind ONU)
     *
     * @param $onuMACIndex
     * @param $fdbIndex
     * @param $fdbVLANIndex
     *
     * @return void
     */
    protected function fdbParseGCOMG($onuMACIndex, $fdbIndex, $fdbVLANIndex) {
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
     * @param $onuSerialsIndexProcessed
     *
     * @return void
     */
    protected function lastDeregParseGCOMG($LastDeregIndex, $onuSerialsIndexProcessed) {
        $ONUDeRegs = array();
        $result = array();

        if (!empty($onuSerialsIndexProcessed) and ! empty($LastDeregIndex)) {
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
            foreach ($onuSerialsIndexProcessed as $devId => $eachMac) {
                if (isset($ONUDeRegs[$devId])) {
                    $result[$eachMac] = $ONUDeRegs[$devId];
                }
            }

            //saving ONUs deregs reasons
            $this->olt->writeDeregs($result);
        }
    }

    /**
     * Performs UNI port oper status preprocessing for index array and stores it into cache
     *
     * @param $uniOperStatusIndex
     * @param $onuSerialsIndexProcessed
     *
     * @return void
     */
    protected function uniParseGCOMG($uniOperStatusIndex, $onuSerialsIndexProcessed) {
        $uniStats = array();
        $result = array();

        if (!empty($onuSerialsIndexProcessed) and !empty($uniOperStatusIndex)) {
//UniOperStats index preprocessing
            foreach ($uniOperStatusIndex as $io => $eachRow) {
                $line = explode('=', $eachRow);

                if (empty($line[0]) || empty($line[1])) {
                    continue;
                }

                // LLID + ether port index
                $tmpLLIDEtherIdx = trim($line[0]);
                $tmpLLIDEtherIdxLen = strlen($tmpLLIDEtherIdx);

                // ehter port index
                $tmpEtherIdx = strrchr($tmpLLIDEtherIdx, '.');
                $tmpEtherIdxLen = strlen($tmpEtherIdx);
                $tmpEtherIdx = 'eth' . trim($tmpEtherIdx, '.');

                //LLID
                $tmpONUPortLLID = substr($tmpLLIDEtherIdx, 0, $tmpLLIDEtherIdxLen - $tmpEtherIdxLen);
                $tmpUniStatus = trim(trim($line[1]), '"');

                $uniStats[$tmpONUPortLLID] = array($tmpEtherIdx => $tmpUniStatus);
            }

//storing results
            foreach ($onuSerialsIndexProcessed as $devId => $eachMac) {
                if (isset($uniStats[$devId])) {
                    $result[$eachMac] = $uniStats[$devId];
                }
            }

            //saving UniOperStats
            $this->olt->writeUniOperStats($result);
        }
    }
}
