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
        global $ubillingConfig;
        $this->ubConfig = $ubillingConfig;
        $this->onuUniStatusEnabled = $this->ubConfig->getAlterParam('PON_ONU_UNI_STATUS_ENABLED', false);

        $oltModelId = $this->oltParameters['MODELID'];
        $oltid = $this->oltParameters['ID'];
        $oltIp = $this->oltParameters['IP'];
        $oltCommunity = $this->oltParameters['COMMUNITY'];
        $oltNoFDBQ = $this->oltParameters['NOFDB'];
        $this->onuSerialCaseMode = (isset($this->snmpTemplates[$oltModelId]['misc']['SERIAL_CASE_MODE'])
                                    ? $this->snmpTemplates[$oltModelId]['misc']['SERIAL_CASE_MODE'] : 1);

        $sigIndexOID = $this->snmpTemplates[$oltModelId]['signal']['SIGINDEX'];
        $sigIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $sigIndexOID, self::SNMPCACHE);
        $sigIndex = str_replace($sigIndexOID . '.', '', $sigIndex);
        $sigIndex = str_replace($this->snmpTemplates[$oltModelId]['signal']['SIGVALUE'], '', $sigIndex);
        $sigIndex = explodeRows($sigIndex);

        // ONU distance polling for bdcom devices
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
            // Get UniOperStatusIndex
            if (isset($this->snmpTemplates[$oltModelId]['misc']['UNIOPERSTATUS']) and $this->onuUniStatusEnabled) {
                $uniOperStatusIndexOID = $this->snmpTemplates[$oltModelId]['misc']['UNIOPERSTATUS'];
                $uniOperStatusIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $uniOperStatusIndexOID, self::SNMPCACHE);
                $uniOperStatusIndex = str_replace($uniOperStatusIndexOID . '.', '', $uniOperStatusIndex);
                $uniOperStatusIndex = str_replace(array($this->snmpTemplates[$oltModelId]['misc']['UNIOPERSTATUSVALUE'], '"'), '', $uniOperStatusIndex);
                $uniOperStatusIndex = explodeRows($uniOperStatusIndex);
            }
        }

        // getting others system data from OLT
        if (isset($this->snmpTemplates[$oltModelId]['system'])) {
            // OLT uptime
            if (isset($this->snmpTemplates[$oltModelId]['system']['UPTIME'])) {
                $uptimeIndexOid = $this->snmpTemplates[$oltModelId]['system']['UPTIME'];
                $oltSystemUptimeRaw = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $uptimeIndexOid, self::SNMPCACHE);
                $this->uptimeParse($oltid, $oltSystemUptimeRaw);
            }

            // OLT temperature
            if (isset($this->snmpTemplates[$oltModelId]['system']['TEMPERATURE'])) {
                $temperatureIndexOid = $this->snmpTemplates[$oltModelId]['system']['TEMPERATURE'];
                $oltTemperatureRaw = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $temperatureIndexOid, self::SNMPCACHE);
                $this->temperatureParse($oltid, $oltTemperatureRaw);
            }
        }

        // getting MAC index.
        $macIndexOID = $this->snmpTemplates[$oltModelId]['signal']['MACINDEX'];
        $macIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $macIndexOID, self::SNMPCACHE);
        $macIndex = str_replace($macIndexOID . '.', '', $macIndex);
        $macIndex = str_replace($this->snmpTemplates[$oltModelId]['signal']['MACVALUE'], '', $macIndex);
        $macIndex = str_replace('"', '', $macIndex);
        $macIndex = explodeRows($macIndex);

        // Start proccesing for get ONU id and MAC
        $this->onuMacProcessing($macIndex);
        $this->onuDevIndexProcessing($onuIndex);

        $this->signalParse($oltid, $sigIndex, $macIndex, $this->snmpTemplates[$oltModelId]['signal']);
        // This is here because BDCOM is BDCOM and another snmp queries cant be processed after MACINDEX query in some cases.
        if (isset($this->snmpTemplates[$oltModelId]['misc'])) {
            if (isset($this->snmpTemplates[$oltModelId]['misc']['DISTINDEX'])) {
                if (!empty($this->snmpTemplates[$oltModelId]['misc']['DISTINDEX'])) {
                    // processing distance data
                    $this->distanceParseGPBd($distIndex, $onuIndex);
                    // processing interfaces data
                    $this->interfaceParseBd($intIndex);
                    // processing UniOperStauts
                    if (isset($this->snmpTemplates[$oltModelId]['misc']['UNIOPERSTATUS']) and $this->onuUniStatusEnabled and !empty($uniOperStatusIndex)) {
                        $this->uniParseGPBd($uniOperStatusIndex);
                    }
                    // processing FDB data
                    if (!$oltNoFDBQ) {
                        $this->FDBParseGPBd($FDBIndex, $oltModelId);
                    }

                    // processing last dereg reason data
                    if (isset($this->snmpTemplates[$oltModelId]['misc']['DEREGREASON'])) {
                        $deregIndexOid = $this->snmpTemplates[$oltModelId]['misc']['DEREGREASON'];
                        $deregIndex = $this->snmp->walk($oltIp . ':' . self::SNMPPORT, $oltCommunity, $deregIndexOid, self::SNMPCACHE);
                        $deregIndex = str_replace($deregIndexOid . '.', '', $deregIndex);
                        $deregIndex = str_replace($this->snmpTemplates[$oltModelId]['misc']['DEREGVALUE'], '', $deregIndex);
                        $deregIndex = explodeRows($deregIndex);

                        $this->lastDeregParseBd($deregIndex);
                    }
                }
            }
        }
    }

    /**
     * Parses & stores in cache OLT ONU interfaces
     *
     * @param array $FDBIndex
     * @param array $oltModelId
     *
     * @return void
     */
    protected function FDBParseGPBd($FDBIndex, $oltModelId) {
        $TmpArr = array();
        $FDBTmp = array();
        $result = array();

        // fdb index preprocessing
        if ((!empty($FDBIndex)) and ( ! empty($this->macIndexProcessed))) {
            foreach ($FDBIndex as $io => $eachfdbRaw) {
                if (preg_match('/' . $this->snmpTemplates[$oltModelId]['misc']['FDBVALUE'] . '|INTEGER:/', $eachfdbRaw)) {
                    $eachfdbRaw = str_replace(array($this->snmpTemplates[$oltModelId]['misc']['FDBVALUE'], 'INTEGER:'), '', $eachfdbRaw);
                    $line = explode('=', $eachfdbRaw);
                    // fdb is present
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

            // storing results
                foreach ($this->macIndexProcessed as $devId => $eachMac) {
                    if (isset($FDBTmp[$devId])) {
                        $fdb = $FDBTmp[$devId];
                        $result[$eachMac] = $fdb;
                    }
                }

                //saving FDB data
                $this->olt->writeFdb($result);
        }
    }

    /**
     * Parses & stores in cache OLT ONU distances
     *
     * @param array $distIndex
     * @param array $onuIndex
     *
     * @return void
     */
    protected function distanceParseGPBd($distIndex, $onuIndex) {
        $distTmp = array();
        $onuTmp = array();
        $result = array();

        // distance index preprocessing
        if ((!empty($distIndex)) and ( !empty($onuIndex))) {
            foreach ($distIndex as $io => $eachdist) {
                $line = explode('=', $eachdist);
                // distance is present
                if (isset($line[1])) {
                    $distanceRaw = trim($line[1]); // distance
                    $devIndex = trim($line[0]); // device index
                    $distTmp[$devIndex] = $distanceRaw;
                }
            }

            // mac index preprocessing
            foreach ($onuIndex as $io => $eachmac) {
                $line = explode('=', $eachmac);
                // mac is present
                if (isset($line[1])) {
                    $macRaw = trim($line[1]); //mac address
                    $devIndex = trim($line[0]); //device index
                    $macRaw = str_replace(' ', ':', $macRaw);
                    $macRaw = strtolower($macRaw);
                    $onuTmp[$devIndex] = $macRaw;
                }
            }

            // storing results
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
                // saving ONUs distances
                $this->olt->writeDistances($result);

                // saving ONUs cache
                $this->olt->writeOnuCache($onuTmp);
            }
        }
    }

    /**
     * Parses & stores in cache OLT ONU dereg reaesons (GPON-specific BDCOM)
     *
     * @param array $deregIndex
     *
     * @return void
     */
    protected function lastDeregParseBd($deregIndex) {
        $result = array();

        // deregistration reasons preprocessing
        $DeregReasonsMap = array(
            0  => array('color' => '"#000000"', 'text' => 'No Reason'),
            1  => array('color' => '"#6500FF"', 'text' => 'Power off'),
            2  => array('color' => '"#000000"', 'text' => 'Laser always on'),
            3  => array('color' => '"#FF4400"', 'text' => 'Admin Down'),
            4  => array('color' => '"#000000"', 'text' => 'omcc-down'),
            5  => array('color' => '"#000000"', 'text' => 'Unknown'),
            6  => array('color' => '"#000000"', 'text' => 'pon-los'),
            7  => array('color' => '"#000000"', 'text' => 'lcdg'),
            8  => array('color' => '"#F80000"', 'text' => 'Wire down'),
            9  => array('color' => '"#000000"', 'text' => 'omci-mismatch'),
            10 => array('color' => '"#000000"', 'text' => 'password-mismatch'),
            11 => array('color' => '"#000000"', 'text' => 'Reboot'),
            12 => array('color' => '"#000000"', 'text' => 'ranging-failed')
        );

        if ((!empty($deregIndex)) and ( !empty($this->onuDevIndexProcessed))) {
            foreach ($deregIndex as $io => $eachdereg) {
                $line = explode('=', $eachdereg);
                
                //dereg is present
                if (isset($line[1])) {
                    $deregRaw = trim($line[1]); // dereg
                    $devIndex = trim($line[0]); // device index
                    
                    $deregCode = (int)$deregRaw;
                   
                    // Setting default values
                    $TxtColor = '"#000000"';
                    $tmpONULastDeregReasonStr = 'Unknown reason';

                    // Check if there is code in the mapping
                    if (isset($DeregReasonsMap[$deregCode])) {
                        $TxtColor = $DeregReasonsMap[$deregCode]['color'];
                        $tmpONULastDeregReasonStr = $DeregReasonsMap[$deregCode]['text'];
                    }

                    // Using wf_tag for formatting
                    $tmpONULastDeregReasonStr = wf_tag('font', false, '', 'color=' . $TxtColor . '') .
                            $tmpONULastDeregReasonStr .
                            wf_tag('font', true);

                    // Store the index in serial number format
                    if (isset($this->onuDevIndexProcessed[$devIndex])) {
                        $result[$this->onuDevIndexProcessed[$devIndex]] = $tmpONULastDeregReasonStr;
                    }
                }
            }

            // saving dereg reasons cache
            $this->olt->writeDeregs($result);
        }
    }
    /**
     * Performs UNI port oper status preprocessing for GPON BDCOM (GP3608)
     *
     * @param array $uniOperStatusIndex
     *
     * @return void
     */
    protected function uniParseGPBd($uniOperStatusIndex) {
        $result = array();

        if (!empty($this->onuDevIndexProcessed) && !empty($uniOperStatusIndex)) {
            foreach ($uniOperStatusIndex as $io => $eachRow) {
                $line = explode('=', $eachRow);
                if (empty($line[0]) || empty($line[1])) {
                    continue;
                }

                $ifIndex = trim(ltrim($line[0], '.'));
                $statusRaw = trim($line[1]);

                // Extract a number from a string of type "up(1)" or "down(2)"
                if (preg_match('/\((\d+)\)/', $statusRaw, $matches)) {
                    $tmpUniStatus = (int)$matches[1];
                } else {
                    $tmpUniStatus = (int)$statusRaw; // in case it's already a pure number
                }

                // Convert to 1/0
                $tmpUniStatus = ($tmpUniStatus == 1) ? 1 : 0;

                // Mapping ifIndex -> ONU Serial
                if (isset($this->onuDevIndexProcessed[$ifIndex])) {
                    $onuSerial = $this->onuDevIndexProcessed[$ifIndex];
                    $result[$onuSerial]['eth1'] = $tmpUniStatus;
                }
            }

            // saving UniOperStats
            $this->olt->writeUniOperStats($result);
        }
    }
}