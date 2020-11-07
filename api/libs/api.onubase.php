<?php

/**
 * PON ONU management basic class
 */
class OnuBase {

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
     * Placeholder for parsed SNMP template contents
     *
     * @var string
     */
    protected $snmpTemplateParsed = array();

    /**
     * Placeholder for any message to return and/or display
     *
     * @var string
     */
    public $displayMessage = '';


    public function __construct($login = '') {
        if (!empty($login)) {
            $this->snmp = new SNMPHelper;
            $this->loadAlter();
            $this->login = $login;
            $this->getOnuData($login);

            if (empty($this->onuData)) {
                $this->getExtUserOnuData($login);
            }

            if (!empty($this->oltId)) {
                $this->getOltData($this->oltId);
            }

            if (!empty($this->oltData)) {
                $this->getOltModelData($this->oltData['modelid']);
            }

            if (!empty($this->onuData) AND !empty($this->oltData) AND !empty($this->oltSnmptemplate)) {
                $this->parseSNMPTemplate();
            }
        }
    }

    /**
     * load alter.ini config
     *
     * @return void
     */
    protected function loadAlter() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Get onu data mac and olt ID to which onu is linked
     *
     * @param string $login
     */
    protected function getOnuData($login) {
        $query = "SELECT * FROM `pononu` WHERE `login` = '$login'";
        $data = simple_query($query);
        if (!empty($data)) {
            $this->oltId = $data['oltid'];
            $this->onuData = $data;
        }
    }

    /**
     * Get onu data mac and olt ID to which onu is linked for external logins(multiport ONUs)
     *
     * @param string $login
     */
    protected function getExtUserOnuData($login) {
        $query = "SELECT * FROM `pononuextusers` WHERE `login` = '$login'";
        $data = simple_query($query);

        if (!empty($data) and !empty($data['onuid'])) {
            $onuID = $data['onuid'];
            $query = "SELECT * FROM `pononu` WHERE `id` = $onuID";
            $data = simple_query($query);

            if (!empty($data)) {
                $this->oltId = $data['oltid'];
                $this->onuData = $data;
            }
        }
    }

    /**
     * Loads data from table `switches` to $oltData var (filter by OLT switch ID)
     *
     * @param int $oltID
     */
    protected function getOltData($oltID) {
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
    protected function getOltModelData($modelID) {
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
    protected function macHexToDec($macOnu) {
        $string = '';

        if (check_mac_format($macOnu)) {
            $res = array();
            $args = explode(":", $macOnu);

            foreach ($args as $each) {
                $res[] = hexdec($each);
            }

            $string = implode(".", $res);
        }

        return ($string);
    }

    /**
     * Parses SNMP templates and returns it's contents
     *
     * @return array
     */
    protected function parseSNMPTemplate($templateName = '') {
        $snmpData = array();
        $template = (empty($templateName)) ? $this->oltSnmptemplate : $templateName;

        if (file_exists(CONFIG_PATH . "/snmptemplates/" . $template)) {
            $snmpData = rcms_parse_ini_file(CONFIG_PATH . "/snmptemplates/" . $this->oltSnmptemplate, true);
            $this->snmpTemplateParsed = $snmpData;
        }

        return ($snmpData);
    }

    /**
     * Checks for essential SNMP options in template for BDCOMs
     *
     * @param array $parsedTemplate
     *
     * @return bool
     */
    protected function checkBDCOMEssentialOpts($parsedTemplate = array()) {
        $checkPassed = true;
        $snmpData = (empty($parsedTemplate)) ? $this->snmpTemplateParsed : $parsedTemplate;

        if (!empty($snmpData)) {
            if (!isset($snmpData['vlan']['VLANMODE'])) {
                $checkPassed = false;
            }

            if (!isset($snmpData['vlan']['SAVE'])) {
                $checkPassed = false;
            }

            if (!isset($snmpData['onu']['DESCRIPTION'])) {
                $checkPassed = false;
            }

            if (!isset($snmpData['onu']['RELOAD'])) {
                $checkPassed = false;
            }

            if (!isset($snmpData['onu']['EPONINDEX'])) {
                $checkPassed = false;
            }

            if (!isset($snmpData['onu']['IFINDEX'])) {
                $checkPassed = false;
            }
        }

        return ($checkPassed);
    }

    /**
     * Getter for onuData property
     *
     * @return array
     */
    public function getDataONU() {
        return ($this->onuData);
    }

    /**
     * Getter for oltData property
     *
     * @return array
     */
    public function getDataOLT() {
        return ($this->oltData);
    }
}
