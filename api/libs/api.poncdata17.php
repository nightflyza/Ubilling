<?php

/**
 * OLT C-Data FD1732S hardware abstraction layer
 */
class PONCdata17 extends PONProto {

    /**
     * C-Data FD1732S devices polling
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
        $signalPollType = (empty($this->snmpTemplates[$oltModelId]['signal']['SIGNAL_POLL_TYPE'])
                          ? 'bulk' : $this->snmpTemplates[$oltModelId]['signal']['SIGNAL_POLL_TYPE']);
        $ponPrefixAdd = (empty($this->snmpTemplates[$oltModelId]['misc']['INTERFACEADDPONPREFIX'])
                        ? '' : $this->snmpTemplates[$oltModelId]['misc']['INTERFACEADDPONPREFIX']);
        $this->onuSerialCaseMode = (isset($this->snmpTemplates[$oltModelId]['onu']['SERIAL_CASE_MODE'])
                        ? $this->snmpTemplates[$oltModelId]['onu']['SERIAL_CASE_MODE'] : 0);

        $macIndex = array();
        $sigIndex = array();
        $distIndex = array();
        $ifaceIndex = array();
        $lastDeregIndex = array();

        // getting MAC / serial index
        $macIndex = $this->walkCleared($oltIPPORT, $oltCommunity,
                                       $this->snmpTemplates[$oltModelId]['signal']['MACINDEX'],
                                       '',
                                       '', self::SNMPCACHE);

        $macIndexProcessed = $this->macParseCdata17($macIndex);

        if ($signalPollType == 'bulk') {
            // FD1732S SIGINDEX suffix is plastic.0.ponIfIndex - do not strip .0.0
            $sigIndex = $this->walkCleared($oltIPPORT, $oltCommunity,
                                           $this->snmpTemplates[$oltModelId]['signal']['SIGINDEX'],
                                           '',
                                           '', self::SNMPCACHE);
        } else {
            if ($signalPollType == 'single') {
                if (!empty($macIndexProcessed)) {
                    foreach ($macIndexProcessed as $eachDevIdx => $eachMAC) {
                        $tmpSNMPRaw = $this->walkCleared($oltIPPORT, $oltCommunity,
                                                         $this->snmpTemplates[$oltModelId]['signal']['SIGINDEX'] . '.' . $eachDevIdx,
                                                         '',
                                                         '', self::SNMPCACHE);

                        if (!empty($tmpSNMPRaw[0]) and !ispos($tmpSNMPRaw[0], 'No Such Instance currently exists at this OID')) {
                            $sigIndex[] = $eachDevIdx . $tmpSNMPRaw[0];
                        }
                    }
                }
            }
        }

        $this->signalParseCdata17($oltid, $sigIndex, $macIndexProcessed, $this->snmpTemplates[$oltModelId]['signal']);

        if (isset($this->snmpTemplates[$oltModelId]['misc'])) {
            if (isset($this->snmpTemplates[$oltModelId]['misc']['DISTINDEX'])) {
                if (!empty($this->snmpTemplates[$oltModelId]['misc']['DISTINDEX'])) {
                    $distIndex = $this->walkCleared($oltIPPORT, $oltCommunity,
                                                    $this->snmpTemplates[$oltModelId]['misc']['DISTINDEX'],
                                                    '',
                                                    '', self::SNMPCACHE);

                    if (isset($this->snmpTemplates[$oltModelId]['misc']['INTERFACEINDEX'])
                        and !empty($this->snmpTemplates[$oltModelId]['misc']['INTERFACEINDEX'])) {
                        $ifaceIndex = $this->walkCleared($oltIPPORT, $oltCommunity,
                                                         $this->snmpTemplates[$oltModelId]['misc']['INTERFACEINDEX'],
                                                         '',
                                                         '"', self::SNMPCACHE);
                    }

                    $this->distanceParse($oltid, $distIndex, $macIndex);
                    $this->interfaceParseCdata17($oltid, $ifaceIndex, $macIndexProcessed, $ponPrefixAdd);
                }
            }

            if (isset($this->snmpTemplates[$oltModelId]['misc']['DEREGREASON'])) {
                if (!empty($this->snmpTemplates[$oltModelId]['misc']['DEREGREASON'])) {
                    $lastDeregIndex = $this->walkCleared($oltIPPORT, $oltCommunity,
                                                         $this->snmpTemplates[$oltModelId]['misc']['DEREGREASON'],
                                                         '',
                                                         '"', self::SNMPCACHE);
                    $this->lastDeregParseCdata17($oltid, $lastDeregIndex, $macIndexProcessed);
                }
            }
        }

        if (!$oltNoFDBQ) {
            if (isset($this->snmpTemplates[$oltModelId]['misc']['FDBMACINDEX'])
                and !empty($this->snmpTemplates[$oltModelId]['misc']['FDBMACINDEX'])) {
                $fdbMACIndex = $this->walkCleared($oltIPPORT, $oltCommunity,
                                                  $this->snmpTemplates[$oltModelId]['misc']['FDBMACINDEX'],
                                                  '',
                                                  '', self::SNMPCACHE);
                $this->fdbParseCdata17($fdbMACIndex, $macIndexProcessed);
            }
        }

        if (isset($this->snmpTemplates[$oltModelId]['misc']['UNIOPERSTATUS'])
            and !empty($this->snmpTemplates[$oltModelId]['misc']['UNIOPERSTATUS'])) {
            $uniOperStatusIndex = $this->walkCleared($oltIPPORT, $oltCommunity,
                                                     $this->snmpTemplates[$oltModelId]['misc']['UNIOPERSTATUS'],
                                                     '',
                                                     array($this->snmpTemplates[$oltModelId]['misc']['UNIOPERSTATUSVALUE'], '"'),
                                                     self::SNMPCACHE);

            $this->uniParseCdata17($uniOperStatusIndex, $macIndexProcessed);
        }

        // system uptime / temperature
        if (isset($this->snmpTemplates[$oltModelId]['system'])) {
            if (isset($this->snmpTemplates[$oltModelId]['system']['UPTIME'])) {
                $uptimeIndexOid = $this->snmpTemplates[$oltModelId]['system']['UPTIME'];
                $oltSystemUptimeRaw = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $uptimeIndexOid, self::SNMPCACHE);
                $this->uptimeParse($oltid, $oltSystemUptimeRaw);
            }

            if (isset($this->snmpTemplates[$oltModelId]['system']['TEMPERATURE'])) {
                $temperatureIndexOid = $this->snmpTemplates[$oltModelId]['system']['TEMPERATURE'];
                $oltTemperatureRaw = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $temperatureIndexOid, self::SNMPCACHE);
                $this->temperatureParse($oltid, $oltTemperatureRaw);
            }
        }
    }

    /**
     * Processes OLT serials and returns them in array: plasticIndex=>serial
     *
     * @param array $macIndex
     *
     * @return array
     */
    protected function macParseCdata17($macIndex) {
        $result = array();

        if (!empty($macIndex)) {
            foreach ($macIndex as $io => $eachmac) {
                $line = explode('=', $eachmac);

                if (empty($line[0]) or empty($line[1])) {
                    continue;
                }

                $tmpONUDevIdx = trim($line[0]);
                $tmpONUMAC = trim($line[1]);
                $tmpONUMAC = str_replace(' ', ':', $tmpONUMAC);

                if ($this->onuSerialCaseMode == 1) {
                    $tmpONUMAC = strtolower($tmpONUMAC);
                } else {
                    if ($this->onuSerialCaseMode == 2) {
                        $tmpONUMAC = strtoupper($tmpONUMAC);
                    }
                }

                if (!empty($tmpONUDevIdx) and !empty($tmpONUMAC)) {
                    $result[$tmpONUDevIdx] = $tmpONUMAC;
                }
            }
        }

        return ($result);
    }

    /**
     * Performs signal preprocessing for sig/mac index arrays and stores it into cache.
     * SIGINDEX keys on FD1732S look like plastic.0.ponIfIndex - use plastic only.
     *
     * @param int   $oltid
     * @param array $sigIndex
     * @param array $macIndexProcessed
     * @param array $snmpTemplate
     *
     * @return void
     */
    protected function signalParseCdata17($oltid, $sigIndex, $macIndexProcessed, $snmpTemplate) {
        $oltid = vf($oltid, 3);
        $sigTmp = array();
        $result = array();

        if ((!empty($sigIndex)) and (!empty($macIndexProcessed))) {
            foreach ($sigIndex as $io => $eachsig) {
                $line = explode('=', $eachsig);

                if (isset($line[1])) {
                    $signalRaw = trim($line[1]);
                    $devIndex = trim($line[0]);
                    $devIndexParts = explode('.', $devIndex);
                    $devIndex = trim($devIndexParts[0]);

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

            if (!empty($macIndexProcessed)) {
                foreach ($macIndexProcessed as $devId => $eachMac) {
                    if (isset($sigTmp[$devId])) {
                        $signal = $sigTmp[$devId];
                        $result[$eachMac] = $signal;

                        if ($signal == 'Offline') {
                            $signal = $this->onuOfflineSignalLevel;
                        }

                        $this->olt->writeSignalHistory($eachMac, $signal);
                    }
                }

                $this->olt->writeSignals($result);

                $macIndexProcessed = array_flip($macIndexProcessed);
                $this->olt->writeMacIndex($macIndexProcessed);
            }
        }
    }

    /**
     * Parses ONU interface descriptions like "gpon 0/1/1 onu 1" into "gpon 0/1/1:1"
     *
     * @param int   $oltid
     * @param array $ifaceIndex
     * @param array $macIndexProcessed plastic=>serial
     * @param string $ponPrefixAdd
     *
     * @return void
     */
    protected function interfaceParseCdata17($oltid, $ifaceIndex, $macIndexProcessed, $ponPrefixAdd = '') {
        $oltid = vf($oltid, 3);
        $ifaceTmp = array();
        $result = array();

        if ((!empty($ifaceIndex)) and (!empty($macIndexProcessed))) {
            foreach ($ifaceIndex as $io => $eachIface) {
                $line = explode('=', $eachIface);

                if (isset($line[1])) {
                    $devIndex = trim($line[0]);
                    $ifaceRaw = trim(trim($line[1]), '"');
                    $ifaceRaw = str_replace(' onu ', ':', $ifaceRaw);
                    $ifaceTmp[$devIndex] = $ponPrefixAdd . $ifaceRaw;
                }
            }

            foreach ($macIndexProcessed as $devId => $eachMac) {
                if (isset($ifaceTmp[$devId])) {
                    $result[$eachMac] = $ifaceTmp[$devId];
                } else {
                    $result[$eachMac] = __('On ho');
                }
            }

            $this->olt->writeInterfaces($result);
        }
    }

    /**
     * Parses & stores ONU last dereg reasons by plastic index with coloring
     *
     * @param int   $oltid
     * @param array $deregIndex
     * @param array $macIndexProcessed
     *
     * @return void
     */
    protected function lastDeregParseCdata17($oltid, $deregIndex, $macIndexProcessed) {
        $oltid = vf($oltid, 3);
        $deregTmp = array();
        $result = array();
        
        $deregColorsMap = array(
            'dying-gasp' => '"#6500FF"',
            'dying gasp' => '"#6500FF"',
            'power off' => '"#6500FF"',
            'power-off' => '"#6500FF"',
            'los' => '"#F80000"',
            'losi' => '"#F80000"',
            'pon-los' => '"#F80000"',
            'wire down' => '"#F80000"',
            'lofi' => '"#F80000"',
            'loai' => '"#F80000"',
            'loami' => '"#F80000"',
            'deactivation' => '"#FF4400"',
            'disable' => '"#FF4400"',
            'admin down' => '"#FF4400"',
            'admin-down' => '"#FF4400"',
            'reboot' => '"#000000"',
            'omcc-down' => '"#000000"',
            'lcdg' => '"#000000"',
            'normal' => '"#00B20E"'
        );

        if ((!empty($deregIndex)) and (!empty($macIndexProcessed))) {
            foreach ($deregIndex as $io => $eachdereg) {
                $line = explode('=', $eachdereg);

                if (isset($line[1])) {
                    $lastDeregRaw = trim(trim($line[1]), '"');
                    $devIndex = trim($line[0]);
                    $deregKey = strtolower($lastDeregRaw);
                    $txtColor = '"#000000"';
                    $deregText = $lastDeregRaw;

                    if (isset($deregColorsMap[$deregKey])) {
                        $txtColor = $deregColorsMap[$deregKey];
                    }

                    if ($deregText === '') {
                        $deregText = __('On ho');
                    }

                    $deregTmp[$devIndex] = wf_tag('font', false, '', 'color=' . $txtColor) .
                            $deregText .
                            wf_tag('font', true);
                }
            }

            foreach ($macIndexProcessed as $devId => $eachMac) {
                if (isset($deregTmp[$devId])) {
                    $result[$eachMac] = $deregTmp[$devId];
                } else {
                    $result[$eachMac] = __('On ho');
                }
            }

            $this->olt->writeDeregs($result);
        }
    }

    /**
     * Parses & stores ONU FDB cache (MACs behind ONU)
     *
     * @param array $fdbMACIndex
     * @param array $macIndexProcessed
     *
     * @return void
     */
    protected function fdbParseCdata17($fdbMACIndex, $macIndexProcessed) {
        $onuMACIndex = $macIndexProcessed;
        $i = 0;
        $fdbCahce = array();

        if (!empty($onuMACIndex) and !empty($fdbMACIndex)) {
            foreach ($fdbMACIndex as $eachIdx => $eachFDBLine) {
                $i++;

                if (empty($eachFDBLine) or !ispos($eachFDBLine, '=')) {
                    continue;
                }

                $line = explode('=', $eachFDBLine);
                $onuPlasticIdx = trim($line[1]);

                if (isset($onuMACIndex[$onuPlasticIdx])) {
                    $fdbMACVLAN = trim($line[0]);
                    $fdbVLAN = substr($fdbMACVLAN, strripos($fdbMACVLAN, '.') + 1);
                    $fdbMAC = convertMACDec2Hex(substr($fdbMACVLAN, 0, strripos($fdbMACVLAN, '.')));

                    $fdbCahce[$onuMACIndex[$onuPlasticIdx]][$i] = array('mac' => $fdbMAC, 'vlan' => $fdbVLAN);
                }
            }
        }

        $this->olt->writeFdb($fdbCahce);
    }

    /**
     * Performs UNI port oper status preprocessing and stores it into cache
     *
     * @param array $uniOperStatusIndex
     * @param array $macIndexProcessed
     *
     * @return void
     */
    protected function uniParseCdata17($uniOperStatusIndex, $macIndexProcessed) {
        $uniStats = array();
        $result = array();

        if (!empty($macIndexProcessed) and !empty($uniOperStatusIndex)) {
            foreach ($uniOperStatusIndex as $io => $eachRow) {
                $line = explode('=', $eachRow);

                if (empty($line[0]) or empty($line[1])) {
                    continue;
                }

                // plastic.0.ethPort
                $tmpDevIdxEtherIdx = trim($line[0]);
                $tmpDevIdxEtherIdxLen = strlen($tmpDevIdxEtherIdx);

                $tmpEtherIdx = strrchr($tmpDevIdxEtherIdx, '.');
                $tmpEtherIdxLen = strlen($tmpEtherIdx);
                $tmpEtherIdx = 'eth' . trim($tmpEtherIdx, '.');

                $tmpONUDevIdx = substr($tmpDevIdxEtherIdx, 0, $tmpDevIdxEtherIdxLen - $tmpEtherIdxLen - 2);
                // FD1732S: 1 - up, 2 - down (UI expects 1/0)
                $tmpUniStatus = trim(trim($line[1]), '"');
                $tmpUniStatus = ($tmpUniStatus == 1) ? 1 : 0;
                $uniStats[$tmpONUDevIdx] = array($tmpEtherIdx => $tmpUniStatus);
            }

            foreach ($macIndexProcessed as $devId => $eachMac) {
                if (isset($uniStats[$devId])) {
                    $result[$eachMac] = $uniStats[$devId];
                }
            }

            $this->olt->writeUniOperStats($result);
        }
    }
}
