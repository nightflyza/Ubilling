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
            $rcomgSerialsIfacesProcessed = $this->interfacesParseRCOMG($rcomgSerialsProcessed, $ifaceDescrIndex, $ifaceNamesIndex, $ponIfacePrefix);
            $this->signalsParseRCOMG($signalsIndex, $rcomgSerialsIfacesProcessed);

            if (isset($this->snmpTemplates[$oltModelId]['misc']['DISTINDEX'])) {
                $distIndex = $this->walkCleared($oltIPPORT, $oltCommunity,
                                                $this->snmpTemplates[$oltModelId]['misc']['DISTINDEX'],
                                                '',
                                                array($this->snmpTemplates[$oltModelId]['misc']['DISTVALUE'], '"'),
                                                self::SNMPCACHE);

                $this->distanceParseRCOMG($distIndex, $rcomgSerialsProcessed);
            }

            if (isset($this->snmpTemplates[$oltModelId]['misc']['DEREGREASON'])) {
                $lastDeregIndex = $this->walkCleared($oltIPPORT, $oltCommunity,
                                                    $this->snmpTemplates[$oltModelId]['misc']['DEREGREASON'],
                                                    '',
                                                    array($this->snmpTemplates[$oltModelId]['misc']['DEREGVALUE'], '"'),
                                                    self::SNMPCACHE);

                $this->lastDeregParseRCOMG($lastDeregIndex, $rcomgSerialsProcessed);
            }

            $uniOperStatusIndex = array();
            $uniDuplexSpeedIndex = array();
            if (isset($this->snmpTemplates[$oltModelId]['misc']['UNIOPERSTATUS'])) {
                $uniOperStatusIndex = $this->walkCleared($oltIPPORT, $oltCommunity,
                                                         $this->snmpTemplates[$oltModelId]['misc']['UNIOPERSTATUS'],
                                                         '',
                                                         array($this->snmpTemplates[$oltModelId]['misc']['UNIOPERSTATUSVALUE'], '"'),
                                                         self::SNMPCACHE);                
            }
            
            if (isset($this->snmpTemplates[$oltModelId]['misc']['UNIDUPLEXSPEED'])) {
                $uniDuplexSpeedIndex = $this->walkCleared($oltIPPORT, $oltCommunity,
                                                         $this->snmpTemplates[$oltModelId]['misc']['UNIDUPLEXSPEED'],
                                                         '',
                                                         array($this->snmpTemplates[$oltModelId]['misc']['UNIDUPLEXSPEEDVALUE'], '"'),
                                                         self::SNMPCACHE);
            }

            $this->uniParseRCOMG($rcomgSerialsIfacesProcessed, $uniOperStatusIndex, $uniDuplexSpeedIndex);

            if (!$oltNoFDBQ) {                
                $fdbVLANIndex = $this->walkCleared($oltIPPORT, $oltCommunity,
                                                   $this->snmpTemplates[$oltModelId]['misc']['FDBVLANINDEX'],
                                                   '',
                                                   $this->snmpTemplates[$oltModelId]['misc']['FDBVLANVALUE'],
                                                   self::SNMPCACHE);

                $fdbMACIndex = $this->walkCleared($oltIPPORT, $oltCommunity,
                                               $this->snmpTemplates[$oltModelId]['misc']['FDBMACINDEX'],
                                               '',
                                               $this->snmpTemplates[$oltModelId]['misc']['FDBMACVALUE'],
                                               self::SNMPCACHE,
                                               false, true, '');

                $this->fdbParseRCOMG($rcomgSerialsIfacesProcessed, $fdbMACIndex, $fdbVLANIndex);
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
     * @return array
     */
    protected function interfacesParseRCOMG($serialsProcessed, $ifaceDescrIndex, $ifaceNamesIndex, $ponIfacePrefix) {
        $ONUIfacesArr = array();
        $PONIfacesArr = array();        
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
                if (empty($eachIfaceDescr)) {
                    continue;
                }

                $tmpIface = explode('=', str_replace(array(" ", "\t", "\n", "\r", "\0", "\x0B"), '', $eachIfaceDescr));
                $tmpPlasticIdx = trim($tmpIface[0]);
                $tmpIfaceDescr = trim($tmpIface[1]);

                if (empty($tmpPlasticIdx) || empty($tmpIfaceDescr)) {
                    continue;
                }

                $ifaceIdxDescrArr[$tmpPlasticIdx] = $tmpIfaceDescr;
            }
        }        

        if (!empty($serialsProcessed) and !empty($ifaceIdxNameArr)) {
//ONU serials to ifaces mapping
            foreach ($ifaceIdxNameArr as $eachPlasticIdx => $eachIface) {
                if (empty($eachIface)) {
                    continue;
                }

// storing ONU's LLID in a form of ["onuSerial" => "boardID/ponPortID:ONUID"]
                if (!empty($serialsProcessed[$eachPlasticIdx])) {
                    $tmpONUSerial = $serialsProcessed[$eachPlasticIdx];
                    $tmpONUBoardPortLLID = substr_replace($eachIface, ':', strrpos($eachIface, '/'), 1);                    
                    $ONUIfacesArr[$tmpONUSerial] = $tmpONUBoardPortLLID;
                }
        
                if (strpos($eachIface, $ponIfacePrefix)) {
                    $tmpPONBoardPort = str_replace($ponIfacePrefix, '', $eachIface);

// if there is some custom descr for current PON port - use it instead of value from iface names array                
                    if (!empty($ifaceIdxDescrArr[$eachPlasticIdx])) {
                        $tmpPONIfaceDescr = $ifaceIdxDescrArr[$eachPlasticIdx];
                    } else {
// as the iface descr is smotheing like this: "gpon-olt3/1" - it's a simple way to add space between numbers and name to get "gpon-olt 3/1"                   
                        $tmpPONIfaceDescr = $ponIfacePrefix . ' ' . $tmpPONBoardPort;
                    }    

                    $PONIfacesArr[$tmpPONBoardPort] = $tmpPONIfaceDescr;
                }
            }

//saving ONU interfaces and interfaces descriptions
            $this->olt->writeInterfaces($ONUIfacesArr);
            $this->olt->writeInterfacesDescriptions($PONIfacesArr);
        }

        return ($ONUIfacesArr);
    }


    /**
     * Performs signal preprocessing for sig/mac index arrays and stores it into cache
     *
     * @param array $signalsIndex
     * @param array $onuSerialsIndexProcessed
     *
     * @return void
     */
    protected function signalsParseRCOMG($signalsIndex, $onuSerialsIfacesProcessed) {
        $ONUsModulesTemps = array();
        $ONUsModulesVoltages = array();
        $ONUsModulesCurrents = array();
        $ONUsSignals = array();
        $result = array();
        $llidONUSerial = array_flip($onuSerialsIfacesProcessed);
        $curDate = curdatetime();

//signal index preprocessing
        if (!empty($signalsIndex) and !empty($llidONUSerial)) {
            foreach ($signalsIndex as $io => $eachSignal) {
                if (empty($eachSignal) or !ispos($eachSignal, '=')) { continue; }

                $line = explode('=', $eachSignal);

//signal is present
                if (isset($line[0])) {
                    $onuBoardPortLLIDRaw = str_replace('.', '', trim($line[0]));
                    $onuSignalRaw = trim($line[1]);
// now some shitty math comes out for getting both - LLIDs and signals from raw values
// actual signal value from the raw value can be obtained via 2 formulas
//  the official one:            (signal_raw - 15000) / 500
//  or the semi-official one:    signal_raw / 500 - 30
// the formulas are pretty exchangable mathematically, but let's stick to the official one

                    $bpllFirstDigit = substr($onuBoardPortLLIDRaw, 0, 1);
                    if (!in_array($bpllFirstDigit, array('1', '3', '8'))) { continue; }
                    
                    $tmpBoardPortLLID = $this->getBoardPortLLIDFromRAW($onuBoardPortLLIDRaw, $bpllFirstDigit);
                    $tmpONUSignal = round(intval((trim($onuSignalRaw)) - 15000) / 500, 2);

                    $ONUsSignals[$tmpBoardPortLLID]['SignalRXdBm'] = $tmpONUSignal;
                }
            }

//storing results
            foreach ($llidONUSerial as $eachLLID => $eachSerial) {
                if (isset($ONUsSignals[$eachLLID])) {
//signal history filling
                    $signal = $ONUsSignals[$eachLLID]['SignalRXdBm'];

                    if (!empty($signal)) {
                        $result[$eachSerial] = $signal;
                    }

                    if (empty($signal) or $signal == 'Offline') {
                        $signal = $this->onuOfflineSignalLevel; //over 9000 offline signal level :P
                    }

                    //saving each ONU signal history
                    $this->olt->writeSignalHistory($eachSerial, $signal);
                }
            }

            //writing signals cache
            $this->olt->writeSignals($result);

            //saving ONU cache
            $this->olt->writeOnuCache($onuSerialsIfacesProcessed);

            // saving macindex as MAC => devID
            $this->olt->writeMacIndex($llidONUSerial);
        }
    }

    /**
     * Calculates and returns ONU's Board and PON port index from raw LLID value 
     *
     * @param string $onuBoardPortLLIDRaw
     * @param string $bpllFirstDigit
     * 
     * @return string
     */
    protected function getBoardPortLLIDFromRAW($onuBoardPortLLIDRaw, $bpllFirstDigit) {
// The "math" for LLIDs is just mind-blowing
//  if the LLID raw value strarts from 1 or 3 - it contains the actual LLIDs from 1 to 99
//      and
//      - the 1st digit is the BOARD index(number)
//      - the 3rd digit is the PON PORT index(number)
//      - the 4th and 5th digits are the actual LLID index(number)
//  if the LLID raw value strarts from 8 - it contains the actual LLIDs from 100 to 128
//      and
//      - the 2nd digit is the BOARD index(number)
//      - the 4th digit MINUS 3 is the PON PORT index(number)
//      - the 5th and 6th digits PLUS 94 are the actual LLID index(number)        
        $result = '';

        if ($bpllFirstDigit == '1' or $bpllFirstDigit == '3') {
            $tmpBoardIdx = substr($onuBoardPortLLIDRaw, 0, 1);
            $tmpPONPortIdx = substr($onuBoardPortLLIDRaw, 2, 1);
            $tmpLLIDIdx = intval(substr($onuBoardPortLLIDRaw, 3, 2));
        } elseif ($bpllFirstDigit == '8') {
            $tmpBoardIdx = substr($onuBoardPortLLIDRaw, 1, 1);
            $tmpPONPortIdx = intval(substr($onuBoardPortLLIDRaw, 3, 1)) - 3;
            $tmpLLIDIdx = intval(substr($onuBoardPortLLIDRaw, 4, 2)) + 94;
        } else { return ($result); }

        $result = $tmpBoardIdx . '/' . $tmpPONPortIdx . ':' . $tmpLLIDIdx;

        return ($result);
    }


    /**
     * Performs distance preprocessing for distance/mac index arrays and stores it into cache
     *
     * @param $DistIndex
     * @param $rcomgSerialsProcessed
     */
    protected function distanceParseRCOMG($DistIndex, $rcomgSerialsProcessed) {
        $ONUDistances = array();
        $result = array();

        if (!empty($rcomgSerialsProcessed) and !empty($DistIndex)) {
//last dereg index preprocessing
            foreach ($DistIndex as $io => $eachRow) {
                $line = explode('=', $eachRow);

                if (empty($line[0]) || empty($line[1])) {
                    continue;
                }

                $tmpPlasticIdx = trim($line[0]);
                $tmpONUDistance = trim($line[1]);

                $ONUDistances[$tmpPlasticIdx] = $tmpONUDistance;
            }

//storing results
            foreach ($rcomgSerialsProcessed as $eachPlasticIdx => $eachSerial) {
                if (isset($ONUDistances[$eachPlasticIdx])) {
                    $result[$eachSerial] = $ONUDistances[$eachPlasticIdx];
                }
            }

            //saving distances
            $this->olt->writeDistances($result);
        }
    }


    /**
     * Performs last dereg reason preprocessing for dereg reason/mac index arrays and stores it into cache
     *
     * @param $LastDeregIndex
     * @param $rcomgSerialsProcessed
     *
     * @return void
     */
    protected function lastDeregParseRCOMG($LastDeregIndex, $rcomgSerialsProcessed) {
        $ONUDeRegs = array();
        $result = array();

        if (!empty($rcomgSerialsProcessed) and !empty($LastDeregIndex)) {
//last dereg index preprocessing
            foreach ($LastDeregIndex as $io => $eachRow) {
                $line = explode('=', $eachRow);

                if (empty($line[0]) || empty($line[1])) {
                    continue;
                }

                $tmpPlasticIdx = trim($line[0]);
                $tmpONULastDeregReason = intval(trim($line[1]));

                switch ($tmpONULastDeregReason) {
                    case 13:
                        $TxtColor = '"#F80000"';
                        $tmpONULastDeregReasonStr = 'Wire down';
                        break;

                    case 6:
                        $TxtColor = '"#FF4400"';
                        $tmpONULastDeregReasonStr = 'Power off';
                        break;

                    default:
                        $TxtColor = '"#000000"';
                        $tmpONULastDeregReasonStr = 'Unknown';
                        break;
                }

                if (!empty($tmpPlasticIdx)) {
                    $tmpONULastDeregReasonStr = wf_tag('font', false, '', 'color=' . $TxtColor . '') .
                                                $tmpONULastDeregReasonStr .
                                                wf_tag('font', true);

                    $ONUDeRegs[$tmpPlasticIdx] = $tmpONULastDeregReasonStr;
                }
            }

//storing results
            foreach ($rcomgSerialsProcessed as $eachPlasticIdx => $eachSerial) {
                if (isset($ONUDeRegs[$eachPlasticIdx])) {
                    $result[$eachSerial] = $ONUDeRegs[$eachPlasticIdx];
                }
            }

            //saving ONUs deregs reasons
            $this->olt->writeDeregs($result);
        }
    }


    /**
     * Parses & stores to cache ONUs FDB cache (MACs behind ONU)
     *
     * @param $onuSerialsIfacesProcessed
     * @param $fdbMACIndex
     * @param $fdbVLANIndex
     *
     * @return void
     */
    protected function fdbParseRCOMG($onuSerialsIfacesProcessed, $fdbMACIndex, $fdbVLANIndex) {
        if (!empty($onuSerialsIfacesProcessed)) {
            $llidONUSerial = array_flip($onuSerialsIfacesProcessed);
            $fdbDevIdxMAC   = array();
            $fdbIdxVLAN     = array();
            $fdbCahce       = array();

            if (!empty($fdbMACIndex)) {
// here we have just a string representation of the SNMP index - need to convert it to array
                $fdbMACIndex = explode('.', $fdbMACIndex);

                if (!empty($fdbMACIndex) and is_array($fdbMACIndex)) { 
// processing FDBIndex array to get pon [port number + ONU LLID raw => FDB MAC] mapping
                    foreach ($fdbMACIndex as $each => $eachIdx) {
                        $line = explode('=', $eachIdx);
    // FDB MAC is present
                        if (isset($line[1])) {
                            $fdbMAC = trim(str_replace(array("\n", "\r", "\t", '"', ' ', 'STRING:', 'dev/ro'), '', $line[1]));

                            if (empty($fdbMAC)) continue;

    // as this OLT stores all MACs behind ONU as a single string - need to split it into separate MACs
                            $tmpMACArr = str_split($fdbMAC, 12);
                            $fdbMAC = array();

                            foreach ($tmpMACArr as $io => $eachMAC) {
                                $fdbMAC[] = strtolower(AddMacSeparator(RemoveMacAddressSeparator($eachMAC)));  // FDB MAC in space-separated format
                            }

                            $onuBoardPortLLIDRaw = str_replace('.', '', trim($line[0]));
                            $fdbDevIdxMAC[$onuBoardPortLLIDRaw] = $fdbMAC;       // pon port number + ONU LLID + dev idx => FDB MAC
                        }
                    }
                }
            }

            if (!empty($fdbVLANIndex)) {
// processing $fdbVLANIndex array of native VLANs to get [board number + pon port number + ONU LLID + raw mapping => FDB VLAN] mapping
                foreach ($fdbVLANIndex as $each => $eachIdx) {
                    $line = explode('=', $eachIdx);
// FDB VLAN is present
                    if (isset($line[1])) {
                        $onuBoardPortLLIDRaw = str_replace('.', '', trim($line[0]));                           
                        $fdbVLAN = trim($line[1]);                       
                        $fdbIdxVLAN[$onuBoardPortLLIDRaw] = $fdbVLAN;        // pon port number + ONU LLID + dev idx => FDB MAC
                    }
                }
            }

            if (!empty($fdbDevIdxMAC)) {
// processing $fdbIdxMAC and $fdbIdxVLAN to prepare [pon port number + ONU LLID => ['mac' => $eachFDBMAC, 'vlan' => $tmpFDBVLAN]] array
// need to add some artificial "device index" to handle the cases when more than 1 MAC is behind ONU
                foreach ($fdbDevIdxMAC as $onuBoardPortLLIDRaw => $eachFDBMACArr) {
                    $bpllFirstDigit = substr($onuBoardPortLLIDRaw, 0, 1);
                    if (!in_array($bpllFirstDigit, array('1', '3', '8'))) { continue; }

                    $tmpBoardPortLLID = $this->getBoardPortLLIDFromRAW($onuBoardPortLLIDRaw, $bpllFirstDigit);
                    $tmpFDBVLAN = empty($fdbIdxVLAN[$onuBoardPortLLIDRaw]) ? '' : $fdbIdxVLAN[$onuBoardPortLLIDRaw];

                    foreach ($eachFDBMACArr as $io => $eachFDBMAC) {
                        $devIdx = $io + 1;
                        $fdbMACVLAN[$tmpBoardPortLLID][$devIdx] = array('mac' => $eachFDBMAC, 'vlan' => $tmpFDBVLAN);
                    }
                }

                if (!empty($fdbMACVLAN)) {
                    foreach ($llidONUSerial as $eachLLID => $eachSerial) {
                        if (!empty($fdbMACVLAN[$eachLLID])) {
                            $fdbCahce[$eachSerial] = $fdbMACVLAN[$eachLLID];
                        }
                    }
                }
            }

            //saving OLT FDB
            $this->olt->writeFdb($fdbCahce);
        }
    }


    /**
     * Performs UNI port oper status preprocessing for index array and stores it into cache
     *
     * @param $uniOperStatusIndex
     * @param $onuSerialsIfacesProcessed
     *
     * @return void
     */
    protected function uniParseRCOMG($onuSerialsIfacesProcessed, $uniOperStatusIndex, $uniDuplexSpeedIndex) {
        $uniStats = array();
        $speedStatsRaw = array();
        $result = array();
        $llidONUSerial = array_flip($onuSerialsIfacesProcessed);
        $uniDuplexSpeedMap = array(0 => 'UNKNOWN',
                                   1 => '10M FULL',
                                   2 => '100M FULL',
                                   3 => '1000M FULL',
                                   17 => '10M HALF',
                                   18 => '100M HALF',
                                   19 => '1000M HALF'
                                );

        if (!empty($onuSerialsIfacesProcessed) and !empty($uniOperStatusIndex)) {
// Duplex and speed raw preprocessing
            if (!empty($uniDuplexSpeedIndex)) {
                foreach ($uniDuplexSpeedIndex as $io => $eachRow) {
                    $line = explode('=', $eachRow);

                    if (empty($line[0]) || empty($line[1])) {
                        continue;
                    }

                    $onuBoardPortLLIDRaw = str_replace('.', '', trim($line[0]));
                    $onuUNISpeedRaw = intval(trim($line[1]));
                    $onuUNISpeed = (empty($uniDuplexSpeedMap[$onuUNISpeedRaw])) ? $uniDuplexSpeedMap[0] : $uniDuplexSpeedMap[$onuUNISpeedRaw];

                    $speedStatsRaw[$onuBoardPortLLIDRaw] = $onuUNISpeed;
                }
            }

//UniOperStatus index preprocessing
            foreach ($uniOperStatusIndex as $io => $eachRow) {
                $line = explode('=', $eachRow);

                if (empty($line[0]) || empty($line[1])) {
                    continue;
                }

                $onuBoardPortLLIDRaw = str_replace('.', '', trim($line[0]));
                $onuUNIStatusRaw = trim($line[1]);             

                $bpllFirstDigit = substr($onuBoardPortLLIDRaw, 0, 1);
                if (!in_array($bpllFirstDigit, array('1', '3', '8'))) { continue; }
                
                $tmpBoardPortLLID = $this->getBoardPortLLIDFromRAW($onuBoardPortLLIDRaw, $bpllFirstDigit);
                $tmpUNIStatus = ($onuUNIStatusRaw == '1') ? 'UP' : 'DOWN';
                $tmpUNISpeed = (empty($speedStatsRaw[$onuBoardPortLLIDRaw])) ? '' : $speedStatsRaw[$onuBoardPortLLIDRaw];
                $tmpEtherIdx = 'eth0';

                $uniStats[$tmpBoardPortLLID] = array($tmpEtherIdx => array('unistatus' => $tmpUNIStatus, 'unispeed' => $tmpUNISpeed));
            }

//storing results
            foreach ($llidONUSerial as $eachLLID => $eachSerial) {
                if (isset($uniStats[$eachLLID])) {
                    $result[$eachSerial] = $uniStats[$eachLLID];
                }
            }

            //saving UniOperStats
            $this->olt->writeUniOperStats($result);
        }
    }
}
