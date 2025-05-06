<?php

/**
 * OLT Stels FD16XX hardware abstraction layer
 */
class PONstels16 extends PONProto {

    /**
     * Stels FD16XX devices polling
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
        $deviceType = $this->snmpTemplates[$oltModelId]['define']['DEVICE'];
        $signalPollType = (empty($this->snmpTemplates[$oltModelId]['signal']['SIGNAL_POLL_TYPE']) 
                          ? "bulk" : $this->snmpTemplates[$oltModelId]['signal']['SIGNAL_POLL_TYPE']);
        $ponPrefixAdd = (empty($this->snmpTemplates[$oltModelId]['misc']['INTERFACEADDPONPREFIX'])
                        ? '' : $this->snmpTemplates[$oltModelId]['misc']['INTERFACEADDPONPREFIX']);
        $this->onuSerialCaseMode = (isset($this->snmpTemplates[$oltModelId]['onu']['SERIAL_CASE_MODE'])
                        ? $this->snmpTemplates[$oltModelId]['onu']['SERIAL_CASE_MODE'] : 0);

        $macIndex = array();
        $sigIndex = array();
        $distIndex = array();
        $ifaceIndex = array();

        //getting MAC index.
        $macIndex = $this->walkCleared($oltIPPORT, $oltCommunity,
                                       $this->snmpTemplates[$oltModelId]['signal']['MACINDEX'],
                                       '',
                                       '', self::SNMPCACHE);

        $macIndexProcessed = $this->macParseStels16($macIndex);

        if ($signalPollType == 'bulk') {
            $sigIndex = $this->walkCleared($oltIPPORT, $oltCommunity,
                                        $this->snmpTemplates[$oltModelId]['signal']['SIGINDEX'],
                                        '',
                                        '.0.0 ', self::SNMPCACHE);
        } elseif ($signalPollType == 'single') {
            if (!empty($macIndexProcessed)) {
                foreach($macIndexProcessed as $eachDevIdx => $eachMAC) {
                    $tmpSNMPRaw = $this->walkCleared($oltIPPORT, $oltCommunity,
                                        $this->snmpTemplates[$oltModelId]['signal']['SIGINDEX'] . '.' . $eachDevIdx,
                                        '',
                                        '0.0 ', self::SNMPCACHE);

                    if (!ispos($tmpSNMPRaw[0], 'No Such Instance currently exists at this OID')) {
                        $sigIndex[] = $eachDevIdx . $tmpSNMPRaw[0];
                    }
                }
            }
        }

        $this->signalParseStels16($oltid, $sigIndex, $macIndexProcessed, $this->snmpTemplates[$oltModelId]['signal']);

//ONU distance polling for stels16 devices
        if (isset($this->snmpTemplates[$oltModelId]['misc'])) {
            if (isset($this->snmpTemplates[$oltModelId]['misc']['DISTINDEX'])) {
                if (!empty($this->snmpTemplates[$oltModelId]['misc']['DISTINDEX'])) {
                    $distIndex = $this->walkCleared($oltIPPORT, $oltCommunity,
                                                    $this->snmpTemplates[$oltModelId]['misc']['DISTINDEX'],
                                                    '',
                                                    '', self::SNMPCACHE);

                    $onuIndex = $this->walkCleared($oltIPPORT, $oltCommunity,
                                                   $this->snmpTemplates[$oltModelId]['misc']['ONUINDEX'],
                                                   '',
                                                   '.0.0 ', self::SNMPCACHE);

                    $ifaceIndex = $this->walkCleared($oltIPPORT, $oltCommunity,
                                                     $this->snmpTemplates[$oltModelId]['misc']['INTERFACEINDEX'],
                                                     '',
                                                     '"', self::SNMPCACHE);
                }
            }

            if (isset($this->snmpTemplates[$oltModelId]['misc']['DEREGREASON'])) {
                if (!empty($this->snmpTemplates[$oltModelId]['misc']['DEREGREASON'])) {
                    $lastDeregIndex = $this->walkCleared($oltIPPORT, $oltCommunity,
                                                         $this->snmpTemplates[$oltModelId]['misc']['DEREGREASON'],
                                                         '',
                                                         '"', self::SNMPCACHE);
                }
            }
        }

        if (isset($this->snmpTemplates[$oltModelId]['misc'])) {
            if (isset($this->snmpTemplates[$oltModelId]['misc']['DISTINDEX'])) {
                if (!empty($this->snmpTemplates[$oltModelId]['misc']['DISTINDEX'])) {
// processing distance data
                    $this->distanceParse($oltid, $distIndex, $macIndex);
//processing interfaces data
                    //$this->interfaceParsestels16($oltid, $ifaceIndex, $macIndex, $deviceType);
                    $this->interfaceParsestels16($oltid, $ifaceIndex, $ponPrefixAdd);
                }
            }

            if (isset($this->snmpTemplates[$oltModelId]['misc']['DEREGREASON'])) {
                if (!empty($this->snmpTemplates[$oltModelId]['misc']['DEREGREASON'])) {
                    $this->lastDeregParsestels16($oltid, $lastDeregIndex, $macIndexProcessed);
                }
            }
        }

        if (!$oltNoFDBQ) {
            $fdbMACIndex = $this->walkCleared($oltIPPORT, $oltCommunity,
                                              $this->snmpTemplates[$oltModelId]['misc']['FDBMACINDEX'],
                                              '',
                                              '', self::SNMPCACHE);
            $this->fdbParsestels16($fdbMACIndex, $macIndexProcessed);
        }

        $uniOperStatusIndex = array();
        if (isset($this->snmpTemplates[$oltModelId]['misc']['UNIOPERSTATUS'])) {
            $uniOperStatusIndex = $this->walkCleared($oltIPPORT, $oltCommunity,
                                                     $this->snmpTemplates[$oltModelId]['misc']['UNIOPERSTATUS'],
                                                     '',
                                                     array($this->snmpTemplates[$oltModelId]['misc']['UNIOPERSTATUSVALUE'], '"'),
                                                     self::SNMPCACHE);

            $this->uniParseStels16($uniOperStatusIndex, $macIndexProcessed);
        }
    }


    /**
     * Processes OLT MAC adresses and returns them in array: LLID=>MAC
     *
     * @param $macIndex
     *
     * @return array
     */
    protected function macParseStels16($macIndex) {
        $ONUsMACs = array();

        if (!empty($macIndex)) {
//mac index preprocessing
            foreach ($macIndex as $io => $eachmac) {
                $line = explode('=', $eachmac);

                if (empty($line[0]) || empty($line[1])) {
                    continue;
                }

                $tmpONUDevIdx = trim($line[0]);
                $tmpONUMAC = trim($line[1]);
                $tmpONUMAC = str_replace(' ', ':', $tmpONUMAC);

                if ($this->onuSerialCaseMode == 1) {
                    $tmpONUMAC = strtolower($tmpONUMAC);
                } elseif ($this->onuSerialCaseMode == 2) {
                    $tmpONUMAC = strtoupper($tmpONUMAC);
                }

//mac is present
                if (!empty($tmpONUDevIdx) and ! empty($tmpONUMAC)) {
                    $ONUsMACs[$tmpONUDevIdx] = $tmpONUMAC;
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
     * @param array $macIndexProcessed
     * @param array $snmpTemplate
     *
     * @return void
     */
    protected function signalParseStels16($oltid, $sigIndex, $macIndexProcessed, $snmpTemplate) {
        $oltid = vf($oltid, 3);
        $sigTmp = array();
        $result = array();

//signal index preprocessing
        if ((!empty($sigIndex)) and ( !empty($macIndexProcessed))) {
            foreach ($sigIndex as $io => $eachsig) {
                $line = explode('=', $eachsig);
//signal is present
                if (isset($line[1])) {
                    $signalRaw = trim($line[1]); // signal level
                    $devIndex = trim($line[0]); // device index
                    if ($signalRaw == $snmpTemplate['DOWNVALUE']) {
                        $signalRaw = 'Offline';
                    } else {
                        if ($snmpTemplate['OFFSETMODE'] == 'div') {
                            if ($snmpTemplate['OFFSET']) {
                                if (is_numeric($signalRaw)) {
                                    $signalRaw = $signalRaw / $snmpTemplate['OFFSET'];
                                } else {
                                    $signalRaw = 'Fail';
                                }
                            }
                        }
                    }
                    $sigTmp[$devIndex] = $signalRaw;
                }
            }

//storing results
            if (!empty($macIndexProcessed)) {
                foreach ($macIndexProcessed as $devId => $eachMac) {
                    if (isset($sigTmp[$devId])) {
                        $signal = $sigTmp[$devId];
                        $result[$eachMac] = $signal;
                        //signal history preprocessing
                        if ($signal == 'Offline') {
                            $signal = $this->onuOfflineSignalLevel; //over 9000 offline signal level :P
                        }

                        //saving each ONU signal history
                        $this->olt->writeSignalHistory($eachMac, $signal);
                    }
                }

                //writing signals cache
                $this->olt->writeSignals($result);

                // saving macindex as MAC => devID
                $macIndexProcessed = array_flip($macIndexProcessed);
                $this->olt->writeMacIndex($macIndexProcessed);
            }
        }
    }

    /**
     * Parses & stores in cache OLT ONU interfaces
     *
     * @param int   $oltid
     * @param array $ifaceIndex
     * @param array $macIndex
     *
     * @return void
     */
    protected function interfaceParsestels16($oltid, $ifaceIndex, $ponPrefixAdd = '') {
        $macIndex = $this->olt->readMacIndex();
        $oltid = vf($oltid, 3);
        $ifaceTmp = array();
        $result = array();
        $i = 0;

//iface index preprocessing
        if ((!empty($ifaceIndex)) and ( !empty($macIndex))) {
// creating mapping of internal pon ifaces nums to sequential, like this:
//bsPortIndex.1.0.13; Value (Integer): 13 => 1
//bsPortIndex.1.0.14; Value (Integer): 14 => 2
//bsPortIndex.1.0.15; Value (Integer): 15 => 3
//bsPortIndex.1.0.16; Value (Integer): 16 => 4
//bsPortIndex.1.0.17; Value (Integer): 17 => 5
//bsPortIndex.1.0.18; Value (Integer): 18 => 6
//bsPortIndex.1.0.19; Value (Integer): 19 => 7
//bsPortIndex.1.0.20; Value (Integer): 20 => 8
            foreach ($ifaceIndex as $io => $eachIface) {
                $i++;
                $line = explode('=', $eachIface);

                if (isset($line[1])) {
                    $ponIfaceNum            = trim($line[1]); // pon interface number
                    $ifaceTmp[$ponIfaceNum] = $i;
                }
            }

// using "special" math to get pon port num + LLID from dev index
// formula: dev index DEC to HEX
// 16780033 => 1000B01, where
// 0B => 11 - pon port num
// 01 => 1 - LLID
            if (!empty($macIndex) and !empty($ifaceTmp)) {
                foreach ($macIndex as $eachMac => $devId) {
                    $LLID     = '';
                    $hexDevId = dechex($devId);
                    $portNum  = hexdec(substr($hexDevId, -4, 2));
                    $onuNum   = hexdec(substr($hexDevId, -2, 2));

                    if (!empty($ifaceTmp[$portNum])) {
                        $portNum = $ifaceTmp[$portNum];
                        $LLID    = $portNum . ":" . $onuNum;
                    } else {
                        $LLID = __('On ho');
                    }
//storing results
                    $result[$eachMac] = $ponPrefixAdd . $LLID;
                }
            }
//saving ONUs interfaces
            $this->olt->writeInterfaces($result);
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
    protected function lastDeregParsestels16($oltid, $deregIndex, $macIndex) {
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
/*            foreach ($macIndex as $io => $eachmac) {
                $line = explode('=', $eachmac);
//mac is present
                if (isset($line[1])) {
                    $macRaw = trim($line[1]); //mac address
                    $devIndex = trim($line[0]); //device index
                    $macRaw = str_replace(' ', ':', $macRaw);
                    $macRaw = strtolower($macRaw);
                    $macTmp[$devIndex] = $macRaw;
                }
            }*/

//storing results
            if (!empty($macIndex)) {
                foreach ($macIndex as $devId => $eachMac) {
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

                //saving ONUs dereg reasons
                $this->olt->writeDeregs($result);
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

                //saving ONUs interfaces
                $this->olt->writeInterfaces($result);
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
                //saving ONUs dereg reasons
                $this->olt->writeDeregs($result);
            }
        }
    }

    /**
     * Parses & stores to cache ONUs FDB cache (MACs behind ONU)
     *
     * @param $fdbMACIndex
     *
     * @return void
     */
    protected function fdbParsestels16($fdbMACIndex, $macIndexProcessed) {
        $onuMACIndex = $macIndexProcessed;
        $i = 0;
        $fdbCahce = array();

        if (!empty($onuMACIndex) and !empty($fdbMACIndex)) {
// processing $fdbMACIndex array to get a FDB record at once
            foreach ($fdbMACIndex as $eachIdx => $eachFDBLine) {
                $i++;

                if (empty($eachFDBLine) or !ispos($eachFDBLine, '=')) { continue; }

                $line           = explode('=', $eachFDBLine);
                $onuPlasticIdx  = trim($line[1]);               // ONU Plastic index

// Plastic index is present
                if (isset($onuMACIndex[$onuPlasticIdx])) {
                    $fdbMACVLAN     = trim($line[0]);       // getting DEC MAC + VLAN portion
                    $fdbVLAN        = substr($fdbMACVLAN, strripos($fdbMACVLAN, '.') + 1);     // fdb VLAN
                    $fdbMAC         = convertMACDec2Hex(substr($fdbMACVLAN, 0, strripos($fdbMACVLAN, '.')));       // fdb MAC;

                    $fdbCahce[$onuMACIndex[$onuPlasticIdx]][$i] = array('mac' => $fdbMAC, 'vlan' => $fdbVLAN);
                }
            }
        }

        //saving OLT FDB
        $this->olt->writeFdb($fdbCahce);
    }


    /**
     * Performs UNI port oper status preprocessing for index array and stores it into cache
     *
     * @param $uniOperStatusIndex
     * @param $macIndexProcessed
     *
     * @return void
     */
    protected function uniParseStels16($uniOperStatusIndex, $macIndexProcessed) {
        $uniStats = array();
        $result = array();

        if (!empty($macIndexProcessed) and !empty($uniOperStatusIndex)) {
//UniOperStats index preprocessing
            foreach ($uniOperStatusIndex as $io => $eachRow) {
                $line = explode('=', $eachRow);
//file_put_contents('exports/pondata/unioperstats/unistats', print_r($line, true));
                if (empty($line[0]) || empty($line[1])) {
                    continue;
                }

                // dev index + .0. + ether port index
                $tmpDevIdxEtherIdx = trim($line[0]);
                $tmpDevIdxEtherIdxLen = strlen($tmpDevIdxEtherIdx);

                // ehter port index
                $tmpEtherIdx = strrchr($tmpDevIdxEtherIdx, '.');
                $tmpEtherIdxLen = strlen($tmpEtherIdx);
                $tmpEtherIdx = 'eth' . trim($tmpEtherIdx, '.');
//file_put_contents('exports/pondata/unioperstats/unistats', $tmpEtherIdx . "\n", 8);
                //dev index = $tmpDevIdxEtherIdx - '.0.' - $tmpEtherIdx
                $tmpONUDevIdx = substr($tmpDevIdxEtherIdx, 0, $tmpDevIdxEtherIdxLen - $tmpEtherIdxLen - 2);
                $tmpUniStatus = trim(trim($line[1]), '"');
//file_put_contents('exports/pondata/unioperstats/unistats', $tmpONUDevIdx . "\n", 8);
//file_put_contents('exports/pondata/unioperstats/unistats', $tmpUniStatus . "\n", 8);
                $uniStats[$tmpONUDevIdx] = array($tmpEtherIdx => $tmpUniStatus);
            }
//file_put_contents('exports/pondata/unioperstats/unistats', print_r($uniStats, true), 8);
//file_put_contents('exports/pondata/unioperstats/unistats', print_r($macIndexProcessed, true), 8);
//storing results
            foreach ($macIndexProcessed as $devId => $eachMac) {
                if (isset($uniStats[$devId])) {
                    $result[$eachMac] = $uniStats[$devId];
                }
            }

            //saving UniOperStats
            $this->olt->writeUniOperStats($result);
        }
    }
}
