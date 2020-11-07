<?php

/**
 * PON ONU degegistering class
 */
class OnuDeregister extends OnuBase {

    /**
     * Performs ONU deregistration
     *
     * @return bool
     *
     * @throws Exception
     */
    public function deregOnu() {
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
                    $reloadPONIdx = $snmpData['onu']['DEREGPONINDEX'];
                    $reloadONUIdx = $snmpData['onu']['DEREGONUINDEX'];
                }

                if ($snmpControlMode == 'STELSFD11' or $snmpControlMode == 'STELSFD12') {
                    $reloadOperIdx = $snmpData['onu']['OPERATION'];
                    $reloadOperNum = $snmpData['onu']['DEREG'];
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
                                    $reloadData[] = array('oid' => $reloadOperIdx . '.5.2.1.4' . '.1' . $onuIndex, 'type' => 'i', 'value' => $reloadOperNum);
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
            $vlanMode = ': ' . $snmpData['vlan']['VLANMODE'];
            $this->displayMessage = __('Function is not supported by this OLT') . $vlanMode;
            return (false);
        }

        if (!$onuFound) {
            $this->displayMessage = __('ONU not found');
        }

        return (false);
    }

    /**
     * Returns ONU deregister button
     *
     * @return string
     */
    public function deregForm() {
        $Inputs = wf_SubmitClassed('true', 'vlanButton', 'DeregOnu', __('Deregister onu'));
        $Form = wf_Form("", 'POST', $Inputs);
        return($Form);
    }

}
