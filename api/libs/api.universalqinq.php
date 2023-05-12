<?php

/**
 * Yet another QinQ implementation.
 */
class UniversalQINQ {

    CONST MODULE = '?module=universalqinq';
    CONST MODULE_VLANMANAGEMENT = '?module=vlanmanagement';

    /**
     * Placeholder for nyan orm (`qinq` table)
     * 
     * @var object
     */
    protected $qinqdb;

    /**
     * contains erros if any
     * 
     * @var array
     */
    public $error = array();

    /**
     * Contains all the cathable exceptions
     * 
     * @var array
     */
    public $exceptions = array();

    /**
     * Contains all 
     * 
     * @var array
     */
    protected $allData;

    /**
     * Containts all C-vlans
     * 
     * @var array
     */
    protected $allCvlans = array();

    /**
     * Contains all realms
     * 
     * @var array
     */
    protected $allRealms = array();

    /**
     * Contains all S-vlans
     * 
     * @var array
     */
    protected $allSvlan = array();

    /**
     * Placeholder for nyan_orm instance for switches_qinq table.
     * 
     * @var object
     */
    protected $switchesqinqDb;

    /**
     * Placeholder for nyan_orm isntance for switches table.
     * 
     * @var object
     */
    protected $switchesDb;

    /**
     * Placeholder for nyan_orm instance for switchmodels table.
     * 
     * @var object
     */
    protected $switchModelsDb;

    /**
     * Contains all switches
     * 
     * @var array
     */
    protected $allSwitches = array();

    /**
     * Contains all switch models.
     * 
     * @var array
     */
    protected $allSwitchModels = array();

    /**
     * Contains all c-vlans occupied by switches.
     * 
     * @var array
     */
    protected $occupiedSwitches = array();

    /**
     * Contains system alter config as key=>value
     * 
     * @var array
     */
    protected $altCfg;

    /**
     * Placeholder for nyan_orm instance for realms table.
     * 
     * @var object
     */
    protected $realmsdb;

    /**
     * Placeholder for nyan_orm instance for svlan_qinq table.
     * 
     * @var object
     */
    protected $svlandb;

    /**
     * Default realm selector
     * 
     * @var string
     */
    protected $defaultRealm;

    /**
     * Placeholder for ubilling messages instance.
     * 
     * @var object
     */
    protected $messages;

    /**
     * Placeholder for ubRouting object
     * 
     * @var object
     */
    public $routing;

    public function __construct() {
        $this->qinqdb = new nya_qinq_bindings;
        $this->realmsdb = new nya_realms();
        $this->svlandb = new nya_qinq_svlan();
        $this->switchesqinqDb = new nya_switches_qinq();
        $this->switchesDb = new nya_switches();
        $this->switchModelsDb = new nya_switchmodels();
        $this->loadAlter();
        $this->initRouting();
        $this->loadData();
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Loads system alter.ini config for further usage
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadAlter() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Creates new protected routing object instance for further usage
     * 
     * @return void
     */
    protected function initRouting() {
        $this->routing = new ubRouting();
    }

    /**
     * Function to preload data from qinq_bindings, realms and qinq_svlan tables.
     * 
     * @return void
     */
    protected function loadData() {
        $this->allData = $this->qinqdb->getAll('id');
        $this->allRealms = $this->realmsdb->getAll('id');
        $this->allSvlan = $this->svlandb->getAll('id');
    }

    /**
     * Redirects user back and show error if any
     * 
     * @return void
     */
    protected function goToStartOrError() {
        if (empty($this->error) and empty($this->exceptions)) {
            if ($this->routing->checkGet('type')) {
                rcms_redirect(VlanManagement::MODULE . '&realm_id=' . $this->routing->get('realm_id', 'int') . '&svlan_id=' . $this->routing->get('svlan_id', 'int'));
            } else {
                rcms_redirect(self::MODULE);
            }
        }
        if (!$this->routing->checkGet('type')) {
            if (!empty($this->error)) {
                $this->showError();
            }
            if (!empty($this->exceptions)) {
                $this->showExceptions();
            }
        }
    }

    /**
     * Generate dynamic selector for realms.
     * 
     * @param string $container
     * @param int $realm
     * @param int $svlan
     * 
     * @return string
     */
    protected function realmsSelector($container = '', $realm = '', $svlan = '') {
        $this->realmSelector[self::MODULE . '&action=realm_id_select&ajrealmid=0'] = '---';

        if (!empty($this->allRealms)) {
            foreach ($this->allRealms as $id => $each) {
                $this->realmSelector[self::MODULE . '&action=realm_id_select&ajrealmid=' . $id . '&svlan_id=' . $svlan] = $each['realm'] . ' | ' . $each['description'];
            }

            $this->defaultRealm = self::MODULE . '&action=realm_id_select&ajrealmid=' . $realm . '&svlan_id=' . $svlan;
        }

        return(wf_AjaxSelectorAC($container, $this->realmSelector, __('Select realm'), $this->defaultRealm, false));
    }

    /**
     * Generates dynamic svlan selector.
     * 
     * @param int $altRealm
     * @param int $altSvlan
     * 
     * @return string
     */
    public function svlanSelector($altRealm = '', $altSvlan = '') {
        if ($this->routing->get('ajrealmid', 'int')) {
            $realm = $this->routing->get('ajrealmid', 'int');
        } else {
            $realm = $altRealm;
        }
        if ($this->routing->get('svlan_id')) {
            $svlan = $this->routing->get('svlan_id');
        } else {
            $svlan = $altSvlan;
        }
        $this->svlandb->where('realm_id', '=', $realm);
        $allSvlan = $this->svlandb->getAll('id');
        $allSvlanSelector = array('' => '---');
        if (!empty($allSvlan)) {
            foreach ($allSvlan as $id => $each) {
                $allSvlanSelector[$id] = $each['svlan'] . ' | ' . $each['description'];
            }
        }
        $result = wf_HiddenInput('module', 'universalqinq');
        $result .= wf_HiddenInput('realm_id', $realm);
        $result .= wf_Selector('svlan_id', $allSvlanSelector, 'SVLAN', $svlan, false);

        return ($result);
    }

    /**
     * Check if cvlan is int and has value from 1 to 4096
     * 
     * @return bool
     */
    protected function validateCvlan() {
        if (($this->routing->get('cvlan_num', 'int') >= 1) and ( $this->routing->get('cvlan_num', 'int') <= 4096)) {
            return(true);
        } else {
            return(false);
        }
    }

    /**
     * Check if svlan_id greated than zero
     * 
     * @return bool
     */
    protected function validateSvlan() {
        if ($this->routing->get('svlan_id', 'int') >= 1) {
            return(true);
        } else {
            return(false);
        }
    }

    /**
     * Check if qinq pair is not occupied by switch.
     * 
     * @return bool
     */
    protected function isSwitchCvlanUnique() {
        if (isset($this->occupiedSwitches[$this->routing->get('cvlan_num', 'int')])) {
            return(false);
        }

        return(true);
    }

    /**
     * Check if qinq pair is not occupied by customer.
     * 
     * @return bool
     */
    protected function isUniversalCvlanUnique() {
        if (isset($this->allCvlans[$this->routing->get('cvlan_num', 'int')])) {
            return(false);
        }

        return(true);
    }

    /**
     * Should we give a chance to assign vlan to non existing user?
     * 
     * @return bool
     */
    public function isUserExists() {
        if ($this->altCfg['UNIVERSAL_QINQ_USER_EXIST']) {
            $allUsers = array_flip(zb_UserGetAllStargazerLogins());
            if ($this->routing->checkGet('username')) {
                $getLogin = 'username';
            } else {
                $getLogin = 'login';
            }
            if (isset($allUsers[$this->routing->get($getLogin, 'mres')])) {
                return(true);
            } else {
                return(false);
            }
        } else {
            return(true);
        }
    }

    /**
     * User might have only one entry
     * 
     * @return bool
     */
    protected function isUserUnique() {
        if ($this->routing->get('action') == 'edit') {
            $this->qinqdb->where('login', '<>', $this->routing->get('login', 'mres'));
        }
        $data = $this->qinqdb->getAll('login');
        if (isset($data[$this->routing->get('login', 'mres')])) {
            return(false);
        } else {
            return(true);
        }
    }

    /**
     * Get all occupied c-vlans in current svlan.
     * 
     * @return void
     */
    protected function occupiedUniversal() {
        $this->qinqdb->where('svlan_id', '=', $this->routing->get('svlan_id', 'int'));
        $this->allCvlans = $this->qinqdb->getAll('cvlan');
    }

    /**
     * Get all c-vlans occupied by switches.
     * 
     * @return void
     */
    protected function occupiedSwitches() {
        $this->allSwitches = $this->switchesDb->getAll('id');
        $this->allSwitchModels = $this->switchModelsDb->getAll('id');
        $this->switchesqinqDb->where('svlan_id', '=', $this->routing->get('svlan_id', 'int'));
        foreach ($this->switchesqinqDb->getAll('switchid') as $io => $each) {
            if (isset($this->allSwitches[$each['switchid']])) {
                $modelid = $this->allSwitches[$each['switchid']]['modelid'];
                $port_number = $this->allSwitchModels[$modelid]['ports'];
                for ($i = $each['cvlan']; $i <= ($each['cvlan'] + $port_number - 1); $i++) {
                    $this->occupiedSwitches[$i] = $this->allSwitches[$each['switchid']]['ip'] . ' | ' . $this->allSwitches[$each['switchid']]['location'];
                    $this->switchVlans[$i] = $each['switchid'];
                }
            }
        }
    }

    /**
     * Check all validation function and return error if something didn't pass
     * 
     * @return bool
     */
    protected function validator() {
        if (!$this->validateCvlan()) {
            $this->error[] = __('Wrong value') . ' CVLAN ' . $this->routing->get('cvlan_num', 'int');
        }
        if (!$this->validateSvlan()) {
            $this->error[] = __('Wrong value') . ' SVLAN ID ' . $this->routing->get('svlan_id', 'int');
        }
        if (!$this->isUniversalCvlanUnique()) {
            $this->error[] = "CVLAN " . $this->routing->get('cvlan_num', 'int')
                    . ' ' . __('occcupied by login') . ': '
                    . wf_link("?module=userprofile&username="
                            . $this->occupiedUniversal[$this->routing->get('cvlan_num', 'int')]['login'], $this->occupiedUniversal[$this->routing->get('cvlan_num', 'int')]['login']
            );
        }
        if (!$this->isSwitchCvlanUnique()) {
            $this->error[] = "CVLAN " . $this->routing->get('cvlan_num', 'int')
                    . ' ' . __('occcupied by switch') . ': '
                    . $this->occupiedSwitches[$this->routing->get('cvlan_num', 'int')];
        }

        if (!$this->routing->get('login')) {
            $this->error[] = __('Login cannot be empty');
        } else if (!$this->isUserExists()) {
            $this->error[] = __('User does not exist') . ' : ' . $this->routing->get('login', 'mres');
        }
        if (!$this->isUserUnique()) {
            $this->error[] = __('There is entry for this login') . ' : ' . $this->routing->get('login', 'mres');
        }

        if (!empty($this->error)) {
            return(false);
        }
        return(true);
    }

    /**
     * Adding new entry
     * 
     * @return void
     */
    public function add() {
        try {
            if ($this->validator()) {
                $this->qinqdb->data('login', trim($this->routing->get('login', 'mres')));
                $this->qinqdb->data('svlan_id', trim($this->routing->get('svlan_id', 'int')));
                $this->qinqdb->data('cvlan', trim($this->routing->get('cvlan_num', 'int')));
                $this->qinqdb->create();
                $this->logAdd();
            }
            $this->goToStartOrError();
            return($this->error);
        } catch (Exception $ex) {
            $this->exceptions[] = $ex;
            $this->goToStartOrError();
        }
    }

    /**
     * Delete entry
     * 
     * @return void
     */
    public function delete() {
        try {
            $this->qinqdb->where('id', '=', trim($this->routing->get('id', 'int')));
            $this->qinqdb->delete();
            $this->logDelete();
            $this->goToStartOrError();
        } catch (Exception $ex) {
            $this->exceptions[] = $ex;
            $this->goToStartOrError();
        }
    }

    /**
     * Edit entry
     * 
     * @return void
     */
    public function edit() {
        try {
            if ($this->validator()) {
                $this->qinqdb->where('id', '=', trim($this->routing->get('id', 'int')));
                $this->qinqdb->data('login', trim($this->routing->get('login', 'mres')));
                $this->qinqdb->data('svlan_id', trim($this->routing->get('svlan_id', 'int')));
                $this->qinqdb->data('cvlan', trim($this->routing->get('cvlan_num', 'int')));
                $this->qinqdb->save();
                $this->logEdit();
            }
            $this->goToStartOrError();
        } catch (Exception $ex) {
            $this->exceptions[] = $ex;
            $this->goToStartOrError();
        }
    }

    /**
     * Show all the entries
     * 
     * @return string
     */
    public function showAll() {
        $modal = '<link rel="stylesheet" href="./skins/vlanmanagement.css" type="text/css" media="screen" />';
        $modal .= wf_tag('div', false, 'cvmodal', 'id="dialog-modal_cvmodal" title="' . __('Choose') . '" style="display:none; width:1px; height:1px;"');
        $modal .= wf_tag('p', false, '', 'id="content-cvmodal"');
        $modal .= wf_tag('p', true);
        $modal .= wf_tag('div', true);
        $modal .= '<script src="./modules/jsc/vlanmanagement.js" type="text/javascript"></script>';

        $columns = array(__('ID'), 'Realm', 'S-VLAN', 'C-VLAN', 'Address', 'Real Name', 'Actions');
        $opts = '"order": [[ 0, "desc" ]]';
        $result = '';
        $ajaxURL = '' . self::MODULE . '&ajax=true';
        $result .= show_window('', $modal . wf_JqDtLoader($columns, $ajaxURL, false, 'UniversalQINQ', 100, $opts));
        return ($result);
    }

    public function links() {
        $urls = wf_BackLink(UniversalQINQ::MODULE_VLANMANAGEMENT, __('Back'), false, 'ubButton');
        show_window('', $urls . $this->addForm());
    }

    /**
     * Form to create new entry
     * 
     * @return void
     */
    protected function addForm() {
        $result = wf_AjaxLoader();
        $inputs2 = wf_HiddenInput('module', 'universalqinq');
        $inputs2 .= wf_HiddenInput('action', 'add');
        $inputs2 .= $this->realmsSelector('ajcontainer');
        $inputs2 .= wf_AjaxContainer('ajcontainer', '', $this->svlanSelector());
        $inputs2 .= wf_TextInput('cvlan_num', 'C-VLAN', '', true, '', 'digits');
        $inputs2 .= wf_TextInput('login', __('Login'), '', true, '', '');
        $inputs2 .= wf_Submit('Save');
        $result .= wf_Form('', 'GET', $inputs2, 'glamour');
        return(wf_modalAuto(web_icon_create() . ' ' . __('Create new entry'), __('Create new entry'), $result, 'ubButton'));
    }

    /**
     * Forming edit form
     * 
     * @param array $each
     * 
     * @return string
     */
    public function editFormGenerator($encode) {
        $decode = unserialize(base64_decode($encode));
        $result = wf_AjaxLoader();
        $addControls = wf_HiddenInput('module', 'universalqinq');
        $addControls .= wf_HiddenInput('action', 'edit');
        $addControls .= wf_HiddenInput('id', $decode['id']);
        $addControls .= $this->realmsSelector('ajcontainer2', $this->allSvlan[$decode['svlan_id']]['realm_id'], $decode['svlan_id']);
        $addControls .= wf_AjaxContainer('ajcontainer2', '', $this->svlanSelector($this->allSvlan[$decode['svlan_id']]['realm_id'], $decode['svlan_id']));
        $addControls .= wf_TextInput('cvlan_num', 'C-VLAN', $decode['cvlan'], true, '', 'digits');
        $addControls .= wf_TextInput('login', __('Login'), $decode['login'], true, '');
        $addControls .= wf_HiddenInput('old_login', $decode['login']);
        $addControls .= wf_HiddenInput('old_svlan', $decode['svlan_id']);
        $addControls .= wf_HiddenInput('old_cvlan', $decode['cvlan']);
        $addControls .= wf_Submit('Save');
        $result .= wf_Form('', 'GET', $addControls, 'glamour');

        return($result);
    }

    /**
     * If we have any errors show all of them
     * 
     * @return void
     */
    protected function showError() {
        foreach ($this->error as $io => $each) {
            show_error($each);
        }
    }

    protected function showExceptions() {
        foreach ($this->exceptions as $io => $each) {
            show_error($each);
        }
    }

    public function getAll() {
        return($this->qinqdb->getAll('login'));
    }

    /**
     * Form all the entries to ajax array
     * 
     * @return void
     */
    public function ajaxData() {
        $json = new wf_JqDtHelper();
        if (!empty($this->allData)) {
            $allRealnames = zb_UserGetAllRealnames();
            $allAddress = zb_AddressGetFulladdresslistCached();
            foreach ($this->allData as $io => $each) {
                $eachId = base64_encode(serialize(array(
                    'id' => $each['id'],
                    'svlan_id' => $each['svlan_id'],
                    'cvlan' => $each['cvlan'],
                    'login' => $each['login']
                )));
                $userLink = wf_Link('?module=userprofile&username=' . $each['login'], web_profile_icon() . ' ' . @$allAddress[$each['login']], false);
                $actLinks = wf_tag('div', false, '', 'id="' . $eachId . '" onclick="qinqEdit(this)" style="display:inline-block;"') . web_edit_icon() . wf_tag('div', true);
                $actLinks .= wf_JSAlert(self::MODULE . '&action=delete&id=' . $each['id'], web_delete_icon(), $this->messages->getDeleteAlert());
                $data[] = $each['id'];
                $data[] = $this->allRealms[$this->allSvlan[$each['svlan_id']]['realm_id']]['description'];
                $data[] = $this->allSvlan[$each['svlan_id']]['svlan'];
                $data[] = $each['cvlan'];
                $data[] = $userLink;
                $data[] = @$allRealnames[$each['login']];
                $data[] = $actLinks;
                $json->addRow($data);
                unset($data);
            }
        }
        $json->getJson();
    }

    /**
     * Log add action
     * 
     * @return void
     */
    public function logAdd($login = '', $svlan = '', $cvlan = '') {
        if (empty($login)) {
            $login = $this->routing->get('login', 'mres');
        }
        if (empty($svlan)) {
            $svlan = $this->routing->get('svlan', 'int');
        }
        if (empty($cvlan)) {
            $cvlan = $this->routing->get('cvlan_num', 'int');
        }
        log_register('CREATE universalqinq ('
                . trim($login)
                . ') s'
                . trim($svlan)
                . '/c'
                . trim($cvlan)
        );
    }

    /**
     * Log delete action
     * 
     * @return void
     */
    protected function logDelete() {
        log_register('DELETE universalqinq (' .
                trim($this->routing->get('login', 'mres')) .
                ') s' .
                trim($this->routing->get('svlan', 'int')) .
                '/c' .
                trim($this->routing->get('cvlan_num', 'int'))
        );
    }

    /**
     * Log edit action
     * 
     * @return void
     */
    protected function logEdit() {
        log_register('EDIT universalqinq ('
                . trim($this->routing->get('old_login', 'mres'))
                . ') FROM s'
                . trim($this->routing->get('old_svlan', 'int'))
                . '/c'
                . trim($this->routing->get('old_cvlan', 'int'))
                . 'ON ('
                . trim($this->routing->get('login', 'mres'))
                . ') s'
                . trim($this->routing->get('svlan', 'int'))
                . '/c'
                . trim($this->routing->get('cvlan_num', 'int'))
        );
    }

}
