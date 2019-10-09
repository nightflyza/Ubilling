<?php

/**
 * Like IPAM for VLAN
 */
class VlanManagement {

    const MODULE = '?module=vlanmanagement';
    const MODULE_SVLAN = '?module=vlanmanagement&svlan=true';
    const MODULE_REALMS = '?module=vlanmanagement&realms=true';
    const MODULE_UNIVERSALQINQ = '?module=universalqinq';

    protected $realmDb;
    protected $svlanDb;
    protected $cvlanDb;
    protected $switchesqinqDb;
    protected $switchesDb;
    protected $switchModelsDb;
    protected $switchPortDb;
    protected $altCfg = array();
    protected $allRealms = array();
    protected $allSvlan = array();
    protected $error = array();
    protected $exceptions = array();
    protected $messages;
    protected $defaultType;
    protected $realmSelector = array();
    protected $allSwitches = array();
    protected $allSwitchModels = array();
    protected $occupiedUniversal = array();
    protected $occupiedSwitches = array();
    public $defaultRealm = 1;
    public $defaultSvlan = 1;
    public $routing;

    public function __construct() {
        $this->dbInit();
        $this->loadData();
        $this->loadAlter();
        $this->routing = new ubRouting();
        $this->messages = new UbillingMessageHelper();
    }

    protected function dbInit() {
        $this->realmDb = new NyanORM('realms');
        $this->svlanDb = new NyanORM('qinq_svlan');
        $this->cvlanDb = new NyanORM('qinq_bindings');
        $this->switchesqinqDb = new NyanORM('switches_qinq');
        $this->switchesDb = new NyanORM('switches');
        $this->switchModelsDb = new NyanORM('switchmodels');
        $this->switchPortDb = new NyanORM('switchportassign');
    }

    protected function loadData() {
        $this->allRealms = $this->realmDb->getAll('id');
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
     * Redirects user back and show error if any
     * 
     * @return void
     */
    protected function goToStartOrError($url) {
        if (empty($this->error) and empty($this->exceptions)) {
            rcms_redirect($url);
        } else {
            $this->showError();
            if (!empty($this->exceptions)) {
                $this->showExceptions();
            }
        }
    }

    protected function validateSvlan() {
        if (!$this->checkSvlan()) {
            $this->error[] = __('Wrong value') . ': SVLAN ' . $this->routing->get('svlan_num', 'int');
        }

        if (!$this->uniqueSvlan()) {
            $this->error[] = __('Wrong value') . ': SVLAN ' . $this->routing->get('svlan_num', 'int') . ' ' . __('already exists');
        }

        if ($this->protectDefault()) {
            $this->error[] = __('Default SVLAN is protected and cannot be deleted or edited');
        }


        if (!empty($this->error)) {
            return(false);
        }
        return(true);
    }

    protected function protectDefault() {
        if (($this->routing->get('action') == 'edit') or ( ($this->routing->get('action') == 'delete'))) {
            if (($this->routing->get('old_svlan_num', 'int') == 0 ) and ( $this->routing->get('realm_id', 'int') == 1)) {
                return(true);
            }
        }
        return false;
    }

    protected function checkSvlan() {
        if (($this->routing->get('svlan', 'int') >= 0) and ( $this->routing->get('svlan', 'int') <= 4096)) {
            return(true);
        }
        return (false);
    }

    protected function uniqueSvlan() {
        if ($this->routing->get('action') == 'add') {
            $this->svlanDb->where('realm_id', '=', $this->routing->get('realm_id', 'int'));
            $allSvlan = $this->svlanDb->getAll('svlan');
            if (isset($allSvlan[$this->routing->get('svlan_num')])) {
                return(false);
            }
        }
        if ($this->routing->get('action') == 'edit') {
            $this->svlanDb->where('realm_id', '=', $this->routing->get('realm_id', 'int'));
            $this->svlanDb->where('svlan', '!=', $this->routing->get('old_svlan_num', 'int'));
            $allSvlan = $this->svlanDb->getAll('svlan');
            if (isset($allSvlan[$this->routing->get('svlan_num')])) {
                return(false);
            }
        }
        return(true);
    }

    public function addSvlan() {
        try {
            if ($this->validateSvlan()) {
                $this->svlanDb->data('realm_id', $this->routing->get('realm_id', 'int'));
                $this->svlanDb->data('svlan', $this->routing->get('svlan_num', 'int'));
                $this->svlanDb->data('description', $this->routing->get('description', 'mres'));
                $this->svlanDb->create();
                $this->logSvlanAdd();
            }
            $this->goToStartOrError(self::MODULE_SVLAN . '&realm_id=' . $this->routing->get('realm_id', 'int'));
        } catch (Exception $ex) {
            $this->exceptions[] = $ex;
            $this->goToStartOrError(self::MODULE_SVLAN . '&realm_id=' . $this->routing->get('realm_id', 'int'));
        }
    }

    public function editSvlan() {
        try {
            if ($this->validateSvlan()) {
                $this->svlanDb->where('realm_id', '=', $this->routing->get('realm_id', 'int'));
                $this->svlanDb->where('id', '=', $this->routing->get('id', 'int'));
                $this->svlanDb->data('svlan', $this->routing->get('svlan_num', 'int'));
                $this->svlanDb->data('description', $this->routing->get('description', 'mres'));
                $this->svlanDb->save();
                $this->logSvlanDelete();
            }
            $this->goToStartOrError(self::MODULE_SVLAN . '&realm_id=' . $this->routing->get('realm_id', 'int'));
        } catch (Exception $ex) {
            $this->exceptions[] = $ex;
            $this->goToStartOrError(self::MODULE_SVLAN . '&realm_id=' . $this->routing->get('realm_id', 'int'));
        }
    }

    public function deleteSvlan() {
        try {
            if ($this->validateSvlan()) {
                $this->svlanDb->where('realm_id', '=', $this->routing->get('realm_id', 'int'));
                $this->svlanDb->where('id', '=', $this->routing->get('id', 'int'));
                $this->svlanDb->delete();

//delete all the qinq bindings for this svlan
                $this->cvlanDb->where('svlan_id', '=', $this->routing->get('id', 'int'));
                $this->cvlanDb->delete();

                $this->logSvlanDelete();
            }
            $this->goToStartOrError(self::MODULE_SVLAN . '&realm_id=' . $this->routing->get('realm_id', 'int'));
        } catch (Exception $ex) {
            $this->exceptions[] = $ex;
            $this->goToStartOrError(self::MODULE_SVLAN . '&realm_id=' . $this->routing->get('realm_id', 'int'));
        }
    }

    protected function addSvlanForm() {
        $addControls = wf_HiddenInput('module', 'vlanmanagement');
        $addControls .= wf_HiddenInput('svlan', 'true');
        $addControls .= wf_HiddenInput('action', 'add');
        $addControls .= wf_HiddenInput('realm_id', $this->routing->get('realm_id', 'int'));
        $addControls .= wf_TextInput('svlan_num', 'SVLAN', '', true, '');
        $addControls .= wf_TextInput('description', __('Description'), '', true, '', '');
        $addControls .= wf_Submit('Save');
        $form = wf_Form('', 'GET', $addControls, 'glamour');
        return(wf_modalAuto(web_icon_create() . ' ' . __('Create new entry'), __('Create new entry'), $form, 'ubButton'));
    }

    public function ajaxEditSvlan($encode) {
        $decode = base64_decode($encode);
        $split = explode("_", $decode);
        $each = explode('/', $split[1]);
        $addControls = wf_HiddenInput('module', 'vlanmanagement');
        $addControls .= wf_HiddenInput('svlan', 'true');
        $addControls .= wf_HiddenInput('action', 'edit');
        $addControls .= wf_HiddenInput('id', $each[0]);
        $addControls .= wf_HiddenInput('realm_id', $each[1]);
        $addControls .= wf_TextInput('svlan_num', 'SVLAN', $each[2], true, '');
        $addControls .= wf_TextInput('description', __('Description'), $each[3], true, '');
        $addControls .= wf_HiddenInput('old_svlan_num', $each[2]);
        $addControls .= wf_Submit('Save');
        $form = wf_Form('', 'GET', $addControls, 'glamour');
        return($form);
    }

    protected function realmSvlanSelector() {
        if (!empty($this->allRealms)) {
            foreach ($this->allRealms as $id => $each) {
                $params[$id] = $each['realm'] . ' | ' . $each['description'];
            }
        }
        $inputs = wf_HiddenInput('module', 'vlanmanagement');
        $inputs .= wf_HiddenInput('svlan', 'true');
        $inputs .= wf_SelectorAC('realm_id', $params, __('Realm'), $this->routing->get('realm_id', 'int'));
        return(wf_Form("", "GET", $inputs));
    }

    public function svlanSelector($realmId) {
        $realmId = vf($realmId, 3);
        $this->svlanDb->where('realm_id', '=', $realmId);
        $allSvlan = $this->svlanDb->getAll('id');
        $allSvlanSelector = array('' => '---');
        if (!empty($allSvlan)) {
            foreach ($allSvlan as $id => $each) {
                $allSvlanSelector[$id] = $each['svlan'] . ' | ' . $each['description'];
            }
        }
        $result = wf_HiddenInput('module', 'vlanmanagement');
        $result .= wf_HiddenInput('realm_id', $realmId);
        $result .= wf_SelectorAC('svlan_id', $allSvlanSelector, 'SVLAN', $this->routing->get('svlan_id'), true);

        return ($result);
    }

    protected function backSvlan() {
        return(wf_BackLink(self::MODULE, __('Back'), false, 'ubButton'));
    }

    public function linksSvlan() {
        show_window('', '' .
                $this->backSvlan() .
                $this->addSvlanForm()
        );
        show_window('', $this->realmSvlanSelector());
    }

    public function showSvlanAll() {
        $modal = '<link rel="stylesheet" href="./skins/vlanmanagement.css" type="text/css" media="screen" />';
        $modal .= wf_tag('div', false, 'cvmodal', 'id="dialog-modal_cvmodal" title="Choose" style="display:none; width:1px; height:1px;"');
        $modal .= wf_tag('p', false, '', 'id="content-cvmodal"');
        $modal .= wf_tag('p', true);
        $modal .= wf_tag('div', true);
        $modal .= '<script src="/modules/jsc/vlanmanagement.js" type="text/javascript"></script>';

        $columns = array('ID', 'SVLAN', 'Description', 'Actions');
        $opts = '"order": [[ 0, "desc" ]]';
        $result = '';
        $ajaxURL = '' . self::MODULE_SVLAN . '&action=ajax&realm_id=' . $this->routing->get('realm_id', 'int');
        $result .= show_window('', $modal . wf_JqDtLoader($columns, $ajaxURL, false, __('Realms'), 100, $opts));
        return ($result);
    }

    public function ajaxSvlanData() {
        $this->svlanDb->where('realm_id', '=', $this->routing->get('realm_id', 'int'));
        $this->allSvlan = $this->svlanDb->getAll('id');
        $json = new wf_JqDtHelper();
        if (!empty($this->allSvlan)) {
            foreach ($this->allSvlan as $io => $each) {
                $eachId = base64_encode('container_' . $each['id'] . '/' . $each['realm_id'] . '/' . $each['svlan'] . '/' . $each['description']);
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

    public function chooseType() {
        $result = wf_modalAuto(web_icon_extended() . ' ' . __('QINQ for switches'), __('QINQ for switches'), $form, 'ubButton');
    }

    public function linksMain() {
        $urls = wf_Link(self::MODULE_UNIVERSALQINQ, web_icon_extended() . 'UniversalQINQ', false, 'ubButton');
        $urls .= wf_Link(self::MODULE_SVLAN . '&realm_id=1', web_icon_extended() . 'SVLAN', false, 'ubButton');
        $urls .= wf_link(self::MODULE_REALMS, web_icon_extended() . __('Realms'), false, 'ubButton');
        show_window('', $urls);
        show_window('', $this->realmAndSvlanSelectors());
    }

    public function realmAndSvlanSelectors() {
        $result = wf_AjaxLoader();
        $inputs = $this->realmMainSelector();
        $inputs .= wf_delimiter();
        $inputs2 = wf_AjaxContainer('ajcontainer', '', $this->svlanSelector($this->routing->get('realm_id', 'int') ? $this->routing->get('realm_id', 'int') : $this->defaultRealm));
        $inputs2 .= wf_delimiter();
        $result .= $inputs . wf_Form("", 'GET', $inputs2);
        return($result);
    }

    protected function realmMainSelector() {
        if (!empty($this->allRealms)) {
            foreach ($this->allRealms as $id => $each) {
                $this->realmSelector[self::MODULE . '&action=realm_id_select&ajrealmid=' . $id] = $each['realm'] . ' | ' . $each['description'];
            }

            reset($this->allRealms);
            $this->defaultRealm = key($this->allRealms);
        }

        return(wf_AjaxSelectorAC('ajcontainer', $this->realmSelector, __('Select realm'), self::MODULE . '&action=realm_id_select&ajrealmid=' . $this->routing->get('realm_id', 'int'), false));
    }

    protected function typeSelector() {
        $selector = array(self::MODULE . '&action=choosetype&type=none' => '---');

        $switches = self::MODULE
                . '&action=choosetype&type=qinqswitches&'
                . '&cvlan_num=' . $this->routing->get('cvlan_num', 'int');
        $universal = self::MODULE
                . '&action=choosetype&type=universalqinq&'
                . '&cvlan_num=' . $this->routing->get('cvlan_num', 'int');

        if ($this->altCfg['QINQ_ENABLED']) {
            $selector[$switches] = __('QINQ for switches');
            $this->defaultType = $switches;
            if ($this->altCfg['UNIVERSAL_QINQ_ENABLED'] and cfr('UNIVERSALQINQCONFIG')) {
                $selector[$universal] = __('Universal QINQ');
            }
        } else {
            if ($this->altCfg['UNIVERSAL_QINQ_ENABLED'] and cfr('UNIVERSALQINQCONFIG')) {
                $selector[$universal] = __('Universal QINQ');
                $this->defaultType = $universal;
            } else {
                $this->defaultType = '---';
            }
        }

        return(wf_AjaxSelectorAC('ajtypecontainer', $selector, __('Choose type'), $this->defaultType, false));
    }

    protected function switchSelector() {
        $query = "SELECT `switches`.`id`,`switches`.`ip`,`switches`.`location` FROM `switches` LEFT JOIN `switches_qinq` ON `switches`.`id` = `switches_qinq`.`switchid` WHERE `switches_qinq`.`switchid` IS NULL";
        $switches = simple_queryall($query);

        foreach ($switches as $io => $each) {
            $options[$each['id']] = $each['ip'] . ' ' . $each['location'];
        }

        return(wf_Selector('qinqswitchid', $options, __('Select switch')));
    }

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

        return($result);
    }

    protected function addNewSwitchBinding() {
        $this->occupiedSwitches();
        $this->occupiedCvlans();
        $used = false;
        $type = '';
        $modelid = $this->allSwitches[$this->routing->get('qinqswitchid')]['modelid'];
        $port_number = $this->allSwitchModels[$modelid]['ports'];
        $lastCvlan = $this->routing->get('cvlan_num', 'int') + $port_number;
        for ($i = $this->routing->get('cvlan_num', 'int'); $i <= $lastCvlan; $i++) {
            if (isset($this->occupiedSwitches[$i])) {
                $used = $this->occupiedSwitches[$i];
                $type = 'switch';
                break;
            }
            if (isset($this->occupiedUniversal[$i])) {
                $used = $this->occupiedUniversal[$i];
                $type = 'universal';
                break;
            }
        }
        if (!$used) {
            $switchesQinQ = new SwitchesQinQ();
            $qinqSaveResult = $switchesQinQ->saveQinQ();
            if (!empty(($qinqSaveResult))) {
                $this->error[] = $qinqSaveResult;
            }
        } else {
            switch ($type) {
                case'switch':
                    $this->error[] = __('Error') . ': ' . __('trying allocate')
                            . ' ' . "CVLAN " . _("from") . ' ' . $this->routing->get('cvlan_num', 'int')
                            . ' ' . __('to') . $lastCvlan
                            . '. CVLAN ' . $i
                            . ' ' . __('occcupied by switch: ') . $used;
                    break;

                case 'universal':
                    $this->error[] = __("Error") . ': ' . __('trying allocate') . ' '
                            . "CVLAN " . __("from") . ' ' . $this->routing->get('cvlan_num', 'int')
                            . ' ' . __('to') . ' ' . $lastCvlan
                            . '. CVLAN ' . $i . ' ' . __('occcupied by login: ')
                            . wf_link("?module=userprofile&username="
                                    . $used['login'], $used['login']
                    );
                    break;
            }
        }
    }

    public function addNewBinding() {
        try {
            switch ($this->routing->get('type')) {
                case 'universalqinq':
                    $universalqinq = new UniversalQINQ();
                    $universalqinq->add();
                    break;
                case 'qinqswitches':
                    $this->addNewSwitchBinding();
                    break;
            }
            $this->goToStartOrError(self::MODULE . '&realm_id=' . $this->routing->get('realm_id', 'int') . '&svlan_id=' . $this->routing->get('svlan_id', 'int'));
        } catch (Exception $ex) {
            $this->exceptions[] = $ex;
            $this->goToStartOrError(self::MODULE . '&realm_id=' . $this->routing->get('realm_id', 'int') . '&svlan_id=' . $this->routing->get('svlan_id', 'int'));
        }
    }

    public function ajaxCustomer() {
        $result = '';
        $this->cvlanDb->where('svlan_id', '=', $this->routing->get('svlan_id', 'int'));
        $this->cvlanDb->where('cvlan', '=', $this->routing->get('cvlan_num', 'int'));
        $data = $this->cvlanDb->getAll('cvlan');
        $login = $data[$this->routing->get('cvlan_num', 'int')]['login'];
        $userData = zb_UserGetAllData($login);
        $userData = $userData[$login];
        $result .= __('Customer') . ': ';
        $result .= wf_Link("?module=userprofile&username=" . $login, $userData['fulladress'] . ' ' . $userData['realname'], true);
        $result .= wf_delimiter(2);
        $result .= wf_Link(self::MODULE_UNIVERSALQINQ . '&action=delete&type=universal&realm_id=' . $this->routing->get('realm_id', 'int') . '&svlan_id=' . $this->routing->get('svlan_id') . '&id=' . $data[$this->routing->get('cvlan_num', 'int')]['id'], web_delete_icon() . __('Delete binding'), false, 'ubButton');

        return($result);
    }

    public function ajaxSwitch() {
        $result = '';
        $this->allSwitches = $this->switchesDb->getAll('id');
        $this->switchesqinqDb->where('svlan_id', '=', $this->routing->get('svlan_id', 'int'));
        $data = $this->switchesqinqDb->getAll('svlan_id');
        $data = $data[$this->routing->get('svlan_id', 'int')];
        $switch = $this->allSwitches[$data['switchid']];
        $result .= __("Switch") . ': ';
        $result .= wf_Link("?module=switches&edit=" . $data['switchid'], $switch['ip'] . ' ' . $switch['location']);
        $result .= wf_delimiter(3);
        $result .= wf_Link(self::MODULE . '&action=deletebinding&realm_id=' . $this->routing->get('realm_id', 'int') . '&svlan_id=' . $this->routing->get('svlan_id', 'int') . '&switchid=' . $data['switchid'], web_delete_icon() . __('Delete binding'), false, 'ubButton');

        return($result);
    }

    public function deleteBinding() {
        $this->switchesqinqDb->where('switchid', '=', $this->routing->get('switchid', 'int'));
        $this->switchesqinqDb->delete();
        $this->goToStartOrError(self::MODULE . '&realm_id=' . $this->routing->get('realm_id', 'int') . '&svlan_id=' . $this->routing->get('svlan_id', 'int'));
    }

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
                $port = $switchPorts[$login]['port'] - 1;
                $this->switchesqinqDb->where('switchid', '=', $switchId);
                $allSwitchQinq = $this->switchesqinqDb->getAll('switchid');
                $startCvlan = $allSwitchQinq[$switchId]['cvlan'];
                $svlan_id = $allSwitchQinq[$switchId]['svlan_id'];
                $this->svlanDb->where('id', '=', $svlan_id);
                $svlans = $this->svlanDb->getAll('id');
                $svlan = $svlans[$svlan_id]['svlan'];
                $cvlan = $startCvlan + $port;
            }
        }
        if ($svlan !== '' and $cvlan !== '') {
            $cells = wf_TableCell('SVLAN/CVLAN', '30%', 'row2');
            $cells .= wf_TableCell(wf_tag('b') . $svlan . '/' . $cvlan . wf_tag('b', true));
            $rows = wf_TableRow($cells, 'row3');
            $result .= wf_TableBody($rows, '100%', '0');
        }
        return($result);
    }

    public function ajaxChooseForm() {
        $inputs = wf_HiddenInput('module', 'vlanmanagement');
        $inputs .= wf_HiddenInput('action', 'add');
        $inputs .= wf_HiddenInput('realm_id', $this->routing->get('realm_id', 'int'));
        $inputs .= wf_HiddenInput('svlan_id', $this->routing->get('svlan_id', 'int'));
        $inputs .= wf_HiddenInput('cvlan_num', $this->routing->get('cvlan_num', 'int'));
        $inputs .= wf_AjaxLoader();
        $inputs2 = $this->typeSelector() . wf_delimiter(1);
        $inputs .= wf_AjaxContainer('ajtypecontainer', '', $this->types($this->defaultType));
        $inputs .= wf_Submit(__('Save'));
        $form = $inputs2 . wf_Form('', "GET", $inputs, 'glamour');
        return($form);
    }

    protected function occupiedCvlans() {
        $this->cvlanDb->where('svlan_id', '=', $this->routing->get('svlan_id', 'int'));
        $this->occupiedUniversal = $this->cvlanDb->getAll('cvlan');
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

    public function cvlanMatrix() {
        $this->occupiedCvlans();
        $this->occupiedSwitches();
        $result = '';
        if ($this->routing->checkGet(array('realm_id', 'svlan_id'))) {
            $result .= '<link rel="stylesheet" href="./skins/vlanmanagement.css" type="text/css" media="screen" />';
            $result .= wf_tag('div', false, 'cvmodal', 'id="dialog-modal_cvmodal" title="Choose" style="display:none; width:1px; height:1px;"');
            $result .= wf_tag('p', false, '', 'id="content-cvmodal"');
            $result .= wf_tag('p', true);
            $result .= wf_tag('div', true);

            for ($cvlan = 1; $cvlan <= 4096; $cvlan++) {
                if (isset($this->occupiedUniversal[$cvlan])) {
                    $color = 'occupied_customer';
                } elseif (isset($this->occupiedSwitches[$cvlan])) {
                    $color = 'occupied_switch';
                } else {
                    $color = 'free_vlan';
                }

                switch ($color) {
                    case 'free_vlan':
                        $onclick = 'onclick="vlanAcquire(this)"';
                        break;
                    case 'occupied_customer':
                        $onclick = 'onclick="occupiedByCustomer(this)"';
                        break;
                    case 'occupied_switch':
                        $onclick = 'onclick="occupiedBySwitch(this)"';
                        break;
                }
                $result .= wf_tag('div', false, 'cvlanMatrixContainer ' . $color, 'id="container_' . $this->routing->get('realm_id', 'int') .
                        '/' . $this->routing->get('svlan_id', 'int') .
                        '/' . $cvlan . '" ' . $onclick . '');

                $result .= $cvlan;
                $result .= wf_tag('div', true);
            }
            $result .= '<script src="/modules/jsc/vlanmanagement.js" type="text/javascript"></script>';
        }
        show_window('', $result);
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
