<?php

/**
 * PON Disable lan port on onu class
 */
class OnuDlp extends OnuBase {

    /**
     * Performs Disable lan port on onu
     *
     * @return bool
     *
     * @throws Exception
     */
    public function dlpOnu() {
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
        } elseif ($this->checkBDCOMEssentialOpts()) {
            $decMacOnu = $this->macHexToDec($macOnu);

            if (empty($decMacOnu)) {
                $this->displayMessage = __('Wrong MAC format (should be XX:XX:XX:XX:XX:XX)');
                return (false);
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
                        $reloadData[] = array('oid' => $snmpData['onu']['DLP'] . '.' . $ifIndex . '.1',  'type' => 'i', 'value' => '2');
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
     * Returns Disable lan port on onu button
     *
     * @return string
     */
    public function dlpForm() {

        $Inputs = wf_SubmitClassed('true', 'vlanButton', 'DlpOnu', __('Disable lan port on onu'));
        $Form = wf_Form("", 'POST', $Inputs);
        return($Form);
    }

}
