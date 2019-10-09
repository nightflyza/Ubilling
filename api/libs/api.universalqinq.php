<?php

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
    protected $allCvlans = array();
    protected $allRealms;
    protected $allSvlan;
    protected $switchesqinqDb;
    protected $switchesDb;
    protected $switchModelsDb;
    protected $allSwitches = array();
    protected $allSwitchModels = array();
    protected $occupiedSwitches = array();

    /**
     * Contains system alter config as key=>value
     * 
     * @var array
     */
    protected $altCfg;
    protected $realmsdb;
    protected $svlandb;

    /**
     * Placeholder for ubRouting object
     * 
     * @var object
     */
    public $routing;

    public function __construct() {
        $this->qinqdb = new nya_qinq_bindings;
        $this->realmsdb = new NyanORM('realms');
        $this->svlandb = new NyanORM('qinq_svlan');
        $this->switchesqinqDb = new NyanORM('switches_qinq');
        $this->switchesDb = new NyanORM('switches');
        $this->switchModelsDb = new NyanORM('switchmodels');
        $this->loadAlter();
        $this->initRouting();
        $this->loadData();
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
        if (empty($this->error)) {
            if ($this->routing->checkGet('type')) {
                rcms_redirect(VlanManagement::MODULE . '&realm_id=' . $this->routing->get('realm_id', 'int') . '&svlan_id=' . $this->routing->get('svlan_id', 'int'));
            } else {
                rcms_redirect(self::MODULE);
            }
        } else {
            $this->showError();
            if (!empty($this->exceptions)) {
                $this->showExceptions();
            }
        }
    }

    protected function realmsSelector() {
        if (!empty($this->allRealms)) {
            foreach ($this->allRealms as $id => $each) {
                $this->realmSelector[self::MODULE . '&action=realm_id_select&ajrealmid=' . $id] = $each['realm'] . ' | ' . $each['description'];
            }

            reset($this->allRealms);
            $this->defaultRealm = key($this->allRealms);
        }

        return(wf_AjaxSelectorAC('ajcontainer', $this->realmSelector, __('Select realm'), self::MODULE . '&action=realm_id_select&ajrealmid=' . $this->routing->get('realm_id', 'int'), false));
    }

    public function svlanSelector($realmId) {
        $realmId = vf($realmId, 3);
        $this->svlandb->where('realm_id', '=', $realmId);
        $allSvlan = $this->svlandb->getAll('id');
        $allSvlanSelector = array('' => '---');
        if (!empty($allSvlan)) {
            foreach ($allSvlan as $id => $each) {
                $allSvlanSelector[$id] = $each['svlan'] . ' | ' . $each['description'];
            }
        }
        $result = wf_HiddenInput('module', 'universalqinq');
        $result .= wf_HiddenInput('realm_id', $realmId);
        $result .= wf_Selector('svlan_id', $allSvlanSelector, 'SVLAN', $this->routing->get('svlan_id'), false);

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

    protected function isSwitchCvlanUnique() {
        if (isset($this->occupiedSwitches[$this->routing->get('cvlan_num', 'int')])) {
            return(false);
        }

        return(true);
    }

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
    protected function isUserExists() {
        if ($this->altCfg['UNIVERSAL_QINQ_USER_EXIST']) {
            $allUsers = array_flip(zb_UserGetAllStargazerLogins());
            if (isset($allUsers[$this->routing->get('login', 'mres')])) {
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
        $data = $this->qinqdb->getAll('login');
        if (isset($data[$this->routing->get('login', 'mres')])) {
            return(false);
        } else {
            return(true);
        }
    }

    protected function occupiedUniversal() {
        $this->qinqdb->where('svlan_id', '=', $this->routing->get('svlan_id', 'int'));
        $this->allCvlans = $this->qinqdb->getAll('cvlan');
    }

    protected function occupiedSwitches() {
        $this->allSwitches = $this->switchesDb->getAll('id');
        $this->allSwitchModels = $this->switchModelsDb->getAll('id');
        $this->switchesqinqDb->where('svlan_id', '=', $this->routing->get('svlan_id', 'int'));
        foreach ($this->switchesqinqDb->getAll('switchid') as $io => $each) {
            $modelid = $this->allSwitches[$each['switchid']]['modelid'];
            $port_number = $this->allSwitchModels[$modelid]['ports'];
            for ($i = $each['cvlan']; $i <= $each['cvlan'] + $port_number; $i++) {
                $this->occupiedSwitches[$i] = $this->allSwitches[$each['switchid']]['ip'] . ' | ' . $this->allSwitches[$each['switchid']]['location'];
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
        if (!$this->isUniversalCvlanUnique()) {
            $this->error[] = "CVLAN " . $this->routing->get('cvlan_num', 'int')
                    . ' ' . __('occcupied by login: ')
                    . wf_link("?module=userprofile&username="
                            . $this->occupiedUniversal[$this->routing->get('cvlan_num', 'int')]['login'], $this->occupiedUniversal[$this->routing->get('cvlan_num', 'int')]['login']
            );
        }
        if (!$this->isSwitchCvlanUnique()) {
            $this->error[] = "CVLAN " . $this->routing->get('cvlan_num', 'int')
                    . ' ' . __('occcupied by switch: ')
                    . $this->occupiedSwitches[$this->routing->get('cvlan_num', 'int')];
        }
        if (!$this->isUserExists()) {
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
            if ($this->validator('add')) {
                $this->qinqdb->data('login', trim($this->routing->get('login', 'mres')));
                $this->qinqdb->data('svlan_id', trim($this->routing->get('svlan_id', 'int')));
                $this->qinqdb->data('cvlan', trim($this->routing->get('cvlan_num', 'int')));
                $this->qinqdb->create();
                $this->logAdd();
            }
            $this->goToStartOrError();
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
                $this->qinqdb->data('svlan', trim($this->routing->get('svlan', 'int')));
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
        $columns = array(__('ID'), 'Realm', 'S-VLAN', 'C-VLAN', 'Address', 'Real Name', 'Actions');
        $opts = '"order": [[ 0, "desc" ]]';
        $result = '';
        $ajaxURL = '' . self::MODULE . '&ajax=true';
        $result .= show_window('', wf_JqDtLoader($columns, $ajaxURL, false, 'UniversalQINQ', 100, $opts));
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
        $inputs = '';
        $result = wf_AjaxLoader();
        $inputs2 = wf_HiddenInput('module', 'universalqinq');
        $inputs2 .= wf_HiddenInput('action', 'add');
        $inputs2 .= $this->realmsSelector();
        $inputs2 .= wf_AjaxContainer('ajcontainer', '', $this->svlanSelector($this->routing->get('realm_id', 'int') ? $this->routing->get('realm_id', 'int') : $this->defaultRealm));
        $inputs2 .= wf_TextInput('cvlan_num', 'C-VLAN', '', true, '', 'digits');
        $inputs2 .= wf_TextInput('login', __('Login'), '', true, '', '');
        $inputs2 .= wf_Submit('Save');
        $result .= $inputs . wf_Form('', 'GET', $inputs2, 'glamour');
        return(wf_modalAuto(web_icon_create() . ' ' . __('Create new entry'), __('Create new entry'), $result, 'ubButton'));
    }

    /**
     * Forming edit form
     * 
     * @param array $each
     * 
     * @return string
     */
    protected function editFormGenerator($each) {
        $addControls = wf_HiddenInput('module', 'universalqinq');
        $addControls .= wf_HiddenInput('action', 'edit');
        $addControls .= wf_HiddenInput('id', $each['id']);
        $addControls .= wf_TextInput('login', __('Login'), $each['login'], true, '');
        $addControls .= wf_TextInput('svlan', 'S-VLAN', $each['svlan_id'], true, '', 'digits');
        $addControls .= wf_TextInput('cvlan_num', 'C-VLAN', $each['cvlan'], true, '', 'digits');
        $addControls .= wf_HiddenInput('old_login', $each['login']);
        $addControls .= wf_HiddenInput('old_svlan', $each['svlan_id']);
        $addControls .= wf_HiddenInput('old_cvlan', $each['cvlan']);
        $addControls .= wf_Submit('Save');
        $form = wf_Form('', 'GET', $addControls, 'glamour');

        return($form);
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
                $userLink = wf_Link('?module=userprofile&username=' . $each['login'], web_profile_icon() . ' ' . @$allAddress[$each['login']], false);
                $actLinks = wf_modalAuto(web_edit_icon(), __('Edit'), $this->editFormGenerator($each), '');
                $actLinks .= wf_Link(self::MODULE . '&action=delete&id=' . $each['id'], web_delete_icon(), false);
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
    protected function logAdd() {
        log_register('CREATE universalqinq ('
                . trim($this->routing->get('login', 'mres'))
                . ') s'
                . trim($this->routing->get('svlan', 'int'))
                . '/c'
                . trim($this->routing->get('cvlan_num', 'int'))
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
