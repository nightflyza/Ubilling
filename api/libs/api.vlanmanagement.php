<?php

/**
 * Like IPAM for VLAN
 */
class VlanManagement {

    const MODULE = '?module=vlanmanagement';
    const MODULE_SVLAN = '?module=vlanmanagement&svlan=true';
    const MODULE_REALMS = '?module=vlanmanagement&realms=true';
    const MODULE_UNIVERSALQINQ = '?module=universalqinq';
    const EMPTY_SELECTOR_OPTION = '---';
    const ARRAY_RANGE_STEP = 1;
    const ARRAY_RANGE_START = 1;
    const QINQ_OPTION = 'QINQ_ENABLED';
    const QINQ_LABEL = 'QINQ for switches';
    const ONUREG_QINQ_OPTION = 'ONUREG_QINQ_ENABLED';
    const UNIVERSAL_QINQ_OPTION = 'UNIVERSAL_QINQ_ENABLED';
    const UNIVERSAL_QINQ_RGHT = 'UNIVERSALQINQCONFIG';
    const UNIVERSAL_QINQ_LABEL = 'Universal QINQ';
    const DEFAULT_SVLAN = 0;
    const DEFAULT_REALM = 1;

    /**
     * Routing URL.
     * 
     * @var string
     */
    protected $startSvlanUrl = '';

    /**
     * Routing URL.
     * 
     * @var string
     */
    protected $startManagementUrl = '';

    /**
     * Placeholder for nyan_orm instance for realms table.
     * 
     * @var object
     */
    protected $realmDb;

    /**
     * Placeholder for nyan_orm instance for qinq_svlan table.
     * 
     * @var object
     */
    protected $svlanDb;

    /**
     * Placeholder for nyan_orm instance for qinq_bindings table.
     * 
     * @var object
     */
    protected $cvlanDb;

    /**
     * Placeholder for nyan_orm instance for switches_qinq table.
     * 
     * @var object
     */
    protected $switchesqinqDb;

    /**
     * Placeholder for nyan_orm instance for switches table.
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
     * Placeholder for nyan_orm instance for switchportassign table.
     * 
     * @var object
     */
    protected $switchPortDb;

    /**
     * Placeholder for nyan_orm instance for zte_qinq table.
     * 
     * @var object
     */
    protected $zteqinqDb;

    /**
     * Placeholder for nyan_orm instance for zte_cards table.
     * 
     * @var object
     */
    protected $zteCardsDb;

    /**
     * Contains main configuration file alter.ini
     * 
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains all realms
     * 
     * @var array
     */
    protected $allRealms = array();

    /**
     * Contains all svlans
     * 
     * @var array
     */
    protected $allSvlan = array();

    /**
     * Contains all errors
     * 
     * @var array
     */
    public $error = array();

    /**
     * Contains all exceptions.
     * 
     * @var array
     */
    public $exceptions = array();

    /**
     * Placeholder for UbillingMessageHelper instance.
     * 
     * @var object
     */
    protected $messages;

    /**
     * Contains default type of vlan allocation.
     * 
     * @var string
     */
    protected $defaultType;

    /**
     * Contains all realms to select
     * 
     * @var array
     */
    protected $realmSelector = array();

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
     * Contains all occupied cvlans by customers.
     * 
     * @var array
     */
    protected $occupiedUniversal = array();

    /**
     * Contains all occupied cvlans by switches.
     * 
     * @var array
     */
    protected $occupiedSwitches = array();

    /**
     * Contains all occupied cvlans by OLTs
     * 
     * @var array
     */
    protected $occupiedOlt = array();

    /**
     * Contains all Cvlan => slot number for ceratin OLT.
     * 
     * @var array
     */
    protected $occupiedOltSlot = array();

    /**
     * Contains all Cvlan => port number for certain OLT.
     * 
     * @var array
     */
    protected $occupiedOltPort = array();

    /**
     * Storing data cvlan = switch id
     * 
     * @var array
     */
    protected $occupiedOltId = array();

    /**
     * Dictionary for pairing cvlan number with switch which occupies this cvlan.
     * 
     * @var array
     */
    protected $switchVlans = array();

    /**
     * Contains all assigned ports by users.
     * 
     * @var array
     */
    protected $switchPortCustomer = array();

    /**
     * Contains all not assigned ports.
     * 
     * @var array
     */
    protected $switchPortFree = array();

    /**
     * Default realm selection
     * 
     * @var mixed
     */
    public $defaultRealm = 1;

    /**
     * Default svlan selection.
     * 
     * @var mixed
     */
    public $defaultSvlan = 1;

    /**
     * Instance of UbRouting class.
     * 
     * @var object
     */
    public $routing;

    /**
     * Array loads from OnuRegister class. Contains all Epon cards.
     * 
     * @var array
     */
    protected $eponCards = array();

    /**
     * Array loads from OnuRegister class. Contains all Gpon cards.
     * 
     * @var array
     */
    protected $gponCards = array();

    /**
     * Contains all occupied switchports
     * 
     * @var array
     */
    protected $allPorts = array();

    /**
     * Contains current svlan_id
     * 
     * @var int
     */
    protected $svlanId = 0;

    public function __construct($svlanId = 0) {
        $this->routing = new ubRouting();
        $this->messages = new UbillingMessageHelper();
        if (!$svlanId) {
            $this->svlanId = $this->routing->get('svlan_id', 'int');
        } else {
            $this->svlanId = $svlanId;
        }
        $this->initEnv();
        $this->dbInit();
        $this->loadData();
    }

    /**
     * Create all nyan_orm instances.
     * 
     * @return void
     */
    protected function dbInit() {
        $this->realmDb = new nya_realms();
        $this->svlanDb = new nya_qinq_svlan();
        $this->cvlanDb = new nya_qinq_bindings();
        $this->switchesqinqDb = new nya_switches_qinq();
        $this->switchesDb = new nya_switches();
        $this->switchModelsDb = new nya_switchmodels();
        $this->switchPortDb = new nya_switchportassign();
        $this->zteqinqDb = new NyanORM('zte_qinq');
        $this->zteCardsDb = new NyanOrm('zte_cards');
    }

    /**
     * Load all realms
     * 
     * @return void
     */
    protected function loadData() {
        $this->eponCards = OnuRegister::allEponCards();
        $this->gponCards = OnuRegister::allGponCards();
        $this->allRealms = $this->realmDb->getAll('id');
        $this->allSwitches = $this->switchesDb->getAll('id');
        $this->allSwitchModels = $this->switchModelsDb->getAll('id');
        $this->loadOccupiedCvlans();
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
     * Init urls
     * 
     * @return void
     */
    protected function initEnv() {
        $this->setManagementUrl();
        $this->setSvlanUrl();
        $this->loadAlter();
    }

    /**
     * Set svlan url
     * 
     * @return void
     */
    protected function setSvlanUrl() {
        $this->startSvlanUrl = self::MODULE_SVLAN . '&realm_id=' . $this->routing->get('realm_id', 'int');
    }

    /**
     * Set main module url.
     * 
     * @return void
     */
    protected function setManagementUrl() {
        $this->startManagementUrl = self::MODULE . '&realm_id=' . $this->routing->get('realm_id', 'int') . '&svlan_id=' . $this->svlanId;
    }

    /**
     * Redirects user back and show error if any
     * 
     * @return void
     */
    protected function goToStartOrError($url) {
        if (!empty($this->error)) {
            $this->showError();
        }
        if (!empty($this->exceptions)) {
            $this->showExceptions();
        }
        //redirect on success
        if (empty($this->error) and empty($this->exceptions)) {
            rcms_redirect($url);
        }
    }

    /**
     * Validator function with subchecks.
     * 
     * @return bool
     */
    protected function validateSvlan() {
        $this->checkSvlanRange();
        $this->uniqueSvlan();
        $this->protectedSvlan();

        if (!empty($this->error)) {
            return (false);
        }
        return (true);
    }

    /**
     * Check if we do not touch protected entries.
     * 
     * @return bool
     */
    protected function protectedSvlan() {
        if ($this->notDefaultSvlanEdit()) {
            return (true);
        }
        if ($this->notDefaultSvlanDelete()) {
            return (true);
        }
        //add error if check not passed
        $this->error[] = __('Default SVLAN is protected and cannot be deleted or edited');
        return false;
    }

    /**
     * Check if svlan number equal to default one.
     * 
     * @return bool
     */
    protected function defaultSvlanNum() {
        if ($this->routing->get('svlan_num', 'int') == self::DEFAULT_SVLAN) {
            return (true);
        }
        return (false);
    }

    /**
     * Check if realm id is equal to default one.
     * 
     * @return bool
     */
    protected function defaultRealmId() {
        if ($this->routing->get('realm_id', 'int') == self::DEFAULT_REALM) {
            return (true);
        }
        return (false);
    }

    /**
     * Check if old svlan num is equal to default one.
     * 
     * @return bool
     */
    protected function defaultSvlanOldNum() {
        if ($this->routing->get('old_svlan_num', 'int') == self::DEFAULT_SVLAN) {
            return (true);
        }
        return (false);
    }

    /**
     * Check if editing not protected entity.
     * 
     * @return bool
     */
    protected function notDefaultSvlanEdit() {
        if (($this->routing->get('action') == 'edit')) {
            if ($this->defaultSvlanOldNum() and $this->defaultRealmId()) {
                return (false);
            }
        }
        return (true);
    }

    /**
     * Check if deleting not protected entity.
     * 
     * @return bool
     */
    protected function notDefaultSvlanDelete() {
        if ($this->routing->get('action') == 'delete') {
            if ($this->defaultSvlanNum() and $this->defaultRealmId()) {
                return (false);
            }
        }
        return (true);
    }

    /**
     * Check if value too low.
     * 
     * @return bool
     */
    protected function vlanNumTooLow() {
        if ($this->routing->get('svlan', 'int') < 0) {
            return (true);
        }
        return (false);
    }

    /**
     * Check if value too high.
     * 
     * @return bool
     */
    protected function vlanNumTooHigh() {
        if ($this->routing->get('svlan', 'int') > 4096) {
            return (true);
        }
        return (false);
    }

    /**
     * Check if SVLAN has correct format from 0 to 4096.
     * 
     * @return bool
     */
    protected function checkSvlanRange() {
        if (!$this->vlanNumTooLow() and ! $this->vlanNumTooHigh()) {
            return (true);
        }
        //add error if not exited previously
        $this->error[] = __('Wrong value') . ': SVLAN ' . $this->routing->get('svlan_num', 'int');
        return (false);
    }

    /**
     * Check if SVLAN is unique.
     * 
     * @return bool
     */
    protected function uniqueSvlan() {
        if ($this->uniqueSvlanAdd()) {
            return (true);
        }
        if ($this->uniqueSvlanEdit()) {
            return (true);
        }
        $this->error[] = __('Wrong value') . ': SVLAN ' . $this->routing->get('svlan_num', 'int') . ' ' . __('already exists');
        return (false);
    }

    /**
     * Check if SVLAN is unique when adding new SVLAN.
     * 
     * @return bool
     */
    protected function uniqueSvlanAdd() {
        if ($this->routing->get('action') == 'add') {
            $this->svlanDb->where('realm_id', '=', $this->routing->get('realm_id', 'int'));
            $allSvlan = $this->svlanDb->getAll('svlan');
            if (isset($allSvlan[$this->routing->get('svlan_num')])) {
                return (false);
            }
        }
        return (true);
    }

    /**
     * Check if SVLAN is unique when editing SVLAN.
     * 
     * @return bool
     */
    protected function uniqueSvlanEdit() {
        if ($this->routing->get('action') == 'edit') {
            $this->svlanDb->where('realm_id', '=', $this->routing->get('realm_id', 'int'));
            $this->svlanDb->where('svlan', '!=', $this->routing->get('old_svlan_num', 'int'));
            $allSvlan = $this->svlanDb->getAll('svlan');
            if (isset($allSvlan[$this->routing->get('svlan_num')])) {
                return (false);
            }
        }
        return (true);
    }

    /**
     * Creating new svlan
     * 
     * @return void
     */
    public function addSvlan() {
        try {
            if ($this->validateSvlan()) {
                $this->addSvlanDb();
                $this->logSvlanAdd();
            }
            $this->goToStartOrError($this->startSvlanUrl);
        } catch (Exception $ex) {
            $this->exceptions[] = $ex;
            $this->goToStartOrError($this->startSvlanUrl);
        }
    }

    /**
     * Adding entry to DB and log.
     * 
     * @return void 
     */
    protected function addSvlanDb() {
        $this->svlanDb->data('realm_id', $this->routing->get('realm_id', 'int'));
        $this->svlanDb->data('svlan', $this->routing->get('svlan_num', 'int'));
        $this->svlanDb->data('description', $this->routing->get('description', 'mres'));
        $this->svlanDb->create();
    }

    /**
     * Editing svlan
     * 
     * @return void
     */
    public function editSvlan() {
        try {
            if ($this->validateSvlan()) {
                $this->editSvlanDb();
                $this->logSvlanEdit();
            }
            $this->goToStartOrError($this->startSvlanUrl);
        } catch (Exception $ex) {
            $this->exceptions[] = $ex;
            $this->goToStartOrError($this->startSvlanUrl);
        }
    }

    /**
     * Saving changes to DB.
     * 
     * @return void
     */
    protected function editSvlanDb() {
        $this->svlanDb->where('realm_id', '=', $this->routing->get('realm_id', 'int'));
        $this->svlanDb->where('id', '=', $this->routing->get('id', 'int'));
        $this->svlanDb->data('svlan', $this->routing->get('svlan_num', 'int'));
        $this->svlanDb->data('description', $this->routing->get('description', 'mres'));
        $this->svlanDb->save();
    }

    /**
     * Delete svlan
     * 
     * @return void
     */
    public function deleteSvlan() {
        try {
            if ($this->validateSvlan()) {
                $this->deleteSvlanRelated();
                $this->logSvlanDelete();
            }
            $this->goToStartOrError($this->startSvlanUrl);
        } catch (Exception $ex) {
            $this->exceptions[] = $ex;
            $this->goToStartOrError($this->startSvlanUrl);
        }
    }

    /**
     * Delete all related to svlan data.
     * 
     * @return void
     */
    protected function deleteSvlanRelated() {
        $this->deleteSvlanDb();
        $this->deleteSvlanSwitchesDb();
        $this->deleteSvlanUniversalDb();
        $this->deleteSvlanOltDb();
    }

    /**
     * Delete svlan from qinq_svlan table.
     * 
     * @return void
     */
    protected function deleteSvlanDb() {
        $this->svlanDb->where('id', '=', $this->routing->get('id', 'int'));
        $this->svlanDb->delete();
    }

    /**
     * Delete svlan from switches_qinq table.
     * 
     * @return void
     */
    protected function deleteSvlanSwitchesDb() {
        $this->switchesqinqDb->where('svlan_id', '=', $this->routing->get('id', 'int'));
        $this->switchesqinqDb->delete();
    }

    /**
     * Delete svlan from qinq_bindings table.
     * 
     * @return void
     */
    protected function deleteSvlanUniversalDb() {
        $this->cvlanDb->where('svlan_id', '=', $this->routing->get('id', 'int'));
        $this->cvlanDb->delete();
    }

    /**
     * Delete svlan from zte_qinq table.
     * 
     * @return void.
     */
    protected function deleteSvlanOltDb() {
        $this->zteqinqDb->where('svlan_id', '=', $this->routing->get('id', 'int'));
        $this->zteqinqDb->delete();
    }

    /**
     * Modal form to create new svlan.
     * 
     * @return string
     */
    protected function addSvlanForm() {
        $addControls = wf_HiddenInput('module', 'vlanmanagement');
        $addControls .= wf_HiddenInput('svlan', 'true');
        $addControls .= wf_HiddenInput('action', 'add');
        $addControls .= wf_HiddenInput('realm_id', $this->routing->get('realm_id', 'int'));
        $addControls .= wf_TextInput('svlan_num', 'SVLAN', '', true, '');
        $addControls .= wf_TextInput('description', __('Description'), '', true, '', '');
        $addControls .= wf_Submit('Save');
        $form = wf_Form('', 'GET', $addControls, 'glamour');
        return (wf_modalAuto(web_icon_create() . ' ' . __('Create new entry'), __('Create new entry'), $form, 'ubButton'));
    }

    /**
     * Little hack for creating dynamic form only on demand.
     * 
     * @param string $encode
     * 
     * @return string
     */
    public function ajaxEditSvlan($encode) {
        $decode = unserialize(base64_decode($encode));
        $addControls = wf_HiddenInput('module', 'vlanmanagement');
        $addControls .= wf_HiddenInput('svlan', 'true');
        $addControls .= wf_HiddenInput('action', 'edit');
        $addControls .= wf_HiddenInput('id', $decode['id']);
        $addControls .= wf_HiddenInput('realm_id', $decode['realm_id']);
        $addControls .= wf_TextInput('svlan_num', 'SVLAN', $decode['svlan'], true, '');
        $addControls .= wf_TextInput('description', __('Description'), $decode['description'], true, '');
        $addControls .= wf_HiddenInput('old_svlan_num', $decode['svlan']);
        $addControls .= wf_Submit('Save');
        $form = wf_Form('', 'GET', $addControls, 'glamour');
        return ($form);
    }

    /**
     * Selector of realms for svlan submodule.
     * 
     * @return string
     */
    protected function realmSvlanSelector() {
        if (!empty($this->allRealms)) {
            foreach ($this->allRealms as $id => $each) {
                $params[$id] = $each['realm'] . ' | ' . $each['description'];
            }
        }
        $inputs = wf_HiddenInput('module', 'vlanmanagement');
        $inputs .= wf_HiddenInput('svlan', 'true');
        $inputs .= wf_SelectorAC('realm_id', $params, __('Realm'), $this->routing->get('realm_id', 'int'));
        return (wf_Form("", "GET", $inputs));
    }

    /**
     * Main svlan selector.
     * 
     * @param int $realmId
     * 
     * @return string
     */
    public function svlanSelector($realmId) {
        $realmId = vf($realmId, 3);
        $this->svlanDb->where('realm_id', '=', $realmId);
        $allSvlan = $this->svlanDb->getAll('id');
        $allSvlanSelector[''] = self::EMPTY_SELECTOR_OPTION;
        if (!empty($allSvlan)) {
            foreach ($allSvlan as $id => $each) {
                $allSvlanSelector[$id] = $each['svlan'] . ' | ' . $each['description'];
            }
        }
        $result = wf_HiddenInput('module', 'vlanmanagement');
        $result .= wf_HiddenInput('realm_id', $realmId);
        $result .= wf_SelectorAC('svlan_id', $allSvlanSelector, 'SVLAN', $this->svlanId, true);

        return ($result);
    }

    /**
     * Link to go back from svlan submodule to main vlanmanagement module.
     * 
     * @return string
     */
    protected function backSvlan() {
        return (wf_BackLink(self::MODULE, __('Back'), false, 'ubButton'));
    }

    /**
     * Render all buttons for svlan submodule.
     * 
     * @return void
     */
    public function linksSvlan() {
        show_window('', '' .
                $this->backSvlan() .
                $this->addSvlanForm()
        );
        show_window('', $this->realmSvlanSelector());
    }

    /**
     * Show all available svlans.
     * 
     * @return string
     */
    public function showSvlanAll() {
        $modal = '<link rel="stylesheet" href="./skins/vlanmanagement.css" type="text/css" media="screen" />';
        $modal .= wf_tag('div', false, 'cvmodal', 'id="dialog-modal_cvmodal" title="' . __('Choose') . '" style="display:none; width:1px; height:1px;"');
        $modal .= wf_tag('p', false, '', 'id="content-cvmodal"');
        $modal .= wf_tag('p', true);
        $modal .= wf_tag('div', true);
        $modal .= '<script src="./modules/jsc/vlanmanagement.js" type="text/javascript"></script>';

        $columns = array('ID', 'SVLAN', 'Description', 'Actions');
        $opts = '"order": [[ 0, "desc" ]]';
        $result = '';
        $ajaxURL = '' . self::MODULE_SVLAN . '&action=ajax&realm_id=' . $this->routing->get('realm_id', 'int');
        $result .= show_window('', $modal . wf_JqDtLoader($columns, $ajaxURL, false, __('Realms'), 100, $opts));
        return ($result);
    }

    /**
     * Data to render qhuery datatables.
     * 
     * @return json
     */
    public function ajaxSvlanData() {
        $this->svlanDb->where('realm_id', '=', $this->routing->get('realm_id', 'int'));
        $this->allSvlan = $this->svlanDb->getAll('id');
        $json = new wf_JqDtHelper();
        if (!empty($this->allSvlan)) {
            foreach ($this->allSvlan as $io => $each) {
                $eachId = base64_encode(serialize(array(
                    'id' => $each['id'],
                    'realm_id' => $each['realm_id'],
                    'svlan' => $each['svlan'],
                    'description' => $each['description']
                )));
                $actLinks = wf_tag('div', false, '', 'id="' . $eachId . '" onclick="svlanEdit(this)" style="display:inline-block;"') . web_edit_icon() . wf_tag('div', true);
                $actLinks .= wf_JSAlert(self::MODULE_SVLAN . '&action=delete&id=' . $each['id'] . '&realm_id=' . $this->routing->get('realm_id', 'int') . '&svlan_num=' . $each['svlan'], web_delete_icon(), $this->messages->getDeleteAlert());
                $data[] = $each['id'];
                $data[] = $each['svlan'];
                $data[] = $each['description'];
                $data[] = $actLinks;
                $json->addRow($data);
                unset($data);
            }
        }
        $json->getJson();
    }

    /**
     * All available buttons and links on main module.
     * 
     * @return void
     */
    public function linksMain() {
        $urls = wf_Link(self::MODULE_UNIVERSALQINQ, web_icon_extended() . 'UniversalQINQ', false, 'ubButton');
        $urls .= wf_Link(self::MODULE_SVLAN . '&realm_id=1', web_icon_extended() . 'SVLAN', false, 'ubButton');
        $urls .= wf_link(self::MODULE_REALMS, web_icon_extended() . __('Realms'), false, 'ubButton');
        show_window('', $urls);
        show_window('', $this->realmAndSvlanSelectors());
    }

    /**
     * Selector for realm and svlan in main module
     * 
     * @return string
     */
    public function realmAndSvlanSelectors() {
        $result = wf_AjaxLoader();
        $inputs = $this->realmMainSelector();
        $inputs .= wf_delimiter();
        $inputs2 = wf_AjaxContainer('ajcontainer', '', $this->svlanSelector($this->routing->get('realm_id', 'int') ? $this->routing->get('realm_id', 'int') : $this->defaultRealm));
        $inputs2 .= wf_delimiter();
        $result .= $inputs . wf_Form("", 'GET', $inputs2);
        return ($result);
    }

    /**
     * Creating selector for realm in main module.
     * 
     * @return striing
     */
    protected function realmMainSelector() {
        if (!empty($this->allRealms)) {
            foreach ($this->allRealms as $id => $each) {
                $this->realmSelector[self::MODULE . '&action=realm_id_select&ajrealmid=' . $id] = $each['realm'] . ' | ' . $each['description'];
            }

            reset($this->allRealms);
            $this->defaultRealm = key($this->allRealms);
        }

        return (wf_AjaxSelectorAC('ajcontainer', $this->realmSelector, __('Select realm'), self::MODULE . '&action=realm_id_select&ajrealmid=' . $this->routing->get('realm_id', 'int'), false));
    }

    /**
     * Choose assign type switch or customer.
     * 
     * @return string
     */
    protected function typeSelector() {
        $selector = array(self::MODULE . '&action=choosetype&type=none' => self::EMPTY_SELECTOR_OPTION);

        $switches = self::MODULE
                . '&action=choosetype&type=qinqswitches&'
                . '&cvlan_num=' . $this->routing->get('cvlan_num', 'int');
        $universal = self::MODULE
                . '&action=choosetype&type=universalqinq&'
                . '&cvlan_num=' . $this->routing->get('cvlan_num', 'int');
        $olt = self::MODULE
                . '&action=choosetype&type=qinqolt&'
                . '&cvlan_num=' . $this->routing->get('cvlan_num', 'int');

        //if qinq switches enabled
        if ($this->altCfg[self::QINQ_OPTION]) {
            $selector[$switches] = __(self::QINQ_LABEL);
            $this->defaultType = $switches;
        }

        if ($this->altCfg[self::QINQ_OPTION] and $this->altCfg[self::UNIVERSAL_QINQ_OPTION] and cfr(self::UNIVERSAL_QINQ_RGHT)) {
            $selector[$universal] = __(self::UNIVERSAL_QINQ_LABEL);
        }

        //if qinq switches disabled
        if (!$this->altCfg[self::QINQ_OPTION] and $this->altCfg[self::UNIVERSAL_QINQ_OPTION] and cfr(self::UNIVERSAL_QINQ_RGHT)) {
            $selector[$universal] = __(self::UNIVERSAL_QINQ_LABEL);
            $this->defaultType = $universal;
        } else {
            $this->defaultType = self::EMPTY_SELECTOR_OPTION;
        }

        if ($this->altCfg[self::UNIVERSAL_QINQ_OPTION] and $this->altCfg[self::ONUREG_QINQ_OPTION] and cfr(self::UNIVERSAL_QINQ_RGHT)) {
            $selector[$olt] = 'QINQ ' . __('pool') . ' ' . __('for') . ' OLT';
        }


        return (wf_AjaxSelectorAC('ajtypecontainer', $selector, __('Choose type'), $this->defaultType, false));
    }

    /**
     * Generate selector for OLTs.
     * 
     * @return string
     */
    protected function oltSelector() {
        //still can't use nyan_orm for joins :(
        $query = 'SELECT `sw`.`id`,`sw`.`ip`,`sw`.`location`,`model`.`snmptemplate` FROM `switches` AS `sw` JOIN `switchmodels` AS `model` ON (`sw`.`modelid` = `model`.`id`) WHERE `sw`.`desc` LIKE "%OLT%" AND `model`.`snmptemplate` LIKE "ZTE%"';
        $switches = simple_queryall($query);

        $options[self::EMPTY_SELECTOR_OPTION] = self::EMPTY_SELECTOR_OPTION;

        if (!empty($switches)) {
            foreach ($switches as $io => $each) {
                $options[self::MODULE . '&action=chooseoltcard&id=' . $each['id'] . '&cvlan_num=' . $this->routing->get('cvlan_num', 'int')] = $each['ip'] . ' ' . $each['location'];
            }
        }

        reset($options);
        $default = current($options);

        $result = wf_AjaxLoader();
        $result .= wf_AjaxSelectorAC('ajoltcontainer', $options, __('Select switch'), $default);
        $result .= wf_AjaxContainer('ajoltcontainer', '');
        return ($result);
    }

    /**
     * Generate card selector for choosen OLT.
     * 
     * @return type
     */
    public function cardSelector() {
        $result = '';
        $options[self::EMPTY_SELECTOR_OPTION] = self::EMPTY_SELECTOR_OPTION;
        if ($this->routing->get('id', 'int')) {
            //still can't use nyan_orm for joins :(
            $this->zteCardsDb->selectable('`zte_cards`.`id`,`zte_cards`.`swid`,`zte_cards`.`slot_number`,`zte_cards`.`card_name`');
            $this->zteCardsDb->join('LEFT', 'zte_qinq', 'swid');
            $this->zteCardsDb->where('swid', '=', $this->routing->get('id', 'int'));
            $this->zteCardsDb->orderBy('slot_number', 'ASC');
            $allCards = $this->zteCardsDb->getAll('id');
            $this->zteCardsDb->selectable();
            if (!empty($allCards)) {
                foreach ($allCards as $io => $each) {
                    $options[self::MODULE . '&action=choosecardport&id=' . $this->routing->get('id', 'int') . '&slot_number=' . $each['slot_number'] . '&card_name=' . $each['card_name'] . '&cvlan_num=' . $this->routing->get('cvlan_num', 'int')] = $each['slot_number'] . ' | ' . $each['card_name'];
                }
            }
        }

        reset($options);
        $default = current($options);

        $result .= wf_AjaxSelectorAC('ajoltcardcontainer', $options, __('Select card'), $default);
        $result .= wf_AjaxContainer('ajoltcardcontainer', '');

        return ($result);
    }

    /**
     * Generate port selector for choosen card.
     * 
     * @return type
     */
    public function portCardSelector() {
        $form = '';
        $options[self::EMPTY_SELECTOR_OPTION] = self::EMPTY_SELECTOR_OPTION;
        $portsCount = 0;
        $maxOnuCount = 128;
        if ($this->routing->get('id', 'int') and $this->routing->get('slot_number', 'int')) {
            if (isset($this->eponCards[$this->routing->get('card_name')])) {
                $portsCount = $this->eponCards[$this->routing->get('card_name')];
                if ($this->routing->get('card_name') != 'ETTO' and $this->routing->get('card_name') != 'ETTOK') {
                    $maxOnuCount = 64;
                }
            } else if (isset($this->gponCards[$this->routing->get('card_name')])) {
                $portsCount = $this->gponCards[$this->routing->get('card_name')];
            }
        }
        if ($portsCount) {
            $possiblePorts = range(self::ARRAY_RANGE_START, $portsCount, self::ARRAY_RANGE_STEP);
            $this->zteqinqDb->where('swid', '=', $this->routing->get('id', 'int'));
            $this->zteqinqDb->where('slot_number', '=', $this->routing->get('slot_number', 'int'));
            $usedPortsRaw = $this->zteqinqDb->getAll('port');
            $usedPorts = array();
            foreach ($usedPortsRaw as $port => $each) {
                $usedPorts[] = $port;
            }
            $freePorts = array_diff($possiblePorts, $usedPorts);
            foreach ($freePorts as $each) {
                $options[$each] = $each;
            }
        }
        $form .= wf_HiddenInput('action', 'add');
        $form .= wf_HiddenInput('type', 'qinqolt');
        $form .= wf_HiddenInput('swid', $this->routing->get('id', 'int'));
        $form .= wf_HiddenInput('slot_number', $this->routing->get('slot_number', 'int'));
        $form .= wf_HiddenInput('card_name', $this->routing->get('card_name', 'mres'));
        $form .= wf_Selector('port', $options, __('Select port'), self::EMPTY_SELECTOR_OPTION, true);
        return ($form);
    }

    /**
     * Generate selector for switches.
     * 
     * @return string
     */
    protected function switchSelector() {
        $options[self::EMPTY_SELECTOR_OPTION] = self::EMPTY_SELECTOR_OPTION;

        $query = "SELECT `switches`.`id`,`switches`.`ip`,`switches`.`location` FROM `switches` LEFT JOIN `switches_qinq` ON `switches`.`id` = `switches_qinq`.`switchid` WHERE `switches_qinq`.`switchid` IS NULL";
        $switches = simple_queryall($query);

        if (!empty($switches)) {
            foreach ($switches as $io => $each) {
                $options[$each['id']] = $each['ip'] . ' ' . $each['location'];
            }
        }

        return (wf_Selector('qinqswitchid', $options, __('Select switch')));
    }

    /**
     * Generating all available types for qinq assign.
     * 
     * @return type
     */
    public function types() {
        $result = '';
        if ($this->routing->checkGet('type')) {
            switch ($this->routing->get('type')) {
                case 'universalqinq':
                    $result .= wf_HiddenInput('type', 'universalqinq');
                    $result .= wf_tag('div', false) . $this->routing->get('cvlan_num', 'int') . " CVLAN" . wf_tag('div', true);
                    $result .= wf_TextInput('login', __('Login'), $this->routing->get('login'), true);
                    break;
                case 'qinqswitches':
                    $result .= wf_HiddenInput('type', 'qinqswitches');
                    $result .= wf_tag('div', false) . $this->routing->get('cvlan_num', 'int') . " CVLAN" . wf_tag('div', true);
                    $result .= $this->switchSelector();
                    break;
                case 'qinqolt':
                    $result .= wf_HiddenInput('type', 'qinqolt');
                    $result .= wf_tag('div', false) . $this->routing->get('cvlan_num', 'int') . " CVLAN" . wf_tag('div', true);
                    $result .= $this->oltSelector();

                    break;
            }
        } else {
            $switches = self::MODULE
                    . '&action=choosetype&type=qinqswitches&'
                    . '&cvlan_num=' . $this->routing->get('cvlan_num', 'int');
            $universal = self::MODULE
                    . '&action=choosetype&type=universalqinq&'
                    . '&cvlan_num=' . $this->routing->get('cvlan_num', 'int');


            switch ($this->defaultType) {
                case $universal:
                    $result .= wf_HiddenInput('type', 'universalqinq');
                    $result .= wf_tag('div', false) . $this->routing->get('cvlan_num', 'int') . " CVLAN" . wf_tag('div', true);
                    $result .= wf_TextInput('login', __('Login'), $this->routing->get('login'), true);
                    break;
                case $switches:
                    $result .= wf_HiddenInput('type', 'qinqswitches');
                    $result .= wf_tag('div', false) . $this->routing->get('cvlan_num', 'int') . " CVLAN" . wf_tag('div', true);
                    $result .= $this->switchSelector();
                    break;
            }
        }

        return ($result);
    }

    /**
     * Check if CVLAN not occupied by any switch.
     * 
     * @param int $cvlan
     * 
     * @return array or bool
     */
    protected function checkCvlanSwitches($cvlan) {
        if (isset($this->occupiedSwitches[$cvlan])) {
            $result['used'] = $this->occupiedSwitches[$cvlan];
            $result['type'] = 'switch';
            return ($result);
        }
        return (false);
    }

    /**
     * Check if CVLAN not occupied by any customer.
     * 
     * @param int $cvlan
     * 
     * @return array or bool
     */
    protected function checkCvlanUniversal($cvlan) {
        if (isset($this->occupiedUniversal[$cvlan])) {
            $result['used'] = $this->occupiedUniversal[$cvlan];
            $result['type'] = 'universal';
            return ($result);
        }
        return (false);
    }

    /**
     * Check if CVLAN not occupied by any OLT.
     * 
     * @param int $cvlan
     * 
     * @return array or bool
     */
    protected function checkCvlanOlt($cvlan) {
        if (isset($this->occupiedOlt[$cvlan])) {
            $result['used'] = $this->occupiedOlt[$cvlan];
            $result['type'] = 'olt';
            return ($result);
        }
        return (false);
    }

    /**
     * Check if CVLAN is free.
     * Multiple return is mandatory to check only in needed order.
     * 
     * @param int $cvlan
     * 
     * @return array
     */
    protected function checkCvlanFree($cvlan) {
        $result['used'] = false;
        $result['type'] = 'none';

        if ($this->checkCvlanOlt($cvlan)) {
            $result = $this->checkCvlanOlt($cvlan);
            return ($result);
        }

        if ($this->checkCvlanSwitches($cvlan)) {
            $result = $this->checkCvlanSwitches($cvlan);
            return ($result);
        }

        if ($this->checkCvlanUniversal($cvlan)) {
            $result = $this->checkCvlanUniversal($cvlan);
            return ($result);
        }

        return ($result);
    }

    /**
     * Return error upon occupied CVLAN.
     * 
     * @param array $check
     * @param int $cvlan
     * @param int $lastCvlan
     * 
     * @return void
     */
    protected function errorOccupied($check, $cvlan, $lastCvlan) {
        switch ($check['type']) {
            case'switch':
                $this->error[] = __('Error') . ': ' . __('trying allocate')
                        . ' ' . "CVLAN " . __("from") . ' ' . $this->routing->get('cvlan_num', 'int')
                        . ' ' . __('to') . $lastCvlan
                        . '. CVLAN ' . $cvlan
                        . ' ' . __('occcupied by switch') . ': ' . $check['used'];
                break;

            case 'universal':
                $this->error[] = __("Error") . ': ' . __('trying allocate') . ' '
                        . "CVLAN " . __("from") . ' ' . $this->routing->get('cvlan_num', 'int')
                        . ' ' . __('to') . ' ' . $lastCvlan
                        . '. CVLAN ' . $cvlan . ' ' . __('occcupied by login') . ': '
                        . wf_link("?module=userprofile&username="
                                . $check['used']['login'], $check['used']['login']
                );
                break;
            case 'olt':
                $this->error[] = __('Error') . ': ' . __('trying allocate')
                        . ' ' . "CVLAN " . __("from") . ' ' . $this->routing->get('cvlan_num', 'int')
                        . ' ' . __('to') . ' ' . $lastCvlan
                        . '. CVLAN ' . $cvlan
                        . ' ' . __('occcupied by OLT') . ': ' . $check['used'];
                break;
        }
    }

    /**
     * Check if we have receive correct data.
     * 
     * @return bool
     */
    protected function validateNewOlt() {
        if (!$this->routing->get('swid', 'int')) {
            $this->error[] = __('No OLT selected');
        }

        if (!empty($this->error)) {
            return (false);
        }
        return (true);
    }

    /**
     * Add new CVLAN range binding for olt.
     * 
     * @return void
     */
    protected function addNewOltBinding() {
        try {
            if ($this->validateNewOlt()) {
                $maxOnuCount = 128;
                $cardName = $this->routing->get('card_name');
                $lastCvlan = $this->routing->get('cvlan_num', 'int') + $maxOnuCount - 1;
                if (isset($this->eponCards[$cardName])) {
                    if ($cardName != 'ETTO' AND $cardName != 'ETTOK') {
                        $maxOnuCount = 64;
                    }
                }
                for ($cvlan = $this->routing->get('cvlan_num', 'int'); $cvlan <= $lastCvlan; $cvlan++) {
                    $check = $this->checkCvlanFree($cvlan);
                    if ($check['used']) {
                        break;
                    }
                }
                if (!$check['used']) {
                    $this->zteqinqDb->data('swid', $this->routing->get('swid', 'int'));
                    $this->zteqinqDb->data('slot_number', $this->routing->get('slot_number', 'int'));
                    $this->zteqinqDb->data('port', $this->routing->get('port', 'int'));
                    $this->zteqinqDb->data('svlan_id', $this->svlanId);
                    $this->zteqinqDb->data('cvlan', $this->routing->get('cvlan_num', 'int'));
                    $this->zteqinqDb->create();
                } else {
                    $this->errorOccupied($check, $cvlan, $lastCvlan);
                }
            }
        } catch (Exception $ex) {
            $this->exceptions[] = $ex;
        }
    }

    /**
     * Check if we have received correct data.
     * 
     * @return bool
     */
    protected function validateNewSwitch() {
        if (!$this->routing->get('qinqswitchid')) {
            $this->error[] = __('No switch selected');
        }

        if ($this->routing->get('qinqswitchid') == self::EMPTY_SELECTOR_OPTION) {
            $this->error[] = __('No switch selected');
        }

        if (!empty($this->error)) {
            return (false);
        }
        return (true);
    }

    /**
     * Create new switch binding
     * 
     * @return void
     */
    protected function addNewSwitchBinding() {
        if ($this->validateNewSwitch()) {
            $modelid = $this->allSwitches[$this->routing->get('qinqswitchid', 'int')]['modelid'];
            $port_number = $this->allSwitchModels[$modelid]['ports'];
            $lastCvlan = $this->routing->get('cvlan_num', 'int') + $port_number - 1;
            for ($cvlan = $this->routing->get('cvlan_num', 'int'); $cvlan <= $lastCvlan; $cvlan++) {
                $check = $this->checkCvlanFree($cvlan);
                if ($check['used']) {
                    break;
                }
            }
            if (!$check['used']) {
                $switchesQinQ = new SwitchesQinQ();
                $qinqSaveResult = $switchesQinQ->saveQinQ();
                if (!empty($qinqSaveResult)) {
                    $this->error[] = $qinqSaveResult;
                }
            } else {
                $this->errorOccupied($check, $cvlan, $lastCvlan);
            }
        }
    }

    /**
     * Create new binding based on chosen type.
     * 
     * @return void
     */
    public function addNewBinding() {
        try {
            switch ($this->routing->get('type')) {
                case 'universalqinq':
                    $universalqinq = new UniversalQINQ();
                    $result = $universalqinq->add();
                    if (!empty($result)) {
                        foreach ($result as $each) {
                            $this->error[] = $each;
                        }
                    }
                    break;
                case 'qinqswitches':
                    $this->addNewSwitchBinding();
                    break;
                case 'qinqolt':
                    $this->addNewOltBinding();
                    break;
            }
            $this->goToStartOrError($this->startManagementUrl);
        } catch (Exception $ex) {
            $this->exceptions[] = $ex;
            $this->goToStartOrError($this->startManagementUrl);
        }
    }

    /**
     * Little trick with generation ajax edit form only on demand.
     * 
     * @return string
     */
    public function ajaxCustomer() {
        $result = '';
        $this->cvlanDb->where('svlan_id', '=', $this->svlanId);
        $this->cvlanDb->where('cvlan', '=', $this->routing->get('cvlan_num', 'int'));
        $data = $this->cvlanDb->getAll('cvlan');
        $login = $data[$this->routing->get('cvlan_num', 'int')]['login'];
        $userData = zb_UserGetAllData($login);
        $userData = $userData[$login];
        $result .= __('Customer') . ': ';
        $result .= wf_Link("?module=userprofile&username=" . $login, $userData['fulladress'] . ' ' . $userData['realname'], true);
        $result .= wf_delimiter(2);
        $result .= wf_Link(self::MODULE_UNIVERSALQINQ . '&action=delete&type=universal&realm_id=' . $this->routing->get('realm_id', 'int') . '&svlan_id=' . $this->routing->get('svlan_id') . '&id=' . $data[$this->routing->get('cvlan_num', 'int')]['id'], web_delete_icon() . __('Delete binding'), false, 'ubButton');

        return ($result);
    }

    /**
     * Little trick with generation ajax edit form only on demand.
     * 
     * @return string
     */
    public function ajaxSwitch() {
        $result = '';
        $this->switchesqinqDb->where('svlan_id', '=', $this->svlanId);
        $this->switchesqinqDb->where('switchid', '=', $this->routing->get('switchid', 'int'));
        $data = $this->switchesqinqDb->getAll('svlan_id');
        $data = $data[$this->svlanId];
        $switch = $this->allSwitches[$data['switchid']];
        $port = $this->routing->get('cvlan_num', 'int') - $data['cvlan'] + 1;
        $this->switchPortDb->where('switchid', '=', $data['switchid']);
        $this->switchPortDb->where('port', '=', $port);
        $swPorts = $this->switchPortDb->getAll('switchid');
        $result .= __("Switch") . ': ';
        $result .= wf_Link("?module=switches&edit=" . $data['switchid'], $switch['ip'] . ' ' . $switch['location']);
        if (!empty($swPorts)) {
            $user = $swPorts[$data['switchid']];
            $userData = zb_UserGetAllData($user['login']);
            $userData = $userData[$user['login']];
            $result .= wf_delimiter();
            $result .= __('Port') . ': ' . $port . '. CVLAN: ' . $this->routing->get('cvlan_num', 'int') . wf_delimiter() . __('Customer') . ': ' . wf_Link("?module=userprofile&username=" . $user['login'], $userData['fulladress'] . ' ' . $userData['realname'], true);
        }
        $result .= wf_delimiter(2);
        $result .= wf_Link(self::MODULE . '&action=deleteswitchbinding&realm_id=' . $this->routing->get('realm_id', 'int') . '&svlan_id=' . $this->svlanId . '&switchid=' . $data['switchid'], web_delete_icon() . __('Delete binding'), false, 'ubButton');

        return ($result);
    }

    /**
     * Little trick with generation ajax edit form only on demand.
     * 
     * @return string
     */
    public function ajaxOlt() {
        $result = '';

        $this->cvlanDb->where('svlan_id', '=', $this->svlanId);
        $this->cvlanDb->where('cvlan', '=', $this->routing->get('cvlan_num', 'int'));
        $data = $this->cvlanDb->getAll('cvlan');
        if (!empty($data)) {
            $login = $data[$this->routing->get('cvlan_num', 'int')]['login'];
            $userData = zb_UserGetAllData($login);
            $userData = $userData[$login];
            $result .= __('Customer') . ': ';
            $result .= wf_Link("?module=userprofile&username=" . $login, $userData['fulladress'] . ' ' . $userData['realname'], true);
            $result .= wf_delimiter();
        }

        $result .= __("OLT") . ': ';
        $result .= wf_Link("?module=ztevlanbinds&edit_card=" . $this->routing->get('switchid', 'int'), $this->occupiedOlt[$this->routing->get('cvlan_num', 'int')]);

        $result .= wf_delimiter(2);
        if (!empty($data)) {
            $result .= wf_Link(self::MODULE_UNIVERSALQINQ . '&action=delete&type=universal&realm_id=' . $this->routing->get('realm_id', 'int') . '&svlan_id=' . $this->routing->get('svlan_id') . '&id=' . $data[$this->routing->get('cvlan_num', 'int')]['id'], web_delete_icon() . __('Delete binding') . ' ' . __('for customer'), false, 'ubButton');
        }

        $result .= wf_Link(self::MODULE . '&action=deleteoltbinding&realm_id=' . $this->routing->get('realm_id', 'int') . '&svlan_id=' . $this->svlanId . '&switchid=' . $this->routing->get('switchid', 'int') . '&slot_number=' . $this->occupiedOltSlot[$this->routing->get('cvlan_num', 'int')] . '&port=' . $this->occupiedOltPort[$this->routing->get('cvlan_num', 'int')], web_delete_icon() . __('Delete binding') . ' ' . __('for') . ' OLT', false, 'ubButton');


        return ($result);
    }

    /**
     * Delete binding for switch
     * 
     * @return void
     */
    public function deleteSwitchBinding() {
        try {
            $this->switchesqinqDb->where('switchid', '=', $this->routing->get('switchid', 'int'));
            $this->switchesqinqDb->delete();
            $this->goToStartOrError($this->startManagementUrl);
        } catch (Exception $ex) {
            $this->exceptions[] = $ex;
            $this->goToStartOrError($this->startManagementUrl);
        }
    }

    /**
     * Delete binding for olt port
     * 
     * @return void
     */
    public function deleteOltBinding() {
        try {
            $this->zteqinqDb->where('swid', '=', $this->routing->get('switchid', 'int'));
            $this->zteqinqDb->where('slot_number', '=', $this->routing->get('slot_number', 'int'));
            $this->zteqinqDb->where('port', '=', $this->routing->get('port', 'int'));
            $this->zteqinqDb->delete();
            $this->goToStartOrError($this->startManagementUrl);
        } catch (Exception $ex) {
            $this->exceptions[] = $ex;
            $this->goToStartOrError($this->startManagementUrl);
        }
    }

    /**
     * Generate table to render qinq pair in user profile.
     * 
     * @param string $login
     * 
     * @return string
     */
    public function showUsersVlanPair($login) {
        $login = mysql_real_escape_string($login);
        $result = '';
        $svlan = '';
        $cvlan = '';
        $this->cvlanDb->where('login', '=', $login);
        $bind = $this->cvlanDb->getAll('login');
        if (isset($bind[$login])) {
            $cvlan = $bind[$login]['cvlan'];
            $svlan_id = $bind[$login]['svlan_id'];
            $this->svlanDb->where('id', '=', $svlan_id);
            $svlans = $this->svlanDb->getAll('id');
            $svlan = $svlans[$svlan_id]['svlan'];
        } else {
            $this->switchPortDb->where('login', '=', $login);
            $switchPorts = $this->switchPortDb->getAll('login');
            if (isset($switchPorts[$login])) {
                $switchId = $switchPorts[$login]['switchid'];
                $allSwitchQinq = $this->switchesqinqDb->getAll('switchid');
                if (isset($allSwitchQinq[$switchId])) {
                    $port = $switchPorts[$login]['port'] - 1;
                    $this->switchesqinqDb->where('switchid', '=', $switchId);
                    $startCvlan = $allSwitchQinq[$switchId]['cvlan'];
                    $svlan_id = $allSwitchQinq[$switchId]['svlan_id'];
                    $this->svlanDb->where('id', '=', $svlan_id);
                    $svlans = $this->svlanDb->getAll('id');
                    if (isset($svlan[$svlan_id])) {
                        $svlan = $svlans[$svlan_id]['svlan'];
                        $cvlan = $startCvlan + $port;
                    }
                }
            }
        }
        if ($svlan !== '' and $cvlan !== '') {
            $cells = wf_TableCell('SVLAN/CVLAN', '30%', 'row2');
            $cells .= wf_TableCell(wf_tag('b') . $svlan . '/' . $cvlan . wf_tag('b', true));
            $rows = wf_TableRow($cells, 'row3');
            $result .= wf_TableBody($rows, '100%', '0');
        }
        return ($result);
    }

    /**
     * generate form for new binding.
     * 
     * @return stinrg
     */
    public function ajaxChooseForm() {
        $inputs = wf_HiddenInput('module', 'vlanmanagement');
        $inputs .= wf_HiddenInput('action', 'add');
        $inputs .= wf_HiddenInput('realm_id', $this->routing->get('realm_id', 'int'));
        $inputs .= wf_HiddenInput('svlan_id', $this->svlanId);
        $inputs .= wf_HiddenInput('cvlan_num', $this->routing->get('cvlan_num', 'int'));
        $inputs .= wf_AjaxLoader();
        $inputs2 = $this->typeSelector() . wf_delimiter(1);
        $inputs .= wf_AjaxContainer('ajtypecontainer', '', $this->types($this->defaultType));
        $inputs .= wf_Submit(__('Save'));
        $form = $inputs2 . wf_Form('', "GET", $inputs, 'glamour');
        return ($form);
    }

    /**
     * Load all occcupied cvlans by customers and equipment     
     * 
     * @return void
     */
    protected function loadOccupiedCvlans() {
        $this->loadUniversalCvlans();
        $this->loadOccupiedPorts();
        $this->loadSwitchesCvlans();
        if ($this->altCfg[self::ONUREG_QINQ_OPTION]) {
            $this->loadOltsCvlans();
        }
    }

    /**
     * Contains all cvlans occupied by customers.
     * 
     * @return void
     */
    protected function loadUniversalCvlans() {
        try {
            $this->cvlanDb->where('svlan_id', '=', $this->svlanId);
            $this->occupiedUniversal = $this->cvlanDb->getAll('cvlan');
        } catch (Exception $ex) {
            $this->exceptions[] = $ex;
        }
    }

    /**
     * Contains all cvlans occupied by switches.
     * 
     * @return void
     */
    protected function loadSwitchesCvlans() {
        try {
            $this->switchesqinqDb->where('svlan_id', '=', $this->svlanId);
            foreach ($this->switchesqinqDb->getAll('switchid') as $io => $each) {
                $portCounter = 1;
                if (isset($this->allSwitches[$each['switchid']])) {
                    $modelid = $this->allSwitches[$each['switchid']]['modelid'];
                    $portNumber = $this->allSwitchModels[$modelid]['ports'];
                    for ($i = $each['cvlan']; $i <= ($each['cvlan'] + $portNumber - 1); $i++) {
                        $this->occupiedSwitches[$i] = $this->allSwitches[$each['switchid']]['ip'] . ' | ' . $this->allSwitches[$each['switchid']]['location'];
                        $this->switchVlans[$i] = $each['switchid'];
                        if (isset($this->allPorts[$each['switchid']])) {
                            $curPorts = $this->allPorts[$each['switchid']];
                            foreach ($curPorts as $eachPort => $eachLogin) {
                                if ($eachPort == $portCounter) {
                                    $this->switchPortCustomer[$i] = array('port' => $eachPort, 'login' => $eachLogin);
                                }
                            }
                        }
                        $this->switchPortFree[$i] = $portCounter;

                        $portCounter++;
                    }
                }
            }
        } catch (Exception $ex) {
            $this->exceptions[] = $ex;
        }
    }

    /**
     * Contains all cvlans occupied by olt.
     * 
     * @return void
     */
    protected function loadOltsCvlans() {
        try {
            $this->zteqinqDb->join('RIGHT', 'zte_cards', 'swid,slot_number');
            $this->zteqinqDb->selectable('`zte_qinq`.`id`,`zte_cards`.`swid`,`zte_cards`.`slot_number`,`zte_cards`.`card_name`,`zte_qinq`.`port`,`zte_qinq`.`cvlan`');
            $this->zteqinqDb->where('zte_qinq.port', 'IS NOT', 'NULL');
            $this->zteqinqDb->where('zte_qinq.svlan_id', '=', $this->svlanId);
            $allZteBinding = $this->zteqinqDb->getAll('id');
            $this->zteqinqDb->selectable();
            if (!empty($allZteBinding)) {
                foreach ($allZteBinding as $io => $each) {
                    $maxOnuCount = 128;
                    if (isset($this->eponCards[$each['card_name']])) {
                        if ($each['card_name'] != 'ETTO' AND $each['card_name'] != 'ETTOK') {
                            $maxOnuCount = 64;
                        }
                    }
                    for ($cvlan = $each['cvlan']; $cvlan <= $each['cvlan'] + $maxOnuCount - 1; $cvlan++) {
                        $currentOlt = $this->allSwitches[$each['swid']];
                        $this->occupiedOlt[$cvlan] = $currentOlt['ip'] . ' ' . $currentOlt['location'] . ' (' . __('Slot') . ': ' . $each['slot_number'] . '/' . $each['card_name'] . ' ' . __('Port') . ': ' . $each['port'] . ')';
                        $this->occupiedOltSlot[$cvlan] = $each['slot_number'];
                        $this->occupiedOltPort[$cvlan] = $each['port'];
                        $this->occupiedOltId[$cvlan] = $each['swid'];
                    }
                }
            }
        } catch (Exception $ex) {
            $this->exceptions[] = $ex;
        }
    }

    /**
     * Load all switchports
     * 
     * @return void
     */
    protected function loadOccupiedPorts() {
        $allPortsRaw = $this->switchPortDb->getAll('id');
        if (!empty($allPortsRaw)) {
            foreach ($allPortsRaw as $io => $each) {
                $this->allPorts[$each['switchid']][$each['port']] = $each['login'];
            }
        }
    }

    /**
     * Adding html properties based on type.
     * 
     * @param int $cvlan
     * 
     * @return array
     */
    protected function setMatricContainerColor($cvlan) {
        $switchid = '';
        $check = $this->checkCvlanFree($cvlan);
        if ($check['type'] == 'olt') {
            if (isset($this->occupiedUniversal[$cvlan])) {
                $color = 'occupied_olt_with_customer';
            } else {
                $color = 'occupied_olt';
            }
            $switchid = $this->occupiedOltId[$cvlan];
        } elseif ($check['type'] == 'switch') {
            if (isset($this->switchPortCustomer[$cvlan])) {
                $color = 'occupied_switch_with_customer';
            } else {
                $color = 'occupied_switch';
            }
            if (isset($this->switchVlans[$cvlan])) {
                $switchid = $this->switchVlans[$cvlan];
            }
        } elseif ($check['type'] == 'universal') {
            $color = 'occupied_customer';
        } else {
            $color = 'free_vlan';
        }

        $onclick = $this->setMatrixOnlick($color);

        $result['switchid'] = $switchid;
        $result['color'] = $color;
        $result['onclick'] = $onclick;

        return ($result);
    }

    /**
     * Set onclick property based on class.
     * 
     * @param string $color
     * 
     * @return string
     */
    protected function setMatrixOnlick($color) {
        $onclick = '';
        switch ($color) {
            case 'free_vlan':
                $onclick = 'onclick = "vlanAcquire(this)"';
                break;
            case 'occupied_customer':
                $onclick = 'onclick = "occupiedByCustomer(this)"';
                break;
            case 'occupied_olt_with_customer':
            case 'occupied_olt':
                $onclick = 'onclick = "occupiedByOlt(this)"';
                break;
            case 'occupied_switch_with_customer':
            case 'occupied_switch':
                $onclick = 'onclick = "occupiedBySwitch(this)"';
                break;
        }
        return ($onclick);
    }

    /**
     * Render main cvlan matrix.
     * 
     * @return void
     */
    public function cvlanMatrix() {
        $result = '';
        if ($this->routing->checkGet(array('realm_id', 'svlan_id'))) {
            $result .= $this->createMatrixMainContainer();

            for ($cvlan = 1; $cvlan <= 4096; $cvlan++) {
                $result .= $this->createMatrixDataContainer($cvlan);
            }

            $result .= $this->loadMatrixJs();
        }

        show_window('', $result);
    }

    /**
     * Create main container and load stylesheets.
     * 
     * @return string
     */
    protected function createMatrixMainContainer() {
        $result = '<link rel="stylesheet" href="./skins/vlanmanagement.css" type="text/css" media="screen" />';
        $result .= wf_tag('div', false, 'cvmodal', 'id = "dialog-modal_cvmodal" title = "' . __('Choose') . '" style = "display:none; width:1px; height:1px;"');
        $result .= wf_tag('p', false, '', 'id = "content-cvmodal"');
        $result .= wf_tag('p', true);
        $result .= wf_tag('div', true);

        return ($result);
    }

    /**
     * Generate div container with data for cvlan.
     * 
     * @param int $cvlan
     * 
     * @return string
     */
    protected function createMatrixDataContainer($cvlan) {
        $matrixColorData = $this->setMatricContainerColor($cvlan);

        $result = wf_tag('div', false, 'cvlanMatrixContainer ' . $matrixColorData['color'], 'id = "container_' . $this->routing->get('realm_id', 'int') .
                '/' . $this->svlanId .
                '/' . $cvlan . '/' . $matrixColorData['switchid'] . '" ' . $matrixColorData['onclick'] . '');

        $result .= $cvlan;
        $result .= $this->createMatrixPortCaption($cvlan);
        $result .= wf_tag('div', true);

        return ($result);
    }

    /**
     * Add div with port caption if exists.
     * 
     * @param int $cvlan
     * 
     * @return string
     */
    protected function createMatrixPortCaption($cvlan) {
        $result = '';
        if (isset($this->switchPortCustomer[$cvlan])) {
            $result = wf_tag('div', false, 'port_caption') . $this->switchPortCustomer[$cvlan]['port'] . wf_tag('div', true);
        } elseif (isset($this->switchPortFree[$cvlan])) {
            $result = wf_tag('div', false, 'port_caption') . $this->switchPortFree[$cvlan] . wf_tag('div', true);
        }

        return ($result);
    }

    /**
     * Returns html string to load JS file.
     * 
     * @return string
     */
    protected function loadMatrixJs() {
        $result = '<script src = "./modules/jsc/vlanmanagement.js" type = "text/javascript"></script>';

        return ($result);
    }

    /**
     * Get all svlan by id as primary key
     * 
     * @return array
     */
    public function getAllSvlan() {
        return ($this->svlanDb->getAll('id'));
    }

    /**
     * Get all realms with id as primary key
     * 
     * @return array
     */
    public function getAllRealms() {
        return ($this->allRealms);
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

    /**
     * Show exceptions if any.
     * 
     * @return void
     */
    protected function showExceptions() {
        foreach ($this->exceptions as $io => $each) {
            show_error($each);
        }
    }

    /**
     * Log add action
     * 
     * @return void
     */
    protected function logSvlanAdd() {
        log_register('CREATE SVLAN (' . trim($this->routing->get('svlan_num', 'int')) . ')');
    }

    /**
     * Log delete action
     * 
     * @return void
     */
    protected function logSvlanDelete() {
        log_register('DELETE SVLAN (' . trim($this->routing->get('svlan_num', 'int')) . ')');
    }

    /**
     * Log edit action
     * 
     * @return void
     */
    protected function logSvlanEdit() {
        log_register('EDIT SVLAN (' . trim($this->routing->get('old_svlan_num', 'int')) . ') ' . 'ON (' . trim($this->routing->get('svlan_num', 'int')) . ')');
    }

    /**
     * Log add action
     * 
     * @return void
     */
    protected function logAdd() {
        log_register('CREATE realm (' . trim($this->routing->get('realm', 'mres')) . ')');
    }

    /**
     * Log delete action
     * 
     * @return void
     */
    protected function logDelete() {
        log_register('DELETE realm (' . trim($this->routing->get('realm', 'mres')) . ')');
    }

    /**
     * Log edit action
     * 
     * @return void
     */
    protected function logEdit() {
        log_register('EDIT realm (' . trim($this->routing->get('old_realm', 'mres')) . ') ' . 'ON (' . trim($this->routing->get('realm', 'mres')) . ')');
    }

}
