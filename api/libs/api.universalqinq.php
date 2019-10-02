<?php

class UniversalQINQ {

    CONST MODULE = '?module=universalqinq';

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
     * Contains system alter config as key=>value
     * 
     * @var array
     */
    protected $altCfg;

    /**
     * Placeholder for ubRouting object
     * 
     * @var object
     */
    public $routing;

    public function __construct() {
        $this->qinqdb = new nya_qinq;
        $this->loadAlter();
        $this->initRouting();
        $this->loadData();

        //$this->tmp = new nyan_qinq();
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
    }

    /**
     * Redirects user back and show error if any
     * 
     * @return void
     */
    protected function goToStart() {
        if (empty($this->error)) {
            rcms_redirect(self::MODULE);
        } else {
            $this->showError();
            if (!empty($this->exceptions)) {
                $this->showExceptions();
            }
        }
    }

    /**
     * Check if svlan is int and has value from 0 to 4096
     * 
     * @return bool
     */
    protected function validateSvlan() {
        if (($this->routing->get('svlan', 'int') >= 0) and ( $this->routing->get('svlan', 'int') <= 4096)) {
            return(true);
        } else {
            return(false);
        }
    }

    /**
     * Check if cvlan is int and has value from 1 to 4096
     * 
     * @return bool
     */
    protected function validateCvlan() {
        if (($this->routing->get('cvlan', 'int') >= 1) and ( $this->routing->get('cvlan', 'int') <= 4096)) {
            return(true);
        } else {
            return(false);
        }
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

    /**
     * Check all validation function and return error if something didn't pass
     * 
     * @param string $action
     * 
     * @return bool
     */
    protected function validator($action = '') {
        if (!$this->validateSvlan()) {
            $this->error[] = __('Wrong value') . ': SVLAN ' . $this->routing->get('svlan', 'int');
        }

        if (!$this->validateCvlan()) {
            $this->error[] = __('Wrong value') . ': CVLAN ' . $this->routing->get('cvlan', 'int');
        }
        if (!$this->isUserExists()) {
            $this->error[] = __('User does not exist') . ' : ' . $this->routing->get('login', 'mres');
        }

        if (!$this->isUserUnique()) {
            $this->error[] = __('There is entry for this login') . ' : ' . $this->routing->get('login', 'mres');
        }

        if (!empty($this->error)) {
            return(false);
        } else {
            return(true);
        }
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
                $this->qinqdb->data('svlan', trim($this->routing->get('svlan', 'int')));
                $this->qinqdb->data('cvlan', trim($this->routing->get('cvlan', 'int')));
                $this->qinqdb->create();
                $this->logAdd();
            }
            $this->goToStart();
        } catch (Exception $ex) {
            $this->exceptions[] = $ex;
            $this->goToStart();
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
            $this->goToStart();
        } catch (Exception $ex) {
            $this->exceptions[] = $ex;
            $this->goToStart();
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
                $this->qinqdb->data('cvlan', trim($this->routing->get('cvlan', 'int')));
                $this->qinqdb->save();
                $this->logEdit();
            }
            $this->goToStart();
        } catch (Exception $ex) {
            $this->exceptions[] = $ex;
            $this->goToStart();
        }
    }

    /**
     * Show all the entries
     * 
     * @return string
     */
    public function showAll() {
        $columns = array(__('ID'), 'S-VLAN', 'C-VLAN', 'Address', 'Real Name', 'Actions');
        $opts = '"order": [[ 0, "desc" ]]';
        $result = '';
        $ajaxURL = '' . self::MODULE . '&ajax=true';
        $result .= show_window('', wf_JqDtLoader($columns, $ajaxURL, false, 'UniversalQINQ', 100, $opts));
        return ($result);
    }

    /**
     * Form to create new entry
     * 
     * @return void
     */
    public function addForm() {
        $addControls = wf_HiddenInput('module', 'universalqinq');
        $addControls .= wf_HiddenInput('action', 'add');
        $addControls .= wf_TextInput('login', __('Login'), '', true, '');
        $addControls .= wf_TextInput('svlan', 'S-VLAN', '', true, '', 'digits');
        $addControls .= wf_TextInput('cvlan', 'C-VLAN', '', true, '', 'digits');
        $addControls .= wf_Submit('Save');
        $form = wf_Form('', 'GET', $addControls, 'glamour');
        show_window('', wf_modalAuto(web_icon_extended() . ' ' . __('Create new entry'), __('Create new entry'), $form, 'ubButton'));
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
        $addControls .= wf_TextInput('svlan', 'S-VLAN', $each['svlan'], true, '', 'digits');
        $addControls .= wf_TextInput('cvlan', 'C-VLAN', $each['cvlan'], true, '', 'digits');
        $addControls .= wf_HiddenInput('old_login', $each['login']);
        $addControls .= wf_HiddenInput('old_svlan', $each['svlan']);
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
                $data[] = $each['svlan'];
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
                . trim($this->routing->get('cvlan', 'int'))
        );
    }

    /**
     * Log delete action
     * 
     * @return void
     */
    protected function logDelete() {
        log_register('DELETE universalqinq: (' .
                trim($this->routing->get('login', 'mres')) .
                ') s' .
                trim($this->routing->get('svlan', 'int')) .
                '/c' .
                trim($this->routing->get('cvlan', 'int'))
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
                . trim($this->routing->get('cvlan', 'int'))
        );
    }

}
