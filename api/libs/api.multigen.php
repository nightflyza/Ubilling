<?php

class MultiGen {

    /**
     * Contains system alter.ini config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains all stargazer user data
     *
     * @var array
     */
    protected $allUserData = array();

    /**
     * Contains existing users NAS bindings
     *
     * @var array
     */
    protected $userNases = array();

    /**
     * Contains available networks as id=>data
     *
     * @var array
     */
    protected $allNetworks = array();

    /**
     * Contains available NAS servers as id=>data
     *
     * @var array
     */
    protected $allNas = array();

    /**
     * Contains array of NASes served networks as netid=>nasids array
     *
     * @var array
     */
    protected $networkNases = array();

    /**
     * Contains available nethosts as ip=>data
     *
     * @var array
     */
    protected $allNetHosts = array();

    /**
     * Contains array of nethosts to networks bindings like ip=>netid
     *
     * @var array
     */
    protected $nethostsNetworks = array();

    /**
     * Contains list of available NAS attributes presets to generate as id=>data
     *
     * @var array
     */
    protected $nasAttributes = array();

    /**
     * Contains multigen NAS options like usernames types etc as nasid=>options
     *
     * @var array
     */
    protected $nasOptions = array();

    /**
     * System message helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Contains available username types as type=>name
     *
     * @var array
     */
    protected $usernameTypes = array();

    /**
     * Contains available nas service handlers as type=>name
     *
     * @var array
     */
    protected $serviceTypes = array();

    /**
     * Contains available operators as operator=>name
     *
     * @var array
     */
    protected $operators = array();

    /**
     * Contains available reply scenarios
     *
     * @var array
     */
    protected $scenarios = array();

    /**
     * Contains basic module path
     */
    const URL_ME = '?module=multigen';

    /**
     * Default scenario tables prefix
     */
    const SCENARIO_PREFIX = 'mg_';
    

    /**
     * Creates new MultiGen instance
     * 
     * @return void
     */
    public function __construct() {
        $this->loadConfigs();
        $this->setOptions();
        $this->initMessages();
        $this->loadNetworks();
        $this->loadNases();
        $this->loadNasAttributes();
        $this->loadNasOptions();
        $this->loadNethosts();
        $this->loadUserData();
        $this->preprocessUserData();
        $this->loadScenarios();
    }

    /**
     * Loads reqired configss
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfigs() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Sets some basic options for further usage
     * 
     * @return void
     */
    protected function setOptions() {
        $this->usernameTypes = array(
            'login' => __('Login'),
            'ip' => __('IP'),
            'mac' => __('MAC')
        );

        $this->serviceTypes = array(
            'none' => __('No'),
            'coa' => __('CoA'),
            'pod' => __('PoD')
        );

        $this->scenarios = array(
            'check' => 'check',
            'reply' => 'reply'
        );

        $this->operators = array(
            '=' => '=',
            ':=' => ':=',
            '==' => '==',
            '+=' => '+=',
            '!=' => '!=',
            '>' => '>',
            '>=' => '>=',
            '<=' => '<=',
            '=~' => '=~',
            '!~' => '!~',
            '=*' => '=*',
            '!*' => '!*',
        );
    }

    /**
     * Inits system message helper for further usage
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Loads all existing users data from database
     * 
     * @return void
     */
    protected function loadUserData() {
        $this->allUserData = zb_UserGetAllData();
    }

    /**
     * Prepares user to NAS bindings array
     * 
     * @return void
     */
    protected function preprocessUserData() {
        if (!empty($this->allUserData)) {
            foreach ($this->allUserData as $io => $each) {
                $userIP = $each['ip'];
                if (isset($this->nethostsNetworks[$userIP])) {
                    if (isset($this->networkNases[$this->nethostsNetworks[$userIP]])) {
                        $userNases = $this->networkNases[$this->nethostsNetworks[$userIP]];
                        $this->userNases[$each['login']] = $userNases;
                    }
                }
            }
        }
    }

    /**
     * Loads existing networks from database
     * 
     * @return void
     */
    protected function loadNetworks() {
        $networksRaw = multinet_get_all_networks();
        if (!empty($networksRaw)) {
            foreach ($networksRaw as $io => $each) {
                $this->allNetworks[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads existing NAS servers from database
     * 
     * @return void
     */
    protected function loadNases() {
        $nasesRaw = zb_NasGetAllData();
        if (!empty($nasesRaw)) {
            foreach ($nasesRaw as $io => $each) {
                $this->allNas[$each['id']] = $each;
                $this->networkNases[$each['netid']][$each['id']] = $each['id'];
            }
        }
    }

    /**
     * Loads existing NAS servers attributes generation optionss
     * 
     * @return void
     */
    protected function loadNasAttributes() {
        $query = "SELECT * from `mg_nasattributes`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->nasAttributes[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads multigen NAS options from database
     * 
     * @return void
     */
    protected function loadNasOptions() {
        $query = "SELECT * from `mg_nasoptions`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->nasOptions[$each['nasid']] = $each;
            }
        }
    }

    /**
     * Loads existing nethosts from database
     * 
     * @return void
     */
    protected function loadNethosts() {
        $query = "SELECT * from `nethosts`";
        $nethostsRaw = simple_queryall($query);
        if (!empty($nethostsRaw)) {
            foreach ($nethostsRaw as $io => $each) {
                $this->allNetHosts[$each['ip']] = $each;
                $this->nethostsNetworks[$each['ip']] = $each['netid'];
            }
        }
    }
    
    /**
     * Loads existing scenarios attributes
     * 
     * @return void
     */
    protected function loadScenarios() {
        if (!empty($this->scenarios)) {
            
        }
    }

    /**
     * Renders NAS options editing form
     * 
     * @param int $nasId
     * 
     * @return string
     */
    public function renderNasOptionsEditForm($nasId) {
        $result = '';
        $nasId = vf($nasId, 3);
        if (isset($this->allNas[$nasId])) {
            $onlyActiveParams = array('1' => __('Yes'), '0' => __('No'));
            $inputs = wf_Selector('editnasusername', $this->usernameTypes, __('Username override'), @$this->nasOptions[$nasId]['usernametype'], false) . ' ';
            $inputs.= wf_Selector('editnasservice', $this->serviceTypes, __('Service'), @$this->nasOptions[$nasId]['service'], false) . ' ';
            $inputs.=wf_Selector('editnasonlyactive', $onlyActiveParams, __('Only active users'), @$this->nasOptions[$nasId]['onlyactive'], false) . ' ';
            $inputs.= wf_HiddenInput('editnasid', $nasId);
            $inputs.=wf_Submit(__('Save'));

            $result.=wf_Form(self::URL_ME . '&editnasoptions=' . $nasId, 'POST', $inputs, 'glamour');
        } else {
            $result.=$this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('NAS not exists'), 'error');
        }
        return ($result);
    }

    /**
     * Checks is NAS basically configured?
     * 
     * @param int $nasId
     * 
     * @return bool
     */
    public function nasHaveOptions($nasId) {
        $result = false;
        $nasId = vf($nasId, 3);
        if (isset($this->allNas[$nasId])) {
            if (isset($this->nasOptions[$nasId])) {
                $result = true;
            }
        }
        return ($result);
    }

    /**
     * Saves NAS basic options
     * 
     * @return void/string on error
     */
    public function saveNasOptions() {
        $result = '';
        if (wf_CheckPost(array('editnasid', 'editnasusername', 'editnasservice'))) {
            $nasId = vf($_POST['editnasid'], 3);
            if (isset($this->allNas[$nasId])) {
                $newUserName = $_POST['editnasusername'];
                $newService = $_POST['editnasservice'];
                $newOnlyActive = $_POST['editnasonlyactive'];
                //some NAS options already exists
                if (isset($this->nasOptions[$nasId])) {
                    $currentNasOptions = $this->nasOptions[$nasId];
                    $currentRecordId = $currentNasOptions['id'];
                    $where = "WHERE `id`='" . $currentRecordId . "'";
                    if ($currentNasOptions['usernametype'] != $newUserName) {
                        simple_update_field('mg_nasoptions', 'usernametype', $newUserName, $where);
                        log_register('MULTIGEN NAS [' . $nasId . '] CHANGE USERNAME `' . $newUserName . '`');
                    }

                    if ($currentNasOptions['service'] != $newService) {
                        simple_update_field('mg_nasoptions', 'service', $newService, $where);
                        log_register('MULTIGEN NAS [' . $nasId . '] CHANGE SERVICE `' . $newService . '`');
                    }

                    if ($currentNasOptions['onlyactive'] != $newOnlyActive) {
                        simple_update_field('mg_nasoptions', 'onlyactive', $newOnlyActive, $where);
                        log_register('MULTIGEN NAS [' . $nasId . '] CHANGE ONLYACTIVE `' . $newOnlyActive . '`');
                    }
                } else {
                    //new NAS options creation
                    $newUserName_f = mysql_real_escape_string($newUserName);
                    $newService_f = mysql_real_escape_string($newService);
                    $newOnlyActive_f = mysql_real_escape_string($newOnlyActive);
                    $quyery = "INSERT INTO `mg_nasoptions` (`id`,`nasid`,`usernametype`,`service`,`onlyactive`) VALUES "
                            . "(NULL,'" . $nasId . "','" . $newUserName_f . "','" . $newService_f . "','" . $newOnlyActive_f . "');";
                    nr_query($quyery);
                    log_register('MULTIGEN NAS [' . $nasId . '] CREATE USERNAME `' . $newUserName . '` SERVICE `' . $newService . '` ONLYAACTIVE `' . $newOnlyActive . '`');
                }
            } else {
                $result.=__('Something went wrong') . ': ' . __('NAS not exists');
            }
        }
        return ($result);
    }

    /**
     * Returns list of attribute presets for some NAS
     * 
     * @param int $nasId
     * 
     * @return array
     */
    protected function getNasAttributes($nasId) {
        $result = array();
        if (!empty($this->nasAttributes)) {
            foreach ($this->nasAttributes as $io => $each) {
                if ($each['nasid'] == $nasId) {
                    $result[$io] = $each;
                }
            }
        }
        return ($result);
    }

    /**
     * Deletes some attribute preset
     * 
     * @param int $attributeId
     * 
     * @return void/string on error
     */
    public function deleteNasAttribute($attributeId) {
        $result = '';
        $attributeId = vf($attributeId, 3);
        if (isset($this->nasAttributes[$attributeId])) {
            $attributeData = $this->nasAttributes[$attributeId];
            $query = "DELETE from `mg_nasattributes` WHERE `id`='" . $attributeId . "';";
            nr_query($query);
            log_register('MULTIGEN ATTRIBUTE `' . $attributeData['attribute'] . '` DELETE [' . $attributeId . ']');
        } else {
            $result.=__('Something went wrong') . ': ' . __('not existing attribute');
        }
        return ($result);
    }

    /**
     * Renders list of available attributes for some NAS
     * 
     * @param int $nasId
     * 
     * @return string
     */
    public function renderNasAttributesList($nasId) {
        $result = '';
        $nasId = vf($nasId, 3);
        if (isset($this->allNas[$nasId])) {
            $currentAtrributes = $this->getNasAttributes($nasId);
            if (!empty($currentAtrributes)) {
                $cells = wf_TableCell(__('Scenario'));
                $cells.= wf_TableCell(__('Attribute'));
                $cells.=wf_TableCell(__('Operator'));
                $cells.= wf_TableCell(__('Value'));
                $cells.= wf_TableCell(__('Actions'));
                $rows = wf_TableRow($cells, 'row1');

                foreach ($currentAtrributes as $io => $each) {
                    $cells = wf_TableCell($each['scenario']);
                    $cells.= wf_TableCell($each['attribute']);
                    $cells.=wf_TableCell($each['operator']);
                    $cells.= wf_TableCell($each['content']);
                    $attributeControls = wf_JSAlert(self::URL_ME . '&editnasoptions=' . $nasId . '&deleteattributeid=' . $each['id'], web_delete_icon(), $this->messages->getDeleteAlert());
                    $cells.= wf_TableCell($attributeControls);
                    $rows.= wf_TableRow($cells, 'row5');
                }

                $result.=wf_TableBody($rows, '100%', 0, 'sortable');
            } else {
                $result.=$this->messages->getStyledMessage(__('Nothing to show'), 'warning');
            }
        } else {
            $result.=$this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('NAS not exists'), 'error');
        }
        return ($result);
    }

    /**
     * Renders NAS attributes editing form
     * 
     * @param int $nasId
     * 
     * @return string
     */
    public function renderNasAttributesEditForm($nasId) {
        $result = '';
        $nasId = vf($nasId, 3);
        if (isset($this->allNas[$nasId])) {
            $inputs = wf_Selector('newscenario', $this->scenarios, __('Scenario'), '', false) . ' ';
            $inputs.= wf_TextInput('newattribute', __('Attribute'), '', false, 20) . ' ';
            $inputs.= wf_Selector('newoperator', $this->operators, __('Operator'), '', false) . ' ';
            $inputs.= wf_TextInput('newcontent', __('Value'), '', false, 20) . ' ';
            $inputs.= wf_HiddenInput('newattributenasid', $nasId);
            $inputs.= wf_Submit(__('Create'));
            //form assembly
            $result.=wf_Form(self::URL_ME . '&editnasoptions=' . $nasId, 'POST', $inputs, 'glamour');
        } else {
            $result.=$this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('NAS not exists'), 'error');
        }
        return ($result);
    }

    /**
     * Creates new NAS attribute preset
     * 
     * 
     * @return void/string on error
     */
    public function createNasAttribute() {
        $result = '';
        if (wf_CheckPost(array('newattributenasid'))) {
            $nasId = vf($_POST['newattributenasid'], 3);
            if (isset($this->allNas[$nasId])) {
                if (wf_CheckPost(array('newscenario', 'newattribute', 'newoperator', 'newcontent'))) {
                    $newScenario = $_POST['newscenario'];
                    $newScenario_f = mysql_real_escape_string($newScenario);
                    $newAttribute = $_POST['newattribute'];
                    $newAttribute_f = mysql_real_escape_string($newAttribute);
                    $newOperator = $_POST['newoperator'];
                    $newOperator_f = mysql_real_escape_string($newOperator);
                    $newContent = $_POST['newcontent'];
                    $newContent_f = mysql_real_escape_string($newContent);

                    $query = "INSERT INTO `mg_nasattributes` (`id`,`nasid`,`scenario`,`attribute`,`operator`,`content`) VALUES "
                            . "(NULL,'" . $nasId . "','" . $newScenario_f . "','" . $newAttribute_f . "','" . $newOperator_f . "','" . $newContent_f . "');";
                    nr_query($query);
                    log_register('MULTIGEN NAS [' . $nasId . '] CREATE ATTRIBUTE `' . $newAttribute . '`');
                }
            } else {
                $result.=__('Something went wrong') . ': ' . __('NAS not exists');
            }
        }
        return ($result);
    }

    /**
     * Performs generation of user attributes if their NAS requires it.
     * 
     * @return void
     */
    public function generateUserAttributes() {
        if (!empty($this->allUserData)) {
            foreach ($this->allUserData as $io => $eachUser) {
                if (isset($this->userNases[$eachUser['login']])) {
                    $userNases = $this->userNases[$eachUser['login']];
                    if (!empty($userNases)) {
                        foreach ($userNases as $eachNasId) {
                            $nasOptions = $this->nasOptions[$eachNasId];
                            $userNameType = $nasOptions['usernametype'];
                            //overriging username type if required
                            switch ($userNameType) {
                                case 'login':
                                    $userName = $eachUser['login'];
                                    break;
                                case 'ip':
                                    $userName = $eachUser['ip'];
                                    break;
                                case 'mac':
                                    $userName = $eachUser['mac'];
                                    break;
                            }

                            if (!empty($nasOptions)) {
                                $nasAttributes = $this->getNasAttributes($eachNasId);
                                if (!empty($nasAttributes)) {
                                    foreach ($nasAttributes as $eachAttributeId => $eachAttributeData) {
                                        $scenario = $eachAttributeData['scenario'];
                                        //TODO
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

}

/**
 * Returns list of available free Juniper NASes
 * 
 * @return string
 */
function web_MultigenListClients() {
    $result = __('Nothing found');
    $query = "SELECT * from `mg_clients` GROUP BY `nasname`";
    $all = simple_queryall($query);
    if (!empty($all)) {
        $cells = wf_TableCell(__('IP'));
        $cells.= wf_TableCell(__('NAS name'));
        $cells.= wf_TableCell(__('Radius secret'));
        $rows = wf_TableRow($cells, 'row1');
        foreach ($all as $io => $each) {
            $cells = wf_TableCell($each['nasname']);
            $cells.= wf_TableCell($each['shortname']);
            $cells.= wf_TableCell($each['secret']);
            $rows.= wf_TableRow($cells, 'row3');
        }
        $result = wf_TableBody($rows, '100%', '0', 'sortable');
    }

    return ($result);
}

?>