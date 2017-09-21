<?php

/*
 * SNMP switch polling API
 */

/**
 * Raw SNMP data parser
 * @return string
 */
function sp_parse_raw($data) {
    if (!empty($data)) {
        $data = explode('=', $data);
        $result = $data[1] . '<br>';
        return ($result);
    } else {
        return (__('Empty reply received'));
    }
}

/**
 * Zyxel Port state data parser
 * 
 * @return string
 */
function sp_parse_zyportstates($data) {
    if (!empty($data)) {
        $data = explode('=', $data);
        $data[0] = trim($data[0]);
        $portnum = substr($data[0], -2);
        $portnum = str_replace('.', '', $portnum);

        if (ispos($data[1], '1')) {
            $cells = wf_TableCell($portnum, '24', '', 'style="height:20px;"');
            $cells.= wf_TableCell(web_bool_led(true));
            $rows = wf_TableRow($cells, 'row3');
            $result = wf_TableBody($rows, '100%', 0, '');
        } else {
            $cells = wf_TableCell($portnum, '24', '', 'style="height:20px;"');
            $cells.= wf_TableCell(web_bool_led(false));
            $rows = wf_TableRow($cells, 'row3');
            $result = wf_TableBody($rows, '100%', 0, '');
        }
        return ($result);
    } else {
        return (__('Empty reply received'));
    }
}

/**
 * Some Foxgate 60xx port state data parser
 * 
 * @return string
 */
function sp_parse_fxportstates($data) {
    $result = '';
    if (!empty($data)) {
        $data = explode('=', $data);
        $data[0] = trim($data[0]);
        $portnum = substr($data[0], -2);
        $portnum = str_replace('.', '', $portnum);
        $portnum = $portnum - 1;

        if ($portnum != 0) {
            if (ispos($data[1], '1')) {
                $cells = wf_TableCell($portnum, '24', '', 'style="height:20px;"');
                $cells.= wf_TableCell(web_bool_led(true));
                $rows = wf_TableRow($cells, 'row3');
                $result = wf_TableBody($rows, '100%', 0, '');
            } else {
                $cells = wf_TableCell($portnum, '24', '', 'style="height:20px;"');
                $cells.= wf_TableCell(web_bool_led(false));
                $rows = wf_TableRow($cells, 'row3');
                $result = wf_TableBody($rows, '100%', 0, '');
            }
        }
        return ($result);
    } else {
        return (__('Empty reply received'));
    }
}

/**
 * D-Link Cable diagnostic data parser
 * 
 * @return string
 */
function sp_parse_cable_tester($ip, $community, $currentTemplate) {
    if (!empty($currentTemplate)) {

        $snmp = new SNMPHelper();
        $result = '';
        @$sectionOids = explode(',', $currentTemplate['OIDS']);

        $sectionResult = array();
        $rawData_arr = array();

        //now parse each oid
        if (!empty($sectionOids)) {
            foreach ($sectionOids as $eachOid) {
                $eachOid = trim($eachOid);
                $rawData = $snmp->walk($ip, $community, $eachOid, true);
                $rawData_arr[] = str_replace('"', '`', $rawData);
                // Create new array [$portnum][$each]=>$data
                foreach ($rawData_arr as $each => $data_arr) {
                    $data = explode(PHP_EOL, $data_arr);
                    foreach ($data as $data_info) {
                        $data = explode('=', $data_info);
                        if (isset($data[0]) and isset($data[1])) {
                            $data[0] = trim($data[0]);
                            $portnum = substr($data[0], -2);
                            $portnum = str_replace('.', '', $portnum);
                            $interger = trim($data[1]);
                            $interger = str_replace('INTEGER: ', '', $interger);

                            $sectionResult[$portnum][$each] = $interger;
                        }
                    }
                }
            }
        }
        // Parsing result after snmwalk and create data array
        foreach ($sectionResult as $port => $data) {
            if (!empty($data)) {
                $cells = wf_TableCell($port, '24', '', 'style="height:20px;"');
                $cells_data = '';
                foreach ($data as $test_id => $info) {
                    if ($test_id == 0 and $info != 2) {
                        if ($data[1] == 0 OR $data[2] == 0 OR $data[3] == 0 OR $data[4] == 0) {
                            $cells_data .= __("OK");
                            // Return Length for Pair2, becase some modele have accrose rawdata
                            $cells_data .= ($data[2] == 0 AND $data[6] > 0 ) ? "," . __("Cable Length:") . $data[6] : '';
                        } elseif ($data[1] == 1 OR $data[2] == 1 OR $data[3] == 1 OR $data[4] == 1) {
                            $cells_data .= ($data[1] == 1) ? __("Pair1 Open:") . $data[5] . " " : '';
                            $cells_data .= ($data[2] == 1) ? __("Pair2 Open:") . $data[6] . " " : '';
                            $cells_data .= ($data[3] == 1) ? __("Pair3 Open:") . $data[7] . " " : '';
                            $cells_data .= ($data[4] == 1) ? __("Pair4 Open:") . $data[8] . " " : '';
                        } elseif ($data[1] == 2 OR $data[2] == 2 OR $data[3] == 2 OR $data[4] == 2) {
                            $cells_data .= ($data[1] == 2) ? __("Pair1 Short:") . $data[5] . " " : '';
                            $cells_data .= ($data[2] == 2) ? __("Pair2 Short:") . $data[6] . " " : '';
                            $cells_data .= ($data[3] == 2) ? __("Pair3 Short:") . $data[7] . " " : '';
                            $cells_data .= ($data[4] == 2) ? __("Pair4 Short:") . $data[8] . " " : '';
                        } elseif ($data[1] == 3 OR $data[2] == 3 OR $data[3] == 3 OR $data[4] == 3) {
                            $cells_data .= ($data[1] == 3) ? __("Pair1 Open-Short:") . $data[5] . " " : '';
                            $cells_data .= ($data[2] == 3) ? __("Pair2 Open-Short:") . $data[6] . " " : '';
                            $cells_data .= ($data[3] == 3) ? __("Pair3 Open-Short:") . $data[7] . " " : '';
                            $cells_data .= ($data[4] == 3) ? __("Pair4 Open-Short:") . $data[8] . " " : '';
                        } elseif ($data[1] == 4 OR $data[2] == 4 OR $data[3] == 4 OR $data[4] == 4) {
                            $cells_data .= ($data[1] == 4) ? __("Pair1 crosstalk") . " " : '';
                            $cells_data .= ($data[2] == 4) ? __("Pair2 crosstalk") . " " : '';
                            $cells_data .= ($data[3] == 4) ? __("Pair3 crosstalk") . " " : '';
                            $cells_data .= ($data[4] == 4) ? __("Pair4 crosstalk") . " " : '';
                        } elseif ($data[1] == 5 OR $data[2] == 5 OR $data[5] == 5 OR $data[4] == 5) {
                            $cells_data .= ($data[1] == 5) ? __("Pair1 unknown") . " " : '';
                            $cells_data .= ($data[2] == 5) ? __("Pair2 unknown") . " " : '';
                            $cells_data .= ($data[3] == 5) ? __("Pair3 unknown") . " " : '';
                            $cells_data .= ($data[4] == 5) ? __("Pair4 unknown") . " " : '';
                        } elseif ($data[1] == 6 OR $data[2] == 6 OR $data[5] == 6 OR $data[4] == 6) {
                            $cells_data .= ($data[1] == 6) ? __("Pair1 count") . " " : '';
                            $cells_data .= ($data[2] == 6) ? __("Pair2 count") . " " : '';
                            $cells_data .= ($data[3] == 6) ? __("Pair3 count") . " " : '';
                            $cells_data .= ($data[4] == 6) ? __("Pair4 count") . " " : '';
                        } elseif ($data[1] == 7 OR $data[2] == 7 OR $data[5] == 7 OR $data[4] == 7) {
                            $cells_data .= __("No Cable");
                        } elseif ($data[1] == 8 OR $data[2] == 8 OR $data[5] == 8 OR $data[4] == 8) {
                            $cells_data .= __("The PHY can't support Cable Diagnostic");
                        }
                    } elseif ($test_id == 0 and $info == 2) {
                        $cells_data .= __("Cable Diagnostic processing");
                    }
                }
                $cells.= wf_TableCell($cells_data);
                $rows = wf_TableRow($cells, 'row3');
                $result .= wf_TableBody($rows, '100%', 0, '');
            }
        }
        return ($result);
    } else {
        return (__('Empty reply received'));
    }
}

/**
 * Zyxel Port byte counters data parser
 * 
 * @return string
 */
function sp_parse_zyportbytes($data) {
    if (!empty($data)) {
        $data = explode('=', $data);
        $data[0] = trim($data[0]);
        $portnum = substr($data[0], -2);
        $portnum = str_replace('.', '', $portnum);

        $bytes = str_replace(array('Counter32:', 'Counter64:'), '', $data[1]);
        $bytes = trim($bytes);

        if (ispos($data[1], 'up')) {
            $cells = wf_TableCell($portnum, '24', '', 'style="height:20px;"');
            $cells.= wf_TableCell($bytes);
            $rows = wf_TableRow($cells, 'row3');
            $result = wf_TableBody($rows, '100%', 0, '');
        } else {
            $cells = wf_TableCell($portnum, '24', '', 'style="height:20px;"');
            $cells.= wf_TableCell($bytes);
            $rows = wf_TableRow($cells, 'row3');
            $result = wf_TableBody($rows, '100%', 0, '');
        }
        return ($result);
    } else {
        return (__('Empty reply received'));
    }
}

/**
 * Foxgate 60xx port byte counters data parser
 * 
 * @return string
 */
function sp_parse_fxportbytes($data) {
    $result = '';
    if (!empty($data)) {
        $data = explode('=', $data);
        $data[0] = trim($data[0]);
        $portnum = substr($data[0], -2);
        $portnum = str_replace('.', '', $portnum);
        $portnum = $portnum - 1; //shitty offset

        $bytes = str_replace(array('Counter32:', 'Counter64:'), '', $data[1]);
        $bytes = trim($bytes);

        if ($portnum != 0) {
            if (ispos($data[1], 'up')) {
                $cells = wf_TableCell($portnum, '24', '', 'style="height:20px;"');
                $cells.= wf_TableCell($bytes);
                $rows = wf_TableRow($cells, 'row3');
                $result = wf_TableBody($rows, '100%', 0, '');
            } else {
                $cells = wf_TableCell($portnum, '24', '', 'style="height:20px;"');
                $cells.= wf_TableCell($bytes);
                $rows = wf_TableRow($cells, 'row3');
                $result = wf_TableBody($rows, '100%', 0, '');
            }
        }
        return ($result);
    } else {
        return (__('Empty reply received'));
    }
}

/**
 * Zyxel Port description data parser
 * 
 * @return string
 */
function sp_parse_zyportdesc($data) {
    if (!empty($data)) {
        $data = explode('=', $data);
        $data[0] = trim($data[0]);
        $portnum = substr($data[0], -2);
        $portnum = str_replace('.', '', $portnum);
        if (ispos($data[1], 'NULL')) {
            $desc = __('No');
        } else {
            $desc = str_replace('STRING:', '', $data[1]);
            $desc = trim($desc);
        }
        if (ispos($data[1], 'up')) {
            $cells = wf_TableCell($portnum, '24', '', 'style="height:20px;"');
            $cells.= wf_TableCell($desc);
            $rows = wf_TableRow($cells, 'row3');
            $result = wf_TableBody($rows, '100%', 0, '');
        } else {
            $cells = wf_TableCell($portnum, '24', '', 'style="height:20px;"');
            $cells.= wf_TableCell($desc);
            $rows = wf_TableRow($cells, 'row3');
            $result = wf_TableBody($rows, '100%', 0, '');
        }
        return ($result);
    } else {
        return (__('Empty reply received'));
    }
}

/**
 * Cisco memory usage data parser
 * 
 * @return string
 */
function sp_parse_ciscomemory($data) {
    if (!empty($data)) {
        $data = explode('=', $data);
        $result = vf($data[1], 3);
        $result = trim($result);
        $result = stg_convert_size($result);
        return ($result);
    } else {
        return (__('Empty reply received'));
    }
}

/**
 * Cisco memory usage data parser
 * 
 * @return string
 */
function sp_parse_ciscocpu($data) {
    if (!empty($data)) {
        $data = explode('=', $data);
        $result = vf($data[1], 3);
        $result = trim($result);
        $result = $result . '%';
        return ($result);
    } else {
        return (__('Empty reply received'));
    }
}

/**
 * Eltex AC Power States
 * 
 * @param string $data
 * 
 * @return string
 */
function sp_parse_eltex_acpower($data) {
    if (!empty($data)) {
        $data = explode(':', $data);
        $out = trim($data[1]);
        $state = ":Normal:Warning:Critical:Shutdown:notPresent:notFunctioning:Restore";
        $power = explode(':', $state);
        $result = $power[$out];
        return ($result);
    } else {
        return (__('Empty reply received'));
    }
}

/**
 * Eltex DC Power States
 * 
 * @param string $data
 * 
 * @return string
 */
function sp_parse_eltex_dcpower($data) {
    if (!empty($data)) {
        $data = explode(':', $data);
        $out = trim($data[1]);
        $state = ":Battery recharge:Battery discharge:Battery low:Shutdown:notPresent:notFunctioning:Restore";
        $power = explode(':', $state);
        $result = $power[$out];
        return ($result);
    } else {
        return (__('Empty reply received'));
    }
}

/**
 * Eltex Battery charge state
 * 
 * @param string $data
 * 
 * @return string
 */
function sp_parse_eltex_battery($data) {
    if (!empty($data)) {
        $data = explode(':', $data);
        $result = vf($data[1]);
        $result = trim($result);
        $result = $result . '%';
        if ($data[1] == 255) {
            $result = __('No');
        }
        return ($result);
    } else {
        return (__('Empty reply received'));
    }
}

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
 * Returns list of all monitored devices
 * 
 * @return array
 */
function sp_SnmpGetAllDevices() {
    $query = "SELECT * from `switches` WHERE `snmp`!='' AND `desc` LIKE '%SWPOLL%'";
    $result = simple_queryall($query);
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
            $result[$each] = rcms_parse_ini_file($privatePath . $each, true);
        }
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
                //this part of code designed by Han and here is some magic, which we do not understand :)
                $parts = array('format' => '%02X:%02X:%02X:%02X:%02X:%02X') + explode('.', trim($rawMac[0], '.'));
                if (count($parts) == 7) {
                    $cleanMac = call_user_func_array('sprintf', $parts);
                    $portData[strtolower($cleanMac)] = vf($rawMac[1], 3);
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
                $parts = array('format' => '%02X:%02X:%02X:%02X:%02X:%02X') + explode('.', trim($rawMac[0], '.'));
                unset($parts[0]);
                if (count($parts) == 7) {
                    $cleanMac = call_user_func_array('sprintf', $parts);
                    $portData[strtolower($cleanMac)] = vf($rawMac[1], 3);
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
                $parts = array('format' => '%02X:%02X:%02X:%02X:%02X:%02X') + explode('.', trim($rawMac[0], '.'));
                unset($parts[0]);
                if (count($parts) == 7) {
                    $cleanMac = call_user_func_array('sprintf', $parts);
                    $portData[strtolower($cleanMac)] = vf($rawMac[1], 3);
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
                $parts = array('format' => '%02X:%02X:%02X:%02X:%02X:%02X') + explode('.', trim($rawMac[0], '.'));
                unset($parts[0]);
                if (count($parts) == 7) {
                    $cleanMac = call_user_func_array('sprintf', $parts);
                    $portData[strtolower($cleanMac)] = vf($rawMac[1], 3);
                }
            }
        }
    }
    return ($portData);
}

/**
 * Show data for some device
 * 
 * @param   $ip device ip
 * @param   $community snmp community
 * @param   $alltemplates all of snmp templates
 * @param   $quiet  no output
 * 
 * @return  void
 */
function sp_SnmpPollDevice($ip, $community, $alltemplates, $deviceTemplate, $allusermacs, $alladdress, $communitywrite = '', $quiet = false) {
    global $ubillingConfig;
    if (isset($alltemplates[$deviceTemplate])) {
        $currentTemplate = $alltemplates[$deviceTemplate];
        if (!empty($currentTemplate)) {
            $deviceDescription = $currentTemplate['define']['DEVICE'];
            $deviceFdb = $currentTemplate['define']['FDB'];
            $sectionResult = '';
            $sectionName = '';
            $finalResult = '';
            $tempArray = array();
            $alterCfg = $ubillingConfig->getAlter();
            $snmp = new SNMPHelper();

            //selecting FDB processing mode
            if (isset($currentTemplate['define']['FDB_MODE'])) {
                $deviceFdbMode = $currentTemplate['define']['FDB_MODE'];
            } else {
                $deviceFdbMode = 'default';
            }

            //selecting FDB ignored port for skipping MAC-s on it
            if (isset($currentTemplate['define']['FDB_IGNORE_PORTS'])) {
                $deviceFdbIgnore = $currentTemplate['define']['FDB_IGNORE_PORTS'];
                $deviceFdbIgnore = explode(',', $deviceFdbIgnore);
                $deviceFdbIgnore = array_flip($deviceFdbIgnore);
            } else {
                $deviceFdbIgnore = array();
            }

            //parse each section of template
            foreach ($alltemplates[$deviceTemplate] as $section => $eachpoll) {
                if ($section != 'define') {
                    if (!$quiet) {
                        $finalResult.= wf_tag('div', false, 'dashboard', '');
                    }

                    @$sectionName = $eachpoll['NAME'];
                    @$sectionOids = explode(',', $eachpoll['OIDS']);

                    if (isset($eachpoll['SETOIDS'])) {
                        $sectionSetOids = explode(',', $eachpoll['SETOIDS']);
                    } else {
                        $sectionSetOids = array();
                    }

                    @$sectionParser = $eachpoll['PARSER'];
                    $sectionResult = '';

                    //yeah, lets set some oids to this shit
                    if (!empty($sectionSetOids)) {
                        foreach ($sectionSetOids as $eachSetOid) {
                            $eachSetOidRaw = trim($eachSetOid);
                            $eachSetOidRaw = explode('|', $eachSetOidRaw);
                            //all three parts of set squense present
                            if ((isset($eachSetOidRaw[0])) AND ( isset($eachSetOidRaw[1])) AND ( isset($eachSetOidRaw[2]))) {
                                $setDataTmp[0] = array('oid' => $eachSetOidRaw[0], 'type' => $eachSetOidRaw[1], 'value' => $eachSetOidRaw[2]);
                                if (!empty($communitywrite)) {
                                    $runSet = $snmp->set($ip, $communitywrite, $setDataTmp);
                                }
                            }
                        }
                    }
                    //now parse each oid
                    if (!empty($sectionOids)) {
                        if ($section == 'cablediag') {
                            if (!empty($sectionParser)) {
                                $sectionResult .= $sectionParser($ip, $community, $currentTemplate['cablediag']);
                            } else {
                                $sectionResult = '';
                            }
                        } else {
                            foreach ($sectionOids as $eachOid) {
                                $eachOid = trim($eachOid);
                                $rawData = $snmp->walk($ip, $community, $eachOid, true);
                                $rawData = str_replace('"', '`', $rawData);
                                if (!empty($sectionParser)) {
                                    $parseCode = '$sectionResult.=' . $sectionParser . '("' . $rawData . '");';
                                    eval($parseCode);
                                } else {
                                    $sectionResult = '';
                                }
                            }
                        }
                    }

                    if (!$quiet) {
                        if (!empty($sectionResult)) {
                            $finalResult.=wf_tag('div', false, 'dashtask', '') . wf_tag('strong') . __($sectionName) . wf_tag('strong', true) . '<br>';
                            $finalResult.=$sectionResult . wf_tag('div', true);
                        }
                    }
                }
            }
            $finalResult.=wf_tag('div', true);
            $finalResult.=wf_tag('div', false, '', 'style="clear:both;"');
            $finalResult.=wf_tag('div', true);
            if (!$quiet) {
                show_window('', $finalResult);
            }
            //
            //parsing data from FDB table
            //
                if ($deviceFdb == 'true') {
                $portData = array();
                $snmp->setBackground(false); // need to process data with system + background

                if ($deviceFdbMode == 'default') {
                    //default zyxel & cisco port table

                    $portTable = $snmp->walk($ip, $community, '.1.3.6.1.2.1.17.4.3.1.2', true);
                } else {
                    if ($deviceFdbMode == 'dlp') {
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

                    //foxgate lazy parsing
                    if ($deviceFdbMode == 'flp') {
                        $flpOid = '.1.3.6.1.2.1.17.7.1.2.3.1.2';
                        $portTable = $snmp->walk($ip, $community, $flpOid, true);
                    }
                }
                if (!empty($portTable)) {
                    if ($deviceFdbMode == 'default') {
                        //default FDB parser
                        $portData = sp_SnmpParseFDB($portTable);
                    } else {
                        if ($deviceFdbMode == 'dlp') {
                            //exotic dlink parser
                            $portData = sp_SnmpParseFdbDl($portTable);
                        }

                        if (($deviceFdbMode == 'tlp5428ev2') OR ( $deviceFdbMode == 'tlp2428')) {
                            //more exotic tplink parser
                            $portData = sp_SnmpParseFdbTlp($portTable, $tlpOid);
                        }

                        //foxgate - its you again? Oo
                        if ($deviceFdbMode == 'flp') {
                            $portData = sp_SnmpParseFdbFlp($portTable, $flpOid);
                        }
                    }

                    //skipping some port data if FDB_IGNORE_PORTS option is set
                    if (!empty($deviceFdbIgnore)) {
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
                    file_put_contents('exports/' . $ip . '_fdb', $fdbCache);
                }


                //show port data User friendly :)
                if (!empty($portData)) {
                    //extracting all needed data for switchport control
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
                    }
                    $allusermacs = array_flip($allusermacs);

                    $cells = wf_TableCell(__('User'), '30%');
                    $cells.= wf_TableCell(__('MAC'));
                    $cells.= wf_TableCell(__('Ports'));
                    $rows = wf_TableRow($cells, 'row1');
                    foreach ($portData as $eachMac => $eachPort) {
                        //user detection
                        if (isset($allusermacs[$eachMac])) {
                            $userLogin = $allusermacs[$eachMac];
                            @$useraddress = $alladdress[$userLogin];
                            $userlink = wf_Link('?module=userprofile&username=' . $userLogin, web_profile_icon() . ' ' . $useraddress, false);
                            //switch port assing form
                            if ($alterCfg['SWITCHPORT_IN_PROFILE']) {
                                $assignForm = wf_modal(web_edit_icon(__('Switch port assign')), __('Switch port assign'), web_SnmpSwitchControlForm($userLogin, $allswitchesArray, $allportassigndata, @$_GET['switchid'], $eachPort), '', '500', '250');

                                if (isset($allportassigndata[$userLogin])) {
                                    $assignForm.=wf_img('skins/arrow_right_green.png') . @$allportassigndata[$userLogin]['port'];
                                }
                            } else {
                                $assignForm = '';
                            }
                        } else {
                            $userlink = '';
                            $assignForm = '';
                        }
                        $cells = wf_TableCell($userlink . $assignForm, '', '', 'sorttable_customkey="' . $eachPort . '"');
                        $cells.= wf_TableCell($eachMac);
                        $cells.= wf_TableCell($eachPort);
                        $rows.= wf_TableRow($cells, 'row3');
                    }
                    if (!$quiet) {
                        show_window(__('FDB'), wf_TableBody($rows, '100%', '0', 'sortable'));
                    }
                }
            }
        }
    }
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
 * function that display JSON data for display FDB cache
 * 
 * @param $fdbData_raw - array of existing cache _fdb files
 * 
 * @return void
 */
function sn_SnmpParseFdbCacheJson($fdbData_raw, $macFilter) {
    $allusermacs = zb_UserGetAllMACs();
    $allusermacs = array_flip($allusermacs);
    $alladdress = zb_AddressGetFulladdresslist();
    $allswitches = zb_SwitchesGetAll();
    $rawFilters = zb_StorageGet('FDBCACHEMACFILTERS');
    $filteredCounter = 0;
    $switchdata = array();
    $allfilters = array();
    $json = new wf_JqDtHelper();

    //switch data preprocessing
    if (!empty($allswitches)) {
        foreach ($allswitches as $io => $eachswitch) {
            $switchdata[$eachswitch['ip']] = $eachswitch['location'];
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
            if (file_exists('exports/' . $each_raw)) {
                $eachfdb_raw = file_get_contents('exports/' . $each_raw);
                $eachfdb = unserialize($eachfdb_raw);
                if (!empty($eachfdb_raw)) {
                    foreach ($eachfdb as $mac => $port) {
                        //detecting user login by his mac
                        if (isset($allusermacs[$mac])) {
                            $userlogin = $allusermacs[$mac];
                        } else {
                            $userlogin = false;
                        }

                        if ($userlogin) {
                            $userlink = wf_Link('?module=userprofile&username=' . $userlogin, web_profile_icon() . ' ' . @$alladdress[$userlogin], $allfilters, false, '');
                        } else {
                            $userlink = '';
                        }

                        if (sn_FDBFilterCheckMac($mac, $allfilters)) {
                            $data[] = $switchIp;
                            $data[] = $port;
                            $data[] = @$switchdata[$switchIp];
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
    return($result);
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
                $result.=$each;
            }
        } else {
            foreach ($data as $io => $each) {
                $modalContent.=$each . wf_tag('br');
            }
            $result.=$each . ' ' . wf_modal(wf_img_sized('skins/menuicons/switches.png', __('Switches'), '12', '12'), __('Switches'), $modalContent, '', '600', '400');
        }
    }
    return ($result);
}

?>