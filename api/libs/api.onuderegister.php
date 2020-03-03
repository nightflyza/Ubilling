<?php

class OnuDeregister {

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains user's onu data from pononu table
     * 
     * @var array
     */
    protected $onuData = array();

    /**
     * Contains OLT data (where user's onu is linked to OLT)
     * 
     * @var array
     */
    protected $oltData = array();

    /**
     * Contains OLT snmp template file name
     * 
     * @var array
     */
    protected $oltSnmptemplate = array();

    /**
     * Contain's OLT switch ID
     * 
     * @var int
     */
    protected $oltId = '';

    /**
     * User's login
     * 
     * @var string
     */
    protected $login = '';

    /**
     * Placeholder for snmp class
     * 
     * @var pointer
     */
    protected $snmp = '';

    /**
     * Placeholder for any message to return and/or display
     *
     * @var string
     */
    public $displayMessage = '';


    public function __construct($login = '') {
        if (!empty($login)) {
            $this->LoadAlter();
            $this->login = $login;
            $this->GetOnuData($login);
            $this->snmp = new SNMPHelper;
            if (!empty($this->oltId)) {
                $this->GetOltData($this->oltId);
            }
            if (!empty($this->oltData)) {
                $this->GetOltModelData($this->oltData['modelid']);
            }
        }
    }

    /**
     * load alter.ini config     
     * 
     * @return void
     */
    protected function LoadAlter() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Get onu data mac and olt ID to which onu is linked
     * 
     * @param string $login 
     */
    protected function GetOnuData($login) {
        $query = "SELECT * FROM `pononu` WHERE `login` = '$login'";
        $data = simple_query($query);
        if (!empty($data)) {
            $this->oltId = $data['oltid'];
            $this->onuData = $data;
        }
    }

    /**
     * Loads data from table `switches` to $oltData var (filter by OLT switch ID)
     * 
     * @param int $oltID
     */
    protected function GetOltData($oltID) {
        $query = "SELECT * FROM `switches` WHERE `id`='$oltID'";
        $data = simple_query($query);
        if (!empty($data)) {
            $this->oltData = $data;
        }
    }

    /**
     * Loads data from table `switchmodels` to $oltSnmptemplate (filter by OLT switch model id)
     * 
     * @param int $modelID
     */
    protected function GetOltModelData($modelID) {
        $query = "SELECT * FROM `switchmodels` WHERE `id`='$modelID'";
        $data = simple_query($query);
        if (!empty($data)) {
            $this->oltSnmptemplate = $data['snmptemplate'];
        }
    }

    /**
     * Format heximal mac address to decimal or show error
     * 
     * @param string $macOnu 
     * 
     * @return string
     */
    protected function MacHexToDec($macOnu) {
        if (check_mac_format($macOnu)) {
            $res = array();
            $args = explode(":", $macOnu);
            foreach ($args as $each) {
                $res[] = hexdec($each);
            }
            $string = implode(".", $res);
            return ($string);
        } else {
            show_error("Wrong mac format (should be XX:XX:XX:XX:XX:XX)");
        }
    }

    public function deregOnu() {
        if (!empty($this->onuData) AND ! empty($this->oltData) AND ! empty($this->oltSnmptemplate)) {
            $macOnu = $this->onuData['mac'];
            $decMacOnu = $this->MacHexToDec($macOnu);

            if (!file_exists(CONFIG_PATH . "/snmptemplates/" . $this->oltSnmptemplate)) {
                return false;
            }
            $snmpData = rcms_parse_ini_file(CONFIG_PATH . "/snmptemplates/" . $this->oltSnmptemplate, true);

            if (isset($snmpData['onu']['CONTROLMODE'])) {
                $snmpControlMode = $snmpData['onu']['CONTROLMODE'];

                if ($snmpControlMode == 'VSOL_1600D' or $snmpControlMode == 'STELSFD11') {
                    $macIndexOID    = $snmpData['signal']['MACINDEX'];
                    $macValType     = $snmpData['signal']['MACVALUE'];

                    if ($snmpControlMode == 'VSOL_1600D') {
                        $reloadPONIdx = $snmpData['onu']['DEREGPONINDEX'];
                        $reloadONUIdx = $snmpData['onu']['DEREGONUINDEX'];
                    }

                    if ($snmpControlMode == 'STELSFD11') {
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

                                if ($snmpControlMode == 'STELSFD11') {
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

                                    $this->snmp->set($this->oltData['ip'], $this->oltData['snmpwrite'], $reloadData);
                                    return (true);
                                }
                            }
                        }
                    }
                }

                return (false);
            } else {
                $vlanMode = (empty($snmpData['vlan']['VLANMODE'])) ? '' : ': ' . $snmpData['vlan']['VLANMODE'];
                $this->displayMessage = __('Function is not supported by this OLT') . $vlanMode;
                return (false);
            }
        }
    }

    public function deregForm() {
        $Inputs = wf_SubmitClassed('true', 'vlanButton', 'DeregOnu', __('Deregister onu'));
        $Form = wf_Form("", 'POST', $Inputs);
        return($Form);
    }

}
