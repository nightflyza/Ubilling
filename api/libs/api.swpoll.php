<?php

/*
 * SNMP switch polling API
 */

/**
 * Raw SNMP data parser
 * 
 * @return string
 */
function sp_parse_raw($data) {
    if (!empty($data)) {
        $data = explode('=', $data);
        $result = $data[1] . wf_tag('br');
        return ($result);
    } else {
        return (__('Empty reply received'));
    }
}

/**
 * Raw SNMP data parser with value types cleanup
 * 
 * @param string $data
 * 
 * @return string
 */
function sp_parse_raw_sanitized($data) {
    $result = '';
    if (!empty($data)) {
        $result = zb_SanitizeSNMPValue($data) . wf_tag('br');
    } else {
        $result = __('Empty reply received');
    }
    return($result);
}

/**
 * Returns human readable uptime converted from seconds value
 * 
 * @param string $data
 * 
 * @return string
 */
function sp_parse_time_seconds($data) {
    $result = '';
    if (!empty($data)) {
        $rawTime = zb_SanitizeSNMPValue($data);
        if (!empty($rawTime)) {
            $result = zb_formatTime($rawTime) . wf_tag('br');
        }
    } else {
        $result = __('Empty reply received');
    }
    return($result);
}

/**
 * Returns LED of electrical power state.
 * 
 * @param string $data
 * 
 * @return string
 */
function sp_parse_power($data) {
    $result = '';
    if (!empty($data)) {
        $rawValue = zb_SanitizeSNMPValue($data);
        if ($rawValue) {
            $result = wf_img('skins/lighton.png') . wf_tag('br');
        } else {
            $result = wf_img('skins/lightoff.png') . wf_tag('br');
        }
    } else {
        $result = __('Empty reply received');
    }
    return($result);
}

/**
 * Returns temperature value from equicom ping3 as text
 * 
 * @param string $data
 * 
 * @return string
 */
function sp_parse_eping_temp($data) {
    $result = '';
    if (!empty($data)) {
        $rawValue = zb_SanitizeSNMPValue($data);
        if (!empty($rawValue)) {
            $result = ($rawValue / 10) . ' °C' . wf_tag('br');
        }
    } else {
        $result = __('Empty reply received');
    }
    return($result);
}

/**
 * Returns temperature value from equicom ping3 as gauge
 * 
 * @param string $data
 * 
 * @return string
 */
function sp_parse_eping_temp_gauge($data) {
    $result = '';
    if (!empty($data)) {
        $rawValue = zb_SanitizeSNMPValue($data);
        if (!empty($rawValue)) {
            $degrees = $rawValue / 10;
            $options = 'max: 40,
                        min: 10,
                        width: 280, height: 280,
                        greenFrom: 15, greenTo: 20,
                        yellowFrom:20, yellowTo: 25,
                        redFrom: 25, redTo: 40,
                        minorTicks: 5';

            $result = wf_renderTemperature($degrees, '', $options);
        }
    } else {
        $result = __('Empty reply received');
    }
    return($result);
}

/**
 * Raw SNMP data parser with trimming
 *
 * @return string
 */
function sp_parse_raw_trim_tab($data) {
    $result = __('Empty reply received');

    if (!empty($data)) {
        $data = trimSNMPOutput($data, '');
        $cells = wf_TableCell($data[1]);
        $rows = wf_TableRow($cells, 'row3');
        $result = wf_TableBody($rows, '100%', 0, '');
    }

    return ($result);
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
            $cells .= wf_TableCell(web_bool_led(true));
            $rows = wf_TableRow($cells, 'row3');
            $result = wf_TableBody($rows, '100%', 0, '');
        } else {
            $cells = wf_TableCell($portnum, '24', '', 'style="height:20px;"');
            $cells .= wf_TableCell(web_bool_led(false));
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
                $cells .= wf_TableCell(web_bool_led(true));
                $rows = wf_TableRow($cells, 'row3');
                $result = wf_TableBody($rows, '100%', 0, '');
            } else {
                $cells = wf_TableCell($portnum, '24', '', 'style="height:20px;"');
                $cells .= wf_TableCell(web_bool_led(false));
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
                        if (@$data[1] == 0 OR @ $data[2] == 0 OR @ $data[3] == 0 OR @ $data[4] == 0) {
                            $cells_data .= __("OK");
                            // Return Length for Pair2, becase some modele have accrose rawdata
                            @$cells_data .= ($data[2] == 0 AND $data[6] > 0 ) ? "," . __("Cable Length:") . $data[6] : '';
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
                $cells .= wf_TableCell($cells_data);
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
            $cells .= wf_TableCell($bytes);
            $rows = wf_TableRow($cells, 'row3');
            $result = wf_TableBody($rows, '100%', 0, '');
        } else {
            $cells = wf_TableCell($portnum, '24', '', 'style="height:20px;"');
            $cells .= wf_TableCell($bytes);
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
                $cells .= wf_TableCell($bytes);
                $rows = wf_TableRow($cells, 'row3');
                $result = wf_TableBody($rows, '100%', 0, '');
            } else {
                $cells = wf_TableCell($portnum, '24', '', 'style="height:20px;"');
                $cells .= wf_TableCell($bytes);
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
            $cells .= wf_TableCell($desc);
            $rows = wf_TableRow($cells, 'row3');
            $result = wf_TableBody($rows, '100%', 0, '');
        } else {
            $cells = wf_TableCell($portnum, '24', '', 'style="height:20px;"');
            $cells .= wf_TableCell($desc);
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
 * Standard parser for values with units and possible division necessity
 *
 * @param string $data
 * @param string $divBy
 * @param string $units
 *
 * @return mixed|string
 */
function sp_parse_division_units($data, $divBy = '', $units = '') {
    $result = __('Empty reply received');

    if (!empty($data)
            and ! ispos($data, 'No Such Object available')
            and ! ispos($data, 'No more variables left')
    ) {

        $data = trimSNMPOutput($data, '');

        $portnum = substr($data[0], -2);
        $portnum = str_replace('.', '', $portnum);

        $value = $data[1];

        if (!empty($divBy) and is_numeric($divBy)) {
            $value = $value / $divBy;
        }

        if (!empty($units)) {
            $value = $value . ' ' . $units;
        }

        $cells = wf_TableCell($portnum, '24', '', 'style="height:20px;"');
        $cells .= wf_TableCell($value);
        $rows = wf_TableRow($cells, 'row3');
        $result = wf_TableBody($rows, '100%', 0, '');
    }

    return ($result);
}

/**
 * Standard parser for values with units and possible division necessity
 * for data without ports info
 *
 * @param string $data
 * @param string $divBy
 * @param string $units
 *
 * @return mixed|string
 */
function sp_parse_division_units_noport($data, $divBy = '', $units = '') {
    $result = __('Empty reply received');

    if (!empty($data)
            and ! ispos($data, 'No Such Object available')
            and ! ispos($data, 'No more variables left')
    ) {

        $data = trimSNMPOutput($data, '');
        $value = $data[1];

        if (!empty($divBy) and is_numeric($divBy)) {
            $value = $value / $divBy;
        }

        if (!empty($units)) {
            $value = $value . ' ' . $units;
        }

        $cells = wf_TableCell($value);
        $rows = wf_TableRow($cells, 'row3');
        $result = wf_TableBody($rows, '100%', 0, '');
    }

    return ($result);
}

/**
 * Mikrotik POE statuses parser
 *
 * @param $data
 *
 * @return mixed|string
 */
function sp_parse_mikrotik_poe($data) {
    $result = __('Empty reply received');

    if (!empty($data)
            and ! ispos($data, 'No Such Object available')
            and ! ispos($data, 'No more variables left')
    ) {
        $data = trimSNMPOutput($data, '');

        $portnum = substr($data[0], -2);
        $portnum = str_replace('.', '', $portnum);

        $value = $data[1];

        switch ($value) {
            case 1:
                $value = 'Disabled';
                break;

            case 2:
                $value = 'Waiting for load';
                break;

            case 3:
                $value = 'Powered ON';
                break;

            case 4:
                $value = 'Overload';
                break;

            default:
                $value = 'Short circuit';
        }

        $cells = wf_TableCell($portnum, '24', '', 'style="height:20px;"');
        $cells .= wf_TableCell($value);
        $rows = wf_TableRow($cells, 'row3');
        $result = wf_TableBody($rows, '100%', 0, '');
    }

    return ($result);
}

/**
 * Returns switch ports index array
 *
 * @param $portIdxTab
 * @param $oid
 *
 * @return array
 */
function sp_parse_sw_port_idx($portIdxTab, $oid) {
    $result = array();

    if (!empty($portIdxTab)) {
        $portIdxTab = explodeRows($portIdxTab);

        foreach ($portIdxTab as $eachRow) {
            $tmpArr = trimSNMPOutput($eachRow, $oid);

            if (!empty($tmpArr)) {
                $result[] = (empty($tmpArr[1])) ? 0 : $tmpArr[1];
            }
        }
    }

    return ($result);
}

/**
 * Returns switch ports descriptions as pre-formatted HTML table cell
 *
 * @param $data
 *
 * @return mixed|string
 */
function sp_parse_sw_port_descr($data) {
    $result = '';

    if (!empty($data)) {
        foreach ($data as $eachPort => $eachDescr) {
            $portnum = $eachPort;
            $descr = $eachDescr;

            $cells = wf_TableCell($portnum, '24', '', 'style="height:20px;"');
            $cells .= wf_TableCell($descr);
            $rows = wf_TableRow($cells, 'row3');
            $result .= wf_TableBody($rows, '100%', 0, '');
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

                $parts = array('format' => '%02X:%02X:%02X:%02X:%02X:%02X') + explode('.', trim($rawMac[0], '.'));
                if (count($parts) == 7) {
                    $cleanMac = call_user_func_array('sprintf', $parts);
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

                    $portData[strtolower($cleanMac)] = $port;
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
                $port = vf($rawMac[1], 3);
                unset($parts[0]);
                // Some devices show CPU interface as port 0
                if (count($parts) == 7 and intval($port) != 0) {
                    $cleanMac = call_user_func_array('sprintf', $parts);
                    $portData[strtolower($cleanMac)] = $port;
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

/**
 * Poll/Show data for some device
 * 
 * @global object $ubillingConfig
 * @param string $ip
 * @param string $community
 * @param array $alltemplates
 * @param string $deviceTemplate
 * @param array $allusermacs
 * @param array $alladdress
 * @param string $communitywrite
 * @param bool $quiet
 * @param array $allswitchmacs
 * 
 * @return void
 */
function sp_SnmpPollDevice($ip, $community, $alltemplates, $deviceTemplate, $allusermacs, $alladdress, $communitywrite = '', $quiet = false, $allswitchmacs = array()) {
    global $ubillingConfig;
    if (isset($alltemplates[$deviceTemplate])) {
        $currentTemplate = $alltemplates[$deviceTemplate];

        if (!empty($currentTemplate)) {
            $deviceDescription = $currentTemplate['define']['DEVICE'];
            $deviceFdb = $currentTemplate['define']['FDB'];
            $deviceMAC = (isset($currentTemplate['define']['MAC'])) ? $currentTemplate['define']['MAC'] : 'false';
            $pollMode = (isset($currentTemplate['define']['POLLMODE'])) ? $currentTemplate['define']['POLLMODE'] : '';
            $sfpStartPort = (empty($currentTemplate['define']['SFPSTARTPORT'])) ? 1 : $currentTemplate['define']['SFPSTARTPORT'];
            $sfpEndPort = (empty($currentTemplate['define']['SFPENDPORT'])) ? '' : $currentTemplate['define']['SFPENDPORT'];
            $poeStartPort = (empty($currentTemplate['define']['POESTARTPORT'])) ? 1 : $currentTemplate['define']['POESTARTPORT'];
            $poeEndPort = (empty($currentTemplate['define']['POEENDPORT'])) ? '' : $currentTemplate['define']['POEENDPORT'];
            $sectionResult = '';
            $sectionName = '';
            $finalResult = '';
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
                        if (!empty($tmpAliasArr[0]) and ( !isset($portDescrArr[$tmpAliasArr[0]]) or empty($portDescrArr[$tmpAliasArr[0]]))) {
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
                    if (!$quiet) {
                        $finalResult .= wf_tag('div', false, 'dashboard', '');
                    }

                    @$sectionName = $eachpoll['NAME'];
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

                    $sectionDivBy = (empty($eachpoll['DIV'])) ? ', ""' : ', "' . $eachpoll['DIV'] . '"';
                    $sectionUnits = (empty($eachpoll['UNITS'])) ? ', ""' : ', "' . $eachpoll['UNITS'] . '"';
                    @$sectionParser = $eachpoll['PARSER'];
                    $sectionResult = '';

                    //yeah, lets set some oids to this shit
                    if (!empty($sectionSetOids)) {
                        foreach ($sectionSetOids as $eachSetOid) {
                            $eachSetOidRaw = trim($eachSetOid);
                            $eachSetOidRaw = explode('|', $eachSetOidRaw);
                            //all three parts of set squense present
                            if ((isset($eachSetOidRaw[0])) and ( isset($eachSetOidRaw[1])) and ( isset($eachSetOidRaw[2]))) {
                                $setDataTmp[0] = array('oid' => $eachSetOidRaw[0], 'type' => $eachSetOidRaw[1], 'value' => $eachSetOidRaw[2]);
                                if (!empty($communitywrite)) {
                                    $runSet = $snmp->set($ip, $communitywrite, $setDataTmp);
                                }
                            }
                        }
                    }


                    if ($section == 'portdesc' and $pollMode == 'cumulative' and ! empty($portDescrArr)) {
                        $sectionResult = sp_parse_sw_port_descr($portDescrArr);
                    } else {
                        //now parse each oid
                        if (!empty($sectionOids)) {
                            // in cumulative mode we are not aware of ports amount
                            // so, need to fulfill each section OID with port number
                            // and populate $sectionOids array with OID for each port, like in conservative mode
                            if ($pollMode == 'cumulative' and $sectionPollMode != 'noncumulative' and ! empty($portIdxArr)) {
                                $tmpOID = $sectionOids[0];
                                $sectionOids = array();
                                $isSFPSection = ispos($section, 'sfp');
                                $sfpEndPort = ($isSFPSection and empty($sfpEndPort)) ? count($portIdxArr) : $sfpEndPort;
                                $isPOESection = ispos($section, 'poe');
                                $poeEndPort = ($isPOESection and empty($poeEndPort)) ? count($portIdxArr) : $poeEndPort;

                                foreach ($portIdxArr as $eachPort) {
                                    if ($isSFPSection and ( $eachPort < $sfpStartPort or $eachPort > $sfpEndPort)) {
                                        continue;
                                    }

                                    if ($isPOESection and ( $eachPort < $poeStartPort or $eachPort > $poeEndPort)) {
                                        continue;
                                    }

                                    $sectionOids[] = $tmpOID . '.' . $eachPort;
                                }
                            }

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
                                    } else {
                                        $sectionResult = '';
                                    }
                                }
                            }
                        }
                    }

                    if (!$quiet) {
                        if (!empty($sectionResult)) {
                            $finalResult .= wf_tag('div', false, 'dashtask', '') . wf_tag('strong') . __($sectionName) . wf_tag('strong', true) . '<br>';
                            $finalResult .= $sectionResult . wf_tag('div', true);
                        }
                    }
                }
            }

            $finalResult .= wf_tag('div', true);
            $finalResult .= wf_tag('div', false, '', 'style="clear:both;"');
            $finalResult .= wf_tag('div', true);

            if (!$quiet) {
                show_window('', $finalResult);
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
                    if (!empty($portQTable) and ! empty($statusQTable)
                            and ! ispos($portQTable, 'No Such Object available')
                            and ! ispos($statusQTable, 'No Such Object available')
                            and ! ispos($portQTable, 'No more variables left')
                            and ! ispos($statusQTable, 'No more variables left')) {

                        $dot1Q = true;
                        $portTabOID = $portQTabOID;
                        $statusOID = $statusQOID;
                        $portTable = $portQTable;
                        $statusTable = $statusQTable;
                    }
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

                        if (($deviceFdbMode == 'tlp5428ev2') OR ( $deviceFdbMode == 'tlp2428') OR ( $deviceFdbMode == 'tlp2210')) {
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
                    file_put_contents('exports/' . $ip . '_fdb', $fdbCache);

                    if (!empty($vlanData)) {
                        $fdbVLANCache = serialize($vlanData);
                        file_put_contents('exports/' . $ip . '_fdb_vlan', $fdbVLANCache);
                    }
                }


                //show port data User friendly :)
                if (!empty($portData)) {
                    $fdbExtenInfo = $ubillingConfig->getAlterParam('SW_FDB_EXTEN_INFO');

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
                        // if we have MACs stored along with VLANs - we need to extract MAC portion
                        $eachMAC_VLAN = '';

                        if (ispos($eachMac, '_')) {
                            $eachMAC_VLAN = $eachMac;
                            $eachMac = substr($eachMac, 0, stripos($eachMac, '_'));
                        }

                        //user detection
                        if (isset($allusermacs[$eachMac])) {
                            $userLogin = $allusermacs[$eachMac];
                            @$useraddress = $alladdress[$userLogin];
                            $userlink = wf_Link('?module=userprofile&username=' . $userLogin, web_profile_icon() . ' ' . $useraddress, false);

                            //switch port assing form
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

                            if (!empty($portDescrArr[$eachPort])) {
                                $eachPortDescr = $portDescrArr[$eachPort];
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
                    if (!$quiet) {
                        $fdbTableResult = wf_TableBody($rows, '100%', '0', 'sortable');
                        $fdbTableResult .= wf_tag('b') . __('Total') . ': ' . $recordsCounter . wf_tag('b', true);
                        show_window(__('FDB'), $fdbTableResult);
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
                } else {
                    /* Need Tests
                      if ($deviceMACMode == 'dlp') {
                      //custom dlink mac
                      $tmpOid = '';
                      $MacOfDevice = $snmp->walk($ip, $community, $tmpOid, true);
                      }

                      if ($deviceMACMode == 'tlp5428ev2') {
                      $tmpOid = '';
                      $MacOfDevice = $snmp->walk($ip, $community, $tmpOid, true);
                      }

                      if ($deviceMACMode == 'tlp2428') {
                      $tmpOid = '';
                      $MacOfDevice = $snmp->walk($ip, $community, $tmpOid, true);
                      }

                      if ($deviceMACMode == 'tlp2210') {
                      $tmpOid = '';
                      $MacOfDevice = $snmp->walk($ip, $community, $tmpOid, true);
                      }

                      //foxgate lazy parsing
                      if ($deviceMACMode == 'flp') {
                      $tmpOid = '';
                      $MacOfDevice = $snmp->walk($ip, $community, $tmpOid, true);
                      }
                     */
                }
                if (!empty($MacOfDevice)) {
                    if ($deviceMACMode == 'default') {
                        //default M parser
                        $MACData = sn_SnmpParseDeviceMAC($MacOfDevice);
                    } else {
                        /* Need test
                          if ($deviceMACMode == 'dlp') {
                          //exotic dlink parser
                          $MACData = sn_SnmpParseDeviceMAC($MacOfDevice);
                          }

                          if (($deviceMACMode == 'tlp5428ev2') OR ( $deviceMACMode == 'tlp2428') OR ( $deviceMACMode == 'tlp2210')) {
                          //more exotic tplink parser
                          $MACData = sn_SnmpParseDeviceMAC($MacOfDevice, $tmpOid);
                          }

                          // foxgate - its you again? Oo
                          if ($deviceMACMode == 'flp') {
                          $MACData = sn_SnmpParseDeviceMAC($MacOfDevice, $tmpOid);
                          }
                         */
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
                                @$switchId = $allswitchmacs[$mac]['id'];
                                @$switchIp = $allswitchmacs[$mac]['ip'];
                                $switchLabel = (!empty($switchAddress)) ? $switchAddress : $switchIp;
                                $userlink = wf_Link('?module=switches&edit=' . $switchId, wf_img_sized('skins/menuicons/switches.png', __('Switch'), 11, 13) . ' ' . $switchLabel);
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
        'Counter32:',
        'Counter64:',
        'Gauge32:',
        'Gauge64:',
        'INTEGER:',
        'STRING:',
        'OID:',
        'Timeticks:',
        'Hex-STRING:',
        'Network Address:'
    );

    if (!empty($data)) {
        $data = explode('=', $data);
        if (isset($data[1])) {
            $result = str_ireplace($dataTypes, '', $data[1]);
            $result = trim($result);
        }
    }


    return($result);
}

?>