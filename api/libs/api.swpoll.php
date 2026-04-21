<?php

/*
 * SNMP switch polling API
 */

/**
 * Gets associated list of SNMP templates and switch models
 * 
 * @return array
 */
function sp_SnmpGetModelTemplatesAssoc() {
    $query = "SELECT `id`,`snmptemplate` from `switchmodels` WHERE `snmptemplate`!=''";
    $all = simple_queryall($query);
    $result = array();
    if (!empty($all)) {
        foreach ($all as $io => $each) {
            $result[$each['id']] = $each['snmptemplate'];
        }
    }
    return ($result);
}

/**
 * Returns raw SNMP data from device with caching
 * @param   $ip         device IP
 * @param   $community  SNMP community
 * @param   $oid        OID which will be polled
 * @param   $cache      cache results
 * 
 * @return   string
 */
function sp_SnmpPollData($ip, $community, $oid, $cache = true) {
    // migrated to SNMPHelper class in 0.6.5
    // left this for backward compatibility
    $snmp = new SNMPHelper();
    $snmp->setBackground(false);
    $result = $snmp->walk($ip, $community, $oid, $cache);

    return ($result);
}

/**
 * Returns list of all SWPOLL monitored devices as id=>switchData
 * 
 * @return array
 */
function sp_SnmpGetAllDevices() {
    $switchesDb = new NyanORM('switches');
    $switchesDb->where('snmp', '!=', '');
    $switchesDb->where('desc', 'LIKE', '%SWPOLL%');
    $result = $switchesDb->getAll('id');
    return ($result);
}

/**
 * Returns list of all available SNMP device templates
 * 
 * @return array
 */
function sp_SnmpGetAllModelTemplates() {
    $path = CONFIG_PATH . 'snmptemplates/';
    $privatePath = DATA_PATH . 'documents/mysnmptemplates/';
    $alltemplates = rcms_scandir($path);
    $result = array();
    if (!empty($alltemplates)) {
        foreach ($alltemplates as $each) {
            $result[$each] = rcms_parse_ini_file($path . $each, true);
        }
    }

    $myTemplates = rcms_scandir($privatePath);
    if (!empty($myTemplates)) {
        foreach ($myTemplates as $each) {
            $privateTemplateBody = rcms_parse_ini_file($privatePath . $each, true);
            //checking custom template integrity and marking it as custom
            if (isset($privateTemplateBody['define'])) {
                if (isset($privateTemplateBody['define']['DEVICE'])) {
                    $originaDeviceModel = $privateTemplateBody['define']['DEVICE'];
                    $privateTemplateBody['define']['DEVICE'] = $originaDeviceModel . ' 🚲 ';
                    $result[$each] = $privateTemplateBody;
                }
            }
        }
    }
    return ($result);
}


/**
 * Polls data for some device and updates cache
 * 
 * @global object $ubillingConfig
 * @param string $ip
 * @param string $community
 * @param array $alltemplates
 * @param string $deviceTemplate
 * @param string $communitywrite
 * 
 * @return void
 */
function sp_SnmpPollDevice($ip, $community, $alltemplates, $deviceTemplate, $communitywrite = '') {
    global $ubillingConfig;
    $devPollProcess = new StarDust('SWPOLL_' . $ip);
    $pollingStart = time();
    if ($devPollProcess->notRunning()) {
        $devPollProcess->start();
        if (isset($alltemplates[$deviceTemplate])) {
            $currentTemplate = $alltemplates[$deviceTemplate];
            if (!empty($currentTemplate)) {
                $deviceFdb = (isset($currentTemplate['define']['FDB'])) ? $currentTemplate['define']['FDB'] : 'false';
                $deviceMAC = (isset($currentTemplate['define']['MAC'])) ? $currentTemplate['define']['MAC'] : 'false';
                $pollMode = (isset($currentTemplate['define']['POLLMODE'])) ? $currentTemplate['define']['POLLMODE'] : '';
                $sfpStartPort = (empty($currentTemplate['define']['SFPSTARTPORT'])) ? 1 : $currentTemplate['define']['SFPSTARTPORT'];
                $sfpEndPort = (empty($currentTemplate['define']['SFPENDPORT'])) ? '' : $currentTemplate['define']['SFPENDPORT'];
                $poeStartPort = (empty($currentTemplate['define']['POESTARTPORT'])) ? 1 : $currentTemplate['define']['POESTARTPORT'];
                $poeEndPort = (empty($currentTemplate['define']['POEENDPORT'])) ? '' : $currentTemplate['define']['POEENDPORT'];
                $tempArray = array();
                $portIdxArr = array();
                $portDescrArr = array();
                $alterCfg = $ubillingConfig->getAlter();
                $snmp = new SNMPHelper();

                //selecting FDB processing mode
                if (isset($currentTemplate['define']['FDB_MODE'])) {
                    $deviceFdbMode = $currentTemplate['define']['FDB_MODE'];
                } else {
                    $deviceFdbMode = 'default';
                }

                //selecting Device MAC processing mode
                if (isset($currentTemplate['define']['MAC_MODE'])) {
                    $deviceMACMode = $currentTemplate['define']['MAC_MODE'];
                } else {
                    $deviceMACMode = 'default';
                }

                // selecting FDB allowed only port to process MAC-s only on them
                // "ignored port" list is ignored if "allowed only" list used
                if (!empty($currentTemplate['define']['FDB_ALLOW_ONLY_PORTS'])) {
                    $deviceFdbAllowedPorts = $currentTemplate['define']['FDB_ALLOW_ONLY_PORTS'];
                    $deviceFdbAllowedPorts = explode(',', $deviceFdbAllowedPorts);
                    $deviceFdbAllowedPorts = array_flip($deviceFdbAllowedPorts);
                } else {
                    $deviceFdbAllowedPorts = array();
                }

                //selecting FDB ignored port for skipping MAC-s on it
                if (isset($currentTemplate['define']['FDB_IGNORE_PORTS'])) {
                    $deviceFdbIgnore = $currentTemplate['define']['FDB_IGNORE_PORTS'];
                    $deviceFdbIgnore = explode(',', $deviceFdbIgnore);
                    $deviceFdbIgnore = array_flip($deviceFdbIgnore);
                } else {
                    $deviceFdbIgnore = array();
                }

                // cumulative mode iface processing
                if ($pollMode == 'cumulative' and ! empty($currentTemplate['portiface'])) {
                    $portIdxOID = trim($currentTemplate['portiface']['PORTINDEX']);
                    $portDescrOID = trim($currentTemplate['portiface']['PORTDESCR']);
                    $portAliasOID = trim($currentTemplate['portiface']['PORTALIAS']);

                    // get ports indexes
                    $rawDataPrtIdx = $snmp->walk($ip, $community, $portIdxOID, true);
                    $portIdxArr = sp_parse_sw_port_idx($rawDataPrtIdx, $portIdxOID);

                    // get ports aliases and ports descrs: if empty alias - we will use descr
                    $rawDataPrtDescr = $snmp->walk($ip, $community, $portDescrOID, true);
                    $rawDataPrtAlias = $snmp->walk($ip, $community, $portAliasOID, true);


                    // storing iface indexes with descriptions
                    if (!empty($rawDataPrtDescr)) {
                        $rawDataPrtDescr = explodeRows($rawDataPrtDescr);

                        foreach ($rawDataPrtDescr as $eachRow) {
                            $tmpArr = trimSNMPOutput($eachRow, $portDescrOID . '.');
                            // $tmpArr[0] - iface/port index
                            // $tmpArr[1] - iface/port descr
                            if (!empty($tmpArr[1])) {
                                $portDescrArr[$tmpArr[0]] = $tmpArr[1];
                            }
                        }
                    }

                    // storing iface indexes with aliases
                    // and trying to populate $portDescrArr
                    // but only if we didn't populate it in the descr section above
                    if (!empty($rawDataPrtAlias)) {
                        $rawDataPrtAlias = explodeRows($rawDataPrtAlias);

                        foreach ($rawDataPrtAlias as $eachRow) {
                            $tmpAliasArr = trimSNMPOutput($eachRow, $portAliasOID . '.');

                            // $tmpAliasArr[0] - iface/port index
                            // $tmpAliasArr[1] - iface/port alias
                            if (!empty($tmpAliasArr[0]) and (!isset($portDescrArr[$tmpAliasArr[0]]) or empty($portDescrArr[$tmpAliasArr[0]]))) {
                                // if nothing was found in port description section for current port index
                                if (!empty($tmpAliasArr[1])) {
                                    $portDescrArr[$tmpAliasArr[0]] = $tmpAliasArr[1];
                                } elseif (!empty($tmpAliasArr[0])) {
                                    // just populate descr index with empty value
                                    $portDescrArr[$tmpAliasArr[0]] = '';
                                }
                            }
                        }
                    }

                    if (!empty($portDescrArr)) {
                        $fdbPortDescrCache = serialize($portDescrArr);
                        file_put_contents('exports/' . $ip . '_fdb_portdescr', $fdbPortDescrCache);
                    }
                }


                //parse each section of template
                foreach ($alltemplates[$deviceTemplate] as $section => $eachpoll) {
                    if ($section != 'define' and $section != 'portiface') {
                        $sectionPollMode = (empty($eachpoll['SECTPOLLMODE'])) ? '' : $eachpoll['SECTPOLLMODE'];

                        if ($pollMode == 'cumulative') {
                            @$sectionOids = array($eachpoll['OIDS']);
                        } else {
                            @$sectionOids = explode(',', $eachpoll['OIDS']);
                        }

                        if (isset($eachpoll['SETOIDS'])) {
                            $sectionSetOids = explode(',', $eachpoll['SETOIDS']);
                        } else {
                            $sectionSetOids = array();
                        }

                        //yeah, lets set some oids to this shit
                        if (!empty($sectionSetOids)) {
                            foreach ($sectionSetOids as $eachSetOid) {
                                $eachSetOidRaw = trim($eachSetOid);
                                $eachSetOidRaw = explode('|', $eachSetOidRaw);
                                //all three parts of set squense present
                                if ((isset($eachSetOidRaw[0])) and (isset($eachSetOidRaw[1])) and (isset($eachSetOidRaw[2]))) {
                                    $setDataTmp[0] = array('oid' => $eachSetOidRaw[0], 'type' => $eachSetOidRaw[1], 'value' => $eachSetOidRaw[2]);
                                    if (!empty($communitywrite)) {
                                        $runSet = $snmp->set($ip, $communitywrite, $setDataTmp);
                                    }
                                }
                            }
                        }


                        //now cache each oid
                        if (!empty($sectionOids)) {
                            // in cumulative mode we are not aware of ports amount
                            // so, need to fulfill each section OID with port number
                            // and populate $sectionOids array with OID for each port, like in conservative mode
                            if ($pollMode == 'cumulative' and $sectionPollMode != 'noncumulative' and ! empty($portIdxArr)) {
                                $tmpOID = $sectionOids[0];
                                $sectionOids = array();
                                $isSFPSection = ispos($section, 'sfp');
                                $sfpEndPort = ($isSFPSection and empty($sfpEndPort)) ? $portIdxArr[count($portIdxArr)] : $sfpEndPort;
                                $isPOESection = ispos($section, 'poe');
                                $poeEndPort = ($isPOESection and empty($poeEndPort)) ? $portIdxArr[count($portIdxArr)] : $poeEndPort;

                                foreach ($portIdxArr as $eachPort) {
                                    if (empty($eachPort)) {
                                        continue;
                                    }

                                    if ($isSFPSection and ($eachPort < $sfpStartPort or $eachPort > $sfpEndPort)) {
                                        continue;
                                    }

                                    if ($isPOESection and ($eachPort < $poeStartPort or $eachPort > $poeEndPort)) {
                                        continue;
                                    }

                                    $sectionOids[] = $tmpOID . '.' . $eachPort;
                                }
                            }

                            if ($section != 'cablediag') {
                                foreach ($sectionOids as $eachOid) {
                                    $eachOid = trim($eachOid);
                                    $rawData = $snmp->walk($ip, $community, $eachOid, true);
                                    $rawData = str_replace('"', '`', $rawData);
                                }
                            }
                        }
                    }
                }

                //
                //parsing data from FDB table
                //
                if ($deviceFdb == 'true') {
                    $portData = array();
                    $vlanData = array();
                    $portTable = '';
                    $statusTable = '';
                    $portTabOID = '';
                    $portTabOIDVal = '';
                    $statusOID = '';
                    $statusOIDVal = '';
                    $dot1Q = false;
                    $snmp->setBackground(false); // need to process data with system + background
                    if ($deviceFdbMode == 'default') {
                        //default zyxel & cisco port table
                        $portTable = $snmp->walk($ip, $community, '.1.3.6.1.2.1.17.4.3.1.2', true);
                    } elseif ($deviceFdbMode == 'sw_cumulative') {
                        if (!empty($currentTemplate['port.1d_fdb'])) {
                            $portTabOID = trim($currentTemplate['port.1d_fdb']['PORTTABLE']);
                            $statusOID = trim($currentTemplate['port.1d_fdb']['PORTSTATUS']);
                            $portTable = $snmp->walk($ip, $community, $portTabOID, true);
                            $statusTable = $snmp->walk($ip, $community, $statusOID, true);
                        }

                        if (!empty($currentTemplate['port.1q_fdb'])) {
                            $portQTabOID = trim($currentTemplate['port.1q_fdb']['PORTTABLE']);
                            $statusQOID = trim($currentTemplate['port.1q_fdb']['PORTSTATUS']);
                            $portQTable = $snmp->walk($ip, $community, $portQTabOID, true);
                            $statusQTable = $snmp->walk($ip, $community, $statusQOID, true);
                        }

                        // if dot1Q table is not empty - we prefer it's data
                        // as it's usually more detailed and contains VLAN data
                        if (
                            !empty($portQTable) and ! empty($statusQTable)
                            and ! ispos($portQTable, 'No Such Object available')
                            and ! ispos($statusQTable, 'No Such Object available')
                            and ! ispos($portQTable, 'No more variables left')
                            and ! ispos($statusQTable, 'No more variables left')
                        ) {

                            $dot1Q = true;
                            $portTabOID = $portQTabOID;
                            $statusOID = $statusQOID;
                            $portTable = $portQTable;
                            $statusTable = $statusQTable;
                        }
                    } else {
                        if (($deviceFdbMode == 'dlp') or ($deviceFdbMode == 'ra')) {
                            //custom dlink port table with VLANS
                            $portTable = $snmp->walk($ip, $community, '.1.3.6.1.2.1.17.7.1.2.2.1.2', true);
                        }

                        if ($deviceFdbMode == 'tlp5428ev2') {
                            $tlpOid = '.1.3.6.1.4.1.11863.1.1.1.2.3.2.2.1.3';
                            $portTable = $snmp->walk($ip, $community, $tlpOid, true);
                        }

                        if ($deviceFdbMode == 'tlp2428') {
                            $tlpOid = '.1.3.6.1.4.1.11863.1.1.11.2.3.2.2.1.3';
                            $portTable = $snmp->walk($ip, $community, $tlpOid, true);
                        }

                        if ($deviceFdbMode == 'tlp2210') {
                            $tlpOid = '.1.3.6.1.4.1.11863.1.1.19.2.3.2.2.1.3';
                            $portTable = $snmp->walk($ip, $community, $tlpOid, true);
                        }

                        //foxgate lazy parsing
                        if ($deviceFdbMode == 'flp') {
                            $flpOid = '.1.3.6.1.2.1.17.7.1.2.3.1.2';
                            $portTable = $snmp->walk($ip, $community, $flpOid, true);
                        }

                        //cisco ebobo parser
                        if ($deviceFdbMode == 'ciscoebobo') {
                            $portTable = $snmp->walk($ip, $community, '.1.3.6.1.2.1.17.4.3.1.2', true);
                        }
                    }

                    if (!empty($portTable)) {
                        if ($deviceFdbMode == 'default') {
                            //default FDB parser
                            $portData = sp_SnmpParseFDB($portTable);
                        } elseif ($deviceFdbMode == 'sw_cumulative') {
                            $portData = sp_SnmpParseFdbCumulative($portTable, $statusTable, $portTabOID, $statusOID, $dot1Q);

                            if ($dot1Q and ! empty($portData)) {
                                // saving array to temp var for further processing
                                $tmpPortData = $portData;
                                $portData = array();

                                // separating port and vlan data to different arrays
                                foreach ($tmpPortData as $eachMAC => $eachData) {
                                    if (!empty($eachData)) {
                                        foreach ($eachData as $each) {
                                            // making array keys like "MAC_VLAN" to provide their uniqueness
                                            $portData[$eachMAC . '_' . $each['vlan']] = $each['port'];
                                            $vlanData[$eachMAC . '_' . $each['vlan']] = $each['vlan'];
                                        }
                                    }
                                }
                            }
                        } else {
                            if ($deviceFdbMode == 'dlp') {
                                //exotic dlink parser
                                $portData = sp_SnmpParseFdbDl($portTable);
                            }

                            if ($deviceFdbMode == 'ra') {
                                //exotic Raisecom parser
                                $portData = sp_SnmpParseFdbRa($portTable);
                            }

                            if (($deviceFdbMode == 'tlp5428ev2') or ($deviceFdbMode == 'tlp2428') or ($deviceFdbMode == 'tlp2210')) {
                                //more exotic tplink parser
                                $portData = sp_SnmpParseFdbTlp($portTable, $tlpOid);
                            }

                            //foxgate - its you again? Oo
                            if ($deviceFdbMode == 'flp') {
                                $portData = sp_SnmpParseFdbFlp($portTable, $flpOid);
                            }

                            //cisco 3xx series giga-port fucking issue
                            if ($deviceFdbMode == 'ciscoebobo') {
                                $portData = sp_SnmpParseFdbCisEb($portTable, '.1.3.6.1.2.1.17.4.3.1.2');
                            }
                        }

                        // processing FDB allowed only ports for cumulative mode
                        // and make an exclusion of allowed ports and ignored ports
                        // to leave only ports which are allowed
                        // thus, if port is in allowed list and in ignored list at the same time - it will not be ignored
                        if (!empty($deviceFdbAllowedPorts)) {
                            if (!empty($portData)) {
                                foreach ($portData as $some_mac => $some_port) {
                                    if (isset($deviceFdbAllowedPorts[$some_port])) {
                                        $tempArray[$some_mac] = $some_port;
                                    }
                                }
                                $portData = $tempArray;
                            }
                        } elseif (!empty($deviceFdbIgnore)) {
                            //skipping some port data if FDB_IGNORE_PORTS option is set
                            if (!empty($portData)) {
                                foreach ($portData as $some_mac => $some_port) {
                                    if (!isset($deviceFdbIgnore[$some_port])) {
                                        $tempArray[$some_mac] = $some_port;
                                    }
                                }
                                $portData = $tempArray;
                            }
                        }

                        $fdbCache = serialize($portData);
                        @file_put_contents('exports/' . $ip . '_fdb', $fdbCache);

                        if (!empty($vlanData)) {
                            $fdbVLANCache = serialize($vlanData);
                            file_put_contents('exports/' . $ip . '_fdb_vlan', $fdbVLANCache);
                        }
                    }
                }
                //
                //parsing data of DEVICE MAC
                //
                if ($alterCfg['SWITCHES_SNMP_MAC_EXORCISM'] and $deviceMAC == 'true') {
                    $MacOfDevice = '';
                    $snmp->setBackground(false); // need to process data with system + background

                    if ($deviceMACMode == 'default') {
                        //default for many D-link HP JunOS
                        $MacOfDevice = $snmp->walk($ip, $community, '.1.0.8802.1.1.2.1.3.2.0', true);
                    }

                    if (!empty($MacOfDevice)) {
                        if ($deviceMACMode == 'default') {
                            //default M parser
                            $MACData = sn_SnmpParseDeviceMAC($MacOfDevice);
                        } 

                        // Write Device MAC address to file
                        if (!empty($MACData)) {
                            file_put_contents('exports/' . $ip . '_MAC', $MACData);
                        }
                    }
                }
            }
        }
    }
    //filling device polling stats
    $devPollProcess->stop();
    $pollingEnd = time();
    $statsPath = 'exports/HORDE_' . $ip;
    $cachedStats = array();
    $cachedStats['start'] = $pollingStart;
    $cachedStats['end'] = $pollingEnd;
    $cachedStats = serialize($cachedStats);
    @file_put_contents($statsPath, $cachedStats);
}

/**
 * Returns compact render string with cached polling time for device
 *
 * @param string $ip
 *
 * @return string
 */
function web_SnmpGetDeviceCacheTime($ip) {
    $result = '';
    $hordeStatsPath = 'exports/HORDE_' . $ip;
    if (file_exists($hordeStatsPath)) {
        $messages = new UbillingMessageHelper();
        $rawHordeStats = file_get_contents($hordeStatsPath);
        if (!empty($rawHordeStats)) {
            $parsedHordeStats = @unserialize($rawHordeStats);
            if (!empty($parsedHordeStats) and isset($parsedHordeStats['start']) and isset($parsedHordeStats['end'])) {
                $startTime = (int) $parsedHordeStats['start'];
                $endTime = (int) $parsedHordeStats['end'];
                $pollingDuration = $endTime - $startTime;
                $startDate = date("Y-m-d", $startTime);
                $endDate = date("Y-m-d", $endTime);
                if ($startDate == $endDate) {
                    $result .= $startDate . ' ';
                    $result .= __('from').' '. date("H:i:s", $startTime) . ' ' . __('to') . ' ' . date("H:i:s", $endTime);
                } else {
                    $result .= date("Y-m-d H:i:s", $startTime);
                    $result .= ' - ';
                    $result .= date("Y-m-d H:i:s", $endTime);
                }
                $result .= ', ' . __('polling time') . ': ' . zb_formatTime($pollingDuration);
            }

            $result = $messages->getStyledMessage($result, 'info');
        }
    }
    return ($result);
}

/**
 * Renders cached SNMP polling data for selected device, if exists
 *
 * @param string $ip
 * @param array $alltemplates
 * @param string $deviceTemplate
 * @param array $allusermacs
 * @param array $alladdress
 * @param array $allswitchmacs
 *
 * @return bool
 */
function web_SnmpRenderDevCache($ip, $alltemplates, $deviceTemplate, $allusermacs, $alladdress, $allswitchmacs = array()) {
    global $ubillingConfig;
    $result = false;
    $portIdxArr = array();
    $portDescrArr = array();
    $cachedTimeData = web_SnmpGetDeviceCacheTime($ip);

    if (isset($alltemplates[$deviceTemplate])) {
        $currentTemplate = $alltemplates[$deviceTemplate];
        $pollMode = (isset($currentTemplate['define']['POLLMODE'])) ? $currentTemplate['define']['POLLMODE'] : '';
        $sfpStartPort = (empty($currentTemplate['define']['SFPSTARTPORT'])) ? 1 : $currentTemplate['define']['SFPSTARTPORT'];
        $sfpEndPort = (empty($currentTemplate['define']['SFPENDPORT'])) ? '' : $currentTemplate['define']['SFPENDPORT'];
        $poeStartPort = (empty($currentTemplate['define']['POESTARTPORT'])) ? 1 : $currentTemplate['define']['POESTARTPORT'];
        $poeEndPort = (empty($currentTemplate['define']['POEENDPORT'])) ? '' : $currentTemplate['define']['POEENDPORT'];
        $finalResult = '';
        $hasSnmpData = false;

        if ($pollMode == 'cumulative' and ! empty($currentTemplate['portiface'])) {
            $portIdxOID = trim($currentTemplate['portiface']['PORTINDEX']);
            $portDescrOID = trim($currentTemplate['portiface']['PORTDESCR']);
            $portAliasOID = trim($currentTemplate['portiface']['PORTALIAS']);

            $portIdxCachePath = 'exports/' . $ip . '_' . $portIdxOID;
            if (file_exists($portIdxCachePath)) {
                $rawDataPrtIdx = file_get_contents($portIdxCachePath);
                $portIdxArr = sp_parse_sw_port_idx($rawDataPrtIdx, $portIdxOID);
            }

            $portDescrCachePath = 'exports/' . $ip . '_' . $portDescrOID;
            if (file_exists($portDescrCachePath)) {
                $rawDataPrtDescr = file_get_contents($portDescrCachePath);
                if (!empty($rawDataPrtDescr)) {
                    $rawDataPrtDescr = explodeRows($rawDataPrtDescr);
                    foreach ($rawDataPrtDescr as $eachRow) {
                        $tmpArr = trimSNMPOutput($eachRow, $portDescrOID . '.');
                        if (!empty($tmpArr[1])) {
                            $portDescrArr[$tmpArr[0]] = $tmpArr[1];
                        }
                    }
                }
            }

            $portAliasCachePath = 'exports/' . $ip . '_' . $portAliasOID;
            if (file_exists($portAliasCachePath)) {
                $rawDataPrtAlias = file_get_contents($portAliasCachePath);
                if (!empty($rawDataPrtAlias)) {
                    $rawDataPrtAlias = explodeRows($rawDataPrtAlias);
                    foreach ($rawDataPrtAlias as $eachRow) {
                        $tmpAliasArr = trimSNMPOutput($eachRow, $portAliasOID . '.');
                        if (!empty($tmpAliasArr[0]) and (!isset($portDescrArr[$tmpAliasArr[0]]) or empty($portDescrArr[$tmpAliasArr[0]]))) {
                            if (!empty($tmpAliasArr[1])) {
                                $portDescrArr[$tmpAliasArr[0]] = $tmpAliasArr[1];
                            } elseif (!empty($tmpAliasArr[0])) {
                                $portDescrArr[$tmpAliasArr[0]] = '';
                            }
                        }
                    }
                }
            }
        }

        foreach ($alltemplates[$deviceTemplate] as $section => $eachpoll) {
            if ($section != 'define' and $section != 'portiface') {
                @$sectionName = $eachpoll['NAME'];
                $sectionPollMode = (empty($eachpoll['SECTPOLLMODE'])) ? '' : $eachpoll['SECTPOLLMODE'];
                $sectionResult = '';

                if ($pollMode == 'cumulative') {
                    @$sectionOids = array($eachpoll['OIDS']);
                } else {
                    @$sectionOids = explode(',', $eachpoll['OIDS']);
                }

                $sectionDivBy = (empty($eachpoll['DIV'])) ? ', ""' : ', "' . $eachpoll['DIV'] . '"';
                $sectionUnits = (empty($eachpoll['UNITS'])) ? ', ""' : ', "' . $eachpoll['UNITS'] . '"';
                @$sectionParser = $eachpoll['PARSER'];

                if ($section == 'portdesc' and $pollMode == 'cumulative' and ! empty($portDescrArr)) {
                    $sectionResult = sp_parse_sw_port_descr($portDescrArr);
                } else {
                    if (!empty($sectionOids)) {
                        if ($pollMode == 'cumulative' and $sectionPollMode != 'noncumulative' and ! empty($portIdxArr)) {
                            $tmpOID = $sectionOids[0];
                            $sectionOids = array();
                            $isSFPSection = ispos($section, 'sfp');
                            $sfpEndPort = ($isSFPSection and empty($sfpEndPort)) ? $portIdxArr[count($portIdxArr)] : $sfpEndPort;
                            $isPOESection = ispos($section, 'poe');
                            $poeEndPort = ($isPOESection and empty($poeEndPort)) ? $portIdxArr[count($portIdxArr)] : $poeEndPort;

                            foreach ($portIdxArr as $eachPort) {
                                if (empty($eachPort)) {
                                    continue;
                                }
                                if ($isSFPSection and ($eachPort < $sfpStartPort or $eachPort > $sfpEndPort)) {
                                    continue;
                                }
                                if ($isPOESection and ($eachPort < $poeStartPort or $eachPort > $poeEndPort)) {
                                    continue;
                                }
                                $sectionOids[] = $tmpOID . '.' . $eachPort;
                            }
                        }

                        if ($section != 'cablediag') {
                            foreach ($sectionOids as $eachOid) {
                                $eachOid = trim($eachOid);
                                $rawDataPath = 'exports/' . $ip . '_' . $eachOid;
                                $rawData = '';
                                if (file_exists($rawDataPath)) {
                                    $rawData = file_get_contents($rawDataPath);
                                    $rawData = str_replace('"', '`', $rawData);
                                }

                                if (!empty($rawData)) {
                                    if (!empty($sectionParser)) {
                                        if (function_exists($sectionParser)) {
                                            if (empty($sectionDivBy) and empty($sectionUnits)) {
                                                $parseCode = '$sectionResult.=' . $sectionParser . '("' . $rawData . '");';
                                            } else {
                                                $parseCode = '$sectionResult.=' . $sectionParser . '("' . $rawData . '"' . $sectionDivBy . $sectionUnits . ');';
                                            }
                                            eval($parseCode);
                                        } else {
                                            $sectionResult = __('Parser') . ' "' . $sectionParser . '" ' . __('Not exists');
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                if (!empty($sectionResult)) {
                    $finalResult .= wf_tag('div', false, 'dashboard', '');
                    $finalResult .= wf_tag('div', false, 'dashtask', '') . wf_tag('strong') . __($sectionName) . wf_tag('strong', true) . '<br>';
                    $finalResult .= $sectionResult . wf_tag('div', true);
                    $finalResult .= wf_tag('div', true);
                    $hasSnmpData = true;
                }
            }
        }

        if ($hasSnmpData) {
            $finalResult .= wf_tag('div', false, '', 'style="clear:both;"');
            $finalResult .= wf_tag('div', true);
            show_window('', $finalResult);
            $result = true;
        }
    }

    $fdbPath = 'exports/' . $ip . '_fdb';
    if (file_exists($fdbPath)) {
        $fdbRaw = file_get_contents($fdbPath);
        $portData = unserialize($fdbRaw);
        if (!empty($portData)) {
            $fdbExtenInfo = $ubillingConfig->getAlterParam('SW_FDB_EXTEN_INFO');
            $alterCfg = $ubillingConfig->getAlter();
            $vlanData = array();
            $fdbPortDescrArr = array();

            if ($fdbExtenInfo) {
                $fdbVlanPath = 'exports/' . $ip . '_fdb_vlan';
                if (file_exists($fdbVlanPath)) {
                    $vlanRaw = file_get_contents($fdbVlanPath);
                    $vlanData = unserialize($vlanRaw);
                }

                $fdbPortDescrPath = 'exports/' . $ip . '_fdb_portdescr';
                if (file_exists($fdbPortDescrPath)) {
                    $fdbPortDescrRaw = file_get_contents($fdbPortDescrPath);
                    $fdbPortDescrArr = unserialize($fdbPortDescrRaw);
                }
            }

            if ($alterCfg['SWITCHPORT_IN_PROFILE']) {
                $allswitchesArray = zb_SwitchesGetAll();
                $allportassigndata = array();
                $allportassigndata_q = "SELECT * from `switchportassign`;";
                $allportassigndata_raw = simple_queryall($allportassigndata_q);
                if (!empty($allportassigndata_raw)) {
                    foreach ($allportassigndata_raw as $iopd => $eachpad) {
                        $allportassigndata[$eachpad['login']] = $eachpad;
                    }
                }
            } else {
                $allswitchesArray = array();
                $allportassigndata = array();
            }

            $allusermacs = array_flip($allusermacs);
            $recordsCounter = 0;
            $cells = wf_TableCell(__('User') . ' / ' . __('Device'), '30%');
            $cells .= wf_TableCell(__('MAC'));
            $cells .= wf_TableCell(__('Ports'));

            if ($fdbExtenInfo) {
                $cells .= wf_TableCell(__('Port description'));
                $cells .= wf_TableCell(__('VLAN'));
            }

            $rows = wf_TableRow($cells, 'row1');

            foreach ($portData as $eachMac => $eachPort) {
                $eachMAC_VLAN = '';
                if (ispos($eachMac, '_')) {
                    $eachMAC_VLAN = $eachMac;
                    $eachMac = substr($eachMac, 0, stripos($eachMac, '_'));
                }

                if (isset($allusermacs[$eachMac])) {
                    $userLogin = $allusermacs[$eachMac];
                    @$useraddress = $alladdress[$userLogin];
                    $userlink = wf_Link('?module=userprofile&username=' . $userLogin, web_profile_icon() . ' ' . $useraddress, false);
                    if ($alterCfg['SWITCHPORT_IN_PROFILE']) {
                        $assignForm = wf_modal(web_edit_icon(__('Switch port assign')), __('Switch port assign'), web_SnmpSwitchControlForm($userLogin, $allswitchesArray, $allportassigndata, @$_GET['switchid'], $eachPort), '', '500', '250');
                        if (isset($allportassigndata[$userLogin])) {
                            $assignForm .= wf_img('skins/arrow_right_green.png') . @$allportassigndata[$userLogin]['port'];
                        }
                    } else {
                        $assignForm = '';
                    }
                } else {
                    if (isset($allswitchmacs[$eachMac])) {
                        @$switchAddress = $allswitchmacs[$eachMac]['location'];
                        @$switchId = $allswitchmacs[$eachMac]['id'];
                        @$switchIp = $allswitchmacs[$eachMac]['ip'];
                        $switchLabel = (!empty($switchAddress)) ? $switchAddress : $switchIp;
                        $userlink = wf_Link('?module=switches&edit=' . $switchId, wf_img_sized('skins/menuicons/switches.png', __('Switch'), 11, 13) . ' ' . $switchLabel);
                        $assignForm = '';
                    } else {
                        $userlink = '';
                        $assignForm = '';
                    }
                }

                $cells = wf_TableCell($userlink . $assignForm, '', '', 'sorttable_customkey="' . $eachPort . '"');
                $cells .= wf_TableCell($eachMac);
                $cells .= wf_TableCell($eachPort);

                if ($fdbExtenInfo) {
                    $eachPortDescr = '';
                    $eachVLAN = '';
                    if (!empty($fdbPortDescrArr[$eachPort])) {
                        $eachPortDescr = $fdbPortDescrArr[$eachPort];
                    }
                    if (!empty($vlanData[$eachMAC_VLAN])) {
                        $eachVLAN = $vlanData[$eachMAC_VLAN];
                    }
                    $cells .= wf_TableCell($eachPortDescr);
                    $cells .= wf_TableCell($eachVLAN);
                }

                $rows .= wf_TableRow($cells, 'row5');
                $recordsCounter++;
            }

            $fdbTableResult = wf_TableBody($rows, '100%', '0', 'sortable');
            $fdbTableResult .= wf_tag('b') . __('Total') . ': ' . $recordsCounter . wf_tag('b', true);
            show_window(__('FDB'), $fdbTableResult);
            $result = true;
        }
    }

    if (!empty($cachedTimeData)) {
        show_window(__('Device polled'), $cachedTimeData);
    }

    if (!$result) {
        show_warning(__('No previous polling data found'));
    }

    return ($result);
}

/**
 * Check MAC address for filter
 * 
 * @param string $mac
 * @param array $allfilters
 * @return bool
 */
function sn_FDBFilterCheckMac($mac, $allfilters) {
    $result = true;
    if (!empty($allfilters)) {
        if (isset($allfilters[$mac])) {
            $result = true;
        } else {
            $result = false;
        }
    }
    return ($result);
}

/**
 * Renders JSON data for display FDB cache
 * 
 * @global object $ubillingConfig
 * @param array $fdbData_raw
 * @param string $macFilter
 * 
 * @return void
 */
function sn_SnmpParseFdbCacheJson($fdbData_raw, $macFilter, $fdbVLANData_raw = array()) {
    global $ubillingConfig;
    $allusermacs = zb_UserGetAllMACs();
    $allusermacs = array_flip($allusermacs);
    $alladdress = zb_AddressGetFulladdresslist();
    $allswitches = zb_SwitchesGetAll();
    $rawFilters = zb_StorageGet('FDBCACHEMACFILTERS');
    $filteredCounter = 0;
    $switchdata = array();
    $switchIds = array();
    $allfilters = array();
    $allswitchmacs = array();
    $switchesExtFlag = $ubillingConfig->getAlterParam('SWITCHES_EXTENDED');
    $fdbExtenInfo = $ubillingConfig->getAlterParam('SW_FDB_EXTEN_INFO');
    $json = new wf_JqDtHelper();

    //switch data preprocessing
    if (!empty($allswitches)) {
        foreach ($allswitches as $io => $eachswitch) {
            $switchdata[$eachswitch['ip']] = $eachswitch['location'];
            $switchIds[$eachswitch['ip']] = $eachswitch['id'];
            if ($switchesExtFlag) {
                $allswitchmacs[$eachswitch['swid']]['id'] = $eachswitch['id'];
                $allswitchmacs[$eachswitch['swid']]['ip'] = $eachswitch['ip'];
                $allswitchmacs[$eachswitch['swid']]['location'] = $eachswitch['location'];
            }
        }
    }
    //mac filters preprocessing
    if (!empty($rawFilters)) {
        $rawFilters = base64_decode($rawFilters);
        $rawFilters = explodeRows($rawFilters);
        if (!empty($rawFilters)) {
            foreach ($rawFilters as $rawfindex => $rawfmac) {
                $eachMacFilter = strtolower($rawfmac);
                $allfilters[trim($eachMacFilter)] = $rawfindex;
            }
        }
    }

    //single mac filter processing
    if (!empty($macFilter)) {
        $allfilters[trim($macFilter)] = '42'; // The Ultimate Question of Life, the Universe, and Everything
    }

    foreach ($fdbData_raw as $each_raw) {
        $nameExplode = explode('_', $each_raw);
        if (sizeof($nameExplode) == 2) {
            $switchIp = $nameExplode[0];
            $switchId = (isset($switchIds[$switchIp])) ? $switchIds[$switchIp] : '';
            $switchControls = '';
            if (!empty($switchId)) {
                if (cfr('SWITCHES')) {
                    $switchControls .= wf_Link('?module=switches&edit=' . $switchId, web_edit_icon());
                }
            }
            if (file_exists('exports/' . $each_raw)) {
                $eachfdb_raw = file_get_contents('exports/' . $each_raw);
                $eachfdb = unserialize($eachfdb_raw);

                if (!empty($eachfdb_raw)) {
                    $eachfdb_vlan = array();
                    $eachfdb_portdescr = array();

                    if ($fdbExtenInfo) {
                        if (file_exists('exports/' . $each_raw . '_vlan')) {
                            $eachfdb_vlan_raw = file_get_contents('exports/' . $each_raw . '_vlan');
                            $eachfdb_vlan = unserialize($eachfdb_vlan_raw);
                        }

                        if (file_exists('exports/' . $each_raw . '_portdescr')) {
                            $eachfdb_portdescr_raw = file_get_contents('exports/' . $each_raw . '_portdescr');
                            $eachfdb_portdescr = unserialize($eachfdb_portdescr_raw);
                        }
                    }

                    foreach ($eachfdb as $mac => $port) {
                        // if we have MACs stored along with VLANs (separated with underscore '_')
                        // - we need to extract MAC portion
                        $eachMAC_VLAN = '';

                        if (ispos($mac, '_')) {
                            // storing original value in "MAC_VLAN" representation
                            $eachMAC_VLAN = $mac;
                            // storing only extracted MAC portion
                            $mac = substr($mac, 0, stripos($mac, '_'));
                        }

                        //detecting user login by his mac
                        if (isset($allusermacs[$mac])) {
                            $userlogin = $allusermacs[$mac];
                        } else {
                            $userlogin = false;
                        }

                        if ($userlogin) {
                            $userlink = wf_Link('?module=userprofile&username=' . $userlogin, web_profile_icon() . ' ' . @$alladdress[$userlogin], $allfilters, false, '');
                        } else {
                            if (isset($allswitchmacs[$mac])) {
                                @$switchAddress = $allswitchmacs[$mac]['location'];
                                @$switchIdL = $allswitchmacs[$mac]['id'];
                                @$switchIpL = $allswitchmacs[$mac]['ip'];
                                $switchLabel = (!empty($switchAddress)) ? $switchAddress : $switchIpL;
                                $userlink = wf_Link('?module=switches&edit=' . $switchIdL, wf_img_sized('skins/menuicons/switches.png', __('Switch'), 11, 13) . ' ' . $switchLabel);
                            } else {
                                $userlink = '';
                            }
                        }

                        if (sn_FDBFilterCheckMac($mac, $allfilters)) {
                            $data[] = $switchIp;
                            $data[] = $port;

                            if ($fdbExtenInfo) {
                                $eachPortDescr = '';
                                $eachVLAN = '';

                                if (!empty($eachfdb_portdescr[$port])) {
                                    $eachPortDescr = $eachfdb_portdescr[$port];
                                }

                                if (!empty($eachfdb_vlan[$eachMAC_VLAN])) {
                                    $eachVLAN = $eachfdb_vlan[$eachMAC_VLAN];
                                }

                                $data[] = $eachPortDescr;
                                $data[] = $eachVLAN;
                            }

                            $data[] = @$switchdata[$switchIp] . ' ' . $switchControls;
                            $data[] = $mac;
                            $data[] = $userlink;
                            $json->addRow($data);
                            unset($data);
                            $filteredCounter++;
                        }
                    }
                }
            }
        }
    }
    $json->getJson();
}

/**
 * function that returns array data for existing FDB cache
 * 
 * @param $fdbData_raw - array of existing cache _fdb files
 * 
 * @return  array
 */
function sn_SnmpParseFdbCacheArray($fdbData_raw) {
    $allswitches = zb_SwitchesGetAll();
    $switchdata = array();
    $result = array();

    //switch data preprocessing
    if (!empty($allswitches)) {
        foreach ($allswitches as $io => $eachswitch) {
            $switchdata[$eachswitch['ip']] = $eachswitch['location'];
        }
    }

    foreach ($fdbData_raw as $each_raw) {
        $nameExplode = explode('_', $each_raw);
        if (sizeof($nameExplode) == 2) {
            $switchIp = $nameExplode[0];
            $eachfdb_raw = file_get_contents('exports/' . $each_raw);
            $eachfdb = unserialize($eachfdb_raw);
            if (!empty($eachfdb_raw)) {
                foreach ($eachfdb as $mac => $port) {
                    if (@!empty($switchdata[$switchIp])) {
                        $switchDesc = $switchIp . ' - ' . @$switchdata[$switchIp];
                    } else {
                        $switchDesc = $switchIp;
                    }
                    $result[$mac][] = $switchDesc . ' ' . __('Port') . ': ' . $port;
                }
            }
        }
    }

    return ($result);
}

/**
 * Extracts array data for some mac from sn_SnmpParseFdbCacheArray results
 * 
 * @param array $data
 * 
 * @return string
 */
function sn_SnmpParseFdbExtract($data) {
    $result = '';
    $modalContent = '';
    if (!empty($data)) {
        if (sizeof($data) == 1) {
            foreach ($data as $io => $each) {
                $result .= $each;
            }
        } else {
            foreach ($data as $io => $each) {
                $modalContent .= $each . wf_tag('br');
            }
            $result .= $each . ' ' . wf_modal(wf_img_sized('skins/menuicons/switches.png', __('Switches'), '12', '12'), __('Switches'), $modalContent, '', '600', '400');
        }
    }
    return ($result);
}

/**
 * Extracts MAC of device
 *
 * @param raw data $data
 *
 * @return string
 */
function sn_SnmpParseDeviceMAC($data) {
    $result = '';

    if (!empty($data)) {
        $data = explode('=', $data);
        $device_mac_raw = str_replace('Hex-STRING:', '', @$data[1]);
        $device_mac_t = trim($device_mac_raw);
        if (!empty($device_mac_t)) {
            $device_mac = str_replace(" ", ":", $device_mac_t);
            $result_temp = strtolower($device_mac);
            if (check_mac_format($result_temp)) {
                $result = $result_temp;
            }
        }
    }
    return ($result);
}

/**
 * Cleans data types from raw SNMP request data. Returns only filtered value.
 *
 * @param string $data
 *
 * @return string
 */
function zb_SanitizeSNMPValue($data) {
    $result = '';
    $dataTypes = array(
        'Hex-STRING:',
        'Counter32:',
        'Counter64:',
        'Gauge32:',
        'Gauge64:',
        'INTEGER:',
        'STRING:',
        'OID:',
        'Timeticks:',
        'Network Address:'
    );

    if (!empty($data)) {
        $data = explode('=', $data);
        if (isset($data[1])) {
            $result = str_ireplace($dataTypes, '', $data[1]);
            $result = trim($result);
        }
    }


    return ($result);
}



/**
 * Returns FDB cache lister MAC filters setup form
 * 
 * @param string $currentFilters
 * 
 * @return string
 */
function web_FDBTableFiltersForm($currentFilters) {
    if (!empty($currentFilters)) {
        $currentFilters = base64_decode($currentFilters);
    }

    $inputs = __('One MAC address per line') . wf_tag('br');
    $inputs .= wf_TextArea('newmacfilters', '', $currentFilters, true, '40x10');
    $inputs .= wf_HiddenInput('setmacfilters', 'true');
    $inputs .= wf_CheckInput('deletemacfilters', __('Cleanup'), true, false);
    $inputs .= wf_Submit(__('Save'));
    $result = wf_Form('', 'POST', $inputs, 'glamour');

    return ($result);
}

/**
 * Shows current FDB cache list container
 * 
 * @param string $fdbSwitchFilter
 */
function web_FDBTableShowDataTable($fdbSwitchFilter = '', $fdbMacFilter = '') {
    global $ubillingConfig;
    $fdbExtenInfo = $ubillingConfig->getAlterParam('SW_FDB_EXTEN_INFO');
    $filter = '';
    $macfilter = '';
    $result = '';
    $filter = (!empty($fdbSwitchFilter)) ? '&swfilter=' . $fdbSwitchFilter : '';
    $macfilter = (!empty($fdbMacFilter)) ? '&macfilter=' . $fdbMacFilter : '';
    $currentFilters = zb_StorageGet('FDBCACHEMACFILTERS');

    $filtersForm = wf_modalAuto(web_icon_search('MAC filters setup'), __('MAC filters setup'), web_FDBTableFiltersForm($currentFilters), '');
    if (!empty($currentFilters)) {
        $filtersForm .= ' ' . wf_img('skins/filter_icon.png', __('Filters'));
    }
    $mainControls = FDBArchive::renderNavigationPanel();
    show_window('', $mainControls);

    if ($fdbExtenInfo) {
        $columns = array('Switch IP', 'Port', __('Port description'), 'VLAN', 'Location', 'MAC', __('User') . ' / ' . __('Device'));
    } else {
        $columns = array('Switch IP', 'Port', 'Location', 'MAC', __('User') . ' / ' . __('Device'));
    }

    $result .= wf_JqDtLoader($columns, '?module=fdbcache&ajax=true' . $filter . $macfilter, true, 'Objects', 100);

    show_window(__('Current FDB cache') . ' ' . $filtersForm, $result);
}
