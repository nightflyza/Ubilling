<?php

class OnuDescribe extends OnuBase {

    /**
     * Flag to determine that operation did actually happened and went successfully
     *
     * @var bool
     */
    public $operationSuccessful = false;

    /**
     * Returns ONU description string
     *
     * @return bool|mixed|string
     *
     * @throws Exception
     */
    public function getOnuDescription() {
        $result = false;
        $onuFound = true;

        if (empty($this->snmpTemplateParsed)) {
            $this->displayMessage = __('SNMP template is not found or not exists');
            return ($result);
        }

        if (empty($this->onuData)) {
            $this->displayMessage = __('ONU data is empty');
            return ($result);
        }

        $macOnu = $this->onuData['mac'];
        $snmpData = $this->snmpTemplateParsed;

        if (isset($snmpData['onu']['CONTROLMODE'])) {
            $snmpControlMode = $snmpData['onu']['CONTROLMODE'];

            if ($snmpControlMode == 'STELSFD11') {
                $this->displayMessage = __('Function is not supported by this OLT') . ': ' . $snmpControlMode;
                return ($result);
            }

            if ($snmpControlMode == 'VSOL_1600D') {
                $macIndexOID = $snmpData['signal']['MACINDEX'];
                $macValType  = $snmpData['signal']['MACVALUE'];
                $descrGetIdx = $snmpData['onu']['DESCRIPTIONGET'];
                $descrValue  = $snmpData['onu']['DESCRVALUE'];

                $macIndexFull = $this->snmp->walk($this->oltData['ip'], $this->oltData['snmp'], $macIndexOID);

                if (!empty($macIndexFull)) {
                    $macIndexFull = str_ireplace(array($macIndexOID, $macValType, '"'), ' ', $macIndexFull);
                    $macIndexFull = explodeRows($macIndexFull);

                    foreach ($macIndexFull as $eachRow) {
                        $indexMAC = explode(' = ', $eachRow);

                        if (!empty($indexMAC[1])) {
                            $tmpCleanMAC = strtolower(trim($indexMAC[1]));

                            if ($macOnu == $tmpCleanMAC) {
                                $onuFound = true;
                                $tmpPONONUIdx = trim($indexMAC[0]);

                                $result = $this->snmp->walk($this->oltData['ip'], $this->oltData['snmp'], $descrGetIdx . $tmpPONONUIdx, false);

                                if (!empty($result) or $result === '') {
                                    $this->operationSuccessful = true;
                                    $result = str_replace(array($descrGetIdx . $tmpPONONUIdx, $descrValue, '=', '"', ' '), '', $result);
                                    return ($result);
                                }
                            }
                        } else {
                            $onuFound = false;
                        }
                    }
                } else {
                    $onuFound = false;
                }
            }
        } elseif ($this->checkBDCOMEssentialOpts()) {
            $eponInt = '';
            $decMacOnu = $this->macHexToDec($macOnu);

            if (empty($decMacOnu)) {
                $this->displayMessage = __('Wrong MAC format (should be XX:XX:XX:XX:XX:XX)');
                return ($result);
            }

            if ($snmpData['vlan']['VLANMODE'] == 'BDCOM_B') {
                $ifIndexOid = $snmpData['onu']['IFINDEX'] . '.' . $decMacOnu;
                $ifIndexFull = snmp2_get($this->oltData['ip'], $this->oltData['snmp'], $ifIndexOid);
                $ifIndex = trim(str_replace(array($ifIndexOid, 'INTEGER:', '= '), '', $ifIndexFull));

                if (!empty($ifIndex)) {
                    $eponIntBare = $this->snmp->walk($this->oltData['ip'], $this->oltData['snmp'], $snmpData['onu']['EPONINDEX'] . '.' . $ifIndex);
                    $eponInt = trim(str_replace(array($snmpData['onu']['EPONINDEX'] . '.' . $ifIndex, ' = INTEGER: '), '', $eponIntBare));
                }

                if (!empty($eponInt)) {
                    $descriptionOid = $snmpData['onu']['DESCRIPTION'] . '.' . $eponInt . '.' . $decMacOnu;
                    $checkResult = $this->snmp->walk($this->oltData['ip'], $this->oltData['snmp'], $descriptionOid, FALSE);
                    $result = trim(str_replace(array($descriptionOid, ' = STRING: '), '', $checkResult));

                    if (!empty($result) or $result === '') {
                        $this->operationSuccessful = true;
                        return ($result);
                    }
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
                        $eponIntBare = $this->snmp->walk($this->oltData['ip'], $this->oltData['snmp'], $snmpData['onu']['EPONINDEX'] . '.' . $ifIndex);
                        $eponInt = trim(str_replace(array($snmpData['onu']['EPONINDEX'] . '.' . $ifIndex, ' = INTEGER: '), '', $eponIntBare));
                    }

                    if (!empty($eponInt)) {
                        $descriptionOid = $snmpData['onu']['DESCRIPTION'] . '.' . $eponInt . '.' . $decMacOnu;
                        $checkResult = $this->snmp->walk($this->oltData['ip'], $this->oltData['snmp'], $descriptionOid, FALSE);
                        $result = trim(str_replace(array($descriptionOid, ' = STRING: '), '', $checkResult));

                        if (!empty($result) or $result === '') {
                            $this->operationSuccessful = true;
                            return ($result);
                        }
                    } else {
                        $onuFound = false;
                    }
                }
            }
        } else {
            $this->displayMessage = __('Essential SNMP options are missing in template');
            return ($result);
        }

        if (!$this->operationSuccessful) {
            if ($onuFound) {
                $this->displayMessage = __('Operation unsuccessful');
            } else {
                $this->displayMessage = __('ONU not found');
            }
        }

        return ($result);
    }

    /**
     * Sets ONU description on OLT
     *
     * @param $description
     *
     * @return bool|mixed|string
     *
     * @throws Exception
     */
    public function describeOnu($description) {
        $result = false;
        $onuFound = true;
        $description = trim($description, " \t\n\r\0\x0B\x31\x22");
        $descrIsEmptyStr = ($description === '');

        if (empty($this->snmpTemplateParsed)) {
            $this->displayMessage = __('SNMP template is not found or not exists');
            return ($result);
        }

        if (empty($this->onuData)) {
            $this->displayMessage = __('ONU data is empty');
            return ($result);
        }

        $macOnu = $this->onuData['mac'];
        $snmpData = $this->snmpTemplateParsed;

        if (isset($snmpData['onu']['CONTROLMODE'])) {
            $snmpControlMode = $snmpData['onu']['CONTROLMODE'];

            if ($snmpControlMode == 'STELSFD11') {
                $this->displayMessage = __('Function is not supported by this OLT') . ': ' . $snmpControlMode;
                return ($result);
            }

            if ($snmpControlMode == 'VSOL_1600D') {
                $description = ($descrIsEmptyStr) ? '""' : $description;
                $macIndexOID = $snmpData['signal']['MACINDEX'];
                $macValType  = $snmpData['signal']['MACVALUE'];
                $descrPONIdx = $snmpData['onu']['DESCRPONINDEX'];
                $descrONUIdx = $snmpData['onu']['DESCRONUINDEX'];
                $descrStrIdx = $snmpData['onu']['DESCRSTRING'];
                $descrCommit = $snmpData['onu']['DESCRCOMMIT'];
                $descrGetIdx = $snmpData['onu']['DESCRIPTIONGET'];
                $descrValue  = $snmpData['onu']['DESCRVALUE'];

                $macIndexFull = $this->snmp->walk($this->oltData['ip'], $this->oltData['snmp'], $macIndexOID);

                if (!empty($macIndexFull)) {
                    $macIndexFull = str_ireplace(array($macIndexOID, $macValType, '"'), ' ', $macIndexFull);
                    $macIndexFull = explodeRows($macIndexFull);
                    $describeData = array();

                    foreach ($macIndexFull as $eachRow) {
                        $indexMAC = explode(' = ', $eachRow);

                        if (!empty($indexMAC[1])) {
                            $tmpCleanMAC = strtolower(trim($indexMAC[1]));

                            if ($macOnu == $tmpCleanMAC) {
                                $onuFound = true;
                                $tmpPONONUIdx = trim($indexMAC[0]);
                                $tmpIdx = trim(substr($indexMAC[0], 1), '.');
                                $ponIfaceIndex = substr($tmpIdx, 0, strpos($tmpIdx, '.', 1));
                                $onuIndex = substr($tmpIdx, strpos($tmpIdx, '.', 1) + 1);
                                $describeData[] = array('oid' => $descrPONIdx, 'type' => 'i', 'value' => $ponIfaceIndex);
                                $describeData[] = array('oid' => $descrONUIdx, 'type' => 'i', 'value' => $onuIndex);
                                $describeData[] = array('oid' => $descrStrIdx, 'type' => 's', 'value' => $description);
                                $describeData[] = array('oid' => $descrCommit, 'type' => 'i', 'value' => '1');

                                $this->snmp->set($this->oltData['ip'], $this->oltData['snmpwrite'], $describeData);
                                //very shitty hack
                                //sleep(4);
                                $result = $this->snmp->walk($this->oltData['ip'], $this->oltData['snmp'], $descrGetIdx . $tmpPONONUIdx, false);

                                if (!empty($result) or ($result === '' and $descrIsEmptyStr)) {
                                    $this->operationSuccessful = true;
                                    $result = str_replace(array($descrGetIdx . $tmpPONONUIdx, $descrValue, '=', '"', ' '), '', $result);
                                    return ($result);
                                }
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
            $eponInt = '';
            $decMacOnu = $this->macHexToDec($macOnu);

            if (empty($decMacOnu)) {
                $this->displayMessage = __('Wrong MAC format (should be XX:XX:XX:XX:XX:XX)');
                return ($result);
            }

            if ($snmpData['vlan']['VLANMODE'] == 'BDCOM_B') {
                $ifIndexOid = $snmpData['onu']['IFINDEX'] . '.' . $decMacOnu;
                $ifIndexFull = snmp2_get($this->oltData['ip'], $this->oltData['snmp'], $ifIndexOid);
                $ifIndex = trim(str_replace(array($ifIndexOid, 'INTEGER:', '= '), '', $ifIndexFull));

                if (!empty($ifIndex)) {
                    $eponIntBare = $this->snmp->walk($this->oltData['ip'], $this->oltData['snmp'], $snmpData['onu']['EPONINDEX'] . '.' . $ifIndex);
                    $eponInt = trim(str_replace(array($snmpData['onu']['EPONINDEX'] . '.' . $ifIndex, ' = INTEGER: '), '', $eponIntBare));
                }

                if (!empty($eponInt)) {
                    $describeData[] = array(
                        'oid'   => $snmpData['onu']['DESCRIPTION'] . '.' . $eponInt . '.' . $decMacOnu,
                        'type'  => 's',
                        'value' => '"' . addcslashes($description, '_') . '"',
                    );

                    $describeData[] = array(
                        'oid'   => $snmpData['vlan']['SAVE'],
                        'type'  => 'i',
                        'value' => '1'
                    );

                    $result = $this->snmp->set($this->oltData['ip'], $this->oltData['snmpwrite'], $describeData);
                    $result.= $this->snmp->walk($this->oltData['ip'], $this->oltData['snmp'], $snmpData['onu']['DESCRIPTION'] . '.' . $eponInt . '.' . $decMacOnu, FALSE);

                    if (!empty($result) or ($result === '' and $descrIsEmptyStr)) {
                        $this->operationSuccessful = true;
                        return ($result);
                    }
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
                        $eponIntBare = $this->snmp->walk($this->oltData['ip'], $this->oltData['snmp'], $snmpData['onu']['EPONINDEX'] . '.' . $ifIndex);
                        $eponInt = trim(str_replace(array($snmpData['onu']['EPONINDEX'] . '.' . $ifIndex, ' = INTEGER: '), '', $eponIntBare));
                    }

                    if (!empty($eponInt)) {
                        $describeData[] = array(
                            'oid'   => $snmpData['onu']['DESCRIPTION'] . '.' . $eponInt . '.' . $decMacOnu,
                            'type'  => 's',
                            'value' => '"' . addcslashes($description, '_') . '"',
                        );
                        $describeData[] = array(
                            'oid'   => $snmpData['vlan']['SAVE'],
                            'type'  => 'i',
                            'value' => '1'
                        );
                        $result = $this->snmp->set($this->oltData['ip'], $this->oltData['snmpwrite'], $describeData);
                        $result.= $this->snmp->walk($this->oltData['ip'], $this->oltData['snmp'], $snmpData['onu']['DESCRIPTION'] . '.' . $eponInt . '.' . $decMacOnu, FALSE);

                        if (!empty($result) or ($result === '' and $descrIsEmptyStr)) {
                            $this->operationSuccessful = true;
                            return ($result);
                        }
                    } else {
                        $onuFound = false;
                    }
                }
            }
        } else {
            $this->displayMessage = __('Essential SNMP options are missing in template');
            return ($result);
        }

        if (!$this->operationSuccessful) {
            if ($onuFound) {
                $this->displayMessage = __('Operation unsuccessful');
            } else {
                $this->displayMessage = __('ONU not found');
            }
        }

        return ($result);
    }

    /**
     * Returns ONU description controls
     *
     * @param $login
     *
     * @return string
     *
     * @throws Exception
     */
    public function describeForm($login) {
        $onuDescription = trim($this->getOnuDescription(), " \t\n\r\0\x0B\x31\x22");
        $onuDescription = (empty($onuDescription)) ? $login : $onuDescription;

        $descriptionInputId = wf_InputId();
        $inputs = wf_delimiter();
        $inputs .= wf_tag('input', false, '', 'type="text" name="onuDescription" value="' . $onuDescription . '" id="' . $descriptionInputId . '" size="60" style="margin-left: 10px;"');
        $inputs .= wf_tag('label', false, '', 'for ="' . $descriptionInputId . '"') . __('Description') . wf_tag('label', true);
        $inputs .= wf_delimiter();
        $inputs .= wf_SubmitClassed('true', 'vlanButton', 'DescribeOnu', __('Change onu description'));
        $form = wf_Form("", 'POST', $inputs);

        return ($form);
    }
}
