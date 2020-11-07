<?php

/**
 * PON ONU rebooting class
 */
class OnuReboot extends OnuBase {

    /**
     * Performs ONU reboot
     *
     * @return bool
     *
     * @throws Exception
     */
    public function rebootOnu() {
        $onuFound = true;

        if (empty($this->snmpTemplateParsed)) {
            $this->displayMessage = __('SNMP template is not found or not exists');
            return (false);
        }

        if (empty($this->onuData)) {
            $this->displayMessage = __('ONU data is empty');
            return (false);
        }

        $macOnu = $this->onuData['mac'];
        $snmpData = $this->snmpTemplateParsed;

        if (isset($snmpData['onu']['CONTROLMODE'])) {
            $snmpControlMode = $snmpData['onu']['CONTROLMODE'];

            if ($snmpControlMode == 'VSOL_1600D' or $snmpControlMode == 'STELSFD11' or $snmpControlMode == 'STELSFD12') {
                $macIndexOID = $snmpData['signal']['MACINDEX'];
                $macValType  = $snmpData['signal']['MACVALUE'];

                if ($snmpControlMode == 'VSOL_1600D') {
                    $reloadPONIdx = $snmpData['onu']['RELOADPONINDEX'];
                    $reloadONUIdx = $snmpData['onu']['RELOADONUINDEX'];
                }

                if ($snmpControlMode == 'STELSFD11' or $snmpControlMode == 'STELSFD12') {
                    $reloadOperIdx = $snmpData['onu']['OPERATION'];
                    $reloadOperNum = $snmpData['onu']['RELOAD'];
                }

                $macIndexFull = $this->snmp->walk($this->oltData['ip'], $this->oltData['snmp'], $macIndexOID);

                if (!empty($macIndexFull)) {
                    $macIndexFull = str_ireplace(array($macIndexOID, $macValType, '"'), ' ', $macIndexFull);
                    $macIndexFull = explodeRows($macIndexFull);
                    $reloadData = array();

                    foreach ($macIndexFull as $eachRow) {
                        $indexMAC = explode(' = ', $eachRow);

                        if (!empty($indexMAC[1])) {
                            if ($snmpControlMode == 'VSOL_1600D') {
                                $tmpCleanMAC = trim($indexMAC[1]);
                            }

                            if ($snmpControlMode == 'STELSFD11' or $snmpControlMode == 'STELSFD12') {
                                $tmpCleanMAC = strtolower(str_replace(' ', ':', trim($indexMAC[1])));
                            }

                            if ($macOnu == $tmpCleanMAC) {
                                $tmpIdx = trim(substr($indexMAC[0], 1), '.');
                                $ponIfaceIndex = substr($tmpIdx, 0, strpos($tmpIdx, '.', 1));
                                $onuIndex = substr($tmpIdx, strpos($tmpIdx, '.', 1) + 1);

                                if ($snmpControlMode == 'VSOL_1600D') {
                                    $reloadData[] = array('oid' => $reloadPONIdx, 'type' => 'i', 'value' => $ponIfaceIndex);
                                    $reloadData[] = array('oid' => $reloadONUIdx, 'type' => 'i', 'value' => $onuIndex);
                                }

                                if ($snmpControlMode == 'STELSFD11') {
                                    $onuIndex = ($onuIndex - 1) / 256;
                                    $reloadData[] = array('oid' => $reloadOperIdx . '.' . $ponIfaceIndex . '.' . $onuIndex, 'type' => 'i', 'value' => $reloadOperNum);
                                }

                                if ($snmpControlMode == 'STELSFD12') {
                                    $reloadData[] = array('oid' => $reloadOperIdx . '.1.1.17' . '.1' . $onuIndex, 'type' => 'i', 'value' => $reloadOperNum);
                                }

                                $this->snmp->set($this->oltData['ip'], $this->oltData['snmpwrite'], $reloadData);
                                return (true);
                            } else {
                                $onuFound = false;
                            }
                        }
                    }
                } else {
                    $onuFound = false;
                }
            }
        } elseif ($this->checkBDCOMEssentialOpts()) {
            $decMacOnu = $this->macHexToDec($macOnu);

            if (empty($decMacOnu)) {
                $this->displayMessage = __('Wrong MAC format (should be XX:XX:XX:XX:XX:XX)');
                return (false);
            }

            if ($snmpData['vlan']['VLANMODE'] == 'BDCOM_B') {
                $ifIndexOid = $snmpData['onu']['IFINDEX'] . '.' . $decMacOnu;
                $ifIndexFull = snmp2_get($this->oltData['ip'], $this->oltData['snmp'], $ifIndexOid);
                $ifIndex = trim(str_replace(array($ifIndexOid, 'INTEGER:'), '', $ifIndexFull));

                if (!empty($ifIndex)) {
                    $reloadData[] = array('oid' => $snmpData['onu']['RELOAD'] . '.' . $ifIndex, 'type' => 'i', 'value' => '0');
                    $result = $this->snmp->set($this->oltData['ip'], $this->oltData['snmpwrite'], $reloadData);
                    return (true);
                } else {
                    $onuFound = false;
                }
            }

            if ($snmpData['vlan']['VLANMODE'] == 'BDCOM_C') {
                $allOnuOid = $snmpData['signal']['MACINDEX'];
                snmp_set_oid_output_format(SNMP_OID_OUTPUT_NUMERIC);
                $allOnu = @snmp2_real_walk($this->oltData['ip'], $this->oltData['snmp'], $allOnuOid);
                $searchArray = array();

                if (!empty($allOnu)) {
                    foreach ($allOnu as $eachIndex => $eachOnu) {
                        $eachIndex = trim(str_replace($allOnuOid . '.', '', $eachIndex));
                        $eachOnu = strtolower(trim(str_replace($snmpData['signal']['MACVALUE'], '', $eachOnu)));
                        $eachOnuMacArray = explode(" ", $eachOnu);
                        $eachOnuMac = implode(":", $eachOnuMacArray);
                        $searchArray[$eachOnuMac] = $eachIndex;
                    }

                    if (!empty($searchArray) and isset($searchArray[$macOnu])) {
                        $ifIndex = $searchArray[$macOnu];
                        $reloadData[] = array('oid' => $snmpData['onu']['RELOAD'] . '.' . $ifIndex, 'type' => 'i', 'value' => '0');
                        $result = $this->snmp->set($this->oltData['ip'], $this->oltData['snmpwrite'], $reloadData);
                        return (true);
                    } else {
                        $onuFound = false;
                    }
                } else {
                    $onuFound = false;
                }
            }
        } else {
            $this->displayMessage = __('Essential SNMP options are missing in template');
            return (false);
        }

        if (!$onuFound) {
            $this->displayMessage = __('ONU not found');
        }

        return (false);
    }

    /**
     * Returns ONU reboot button
     *
     * @return string
     */
    public function rebootForm() {
        $Inputs = wf_SubmitClassed('true', 'vlanButton', 'RebootOnu', __('Reboot onu'));
        $Form = wf_Form("", 'POST', $Inputs);
        return($Form);
    }

}
