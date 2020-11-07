<?php
/**
 * Siwtches auth data and management basic class
 */
class SwitchLogin {

    /**
     * Contains all available switch logins
     * 
     * @var array
     */
    protected $switchWithLogin = array();
    protected $switchWithoutLogin = array();
    protected $ipLength = 0;

    /**
     * Contains data for switch selector as switch['id'] => switch['location'] + IP + modelname
     * 
     * @var array
     */
    protected $switchSelector = array();

    /**
     * Contains all snmptemplates in config/autoconfig/
     * 
     * @var array
     */
    protected $allAutoconfigSnmptemplates = array();

    const MODULE = 'SWITCHLOGIN';
    const MODULE_URL = '?module=switchlogin';
    const TABLE_NAME = 'switch_login';
    const PATH = 'config/autoconfig/';

    public function __construct() {
        $this->loadSwitchesWithLogin();
        $this->loadSwitchesWithoutLogin();
        $this->loadAutoconfigSnmp();
    }

    /**
     * Load all switches logins to $switchWithLogin
     * 
     * @return void
     */
    protected function loadSwitchesWithLogin() {
        $query = "SELECT `switches`.`id`,"
                . "`switches`.`ip`,"
                . "`switches`.`desc`,"
                . "`switches`.`location`,"
                . "`switchmodels`.`modelname`,"
                . "`switch_login`.`swlogin`,"
                . "`switch_login`.`swpass`,"
                . "`switch_login`.`method`,"
                . "`switch_login`.`community`,"
                . "`switch_login`.`enable`,"
                . "`switch_login`.`snmptemplate` "
                . "FROM `switches` "
                . "LEFT JOIN `switchmodels` ON `switches`.`modelid`=`switchmodels`.`id` "
                . "LEFT JOIN `switch_login` ON `switches`.`id`=`switch_login`.`swid` "
                . "WHERE `switch_login`.`swid` IS NOT NULL";
        $data = simple_queryall($query);
        if (!empty($data)) {
            foreach ($data as $each) {
                $this->switchWithLogin[$each['id']] = $each;
            }
        }
    }

    protected function loadSwitchesWithoutLogin() {
        $query = "SELECT `switches`.`id`,"
                . "`switches`.`ip`,"
                . "`switches`.`desc`,"
                . "`switches`.`location`,"
                . "`switchmodels`.`modelname`,"
                . "`switch_login`.`swlogin`,"
                . "`switch_login`.`swpass`,"
                . "`switch_login`.`method`,"
                . "`switch_login`.`community`,"
                . "`switch_login`.`enable`,"
                . "`switch_login`.`snmptemplate` "
                . "FROM `switches` "
                . "LEFT JOIN `switchmodels` ON `switches`.`modelid`=`switchmodels`.`id` "
                . "LEFT JOIN `switch_login` ON `switches`.`id`=`switch_login`.`swid` "
                . "WHERE `switch_login`.`swid` IS NULL";
        $data = simple_queryall($query);
        if (!empty($data)) {
            foreach ($data as $each) {
                $this->switchWithoutLogin[$each['id']] = $each;
            }
        }
    }

    protected function alignLength() {
        if (!empty($this->switchWithLogin)) {
            foreach ($this->switchWithLogin as $each) {
                if (strlen($each['ip']) > $this->ipLength) {
                    $this->ipLength = strlen($each['ip']);
                }
            }
        }
        if (!empty($this->switchWithoutLogin)) {
            foreach ($this->switchWithoutLogin as $each) {
                if (strlen($each['ip']) > $this->ipLength) {
                    $this->ipLength = strlen($each['ip']);
                }
            }
        }
    }

    /**
     * Load data from switches, parse it and place to $switchSelector.
     * 
     * @return void
     */
    protected function loadWithLoginSelector() {
        $this->alignLength();
        if (!empty($this->switchWithLogin)) {
            foreach ($this->switchWithLogin as $each) {
                for ($i = strlen($each['ip']); $i <= $this->ipLength; $i++) {
                    $each['ip'] .= "&nbsp;";
                }
                $this->switchSelector[$each['id']] = $each['ip'] . ' - ' . $each['location'];
            }
        }
    }

    /**
     * Load data from switches, parse it and place to $switchSelector.
     * 
     * @return void
     */
    protected function loadWithoutLoginSelector() {
        $this->alignLength();
        if (!empty($this->switchWithoutLogin)) {
            foreach ($this->switchWithoutLogin as $each) {
                $each['modelname'] = strtolower($each['modelname']);
                $each['location'] = strtolower($each['location']);
                for ($i = strlen($each['ip']); $i <= $this->ipLength; $i++) {
                    $each['ip'] .= "&nbsp;";
                }
                $this->switchSelector[$each['id']] = $each['ip'] . ' - ' . $each['location'];
            }
        }
    }

    /**
     * Parse all found files in directory, reads directive ['define']['DEVICE'] and place it to $allAutoconfigSnmptemplates.
     * 
     * @return void
     */
    protected function loadAutoconfigSnmp() {
        $allTemplates = rcms_scandir(self::PATH);
        $templates = array();
        $result = array('' => __('No'));
        if (!empty($allTemplates)) {
            foreach ($allTemplates as $each) {
                $templates[$each] = rcms_parse_ini_file(self::PATH . $each, true);
            }
        }
        if (!empty($templates)) {
            foreach ($templates as $io => $each) {
                $result[$io] = $each['define']['DEVICE'];
            }
        }
        $this->allAutoconfigSnmptemplates = $result;
    }

    /**
     * Shows form for adding new snmp login data for switch
     * 
     * @return string
     */
    public function web_loginAddSnmp() {
        $this->loadWithoutLoginSelector();
        $cell = wf_HiddenInput('add', 'true');
        $cell .= wf_HiddenInput('SwMethod', 'SNMP');
        $cell .= wf_HiddenInput('SwLogin', '');
        $cell .= wf_HiddenInput('SwPass', '');
        $cell .= wf_HiddenInput('Enable', '');
        $cell .= wf_Selector('swmodel', $this->switchSelector, __('Model'), '', true, false, '', 'monospace');
        $cell .= wf_Selector('snmptemplate', $this->allAutoconfigSnmptemplates, __('Template'), '', true, false, '', '');
        $cell .= wf_TextInput('RwCommunity', __('SNMP community'), '', true);        
        $cell .= wf_Submit(__('Add'));
        $Row = wf_TableRow($cell, 'row1');
        $form = wf_Form("", 'POST', $Row, 'glamour');
        die($form);
    }

    /**
     * Shows form for editing existing snmp login for switch
     * 
     * @param int $id
     * 
     * @return string
     */
    public function web_loginEditSnmp($id) {
        $params = $this->switchWithLogin[$id];
        $this->loadWithLoginSelector();
        $this->loadWithoutLoginSelector();
        $cell = wf_HiddenInput('edit', 'true');
        $cell .= wf_HiddenInput('EditConn', 'SNMP');
        $cell .= wf_HiddenInput('EditSwLogin', '');
        $cell .= wf_HiddenInput('EditSwPass', '');
        $cell .= wf_HiddenInput('EditEnable', '');
        $cell .= wf_Selector('swmodel', $this->switchSelector, __('Model'), $params['id'], true, false, '', 'monospace');
        $cell .= wf_Selector('snmptemplate', $this->allAutoconfigSnmptemplates, __('Template'), $params['snmptemplate'], true, false, '', '');
        $cell .= wf_TextInput('EditRwCommunity', __('SNMP community'), $params['community'], true);        
        $cell .= wf_Submit(__('Save'));
        $Row = wf_TableRow($cell, 'row1');
        $form = wf_Form("", 'POST', $Row, 'glamour');
        die($form);
    }

    /**
     * Shows form for adding new ssh\telnet login data for switch
     * 
     * @return string
     */
    public function web_loginAddConn() {
        $this->loadWithoutLoginSelector();
        $conn = array('SSH' => __('SSH'), 'TELNET' => __('TELNET'));
        $enable = array('no' => __('no'), 'yes' => __('yes'));
        $cell = wf_HiddenInput('add', 'true');
        $cell .= wf_HiddenInput('RwCommunity', '');
        $cell .= wf_HiddenInput('snmptemplate', '');
        $cell .= wf_Selector('swmodel', $this->switchSelector, __('Model'), '', true, false, '', 'monospace');
        $cell .= wf_Selector('SwMethod', $conn, __('Connection method'), 'SSH', true, false, '', '');
        $cell .= wf_TextInput('SwLogin', __('Login'), '', true);
        $cell .= wf_TextInput('SwPass', __('Password'), '', true);
        $cell .= wf_Selector('Enable', $enable, __('enable propmpt for cisco,bdcom,etc (should be same as password)'), '', true);        
        $cell .= wf_Submit(__('Add'));
        $form = wf_Form("", 'POST', $cell, 'glamour');
        $result = $form;
        die($result);
    }

    /**
     * Shows form for editing existing ssh\telnet login data for switch
     * 
     * @param int $id
     * 
     * @return string
     */
    public function web_loginEditConn($id) {
        $this->loadWithLoginSelector();
        $this->loadWithoutLoginSelector();
        $params = $this->switchWithLogin[$id];
        $conn = array('SSH' => __('SSH'), 'TELNET' => __('TELNET'));
        $enable = array('no' => __('no'), 'yes' => __('yes'));
        $cell = wf_HiddenInput('edit', 'true');
        $cell .= wf_HiddenInput('EditRwCommunity', '');
        $cell .= wf_HiddenInput('snmptemplate', '');
        $cell .= wf_Selector('swmodel', $this->switchSelector, __('Model'), $params['id'], true, false, '', 'monospace');
        $cell .= wf_Selector('EditConn', $conn, __('Connection method'), $params['method'], true, false, '', '');
        $cell .= wf_TextInput('EditSwLogin', __('Login'), $params['swlogin'], true);
        $cell .= wf_TextInput('EditSwPass', __('Password'), $params['swpass'], true);
        $cell .= wf_Selector('EditEnable', $enable, __('enable propmpt for cisco,bdcom,etc (should be same as password)'), $params['enable'], true);        
        $cell .= wf_Submit(__('Save'));
        $form = wf_Form("", 'POST', $cell, 'glamour');
        $result = $form;
        die($result);
    }

    /**
     * Adding login data for switch to database
     * 
     * @param int $swmodel
     * @param string $login
     * @param string $pass
     * @param string $method
     * @param string $community
     * @param int $enable
     * 
     * @return void
     */
    public function SwLoginAdd($SwModel, $SwLogin, $SwPass, $Method, $Community, $Enable, $snmpTemplate) {
        $SwLogin = vf(trim($SwLogin));
        $SwPass = vf(trim($SwPass));
        $Community = vf(trim($Community));
        $query = "INSERT INTO " . self::TABLE_NAME . " (`id`, `swid`, `swlogin`, `swpass`, `method`, `community`, `enable`, `snmptemplate`)
        VALUES (NULL, '" . $SwModel . "', '" . $SwLogin . "', '" . $SwPass . "', '" . $Method . "', '" . $Community . "', '" . $Enable . "', '" . $snmpTemplate . "')";
        nr_query($query);
        log_register('ADD Switch login `' . $SwModel . '`');
    }

    /**
     * Editing existing login data for switch in database
     * 
     * @param type $SwModel
     * @param string $SwLogin
     * @param string $SwPass
     * @param string $Conn
     * @param string $Community
     * @param int $Enable
     * @param int $id
     * 
     * @return void
     */
    public function SwLoginEditQuery($SwModel, $SwLogin, $SwPass, $Method, $Community, $Enable, $snmpTemplate, $id) {
        simple_update_field(self::TABLE_NAME, 'swid', $SwModel, "WHERE `id`='" . $id . "'");
        simple_update_field(self::TABLE_NAME, 'swlogin', trim(vf($SwLogin)), "WHERE `id`='" . $id . "'");
        simple_update_field(self::TABLE_NAME, 'swpass', trim(vf($SwPass)), "WHERE `id`='" . $id . "'");
        simple_update_field(self::TABLE_NAME, 'method', $Method, "WHERE `id`='" . $id . "'");
        simple_update_field(self::TABLE_NAME, 'community', trim(vf($Community)), "WHERE `id`='" . $id . "'");
        simple_update_field(self::TABLE_NAME, 'enable', $Enable, "WHERE `id`='" . $id . "'");
        simple_update_field(self::TABLE_NAME, 'snmptemplate', $snmpTemplate, "WHERE `id`='" . $id . "'");
        log_register('MODIFY Switch login [' . $SwModel . ']');
    }

    /**
     * Show all availables switch logins
     * 
     * @return string
     */
    public function ShowSwAllLogin() {
        $this->loadSwitchesWithLogin();
        $this->loadWithLoginSelector();
        $tablecells = wf_TableCell(__('ID'));
        $tablecells .= wf_TableCell(__('Model'));
        $tablecells .= wf_TableCell(__('Username'));
        $tablecells .= wf_TableCell(__('Password'));
        $tablecells .= wf_TableCell(__('Method'));
        $tablecells .= wf_TableCell(__('SNMP community'));
        $tablecells .= wf_TableCell(__('enable function'));
        $tablecells .= wf_TableCell(__('SNMP template'));
        $tablecells .= wf_TableCell(__('Actions'));
        $tablerows = wf_TableRow($tablecells, 'row1');
        foreach ($this->switchWithLogin as $login) {
            $tablecells = wf_TableCell($login['id']);
            $tablecells .= wf_TableCell($this->switchSelector[$login['id']]);
            $tablecells .= wf_TableCell($login['swlogin']);
            $tablecells .= wf_TableCell($login['swpass']);
            $tablecells .= wf_TableCell($login['method']);
            $tablecells .= wf_TableCell($login['community']);
            $tablecells .= wf_TableCell($login['enable']);
            $tablecells .= wf_TableCell($login['snmptemplate']);
            $actionlinks = wf_JSAlert(self::MODULE_URL . '&delete=' . $login['id'], web_delete_icon(), 'Removing this may lead to irreparable results');
            $actionlinks .= wf_Link(self::MODULE_URL . '&edit=' . $login['id'], web_edit_icon(), false);
            $tablecells .= wf_TableCell($actionlinks);
            $tablerows .= wf_TableRow($tablecells, 'row3');
        }
        $result = wf_TableBody($tablerows, '100%', '0', 'sortable');
        $result .= wf_delimiter();
        show_window(__('Available switches login data'), $result);
    }

    /**
     * Delete login data for switch from database
     * 
     * @param int $id
     * 
     * @return void
     */
    public function SwLoginDelete($id) {
        $id = vf($id);
        $query = "DELETE FROM `switch_login` WHERE `swid`='" . $id . "'";
        nr_query($query);
        log_register('DELETE Switch Login [' . $id . ']');
    }

}
