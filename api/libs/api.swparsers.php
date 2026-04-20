<?php


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
    return ($result);
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
        $rawTime = ubRouting::filters($rawTime, 'int');
        if (!empty($rawTime)) {
            $result = zb_formatTime($rawTime) . wf_tag('br');
        }
    } else {
        $result = __('Empty reply received');
    }
    return ($result);
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
    return ($result);
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
    return ($result);
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
    return ($result);
}

/**
 * Returns CPU percent load as gauge
 *
 * @param string $data
 *
 * @return string
 */
function sp_parse_cpu_gauge($data) {
    $result = '';
    if (!empty($data)) {
        $rawValue = zb_SanitizeSNMPValue($data);
        if (!empty($rawValue)) {
            $percent = $rawValue;
            $options = 'max: 100,
                        min: 0,
                        width: 280, height: 280,
                        greenFrom: 0, greenTo: 40,
                        yellowFrom:40, yellowTo: 70,
                        redFrom: 70, redTo: 100,
                        minorTicks: 5';

            $result = wf_renderGauge($percent, '', '%', $options);
        }
    } else {
        $result = __('Empty reply received');
    }
    return ($result);
}

/**
 * Returns battery voltage value from equicom ping3 as text
 *
 * @param string $data
 *
 * @return string
 */
function sp_parse_eping_bat($data) {
    $result = '';
    if (!empty($data)) {
        $rawValue = zb_SanitizeSNMPValue($data);
        if (!empty($rawValue)) {
            $result = ($rawValue / 10) . ' V' . wf_tag('br');
        }
    } else {
        $result = __('Empty reply received');
    }
    return ($result);
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
 * Raisecom Port state data parser
 *
 * @return string
 */
function sp_parse_raportstates($data) {
    if (!empty($data)) {
        $data = explode('=', $data);
        $data[0] = trim($data[0]);
        $portnum = substr($data[0], -2);
        $portnum = str_replace('.', '', $portnum);
        $portnum = $portnum - 32;

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
                        if (@$data[1] == 0 or @$data[2] == 0 or @$data[3] == 0 or @$data[4] == 0) {
                            $cells_data .= __("OK");
                            // Return Length for Pair2, becase some modele have accrose rawdata
                            @$cells_data .= ($data[2] == 0 and $data[6] > 0) ? "," . __("Cable Length:") . $data[6] : '';
                        } elseif ($data[1] == 1 or $data[2] == 1 or $data[3] == 1 or $data[4] == 1) {
                            $cells_data .= ($data[1] == 1) ? __("Pair1 Open:") . $data[5] . " " : '';
                            $cells_data .= ($data[2] == 1) ? __("Pair2 Open:") . $data[6] . " " : '';
                            $cells_data .= ($data[3] == 1) ? __("Pair3 Open:") . $data[7] . " " : '';
                            $cells_data .= ($data[4] == 1) ? __("Pair4 Open:") . $data[8] . " " : '';
                        } elseif ($data[1] == 2 or $data[2] == 2 or $data[3] == 2 or $data[4] == 2) {
                            $cells_data .= ($data[1] == 2) ? __("Pair1 Short:") . $data[5] . " " : '';
                            $cells_data .= ($data[2] == 2) ? __("Pair2 Short:") . $data[6] . " " : '';
                            $cells_data .= ($data[3] == 2) ? __("Pair3 Short:") . $data[7] . " " : '';
                            $cells_data .= ($data[4] == 2) ? __("Pair4 Short:") . $data[8] . " " : '';
                        } elseif ($data[1] == 3 or $data[2] == 3 or $data[3] == 3 or $data[4] == 3) {
                            $cells_data .= ($data[1] == 3) ? __("Pair1 Open-Short:") . $data[5] . " " : '';
                            $cells_data .= ($data[2] == 3) ? __("Pair2 Open-Short:") . $data[6] . " " : '';
                            $cells_data .= ($data[3] == 3) ? __("Pair3 Open-Short:") . $data[7] . " " : '';
                            $cells_data .= ($data[4] == 3) ? __("Pair4 Open-Short:") . $data[8] . " " : '';
                        } elseif ($data[1] == 4 or $data[2] == 4 or $data[3] == 4 or $data[4] == 4) {
                            $cells_data .= ($data[1] == 4) ? __("Pair1 crosstalk") . " " : '';
                            $cells_data .= ($data[2] == 4) ? __("Pair2 crosstalk") . " " : '';
                            $cells_data .= ($data[3] == 4) ? __("Pair3 crosstalk") . " " : '';
                            $cells_data .= ($data[4] == 4) ? __("Pair4 crosstalk") . " " : '';
                        } elseif ($data[1] == 5 or $data[2] == 5 or $data[5] == 5 or $data[4] == 5) {
                            $cells_data .= ($data[1] == 5) ? __("Pair1 unknown") . " " : '';
                            $cells_data .= ($data[2] == 5) ? __("Pair2 unknown") . " " : '';
                            $cells_data .= ($data[3] == 5) ? __("Pair3 unknown") . " " : '';
                            $cells_data .= ($data[4] == 5) ? __("Pair4 unknown") . " " : '';
                        } elseif ($data[1] == 6 or $data[2] == 6 or $data[5] == 6 or $data[4] == 6) {
                            $cells_data .= ($data[1] == 6) ? __("Pair1 count") . " " : '';
                            $cells_data .= ($data[2] == 6) ? __("Pair2 count") . " " : '';
                            $cells_data .= ($data[3] == 6) ? __("Pair3 count") . " " : '';
                            $cells_data .= ($data[4] == 6) ? __("Pair4 count") . " " : '';
                        } elseif ($data[1] == 7 or $data[2] == 7 or $data[5] == 7 or $data[4] == 7) {
                            $cells_data .= __("No Cable");
                        } elseif ($data[1] == 8 or $data[2] == 8 or $data[5] == 8 or $data[4] == 8) {
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
 * Raisecom-ISCOM2624G-4GE-AC Port byte counters data parser
 *
 * @return string
 */
function sp_parse_raportbytes($data) {
    if (!empty($data)) {
        $data = explode('=', $data);
        $data[0] = trim($data[0]);
        $portnum = substr($data[0], -2);
        $portnum = $portnum - 32;
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
    $result = '';

    if (
        !empty($data)
        and ! ispos($data, 'No Such Object available')
        and ! ispos($data, 'No more variables left')
    ) {

        $data = trimSNMPOutput($data, '');

        $portnum = substr($data[0], -2);
        $portnum = str_replace('.', '', $portnum);

        $value = $data[1];

        // 10 G
        if ($value == 1410065408) {
            $value = 10000000;
            $units = __('Gbit/s');
        }


        if (!empty($divBy) and is_numeric($divBy)) {
            $value = $value / $divBy;
        }

        if (!empty($units)) {
            $value = $value . ' ' . __($units);
        }

        $cells = wf_TableCell($portnum, '24', '', 'style="height:20px;"');
        $cells .= wf_TableCell($value);
        $rows = wf_TableRow($cells, 'row3');
        $result = wf_TableBody($rows, '100%', 0, '');
    } else {
        $cells = wf_TableCell('', '24', '', 'style="height:20px;"');
        $cells .= wf_TableCell(__('Empty reply received'));
        $rows = wf_TableRow($cells, 'row3');
        $result = wf_TableBody($rows, '100%', 0, '');
    }

    return ($result);
}

/**
 * Raisecom-ISCOM2624G-4GE-AC parser for values with units and possible division necessity
 *
 * @param string $data
 * @param string $divBy
 * @param string $units
 *
 * @return mixed|string
 */
function sp_parse_division_units_ra($data, $divBy = '', $units = '') {
    $result = '';

    if (
        !empty($data)
        and ! ispos($data, 'No Such Object available')
        and ! ispos($data, 'No more variables left')
    ) {

        $data = trimSNMPOutput($data, '');

        $portnum = substr($data[0], -2);
        $portnum = str_replace('.', '', $portnum);
        $portnum = $portnum - 32;


        $value = $data[1];

        if (!empty($divBy) and is_numeric($divBy)) {
            $value = $value / $divBy;
        }

        if (!empty($units)) {
            $value = $value . ' ' . __($units);
        }

        $cells = wf_TableCell($portnum, '24', '', 'style="height:20px;"');
        $cells .= wf_TableCell($value);
        $rows = wf_TableRow($cells, 'row3');
        $result = wf_TableBody($rows, '100%', 0, '');
    } else {
        $cells = wf_TableCell('', '24', '', 'style="height:20px;"');
        $cells .= wf_TableCell(__('Empty reply received'));
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
    $result = '';

    if (
        !empty($data)
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
    } else {
        $cells = wf_TableCell('', '24', '', 'style="height:20px;"');
        $cells .= wf_TableCell(__('Empty reply received'));
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

    if (
        !empty($data)
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
 * Standard parser for values with units and possible division necessity
 * for data without ports info
 *
 * @param string $data
 * @param int $divBy 
 * @param string $units min|max|yellow|red
 *
 * @return mixed|string
 */
function sp_parse_division_temperature($data, $divBy = '', $units = '') {
    $result = '';
    if (
        !empty($data)
        and ! ispos($data, 'No Such Object available')
        and ! ispos($data, 'No more variables left')
    ) {

        $data = trimSNMPOutput($data, '');
        $value = $data[1];
        $value = ubRouting::filters($value, 'float');

        if (!empty($divBy) and is_numeric($divBy)) {
            $value = $value / $divBy;
        }

        $min = 5;
        $max = 100;
        $yellow = 30;
        $red = 50;
        if (!empty($units)) {
            //mapped from units format: min|max|yellow|red
            $chartOpts = explode('|', $units);
            $min = $chartOpts[0];
            $max = $chartOpts[1];
            $yellow = $chartOpts[2];
            $red = $chartOpts[3];
        }

        $options = 'max: ' . $max . ',
                    min: ' . $min . ',
                    width: 280, height: 280,
                    greenFrom: ' . ($min + 1) . ', greenTo: ' . $yellow . ',
                    yellowFrom:' . $yellow . ', yellowTo: ' . $red . ',
                    redFrom: ' . $red . ', redTo: ' . ($max - 1) . ',
                    minorTicks: 5';

        $result = wf_renderTemperature($value, '', $options);
    } else {
        $cells = wf_TableCell(__('Empty reply received'));
        $rows = wf_TableRow($cells, 'row3');
        $result = wf_TableBody($rows, '100%', 0, '');
    }

    return ($result);
}
