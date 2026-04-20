<?php

/**
 * Convert splitted decimal MAC to normal view
 * 
 * @param array $parts
 * 
 * @return string
 */
function sp_PartsToMac($parts) {
    $result = '';
    //format + mac is present?
    if (count($parts) == 6) {
        foreach ($parts as $io => $eachPart) {
            $result .= sprintf('%02X', $eachPart) . ':';
        }
        $result = rtrim($result, ':');
        $result = strtolower($result);
    }
    return ($result);
}



/**
 * Parsing of FDB port table SNMP raw data
 * 
 * @param   $portTable raw SNMP data
 * 
 * @return  array
 */
function sp_SnmpParseFdb($portTable) {
    $portData = array();
    $arr_PortTable = explodeRows($portTable);
    if (!empty($arr_PortTable)) {
        foreach ($arr_PortTable as $eachEntry) {
            if (!empty($eachEntry)) {
                $eachEntry = str_replace('.1.3.6.1.2.1.17.4.3.1.2', '', $eachEntry);
                $cleanMac = '';
                $rawMac = explode('=', $eachEntry);
                $parts = explode('.', trim($rawMac[0], '.'));
                if (count($parts) == 6) {
                    $cleanMac = sp_PartsToMac($parts);
                    $portData[$cleanMac] = vf($rawMac[1], 3);
                }
            }
        }
    }
    return ($portData);
}

//  Parsing of FDB port table SNMP raw data for Raisecom
//  Due crazy portindex
function sp_SnmpParseFdbRa($portTable) {
    $portData = array();
    $arr_PortTable = explodeRows($portTable);
    if (!empty($arr_PortTable)) {
        foreach ($arr_PortTable as $eachEntry) {
            if (!empty($eachEntry)) {
                $eachEntry = str_replace('.1.3.6.1.2.1.17.7.1.2.2.1.2', '', $eachEntry);
                $cleanMac = '';
                $rawMac = explode('=', $eachEntry);
                $parts = explode('.', trim($rawMac[0], '.'));
                $port = vf($rawMac[1], 3);
                $port = $port - 2082476032;
                unset($parts[0]);
                // Some devices show CPU interface as port 0
                if (count($parts) == 6 and intval($port) != 0) {
                    $cleanMac = sp_PartsToMac($parts);
                    $portData[strtolower($cleanMac)] = $port;
                }
            }
        }
    }
    return ($portData);
}

/**
 * Parsing of FDB port table SNMP raw data from Cisco 3xx
 *
 * @param   $portTable raw SNMP data
 *
 * @return  array
 */
function sp_SnmpParseFdbCisEb($portTable) {
    $portData = array();
    $arr_PortTable = explodeRows($portTable);
    if (!empty($arr_PortTable)) {
        foreach ($arr_PortTable as $eachEntry) {
            if (!empty($eachEntry)) {
                $eachEntry = str_replace('.1.3.6.1.2.1.17.4.3.1.2', '', $eachEntry);
                $cleanMac = '';
                $rawMac = explode('=', $eachEntry);
                $parts = explode('.', trim($rawMac[0], '.'));
                if (count($parts) == 6) {
                    $cleanMac = sp_PartsToMac($parts);
                    $port = ubRouting::filters($rawMac[1], 'int');
                    //A-A-A!!!!111
                    $portReplaceTable = array(
                        1 => 49,
                        2 => 50,
                        27 => 51,
                        28 => 52,
                    );

                    //combo ports offset
                    if (isset($portReplaceTable[$port])) {
                        $port = $portReplaceTable[$port];
                    } else {
                        if ($port < 27) {
                            $port = $port - 2;
                        }

                        if ($port > 28) {
                            $port = $port - 4;
                        }
                    }

                    $portData[$cleanMac] = $port;
                }
            }
        }
    }
    return ($portData);
}

/**
 * Parsing of FDB port table SNMP raw data for some exotic Dlink switches
 * 
 * @param   $portTable raw SNMP data
 * 
 * @return  array
 */
function sp_SnmpParseFdbDl($portTable) {
    $portData = array();
    $arr_PortTable = explodeRows($portTable);
    if (!empty($arr_PortTable)) {
        foreach ($arr_PortTable as $eachEntry) {
            if (!empty($eachEntry)) {
                $eachEntry = str_replace('.1.3.6.1.2.1.17.7.1.2.2.1.2', '', $eachEntry);
                $cleanMac = '';
                $rawMac = explode('=', $eachEntry);
                $parts = explode('.', trim($rawMac[0], '.'));
                $port = vf($rawMac[1], 3);
                unset($parts[0]);
                // Some devices show CPU interface as port 0
                if (count($parts) == 6 and intval($port) != 0) {
                    $cleanMac = sp_PartsToMac($parts);
                    $portData[$cleanMac] = $port;
                }
            }
        }
    }
    return ($portData);
}

/**
 * Parsing of FDB port table SNMP raw data for some exotic Tplink switches
 * 
 * @param   $portTable raw SNMP data
 * 
 * @return  array
 */
function sp_SnmpParseFdbTlp($portTable, $oid) {
    $portData = array();
    $arr_PortTable = explodeRows($portTable);
    if (!empty($arr_PortTable)) {
        foreach ($arr_PortTable as $eachEntry) {
            if (!empty($eachEntry)) {
                $eachEntry = str_replace($oid, '', $eachEntry);
                $cleanMac = '';
                $rawMac = explode('=', $eachEntry);
                $rawMac[0] = substr($rawMac[0], 0, -2); //drop last 01 octet
                $rawMac[0] = '.1' . $rawMac[0]; // add .1 part. fuck this shit
                $parts = explode('.', trim($rawMac[0], '.'));
                unset($parts[0]);
                if (count($parts) == 6) {
                    $cleanMac = sp_PartsToMac($parts);
                    $portData[$cleanMac] = vf($rawMac[1], 3);
                }
            }
        }
    }
    return ($portData);
}

/**
 * Parsing of FDB port table SNMP raw data for some strange foxgate switches
 * 
 * @param   $portTable raw SNMP data
 * 
 * @return  array
 */
function sp_SnmpParseFdbFlp($portTable, $oid) {
    $portData = array();
    $arr_PortTable = explodeRows($portTable);
    if (!empty($arr_PortTable)) {
        foreach ($arr_PortTable as $eachEntry) {
            if (!empty($eachEntry)) {
                $eachEntry = str_replace($oid, '', $eachEntry);
                $cleanMac = '';
                $rawMac = explode('=', $eachEntry);
                $parts = explode('.', trim($rawMac[0], '.'));
                unset($parts[0]);
                if (count($parts) == 6) {
                    $cleanMac = call_user_func_array('sprintf', $parts);
                    $portData[$cleanMac] = vf($rawMac[1], 3);
                }
            }
        }
    }
    return ($portData);
}

/**
 * Parsing of FDB port and VLAN tables from SNMP raw data for cumulative FDB mode
 *
 * @param string $portTable
 * @param string $statusTable
 * @param string $portOID
 * @param string $statusOID
 * @param bool $dot1Q
 *
 * @return array
 */
function sp_SnmpParseFdbCumulative($portTable, $statusTable, $portOID, $statusOID, $dot1Q = false) {
    $portData = array();
    $statusData = array();
    $arr_PortTable = explodeRows($portTable);
    $arr_StatusTale = explodeRows($statusTable);

    if (!empty($arr_StatusTale)) {
        foreach ($arr_StatusTale as $eachEntry) {
            $tmpStatusArr = trimSNMPOutput($eachEntry, $statusOID);

            // $tmpStatusArr[0] - DEC raw MAC or VLAN.DEC raw MAC in dot1Q mode
            // $tmpStatusArr[1] - MAC status: 1 - other, 2 - invalid, 3 - learned, 4 - self, 5 - mgmt
            if (!empty($tmpStatusArr[0])) {
                $statusData[$tmpStatusArr[0]] = $tmpStatusArr[1];
            }
        }
    }

    if (!empty($arr_PortTable)) {
        foreach ($arr_PortTable as $eachEntry) {
            if (!empty($eachEntry)) {
                $tmpArr = trimSNMPOutput($eachEntry, $portOID);

                // $tmpArr[0] - DEC raw MAC or VLAN.DEC raw MAC in dot1Q mode
                // $tmpArr[1] - src port idx
                if (!empty($tmpArr[0])) {
                    // trying to exclude and skip MAC addresses with "self" status, as they are native for the device
                    if (!empty($statusData[$tmpArr[0]]) and $statusData[$tmpArr[0]] == 4) {
                        continue;
                    }

                    if ($dot1Q) {
                        // extracting VLAN portion from OID
                        $vlanNum = substr($tmpArr[0], 0, stripos($tmpArr[0], '.'));
                        $rawMAC = substr($tmpArr[0], strlen($vlanNum) + 1);
                    } else {
                        $rawMAC = $tmpArr[0];
                    }

                    $cleanMAC = convertMACDec2Hex($rawMAC);

                    if (!empty($cleanMAC)) {
                        if ($dot1Q) {
                            $portData[strtolower($cleanMAC)][] = array('port' => vf($tmpArr[1], 3), 'vlan' => vf($vlanNum, 3));
                        } else {
                            $portData[strtolower($cleanMAC)] = vf($tmpArr[1], 3);
                        }
                    }
                }
            }
        }
    }

    return ($portData);
}