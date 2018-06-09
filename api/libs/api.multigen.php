<?php

class MultiGen {

    /**
     * Contains system alter.ini config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

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
     * Contains array of NASes served networks as netid=>nas
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
     * Contains list of available NAS attributes presets to generate
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
     * Contains basic module path
     */
    const URL_ME = '?module=multigen';

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
            'coa' => __('COA'),
            'pod' => __('POD')
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
                $this->networkNases[$each['netid']] = $each['id'];
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
            $inputs = wf_Selector('editnasusername', $this->usernameTypes, __('Username override'), @$this->nasOptions[$nasId]['usernametype'], false) . ' ';
            $inputs.= wf_Selector('editnasservice', $this->serviceTypes, __('Service'), @$this->nasOptions[$nasId]['service'], false) . ' ';
            $inputs.= wf_HiddenInput('editnasid', $nasId);
            $inputs.=wf_Submit(__('Save'));

            $result.=wf_Form(self::URL_ME . '&editnasoptions=' . $nasId, 'POST', $inputs, 'glamour');
        } else {
            $result.=$this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('NAS not exists'), 'error');
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
                } else {
                    //new NAS options creation
                    $newUserName_f = mysql_real_escape_string($newUserName);
                    $newService_f = mysql_real_escape_string($newService);
                    $quyery = "INSERT INTO `mg_nasoptions` (`id`,`nasid`,`usernametype`,`service`) VALUES "
                            . "(NULL,'" . $nasId . "','" . $newUserName_f . "','" . $newService_f . "');";
                    nr_query($quyery);
                    log_register('MULTIGEN NAS [' . $nasId . '] CREATE USERNAME `' . $newUserName . '` SERVICE `' . $newService . '`');
                }
            } else {
                $result.=__('Something went wrong') . ': ' . __('NAS not exists');
            }
        }
        return ($result);
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