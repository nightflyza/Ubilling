<?php

/**
 * OLT Stels FD12XX hardware abstraction layer
 */
class PONStels12 extends PONProto {

    /**
     * Stels FD12XX devices polling
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
        $ponPrefixAdd = (empty($this->snmpTemplates[$oltModelId]['misc']['INTERFACEADDPONPREFIX'])
                        ? '' : $this->snmpTemplates[$oltModelId]['misc']['INTERFACEADDPONPREFIX']);
        $distIndex = array();
        $ifaceIndex = array();

        $sigIndex = $this->walkCleared($oltIPPORT, $oltCommunity,
                                       $this->snmpTemplates[$oltModelId]['signal']['SIGINDEX'],
                                       '',
                                       '.0.0 ', self::SNMPCACHE);

//ONU distance polling for stels12 devices
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

//getting MAC index.
        $macIndex = $this->walkCleared($oltIPPORT, $oltCommunity,
                                       $this->snmpTemplates[$oltModelId]['signal']['MACINDEX'],
                                       '',
                                       '', self::SNMPCACHE);

        $this->signalParse($oltid, $sigIndex, $macIndex, $this->snmpTemplates[$oltModelId]['signal']);

        if (isset($this->snmpTemplates[$oltModelId]['misc'])) {
            if (isset($this->snmpTemplates[$oltModelId]['misc']['DISTINDEX'])) {
                if (!empty($this->snmpTemplates[$oltModelId]['misc']['DISTINDEX'])) {
// processing distance data
                    $this->distanceParse($oltid, $distIndex, $macIndex);
//processing interfaces data
                    //$this->interfaceParseStels12($oltid, $ifaceIndex, $macIndex, $deviceType);
                    $this->interfaceParseStels12($oltid, $ifaceIndex, $ponPrefixAdd);
                }
            }

            if (isset($this->snmpTemplates[$oltModelId]['misc']['DEREGREASON'])) {
                if (!empty($this->snmpTemplates[$oltModelId]['misc']['DEREGREASON'])) {
                    $this->lastDeregParseStels12($oltid, $lastDeregIndex, $macIndex);
                }
            }
        }

        if (!$oltNoFDBQ) {
            $fdbMACIndex = $this->walkCleared($oltIPPORT, $oltCommunity,
                                              $this->snmpTemplates[$oltModelId]['misc']['FDBMACINDEX'],
                                              '',
                                              '', self::SNMPCACHE);
            $this->fdbParseStels12($fdbMACIndex);
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
    protected function interfaceParseStels12($oltid, $ifaceIndex, $ponPrefixAdd = '') {
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
    protected function fdbParseStels12($fdbMACIndex) {
        $onuMACIndex = $this->olt->readMacIndex();
        $i = 0;
        $fdbCahce = array();

        if (!empty($onuMACIndex) and !empty($fdbMACIndex)) {
// processing $fdbMACIndex array to get a FDB record at once
            foreach ($fdbMACIndex as $eachIdx => $eachONUMAC) {
                $i++;
                $line = explode('=', $eachONUMAC);
// MAC is present
                if (isset($line[1])) {
                    $onuMAC = trim($line[1]);

                    if (ispos($onuMAC, 'STRING:')) continue;

                    $onuMAC = strtolower(str_replace(' ', ':', $onuMAC));       // ONU MAC

                    $tmpIndex = trim($line[0]);               // pon port number + device index
                    $fdbVLAN = substr($tmpIndex, strripos($tmpIndex, '.') + 1);     // fdb VLAN
                    $fdbMAC = convertMACDec2Hex(substr($tmpIndex, 0, strripos($tmpIndex, '.')));       // fdb MAC;

                    $fdbCahce[$onuMAC][$i] = array('mac' => $fdbMAC, 'vlan' => $fdbVLAN);
                }
            }
        }

        //saving OLT FDB
        $this->olt->writeFdb($fdbCahce);
    }

}
