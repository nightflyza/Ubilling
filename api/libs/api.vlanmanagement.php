<?php

/**
 * Like IPAM for VLAN
 */
class VlanManagement {

    const MODULE = '?module=vlanmanagement';
    const MODULE_SVLAN = '?module=vlanmanagement&svlan=true';
    const MODULE_REALMS = '?module=vlanmanagement&realms=true';

    protected $realmDb;
    protected $svlanDb;
    protected $cvlanDb;
    protected $switchesqinqDb;
    protected $allRealms = array();
    protected $allSvlan = array();
    protected $error = array();
    protected $exceptions = array();
    protected $messages;
    public $defaultRealm = 1;
    public $defaultSvlan = 1;
    protected $realmSelector = array();
    public $routing;

    public function __construct() {
        $this->dbInit();
        $this->loadData();
        $this->routing = new ubRouting();
        $this->messages = new UbillingMessageHelper();
    }

    protected function dbInit() {
        $this->realmDb = new NyanORM('realms');
        $this->svlanDb = new NyanORM('qinq_svlan');
        $this->cvlanDb = new NyanORM('qinq_bindings');
        $this->switchesqinqDb = new NyanORM('switches_qinq');
    }

    protected function loadData() {
        $this->allRealms = $this->realmDb->getAll('id');
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

    public function chooseType() {
        $result = wf_modalAuto(web_icon_extended() . ' ' . __('QINQ for switches'), __('QINQ for switches'), $form, 'ubButton');
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

    public function linksMain() {
        $urls = wf_BackLink(UniversalQINQ::MODULE, __('Back'), false, 'ubButton');
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

    public function ajaxChooseForm() {
        $inputs = wf_HiddenInput('module', 'vlanmanager');
        $inputs .= wf_HiddenInput('realm_id', $this->routing->get('realm_id', 'int'));
        $inputs .= wf_HiddenInput('svlan_id', $this->routing->get('svlan_id', 'int'));
        $inputs .= wf_HiddenInput('cvlan_num', $this->routing->get('cvlan_num', 'int'));
        $inputs .= wf_RadioInput('qinq_type', __('QINQ for switches'), 'switches', true, true);
        $inputs .= wf_RadioInput('qinq_type', __('Universal QINQ'), 'universal', true, false);
        $inputs .= wf_Submit(__('Save'));
        $form = wf_Form('', "GET", $inputs);
        return($form);
    }

    public function cvlanMatrix() {
        $this->cvlanDb->where('svlan_id', '=', $this->routing->get('svlan_id', 'int'));
        $allVlans = $this->cvlanDb->getAll('cvlan');
        $result = '';
        if ($this->routing->checkGet(array('realm_id', 'svlan_id'))) {
            $result .= '<link rel="stylesheet" href="./skins/vlanmanagement.css" type="text/css" media="screen" />';
            $result .= wf_tag('div', false, 'cvmodal', 'id="dialog-modal_cvmodal" title="Choose" style="display:none; width:1px; height:1px;"');
            $result .= wf_tag('p', false, '', 'id="content-cvmodal"');
            $result .= wf_tag('p', true);
            $result .= wf_tag('div', true);

            for ($cvlan = 1; $cvlan <= 4096; $cvlan++) {
                if (isset($allVlans[$cvlan])) {
                    $color = 'not_free';
                } else {
                    $color = 'free';
                }
                $result .= wf_tag('div', false, 'cvlanMatrixContainer ' . $color, 'id="container_' . $this->routing->get('realm_id', 'int') .
                        '/' . $this->routing->get('svlan_id', 'int') .
                        '/' . $cvlan . '" onclick="vlanAcquire(this)"');
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
